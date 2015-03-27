<?php
namespace base;

	abstract class Component {
		
		protected $_childrens = array();
		protected $_default_fetch_options = array(
			'fields' => array(),
			'excluded_fields' => array('is_del'),
			'order' => NULL,
			'count' => 100
		);
		protected $_parent;
		protected $_table = '';
		protected $_tables = array();
		
		
		function __construct() {
			if(is_string($this->_table) && $this->_table)
				$this->_table = $this->initTable($this->_table);
			
			if(is_array($this->_tables) && count($this->_tables))
				foreach($this->_tables as $_tb_name=>$_tb_dns) {
					if(strpos($_tb_dns, '{model_id}') !== false && !$this->isNew()) {
						$_tb_dns = str_replace('{model_id}', $this->id, $_tb_dns);
						$this->_tables[$_tb_name] = $this->initTable($_tb_dns);
					} else
						$this->_tables[$_tb_name] = $this->initTable($_tb_dns);
				}
		}
		
		public function trigger($name) {
			
		}
		
		public function attach($parent_object) {
			array_push($_childrens, $parent_object);
			return $this;
		}
		
		public function attachTo($parent_object) {
			if($parent_object instanceof \base\Component) {
				$this->_parent = $parent_object;
				$parent_object->attach($this);
			}
			return $this;
		}
		
		public function isValid() {
			return true;
		}
		
		
		/* Synchronization of the component with database support
		 ========================================================================== */
		
		public function fetch($options) {
			
			if(is_null($options))
				$options = $this->_default_fetch_options;
			elseif(is_array($options))
				$options = array_merge($this->_default_fetch_options, $options);
			else
				throw new SystemException("WrongFetchOptions");
			
			if(isset($options['fields']))
				$options['fields'] = str2array($options['fields']);
			else
				$options['fields'] = $this->_table->fields();
			
			if(is_null($options['order']))
				$options['order'] = '`' . $this->_table->primaryKey() . '` DESC';
			
			if(isset($_GET['debug'])) {
				var_dump("// Table Options");
				var_dump($options);
			}
				
			//
			if(!isset($options['excluded_fields'])) {
				// TODO: `excluded_fields` by default
			}
			if(isset($options['excluded_fields'])) {
				$options['excluded_fields'] = str2array($options['excluded_fields']);
				// TODO: remove items from `fields` that are present in the `excluded_fields`
			}
			
			// 
			$res = $this->_table->reset()
								->select($options['fields'])
								->limit($options['count'])
								->offset($options['offset'])
								->order($options['order'])
								->fetchAll(\PDO::FETCH_ASSOC);
			
			return $this->set($res);
		}
		
		/**
		 * @method initTable - helper method component framework.
		 * The initialization of an object of class Table.
		 * @param $table - 
		 */
		public function initTable($table) {
			
			if($table instanceof \DB\Schema)
				return $table;
				
			if(empty($table))
				throw new \SystemException('EmptyTableName');
			
			if(strpos($table, 'sqlite:') === 0)
				$table = new \DB\SQLite\Table($table);
				
			if(strpos($table, 'mysql:') === 0)
				$table = new \DB\MySql\Table($table);
			
			if(!($table instanceof \DB\Schema))
				throw new \SystemException('FailInitComponentTable');
			
			return $table;
		}
	}

?>