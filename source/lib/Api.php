<?php
	class Api {
		public static $Request;
		public static $Response;
		public static $User;
		protected static $instance;
		
		public static function init() {
			if (!isset(self::$instance)) {
				$class = get_called_class();
				self::$instance = new $class;
			}
			
			/**/
			self::$Request = new stdClass;
			self::$Request->method = strtolower($_SERVER['REQUEST_METHOD']);
			self::$Request->data = self::fetchRequestData();
			
			$_uri = explode('?',$_SERVER['REQUEST_URI']);
			$_parts = explode('/',$_uri[0]);
			self::$Request->uri = $_uri[0];
					
			/**/
			self::$Response = new stdClass;
			if(self::$Request->data['callback']) {
				self::$Response->callback = self::$Request->data['callback'];
				unset(self::$Request->data['callback']);
			}
			self::$Response->data = NULL;
			self::$Response->meta = array( 'code' => 200 );
			
			/**/
			self::$User = new User();
			if(self::$Request->data['access_token']) {
				self::$User->set('access_token', self::$Request->data['access_token'])->fetch();
				unset(self::$Request->data['access_token']);
			}
			
			
			
			$uri_points = explode('/', self::$Request->uri);
			self::$Request->points = array();
			foreach($uri_points as $pos=>$point) if($point) {
				// point is a identifier
				if(is_numeric($point)) {
					array_push(self::$Request->points, array(
						'type' => 'id',
						'val' => (int)$point
					));
					continue;
				}
				
				// if the class exists, the point is a class
				if(class_exists(ucfirst($point), true)) {
					array_push(self::$Request->points, array(
						'type' => 'class',
						'val' => ucfirst($point)
					));
					continue;
				}
				
				// by default, the point is a class method
				array_push(self::$Request->points, array(
					'type' => 'method',
					'val' => self::$Request->method . '_' . $point
				));
			}
			
			return self::$instance;
		}
		
		public static function fetchRequestData() {
			$result = NULL;
			
			if(self::$Request->method == 'get') {
				$result = count($_GET) ? $_GET : NULL;
			} elseif(self::$Request->method == 'post') {
				$result = count($_POST) ? $_POST : NULL;
			} elseif(in_array(self::$Request->method, array("put","delete"))) {
				// reads data from stream php://input
				$data = file_get_contents("php://input");
				if($data){
					parse_str($data, $result);
					// prepare of input data
					foreach ($result as $key=>$value) {
						if(is_string($value)) $result[$key] = stripcslashes(urldecode($value));
					}
				}
			}
			
			return $result;
		}
		
		public static function issue() {
			$callback = self::$Response->callback;
			if (self::_isValidCallback($callback)) {
				unset(self::$Response->callback);
				header("Content-type: text/javascript; charset=utf-8");
				die($callback.'('.json_encode(self::$Response).')');
			} else {
				header("Content-Type: application/json; charset=utf-8");
				die(json_encode(self::$Response));
			}
		}
		
		private function _isValidCallback($subject) {
			if(!$subject) return false;
			
			$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
			$reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
									'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
									'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
									'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
									'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
									'private', 'public', 'yield', 'interface', 'package', 'protected',
									'static', 'null', 'true', 'false');
			  
			return preg_match($identifier_syntax, $subject) && !in_array(strtolower($subject), $reserved_words);
		}
		
		private function __construct() {}
		private function __clone() {}
	}
?>