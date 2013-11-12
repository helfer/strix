<?php
class ibo2013TravelDetailsContent extends Content {
	protected $type = 'arrival'; //used to store if teh form is used for arrival or departure$
	protected $TYPE = 'Arrival';
	protected $text = 'arrival';
	protected $fragment_vars = array('text');
	
	protected $process_msg = '';
	protected $process_status = false;
	protected $processing_modification_status = false;
	protected $processing_assignment_status = false;
	protected $userCountryId = -1;
	
	protected $currentFee = 0;
	protected $fees = array();
	protected $modify_id=-1;
	protected $modify_details=array();
	
	protected $missing_members_stored=false;
	protected $missing_members=array();
	protected $newconfirmation=false;

	protected function splitConfValues(){
		$this->type=$this->text;
		$this->TYPE=$this->text;
		$this->TYPE{0}=strtoupper($this->TYPE{0});
	}

	public function edit_form_elements(){		
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		$elements['type'] = new Select('Type: ', 'type', array('arrival'=>'arrival', 'departure'=>'departure'), $this->type);		
		return $elements;
	}
	
	public function process_edit($action = NULL){
		$form = $this->getForm('edit_form');
		$values = $form->getElementValues();				
		$this->__set('text',$values['type']);
	}
	
	public function display(){
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();		
		$sql = SqlQuery::getInstance();	
		
		//modify or delete?
		foreach($GLOBALS['POST_KEYS'] as $p){
			$p=explode('_', $p);
			if(count($p)==2 && $p[0]=='modifyitinerary' && is_numeric($p[1])){
				//check if this itinerary exists
				$this->fillModifyDetails($sql, $p[1]);
				break;
			} elseif(count($p)==2 && $p[0]=='deleteteitinerary' && is_numeric($p[1])){
				//check if user has the right to do so...
				$query='select count(id) as num from ibo2013_itinerary where id='.$p[1].' and country_id='.$this->getMyCountryId($sql);
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				if($res[0]['num']==1){
					//delete itinerary from all people
					$query='update ibo2013_participants set '.$this->type.'_itinerary_id=0 where '.$this->type.'_itinerary_id='.$p[1];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 									
					//delete this itinerary
					$query='delete from ibo2013_itinerary where id='.$p[1];					
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 									
					if(!$ok){
						$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
						sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TravelDetailsContent', $query);
					} else {
						$this->process_msg .= '<p class="success">Itinerary has been removed sucessfully!</p>';
					}
				} else {
					$this->process_msg .= '<p class="success">You do not have sufficient privilegies to remove this itinerary!</p>';
				}
			} elseif(count($p)==2 && $p[0]=='deleteteassignement' && is_numeric($p[1])){
				//check if user has the right to do so...
				$query='select count(id) as num from ibo2013_participants where id='.$p[1].' and country_id='.$this->getMyCountryId($sql);
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				if($res[0]['num']==1){ 									
					//delete this assignement
					$query='update ibo2013_participants set '.$this->type.'_itinerary_id=0 where id='.$p[1];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 									
					if(!$ok){
						$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
						sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TravelDetailsContent', $query);
					} else {
						$this->process_msg .= '<p class="success">Assignement has been removed sucessfully!</p>';
					}
				} else {
					$this->process_msg .= '<p class="success">You do not have sufficient privilegies to remove this assignement!</p>';
				}
			}		 
		}		
		
		//show process messages
		$xhtml = $this->process_msg;	
				
		//show all itineraries
		$itineraries_stores=$this->writeItineraries($sql, $xhtml);
		
		//show form to add or change an itinerary
		if($this->modify_id>0){
			$xhtml .= '<p class="title">Modifying Itinerary '.$this->modify_details['number'].'</p>';
			$form=$this->getForm('modify_itinerary_form');
			$xhtml .= $form->getHtml($this->id);
		} else {
			$xhtml .= '<p class="title">Adding an Itinerary</p>';
	
			if($this->process_status) $form=$this->remakeForm('add_itinerary_form');		
			else $form=$this->getForm('add_itinerary_form');
			$xhtml .= $form->getHtml($this->id);			
		}	
		
		//show form to assign team members
		$xhtml .= '<p class="title">Assign Team Members to Itineraries</p>';
		if($this->processing_assignment_status) $form=$this->remakeForm('add_itinerary_to_member_form');	
		$form=$this->getForm('add_itinerary_to_member_form');
		$xhtml .= $form->getHtml($this->id);		
				
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
	
	protected function fillModifyDetails(&$sql, $id){
		$_SESSION['current_itinerary_modify_id']=$id;
		$this->modify_id=$id;
		if($id>0){
			$query='select * from ibo2013_itinerary where id='.$id;
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if(count($res)==1){
				$this->modify_details=$res[0];
				return true;
			} else {			
				$this->modify_details=array();
				$this->modify_id=-1;
				$_SESSION['current_itinerary_modify_id']=$this->modify_id;
				return false;
			}
		}
		$this->modify_details=array();
		return false;
	}
	
	
	public function readConfirmedMembersWithoutItinerary(&$sql){
		$query='select p.*, d.category, d.id as cat_id, d.two_letter_code as cat_code, c.alpha3, t.title from ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_countries c, ibo2013_titles t where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' and p.'.$this->type.'_itinerary_id=0 and p.user_id>0 and c.id=p.country_id and p.title_id=t.id order by d.id asc';		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		return $res;
	}
	
	public function readItineraries(&$sql){
		$query='select i.*, p.location as pickup_location, a.location as arrival_location from ibo2013_itinerary i, ibo2013_arrival_departure_locations a, ibo2013_pickup_dropoff_locations p where i.type="'.$this->type.'" and p.id=i.pickup_location_id and a.id=i.arrival_location_id and i.country_id='.$this->getMyCountryId($sql).' order by i.id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		return $res;
	}
	
	public function writeItineraries(&$sql, &$xhtml){
		$hist=$this->readItineraries($sql);
		//only write history if there are entries		
		if(count($hist)==0) return false;

		$xhtml.='<p class="title">Currently Stored Itineraries</p>';
				
		$xhtml.='<p>By using the appropriate symbols, added itineraries can be edited anytime (<img src="/webcontent/styles/img/b_edit.png">); they can be removed (<img src="/webcontent/styles/img/b_drop.png">) if no team member is assigned to it. Team member assignments can equally be reversed (<img src="/webcontent/styles/img/b_drop.png">).</p>';
				
		//begin form to show buttons
		$xhtml.='<form id="itineraries_table_form" action="" method="post">';
		
		//write table header
		$xhtml.='<table class="standard">';
		//$xhtml.='<tr class="header"><td><p class="monospacebold">#</p></td><td><p class="monospacebold">Pickup</p></td><td><p class="monospacebold">Arrival</p></td><td><p class="monospacebold">Transport</p></td><td><p class="monospacebold">Comment</p></td><td></td></tr>';

		$tag='Pick-up';
		if($this->type=='departure') $tag='Drop-off';
		
		$num=0; 
		foreach($hist as $r){
			++$num;
			
			//read all individuals signed up for this itinerary
			$query='select part.id, part.first_name, part.last_name, d.category, t.title from ibo2013_participants part, ibo2013_delegation_categories d, ibo2013_titles t where d.id=part.delegation_category_id and t.id=part.title_id and part.country_id='.$this->getMyCountryId($sql).' and part.'.$this->type.'_itinerary_id='.$r['id'];
			$sql->start_transaction(); $individuals=$sql->simpleQuery($query); $sql->end_transaction(); 
			
			$xhtml.='<tr class="';
			if($num==1) $xhtml.='first'; else $xhtml.='odd';
			$xhtml.='"><td><button name="modifyitinerary_'.$r['id'].'" type="submit" class="img_edit"/>';
			if(count($individuals)==0) $xhtml.='&nbsp;&nbsp;<button name="deleteteitinerary_'.$r['id'].'" type="submit" class="img_delete"/>';
			$xhtml.='</td><td><p class="monospace"><b>'.$this->TYPE.' '.$r['number'].'</b></p></td><td><p class="monospace"><b>'.$this->TYPE.'</b></p></td><td><p class="monospace">';
			if($r['arrival_date']!=0) $xhtml.=date('F j',strtotime($r['arrival_date']));
			else {
				if($this->type=='departure') $xhtml.='after July 2013';
				else $xhtml.='before July 2013';
			}
			$xhtml.=' at '.date('H:i',strtotime($r['arrival_time'])).' at '.$r['arrival_location'].' by '.$r['transport_mode'].' '.$r['flight_number'].'</p></td></tr>';
			
			$xhtml.='<tr class="';
			if(count($individuals)<1 && $r['comment']=='') $xhtml.='last'; else $xhtml.='odd';
			$xhtml.='"><td></td><td></td><td><p class="monospace"><b>'.$tag.'</b></p></td><td width="470"><p class="monospace">';			
			if($r['pickup_time']!='') $xhtml.=date('H:i',strtotime($r['pickup_time'])).' at ';
			$xhtml.=$r['pickup_location'].'</p></td></tr>';			
			
			if($r['comment']!=''){
				$xhtml.='<tr class="';
				if(count($individuals)>0) $xhtml.='odd'; else $xhtml.='last';
				$xhtml.='"><td></td><td></td><td><p class="monospace"><b>Comment</b></p></td><td><p class="monospace">'.$r['comment'].'</p></td></tr>';
			}
			
			if(count($individuals)>0){
				$xhtml.='<tr class="last"><td></td><td></td><td><p class="monospace"><b>Travellers</b></p></td><td>';
				$n=0;
				foreach($individuals as $m){
					++$n;
					$xhtml.='<p class="monospace">';
					$xhtml.=$m['title'].' '.$m['first_name'].' '.strtoupper($m['last_name']).' ('.$m['category'].')';
					$xhtml.='&nbsp;&nbsp;<button name="deleteteassignement_'.$m['id'].'" type="submit" class="img_delete"></button><br/>';
					$xhtml.='</p>';							
				}
				$xhtml.='</td></tr>';
			}
		}
		$xhtml.='</table></form>';


		return true;
	}	
	
	public function process_add_itinerary_form(){
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		$frm = $this->getForm('add_itinerary_form');		
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$this->process_status=true;
		$val = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();				
		
		//is it update or insert?		
		if(isset($val['submit_add_itinerary'])){			
			//replace date
			if($val['arrivaldate']>0 && $val['arrivaldate']<32) $val['arrivaldate']='2013-07-'.$val['arrivaldate'];
			else $val['arrivaldate']='000-00-00';

			//allow for empty pickuptime on departure
			if($val['pickuptime']=='') $val['pickuptime']='NULL';
			else $val['pickuptime']='"'.$val['pickuptime'].'"';
			
			//get next number
			$query='select max(number) as num from ibo2013_itinerary where type="'.$this->type.'" and country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(count($res)<1) $num=1;
			else $num=$res[0]['num']+1;

			$query='insert into ibo2013_itinerary (type, country_id, pickup_location_id, pickup_time, arrival_location_id, arrival_date, arrival_time, transport_mode, flight_number, comment, number) ';
			$query.='values ("'.$this->type.'", '.$this->getMyCountryId($sql).', '.$val['pickuploc'].', '.$val['pickuptime'].', '.$val['arrivalloc'].', "'.$val['arrivaldate'].'", "'.$val['arrivaltime'].'", "'.$val['transportmode'].'", "'.$val['flightnumber'].'", "'.$val['comment'].'", '.$num.')';
			//insert
			echo $query;
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TravelDetailsContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Itinerary has been added sucessfully!</p>';				 			 
			}
			return true;			
		} 	
	}
	
	public function process_modify_itinerary_form(){
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		$frm = $this->getForm('modify_itinerary_form');		
		$sql = SqlQuery::getInstance();				
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			$this->fillModifyDetails($sql, $_SESSION['current_itinerary_modify_id']);
			return false;
		}
		
		$this->processing_modification_status=true;
		$val = $frm->getElementValues();	
		
		
		//is it update or insert?		
		if(isset($val['submit_modify_itinerary'])){			
			//replace date
			if($val['arrivaldate']>0) $val['arrivaldate']='2013-07-'.$val['arrivaldate'];
			else $val['arrivaldate']='000-00-00';

			//allow for empty pickuptime on departure
			if($val['pickuptime']=='') $val['pickuptime']='NULL';
			else $val['pickuptime']='"'.$val['pickuptime'].'"';

			$query='update ibo2013_itinerary set ';
			$query.='pickup_location_id='.$val['pickuploc'].', pickup_time='.$val['pickuptime'].', arrival_location_id='.$val['arrivalloc'].', arrival_date="'.$val['arrivaldate'].'", arrival_time="'.$val['arrivaltime'].'", transport_mode="'.$val['transportmode'].'", flight_number="'.$val['flightnumber'].'", comment="'.$val['comment'].'" where id='.$val['modify_itinerary_id'].' and country_id='.$this->getMyCountryId($sql);
			//update
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TravelDetailsContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Itinerary has changed sucessfully!</p>';				 			 
			}			
			$this->fillModifyDetails($sql, 0);
			return true;			
		} 	
	}
	
	public function process_add_itinerary_to_member_form(){
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		$frm = $this->getForm('add_itinerary_to_member_form');		
		$sql = SqlQuery::getInstance();				
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';		
			return false;
		}
		
		$this->processing_assignment_status=true;
				
		$val = $frm->getElementValues();	
		
		//update
		$query='update ibo2013_participants set '.$this->type.'_itinerary_id='.$val['itinerary'].' where id='.$val['team_member'];
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
		 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TravelDetailsContent', $query);
		} else {
			$this->process_msg .= '<p class="success">Itinerary has been assigned sucessfully!</p>';				 			 
		}					
		return true;		
	}	

//------------------------------------------------
//NOTE: There are two forms, one to create and one to modify. Any changes need to be applied to BOTH!!!!!
//------------------------------------------------
	
	protected function add_itinerary_form($vector){
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		//read missing members from DB
		$sql = SqlQuery::getInstance();					
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(250,500);

		//Arrival / Departure
		$dates=array();
		if($this->type=='departure'){
			$newForm->addElement('titleArrival', new HtmlElement('<p class="formparttitle">Departure from Switzerland</p>'));			
			$newForm->addElement('arrivaltext', new HtmlElement('<p class="formtext">Please indicate when, where and how you are planning to leave Switzerland.</p></br>'));			
			for($i=20; $i<32; ++$i) $dates[$i]='July '.$i.' 2013';
			$dates[32]='after July 2013';
		} else {
			$newForm->addElement('titleArrival', new HtmlElement('<p class="formparttitle">Arrival in Switzerland</p>'));
			$newForm->addElement('arrivaltext', new HtmlElement('<p class="formtext">Please indicate when, where and how you are planning to enter Switzerland. If you plan to arrive before July 14, please also read the section "Arrivals before July 14" in the menu <a href="http://www.ibo2013.org/plan_your_trip/arrivalday/">Arrival Day</a>.</p></br>'));
			$dates=array(0=>'before July 2013');
			for($i=1; $i<16; ++$i) $dates[$i]='July '.$i.' 2013';
		}

		$arrivaldate = new Select('Date of '.$this->type.':','arrivaldate', $dates, 14);		
		$newForm->addElement('arrivaldate',$arrivaldate);
		
		$arrivaltime = new Input('Time of '.$this->type.' (e.g. 18:20; CET):','text','arrivaltime','',array('size'=>5));
		$arrivaltime->addRestriction(new isTime());		
		$newForm->addElement('arrivaltime',$arrivaltime);		
		
		$query='select * from ibo2013_arrival_departure_locations where type="'.$this->type.'" order by id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		$arrivallocations=array();
		foreach($res as $r){
			$arrivallocations[$r['id']]=$r['location'];			
		}
		$tag='Arriving at:'; if($this->type=='departure') $tag='Departing from:';
		$arrivalloc = new Select($tag,'arrivalloc', $arrivallocations, 0);		
		$newForm->addElement('arrivalloc',$arrivalloc);
		
		$modes=array('Flight'=>'Flight', 'Train'=>'Train', 'Car'=>'Car', 'Bike'=>'Bike', 'Other'=>'Other (please leave comment)');
		$transportmode = new Select('Mode of transport:','transportmode', $modes, 'Flight');		
		$newForm->addElement('transportmode',$transportmode);
		
		$number = new Input('Flight or train number:','text','flightnumber','',array('size'=>20));
		$number->addRestriction(new IsEmptyOrStrlenRestriction(2,25));		
		$newForm->addElement('flightnumber',$number);		
		
		//pickup / dropoff
		$tag='Pick-up';
		if($this->type=='departure') $tag='Drop-off';		
		$newForm->addElement('titlePickup', new HtmlElement('<p class="formparttitle">'.$tag.' Service</p>'));
		$location_title='Pick-up location on July 14:';
		if($this->type=='departure'){
			$location_title='Shuttle service needed:';
			$newForm->addElement('pickuptext', new HtmlElement('<p class="formtext">On <b>July 21</b>, you can use our free bus shuttle service to Zurich Airport. Busses will connect Bern (Guisanplatz, former IBO Registration Site) and <b>Zurich Airport</b> starting at about 4.30 am. Last shuttle bus running to Zurich Airport will leave Bern on 3 pm (approximate duration: 1.5 h). If you want to use this service, please indicate when you want to reach Zurich Airport.</p>'));
		} else {
			$newForm->addElement('pickuptext', new HtmlElement('<p class="formtext">On <b>July 14</b>, you have four options to reach the IBO: Either by using our pick up services from <b>Zurich Airport</b> and <b>Bern-Belp Airport</b>, or by meeting our guides in the main hall of the <b>Bern Train Station</b>, or by getting to the <b>IBO Registration Site</b> on your own. Please indicate your prefered location and the estimated arrival time at the pickup location on July 14. </br>For further information on how to reach the IBO 2013, see the menu <a href="http://www.ibo2013.org/plan_your_trip/arrivalday/">Arrival Day</a>.</p></br><p></p>'));
		}
			
		$query='select * from ibo2013_pickup_dropoff_locations where ';
		if($this->type=='departure') $query.='type="dropoff"';
		else $query.='type="pickup"';
		$query.=' order by id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		$pickuplocations=array();
		foreach($res as $r){
			$pickuplocations[$r['id']]=$r['location'];			
		}					
		$pickuploc = new Select($location_title,'pickuploc', $pickuplocations, 0);		
		$newForm->addElement('pickuploc',$pickuploc);
		
		$pickuptime = new Input($tag.' time (e.g. 18:20; CET):','text','pickuptime','',array('size'=>5));				
		if($this->type=='departure'){			
			 $pickuptime->addRestriction(new isEmptyOrTime());	
		} else $pickuptime->addRestriction(new isTime());		
			
		$newForm->addElement('pickuptime',$pickuptime);
		
		//comment
		$newForm->addElement('titlecomment', new HtmlElement('<p class="formparttitle">Comments</p>'));	
		$newForm->addElement('commenttext', new HtmlElement('<p class="formtext">Feel free to leave a comment. For questions, please contact us by email <a href="mailto:info@ibo2013.org">info@ibo2013.org</a>.</p></br><p></p>'));			
		$newForm->addElement('comment',new Textarea('','comment','',3,45));
	
		//submit
		$newForm->addElement('submit_add_itinerary', new Submit('submit_add_itinerary','add itinerary'));
		

		return $newForm;	
	}
	
	protected function modify_itinerary_form($vector){	
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		//read missing members from DB
		$sql = SqlQuery::getInstance();					
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(250,500);
		
		//Arrival Departure
		$dates=array();
		if($this->type=='departure'){
			$newForm->addElement('titleArrival', new HtmlElement('<p class="formparttitle">Departure from Switzerland</p>'));			
			$newForm->addElement('arrivaltext', new HtmlElement('<p class="formtext">Please indicate when, where and how you are planning to enter Switzerland. If you plan to arrive before July 14, please also read the section "Arrivals before July 14" in the menu <a href="http://www.ibo2013.org/plan_your_trip/arrivalday/">Arrival Day</a>.</p></br>'));			
			for($i=20; $i<32; ++$i) $dates[$i]='July '.$i.' 2013';
			$dates[32]='after July 2013';
		} else {
			$newForm->addElement('titleArrival', new HtmlElement('<p class="formparttitle">Arrival in Switzerland</p>'));
			$newForm->addElement('arrivaltext', new HtmlElement('<p class="formtext">Please indicate when, where and how you are planning to enter Switzerland. If you plan to arrive before July 14, please also read the section "Arrivals before July 14" in the menu <a href="http://www.ibo2013.org/plan_your_trip/arrivalday/">Arrival Day</a>.</p></br>'));
			$dates=array(0=>'before July 2013');
			for($i=1; $i<16; ++$i) $dates[$i]='July '.$i.' 2013';
		}
		
		$arrivaldate = new Select('Date of '.$this->type.':','arrivaldate', $dates, date('j',strtotime($this->modify_details['arrival_date'])));		
		$newForm->addElement('arrivaldate',$arrivaldate);
		
		$arrivaltime = new Input('Time of '.$this->type.' (e.g. 18:20; CET):','text','arrivaltime',date('H:i', strtotime($this->modify_details['arrival_time'])),array('size'=>5));
		$arrivaltime->addRestriction(new isTime());		
		$newForm->addElement('arrivaltime',$arrivaltime);		
		
		$query='select * from ibo2013_arrival_departure_locations where type="'.$this->type.'" order by id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		$arrivallocations=array();
		foreach($res as $r){
			$arrivallocations[$r['id']]=$r['location'];			
		}
		$tag='Arriving at:'; if($this->type=='departure') $tag='Departing from:';
		$arrivalloc = new Select($tag.' at:','arrivalloc', $arrivallocations, $this->modify_details['arrival_location_id']);		
		$newForm->addElement('arrivalloc',$arrivalloc);
		
		$modes=array('Flight'=>'Flight', 'Train'=>'Train', 'Car'=>'Car', 'Bike'=>'Bike', 'Other'=>'Other (please leave comment)');
		$transportmode = new Select('Mode of Transport:','transportmode', $modes, $this->modify_details['transport_mode']);		
		$newForm->addElement('transportmode',$transportmode);
		
		$number = new Input('Flight or train number:','text','flightnumber',$this->modify_details['flight_number'],array('size'=>20));
		$number->addRestriction(new IsEmptyOrStrlenRestriction(2,25));		
		$newForm->addElement('flightnumber',$number);	
		
		//pick-up or drop-off
		$tag='Pick-up';
		if($this->type=='departure') $tag='Drop-off';
		$newForm->addElement('titlePickup', new HtmlElement('<p class="formparttitle">'.$tag.' Location</p>'));
		$location_title='Pick-up location on July 14:';
		if($this->type=='departure'){
			$location_title='Shuttle service needed:';
			$newForm->addElement('pickuptext', new HtmlElement('<p class="formtext">On <b>July 21</b>, you can use our free bus shuttle service to Zurich Airport. Busses will connect Bern (Guisanplatz, former IBO Registration Site) and <b>Zurich Airport</b> starting at about 4.30 am. Last shuttle bus running to Zurich Airport will leave Bern on 3 pm (approximate duration: 1.5 h). If you want to use this service, please indicate when you want to reach Zurich Airport.</p></br><p></p>'));
		} else {
			$newForm->addElement('pickuptext', new HtmlElement('<p class="formtext">On <b>July 14</b>, you have four options to reach the IBO: Either by using our pick up services from <b>Zurich Airport</b> and <b>Bern-Belp Airport</b>, or by meeting our guides in the main hall of the <b>Bern Train Station</b>, or by getting to the <b>IBO Registration Site</b> on your own. Please indicate your prefered location and the estimated arrival time at the pickup location on July 14. </br>For further information on how to reach the IBO 2013, see the menu <a href="http://www.ibo2013.org/plan_your_trip/arrivalday/">Arrival Day</a>.</p></br><p></p>'));
		}
				
		$query='select * from ibo2013_pickup_dropoff_locations where ';
		if($this->type=='departure') $query.='type="dropoff"';
		else $query.='type="pickup"';
		$query.=' order by id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		$pickuplocations=array();
		foreach($res as $r){
			$pickuplocations[$r['id']]=$r['location'];			
		}		
		
		$pickuploc = new Select($location_title,'pickuploc', $pickuplocations, $this->modify_details['pickup_location_id']);
		$newForm->addElement('pickuploc',$pickuploc);
		
		$pickuptime = new Input($tag.' time (e.g. 18:20; CET):','text','pickuptime',date('H:i', strtotime($this->modify_details['pickup_time'])),array('size'=>5));		
		if($this->type=='departure') $pickuptime->addRestriction(new isEmptyOrTime());	
		else $pickuptime->addRestriction(new isTime());		
		$newForm->addElement('pickuptime',$pickuptime);					
		
		//comment
		$newForm->addElement('titlecomment', new HtmlElement('<p class="formparttitle">Comments</p>'));	
		$newForm->addElement('commenttext', new HtmlElement('<p class="formtext">Feel free to leave a comment. For questions, please contact us by email <a href="mailto:info@ibo2013.org">info@ibo2013.org</a>.</p></br><p></p>'));			
		$newForm->addElement('comment',new Textarea('','comment',$this->modify_details['comment'],3,45));
	
		//submit
		$newForm->addElement('modify_itinerary_id', new Hidden('modify_itinerary_id', $this->modify_id));
		$newForm->addElement('submit_modify_itinerary', new Submit('submit_modify_itinerary','modify itinerary'));


		return $newForm;	
	}

	protected function add_itinerary_to_member_form($vector){	
		$fr = $this->loadAdditionalFragments();
		$this->splitConfValues();
		//read signed up members from DB
		$sql = SqlQuery::getInstance();	
		$hist=$this->readConfirmedMembersWithoutItinerary($sql);
			
		$members=array();		
		foreach($hist as $r){
			$members[$r['id']]=$r['first_name'].' '.strtoupper($r['last_name']).' ('.$r['category'].')';			
		}		
			
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(250,500);
		
		if(count($members)>0){
			//get all itineraries as select
			$res=$this->readItineraries($sql);
			$it=array();
			foreach($res as $r){
				$it[$r['id']]=$this->TYPE.' '.$r['number'];
			}			
			if(count($it)>0){
				
				$who = new Select('Team member:','team_member', $members, 0);
				$newForm->addElement('team_member',$who);

				$itinerary = new Select('Itinerary:','itinerary', $it, 0);
				$newForm->addElement('itinerary',$itinerary);

				$newForm->addElement('submit_assign_itinerary', new Submit('submit_assign_itinerary','Assign Itinerary'));
			} else {
				$newForm->addElement('notice', new HtmlElement('<p class="formparttitle">You have to enter an itinerary first in order to assign the confirmed members of your delegation.</p>') );						}
		} else {			
				$newForm->addElement('notice', new HtmlElement('<p class="formparttitle">All currently confirmed members of your delegation have been given an itinerary.</p>') );			
		}
		return $newForm;	
	}
}
?>

