<?php
/*
 *      stat_functions.php
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

/*
include("phpMyGraph4.0.php");


$data = array('10'=>0,'20'=>1,'30'=>6,'40'=>12,'50'=>10,'60'=>5,'70'=>1);

//Create new graph 
$graph = new phpMyGraph();
        
//Parse vertical line graph and outpunt as png
$graph->parseVerticalColumnGraph($data); 
*/


/*
$a = array(1,2,3,3.5,4,5,6);
echo average($a);
echo "\n";
echo variance($a);
echo "\n";
echo std_deviation($a);
echo "\n";
print_r(tscore_array($a,10,50,2));
echo "\n";
*/


function average($arr)
{
	if(empty($arr))
		//throw new Exception('array is empty!');
		return 0;
	
	return array_sum($arr) / count($arr);	
}

//computes the variance
function variance($arr,$sample = FALSE)
{
	if(empty($arr))
		return 0;
		
	$avg = average($arr);
	
	$sum = 0;
	
	foreach($arr as $v)
		$sum += pow(($v - $avg),2);
		
	return $sum / count($arr);
}

function std_deviation($arr)
{
	return sqrt(variance($arr));	
}


//T-score eval ............. (z-score)
//stretch by factor and add to a base to avoid negative values.
function tscore($value,$mean,$variance,$base = 0,$stretch = 1,$sfig = -1)
{
	if($variance == 0)
		$score = $base;
	else
		$score =  ( ($value - $mean)/sqrt($variance) ) * $stretch + $base;
		
	return ($sfig >= 0) ? round($score,$sfig) : $score;
	//if sfig >= 0 , then round the values
}


//takes an array with absolute scores as values and returns an array
//with the same keys and z-scores as values.
//for t-score, the values can be stretched by a factor and 
//a base-value can be added to avoid negative scores.
//round to significant figures...
function tscore_array($arr,$base = 0,$stretch = 1,$sfig = -1){
	$mean = average($arr);
	$variance = variance($arr);
	
	/*
	echo $base;
	echo '<br />';
	echo($mean);
	echo '<br />';
	echo($variance);
	echo '<br />';
	*/
	
	$ret_array = array();
	
	foreach($arr as $k=>$v)
	{
		$ret_array[$k] = tscore($v,$mean,$variance,$base,$stretch,$sfig);	
	}
	
	return $ret_array;
	
}


function array_zip_mult($val_arr,$mult_arr)
{
	return ;
}

?>
