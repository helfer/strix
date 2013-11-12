<?php
class ibo2013TeamRegistrationContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $userCountryId = -1;
	
	protected $current_fee_entry = -1;
	protected $fees = array();
	protected $penalty_fee = 100;
	protected $history = array();
	
	
	public function display(){
		$xhtml = $this->process_msg;
		if($this->process_status) $form=$this->remakeForm('team_registration_form');		
		else $form=$this->getForm('team_registration_form');		
		$sql = SqlQuery::getInstance();
		
		$xhtml.='<p class="title">Registering your Delegation</p>';
		
		$xhtml.='<p class="text">The first step towards participating in the IBO 2013 is the registration of your delegation. In order to ensure the participation of every interested country, we are determined to organize the IBO 2013 in Switzerland at a low delegation fee. Therefore, we need to make many arrangements as early as possible. We are thus only able to provide our <b>lowest fees to those registering early</b>. In order to register at a low fee, it is sufficient to provide the number of people from your delegation participating in the IBO 2013. <b>Names</b> and further information of all members of your delegation will be <b>needed by May 25</b>, 2013 latest. Please inform us as soon  as possible should you not be able to deliver personal details  on the given date.</p>';
		
		$this->writeTeamFees($sql, $xhtml);
		
		//show registration history, if it exists
		if($this->writeRegistrationHistory($sql, $xhtml)){
			$xhtml.='<p class="subtitle">Altering Registration</p>';
			$fee=$this->getPenaltyFee($last_entry_that_matters);
			if($fee>0) $xhtml.='<p class="text"><b>NOTE:</b> Changing the number of registered participants at this time is subjected to an <b>alteration fee of CHF '.$fee.' per person</b>. See above for more explanations</p>';
		} else {
			$xhtml.='<p class="subtitle">Initial Registration</p>';
			$xhtml.='<p class="text"><b>NOTE:</b> Altering the number of participants is possible at all times. However, changing the number of registered participants may be subjected to an <b>alteration fee</b> (see above).</p>';
$xhtml.='<p class="text">Please indicate the number of jury members and students you would like to register for the IBO 2013. Should you like to register more jury members, please add them in the <a href="http://www.ibo2013.org/Participate/registration/AdditionalJuryMember/">Additional Jury</a> menu after you have registered your delegation.</p>';
		}		
		
		//show registration form
		$xhtml .= $form->getHtml($this->id);
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
		$query='select * from ibo2013_fees where type="team" order by last_date_timestamp asc';		
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$sql->end_transaction(); 
		$this->fees=array();
		$last_timediff=999999999;
		foreach($res as $id=>$r){
			$timediff=(strtotime($r['last_date_timestamp'])-time());
			if($timediff>0 && $timediff<$last_timediff){
				$last_timediff=$timediff;
				$this->current_fee_entry=$id;
			}
			$this->fees[]=array('id'=>$r['id'], 'last_date_timestamp'=>$r['last_date_timestamp'], 'fee'=>$r['fee'], 'alteration_fee'=>$r['alteration_fee'], 'time_diff'=>$timediff);
		}
	}
	
	public function getRegistrationHistoryFromDB(&$sql){
		$query='select tr.*, u.first_name, u.last_name, f.last_date_timestamp from ibo2013_team_registration tr, ibo2013_fees f, user u where tr.country_id='.$this->getMyCountryId($sql).' and tr.user_id=u.id and tr.fee_id=f.id order by tr.timestamp asc';		
		$sql->start_transaction(); 	
		$res=$sql->simpleQuery($query);
		$sql->end_transaction(); 
		$this->history=$res;
	}
	
	public function writeTeamFees(&$sql, &$xhtml){
		$this->readFeesFromDB($sql);
		
		$xhtml.='<p class="subtitle">Participation Fees for Delegations</p>';
		$xhtml.='<p class="text">The participation fee covers food, accommodation, transportation and excursions during the IBO 2013, starting on the evening (including dinner) of July 14, 2013, ending with breakfast on July 21, 2013. The indicated fees enable the participation of one delegation consisting of <b>up to 4 students and 2 jury members</b>. In order to register additional jury members, click <a href="http://www.ibo2013.org/Participate/registration/AdditionalJuryMember/?lan=en">here</a>. The fees are valid if the delegation is registered before or on the date specified in the table below. The relevant time zone is the Central European Time (CET).</p>';
		
		$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Valid Until</p></td><td colspan="2"><p class="monospacebold">Participation Fee</p></td><td colspan="2"><p class="monospacebold">Alteration Fee (per Person)</p></td></tr>';
		
		$num=0;
		$current_found=false;
		$odd=1;
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
				} else {
					if(!$current_found){						
						$style=' style="font-weight: bold;"';
						$current_found=true;
					} else {
						$style='';
						$closes_in='';			
					}
				}
				//fee tier
				$xhtml.='">';
				//valid until
				$xhtml.='<td><p class="monospace"'.$style.'>'.date('M d Y', strtotime($f['last_date_timestamp'])).'</p></td>';
				//registration fee
				$xhtml.='<td width="10"><p class="monospace"'.$style.'>CHF</p></td><td><p class="monospace"'.$style.'>';
				if($f['fee']<1000) $xhtml.='&nbsp';
				$xhtml.=$f['fee'].'</p></td>';
				//alteration fee
				$xhtml.='<td width="10"><p class="monospace"'.$style.'>CHF</p></td><td><p class="monospace"'.$style.'>';
				$xhtml.=$f['alteration_fee'].'</p></td></tr>';
			}
			
			$xhtml.='</table>';	
			
			
			$xhtml.='<p class="text"><b>Currency:</b> All fees are to be paid in Swiss Francs (CHF). Current conversion rates as provided by the Google API are 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'EUR').' or 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'USD').'. These rates are provided without any warranty.</p>';
				
			$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>Alteration Fees:</b> While your registration can be altered at any time, a fee may be due. Additional fees are charged at the end of each fee tier for each person added or subtracted in comparison to the numbers provided at the end of the previous fee tier. The amount charged depends on the fee tier as indicated in the table above. Note that only the last update within a fee tier is considered.</br>
<b>Example:</b> Registering 2 jury members and 3 students in January and then changing to 1 jury member and 4 students in May will lead to an additional fee of CHF 300, CHF 150 for cancelling a jury member and CHF 150 for bringing an additional student.</p>';

			$xhtml.='<p class="text"><b>Single Rooms:</b> For jury members, the participation fee covers the stay in a double room (mostly with twin beds, but includes some with queen size beds with separate linen). Rooms will be shared with people of the same gender (except otherwise requested) and preferentially from the same delegation. There is an option to book a single room for an extra fee in the <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">Personal Details</a> form once the team members have been <a href="http://www.ibo2013.org/Participate/registration/provide_names/">confirmed</a>.</p>';
			
	}
	
	public function writeRegistrationHistory(&$sql, &$xhtml){
		$this->getRegistrationHistoryFromDB($sql);
		
		//only write history if there are entries
		if(count($this->history)==0) return false;
		else {
			$query='select en from ibo2013_countries where id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); 	
			$cname=$sql->simpleQuery($query);
			$sql->end_transaction(); 			
			$xhtml.='<p class="subtitle">Registration history for '.$cname[0]['en'].'</p>';
			$xhtml.='<p>Thank you for registering your delegation! We have currently the following registration on record:</p>';
			
			//write header
			$xhtml.='<table class="standard"><tr class="header"><td></td><td><p class="monospacebold">When</p></td><td><p class="monospacebold">Jury</p></td><td><p class="monospacebold">Students</p></td><td><p class="monospacebold">Fee</p></td></tr>';
			
			$type='Registration';
			$odd=1;
			$num=0;
			$totalFee=0;
			foreach($this->history as $r){
				++$num;
				$xhtml.='<tr class="';
				if($num==count($res)){
					$xhtml.='last';
				} else {
					if($odd==1) $xhtml.='odd';
					else $xhtml.='even';
				$odd=1-$odd;
				}
				$xhtml.='"><td><p class="monospace">'.$type.'</p></td><td><p class="monospace">'.date('d M Y H:i', strtotime($r['timestamp'])).'</p></td><td><p class="monospace">'.$r['num_jury'].'</p></td><td><p class="monospace">'.$r['num_students'].'</p></td><td><p class="monospace">';
				if($r['fee']>0){
					$xhtml.='CHF ';
					if($r['fee']<1000) $xhtml.='&nbsp;';
					if($r['fee']<100) $xhtml.='&nbsp;';
					$xhtml.=$r['fee'];
				} else $xhtml.='-';
				$xhtml.='</p></td></tr>';
				$type='Update';
				$totalFee+=$r['fee'];
			}
			//add total line
			$xhtml.='<tr class="last"><td class="bordertop"><p class="monospacebold">Total</p></td><td class="bordertop"></td><td class="bordertop"></td><td class="bordertop"></td><td class="bordertop"><p class="monospacebold">CHF ';
			if($totalFee<1000) $xhtml.='&nbsp;';
			if($totalFee<100) $xhtml.='&nbsp;';
			$xhtml.=$totalFee.'</p></td></tr>';
			$xhtml.='</table>';
		}
		return true;
	}
	
	public function getPenaltyFee(& $last_entry_that_matters){
		//find last entry that matters for calculating penalty fee 
		$last_timestamp=strtotime($this->history[0]['timestamp'])-1;
		$last_entry_that_matters=-1;
		foreach($this->history as $id=>$r){
			if($r['last_date_timestamp']<$this->fees[$this->current_fee_entry]['last_date_timestamp'] && strtotime($r['timestamp'])>$last_timestamp){
				$last_timestamp=strtotime($r['timestamp']);
				$last_entry_that_matters=$id;
			}
		}	

		if($last_entry_that_matters<0) return 0;
		else return $this->fees[$this->current_fee_entry]['alteration_fee'];
	}
	
	public function process_team_registration_form(){
		$frm = $this->getForm('team_registration_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		//check if the new values differ from the lastest values in DB
		$sql = SqlQuery::getInstance();
		$this->getRegistrationHistoryFromDB($sql);
		$is_initial=false;
		$fee=$this->penalty_fee;
		if(count($this->history)>0){
			//check if it is an update
			if($values['num_jury']==$this->history[count($this->history)-1]['num_jury'] && $values['num_students']==$this->history[count($this->history)-1]['num_students']){
				$this->process_msg .= '<p class="error">Update does not imply any change!</p>';
				return false;
			}
			
			//If reduction, why not remove additional jury member?
			if($values['num_jury']<$this->history[count($this->history)-1]['num_jury']){
				$query='select count(o.id) as num from ibo2013_observer_registration o, ibo2013_delegation_categories d where o.delegation_category_id=d.id and (d.category="Jury" or d.category="Jury Budget") and o.country_id='.$this->getMyCountryId($sql).' and o.cancellation_date<1900';
				$sql->start_transaction(); 
				$tmp=$sql->simpleQuery($query);
				$ok = $sql->end_transaction(); 
				if($tmp[0]['num']>0){
					$this->process_msg .= '<p class="error">It is cheaper for you to cancle an additional jury memeber instead!</p>';
					return false;
				}
			}
			
			//People already signed up? Make sure they cannot be cancelled!
			$query='select count(p.id) as num, d.category from ibo2013_participants p, ibo2013_delegation_categories d where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' group by d.id';
			$sql->start_transaction(); 
			$tmp=$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			$num=array();
			foreach($tmp as $t){
				$num[$t['category']]=$t['num'];
			}
			//too many signed up students?
			if($values['num_students']<$num['Student']){
				$this->process_msg .= '<p class="error">You have already signed up too many students to reduce the number of students to '.$values['num_students'].'</p>';
				return false;
			};
			//Too many signed up Jury? -> more complicated, also check additional jury members
			$query='select count(o.id) as num from ibo2013_observer_registration o, ibo2013_delegation_categories d where o.delegation_category_id=d.id and d.category="Jury" and o.country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); 
			$numjury=$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			//print_r($num);
			//print_r($numjury);
			//echo "RESULTS IN A TOTAL OF ".($num['Jury']+$num['Team Leader']+$num['Jury Budget']-$numjury[0]['num'])." <  or > ".$values['num_jury']."<br/>";
			if($values['num_jury']<($num['Jury']+$num['Team Leader']+$num['Jury Budget']-$numjury[0]['num'])){
				$this->process_msg .= '<p class="error">You have already signed up too many jury members to reduce the number of jury members to '.$values['num_jury'].'</p>';
				return false;
			};
			
			//set fee to zero for all updates in current tier
			$query='update ibo2013_team_registration set fee=0 where fee_id='.$this->fees[$this->current_fee_entry]['id'].' and type="update" and country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok=$sql->end_transaction();
			if(!$ok){
				$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamRegistrationContent', $query);
				return false;
			}
			$last_entry_that_matters=0;
			$fee=$this->getPenaltyFee($last_entry_that_matters);
			
			//how many differences?
			$diff=abs($values['num_jury'] - $this->history[$last_entry_that_matters]['num_jury']) + abs($values['num_students'] - $this->history[$last_entry_that_matters]['num_students']);
			$fee=$diff * $fee;
			
			
			$query='insert into ibo2013_team_registration (country_id, user_id, type, num_jury, num_students, fee, fee_id) values ('.$this->getMyCountryId($sql).','.$GLOBALS['user']->id.', "update", "'.$values['num_jury'].'", "'.$values['num_students'].'", '.$fee.', '.$this->fees[$this->current_fee_entry]['id'].')';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamRegistrationContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Registration has been successfully updated.</p>';						
			}
		} else {
			 //New entry
			 $this->readFeesFromDB($sql);
			 $fee=$this->fees[$this->current_fee_entry]['fee'];
			 $query='insert into ibo2013_team_registration (country_id, user_id, type, num_jury, num_students, fee, fee_id) values ('.$this->getMyCountryId($sql).','.$GLOBALS['user']->id.', "initial", "'.$values['num_jury'].'", "'.$values['num_students'].'", '.$fee.', '.$this->fees[$this->current_fee_entry]['id'].')';
			 $sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			 if(!$ok){
				$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamRegistrationContent', $query);
			 } else {
				$this->process_msg .= '<p class="success">Thank you for registering!</p>';				
			 } 
		 }
	}
	
	protected function team_registration_form($vector){	
		//read from DB
		$sql = SqlQuery::getInstance(); 
		$this->readFeesFromDB($sql);
		$this->getRegistrationHistoryFromDB($sql);
		
		//get most recent update or fill in default values
		if(count($this->history)==0){
			$res['num_students']=0;
			$res['num_jury']=0;
			$condition='I confirm that I\'m entitled to register our delegation.<br/> I understand that a <b>participation fee of CHF '.$this->fees[$this->current_fee_entry]['fee'].'</b> will be due.';
		} else {
			$res=$this->history[count($this->history)-1];
			$condition='I confirm that I\'m entitled to modify the registration of our delegation.';
			$fee=$this->getPenaltyFee($last_entry_that_matters);
			if($fee>0) $condition.='<br/> I understand that an <span class="problem">alteration fee of CHF '.$fee.' per person</span> will be due (see above).';			
		}	

		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(130,400);
	
		//get num Jury form DB
		$query='select jury_member_allowence from ibo2013_countries where id='.$this->getMyCountryId($sql);
		$sql->start_transaction(); 
		$numjury=$sql->simpleQuery($query);
		$numjury=$numjury[0]['jury_member_allowence'];
		$ok = $sql->end_transaction();
		$sel=array();
		for($i=1; $i<=$numjury;++$i){ $sel[$i]=$i;}
		if($numjury>2){
			$jurytag='Jury <span class="problem">*</span>:';
		} else {
			$jurytag='Jury:';
		}
	
		$numStudents = new Select($jurytag,'num_jury',$sel,$res['num_jury']);
		$newForm->addElement('num_jury',$numStudents);
		
		$numStudents = new Select('Students:','num_students',array(1=>1,2=>2,3=>3,4=>4),$res['num_students']);
		$newForm->addElement('num_students',$numStudents);
	
		
		$conditions=new CheckboxWithText('Please accept:','accept conditions',true,$condition);
		$conditions->addRestriction(new NotFalseRestriction());
		$newForm->addElement('accept conditions',$conditions);
		
		$newForm->addElement('submit_team_registration', new Submit('submit_email','save'));
		
		//explanation for more jury
		if($numjury>2){
			$adds='';
			if($numjury>3) $adds='s';
			$newForm->addElement('explainjury', new HtmlElement('<p class="problem"><br/><br/>* Your delegation is allowed to bring '.($numjury-2).' extra jury member'.$adds.' included in the participation fee.</p>'));
		}
		
		
		return $newForm;	
	}

}
?>

