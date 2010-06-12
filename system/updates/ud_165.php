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
 File: ud_165.php
-----------------------------------------------------
 Purpose: Performs version 1.6.5 update
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class Updater {

	function do_update()
	{
		global $DB, $UD, $conf, $REGX;
		
		$Q[] = "ALTER TABLE `exp_search` CHANGE `query` `query` MEDIUMTEXT NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_search` CHANGE `custom_fields` `custom_fields` MEDIUMTEXT NULL DEFAULT NULL";
		
		$Q[] = "ALTER TABLE `exp_templates` ADD `last_author_id` INT(10) UNSIGNED NOT NULL AFTER `edit_date`";
		$Q[] = "ALTER TABLE `exp_revision_tracker` ADD `item_author_id` INT(10) UNSIGNED NOT NULL AFTER `item_date`";
		
		$query = $DB->query('SHOW FIELDS FROM exp_weblog_data');

		foreach ($query->result as $row)
		{
			if (strncmp($row['Field'], 'field_ft', 8) == 0)
			{
				$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `{$row['Field']}` `{$row['Field']}` TINYTEXT NULL";
			}
		}

		// run our queries
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
		
		// for the benefit of Mr Kite! Okay, for the benefit of the Prefs class.
		if ( ! defined('REQ'))
		{
			define('REQ', 'UPDATE');
		}
		
		require PATH_CORE.'core.regex'.EXT;
	    $REGX = new Regex();
		
		require PATH_CORE.'core.prefs'.EXT;        				

		// We need to add a new template preference, so we'll fetch the existing site template prefs
		$query = $DB->query("SELECT site_name, site_id, site_template_preferences FROM exp_sites");

		foreach ($query->result as $row)
		{
			$PREFS = new Preferences();		
			$PREFS->site_prefs($row['site_name'], $row['site_id']);
		
			$prefs = $this->array_stripslashes(unserialize($row['site_template_preferences']));

			// Add our new pref to the array
			$prefs['strict_urls'] = ($PREFS->ini('site_404') == FALSE) ? 'n' : 'y';
				
			// Update the DB
			$DB->query($DB->update_string('exp_sites', array('site_template_preferences' => addslashes(serialize($prefs))), "site_id = '".$row['site_id']."'"));
		}

		return TRUE;

	}
	/* END */


     function array_stripslashes($vals)
     {
     	if (is_array($vals))
     	{	
     		foreach ($vals as $key=>$val)
     		{
     			$vals[$key] = $this->array_stripslashes($val);
     		}
     	}
     	else
     	{
     		$vals = stripslashes($vals);
     	}
     	
     	return $vals;
	}
	
}	
/* END CLASS */


?>