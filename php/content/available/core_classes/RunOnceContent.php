<?php
/*
 *      runoncecontent.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 */

class RunOnceContent extends Content
{

	public function display()
	{
		$xhtml = $this->getForm('run_once_form')->getHtml($this->id);
		
		if(isset($this->mActivatedForm))
		{
			$file = $this->mActivatedForm->getElementValue('file');
			$filename = RUN_ONCE_DIR.$file;
			$xhtml .= $this->run_script($filename);
		}
		
		return $xhtml;
	}
	
	//returns a string of all the output of the script
	protected function run_script($filename)
	{
		if(!is_file($filename))
			return 'cannot read file';

		ob_start();
		
			include($filename);
			
		$output = ob_get_clean();
		
		return $output;
	}
	
	
	public function run_once_form()
	{
		$form = new SimpleForm(__METHOD__);


		$files = scandir(RUN_ONCE_DIR);
		foreach($files as $file)
		{
			if (strpos($file,'.php'))
				$options [$file]= $file;
		}
		
		$form->addElement('file', new Select('Select file to run: ','filename',$options));
		$form->addElement('submit',new Submit('action','submit','run'));
		
		return $form;	
	}
	
	
	public function process_run_once_form()
	{
		//VOID [yeah, not perfect, I know...]
	}
	
}


?>
