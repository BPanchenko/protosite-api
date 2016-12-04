<?php

class Post extends \base\Model {
    public static $idAttribute = 'post_id';
    public $tb = "mysql:posts";
}