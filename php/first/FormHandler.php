<?php
/**
 * @version 0.1
 * @brief Takes care of DbForms (centralized instance)
 * 
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-23
 */
class FormHandler
{
	
	// $_SESSION[TOKEN_FIELD_NAME]			 	//holds all active tokens
	// $_SESSION[TOKEN_SERIAL_FIELD]		 	//associates all tokens to a request
	protected $mForms = array();
	protected $mDbForms = array();				
	protected $mDbWrappers = array();			//stores the wrappers
	public function __construct()
	{
		///@todo this is not the right way to do things!
		include_once('content/utils/forms/dbformwrapper.php');
		include_once('content/utils/forms/dbformelementwrapper.php');
		include_once('content/utils/forms/form.php');
		include_once('content/utils/forms/form_elements.php');
		include_once('content/utils/forms/form_restrictions.php');
	}
	
	public function getForm($name)
	{
		if ( !isset($this->mForms[$name]) )
		{
			///@ todo implement this with flat files (?? or not ??)
		}
		return $this->mForms[$name];
		
	}
	
	public function getDbForm($id , $vector = NULL)
	{
		if ( !isset($this->mDbForms[$id]) )
			$this->loadDbWrapper( $id , $vector );

		return $this->mDbForms[$id];	
	}
	
	public function getDbFormWrapper( $id )
	{
		if(!isset($this->mDbWrappers[$id]))
			$this->loadDbWrapper($id);
			
		return $this->mDbWrappers[$id];
	}
	
	
	public function storeFormValues( $id )
	{
		if(!isset($this->mDbWrappers[$id]))
			$this->loadDbWrapper($id);
			
		$this->mDbWrappers[$id]->storeFormValues();
	}
	
	
	protected function loadDbWrapper($id , $vector)
	{
		$dbf = new DbFormWrapper();
		$dbf->LoadFromDbById($id,$vector);
		
		$this->mDbWrappers[$id] = $dbf;
		$this->mDbForms[$id] = $dbf->getForm();
	}
	
}
?>
