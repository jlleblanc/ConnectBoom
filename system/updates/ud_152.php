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
 File: ud_152.php
-----------------------------------------------------
 Purpose: Performs version 1.5.2 update
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
		
		$Q[] = "ALTER TABLE exp_members ADD `ignore_list` text not null AFTER `sig_img_height`";
		
		/*
		 * ------------------------------------------------------
		 *  Add Edit Date and Attempt to Intelligently Set Values
		 * ------------------------------------------------------
		 */
		require PATH_CORE.'core.localize'.EXT;
		$LOC = new Localize();
		
		$Q[] = "ALTER TABLE exp_templates ADD `edit_date` int(10) default 0 AFTER `template_notes`";
		$Q[] = "UPDATE exp_templates SET edit_date = '".$LOC->now."'";
		
		$query = $DB->query("SELECT item_id, MAX(item_date) as max_date FROM `exp_revision_tracker` GROUP BY item_id");
		
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$Q[] = "UPDATE exp_templates SET edit_date = '".$DB->escape_str($row['max_date'])."' WHERE template_id = '".$DB->escape_str($row['item_id'])."'";			
			}
		}
		
		/*
		 * ------------------------------------------------------
		 *  Add Hash for Bulletins and Set For Existing Bulletins
		 * ------------------------------------------------------
		 */
		
		$Q[] = "ALTER TABLE `exp_member_bulletin_board` ADD `hash` varchar(10) default '' AFTER `bulletin_date`";
		$Q[] = "ALTER TABLE `exp_member_bulletin_board` ADD INDEX (`hash`)";
		
		$query = $DB->query("SELECT DISTINCT bulletin_date, bulletin_message, sender_id FROM `exp_member_bulletin_board`");
		
		if ($query->num_rows > 0)
		{
			require PATH_CORE.'core.functions'.EXT;
			$FNS = new Functions();
		
			foreach($query->result as $row)
			{
				$Q[] = "UPDATE exp_member_bulletin_board SET hash = '".$DB->escape_str($FNS->random('alpha', 10))."' 
						WHERE bulletin_date = '".$DB->escape_str($row['bulletin_date'])."'
						AND bulletin_message = '".$DB->escape_str($row['bulletin_message'])."'
						AND sender_id = '".$DB->escape_str($row['sender_id'])."'";			
			}
		}
		
		// run the queries
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