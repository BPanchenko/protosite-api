<?php

class Post extends \base\Model {
    public static $idAttribute = 'post_id';
    protected $_table = "mysql:posts";
}