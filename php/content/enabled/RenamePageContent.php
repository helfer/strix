<?php
/*
 *      navicontent.php
 *      
 *      Copyright 2009 user007 <user007@UPS1746>
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

class RenamePageContent extends Content{


	public function display()
	{
		if(isset($_GET['vmode']) && $_GET['vmode'] == VMODE_MODIFY)
		{
			$xhtml = '<p><b>folder_name</b> steht für den Namen, der in der Adressleiste des Browsers zu sehen ist. Bei "Erste Runde" steht zum Beispiel "Erste_Runde". In der Adresszeile des Browsers steht dann http://www.ibosuisse.ch/SBO/<b>Erste_Runde</b>/, wenn man auf der Seite ist. <b>Bitte in diesem Feld nur a-z, A-Z, Zahlen oder _ (underscore) verwenden, da es sonst sein kann, dass die Seite nicht erreichbar ist!</b></p>
			<p><b>abb</b> steht für die Abkürzung, die im Menü der Seite (Navigation) zu finden ist, zB SBO für Schweizer Biologie Olympiade. Bitte hier keine zu langen Namen brauchen, da sonst das Menu komisch aussieht!</p>
			<p><b>title</b> ist der Titel, der ganz oben im Browser erscheinen wird, zum Beispiel "Schweizer Biologie Olympiade", wenn man auf SBO ist. Dieser Titel ist auch in der Taskbar zu sehen, wenn das Fenster minimiert ist.</p>
			<p>Falls ihr nicht sicher seid, probiert einfach mal und schaut euch dann das Resultat an. Falls ihr bei folder_name etwas komisches eingegeben habt, ist die Seite womöglich nicht mehr abrufbar. Ihr könnt dann auf eine andere Sprache wechseln, um zur Seite zurück zu kommen und die Eingabe wieder zu ändern. Aus diesem Grund solltet ihr <b>immer nur eine Sprache aufs Mal bearbeiten!</b></p>';
			$xhtml .= $this->getForm('edit_page_form',array($GLOBALS['page']->id))->getHtml($this->id);
		
			return $xhtml;
		}
		else
		{
			//don't display anything
		}
	}
	
	//the id of the page to edit is given as $vector[0]
	protected function edit_page_form($vector)
	{
		if(!isset($vector[0]))
			throw new FormException('EMPTY VECTOR');

		
		$page = $GLOBALS['page_index'][$vector[0]];
		if(empty($page))
			throw new FormException('corrupt vector. No page with id '.$vector[0]);
		
		$form = new AccurateForm(__METHOD__,'',array(),$this->id,'edit');
		$form->setVector($vector);
		$form->setGridSize(12,5);
		
		
		$row = 0;		
			//LANGUAGE DEPENDENT STUFF:
			$fragments = $page->loadAdditionalFragments(); //loads title, name, etc for all other fragments.
			
			//take out uri, we don't want to display that.
			foreach($fragments as $id=>$vals)
				unset($fragments[$id]['uri']);

			// FRAGMENTS (i.e. title, folder, abb)

			//LABELS ..................
			$col = 1;
			$var_order = array();
			foreach(array_keys(reset($fragments)) as $k)
			{
				$form->putElement('lbl_var_'.$k,$row,$col,new HtmlElement('<b>'.$k.'</b>'));
				$var_order []= $k;
				$col++;
			}
			
			
			// FIELDS ..................
		$row++;
		
			foreach($fragments as $lang_id=>$vars)
			{
				$form->putElement('lbl_lang_'.$lang_id,$row,0,new HtmlElement('<b>'.Language::id2long($lang_id).'</b>'));
				$col = 1;
				foreach($var_order as $k) //using var order to guarantee same order as labels!
				{	
					$v = $vars[$k];
					
					$input = new TextInput('',$k.'#'.$lang_id,$v,array()) ;
					
					//because root page can have (and will usually have) empty folder name
					if(isset($page->parent) || $k != 'folder_name')
						$input->addRestriction(new NotEmptyRestriction());
					
					$form->putElement($k.'#'.$lang_id,$row,$col,$input);
					$col++;
				}
			$row++;
			}
		
		$languages_ni = array_diff_key($GLOBALS['config']['languages_enabled'],$fragments);
		$languages_ni = array_intersect_key($GLOBALS['config']['languages_long'],$languages_ni);
		
		if(!empty($languages_ni))
		{
			$form->putElement('lbl_langmissing',$row,0,new HtmlElement('add language'));
			$form->putElement('lang_missing',$row,1,new Select('','lang_missing',$languages_ni));
			$form->putElement('add_lang',$row,2,new Submit('action','add_lan','add'));
			$row++;
		}
		
		$form->putElement('submit_final',$row,1,new Submit('action','submit','submit form'));
		
		return $form;
	}
	
	
	protected function process_page_edit($action = NULL)
	{
		$form = $this->getForm('edit_page_form');
		$input = $form->getElementValues();
		$page = $GLOBALS['page_index'][reset($form->getVector())];
	
	//add language
		if('add_lang' == $action)
		{
			$lang_id = $input['lang_missing'];
			$page->addAdditionalFragment($lang_id,array_flip($page->fragment_vars));
			$this->remakeForm('edit_page_form',NULL);
			return $this->getForm('edit_page_form')->getHtml($this->id);
		}
		
	//update FRAGMENTS
		$fragments = $page->loadAdditionalFragments();
		foreach($fragments as $k=>$v)
			unset($fragments[$k]['uri']);
		
		foreach($fragments as $l_id=>$vals)
		{
			$new_vals = array();
			foreach($vals as $k=>$v)
				$new_vals[$k] = $input[$k.'#'.$l_id];	
			
			$page->changeAdditionalFragment($l_id,$new_vals);
		}	
		$page->storeAdditionalFragments();
			
	//update URI
		if(isset($page->parent))
			$page->parent->update_uri(NULL); //passing null results in update of kids uri only!
		else
			$page->update_uri(array()); //TODO: just enough for all languages :D
			
		$page->store();
		return FALSE; //meaning we're done editing.
	}	
	
	
}
?>
