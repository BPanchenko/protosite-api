<?php
	class Author extends Model {
		protected $_table = "`bp_sov-art`.`authors`";
		protected $_table_links = "`bp_sov-art`.`author_links`";
		
		function __construct($data) {
			parent::__construct($data);
		}
		
		public function fetch($fields=NULL) {
			parent::fetch($fields);
			if(empty($fields) || (is_string($fields) && strpos('photos',$fields)!==false) || (is_array($fields) && in_array('photos',$fields))) {
				$this->setPhotos();
			}
			return $this;
		}
		
		public function setPhotos() {
			$sth = $this->_dbh->query("select `photo_id` from ".$this->_table_links." where `".$this->idAttribute."` = ".$this->id.";");
			$photo_ids = array();
			$Photos = new Photos();
			if($sth->rowCount()) {
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				while($row = $sth->fetch()) {
					$Photos->create($row)->fetch()->addTo($Photos);
				}
			}
			$this->set('photos',$Photos);
			return $this;
		}
	}
	
	class Authors extends Collection {
		public $ModelClass = Author;
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