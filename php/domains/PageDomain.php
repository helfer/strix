<?php
/**
 * @version 0.5
 * @brief Serves html pages to user.
 * 
 * This domain finds the page requested by the user and returns it, if the
 * user has the necessary permissions. It tells the page to load
 * and sets up the basic environment
 * for the page, which contains a pagetree, the content-tree and a page- and
 * content-array indexed by id.
 * 
 * @todo remove the input handling completely
 * @todo implement caching
 * @todo include forms + tables only when necessary. Link them in ENABLED and improve __autoload
 * @todo move the error checking etc. from get_page_vars to the main function.
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-25
 */
class PageDomain extends Domain
{
	
	public function serveContent($resource){
		
		/* INCLUDES -------------------------------------------------*/
		///@todo is this the right place to include languages??
		require_once(SCRIPT_DIR.'core/languages.php');

		
		if(empty($_GET['vmode']))
			$_GET['vmode'] = VMODE_NORMAL;

		$user = $GLOBALS['user'];

		//determines the language the page should be served in.
		Language::initialize_languages();

		//fetch page information from database
		$page_vars = $this->get_db_page($resource,$user);
		
		
		if( empty( $page_vars ) )
		{
			return array('type'=>'error','output'=>'No page returned. Error page missing?');
		}
		
		if ($page_vars['language_id'] != $_SESSION['language_id'])
		{
			notice('Page not found in your language');
			
			if (URL_SETS_PREF_LANGUAGE && !(FORCE_GET_LANGUAGE && $GLOBALS['language_set']))
				Language::setPreferredLanguage($page_vars['language_id']);
		}
		
		//TODO: this is not really necessary!!! just build it into the error page!
		if ($page_vars['id'] == ERROR_PAGE_ID)
		{
			//$GLOBALS['RequestHandler']->setHttpHeader('HTTP/1.0 404 Page Not Found');
			return array('type'=>'redirect','output'=>'/');
		}
		else if ($page_vars['uri'] != $resource && (REDIRECT_ON_URI_MISMATCH || (FORCE_GET_LANGUAGE && $GLOBALS['language_set'])))
		{
			$redirect_uri = 'http://' . $GLOBALS['request_host'] . substr($this->prefix , 0 , strlen($this->prefix)-1 ) . $page_vars['uri'];

			$redirect_uri .= '?'.$_SERVER['QUERY_STRING'];
				
			return array('type'=>'redirect','output'=>$redirect_uri);
		}
		
		
		if (isset($_GET['cache']))
			throw new NanoException('this is where we would load from cache');
		
		//------------------------------------------------------------//
		require_once(SCRIPT_DIR.'core/trees.php');
		require_once(SCRIPT_DIR.'content/boxes.php');
		require_once(SCRIPT_DIR.'content/content.php'); //TODO: split up content_classes.php
		require_once(SCRIPT_DIR.'content/Page.php');
		
		///@todo: only to include when really needed!
		require_once(SCRIPT_DIR.'content/utils/forms/form.php');
		require_once(SCRIPT_DIR.'content/utils/forms/form_elements.php');
		require_once(SCRIPT_DIR.'content/utils/forms/form_restrictions.php');
		require_once(SCRIPT_DIR.'content/utils/table.php');
		
		$this->makePagetree();
		
		///@bug THIS doesn't work with the error page, it's not in the tree, thus not in the index.
		$page = new Page($page_vars);
		$page->loadAdditionalFragments();
				
		$GLOBALS['current_language'] = $page->language_id;
		$GLOBALS['page'] = $page;
		
		$page->fetch_content();
		$page->pass_input();
	
	
		//GENERATE PAGE:
		$head = $page->getHeader();
		$body = $page->display();
		
		///@todo shouldn't it be the page that prints the notices???
		if($GLOBALS['config']['show_notices'])
			$body = '<div class="notices">'.$GLOBALS['notices'].'</div>' . $body;
		
		
		return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));
	}
	
	
	///@todo: should always return, even in the case of a redirect!
	protected function get_db_page( $resource , $user )
	{
		
		//QUERY: returns page matching the uri exactly in the language of the session. If page not in that language,
		//the language with the closest id is returned. RETURNS ONLY ONE ROW (= PAGE) !!!
		
		//append / if not already there. (yeah I know, its a nasty trick...)
		$uri_slash = str_replace('//', '/', $resource . '/');

		debug('Looking for page' . $resource);
		
		///@todo CAN CUT DOWN THIS QUERY!! all we need to know here is: does it exist? id? name? cacheable? cache_upd??
		///@todo Correlated subqueries are bad as well!
		$query =	"SELECT 
						p.* ,
						pf.title AS title, 
						pf.abb,
						pf.language_id,
						pf.uri,
						pf.folder_name,
						SUM(DISTINCT pgp.permission) as permission,
						".Language::langPrefMySqlString('pf.language_id','pf.uri',$uri_slash)." as lq
					
					FROM `page` p 
					JOIN `page_fragment`pf ON pf.page_id = p.id 
					JOIN `pagegroup_permission` pgp ON p.`pagegroup_id` = pgp.`pagegroup_id` 
					JOIN `usergroup` ug ON ug.`id` = pgp.`usergroup_id` 
					JOIN `user_in_group` uig ON uig.`usergroup_id` = ug.`id` 
					WHERE 
						uig.user_id='".$user->__get('id')."' 
						AND pgp.permission > 0 
						AND (
							pf.page_id IN ( SELECT page_id FROM page_fragment WHERE uri LIKE '$uri_slash' )  
							OR page_id = ".ERROR_PAGE_ID." 
							) 
					GROUP BY p.id, pf.language_id 
					ORDER BY 
						page_id DESC,
					 	lq ASC
					 LIMIT 1";
		
		$page_vars = SqlQuery::getInstance()->singleRowQuery($query);
		
		return $page_vars;
	}	


	protected function makePagetree()
	{
		$user = $GLOBALS['user'];
		$language_id = $_SESSION['language_id'];
		
		$query ="SELECT 
					p.* ,
					pf.title AS title,
					pf.abb AS abb, 
					pf.language_id,
					pf.uri, 
					pf.folder_name,
					'Page' as object_type,
					SUM(DISTINCT IF(uig.user_id IS NOT NULL, pgp.permission,0) ) as permission
				
				FROM `page` p 
				JOIN `page_fragment`pf ON pf.page_id = p.id 
				JOIN `pagegroup_permission` pgp ON p.`pagegroup_id` = pgp.`pagegroup_id` 
				LEFT JOIN `usergroup` ug ON ug.`id` = pgp.`usergroup_id` 
				LEFT JOIN `user_in_group` uig ON uig.`usergroup_id` = ug.`id` AND uig.user_id='".$user->__get('id')."'
				LEFT JOIN `page_fragment` pf2 
						ON pf2.page_id = p.`id` 
						AND ".Language::langPrefMySqlString('pf2.language_id','pf2.language_id',$language_id)." 
							< ".Language::langPrefMySqlString('pf.language_id','pf.language_id',$language_id)."
				WHERE  
					p.`left` > 0 AND p.`right` > 0
					AND pf2.`language_id` IS NULL
				GROUP BY p.id, pf.language_id 
				ORDER BY 
					p.left ASC,
					p.right ASC
				";
		
		$answer = SqlQuery::getInstance()->simpleQuery($query);
//print_r($answer);
		
		//TODO: why do I need this if? must check this in tree node class!!!
		if(sizeof($answer) > 0){
			$pagetree = Tree::make_index_tree_array($answer,$GLOBALS['page_index']);	
			$GLOBALS['pagetree'] = $pagetree[0];
		}
		
		debug($GLOBALS['pagetree']);
	
	}
	
}
?>
