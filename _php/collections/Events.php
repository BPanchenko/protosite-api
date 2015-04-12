<?php
	class Events extends \base\Collection {
		public static $classModel = Event;
		
		protected $_table = '';
		
		function __construct($data, $parent = NULL) {
			
			if(is_null($parent)) throw new SystemException('ParentIsNull');
			if(!($parent instanceof \base\Model)) throw new SystemException('ParentIsNotModel');
			
			parent::__construct($data, $parent);
		}
		
		public function fetch($params, $query) {
			$tb_media = $this->_parent->tables['profile.media_list'];
			
			$list = $tb_media->select()
					 ->order('`created_time` DESC')
					 ->limit(3)->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach($list as $item) {
				$this->add($item);
			}
			
			return $this;
		}
		
		public function get__lastModify ($params, $query) {
			if(!isset($query) && isset($params)) {
				$query = $params;
				unset($params);
			}
			
			return date("c", $_ts);
		}
	}
?>