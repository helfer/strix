<?php
	//TODO: must somehow handle errors as well to be able to send e-mail messages
	
	
	class NanoException extends Exception{
		protected $code = 1;
		protected $must_report = FALSE;
		protected $mail_report = FALSE;
		
		
		public function __construct($message = null,$code = 0,$mail_report=FALSE){
			parent::__construct($message,$code);
			$this->mail_report = $this->must_report || $mail_report;
		}
		
		
		
		/* Nano Exception Handler
		 * ************************************************************/
		public static function handler($exc){
			if($exc instanceof NanoException){
				
				$retry = FALSE;
				
				switch($exc->getCode()){
					case 44: //UserInputException
						echo 'USER INPUT EXCEPTION!'."\n";
						echo $exc->getMessage()."\n";
						$retry = TRUE;
						break;
					case 404: //HTML Exception
						echo 'HTML EXCEPTION!';
						break;
					case 33: //Session Exception
						session_destroy();
						$retry = TRUE; //try again
						break;
					case 37: //Login Exception
						break;
					case 6: //Nano Domain Exception
						error('NANO DOMAIN EXCEPTION');
						//catch $exc;
						break;
					case 14: //SQL Exception
						echo 'SQL EXCEPTION!!';
						break;
					default:
						//die('stupid');	
				}
				
				if ($retry)
				{
					return TRUE;
				}
				else
				{
				echo $exc->getMessage().'<br />';
				echo nl2br($exc->getTraceAsString());
				return FALSE;
				}
				
				
			} else {
				echo '<b>Exception thrown at:</b>'.$exc->getFile().':'.$exc->getLine().' is not NanoException';
				echo $exc->getTraceAsString();
				//exit();
			}
		}
		
	}
	
	class SessionException extends NanoException
	{
		protected $code = 33;
		protected $must_report = TRUE;			
	}
	
	class TreeException extends NanoException
	{
		protected $code = 809;
		protected $must_report = FALSE;			
	}
	
	class UserInputException extends NanoException{
		protected $code = 44;
		protected $must_report = TRUE;	
	}
	
	
	class SqlException extends NanoException{
		protected $code = 14;
		protected $must_report = TRUE;
	}
	
	class DatabaseException extends NanoException{
		protected $code = 11;
		protected $must_report = TRUE;
	}
	
	class NanoDomainException extends NanoException{
		protected $code = 6;
		protected $must_report = TRUE;
	}
	
	class HtmlException extends NanoException{
		protected $code = 404;
		protected $must_report = TRUE;
	}
	
	class FormException extends NanoException{
		protected $code = 771;
		protected $must_report = FALSE;
	}
	
	


?>
<?php /*
class Exception
{
    protected $message = 'Unknown exception';   // exception message
    protected $code = 0;                        // user defined exception code
    protected $file;                            // source filename of exception
    protected $line;                            // source line of exception

    function __construct($message = null, $code = 0);

    final function getMessage();                // message of exception 
    final function getCode();                   // code of exception
    final function getFile();                   // source filename
    final function getLine();                   // source line
    final function getTrace();                  // an array of the backtrace()
    final function getTraceAsString();          // formated string of trace

    // Overrideable
    function __toString();                       // formated string for display
} */
?>
