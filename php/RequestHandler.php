<?php
/**
 * @version 0.8
 * @brief Handles all standard html requests.
 * 
 * Any standard html requests will be handled by this object.
 * Ajax requests on the other hand are NOT handled by this class
 * 
 * @todo have domains return even in case of a redirect and handle it here.
 * @todo make singleton!
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-25
 */
class RequestHandler
{
	protected $mHeaders = array();
	protected $mBytesSent = 0;
	
	protected $requestId = NULL;
	
	//hacked variable...
	protected $send_file_only = FALSE;
	protected $file_text = '';
	

	public function __construct(){} //VOID
	
	//request handler is global object and its output functions can be called from domains
	
	public function newRequest()
	{		
		/* A few essentials first:
		 ***************************************************************/
		ob_start();
		error_reporting(0);
		///@todo debugging only!
		//error_reporting(E_ALL);
		
		//Script_dir is used to make relative paths absolute (faster includes)
		define('SCRIPT_DIR',dirname(__FILE__).'/');
		
		
		/* Debug and error reporting
		 ***************************************************************/
		
		//set error handling options [GET: mcw083 must be set for DEBUG to be set to true]
		if( isset( $_GET['mcw083ob1'] ) )
		{
			unset($_GET['mcw083ob1']);	//don't want it to appear in the logs
			
			if( isset( $_GET['e_all'] ) )
			{
				error_reporting(E_ALL);
				define('TRACE_ERRORS',TRUE);
				echo 'error reporting on<br />';
				echo 'POST: '; print_r($_POST);			
			}
			if( isset( $_GET['debug'] ) )
			{
				define('DEBUG',TRUE);
				echo 'debugging on<br />';
			}
			if( isset( $_GET['debug_sql'] ) )
			{
				define('DEBUG_SQL',TRUE);
				echo 'sql debugging on<br />';
			}
			if( isset( $_GET['ping'] ) )
			{
				echo 'ping request complete.<br />';
				ob_end_flush();
				flush(); //doesn't really work, or does it?
				die();
			}

		}
		
		/* Include essential files
		 ***************************************************************/
		require_once(SCRIPT_DIR . 'first/default_settings.php');
		require_once(SCRIPT_DIR . 'local_settings.php');
		require_once(SCRIPT_DIR . 'first/SqlQuery.php');
		require_once(SCRIPT_DIR . 'first/functions.php');
		require_once(SCRIPT_DIR . 'first/scException.php');
		require_once(SCRIPT_DIR . 'first/Domain.php');
		require_once(SCRIPT_DIR . 'first/interfaces.php');
		require_once(SCRIPT_DIR . 'first/DatabaseObject.php');
		require_once(SCRIPT_DIR . 'first/User.php');
		
		
		
		/* Apply default + local settings
		 ***************************************************************/
		mb_internal_encoding($GLOBALS['config']['charset']);
		
		/* Load session
		 ***************************************************************/		
		$this->loadSession();
		
		/* Process Form input (POST)
		 ***************************************************************/
		$GLOBALS['POST_KEYS'] = array_keys($_POST); //for logging	
		
		require_once(SCRIPT_DIR . 'first/InputHandler.php');
		$iHandler = new InputHandler();
		require_once(SCRIPT_DIR . 'first/FormHandler.php');
		$fHandler = new FormHandler();
		$GLOBALS['InputHandler'] = $iHandler;
		$GLOBALS['FormHandler'] = $fHandler;
		$iHandler->processInput();

		
		/* Create user instance
		 ***************************************************************/
		$user = new User();
		$user->LoadFromDbById($_SESSION['user_id']);
		$GLOBALS['user'] = $user;
		
		/* Extract request uri and host name
		 ***************************************************************/		 
		$request_uri = rawurldecode( strtok($_SERVER['REQUEST_URI'],'?') ); //cut off query string
		$request_host = $_SERVER['HTTP_HOST'];
		$GLOBALS['request_host'] = $request_host;

		/* Log request
		 ***************************************************************/
		//--> time, ip , hostname, session_id, URL, GET, post_keys, referrer, user_agent
		//[requestId = insert_id of sql query for log]
		$pinfo = empty($GLOBALS['POST_KEYS']) ? '': 'P['.implode(',',$GLOBALS['POST_KEYS']).']';
		$pinfo = substr($pinfo,0,500);		

		$GLOBALS['request_id'] = SqlQuery::getInstance()->insertQuery('log',array('ip'=>$_SERVER['REMOTE_ADDR'],'user'=>$_SESSION['user_id'], 'data'=>substr($_SERVER['REQUEST_URI'],0,500).' '.$pinfo));
		notice('request_id= ' . $GLOBALS['request_id']);
		
				
		/* Find corresponding domain and pass on request
		 ***************************************************************/		
		$domain = $this->matchDomain($request_host,$request_uri,$_SESSION['user_id']);
		/** @todo: SQL query looking up domain + permission to it.*/		
		
		if($domain === NULL)
			throw new Exception('Ooops, no domain returned!');
		
		require_once(SCRIPT_DIR . 'domains/' . $domain['class'] . '.php');
		
/*		PASS REQUEST TO DOMAIN:
/*******************************************************************************************************************/
		$domain_obj = new $domain['class']($domain['id']);
						
		$output_ary = $domain_obj->serveContent($domain['resource']);	
				

		if( isset($output_ary['error']))
			throw new Exception('Uh, ah ,eh... there was an ERROR!');

		$output_type = $output_ary['type'];
		$output = $output_ary['output'];
		
/*******************************************************************************************************************/
		if($this->send_file_only)
			$output_type = 'file_only';

		/* Output results / wrap up
		 ***************************************************************/
		switch($output_type)
		{
			case 'error':
				$this->outputHtml(array(
						'head'=>'<title>error</title>',
						'body'=>'<p style="color:red">Error: ' . $output . '</p>'
					));
				break;
			case 'text/html':
				$this->outputHtml($output);
				break;
			case 'file_only':
				$this->outputPlain($this->file_text);
			case 'text/plain':
				$this->outputPlain($output);
				break;
			case 'auto':
				//DO NOTHING, output alread handled...
				break;
			case 'redirect':
				$this->HttpRedirect($output);
				break;
			default:
				//throw new Exception("Bad output type ($output_type)");
		}
		//if no errors prevented form input from being processed:
		$iHandler->discardUsedTokens();
		
		
		/* Final flush, NO MORE OUTPUT FROM HERE ON!
		 ***************************************************************/		
		$this->mBytesSent += ob_get_length();
		if(!headers_sent())
		{
			//this should assure that the browser won't wait for further output
			$this->setHttpHeader('Content-Length: '.$this->mBytesSent);
			$this->sendHttpHeaders();
		}
		
		ob_end_flush();
		flush(); //doesn't really work, or does it?
		
		
		/* Write final log entry
		 ***************************************************************/
		//--> number of bytes returned, time taken to complete request, errors occured? [reference]
		//[use $this->requestId to write to the proper row in DB
		
	}
	
	/**Logs given information in DB.
	 * 
	 */
	protected function logRequest($log_infomation)
	{
		
	}
	
	/** Function usually called to output an html page.
	 * 
	 * Includes some statistics at end of page and provides the option
	 * to make some last-minute modifications to head or body.
	 * 
	 * @param[in]	output	output is an array('head'=>html header, 'body'=> html body)
	 * @return:		VOID
	 */
	protected function outputHtml($output)
	{
		$this->setHttpHeader('Content-Type: text/html;charset=' . mb_internal_encoding());
		
		echo '<?xml version="1.0" encoding="' . mb_internal_encoding() . '"?>';
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">';

		echo '<head>' . $output['head'] . '</head>';
		echo '<body>' . $output['body'];
		
		
		
		/* Credits (who really wants to see these?)
		***************************************************************/
		echo '<p style="text-align:left; padding-left:23em;font-size:0.75em;">&copy; 2010 ibo|suisse | powered by <b>strix</b> v 0.7';	
		
		/* Statistics:
		 ***************************************************************/
		
		//sql queries
		$num_queries = SqlQuery::getInstance()->getTotalQueries();
		$query_latency = round(SqlQuery::getInstance()->getTotalQueryTime(),6)*1000; //time in ms
		
		echo ' | queries to DB: ' . $num_queries . ' (' . $query_latency . 'ms)';
		
		//time taken (till here)
		echo ' | build time ≈ ' . round((microtime(true) - START_TIME),6)*1000 .'ms, ';
	
		
		/* Finally, the most useless statistics of all (page size in bytes):
		 ***************************************************************/
		$stats1 = ' | page size: ';
		$stats2 = ' bytes</p>';
		$end_tags = '</body></html>';
		
		$page_size =  ob_get_length() + mb_strlen($stats1 . $stats2 . $end_tags,'latin1');
		$total_size = $page_size + strlen($page_size);
		$total_size += strlen($total_size) - strlen($page_size); //yeah, it's a nasty one!
		
		echo $stats1 . $total_size . $stats2;
		echo $end_tags;
		
	}
	
	protected function outputPlain($output)
	{		
		$this->setHttpHeader('Content-Type: text/plain;charset=' . mb_internal_encoding());
		echo $output;
	}
	
	/** Finds and returns matching domain.
	 * 
	 * @return: array(id,hostname,path,class)
	 */
	protected function matchDomain($request_host,$request_uri,$user_id)
	{
		//ERRORS: NO_SUCH_DOMAIN, NO_PERMISSION, DOMAIN_LOCKED, AUTH_REQUIRED		
		
		//echo $request_host . '<br />';
		//echo $request_uri . '<br />';
		
		$domains = $GLOBALS['config']['domain_list'];		
				
		//find best matching domain (case insensitive) ---------------------
		$matching_domain = NULL;
		$match_length = 0;

		foreach($domains as $i=>$domain){
			$domain = array_merge(array('id'=>$i),$domain);
			
			//empty string = match any
			$host_matches = ($domain['host'] == '' || $request_host == $domain['host']);
			$path_matches = ($domain['path'] == '' || stripos($request_uri,$domain['path']) === 0);
			
			//best matching domain is the one with the longest matching prefix
			if( $host_matches && $path_matches/* && $match_length < strlen($domain['path']) */){
					$match_length = strlen($domain['path']);
					
					//what's left of the uri after taking away the domain path (leaving the ending slash)
					$domain['resource'] = substr($request_uri,$match_length - 1);
					
					$matching_domain = $domain;
			}
		}
		
		return $matching_domain;
	}
	
	/**Starts session and instantiates the wrapper-object (do I need one?)
	 * 
	 * Session is stored in Database!
	 * 
	 */
	protected function loadSession()
	{
		session_start();
		
		if( !isset($_SESSION['user_id']) )
			$_SESSION['user_id'] = ANONYMOUS_USER_ID;
	}
	
	/** Destroys the old session and starts a new one.
	 * 
	 */
	public function cleanSession()
	{
		session_destroy(); //delete all session information
		session_start();

		$_SESSION['user_id'] = ANONYMOUS_USER_ID;
		
	}
	
	
	//necessary?
	public function setHttpHeader($head)
	{
		$this->mHeaders []= $head;
	}
	
	//
	protected function sendHttpHeaders()
	{
		foreach($this->mHeaders as $head)
			header($head);
		
	}
	
	/** flushes buffer and updates number of bytes sent.
	 * 
	 * Function is mostly used for file output
	 * 
	 */
	public function flushOutputBuffer()
	{
		if(ob_get_length() > 0)
		{
			if(!headers_sent())
				$this->sendHttpHeaders();
			
			//ob_get_length returns bytes, not # of characters!
			$this->mBytesSent += ob_get_length();
			
			ob_flush();
		}
		
		flush();
	}
	
	//can take an internal link [page_id + [domain_id] ] OR a http URL
	public function HttpRedirect($location,$language_id = 0)
	{
		
		if(is_numeric($location)) //is internal page_id, must find corresponding url
		{
			if($language_id == 0)
				$language_id = $_SESSION['language_id'];
			
			$location = SqlQuery::getInstance()->singleValueQuery("SELECT uri FROM `page` p JOIN `page_fragment` pf ON `p`.id = `pf`.page_id WHERE p.`id` = '$location' AND pf.`language_id` = '$language_id';");
		}
		
		//TODO: this will break redirects to a different HTTP_HOST where the uri of the document is the same!!!
		//infinite loop prevention
		$request_uri = 'guagoien';
		if(strstr($location,$request_uri) == $request_uri)
			return;
		
		//send the redirect header
		header("Location: $location");
		
		
		///@todo: this is crap and breaks any logic. It's like goto, only worse!
		exit(0); //must exit here and not continue outputting!
		//TODO: write a proper cleanup-procedure for the end!
	}
	
	
	
	/* TODO: this is a rather ugly hack, because it bypasses a lot of functionality and doesn't fit... */
	public function SendOnlyThis($content)
	{
		$this->send_file_only = TRUE;
		$this->file_text = $content;
	}
	
	
}


?>
