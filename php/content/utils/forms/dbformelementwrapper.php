<?php

/**
*	File: DbFormElement.php
*	Author: jonas.helfer@epfl.ch
*	copyright: 2009 Jonas Helfer
*
*/

class DbFormElementWrapper extends DatabaseObject
{
	
	protected $db_vars = array('id','form_id','name','left_id','right_id','label','form_element_class_id','data');
	
	//---------------
	//meta-db-vars
	protected $classname = NULL;
	protected $value = NULL;
	
	//------------------
	
	protected $id = NULL;
	protected $form_id = NULL;
	protected $left_id = NULL;
	protected $right_id = NULL;
	protected $form_element_class_id = NULL;
	protected $name = '';
	protected $label = '';
	protected $data = array(); //UNSERIALIZE!!
	
	//-------------
	
	protected $mFormElementObject = NULL;
	
	public function __construct($vars = array())
	{
		parent::__construct($vars);
		
		//$this->ident = $GLOBALS['user']->id;
	}
	
	public function getTableName(){return 'form_element';}
	
	public function LoadFromDbById($id)
	{
		$query = "SELECT fe.*, cl.class AS classname 
					FROM `" . $this->getTableName() . "` fe 
					JOIN form_element_class cl ON fe.form_element_class_id = cl.id 
					WHERE id='$id'";
		$obj_row = SqlQuery::getInstance()->singleRowQuery($query);
		$this->LoadFromArray($obj_row);
	}
	
	
	public function getFormElement()
	{
		if ( isset ($this->mFormElementObject) )
			return $this->mFormElementObject;
		
		if(!empty($this->data))
			$element_data = unserialize($this->data); //just set all fields straight from data.
		else
			$element_data = array();
		
		if( !is_array($element_data) )
		{
			print_r($element_data);
			echo '##' . serialize( array('options'=>array('ja','nein','vielleicht'))) . '##';
			throw new Exception('Element data is not an array!');
		}
		//override fields defined in class
		//$element_data['classname'] = $this->classname;
		$element_data['label'] = $this->label;
		$element_data['name'] = $this->name;
		$element_data['value'] = $this->value;
		
		
		
		$this->mFormElementObject = new $this->classname($element_data);
		return $this->mFormElementObject;
	}


}

?>
