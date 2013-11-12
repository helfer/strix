<?php
class ibo2013CompletingPersonalDetailsContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $userCountryId = -1;	
	
	protected $user_to_edit=0;
	protected $user_details=array();
	
	protected $single_room_fee = 400;
	
	protected $imagedir = 'images/participant_pictures/';
	protected $imagedir_teacher = 'images/teacher_pictures/';
	protected $declformdir = 'downloads/declaration_form/';
		
	public function display(){				
		$sql = SqlQuery::getInstance();	

		//show process messages
		$xhtml = $this->process_msg;
		
		//which user to edit?
		//when processing problem, keep old id
		if(!$this->process_status && isset($_SESSION['ibo2013_teammemberdetails_edituserid']))  $this->user_to_edit=$_SESSION['ibo2013_teammemberdetails_edituserid'];
		else {
			//Students, Visitors and Volunteers can only edit their own details
			if($GLOBALS['user']->primary_usergroup_id == 32 || $GLOBALS['user']->primary_usergroup_id == 33 || $GLOBALS['user']->primary_usergroup_id == 35){
				$this->user_to_edit=$GLOBALS['user']->id; 
			} else if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 2 || $GLOBALS['user']->primary_usergroup_id == 31 || $GLOBALS['user']->primary_usergroup_id == 34){
				//Jury, country, observer, observing country, admin and editor can modify all members of their country
				//take from session if present, otherwise pick first
				if(isset($_SESSION['ibo2013_teammemberdetails_edituserid']) && is_numeric($_SESSION['ibo2013_teammemberdetails_edituserid']) && $_SESSION['ibo2013_teammemberdetails_edituserid']>0){
					 $this->user_to_edit=$_SESSION['ibo2013_teammemberdetails_edituserid'];
				} else {
					$query='select p.user_id from ibo2013_participants p where p.country_id='.$this->getMyCountryId($sql);
					$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
					if(count($res)>0) $this->user_to_edit=$res[0]['user_id'];
					else {
						$this->user_to_edit=0;					
					}
				}
			} else {
				$this->user_to_edit=0;
			}
		}
		$_SESSION['ibo2013_teammemberdetails_edituserid']=$this->user_to_edit;		

		$xhtml.='<p class="title">Complete Personal Details</p>';
		
		$canChoose=false;
		//if Jury, country, observer, observing country or admin show form to choose member
		if($GLOBALS['user']->primary_usergroup_id == 29 || $GLOBALS['user']->primary_usergroup_id == 30 || $GLOBALS['user']->primary_usergroup_id == 31 || $GLOBALS['user']->primary_usergroup_id == 34 || $GLOBALS['user']->primary_usergroup_id == 2 ){
			//are there any to choose?
			$tochoose=$this->getConfirmedMembers($sql);
			if(count($tochoose)>0){			
				$xhtml.='<p class="subtitle">Choose / Switch Delegation Member</p>';
				$frm = $this->getForm('choose_team_member_form');
				$xhtml .= $frm->getHtml($this->id);			
				$canChoose=true;				
			} else {
				$xhtml.='<p class="formparttitle">No members of your delegation are confirmed yet!</p>';
				return $xhtml;
			}
		}		

		if($this->user_to_edit>0){		
			//show form modify / complete personal details							
			if($this->process_status) $frm = $this->remakeForm('student_personal_details_form'); 
			else {
				$this->readMemberDetails($this->user_to_edit, $sql);
				$frm = $this->getForm('student_personal_details_form'); 						
			}
			$xhtml .= $frm->getHtml($this->id);
		} else {			
			$_SESSION['ibo2013_teammemberdetails_edituser_details']=array();
			if(!$canChoose) $xhtml.='<p class="formparttitle">Please confirm your team members first!</p>';
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
	
	public function readSignedupMembers(&$sql){
		$query='select p.*, d.category, d.id as cat_id, d.two_letter_code as cat_code, c.alpha3 from ibo2013_participants p, ibo2013_delegation_categories d, ibo2013_countries c where p.delegation_category_id=d.id and p.country_id='.$this->getMyCountryId($sql).' and c.id=p.country_id order by d.id asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		return $res;
	}
	
	public function getConfirmedMembers(&$sql){
		$hist=$this->readSignedupMembers($sql);
		
		//select with those that are not yet registered
		$tochoose=array();
		foreach($hist as $r){
			if($r['user_id']!=0) $tochoose[$r['user_id']]=$r['first_name'].' '.strtoupper($r['last_name']).' ('.$r['category'].')';
		}
		
		return $tochoose;
	}
	
	protected function readMemberDetails($user_id, &$sql){				
		$query='select p.*, d.category, d.id as cat_id, d.class as cat_class, c.en as country from ibo2013_participants p left join ibo2013_countries c on p.country_id=c.id, ibo2013_delegation_categories d where p.delegation_category_id=d.id and p.user_id='.$user_id.' order by d.id asc';		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 		
		$this->user_details=$res[0];
		$_SESSION['ibo2013_teammemberdetails_edituser_details']=$this->user_details;		
	}
	

	public function process_choose_team_member_form(){				
		$frm = $this->getForm('choose_team_member_form');
		$values = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();	
		$query='select count(id) as num from ibo2013_participants where user_id='.$values['team_member'].' and country_id='.$this->getMyCountryId($sql);
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		if($res[0]['num']==1) $_SESSION['ibo2013_teammemberdetails_edituserid']=$values['team_member'];
		else $_SESSION['ibo2013_teammemberdetails_edituserid']=0;		
	}
	
	public function process_student_personal_details_form(){		
		$this->process_status=false;

		$frm = $this->getForm('student_personal_details_form');
		$values = $frm->getElementValues();	
		
		$validate=$frm->validate();		
		if(!$validate){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			$this->modify_id=$values['modify_member_id'];
			return false;
		}
		
		$this->process_status=true;
		$sql = SqlQuery::getInstance();	 
		
		//update DB
		//birthday		
		$values['birthday']=$values['birthday']['year'].'-'.$values['birthday']['month'].'-'.$values['birthday']['day'];
		unset($values['bdayYear']); unset($values['bdayMonth']); unset($values['bdayDay']);
		//unset photo fields, submit and hidden
		unset($values['photo']); unset($values['teacherphoto']); unset($values['submit_modify_member']); unset($values['modify_member_id']); unset($values['codex']); unset($values['maxsize']); unset($values['declaration_form']);
		//checkboxes		
		if($values['vegi_halal'][0]==1) $values['vegi_halal']=1; else $values['vegi_halal']=0; 				
		if($values['email_yearbook'][0]==1) $values['email_yearbook']=1; else $values['email_yearbook']=0; 
		if($values['birthday_yearbook'][0]==1) $values['birthday_yearbook']=1; else $values['birthday_yearbook']=0; 
		if($values['photo_on_web'][0]==1) $values['photo_on_web']=1; else $values['photo_on_web']=0; 				
		if(isset($values['single_room_fix'])){
			unset($values['single_room_fix']);
			unset($values['single_room']);
		} else {
			if(isset($values['single_room'])){		
				if($values['single_room'][0]==1) $values['single_room']=1; else $values['single_room']=0; 					
			}
		}
		
		
		$sql->start_transaction(); $res=$sql->updateQuery('ibo2013_participants', $values, array('user_id'=>$_SESSION['ibo2013_teammemberdetails_edituserid'])); $ok=$sql->end_transaction(); 
		if($ok) $this->process_msg .= '<p class="success">Changes have been saved successfully!</p>';
		else {
			$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
			//RACTIVATE
			//sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013CompletingPersonalDetailsContent', print_r($values, true).'<br/><br/>'.$_SESSION['ibo2013_teammemberdetails_edituserid']);
			return false;
		}

		//force reload of details from DB		
		$this->readMemberDetails($_SESSION['ibo2013_teammemberdetails_edituserid'], $sql);		
		
		//new declaration form?
		if(isset($GLOBALS['HTTP_POST_FILES']['declaration_form']) && $GLOBALS['HTTP_POST_FILES']['declaration_form']['size']>0){
			$dir=HTML_DIR.$this->mConfValues['file_directory'].'webcontent/'.$this->declformdir;		
			
			//delete old images, if they exists
			if(isset($_SESSION['ibo2013_teammemberdetails_edituser_details']['declaration_form_name']) && $_SESSION['ibo2013_teammemberdetails_edituser_details']['declaration_form_name']!=''){
				$existing_files=scandir($dir);
				foreach($existing_files as $ff){
					if(strpos($ff, $_SESSION['ibo2013_teammemberdetails_edituser_details']['declaration_form_name'])!==false && strpos($ff, $this->user_details['declaration_form_name'])==0){
						unlink($dir.$ff);
					}
				}
			}
			
			//construct filename for photo from user data (stored in $this->user_details)
			$declform_name='declform_'.substr(preg_replace('/[^a-zA-Z]/s', '', $_SESSION['ibo2013_teammemberdetails_edituser_details']['last_name']), 0, 5).substr(preg_replace('/[^a-zA-Z]/s', '', $_SESSION['ibo2013_teammemberdetails_edituser_details']['first_name']), 0, 5).'_'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['individual_id'].'_'.date('dmyHis');
									
			//copy declaration form
			$uploadedfile = $GLOBALS['HTTP_POST_FILES']['declaration_form'];
			$ext=explode('.', $uploadedfile['name']);				
			$declform_name.='.'.$ext[count($ext)-1];			
			if(!move_uploaded_file($uploadedfile['tmp_name'], $dir.$declform_name)){
				$this->process_msg .= '<p class="error">Uploading declaration form failed!</p>';
				return false;
			} else chmod($dir.$declform_name, 0644);
			
			//update DB
			$query='update ibo2013_participants set declaration_form_name="'.$declform_name.'" where user_id='.$_SESSION['ibo2013_teammemberdetails_edituserid'];
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok=$sql->end_transaction(); 
			$this->process_msg .= '<p class="success">Declaration form has been sucessfully updated</p>';
			
			//force reload of details from DB
			$this->readMemberDetails($_SESSION['ibo2013_teammemberdetails_edituserid'], $sql);
		}
		
		//new photo?
		if(isset($GLOBALS['HTTP_POST_FILES']['photo']) && $GLOBALS['HTTP_POST_FILES']['photo']['size']>0){
			$dir=HTML_DIR.$this->mConfValues['file_directory'].'webcontent/'.$this->imagedir;		
			
			//delete old images, if they exists
			if(isset($_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_basename']) && $_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_basename']!=''){
				$existing_files=scandir($dir);
				foreach($existing_files as $ff){
					if(strpos($ff, $_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_basename'])!==false && strpos($ff, $this->user_details['photo_basename'])==0){
						unlink($dir.$ff);
					}
				}
			}
			
			//construct filename for photo from user data (stored in $this->user_details)
			$photo_filename=substr(preg_replace('/[^a-zA-Z]/s', '', $this->user_details['last_name']), 0, 5).substr(preg_replace('/[^a-zA-Z]/s', '', $this->user_details['first_name']), 0, 5).'_'.$this->user_details['individual_id'].'_'.date('dmyHis');
			
			//resize
			$uploadedfile = $GLOBALS['HTTP_POST_FILES']['photo'];
			$ext = substr(strrchr($uploadedfile['name'], "."), 1);
			$src=0;
			switch($ext){
				case 'jpg':
				case 'jpeg':
				case 'JPG':
				case 'JPEG': $src = imagecreatefromjpeg($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name']); break;
				case 'png':
				case 'PNG': $src = imagecreatefrompng($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name']); break;
			}
			list($width,$height)=getimagesize($uploadedfile['tmp_name']);

			//first, crop image to be a square
			if($width>$height){
				//crop middle of width
				$crops=round(($width-$height)/2);
				$tmp_sq=imagecreatetruecolor($height, $height);
				imagecopy($tmp_sq,$src,0,0,$crops,0,$width,$height);			
			} elseif($height>$width){
				//crop middle of height
				$crops=round(($height-$width)/2);
				$tmp_sq=imagecreatetruecolor($width, $width);
				$height=$width;
				imagecopy($tmp_sq,$src,0,0,0,$crops,$width,$height);
			} else {
				//is already a square
				$tmp_sq=$src;
			}	
			
			foreach(array(100, 120, 150, 160,180,200,250,300) as $newheight){
				$tmp=imagecreatetruecolor($newheight,$newheight);
				imagecopyresampled($tmp,$tmp_sq,0,0,0,0,$newheight,$newheight, $height,$height);
				if(!imagejpeg($tmp,$dir.$photo_filename.'_'.$newheight.'px.jpg',95)){
					$this->process_msg .= '<p class="error">Upload failed!</p>';
					return false;
				}
				imagedestroy($tmp);
			}
			imagedestroy($src);
			
			//update DB
			$query='update ibo2013_participants set photo_basename="'.$photo_filename.'" where user_id='.$_SESSION['ibo2013_teammemberdetails_edituserid'];
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok=$sql->end_transaction(); 
			$this->process_msg .= '<p class="success">Photo has been sucessfully updated</p>';
			
			//force reload of details from DB
			$this->readMemberDetails($_SESSION['ibo2013_teammemberdetails_edituserid'], $sql);
		}
		
		//new teacher photo?
		if(isset($GLOBALS['HTTP_POST_FILES']['teacherphoto']) && $GLOBALS['HTTP_POST_FILES']['teacherphoto']['size']>0){
			$dir=HTML_DIR.$this->mConfValues['file_directory'].'webcontent/'.$this->imagedir_teacher;		
			
			//delete old images, if they exists
			if(isset($_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename']) && $_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename']!=''){
				$existing_files=scandir($dir);
				foreach($existing_files as $ff){
					if(strpos($ff, $_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename'])!==false && strpos($ff, $this->user_details['teacher_photo_basename'])==0){
						unlink($dir.$ff);
					}
				}
			}
			
			//construct filename for photo from user data (stored in $this->user_details)
			$teacher_filename='teacher_'.substr(preg_replace('/[^a-zA-Z]/s', '', $_SESSION['ibo2013_teammemberdetails_edituser_details']['last_name']), 0, 5).substr(preg_replace('/[^a-zA-Z]/s', '', $_SESSION['ibo2013_teammemberdetails_edituser_details']['first_name']), 0, 5).'_'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['individual_id'].'_'.date('dmyHis');
			
			//make thumbnails
			$uploadedfile = $GLOBALS['HTTP_POST_FILES']['teacherphoto'];
			$ext = substr(strrchr($uploadedfile['name'], "."), 1);
			$src=0;
			switch($ext){
				case 'jpg':
				case 'jpeg':
				case 'JPG':
				case 'JPEG': $src = imagecreatefromjpeg($GLOBALS['HTTP_POST_FILES']['teacherphoto']['tmp_name']); break;
				case 'png':
				case 'PNG': $src = imagecreatefrompng($GLOBALS['HTTP_POST_FILES']['teacherphoto']['tmp_name']); break;
			}		
			list($width,$height)=getimagesize($uploadedfile['tmp_name']);

			$newheight=120;
			$newwidth=($width/$height)*$newheight;
			$tmp=imagecreatetruecolor($newwidth,$newheight);
			imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight, $width,$height);

			if(!imagejpeg($tmp,$dir.$teacher_filename.'_'.$newheight.'px.jpg',95)){
				$this->process_msg .= '<p class="error">Creating / uploading of thumbnail failed!</p>';
				return false;
			}
			imagedestroy($tmp);
			imagedestroy($src);
			
			//copy original
			if(!move_uploaded_file($uploadedfile['tmp_name'], $dir.$teacher_filename.'_original.jpg')){
				$this->process_msg .= '<p class="error">Uploading original failed!</p>';
				return false;
			} else chmod($dir.$teacher_filename.'_original.jpg', 0644);
			
			//update DB
			$query='update ibo2013_participants set teacher_photo_basename="'.$teacher_filename.'" where user_id='.$_SESSION['ibo2013_teammemberdetails_edituserid'];
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok=$sql->end_transaction(); 
			$this->process_msg .= '<p class="success">Teacher photo has been sucessfully updated</p>';
			
			//force reload of details from DB
			$this->readMemberDetails($_SESSION['ibo2013_teammemberdetails_edituserid'], $sql);
		}
	}
	
	protected function choose_team_member_form($vector){
		$sql = SqlQuery::getInstance();	
		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(200,400);
		
		$tochoose=$this->getConfirmedMembers($sql);
		
		$who = new Select('Delegation member:','team_member', $tochoose, $_SESSION['ibo2013_teammemberdetails_edituserid']);
		$newForm->addElement('team_member',$who);

		$newForm->addElement('submit_choose_team_member', new Submit('submit_choose_team_member','change'));

		return $newForm;
	}
	
	protected function student_personal_details_form($vector){			
		//read missing members from DB
		$sql = SqlQuery::getInstance();	
		
		$newForm = new AccurateForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$newForm->setGridSize(40,4);
		$newForm->setVector($vector);
		$newForm->setColWidth(array(200,10,20,10,380));
		$row=0;	
		
		//Write Category, Name and email -> thinsg that cannot be changed!
		$newForm->putElementMulticol('titleUnchangeable', $row,-1,5, new HtmlElement('<p class="formparttitle">Personal Details</p>') );	
		++$row;	
			
		$newForm->setRowName($row, 'Category:');		
		$newForm->putElementMulticol('category', $row,0,4,new HtmlElement('<p class="formtext"><b>'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['category'].'</b></p>'));
		++$row;

		$newForm->setRowName($row, 'Name:');
		$newForm->putElementMulticol('first_name', $row,0,4,new HtmlElement('<p class="formtext"><b>'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['first_name'].' '.strtoupper($_SESSION['ibo2013_teammemberdetails_edituser_details']['last_name']).'</b></p>'));
		++$row;	
		
		$newForm->setRowName($row, 'Sex: ');		
		$newForm->putElementMulticol('sex',$row,0,4,new HtmlElement('<p class="formtext"><b>'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['sex'].'</b></p>'));
		++$row;
	
		$newForm->setRowName($row, 'E-Mail: ');
		$newForm->putElementMulticol('email', $row,0,4,new HtmlElement('<p class="formtext"><b>'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['email'].'</b></p>'));
		++$row;

		//Birthday
		$bd_day='';
		$bd_month='';
		$bd_year='';
		$bd=strtotime($_SESSION['ibo2013_teammemberdetails_edituser_details']['birthday']);
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['birthday']!='0000-00-00' && date('Y', $bd)>1920){
			$bd_day=date('j', $bd);
			$bd_month=date('n', $bd);
			$bd_year=date('Y', $bd);
		} 
		
		//Birthday
		$bd_day='';
		$bd_month='';
		$bd_year='';
		$bd=strtotime($_SESSION['ibo2013_teammemberdetails_edituser_details']['birthday']);
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['birthday']!='0000-00-00' && date('Y', $bd)>1920){
			$bd_day=date('j', $bd);
			$bd_month=date('n', $bd);
			$bd_year=date('Y', $bd);
		} 
		$newForm->setRowName($row, 'Birthday: ');
		$bdayDay = new DateWithThreeInputs('','birthday',array('day'=>'day', 'month'=>'month', 'year'=>'year'),array('day'=>$bd_day,'month'=>$bd_month, 'year'=>$bd_year), array('begin'=>'1920-01-01', 'end'=>'2000-01-01'));		
		$newForm->putElementMulticol('birthday',$row,0,4,$bdayDay);
		++$row;
		
		//sizes of cloth
		$newForm->setRowName($row, 'T-Shirt Size (see <a href="http://www.ibo2013.org/webcontent/downloads/Size Chart.pdf">chart</a>): ');
		$tshirt = new Select('','tshirt_size',array('XS'=>'XS', 'S'=>'S', 'M'=>'M', 'L'=>'L', 'XL'=>'XL', 'XXL'=>'XXL'), $_SESSION['ibo2013_teammemberdetails_edituser_details']['tshirt_size']);
		$newForm->putElementMulticol('tshirt_size',$row,0,4,$tshirt);
		++$row;
		
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_id']==1){
			$newForm->setRowName($row, 'Lab Coat Size (see <a href="http://www.ibo2013.org/webcontent/downloads/Size Chart.pdf">chart</a>): ');
			$labcoat = new Select('','labcoat_size',array('XS'=>'XS', 'S'=>'S', 'M'=>'M', 'L'=>'L', 'XL'=>'XL', 'XXL'=>'XXL'), $_SESSION['ibo2013_teammemberdetails_edituser_details']['labcoat_size']);
			$newForm->putElementMulticol('labcoat_size',$row,0,4,$labcoat);
			++$row;	
		}
		
		//Food Restrictions
		$newForm->putElementMulticol('titleFood', $row,-1,5, new HtmlElement('<p class="formparttitle">Food Restrictions</p>'));	
		++$row;
		
		$newForm->setRowName($row, 'Vegetarian / Halal: ');		
		$vegihalal=new CheckboxWithText('','vegi_halal',true,'I prefer vegetarian / halal food.', $_SESSION['ibo2013_teammemberdetails_edituser_details']['vegi_halal']==1);		
		$newForm->putElementMulticol('vegi_halal',$row,0,4,$vegihalal);	++$row;

		$newForm->putElementMulticol('halalnote', $row,-1,5, new HtmlElement('<p class="text"><b>NOTE</b>: All our vegetarian dishes will be halal. All meat dishes are served with neither pork nor alcohol (exceptions will be clearly declared).</p>'));	++$row;
		
		$newForm->setRowName($row, 'Other food restrictions: ');
		$foodrest = new Textarea('','food_restrictions',$_SESSION['ibo2013_teammemberdetails_edituser_details']['food_restrictions'],3,45);
		$newForm->putElementMulticol('food_restrictions',$row,0,4,$foodrest);		
		++$row;
		
		//Ramadan
		$newForm->putElementMulticol('titleramadan', $row,-1,5, new HtmlElement('<p class="formparttitle">Ramadan</p>'));	
		++$row;
		
		$newForm->putElementMulticol('ramadantext', $row,-1,5, new HtmlElement('<p class="text">If you are observing Ramadan, please indicate your fasting times as precise as possible (see <a href="http://www.timeanddate.com/worldclock/astronomy.html?n=270&month=7&year=2013&obj=sun&afl=-11&day=1">here</a> for local sunrise / sunset times).'));
		++$row;
		
		$newForm->setRowName($row, 'I\'ll be fasting: ');
		$newForm->putElement('fatsing_from_html', $row,0, new HtmlElement('<p class="formtext">from</p>') );
		$fastingfrom = new Input('','text','fasting_from',$_SESSION['ibo2013_teammemberdetails_edituser_details']['fasting_from'],array('size'=>6));		
		$fastingfrom->addRestriction(new IsEmptyOrStrlenRestriction(1,20));
		$newForm->putElement('fasting_from',$row,1,$fastingfrom);		
		$newForm->putElement('fatsing_to_html', $row,2, new HtmlElement('<p class="formtext">to</p>') );
		$fastingto = new Input('','text','fasting_to',$_SESSION['ibo2013_teammemberdetails_edituser_details']['fasting_to'],array('size'=>6));		
		$fastingto->addRestriction(new IsEmptyOrStrlenRestriction(1,20));
		$newForm->putElementMulticol('fasting_to',$row,3,1,$fastingto);
		++$row;
		
		
		//student Declaration Form
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_id']==1){			
			$newForm->putElementMulticol('titleDeclForm', $row,-1,5, new HtmlElement('<p class="formparttitle">Student Declaration Form</p>'));	
			++$row;
		
			$newForm->putElementMulticol('declFormText', $row,-1,5, new HtmlElement('<p class="text">All students must complete a declaration form and have it signed and stamped by the principle of their school. The declaration form must be scanned and uploaded <b>before June 15</b>, 2013. Additionally, you have to hand in the <b>original declaration form</b> during registration in Switzerland<br/>The declaration form can be downloaded <a href="http://www.ibo2013.org/createPDF/?pdf=declaration_form">here</a> in pdf format.</p>'));
			++$row;		
			
			$newForm->setRowName($row, 'Current Declaration Form:');
			if($_SESSION['ibo2013_teammemberdetails_edituser_details']['declaration_form_name']!=''){
				$newForm->putElementMulticol('current_decl_form', $row,0,4, new HtmlElement('<a href="/webcontent/downloads/declaration_form/'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['declaration_form_name'].'" target="_blank">Download your current form here.</a>'));			
			} else {
				$newForm->putElementMulticol('current_decl_form', $row,0,4, new HtmlElement('<p class="error"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;">No declaration form has been uploaded yet.</p>'));	
			}
			++$row;		
					
			$newForm->setRowName($row, 'New declaration form<br/>(jpg or pdf):');
			$declform = new FileInput('', 'declaration_form');
			$declform->addRestriction(new isEmptyOrPDFOrImageRestriction('declaration_form', 5*1100000));
			$newForm->putElementMulticol('declaration_form',$row,0,4,$declform);	
			++$row;
						
		}
	
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_id']!=5){	
			//Year Book
			$newForm->putElementMulticol('titleYearBook', $row,-1,5, new HtmlElement('<p class="formparttitle">Yearbook</p>'));	
			++$row;
			
			$newForm->putElementMulticol('yearbooktext', $row,-1,5, new HtmlElement('<p class="text">We intend to prepare a yearbook that will be distributed to you and everybody else who participates in the IBO. Please provide additional information you would like to be added to the yearbook.'));	++$row;		

			$newForm->setRowName($row, 'E-mail address: ');		
			$emailyb=new CheckboxWithText('','email_yearbook',1, 'Yes, add my e-mail address to the yearbook.', $_SESSION['ibo2013_teammemberdetails_edituser_details']['email_yearbook']==1);		
			$newForm->putElementMulticol('email_yearbook',$row,0,4,$emailyb);
			++$row;

			$newForm->setRowName($row, 'Birthday: ');		
			$emailyb=new CheckboxWithText('','birthday_yearbook',1, 'Yes, add my birthday to the yearbook.', $_SESSION['ibo2013_teammemberdetails_edituser_details']['birthday_yearbook']==1);		
			$newForm->putElementMulticol('birthday_yearbook',$row,0,4,$emailyb);
			++$row;
			
			$newForm->setRowName($row, 'Nickname: ');
			$nickname = new Input('','text','nickname',$_SESSION['ibo2013_teammemberdetails_edituser_details']['nickname'],array('size'=>20));
			$nickname->addRestriction(new IsEmptyOrStrlenRestriction(1,50));
			$newForm->putElementMulticol('nickname',$row,0,4,$nickname);
			++$row;
			
			$newForm->setRowName($row, 'Skype name: ');
			$skypename = new Input('','text','skype_name',$_SESSION['ibo2013_teammemberdetails_edituser_details']['skype_name'],array('size'=>20));
			$skypename->addRestriction(new IsEmptyOrStrlenRestriction(1,50));
			$newForm->putElementMulticol('skype_name',$row,0,4,$skypename);
			++$row;
				
			//FOTO....
			$newForm->setRowName($row, 'Current photo:');
			if($_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_basename']!=''){
				$newForm->putElementMulticol('current_photo', $row,0,4, new HtmlElement('<img src="/webcontent/images/participant_pictures/'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_basename'].'_120px.jpg">'));			
			} else {
				$newForm->putElementMulticol('current_photo', $row,0,4, new HtmlElement('<p class="error"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;">No photo has been uploaded yet.</p>'));	
			}
			++$row;		
						
			//$newForm->putElementMulticol('maxsize', $row, 4,1,new Hidden('MAX_FILE_SIZE',10*1100000));
			//++$row;
			$newForm->setRowName($row, 'New photo<br/> (jpg or png, height > 600px):');
			$photo = new FileInput('', 'photo');
			$photo->addRestriction(new isEmptyOrImageRestriction('photo', 10*1100000, 0, 300));
			$newForm->putElementMulticol('photo',$row,0,4,$photo);	
			++$row;
			
			$newForm->putElementMulticol('phototext', $row,0,5, new HtmlElement('<p class="formtext">Note: your photo will be cropped to a square.'));	++$row;			
			$newForm->putElementMulticol('spacer3', $row,0,4, new HtmlElement('<p class="text"></p>') );++$row;
			$newForm->setRowName($row, 'Photo on website: ');		
			
			$photoonweb=new CheckboxWithText('','photo_on_web',1, 'Yes, it is OK to show my photo on the delegation page.',$_SESSION['ibo2013_teammemberdetails_edituser_details']['photo_on_web']==1);		
			$newForm->putElementMulticol('photo_on_web',$row,0,4,$photoonweb);
			++$row;
		}
		
		
		//Special Program Friday
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_id']==1){			
			$newForm->putElementMulticol('titlespecialprogram', $row,-1,5, new HtmlElement('<p class="formparttitle">Special Program on Friday July 19, 2013</p>'));	
			++$row;
		
			$newForm->putElementMulticol('specialprogramtext', $row,-1,5, new HtmlElement('<p class="formtext">On Friday July 19, 2013 a special program will take place in the heart of the city of Bern, in front of our national parliament. For this, we kindly ask all students:<br/><br/></p><ul class="list"><li>to bring an object that relates to biology (one per delegation; not bigger then 25 cm in any dimension)</li><li>to bring a standard biology school book (one per delegation)</li><li>to upload a picture of your biology teacher or your biology class room / school building (upload below)</li></ul><br/>')); ++$row;		
				
			$newForm->setRowName($row, 'Current photo:');
			if(isset($_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename']) && $_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename']!=''){
				$newForm->putElementMulticol('current_teacher_photo', $row,0,4, new HtmlElement('<img src="/webcontent/images/teacher_pictures/'.$_SESSION['ibo2013_teammemberdetails_edituser_details']['teacher_photo_basename'].'_120px.jpg">'));			
			} else {
				$newForm->putElementMulticol('current_teacher_photo', $row,0,4, new HtmlElement('<p class="error"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;">No photo has been uploaded yet.</p>'));	
			}
			++$row;		
			
			$newForm->setRowName($row, 'Photo of biology teacher<br/>(jpg or png, height > 900px):');
			$teacherphoto = new FileInput('', 'teacherphoto');
			$teacherphoto->addRestriction(new isEmptyOrImageRestriction('photo', 10*1100000, 0, 600));
			$newForm->putElementMulticol('teacherphoto',$row,0,4,$teacherphoto);	
			++$row;
		}
	
		//Room preference		
		switch($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_id']){
				//volunteers
				case 13:
				case 14:
				case 15:
				case 16:
				case 17:
				case 18:
					$newForm->putElementMulticol('titleroompref', $row,-1,5, new HtmlElement('<p class="formparttitle">Room Preference</p>'));
					++$row;
					$newForm->putElementMulticol('roompreftext', $row,-1,5, new HtmlElement('<p class="formtext">All volunteers will share double, triple rooms or 4 bad dormitories. During the IBO, Team Guides will stay in a different hotel than all other volunteers. If you know other volunteers you would like to share your room with, please provide their names. While we will try hard, we can unfortunately not guarantee any particular room attribution.</p>')); ++$row;
					
					$newForm->setRowName($row, 'Preferred Room Partners: ');
					$roompref = new Textarea('','room_preference',$_SESSION['ibo2013_teammemberdetails_edituser_details']['room_preference'],3,45);
					$newForm->putElementMulticol('room_preference',$row,0,4,$roompref);		
					++$row;
					
					break;
					
				//Jury & Organizers except Budget	
				case 2:
				case 3:
				case 5:
				case 6:
				case 7:
				case 8:
					$newForm->putElementMulticol('titleroompref', $row,-1,5, new HtmlElement('<p class="formparttitle">Room Preference</p>'));
					++$row;
					$newForm->putElementMulticol('roompreftext', $row,-1,5, new HtmlElement('<p class="formtext">By default, Jury Members, Observer and Visitors will share double rooms with other participants of the same sex. Below you have the possibility to provide names of people you would prefer to share your room with. While we will try hard, we can unfortunately not guarantee any particular room attribution.<br/>Alternatively, Jury Members, Observer and Visitors also have the option to book a single room at an additional fee of CHF '.$this->single_room_fee.' by checking the appropriate box below. Single rooms are available in a limited number <b>until June 15, 2013</b>, only. <br/><br/></p>')); ++$row;
					
					$newForm->setRowName($row, 'Preferred Room Partners: ');
					$roompref = new Textarea('','room_preference',$_SESSION['ibo2013_teammemberdetails_edituser_details']['room_preference'],3,45);
					$newForm->putElementMulticol('room_preference',$row,0,4,$roompref);		
					++$row;
					$newForm->putElementMulticol('spacer10', $row,0,4, new HtmlElement('<p class="formparttitle"></p>') );
					++$row;
					$newForm->setRowName($row, 'Single Room: ');	
					//until until June 15!!					
					if(date('Ymd')<20130616){
						$singleroom=new CheckboxWithText('','single_room',TRUE, 'Yes, I\'d like to book a single room for an addition CHF '.$this->single_room_fee.'.', $_SESSION['ibo2013_teammemberdetails_edituser_details']['single_room']==1);
						$newForm->putElementMulticol('single_room',$row,0,4,$singleroom);					
						++$row;
					} else {					
						$singleroom=new CheckboxWithText('','single_room_fix',TRUE, 'Yes, I\'d like to book a single room for an addition CHF '.$this->single_room_fee.'.', $_SESSION['ibo2013_teammemberdetails_edituser_details']['single_room']==1, array('disabled'=>"disabled"));
						$newForm->putElementMulticol('single_room_fix',$row,0,4,$singleroom);	
						++$row;

					}
					
					
					
					break;
		}
		
	
		//Important Information
		$newForm->putElementMulticol('titleimportantinfo', $row,-1,5, new HtmlElement('<p class="formparttitle">Important Information</p>'));	
		++$row;
		
		$info='Please read the following information carefully:<ul class="list">
		<li>All participants in the IBO 2013 have to arrange their own <b>insurance for accidents, health and travelling</b>.';
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Organizer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Volunteer'){
			$info.=' Please ensure that your insurance covers expenses in Switzerland. You may need a special coverage as our country has a very expensive medical system.';
		}
		$info.='</li>';
		$info.='<li>By participating in the IBO you agree to have <b>pictures taken of you</b>. These pictures as well as your name may be published, and may be freely used by all IBO participants.</li>';
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Volunteer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['category']!='Visitor' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Organizer'){
			$info.='<li><b>Students’ declaration form</b> must be filled out, scanned and uploaded in no later than June 15, 2013. The original declaration form has to be handed in during registration.</li>';
		}
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Organizer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Volunteer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['category']!='Visitor' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['category']!='Student'){
			$info.='<li><b>All fees</b> (fee to the Coordinating Center in Prague and IBO 2013 participation fee) must be paid in advance. Non-payment of the fees means no participation in the competition.</li>';
		} 		
		if($_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Organizer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['cat_class']!='Volunteer' && $_SESSION['ibo2013_teammemberdetails_edituser_details']['category']!='Visitor'){
			$info.='<li>All students will have to deposit ALL <b>computers, phones and other communication devices</b> at registration. They will be securely stored and returned after all IBO tests are written.</li>';
		}
		$info.='<li>Any <b>non-respect of the IBO rules</b> may result in your exclusion from the IBO.</li>
		</ul>';
		
		
		
		$newForm->putElementMulticol('importantinfotext', $row,-1,5, new HtmlElement('<p class="text">'.$info.'</p>'));
		++$row;	
		
		$newForm->putElementMulticol('spacer2', $row,0,4, new HtmlElement('<p class="formparttitle"></p>') );
		++$row;
		$newForm->setRowName($row, 'Please accept: ');
		$codex=new CheckboxWithText('','codex',1,'I confirm that I have read and understood all the information given above.');
				
		$codex->addRestriction(new NotFalseRestriction());
		$newForm->putElementMulticol('codex',$row,0,4,$codex);
		++$row;

		//Submit
		$newForm->putElementMulticol('submit_modify_member', $row, 1,4, new Submit('submit_modify_member','save changes'));		
		$newForm->putElement('modify_member_id', $row, 0, new Hidden('modify_member_id', $this->user_to_edit));

		return $newForm;	
	}
}
?>
