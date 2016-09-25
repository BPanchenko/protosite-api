<?php

class Photo extends \base\Model {
    public static $idAttribute = 'photo_id';
    protected $_table = "mysql:photos";
}