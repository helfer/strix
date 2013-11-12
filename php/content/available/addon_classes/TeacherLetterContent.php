<?php

include_once(SCRIPT_DIR . 'core/i18n.php');

class TeacherLetterContent extends Content{
	
	protected $selected_exam_id = NULL;
	
	function display()
	{
		
		$html = '';
		//-------------------------------------------------------------
		//form to chose the exam 
		$html .= $this->getForm('select_exam_form')->getHtml($this->id);
		
		
		
		
		
		$sql = SqlQuery::getInstance();
		// if an exam is chosen, read from DB
		if(isset($this->selected_exam_id))
			$thisExam = $sql->singleRowQuery("select * from IBO_exam where id=".$this->selected_exam_id);	
			
		




		// if an exam is chosen, print it
		if(isset($thisExam)){		
			$html.='<p class="subtitle">';
			switch($_SESSION['language_abb']){
				case 'de': $html.='Pr&uuml;fung "'.$thisExam['de'].'" ausgew&auml;hlt'; break;
				case 'fr': $html.='??'; break;
				case 'it': $html.='??'; break;
				case 'en': $html.='??'; break;
			}	
			$html.='</p>';
		}
		
		//-------------------------------------------------------------
		if(isset($thisExam)){
			//extract data for letter
			
			$qry='select count(id) as cnt from IBO_student_exam where language_id=1 and exam_id='.$thisExam['id'];
			$n_teilnehmerD=$sql->singleValueQuery($qry);
			
			$qry='select count(id) as cnt from IBO_student_exam where language_id>1 and exam_id='.$thisExam['id'];
			$n_teilnehmerF=$sql->singleValueQuery($qry);
			
			$qry='select count(id) from IBO_student_exam where passed=1 AND language_id=1 AND exam_id='.$thisExam['id'];
			$n_qualifiziertD=$sql->singleValueQuery($qry);
			
			$qry='select count(id) from IBO_student_exam where passed=1 AND language_id>1 AND exam_id='.$thisExam['id'];
			$n_qualifiziertF=$sql->singleValueQuery($qry);
			
			$n_qualifiziert=$n_qualifiziertD+$n_qualifiziertF;
			$n_teilnehmer=$n_teilnehmerD+$n_teilnehmerF;			

			//--------------------------------------------
			//write out file in latex format
			include_once('ibo_classes/class_ibolatex.php');			
			$latex=new iboLetterLatex();
			
			
			// LOAD TEXTS AND VARIABLES:
				
			$variable = new i18nVariable();
	
			$variable->loadFromDbByName('teacher_letter_r1_latex');
			
			$variable->loadAdditionalFragments();
			
			$text[1]=$variable->getAdditionalFragment(1,'text');
			$text[2]=$variable->getAdditionalFragment(2,'text');
			$text[3]=$variable->getAdditionalFragment(3,'text');

			
		
			$qry="
			SELECT DISTINCT 
				l.*, 
				se.language_id, 
				a.de, 
				a.fr, 
				a.it 
			FROM 
				LEG_lehrer l  
			JOIN IBO_student s ON s.teacher_id = l.id
			JOIN IBO_student_exam se ON se.user_id=s.user_id 
			LEFT JOIN LEG_anschrift a on  l.anschrift=a.id
			WHERE se.exam_id='{$thisExam['id']}'";
			
			$result=$sql->simpleQuery($qry);							
			foreach($result as $a){													
					
/*$text[1]='Wir danken Ihnen herzlich für Ihre Teilnahme mit Ihren Schülern an der ersten Runde der 11. Schweizer Biologie Olympiade SBO. Ihrem Einsatz ist es zu verdanken, dass die SBO dieses Jahr einen Teilnehmerrekord verzeichnen kann. Konkret haben wir [[PARTICIPANTS]] ausgefüllte Prüfungsbogen erhalten.\par
Für die Vorbereitungswoche 2010, welche vom 31. Oktober bis und mit 7. November 2010 stattfinden wird, haben wir insgesamt [[INVITED]] Teilnehmerinnen und Teilnehmer eingeladen.\par
Als Beilage finden Sie die Resultate Ihrer Schülerinnen und Schüler. Es handelt sich dabei um die Rangliste der für die zweiten Runde qualifizierten Schüler. Zusätzlich sind alle ihre Schüler mit Punktzahl und Rang aufgelistet. Die Schülerinnen und Schüler haben wir bereits brieflich über ihre Resultate informiert. Auf unserer Homepage www.ibosuisse.ch finden Sie wie gewohnt sowohl den Lösungsschlüssel als auch die Prüfung als pdf.\par
Wie viele von Ihnen bemerkt haben, sind bei den Aufgaben 21 und 28 jeweils 2 Antworten richtig. Selbstverständlich wurden beide Antworten mit einem Punkt belohnt. Die Aufgabe 49 ergibt in der italienischen Version keine richtige Lösung, während die Aufgabe 61 auf Französisch nicht lösbar ist. Damit niemand einen Nachteil hat, wurden diese Aufgaben für alle Teilnehmer nicht gewertet.\par
Wir würden uns sehr freuen, wenn Sie nächstes Jahr erneut mit Ihren Schülerinnen und Schülern an der Schweizer Biologie Olympiade teilnehmen würden.\par\vspace{15pt}';

$text[2]='Nous vous remercions chaleureusement d\'avoir donné la possibilité à vos élèves de participer au premier tour des 12\supscr{èmes} Olympiades Suisses de Biologie OSB. Gr\^ace à votre engagement, cette année encore, nous avons enregistré un nouveau record de participation. Nous avons en effet reçu en retour [[PARTICIPANTS]] questionnaires. De ces [[PARTICIPANTS]] participants, nous en avons invité [[INVITED]] à la semaine de préparation qui se déroulera du 31 octobre au 7 novembre 2010. \par
En annexe, vous trouverez le classement des participants qualifiés pour le deuxième tour ainsi que les rangs de vos élèves. Nous les avons déjà informés par écrit de leurs résultats. Comme d\'habitude, vous trouverez sur notre site internet www.ibosuisse.ch le classement complet, une clé de solution, de même que l\'examen en format pdf.\par
Comme beaucoup ont d\^u le remarquer, les questions 21 et 28 ont deux réponses correctes. Bien entendu, les deux ont été acceptées à la correction. La question 49 était fausse en italien et la question 61 n\'avait pas de réponse juste en français. Pour qu\'aucun participant ne soit désavantagé, ces deux questions n\'ont pas été retenues.\par
Nous nous réjouissons d\'une prochaine participation de votre part aux Olympiades Suisses de Biologie.\par\vspace{15pt}';

$text[3]='La ringraziamo cortesemente per la sua partecipazione con i suoi allievi alla prima tappa delle Olimpiadi Svizzere di Biologia OSB. Grazie al suo impegno, le OSB hanno di nuovo avuto tanti iscritti. Più concretamente, abbiamo ricevuto [[PARTICIPANTS]] questionari compilati, [[PARTICIPANTSFR]] dalla Svizzera romanda e dal Ticino e [[PARTICIPANTSDE]] dalla Svizzera tedesca. \par
Abbiamo invitato [[INVITED]] partecipanti, di cui [[INVITEDFR]] romandi, alla settimana di preparazione 2010 che avrà luogo dal 25 ottobre al 1 novembre 2009.\par
In allegato trova i risultati dei suoi allievi e una lista dei partecipanti che si sono classificati per il secondo turno nella quale trova i suoi allievi in grassetto. I partecipanti sono già stati avvisati dei loro risultati per iscritto. Come di consueto, troverà le soluzioni e l\'esame sul nostro sito internet wwww.ibosuisse.ch in formato pdf. \par
Come molti di voi avranno notato, le domande 21 e 28 avevano entrambe due risposte corrette; naturalmente a entrambe le risposte verrà assegnato il punto. Per la domanda 49 non c\'era nessuna risposta corretta nella versione in italiano, mentre l\'esercizio 61 della versione in francese non si poteva risolvere. In modo che nessuno abbia uno svantaggio, per tutti i partecipanti queste domande non verranno considerate nell\'assegnazione dei punti. Ci voglia scusare per questo.\par
Ci farebbe molto piacere che i suoi allievi partecipino alle Olimpiadi Svizzere di Biologia anche l’anno prossimo.\par\vspace{15pt}';*/
		
		
			
				//-----------------------------------------------------
				//ADRESSE				
				//-----------------------------------------------------
				$qry="SELECT sch.* FROM LEG_schulen sch, LEG_lehrer l 
					WHERE sch.schule_code=l.schule and l.id=".$a['id'];				
				$r=$sql->singleRowQuery($qry);
				
				$anrede='';
				if($a['anschrift']>0){	
					switch($a['language_id']){
						case 1: $anrede=$a['de'].'<br/>\\'."\n"; break;
						case 2: $anrede=$a['fr'].'<br/>\\'."\n"; break;
						case 3: $anrede=$a['it'].'<br/>\\'."\n"; break;						
					}
				} else $anrede='\vspace{13pt}';
				$latex->addLetterTo($anrede.$a['vorname'].' '.$a['nachname'].'<br>'.$r['name'].'<br>'.$r['plz'].' '.$r['ort']);
							
			
				//-----------------------------------------------------
				//DATUM
				//-----------------------------------------------------
				switch($a['language_id']){
					case '1': $latex->setDate('Bern, 3. Oktober 2010'); break;
					case '2': $latex->setDate('Berne, le 3 Octobre 2010'); break;
					case '3': $latex->setDate('Berna, 3 ottobre 2010'); break;				
				}									
				
				//-----------------------------------------------------
				//BETREFF
				//-----------------------------------------------------					
				switch($a['language_id']){
					case '1': $latex->setCaption('Resultate der Ersten Runde der SBO 2011'); break;
					case '2': $latex->setCaption('Résultats du 1er tour des OSB 2011'); break;
					case '3': $latex->setCaption('Risultati del primo turno delle OSB 2011'); break;					
				}						
				
				//-----------------------------------------------------
				//ANREDE  -> NAME!!!
				//-----------------------------------------------------
				switch($a['language_id']){
					case '1': $latex->setSalutation('Sehr geehrte Biologielehrerinnen und Biologielehrer'); break;
					case '2': $latex->setSalutation('Chers professeurs de biologie'); break;
					case '3': $latex->setSalutation('Egregio Professore di  biologia'); break;					
				}							
				
				//-----------------------------------------------------
				//TEXT: TWO DIFFERENT LETTERS!!!
				//-----------------------------------------------------				
				$tags=array('[[INVITED]]','[[INVITEDDE]]', '[[INVITEDFR]]', '[[PARTICIPANTS]]', '[[PARTICIPANTSDE]]', '[[PARTICIPANTSFR]]');				
				$replace=array($n_qualifiziert, $n_qualifiziertD, $n_qualifiziertF, $n_teilnehmer, $n_teilnehmerD, $n_teilnehmerF);
				$latex->setText(str_replace($tags, $replace, $text[$a['language_id']]));
				
				//-----------------------------------------------------
				//GRUSS: JE NACH BRIEF
				//-----------------------------------------------------
					switch($a['language_id']){
						case '1': $latex->setGreetings('Wir verbleiben mit bestem Dank\par'); break;
						case '2': $latex->setGreetings('Avec nos meilleures salutations.\par'); break;
						case '3': $latex->setGreetings('Le porgiamo i nostri migliori saluti\par');break;		
					}	
				
				//-----------------------------------------------------
				//UNTERZEICHNER: JE NACH BRIEF
				//-----------------------------------------------------
					switch($a['language_id']){
						case '1': $latex->setSignature('Thierry Aebischer und das ibo\textbar\textbf{suisse}-Team');
							break;
						case '2': $latex->setSignature('Thierry Aebischer et l\'équipe ibo\textbar\textbf{suisse}');
							break;						
						case '3': $latex->setSignature('Thierry Aebischer e il gruppo ibo\textbar\textbf{suisse}');
							 break;					
					}					
					switch($a['language_id']){
						case '1': $latex->setAttachements('\vspace{70pt}Beilage: Rangliste'); break;
						case '2': $latex->setAttachements('\vspace{70pt}Annexe : Classement'); break;
						case '3': $latex->setAttachements('\vspace{70pt}Allegati: Classifica'); break;							
					}						
				
			}
			
	
		
			
		$GLOBALS['RequestHandler']->SendOnlyThis($latex->getLatex()."\n%");
		$html.='<textarea style="width: 400px; height:200px;">if you see this, something went wrong</textarea>';
		
			
		
			
			
		}
		return $html;
	}
	
	
	public function select_exam_form($vector)
	{
		//SELECT EXAM FORM ------------------
		
		$form = new TabularForm(__METHOD__);
		$form->setVector($vector);
		$form->setProportions('10em','28em');

		
		switch($_SESSION['language_abb']){
			case 'de': $html ='Bitte w&auml;hlen'; break;
			case 'fr': $html ='Choisir s.v.p'; break;
			case 'it': $html ='??'; break;
			case 'en': $html ='??'; break;
		}		
		$form->addElement('html1',new HtmlElement($html));

		$query="SELECT id,de FROM IBO_exam WHERE closed < 2 ORDER BY date DESC";
		
		$q_args = array('query'  => $query,
						'keys'  => array('id'),
						'values'=> array('de')
						);
		$arg_ary = array('label'=>'exam:','name'=>'exam_id','query_args'=>$q_args);
		$form->addElement('exam_id',new DataSelect($arg_ary));
		
		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
		// END OF SELECT EXAM FORM -------------------------	
	}
	
	protected function process_select_exam_form()
	{
		$form = $this->getForm('select_exam_form');
		if(!$form->validate())
			return FALSE;
			
		$eid = $form->getElementValue('exam_id');
		
		$this->selected_exam_id = $eid;
		
		return;	
	}
	
}


