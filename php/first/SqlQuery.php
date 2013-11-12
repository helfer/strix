<?php

define('ASSOC_KEY_GLUE','<:>');

final class SqlQuery{
	private $instance;
	
	private $connection;
	private $db_name;
	
	private $totalQueries = 0;
	private $totalQueryTime = 0;
	
	private $logging = FALSE;
	
	//last result;
	private $result;
	
	//number of rows of last query
	private $num_rows = 0;
	
	//number of rows affected by last query
	private $affected_rows = 0;
	
	protected $mTransError = FALSE;
	/*
	 * 
	
	
	
	/*************************************************************************************/
	//OBJECT METHODS: -----------------------------------------------------------------
	/*************************************************************************************/
	
	private function __construct(){
		$db_conf = $GLOBALS['config'];
		
		$query_time_start = microtime(true);
		
		$this->connection=mysql_connect($db_conf['db_host'], $db_conf['db_user'], $db_conf['db_pass']) or die('Database Server unavailable');
		$this->db_name = $db_conf['db_name'];
		//TODO: throw an exception here so admin can be notified!!
		mysql_select_db($this->db_name, $this->connection) or die('Select database failed');
		mysql_query("SET NAMES 'utf8' "); //connection must be UTF-8 for correct encoding of special characters
		
		mysql_query("SET SESSION sql_mode='STRICT_ALL_TABLES'"); //STRICT MODE TO AVOID VARCHAR-TRUNCATION
		//echo mysql_error();
		$this->totalQueryTime += microtime(true) - $query_time_start;
		
	}
	
	
	/** Returns total number of queries executed until now.
	 * 
	 */
	public function getTotalQueries()
	{
		return $this->totalQueries;	
	}
	
	/** Returns the time taken for all queries executed by this object
	 * 
	 */
	public function getTotalQueryTime()
	{
		return $this->totalQueryTime;	
	}
	
	
	public static function getInstance(){
		
		if (!isset($GLOBALS['SqlQueryInstance'])) $GLOBALS['SqlQueryInstance'] = new SqlQuery();
			
		return $GLOBALS['SqlQueryInstance'];
	}
	
	/**all queries are done through this method!!!
	 * 
	 */
	public function execute($query){
		//!!crap warning!
		$query_time_start = microtime(true);
		$this->totalQueries++;
		
		$this->result = mysql_query($query,$this->connection);

		sql_debug(scms_backtrace().'<br />'.$query);	
		profile('execution time = '.(microtime(true) - $query_time_start).' seconds for: '.substr($query,0,112).'...');
		
		
		
		if($err = mysql_error()){
			error('Mysql Error: '.$err); //TODO: !
			$this->mTransError = TRUE;
			//throw new SqlException('query failed: '.$query);	
		}
		
		//$this->num_rows = mysql_num_rows($this->result);
		$this->totalQueryTime += microtime(true) - $query_time_start;
		
		return $this->result;
	}
	
	public function singleRowQuery($query){
		$ans = $this->execute($query);
		if($ans && $this->num_rows() == 1)
			return mysql_fetch_assoc($ans);
		if($ans && $this->num_rows() == 0){
			return array();	
		}
		if($ans && $this->num_rows() > 1){
			throw new SqlException('Sql query returned more than one row!');
			return FALSE;
		}
	}
	
	
	public function safeSelect($table,$cols,$where,$escaped = FALSE)
	{
		if(!$escaped)	
		{
			$where = array_map('mysql_real_escape_string',$where); 
			$cols = array_map('mysql_real_escape_string',$cols);
			$table = mysql_real_escape_string($table);  
		}
		
		$cond = self::make_var_string($where,' AND ');
		$cols = implode(', ',$cols);
		
		$query = "SELECT $cols FROM $table WHERE $cond;";
		
		return $this->simpleQuery($query);
	}
	
	
	
	//query form: $query, array of identifying rows, array of other rows
	//ATTENTION: keys and values are arrays! to have single-string keys in resulting
	//array, a glue is used!!!
	public function assocQuery($query,$keys,$values,$collapse = FALSE){
		$ans = $this->execute($query);
		$res = array();
		
		while($ab = mysql_fetch_assoc($ans)){
			$key = implode(ASSOC_KEY_GLUE,array_intersect_key($ab,array_flip($keys)));
			$val = array_intersect_key($ab,array_flip($values));
				
			if ($collapse && sizeof($values) == 1)
				$val = reset($val);
				
				
			$res[$key] = $val;
		}
			
		return $res;	
	}
	
	//makes an array of single values
	public function listQuery($table,$key,$value,$distinct = '',$where = 1)
	{
		$query = "SELECT $distinct `$key`,`$value` FROM `$table` WHERE $where ORDER BY $key";
		$ans = $this->execute($query);
		
		$res = array();
		while($k = mysql_fetch_assoc($ans))
			$res[$k[$key]]= $k[$value];
			
		return $res;
	}

	//TODO: escape keys and table as well??
	//expects non-escaped values by default!
	public function updateQuery($table,$set,$where, $limit = 0,$escaped = FALSE)
	{
		
		if(!$escaped)
		{	
			$set = array_map('mysql_real_escape_string',$set);
			$where = array_map('mysql_real_escape_string',$where);
		}
		
		$query = "UPDATE `$table` SET ";
		
		$vars = array();
		foreach($set as $k=>$v)
		{
			if($v == 'sql_NULL')
				$vars []= "`$k`= NULL";
			else if ($v == 'NOW()')
					$vars []= "`$k`= NOW()";
			else
				$vars []= "`$k`='$v'";
			
		}
			
		$wheres = array();
		foreach($where as $k=>$v)
		{
			if($v == 'sql_NULL')
				$wheres []= "`$k`= NULL";
			else
				$wheres []= "`$k`='$v'";
			
		}
			
		$query .= 	implode(', ',$vars).' WHERE '.implode(' AND ',$wheres);
		
		if(is_numeric($limit) && $limit)
			$query .= ' LIMIT '.$limit;	
			
		//echo "QUERY: ".$query."<br/>";
		$res = $this->execute($query,$this->connection);
		return mysql_affected_rows();
	}
	
	
	public function updateSelectQuery($table,$set_select,$where,$limit = 0)
	{
		$query = "UPDATE `$table` SET ";
		
		$where = array_map('mysql_real_escape_string',$where); //escapes...
		
		$wheres = array();
		foreach($where as $k=>$v)
			$wheres []= "`$k`='$v'";
			
		$query .= 	$set_select.' WHERE '.implode(' AND ',$wheres);
	
		if(is_numeric($limit) && $limit)
				$query .= ' LIMIT '.$limit;	
				
		sql_debug($query);	
			
		$res = $this->execute($query,$this->connection);
		return TRUE;		
	}
	
	
	public function replaceQuery($table,$insert_values,$escaped = FALSE)
	{
		return	$this->insertQuery($table,$insert_values,$escaped,TRUE);
	}
	
	//TAKES NON-ESCAPED VALUES by default!
	//TODO: escape keys and $table as well??? 
	//TODO: what to return on failure??
	//RETURN value: insert_id
	public function insertQuery($table,$insert_values,$escaped = FALSE, $replace = FALSE){
		
		if(!$escaped)	
		{
			$table = mysql_real_escape_string($table);	
		}
		
		
		$val_ary = array();
		$vals = '';
		$vars = '';
		
		//could be array of rows to insert:
		if (is_array(reset($insert_values)))
		{
			list($vars) = $this->parseInsertRow( reset($insert_values),$escaped);
			
			foreach($insert_values as $line)
			{
				list(,$tmp) = $this->parseInsertRow($line);
				$val_ary []= $tmp;
			}
				
			$vals = implode(",\n",$val_ary);
		}
		else //or just one row to insert:
		{
			list($vars,$vals) = $this->parseInsertRow($insert_values,$escaped);
		}
		
		
		$insert_or_replace = $replace ? 'REPLACE' : 'INSERT';
		
		$query = $insert_or_replace . ' INTO ' . $table . ' (' . $vars . ') VALUES '.$vals; 

		//echo $query;
		
		$res = $this->execute($query,$this->connection);
			
		if (!mysql_error() )
			return mysql_insert_id();
		else
			return NULL;
	}
	
	/**
	 * @return array(keys, values)
	 */
	protected function parseInsertRow($insert_row,$escaped = FALSE)
	{
		if(!$escaped)	
		{
			$insert_row = array_map('mysql_real_escape_string',$insert_row);
		}
		
		$vars = '';
		$vals = '';
		foreach($insert_row as $key => $val){
			$vars .= '`'.$key.'`, ';
			$vals .= '\''.$val.'\', ';		
		}
		$vars = trim($vars,', ');
		$vals = trim($vals,', ');
	
		$vals = '(' . $vals . ')';
		return array($vars,$vals);
	}

	
	
	public function insertSelectQuery($table,$keys,$select)
	{
		$query = "INSERT INTO `$table` ($keys) $select";
		$res = $this->execute($query);
		return mysql_affected_rows();
	}
	
	
	
	public function singleValueQuery($query)
	{
		//TODO: what if no row is returned??
		$ans = $this->execute($query);
		
		
		if(!$ans || $this->num_rows() < 1)
			return NULL;
		//a value must be returned for this type of query!!
		if($this->num_rows() != 1)
			throw new Exception('Wrong number of rows returned for query '.$query);
			
		$row = mysql_fetch_row($ans);
		
		return reset($row);	
	}
	
	
	
	public function deleteQuery($table,$vars,$limit=0)
	{
		
		$ident = self::make_var_string($vars,' AND ');
		
		$lim = '';
		if(is_numeric($limit) && $limit)
			$lim = ' LIMIT '.$limit;
		
		$query = "DELETE FROM `$table` WHERE $ident $lim";
			
		$this->execute($query);

		return $this->affected_rows();
		//return 1;
	}
	
	public function num_rows($res = 0){
		if($res === 0)
			$res = $this->result;
			
		return mysql_num_rows($res);
	}
	
	public function affected_rows(){
		return mysql_affected_rows($this->connection);
	}
	
	public function simpleQuery($query){
		if(get_class($this) != 'SqlQuery')
			throw new Exception('No SqlQueryObject!!');
			
		return self::mysql2array( $this->execute($query) );
	}
	
	public function start_transaction()
	{
		$this->mTransError = FALSE;
		mysql_query("BEGIN",$this->connection);
		profile('BEGIN TRANSACTION');	
	}
	
	public function end_transaction()
	{
		profile('END TRANSACTION');
		if($this->mTransError){
			error('TRANSACTION ROLLBACK');//. Reason: '.$this->mTransError);
			mysql_query("ROLLBACK",$this->connection);
			return FALSE;
		} else {
			debug('TRANSACTION COMMIT');
			mysql_query("COMMIT",$this->connection);
			return TRUE;	
		}
		
	}
	
	public function abort_transaction($reason)
	{
		profile('ABORT TRANSACTION');
		error('TRANSACTION ABORTED. REASON: '. $reason);
		mysql_query("ROLLBACK",$this->connection);
	}
	
	public function activate_logging(){
		$this->logging = TRUE;
	}

	/**
	 * Useful for WHERE conditions, eg. WHERE v1='bla' AND v2=3 ...
	 * Useful also for SET: SET x1=0, x2=14 ...
	 */
	private static function make_var_string($arr,$glue = ', '){
		$strarr = array();
		foreach($arr as $k=>$v)
			$strarr []= "`$k`='$v'";
			
		return implode($glue,$strarr);
		
	}
	
	/*************************************************************************************/
	//HELPER FUNCTIONS: ------------------------------------------------------------------
	/*************************************************************************************/
	
	public static function mysql2array($mysql){
		$arr = array();
		while($a = mysql_fetch_assoc($mysql)){$arr[] = $a;}
		return $arr;
	}





/*

if (!function_exists('mysql_dump')) {

TODO: check this before using it!


   function mysql_dump($database) {

      $query = '';

      $tables = @mysqlllll_list_tables($database);
      while ($row = @mysql_fetch_row($tables)) { $table_list[] = $row[0]; }

      for ($i = 0; $i < @count($table_list); $i++) {

         $results = mysql_query('DESCRIBE ' . $database . '.' . $table_list[$i]);

         $query .= 'DROP TABLE IF EXISTS `' . $database . '.' . $table_list[$i] . '`;' . lnbr;
         $query .= lnbr . 'CREATE TABLE `' . $database . '.' . $table_list[$i] . '` (' . lnbr;

         $tmp = '';

         while ($row = @mysql_fettttch_assoc($results)) {

            $query .= '`' . $row['Field'] . '` ' . $row['Type'];

            if ($row['Null'] != 'YES') { $query .= ' NOT NULL'; }
            if ($row['Default'] != '') { $query .= ' DEFAULT \'' . $row['Default'] . '\''; }
            if ($row['Extra']) { $query .= ' ' . strtoupper($row['Extra']); }
            if ($row['Key'] == 'PRI') { $tmp = 'primary key(' . $row['Field'] . ')'; }

            $query .= ','. lnbr;

         }

         $query .= $tmp . lnbr . ');' . str_repeat(lnbr, 2);

         $results = mysql_quuuuuery('SELECT * FROM ' . $database . '.' . $table_list[$i]);

         while ($row = @mysql_fetch_assoc($results)) {

            $query .= 'INSERT INTO `' . $database . '.' . $table_list[$i] .'` (';

            $data = Array();

            while (list($key, $value) = @each($row)) { $data['keys'][] = $key; $data['values'][] = addslashes($value); }

            $query .= join($data['keys'], ', ') . ')' . lnbr . 'VALUES (\'' . join($data['values'], '\', \'') . '\');' . lnbr;

         }

         $query .= str_reppppeat(lnbr, 2);

      }

      return $query;

   }

*/

}


?>
