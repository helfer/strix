<?php
class ibo2013DelegationContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	public function htmlOfOneCountry($c){
		return '<td width="10"><a href="/ibo2013/delegations/portrait?country='.$c['id'].'"><img src="/webcontent/images/flags/'.str_replace(".png", "_thumb.png", $c["flag_file"]).'" class="flag"></a></td><td width="130"><a href="/ibo2013/delegations/portrait?country='.$c['id'].'"> '.$c["country"].'</a></td>';
	}
	
	
	public function display()
	{
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select a.id, a.'.$_SESSION['language_abb'].' as country, b.'.$_SESSION['language_abb'].' as continent, a.flag_file from ibo2013_countries a, ibo2013_continents b where a.continent_id=b.id order by continent asc, country asc;';
		$res = $sql->execute($query);		
		$ok = $sql->end_transaction(); 

		$res = $sql->mysql2array($res);
		$continents=array();
		foreach($res as $v){
			if(!isset($continents[$v["continent"]])) $continents[$v["continent"]]=array();
			$continents[$v["continent"]][]=$v;
		}

		$xhtml = $this->process_msg;
		$xhtml .= "<table>";
		foreach($continents as $c=>$l){
			$xhtml .= "<tr><td colspan='6'><p class='subtitle'>".$c."</td></tr>";
			$nrows=ceil(count($l)/3);
			for($i=0; $i<$nrows; ++$i){				
				$xhtml .= '<tr height="30">'.$this->htmlOfOneCountry($l[$i]);
				if(($nrows+$i)<count($l)) $xhtml .= $this->htmlOfOneCountry($l[$nrows+$i]);
				if((2*$nrows+$i)<count($l)) $xhtml .= $this->htmlOfOneCountry($l[2*$nrows+$i]);
				$xhtml.='</tr>';
			}
		}
		$xhtml .= "</table>";
	


		return $xhtml;
	}

}
?>

