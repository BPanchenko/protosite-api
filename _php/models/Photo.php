<?php

class Photo extends \base\Model {
  public static $idAttribute = 'photo_id';
  public $tb = "mysql:photos";

  public function parse(array $data = array()): array
  {
    $data['src'] = PHOTOS_PATH . '/' . $data['folder'] . '/' . $data['filename'] . '.' . $data['extension'];
    return $data;
  }
}