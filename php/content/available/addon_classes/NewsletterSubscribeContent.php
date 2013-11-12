<?php
/*
 *      testcontent.php
 *      
 *     
 */
include_once('./classes/NewsletterSubscriber.php');

//forms get initiated automatically!!
class NewsletterSubscribeContent extends Content
{
	protected $fragment_vars = array();
	
	protected $procMsg = '';

	public function display()
	{		
		if(!$this->procMsg)
			$this->procMsg = $this->mProcessInfo;
		
		if(isset($_GET['confirm_subscription']) && isset($_GET['subscriber_id']))
			return $this->confirm_subscription($_GET['subscriber_id']);
		
		/// @ todo store subscriber id in session, this seems more logical to ppl who subscribe.	
		if(isset($_GET['subscriber_id']))
			return $this->display_modify_subscription($_GET['subscriber_id']);

		$xhtml = '';
		
		$form = $this->getForm('form_subscribe');

		$xhtml .= $this->procMsg;

		$xhtml .= $form->getHtml($this->id);
		//---------------------------
		include_once('./classes/newsletter.php');

		$newsletter = new Newsletter();
		$newsletter->loadFromDbById( 1 );

		
		return $xhtml;
	}
	
	public function display_modify_subscription($subscriber_secret)
	{
		
		$subscriber = new NewsletterSubscriber();
		if (!$subscriber->loadFromDbBySecret($subscriber_secret))
			return '<p class="problem">Abonnenten-Nummer ist falsch.</p>';
		///@ todo if subscriber is coupled to a user, the user must log in now.

		print_r($subscriber);
		//return;
		
		print_r($subscriber->getSubscriptions());
		print_r($subscriber->getNonsubscriptions());
 
		
	}
	


	public function process_form_subscribe()
	{
		$form = $this->getForm('form_subscribe');
		$form->validate();
		
		if($form->validate())
		{
			$newsletters = $form->getElement('newsletters')->getChecked();
			if(empty($newsletters))
			{
				$this->procMsg = '<p class="problem">Kein Newsletter ausgewählt!</p>';
				return;
			}
			
			$values = $form->getElementValues();
			notice($values);
			
			$sql = SqlQuery::getInstance();
			
			
			//check if email is unique -------------------
			
			$exists = $sql->safeSelect(SUBSCRIBER_TABLE,array('email'),array('email'=>$values['email']));
			if($exists)
			{
				return '<p class="problem">Es existiert schon ein Abonnement für diese e-mail Adresse!</p>';
			}	
			
			//create subscriber ---------------------
			
			$relevant_fields = array('anrede','first_name','last_name','email','language_id');
			$insert = array_intersect_key($values,array_flip($relevant_fields));
			$insert['secret'] = NewsletterSubscriber::makeSecret();
			$insert['user_id'] = $_SESSION['user_id'];
			
			$subscriber = new NewsletterSubscriber($insert);
			
			if (!$insert['secret'])
				return '<p class="problem">Es ist ein kleiner Datenbankfehler aufgetreten. Bitte versuchen Sie es später noch einmal oder kontaktieren Sie einen Administrator</p>';
			
			
			if(!$subscriber->store())
				return '<p class="problem">Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später noch einmal oder kontaktieren Sie einen Administrator</p>';
			
			//create subscriptions --------------------
			
			foreach($newsletters as $nl_id)
			{
				$subscriber->subscribe($nl_id);
			}
			
			
			//send email confirmation
			//by smtp from newsletter@ibosuisse.ch
			
			$this->procMsg = '<p class="success">Vielen Dank, Ihre Anmeldung wurde verarbeitet. Sie werden ab nun den Newsletter erhalten.</p>';
			//NEWSLETTER_PATH .
			$link = '?subscriber_id=' . $insert['secret'];
			$form->reset();
			//$this->procMsg .= '<p>Ihre Anmeldung können sie <a href="' . $link . '">hier</a> &auml;ndern.</p>';
		
		}
		else
		{
			$this->procMsg = '<p class="problem">Formular enthält Fehler!</p>';	
		}
	}
	
	protected function form_subscribe()
	{
		$form1 = new TabularForm(__METHOD__);
		
		$form1->setProportions('12em','24em');
		
		$anrede = new Select(
			array(	'label'		=>	'Anrede', 
				'name'		=>	'anrede', 
				'options'	=>	array('"Du"','Herr','Frau','Dr.','Prof.') 
				) 
		);
		
		$first_name = new TextInput(
			array(	'label'		=>	'Vorname',
				'name'		=>	'first_name',
				'value'		=>	''
			)
		);
		$first_name->addRestriction( new StrlenRestriction(1,128) );
		
		$last_name = new TextInput(
			array(	'label'		=>	'Nachname',
				'name'		=>	'last_name',
				'value'		=>	''
			)
		);
		$last_name->addRestriction( new StrlenRestriction(1,128) );
		
		$email = new TextInput(
			array(	'label'		=>	'E-mail',
				'name'		=>	'email',
				'value'		=>	''
			)
		);
		$email->addRestriction( new IsEmailRestriction() );
		
		$email2 = new TextInput(
			array(	'label'		=>	'E-mail (bestätigen)',
				'name'		=>	'email2',
				'value'		=>	''
			)
		);
		$email2->addRestriction( new SameAsRestriction($email) );
		
		$language = new Select(
			array(	'label'		=>	'Sprache',
				'name'		=>	'language_id',
				'options'	=>	array('1'=>'Deutsch','2'=>'Französisch')
			)
		);
		
		$form1->addElement('html',new HtmlElement('<br /><h2>Ich möchte folgende Newsletter abonnieren ...</h2>'));
		
		include_once('./classes/newsletter.php');
		$l = Newsletter::listAllNewsletters();
		$check_values = array();
		$check_labels = array();
		foreach($l as $i=>$nl)
		{
			$check_values[$nl['id']] = FALSE; //not checked
			$check_labels[$nl['id']] = $nl['title'];
		}
		
			$chk1 = new MultiCheckbox( 'Newsletters:', 'newsletters', $check_labels, $check_values,array('reverse'=>TRUE));
			$form1->addElement($chk1);
		
		$form1->addElement('html0',new HtmlElement('<h2>Bitte senden Sie den Newsletter an ...</h2>'));
		
		$form1->addElement($anrede);
		$form1->addElement($first_name);
		$form1->addElement($last_name);
		$form1->addElement($email);
		$form1->addElement($email2);
		$form1->addElement($language);
		
		
		$form1->addElement(new SimpleSubmit('abonnieren'));
			
		return $form1;
	}
	
	
	public function process_form_change_subscription()
	{
		
	}
	
	public function form_change_subscription($vector = array())
	{
		
	}
	
	
}

?>
