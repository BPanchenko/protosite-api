<?php
	class MediaCollection extends \base\Collection {
		public static $classModel = Profile;
		
		protected $_table = 'sqlite:{ig_uid}/media_list';
		
		function __construct($data) {
			parent::__construct($data);
			
			/*
			$this->_table = new \DB\Table('profiles', array(
				'dsn' => "mysql:host=" . \DB\MySql\Schema::HOST . ";dbname=bp_instastat",
				'user' => \DB\MySql\Schema::USER,
				'pass' => \DB\MySql\Schema::PASS
			));
			*/
		}
	}
?>