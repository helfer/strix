<?php
/*
 *      finalranking.php
 * 
 * ----------> IS ONLY APPLICABLE FOR THE FINAL ROUND 2009 AND MUST BE MODIFIED FOR OTHER PURPOSES!!!!
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
class FinalRanking extends Content{
	
	//protected $mConfKeys = array('mean','std-dev','sfig');
	//protected $mConfValues = array('50','10','1');

	//SELECT EXAMS OF FINAL ROUND (id, name,de,fr, weight) FROM DATABASE
	function display(){

		$sql = SqlQuery::getInstance();
		
		$output_array = array();
		

		//TODO: yeah, I know this is a funny query. But that's how i did it last year :D
		$exams = $sql->simpleQuery("
			SELECT * FROM `IBO_exam` WHERE MONTH(date) = 4 AND YEAR(date) = 2010 ORDER BY id ASC");
			
		$weights = array();
		$praktika = '';
			
		$output_array[0]['Rang'] = '';
		$output_array[0]['Name'] = 'Gewichtung:';
		$tot = 0;
		$i = 0;
		$cur_name = '';
		$de_name_map = array();
		foreach($exams as $e){


			//update and set t-score, if not yet done.
			ExamResults::calculate_tscore($e['id']);			

			$i++;
			$cur_name = 'P'.$i;
			$de_name_map[$e['de']] = $cur_name;
			//print_r($e);
			$output_array[0][$cur_name] = $e['weight'];
			$tot += $e['weight'];
			$weights[$e['de']] = $e['weight'];
			$praktika .=  $cur_name.': '.$e['de'].'<br />';
		}
		$output_array[0]['Total'] = '';
		
		$students = $sql->simpleQuery("SELECT CONCAT(u.first_name,' ', u.last_name) as name, u.*, SUM(se.t_score*ie.weight) score
FROM `IBO_student_exam` se 
JOIN user u ON u.id = se.user_id
JOIN IBO_exam ie ON ie.id = se.exam_id

WHERE MONTH(ie.date) = 4 AND YEAR(ie.date) = 2010
GROUP BY u.id ORDER BY score DESC");
		
		$rank = 1;
		foreach($students as $stud){
			if($rank == 5) //for to make pretty sings happen on se peidsch.
				$output_array []= array('rank'=>'--','name'=>'----');
				
			$scores = $sql->simpleQuery("SELECT ie.de, ise.t_score 
				FROM IBO_student_exam ise JOIN IBO_exam ie ON ie.id = ise.exam_id
				WHERE MONTH(ie.date) = 4 AND YEAR(ie.date) = 2010 AND ise.user_id = '".$stud['id']."'
				ORDER BY ie.id");
				
			$arr = array();
			$total = 0;
			$arr['Rang'] = $rank++;
			$arr['Name'] = $stud['name'];
			foreach($scores as $co){
				$arr[$de_name_map[$co['de']]] = round($co['t_score'],1);
				$total += $co['t_score']*$weights[$co['de']];
			}
			$arr['Total'] = round($total,1);
			
			$output_array []= $arr;
		}
	
			return $praktika.array2html($output_array);
	}
	
}

//FOR EACH STUDENT: SELECT T_SCORE FOR EACH EXAM

//print out table with scores + sum!



?>

