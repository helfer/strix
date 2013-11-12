<?php

/*
 * ******************************************************************************************************************
 * 
 * CLASS FORM ELEMENT
 * 
 * ******************************************************************************************************************
 */
	
	
abstract class FormElement
{
	protected $mLabel = ''; //??? WTF am I doing??
	
	//protected $mNames = array(); this is not needed as it is the same as array_keys($this->mValues)
	protected $mValues = array(); // Array of (name=>value)!
	protected $mDefaultValues = array();
	
	protected $mExtras = array();
	
	protected $mRestrictions = array();
	
	protected $mValid = NULL;
	protected $mChanged = FALSE;
	
	
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','defaults','extras');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('extras'=>array());
	
	//----------------

	/**
	 * 
	 *@ param args : array ( label => String, defaults => array (name=>default_value, ...) , extras => array )
	 */
	public function __construct($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		
		$this->mLabel = $args['label'];
		$this->mDefaultValues = $args['defaults'];
		$this->mValues = $this->mDefaultValues;

		if (!is_array( $args['extras'] ) )
		{
						print_r($args);
			throw new Exception('Form element extras must be an array');

		}
		$this->mExtras = $args['extras'];
	}
	
	public function getLabel()
	{
		return $this->mLabel;
	}
	
	public abstract function getHtml();
	
	
	//TODO: test
	public function setValue($value, $name = '')
	{
		$old_values = $this->mValues[$name];

		if ('' == $name)
		{
			if (!is_array($value))
				throw new FormException('FormElement::setValue argument is not an array: '.$value);
				
			//filter out the values which don't concern this element
			$filtered_input = array_intersect_key($value,$this->mValues);
			
			$size_before = sizeof($this->mValues);
			//TODO: make a workaround//ATTENTION: this will NOT work with numeric keys!!!
			$this->mValues = array_merge($this->mValues,$filtered_input);

			if (sizeof($this->mValues) !== $size_before)
				throw new Exception('Are you using Numeric keys in your form?? It\'s a bad idea!');
		}
		else if (array_key_exists($name,$this->mValues))
		{
				
				$this->mValues[$name] = $value;
				
		}
		else
		{
			throw new FormException('Not a value of this Element: '.$name);
		}
		
		if($this->mDefaultValues != $this->mValues){
			//notice('default:');
			//print_r($this->mDefaultValues);
			//notice('after:');
			//print_r($this->mValues);	
			$this->mChanged = TRUE;
		}
		//notice('after');
		//print_r($this->mValues);
		
	}
	
	public function getNames()
	{
		return array_keys($this->mValues);
	}
	
	
	//TODO: test
	public function getValues($filter_names = array())
	{
		if (empty($filter_names))
			return $this->mValues;
		else
			return array_intersect_key($this->mValues,array_flip($filter_names));	
	}
	
	
	
	public function getValue($name = '')
	{
		//print_r($this->mValues);
		if ('' == $name)	
			return $this->mValues;
		else
			return $this->mValues[$name];
	}
	
	//depends strongly on form initialization, which cannot always be the same.
	//IDEA: store initialization_vector (really hidden form field) in token stuff!!!! 
	//returns only the values which are not equal to the default values.
	public function getChangedValues()
	{
		if (!$this->mChanged)
			return array();
			
		$ret = array();
		foreach ($this->mValues as $k=>$v)
		{
			if($v !== $this->mDefaultValues[$k])
				$ret[$k] = $v;
		}
		
		return $ret;			
	}
	
	
	
	
	public function addRestriction($restr)
	{
		if(!is_subclass_of($restr,'Restriction'))
			throw new Exception(get_class($restr).' is not a restriction');
		
		$this->mRestrictions []= $restr;
	}
	
	
	public function validate()
	{

		$valid = TRUE;
		
		foreach($this->mRestrictions as $res){			
			$valid = $res->validate($this->meValue) && $valid; //ORDER decides if more than one invalid restriction is listed
		}
			
		$this->mValid = $valid;
		
		return $this->mValid;
	}
	
	
	public function getErrors()
	{
		$errors = array();
		
		//only print errors if validate has been called before.
		if(!isset($this->mValid) || $this->isValid())
			return $errors;
			
		//print_r($this->mRestrictions);
		foreach ($this->mRestrictions as $k=>$res)
		{
			if ($err = $res->getErrorMessage())
				$errors[$k]=$err;	
		}
		//print_r($errors);
		return $errors;
	}
	
	public function hasErrors()
	{
		if(!isset($this->mValid) || $this->isValid())
			return false;
		else
			return true;
	}
	
	//TODO: test
	public function reset($names = array())
	{
		//notice($this->mValues);
		//notice($this->mDefaultValues);
		
		if (empty($names))
			$this->mValues = $this->mDefaultValues;
		else
			$this->mValues = array_merge($this->mValues,array_intersect_key($this->mDefaultValues,array_flip($names)));
	
		//notice($this->mValues);
	}
	
	
	public function isValid()
	{
		if (!isset($this->mValid))
			return $this->validate();
			
		return $this->mValid;
	}
	
	public function hasChanged()
	{
		//second part is for checkboxes and the like.
		return $this->mChanged;// || ($this->mValues != $this->mDefaultValues);
	}
	
	//TODO: this method is duplicated!! (form + form_elements)
	//Auxilary function
	protected function makeExtras($extras)
	{
		$tail = '';
		foreach($extras as $k=>$v)
			if(is_numeric($k))
				$tail.=' '.$v;
			else
				$tail.=' '.$k.'="'.$v.'"';
			return $tail;
	}
	
	
	
	/**
	 * this function allows you to create form elements from a list
	 * it facilitates creation by determining the object from the specs
	 * 
	 * @params: Array with Object specifications: array('classname'=>'Select','name'=>'Select1','label'=>'Choose a language','options'=>array('de','fr','it'),'default'=>'0')
	 */
	public static function createFromArray($specs)
	{
		print_r($specs);
		//just passes it on to the actual function implementation of the subclass
		$class = $specs['classname'];
		//unset($specs['classname']);
		//echo $class;
		return new $specs['classname']($specs);
		
	}
	
	public abstract static function newFromArray($ary);
	
	
	/**
	 * checks if the specification array defines all fields required for the construction of the object
	 * If no values are set for an optional argument, the default is taken.
	 */
	
	public static function checkArgs(&$args,$required,$optional)
	{
	if ( !is_array($args) )
		throw new Exception('bad arguments for construction: ' . $args . ' is not an array');
			//print_r($args);
		$args = array_merge($optional, $args);
				//	print_r($args);

		
		$missing = array_diff($required,array_keys($args));
		if(!empty($missing))
		{
			print_r($args);
			print_r($required);
			//echo serialize(array('value'=>0));
			throw new Exception('FormElement creation failed, field missing: ' . reset($missing));
		}
	}
	
	
}

//Not a good solution
/*
class WrapperElement extends FormElement
{
	protected $mLabelElements = array();
	protected $mContentElements = array();
	
	public function __construct()
	{
		parent::__construct('',array());
	}
	
	public function addLabelElement($el)
	{
		$this->mLabelElements []= $el;
		$this->mValues = array_merge_numeric($this->mValues,$el->mValues);
	}
	
	public function addContentElement($el)
	{
		$this-mContentElements []= $el;
		$this->mValues = array_merge_numeric($this->mValues,$el->mValues);
	}
	
	
	public function getLabel()
	{
			
	}
	
	public function getHtml()
	{
		
	}
	
	setValues
	getValue
}*/


/*
 * ******************************************************************************************************************
 * 
 * SIMPLE FORM ELEMENT -*"54-
 * 
 * ******************************************************************************************************************
 */

//Simple Form Element: a form Element with only one Value (most form Elements are of this Type)
abstract class SimpleFormElement extends FormElement
{
	
	protected $meName = NULL;
	protected $meDefaultValue = NULL;
	protected $meValue = NULL;
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','name','value','extras');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('extras'=>array());
	
	/**
	 * @param args array(label,name,value,extras)
	 */
	public function __construct($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		
		extract($args); ///@todo how safe is this?
		
		$name = str_replace(' ','_',$name);
		
		
		parent::__construct( array('label'=>$label, 'defaults'=>array($name=>$value),'extras'=>$extras) );
		$this->meName = $name;
		$this->meDefaultValue = $value;
		$this->meValue = $this->meDefaultValue;
	}
	
	public static function newFromArray($specs)
	{
		
		//some safety checks
		//self::checkSpecArray($spec_ary);
		
		//if omitted, it must be set to an empty array to avoid errors
		if(empty($specs['extras']))
		{
			$specs['extras'] = array();
		}
		
		return self::__construct($specs['label'],$specs['name'],$specs['value'],$specs['extras']);	
	}
	

	
	
	public function setValue($val,$name = '')
	{
		//echo 'setvalue '.$name.' ';
		//print_r($val);
		parent::setValue($val,$this->meName);
		$this->meValue = $val;	
	}
	
	public function getValue($void = NULL)
	{
		return $this->meValue;	
	}
	
	public function reset($names = array())
	{
		parent::reset($names);	
		$this->meValue = $this->meDefaultValue;
	
	}
	
	
}


/*
 * ******************************************************************************************************************
 * 
 * STATIC FORM ELEMENT -*"54-
 * 
 * ******************************************************************************************************************
 */

//a static form element has no value
abstract class StaticFormElement extends SimpleFormElement
{
	public function __construct($value,$label='')
	{
		parent::__construct(array('label'=>$label,'name'=>'staticElement','value'=>$value));
	}
	
	//to prevent form from forwarding anything (relay) to this object.
	//with array() it won't be entered into the directory of the form
	public function getNames()
	{
		return array();	
	}

}


class HtmlElement extends StaticFormElement
{

	public function getHtml()
	{
		return $this->meValue;
	}	
	
}





////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////



class Submit extends Button
{
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('name','value','button_label','extras');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('button_label'=>0,'extras'=>array());
	
	//old constructor
	//public function __construct($name,$default_value,$button_label = 0,$extras = array())
	public function __construct($args)
	{	
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 4 )
				list($args['name'],$args['value'],$args['button_label'],$args['extras']) = func_get_args();
			else if ( func_num_args() == 3 )
				 list($args['name'],$args['value'],$args['button_label']) = func_get_args();
			else if ( func_num_args() == 2 )
				 list($args['name'],$args['value']) = func_get_args();
			else
				throw new Exception('Form Element legacy: wrong number of arguments');
		}
		
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		extract($args);
		
		if($button_label === 0)
			$button_label = $value;
			
		parent::__construct(array('label'=>'','name'=>$name,'value'=>$value,'type'=>'submit','content'=>$button_label,'extras'=>$extras) );
	}
	
}


class SimpleSubmit extends Submit
{

	public function __construct()
	{
		parent::__construct(array('name'=>'submit','value'=>'submit'));
	}	
	
}

class SafeSubmit extends Button
{
	protected $myButton = NULL;
	protected $myCheckbox = NULL;
	
	public function __construct($name,$default_value,$label,$extras = array())
	{
		$this->myButton = new Submit($name,$default_value,$label,$extras);
		$this->myCheckbox = new Checkbox('',$name.'_confirm','OK',FALSE,array('style'=>'vertical-align:middle;'));
		$this->myRestriction = new NotEmptyRestriction();
		$this->myRestriction->setErrorMessages(array('error'=>array('1'=>'Zum löschen Häkchen setzen',
															'2'=>'Cocher la case pour supprimer',
															'4'=>'Check the box to delete'))
															);
		$this->myCheckbox->addRestriction($this->myRestriction);
	}

	
	public function getNames()
	{
		return array_merge($this->myButton->getNames(),$this->myCheckbox->getNames());
	}
	
	public function getHtml()
	{
		$err_msg = '';
		if($err = $this->myRestriction->getErrorMessage())
			$err_msg = '<b><span class="form_error">'.$err.'</span></b><br />';
		return  $err_msg
				.'<span class="safe_submit" style="padding:1px;border:1px dotted #CF9F3C;">'
				.$this->myCheckbox->getHtml()
				.' '.$this->myButton->getHtml()
				.'</span>';	
	}
	
	public function setValue($val,$name)
	{
		if(in_array($name,$this->myCheckbox->getNames()))
		{
			$this->myCheckbox->setValue($val,$name);
		} 
		else if(in_array($name,$this->myButton->getNames()))
		{
			$this->myButton->setValue($val,$name);
		}
			
	}		


	public function isPressed()
	{
		return $this->myButton->isPressed() && $this->myCheckbox->isValid();	
	}

	
}

//button always keeps submitting its default value!
//TODO: add a thing like protected $meePressed that is set to true on setValue!
class Button extends SimpleFormElement
{
	protected $meeType = NULL;
	protected $meeContent = NULL;
	protected $meePressed = FALSE;
	
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','name','value','type','content','extras');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('extras'=>array());
	
	//public function __construct($label,$name,$value,$type,$content,$extras = array())
	public function __construct($args)
	{	
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 6 )
				list($args['label'],$args['name'],$args['value'],$args['type'],$args['content'],$args['extras']) = func_get_args();
			else if ( func_num_args() == 5 )
				 list($args['label'],$args['name'],$args['value'],$args['type'],$args['content']) = func_get_args();
			else
				throw new Exception('Form Element legacy: wrong number of arguments');
		}
		
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		extract($args);
		
		//bloody internet explorer fix:
		$ieName = $name.'#'.$value;
		//and now the other fix: spaces are replaced with _ by the browser when submitted...
		$ieName = str_replace(' ','_',$ieName);
		//echo $ieName;
		$value = 'Seriously_GET_FIREFOX';
		
		parent::__construct( array('label'=>$label,'name'=>$ieName,'value'=>$value,'extras'=>$extras) );
		$this->meeContent = $content;
		$this->meeType = $type;
	}
	
	//bloody internet explorer fix: (who uses that damn thing anyway?
	public function getValue($void = NULL)
	{
		list($name,$value) = explode('#',$this->meName);
		return $value;	
	}
	
	
	public function getHtml()
	{
		return '<button name="'.$this->meName.'" type="'.$this->meeType.'" id="'.$this->meName.'" value="'.$this->meDefaultValue.'" '.$this->makeExtras($this->mExtras).'>'.$this->meeContent.'</button>';
	}
	
	
	public function setValue($val,$name='')
	{
		//bloody internet explorer workaround
		
		//parent::setValue($val,$name);
		//notice('cmp: '.$val.' '.$this->meDefaultValue);
		if($name === $this->meName)
			$this->meePressed = TRUE;
		else
			$this->meePressed = FALSE;
			
		//if($this->meePressed)
		//	notice('pressed');
	}
	
	
	public function isPressed()
	{
		return $this->meePressed;	
	}
	
	public function hasChanged()
	{
		//please ppl, buttons don't change!
		return FALSE;
	}
	
}


class Checkbox extends SimpleFormElement
{
	protected $meeChecked = FALSE;
	
	public function __construct($label,$name,$value,$checked = FALSE,$extras = array())
	{
		$this->meeChecked = $checked;
			
		parent::__construct(array('label'=>$label,'name'=>$name,'value'=>$value,'extras'=>$extras));
		
		//comment ?
		//if($checked && $checked!='') $this->setValue($value);
		//else $this->setValue(FALSE);	
	}	
	
	public function getHtml(){
		return '<input type="checkbox" name="'.$this->meName.'" value="'.$this->meDefaultValue.'" class="checkbox"'.$this->makeExtras($this->mExtras).' />';
	}
	
	public function setValue($val,$name = ''){
		//echo( $this->meName.' setting value to '.$val.'<br />');
		if($val == $this->meDefaultValue)
		{
			$chg = !$this->meeChecked;	
			//echo 'check';
			$this->meeChecked = TRUE;	
		}
		else
		{
			//echo 'nocheck';
			$val = FALSE;	
			$chg = $this->meeChecked;
		}
		
		parent::setValue($val);
		$this->mChanged = $chg;	 //have to store it temporarily because of parent...
		//print_r($this);

	}
	
	//Auxilary function
	protected function makeExtras($extras)
	{
		
		$tail = parent::makeExtras($extras);
		
		if($this->meeChecked)
			$tail .= ' checked';
			
		return $tail;
	}
	
}

class CheckboxWithText extends SimpleFormElement
{
	protected $meeChecked = FALSE;
	protected $meeText = '';
	
	public function __construct($label,$name,$value,$text,$checked = FALSE,$extras = array())
	{
		$this->meeChecked = $checked;
			
		parent::__construct(array('label'=>$label,'name'=>$name,'value'=>$value, 'extras'=>$extras));

		$this->meeText=$text;
		
		if($checked && $checked!='') $this->setValue($value);
		else $this->setValue(FALSE);										
		
	}	
	
	public function getHtml(){		
		$xhtml = '<p class="text"><input type="checkbox" name="'.$this->meName.'" value="'.$this->meDefaultValue.'" class="checkbox"'.$this->makeExtras($this->mExtras).' /> '.$this->meeText.'</p>';
			
		return $xhtml;
	}
	
	public function setValue($val,$name = ''){
		//echo( $this->meName.' setting value to '.$val.'<br />');
		if($val == $this->meDefaultValue)
		{
			$chg = !$this->meeChecked;	
			//echo 'check';
			$this->meeChecked = TRUE;	
		}
		else
		{
			//echo 'nocheck';
			$val = FALSE;	
			$chg = $this->meeChecked;
		}
		
		parent::setValue($val);
		$this->mChanged = $chg;	 //have to store it temporarily because of parent...
	}
	
	//Auxilary function
	protected function makeExtras($extras)
	{
		
		$tail = parent::makeExtras($extras);
		
		if($this->meeChecked)
			$tail .= ' checked';
			
		return $tail;
	}
	
}


class MultiCheckbox extends FormElement
{
	protected $meLabels = array();
	protected $meName = 'multicheck';
	
	protected $meElementSeparator = '<br />';
	protected $meLabelSeparator = ' ';
	protected $meReverse = FALSE; //if TRUE, checkbox comes before label
	
	
	//values are array of element names with indication if checked or not: array('el_1'=>TRUE,'el_2'=>FALSE);
	//labels must use the same keys as values
	public function __construct($label_top,$name,$labels,$values,$options = array())
	{
		if(count($labels) != count($values)){
			print_r($labels);
			print_r($values);
			throw new Exception('Number of Labels is not equal to number of Values');
		}
		
		parent::__construct(array('label'=>$label_top,'defaults'=>$values) );
		$this->meLabels = $labels;
		$this->meName = $name;
		
		
		//set options
		if(isset($options['elem_separator']))
			$this->meElementSeparator = $options['elem_separator'];
		if(isset($options['label_separator']))
			$this->meLabelSeparator = $options['label_separator'];
		if(isset($options['reverse']))
			$this->meReverse = $options['reverse'];
		
	}	
	
	public static function newFromArray($specs)
	{
		
		///@todo CHECK specs !!!	
		
		
		return self::__construct($specs['label_top'],$specs['name'],$specs['labels'],$specs['values']);
		
	}
	
	public function getHtml(){
		$xhtml = '';
		
		//print_r($this->meChecked);
		//print_r($this->mValues);
		foreach($this->meLabels as $k=>$lbl){
			
			$chbox = new Checkbox($lbl,$this->meName.'[]',$k,$this->meValues[$k]);
			
			if($this->meReverse)
				$xhtml .= $chbox->getHtml() . $this->meLabelSeparator . $chbox->getLabel() . $this->meElementSeparator;
			else
				$xhtml .= $chbox->getLabel() . $this->meLabelSeparator . $chbox->getHtml(). $this->meElementSeparator;
		}
		
		return $xhtml;
	}
	
	public function setValue($val,$name = ''){
		foreach($this->mValues as $name=>$check)
		{
				$this->mValues[$name] = in_array($name,$val);
		}
	}
	
	public function getNames(){
		return array($this->meName); //only return one name to get the array instead of lots of independent values.	
	}
	
	public function getChecked()
	{
		$ret = array();
		foreach($this->mValues as $name=>$check)
		{
				if($this->mValues[$name])
					$ret []= $name;
		}
		return $ret;
		
	}
	

}


class MultiCheckboxTable extends FormElement
{
	protected $meLabels = array();
	protected $meName = 'multichecktable';
	
	protected $meNumCol = 2;
	protected $meLabelSeparator = ' ';
	protected $meReverse = FALSE; //if TRUE, checkbox comes before label
	
	
	//values are array of element names with indication if checked or not: array('el_1'=>TRUE,'el_2'=>FALSE);
	//labels must use the same keys as values
	public function __construct($label_top,$name,$labels,$values,$options = array())
	{
		if(count($labels) != count($values)){
			print_r($labels);
			print_r($values);
			throw new Exception('Number of Labels is not equal to number of Values');
		}
		
		parent::__construct(array('label'=>$label_top,'defaults'=>$values) );
		$this->meLabels = $labels;
		$this->meName = $name;
		
		
		//set options
		if(isset($options['num_col']))
			$this->meNumCol = $options['num_col'];
		if(isset($options['label_separator']))
			$this->meLabelSeparator = $options['label_separator'];
		if(isset($options['reverse']))
			$this->meReverse = $options['reverse'];
		
	}	

	public static function newFromArray($specs)
	{
		
		///@todo CHECK specs !!!			
		return self::__construct($specs['label_top'],$specs['name'],$specs['labels'],$specs['values']);
		
	}
		
	public function getHtml(){
		$xhtml = '<table class="multiCheckboxTable"><tr>';
		$num=0;
		foreach($this->meLabels as $k=>$lbl){
			++$num;
			$chbox = new Checkbox($lbl,$this->meName.'[]',$k,$this->mValues[$k]);
			
			if($this->meReverse)
				$xhtml .= '<td>'.$chbox->getHtml() . $this->meLabelSeparator . $chbox->getLabel().'</td>';
			else
				$xhtml .= '<td>'.$chbox->getLabel() . $this->meLabelSeparator . $chbox->getHtml().'</td>';

			if($num == $this->meNumCol){
				$num=0;
				$xhtml .= '</tr><tr>';
			}

		}
		
		return $xhtml.'</tr></table>';
	}

	public function setValue($val,$name = ''){
		foreach($this->mValues as $name=>$check)
		{
				$this->mValues[$name] = in_array($name,$val);
		}
	}
	
	public function getNames(){
		return array($this->meName); //only return one name to get the array instead of lots of independent values.	
	}
	
	public function getChecked()
	{
		$ret = array();
		foreach($this->mValues as $name=>$check)
		{
				if($this->mValues[$name])
					$ret []= $name;
		}
		return $ret;
		
	}
	
}



//TODO: make this an abstract Object!!!
class Input extends SimpleFormElement{
	
	protected $meeType = NULL;
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','name','value','type','extras');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('extras'=>array());
	
	//old function definition:
	//public function __construct($label,$type,$name,$value,$extras=array()){
	
	public function __construct($args)
	{	
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 4 )
				list($args['label'],$args['type'],$args['name'],$args['value']) = func_get_args();
			else if ( func_num_args() == 5 )
				 list($args['label'],$args['type'],$args['name'],$args['value'],$args['extras']) = func_get_args();
			else
				throw new Exception('Form Element legacy: wrong number of arguments');
		}
		
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		
		$this->meeType = $args['type'];	
		unset($args['type']);
		
		parent::__construct($args);
	}
	
	public function getHtml(){
		return '<input type="'.$this->meeType.'" name="'.$this->meName.'" value="'.$this->meValue.'" class="'.$this->meeType.'"'.$this->makeExtras($this->mExtras).' />';
	}
	
	public function setValue($val,$name = ''){
		parent::setValue($val,$name);
		
		switch($this->meeType)
		{
			
			case 'checkbox':
			case 'radio':
				$this->mExtras []= 'checked';
				break;
				
			default:
				//VOID
		}

	}
	
}


class TextInput extends Input
{
	//old constructor
	//public function __construct($label,$name,$value,$extras = array())
	public function __construct($args)
	{	
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 3 )
				list($args['label'],$args['name'],$args['value']) = func_get_args();
			else if ( func_num_args() == 4 )
				 list($args['label'],$args['name'],$args['value'],$args['extras']) = func_get_args();
			else
			{
				print_r( func_get_args() );
				throw new Exception('Form Element legacy: wrong number of arguments');
				
			}
		}
		
		$args['type'] = 'text';
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);

		parent::__construct($args);

	}

}

class NumericInput extends TextInput
{

	public function __construct($label,$name,$value,$range = array(), $extras= array())
	{
		parent::__construct($label,$name,$value,$extras);
		
		$this->addRestriction(new IsNumericRestriction());
		if(isset($range['max']) && isset($range['min']))
			$this->addRestriction(new InRangeRestriction($range['min'],$range['max']));
				
	}	
	
	
}

class DateInput extends TextInput
{

	public function __construct($label,$name,$value = '0000-00-00',$begin = '0000-00-00',$end = false, $extras= array())
	{
		parent::__construct($label,$name,$value,$extras);
		
		$this->addRestriction(new IsDate($begin,$end));				
	}	
	
	
}

class DateWithThreeInputs extends FormElement
{
	protected $meLabels = array();
	protected $meName = 'DateWithThreeInputs';
	
	protected $firstPossibleDate = '0000-00-00';
	protected $lastPossibleDate = false;
	
	
	//values is an array with current value: array('day'=>21,'month'=>2, 'year'=>2010);
	//labels must use the same keys as values: array('day'=>'Tag', 'month'=>'Monat', 'year'=>'Jahr')
	//other keys are ignored!
	public function __construct($label_top,$name,$labels,$values,$options = array())
	{		
		parent::__construct(array('label'=>$label_top,'defaults'=>$values) );
		$this->meLabels = $labels;
		$this->meName = $name;		
		$this->meValue=$values;
		
		//set options
		if(isset($options['begin'])) $this->firstPossibleDate=$options['begin'];
		if(isset($options['end'])) $this->lastPossibleDate=$options['end'];	
		
		//set restriction
		$this->addRestriction(new ArrayIsDate($this->firstPossibleDate,$this->lastPossibleDate));	
	}	
	
	public static function newFromArray($specs)
	{		
		return self::__construct($specs['label_top'],$specs['name'],$specs['labels'],$specs['values'], $specs['options']);		
	}
	
	public function getHtml(){
		$xhtml = '';
		//day
		if(isset($this->meValue['day'])) $val=$this->meValue['day']; else $val='';
		$day = new Input('', 'text', $this->meName.'[]', $val, array('size'=>2));						

		//month
		if(isset($this->meValue['month'])) $val=$this->meValue['month']; else $val='';
		$month = new Input('', 'text', $this->meName.'[]', $val, array('size'=>2));
		
		//month
		if(isset($this->meValue['year'])) $val=$this->meValue['year']; else $val='';
		$year = new Input('', 'text', $this->meName.'[]', $val, array('size'=>4));
		
		//write
		$xhtml = $day->getHtml().' '.$this->meLabels['day'].' '.$month->getHtml().' '.$this->meLabels['month'].' '.$year->getHtml().' '.$this->meLabels['year'];
		
		return $xhtml;
	}
	
	public function setValue($val,$name = ''){				
		$this->meValue=array('day'=>$val[0], 'month'=>$val[1], 'year'=>$val[2]);			
		$this->mValues=array('day'=>$val[0], 'month'=>$val[1], 'year'=>$val[2]);			
	}

	public function getNames(){				
		return array($this->meName); 
	}

}


//used to identify exact form if used repeatedly
//TODO: add restrictions by default!
class Hidden extends SimpleFormElement
{
	
	public function __construct($name,$value){
		parent::__construct(array('label'=>'' ,'name'=>$name,'value'=>$value));
	}
	
	public function getHtml(){
		return '<input type="hidden" name="'.$this->meName.'" value="'.$this->meValue.'" />';	
	}
	
	
}







class Select extends SimpleFormElement
{
	protected $meeOptions = array();
	protected $meeMulti = FALSE;
	
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','name','options','value','extras','multi');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('value'=>NULL,'extras'=>array(),'multi'=>FALSE);
	
	
	//old constructor definition
	//public function __construct($label,$name,$options,$value=array(),$extras = array(),$multi = FALSE)

	///@todo Remove all that legacy constructor stuff asap
	public function __construct($args)
	{	
		
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 6 )
				list($args['label'],$args['name'],$args['options'],$args['value'],$args['extras'],$args['multi']) = func_get_args();
			else if ( func_num_args() == 5 )
				 list($args['label'],$args['name'],$args['options'],$args['value'],$args['extras']) = func_get_args();
			else if ( func_num_args() == 4 )
				 list($args['label'],$args['name'],$args['options'],$args['value']) = func_get_args();
			else if ( func_num_args() == 3 )
				 list($args['label'],$args['name'],$args['options']) = func_get_args();
			else
				throw new Exception('Form Element legacy: wrong number of arguments');
		}
		
		//blunt, but efficient
		if( isset( $args['default_selected']))
		{
			print_r($args);
			throw new Exception('deprecated "default_selected" in arguments. change variable name to "value"');
		}
		
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		extract($args);
		
		$this->meeOptions = $options;
		$this->meeMulti = $multi;
		
		if($multi)
			$extras []= 'multiple';
			
			//print_r($value);
			//var_dump($multi);
		
		if(!$multi && !isset($value))
			$value = current(array_keys($options));
		if($multi && !isset($value))
			$value = array();
			
		parent::__construct( array('label'=> $label, 'name'=>$name, 'value'=>$value,'extras'=>$extras));
		
		$this->addRestriction(new InListRestriction(array_keys($options)));
	}
	
	public function getHtml(){
		//print_r($this);
		$sup = $this->meeMulti ? '[]' : '';
		
		$html = '<select name="'.$this->meName.$sup.'" id="'.$this->meName.'" '.$this->makeExtras($this->mExtras).'>';
		
		//var_dump($this->meValue);
		
		foreach ($this->meeOptions as $value=>$text)
		{
			//the default being not selected.
			$select = '';
			
			if ( $this->meeMulti )
			{
				if( in_array($value,$this->meValue) ) 
					$select = 'selected';
			}
			else
			{
				//notice($value . '=?' . $this->meValue);
				if( $value == $this->meValue )
					$select = 'selected';
			}
				
			
			$html .= '<option value="'.$value.'" '.$select.'>'.$text."</option>\n";
			
		}

		return $html.'</select>';
	}
	
	public function getOptionValue($key)
	{
		return $this->meeOptions[$key];	
	}
	
	
}


class DataSelect extends Select{
	
	const Glue = ' ';
	
	//list of fields required for construction:
	protected static $mRequiredArgs = array('label','name','query_args','selected','extras','multi');
	//if not set, these values will be applied as default BEFORE checking
	protected static $mOptionalArgs = array('selected'=>array(),'extras'=>array(),'multi'=>FALSE);

	//old constructor
	//public function __construct($label,$name,$query_args,$selected = array(),$extras = array(),$multi = FALSE){

	public function __construct($args)
	{	
		//legacy compatibility:
		if ( func_num_args() > 1 )
		{
			$args = array();
			if ( func_num_args() == 6 )
				list($args['label'],$args['name'],$args['query_args'],$args['selected'],$args['extras'],$args['multi']) = func_get_args();
			else if ( func_num_args() == 5 )
				 list($args['label'],$args['name'],$args['query_args'],$args['selected'],$args['extras']) = func_get_args();
			else if ( func_num_args() == 4 )
				 list($args['label'],$args['name'],$args['query_args'],$args['selected']) = func_get_args();
			else if ( func_num_args() == 3 )
				 list($args['label'],$args['name'],$args['query_args']) = func_get_args();
			else
				throw new Exception('Form Element legacy: wrong number of arguments');
		}
		
		$this->loadFromArray($args);
	}
	
	/**
	 * Does the actual constructing of the Object, introduced for compatibility with old constructors
	 */
	private function loadFromArray($args)
	{
		self::checkArgs($args,self::$mRequiredArgs,self::$mOptionalArgs);
		extract($args);
		
		$options = SqlQuery::getInstance()->assocQuery($query_args['query'],$query_args['keys'],$query_args['values']);
			
		//because the values are arrays, they need to be imploded to be displayed
		$options = array_map(array('DataSelect','fastImploder'),$options);
		
		if(empty($options)){
			$options = array('-1'=>'no options');
			$extras []= 'disabled';
		}
		
		parent::__construct( array('label'=>$label,'name'=>$name,'options'=>$options,'value'=>$selected,'extras'=>$extras,'multi'=>$multi) );
		
	}
	
	public static function fastImploder($arr){
		return implode(self::Glue,$arr);
	}
	
	/*
	private static function implode_values($glue,$arr){
		array_walk($arr,array('DataSelect','imploder'),$glue);
	}
	
	public static function imploder(&$item,$key,$glue){
		$item = implode($glue,$item);	
	}
	*/
}



class Textarea extends SimpleFormElement{
	
	protected $meeCols = 0;
	protected $meeRows = 0;
	
	public function __construct($label,$name,$value,$rows,$cols,$extras=array()){
		parent::__construct(array('label'=>$label,'name'=>$name,'value'=>$value,'extras'=>$extras));
		$this->meeRows = $rows;
		$this->meeCols = $cols;
	}
	
	public function getHtml()
	{
		return '<textarea name="'.$this->meName.'" rows="'.$this->meeRows.'" cols="'.$this->meeCols.'" id="'.$this->meName.'"'.$this->makeExtras($this->mExtras).'>'.$this->meValue.'</textarea>';
	}
	
}

class FileInput extends Input
{
	public function __construct($label,$name)
	{
		parent::__construct($label,'file',$name,'');
	}	

}

class FileInputMultiple extends Input
{
	public function __construct($label,$name)
	{
		parent::__construct($label,'file',$name,'');
	}	
	
	public function getHtml(){
		return '<input type="'.$this->meeType.'" name="'.$this->meName.'[]" value="'.$this->meValue.'" class="'.$this->meeType.'"'.$this->makeExtras($this->mExtras).' multiple />';
	}

}

//TODO: missing Radio input
class Radio extends SimpleFormElement
{
	protected $meeChecked = FALSE;
	
	public function __construct($label,$name,$value,$checked = FALSE,$extras = array())
	{
		$this->meeChecked = $checked;
			
		parent::__construct(array('label'=>$label,'name'=>$name,'value'=>$value,'extras'=>$extras));
		
		//$checked ? $this->setValue($value) : $this->setValue(FALSE);
	}	
	
	public function getHtml()
	{
		return '<input type="radio" name="'.$this->meName.'" value="'.$this->meDefaultValue.'" class="checkbox"'.$this->makeExtras($this->mExtras).' />';
	}
	
	public function setValue($val,$name = ''){
		//echo( $this->meName.' setting value to '.$val.'<br />');
		if($val == $this->meDefaultValue)
		{
			$chg = !$this->meeChecked;	
			echo 'check';
			$this->meeChecked = TRUE;	
		}
		else
		{
			echo 'nocheck';
			$val = FALSE;	
			$chg = $this->meeChecked;
		}
		
		parent::setValue($val);
		$this->mChanged = $chg;	 //have to store it temporarily because of parent...
		//print_r($this);

	}
	
}

//TODO: missing multiple Select and Optgroup possibility
//TODO: missing Fieldset (fieldset contains other objects, acts as passthrough)


?>
