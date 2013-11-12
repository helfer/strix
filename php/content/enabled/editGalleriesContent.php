<?php
class editGalleriesContent extends Content {

	protected $mConfKeys = array();
	protected $mConfValues = array();

	protected $process_msg = '';
	protected $process_status = false;
	
	protected $modify_id = -1;
	protected $modify_details = array();
	protected $processing_modification_status = false;
	protected $processing_add_picture_status = false;
	protected $picture_inline_edit_id = -1;
	protected $picture_inline_edit_title = '';
	
	protected $maxPictureHeight = 800;
	protected $maxPictureWidth = 1000;	
	protected $minPictureHeight = 250;
	protected $minPictureWidth = 250;
	protected $thumbHeight = 120;
	
	protected $imagedir = 'images/gallery/';
	
	
	public function display(){
		$sql = SqlQuery::getInstance();		

		//modify or delete?
		foreach($GLOBALS['POST_KEYS'] as $p){
			$p=explode('_', $p);
			if(count($p)==2 && $p[0]=='modifygalleryid' && is_numeric($p[1])){
				//check if this gallery exists
				$this->fillModifyDetails($sql, $p[1]);
				break;
			} elseif(count($p)==2 && $p[0]=='deletetegalleryid' && is_numeric($p[1])){
				//check if gallery is empty
				$query='select count(id) as num from gallery_pictures where gallery_id='.$p[1];
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				if($res[0]['num']!=0){
					$this->process_msg .= '<p class="error">Gallery can not be deleted: gallery is not empty!</p>';
				} else {
					//delete this gallery
					$query='delete from galleries where id='.$p[1];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
					if(!$ok){
						$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
						sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
					} else {
						$this->process_msg .= '<p class="success">Gallery has been removed sucessfully!</p>';
					}
				}		 			 
			} elseif(count($p)==2 && $p[0]=='deletetepicture' && is_numeric($p[1])){
				//read info from DB
				$query='select * from gallery_pictures where id='.$p[1];
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				//unlink files
				$dir=HTML_DIR.$this->mConfValues['file_directory'].'webcontent/'.$this->imagedir;
				if(!unlink($dir.$res[0]['base_name'].'_thumb.jpg') || !unlink($dir.$res[0]['base_name'].'.jpg')){
					$this->process_msg .= '<p class="error">Deleting Picture failed!</p>';
				} else {						
					//change position of remaining photos
					$query='update gallery_pictures set position=position-1 where position >'.$res[0]['position'];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
					//delete a picture
					$query='delete from gallery_pictures where id='.$p[1];
					$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
					if(!$ok){
						$this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
					} else {
						$this->process_msg .= '<p class="success">Picture has been removed sucessfully!</p>';
					}		
				}				
			} elseif(count($p)==3 && $p[0]=='movepicture' && is_numeric($p[2])){
				//read info from DB
				$query='select * from gallery_pictures where id='.$p[2];
				$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
				//move picture around
				switch($p[1]){
					case 'up':    	//only do if not at 0 yet
									if($res[0]['position']>0){
										//move down the one above
										$query='update gallery_pictures set position=position+1 where position='.($res[0]['position']-1);
										$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
										//move this one up
										$query='update gallery_pictures set position=position-1 where id='.$p[2];
										$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
									}
									break;
					case 'down':	//only do if not yet on top
									$query='update gallery_pictures set position=position-1 where position='.($res[0]['position']+1);
									$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	
									$ar=$sql->affected_rows().'<br/>'; $ok = $sql->end_transaction(); 
									if($ar>0){
										//move this one down
										$query='update gallery_pictures set position=position+1 where id='.$p[2];
										$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
									}
									break;
				}
			} elseif(count($p)==2 && $p[0]=='editpicture' && is_numeric($p[1])){
				//edit a picture by providing an inline form
				$this->picture_inline_edit_id=$p[1];
			}
		}
		
		//if currenbt modify id = -1, take from SESSION
		if($this->modify_id==-1 && isset($_SESSION['current_gallery_modify_id'])){
			$this->fillModifyDetails($sql, $_SESSION['current_gallery_modify_id']);
		 }
		
		$xhtml = '<p class="title">Managing Galleries</p>';
		
		//show process messages
		$xhtml .= $this->process_msg;
		
		//show form to add new gallery
		$xhtml .= '<p class="subtitle">Creating a new Gallery</p>';
		if($this->process_status) $form=$this->remakeForm('gallery_create_form');		
		else $form=$this->getForm('gallery_create_form');
		$xhtml .= $form->getHtml($this->id);
		
		$xhtml .= '<p class="subtitle">Existing Galleries</p>';
		//show existing galleries
		$this->writeExistingGalleries($sql, $xhtml);	
		
		//show forms to edit galleries
		if($this->modify_id>0){			
			//show form to change name etc.			
			$name=$this->modify_details['name'];
			if(strlen($name)>30) $name=substr($name, 0,30).'&hellip;';
			$xhtml .= '<p class="subtitle">Renameing Gallery "'.$name.'"</p>';
			$form=$this->getForm('gallery_modify_form');			
			$xhtml .= $form->getHtml($this->id);
			
			//show form to add a picture
			$xhtml .= '<p class="subtitle">Adding Picture to "'.$name.'"</p>';
			if($this->processing_add_picture_status) $addpictureform=$this->remakeForm('add_picture_to_gallery_form');	
			else $addpictureform=$this->getForm('add_picture_to_gallery_form');			
			$xhtml .= $addpictureform->getHtml($this->id);
			
			//show list to modify picture order and to remove pictures
			$this->show_existing_pictures($sql, $xhtml, $name);
		}
		
		return $xhtml;
	}
	
	protected function fillModifyDetails(&$sql, $id){
		$_SESSION['current_gallery_modify_id']=$id;
		$this->modify_id=$id;
		if($id>0){
			$query='select g.id, g.name, count(p.id) as numpic from  galleries g left join gallery_pictures p on p.gallery_id=g.id where g.id='.$id.' group by g.id';
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
			if(count($res)==1){
				$this->modify_details=$res[0];
				return true;
			} else {			
				$this->modify_details=array();
				$this->modify_id=-1;
				$_SESSION['current_gallery_modify_id']=$this->modify_id;
				return false;
			}
		}
	}
	
	public function writeExistingGalleries(&$sql, &$xhtml){
		//read existing galleries from DB
		$query='select g.id, g.name, count(p.id) as numpic from  galleries g left join gallery_pictures p on p.gallery_id=g.id group by g.name order by g.name asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		//only write if there are entries
		if(count($res)==0) return false;

		//begin form to show buttons
		$xhtml.='<form id="existing_galleries_table_form" action="" method="post">';
		
		//write table header
		$xhtml.='<table class="standard"><tr class="header"><td><p class="monospacebold">Name</p></td><td><p class="monospacebold"># Pictures</p></td><td><p class="monospacebold">Edit</p></td></tr>';
		
		$odd=1; $num=0; 
		foreach($res as $r){
			++$num; 
			
			//check if cancelled
			if($r['numpic']==0)	$style='';
			else $style=' style="color: green; font-weight: bold;"';
			
			$xhtml.='<tr class="';
			if($num==count($res)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}
			$name=$r['name'];
			if(strlen($name)>30) $name=substr($name, 0,30).'&hellip;';
			$xhtml.='"><td><p class="monospace"'.$style.'>'.$name.'</p></td><td><p class="monospace"'.$style.'>'.$r['numpic'].'</p></td>';
			//buttons to modify and delete
			$xhtml.='<td><button name="modifygalleryid_'.$r['id'].'" type="submit" class="img_edit"/>';
			if($r['numpic']==0){
				$xhtml.='&nbsp;<button name="deletetegalleryid_'.$r['id'].'" type="submit" class="img_delete"/>';		
			}
			$xhtml.='</td></tr>';			
		}
		$xhtml.='</table></form>';
		$xhtml.='<p>Galleries can be edited (<img src="/webcontent/styles/img/b_edit.png">) and empty galleries can be removed (<img src="/webcontent/styles/img/b_drop.png">) using the appropriate symbols. To upload or remove pictures, please choose to edit a gallery.</p>';

		return true;
	}
	
	protected function show_existing_pictures(&$sql, &$xhtml, &$galleryname){
		//read existing pictures from DB
		$query='select p.* from gallery_pictures p where gallery_id='.$this->modify_id.' order by p.position asc';
		$sql->start_transaction(); $res=$sql->simpleQuery($query); $sql->end_transaction(); 
		
		//only write if there are entries
		if(count($res)==0) return false;

		//begin form to show buttons
		$xhtml .= '<p class="subtitle">Current Pictures in "'.$galleryname.'"</p>';
		$xhtml.='<form id="existing_galleries_table_form" action="" method="post">';
		
		//write table header
		$xhtml.='<table class="standard"><tr class="header"><td></td><td><p class="monospacebold">Title</p></td><td width="100"><p class="monospacebold">Date</p></td><td width="75"><p class="monospacebold">Edit</p></td></tr>';
		
		$odd=1; $num=0; 
		foreach($res as $r){
			++$num; 
			
			$xhtml.='<tr class="';
			if($num==count($res)){
				$xhtml.='last';
			} else {
				if($odd==1) $xhtml.='odd';
				else $xhtml.='even';
				$odd=1-$odd;
			}
			$name=$r['title'];
			//if(strlen($name)>120) $name=substr($name, 0,120).'&hellip;';
			$xhtml.='"><td><img src="/webcontent/'.$this->imagedir.$r['base_name'].'_thumb.jpg"></td><td>';
			if($this->picture_inline_edit_id==$r['id']){
				//show an inlien form
				$this->picture_inline_edit_title = $r['title'];
				$form=$this->getForm('edit_picture_form');			
				$xhtml .= $form->getHtml($this->id);			
			} else $xhtml.='<p class="monospace">'.$name.'&nbsp;<button name="editpicture_'.$r['id'].'" type="submit" class="img_edit"/></p>';
			$xhtml.='</td><td><p class="monospace"'.$style.'>'.date('j.n.Y',strtotime($r['uploaded'])).'</p></td>';
			//buttons to modify and delete
			$xhtml.='<td><button name="deletetepicture_'.$r['id'].'" type="submit" class="img_delete"/>';		
			$xhtml.='&nbsp; &nbsp;<button name="movepicture_up_'.$r['id'].'" type="submit" class="img_up"/>';
			$xhtml.='&nbsp;<button name="movepicture_down_'.$r['id'].'" type="submit" class="img_down"/>';
			$xhtml.='</td></tr>';			
		}
		$xhtml.='</table></form>';
		$xhtml.='<p>Pictures can be edited (<img src="/webcontent/styles/img/b_edit.png">), removed (<img src="/webcontent/styles/img/b_drop.png">) and moved (<img src="/webcontent/styles/img/b_up.png"> <img src="/webcontent/styles/img/b_down.png">) using the appropriate symbols. To upload or remove pictures, please choose to edit a gallery.</p>';

		return true;
	}
	
	
	function process_gallery_create_form(){
		$frm = $this->getForm('gallery_create_form');
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">'.(Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors'])).'</p>';
			return false;
		}
		
		$this->process_status=true;
		$values = $frm->getElementValues();
				
		$sql = SqlQuery::getInstance();				
		//is it update or insert?
		if(isset($values['submit_add_gallery'])){
			//query
			$query='insert into galleries (name) values ("'.$values['g_name'].'")';
			//insert
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
				sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Gallery "'.$values['g_name'].'" has been added sucessfully!</p>';
			}
			return true;			
		} 				
	}
	
	public function process_gallery_modify_form(){
		$this->processing_modification_status=false;
		$frm = $this->getForm('gallery_modify_form');
		$values = $frm->getElementValues();
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">'.Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors']).'</p>';			
			return false;
		}
		
		$this->processing_modification_status=true;
		
		$sql = SqlQuery::getInstance();
		
		if(isset($values['submit_modify_gallery'])){
			//query
			$query='update galleries set name="'.$values['g_name'].'" where id='.$values['modify_gallery_id'];
			
			//update
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Gallery '.$values['g_name'].' has been sucessfully modified!</p>';		 			 
			}
			$this->fillModifyDetails($sql, $values['modify_gallery_id']);
			return true;			
		}
		$this->fillModifyDetails($sql, $values['modify_gallery_id']);
		return false; 	
	}
	
	public function process_add_picture_to_gallery_form(){
		$this->processing_add_picture_status=false;
		$sql = SqlQuery::getInstance();	

		$frm = $this->getForm('add_picture_to_gallery_form');
		$values = $frm->getElementValues();
		$this->fillModifyDetails($sql, $values['modify_gallery_id']);
		
		$validate=$frm->validate();
		if(!$validate){
			$this->process_msg .= '<p class="error">'.(Language::extractPrefLan($GLOBALS['tag_array']['form_has_errors'])).'</p>';
			return false;
		}
		

		//insert picture
		if(isset($GLOBALS['HTTP_POST_FILES']['photo']['name'])){
						
			for($i=0; $i<count($GLOBALS['HTTP_POST_FILES']['photo']['name']); ++$i){
					$all_photos[]=array('name'=>$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i], 'type'=>$GLOBALS['HTTP_POST_FILES']['photo']['type'][$i], 'tmp_name'=>$GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i], 'error'=>$GLOBALS['HTTP_POST_FILES']['photo']['error'][$i], 'size'=>$GLOBALS['HTTP_POST_FILES']['photo']['size'][$i]);
			}
			
			$dir=HTML_DIR.$this->mConfValues['file_directory'].'webcontent/'.$this->imagedir;
			for($i=0; $i<count($GLOBALS['HTTP_POST_FILES']['photo']['name']); ++$i){	
				//check error		
				if($GLOBALS['HTTP_POST_FILES']['photo']['error'][$i]>0){				
					switch($GLOBALS['HTTP_POST_FILES']['photo']['error'][$i]){
						case 1:
						case 2: $this->process_msg .= '<p class="error">"'.$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i].'" is too big!</p>'; break;
						case 3: 
						case 4:
						case 5:
						case 6:
						case 7: 
						case 8: $this->process_msg .= '<p class="error">Upload of "'.$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i].'" failed!</p>'; break;
					}						
				} else {			
					//check size		
					list($width,$height)=getimagesize($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i]);
					if($width<$this->minwidth || $height<$this->minheigth){				
						$this->process_msg .= '<p class="error">"'.$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i].'" is too small!</p>';
					} else {		
						//check extension
						$ext = substr(strrchr($GLOBALS['HTTP_POST_FILES']['photo']['name'][$i], "."), 1);
						if(!in_array($ext, array('jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG'))){
							$this->process_msg .= '<p class="error">Format of "'.$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i].'" is not supported!</p>';
						} else {											
							//construct filename for photo from gallery name and photo name
							$filename=$this->modify_id.'_'.substr(preg_replace('/[^a-zA-Z0-9]/s', '', $this->modify_details['name']), 0, 10).'_'.substr(preg_replace('/[^a-zA-Z0-9]/s', '', $GLOBALS['HTTP_POST_FILES']['photo']['name'][$i]), 0, 10).'_'.date('His').'_'.substr(md5(microtime()),rand(0,26),2);

							//resize image							
							list($width,$height)=getimagesize($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i]);
							$src=0;
							switch($ext){
								case 'jpg':
								case 'jpeg':
								case 'JPG':
								case 'JPEG': $src = imagecreatefromjpeg($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i]); break;
								case 'png':
								case 'PNG': $src = imagecreatefrompng($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i]); break;
							}							
							
							//shrink if too big
							$shrink=false; $neww=$width; $newh=$height;
							if($neww>$this->maxPictureWidth){
								$shrink=true;
								$neww=$this->maxPictureWidth;
								$newh=($height/$width)*$neww;				
							}
							if($newh>$this->maxPictureHeight){
								$shrink=true;		
								$newh=$this->maxPictureHeight;		
								$neww=($width/$height)*$newh;				
							}
							if($shrink){
								$tmp=imagecreatetruecolor($neww,$newh);
								imagecopyresampled($tmp,$src,0,0,0,0,$neww,$newh, $width,$height);
								if(!imagejpeg($tmp,$dir.$filename.'.jpg',95)){
									$this->process_msg .= '<p class="error">Upload failed!</p>';
									return false;
								}
								imagedestroy($tmp);
							} else {
								if(!move_uploaded_file($GLOBALS['HTTP_POST_FILES']['photo']['tmp_name'][$i], $dir.$filename.'.'.$ext)){
									$this->process_msg .= '<p class="error">Upload failed!</p>';
									return false;
								} else chmod($dir.$filename.'.'.$ext, 0644);
							}

							//create thumbnail
							//first, crop image to be a square, then resize
							if($width>$height){
								//crop middle of width
								$crops=round(($width-$height)/2);
								$tmp_sq=imagecreatetruecolor($height, $height);
								imagecopy($tmp_sq,$src,0,0,$crops,0,$width,$height);
								$tmp=imagecreatetruecolor($this->thumbHeight,$this->thumbHeight);
								imagecopyresampled($tmp,$tmp_sq,0,0,0,0,$this->thumbHeight,$this->thumbHeight, $height,$height);	
								imagedestroy($tmp_sq);			
							} elseif($height>$width){
								//crop middle of height
								$crops=round(($height-$width)/2);
								$tmp_sq=imagecreatetruecolor($width, $width);
								imagecopy($tmp_sq,$src,0,0,0,$crops,$width,$height);
								$tmp=imagecreatetruecolor($this->thumbHeight,$this->thumbHeight);
								imagecopyresampled($tmp,$tmp_sq,0,0,0,0,$this->thumbHeight,$this->thumbHeight, $width,$width);	
								imagedestroy($tmp_sq);				
							} else {
								//is already a square
								$tmp=imagecreatetruecolor($this->thumbHeight,$this->thumbHeight);
								imagecopyresampled($tmp,$src,0,0,0,0,$this->thumbHeight,$this->thumbHeight, $width,$height);
							}			
							//save thumbnail
							imagejpeg($tmp,$dir.$filename.'_thumb.jpg',95);
							imagedestroy($tmp);			
							imagedestroy($src);

							//update DB
							$query='select max(position)+1 as position from gallery_pictures where gallery_id='.$this->modify_id;
							$sql->start_transaction(); 	$res=$sql->simpleQuery($query);	$ok = $sql->end_transaction(); 
							$pos=0; if($res[0]['position']>0) $pos=$res[0]['position'];
							$query='insert into gallery_pictures (gallery_id, base_name, title, position) values ('.$this->modify_id.', "'.$filename.'", "'.$values['photo_title'].'", '.$pos.')';
							$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok=$sql->end_transaction(); 
							if(!$ok){
								 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
								 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
							} else {
								$this->process_msg .= '<p class="success">Picture "'.$GLOBALS['HTTP_POST_FILES']['photo']['name'][$i].'" has been uploaded sucessfully!</p>';
								$this->processing_add_picture_status=true;
							}					
						}
					}
				}
			}
		}
	}
			
	public function process_edit_picture_form(){

		$frm = $this->getForm('edit_picture_form');
		$values = $frm->getElementValues();
	
		if(!$frm->validate()){
			$this->process_msg .= '<p class="error">Please verify highlighted fields.</p>';		
			$this->picture_inline_edit_id = $values['modify_picture_id'];
			return false;
		}
		
		$sql = SqlQuery::getInstance();
		
		if(isset($values['submit_change_picture_title'])){
			//query
			$query='update gallery_pictures set title="'.$values['photo_title'].'" where id='.$values['modify_picture_id'];
			
			//update
			$sql->start_transaction(); $res=$sql->simpleQuery($query); $ok = $sql->end_transaction(); 
			if(!$ok){
				 $this->process_msg .= '<p class="error">Transaction failed!<br/>An email about this was sent to the administrator.</p>';
			 sendSBOmail('daniel.wegmann@olympiads.unibe.ch','DB Transaction failed in ibo2013TeamMemberSignupContent', $query);
			} else {
				$this->process_msg .= '<p class="success">Picture has been sucessfully modified!</p>';		 			 
			}
			return true;			
		}
		return false; 	

	}

	protected function gallery_create_form($vector){
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(250,400);

		$gname = new Input('Gallery Name: ','text','g_name','',array('size'=>35));
		$gname->addRestriction(new StrlenRestriction(3,128));		
		$newForm->addElement('g_name',$gname);	
		
		$newForm->addElement('submit_add_gallery', new Submit('submit_add_gallery','add gallery'));
		
		return $newForm;
	}
	
	protected function gallery_modify_form($vector){
		$newForm = new TabularForm(__METHOD__);
		$newForm->setVector($vector);
		$newForm->setProportions(250,400);

		$gname = new Input('Gallery Name: ','text','g_name',$this->modify_details['name'],array('size'=>35));
		$gname->addRestriction(new StrlenRestriction(3,128));		
		$newForm->addElement('g_name',$gname);	
		
		$newForm->addElement('modify_gallery_id', new Hidden('modify_gallery_id', $this->modify_id));
		$newForm->addElement('submit_modify_gallery', new Submit('submit_modify_gallery','modify gallery'));
		
		return $newForm;
	}
	
	protected function add_picture_to_gallery_form($vector){
		$newForm = new TabularForm(__METHOD__,'',array('enctype'=>'multipart/form-data'));
		$newForm->setVector($vector);
		$newForm->setProportions(250,400);
	
		$newForm->addElement('maxsize', new Hidden('MAX_FILE_SIZE',10*1048576));

		$photo = new FileInputMultiple('Picture (> '.$this->minPictureWidth.' x '.$this->minPictureHeight.', < 10Mb): ', 'photo');
		//$photo->addRestriction(new isImageRestriction('photo', 10*1048576, $this->minPictureWidth, $this->minPictureHeight));
		$newForm->addElement('photo',$photo);	

		$gname = new Input('Title: ','text','photo_title','',array('size'=>35));
		$gname->addRestriction(new StrlenRestriction(0,256));		
		$newForm->addElement('photo_title',$gname);	
		
		$newForm->addElement('modify_gallery_id', new Hidden('modify_gallery_id', $this->modify_id));
		$newForm->addElement('submit_add_picture', new Submit('submit_add_picture','add picture'));
		
		return $newForm;		
	}

	protected function edit_picture_form($vector){
		$newForm = new simpleForm(__METHOD__);
		$newForm->setVector($vector);
	
		$gname = new Textarea('','photo_title',$this->picture_inline_edit_title,3,34);
		$gname->addRestriction(new StrlenRestriction(0,256));		
		$newForm->addElement('photo_title',$gname);	
		
		$newForm->addElement('modify_picture_id', new Hidden('modify_picture_id', $this->picture_inline_edit_id));
		$newForm->addElement('submit_change_picture_title', new Submit('submit_change_picture_title','save'));
		
		return $newForm;		
	}

}
?>

