<?php
	class Country extends Model {
		protected $_table = "`bp_sov-art`.`product_countries`";
		
		function __construct($data) {
			parent::__construct($data);
		}
	}
	
	class Countries extends Collection {
		public $ModelClass = Country;
		
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