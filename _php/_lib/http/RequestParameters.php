<?php
namespace http;
	class RequestParameters extends \base\Model {
		
		/****/
		public function parse(array $data = array()) {
			
			if(isset($data['fields']))
				$data['fields'] = str2array($data['fields']);
				
			if(isset($data['excluded_fields']))
				$data['excluded_fields'] = str2array($data['excluded_fields']);
			
			return $data;
		}
	}
?>