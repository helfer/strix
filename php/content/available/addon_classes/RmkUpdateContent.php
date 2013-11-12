<?php
/**
 * scan remark office files of exams and insert into database.
 **/

class RmkUpdateContent extends Content
{	
	// TODO: get the number of questions in exam out of the database.
	protected	$num_questions = 68;
	// TODO: get number of info fields out of database
	protected	$num_info_fields = 11;
	
	///@todo configure this in "admin"
	protected $key_order = array(
			'language',
			'first_name',
			'last_name',
			'co',
			'street',
			'zip',
			'city',
			'birthday',
			'school',
			'class',
			'teacher_id',
			'answer',
			'file'
			);
			
			//that's the info from the rmk file needed to create the user
	protected $key_info = array(
			'language', //(de = 1, fr = 2, it = 3)
			'first_name',
			'last_name',
			'co',
			'street',
			'zip',
			'city',
			'birthday', //format: dd.mm.yyyy
			);
	
	
	private $file_extensions = array('rmk');
	protected $process_msg = '';

	function display()
	{

				$html = $this->process_msg;
				
				$html .= $form = $this->getForm('upload_file_form')->getHtml($this->id);	
				
				return $html;
	}
	
	
	public function upload_file_form($vector)
	{
		$form = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$form->setVector($vector);
		$form->setProportions('10em','28em');
		
		$q_args = array('query'  => "SELECT id, de FROM `IBO_exam` WHERE closed = 0 ORDER BY date DESC",
						'keys'  => array('id'),
						'values'=> array('de')
						);
		$arg_ary = array('label'=>'exam:','name'=>'exam_id','query_args'=>$q_args);
		$form->addElement('exam_id',new DataSelect($arg_ary));
		
		$file = new FileInput('rmk-file:','rmkfile');
		$form->addElement('rmkfile',$file);

		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
	}
	
	function process_upload_file_form()
	{
			
			notice("processing uploaded file");
			
			$form = $this->getForm('upload_file_form');
			
			if ( !$form->validate() )
			{
				$this->process_msg = '<p class="error">Form contains errors</p>';
				return FALSE;
			}
			
			$vals = $form->getElementValues();
		
			if(!isset($_FILES['rmkfile']))
			{
				$this->process_msg = '<p class="error">No file uploaded!</p>';
				return;
			}
			
			$filename = basename($_FILES['rmkfile']['name']);
				
				
			//SECURITY CHECK:
			$ext = end(explode('.',$filename));
			if(!in_array($ext,$this->file_extensions))
			{
				$this->process_msg = '<p class="error">The file extension "'.$ext.'" is not allowed!</p>';
				return;
			}
			
			
			//parse file:
			$file_content_str = file_get_contents($_FILES['rmkfile']['tmp_name']);
			
			//convert to UTF-8 !!
			$file_content_str = mb_convert_encoding($file_content_str, 'UTF-8', mb_detect_encoding($file_content_str, 'UTF-8, ISO-8859-1', true));
			
			$ok = $this->parse_rmk_string($file_content_str,$vals['exam_id'],$filename);
			
			if ($ok)
				$this->process_msg .= '<p class="success">File successfully updated.</p>';
			else
				$this->process_msg .= '<p class="error">An error occurred. Solve the problem!</p>';

			return TRUE;
	}
	
	protected function parse_rmk_string($rmk_str,$exam_id,$filename)
	{
		
		notice("parsing rmk string");
		
		//get filename and number of fields out of the first line
		$firstline = strtok($rmk_str,"\n");
		list ( , ,$num_fields) = explode("\t",$firstline);
		
		
		
		echo basename($filename) . '<br />' . $num_fields . '<br />';
		
		
		//assert: questions + info-fields == num_fields
		if($num_fields != $this->num_questions + $this->num_info_fields)
		{
			$this->process_msg = '<p class="error">unexpected number of question + info fields. Did you select the right exam?</p>';
			return FALSE;
		}
		
		
		$exam_batch = array();
		
		//parse string line by line
		while($line = strtok("\n"))
		{
			//echo 'new line<br />';	
			if(strlen($line) > 2*$num_fields + 1)
			{
				$line = substr($line,0,strpos($line,"")); //cut off line at this char, which follows immediately after the filename.
				
				$tokens = explode("\t",$line);
				$info_fields = array_slice($tokens,0,$this->num_info_fields);
				$answer_fields = array_slice($tokens,$this->num_info_fields,$this->num_questions);
				$exam_file = trim( strrchr($tokens[$num_fields],"\\"), "\\" ); //get the !!!WINDOWS!!! filename of the student answersheet image file
				
				//put all into one data structure
				$exam_ary = $info_fields;
				$exam_ary []= $answer_fields;
				$exam_ary []= $exam_file;
				
				$exam_ary = array_combine($this->key_order,$exam_ary);
				
				
				echo $exam_file .'<br />';
				$exam_batch [] = $exam_ary;
				
			}
			else //there are these strange interspersed lines with no relevant information. Identify them by length.
			{
				//VOID
				//echo 'line discarded, length = '.strlen($line).'<br />';
			}
		}
		
		
			
		return $this->insert_DB($exam_batch,$exam_id);	
	
	}
	
	
	/**
	 * Inserts the student exam data into the 4 different tables: user, student, student_exam and scanned_exam (for verification)
	 */
	

	/**
	 * Take a batch of scanned exams and insert them into the db.
	 */
	protected function insert_DB($exam_batch,$exam_id)
	{
		$sql = SqlQuery::getInstance();

		$sql_error = FALSE;
		
		$sql->start_transaction();
		
		$updated_se = 0;
		$updated_sc = 0;
	
		// one big loop to accumulate all data...
		foreach($exam_batch as $exam)
		{
			
			//language...
			//TODOTODOTODOTODOT get this info from DB!
			if(!is_numeric($exam['language']))
				$exam['language'] = str_replace(array('de','fr','it'),array(1,2,3),$exam['language']);
				
			//change birthday to right format:
			$date = date_parse($exam['birthday']);
			$exam['birthday'] = $date['year'].'-'.$date['month'].'-'.$date['day'];
					
					
			//check if in scanned exams
			$ok = $sql->singleRowQuery("SELECT * FROM `IBO_scanned_exam` WHERE `exam_file`='{$exam['file']}'");
			if(!$ok)
				$this->process_msg .= '<p class="error">(Scanned_exam) File not found in DB: '.$exam['file'].'</p>';
			else
			{
					$aff = $sql->updateQuery('user',array('birthday'=>$exam['birthday']),array('id'=>$ok['user_id']));
					if($aff)
						$this->process_msg .= '<p class="error">bday updated for user id: '.$ok['user_id'].'</p>';
					if(!is_numeric($aff))
						$this->process_msg .= '<p class="error">Error on bd-update for user: '.$ok['user_id'].'</p>';		
				
						
			}	
			
			
			
			//check if in student exams		
			$ok = $sql->singleValueQuery("SELECT 1 FROM `IBO_student_exam` WHERE `filename`='{$exam['file']}'");
			if(!$ok)
				$this->process_msg .= '<p class="error">(Student exam) File not found in DB: '.$exam['file'].'</p>';		
					
				
			
			$update_se = array(
					'answer'=>implode('#',$exam['answer']),
					'language_id'=>$exam['language']
					);
					
				$key_se = array('filename'=>$exam['file']);
					
				//perform insert here and store student_exam_ids for future reference
				$se_ok = $sql->updateQuery('IBO_student_exam',$update_se,$key_se);
				
				if(!is_numeric($se_ok))
				{
					$sql_error = TRUE;
					//user could not be created...
					$this->process_msg .= '<p class="error">Error on update: '.mysql_error().'</p>';
					return;	
				}
				else
					$updated_se += $se_ok;
				
				$update_sc = array(
					'answer_fields'=>implode('#',$exam['answer'])
					);
				$key_sc = array(
					'exam_file' => $exam['file']
					);
					
				$sc_ok = $sql->updateQuery('IBO_scanned_exam',$update_sc,$key_sc);
				if(!is_numeric($sc_ok))
				{
					$sql_error = TRUE;
					//user could not be created...
					$this->process_msg .= '<p class="error">Error on update: '.mysql_error().'</p>';
				}
				else
					$updated_sc += $sc_ok;
		}
		
		if ($sql->end_transaction())
			
			{
				$this->process_msg .= '<p class="success">'.$updated_se.' student exams updated</p>';
				$this->process_msg .= '<p class="success">'.$updated_sc.' scan lines updated</p>';
				return TRUE;
			}
			
		else
		{
			$this->process_msg .= '<p class="error">Transaction aborted!</p>';
			return FALSE;
		}
		
	}

}
?>
