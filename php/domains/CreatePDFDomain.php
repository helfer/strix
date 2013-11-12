<?php
/**
 * @version 1.0
 * @brief Serves restricted files to users with permission
 * 
 * This domain returns files that are stored in folders with restricted access.
 * It checks users permissions and file attributes before sending the file
 * 
 * @author: Jonas Helfer <jonas.helfer@ibosuisse.ch>
 * @date: 2009-05-25
 */
class createPDFDomain extends Domain{	
		
	public function serveContent($resource){
		

		//check if user is logged in: 
		$user = $GLOBALS['user'];

		//anonymous users can't create files
		if($user->id == ANONYMOUS_USER_ID)
		{
			$GLOBALS['RequestHandler']->setHttpHeader('HTTP/1.0 403 Forbidden');
			
			$head = '<title>403 forbidden</title>';
			
			$body = "<h1>Error: 403 Forbidden</h1>";
			$body .= "<p>You do not have the right to access this folder</p>";
			
			SqlQuery::getInstance()->execute("UPDATE log SET data = CONCAT_WS(' -> ',data,'denied.reason: no login') WHERE id='{$GLOBALS['request_id']}'");
				
			return array('type'=>'text/html','output'=>array('head'=>$head,'body'=>$body));
		}
		
		
		
		debug($this);
		debug($resource);
		
		//which pdf?
		if(isset($_GET['pdf']))
			$pdf = $_GET['pdf'];
		else $pdf='';
		debug('requested pdf: '.$pdf);
		
		//call appropriate function
		switch($pdf){
			case 'declaration_form': 
					if(isset($_SESSION['ibo2013_teammemberdetails_edituser_details'])){										
						require(getcwd().'/createPDF/ibo2013_declaration_form.php');				
						return array('type'=>'auto','output'=>'');													
					}
					break;
			case 'invoice':					
					require(getcwd().'/createPDF/ibo2013_invoice.php');				
					return array('type'=>'auto','output'=>'');																		
					break;
			case 'badges':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_badges.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'yearbook':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_yearbook.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'openingceremony':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_openingCeremony.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'flags':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_flags.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'certificates':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_certificates.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'adeline':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_adeline.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'coversheetsforpractica':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_coversheetsforpractica.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;
			case 'passwords':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_passwords.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;	
			case 'test_beni':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_testbeni.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;	
			case 'theoryres':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_student_theory_results.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;										
			case 'closing_ceremony':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_closingCeremony.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;	
			case 'ranking':
					if($user->primary_usergroup_id == 2 || $user->primary_usergroup_id == 27 || $user->primary_usergroup_id == 36){
						require(getcwd().'/createPDF/ibo2013_ranking.php');				
						return array('type'=>'auto','output'=>'');
					}
					break;				
			
		}
				

		//directory listing is disabled
		return array('type'=>'auto','output'=>'');	
		
	}
	
	


}

?>
