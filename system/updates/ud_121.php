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
 File: ud_121.php
-----------------------------------------------------
 Purpose: Performs version 1.2.1 update
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
		
        $Q[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Weblog', 'insert_new_entry')";

        $DB->fetch_fields = TRUE;
        $query = $DB->query("SELECT * FROM exp_member_groups");
        $flag = FALSE;
        
		foreach ($query->fields as $field)
		{
			if ($field == 'can_assign_post_authors')
			{
				$flag = TRUE;
			}		
		}
        
        if ($flag == FALSE)
        {
			$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_assign_post_authors char(1) NOT NULL default 'n'";
		}
       
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN trackback_system_enabled char(1) NOT NULL default 'y'";
	
		// Run the queries
		
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
	
		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/
		
		$data = array(
                    		'max_tmpl_revisions' => '',
                    		'captcha_rand' => 'n',
                    		'remap_pm_urls' => 'n',
                    		'remap_pm_dest'	=> '',
                    		'new_version_check' => 'y',
                    		'max_referrers'		=> '500'
					);
													
		$UD->append_config_file($data);
		
		
		return TRUE;
	}
	
		
}	
// END CLASS



?>