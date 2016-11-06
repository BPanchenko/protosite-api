<?php

class Photos extends base\Collection {
    public static $classModel = Photo;
    protected $_table = "mysql:photos";

    /* public API
     ========================================================================== */

    public function POST__upload(): array {
        $file =  $_FILES['file'];

        $extensions = array('jpg','png','gif');
        $types = array('image/jpeg','image/png','image/gif');

        if(!$file) throw new Exception('UPLOAD_ERR_NO_FILE');
        if($file['error']) throw new Exception($file['error']);
        if(!in_array($file['type'], $types)) throw new Exception('UPLOAD_ERR_WRONG_TYPE');

        $temp = explode(".", $file['name']);
        $filename = preg_replace('~[^\w]+~u',"-", translit($temp[0]));
        $filename = $this->_table->getUniqValue('filename', $filename);
        $extension = $extensions[array_search($file['type'], $types)];

        if(move_uploaded_file($file['tmp_name'], PHOTOS_UPLOAD_DIR .  '/' . $filename . "." . $extension)) {
            $Photo = $this->create(array(
                'folder' => 'temp',
                'filename' => $filename,
                'extension' => $extension
            ))->save();
            return $Photo->toArray();
        } else {
            throw new Exception('UPLOAD_FAIL');
        }
    }
}