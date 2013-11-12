<?php

final class LoginContent extends Content{
	
	protected $tag_keys = array('chgVmodeEdit','chgVmodeNormal','username','password','login','group','logout','forgot_pw','message');
	protected $tags = array('edit','normal','username','password','login','group','logout','forgot password?','logged in as');
	
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
				
					
				$vmode_switch = '<p><a href="?vmode='.$mode_id.'">'.$mode_text.'</a></p>';
				
				//TODO: JUST A TEST for conf.
				//print_r($this->conf);
				switch($this->mConfValues['spaces'])
				{
					case'3': break;
					
					case '2':
						$vmode_switch .='<br />'; break;
					case '1':
						$vmode_switch .='<br />'; break;
					default:	
						$vmode_switch .= ''; break;
				}
				$vmode_switch .= str_repeat('<br />',$this->mConfValues['spaces']);
				
			}	
			$user_info = '<p><b>'.$tag['message'].'</b> <span class="problem">'.$GLOBALS['user']->__get('username').'</span></p>';

			$xhtml='<br/><div id="loginbox">'.$user_info.$this->logout_form()->getHtml().'<br/>'.$vmode_switch.'</div>';
						
			return $xhtml;
		} else {
			
			$notice = isset($GLOBALS['login_notice'])? '<p style="color:magenta">'.$GLOBALS['login_notice'].'</p>' : '';
	
			$forgot_pw_link = empty($notice) ? 
					'' 
				: 
				
					'<br /><a href="http://' . $GLOBALS['request_host'] . RESET_PWD_PAGE . '" style="color:yellow">'.$this->tags['forgot_pw'].'</a>'
				;
	
			return	'<div id="loginbox">
						'.$notice.$this->login_form($this->tags['username'],$this->tags['password'],$this->tags['login'])->getHtml().'
					'.$forgot_pw_link.'
					</div>';
		}
	}
	
	//declared static so it can be called from User class without instance of Login.
	public static function login_form($u_tag = 'u',$p_tag = 'p',$l_tag = 'l')
	{
		$form = new SimpleForm('login_form','',array(),1818,'login'); //1818 is just a nonsense number.
		
		//testing new constructor:
		$c_args = array('label' => $u_tag . '<br />',
						'type' => 'text',
						'name' => 'username',
						'value' => '',
						'extras' => array('alt'=>'username'));
		
		$form->addElement('user',new Input($c_args));
		
		//testing new constructor:
		$p_args = array('label' => '<br />' . $p_tag . '<br />',
						'type' => 'password',
						'name' => 'password',
						'value' => '',
						'extras' => array('alt'=>'password'));
		
		$form->addElement('pass',new Input($p_args));	
		$form->addElement('sub',new Submit('login',$l_tag));
		
		return $form;
	}
	
	public function logout_form()
	{
		$form = new SimpleForm('logout_form','',array(),1818,'logout'); //again, 1818 is just any nonsense number
		$form->addElement('sub',new Submit('logout',$this->tags['logout']));
	
		return $form;	
	}

}

?>
