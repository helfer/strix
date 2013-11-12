<?php

/**
 * Displays and modifies a list of todo-items
 */
class JIBUN_TodoList extends TabContent
{


	protected $meTabs = array(
		'all'=>'all',
		'students'=>'students',
		'teachers'=>'teachers',
		'schools'=>'schools',
		'db'=>'db',
		'bug'=>'bug',
		'idea'=>'idea');
	protected $meDefaultTab = 'all';

	protected $meeCatId = array('all'=>0,'students'=>1,'teachers'=>2,'schools'=>3,'db'=>4,'idea'=>5,'bug'=>6);
	protected $meeCurrentCat = 0;

	public function students()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function teachers()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function bug()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function idea()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function db()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function schools()
	{
		return $this->display_category(__METHOD__);
	}
	
	public function all()
	{
		return $this->display_category(__METHOD__);
	}


	/// processes some stupid stuff and then calls parent::display (tabs)
	public function display()
	{
		
		$xhtml = '<div>' . $this->process_msg;
		
		if(isset($_GET['skip']))
		{

			if(SqlQuery::getInstance()->singleRowQuery("SELECT 1 FROM `JIBUN_todo` WHERE `completed` IS NULL AND id='{$_GET['skip']}'")){

				SqlQuery::getInstance()->updateQuery('JIBUN_todo',array('category'=>'6'),array('id'=>$_GET['skip']));
				$xhtml .= '<p class="skipper">YOU BETTER HAVE A GOOD REASON!</p>';
			} 
			else 
			{
				$xhtml .= 'ID not in tasks to complete...';	
			}
		}

		if (isset($_GET['show_completed'])) {
			$xhtml .= '<h2>completed items:</h2>';
			$item = SqlQuery::getInstance()->simpleQuery("SELECT * FROM `JIBUN_todo` WHERE `completed` IS NOT NULL ORDER BY completed ASC");
			
			$xhtml .= $this->display_items($item,TRUE);
		} 
		else 
		{
			//displays categories
			$xhtml .= parent::display();
					
			$xhtml .= $this->getForm('add_item_form')->getHtml($this->id);
		}	
		
		$xhtml .= '</div>';
		
		return $xhtml;
	}
	


	public function task_completed_form($vector)
	{
		
		
		$newForm = new SimpleForm(__METHOD__);
		
		$item_id = $vector;
		
		$newForm->addElement('id',new Hidden('id',$item_id));
		$minutes = new TextInput('minutes ','minutes','',array('style'=>'width:2em;'));
		$minutes->addRestriction(new StrlenRestriction(1,5));
		$minutes->addRestriction(new IsNumericRestriction());
		$newForm->addElement('minutes',$minutes);
		$newForm->addElement('submit',new Submit(array('name'=>'submit','value'=>'finished')));
		
		return $newForm;
	}

	public function process_task_completed_form()
	{
		$form = $this->getActivatedForm();
		
		if($form->validate())
		{
			$id = $form->getElementValue('id');
			$minutes_real = $form->getElementValue('minutes');
			
			if(SqlQuery::getInstance()->singleRowQuery("SELECT 1 FROM `JIBUN_todo` WHERE `completed` IS NULL AND id='$id'"))
			{
				SqlQuery::getInstance()->updateQuery('JIBUN_todo',array('completed'=>'NOW()','minutes_real'=>$minutes_real),array('id'=>$id));
				$this->process_msg = '<p class="congratulations">やった</p>';
			} 
			else 
			{
				$this->process_msg = 'ID not in tasks to complete...';	
			}
		}	
	}



	public function process_add_item_form()
	{
		
		$form = $this->getActivatedForm();
		
		if($form->validate())
		{
			
			print_r($form->getSomeElementValues(array('task','category')));
			echo '...';
			print_r($form->getElementValues());
			SqlQuery::getInstance()->insertQuery('JIBUN_todo',$form->getSomeElementValues(array('task','category','importance','minutes')));
		}
		
		$form->reset();
		$this->process_msg = '<p class="success">New item inserted</p>';
	}
	
	public function add_item_form($vector)
	{
		$newForm = new SimpleForm(__METHOD__);
		$newForm->setVector($vector);
		
		//print_r($newForm);

		
		$fname = new Input('task ','text','task','',array('size'=>20));
		$fname->addRestriction(new StrlenRestriction(1,256));
		$newForm->addElement('task',$fname);
		$minutes = new TextInput('minutes ','minutes','5',array('style'=>'width:1.6em;text-align:right;'));
		$minutes->addRestriction(new StrlenRestriction(1,5));
		$minutes->addRestriction(new IsNumericRestriction());
		$newForm->addElement('minutes',$minutes);
		
		$query = "SELECT id, name FROM `JIBUN_todo_category` WHERE 1";
		$query_args = array('query'=>$query,'keys'=>array('id'),'values'=>array('name'));
		$data_sel = new DataSelect('cat ','category',$query_args,$this->meeCurrentCat,array('style'=>'width:6em;'));
		$newForm->addElement('category',$data_sel);
		
		$query = "SELECT id, name FROM `JIBUN_todo_importance` WHERE 1";
		$query_args = array('query'=>$query,'keys'=>array('id'),'values'=>array('name'));
		$lvl_sel = new DataSelect('lvl ','importance',$query_args,$this->meeCurrentCat,array('style'=>'width:10em;'));
		$newForm->addElement('imporance',$lvl_sel);
		
		$newForm->addElement('submit',new SimpleSubmit());
		
		return $newForm;
	}

	/** displays all tasks for one category (all categories if $cat = all) */
	public function display_category($cat)
	{
		$xhtml = '';
		
		$cat = explode('::',$cat);
		$cat = $cat[1];
		
		$cat_id = $this->meeCatId[$cat];
		$this->meeCurrentCat = $cat_id;
		
		$categories = $cat_id != 0 ? "AND category = '$cat_id'" : "AND category <> 6";
		
		$item = SqlQuery::getInstance()->simpleQuery("
			SELECT * 
			FROM 
				`JIBUN_todo` 
			WHERE 
				`completed` IS NULL 
				$categories 
			ORDER BY 
				id ASC
				");


		//$xhtml .= $this->getForm('select_category_form')->getHtml($this->id);

		$xhtml .= "showing category '$cat'";

		if(count($item) == 0)
		{
			$xhtml .= "<p>Fantastic, you have nothing left on your todo-list! <br /><b>What? Nothing to do?</b></p>";
		}
		else
		{
			$xhtml .= '<br />there are '. count($item). ' items<br /><br /><br />';
			$xhtml .= '<p style="font-size:15px;color:lime;font-weight:bold;"><b>Hallo Linus!</b></p>';
			$xhtml .= $this->display_items($item);
		}
		
		return $xhtml;
	}


	public function display_items($item,$done = FALSE){
		
		$xhtml = '<div class="todo_item" style="min-height:10em">';
					foreach($item as $it)
					{
						$xhtml .= '
						<div>
						'.$it['importance'].' ('.$it['minutes'].'min) '.$it['task'].'&nbsp;&nbsp;'.date('d.m H:i',strtotime($it['added'])).'&nbsp;&nbsp;
						<br />';
						
					if(!$done)
						//have to do it this way to get multiple instances...
						$xhtml .= $this->task_completed_form($it['id'])->getHtml($this->id);
						$xhtml .= '&nbsp;<a class="skip" href="?skip='.$it['id'].'">skip</a>';
						
					$xhtml.= '
					</div>
					';
						
					}
						
		$xhtml .= '</div>';
		
		return $xhtml;
	}


	/*
	public function select_category_form($vector)
	{
		$newForm = new SimpleForm(__METHOD__,$action = '', $extras = array(), $target_id = $this->id,$target_type = 'content',$method = 'get');
		$newForm->setVector($vector);

		$query = "SELECT id, name FROM `JIBUN_todo_category` WHERE 1";
		$query_args = array('query'=>$query,'keys'=>array('id'),'values'=>array('name'));
		$data_sel = new DataSelect('cat ','category',$query_args,$this->meeCurrentCat);
		$newForm->addElement('category',$data_sel);
		$newForm->addElement('submit',new SimpleSubmit());
		
		return $newForm;
	}
	
	public function process_select_category_form()
	{
	//VOID (it's a $_GET form)
	}
	*/
}

?>
