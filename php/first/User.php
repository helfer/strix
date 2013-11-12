<?php
/**
 * @version 0.1
 * @brief User Object holding all methods associated with a user.
 * 
 * User Object. Does not handle Authentication!!!
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-23
 */
final class User extends DatabaseObject{
	
	protected $db_vars = array('username','password','first_name','last_name','co','street','zip','city','email','phone','mobile','phone2','sex','birthday','language_id','primary_usergroup_id','registration_date','creation_date','failed_attempts','num_logins','last_login','ip_log');
	
	protected $login = FALSE; //default login to indicate user is not logged in
	
	protected $id = 1; //!!!! DO NOT CHANGE THIS VALUE!!! //TODO: find a better solution for this
	protected $username = 'everybody_username';
	protected $password = 'pw_hash';
	protected $language_id = '1'; //TODO: add constraint!
	protected $primary_usergroup_id = 99999;
	protected $first_name = 'firstname';
	protected $last_name = 'lastname';
	protected $co = 'co';
	protected $street = 'street';
	protected $zip = 'zip';
	protected $city = 'city';
	protected $email = NULL;
	protected $phone = '';
	protected $mobile = '';
	protected $sex = '';
	protected $num_logins = '';
	protected $birthday = '0000-00-00';
	protected $creation_date = '0000-00-00';
	protected $registration_date = '0000-00-00';
	protected $last_login = '0000-00-00 00:00:00';
	//protected $last_activity = '0000-00-00 00:00:00';
	protected $ip_log = NULL; //logs time+ip for 512 varchars.
	protected $failed_attempts = '0'; //number of consecutive unsucessful pw-attempts.
	
	
	//--- cross variables
	//protected $primary_user_group_name = 'everybody';
	//protected $secondary_user_groups;
	
	//--- other variables
	
	public function getTableName()
	{
		return 'user';	
	}

	//should be private but can't because of DBobject
	public function __construct($vars = array()){
		if (!isset($vars['primary_usergroup_id']))
			$this->primary_usergroup_id = EVERYBODY_GROUP;
		
		if(isset($vars['id']) && $vars['id'] > 0)
			$this->login = TRUE;
			
		if(!isset($vars['num_logins']))
			$this->num_logins = 0;
		
		foreach($vars as $name => $var){ 
			$this->$name = $var;
			//$this->db_vars []= $name;
		}
	}
	
	
	//creates a new user and stores it in database
	public static function MakeNew($user_row)
	{
		return FALSE;
	}

	public static function LoadFromSession()
	{
		return self::LoadFromDbById($_SESSION['user_id']);
	}
	/*
	public static function LoadFromDbById($id)
	{
		$query = "SELECT * FROM user WHERE id='$id'";
		$obj_row = SqlQuery::getInstance()->singleRowQuery($query);
		return new User($obj_row);
	}*/
	
	public function ChangePassword($old_pw,$new_pw)
	{
		if (md5($old_pw) == $this->password)
			$this->__set('password',md5(new_pw));
		else
			error('incorrect password!');
	}
	
	/**
	 * add user to that group
	 */
	public function addToUsergroup($usergroup_id)
	{
		if(!isset($this->id) || $this->needs_wb)
			throw new Exception('Writeback required before adding to usergroup');
		
		$sql = SqlQuery::getInstance();
		$sql->insertQuery('user_in_group',array('user_id'=>$this->id,'usergroup_id'=>$usergroup_id));
		
	}
	
	/**
	 * remove user from that group
	 */
	public function removeFromUsergroup($usergroup_id)
	{
		if(!isset($this->id) || $this->needs_wb)
			throw new Exception('Writeback required before removing from usergroup');
			
		$sql = SqlQuery::getInstance();
		$sql->deleteQuery('user_in_group',array('user_id'=>$this->id,'usergroup_id'=>$usergroup_id));
	}
	
}

?>
