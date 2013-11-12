<?php
/*
 * class name: template_content_class ...
 * 
 * 
 */
class ChangePasswordContent extends Content{

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
		
		$xhtml .= $this->getForm('chg_pw_form')->getHtml($this->id);
		
		return $xhtml;
	}
	
	public function edit(){
		$xhtml = '';
		
		return $xhtml;
	}
	
	public function process_chg_pw_form(){

		//TODO...
		$form = $this->mActivatedForm;
		
		$form->stayAlive();
		
		if($form->validate()){
			$new_pw = $form->getElementValue('newpw');
			
			$old_pw_md5 = $GLOBALS['user']->__get('password');
			$GLOBALS['user']->__set('password',md5($new_pw));
			$res = $GLOBALS['user']->store();
			
			if($res){
				$this->process_msg = '<b style="color:green">Password changed!</b>';
				$form->reset();
				
				sendSBOmail(ADMIN_EMAIL,'Password changed for '.$GLOBALS['user']->username,'Strlen='.strlen($new_pw));
				
			}else
			{
				$this->process_msg = 'Database Error. Could not change pw!';
				$GLOBALS['user']->__set('password',$old_pw_md5); //rollback ...
			}
		}else
			$this->process_msg = 'form contains errors';

		
	}
	
	protected function chg_pw_form($vector){
		
		$form = new TabularForm(__METHOD__);
		$form->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$form->setVector($vector);

		$form->addElement('info',new HtmlElement('The password must contain lower- and uppercase letters as well as numbers and must be between 8 and 64 characters long!<br /><br />'));
		
		$pw_old = new Input('Old Password:   &nbsp;&nbsp;','password','pw_old','',array('size'=>20));
		$pw_old->addRestriction(new IsUserPassword());
		$form->addElement('oldpw',$pw_old);

		$pw_new = new Input('New Password:   &nbsp;','password','pw_new1','',array('size'=>20));
		$pw_new->addRestriction(new IsDecentPassword());
		$form->addElement('newpw',$pw_new);
		
		$pw_new2 = new Input('New Password:   &nbsp;','password','pw_new2','',array('size'=>20));
		$pw_new2->addRestriction(new SameAsRestriction($pw_new));
		$form->addElement('newpw2',$pw_new2);

		$form->addElement('submit',new Submit('submit',$this->tags['submit']));
	
		return $form;	
		
	}

}
?>
