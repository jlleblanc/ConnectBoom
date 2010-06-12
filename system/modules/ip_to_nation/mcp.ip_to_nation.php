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
 File: mcp.Nation_ban.php
-----------------------------------------------------
 Purpose: Loads an SQL table containing the all availabe
 IP addresses.  An admin to ban an entire country
 from being permitted to post comments
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Ip_to_nation_CP {

    var $version = '1.4';
   

    /** -------------------------
    /**  Constructor
    /** -------------------------*/
    
    function Ip_to_nation_CP($switch = TRUE)
    {
        global $IN, $DB;
        
        
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT module_version FROM exp_modules WHERE module_name = 'Ip_to_nation'");
        
        if ($query->num_rows == 0)
        {
        	return;
        }
        
        /* Is the version current? */
        
        if ($query->row['module_version'] != $this->version)
        {
			$this->update_module($query->row['module_version']);
        }
		// --       
        
        if ($switch)
        {
            switch($IN->GBL('P'))
            {
                case 'banlist'	:  $this->ip_to_nation_banlist();
                	break;
                case 'update'	:  $this->ip_to_nation_update();
                	break;
               default			:  $this->ip_to_nation_home();
                    break;
            }
        }
    }
    /* END */
    
   
    /** ----------------------------------------
    /**  Nation Ban Home Page
    /** ----------------------------------------*/

    function ip_to_nation_home()
    {
        global $DB, $DSP, $OUT, $LANG, $REGX;
    
    	if ( ! include_once(PATH_LIB.'countries.php'))
		{
			$DSP->error_message($LANG->line('countryfile_missing'));
			return;
		}

		$DSP->title  = $LANG->line('ip_to_nation_module_name');
		$DSP->crumb  = $LANG->line('ip_to_nation_module_name');
			
		$ip = (isset($_POST['ip']) AND $REGX->valid_ip($_POST['ip'])) ? $_POST['ip'] : '';
		
		$country = '';
		
		if ($ip != '')
		{
			$query = $DB->query("SELECT country FROM exp_ip2nation WHERE ip < INET_ATON('".$DB->escape_str(trim($ip))."') ORDER BY ip DESC LIMIT 0,1");
		
			if ($query->num_rows == 1)
			{
				if (@isset($countries[$query->row['country']]))
				{
					$country = $countries[$query->row['country']];
				}
			}
		}
		
		
		$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=ip_to_nation', 'method' => 'post'));
		
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('ip_search'));			
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('ip_search_inst'));
		
		if ($country != '')
		{
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qspan('highlight_alt', '<b>'.$LANG->line('ip_result').'&nbsp;&nbsp;</b>').$DSP->qspan('defaultBold', $country));
		}
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('ip', $ip, '35', '100', 'input', '300px'));
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit')));
		$DSP->body .= $DSP->div_c();                        
		$DSP->body .= $DSP->form_close();   

		$DSP->body .= $DSP->qdiv('box', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=ip_to_nation'.AMP.'P=banlist', '<b>'.$LANG->line('manage_banlist').'</b>'));

	}
	/* END */
	

    /** ----------------------------------------
    /**  Ban list table
    /** ----------------------------------------*/

	function ip_to_nation_banlist($updated = FALSE)
	{
		global $DB, $DSP, $LANG;

    	if ( ! include(PATH_LIB.'countries.php'))
		{
			$DSP->error_message($LANG->line('countryfile_missing'));
			return;
		}
		
		$DSP->title  = $LANG->line('ip_to_nation_module_name');
		$DSP->crumb .= $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=ip_to_nation', $LANG->line('ip_to_nation_module_name')).$DSP->crumb_item($LANG->line('banlist'));
			
        $query = $DB->query("SELECT * FROM exp_ip2nation_countries");
		$status = array();        
        foreach ($query->result as $row)
        {
        	$status[$row['code']] = $row['banned'];
        }
        
        
		$DSP->body = $DSP->qdiv('tableHeading', $LANG->line('banlist'));        
		$DSP->body .= $DSP->qdiv('box', $LANG->line('ban_info'));
		
		if ($updated == TRUE)
		{
			$DSP->body .= $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('banlist_updated')));
		}

        $DSP->body	.=	$DSP->toggle();
        $DSP->body_props .= ' onload="magic_check()" ';
        $DSP->body .= $DSP->magic_checkboxes();
        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=ip_to_nation'.AMP.'P=update', 'name' => 'target', 'id' => 'target'));

		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$DSP->body .= $DSP->table_row(array(
									array(
											'text'	=> $LANG->line('ban'),
											'class'	=> 'tableHeadingAlt'
										),
									array(
											'text'	=> $LANG->line('country'),
											'class'	=> 'tableHeadingAlt'
										)
									)
								);
		
		$i = 0;
		foreach ($countries as $key => $val)
		{   
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$DSP->body .= $DSP->table_row(array(
										array(
												'text'	=> $DSP->input_checkbox($key, 'y', $status[$key], " id='code_".$key."'"),
												'class'	=> $style,
												'width'	=> '5%'
											),			
										array(
												'text'	=> $DSP->qdiv('defaultBold', $val),
												'class'	=> $style,
												'width'	=> '95%'
											)
									)
								);
		}
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$DSP->body .= $DSP->table_row(array(
									array(
											'text'		=> $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update'))),
											'class'		=> $style,
											'colspan'	=> '2'
										)
									)
								);
	
		
        $DSP->body	.=	$DSP->table_close(); 
        $DSP->body	.=	$DSP->form_close();  
	}
	/* END */



    /** ----------------------------------------
    /**  Update Ban List
    /** ----------------------------------------*/
    
	function ip_to_nation_update()
	{
		global $DB, $DSP;
		
    	if ( ! include(PATH_LIB.'countries.php'))
		{
			$DSP->error_message($LANG->line('countryfile_missing'));
			return;
		}
		
		
		$codestr = '';
		foreach ($_POST as $key => $val)
		{
			if (isset($countries[$key]) AND $val == 'y')
			{
				$codestr .= "'".$key."',";
			}
		}
		
		$DB->query("UPDATE exp_ip2nation_countries SET banned = 'n'");
		
		if ($codestr != '')
		{
			$codestr = substr($codestr, 0, -1);			
			$DB->query("UPDATE exp_ip2nation_countries SET banned = 'y' WHERE CODE IN(".$codestr.")");
			$updated = TRUE;
		}
		
		unset($countries);
		return $this->ip_to_nation_banlist(TRUE);		
	}
	/* END */


    /** ----------------------------------------
    /**  Module installer
    /** ----------------------------------------*/

    function Ip_to_nation_module_install()
    {
        global $DB, $DSP, $OUT, $LANG;
        
		if ( ! include_once(PATH_MOD.'ip_to_nation/iptonation.php'))
		{
			$LANG->fetch_language_file('ip_to_nation');
			$DSP->error_message($LANG->line('iptonation_missing'));	
			$DSP->show_full_control_panel();
			$OUT->display_final_output();
			exit;
		}
        
		$sql[] = "DROP TABLE IF EXISTS exp_ip2nation;";

		$sql[] = "CREATE TABLE exp_ip2nation (
		  ip int(11) unsigned NOT NULL default '0',
		  country char(2) NOT NULL default '',
		  KEY ip (ip)
		);";

		$sql[] = "DROP TABLE IF EXISTS exp_ip2nation_countries;";

		$sql[] = "CREATE TABLE exp_ip2nation_countries (
		  code varchar(2) NOT NULL default '',
		  banned varchar(1) NOT NULL default 'n',
		  KEY code (code)
		);";
		
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Ip_to_nation', '$this->version', 'y')";
    
        foreach ($sql as $query)
        {
			$DB->query($query);
        }
        
		// Insert the massive number of records
				
		for ($i = 0, $total = count($cc); $i < $total; $i = $i + 100)
		{
			$DB->query("INSERT INTO exp_ip2nation_countries (code) VALUES ('".implode("'), ('", array_slice($cc, $i, 100))."')");
		}

		for ($i = 0, $total = count($ip); $i < $total; $i = $i + 100)
		{
			$DB->query("INSERT INTO exp_ip2nation (ip, country) VALUES (".implode("), (", array_slice($ip, $i, 100)).")");
		}

		/** ----------------------------------------
		/**  Add a flag to the config file
		/** ----------------------------------------*/
	  
		if ( ! class_exists('Admin'))
		{
			require PATH_CP.'cp.admin'.EXT;
		}
        
		Admin::append_config_file(array('ip2nation' => 'y'));
        
        return true;
    }
    /* END */
    
    
    /** ----------------------------------------
    /**  Module de-installer
    /** ----------------------------------------*/

    function Ip_to_nation_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Ip_to_nation'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Ip_to_nation'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Ip_to_nation'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Ip_to_nation'";
		$sql[] = "DROP TABLE IF EXISTS exp_ip2nation;";
		$sql[] = "DROP TABLE IF EXISTS exp_ip2nation_countries;";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
		/** ----------------------------------------
		/**  Remove the flag from the config file
		/** ----------------------------------------*/
	  
		if ( ! class_exists('Admin'))
		{
			require PATH_CP.'cp.admin'.EXT;
		}
		
		Admin::append_config_file('', array('ip2nation'));

        return true;
    }
    /* END */

	
	/** ---------------------------------------
	/**  Module updater
	/** ---------------------------------------*/

	function update_module($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		// Version 1.3 uses data based on 11/02/2009 sql from ip2nation.com
		
		if ($current < 1.3)
		{
			if ( ! include_once(PATH_MOD.'ip_to_nation/iptonation.php'))
			{
				$LANG->fetch_language_file('ip_to_nation');
				$DSP->error_message($LANG->line('iptonation_missing'));	
				$DSP->show_full_control_panel();
				$OUT->display_final_output();
				exit;
			}
			
			// Fetch banned nations
			
			$query = $DB->query("SELECT code FROM exp_ip2nation_countries WHERE banned='y'");
			
			// Truncate tables
			
			$DB->query("TRUNCATE `exp_ip2nation_countries`");
			$DB->query("TRUNCATE `exp_ip2nation`");
			
			// Re-insert the massive number of records

			for ($i = 0, $total = count($cc); $i < $total; $i = $i + 100)
			{
				$DB->query("INSERT INTO exp_ip2nation_countries (code) VALUES ('".implode("'), ('", array_slice($cc, $i, 100))."')");
			}

			for ($i = 0, $total = count($ip); $i < $total; $i = $i + 100)
			{
				$DB->query("INSERT INTO exp_ip2nation (ip, country) VALUES (".implode("), (", array_slice($ip, $i, 100)).")");
			}
			
			// update banned nations
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$DB->query($DB->update_string('exp_ip2nation_countries', array('banned' => 'y'), array('code' => $row['code'])));
				}						
			}
		}
		
    	$DB->query("UPDATE exp_modules SET module_version = '{$this->version}' WHERE module_name = 'Ip_to_nation'");

		return TRUE;
	}
	/* END */
	

}
// END CLASS
?>