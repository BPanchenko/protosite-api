<?php
	class ___Comments extends \base\Collection {
		public static $classModel = Comment;
		
		// protected $_table = 'mysql:{schema_name}.{table_name}';
		protected $_table = 'sqlite:{ig_uid}/profile.media_comments';
		
		public function fetch($options) {
			
			if(is_null($this->_parent))
				throw new SystemException('ParentIsNull');
			if(!($this->_parent instanceof \base\Model))
				throw new SystemException('ParentIsNotModel');
			if(!($this->_parent instanceof Profile))
				throw new SystemException('ParentIsNotProfile');
			
			$this->_table = str_replace('{ig_uid}', $this->_parent->id, $this->_table);
			
			return parent::fetch($options);
		}
	}
?>