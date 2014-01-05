<?php
	class Page extends Model {
		protected $_table = "`bp_sov-art`.`pages`";
		protected $_table_content = "`bp_sov-art`.`page_content`";
		
		function __construct($data) {
			parent::__construct($data);
		}
		
		public function setHtml($html=''){
			$html = trim($html);
			if(empty($html)) {
				$sth = $this->_dbh->query("SELECT `html`,`date_create` FROM ".$this->_table_content." WHERE `page_id`=".$this->id." order by `is_active` desc limit 1;");
				$row = $sth->fetch();
				$html = $row['html'];
				$this->set('date_update', $row['date_create']);
			} else {
				$this->_dbh->exec("UPDATE ".$this->table_content." 
									SET `is_active`=0 
									WHERE `page_id`=".$this->id.";");
				$sth = $this->_dbh->prepare("INSERT ".$this->table_content."(`page_id`,`html`,`date_create`) 
											VALUES (:page_id, :html, now());");
				$sth->execute(array( "page_id" => $this->id, "html" => $html ));
				$this->set('date_update', date("Y-m-d H:i:s"));
			}
			$this->set('html',$html);
			return $this;
		}
	}
	
	class Pages extends Collection {
		public $ModelClass = Page;
        protected $_defaults = array(
			"fetch" => array(
				"search_fields" => array('name'),
				"order_key" => "sort",
				"order_by" => "asc",
				"offset" => 0,
				"count" => 1000
			)
		);
		
		function __construct() {
			parent::__construct();
		}
		
	}
?>