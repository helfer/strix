<?php

class TranslationManager extends Content{
	
	protected $selected_language_id = NULL;
	
	function display()
	{
		
		$html = '';
		//-------------------------------------------------------------
		//form to chose the language to translate to
		$html .= $this->getForm('select_language_form')->getHtml($this->id);
		
		if ( isset($this->selected_language_id))
		{
			$lid = $this->selected_language_id;
			
			$html .= 'language_id = '.$lid.'<br />';
			
			$sql = SqlQuery::getInstance();
			
			$query = "
			SELECT DISTINCT 
				pf.uri,
				pf.abb,
				c.id, 
				cf.version, 
				cf.text,
				cf2.version as v2,
				SUM(DISTINCT pgp.permission) as permission
			FROM 
				`content` c 
			JOIN `content_fragment` cf ON 
				c.id = cf.content_id  
			JOIN `page` p ON
				p.id = c.page_id
			JOIN pagegroup_permission pgp ON
				pgp.pagegroup_id = p.pagegroup_id
			JOIN user_in_group uig ON
				uig.usergroup_id = pgp.usergroup_id
			JOIN `page_fragment` pf ON
				pf.page_id = p.id AND pf.language_id = cf.language_id
			LEFT JOIN `content_fragment` cf2 ON 
				cf2.version < cf.version AND cf2.content_id = cf.content_id 
			WHERE 
				(
					cf2.version IS NOT NULL OR cf.content_id NOT IN 
						(
							SELECT DISTINCT content_id FROM content_fragment WHERE language_id = $lid
						) 
				) 
				AND cf.language_id <> $lid 
				AND c.content_class_id IN (1,8)
				AND uig.user_id = {$_SESSION['user_id']}
			GROUP BY
				c.id
			ORDER BY 
				c.id, 
				cf.version DESC";
				
			$contents = $sql->simpleQuery($query);
			
			$html .= count($contents).' lines returned<br />';
			
			$tbl = '<table>';
			$even = FALSE;
			foreach($contents as $c)
			{
				$style_class = $even ? 'even':'odd';
				$tbl .= '<tr class="'.$style_class.'">';
				$tbl .= '<td><a href="'.$c['uri']./*'#id'.$c['id'].*/'?lan='.$lid.'&amp;vmode=2" target="_blank">'.$c['abb'].'</a></td>';
				if(isset($c['v2']))
					$tbl .= '<td>update!</td>';
				else
					$tbl .= '<td>translate!</td>';
					
				$tbl .= '<td title="'.htmlentities($c['text'],ENT_QUOTES,'UTF-8').'">'.htmlentities(substr($c['text'],0,40),ENT_QUOTES,'UTF-8').'...</td>';
				
				$tbl .= '</tr>';
				$even = !$even;
			}
			
			$tbl .= '</table>';
			
			$html .= $tbl;

		}
		
	
		return $html;
	}
	
	
	public function select_language_form($vector)
	{
		//SELECT Language FORM ------------------
		
		$form = new SimpleForm(__METHOD__);
		$form->setVector($vector);

		$query="SELECT id,name FROM language WHERE active=1";
		
		$q_args = array('query'  => $query,
						'keys'  => array('id'),
						'values'=> array('name')
						);
		$arg_ary = array('label'=>'language:','name'=>'language_id','query_args'=>$q_args);
		$form->addElement('language_id',new DataSelect($arg_ary));
		
		$form->addElement('submit',new Submit('submit','Process'));
		
		return $form;
		// END OF SELECT EXAM FORM -------------------------	
	}
	
	protected function process_select_language_form()
	{
		$form = $this->getForm('select_language_form');
		if(!$form->validate())
			return FALSE;
			
		$eid = $form->getElementValue('language_id');
		
		$this->selected_language_id = $eid;
		
		return;	
	}
	
}
?>
