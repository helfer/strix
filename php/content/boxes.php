<?php

// BOX Objects:
/*********************************************************************************/

abstract class Box extends TreeNode implements XhtmlInterface{
	
	protected $style_id;
	protected $style_class;
	
	protected $box_structure_id;
	protected $name = '';
	
	public final function getTableName(){
		return 'boxes';
	}

	public final function draw_normal(){
		if(count($this->{$this->kids_field})>0){
			$xhtml = '';
			foreach($this->{$this->kids_field} as $co) 
				$xhtml .= $co->draw_normal();
			return '<div class="'.$this->style_class.'">'.$xhtml.'</div>';
		} else return '<div class="empty_box"></div>';
	}
	
	public function draw_modify(){
		return 'NO DRAW MODIFY FOR THIS CLASS!<br />'.$this->draw_normal();	
	}

	
	//---------------
	
	public function editor_input($form_name,$vector,$post){
		$form = $this->$form_name();
		$form->populate($post); //just for fun, we don't really use it...
		
		if(!$form->validate())
		{
			error('Form is invalid!');
			return FALSE;
		}	
		
		///@todo redo, using forms this time!
		
		if($form_name === 'box_insert_form')
		{
		
			notice('calling insert!');
			notice($post);
			$pos = $post['pos'];
			$new_class_id = $post['insert_class_id'];
			
			//ATTENTION: this means that the content_index of page is not up to date any more!!!
			
			$type = Content::getNameForId($new_class_id);
			$neu = new $type();
			$this->insert_kids($neu,$pos);
			$this->reposition_kids();
			
			if (!$neu->store()) //must call this last!
				error(mysql_error());
			else
				notice('insert successful');
				
			$GLOBALS['content_index'][$neu->id] = $neu;
		} 
		else if ($form_name === 'box_move_form')
		{
			
			notice('calling move!');
			notice($post);	
			
			$new_pos = -1;
			
			switch($form->getButtonPressed())
			{
				case 'action_up':
					$new_pos = $post['pos'] -1;
					break;
				case 'action_down':
					$new_pos = $post['pos'] +1;
					break;
				default:
					throw new UserInputException('Action is not one of up/down but '.$form->getButtonPressed());
			}
			if ($new_pos >= 0)
				Tree::transfer($post['cid'],$this,$post['pos'],$this,$new_pos);
			else
				notice('already on top!');
			
		}
		
	}
	


}




final class SuperBox extends Box{
	protected $kids_field = 'kids';
	protected $object_type = 'SuperBox';


	public final function draw_modify(){
		$xhtml = '';
		
		foreach($this->{$this->kids_field} as $co){
				$xhtml .= $co->draw_modify();		
			}
			
		return '<div class="'.$this->style_class.'">'.$xhtml.'</div>';

	}

}

final class ContentBox extends Box{
	protected $content = array();
	
	protected $kids_field = 'content';
	protected $object_type = 'ContentBox';
	
	protected function adopt($kid){
		TreeNode::adopt($kid);
		$kid->__set('box_id',$this->id);
	}
	
	//TODO: is this smart:
	protected static $insert_form = NULL;
	
	public final function draw_modify(){
		$xhtml = '';
		
		if($this->checkPermission(WRITE_PERMISSION)){
			$style = 'style="border:2px dotted grey"';
			$yPos = 0;
			$xhtml .= $this->box_insert_form($yPos)->getHtml($this->id);
			foreach($this->{$this->kids_field} as $co){
				$xhtml .= $this->draw_content_modif($co);	
				$xhtml .= $this->box_insert_form(++$yPos)->getHtml($this->id);
			}	
		
		} else {
			$style = '';
			foreach($this->{$this->kids_field} as $co){
				$xhtml .= $co->draw_normal();		
			}

		}
			
		return '<div '.$style.' class="'.$this->style_class.'">'.$xhtml.'</div>';
	}
	
	
	
	
	protected function draw_content_modif($content_obj)
	{
		$xhtml = '';
		$xhtml .= '<div style="border: 1px dotted red">';
		$xhtml .= $this->box_move_form($content_obj->id,$content_obj->position)->getHtml($this->id);
		$xhtml .= $content_obj->draw_modify();
		$xhtml .= '</div>';
		
		return $xhtml;
		
	}
	
	
	
	//FORMS ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::://
	
	//form to move content (for first, don't display up, for last don't display down)
	protected static function box_move_form($content_id = 0,$position = -18){
		//var_dump($content_id);
		//debug_print_backtrace();
		$form1 = new SimpleForm('box_move_form','',array(),-1,'box');
		//$form1->addElement('action_up',new Button('','action','up','submit',Language::getGlobalTag('ContentBox','move_up')));
		if ($position > 0 || $position == -18)
			$form1->addElement('action_up',new Submit('action','up','<img class="button" src="/webcontent/styles/img/b_up.png" alt="up" />',array('class'=>'img','title'=>'move up')));
		if($content_id == 0 || !$GLOBALS['content_index'][$content_id]->isLastKid())
			$form1->addElement('action_down',new Submit('action','down','<img class="button" src="/webcontent/styles/img/b_down.png" alt="down" />',array('class'=>'img','title'=>'move down')));
		//$form1->addElement('action_down',new Button('','action','down','submit',Language::getGlobalTag('ContentBox','move_down')));
		
		
		
		
		$form1->addElement('cid',new Hidden('cid',$content_id));
		$form1->addElement('pos',new Hidden('pos',$position));
		
		return $form1;
	}
	
	
	//form to insert new content
	protected static function box_insert_form($pos = 0){
		
		//keep em global so they don't get recreated for every form!
		//couldn't figure out how to do it with a static member
		if(isset($GLOBALS['forms_per_page']['insert_form'])){
			$form =& $GLOBALS['forms_per_page']['insert_form'];
			$form->getElement('pos')->setValue($pos);
			return $form;
		}
		
		$form1 = new SimpleForm('box_insert_form','',array(),-1,'box');
		
		//select only content which this user has a right to write.
		$query = 	"SELECT DISTINCT cc.id, cc.description
					FROM `content_class` cc
					JOIN `content_class_permission` ccp ON ccp.`content_class_id` = cc.`id`
					JOIN `user_in_group` uig ON ccp.`usergroup_id` = uig.`usergroup_id`
					WHERE uig.`user_id`='".$GLOBALS['user']->id."' AND ccp.`permission` = '".WRITE_PERMISSION."'";
		$keys = array('id');
		$values = array('description');
		
		$form1->addElement('ins1',new DataSelect(Language::getGlobalTag('ContentBox','insert_label'),'insert_class_id',array('query'=>$query,'keys'=>$keys,'values'=>$values)));
		$form1->addElement('sub',new Button('','action','insert','submit',Language::getGlobalTag('ContentBox','insert')));
		$form1->addElement('pos',new Hidden('pos',$pos));
		
		$GLOBALS['forms_per_page']['insert_form'] = $form1;
		return $GLOBALS['forms_per_page']['insert_form'];
		
	}
	
	
	//TODO: this is just a fix!!!
	
	//reposition not self, but the kids (which must be in a tree with different left and right!!)
	public function reposition_kids($start = 0){

		//only reposition from $start on!!
		if ($start > 0)
			$pos = $this->{$this->kids_field}[$start-1]->__get('right') + 1;
		else
			$pos = 1;
		foreach(array_slice($this->{$this->kids_field},$start) as $kid){
			$pos = $kid->reposition_self($pos);	
		}
		debug('wow I now have '.(($pos-1)/2).' kids!');
		return $pos;
	}

}
?>
