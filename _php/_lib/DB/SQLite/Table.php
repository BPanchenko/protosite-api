<?php
namespace DB\SQLite;
require_once dirname(__FILE__) . '/Schema.php';

	class Table extends \DB\SQLite\Schema {
		
		protected $_table = '';
		
		function __construct($dns) {
			list($this->_table, $dir, $file_name) = $this->_prepareTable($dns);
			parent::__construct($file_name, $dir);
			
			var_dump($this->tableNames());
		}
		
		
		
		public function drop() { return $this->dropTable($this->_table); }
		public function name() { return $this->_table; }
		
		private function _prepareTable($dns) {
			$_parts = explode('.', str_replace(array('sqlite:'), '', $dns));
			
			$_table = $_parts[1];
			$_file_name = trim(substr($_parts[0], strrpos($_parts[0], '/')), '/');
			$_dir = str_replace(('/' . $_file_name), '', $_parts[0]);
			
			return array( $_table, $_dir, $_file_name );
		}
	}
?>