<?php

array_walk($_COOKIE, function(&$cookie){ $cookie = trim($cookie, '"'); });

function array_parse_int(array $arr = []) {
  return array_combine(array_keys($arr), array_map(function($val){
    return is_numeric($val) ? (int)$val : $val;
  }, $arr));
}

function getQueryWithoutParameter($param='') {
    $query = trim($_SERVER{'QUERY_STRING'}, '?');
    $params = array();

    $temp = explode('&', $query);
    foreach($temp as $phrase) {
        if(trim($phrase) && strpos($phrase, $param . '=') === false)
            array_push($params, $phrase);
    }

    return count($params) ? join('&', $params) : null;
}

function translit($string) {
    $trans = array("а" => "a", "б" => "b", "в" => "v", "г" => "g",
                    "д" => "d", "е" => "e", "ё" => "e", "ж" => "zh",
                    "з" => "z", "и" => "i", "й" => "y", "к" => "k",
                    "л" => "l", "м" => "m", "н" => "n", "о" => "o",
                    "п" => "p", "р" => "r", "с" => "s", "т" => "t",
                    "у" => "u", "ф" => "f", "х" => "kh", "ц" => "ts",
                    "ч" => "ch", "ш" => "sh", "щ" => "shch", "ы" => "y",
                    "э" => "e", "ю" => "yu", "я" => "ya", "А" => "A",
                    "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D",
                    "Е" => "E", "Ё" => "E", "Ж" => "Zh", "З" => "Z",
                    "И" => "I", "Й" => "Y", "К" => "K", "Л" => "L",
                    "М" => "M", "Н" => "N", "О" => "O", "П" => "P",
                    "Р" => "R", "С" => "S", "Т" => "T", "У" => "U",
                    "Ф" => "F", "Х" => "Kh", "Ц" => "Ts", "Ч" => "Ch",
                    "Ш" => "Sh", "Щ" => "Shch", "Ы" => "Y", "Э" => "E",
                    "Ю" => "Yu", "Я" => "Ya", "ь" => "", "Ь" => "",
                    "ъ" => "", "Ъ" => "");
    return preg_match("/[а-яА-Я]/",$string) ? strtr($string, $trans) : $string;
}

function str2array($data) {
    $result = NULL;

    if(is_array($data))
        $result = $data;
    else if(!empty($data)) {
        $str = str_replace(array(';','\t','\n'),',',$data);
        $str = preg_replace('~[^-_a-z0-9,]+~u','',$str);
        $array = explode(',',$str);
        if(!is_array($array) && !empty($array)) $array[0] = $array;
        $result = $array;
    }

    return $result;
}

function camelize($str) {
    $str = preg_replace('~[^A-Za-z0-9]+~u', ' ', strtolower($str));
    $str = ucwords($str);
    $str = str_replace(' ', '',$str);
    $str = strtolower($str{0}) . substr($str, 1);
    return $str;
}
?>