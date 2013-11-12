<?php
/*
 *      form_restrictions.php
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
 *      
 */

abstract Class Restriction
{
	protected $mErrorTypes = array();
	protected $mErrorMessages = array();
	/*array(
		'error'=>array(
				'1'=>'*deutsch',
				'2'=>'*français',
				'3'=>'*etc...'
				),
		'error2'=>array(
				'1'=>'deutsch2',
				'2'=>'etc...'
				)
	);*/
	protected $mErrorMessage = '';
	//protected $mValid = TRUE;
	
	//did not declare this here because some can be static and some not.
	//public abstract function validate($values);
	
	public function getErrorMessage()
	{
		return $this->mErrorMessage;
	}
	
	protected function setError($type)
	{
		//last chance to initialize if messages are empty
		if(empty($this->mErrorMessages))
			$this->mErrorMessages = Language::getGlobalTag($this);
		//print_r($this->mErrorMessages);
			
		$this->mErrorMessage = Language::extractPrefLan($this->mErrorMessages[$type]);
	}
	
	
	public function setErrorMessages($messages)
	{
		if(empty($this->mErrorMessages))
			$this->mErrorMessages = Language::getGlobalTag($this);
			
		foreach($messages as $k=>$languages)
		{
			//does not work with numeric keys
			$this->mErrorMessages[$k] = array_merge_numeric($this->mErrorMessages[$k],$languages);
		}
	}

}



//-------------------

class NotEmptyRestriction extends Restriction{
	
	public function validate($val)
	{
		if (!empty($val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}

}

class NotFalseRestriction extends Restriction{
	
	public function validate($val)
	{
		if ($val)
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}

}

class IsNumericRestriction extends Restriction{
	
	public function validate($val)
	{
		if (is_numeric($val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;	
	}

}

class InListRestriction extends Restriction{

	private $mList;

	public function __construct($list)
	{
		$this->mList = $list;
	}
	
	public function validate($val)
	{
		if(!is_array($val))
			$val = array($val);
		$d = array_diff($val , $this->mList);
		if (!empty( $d ) )
			$this->setError('error');
		else
			return TRUE;
			
		return FALSE;	
	}

}

/*
//verifies that $val is in table $table in column $column
class inTableRestriction extends Restriction{
	
	private $mQuery;
	
	public function __construct($table,$colname){
		$this->mQuery = "SELECT * FROM $table WHERE `$colname`='"
	}
	
	public function validate($val){
		if (SqlQuery::getInstance->existsQuery($this->mQuery.$val."'"))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE; 
	}
}*/


class InRangeRestriction extends Restriction{
	private $mLower = 1;
	private $mUpper = 0;
	
	public function __construct($lower, $upper){
		$this->mLower = $lower;
		$this->mUpper = $upper;
	}
	
	//checks if val is between $lower (inclusive) and $upper (inclusive)
	public function validate($val){
		
		if ($val <= $this->mUpper && $val >= $this->mLower)
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;	
	}
	
}


class FunctionalRestriction extends Restriction{
	private $mLower = 1;
	private $mUpper = 0;
	private $mCallback = NULL;
	
	public function __construct($lower, $upper,$callback){
		$this->mLower = $lower;
		$this->mUpper = $upper;
		$this->mCallback = $callback;
	}
	
	public function validate($val){
		
		if (call_user_func($this->mCallback,$val) <= $this->mUpper && call_user_func($this->mCallback,$val) >= $this->mLower)
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}
	
}

class IsEmailRestriction extends Restriction{
	
	
	public function validate($val){
		if (self::checkEmail($val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;		
		
	}

	
	public static function checkEmail($email){
		
		// checks proper syntax
		if( !preg_match("/^.+@.+\..+$/", $email))
			return FALSE;

		//checks for MX record
		list($userName, $mailDomain) = split("@", $email);
		if (checkdnsrr($mailDomain, "MX")) 
			return TRUE;
		else
			return FALSE;	
			
	}
	
	
}

class IsEmptyOrEmailRestriction extends Restriction{	
	
	public function validate($val){
		if (self::checkEmail($val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;		
		
	}

	
	public static function checkEmail($email){
		if($email=='') return true;
		// checks proper syntax
		if( !preg_match("/^.+@.+\..+$/", $email))
			return FALSE;

		//checks for MX record
		list($userName, $mailDomain) = split("@", $email);
		if (checkdnsrr($mailDomain, "MX")) 
			return TRUE;
		else
			return FALSE;	
			
	}
	
	
}

class IsUserPassword extends Restriction{
	

	public function validate($val){
		//echo $_SESSION['user']->password;
		//echo ' -- '.$val;
		if (0 === strcmp($GLOBALS['user']->password,md5($val)))
		//if (0 === strcmp($_SESSION['user']->password,$val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}
	
}

class IsDate extends Restriction{
	
	private $mBegin = '';
	private $mEnd = false;
	
	public function __construct($begin = '0000-00-00',$end = false){
		$this->mBegin = $begin;
		$this->mEnd = $end;
	}
	

	public function validate($val){
		
		$matches = array();
		$n = preg_match('/(?<year>\d\d\d\d)-(?<month>\d\d)-(?<day>\d\d)/i', $val, $matches);
		
		if ( ($n == 1) && checkdate($matches['month'],$matches['day'],$matches['year']) ){
		
			
			if(strcmp($this->mBegin,$val) > 0)
			{
				$this->setError('too_early');
				return FALSE;	
			}
			if($this->mEnd && strcmp($val,$this->mEnd) > 0){
				$this->setError('too_late');	
				return FALSE;
			}
			
			if(strtotime($val) !== FALSE)
				return TRUE;
			else
				return FALSE;
		
		
		}else
			$this->setError('error');
			
		return FALSE;
	}
	
}


class IsTime extends Restriction{
	
	public function __construct(){}	

	public function validate($val){		
		$val=explode(':', $val);
		if(count($val)!=2){
			$this->setError('wrong_format');
			return false;
		}
		if(!is_numeric($val[0]) || !is_numeric($val[1])){
			$this->setError('wrong_format');
			return false;
		}
		
		if($val[0]<0 || $val[0]>23){
			$this->setError('unknown_time');
			return false;
		}
		
		if($val[1]<0 || $val[1]>59){
			$this->setError('unknown_time');
			return false;
		}
		
		return true;
	}
}

class IsEmptyOrTime extends Restriction{
	
	public function __construct(){}	

	public function validate($val){
		if($val=='') return true;
		$val=explode(':', $val);
		if(count($val)!=2){
			$this->setError('wrong_format');
			return false;
		}
		if(!is_numeric($val[0]) || !is_numeric($val[1])){
			$this->setError('wrong_format');
			return false;
		}
		
		if($val[0]<0 || $val[0]>23){
			$this->setError('unknown_time');
			return false;
		}
		
		if($val[1]<0 || $val[1]>59){
			$this->setError('unknown_time');
			return false;
		}
		
		return true;
	}
}


class ArrayIsDate extends Restriction{
	
	private $mBegin = '';
	private $mEnd = false;
	
	public function __construct($begin = '0000-00-00',$end = false){
		$this->mBegin = $begin;
		$this->mEnd = $end;
	}
	

	public function validate($val){		
		//Array with three values: day, month, year
		if ( checkdate($val['month'],$val['day'],$val['year']) ){		
			$datestr=$val['year'].'-'.$val['month'].'-'.$val['day'];
			if(strcmp($this->mBegin,$datestr) > 0)
			{
				$this->setError('too_early');
				return FALSE;	
			}
			if($this->mEnd && strcmp($datestr,$this->mEnd) > 0){
				$this->setError('too_late');	
				return FALSE;
			}
			
			if(strtotime($datestr) !== FALSE)
				return TRUE;
			else
				return FALSE;
		
		
		}else
			$this->setError('error');
			
		return FALSE;
	}
	
}

class IsDecentPassword extends Restriction{
	
	public function validate($val){
		if (self::decencyTest($val))
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
		
	}
	
	public static function decencyTest($data){
		//password must contain lowercase uppercase and numbers 
		//TODO: adapt this at some point!!
		
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
	
}


class SameAsRestriction extends Restriction{
	
	private $mOtherElement = NULL;
	
	public function __construct($otherElement){
		$this->mOtherElement = $otherElement;
	}
	
	public function validate($val){
		if ($val == $this->mOtherElement->getValue())
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}
	
}

class SameEmailAsRestriction extends Restriction{
	
	private $mOtherElement = NULL;
	
	public function __construct($otherElement){
		$this->mOtherElement = $otherElement;
	}
	
	public function validate($val){
		if ($val == $this->mOtherElement->getValue())
			return TRUE;
		else
			$this->setError('error');
			
		return FALSE;
	}
	
}


class StrlenRestriction extends Restriction
{
	
	private $mLower = 1;
	private $mUpper = 0;
	
	public function __construct($lower, $upper){
		$this->mLower = $lower;
		$this->mUpper = $upper;
	}
	
	public function validate($val){
		
		//notice('val string '.$val);
		
		if (strlen($val) <= $this->mUpper && strlen($val) >= $this->mLower)
			return TRUE;
		else
		{
			if (strlen($val) > $this->mUpper)
				$this->setError('too_long');
			else
				$this->setError('too_short');
		}
		
		return FALSE;
	}
}

class IsEmptyOrStrlenRestriction extends Restriction
{	
	private $mLower = 1;
	private $mUpper = 0;
	
	public function __construct($lower, $upper){
		$this->mLower = $lower;
		$this->mUpper = $upper;
	}
	
	public function validate($val){		
		if($val=='') return TRUE;
		if (strlen($val) <= $this->mUpper && strlen($val) >= $this->mLower)
			return TRUE;
		else {
			if (strlen($val) > $this->mUpper)
				$this->setError('too_long');
			else
				$this->setError('too_short');
		}		
		return FALSE;
	}
}

class StrlenRestrictionIfEmailNotEmpty extends Restriction
{	
	private $mLower = 1;
	private $mUpper = 0;
	private $mOtherElement = NULL;
	
	public function __construct($lower, $upper, $otherElement){
		$this->mLower = $lower;
		$this->mUpper = $upper;
		$this->mOtherElement = $otherElement;
	}
	
	public function validate($val){
		if (trim($this->mOtherElement->getValue())==''){
			if(strlen(trim($val))>0){
				 $this->setError('nameNoEmail');
			} else return TRUE;
		} else {
			if(strlen($val) <= $this->mUpper && strlen($val) >= $this->mLower)
				return TRUE;
			else {
				if (strlen($val) > $this->mUpper)
					$this->setError('too_long');
				else
					$this->setError('too_short');
				}
		}		
		return FALSE;
	}
}

class StrlenRestrictionIfAdressNotEmpty extends Restriction
{	
	private $mLower = 1;
	private $mUpper = 0;
	private $mOtherElement = NULL;
	
	public function __construct($lower, $upper, $otherElement){
		$this->mLower = $lower;
		$this->mUpper = $upper;
		$this->mOtherElement = $otherElement;
	}
	
	public function validate($val){
		if (trim($this->mOtherElement->getValue())==''){
			if(strlen(trim($val))>0){
				 $this->setError('nameNoAddress');
			} else return TRUE;
		} else {
			if(strlen($val) <= $this->mUpper && strlen($val) >= $this->mLower)
				return TRUE;
			else {
				if (strlen($val) > $this->mUpper)
					$this->setError('too_long');
				else
					$this->setError('too_short');
				}
		}		
		return FALSE;
	}
}

class isImageRestriction extends Restriction{
	protected $imagename='';
	protected $maxsize=0;
	protected $minwidth=0;
	protected $minheigth=0;
	
	
	public function __construct($imagename, $maxsize, $minwidth, $minheigth){
		$this->imagename = $imagename;
		$this->maxsize=$maxsize;
		$this->minwidth=$minwidth;
		$this->minheigth=$minheigth;
	}
	
	public function validate($val){
		$file=$GLOBALS['HTTP_POST_FILES'][$this->imagename];		
		if(!isset($file['name']) || strlen($file['name'])==0){
			$this->setError('notimage');	
			 return false;
		 }

		if($file['size']>$this->maxsize){
			$this->setError('toobig');			
			return FALSE;
		}
		//check file extension
		$ext = substr(strrchr($file['name'], "."), 1);
		if(!in_array($ext, array('jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG'))){
			$this->setError('notimage');						
			return FALSE;
		}			
		//check dimension
		$src = imagecreatefromjpeg($file['tmp_name']);
		list($width,$height)=getimagesize($file['tmp_name']);
		if($width<$this->minwidth || $height<$this->minheigth){
			$this->setError('toosmall');						
			return FALSE;
		}
		
		//$_FILES error
		switch($file['error']){
			case 0: return true; break;
			case 1:
			case 2: $this->setError('toobig');	return FALSE; break;
			case 3: 
			case 4:
			case 5:
			case 6:
			case 7: 
			case 8: $this->setError('uploaderror');	return FALSE; break;
		}
		return TRUE;
	}
		
}

class isEmptyOrImageRestriction extends Restriction{
	protected $imagename='';
	protected $maxsize=0;
	protected $minwidth=0;
	protected $minheigth=0;
	
	
	public function __construct($imagename, $maxsize, $minwidth, $minheigth){
		$this->imagename = $imagename;
		$this->maxsize=$maxsize;
		$this->minwidth=$minwidth;
		$this->minheigth=$minheigth;
	}
	
	public function validate($val){
		$file=$GLOBALS['HTTP_POST_FILES'][$this->imagename];
		//die(print_r($file, true).'<br/>'.$this->maxsize);
		if(!isset($file['name']) || strlen($file['name'])==0) return TRUE;
		if($file['size']>$this->maxsize){
			$this->setError('toobig');			
			return FALSE;
		}
		//check file extension
		$ext = substr(strrchr($file['name'], "."), 1);
		if(!in_array($ext, array('jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG'))){
			$this->setError('notimage');						
			return FALSE;
		}					
		//check dimension
		$src = imagecreatefromjpeg($file['tmp_name']);
		list($width,$height)=getimagesize($file['tmp_name']);
		if($width<$this->minwidth || $height<$this->minheigth){
			$this->setError('toosmall');						
			return FALSE;
		}
		
		//$_FILES error
		switch($file['error']){
			case 0: return true; break;
			case 1:
			case 2: $this->setError('toobig');	return FALSE; break;
			case 3: 
			case 4:
			case 5:
			case 6:
			case 7: 
			case 8: $this->setError('uploaderror');	return FALSE; break;
		}
		return TRUE;
	}
		
}

class isEmptyOrPDFOrImageRestriction extends Restriction{
	protected $filename='';
	protected $maxsize=0;
	
	public function __construct($filename, $maxsize){
		$this->filename = $filename;
		$this->maxsize=$maxsize;		
	}
	
	public function validate($val){
		$file=$GLOBALS['HTTP_POST_FILES'][$this->filename];
		//die(print_r($file, true).'<br/>'.$this->maxsize);
		if(!isset($file['name']) || strlen($file['name'])==0) return TRUE;
		if($file['size']>$this->maxsize){
			$this->setError('toobig');			
			return FALSE;
		}
		//check file extension
		$ext = substr(strrchr($file['name'], "."), 1);
		if(!in_array($ext, array('jpg', 'jpeg', 'JPG', 'JPEG', 'pdf', 'PDF'))){
			$this->setError('notimageorpdf');						
			return FALSE;
		}						
		
		//$_FILES error
		switch($file['error']){
			case 0: return true; break;
			case 1:
			case 2: $this->setError('toobig');	return FALSE; break;
			case 3: 
			case 4:
			case 5:
			case 6:
			case 7: 
			case 8: $this->setError('uploaderror');	return FALSE; break;
		}
		return TRUE;
	}
		
}

?>
