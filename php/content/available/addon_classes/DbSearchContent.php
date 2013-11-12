<?php

class DbSearchContent extends Content
{


	function display()
	{
		$html = $this->process_msg;
		
		$form = $this->getForm('searchdb_form');
		
		$html .= $form->getHtml($this->id);
		
		if( $form->getButtonPressed() && $form->validate() )
		{
			
			$fvalues = $form->getElementValues();
		
			$search_type = $fvalues['search_type'];
			$search_limit = $fvalues['search_limit'];
			//MUST escape string!!!
			$search_string = mysql_real_escape_string( $fvalues['search_string'] );
			
			
			$html .= $this->perform_search($search_string,$search_type,$search_limit);
		}

		return $html;
	}
	
	public function searchdb_form($vector)
	{
		$form = new TabularForm(__METHOD__);
		$form->setVector($vector);
		$form->setProportions('10em','28em');
		
		$arg_ary = array('label'=>'Suche in:','name'=>'search_type',
						'options'=> array ('teacher'=>'Lehrer','school'=>'Schulen','participant'=>'Teilnehmer','any'=>'Alle'));
		$form->addElement('search_type',new Select($arg_ary));
		
		$arg_ary = array('label'=>'Max. Resultate:','name'=>'search_limit',
						'options'=> array (10=>'10',25=>'25',100=>'100',1000=>'1000'));
		$form->addElement('search_limit',new Select($arg_ary));
		
		$search_string = new TextInput( array('label'=>'Suche nach:','name'=>'search_string','value'=>'') );
		$search_string->addRestriction(new StrlenRestriction(2,50));
		$form->addElement('search_string',$search_string);

		$form->addElement('submit',new Submit('submit','Search'));
		
		return $form;
	}
		
		
	public function process_searchdb_form()
	{
		$form = $this->getForm('searchdb_form');
		if(!$form->validate())
		{
			$this->process_msg = '<p class="error">Form contains errors!</p>';
			return FALSE;
		}
	}
	
	
	protected function perform_search($search_string,$search_type,$search_limit)
	{		
		$sql = SqlQuery::getInstance();
		
		$total = 0;
		$head_string = ''; //header string to return
		$ret_table = ''; //string with html-tables to return
		
		if($search_type == 'school' || $search_type == 'any')
		{
		
			$num_schools = $sql->singleValueQuery(
				"SELECT 
					COUNT(schule_code) 
				FROM 
					`LEG_schulen` 
				WHERE
					name LIKE '%$search_string%'
					OR plz LIKE '%$search_string%'
					OR strasse LIKE '%$search_string%'
					OR ort LIKE '%$search_string%' 
					OR schule_code LIKE '%$search_string%'"
					);
					
			$schools = $sql->simpleQuery(
				"SELECT 
					schule_code,
					name, 
					strasse, 
					plz, 
					ort 
				FROM 
					`LEG_schulen`
				WHERE
					name LIKE '%$search_string%'
					OR plz LIKE '%$search_string%'
					OR strasse LIKE '%$search_string%'
					OR ort LIKE '%$search_string%' 
					OR schule_code LIKE '%$search_string%'
					LIMIT
					$search_limit
					");
					
			$total += $num_schools;		
					
			$head_string .= "$num_schools Schools found<br />";		
			
			$min = min($search_limit,$num_schools);
			if($num_schools)
				$ret_table .=  "<p><b>Schulen ($min of $num_schools):</b></p>" . array2html($schools);
						
		}
		
		if($search_type == 'teacher' || $search_type == 'any')
		{
			$num_teachers = $sql->singleValueQuery(
				"SELECT 
					COUNT(l.id)  
				FROM
					`LEG_lehrer` l, `LEG_schulen` sch 
				WHERE 
					l.schule=sch.schule_code 
						AND (
							l.vorname LIKE '%$search_string%' 
							OR l.nachname LIKE '%$search_string%'
							OR sch.name LIKE '%$search_string%'
							)"
					);				
	
			$teachers = $sql->simpleQuery(
				
				"SELECT 
					l.id,
					l.vorname,
					l.nachname,
					sch.name,
					l.sprache  
				FROM
					`LEG_lehrer` l, `LEG_schulen` sch 
				WHERE 
					l.schule=sch.schule_code 
						AND (
							l.vorname LIKE '%$search_string%' 
							OR l.nachname LIKE '%$search_string%'
							OR sch.name LIKE '%$search_string%'
							)
					LIMIT
					$search_limit
					"
					);				
			
			$total += $num_teachers;
								
			$head_string .= "$num_teachers Teachers found<br />";		
			
			$min = min($search_limit,$num_teachers);
			if($num_teachers)
				$ret_table .=  "<p><b>Lehrer($min of $num_teachers):</b></p>" . array2html($teachers);
			
		}
	
		if($search_type == 'participant' || $search_type == 'any')
		{
			$num_participants = $sql->singleValueQuery(
				"SELECT 
					COUNT(id)  
				FROM
					`user` 
				WHERE 
					first_name LIKE '%$search_string%' 
					OR last_name LIKE '%$search_string%'
					OR street LIKE '%$search_string%'
					OR zip LIKE '%$search_string%'
					OR city LIKE '%$search_string%'
					OR username LIKE '%$search_string%'
					OR birthday LIKE '%$search_string%'
					OR email LIKE '%$search_string%'
					"
					);				
	
			$participants = $sql->simpleQuery(
				
				"SELECT 
					id,
					username,
					first_name,
					last_name,
					street,
					zip,
					city
				FROM
					`user` 
				WHERE 
					first_name LIKE '%$search_string%' 
					OR last_name LIKE '%$search_string%'
					OR street LIKE '%$search_string%'
					OR zip LIKE '%$search_string%'
					OR city LIKE '%$search_string%'
					OR username LIKE '%$search_string%'
					OR birthday LIKE '%$search_string%'
					OR email LIKE '%$search_string%'
				LIMIT
					$search_limit
					"
					);				
			
			
			$total += $num_participants;
			
			
								
			$head_string .= "$num_participants users found<br />";		
			
			$min = min($search_limit,$num_participants);
			if($num_participants)		
				$ret_table .=  "<p><b>Users ($min of $num_participants):</b></p>" . array2html($participants);
		}


		//do not print tables if no entries found.
		if ($total == 0)
			$ret_table = '<p class="error">0 Entries found!</p>';
		
		return $head_string . $ret_table;
		
	}
		
	//old stuff, remove when done coding the new stuff.	
	/*	
		global $cfg;	
	
		$html = '';
		//search form			
		$html.='<form action="" name="ibodbsearchform" method="post" enctype="multipart/form-data">';
		$html.='<input type="text" size="40" name="ibodbsearchstring" value="';
		if(isset($_POST['ibodbsearchstring'])) $html.=$_POST['ibodbsearchstring'];
		$html.='">&nbsp;&nbsp;';
		
		$html.='&nbsp;&nbsp;<select name="ibodbsearchwhat"><option value="all"';
		if(isset($_POST['ibodbsearchwhat']) && $_POST['ibodbsearchwhat']=='all') $html.=' SELECTED';
		$html.='>';
		switch($_SESSION['language']){
			case 'de': $html.='Alles'; break;
			case 'fr': $html.='Tous'; break;
			case 'it': $html.='Tutto'; break;
			case 'en': $html.='All'; break;
		}
		$html.='</option>';
		
		$html.='<option value="school"';
		if(isset($_POST['ibodbsearchwhat']) && $_POST['ibodbsearchwhat']=='school') $html.=' SELECTED';
		$html.='>';
		switch($_SESSION['language']){
			case 'de': $html.='Schule'; break;
			case 'fr': $html.='École'; break;
			case 'it': $html.='Scuola'; break;
			case 'en': $html.='School'; break;
		}
		$html.='</option>';
		
		$html.='<option value="teacher"';
		if(isset($_POST['ibodbsearchwhat']) && $_POST['ibodbsearchwhat']=='teacher') $html.=' SELECTED';
		$html.='>';
		switch($_SESSION['language']){
			case 'de': $html.='Lehrkraft'; break;
			case 'fr': $html.='Professeur'; break;
			case 'it': $html.='Professore'; break;
			case 'en': $html.='Teacher'; break;
		}
		$html.='</option>';
		
		$html.='<option value="student"';
		if(isset($_POST['ibodbsearchwhat']) && $_POST['ibodbsearchwhat']=='student') $html.=' SELECTED';
		$html.='>';
		switch($_SESSION['language']){
			case 'de': $html.='Sch&uuml;ler'; break;
			case 'fr': $html.='Etudiant'; break;
			case 'it': $html.='Studente'; break;
			case 'en': $html.='Student'; break;
		}
		$html.='</option></select>&nbsp;&nbsp;';
		$html.='<input type="submit" name="submit_ibodbsearch" value="let\'s go!!">';
		$html.='</form>';
		
		

		}
		return $html;
	}

	
	
	function process()
	{
		//search the DB
		if(isset($_POST['submit_ibodbsearch']) && isset($_POST['ibodbsearchwhat']) && isset($_POST['ibodbsearchstring']) && strlen($_POST['ibodbsearchstring'])>0){
			//search schools
			if($_POST['ibodbsearchwhat']=='all' || $_POST['ibodbsearchwhat']=='school'){
				$sql='select count(schule_code) as cnt from schulen where name like "%'.$_POST['ibodbsearchstring'].'%" or plz like "%'.$_POST['ibodbsearchstring'].'%" or strasse like "%'.$_POST['ibodbsearchstring'].'%" or ort like "%'.$_POST['ibodbsearchstring'].'%" or schule_code like "%'.$_POST['ibodbsearchstring'].'%"';
				$result=sql_query($sql);
				$result=mysql_fetch_assoc($result);
				$cnt=$result['cnt'];
				if($cnt>$this->displayLength){
					switch($_SESSION['language']){
						case 'de': $html.='<p class="subtitle">25 Schulen (von '.$cnt.')</p>'; break;
						case 'fr': $html.='Etudiant'; break;
						case 'it': $html.='Studente'; break;
						case 'en': $html.='Student'; break;
					}
				} else {
					switch($_SESSION['language']){
						case 'de': $html.='<p class="subtitle">'.$cnt.' Schulen gefunden</p>'; break;
						case 'fr': $html.='Etudiant'; break;
						case 'it': $html.='Studente'; break;
						case 'en': $html.='Student'; break;
					}
				}
				if($cnt>0){
					$sql='select schule_code, name, strasse, plz, ort from schulen where name like "%'.$_POST['ibodbsearchstring'].'%" or plz like "%'.$_POST['ibodbsearchstring'].'%" or strasse like "%'.$_POST['ibodbsearchstring'].'%" or ort like "%'.$_POST['ibodbsearchstring'].'%" or schule_code like "%'.$_POST['ibodbsearchstring'].'%" order by name asc limit 0, '.$this->displayLength;					
					$result=sql_query($sql);					
					switch($_SESSION['language']){
						case 'de': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
						case 'fr': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
						case 'it': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
						case 'en': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
					}
					$html.=array2html($result);
				}			
			
			}	
			//search teacher
			if($_POST['ibodbsearchwhat']=='all' || $_POST['ibodbsearchwhat']=='teacher'){
				$sql='select count(l.id) as cnt from lehrer l, schulen sch where l.schule=sch.schule_code and (l.vorname like "%'.$_POST['ibodbsearchstring'].'%" or l.nachname like "%'.$_POST['ibodbsearchstring'].'%" or sch.name like "%'.$_POST['ibodbsearchstring'].'%")';				
				$result=sql_query($sql);
				$result=mysql_fetch_assoc($result);
				$cnt=$result['cnt'];
				if($cnt>$this->displayLength){
					switch($_SESSION['language']){
						case 'de': $html.='<p class="subtitle">25 Lehrkr&auml;fte (von '.$cnt.')</p>'; break;
						case 'fr': $html.='Etudiant'; break;
						case 'it': $html.='Studente'; break;
						case 'en': $html.='Student'; break;
					}
				} else {
					switch($_SESSION['language']){
						case 'de': $html.='<p class="subtitle">'.$cnt.' Lehrkr&auml;fte gefunden</p>'; break;
						case 'fr': $html.='Etudiant'; break;
						case 'it': $html.='Studente'; break;
						case 'en': $html.='Student'; break;
					}
				}
				if($cnt>0){
					$sql='select l.id, l.vorname, l.nachname, sch.name, l.sprache from lehrer l, schulen sch where l.schule=sch.schule_code and (l.vorname like "%'.$_POST['ibodbsearchstring'].'%" or l.nachname like "%'.$_POST['ibodbsearchstring'].'%" or sch.name like "%'.$_POST['ibodbsearchstring'].'%") order by l.nachname asc limit 0, '.$this->displayLength;					
					$result=sql_query($sql);					
					switch($_SESSION['language']){
						case 'de': $result=mysql2arrayWithKeys($result, array('ID', 'Vorname', 'Nachname', 'Schule', 'Sprache')); break;
						case 'fr': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
						case 'it': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
						case 'en': $result=mysql2arrayWithKeys($result, array('ID', 'Name', 'Strasse', 'PLZ', 'Ort')); break;
					}
					$html.=array2html($result);
				}			
			
			}		
	}*/

}

?>
