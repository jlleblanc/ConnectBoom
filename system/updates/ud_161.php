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
 File: ud_161.php
-----------------------------------------------------
 Purpose: Performs version 1.6.1 update
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
		
		$query = $DB->query("SHOW tables LIKE 'exp_mailing_list'");
		
		if ($query->num_rows > 0)
		{
			$Q[] = "ALTER TABLE `exp_mailing_list` ADD `ip_address` VARCHAR(16) NOT NULL AFTER `list_id`";			
		}
		
		// Change default weblog preferences for trackbacks
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `enable_trackbacks` `enable_trackbacks` CHAR(1) NOT NULL DEFAULT 'n'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `trackback_system_enabled` `trackback_system_enabled` CHAR(1) NOT NULL DEFAULT 'n'";
		$Q[] = "ALTER TABLE `exp_weblog_titles` CHANGE `allow_trackbacks` `allow_trackbacks` CHAR(1) NOT NULL DEFAULT 'n'";
		
		// fix version number for Member module, which may be out of sync for old installations
		$Q[] = "UPDATE `exp_modules` SET `module_version` = '1.3' WHERE `module_name` = 'Member'";

		// Text formatting for emails from the Communicate page
		$Q[] = "ALTER TABLE `exp_email_cache` ADD `text_fmt` VARCHAR(40) NOT NULL AFTER `mailtype`";
		
		// Member Group setting for showing in Author List
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `include_in_authorlist` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_send_bulletins`";
		
		// Show All Tab in the Publish Area
		$Q[] = "ALTER TABLE `exp_weblogs` ADD `show_show_all_cluster` CHAR( 1 ) NOT NULL DEFAULT 'y' AFTER `show_pages_cluster`;";
		
		// "live" preview modifications
		$Q[] = "ALTER TABLE `exp_weblogs` ADD `live_look_template` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `url_title_prefix`";
		
		/** ---------------------------------------
		/**  Run Queries
		/** ---------------------------------------*/
		
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
		
		
		/** ---------------------------------------
		/**  Update the Config File
		/** ---------------------------------------*/

		//$data['x'] = "y";
		
		//$UD->append_config_file($data);
		
		unset($conf);
		include('config'.EXT);

		return TRUE;
	}
	/* END */
	
	
	
}	
/* END CLASS */


?>