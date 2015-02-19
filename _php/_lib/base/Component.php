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
			
			if(is_string($this->_table)) $this->_initTable();
			
			// 
			$_fields = array();
			if(isset($options['excluded_fields'])) {
				$options['excluded_fields'] = str2array($options['excluded_fields']);
			}
			
			$_opt = array(
				'where' => (array)$options['where'],
				'fields' => is_array($options['fields']) ? $options['fields'] : '*',
				'order' => '`id` DESC',
				'count' => 20,
				'offset' => 0
			);
			$options = array_merge($_default_options, $options);
			
			// 
			if(isset($options['fields']))
				$options['fields'] = str2array($options['fields']);
			else
				$options['fields'] = $this->_table->columns();
			
			// 
			$res = $this->_table->reset()
								->select($options['fields'])
								->limit($options['count'])
								->offset($options['offset'])
								->fetchAll(\PDO::FETCH_ASSOC);
			
			return $this->set($res);
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