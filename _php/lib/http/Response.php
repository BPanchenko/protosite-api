<?php
namespace http;

	class Response extends \base\Component {
		
		const EVENT_BEFORE_SEND = 'beforeSend';
		const EVENT_AFTER_SEND = 'afterSend';
		const EVENT_AFTER_PREPARE = 'afterPrepare';
		
		/**
		 * @var string the response content. When [[data]] is not null, it will be converted into [[content]]
		 * according to [[format]] when the response is being sent out.
		 * @see data
		 */
		public $content;
		/**
		 * @var string the HTTP status description that comes together with the status code.
		 * @see httpStatuses
		 */
		public $statusText = 'OK';
				
		/**
		* @var array list of HTTP status codes and the corresponding texts
		*/
		public static $httpStatuses = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			118 => 'Connection timed out',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			208 => 'Already Reported',
			210 => 'Content Different',
			226 => 'IM Used',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',
			310 => 'Too many Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested range unsatisfiable',
			417 => 'Expectation failed',
			418 => 'I\'m a teapot',
			422 => 'Unprocessable entity',
			423 => 'Locked',
			424 => 'Method failure',
			425 => 'Unordered Collection',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			449 => 'Retry With',
			450 => 'Blocked by Windows Parental Controls',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway or Proxy Error',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported',
			507 => 'Insufficient storage',
			508 => 'Loop Detected',
			509 => 'Bandwidth Limit Exceeded',
			510 => 'Not Extended',
			511 => 'Network Authentication Required'
		);
		
		/**
		* @var integer the HTTP status code to send with the response.
		*/
		private $_statusCode = 200;
		/**
		* @var HeaderCollection
		*/
		private $_headers = [];
		
		private $_body;
		protected static $_instance;
		
		/**
		 * Initializes this component.
		 */
		public static function init() {
			if (is_object(self::$_instance))
				return self::$_instance;
				
			self::$_instance = new self;
			
			if (!isset(self::$_instance->version))
				if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
					self::$_instance->version = '1.0';
				} else {
					self::$_instance->version = '1.1';
				}
			
			if (!isset(self::$_instance->charset))
				self::$_instance->charset = 'UTF-8';
			
			self::$_instance->clear();
			
			return self::$_instance;
		}
		
		/**
		 * 
		 */
		public function get($name) {
			return $this->content->$name;
		}
		
		/**
		 * 
		 */
		public function set($name, $value) {
			$this->content->$name = $value;
			return $this;
		}
		
		/**
		 * 
		 */
		public function is_empty($name) {
			return empty($this->content->$name);
		}
		
		
		/**
		 * @return integer the HTTP status code to send with the response.
		 */
		public function getStatusCode() {
			return $this->_statusCode;
		}
		
		/**
		 * Sets the response status code.
		 * This method will set the corresponding status text if `$text` is null.
		 * @param integer $value the status code
		 * @param string $text the status text. If not set, it will be set automatically based on the status code.
		 */
		public function setStatusCode(int $value = 200, string $text = '') {
			$this->_statusCode = $value;
			if (is_array($this->content->meta)) $this->content->meta['code'] = $this->_statusCode;
			if (is_object($this->content->meta)) $this->content->meta->code = $this->_statusCode;
			
			if (!$text)
				$this->statusText = isset(static::$httpStatuses[$this->_statusCode]) ? static::$httpStatuses[$this->_statusCode] : '';
			else
				$this->statusText = $text;

			return $this;
		}
		
		
		/**
		 * Sends the response to the client.
		 */
		public function send() {
			if ($this->isSent) return $this;
		
			$this->trigger(self::EVENT_BEFORE_SEND);
			$this->prepare();
			$this->trigger(self::EVENT_AFTER_PREPARE);
			$this->sendHeaders();
			// $this->sendCookies();
			$this->sendContent();
			$this->trigger(self::EVENT_AFTER_SEND);
			$this->isSent = true;
			
			return $this;
		}
		
		/**
		 * Clears the headers, cookies, content, status code of the response.
		 */
		public function clear() {
			$this->_headers = [];
			$this->_cookies = NULL;
			$this->_statusCode = 200;
			$this->statusText = 'OK';
			
			$this->content = new \stdClass;
			$this->content->meta = new \stdClass;
			$this->content->data = NULL;
			
			$this->isSent = false;
			
			return $this;
		}


        public function setHeader($name, $value = '') {
            if(!$this->_headers[$name])
                $this->_headers[$name] = (array)$value;
            else
                array_push($this->_headers[$name], $value);
            return $this;
        }
		
		/**
		 * Sends the response headers to the client
		 */
		public function sendHeaders() {

			$statusCode = $this->getStatusCode();
			header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
			
			if (count($this->_headers))
				foreach ($this->_headers as $name => $values) {
					$name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
					// set replace for first occurrence of header but false afterwards to allow multiple
					$replace = true;
					foreach ($values as $value) {
						header("$name: $value", $replace);
						$replace = false;
					}
				}
			
			return $this;
		}
		
		/**
		 * Sends the cookies to the client.
		 */
		protected function sendCookies() {
			if ($this->_cookies === null)
				return;
			
			foreach ($this->getCookies() as $cookie) {
				$value = $cookie->value;
				setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
			}
			$this->getCookies()->removeAll();
			
			return $this;
		}
		
		
		/**
		 * Sends the response content to the client
		 */
		protected function sendContent() {
			if ($this->stream === NULL) {
				echo $this->content;
				return;
			}
			set_time_limit(0); // Reset time limit for big files
			$chunkSize = 8 * 1024 * 1024; // 8MB per chunk
			if (is_array($this->stream)) {
				list ($handle, $begin, $end) = $this->stream;
				fseek($handle, $begin);
				while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
					if ($pos + $chunkSize > $end) {
						$chunkSize = $end - $pos + 1;
					}
					echo fread($handle, $chunkSize);
					flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
				}
				fclose($handle);
			} else {
				while (!feof($this->stream)) {
					echo fread($this->stream, $chunkSize);
					flush();
				}
				fclose($this->stream);
			}
		}

		 public function getCookies() {
			if ($this->_cookies === NULL)
				$this->_cookies = $_COOKIE;
			
			return $this->_cookies;
		}
		/**
		 * @return boolean whether this response has a valid [[statusCode]].
		 */
		public function getIsInvalid() {
			return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
		}
		/**
		 * @return boolean whether this response is informational
		 */
		public function getIsInformational() {
			return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
		}
		/**
		 * @return boolean whether this response is successful
		 */
		public function getIsSuccessful() {
			return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
		}
		/**
		 * @return boolean whether this response is a redirection
		 */
		public function getIsRedirection() {
			return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
		}
		/**
		 * @return boolean whether this response indicates a client error
		 */
		public function getIsClientError() {
			return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
		}
		/**
		 * @return boolean whether this response indicates a server error
		 */
		public function getIsServerError() {
			return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
		}
		/**
		 * @return boolean whether this response is OK
		 */
		public function getIsOk() {
			return $this->getStatusCode() == 200;
		}
		/**
		 * @return boolean whether this response indicates the current request is forbidden
		 */
		public function getIsForbidden() {
			return $this->getStatusCode() == 403;
		}
		/**
		 * @return boolean whether this response indicates the currently requested resource is not found
		 */
		public function getIsNotFound() {
			return $this->getStatusCode() == 404;
		}
		/**
		 * @return boolean whether this response is empty
		 */
		public function getIsEmpty() {
			return in_array($this->getStatusCode(), array(201, 204, 304));
		}

        /**
         * Prepare content of response
         * @return $this
         */
		public function prepare() {
			$this->content = json_encode($this->content);
			return $this;
		}
	}
?>