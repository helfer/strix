<?php

include_once(SCRIPT_DIR . 'core/i18n.php');

class StudentLetterContent extends Content{

	
	protected $selected_exam_id = NULL;

	function display()
	{	
	
		$html = '';
		//-------------------------------------------------------------		
		//form to chose the exam 
		$html .= $this->getForm('select_exam_form')->getHtml($this->id);
		
		
		
		
		
		$sql = SqlQuery::getInstance();
		// if an exam is chosen, read from DB
		if(isset($this->selected_exam_id))
			$thisExam = $sql->singleRowQuery("select * from IBO_exam where id=".$this->selected_exam_id);	
			
		




		// if an exam is chosen, print it
		if(isset($thisExam)){		
			$html.='<p class="subtitle">';
			$html.='Pr&uuml;fung "'.$thisExam['de'].'" ausgew&auml;hlt';
			$html.='</p>';
		}
		
		//-------------------------------------------------------------
		if(isset($thisExam)){			
			//prepare letter
			$letter = '';
		
			//extract a few things
			$numStudents = $sql->singleValueQuery($qry="SELECT COUNT(id) as cnt from IBO_student_exam where exam_id=".$thisExam['id']);
			
			$qry='select count(id) as cnt from IBO_student_exam where language_id=1 and exam_id='.$thisExam['id'];
			$numStudentsDE=$sql->singleValueQuery($qry);
			
			$qry='select count(id) as cnt from IBO_student_exam where language_id=2 and exam_id='.$thisExam['id'];
			$numStudentsFR=$sql->singleValueQuery($qry);
			
			
			// LOAD TEXTS AND VARIABLES:
			$variable = new i18nVariable();

			$variable->loadFromDbByName('latex_base_preamble',1);
			
			$letter .= $variable->text;
			
			$variable = new i18nVariable();

			$variable->loadFromDbByName('latex_letter_head',1);
			
			$letter_head = $variable->text; 
				
			$variable = new i18nVariable();
	
			$variable->loadFromDbByName('student_letter_succeed_r1_latex');
			
			$variable->loadAdditionalFragments();
			
			$einladung[1]=$variable->getAdditionalFragment(1,'text');
			$einladung[2]=$variable->getAdditionalFragment(2,'text');
			$einladung[3]=$variable->getAdditionalFragment(3,'text');
			
			$variable = new i18nVariable();
			$variable->loadFromDbByName('student_letter_r1_nopass');
			$variable->loadAdditionalFragments();
			$absage[1]=$variable->getAdditionalFragment(1,'text');
			$absage[2]=$variable->getAdditionalFragment(2,'text');
			$absage[3]=$variable->getAdditionalFragment(3,'text');
			
			$variable = new i18nVariable();
			$variable->loadFromDbByName('student_letter_r1_too_old');
			$variable->loadAdditionalFragments();
			$zualt[1]=$variable->getAdditionalFragment(1,'text');
			$zualt[2]=$variable->getAdditionalFragment(2,'text');
			$zualt[3]=$variable->getAdditionalFragment(3,'text');
			
			$variable = new i18nVariable();
			$variable->loadFromDbByName('notfallblatt_latex');
			$variable->loadAdditionalFragments();
			$notfall[1]=$variable->getAdditionalFragment(1,'text');
			$notfall[2]=$variable->getAdditionalFragment(2,'text');
			$notfall[3]=$variable->getAdditionalFragment(3,'text');
			//print_r($notfall);
			
			//extract data for letter and loop over students
			$qry="SELECT
					 u.id, 
					 u.first_name as vorname, 
					 u.last_name as nachname, 
					 u.co as co,
					 s.teacher_id, 
					 se.language_id, 
					 u.zip as plz, 
					 u.city as ort, 
					 u.street as strasse, 
					 se.total, 
					 se.rang, 
					 se.rang_de, 
					 se.rang_fr, 
					 se.passed, 
					 u.username as login, 
					 scn.new_pw as password
				FROM
				 user u 
				 JOIN IBO_student_exam se ON se.user_id = u.id 
				 JOIN IBO_student s ON s.user_id = u.id
				 JOIN IBO_scanned_exam scn ON scn.student_exam_id = se.id
				WHERE
					se.exam_id='{$thisExam['id']}'
				ORDER BY 
					se.rang,
					u.last_name,
					u.first_name";
			$result=$sql->simpleQuery($qry);		
			
			echo '%'.count($result).' Schüler!';
		
			foreach($result as $a){		
						
				$tags=array(
					'[[VORNAME]]',
					'[[NACHNAME]]',
					'[[ADRESSE]]',
					'[[PLZ]]',
					'[[ORT]]',
					'[[NUMSTUDENTS]]',
					'[[NUMSTUDENTSDE]]',
					'[[TOTAL]]',
					'[[RANK_TOTAL]]',
					'[[LOGIN]]',
					'[[PASSWORD]]'
					);
					
				$replace=array(
					$a['vorname'],
					$a['nachname'],
					$a['strasse'],
					$a['plz'],
					$a['ort'],
					$numStudents,
					$numStudentsDE,
					$a['total'],
					$a['rang'],
					$a['login'],
					$a['password']
				);
				
				$letter.=str_replace($tags, $replace, $letter_head);
				
				switch($a['passed'])
				{
					case '1': $letter.=str_replace($tags, $replace, $einladung[$a['language_id']]); break;
					case '0': $letter.=str_replace($tags, $replace, $absage[$a['language_id']]); break;
					case '-1': $letter.=str_replace($tags, $replace, $zualt[$a['language_id']]); break;
				}
				
				//--------------------------------------------------------------------------------------------------
				//NOTFALLBLATT				//--------------------------------------------------------------------------------------------------
				if($a['passed']==1)
				{
					$letter .= "\n".str_replace($tags, $replace,$notfall[$a['language_id']]);
				} //end notfallblatt			
			}	
				
			$letter .= '\end{document}';	
				
			$GLOBALS['RequestHandler']->SendOnlyThis($letter."\n%");
			$html.='<textarea style="width: 400px; height:200px;">if you see this, something went wrong</textarea>';	
		}					
	
		return $html;
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

}




?>
