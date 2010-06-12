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
 File: ud_132.php
-----------------------------------------------------
 Purpose: Performs version 1.3.2 update
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
		
		
		$Q[] = "CREATE TABLE exp_mailing_lists (
		 list_id int(7) unsigned NOT NULL auto_increment,
		 list_name varchar(40) NOT NULL,
		 list_title varchar(100) NOT NULL,
		 PRIMARY KEY (list_id),
		 KEY (list_name)
		)";
		
		$Q[] = "CREATE TABLE exp_email_cache_ml (
		  cache_id int(6) unsigned NOT NULL,
		  list_id smallint(4) NOT NULL,
		  KEY (cache_id)
		)";
		
		$Q[] = "INSERT INTO exp_mailing_lists(list_id, list_name, list_title) values ('', 'default', 'Default Mailing List')";
		$Q[] = "ALTER TABLE exp_mailing_list ADD COLUMN list_id int(7) unsigned default '0' NOT NULL";				
		$Q[] = "ALTER TABLE exp_mailing_list_queue ADD COLUMN list_id int(7) unsigned default '0' NOT NULL";	
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN weblog_notify char(1) NOT NULL default 'n'";	
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN weblog_notify_emails varchar(255) NOT NULL";	
		$Q[] = "ALTER TABLE exp_weblogs CHANGE COLUMN blog_url blog_url varchar(100) NOT NULL";
		$Q[] = "UPDATE exp_mailing_list SET list_id = 1";		
		$Q[] = "ALTER TABLE exp_mailing_list DROP PRIMARY KEY";
		$Q[] = "ALTER TABLE exp_mailing_list ADD INDEX(list_id)";
		$Q[] = "ALTER TABLE exp_mailing_list ADD INDEX(email)";
		$Q[] = "insert into exp_specialty_templates(template_id, template_name, data_title, template_data) values ('', 'admin_notify_entry', '".addslashes(trim($this->admin_notify_entry_title()))."', '".addslashes($this->admin_notify_entry())."')";		
		
		// Run the queries
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
		
	
		$data['publish_tab_behavior'] = "hover";
		
		$UD->append_config_file($data);
		
		return TRUE;
	}
	
//---------------------------------------------------
//	Admin Notification of New Entry
//--------------------------------------------------

function admin_notify_entry_title()
{
return <<<EOF
A new weblog entry has been posted
EOF;
}

function admin_notify_entry()
{
return <<<EOF
A new entry has been posted in the following weblog:
{weblog_name}

The title of the entry is:
{entry_title}

Posted by: {name}
Email: {email}

To read the entry please visit: 
{entry_url}

EOF;
}
/* END */

		
}	
// END CLASS

?>