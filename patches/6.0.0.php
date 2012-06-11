<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// twitter_message 

if(!isset($tables['twitter_message'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS twitter_message (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			twitter_id VARCHAR(128) DEFAULT '',
			twitter_user_id VARCHAR(128) DEFAULT '',
			user_name VARCHAR(128) DEFAULT '',
			user_screen_name VARCHAR(128) DEFAULT '',
			user_followers_count INT UNSIGNED NOT NULL DEFAULT 0,
			user_profile_image_url VARCHAR(255) NOT NULL DEFAULT '',
			created_date INT UNSIGNED NOT NULL DEFAULT 0,
			is_closed TINYINT UNSIGNED NOT NULL DEFAULT 0,
			content VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			INDEX created_date (created_date)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['twitter_message'] = 'twitter_message';
}

// ===========================================================================
// Enable scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('wgmtwitter.cron', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 23:45'));
}

return TRUE;