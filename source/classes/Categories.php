<?php
	class Category extends Model {
		protected $_table = "`dbname`.`product_categories`";
		
		function __construct($data) {
			parent::__construct($data);
		}
	}
	
	class Categories extends Collection {
		public $ModelClass = Category;
		
		function __construct() {
			parent::__construct();
		}
		
		public function fetch() {
			$query = "select * from ".$this->Table->name()." order by `sort` asc";
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