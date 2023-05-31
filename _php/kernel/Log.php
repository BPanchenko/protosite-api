<?php

class Log {

    private  $_dir;
    private  $_filename;
    private  $_handle;
    private  $_path;

    function __construct(string $filename = '') {
        $this->_dir = LOG_DIR;

        if(!$this->filename($filename)) {
            $this->filename($_SERVER['HTTP_HOST'] . '--' . date('Ymd') . '.log');
        }

        if (!file_exists($this->_path)) {
            $this->_handle = fopen($this->_path, 'a+');
        }
    }

    public function dir($string) {
        if($string) {
            $this->_dir = $string;
            $this->_path = $this->_dir . '/' . $this->_filename;
        }
        return $this->_dir;
    }

    public function filename(string $string) {
        if($string) {
            $this->_filename = $string;
            $this->_path = $this->_dir . '/' . $this->_filename;
        }
        return $this->_filename;
    }

    /**
     * Читает содержимое файла в массив.
     * Возвращает ассоциативный массив, ключи которого являются метками времени записи строк в файл.
     * Если передать true в качестве первого аргумента, то метод вернет всё содержимое в виде строки.
     */
    public function read($is_string = false, $length = 200) {

        if(is_numeric($is_string)) {
            $length = (int)$is_string;
            $is_string = false;
        }

        if($is_string)
            $result = file_get_contents($this->_path);
        else {
            $damp = array_reverse(file($this->_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

            $result = [];
            foreach($damp as $index => $line) {
                if($index >= $length) break;
                $line = explode("\t", $line);
                $result[array_shift($line)] = $line;
            }

            unset($damp);
        }

        return $result;
    }

    public function write($str) {
        file_put_contents($this->_path, microtime(true) . "\t" . $str, FILE_APPEND);
        return $this;
    }
}
?>