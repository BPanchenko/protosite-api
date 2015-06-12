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
		
		
		function __construct($data = array(), $parent = NULL) {
			
			
			if($parent instanceof \base\Component)
				$this->attachTo($parent);
			
			if(is_string($this->_table) && $this->_table)
				$this->_table = $this->initTable($this->_table);
			
			if(is_array($this->_tables) && count($this->_tables))
				foreach($this->_tables as $_tb_name=>$_tb_dns) {
					if($_tb_dns instanceof \PDO)
						continue;
					
					if(strpos($_tb_dns, '{model_id}') !== false && !$this->isNew()) {
						$_tb_dns = str_replace('{model_id}', $this->id, $_tb_dns);
						$this->_tables[$_tb_name] = $this->initTable($_tb_dns);
					} elseif(strpos($_tb_dns, '{model_id}') === false)
						$this->_tables[$_tb_name] = $this->initTable($_tb_dns);
				}
		}
		
		public function trigger($name) {
			
		}
		
		public function attach($parent_object) {
			if($parent instanceof \Component)
				array_push($_childrens, $parent_object);
			return $this;
		}
		
		public function attachTo($parent_object) {
			if($parent_object instanceof \base\Component) {
				$this->_parent = $parent_object;
				$parent_object->attach($this);
			}
			if($this instanceof \base\Model && $parent_object instanceof \base\Collection)
				$this->collection = $parent_object;
			
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
			
			// build where expression
			$where_parts = array();
			if($this instanceof \base\Model)
				array_push($where_parts, "`" . $this->_table->primaryKey() . "` = '" . $this->id . "'");
			
			$where_expression = join($where_parts, ' AND ');
			
			
			// 
			$res = $this->_table->reset()
								->select($options['fields'])
								->where($where_expression)
								->limit($options['count'])
								->offset($options['offset'])
								->order($options['order'])
								->fetchAll(\PDO::FETCH_ASSOC);
			
			return $this->set( $this instanceof \base\Model ? $res[0] : $res);
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
		
		
		/* Insert or update a component in the database
		 ========================================================================== */
		
		public function save($data) {
			if($data instanceof \stdClass)
				$data = json_decode(json_encode($data), true);
			
			if(is_array($data) && count($data))
				$this->set($this->parse($data));
			
			// save models of collection
			if($this instanceof \base\Collection) {
				foreach($this as $model)
					$model->save();
				return $this;
			}
			
			// save model
			$this->_table->save($this->toArray());
			
			return $this;
		}
		
		
		/* Prepare api response
		 ========================================================================== */
		
		public function prepareResponse($Response) {
			$Response->set('data', $this->toArray());
			return $this;
		}
	}

?>