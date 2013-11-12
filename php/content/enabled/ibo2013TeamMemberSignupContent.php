<?php
class ibo2013TeamMemberSignupContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;

	protected $userCountryId = -1;

	protected $currentFee = 0;
	protected $fees = array();
	protected $modify_id=-1;
	protected $modify_details=array();
	protected $processing_member_modification_status=true;
	protected $missing_members_stored=false;
	protected $missing_members=array();
	protected $newconfirmation=false;


	public function display(){
		$sql = SqlQuery::getInstance();

		//modify, delete or insert new?
		if($this->processing_member_modification_status){
			$this->modify_id=-1;
			foreach($GLOBALS['POST_KEYS'] as $p){
				$p=explode('_', $p);
				if(count($p)==2 && $p[0]=='modifyteammember' && is_numeric($p[1])){
					//check if this member exists
					$query='select p.*, d.category from ibo2013_participants p, ibo2013_delegation_categories d where d.id=p.delegation_category_id and p.id='.$p[1].' and p.country_id='.$this->getMyCountryId($sql);
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction();
					if(count($res)==1){
						 $this->modify_id=$p[1];
						 $this->modify_details=$res[0];
					}
					break;
				} else {
					if(count($p)==2 && $p[0]=='deleteteammember' && is_numeric($p[1])){
						//delete this member
						$query='delete from ibo2013_participants where id='.$p[1].' and country_id='.$this->getMyCountryId($sql);
						$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction();
						if(!$ok){
							$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
							sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
						} else {
							$this->process_msg .= '<p class="success">Member of your delegation was sucessfully removed!</p>';
						}
					}
				}
			}
		}

		//show process messages
		$xhtml = $this->process_msg;

		//show registration history
		$this->writeSignedupMembers($sql, $xhtml);

		//show form to change
		if($this->modify_id>0){
			$xhtml .= '<p class="subtitle">Modifying a '.$this->modify_details['category'].' Entry</p>';

			$form=$this->getForm('team_member_modify_form');
			$xhtml .= $form->getHtml($this->id);
		} else {
			$numbers=$this->getMissingMembersFromDB(&$sql);
			if(count($numbers)>0){
				$xhtml .= '<p class="subtitle">Signing up Members of your Delegation</p>';
				$xhtml .= '<p>The <b>names</b>, as provided in this form, will be used on all documents (badge, certificates, ect...).</p>';
				$xhtml .= '<p><b>Team Leader</b> is the jury member which will act as the principal contact person of your delegation during the IBO.</p><br />';

				if($this->process_status) $form=$this->remakeForm('team_member_sign_up_form');
				else $form=$this->getForm('team_member_sign_up_form');
				$xhtml .= $form->getHtml($this->id);
			}
		}

		//show form to confirm
		$xhtml.='<p class="subtitle">Confirming Members of your Deleation</p>';
		$xhtml.='<p class="text">We request that each signed-up person is actively confirmed. After confirmation, an individual account is created for this person allowing to fill in his or her <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">personal details</a> (such as food restrictions or T-shirt size). An email with personal login credentials will be sent to each person once confirmed. Students will be able to access only their &quot;Personal Details Form&quot;, while all jury members will have access to all menus and forms, including their students&prime; &quot;Personal Details Form&quot;.';
		$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>NOTE:</b> Changing the name, email address, sex or the registration category (e.g. from "Jury" to "Visitor") will not be possible after confirmation!</p>';
		if($this->newconfirmation==true){
			$frm = $this->remakeForm('team_member_confirmation_form');
		} else {
			$frm = $this->getForm('team_member_confirmation_form');
		}
		$xhtml .= $frm->getHtml($this->id);
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

	public function getMissingMembersFromDB(&$sql){
		if($this->missing_members_stored) return $this->missing_members;
		//read registered team members
		$reg=array();
		$query='select * from ibo2013_team_registration where country_id='.$this->getMyCountryId($sql).' order by timestamp desc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
		$res=$res[0];
		$numbers=array();
		if($res['num_students']>0){
			 $numbers[1]['name']='Student';
			 $numbers[1]['num']=$res['num_students'];
		 }
		if($res['num_jury']>0){
			 $numbers[2]['name']='Team Leader';
			 $numbers[2]['num']=1;
		}
		if($res['num_jury']>1){
			 $numbers[3]['name']='Jury';
			 $numbers[3]['num']=$res['num_jury']-1;
		}

		//read registered observer types
		$query='select count(o.id) as num, d.id as id, d.category from ibo2013_observer_registration o, ibo2013_delegation_categories d where country_id='.$this->getMyCountryId($sql).' and o.delegation_category_id=d.id and o.cancellation_date < 1900 group by d.category';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
		foreach($res as $r){
			//Jury already exists!
			if($r['category']=="Jury"){
				$numbers[$r['id']]['num']+=$r['num'];
			} else {
				$numbers[$r['id']]['name']=$r['category'];
				$numbers[$r['id']]['num']=$r['num'];
			}
		}
		//read signed up numbers and remove from total number of members
		$query='select count(p.user_id) as num, d.id as id from ibo2013_participants p, ibo2013_delegation_categories d where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' group by d.category';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
		
		//print_r($res); echo '<br/><br/>';

		foreach($res as $r){
			//check if missing category is one we attributed the people to (e.g. Steering Committee or Translator)			
			if(!isset($numbers[$r['id']])){
				$query='select d.*, x.id as newid from ibo2013_delegation_categories d left join ibo2013_delegation_categories x on d.class=x.category where d.id='.$r['id'];
				$sql->start_transaction(); $d=$sql->simpleQuery($query); $sql->end_transaction();
				//print_r($d);
				if(count($d)==1 && $d[0]['class']=='Jury'){
					//echo ' -> OK!';
					 $r['id']=$d[0]['newid'];
				 }
				  //echo '<br/><br/>';
			}
			if(isset($numbers[$r['id']]) && $numbers[$r['id']]['num']>=$r['num']){
				$numbers[$r['id']]['num']-=$r['num'];
				if($numbers[$r['id']]['num']<1) unset($numbers[$r['id']]);
			} else {
				sendSBOmail('daniel.wegmann@olympiads.unibe.ch','Participant signed up in category not registered!', 'Country: '.$this->getMyCountryId($sql).'<br/>Category: '.$r['category']);
			}
		}
		$this->missing_members=$numbers;
		//echo '--------------------------------------------------<br/><br/>';
		return $numbers;
	}

	public function readSignedupMembers(&$sql){
		$query='select p.*, d.category, d.id as cat_id, d.two_letter_code as cat_code, c.alpha3, t.title from ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_countries c, ibo2013_titles t where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' and c.id=p.country_id and p.title_id=t.id order by d.id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
		return $res;
	}

	public function writeSignedupMembers(&$sql, &$xhtml){
		$hist=$this->readSignedupMembers($sql);
		//only write history if there are entries
		if(count($hist)==0) return false;

		$query='select en from ibo2013_countries where id='.$this->getMyCountryId($sql);
		$sql->start_transaction();
		$cname=$sql->simpleQuery($query);
		$sql->end_transaction();
		$xhtml.='<p class="subtitle">Signed up Members of the Delegation of '.$cname[0]['en'].'</p>';
		$xhtml.='<p>Thank you for signing up members of your delegation! We have currently the following members on record (already confirmed members in green):</p>';

		//begin form to show buttons
		$xhtml.='<form id="signedup_team_member_table_form" action="" method="post">';

		//write table header
		$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Type</p></td><td><p class="monospacebold">Name</p></td><td><p class="monospacebold">Sex</p></td><td><p class="monospacebold">E-Mail</p></td><td><p class="monospacebold">Edit</p></td></tr>';

		$odd=1; $num=0;
		foreach($hist as $r){
			++$num;

			//check if cancelled
			if($r['user_id']==0){
				$style='';
			} else {
				$style=' style="color: green; font-weight: bold;"';
			}

			$xhtml.='<tr class="';
			if($num==count($hist)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
			$odd=1-$odd;
			}
			if($r['title']!='') $name=$r['title'].' '; else $name='';
			$name.=$r['first_name'].' '.strtoupper($r['last_name']);
			if(strlen($name)>30) $name=substr($name, 0,30).'&hellip;';
			if(strlen($r['email'])>25) $r['email']=substr($r['email'], 0, 24).'&hellip;';
			$xhtml.='"><td><p class="monospace"'.$style.'>'.($r['category']).'</p></td><td><p class="monospace"'.$style.'>'.$name.'</p></td><td><p class="monospace"'.$style.'>';
			if($r['sex']=='female') $xhtml.='F'; else $xhtml.='M';
			$xhtml.='</p></td><td><p class="monospace"'.$style.'>'.$r['email'].'</p></td>';
			//buttons to modify if not yet confirmed
			if($r['user_id']==0){
				$xhtml.='<td><button name="modifyteammember_'.$r['id'].'" type="submit" class="img_edit"/>';
				$xhtml.='&nbsp;<button name="deleteteammember_'.$r['id'].'" type="submit" class="img_delete"/></td>';
			}
			$xhtml.='</tr>';
		}
		$xhtml.='</table></form>';
		$xhtml.='<p>Entries of members not yet confirmed can be edited (<img src="/webcontent/styles/img/b_edit.png">) or removed (<img src="/webcontent/styles/img/b_drop.png">) using the appropriate symbols.</p>';

		return true;
	}

	protected function readDelegationCategoriesFromDB($sql){
		$query='select * from ibo2013_delegation_categories where id < 4 sort by id asc';
		$sql->start_transaction();
		$res=$sql->simpleQuery($query);
		$sql->end_transaction();
		return $res;
	}


	public function process_team_member_sign_up_form(){
		$frm = $this->getForm('team_member_sign_up_form');

		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}

		$this->process_status=true;
		$values = $frm->getElementValues();

		$sql = SqlQuery::getInstance();


		//is it update or insert?
		if(isset($values['submit_add_member'])){
			//insert
			//read missign members and check insertion
			$numbers=$this->getMissingMembersFromDB($sql);
			if($numbers[$values['category']]['num']<1){
				$this->process_msg .= '<p class="error">Failed to sign-up: there are not enough "'.$numbers[$values['category']]['name'].'" registered.</p>';
				return false;
			}
			//query
			$query='insert into ibo2013_participants (country_id, delegation_category_id, title_id, first_name, last_name, sex, email) ';
			$query.='values ('.$this->getMyCountryId($sql).', '.$values['category'].', '.$values['title'].', "'.$values['first_name'].'", "'.$values['last_name'].'", "'.$values['sex'].'", "'.$values['email'].'")';
			//insert
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction();
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Team member '.$values['first_name'].' '.$values['last_name'].' has been signed up sucessfully!</p>';
			}
			return true;
		}
	}

	public function process_team_member_modify_form(){
		$this->processing_member_modification_status=false;
		$frm = $this->getForm('team_member_modify_form');
		$values = $frm->getElementValues();

		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			$this->modify_id=$values['modify_member_id'];
			return false;
		}

		$this->processing_member_modification_status=true;


		$sql = SqlQuery::getInstance();

		if(isset($values['submit_modify_member'])){
			//verify if update is allowed
			$query='select p.*, d.category from ibo2013_participants p, ibo2013_delegation_categories d where d.id=p.delegation_category_id and p.id='.$values['modify_member_id'].' and p.country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction();
			if(count($res)!=1){
				$this->process_msg .= '<p class="error">Modification failed: you do not have sufficient privilegies</p>';
				return false;
			}

			//query
			$query='update ibo2013_participants set';
			$query.=' title_id='.$values['title'].',';
			$query.=' first_name="'.$values['first_name'].'",';
			$query.=' last_name="'.$values['last_name'].'",';
			$query.=' sex="'.$values['sex'].'",';
			$query.=' email="'.$values['email'].'"';
			$query.=' where id='.$values['modify_member_id'];

			//update
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction();
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Team member '.$values['first_name'].' '.$values['last_name'].' has been sucessfully modified!</p>';
			}
			$this->modify_id=-1;
			return true;
		}
		return false;
	}

	protected function process_team_member_confirmation_form(){
		$frm = $this->getForm('team_member_confirmation_form');

		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}

		$sql = SqlQuery::getInstance();
		$values = $frm->getElementValues();
		if(isset($values['submit_confirm_team_member'])){
			//which team member?
			$hist=$this->readSignedupMembers($sql);
			$found=false;
			foreach($hist as $r){
				if($r['id']==$values['team_member']){
					 $found=true;
					 break;
				 }
			}
			if(!$found){
				 $this->process_msg .= '<p class="error">Confirmation failed: unable to locate team member!</p>';
				 return false;
			}

			//create an appropriate user
			$ins=array();
			$ins['username']=substr(preg_replace('/[^a-zA-Z]/s', '', $r['last_name'].$r['first_name']), 0, 5).$r['id'];
			$arr = str_split('123456789abcdefghkmnpqrstuvwxyz'); $pass='';
			for($i=0; $i<3; ++$i){
				shuffle($arr);
				$pass .= implode('', array_slice($arr, 0, 3));
			}
			$ins['password']="";
			$ins['language_id']=4;
			//link delegeation category with user id (see DB)
			switch($r['cat_id']){
				//Student
				case 1: $ins['primary_usergroup_id']=32; break;
				//Jury
				case 2:
				case 3:
				case 4: $ins['primary_usergroup_id']=30; break;
				//Visitor
				case 5: $ins['primary_usergroup_id']=35; break;
				//Obsever
				case 8:
				case 9: $ins['primary_usergroup_id']=30; break;
			}
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
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', 'INSERT:<br/>'.print_r($ins, true));
				 return false;
			}
			//add password
			$query="update user set password=md5('".$pass."') where id=".$id;
			$sql->start_transaction();
			$sql->simpleQuery($query);
			$sql->end_transaction();


			//generate individual id from country and category and use logical numbering
			$ind_id=$r['alpha3'].'-'.$r['cat_code'].'-';
			$query='select individual_id from ibo2013_participants where country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction();
			$res=$sql->simpleQuery($query);
			$ok = $sql->end_transaction();
			$existing_ids=array();
			foreach($res as $a) $existing_ids[]=$a['individual_id'];
			$i=1;
			while(in_array($ind_id.$i, $existing_ids)) ++$i;
			$ind_id.=$i;

			//update ibo2013_paricipants_table: set user_id and individual_id
			$query='update ibo2013_participants set user_id='.$id.', individual_id="'.$ind_id.'" where id='.$r['id'];
			$sql->start_transaction();
			$res=$sql->simpleQuery($query);
			$ok = $sql->end_transaction();
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
				 return false;
			}

			//add new user to group
			$query='insert into user_in_group values('.$id.', '.$ins['primary_usergroup_id'].')';
			$sql->start_transaction();
			$id=$sql->simpleQuery($query);
			$ok = $sql->end_transaction();
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
				 return false;
			}
			$this->newconfirmation=true;

			//prepare email text
			$footer="<p>Best regards,<br/>Your IBO 2013 organizing team<br/><br/>----------------------<br/><br/>";
			$footer.="International Biology Olympiad IBO 2013<br/>
					  Association of Swiss Scientific Olympiads ASSO<br/>
					  University of Bern <br/>
					  Gesellschaftsstrasse 25 <br/>
					  3012 Bern <br/>
					  Switzerland <br/><br/>
					  +41 (0)31 631 35 38 <br/>
					  info@ibo2013.org <br/><br/>
					  www.ibo2013.org <br/>
					  www.facebook.com/ibo2013</p>";

			$commonPoints="<li>Please read all the information carefully!</li>";
			$commonPoints.="<li>Log in to our website www.ibo2013.org using the following personal credentials:<br/>Username: ".$ins['username']."<br/>Password: ".$pass."</li>";
			$commonPoints.="<li>Once logged in, fill in the form \"<b>Personal Details</b>\" in the menu \"Participate/Registration\".</li>";
			$yearbook="<li>We will prepare a yearbook for all participants. Please provide all information you wish to share with other participants as soon as possible. If you do not add any details or picture for the yearbook, only your name will be published.</li>";
			$questions="</ul><p>If you have any questions, please don’t hesitate to contact us. We are looking forward to welcoming you at the IBO 2013 in Bern!</p>";

			$text="<p>Dear ";
			if($r['title']!='') $text.=$r['title'].' ';
			$text.=$ins['first_name']." ".$ins['last_name']."</p>";

			//write email
			switch($r['cat_id']){
				//Student
				case 1: $text.="<p>You have been confirmed as a student participant in the International Biology Olympiad IBO 2013. Congratulations to your successful qualification!</p>";
					$text.="<p>In order to participate in the IBO 2013, we need you to take the following steps:</p><ul>";
					$text.=$commonPoints;
					$text.="<li>Make sure to complete all forms and meet all requirements on time, especially concerning the Students Declaration Form. Be aware that you will not be allowed to participate in the IBO 2013 without handing in a completed and signed declaration form!</li>";
					$text.=$yearbook;
					$text.="<li>Finally, we are planning a special event of Friday, July 19 2013. For this event, please upload a picture of your biology teacher and do not forget to bring the requested items with you to Bern! (see \"Personal Details\")</li>";

					$text.="</ul><p>If you have any questions, please contact your country coordinator.<br/>We are looking forward to welcoming you at the IBO 2013 in Bern!</p>";
					$text.=$footer;
					sendSBOmail($ins['email'],'Greetings from the IBO 2013 in Bern!', $text);
					break;

				//Jury
				case 2:
				case 3:
				case 4: $text.="<p>You have been confirmed as a jury member in the International Biology Olympiad IBO 2013.</p>";
					$text.="<p>In order to participate in the IBO 2013, we need you to take the following steps till June 15, 2013. Should you not be able to meet this deadline, please inform us without delay.</p><ul>";
					$text.=$commonPoints;
					$text.=$yearbook;
					$text.="<li>Team leader: please make sure that <b>all members of your delegation</b> have completed their \"Personal Details\" form. The personal login credentials were sent to all student participants and jury members at the e-mail address submitted online. As a jury member, you can fill in the \"Personal Details\" form for your entire delegation</li>";
					$text.="<li>Please remember that all student participants have to fill in the \"<b>Declaration Form</b>\" (available as a PDF in their \"Personal Details\" form) and hand it in twice. First a scanned version must be uploaded on our website. Second, the original form has to be handed in during registration in Bern. No completed \"Declaration Form\" means no participation in the IBO 2013!</li>";
					$text.="<li>In the menu \"Participate/Registration\" you will further find the \"<b>Travel Information</b>\" form. Please make sure that this form is filled in for your entire delegation.</li>";
					$text.=$questions;
					$text.=$footer;
					sendSBOmail($ins['email'],'Greetings from the IBO 2013 in Bern!', $text);

					break;

				//Visitor
				case 5: $text.="<p>You have been confirmed as a visitor to the International Biology Olympiad IBO 2013, held in Bern, Switzerland.</p>";
					$text.="<p>In order to participate in the IBO 2013, we need you to take the following steps till June 15, 2013. Should you not be able to meet this deadline, please inform us without delay.</p><ul>";
					$text.=$commonPoints;
					$text.="<li>As a visitor, you are welcome to participate to the opening and closing ceremony and to all parties and excursions, but you will not be granted access to the jury room nor be provided meals during the jury sessions (lunch on Monday July 15 through Wednesday July 17, dinner on Monday and Wednesday). The special fee covers accommodation, food (exception mentioned) and excursions.</li>";
					$text.="<li>If you wish to be picked-up together with your delegation, please inform us and your coordinator.</li>";
					$text.=$questions;
					$text.=$footer;
					sendSBOmail($ins['email'],'Greetings from the IBO 2013 in Bern!', $text);

					break;


				//Obsever
				case 8:
				case 9: $text.="<p>You have been confirmed as an observer participating in the International Biology Olympiad IBO 2013.</p>";
					$text.="<p>In order to participate in the IBO 2013, we need you to take the following steps till June 15, 2013. Should you not be able to meet this deadline, please inform us without delay.</p><ul>";
					$text.=$commonPoints;
					$text.=$yearbook;
					$text.="<li>Please make sure that <b>all members of your delegation</b> have completed their \"Personal Details\" form. The personal login credentials were sent to all student participants and jury members at the e-mail address submitted online. As an observer, you can fill in the \"Personal Details\" form for your entire delegation</li>";
					$text.="<li>In the menu \"Participate/Registration\" you will further find the \"<b>Travel Information</b>\" form. Please make sure that this form is filled in for your entire delegation.</li>";
					$text.=$questions;
					$text.=$footer;
					sendSBOmail($ins['email'],'Greetings from the IBO 2013 in Bern!', $text);

					break;

			}
			$this->process_msg .= '<p class="success">Team member '.$ins['first_name'].' '.$ins['last_name'].' has been sucessfully confirmed!</p>';
			return true;

		}
		return false;
	}

	protected function team_member_sign_up_form($vector){
		//read missing members from DB
		$sql = SqlQuery::getInstance();

		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(150,500);

		//get list of categories depending on registration
		$numbers=$this->getMissingMembersFromDB($sql);
		if(count($numbers)==0) return $newForm;

		//build category list and add select
		$categories=array();
		foreach($numbers as $id=>$cat){
			$categories[$id]=$cat['name'];
			if($cat['num']>1) $categories[$id].=' ('.$cat['num'].')';
		}
		$category = new Select('Category:','category', $categories, 0);
		$newForm->addElement('category',$category);

		//Title
		$titles=array(6=>'', 3=>'Dr.', 4=>'Prof.', 5=>'Prof. Dr.', 7=>'Minister');
		$title = new Select('Title: ','title',$titles, 1);
		$newForm->addElement('title',$title);


		//Names
		$fname = new Input('First Name(s):','text','first_name','',array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('first_name',$fname);


		$lname = new Input('Last Name(s):','text','last_name','',array('size'=>20));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('last_name',$lname);

		//sex
		$newForm->addElement('sex',new Select('Sex:','sex',array('female'=>'female','male'=>'male')));

		//email
		$em = new Input('E-Mail:','text','email','',array('size'=>27));
		$em->addRestriction(new IsEmailRestriction());
		$newForm->addElement('email', $em);

		$emtwo = new Input('Confirm E-Mail:','text','email2','',array('size'=>27));
		$emtwo->addRestriction(new IsEmailRestriction());
		$emtwo->addRestriction(new SameEmailAsRestriction($em));
		$newForm->addElement('email2', $emtwo);

		//submit
		$newForm->addElement('submit_add_member', new Submit('submit_add_member','save'));

		return $newForm;
	}

	protected function team_member_modify_form($vector){
		//read missing members from DB
		$sql = SqlQuery::getInstance();

		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(150,500);

		$titles=array(6=>'', 3=>'Dr.', 4=>'Prof.', 5=>'Prof. Dr.', 7=>'Minister');
		$title = new Select('Title: ','title',$titles, $this->modify_details['title_id']);
		$newForm->addElement('title',$title);

		$fname = new Input('First Name(s):','text','first_name',$this->modify_details['first_name'],array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('first_name',$fname);

		$lname = new Input('Last Name(s):','text','last_name',$this->modify_details['last_name'],array('size'=>20));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('last_name',$lname);

		$newForm->addElement('sex',new Select('Sex:','sex',array('female'=>'female','male'=>'male'), $this->modify_details['sex']));

		if($this->modify_id>0) $cur=$this->modify_details['email']; else $cur='';
		$em = new Input('E-Mail:','text','email',$this->modify_details['email'],array('size'=>27));
		$em->addRestriction(new IsEmailRestriction());
		$newForm->addElement('email', $em);

		$emtwo = new Input('Confirm E-Mail:','text','email2',$this->modify_details['email'],array('size'=>27));
		$emtwo->addRestriction(new IsEmailRestriction());
		$emtwo->addRestriction(new SameEmailAsRestriction($em));
		$newForm->addElement('email2', $emtwo);

		$newForm->addElement('modify_member_id', new Hidden('modify_member_id', $this->modify_id));
		$newForm->addElement('submit_modify_member', new Submit('submit_modify_member','save changes'));

		return $newForm;
	}

	protected function team_member_confirmation_form($vector){
		//read signed up members from DB
		$sql = SqlQuery::getInstance();
		$hist=$this->readSignedupMembers($sql);

		//select with those that are not yet registered
		$toconfirm=array();
		$numconfirmed=0;
		foreach($hist as $r){
			if($r['user_id']==0) $toconfirm[$r['id']]=$name=$r['first_name'].' '.strtoupper($r['last_name']).' ('.$r['category'].')';
			else ++$numconfirmed;
		}

		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(80,400);

		if(count($toconfirm)>0){
			$who = new Select('Team member:','team_member', $toconfirm, 0);
			$newForm->addElement('team_member',$who);


			$condition='I acknowledge that I\'m entitled to finalize this person\'s sign-up process.<br/>I\'m aware that an individual account will be created and that this person will be informed by email about their registration.<br/>I\'m aware that changing name, sex or cateogry of this person will no longer be possible.';

			$conditions=new CheckboxWithText('Please accept:','accept conditions',true, $condition);
			$conditions->addRestriction(new NotFalseRestriction());
			$newForm->addElement('accept conditions',$conditions);

			$newForm->addElement('submit_confirm_team_member', new Submit('submit_confirm_team_member','confirm team member'));
		} else {
			if($numconfirmed>0){
				$newForm->addElement('notice', new HtmlElement('<p class="formparttitle">All currently signed-up members of your delegation have already been confirmed.</p>') );
			} else {
				$newForm->addElement('notice', new HtmlElement('<p class="formparttitle">No members signed-up yet.</p>') );
			}
		}
		return $newForm;
	}

}
?>

