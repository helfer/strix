<?php
class ibo2013Results extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();
	
	public function display()
	{
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query = 'select p.first_name, p.last_name, p.individual_id, c.id as country_id, c.en, c.de, c.fr, c.alpha3, c.flag_file, r.tscore_T, r.tscore_P, r.tscore_total, r.rank, r.medal, r.sum_tscores_P, tr.T1, tr.T2, tr.TTot, pr.p1, pr.p2, pr.p3, pr.p4, pr.t1, pr.t2, pr.t3, pr.t4 from ibo2013_ranking r, ibo2013_participants p, ibo2013_countries c, ibo2013_theory_results tr, ibo2013_practical_results pr where p.id=r.participant_id and p.country_id=c.id and tr.participant_id=p.id and pr.participant_id=p.id order by r.rank asc';
		$res = $sql->simpleQuery($query);		
		$ok = $sql->end_transaction(); 
				
		$xhtml .= '<table class="standard">';
		$xhtml .= '<tr class="header"><td></td><td><p class="monospacebold">Award</p></td><td><p class="monospacebold">Name</p></td><td colspan="2"><p class="monospacebold" style="padding-right:5px;">Delegation</p></td><td width="40"><p class="monospacebold">Theory</p></td><td width="40"><p class="monospacebold">Practical</p></td><td width="40"><p class="monospacebold">Total</p></td>';

		//write also as csv to a file for download
		$file=fopen(HTML_DIR.'webcontent/downloads/IBO2013_final_ranking.txt', 'w');
		fwrite($file, "Rank,Award,First_Name,Last_Name,Delegation_alpha3,Delegation_en,Theory_1,Theory_2,Theory_tot,Practical_1,Practical_1_tscore,Practical_2,Practical_2_tscore,Practical_3,Practical_3_tscore,Practical_4,Practical_4_tscore,Practical_tot,Theory_tscore,Practical_tscore,Total\n");	

		$num=0; $odd=1;
		foreach($res as $r){
			//which class?
			++$num;
			$xhtml.='<tr class="';
			if($num==count($res)) $xhtml.='last';
			else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}
			//Print one student			
			//rank and medal
			$xhtml.='"><td style="padding-right:5px;"><p class="monospace">'.$r['rank'].'</p></td><td style="padding-right:5px;"><p class="monospace">'.$r['medal'].'</p></td>';
			//name
			$name=$r['first_name'].' '.strtoupper($r['last_name']);
			$xhtml.='<td style="padding-right:5px;"><p class="monospace"><a href="/ibo2013/delegations/portrait?country='.$r['country_id'].'">'.$name.'</a></p></td>';
			//country
			$xhtml.='<td style="padding-right:5px;"><p class="monospace"><a href="/ibo2013/delegations/portrait?country='.$r['country_id'].'"><img src="/webcontent/images/flags/'.str_replace(".png", "_thumb.png", $r["flag_file"]).'" class="flag"></a></td><td><a href="/ibo2013/delegations/portrait?country='.$r['country_id'].'"> '.$r["alpha3"].'</a></p></td>';
			//results
			$val=round($r['tscore_T'],3);
			if($val>0) $val='&nbsp;'.$val;
			$xhtml.='<td><p class="monospace">'.$val.'</p></td>';
			$val=round($r['tscore_P'],3);
			if($val>0) $val='&nbsp;'.$val;			
			$xhtml.='<td><p class="monospace">'.$val.'</p></td>';
			$val=round($r['tscore_total'],3);
			if($val>0) $val='&nbsp;'.$val;			
			$xhtml.='<td><p class="monospace">'.$val.'</p></td></tr>';
			//write to file
			fwrite($file, $r['rank'].','.$r['medal'].','.$r['first_name'].','.$r['last_name'].','.$r['alpha3'].','.$r['en'].','.$r['T1'].','.$r['T2'].','.$r['TTot'].','.$r['p1'].','.$r['t1'].','.$r['p2'].','.$r['t2'].','.$r['p3'].','.$r['t3'].','.$r['p4'].','.$r['t4'].','.$r['sum_tscores_P'].','.$r['tscore_T'].','.$r['tscore_P'].','.$r['tscore_total']."\n");
		}
		$xhtml .= "</table>";
		fclose($file);
		return $xhtml;
	}

}
?>

