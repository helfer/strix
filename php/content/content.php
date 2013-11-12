<?php

//TODO: setting the array db_vars in the class itself!
//TODO: should db_vars rather be returned in a static function declared abstract by DB-object?


// CONTENT OBJECTS:
/***********************************************************************************************/

abstract class Content extends TreeNode implements XhtmlInterface {
	protected $language_id = NULL;
	protected $page_id = -1;
	protected $box_id = -1;
	protected $style_id = 9; //default = Text (9)
	protected $style_class = '';
	protected $content_class_id = -1;
	protected $conf = '';
	
	//ID MUST NOT BE IN DB-vars. it cannot be changed!!!
	protected $db_vars = array(	'content_class_id',
								'page_id',
								'box_id',
								'style_id',
								'left',
								'right',
								'conf'
								);
										
	protected $fragment_vars = array();
	
	protected $tag_keys = array();
	protected $tags = array();
									
	protected $permission = 0;
	
	protected $forms = array();
	protected $mActivatedForm = NULL;
	private $mEditMode = FALSE;
	
	//conf sorta replaces field1 and field2
	protected $mConfKeys = array();
	protected $mConfValues = array();
	
	//information passed from processing to display:
	protected $process_msg = '';
	
	protected $mProcessInfo = ''; //value as returned by form processing method proces_<form_name>
	
				
	//TODO: ALERT: make final					
	public function __construct($vars = array(),$temp_object = FALSE)
	{
		parent::__construct($vars);
		
		if(!empty($this->tag_keys))
		{
			if(isset($vars['tags']))
				$this->constructTags($vars['tags']);
			else
				$this->constructTags(array());
		}
			
		//construction of NEW content (not yet in DB);	
		if(sizeof($vars) == 0 && !$temp_object)
			$this->first_construction();
		
		//TODO: make the conf work for new content!!
		$this->load_conf();
		
		

	}
	
	
	//auxilary construct functions: -----------------------------------------------------//
	
	//conf sorta replaces the old field1 and field2
	protected final function load_conf()
	{
		if (!empty($this->mConfKeys))
		{
			$con = explode(TAG_SEPARATOR,$this->conf);
			if(sizeof($this->mConfKeys) !== sizeof($con))
			{	
				error('conf error for '.$this->id);
				$this->mConfValues = array_combine($this->mConfKeys,$this->mConfValues);
			}
			else
			{
				//MUST not write that down as conf! Otherwise it'll be lost on the next writeback!
				$this->mConfValues = array_combine($this->mConfKeys,$con);	
			}
		}
	}
	
	protected final function constructTags($tags)
	{
		if (!empty($tags))
		{
			$tag_arr = explode(TAG_SEPARATOR,$tags);
			
			if (sizeof($tag_arr) != sizeof($this->tag_keys))
				throw new DatabaseException('Tag Key Number does not match number of tags:'.implode(',',$this-tag_keys).' -> '.implode(',',$vars['tags']));
				
			$this->tags = array_combine($this->tag_keys,$tag_arr);
		} 
		else
		{
			//notice('else '.get_class($this));
			//in case there are no tags defined, use keys.
			$this->tags = array_combine($this->tag_keys,$this->tag_keys);
		}
	}
	
	protected final function first_construction()
	{
		debug('__constructing really new content!!!');
		$this->needs_wb = TRUE;
		$this->page_id=$GLOBALS['page']->id;
		$this->content_class_id = self::getClassId();
		$this->language_id = $GLOBALS['current_language'];
		$this->permission = self::getClassPermission($this->content_class_id);
		$this->conf = implode(TAG_SEPARATOR,$this->mConfValues);
	}
	
	//------------------------------- functions used for first construction ---------------//
	
	public function getTableName(){
		return 'content';	
	}
	
	//This is here so that the id can change in DB without having to change it here.
	public final function getClassId(){
		return SqlQuery::getInstance()->singleValueQuery("SELECT `id` FROM `content_class` WHERE `object_type`='".get_class($this)."'");
	}
	
	public static final function getNameForId($class_id){
		$name = SqlQuery::getInstance()->singleValueQuery("SELECT `object_type` FROM `content_class` WHERE `id`='$class_id'");
		return $name;
	}
	
	public static final function getClassPermission($class_id){
		return SqlQuery::getInstance()->singleValueQuery(
				"SELECT SUM(DISTINCT ccp.`permission`) as 'permission' 
				FROM `content_class` cc 
				JOIN `content_class_permission` ccp ON ccp.`content_class_id` = cc.`id`
				JOIN `user_in_group` uig ON uig.`usergroup_id`=ccp.`usergroup_id`
				WHERE `id`='$class_id' AND uig.user_id={$GLOBALS['user']->id}"
				 );
	}
	

	
	//------------------------------------------------------------------//
	/*
	 *  DRAWING FUNCTIONS ... (final)
	 ******************************************************************/
	
	public final function draw_normal()
	{
		if(isset($this->language_id) && $this->language_id != $_SESSION['language_id'])
		{
			$note = '<legend>'.Language::id2long($this->language_id).'</legend>';
			return '<fieldset class="different_language">'.$note.$this->display().'</fieldset>';
		}
		else
			return $this->display();
		
	}
	
	
	public final function draw_modify()
	{
		//if you're not allowed to edit, you see the normal paragraph.
		if(!$this->checkPermission(WRITE_PERMISSION))
			return $this->draw_normal();
			
		$color = !empty($this->language_id) && ($this->language_id != $_SESSION['language_id'])?'background-color:#FFCCCC;':'';
			
		$xhtml = '<div style="border: 1px solid blue;'.$color.'">permission = '.$this->permission;
		
		$xhtml .= $this->getForm('parent_modif_form')->getHtml($this->id);
		
		foreach($this->{$this->kids_field} as $co){
			$xhtml .= $co->draw_modify();		
		}
		$xhtml .= $this->tree_stats();
		$xhtml .= $this->display();

	
		return $xhtml.'</div>';
	}
	
	
	// ---------------------------------------------------------------//
	// the following methods are usually used to display in the edit-box
	
	public final function draw_admin()
	{
		
		return $this->getForm('super_edit_form')->getHtml($this->id).
				'<p>You will need to reload after submitting to see the changes!</p>';
		
	}
	
	public final function draw_edit()
	{
		//notice($_SESSION['language_id'].' cmp '.$GLOBALS['page']->language_id);
		if(isset($this->language_id) && $_SESSION['language_id'] != $GLOBALS['page']->language_id)
			error('This page does not exist in the language you want to modify! Please add the language to this page first!');
		
		
		$language = '<p style="background-color:#FC9FC9">Edit language: '.$GLOBALS['config']['languages_long'][$_SESSION['language_id']].'</p>'; 
		$form_html = $this->getForm('edit_form')->getHtml();	
		//print_r(array_keys($this->forms));
		return $language.$form_html;
	}
	
	
	public final function draw_translate()
	{
		return $this->getForm('translate_form')->getHtml();	
	}
	
	/* FORMS:
	 ******************************************************************/
	//----------------------------------------------------------------//
	
	//TODO: does it make sense to have this one static?
	private final function parent_modif_form()
	{
		
		$form = new SimpleForm('parent_modif_form','',array(),-1,'edit');
		
		$form->addElement('delete',new SafeSubmit('action','delete',Language::getGlobalTag('Content','delete')));
		
		if(!method_exists($this,'edit_form_elements'))
			$form->addElement('noedit',new Submit('action','no_edit','no edit',array('disabled','style'=>'background-color:#EFEFEF;')));
		else
		{
			if(isset($this->language_id) && $this->language_id != $_SESSION['language_id'])
				$form->addElement('translate',new Submit('action','translate',Language::getGlobalTag('Content','translate')));
			else
				$form->addElement('edit',new Button('','action','edit','submit',Language::getGlobalTag('Content','edit')));
		}
		
		if($this->checkPermission(ADMIN_PERMISSION))
			$form->addElement('admin',new Submit('action','admin',Language::getGlobalTag('Content','admin')));
					
		return $form;
	}
	
	
	
	//this is the wrapper for the edit form of content.
	public function edit_form()
	{
		$form = new TabularForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setProportions('12em','40em');
		
		foreach($this->edit_form_elements() as $id=>$el)
			$form->addElement($id,$el);		
		
		$form->addElement('submit',new Submit('action','submit','Submit'));
		$form->addElement('preview',new Submit('action','preview','Preview'));
		
		return $form;
	}
	
	
	//super edit form is only for admins !!!
	//it lets you modify all the variables for one content directly in the DB!
	public function super_edit_form(){		
		
		$form = new DataForm(__METHOD__,'',array(),818,'edit');
		
		$form->loadFromObject($this);
		$form->addElement('submit',new Submit('action','submit'));
		return $form;
		
		/*
		
		//($name, $action = '', $extras = array(), $target_id = -1,$target_type = 'content',$method = 'post')
		
		$form = new TabularForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setProportions('12em','40em');
		
		$form->addElement('conf',new TextInput('Configuration:','conf',$this->conf,array('maxlength'=>'512')));
		$form->addElement('submit',new Submit('action','submit'));
		$form->addElement('preview',new Submit('action','preview'));
		
		return $form;
		//return '<p>THIS CONTENT HAS NO EDIT!!!</p>';	
		*/
	}
	
	
	
	
	//Displays a form with all fragment vars to be translated.
	public function translate_form()
	{
		$form = new TabularForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setProportions('12em','40em');
		foreach($this->fragment_vars as $name){
			$form->addElement($name,new Textarea('Translate '.$name,$name,$this->$name,10,50));
		}
		//we always translate to the session language id.
		$form->addElement('language_id', new Hidden('language_id',$_SESSION['language_id']));
		$form->addElement('submit',new Submit('action','submit'));
		$form->addElement('preview',new Submit('action','preview'));
		
		return $form;
	}
	
	//Function translates content fragments
	//TODO: this is a bogus function!
	protected function translate()
	{
		$form = $this->getForm('translate_form');
		$this->language_id = $form->getElementValue('language_id');
		foreach($this->fragment_vars as $name)
		{
			$this->$name = $form->getElementValue($name);
		}
	}
	
	
	//----------------------------------------------------------------//
	
	/* INPUT FUNCTIONS
	 ******************************************************************/
	
	public final function populate_form($form_name,$vector,$post)
	{
		notice('POST input for: '.$this->object_type.' '.$this->id);
		notice($post);
		
		//notice('ui vector');
		//print_r($vector);
		$this->mActivatedForm = $this->getForm($form_name,$vector);
		$this->mActivatedForm->populate($post);
		
		return 0; //everything ok.
	}
	
	public final function user_input($form_name,$vector,$post)
	{
		$this->populate_form($form_name,$vector,$post);
		$target_method = 'process_'.$form_name;
		$this->mProcessInfo = $this->$target_method();	
	}
	
	
	public final function editor_input($form_name,$vector,$input)
	{
			
		$this->populate_form($form_name,$vector,$input);
		
		$aForm = $this->mActivatedForm;
	
		//and now da real thing: ---------------------------------
	
		$valid = $aForm->validate();
		notice($valid?'valid':'invalid');
		$action = $aForm->getButtonPressed();
		notice('action: '.$action);
		
		if (empty($action))//no valid button was pressed (usually delete w/o check)
			return FALSE;
			
		switch ($form_name)
		{
			case 'super_edit_form':
				if(!$valid)
					return $this->draw_admin();
					
				return $this->process_super_edit();

				break;
			case 'parent_modif_form':
				if (!$valid)
					return '';
				
				switch ($action)
				{
					case 'delete':
						$this->suicide();
						return '';
						break; //I know it's not necessary...
					case 'admin':
						return $this->draw_admin(); //HERE
						break;
					case 'edit':
						return $this->draw_edit();
						break;
					case 'translate':
						return $this->draw_translate();
						break;
					default:
						throw new UserInputException('Unexpected Action '.$action.' for '.$form_name);
				}
				
				break;
			case 'edit_form':
				if (!$valid)
					return $this->draw_edit();
			
				switch ($action)
				{
					case 'submit':
						if ($this->process_edit())
							return $this->draw_edit();
						$this->store();
						return '';
					case 'preview':
						$this->process_edit();
						return $this->draw_edit();
						break;
					default:
						$this->process_edit($action);
						return $this->draw_edit();
						
					//default:
					//	throw new UserInputException('Unexpected Action for '.$form_name);
				}
			
				break;
			case 'translate_form':
				if (!$valid)
					return $this->draw_translate(); //why not $aForm->getHtml??
			
				switch ($action)
				{
					case 'preview':
						$this->translate();
						return $this->draw_translate();
						break;
					case 'submit':
						$this->translate();
						$this->addFragment();
						$this->store();
						return '';
						break;
				}
				break;
			case 'edit_pagetree_form':
				if (!$valid)
					return '';
				return $this->process_pagetree_edit($action);
				break;
			case 'edit_page_form':
				if (!$valid)
					return $aForm->getHtml();
				return $this->process_page_edit($action);
				
				break;
			default:
				throw new UserInputException('Unexpected form '.$form_name);	
		}
		
		return TRUE;
		
	}
	
	
	
	
	/*
	 *  ABSTRACT Functions
	 ******************************************************************/

	//called for normal display
	protected abstract function display();
	
	
	//called to process edit
	protected function process_super_edit(){
		if(!$this->checkPermission(ADMIN_PERMISSION))
			return 'You don\t have permission to edit!';
		
		$form = $this->getForm('super_edit_form');
		//print_r($form->getElementValues());
		$changed = $form->getChangedElementValues();
		print_r($changed);
		if(empty($changed))
			return '';
		else
		{
			$note = '';
			foreach($changed as $k=>$v)
			{
				$this->__set($k,$v);
				$note .= 'SET '.$k.' to '.$v.'<br />';
			}
			
			$this->store();
			return $note;
		}
	}
	
	/*{
		if($this->mEditMode) //TODO: this is a mess!
			return $this->edit();
		
		
		$div = '<div style="border:solid 1px black;">';
		return '<p>Obj Type: '.$this->object_type.'</p><p class="'.$this->style_class.'" style="'.$this->style_info.'">'.$this->text.'</p>';
	}*/
	
	
	
	/*
	 *  PSEUDO-ABSTRACT Functions
	 ******************************************************************/	


	

	
	
	
	/*
	 * Form stuff
	 ******************************************************************/
	
	
	
	
	protected function getActivatedForm(){
		return $this->mActivatedForm;	
	}
	
	
	
	protected function getForm($form_name,$vector = NULL){
		//the forms are defined in a function, their name should be __METHOD__
		
		if(!method_exists($this,$form_name))
			throw new UserInputException('Form '.$form_name.' not found in '.get_class($this));
		
		//retrieve vector from session if form was stored there before.
		if(!isset($vector) && isset($_SESSION['form_vectors'][$form_name.'_'.$this->id]))
			$vector = $_SESSION['form_vectors'][$form_name.'_'.$this->id];
		
		if (!isset($this->forms[$form_name])) //create the form if it hasn't been constructed yet.
			$this->forms[$form_name] = $this->$form_name($vector); 
		
		$form_ret = $this->forms[$form_name];
		
		//stores the vector for later calls...
		$v = $form_ret->getVector();
		if (!empty($v))
			$_SESSION['form_vectors'][$form_name.'_'.$this->id] = $v;
			
		return $form_ret;

	}
	
	protected function remakeForm($form_name,$new_vector = NULL)
	{
		if($new_vector === NULL)
			$new_vector = $this->forms[$form_name]->getVector();
		
		unset($this->forms[$form_name]); //if we unset the form, getForm() will rebuild it.
		return $this->getForm($form_name,$new_vector);	
	}
	
	//alias for remakeForm
	protected function reloadForm($form_name,$new_vector = NULL)
	{
		return $this->remakeForm($form_name,$new_vector);
	}
	
}

?>
