<?php
namespace base;

	class Model extends Component implements \ArrayAccess {
		
        protected $_attributes = array();
		protected $_changed = array();
		protected $_defaults = array();
		protected $_previous = array();
		
		public static $idAttribute = 'id';
		public $id;
		public $collection;
		
		
		function __construct($data=array(), $parent=NULL) {
			
			$this->_attributes = $this->_defaults;
			
			if($data instanceof \stdClass)
				$data = json_decode(json_encode($data), true);
			
			if(is_array($data) && count($data))
				$this->set($this->parse($data));
			elseif(is_numeric($data))
				$this->set(array('id'=>$data));
			
			parent::__construct($data, $parent);
		}
		
		/** 
		 ============ */
		public function offsetExists($attr): bool {
			return $this->has($attr);
		}
		public function offsetGet($attr) {
			return $this->has($attr) ? $this->get($attr) : null;
		}

		public function offsetSet($attr, $value): self {
			return $this->set($attr, $value);
		}

		public function offsetUnset($offset) {
			return;
		}
		
		
		/**
		 * @method has()
		 */
		public function has(string $attr): bool {
			if($attr == 'id')
				return !!$this->id;
			else
				return array_key_exists($attr, $this->_attributes);
		}
		
		
		/**
		 * @method get()
		 */
		public function get(string $attr) {
			if($attr == 'id')
				return $this->id;
			if($this->has($attr))
				return $this->_attributes[$attr];
			return null;
        }
		
		
		/**
		 * @method parse()
		 */
		public function parse(array $data): array { return $data; }
		
		
		/**
		 * @method set()
		 */
		public function set($attr, $value = null): self {
			
			$attributes = array();
			is_array($attr) ? $attributes = $attr : $attributes[$attr] = $value;

            if(count($attributes)) {
                $_changed = array();
                $_previous = array();
            } else
                return $this;
			
			// предварительное приведение типов
			foreach($attributes as $key => $val) {
                if(is_double($val) && $val < PHP_INT_MAX) {
					$val = doubleval($val);
				} elseif(is_numeric($val) && $val < PHP_INT_MAX) {
                    $val = strpos($val, '.') != false ? floatval($val) : intval($val);
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
                    $_previous[$key] = $this->_attributes[$key];
					$_changed[$key] = $val;
				} elseif ($this->_attributes[$key] === $val) continue;
				
				if(is_array($this->_attributes[$key]) && is_array($val)) {
					// для сохранения массива данных используется слияние старых и новых данных 
					$this->_attributes[$key] = $val + $this->_attributes[$key];
				} else {
					$this->_attributes[$key] = $val;
				}
				
				if($key == static::$idAttribute || $key == 'id') {
					$this->id = $this->_attributes[static::$idAttribute] = $val;
				}

                $this->trigger(self::EVENT_CHANGE . ":$key", array(
                    "value" => $val,
                    "previous" => $_previous[$key]
                ));
			}

            $this->_changed = $_changed;
            $this->_previous = $_previous;

            if(count($_changed)) {
                $this->trigger(self::EVENT_CHANGE, array(
                    "changed" => $_changed,
                    "previous" => $_previous
                ));
            }
			
			return $this;
		}
		
		
		/**
		 * @method delete()
		 */
		public function delete(): bool {
			$res = $this->_table->update(array( "is_del" => 1 ), "`".static::$idAttribute."` = " . $this->id);
			return true;
		}


		/**
		 * @method destroy()
		 */
		public function destroy(): bool {
			// TODO: remove all records about the model in the database
			return true;
		}
		
		
		/**
		 * @method remove()
		 */
		public function remove($attr): self {
			$attributes = str2array($attr);
			foreach($attributes as $key)
				unset($this->_attributes[$key]);
			return $this;
		}
		
		/**
		 * @method isEmpty()
		 */
		public function isEmpty(string $attr = ''): bool {
			return !count($this->_attributes) || !$this->has($attr) || !(bool)trim($this->get($attr)) || !(bool)count($this->get($attr));
		}
		/**
		 * @method isNew()
		 */
		public function isNew(): bool { return !$this->id; }
		
		/**
		 * @method toArray()
		 */
		public function toArray(array $options=array()): array {
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

            if (!$this->isNew()) $result['id'] = $this->id;
			
			return $result;
        }
		
		public function toJSON(array $options=array()): string {
			return json_encode($this->toArray($options));
		}
		
		/**
		 * Magic methods
		 */
		public function __get($attr) { return $this->get($attr); }
		public function __set($attr, $value) { return $this->set($attr, $value); }
		public function __isset($attr) { return $this->has($attr); }
	}
?>