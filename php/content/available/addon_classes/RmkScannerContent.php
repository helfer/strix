<?php
/**
 * scan remark office files of exams and insert into database.
 **/


define('EXAM_PATH','/files/exams/');
define('FIRST_ROUND','round1');
define('SECOND_ROUND','round2');
	
class RmkScannerContent extends Content
{
	
	///@conf change if first or second round exam
	protected $exam_type = FIRST_ROUND;
	
	//@todo get id out of DB
	protected $new_usergroup_id = 14;
	
	//@todo get number of questions out of DB
	protected	$num_questions = 68;
	//@todo get number of fields out of DB
	protected	$num_info_fields = 11;
	
	///@todo configure this in "admin"
	protected $key_order_first = array(
			'language',
			'first_name',
			'last_name',
			'co',
			'street',
			'zip',
			'city',
			'birthday',
			'school',
			'class',
			'teacher_id',
			'answer',
			'file'
			);
			
			
	protected $key_order_second = array(
			'language',
			'first_name',
			'last_name',
			'user_id',
			'answer',
			'file'
			);
			
			//that's the info from the rmk file needed to create the user
	protected $key_info = array(
			'language', //(de = 1, fr = 2, it = 3)
			'first_name',
			'last_name',
			'co',
			'street',
			'zip',
			'city',
			'birthday', //format: dd.mm.yyyy
			);
	

	
	
	private $file_extensions = array('rmk');
	protected $process_msg = '';

	function display()
	{

				$html = $this->process_msg;
				
				$html .= $form = $this->getForm('upload_file_form')->getHtml($this->id);	
				
				return $html;
	}
	
	
	public function upload_file_form($vector)
	{
		$form = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$form->setVector($vector);
		$form->setProportions('10em','28em');
		
		$q_args = array('query'  => "SELECT id, de FROM `IBO_exam` WHERE closed = 0 ORDER BY date DESC",
						'keys'  => array('id'),
						'values'=> array('de')
						);
		$arg_ary = array('label'=>'exam:','name'=>'exam_id','query_args'=>$q_args);
		$form->addElement('exam_id',new DataSelect($arg_ary));
		
		$file = new FileInput('rmk-file:','rmkfile');
		$form->addElement('rmkfile',$file);

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	function process_upload_file_form()
	{
			
			notice("processing uploaded file");
			
			$form = $this->getForm('upload_file_form');
			
			if ( !$form->validate() )
			{
				$this->process_msg = '<p class="error">Form contains errors</p>';
				return FALSE;
			}
			
			$vals = $form->getElementValues();
		
			if(!isset($_FILES['rmkfile']))
			{
				$this->process_msg = '<p class="error">No file uploaded!</p>';
				return;
			}
			
			$filename = basename($_FILES['rmkfile']['name']);
				
				
			//SECURITY CHECK:
			$ext = end(explode('.',$filename));
			if(!in_array($ext,$this->file_extensions))
			{
				$this->process_msg = '<p class="error">The file extension "'.$ext.'" is not allowed!</p>';
				return;
			}
			
			
			//parse file:
			$file_content_str = file_get_contents($_FILES['rmkfile']['tmp_name']);
			
			//convert to UTF-8 !!
			$file_content_str = mb_convert_encoding($file_content_str, 'UTF-8', mb_detect_encoding($file_content_str, 'UTF-8, ISO-8859-1', true));
			
			$ok = $this->parse_rmk_string($file_content_str,$vals['exam_id'],$filename);
			
			if ($ok)
				$this->process_msg = '<p class="success">File successfully scanned.</p>';
			else
				$this->process_msg .= '<p class="error">An error occurred. Solve the problem!</p>';

			return TRUE;
	}
	
	protected function parse_rmk_string($rmk_str,$exam_id,$filename)
	{
		
		notice("parsing rmk string");
		
		//get filename and number of fields out of the first line
		$firstline = strtok($rmk_str,"\n");
		list ( , ,$num_fields) = explode("\t",$firstline);
		
		
		
		echo basename($filename) . '<br />' . $num_fields . '<br />';
		
		
		//assert: questions + info-fields == num_fields
		if($num_fields != $this->num_questions + $this->num_info_fields)
		{
			$this->process_msg = '<p class="error">unexpected number of question + info fields. Did you select the right exam? ( '.$num_fields.' != '.$this->num_questions.' + '.$this->num_info_fields.'</p>';
			return FALSE;
		}
		
		
		$exam_batch = array();
		
		//parse string line by line
		while($line = strtok("\n"))
		{
			//echo 'new line<br />';	
			if(strlen($line) > 2*$num_fields + 1)
			{
				//echo $line;
				$line = substr($line,0,strpos($line,"")); //cut off line at this char, which follows immediately after the filename.
				
				$tokens = explode("\t",$line);
				
				
				//print_r($tokens);
				
				$info_fields = array_slice($tokens,0,$this->num_info_fields);
				$answer_fields = array_slice($tokens,$this->num_info_fields,$this->num_questions);
				$exam_file = trim( strrchr($tokens[$num_fields],"\\"), "\\" ); //get the !!!WINDOWS!!! filename of the student answersheet image file
				
				//put all into one data structure
				$exam_ary = $info_fields;
				$exam_ary []= $answer_fields;
				$exam_ary []= $exam_file;
				
				//$exam_ary = array_combine($this->key_order_first,$exam_ary);
				
				
				echo $exam_file .'<br />';
				$exam_batch [] = $exam_ary;
				
			}
			else //there are these strange interspersed lines with no relevant information. Identify them by length.
			{
				//VOID
				//echo 'line discarded, length = '.strlen($line).'<br />';
			}
		}
		
		
		if($this->exam_type == FIRST_ROUND){
			echo 'scan first round';
			return $this->insert_DB_firstround($exam_batch,$exam_id);
		} else if ($this->exam_type == SECOND_ROUND) {
			echo 'scan second round';
			return $this->insert_DB_secondround($exam_batch,$exam_id);
		}	
	
	}
	
	
	/**
	 * Inserts the student exam data into the 4 different tables: user, student, student_exam and scanned_exam (for verification)
	 */
	

	/**
	 * Take a batch of scanned exams and insert them into the db.
	 */
	protected function insert_DB_firstround($exam_batch,$exam_id)
	{
		$sql = SqlQuery::getInstance();
		
		
		$this->new_usergroup_id = $sql->singleValueQuery("SELECT participants_usergroup_id FROM IBO_exam WHERE `id`='$exam_id'");
		
		//TODO: start a transaction and commit only if every query succeeds, i.e. if every exam was sucessfully inserted.
		$ins_values = array();
		
		$user_id_ary = array(); //to have the reference insert_id later...
		$se_id_ary = array(); //to have reference for rollback
		
		$tid_ary = array(); //to help find wrong teacher ids...
		
		$student_ary = array(); //array for students (SQL insert)
		$student_exam_line = array(); //array for student_exam (inserted one by one!)
		$scanned_exam_ary = array(); //array for scanned exams
		
		
		$user_insert_success = TRUE;
		
		// one big loop to accumulate all data...
		foreach($exam_batch as $exam)
		{
			//print_r($exam);
			//print_r($this->key_order_first);
			$exam = array_combine($this->key_order_first,$exam);
			//print_r($exam);
			
			
			//language...
			//TODOTODOTODOTODOT get this info from DB!
			if(!is_numeric($exam['language']))
				$exam['language'] = str_replace(array('de','fr','it'),array(1,2,3),$exam['language']);
			
			//1. user creation -----------------------
			$user = array_intersect_key($exam,array_flip($this->key_info));
			
			$len = 7;
			$user['id'] = NULL; // must be set to NULL to create new user.
			
			//the username will be identical for a person with same first + last name, birthday if inserted in the same year.
			$user['username'] = 
			mb_substr($user['first_name'],0,2) . 
			mb_substr($user['last_name'],0,2) . 
			substr(abs(crc32($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['birthday'] . date('Y'))),0,4);
			
			
			$user['co'] = '';
			
			$new_pw = generateRandomPassword($len);
			$user['password'] = md5($new_pw);
			$user['primary_usergroup_id'] = $this->new_usergroup_id;
			$user['language_id'] = $user['language'];
			//unset($user['language']);
			$user['creation_date'] = date('Y-m-d');
			
			//change birthday to right format:
			//$date = date_parse_from_format('d.m.Y',$user['birthday']);
			//print_r($date);
			
			if(preg_match('#^(\d{2}).(\d{2}).(\d{4})$#', $exam['birthday'], $matches) && checkdate($matches[2], $matches[1], $matches[3])){
				//print_r($matches);
				//echo 'date valid';
				$user['birthday'] = $matches[3].'-'.$matches[2].'-'.$matches[1];
			} else {
				//user could not be created...
				$user_insert_success = FALSE;
				$this->process_msg = '<p class="error">Error parsing date '.$exam['birthday'].'</p>';
				break;
			}
			
			
			$user_obj = new User($user);
			//print_r($user_obj);
			
			if($user_obj->store())
			{
				$user_obj->addToUsergroup(EVERYBODY_USERGROUP_ID);
				$user_obj->addToUsergroup($this->new_usergroup_id);
			}
			else
			{
				//user could not be created...
				$user_insert_success = FALSE;
				$this->process_msg = '<p class="error">Error on user creation.</p>';
				break;
				
			}
			$uid = $user_obj->__get('id');
			$user_id_ary []= $uid;

			//3. create student_exams array(use the fact that insert_ids are assigned in consecutive manner
			
			
			$student_exam_line = array(
				'user_id'=>$uid,
				'filename'=>$exam['file'],
				'answer'=>implode('#',$exam['answer']),
				'exam_id'=>$exam_id, //this is passed to the function
				'language_id'=>$exam['language']
				);
				
			//perform insert here and store student_exam_ids for future reference
			$se_id = $sql->insertQuery('IBO_student_exam',$student_exam_line);
			if(!$se_id)
			{
				//exam could not be inserted
				$user_insert_success = FALSE;
				$this->process_msg = '<p class="error">Error on student_exam creation.</p>';
				break;	
			}
			
			$se_id_ary []= $se_id;
			
			//4. create array for IBO_scanned_exams
			$info = $exam;
			unset($info['answer']);
			unset($info['file']);
			//TODO!!!
			
			$scanned_exam_ary []= array(
			'user_id'			=>	$uid,
			'student_exam_id'	=>	$se_id,
			'new_pw'			=>	$new_pw,
			'exam_file'	=>	$exam['file'],
			'info_fields'		=>	serialize($info),
			'answer_fields'		=>	implode('#',$exam['answer']),
			'verified'			=>	0
			//id, verified_by and changed are NULL (take default values)
			);
			
			
			
			//2. fill array with students (just for teacher reference)
			$student_ary []= array('user_id'=>$uid,'class'=>$exam['class'],'teacher_id'=>$exam['teacher_id']);
			$tid_ary [$exam['teacher_id']]= $exam['teacher_id'];
			
		}
		foreach($tid_ary as $tid)
		{
			$exists = $sql->singleValueQuery("SELECT id FROM `LEG_lehrer` WHERE id='$tid'");
			if(!is_numeric($exists) || !$exists)
				echo '<p class="error">No teacher with id='.$tid.' in DB</p>';
		}
		
		//only do this if no error has occurred yet
		if($user_insert_success)
		{
			//insert IBO_scanned_exams and IBO_students
			$sql->start_transaction();
			$ok = $sql->insertQuery('IBO_scanned_exam',$scanned_exam_ary);
			$ok2 = $sql->insertQuery('IBO_student',$student_ary);
			if(!$sql->end_transaction())
				$user_insert_success = FALSE;
		}
		
		
		//small glitch, roll back user creation and return...
		if(!$user_insert_success)
		{
			$this->rollback_user_creation($user_id_ary,$se_id_ary);
			return FALSE;	
		}
		
		//run insert queries for student, student_exam and scanned_exams
		return TRUE;
	}
	
	
	protected function insert_DB_secondround($exam_batch,$exam_id)
	{
		
		//print_r($exam_batch);
		
		foreach($exam_batch as $exam)
		{
			$exam = array_combine($this->key_order_second,$exam);
			
			if( SqlQuery::getInstance()->singleValueQuery("SELECT 1 FROM user WHERE id ='".$exam['user_id']."'") != 1)
			{
				echo "ERROR (second round exam): id of user".$exam['user_id']." is not valid!";
				print_r($exam);
				return FALSE;
			}
		}
		
		// one big loop to accumulate all data...
		foreach($exam_batch as $exam)
		{
			
			$error = FALSE;
			
			$exam = array_combine($this->key_order,$exam);
			//print_r($exam);		
				
			//language...
			//TODOTODOTODOTODOT get this info from DB!
			if(!is_numeric($exam['language']))
				$exam['language'] = str_replace(array('de','fr','it'),array(1,2,3),$exam['language']);
			
			
			//insert into student_exams --------------------------
			$student_exam_line = array(
				'user_id'=>$exam['user_id'],
				'filename'=>$exam['file'],
				'answer'=>implode('#',$exam['answer']),
				'exam_id'=>$exam_id, //this is passed to the function
				'language_id'=>$exam['language']
				);
			
			$se_id = SqlQuery::getInstance()->insertQuery('IBO_student_exam',$student_exam_line);
			if(!$se_id)
			{
				//exam could not be inserted
				$this->process_msg = '<p class="error">Error on student_exam creation.</p>';
				$error = TRUE;
				break;	
			}
			
			$info = $exam;
			unset($info['answer']);
			unset($info['file']);
			
			
			//set file permissions -----------------------------
			
			$file_ary = array(
				'name'=>$exam['file'],
				'path'=>EXAM_PATH,
				'owner_id'=>$exam['user_id'],
				'owner_permission'=>1, //read only
				'mimetype_id'=>1 // application/pdf
				);
			
			$file_id = SqlQuery::getInstance()->insertQuery('file',$file_ary);
			if(!$file_id)
			{
				//exam could not be inserted
				$this->process_msg = '<p class="error">File reference not created... error ignored</p>';	
			}
			
			//insert into scanned exams ------------------------
			$scanned_exam_ary = array(
			'user_id'			=>	$exam['user_id'],
			'student_exam_id'	=>	$se_id,
			'new_pw'			=>	'',
			'exam_file'	=>	$exam['file'],
			'info_fields'		=>	serialize($info),
			'answer_fields'		=>	implode('#',$exam['answer']),
			'verified'			=>	100
			);
			
			$ok = SqlQuery::getInstance()->insertQuery('IBO_scanned_exam',$scanned_exam_ary);
			
			
		}
		
		return !$error;
	}
	
	
	
	
	/**
	 * Undo user creation and student_exam insert into DB
	 */
	protected function rollback_user_creation($user_id_ary,$se_id_ary)
	{
		$sql = SqlQuery::getInstance();
		
		foreach($se_id_ary as $se_id)
			$sql->deleteQuery('IBO_student_exam',array('id'=>$se_id));
		
		foreach($user_id_ary as $id)
			{
				$sql->deleteQuery('user',array('id'=>$id));
			}
	}
	

}
?>
