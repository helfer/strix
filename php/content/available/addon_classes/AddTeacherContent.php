<?php

class AddTeacherContent extends Content{


	function display()
	{

		$html = $this->process_msg;
		
		$html .= $this->getForm('add_teacher_form')->getHtml($this->id);
		
		return $html;
	}
	
	protected function add_teacher_form($vector)
	{

		
		$newForm = new TabularForm(__METHOD__);
		$newForm->setProportions('8em','12em');
		$newForm->setVector($vector);
		
		//print_r($newForm);
		$query_lng = array('query'=>"SELECT * FROM `language`",'keys'=>array('id'),'values'=>array('name'));
		$language = new DataSelect('Language: ','language_id',$query_lng,1);
		$newForm->addElement('teacher_sprache',$language);
		
		$query_anrede = array('query'=>"SELECT * FROM `LEG_anschrift`",'keys'=>array('id'),'values'=>array('de'));
		$anrede = new DataSelect('Anrede: ','anrede_id',$query_anrede,1);
		$newForm->addElement('teacher_anschrift',$anrede);
		
		$query_titel = array('query'=>"SELECT * FROM `LEG_titel`",'keys'=>array('id'),'values'=>array('de'));
		$titel = new DataSelect('Titel: ','titel_id',$query_titel,7);
		$newForm->addElement('teacher_titel',$titel);
		
		$fname = new Input('First name: ','text','first_name','',array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('teacher_vorname',$fname);
		
		$lname = new Input('Last name: ','text','last_name','',array('size'=>20));
		$lname->addRestriction(new StrlenRestriction(1,64));
		$newForm->addElement('teacher_nachname',$lname);
		
		$query_schulen = array('query'=>"SELECT schule_code, name FROM `LEG_schulen` ORDER BY name",'keys'=>array('schule_code'),'values'=>array('name'));
		$schulen = new DataSelect('schulen: ','schulen_id',$query_schulen,1);
		$newForm->addElement('teacher_schule',$schulen);
		
		
		$newForm->addElement('submit_form',new Submit('submit_form','Add Teacher'));
		
		
		


		//ONLY FOR EXISTING USERS!
		//$user = SqlQuery::getInstance()->singleRowQuery("SELECT first_name, last_name, street, co, city, zip, email, `phone`, `mobile` FROM user WHERE id='".$_SESSION['user']->id."'");
		//$newForm->populate($_SESSION['user']->getTableValues());

		//print_r($_SESSION['user']->getTableValues());

		return $newForm;
	}
	
	
	function process_add_teacher_form()
	{	
		
		
		$form = $this->getForm('add_teacher_form');
		
		if(!$form->validate())
			return FALSE;
		
		$values = $form->getElementValues();
		
		$sql = SqlQuery::getInstance();
			//search db to check whether entry exists
		$res = $sql->singleValueQuery('SELECT count(id) FROM LEG_lehrer WHERE 
			 vorname LIKE "'.	$values['teacher_vorname'].'"'.
		'and nachname LIKE "'.	$values['teacher_nachname'].'"'.
		'and schule LIKE "'.	$values['teacher_schule'].'"');
		

		if ($res > 0) 
		{
			$this->process_msg = '<p class="error">Teacher already in Database</p>';
		}
		else
		{
			//add entry
			$ins_k = array('anschrift','titel','vorname','nachname','schule','sprache','fach');
			$ins_v = array(
						$values['teacher_anschrift'],
						$values['teacher_titel'],
						$values['teacher_vorname'],
						$values['teacher_nachname'],
						$values['teacher_schule'],
						$values['teacher_sprache'],
						1);
			$ins = array_combine($ins_k,$ins_v);
			
			$teacher_id = $sql->insertQuery('LEG_lehrer',$ins);
			
			if(!mysql_error())
				$this->process_msg = '<p class="success">New teacher inserted. Insert id = '.$teacher_id.'</p>';
			else
				$this->process_msg = '<p class="error">An SQL error has occured. Could not insert teacher.</p>';
			
			/*
			$myID=mysql_insert_id();
			$sql="select l.id as id, a.de as anschrift, t.de as titel, l.vorname, l.nachname, sch.name as schule, lan.abb as lang from anschrift a, titel t, lehrer l, schulen sch, languages lan where l.anschrift=a.id and l.titel=t.id and l.schule=sch.schule_code and l.sprache=lan.id and l.id=".$myID;
			$sql=sql_query($sql);
			$sql=mysql_fetch_assoc($sql);
			$html .=  '<p class="notice">Lehrer erfolgreich eingef&uuml;gt!</p>';
			$html .=  '<table>';
			$html .=  '<tr><td><p>ID:</p></td><td><p>'.$sql['id'].'</p></td></tr>';
			$html .=  '<tr><td><p>Anschrift:</p></td><td><p>'.$sql['anschrift'].'</p></td></tr>';
			$html .=  '<tr><td><p>Titel:</p></td><td><p>'.$sql['titel'].'</p></td></tr>';
			$html .=  '<tr><td><p>Vorname:</p></td><td><p>'.$sql['vorname'].'</p></td></tr>';
			$html .=  '<tr><td><p>Nachname:</p></td><td><p>'.$sql['nachname'].'</p></td></tr>';
			$html .=  '<tr><td><p>Schule:</p></td><td><p>'.$sql['schule'].'</p></td></tr>';
			$html .=  '<tr><td><p>Sprache:</p></td><td><p>'.$sql['lang'].'</p></td></tr>';
			$html .=  '</table>';	
			*/
		}
	}
	

}

?>
