<?php
	class ApiException extends Exception {
		private $_type;
		private $_data;
		private $_map = array(
			'ApiUnknownError' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Unknown Error API",
					'ru' => "Неизвестная ошибка API"
				)
			),
			'RequestManyCheckpoints' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Many checkpoints in the request",
					'ru' => "Запрос содержит лишние контрольные точки"
				)
			)
		);
		
		public function __construct($type = 'ApiUnknownError', $data=NULL) {
			$this->_type = $type;
			$this->_data = $data;
			parent::__construct($type);
		}
		
		public function toArray() {
			$result = array();
			$result['code'] = 400;
			$result['error_type'] = $this->_type;
			if($this->_data) {
				$result['error_data'] = $this->_data;
			}
			if(array_key_exists($this->_type, $this->_map)) {
				$result = array_merge($result, $this->_map[$this->_type]);
			}
			$result['request'] = Api::$Request;
			$result['user'] = Api::$User->toArray('id,access_token');
			
			return $result;
		}
		
		public function __toString() {
			return $this->_type;
		}
	}
?>