<?php
/*
 *      addexamquestions.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

/*STEP 1: 
	create a new question -> take shortcut!
	* 
	* [exam_id], {name, points}, position
	* 
	* should be able to change this afterwards, i.e. delete questions and insert questions.
*/

//TODO: per question statistics? (score distribution graphics?)

//TODO: this is just for type 6 questions.
class AddExamQuestions extends Content
{

	public function display()
	{
		$xhtml = '';
		
		$exam_form = $this->getForm('SelectExamForm');
		$xhtml .= $exam_form->getHtml($this->id);
		
		
		if($eid = $exam_form->getElementValue('exam_id'))
		{
			
			//TODO: question weight??? does it matter?
			$tbl = new AbTable('questions',"
			SELECT eq.`position`,q.`id`,q.`name`, q.`points`
				FROM `IBO_exam_question` eq 
				JOIN `IBO_question` q ON q.`id` = eq.`question_id`
				WHERE eq.`exam_id` = '$eid'", array('id'));
				
			if($delete = $tbl->getSelected())
			{
				$xhtml .= 'DELETED QUESTION '.$delete.'!<br />';
				$sql = SqlQuery::getInstance();
				$sql->start_transaction(); //------------------------------------------
					$sql->deleteQuery('IBO_exam_question',array('question_id'=>$delete));
					$sql->deleteQuery('IBO_question',array('id'=>$delete));	
				$sql->end_transaction(); //----------------------------------------------
				
				$tbl = new AbTable('questions',"
				SELECT eq.`position`,q.`id`,q.`name`, q.`points`
				FROM `IBO_exam_question` eq 
				JOIN `IBO_question` q ON q.`id` = eq.`question_id`
				WHERE eq.`exam_id` = '$eid'", array('id'));
			}
				
			$xhtml .= $tbl->getHtml();
			
			
			//add question form, vector = exam #
			$q_form = $this->getForm('AddQuestionForm',array('exam_id'=>$eid));
			$xhtml .= $q_form->getHtml($this->id);	
			
			
		}
		
		
		return $xhtml;
	}
	
	
	public function AddQuestionForm($vector)
	{
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
	
		$name = new TextInput('question name: ','name','');
		$name->addRestriction(new NotEmptyRestriction());
		$points = new NumericInput('points: ','points','1',array('min'=>'0','max'=>'1024'));
		$position = new NumericInput('position: ','position','1',array('min'=>'0','max'=>'512'));
		//TODO add the weight here once the question object is really completed.
		
		$newForm->addElement('name',$name);
		$newForm->addElement('points',$points);
		$newForm->addElement('position',$position);
		$newForm->addElement('sm',new SimpleSubmit());
		
		return $newForm;
	}
	
	public function process_AddQuestionForm()
	{
		$form = $this->getForm('AddQuestionForm');
		
		if( !$form->validate())
			return FALSE;
			
		$values = $form->getElementValues();
		$exam_id = $form->getVectorValue('exam_id');
			
		$sql = SqlQuery::getInstance();
		
		$sql->start_transaction(); //-----------------------------------
		//TODO: only for type 6 so far!
		$question = array('name'=>$values['name'],'exam_id'=>$exam_id,'points'=>$values['points'],'solution'=>'no','type_id'=>'6');
		$insert_id = $sql->insertQuery('IBO_question',$question);
		notice($question);
		
		$exam_question = array('question_id'=>$insert_id,'exam_id'=>$exam_id,'weight'=>$values['points'],'position'=>$values['position']);
		$sql->insertQuery('IBO_exam_question',$exam_question);
		notice($exam_question);
		
		$sql->end_transaction(); //-------------------------------------
	}
	
	
	
	
	public function	process_SelectExamForm()
	{
		$this->getForm('SelectExamForm')->validate();	
	}
	
	
	function SelectExamForm($vector)
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
		
		$newForm->addElement('submit',new Submit('submit','select'));

		$newForm->stayAlive();
		return $newForm;
	}
	
}

?>
