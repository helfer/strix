<?php
class ibo2013PickRandomDelegationsContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	protected $num_delegations = 3;
	
	public function htmlOfOneCountry($c){
		return '<td width="10"><a href="/ibo2013/delegations/portrait?country='.$c['id'].'"><img src="/webcontent/images/flags/'.str_replace(".png", "_thumb.png", $c["flag_file"]).'" class="flag"></a></td><td width="130"><a href="/ibo2013/delegations/portrait?country='.$c['id'].'"> '.$c["country"].'</a></td>';
	}
	
	
	public function display()
	{
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select a.id, a.'.$_SESSION['language_abb'].' as country, b.'.$_SESSION['language_abb'].' as continent, a.flag_file from ibo2013_countries a, ibo2013_continents b where a.continent_id=b.id order by rand() limit 0,'.$this->num_delegations;
		$res = $sql->execute($query);		
		$ok = $sql->end_transaction(); 

		$res = $sql->mysql2array($res);
		
		$xhtml = $this->process_msg;
		$xhtml .= "<table>";
		$nrows=ceil(count($res)/3);
		for($i=0; $i<$nrows; ++$i){				
			$xhtml .= '<tr height="30">'.$this->htmlOfOneCountry($res[$i]);
			if(($nrows+$i)<count($res)) $xhtml .= $this->htmlOfOneCountry($res[$nrows+$i]);
			if((2*$nrows+$i)<count($res)) $xhtml .= $this->htmlOfOneCountry($res[2*$nrows+$i]);
			$xhtml.='</tr>';
		}
		$xhtml .= "</table>";
	


		return $xhtml;
	}

}
?>

