<?php
/*
 *      usergroup.php
 *      
 *      Copyright 2009 user007 <user007@u007>
 */

class Usergroup extends DatabaseObject
{
	protected $db_vars = array('id','name','description','chief_user_id');
	
	protected $id = NULL;
	protected $name = '';
	protected $description = '';
	protected $chief_user_id = NULL;
	
	public function getTableName(){return 'usergroup';}
	
	/*public static function LoadFromDbById($id)
	{
		$query = "SELECT * FROM usergroup WHERE id='$id'";
		$obj_row = SqlQuery::getInstance()->singleRowQuery($query);
		return new Usergroup($obj_row);
	}*/
	
}


?>
