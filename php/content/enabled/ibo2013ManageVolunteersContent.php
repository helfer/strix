<?php
class ibo2013ManageVolunteersContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	protected $process_status_assignement = false;
		
	
	public function display(){
		$sql = SqlQuery::getInstance();	
		
		//delete assignement?
		foreach($GLOBALS['POST_KEYS'] as $p){
			$p=explode('_', $p);
			if(count($p)==2 && $p[0]=='deleteteassignement' && is_numeric($p[1])){
				//delete assignement -> set delegation_category=0
				$query='update ibo2013_participants set delegation_category_id=0 where id='.$p[1];					
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 									
				if(!$ok){
					$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
					sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManageVolunteersContent', $query);
				} else {
					$this->process_msg .= '<p class="success">Assignement has been removed sucessfully!</p>';
				}
			}
		}
		
		//show process messages
		$xhtml = $this->process_msg;	
		
		//write all assigned volunteers
		$this->writeVolunteerAssignements($sql, $xhtml);
		
		//show form to assign volunteers
		$xhtml .= '<p class="subtitle">Assign Volunteers</p>';
		if($this->process_status_assignement) $form=$this->remakeForm('assign_volunteer_form');
		else $form=$this->getForm('assign_volunteer_form');
		$xhtml .= $form->getHtml($this->id);	
		
		//show form to create users
		$xhtml .= '<p class="subtitle">Create Volunteer from Applications</p>';
		$xhtml .= '<p class="text"><b>Note:</b> this cannot be undone! However, the volunteer applicants are not informed about this step.</p>';
		if($this->process_status) $form=$this->remakeForm('create_user_form');
		else $form=$this->getForm('create_user_form');
		$xhtml .= $form->getHtml($this->id);	
				
		
				
		return $xhtml;
	}
	
	
	
	public function writeVolunteerAssignements(&$sql, &$xhtml){				
		$xhtml.='<p class="subtitle">Current Volunteer Assignements</p>';
		$query='select p.id, p.first_name, p.last_name, d.category from ibo2013_delegation_categories d left join ibo2013_participants p on p.delegation_category_id=d.id where d.class="Volunteer" or d.class="Organizer" or d.category="Translater" order by d.category asc, p.last_name asc, p.first_name asc';
		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
		
		$xhtml.='<p>Assignements can be removed by clicking on <img src="/webcontent/styles/img/b_drop.png">.</p>';
				
		//begin form to show buttons
		$xhtml.='<form id="itineraries_table_form" action="" method="post">';
		
		//write table header
		$xhtml.='<table class="standard">';
		$xhtml.='<tr class="header"><td><p class="monospacebold">Volunteer Category</p></td><td><p class="monospacebold">Assigned Volunteers';
		
		$num=0;
		$oldCat=""; 
		foreach($res as $r){
			++$num;
			
			//new category?
			if($r['category']!=$oldCat){
				$oldCat=$r['category'];
				$xhtml.='</p></td></tr>';
				$xhtml.='<tr class="last"><td><p class="monospace">'.$r['category'].'</p></td><td><p class="monospace">';
			} else $xhtml.='<br/>';
			//add new volunteer
			if($r['last_name']!=""){
				$xhtml.=strtoupper($r['last_name']).' '.$r['first_name'].'&nbsp;&nbsp;<button name="deleteteassignement_'.$r['id'].'" type="submit" class="img_delete"></button>';
			}
		}
		$xhtml.='</p></td></tr></table></form>';

	}	
	
	public function process_create_user_form(){
		$frm = $this->getForm('create_user_form');		
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$val = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();				
		
		//proper button pressed?
		if(isset($val['submit_create_user'])){
			$this->process_status=true;
			//read everything from application
			$query='select * from ibo2013_volunteer_applications where id='.$val['volunteer'];
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			$r=$res[0];
			
			//create an appropriate user
			$arr = str_split('123456789abcdefghkmnpqrstuvwxyz');
			shuffle($arr);
			$ins=array();
			$ins['username']=substr(preg_replace('/[^a-zA-Z]/s', '', $r['last_name'].$r['first_name']), 0, 5).$r['id'].$arr[0].$arr[1];
			$pass='';
			for($i=0; $i<3; ++$i){
				shuffle($arr); 
				$pass .= implode('', array_slice($arr, 0, 3));
			}
			$ins['password']="";
			$ins['language_id']=4;
			$ins['primary_usergroup_id']=33;
			$ins['first_name']=$r['first_name'];
			$ins['last_name']=$r['last_name'];
			$ins['email']=$r['email'];
			$ins['creation_date']=date('Y-m-t');
			$ins['street']='';
			$ins['zip']='0';
			$ins['city']='';
			$ins['birthday']='0000-00-00';
			
			$sql->start_transaction(); 
			$id=$sql->insertQuery('user',$ins);
			$ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManageVolunteersContent', 'INSERT:<br/>'.print_r($ins, true));
				 return false;
			}
			
			//add password
			$query="update user set password=md5('".$pass."') where id=".$id;
			$sql->start_transaction(); 
			$sql->simpleQuery($query);
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
				 return false;
			}
			$this->newconfirmation=true;
			
			//add new user to group
			$query='insert into user_in_group values('.$id.', '.$ins['primary_usergroup_id'].')';
			$sql->start_transaction(); 
			$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
				 return false;
			}
			
			//create indidivual id
			$query='select two_letter_code from ibo2013_delegation_categories where id='.$val['category'];
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			$ind_id=$res[0]['two_letter_code'].'-';
			$query='select individual_id from ibo2013_participants where individual_id like "'.$ind_id.'%"';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			$exists=array();
			foreach($res as $a) $exists[$a['individual_id']]=true;
			$num=1;
			while(isset($exists[$ind_id.$num])) ++$num;

			//insert into ibo2013_paricipants_table
			$partins['user_id']=$id;
			$partins['country_id']=0;
			$partins['delegation_category_id']=$val['category'];
			$partins['title_id']=6;
			$partins['first_name']=$r['first_name'];
			$partins['last_name']=$r['last_name'];
			$partins['sex']=$r['sex'];
			$partins['email']=$r['email'];
			$partins['individual_id']=$ind_id.$num;
			$partins['birthday']=$r['birthday'];
			$partins['nationality']=$r['nationality'];
			$part_id=$sql->insertQuery('ibo2013_participants',$partins);			
			
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManageVolunteersContent', 'INSERT:<br/>'.print_r($part_id, true));
				 return false;
			}		
			
			//update volunteer table
			$query='update ibo2013_volunteer_applications set participant_id='.$part_id.' where id='.$val['volunteer'];
			$sql->start_transaction(); 
			$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
				 return false;
			}
			
			//write email
			$text="<p>Dear ";
			if($r['title']!='') $text.=$r['title'].' ';
			$text.=$ins['first_name']." ".$ins['last_name']."</p>";

			$text.="<p>Thanks again for registering as a volunteer for the International Biology Olympiad IBO 2013 in Bern. In order to continue our planning, we need you to carry out the following tasks:</p>";
			$text.="<ul><li>Please read all the information carefully!</li>";
			$text.="<li>Log in to our website www.ibo2013.org using the following personal credentials:<br/>Username: ".$ins['username']."<br/>Password: ".$pass."</li>";
			$text.="<li>Once logged in, fill in the form \"<b>Personal Details</b>\" in the menu \"Participate/Registration\".</li>";
			$text.="<li>We will prepare a yearbook for all participants. Please provide all information you wish to share with other participants as soon as possible. If you do not add any details or picture for the yearbook, only your name will be published.</li>";
			$text.="</ul><p>If you have any questions, please don’t hesitate to contact us. Should you no longer wish to volunteer for the IBO 2013, please let us know immediately. </p><p>We are looking forward to meeting you at the IBO 2013 in Bern!</p>";

			$text.="<p>Best regards,<br/>Your IBO 2013 organizing team<br/><br/>----------------------<br/><br/></p>";
			$text.="International Biology Olympiad IBO 2013<br/>
					  Association of Swiss Scientific Olympiads ASSO<br/>
					  University of Bern <br/>
					  Gesellschaftsstrasse 25 <br/>
					  3012 Bern <br/>
					  Switzerland <br/><br/>
					  +41 (0)31 631 35 38 <br/>
					  info@ibo2013.org <br/><br/>
					  www.ibo2013.org <br/>
					  www.facebook.com/ibo2013</p>";

			sendSBOmail($ins['email'],'Greetings from the IBO 2013 in Bern!', $text);

			$this->process_msg .= '<p class="success">User for volunteer '.$ins['first_name'].' '.$ins['last_name'].' has been created sucessfully!</p>';	
			return true;
			
		} 	
	}
	
	public function process_assign_volunteer_form(){
		$frm = $this->getForm('assign_volunteer_form');		
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$val = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();				
		
		//proper button pressed?
		if(isset($val['submit_assign_volunteer'])){
			$this->process_status_assignement=true;
			//update delegation category
			$query='update ibo2013_participants set delegation_category_id='.$val['category'].' where id='.$val['volunteer'];
			$sql->start_transaction(); 
			$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManageVolunteersContent', $query);
				 return false;
			}
			$this->process_msg .= '<p class="success">Volunteer has been assigned sucessfully!</p>';	
			return true;
		}
		return false;
	}

	
	protected function create_user_form($vector){	
		$sql = SqlQuery::getInstance();					
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(200,500);
		
		//read all volunteer applicants from DB
		$query='select id, first_name, last_name from ibo2013_volunteer_applications where participant_id=0 order by last_name asc, first_name asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
		
		$volunteers=array();		
		foreach($res as $r){
			$volunteers[$r['id']]=strtoupper($r['last_name']).' '.$r['first_name'];			
		}		

		if(count($volunteers)>0){
			$who = new Select('Volunteer applicant:','volunteer', $volunteers, 0);
			$newForm->addElement('volunteer',$who);
			
			//select category
			$query='select id, category from ibo2013_delegation_categories where class="Volunteer" or class="Organizer"  or category="Translater" order by category asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			$cat=array();
			foreach($res as $r) $cat[$r['id']]=$r['category'];
			
			$categories = new Select('Volunteer Category:','category', $cat, 0);
			$newForm->addElement('category',$categories);
			
			
			$newForm->addElement('submit_create_user', new Submit('submit_create_user','Create User'));
		} else {
			$newForm->addElement('notice', new HtmlElement('<p class="formparttitle">A user has been created for all volunteer applicants registered.</p>') );
		}
		return $newForm;	
	}
	
	protected function assign_volunteer_form($vector){	
		//read volunteers from
		$sql = SqlQuery::getInstance();	
		$query='select p.id, p.first_name, p.last_name from ibo2013_participants p, ibo2013_volunteer_applications v where v.participant_id=p.id and p.delegation_category_id=0 order by p.last_name asc, p.first_name asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			
		$members=array();		
		foreach($res as $r){
			$members[$r['id']]=$r['first_name'].' '.strtoupper($r['last_name']);			
		}		
			
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(200,500);
		
		if(count($members)>0){			
			$who = new Select('Volunteer:','volunteer', $members, 0);
			$newForm->addElement('volunteer',$who);

			//select category
			$query='select id, category from ibo2013_delegation_categories where class="Volunteer" or class="Organizer"  or category="Translater" order by category asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			$cat=array();
			foreach($res as $r) $cat[$r['id']]=$r['category'];
			
			$categories = new Select('Volunteer Category:','category', $cat, 0);
			$newForm->addElement('category',$categories);
			
			$newForm->addElement('submit_assign_volunteer', new Submit('submit_assign_volunteer','Assign Volunteer'));
		} else {			
				$newForm->addElement('notice', new HtmlElement('<p class="text"><b>All volunteers are assigned.</b></p>') );			
		}
		return $newForm;	
	}
}
?>

