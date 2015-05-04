<?php
namespace DB\MySql;
require_once dirname(__FILE__) . '/Schema.php';

	class Table extends \DB\MySql\Schema {
		
		protected $_name = '';
		protected $_schema = '';
		
		protected $_columns;
		protected $_defaults_query;
		protected $_primary_key;
		
		function __construct($table, $user, $pass) {
			$table = str_replace('`', '', $table);
			if(preg_match('/^mysql:([\w]+).([\w]+)/', $table, $_matches)) {
				$this->_schema = $_matches[1];
				$this->_name = $_matches[2];
			} else 
				throw new SystemException('WrongMySqlTableName');
			
			parent::__construct($this->_schema, $user, $pass);
			
			$this->_defaults_query = array(
				'from' => $this->_name
			);
			$this->columns();
		}
		
		/****/
		public function columns() {
			if($this->_columns) return $this->_columns;
			
			// fetch the table structure
			$_sql = "SHOW COLUMNS FROM `".$this->name()."` \n";
			$_sth = $this->query($_sql);
			$_sth->bindParam(":tbl_name", $this->name());
			$_sth->execute();
			
			while($_row = $_sth->fetch(\PDO::FETCH_OBJ)) {
				$_name = $_row->Field;
				$_type = strtolower($_row->Key) == 'pri' ? 'pk' : NULL;
				
				if(!$_type)
					foreach ($this->columnTypes as $columnType=>$needle) {
						$_type = $_row->Type == $needle ? $columnType : NULL;
						if($_type) break;
					}
				
				$this->_columns[$_name]['_inst'] = $_row;
				$this->_columns[$_name]['type'] = $_type;
				if($_type == 'pk')
					$this->_setPrimaryKey($_name);
				
			}
			
			return $this->_columns;
		}
		
		/* < TODO: use php trait ... */
		
		/****/
		public function fields() {
			if(!$this->_columns) $this->columns();
			return
				array_keys($this->columns);
		}
		
		/****/
		public function columnType($column_name) {
			if(!$this->_columns) $this->columns();
			return
				$this->hasColumn($column_name) ? $this->_columns[$column_name]['type'] : NULL;
		}
		
		/****/
		public function hasColumn($column_name) {
			if(!$this->_columns) $this->columns();
			return
				array_key_exists($column_name, $this->_columns);
		}
		
		/****/
		public function primaryKey() {
			return $this->_primary_key;
		}
		
		/****/
		private function _setPrimaryKey($column_name) {
			
			if(is_null($this->_primary_key))
				$this->_primary_key = $column_name;
			elseif(is_array($this->_primary_key))
				array_push($this->_primary_key, $column_name);
			elseif(is_string($this->_primary_key))
				$this->_primary_key = array($this->_primary_key, $column_name);
			
			return $this->primaryKey();
		}
		
		/* SQL-Constructor */
		
		/****/
		public function fetchColumn() {
			$this->from($this->_name);
			return parent::fetchColumn();
		}
		
		/****/
		public function fetchAll($fetch_style = \PDO::FETCH_OBJ) {
			$this->from($this->_name);
			return parent::fetchAll($fetch_style);
		}
		
		/****/
		public function save(array $columns) {
			// check columns among the fields of database table
			foreach($columns as $column=>$value)
				if(!$this->hasColumn($column))
					unset($columns[$column]);
			
			return parent::save($this->_name, $columns);
		}
		
		/****/
		public function update($columns = array(), $conditions='', $params=array()) {
			return parent::update($this->_name, $columns, $conditions='', $params);
		}
		
		public function drop() { return $this->dropTable($this->_name); }
		public function name() { return $this->_name; }
		
		/* </ use php trait ... */
	}
?>