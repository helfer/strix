<?php

final class LogoutContent extends Content{
	
	protected $tag_keys = array('chgVmodeEdit','chgVmodeNormal','message','logout');
	protected $tags = array('edit','normal','logged in as','logout');
	
	protected $mConfKeys = array('spaces');
	protected $mConfValues = array('1');

	public function display(){

		$tag = $this->tags;
		
		if($GLOBALS['user']->__get('id') != ANONYMOUS_USER_ID){
			
			$vmode_switch = '';
			if($GLOBALS['page']->checkPermission(WRITE_PERMISSION))
			{		
				
				if (!isset($_GET['vmode']) || $_GET['vmode'] == VMODE_NORMAL)
				{
					$mode_id = VMODE_MODIFY;
					$mode_text = $tag['chgVmodeEdit'];
				}
				else
				{
						$mode_id = VMODE_NORMAL;
						$mode_text = $tag['chgVmodeNormal'];	
				}
				
					
				$vmode_switch = '<p>Change to <a href="?vmode='.$mode_id.'">'.$mode_text.'</a></p>';
				
			}	
			$user_info = '<p><b>'.$tag['message'].'</b> <span class="problem">'.$GLOBALS['user']->__get('first_name').' '.$GLOBALS['user']->__get('last_name').'</span></p>';
					
			$xhtml='<br/><div id="logoutbox">'.$user_info.$this->logoutcontent_logout_form()->getHtml().'<br/>'.$vmode_switch.'</div>';
			return $xhtml;
		} 
	}
	
	public function logoutcontent_logout_form()
	{
		$form = new SimpleForm('logout_form','',array(),1818,'logout'); //again, 1818 is just any nonsense number
		$form->addElement('sub',new Submit('logout',$this->tags['logout']));
	
		return $form;	
	}

}

?>
