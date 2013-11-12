<?php
class ibo2013FinalizeInvoiceContent extends Content {

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $filedir = 'payment_files/';
	
	
	public function display(){
		$sql = SqlQuery::getInstance();		
				
		$xhtml = '<p class="title">Finalizing Payments</p>';
		
		//show process messages
		$xhtml .= $this->process_msg;
		
		//show all invoces
		$this->writeInvoiceOverview($sql, $xhtml);

		//show form to finalize payments	
		$xhtml .= '<p class="subtitle">Finalize Payment</p>';
		$form=$this->getForm('finalize_invoice_form');
		$xhtml .= $form->getHtml($this->id);	
		
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
	
	
	public function writeInvoiceOverview(&$sql, &$xhtml){		
		//read invoice state from DB				
		require_once(SCRIPT_DIR .'/content/enabled/ibo2013Invoice.php');
		$inv=new ibo2013Invoice($sql);		
		$res=$inv->getTotalAmountDue();

		//only write if there are entries
		if(count($res)==0) return false;

		$xhtml .= '<p class="subtitle">Invoice Overview</p>';
		
		//begin form to show buttons
		$xhtml.='<form id="finalized_invoices_form" action="http://www.ibo2013.org/createPDF/?pdf=invoice" method="post"><input type="hidden" name="nologo"/>';
		
		//write table header	
		$xhtml.='<table class="standard"><tr class="header"><td></td><td><b>Country</b></td><td><b>Total Fees</b></td><td><b>Amount Payed</b></td><td><b>Amount due</b></td></td>';
		
		$odd=1; $num=0; 	
		$total_fee=0;
		$total_payed=0;
		$total_due=0;	
		$num_due=0;
		foreach($res as $r){
			++$num; 			
			//check if finalized invoices have an amount due of 0
			if($r['invoice_finalized']==1 && $r['amount_due']!=0){								
				$query='update ibo2013_countries set invoice_finalized=0 where id='.$r['country_id'];
				$sql->start_transaction(); $sql->simpleQuery($query); $sql->end_transaction(); 
				$r['invoice_finalized']=0;				
			}
			
			//prepare table row			
			$xhtml.='<tr class="';
			if($num==count($res)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}		
			
			$xhtml.='" height="22">';
			if($r['invoice_finalized']) $xhtml.='<td><img src="/webcontent/images/tickboxok_small.png" style="float:left;"></td>';
			else $xhtml.='<td></td>';
			$xhtml.='<td><p class="monospace">'.$r['en'].'</p></td><td><p class="monospace">'.$inv->makeCHF($r['total_fees'], '').'</p></td><td><p class="monospace">'.$inv->makeCHF($r['amount_payed'], '').'</p></td><td><p class="monospace"';
			if($r['amount_due']>0) $xhtml.=' style="color: red;"';
			if($r['amount_due']<0) $xhtml.=' style="color: green;"';
			$xhtml.='>'.$inv->makeCHF($r['amount_due'], '').'</p></td><td>';
			//buttons to modify and delete
			$xhtml.='<td><button name="getinvoicepdf_'.$r['country_id'].'" type="submit" style="height:20px;';
			if($r['invoice_finalized']==1) $xhtml.=' background-color:green;">Invoice';
			else $xhtml.=' background-color:red;">Proforma Invoice';
			$xhtml.='</button></td></tr>';		
			$total_fee+=$r['total_fees'];
			$total_payed+=$r['amount_payed'];
			$total_due+=$r['amount_due'];	
			if($r['amount_due']>0) ++$num_due;
		}
		//add total column
		$xhtml.='<tr class="last"><td></td><td><p class="monospace"><b>Total</b></p></td><td><p class="monospace"><b>'.$total_fee.'</b></p></td><td><p class="monospace"><b>'.$total_payed.'</b></p></td><td><p class="monospace"><b>'.$total_due.'</b></p></td></tr>';
		$xhtml.='</table></form>';

		return true;
	}
	
	function process_finalize_invoice_form(){
		$frm = $this->getForm('finalize_invoice_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">'.(Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors'])).'</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
		
		$sql = SqlQuery::getInstance();

		//compute amount due and insert payment
		require_once(SCRIPT_DIR .'/content/enabled/ibo2013Invoice.php');
		$inv=new ibo2013Invoice($sql);				
		$res=$inv->getTotalAmountDue($values['country_id']);				
		$query='insert into ibo2013_payment (country_id, date, amount, file, payed_in_cash) values ('.$values['country_id'].', now(), '.$res[0]['amount_due'].', "", 1)';		
		$sql->start_transaction(); $res=$sql->execute($query); $ok = $sql->end_transaction();
		
		//update db
		$query='update ibo2013_countries set invoice_finalized=1 where id='.$values['country_id'];			
		$sql->start_transaction(); $res=$sql->execute($query); $ok = $sql->end_transaction(); 
		if(!$ok){
			 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013ManagePaymentsContent', $query);
		} else {
			$this->process_msg .= '<p class="success">Invoice has been finalized sucessfully!</p>';
		}
		return true;					
	}

	protected function finalize_invoice_form($vector){
		$newForm = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$newForm->setVector($vector);
		$newForm->setProportions(150,400);
				
		//choose delegation
		$sql = SqlQuery::getInstance();	
		$query='select c.id, c.en from ibo2013_countries c where c.invoice_finalized=0 order by c.en asc';		
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		$del=array();
		foreach($res as $r){
			$del[$r['id']]=$r['en'];
		}				
		$newForm->addElement('country_id',new Select('Delegation: ','country_id',$del));	
		
		$newForm->addElement('submit_finalize_invoice', new Submit('submit_finalize_invoice','Finalize Invoice'));
		
		return $newForm;
	}

	function getAmountDue(&$sql, $id=0){
		$query='select c.en, c.id as country_id, c.invoice_finalized, ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0) as total_fees, ifnull(p.amount,0) as amount_payed, ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0)-ifnull(p.amount,0) as amount_due from ibo2013_countries c left join (select sum(fee) as fee, country_id from ibo2013_team_registration group by country_id) tr on tr.country_id=c.id left join (select sum(fee) as fee, country_id from ibo2013_observer_registration group by country_id) aj on c.id=aj.country_id left join (select sum(f.fee) as fee, country_id from ibo2013_participants p, ibo2013_fees f where f.type="single_room" and p.single_room=1 group by country_id) sr on c.id=sr.country_id left join (select sum(amount) as amount, country_id from ibo2013_payment group by country_id) p on c.id=p.country_id left join (select sum(amount) as amount, country_id from ibo2013_extra_costs group by country_id) e on c.id=e.country_id';
		if($id>0) $query.=' where c.id='.$id;
		$query.=' order by c.en asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction();
		return $res;
	}
}
?>

