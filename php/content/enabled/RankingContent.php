<?php

class RankingContent extends Content{

	protected $mConfKeys = array('exam_id');
	protected $mConfValues = array('0');

	function display()
	{	

		$xhtml='';

  	//----------------------------------------------------------------------------
  	//show ranking
    $nureinmal=0;
    $query = "SELECT  
    			s.last_name as Name, 
    			s.first_name as Vorname, 
    			e.total as Punkte, 
    			e.rang as Rang, 
    			e.rang_de as 'Rang DE', 
    			e.rang_fr as 'Rang FR',
	   			IF(e.passed = '1',\"*\",(IF(e.passed = '-1',\"a\",(IF(e.passed = '-2',\"?\",\"\"))))) as q
	   		FROM 
	   			user s JOIN IBO_student_exam e ON e.user_id = s.id
	   		WHERE 
	   			e.exam_id=".$this->mConfValues['exam_id']."
    		ORDER BY 
    			e.rang ASC, 
    			s.last_name ASC, 
    			s.first_name ASC;";
    
    //attention, because we have an ORDER BY clause, the Ordering argument to AbTable must be FALSE
    
    $table = new AbTable('ranking'.$this->mConfValues['exam_id'],$query,array(),FALSE);
    return $table->getHtml();
    			
    $sql = SqlQuery::getInstance();
	$ranking_length = mysql_num_rows($result);
	$raw_ranking=array();
	$ranking=array();
	for ($i=0;$i<$ranking_length;$i++){
		$ranking[$i]=array();
		$raw_ranking[]=mysql_fetch_assoc($result);	
		if ($raw_ranking[$i]['passed']==1){
			$ranking[$i]['Name']=$raw_ranking[$i]['name'].'<span class="redstar">*</span>';			
		}
		else $ranking[$i]['Name']=$raw_ranking[$i]['name'];			
		$ranking[$i]['Vorname']= $raw_ranking[$i]['vorname'];
		$ranking[$i]['Punkte']= $raw_ranking[$i]['total'];
		
		$ranking[$i]['Rang']=$raw_ranking[$i]['rang'];
		if ($raw_ranking[$i]['rang_de']==0){
			$ranking[$i]['Rang DE']='';
		}
		else $ranking[$i]['Rang DE']=$raw_ranking[$i]['rang_de'];
		if ($raw_ranking[$i]['rang_fr']==0){
			$ranking[$i]['Rang FR']='';
		}
		else $ranking[$i]['Rang FR']=$raw_ranking[$i]['rang_fr'];
	}     
    
    $html.=array2html($ranking) ;
    //print_r($ranking);
    $color=0;

		return $xhtml;
	}

	

}

?>
