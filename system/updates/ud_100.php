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
 File: ud_100.php
-----------------------------------------------------
 Purpose: Perform version 1.0 update
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
		
		$Q[] = "CREATE TABLE IF NOT EXISTS exp_captcha (
		 date int(10) unsigned NOT NULL,
		 ip_address varchar(16) default '0' NOT NULL,
		 word varchar(20) NOT NULL,
		 KEY (word)
		)";

		// status no access table
		
		$Q[] = "CREATE TABLE IF NOT EXISTS exp_status_no_access (
		 status_id int(6) unsigned NOT NULL,
		 member_group tinyint(3) unsigned NOT NULL
		)";

		// Field formatting
				
		$Q[] = "CREATE TABLE IF NOT EXISTS exp_field_formatting (
		 field_id int(10) unsigned NOT NULL,
		 field_fmt varchar(40) NOT NULL,
		 KEY (field_id)
		)";
		
		
		// Define the table changes

		$Q[] = "insert into exp_specialty_templates(template_id, template_name, data_title, template_data) values ('', 'decline_member_validation', '".addslashes(trim(decline_member_validation_title()))."', '".addslashes(decline_member_validation())."')";
		
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN exclude_from_moderation char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_security_hashes ADD COLUMN ip_address varchar(16) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_use_captcha char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_upload_prefs ADD COLUMN weblog_id int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_search ADD COLUMN keywords varchar(60) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_show_fmt char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblog_fields CHANGE COLUMN field_fmt field_fmt varchar(40) NOT NULL default 'xhtml'";



		$Q[] = "UPDATE exp_member_groups set exclude_from_moderation = 'y' WHERE group_id = '1'";
		
		// Run the queries
		
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}

		$query = $DB->query("SELECT field_id FROM exp_weblog_fields");

		foreach ($query->result as $row)
		{
			$DB->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'none')");
			$DB->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'br')");
			$DB->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'xhtml')");
		}

		
		$query = $DB->query("SELECT * FROM exp_weblog_data");

		foreach ($query->row as $key => $val)
		{
			if (substr($key, 0, 9) == 'field_ft_')
			{
				$id = substr($key, 9);
			
				$DB->query("ALTER TABLE exp_weblog_data CHANGE COLUMN field_ft_".$id." field_ft_".$id." varchar(40) NOT NULL default 'xhtml'");
			}
		}
	


		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$captcha_url = rtrim($conf['site_url'], '/').'/';

		$captcha_url .= 'images/captchas/';

		
		$data = array(
						'captcha_path'				=> './images/captchas/',
						'captcha_url'				=> $captcha_url,
						'captcha_font'				=> 'y',
						'use_membership_captcha'	=> 'n',
						'auto_convert_high_ascii'	=> 'n'
						
					);
								
		$UD->append_config_file($data);


		return TRUE;

	}
	
}	
// END CLASS




//---------------------------------------------------
//	Decline Member Validation
//--------------------------------------------------

function decline_member_validation_title()
{
return <<<EOF
Your membership account has been declined
EOF;
}

function decline_member_validation()
{
return <<<EOF
{name},

We're sorry but our staff has decided not to validate your membership.

{site_name}
{site_url}
EOF;
}
/* END */


?>