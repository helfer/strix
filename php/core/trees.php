<?php

//Tree Node Object:
/*******************************************************************************/


//TODO: there's a dangerous mixup beteween page-tree, box-tree and content trees!


//TODO: each TreeNode should also have a tree-identifier field, 
//which allows to see if two nodes belong to the same tree.

abstract class TreeNode extends DatabaseObject{

	protected $parent = NULL;
	protected $position = 0;
	protected $kids = array(); //kids are always of the same metatype, eg. a box for a box, a content for a content.
	protected $left = 0;
	protected $right = 0;
	protected $kids_field = 'kids';
	
	
	
	protected final function tree_stats()
	{
		return '<p>obj: '.get_class($this).' id: '.$this->id.' pos: '.$this->position.' left: '.$this->left.' right: '.$this->right.'</p>';
	}
	
	public final function list_kids()
	{
		$str = $this->id." =>\n";
		$str = 'num '.$this->count_kids()."\n";
		foreach($this->{$this->kids_field} as $kid)
			$str .= $kid->tree_stats()."\n";
			
		
		return $str;
	}
	
	//returns the ids of all ancestors
	protected final function get_ancestors()
	{
		if(!isset($this->parent))
			return array($this->id);
			
		return array_merge($this->parent->get_ancestors(),array($this->id));	
	}
	
	
	protected final function get_kids()
	{
		return $this->kids;	
	}
	
	
	/* Abstract functions:
	 ********************************************************/
	 
	 protected function adopt($kid){
	 	debug('I ('.$this->object_type.' '.$this->id.') adpot '.$kid->object_type.' '.$kid->id);
	 	$kid->__set('parent',$this);
	 	
	 	//TODO: not perfect... really...
	 	if(is_subclass_of($this,'Box') && is_subclass_of($kid,'Content'))
	 	{
	 		$kid->__set('box_id',$this->id);	
	 	}
	 	else
	 	{
	 		//TODO: yeah whatever. Really need to clean up this tree stuff!!!
	 	}
	 	//TODO: better set parent method!!!
	 }
	 
	 /* Final functions
	 *****************************************/

	
	public final function move_up()
	{
		if(!isset($this->parent))
			return FALSE;
			
		if($this->position <= 0)
			return FALSE;
		//notice('p= '.$this->position);
		Tree::transfer($this->id,$this->parent,$this->position,$this->parent,$this->position-1);
		//notice('p= '.$this->position);
		return TRUE;
	}
	
	public function move_down()
	{
		if(!isset($this->parent))
			return FALSE;
			
		if ($this->position >= sizeof($this->parent->get_kids()) -1 )
			return FALSE;
		//notice('p = '.$this->position);
		Tree::transfer($this->id,$this->parent,$this->position,$this->parent,$this->position+1);
		//notice('p= '.$this->position);
		return TRUE;
	}	 
	 
	
	//don't use this functionx directly, kids should manage details. meta: WTF???
	
	 public final function insert_kids($kids,$insert_position='e')
	 {
	 	//autofix
		if(!is_array($kids))
			$kids = array($kids);
			
		if(empty($kids))
			return TRUE;
		
		
		debug('requestpos = '.$insert_position);
		
		if($insert_position === 'e')	//insert at the end
			$insert_position = sizeof($this->{$this->kids_field});
			
		
		debug($this->list_kids());
		array_splice($this->{$this->kids_field},$insert_position,0,$kids);
		debug($this->list_kids());

		foreach($kids as $kid){
			 $this->adopt($kid);
		}
			
		$i = $insert_position - 1;
		$end = sizeof($this->{$this->kids_field});
		while(++$i < $end){
			debug("inserter -> position old: {$this->{$this->kids_field}[$i]->position} new: $i");	
			$this->{$this->kids_field}[$i]->position = $i;
		}
		
		//THIS is fatal here! tree structure gets damaged if some content is invisible to current user!
		//$this->reposition_kids(); //update left+ right values
		
		debug(array_keys($this->{$this->kids_field}));
			
			//TODO: return number of kids or position of last kid inserted??
		return TRUE;
			
	}


	//removes a kid, id of first kid must correspond to id of node at that position!
	//don't use this function directly, kids should manage details.
	public final function remove_kids($id,$position,$number)
	{
		if ($this->{$this->kids_field}[$position]->id != $id)
		{
			throw new Exception('First element ID mismatch for remove!');
			return FALSE;
		}
			
		$ret = array_splice($this->{$this->kids_field},$position,$number);
		
		
		
		foreach ($ret as $kid){
			notice($kid->abb.' '.$kid->folder_name);
			$kid->__set('parent', NULL);	
		}
		
		$i = $position - 1;
		$end = sizeof($this->{$this->kids_field});
		
		while (++$i < $end)
		{
			debug("remover -> position old: {$this->{$this->kids_field}[$i]->position} new: $i");	
			$this->{$this->kids_field}[$i]->position = $i;
		}
		
		return $ret;
	}
	
	/* Standard functions:
	 *****************************************/
	
	//entry field: field which holds kids of root
	//kids field: field which holds kids of kids.
	//input: the value that left should have
	//output: the value of new right + 1
	public function reposition_self($pos){ //TODO: could set 1 as default
		//throw new Exception('ha, gotcha!!');
		
		
		$this->__set('left',$pos++);
		foreach($this->kids as $kid) $pos = $kid->reposition_self($pos);
		$this->__set('right',$pos++);
		
		//TODO: BUGFIX, this shouldn't be necessary.
		if(get_class($this) == 'ContentBox')
			$this->reposition_kids();
		
		$this->store(); //we might need a writeback ...

		return $pos;
	}
	
	//TODO: fix this, repositioning is not working right now!
	
	//reposition not self, but the kids (which must be in a tree with different left and right!!)
	public function reposition_kids($start = 0){
		//print_r($this->{$this->kids_field});
		//exit();
		//only reposition from $start on!!
		if ($start > 0)
			$pos = $this->{$this->kids_field}[$start-1]->__get('right') + 1;
		else
			$pos = 1;
		foreach(array_slice($this->{$this->kids_field},$start) as $kid){
			$pos = $kid->reposition_self($pos);	
		}
		debug('wow I now have '.(($pos-1)/2).' kids!');
		return $pos;
	}
	
	public final function reposition_in_tree()
	{
		if(!empty($this->parent))
			$this->parent->reposition_in_tree();
		else
			$this->reposition_self(1);
		
	}
	
	public final function count_kids(){
		return count($this->{$this->kids_field});
	}
	
	
	
	protected final function suicide(){

			if(!empty($this->kids) || !empty($this->{$this->kids_field}))
			{
				error('You must remove all kids before deleting!');
				return FALSE;
			}
			
			
			if ($this->delete())
			{
				$tmp_parent = $this->parent;
				$tmp_parent->remove_kids($this->id,$this->position,1);
				$tmp_parent->reposition_in_tree();
				return TRUE;
			}
			else
			{
				error('could not delete!');
				return FALSE;
			}	
	}
	
	
	
	
	public function isFirstKid()
	{
		if(!isset($this->parent))
			return FALSE;
			
		return ($this->position == 0);	
	}
	
	public function isLastKid()
	{
		if(!isset($this->parent))
			return FALSE;
			
		//notice($this->id.', '.$this->position. ' cmp '.($this->parent->count_kids() -1));
		//notice($this->parent->list_kids());
		return ($this->position == ($this->parent->count_kids() -1));	
	}

}








abstract class Tree
{
	
	
	/* UTILS:
	 * ************/
	
	public static function print_tree($root,$field_of_intrest = NULL,$level = 0)
	{
		$info = '';
		if(!empty($field_of_intrest))
			$info = $field_of_intrest.'= '.$root->$field_of_intrest.' ';
		$indent = str_repeat("\t",$level);
		$str = $indent.'<li>'.$info.'c= '.get_class($root).' id='.$root->id.' l='.$root->left.' r='.$root->right.'</li>'."\n";
	
		$kids = $root->kids;
		if(empty($kids))
			return $str;	
		
		$str .= $indent.'<ul>';
		
		foreach($kids as $kid)
			$str .= Tree::print_tree($kid,$field_of_intrest,$level+1);	
		
		$str .= $indent.'</ul>';
		
		return $str;
	}
	
	
	/* Static final functions:
	 ********************************************************/
	
	//does what it says
	//can build and return multiple trees, not all part of a bigger one
	//OR might return multiple trees as parts of a bigger one whose root
	//is not visible [in the array] => TODO: make a function make_index_tree for this.
	public static function make_index_tree_array(&$array,&$index = false)
	{
		//print_r($array);
		//echo '<br /><br /><br /><br />';
		$array_pointer = 0; //index for array
		$r_bound = 0; //0 means unbounded
		debug($array);
		$ret_array = array();
		
		$num = sizeof($array);
		while($array_pointer < $num)
		{
			//echo 'now taking '.$array_pointer.'<br />';
			$tree_pos = 1;
			$tree_root = self::rec_make_tree_array($array,$array_pointer,$tree_pos,$r_bound,$index);
			debug(Tree::print_tree($tree_root,'name'));
			$ret_array []= $tree_root;
		}
		
		if($diff = $num - $array_pointer)
			error('make_index_tree_array: '.$diff.' nodes left out!');
		return $ret_array;
	
	}
	
	//TODO: add a function make index tree, which does NOT reset the tree_pos after each tree!
	
	
	
	/* FUNCTION rec_make_tree_array:
	 * 
	 * recursive function that creates a tree of objects out of an array
	 * of an array of variables for a TreeObject [left,right, object_type, id etc.]
	 * gaps are expected in the tree [because of missing read permissions for normal users]
	 * 
	 * 
	 * Arguments:-----
	 * &array: array( 0=> array('id'=>11,'left'=>0,'right'=>14,'object_type'=>'Page', ...),1=> array( .... ) ... )
	 * &array_pointer: index of the current element in the array
	 * &tree_pos: current position in the tree (when going throught left and right)
	 * &r_bound: if obj->right >= r_bound, array() will be returned and tree_pos set to r_bound
	 * &index: if index !== FALSE, objects will be indexed in this array by their id.
	 * 
	 * Return values: ------------
	 * returns the root of the tree
	 * 
	 * ***********************************************************/
	
	public static function rec_make_tree_array(&$array, &$array_pointer, &$tree_pos, $r_bound, &$index = false)
	{
		//check if index exists:
		if(!isset($array[$array_pointer]))
		{
			//gaps are normal if people don't see all the pages!
			debug('array size is '.sizeof($array));
			debug('array index out of bounds: '.$array_pointer);
			
			$tree_pos = $r_bound;
			
			//must return array() because insert_kids will not take NULL!
			return array();
		}
			
		//create object
		$node = $array[$array_pointer];
		$obj = new $node['object_type']($node);
		
		//verify that obj->left == tree_pos
		if($obj->left > $tree_pos)
		{
			debug('Gap between tree_pos '.$tree_pos.' and left '.$obj->left);
			$tree_pos = $obj->left;
		}
		else if ($obj->left < $tree_pos)
		{
			//could also be a new tree!!! this is an impurity in the design of make_index_tree_array ...
			throw new TreeException('Array chaos! Left lower than tree_pos at left='.$obj->left.' tree_pos = '.$tree_pos);
		}	
		//verify that obj->right < r_bound
		if( ($obj->right >= $r_bound) && ($r_bound !== 0) )
		{
			//because of missing permissions, not all nodes appear, leading to gaps.
			debug('Missing node? r_bound is '.$r_bound.' but obj->right is '.$obj->right);
			$tree_pos = $r_bound;

			//must return array() because insert_kids will not take NULL!
			return array();
		}
		
		//write object to index [if needed]
		if($index !== FALSE) $index[$obj->id] = $obj;
		
		//object was rightfully parsed, so advance array pointer.
		$array_pointer++;
		
		$tree_pos++;
		
		//loop to add all kids
		while($tree_pos < $obj->right)
			$obj->insert_kids(self::rec_make_tree_array($array,$array_pointer,$tree_pos,$obj->right,$index));

		
		//tree_pos must now be obj->right
		if($tree_pos != $obj->right)
			throw new TreeException('Unexpected value for tree_pos: '.$tree_pos.', should be: '.$obj->right);
			
		
		$tree_pos++; //tree pos should now be the left of the next node
		
		return $obj;
	}
	
	
	//parent-ident-field is the field in kids which identifies the parent.
	public static final function inject_trees(&$parent_array,&$children_trees,$parent_ident_field,$kids_field){
		foreach($children_trees as $kid){
			if(isset($parent_array[$kid->$parent_ident_field])){
				$parent_array[$kid->$parent_ident_field]->insert_kids($kid,'e'); //insert at end.
			}
		}
	}	
	
	//transfers a tree node: old parent and new parent are OBJECTS, fields identify which vars hold parent and kids
	//ONLY USE for real transfers!!!
	public static final function transfer($id,&$old_parent,$old_pos,&$new_par,$new_pos){
		$new_parent = $new_par; //in case referred object is removed by calling remove kids.
		debug('transfer '.$id.' o.pos '.$old_pos.' n_pos '.$new_pos);
		if($new_pos >= 0 && $os = $old_parent->remove_kids($id,$old_pos,1)){

			notice('after rmove '.$os[0]->abb.': '.$os[0]->folder_name);
			//notice('new Pos= '.$new_pos);
			//notice('np');
			//print_r($new_parent);
			//notice('extracted');
			//print_r($o);
			if ($new_parent->insert_kids($os,$new_pos))
			{
				notice('after insert '.$os[0]->abb.': '.$os[0]->folder_name);
				
			$new_parent->reposition_kids();
			/*
				if ($new_parent->id == $old_parent->id && $new_parent->object_type == $old_parent->object_type)
						$new_parent->reposition_kids(min($old_pos,$new_pos));
				else
				{
						$old_parent->reposition_kids($old_pos); //update left+right values
						$new_parent->reposition_kids($new_pos);
				}
			*/
			} else {
				throw new Exception('Transfer failed!!!');
			}
		}else
			error('cannot insert => cannot make transfer!');
	}	
	
}

?>
