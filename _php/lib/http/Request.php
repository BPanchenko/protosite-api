<?php
namespace http;
require_once dirname(__FILE__) . '/RequestParametersModel.php';

class Request {
    protected static $_instance;

    public $method;
    public $params;
    private $_parts;
    private $_headers;
    private $_parameters;
    private $_uri;

    static public function init() {
        if (is_object(self::$_instance))
            return self::$_instance;

        self::$_instance = new self;
        self::$_instance->method = strtoupper($_SERVER['REQUEST_METHOD']);

        self::$_instance->_headers = new \base\Model(getallheaders());
        self::$_instance->_uri = $_SERVER['REQUEST_URI'];

        $request_parameters = array_merge($_GET, array(
            '__uri__' => array()
        ));
        self::$_instance->_parameters = new RequestParametersModel($request_parameters);

        return self::$_instance;
    }

    public function headers() {
        return $this->_headers;
    }

    public function ip($check_proxy = true) {
        if ($check_proxy && $_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if ($check_proxy && $_SERVER['HTTP_X_FORWARDED_FOR']) {
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
        if(!is_null($this->_parts))
            return $this->_parts;

        $_res = array();
        if(strrpos($_SERVER['REQUEST_URI'],'?'))
            $_uri = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'],'?'));
        else
            $_uri = $_SERVER['REQUEST_URI'];

        $_uri = trim($_uri, '/');
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
                $_part->value = $_value < PHP_INT_MAX ? (int)$_value : $_value;

            } elseif($_value === 'self') {
                $_part->type = 'self'; // checkpoint 'self' defines the authorized user ID

            } else {
                $_part->type = 'string';
            }

            $_parts[$i] = $_part;
        }

        return ($this->_parts = $_parts);
    }

    public function uri(){
        return self::$_instance->_uri;
    }

    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}
}
?>