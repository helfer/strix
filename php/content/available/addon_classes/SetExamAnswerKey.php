<?php

include_once('ibo_classes/class_question.php');

class SetExamAnswerKey extends Content{


	protected $selected_exam_id = NULL;

	function display()
	{
		$html = $this->process_msg;
		
		
		
		
		
		if(!isset($this->selected_exam_id))
		{
			//select exam:
			$form_select = $this->getForm('select_exam_form');
			$html .= $form_select->getHtml($this->id);
		}
		else
		{ 
			$html .= '<a href="">select different exam</a>';
			$form_answerkey = $this->getForm('edit_answerkey_form',$this->selected_exam_id);
			$html .= $form_answerkey->getHtml($this->id);
		}									
	
		return $html;
	}
	
	
	
	public function select_exam_form($vector)
	{
		//SELECT EXAM FORM ------------------
		
		$form = new TabularForm(__METHOD__);
		$form->setVector($vector);
		$form->setProportions('10em','28em');

		
		switch($_SESSION['language_abb']){
			case 'de': $html ='Bitte w&auml;hlen'; break;
			case 'fr': $html ='Choisir s.v.p'; break;
			case 'it': $html ='??'; break;
			case 'en': $html ='??'; break;
		}		
		$form->addElement('html1',new HtmlElement($html));

		$query="SELECT id,de FROM IBO_exam WHERE closed < 2 ORDER BY date DESC";
		
		$q_args = array('query'  => $query,
						'keys'  => array('id'),
						'values'=> array('de')
						);
		$arg_ary = array('label'=>'exam:','name'=>'exam_id','query_args'=>$q_args);
		$form->addElement('exam_id',new DataSelect($arg_ary));
		
		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
		// END OF SELECT EXAM FORM -------------------------	
	}
	
	protected function process_select_exam_form()
	{
		$form = $this->getForm('select_exam_form');
		if(!$form->validate())
			return FALSE;
			
		$eid = $form->getElementValue('exam_id');
		
		$this->selected_exam_id = $eid;
		
		return;	
	}
	
	public function edit_answerkey_form($vector)
	{
		
		$form = new SimpleForm(__METHOD__);
		$form->setVector($vector);
		 

   		switch($_SESSION['language_abb']){
			case 'de': $html='ändern'; break;
			case 'fr': $html='changer'; break;
			case 'it': $html=''; break;
			case 'en': $html='change'; break;
		}	
		//$form->addElement('html1',new HtmlElement($html));
		
 		$exam_id = $vector;
		//var_dump($vector);
		//var_dump($this->selected_exam_id);
 		
   		//create text for texarea     	
		$sql = SqlQuery::getInstance();	
		$q="
		SELECT q.*, qt.name AS type_name 
		FROM IBO_question q, IBO_question_type qt 
		WHERE 
			exam_id='$exam_id' 
			AND 
			qt.id=q.type_id 
		ORDER BY position ASC";
		
		$result=$sql->simpleQuery($q);		
		$questions=array();
		
		foreach($result as $s)
		{
			//print_r($s);
			$n='question_'.$s['type_name'];
			$questions[]=new $n($s);
		}
		$s='';
		foreach($questions as $v)
		{
			$s.=$v->name.";".$v->points.";".$v->type_id.";".$v->type_specific_info.";".$v->get_solution_for_DB()."\n";
		}
		$text=trim($s);   

		$form->addElement('answerkey',new Textarea('','answerkey',$text,20,45));
		
		$form->addElement('submit',new Submit('submit','Process'));

   		return $form;	
	}
	
	
	
	
	
	
	
	
	
	public function process_edit_answerkey_form()
	{
		$form = $this->getForm('edit_answerkey_form');
		$exam_id = $form->getVector();
		
		$this->selected_exam_id = $exam_id; //to return to the same exam again...
		
		if(!$form->validate())
			return FALSE;	

		$textarea_parsing_error = FALSE;
		
		$answerkey_text = $form->getElementValue('answerkey');
			
		//save changes
		//--------------------------
		//create question-array 
		$sql = SqlQuery::getInstance();
		
		
   		$qry="
		SELECT q.*, qt.name as type_name 
		FROM IBO_question q, IBO_question_type qt 
		WHERE 
			exam_id='$exam_id' 
			AND 
			qt.id=q.type_id 
		ORDER BY position ASC";
   		$res=$sql->simpleQuery($qry);
   		$questions=array();
   		$question_name_to_id=array();
   		$k=0;
   		foreach($res as $s)
		{
      		$n='question_'.$s['type_name'];
      		$questions[$k] = new $n($s);
      		$question_name_to_id[$questions[$k]->name]=$k;
      		++$k;
   		}

   				
		//read question types from DB
		$question_types=array();
		$qry='select * from IBO_question_type';
		$res=$sql->simpleQuery($qry);
		
		foreach($res as $q) 
			$question_types[$q['id']] = $q['name'];

		//read questions from textarea
		$from_textarea=explode("\n",$answerkey_text);
		$questions_from_textarea=array();
		$pos=1;

		foreach($from_textarea as $v)
		{
			if(trim($v)!=""){
				$v=explode(';', $v);
				if(count($v)!=5){
					$textarea_parsing_error=true;
					$this->process_msg .= '<p class="error">Question "'.$v[0].'" is missing elements!</p>';
					break;
				}
				if(!isset($questions_from_textarea[$v[0]])){
					$questions_from_textarea[$v[0]]['name']=$v[0];
					$questions_from_textarea[$v[0]]['position']=$pos;
					$questions_from_textarea[$v[0]]['points']=$v[1];
					$questions_from_textarea[$v[0]]['type_id']=$v[2];
					$questions_from_textarea[$v[0]]['type_specific_info']=$v[3];
					$questions_from_textarea[$v[0]]['solution']=$v[4];
					$questions_from_textarea[$v[0]]['exam_id']=$exam_id;
				} else {
					$textarea_parsing_error=true;
					$this->process_msg .= '<p class="error">Questionname "'.$v[0].'" is not unique!!</p>';
					break;
				}
				++$pos;
			}
      	}
		

		if(!$textarea_parsing_error)
		{
		//update questions
			foreach($questions_from_textarea as $k=>$v)
			{
				if(isset($question_name_to_id[$k]))
				{ //stay/update
					if(!$questions[$question_name_to_id[$k]]->compare_vars($v))
					{ //update
						if($questions[$question_name_to_id[$k]]->type_id==$v['type_id']) //update exisitng object
							$questions[$question_name_to_id[$k]]->update_from_object($v);
						else { //generate new object beacuse it has a new type_id
							if(!isset($question_types[$v['type_id']])){
								$textarea_parsing_error=true;
								$this->process_msg.='<p class="error">Question "'.$v['name'].'" has a non defined type ID!!</p>';
								break;
							}
					
							$v->id=$questions[$question_name_to_id[$k]]->id;
							$n="question_".$question_types[$v['type_id']];
							$questions[$question_name_to_id[$k]]=new $n($v);
						}
						
						$a=$questions[$question_name_to_id[$k]]->update_DB();
						if($a)
						{
							$this->process_msg.='<p class="notice">'.$a.'</p>';
							$textarea_parsing_error=true;
							break;
						} 
						else 
						{
							if($a === 0)
								;//VOID, question has not been changed!
							else
								$this->process_msg.='<p class="notice">Question "'.$v['name'].'" has been changed!!</p>';
						}
					}
					unset($question_name_to_id[$k]); //delete in list from DB
				} 
				else 
				
				{ //new object
					if(!isset($question_types[$v['type_id']])){
						$textarea_parsing_error=true;
						$this->process_msg.='<p class="notice">Question "'.$v['name'].'" has a non defined type ID!!</p>';
						break;
					}
					$n="question_".$question_types[$v['type_id']];
					$new_questions=new $n($v);
					if($a=$new_questions->update_DB()){
						$this->process_msg.='<p class="notice">'.$a.'</p>';
						$textarea_parsing_error=true;
						break;
					} else $this->process_msg.='<p class="notice">Question "'.$v['name'].'" has been inserted!!</p>';
				}
			}

			//delete questions remaining in list from DB
			if(!$textarea_parsing_error){
				foreach($question_name_to_id as $k=>$v){
					if($a=$questions[$v]->delete_in_DB()){
						$this->process_msg.='<p class="notice">'.$a.'</p>';
						$textarea_parsing_error=true;
						break;
					} else $this->process_msg.='<p class="notice">Question "'.$k.'" has been deleted!!</p>';
				}
			}
      	}
		
		
   	//-------------------------- end of process_answerkey_form
	}
	
}
?>
