<?php
class ibo2013BadgesContent extends Content {

	
	public function display(){
		$xhtml='';
		$sql = SqlQuery::getInstance();

		$xhtml.='<p class="text">To generate pdfs of all badges visit <a href="http://www.ibo2013.org/createPDF/?pdf=badges" target="_blank">this page here</a>.</p>';
		$xhtml.='<p class="text"><b>Note:</b> this script will turn for several minutes and will produce an output on the screen listing all participants for which a basge has been created. The last lien MUST be "DONE!" or the script failed. If it fails, ask Phäntu...</p>';
		$xhtml.='<p class="text">Once the pdfs have been created, you can download them here:<ul>';

		//search directory to get all pdf files
		$filedir = HTML_DIR.$this->mConfValues['file_directory'].'webcontent/downloads/badges_dsjnvfsdivjuoweojhfcdew_JHUIZH98/';		
		$files=scandir($filedir);
		foreach($files as $f){
			
			if(strpos($f, '.pdf') !== FALSE){				
				//is a pdf -> find modified data and print link to download
				$time=filemtime($filedir.$f);
				$size=round(filesize($filedir.$f)/1024/1024, 1);
				$xhtml.='<li><a href="http://www.ibo2013.org/webcontent/downloads/badges_dsjnvfsdivjuoweojhfcdew_JHUIZH98/'.$f.'" target="_blank">'.$f.'</a>  ('.$size.'Mb, last modified on '.date('M j Y H:i:s', $time).')</li>';
				
			}
    
		}
		$xhtml.='</ul><br/></p>';

		$xhtml.='<p class="text"><b>NOTE:</b>The pdf can always be downloaded. But without regenerating it changes in participant details since the last generation of the are not incorporated.</p>';

		return $xhtml;
	}

	
}
?>

