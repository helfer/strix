<?php
/*
 *      languageselectcontent.php
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
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
define('LANG_SEPAR',' | ');

class LanguageSelectContent extends Content{

	public function display()
	{
		
		$languages = $GLOBALS['config']['languages_enabled'];
		//$languages = array_combine($_SESSION['language_order_id'],$_SESSION['language_order_abb']);
		$langs = array();
		foreach($languages as $id=>$abb)
		{
			$f = $GLOBALS['page']->getadditionalFragment($id);
			$uri = empty($f['uri']) ? './' : $f['uri'];
			
			if($id == $_SESSION['language_id'])
				$langs []= '<li class="selected_language">'.$abb.'</li>';
			else
				$langs []= '<li><a href="'.$uri.'?lan='.$abb.'" title="'.$GLOBALS['config']['languages_long'][$id].'">'.$abb.'</a></li>';
		}
		return '<ul class="language_switch">'.implode(htmlentities(LANG_SEPAR,ENT_QUOTES,'UTF-8'),$langs).'</ul>';
	}
}

?>
