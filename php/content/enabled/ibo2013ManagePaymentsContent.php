<?php
class ibo2013ManagePaymentsContent extends Content {

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $filedir = 'payment_files/';
	
	
	public function display(){
		$sql = SqlQuery::getInstance();		

		//delete?		
		foreach($GLOBALS['POST_KEYS'] as $p){
			$p=explode('_', $p);
			if(count($p)==2 && $p[0]=='deletetepaymentid' && is_numeric($p[1])){								
				//delete this payment				
				$query='select * from ibo2013_payment where id='.$p[1];
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				$res=$res[0];				
				//unlink files first
				$fileok=true;		
				if(strlen($res['file'])>3){
					$dir=HTML_DIR.'webcontent/'.$this->filedir;					
					if(!unlink($dir.$res['file'])){
						$this->process_msg .= '<p class="error">Deleting payment file failed!</p>';
						$fileok=false;
					}
				} 
				if($fileok){
					$query='delete from ibo2013_payment where id='.$p[1];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
					if(!$ok){
						$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
						sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManagePaymentsContent', $query);
					} else {
						$this->process_msg .= '<p class="success">Payment has been removed sucessfully!</p>';
					}
				}		 			 
			} 
		}
				
		$xhtml = '<p class="title">Managing Payments</p>';
		
		//show process messages
		$xhtml .= $this->process_msg;
		
		//show form to add new payment		
		$xhtml .= '<p class="subtitle">Adding a Payment</p>';
		if($this->process_status) $form=$this->remakeForm('payment_insert_form');		
		else $form=$this->getForm('payment_insert_form');
		$xhtml .= $form->getHtml($this->id);						
		
		$xhtml .= '<p class="subtitle">Existing Payments</p>';
		//show existing payments
		$this->writeExistingPayments($sql, $xhtml);			
		
		return $xhtml;
	}
	
	protected function fillModifyDetails(&$sql, $id){
		$_SESSION['current_payment_modify_id']=$id;
		$this->modify_id=$id;
		if($id>0){
			$query='select p.* from ibo2013_payment where p.id='.$id;
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if(count($res)==1){
				$this->modify_details=$res[0];
				return true;
			} else {			
				$this->modify_details=array();
				$this->modify_id=-1;
				$_SESSION['current_payment_modify_id']=$this->modify_id;
				return false;
			}
		}
	}
	
	
	public function writeExistingPayments(&$sql, &$xhtml){
		//read existing galleries from DB
		$query='select p.*, c.en from ibo2013_payment p, ibo2013_countries c where c.id=p.country_id order by p.date desc, c.en asc';		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		//only write if there are entries
		if(count($res)==0) return false;

		//begin form to show buttons
		$xhtml.='<form id="existing_payments_table_form" action="" method="post">';
		
		//write table header
		$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Date</p></td><td><p class="monospacebold">Delegation</p></td><td><p class="monospacebold">Amount</p></td><td><p class="monospacebold">File</p></td><td><p class="monospacebold">Edit</p></td></tr>';
		
		$odd=1; $num=0; 
		foreach($res as $r){
			++$num; 
						
			$xhtml.='<tr class="';
			if($num==count($res)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}
			
			$xhtml.='"><td><p class="monospace">'.$r['date'].'</p></td><td><p class="monospace">'.$r['en'].'</p></td><td><p class="monospace">'.$r['amount'].'</p></td><td>';			
			if(strlen($r['file'])>3) $xhtml.='<a class="monospace" href="/webcontent/'.$this->filedir.$r['file'].'">see pdf</a>';
			else {
				if($r['payed_in_cash']==1) $xhtml.='(in cash)';
			}
			$xhtml.='</td>';
			//buttons to modify and delete
			$xhtml.='<td><button name="deletetepaymentid_'.$r['id'].'" type="submit" class="img_delete"/>';					
			$xhtml.='</td></tr>';			
		}
		$xhtml.='</table></form>';
		$xhtml.='<p>Payments can be edited (<img src="/webcontent/styles/img/b_edit.png">) or removed (<img src="/webcontent/styles/img/b_drop.png">) using the appropriate symbols.</p>';

		return true;
	}
	
	
	
	function process_payment_insert_form(){
		$frm = $this->getForm('payment_insert_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">'.(Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors'])).'</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();
		
		//upload file
		$filename='';		
		if(isset($GLOBALS['HTTP_POST_FILES']['paymentfile']) && $GLOBALS['HTTP_POST_FILES']['paymentfile']['size']>0){
			$dir=HTML_DIR.'webcontent/'.$this->filedir;			
			$uploadedfile = $GLOBALS['HTTP_POST_FILES']['paymentfile'];
			
			//construct filename for payment file from delegation name and timestamp
			$query='select c.alpha3 from ibo2013_countries c where c.id='.$values['country_id'].' order by c.en asc';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 					
			$filename=$res[0]['alpha3'].'_'.date('His').'_'.substr(md5(microtime()),rand(0,26),2);
			//add extension
			$ex=explode('.', $uploadedfile['name']);
			$filename.='.'.$ex[count($ex)-1];
			
			//move file
			if(!move_uploaded_file($uploadedfile['tmp_name'], $dir.$filename)){
				$this->process_msg .= '<p class="error">Upload failed!</p>';
				return false;
			} else chmod($dir.$filename, 0644);			
		}
		
		//insert into DB
		$paydate=$values['paydate']['year'].'-'.$values['paydate']['month'].'-'.$values['paydate']['day'];
		if(!isset($values['payed_in_cash'])) $values['payed_in_cash']=0;
		if($values['payed_in_cash']=='') $values['payed_in_cash']=0;
		$query='insert into ibo2013_payment (country_id, date, amount, file, payed_in_cash) values ('.$values['country_id'].', "'.$paydate.'", '.$values['amount'].', "'.$filename.'", '.$values['payed_in_cash'].')';
		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManagePaymentsContent', $query);
		} else {
			$this->process_msg .= '<p class="success">Payment has been added sucessfully!</p>';
		}
		return true;					
	}

	protected function payment_insert_form($vector){
		$newForm = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$newForm->setVector($vector);
		$newForm->setProportions(150,400);
				
		//choose delegation
		$sql = SqlQuery::getInstance();	
		$query='select c.id, c.en from ibo2013_countries c order by c.en asc';		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$del=array();
		foreach($res as $r){
			$del[$r['id']]=$r['en'];
		}				
		$newForm->addElement('country_id',new Select('Delegation: ','country_id',$del));	
				
		//date
		$paydate = new DateWithThreeInputs('','paydate',array('day'=>'day', 'month'=>'month', 'year'=>'year'),array('day'=>date('j'),'month'=>date('n'), 'year'=>'2013'), array('begin'=>'2012-01-01', 'end'=>'2015-01-01'));
		$newForm->addElement('paydate', $paydate);	
		
		//amount
		$amount = new Input('Amount: ','text','amount','',array('size'=>6));
		$amount->addRestriction(new IsNumericRestriction());		
		$newForm->addElement('amount',$amount);	
		
		//file
		$paymentfile = new FileInput('File (pdf image): ', 'paymentfile');
		$paymentfile->addRestriction(new isEmptyOrPDFOrImageRestriction('paymentfile', 10*1048576));
		$newForm->addElement('paymentfile',$paymentfile);	

		//payed in cash
		$pc=new CheckboxWithText('','payed_in_cash',1, 'Amount was payed in cash.');		
		$newForm->addElement('payed_in_cash',$pc);
		
		$newForm->addElement('submit_add_gallery', new Submit('submit_add_gallery','add gallery'));
		
		return $newForm;
	}

}
?>

