<?php
class ibo2013InvitationLetterShowContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $userCountryId = -1;
	
	
	public function display()
	{
		$sql = SqlQuery::getInstance();
		$xhtml='';
		//show email recipients with pdf
		$res=$this->readEmailCoordinatorsFromDB($sql);
		if(count($res)>0){
			$xhtml.='<p class="subtitle">E-Mail Contacts</p><table class="standard"><tr class="header"><td><p class="monospacebold">Who</p></td><td><p class="monospacebold">E-Mail</p></td><td><p class="monospacebold">Letter</p></td></tr>';

			foreach($res as $r){
					++$num;
					$xhtml.='<tr class="';
					if($num==count($res)){
						$xhtml.='last';
					} else {
						if($odd==1) $xhtml.='odd';
						else $xhtml.='even';
					$odd=1-$odd;
					}
					$name=$r['name'];
					if($r['title']!='')$name=$r['title'].' '.$name;
					$xhtml.='"><td><p>'.$name.'</p></td><td><p class="monospace">'.$r['email'].'</p></td><td><a href="/webcontent/downloads/invitation_letter/'.$r['invitation_pdf'].'">pdf</a></td></tr>';
				}		
			$xhtml.='</table>';		
		}
		//show paper mail recipients with pdf
		$res=$this->readSnailmailCoordinatorsFromDB($sql);
		if(count($res)>0){
			$xhtml.='<p class="subtitle">Paper Mail Contacts</p><table class="standard"><tr class="header"><td><p class="monospacebold">Who</p></td><td><p class="monospacebold">Address</p></td><td><p class="monospacebold">Letter</p></td></tr>';

			foreach($res as $r){
					++$num;
					$xhtml.='<tr class="';
					if($num==count($res)){
						$xhtml.='last';
					} else {
						if($odd==1) $xhtml.='odd';
						else $xhtml.='even';
					$odd=1-$odd;
					}
					$name=$r['name'];
					if($r['title']!='')$name=$r['title'].' '.$name;
					$xhtml.='"><td><p>'.$name.'</p></td><td><p class="monospace">'.$r['email'].'</p></td><td><a href="/webcontent/downloads/invitation_letter/'.$r['invitation_pdf'].'">pdf</a></td></tr>';
				}		
			$xhtml.='</table>';	
		}
		return $xhtml;
	}
	
	
		public function getMyCountryId(&$sql){
		if($this->userCountryId > 0) return $this->userCountryId;
		//else: get it from DB!	
		$query='';
		switch($GLOBALS['user']->primary_usergroup_id){
			case 2:
			case 36:
			case 27: $query='select id as country_id from ibo2013_countries c where c.alpha3="TTT"'; break;
			case 34:
			case 29: $query='select id as country_id from ibo2013_countries c where c.user_id='.$GLOBALS['user']->id; break;
			case 30:
			case 32:
			case 35:
			case 31: $query.='select country_id from ibo2013_participants p where p.user_id='.$GLOBALS['user']->id; break;
		}
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		if(count($res)!=1){
			$this->process_msg .= '<p class="error">Unable to obtain country id!</p>';
			return -1;
		}		
		$this->userCountryId = $res[0]['country_id'];
		return $res[0]['country_id'];
	}
	
	public function readEmailCoordinatorsFromDB(&$sql){			
		$query='select e.*, t.title from ibo2013_coordinators_email e, ibo2013_titles t where e.title_id=t.id and country_id='.$this->getMyCountryId($sql).' order by e.name asc';		
		$sql->start_transaction(); 		
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		return $res;
	}
	
	public function readSnailmailCoordinatorsFromDB(&$sql){	
		$query='select a.*, t.title from ibo2013_coordinators_snailmail a, ibo2013_titles t where a.title_id=t.id and country_id='.$this->getMyCountryId($sql).' order by a.name asc';		
		$sql->start_transaction(); 		
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		if(!$ok){
			$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
			sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013InvitationLetterSnailmailContent', $query);
		} 
		return $res;
	}
	

}
?>

