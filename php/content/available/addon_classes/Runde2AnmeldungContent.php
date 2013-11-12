<?php
/**
 * Content type that displays a form to user, in which he can select wheter or not to participate and where to participate in the exam of the 2nd round
 */
class Runde2AnmeldungContent extends Content
{
	
	/** ID of the current exam, change this by hand every year =) */
	protected $exam_id = 1;
	
	/** Locations of the 2. Round exams. Change as needed */
	protected $locations = array(0=>"nicht",
				"bern"=>"in Bern",
				"lausanne"=>"in Lausanne",
				"sargans"=>"in Sargans",
				"ticino"=>"im Tessin");

	protected $error_message = '';
	protected $reloadForm = FALSE;
	protected $hide_form = FALSE;
	protected $formError = FALSE;
	
	private $problem = array('1'=>'Es ist ein Fehler aufgetreten, bitte informiere uns per e-mail (jonas.helfer@ibosuisse.ch)','2'=>'Une erreur s\'est produit. Envoie nous un e-mail pour nous en informer, s.t.p. (jonas.helfer@ibosuisse.ch)');
	
	private $complete = array(1=>'Zum Vervollst&auml;ndigen der Anmeldung bitte ausf&uuml;llen',2=>'Veuillez remplir les champs ci-dessous pour compl&eactue;ter l\'inscription');
	
	private $location_label = array('1'=>'Ich möchte die zweite Runde ', '2'=>'J\'aimerais passer l\'examen ');

	private $subscribe = array(1=>'<p class="success">Du hast dich erfolgreich angemeldet und wirst in den n&auml;chsten Minuten ein e-mail von uns erhalten, vielen Dank!</p>', 2=>'<p class="success">Merci pour ton inscription! Tu vas recevoir un e-mail de confirmation dans les prochaines minutes.</p>');
	
	
	
	protected $ok_email = array(
		'subject'=> array(
			1=>"2. Runde SBO 2010",
			2=>"2e tour OSB 2010"
			)
		,
		'body'=> array(1=>'dummy body, fill in in process',2=>'dummy body, fill in in process')
		);
	
	
	
	function display()
	{
		if($this->hide_form)
			return $this->error_message;	
		
		
		$sql = SqlQuery::getInstance();
		
		$camp_id=1;

		$id= $GLOBALS['user']->id; //$sql->singleValueQuery('SELECT user_id FROM IBO_student WHERE user_id='.$GLOBALS['user']->id);
		if($id)
			$student_id = $id;
		else
			return '<p class="success">you are not a student</p>';

		
		$round2 = $sql->singleRowQuery("SELECT * FROM IBO_round2 WHERE exam_id='{$this->exam_id}' AND user_id='$student_id'");

		if(empty($round2))
			$round2 = array(
				'user_id'=>$student_id, 
				'exam_id'=>$this->exam_id,
				'location'=>'0' // 0 = not participating
			);
			
		//print_r($round2);
			
		if($this->reloadForm)
			$anmeldung = $this->reloadForm('form_anmeldung',$round2);  //because vector can change during processing
		else
			$anmeldung = $this->getForm('form_anmeldung',$round2);

			
			
		$html = $this->error_message;
		$html .= $anmeldung->getHtml($this->id);
		return $html;
	}
	

	function form_anmeldung($vector)
	{
		
		$round2 = $vector;


		$anmeldung = new SimpleForm('form_anmeldung');
		$anmeldung->setVector($round2);


		//print_r($round2);



		if($round2['location'] != '0')
		{
			$anmeldung->addHtml(i18n(
		array(1=>"Du bist im Moment für die 2. Runde ",
		2=>"Tu es inscrit pour le 2e tour ")) . $this->locations[$round2['location']] . i18n(array(1=>" angemeldet<br />",2=>"<br />")));
		}

		$anmeldung->addElement('location', new Select(i18n($this->location_label),'course_language_id',
			$this->locations,$round2['location']));
			

		$anmeldung->addHtml(' schreiben' . '&nbsp;');
	


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
		$eid = $form->getVectorValue('exam_id');
		
		$values = $form->getElementValues();
		unset($values['s']); //the submit button...
		$values['user_id'] = $uid;
		$values['exam_id'] = $eid;
		
		{
		
			if(!$form->validate())
			{
				$this->error_message = '<p class="error">FORM INVALID</p>';	
				$this->formError = TRUE;
				return;	
			}
			
			//wants to participate
			
			$ok = SqlQuery::getInstance()->insertQuery('IBO_round2',$values,FALSE,TRUE);
			if(mysql_error())
			{
				$this->error_message .= '<p class="error">'.i18n($this->problem).'</p>';
		
				return;
			}
			else
			{
				//print_r($values);
				
				if($values['location'] == '0'){
					$this->error_message = i18n(array(1=>"Du hast dich abgemeldet",2=>"Tu n'es plus inscrit"));
					$this->hide_form = true;
					return;	
				}
	
				if(!isset($GLOBALS['user']->email)){
					
					$this->ok_email['body'] =  array(
			1=>"Hallo {$GLOBALS['user']->first_name},<br /><br />Vielen Dank für deine Anmeldung. Die 2. Runde der SBO findet am 23. Januar 2010 statt, beginnt voraussichtlich um ca. 9 Uhr und dauert 4h. Alle weiteren Informationen werden wir dir in einem e-mail eine oder zwei Wochen vor der Prüfung mitteilen.<br /><br /> Falls Du Lust hast, kannst du in der Zwischenzeit unser Forum besuchen (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) und dich dort mit anderen Teilnehmern austauschen oder Fragen an die Organisatoren stellen. Du kannst das gleiche Login und Passwort brauchen wie für die SBO-Seite. Du musst dich einloggen, um den Bereich für die Teilnehmer zu sehen, Das Forum ist noch ganz neu und daher recht leer, aber getrau dich ruhig, den ersten Beitrag zu schreiben oder einen neuen 'Thread' zu eröffnen.<br /><br />Liebe Grüsse und bis bald,<br /><br />Jonas und alle anderen vom SBO-Team<br /><br /><br />------------------------------------------------------------------------<br /><br /><br />Salut {$GLOBALS['user']->first_name}<br /><br />Merci pour ton inscription. L'examen du 2e tour sera le 23 Janvier 2010. Il commencera environ à 09:00 et durera 4h. Tous les autres informations te seront communiqués par mail une ou deux semaines à l'avance<br /><br />Si tu as envie, tu peux entre-temps aller sur notre forum (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) et discuter avec d'autres participants ou poser des questions aux organisateurs. Tu peux utiliser le même mot de passe que pour le site OSB. Il faut se connecter avec le nom d'utilisateur et le mot de passe pour voir la partie réservée aux participants. Le forum est très récent et encore relativement vide mais n'aie pas peur d'écrire un premier message ou d'ouvrir un nouveau thème.<br /><br />Meilleures salutations et à bientôt<br /><br />Jonas et l'équipe des OSB",
			2=>"Hallo {$GLOBALS['user']->first_name},<br /><br />Vielen Dank für deine Anmeldung. Die 2. Runde der SBO findet am 23. Januar 2010 statt, beginnt voraussichtlich um ca. 9 Uhr und dauert 4h. Alle weiteren Informationen werden wir dir in einem e-mail eine oder zwei Wochen vor der Prüfung mitteilen.<br /><br /> Falls Du Lust hast, kannst du in der Zwischenzeit unser Forum besuchen (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) und dich dort mit anderen Teilnehmern austauschen oder Fragen an die Organisatoren stellen. Du kannst das gleiche Login und Passwort brauchen wie für die SBO-Seite. Du musst dich einloggen, um den Bereich für die Teilnehmer zu sehen, Das Forum ist noch ganz neu und daher recht leer, aber getrau dich ruhig, den ersten Beitrag zu schreiben oder einen neuen 'Thread' zu eröffnen.<br /><br />Liebe Grüsse und bis bald,<br /><br />Jonas und alle anderen vom SBO-Team<br /><br /><br />------------------------------------------------------------------------<br /><br /><br />Salut {$GLOBALS['user']->first_name}<br /><br />Merci pour ton inscription. L'examen du 2e tour sera le 23 Janvier 2010. Il commencera environ à 09:00 et durera 4h. Tous les autres informations te seront communiqués par mail une ou deux semaines à l'avance<br /><br />Si tu as envie, tu peux entre-temps aller sur notre forum (<a href=\"http://forum.ibosuisse.ch\">forum.ibosuisse.ch</a>) et discuter avec d'autres participants ou poser des questions aux organisateurs. Tu peux utiliser le même mot de passe que pour le site OSB. Il faut se connecter avec le nom d'utilisateur et le mot de passe pour voir la partie réservée aux participants. Le forum est très récent et encore relativement vide mais n'aie pas peur d'écrire un premier message ou d'ouvrir un nouveau thème.<br /><br />Meilleures salutations et à bientôt<br /><br />Jonas et l'équipe des OSB"
		);
					
					
					$this->error_message .= i18n($this->subscribe);
					$this->hide_form = TRUE;
					//sendSBOmail(ADMIN_EMAIL,'Runde2 Anmeldung',implode(' | ',$values));
					sendSBOmail(array($GLOBALS['user']->email,ADMIN_EMAIL),Language::extractPrefLan($this->ok_email['subject']),Language::extractPrefLan($this->ok_email['body']));
				}
				else
				{
					var_dump(isset($GLOBALS['user']->email));
					$this->error_message .= '<p class="error">'.i18n($this->problem).'</p>';
					$this->hide_form = TRUE;
				}
			
			}
		}
		
		$this->reloadForm = TRUE;
		return;
	}
	
	
	public function edit_form_elements()
	{
		//just to display the ppl already enroled...
		
		$sql = SqlQuery::getInstance();
		
		$tbl = new AbTable('tbl1',"SELECT u.first_name, u.last_name, u.zip, c.location FROM IBO_round2 c JOIN user u ON u.id = c.user_id",array('user_id'));
		
		return array(new HtmlElement($tbl->getHtml()));
		
		
	}

}
?>
