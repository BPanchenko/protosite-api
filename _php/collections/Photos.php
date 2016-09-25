<?php

class Photos extends base\Collection {
    public static $classModel = Photo;
    protected $_table = "mysql:photos";
}