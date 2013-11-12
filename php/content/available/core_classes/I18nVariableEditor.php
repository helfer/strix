<?php

include_once(SCRIPT_DIR . 'core/i18n.php');
/*
 * class name: change_profile
 * 
 * 
 */
class I18nVariableEditor extends Content{
	
	protected $mConfKeys = array('dimension_lbl','dimension_input');
	protected $mConfValues = array('8em','20em');

	
	/*
	 * additional members:
	 */
	
	protected $process_msg = '';
	
	protected $selected_variable_id = null;
	
	/*
	 * functions:
	 */
	
	public function display()
	{
		$xhtml = '';
		
		$xhtml .= $this->process_msg;
		
		$xhtml .= '<p>Current language = '.$_SESSION['language_abb']. '</p>';
		
		if(isset($this->selected_variable_id)){
			$xhtml .= $this->getForm('change_variable_form',$this->selected_variable_id)->getHtml($this->id);
			//print_r($variable);
		
		} else {
			$xhtml .= $this->getForm('select_variable_form')->getHtml($this->id);
		}
		return $xhtml;
	}
	
	
	public function process_change_variable_form(){
		
		$form = $this->getActivatedForm();
		
		if($form->validate())
		{
			$this->process_msg = 'FORM IS VALID';
			
			$text = $form->getElementValue('text');
			$vector = $form->getVector();
			
			$variable = new i18nVariable();
			$variable->loadFromDbById($vector);
			
			$variable->__set('text',$text);
			$variable->store();
			
			$this->process_msg .= ' AND TEXT WAS CHANGED FOR LANGUAGE '.$variable->language_id;
			
		}else
			$this->process_msg = 'FORM IS INVALID';

	}

	
	
	protected function change_variable_form($vector){
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$newForm->setVector($vector);
		
		$variable = new i18nVariable();
		$variable->loadFromDbById($vector);
			
		$newForm->addElement('text',new Textarea('text','text',$variable->text,20,30));
		
		$newForm->addElement('submit_addr',new Submit('submit_addr','Submit'));

		return $newForm;
	}
	
	
	protected function select_variable_form($vector){
		$newForm = new TabularForm(__METHOD__);
		$newForm->setProportions($this->mConfValues['dimension_lbl'],$this->mConfValues['dimension_input']);
		$newForm->setVector($vector);
		
		$qargs = array('query'=>"SELECT id,name FROM i18n",'keys'=>array('id'),'values'=>array('name'));
		$newForm->addElement('name',new DataSelect('Variable Name','name',$qargs));
		
		$newForm->addElement('submit_addr',new Submit('submit_addr','Select'));
		
		return $newForm;
	}
	
	protected function process_select_variable_form(){
		$form = $this->getActivatedForm();
		if($form->validate()){
			$this->selected_variable_id = $form->getElementValue('name');	
		}
	}

}
?>
