<?php
	class Shop extends Model {
		protected $_table = "`bp_sov-art`.`product_shops`";
		
		function __construct($data) {
			parent::__construct($data);
		}
	}
	
	class Shops extends Collection {
		public $ModelClass = Shop;
		
		function __construct() {
			parent::__construct();
		}
		
		public function fetch() {
			$query = "select * from ".$this->Table->name()." order by `location` asc";
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