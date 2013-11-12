<?php
/*
 * class name: template_content_class ...
 * 
 * 
 */

//TODO: replace jonas_helfer@yahoo.de by ADMIN_EMAIL

class ResetPasswordContent extends Content{

	protected $tag_keys = array('submit','chgVmodeNormal','username','password','login','group','logout');
	protected $tags = array('submit','normal','username','password','login','group','logout');
	
	protected $mConfKeys = array('dimension_lbl','dimension_input');
	protected $mConfValues = array('12em','30em');
	
	
	
	
	/*
	other members:
	*/
	protected $process_msg = '';
	

	
	public function display(){
		
		$xhtml = '';
		$xhtml .= $this->process_msg;
		/*TODO: find a general solution;
		if(isset($this->mActivatedForm) && $this->mActivatedForm->getName() == 'chg_pw_form')
		{
			$process_method = 'process_'.$this->mActivatedForm->getName();
			$xhtml .= $this->$process_method();	
		}*/
		
		$xhtml .= $this->getForm('reset_pw_form')->getHtml($this->id);
		
		return $xhtml;
	}
	
	public function edit(){
		$xhtml = '';
		
		return $xhtml;
	}
	
	public function process_reset_pw_form(){

		//TODO...
		//needs phpmailer-work...
		$form = $this->mActivatedForm;
		
		$form->stayAlive();
		
		if($form->validate()){
			
			$values = $form->getElementValues();
			//print_r($values);
			$em = $values['email'];
			$zip = $values['zip'];
			$fn = $values['first_name'];
			$ln = $values['last_name'];
				
				
			$sql = SqlQuery::getInstance();
			
			//TODO: must cross check if email-pw is activated for ALL groups the user belongs to!!!
			$query = "SELECT id, username, mobile FROM `user` WHERE `first_name`='$fn' AND `last_name`='$ln'	AND `zip`='$zip' AND (`email`='$em' OR `email2`='$em')";
			$res = $sql->singleRowQuery($query);
			
			if(!empty($res) && $res['id'] > 99) //entry confirmed, send password!
			{
				//generate new password
				$newpw = generateRandomPassword(8);
				$username = $res['username'];
				
				//update password in database
				$sql->updateQuery('user',array('password'=>md5($newpw)),array('id'=>$res['id']));	

				
				//TODO: write a mailer class that does this for you.	
				$mail_message = "Hallo $fn,\n\nDein neues Passwort ist '$newpw', dein username ist '$username'";
				//$this->process_msg =  $mail_message;
				if(sendSBOmail('jonas_helfer@yahoo.de','sbo09 pw reset for user '.$res['username'],$mail_message)
					&& sendSBOmail($em,'Neues Passwort',$mail_message) )
				{
					$this->process_msg .= '<b style="color:green">Neues Passwort gesendet an: '.$form->getElementValue('email').'</b>';
				}
				else
					$this->process_msg .= '<b style="color:green">Beim senden des Passworts ist ein fehler aufgetreten. Wende Dich bitte an den Administrator (jonas@ibosuisse.ch), um ein neues Passwort zu erhalten</b>';
				
				$form->reset();	
				
			}
			else if(!empty($res))
			{ //not allowed to reset pw.
				$mobile = substr($res['mobile'],0,strlen($res['mobile'])-2).'**';
				$this->process_msg .= '<b style="color:red">Sorry, Du darfst dein Passwort nicht per e-mail zurücksetzen! Bitte schicke Jonas (079 578 84 16) eine SMS, damit er Dir ein neues Passwort geben kann. Passwörter werden vorsichtshalber nur an die Nummer verschickt, die Du auf der Liste eingetragen hast ('.$mobile.')!</b>';		
				$msg = 'reset attempt for '.$res['username'].' ,Natel = '.$res['mobile'];
				sendSBOmail('jonas_helfer@yahoo.de',$msg,$msg);	
				
			}
			else //no such entry
				$this->process_msg .= '<b style="color:red">Kein Eintrag mit diesen Angaben gefunden!</b>';

			
			//$this->process_msg .= '<b style="color:red">Kein Eintrag mit diesen Angaben gefunden!</b>';
		}else
			$this->process_msg = 'form contains errors';

		
	}
	
	protected function reset_pw_form($vector){
		
		$form = new TabularForm(__METHOD__);
		$form->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$form->setVector($vector);

		$form->addElement('info',
			new HtmlElement('Um dir ein neues Passwort zu schicken, brauchen wir deinen Namen, Vornamen, e-mail Adresse sowie die Postleitzahl, die Du uns angegeben hast.<br /><br />'));
		
		$f_name = new TextInput('Vorname: ','first_name','');
		$f_name->addRestriction(new StrlenRestriction(1,64));
		
		
		$l_name = new TextInput('Nachname: ','last_name','');
		$l_name->addRestriction(new StrlenRestriction(1,64));
		
		
		$em = new Input('e-mail: ','text','email','',array('size'=>25));
		$em->addRestriction(new isEmailRestriction());
		$em->addRestriction(new StrlenRestriction(5,128)); //just to be sure...


		$zip = new Input('PLZ: ','text','plz','',array('size'=>4));
		$zip->addRestriction(new IsNumericRestriction());
		$zip->addRestriction(new InRangeRestriction(1000,9999));
		
		
		
		$form->addElement('first_name',$f_name);
		$form->addElement('last_name',$l_name);
		$form->addElement('email',$em);
		$form->addElement('zip',$zip);
		
		//$form-addElement('info2',new HtmlElement('

		$form->addElement('submit',new Submit('submit',$this->tags['submit']));
	
		return $form;	
		
	}

}
?>
