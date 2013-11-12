<?php
class ShowMyExamContent extends Content{


	protected $mConfKeys = array('exam_id');
	protected $mConfValues = array('all');
	
	//protected $mTagKeys = array();
	//protected $mTags = array(); TODO: yeah, yeah, yeah...
	

	function display(){
		
		$selected_exam_id = FALSE;
		$selected_student_id = FALSE;
		
		$xhtml = '';

		$sel_exam_form = $this->getForm('SelectExamForm');
		
		$xhtml .= $sel_exam_form->getHtml($this->id);
		
		
		if($sel_exam_form->getButtonPressed())
		{
			$vals = $sel_exam_form->getElementValues();
			$selected_exam_id = $vals['exam_id'];
			if(isset($vals['student_id']))
				$selected_student_id = $vals['student_id'];
		}
		
		
		
		if(!$selected_exam_id) 
			return $xhtml.'<br/>no exam selected';
			
		if(!$selected_student_id)
		{
			$selected_student_id = $GLOBALS['user']->id;	
		}
	   
	   //some language stuff
	   $table_header_frage=array("de"=>"Frage","fr"=>"Question","it"=>"Domanda");
	   $table_header_deine_antwort=array("de"=>"Deine Antwort","fr"=>"Ta r&eacute;ponse","it"=>"La tua risposta");
	   $table_header_unsere_antwort=array("de"=>"L&ouml;sung","fr"=>"Solution","it"=>"Risposta guista");
	   $table_header_punkte=array("de"=>"Punkte","fr"=>"Points","it"=>"Balla");
	   $es_ging_in_die_hosen=array("de"=>"Wir haben keinen Eintrag f&uuml;r Dich in der Datenbank gefunden. Falls Du wirklich an dieser Pr&uuml;fung teilgenommen hast, bitte kontaktiere uns direkt, und wir werden versuchen,
	   den Fehler schnellst m&ouml;glich zu beheben.","fr"=>"Selon nos informations, tu n'as pas participé à cet examen. Au cas ou ça serait erroné, contacte-nous directement","it"=>"Wir haben keinen Eintrag f&uuml;r Dich in der Datenbank gefunden. Falls Du wirklich an dieser Pr&uuml;fung teilgenommen hast, bitte kontaktiere uns direkt, und wir werden versuchen,
	   den Fehler schnellst m&ouml;glich zu beheben.");
	   $link_pdf=array('de'=>'Meine Pr&uuml;fung als pdf', 'fr'=>'Mon examen sous forme de pdf', 'it'=>'Mon questionair sous forme de pdf');
	   $page_title=array('de'=>"Resultate der [[runde]] von [[name]]", 'fr'=>"Résultats du [[runde]] pour [[name]]", 'it'=>"klishf£");
				
		
		// PRINT EXAM: ------------------------------------------------------
		//return 'printing exam';
		$exam_id = $selected_exam_id;
		
		//Phäntus code is sometimes difficult to maintain. This is supposed to be a patch for it...
		if(!in_array($_SESSION["language_abb"],array('de','fr','it')))
			$lang = 'de';
		else
			$lang = $_SESSION["language_abb"];
			
			
		//$sql = 'SELECT e.'.$_SESSION['language'].' as exam_name, s.vorname, s.name FROM exam e, students s WHERE s.user_id='.$this->selected_student_id.' AND e.id='.$this->selected_exam_id;
		$query = "SELECT DISTINCT 
					u.last_name as name, 
					u.first_name as vorname, 
					se.language_id as language_id, 
					se.*, 
					e.".$lang." as exam_name 
				FROM IBO_student_exam se
					JOIN user u ON u.id = se.user_id
					JOIN IBO_exam e ON e.id = se.exam_id
				WHERE se.exam_id=".$exam_id." 
					AND u.id ='$selected_student_id'";

		
		$res1 = SqlQuery::getInstance()->singleRowQuery($query);
		//print_r($res1);
		if(!$res1){
			return $xhtml .= i18n($es_ging_in_die_hosen);	
		}
		
		
		  $xhtml .= "<p class='subtitle'>".str_replace(array("[[name]]", "[[runde]]"), array($res1['vorname']." ".$res1['name'], $res1['exam_name']), $page_title[$lang])."</p><br>";
		 
		 ///@todo autoinclude these objects!
		  //create question objects
		  include_once(SCRIPT_DIR . 'ibo_classes/class_question.php');
		  
		  //return $xhtml .'OK';
		  
		  $query = "SELECT q.*, 
		  				qt.name as type_name 
		  			FROM 
		  				IBO_question q
		  				JOIN IBO_question_type qt ON qt.id = q.type_id
		  			WHERE 
		  				exam_id='$exam_id'
		  			ORDER BY 
		  				position asc";		  
		  
		  $res_2=SqlQuery::getInstance()->simpleQuery($query);
		  
		  $questions=array();
		  foreach($res_2 as $q)
		  {
			 $n='question_'.$q['type_name'];
			 $questions[]=new $n($q);
		  }

		  //make answer string by passing it through the routines of the question objects
		  $template=$res1['answer'];
		  $parsed="";
		  foreach($questions as $v)
		  {
			 if($err=$v->parse_student_answer($template, $parsed))
			 {
				$return .= "-> Student: ".$res1['id']." ".$res1['vorname']." ".$res1['nachname']." -> ".$err;
			 }
		  }

		  $xhtml .= '<a href="http://'.$_SERVER['HTTP_HOST'].'/files/exams/?file='.$res1['filename'].'" target="_blank">'.$link_pdf[$lang].'</a><br/>';

		  //write table header
		  $xhtml .= "<table border='0' cellspacing='0' cellpadding='0'>\n";
		  $xhtml .= "<th>".$table_header_frage[$lang]."</th><td width='10'></td>";
		  $xhtml .= "\n<th>".$table_header_deine_antwort[$lang]."</th><td width='10'></td>";
		  $xhtml .= "\n<th>".$table_header_unsere_antwort[$lang]."</th><td width='10'></td>";
		  $xhtml .= "\n<th>".$table_header_punkte[$lang]."</th></tr>";

		  $color=0;
		  //now explode the new answer string and correct the whole stuff!
		  $a=explode('#', trim($parsed, '#'));
		  $total=0;
		  foreach($a as $k=>$v)
		  {
		  	//echo "question\n";
			 $total+=$questions[$k]->check($v, $res1['language_id']);
			 $xhtml .= "<tr bgcolor='#";
			 if($color) $xhtml .= "ffffff"; else $xhtml .= "EFFFE2";
			 $color=1-$color;
			 $xhtml .= "'><td><p class='text'>".$questions[$k]->name."</p></td><td></td>";
			 $xhtml .= "<td><p class='text'>".$v."</p></td><td></td>";
			 $q=$questions[$k]->get_solutions_array();
			 $xhtml .= "<td><p class='text'>".$q[$res1['language_id']]."</p></td><td></td>";
			 $xhtml .= "<td><p class='text'>".$total."</p></td></tr>";
		  }
		  $xhtml .= "\n<tr><td colspan='7' height='1' bgcolor='#000000'></td></tr>";
		  $xhtml .= "\n<tr><td><p class='text'><b>Total</b></p></td><td colspan='5'></td><td><p class='text'><b>".$res1['total']."</b></p></td></tr>";
		  $xhtml .= "\n<tr><td colspan='7' height='1' bgcolor='#000000'></td></tr></table>";
	  
		//$xhtml .= 'SELECTED STUDENT: '.$this->selected_student_id.'<br/>';
		//$xhtml .= 'SELECTED EXAM: '.$this->selected_exam_id;
	   return $xhtml;
	}
	

	function SelectExamForm($vector){
		
		$newForm = new SimpleForm(__METHOD__);
		$newForm->setVector($vector);
		
		//print_r($newForm);
		
		//SELECT exam FORM:
		$user = $GLOBALS['user'];
		$user_id = $user->id;
		
		$query = "SELECT e.id, e.de
					FROM
					IBO_exam e JOIN IBO_student_exam se ON se.exam_id = e.id
					WHERE se.user_id = '$user_id' AND e.detail=1";
		//echo $user->primary_usergroup_id;
		//echo $user->primary_usergroup_id .'==='. ORGANISATOREN;
		//var_dump($user->primary_usergroup_id);
		//var_dump(ORGANISATOREN);
		$exams = SqlQuery::getInstance()->assocQuery($query,array('id'),array('de'), TRUE);
		//TODO: don't hardcode this!!!!
		if(empty($exams) && !($user->primary_usergroup_id == ORGANISATOREN || $user->primary_usergroup_id == ADMIN_GROUP))
		{
			$newForm->addElement('sorry',new HtmlElement('Sorry, we couldn\'t find any exams for you! If you did sit an exam, please let us know!'));
		}
		else
		{			
			//TODO: don't hardcode this!!!
			//ONLY admins can select the Student who's exam they want to see.
			if($user->primary_usergroup_id == ORGANISATOREN || $user->primary_usergroup_id == ADMIN_GROUP)
			{
				//echo $user->primary_user_group_id;
				//echo $user->primary_user_group_id .'==='. ADMIN_GROUP;
				//var_dump($user->primary_user_group_id === ADMIN_GROUP);
				$query = "SELECT e.id, e.de
					FROM
					IBO_exam e";

				$exams = SqlQuery::getInstance()->assocQuery($query,array('id'),array('de'), TRUE);
				
				$sel_exam = new Select('Exam: ','exam_id',$exams);
				$newForm->addElement('exam_id',$sel_exam);
				
				$query = "SELECT u.id, concat(u.last_name,' ',u.first_name) as name
					FROM
						user u 
						JOIN user_in_group uig ON uig.user_id = u.id
					WHERE 
						uig.usergroup_id IN (14,23)
					ORDER BY
						name"; //TODO: configure this!

				$students = SqlQuery::getInstance()->assocQuery($query,array('id'),array('name'), TRUE);
		
			
				$newForm->addElement('student_id',new Select('Select Student: ','student_id',$students));
			}
			else
			{
				$sel_exam = new Select('Exam: ','exam_id',$exams);
				$newForm->addElement('exam_id',$sel_exam);
			}
		
			$newForm->addElement('submit',new Submit('submit','Show'));
		}
		//print_r($_SESSION['user']->getTableValues());

		return $newForm;
	}
	
	function process_SelectExamForm()
	{
		//print_r($this->getForm('SelectExamForm')->getElementValues());	
	}
	
	//ADDITIONAL FUNCTIONS IF NECESSARY
	


}



?>
