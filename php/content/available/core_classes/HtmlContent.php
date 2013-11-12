<?php
/*
 *      htmlcontent.php
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


class HtmlContent extends Content
{
	protected $text = 'new html content';
	protected $fragment_vars = array('text');
	
	
	
	public function display()
	{
		return $this->text;	
	}
	
	public function edit_form_elements()
	{
		$ret['text'] = new Textarea('Html Code: ','text',$this->text,15,40);
		return $ret;	
	}
	
	public function process_edit($action = NULL)
	{
		$txt = $this->getActivatedForm()->getElementValue('text');
				echo $txt;
		$this->__set('text',$txt);
	}
	
}

?>
