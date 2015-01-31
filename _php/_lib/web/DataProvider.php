<?php
namespace Data;
require_once dirname(__FILE__) . '/Curl.php';

	/**
	 * Поставщик данных из источников: WebAPI, MySQL и SQLite.
	 * Интерфейс класса представляет собой единый конструктор запросов.
	 */
	class Provider {
		public $resource;
		static $HOST = '';
		
		private $_type; // URL or SQL
		private $_type_resource; // curl, curl_multi or PDO
		
		private $_counter = -1;
		private $_requests = array();
		private $_success_handlers = array();
		private $_success_posteds = array();
		private $_error_handlers = array();
		private $_error_posteds = array();
		
		private $_clean_request = array(
			'select' => array(),
			'from' => array(),
			'where' => '',
			'order' => '',
			'offset' => 0,
			'limit' => 0,
			'params' => array(),
		);
		private $_request = array();
		
		// @param $resource - resource of PDO object or empty for API requests
		function __construct($resource = NULL) {
			if(!$resource) {
				$this->_type = 'URL';
				$this->_type_resource = 'Data\Curl';
				
			} elseif (is_object($resource)) {
				$this->_type_resource = get_class($resource);
				if(in_array($this->_type_resource, array('PDO'))) {
					$this->_type = 'SQL';
					$this->resource = $resource;
				} else
					throw new \ErrorException(get_class() . ": Object '" . $this->_type_resource . "' is not supported.\n");
				
			} else {
				throw new \ErrorException(get_class() . ": Resource is not defined.\n");
			}
		}
		
		public function createCommand() {
			
			if(!empty($this->_request)) array_push($this->_requests, $this->_request);
			$this->_request = $this->_clean_request;
			$this->_counter++;
			
			return $this;
		}
		
		public function onComplete($handler, $posted_data=NULL) {
			$this->_complete_handlers[$this->_counter] = $handler;
			$this->_complete_posteds[$this->_counter] = $posted_data;
			
			return $this;
		}
		public function onSuccess($handler, $posted_data=NULL) {
			$this->_success_handlers[$this->_counter] = $handler;
			$this->_success_posteds[$this->_counter] = $posted_data;
			
			return $this;
		}
		public function onError($handler, $posted_data=NULL) {
			$this->_error_handlers[$this->_counter] = $handler;
			$this->_error_posteds[$this->_counter] = $posted_data;
			
			return $this;
		}
		
		
		public function exec() {
			if(!empty($this->_request)) array_push($this->_requests, $this->_request);
			if(!count($this->_requests)) return $this->reset();
			
			switch($this->_type) {
				case 'URL':
					if(count($this->_requests) == 1) {
						$Curl = $this->_initCurl();
						$result = $Curl->setUrl($this->_buildQuery($this->_requests[0]))
									   ->setData($this->_buildUrlData($this->_requests[0]))
									   ->get();
						
						// call onSuccess or onError
						$callback = $Curl->error ? $this->_error_handlers[0] : $this->_success_handlers[0];
						$posted_data = $Curl->error ? $this->_error_posteds[0] : $this->_success_posteds[0];
						$this->_call($callback, $Curl, $posted_data);
						
						// call onComplete
						$callback = $this->_complete_handlers[0];
						$posted_data = $this->_complete_posteds[0];
						$this->_call($callback, $Curl, $posted_data);
						
					} else {
						$mc = curl_multi_init();
						$curls = array();
						foreach($this->_requests as $i => $r) {
							$curls[$i] = $Curl = $this->_initCurl();
							$Curl->buildURL($this->_buildQuery($r), $this->_buildUrlData($r));
							$Curl->setOpt(CURLOPT_URL, $Curl->buildURL($this->_buildQuery($r), $this->_buildUrlData($r)))
								 ->setOpt(CURLOPT_CUSTOMREQUEST, 'GET')
								 ->setopt(CURLOPT_HTTPGET, true);
							
							$curlm_error_code = curl_multi_add_handle($mc, $Curl->ch);
							if ($curlm_error_code !== CURLM_OK)
								throw new \ErrorException('cURL multi add handle error: ' . curl_multi_strerror($curlm_error_code));
						}
						
						do {
							$status = curl_multi_exec($mc, $active);
						} while ($status === CURLM_CALL_MULTI_PERFORM || $active);
						
						while (!($info_array = curl_multi_info_read($mc)) === false) {
							if (!($info_array['msg'] === CURLMSG_DONE)) {
								continue;
							}
							foreach ($curls as $Curl) {
								if ($Curl->ch === $info_array['handle']) {
									$Curl->curl_error_code = $info_array['result'];
									break;
								}
							}
						}

						foreach ($curls as $i => $Curl) {
							$Curl->raw_response = curl_multi_getcontent($Curl->ch);
							$result = $Curl->exec();
							
							// call onSuccess or onError
							$callback = $Curl->error ? $this->_error_handlers[$i] : $this->_success_handlers[$i];
							$posted_data = $Curl->error ? $this->_error_posteds[$i] : $this->_success_posteds[$i];
							$this->_call($callback, $Curl, $posted_data);
							
							// call onComplete
							$callback = $this->_complete_handlers[$i];
							$posted_data = $this->_complete_posteds[$i];
							$this->_call($callback, $Curl, $posted_data);
						}
					}
					
				break;
				
				case 'SQL':
					foreach($this->_requests as $i=>$request) {
						$sth = $this->resource->query($this->_buildQuery($request));
						
						$callback = $this->_success_handlers[$i];
						$result = $sth->fetchAll(\PDO::FETCH_ASSOC);
						$posted_data = $this->_success_posteds[$i];
						
						$this->_call($callback, $result, $posted_data);
					}
				break;
			}
			$this->reset();
			
			return $result;
		}
		
		public function reset() {
			$this->_counter = -1;
			$this->_requests = array();
			$this->_success_handlers = array();
			$this->_error_handlers = array();
			$this->_hn_params = array();
			$this->_request = array();
			return $this;
		}
		
		public function where($conditions='', $params=array(), $return_result=false) {
			if(empty($conditions) && count($params))
				return $return_result ? '' : $this;
			
			$conditions = $this->_prepareConditions($conditions, $params);
			
			if(strpos($conditions, ':') !== false)
				throw new \ErrorException(get_class() . ": The number of parameters is not suitable to conditions.\n");
			
			if($return_result)
				return $conditions;
			else {
				$this->_request['where'] = $conditions;
				return $this;
			}
		}
		
		/**
		 * Insert
		 */
		public function insert($table, $columns) {
			switch($this->_type) {
				case 'URL':
					
				break;
				
				case 'SQL':
					$keys = array_keys($columns);
					$inserted_parts = array();
					foreach($keys as $key) array_push($inserted_parts, "`".$key."`=:".$key);
					
					$query = "insert ".$this->_name." (`".implode("`, `",$keys)."`)
									values (:".implode(", :",$keys).")
									on duplicate key update ".implode(", ",$inserted_parts).";";
					
					$this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					$this->resource->prepare($query)->execute($columns);
					
					return $this->resource->lastInsertId();
					
				break;
			}
		}
		
		/**
		 * Update
		 */
		public function update($table, $columns, $conditions='', $params=array()) {
			switch($this->_type) {
				case 'URL':
					
				break;
				
				case 'SQL':
					$keys = array_keys($columns);
					$update_parts = array();
					foreach($keys as $key) array_push($update_parts, "`".$key."`=:".$key);
					
					$query = "update ".$table." SET " . implode(", ",$update_parts) . " WHERE " . $conditions . ";";
					$this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					$this->resource->prepare($query)->execute(array_merge($columns, $params));
					
					return $this->resource->lastInsertId();
					
				break;
			}
		}
		
		
		/**
		 * Объединение частей запроса в строку запроса к базе данных или к интерфейсу API.
		 */
		private function _buildQuery($type='', $parts=NULL) {
			if(empty($parts) && is_array($type) && count($type)) {
				$parts = $type;
				$type = $this->_type;
			}
			if(empty($type)) $type = $this->_type;
			if(empty($parts)) $parts = $this->_request;
			
			$request = '';
			if($type == 'URL') {
				
				$url = implode('/', $parts['from']);
				if(strpos($request, 'http://') === false && strpos($url, 'https://') === false) {
					if (!self::$HOST) throw new \ErrorException(get_class() . ": Host name API is not defined.\n");
					$url = self::$HOST . '/' . $url;
				}
				$request = $url;
				
			} elseif ($type == 'SQL') {
				
				if(count($parts['select'])) $query = "SELECT `" . implode('`,`', $parts['select']) . "`";
				else $query = "SELECT *";
				
				$query .= " FROM `" . implode('`.`', $parts['from']) . "`";
				
				if(!empty($parts['where'])) 
					$query .= " WHERE " . $parts['where'];
				
				if(!empty($parts['order'])) {
					$order = strtolower($parts['order']);
					$is_desc = strpos($order, 'desc') !== false || strpos($order, '-') !== false;
					$order = '`' . str_replace(array('`', 'desc', 'asc', ' '), '', $order) . '`';
					$query .= ' ORDER BY ' . $order . ($is_desc ? ' DESC' : ' ASC');
				}
				
				if(!empty($parts['offset'])) 
					$query .= " OFFSET " . $parts['offset'];
				
				if(!empty($parts['limit'])) 
					$query .= " LIMIT " . $parts['limit'];
				
				$request = $query .= ';';
			}
			
			return $request;
		}
		
		/**
		 * Массив параметров URL-запроса
		 */
		private function _buildUrlData($parts=NULL) {
			$type  = $this->_type;
			$parts = $parts ? $parts : $this->_request;
			$data  = array();
				
			if(count($parts['select']))
				$data['fields'] = implode(',', $parts['select']);
			
			// TODO: не приравнивать "or" к "and".
			//		(необходима реализация протокола передачи параметра 'where' в API)
			if(!empty($parts['where'])) {
				// преобразование строки в массив, в качестве разделителя строка "and"
				$_wheres = explode("and", $parts['where']);
				// при нахождении в элементах массива строки "or"
				// массив модифицируется с добавлением элементов,
				// которые были получены разбиением элемента по разделителю "or".
				$_i = 0;
				do {
					if(strpos($_wheres[$_i], ' or ') !== false) {
						$_wheres1 = array_slice($_wheres, 0, $_i);
						$_wheres2 = explode("or", $parts['where']);
						$_wheres3 = array_slice($_wheres, $_i);
						$_wheres = array_merge($_wheres1, $_wheres2, $_wheres3);
					}
					$_i++;
				} while($_i < count($_wheres));
				// Параметры поиска передаются как отдельные параметры GET-строки
				foreach($_wheres as $param) {
					$_param = explode('=', str_replace(array('`',"'",'"',' '), '', $param));
					$data[$_param[0]] = $_param[1];
				}
			}
				
			if(!empty($parts['order'])) {
				$order = strtolower($parts['order']);
				$is_desc = strpos($order, 'desc') !== false || strpos($order, '-') !== false;
				$data['order'] = str_replace(array('`', 'desc', 'asc', ' '), '', $order);
				if($is_desc) $data['order'] = '-' . $data['order'];
			}
			
			if(count($parts['params']))
				$data = array_merge($data, $parts['params']);
			if($data['offset'])
				$data['offset'] = $parts['offset'];
			if($data['count'])
				$data['count'] = $parts['limit'];
				
			return $data;
		}
		
		
		/**
		 * Преобразование строки условия запроса
		 */
		private function _prepareConditions($conditions, $params = array()) {
			$conditions = strtolower($conditions);
			
			if(count($params)) foreach($params as $key=>$val) {
				if(is_numeric($val) && $val < PHP_INT_MAX)
					$val = (int)$val;
				elseif(is_string($val))
					$val = "'" .mysql_escape_string(trim($val)) . "'";
				else continue;
				
				$conditions = str_replace(':'.$key, $val, $conditions);
			}
			
			return $conditions;
		}

		/**
		 * Массив параметров URL-запроса
		 */
		private function _call($function) {
			if (is_callable($function)) {
				$args = func_get_args();
				array_shift($args);
				call_user_func_array($function, $args);
			}
			return $this;
		}
		
		private function _initCurl() {
			$Curl = new Curl();
			
			$Curl->setUserAgent('Opera/9.80 (X11; Linux x86_64; U; de) Presto/2.7.62 Version/11.01')
				 ->setOpt(CURLOPT_FAILONERROR, false)
				 ->setOpt(CURLOPT_FOLLOWLOCATION, true)
				 ->setOpt(CURLOPT_MAXREDIRS, 3)
				 ->setOpt(CURLOPT_TIMEOUT, 30);
	
			return $Curl;
		}
		
		private function _isValidRequest($parts) {
			return is_array($parts['from']) && !!count($parts['from']);
		}
		
		
		/**
		 * Magic methods
		 */
		public function __call($method, $args) {
			
			if(!array_key_exists($method, $this->_request))
				throw new \ErrorException("Call undefined method '$method' " . implode(', ', $args). "\n");
			
			if(in_array($method, array('select', 'from')) && is_string($args[0])) {
				$val = str_replace(array('`',' ','*'), '', $args[0]);
				$val = str_replace(array('.','/'), ',', trim($val, '/'));
				$val = str2array($val);
			} elseif(in_array($method, array('limit', 'offset'))) {
				$val = (int)$args[0];
			} elseif(in_array($method, array('params')) && is_array($args[0])) {
				$val = $args[0];
			} else $val = $args[0];
			
			$this->_request[$method] = $val;
			
			return $this;
		}
		
		public static function __callStatic($name, $arguments) { echo "Call undefined static method '$name' " . implode(', ', $arguments). "\n"; }
		public function __toString() { return '{' . get_called_class() . ': "' . implode('","', $this->_requests) .'"}'; }
	}
?>