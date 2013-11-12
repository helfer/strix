<?php



/*					DATABASE OBJECT:																*/
/****************************************************************************************************/
define('FRAGMENT_SUF','_fragment'); //fragment suffix for tables in database.


abstract class DatabaseObject implements DBInterface, PermissionInterface {
	
	protected $id = NULL; //each database object MUST have an id.
	protected $object_type = 'DatabaseObject';
	protected $db_vars = array(); //array of all database related variables
	protected $fragment_vars = array();
	protected $needs_wb = FALSE; //indicates if database related variables were changed
	
	//obvious
	protected $changedDBvars = array();
	
	//optionally load all fragments
	protected $mAdditionalFragments = array();
	
	//permissions
	protected $permission = 1;

	
	//CONSTANTS:
	protected $fragment_suffix = '_fragment';
	
	//TODO: use this?
	//protected abstract function my_table_name();
	
		
	/**
	 * @param: Can be called with array of variables for immediate initialization
	 */
	public function __construct($vars = array())
	{
		if(!empty($vars))
			$this->LoadFromArray($vars);
	}
	
	public function has_fragments(){
		return (sizeof($this->fragment_vars) > 0);
	}
	
	
	//magic functn to set protected/private values. checks if var is dbvar to set need_wb accordingly
	public final function __set($name,$value){
		debug( 'in DBObj::set '.get_class($this).' -> '.$name.' ');
		if($value != $this->$name){			

			if(in_array($name,$this->db_vars) || in_array($name,$this->fragment_vars)){
				$this->needs_wb = 1;
				$this->changedDBvars[$name] = $value;
				debug('<strong>Dboject.__set changed</strong> '.get_class($this).' <strong>id</strong> '.$this->id.' var '.$name. ' <strong>from</strong> '.$this->$name.' <strong>to</strong> '.$value);
				debug('<strong>object needs writeback</strong>!');
			}
			$this->$name = $value;
		}
	}
	
	public final function __get($varname){
		if(property_exists($this,$varname))
			//throw new Exception('variable '.$varname.' does not exist in class'.__CLASS__);
		return $this->$varname;
	}
		
	/* This little darling of a functn will do the writeback for ALL database-objects stored in db;
	 *****************************************************************************/

	public final function getTableValues($skipnull = FALSE){
		$vals =  $this->getListValues($this->db_vars);
		if($skipnull)
			$vals = array_filter( $vals , create_function('$a', 'return !is_null($a);') );
	
		return $vals;
	}
	
	public final function getFragmentValues(){
		return $this->getListValues($this->fragment_vars);
	}
	
	protected function getListValues($list){
		$set = array();
		foreach($list as $v)
			$set[$v] = $this->$v;
	
		return $set;
	}

	
	/* ABSTRACT FUNCTIONS:
	 * *************************************************************************/
	
	protected function getTableName(){throw new Exception('Table Name not set!');}

	/* Executes INSERT query (SQL Database) for a newly created object.
	 *******************************************************************************/	
	private final function persist()
	{
		
		/*notice('lan: '.$_SESSION['language_id']);
		notice($this->language_id);*/
		notice('persist '.$this->id);
		debug('in DBObj::Persist!');
		
		$sql = SqlQuery::getInstance();
		
		//-----------TRANSACTION----------//
		$old_id = $this->id; //if there even was one... also works with fix id for inserts...
		$sql->start_transaction();
		
		$values = $this->getTableValues(TRUE);
		if ( isset($values['id']) )
			unset($values['id']);
			
			
		$res1 = $sql->insertQuery($this->getTableName(),$values);
		$this->id = $res1;
		if($this->has_fragments() && isset($this->language_id))
			$res2 = $sql->insertQuery($this->getTableName().$this->fragment_suffix,array_merge($this->getFragmentValues(),array($this->getTableName().'_id'=>$this->id,'language_id'=>$this->language_id)));
		
		
		$end = $sql->end_transaction();
		if(!$end)
			$this->id = $old_id;
		//---------END TRANSACTION--------//		

		$this->needs_wb = !$end;
		return $end;
	}	
	
	
	//store (insert / update) Object in DB.
	//calls persist if id is null, <= 0 or not numeric
	/**
	 * @param: force_wb means write back all variables no matter if changed or not
	 */
	public function store($force_wb = FALSE)
	{
		//notice('store '.$this->id);
		debug('in DBObj::store '.$this->id);
		if(!is_null($this->id) && $this->id > 0 && is_numeric($this->id))
			return $this->writeback($force_wb);
		else
			return $this->persist();
	}
	
	public function temp_waround_persist()
	{
		$this->persist();
	}
	
	
	
	//use store() to access from outside
	private function writeback($force_wb = FALSE)
	{
		debug('start writeback');
	
		if(!$this->needs_wb && !$force_wb){
			debug('skip writeback');
			return TRUE;	
		}
		
		echo('writeback '.$this->id ); //echo to save space! (what?)

		$sql = SqlQuery::getInstance();
		/*
		print_r($this->changedDBvars);
		print_r(array_flip($this->db_vars));
		print_r(array_intersect_key($this->changedDBvars, array_flip($this->db_vars)));
		print_r(array_flip($this->fragment_vars));
		print_r(array_intersect_key($this->changedDBvars, array_flip($this->fragment_vars)));
		return FALSE;
		*/
		
		//-----------TRANSACTION----------//
		$sql->start_transaction();
		
			$changes_main = array_intersect_key($this->changedDBvars, array_flip($this->db_vars));
			
			if($changes_main)
			{
				$res1 = $sql->updateQuery(
					$this->getTableName(),
					$changes_main,
					array('id'=>$this->id),
					1);
			}
			else
			{
				$res1 = TRUE;	
			}
		
		//TODO: sometimes we need to insert a new fragment into an already existing content object!!
			if($this->has_fragments() && isset($this->language_id))
			{
				$changes = array_intersect_key($this->changedDBvars, array_flip($this->fragment_vars));
				if( empty($changes) )
				{
					$res2 = TRUE;	
				}
				else
				{
					$res2 = $sql->updateQuery(
						$this->getTableName() . $this->fragment_suffix,
						$changes,
						array($this->getTableName() . '_id'=>$this->id,'language_id'=>$this->language_id),
						1);
				}
			}
			else
				$res2 = TRUE;
		
		$end = $sql->end_transaction();
		//---------END TRANSACTION--------//
		
		if($end){
			$this->needs_wb = FALSE;
			debug('writeback successful (for id= '.$this->id.')');
			return TRUE;
		} else{
			error('writeback failed, reason: '.mysql_error());
			return FALSE;
		}
		
	}
	
	
	//----------------------- FRAGMENT specific -----------------------
	
	//TODO: find a better way for this!!!
	protected function addFragment()
	{
		notice('add fragment '.$this->language_id);
		$res2 = SqlQuery::getInstance()->insertQuery($this->getTableName().FRAGMENT_SUF,array_merge($this->getFragmentValues(),array($this->getTableName().'_id'=>$this->id,'language_id'=>$this->language_id)));
	}

	
	protected function removeFragment()
	{
		SqlQuery::getInstance()->deleteQuery($this->getTableName().FRAGMENT_SUF , array($this->getTableName().'_id'=>$this->id,'language_id'=>$this->language_id));
	}
	
	
	
	//--------------------- more fragment stuff -----------------------------
	
	
	
	
	//Make sure to store any changes you made to fragment variables first!!!
	public function loadAdditionalFragments()
	{
		if(!empty($this->mAdditionalFragments))
			return $this->mAdditionalFragments;
		
		//TODO: put it in or take it out???	
		$this->store();
			
		$tbl = $this->getTableName();
		$query = "SELECT * FROM `{$tbl}_fragment` WHERE {$tbl}_id = '{$this->id}'";
		$this->mAdditionalFragments = SqlQuery::getInstance()->assocQuery($query,array('language_id'),$this->fragment_vars);
		return $this->mAdditionalFragments;
	}
	
	public function changeAdditionalFragment($lang_id,$frag_vars)
	{
		$this->mAdditionalFragments[$lang_id] = $frag_vars;
	}
	
	//TODO: really, find a better way!
	protected function addAdditionalFragment($lang_id,$frag_values)
	{
		$tbl = $this->getTableName();
		
		if(isset($this->mAdditionalFragments[$lang_id]))
			throw new Exception('Additional Fragment exists already');
			
		$this->mAdditionalFragments[$lang_id] = $frag_values;
		notice($this->id.' add additional fragment '.$lang_id);
		$res2 = SqlQuery::getInstance()->insertQuery($tbl.FRAGMENT_SUF,array_merge($frag_values,array($this->getTableName().'_id'=>$this->id,'language_id'=>$lang_id)));
	
	}
	
	protected function getAdditionalFragment($lang_id,$field=null)
	{
		if(isset($this->mAdditionalFragments[$lang_id]))
		{
			if(isset ($field))
			{
				return $this->mAdditionalFragments[$lang_id][$field];
			} else {
				return $this->mAdditionalFragments[$lang_id];
			}
		}
		else
			return NULL;
	}
	
	public function storeAdditionalFragments()
	{
		
		$sql = SqlQuery::getInstance();
			$sql->start_transaction();
		
		foreach($this->mAdditionalFragments as $lang_id=>$frag_vars)
		{
			
			if($this->language_id == $lang_id) //change immediately, but do NOT store!
			{
					foreach ($frag_vars as $k=>$val)
						$this->$k = $val;
			}
			
			
			$tbl = $this->getTableName();
			
			$sql->updateQuery($tbl.FRAGMENT_SUF,$frag_vars,array($tbl.'_id'=>$this->id,'language_id'=>$lang_id),1);
						
		}
		
		$sql->end_transaction();
	}
	
	
	//Refresh = refetch information from DB:
	public function refresh()
	{
		//use retrieve to implement it.
			
	}
	
	
	/** Loads object from DB by its id.
	 * 
	 * Cannot be made static because __CLASS__ etc. would always refer to DbObject
	 * 
	 */
	public function LoadFromDbById($id)
	{
		$sql = SqlQuery::getInstance();
		
		$id = mysql_real_escape_string($id);
		$query = "SELECT * FROM `" . $this->getTableName() . "` WHERE id='$id'";
		$obj_row = $sql->singleRowQuery($query);
		if(empty($obj_row))
			throw new Exception('No Object by that id in ' . $this->getTableName() );
			
		$this->LoadFromArray($obj_row);
	}
	
	/*
	 * 
	 */
	public function LoadFromArray($vars)
	{
		foreach($vars as $name => $var)
			$this->__set($name,$var);

		$this->changedDBvars = array();
		$this->needs_wb = FALSE; //initial data, no wb by default
	}
	
	
	/* NOT yet complete (permissions,fragments etc)*/
	public function retrieve($id){
		
		
		throw new Exception('not implemented yet');	
	}/*
		$tname = $this->getTableName();
		$arr = SqlQuery::getInstance()->singleRowQuery(	"SELECT o.* , of.* 
								FROM `".$tname."` o
								JOIN `".$tname.$this->fragment_suffix."` of ON o.`id` = of.`".$tname."_id` 
								WHERE `id`= '".$id."'");
		//print_r($arr);
		$this->__construct($arr);
	}
	*/
	
	public function delete(){
		$sql = SqlQuery::getInstance();
		
		//TRANSACTION start!!
		$sql->start_transaction();
		
			$num_aff = $sql->deleteQuery($this->getTableName(),array('id'=>$this->id));
		
			//in case they do not cascade (normally Mysql should do so).
			if($this->has_fragments())
				$num_aff += SqlQuery::getInstance()->deleteQuery($this->getTableName().$this->fragment_suffix,array($this->getTableName().'_id'=>$this->id));
			
		$success = $sql->end_transaction();	
		//TRANSACTION end!!
		if ($success)
			return $num_aff;
		else
			return FALSE;
		
	}
	
	public function hide()
	{
	}

/* 
 * PERMISSIONS !!!!!!!!!!!!!!!
 * ********************************************************************/

	//TODO: do we really need both??
	public final function checkPermission($perm){
		return	(($this->permission & $perm) != 0);
	}
	
	public final function maskPermissions($perm){
		return	$this->permission = ($this->permission & $perm);	
	}



}
?>
