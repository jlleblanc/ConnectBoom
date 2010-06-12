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
 File: cp.log.php
-----------------------------------------------------
 Purpose: Logging class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Logger {



    /** -------------------------------------
    /**  Log an action
    /** -------------------------------------*/

    function log_action($action = '')
    {
        global $DB, $SESS, $IN, $LOC, $PREFS;

		if ($action == '')
		{
			return;
		}
                
        if (is_array($action))
        {
        	if (count($action) == 0)
        	{
        		return;
        	}
        
            $msg = '';
        
            foreach ($action as $val)
            {
                $msg .= $val."\n";    
            }
            
            $action = $msg;
        }
                                               
        $DB->query(
                     $DB->insert_string(
                                           'exp_cp_log',
                
                                            array(
                                                    'id'         => '',
                                                    'member_id'  => $SESS->userdata('member_id'),
                                                    'username'   => $SESS->userdata['username'],
                                                    'ip_address' => $IN->IP,
                                                    'act_date'   => $LOC->now,
                                                    'action'     => $action,
                                                    'site_id'	 => $PREFS->ini('site_id')
                                                 )
                                            )
                    );    
    }
    /* END */



    /** -------------------------------------
    /**  Clear control panel logs
    /** -------------------------------------*/

    function clear_cp_logs()
    {
        global $DSP, $LANG, $DB;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
    
        $query = $DB->query("DELETE FROM exp_cp_log");
        
        $this->log_action($LANG->line('cleared_logs'));
        
        return $this->view_logs();
    }
    /* END */



    /** -------------------------------------
    /**  View control panel logs
    /** -------------------------------------*/

    function view_logs()
    {
        global $DSP, $LANG, $LOC, $IN, $DB;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        // Number of results per page
        
         $perpage = 100;  
        
        // Fetch the total number of logs for our paginate links
        
        $query = $DB->query("SELECT COUNT(*) as count FROM exp_cp_log");
        
        $total = $query->row['count'];
        
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
        
        // Run the query
        
        $sites_query = $DB->query("SELECT site_id, site_label FROM exp_sites");
        
        $sites = array();
        
        foreach($sites_query->result as $row)
        {
        	$sites[$row['site_id']] = $row['site_label'];
        }
            
        $query = $DB->query("SELECT * FROM exp_cp_log ORDER BY act_date desc LIMIT $rownum, $perpage");
        
        // Build the output
        
        $r  = $DSP->qdiv('tableHeading', $LANG->line('view_log_files'));
                     
        $r .= $DSP->table('tableBorder', '0', '0', '100%');
             
        $r .= $DSP->table_qrow('tableHeadingAlt',
                              array(
                                    $LANG->line('member_id'),
                                    $LANG->line('username'),
                                    $LANG->line('ip_address'),
                                    $LANG->line('date'),
                                    $LANG->line('site'),
                                    $LANG->line('action')
                                   )
                             );
        
        $i = 0;
        
        foreach ($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
            $r .= $DSP->table_qrow($style,
                                    array(
                                            $row['member_id'],
                                            $row['username'],
                                            $row['ip_address'],
                                            $LOC->set_human_time($row['act_date']),
                                            $sites[$row['site_id']],
                                            nl2br($row['action'])
                                          )
                                   );
        }
        
        $r .= $DSP->table_c();
        
        $r .= $DSP->qdiv('itemWrapper',
              $DSP->qdiv('crumblinks',
              $DSP->pager(
                            BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=view_logs',
                            $total,
                            $perpage,
                            $rownum,
                            'rownum'
                          )));
              
 
		$DSP->right_crumb($LANG->line('clear_logs'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=clear_cplogs');
 
        $DSP->title  = $LANG->line('view_log_files');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		   $DSP->crumb_item($LANG->line('view_log_files'));
        $DSP->body   = $r;
    }
    /* END */




    /** -------------------------------------
    /**  Clear Search Terms log
    /** -------------------------------------*/

    function clear_search_log()
    {
        global $DSP, $LANG, $DB;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
    
        $query = $DB->query("DELETE FROM exp_search_log");
                
        return $this->view_search_log();
    }
    /* END */



    /** -------------------------------------
    /**  View Search Term Log
    /** -------------------------------------*/

    function view_search_log()
    {
        global $DSP, $LANG, $LOC, $IN, $DB;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        // Number of results per page
        
         $perpage = 100;  
        
        // Fetch the total number of logs for our paginate links
        
        $query = $DB->query("SELECT COUNT(*) as count FROM exp_search_log");
        
        $total = $query->row['count'];
        
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
        
        // Run the query
        
        $sites_query = $DB->query("SELECT site_id, site_label FROM exp_sites");
        $sites = array();
        
        foreach($sites_query->result as $row)
        {
        	$sites[$row['site_id']] = $row['site_label'];
        }
        
        $query = $DB->query("SELECT * FROM exp_search_log ORDER BY search_date desc LIMIT $rownum, $perpage");
        
        // Build the output
        
		$r = $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(array('text' => $LANG->line('view_search_log'),'class' => 'tableHeading', 'colspan' => '6' )));
		$r .= $DSP->table_row(array(
									array('text' => $LANG->line('screen_name'), 'class' => 'tableHeadingAlt', 'width' => '10%'),
									array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%'),
									array('text' => $LANG->line('date'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
									array('text' => $LANG->line('site'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
									array('text' => $LANG->line('searched_in'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
									array('text' => $LANG->line('search_terms'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
									)
							);
		if ($query->num_rows == 0)
		{
			$r .= $DSP->table_row(array(array('text' => $DSP->qdiv('highlight', $LANG->line('no_search_terms')), 'class' => 'tableCellTwo', 'colspan' => '6' )));
		}
		else
		{
			$i = 0;
			foreach($query->result as $row)
			{
				$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
				
				if ($row['member_id'] == 0)
				{
					$link = $LANG->line('guest');
				}
				else
				{
					$link = $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['screen_name'].'</b>');
				}
				
				if ($row['search_type'] == 'site')
				{
					$type = $LANG->line('site_search');
				}
				elseif ($row['search_type'] == 'forum')
				{
					$type = $LANG->line('forum_search');
				}
				elseif ($row['search_type'] == 'wiki')
				{
					$type = $LANG->line('wiki_search');
				}
				else
				{
					$type = $row['search_type'];
				}
				
				$r .= $DSP->table_row(array(
											array('text' => $link, 'class' => $class),
											array('text' => $row['ip_address'], 'class' => $class),
											array('text' => $LOC->set_human_time($row['search_date']), 'class' => $class),
											array('text' => $sites[$row['site_id']], 'class' => $class),
											array('text' => $type, 'class' => $class),
											array('text' => $row['search_terms'], 'class' => $class)
											)
									);
			} // End foreach
		}

		$r .= $DSP->table_close();
        
        $r .= $DSP->qdiv('itemWrapper',
              $DSP->qdiv('crumblinks',
              $DSP->pager(
                            BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=view_search_log',
                            $total,
                            $perpage,
                            $rownum,
                            'rownum'
                          )));
              
 
		$DSP->right_crumb($LANG->line('clear_logs'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=clear_search_log');
 
        $DSP->title  = $LANG->line('view_search_log');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		   $DSP->crumb_item($LANG->line('view_search_log'));
        $DSP->body   = $r;
    }
    /* END */
    
    
    
	/** -------------------------------------
    /**  View Throttle Log
    /** -------------------------------------*/

    function view_throttle_log()
    {
        global $DSP, $LANG, $LOC, $IN, $DB, $PREFS;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        if ($PREFS->ini('enable_throttling') == 'n')
        {
        	return $DSP->error_message($LANG->line('throttling_disabled'));
        }
        
        $max_page_loads = 10;
		$time_interval	= 5;
		$lockout_time	= 30;
        
        if (is_numeric($PREFS->ini('max_page_loads')))
		{
			$max_page_loads = $PREFS->ini('max_page_loads');
		}

		if (is_numeric($PREFS->ini('time_interval')))
		{
			$time_interval = $PREFS->ini('time_interval');
		}

		if (is_numeric($PREFS->ini('lockout_time')))
		{
			$lockout_time = $PREFS->ini('lockout_time');
		}
        
        // Number of results per page
		$perpage = 100;
		
		if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
        
        /** ---------------------------
        /**  Retrieve List of Devils
        /** ---------------------------*/
		
        $lockout = time() - $lockout_time;
        
        $offset = $LOC->now - time();
        
        $query = $DB->query("SELECT COUNT(ip_address) AS count FROM exp_throttle 
							 WHERE (hits >= '{$max_page_loads}' OR (locked_out = 'y' AND last_activity > '{$lockout}'))");
							
		$total = $query->row['count'];
		
		$query = $DB->query("SELECT ip_address, hits, locked_out, last_activity FROM exp_throttle 
							 WHERE (hits >= '{$max_page_loads}' OR (locked_out = 'y' AND last_activity > '{$lockout}'))
							 ORDER by ip_address LIMIT $rownum, $perpage");
        
        // Build the output
        
		$r = $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(array('text' => $LANG->line('view_throttle_log'),'class' => 'tableHeading', 'colspan' => '5' )));
		$r .= $DSP->table_row(array(
									array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '25%'),
									array('text' => $LANG->line('hits'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
									//array('text' => $LANG->line('locked_out'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
									array('text' => $LANG->line('last_activity'), 'class' => 'tableHeadingAlt', 'width' => '55%'),
									)
							);
							
		if ($query->num_rows == 0)
		{
			$r .= $DSP->table_row(array(array('text' => $DSP->qdiv('highlight', $LANG->line('no_throttle_logs')), 'class' => 'tableCellTwo', 'colspan' => '5' )));
		}
		else
		{
			$i = 0;
			foreach($query->result as $row)
			{
				$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
				
				$r .= $DSP->table_row(array(
											array('text' => $row['ip_address'], 'class' => $class),
											array('text' => $row['hits'], 'class' => $class),
											//array('text' => $row['locked_out'], 'class' => $class),
											array('text' => $LOC->set_human_time($row['last_activity'] + $offset), 'class' => $class)
											)
									);
			} // End foreach
		}

		$r .= $DSP->table_close();
        
        $r .= $DSP->qdiv('itemWrapper',
              $DSP->qdiv('crumblinks',
              $DSP->pager(
                            BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=view_throttle_log',
                            $total,
                            $perpage,
                            $rownum,
                            'rownum'
                          )));
 		
 		if ($total > 0)
 		{
 			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Blacklist'");
        
			if ($query->row['count'] > 0)
			{
				$DSP->right_crumb($LANG->line('blacklist_all_ips'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=blacklist_ips');
			}
		}
		
        $DSP->title  = $LANG->line('view_throttle_log');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		   $DSP->crumb_item($LANG->line('view_throttle_log'));
        $DSP->body   = $r;
    }
    /* END */
    
    
    
	/** -------------------------------------
    /**  Blacklist Throttled IPs
    /** -------------------------------------*/

    function blacklist_ips()
    {
        global $DSP, $LANG, $LOC, $IN, $DB, $PREFS, $SESS;
    
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        if ($PREFS->ini('enable_throttling') == 'n')
        {
        	return $DSP->error_message($LANG->line('throttling_disabled'));
        }
        
        $max_page_loads = 10;
		$time_interval	= 5;
		$lockout_time	= 30;
        
        if (is_numeric($PREFS->ini('max_page_loads')))
		{
			$max_page_loads = $PREFS->ini('max_page_loads');
		}

		if (is_numeric($PREFS->ini('time_interval')))
		{
			$time_interval = $PREFS->ini('time_interval');
		}

		if (is_numeric($PREFS->ini('lockout_time')))
		{
			$lockout_time = $PREFS->ini('lockout_time');
		}
        
        /** ---------------------------
        /**  Retrieve List of Devils
        /** ---------------------------*/
        
        $lockout = time() - $lockout_time;
        
        $query = $DB->query("SELECT ip_address FROM exp_throttle
							 WHERE (hits >= '{$max_page_loads}' OR (locked_out = 'y' AND last_activity > '{$lockout}'))");
        
 		if ($query->num_rows == 0)
 		{
 			return $DSP->error_message($LANG->line('no_throttle_logs'));
 		}
 		
 		$naughty = array();
 		
 		foreach($query->result as $row)
 		{
 			$naughty[] = $row['ip_address'];
 		}
 		
 		$query = $DB->query("SELECT blacklisted_value FROM exp_blacklisted WHERE blacklisted_type = 'ip'");
 		
 		if ($query->num_rows > 0)
 		{
 			$naughty = array_merge($naughty, explode('|',$query->row['blacklisted_value']));
 		}
 		
		$DB->query("DELETE FROM exp_blacklisted WHERE blacklisted_type = 'ip'");
		
		$data = array(	'blacklisted_type' 	=> 'ip',
						'blacklisted_value'	=> implode("|", array_unique($naughty)));
					
		$DB->query($DB->insert_string('exp_blacklisted', $data));	
 		
 		$LANG->fetch_language_file('blacklist');
 		
 		if ( ! class_exists('Blacklist'))
 		{
 			require PATH_MOD.'blacklist/mcp.blacklist'.EXT;
 		}
 		
 		$MOD = new Blacklist_CP(FALSE);
 		
 		if ($SESS->userdata['group_id'] == 1 && $PREFS->ini('htaccess_path') !== FALSE && file_exists($PREFS->ini('htaccess_path')) && is_writable($PREFS->ini('htaccess_path')))
 		{
 			$_POST['htaccess_path'] = $PREFS->ini('htaccess_path');
 			$MOD->write_htaccess(FALSE);
 		}
 		
 		return $MOD->view_blacklist($DSP->qdiv('success', $LANG->line('blacklist_updated')));
    }
    /* END */

}
// END CLASS
?>