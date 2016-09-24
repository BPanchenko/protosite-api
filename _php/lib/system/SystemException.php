<?php

	class SystemException extends Exception {
		
		protected $message = 'Internal Server Error';
		protected $code = 500;
		protected $type = 'UnknowError';
		
		public function __construct($error_type) {
			
			$this->type = $error_type;
			// $this->code = ... by $error_type;
			// parent::__construct($this->getErrorMessage($this->code),this->code);
			// Logger::newMessage($this);
			
			parent::__construct($this->message, $this->code);
		}
		
		public function getType() {
			return $this->type;
		}
	}
?>