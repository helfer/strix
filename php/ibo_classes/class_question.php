<?php
class question_conventional_MC{
   var $id=false;
   var $position=false;
   var $name=false;
   var $points=false;
   var $type_specific_info=false;
   var $solution=array();
   var $exam_id=false;
   var $type_id=1;
   var $skipped=false;
   var $prop_right;
   var $prop_mostfreq_wrong;
   var $mostfreq_wrong;

   //base constructor, if $connection then read from DB
   function question_conventional_MC($mysql_object, $connection=false){
   	/*
      if($connection){
         $mysql_object="select * from question where id=".$mysql_object;
         $mysql_object=mysql_query($mysql_object, $connection);
         $mysql_object=mysql_fetch_object($mysql_object);
      }*/
      
      if(isset($mysql_object['id'])) $this->id=$mysql_object['id'];
      $this->update_from_object($mysql_object);
      if(isset($mysql_object['skipped'])) $this->skipped=$mysql_object['skipped'];
      if(isset($mysql_object['prop_right'])) $this->prop_right=$mysql_object['prop_right'];
      if(isset($mysql_object['prop_mostfreq_wrong'])) $this->prop_mostfreq_wrong=$mysql_object['prop_mostfreq_wrong'];
      if(isset($mysql_object['mostfreq_wrong'])) $this->mostfreq_wrong=$mysql_object['mostfreq_wrong'];      
   }

   function update_from_object($mysql_object){
      $this->exam_id=$mysql_object['exam_id'];
      $this->name=$mysql_object['name'];
      $this->position=$mysql_object['position'];
      $this->points=$mysql_object['points'];
      $this->type_specific_info=$mysql_object['type_specific_info'];
      $this->solution=$this->parse_solution_from_DB($mysql_object['solution']);
   }

   function compare_vars($mysql_object){
	   //debug_print_backtrace();
      if($this->points!=$mysql_object['points']
         || $this->type_specific_info!=$mysql_object['type_specific_info']
         || $this->get_solution_for_DB()!=$mysql_object['solution']
         || $this->position!=$mysql_object['position']
         || $this->type_id!=$mysql_object['type_id'])
         return false;
      else return true;
   }

   function get_solutions_array($sep=" / "){
      return $this->solution;
   }
   
   function get_solution_Language($lan, $sep=' / ')
   {         	
	if(!isset($this->solution[$lan]))
		return '';
	return $this->solution[$lan];
   }



	function delete_in_DB()
	{
		if($this->id)
		{
			$del= SqlQuery::getInstance()->deleteQuery('IBO_question',array('id'=>$this->id));
		
			if($del) 
		 		return false; 
			else 
				return "Error in 'delete_in_DB' from '".get_class($this)."': ".mysql_error();
		} 
		else 
			return "ID not defined!";
	}

   function get_solution_for_DB(){
      $a=array();
      foreach($this->solution as $k=>$v) $a[]=$k.'@'.$v;
      return implode('|', $a);
   }

   function parse_solution_from_DB($s){
      $c=array();
      $a=explode('|', $s);
      foreach($a as $k=>$v){
          $b=explode('@', $v);
          $c[$b[0]]=trim($b[1]);
      }
      return $c;
   }
   //update database
   function update_DB(){
	  $sql = SqlQuery::getInstance();
      if($this->id)
	  {
		$ok = $sql->updateQuery('IBO_question',
		 	array('position'=>$this->position,
				'name'=>$this->name,
				'points'=>$this->points,
				'exam_id'=>$this->exam_id,
				'type_specific_info'=>$this->type_specific_info,
				'type_id'=>$this->type_id,
				'solution'=>$this->get_solution_for_DB()
				),
			array('id'=>$this->id)
		);
		
		if(mysql_error()) 
		 	return "Error in update in 'update_DB' from '".get_class($this)."': ".mysql_error(); 
		else 
		{
			if($ok === 0)
				return 0;
			else
				return false;
		}
      } 
	  else 
	  {
         $ok = $sql->insertQuery('IBO_question',
		 		array('position'=>$this->position,
				'name'=>$this->name,
				'points'=>$this->points,
				'exam_id'=>$this->exam_id,
				'type_specific_info'=>$this->type_specific_info,
				'type_id'=>$this->type_id,
				'solution'=>$this->get_solution_for_DB()
				)
			);

         if(mysql_error()) 
		 	return "Error in insert in 'update_DB' from '".get_class($this)."': ".mysql_error(); 
		 else 
		 	return false;
      }
   }

   function parse_student_answer(&$template, &$parsed){
      if(strlen($template)==0 || $template{0}=='#') return "Error! Question '".$this->name."' has no answer for this student!";
      if($pos=strpos($template, '#')){
         $s=strtoupper(substr($template, 0, $pos));
         $template=substr($template, min($pos+1, strlen($template)-1));
      } else{
         $s=strtoupper($template);
         $template="";
      }
      //replace
      $r=array('MULT'=>'*', 'BLANK'=>'_', '*'=>'*', '_'=>'_');
      for($i=0; $i<$this->type_specific_info; ++$i) $r[chr(65+$i)]=chr(65+$i);
      if(!isset($r[$s])) return "Error! Question '".$this->name."' has no legal answer for this student!";
      $parsed.=$r[$s].'#';
      return false;
   }

   //check if given answer is correct and return the number of points
	function check($answer, $lan_id){
      if(!isset($this->solution[$lan_id])){
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }     
	  //MAJOR BUGFIX!!!
      if (!$this->skipped && in_array($answer,explode('#',$this->solution[$lan_id])) )
	  {

      	 //echo $answer .' == '. $this->solution[$lan_id].' lan='.$lan_id. ' p='.$this->points.'<br />';
      	 return $this->points; 
      }
	  else 
	  {
      	//if($lan_id == 3) echo 'faux: '.$answer.'<br/>';
		//echo $answer .' != '. $this->solution[$lan_id]. ' lan='.$lan_id.'<br />';
      	return 0;

      }
   }

   function show_answer($lan_id){
      return($this->solution[$lan_id]);
   }


} //end class question_conventional_MC

//------------------------------------------------------------------------------
class question_MC_several_solutions extends question_conventional_MC{
   var $type_id=2;

   function get_solutions_array($sep=" / "){
      $r=array();
      foreach($this->solution as $k=>$v) $r[$k]=implode($sep, $v);
      return $r;
   }
   
   function get_solution_Language($lan, $sep=' / '){         	  
   	   return implode($sep, $this->solution[$lan]);      
   }

   function get_solution_for_DB(){
      $a=array();
      foreach($this->solution as $k=>$v) $a[]=$k.'@'.implode('#', $v);
      return implode('|', $a);
   }

   function parse_solution_from_DB($s){
      $c=array();
      $a=explode('|', $s);
      foreach($a as $k=>$v){
          $b=explode('@', $v);
          $c[$b[0]]=explode('#', trim($b[1]));
      }
      return $c;
   }

   function check($answer, $lan_id){
      if(!isset($this->solution[$lan_id])){
         $_SESSION['error_message'].="Solution for language ".$lan_id." not defined in question ".$this->name."<br>";
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }
      if($this->skipped){
      	//if($lan_id == 3) echo 'skipped: '.$answer.'<br/>';
      	 return 0;
      	 
      }
      foreach($this->solution[$lan_id] as $v){ if($answer==$v){
      	 //if($lan_id == 3) echo 'juste: '.$answer.'<br/>';
      	 return $this->points;
      }
  		}
      //if($lan_id == 3) echo 'faux: '.$answer.'<br/>';
      return 0;
      
   }

   function show_answer($lan_id){
      return(implode('#',$this->solution[$lan_id]));
   }


} //end class MC_several_solutions

//------------------------------------------------------------------------------
class question_matching_symmetric extends question_conventional_MC{
   var $type_id=3;

   function parse_student_answer(&$template, &$parsed){
      $r=array('MULT'=>'*', 'BLANK'=>'_', '*'=>'*', '_'=>'_');
      for($i=0; $i<$this->type_specific_info; ++$i) $r[chr(65+$i)]=chr(65+$i);
      for($q=0; $q<$this->type_specific_info; ++$q){
         if(strlen($template)==0 || $template{0}=='#') return "Error! Question '".$this->name."' has no answer for this student!";
         if($pos=strpos($template, '#')){
            $s=strtoupper(substr($template, 0, $pos));
            $template=substr($template, min($pos+1, strlen($template)-1));
         } else{
            $s=strtoupper($template);
            $template="";
         }
         //replace
         if(!isset($r[$s])) return "Error! Question '".$this->name."' has no legal answer for this student!";
         $parsed.=$r[$s];
      }
      $parsed.='#';
      return false;
   }

   //check if given answer is correct and return the number of points
   function check($answer, $lan_id){
      if($this->skipped) return 0;
      if(!isset($this->solution[$lan_id])){
         $_SESSION['error_message'].="Solution for language ".$lan_id." not defined in question ".$this->name."<br>";
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }
      if(strlen($answer)!=strlen($this->solution[$lan_id])){
         $_SESSION['error_message'].="Solution for language ".$lan_id." and answer have unequal lengthin question ".$this->name."<br>";
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }
      $t=0;
      for($q=0; $q<$this->type_specific_info; ++$q) if($answer{$q}==$this->solution[$lan_id]{$q}) ++$t;
      if($t==0) return 0;
      else return 0.2 + 0.8*($t-1)/($this->type_specific_info-1);
   }


} //end class question_matching_symmetric

//------------------------------------------------------------------------------
class question_matching_symmetric_several_solutions extends question_matching_symmetric{
   var $type_id=4;

    function get_solutions_array($sep=" / "){
       $r=array();
       foreach($this->solution as $k=>$v) $r[$k]=implode($sep, $v);
       return $r;
    }

    function get_solution_for_DB(){
      $a=array();
      foreach($this->solution as $k=>$v) $a[]=$k.'@'.implode('#', $v);
      return implode('|', $a);
   }

   function parse_solution_from_DB($s){
      $c=array();
      $a=explode('|', $s);
      foreach($a as $k=>$v){
          $b=explode('@', $v);
          $c[$b[0]]=explode('#', trim($b[1]));
      }
      return $c;
   }

   //check if given answer is correct and return the number of points
   function check($answer, $lan_id){
      if($this->skipped) return 0;
      if(!isset($this->solution[$lan_id])){
         $_SESSION['error_message'].="Solution for language ".$lan_id." not defined in question ".$this->name."<br>";
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }
      foreach($this->solution[$lan_id] as $v){
         if(strlen($answer)!=strlen($v)){
            $_SESSION['error_message'].="Solution for language ".$lan_id." and answer have unequal lengthin question ".$this->name."<br>";
            echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
            return 0;
         }
      }
      $maximum=0;
      foreach($this->solution[$lan_id] as $v){
         $t=0;
         for($q=0; $q<$this->type_specific_info; ++$q) if($answer{$q}==$v{$q}) ++$t;
         if($t>$maximum) $maximum=$t;
      }
      if($maximum==0) return 0;
      else return 0.2 + 0.8*($maximum-1)/($this->type_specific_info-1);
   }
   function show_answer($lan_id){
      return(implode('#',$this->solution[$lan_id]));
   }
} //end class question_matching_symmetric

//------------------------------------------------------------------------------
class question_different_orders_MC extends question_matching_symmetric{
   var $type_id=5;

    function get_solutions_array($sep=" / "){
       $r=array();
       foreach($this->solution as $k=>$v) $r[$k]=implode($sep, $v);
       return $r;
    }

    function get_solution_for_DB(){
      $a=array();
      foreach($this->solution as $k=>$v) $a[]=$k.'@'.implode('#', $v);
      return implode('|', $a);
   }

   function parse_solution_from_DB($s){
      $c=array();
      $a=explode('|', $s);
      foreach($a as $k=>$v){
          $b=explode('@', $v);
          $c[$b[0]]=explode('#', $b[1]);
      }
      return $c;
   }

   //check if given answer is correct and return the number of points
   function check($answer, $lan_id){
      if($this->skipped) return 0;
      if(!isset($this->solution[$lan_id])){
         $_SESSION['error_message'].="Solution for language ".$lan_id." not defined in question ".$this->name."<br>";
         echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
         return 0;
      }
      foreach($this->solution[$lan_id] as $v){
         if(strlen($answer)!=strlen($v)){
            $_SESSION['error_message'].="Solution for language ".$lan_id." and answer have unequal lengthin question ".$this->name."<br>";
            echo "<p class='error'>Solution for language ".$lan_id." not defined in question ".$this->name."</p>";
            return 0;
         }
      }
      $maximum=0;
      foreach($this->solution[$lan_id] as $v){
         $t=0;
         for($q=0; $q<$this->type_specific_info; ++$q) if($answer{$q}==$v{$q}) ++$t;
         if($t>$maximum) $maximum=$t;
      }
      return $maximum;
   }
   function show_answer($lan_id){
      return(implode('#',$this->solution[$lan_id]));
   }
} //end class question_matching_symmetric














