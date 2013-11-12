<?php
/*
 *      groupmembershipcontent.php
 */
class GroupMembershipContent extends TabContent
{

//DISPLAY BY USER OR BY GROUP!

	protected $meTabs = array('by_group'=>'by group','by_user'=>'by user');
	
	protected $meDbTable = 'user_in_group';
	protected $idColumn = array('by_user'=>'user_id','by_group'=>'usergroup_id');
	protected $meTables = array('by_user'=>'user','by_group'=>'usergroup');
	protected $meColumns = array('by_user'=>array('id','username','first_name','last_name'),
							'by_group'=>array('id','name','description'));
							
							
	//TODO: can be made more generic with little effort
	//NOTE: it's the other way round here!
	protected $meArgs = array('by_group'=>array(
										'name'=>"CONCAT(username,' (',first_name,' ',last_name,')')",
										'name_comp'=>"name",
										'table'=>'user',
										'table_comp'=>'usergroup'),
									'by_user'=>array(
										'name'=>"name",
										'name_comp'=>"CONCAT(username,' (',first_name,' ',last_name,')')",
										'table'=>'usergroup',
										'table_comp'=>'user'),
									);
									



	public function by_group()
	{
		$xhtml = '';
		
		$xhtml .= 'DISPLAY BY GROUP<br />';
		
		return $xhtml . $this->display_table();	
	}

	public function by_user()
	{
		$xhtml = '';
		
		$xhtml .= 'DISPLAY BY USER<br />';
		
		return $xhtml . $this->display_table();	
	}	
	
	
	protected function display_table()
	{
		$xhtml = '';
		
		$sel_tab = $this->get_selected_tab();
		
		$db_table = $this->meTables[$sel_tab];
		$columns = $this->meColumns[$sel_tab];
		$query = 'SELECT `' . implode('`, `',$columns) . '` FROM `' . $db_table . '`';
		//echo $query;
		
		$table = new AbTable('table'.$this->id.'_'.$sel_tab,$query,array('id'),TRUE,'&'.$this->get_selected_tab(TRUE));
		
		$sel = $table->getSelected();
		if($sel !== NULL) //display membership modif form
		{
			$vector['id'] = $sel;
			$vector['sel_tab'] = $sel_tab;
			
			$form = $this->getForm('edit_membership_form',$vector);
			$xhtml .= $form->getHtml($this->id);
		}
		else //display table
		{
			$xhtml .= $table->getHtml();
		}
		
		return $xhtml;
	}
	
	

	
	public function process_edit_membership_form()
	{
		$form = $this->getForm('edit_membership_form');
		if($form->validate())
		{
		
			$vector = $form->getVector();
			$sel_tab = $vector['sel_tab'];
			$id = $vector['id'];
			$sel_members = $form->getElementValue('member');
			$sel_non_members = $form->getElementValue('non_member');
			
			$constant_id_col = $this->idColumn[$sel_tab];
			$variable_id_col = $this->meArgs[$sel_tab]['table'] . '_id';
			
			$sql = SqlQuery::getInstance();
			
			switch($form->getButtonPressed())
			{
				case 'remove':
					$rem_ary = array();
					foreach($sel_members as $usr)
					{
						//print_r(array($constant_id_col=>$id,$variable_id_col=>$usr));
						$sql->deleteQuery($this->meDbTable,	array($constant_id_col=>$id,$variable_id_col=>$usr),1);
					}

					
					break;
				case 'add':
					foreach($sel_non_members as $usr)
					{
						$sql->insertQuery($this->meDbTable,	array($constant_id_col=>$id,$variable_id_col=>$usr));
					}
					
					break;
					
				default:
					throw new Exception('Unexpected button pressed');
			}
			
			//reload to make changes visible instantly
			$this->remakeForm('edit_membership_form',$vector);

		}
		else
			echo 'there was a problem';
			$this->process_msg = '<p class="problem">The form contains errors</p>';
	}
	
	
	//vector = id of Usergroup
	public function edit_membership_form($vector)
	{
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		
		$sel_tab = $vector['sel_tab'];
		$col = $this->idColumn[$sel_tab];
		$id = $vector['id'];
		$table = $this->meDbTable;
		
		
		
		$name = $this->meArgs[$sel_tab]['name'];
		$name_comp = $this->meArgs[$sel_tab]['name_comp'];
		$table = $this->meArgs[$sel_tab]['table'];
		$table_comp = $this->meArgs[$sel_tab]['table_comp'];
		
		$query_template = "SELECT 
							id,$name AS name 
						FROM $table 
						WHERE id %NOT IN 
							(
							SELECT {$table}_id 
							FROM {$this->meDbTable} 
							WHERE {$table_comp}_id = $id
							)";
							
		$target_name = SqlQuery::getInstance()->singleValueQuery("SELECT $name_comp FROM $table_comp WHERE id=$id");

		$query_yes = str_replace('%NOT','',$query_template);
		$query_no = str_replace('%NOT','NOT',$query_template);
		
		$args_yes = array('query'=>$query_yes,'keys'=>array('id'),'values'=>array('name'));
		$args_no = array('query'=>$query_no,'keys'=>array('id'),'values'=>array('name'));
		
		$newForm->addElement('info',new HtmlElement('<b>' . $table_comp . ' ' . $target_name .'</b>'));
		
		$newForm->addElement('member',new DataSelect('member','member',$args_yes,array(),array(),TRUE));
		
		$newForm->addElement('remove',new Submit('remove','remove'));
		
		$newForm->addElement('non_member',new DataSelect('not member','non_member',$args_no,array(),array(),TRUE));
		
		$newForm->addElement('add',new Submit('add','add'));
		
		return $newForm;
		
	}
	


}

?>
