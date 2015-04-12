<?php
	class Event extends \base\Model {
		
		public static $idAttribute = 'event_id';
		private $tables = array();
		
		function __construct($data) {
			parent::__construct($data);
			
			if(isset($data->media_id)) {
				$this->media = new MediaModel($data->media_id);
				$this->media->set($this->toArray());
			}
		}
	}
?>