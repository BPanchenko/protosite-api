<?php
namespace http;
require_once dirname(__FILE__) . '/RequestParametersModel.php';

enum ContentType: string {
    case Json = 'application/json';
    case FormUrlencodedData = 'application/x-www-form-urlencoded';
    case FormMultipartData = 'multipart/form-data';
}

class Request {
    protected static $_instance;

    public ContentType $content_type;
    public string $method;
    public $params;
    private $_parts;
    private $_headers;
    private RequestParametersModel $_parameters;
    private $_uri;

    static public function init() {
        if (is_object(self::$_instance))
            return self::$_instance;
        
        self::$_instance = new self;
        self::$_instance->method = strtoupper($_SERVER['REQUEST_METHOD']);
        self::$_instance->content_type = self::$_instance->_getContentType();
        self::$_instance->_headers = new \base\Model(getallheaders());
        self::$_instance->_parameters = new RequestParametersModel($_GET);

        return self::$_instance;
    }

	public function getBody(): array {
		$result = [];

		if($this->_isPostFormData()) {
			$result = count($_POST) ? $_POST : [];
		} else {
			$data = file_get_contents("php://input");
			if($this->content_type == ContentType::Json) {
				$result = json_decode($data, true);
			} elseif($data) {
				parse_str($data, $result);
			}
		}

		return $this->_parseBodyData($result);
	}

    public function headers() {
        return $this->_headers;
    }

    public function ip($check_proxy = true) {
        if ($check_proxy && isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if ($check_proxy && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function parameters(string $key = '', $value = NULL) {
        if(!is_null($value)) {
            $this->_parameters->set($this->_parameters->parse(array(
                $key => $value
            )));
        }
        return $this->_parameters->has($key) ? $this->_parameters->get($key) : $this->_parameters;
    }

    public function parts() {
        if(!is_null($this->_parts)) return $this->_parts;

        $_parts = explode('/', $this->uri());

        foreach ($_parts as $i=>$_part) {
            $_value = $_part;
            $_part = new \stdClass;
            $_part->value = $_value;

            $_tmp = explode('-', $_value);
            $_tmp = array_map('ucfirst', $_tmp);
            $classname = implode('', $_tmp);

            if(class_exists($classname, true)) {
                $_part->type = 'class'; // if the class exists, the point is a class
                $_part->value = $classname;
                $_part->_value = $_value;

            } elseif(is_numeric($_value)) {
                $_part->type = 'int'; // point is a identifier
                $_part->value = $_value < PHP_INT_MAX ? (int)$_value : $_value;

            } else {
                $_part->type = 'string';
            }

            $_parts[$i] = $_part;
        }

        return ($this->_parts = $_parts);
    }

    public function uri(){
        if (!isset($this->_uri)) {
            $_root = trim(API_ROOT, '/');
            $_uri = $_SERVER['REQUEST_URI'];
            if (strpos($_uri, '?') !== false) $_uri = substr($_uri, 0, strrpos($_uri, '?'));
            if (strpos($_uri, $_root) !== false) $_uri = substr($_uri, strpos($_uri, $_root) + strlen($_root));
            $_uri = trim($_uri, '/');
            $this->_uri = $_uri;
        }
        return $this->_uri;
    }

    private function _getContentType(): string {
        $result = strtolower($_SERVER['CONTENT_TYPE'] ?? ContentType::Json);
        foreach (ContentType::cases() as $value) {
            if (str_contains($result, (string) $value)) {
                $result = $value;
                break;
            }
        }
        return $result;
    }

    private function _isPostFormData(): bool {
        return $this->method == 'POST'
            && array_search(
                $this->content_type,
                ['multipart/form-data', 'application/x-www-form-urlencoded']
            ) !== false;
    }

	private function _parseBodyData(array $data): array {
		foreach ($data as $key => $value) {
			$_val = is_string($value) ? trim(stripcslashes(urldecode($value))) : $value;

			if(is_double($_val) && $_val < PHP_INT_MAX) {
				$_val = doubleval($_val);
			} elseif(is_numeric($_val) && $_val < PHP_INT_MAX) {
				$_val = strpos($_val, '.') != false ? floatval($_val) : intval($_val);
			}

			if($_val == 'true') $data[$key] = 1;
			elseif($_val == 'false') $data[$key] = 0;
			else $data[$key] = $_val;
		}
		return $data;
	}

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}
}
?>
