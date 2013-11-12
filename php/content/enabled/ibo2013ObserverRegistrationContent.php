<?php
class ibo2013ObserverRegistrationContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg_adding = '';
	protected $process_status_adding = false;
	protected $process_msg_cancelling = '';
	protected $process_status_cancelling = false;
	
	protected $userCountryId = -1;
	
	protected $currentFee = 0;
	protected $fees = array();
	protected $visitor_rebate = 150;
	protected $cheaper_bed_rebate = 500;
	protected $catname = 'observer';
	protected $catname_caps = 'Observer';
	
	
	public function display(){	
		$xhtml='';	
		if($this->process_status_adding){
			 $addingObserverForm=$this->remakeForm('observer_registration_form');				 
		} else {
			$addingObserverForm=$this->getForm('observer_registration_form');		
		}
		
		if($this->process_status_cancelling){
			 $removingObserverForm=$this->remakeForm('observer_cancellation_form');					 
		} else {
			$removingObserverForm=$this->getForm('observer_cancellation_form');		
		}		
		
		$sql = SqlQuery::getInstance();
		
		$xhtml .= $this->process_msg_adding;
		$xhtml.=$this->process_msg_cancelling;
		
		

		$xhtml.='<p class="title">Registering '.$this->catname_caps.'s</p>';
		
		$xhtml.='<p class="text">Each '.$this->catname.' is required to pay a fee covering food, accommodation, transportation and excursions during the IBO 2013, starting on the evening (including dinner) of July 14, 2013, ending with breakfast on July 21, 2013. We are only able to provide our lowest fees to delegations registering their '.$this->catname.'s early. To register at a low fee, no names have to be provided at registration. <b>Names</b> and further information of each delegation member will be <b>needed by May 25</b>, 2013 latest.</p>';
				
		$this->writeObserverFees($sql, $xhtml);
		
		//show registration history, if it exists
		$hasHistory=$this->writeRegistrationHistory($sql, $xhtml);
		
		//show form to add observers
		$xhtml.='<p class="subtitle">Registering '.$this->catname_caps.'s</p>';
		$xhtml.='<p class="text">If you wish to register more than one '.$this->catname.', repeat the registration for every extra person.</p>';
		$xhtml.='<p class="text"><b>NOTE:</b> Cancelling a registered '.$this->catname.' at a later stage may result in a cancellation fee! See above for details.</p>';
		
		$xhtml .= $addingObserverForm->getHtml($this->id);
		
		//show form to remove observers
		if($hasHistory){
			$xhtml.='<p class="subtitle">Cancelling the Registration of '.$this->catname_caps.'s</p>';
			$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>NOTE:</b> Cancelling a registered '.$this->catname.' may result in a cancellation fee! See above for details and in the dropdown menu below for the amount due.</p>';
			
			//check if there are signed up jury members
			$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>NOTE:</b> You can only cancel registrations for which no-one has been signed-up. Please cancel the registration of this person first (see <a href="http://www.ibo2013.org/Participate/TeamRegistration/TeamMembers/">here</a>).</p>';

			$xhtml.=$removingObserverForm->getHtml($this->id);
		}
		return $xhtml;
	}
	
	public function getGoogleCurrencyConverter($amount, $currency){
		$url='http://www.google.com/ig/calculator?hl=en&q='.$amount.'CHF=?'.$currency;
		
		$res=file_get_contents($url);
		$res=explode(',', $res);
		$res=explode('"', $res[1]);
		$res=round(preg_replace('/[^0-9\.]/', '', $res[1]), 3);
		return $currency.' '.$res;		
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
	
	public function readFeesFromDB(&$sql){
		$query='select * from ibo2013_fees where type="observer" order by last_date asc';		
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$sql->end_transaction(); 
		$this->fees=array();
		$last_timediff=999999999;
		foreach($res as $r){
			$timediff=(strtotime($r['last_date'])-time()+24.1*3600);
			if($timediff>0 && $timediff<$last_timediff){
				$last_timediff=$timediff;
				$this->currentFee=$r['fee'];
			}
			$this->fees[]=array('last_date'=>$r['last_date'], 'amount'=>$r['fee'], 'time_diff'=>$timediff);
		}
	}
	
	public function readCheaperObserverbedsFromDB(&$sql){
		$query='select * from ibo2013_cheaper_observer_beds';
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$sql->end_transaction(); 
		$res=$res[0];
		$res['available']=$res['num_beds']-$res['num_beds_booked'];
		return $res;
	}
	
	public function getRegistrationHistoryFromDB(&$sql){
		$query='select tr.*, d.id as category_id, d.category, u.first_name, u.last_name from ibo2013_observer_registration tr, user u, ibo2013_delegation_categories d where tr.country_id='.$this->getMyCountryId($sql).' and tr.user_id=u.id and d.id=tr.delegation_category_id order by tr.registration_date asc';		
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$sql->end_transaction(); 
		return $res;
	}
	
	public function writeObserverFees(&$sql, &$xhtml){
		$this->readFeesFromDB($sql);
		
		$xhtml.='<p class="subtitle">Fees for '.$this->catname_caps.'s</p>';
		$xhtml.='<p class="text">The indicated fees are valid if an observer is registered before or on the date specified in the table below. The relevant time zone is the Central European Time (CET).</p>';

		$xhtml.='<table class="standard"><tr class="header"><td colspan="2"><p class="monospacebold">Fee</p></td><td><p class="monospacebold">Valid Until</p></td><td><p class="monospacebold"></p></td></tr>';
		
		$num=0;
		$odd=1;
		$current_found=false;
		foreach($this->fees as $f){
			++$num;
			$xhtml.='<tr class="';
			if($num==count($this->fees)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}
				
			if($f['time_diff']<0){
				$style=' style="text-decoration: line-through;"';
				$closes_in='';
			} else {
				if(!$current_found){						
					$style=' style="font-weight: bold;"';
					$current_found=true;
					$days_until=ceil(($f['time_diff']-0.11*3600)/(24*3600));
					$closes_in='closes in <'.$days_until.' day'; 
					if($days_until>1) $closes_in.='s';
				} else {
					$style='';
					$closes_in='';			
				}
			}
			$xhtml.='"><td width="10"><p class="monospace"'.$style.'>CHF</p></td><td><p class="monospace"'.$style.'>';
			if($f['amount']<1000) $xhtml.='&nbsp';
			$xhtml.=$f['amount'].'</p></td><td><p class="monospace"'.$style.'>'.date('l, d M Y', strtotime($f['last_date'])).'</p></td><td><p class="monospace"'.$style.'>'.$closes_in.'</p></td></tr>';
		}
			
		$xhtml.='</table>';

		$xhtml.='<p class="text"><b>Currency:</b> All fees are to be paid in Swiss Francs (CHF). Current conversion rates as provided by the Google API are 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'EUR').' or 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'USD').'. These rates are provided without any warranty.</p>';
		
		$this->getGoogleCurrencyConverter(2241, 'EUR');
			
		$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>Cancellation Fee:</b> Registered '.$this->catname.'s can be cancelled at any time. However, a cancellation fee may be due. This fee corresponds to the difference between the fee at the time of cancellation and the fee at the time of registration. For instance, registering in February 2013 (fee of CHF 1700) and cancelling the registration on May 12 (fee of CHF 2000) will result in a cancellation fee of CHF 300. The only exceptions are fees for '.$this->catname.'s registered after June 1 which have to be paid in full even if cancelled.</p>';
			
		$xhtml.='<p class="text"><b>Single rooms:</b> The indicated fees cover the stay in a double room (mostly with twin beds, but includes some with queen size beds with separate linen). Rooms will be shared with people of the same gender (except otherwise requested) and preferentially from the same delegation. There is an option to book a single room for an extra fee in the <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">Personal Details</a> form.</p>';
			
		$res=$this->readCheaperObserverbedsFromDB($sql);
			
		$xhtml.='<p class="text"><b>Budget rate:</b> In response to several requests we offer a budget rate for '.$this->catname.'s. The fee is CHF '.$this->cheaper_bed_rebate.' cheaper than the current base rate. The lower price for this rate arises because these people will stay in dormitories in the <a href="http://www.youthhostel.ch/en/hostels/berne" target="_blank">Youth Hostel of Bern</a> and will have to get from and to their accommodation on their own (roughly 20 min walking distance with rather steep parts). No drop-off / pick-up will be organized on any day. The number of budget rates is limited and can be booked on a "first-come, first-served" basis. For this option, choose "Jury Budget" from the "Category" drop down menu when registering additional jury members. ';
			
		if($res['available']>1){
			 $xhtml.='There are currently still <b>'.$res['available'].' out of '.$res['num_beds'].' budget slots available</b>.';
		} else {
			if($res['available']==1) $xhtml.='There is currently still <b>one last budget slots available</b>.';
			else {
				$xhtml.='There are unfortunately <b>no budget slots available</b> anymore.';
			}
		}
		$xhtml.='</p>';	
	}
	
	public function writeRegistrationHistory(&$sql, &$xhtml){

		$res=$this->getRegistrationHistoryFromDB($sql);
		
		//only write history if there are entries
		if(count($res)==0) return false;
		else {
			$query='select en from ibo2013_countries where id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); 	
			$cname=$sql->simpleQuery($query);
			$sql->end_transaction(); 			
			$xhtml.='<p class="subtitle">Registration History for '.$cname[0]['en'].'</p>';
			
			//write header
			$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Type</p></td><td><p class="monospacebold">Registered</p></td><td><p class="monospacebold">Status</p></td><td><p class="monospacebold">Cancelled</p></td><td><p class="monospacebold">Fee</p></td></tr>';
			
			$odd=1;
			$num=0;
			$totalFee=0;
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
				//check if cancelled
				if($r['cancellation_date']>$r['registration_date']){
					$status='cancelled';
					$style=' style="color: red; text-decoration: line-through;"';
				} else {
					$style=' style="color: green; font-weight: bold;"';
					$status='active';
				}
					
				$xhtml.='">';
				$xhtml.='<td><p class="monospace"'.$style.'>'.$r['category'].'</p></td>';
				$xhtml.='<td><p class="monospace"'.$style.'>'.date('M d Y H:i', strtotime($r['registration_date'])).'</p></td>';
				$xhtml.='<td><p class="monospace"'.$style.'>'.$status.'</p></td>';
				if($status=='cancelled') $xhtml.='<td><p class="monospace">'.date('M d Y H:i', strtotime($r['cancellation_date'])).'</p></td>';
				else $xhtml.='<td><p class="monospace">-</p></td>';
				
				$xhtml.='<td><p class="monospace">';
				if($r['fee']>0){
					$xhtml.='CHF ';
					if($r['fee']<10000) $xhtml.='&nbsp;';
					if($r['fee']<1000) $xhtml.='&nbsp;';
					if($r['fee']<100) $xhtml.='&nbsp;';
					$xhtml.=$r['fee'];
				} else $xhtml.='-';
				$xhtml.='</p></td></tr>';
				$totalFee+=$r['fee'];
			}
			//add total line
			$xhtml.='<tr class="last"><td><p class="monospacebold">Total</p></td><td></td><td></td><td></td><td><p class="monospacebold">CHF ';
			if($totalFee<10000) $xhtml.='&nbsp;';
			if($totalFee<1000) $xhtml.='&nbsp;';
			$xhtml.=$totalFee.'</p></td></tr>';
			$xhtml.='</table>';
		}
		return true;

	}
	
	
	public function process_observer_registration_form(){
		$frm = $this->getForm('observer_registration_form');

		if(!$frm->validate()){
			$this->process_msg_adding .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		//get fee
		$sql = SqlQuery::getInstance();
		$this->readFeesFromDB($sql);			
		$fee=$this->get_current_fee($sql, $values['category']);			
		
		//if budget, remove one budget bed
		if($values['category']=='Observer Budget'){
			$query='update ibo2013_cheaper_observer_beds set num_beds_booked=num_beds_booked+1';
			$sql->start_transaction(); 
			$sql->simpleQuery($query);
			$sql->end_transaction(); 
		}
		
		//add new registration entry
		$query='insert into ibo2013_observer_registration (country_id, delegation_category_id, user_id, fee) select '.$this->getMyCountryId($sql).', d.id, '.$GLOBALS['user']->id.', '.$fee.' from ibo2013_delegation_categories d where d.category="'.$values['category'].'"';
		$sql->start_transaction(); 
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg_adding .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ObserverRegistrationContent', $query);
		 } else {
			$this->process_msg_adding .= '<p class="success">Thank you for registering!</p>';							 	
		}			
	}
	
	public function process_observer_cancellation_form(){
		$frm = $this->getForm('observer_cancellation_form');

		if(!$frm->validate()){
			$this->process_msg_cancelling .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$this->process_status_cancelling=true;
		$values = $frm->getElementValues();
		

		//get which DB entry to be cancelled
		$sql = SqlQuery::getInstance();
		$res=$this->getRegistrationHistoryFromDB($sql);
		
		$thisobserver=-1;
		$thisfee=99999999999999;
		$thisdate=0;
		$fee=0;
		foreach($res as $id=>$r){
			if($r['category']==$values['category_remove'] && $r['cancellation_date']<$r['registration_date']){
				if(strtotime($r['registration_date'])>strtotime("1 June 2013")) $fee=$r['fee'];
				else $fee=$this->get_current_fee($sql, $r['category'])-$r['fee'];
				if($thisobserver==-1 || $thisfee>$fee || ($thisfee==$fee && $thisdate<$r['registration_date'])){
					 $thisobserver=$id;
					 $thisfee=$fee;
					 $thisdate=$r['registration_date'];		
				}		
			}
		}
		if($thisobserver<0){
			 $this->process_msg_cancelling .= '<p class="error">Cancellation failed! An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','Failed to find observer to be removed in ibo2013ObserverRegistrationContent', 'country_id='.$this->getMyCountryId($sql).'<br/>'.print_r($values, true));
		}
			
		//set appropriate observer registration as cancelled
		$query='update ibo2013_observer_registration set cancellation_date=now(), fee='.$thisfee.' where id='.$res[$thisobserver]['id'];
		echo $query;
		$sql->start_transaction(); 
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg_cancelling .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ObserverRegistrationContent', $query);
		 } else {
			$this->process_msg_cancelling .= '<p class="success">The registration has been successfully cancelled.</p>';							 	
		}			
	}
	
	protected function get_current_fee(&$sql, $type){
		$this->readFeesFromDB($sql);	
		switch($type){
			case 'Visitor': return $this->currentFee - $this->visitor_rebate; break;						
			case 'Observer Budget': return $this->currentFee - $this->cheaper_bed_rebate; break;			
			case 'Observer': return $this->currentFee; break;			
		}		
	}
	
	protected function observer_registration_form($vector){	
		//read from DB
		$sql = SqlQuery::getInstance();
		$this->readFeesFromDB($sql);	

		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(130,400);
	
		$res=$this->readCheaperObserverbedsFromDB($sql);
		$categories=array();
		$categories['Observer']='Observer (CHF '.$this->get_current_fee($sql, 'Jury').')';
		if($res['available']>0) $categories['Observer Budget']='Observer Budget (CHF '.$this->get_current_fee($sql, 'Jury Budget').')';	
	
		$category = new Select('Category:','category',$categories, 'double');
		$newForm->addElement('category',$category);
		
		$condition='I confirm that I\'m entitled to register '.$this->catname.'s for the IBO 2013 for our delegation.<br/> I understand that a <b>participation fee</b> will be due (see above for amount).<br/> I acknowledge that cancelling this registration may result in a <b>cancellation fee</b> as described above.';
		
		$conditions=new CheckboxWithText('Please accept:','accept conditions',true, $condition);
		$conditions->addRestriction(new NotFalseRestriction());
		$newForm->addElement('accept conditions',$conditions);
		
		$newForm->addElement('submit_add_observer', new Submit('submit_add_observer','register'));
		
		return $newForm;	
	}
	
	protected function observer_cancellation_form($vector){	
		//read from DB
		$sql = SqlQuery::getInstance();
		$this->readFeesFromDB($sql);
		$res=$this->getRegistrationHistoryFromDB($sql);

		//fill array of all registered types
		$types=array();
		$fee=0;
		foreach($res as $r){
			if($r['cancellation_date']<$r['registration_date']){
				if(!isset($types[$r['category']])){
					$types[$r['category']]=array('fee'=>99999, 'num'=>1);
				} else 	++$types[$r['category']]['num'];
				if(strtotime($r['registration_date'])>strtotime("1 June 2013")) $fee=$r['fee'];
				else $fee=$this->get_current_fee($sql, $r['category'])-$r['fee'];
				if($types[$r['category']]['fee']>$fee){
					 $types[$r['category']]['fee']=$fee;
				 }
			}
		}			

		//make sure that signed-up people can not be cancelled
		//-> how many have been signed up? = Num per category - aigned up. BUT: The Jury category may have one additional due to the team
		$query='select count(p.id) as num, d.category from ibo2013_participants p, ibo2013_delegation_categories d where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' group by d.id';
		$sql->start_transaction(); 
		$num=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		
		foreach($num as $id=>$n){
			if($n['category']=='Observer Budget' && isset($types['Observer Budget'])) $types['Observer Budget']['num']-=$n['num'];
			if($n['category']=='Observer' && isset($types['Observer'])) $types['Observer Budget']['num']-=$n['num'];
		}
		
		//make list to choose from
		if(isset($types['Observer']) && $types['Observer']['num']>0) $categories['Observer']='Observer (Cancellation fee CHF '.$types['Jury']['fee'].')';
		if(isset($types['Observer Budget']) && $types['Observer Budget']['num']>0) $categories['Observer Budget']='Observer Budget (Cancellation fee CHF '.$types['Jury Budget']['fee'].')';
	
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(130,400);
	
		if(count($categories)>0){
		$category = new Select('Category:','category_remove',$categories, 'base');
		$newForm->addElement('category_remove',$category);
		
		$condition='I confirm that I\'m entitled to cancel a registration.<br/> I understand that a <b>cancellation fee</b> maybe due (see dropdown menu for amount).';
		$conditions=new CheckboxWithText('Please accept:','remove_conditions',true, $condition);
		$conditions->addRestriction(new NotFalseRestriction());
		$newForm->addElement('remove_conditions',$conditions);
		
		$newForm->addElement('submit_remove_observer', new Submit('submit_remove_observer','cancel registration'));
		}
		return $newForm;	
	}
	
	
	

}
?>

