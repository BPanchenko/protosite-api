<?php
	abstract class Collection {
		public $ModelClass = Model;
		public $models = array();
		public $length = 0;
		protected $_columns = array();
        protected $_defaults = array(
			"fetch" => array(
				'compact' => false,
				'search_fields' => array('name'),
				'order_key' => "id",
				'order_by' => "desc",
				'offset' => 0,
				'count' => 20
			)
		);
		protected $_folder = "temp";
		public $idAttribute;
		public $Table;	// Объект таблицы базы данных
		public $TableLinks;	// Объект таблицы связей сущности
		
		function __construct() {
			$this->_dbh = DB::dbh();
			// определение характеристик таблицы данных из Модели
			$Model = new $this->ModelClass();
			if(!empty($Model->Table)) {
				$this->Table = $Model->Table;
				$this->_defaults['fetch']['order_key'] = $this->idAttribute = $Model->Table->primaryKey();
				$this->folder($Model->folder());
			}
			if(!empty($Model->TableLinks)) $this->TableLinks = $Model->TableLinks;
			unset($Model);
		}
		
		public function add($data){
			if($data instanceof $this->ModelClass) {
				$model = $data;
			} elseif(is_numeric($data) || is_array($data)) {
				$model = new $this->ModelClass($data);
			}
			if(isset($model)) {
				array_push($this->models, $model);
				$this->length++;
			}
			return $this;
		}
		
		public function create($data){
			try {
				$new_model = new $this->ModelClass($data);
			} catch(ErrorException $e) {
				if($e->getMessage() == 'WrongModelID') return NULL;
			}
			return $new_model;
		}
		
		public function fetch($options) {
			$options = $this->_prepareFetchOptions($options);
			
			$rows = array();
			$ids = array();
			if(count($options['ids'])) $ids = $options['ids'];
			else {
				// OFFSET & LIMIT
				$offset = " OFFSET ".$options['offset'];
				$limit = " LIMIT ".$options['count'];
				unset($options['offset'], $options['count']);
				
				// ORDER
				$_aOrder[] = "`".$options['order_key']."` ".$options['order_by'];
				if($options['order_key'] != $this->idAttribute && $options['order_key'] != 'id') $_aOrder[] = "`".$this->idAttribute."` desc";
				if(count($_aOrder)) $order = " order by ".implode(", ",$_aOrder);
				unset($options['order_key'],$options['order_by']);
				
				// WHERE
				$exprs = array();
				if($this->Table->hasField('is_del')) array_push($exprs, "`is_del`=".($options['is_del'] ? $options['is_del'] : 0));
				if($options['max_id']) {
					array_push($exprs, "`".$this->idAttribute."`<=".(int)$options['max_id']);
					unset($options['max_id']);
				}
				if($options['min_id']) {
					array_push($exprs, "`".$this->idAttribute."`>=".(int)$options['min_id']);
					unset($options['min_id']);
				}
				if($options['q']) {
					if(is_numeric($options['q'])) array_push($exprs, "`".$this->idAttribute."`=".(int)$options['q']);
					else {
						array_push($exprs, "(`".implode("` like '%".$options['q']."%' or `", $options['search_fields'])."` like '%".$options['q']."%')");
					}
				}
				unset($options['q'], $options['search_fields']);
				
				if(count($options)) foreach ($options as $k=>$v) if($this->Table->hasField($k)) {
					if(is_string($v)) array_push($exprs,"`".$k."` LIKE '%".$v."%'");
					else array_push($exprs,"`".$k."`=".$v);
				}
				if(count($exprs)) $where = "WHERE ".implode(" and ", $exprs);
				
				// QUERY
				if((boolean)$options['compact']) {
					$query = "select * from ".$this->Table->name()." ".$where.$order.$limit.$offset;
					$sth = $this->_dbh->query($query);
					if($sth->rowCount()) while($row = $sth->fetch()) {
						$this->add($row);
					}
				} else {
					$query = "select `".$this->idAttribute."` from ".$this->Table->name()." ".$where.$order.$limit.$offset;
					$sth = $this->_dbh->query($query);
					if($sth->rowCount()) while($id = $sth->fetchColumn()) array_push($ids, $id);
				}
			}
			
			if(count($ids)) foreach($ids as $id) {
				$model = $this->create(array('id' => $id));
				if(!empty($model) && $model->isValid()) {
					$model->fetch($options['fields'])->addTo($this);
				}
			}
			
			return $this;
		}
		
		public function folder($_folder=''){
			if(!empty($_folder) && is_string($_folder)) {
				$this->_folder = $_folder;
			}
			return $this->_folder;
		}
		
		public function get($id) {
			if($id && is_numeric($id)) 
				foreach($this->models as $model) 
					if($model->id == $id) return $model;
			return NULL;
		}
		
		public function toAPI($fields=array()) {
			$array = $this->toArray($fields);
			return $array;
		}
		
		public function toArray($fields=NULL) {
			$array = array();
			if(count($this->models)) foreach($this->models as $model) {
				array_push($array,$model->toArray($fields));
			}
			return $array;
		}
		
		protected function _prepareFetchOptions($options=array()){
			$opt = array();
			if(!empty($options['order'])) {
				$opt['order_by'] = strpos($options['order'],"-")===false ? 'asc' : 'desc';
				$opt['order_key'] = trim($options['order'],"-");
				if(!$this->Table->hasField($opt['order_key'])) {
					$opt['order_key'] = $this->_defaults['fetch']['order_key'];
				}
			}
			
			$system_params = array('ids','fields','compact','q','max_id','min_id','count','offset');
			if(count($options)) foreach($options as $key => $value){
				// передается массив идентификаторов или перечень полей выборки
				if($key == 'ids' && !is_array($value)) {
					$opt[$key] = Data::str2array($value);
				}
				// необходимо сделать выборку по одному идентификатору
				if($key == 'id') {
					$opt['ids'][0] = (int)$value;
					break;
				}
				// сохранение только тех параметров, которые есть в полях таблицы
				if($this->Table->hasField($key)) {
					// приведение типа данных параметра к типу столбца таблицы
					$opt[$key] = $this->Table->typeField($key) == 'number' ? (int)$value : (string)$value;
				}
				// или в массиве системных параметров
				if(in_array($key, $system_params) && !array_key_exists($key,$opt)) {
					$opt[$key] = $value;
				}
			}
			$opt = array_merge($this->_defaults['fetch'], $opt);
			
			return $opt;
		}
		
		public function where($attributes, $fields=NULL){
			if(!is_array($attributes) || empty($attributes)) return $this->toArray();
			
			$_result = array();
			foreach($attributes as $attr=>$value) {
				foreach($this->models as $model) {
					if($model->get($attr) === $value) {
						array_push($_result, $model->toArray($fields));
						continue 2;
					}
				}
			}
			return $_result;
		}
	}
?>