<?php
/*
 *      usergroup.php
 *      
 *      Copyright 2009 user007 <user007@u007>
 */

define('SUBSCRIBER_TABLE','IBO_newsletter_subscriber');
define('NEWSLETTER_TABLE','IBO_newsletter');
define('SUBSCRIPTION_TABLE','IBO_newsletter_subscription');
define('NEWSLETTER_PERMISSION_TABLE','IBO_newsletter_permission');

class Newsletter extends DatabaseObject
{
	protected $db_vars = array('id','name','description');
	protected $fragment_vars = array('title','short');
	
	protected $id = NULL;
	protected $name = '';
	protected $description = '';
	
	protected $title = '';
	protected $short = '';
	
	public function getTableName(){return 'IBO_newsletter';}
	
	
	
	
	
	public static function listAllNewsletters()
	{
		//select all newsletters to which user has read permission
		$query = "SELECT n.*,nf.* 
					FROM `IBO_newsletter` n
					JOIN `IBO_newsletter_fragment` nf ON n.`id` = nf.`newsletter_id`
					JOIN `IBO_newsletter_permission` np ON n.`id` = np.`newsletter_id`
					JOIN `user_in_group` uig ON uig.`usergroup_id` = np.`usergroup_id`
					WHERE 
						np.`permission` = 1
						AND nf.language_id = {$_SESSION['language_id']}
						AND uig.`user_id` = {$_SESSION['user_id']}";
						
		$ary = SqlQuery::getInstance()->simpleQuery($query);
		
		return $ary;
	}
	
	/** Returns an array with all subscribers to this Newsletter
	 * 
	 */
	public function getSubscribers()
	{
		$query = "SELECT s.* FROM "
		 . SUBSCRIBER_TABLE . " s 
		 JOIN " . SUBSCRIPTION_TABLE . " st ON st.subscriber_id = s.id
		 WHERE st.newsletter_id = '{$this->id}'";
		
		return SqlQuery::getInstance()->simpleQuery($query);
	}
	
	
	/**	Adds one subscriber to newsletter
	 * 
	 */
	public function addSubscriber($sub_id)
	{
		SqlQuery::getInstance()->insertQuery(SUBSCRIPTION_TABLE,array('newsletter_id'=>$this->id,'subscriber_id'=>$sub_id));
	}
	
	public function removeSubscriber($sub_id)
	{
		SqlQuery::getInstance()->deleteQuery(SUBSCRIPTION_TABLE,array('newsletter_id'=>$this->id,'subscriber_id'=>$sub_id));	
	}
	
	public function getSubscriberNumber()
	{
		return sizeof($this->getSubscribers());	
	}
	
}


?>
