<?php

class TextContent extends Content{
	
	protected $text = 'new content';
	protected $fragment_vars = array('text');


	
	public function display()
	{
		return '<p class="'.$this->style_class.'" style="'.$this->style_info.'">'.$this->transform_text($this->text).'</p>';
	}
	
	public function transform_text($txt)
	{
		//TODO: problem with special chars used in linknames or filenames!!!
		return nl2br( self::tag_replace( htmlentities($txt,ENT_QUOTES,$GLOBALS['config']['charset']) ) );
	}
	
	//don't use 'submit' or 'preview' as element names!!!
	public function edit_form_elements(){
		
		$fr = $this->loadAdditionalFragments();
		
		$elements['hint'] = new HtmlElement('<div class="compare_text" style="margin-bottom: 1em;margin-top:1em;"><ul class=list style="margin-left: 0em;"><li>Use [b] and [/b] for bold.</li><li>Use [i] and [/i] for bold.</li><li>Use [list] and [/list] for a list and [item] and [/item] for each item in a list.</li></div>');

		$comp = array();
		foreach($fr as $v)
			$comp []= htmlentities($v['text'], ENT_QUOTES, mb_internal_encoding());
			
		$elements['comp'] = new HtmlElement('<div class="compare_text">'.implode('<hr></hr>',$comp).'</div>');	
		$elements['text'] = new Textarea('Text:','text',$this->text,15,40);
		
		
			$query_style = "SELECT s.id, s.class 
							FROM content_class_style ccs 
							JOIN style s ON s.id = ccs.style_id 
							WHERE ccs.content_class_id='{$this->content_class_id}'";
			$definition = array('query'=>$query_style,'keys'=>array('id'),'values'=>array('class'));
		$elements['style_id'] = new DataSelect('Style: ','style',$definition,$this->style_id); 
		
			$pagelinks = array();
			foreach($GLOBALS['page_index'] as $i=>$p)
				$pagelinks[$i] = $p->__get('uri');
				
		$elements['link'] = new Select('Insert new link: ','link',$pagelinks);
		$elements['ins_link'] = new Submit('action','Link','link');
		
			$files = array();
			foreach(scandir(DOWNLOADS_DIR) as $file){
				if($file != '..' && $file != '.')
					$files[$file] = $file;
			}
		if(!empty($files))
		{
			$elements['file'] = new Select('Insert new file:','file',$files);
			$elements['ins_file'] = new Submit('action','File','file');
			$elements['ins_img'] = new Submit('action','Image','image');
		}
		
		return $elements;
	}
	
	
	
	
	
	
	
	//action is only set if it's not submit or preview!
	public function process_edit($action = NULL)
	{
		$form = $this->getForm('edit_form');
		
		if (isset($action))
		{
			$txt = $form->getElementValue('text');
			echo 'action: ' . $action;
			switch($action)
			{
				case 'ins_link':
					$link_id = $form->getElementValue('link');
					$abb = $GLOBALS['page_index'][$link_id]->__get('abb');
					$link_tag = "[link=$link_id]{$abb}[/link]";
					$form->setValue('text',$txt.$link_tag);
					break;
				case 'ins_file':
					$file = $form->getElementValue('file');
					$file_tag = "[file={$file}]{$file}[/file]";
					$form->setValue('text',$txt.$file_tag);
					break;
				case 'ins_img':
					$file = $form->getElementValue('file');
					$file_tag = "[img={$file}]left[/img]";
					$form->setValue('text',$txt.$file_tag);
					break;
				default:
					throw new UserInputException('Action '.$action.' not excpected');
			}
			
			return $form;
		}
		else
		{

			$v = $form->getChangedElementValues();
			foreach($v as $key => $val)
			{
				echo $key.'=>'.$val;
				$this->__set($key,html_entity_decode(br2nl($val), ENT_QUOTES, mb_internal_encoding() )); //just in case someone thinks it's necessary to put br's
			}
		
			if(isset($v['style_id']))
				$this->style_class = $form->getElement('style_id')->getOptionValue($this->style_id);
		}
	}













	// AUXILARY FUNCTION:

	//TODO: make this one shorter and prettier!!!	
	protected static function tag_replace($str){
	 	global $cfg;
		global $menu;
		
		//I love regexp! it's just so easy to make changes!!
		
		//downloads:
		$patterns[0] = '/\[file=([^\]]+)\]([^\[]+)\[\/file]/';
		$replacements[0] = '<a href="'.DOWNLOADS_PATH.'$1">$2</a>';

		$niuwe = preg_replace($patterns, $replacements, $str);

		
		//internal links:
		$matches = array();
		$pattern = '/\[link=([^\]]+)\]([^\[]*)\[\/link]/';
		preg_match_all($pattern,$niuwe,$matches,PREG_PATTERN_ORDER);
		
		foreach($matches[0] as $i => $pat){
			if ( isset($GLOBALS['page_index'][$matches[1][$i]]) )
			{
				$url= $GLOBALS['page_index'][$matches[1][$i]]->uri;
				$title = empty($matches[2][$i]) ? $GLOBALS['page_index'][$matches[1][$i]]->abb : $matches[2][$i];
				$niuwe = str_replace($pat,'<a href="'.$url.'">'.$title.'</a>',$niuwe);
			}
			else
			{
				$niuwe = str_replace($pat,'(invalid link)',$niuwe);
			}
		} 
		
		//external links:
		$matches2 = array();
		$pattern2 = '/\[elink=([^\]]+)\]([^\[]*)\[\/elink]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){
			$title = empty($matches2[2][$i]) ? $matches2[1][$i] : $matches2[2][$i];
			$niuwe = str_replace($pat,'<a href="'.$matches2[1][$i].'" target="blank">'.$title.'</a>',$niuwe);
		}
		
		//images:
		$matches2 = array();
		$pattern2 = '/\[img=([^\]]+)\]([^\[]*)\[\/img]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){
			$float = empty($matches2[2][$i]) ? 'left' : $matches2[2][$i];
			$niuwe = str_replace($pat,'<img src="'.DOWNLOADS_PATH.$matches2[1][$i].'" class="'.$float.'"/>',$niuwe);
		}

		//bold:
		$matches2 = array();
		$pattern2 = '/\[b\]([^\[]*)\[\/b\]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){			
			$niuwe = str_replace($pat,'<b>'.$matches2[1][$i].'</b>',$niuwe);
		}

		//italic:
		$matches2 = array();
		$pattern2 = '/\[i\]([^\[]*)\[\/i\]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){
			$float = empty($matches2[2][$i]) ? 'none' : $matches2[2][$i];
			
			$niuwe = str_replace($pat,'<i>'.$matches2[1][$i].'</i>',$niuwe);
		}

		//lists:
		$matches2 = array();
		$pattern2 = '/\[list\]([^°]*)\[\/list\]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){
			$niuwe = str_replace($pat,'<ul class="list">'.$matches2[1][$i].'</ul>',$niuwe);
		}

		$matches2 = array();
		$pattern2 = '/\[item\]([^\[]*)\[\/item]/';
		preg_match_all($pattern2,$niuwe,$matches2,PREG_PATTERN_ORDER);
		
		foreach($matches2[0] as $i => $pat){
			$niuwe = str_replace($pat,'<li class="list">'.$matches2[1][$i].'</li>',$niuwe);
		}

		
		return $niuwe;
		
	 }




}

?>
