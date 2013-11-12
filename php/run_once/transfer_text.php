<?php
/*
 *      transfer_text.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
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
 */


class TransferContent extends TextContent
{
	protected $content_class_id = 1; //TEXT;
	
	public function getTableName(){return 'contentT';}
	
	public function __construct($vars)
	{
		parent::__construct($vars);
		$style_convert = array('text'=>9,'title'=>13,'subtitle'=>14);
		$this->style_id = $style_convert[$this->style_class];
		
		if($this->page_id == -1)
			$this->page_id = GLOBAL_PAGE_ID;
		else
			$this->page_id += 10;
		
		$this->box_id = 7; //central content_box
		
		$matches = array();
		$pattern = '/\[link=([^\]]+)\]([^\[]+)\[\/link]/';
		preg_match_all($pattern,$this->text,$matches,PREG_PATTERN_ORDER);
		foreach($matches[0] as $i => $pat)
			$this->text = str_replace($pat,'[link='.($matches[1][$i]+10).']'.$matches[2][$i].'[/link]',$this->text);

		$this->text=br2nl($this->text);
		$this->text = str_replace("\n\n","\n",$this->text);
		$this->text=html_entity_decode($this->text,ENT_QUOTES,'UTF-8');
		
		$this->id = NULL;
		
		$this->temp_waround_persist();
		$spx = split_languages($this->text);
		print_r($spx);
		unset($spx[0]);
		
		foreach($spx as $lang=>$txt)
		{
			if($lang !== 'de' && $lang !== 'fr')
			{
				error($this->id.' '.$txt);
				continue;
			}		
			$con = array('de'=>1,'fr'=>'2');
			$lan_id = array_search($lang,$GLOBALS['config']['languages_available']);
		
			//$this->mAdditionalFragments[$lan_id]['text'] = $txt;
			echo('id= '.$this->id.' fragment: '.$lang.' '.$lan_id.' text= '.$txt.'<br />');
			$this->addAdditionalFragment($lan_id,array('text'=>$txt));
		}

		$this->storeAdditionalFragments();
		//echo $this->transform_text($this->text);
		
	}
	
	
	public function print_dbvars()
	{
		//print_r($this->db_vars);
		$r = array('<hr></hr><br />id = '.$this->id);
		$r []= '<b>db:</b>';
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
	
	public function display()
	{
		return $this->print_dbvars();
	}

}


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



//SGRIBEDISGRIBBT:

$sql = SqlQuery::getInstance();

$old_content = $sql->simpleQuery("SELECT *, 'TansferContent' as object_type 
								FROM `ext_ibosuisse1`.`content` 
								WHERE content_type = 'text'
									AND page_id <> -1
								ORDER BY `page_id`,`left`");

$carr = array();
foreach($old_content as $i=>$v)
	$carr[$i] = new TransferContent($v);


foreach($carr as $o)
	echo $o->print_dbvars();



?>
