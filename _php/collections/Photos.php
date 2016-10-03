<?php

class Photos extends base\Collection {
    public static $classModel = Photo;
    protected $_table = "mysql:photos";

    /* public API
     ========================================================================== */

    public function POST__upload(){
        $file = upload_file($_FILES['file'], PHOTOS_UPLOAD_DIR, 'images');
        $file['folder'] = 'temp';
        $Photo = $this->create($file)->save();
        return $Photo->toArray();
    }
}