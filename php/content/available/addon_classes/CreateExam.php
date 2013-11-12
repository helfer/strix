<?php
/*
 *      createexam.php
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

class CreateExam extends Content
{
	function display()
	{
		$tbl = new AbTable('exams',"SELECT id,de,fr,it,date,participants_usergroup_id as grp FROM `IBO_exam`");
		
		return '<h2>list of exams</h2>'.$tbl->getHtml().'<br /><h2>create new exam</h2>'.$this->getForm('CreateExamForm')->getHtml($this->id).'x';
		
	}
	
	function CreateExamForm($vector)
	{
		/*
		$form = new DataForm(__METHOD__);
		$form->loadTableDescription('IBO_exam');
		
		return $form;
		*/
		
		//TODO: add/remove langauges to exams! (fragments to keep it in line with the rest!) exam will be an object!
		
		$form = new TabularForm(__METHOD__);
		
		$nonempty = new NotEmptyRestriction();
		
		$name_de = new TextInput('Name de: ','name_de','');
		$name_de->addRestriction($nonempty);
		$name_fr = new TextInput('Nom fr: ','name_fr','');
		$name_fr->addRestriction($nonempty);
		$name_it = new TextInput('Nom it: ','name_it','');
		$name_it->addRestriction($nonempty);
		$date = new TextInput('date: ','date',date('Y-m-d'));
		$date->addRestriction($nonempty);

		//little hack with usergroup 18 for SBO-week 2010 !!!
		$qarg = array('query'=>'SELECT id,name FROM usergroup WHERE name LIKE "TN%"','keys'=>array('id'),'values'=>array('name'));
		$partici = new DataSelect('participants: ','part_usergroup_id',$qarg);
		$type = new Select('type:','type',array('0'=>'auto','1'=>'manual','3'=>'summary'));
		
		$form->addElement('de',$name_de);
		$form->addElement('fr',$name_fr);
		$form->addElement('it',$name_it);
		$form->addElement('date',$date);
		$form->addElement('participants_usergroup_id',$partici);
		$form->addElement('type',$type);
		
		$form->addElement('sm',new SimpleSubmit());
		
		return $form;
		
	}	
	
	public function process_CreateExamForm()
	{
		$frm = $this->getForm('CreateExamForm');	
		if(!$frm->validate())
			return;
			
		$sql = SqlQuery::getInstance();
		
		$values = $frm->getElementValues();
		unset($values['sm']);
		print_r($values);
		
		$sql->start_transaction(); //---------------------------------
		
			$exam_id = $sql->insertQuery('IBO_exam',$values);
			
			$sql->insertSelectQuery('IBO_student_exam','
`id` ,
`user_id` ,
`exam_id` ,
`language_id` ,
`answer` ,
`total` ,
`t_score` ,
`rang` ,
`rang_de` ,
`rang_fr` ,
`filename` ,
`passed`',
"SELECT NULL,user_id, '$exam_id', '1', 'TEST', '0', '0', '0', NULL , NULL , NULL , '0' FROM `user_in_group` WHERE usergroup_id = '".$values['participants_usergroup_id']."'");

			
		$sql->end_transaction(); //-----------------------------------
	}
}


//BEFORE YOU CAN CORRECT AN EXAM, YOU NEED TO EXECUTE A QUERY LIKE THIS ONE:

//actually, you'll have a usergroup which is specific to this exam, so when you 
//add users, you have to create an exam at the same time. Make sure you can add users even after
//the initial phase!
/*
 INSERT INTO `nano`.`IBO_student_exam` (
`id` ,
`user_id` ,
`exam_id` ,
`language_id` ,
`answer` ,
`total` ,
`t_score` ,
`rang` ,
`rang_de` ,
`rang_fr` ,
`filename` ,
`passed`
)

SELECT NULL,user_id, '1', '1', 'TEST', '0', '0', '0', NULL , NULL , NULL , '0' FROM `user_in_group` WHERE usergroup_id = 11

/*








*/

?>

