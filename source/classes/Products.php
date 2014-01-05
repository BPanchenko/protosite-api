<?php
	class Product extends Model {
		protected $_table = "`bp_sov-art`.`products`";
		protected $_table_links = "`bp_sov-art`.`product_links`";
		
		function __construct($data) {
			parent::__construct($data);
		}
		
		public function fetch($fields=NULL) {
			parent::fetch($fields);
			if(empty($fields) || (is_string($fields) && strpos('photos',$fields)!==false) || (is_array($fields) && in_array('photos',$fields))) {
				$this->setPhotos();
			}
			return $this;
		}
		
		public function parse($data){
			foreach($data as $key=>$var) {
				if($key==$this->id_attribute) $data['id'] = (double)$var;
				if(is_numeric($var)) $data[$key] = (double)$var;
				else $data[$key] = $var;
				switch($key){
					case 'price_sale':
						$var = str_replace(",00","",number_format((double)$var, 2, ',', ' '));
						$data['price_sale_formatted'] = $var;
					break;
					case 'author_id':
						$sth = $this->_dbh->query("select `author_id`, `name`, `name_translit`, `notice`, `text`, `is_public` 
													from `authors`
													where `author_id`=".$var." 
													limit 1");
						if($sth->rowCount()) {
							$sth->setFetchMode(PDO::FETCH_ASSOC);
							$data['author'] = $sth->fetch();
						} else $data[$key] = NULL;
					break;
					case 'owner_id':
						$sth = $this->_dbh->query("select `owner_id`, `name`, `phone`, `passport` 
													from `product_owners` 
													where `owner_id`=".$var." 
													limit 1;");
						if($sth->rowCount()) {
							$sth->setFetchMode(PDO::FETCH_ASSOC);
							$data['owner'] = $sth->fetch();
						} else $data[$key] = NULL;
					break;
					case 'shop_id':
						$sth = $this->_dbh->query("select `shop_id`, `location` 
													from `product_shops` 
													where `shop_id`=".$var." 
													limit 1;");
						if($sth->rowCount()) {
							$sth->setFetchMode(PDO::FETCH_ASSOC);
							$data['shop'] = $sth->fetch();
						} else $data[$key] = NULL;
					break;
					case 'factory_id':
						$sth = $this->_dbh->query("select `factory_id`, `name`, `name_translit`, `notice`, `text`, `is_public` 
													from `factories`
													where `factory_id`=".$var." 
													limit 1");
						if($sth->rowCount()) {
							$sth->setFetchMode(PDO::FETCH_ASSOC);
							$data['factory'] = $sth->fetch();
						} else $data[$key] = NULL;
					break;
					case 'text':
						$data[$key] = !trim($var) ? NULL : $var;
					case 'notice':
						$data[$key] = !trim($var) ? NULL : $var;
					break;
				}
			}
			return $data;
		}
		
		public function setPhotos() {
			$sth = $this->_dbh->query("select `photo_id` from ".$this->_table_links." where `".$this->idAttribute."` = ".$this->id.";");
			$photo_ids = array();
			$Photos = new Photos();
			if($sth->rowCount()) {
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				while($row = $sth->fetch()) {
					$Photos->create($row)->fetch()->addTo($Photos);
				}
			}
			$this->set('photos',$Photos);
			return $this;
		}
	}
	
	class Products extends Collection {
		public $ModelClass = Product;
		
		function __construct() {
			parent::__construct();
		}
		
	}
?>