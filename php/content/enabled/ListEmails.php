<?php
/*
 *      listemails.php
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


class ListEmails extends Content
{
	protected $fragment_vars = array();
	protected $mProcMsg = '';

	public function display()
	{
		
		$xhtml = '';
		
		$tbl = new AdvancedTable('adt'.$this->id,'SELECT id, name, description FROM usergroup',array('id'));
		
		//$xhtml .=  $tbl->getHtml();
		
		$form = $this->getForm('form_test');
		$xhtml .= $form->getHtml($this->id);
		
		$xhtml .= $this->mProcMsg;
		
		return $xhtml;
	}
		
		
	protected function process_form_test()
	{
		$form = $this->getForm('form_test');
		$form->validate();	
		$usergroup_id = $form->getElementValue('group_select');
		$lang_ids = $form->getElement('mcb')->getChecked();
		
		
		if(empty($lang_ids)){
			$this->mProcMsg = 'No language selected!';
			return;
		}
			
		
		$languages = '('.implode(',',$lang_ids).')';
		
		$sql = SqlQuery::getInstance();
		
		
		//doesn't work with group_concat because of the max_len (and I don't want to change it...
		$query = "SELECT DISTINCT u.id, u.email as email 
		FROM user u
		JOIN user_in_group uig ON uig.user_id = u.id
		WHERE uig.usergroup_id=$usergroup_id AND u.language_id IN $languages AND CHAR_LENGTH(u.email) > 8";
		
		//echo $query;
		
		$emails = $sql->assocQuery($query,array('id'),array('email'),TRUE);
		

		
		//echo $emails[0]['number'].' adresses returned:<br />'.$emails[0]['emails'];
		$this->mProcMsg = '<p><b>'.count($emails).' adresses returned:</b><br />';
		$impl = array();
		foreach($emails as $email)
			$impl []= $email;
			
		$this->mProcMsg .= implode(', ',$impl).'</p>';
	}
	
	
	protected function form_test()
	{
		$form1 = new TabularForm('form_test');
		
		$form1->setProportions('12em','36em');

		$query_args = array('query'=>'SELECT * FROM `usergroup`','keys'=>array('id'),'values'=>array('name'));
		
		//test for genericform element construction
		$dselect = FormElement::createFromArray(array('classname'=>'DataSelect','label'=>'Group: ','name'=>'group_select','query_args'=>$query_args) );
		$form1->addElement('group_select',$dselect);
		
		$sql = SqlQuery::getInstance();
		$langs = $sql->assocQuery("SELECT * FROM `language` WHERE `active` = '1'",array('id'),array('name'),TRUE);
		
		$cbtest = new MultiCheckbox('Languages:','languages',
			$langs,
			array_fill_keys(array_keys($langs),FALSE));
		$form1->addElement('mcb',$cbtest);
		
		$form1->addElement('submit',new Submit('action','submit','GO'));
			
		return $form1;
	}

	
}
?>
