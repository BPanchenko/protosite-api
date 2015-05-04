<?
namespace web;

use \base\CaseInsensitiveArray;

	class Curl {
		private $_cookies = array();
		private $_headers = array();
		private $_options = array();
		
		private $_success_function = NULL;
		private $_error_function = NULL;
		private $_complete_function = NULL;
		
		private $_url;
		private $_data;
		
		public $ch;
	
		public $error = false;
		public $error_code = 0;
		public $error_message = NULL;
	
		public $curl_error = false;
		public $curl_error_code = 0;
		public $curl_error_message = NULL;
	
		public $http_error = false;
		public $http_status_code = 0;
		public $http_error_message = NULL;
	
		public $request_headers = NULL;
		public $response_headers = NULL;
		public $response = NULL;
		public $raw_response = NULL;
	
		public function __construct($ch=NULL)
		{
			if (!extension_loaded('curl')) {
				throw new \ErrorException('cURL library is not loaded');
			}
	
			$this->ch = is_resource($ch) ? $ch : curl_init();
        	$this->setDefaultUserAgent()
				 ->setOpt(CURLINFO_HEADER_OUT, true)
				 ->setOpt(CURLOPT_HEADER, true)
				 ->setOpt(CURLOPT_RETURNTRANSFER, true);
		}
		
		public function get() {
			$this->setOpt(CURLOPT_URL, $this->buildURL($this->_url, $this->_data));
            $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
			$this->setOpt(CURLOPT_HTTPGET, true);
			
			return $this->exec();
		}

		public function post($url, $data = array()) {
			if (is_array($data))
				$this->setData($data);
	
			$this->setOpt(CURLOPT_URL, $this->buildURL($url));
			$this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
			$this->setOpt(CURLOPT_POST, true);
			$this->setOpt(CURLOPT_POSTFIELDS, $this->_data);
			
			return $this->exec();
		}
	
		public function options($url, $data = array())
		{
			$this->unsetHeader('Content-Length');
			$this->setOpt(CURLOPT_URL, $this->buildURL($url, $data));
			$this->setOpt(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
			return $this->exec();
		}
		
		public function exec() {
			if(!$this->raw_response)
				$this->raw_response = curl_exec($this->ch);
			
            $this->curl_error_code = curl_errno($this->ch);
			$this->curl_error_message = curl_error($this->ch);
			$this->curl_error = !($this->curl_error_code === 0);
			$this->http_status_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
			$this->http_error = in_array(floor($this->http_status_code / 100), array(4, 5));
			$this->error = $this->curl_error || $this->http_error;
			$this->error_code = $this->error ? ($this->curl_error ? $this->curl_error_code : $this->http_status_code) : 0;
	
			$this->request_headers = $this->_parseRequestHeaders(curl_getinfo($this->ch, CURLINFO_HEADER_OUT));
			list($this->response_headers, $this->response, $this->raw_response) = $this->_parseResponse($this->raw_response);
	
			$this->http_error_message = '';
			if ($this->error) {
				if (isset($this->response_headers['Status-Line'])) {
					$this->http_error_message = $this->response_headers['Status-Line'];
				}
			}
			$this->error_message = $this->curl_error ? $this->curl_error_message : $this->http_error_message;
			
			return $this->response;
		}

		public function setCookie($key, $value = NULL) {
			if(is_array($key)) $this->_cookies = array_merge($this->_cookies, $key);
			else $this->_cookies[$key] = $value;
			
			$this->setOpt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; ', PHP_QUERY_RFC3986));
			return $this;
		}
		
		public function setOpt($option, $value) {
			$required_options = array(
				CURLINFO_HEADER_OUT    => 'CURLINFO_HEADER_OUT',
				CURLOPT_HEADER         => 'CURLOPT_HEADER',
				CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER'
			);
			
			if (in_array($option, array_keys($required_options), true) && !($value === true))
				throw new \ErrorException(get_class() . ":" . $required_options[$option] . " is a required option.\n");
			
			$this->_options[$option] = $value;
			curl_setopt($this->ch, $option, $value);
			
			return $this;
		}
		public function getOpt($option) { return $this->_options[$option]; }
		
		public function setData($data) {
			$this->_data = $data;
			return $this;
		}

		public function setDefaultUserAgent() {
			$user_agent = 'InstaStat.CurlClass (http://instastat.pro)';
			$user_agent .= ' PHP/' . PHP_VERSION;
			$curl_version = curl_version();
			$user_agent .= ' curl/' . $curl_version['version'];
			$this->setUserAgent($user_agent);
			return $this;
		}
		
		public function setUserAgent($user_agent) {
			$this->setOpt(CURLOPT_USERAGENT, $user_agent);
			return $this;
		}
		
		public function setUrl($url) {
			$this->_url = $url;
			return $this;
		}
		
		public function buildURL($url, $data = array()) {
			return $url . (empty($data) ? '' : '?' . http_build_query($data));
		}
		
		private function _parseHeaders($raw_headers) {
			$raw_headers = preg_split('/\r\n/', $raw_headers, null, PREG_SPLIT_NO_EMPTY);
			$http_headers = new CaseInsensitiveArray();
	
			for ($i = 1; $i < count($raw_headers); $i++) {
				list($key, $value) = explode(':', $raw_headers[$i], 2);
				$key = trim($key);
				$value = trim($value);
				// Use isset() as array_key_exists() and ArrayAccess are not compatible.
				if (isset($http_headers[$key])) {
					$http_headers[$key] .= ',' . $value;
				} else {
					$http_headers[$key] = $value;
				}
			}
	
			return array(isset($raw_headers['0']) ? $raw_headers['0'] : '', $http_headers);
		}
	
		private function _parseRequestHeaders($raw_headers) {
			$request_headers = new CaseInsensitiveArray();
			list($first_line, $headers) = $this->_parseHeaders($raw_headers);
			$request_headers['Request-Line'] = $first_line;
			foreach ($headers as $key => $value) {
				$request_headers[$key] = $value;
			}
			return $request_headers;
		}

		private function _parseResponse($response) {
			$response_headers = '';
			$raw_response = $response;
			if (!(strpos($response, "\r\n\r\n") === false)) {
				$response_array = explode("\r\n\r\n", $response);
				for ($i = count($response_array) - 1; $i >= 0; $i--) {
					if (stripos($response_array[$i], 'HTTP/') === 0) {
						$response_header = $response_array[$i];
						$response = implode("\r\n\r\n", array_splice($response_array, $i + 1));
						break;
					}
				}
				$response_headers = explode("\r\n", $response_header);
				if (in_array('HTTP/1.1 100 Continue', $response_headers)) {
					list($response_header, $response) = explode("\r\n\r\n", $response, 2);
				}
				$response_headers = $this->_parseResponseHeaders($response_header);
				$raw_response = $response;
	
				if (isset($response_headers['Content-Type'])) {
					if (preg_match('/^application\/json/i', $response_headers['Content-Type'])) {
						$json_obj = json_decode($response, false);
						if (!is_null($json_obj)) {
							$response = $json_obj;
						}
					} elseif (preg_match('/^application\/atom\+xml/i', $response_headers['Content-Type']) ||
							  preg_match('/^application\/rss\+xml/i', $response_headers['Content-Type']) ||
							  preg_match('/^application\/xml/i', $response_headers['Content-Type']) ||
							  preg_match('/^text\/xml/i', $response_headers['Content-Type'])) {
						$xml_obj = @simplexml_load_string($response);
						if (!($xml_obj === false)) {
							$response = $xml_obj;
						}
					}
				}
			}
	
			return array($response_headers, $response, $raw_response);
		}

		private function _parseResponseHeaders($raw_headers) {
			$response_headers = new CaseInsensitiveArray();
			list($first_line, $headers) = $this->_parseHeaders($raw_headers);
			$response_headers['Status-Line'] = $first_line;
			foreach ($headers as $key => $value) {
				$response_headers[$key] = $value;
			}
			return $response_headers;
		}
		
		function __destruct() {
			curl_close($this->ch);
		}
	}
?>