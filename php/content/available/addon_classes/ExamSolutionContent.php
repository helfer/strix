<?php

class ExamSolutionContent extends Content{
	
	
	protected $mConfKeys = array('exam_id');
	protected $mConfValues = array('19');

	function display()
	{

		$xhtml='';

 
  	//----------------------------------------------------------------------------
  	//create question array
  	include_once(SCRIPT_DIR . 'ibo_classes/class_question.php');
   	$query = "SELECT 
   				q.*, 
   				qt.name as type_name
   			FROM
   				IBO_question q JOIN IBO_question_type qt 
   					ON qt.id=q.type_id
   			WHERE 
   				exam_id='".$this->mConfValues['exam_id']."'
   			ORDER BY
   				q.position";
   				
    $q_array = SqlQuery::getInstance()->simpleQuery($query);
    		
    $questions=array();
    
    foreach($q_array as $s)
    {
    	$n='question_'.$s['type_name'];
        $questions[]=new $n($s);     
    }
    //print_r($questions);
    //return;
    
    //create array
    $key=array();
    switch($_SESSION['language_abb']){
		case 'de': $key=array('Frage', 'L&ouml;sung DE', 'L&ouml;sung FR', 'L&ouml;sung IT', 'richtig gel&ouml;st von', 'h&auml;ufigste falsche Antwort'); $or=' oder '; break;
		case 'fr': $key=array('Question', 'Solution DE', 'Solution FR', 'Solution IT', '% juste', 'R&eacute;ponse fausse la plus fr&eacute;quante'); $or=' ou '; break;
		case 'it': $key=array('Domanda', 'risposta DE', 'risposta FR', 'risposta IT', '% guisto', 'Risposte errato pi&ugrave; frequente'); $or=' o '; break;
		case 'en': $key=array('Question', 'Solution DE', 'Solution FR', 'Solution IT', '% richtig', 'häufigste falsche Antwort'); $or=' or '; break;		
	}	
    
    $table=array();
    foreach($questions as $v){
    	$table[]=array($key[0]=>$v->name, 
    	               $key[1]=>$v->get_solution_Language(1, $or), 
    	               $key[2]=>$v->get_solution_Language(2, $or), 
    	               $key[3]=>$v->get_solution_Language(3, $or), 
    	               $key[4]=>number_format(100*$v->prop_right,1).'%', 
    	               $key[5]=>$v->mostfreq_wrong.' ('.number_format(100*$v->prop_mostfreq_wrong,1).'%)');
	}
	
	//add statistics
	
    	
    //show solutions    
    $xhtml.=array2html($table) ;
    return $xhtml;
}
	

}

?>
