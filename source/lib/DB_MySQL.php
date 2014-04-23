<?php
	class DB_MySQL {
		const HOST = "bp.mysql";
		const USER = "bp_mysql";
		const PWD = "dgy9dzuj";
		protected static $dbh;
		protected static $name;
		public static function dbh() { return self::$dbh; }
		public static function name() { return self::$name; }
		
		public static function connect($db_name) {
			$_name = str_replace("`",'',$db_name);
			if(self::$name == $_name) return self::dbh();
			
			try {
				self::$name = $_name;
				self::$dbh = new PDO("mysql:host=".self::HOST.";dbname=".self::$name, self::USER, self::PWD);
				self::$dbh->exec("SET CHARACTER SET 'utf8';");
				self::$dbh->exec("SET NAMES 'utf8' COLLATE 'utf8_general_ci';");
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo 'Connection failed: '.$e->getMessage();  
			}
			return self::$dbh;
		}
		
		private function __construct() {}
		private function __clone() {}
	}
?>