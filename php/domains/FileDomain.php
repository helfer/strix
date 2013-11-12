<?php
/**
 * @version 1.0
 * @brief Serves restricted files to users with permission
 * 
 * This domain returns files that are stored in folders with restricted access.
 * It checks users permissions and file attributes before sending the file
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-25
 */
class FileDomain extends Domain{
	private $mFolder = FILE_DIR;
	
	
	/** Checks if user has permission to download file before calling a function that sends it.
	 * 
	 */
	public function serveContent($resource){

		//check if user is logged in:
		$user = $GLOBALS['user'];

		//anonymous users can't download restricted files
		if($user->id == ANONYMOUS_USER_ID)
		{
			$GLOBALS['RequestHandler']->setHttpHeader('HTTP/1.0 403 Forbidden');
			
			$head = '<title>403 forbidden</title>';
			
			$body = "<h1>Error: 403 Forbidden</h1>";
			$body .= "<p>You do not have the right to access this folder</p>";
			
			SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'denied.reason: no login') WHERE id='{$GLOBALS['request_id']}'");
				
			return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));
		}
		
		
		debug($this);
		debug($resource);
		debug('requested file path: '.$this->mFolder . $resource);
		$target = $this->mFolder . $resource;
		
		if(isset($_GET['file']))
			$target .= $_GET['file'];
			
		//echo $target;exit;
		
		//$target = '/var/www/web1/files/exams/';

		//directory listing is disabled
		if(is_dir($target))
		{
			$head = '<title>directory listing denied</title>';
			$body = '<h2>Directory listing is not enabled!</h2>';
			
						SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'directory listing denied') WHERE id='{$GLOBALS['request_id']}'");
			
			return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));
		}
		
		//does the file even exist??
		if(!is_file($target))
		{
			error($target);
			$GLOBALS['RequestHandler']->setHttpHeader('HTTP/1.0 404 File not found');
			
						SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'error: file not found') WHERE id='{$GLOBALS['request_id']}'");
			
			$head = '<title>404 file not found</title>';
			$body = '<h2>The file you requested does not exist.</h2>';
			return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));	
		}
		
		
		//get file information in DB (including permission)
		$sql = SqlQuery::getInstance();
		$query = "SELECT f.*, m.`type` as mimetype FROM file f
				JOIN mimetype m ON m.`id` = f.`mimetype_id`
				LEFT JOIN file_permission p ON p.file_id = f.id
				LEFT JOIN user_in_group uig ON uig.usergroup_id = p.usergroup_id
				WHERE
					owner_permission = 1
				AND	name = '".basename($target)."'
				AND (
						owner_id = '".$GLOBALS['user']->id."'
						OR (
								uig.user_id = '".$GLOBALS['user']->id."'
								AND p.permission = 1
							)
						OR (
							uig.usergroup_id IN (".ORGANISATOREN.','.ADMIN_GROUP.")
						)
					)
				GROUP BY f.id";

		$f_info = $sql->singleRowQuery($query);
		
//		print_r($f_info);
//		return;
		
		//no file information returned means user has no permission
		if (!$f_info  && !in_array($GLOBALS['user']->primary_usergroup_id , array(ORGANISATOREN,ADMIN_GROUP)) )
		{
			$GLOBALS['RequestHandler']->setHttpHeader('HTTP/1.0 403 Forbidden');
			
			$head = '<title>403 forbidden</title>';
			
			SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'denied: no file permission') WHERE id='{$GLOBALS['request_id']}'");
			
			$body = "<h1>Error: 403 Forbidden</h1>";
			$body .= "<p>You do not have the right to access this file</p>";
			return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));
			
		}
		
					SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'serving file ... ') WHERE id='{$GLOBALS['request_id']}'");

		if ( $this->serve_file_chunked($target, $f_info['mimetype'], TRUE) )
		{
			
			SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'done') WHERE id='{$GLOBALS['request_id']}'");
			//TODO: improve the 'exit';
			exit(0);
			return array('type'=>'auto'); //auto= output already completed
		}
		else
		{
			SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,' output error') WHERE id='{$GLOBALS['request_id']}'");
			return array('type'=>'text/html','output'=>array('head'=>'<title>error</title>','body'=>'<h2>an output error occurred</h2>'));
		}
	}
	
	

	
	/** Reads file and outputs content in chunks (to save memory).
	 * 
	 */
	private function serve_file_chunked($filename, $mimetype, $retbytes = TRUE) {
		$buffer = '';
		$bytes =0;

		ob_clean();

		$handle = fopen($filename, 'rb');
		if ($handle === false) 
		{
			print_r('could not open '.$filename.' for reading');
		  return false;
		}

		$rh = $GLOBALS['RequestHandler'];

		$rh->setHttpHeader('Content-Type: '.$mimetype );
		$rh->setHttpHeader('Content-Description: File Transfer');
    	//$rh->setHttpHeader('Content-Type: application/octet-stream');
		$rh->setHttpHeader('Content-Disposition: attachment; filename='.basename($filename));
    	$rh->setHttpHeader('Content-Transfer-Encoding: binary');
		$rh->setHttpHeader("Content-Length: " . filesize($filename)); 

		while (!feof($handle)) 
		{
			$buffer = fread($handle, FILE_OUTPUT_CHUNK_SIZE);
			echo $buffer;

			$GLOBALS['RequestHandler']->flushOutputBuffer();
			//usleep(200000);
		  
			if ($retbytes) 
				$bytes += mb_strlen($buffer,'latin1');
		}
		
		
		$status = fclose($handle);
		
		if ($retbytes && $status) 
		  return $bytes; // return num. bytes delivered like readfile() does.
		else
			return $status;
	}
	
	
}

?>

