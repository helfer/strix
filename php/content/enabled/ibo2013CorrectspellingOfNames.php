<?php
class ibo2013CorrectspellingOfNames extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	public function getFromDB($country_id){
		$sql = SqlQuery::getInstance();
		$sql->start_transaction();

		$query='select p.*, dc.* from ibo2013_participants p, ibo2013_delegation_categories dc where dc.id=p.delegation_category_id and dc.class="Volunteer" order by dc.order_by, last_name asc';
		//$query='select * from ibo2013_participants where photo_basename!="" order by delegation_category_id asc, last_name asc';
		//echo $query."\n";
		$res = $sql->execute($query);		
		$ok = $sql->end_transaction();

		$res = $sql->mysql2array($res);
		return $res;
	}
	
	
	public function display()
	{
		$xhtml=$this->process_msg;
		
		$xhtml .= '<p class="subtitle">Correct Spelling of Names</p>';
		$form=$this->getForm('runSpellingCorrection_form');
		$xhtml .= $form->getHtml($this->id);						


		return $xhtml;
	}

	public function checkCharacters($c){
		//remove hypen and spaces
		$c=str_replace('-', '', $c);
		$c=str_replace(' ', '', $c);
		if(ctype_lower($c) || ctype_upper($c)) return true;
		else return false;
	}

	public function makeNewName($n){
		$n=trim($n);
		if($this->checkCharacters($n)){
			$n=strtolower($n);
			//take care of hyphen
			$n=explode('-', $n);
			foreach($n as $i=>$x){				
				$n[$i]{0}=strtoupper($n[$i]{0});
			}
			$n=implode('-', $n);
			//take care of spaces
			$n=explode(' ', $n);
			foreach($n as $i=>$x){				
				$n[$i]{0}=strtoupper($n[$i]{0});
			}
			$n=implode(' ', $n);
		}
		return($n);
	}
	
	public function process_runSpellingCorrection_form($vector){
		//find all names with only capitals / lower letter
		$sql = SqlQuery::getInstance();
		$query='select id, first_name, last_name from ibo2013_participants order by last_name asc, first_name asc';
		$sql->start_transaction(); $res = $sql->simpleQuery($query); $ok = $sql->end_transaction();
		$out=array(); $counter=0;
		foreach($res as $r){
			$tf=$this->makeNewName($r['first_name']);
			$tl=$this->makeNewName($r['last_name']);
			if($tf!=$r['first_name'] || $tl!=$r['last_name']){
				$out[]='"'.$r['first_name'].'" "'.$r['last_name'].'" ---> "'.$tf.'" "'.$tl.'"';				
				$query='update ibo2013_participants set first_name="'.$tf.'", last_name="'.$tl.'" where id='.$r['id'];
				$sql->start_transaction(); $sql->simpleQuery($query); $ok = $sql->end_transaction();
				if(!$ok){
					 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
					 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013CorrectspellingOfNames', $query);
				}
			}
		}
		
		if(count($out)>0){
			$this->process_msg = '<p class="success">'.count($out).' Names have been changed! The changes are listed below:</p><p class="text">'.implode('<br/>', $out).'</p>';
		} else $this->process_msg = '<p class="success">All Names are already spelled OK!</p>';
		
	}

	protected function runSpellingCorrection_form($vector){
		$newForm = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$newForm->setVector($vector);
		$newForm->setProportions(10,400);

		$newForm->addElement('explanations', new HtmlElement('Will Change All Names Containg Only Capitals Or Only Lower Case Letters To This Format (Also When Including Hyphens: Hallo-Marco!)'));
				
		$newForm->addElement('submit_runspellingcorrection', new Submit('submit_runspellingcorrection','run spelling correction'));
		
		return $newForm;
	}


}
?>

