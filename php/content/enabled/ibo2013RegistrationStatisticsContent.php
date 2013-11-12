<?php
class ibo2013RegistrationStatisticsContent extends Content {

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $filedir = 'payment_files/';
	
	
	public function display(){
		$sql = SqlQuery::getInstance();		
				
		$xhtml = '<p class="title">Registration Statistics</p>';
		
		//get for all countries as one big sql
		$query='select c.en, c.alpha3, c.id, ifnull(tr.num_students,0)+ifnull(tr.num_jury,0)+ifnull(naj.num_add_jury,0)+ifnull(najb.num_add_jury_budget,0)+ifnull(nv.num_visitors,0)+ifnull(no.num_obs,0)+ifnull(nob.num_obs_budget,0) as tot_pers, 		
		ifnull(tr.num_students,0) as num_students, ifnull(tr.num_jury,0)+ifnull(naj.num_add_jury,0)+ifnull(najb.num_add_jury_budget,0) as tot_jury, ifnull(tr.num_jury,0), ifnull(naj.num_add_jury,0), ifnull(najb.num_add_jury_budget,0), ifnull(nv.num_visitors,0), ifnull(no.num_obs,0)+ifnull(nob.num_obs_budget,0) as tot_obs, ifnull(no.num_obs,0), ifnull(nob.num_obs_budget,0), ifnull(of.tot_fee,0)+ifnull(trf.tot_fee,0) as tot_fee, ifnull(pay.tot_payed,0), ifnull(of.tot_fee,0)+ifnull(trf.tot_fee,0)-ifnull(pay.tot_payed,0) as amount_due, ifnull(pay.last_payment, "-")		
		from ibo2013_countries c 
		left join (select ifnull(sum(fee), 0) as tot_fee, country_id from ibo2013_observer_registration group by country_id) of on c.id=of.country_id
		left join (select sum(fee) as tot_fee, country_id from ibo2013_team_registration group by country_id) trf on trf.country_id=c.id
		left join (select ttrr.num_jury, ttrr.num_students, ttrr.country_id from ibo2013_team_registration ttrr where timestamp=(select max(timestamp) from ibo2013_team_registration where country_id=ttrr.country_id)) tr on tr.country_id=c.id
		left join (select sum(p.amount) as tot_payed, max(date) as last_payment, country_id from ibo2013_payment p group by p.country_id) pay on c.id=pay.country_id 		
		left join (select count(id) as num_add_jury, country_id from ibo2013_observer_registration where delegation_category_id=3 and cancellation_date < "1900-01-01" group by country_id) naj on naj.country_id=c.id
		left join (select count(id) as num_add_jury_budget, country_id from ibo2013_observer_registration where delegation_category_id=4 and cancellation_date < "1900-01-01" group by country_id) najb on najb.country_id=c.id
		left join (select count(id) as num_visitors, country_id from ibo2013_observer_registration where delegation_category_id=5 and cancellation_date < "1900-01-01" group by country_id) nv on nv.country_id=c.id
		left join (select count(id) as num_obs, country_id from ibo2013_observer_registration where delegation_category_id=8 or delegation_category_id=9 group by country_id) no on no.country_id=c.id
		left join (select count(id) as num_obs_budget, country_id from ibo2013_observer_registration where delegation_category_id=10 and cancellation_date < "1900-01-01" group by country_id) nob on nob.country_id=c.id	
		where c.continent_id>0
		order by c.en asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
				
		//parse into CSV and store stats
		$file=fopen(HTML_DIR.'webcontent/downloads/registration_statistics.txt', 'w');		
		fwrite($file, "Delegation;Alpha3;ID;Total;Students;Total Jury;Jury;Additional Jury; Jury Budget;Visitors;Total Observer;Observer;Observer Budget;Total Fee;Amount Payed;Amount Due;Last Payment\n");
		$stats=array('del'=>0, 'tot_pers'=>0, 'num_students'=>0, 'tot_jury'=>0, 'tot_obs'=>0, 'ifnull(nv.num_visitors,0)'=>0, 'ifnull(najb.num_add_jury_budget,0)'=>0, 'ifnull(nob.num_obs_budget,0)'=>0);				
		foreach($res as $r){
			fwrite($file, implode(';',$r)."\n");			
			if($r['tot_pers']>0){
				 ++$stats['del'];				 
			 }			 
			foreach($r as $k=>$v){								
				if(isset($stats[$k]) && is_numeric($v)) $stats[$k]+=$v;
			}			
		}		
		fclose($file);
		
		$xhtml.='<p class="text">Download per country statistics <a href="http://www.ibo2013.org/webcontent/downloads/registration_statistics.txt">here</a>.</p>';
						
		//Show Overview Statistics
		$xhtml.='<p class="subtitle">Some Overview Stats</p>';
		$xhtml.='<table><tr><td width="220">Registered Delegations:</td><td>'.$stats['del'].'</td></tr>';
		$xhtml.='<tr><td>Registered People in Total:</td><td>'.$stats['tot_pers'].'</td></tr>';
		$xhtml.='<tr><td>Registered Students:</td><td>'.$stats['num_students'].'</td></tr>';
		$xhtml.='<tr><td>Registered Jury:</td><td>'.$stats['tot_jury'].' (of which '.$stats['ifnull(najb.num_add_jury_budget,0)'].' as budget)</td></tr>';
		$xhtml.='<tr><td>Registered Visitors:</td><td>'.$stats['ifnull(nv.num_visitors,0)'].'</td></tr>';
		$xhtml.='<tr><td>Registered Observers:</td><td>'.$stats['tot_obs'].' (of which '.$stats['ifnull(nob.num_obs_budget,0)'].' as budget)</td></tr>';
		$xhtml.='<tr><td>Mean People / Delegation</td><td>'.$stats['tot_pers']/$stats['del'].'</td></tr>';
		$xhtml.='<tr><td>Mean Jury / Delegation</td><td>'.$stats['tot_jury']/$stats['del'].'</td></tr>';
		$xhtml.='<tr><td>Mean Students / Delegation</td><td>'.$stats['num_students']/$stats['del'].'</td></tr>';
		$xhtml.='</table>';
		
		//show table of registration progress						
		$query="select dcsub.class, ifnull(t.num,0) as tot_signedup, ifnull(u.num,0) as tot_confirmed, ifnull(v.num,0) as tot_completed, ifnull(w.num, 0) as tot_halal, ifnull(x.num, 0) as tot_fasting, ifnull(y.num, 0) as tot_singleroom, ifnull(z.num, 0) as tot_itinerary from (select distinct class from ibo2013_delegation_categories) dcsub
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id group by dc.class) t on dcsub.class=t.class
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 group by dc.class) u on dcsub.class=u.class
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 and p.tshirt_size != '-' group by dc.class) v on dcsub.class=v.class
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 and p.vegi_halal=1 group by dc.class) w on dcsub.class=w.class
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 and p.fasting_from is not null and p.fasting_from!='' group by dc.class) x on dcsub.class=x.class
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 and p.arrival_itinerary_id>0 group by dc.class) z on dcsub.class=z.class		
		left join (select count(p.id) as num, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.delegation_category_id=dc.id and p.user_id>0 and p.single_room=1 group by dc.class) y on dcsub.class=y.class order by dcsub.class asc";				
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$xhtml.='<p class="subtitle">Registration Progress (with % / expected)</p>';
		$xhtml.='<table class="standard"><tr class="header"><td><b>Class</b></td><td><b>Total</b></td><td colspan="2"><b>Signed Up</b></td><td colspan="2"><b>Confirmed</b></td><td colspan="2"><b>tshirt ok</b></td><td colspan="2"><b>itinerary</b></td><td colspan="2"><b>vegi/halal</b></td><td colspan="2"><b>Fasting</b></td><td colspan="2"><b>Single room</b></td></tr>';
		$i=0;
		foreach($res as $r){
			++$i;
			if($i==count($res)) $xhtml.='<tr class="last">';			
			else $xhtml.='<tr class="even">';			
			//save counts for tshirt prediction
			$tot_tshirt[$r['class']]=$r['tot_completed'];
			switch($r['class']){
				case 'Student': $xhtml.=$this->regProgressRow($r['class'], $stats['num_students'], $r); $tot_registered[$r['class']]=$stats['num_students']; break;
				case 'Jury': $xhtml.=$this->regProgressRow($r['class'], $stats['tot_jury'], $r); $tot_registered[$r['class']]=$stats['tot_jury']; break;
				case 'Organizer': $xhtml.=$this->regProgressRow($r['class'], $r['tot_signedup'], $r); $tot_registered[$r['class']]=$r['tot_signedup']; break;
				case 'Volunteer': $xhtml.=$this->regProgressRow($r['class'], $r['tot_signedup'], $r); $tot_registered[$r['class']]=$r['tot_signedup']; break;
			}						
		}
		$xhtml.='</table>';
		
		//show tshirt sizes		
		$query="select distinct tshirt_size from ibo2013_participants where tshirt_size!='-' order by tshirt_size asc";
		$sql->start_transaction(); $ts=$sql->simpleQuery($query); $sql->end_transaction();
		$xhtml.='<p class="subtitle">T-shirt sizes (M/F)</p>';
		$header='<table class="standard"><tr class="header"><td><b>Class</b></td>';
		foreach($ts as $r){
			$header.='<td width="70"><b>'.$r['tshirt_size'].'</b></td>';
		}
		$header.='<td><b>Total (M/F)</b></td></tr><tr>';
		$xhtml.=$header;
		
		$query="select ts.class, ts.tshirt_size, ts.sex, ifnull(pp.num,0) as num from (select distinct p.tshirt_size, p.sex, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where p.tshirt_size!='-' order by dc.class asc, p.tshirt_size asc, p.sex asc) ts 
		left join (select count(p.id) as num, p.tshirt_size, p.sex, dc.class from ibo2013_participants p, ibo2013_delegation_categories dc where dc.id=p.delegation_category_id group by dc.class, p.tshirt_size, p.sex) pp on pp.tshirt_size=ts.tshirt_size and pp.class=ts.class and pp.sex=ts.sex order by ts.class asc, ts.tshirt_size asc, ts.sex asc";
		$sql->start_transaction(); $ts=$sql->simpleQuery($query); $sql->end_transaction();
		
		$class='';			
		$tot=array();			
		$j=0;		
		foreach($ts as $r){			
			if($class!=$r['class']){
				if($class!=""){
					$xhtml.='<td>'.$tot[$class]['male'].' / '.$tot[$class]['female'].'</td></tr>';					
				}	 		
				++$j;		
				if($j==$i) $xhtml.='<tr class="last">';						
				else $xhtml.='<tr class="even">';						
				$xhtml.='<td>'.$r['class'].'</td>';				
				$class=$r['class'];				
				$tot[$class]=array('male'=>0, 'female'=>0);
			}
			if($r['sex']=='male') $xhtml.='<td>'.$r['num'];
			else $xhtml.=' / '.$r['num'].'</td>';
			$tot[$r['class']][$r['sex']]+=$r['num'];
		}		
		$xhtml.='<td>'.$tot[$r['class']]['male'].' / '.$tot[$r['class']]['female'].'</td></tr></table>';		

		//get sex ratio
		$query="select sr.class, sr.sex, ifnull(pp.num,0) as num from (select distinct p.sex, dc.class from ibo2013_delegation_categories dc, ibo2013_participants p) sr left join (select count(p.id) as num, p.sex, dc.class from ibo2013_delegation_categories dc, ibo2013_participants p where p.delegation_category_id=dc.id group by p.sex, dc.class) pp on sr.sex=pp.sex and sr.class=pp.class order by sr.class asc, sr.sex asc";
		$sql->start_transaction(); $sr=$sql->simpleQuery($query); $sql->end_transaction();
		$sexratio=array();
		foreach($sr as $x){
			$sexratio[$x['class']][$x['sex']]=$x['num'];
		}
		
		//expected t-shirt sizes		
		$xhtml.='<p class="subtitle">Expected T-shirt sizes (M/F)</p>';
		$xhtml.=$header;
		$class='';
		$j=0;
		foreach($ts as $r){
			if($class!=$r['class']){
				if($class!=""){
					$xhtml.='<td>'.round($exp['male']).' / '.round($exp['female']).'</td></tr>';
				}
				++$j;		
				if($j==$i) $xhtml.='<tr class="last">';						
				else $xhtml.='<tr class="even">';		
				$xhtml.='<td>'.$r['class'].'</td>';
				$class=$r['class'];
				$exp['male']=$tot_registered[$r['class']]*$sexratio[$r['class']]['male'];
				$exp['female']=$tot_registered[$r['class']]*$sexratio[$r['class']]['female'];
				foreach($exp as $s=>$n){
					if($n>0) $exp[$s]=$exp[$s]/($sexratio[$r['class']]['male']+$sexratio[$r['class']]['female']);
				}
			}
			if($r['sex']=='male') $xhtml.='<td>'; else $xhtml.=' / ';
			$xhtml.=$this->getExpected($r['num'], $tot[$r['class']][$r['sex']], $exp[$r['sex']]);
			if($r['sex']=='female') $xhtml.='</td>';			
		}
		$xhtml.='<td>'.round($exp['male']).' / '.round($exp['female']).'</td></tr></table>';
	
		//--------------------------------------------------
		//pickup locations
		//--------------------------------------------------
		//first generate full file
		$query="select i.pickup_time, pl.location, p.first_name, p.last_name, c.en, i.arrival_date, i.arrival_time, i.transport_mode, i.flight_number, i.comment from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p, ibo2013_countries c where p.arrival_itinerary_id=i.id and i.type='arrival' and pl.id=i.pickup_location_id and p.country_id!=72 and c.id=p.country_id order by pl.id asc, i.pickup_time asc";
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$file=fopen(HTML_DIR.'webcontent/downloads/all_arrival_details.txt', 'w');		
		fwrite($file, "pickup_time;location;first_name;last_name;country;arrival_date;arrival_time;transport_mode;flight_number;comment\n");
		foreach($res as $r){
			fwrite($file, implode(';',$r)."\n");						
		}		
		fclose($file);
						
		//now show summary
		//$query="select pp.*, ifnull(cc.num_stud,0) as num_stud from (select count(p.id) as num, count(distinct p.country_id) as num_c, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p where i.type='arrival' and p.arrival_itinerary_id=i.id and pl.id=i.pickup_location_id and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) pp left join (select count(p.id) as num_stud, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p, ibo2013_delegation_categories dc where i.type='arrival' and p.departure_itinerary_id=i.id and pl.id=i.pickup_location_id and p.delegation_category_id=dc.id and dc.class='Student' and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) cc on pp.pickup_time=cc.pickup_time and pp.location=cc.location";
		
		$query="select pp.*, ifnull(cc.num_stud,0) as num_stud from (select count(p.id) as num, count(distinct p.country_id) as num_c, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p where i.type='arrival' and p.arrival_itinerary_id=i.id and pl.id=i.pickup_location_id and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) pp left join (select count(p.id) as num_stud, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p, ibo2013_delegation_categories dc where i.type='arrival' and p.arrival_itinerary_id=i.id and pl.id=i.pickup_location_id and p.delegation_category_id=dc.id and dc.class='Student' and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) cc on pp.pickup_time=cc.pickup_time and pp.location=cc.location";
		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$xhtml.='<p class="subtitle">Registered Pick-ups</p>';
		$xhtml.='<p class="text">Download all trevael details per person here <a href="http://www.ibo2013.org/webcontent/downloads/all_arrival_details.txt">here</a>.</p>';
		
		//$xhtml.='<p class="text">Geht auch über SQL ganz einfach:<br/>select i.pickup_time, pl.location, p.first_name, p.last_name, c.en, i.arrival_date, i.arrival_time, i.transport_mode, i.flight_number, i.comment from ibo2013_itinerary i, ibo2013_pickup_locations pl, ibo2013_participants p, ibo2013_countries c where p.arrival_itinerary_id=i.id and i.type="arrival" and pl.id=i.pickup_dropoff_location_id and p.country_id!=72 and c.id=p.country_id order by pl.id asc, i.pickup_time asc</p>';
		
		$xhtml.='<p class="subtitle">Summary Pick-ups</p>';
		$xhtml.='<table class="standard"><tr class="header"><td><b>Pick-up location</b></td><td><b>Pickup time</b></td><td><b>Num tot people</b></td><td><b>Num students</b></td><td><b>Num countries</b></td>';
		$loc='';
		$i=0;
		foreach($res as $r){
			++$i;
			if($r['location']!=$loc){				
				$xhtml.='<tr class="first"><td>'.$r['location'].'</td>';
				$loc=$r['location'];
			} else {
				if($i==count($res)) $xhtml.='<tr class="last"><td></td>';
				else $xhtml.='<tr class="even"><td></td>';
			 }
			$xhtml.='<td>'.date('H:i',strtotime($r['pickup_time'])).'</td><td>'.$r['num'].'</td><td>'.$r['num_stud'].'</td><td>'.$r['num_c'].'</td></tr>';
		}
		$xhtml.='</table>';

		//--------------------------------------------------
		//departure locations
		//--------------------------------------------------
		//first generate full file
		$query="select i.pickup_time, pl.location, p.first_name, p.last_name, c.en, i.arrival_date, i.arrival_time, i.transport_mode, i.flight_number, i.comment from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p, ibo2013_countries c where p.departure_itinerary_id=i.id and i.type='departure' and pl.id=i.pickup_location_id and p.country_id!=72 and c.id=p.country_id order by pl.id asc, i.pickup_time asc";
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$file=fopen(HTML_DIR.'webcontent/downloads/all_departure_details.txt', 'w');		
		fwrite($file, "drop-off_time;location;first_name;last_name;country;departure_date;departure_time;transport_mode;flight_number;comment\n");
		foreach($res as $r){
			fwrite($file, implode(';',$r)."\n");						
		}		
		fclose($file);
						
		//now show summary
		$query="select pp.*, ifnull(cc.num_stud,0) as num_stud from (select count(p.id) as num, count(distinct p.country_id) as num_c, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p where i.type='departure' and p.departure_itinerary_id=i.id and pl.id=i.pickup_location_id and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) pp left join (select count(p.id) as num_stud, i.pickup_time, pl.location from ibo2013_itinerary i, ibo2013_pickup_dropoff_locations pl, ibo2013_participants p, ibo2013_delegation_categories dc where i.type='departure' and p.departure_itinerary_id=i.id and pl.id=i.pickup_location_id and p.delegation_category_id=dc.id and dc.class='Student' and p.country_id!=72 group by i.pickup_time, pl.location order by pl.id, i.pickup_time) cc on pp.pickup_time=cc.pickup_time and pp.location=cc.location";
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$xhtml.='<p class="subtitle">Registered Drop-offs</p>';
		$xhtml.='<p class="text">Download all trevael details per person here <a href="http://www.ibo2013.org/webcontent/downloads/all_departure_details.txt">here</a>.</p>';
	
		
		$xhtml.='<p class="subtitle">Summary Drop-offs</p>';
		$xhtml.='<table class="standard"><tr class="header"><td><b>Drop-off location</b></td><td><b>Pickup time</b></td><td><b>Num tot people</b></td><td><b>Num students</b></td><td><b>Num countries</b></td>';
		$loc='';
		$i=0;
		foreach($res as $r){
			++$i;
			if($r['location']!=$loc){				
				$xhtml.='<tr class="first"><td>'.$r['location'].'</td>';
				$loc=$r['location'];
			} else {
				if($i==count($res)) $xhtml.='<tr class="last"><td></td>';
				else $xhtml.='<tr class="even"><td></td>';
			 }
			$xhtml.='<td>'.date('H:i',strtotime($r['pickup_time'])).'</td><td>'.$r['num'].'</td><td>'.$r['num_stud'].'</td><td>'.$r['num_c'].'</td></tr>';
		}
		$xhtml.='</table>';
					
		return $xhtml;
	}
	
	public function regProgressRow($class, $tot, $row){
		$tmp= '<td>'.$class.'</td><td>'.$tot.'</td><td>'.$row['tot_signedup'].'</td><td>'.$this->getPercent($row['tot_signedup'],$tot).'</td><td>'.$row['tot_confirmed'].'</td><td>'.$this->getPercent($row['tot_confirmed'], $tot).'</td><td>'.$row['tot_completed'].'</td><td>'.$this->getPercent($row['tot_completed'], $tot).'</td><td>'.$row['tot_itinerary'].'</td><td>'.$this->getPercent($row['tot_itinerary'], $tot).'</td><td>'.$row['tot_halal'].'</td><td>('.$this->getExpected($row['tot_halal'], $row['tot_completed'], $tot).')</td><td>'.$row['tot_fasting'].'</td><td>('.$this->getExpected($row['tot_fasting'], $row['tot_completed'], $tot).')</td><td>'.$row['tot_singleroom'].'</td><td>('.$this->getExpected($row['tot_singleroom'], $row['tot_completed'], $tot).')</td></tr>';
		return $tmp;
	}


	public function getPercent($n, $tot){
		if($tot<1) return '(0%)';
		return '('.round(100*$n/$tot).'%)';		
	}
	
	public function getExpected($n, $of, $tot){
		if($tot<0) return 0.0;		
		return round($n/$of*$tot,0);
	}
}
