<?php
/*
 *      Subscriber.php
 * 
 * 		Newsletter subscriber
 *      
 *      Copyright 2009 Jonas Helfer <jonas.helfer@epfl.ch>
 */
include_once('newsletter.php');

class NewsletterSubscriber extends DatabaseObject
{
	protected $db_vars = array('id','secret','user_id','anrede','first_name','last_name','email','language_id','joined','valid');
	
	protected $id = NULL;
	protected $secret = NULL;
	protected $user_id = NULL;
	
	protected $anrede = NULL;
	protected $first_name;
	protected $last_name;
	protected $email;
	protected $language_id;
	protected $joined;
	protected $valid = 0;
	
	
	/************/
	//pseudo members (cached from DB)
	
	protected $mSubscriptions = NULL;
	protected $mNonSubscriptions = NULL;
	
	public function getTableName(){return SUBSCRIBER_TABLE;}
	
	/** Loads subscriber from Db by his secret.
	 * 
	 */
	public function loadFromDbBySecret($secret)
	{
		mysql_real_escape_string($secret);
		$vars = SqlQuery::getInstance()->singleRowQuery("SELECT * FROM " . $this->getTableName() . " WHERE `secret`= '$secret' ");
		if(empty($vars))
			return FALSE;
			//print_r($vars);
		$this->LoadFromArray($vars);
		
		return TRUE;
	}
	
	/** Tries 10 times to create a unique secret.
	 * 
	 */
	public static function makeSecret()
	{
		$duplicate = TRUE;
		$i = 0;
		while($duplicate && $i < 10)
		{
			$secret = sha1(rand(0,100).microtime(TRUE));
			$ret = SqlQuery::getInstance()->singleRowQuery("SELECT 1 FROM " . SUBSCRIBER_TABLE . " WHERE `secret`='$secret' ");
			
			if ($ret)	
				$duplicate = TRUE;
			else
				$duplicate = FALSE;
			$i++;
		}
		
		if($i == 10)
			return FALSE;
		else
			return $secret;
	}
		
	/** Returns list of Newsletter to which Subscriber is subscribed.
	 * 
	 */
	public function getSubscriptions()
	{
		$query = "SELECT nl.* as sub FROM " . 
				NEWSLETTER_TABLE . " nl 
				JOIN " . NEWSLETTER_PERMISSION_TABLE . " p ON p.newsletter_id = nl.id
				JOIN  " . SUBSCRIPTION_TABLE . " st ON st.newsletter_id = nl.id 
				JOIN " . SUBSCRIBER_TABLE . " s ON s.id = st.subscriber_id
				WHERE 
					s.id = {$this->id}";
		$subscriptions = SqlQuery::getInstance()->simpleQuery($query);
		
		return $subscriptions;
	}
	

	
	
	
	/** Returns newsletters the Subsciber is not subscribed to.
	 * 
	 * @ param all If all=false, only the newsletters to which the current session's user has right to see are returned.
	 */
	public function getNonsubscriptions($all = FALSE)
	{
		$query = "SELECT nl.* FROM " . 
				NEWSLETTER_TABLE ." nl 
				WHERE
					nl.id NOT IN (
						SELECT newsletter_id
						FROM " . SUBSCRIPTION_TABLE . "
						WHERE subscriber_id = '{$this->id}'
					)";
		$nonsub = SqlQuery::getInstance()->simpleQuery($query);
		return $nonsub;
	}
	
	
	/** Subscribes Subscriber to the newsletter with that id.
	 * 
	 */
	public function subscribe($newsletter_id)
	{
		$nl = new Newsletter();
		$nl->loadFromDbById($newsletter_id);
		$nl->addSubscriber($this->id);
	}
	
}


?>
