<?php

include_once('ibo_classes/class_question.php');

class AutoCorrectExam extends Content
{


	var $ersterunde_group_id = 14;
	
	protected $selected_exam_id = NULL;
	
	//protected $max_bday = '1991-07-01'; //student's bday must be on this day or earlier to participate in IBO
	
	
	public function choose_action_form($vector)
	{
				//-------------------------------------------------------------
		//if an exam is read from DB, show buttons
		
		$form = new TabularForm(__METHOD__);
		$form->setVector($vector);
		$form->setProportions('10em','28em');
		
		$exam = $vector;
					
			
		switch($_SESSION['language_abb']){
			case 'de': $html='Resultate berechnen'; break;
			case 'fr': $html='?only german text available?'; break;
			case 'it': $html='?only german text available?'; break;
			case 'en': $html='calculate results'; break;
		}								
		$form->addElement('result',new Submit('result',$html));
		

		switch($_SESSION['language_abb']){
			case 'de': $html='Rangliste erstellen'; break;
			case 'fr': $html='?only german text available?'; break;
			case 'it': $html='?only german text available?'; break;
			case 'en': $html='make ranking'; break;
		}				
		$form->addElement('ranking',new Submit('ranking',$html));
						
		$tde = new TextInput('threshold de','threshold_de',$exam['threshold_de']);
		$tde->addRestriction(new InRangeRestriction(0,1000000));
		$tde->addRestriction(new IsNumericRestriction());
		$form->addElement('threshold_de',$tde);
		
		$tde2 = new TextInput('threshold fr','threshold_fr',$exam['threshold_fr']);
		$tde2->addRestriction(new InRangeRestriction(0,1000000));
		$tde2->addRestriction(new IsNumericRestriction());
		$form->addElement('threshold_fr',$tde2);
		$form->addElement('max_bday',new DateInput('earliest allowed birthday','max_bday',date('Y')-19 .'-07-01'));
		switch($_SESSION['language_abb'])
		{
			case 'de': $html='Threshold setzen'; break;
			case 'fr': $html='?only german text available?'; break;
			case 'it': $html='?only german text available?'; break;
			case 'en': $html='set threshold'; break;
		}								
		$form->addElement('threshold',new Submit('threshold',$html));		
		
		return $form;
	}
	
	public function process_choose_action_form()
	{
		 
		
		$form = $this->getForm('choose_action_form');	
		$button = $form->getButtonPressed();
		
		$thisExam = $form->getVector();
		
		$this->selected_exam_id = $thisExam['id'];
		
		print_r($thisExam);
		
		if(!$form->validate())
		{
			//echo $form->printErrors();
			return FALSE;
		}	
		
		$sql=SqlQuery::getInstance();
		
		switch($button)
		{
			case 'threshold':
			
				$tde = $form->getElementValue('threshold_de');
				$tfr = $form->getElementValue('threshold_fr');
				$max_bday = $form->getElementValue('max_bday');
			
				//first set passed to zero everywhere
				//$sql="update student_exam set passed=0 where exam_id=".$thisExam['id'];
				$sql->updateQuery('IBO_student_exam',array('passed'=>0),array('exam_id'=>$thisExam['id']));
				//threshold DE
				$qry="update IBO_student_exam se, user u, language l set se.passed=1 where se.exam_id=".$thisExam['id']." and se.user_id=u.id and se.language_id=l.id and l.abb='de' and se.total>=".$tde;
				$sql->execute($qry);
				//TODO: problem here!!!
				//threshold FR
				$qry="update IBO_student_exam se, user u, language l set se.passed=1 where se.exam_id=".$thisExam['id']." and se.user_id=u.id and se.language_id=l.id and (l.abb='fr' ||  l.abb='it' ) and se.total>=".$tfr;
				$sql->execute($qry);
				
				//too old (=> -1).. or don't know (=> -2)
				$qry="UPDATE IBO_student_exam se,user u
						SET se.passed=IF(YEAR(birthday)=1900,-2,-1)
						WHERE se.exam_id=".$thisExam['id']." AND se.user_id=u.id AND se.passed=1 AND u.birthday<'{$max_bday}'";
				$sql->execute($qry);

						
				
				$sql->updateQuery('IBO_exam',array('threshold_de'=>$tde,'threshold_fr'=>$tfr),array('id'=>$thisExam['id']));
				
				/*DO NOT UNCOMMENT UNLESS YOU UNDERSTAND WHAT IT DOES!
				$sql="UPDATE student_exam se, students s, users u set u.user_group_id=".$this->camp_group_id." where se.exam_id=".$thisExam['id']." and se.student_id=s.id and s.user_id=u.id and se.passed=1";
				echo $sql;
				sql_query($sql);
				echo '<br/>students in camp group:'.mysql_affected_rows().'<br/>';
				*/
				
				
				$html='<p class="notice">';
				switch($_SESSION['language_abb']){
					case 'de': $html.='Threshold gesetzt'; break;
					case 'fr': $html.='?only german text available?'; break;
					case 'it': $html.='?only german text available?'; break;
					case 'en': $html.='?only german text available?'; break;
				}	   			   			
				$html.='</p>'; 	
				$this->process_msg .= $html;		

			break;
			case 'result':
				//create question objects
				$qry='select q.*, qt.name as type_name from IBO_question q, IBO_question_type qt where exam_id='.$thisExam['id'].' and qt.id=q.type_id order by position asc';
				$result=$sql->simpleQuery($qry);
				$questions=array();
				foreach($result as $s)
				{
					$n='question_'.$s['type_name'];
					$questions[]=new $n($s);
				}
				//prepare array for statistics
				foreach($questions as $k=>$v)
				{          		
					$stats['prop_right'][$v->name]=0;      			
				}    	     			
				
							
							
							
				//get students
				$qry='select u.*, se.id as student_exam_id, se.answer, se.language_id as lan_id from user u, IBO_student_exam se
					 where se.exam_id='.$thisExam['id'].' AND u.id=se.user_id';         		 
				$stud=$sql->simpleQuery($qry);
				$updated_students=0;
				
				
				
				foreach($stud as $s)
				{
						
					//if($s['lan_id'] == 3) echo 'student_id: '.$s['id'].'<br/>';   				
					//make answer string by passing it through the routines of the question objects
					$template=$s['answer'];
					$parsed="";
					foreach($questions as $v)
					{
						if($err=$v->parse_student_answer($template, $parsed)){
							$this->process_msg.='<p class="notice">-> Student: '.$s['id'].' '.$s['first_name'].' '.$s['last_name'].' -> '.$err.'</p>';
							break;
						}
					}
					//now explode the new answer string and correct the whole stuff!
					$a=explode('#', trim($parsed, '#'));
					$total=0;
					foreach($a as $k=>$v)
					{    
						$check=$questions[$k]->check($v, $s['lan_id']);
						if($check>0){
							$total+=$check;
							++$stats['prop_right'][$questions[$k]->name];
						} else {
							if(!$questions[$k]->skipped){
								if(isset($stats['prop_wrong'][$questions[$k]->name][$v])) ++$stats['prop_wrong'][$questions[$k]->name][$v];
								else $stats['prop_wrong'][$questions[$k]->name][$v]=1;							
							}
						}
						//echo 'tot='.$total.'<br />';
					}    	
					//echo 'TOTAL = '.$total.'<br /><br /><br />';  		
					$sql->updateQuery('IBO_student_exam',array('total'=>$total),array('id'=>$s['student_exam_id']));
					++$updated_students;
				}
				
				
				//update stats -> could be in the question class....   		
				foreach($questions as $k=>$v){
					$mostfreq_wrong_prop=0;
					$mostfreq_wrong_answer='';
					foreach($stats['prop_wrong'][$v->name] as $key=>$value){
						if($value>$mostfreq_wrong_prop){
							$mostfreq_wrong_prop=$value;
							$mostfreq_wrong_answer=$key;
						}   					
					}   				
					//$sql='update question set prop_right='.($stats['prop_right'][$v->name]/$updated_students).', prop_mostfreq_wrong='.($mostfreq_wrong_prop/$updated_students).', mostfreq_wrong="'.$mostfreq_wrong_answer.'" where id='.$v->id;
					$sql->updateQuery('IBO_question',
						array('prop_right'=>($stats['prop_right'][$v->name]/$updated_students),
						'prop_mostfreq_wrong'=>($mostfreq_wrong_prop/$updated_students),
						'mostfreq_wrong'=>$mostfreq_wrong_answer),array('id'=>$v->id)
						);
				}
				
				
				$html='<p class="notice">'.$updated_students.' ';
				switch($_SESSION['language_abb']){
					case 'de': $html.='Sch&uuml;er wurden mit einem Total versehen!'; break;
					case 'fr': $html.='??'; break;
					case 'it': $html.='??'; break;
					case 'en': $html.='??'; break;
				}	   			   			
				$html.='</p>';
				$this->process_msg .= $html;
			
			break;
			
			case 'ranking':				
				//Ranking CH
				$qry="SELECT u.*, se.id as student_exam_id, se.total, l.abb 
				FROM language l, user u, IBO_student_exam se
				WHERE se.exam_id=".$thisExam['id']." 
					AND se.language_id=l.id 
					AND u.id=se.user_id 
				ORDER BY se.total desc;";        	 
				$res=$sql->simpleQuery($qry);
				$rang=0;
				$rang_print=0;
				$total=0;
				$updated_students=0;
				foreach($res as $s){
					++$rang;
					if($total!=$s['total'])
					{
						$total=$s['total'];
						$rang_print=$rang;
					}
					$u="update student_exam set rang=".$rang_print." where id=".$s['student_exam_id'];
					$sql->updateQuery('IBO_student_exam',array('rang'=>$rang_print),array('id'=>$s['student_exam_id']));
					++$updated_students;
				}
				
				//Ranking DE
				$qry="
				SELECT u.*, se.id as student_exam_id, se.total, l.abb from language l, user u, IBO_student_exam se
				WHERE se.exam_id=".$thisExam['id']." 
				AND se.language_id=l.id and u.id=se.user_id 
				AND l.abb='de' 
				ORDER BY se.total DESC;"; 
								
				$res=$sql->simpleQuery($qry);
				
				$rang=0;
				$rang_print=0;
				$total=0;
				foreach($res as $s){
					++$rang;
					if($total!=$s['total']){
						$total=$s['total'];
						$rang_print=$rang;
					}
					//$u="update student_exam set rang_de=".$rang_print." where id=".$s->student_exam_id;
					$sql->updateQuery('IBO_student_exam',array('rang_de'=>$rang_print),array('id'=>$s['student_exam_id']));
				}
				
				//Ranking FR
				$qry="
				SELECT u.*, se.id as student_exam_id, se.total, l.abb from language l, user u, IBO_student_exam se
				WHERE se.exam_id=".$thisExam['id']." 
				AND se.language_id=l.id and u.id=se.user_id 
				AND (l.abb='fr' OR l.abb='it') 
				ORDER BY se.total DESC;"; 
								
				$res=$sql->simpleQuery($qry);
				
				$rang=0;
				$rang_print=0;
				$total=0;
				foreach($res as $s){
					++$rang;
					if($total!=$s['total']){
						$total=$s['total'];
						$rang_print=$rang;
					}
					//$u="update student_exam set rang_de=".$rang_print." where id=".$s->student_exam_id;
					$sql->updateQuery('IBO_student_exam',array('rang_fr'=>$rang_print),array('id'=>$s['student_exam_id']));
				}
				$html='<p class="notice">'.$updated_students;
				switch($_SESSION['language_abb']){
					case 'de': $html.='Sch&uuml;er wurden mit einem Rang versehen!'; break;
					case 'fr': $html.='?only german text available?'; break;
					case 'it': $html.='?only german text available?'; break;
					case 'en': $html.='?only german text available?'; break;
				}	   			   			
				$html.='</p>';  	
				$this->process_msg = $html;		
			
				
			break;
			default:	
				$this->process_msg = '<p class="error">unknown action: '.$button.'</p>';
		}
		
		
	}
	
	
	public function select_exam_form($vector)
	{
		//SELECT EXAM FORM ------------------
		
		$form = new TabularForm(__METHOD__);
		$form->setVector($vector);
		$form->setProportions('10em','28em');

		
		switch($_SESSION['language_abb']){
			case 'de': $html ='Bitte w&auml;hlen'; break;
			case 'fr': $html ='Choisir s.v.p'; break;
			case 'it': $html ='??'; break;
			case 'en': $html ='??'; break;
		}		
		$form->addElement('html1',new HtmlElement($html));

		$query="SELECT id,de FROM IBO_exam WHERE closed < 2 ORDER BY date DESC";
		
		$q_args = array('query'  => $query,
						'keys'  => array('id'),
						'values'=> array('de')
						);
		$arg_ary = array('label'=>'exam:','name'=>'exam_id','query_args'=>$q_args);
		$form->addElement('exam_id',new DataSelect($arg_ary));
		
		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
		// END OF SELECT EXAM FORM -------------------------	
	}
	
	protected function process_select_exam_form()
	{
		$form = $this->getForm('select_exam_form');
		if(!$form->validate())
			return FALSE;
			
		$eid = $form->getElementValue('exam_id');
		
		$this->selected_exam_id = $eid;
		
		return;	
	}

	function display()
	{

		$html = $this->process_msg;
		//-------------------------------------------------------------
		//form to chose the exam 
		
		$sql = SqlQuery::getInstance();
		
		// if an exam is chosen, read from DB
		if(isset($this->selected_exam_id)){
			$qry='select * from IBO_exam where id='.$this->selected_exam_id;
			$thisExam=$sql->singleRowQuery($qry);		
		}
			
		$html .= $this->getForm('select_exam_form')->getHtml($this->id);
		
		
		// if an exam is chosen, read from DB
		if(isset($thisExam)){		
			$html.='<p class="subtitle">';
			switch($_SESSION['language_abb']){
				case 'de': $html.='Pr&uuml;fung "'.$thisExam['de'].'" ausgew&auml;hlt'; break;
				case 'fr': $html.='Examen choisi'; break;
				case 'it': $html.='?only german text available?'; break;
				case 'en': $html.='Exam chosen'; break;
			}	
			$html.='</p>';
		}
		
		if(isset($thisExam))
		{
			$html .= $this->getForm('choose_action_form',$thisExam)->getHtml($this->id);
		}
		
			
			

		
					
						
	
		return $html;
	}
	
	
	function process(){	
	
	}
	

}

?>
