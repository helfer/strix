<?php
class ibo2013ScientificCommiteePortraitContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;

	public function getFromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select p.*, dc.*, tr.title from ibo2013_participants p, ibo2013_delegation_categories dc, ibo2013_titles tr where dc.id=p.delegation_category_id and (dc.category="Scientific Committee" or (dc.category="Management" and p.last_name="Wegmann")) and tr.id=p.title_id order by dc.order_by, last_name desc';
		$res = $sql->execute($query);
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}


	public function display(){
		$xhtml='';
		$res=$this->getFromDB($country_id);
		if(count($res)>0){
			$i=0;
			$xhtml.='<table><tr>';
			$names='<tr>';
			foreach($res as $r){
				++$i;
				if($i==5){
					$i=1;
					$xhtml.='</tr>'.$names.'</tr><tr height="15"></tr><tr>';
					$names='<tr>';
				}
				$xhtml.='<td width="125"><img src="/webcontent/images/participant_pictures/';
				if($r['photo_on_web']==1 && $r['photo_basename']!="") $xhtml.=$r['photo_basename'].'_120px.jpg';
				else $xhtml.='no_photo_120px.png';
				$xhtml.='"/></td>';
				$names.='<td>';
				switch($r['last_name']){
					case 'Wegmann': $names.='<a href="http://www.unifr.ch/biochem/index.php?id=789" target="new">';
									$end='</a>';
									break;
					case 'Jutzi': $names.='<a href="http://www.botanischergarten.ch/web/boga/ueber_uns/infoflora.html" target="new">';
									$end='</a>';
									break;
					case 'Wegmann': $names.='<a href="http://www.unifr.ch/biochem/index.php?id=789" target="new">';
									$end='</a>';
									break;
				}
				if($r['title']!="") $names.=$r['title'].' ';
				$names.=$r['first_name'].'<br/>'.strtoupper($r['last_name']).$end.'</td>';
			}
			$xhtml.='</tr>'.$names.'</tr></table>';
		}
	
		return $xhtml;
	}
} ?>
