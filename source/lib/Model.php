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
			
			if(is_array($data)) {
				$this->set($this->parse($data));
			} elseif(is_numeric($data)) {
				$this->set(array('id'=>$data));
			}
		}
		
		public function addTo($Collection) {
			if(!($Collection instanceof Collection)) {
				print "SAVE Model ".get_class($this);
				pa($this->toArray());
				throw new ErrorException("IncorrectCollection");
			}
			$Collection->add($this);
			$this->Collection = $Collection;
			return $Collection;
		}
		
		public function attached($Model, $info=array(), $debug=false){
			if($debug) {
				print "attached";
				pa($info);
			}
			$this->Owner = $Model;
			$this->Owner->linkTo($this, $info, $debug);
			return $this;
		}
		
		public function delete() {
			$this->_dbh->exec("update ".$this->_table.
								" set `is_del`=1".
								" where `".$this->idAttribute."`=".$this->id." limit 1;");
			return true;
		}
		
		public function fetch($fields=NULL){
			if($this->isNew()) return $this;
			
			$columns = "*";
			if(!empty($fields)) {
				$_fields = Data::str2array($fields);
				if(count($_fields)) {
					$fields = array();
					foreach($_fields as $field)
						if($this->Table->hasField($field)) array_push($fields, $field);
					if(count($fields)) {
						if(!in_array($this->idAttribute,$fields)) array_unshift($fields, $this->idAttribute);
						$columns = "`".implode("`,`",$fields)."`";
					}
				}
			}
			
			$query = "select ".$columns.
								" from ".$this->_table.
								" where `".$this->idAttribute."`=".$this->id." limit 1;";
			$sth = $this->_dbh->query($query);
			if(!$sth) print $query;
			if(!$sth->rowCount()) {
				var_dump($sth);
				print $query;
				throw new ErrorException("WrongModelID");
			}
			$this->set($this->parse($sth->fetch(PDO::FETCH_ASSOC)));
			
			return $this;
		}
		
		public function folder($_folder=''){
			if(!empty($_folder) && is_string($_folder)) {
				$this->set('folder',$_folder)->_folder = $_folder;
			}
			return $this->_folder;
		}
		
		/**
		 * Устанавливает или обновляет связь между сущностями,
		 * записывая идентификаторы связанных сущностей в $this->TableLinks.
		 * $obj - объект, который связывается с текущим.
		 *        Может быть как моделью, так и коллекцией.
		 *        Если передана коллекция, то каждая модель коллекции связывается с текущим объектом.
		 *        Свойство $obj->Owner устанавливается как ссылка на текущую модель.
		 * $info - дополнительные данные, записываемые в $this->TableLinks вместе с идентификаторами.
		 * 		   Если в $obj передана модель, то $info есть ассоциативный массив,
		 *         названия ключей которого совпадают с названиями столбцов данных в таблице $this->TableLinks.
		 * TODO:   Если была передана коллекция, то $info может быть простым массивом, 
		 *         каждым элементом которого будет являтся ассоциативный массив, 
		 *         хранящий данные для ссылки на соответствующую модель из коллекции.
		 *         Порядок следования моделей и данных должен совпадать.
		 */
		public function linkTo($obj, $info=array(), $debug=false) {
			if(empty($this->TableLinks)) return $this;
			
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
				if(is_array($info)) $link = array_merge($link, $info);
				if($debug) pa($link);
				$this->TableLinks->save($link);
			}
			return $this;
		}
		
		public function hasLink($model) {
			if(!($model instanceof Model)) return false;
			$query = "select `".$this->idAttribute."`".
								" from ".$this->TableLinks->name().
								" where `".$this->idAttribute."`=".$this->id." and ".$model->idAttribute."`=".$model->id." limit 1;";
			$sth = $this->_dbh->query($query);
			return (bool)$sth->rowCount();
		}
		
		public function getIdLinks($col){
			$_res = array();
			if($this->TableLinks->hasField($col)) {
				$sth = $this->_dbh->query("select `".$col."` from ".$this->TableLinks->name()." 
									where ".$this->idAttribute."=".$this->id);
				if($sth->rowCount()) {
					while($id = $sth->fetchColumn()) array_push($_res, $id);
				}
			}
			return $_res;
		}
		
		public function removeLink($col, $id) {
			if($col instanceof Model) {
				$obj = $col;
				if($this->TableLinks->hasField($obj->idAttribute)) {
					$this->_dbh->exec("delete from ".$this->TableLinks->name()." 
										where ".$obj->idAttribute."=".$obj->id." 
												and ".$this->idAttribute."=".$this->id);
					$obj->Owner = NULL;
				}
			} elseif(is_string($col) && is_numeric($id) && $id) {
				if($this->TableLinks->hasField($col)) {
					$this->_dbh->exec("delete from ".$this->TableLinks->name()." 
										where `".$col."`=".$id." and `".$this->idAttribute."`=".$this->id);
				}
			}
			return $this;
		}
		public function removeLinks($col){
			if($this->TableLinks->hasField($col)) {
				$this->_dbh->exec("delete from ".$this->TableLinks->name()." 
									where ".$col.">0 and ".$this->idAttribute."=".$this->id);
			}
			return $this;
		}
		
		public function clear($attr) {
			return $this->remove($attr);
		}
		
		public function remove($attr) {
			$attributes = Data::str2array($attr);
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
			return !$this->has($attr) || !(boolean)trim($this->get($attr)) || !(boolean)count($this->get($attr));
		}
		
		public function isNew(){
			return !(bool)$this->id;
		}
		
		public function isValid() {
			return true;
		}
		
		public function parse($data) {
//			if(empty($data['name_translit']) && $this->isNew() && 
//			$this->Table->hasField('name_translit') && !empty($data['name'])) {
//				$data['name_translit'] = $this->Table->uniqTranslit('name_translit', $data['name']);
//			}
			
			return $data;
		}
		
		public function save($data=array(), $debug=false) {
			if($data === true) {
				$data = array();
				$debug = true;
			}
			if(count($data)) $this->set($this->parse($data));
			
			if($this->Table->hasField('sort') && $this->isEmpty('sort')) {
				$this->set('sort', $this->Table->nextSort());
			}
			if($this->Table->hasField('sort') && $this->isEmpty('sort')) {
				$this->set('sort', $this->Table->nextSort());
			}
			if($this->Table->hasField('name_translit') && $this->isEmpty('name_translit') 
					&& $this->isNew() && !$this->isEmpty('name')) {
				$this->set('name_translit',  $this->Table->uniqTranslit('name_translit', $this->get('name')));
			}
			
			if($debug && $this instanceof Photo) {
				print "SAVE Model ".get_class($this);
				pa($this->toArray());
			}
			$id = $this->Table->save($this->toArray(), $debug);
			if($this->isNew() && !empty($id)) {
				$this->set($this->idAttribute,$id);
			}
			return $this;
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
		
		public function toAPI($fields=array()) {
			$array = $this->toArray($fields);
			return $array;
		}
		
		public function toArray($fields=NULL) {
			$result = array();
			
			if(!empty($fields) && is_string($fields)) $fields = Data::str2array($fields);
			
			if(count($fields)) {
				if(!in_array($this->idAttribute, $fields)) {
					array_unshift($fields, $this->idAttribute);
				}
				foreach($fields as $attr) {
					$attr = explode('.', $attr);
					if(array_key_exists($attr[0], $this->_attributes)) {
						if(!empty($attr[1]) && is_array($this->_attributes[$attr[0]])) 
							$result[$attr[0]][$attr[1]] = $this->_attributes[$attr[0]][$attr[1]];
						else 
							$result[$attr[0]] = $this->_attributes[$attr[0]];
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