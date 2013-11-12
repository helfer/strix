<?php
class ibo2013RegistrationStatusContent extends Content {
	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	public function display(){
		$sql = SqlQuery::getInstance();	
		$xhtml='';
		$number=1;
		
		//not logged in?
		$error="<p class='problem'>You have to be logged in in order to register for the IBO 2013.</p>";
		if(!isset($GLOBALS['user']) || $GLOBALS['user']->primary_usergroup_id == 1 || $GLOBALS['user']->primary_usergroup_id == 28){
			 return $error;
		 }
				
		//prepare counters
		$num_students=0;
		$num_jury=0;
		$num_additional_jury=0;
		$num_observers=0;
				
				
		//Team Registration
		//country=29, jury=30, admin=2 
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2){
			$query='select num_jury, num_students from ibo2013_team_registration where country_id='.$this->getMyCountryId($sql).' order by timestamp desc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($sql->num_rows()>0){
				$num_students=$res[0]['num_students'];
				$num_jury=$res[0]['num_jury'];
			} else {
				$num_students=0;
				$num_jury=0;
			}
			
			$xhtml.='<p class="subtitle">'.$number.'. Register your Delegation</p>';
			++$number;
			if($num_students>0 && $num_jury>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> Your delegation has been successfully registered! You indicated to bring <b>'.$num_students.' students and '.$num_jury.' jury members</b>. These numbers can be changed <a href="http://www.ibo2013.org/Participate/registration/Delegation/">here</a>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Your delegation has not yet been registered. Please provide the number of students and jury members you plan to bring <a href="http://www.ibo2013.org/Participate/registration/Delegation/">here</a>.</p>';			
			}
			
			//Additional Jury Members				
			$query='select count(tr.id) as num, d.category from ibo2013_observer_registration tr, ibo2013_delegation_categories d where tr.country_id='.$this->getMyCountryId($sql).' and d.id=tr.delegation_category_id and tr.cancellation_date<"1900-01-01" and d.id>1 and d.id<6 group by d.category order by d.id asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_additional_jury_categories=$sql->num_rows();
			$num_additional_jury=0;
					
			$xhtml.='<p class="subtitle">'.$number.'. Register Additional Jury Members</p>';
			++$number;
			if($num_additional_jury_categories > 0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have registered ';
				$comma=', ';
				foreach($res as $id=>$r){
						if($id==count($res)-2) $comma=' and ';
						if($id==count($res)-1) $comma='';
						$xhtml.='<b>'.$r['num'].' '.$r['category'].'</b>'.$comma;
						$num_additional_jury+=$r['num'];
				} 
				$xhtml.='. You can add or remove additional jury members <a href="http://www.ibo2013.org/Participate/registration/AdditionalJuryMember/">here</a>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> You have not registered any additional jury members. If you wish to bring additional jury members, please register them <a href="http://www.ibo2013.org/Participate/registration/AdditionalJuryMember/">here</a>.</p>';			
			}
		}

		//Observer		
		//observing country=34, observer=31, admin=2
		if($GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31 || $GLOBALS['user']->primary_usergroup_id == 2){
			$query='select count(tr.id) as num, d.category from ibo2013_observer_registration tr, ibo2013_delegation_categories d where tr.country_id='.$this->getMyCountryId($sql).' and d.id=tr.delegation_category_id and tr.cancellation_date<"1900-01-01" and (d.id=9 or d.id=8) group by d.category order by d.id asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_observers_categories=$sql->num_rows();
			$num_observers=0;
			
			$xhtml.='<p class="subtitle">'.$number.'. Register Observers</p>';
			++$number;
			if($num_observers_categories > 0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have registered ';
				$comma=', ';
				foreach($res as $id=>$r){
						if($id==count($res)-2) $comma=' and ';
						if($id==count($res)-1) $comma='';
						$xhtml.='<b>'.$r['num'].' '.$r['category'].'</b>'.$comma;
						$num_observers+=$r['num'];
				} 
				$xhtml.='. You can add or remove Observers <a href="http://www.ibo2013.org/Participate/registration/AdditionalJuryMember/">here</a>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> You have not registered any Observers. Please do so <a href="http://www.ibo2013.org/Participate/registration/observer/">here</a>.</p>';			
			}
		}
		
		//Are the members signed up?
		//country=29, jury=30, admin=2, observing country=34, observer=31
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){
			$num_registered=$num_students+$num_jury+$num_additional_jury+$num_observers;		
			$query='select count(p.id) as num from ibo2013_participants p where p.country_id='.$this->getMyCountryId($sql);				
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_not_signed_up=$num_registered - $res[0]['num'];

			$xhtml.='<p class="subtitle">'.$number.'. Sign-up your Delegation by Providing Names</p>';
			++$number;
			if($num_not_signed_up == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have signed-up your entire delegation.</p>';
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please provided the names of all your delegation members <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>May 25, 2013</b>.</p>';	
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> There are <b>'.$num_not_signed_up.' members of your delegation</b> not signed-up yet. Please provide their names <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>May 25, 2013</b>.</p>';			
			}
		}
		
		//Are all Team Members confirmed?
		//country=29, jury=30, admin=2, observing country=34, observer=31
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){
			$query='select count(p.id) as num from ibo2013_participants p where p.user_id>0 and p.country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_not_confirmed=$num_registered - $res[0]['num'];
			
			$xhtml.='<p class="subtitle">'.$number.'. Confirm the Members of your Delegation</p>';
			++$number;
			if($num_not_confirmed == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have successfully confirmed all members of your delegation.</p>';
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please confirm the participation of your members <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>May 25, 2013</b>.</p>';			
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> There are <b>'.$num_not_confirmed.' members of your delegation</b> not confirmed yet. Please confirm them <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>May 25, 2013</b>.</p>';			
			}
		}
				
		//Provide Personal Details	
		//Student, visitor and volunteer -> only their own details
		if($GLOBALS['user']->primary_usergroup_id == 32 || $GLOBALS['user']->primary_usergroup_id == 33 || $GLOBALS['user']->primary_usergroup_id == 35){
			$xhtml.='<p class="subtitle">'.$number.'. Provide Personal Details</p>';
			++$number;
			//check if tshirt and birthday is entered			
			$query='select count(p.id) as num from ibo2013_participants p where p.user_id='.$GLOBALS['user']->id.' and p.birthday>"1900-01-01" and p.tshirt_size!="-"';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 			
			if($res[0]['num']==1){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have successfully provided your personal details. However, you can still modify them <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>You have not yet provided your personal details. Please do so <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';	
			}			
		}
		
		//jury, observer, country, observing country and admin -> change details of whole team
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){		
			$xhtml.='<p class="subtitle">'.$number.'. Provide Personal Details</p>';
			++$number;
			$query='select count(p.id) as num from ibo2013_participants p where p.user_id>0 and p.birthday>"1900-01-01" and p.country_id='.$this->getMyCountryId($sql);
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_no_personal_details=$num_registered - $res[0]['num'];
			if($num_no_personal_details == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> All members of your delegation have provided their personal details.</p>';
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please provide all personal details <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>June 15, 2013</b>.</p>';			
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> '.$num_no_personal_details.' members of your delegation</b> have not yet provided their personal details. Please ask them to do so or provide their details <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';			
			}
		}
		
		//Yearbook Photo
		//everyone except visitors		
		//Students and volunteers -> only their own photo
		if($GLOBALS['user']->primary_usergroup_id == 32 || $GLOBALS['user']->primary_usergroup_id == 33 || $GLOBALS['user']->primary_usergroup_id == 35){
			$xhtml.='<p class="subtitle">'.$number.'. Provide Photo for Yearbook</p>';
			++$number;
			//check if photo has been provided
			$query='select count(p.id) as num from ibo2013_participants p where p.user_id='.$GLOBALS['user']->id.' and length(p.photo_basename)>5';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($res[0]['num']==1){			
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have successfully provided your personal details. However, you can still modify them <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> You have not yet provided your personal details. Please do so <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';	
			}
		}

		//jury, observer, country, observing country and admin -> change details of whole team
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){	
			$xhtml.='<p class="subtitle">'.$number.'. Provide Photo for Yearbook</p>';
			++$number;
			//get num visitors, as they are not in year book
			$query='select count(tr.id) as num, d.category from ibo2013_observer_registration tr, ibo2013_delegation_categories d where tr.country_id='.$this->getMyCountryId($sql).' and d.id=tr.delegation_category_id and tr.cancellation_date<"1900-01-01" and d.id=5 group by d.category order by d.id asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($sql->num_rows()) $num_yearbook=$num_registered-$res[0]['num'];
			else $num_yearbook=$num_registered;
		
			//get num of those that provided a yearbook photo
			$query='select count(p.id) as num from ibo2013_participants p where length(p.photo_basename)>5 and p.country_id='.$this->getMyCountryId($sql);				
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($sql->num_rows()==1) $num_no_yearbook_picture=$num_yearbook - $res[0]['num'];					
		
			if($num_no_yearbook_picture == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> All members of your delegation have uploaded a photo for the yearbook.';		
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please provide a photo <a href="http://www.ibo2013.org/Participate/registration/provide_names/">here</a> before <b>June 15, 2013</b>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>'.$num_no_yearbook_picture.' members of your delegation</b> have not yet uploaded a photo for the yearbook. Please ask them to do so or provide it <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';
			}			
		}
		
		//Student Declaration Form
		//country=29, jury=30, admin=2, student=32
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2){
			$query='select count(p.id) as num from ibo2013_participants p where p.delegation_category_id=1 and length(p.declaration_form_name)>5 and p.country_id='.$this->getMyCountryId($sql);
			
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_no_declaration_form=$num_students - $res[0]['num'];
			
			$xhtml.='<p class="subtitle">'.$number.'. Upload Student Declaration Forms</p>';
			++$number;
			if($num_no_declaration_form == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> All your students have uploaded a declaration form. Please make sure you will also <b>bring the originals</b> to Switzerland.</p>';				
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please upload the declaration forms <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';	
			} else {						
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>'.$num_no_declaration_form.' students</b> have not yet uploaded a declaration form. Please ask them to do so or provide it yourself  <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>. A personalzed declaration form can be found on the personal details forms of your students. Note that you first have to confirm the name of your students, before their personal details form is created. </p>';
			}
		}
		
		if($GLOBALS['user']->primary_usergroup_id == 32){
			$xhtml.='<p class="subtitle">'.$number.'. Upload Student Declaration Forms</p>';
			++$number;
			$query='select count(p.id) as num from ibo2013_participants p where length(p.declaration_form_name)>5 and p.user_id='.$GLOBALS['user']->id;
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($res[0]['num'] == 1){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have successfully uploaded a declaration form. Please make sure to <b>bring the originals</b> to Switzerland.</p>';
			} else {			
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have not yet uploaded a declaration form. Please do so <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';
			}
		}
		
		//Teacher Photo
		//country=29, jury=30, admin=2, student=32
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2){
			$query='select count(p.id) as num from ibo2013_participants p where length(p.teacher_photo_basename)>5 and p.country_id='.$this->getMyCountryId($sql);
			
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_no_teacher_photo=$num_students - $res[0]['num'];
			
			$xhtml.='<p class="subtitle">'.$number.'. Students Provide Photo of their Biology Teacher</p>';
			++$number;
			if($num_no_teacher_photo == 0 && $num_registered>0){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> All your students have uploaded a photo of their biology teacher.';				
			} else if($num_registered==0){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> Please upload these photos <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';	
			} else {			
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>'.$num_no_teacher_photo.' students</b> have not yet uploaded a photo of their biology teacher. Please ask them to do so or provide it <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';	
			}
		}
		
		if($GLOBALS['user']->primary_usergroup_id == 32){
			$xhtml.='<p class="subtitle">'.$number.'. Students Provide Photo of their Biology Teacher</p>';
			++$number;
			$query='select count(p.id) as num from ibo2013_participants p where length(p.teacher_photo_basename)>5 and p.user_id='.$GLOBALS['user']->id;
			echo $query;
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if($res[0]['num'] == 1){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> You have successfully uploaded a picture of your biology teacher. However, you can still change it <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a>.</p>';
			} else {			
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> You have not yet uploaded a photo of your biology teacher. Please do so <a href="http://www.ibo2013.org/Participate/registration/personalDetails/">here</a> before <b>June 15, 2013</b>.</p>';
			}
		}
		
		
		//Arrival Detail
		//country=29, jury=30, admin=2, observing country=34, observer=31
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){
			$xhtml.='<p class="subtitle">'.$number.'. Provide Arrival Details</p>';		
			++$number;
			
			$query='select count(p.id) as num from ibo2013_participants p where p.country_id='.$this->getMyCountryId($sql).' and p.arrival_itinerary_id>0';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_without_itinerary=$num_registered-$res[0]['num'];
			
			if($num_without_itinerary>0 || ($num_without_itinerary==0 && $num_registered==0)){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>'.$num_without_itinerary.' team members</b> have not yet been assigned to a arrival itinerary. Please provide arrival details for all your team members <a href="http://www.ibo2013.org/Participate/registration/arrivaldetails/">here</a> before <b>June 15, 2013</b>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> The necessary arrival details have been provided for all members of your delegation. However, you can still modify them <a href="http://www.ibo2013.org/Participate/registration/arrivaldetails/">here</a>.</p>';				
			}
		}

		//Departure Detail
		//country=29, jury=30, admin=2, observing country=34, observer=31
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){
			$xhtml.='<p class="subtitle">'.$number.'. Provide Departure Details</p>';		
			++$number;
			
			$query='select count(p.id) as num from ibo2013_participants p where p.country_id='.$this->getMyCountryId($sql).' and p.departure_itinerary_id>0';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			$num_without_itinerary=$num_registered-$res[0]['num'];
			
			if($num_without_itinerary>0 || ($num_without_itinerary==0 && $num_registered==0)){
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> <b>'.$num_without_itinerary.' team members</b> have not yet been assigned to a departure itinerary. Please provide departure details for all your team members <a href="http://www.ibo2013.org/Participate/registration/departuredetails/">here</a> before <b>June 15, 2013</b>.</p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> The necessary departure details have been provided for all members of your delegation. However, you can still modify them <a href="http://www.ibo2013.org/Participate/registration/departuredetails/">here</a>.</p>';				
			}
		}
		
		
		//Payment
		//country=29, jury=30, admin=2, observing country=34, observer=31
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 31){
			$xhtml.='<p class="subtitle">'.$number.'. Payment</p>';		
			++$number;
			
			//amount due? Payment received?
			$query='select ifnull(of.tot_fee,0)+ifnull(trf.tot_fee,0) as tot_fee, ifnull(pay.tot_payed,0) as tot_payed, ifnull(of.tot_fee,0)+ifnull(trf.tot_fee,0)-ifnull(pay.tot_payed,0) as amount_due		
		from ibo2013_countries c 
		left join (select ifnull(sum(fee), 0) as tot_fee, country_id from ibo2013_observer_registration group by country_id) of on c.id=of.country_id
		left join (select sum(fee) as tot_fee, country_id from ibo2013_team_registration group by country_id) trf on trf.country_id=c.id			
		left join (select sum(p.amount) as tot_payed, country_id from ibo2013_payment p group by p.country_id) pay on c.id=pay.country_id 		
		where c.id='.$this->getMyCountryId($sql);
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		$res=$res[0];		
		if($res['tot_payed']==0){
			$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> We have not yet received any payments. Please make your payment via bank transfer and make sure it will have reached us <b>by June 19</b>. More details as well as your invoice can be found <a href="http://www.ibo2013.org/Participate/registration/payment/">here</a></p>';
		} else {
			if($res['amount_due']<10){
				$xhtml.='<p class="text"><img src="/webcontent/images/tickboxok.png" style="float:left;padding-right:10px;"> We have received payments over <b>CHF '.$res['tot_payed'].'</b> so far. Thanks you very much for paying all necessary fees! Your invoice can still be found <a href="http://www.ibo2013.org/Participate/registration/payment/">here</a></p>';
			} else {
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"> We have received payments over CHF '.$res['tot_payed'].' so far. Please pay the remaining <b>CHF '.$res['amount_due'].'</b> via bank transfer and make sure it will have reached us <b>by June 19</b>. More details as well as your invoice can be found <a href="http://www.ibo2013.org/Participate/registration/payment/">here</a></p>';
			}
		}
			
			
			
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
	
	
	protected function readMemberDetails($user_id, &$sql){
		$query='select p.*, d.category, d.id as cat_id, d.class as cat_class, c.en as country from ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_countries c where p.delegation_category_id=d.id and p.user_id='.$user_id.' and c.id=p.country_id order by d.id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 		
		$this->user_details=$res[0];
		$_SESSION['ibo2013_teammemberdetails_edituser_details']=$this->user_details;
	}	
} ?>
