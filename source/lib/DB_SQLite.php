<?php
	class DB_SQLite {
		protected static $dbh;
		protected static $resource;
		public function dbh() { return self::$dbh; }
		public function name() { return self::$resource; }
		
		public static function connect($resource) {
			if(self::$resource == $resource) return self::dbh();
			
			$path_file_db = str_replace('.:','',get_include_path()).'/';
			try {
				self::$resource = $resource;
				if(!$resource || $resource == ':memory:') {
					self::$dbh = new PDO('sqlite::memory:');
				} else {
					self::$dbh = new PDO('sqlite:'.$path_file_db.$resource);
				}
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