<?php
	class Factory extends Model {
		protected $_table = "`bp_sov-art`.`factories`";
		protected $_table_links = "`bp_sov-art`.`factory_links`";
		
		function __construct($data) {
			parent::__construct($data);
		}
		
		public function linkTo($obj) {
			if(!empty($this->TableLinks)) {
				if($obj instanceof Model && !$obj->isNew()) {
					$data[0][$this->idAttribute] = $this->id;
					$data[0][$obj->idAttribute] = $obj->id;
					if(!$obj->isEmpty('is_mark')) $data[0]['is_mark'] = 1;
				}
				if($obj instanceof Collection && $obj->length) {
					foreach($obj->models as $key => $model) {
						$data[$key][$this->idAttribute] = $this->id;
						$data[$key][$model->idAttribute] = $model->id;
						if(!$model->isEmpty('is_mark')) $data[$key]['is_mark'] = 1;
					}
				}
				if(count($data)) foreach($data as $link) {
					$this->TableLinks->save($link);
				}
			}
			return $this;
		}
	}
	
	class Factories extends Collection {
		public $ModelClass = Factory;
        protected $_defaults = array(
			"fetch" => array(
				"search_fields" => array('name'),
				"order_key" => "name",
				"order_by" => "asc",
				"offset" => 0,
				"count" => 20
			)
		);
		
		function __construct() {
			parent::__construct();
		}
		
	}
?>