<?php 
	   
	/** Set start time & hand over to RequestHandler.
	 *******************************************************************/
	$total_time_start = microtime(true);
	define('START_TIME',$total_time_start);
	define('HTML_DIR',dirname(__FILE__).'/');
	
	//echo getcwd();
	chdir('../php');
	require('RequestHandler.php');
	$rh = new RequestHandler();
	$GLOBALS['RequestHandler'] = $rh;
	$rh->newRequest();

?>
