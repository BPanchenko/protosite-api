<?php
namespace DB\MySql;
require_once dirname(__FILE__) . '/Schema.php';

	class Table extends \DB\MySql\Schema {
		
		protected $_table = '';
		protected $_schema = '';
		
		function __construct($table, $user='', $pass='') {
			// "mysql:host=" . DB_ROOT_HOST . ";dbname=" . $this->_schema
			
			$_parts = explode('.',str_replace(array('mysql:', '`'), '', trim($table)));
			$this->_schema = $_parts[0];
			$this->_table = $_parts[1];
			
			parent::__construct($this->_schema, $user, $pass);
			
			
		}
	}
?>