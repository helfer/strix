<?php
/*
 *      usermanagercontent.php
 */
include('ibo_classes/ScannedExam.php');

class ListScannedExamsContent extends Content
{


	public function display()
	{
		$xhtml = '';
		
		$num_corr = SqlQuery::getInstance()->singleValueQuery("SELECT COUNT(id) FROM IBO_scanned_exam s WHERE verified >= 1");
		$num_ncorr = SqlQuery::getInstance()->singleValueQuery("SELECT COUNT(id) FROM IBO_scanned_exam s WHERE verified <= 0");
		
		
		
		
		$xhtml .= '<p>Number of entries already verified: '.$num_corr.'</p>';
		$xhtml .= '<p>Number of entries still to be verified: '.$num_ncorr.'</p>';
		
		
		$table = new AbTable('table'.$this->id,'SELECT s.id, verified, u.first_name, u.last_name, s.exam_file, s.changed, u2.username, s.verified, s.match_dist FROM IBO_scanned_exam s JOIN user u ON u.id = s.user_id LEFT JOIN user u2 ON u2.id = s.verified_by',array('id'));
		
		$sel = $table->getSelected();
		//var_dump($sel);
		
		if($sel !== NULL) //display user_edit
		{
			$xhtml .= '<a href="?"><b>back</b></a><br />';
			$xhtml .= '<b>scanned exam:</b></ br>';
			$xhtml .= $this->process_msg;
			$xhtml .= $this->getForm('edit_scan_form', $sel)->getHtml($this->id);
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
	
	public function process_edit_scan_form()
	{
		$form = $this->getForm('edit_scan_form');
		if($form->validate())
		{
			$vals = $form->getElementValues();
			unset($vals['id']);
			print_r($vals);
			$scan = new ScannedExam();
			$scan->LoadFromDbById($form->getVector());
			foreach($vals as $k=>$v)
				$scan->__set($k,$v);
			if($scan->store())
				$this->process_msg = '<p class="success">Changes have been applied</p>';	
			else
				$this->process_msg = '<p class="problem">Changes could not be stored</p>';
			
		}
		else
			$this->process_msg = '<p class="problem">The form contains errors</p>';
	}
	
	
	//vector = id of Usergroup
	public function edit_scan_form($vector)
	{
		$newForm = new DataForm(__METHOD__);
		$newForm->setVector($vector);
		
		$scan = new ScannedExam();
		$scan->LoadFromDbById($vector);
		
		$newForm->loadFromObject($scan);
		$newForm->addElement('submit',new SimpleSubmit());
		
		return $newForm;
		
	}
	
	


}

?>
