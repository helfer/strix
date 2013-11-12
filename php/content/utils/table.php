<?php

/*
	class TableManager{
		//Singleton. Manages all tables and persists in Session.
		//Makes sure GET is appropriately handled.
		
		
		/*
		
		SELECT $vars FROM $table WHERE $conditions ORDER BY $order
		
		
		$vars = array('n1'=>'as1','n2'=>'as2','n3'=>'as3','n4'=>'as4');
		$table = array(t1 => asp1 ...);
		$conditions array(id => '> 0', ab => '= 12');
		$initial_order(id => 'ASC'
		
		
		*//*
		
		private $table_index = array();
		public $a = 'x';
		
		private function __construct(){
		}
		
		public static function getInstance(){
			if (!isset($instance)){
				//table-manager must survive in session [this instance catches the get_variables to order or edit tables]
				if(isset($_SESSION['table_manager']))
					$instance = $_SESSION['table_manager'];
				else{
					$instance = new TableManager();
					$_SESSION['table_manager'] = $instance;
				}
			}
			//$instance->a .= 'x';
			//echo '<br />'.$instance->a.' <br />';
			return $instance;
		}
		
		public function newOrderedTable($table_name,$query){
			if($this->tableExists($table_name))
				return;
			//	throw new Exception('Table with name '.$table_name.' already exists!'); //TODO: make proper Exception for this
				
			$this->table_index[$table_name] = new OrderedTable($table_name,$query,array('id'=>'a','title'=>'ti','page_group_id'=>'na'));
			//echo 'mission ok';
		}
		
		public function displayTable($table_name){
			if(!$this->tableExists($table_name)){
				print_r($this);
				throw new Exception('Table with name '.$table_name.' does not exist!'); //TODO: make proper Exception for this
				
			}
			
			$table = $this->table_index[$table_name];
			

			
			return $table->display();
		}
		
		public function parseUserInput($arr){
			if(isset($arr['tbl']) && $this->tableExists($arr['tbl']) && isset($arr['order_by']) && isset($arr['dir'])){
				$this->table_index[$arr['tbl']]->OrderBy(array($arr['order_by']=>$arr['dir']));
				//$this->query = $this->query.' ORDER BY `'.$_GET['order_by'].'` '.$_GET['dir'];	
			}
			
		}
		
		
		public function tableExists($name){
			return in_array($name,array_keys($this->table_index));
		}
		
	}
	
	
	//actions carried out directly on table, but table reports to table manager?
	class Table{
		protected $name = 'none';
		protected $query = '';
		protected $vars = array();
		protected $tables = array();
		protected $conditions = '';
		protected $order = array();
		
		protected $order_by = '';
		
		protected function makeQuery(){
			$query = 'SELECT '.$this->arg2str($this->vars). ' FROM '.$this->arg2str($this->tables).' '.$this->conditions.' '.$this->order_by;
			return 	SqlQuery::simpleQuery($query);
		}
		
		
		public function OrderBy($cols){
			//TODO: verify that all cols really exist in query!
			$this->order = array_merge($this->order,$cols);
			$this->order = array_merge($cols,$this->order);
			if(sizeof($this->order) == 0){
				$this->order_by = '';
				return;	
			}
				
			$this->order_by = ' ORDER BY '.$this->arg2str($this->order);
		}
		
		
		protected final function arg2str($arr){
			$res = '';
			foreach($arr as $c=>$d){
				$res .= ' `'.$c.'` '.$d.',';
			}
			return trim($res,',');	
		}
		
		
		public static function in_vars($arg){
			return in_array($arg,array_keys($this->vars));	
		}
	}
	

	class OrderedTable extends Table{
		// Table::protected $query = '';
		
		

		
		public function __construct($name, $query, $vars){
			$this->name = $name;
			$this->query = $query;
			$this->vars = $vars;
		}
		
		
		
		public function display($selection = FALSE){
			echo ' >>> '.$this->query.$this->order_by;
			$result_array = SqlQuery::simpleQuery($this->query.$this->order_by);
			$indexes = FALSE;
			if(sizeof($result_array) == 0)
	 		return 'functions.array2html: array size is zero';
	 	
	 			$colnames = array_keys(reset($result_array));

				$html = '<table class="standard" align="center">';
				if($indexes)
					$html .= '<th style="standard">#</th>';
						
				foreach($colnames as $col){
					$order = 'ASC';
					if(isset($_GET['order_by']) && ($_GET['order_by'] == $col) && $_GET['dir'] == 'ASC')
						$order = 'DESC';
					$html .= '<th style="standard"><a href="?tbl='.$this->name.'&amp;order_by='.$col.'&amp;dir='.$order.'">'.$col.'</a></th>';
				}
				$even=false;
				$i = 1;
				foreach($result_array as $row){
					
					if($even)$html .= '<tr class="even">'; else $html.= '<tr class="odd">';
					$even = !$even;
					if($indexes)
						$html .= '<td>'.$i.'</td>';
					foreach($row as $element){
						$html .= '<td>'.$element.'</td>';
					}
					$html .= "</tr>\n";
					$i++;
				}
			return $html.'</table>';
		} 	
		
		
		
	}
*/





class AbTable
{
		// Table::protected $query = '';
		protected $name;
		protected $query;
		protected $pk;
		protected $mOrdering = TRUE;
		protected $order_by = '';
		protected $mXhtml = 'table';
		protected $mQsa = '';
		

		protected $mOptions = array();
		
		//qsa = query string to append. Don't forget the & (for now)
		public function __construct($name, $query, $pk = array(),$ordering = TRUE,$qsa = ''){
			$this->name = $name;
			$this->query = $query;
			$this->pk = $pk;
			$this->mOrdering = $ordering;
			$this->mQsa = $qsa;
			
			//doing this here to be able to verify selection
			$this->mXhtml = $this->makeTable();
		}
		
		//returns NULL if table does not have the element stipulated in $_GET
		//TODO: return values of this function are a mess and counter-intuitive!
		public function getSelected(){
			if(isset($_GET['tbl']) && $_GET['tbl'] == $this->name && isset($_GET['select'])){
				
				if(array_search($_GET['select'],$this->mOptions) === FALSE)
				{
					if(sizeof($this->pk) == 1)
						return NULL;
					else
						return array();
				}	
				
				$arr =  explode(':',$_GET['select']);
				//print_r($arr);
				if(sizeof($arr) == 1)
					return $arr[0];
				else
					return $arr;
			}else
				return NULL;
			
		}
		
		
		public function getHtml()
		{
			return $this->mXhtml;
		}
		
		protected function makeTable()
		{
			
			//------------------- ORDER ------------------
			if($this->mOrdering && isset($_GET['tbl']) && $_GET['tbl'] == $this->name && isset($_GET['order_by'])){
				$this->order_by = ' ORDER BY `'.$_GET['order_by'].'` '.$_GET['dir'];
			}
			//--------------------------------------------
			
			$html = '';
			debug($this->name.' >>> '.$this->query.' '.$this->order_by);
			//make query to database
			$result_array = SqlQuery::getInstance()->simpleQuery($this->query.' '.$this->order_by);
			
			//display selection option?
			if($this->pk != array())
				$selection = TRUE;	
			else
				$selection = FALSE;
				
			
			if(sizeof($result_array) == 0)
	 		return 'functions.array2html: array size is zero';
	 	
	 			$colnames = array_keys(reset($result_array));

				$html .= '<table class="standard" align="center">';
				if($selection)
					$html .= '<th style="standard">#</th>';
						
				foreach($colnames as $col){
					$order = 'ASC';
					if(isset($_GET['order_by']) && ($_GET['order_by'] == $col) && $_GET['dir'] == 'ASC')
						$order = 'DESC';
					if($this->mOrdering)
						$html .= '<th style="standard"><a href="?tbl='.$this->name.'&amp;order_by='.$col.'&amp;dir='.$order . $this->mQsa . '" title="order by '.$col.' '.$order.'">'.$col.'</a></th>';
					else
						$html .= '<th style="standard">'.$col.'</th>';
				}
				$even=false;
				$i = 1;
				foreach($result_array as $row){
					
					if($even)
						$html .= '<tr class="even">'; 
					else 
						$html.= '<tr class="odd">';
						
					
					if($selection){
						$identA = array();
						foreach($this->pk as $k){
							$identA []= $row[$k];
						}
						$ident = implode(':',$identA);
						$this->mOptions []= $ident;
						
						$html .= '<td><a href="?tbl='.$this->name.'&amp;select='.$ident . $this->mQsa . '" title="select this row">sel</a></td>';
						
					}
					foreach($row as $element){
						$html .= '<td>'.$element.'</td>';
					}
					$html .= "</tr>\n";
					
					$even = !$even;
					$i++;
			}
		return $html.'</table>';
	} 	
		
		
		
}
	
	
class AdvancedTable
{
	
	private $mName;
	private $mQuery;
	private $mKeys;
	private $mActions = array();
	private $mTable;
	private $mOrder;
	private $mXhtml = NULL;
	
	

	public function __construct($name, $query, $keys)
	{
		$this->mName = $name;
		$this->mQuery = $query;
		$this->mKeys = $keys;
		
	}
	
	public function addAction($name, $icon)
	{
		
	}
	
	public function getAction()
	{
		
	}
	
	private function orderBy($order)
	{
		
	}
	
	public function getHtml()
	{
		$sql = SqlQuery::getInstance();
		
		
		$tbl = $sql->simpleQuery($this->mQuery);
		
		
		print_r($_GET);
		
		return $this->makeTable($tbl);
	}
	
	public function makeTable($result_array)
	{
		
		$formX = new SimpleForm($this->mName.__METHOD__,'',NULL,NULL,'content','get');
		
		$html = '';
		
			//display selection option?
			if($this->mKeys != array())
				$selection = TRUE;	
			else
				$selection = FALSE;
		
					if(sizeof($result_array) == 0)
	 		return 'functions.array2html: array size is zero';
	 	
	 			$colnames = array_keys(reset($result_array));

				$html .= '<table class="standard" align="center">';
				if($selection)
					$html .= '<th style="standard">#</th>';
						
				foreach($colnames as $col){
					$order = 'ASC';
					if(isset($_GET['order_by']) && ($_GET['order_by'] == $col) && $_GET['dir'] == 'ASC')
						$order = 'DESC';
					$html .= '<th style="standard"><a href="?tbl='.$this->mName.'&amp;order_by='.$col.'&amp;dir='.$order.'" title="order by '.$col.' '.$order.'">'.$col.'</a></th>';
				}
				$even=false;
				$i = 1;
				foreach($result_array as $row){
					
					if($even)
						$html .= '<tr class="even">'; 
					else 
						$html.= '<tr class="odd">';
						
		
		$html .= '<td>';
			$formX->addElement('html'.$i,new HtmlElement($html));	
			$formX->addElement('sel'.$i,new Checkbox('','sel_mult[]','id_'.$i,FALSE,array('multiple')));
		$html = '</td>';
		
					
					if($selection){
						$identA = array();
						foreach($this->mKeys as $k){
							$identA []= $row[$k];
						}
						$ident = implode(':',$identA);
						$this->mOptions []= $ident;
						
						$html .= '<td><a href="?tbl='.$this->mName.'&amp;select='.$ident.'" title="select this row">sel</a></td>';
						
					}
					foreach($row as $element){
						$html .= '<td>'.$element.'</td>';
					}
					$html .= "</tr>\n";
					
		$formX->addElement('htmlB'.$i,new HtmlElement($html));
		$html = '';
		
					$even = !$even;
					$i++;
			}
		//return $html.'</table>';
		
		$formX->addElement('cloze',new HtmlElement('</table>'));
		
		$formX->addElement('action',new Select('With selected: ','action',array('0'=>'delete','1'=>'edit','2'=>'fuck')));
		$formX->addElement('submit',new Submit('submit','go'));
		
		return $formX->getHtml(71);
		
	}
	
}
	
	
?>
