<?php
class ibo2013DelegationPortraitContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;

	public function getfromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select country.'.$_SESSION['language_abb'].' as country, country.* from ibo2013_countries country where country.id='.$country_id.';';
		$res = $sql->execute($query);
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		$res=$res[0];
		return $res;
	}

	public function getPreviousResultsfromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select ibo, gold, silver, bronze from ibo2013_results_previous_ibos where country_id='.$country_id.' order by ibo desc';
		$res = $sql->execute($query);
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}

	public function getStudentsfromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select * from ibo2013_participants where country_id='.$country_id.' and delegation_category_id=1 order by last_name asc';
		$res = $sql->execute($query);
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}

	public function getJuryfromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select p.*, tr.title from ibo2013_participants p, ibo2013_titles tr, ibo2013_delegation_categories d where tr.id=p.title_id and country_id='.$country_id.' and 	d.id=p.delegation_category_id and d.class="Jury" and d.yearbook=1 order by d.order_by asc, last_name asc';
		$res = $sql->execute($query);
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}


	public function display(){
		if(isset($_GET['country']) && is_numeric($_GET['country']) && $_GET['country']>0){
			$country_id=$_GET['country'];
			$_SESSION['portrait_country_id']=$country_id;
		} else {
			if($_SESSION['portrait_country_id']){
				$country_id=$_SESSION['portrait_country_id'];
			} else {
				$country_id=52;
			}
		}
		$res=$this->getfromDB($country_id);

		$xhtml='<p class="title">'.$res['country'].'</p>';
		$xhtml.='<div><img class="flag_large" src="/webcontent/images/flags/'.$res['flag_file'].'" style="float: right; margin-left:2em;">';
		$xhtml.='<p class="text">';
		if(strlen($res['web'])>4){
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='<a href="'.$res['web'].'" target="_blank">Nationale Olympiade</a>'; break;
				case 'en':	$xhtml.='<a href="'.$res['web'].'" target="_blank">National Olympiad</a>'; break;
				case 'fr':	$xhtml.='<a href="'.$res['web'].'" target="_blank">Olympiades nationales</a>'; break;
			}
			$xhtml.=' | ';
		}
		$xhtml.='<a href="http://'.$_SESSION['language_abb'].'.wikipedia.org/wiki/'.str_replace(' ', '_', $res['country']).'" target="_blank">Wikipedia</a> | ';
		switch($_SESSION['language_abb']){
			case 'de':	$xhtml.='<a href="https://www.google.ch/maps?q='.$res['en'].'&t=m" target="_blank">Karte</a>'; break;
			case 'en':	$xhtml.='<a href="https://www.google.com/maps?q='.$res['en'].'&t=m" target="_blank">Map</a>'; break;
			case 'fr':	$xhtml.='<a href="https://www.google.ch/maps?q='.$res['en'].'&t=m" target="_blank">Carte</a>'; break;
		}
		$xhtml.='</p>';
		if($res['is_observer']==1){
	    	switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='<p class="text">Wird an der IBO 2013 als <b>Beobachter</b> teilnehmen.</p>'; break;
				case 'en':	$xhtml.='<p class="text">Will participate at the IBO 2013 as an <b>observing country</b>.</p>'; break;
				case 'fr':	$xhtml.='<p class="text">Prend part aux IBO 2013 comme <b>observateur</b>.</p>'; break;
			}
		} else {
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='<p class="text">Nimmt seit <b>'.$res['first_participation'].'</b> an der IBO teil.</p>'; break;
				case 'en':	$xhtml.='<p class="text">Participates in the IBO since <b>'.$res['first_participation'].'</b>.</p>'; break;
				case 'fr':	$xhtml.='<p class="text">Prend part aux IBO depuis <b>'.$res['first_participation'].'</b>.</p>'; break;
			}
		}
		if($res['host_year']>1980){
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='<p class="text">War Gastgeber der <b>IBO'.$res['host_year'].'</b> in <b>'.$res['host_city'].'</b> für';
				 if($res['host_num_students']>0) $xhtml.=' '.$res['host_num_students'];
				 $xhtml.=' Studenten aus <b>'.$res['host_num_countries'].' Ländern</b>.</p>'; break;
				case 'en':	$xhtml.='<p class="text">Hosted the <b>IBO '.$res['host_year'].'</b> in <b>'.$res['host_city'].'</b> with';
				if($res['host_num_students']>0) $xhtml.=' '.$res['host_num_students'];
				$xhtml.=' students from <b>'.$res['host_num_countries'].' countries</b>.</p>'; break;
				case 'fr':	$xhtml.='<p class="text">Hôte des <b>IBO '.$res['host_year'].'</b> &agrave; <b>'.$res['host_city'].'</b> avec';
				if($res['host_num_students']>0) $xhtml.=' '.$res['host_num_students'];
				$xhtml.=' étudiants de <b>'.$res['host_num_countries'].' pays</b>.</p>'; break;
			}
		}

		//get previous results
		$res=$this->getPreviousResultsfromDB($country_id);
		if(count($res)>0){
			$xhtml.='<p class="formparttitle">';
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='Resultate früherer Olympiaden'; break;
				case 'en':	$xhtml.='Results from previous IBOs'; break;
				case 'fr':	$xhtml.='Résultats obtenus'; break;
			}
			$xhtml.='</p><table><tr align="center" valign="middle"><td class="borderboth" width="70"></td>';
			$metal=array();
			$metal[0]='Gold'; $metal[1]='Silver'; $metal[2]='Bronze';
			switch($_SESSION['language_abb']){
				case 'de': $metal[0]='Gold'; $metal[1]='Silber'; $metal[2]='Bronze'; break;
				case 'fr': $metal[0]='Or'; $metal[1]='Argent'; $metal[2]='Bronze'; break;
			}
			for($i=0; $i<3; ++$i){
				$xhtml.='<td width="40" class="borderboth"><p class="nomargin"><b>'.$metal[$i].'</b></p></td>';
			}
			$xhtml.='</tr>';
			$tdclass='';
			for($i=0; $i<count($res); ++$i){
				if($i==count($res)-1) $tdclass=' class="borderbottom"';
				$xhtml.='<tr><td'.$tdclass.'><p class="nomargin">IBO '.$res[$i]['ibo'].'</p></td><td align="center"'.$tdclass.'><p class="nomargin">'.$res[$i]['gold'].'</p></td><td align="center"'.$tdclass.'><p class="nomargin">'.$res[$i]['silver'].'</p></td><td align="center"'.$tdclass.'><p class="nomargin">'.$res[$i]['bronze'].'</p></td></tr>';

			}

			$xhtml.='</table>';
		}
		//Team portrait
		$res=$this->getStudentsfromDB($country_id);
		if(count($res)>0){
			$xhtml.='<p class="formparttitle">';
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='Teilnehmende Schülerinnen und Schüler'; break;
				case 'en':	$xhtml.='Participating Students'; break;
				case 'fr':	$xhtml.='Étudiants qui participent'; break;
			}
			$xhtml.='</p><table><tr>';
			$names='<tr>';
			foreach($res as $r){
				$xhtml.='<td width="125"><img src="/webcontent/images/participant_pictures/';
				if($r['photo_on_web']==1 && $r['photo_basename']!="") $xhtml.=$r['photo_basename'].'_120px.jpg';
				else $xhtml.='no_photo_120px.png';
				$xhtml.='"/></td>';
				$names.='<td><p>'.$r['first_name'].'<br/>'.strtoupper($r['last_name']).'</p></td>';
			}
			$xhtml.='</tr>'.$names.'</tr></table>';
		}

		//Jury
		$res=$this->getJuryfromDB($country_id);
		if(count($res)>0){
			$xhtml.='<p class="formparttitle">';
			switch($_SESSION['language_abb']){
				case 'de':	$xhtml.='Mitglieder der Internationalen Jury'; break;
				case 'en':	$xhtml.='Members of the International Jury'; break;
				case 'fr':	$xhtml.='Membres du jury international'; break;
			}
			$xhtml.='</p><table><tr>';
			$names='<tr>';
			$i=0;
			foreach($res as $r){
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
