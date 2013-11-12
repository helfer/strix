<?php
/*
 *      usermanagement.php
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

class UserManagement extends Content{
	
	protected $mConfKeys = array('dimension_lbl','dimension_input');
	protected $mConfValues = array('8em','20em');
	
	protected $mProcMsg = '';
	
	public function display(){
		$xhtml = '';
		
		$xhtml .= '<h2>Add new user</h2>';
		
		$xhtml .= $this->mProcMsg;
		
		$form = $this->getForm('change_profile_form');
		
		$xhtml .= $form->getHtml($this->id);
		
		return $xhtml;
	}
	
	public function process_change_profile_form(){
		$frm = $this->getForm('change_profile_form');
		
		if(!$frm->validate())
			return false;
			
		$values = $frm->getElementValues();
		unset($values['submit_addr']);
		
		$usergroup = $values['primary_usergroup_id'];	
		
		$sql = SqlQuery::getInstance();
		
		
		//GENERATE RANDOM PASSWORD:
		$pwd = generateRandomPassword(9);
		$values['password'] = md5($pwd);
		
		//ADD NECESSARY COLUMNS (non - null values not allowed)
		$values['creation_date'] = date('Y-m-d');
		
		$sql->start_transaction(); //------------------------------------
		
		$iid = $sql->insertQuery('user',$values);
		
		
		$sql->insertQuery('user_in_group',array('user_id'=>$iid,'usergroup_id'=>'1'));
		
		if($usergroup != 1)
			$sql->insertQuery('user_in_group',array('user_id'=>$iid,'usergroup_id'=>$usergroup));
			
		$ok = $sql->end_transaction(); //----------------------------------
		
		if($ok){
			$this->mProcMsg = 'USER INSERTED<br />user: '.$values['username'].'<br /> pass='.$pwd.'<br />';
			$frm->reset();
		}
		else
			$this->mProcMsg = '<p class="error">no user could be created</p>';
	}
	

	protected function change_profile_form($vector){
		
		//First name
		//Last name
		//street
		//city
		//PLZ
		//email
		//tel
		//+ tel2
		//mobile
		//+ birthday		
		

		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$newForm->setVector($vector);
		
		//print_r($newForm);

		$uname = new Input('username: ','text','username','',array('size'=>20));
		$uname->addRestriction(new StrlenRestriction(2,64));
		$newForm->addElement('username',$uname);
		
		$fname = new Input('First name: ','text','first_name','',array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('first_name',$fname);
		
		$lname = new Input('Last name: ','text','last_name','',array('size'=>20));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('last_name',$lname);
		
		$query_lng = array('query'=>"SELECT * FROM `language`",'keys'=>array('id'),'values'=>array('name'));
		$language = new DataSelect('Language: ','language_id',$query_lng,1);
		$newForm->addElement('language_id',$language);
		
		$co = new Input('Zusatz (c/o) (if applicable): ','text','co','',array());
		$co->addRestriction(new StrlenRestriction(0,64));
		$newForm->addElement('co',$co);

		
		$street = new Input('Street: ','text','street','',array());
		$street->addRestriction(new StrlenRestriction(0,128));
		$newForm->addElement('street',$street);
		
		$zip = new Input('PLZ: ','text','zip','',array('size'=>4));
		//$zip->addRestriction(new IsNumericRestriction());
		//$zip->addRestriction(new InRangeRestriction(1000,9999));
		$zip->addRestriction(new StrlenRestriction(0,4));
		$newForm->addElement('zip',$zip);
		
		$city = new Input('City: ','text','city','',array('size'=>20));
		$city->addRestriction(new StrlenRestriction(0,32));
		$newForm->addElement('city',$city);
		
		$em = new Input('e-mail: ','text','email','',array('size'=>25));
		$em->addRestriction(new isEmailRestriction());
		$newForm->addElement('email',$em);
		
		$phone = new Input('Tel: ','text','phone','',array('size'=>20));
		//$phone->addRestriction(new IsNumericRestriction());
		$phone->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('phone',$phone);
		
		$mobile = new Input('Mobile: ','text','mobile','',array('size'=>20));
		//$mobile->addRestriction(new IsNumericRestriction());
		$mobile->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('mobile',$mobile);
		
		$phone2 = new Input('Tel2: ','text','phone2','',array('size'=>20));
		//$phone2->addRestriction(new IsNumericRestriction());
		$phone2->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('phone2',$phone2);
		
		$bday = new DateInput('Birthday: ','bday','1900-12-31','1940-01-01',date('Y-m-d'));
		$newForm->addElement('birthday',$bday);
		
		$sex = new Select('Sex:','sex',array('F'=>'F','M'=>'M'));
		$newForm->addElement('sex',$sex);
		
		
		$grp_query = array('query'=>"SELECT * FROM `usergroup` WHERE id <> '".ADMIN_GROUP."'",'keys'=>array('id'),'values'=>array('name'));
		$usrgrp = new DataSelect('Primary usergroup:','primary_usergroup_id',$grp_query,EVERYBODY_GROUP);
		$newForm->addElement('primary_usergroup_id',$usrgrp);
		
		
		
		$newForm->addElement('submit_addr',new Submit('submit_addr','ADD USER'));
		
		
		


		//ONLY FOR EXISTING USERS!
		//$user = SqlQuery::getInstance()->singleRowQuery("SELECT first_name, last_name, street, co, city, zip, email, `phone`, `mobile` FROM user WHERE id='".$_SESSION['user']->id."'");
		//$newForm->populate($_SESSION['user']->getTableValues());

		//print_r($_SESSION['user']->getTableValues());

		return $newForm;
	}	
	
}


?>
