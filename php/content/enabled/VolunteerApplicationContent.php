<?php
class VolunteerApplicationContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	
	public function display()
	{
		$xhtml='';
		if($_SESSION['language_abb']=='en'){
			$xhtml.='<p>This form is onyl available in <a href="http://www.ibo2013.org/Mitmachen/Helfen/Anmelden/?lan=de">German</a> or <a href="http://www.ibo2013.org/Participer/Benevoles/s_inscrire/?lan=fr">French</a></p>';
		} else {
			$xhtml = $this->process_msg;
			if(!$this->process_status)
				$xhtml .= $this->getForm('apply_as_volunteer_form')->getHtml($this->id);
		}
		return $xhtml;
		
	}
	
	
	public function process_apply_as_volunteer_form(){
		$frm = $this->getForm('apply_as_volunteer_form');
	
		if(!$frm->validate()){
			switch($_SESSION['language_abb']){
				case 'de': $this->process_msg = '<p class="error">Bitte markierte Angaben überprüfen.</p>'; break;
				case 'fr': $this->process_msg = '<p class="error">Veuiller verifier les champs s.v.p.</p>'; break;
				case 'en': $this->process_msg = '<p class="error">Please verify highlighted fields.</p>'; break;
			}	
			return false;
		}
		$this->process_status=true;
		$values = $frm->getElementValues();
		//modify a few
		unset($values['submit_register']);
		unset($values['email2']);
		$values['birthday']=$values['bdayYear'].'-'.$values['bdayMonth'].'-'.$values['bdayDay'];
		unset($values['bdayYear']);
		unset($values['bdayMonth']);
		unset($values['bdayDay']);
		unset($values['codex']);
	
		$participation=array();
		foreach($values['participation_SSO'] as $k=>$v){
			if($v==1){
				switch($k){
					case 0: $participation[]='Biologie'; break;
					case 1: $participation[]='Chemie'; break;
					case 2: $participation[]='Informatik'; break;
					case 3: $participation[]='Mathematik'; break;
					case 4: $participation[]='Philosophie'; break;
					case 5: $participation[]='Physik'; break;
				}
			}
		}
		$values['participation_SSO']=implode(', ', $participation);
		$participation=array();
		foreach($values['participation_ISO'] as $k=>$v){
			if($v==1){
				switch($k){
					case 0: $participation[]='Biologie'; break;
					case 1: $participation[]='Chemie'; break;
					case 2: $participation[]='Informatik'; break;
					case 3: $participation[]='Mathematik'; break;
					case 4: $participation[]='Philosophie'; break;
					case 5: $participation[]='Physik'; break;
				}
			}
		}
		$values['participation_ISO']=implode(', ', $participation);
		$values['registration_date'] = date('Y-m-d');		
		
		$sql = SqlQuery::getInstance();
		$sql->start_transaction(); 
		$iid = $sql->insertQuery('ibo2013_volunteer_applications',$values);		
		$ok = $sql->end_transaction(); 

		$text='';
		foreach($values as $k=>$v){
			$text.=$k.': '.$v."<br/>";
		}

		if(!$ok){
			switch($_SESSION['language_abb']){
				case 'de': $this->process_msg = '<p class="error">Die Anmeldung ist leider fehlgeschlagen.<br/>Bitte später erneut versuchen.<br/>Eine Fehlermeldung wurde an den Verantwortlichen gesendet.</p>'; break;
				case 'fr': $this->process_msg = '<p class="error">L\'envoi de l\'inscription n\'a pas fonctionné. Veuillez réessayer plus tard. Un message d\'erreur a été envoyé aux responsables des IBO 2013.</p>'; break;
				case 'en': $this->process_msg = '<p class="error">Please verify highlighted fields.</p>'; break;
			}	
			return false;
			sendSBOmail('marco.gerber@olympiads.unibe.ch','Registrierung als Helfer fehlgeschlagen!', $text);		
			return false;
		} else {
			switch($_SESSION['language_abb']){
				case 'de': $this->process_msg = '<p class="text">Die Anmeldung war erfolgreich.<br/> Eine Bestätigungsemail wurde an '.$values['email'].' versandt.</p>'; break;						
				case 'fr': $this->process_msg = '<p class="text">Ton inscription nous est parvenue. Un message de confirmation a été envoyé à l\'adresse '.$values['email'].' indiquée sur le formulaire.</p>'; break;
				case 'en': $this->process_msg = '<p class="text">The application has been submitted sucessfully. A confirmation was sent to '.$values['email'].'</p>'; break;
			}	
			switch($values['corrspondance_language']){
				case 'german': 	$individual_text="Liebe";
						if($values['sex']=='male') $individual_text.="r";
						$individual_text.=" ".$values['first_name']."<br/>Herzlichen Dank für dein Interesse an der IBO 2013! Wir haben Deine Angaben gespeichert und werden Dich in Kürze über das weitere Vorgehen informieren.<br/><br/>Dies ist eine automatisch generierte E-Mail. Für weitere Fragen stehen wir dir aber gerne zur Verfügung:<br/>www.ibo2013.org/kontakt<br/><br/>Mit freundlichen Grüssen<br/>Das IBO 2013 Team.";
						sendSBOmail($values['email'],'Anmeldung als Helfer für die IBO 2013', $individual_text);
						break;
				case 'french': 	$individual_text="";
						if($values['sex']=='male') $individual_text.="Cher ";
						else $individual_text.="chère ";
						$individual_text.=$values['first_name']."<br/> Nous te remercions de ton intérêt pour les IBO 2013! Nous avons sauvegardé tes données et te recontacterons dans un instant pour t'informer sur la suite des événements.<br/><br/>Ce message a été généré automatiquement. Pour de plus amples informations, contacte-nous par <br/>www.ibo2013.org/contact<br/><br/>Meilleures salutations<br/>Le team IBO 2013";
						sendSBOmail($values['email'],'Inscription en tant que bénévole pour les IBO 2013', $individual_text);
						break;
				case 'english':	$individual_text="Dear ".$values['first_name'].",<br/>Thanks for your intrest in the IBO 2013! We have sucessfully received your application and will contact you with further details soon.<br/><br/>This message has been generated automatically. But please, do not hesitate to contact us in case you have further questions:<br/>www.ibo2013.org/kontakt<br/><br/>With kind regards,<br/>The IBO 2013 Team.";
						sendSBOmail($values['email'],'Application as a volunteer at the IBO 2013', $individual_text);
						break;
			}			
			sendSBOmail('marco.gerber@olympiads.unibe.ch',$values['first_name'].' '.$values['last_name'].' hat sich als Helfer für 2013 beworben.', $text);			
			return true;
		}
		
	}

	
	protected function apply_as_volunteer_form($vector){	
		$newForm = new AccurateForm(__METHOD__);
		$newForm->setGridSize(30,4);
		$newForm->setVector($vector);

		$translations=array();

		switch($_SESSION['language_abb']){
			case 'de': $translations=array(
					'titleAnticipatedTask'=>'Bevorzugter Einsatzbereich',
					'titlePeron'=>'Angaben zur Person',
					'fname'=>'Vorname',
					'lname'=>'Nachname',
					'bday'=>'Geburtsdatum<br/>(dd/mm/yyyy)',
					'sex'=>'Geschlecht',
					'male'=>'männlich',
					'female'=>'weiblich',
					'nationality'=>'Nationalität',
					'titleContact'=>'Kontaktangaben',
					'corrspondance_language'=>'Korrespondezsprache',
					'street'=>'Adresse',
					'zip/city'=>'PLZ / Ort',
					'phone'=>'Telefon',
					'em'=>'E-Mail',
					'em2'=>'E-Mail bestätigen',
					'titleExperiences'=>'Bisherige Erfahrungen',
					'education'=>'Studium / Beruf',
					'expertise'=>'Besondere Fähigkeiten / spezielle Interessen',
					'languages'=>'Sprachkenntnisse',
					'languageExplanation'=>'Englisch und eine Landesprache sind Voraussetzung, bitte alle Sprachen angeben.',
					'leader_jobs'=>'Leitertätigkeiten',
					'participationSSO'=>'Teilnahme Schweizer Wissenschaftsolympiaden',
					'participationISO'=>'Teilnahme internationale Wissenschaftsolympiaden',
					'biology'=>'Biologie',
					'chemistry'=>'Chemie',
					'computerScience'=>'Informatik',
					'math'=>'Mathematik',
					'philo'=>'Philosophie',
					'physics'=>'Physik',
					'titleCodex'=>'Ehrenkodex',
					'codex'=>'Ich verpflichte mich den Geist der Wissenschafts-Olympiaden zu respektieren und deren Regeln zu beachten. Loyalität und Fair-play bestimmen mein Handeln und meine Einstellung gegenüber Teilnehmern und Teilnehmerinnen, Helfern und Helferinnen.',
					'titlePrivacy'=>'Datenschutz',
					'privacy'=>'Die angegebenen Daten dienen ausschliesslich dem Einsatz  und der zusammenhängenden Korrespondenz als Helfer bei der IBO 2013 in Bern. Die Daten werden zu diesem Zweck in unserer Datenbank  für die IBO 2013 gespeichert. Sie werden nicht an Dritte weitergegeben.',
					'submit'=>'Bewerbung abschicken');
				   break;
			case 'fr': $translations=array(
					'titleAnticipatedTask'=>'Préférence pour le secteur d’activité',
					'titlePeron'=>'Informations personnelles',
					'fname'=>'Prénom',
					'lname'=>'Nom',
					'bday'=>'Date de naissance<br/>(dd/mm/yyyy)',
					'sex'=>'Sexe',
					'male'=>'M',
					'female'=>'F',
					'nationality'=>'Nationalité',
					'titleContact'=>'Contact',
					'corrspondance_language'=>'Langue de correspondance',
					'street'=>'Adresse',
					'zip/city'=>'Code postal, lieu',
					'phone'=>'Téléphone',
					'em'=>'Courriel',
					'em2'=>'Confirmer le courriel',
					'titleExperiences'=>'Expériences préalables',
					'education'=>'Etudes/profession',
					'expertise'=>'Compétences particulières, intérêts particuliers',
					'languages'=>'Connaissances en langues',
					'languageExplanation'=>'La maîtrise de l’anglais et de l’une de nos langues nationales sont une condition préalable à l’inscription. Veuillez indiquer toutes les langues.',
					'leader_jobs'=>'Activités en tant que moniteur/animateur/coach…',
					'participationSSO'=>'Participation aux Olympiades Scientifiques Suisses',
					'participationISO'=>'Participation aux Olympiades Scientifiques Internationales',
					'biology'=>'biologie',
					'chemistry'=>'chimie',
					'computerScience'=>'informatique',
					'math'=>'mathématiques',
					'philo'=>'philosophie',
					'physics'=>'physique',
					'titleCodex'=>'Code d’honneur',
					'codex'=>'En tant que guide et bénévole je m’engage à respecter l’esprit des Olympiades Scientifiques et ses règles. Mon attitude et mon comportement sont dictés par le fairplay et la loyauté envers les participant/es, les bénévoles et les collègues.',
					'titlePrivacy'=>'Protection des données',
					'privacy'=>'Vos données personnelles sont exclusivement utilisées dans le cadre de votre travail bénévole et la correspondance concernant les IBO 2013. Ces informations sont enregistrées dans notre banque de données IBO 2013. Elles ne sont pas transmises à des tiers.',
					'submit'=>'envoyer');
				   break;
			case 'en': $translations=array(
					'titleAnticipatedTask'=>'Preferred position',
					'titlePeron'=>'Personal information',
					'fname'=>'First name',
					'lname'=>'Last name',
					'bday'=>'Date of birth (dd/mm/yyyy)',
					'sex'=>'Sex',
					'male'=>'male',
					'female'=>'female',
					'nationality'=>'Nationality',
					'titleContact'=>'Contact information',
					'corrspondance_language'=>'Language of correspondance',
					'street'=>'Address',
					'zip/city'=>'zip / city',
					'phone'=>'Telephone',
					'em'=>'E-mail',
					'em2'=>'Confirm e-mail',
					'titleExperiences'=>'Previous experiences',
					'education'=>'Degree / Occupation',
					'expertise'=>'Special skills / specific interests',
					'languages'=>'Language skills',
					'languageExplanation'=>'Fluency in English and one national language is required. Please indiciate all language skills.',
					'leader_jobs'=>'Experience as voluntee, coach or guide',
					'participationSSO'=>'Participation in Swiss Science Olympiads',
					'participationISO'=>'Participation in International Science Olympiads',
					'biology'=>'Biology',
					'chemistry'=>'Chemistry',
					'computerScience'=>'computer Science',
					'math'=>'Math',
					'philo'=>'Philosphy',
					'physics'=>'Physics',
					'titleCodex'=>'Code of conduct',
					'codex'=>'As a guide or volunteer, I will respect the spirit and rules of the Science Olympiads. My attitude and behaviour towards participants, volunteers and collegues will be guided by loyality and fairness.',
					'titlePrivacy'=>'Data privacy protection',
					'privacy'=>'The provided information will be use exclusively for the recruitment process and during the resulting assignments. For this purpose your information will be stored in our data base. We will never share your information with other.',
					'submit'=>'send application');
				   break;
	}	

		$newForm->putElementMulticol('titleAnticipatedTask',0,-1,5,new HtmlElement('<p class="formparttitle">'.$translations['titleAnticipatedTask'].'</p>') );	
		$anticipated_task = new Textarea('','anticipated_task','',2,55);
		$newForm->putElementMulticol('anticipated_task',1,-1,5,$anticipated_task);

		$newForm->putElementMulticol('titlePeron', 2,-1,4,new HtmlElement('<p class="formparttitle">'.$translations['titlePeron'].'</p>') );	
		$newForm->setRowName(3, $translations['fname'].': ');
		$fname = new Input('','text','first_name','',array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->putElementMulticol('first_name',3,0,4,$fname);
		
		$newForm->setRowName(4, $translations['lname'].': ');
		$lname = new Input('','text','last_name','',array('size'=>20));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->putElementMulticol('last_name',4,0,4,$lname);

		$newForm->setRowName(5, $translations['bday'].': ');

		$bdayDay = new Input('','text','bdayDay','',array('size'=>2));
		$bdayDay->addRestriction(new InRangeRestriction(1,31));
		$newForm->putElement('bdayDay',5,0,$bdayDay);		

		$bdayMonth = new Input('','text','bdayMonth','',array('size'=>2));
		$bdayMonth->addRestriction(new InRangeRestriction(1,12));
		$newForm->putElement('bdayMonth',5,1,$bdayMonth);

		$bdayYear = new Input('','text','bdayYear','',array('size'=>4));
		$bdayYear->addRestriction(new InRangeRestriction(1900,2000));
		$newForm->putElement('bdayYear',5,2,$bdayYear);

		$newForm->setRowName(6, $translations['sex'].': ');
		$sex = new Select('','sex',array('female'=>$translations['female'],'male'=>$translations['male']), array('size'=>8));
		$newForm->putElementMulticol('sex',6,0,3,$sex);

		$newForm->setRowName(7, $translations['nationality'].': ');
		$nationality = new Input('Nationality: ','text','nationality','',array());
		$nationality->addRestriction(new StrlenRestriction(1,128));
		$newForm->putElementMulticol('nationality',7,0,4,$nationality);


		$newForm->putElementMulticol('titleContact', 8,-1,3, new HtmlElement('<p class="formparttitle">'.$translations['titleContact'].'</p>') );	

		$newForm->setRowName(9, $translations['corrspondance_language'].': ');
		$corrspondance_language=NULL;
		switch($_SESSION['language_abb']){
			case 'de': $corrspondance_language = new Select('','corrspondance_language',array('german'=>'Deutsch','french'=>'Français','english'=>'English'), 'german'); break;
			case 'fr': $corrspondance_language = new Select('','corrspondance_language',array('german'=>'Deutsch','french'=>'Français','english'=>'English'), 'french'); break;
			case 'en': $corrspondance_language = new Select('','corrspondance_language',array('german'=>'Deutsch','french'=>'Français','english'=>'English'), 'english'); break;
		}
		$newForm->putElementMulticol('corrspondance_language',9,0,4,$corrspondance_language);

		$newForm->setRowName(10, $translations['street'].': ');
		$street = new Input('','text','street','',array());
		$street->addRestriction(new StrlenRestriction(1,128));
		$newForm->putElementMulticol('street', 10, 0, 4, $street);

		$newForm->setRowName(11, $translations['zip/city'].': ');
		$zip = new Input('PLZ: ','text','zip','',array('size'=>6));
		$zip->addRestriction(new StrlenRestriction(4,8));
		$newForm->putElementMulticol('zip',11,0,2,$zip);
		
		$city = new Input('','text','city','',array('size'=>20));
		$city->addRestriction(new StrlenRestriction(1,64));
		$newForm->putElementMulticol('city',11,2,2,$city);

		$newForm->setRowName(12, $translations['phone'].': ');
		$phone = new Input('Tel: ','text','phone','',array('size'=>20));
		$phone->addRestriction(new StrlenRestriction(0,20));
		$newForm->putElementMulticol('phone',12,0,4,$phone);

		$newForm->setRowName(13, $translations['em'].': ');
		$em = new Input('','text','email','',array('size'=>27));
		$em->addRestriction(new IsEmailRestriction());
		$newForm->putElementMulticol('email', 13,0,4,$em);

		$newForm->setRowName(14, $translations['em2'].': ');
		$emtwo = new Input('','text','email2','',array('size'=>27));
		$emtwo->addRestriction(new IsEmailRestriction());
		$emtwo->addRestriction(new SameEmailAsRestriction($em));
		$newForm->putElementMulticol('email2', 14,0,4,$emtwo);

		$newForm->putElementMulticol('titleExperiences', 15,-1,4, new HtmlElement('<p class="formparttitle">'.$translations['titleExperiences'].'</p>') );	

		$newForm->setRowName(16, $translations['education'].': ');
		$education = new Input('','text','education','',array('size'=>30));
		$education->addRestriction(new StrlenRestriction(1,256));
		$newForm->putElementMulticol('education',16,0,4,$education);

		$newForm->setRowName(17, $translations['languages'].': ');
		$newForm->putElementMulticol('languageExplanation', 17,0,5, new HtmlElement('<p class="label">'.$translations['languageExplanation'].'</p>') );	
		$languages = new Input('','text','languages','',array('size'=>30)); 
		$languages->addRestriction(new StrlenRestriction(1,256));
		$newForm->putElementMulticol('languages',18,0,4,$languages);

		$newForm->setRowName(19, $translations['leader_jobs'].': ');
		$leader_jobs = new Textarea('','leader_jobs','',3,34);
		$newForm->putElementMulticol('leader_jobs',19,0,4,$leader_jobs);

		$newForm->setRowName(20, $translations['expertise'].': ');
		$expertise = new Textarea('','expertise','',3,34);
		$newForm->putElementMulticol('expertise',20,0,4,$expertise);

		$newForm->setRowName(21, $translations['participationSSO'].': ');
		$participationSSO = new MultiCheckboxTable('','participation_SSO',array($translations['biology'],$translations['chemistry'], $translations['computerScience'], $translations['math'],$translations['philo'],$translations['physics']), array(false,false,false,false,false,false), array('num_col'=>2, 'reverse'=>1));
		$newForm->putElementMulticol('participation_SSO',21,0,4,$participationSSO);

		$newForm->setRowName(22, $translations['participationISO'].': ');
		$participationISO = new MultiCheckboxTable('','participation_ISO',array($translations['biology'],$translations['chemistry'], $translations['computerScience'], $translations['math'],$translations['philo'],$translations['physics']), array(false,false,false,false,false,false), array('num_col'=>2, 'reverse'=>1));
		$newForm->putElementMulticol('participation_ISO',22,0,4,$participationISO);

		$newForm->putElementMulticol('titleCodex',23,-1,3,new HtmlElement('<p class="formparttitle">'.$translations['titleCodex'].'</p>') );	
		$codex=new CheckboxWithText('','codex',1,$translations['codex']);
		$codex->addRestriction(new NotFalseRestriction());
		$newForm->putElementMulticol('codex',24,-1,5,$codex);

		$newForm->putElementMulticol('spacer', 25,0,4, new HtmlElement('<p class="formparttitle"></p>') );
		$newForm->putElementMulticol('submit_register', 26, -1,5, new Submit('submit_addr',$translations['submit']));

		$newForm->putElementMulticol('titlePrivacy', 27,-1,4, new HtmlElement('<p class="formparttitle">'.$translations['titlePrivacy'].'</p>') );
		$newForm->putElementMulticol('privacy', 28,-1,5, new HtmlElement('<p class="formtext">'.$translations['privacy'].'</p>') );		
		

		$newForm->putElementMulticol('spacer2', 29,0,4, new HtmlElement('<p class="formparttitle"></p>') );	
 
		return $newForm;
	}
	

}
?>

