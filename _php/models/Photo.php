<?php

class Photo extends \base\Model {
    public static $idAttribute = 'photo_id';
    protected $_table = "mysql:photos";

    public function parse($data = array()){
        $data['src'] = PHOTOS_PATH . '/' . $data['folder'] . '/' . $data['filename'] . '.' . $data['extension'];
        return $data;
    }
}