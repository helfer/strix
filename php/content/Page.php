<?php

// PAGE OBJECT:
/****************************************************************************************/


class Page extends TreeNode{
	const my_table_name = 'pages';
	protected $object_type = 'Page';
	
///@todo: clean up all this crap!
	protected $boxes = array();
	protected $box_index = array();
	protected $content_index = array();
	protected $content_tree = array();

	
	protected $id = 0; //404 Error Page!
	protected $box_structure_id;
	protected $cacheable = 0;
	protected $cache_update = 0;
	protected $language_id=NULL;
	protected $folder_name='';
	protected $invisible = 0;
	protected $uri = '';
	protected $title = '';
	protected $abb = ''; //abbreviation
	protected $name = '';
	protected $pagegroup_id = -1;
	
	protected $db_vars = array(	'left',
								'right',
								'name',
								'pagegroup_id',
								'box_structure_id',
								'cacheable',
								'cache_update');
							
	protected $fragment_vars = array(	'title',
										'folder_name',
										'abb',
										'uri');
	
	//more like a constant.	
	public function getTableName(){return 'page';}
	
	protected $stylesheet_file;
	
	protected $editContent = '';
	
	///@todo: think about it.
	public function display(){
		$xhtml = '';
		//print_r($_SESSION['language_order_abb']);
		
		if(!empty($this->editContent))
		{
			$_GET['vmode'] = 1; //normal
			$xhtml .= '<div class="editbox" style="border: 1px solid lime;
								background-color: #FCFCFC;
			">'.$this->editContent.'</div>';
		}
		
		if(isset($_GET['vmode']))
		{
			
			
			switch($_GET['vmode']){
				case VMODE_MODIFY:
					$xhtml .= $this->draw_modify();

				break;
				case VMODE_ADMIN:
					$xhtml .= $this->draw_admin();
				
				break;
				default:
					$xhtml .= $this->draw_normal();
			}
			
			
		} 
		else
		{
			$xhtml .= $this->draw_normal();
		}
		
		return $xhtml;
	}
	
	public function draw_normal(){
		notice('Page language = '.Language::id2long($this->language_id));
		$xhtml = '';
		foreach($this->boxes as $box) $xhtml .= $box->draw_normal();
		return $xhtml;
	}
	
	
	public function draw_modify(){
		notice('Page language = '.Language::id2long($this->language_id));
		notice('Your permission on this page is: '.$this->permission);
		
		if(!$this->checkPermission(2))
			return $this->draw_normal();
			
		$xhtml = '';
		foreach($this->boxes as $box) $xhtml .= $box->draw_modify();
		return $xhtml;
	}
	

	
	/* Updates its own url and the one of its kids 
	 * $prefix is a _language-encoded_ prefix! array(1=>'SBO',2=>'OSB',3=>'ZBO', etc.)
	 **********************************************/
	public function update_uri($prefixes){
		$fragm = $this->loadAdditionalFragments();
		$pass = array();
		//notice($this->name.' '.implode('.',$prefixes));
		
		if(NULL !== $prefixes) //if prefixes = NULL ,only update kids!
		{
			foreach($fragm as $l_id=>$values)
			{
				//notice($this->id.' a');
				if(!empty($prefixes[$l_id]))
				{
					$pass[$l_id] = $prefixes[$l_id].$values['folder_name'].'/';
				}
				else
				{
					//TODO: this is a crappy policy!!!
					if(isset($this->parent))
						$pass[$l_id] = $this->parent->uri.$values['folder_name'].'/';
					else
						//needed a fix for empty folder names.
						$pass[$l_id] = empty($values['folder_name']) ? '/' : '/'.$values['folder_name'].'/';
						
				}
				$this->mAdditionalFragments[$l_id]['uri'] = $pass[$l_id];
			}
		}
		
		foreach($this->mAdditionalFragments as $l=>$v)
			$pass[$l] = $this->mAdditionalFragments[$l]['uri'];
			
		$this->storeAdditionalFragments();
			
		//print_r($pass);

		foreach($this->kids as $kid)
			$kid->update_uri($pass);

	}/**/
	

	///@todo Make function obsolete by passing input directly from InputHandler to content/boxes	
	public function pass_input()
	{
		
		$ti = $GLOBALS['InputHandler']->getInputInfo();
			
		if( empty($ti) )
			return;
			
		$form_name = $ti['form_name'];
		$vector = $ti['vector'];
		$target_id = $ti['owner_id'];
		$input = $GLOBALS['InputHandler']->getFormInput($form_name,$ti['owner_id']);
			
		switch($ti['target_type']){
			case 'edit':
					$this->editContent = $this->content_index[$target_id]->editor_input($form_name,$vector,$input);
				break;
			case 'content':
				$this->content_index[$target_id]->user_input($form_name,$vector,$input);
				
				break;
			case 'box':
				$target = $this->box_index[$target_id]->editor_input($form_name,$vector,$input);
				
				break;
			default:
				//DO NOTHING. (might be login or logout ...)
		}

	}
	
	 
	
	public function getHeader(){
		$xhtml = '';
		$xhtml .= '<meta http-equiv="Content-Type" content="text/html; charset=' . mb_internal_encoding() . '" />';
		$xhtml .= '<link rel="stylesheet" href="http://' . $GLOBALS['request_host'] . '/webcontent/styles/layout.css" type="text/css" />';
		
		//files used by carousel
		$xhtml .= '<link rel="stylesheet" href="http://' . $GLOBALS['request_host'] . '/webcontent/styles/carousel.css" type="text/css" />';
		$xhtml .= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>';
		$xhtml .= '<script src="http://' . $GLOBALS['request_host'] . '/webcontent/js/slides.min.jquery.js"></script>';
		$xhtml .= '<script> $(function(){ $(\'#slides\').slides({ generatePagination: false, preload: false, generateNextPrev: false, play: 20000, randomize: true }); });</script>';
		
		//additional files used by lightbox
		$xhtml .= '<script src="http://' . $GLOBALS['request_host'] . '/webcontent/js/lightbox.js"></script>';
		$xhtml .= '<link rel="stylesheet" href="http://' . $GLOBALS['request_host'] . '/webcontent/styles/lightbox.css" type="text/css" />';
		

		$xhtml .= '<title>' . $this->title . '</title>';
		return $xhtml;
	}




	public function fetch_content(){
		
		
		/*Select boxes and build box-tree.
		********************************************************************************************/
	$query = "SELECT 
				b.id, 
				b.name, 
				b.left, 
				b.right, 
				s.class as style_class , 
				b.object_type as object_type, 
				SUM(DISTINCT bp.permission) as permission 
			FROM box b 
			JOIN style s ON s.id = b.style_id 
			JOIN box_permission bp ON bp.box_id = b.id 
			JOIN user_in_group uig ON uig.usergroup_id = bp.usergroup_id
			WHERE (`box_structure_id`='{$this->box_structure_id}' OR box_structure_id=-1) AND uig.user_id='{$GLOBALS['user']->id}' 
			GROUP BY b.id 
			ORDER BY `left` ASC";
		$answer = SqlQuery::getInstance()->simpleQuery($query);
		debug('BOXES from query');
		debug($answer);
		
		$this->boxes = Tree::make_index_tree_array($answer,$this->box_index);
	
		debug('BOXES in tree');
		debug($this->boxes);
		
		
		/*Select content and build content trees
		 ********************************************************************************************/
		//select content in Language of page or according to language preferences (SESSION) if it doesn't exist.
		//selects tags in same language as content BUT 'randomly
		$vmodes = ($_GET['vmode'] == VMODE_NORMAL);
		
		$language_coeff1 = COHERENT_PAGE_LANGUAGE && $vmodes ?
			Language::langPrefMySqlString('cf.language_id','cf.language_id',$this->language_id) :
			Language::langPrefMySqlString('cf.language_id');
		$language_coeff2 = COHERENT_PAGE_LANGUAGE && $vmodes ?
			Language::langPrefMySqlString('cf2.language_id','cf2.language_id',$this->language_id) :
			Language::langPrefMySqlString('cf2.language_id');
		
		$query = 	"SELECT 
 						c.* ,
 						cc.`object_type`, 
 						s.`class` as style_class, 
 						cf.`language_id`, 
 						cf.`text`, 
 						ccf.`tags`,
 						SUM(DISTINCT ccp.`permission`) as permission
					FROM `content` c 
					LEFT JOIN `content_fragment` cf ON cf.`content_id` = c.`id` 
					JOIN `content_class` cc ON cc.`id` = c.`content_class_id`
					LEFT JOIN `content_class_fragment` ccf ON 
						ccf.`content_class_id`= cc.`id`
						AND (ccf.language_id = cf.language_id OR (cf.language_id IS NULL AND ccf.`language_id`='{$_SESSION['language_id']}'))
					JOIN `content_class_permission` ccp ON ccp.`content_class_id` = cc.`id`
					JOIN `user_in_group` uig ON ccp.`usergroup_id` = uig.`usergroup_id`
					JOIN `style` s ON s.`id` = c.`style_id`
					LEFT JOIN `content_fragment` cf2 
						ON cf2.content_id = c.`id` 
						AND ".$language_coeff2." 
							< ".$language_coeff1."
					WHERE 
						uig.`user_id` = {$GLOBALS['user']->id} 
						AND ( c.`page_id` ='{$this->id}' OR c.`page_id` =".GLOBAL_PAGE_ID." )
						AND cf2.language_id IS NULL
					GROUP BY c.`id`
					ORDER BY c.`left` ASC,c.`right` ASC";

					
		$answer = SqlQuery::getInstance()->simpleQuery($query);
		
		//TODO: why do I need this if? must check this in tree node class!!!
		if(sizeof($answer) > 0){
			$this->content_tree = Tree::make_index_tree_array($answer,$this->content_index);	

			/*inject content into boxes and boxes into page.
			 ********************************************************************************************/
			Tree::inject_trees($this->box_index,$this->content_tree,'box_id','content');
			
			$GLOBALS['content_index'] = $this->content_index;
				
			debug($this->boxes);
		}
	}
	
	

}
?>
