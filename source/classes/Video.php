<?php
	class Vid extends Model {
		protected $_table = "`bp_sov-art`.`video`";
		
		function __construct($data) {
			parent::__construct($data);
		}
	}
	
	class Video extends Collection {
		public $ModelClass = Vid;
		
		function __construct() {
			parent::__construct();
		}
	}
?>