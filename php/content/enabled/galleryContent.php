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

class galleryContent extends Content{
	protected $text = '1;5'; //used to store gallery id and num pi cper row
	protected $gallery_id = -1;
	protected $pic_per_row = 5;
	protected $fragment_vars = array('text');
	
	protected $imagedir = 'images/gallery/';
	

	public function display(){
		$this->splitConfValues();

		$sql = SqlQuery::getInstance();	
		$query='select p.* from gallery_pictures p where gallery_id='.$this->gallery_id.' order by p.position asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		//only write if there are entries
		if(count($res)==0) return '<p class="error">No Pictures in this Gallery!</p>';
		
		//write table header
		$xhtml='<table class="standard"><tr>';
		
		//add pictures
		$nr=0;
		foreach($res as $r){
			++$nr;
			$xhtml.='<td><a href="/webcontent/'.$this->imagedir.$r['base_name'].'.jpg" rel="lightbox['.$this->gallery_id.']" title="'.$r['title'].'"><img src="/webcontent/'.$this->imagedir.$r['base_name'].'_thumb.jpg"></a></td>';
			if($nr % $this->pic_per_row == 0) $xhtml.='</tr><tr>';
		}
		$xhtml.='</tr></table>';

		return $xhtml;
	}
	
	protected function splitConfValues(){
		$tmp=explode(';', $this->text);
		$this->gallery_id=$tmp[0];
		$this->pic_per_row=$tmp[1];
	}
	
	public function edit_form_elements(){		
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
			
		$query_galleries = "select * from galleries";
		$definition = array('query'=>$query_galleries,'keys'=>array('id'),'values'=>array('name'));
		$elements['gallery_id'] = new DataSelect('Gallery: ','gallery_id',$definition,$this->gallery_id);
		
		$elements['pic_per_row'] = new Select('Pictures per Row: ', 'pic_per_row', array('1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, '10'=>10), $this->pic_per_row);
		
		return $elements;
	}
	
	public function process_edit($action = NULL){
		$form = $this->getForm('edit_form');
		$values = $form->getElementValues();		
		
		$this->__set('text',$values['gallery_id'].';'.$values['pic_per_row']); 		
	}

}
?>
