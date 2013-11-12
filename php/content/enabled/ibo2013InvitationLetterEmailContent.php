<?php
class ibo2013InvitationLetterEmailContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $userCountryId = -1;
	
	
	public function display()
	{
		$xhtml = $this->process_msg;
		if($this->process_status) $emailForm=$this->remakeForm('coordinator_email_form');		
		else $emailForm=$this->getForm('coordinator_email_form');
		$xhtml .= $emailForm->getHtml($this->id);
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
		$query='select e.* from ibo2013_coordinators_email e where country_id='.$this->getMyCountryId($sql).' order by e.name asc';		
		$sql->start_transaction(); 		
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		return $res;
	}
	
	public function process_coordinator_email_form(){
		$frm = $this->getForm('coordinator_email_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		//read out DB	
		$sql = SqlQuery::getInstance();
		$coor_email=$this->readEmailCoordinatorsFromDB($sql);
		
		//update existing ones
		foreach($coor_email as $ce){
			$query='';
			$process_msg='';
			if(trim($values['email_'.$ce['id']])==''){
				//delete
				$query='delete from ibo2013_coordinators_email where id='.$ce['id'];
				$process_msg = '<p class="success">E-mail contact "'.$ce['email'].'" has been removed.</p>';
			} else {
				if(trim($values['email_'.$ce['id']])!=$ce['email'] || trim($values['name_'.$ce['id']])!=$ce['name'] || $values['title_'.$ce['id']]!=$ce['title_id']){
					//update
					$query='update ibo2013_coordinators_email set title_id='.$values['title_'.$ce['id']].', name="'.$values['name_'.$ce['id']].'", email="'.$values['email_'.$ce['id']].'" where id='.$ce['id'];			
					$process_msg = '<p class="success">E-mail contact "'.$values['email_'.$ce['id']].'" has been updated.</p>';
				}
			}
			if($query!=''){
				$sql->start_transaction(); 
				$res=$sql->simpleQuery($query);
				$ok = $sql->end_transaction(); 
				if(!$ok){
					$this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
					sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013InvitationLetterContent', $query);
				} else {
					$this->process_msg .= $process_msg;
				}
			}
		}
		
		//add new one
		if(trim($values['email_new'])!=''){			
			$query='insert into ibo2013_coordinators_email (country_id, title_id, name, email) values ('.$this->getMyCountryId($sql).','.$values['title_new'].', "'.$values['name_new'].'", "'.$values['email_new'].'")';
			$sql->start_transaction(); 
			$res=$sql->simpleQuery($query);
			$ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed! An email about this was sent to the administrator.</p>';
				 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013InvitationLetterContent', $query);
			 } else {
				$this->process_msg .= '<p class="success">E-mail contact "'.$values['email_new'].'" has been added.</p>';				
			}
		}	
			
	}
	
	protected function coordinator_email_form($vector){	
		//read from DB
		$sql = SqlQuery::getInstance();
		$coor_email=$this->readEmailCoordinatorsFromDB($sql);
				
		//prepare titles from DB
		$sql->start_transaction(); 
		$query='select * from ibo2013_titles';
		$tmp = $sql->simpleQuery($query);
		$ok = $sql->end_transaction(); 
		$titles=array();
		
		foreach($tmp as $t){
			$titles[$t['id']]=$t['title'];
		}
			
		$newForm = new AccurateForm(__METHOD__);
		$newForm->setGridSize(count($coor_email)+3,2);
		$newForm->setVector($vector);
		$newForm->setColName(-1, '<p class="formpartsubtitle">Name</p>');
		$newForm->setColName(0, '<p class="formpartsubtitle">E-Mail</p>');
	
		//add known entries	
		$row=0;
		foreach($coor_email as $ce){
			$title = new Select('','title_'.$ce['id'],$titles, $ce['title_id']);
			$newForm->putElementMulticol('title_'.$ce['id'],$row,-1,1,$title);

			$em = new Input('','text','email_'.$ce['id'],$ce['email'],array('size'=>27));
			$em->addRestriction(new IsEmptyOrEmailRestriction());
			$name = new Input('','text','name_'.$ce['id'],$ce['name'],array('size'=>20));
			$name->addRestriction(new StrlenRestrictionIfEmailNotEmpty(1,100, $em));
			
			$newForm->putElementMulticol('name_'.$ce['id'],$row,0,1,$name);			
			$newForm->putElementMulticol('email_'.$ce['id'], $row,1,1,$em);
			
			++$row;
		}
		
		//add empty one
		$title = new Select('','title_new',$titles);
		$newForm->putElementMulticol('title_new',$row,-1,1,$title);

		$em = new Input('','text','email_new','',array('size'=>27));
		$em->addRestriction(new IsEmptyOrEmailRestriction());
		
		$name = new Input('','text','name_new','',array('size'=>20));
		$name->addRestriction(new StrlenRestrictionIfEmailNotEmpty(1,100, $em));
		$newForm->putElementMulticol('name_new',$row,0,1,$name);		
		$newForm->putElementMulticol('email_new', $row,1,1,$em);
		
		$newForm->putElementMulticol('spacer2', $row+1,0,3, new HtmlElement('<p class="formparttitle"></p>') );	
		
		$newForm->putElementMulticol('submit_email', $row+2, 0,3, new Submit('submit_email','save'));
		
		return $newForm;	
	}

}
?>

