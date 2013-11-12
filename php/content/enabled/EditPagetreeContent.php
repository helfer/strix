<?php
/*
 *      editpagetreecontent.php
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

class EditPagetreeContent extends Content{

	public function display()
	{
		//this is just ridiculous
		return '<div class="pagetree">'.$this->getForm('edit_pagetree_form',NULL)->getHtml($this->id).'</div>';
	}
	
	
	public function process_pagetree_edit($input)
	{
		list($action,$id) = explode('_',$input);
		
		notice('PAGETREE PROCESSING: '.$action.' on '.$id);
		
		switch($action)
		{
			case 'b.del':
				$ok = $GLOBALS['page_index'][$id]->suicide();
				$this->remakeForm('edit_pagetree_form',NULL); //will redraw the form
				return $ok ? 'page deleted' : 'could not delete';
				break;
			case 'b.up':
				$GLOBALS['page_index'][$id]->move_up();
				$GLOBALS['pagetree']->reposition_self(1); //TODO: this is just a patch for a bug in tree transfer!
				$this->remakeForm('edit_pagetree_form',NULL); //will redraw the form
				break;
			case 'b.down':
				$GLOBALS['page_index'][$id]->move_down();
				$GLOBALS['pagetree']->reposition_self(1); //TODO: this is just a patch for a bug in tree transfer!
				$this->remakeForm('edit_pagetree_form',NULL); //will redraw the form
				break;
			case 'b.ins':
				$parent = $GLOBALS['page_index'][$id];
				$p = new Page(array('id'=>NULL,
									'name'=>'newpage',
									'abb'=>'',
									'title'=>'',
									'uri'=>'',
									'pagegroup_id'=>UNDER_CONSTRUCTION_PG,
									'box_structure_id'=>$parent->box_structure_id,
									'language_id'=> 1 //$parent->language_id
									));
									
				//order is important!					
				
				if($p->store())
				{
					$GLOBALS['page_index'][$p->id] = $p;
					$this->remakeForm('edit_pagetree_form',NULL); //will redraw the form
					$parent->insert_kids($p);
					$GLOBALS['pagetree']->reposition_self(1);
				
					$id = $p->id;
					//NO BREAK HERE!
				}
				else
				{
					error('Internal error: Could not store page in database.');
					break;
				}
				//NO BREAK HERE!
			case 'b.edit':
				return $this->edit_page_form(array($id))->getHtml($this->id); //array($id) is the vector!
				break;
			case 'b.close':
				$treeVector = $this->getForm('edit_pagetree_form')->getVector();
				$k = array_search($id,$treeVector);
				unset($treeVector[$k]);
				$this->remakeForm('edit_pagetree_form',$treeVector);
				break;
			case 'b.open':
				$treeVector = $this->getForm('edit_pagetree_form',NULL)->getVector();
				array_push($treeVector,$id);
				$this->remakeForm('edit_pagetree_form',$treeVector);
				break;
			default:
				notice('nYi');
		}
		
		return '';
		//Tree::print_tree($GLOBALS['pagetree'],'abb');
		
	}
	
	
	protected function process_page_edit($action = NULL)
	{
		$form = $this->getForm('edit_page_form');
		$input = $form->getElementValues();
		$page = $GLOBALS['page_index'][reset($form->getVector())];
	
	//add language
		if('add_lang' == $action)
		{
			$lang_id = $input['lang_missing'];
			$page->addAdditionalFragment($lang_id,array_flip($page->fragment_vars));
			$this->remakeForm('edit_page_form',NULL);
			return $this->getForm('edit_page_form')->getHtml($this->id);
		}
		
	//update FRAGMENTS
		$fragments = $page->loadAdditionalFragments();
		foreach($fragments as $k=>$v)
			unset($fragments[$k]['uri']);
		
		foreach($fragments as $l_id=>$vals)
		{
			$new_vals = array();
			foreach($vals as $k=>$v)
				$new_vals[$k] = $input[$k.'#'.$l_id];	
			
			$page->changeAdditionalFragment($l_id,$new_vals);
		}	
		$page->storeAdditionalFragments();

	//update DB-vars
		$members = array('name','pagegroup_id','box_structure_id','cacheable');
		//that's why php is cool
		foreach($members as $name)
			$page->__set($name,$input[$name]);
			
	//update URI
		if(isset($page->parent))
			$page->parent->update_uri(NULL); //passing null results in update of kids uri only!
		else
			$page->update_uri(array()); //TODO: just enough for all languages :D
			
		$page->store();
		return FALSE; //meaning we're done editing.
	}
	
		
	//****************************************************************//
	//							FORMS								  //
	//****************************************************************//
	
	
	
	
	//the id of the page to edit is given as $vector[0]
	protected function edit_page_form($vector)
	{
		if(!isset($vector[0]))
			throw new FormException('EMPTY VECTOR');

		
		$page = $GLOBALS['page_index'][$vector[0]];
		if(empty($page))
			throw new FormException('corrupt vector. No page with id '.$vector[0]);
		
		$form = new AccurateForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setVector($vector);
		$form->setGridSize(12,5);
		
		
		$row = 0;	
			$name_input = new TextInput( 'name:','page_name',$page->name,array());
				
				$name_input->addRestriction(new NotEmptyRestriction());
				$form->putElement('lbl name',$row,0,new HtmlElement('name:'));
				$form->putElement('name',$row,1, $name_input );
		
		$row++;	
			
			$query1_args = array('query'=>"SELECT * FROM `box_structure`",'keys'=>array('id'),'values'=>array('name'));
				$form->putElement('lbl_structure',$row,0,new HtmlElement('box structure:'));
				$form->putElement('box_structure_id',$row,1, new DataSelect( 'box structure:','page_box_s_id', $query1_args,$page->box_structure_id) );
				
		$row++;
			$query2_args = array('query'=>"SELECT pg.* FROM `pagegroup` pg JOIN pagegroup_permission pgp ON pgp.pagegroup_id = pg.id AND pgp.permission > 1 JOIN user_in_group uig ON uig.usergroup_id = pgp.usergroup_id AND uig.user_id={$_SESSION['user_id']}",'keys'=>array('id'),'values'=>array('name'));
				$form->putElement('lbl_pagegrp',$row,0,new HtmlElement('pagegroup: '));
				$form->putElement('pagegroup_id',$row,1,new DataSelect('pagegroup','page_pagegroup_id',$query2_args,$page->pagegroup_id));
			
		$row++;
			$form->putElement('lbl_cache',$row,0,new HtmlElement('cacheable: '));
			$form->putElement('cacheable',$row,1,new Checkbox('cacheable: ','page_cache','1',$page->cacheable));


		$row++;	
			//LANGUAGE DEPENDENT STUFF:
			$fragments = $page->loadAdditionalFragments(); //loads title, name, etc for all other fragments.
			
			//take out uri, we don't want to display that.
			foreach($fragments as $id=>$vals)
				unset($fragments[$id]['uri']);

			// FRAGMENTS (i.e. title, folder, abb)

			//LABELS ..................
			$col = 1;
			$var_order = array();
			foreach(array_keys(reset($fragments)) as $k)
			{
				$form->putElement('lbl_var_'.$k,$row,$col,new HtmlElement('<b>'.$k.'</b>'));
				$var_order []= $k;
				$col++;
			}
			
			
			// FIELDS ..................
		$row++;
		
			foreach($fragments as $lang_id=>$vars)
			{
				$form->putElement('lbl_lang_'.$lang_id,$row,0,new HtmlElement('<b>'.Language::id2long($lang_id).'</b>'));
				$col = 1;
				foreach($var_order as $k) //using var order to guarantee same order as labels!
				{	
					$v = $vars[$k];
					
					$input = new TextInput('',$k.'#'.$lang_id,$v,array()) ;
					
					//because root page can have (and will usually have) empty folder name
					if(isset($page->parent) || $k != 'folder_name')
						$input->addRestriction(new NotEmptyRestriction());
					
					$form->putElement($k.'#'.$lang_id,$row,$col,$input);
					$col++;
				}
			$row++;
			}
		
		$languages_ni = array_diff_key($GLOBALS['config']['languages_enabled'],$fragments);
		$languages_ni = array_intersect_key($GLOBALS['config']['languages_long'],$languages_ni);
		
		if(!empty($languages_ni))
		{
			$form->putElement('lbl_langmissing',$row,0,new HtmlElement('add language'));
			$form->putElement('lang_missing',$row,1,new Select('','lang_missing',$languages_ni));
			$form->putElement('add_lang',$row,2,new Submit('action','add_lan','add'));
			$row++;
		}
		
		$form->putElement('submit_final',$row,1,new Submit('action','submit','submit form'));
		
		return $form;
	}
	
	
	protected function edit_pagetree_form($open_nodes = NULL,$root = NULL)
	{
		if(!isset($root))
			$root = $GLOBALS['pagetree'];
		
		if(!isset($open_nodes))
			$open_nodes = array($root->__get('id')); //the root node is open by default
			
		if(!is_array($open_nodes))
			throw new FormException('Vector is not array!');
			
		
		$form = new SimpleForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setVector($open_nodes);
		
		$form->addElement('ulo_base',new HtmlElement("\n".'<ul class="pagetree">'."\n"));
		$form->addElementArray($this->rec_tree_elements($open_nodes,$root));
		$form->addElement('ulc_base',new HtmlElement('</ul>'."\n"));
		
		return $form;
	}
	
	
	//returns an array of form elements with text,buttons etc. for every page
	protected function rec_tree_elements($open_nodes,$page,$level = 0)
	{

		$open = in_array($page->id,$open_nodes); //is this node open?
		
		if($open)
			$tree_op = 'close'; //if node is open, you can close it.
		else
			$tree_op = 'open';
		
		$el_arr = array();
		$tabs = str_repeat("\t",$level);
		$tabs2 = $tabs."\t";	
		
		$id = $page->id;
		
//<li>		
		$el_arr['lio_'.$id] = new HtmlElement($tabs2.'<li title="'.$page->title.'">');
		
	//button to open or close		
		if ($level < MAX_PAGETREE_DEPTH)
			$el_arr['b.'.$tree_op.'_'.$id] = new Submit('action'.$id,$tree_op.'_'.$id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_'.$tree_op.'_small.png" alt="'.$tree_op.'" />',array('class'=>'img','title'=>$tree_op));
	//title abb etc. of page.	
		$el_arr['litext_'.$id] = new HtmlElement($page->abb.' (lan='.$page->language_id.' pg='.$page->pagegroup_id.') <div class="button_group">');
	
	if($id != $GLOBALS['pagetree']->id) //must NOT edit the root of the tree!
	{
	//buttons to edit move or delete this page.
		$el_arr['b.del_'.$id] = new Submit('action'.$id,'delete_'.$id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_drop.png" alt="delete" />',array('class'=>'img','title'=>'delete'));	//delete
		$el_arr['b.edit_'.$id] = new Submit('action'.$id,'edit_'.$id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_edit.png" alt="edit" />',array('class'=>'img','title'=>'edit')); //edit
		$el_arr['b.up_'.$id] = new Submit('action'.$id,'up_'.$id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_up.png" alt="up" />',array('class'=>'img','title'=>'move up'));
		$el_arr['b.down_'.$id] = new Submit('action'.$id,'down_'.$id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_down.png" alt="down" />',array('class'=>'img','title'=>'down'));
				
		$el_arr['lic_'.$page->id] = new HtmlElement('</div></li>'."\n");
//</li>
	}
		
		
		if($open)
		{
		//<ul>	
			$el_arr['ulo_'.$page->id] = new HtmlElement("\n".$tabs.'<ul class="pagetree">'."\n");
		
		//RECURSION !!! subpages	
			foreach($page->get_kids() as $subpage)
			{
				if($subpage->permission > 1)
					$el_arr = array_merge( $el_arr , $this->rec_tree_elements($open_nodes,$subpage,$level+1));	
			}

			
		//button to insert another page.
			if( $level < MAX_PAGETREE_DEPTH)
			{
				$el_arr['lio_b.ins_'.$page->id] = new HtmlElement( $tabs2.'<li title="insert new subpage for '.$page->abb.'" >');
				$el_arr['b.ins_'.$page->id] = new Submit('action'.$page->id,'insert_'.$page->id,'<img class="button" src="'.STYLE_IMAGE_PATH.'/b_ins.png" alt="insert" title="insert" />',array('class'=>'img'));
				$el_arr['lic_b.ins_'.$page->id] = new HtmlElement( $tabs2.'</li>');
			}
		
			$el_arr['ulc_'.$page->id] = new HtmlElement($tabs.'</ul>'."\n");
		//</ul>	
		}
		
		
		return $el_arr;
	}


}
?>
