<?php

/**
*	File: DbFormWrapper.php
*	Author: jonas.helfer@epfl.ch
*	copyright: 2009 Jonas Helfer
*
* 
* @todo This object should actually work just like a form. The way it's handled now is too
* complicated because it acts as an intermediary which is not accessible to all objects using the form it contains.
*/

class DbFormWrapper extends DatabaseObject
{
	
	protected $db_vars = array('id','name','description','form_class_id','ident_type','data');
	
	protected $id = NULL;
	protected $name = '';
	protected $description = '';
	protected $form_class_id = NULL;
	protected $ident_type = NULL;
	protected $data = '';
	
	//-------------------------------
	//meta-db-vars
	protected $classname = NULL;
	//-------------------------------
	
	
	protected $mDbElements = array();
	protected $mDbElementIds = array();	//array (name=>id, name=>id ...)
	//protected $mElementOrder = array();
	protected $mForm = NULL;
	protected $mIdentValue = NULL;
	
	
	public function getTableName(){return 'form';}
	
	/**
	 * @param vector The forms vector if it already exists.
	 */
	public function LoadFromDbById($id,$vector)
	{

		//Load form
		$query = "SELECT form.*, class.class  as classname
					FROM `" . $this->getTableName() . "` form
					JOIN `form_class` class ON class.id = form.form_class_id
					WHERE form.id='$id'";
		$obj_row = SqlQuery::getInstance()->singleRowQuery($query);
		
		$this->LoadFromArray($obj_row);
		
		
		if ( isset($vector['ident_value']) )
			$this->mIdentValue = $vector['ident_value'];
		else
		{
			switch ($this->ident_type)
			{
				case 'user_id':
					if (!isset($_SESSION['user_id']) )
						throw new Exception('Jonas, you need to change your code, the user_id is still undefined for dbform ident!');
						
					$this->mIdentValue = $_SESSION['user_id'];
					
					break;
				default:
					throw new Exception('db form ident type undefined!');	
				
			}
		}
				
		//Load elements:
		$query = "SELECT fe.*, data.value, cl.class AS classname 
					FROM `form_element` fe 
					JOIN form_element_class cl ON fe.form_element_class_id = cl.id 
					LEFT JOIN form_data data ON data.element_id = fe.id AND data.form_id = fe.`form_id` AND data.ident = '{$this->mIdentValue}'
					WHERE fe.`form_id`='{$obj_row['id']}'
					ORDER BY left_id ASC";
		$rows = SqlQuery::getInstance()->simpleQuery($query);
		
		foreach($rows as $e_row)
		{
			//print_r($e_row);
				$element = new DbFormElementWrapper();
				$element->LoadFromArray($e_row);
				$this->mDbElements []= $element;
				$this->mDbElementIds[$e_row['name']] = $e_row['id'];
		}
		
	}
	
	/**
	 * @ return Returns all form values entered so far.
	 */
	public function getValues()
	{
		$query = "SELECT fd.element_id, fe.name, fd.ident, fd.time, fd.value
					FROM form_data fd 
					JOIN form_element fe ON fd.element_id = fe.id
					WHERE fd.`form_id`='{$this->id}'
					ORDER BY fd.`ident`, fe.`left_id` ASC";
		$resp = SqlQuery::getInstance()->simpleQuery($query);
	
		$ret_array = array();
	
	
		///@ todo find a better way, seriously!
		$curr_ident = NULL;
		foreach ($resp as $val)
		{
			$ret_array[ $val['ident'] ]['ident'] = $val['ident'];
			$ret_array[ $val['ident'] ]['time'] = $val['time'];
			$ret_array[ $val['ident'] ][ $val['name'] ] = $val['value'];
		}
	
		return $ret_array;
	}
	
	
	
	public function getForm()
	{
		if (isset ( $this->mForm ) )
			return $this->mForm;
		
		$form = new $this->classname(
			$this->name,
			'', //action
			array(), //extras
			-1, //target_id
			'db_form', //target_type
			'post' //method
			);
			
		$form->setVector(array('dbform_id'=>$this->id,'ident_value'=>$this->mIdentValue));
			
		foreach($this->mDbElements as $dbel)
		{
			$form->addElement( $dbel->__get('name') , $dbel->getFormElement() );	
		}
		
		$this->mForm = $form;
		
		
		return $this->mForm;
	}
	
	

/*
	public function createElement($element_ary)
	{

	}
*/	
	
	public function addElement($name,$element)
	{
		if(isset($this->mElements[$name]))
			throw new Exception('Element with name ' . $name . ' already exists in DbForm ' . $this->name);

		$this->mElements[$name] = $element;
		$this->mElementOrder []= $name;
	}
	
	public function removeElement($name)
	{
		if(isset($this->mElements[$name]))
		{
			unset($this->mElements[$name]);
			unset($this->mElementOrder[array_search($name, $this->mElementOrder)]);
			array_merge($this->mElementOrder); //not merging, just reindexing!
		}
		else
			throw new Exception('No element with name ' . $name . ' in DbForm ' . $this->name);
	}
	
	protected function getElementWrapperId($name)
	{
		///@todo safety checks
		
		return $this->mDbElementIds[$name];
	}
	
	//outputs the php-code defining the form for simple copy-paste
	public function printPhpCode()
	{
		$php = '';

		$class = $this->form_class_name;

		$php .= '$form_auto = new ' . $class . '( array( [initialization values]) )' . "\n";

		$php .= '$form_auto->parseFromList(' . "\n";

		//list format: element_name=> array(name=>element_name,label=>element_label, foreach data : k=>v)


	}
	
	public function storeFormValues()
	{
		$values = $this->mForm->getWriteableElementValues();
		
		$sql = SqlQuery::getInstance();
		

		
		foreach($values as $name=>$val)
		{
			$ary = array('form_id'=>$this->id,'element_id'=>$this->getElementWrapperId($name),'ident'=>$this->mIdentValue,'value'=>$val);
			$sql->replaceQuery('form_data',$ary);
		}
	}

}

?>
