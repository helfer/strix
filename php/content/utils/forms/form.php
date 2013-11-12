<?php
/*
 *      form.php
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

define('VERTICAL',3);
define('HORIZONTAL',12);




abstract class Form {
	
	protected $name = 		'default_form_name';
	protected $id = 		-1;
	protected $action = 	'';
	protected $method = 	'POST';
	protected $mExtras = 	array();
	
	protected $mVector = array(); //The vector can be used in dynamic forms to store the form structure between requests.
	protected $token = 		NULL;
	
	protected $elements = 	array(); //stores the elements
	protected $relay =		array(); //stores the relay information
	protected $fieldsets = 	array();
	//protected $invariants = array();
	
	protected $target_type = 'content';
	protected $target_id = -1;
	
	protected $mKeepAlive = FALSE; //if this is activated, form stores its values in Session.
	protected $mPopulated = FALSE;
	
	protected $mButtonPressed = NULL;
		
	protected $valid = FALSE;
	
	protected $mStyleClass = 'standard';
	
	
	//TODO: idea: just use name, action extras. Extract method + target type from extras!
	
	public function __construct($name, $action = '', $extras = array(), $target_id = -1,$target_type = 'content',$method = 'post')
	{
		
				//TODO: put this in ONE place only!
		//to enable use of __METHOD__ and __FUNCTION__ in form names.
		if($sub = stristr($name,'::'))
		{
			notice('form create fN: '.$name);
			//debug_print_backtrace();
			$name = substr($sub,2);
		}

		$this->name = $name;
		$this->action = $action;
		$this->method = strtolower($method); //for standards compliance
		$this->mExtras = $extras;
		
		$this->target_id = $target_id;
		$this->target_type = $target_type;
		
		//print_r($_POST);
		//debug_print_backtrace();

	}	
	
	public function getName()
	{
		return $this->name;	
	}
	
	//TODO: keep-alive for Vector!!!
	public function setVector($vector)
	{
		$this->mVector = $vector;	
	}
	
	public function getVector()
	{
		return $this->mVector;	
	}
	
	public function getVectorValue($name)
	{
		if(isset($this->mVector[$name]))
			return $this->mVector[$name];	
		else
			throw new Exception($name.' not found in form Vector');
	}
	
	public function setVectorValue($name,$val)
	{
		if(isset($this->mVector[$name]))
			$this->mVector[$name] = $val;	
		else
			throw new Exception($name.' not found in form Vector');		
	}
	
	
	//gets a token and outputs the html code.
	public function getHtml($target_id = -1,$target_type = '')
	{
		if(empty($this->elements))
			return '<!-- no elements in form '.$this->name.'-->';
		
		if ($target_id <= 0)
			$target_id = $this->target_id;
			
		if ('' == $target_type)
			$target_type = $this->target_type;
			
		if ($target_id <= 0 || '' == $target_type)
			throw new FormException('form target is invalid (type='.$target_type.' id='.$target_id.')');
			
			
			
		//token counts for exactly one target type and id !!!
		//$token = $GLOBALS['domain']->get_token($this->name,$target_type,$target_id,$this->mVector);
		$expire = 0;
		$token = $GLOBALS['InputHandler']->makeToken($this->name, $target_type, $target_id, $expire, $this->mVector);
		
		$xhtml = '';
		
		
		//print initial block (required so information will get to the right form and content)
		//TODO: add the extras!
		$xhtml = '<form id="'.$this->name.'" class="'.$this->mStyleClass.'" action="'.$this->action.'" method="'.$this->method.'" '.$this->makeExtras($this->mExtras).'>
					<p>
						<input type="hidden" name="'.TOKEN_FIELD_NAME.'" value="'.$token.'" />';
						//<input type="hidden" name="form_name" value="'.$this->name.'" />
						//<input type="hidden" name="target_type" value="'.$target_type.'" />
						//<input type="hidden" name="target_id" value="'.$target_id.'" />						
		
		
		if ($this->mKeepAlive && ! $this->mPopulated)
			$this->setValues($this->retrieveValuesFromSession());
		
		$xhtml .= $this->printFormElements();	
				
		$xhtml .=	'</p>';
		$xhtml .= '</form>';
		//TODO: return form html.
		return $xhtml;
	}
	
	//TODO: this method is duplicated (form + form_elements)
	protected function makeExtras($extras)
	{
		//print_r($extras);
		if(empty($extras))
			return '';
		
		$tail = '';
		foreach($extras as $k=>$v)
			if(is_numeric($k))
				$tail.=' '.$v;
			else
				$tail.=' '.$k.'="'.$v.'"';
			return $tail;
	}
	
	
	//TODO: order must be respected!
	protected abstract function printFormElements();
	
	
	protected abstract static function printErrors($errs);
	
	
	
	//-------------------------------------------------------------------
	
	//element id can be number or name or whatever, but 
	public function addElement($id, &$element = NULL)
	{
		
		// a bit shaky, but who cares?
		if(is_null($element))
		{
			$element = $id;
			$id = reset($element->getNames());
		}
		
		if (!is_a($element,'FormElement'))
			throw new FormException('Element is not a Form element: '.$id);
			
		if (array_key_exists($id,$this->elements))
			throw new FormException('Element with id '.$id.' already exists!');
			
			
			
		$this->elements[$id]=$element;
		
		//don't change the way the names are found! some Elements rely on this to stay hidden
		foreach($element->getNames() as $name)
		{
			if(isset($this->relay[$name]))
				$this->relay[$name] []= $id;
				//return;
				//throw new FormException('another element with variable name '.$name.' already exists!');
			else	
				$this->relay[$name] = array($id);			
		}
			
	}
	
	public function addHtml($html)
	{
		$this->addElement('html'.rand(1000,999999),new HtmlElement($html));
	}
	
	
	public function addElementArray($el_array)
	{
		foreach($el_array as $id=>$el)
			$this->addElement($id,$el);	
	}
	
	//TODO: throw error instead??
	public function getElement($id)
	{
		if (isset($this->elements[$id]))
			return $this->elements[$id];
		else
			throw new FormError('Element '.$id.' does not exist!');
	}
	
	
	
	
	public function removeElement($id)
	{
		if(isset($this->elements[$id]))
			unset($this->elements[$id]);
		else
			throw new FormError('Element '.$id.' does not exist!');
	}
	
	//------------------------------
	
	//getters + setters for other vars?
	
	
	//------------------------------
	
	//TODO: merge functions
	public function getSomeElementValues($elements)
	{
		return array_intersect_key($this->getElementValues(),array_flip($elements));	
	}
	
	
	
	public function getElementValues($force_arrays = FALSE,$include_html = FALSE)
	{
		$ret = array();		
		foreach($this->elements as $id=>$element)
		{
			if($include_html || !is_a($element,'HtmlElement')){
				$ret[$id] = $force_arrays?$element->getValues():$element->getValue();				
			}
		}

		return $ret;
	}
	
	public function getElementValue($id)
	{
		if(isset($this->elements[$id]))
			return $this->elements[$id]->getValue();
		else
			throw new Exception('No form element with name '.$id);
	}
	
	
	/**
	 * Returns values of all writeable elments (leaves out Static elements and buttons)
	 */
	public function getWriteableElementValues()
	{
		$ret = array();
		foreach ($this->elements as $id=>$el)
		{
			if (!is_a($el,'StaticFormElement') && !is_a($el,'Button'))	
				$ret[$id]= $el->getValue();
		}
		return $ret;
			
	}
	
	public function getChangedElementValues()
	{
		$ret = array();
		foreach($this->getChangedElements() as $id=>$el)
			$ret[$id] = $el->getValue();
			
		return $ret;
	}
	
	//TODO: i'm sure there are shorter ways to do this. filter?
	public function getChangedElements()
	{
		$ret = array();
		foreach ($this->elements as $id=>$el)
		{
			if ($el->hasChanged())	
				$ret[$id]= $el;
		}
		return $ret;
	}
	
	
	//to get single values (as if from POST):
	public function getPostValue($name){
		$id_arr = $this->relay[$name];
		$id = $id_arr[0];
		return $this->elements[$id]->getValue($name);	
	}
	
	public function getButtonPressed()
	{
		return $this->mButtonPressed;
	}
	
	public function isPopulated()
	{
		return $this->mPopulated;	
	}
	
	//use this to overwrite values eventually stored in session.
	public function populate($arr)
	{
		//print_r($arr);
		$this->mPopulated = TRUE;
		$this->setValues($arr,TRUE);
	}
	
	//alias of populate
	//SETS only the registered values. The rest is just ignored.
	public function setValues($arr,$store = FALSE)
	{
		//echo pretty_print_r($arr);
		//echo pretty_print_r($this->relay);
		foreach($this->relay as $name=>$element_ids){
			
			foreach($element_ids as $id)
			{
				if(isset($arr[$name]))
					$value = $arr[$name];
				else
				{
					//TODO: this is a very ugly workaround!
					if(is_a($this->elements[$id],'Checkbox') || is_a($this->elements[$id],'CheckboxWithText'))
						$value = NULL;
					else if (is_a($this->elements[$id],'MultiCheckbox'))
						$value = array();
					else if(is_a($this->elements[$id],'Radio'))
						$value = NULL;
					else
						continue;
					//echo('<br />'.$name.' not found in arr<br />');
				}	
				
				$this->elements[$id]->setValue($value,$name);
				//notice($id.': '.$store.' >'.is_a($this->elements[$id],'Button').' >'.$this->elements[$id]->isPressed());
				//var_dump($store);
				//var_dump(get_class($this->elements[$id]));
				//if(is_a($this->elements[$id],'Button'))
				//	var_dump($this->elements[$id]->isPressed());
				if($store && is_a($this->elements[$id],'Button') && $this->elements[$id]->isPressed())
				{
					//echo 'PRESSED';
					$this->mButtonPressed = $id;
					//echo '<br />pressed: '.$id;
				}
				else
				{
					//echo '<br />not pressed: '.$id;	
				}
				
				if ($this->mKeepAlive && $store)
					self::storeValueInSession($name,$value);		
			}
				
		}
				//throw new UserInputException('form::populate(): name not found: '.$name);
				//debug('form::populate(): name not found: '.$name);	
	}
	
	
	
	
	public function setValue($id,$value,$name = '')
	{
		$this->elements[$id]->setValue($value,$name);
	}
	
	
	
	
	public function reset()
	{
		
		foreach($this->elements as $el)
			$el->reset();
	}
	
	
	//-----------------------------
	
	public function validate()
	{
		$valid = TRUE;
		
		foreach($this->elements as $el)
			$valid = $el->validate() && $valid; //ORDER! ORDER! ORDER! (lazy evaluation)
			
		$this->valid = $valid;
		
		return $this->valid;
	}
	
	
	
	public function isValid()
	{
		return $this->valid;
	}
	
	
	public function stayAlive()
	{
		$this->mKeepAlive = TRUE;
	}
	
	//to keep form alive, even if user goes to another page
	public function storeValueInSession($name,$value)
	{
		$_SESSION['stored_forms'][$this->name.$this->target_type][$name] = $value;
	}
	
	//to retrieve a form which is kept alive.
	public function retrieveValuesFromSession()
	{
		if (isset($_SESSION['stored_forms'][$this->name.$this->target_type]))
			return $_SESSION['stored_forms'][$this->name.$this->target_type];
		else
			return array();
	}
	
}
/*
 * 
 * 				CLASS SIMPLE FORM --------------************************
 * 
 */	
	
class SimpleForm extends Form
{
	protected $mElementSpacer = '';
	
	protected $mStyleClass = 'simple';
	
	
	protected final function printFormElements()
	{
		//$xhtml = '<p>';
		$xhtml = '';
		foreach ($this->elements as $el)
		{
			//TODO: clean up style stuff!!!!
			$ok = !$el->hasErrors();
			if ( !$ok )
			{
				$xhtml .= '<div style="border: 1px dotted red;background-color:#FFEEEE">';
				$xhtml .= self::printErrors($el->getErrors());
			}
			$label = $el->getLabel();
				
			$xhtml .= "\n". '<b>' . (strlen($label) ? $label.' ' : '') .'</b>' . $el->getHtml() . $this->mElementSpacer;
		
			if(!$ok)
				$xhtml .= '</div>';
		}	
		
		return $xhtml;//.'</p>';
	}
	
	
	protected static function printErrors($errs)
	{
		if (empty($errs))
			return '';
			
		$str = '<ul>';
		foreach($errs as $error)
		{
			$str .= '<li><b><span class="form_error">'.$error.'</span></b></li>';
		}
		return $str.'</ul>';
	}
	
	
	public final function setLayout($layout)
	{	
		switch($layout)
		{
			case VERTICAL:
				$this->mElementSpacer = '<br />';
			break;
			case HORIZONTAL:
				$this->mElementSpacer = '';
			break;
			default:
				throw new FormException('Invalid Layout: '.$layout);
		}	
	}
	
}





/*
 * CLASS TABULAR FORM
 * 
 **********************************************************************/


class TabularForm extends Form{
	
	protected $mLabelWidth = '1em';
	protected $mContentWidth = '1em';
	
	protected $mStyleClass = 'tabular';
	
	protected function printFormElements()
	{
		$xhtml = '';
	
		$xhtml .= '<table class="table_form">';
	
		foreach($this->elements as $el)
		{
			//if(strip_tags($el->getLabel().$el->getHtml()) == '')
			//	continue;
			
			$xhtml .= '<tr>
						<td class="label" width='.$this->mLabelWidth.'><b>'.$el->getLabel().'</b></td>
						<td class="content" width='.$this->mContentWidth.'>'.self::printErrors($el->getErrors()).$el->getHtml().'</td>
					   </tr>';
			
		}
		
		$xhtml .= '</table>';
		
		return $xhtml;
	}
	
	
	protected static function printErrors($errs)
	{
		if (empty($errs))
			return '';
			
		$str = '';
		foreach($errs as $error)
		{
			$str .= '<b><span class="form_error">'.$error.'</span></b><br />';
		}
		return $str;
	}
	
		
	
	public function setProportions($labels,$content)
	{
		$this->mLabelWidth = $labels;
		$this->mContentWidth = $content;
	}
}








/*
 * CLASS AccurateForm
 **********************************************************************/

class AccurateForm extends Form
{
	
	//TODO: have several tables, not just one!!!
	protected $mStyleClass = 'accurate';

	protected $mGridSizeX = 5;
	protected $mGridSizeY = 5;
	protected $mElementCoordinates = array();
	protected $mElementSpan = array();
	protected $rowNames = array();
	protected $colNames = array();
	protected $colNamesSet = false;
	protected $colWidth = array();
	
	
	public function setGridSize($x,$y)
	{
		$this->mGridSizeX = $x;
		$this->mGridSizeY = $y+1;
		$this->rowNames = array();
		for($x = 0; $x < $this->mGridSizeX; $x++)
			$this->rowNames[$x]='';
		$this->colNames = array();
		for($y = 0; $y < $this->mGridSizeX; $y++)
			$this->colNames[$y]='';
		$this->colNamesSet=false;
	}
	
	public function setColWidth($width){
			$this->colWidth=$width;
	}
	
	public function putElement($id,$x,$y,$element)
	{
		$y=$y+1;
		if($x < 0 || $y < 0 || $x > $this->mGridSizeX || $y > $this->mGridSizeY)
			throw new FormException('Coordinates out of bounds: '.$x.' '.$y);
		
		$this->addElement($id,$element);
		$this->mElementCoordinates[$x][$y] = $id;	
		$this->mElementSpan[$x][$y] = 1;
	}
	
	public function putElementMulticol($id,$x,$firstcol,$numcol,$element)
	{
		$firstcol=$firstcol+1;
		if($x < 0 || $firstcol < 0 || $x > $this->mGridSizeX || ($firstcol + $numcol -1) > $this->mGridSizeY)
			throw new FormException('Coordinates out of bounds: '.$x.' '.($firstcol + $numcol -1));
		
		$this->addElement($id,$element);
		$this->mElementCoordinates[$x][$firstcol] = $id;
		$this->mElementSpan[$x][$firstcol] = $numcol;
	}
	
	public function setRowName($x, $name){
		if($x < 0 || $x > $this->mGridSizeX)
			throw new FormException('x-Coordinates out of bounds: '.$x);
		$this->rowNames[$x]=$name;
	}
	
	public function setColName($y, $name){
		$this->colNamesSet=true;
		$y=$y+1;		
		if($y < 0 || $y > $this->mGridSizeY)
			throw new FormException('y-Coordinates out of bounds: '.$y);
		$this->colNames[$y]=$name;
	}
	
	public function setLayout($layout)
	{
		//LAYOUT CAN BE TABLE
		//LAYOUT CAN BE FLAT	
	}
	
	public function printFormElements()
	{
		$xhtml = '';
		$xhtml .= '<table class="table_form">';
		
		//col names, if any
		if($this->colNamesSet){
			$xhtml .= '<tr><td></td>';
			for($y = 0; $y < $this->mGridSizeY; $y++)
			{
				$xhtml .= '<td class="toplabel"><p>'.$this->colNames[$y].'</p></td>';
			}
			$xhtml .= '</tr>';
		}
		
		for($x = 0; $x < $this->mGridSizeX; $x++)
		{
			$y=0;
			$has_element=false;
			$tmp_html='';
			//row label
			if($this->rowNames[$x]!=''){ 
				$tmp_html .= '<tr><td class="label"';
				if(isset($this->colWidth[$y])) $tmp_html.=' width="'.$this->colWidth[$y].'"';
				$tmp_html.='><p>'.$this->rowNames[$x].'</p></td>';
				$y=1;
				$has_element=true;
			} else $tmp_html.='<tr>';
			
			
			for(; $y < $this->mGridSizeY; $y++){
				
				if(isset($this->mElementSpan[$x][$y])) $has_element=true;
				
				if($this->mElementSpan[$x][$y] > 1) $tmp_html .= '<td colspan="'.$this->mElementSpan[$x][$y].'"';
				else $tmp_html .= '<td';
				if(isset($this->colWidth[$y])) $tmp_html.=' width="'.$this->colWidth[$y].'"';
				$tmp_html.='>';
					
				if(!empty($this->mElementCoordinates[$x][$y]))
				{
					$el = $this->elements[$this->mElementCoordinates[$x][$y]];
					$tmp_html .= self::printErrors($el->getErrors());
					$tmp_html .= $el->getHtml();
				}
					
				$tmp_html .= '</td>';	
				if($this->mElementSpan[$x][$y] > 1) $y+=$this->mElementSpan[$x][$y] -1;				
			}
			$tmp_html .= '</tr>'."\n";
			if($has_element) $xhtml.=$tmp_html;
			
		}
		$xhtml .= '</table>';
		
		return $xhtml;
	}
	
	//TODO: implement!!!
		protected static function printErrors($errs)
	{
		if (empty($errs))
			return '';
			
		$str = '';
		foreach($errs as $error)
		{
			$str .= '<b><span class="form_error">'.$error.'</span></b><br />';
		}
		return $str;
	}
}	



class DataForm extends TabularForm
{
	
	private $type_mapping = array(
		'int'=>'NumericInput',
		'mediumint'=>'NumericInput',
		'tinyint'=>'NumericInput',
		'bigint'=>'NumericInput',
		'varchar'=>'TextInput',
		'text'=>'Textarea');
		

	//description can contain default values. array(varname=> array(properties [+current_value]), etc. );
	public function loadFromDescription($desc)
	{
		//print_r($desc);
		foreach($desc as $name=>$info)
		{
			if(!empty($info['references']))
				$this->addReferenced($name,$info);
			else
			{
				switch($info['DATA_TYPE'])
				{
					case 'int':
					case 'bigint':
					case 'mediumint':
					case 'tinyint':
					case 'smallint':
						$this->addNumeric($name,$info);
						break;
					//case 'float':
					//case 'double':
					//case 'decimal':
					//	$this->addNumeric($name,$info);
						break;
					case 'text':
					case 'longtext':
					case 'varchar':
						$this->addTextstuff($name,$info);
						break;
					case 'date':
						$this->addDate($name,$info);
						break;
					default:
						error('not implemented: '.$info['DATA_TYPE']);
						$this->addDisabled($name,$info);
				}
				}
		}
	}
	
	//has to be an DatabaseObject!!!
	public function loadFromObject($obj)
	{
		//throw new Exception('i');
		//var_dump($obj);
		//print_r($obj);
		
		$vars_info = $this->loadTableDescription($obj->getTableName());
		//print_r($vars_info);
		$vars = array();
		foreach($vars_info as $inf)
		{
			$varname = $inf['COLUMN_NAME'];
			$vars[$varname] = array();
			$v =& $vars[$varname];
			
			$v['current_value'] = $obj->$varname;
			
			//TODO: use export instead??
			$v['DATA_TYPE'] = $inf['DATA_TYPE'];
			$v['CHARACTER_MAXIMUM_LENGTH'] = $inf['CHARACTER_MAXIMUM_LENGTH'];
			$v['NUMERIC_PRECISION'] = $inf['NUMERIC_PRECISION'];
			$v['COLUMN_TYPE'] = $inf['COLUMN_TYPE'];
			$v['IS_NULLABLE'] = $inf['IS_NULLABLE'];
			$v['COLUMN_KEY'] = $inf['COLUMN_KEY'];
			$v['COLUMN_COMMENT'] = $inf['COLUMN_COMMENT'];
			
			$ref_tbl = $inf['REFERENCED_TABLE_NAME'];
			$ref_col = $inf['REFERENCED_COLUMN_NAME'];
			if(!empty($ref_tbl) && !empty($ref_col))
			{
				//TODO: this is a workaround.
				$name = ($ref_tbl == 'style') ? 'class' : 'name';
				$name = ($ref_tbl == 'user') ? 'username' : $name;
				$name = ($ref_tbl == 'IBO_student_exam') ? 'id' : $name;
				$v['references'] = SqlQuery::getInstance()->listQuery($ref_tbl,$ref_col,$name,'DISTINCT',1);
			}
			else
				$v['references'] = NULL;
		}
		
		//and make the call to construct elements:
		$this->loadFromDescription($vars);
		
	}

	public function loadTableDescription($table,$db = NULL)
	{
		if( empty($db) )
			$db = $GLOBALS['config']['db_name'];
		
		$sql = SqlQuery::getInstance();
		
		$query =  "SELECT 	cols.COLUMN_NAME, 
							cols.DATA_TYPE,
							cols.CHARACTER_MAXIMUM_LENGTH, 
							cols.NUMERIC_PRECISION,
							cols.IS_NULLABLE,
							cols.COLUMN_TYPE,
							cols.COLUMN_KEY,
							cols.COLUMN_COMMENT,
							k.REFERENCED_TABLE_NAME, 
							k.REFERENCED_COLUMN_NAME
					FROM `information_schema`.`COLUMNS` cols
					LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` k ON 
							cols.TABLE_SCHEMA = k.TABLE_SCHEMA 
						AND cols.TABLE_NAME = k.TABLE_NAME 
						AND cols.COLUMN_NAME = k.COLUMN_NAME
					WHERE cols.TABLE_SCHEMA='$db' AND cols.TABLE_NAME='$table'
					ORDER BY cols.ORDINAL_POSITION";
					
		return $sql->simpleQuery($query);
	}
	
	protected function addReferenced($name,$info)
	{
		//notice('reference! '.$name.' opt: '.implode(',',$info['references']));
		
		$value = isset($info['current_value']) ? $info['current_value'] : NULL;
		$this->addElement($name, new Select($name,$name,$info['references'],$value));	
	}
	
	protected function addTextstuff($name,$info)
	{
		//notice('type is: '.$info['type']);	
		$value = isset($info['current_value']) ? $info['current_value'] : '';
		
		$this->addElement($name, new TextInput($name,$name,htmlentities($value,ENT_QUOTES,"UTF-8")));
	}
	
	
	protected function addDisabled($name,$info)
	{
		//notice('type is: '.$info['type']);	
		$value = isset($info['current_value']) ? $info['current_value'] : '';
		
		$this->addElement($name, new TextInput($name,$name,htmlentities($value,ENT_QUOTES,"UTF-8"),array('disabled')));
	}
	
	
	protected function addNumeric($name,$info)
	{
		$unsigned = (strpos($info['COLUMN_TYPE'],'unsigned') !== FALSE) ? TRUE : FALSE;
		
		$powers = array('tinyint'=>'8',
						'smallint'=>'16',
						'mediumint'=>'24',
						'int'=>'32',
						'bigint'=>'64');
		
		//TODO: this is only tested with unsigned!!!				
		$min = $unsigned ? 0 : (-1) * pow(2,$powers[$info['DATA_TYPE']]-1);
		$max = $unsigned ? pow(2,$powers[$info['DATA_TYPE']])-1: pow(2,$powers[$info['DATA_TYPE']]-1)-1;
			
		//notice('added '.$name. ' as '.$info['data_type'].' with min='.$min.' max='.$max);
		
		$value = isset($info['current_value']) ? $info['current_value'] : 0;
		
		$extras = ('id' == $name) ? array('disabled','maxlen'=>20) : array('maxlen'=>20);
		
		$element = new TextInput($name,$name,$value,$extras);
		$element->addRestriction(new InRangeRestriction($min,$max));
		$this->addElement($name,$element);
	}
	
	protected function addDate($name,$info)
	{
		//notice('type is: '.$info['type']);	
		$value = isset($info['current_value']) ? $info['current_value'] : '';
		
		$this->addElement($name, new DateInput($name,$name,$value));	
	}
	
}	

?>
