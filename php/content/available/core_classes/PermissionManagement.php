<?php
/*
 *      permissionmanagement.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
 *      
 */


//Objective: CODE A SIMPLE PERMISSION-MODIFICATION INTERFACE!

class PermissionManagement extends TabContent
{
	
	protected $mConfKeys = array('permission_table');
	protected $mConfValues = array('pagegroup_permission');

	protected $meTabs = array(); //array('by_group'=>'by group','by_user'=>'by user');
	
	//NOTE: index p is outdated, was used to indicate wether permissions are used.
	//which tables can be altered and how... + labels for tabs
	protected $meeTables = array(/*'user_in_group'=>
									array('p'=>FALSE,'tabs'=>array('user'=>'by user','usergroup'=>'by group')),*/
			'pagegroup_permission'=>
				array(
					'p'=>TRUE,
					'tabs'=>array(
						'pagegroup'=>'by pagegroup',
						'usergroup'=>'by usergroup')),
			'content_class_permission'=>
				array(
					'p'=>TRUE,
					'tabs'=>array(
						'content_class'=>'by class',
						'usergroup'=>'by usergroup')),
			'box_permission'=>
				array(
					'p'=>TRUE,
					'tabs'=>array(
						'box'=>'by box',
						'usergroup'=>'by usergroup')),
			'domain_permission'=>
				array(
					'p'=>TRUE,
					'tabs'=>array(
						'domain'=>'by domain',
						'usergroup'=>'by usergroup'))
	);

	protected $meeCurrent = array();
	
	//@override
	public function display()
	{
		//print_r($this->mConfValues);
		$table = $this->mConfValues['permission_table'];
		
		$this->meeCurrent = $this->meeTables[$table];
		$cur = array_values($this->meeCurrent['tabs']);
		
		$this->meTabs = array('tab0'=>$cur[0],'tab1'=>$cur[1]);
		
		return parent::display();
	}
	
	protected function tab0()
	{
		$rows = array_keys($this->meeCurrent['tabs']);
		$key = $rows[0];
		$value = $rows[1];
		
		return $this->display_modify_permission($key,$value);
	}
	
	protected function tab1()
	{
		$rows = array_keys($this->meeCurrent['tabs']);
		$key = $rows[1];
		$value = $rows[0];
		
		return $this->display_modify_permission($key,$value);
	}
	
	protected function display_modify_permission($key,$value)
	{
		$xhtml = '';
		
		$vector = array('table'=>$key,'victim'=>$value);
		
		$form =  $this->getForm('select_target_form',$vector);
		
		$xhtml .= $form->getHtml($this->id);
		
		
		//If group or target was selected, display the permission modification form
		if($form->getElementValue('select')/* && $form->isValid()*/)
		{
			
			
			$key = $form->getVectorValue('table');
			$val = $form->getElementValue('select');
			$vict = $form->getVectorValue('victim');
			
			$vector_perm = array('key'=>$key,'val'=>$val,'victim'=>$vict);
			
			$xhtml .= 'You selected '.$val;
			$form = $this->getForm('permission_form',$vector_perm);
			$xhtml .= $form->getHtml($this->id);
		}
		
		return $xhtml;
	}
	
	
	
	
	
	
	
	
	protected function process_select_target_form()
	{
		$form = $this->getForm('select_target_form');
		$form->validate();
		//print_r($form->getVector());
		//print_r($form->getElementValue('select'));
	}
	
	
	protected function select_target_form($vector)
	{
		$form1 = new SimpleForm(__METHOD__);
		$form1->setVector($vector);
		
		$form1->stayAlive();
		
		$table = $vector['table'];
		
		//need to do some special things, because user_in_group has no permission
		/*if($this->mConfValues['permission_table'] == 'user_in_group' && $table == 'user')
			$query = "SELECT id, CONCAT(first_name,' ',last_name) as name FROM $table WHERE 1";
		else*/
			$query = "SELECT id, name FROM $table WHERE 1";
		
		$query_args = array('query'=>$query,'keys'=>array('id'),'values'=>array('name'));
		$data_sel = new DataSelect('Select '.$table,'select',$query_args);
		$form1->addElement('select',$data_sel);
		$form1->addElement('submit',new SimpleSubmit());
		
		return $form1;
		
	}
	
	
	//elements who have no more permissions will dissappear from the form.
	public function process_permission_form()
	{
		$form = $this->getForm('permission_form');
		if(!$form->validate())
			return FALSE;
		
		$key = $form->getVectorValue('key');
		$val = $form->getVectorValue('val');
		$vict = $form->getVectorValue('victim');

		$key_id = $key.'_id';
		$vict_id = $vict.'_id';
		
		
				switch($form->getButtonPressed())
		{
			case 'submit':
				$changed = $form->getChangedElementValues();
				
				unset($changed['add_select']);
				
				if(empty($changed)) //nothing to change
					return TRUE;
					
				$sql = SqlQuery::getInstance();
				
				foreach($changed as $el=>$new_value)
				{
					list($target_id,$target_name,$perm) = explode('#',$el);
					
					if($new_value == '1')
					{
						$insert = array($key_id=>$val,$vict_id=>$target_id,'permission'=>$perm);
						//$query = "INSERT INTO {$this->mConfValues['permission_table']} ($key_id,$vict_id,permission) VALUES ($val,$target_id,$perm)";
						$sql->insertQuery($this->mConfValues['permission_table'],$insert);
					}
					else
					{
						$delete = array($vict_id=>$target_id,$key_id=>$val,'permission'=>$perm);
						$sql->deleteQuery($this->mConfValues['permission_table'],$delete);
						//$query = "DELETE FROM {$this->mConfValues['permission_table']} WHERE $vict_id=$target_id AND $key_id=$val AND permission=$perm";
					}	
						
				}
				
				break;
				
			//to add an element in the form, we need to give it some sort of permission...
			case 'add_submit':
				$target_id =  $form->getElementValue('add_select');
				
				if($target_id <= 0)
					return FALSE;
					
				$perm = '1'; //add read-permission by default
				
				$sql = SqlQuery::getInstance();
				
				$insert = array($key_id=>$val,$vict_id=>$target_id,'permission'=>$perm);
				$sql->insertQuery($this->mConfValues['permission_table'],$insert);
					
				
				break;
			default:
				echo 'unknown button pressed!';
				//throw new Exception('unknown button pressed!');
		}

		$this->remakeForm('permission_form');		
		
		
	}
	
	
	
	
	
	public function permission_form($vector)
	{
		$form = new AccurateForm(__METHOD__);
		$form->setVector($vector);
		
		
		$val = $vector['val'];
		$key = $vector['key'];
		$key_id = $key.'_id';
		$vict = $vector['victim'];
		$vict_id = $vict.'_id';
		
				
		/*if($this->mConfValues['permission_table'] == 'user_in_group')
			{
				if($key == 'user')
					$query= "SELECT 
						v.id,v.name
					FROM ".$this->mConfValues['permission_table']." p
					JOIN $vict v ON v.id = p.$vict_id WHERE $key_id=$val";
				else if($key == 'usergroup')
					$query = "SELECT 
						v.id, CONCAT(v.first_name,' ',v.last_name) as name
					FROM ".$this->mConfValues['permission_table']." p
					JOIN $vict v ON v.id = p.$vict_id WHERE $key_id=$val";
			}
			else*/
				$query = "SELECT 
					v.id, v.name,
					SUM(DISTINCT permission) as permission 
				FROM ".$this->mConfValues['permission_table']." p
				JOIN $vict v ON v.id = p.$vict_id WHERE $key_id=$val GROUP BY $vict_id";
		
		
		$res = SqlQuery::getInstance()->simpleQuery($query);

				$form->setGridSize(sizeof($res)+5,5);


			$row = 0;
			
		if(empty($res))
		{
			$form->putElement('ht',$row++,0,new HtmlElement('no rows returned'));
		}
		else
		{


			
			$prs = array($vict,'read','write','admin');
			foreach($prs as $i=>$pe)
				$form->putElement('per'.$i,$row,$i,new HtmlElement('<h2>'.$pe.'</h2>'));
			$row++;
			
			foreach($res as $d=>$group)
			{
				$form->putElement('lbl'.$d,$row,0,new HtmlElement('<b>'.$group['name'].'</b>'));
				
				for($e=0;pow(2,$e)<=MAX_PERMISSION;$e++)
				{
					$ind = pow(2,$e);
					$can = $group['permission'] & $ind;
					$el_name = $group['id'].'#'.$group['name'].'#'.$ind;
					$form->putElement($el_name,$row,$e+1,new Checkbox('',$el_name,TRUE,$can));	
				}
				
				$row++;

			}

			$form->putElement('submit',$row++,0,new Submit('action','submit'));
			
		}	
		
		//Second part of form: to add groups or individuals not yet in the list
		
		$query2 = "SELECT 
					v.id, v.name
				FROM $vict v
				LEFT JOIN ".$this->mConfValues['permission_table']." p ON v.id = p.$vict_id AND p.$key_id=$val 
				WHERE p.$key_id IS NULL";
		
		//echo $query2;
		
		$q_args = array('query'=>$query2,'keys'=>array('id'),'values'=>array('name'));
		
		$add_select = new DataSelect('Add '.$vict,'add_select',$q_args);
		$form->putElement('htmlxqe',$row++,0,new HtmlElement('<b>Add '.$vict.'</b>'));
		$form->putElement('add_select',$row,0,$add_select);
		$form->putElement('add_submit',$row++,1,new Submit('action','add'));

		
		//notice($form->getElementValues());
		return $form;
	}
	

}


?>

