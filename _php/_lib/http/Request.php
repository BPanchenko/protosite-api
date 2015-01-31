<?php
namespace http;

	class Request {
		protected static $_instance; 
		
		public $method;
		public $params;
		private $_parts;
		private $_headers;
		private $_parameters;
		
		static public function init() {
			if (is_object(self::$_instance))
				return self::$_instance;
			
			self::$_instance = new self;
			self::$_instance->method = strtoupper($_SERVER['REQUEST_METHOD']);
			self::$_instance->_parameters = new \base\Model(array(
				'__uri__' => new \base\CaseInsensitiveArray
			));
			
			return self::$_instance;
		}
		
		public function headers() {
			if(!is_null($this->_headers))
				return $this->_headers;
			
			var_dump(getallheaders());
			
			return NULL;
		}
		
		public function parameters($key) {
			
			return $this->_parameters->has($key) ? $this->_parameters->get($key) : $this->_parameters;
		}
		
		public function parts() {
			if(!is_null($this->_parts))
				return $this->_parts;
			
			$_res = array();
			$_uri = trim($_SERVER['REQUEST_URI'], '/');
			$_parts = explode('/', $_uri);
			
			foreach ($_parts as $i=>$_part) {
				$_value = $_part;
				$_part = new \stdClass;
				$_part->value = $_value;
				
				$_tmp = explode('-', $_value);
				$_tmp = array_map(ucfirst, $_tmp);
				$classname = implode('', $_tmp);
				
				if(class_exists($classname, true)) {
					$_part->type = 'class'; // if the class exists, the point is a class
					$_part->value = $classname;
					$_part->_value = $_value;
					
				} elseif(is_numeric($_value)) {
					$_part->type = 'int'; // point is a identifier
					$_part->value = (int)$_value;
					
				} elseif($_value === 'self') {
					$_part->type = 'self'; // checkpoint 'self' defines the authorized user ID
					
				} else {
					$_part->type = 'string';
				}
				
				$_parts[$i] = $_part;
			}
			
			return ($this->_parts = $_parts);
		}
		
		private function __construct() {}
		private function __clone() {}
		private function __wakeup() {}
	}
?>