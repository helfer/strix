<?php
/*
 *      examresults.php
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
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

/* 1. select student exams from DB, order by totals [already calculated when corrected!]
 * 2. parse, calculate avg,min,max,variance and set rank (increase when total decreases)
 * 3. traverse array again, this time calculate and set t-score = (points - avg) / sqrt(variance)* 10 + 40
 * 
 * 4. create graphs for score distribution and store in /files/
 * 5. output links to images, avg, max, min, variance etc. 
 * 6. output ranking? (make optional, set in conf);
 * 
 */
class ExamResults extends Content
{
	
	const tscore_mean = 50; // mean = base for points given.
	const tscore_factor = 10; //points per SD
	const tscore_sfig = 5; //significant figures for rounding of t-scores!

	function display()
	{
		$image_owner_id = 2; //jonas
		$owner_permission = 1; //read
		
		$xhtml = '';
		
		include("./content/utils/phpMyGraph4.0.php");
		
		$form = $this->getForm('SelectExamsForm');
		
		$xhtml .= $form->getHtml($this->id);
		
		$eid = $form->getElementValue('exam_id');
		
		list($image_data,$student_exams) = self::calculate_tscore($eid,$xhtml);
		//---------------------------------
	
		
		//CREATE IMAGE -------------------------------------------------
		$sql = SqlQuery::getInstance();
		
		
		//Create new graph 
		$graph = new phpMyGraph();
		
		$image_name = 'exam_'.$eid.'_stats.jpg';
		$image_dir = FILE_DIR.'/img/';
		
		$filename = $image_dir.$image_name;
		
		$cfg = array();
		$cfg['file-name'] = $filename;
		$cfg['font-size'] = 1;
		$cfg['min-col-width'] = 24;
		$cfg['title'] = 'Score distribution for exam '.$eid;
        
		//Parse vertical line graph and save png file
		$graph->parseVerticalColumnGraph($image_data,$cfg); 
		echo 'savec image: ';print_r($cfg);
		
		//TODO: more flexible please...
		//create image in database if it doesn't exist...
		$finfo = array('name'=>$image_name,
		'path'=>FILE_LOC.'img/',
		'owner_id'=>$image_owner_id,
		'owner_permission'=>$owner_permission,
		'modified'=>date('Y-m-d h:i:s'),
		'mimetype_id'=>'3'); //mimetype2 = png
		$ok = $sql->UpdateQuery('file',$finfo,array('name'=>$finfo['name'],'path'=>$finfo['path']),1);
		
		if(!$ok)
			$sql->insertQuery('file',$finfo);
		
		
		
		
		$xhtml .= '<br /><a href="'.FILE_PATH.'img/?file='.$image_name.'">score distribution image</a><br />';
		//$xhtml .= '<a href="'.RES_FILE_PATH.'img/'.$image_name.'">image</a>';
		
				
		//print restults incl. t-score
		$xhtml .= array2html($student_exams);
		
		
		return $xhtml.'<hr></hr>';	
	}
	
	
	public static function calculate_tscore($eid, &$xhtml = null)
	{

		include_once('./content/utils/stat_functions.php');
	
		$sql = SqlQuery::getInstance();



		if(!isset($xhtml))
			$xhtml = '';




		//check exam type. if type = 2 (manual), need to calculate totals...

		if( $sql->singleValueQuery("SELECT type FROM IBO_exam WHERE id = '$eid'") == 2)
		{
			echo 'updated!';
			$query_update = "UPDATE IBO_student_exam se SET se.total = (SELECT sum(score) FROM IBO_student_answer WHERE student_exam_id = se.id) WHERE exam_id = '$eid'";
			$sql->execute($query_update);
		}
		
		$query = "
			SELECT u.first_name, u.last_name, se.* FROM `IBO_student_exam` se
			JOIN `user` u ON u.`id`= se.`user_id`
			WHERE se.`exam_id`='$eid' 
			ORDER BY se.`total` DESC,
			se.`rang` DESC,
			se.`rang_de` DESC,
			se.`rang_fr` DESC";
		$e_keys = array('id');
		$e_values = array('id','first_name','last_name','language_id','total','rang','rang_de','rang_fr','passed','t_score');
		
		$student_exams = $sql->assocQuery($query,$e_keys,$e_values);
		
		if(empty($student_exams))
			return $xhtml . '<br /><b>No student exams found for this exam!</b>';
		
		//print_r($student_exams);
		
		$points_arr = array_multiintersect($student_exams,'total');
		
		$min = min($points_arr);
		$max = max($points_arr);
		$avg = average($points_arr);
		$vari = variance($points_arr);
		$p_num = count($student_exams);
		
		//calculate tscore.
		echo 'mean= '.self::tscore_mean;
		$tscore_array = tscore_array($points_arr,self::tscore_mean,self::tscore_factor,self::tscore_sfig);
		
		//print_r($points_arr);
		//print_r($tscore_array);
		
		$xhtml .= "<br />participants: $p_num";
		$xhtml .= "<br /><b>min</b>= $min, <b>max</b>= $max,<b>avg</b>=".round($avg,3).", <b>var</b>=".round($vari,3)."<br />";
		
		$rank = 0;
		$rank_de = 0;
		$rank_fr = 0;
		$current_total = 0;
		$current_total_de = 0;
		$current_total_fr = 0;
		
		$count = 0;
		$count_de = 0;
		$count_fr = 0;
		
		$image_data = array_fill(round($min),round($max)-round($min)+1,0); //information for vertical column graph
		
		
		foreach($student_exams as $se_id=>$student)
		{
			$count++;
			
			$student['t_score'] = $tscore_array[$se_id];
			
			if($student['total'] != $current_total)
			{
				//$xhtml .= $student['total'].' != '.$current_total.'<br />';
				$current_total = $student['total'];
				$rank = $count;
			}
			
			$image_data[round($current_total)]++;
			
			$student['rang'] = $rank;
			
			if($student['language_id'] == 2 || $student['language_id'] == 3) //fr OR it
			{
				$count_fr++;
				
				if($student['total'] != $current_total_fr)
				{
					$rank_fr = $count_fr;
					$current_total_fr = $student['total'];	
				}
					
				$student['rang_fr'] = $rank_fr;
				$student['rang_de'] = 'sql_NULL';
				
			}
			else if($student['language_id'] == 1) //de
			{
				$count_de++;
				
				if($student['total'] != $current_total_de)
				{
					$rank_de = $count_de;
					$current_total_de = $student['total'];	
				}
					
				$student['rang_de'] = $rank_de;
				$student['rang_fr'] = 'sql_NULL';
			}
			else
				throw new Exception('wrong language, idiot!');
			
			//YES, this is necessary
			$student_exams[$se_id] = $student;

			
		}
		
		
		//STORE TABLE BACK IN DATABASE ---------------------------------
		
		$affected = 0;
		foreach($student_exams as $ident=>$exam_info)
		{
			unset($exam_info['first_name']);
			unset($exam_info['last_name']);
			$affected += $sql->updateQuery('IBO_student_exam',$exam_info,array('id'=>$ident));
		}
		$xhtml .= '<br />updated '.$affected.' rows in database<br />';

		return array($image_data,$student_exams);
	}

	
	/*
	 * FORMS -----------------------------------------------------------
	 */
	
	
	public function	process_SelectExamsForm()
	{
		$this->getForm('SelectExamsForm')->validate();	
	}
	
	
	function SelectExamsForm($vector)
	{
		$newForm = new SimpleForm(__METHOD__);
		$newForm->setVector($vector);
	
		$query = "SELECT e.id, e.de
			FROM
			IBO_exam e
			WHERE closed = '0'
			ORDER BY `date` DESC";

		$exams = SqlQuery::getInstance()->assocQuery($query,array('id'),array('de'), TRUE);
		
		$sel_exam = new Select('Exam: ','exam_id',$exams);
		$newForm->addElement('exam_id',$sel_exam);
		
		$newForm->addElement('submit',new Submit('submit','select'));

		$newForm->stayAlive();
		return $newForm;
	}


}




?>
