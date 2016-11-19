<?php
namespace DB;
require_once dirname(__FILE__) . '/Schema.php';

	class Table {
		public $_schema;
		
		protected $_query;
		protected $_name;
		protected $_isSQLite;
		protected $_isMySql;
		
		function __construct($name, $options = array(
			'dsn' => NULL,
			'user' => NULL,
			'pass' => NULL,
			'dir' => NULL
		)) {
			$this->_name = $name;
			
			if(strpos($options['dsn'], 'sqlite:') === 0 && !is_null($options['dir']) && is_null($options['user']) && is_null($options['pass'])) {
				$this->_isSQLite = true;
				$this->_schema = new \DB\SQLite\Schema(str_replace('sqlite:', '',$options['dsn']), $options['dir']);
				
			} elseif(strpos($options['dsn'], 'mysql:') === 0 && !is_null($options['user']) && !is_null($options['pass']) && is_null($options['dir'])) {
				$this->_isMySql = true;
				$this->_schema = new \DB\MySql\Schema(str_replace('mysql:', '',$options['dsn']), $options['user'], $options['pass']);
			}
			
			$this->reset();
		}
		
		public function IsSQLite() { return $this->_isSQLite; }
		
		public function IsMySql() { return $this->_isMySql; }
		
		public function name() { return $this->_name; }
		
		public function save(array $data) {
			return $this->_schema->save($this->name(), $data);
		}
		
		
		
		/****/
		public function reset() {
			$this->_schema->reset();
			$this->_schema->from($this->_name);
			return $this;
		}
		
		/****/
		public function groupBy($sql) {
			$this->_schema->groupBy($sql);
			return $this;
		}
		
		/****/
		public function select($columns='*', $option='') {
			$this->_schema->select($columns, $option);
			return $this;
		}
		
		/****/
		public function selectDistinct($columns='*') {
			$this->_schema->selectDistinct($columns);
			return $this;
		}
		
		/****/
		public function where($conditions, array $params = array()) {
			$this->_schema->where($conditions, $params);
			return $this;
		}
		
		/****/
		public function andWhere($conditions, array $params = array()) {
			$this->_schema->andWhere($conditions, $params);
			return $this;
		}
		
		/****/
		public function orWhere($conditions, array $params = array()) {
			$this->_schema->orWhere($conditions, $params);
			return $this;
		}
		
		/****/
		public function order($columns) {
			$this->_schema->order($columns);
			return $this;
		}
		
		/****/
		public function offset($offset) {
			$this->_schema->limit($offset);
			return $this;
		}
		
		/****/
		public function limit($limit, $offset=NULL) {
			$this->_schema->limit($limit, $offset);
			return $this;
		}
		
		/****/
		public function fetch($fetch_style = \PDO::FETCH_OBJ) {
			return $this->_schema->fetch($fetch_style);
		}
		
		/****/
		public function fetchColumn() {
			return $this->_schema->fetchColumn();
		}
		
		/****/
		public function fetchAll($fetch_style = \PDO::FETCH_OBJ) {
			return $this->_schema->fetchAll($fetch_style);
		}
		
		/**
		 * Returns all table names in the database.
		 * @return array all table names in the database.
		 */
		public function tableNames() {
			return $this->_schema->tableNames();
		}
	}

?>