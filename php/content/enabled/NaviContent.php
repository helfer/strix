<?php
/*
 *      navicontent.php
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

class NaviContent extends Content{

	protected $style_id=11; //hnavi
	protected $mConfKeys=array('min_level','max_level');
	protected $mConfValues=array(1,1);

	public function display(){
		$xhtml = '';
		
		$root = $GLOBALS['pagetree'];
		//$xhtml .= '<a href="'.$root->uri.'">'.$root->abb.'</a>&nbsp;';
		if($this->mConfValues['min_level']==2){
			//if($GLOBALS['page_index'][$GLOBALS['page']->left]>0){
				//$f=$GLOBALS['page_index'][$GLOBALS['page']->id]->get_ancestors();
				//$xhtml.="<div class='colored_background_div_navi'><a class='colored_background_div_title_navi' href='".$GLOBALS['page_index'][$f[1]]->uri."'>".$GLOBALS['page_index'][$f[1]]->abb."</a></div>";
				//$xhtml.="<div class='colored_background_div_navi'></div>";
			//}
		}
		$xhtml .= '<ul class="'.$this->style_class.'">'."\n";
		
		//TODO: this is a workaround for $GLOBALS['page'] not being the same as in the tree;
		//TODO: and another fix for the error page!
		if(isset($GLOBALS['page_index'][$GLOBALS['page']->id]))
			$open_nodes = $GLOBALS['page_index'][$GLOBALS['page']->id]->get_ancestors();
		else
			$open_nodes = array($GLOBALS['pagetree']->__get('id'));
				
		//print_r($open_nodes);
		return $xhtml.$this->rec_navi($root,0,$open_nodes,$this->mConfValues['min_level'],$this->mConfValues['max_level']).'</ul>';
	}
	
	
	protected function rec_navi($root,$level=0,$open_nodes = array(), $min_level = 0,$max_level = 100)
	{
		$xhtml = '';
		 
		if($level >= $min_level)
		{
			if($root->id == $GLOBALS['page']->id || in_array($root->id,$open_nodes))
				$xhtml .= '<li class="here"><a href="'.$root->uri.'">'.$root->abb.'</a></li>'."\n";
			else
				$xhtml .= '<li><a href="'.$root->uri.'">'.$root->abb.'</a></li>'."\n";
		}
		
		if($level < $max_level && in_array($root->id,$open_nodes))
		{
			$kids = $root->get_kids();
			if(empty($kids))
				return $xhtml;
			
			//count kids with permission
			$n=0;
			foreach($kids as $page){
				if($page->permission != 0) ++$n;				
			}
			
			if($level >= $min_level && $n>0)
				$xhtml .= '<ul class="'.$this->style_class.'">'."\n";
				
			foreach($kids as $page)
			{
				if($page->permission != 0)
					$xhtml .= $this->rec_navi($page,$level+1,$open_nodes,$min_level,$max_level);
			
			}
			if($level >= $min_level && $n>0)
				$xhtml.='</ul>'."\n";
		}
		
		return $xhtml;
	}

}
?>
