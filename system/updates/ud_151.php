<?php
/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: ud_151.php
-----------------------------------------------------
 Purpose: Performs version 1.5.1 update
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class Updater {

	function do_update()
	{
		global $DB, $UD, $conf;
	

		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`group_id`);";
		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`unique_id`);";
		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`password`);";
		$Q[] = "ALTER TABLE `exp_sessions` ADD INDEX (`member_id`);";
		$Q[] = "ALTER TABLE `exp_template_no_access` ADD INDEX (`template_id`);";
		$Q[] = "ALTER TABLE `exp_trackbacks` ADD INDEX (`weblog_id`);";
		
		// pMachine News Feed for Control Panel homepage
		$Q[] = "ALTER TABLE exp_member_homepage ADD `pmachine_news_feed` char(1) NOT NULL default 'l'";
		$Q[] = "ALTER TABLE exp_member_homepage ADD `pmachine_news_feed_order` int(3) NOT NULL default '0'";
		
		// Run the queries
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
		
		return TRUE;
	}
	/* END */
	
}	
// END CLASS


?>