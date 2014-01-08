<?php
	abstract class Model {
        protected $_attributes = array();	// Массив данных модели
		protected $_changed = array();	// Хеш измененных атрибутов модели
		protected $_dbh;
		protected $_folder = "temp";
        protected $_table = "";			// Основная таблица данных модели
		public $idAttribute;
		public $Collection = NULL;
		public $Owner = NULL; // Модель, к которой установленно отношение текущей модели с помощью методов attached и linkTo
		public $Table;	// Объект таблицы базы данных
		public $TableLinks;	// Объект таблицы связей сущности
		public $id;
		
		function __construct($data) {
			$this->_dbh = DB::dbh();
			if(!empty($this->_table)) {
				$this->Table = new Table($this->_table);
				$this->idAttribute = $this->Table->primaryKey();
			}
			if(!empty($this->_table_links)) {
				$this->TableLinks = new Table($this->_table_links);
			}
			if(ctype_digit($data)) {
				$this->set(array('id'=>$data));
			} else {
				$this->set($data);
			}
		}
		
		public function addTo($Collection) {
			if(!($Collection instanceof Collection)) throw new ErrorException("IncorrectCollection");
			$Collection->add($this);
			$this->Collection = $Collection;
			return $Collection;
		}
		
		public function attached($Model){
			$Model->linkTo($this);
			$this->Owner = $Model;
			return $Model;
		}
		
		public function delete() {
			$this->_dbh->exec("update ".$this->_table.
								" set `is_del`=1".
								" where `".$this->idAttribute."`=".$this->id." limit 1;");
			return true;
		}
		
		public function fetch($fields=NULL){
			if($this->isNew()) return $this;
			
			if(!empty($fields)) {
				$fields = Data::str2array($fields);
				if(!in_array($this->idAttribute,$fields)) array_unshift($fields, $this->idAttribute);
				$columns = "`".implode("`,`",$fields)."`";
			} else $columns = "*";
			
			$sth = $this->_dbh->query("select *".
								" from ".$this->_table.
								" where `".$this->idAttribute."`=".$this->id." limit 1;");
			
			if(!$sth->rowCount()) throw new ErrorException("WrongModelID");
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			$this->set($this->parse($sth->fetch()));
			
			return $this;
		}
		
		public function folder($_folder=''){
			if(!empty($_folder) && is_string($_folder)) {
				$this->set('folder',$_folder)->_folder = $_folder;
			}
			return $this->_folder;
		}
		
		public function linkTo($obj) {
			if(!empty($this->TableLinks)) {
				if($obj instanceof Model && !$obj->isNew()) {
					$data[0][$this->idAttribute] = $this->id;
					$data[0][$obj->idAttribute] = $obj->id;
					$obj->Owner = $this;
				}
				if($obj instanceof Collection && $obj->length) {
					foreach($obj->models as $key => $model) {
						$data[$key][$this->idAttribute] = $this->id;
						$data[$key][$model->idAttribute] = $model->id;
						$model->Owner = $this;
					}
				}
				if(count($data)) foreach($data as $link) {
					$this->TableLinks->save($link);
				}
			}
			return $this;
		}
		
		public function clear($attr) {
			$attributes = array();
			is_array($attr) ? $attributes = $attr : $attributes[0] = $attr;
			foreach($attributes as $key) unset($this->_attributes[$key]);
			return $this;
		}
		
        public function get($attr) {
			return $this->has($attr) ? $this->_attributes[$attr] : NULL;
        }
		
		public function has($attr) {
			return array_key_exists($attr, $this->_attributes);// && $this->_attributes[$attr] != null);
		}
		
		public function isEmpty($attr){
			return !$this->has($attr) || !(boolean)trim($this->get($attr));
		}
		
		public function isNew(){
			return !(bool)$this->id;
		}
		
		public function isValid() {
			return true;
		}
		
		public function parse($data) {
			if($this->Table->hasField('name_translit') && empty($data['name_translit']) && !empty($data['name'])) {
				$data['name_translit'] = $this->Table->uniqTranslit('name_translit', $data['name']);
			}
			if($this->Table->hasField('sort') && empty($data['sort'])) {
				$data['sort'] = $this->Table->nextSort();
			}

			return $data;
		}
		
		public function set($attr, $value=NULL) {
			if(!$attr || empty($attr)) return $this;
			
			$attributes = array();
			if($value instanceof Model || $value instanceof Collection) {
				$value = call_user_func(array($value,"toArray"));
			}
			is_array($attr) ? $attributes = $attr : $attributes[$attr] = $value;
			
			foreach($attributes as $key => $val) {
				if(is_numeric($val) && $val < PHP_INT_MAX) {
					$val = (int)$val;
				} elseif(is_string($val)) {
					$val = trim($val);
				}
				if($key == $this->idAttribute || $key == 'id') {
					$this->id = $this->_attributes['id'] = $this->_attributes[$this->idAttribute] = $val;
				}
				
				// если атрибут был задан ранее и не равен новому значению, то он сохраняется в хеше измененных атрибутов
				if(isset($this->_attributes[$key]) && $this->_attributes[$key] !== $val) $this->_changed[$key] = $val;
				
				if(is_array($this->_attributes[$key]) && is_array($val)) {
					// для сохранения массива данных используется слияние старых и новых данных 
					$this->_attributes[$key] = array_merge_recursive($this->_attributes[$key], $val);
				} else {
					$this->_attributes[$key] = $val;
				}
			}
			
			return $this;
		}
		
		public function save($data=array()) {
			if(count($data)) $this->set($this->parse($data));
			$id = $this->Table->save($this->toArray());
			if($this->isNew() && !empty($id)) {
				$this->set($this->idAttribute,$id);
			}
			return $this;
		}
		
		public function toArray($fields=NULL) {
			$result = array();
			
			if(!empty($fields) && is_string($fields)) $fields = Data::str2array($fields);
			
			if(count($fields)) {
				if(!in_array($this->idAttribute, $fields)) array_unshift($fields, $this->idAttribute);
				foreach($fields as $attr) {
					if(array_key_exists($attr, $this->_attributes)) {
						$result[$attr] = $this->_attributes[$attr];
					}
				}
//			} else $result = $this->_attributes;
			} else {
				foreach($this->_attributes as $attr=>$value) {
					if(!in_array($attr, array('id'))) {
						$result[$attr] = $value;
					}
				}
			}
			return $result;
        }
		
		public function __set($attr, $value) { return $this->set($attr, $value); }
		public function __isset($attr) { return $this->has($attr); }
	}
?>