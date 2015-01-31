<?php
	class AppException extends Exception {
		public $type;
		public $data;
		public $error;
		
		private $_hash = array(
			'UnknownError' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Unknown Error",
					'ru' => "Неизвестная ошибка"
				)
			),
			'AccessDenied' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Access denied",
					'ru' => "Доступ запрещен"
				)
			),
			'AccessTokenInvalid' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Invalid a access token",
					'ru' => "Недействительный ключ доступа"
				)
			),
			'AccessTokenRequired' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Required a access token",
					'ru' => "Требуется ключ доступа"
				)
			),
			'AuthEmailRequired' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Required a e-mail",
					'ru' => "Пустой адрес e-mail"
				)
			),
			'AuthEmailNotFound' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "",
					'ru' => "Адрес e-mail не зарегистрирован"
				)
			),
			'AuthPasswordWrong' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "",
					'ru' => "Неверный пароль"
				)
			),
			'MethodNotExist' => array(
				'code' => 405,
				'error_message' => array(
					'en' => "Method is not supported",
					'ru' => "Метод не поддерживается"
				)
			),
			'RequestManyCheckpoints' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Many checkpoints in the request",
					'ru' => "Запрос содержит лишние контрольные точки"
				)
			),
			'UploadFileTypeWrong' => array(
				'code' => 400,
				'error_message' => array(
					'en' => "Not registered file type",
					'ru' => "Не зарегистрированный тип файла"
				)
			)
		);
		
		public function __construct($type = 'ApiUnknownError', $data=NULL) {
			parent::__construct($type);
			
			$this->type = $type;
			$this->data = $data;
			
			if(array_key_exists($this->type, $this->_hash))
				$this->error = $this->_hash[$this->type];
		}
		
		public function code() {
			return $this->error ? $this->error['code'] : 400;
		}
		
		public function message() {
			return $this->error ? $this->error['error_message'] : $this->_hash['UnknownError']['error_message'];
		}
		
		public function toArray() {
			$result = array();
			$result['code'] = 400;
			$result['error_type'] = $this->_type;
			
			if($this->_data)
				$result['error_data'] = $this->_data;
			
			if(array_key_exists($this->_type, $this->_hash))
				$result = array_merge($result, $this->_hash[$this->_type]);
			
			return $result;
		}
		
		public function __toString() {
			return $this->_type;
		}
	}
?>