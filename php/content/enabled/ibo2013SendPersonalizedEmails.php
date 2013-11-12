<?php
class ibo2013SendPersonalizedEmails extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $emailstobesent = array();
	
	
	public function display(){
		
		//show process messages
		$xhtml = $this->process_msg;
				
		//show compiled list of emails?
		if(count($this->emailstobesent)>0){
			$xhtml.='<p class="subtitle">Emails to be sent:</p>';
			foreach($this->emailstobesent as $e){
				$xhtml.='<p class="text"><b>TO: '.$e['email'].'</b><br/><b>SUBJECT: '.$e['subject'].'</b><br/>'.$e['text'].'</p>';
			}
		}
		
		//show form
		$xhtml.='<p class="text">The following tags can be used:<ul><li>[NAME]</li><li>[COUNTRY]</li><li>[ALPHA3]</li><li>[PASSWD]</li><li>[NUM_MEMBERS_TOTAL]</li><li>[NUM_MEMBERS_NOT_OK]</li><li>[AMOUNT_DUE]</li><li>[EXAM_LOGIN]</li><li>[EXAM_PASSWORD]</li><li>[B] and [/B] for bold text</li></ul><br/></p>';
		$frm = $this->getForm('send_personalized_emails_form');
		$xhtml .= $frm->getHtml($this->id);

		return $xhtml;
	}
	
		
	public function process_send_personalized_emails_form(){
		$this->processing_status=false;

		$frm = $this->getForm('send_personalized_emails_form');
		$values = $frm->getElementValues();
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		//get recipients and check for replacement bits
		$sql = SqlQuery::getInstance();
		$query=array();
		
		switch($values['recipients']){
			case 'all coordinators': 
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "all coordinators".</p>';
					return false;
				}
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "all coordinators".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}	
				$query[]='select t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, c.initial_passwd as passwd, ce.invitation_pdf from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c where ce.title_id=t.id and ce.country_id=c.id order by c.en asc, ce.name asc';
				break;

			//case 'missed coordinators': $query='select t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, c.initial_passwd as passwd, ce.invitation_pdf from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c where ce.title_id=t.id and ce.country_id=c.id and (ce.id=44 or ce.id=45 or ce.id=5 or ce.id=80 or ce.id=58 or ce.id=73 or ce.id=42 or ce.id=47) order by c.en asc, ce.name asc'; break;
			
			case 'no delegation registration': 
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "no delegation registration".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "no delegation registration".</p>';
					return false;
				}
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, c.initial_passwd as passwd, ce.invitation_pdf from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join ibo2013_team_registration tr on c.id=tr.country_id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and tr.id is null order by c.en asc, ce.name asc';
				break;
			
			case 'not all members confirmed yet':
				//check tags in email
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "not all members confirmed yet".</p>';
					return false;
				}
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}				
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}		
				$query[]='select distinct t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_with_itinerary, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.user_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id left join (select distinct c.id as country_id from ibo2013_participants p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.user_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.country_id=c.id and (p.delegation_category_id=2 or p.delegation_category_id=3 or p.delegation_category_id=4 or p.delegation_category_id=7 or p.delegation_category_id=8 or p.delegation_category_id=9) and x.diff>0) hasjury on hasjury.country_id=c.id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and x.diff>0 and hasjury.country_id is null order by c.en asc, ce.name asc';
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_confirmed, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.user_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.diff>0 group by c.id order by c.en asc, p.last_name asc';
				break; 
				
			case 'not all members have travel itinerary':
				//check tags in email
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "not all members have travel itinerary".</p>';
					return false;
				}			
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}			
				$query[]='select distinct t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_with_itinerary, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.arrival_itinerary_id>0 and p.departure_itinerary_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id left join (select distinct c.id as country_id from ibo2013_participants p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.arrival_itinerary_id>0 and p.departure_itinerary_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.country_id=c.id and (p.delegation_category_id=2 or p.delegation_category_id=3 or p.delegation_category_id=4 or p.delegation_category_id=7 or p.delegation_category_id=8 or p.delegation_category_id=9) and x.diff>0) hasjury on hasjury.country_id=c.id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and x.diff>0 and hasjury.country_id is null order by c.en asc, ce.name asc';
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_confirmed, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where p.arrival_itinerary_id>0 and p.departure_itinerary_id>0 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.diff>0 group by c.id order by c.en asc, p.last_name asc';
				break;
				
			case 'no decleration form (jury + coordinators)':
				//check tags in email
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "no decleration form".</p>';
					return false;
				}			
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				//coordinators where no jury
				$query[]='select distinct t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join (select ifnull(tr.tot,0) as tot_registered, ifnull(tr.tot,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_students as tot, a.country_id from (select num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.declaration_form_name is not null and p.declaration_form_name!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id left join (select distinct c.id as country_id from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c left join (select ifnull(tr.tot,0) as tot_registered, ifnull(tr.tot,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_students as tot, a.country_id from (select num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.declaration_form_name is not null and p.declaration_form_name!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and (p.delegation_category_id=2 or p.delegation_category_id=3 or p.delegation_category_id=4 or p.delegation_category_id=7 or p.delegation_category_id=8 or p.delegation_category_id=9) and x.diff>0 order by c.en asc, p.last_name asc) hasjury on hasjury.country_id=c.id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and x.diff>0 and hasjury.country_id is null order by c.en asc, ce.name asc';
				//jury
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_confirmed, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.declaration_form_name is not null and p.declaration_form_name!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.diff>0 group by c.id order by c.en asc, p.last_name asc';
				break;
				
			case 'no decleration form (students)':
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "no decleration form (students)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "no decleration form (students)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "no decleration form (students)".</p>';
					return false;
				}	
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3 from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c where p.title_id=t.id and p.country_id=c.id and (p.declaration_form_name is null or p.declaration_form_name="") and p.delegation_category_id=1 order by c.en asc, p.last_name asc';
				break;
				
			case 'no teacher photo (jury + coordinators)':
				//check tags in email
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "no decleration form".</p>';
					return false;
				}			
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				//coordinators where no jury
				$query[]='select distinct t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join (select ifnull(tr.tot,0) as tot_registered, ifnull(tr.tot,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_students as tot, a.country_id from (select num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.teacher_photo_basename is not null and p.teacher_photo_basename!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id left join (select distinct c.id as country_id from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c left join (select ifnull(tr.tot,0) as tot_registered, ifnull(tr.tot,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_students as tot, a.country_id from (select num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.teacher_photo_basename is not null and p.teacher_photo_basename!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and (p.delegation_category_id=2 or p.delegation_category_id=3 or p.delegation_category_id=4 or p.delegation_category_id=7 or p.delegation_category_id=8 or p.delegation_category_id=9) and x.diff>0 order by c.en asc, p.last_name asc) hasjury on hasjury.country_id=c.id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and x.diff>0 and hasjury.country_id is null order by c.en asc, ce.name asc';
				//jury
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, x.tot_registered, x.diff as num_not_ok from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.tot,0) + ifnull(o.num,0) as tot_registered, ifnull(tc.num,0) as tot_confirmed, ifnull(tr.tot,0) + ifnull(o.num,0) - ifnull(tc.num,0) as diff, cc.id as country_id from ibo2013_countries cc left join (select a.num_jury+a.num_students as tot, a.country_id from (select num_jury, num_students, country_id, timestamp from ibo2013_team_registration order by country_id asc, timestamp desc) a group by country_id) tr on tr.country_id=cc.id left join (select count(id) as num, country_id from ibo2013_observer_registration where cancellation_date = 0 group by country_id) o on o.country_id=cc.id left join (select count(p.id) as num, p.country_id from ibo2013_participants p where (p.teacher_photo_basename is not null and p.teacher_photo_basename!="" ) and p.delegation_category_id=1 group by p.country_id) tc on tc.country_id=cc.id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.diff>0 group by c.id order by c.en asc, p.last_name asc';
				break;
				
			case 'no teacher photo (students)':
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "no decleration form (students)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "no decleration form (students)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "no decleration form (students)".</p>';
					return false;
				}	
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3 from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c where p.title_id=t.id and p.country_id=c.id and (p.teacher_photo_basename is null or p.teacher_photo_basename="") and p.delegation_category_id=1 order by c.en asc, p.last_name asc';
				break;
				
			case 'participants not having completed personal details (t-shirt)':
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}	
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3 from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c where p.title_id=t.id and p.country_id=c.id and p.tshirt_size="-" order by c.en asc, p.last_name asc';
				break;
				
			case 'participants not having provided a photo':
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "participants not having completed personal details (t-shirt)".</p>';
					return false;
				}	
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3 from ibo2013_titles t, ibo2013_participants p, ibo2013_countries c where p.title_id=t.id and p.country_id=c.id and (p.photo_basename is null or p.photo_basename="") order by c.en asc, p.last_name asc';
				break;				
				
			
			case 'Amount due > 100 CHF':
				//check tags in email
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}			
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_PASSWORD]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_PASSWORD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[EXAM_LOGIN]')!==false){
					$this->process_msg .= '<p class="error">Field [EXAM_LOGIN] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				//coordinators where no jury
				$query[]='select distinct t.title as title, ce.name as name, ce.email as email, c.en as country, c.alpha3 as country_alpha3, x.amount_due as amount_due from ibo2013_titles t, ibo2013_coordinators_email ce, ibo2013_countries c left join (select ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0)-ifnull(p.amount,0) as amount_due, cc.id as country_id from ibo2013_countries cc left join (select sum(fee) as fee, country_id from ibo2013_team_registration group by country_id) tr on tr.country_id=cc.id left join (select sum(fee) as fee, country_id from ibo2013_observer_registration group by country_id) aj on cc.id=aj.country_id left join (select sum(f.fee) as fee, country_id from ibo2013_participants p, ibo2013_fees f where f.type="single_room" and p.single_room=1 group by country_id) sr on cc.id=sr.country_id left join (select sum(amount) as amount, country_id from ibo2013_payment group by country_id) p on cc.id=p.country_id left join (select sum(amount) as amount, country_id from ibo2013_extra_costs group by country_id) e on cc.id=e.country_id) x on x.country_id=c.id left join (select distinct c.id as country_id from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0)-ifnull(p.amount,0) as amount_due, cc.id as country_id from ibo2013_countries cc left join (select sum(fee) as fee, country_id from ibo2013_team_registration group by country_id) tr on tr.country_id=cc.id left join (select sum(fee) as fee, country_id from ibo2013_observer_registration group by country_id) aj on cc.id=aj.country_id left join (select sum(f.fee) as fee, country_id from ibo2013_participants p, ibo2013_fees f where f.type="single_room" and p.single_room=1 group by country_id) sr on cc.id=sr.country_id left join (select sum(amount) as amount, country_id from ibo2013_payment group by country_id) p on cc.id=p.country_id left join (select sum(amount) as amount, country_id from ibo2013_extra_costs group by country_id) e on cc.id=e.country_id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.amount_due>100 group by c.id order by c.en asc, p.last_name asc) hasjury on hasjury.country_id=c.id where ce.title_id=t.id and ce.country_id=c.id and ce.send_reminder=1 and x.amount_due>100 and hasjury.country_id is null order by c.en asc, ce.name asc';				
				
				//jury
				$query[]='select distinct t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, x.amount_due as amount_due from ibo2013_titles t, (select peo.last_name, peo.first_name, peo.title_id, peo.email, peo.country_id, dlc.category from ibo2013_participants peo, ibo2013_delegation_categories dlc where peo.delegation_category_id=dlc.id and (peo.delegation_category_id=2 or peo.delegation_category_id=3 or peo.delegation_category_id=4 or peo.delegation_category_id=7 or peo.delegation_category_id=8 or peo.delegation_category_id=9) order by country_id asc, dlc.order_by asc) p, ibo2013_countries c left join (select ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0)-ifnull(p.amount,0) as amount_due, cc.id as country_id from ibo2013_countries cc left join (select sum(fee) as fee, country_id from ibo2013_team_registration group by country_id) tr on tr.country_id=cc.id left join (select sum(fee) as fee, country_id from ibo2013_observer_registration group by country_id) aj on cc.id=aj.country_id left join (select sum(f.fee) as fee, country_id from ibo2013_participants p, ibo2013_fees f where f.type="single_room" and p.single_room=1 group by country_id) sr on cc.id=sr.country_id left join (select sum(amount) as amount, country_id from ibo2013_payment group by country_id) p on cc.id=p.country_id left join (select sum(amount) as amount, country_id from ibo2013_extra_costs group by country_id) e on cc.id=e.country_id) x on x.country_id=c.id where p.title_id=t.id and p.country_id=c.id and x.amount_due>100 group by c.id order by c.en asc, p.last_name asc';
				break;

			case 'all team leaders': 
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, pl.username as exam_login, pl.password as exam_password from ibo2013_titles t, ibo2013_countries c, ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_participants_login pl where p.id=pl.id and p.title_id=t.id and p.country_id=c.id and d.id=p.delegation_category_id and d.category="Team Leader" and c.id!=72 order by c.en asc, p.last_name asc';

			

				//Add steering commitee member for all that do not have a team leader
				//$query[]='select t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3 from ibo2013_titles t, ibo2013_delegation_categories d, ibo2013_participants p left join (select co.* from ibo2013_countries co left join (select * from ibo2013_participants where delegation_category_id=2) po on po.country_id=co.id where po.id is null and co.id!=72) c on c.id=p.country_id where p.title_id=t.id and p.country_id=c.id and d.id=p.delegation_category_id and d.group="Steering Comitee" order by c.en asc, p.last_name asc';

				$query[]='select t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, c.exam_login, c.exam_password from ibo2013_titles t, ibo2013_delegation_categories d, ibo2013_participants p left join (select co.*, po.exam_login, po.exam_password from ibo2013_countries co left join (select p.*, pl.username as exam_login, pl.password as exam_password from ibo2013_participants p, ibo2013_participants_login pl where p.id=pl.id and p.delegation_category_id=2) po on po.country_id=co.id where po.id is null and co.id!=72) c on c.id=p.country_id where p.title_id=t.id and p.country_id=c.id and d.id=p.delegation_category_id and d.group="Steering Comitee" order by c.en asc, p.last_name asc';
				
				break;

			case 'all students': 
				//check tags in email
				if(strpos($values['email text'], '[NUM_MEMBERS_TOTAL]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_TOTAL] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[NUM_MEMBERS_NOT_OK]')!==false){
					$this->process_msg .= '<p class="error">Field [NUM_MEMBERS_NOT_OK] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[AMOUNT_DUE]')!==false){
					$this->process_msg .= '<p class="error">Field [AMOUNT_DUE] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				if(strpos($values['email text'], '[PASSWD]')!==false){
					$this->process_msg .= '<p class="error">Field [PASSWD] is not available for "'.$values['recipients'].'".</p>';
					return false;
				}
				$query[]='select t.title as title, concat(p.first_name, " ", p.last_name) as name, p.email as email, c.en as country, c.alpha3 as country_alpha3, pl.username as exam_login, pl.password as exam_password from ibo2013_titles t, ibo2013_countries c, ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_participants_login pl where p.id=pl.id and p.title_id=t.id and p.country_id=c.id and d.id=p.delegation_category_id and d.category="Student" and c.id!=72 order by c.en asc, p.last_name asc';
	
				break;

			default: return false; break;
		}
		
		$res=array();
		foreach($query as $i=>$q){
			$sql->start_transaction(); 
			$res[$i]=$sql->simpleQuery($query[$i]);
			$ok = $sql->end_transaction(); 
		}
		//if multiple queries, fuse into one		
		//Note: duplicate emails are not added		
		for($i=1; $i<count($res); ++$i){
			//All columns have to match!!!				
			if(array_diff_key($res[0][0], $res[$i][0])){
				$this->process_msg .= '<p class="error">Arrays have different keys!</p>';
				return false;
			}			
			foreach($res[$i] as $r){
				//Skip duplicate entries on email field
				$email_exists=false;
				foreach($res[0] as $x){
					if($x['email']==$r['email']){
						$email_exists=true;
						break;
					}
				}
				if(!$email_exists){
					$res[0][]=$r;
				}
			}			
		}						
		
		//apply limit
		if($values['limit']!='all')
			$res[0]=array_slice($res[0], 0, $values['limit']);
		
		//attachment?
		switch($values['attachment']){
			case 'invitation letter': if($values['recipients'] != 'all coordinators' && $values['recipients']!='no delegation registration'){
										$this->process_msg .= '<p class="error">Invitation letters can only be sent to "all coordinators"!</p>';
										return false; break;
								      }
									 break;
			default: break;
		}

		//prepare text for emails
		$values['email text']=str_replace("\n", '<br/>', $values['email text']);
		
		//go through all recipients		
		foreach($res[0] as $e){			
				$text=$values['email text'];
				//replace [NAME] tag
				$name=trim($e['name']);
				if($e['title']!="") $name=$e['title'].' '.$name; 
				$text=str_replace('[NAME]', $name, $text);
				//replace [COUNTRY] tag
				$text=str_replace('[COUNTRY]', trim($e['country']), $text);
				//replace [ALPHA3] tag
				$text=str_replace('[ALPHA3]', trim($e['country_alpha3']), $text);
				//replace [PASSWD] tag
				$text=str_replace('[PASSWD]', trim($e['passwd']), $text);
				//replace NUM MEMBERS tags
				$text=str_replace('[NUM_MEMBERS_TOTAL]', trim($e['tot_registered']), $text);
				$text=str_replace('[NUM_MEMBERS_NOT_OK]', trim($e['num_not_ok']), $text);
				//replace amount due tag
				$text=str_replace('[AMOUNT_DUE]', trim($e['amount_due']), $text);
				//replace translation login credentials
				$text=str_replace('[EXAM_LOGIN]', trim($e['exam_login']), $text);
				$text=str_replace('[EXAM_PASSWORD]', trim($e['exam_password']), $text);
				
				//make bold
				$text=str_replace('[B]', '<b>', $text);
				$text=str_replace('[/B]', '</b>', $text);
				//add attachment
				switch($values['attachment']){
					case 'invitation letter': $attachment='../html/webcontent/downloads/invitation_letter/'.$e['invitation_pdf'];
											  $attachname='IBO2013_invitation_letter.pdf';
											  break;
					default: $attachment=''; $attachname=''; break;
				}				
				//send
				switch($values['send']){
					case 'print': $this->emailstobesent[]=array('email'=>$e['email'], 'subject'=>$values['subject'], 'text'=>$text); break;
					case 'send_info': sendSBOmail('info@ibo2013.org',$values['subject'], $text, $attachment, $attachname);	break;			
					case 'send_dan': sendSBOmail('daniel.wegmann@olympiads.unibe.ch',$values['subject'], $text, $attachment, $attachname);	break;
					case 'send_irene': sendSBOmail('irene.steinegger@olympiads.unibe.ch',$values['subject'], $text, $attachment, $attachname);	break;
					case 'send_all': sendSBOmail($e['email'], $values['subject'], $text, $attachment, $attachname);	sleep(2); break;
				}				
		}
	
		$this->process_msg .= '<p class="error">Sent a total of '.count($res[0]).' emails!</p>';
	}
	
	protected function send_personalized_emails_form($vector){	
		//read missing members from DB
		$sql = SqlQuery::getInstance();	
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(130,400);
	
		$recipients=array();
		$recipients['all coordinators']='all coordinators';
		$recipients['all team leaders']='all team leaders';
		$recipients['all students']='all students';
		//$recipients['missed coordinators']='missed coordinators';
		$recipients['no delegation registration']='no delegation registration';
		$recipients['not all members confirmed yet']='not all members confirmed yet';
		$recipients['not all members have travel itinerary']='not all members have travel itinerary';
		$recipients['no decleration form (jury + coordinators)']='no decleration form (jury + coordinators)';
		$recipients['no decleration form (students)']='no decleration form (students)';
		$recipients['no teacher photo (jury + coordinators)']='no teacher photo (jury + coordinators)';
		$recipients['no teacher photo (students)']='no dteacher photo (students)';		
		$recipients['participants not having completed personal details (t-shirt)']='participants not having completed personal details (t-shirt)';
		$recipients['participants not having provided a photo']='participants not having provided a photo';
		$recipients['Amount due > 100 CHF']='Amount due > 100 CHF';
		
		
		$recipientselect = new Select('Recipients:','recipients',$recipients, 'double');
		$newForm->addElement('recipients',$recipientselect);
		
		$attachments=array();
		$attachments['none']='-';
		$attachments['invitation letter']='invitation letter';
		$attachselect = new Select('Attachment:','attachment',$attachments, 'double');
		$newForm->addElement('attachment',$attachselect);
				
		$subject = new Input('Subject','text','subject','',array('size'=>35));
		$subject->addRestriction(new StrlenRestriction(1,200));
		$newForm->addElement('subject',$subject);
		
		$text = new Textarea('Email text','email text','',20,45);		
		$text->addRestriction(new StrlenRestriction(1,99999));
		$newForm->addElement('email text',$text);
		
		$limitcat = array(1=>"Limit to first", 5=>"Limit to first 5", 10=>"Limit to first 10", 'all'=>"Use all entries");
		$limit = new Select('Limit:','limit',$limitcat, 'base');
		$newForm->addElement('limit',$limit);
		
		$sendcat = array('print'=>"Do not send, just print to screen", 'send_info'=>"Send to info@ibo2013.org", 'send_dan'=>"Send to daniel.wegmann@olympiads.unibe.ch", 'send_irene'=>"Send to irene.steinegger@olympiads.unibe.ch", 'send_all'=>'send to recipients');
		$send =	new Select('Send:','send',$sendcat, 'base');
		$newForm->addElement('send',$send);		

		
		$newForm->addElement('submitit', new Submit('submitit','submit'));
		
		return $newForm;	
	}
	
}
?>

