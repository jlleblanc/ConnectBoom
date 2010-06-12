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
 File: ud_169.php
-----------------------------------------------------
 Purpose: Performs version 1.6.9 update
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class Updater {

	function do_update()
	{
		// This update corresponds to the 2.0.2 release in the 2.x branch.
		// Some of the changes made in this update can also be found in
		// said update on the other branch.
		
		global $DB;

		// If they have existing Pages, saved array needs to be updated to new format
		$Q = array();
		$fields = array();
		
		$field_query = $DB->query("SHOW COLUMNS FROM exp_sites");
		
		foreach ($field_query->result as $row)

		{
			$fields[] = $row['Field'];
		}

		if (in_array('site_pages', $fields)) 
		{
			$query = $DB->query("SELECT site_id, site_pages, site_system_preferences 
								 FROM exp_sites
								 WHERE site_pages != ''");	

			if ($query->num_rows > 0)
			{
 				foreach ($query->result as $row)
				{
					$system_prefs =  $row['site_system_preferences'];
					$old_pages = $row['site_pages'];

					if ( ! is_string($old_pages) OR substr($old_pages, 0, 2) != 'a:')
					{
						// Error or will lose data- which is borked!
						continue;
					}
					else
					{
						$new_pages[$row['site_id']] = $this->array_stripslashes(unserialize($old_pages));	
					}
					
					if ( ! is_string($system_prefs) OR substr($system_prefs, 0, 2) != 'a:')
					{
						$new_pages[$row['site_id']]['url'] = '';
					}
					else
					{
						$prefs = $this->array_stripslashes(unserialize($system_prefs));
						
						$url = (isset($prefs['site_url'])) ? $prefs['site_url'].'/' : '/';
						$url .= (isset($prefs['site_index'])) ? $prefs['site_index'].'/' : '/';

						$new_pages[$row['site_id']]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);						
					}
					
					$Q[] = "UPDATE exp_sites SET site_pages = '".addslashes(serialize($new_pages))."' WHERE site_id = '".$row['site_id']."'";
					
					unset($new_pages);
				}
			}
		}

		$Q[] = "ALTER TABLE `exp_password_lockout` ADD `username` VARCHAR(50) NOT NULL AFTER `user_agent`";
		
		// run our queries
		foreach ($Q as $sql)
		{
			$DB->query($sql);
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