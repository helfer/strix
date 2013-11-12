<?php

//constants:
define('TOKEN_VALID',0);
define('E_TOKEN_MISSING',1);
define('E_TOKEN_INVALID',2);
define('E_TOKEN_EXPIRED',3);
define('E_TOKEN_USED',4);

/**
 * @version 0.1
 * @brief Handles POST input, may treat some GET input.
 * 
 * @todo make singleton!
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-23
 */
class InputHandler
{
	
	// $_SESSION[TOKEN_FIELD_NAME]			 	//holds all active tokens
	// $_SESSION[TOKEN_SERIAL_FIELD]		 	//associates all tokens to a request
	protected $activeTokenId 	= 	FALSE;		//indicates the token id used (if one was used)
	protected $activeTokenInfo	=	array();	//hold the information associated with the token used in the current request
	protected $postInput		 = 	array();	//holds the input of $_POST after filtering

	public function __construct()
	{
	}
	
	/** Process Http POST information.
	 * Process Http POST information, instantiate FormHandler Object,
	 * pass information to it and retrieve results
	 */
	public function processInput()
	{
		if( empty($_POST) )
			return;
		
		switch( $this->checkToken() )
		{
			case TOKEN_VALID: //accept input
				$this->activeTokenInfo = $this->getTokenInfo();

				$this->postInput = $_POST;

				//print_r( $this->activeTokenInfo );

				//autoprocess = the form will process itself
				if( $this->activeTokenInfo['target_type'] == 'login' )
				{
					///@todo this is very ugly. change it when form-autoprocessing is available
					/// and when login form is not stored as function in LoginContent.
					
					$_SESSION['user_id'] = scms_auth_user($this->postInput['username'],$this->postInput['password']);

					//part of ugly solution for redirects (start page)
					$GLOBALS['new_login'] = TRUE;
				}
				else if( $this->activeTokenInfo['target_type'] == 'logout' )
				{
					///@todo this is very ugly. change it when form-autoprocessing is available
					/// and when login form is not stored as function in LoginContent.
					
					$GLOBALS['RequestHandler']->cleanSession();
				}
				else if( $this->activeTokenInfo['target_type'] == 'db_form' )
				{
					print_r($_POST);
					
					$form_id = $this->activeTokenInfo['vector']['dbform_id'];
					
					$form = $GLOBALS['FormHandler']->getDbForm( $form_id , $this->activeTokenInfo['vector'] );
					$form->populate($this->postInput);
					
					///@todo not here, just testing!
					$GLOBALS['FormHandler']->storeFormValues($form_id);
					//echo $form->getHtml(1818);	
				}
					
				
				break;
			case E_TOKEN_USED:
				notice('token was used: ' . date('H:i:s',$_SESSION[TOKEN_USED_FIELD][$_POST[TOKEN_FIELD_NAME]]) );
				break;
			case E_TOKEN_INVALID:
				notice('form token is invalid');
				break;
			case E_TOKEN_EXPIRED:
				notice('the token has expired');
				break;
			case E_TOKEN_MISSING:
				notice('no form token!');	
				break;
			default:
				throw new Exception('Token Input Error');

		}
		//security measure: allowing no direct access to $_POST	
		$_POST = array();
		

	}
	
	/** checks a token for its validity.
	 * @return array(result,ERRNO)
	 */
	protected function checkToken()
	{
		if( empty($_POST[TOKEN_FIELD_NAME]) )
			return E_TOKEN_MISSING;
			
		$token = $_POST[TOKEN_FIELD_NAME];
		
		if( isset($_SESSION[TOKEN_USED_FIELD][$token]) )
			return E_TOKEN_USED;
			
		if( !isset($_SESSION[TOKEN_FIELD_NAME][$token]) )
			return E_TOKEN_INVALID;
			
		$info = $_SESSION[TOKEN_FIELD_NAME][$token];
			
		if( !empty($info['expires']) && $info['expires'] > time() )
			return E_TOKEN_EXPIRED;
			
		return TOKEN_VALID;
	}
	
	/** Returns the information associated with the token.
	 * 
	 * And marks the serial (request_id) as used, so tokens will get deleted.
	 * 
	 */
	protected function getTokenInfo()
	{
		$token_id = $_POST[TOKEN_FIELD_NAME];
		
		$info = $_SESSION[TOKEN_FIELD_NAME][$token_id];
		
		$this->activeTokenId = $token_id;
		
		return $info;
	}
	
	/** Discards the tokens that were used.
	 * 
	 * Discards all tokens associated with the same request as the token just used.
	 * 
	 */
	public function discardUsedTokens()
	{
		$num = 0;
		
		if( !empty( $this->activeTokenId) )
		{
			$serial = $this->activeTokenInfo['request_id'];
			
			$_SESSION[TOKEN_USED_FIELD][$this->activeTokenId] = time();
			
			$num = 	sizeof($_SESSION[TOKEN_SERIAL_FIELD][$serial]);
			
			foreach($_SESSION[TOKEN_SERIAL_FIELD][$serial] as $token_id)
				unset($_SESSION[TOKEN_FIELD_NAME][$token_id]);
				
			unset($_SESSION[TOKEN_SERIAL_FIELD][$serial]);
		}
		
		return $num;
	}
	
	
	/* Makes a token and returns the id to caller.
	 * 
	 * Function must be used for any input from forms, as input without
	 * a valid token will be discarded!
	 * 
	 * @param form_name Name to find the corresponding form
	 * @param owner_id Id of form caller (owner)
	 * @param expiry Total time allowed to complete the form
	 * @param vector Initialization vector of the form
	 * 
	 * @return token id
	 */
	public function makeToken($form_name, $target_type, $owner_id, $expiry, $vector)
	{
		$info = array(
			'form_name'=>$form_name,
			'target_type'=>$target_type,
			'owner_id'=>$owner_id,
			'vector'=>$vector,
			'time'=>time(),
			'expires'=>$expiry,
			'request_id'=>$GLOBALS['request_id']);
			
		$new_id = sha1($info['form_name'] . $info['owner_id'] . $info['request_id'] . $info['time'] . SHA_1_SALT);
			
		$_SESSION[TOKEN_FIELD_NAME][$new_id] = $info;
		$_SESSION[TOKEN_SERIAL_FIELD][$info['request_id']] []= $new_id;
		
		return $new_id;	
	} 
	
	
/************************************************************************/
/*				FORM HANDLING PART										*/	
/************************************************************************/

	/*
	 * each form is stored in a separate definition file or in the database
	 * all that's needed are the arguments for creating the form.
	 * 
	 * Form Definition file contents:
	 * 
	 * File Form:
	 * a function by the name of _FORM_<form_name_here> which returns the finished form
	 * it must accept the initialization vector as the one and only argument.
	 * ?? should the creator function not use any other variables in session etc??
	 * 
	 * Database Form:
	 * no initialization vectors possible here, only language and value_identifier to be
	 * able to populate the form from previous values stored in the database.
	 * 
	 * It is preferable to use database forms for all addon content, as it can be modified that way.
	 * It is also preferable to use database forms when input is to be stored for more than the duration of a session.


	/**
	 * 
	 * @param form_name if name contains db_<name>, then the form will be fetched from the database.
	 * If additionally the name is numeric, it will be interpreted as the id of the form.
	 */
	public function getForm($form_name, $vector = NULL, $caller_id = NULL)
	{
		//look up form and create a NEW instance of it.
		
		//if caller id = 0, simply return a copy of the form with default values
		
		//if caller id + form name match input token, copy + populate form before returning it [different reference]
	}
	
	/** Checks if a pairing of form_name + caller_id has input
	 * 
	 * 
	 */
	public function hasInput($form_name,$caller_id)
	{
		
	}
	
	/** Returns input for a form - caller_id couple if there is some.
	 * 
	 * @todo eventually, this function should be deleted
	 */
	public function getFormInput($form_name, $caller_id)
	{
		if( empty($this->postInput) )
			return NULL;
			
		if( $this->activeTokenInfo['form_name'] == $form_name && $this->activeTokenInfo['owner_id'] == $caller_id )
			return $this->postInput;
		else
			return NULL;
	}
	
	/**
	 * Takes the values straight from input + token_info
	 */
	public function populateForm($form,$caller_id)
	{
		
	}
	
	
	/**
	 * @todo delete this function asap (implement same functionality differently!)
	 */
	public function getInputInfo()
	{
		return $this->activeTokenInfo;	
	}
	


}

?>
