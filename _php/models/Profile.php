<?php
	class Profile extends \base\Model {
		
		public static $idAttribute = 'ig_uid';
		public $_tables = array(
			'users' => 'mysql:bp_stat.ig_users',
			'profile.counts' => 'sqlite:{model_id}/profile.counts',
			'profile.media_comments' => 'sqlite:{model_id}/profile.media_comments',
			'profile.media_likes' => 'sqlite:{model_id}/profile.media_likes',
			'profile.media_list' => 'sqlite:{model_id}/profile.media_list',
			'profile.tags' => 'sqlite:{model_id}/profile.tags',
			
			'related_users.counts' => 'sqlite:{model_id}/related_users.counts',
			'related_users.users' => 'sqlite:{model_id}/related_users.users',
		);
		
		public function fetch($params, $query) {
			
			// fetch user data from the `ig_users` mysql table
			$_tbl = $this->_tables['users'];
			$_tbl->reset()
				 ->select()->where("`user_id` = " . $this->id)->limit(1);
			$_row = $_tbl->fetchAll(\PDO::FETCH_ASSOC);
			$_row = $_row[0];
			
			// 
			$cnt_likers = $this->_tables['profile.media_likes']->reset()
							 ->select(array(
								"COUNT(DISTINCT(`user_id`))",
							 ))->fetchColumn();
			$cnt_commenters = $this->_tables['profile.media_comments']->reset()
							 ->select(array(
								"COUNT(DISTINCT(`user_id`))",
							 ))->fetchColumn();
			
			$this->set('counts', array(
				'media' => (int)$_row['cnt_media'],
				'likes' => (int)$_row['cnt_likes'],
				'comments' => (int)$_row['cnt_comments'],
				'follows' => (int)$_row['cnt_follows'],
				'followed_by' => (int)$_row['cnt_followed_by'],
				'likers' => (int)$cnt_likers,
				'commenters' => (int)$cnt_commenters
			));
			unset($_row['cnt_media'], $_row['cnt_likes'], $_row['cnt_comments'], $_row['cnt_follows'], $_row['cnt_followed_by']);
			$this->set($_row);
			
			return $this;
		}
		
		public function GET__counts($params, $query) {
			if(!isset($query) && isset($params)) {
				$query = $params;
				unset($params);
			}
			
			if($query['from'] == '-1day')
				$query['from'] = "strftime('%s', 'now', '-1 day')";
			
			elseif($query['from'] == '-3days')
				$query['from'] = "strftime('%s', 'now', '-3 days')";
			
			elseif($query['from'] == '-1week')
				$query['from'] = "strftime('%s', 'now', '-7 days')";
			
			elseif($query['from'] == '-2week')
				$query['from'] = "strftime('%s', 'now', '-14 days')";
			
			elseif($query['from'] == '-3week')
				$query['from'] = "strftime('%s', 'now', '-21 days')";
			
			elseif($query['from'] == '-1month')
				$query['from'] = "strftime('%s', 'now', '-1 month')";
			
			elseif($query['from'] == '-2month')
				$query['from'] = "strftime('%s', 'now', '-2 month')";
			
			elseif($query['from'] == '-3month')
				$query['from'] = "strftime('%s', 'now', '-3 month')";
			
			else
				$query['from'] = "strftime('%s', 'now', '-7 days')";
			
			if(!$query['to'] || $query['to'] == 'now')
				$query['to'] = date('U');
			
			if(!$query['limit'])
				$query['limit'] = 1000;
				
			
			$tbl = $this->_tables['profile.counts'];
			
			$_res = array(
				'media' => array(),
				'followed_by' => array(),
				'follows' => array(),
				'likes' => array(),
				'likers' => array(),
				'comments' => array(),
				'commenters' => array(),
				'timestamps' => array()
			);
			
			$step = 3600; // hour
			if($query['scale'] === 'day')
				$step = $step * 24;
				
			// var_dump($step);
			
			$query['from'] = $start = $tbl->query("SELECT " . $query['from'])->fetchColumn();
			$start = floor($start/$step) * $step;
			
			$ts = $start;
			
			$end = $tbl->query("SELECT " . $query['to'])->fetchColumn();
			$end = ceil($end/$step) * $step;
			
			do {
				$_res['timestamps'][] = $ts;
				$ts += $step;
			} while($ts <= $end);
			
			foreach($_res['timestamps'] as $i=>$ts) {
				// fetch own result ( arithmetical mean value )
				$tbl->reset()
					->select(array(
						//"strftime('%Y-%m-%dT%H:%M:%S+00:00', datetime(SUM(`date`) / COUNT(*), 'unixepoch')) as `dates`",
						"strftime('%Y-%m-%dT%H:%M:%S+00:00', datetime(`date`, 'unixepoch')) as `dates`",
						"COUNT(*) as `length`",
						"SUM(`cnt_media`) / COUNT(*) as `media`",
						"SUM(`cnt_follows`) / COUNT(*) as `follows`",
						"SUM(`cnt_followed_by`) / COUNT(*) as `followed_by`",
						"SUM(`cnt_likes`) / COUNT(*) as `likes`",
						"SUM(`cnt_likers`) / COUNT(*) as `likers`",
						"SUM(`cnt_comments`) / COUNT(*) as `comments`",
						"SUM(`cnt_commenters`) / COUNT(*) as `commenters`"
					))
					->where("`date` > ".( $ts - $step/2 )." AND `date` <= ".( $ts + $step/2 ));
				
				$_row = $tbl->order("`date`")
							->limit($query['limit'])
							->fetchAll(\PDO::FETCH_ASSOC);
				/*
				var_dump("#START: " . date('Y-m-d H:i:s', $ts - $step/2));
				var_dump("#MIDDLE: " . date('Y-m-d H:i:s', $ts));
				var_dump("#END: " . date('Y-m-d H:i:s', $ts + $step/2));
				var_dump($_row);
				*/
				foreach($_row[0] as $key=>$val) {
					// var_dump($key . ':' . $val);
					if(is_numeric($val))
						$val = (int)$val;
					$_res[$key][$i] = $val;
				}
			}
			
			global $Response;
			$pagination = new stdClass();
			$pagination->start = date("c", min($_res['timestamps']));
			$pagination->step = $step;
			$pagination->end = date("c", max($_res['timestamps']));
			$Response->set('pagination', $pagination);
			
			//
			if(isset($params[0])) {
				foreach($_res as $metric=>$value) {
					if(!in_array($metric, array('timestamps', 'dates', $params[0])))
						unset($_res[$metric]);
				}
			}
			
			return $_res;
		}
		
		public function GET__comments ($params, $query) {
			if(!isset($query) && isset($params)) {
				$query = $params;
				unset($params);
			}
			
			if(!$query['limit'])
				$query['limit'] = 3;
			
			if(!$query['bulk'])
				$query['bulk'] = 'compact';
				 
			$_comments = $this->_tables['profile.media_comments']->reset()
						->select()
						->limit($query['limit'])
						->fetchAll(\PDO::FETCH_ASSOC);
						
			if($query['bulk'] == 'full') {
				foreach($_comments as $_comment) {
					if($_comment['user_id']) {
						$this->_tables['users']->reset()->select()->where("`user_id` = " . $_comment['user_id'])->limit(1);
						$_row = $this->_tables['users']->fetchAll(\PDO::FETCH_ASSOC);
						$_comment['user'] = $_row[0];
					}
				}
			}
			foreach($_comments as $i=>$_comment) {
				
				if ($_comment['user_id']) {
					if($query['bulk'] == 'full') {
						$this->_tables['users']->reset()->select()->where("`user_id` = " . $_comment['user_id'])->limit(1);
						$_row = $this->_tables['users']->fetchAll(\PDO::FETCH_ASSOC);
						$_comments[$i]['user'] = $_row[0];
						$_comments[$i]['user']['cnt_comments_in'] = $this->_tables['profile.media_comments']
														  ->query("SELECT COUNT(*) FROM `media_likes` WHERE `user_id`=" .
														  		$_comment['user_id'])
														  ->fetchColumn();
					}
				}
			}
			
			return $_comments;
		}
		
		public function GET__likes ($params, $query) {
			if(!isset($query) && isset($params)) {
				$query = $params;
				unset($params);
			}
			
			if(!$query['limit'])
				$query['limit'] = 3;
			
			if(!$query['bulk'])
				$query['bulk'] = 'compact';
				 
			$_likes = $this->_tables['profile.media_likes']->reset()
						->select()
						->limit($query['limit'])
						->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach($_likes as $i=>$_like) {
				if($_like['user_id']) {
					if($query['bulk'] == 'full') {
						$this->_tables['users']->reset()->select()->where("`user_id` = " . $_like['user_id'])->limit(1);
						$_row = $this->_tables['users']->fetchAll(\PDO::FETCH_ASSOC);
						$_likes[$i]['user'] = $_row[0];
						$_likes[$i]['user']['cnt_likes_in'] = $this->_tables['profile.media_comments']->query("SELECT COUNT(*) FROM `media_likes` WHERE `user_id`=" . $_like['user_id'])->fetchColumn();
					}
				}
			}
			
			return $_likes;
		}
		
		public function GET__countsLastModify ($params, $query) {
			if(!isset($query) && isset($params)) {
				$query = $params;
				unset($params);
			}
			
			$_ts = $this->_tables['profile.counts']
						->select("date")
						->order('`date` desc')
						->fetchColumn();
			
			return date("c", $_ts);
		}
	}
?>