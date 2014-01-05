<?php
	class Technology extends Model {
		protected $_table = "`bp_sov-art`.`product_technologies`";
		
		function __construct($data) {
			parent::__construct($data);
		}
	}
	
	class Technologies extends Collection {
		public $ModelClass = Technology;
		
		function __construct() {
			parent::__construct();
		}
		
		public function fetch() {
			$query = "select * from ".$this->Table->name()." order by `name` asc";
			$sth = $this->_dbh->query($query);
			if($sth->rowCount()) {
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				while($row = $sth->fetch()) {
					$this->add($row);
				}
			}
			return $this;
		}
		
	}
?>