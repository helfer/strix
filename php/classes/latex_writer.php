<?php

/* I started transforming the script into this class when I realized: This is all crap. I would 
 * do it completely differently. And it's gonna be done completely differently next year anyway, so why bother???
 * 
 * That's when I stopped...
 * 
 * 
 */

//this script writes an ibo exam document into a latex document
//it takes two arguments:
//- the file name of the exam file as txt
//- a filename of a file containing information to randomize the exam

define('ANSWERSHEET_CAPTION_VSPACE','2pt');

define('STD_OUT',1);
define('FILE_OUT',2);

define('STATE_FIRSTPAGE',1);
define('STATE_BODY',2);

class LatexExamConverter
{
	
	protected $outputMode = STD_OUT; //can be STD_OUT or FILE_OUT
	protected $randomArray = array();
	protected $state = STATE_FIRSTPAGE;
	
	protected $num_questions = 0;

	//parse exam file
	protected $c=array();
	protected $special=array();
	protected $answersheet=array();
	protected $i_line = 0;
	
	//---------------------------------------------------------------------
	//functions

	//write to latex file
	protected function fw($text)
	{
		global $latex; //output file
		fwrite($latex, $text.chr(10));
	}

	//not my favourite function. Why not start using utf-8 with LaTeX?
	public static function replaceUmlaut($text)
	{
		return $text;
		/*$from=array('ä','ö','ü','Ä','Ö','Ü','é','è','ê','à','â','ï','î', 'ì','ù','ô','œ', 'ç','ë');
		$to=array('\"{a}','\"{o}','\"{u}','\"{A}','\"{O}','\"{U}',"\'{e}",'\`{e}','\^{e}','\`{a}','\^{a}','\"{i}','\^{i}','\`{i}','\`{u}','\^{o}', '\oe{}','\c{c}','\"{e}');
		return str_replace($from, $to, $text);

	*/ }

	
	public function convertFile($filename)
	{
		if(file_exists($filename)) 
			$exam=fopen($argv[1], 'r');
		else
			return FALSE;
			
			
		while($line=trim(fgets($exam)))
		{
			$this->parseLine($line);	
		}
	}
	
	public function convertString($string)
	{
		$line = strtok($string,"\n");
		while($line != FALSE)
		{
			$this->parseLine(trim($line));
			$line = strtok("\n");
		}
		
		$this->wrapUp();	
	}
	
	public function useRandomFile($filename)
	{
		if(file_exists($filename))
		{
			
			$randomize=array();
			$randnum=0;
			$r=fopen($argv[2], 'r');
			while($line=fgets($r))
			{
				$this->randomArray []= explode(';',$line);
			}
			fclose($r);
			echo 'File will be randomized!'.chr(10);
			
			$this->randomize = TRUE;
		} 
		else 
		{
			return FALSE;
		}
	}
	
	public function useRandomString($string)
	{
		$exp = explode("\n", $string)
		foreach($exp as $line)
		{
			$this->randomArray []= explode(';',$line);	
		}
		$this->randomize = TRUE;
	}
	
	
	public function writeHeaders()
	{
		//write headers
		fw('\documentclass[a4paper, twocolumn, 10pt]{scrartcl}');
		fw('\pagestyle{empty}');
		fw('%set language option');
		$a=explode('_', $a[count($a)-2]);
		fw('\usepackage['.$a[count($a)-1].']{optional}');
		fw('\include{ibo_exam_latex_preamble}');
		fw('%-----------------------------------------------------------------------');
		fw('\begin{document}');

		//first explnanations
		fw('\ibofrontpage{%');
	}
	
	//parses one line and generates output
	public function parseLine($line)
	{
		switch($this->state)
		{
			case STATE_FIRSTPAGE:
				if($line == '!questions_start!')
				{
					$this->state = STATE_BODY;
					fw('}');
					fw('\clearpage');
				}
				else
					fw(replaceUmlaut($line).'\par\vspace{8pt}');
				
				break;
			case STATE_BODY:
			
				break;
			default:
				throw new Exception('Unknown state: '.$this->state);
			
	}
	
	public function wrapUp()
	{
		
	}


	while($line=fgets($exam))
	{
		$i_line++;
		$line=trim($line);
		if($line == '')
		{ //new line, question entry is over
			$num_questions++;
			switch(count($c)){
				case 1: 
				
				
					if(isset($special['figure']))
					{ //a question with only a figure
						fw('\questionWithFigure{'.$c[0].'}{'.$special['figure'].'}');
						$answersheet[]='\answer';
					} 
					else 
					{ //section heading
						$num_questions--; //not a question
					
						fw('\section{'.$c[0].'}');
						if(count($answersheet)>0) $temp='\vspace{'.ANSWERSHEET_CAPTION_VSPACE.'}';
						else $temp='';
						$answersheet[] = $temp.'\section{'.$c[0].'}\vspace{'.ANSWERSHEET_CAPTION_VSPACE.'}';
					}					
					break;
					
				case 4: //link question
					fw('\begin{linkQuestion}{'.$c[0].'}{'.$c[1].'}{'.$c[2].'}{'.$c[3].'}\end{linkQuestion}');
					$answersheet[]='\answer';
					break;
						
				case 6: //multiple choice, 5 answers
					if(isset($special['figure'])) 
						fw('\begin{multiplechoiceWithFigure}{'.$c[0].'}{'.$special['figure'].'}');
					else 
						fw('\begin{multiplechoice}{'.$c[0].'}');
						
					//potential randomizer!					
					if(isset($randomize) && !isset($special['dont_randomize']))
					{
						foreach($randomize[$num_questions] as $r)
						{
							$r = trim($r);
							if(isset($c[$r])) 
								fw('\choice{'.$c[$r].'}');
							else
							{
								echo "$i_line: question $num_questions answer $r not set\n";
								var_dump($c);
							}
						}						
					} 
					else 
					{
						for($i=1;$i<6;++$i) 
						{
							fw('\choice{'.$c[$i].'}');
						}
					}								
					++$randnum;		
									
					if(isset($special['figure'])) 
						fw('\end{multiplechoiceWithFigure}');
					else 
						fw('\end{multiplechoice}');
						
					$answersheet[]='\answer';
					
					break;
				default: 
					if(count($c)>5)
					{ //no double empty lines... or lines at the end of the file
						if(isset($special['matching'])){ //matching
							$l='\begin{matching}{'.$c[0].'}{';
							
							$numpos=(count($c)-6)/2;
							for($i=0; $i<$numpos;++$i) 
								$l.='\possibility{'.$c[$i+1].'}';
								
							$l.='}{';
							
							for($i=0; $i<$numpos;++$i) 
								$l.='\possibility{'.$c[$i+1+$numpos].'}'.chr(10);
								
							fw($l.'}');
							
							for($i=0;$i<5;++$i) 
								fw('\choice{'.$c[$i+1+2*$numpos].'}');
								
							fw('\end{matching}');
														
						} else { //selection choice
							if(isset($special['figure'])) 
								$l='\begin{selectionchoiceWithFigure}{'.$c[0].'}{';
							else 
								$l='\begin{selectionchoice}{'.$c[0].'}{';					        
							
							for($i=1; $i<(count($c)-5);++$i) 
								$l.='\possibility{'.$c[$i].'}'.chr(10);
							
							$l.='}';
							if(isset($special['figure'])) 
								$l.='{'.$special['figure'].'}';
							
							for($i=(count($c)-5); $i<count($c);++$i)
								$l.='\choice{'.$c[$i].'}'.chr(10);
								
							if(isset($special['figure'])) 
								fw($l.'\end{selectionchoiceWithFigure}');
							else 
								fw($l.'\end{selectionchoice}');				
						}
							$answersheet[]='\answer';
					}	
					else
					{
						echo("\n$i_line:".' wrong number of lines for question ' . $num_questions . ":\t" . substr($c[0], 10) . ' ...'."\n");		
					}		
						break;
			}
			$c=array();
			$special=array();
		} 
		else 
		{ //the line is part of a question, add to array
		
			if($line{0} == "!")
			{
				$m=explode('!', $line);
				$special[$m[1]]=$m[2];
			} else {
				$c[]=replaceUmlaut($line);
			}
		}	
	}

	//write answersheet

	fw('\clearpage');
	fw('\begin{minipage}{18.5cm}');
	fw('\answersheetheader');
	fw('\begin{multicols}{3}');
	foreach($answersheet as $a) fw($a);	
	fw('\end{multicols}');
	fw('\end{minipage}');
	//close file
	fw('\end{document}');
	fclose($latex);

	echo "\n $num_questions questions parsed\n";
}
?>
