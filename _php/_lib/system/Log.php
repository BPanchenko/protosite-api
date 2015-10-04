<?php
namespace system;

    class Log {

        private  $_dir;
        private  $_filename;
        private  $_fp;
        private  $_path;

        function __construct($filename) {
            $this->_dir = str_replace('.:', '', get_include_path()) . '/logs/';

            if(!$this->filename($filename))
                $this->filename($_SERVER['HTTP_HOST'] . '--' . date('Ymd') . '.log');

            $this->_fp = fopen($this->_path, 'a+');
        }

        public function dir($string) {
            if($string) {
                $this->_dir = $string;
                $this->_path = $this->_dir . $this->_filename;
            }
            return $this->_dir;
        }

        public function filename($string) {
            if($string) {
                $this->_filename = $string;
                $this->_path = $this->_dir . $this->_filename;
            }
            return $this->_filename;
        }

        public function read($is_string = false, $length = 200) {
            if(is_numeric($is_string)) {
                $length = (int)$is_string;
                $is_string = false;
            }

            if($is_string)
                $result = fread($this->_fp, filesize($this->_path));
            else {
                $result = new \base\CaseInsensitiveArray;
                while (($line = fgets($this->_fp, 1024)) !== false) {
                    $result->push($line);
                }
                array_slice($result, $result->count() - $length, $length);
            }

            return $result;
        }

        public function write($str) {
            fwrite($this->_fp, microtime(true) . "\t" . $str);
            return $this;
        }
    }
?>