<?php

	/** Stores all the default settings (Do NOT modify).
	 * 
	 * This file holds default settings for various values. Do Not modify
	 * this file, make changes in local_settings.php instead, local_settings
	 * overrides the settings in this file.
	 *******************************************************************/
	
	
	
/************************************************************************************/
/*					Constants														*/
/************************************************************************************/
	
	/* Paths (HTTP URI) [thus the _PATH]
	 ******************************************************************/
	define('STYLESHEETS_PATH','/webcontent/styles/css/');
	define('STYLE_IMAGE_PATH','/webcontent/styles/img/');
	define('DOWNLOADS_PATH','/webcontent/downloads/');
	define('FILE_PATH','/files/');
	
	
	/* Files + Directories (UNIX) [thus the _DIR]
	 ******************************************************************/
	
	define('FILE_LOC','/files/');
	define('FILE_DIR',SCRIPT_DIR . '../files');
	define('ENABLED_CONTENT_DIR',SCRIPT_DIR . 'content/enabled/');
	define('AVAILABLE_CONTENT_DIR',SCRIPT_DIR . 'content/available/');
	define('DOWNLOADS_DIR', HTML_DIR . DOWNLOADS_PATH);
	
	define('RUN_ONCE_DIR',SCRIPT_DIR . 'run_once/');
	
	/* Security:
	 **********************************************************************/
	define('SHA_1_SALT','efe7FNe1aQ');
	
	define('FILE_OUTPUT_CHUNK_SIZE','2048');
	
	
 	/* Viewing modes:
 	 **********************************************************************/
 	define('VMODE_NORMAL',1);
 	define('VMODE_MODIFY',2);
 	define('VMODE_ADMIN',3);
 	define('VMODE_TRANSLATE',4);
	
	/* Rights:
	 **********************************************************************/
	define('MAX_PERMISSION',4);
	define('READ_PERMISSION',1);
	define('WRITE_PERMISSION',2);
	define('ADMIN_PERMISSION',4);
	
	define('ANONYMOUS_USER_ID',1);
	define('EVERYBODY_USERGROUP_ID',1);
	
	/* Forms + Tokens
	 *******************************************************************/
	define('TOKEN_FIELD_NAME','uid_form_token');
	define('TOKEN_SERIAL_FIELD','uid_token_serial');
	define('TOKEN_USED_FIELD','uid_token_used');
	define('MAX_USED_TOKENS',10);
	define('MAX_TOKENS_PER_PAGE',100);
	define('MAX_TOTAL_TOKENS',250);
	
	
	
	
/************************************************************************************/
/*					Configuration													*/
/************************************************************************************/	
	
	
	

	$GLOBALS['config'] = array();
	$sg =& $GLOBALS['config'];
	
	
	/* Charset
	 *******************************************************************/
	$sg['charset'] = 'UTF-8';
	
	
	/* Debugging + stuff
	 *******************************************************************/
	$sg['show_notices'] = FALSE;
	
	
	/** @todo: move to Database;
	 *  Domains:
 	 **********************************************************************/
	
	//empty string = match any
 	$sg['domain_list'] = array(
 	array('class'=>'PageDomain','host'=>'','path'=>'/'),
 	array('class'=>'WikiDomain','host'=>'','path'=>'/wiki/'),
 	array('class'=>'PhpBBDomain','host'=>'','path'=>'/forum/'),
 	array('class'=>'AdminDomain','host'=>'','path'=>'/admin/'),
 	array('class'=>'FileDomain','host'=>'','path'=>'/files/'),
	/*array('class'=>'UtilsDomain','path'=>'/utils/'),*/
	array('class'=>'AjaxHandler','host'=>'','path'=>'/AXréées/'),
 	array('class'=>'ErrorDomain','host'=>'','path'=>'/opens/'),
 	array('class'=>'CreatePDFDomain', 'host'=>'','path'=>'/createPDF/')
 	);
		
		
	//if this is on, the language of the uri requested overrides the session language preferences and HttpAcceptLanguage
 	//a consequence of this is that content cannot be normally displayed in any other than the page languages as long
 	//as all content exists in the page language as well
 	define('URI_LANGUAGE_OVERRIDE',0);
 	
 	 //enable this if you want the language of the page uri to be set as the preferred language.
 	 //Recommended to set this to the same value as URI_LANGUAGE_OVERRIDE
 	define('URL_SETS_PREF_LANGUAGE',0);
 	
 	//determines if page prefers to display content in its own language.
 	define('COHERENT_PAGE_LANGUAGE',0);
 	
 	//query string (eg. ?lan=fr) will override everything (including URI_LANGUAGE_OVERRIDE and URL_SETS_PREF_LANGUAGE);
 	//recommended to leave this on 1 (behaviour is more predictable for user)
 	define('FORCE_GET_LANGUAGE',1);
 	
 	//redirect if uri of page returned is not exactly the same as the uri requested by the browser
 	define('REDIRECT_ON_URI_MISMATCH',1);	
	


	/* User Input + Uploads
	 *******************************************************************/
	$sg['upload']['allowed_types'] = 
	array('jpg',
		'png',
		'tif',
		'jpeg',
		'gif',
		'tiff',
		'bmp',
		'pdf',
		'doc',
		'xls',
		'ods',
		'odt',
		'rmk',
		'tar',
		'zip',
		'gz',
		'7z',
		'mp3',
		'avi',
		'wmv');
	


?>
