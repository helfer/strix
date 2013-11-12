<?php
class ibo2013ManageExtraCostsContent extends Content {

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $filedir = 'payment_files/';
	
	
	public function display(){
		$sql = SqlQuery::getInstance();		

		//delete?		
		foreach($GLOBALS['POST_KEYS'] as $p){
			$p=explode('_', $p);
			if(count($p)==2 && $p[0]=='deleteteextracostid' && is_numeric($p[1])){																
				$query='delete from ibo2013_extra_costs where id='.$p[1];				
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				if(!$ok){
					$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
					sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManagePaymentsContent', $query);
				} else {
					$this->process_msg .= '<p class="success">Payment has been removed sucessfully!</p>';
				}						 			 
			} 
		}
				
		$xhtml = '<p class="title">Managing Extra Costs</p>';
		
		//show process messages
		$xhtml .= $this->process_msg;
		
		//show form to add new payment		
		$xhtml .= '<p class="subtitle">Adding an Extra Cost</p>';
		if($this->process_status) $form=$this->remakeForm('extracost_insert_form');		
		else $form=$this->getForm('extracost_insert_form');
		$xhtml .= $form->getHtml($this->id);						
		
		$xhtml .= '<p class="subtitle">Existing extra Costs</p>';
		//show existing payments
		$this->writeExistingExtraCosts($sql, $xhtml);			
		
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
	
	
	public function writeExistingExtraCosts(&$sql, &$xhtml){		
		//read existing galleries from DB
		$query='select p.*, c.en from ibo2013_extra_costs p, ibo2013_countries c where c.id=p.country_id order by p.date desc, c.en asc';		
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
			$title=$r['title'];
			if(strlen($title)>30) $title=substr($title, 0, 28).'...';
			$xhtml.='"><td><p class="monospace">'.$r['date'].'</p></td><td><p class="monospace">'.$r['en'].'</p></td><td><p class="monospace">'.$r['amount'].'</p></td><td><p class="monospace">'.$title.'</a></td>';			
			//buttons to modify and delete
			$xhtml.='<td><button name="deleteteextracostid_'.$r['id'].'" type="submit" class="img_delete"/>';					
			$xhtml.='</td></tr>';			
		}
		$xhtml.='</table></form>';
		$xhtml.='<p>Payments can be edited (<img src="/webcontent/styles/img/b_edit.png">) or removed (<img src="/webcontent/styles/img/b_drop.png">) using the appropriate symbols.</p>';
		return true;
	}
	
	
	
	function process_extracost_insert_form(){
		$frm = $this->getForm('extracost_insert_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">'.(Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors'])).'</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();
		
		//insert into DB
		$paydate=$values['paydate']['year'].'-'.$values['paydate']['month'].'-'.$values['paydate']['day'];
		$query='insert into ibo2013_extra_costs (country_id, date, amount, title) values ('.$values['country_id'].', "'.$paydate.'", '.$values['amount'].', "'.$values['title'].'")';
		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManagePaymentsContent', $query);
		} else {
			$this->process_msg .= '<p class="success">Extra cost has been added sucessfully!</p>';
		}
		return true;					
	}

	protected function extracost_insert_form($vector){
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
		
		//title
		$title = new Input('Title: ','text','title','',array('size'=>30));
		$title->addRestriction(new StrlenRestriction(3,64));
		$newForm->addElement('title',$title);	

		
		$newForm->addElement('submit_add_extra_costs', new Submit('submit_add_extra_costs','add extra costs'));
		
		return $newForm;
	}

}
?>

