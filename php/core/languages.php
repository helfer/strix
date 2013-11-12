<?php
//LANGUAGE OBJECT:
/************************************************************************************************************************/

//This function does EVERYTHING that has to do with languages. If other objects can have language content, it MUST go through here.


/*
 * $_SESSION['language_order_id'] = array(0=>'1',1=>'4',2=>'2')
 * $_SESSION['language_id'] = 1;
 * $_SESSION['language_order_abb'] = array(0=>'de',1=>'en',2=>'fr')
 * $_SESSION['language_abb'] = 'de';
 */


 


class Language{	
	

	public static function simple_initialize_languages(){
		if (isset($_GET['lan']) && is_numeric($_GET['lan'])){
			$_SESSION['language_id'] = $_GET['lan'];
			notice('Language changed to '.$_GET['lan']);
			$_SESSION['language_set'] = TRUE;		
		}
				
		if(!isset($_SESSION['language_id']))
		{
			$_SESSION['language_id'] = 1;
			$_SESSION['language_set'] = FALSE;
		}
	}
	
	
	
	/* Initialize Languages:
	 * 1. look up session
	 * 2. if not defined, take browser preferences and fill values
	 * 3. treat GET input: check if language exists (id or abb) -> take it out of arrays -> put it in front of arrays + renumber key
	 */


	//TODO: clean up notification messages!
	public static function initialize_languages(){
		debug('determine_language');
		
		//1. Check if languages are set in session
		/*-----------------------------------------------------------*/

		if(!isset($_SESSION['language_id']))
			self::parseHtmlAcceptLanguages(); //sets all session language variables according to HtmlAccept input.

		
		//2. Treat $GET input (id OR abb) and adjust preferences. Redirect if page is set in language!!
		/*-----------------------------------------------------------*/
		if (isset($_GET['language']) && !isset($_GET['lan']))
			$_GET['lan'] = $_GET['language'];
		
		if (isset($_GET['lan'])) //TODO: also check for $_GET['language']??
		{ 
			$lan = $_GET['lan'];
			unset($_GET['lan']);
			
			if (in_array($lan,$_SESSION['language_order_id']) || in_array($lan,$_SESSION['language_order_abb']))
			{
				self::setPreferredLanguage($lan);
				$GLOBALS['language_set'] = TRUE;
			}
			else
			{
				//!!TODO: don't really throw one!
				notice('No such language id/abb : '.$lan);
				$GLOBALS['language_set'] = FALSE;
				return;
				//throw new InputException('No such language id/abb : '.$lan);	
			}
			
			
		}
		else
		{
			$GLOBALS['language_set'] = FALSE;
		}
		
		if($GLOBALS['language_set'])
			debug('Language is now '.$_SESSION['language_long']);

		
		
	}
	
	
	private static function parseHtmlAcceptLanguages(){
		$accept_languages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			
		//TODO: is this RFC ???? conform?
		foreach($accept_languages as $al)
			$lng[] = reset(explode('-',reset(explode(';',$al))));
			
		//array in order of preferences without duplicates
		$lng = array_unique($lng);
		
		//array of enabled languages (abb) found in request, in order of preference
		$accept_abb = array_intersect($lng, $GLOBALS['config']['languages_enabled']);
		
		//enabled languages which are not in httpaccept of request.
		$leftover_abb = array_diff($GLOBALS['config']['languages_enabled'],$accept_abb);
		
		//all enabled languages ordered.
		$final_abb = array_merge($accept_abb,$leftover_abb);
		
		//array of enabled languages (id) in order of preference
		$final_id = array_map(array('Language','abb2id'),$final_abb);
		
		debug($final_id);
		debug($final_abb);
		
		$_SESSION['language_order_id'] = $final_id;
		$_SESSION['language_id'] = $final_id[0];
		$_SESSION['language_order_abb'] = $final_abb;
		$_SESSION['language_abb'] = $final_abb[0];
		$_SESSION['language_long'] = self::id2long($final_id[0]);
		
		
		return;

		
	}
	
	//extracts the language with id $id and puts it in front.
	//you can call with both id OR abb of language.
	public function setPreferredLanguage($id)
	{
		if(!is_numeric($id))
			$id = self::abb2id($id);
			
		if($_SESSION['language_id'] == $id)
			return notice('no language change');
		
		$order_id =& $_SESSION['language_order_id'];
		$order_abb =& $_SESSION['language_order_abb'];
		
		$offset = array_search($id,$order_id);

		$splice_id = array_splice($order_id,$offset,1);
		
		$splice_abb = array_splice($order_abb,$offset,1);
		
		/*
		print_r($splice_id);
		print_r($splice_abb);
		print_r($order_id);
		print_r($order_abb);
		*/
		
		array_unshift($order_id,$splice_id[0]);
		array_unshift($order_abb,$splice_abb[0]);
		
		$_SESSION['language_id'] = $order_id[0];
		$_SESSION['language_abb'] = $order_abb[0];
		$_SESSION['language_long'] = self::id2long($order_id[0]);
		
		debug('language order changed to:');
		debug($order_abb);
		notice('language changed to '.$_SESSION['language_long']);
		
	}

	////////////////////////////////////////////////////////////////////	
	
	
	public static function abb2id($abb)
	{
		$flip = array_flip($GLOBALS['config']['languages_enabled']);
		return $flip[$abb];
	}
	
	public static function id2abb($id)
	{
		return $GLOBALS['config']['languages_enabled'][$id];	
	}
	
	public static function id2long($id)
	{
		return $GLOBALS['config']['languages_long'][$id];
	}
	
	//makes a string for language preference ordering in mysql queries.
	//Smaller values returned by mysql mean preferred language. [use MIN()]
	//example return value: (page_fragments.language_id = <id of favourite>)*0 + (page_fragments.language_id = <id of 2nd fav>)*1
	public static function langPrefMySqlString($field,$override_field = 0,$override_val = 0)
	{
		$arr = array();
		foreach ($_SESSION['language_order_id'] as $key=>$lid)
		{
			$arr []= " ($field = $lid)*$key ";
		}
		//debug_print_backtrace();
		//TODO: look into this again.
		//notice($override_field.' '.FORCE_GET_LANGUAGE.' '.$GLOBALS['language_set']);
		
		//URI language override means language of override_field overrides everything else
		if($override_field && !(FORCE_GET_LANGUAGE && $GLOBALS['language_set']))
			$arr []= " ($override_field <> '$override_val')*10000";
			//notice('bidou');	
		
		
		return implode('+',$arr) . " + 0.01*".$override_field;
	}
	
	
	
	/* Static language functions
  	 **************************************************************/
	
	
	//very useful for language-customization of non-DB classes
	public static function getGlobalTag($class,$name = '')
	{
		if(!is_string($class))
			$class = get_class($class);
			
		if	(empty($name))
			return $GLOBALS['tag_array'][$class]; //returns all languages and all fields!!!
		else
			return Language::extractPrefLan($GLOBALS['tag_array'][$class][$name]);	
	}	
	
	//input: array keys must be language_ids
	//extracts the most favoured language of the user out of the array
	public static function extractPrefLan($arr)
	{
		if(empty($arr))
			return 'error: no language to extract';
		
		//print_r($_SESSION['language_order_id']);
		//print_r($arr);
		
		foreach ($_SESSION['language_order_id'] as $i=>$lan_id)
		{
			if (isset($arr[$lan_id]))
				return $arr[$lan_id];
		}
		
		//solves the problem of english errors on pages only in other languages.
		return reset($arr);
	}
	

	
	
	//TODO: clean up the following fuzz!!


	/*TODO: idea: have possibly untagged and tagged content... determine if content is tagged by having "<!ltags>" at beginning.
	example: <!ltags> A B C D ... <lang=de>Das Alphabet</de><lang=fr>L'alphabet</fr> ... X Y Z <lang=de>Ende</de><lang=fr>Fin</fr>
	Problem: more difficult and for the moment unnecessary.

	/*
	 * This function splits a language-tagged string into languages and returns an array with the language-ids as keys
	 * The default language is returned under key 0.
	 * Input: e.g. "<Lang=de>Guten Tag</de><Lang=fr>Bonjour</fr><Lang=en>Hello</en>"
	 * Output: array ( "de" => "Guten Tag", "fr" => "Bonjour", "en" => "Hello", 0 => "Guten Tag")
	 *********************************************************************/
	public static function split_languages($text){
		//cannot contain any language tags
		//TODO: only being nice here for uppercase L (substr)
		if(strlen($text) < 10 || substr($text,2,4) != 'ang=')
			return array();
	
		//TODO: only being nice here for uppercase L
		$regexp = "/\<[l|L]ang=([\w]+)\>(.*?)\<\/lang\>/s";
		$matches = array();
		preg_match_all($regexp,$text,$matches, PREG_PATTERN_ORDER );

		if(sizeof($matches[0]) == 0) //if no match was found, return emtpy array
			error('functions.splitlanguages: invalid tags found in: '.$text);
			
		$res = array();
		foreach($matches[1] as $i=>$v) $res[$v] = $matches[2][$i];
		
		return $res;
	}
	
	/* This function reverses the split_languages function.
	 * Input: array ('klingon' => 'content', ...)
	 * Output: String "<Lang=klingon>content</klingon>..."
	 ******************************************/
	public static function pack_languages($lang_array){
		$packed = '';
		foreach($lang_array as $lang => $content) $packed .= "<lang=$lang>$content</lang>";
		return $packed;
	}
	
	/*
	 * Simple function to facilitate language extraction in tagged strings.
	 * Automatically selects the preferred language from the tagged text. If it doesn't exist,
	 * the function returns the first language found.
	 * Will return FALSE if the content was not tagged for languages.
	 ********************************************************************************/
	public static function extract_language($text,$lan = 7){
		if($lan === 7) $lan = $_SESSION['lan'];
		$languages = self::split_languages($text);
		
		if(sizeof($languages) === 0)
			return FALSE;
		
		if(isset($languages[$lan]))
			return $languages[$lan];
		else
			return array_pop($languages); //will return last language found
	}
	
	
	 /* Almost like extract_language, but will mark text if language does not correspond to $lan
	 *****************************************/
	public static function extract_mark_language($text,$lan = 7){
		if($lan === 7) $lan = $_SESSION['language_id'];
	
		if($select = self::select_language($text,$lan))
			return $select;
		else
			return '<em style="color:red">'.self::extract_language($text,$lan).'</em>';
	}
	

	 /* Almost like extract_language, but input defines language. Returns FALSE if langauge not found in text
	 *****************************************/
	public static function select_language($text,$lan){
		$languages = self::split_languages($text);
		
		if(isset($languages[$lan]))
			return $languages[$lan];
		else
			return FALSE;
	}
	
	

	/*Sets the defined language in string $text to $new
	 *Text: the whole text
	 *New: the part to update
	 *Lang: language of new part
	 **********************************/
	public static function update_language($text,$new,$lang){
		$arr = array();
		
		if($text != '') $arr = self::split_languages($text);

		$arr[$lang] = $new;
		print_r($arr);
		print_r(self::pack_languages($arr));
		print_r(self::split_languages(self::pack_languages($arr)));
		
		return self::pack_languages($arr);
	}
	
	/* Reverse functionality. Returns array of all languages that contatin exactly $piece
	 * Useful for url-language matching...
	 **************************************/
	public static function match_language($piece,$text){
		$languages = self::split_languages($text);
		return array_keys($languages,$piece);
	}

}
?>

