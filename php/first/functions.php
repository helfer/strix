<?php

/**
 * @version 0.1
 * @brief All global functions are found here.
 * 
 * Functions, functions, functions. But only those that can not be
 * associated with an Object. 
 * Global functions are prepended with 'scf_' (mediawiki-style)
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-23
 */


/** Autoload function, used only for content classes.
 *
 * Includes content class file if found in ENABLED_CONTENT_DIR
 * All enabled content classes are "softlinked" in one directory
 */
function __autoload($class_name){
	
	//$class_file = str_replace('_','/',strtolower($class_name)).'.php';
	$class_file = $class_name . '.php';
	
	if( ! @include_once(ENABLED_CONTENT_DIR.$class_file) )
	{		
		echo "ERROR: could not include file $class_file. Is the class enabled?";
	}
			
}


///@todo move this function to a good place
function scms_auth_user($username,$password)
{
	$sqlq = SqlQuery::getInstance();
		
	$login = mysql_real_escape_string($username);
	$pw = mysql_real_escape_string($password);
	
	//TODO: pw is unencrypted for local testing
	$query='SELECT u.id FROM `user` u WHERE u.`username`=\''.$login.'\' AND u.`password`= \''.md5($pw).'\' LIMIT 3';		//limit 3 is just for fun. Could be 2.
	
	$answer = $sqlq->singleRowQuery($query);
	
	$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	
	if($answer)
	{
		notice('Login success');
		$sqlq->execute("UPDATE user SET last_login=NOW(), failed_attempts = 0, num_logins = num_logins + 1, ip_log = LEFT(CONCAT_WS('#',ip_log,'$host'),511) WHERE id='{$answer['id']}'");
		return $answer['id'];
	}
	else
	{
		$GLOBALS['login_notice'] = 'username or password wrong';
		$sqlq->execute("UPDATE user SET failed_attempts = failed_attempts + 1, ip_log = LEFT(CONCAT_WS('#!',ip_log,'".mysql_real_escape_string($host)."'),511) WHERE username='".mysql_real_escape_string($username)."'");
		notice('Login failed!');
		return ANONYMOUS_USER_ID;	
	}	
}


//TODO: where should I put this?
//like array_merge, but works also with numeric keys.
function array_merge_numeric($a1,$a2)
{
	$ret = $a1;
	foreach ($a2 as $k=>$v)
	{
		$ret[$k] = $v;	
	}
	return $ret;
}


function array_multiintersect($array,$values,$key = FALSE)
{
	$ret = array();
	
	foreach($array as $ki=>$subarray)
	{
		if($key === FALSE)
			$k = $ki;
		else
			$k = $subarray[$key];

		if(!is_array($values))
			$ret[$k] = $subarray[$values];
		else
			$ret[$k] = array_intersect_key($subarray,array_flip($values));
	}
	
	return $ret;
	
}



//Junk, TODO: remove this junk asap!
function notice($msg){
	if(!$GLOBALS['config']['show_notices'])
		return;
		
	if(!isset($GLOBALS['notices']))
		$GLOBALS['notices'] = '';
	
	
	if(is_string($msg))
		$GLOBALS['notices'] .= '<p>notice: '.$msg.'</p>';	
	else{
		$GLOBALS['notices'] .= pretty_print_r($msg);
	}
}

function debug($msg){
	/*TODO: ... */
	if(!defined('DEBUG'))
		return;
	/**/
	 
	if(is_string($msg))
		echo '<p>debug: '.$msg.'</p>';	
	else{
		echo pretty_print_r($msg);
	}
}

function sql_debug($msg){

	if(!defined('DEBUG_SQL'))
		return;

	 
	if(is_string($msg))
		echo '<b>SQLdebug: </b><p>'.$msg.'</p>';	
	else{
		echo pretty_print_r($msg);
	}
}

function error($msg){
	if(defined('TRACE_ERRORS'))
		echo scms_backtrace();
	echo '<p class ="error">error: '.$msg.'</p>';	
}

function profile($msg){
	/*TODO: ... */
	if(!isset($_GET['profile']))
		return;
	/**/
	
	echo '<p>profile: '.$msg.'</p>';	
}

function timer()
{
	//DO nothing =)	
}

function pretty_print_r($arr){
	ob_start();
		echo '<p>';
		print_r($arr);
		echo '</p>';
	return ob_get_clean();
}

?>
<?php
/*
 *      functions.php
 *      
 *      Copyright 2009 user007 <user007@UPS1746>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 */

function br2nl($str)
{
	return str_replace(array('<br />','<br>','<br />','</br>'),array("\n","\n","\n","\n"),$str);
}


?>
<?php 

//more junk!

/* Database functions:
 ************************************************************
$config = $GLOBALS['config'];
$connection=mysql_connect($config['database']['host'], $config['database']['user'], $config['database']['password']) or die('Database Server unavailable');
mysql_select_db($config['database']['db_name'], $connection) or die('Select database failed');

function db_query($query){
	throw new Exception('old sql query attempt');
	global $connection;
	echo $query;
	$result=mysql_query($query,$connection);
	if($result === FALSE){
		 error('query: '.$query.' failed. reason: '. mysql_error());
		 return $result;
	}
	if($result === TRUE) return $result;
	
	//otherwise it's a result object:
	return mysql2array($result);
}

function mysql2array($mysql){
	$arr = array();
	while($a = mysql_fetch_assoc($mysql)){$arr[] = $a;}
	return $arr;
}*/


// oops, i'm throwing up. it's too ugly...
	 //TODO: make pretty and neat! Not with inline styles!q
	 function array2html($result_array, $indexes = FALSE)
	 {
	 	if(sizeof($result_array) == 0)
	 		return 'functions.array2html: array size is zero';
	 	
	 			$colnames = array_keys(reset($result_array));

				$html = '<table class="standard" align="center">';
				if($indexes)
					$html .= '<th style="standard">#</th>';
						
				foreach($colnames as $col)										
					$html .= '<th style="standard">'.$col.'</th>';
				$even=false;
				$i = 1;
				foreach($result_array as $row){
					
					if($even)$html .= '<tr class="even">'; else $html.= '<tr class="odd">';
					$even = !$even;
					if($indexes)
						$html .= '<td>'.$i.'</td>';
					foreach($row as $element){
						$html .= '<td>'.$element.'</td>';
					}
					$html .= "</tr>\n";
					$i++;
				}
			return $html.'</table>';
	 	
	 }
	 
	 function write_log($data)
	{
		if($fp = fopen('../page/webcontent/logfile01', 'a')){		
			fwrite($fp,$data);
			fclose($fp);
		}
		else
		{
			error('logfile?');	
		}
	}
	

	//returns a random password of given length
	//includes small, capital, numbers, punctuation ...
	function generateRandomPassword($len)
	{
		$chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$strlen = strlen($chars);

    	srand((double)microtime()*1000000);
    	$i = 0;
    	$pass = '' ;
    	
    	while ($i < $len) {
        	$num = rand() % $strlen;
        	$tmp = substr($chars, $num, 1);
        	$pass = $pass . $tmp;
        	$i++;
    	}
    	return $pass;
		
	}
	
	
	//TODO: offer possibility of variable "from" (right now it's always jonas@ibo2013.ch)
	function sendSBOmail($to,$subject,$message, $attachment='', $attachname='')
	{
		require_once('content/utils/phpmailer/class.phpmailer.php');
		
		if(!is_array($to))
			$to = array($to);
				
		$mail	= new PHPMailer();
		
		$mail->CharSet = 'utf-8';

		$mail->IsSMTP(); // telling the class to use SMTP

		if(defined('DEBUG'))
			$mail->SMTPDebug  = 1;  // enables SMTP debug information (for testing)
									// 1 = errors and messages
									// 2 = messages only
									
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->Host       = "mail.ibo2013.ibone.ch"; // sets the SMTP server
		$mail->Port       = 25;                    // set the SMTP port for the GMAIL server
		$mail->Username   = "web1p3"; // SMTP account username
		$mail->Password   = "an2TH3gX4";        // SMTP account password

		$mail->SetFrom('info@ibo2013.org', 'IBO 2013 Organizing Team');

		$mail->AddReplyTo("info@ibo2013.org","IBO 2013 Organizing Team");

		$mail->Subject    = $subject;

		//$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

		$mail->MsgHTML($message);

		foreach($to as $recipient)
			$mail->AddAddress($recipient);
			
		$mail->AddBCC('daniel.wegmann@olympiads.unibe.ch');
		$mail->AddBCC('info@ibo2013.org');
		
		//add attachment?
		if($attachment!=''){
			if(file_exists($attachment)){	
				if($attachname=='')	
					$mail->AddAttachment($attachment);
				else
					$mail->AddAttachment($attachment, $attachname);
			} else {
				echo "Mailer Error: attachment '".$attachment."' not found!";
			}
		}
		
		
		//$mail->AddAttachment("images/phpmailer.gif");      // attachment
		//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

		if(!$mail->Send()) {
		  echo "Mailer Error: " . $mail->ErrorInfo;
		  return FALSE;
		} else {
		  //echo "Message sent!";
		  return TRUE;
		}
		/**/
	
	
	}
	
	
	/*
	 * Provides a short and easy to use alias for Language::extractPefLan
	 */
	function i18n($arr)
	{
		return Language::extractPrefLan($arr);	
	}

	function scms_backtrace()
	{
		//level indicates the depth of the backtrace wished...
		$lvl = isset($_GET['lvl']) ? $_GET['lvl'] : 3;
		$stacktrace = array();
		
		$trace = debug_backtrace();
		$size = count($trace);
		for($i = 1; $i < $lvl && $i < $size;$i++)
			$stacktrace []= $i.' - '.basename($trace[$i]['file']).': '.$trace[$i]['line'];
			
		return implode('<br />',$stacktrace);
		
	}



?>
