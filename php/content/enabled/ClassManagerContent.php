<?php
/*
 *      classmanagercontent.php
 *      
 *      Copyright 2009 user007 <user007@D1612ak>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 */


Class ClassManagerContent extends Content
{
	
	
	/**@todo 
	 * 1. Scan the AVAILABLE directory for all files in DB table
	 * 2. Warn the user about those that are no longer there
	 * 3. Scan the ENABLED folder and check if all enabled classes are linked
	 * 4. Link those which are enabled but not linked
	 * 5. See if there are any new files in the directories under AVAILABLE
	 * 6. Propose to include those.
	 */
	
	/**@todo
	 * Provide a way to disable content without discarding it from the DB
	 * by making sure that every content object that gets loaded from DB is
	 * checks on the "enabled"-state of its class. If its not enabled, the
	 * content should not be displayed at all or a dummy content should replace it.
	 */
	
	/** @todo
	 * When a content type is deleted (deleted, not disabled), the user should 
	 * be asked to confirm. If he confirms, all content of that type will be deleted
	 * along with the content class itself. 
	 * 
	 */


	public function display()
	{
		$xhtml = '';
		
		$xhtml .= $this->getForm('include_form',NULL)->getHtml($this->id);
		
		$query = "SELECT 
					cc.* ,
					COUNT(c.id) as instances, 
					MAX(ccp.permission) as public 
				FROM `content_class` cc 
				LEFT JOIN content c ON c.content_class_id = cc.id 
				LEFT JOIN content_class_permission ccp ON ccp.content_class_id = cc.id 
					AND ccp.usergroup_id = 1 
				GROUP BY cc.id";
		
		$tbl = new AbTable('classes',$query,array('id'));
		$y = $tbl->getSelected();
		if(!empty($y))
		{
			//TODO: this is slow and stupid!
			$cc_name = SqlQuery::getInstance()->singleValueQuery("SELECT description FROM `content_class` WHERE id=$y");
			
			$form = $this->getForm('permission_form',array($y));
			$xhtml .= '<a href=".">&lt;= back to content table</a>';
			$xhtml .= '<h2>Permissions for &quot;'.$cc_name.'&quot;</h2>';
			$xhtml .= $form->getHtml($this->id);

			return $xhtml;
			
		}
		
		return $xhtml.'<br /><hr></hr>'.$tbl->getHtml();
		
	}	
	
	
	public function permission_form($vector)
	{
		$cc_id = $vector[0];
		$form = new AccurateForm(__METHOD__);
		$form->setVector($vector);
		
		
				
		$query = "SELECT 
					u.*,
					SUM(DISTINCT permission) as permission
				FROM `usergroup` u  
				LEFT JOIN `content_class_permission` ccp 
					ON ccp.`usergroup_id` = u.id 
					AND ccp.`content_class_id` = $cc_id
				GROUP BY u.id";
				
		$res = SqlQuery::getInstance()->simpleQuery($query);
		
		$form->setGridSize(count($res)+5,5);

		$row = 0;
		
		$prs = array('group','read','write','admin');
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

		$form->putElement('submit',$row,0,new Submit('action','submit'));

		
		//notice($form->getElementValues());
		return $form;
	}
	
	
	//TODO: this is still buggy somehow.
	
	public function process_permission_form()
	{
		$xhtml = '';
		
		$form = $this->getActivatedForm(); // is permission_form!!!
		
		$values = $form->getChangedElementValues();
		//print_r($values);
		//print_r($form->getElementValues());
		
		//content class id is first index of vector
		$vect = $form->getVector();
		$cc_id = $vect[0];
		
		foreach($values as $c=>$val)
		{
			list($ug_id,$grp_name,$permission) = explode('#',$c);
			
			$ivs = array('usergroup_id'=>$ug_id,'content_class_id'=>$cc_id,'permission'=>$permission);
			
			if($val == TRUE)
			{
				$ok = SqlQuery::getInstance()->insertQuery('content_class_permission',$ivs);
				$xhtml .= 'added permission '.$permission.' for '.$grp_name.'<br />';
			}
			else
			{
				$ok = SqlQuery::getInstance()->deleteQuery('content_class_permission',$ivs);
				$xhtml .= 'removed permission '.$permission.' for group '.$grp_name.'<br />';
			}	
		}
		
		if(!empty($values))
			$this->remakeForm('permission_form',$vect);
		
		return $xhtml;
	}
	
	
	public function rec_dir_listing($dir,&$return_list)
	{
		
		$file_list = array_diff( scandir($dir), array('.','..') );

		
		foreach($file_list as $k=>$fi)
		{
			if( is_dir( $dir . $fi ) )
				$this->rec_dir_listing( $dir . $fi, $return_list );
			else
			{
				if( isset($return_list[$fi]) )
					throw new Exception('Filename collision: ' . $fi);
				
				$return_list [$fi]= $dir . '/' . $fi;	
			}
				
			//$file_list = array_merge($file_list, scandir(ADDON_CONTENT_CLASS_DIR));	
		}	
		
		return $return_list;
	}
	
	
	public function include_form($vector = NULL)
	{	
		
		//scan for files recursively...
		$file_list = array();
		$this->rec_dir_listing(AVAILABLE_CONTENT_DIR,$file_list);
		
		//print_r($file_list);	
		
		$form = new TabularForm(__METHOD__);
		$form->setProportions('8em','20em'); //TODO: config this...
		$sql = SqlQuery::getInstance();
		
		$included = $sql->listQuery('content_class','id','object_type');
		$included = array_map(create_function('$a','return $a . ".php";'),$included);
		$included = array_flip($included);
		
		//print_r($included);
			
			
		$new_types = array_diff_key($file_list, $included);
	
		//print_r($new_types);
		
		$form->addElement('infoNumber',new HtmlElement(count($new_types) . ' files to include') );
		
		if(empty($new_types))
			return $form;
		
		// note: only one new content file gets displayed at a time.
		$newfile = current($new_types);
		$newname = key($new_types);
		
		
		//This block is kinda useless... (but fun!!!)
		//--------------------------------
		if(is_file($newfile))
			$filecontent = file_get_contents($newfile);
		else
			throw new Exception('Unable to find file '.$newfile);
			
		preg_match('/class (?<name>\w+) extends (?<superclass>\w+)/i', $filecontent, $matches);
		
		if(!isset($matches['name']))
		{
			$form->addElement('err',
				new HtmlElement('<p class="error">No classname found in document '.$newfile.'!</p>','<h2>Error: </h2>'));
			return $form;	
		}
		
		//TODO: this doesn't work so far (crash because it tries to include the class...
		
		$super = $matches['superclass'];
		if($super != 'Content' && !is_subclass_of($super,'Content'))
		{
			$form->addElement('err',
				new HtmlElement('<p class="error">Not a content class: '.$super.' in file '.$newfile.'</p>','<h2>Error: </h2>'));
			return $form;			
		}
		
		$classname = $matches['name'];
		//------------------------
		
		$form->addElement('lbl1',new HtmlElement('<h2>'.$newfile.'</h2>','<h2>File:</h2>'));
			$name_element = new TextInput('Name: ','name',$newname);
			$name_element->addRestriction(new NotEmptyRestriction());
		$form->addElement('name',$name_element);
		$form->addElement('object_type',new TextInput('Object Type: ','object_type',$classname,array('readonly')));
			$desc_element = new Textinput('Description: ','description','');
			$desc_element->addRestriction(new NotEmptyRestriction());
		$form->addElement('description',$desc_element);
		$form->addElement('submit',new Submit('action','submit'));
		
		//TODO: this is vector cheating!!! BAD!
		$form->setVector(array($newfile));
		//print_r($form->getVector());
		
		return $form;
		
	}
	
	
	public function process_include_form()
	{
		$form = $this->getForm('include_form');
		
		if(!$form->validate())
			return 'invalid!';
			
		$keys = array('name','object_type','description','filename');
		$values = $form->getSomeElementValues($keys);
		$vect = $form->getVector();
		
		notice('Vals');
		notice($values); //for next time
		notice('Vect');
		notice($vect);
		$values['file'] = $vect[0];

		

		//This is to verify that the content is ok:
		$filename = $values['file'];
		
		if(is_file($filename))
			include($filename); //to parse the php a first time.
		else
			throw new Exception('Unable to find file '.$newfile);
		
		if ( symlink($filename, ENABLED_CONTENT_DIR . $values['object_type'] . '.php') ) // .php as defined in __autoload
		{
			
			SqlQuery::getInstance()->insertQuery('content_class',$values);	
			
			//to make the newly included disappear
			$this->remakeForm('include_form',NULL);
			
			return 'inserted class '.$values['name'];
		}
		else
		{
			return 'insert not successful is ENABLED directory writeable?';	
		}
	}
	
	
	
}

?>
