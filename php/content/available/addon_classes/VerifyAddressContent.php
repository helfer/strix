<?php

define('SKIPPED',-1);
define('NOT_VERIFIED',0);
define('DB_ENTRY',1);
define('TEL_ENTRY',2);
define('MAN_ENTRY',3);

class VerifyAddressContent extends Content
{

		protected $normal = 5; //weight normalization factor
		var $tolerance_db = 140; //matches above tolerance will not be retained
		var $tolerance_tel = 70;
		protected $tolerance_plz = 20;
		var $match_threshold = 6;
		var $decent_threshold = 50;
		var $max_plz = 2;
		
		//TODO: ...
		var $student_group_id = 4;
		var $exam_file_type = 'pdf';
		
		//these two fellas should not be here but instead passed by the funct telsearch_search...
		var $search_sum = 0;
		var $num_queries = 0;
		
		protected $form_submitted = FALSE;


	function display()
	{
		if($this->form_submitted)
		{
			return 'FORM submitted: '.$this->process_msg . '<br /> '.
			'<a href="">next exam</a>';	
		}
		
		/* //for testing the eliminateDoubles function
		$ary = array(array('a'=>'hola','b'=>'hula'),
		array('a'=>'hola','b'=>'hula'),
		array('a'=>'hola','b'=>'hulu'),
		array('17'=>'51'),
		'a',
		array('17'=>'51'));
		
		print_r($ary);
		$ary = $this->eliminateDoubles($ary);
		print_r($ary);
		return 'TEST';
		*/
		
		
		//four states state machine!
		$sql = SqlQuery::getInstance();
		
		$html = '';
		if(isset($this->process_msg))
			$html .= '<p class="error">'.$this->process_msg.'</p>';	

		$query = "SELECT * FROM IBO_scanned_exam WHERE verified < 1 ORDER BY changed ASC LIMIT 1"; //verified='\0'
		$exam = $sql->singleRowQuery($query);
		
		if(count($exam) == 0)
			return '<p>NO ROWS RETURNED. You have verified all exams!</p>';

		//update "changed" time of exam...
		$now = date('Y-m-d H:i:s');
		$response2 = $sql->updateQuery(
			'IBO_scanned_exam',
			array('verified_by'=>$GLOBALS['user']->id,'changed'=>$now),
			array('id'=>$exam['id'])
		);
				
		$entry = unserialize($exam['info_fields']);
		
		
		$firstname = $entry['first_name'];
		$lastname = $entry['last_name'];
		$addr = $entry['street'];
		$co = $entry['co'];
		$plz = $entry['zip'];
		$city = $entry['city'];
		$id = $exam['id'];
		$birthday = $entry['birthday'];
		$filename = reset(explode('.',$exam['exam_file'])).'.'.$this->exam_file_type;

		
		//SEARCH DATABASE:
		$match = FALSE;
		
		$html .= '<div style="border:1px solid black; margin-bottom:2em">';

		$html .= '<p>Searched for <b>'.$firstname.' '.$lastname.' in '.$plz.' </b>';
		
		//Include pdf image:
		$html .= '<a href="http://www.ibosuisse.ch/files/exams/'.$filename.'" target="_blank">'.$filename.'</a></p>';
			   

		$db_entries = $this->db_search($match,$id,$firstname,$lastname,$addr,$plz,$city,$birthday);
		
		$dbform = $this->getForm('db_entries_form',array('search'=>$entry,'find'=>$db_entries,'eid'=>$id));
	   
		$html .= $dbform->getHtml($this->id);	   
	   
		//only if no perfect match was found
		if(!$match)
		{
			//SEARCH TELSEARCH:
			$telsearch_entries = $this->telsearch_search($id,$firstname,$lastname,$addr,$plz,$city);
			
			$telsearch_form = $this->getForm('telsearch_entries_form',array('search'=>$entry,'find'=>$telsearch_entries,'eid'=>$id));
		
			$html .= $telsearch_form->getHtml($this->id);
		}
		
		
		//MANUAL Entry:
		$html .= $this->getForm('manual_entry_form',array('entry'=>$entry,'eid'=>$id))->getHtml($this->id);
		
		
		$html .= '</div>';
		return $html;
	}
	
	function process()
	{
				
		if(!isset($_POST['radio'])){
			$this->err_msg ='ERROR in POST: radio missing';
			return;
		}
		
		$query = 'SELECT * FROM `scanned_exams` WHERE id='.$_POST['scanned_exam_id'];
		$response = sql_query($query);
		if(!$response || mysql_num_rows($response) == 0)
			$this->err_msg = 'Could not find any exam with id='.$_POST['scanned_exam_id'];
				$exam = mysql_fetch_assoc($response);
		if($exam['verified'] == 1){
			$this->err_msg = 'Exam already verified by: '.$exam['verified_by'];
			return;	
		}
		


		$student_id = 0;
		$response = 0;
		$sw = explode(':',$_POST['radio']);
		switch($sw[0]){
			case 'db':
				$student_id = $sw[1];
				$response = 1;

				break;
			case 'telsearch':
			
				//People data.
				$pieces = explode('#',$sw[1]);
			
				//make new user, then do the same thing as above
				//echo '<br/>telsearch case<br/>';
				$sql = 'INSERT INTO `students` (`id`, `user_id`, `vorname`, `name`, `strasse`, `plz`, `ort`, `mobil`, `email`, `telephone`, `lehrer_id`, `language_id`, `class`) VALUES (NULL, 0, \''.$exam['firstname'].'\', \''.$pieces[1].'\', \''.$pieces[2].'\', \''.$pieces[3].'\', \''.$pieces[4].'\', \'\', \'\',\'\', \'0\', \''.$exam['language'].'\',\''.$exam['class'].'\')';
				$response = sql_query($sql);
				
				$student_id = mysql_insert_id();
				
				break;
			case 'manual':
				if(is_numeric($_POST['student_id'])){ //MODIFICATION OF EXISTING ENTRY!		
					$query = 'UPDATE students SET `vorname`=\''.$_POST['firstname'].'\' ,`name`=\''.$_POST['lastname'].'\' ,`strasse`=\''.$_POST['addr'].'\' ,`plz`=\''.$_POST['plz'].'\' ,`ort`=\''.$_POST['city'].'\', `class`=\''.$exam['class'].'\' WHERE `id`='.$_POST['student_id'];
					$response = mysql_query($query);
					//echo $query;
					
					$student_id = $_POST['student_id'];					
				
				//,\''.$_POST['firstname'].$_POST['lastname'].rand(1,9).'\', \'pass\'
				} else { // NEW ENTRY!
					$sql = 'INSERT INTO `students` (`id`, `user_id`, `vorname`, `name`, `strasse`, `plz`, `ort`, `mobil`, `email`, `telephone`, `lehrer_id`, `language_id`, `class`) VALUES (NULL, 0, \''.$_POST['firstname'].'\', \''.$_POST['lastname'].'\', \''.$_POST['addr'].'\', \''.$_POST['plz'].'\', \''.$_POST['city'].'\', \'\', \'\',\'\', \'0\', \''.$exam['language'].'\',\''.$exam['class'].'\')';
					$response = sql_query($sql);
					
					$student_id = mysql_insert_id();
				}		
					
				
				break;
				
			default:
				$this->err_msg = '<br/>radio switch is broken!<br/>';
				break;
			
			}
			
		//INSERT EXAM INTO STUDENT_EXAM, SET EXAM AS CORRECTED
				
			if($response && $student_id != 0){
				
				//determine language_id from language:
				$sql = 'SELECT id,abb FROM languages WHERE abb=\''.$exam['language'].'\'';
				error($sql);
				$ret = sql_query($sql);
				if(mysql_num_rows($ret) != 1)
					$this->err_msg = 'Could not determine exam language: '.$exam['language'];
				else{
					$lns = mysql_fetch_assoc($ret);
					$language_id = $lns['id'];
				}
				
								
				$query2 = 'INSERT INTO `student_exam` (`id`, `student_id`, `exam_id`, `language_id`, `answer`, `total`, `t_score`, `rang`, `rang_de`, `rang_fr`, `filename`, `passed`) VALUES (NULL, \''.$student_id.'\', \''.$exam['exam_id'].'\', \''.$language_id.'\', \''.$exam['answers'].'\', \'0\', \'\', \'0\', \'0\', \'0\',\''.$exam['filename'].'\', \'0\');';
				$response2 = sql_query($query2);
				
							
				if($response2){						
					$query3 = 'UPDATE `scanned_exams` SET verified=1, matched_student_id=\''.$student_id.'\', verified_by=\''.$_SESSION['user']->login.'\'WHERE id='.$exam['id'];
					$response3 = sql_query($query3);
					if(!$response3){
						$this->err_msg = 'COULD NOT MARK exam as CORRECTED! reason: '.mysql_error();	
					}
					
					
					//CREATE WEBSITE USER HERE:
					
					
					$quer = 'SELECT * FROM `students` WHERE `id`='.$student_id;
					$resu = sql_query($quer);
					$student = mysql_fetch_assoc($resu);
					
					//create login...
					$login = '';
					$login .= substr($student['vorname'],0,2);
					$login .= substr($student['name'],0,2);
						
					$login .= $student['id'];
					
					//create pass...
					$pass = $this->createRandomPassword();
					
					
					
					$sql = 'INSERT INTO `users` (`id`, `login`, `password`, `user_group_id`, `first_name`, `last_name`, `street`, `zip`, `city`, `email`, `phone`, `mobile`, `sex`, `last_login`) VALUES (NULL, \''.$login.'\', MD5(\''.$pass.'\'), \''.$this->student_group_id.'\', \''.$student['vorname'].'\', \''.$student['name'].'\', \''.$student['strasse'].'\', \''.$student['plz'].'\', \''.$student['ort'].'\', \'email\', \'phone\', \''.$pass.'\', \'0\', NOW());';
					error($sql);
					$resp = sql_query($sql);
					if(!$resp){
						$this->err_msg = 'CREATE user failed, reason: '.mysql_error();
					} else {
						$sql = 'UPDATE `students` SET `user_id`='.mysql_insert_id().' WHERE id='.$student['id'];
						error($sql);
						$resp = sql_query($sql);
						if(!$resp){
							$this->err_msg = 'CREATE user failed, reason: '.mysql_error();
						} else {
							echo '<p class="error">All queries executed successfully!</p>';
						}
					}
					//------------------------
					
					
					
					
					
				} else {
					$this->err_msg = 'DATABASE exam insert failed, reason: '.mysql_error();
				
				}	
			} else {
				$this->err_msg = 'DATABASE update failed, reason: '.mysql_error();
			}
			
			
			
	}
	
	/**
	 * This was translated from an old version, so it's very likely it's buggy!
	 */
	public function db_entries_form($vector)
	{
		$form = new SimpleForm(__METHOD__);
		$form->setVector($vector);
		
		$perfect = FALSE; //indicates whether a perfect match was found.
		
		
		$sel = $vector['find'];
		$entry = $vector['search'];
		
		$firstname = $entry['first_name'];
		$lastname = $entry['last_name'];
		$addr = $entry['street'];
		$co = $entry['co'];
		$plz = $entry['zip'];
		$city = $entry['city'];
		$birthday = $entry['birthday'];
		
		$form->addElement('html0',new HtmlElement('<table>'));
		
		$form->addElement('html1',new HtmlElement('<tr><td colspan="5"><h2>ibosuisse Database</h2></td></tr>'));
		
		if(sizeof($sel) == 0){
			$form->addElement('html2',new HtmlElement('<tr><td colspan="5"><b style="color:red">no probable matches found in database</b></td></tr>'));
			return $form;
		} else if($sel[0][0] == 0){
			$perfect = TRUE;
			$form->addElement('html2',new HtmlElement('<tr><td colspan="5"><b style="color:lime">perfect match found: </b></td></tr>'));
			$sel = array($sel[0]);
		} else if(sizeof($sel) == 1){
			$form->addElement('html2',new HtmlElement('<tr><td colspan="5"><b style="color:blue">one probable match found: '.$sel[0][0].'d  </b></td></tr>'));
		} else {
			$form->addElement('html2',new HtmlElement(sizeof($sel).'<tr><td colspan="5">several possible matches found in database</td></tr>'));	
		}

		$form->addElement('html3',new HtmlElement("<tr style=\"background-color:#99FFFF\"><td><td></td></td><td>$firstname</td><td>$lastname</td><td>$addr</td><td>$plz</td><td>$city</td><td>$birthday</td></tr>"));
		$form->addElement('html4',new HtmlElement('<tr><th>dist</th><th>id</th><th>prenom</th><th>nom</th><th>addr</th><th>plz</th><th>ort</th><th>bday</th></tr>'));
		
		foreach($sel as $i=>$opt)
		{
				$form->addElement('htmlA'.$i,new HtmlElement('<tr><td>'.$opt[0].'</td><td>'.implode('</td><td>',$opt[1]).'</td><td>'));
				$form->addElement('radio'.$i,new Radio('',"radio",$i,$perfect));
				
				$form->addElement('htmlB'.$i,new HtmlElement('</td></tr>'));
				
			}
		
		$form->addElement('htmllast'.$i,new HtmlElement('</table>'));

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	public function telsearch_entries_form($vector)
	{
		$form = new SimpleForm(__METHOD__);
		$form->setVector($vector);
		
		$sel = $vector['find'];
		$entry = $vector['search'];
		
		$firstname = $entry['first_name'];
		$lastname = $entry['last_name'];
		$addr = $entry['street'];
		$co = $entry['co'];
		$plz = $entry['zip'];
		$city = $entry['city'];
		
		/* JUST FOR REFERENCE!
		if(sizeof($qmatch) == 0){
			$html .= '<tr><td colspan="5"><b style="color:red">In '.$search_sum.' entries from '.$num_queries.' searches on telsearch there were no likely matches</b></td></tr>';
			return $html;
		}
		
		$html .= '<tr><td colspan="5">found '.$search_sum.' results in '.$num_queries.' searches on telnet for query: '.$lastname.' in '.$plz.'</td></tr>';
		$html .= "<tr style=\"background-color:#99FFFF\"><td></td><td></td><td>$firstname</td><td>$lastname</td><td>$addr</td><td>$plz</td><td>$city</td></tr>";
		$html .= '<tr><th>dist</th><th>id</th><th>prenom</th><th>nom</th><th>addr</th><th>plz</th><th>ort</th></tr>';
		
		foreach($qmatch as $line){
			$quality = $line[0];
			$found = $line[1];
			$html .= "<tr><td>$quality</td><td></td><td><i>{$found['firstname']}</i></td><td>{$found['lastname']}</td><td>{$found['addr']}</td><td>{$found['plz']}</td><td>{$found['city']}</td>";
			$html .= '<td><input type="radio" name="radio" value="telsearch:'.implode('#',$found).'"/></td><td></td></tr>';
		}
		*/
		
		$form->addElement('html0',new HtmlElement('<table>'));
		
		$form->addElement('html1',new HtmlElement('<tr><td colspan="5"><h2>tel.search.ch</h2></td></tr>'));
		
		if(sizeof($sel) == 0){
			$form->addElement('html2',new HtmlElement('<tr><td colspan="5"><b style="color:red">In '.$this->search_sum.' entries from '.$this->num_queries.' searches on telsearch there were no likely matches</b></td></tr>'));
			return $form;
		} 
		else
		{
			$form->addElement('html2',new HtmlElement('<tr><td colspan="5">found '.$this->search_sum.' results in '.$this->num_queries.' searches on telnet for query: '.$lastname.' in '.$plz.'</td></tr>'));
		}
		
		$form->addElement('html3',new HtmlElement("<tr style=\"background-color:#99FFFF\"><td></td><td>$firstname</td><td>$lastname</td><td>$addr</td><td>$plz</td><td>$city</td></tr>"));
		$form->addElement('html4',new HtmlElement('<tr><th>dist</th><th>prenom</th><th>nom</th><th>addr</th><th>plz</th><th>ort</th></tr>'));
		
		foreach($sel as $i=>$opt)
		{
				$form->addElement('htmlA'.$i,new HtmlElement('<tr><td>'.$opt[0].'</td><td>'.implode('</td><td>',$opt[1]).'</td><td>'));
				$form->addElement('radio'.$i,new Radio('',"radio",$i));
				
				$form->addElement('htmlB'.$i,new HtmlElement('</td></tr>'));
				
			}
		
		$form->addElement('htmllast'.$i,new HtmlElement('</table>'));

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	public function manual_entry_form($vector)
	{
		$form = new SimpleForm(__METHOD__);
		$form->setVector($vector);
		
		$entry = $vector['entry'];
		
		$firstname = $entry['first_name'];
		$lastname = $entry['last_name'];
		$addr = $entry['street'];
		$co = $entry['co'];
		$plz = $entry['zip'];
		$city = $entry['city'];
		
		$form->addElement('html0',new HtmlElement('<table>'));
		
		$form->addElement('html1',new HtmlElement('<tr><td colspan="5"><h2>new manual Entry</h2></td></tr>'));
		
		$form->addElement('html2',new HtmlElement("<tr style=\"background-color:#99FFFF\"><td></td><td></td><td>$firstname</td><td>$lastname</td><td>$addr</td><td>$plz</td><td>$city</td></tr>"));
		$form->addElement('html3',new HtmlElement('<tr><th>dist</th><th>id</th><th>prenom</th><th>nom</th><th>co</th><th>addr</th><th>plz</th><th>ort</th></tr>'));
		
		$form->addElement('html4',new HtmlElement('<tr><td></td><td></td>'));
		
		$form->addElement('html6a',new HtmlElement('<td>'));
			$form->addElement('first_name',new TextInput('','firstname',$firstname,array('size'=>"11")));
		$form->addElement('html6b',new HtmlElement('</td>'));
		
		$form->addElement('html7a',new HtmlElement('<td>'));
			$form->addElement('last_name',new TextInput('','lastname',$lastname,array('size'=>"11")));
		$form->addElement('html7b',new HtmlElement('</td>'));
		
		$form->addElement('html71a',new HtmlElement('<td>'));
			$form->addElement('co',new TextInput('','co',$co,array('size'=>"11")));
		$form->addElement('html71b',new HtmlElement('</td>'));
		
		$form->addElement('html8a',new HtmlElement('<td>'));
			$form->addElement('street',new TextInput('','addr',$addr,array('size'=>"14")));
		$form->addElement('html8b',new HtmlElement('</td>'));
		
		$form->addElement('html9a',new HtmlElement('<td>'));
			$form->addElement('zip',new TextInput('','plz',$plz,array('size'=>"6")));
		$form->addElement('html9b',new HtmlElement('</td>'));
		
		$form->addElement('html10a',new HtmlElement('<td>'));
			$form->addElement('city',new TextInput('','city',$city,array('size'=>"12")));
		$form->addElement('html10b',new HtmlElement('</td>'));
		
		
		$form->addElement('htmllast',new HtmlElement('</tr></table>'));

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	public function process_db_entries_form()
	{
		
		$form = $this->getForm('db_entries_form');
		$this->form_submitted = TRUE;
		if(!$form->validate())
		{
			$this->process_msg = '<p>Errors in form:'. $form->getErrors().'</p>';
			return FALSE;	
		}
			
		$se_id = $form->getVectorValue('eid');

		$entry_id = $form->getPostValue('radio');
		
		$entry = $form->getVectorValue('find');
		
		$user_id = reset($entry[$entry_id][1]); //yeah, I know it's ugly!
		$distance = $entry[$entry_id][0];
		
		
		
		$sql = SqlQuery::getInstance();
		//get the entry out of IBO_scanned_exam
		$scanned = $sql->singleRowQuery("SELECT * FROM IBO_scanned_exam WHERE id='$se_id'");
		
		$old_user_id = $scanned['user_id'];
		
		$old_user = $sql->singleRowQuery("SELECT * FROM user WHERE id='$old_user_id'");
		
		
		$sql->start_transaction();
			//change user_id in IBO_student_exam
			$sql->updateQuery('IBO_student_exam',array('user_id'=>$user_id),array('user_id'=>$old_user_id));
			//change user_id in IBO_student
			$sql->deleteQuery('IBO_student',array('user_id'=>$user_id));
			$sql->updateQuery('IBO_student',array('user_id'=>$user_id),array('user_id'=>$old_user_id));
			
			//change user_id, verified, verified_by in IBO_scanned_exam
			$sql->updateQuery('IBO_scanned_exam',
				array('user_id'=>$user_id,
					'verified_by'=>$GLOBALS['user']->id,
					'verified'=>DB_ENTRY,
					'match_dist'=>$distance),
				array('id'=>$se_id)
			);
			
			
			//update user_in_group, remove group_memberships for obsolete user
			$sql->insertSelectQuery('user_in_group',
				'user_id,usergroup_id',
				"SELECT $user_id, usergroup_id FROM user_in_group 
				WHERE user_id='$old_user_id' 
				AND usergroup_id NOT IN 
				(
					SELECT usergroup_id FROM user_in_group 
					WHERE user_id='$user_id'
					)"
			);
			
			$sql->deleteQuery('user_in_group',array('user_id'=>$old_user_id));
			//update password,primary_usergroup_id for user
			$sql->updateQuery('user',
				array('password'=>$old_user['password'],
					'primary_usergroup_id'=>$old_user['primary_usergroup_id']),
				array('id'=>$user_id));
			//delete old user
			$sql->deleteQuery('user',array('id'=>$old_user_id));
		
		if($sql->end_transaction())
			$this->process_msg = '<p class="success">scanned exam assigned to existing user in DB</p>';
		else
			$this->process_msg = '<p class="error">sql error</p>';
		
	}
	
	public function process_telsearch_entries_form()
	{
		$form = $this->getForm('telsearch_entries_form');	
		
		$this->form_submitted = TRUE;
		if(!$form->validate())
		{
			$this->process_msg = '<p>Errors in form:'. $form->getErrors().'</p>';
			return FALSE;	
		}
			
		$se_id = $form->getVectorValue('eid');

		$entry_id = $form->getPostValue('radio');
		
		$entry = $form->getVectorValue('find');
		
		$user_row = $entry[$entry_id][1]; //yeah, I know it's ugly!
		$distance = $entry[$entry_id][0];
		
		print_r($user_row);
		
		//all you need to do now:
		//update user and set last_name, address, etc.
		$sql = SqlQuery::getInstance();
		
		$scan = $sql->singleRowQuery("SELECT * FROM IBO_scanned_exam WHERE id='$se_id'");
		
		//update scanned exam
		$sql->start_transaction();
		
			$sql->updateQuery('IBO_scanned_exam',
				array('verified_by'=>$GLOBALS['user']->id,
					'verified'=>DB_ENTRY,
					'match_dist'=>$distance),
				array('id'=>$se_id)
			);
		
		//update user
		$sql->updateQuery('user',
			array('last_name'=>$user_row['lastname'],
				'street'=>$user_row['addr'],
				'zip'=>$user_row['plz'],
				'city'=>$user_row['city']
			),
			array('id'=>$scan['user_id'])
		);
		
		if($sql->end_transaction())
			$this->process_msg = '<p class="success">created new user according to telsearch entry</p>';
		else
			$this->process_msg = '<p class="error">sql error</p>';
		
	}
	
	public function process_manual_entry_form()
	{
		$form = $this->getForm('manual_entry_form');
		
		$this->form_submitted = TRUE;
		if(!$form->validate())
		{
			$this->process_msg = '<p>Errors in form:'. $form->getErrors().'</p>';
			return FALSE;	
		}
			
		$se_id = $form->getVectorValue('eid');
		$values = $form->getElementValues();
		
		
		//all you need to do now:
		//update user and set last_name, address, etc.
		$sql = SqlQuery::getInstance();
		
		$scan = $sql->singleRowQuery("SELECT * FROM IBO_scanned_exam WHERE id='$se_id'");
		
		//update scanned exam
		$sql->start_transaction();
		
			$sql->updateQuery('IBO_scanned_exam',
				array('verified_by'=>$GLOBALS['user']->id,
					'verified'=>MAN_ENTRY,
					'match_dist'=>-1),
				array('id'=>$se_id)
			);
		
		//update user
		$sql->updateQuery('user',
			array('first_name'=>$values['first_name'],
				'last_name'=>$values['last_name'],
				'co'=>$values['co'],
				'street'=>$values['street'],
				'zip'=>$values['zip'],
				'city'=>$values['city']
			),
			array('id'=>$scan['user_id'])
		);
		
		if($sql->end_transaction())
			$this->process_msg = '<p class="success">created new user according to telsearch entry</p>';
		else
			$this->process_msg = '<p class="error">sql error</p>';
		
	}
	
	// !!!! RETURNS THE string for the select option in the form. 
	function db_search(&$perfect,$id,$firstname,$lastname,$addr,$plz,$city,$birthday)
	{
		$sql = SqlQuery::getInstance();
		
		$kweri = "SELECT 
					id,
					first_name,
					last_name,
					street,
					zip,
					city,
					birthday 
				FROM 
					`user` 
				WHERE 
					(
						first_name LIKE '".substr(mysql_escape_string($firstname),0,1)."%' 
						OR last_name LIKE '".substr(mysql_escape_string($lastname),0,1)."%' 
						OR zip LIKE '$plz'
					) 
					AND
						id NOT IN (SELECT user_id FROM IBO_scanned_exam WHERE verified <> 1)
					";
		$risolt = $sql->simpleQuery($kweri);
		

		
		$sel = $this->compare_sort_prune_levenshtein(array(0,$firstname,$lastname,$addr,$plz,$city,$birthday),$risolt,array(0,10,10,1,1,1,0),$this->tolerance_db);

		if(!empty($sel) && $sel[0][0] == 0)
			$perfect = TRUE;
			
		return $sel;
		
	}
	
	//table of options for telsearch results
	function telsearch_search($id,$firstname,$lastname,$addr,$plz,$city)
	{
		$html = '<tr><td colspan="5"><h2>new entry telsearch</h2></td></tr>';
		//echo '<br />TTTTTTTTTTTTTTTTTEEEEEEEEEEEEEEEEEEEEEEEELLLLLLLLLLLLLLLLLLLSSSSSSSSSSSSSSSSSSEEEEEEEEEEEEEEEARCH';

		$sql = SqlQuery::getInstance();
		
		//get 5 most likely matches for PLZ + Ort from our DB.
		$query = 'SELECT plz,ort from IBO_postleitzahlen WHERE plz LIKE \'_'.substr($plz,1,2).'_\' OR plz LIKE \''.substr($plz,1,1).'_'.substr($plz,3,1).'\' OR plz LIKE \'__'.substr($plz,2,2).'\' OR ort LIKE \''.substr($city,0,1).'%\'';
		$response = $sql->simpleQuery($query);
		//echo $query;
		//print_r(mysql2array($response));
		$probable = $this->compare_sort_prune_levenshtein(array($plz,$city),$response,array(2,1),$this->tolerance_plz);
			
		$best5 = array();
		foreach($probable as $p)
			$best5 []= $p[1]['plz'];
			
		$best5 = array_unique($best5);
			
		if(sizeof($best5) > $this->max_plz)
			$best5 = array_slice($best5,0,$this->max_plz);
			
		if(empty($best5))
			$html .= '<p class="error">No PLZ found!</p>';
	
			
		//print_r($best5);
		
		//$resource = incremental_telsearch($PLZs,array($lastname,$addr,$plz));
		$index = 0;
		$qmatch = array(); //array of all possible matches
		$search_sum = 0;
		$num_queries = 0;
		$found = FALSE;
		$decent = FALSE;
		while(sizeof($qmatch) < 10 && $index < sizeof($best5) && !$decent){
			$curr_plz = $best5[$index];	
			echo '<br/>curr Plz: '.$curr_plz.'<br/>';
			
			//inner loop:
			
			$house_nr = end(explode(' ',$addr));
			//incremental search rules:
			$attempts[0] = array($lastname,$curr_plz);
			$attempts[1] = array('',$house_nr.','.$curr_plz);
			$attempts[2] = array(substr($lastname,0,1),substr($addr,0,2).','.$curr_plz);
			$attempts[3] = array('',substr($addr,0,2).','.$curr_plz);
			//$attempts[4]= array(,);
			
			$k = 0;
			$decent = FALSE;
			while(sizeof($qmatch) < 10 && $k < sizeof($attempts) && !$found){
				$attempt = $attempts[$k];
				echo '<br/>searching for:'.$attempt[0].' and '.$attempt[1];
				$telsearch = $this->telsearch_array_lookup($attempt[0],$attempt[1]);
				$search_sum += sizeof($telsearch);
				
				//strasse to str
				$addr = str_replace(array('.','strasse'),array('','str'),$addr);
				
				//!!!!!!!!!!!print_r($telsearch);
				
				$pruned_search = $this->compare_sort_prune_levenshtein(array($firstname,$lastname,$addr,$plz,$city),$telsearch,array(0,3,1,0,0),$this->tolerance_tel);
				//echo '<br /><br />pruned_search</br>';
				//print_r($pruned_search);	
				//echo '<br />qmatch<br />';
				//print_r($qmatch);
				$qmatch = array_merge($qmatch,$pruned_search);
				//echo '<br />qmatch2<br />';
				//print_r($qmatch);
				$qmatch = $this->eliminateDoubles($qmatch);
				//echo '<br />qmatch3<br />';
				//print_r($qmatch);
				
				$perfect = array_filter($qmatch,array("VerifyAddressContent", "retain_matches"));
				if(!empty($perfect))
					$found = TRUE;
					
				$decent = array_filter($qmatch,array("VerifyAddressContent", "retain_decent"));
				if(!empty($decent))
					$decent = TRUE;
				
				$num_queries++;
				$k++;
			}

			$index++;	
		}
		
		//print 'queries: '.$num_queries.'<br />';
		//print 'results: '.$search_sum.'<br />';
		usort($qmatch,array("VerifyAddressContent", "cmp"));
		//print_r($qmatch);
		
		$this->search_sum = $search_sum;
		$this->num_queries = $num_queries;
		
		return $qmatch;		
		//INCREMENTAL SEARCH: Name; PLZ, => nada; house nr. , PLZ => initials1;addrInitials2,PLZ => nada;addrI2,PLZ
		//retain all matches until 10 are reached. Keep all.

	}
	
	//prints line of table for manual entry + a checkbox
	function manual_entry($id,$firstname,$lastname,$addr,$plz,$city)
	{
		$html = '';
		
		$html .= '<tr><td colspan="5"><h2>new manual Entry</h2></td></tr>';
		
		$html .= "<tr style=\"background-color:#99FFFF\"><td></td><td></td><td>$firstname</td><td>$lastname</td><td>$addr</td><td>$plz</td><td>$city</td></tr>";
		$html .= '<tr><th>dist</th><th>id</th><th>prenom</th><th>nom</th><th>addr</th><th>plz</th><th>ort</th></tr>';
		
		$html .= '<tr><td></td>';
		$html .= '<td><input type="text" size="4" name="student_id" value="new" /></td>';
		$html .= '<td><input type="text" size="11" name="firstname" value="'.$firstname.'" /></td>';
		$html .= '<td><input type="text" size="11" name="lastname" value="'.$lastname.'" /></td>';
		$html .= '<td><input type="text" size="14" name="addr" value="'.$addr.'" /></td>';
		$html .= '<td><input type="text" size="6" name="plz" value="'.$plz.'" /></td>';
		$html .= '<td><input type="text" size="12" name="city" value="'.$city.'" /></td>';
		$html .= '<td><input type="radio" name="radio" value="manual"/></td>';
		$html .= '<br />';
		
		return $html;
	}





	function retain_matches($pruned)
	{
		return ($pruned[0] < $this->match_threshold);
	}
	
	function retain_decent($pruned)
	{
		return ($pruned[0] < $this->decent_threshold);
	}
	
	static function cmp($a, $b)
	{
		if ($a[0] == $b[0])
			return 0;
		
		return ($a[0] < $b[0]) ? -1 : 1;
	}
	
	
	/*PROCEDURE: ( this has changed since!!!!!!!)
	
	1. User gets presented with number of scanned exams and choses a range [new exams must be added at end! ]
	
	2. User gets presented 10 exams at a time from beginning of range till the end and can choose which entry to add,
	possibilies: confirm script, select from script options or enter manually [user can choose to look at pdf file]
	!!for adress changes, must be able to override old address! (or just create a new user??)
	
	> user is shown original entry next to proposed entries. The differences are highlighted (includes address). 
	MUST detect if Address was changed!
	
	3. user submits form. for each entry, the script checks that it hasn't been checked already, then drops it and creates:
	student if necessary, student exam, etc.
	user sees if everything was ok.
	
	4. back to 2 or if at end of range back to 1.
	*/
	
	//order of sieve and grains is important! must be the same
	function compare_sort_prune_levenshtein($sieve,$sand,$weights,$tolerance)
	{
		
		if(sizeof($sand) == 0)
			return array();
		if(sizeof($sieve) != sizeof($weights) || sizeof($sieve) != sizeof($sand[0])){
			echo '<p class="error">levenshtein compare: arrays differ in size!</p>';
			print_r($sieve);
			print_r($weights);
			print_r($sand[0]);
			return array();
		}
		

		//normalize weights to $this->normal:
		$tot = 0;
		foreach($weights as $w)
			$tot += $w;

		$weights = array_map(create_function('$a','return $a/'.$tot.'*'.$this->normal.';'),$weights);

		
		//STUPID part:
			$sieve = array_combine(array_keys($sand[0]),$sieve);
			$weights = array_combine(array_keys($sand[0]),$weights);
		//end of stupid part.
		
		$ret = array();
		//echo 'sieve: ';
		//print_r($sieve);
		foreach($sand as $grain){
			//echo('grain');
			//print_r($grain);
			$prod = 1;
			$dist = array(); //array with distances
			//print_r($sieve);

			foreach($grain as $k=>$v){
				//print 'key: '.$k.' <br />';
				$unit = $v;
				$elem = $sieve[$k];
				$w = $weights[$k];
			
				$d = round(levenshtein($unit,$elem)/strlen($elem)*$this->normal,2);
				
				//echo '<br />dist '.$unit.'-'.$elem.' = '.$d;
			
				$dist []= $d;
				$prod *= $w*$d + 1;
			}
			//echo '<br /> p = '.$prod. ' tol = '.$tolerance;
			if($prod < $tolerance)
			{
				
				$ret []= array(round($prod,2) -1,$grain,$dist);
				
			}
			//else
				//echo '<br/>iik: '.$prod.' > '.$tolerance;
			
			//print_r(array($prod-1,$grain,$dist));
		
		}
		
		usort($ret,array("VerifyAddressContent", "cmp"));
		//print_r($ret);
		return $ret;
		
	}
		

	function localch_array_lookup($name,$addr)
	{
		$local_url = '';
		$local_response = file_get_contents($local_url) or $this->process_msg .=  '<tr><td style="color:red" colspan="5"><b>Could not connect to telsearch.ch</b></td></tr>';


		$regexp = "/\<table class=\"record\"\>(.*?)\<\/table\>/s";
		$matches = array();
		$num_results = preg_match_all($regexp,$telsearch_response,$matches, PREG_PATTERN_ORDER );
		
		echo 'aha?';
		if($_SESSION['user_id']==2){
			echo 'aaaaaaaaaaaarhgh';
			print_r($matches);
			
		}
		
		$summary = array();
		foreach($matches[1] as $result){
			$regexp2 = "/\<div class=\"rname\"\>(.*?)\<\/div\>/s";
			$matches2 = array();
			preg_match_all($regexp2,$result,$matches2, PREG_PATTERN_ORDER );
			
			$regexp3 = "/\<div class=\"raddr\"\>(.*?)\<\/div\>/s";
			$matches3 = array();
			preg_match_all($regexp3,$result,$matches3, PREG_PATTERN_ORDER );
			
			$name = explode(',',strip_tags($matches2[1][0]));
			if(isset($name[1]))
				$found['firstname'] = trim($name[1]);
			else
				$found['firstname'] = '';
				
			$found['lastname'] = trim($name[0]);
			
			$road = explode(',',strip_tags($matches3[1][0]));
			$found['addr'] = trim($road[0]);
			
			if(isset($road[1]))
				$rest = explode(' ',trim($road[1]));
			else
				$rest = array('','');
				
			$found['plz'] = $rest[0];
			if(isset($rest[1]))
				$found['city'] = reset(explode('/',$rest[1]));
			else
				$found['city'] = '';
			
			$ret = array();
			foreach($found as $k=>$f)
				$ret[$k] = $f;
				
			$found = $ret;
			//echo '<br/>';
			//print_r($found);
			//echo '<br/>';
			
			//strasse to str
			$found['addr'] = str_replace(array('.','strasse'),array('','str'),$found['addr']);
			
			$summary []= $found;
		}
		return $summary;
	}


	function telsearch_array_lookup($name,$addr)
	{
		
		//return array();
	
	//TODO: use cURL instead!
			$telsearch_url = 'http://tel.search.ch/?was='.urlencode($name).'&wo='.urlencode($addr).'&maxnum=200';
			echo '<br /><br />'.$telsearch_url.'<br />';
			$telsearch_response = file_get_contents($telsearch_url) or $this->process_msg .=  '<tr><td style="color:red" colspan="5"><b>Could not connect to telsearch.ch</b></td></tr>';
		
		//echo '*>'.$telsearch_response.'<*'.strlen($telsearch_response);
		
			//make sure char encoding is UTF-8
			$telsearch_response = mb_convert_encoding($telsearch_response, 'UTF-8',
          mb_detect_encoding($telsearch_response, 'UTF-8, ISO-8859-1', true));
		
		echo 'response len= '.strlen($telsearch_response);

		if(!$telsearch_response || strlen($telsearch_response) == 0)
		{
			$this->process_msg = '<tr><td style="color:red" colspan="5"><b>No telsearch response!</b></td></tr>';
			return array();
		}
		
		if(isset($_GET['test'])){
			echo 'aaaaaaaaa';
			echo $telsearch_response;
			
		}
	
		$regexp = "/\<table .*class=\"record\"\>(.*?)\<\/table\>/s";
		$matches = array();
		$num_results = preg_match_all($regexp,$telsearch_response,$matches, PREG_PATTERN_ORDER );
		
		echo 'aha?';
		/*if($_SESSION['user_id']==2){
			echo 'aaaaaaaaaaaarhgh';
			print_r($matches);
			
		}*/
		//print_r($matches[1]);
		
		$summary = array();
		foreach($matches[1] as $result){
			$regexp2 = "/\<a .* class=\"fn\"\>(.*?)\<\/a\>/s";
			$matches2 = array();
			preg_match_all($regexp2,$result,$matches2, PREG_PATTERN_ORDER );
			
			$regexp3 = "/\<span class=\"adrgroup street-address\"\>(.*?)\<\/span\>/s";

			$matches3 = array();
			preg_match_all($regexp3,$result,$matches3, PREG_PATTERN_ORDER );

			//print_r($matches2);
			//print_r($matches3);

			$regexp4 = "/\<span class=\"locality\"\>(.*?)\<\/span\>/s";

			$matches4 = array();
			preg_match_all($regexp4,$result,$matches4, PREG_PATTERN_ORDER );

			//print_r($matches4);

			$regexp5 = "/\<span class=\"postal-code\"\>(.*?)\<\/span\>/s";

			$matches5 = array();
			preg_match_all($regexp5,$result,$matches5, PREG_PATTERN_ORDER );

			//print_r($matches5);

			
			$name = explode(',',strip_tags($matches2[1][0]));
			if(isset($name[1]))
				$found['firstname'] = trim($name[1]);
			else
				$found['firstname'] = '';
				
			$found['lastname'] = trim($name[0]);
			
			$road = explode(',',strip_tags($matches3[1][0]));
			$found['addr'] = trim($road[0]);
			
			if(isset($road[1]))
				$rest = explode(' ',trim($road[1]));
			else
				$rest = array('','');
				
			$found['plz'] = $matches5[1][0];
			$found['city'] = $matches4[1][0];

			//print_r($found);
			
			$ret = array();
			foreach($found as $k=>$f)
				$ret[$k] = $f;
				
			$found = $ret;
			//echo '<br/>';
			//print_r($found);
			//echo '<br/>';
			
			//strasse to str
			$found['addr'] = str_replace(array('.','strasse'),array('','str'),$found['addr']);
			
			$summary []= $found;
		}
		return $summary;
	}
	
	//elimiates the doubles from an array returned by compare_sort_prune_levenshtein
	public function eliminateDoubles($ary)
	{
		$hashes = array();
		$ret = array();
		//echo '<br />';
		foreach($ary as $k=>$el)
		{
			$md5 = md5(serialize($el));
			//echo $md5.'<br />';
			if(array_key_exists($md5,$hashes) && serialize($ary[$hashes[$md5]]) == serialize($el)) //not quite perfect (hash collisions) but for our purposes sufficient
			{
				//it's the same, so skip it
				echo 'double in ary<br />';			
			}
			else
			{
				$hashes[$md5] = $k;
				$ret []= $el;	
			}
		}
		//print_r($hashes);
		return $ret;
	}
	

/*
			$d_firstname = levenshtein($firstname,$pers['vorname']);
			$d_lastname = levenshtein($lastname,$pers['name']);
			$d_plz = levenshtein($plz,$pers['plz']);
			$d_addr = levenshtein($addr,$pers['strasse']);
			
			$d_total = ($d_firstname +1)* ($d_lastname+1) *($d_plz+1)*($d_addr+1) -1;
			
			//only take matches with distance smaller than 200.
			if($d_total < match_tolerance)
				$sel []= array($d_total,array($pers['id'],$pers['vorname'],$pers['name'],$pers['strasse'],$pers['plz'],$pers['ort']),array($d_firstname,$d_lastname,$d_addr,$d_plz,0));
		}
		//print_r($sel);
		usort($sel,array("VerifyAddressContent", "cmp"));
*/

/**

 * The letter l (lowercase L) and the number 1

 * have been removed, as they can be mistaken

 * for each other.

 */



	
}
?>
