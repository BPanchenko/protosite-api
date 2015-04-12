<?php
	class Accounts extends \base\Collection {
		public static $classModel = Account;
		
		protected $_table = '';
		
		function __construct($data) {
			parent::__construct($data);
			
			$this->_table = new \DB\Table('accounts', array(
				'dsn' => "mysql:host=" . \DB\MySql\Schema::HOST . ";dbname=bp_instastat",
				'user' => \DB\MySql\Schema::USER,
				'pass' => \DB\MySql\Schema::PASS
			));
		}
		
	}
?>