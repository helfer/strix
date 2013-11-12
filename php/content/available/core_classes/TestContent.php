<?php
/*
 *      testcontent.php
 *      
 *     
 */


//forms get initiated automatically!!
class TestContent extends Content
{
	protected $fragment_vars = array();

	public function display()
	{
		$xhtml = '';
	
		include_once('content/utils/forms/dbformwrapper.php');
		include_once('content/utils/forms/dbformelementwrapper.php');
		
		$form_id = 1;
		
		$form = $GLOBALS['FormHandler']->getDbForm( $form_id );
		
		if ( $form->isPopulated() )
		{
			$xhtml .= 'FORM IS POPULATED<br />';	
		}
		
		if ( $form->getChangedElementValues() )
		{
			$xhtml .= 'changed values are: <br />';
			$xhtml .= implode('<br />',$form->getChangedElementValues() );	
		}
		
		$xhtml .= $form->getHtml($this->id);
		
		$dbfwrap = $GLOBALS['FormHandler']->getDbFormWrapper( $form_id );
		
		$xhtml .= array2html( $dbfwrap->getValues() );
	
	
		//print_r($dbf);
	
	
	/*
	 * 
		
		$xhtml = time() . '<br /> ';
		
		if (checkdnsrr('yahoo.com', "MX")) 
			$xhtml .= 'ok';
		else
			$xhtml .= 'no';
			
		return $xhtml . '<br />' . time();
		
	/*
		$xhtml = '';
		$tbl = new AdvancedTable('adt'.$this->id,'SELECT id, name, description FROM usergroup',array('id'));
		
		return $tbl->getHtml();
		
	//----------------------------------*/	
		/*
		//print_r($_POST);
		//print_r($_GET);
		//print_r($_REQUEST);
		$xhtml = '';
		$form1 = $this->getForm('form_test',NULL);
		
		$xhtml .= $form1->getPostValue('input1_1');
		//print_r($form1);
		
		return $xhtml.'ABC'.$form1->getHtml($this->id);
		*/
		
		return $xhtml;
	}
	
	protected function form_test()
	{
		/*$form1 = new TabularForm('form_test');
		$form1->setProportions('12em','36em');
		//$form1 = new SimpleForm('form_test');
		//$form1->setLayout(HORIZONTAL);
		
		$input1 = new Input('Enter a number: ','text','input1','abc',array('maxlength'=>10,'size'=>10));
			$input1->addRestriction(new IsNumericRestriction());
			$input1->addRestriction(new StrlenRestriction(0,10));
			$form1->addElement('input1',$input1);
		//$form1->addElement('br', new HtmlElement('','<br />'));
		
		$textarea1 = new Textarea('Enter some text with exactly 5 characters: ','text','Text value',5,10);
			$restr_strlen2 = new StrlenRestriction(5,5);
			$restr_strlen2->setErrorMessages(
				array(	
					'too_long'=>array('1'=>'muss genau 5 Zeichen sein'),
					'too_short'=>array('4'=>'must be exactly 5 characters')
					)
			);
			$textarea1->addRestriction($restr_strlen2);
			$form1->addElement('text',$textarea1);
			
		$form1->addElement('submit1',new Submit('action','submit'));
		$form1->addElement('submit2',new Submit('action','bing'));
			
		$form1->stayAlive();*/
		
		
		/*
		$form1 = new AccurateForm('form_test');
		$form1->setGridSize(5,4);
		$form1->putElement('h1',0,1,new HtmlElement('<b>string</b>'));
		$form1->putElement('h2',0,2,new HtmlElement('<b>integer</b>'));
		$form1->putElement('h3',0,3,new HtmlElement('<b>boolean</b>'));
		
		$form1->putElement('c1',1,0,new HtmlElement('Deutsch'));
		$form1->putElement('in1.1',1,1,new Input('','text','input1_1','abc'));
		$form1->putElement('in1.2',1,2,new Input('','text','input1_2','abc'));
		$form1->putElement('in1.3',1,3,new Input('','text','input1_3','abc'));
		
		$form1->putElement('c2',2,0,new HtmlElement('Français'));
		$form1->putElement('in2.1',2,1,new Input('','text','input2_1','abc'));
		$form1->putElement('in2.2',2,2,new Input('','text','input2_2','abc'));
		$form1->putElement('in2.3',2,3,new Input('','text','input2_3','abc'));
		
		$form1->putElement('c3',3,0,new HtmlElement('English'));
		$form1->putElement('in3.1',3,1,new Input('','text','input3_1','abc'));
		$form1->putElement('in3.2',3,2,new Input('','text','input3_2','abc'));
		$form1->putElement('in3.3',3,3,new Input('','text','input3_3','abc'));
		
		$form1->putElement('submit',4,1,new Submit('action','submit','GO'));
		
		//$textarea1 = new Textarea('Enter some text with exactly 5 characters: ','text','Text value',5,10);
		//$form1->putElement('text1',4,2,$textarea1);
		*/
		
		
		
			
		return $form1;
	}
}

?>
