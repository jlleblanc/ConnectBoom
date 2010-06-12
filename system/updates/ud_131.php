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
 File: ud_131.php
-----------------------------------------------------
 Purpose: Performs version 1.3.1 update
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

		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_forum_cluster char(1) NOT NULL default 'y'";				
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN forum_topic_id int(10) unsigned NOT NULL";				
		$Q[] = "ALTER TABLE `exp_members` ADD `accept_messages` CHAR(1) DEFAULT 'y' NOT NULL AFTER `private_messages`";		
			
		// Run the queries
		
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
		
		
		if ( ! isset($conf['enable_throttling']))
		{
			$data['enable_throttling'] = "y";
			$UD->append_config_file($data);
		}		
		
		return TRUE;
	}
	
		
}	
// END CLASS
?>