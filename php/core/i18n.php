<?php


class i18nVariable extends DatabaseObject
{
	
	protected $name = '';
	protected $text = '';
	protected $description = '';
	
	//ID MUST NOT BE IN DB-vars. it cannot be changed!!!
	protected $db_vars = array(	'name',
								'description',
								);
								
	protected $fragment_vars = array('text');
	
	
	protected function getTableName(){return "i18n";}
	
	///@override because I need it to load the Fragment too...
	public function LoadFromDbById($id)
	{
		$sql = SqlQuery::getInstance();
		
		$id = mysql_real_escape_string($id);
		$query = "SELECT * FROM 
			`" . $this->getTableName() . "` t 
			JOIN `" . $this->getTableName() . "_fragment` ft 
			ON t.id = ft.`" . $this->getTableName() . "_id`
			WHERE t.id='$id' AND ft.language_id='" . $_SESSION['language_id'] . "'";
		$obj_row = $sql->singleRowQuery($query);
		if(empty($obj_row))
			throw new Exception('No Object by that id and language in ' . $this->getTableName() );
			
		$this->LoadFromArray($obj_row);
	}
	
	public function LoadFromDbByName($name,$language_id = null)
	{
		if(!isset($language_id))
			$language_id = $_SESSION['language_id'];
		
		$sql = SqlQuery::getInstance();
		
		$name = mysql_real_escape_string($name);
		$query = "SELECT * FROM 
			`" . $this->getTableName() . "` t 
			JOIN `" . $this->getTableName() . "_fragment` ft 
			ON t.id = ft.`" . $this->getTableName() . "_id`
			WHERE t.name='$name' AND ft.language_id='" . $language_id . "'";
		$obj_row = $sql->singleRowQuery($query);
		if(empty($obj_row))
			throw new Exception('No Object by that id and language in ' . $this->getTableName() );
			
		$this->LoadFromArray($obj_row);
	}
	
}

?>
