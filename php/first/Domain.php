<?php
abstract class Domain
{
	protected $id;
	
	protected $mResponseSerial = NULL;
	
	public function __construct($id)
	{
		$this->id = $id;
	}
	
	//every domain must be able to process requests
	protected abstract function serveContent($uri);
	
	
	
	
	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	/*
	 *  Check Input stuff...
	 * **************************************************************/
	
	//returns array(form name, target_type, target_id, input) where input= rest of POST
	protected function check_input()
	{
		$post = $_POST;
		
		/*
		//for safety reasons, don't allow any direct user input except through GET
		$_REQUEST = array();
		//$_GET = array();
		$_POST = array();
		//print_r($post);
		*/
		
		//response serial is a unique number used to identify all tokens of a same Response
		$this->getResponseSerial();
					
		if (!isset($post[TOKEN_VARIABLE_NAME]))
			return array(NULL,NULL,NULL,NULL,NULL);
			
		$token = $post[TOKEN_VARIABLE_NAME];
		unset($post[TOKEN_VARIABLE_NAME]);
			
		debug('token= '.$token);
		//notice('ARRAY=');
		//print_r($_SESSION['UID_tokens']);
		
		if(!$this->valid_token($token))
		{
			notice('Token is invalid!');
			return array(NULL,NULL,NULL,NULL,NULL);	
		}
		else
			debug('Token is valid');
		
		list($form_name,$target_type,$target_id,$initial_vector) = $this->use_token($token);
		
		//TODO: when using multiple tabs, this is not ideal!!!
		//$_SESSION['UID_tokens'] = array();

		return array($form_name,$target_type,$target_id,$initial_vector,$post);
		
	}
	
	
	
	//page serial is a random serial generated for each Request and used to store token
	protected function getResponseSerial(){
		
		if(empty($this->mResponseSerial))
		{				
			$this->mResponseSerial = substr(sha1(microtime() . rand(100,999)),0,LEN_PG_SERIAL);
			$_SESSION['UID_tokens'][$this->mResponseSerial] = array();
			
			debug('created serial: '.$this->mResponseSerial);

			//limit size to MAX_PAGE_TOKENS, unset first one if needed.
			$size = sizeof($_SESSION['UID_tokens']);
			if ($size >= MAX_PAGE_TOKENS)
			{
				$_SESSION['UID_tokens'] = array_slice($_SESSION['UID_tokens'],($size - MAX_PAGE_TOKENS + 1),$size -1,TRUE);
				debug('token array reduced!');	
			}
			
		}
		
		return $this->mResponseSerial;
		
	}
	
	
	protected function split_token($token)
	{
		$serial = substr($token,0,LEN_PG_SERIAL); //the request serial
		$token_id = substr($token,LEN_PG_SERIAL); //the token id
		return array($serial,$token_id);
	}
	
	
	
	protected function valid_token($token)
	{
		list($serial,$token_id) = $this->split_token($token);
		return isset($_SESSION['UID_tokens'][$serial][$token_id]);	
	}
	
	
	
	protected function use_token($token)
	{
		list($serial,$token_id) = $this->split_token($token);
		
		$ret = $_SESSION['UID_tokens'][$serial][$token_id];
		unset($_SESSION['UID_tokens'][$serial]); //only one token can be used per request.
		
		debug('token used!');
		
		return $ret;
	}
	
	
	
	//method used by forms to get a token they can then use.
	public function get_token($form_name,$target_type,$target_id,$initialVector = array())
	{	
		$token_array =& $_SESSION['UID_tokens'][$this->getResponseSerial()];
		debug('token array');
		debug($token_array);
		
		
		$token_id = sha1($form_name.$target_type.$target_id.SHA_1_SALT);
		$token = $this->mResponseSerial.$token_id; //the token is made up of two parts!
		
		if ($this->valid_token($token))
			debug('token already in use!'); //some forms are displayed several times. they only use one token.

		$token_array[$token_id] = array($form_name,$target_type,$target_id,$initialVector);
		return $token;
	}
		

}
?>
