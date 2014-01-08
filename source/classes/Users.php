<?php
	class User extends Model {
		protected $_table = "`dbname`.`users`";
		protected $_table_tokens = "`dbname`.`user_tokens`";
		public $TableTokens;
		
		function __construct($data) {
			parent::__construct($data);
			$this->TableTokens = new Table($this->_table_tokens);
			if(!$this->isEmpty('access_token')) {
				$sth = $this->_dbh->query("select `user_id` 
											from ".$this->_table_tokens." 
											where `access_token`='".$this->get('access_token')."' 
											limit 1;");
				if(!$sth->rowCount()) throw new Exception("AuthAccessTokenException");
				$this->set('user_id',$sth->fetchColumn());
			}
		}
		
		public function doAuth(){
			if($this->isEmpty('nickname')) throw new Exception('AuthNicknameEmpty');
			if($this->isEmpty('password')) throw new Exception('AuthPasswordEmpty');
			if(strlen($this->get('password'))<6) throw new Exception('AuthPasswordInvalid');
			
			$nickname = trim($_POST['nickname']);
			$sth = $this->_dbh->query("select `user_id` from ".$this->_table." where `nickname`='".$nickname."' limit 1;");
			if(!$sth->rowCount()) throw new Exception("AuthNicknameNotFound");
			$res = $sth->fetch();
			
			$pwd_sha1 = sha1($nickname.$res['user_id'].$this->get('password'));
			$sth = $this->_dbh->query("select `user_id` from ".$this->_table." where `user_id`=".$res['user_id']." AND `pwd_sha1`='".$pwd_sha1."' LIMIT 1");
			if(!$sth->rowCount()) throw new Exception("AuthPasswordWrong");
			$res = $sth->fetch();
			
			// пользователь авторизован, создаем маркер доступа
			$access_token = sha1($nickname.$this->get('password').time());
			$this->TableTokens->save(array(
				'user_id' => $res['user_id'],
				'access_token' => $access_token
			));
			$this->set('access_token',$access_token)
					->set('user_id',$res['user_id']);
			
			return $this;
		}
		
		public function doRegistration($data){
			if(!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/i", $data['email'])) throw new Exception('EmailInvalid');
			
			return $this;
		}
		
		public function doRecovery($data){
			if(!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/i", $data['email'])) throw new Exception('EmailInvalid');
			
			return $this;
		}
	}
?>