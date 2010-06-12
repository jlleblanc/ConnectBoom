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
 File: mcp.blacklist.php
-----------------------------------------------------
 Purpose: Blacklist class - CP
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Blacklist_CP {

    var $version	= '2.0';
    var $value		= '';
    var $LB			= "\r\n";


    /** -------------------------
    /**  Constructor
    /** -------------------------*/
    
    function Blacklist_CP( $switch = TRUE )
    {
        global $IN, $DB;
        
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Blacklist'");
        
        if ($query->row['count'] == 0)
        {
        	return;
        }
        
        /** -------------------------
		/**  Updates
		/** -------------------------*/
		
		$query = $DB->query("SELECT module_version FROM exp_modules WHERE module_name = 'Blacklist'");
		
		if ($query->num_rows > 0)
		{
			$sql = array();
			
			if ( ! $DB->table_exists('exp_whitelisted'))
			{
				$sql[] = "CREATE TABLE IF NOT EXISTS  `exp_whitelisted` (
						 `whitelisted_type` VARCHAR( 20  )  NOT  NULL ,
						 `whitelisted_value` TEXT  NOT  NULL);";
			}
			
			foreach($sql as $query)
			{
				$DB->query($query);
			}
		}
		
		/** -------------------------
		/**  Menu Switch
		/** -------------------------*/
        
        if ($switch)
        {
            switch($IN->GBL('P'))
            {
                case 'pmachine_blacklist'	:  $this->pmachine_blacklist();
                    break;
                case 'pmachine_whitelist'	:  $this->pmachine_whitelist();
                    break;
                case 'view_blacklist'		:  $this->view_blacklist();
                    break;
                case 'update_blacklist'		:  $this->update_blacklist();
                    break;
                case 'view_whitelist'		:  $this->view_whitelist();
                    break;
                case 'update_whitelist'		:  $this->update_whitelist();
                    break;
                case 'write_htaccess'		:  $this->write_htaccess();
                    break;
                default       				:  $this->blacklist_home();
                    break;
            }
        }
    }
    /* END */
    

    /** -------------------------
    /**  Blacklist Home Page
    /** -------------------------*/
    
    function blacklist_home($message='')
    {
        global $DSP, $DB, $LANG, $PREFS, $SESS;
                        
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->crumb = $LANG->line('blacklist_module_name');    
                
        // Blacklist
        
        if ($message != '')
        {
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));
        }
        
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr();

		$DSP->body	.=	$DSP->td('tableHeading', '', '2');
		$DSP->body	.=	$LANG->line('blacklist_module_name');
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body  .=  $DSP->tr();
	
		$DSP->body	.=	$DSP->td('tableCellTwo', '40%');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist'.AMP.'P=view_blacklist', $LANG->line('ref_view_blacklist')));
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->td('tableCellTwo', '60%');
       
        if ($license = $PREFS->ini('license_number'))
        {
        	$DSP->body .= $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist'.AMP.'P=pmachine_blacklist', $LANG->line('pmachine_blacklist')); 
		}
		else
		{
			$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('pmachine_blacklist')).$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('requires_license_number'))); 
		}
		
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();
		$DSP->body	.=	$DSP->td('tableCellOne');
		
		// Whitelist
		$DSP->body  .= 	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist'.AMP.'P=view_whitelist', $LANG->line('ref_view_whitelist')));
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->td('tableCellOne');

        if ($license = $PREFS->ini('license_number'))
        {
        	$DSP->body .= $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist'.AMP.'P=pmachine_whitelist', $LANG->line('pmachine_whitelist')); 
		}
		else
		{
			$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('pmachine_whitelist')).$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('requires_license_number'))); 
		}
		
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->tr_c();
		
		if ($SESS->userdata['group_id'] == '1')
		{
			$DSP->body	.=	$DSP->tr();
			$DSP->body	.=	$DSP->td('tableCellOne', '100%', '2');
			
			$htaccess_path = ( ! isset($_POST['htaccess_path']) OR $message == '') ? $PREFS->ini('htaccess_path') : $_POST['htaccess_path'];
			
			// .htaccess
			$DSP->body  .=  $DSP->form_open(array('action' => 'C=modules'.AMP.'M=blacklist'.AMP.'P=write_htaccess'));
			$DSP->body  .= 	$DSP->qdiv('defaultBold', $LANG->line('write_htaccess_file'));
			$DSP->body  .=  $DSP->qdiv('itemWrapper', $LANG->line('htaccess_server_path', 'htaccess_path'));
			$DSP->body  .=  $DSP->qdiv('itemWrapper', $DSP->input_text('htaccess_path', $htaccess_path, '35', '100', 'input', '400px'));
        	$DSP->body  .=  $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit')));
        	$DSP->body	.=  $DSP->form_close();
			$DSP->body	.=	$DSP->td_c();
			$DSP->body	.=	$DSP->tr_c();
		}	
		
		$DSP->body	.=	$DSP->table_c();
    }
    /* END */
    
    
    /** -------------------------
    /**  Write .htaccess File
    /** -------------------------*/
    
    function write_htaccess($redirect=TRUE)
    {
    	global $IN, $DB, $DSP, $LANG, $SESS, $PREFS, $EXT;
    	
    	if ($SESS->userdata['group_id'] != '1' OR $IN->GBL('htaccess_path') === FALSE) 
    	{
    		if ($redirect === FALSE) return;
    		
    		return $this->blacklist_home();
    	}
    	
		if ( ! file_exists($IN->GBL('htaccess_path')) OR ! is_writeable($IN->GBL('htaccess_path')))
		{
			$DSP->body .= $DSP->error_message($LANG->line('invalid_htaccess_path'));        
			return;
		}
		
		if ( ! class_exists('Admin'))
		{
			require PATH_CP.'cp.admin'.EXT;
		}
		
		if ($PREFS->ini('htaccess_path') !== FALSE)
        {
            Admin::update_config_file(array('htaccess_path' => $IN->GBL('htaccess_path')));
        }
        else
        {
            Admin::append_config_file(array('htaccess_path' => $IN->GBL('htaccess_path')));
        }
		
		if ( ! $fp = @fopen($IN->GBL('htaccess_path'), 'rb'))
		{
            $DSP->body .= $DSP->error_message($LANG->line('invalid_htaccess_path'));        
			return;
		}
		
        flock($fp, LOCK_SH);
        $data = @fread($fp, filesize($IN->GBL('htaccess_path')));
        flock($fp, LOCK_UN);
        fclose($fp);
        
        if (preg_match("/##EE Spam Block(.*?)##End EE Spam Block/s", $data, $match))
        {
        	$data = str_replace($match['0'], '', $data);
        }
        
        $data = trim($data);
        
        /** -----------------------------------------
        /**  Current Blacklisted
        /** -----------------------------------------*/
        
        $query			= $DB->query("SELECT * FROM exp_blacklisted");
        $old['url']		= array();
        $old['agent']	= array();
        $old['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$old_values = explode('|',trim($row['blacklisted_value']));
        		for ($i=0, $s = sizeof($old_values); $i < $s; $i++)
        		{
        			if (trim($old_values[$i]) != '')
        			{
        				//$old[$row['blacklisted_type']][] = $old_values[$i];
        				$old[$row['blacklisted_type']][] = preg_quote($old_values[$i]);
        			}
        		}       	
        	}
        }
        
        /** --------------------------------------------------
        /**  Right now we are only using URLs and IPs
        /** --------------------------------------------------*/
        
        $urls = '';
        
        while(sizeof($old['url']) > 0)
        {
        	$urls .= 'SetEnvIfNoCase Referer ".*('.trim(implode('|', array_slice($old['url'], 0, 50))).').*" BadRef'.$this->LB;	
        	
        	$old['url'] = array_slice($old['url'], 50);
        }
        
        $ips = '';
        
        while(sizeof($old['ip']) > 0)
        {
        	$ips .= 'SetEnvIfNoCase REMOTE_ADDR "^('.trim(implode('|', array_slice($old['ip'], 0, 50))).').*" BadIP'.$this->LB;
        	
        	$old['ip'] = array_slice($old['ip'], 50);
        }
        
        $site 	= parse_url($PREFS->ini('site_url'));
        
        $domain  = ( ! $PREFS->ini('cookie_domain')) ? '' : 'SetEnvIfNoCase Referer ".*('.preg_quote($PREFS->ini('cookie_domain')).').*" GoodHost'.$this->LB;
        
        $domain .= 'SetEnvIfNoCase Referer "^$" GoodHost'.$this->LB;  // If no referrer, they be safe!

        $host  = 'SetEnvIfNoCase Referer ".*('.preg_quote($site['host']).').*" GoodHost'.$this->LB;
        
        if ($urls != '' OR $ips != '')
        {
        	$data .= $this->LB.$this->LB."##EE Spam Block".$this->LB
        			.	$urls
        			.	$ips
        			.	$domain
        			.	$host
        			.	"order deny,allow".$this->LB
        			.	"deny from env=BadRef".$this->LB
        			.	"deny from env=BadIP".$this->LB
        			.	"allow from env=GoodHost".$this->LB
        			."##End EE Spam Block".$this->LB.$this->LB;
        }
        
        // -------------------------------------------
		// 'blacklist_write_htaccess_data' hook.
		//  - Modify what is written to the .htaccess file in Blacklist CP
		//
			if ($EXT->active_hook('blacklist_write_htaccess_data') === TRUE)
			{
				$data = $EXT->call_extension('blacklist_write_htaccess_data', $data, $old);
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
        
        if ( ! $fp = @fopen($IN->GBL('htaccess_path'), 'wb'))
        {
            $DSP->body .= $DSP->error_message($LANG->line('invalid_htaccess_path'));        
			return;
		}
		
        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);
        		
        if ($redirect === TRUE)
        {
        	return $this->blacklist_home($LANG->line('htaccess_written_successfully'));
        }
	}
	/* END */

    
    /** -------------------------
    /**  Update Blacklisted Items
    /** -------------------------*/
    
    function update_blacklist()
    {
    	global $IN, $DB, $DSP, $LANG, $STAT, $EXT;
    	
		if ( ! $DB->table_exists('exp_blacklisted'))
        {
			$DSP->body .= $DSP->error_message($LANG->line('ref_no_blacklist_table'));        
			return;
        }
        
        // -------------------------------------------
		// 'blacklist_update_blacklist_start' hook.
		//  - Rewrite what happens when the blacklist is updated
		//
			if ($EXT->active_hook('blacklist_update_blacklist_start') === TRUE)
			{
				$edata = $EXT->call_extension('blacklist_update_blacklist_start');
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
        
        
        /** -----------------------------------------
        /**  Current Blacklisted
        /** -----------------------------------------*/
        
        $query			= $DB->query("SELECT * FROM exp_blacklisted");
        $old['url']		= array();
        $old['agent']	= array();
        $old['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$old_values = explode('|',$row['blacklisted_value']);
        		for ($i=0; $i < sizeof($old_values); $i++)
        		{
        			$old[$row['blacklisted_type']][] = $old_values[$i]; 
        		}       	
        	}
        }
        
        /** -----------------------------------------
        /**  Current Whitelisted
        /** -----------------------------------------*/
        
        $query				= $DB->query("SELECT * FROM exp_whitelisted");
        $white['url']		= array();
        $white['agent']		= array();
        $white['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$white_values = explode('|',$row['whitelisted_value']);
        		for ($i=0; $i < sizeof($white_values); $i++)
        		{
        			if (trim($white_values[$i]) != '')
        			{
        				$white[$row['whitelisted_type']][] = $white_values[$i]; 
        			}
        		}       	
        	}
        }
        
        /** ----------------------------------------------------------
        /**  Update Blacklist with New Values sans Whitelist Matches
        /** ----------------------------------------------------------*/
    	
    	$default = array('ip', 'agent', 'url');
    	$modified_weblogs = array();
        
        foreach ($default as $val)
        {
			if (isset($_POST[$val]))
			{
				 $_POST[$val] = str_replace('[-]', '', $_POST[$val]);
				 $_POST[$val] = str_replace('[+]', '', $_POST[$val]);
				 $_POST[$val] = trim(stripslashes($_POST[$val]));
				 
				 $new_values = explode(NL,strip_tags($_POST[$val]));
				 
				 // Clean out user mistakes 
				 // and
				 // Clean out Referrers/Trackbacks with new additions
				 foreach ($new_values as $key => $this->value)
				 {
					if (trim($this->value) == "" || trim($this->value) == NL)
					{
						unset($new_values[$key]);
					}
					elseif ( ! in_array($this->value,$old[$val]))
					{
						$name = ($val == 'url') ? 'from' : $val; 
						
						$sql = "DELETE FROM exp_referrers WHERE ref_{$name} LIKE '%".$DB->escape_like_str($this->value)."%' ";
						
						if (sizeof($white[$val]) > 1)
						{
							$sql .=  " AND ref_{$name} NOT LIKE '%".implode("%' AND ref_{$name} NOT LIKE '%", $DB->escape_like_str($white[$val]))."%'";
						}
						elseif (sizeof($white[$val]) > 0)
						{
							$sql .= "AND ref_{$name} NOT LIKE '%".$DB->escape_like_str($white[$val]['0'])."%'";
						}
						
						$DB->query($sql);
						
						if ($val == 'url' OR $val == 'ip')
						{
							$sql = " exp_trackbacks WHERE trackback_".$val." LIKE '%".$DB->escape_like_str($this->value)."%'";
							
							if (sizeof($white[$val]) > 1)
							{
								$sql .=  " AND trackback_".$val." NOT LIKE '%".implode("%' AND trackback_".$val." NOT LIKE '%", $DB->escape_like_str($white[$val]))."%'";
							}
							elseif (sizeof($white[$val]) > 0)
							{
								$sql .= "AND trackback_".$val." NOT LIKE '%".$DB->escape_like_str($white[$val]['0'])."%'";
							}
							
							$query = $DB->query("SELECT entry_id, weblog_id FROM".$sql);
							
							if ($query->num_rows > 0)
							{
								$DB->query("DELETE FROM".$sql);
							
								foreach($query->result as $row)
								{
									$modified_weblogs[] = $row['weblog_id'];
									
									$results = $DB->query("SELECT COUNT(*) AS count from exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");
									$results2 = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks 
															WHERE entry_id = '".$row['entry_id']."'");
            						$date = ($results2->num_rows == 0 OR ! is_numeric($results2->row['max_date'])) ? 0 : $results2->row['max_date'];
            						
									$DB->query("UPDATE exp_weblog_titles 
												SET trackback_total = '".$results->row['count']."',
												recent_trackback_date = '{$date}'
												WHERE entry_id = '".$row['entry_id']."'");
								}							
							}
						}
					}
				 }
				 
				 sort($new_values);
				 
				 $_POST[$val] = implode("|", array_unique($new_values));
				 
				 $DB->query("DELETE FROM exp_blacklisted WHERE blacklisted_type = '{$val}'");
				 
				 $data = array(	'blacklisted_type' 	=> $val,
								'blacklisted_value'	=> $_POST[$val]);
								
				 $DB->query($DB->insert_string('exp_blacklisted', $data));			     
			}
        } 
        
        // -------------------------------------------
		// 'blacklist_update_blacklist_end' hook.
		//  - Add additional processing onto the end of the update blacklist routine
		//
			if ($EXT->active_hook('blacklist_update_blacklist_end') === TRUE)
			{
				$edata = $EXT->call_extension('blacklist_update_blacklist_end', $data);
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
        
        if (isset($modified_weblogs) && sizeof($modified_weblogs) > 0)
        {
        	$modified_weblogs = array_unique($modified_weblogs);
        	
        	foreach($modified_weblogs as $weblog_id)
        	{
        		$STAT->update_trackback_stats($weblog_id);
        	}
        }
        
        if ($IN->GBL('write_htaccess') !== FALSE)
        {
        	$this->write_htaccess(FALSE);
        }
        
		return $this->view_blacklist($DSP->qdiv('success', $LANG->line('blacklist_updated')));
    }
    /* END */
    
    
    
    /** -------------------------
    /**  Update Blacklist
    /** -------------------------*/
    
    function pmachine_blacklist()
    {
        global $DSP, $LANG, $PREFS, $DB, $STAT, $SESS;
        
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist', $LANG->line('blacklist_module_name'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('pmachine_blacklist'));  
        
        $r = '';  

        if ( ! $DB->table_exists('exp_blacklisted'))
        {
			$r = $DSP->error_message($LANG->line('ref_no_blacklist_table'));
			$DSP->body .= $r;        
			return;
        }      
        
        if ( ! class_exists('XML_RPC'))
		{
			require PATH_CORE.'core.xmlrpc'.EXT;
		}
		                
        
        /** -----------------------------------------
        /**  Get Current Black List from pMachine.com
        /** -----------------------------------------*/
        
        if ( ! $license = $PREFS->ini('license_number'))
        {
        	$r .= $DSP->error_message($LANG->line('ref_no_license'));        
            $DSP->body .= $r;        
            return;
        }
        
        $client = new XML_RPC_Client('/index.php','ping.expressionengine.com','80');
        $message = new XML_RPC_Message('ExpressionEngine.blacklist',array(new XML_RPC_Values($license)));

        if ( ! $result = $client->send($message))
        {
        	$r .= $DSP->error_message($LANG->line('ref_blacklist_irretrievable'));        
            $DSP->body .= $r;        
            return;
        }
        
        if ( ! $result->value())
        {
        	$r .= $DSP->error_message($LANG->line('ref_blacklist_irretrievable').BR.$result->errstr);        
            $DSP->body .= $r;        
            return;
        }
		elseif ( ! is_object($result->val))
        {
        	$r .= $DSP->error_message($LANG->line('ref_blacklist_irretrievable'));        
            $DSP->body .= $r;        
            return;
        }
        
        // Array of our returned info        
        $remote_info = $result->decode();
        
        if ($remote_info['flerror'] != 0)
        {
        	$r .= $DSP->error_message($LANG->line('ref_blacklist_irretrievable').BR.$remote_info['message']);        
            $DSP->body .= $r;        
            return; 	
        }
        
        $new['url'] 	= ( ! isset($remote_info['urls']) || sizeof($remote_info['urls']) == 0) 	? array() : explode('|',$remote_info['urls']);
        $new['agent'] 	= ( ! isset($remote_info['agents']) || sizeof($remote_info['agents']) == 0) ? array() : explode('|',$remote_info['agents']);   
        $new['ip'] 		= ( ! isset($remote_info['ips']) || sizeof($remote_info['ips']) == 0) 		? array() : explode('|',$remote_info['ips']);        
        
        /** -----------------------------------------
        /**  Add Current Blacklisted
        /** -----------------------------------------*/
        
        $query			= $DB->query("SELECT * FROM exp_blacklisted");
        $old['url']		= array();
        $old['agent']	= array();
        $old['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$old_values = explode('|',$row['blacklisted_value']);
        		for ($i=0; $i < sizeof($old_values); $i++)
        		{
        			$old[$row['blacklisted_type']][] = $old_values[$i]; 
        		}       	
        	}
        }
        
        /** -----------------------------------------
        /**  Current Whitelisted
        /** -----------------------------------------*/
        
        $query				= $DB->query("SELECT * FROM exp_whitelisted");
        $white['url']		= array();
        $white['agent']		= array();
        $white['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$white_values = explode('|',$row['whitelisted_value']);
        		for ($i=0; $i < sizeof($white_values); $i++)
        		{
        			if (trim($white_values[$i]) != '')
        			{
        				$white[$row['whitelisted_type']][] = $white_values[$i]; 
        			}
        		}       	
        	}
        }
        
        
        /** -----------------------------------------
        /**  Check for uniqueness and sort
        /** -----------------------------------------*/
        
        $new['url'] 	= array_unique(array_merge($old['url'],$new['url']));
        $new['agent']	= array_unique(array_merge($old['agent'],$new['agent']));
        $new['ip']		= array_unique(array_merge($old['ip'],$new['ip']));
        
        sort($new['url']);
        sort($new['agent']);
        sort($new['ip']); 
        
        
        /** -----------------------------------------
		/**  Put blacklist info back into database
		/** -----------------------------------------*/
		
		$DB->query("DELETE FROM exp_blacklisted");
		
		foreach($new as $key => $value)
		{
			$blacklisted_value = implode('|',$value);
			
			$data = array(	'blacklisted_type' 	=> $key,
							'blacklisted_value'	=> $blacklisted_value);
								
			$DB->query($DB->insert_string('exp_blacklisted', $data));
		}
		
		/** ----------------------------------------------
		/**  Using new blacklist members, clean out spam
		/** ----------------------------------------------*/
		
		$new['url']		= array_diff($new['url'], $old['url']);
		$new['agent']	= array_diff($new['agent'], $old['agent']);
        $new['ip']		= array_diff($new['ip'], $old['ip']);
        
        $modified_weblogs = array();
        
        foreach($new as $key => $value)
		{
			sort($value);
			$name = ($key == 'url') ? 'from' : $key; 
			
			if (sizeof($value) > 0)
			{
				for($i=0; $i < sizeof($value); $i++)
				{
					if ($value[$i] != '')
					{
						$sql = "DELETE FROM exp_referrers WHERE ref_{$name} LIKE '%".$DB->escape_like_str($value[$i])."%' ";
						
						if (sizeof($white[$key]) > 1)
						{
							$sql .=  " AND ref_{$name} NOT LIKE '%".implode("%' AND ref_{$name} NOT LIKE '%", $DB->escape_like_str($white[$key]))."%'";
						}
						elseif (sizeof($white[$key]) > 0)
						{
							$sql .= "AND ref_{$name} NOT LIKE '%".$DB->escape_like_str($white[$key]['0'])."%'";
						}					
					
						$DB->query($sql);
						
						if ($key == 'url' OR $key == 'ip')
						{
							$sql = " exp_trackbacks WHERE trackback_".$key." LIKE '%".$DB->escape_like_str($value[$i])."%'";
							
							if (sizeof($white[$key]) > 1)
							{
								$sql .=  " AND trackback_".$key." NOT LIKE '%".implode("%' AND trackback_".$key." NOT LIKE '%", $DB->escape_like_str($white[$key]))."%'";
							}
							elseif (sizeof($white[$key]) > 0)
							{
								$sql .= "AND trackback_".$key." NOT LIKE '%".$DB->escape_like_str($white[$key]['0'])."%'";
							}
							
							$query = $DB->query("SELECT entry_id, weblog_id FROM".$sql);
							
							if ($query->num_rows > 0)
							{
								$DB->query("DELETE FROM".$sql);
							
								foreach($query->result as $row)
								{
									$modified_weblogs[] = $row['weblog_id'];
									
									$results = $DB->query("SELECT COUNT(*) AS count from exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");
									$results2 = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks 
															WHERE entry_id = '".$row['entry_id']."'");
            						$date = ($results2->num_rows == 0 OR !is_numeric($results2->row['max_date'])) ? 0 : $results2->row['max_date'];
            						
									$DB->query("UPDATE exp_weblog_titles 
												SET trackback_total = '".$results->row['count']."',
												recent_trackback_date = '{$date}'
												WHERE entry_id = '".$row['entry_id']."'");									
								}							
							}
						}
					}			
				}
			}
		}
		
		if (isset($modified_weblogs) && sizeof($modified_weblogs) > 0)
        {
        	$modified_weblogs = array_unique($modified_weblogs);
        	
        	foreach($modified_weblogs as $weblog_id)
        	{
        		$STAT->update_trackback_stats($weblog_id);
        	}
        }
        
        
        /** -----------------------------------------
		/**  Blacklist updated message
		/** -----------------------------------------*/
		$r .= $DSP->heading($LANG->line('pmachine_blacklist'));   
		$r .= $DSP->qdiv('success', $LANG->line('blacklist_updated')); 
		
		if ($SESS->userdata['group_id'] == '1' && $PREFS->ini('htaccess_path') != '')
		{
			// .htaccess
			$r .= BR.$DSP->form_open(array('action' => 'C=modules'.AMP.'M=blacklist'.AMP.'P=write_htaccess'));
			$r .= $DSP->input_hidden('htaccess_path', $PREFS->ini('htaccess_path'));
			$r .= $DSP->input_hidden('write_htaccess', 'y');
			
			$r .= $DSP->qdiv('box', $DSP->qdiv('defaultBold', $LANG->line('write_htaccess_file')).
									$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')))
							);
		}
        
        $DSP->body = $r;
    }
    /* END */
    
    
    
    /** -------------------------
    /**  View Blacklisted
    /** -------------------------*/
    
    function view_blacklist($msg = '')
    {
        global $IN, $DSP, $LANG, $FNS, $DB, $PREFS, $SESS;

		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
                        
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist', $LANG->line('blacklist_module_name'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('ref_view_blacklist'));     
        
        if ( ! $DB->table_exists('exp_blacklisted'))
        {
			$r = $DSP->error_message($LANG->line('ref_no_blacklist_table'));
			$DSP->body .= $r;        
			return;
        }
        
        $r = $DSP->qdiv('tableHeading', $LANG->line('ref_view_blacklist')); 
        
        if ($msg != '')
        	$r .= $DSP->qdiv('successBox', $msg);
        
        $rows = array();
        $default = array('ip', 'url','agent');
        foreach ($default as $value)
        {
        	$rows[$value] = '';
        }
        
        // Store by type with | between values       
        $query = $DB->query("SELECT * FROM exp_blacklisted ORDER BY blacklisted_type asc");
        
        if ($query->num_rows != 0)
        {
        	foreach($query->result as $row)
        	{
        		$rows[$row['blacklisted_type']] = $row['blacklisted_value'];	
        	}
        }

		$r .= $DSP->form_open(
								array(
										'action'	=> 'C=modules'.AMP.'M=blacklist'.AMP.'P=update_blacklist',
										'name'		=> 'target',
										'id'		=> 'target'
									)
								);
		
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', 
                                array(
                                        $LANG->line('ref_type'),
                                        $LANG->line('ref_blacklisted')
                                     )
                                ).
              $DSP->tr_c();


        $i = 0;
        
        //sort($rows);
        foreach($rows as $key => $value)
        {
            $style = ($i++ % 2) ? 'tableCellOneBold' : 'tableCellTwoBold';
                      
            $r .= $DSP->tr();
            
            // Type
            switch($key)
            {
            	case 'ip' :
            		$name = $LANG->line('ref_ip');
            	break;
            	case 'agent' :
            		$name = $LANG->line('ref_user_agent');
            	break;
            	default:
            		$name = $LANG->line('ref_url');
            	break;
            }

        	$r .= $DSP->table_qcell($style, $name,'35%','top');
        	
        	// Value
        	$value = str_replace('|',NL,$value); 
        	$r .= $DSP->table_qcell($style, $DSP->input_textarea($key, $value, 15, 'textarea', '100%'));
        	$r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();
        
        if ($SESS->userdata['group_id'] == '1' && $PREFS->ini('htaccess_path') != '')
		{
			// .htaccess
			$r .= $DSP->qdiv('box', $DSP->input_checkbox('write_htaccess','y', 'y').' '.$LANG->line('write_htaccess_file'));
			$r .= $DSP->input_hidden('htaccess_path', $PREFS->ini('htaccess_path'));
		}

    	$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));             
        
       	$r .= $DSP->form_close();

        $DSP->body .= $r;        
    }
    /* END */
    
    
    
    // ===============================
    //  WHITE LIST AREA
    // ===============================

    /** -------------------------
    /**  Update Whitelisted Items
    /** -------------------------*/
    
    function update_whitelist()
    {
    	global $IN, $DB, $DSP, $LANG, $EXT;
    	
        if ( ! $DB->table_exists('exp_whitelisted'))
        {
			$DSP->body .= $DSP->error_message($LANG->line('ref_no_whitelist_table'));    
			return;
        }
        
        // -------------------------------------------
		// 'blacklist_update_whitelist_start' hook.
		//  - Rewrite what happens when the whitelist is updated
		//
			if ($EXT->active_hook('blacklist_update_whitelist_start') === TRUE)
			{
				$edata = $EXT->call_extension('blacklist_update_whitelist_start');
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
        
        /** -----------------------------------------
        /**  Current Whitelisted
        /** -----------------------------------------*/
        
        $query			= $DB->query("SELECT * FROM exp_whitelisted");
        $old['url']		= array();
        $old['agent']	= array();
        $old['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$old_values = explode('|',$row['whitelisted_value']);
        		for ($i=0; $i < sizeof($old_values); $i++)
        		{
        			$old[$row['whitelisted_type']][] = $old_values[$i]; 
        		}       	
        	}
        }
        
        /** -----------------------------------------
        /**  Update Whitelist with New Values
        /** -----------------------------------------*/
        
    	
    	$default = array('ip', 'agent', 'url');
        
        foreach ($default as $val)
        {
			if (isset($_POST[$val]))
			{
				 $_POST[$val] = str_replace('[-]', '', $_POST[$val]);
				 $_POST[$val] = str_replace('[+]', '', $_POST[$val]);
				 $_POST[$val] = trim(stripslashes($_POST[$val]));
				 
				 $new_values = explode(NL,strip_tags($_POST[$val]));
				 
				 // Clean out user mistakes 
				 // and
				 // Clean out Whitelists with new additions
				 foreach ($new_values as $key => $value)
				 {
					if (trim($value) == "" || trim($value) == NL)
					{
						unset($new_values[$key]);
					}
				 }
				 
				 $_POST[$val] = implode("|",$new_values);
				 
				 $DB->query("DELETE FROM exp_whitelisted WHERE whitelisted_type = '{$val}'");
				 
				 $data = array(	'whitelisted_type' 	=> $val,
								'whitelisted_value'	=> $_POST[$val]);
								
				 $DB->query($DB->insert_string('exp_whitelisted', $data));			     
			}
        } 	
        
         // -------------------------------------------
		// 'blacklist_update_whitelist_end' hook.
		//  - Add processing for when the whitelist is updated
		//
			if ($EXT->active_hook('blacklist_update_whitelist_end') === TRUE)
			{
				$edata = $EXT->call_extension('blacklist_update_whitelist_end', $data);
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
		
		return $this->view_whitelist($DSP->qdiv('success', $LANG->line('whitelist_updated')));
    }
    /* END */
    
    
    
    /** -------------------------
    /**  Update Whitelist
    /** -------------------------*/
    
    function pmachine_whitelist()
    {
        global $DSP, $LANG, $PREFS, $DB;        
        
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist', $LANG->line('blacklist_module_name'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('pmachine_whitelist'));  
        
        $r = '';  
     
        if ( ! $DB->table_exists('exp_whitelisted'))
        {
			$r = $DSP->error_message($LANG->line('ref_no_whitelist_table'));
			$DSP->body .= $r;        
			return;
        }      
        
        if ( ! class_exists('XML_RPC'))
		{
			require PATH_CORE.'core.xmlrpc'.EXT;
		}
		                
        
        /** -----------------------------------------
        /**  Get Current Black List from pMachine.com
        /** -----------------------------------------*/
        
        if ( ! $license = $PREFS->ini('license_number'))
        {
        	$r .= $DSP->error_message($LANG->line('ref_no_license'));        
            $DSP->body .= $r;        
            return;
        }
        
        $client = new XML_RPC_Client('/index.php','ping.expressionengine.com','80');
        $message = new XML_RPC_Message('ExpressionEngine.whitelist',array(new XML_RPC_Values($license)));

        if ( ! $result = $client->send($message))
        {
        	$r .= $DSP->error_message($LANG->line('ref_whitelist_irretrievable'));        
            $DSP->body .= $r;        
            return;
        }        
       
        if ( ! $result->value())
        {
        	$r .= $DSP->error_message($LANG->line('ref_whitelist_irretrievable').BR.$result->errstr);        
            $DSP->body .= $r;        
            return;
        }
        elseif ( ! is_object($result->val))
        {
        	$r .= $DSP->error_message($LANG->line('ref_whitelist_irretrievable'));        
            $DSP->body .= $r;        
            return;
        }
        
        // Array of our returned info        
        $remote_info = $result->decode();
        
        if ($remote_info['flerror'] != 0)
        {
        	$r .= $DSP->error_message($LANG->line('ref_whitelist_irretrievable').BR.$remote_info['message']);        
            $DSP->body .= $r;        
            return; 	
        }
        
        $new['url'] 	= ( ! isset($remote_info['urls']) || sizeof($remote_info['urls']) == 0) 	? array() : explode('|',$remote_info['urls']);
        $new['agent'] 	= ( ! isset($remote_info['agents']) || sizeof($remote_info['agents']) == 0) ? array() : explode('|',$remote_info['agents']);   
        $new['ip'] 		= ( ! isset($remote_info['ips']) || sizeof($remote_info['ips']) == 0) 		? array() : explode('|',$remote_info['ips']);        
        
        /** -----------------------------------------
        /**  Add Current Whitelisted
        /** -----------------------------------------*/
        
        $query			= $DB->query("SELECT * FROM exp_whitelisted");
        $old['url']		= array();
        $old['agent']	= array();
        $old['ip']		= array();
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$old_values = explode('|',$row['whitelisted_value']);
        		for ($i=0; $i < sizeof($old_values); $i++)
        		{
        			$old[$row['whitelisted_type']][] = $old_values[$i]; 
        		}       	
        	}
        }
        
        
        /** -----------------------------------------
        /**  Check for uniqueness and sort
        /** -----------------------------------------*/
        
        $new['url'] 	= array_unique(array_merge($old['url'],$new['url']));
        $new['agent']	= array_unique(array_merge($old['agent'],$new['agent']));
        $new['ip']		= array_unique(array_merge($old['ip'],$new['ip']));
        
        sort($new['url']);
        sort($new['agent']);
        sort($new['ip']); 
        
        
        /** -----------------------------------------
		/**  Put whitelist info back into database
		/** -----------------------------------------*/
		
		$DB->query("DELETE FROM exp_whitelisted");
		
		foreach($new as $key => $value)
		{
			$whitelisted_value = implode('|',$value);
			
			$data = array(	'whitelisted_type' 	=> $key,
							'whitelisted_value'	=> $whitelisted_value);
								
			$DB->query($DB->insert_string('exp_whitelisted', $data));
		}
        
        
        /** -----------------------------------------
		/**  Whitelist updated message
		/** -----------------------------------------*/
		$r .= $DSP->heading($LANG->line('pmachine_whitelist'));   
		$r .= $DSP->qdiv('success', $LANG->line('whitelist_updated')); 
        
        $DSP->body = $r;
    }
    /* END */
    
    
    
    /** -------------------------
    /**  View Whitelisted
    /** -------------------------*/
    
    function view_whitelist($msg = '')
    {
        global $IN, $DSP, $LANG, $FNS, $DB, $PREFS;

		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
                        
        $DSP->title = $LANG->line('blacklist_module_name');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blacklist', $LANG->line('blacklist_module_name'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('ref_view_whitelist'));     
        
        if ( ! $DB->table_exists('exp_whitelisted'))
        {
			$r = $DSP->error_message($LANG->line('ref_no_whitelist_table'));
			$DSP->body .= $r;        
			return;
        }
        
        $r = $DSP->heading($LANG->line('ref_view_whitelist')); 
        
        $r .= $msg;
        
        $rows = array();
        $default = array('ip', 'url','agent');
        foreach ($default as $value)
        {
        	$rows[$value] = '';
        }
        
        // Store by type with | between values       
        $query = $DB->query("SELECT * FROM exp_whitelisted ORDER BY whitelisted_type asc");
        
        if ($query->num_rows != 0)
        {
        	foreach($query->result as $row)
        	{
        		$rows[$row['whitelisted_type']] = $row['whitelisted_value'];	
        	}
        }

		$r .= $DSP->form_open(
								array(
										'action' 	=> 'C=modules'.AMP.'M=blacklist'.AMP.'P=update_whitelist', 
										'name'		=> 'target',
										'id'		=> 'target'
								)
							);
		              
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', 
                                array(
                                        $LANG->line('ref_type'),
                                        $LANG->line('ref_whitelisted')
                                     )
                                ).
              $DSP->tr_c();


        $i = 0;
        
        //sort($rows);
        foreach($rows as $key => $value)
        {
            $style = ($i++ % 2) ? 'tableCellOneBold' : 'tableCellTwoBold';
                      
            $r .= $DSP->tr();
            
            // Type
            switch($key)
            {
            	case 'ip' :
            		$name = $LANG->line('ref_ip');
            	break;
            	case 'agent' :
            		$name = $LANG->line('ref_user_agent');
            	break;
            	default:
            		$name = $LANG->line('ref_url');
            	break;
            }

        	$r .= $DSP->table_qcell($style, $name,'35%','top');
        	
        	// Value
        	$value = str_replace('|',NL,$value); 
        	$r .= $DSP->table_qcell($style, $DSP->input_textarea($key, $value, 15, 'textarea', '100%'));
        	
        }

        $r .= $DSP->table_c();
    	$r .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('update')));             
        
       	$r .= $DSP->form_close();

        $DSP->body .= $r;        
    }
    /* END */


    /** -------------------------
    /**  Module installer
    /** -------------------------*/

    function blacklist_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Blacklist', '$this->version', 'y')";
    	$sql[] = "CREATE TABLE IF NOT EXISTS  `exp_blacklisted` (
				`blacklisted_type` VARCHAR( 20  )  NOT  NULL ,
				`blacklisted_value` TEXT  NOT  NULL);";
				
		$sql[] = "CREATE TABLE IF NOT EXISTS  `exp_whitelisted` (
				`whitelisted_type` VARCHAR( 20  )  NOT  NULL ,
				`whitelisted_value` TEXT  NOT  NULL);";
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
        return true;
    }
    /* END */
    
    
    /** -------------------------
    /**  Module de-installer
    /** -------------------------*/

    function blacklist_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Blacklist'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Blacklist'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Blacklist'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Blacklist_CP'";
		$sql[] = "DROP TABLE IF EXISTS exp_blacklisted";
		$sql[] = "DROP TABLE IF EXISTS exp_whitelisted";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */


	/** -------------------------
    /**  Whitelist cleaning for new Blacklist items
    /** -------------------------*/

    function whitelist_clean($var)
    {
		return stristr($var, $this->value);
	}
	/* END */
}
// END CLASS
?>