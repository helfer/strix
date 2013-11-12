<?php

class FileManagerContent extends Content{
	
	
	protected $mConfKeys = array('file_directory','maxsizeMB');
	protected $mConfValues = array(DOWNLOADS_DIR,'32');


	protected $process_msg;

	function display(){
	
		$xhtml = '<div class="bordered">';
		
		//define absolute directory (UNIX)
		$path = HTML_DIR . $this->mConfValues['file_directory']; //TODO: not yet dynamic everywhere in this class!
		
		
		/*REMOVE FILES:
		************************************/
		//TODO: don't use GET or POST the way it's used now. find a good solution applicable to everything.
		
		//CONFIRM FIRST:
		if(isset($_GET['removefile']))
		{
			$filename = $_GET['removefile'];
			if(file_exists($path.$filename))
				return $this->getForm('confirm_delete_form',array($_GET['removefile']))->getHtml($this->id);
			else 
				$xhtml.= '<b style="color:red">file to remove does not exist!</b><br />';
		}
		

		/* UPLOAD FORM:
		*************************************/			
			
		//message passed from form processing (errors, notifications)
		$xhtml .= $this->process_msg;
		
		$xhtml .= '<p><b>&nbsp;UPLOAD NEW FILES:</b></p>';
		
		$xhtml .= $this->getForm('upload_file_form')->getHtml($this->id);

		
		
		/* EXISTING FILES:
		*************************************/	
		
		
		//using the opendir function
		$dir_handle = @opendir($path);
		
		//echo $path;
		$xhtml .= '<b>Existing Files:</b> <br/>';
		
		//running the while loop
		while ($filename = readdir($dir_handle)){
			if($filename != '.' && $filename != '..')
				$xhtml .= '<p><a href="/'.$this->mConfValues['file_directory'] . $filename.'"> show </a> 
					<a href="?removefile='.$filename.'"> remove </a>&nbsp;'.str_pad(round((filesize($path.$filename)/1024),1),8,'0',STR_PAD_LEFT).' kB &nbsp;'.$filename.'</p>';
		}
		//closing the directory
		closedir($dir_handle);
			
		return $xhtml.'</div>';
	}
	
	public function upload_file_form($vector)
	{
		$form = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$form->setVector($vector);
		$form->setProportions('10em','28em');
		
		$form->addElement('maxsize',new Hidden('MAX_FILE_SIZE',$this->mConfValues['maxsizeMB']*1000000));
		
		
		$form->addElement('max',new HtmlElement('<span style="color:#FF0000">The maximum filesize for upload is '.$this->mConfValues['maxsizeMB'].'MB!</span>'));
		
		$file = new FileInput('File:','uploadedfile');
		$form->addElement('uploadedfile',$file);
		
		$fname = new TextInput('Filename (optional):','filename','');
		$fname->addRestriction(new StrlenRestriction(0,128));
		$form->addElement('filename',$fname);		

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	
	function process_upload_file_form()
	{
			notice("processing uploaded file");
			
			$form = $this->getForm('upload_file_form');
			
			$vals = $form->getElementValues();
		
			if(!isset($_FILES['uploadedfile']))
			{
				$this->process_msg = '<p class="error">No file uploaded!</p>';
				return;
			}
			
			//if no filename is entered, leave the name as it is.
			if($vals['filename'] != '')
				$filename =  $vals['filename'];
			else
				$filename = basename($_FILES['uploadedfile']['name']);
				
				
			//SECURITY CHECK:
			$ext = end(explode('.',$filename));
			if(!in_array($ext,$GLOBALS['config']['upload']['allowed_types']))
			{
				$this->process_msg = '<p class="error">The file extension "'.$ext.'" is not allowed!</p>';
				return;
			}
			
			//define absolute path
			$folder = HTML_DIR . $this->mConfValues['file_directory']; //TODO: not yet dynamic everywhere in this class!
		
			
			if(file_exists( $folder.basename($filename)))
			{
				$this->process_msg = '<p class="error">A file with the same name aleady exists! Please chose a new name!</p>';
				return;
			}
			
			
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $folder.basename($filename))) 
			{
    			$this->process_msg = 'The file '.$_FILES['uploadedfile']['name'].' has been uploaded as '.$filename;
    			
    			//print(fileinfo($path.basename($filename)));
    			
			} else
			{
				switch($_FILES['uploadedfile']['error'])
				{
					case UPLOAD_ERR_INI_SIZE:
						$this->process_msg = '<p class="error">The file you are trying to upload is too big! [php.ini]</p>';
						break;
					case 2:
						$this->process_msg = '<p class="error">The file you are trying to upload is too big! [MAX_FILESIZE_POST]</p>';
						break;
					case UPLOAD_ERR_PARTIAL:
						$this->process_msg = '<p class="error">The file was only partially uploaded. Please try again!</p>';
						break;
					case UPLOAD_ERR_NO_FILE:	
						$this->process_msg = '<p class="error">The file was not uploaded. Please try again!</p>';
						break;
					case UPLOAD_ERR_NO_TMP_DIR:	
						$this->process_msg = '<p class="error">Server configuration error. The file can not be uploaded. Notify admin if problem persists.</p>';
						break;
					case UPLOAD_ERR_CANT_WRITE:	
						$this->process_msg = '<p class="error">The file could not be written. Please notify the admin if this problem persists.</p>';
						break;
					case UPLOAD_ERR_EXTENSION:	
						$this->process_msg = '<p class="error">The file you are trying to upload has an invalid extension!</p>';
						break;
					default:
						$this->process_msg = '<p class="error">Unknown upload error, please try again!</p>';
				}
				
				print_r($_FILES['uploadedfile']);
    			//$this->process_msg = '<p class="error">There was an error uploading the file, please try again!</p>';
			}

	
	}
	
	
	public function confirm_delete_form($vector)
	{
		$form = new SimpleForm(__METHOD__,'.');
		$form->setVector($vector);
		$form->addElement('info',new HtmlElement('Do you really want to remove the file '.$vector[0].'?'));
		$form->addElement('delete',new Submit('delete','YES'));
		$form->addElement('cancel',new Submit('cancel','NO'));
		
		return $form;
	}
	
	public function process_confirm_delete_form()
	{
		$form = $this->getForm('confirm_delete_form');
		
		//define absolute path
		$folder = HTML_DIR . $this->mConfValues['file_directory']; //TODO: not yet dynamic everywhere in this class!
		
		
		switch($form->getButtonPressed())
		{
			case 'delete':
			
				//using vector instead of hidden to protect from modifications.
				unlink($folder.reset($form->getVector()));
				$this->process_msg = '<b style="color:red">file removed</b>';
				break;
			case 'cancel':
				$this->process_msg = '<b>file NOT removed!</b>';
				break;
			default:
				error('no action');
		}
		
	}
}

?>
