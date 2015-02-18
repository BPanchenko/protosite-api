<?php
namespace base;

	abstract class Component {
		
		protected $_table = '';
		protected $_parent;
		protected $_childrens = array();
		
		public function trigger($name) {
			
		}
		
		public function attach($parent_object) {
			array_push($_childrens, $parent_object);
			return $this;
		}
		
		public function attachTo($parent_object) {
			$this->_parent = $parent_object;
			$parent_object->attach($this);
			return $this;
		}
		
		
		/* Synchronization of the component with database support
		 ========================================================================== */
		
		public function fetch($options) {
			
			$_default_options = array(
				'where' => (array)$options['where'],
				'fields' => array(),
				'count' => 20,
				'offset' => 0
			);
			$options = array_merge($_default_options, $options);
			
			if(is_string($this->_table))
				$this->_initTable();
				
			// var_dump($this->_parent);
			
			return $this;
		}
		
		protected function _initTable() {
			if(empty($this->_table))
				throw new SystemException('EmptyComponentTableName');
			
			if(strpos($this->_table, 'sqlite:') === 0)
				$this->_table = new \DB\SQLite\Table($this->_table);
				
			if(strpos($this->_table, 'mysql:') === 0)
				$this->_table = new \DB\MySql\Table($this->_table);
			
			if(!($this->_table instanceof \DB\Schema))
				throw new SystemException('FailInitComponentTable');
			
			return $this;
		}
	}

?>