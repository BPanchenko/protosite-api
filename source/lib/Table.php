<?php
	class Table {
		private $_dbh;
		private $_fields = array();
		private $_name;
		private $_primaryKey;
		
		function __construct($table_name) {
			$this->_dbh = DB_MySql::dbh();
			$_name = str_replace(array(DB_MySql::name(),'`','.'),'',$table_name);
			if(!$this->_dbh->query("show tables like '".$_name."'")->rowCount()) throw new ErrorException("WrongTableName");
			$this->_name = "`".DB_MySql::name()."`.`".$_name."`";
			$this->_fields = $this->_fields();
			$this->_primaryKey = $this->_primaryKey();
		}
		
		public function typeField($field) {
			return array_key_exists($field, $this->_fields) ? $this->_fields[$field] : NULL;
		}
		
		public function hasField($field) {
			return array_key_exists($field, $this->_fields);
		}
		
		public function save($data, $debug) {
			$prepare_data = array();
			if(!empty($data) && is_array($data)) {
				foreach($data as $key=>$value) {
					if($this->hasField($key) && !in_array($key, array('date_update'))) {
						if($this->_fields[$key]=='number') $prepare_data[$key] = (int)$value;
						elseif($this->_fields[$key]=='double') $prepare_data[$key] = (float)$value;
						else $prepare_data[$key] = stripslashes($value);
					}
				}
			}
			if(count($prepare_data)) {
				$keys = array_keys($prepare_data);
				$update_parts = array();
				foreach($keys as $key) array_push($update_parts, "`".$key."`=:".$key);
				
				if(!in_array('date_create',$keys) && $this->hasField('date_create')) {
					$query = "insert ".$this->_name." (`".implode("`, `",$keys)."`, `date_create`)
								values (:".implode(", :",$keys).", now())";
				} else {
					$query = "insert ".$this->_name." (`".implode("`, `",$keys)."`)
								values (:".implode(", :",$keys).")";
				}
				$query .= "on duplicate key update ".implode(", ",$update_parts).";";
				
				if($debug) {
					print $query;
					print "<br>\n";
					var_dump($prepare_data);
				}
				
				$this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$sth = $this->_dbh->prepare($query);
				$sth->execute($prepare_data);
				return $this->_dbh->lastInsertId();
			}
			return false;
		}
		
		public function name() {
			return $this->_name;
		}
		
		public function nextSort() {
			$row = $this->_dbh->query("select `sort` from ".$this->_name." order by `sort` desc limit 1")->fetch();
			$_sort = $row['sort']+1;
			return $_sort;
		}
		
		public function truncate() {
			$this->_dbh->exec("truncate table ".$this->_name);
			return true;
		}
		
		public function uniq($column, $string) {
			if(preg_match("/\((\d+)\)$/",$string, $matches)) $a = $matches[1];
			else $a = 0;
			while($a<10000 && $this->_dbh->query("select COUNT(*) as `count` from ".$this->_name." where ".$column."='".$string."'")->fetchColumn()) {
				$a++;
				$a==2 ? $string .= "(".$a.")" : $string = str_replace("(".($a-1).")", "(".$a.")", $string);
			}
			return $string;
		}
		
		public function uniqTranslit($column, $string) {
			return self::uniq($column, Data::str2url($string));
		}
		
		private function _fields(){
			$sth = $this->_dbh->query("show columns from ".$this->_name);
			$fields = array();
			if($sth->rowCount()) {
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				while($row = $sth->fetch()) {
					$field = $row['Field'];
					if(preg_match("/(double)/",$row['Type'])) $type = "double";
					elseif(preg_match("/(int)/",$row['Type'])) $type = "number";
					elseif(preg_match("/(date|timestamp)/",$row['Type'])) $type = "date";
					else $type = "string";
					$fields[$field] = $type;
				}
			}
			return $fields;
		}
		public function fields(){ return $this->_fields; }
		
		public function primaryKey(){ return $this->_primaryKey; }
		private function _primaryKey(){
			try {
				$sth = $this->_dbh->query("show keys from ".$this->_name);
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				while($row = $sth->fetch()) if ($row['Key_name']=="PRIMARY") return $row['Column_name'];
			} catch(PDOException $e) {  
				echo $e->getMessage();  
			}
			return false;
		}
	}
?>