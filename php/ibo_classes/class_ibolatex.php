<?php
//these classes are used to write LATEX (and maybe pdf later...) for the ibo

include_once(SCRIPT_DIR . 'core/i18n.php');

class latex {	
	var $twoside=false;
	var $twocolumn=false;
	
	function __construct(){ 
		
	}	
	
	function getLatex(){
		$latex=$this->getDoctype().chr(10);
		$latex.=$this->getPreamble().chr(10);
		$latex.='\begin{document}'.chr(10);
		$latex.=$this->getDocument().chr(10);
		$latex.='\end{document}'.chr(10);	
		return $latex;
	}
	
	function getDoctype(){
		$latex='\documentclass[a4paper, 11pt';
		if($this->twoside) $latex.=',twoside';
		if($this->twocolumn) $latex.=',twocolumn';
		return $latex.']{scrartcl}';
	}
	
	function getPreamble(){
		$variable = new i18nVariable();

		$variable->loadFromDbByName('latex_base_preamble',1);//<=the 1 is for language id
		
		$latex = $variable->text;
		/*$latex= '\pagestyle{empty}
\usepackage[german]{babel}
\usepackage{sectsty}
\usepackage[pdftex]{color, graphicx}
\usepackage{picins} %sollte ersetzt werden ... veraltet (mögl. alternativen: floatflt, wrapfig)
\usepackage{textcomp}
\renewcommand{\rmdefault}{pfu}\rmfamily
\allsectionsfont{\usefont{OT1}{pfu}{b}{n}\selectfont}\setlength{\textheight}{32cm}'	;*/			
		return $latex;
	}
	
	function getDocument(){
		return '';
	} 

	
	function stringToLatex($string){
		//first everything to html		
		$string=str_replace('&amp;', '&', $string);
		$char1a=array('Ã¤','Ã¶','Ã¼','Ã','Ã','Ã', 'Ã©', 'Ã¨', 'Ã ', 'Ã&nbsp;', 'Ã', 'Ã', 'Ã', 'Ã¢', 'Ãª', 'Ã', 'Ã', 'Ã§', "'", '"');
   		$char1b=array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&eacute;', '&egrave;', '&agrave;', '&Eacute;', '&Egrave;', '&Agrave;', '&Agrave;', '&acirc;', '&ecirc;', '&Acirc;', '&Ecirc;', '&ccedil;', '&#039;', '&quot;');

		$char2a=array('ä','ö','ü','Ä','Ö','Ü', 'é', 'è', 'à', 'É', 'È', 'À', 'â', 'ê', 'Â', 'Ê', 'ç','ï','î', 'ì','ù','ô','ë', 'Ï','Î', 'Ì','Ù','Ô','Ë');
   		$char2b=array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&eacute;', '&egrave;', '&agrave;', '&Eacute;', '&Egrave;', '&Agrave;', '&acirc;', '&ecirc;', '&Acirc;', '&Ecirc;', '&ccedil;', '&iuml;', '&icirc;', '&igrave;', '&ugrave;', '&ocirc;', '&euml;', '&Iuml;', '&Icirc;', '&Igrave;', '&Ugrave;', '&Ocirc;', '&Euml;'); 

		$string=str_replace($char1a, $char1b, $string);		
		$string=str_replace($char2a, $char2b, $string);	

		//then replace some latex 		
		$from=array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&eacute;', '&egrave;', '&agrave;', 
		'&Eacute;', '&Egrave;', '&Agrave;', '&acirc;', '&ecirc;', '&Acirc;', '&Ecirc;', '&ccedil;', '&iuml;', 
		'&icirc;', '&igrave;', '&ugrave;', '&ocirc;', '&euml;', '&Iuml;', '&Icirc;', '&Igrave;', '&Ugrave;', 
		'&Ocirc;', '&Euml;', '&laquo;' , '&raquo;'); 
		$to=array('\"{a}','\"{o}','\"{u}','\"{A}','\"{O}','\"{U}','\\\'{e}','\`{e}','\`{a}','\^{e}','\`{E}','\`{A}','\^{a}','\^{e}','\^{A}','\^{E}','\c{c}','\"{i}','\^{i}','\`{i}','\`{u}','\^{o}','\"{e}','\”{I}','\^{I}','\`{I}','\`{U}','\^{O}','\"{E}', '\flqq', '\frqq');
		$string=str_replace($from, $to, $string);
		//finally, replace more latex
		$from=array('<b>', '</b>', '<p>', '</p>', '<tt>', '</tt>', '<i>', '</i>', '<br>', '<br/>'); 
		$to=array('\textbf{', '}', '', '\par', '\texttt{', '}', '\textit{', '}', '\\\\', '\\\\');
		$string=str_replace($from, $to, $string);
		//now, replace even more latex!
		$from=array('&', '>', '<', '|', '„', '“', '«', '»', '”', '’', '&#039;', '-'); 
		$to=array('\&', '\textgreater ', '\textless ', '\textbar ', "``", "''", '\flqq', '\frqq', "''", '\textquotesingle ', '\textquotesingle ', '\--');	
		$string=str_replace($from, $to, $string);		
		
		$from=array('\\\\textquotesingle');
		$to=array('\\textquotesingle');
		return str_replace($from, $to, $string);		
	}


};

class iboLetterLatex extends latex{
	var $sender=array();
	var $date=array();
	var $recepient=array();
	var $caption=array();
	var $pageformat=array();
	var $logo=array();
	var $text=array();	
	var $salutation=array();
	var $attachements=array();
	var $addIterator=-1;
		
	function __construct(){	
		//$this->addIterator=-1;
	}
	
	function getPreamble(){
		$latex=parent::getPreamble();
		$variable = new i18nVariable();

		$variable->loadFromDbByName('latex_letter_preamble',1);//<=the 1 is for language id
		
		$latex .= $variable->text;		
		/*$latex .='
\setlength{\textwidth}{17.0cm}
\setlength\topmargin{-2.2cm}
\setlength{\voffset}{-0.5cm}
\setlength{\marginparwidth}{0cm}
\setlength{\oddsidemargin}{40pt}
\setlength{\evensidemargin}{40pt}
\setlength{\hoffset}{-1.5cm}
\setlength{\parindent}{0pt}
\setlength{\footskip}{35pt}\newcommand{\subscr}[1]{$_{\textrm{\footnotesize{#1}}}$}
\newcommand{\supscr}[1]{$^{\textrm{\footnotesize{#1}}}$}
\renewcommand{\rmdefault}{bfu}\rmfamily

\newenvironment{textl}[1]{
\usefont{T1}{bfu}{l}{n}\selectfont#1
\usefont{T1}{bfu}{mb}{n}\selectfont}

\newenvironment{textli}[1]{
\usefont{T1}{bfu}{l}{it}\selectfont#1
\usefont{T1}{bfu}{mb}{n}\selectfont}
';*/
		return $latex;						
	}	
	
	function getDocument(){		
		$latex='';		
		for($i=0;$i<count($this->text);++$i){
			if($i>0) $latex.='\newpage
';			
			if($this->pageformat[$i]!='') $latex.=$this->pageformat[$i];
			if($this->logo[$i]!='') $latex.=$this->logo[$i].chr(10);
			if($this->sender[$i]!='') $latex.=$this->sender[$i].'\par\vspace{15pt}'.chr(10);
			if($this->date[$i]!='') $latex.=$this->date[$i].'\par\vspace{25pt}'.chr(10);
			if($this->recepient[$i]!='') $latex.=$this->recepient[$i].'\par'.chr(10);
			$latex.='\vspace{30pt}{\Large \textbf{'.$this->caption[$i].'}}\par\vspace{15pt}'.chr(10)
					.$this->salutation[$i].'\par\vspace{15pt}'.chr(10)
					.$this->text[$i].'\par\vspace{15pt}'.chr(10)
					.$this->greetings[$i].'\par\vspace{15pt}'.chr(10)
					.$this->signature[$i].chr(10);
			if($this->attachements[$i]!=''){				
				$latex.='\par\vspace{30pt}'.$this->attachements[$i];				
			}			
			$latex.='';
		}
		return $latex;
	}	
	
	function addLetterTo($recipient){
		++$this->addIterator;
		$this->recepient[$this->addIterator]=$this->stringToLatex($recipient);	
		$this->attachements[$this->addIterator]='';	
		$this->sender[$this->addIterator]='ibo\textbar\textbf{suisse} \\\\Institut f\"ur \"Okologie und Evolution\\\\Baltzerstrasse 6\\\\3012 Bern\\\\info@ibosuisse.ch\\\\www.ibosuisse.ch';
		$this->date[$this->addIterator]='';
		$this->caption[$this->addIterator]='';
		$this->logo[$this->addIterator]='';
		$this->pageformat[$this->addIterator]='';
		$this->salutation[$this->addIterator]='';
		$this->text[$this->addIterator]='';
		$this->signature[$this->addIterator]='';
		$this->greetings[$this->addIterator]='';
	}	
	function addLogo($Logo=null){
		$this->logo[$this->addIterator]='\AddToShipoutPicture*{
  \AtPageLowerLeft{
  \put(65,475){\includegraphics[width=6.5cm, angle=90]{logo_ibosuisse.pdf}}
  }
}';
	}	
	function setPageFormat($format){
		$this->sender[$this->addIterator]=$format;
	}	

	function setSender($Sender){
		$this->sender[$this->addIterator]=$this->stringToLatex($Sender);
	}	
	function setDate($date){
		$this->date[$this->addIterator]=$this->stringToLatex($date);
	}	
	function setCaption($caption,$dirty_hack=false){
		if($dirty_hack)
		{
			$this->caption[$this->addIterator]='\setlength{\oddsidemargin}{20pt}
\setlength{\evensidemargin}{20pt}
\setlength{\textwidth}{19.0cm}
\begin{center}'.$this->stringToLatex($caption).'\end{center}\par';
		}
		else
		{
			$this->caption[$this->addIterator]=$this->stringToLatex($caption);
		}
	}
	function setSalutation($salutation){
		$this->salutation[$this->addIterator]=$this->stringToLatex($salutation);
	}
	function setText($text){
		$this->text[$this->addIterator]=$this->stringToLatex($text);
	}
	function setGreetings($greetings){
		$this->greetings[$this->addIterator]=$this->stringToLatex($greetings);
	}	
	function setSignature($signature){
		$this->signature[$this->addIterator]=$this->stringToLatex($signature);
	}	
	function setAttachements($att){
		$this->attachements[$this->addIterator]=$this->stringToLatex($att);
	}
	
}

class iboRankingLatex extends latex{
	
	var $name=array();
	var $text=array();
	var $title=array();
	var $tableTitle=array();
	var $tableRows=array();
	var $align=array('l','l','r','r',);
	var $addIterator=-1;
	var $rowcounter=0;
	
	
	function __construct(){	
		$this->twoside=true;
		$this->twocolumn=true;
	}
	
	function getDocument(){		
		$latex='';		
		for($i=0;$i<count($this->text);++$i){
			if($i>0) $latex.='\cleardoublepage';
			$latex.= '\section*{'.$this->title[$i].'\\\\ \texttt{'.$this->name[$i].'}}'.chr(10)
					.$this->text[$i].'\par\vspace{20pt}'.chr(10)
					.$this->tableTitle[$i].chr(10)
					.'\begin{xtabular}{';					
			foreach($this->align as $a){
				$latex.='>{\\small} '.$a;
			}
			$latex.='}'.chr(10);
			foreach($this->tableRows[$i] as $r){
				$latex.=$r.chr(10);
			}
			$latex.='\end{xtabular}';
		}
		return $latex;
	}	
	 
	
	function getPreamble(){
		$latex=parent::getPreamble();
		$latex.='\usepackage{xtab}'.chr(10)
				.'\usepackage{array, color}'.chr(10)
				.'\usepackage{colortbl}'.chr(10)
				.'\setlength\columnsep{0.8cm}'.chr(10)
				.'\setlength\textheight{30.5cm}'.chr(10)
				.'\setlength{\\textwidth}{18.0cm}'.chr(10)
				.'\setlength\\topmargin{-1.2cm}'.chr(10)
				.'\setlength{\\voffset}{-0.5cm}'.chr(10)
				.'\setlength{\\marginparwidth}{0cm}'.chr(10)
				.'\setlength{\\oddsidemargin}{0pt}'.chr(10)
				.'\setlength{\\evensidemargin}{0pt}'.chr(10)
				.'\setlength{\\hoffset}{-1.0cm}'.chr(10)
				.'\setlength{\\footskip}{35pt}'.chr(10);				
		return $latex;
	}
	
	function addRankingFor($name){
		$this->addIterator++;
		$this->name[$this->addIterator]=$this->stringToLatex($name);
		$this->rowcounter=0;
	}
	
	function setTitle($title){
		$this->title[$this->addIterator]=$this->stringToLatex($title);
	}
	
	function setText($text){
		$this->text[$this->addIterator]=$this->stringToLatex($text);
	}
	
	function setAlign($align){
		$this->align=$align;
	}
	
	function setTableTitle($tableTitle){		
		foreach($tableTitle as $v=>$t){
			$tableTitle[$v]='\textbf{'.$this->stringToLatex($t).'}';
		}
		$this->tableTitle[$this->addIterator]='\tablehead{'.implode(' & ', $tableTitle).'\\\\}'.chr(10);
		$this->tableTitle[$this->addIterator].='\tablefirsthead{'.implode(' & ', $tableTitle).'\\\\}';
	}
	
	function addTableRow($tableRow){				
		foreach($tableRow as $v=>$t){
			$tableRow[$v]=$this->stringToLatex($t);
		}
		$this->tableRows[$this->addIterator][$this->rowcounter]=implode(' & ', $tableRow);
		if(isset($this->tableRows[$this->addIterator][$this->rowcounter-1])) $this->tableRows[$this->addIterator][$this->rowcounter-1].='\\\\';		
		++$this->rowcounter;
	}
};




?>
