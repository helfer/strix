<?php

require_once(SCRIPT_DIR . 'core/usergroup.php');


/*
 *      groupmanagercontent.php
 */
class GroupManagerContent extends Content
{


	public function display()
	{
		$xhtml = '';
		
		$table = new AbTable('table'.$this->id,'SELECT id, name, description FROM usergroup',array('id'));
		
		$sel = $table->getSelected();
		//var_dump($sel);
		
		if($sel !== NULL) //display group_edit
		{
			$xhtml .= '<a href="?"><b>back</b></a><br />';
			$xhtml .= '<b>Edit group:</b></ br>';
			$xhtml .= $this->process_msg;
			$xhtml .= $this->getForm('edit_group_form', $sel)->getHtml($this->id);
		}
		else //display table of groups
		{
			$xhtml .= $table->getHtml();
			$xhtml .= $this->getForm('add_group_form')->getHtml($this->id);
		}
		return $xhtml;
	}
	
	public function process_add_group_form()
	{
		$form = $this->getForm('add_group_form');
		$name = $form->getElementValue('name');
		$sql = SqlQuery::getInstance();
		$ok = $sql->insertQuery('usergroup',array('name'=>$name,'description'=>''));
		if ($ok)
			echo 'inserted';
		else
			echo 'there was an error';
	}
	
	public function add_group_form($vector)
	{
		
		$newForm = new SimpleForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->addElement('desc', new HtmlElement('<b>Add new group:</b>&nbsp;'));
		$newForm->addElement('name', new TextInput('','name',''));
		$newForm->addElement('submit', new SimpleSubmit());
		
		
		return $newForm;
		
	}
	
	public function process_edit_group_form()
	{
		$form = $this->getForm('edit_group_form');
		if($form->validate())
		{
			$vals = $form->getSomeElementValues(array('name','description','chief_user_id'));
			print_r($vals);
			$group = new Usergroup();
			$group->LoadFromDbById($form->getVector());
			foreach($vals as $k=>$v)
				$group->__set($k,$v);
			if($group->store())
				$this->process_msg = '<p class="success">Changes have been applied</p>';	
			else
				$this->process_msg = '<p class="problem">Changes could not be stored</p>';
			
		}
		else
			$this->process_msg = '<p class="problem">The form contains errors</p>';
	}
	
	
	//vector = id of Usergroup
	public function edit_group_form($vector)
	{
		$newForm = new DataForm(__METHOD__);
		$newForm->setVector($vector);
		
		$group = new Usergroup();
		$group->LoadFromDbById($vector);
		
		$newForm->loadFromObject($group);
		$newForm->addElement('submit',new SimpleSubmit());
		
		return $newForm;
		
	}
	
	


}

?>
