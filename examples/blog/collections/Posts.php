<?php
	class Posts extends \base\Collection {
		public static $classModel = Post;
		protected $_table = 'mysql:db.posts';
	}
?>