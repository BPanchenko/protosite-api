<?php
namespace base;

	class Model implements \ArrayAccess {
		
		/* Data of model */
        protected $_attributes = array();
		protected $_changed = array();
		protected $_defaults = array();
		protected $_previous = array();
		
		public static $idAttribute = 'id';
		public $id;
		public $collection;
		
		
		function __construct($data=array(), $linked=NULL) {
			$this->_attributes = $this->_defaults;
			
			if(!is_null($linked) && $linked instanceof Collection)
				$this->collection = $linked;
			
			if($data instanceof stdClass)
				$data = json_decode(json_encode($data), true);
			
			if(is_array($data))
				$this->set($this->parse($data));
			elseif(is_numeric($data))
				$this->set(array('id'=>$data));
		}
		
		/** 
		 ============ */
		public function offsetExists($attr) {
			return $this->has($attr);
		}
		public function offsetGet($attr) {
			return $this->has($attr) ? $this->get($attr) : NULL;
		}

		public function offsetSet($attr, $value) {
			return $this->set($attr, $value);
		}

		public function offsetUnset($offset) {
			return $this->remove($attr, $value);
		}
		
		/**
		 * @method fetch()
		 */
		public function fetch() { return $this; }
		
		/**
		 * @method has()
		 */
		public function has($attr) {
			return array_key_exists($attr, $this->_attributes);
		}
		/**
		 * @method get()
		 */
        public function get($attr) {
			return $this->has($attr) ? $this->_attributes[$attr] : NULL;
        }
		/**
		 * @method parse()
		 */
		public function parse($data) { return $data; }
		/**
		 * @method set()
		 */
		public function set($attr, $value=NULL) {
			if(!$attr || empty($attr)) return $this;
			
			$attributes = array();
			is_array($attr) ? $attributes = $attr : $attributes[$attr] = $value;
			
			// предварительное приведение типов
			foreach($attributes as $key => $val) {
				if(is_numeric($val) && $val < PHP_INT_MAX) {
					$val = (int)$val;
				} elseif(is_string($val)) {
					$val = trim($val);
				}
				$attributes[$key] = $val;
			}
			
			foreach($attributes as $key => $val) {
				// Если атрибут был задан ранее и не равен новому значению, 
				// то он сохраняется в хеше измененных атрибутов, а также
				// прежнее значение сохраняется в $this->_previous.
				if(isset($this->_attributes[$key]) && $this->_attributes[$key] !== $val) {
					$this->_previous[$key] = $this->_attributes[$key];
					$this->_changed[$key] = $val;
				} elseif ($this->_attributes[$key] == $val) continue;
				
				if(is_array($this->_attributes[$key]) && is_array($val)) {
					// для сохранения массива данных используется слияние старых и новых данных 
					$this->_attributes[$key] = array_merge($this->_attributes[$key], $val);
				} else {
					$this->_attributes[$key] = $val;
				}
				
				if($key == static::$idAttribute || $key == 'id') {
					$this->id = $this->_attributes['id'] = $this->_attributes[static::$idAttribute] = $val;
				}
			}
			
			return $this;
		}
		/**
		 * @method remove()
		 */
		public function remove($attr) {
			$attributes = str2array($attr);
			foreach($attributes as $key) unset($this->_attributes[$key]);
			return $this;
		}
		
		/**
		 * @method isEmpty()
		 */
		public function isEmpty($attr=NULL){
			return !count($this->_attributes) || !$this->has($attr) || !(boolean)trim($this->get($attr)) || !(boolean)count($this->get($attr));
		}
		/**
		 * @method isNew()
		 */
		public function isNew(){ return !(bool)$this->id; }
		/**
		 * @method isValid()
		 */
		public function isValid() { return true; }
		
		/**
		 * @method toArray()
		 */
		public function toArray($options=array()) {
			if(!count($this->_attributes)) return NULL;
			if(!empty($options['fields'])) $fields = str2array($options['fields']);
			
			$result = array();
			if(count($fields)) {
				if(!in_array(static::$idAttribute, $fields)) {
					array_unshift($fields, static::$idAttribute);
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
			} else {
				$result = array();
				foreach($this->_attributes as $attr=>$value)
					if(is_object($value) && method_exists($value, 'toArray'))
						$result[$attr] = call_user_func(array($value, 'toArray'));
					else
						$result[$attr] = $value;
			}
			
			return $result;
        }
		
		public function toJSON($options=array()) {
			return json_encode($this->toArray($options));
		}
		
		/**
		 * Magic methods
		 */
		public function __get($attr) { return $this->get($attr); }
		public function __set($attr, $value) { return $this->set($attr, $value); }
		public function __isset($attr) { return $this->has($attr); }
		public function __toString() { return '{' . get_called_class() . ':' . json_encode($this->toArray()) .'}'; }
		public function __call($name, $arguments) { echo "Call undefined method '$name' " . implode(', ', $arguments). "\n"; }
		public static function __callStatic($name, $arguments) { echo "Call undefined static method '$name' " . implode(', ', $arguments). "\n"; }
	}
?>