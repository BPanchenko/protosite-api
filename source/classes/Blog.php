<?php
	class Article extends Model {
		protected $_table = "`dbname`.`articles`";
		protected $_table_links = "`dbname`.`article_links`";
		
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
		
		public function _parse($data){
			$data = parent::_parse($data);
			if(!empty($data['date']) && is_numeric($data['date'])) $data['date'] = date("Y-m-d", $data['date']);
			return $data;
		}
	}
	
	class Blog extends Collection {
		public $ModelClass = Article;
		
		function __construct() {
			parent::__construct();
		}
		
	}
?>