<?php
//this class lets you create forms easily anc correctly. functions are more or less self explaining...


define('newline_code','newlineNLbrcrlf');

class Form {
	
	//-----------------------
	private $name = '';
	private $action = '';
	private $method = '';
	private $extras = '';
	private $target_content_id = -1;
	private $elements = array();
	private $staticElements = array();
	private $element_order = array();
	private $errors = array();
	
	private $valid = TRUE;
	private $problems = '';
	
	//extras is an array of the form array(ident=>value, ident2=>value2) eg array('rows'=>5, 'cols'=>2)
	public function __construct($name,$action,$method,$target_content_id,$extras = array()){
		$this->name = $name;
		$this->action = $action;
		$this->method = $method;
		$this->extras = $extras;
		$this->target_content_id = $target_content_id;
		$this->makeTarget();
	}
	
	
	private function makeTarget(){
		$this->addStatic( new Hidden('form_name',$this->name));
		$this->addStatic( new Hidden('action','process'));
		$this->addStatic( new Hidden('target_content_id',$this->target_content_id));
	}
	
	public function addStatic($el){
		//print_r($el);
		$this->staticElements []= $el;
	}
	
	private function printStaticElements(){
		//print_r($this->staticElements);
		$ret = '';
		foreach($this->staticElements as $stat)
			$ret .= $stat->getHtml();
		return $ret;
	}
	
	public function getElements(){
		return $this->elements;	
	}
	
	public function getElementByName($name){
		if(isset($this->elements[$name]))
			return $this->elements[$name];
		else
			return FALSE;
	}
	
	public function addElement($formElement){
		if(in_array($formElement->getName(),$this->listElements()) || $this->getName() == $formElement->getName()){
			$this->problems .= 'element with name \''.$formElement->getName().'\' already exists in form '.$this->name.'<br>';
			$this->valid = FALSE;
			return FALSE;
		}
			
		//echo '<br>bool: '.$formElement->getName().'not in: '.implode(',',$this->listElements()) .' || '.$this->getName().' == '.$formElement->getName();
		
		$this->element_order []= $formElement->getName();
		$this->elements[$formElement->getName()] = $formElement;
		return TRUE;
	}
	
	public function newline($num = 1){
		for($i = 0;$i<$num;$i++){
			$this->element_order []= newline_code;
		}
	}
	
	public function removeElements(){
		$this->elements = array();
		$this->element_order = array();
	}
	
	public function printElements(){
		$html = '';
		foreach($this->element_order as $name){
			if($name == newline_code)
				$html .= "<br />\n";
			else{
			if(!$this->valid && !$this->elements[$name]->validate())
				$html .= '<br/><b style="color:red">'.$this->elements[$name]->getError().'</b>';
					
				$html .=	$this->elements[$name]->getHtml();
			}
			
		}
		return $html;
	}
	
	public function printStartTags(){
		$html = '<form name="'.$this->name.'" action="'.$this->action.'" method="'.$this->method.'"'.makeExtras($this->extras).'>';
		$html .= '<p>';
		return $html;
	}
	
	public function printEndTags(){
		$html = '</p>';
		$html .= '</form>';
		return $html;
	}
	
	
	public function getHtml(){
		$html = '';
		//print_r($this);
		if(!$this->valid)
			$html .= '<p class="error">invalid form!</p>'.$this->problems;

		$html .= $this->printStartTags();
		$html .= $this->printStaticElements();
		$html .= $this->printElements();
		$html .= $this->printEndTags();
		
		return $html;
	}
	
	public function getName(){
		return $this->name;	
	}


	public function listElements(){
		return $this->element_order;
	}
	
	public function getElementValue($name){
		return $this->elements[$name]->getValue();
	}
	
	//returns an array of form: array( elName=>elValue, el2Name=>el2Value)
	public function getValues(){
		$ret = array();
		foreach ($this->elements as $name=>$element){
			$ret[$name] = $element->getValue();
		}
		return $ret;
	}
	
	//------------------------------------------------------------------
	
	//takes only values it can use from the array $variables
	public function populate($variables, $safe = FALSE){
		if($safe){
			if(!isset($variables['form_name']) || $variables['form_name'] != $this->name)
				return;
		}
		
		foreach($variables as $k=>$v){
			if(in_array($k,$this->element_order))
				$this->elements[$k]->setValue($v);
		}
	}
	
	public function validate(){
		$valid = TRUE;
		
		foreach($this->elements	as $el){
			$elem = $el->validate();
			$valid = $valid && $elem;	
			if(!$elem)
				$this->errors[$el->getName()] = $el->getError();
		}
		
		$this->valid = $valid;
		return $valid;
		
	}
	
	public function clear(){
		foreach($this->elements as $el)
			$el->clear();	
	}
	
	
	
	// -----------------------------------------------------------------

	
	
	// direct functions ------------------------------------------------
	
	public function addSelect($label,$name,$options,$selected_options=array()){
		return $this->addElement(new Select($label,$name,$options,$selected_options));
	}
	
	public function addDataSelect($label,$name,$query_args,$selected_options=array()){
		return $this->addElement(new DataSelect($label,$name,$query_args,$selected_options));
	}
	
	public function addInput($label,$type,$name,$value,$extras=array()){
		return $this->addElement(new Input($label,$type,$name,$value,$extras));
	}
	
	public function addButton($name,$type,$value,$label){
		return $this->addElement(new Button($name,$type,$value,$label));
	}
	
	public function addTextarea($label,$name,$rows,$cols,$text,$extras=array()){
		return $this->addElement(new Textarea($label,$name,$rows,$cols,$text,$extras));
	}
	
	public function addRadios($labels,$name,$values,$line_tag = '',$checked = ''){
		return $this->addElement(new Radio($labels,$name,$values,$line_tag, $checked));
	}
	
	public function addSubmit($name,$value){
		return $this->addElement(new Submit($name,$value));
	}
	
	public function addHidden($name,$value){
		return $this->addElement(new Hidden($name,$value));
	}

	public function addSimpleSubmit(){
		$this->addElement(new Submit('submit','los'));	
	}
	
	public function addRestrictionToElement($name,$restr){
		$this->getElementByName($name)->addRestriction($restr);	
	}
	
	public function addIdenticalRestriction($el1_name,$el2_name){
		$this->addRestrictionToElement($el2_name,new sameAs($this->elements[$el1_name]));
	}

}


	
/* FORM ELEMENTS:
* **********************************************************************************/
	
class FormElement{
	protected $label = 'new FormElement';
	protected $name = 'new FormElement';
	protected $value = FALSE;
	protected $restrictions = array();
	protected $parentForm = FALSE;
	protected $valid = TRUE;
	
	
	
	public function getValue(){
		return $this->value;
	}
	
	public function setValue($val){
		$this->value = $val;	
	}
	
	public function getName(){
		return $this->name;	
	}
	
	public function makeLabel($text,$for){
		return '<label for="'.$for.'">'.$text.'</label>';
	}
	
	public function addRestriction($restr){
		$this->restrictions []= $restr;	
	}
	
	
	public function validate(){
		foreach($this->restrictions as $restr){
			if(!$restr->check($this->getValue()))
				return $this->valid = FALSE;
		}	
		
		
		return $this->valid = TRUE;
	}
	
	public function getError(){
		if($this->valid)
			return '';
			
		$ret = '';
			
		foreach($this->restrictions as $restr){
			$err = $restr->comment($this->getValue());
			if(strlen($err) > 0)
				$ret .= $err.'<br />';
		}	
		return $ret;
	}
	
	public function clear(){
		$this->setValue('');
	}
	
	
	/*
	public function setParentForm($form){
		if($parentForm)
			$parentForm->removeElement($this->name);
			
		$this->preifixName($form->getName());
	}
	
	private function prefixName($prefix){
		
	}*/
	
}
/*
class Select extends FormElement{

	public function __construct($label,$name,$options,$selected_options=array()){
		$this->label = $label;
		$this->name = $name;
		$this->options = $options;
		//echo '<br>selected: ';print_r($selected_options);
		if(!is_array($selected_options))
			$selected_options = array('0'=>$selected_options);
		$this->selected_options = $selected_options;
	}
	
	public function getHtml(){
		
		$html = $this->makeLabel($this->label,$this->name).'<select name="'.$this->name.'" id="'.$this->name.'">';
		$select = '';
		foreach($this->options as $value=>$text){
			if(in_array($value,$this->selected_options)) $select = 'selected';
			else $select = '';
			
			$html .= '<option value="'.$value.'" '.$select.'>'.$text."</option>\n";
			
		}

		return $html.'</select>';
	}
	
	public function setValue($val){
		$this->selected_options=array($val);
	}
	
	//TODO: this is crap and just a patchwork solution:
	public function getValue(){
		return reset($this->selected_options);
	}
	
	
}*/

class Radio extends FormElement{
	

	public function __construct($labels,$name,$values,$line_tag = '',$checked = ''){
		
		if(!is_array($labels) || !is_array($values))
			throw new Exception('no array found when expected in class Radio->__construct');
		
		if(sizeof($labels) != sizeof($values))
			throw new Exception('array sizes differ! Radio->__construct');
		$this->label = $labels;
		$this->name = $name;
		$this->values = $values;
		$this->checked = $checked;
		$this->line_tag = $line_tag;


	}
	
	public function getHtml(){
		$html = '';
		$max = sizeof($this->label);
		for($i = 0;$i < $max; $i++){
			//echo '<br />chk: '.$this->checked;
			$chk = '';
			if($this->checked == $this->values[$i]){
				$chk = 'checked';
			}
				
			$html .= '<input type="radio" name="'.$this->name.'" value="'.$this->values[$i].'" '.$chk.' />'.$this->label[$i].$this->line_tag;
		}
		
		return $html;
	}
	
	public function setValue($val){
		if(in_array($val,$this->values))
			$this->checked = $val;
		else
			throw new Exception('No radio element with value '.$val.' found!');
	}
	
	//TODO: this is crap and just a patchwork solution:
	public function getValue(){
		return $this->checked;
	}
	
	
}

/*
class Input extends FormElement{
	
	public function __construct($label,$type,$name,$value,$extras=array()){
		$this->label = $label;
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
		$this->extras = $extras;
	}
	
	public function getHtml(){
		return $this->makeLabel($this->label,$this->name).'<input type="'.$this->type.'" name="'.$this->name.'" value="'.$this->value.'" id="'.$this->name.'"'.makeExtras($this->extras).' />';
	}
	
	public function setValue($val){
		switch($this->type){
			
			case 'checkbox':
				
			case 'radio':
				$this->extras []= 'checked';
				break;
				
			default:
				$this->value = $val;
		}
		//print_r($this);
	}
	
}


class Button extends FormElement{
	
	public function __construct($label,$name,$type,$value){
		$this->label = $label;
		$this->name = $name;
		$this->type = $type;
		$this->$value = $value;
	}
	
	public function getHtml(){
		return $this->makeLabel($this->label,$this->name).'<button name="'.$this->name.'" type="'.$this->type.'" id="'.$this->name.'" value="'.$this->value.'">'.$this->label.'</button>';
	}
	
	public function clear(){
		return;	
	}
	
}
/*
class Textarea extends FormElement{
	
	private $text = '';
	
	public function __construct($label,$name,$rows,$cols,$text,$extras=array()){
		$this->label = $label;
		$this->name = $name;
		$this->rows = $rows;
		$this->cols = $cols;
		$this->value = $text;
		$this->text = $text;
		$this->extras = $extras;
	}
	
	public function getHtml(){
		return $this->makeLabel($this->label,$this->name).'<textarea name="'.$this->name.'" rows="'.$this->rows.'" cols="'.$this->cols.'" id="'.$this->name.'"'.makeExtras($this->extras).'>'.$this->text.'</textarea>';
	}
	
	public function getText(){
		return $this->text;	
	}
	
	public function setValue($val){
		$this->text = $val;	
	}
	
	public function getValue(){
		return $this->text;	
	}
	
}
/*
class Submit extends FormElement{
	
	public function __construct($name,$value){
		$this->name = $name;
		$this->value = $value;
	}
	
	public function getHtml(){
		return '<input type="submit" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->value.'" />';	
	}
	
	public function clear(){
		return;
	}
	
}*/

/*
class DataSelect extends Select{

	public function __construct($label,$name,$query_args,$selected_options=array()){

		$options = SqlQuery::assocQuery($query_args[0],$query_args[1],$query_args[2]);
		
		parent::__construct($label,$name,self::implode_values(' ',$options),$selected_options);
	}
	
	private static function implode_values($glue,$arr){
		$ret = array();
		foreach($arr as $k=>$v)
			$ret[$k] = implode($glue,$v);
			
		return $ret;	
	}
	

	
} 

class Information extends FormElement{
	private $text = 'info:';
	
	
	public function __construct($name,$text){
		$this->text = $text;
		$this->name = $name;
	}	
	
	public function getHtml(){
		return $this->text;
	}
	
}

//TODO: this is all bad coding!!! :
 
class Fieldset extends FormElement{
	private $title = 'fieldset:';
	
	
	public function __construct($name,$title){
		$this->title = $title;
		$this->name = $name;
	}	
	
	public function getHtml(){
		return "<fieldset style=\"border:1px solid\">\n<legend>".$this->title."</legend>\n";
	}
	
}

class FieldsetEnd extends FormElement{	
	
	public function __construct($name){
		$this->name = $name;
	}	
	
	public function getHtml(){
		return "</fieldset>\n";
	}
	
}

/*
class Hidden extends FormElement{
	
	public function __construct($name,$value){
		$this->name = $name;
		$this->value = $value;
	}
	
	public function getHtml(){
		return '<input type="hidden" name="'.$this->name.'" value="'.$this->value.'" />';	
	}
	
	public function clear(){
		return;	
	}
	
} */




//////////////////////*****************************************//////////////////////
/*-------------------------------------- RESTRICTIONS------------------------------*/

abstract class Restriction{

	public abstract function check($value);
	public abstract function comment($value);
	
}

class NotEmptyRestriction extends Restriction{
	
	public function check($val){
		return (sizeof($val) != 0);	
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'field is empty!';	
	}

}

class IsNumericRestriction extends Restriction{
	
	public function check($val){
		return is_numeric($val);	
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'field is not numeric!';	
	}

}

class InListRestriction extends Restriction{

	private $list;

	public function __construct($list){
		$this->list = $list;
	}

	public function check($val){
		return in_array($val,$this->list);	
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'illegal value!';	
	}

}
/*
class DataRestriction extends Restriction{
	
	private $query;
	//query must be of FORM array(array( TABLES), array('ROW'=>'value', 'ROW2'=>'value2') )
	
	public function __construct($tables,$rows){
		$this->query = $query;
	}
	
	public function check($val){
		return SqlQuery::rowExists($this->query);		
	}
}*/


class RangeRestriction extends Restriction{
	private $lower = 1;
	private $upper = 0;
	
	public function __construct($lower, $upper){
		$this->lower = $lower;
		$this->upper = $upper;
	}
	
	public function check($val){
		return ($val <= $this->upper && $val >= $this->lower);	
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'value not in allowed range!';	
	}
	
}

class FunctionalRestriction extends Restriction{
	private $lower = 1;
	private $upper = 0;
	private $callback = NULL;
	
	public function __construct($lower, $upper,$callback){
		$this->lower = $lower;
		$this->upper = $upper;
		$this->callback = $callback;
	}
	
	public function check($val){
		
		return (call_user_func($this->callback,$val) <= $this->upper && call_user_func($this->callback,$val) >= $this->lower);	
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'length must be between (incl) '.$this->lower.' and '.$this->upper;
	}
	
}

class isEmailRestriction extends Restriction{
	
	public function __construct(){
	}
	
	public function check($val){
		return checkEmail($val);
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'not a valid e-mail address!';
	}
	
	
}

class isUserPassword extends Restriction{
	
	public function __construct(){
	}
	
	public function check($val){
		//print_r($_SESSION['user']);
		//echo '<br />'.$_SESSION['user']->password.' ~ '.md5($val).'<br />';
		return !strcmp($_SESSION['user']->password,md5($val));
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'password is wrong!';
	}
	
}

class isDecentPassword extends Restriction{
	
	public function __construct(){
	}
	
	public function check($val){
		return $this->decencyTest($val);
	}
	
	private function decencyTest($data){
		//password must contain lowercase uppercase and numbers 
		
		$lc = 0;
		$uc = 0;
		$num = 0;
		
		//echo '<br />';
		foreach (count_chars($data, 1) as $i => $val) {
			if($i >= ord('a') && $i <= ord('z'))
				$lc++;
			if($i >= ord('A') && $i <= ord('Z'))
				$uc++;
			if($i >= ord('0') && $i <= ord('9'))
				$num++;	
				
				

		}
		//echo "There were $lc lower, $uc upper, $num numbers in the string.<br />";
		return ($lc && $uc && $num);
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'password is not safe enough!';
	}
	
}


class sameAs extends Restriction{
	
	private $otherElement = NULL;
	
	public function __construct($otherElement){
		$this->otherElement = $otherElement;
	}
	
	public function check($val){
		//print_r($this);
		//echo '<br />'.$val.' ~ '.$this->otherElement->getValue().'<br />';
		return $val == $this->otherElement->getValue();
	}
	
	public function comment($val){
		if($this->check($val))
			return '';
		else
			return 'values are not identical!';
	}
	
	
}






	
	
	
	// Auxilary functions: ----------------------------------------------
	
function makeExtras($extras){
	$tail = '';
	foreach($extras as $k=>$v)
		if(is_numeric($k))
			$tail.=' '.$v;
		else
			$tail.=' '.$k.'="'.$v.'"';
		return $tail;
}



?>
