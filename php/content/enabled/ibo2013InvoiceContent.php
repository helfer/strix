<?php
class ibo2013InvoiceContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $userCountryId = -1;
	protected $filedir = 'payment_files/';
	protected $single_room_fee = 400;

	public function display(){
		$xhtml='';

		$sql = SqlQuery::getInstance();

		//prepare table
		$tablerows=array();
		$total=0;

		//get all entries
		require_once(SCRIPT_DIR .'/content/enabled/ibo2013Invoice.php');
		$inv=new ibo2013Invoice($sql);
		$entries=$inv->getInvoiceEntries($this->getMyCountryId($sql));

		//compile table
		if(count($entries)>0){
			if($inv->is_proforma){
				$xhtml.='<p class="subtitle">Proforma Invoice</p>';
				$xhtml.='<p class="text"><img src="/webcontent/images/exclamation.png" style="float:left;padding-right:10px;"><b>This is a proforma invoice</b> based on the registration details provided and payments received as of '.date('F j, Y').'. Please note that any future alterations of the registration may result in <b>additional fees</b>. Any difference between the amount paid and the amount due on July 14, 2013 will have to be paid on site in Bern in order to participate in the IBO.</p>';
			} else $xhtml.='<p class="subtitle">Invoice</p>';

			$xhtml.='<p class="text"><b>Currency:</b> All fees are to be paid in Swiss Francs (CHF). Current conversion rates as provided by the Google API are 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'EUR').' or 1 CHF &#8776; '.$this->getGoogleCurrencyConverter(1, 'USD').'. <b>These rates are provided without any warranty.</b></p>';

			$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Item</p></td><td><p class="monospacebold">Date</p></td><td><p class="monospacebold">Amount</p></td></tr>';
			$i=0;
			foreach($entries as $e){
				++$i;
				$xhtml.='<tr class="';
				if($i==count($entries)) $xhtml.='last';
				else {
					if($i % 2 == 0) $xhtml.='even';
					else $xhtml.='odd';
				}
				$xhtml.='"><td>';
				//name
				if(strlen($e['link'])>0){
					$xhtml.='<a class="monospace" href="'.$e['link'].'">'.$e['name'].'</a>';
				} else {
					$xhtml.='<p class="monospace">'.$e['name'].'</p>';
				}
				//date
				$xhtml.='</td><td><p class="monospace">'.$e['date'].'</p></td>';
				//amount
				$xhtml.='<td><p class="monospace"';
				if($e['amount']<=0) $xhtml.=' style="color: green;"';
				$xhtml.='>'.$inv->makeCHF($e['amount']).'</p></td></tr>';
			}
			//add total line
			$xhtml.='<tr class="last"><td><p class="monospacebold">Total Amount Due</p></td><td></td><td><p class="monospacebold" style="font-weight: bold;';
			if($total<=0) $xhtml.=' color: green;';
			$xhtml.='">'.$inv->makeCHF($inv->total).'</p></td></tr>';
			$xhtml.='</table>';

			$_SESSION['ibo2013_invoice']['country_id']=$this->getMyCountryId($sql);
			$xhtml.='<p class="bold">The invoice is also <a href="http://www.ibo2013.org/createPDF/?pdf=invoice">available as pdf</a></p>';


		} else {
			unset($_SESSION['ibo2013_invoice']);
			$xhtml.='<p class="problem">An invoice will be available after registration.</p>';
		}


		return $xhtml;
	}

	public function getGoogleCurrencyConverter($amount, $currency){
		$url='http://www.google.com/ig/calculator?hl=en&q='.$amount.'CHF=?'.$currency;

		$res=file_get_contents($url);
		$res=explode(',', $res);
		$res=explode('"', $res[1]);
		$res=round(preg_replace('/[^0-9\.]/', '', $res[1]), 3);
		return $currency.' '.$res;
	}


	public function getMyCountryId(&$sql){
		if($this->userCountryId > 0) return $this->userCountryId;
		//else: get it from DB!
		$query='';
		switch($GLOBALS['user']->primary_usergroup_id){
			case 2:
			case 36:
			case 27: $query='select id as country_id from ibo2013_countries c where c.alpha3="TTT"'; break;
			case 34:
			case 29: $query='select id as country_id from ibo2013_countries c where c.user_id='.$GLOBALS['user']->id; break;
			case 30:
			case 32:
			case 35:
			case 31: $query.='select country_id from ibo2013_participants p where p.user_id='.$GLOBALS['user']->id; break;
		}
		$sql->start_transaction();
		$res=$sql->simpleQuery($query);
		$ok = $sql->end_transaction();
		if(count($res)!=1){
			$this->process_msg .= '<p class="error">Unable to obtain country id!</p>';
			return -1;
		}
		$this->userCountryId = $res[0]['country_id'];
		return $res[0]['country_id'];
	}

}
?>

