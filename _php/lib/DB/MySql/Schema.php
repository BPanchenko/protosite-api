<?php
namespace DB\MySql;
require_once dirname(__FILE__) . '/../Schema.php';

	class Schema extends \DB\Schema {
		
		/* depricated */
		public $columnTypes=array(
				   'pk' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
				'bigpk' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
			   'string' => 'varchar(255)',
				 'text' => 'text',
			  'integer' => 'int(11)',
			   'bigint' => 'bigint(20)',
				'float' => 'float',
			  'decimal' => 'decimal',
			 'datetime' => 'datetime',
			'timestamp' => 'timestamp',
				 'time' => 'time',
				 'date' => 'date',
			   'binary' => 'blob',
			  'boolean' => 'tinyint(1)',
				'money' => 'decimal(19,4)',
		);
		
		function __construct($schema, $user='', $pass='') {
			$this->_schema = $schema;
			$dsn = "mysql:host=" . DB_ROOT_HOST . ";dbname=" . $this->_schema;
			
			if(empty($user))
				$user = DB_ROOT_USER;
			if(empty($pass))
				$pass = DB_ROOT_PASS;
			
			try {
				parent::__construct($dsn, $user, $pass);
				$this->exec("SET CHARACTER SET 'utf8';");
				$this->exec("SET NAMES 'utf8' COLLATE 'utf8_general_ci';");
				$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {  
				echo 'Connection failed: '.$e->getMessage();  
			}
		}
		
		
		public function insert(string $table, array $columns): \DB\MySql\Schema {
			$keys = array_keys($columns);
			
			$update_parts = array();
			foreach($keys as $key)
				array_push($update_parts, $this->quote($key)."=:".$key);
			
			$sql = "INSERT INTO ".$this->quote($table)." (`".implode("`, `",$keys)."`) VALUES (:".implode(", :",$keys).")
					ON DUPLICATE KEY UPDATE ".implode(", ",$update_parts).";";
					
			$this->prepare($sql)
				 ->execute($columns);
				
			return $this;
		}
	}
?>