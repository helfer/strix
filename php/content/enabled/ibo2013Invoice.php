<?php

class ibo2013Invoice{
	
	protected $sql;
	protected $country_id;
	public $total=0;
	public $is_proforma=true;
	
	public function __construct($SQL){
		$this->sql=$SQL;		
		$this->total=0;				
	}
	
	public function getInvoiceEntries($country_id){
		$entries=array();
		$this->country_id=$country_id;
		//add team registration with modifications
		$hist=$this->getTeamRegistrationHistoryFromDB();		
		if(count($hist)>0){						
			foreach($hist as $h){
				if($h['fee']>0){					
					if($h['type']=='initial') $name='Initial Registration';
					else $name='Registration update';					
					$date=date('M d Y', strtotime($h['timestamp']));
					$entries[]=array("name"=>$name, "date"=>$date, "amount"=>$h['fee'], 'link'=>'/Participate/registration/Delegation/');
					$this->total+=$h['fee'];
				}				
			}			
		}
		
		//add additional jury / observers
		$hist=$this->getAdditionalJuryRegistrationHistoryFromDB();		
		if(count($hist)>0){
			foreach($hist as $h){
				if($h['fee']>0){
					$tmp='<td><a href="';
					$name=''; $link='';
					if(strpos($h['category'], 'Observer')!==false) $link='/Participate/registration/observer/';
					else $link='/Participate/registration/AdditionalJuryMember/';					
					if(strpos($h['category'], 'Jury')!==false) $name.='Additional ';
					$name.=$h['category'];
					if($h['cancellation_date']>$h['registration_date']){
						 $name.=' Cancellation Fee';
						 $date=date('M d Y', strtotime($h['cancellation_date']));
					} else $date=date('M d Y', strtotime($h['registration_date']));
					$entries[]=array("name"=>$name, "date"=>$date, "amount"=>$h['fee'], 'link'=>$link);
					$this->total+=$h['fee'];
				}				
			}				
		}
		
		//add single rooms		
		$hist=$this->getSingleRoomsFromDB();	
		if(count($hist)>0){
			foreach($hist as $h){
				if($h['title']!="") $title=$h['title'].' '; else $title='';
				$entries[]=array("name"=>'Single room ('.$title.$h['last_name'].')', "date"=>'-', "amount"=>$h['amount'], 'link'=>'/Participate/registration/personalDetails/');
				$this->total+=$h['amount'];			
			}				
		}
		
		//add extra costs
		$hist=$this->getExtraCostsFromDB();	
		if(count($hist)>0){
			foreach($hist as $h){				
				$entries[]=array("name"=>$h['title'], "date"=>date('M d Y', strtotime($h['date'])), "amount"=>$h['amount'], 'link'=>'');
				$this->total+=$h['amount'];			
			}				
		}
		
		
		//add Payments		
		$hist=$this->getPaymentsFromDB();		
		if(count($hist)>0){
			foreach($hist as $h){
				if($h['amount']!=0){
					//what text? Payment received, in cash, or payed back?
					$name='';
					if($h['amount']<0) $name.='Refund of Overpayment';
					else $name.='Payment Received';
					if($h['payed_in_cash']==1) $name.=' (in cash)';					
					$link='';
					if(strlen($h['file'])>3) $link='/webcontent/'.$this->filedir.$h['file'];
					$date=date('M d Y', strtotime($h['date']));										
					$entries[]=array("name"=>$name, "date"=>$date, "amount"=>-$h['amount'], 'link'=>$link);
					$this->total-=$h['amount'];
				}				
			}				
		}
		
		//check if it is proforma
		if($total==0){
			$query='select invoice_finalized from ibo2013_countries where id='.$this->country_id;
			$this->sql->start_transaction(); 	
			$res=$this->sql->simpleQuery($query);
			$this->sql->end_transaction(); 
			//if($res[0]['invoice_finalized']) $this->is_proforma=false;
		}
		return($entries);		
	}
	
	public function getAdditionalJuryRegistrationHistoryFromDB(){
		$query='select tr.*, d.id as category_id, d.category, u.first_name, u.last_name from ibo2013_observer_registration tr, user u, ibo2013_delegation_categories d where tr.country_id='.$this->country_id.' and tr.user_id=u.id and d.id=tr.delegation_category_id order by tr.registration_date asc';		
		$this->sql->start_transaction(); 	
		$res=$this->sql->simpleQuery($query);
		$this->sql->end_transaction(); 
		return $res;
	}	
	
	public function getTeamRegistrationHistoryFromDB(){
		$query='select tr.*, u.first_name, u.last_name, f.last_date_timestamp from ibo2013_team_registration tr, ibo2013_fees f, user u where tr.country_id='.$this->country_id.' and tr.user_id=u.id and tr.fee_id=f.id order by tr.timestamp asc';		
		$this->sql->start_transaction(); 	
		$res=$this->sql->simpleQuery($query);
		$this->sql->end_transaction(); 
		return $res;
	}
	
	public function getSingleRoomsFromDB(){
		$query='select t.title, p.first_name, p.last_name, f.fee as amount from ibo2013_participants p, ibo2013_titles t, ibo2013_fees f where p.title_id=t.id and p.single_room=1 and f.type="single_room" and p.country_id='.$this->country_id.' order by p.last_name asc';				
		$this->sql->start_transaction(); 	
		$res=$this->sql->simpleQuery($query);
		$this->sql->end_transaction(); 
		return $res;
	}
	
	public function getPaymentsFromDB(){
		$query='select p.* from ibo2013_payment p where p.country_id='.$this->country_id.' order by p.date asc, amount desc';		
		$this->sql->start_transaction(); 	
		$res=$this->sql->simpleQuery($query);
		$this->sql->end_transaction(); 
		return $res;
	}
	
	public function getExtraCostsFromDB(){
		$query='select * from ibo2013_extra_costs where country_id='.$this->country_id.' order by date asc, title asc';		
		$this->sql->start_transaction(); 	
		$res=$this->sql->simpleQuery($query);
		$this->sql->end_transaction(); 
		return $res;		
	}
	
	public function makeCHF($x, $cur='CHF '){
		$r=$cur;
		if(abs($x)<10000) $r.='&nbsp;';
		if(abs($x)<1000) $r.='&nbsp;';
		if(abs($x)<100) $r.='&nbsp;';
		if(abs($x)<10) $r.='&nbsp;';
		if($x>=0) $r.='&nbsp;';
		return $r.$x;
	}
	
	public function getTotalAmountDue($id=0){
		$query='select c.en, c.id as country_id, c.invoice_finalized, ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0) as total_fees, ifnull(p.amount,0) as amount_payed, ifnull(tr.fee,0)+ifnull(aj.fee,0)+ifnull(sr.fee,0)+ifnull(e.amount,0)-ifnull(p.amount,0) as amount_due from ibo2013_countries c left join (select sum(fee) as fee, country_id from ibo2013_team_registration group by country_id) tr on tr.country_id=c.id left join (select sum(fee) as fee, country_id from ibo2013_observer_registration group by country_id) aj on c.id=aj.country_id left join (select sum(f.fee) as fee, country_id from ibo2013_participants p, ibo2013_fees f where f.type="single_room" and p.single_room=1 group by country_id) sr on c.id=sr.country_id left join (select sum(amount) as amount, country_id from ibo2013_payment group by country_id) p on c.id=p.country_id left join (select sum(amount) as amount, country_id from ibo2013_extra_costs group by country_id) e on c.id=e.country_id';
		if($id>0) $query.=' where c.id='.$id;
		$query.=' order by c.en asc';
		$this->sql->start_transaction(); $res=$this->sql->simpleQuery($query); $this->sql->end_transaction();
		return $res;
	}
}
