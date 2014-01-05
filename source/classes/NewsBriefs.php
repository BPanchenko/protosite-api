<?php
	class News extends Model {
		protected $_table = "`bp_sov-art`.`news`";
		protected $_table_links = "`bp_sov-art`.`news_links`";
		
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
			if($this->isNew()) return $this;
			$sth = $this->_dbh->query("select `photo_id` from ".$this->_table_links." where `".$this->idAttribute."` = ".$this->id.";");
			$photo_ids = array();
			$Photos = new Photos();
			if($sth->rowCount()) while($row = $sth->fetch()) {
				$Photos->create($row)->fetch()->addTo($Photos);
			}
			$this->set('photos',$Photos);
			return $this;
		}
		
		public function parse($data){
			$data = parent::parse($data);
			if(!empty($data['date']) && ctype_digit($data['date'])) {
				$data['date'] = date("Y-m-d", $data['date']);
				
			}
			return $data;
		}
	}
	
	class NewsBriefs extends Collection {
		public $ModelClass = News;
		
		function __construct() {
			parent::__construct();
		}
		
	}
?>