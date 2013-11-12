<?php
class CampAnmeldungContent extends Content
{
	protected $camp_id = 2; ///@todo: this has to be changed manually every year
	
	protected $error_message = '';
	protected $reloadForm = FALSE;
	protected $hide_form = FALSE;
	protected $formError = FALSE;
	
	private $problem = array('1'=>'Es ist ein Fehler aufgetreten, bitte informiere uns per e-mail (jonas.helfer@ibosuisse.ch)','2'=>'Une erreur s\'est produit. Envoie nous un e-mail pour nous en informer, s.t.p. (jonas.helfer@ibosuisse.ch)');
	
	private $complete = array(1=>'Zum Vervollst&auml;ndigen der Anmeldung bitte ausf&uuml;llen',2=>'Veuillez remplir les champs ci-dessous pour compl&eactue;ter l\'inscription');
	
	private $teilnahme = array('1'=>'Ich will am camp teilnehmen', '2'=>'J\'aimerais participer au camp');
	private $sprache = array('1'=>'Ich m&ouml;chte auf ... unterrichtet werden&nbsp;', '2'=>'J\'aimerais &ecirc;tre enseign&eacute; en&nbsp;');
	private $vegi = array(1=>'Ich bin vegetarier&nbsp;', 2=>'Je suis v&eacute;g&eacute;tarien&nbsp;');
	private $food_restrict = array(1=>'Bemerkungen bzgl. Essen oder anderem&nbsp;(max. 511 Zeichen)<br />', 2=>'Remarques concernant la nourriture ou autre chose&nbsp; (511 charact&egrave;res max)<br/>');
	private $female = array(1=>'Ich bin ein weibchen (f&uuml;r T-Shirt)&nbsp;', 2=>'Je suis une femelle (Pour le T-shirt)&nbsp;');
	private $tshirt = array(1=>'Meine T-Shirt gr&ouml;sse&nbsp;', 2=>'Ma taille de T-Shirt&nbsp;');
	private $email = array(1=>'e-mail* (zwingend auszuf&uuml;llen)&nbsp;', 2=>'e-mail* (obligatoire)&nbsp;');
	private $email2 = array(1=>'e-mail adresse best&auml;tigen:&nbsp;', 2=>'confirmer l\'adresse e-mail&nbsp;');
	
	private $unsubscribe = array(1=>'<p class="success">Du hast dich erfolgreich abgemeldet!</p>', 2=>'<p class="success">tu t\'es désinscrit de la semaine de préparation!</p>');
	
	private $subscribe = array(1=>'<p class="success">Du hast dich erfolgreich angemeldet und wirst in den n&auml;chsten Minuten ein e-mail von uns erhalten, vielen Dank!</p>', 2=>'<p class="success">Merci pour ton inscription! Tu vas recevoir un e-mail de confirmation dans les prochaines minutes.</p>');
	
	private $subscribed = array(1=>'<p>Du hast dich bereits für das Lager angemeldet. Falls Du dich abmelden willst, dann klicke bitte hier. Falls Du deine Anmeldung ändern willst, so musst Du dich abmelden und dann neu anmelden',
	2=>'Tu t\'es déjà inscrit au camp de préparation. Pour te désinscrire, clique sur le bouton ci-dessous. Pour changer ton inscription, tu dois te désinscrire et puis ré-inscrire.');
	
	
	protected $ok_email = array(
		'subject'=> array(
			1=>"Vorbereitungswoche SBO 2011",
			2=>"Camp de préparation OSB 2011"
			)
		,
		'body'=> array(1=>'dummy body, fill in in process',2=>'dummy body, fill in in process')
		);
	
	
	
	function display()
	{
		if($this->hide_form)
			return $this->error_message;	
		
		
		$sql = SqlQuery::getInstance();
		
		$camp_id=$this->camp_id;

		$id= $GLOBALS['user']->id; //$sql->singleValueQuery('SELECT user_id FROM IBO_student WHERE user_id='.$GLOBALS['user']->id);
		if($id)
			$student_id = $id;
		else
			return '<p class="success">you are not a student</p>';

		
		$camp = $sql->singleRowQuery("SELECT * FROM IBO_camp WHERE camp_id='$camp_id' AND user_id='$student_id'");

		if(empty($camp))
			$camp = array(
				'id'=>-1, 
				'user_id'=>$student_id, 
				'camp_id'=>$camp_id, 
				'participation'=>'0', 
				'course_language_id'=>'', 
				'vegetarian'=>'', 
				'food_comment'=>'', 
				'tshirt_size'=>'', 
				'email'=>NULL,
				'sex'=>'', 
				'telefon'=>''
			);
			
		//print_r($camp);
			
		if($this->reloadForm)
			$anmeldung = $this->reloadForm('form_anmeldung',$camp);  //because vector can change during processing
		else
		{
			if($camp['participation'] == 1 && isset($camp['email']) && !$this->formError)
				$anmeldung = $this->getForm('form_abmeldung',$camp);
			else
				$anmeldung = $this->getForm('form_anmeldung',$camp);
			
		}
			
			
		$html = $this->error_message;
		$html .= $anmeldung->getHtml($this->id);
		return $html;
	}
	
	function form_abmeldung($vector)
	{
		$form = new SimpleForm('form_abmeldung');
		$form->setVector($vector);
		
		$form->addHtml(i18n($this->subscribed));
		$form->addElement('submit',new Submit('abmelden','abmelden'));
		
		return $form;
	}
	
	function process_form_abmeldung()
	{
			$form = $this->getForm('form_abmeldung');
			$uid = $form->getVectorValue('user_id');
			$cid = $form->getVectorValue('camp_id');
	
			SqlQuery::getInstance()->insertQuery('IBO_camp',array('participation'=>0,'user_id'=>$uid,'camp_id'=>$cid),FALSE,TRUE);
			$this->error_message = '<p class="success">'.i18n($this->unsubscribe).'</p>';
			sendSBOmail(ADMIN_EMAIL,'camp abmeldung',"user $uid has unsubscribed from the camp");
	}
	

	function form_anmeldung($vector)
	{
		
		$camp = $vector;


		$anmeldung = new SimpleForm('form_anmeldung');
		$anmeldung->setVector($camp);


		//print_r($camp);

		
		
		if($camp['participation'])
		{
			$anmeldung->addElement('participation', new Hidden('participation',1));
			
			$anmeldung->addHtml('<br /><hr></hr><br />');
			$anmeldung->addElement('course_language_id', new Select(i18n($this->sprache),'course_language_id',array(1=>'Deutsch',2=>'Fran&ccedil;ais'),$camp['course_language_id']));
			
			
			$anmeldung->addHtml('<br /><br />');
			
			$vegi_extra = array();
			if($camp['vegetarian'])
				$vegi_extra = array(0=>'checked');
			
			$anmeldung->addElement('vegetarian', new Checkbox(i18n($this->vegi),'vegetarian','1',$vegi_extra));
			
			$anmeldung->addHtml('<br /><br /><br />');
			
			$comment = new Textarea(i18n($this->food_restrict),'food_comment',$camp['food_comment'],3,60);
			$comment->addRestriction(new StrlenRestriction(0,511));
			$anmeldung->addElement('food_comment', $comment);
			
			$anmeldung->addHtml('<br /><br />');
			
			$fraulein = array();
			if($camp['sex'])
				$fraulein = array(0=>'checked');
			
			$anmeldung->addElement('sex',new Checkbox(i18n($this->female),'geschlecht','1',$fraulein));
			
			$anmeldung->addHtml('<br /><br />');
			
			$anmeldung->addElement('tshirt_size', new Select(i18n($this->tshirt),'tshirt_size',array('XS'=>'XS','S'=>'S','M'=>'M','L'=>'L','XL'=>'XL','XXL'=>'XXL'),array($camp['tshirt_size'])));
			
			$anmeldung->addHtml('<br /><br />');
			
			
			$em = $camp['email'];
			$email = new TextInput(i18n($this->email),'email',$em);
			$email->addRestriction(new IsEmailRestriction());
			$anmeldung->addElement('email', $email);

			$anmeldung->addHtml('<br /><br />');

			$email2 = new TextInput(i18n($this->email2),'email2',$em);
			$email->addRestriction(new SameAsRestriction($email));
			$anmeldung->addElement('email2', $email2);
		}
		else
		{
			$anmeldung->addElement('participation', new Checkbox(i18n($this->teilnahme),'participation','1',$camp['participation']));
		}
		
		$anmeldung->addHtml('<br /><br />');
		
		$anmeldung->addElement('s',new Submit('submit','submit'));
		
		return $anmeldung;
		
	}
	
//******************************************************************************//	

//******************************************************************************//

//******************************************************************************//	
	
	function process_form_anmeldung()
	{
		$form = $this->getForm('form_anmeldung');
		
		$uid = $form->getVectorValue('user_id');
		$cid = $form->getVectorValue('camp_id');
		
		$values = $form->getElementValues();
		unset($values['s']); //the submit button...
		unset($values['email2']);
		$values['user_id'] = $uid;
		$values['camp_id'] = $cid;
		
		{
			//because we display the button only once...
			if(!isset($values['participation']))
				$values['participation'] = 1;
		
		
			if(!$form->validate())
			{
				$this->error_message = '<p class="error">FORM INVALID</p>';	
				$this->formError = TRUE;
				return;	
			}
			
			//wants to participate
			
			$ok = SqlQuery::getInstance()->insertQuery('IBO_camp',$values,FALSE,TRUE);
			if(mysql_error())
			{
				$this->error_message .= '<p class="error">'.i18n($this->problem).'</p>';
		
				return;
			}
			else
			{
				if(!empty($values['email'])){
					
					$this->ok_email['body'] =  array(
			1=>"Hallo {$GLOBALS['user']->first_name},<br /><br />Vielen Dank für deine Anmeldung. Die Vorbereitungswoche findet vom 31. Oktober bis zum 7. November 2010 in Müntschemier (BE) statt. Alle weiteren wichtigen Informationen kannst Du dem Brief entnehmen, den wir dir nach der 1. Runde geschickt haben. Vergiss nicht, das Notfallblatt bis zum 23. Oktober einzuschicken. Ansonsten musst Du bis zum Lagerbegin nichts mehr tun.<br /><br /> Falls Du Lust hast, kannst du in der Zwischenzeit unser Forum besuchen (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) und dich dort mit anderen Teilnehmern austauschen oder Fragen an die Organisatoren stellen. Du kannst das gleiche Login und Passwort brauchen wie für die SBO-Seite. Du musst dich einloggen, um den Bereich für die Teilnehmer zu sehen, Das Forum ist noch ganz neu und daher recht leer, aber getrau dich ruhig, den ersten Beitrag zu schreiben oder einen neuen 'Thread' zu eröffnen.<br /><br />Liebe Grüsse und bis bald,<br /><br />Jonas und alle anderen vom SBO-Team",
			2=>"Salut {$GLOBALS['user']->first_name}<br /><br />Merci pour ton inscription. La semaine de préparation a lieu du 31 octobre au 7e novembre à Müntschemier (BE). Toutes les informations importantes sont dans la feuille que tu as reçue après le 1er tour. N'oublie pas d'envoyer la fiche d'urgence jusqu'au 23 octobre. C'est tout ce que tu as à faire avant le début du camp.<br /><br />Si tu as envie, tu peux entre-temps aller sur notre forum (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) et discuter avec d'autres participants ou poser des questions aux organisateurs. Tu peux utiliser le même mot de passe que pour le site OSB. Il faut se connecter avec le nom d'utilisateur et le mot de passe pour voir la partie réservée aux participants. Le forum est très récent et encore relativement vide mais n'aie pas peur d'écrire un premier message ou d'ouvrir un nouveau thème.<br /><br />Meilleures salutations et à bientôt<br /><br />Jonas et l'équipe des OSB"
		);
					
					
					$this->error_message .= i18n($this->subscribe);
					$this->hide_form = TRUE;
					sendSBOmail(ADMIN_EMAIL,'camp Anmeldung',implode(' | ',$values));
					sendSBOmail(array($values['email'],ADMIN_EMAIL),$this->ok_email['subject'][$values['course_language_id']],$this->ok_email['body'][$values['course_language_id']]);
				}
				else
				{
					$this->error_message .= '<p class="notice">'.i18n($this->complete).'</p>';
					
				}
			
			}
		}
		
		$this->reloadForm = TRUE;
		return;
	}
	
	
	public function edit()
	{
		//just to display the ppl already enroled...
		
		$sql = SqlQuery::getInstance();
		
		$tbl = new AbTable("SELECT u.first_name, u.last_name, u.zip, c.* FROM IBO_camp c JOIN user u ON u.id = c.user_id",array('user_id'));
		
		return $tbl->getHtml();
		
		
	}

}
?>
