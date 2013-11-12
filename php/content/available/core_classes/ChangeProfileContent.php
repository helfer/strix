<?php
/*
 * class name: change_profile
 * 
 * 
 */
class ChangeProfileContent extends Content{
	

	//protected $tag_keys = array('chgVmodeEdit','chgVmodeNormal','username','password','login','group','logout');
	//protected $tags = array('edit','normal','username','password','login','group','logout');
	
	protected $mConfKeys = array('dimension_lbl','dimension_input');
	protected $mConfValues = array('8em','20em');

	
	/*
	 * additional members:
	 */
	
	protected $process_msg = '';
	
	
	
	/*
	 * functions:
	 */
	
	public function display()
	{
		$xhtml = '';
		
		$xhtml .= $this->process_msg;
		
		$xhtml .= $this->getForm('change_profile_form')->getHtml($this->id);
		return $xhtml;
	}
	
	
	public function process_change_profile_form(){
		
		$form = $this->getActivatedForm();
		
		if($form->validate())
		{
			$this->process_msg = 'IS VALID';
			
			$vals = $form->getChangedElementValues();
			unset($vals['submit']);
			
			//notice($vals);
			//update information in database
			foreach($vals as $k=>$v)
			{
				$oldv[$k] = $GLOBALS['user']->__get($k);
				$GLOBALS['user']->__set($k,$v);
			}
			
			//$sql = SqlQuery::getInstance()->updateQuery('user',$vals,array('id'=>$GLOBALS['user']->id),1);
			$sql = $GLOBALS['user']->store();
			//make user object identical to the one in database
				
			if($sql){
				$this->process_msg = '<b style="color:lime">INFORMATION UPDATED!</b>';
				$user = $GLOBALS['user'];
				
				ob_start();
					print_r($oldv);
					echo '\n has been changed to: \n';
					print_r($vals);
				$body = nl2br(htmlentities(ob_get_clean()));
				
				sendSBOmail(ADMIN_EMAIL,'Profile changed for '.$user->username,$body);
			/*
				$mail_message = 'Old Profile of '.$user->login.":\n\n".v_implode("\n",$user->profile())."\n\nNew Profile:\n\n".v_implode("\n",$vals);
				//echo $mail_message;
				//print_r($user->profile());
			
				if(mail(ADMIN_EMAIL,'sbo09 profile update '.$user->login,$mail_message))
					error('notification sent!');
				else
					error('sendmail error');
			
				$user->refresh();
			*/
			}else
				$this->process_msg = 'DATABASE ERROR ON UPDATE!';
		}else
			$this->process_msg = 'FORM IS INVALID';

	}

	
	
	protected function change_profile_form($vector){
		
		//First name
		//Last name
		//street
		//city
		//PLZ
		//email
		//tel
		//mobile
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$newForm->setVector($vector);
		
		//print_r($newForm);

		
		$fname = new Input('First name: ','text','first_name','',array('size'=>20,'disabled'));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('first_name',$fname);
		
		$lname = new Input('Last name: ','text','last_name','',array('size'=>20,'disabled'));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('last_name',$lname);
		
		$co = new Input('Zusatz (c/o) (if applicable): ','text','co','',array());
		$co->addRestriction(new StrlenRestriction(0,64));
		$newForm->addElement('co',$co);

		
		$street = new Input('Street: ','text','street','',array());
		$street->addRestriction(new StrlenRestriction(1,128));
		$newForm->addElement('street',$street);
		
		$zip = new Input('PLZ: ','text','zip','',array('size'=>4));
		$zip->addRestriction(new IsNumericRestriction());
		$zip->addRestriction(new InRangeRestriction(1000,9999));
		$zip->addRestriction(new StrlenRestriction(4,4));
		$newForm->addElement('zip',$zip);
		
		$city = new Input('City: ','text','city','',array('size'=>20));
		$city->addRestriction(new StrlenRestriction(1,32));
		$newForm->addElement('city',$city);
		
		$em = new Input('e-mail: ','text','email','',array('size'=>25));
		$em->addRestriction(new isEmailRestriction());
		$newForm->addElement('email',$em);
		
		$phone = new Input('Tel: ','text','phone','',array('size'=>20));
		$zip->addRestriction(new IsNumericRestriction());
		$phone->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('phone',$phone);
		
		$mobile = new Input('Mobile: ','text','mobile','',array('size'=>20));
		$zip->addRestriction(new IsNumericRestriction());
		$mobile->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('mobile',$mobile);
		
		$phone2 = new Input('Tel2: ','text','phone2','',array('size'=>20));
		//$phone2->addRestriction(new IsNumericRestriction());
		$phone2->addRestriction(new StrlenRestriction(0,20));
		$newForm->addElement('phone2',$phone2);
		
		$bday = new DateInput('Birthday: ','birthday','1900-12-31','1940-01-01',date('Y-m-d'));
		$newForm->addElement('birthday',$bday);
		
		$sex = new Select('Sex:','sex',array('F'=>'F','M'=>'M'));
		$newForm->addElement('sex',$sex);
		
		$newForm->addElement('submit_addr',new Submit('submit_addr','Submit changes'));

		$user = SqlQuery::getInstance()->singleRowQuery("SELECT first_name, last_name, street, co, city, zip, email, `phone`, `mobile` FROM user WHERE id='".$GLOBALS['user']->id."'");
		$newForm->populate($GLOBALS['user']->getTableValues());

		//print_r($GLOBALS['user']->getTableValues());

		return $newForm;
	}
	

}
?>
