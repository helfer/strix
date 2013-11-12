<?php
/*
 *      tabcontent.php
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
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

abstract class TabContent extends Content
{

	protected $meTabs = array("tab1"=>'by user','tab2'=>'by group');
	protected $meDefaultTab = '';
	
	function display()
	{
		$xhtml = '';

			
		$sel_tab = $this->get_selected_tab();
		
		
		//print tabs:
		$tab_xml = array();
		foreach($this->meTabs as $name=>$title)
		{
			if($sel_tab != $name)
				$tab_xml []= '<a href="?tab'.$this->id.'='.$name.'">'.$title.'</a>';
			else
				$tab_xml []= '<span>'.$title.'</span>';
				
		}
		
		$xhtml .= '<div class="tabs">'.implode(' | ',$tab_xml).'</div>';
			
		//get+ print tab content
		$xhtml .= $this->$sel_tab();	
			
		return $xhtml;
	}	
	
	
	public function get_selected_tab($query_string = FALSE)
	{
		//verification
		if(empty($this->meTabs))
			throw new Exception('no tabs defined');
		
		//verification
		if(empty($this->meDefaultTab))
			$this->meDefaultTab = reset(array_keys($this->meTabs));
			
		//set selected tab
		if(isset($_GET['tab'.$this->id]) && in_array($_GET['tab'.$this->id],array_keys($this->meTabs)))
			$sel_tab = $_GET['tab'.$this->id];
		else
			$sel_tab = $this->meDefaultTab;
			
		if($query_string)
			return 'tab'.$this->id.'='.$sel_tab;
		else
			return $sel_tab;
	}
	
}

?>
