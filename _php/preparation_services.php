<?php
require_once dirname(__FILE__) . '/_lib/SQLite/DB.php';

	$user_id = (int)$argv[1];
//	$client_id = $argv[2];
	if(!$user_id) exit();
	
	// user dir
	$user_dir = SQLite\DB::STORAGE . '/' . $user_id;
	if(!is_dir($user_dir)) mkdir($user_dir, 0755);
	
	
	/**
	 * Profile DB
	 */
	$dbProfile = new SQLite\DB('profile', $user_id);
	
	// Tables
	$dbProfile
	->begin()
	->createTable('counts', array(
		'date' => 'pk',
		'cnt_media' => 'integer',
		'cnt_followed_by' => 'integer',
		'cnt_follows' => 'integer',
		'cnt_likes' => 'integer',
		'cnt_comments' => 'integer'
	), true)
	->createTable('media_list', array(
		'media_id' => 'INTEGER PRIMARY KEY',
		'type' => 'string',
		'filter' => 'string',
		'cnt_likes' => 'integer',
		'cnt_comments' => 'integer',
		'location_id' => 'integer',
		'created_time' => 'timestamp',
		'updated_time' => 'timestamp',
		'has_comments_parse' => 'boolean',
		'has_likes_parse' => 'boolean'
	), true)
	->createTable('media_comments', array(
		'comment_id' => 'INTEGER PRIMARY KEY',
		'text' => 'text',
		'media_id' => 'integer',
		'user_id' => 'integer',
		'created_time' => 'timestamp'
	), true)
	->createTable('media_likes', array(
		'media_id' => 'integer',
		'user_id' => 'integer',
		'created_time' => 'timestamp'
	), true)
	->createTable('tags', array(
		'tag_id' => 'integer',
		'media_id' => 'integer'
	), true)
	->commit();
	
	// Triggers
	$dbProfile->exec("
		CREATE TRIGGER `tags__onInsertBefore` BEFORE INSERT ON `tags`
		WHEN EXISTS (SELECT * FROM `tags` WHERE `tag_id`=NEW.tag_id AND `media_id`=NEW.media_id)
		BEGIN
			SELECT RAISE(IGNORE);
		END;");
	$dbProfile->exec("
		CREATE TRIGGER IF NOT EXISTS `media_list__onInsertBefore` BEFORE INSERT ON `media_list`
		WHEN EXISTS (SELECT `media_id` FROM `media_list` WHERE `media_id`=NEW.media_id)
		BEGIN
			UPDATE `media_list` SET `updated_time` = strftime('%s',current_timestamp), `cnt_likes`=NEW.cnt_likes, `cnt_comments`=NEW.cnt_comments
			WHERE `media_id`=NEW.media_id;
			SELECT RAISE(IGNORE);
		END;");
	$dbProfile->exec("
		CREATE TRIGGER IF NOT EXISTS `media_likes__onInsertBefore` BEFORE INSERT ON `media_likes`
		WHEN EXISTS (SELECT * FROM `media_likes` WHERE `media_id`=NEW.media_id AND `user_id`=NEW.user_id)
		BEGIN
			SELECT RAISE(IGNORE);
		END;");
	$dbProfile->exec("
		CREATE TRIGGER IF NOT EXISTS `media_comments__onInsertBefore` BEFORE INSERT ON `media_comments`
		WHEN EXISTS (SELECT * FROM `media_comments` WHERE `comment_id`=NEW.comment_id)
		BEGIN
			SELECT RAISE(IGNORE);
		END;");
	$dbProfile->exec("
		CREATE TRIGGER IF NOT EXISTS `counts__onInsertBefore` BEFORE INSERT ON `counts`
		WHEN EXISTS (SELECT `date` FROM `counts` WHERE `date`=NEW.date)
		BEGIN
			UPDATE `counts` 
			SET 
				`cnt_media`=NEW.cnt_media,
				`cnt_followed_by`=NEW.cnt_followed_by,
				`cnt_follows`=NEW.cnt_follows,
				`cnt_likes`=NEW.cnt_likes,
				`cnt_comments`=NEW.cnt_comments
			WHERE `date`=NEW.date;
			SELECT RAISE(IGNORE);
		END;");
	
	/**
	 * Requests DB
	 */
	$dbRequests = new SQLite\DB('related_requests', $user_id);
	$dbRequests->createTable('requests', array(
		'request_id' => 'pk',
		'type' => 'string',
		'user_id' => 'integer',
		'next_max_id' => 'string',
		'completed' => 'boolean',
		'in_photo' => 'boolean',
		'outgoing_status' => 'string',
		'incoming_status' => 'string'
	), true);
	// DROP TRIGGER `related_requests__onInsertBefore`;
    $dbRequests->exec("
		CREATE TRIGGER IF NOT EXISTS `related_requests__onInsertBefore` BEFORE INSERT ON `requests`
		WHEN EXISTS (SELECT * FROM `requests` WHERE `user_id`=NEW.user_id AND `type`=NEW.type)
		BEGIN
			UPDATE `requests` 
			SET 
				`in_photo`=NEW.in_photo,
				`outgoing_status`=NEW.outgoing_status,
				`incoming_status`=NEW.incoming_status
			WHERE `user_id`=NEW.user_id AND `type`=NEW.type;
			SELECT RAISE(IGNORE);
		END;");
	
	/**
	 * Realted users DB
	 */
	$dbUsers = new SQLite\DB('related_users', $user_id);
	
	// Tables
	$dbUsers
	->begin()
	->createTable('users', array(
		'user_id' => 'INTEGER PRIMARY KEY',
		'outgoing_status' => 'string',
		'incoming_status' => 'string',
		'is_private' => 'boolean',
		'in_photo' => 'boolean',
		'created_time' => 'timestamp',
		'updated_outgoing' => 'timestamp',
		'updated_incoming' => 'timestamp'
	), true)
	->createTable('counts', array(
		'date' => 'timestamp',
		'user_id' => 'integer',
		'cnt_media' => 'integer',
		'cnt_followed_by' => 'integer',
		'cnt_follows' => 'integer',
		'cnt_likes' => 'integer',
		'cnt_comments' => 'integer',
		'cnt_likes_in' => 'integer',
		'cnt_comments_in' => 'integer'
	), true)
	->commit();
	
	// Triggers
	// DROP TRIGGER `users__onInsertBefore`;
	$dbUsers->exec("
		CREATE TRIGGER IF NOT EXISTS `users__onInsertBefore` BEFORE INSERT ON `users`
		WHEN EXISTS (SELECT `user_id` FROM `users` WHERE `user_id`=NEW.user_id)
		BEGIN
			UPDATE `users` 
			SET 
				`outgoing_status`=NEW.outgoing_status,
				`incoming_status`=NEW.incoming_status,
				`is_private`=NEW.is_private,
				`in_photo`=NEW.in_photo
			WHERE `user_id`=NEW.user_id;
			SELECT RAISE(IGNORE);
		END;");
	// DROP TRIGGER `users__onUpdateOutgoingStatus`;
	$dbUsers->exec("
		CREATE TRIGGER IF NOT EXISTS `users__onUpdateOutgoingStatus` UPDATE OF `outgoing_status` ON `users`
		BEGIN
			UPDATE `users` SET `updated_outgoing` = strftime('%s', current_timestamp) 
			WHERE 
				OLD.`outgoing_status` != NEW.`outgoing_status`
				AND `user_id` = OLD.`user_id`;
		END;");
	// DROP TRIGGER `users__onUpdateIncomingStatus`;
	$dbUsers->exec("
		CREATE TRIGGER IF NOT EXISTS `users__onUpdateIncomingStatus` UPDATE OF `incoming_status` ON `users`
		BEGIN
			UPDATE `users` SET `updated_incoming` = strftime('%s', current_timestamp) 
			WHERE 
				OLD.`incoming_status` != NEW.`incoming_status`
				AND `user_id` = OLD.`user_id`;
		END;");
	
	// DROP TRIGGER `counts__onInsertBefore`;
	$dbUsers->exec("
		CREATE TRIGGER IF NOT EXISTS `counts__onInsertBefore` BEFORE INSERT ON `counts`
		WHEN EXISTS (SELECT `date` FROM `counts` WHERE `date`=NEW.date AND `user_id`=NEW.user_id)
		BEGIN
			UPDATE `counts` 
			SET 
				`cnt_media`=NEW.cnt_media,
				`cnt_followed_by`=NEW.cnt_followed_by,
				`cnt_follows`=NEW.cnt_follows,
				`cnt_likes`=NEW.cnt_likes,
				`cnt_comments`=NEW.cnt_comments,
				`cnt_likes_in`=NEW.cnt_likes_in,
				`cnt_comments_in`=NEW.cnt_comments_in
			WHERE `date`=NEW.date AND `user_id`=NEW.user_id;
			SELECT RAISE(IGNORE);
		END;");
			
	
	/**
	 * Related media DB
	 */
	$dbMedia = new SQLite\DB('related_media', $user_id);
	
	// Tables
	$dbMedia
	->begin()
	->createTable('media_list', array(
		'media_id' => 'INTEGER PRIMARY KEY',
		'user_id' => 'integer',
		'type' => 'string',
		'filter' => 'string',
		'cnt_likes' => 'integer',
		'cnt_comments' => 'integer',
		'location_id' => 'integer',
		'created_time' => 'timestamp',
		'updated_time' => 'timestamp'
	), true)
	->createTable('tags', array(
		'tag_id' => 'integer',
		'media_id' => 'integer',
		'user_id' => 'integer'
	), true)
	->commit();
	
	// Triggers
	// DROP TRIGGER `media_list__onInsertBefore`;
	$dbMedia->exec("
		CREATE TRIGGER IF NOT EXISTS `media_list__onInsertBefore` BEFORE INSERT ON `media_list`
		WHEN EXISTS (SELECT `media_id` FROM `media_list` WHERE `media_id`=NEW.media_id AND `user_id`=NEW.user_id)
		BEGIN
			UPDATE `media_list` SET `updated_time` = strftime('%s',current_timestamp), `cnt_likes`=NEW.cnt_likes, `cnt_comments`=NEW.cnt_comments
			WHERE `media_id`=NEW.media_id AND `user_id`=NEW.user_id;
			SELECT RAISE(IGNORE);
		END;");
	// DROP TRIGGER `tags__onInsertBefore`;
	$dbMedia->exec("
		CREATE TRIGGER IF NOT EXISTS `tags__onInsertBefore` BEFORE INSERT ON `tags`
		WHEN EXISTS (SELECT * FROM `tags` WHERE `tag_id`=NEW.tag_id AND `media_id`=NEW.media_id AND `user_id`=NEW.user_id)
		BEGIN
			SELECT RAISE(IGNORE);
		END;");
	
?>