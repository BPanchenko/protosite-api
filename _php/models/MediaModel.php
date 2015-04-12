<?php
	class MediaModel extends \base\Model {
		public static $idAttribute = 'media_id';
		
		function __construct($data) {
			parent::__construct($data);
			
			if($this->isNew()) {
				throw new AppException('UnprocessableEntity');
			}
			
		}
	}
?>