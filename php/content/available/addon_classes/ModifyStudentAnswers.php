<?php


class ModifyStudentAnswers extends Content
{
	
	
	function display()
	{
		$xhtml = '';
		
		$form = $this->getForm('SelectStudentExamForm');
		
		$xhtml .= '<h2>Score tests: </h2>'.$form->getHtml($this->id);
		//SELECT EXAM:
		//SELECT STUDENT:
		
		if(($selection = $form->getElementValues()) /*&& $form->getButtonPressed()*/)
		{
			
			$eid = $selection['exam_id'];
			$exam_name = $form->getElement('exam_id')->getOptionValue($eid);
			
			//TODO: what's up with this query? written at 1:30AM ???
			$query = "SELECT DISTINCT 
						se.`user_id`,
						eq.`position`,
						q.`id` as qid,
						q.`type_id`,
						q.`name`,
						sa.`answer`,
						sa.`score`,
						q.`points`,
						q.`solution`,
						sa.`comment`,
						sa.`question_id` as 'answered',
						se.`id` as 'student_exam_id'
					 FROM 
						`IBO_exam_question` eq
						LEFT JOIN `IBO_student_exam` se ON se.`exam_id`='$eid'
						LEFT JOIN `IBO_student_answer` sa ON sa.`question_id` = eq.`question_id` AND se.`id` = sa.`student_exam_id`
						LEFT JOIN  `IBO_question` q ON q.`id` = `eq`.`question_id`
						LEFT JOIN user u ON u.id = se.user_id
					WHERE
						eq.`exam_id` = '$eid'
						AND se.`id` IS NOT NULL
					ORDER BY
						u.first_name, u.last_name, position ASC
					"; //just to make sure there's a student exam there.

			$sql = SqlQuery::getInstance();
			
			$ans = $sql->simpleQuery($query);
			
			
			if(empty($ans))
				$xhtml .= 'sorry, no student exams found!';
			else
			{
				//print_r($ans);
			
				$answers_form = $this->getForm('ModifyAnswersForm',array('arr'=>$ans,'eid'=>$eid));
			
				$xhtml .= "<b>Score $exam_name for all students</b>".$answers_form->getHtml($this->id);
			}
		}
		
		/*TODO: indicate the students who have unanswered questions for this exam!
		$noanswer_query = "
			SELECT DISTINCT  u.`id`, concat(u.last_name,' ',u.first_name) as name 
			FROM `IBO_exam_question` eq 
			JOIN `IBO_student_exam` se 
				ON eq.`exam_id` = se.`exam_id` 
			JOIN `user` u 
				ON u.`id` = se.`user_id` 
			LEFT JOIN `IBO_student_answer` sa 
				ON sa.`question_id` = eq.`question_id` AND sa.`student_exam_id`=se.`id` 
			
			WHERE 
				sa.`score` IS NULL 
				AND eq.`exam_id`=$eid
			ORDER BY name ASC";
		
		$unanswered = $sql->assocQuery($noanswer_query,array('id'),array('name'));
		
		//print_r($unanswered);
		
		$xhtml .= '<p><b>The following students have unscored questions for this exam:<br /></b>';
		foreach($unanswered as $person)
			$xhtml .= $person['name'].'<br />';
			
		$xhtml .= '</p>';
		*/
		return $xhtml;
		
	
		//show+modify answers depending on type (implement only for one type now!)
		
	}
	
	//vector is array of answers (nano.IBO_student_answer)
	function ModifyAnswersForm($vector)
	{
		//print_r($vector);
		
		$newForm = new AccurateForm(__METHOD__);
		$newForm->setVector($vector);
		
		
		//fields of intrest for the questions...
		//$field = array('position','id','name','answer','solution','score','points','comment');
		//print_r($vector);
		//$q_array = $vector['arr'][0];

		$people = SqlQuery::getInstance()->assocQuery("SELECT u.id, CONCAT(first_name, last_name) as name FROM IBO_student_exam se JOIN user u ON se.user_id = u.id WHERE se.exam_id = '".$vector['eid']."'",array('id'),array('name'));
		$questions = SqlQuery::getInstance()->simpleQuery("SELECT * FROM IBO_question q WHERE q.exam_id = '".$vector['eid']."'");
		$newForm->setGridSize(count($people)+3,count($questions)+1);
		
		//return $newForm;
		//echo 'people: '.count($people);
		//echo 'questions: '.count($questions);
		//print_r($people);
		//print_r($questions);
	
		//make headers:
		$col = 0;
		$row = 0;
		foreach($questions as $q)
		{
			$newForm->putElement($row.'-'.$col,$row,$col+1,new HtmlElement('<b>'.$q['name'].'</b>'));
			$col++;
		}

		//return $newForm;
		
		$row = 1;
		$col = 0;

		$current_stud_id = 0;
		foreach($vector['arr'] as $ans)
		{
			if($current_stud_id != $ans['user_id'])
			{
				$current_stud_id = $ans['user_id'];
				$row++;
				$col = 0;
				//echo '\n student now:'.$people[$ans['user_id']]['name'];
				$newForm->putElement('name'.$ans['user_id'],$row,$col,new HtmlElement('<b>'.$people[$ans['user_id']]['name'].'</b>'));
			}

			$col++;
			//echo 'pos = '.$row.' col '.$col.' ';
			//echo ' question now: '.$ans['name'];

			$el = new TextInput('',$ans['user_id'].'_'.$ans['qid'],$ans['score'],array('style'=>'width:2em'));
			$el->addRestriction(new InRangeRestriction(0,$ans['points']));
			$newForm->putElement($ans['qid'].'_'.$ans['student_exam_id'],$row,$col,$el);


		}

		$row++;
		
	echo 'wut';
		
		$newForm->putElement('submit',$row,0,new SimpleSubmit());
		
		return $newForm;
	}
	
	function SelectStudentExamForm($vector)
	{
		
		$newForm = new SimpleForm(__METHOD__);
		$newForm->setVector($vector);
		

		$query = "SELECT e.id, e.de
			FROM
			IBO_exam e
			WHERE closed = 0";

		$exams = SqlQuery::getInstance()->assocQuery($query,array('id'),array('de'), TRUE);
		
		$sel_exam = new Select('Exam: ','exam_id',$exams);
		$newForm->addElement('exam_id',$sel_exam);
		
		
		//TODO: okay, I know this is cheating. The usergroup should come 
		//from the list of people who participated in that specific exam (implement AJAX!)
		$query = "SELECT u.id, concat(u.last_name,' ',u.first_name) as name
			FROM
				user u 
				JOIN user_in_group uig ON uig.user_id = u.id
			WHERE 
				uig.usergroup_id IN (18)
			ORDER BY
				name"; //TODO: configure this!

		$students = SqlQuery::getInstance()->assocQuery($query,array('id'),array('name'), TRUE);

	
		//$newForm->addElement('student_id',new Select('Select Student: ','student_id',$students));


		$newForm->addElement('submit',new Submit('submit','Show Answers'));

		$newForm->stayAlive();
		return $newForm;
	}
	
	function process_ModifyAnswersForm()
	{
		echo 'in form process!';
		
		$ans_form = $this->getForm('ModifyAnswersForm');
		
		if(!$ans_form->validate())
			return FALSE;
		
		$changes = $ans_form->getChangedElementValues();
		$eid = $ans_form->getVectorValue('eid');
		//$se_id = $vect_arr['0']['student_exam_id'];	

		echo 'eid'+$eid;	

		//print_r($changes);

		$sql = SqlQuery::getInstance();
		
		foreach($changes as $chg=>$val){
			list($qid,$seid) = explode('_',$chg);
			//insert query with replace. will replace if unique combination of qid and seid already exists.
			$sql->insertQuery('IBO_student_answer',array('question_id'=>$qid,'student_exam_id'=>$seid,'answer'=>'','comment'=>'','score'=>$val),FALSE,TRUE);
		}
	}
	
	
	
	function process_SelectStudentExamForm()
	{
		//print_r($this->getForm('SelectExamForm')->getElementValues());	
	}
	
	
	//TODO: grrrrrrrrrrrrrrrrr this is so stupid! I hate myself for this quick fix!
	function update_vect($id,$vect)
	{
		$ret = array();
		foreach($vect as $k=>$v)
		{
			$ret[$k] = $v;
			if($v['id'] == $id)
			{
				$ret[$k]['answered'] = '1';
				//echo 'changed for '.$id;	
			}
				
		}	
		
		return $ret;
	}
}

?>
