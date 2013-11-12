<?php
/*
 *      usermanagercontent.php
 */
class UserManagerContent extends Content
{


	public function display()
	{
		$xhtml = '';
		
		$table = new AbTable('table'.$this->id,'SELECT id, username, first_name, last_name FROM user',array('id'));
		
		$sel = $table->getSelected();
		//var_dump($sel);
		
		if($sel !== NULL) //display user_edit
		{
			$xhtml .= '<a href="?"><b>back</b></a><br />';
			$xhtml .= '<b>Edit user:</b></ br>';
			$xhtml .= $this->process_msg;
			$xhtml .= $this->getForm('edit_user_form', $sel)->getHtml($this->id);
		}
		else //display table of users
		{
			$xhtml .= $table->getHtml();
			//$xhtml .= $this->getForm('add_user_form')->getHtml($this->id);
		}
		return $xhtml;
	}
	/*
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
	*/
	
	public function process_edit_user_form()
	{
		$form = $this->getForm('edit_user_form');
		if($form->validate())
		{
			$vals = $form->getElementValues();
			unset($vals['id']);
			print_r($vals);
			$user = User::LoadFromDbById($form->getVector());
			foreach($vals as $k=>$v)
				$user->__set($k,$v);
			if($user->store())
				$this->process_msg = '<p class="success">Changes have been applied</p>';	
			else
				$this->process_msg = '<p class="problem">Changes could not be stored</p>';
			
		}
		else
			$this->process_msg = '<p class="problem">The form contains errors</p>';
	}
	
	
	//vector = id of Usergroup
	public function edit_user_form($vector)
	{
		$newForm = new DataForm(__METHOD__);
		$newForm->setVector($vector);
		
		$user = new User();
		$user->LoadFromDbById($vector);
		
		$newForm->loadFromObject($user);
		$newForm->addElement('submit',new SimpleSubmit());
		
		return $newForm;
		
	}
	
	


}

?>
