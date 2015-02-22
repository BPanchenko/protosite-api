<?php
namespace http;
	class RequestParametersModel extends \base\Model {
			
		protected $_defaults = array(
			'fields' => NULL,
			'excluded_fields' => array('is_del'),
			'count' => FETCH_DEFAULT_COUNT,
			'offset' => FETCH_DEFAULT_OFFSET
		);
		
		/****/
		public function parse(array $data = array()) {
			
			if(isset($data['fields']))
				$data['fields'] = str2array($data['fields']);
				
			if(isset($data['excluded_fields']))
				$data['excluded_fields'] = str2array($data['excluded_fields']);
			
			if(isset($data['order']) && strpos($data['order'], '`') === false) {
				if(strpos($data['order'], '-') === 0)
					$data['order'] = '`' . str_replace('-','',$data['order']) . '` DESC';
				else
					$data['order'] = '`' . str_replace('-','',$data['order']) . '` ASC';
			}
			
			return $data;
		}
	}
?>