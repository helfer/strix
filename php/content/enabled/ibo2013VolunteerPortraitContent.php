<?php
class ibo2013VolunteerPortraitContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	public function getFromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select p.*, dc.* from ibo2013_participants p, ibo2013_delegation_categories dc where dc.id=p.delegation_category_id and dc.class="Volunteer" and dc.yearbook=1 order by dc.order_by, last_name asc';
		//$query='select * from ibo2013_participants where photo_basename!="" order by delegation_category_id asc, last_name asc';
		//echo $query."\n";
		$res = $sql->execute($query);		
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}
	
	
	public function display()
	{
		$xhtml='';
		$res=$this->getFromDB($country_id);
		if(count($res)>0){
			$category='';
			foreach($res as $r){
				if($r['category']!=$category){
					if($category!="") $xhtml.='</tr>'.$names.'</tr></table>';
					$category=$r['category'];
					$xhtml.='<p class="formparttitle">'.$category.'</p>';
					$i=0;
					$xhtml.='<table><tr>';
					$names='<tr>';		
				}
				++$i;
				if($i==5){
					$i=1;
					$xhtml.='</tr>';
					$xhtml.='</tr>'.$names.'</tr><tr height="15"></tr><tr>';
					$names='<tr>';
				}
				$xhtml.='<td width="125"><img src="/webcontent/images/participant_pictures/';					
				if($r['photo_on_web']==1 && $r['photo_basename']!="") $xhtml.=$r['photo_basename'].'_120px.jpg';
				else $xhtml.='no_photo_120px.png';
				$xhtml.='"/></td>';
				$names.='<td><p>';
				if($r['title']!="") $names.=$r['title'].' ';
				$names.=$r['first_name'].'<br/>'.strtoupper($r['last_name']).'</p></td>';
			}
			$xhtml.='</tr>'.$names.'</tr></table>';			
		}
		

		$xhtml.='</div>';


		return $xhtml;
	}


}
?>

