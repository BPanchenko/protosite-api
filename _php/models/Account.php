<?php
	class Account extends \base\Model {
		
		public static $idAttribute = 'account_id';
		public $table;
		public $tables = array();
		
		private $_dir_storage = '/storage/';
		private $_profiles;
		
		function __construct($data) {
			parent::__construct($data);
			
			$this->table = new \DB\Table('accounts', array(
				'dsn' => "mysql:host=" . \DB\MySql\Schema::HOST . ";dbname=bp_instastat",
				'user' => \DB\MySql\Schema::USER,
				'pass' => \DB\MySql\Schema::PASS
			));
			
			$this->fetch();
		}
		
		public function fetch($params, $query) {
			
			//
			$this->table->reset()
						->select()
						->where("`account_id` = " . $this->id)
						->limit(1);
			$_row = $this->table->fetchAll(\PDO::FETCH_ASSOC);
			$this->set($_row[0]);
			
			return $this;
		}
		
		public function isValid() {
			return $this->has(self::$idAttribute);
		}
		
		public function GET__countsLastModify ($params, $query) {
			return date("c", $this->updated);
		}
	}
?>