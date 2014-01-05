<?php
	class DB {
		const HOST = "localhost";
		const USER = "root";
		const PWD = "";
		protected static $instance;
		protected static $dbh;
		protected static $name;
		protected static $tables;
		public function dbh() { return self::$dbh; }
		public function name() { return self::$name; }
		
		public static function connect($db_name) {
			if (!isset(self::$instance)) {
				$class_name = get_called_class();
				self::$instance = new $class_name;
			}
			
			try {
				self::$name = str_replace("`",'',$db_name);
				// $dbh = new PDO('sqlite:'.DB_PATH.'/dbname.sqlite');
				self::$dbh = new PDO("mysql:host=".self::HOST.";dbname=".self::$name, self::USER, self::PWD);
				self::$dbh->exec("SET CHARACTER SET 'utf8';");
				self::$dbh->exec("SET NAMES 'utf8' COLLATE 'utf8_general_ci';");
			} catch(PDOException $e) {  
				echo 'Connection failed: '.$e->getMessage();  
			}
			
			return self::$instance;
		}
		
		protected function tables($db_name=''){
			$tables = array();
			return $tables;
		}
		
		private function __construct() {}
		private function __clone() {}
	}
?>