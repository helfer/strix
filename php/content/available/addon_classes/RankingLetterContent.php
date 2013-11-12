<?php

include_once(SCRIPT_DIR . 'core/i18n.php');

class RankingLetterContent extends Content
{

	
	/*
	function writeRanking($ranking, $lehrer, $lang)
	{				
		$rang=0; $latex='';		
		if($lang==1) $rankKey='Rang DE';
		else $rankKey='Rang FR';			
		foreach($ranking as $r){			
			if(($lang==1 && $r['Sprache']==1) || ($lang>1 && $r['Sprache']>1)){				
				$line='';
				if($r['passed']==1){
					$line.='\\rowcolor[gray]{0.9} ';
				}
				//echo $r['lehrer'].'<>'.$lehrer.'<br>';
				//print_r($lehrer);
				if ($r['lehrer']==$lehrer['id']){
					if($rang!=$r[$rankKey]){
						$rang= $r[$rankKey]; 
						$line .= '\\textbf{'.$rang.'.}'; 
					}
					$line.=' & \\textbf{'.$r['Vorname'].'} & \\textbf{'.$r['Name'].'} & \\textbf{'.$r['Total'].'}\\\\';
					$latex.=$line;
				}
				else{
					if($rang!=$r[$rankKey]){
						$rang=$r[$rankKey];
						$line.=$rang.'. '; 
					}
					$line.=' & '.$r['Vorname'].' & '.$r['Name'].' & '.$r['Total'].'\\\\'.chr(10);
					$latex.=$line;
				}
			}
		}		
		return $latex;
	}*/
	
	
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
				
		include_once('ibo_classes/class_ibolatex.php');			
		$latex=new iboRankingLatex();
		
			// LOAD TEXTS AND VARIABLES:
				
			$variable = new i18nVariable();
	
			$variable->loadFromDbByName('teacher_lettter_latex_ranking');
			
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
			foreach($result as $a)
			{
			
			//new ranking
			if($a['anschrift']>0){	
				switch($a['language_id']){
					case 1: $anrede=$a['de'].' '; break;
					case 2: $anrede=$a['fr'].' '; break;
					case 3: $anrede=$a['it'].' '; break;
				}
			} else $anrede='';
			$latex->addRankingFor($anrede.$a['vorname'].' '.$a['nachname']);
			
			//title
			switch($a['language_id']){
				case '1': $latex->setTitle('Rangliste der ersten Runde 2011'); break;
				case '2': $latex->setTitle('Classment du 1er tour 2011'); break;
				case '3': $latex->setTitle('Classificà del primo turno 2011'); break;				
			}
			//text
			
			$latex->setText($text[$a['language_id']].'\newpage');
     /*switch($a['language_id']){
                                case '1': $latex->setText('Dieses Dokument enthält die Rangliste der '.$thisExam['de'].' Die '.$n_teilnehmer.
                        ' teilnehmenden Schüler sind nach ihrer Punktezahl geordnet. Diejenigen '.$n_qualifiziert.
                        ' Schüler, welche sich für die Vorbereitungswoche sowie für die zweite Runde qualifiziert haben, sind grau hinterlegt.'.
                        chr(10).'Damit Sie Ihre Schüler schneller finden, wurden diese in der Rangliste fett hervorgehoben. Die vollständige Rangliste finden sie auf unserer Homepage www.ibosuisse.ch.\newpage'); break; 
                                case '2': $latex->setText('Ce document contient le début du classement du premier tour des OSB 2010,de m\^eme '.
                                        'que les rangs de vos élèves (le classement complet se trouve sur notre site internet www.ibosuisse.ch). Les participant(e)s y sont '.
                                        'classés selon le nombre de points obtenus. Les noms des '.$n_qualifiziert.' élèves qui se sont'.
                                        ' qualifié(e)s pour la semaine de préparation ainsi que pour le deuxième tour sont grisés.'.
                                        'Les noms de vos élèves sont écrits en gras. Cela vous permet de les identifier rapidement.\newpage'); break;
                                case '3': $latex->setText('Questo documento contiene la prima parte della classifica della prima tappa delle Olimpiadi Svizzere di Biologia OSB 2010 e la lista dei suoi allievi. È possibile ottenere la classifica intera consultando il nostro sito www.ibosuisse.ch. '.
                                        'Tutti i '.$n_teilnehmer.' studenti della Svizzera romanda e del Ticino che hanno partecipato alle OSB sono stati'.
                                        'classificati in base al punteggio ottenuto. I nomi dei '.$n_qualifiziert.' studenti che si sono'.
                                        ' qualificati per la settimana di preparazione e per la seconda tappa delle OSB sono scritti su sfondo grigio.'.
                                        'I nomi dei Suoi studenti sono scritti in grassetto.\newpage'); break;                                       
                        		default: $latex->setText('');
						}*/
			
			//table headers_sent
			switch($a['language_id']){
				case '1': $latex->setTableTitle(array('Rang','Vorname', 'Nachname', 'Total')); break;
				case '2': $latex->setTableTitle(array('Rang','Prénom','Nom','Total')); break;
				case '3': $latex->setTableTitle(array('Posizione','Prenome','Nome','Totale')); break;				
			}
			
			//fill tableRows
			$qry="
				SELECT 
					se.passed, 
					se.rang, 
					u.first_name as vorname, 
					u.last_name as name, 
					se.total, 
					stu.teacher_id as lehrer_id
				FROM 
					IBO_student_exam se
					JOIN user u ON u.id = se.user_id
					JOIN IBO_student stu ON stu.user_id = u.id
				WHERE 
					se.exam_id={$thisExam['id']} 
					AND (passed <> 0 OR stu.teacher_id='{$a['id']}')
				ORDER BY 
					se.total DESC, 
					name ASC";
			$res=$sql->simpleQuery($qry);	
			$rang=0;
			if ($a['language_id'] == 1) $rankKey='rang';
			else $rankKey='rang';
				
			foreach($res as $r)
			{	
				$line=array('');
				if($r['passed']=='1'){
					$line[0]='\rowcolor[gray]{0.9} ';
				}
				
				
				if ($r['lehrer_id'] == $a['id']){
					//echo $a['id'].' FETT'.'<br />';
					if($rang!=$r[$rankKey]){
						$rang= $r[$rankKey]; 
						$line[0] .= '\textbf{'.$rang.'.}'; 
					}
					$line[]='\textbf{'.$r['vorname'].'}';
					$line[]='\textbf{'.$r['name'].'} ';
					$line[]='\textbf{'.$r['total'].'}';
				}
				else{
					if($rang!=$r[$rankKey]){
						$rang= $r[$rankKey]; 
						$line[0].=$rang; 
					}
					$line[]=$r['vorname'];
					$line[]=$r['name'];
					$line[]=$r['total'];
				}
				$latex->addTableRow($line);
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


