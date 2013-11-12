<?php


$sql = SqlQuery::getInstance();
/**
$p_vars = $sql->simpleQuery("SELECT *, page_group_id as pagegroup_id FROM `ext_ibosuisse1`.`pages`");

$pages = array();
foreach($p_vars as $p)
	$pages []= new OldPage($p);
	
foreach($pages as $p)
	echo $p->print_dbvars().'<br /><br />';


//PAGETREE::
/**/
$page_array = $sql->simpleQuery("SELECT *, 'UriUpdatePage' as object_type FROM  `page3` WHERE `left` > 0 ORDER BY `left`");

//print_r($page_array);

if(sizeof($page_array) > 0){
	$pagetree = Tree::make_tree_array($page_array);	
	$root = $pagetree[0];
	echo Tree::print_tree($root,'name');
	//print_r($root);
	$root->update_uri(array());
}
else
{
	error('failed to update uri');
}
		
/**/

class UriUpdatePage extends Page
{
	public function getTableName(){return 'page3';}
	
	
	
	/* Updates its own url and the one of its kids 
	 * $prefix is a _language-encoded_ prefix! array(1=>'SBO',2=>'OSB',3=>'ZBO', etc.)
	 **********************************************/
	public function update_uri($prefixes){
		$fragm = $this->loadAdditionalFragments();
		$pass = array();
		
		if(NULL !== $prefixes) //if prefixes = NULL ,only update kids!
		{
			foreach($fragm as $l_id=>$values)
			{
				if(!empty($prefixes[$l_id]))
				{
					$pass[$l_id] = $prefixes[$l_id].$values['folder_name'].'/';
				}
				else
				{
					//TODO: this is a crappy policy!!!
					if(isset($this->parent))
						$pass[$l_id] = $this->parent->uri.$values['folder_name'].'/';
					else
						//needed a fix for empty folder names.
						$pass[$l_id] = empty($values['folder_name']) ? '/' : '/'.$values['folder_name'].'/';
						
				}
				$this->mAdditionalFragments[$l_id]['uri'] = $pass[$l_id];

			}
		}
		
		notice('id = '.$this->id.' name= '.$this->name);
		notice($this->mAdditionalFragments);
		
		foreach($this->mAdditionalFragments as $l=>$v)
			$pass[$l] = $this->mAdditionalFragments[$l]['uri'];
			
		$this->storeAdditionalFragments();
			
		//print_r($pass);

		foreach($this->kids as $kid)
			$kid->update_uri($pass);

	}/**/
	
}


class OldPage extends Page
{
	
		protected $db_vars = array(	'id',
								'left',
								'right',
								'name',
								'pagegroup_id',
								'box_structure_id',
								'cacheable',
								'cache_update');

	public function getTableName(){return 'page3';}
	
	public function __construct($vals)
	{
		foreach($vals as $k=>$v) 
			$this->$k=$v;
			
		$this->id = $this->id+10; //to keep it simple but straight.
		
		$pg_conversion_table = array('1'=>'1','2'=>'4','3'=>'5','4'=>'2','5'=>'6','6'=>'7');
		$this->pagegroup_id = $pg_conversion_table[$this->pagegroup_id];
		
		$titles = split_languages($this->title);
		
		$fragment = array();
		foreach($titles as $lan=>$title)
		{
			//THIS is VERY custom, don't ever use it again if you don't know for sure that it's ok.
			if (!empty($title) && $lan_id = array_search($lan,$GLOBALS['config']['languages_available'])){
				if($lan == 'de' || $lan == 'fr')
				{
					$ti = rreplaceUmlaut($title,TRUE);
					if($ti == 'NNF')
					{
						$fragment[$lan_id] = array('title'=>'Home',
													'abb'=>'Home',
													'folder_name'=>'');
						$this->name = 'Home';
					}
					else
					{
						$fragment[$lan_id]['title'] = $ti;
						//$fragment[$lan_id]['uri'] = str2url($ti);
						$this->name = str2url($ti).$this->id;
						$fragment[$lan_id]['abb'] = $ti;
						$fragment[$lan_id]['folder_name'] = str2url($ti);
					}	
				}
			}
		}

		
		$this->temp_waround_persist(); //TODO: take that one out again!
		notice('id = '.$this->id);
		
		foreach($fragment as $l_id=>$values)
		{
			notice($l_id.'=>'.implode(', ',$values));
			//$this->mAdditionalFragments[$l_id] = $values;
			$this->addAdditionalFragment($l_id,$values);
		}

	}
		
	public function print_dbvars()
	{
		//print_r($this->db_vars);
		$r = array('<b>db:</b>');
		foreach($this->db_vars as $n)
			$r []= $n.'=>'.$this->$n;
			
		$r []= '<b>fragments:</b>';
		foreach($this->fragment_vars as $n)
			$r []= $n.'=>'.$this->$n;
			
		$r []= '<b>additional:</b>';
		foreach($this->mAdditionalFragments as $lan=>$vals)
			$r []= $lan.'=>'.implode(', ',$vals);
			
		return implode("<br />\n",$r);
		return implode("\n",$r);
	}

}



//UTILS: 

//split languages

	function split_languages($text)
	{
		//TODO: only being nice here for uppercase L
		$regexp = "/\[[l|L]ang=([\w]+)\](.*?)\[\/lang\]/s";
		$matches = array();
		preg_match_all($regexp,$text,$matches, PREG_PATTERN_ORDER );
		
		//echo "matches:\n";
		//print_r($matches);
		
		if(sizeof($matches[0]) == 0){ //if no match was found, return emtpy array
			error("no language content found in $text");
			//TODO: for emtpy text, just return empty text
			return array('de'=>'NNF','fr'=>'NNF');
			return array( 0 => "untagged: $text"); 
		}
		//print_r($matches);
		$bruno = array();
		foreach($matches[1] as $i=>$v) $bruno[$v] = $matches[2][$i];
		//TODO: possiblilty to define the default lang? but what, if it doesn't exist?
		$bruno[0] = $matches[2][0]; //put the first language found at key 0 (default language)
		//echo pack_languages($bruno);
		
		//echo "bruno:\n";
		//print_r($bruno);
		
		return $bruno;
	}

//split_n_code !!!



//umlaut

function rreplaceUmlaut($text){
   return help_replace_umlaut($text,TRUE);
}

function help_replace_umlaut($text,$reverse = false){
	//first replace 'strange' encoding
	//echo '<br>IN: '.$text;
	$char1a=array('Ã¤','Ã¶','Ã¼','Ã','Ã','Ã', 'Ã©', 'Ã¨', 'Ã ', 'Ã&nbsp;', 'Ã', 'Ã', 'Ã', 'Ã¢', 'Ãª', 'Ã', 'Ã', 'Ã§', '\'', '"', '');
   	$char1b=array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&eacute;', '&egrave;', '&agrave;', '&Eacute;', '&Egrave;', '&Agrave;', '&Agrave;', '&acirc;', '&ecirc;', '&Acirc;', '&Ecirc;', '&ccedil;', '&#039;', '&quot;', '&acute;');


	$char2a=array('ä','ö','ü','Ä','Ö','Ü', 'é', 'è', 'à', 'É', 'È', 'À', 'â', 'ê', 'Â', 'Ê', 'ç','ï','î', 'ì','ù','ô','ë', 'Ï','Î', 'Ì','Ù','Ô','Ë', '\'', '"', '');
   	$char2b=array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&eacute;', '&egrave;', '&agrave;', '&Eacute;', '&Egrave;', '&Agrave;', '&acirc;', '&ecirc;', '&Acirc;', '&Ecirc;', '&ccedil;', '&iuml;', '&icirc;', '&igrave;', '&ugrave;', '&ocirc;', '&euml;', '&Iuml;', '&Icirc;', '&Igrave;', '&Ugrave;', '&Ocirc;', '&Euml;','&#039;', '&quot;', '&acute;'); 

	if($reverse)
		 return str_replace($char2b, $char2a, $text);
	else {		
		//$text=str_replace($char1a, $char1b, $text);		
		return str_replace($char2a, $char2b, $text);
		
	}
}


//url

	 function str2url($str){
	 	if(is_array($str)){
	 		//print_r($str);
	 		return array_map("str2url",$str);
	 	}
	 	$start = $str;
	 	$in = rreplaceUmlaut($str);
	 	
		$old = array('ä','ö','ü','ç','é','è','à','ê','â','ô','ï','î',"'",'/',' ');		 
		$new = array('ae','oe','ue','c','e','e','a','e','a','o','i','i','','or','_');
		$str2=str_replace($old,$new,$in);
		
		$old2 = array('Ã¤', 'Ã¶', 'Ã¼', 'Ã§', 'Ã©', 'Ã¨', 'Ã ', 'Ã¢', 'Ãª', 'Ã´', 'Ã¯', 'Ã®');		 
		$new2 = array('ae','oe','ue','c','e','e','a','e','a','o','i','i');
		$out =  str_replace($old2,$new2,$str2);
		
		/*$old3 = array('&auml;', '&ouml;', '&uuml;', '&eaigu;', 'Ã©', 'Ã¨', 'Ã ', 'Ã¢', 'Ãª', 'Ã´', 'Ã¯', 'Ã®');		 
		$new3 = array('ae','oe','ue','c','e','e','a','e','a','o','i','i');
		$out =  str_replace($old3,$new3,$str3);*/
		 
		 		 
		/*if($_SESSION['user']->user_group_id == 2)
			echo 'old: '.$start.' new: '.$out." <br />\n";
		 	*/
		 	
		return $out;
	 }

?>
