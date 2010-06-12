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
 File: mcp.stats.php
-----------------------------------------------------
 Purpose: Statistical tracking module - backend
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Stats_CP {

    var $version	= '1.0';
    var $stats		= array();
    


    /** --------------------------------
    /**  Update statistics
    /** --------------------------------*/
        
    function update_stats()
    {    
		global $IN, $FNS, $LOC, $DB, $SESS, $PREFS;
		
		$time_limit = 15; // Number of minutes to track users
				
		/** --------------------------------
		/**  Set weblog ID
		/** --------------------------------*/

		$weblog_id = (USER_BLOG !== FALSE) ? UB_BLOG_ID : 0;

		/** --------------------------------
		/**  Fetch current user's name
		/** --------------------------------*/

        if ($SESS->userdata('member_id') != 0)
        {
            $name = ($SESS->userdata['screen_name'] == '') ? $SESS->userdata['username'] : $SESS->userdata['screen_name'];
        }
        else
        {
            $name = '';
        }
        
        // Is user browsing anonymously?
        
        $anon = ( ! $IN->GBL('anon', 'COOKIE')) ? '' : 'y';
		

		/** --------------------------------
		/**  Fetch online users
		/** --------------------------------*/
			
		$cutoff = $LOC->now - ($time_limit * 60);
			
		$query = $DB->query("SELECT * FROM exp_online_users WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND date > $cutoff AND weblog_id = '$weblog_id' ORDER BY name");
		
		if ($PREFS->ini('dynamic_tracking_disabling') !== FALSE && $PREFS->ini('dynamic_tracking_disabling') != '' && $query->num_rows > $PREFS->ini('dynamic_tracking_disabling'))
		{
			// disable tracking!
			$PREFS->disable_tracking();
			
	        if ((mt_rand() % 100) < $SESS->gc_probability) 
	        {
				$DB->query("DELETE FROM exp_online_users WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND date < $cutoff AND weblog_id = '$weblog_id'");
			}

			return;
		}
		
		/** -------------------------------------------
		/**  Assign users to a multi-dimensional array
		/** -------------------------------------------*/
		
		$total_logged	= 0;
		$total_guests	= 0;
		$total_anon	    = 0;
		$update 		= FALSE;
		$current_names	= array();		
		
		if ($query->num_rows > 0)
		{
            foreach ($query->result as $row)
            {    
            	if ($row['member_id'] == $SESS->userdata('member_id')  AND $row['ip_address'] == $IN->IP AND $row['name'] == $name)
            	{
            		$update = TRUE;
            		$anon = $row['anon'];
            	}
            
				if ($row['member_id'] != 0)
				{
					$current_names[$row['member_id']] = array($row['name'], $row['anon']);
	
					if ($row['anon'] != '')
					{		
						$total_anon++;
					}
					else
					{	
						$total_logged++;
					}
				}
				else
				{
					$total_guests++;
				}
            }
        }
        else
        {
        	$total_guests++;
        }
               
        
		/** -------------------------------------------
		/**  Set the "update" pref, which we'll use later
		/** -------------------------------------------*/
        
        if ($update == TRUE)
        {
            $total_visitors = $query->num_rows;
        }
        else
        {  
			if ($SESS->userdata('member_id') != 0)
			{
				$current_names[$SESS->userdata('member_id')] = array($name, $anon);
			
				$total_logged++;
			}
			else
			{
				$total_guests++;
			}
			
            $total_visitors = $query->num_rows + 1;
        }


		/** --------------------------------
		/**  Update online_users table
		/** --------------------------------*/

		$data = array(
						'weblog_id'		=> $weblog_id,
						'member_id'		=> $SESS->userdata('member_id'),
						'name'			=> $name,
						'ip_address'	=> $IN->IP,
						'date'			=> $LOC->now,
						'anon'			=> $anon,
						'site_id'		=> $PREFS->ini('site_id')
					);

		if ($update == FALSE)
		{
        	$DB->query($DB->insert_string('exp_online_users', $data));
		}
		else
		{
        	$DB->query($DB->update_string('exp_online_users', $data, array('site_id' => $PREFS->ini('site_id'), "ip_address" => $IN->IP, "member_id" => $data['member_id'])));
		}
		
		unset($data);

		/** --------------------------------
		/**  Fetch global statistics
		/** --------------------------------*/
		
		$query = $DB->query("SELECT * FROM exp_stats WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$weblog_id'");
		
		/** --------------------------------
		/**  Update the stats
		/** --------------------------------*/
			
		if ($total_visitors > $query->row['most_visitors'])			
		{
			 $query->row['most_visitors'] 		= $total_visitors;
			 $query->row['most_visitor_date']	= $LOC->now;
		
			$sql = "UPDATE exp_stats SET most_visitors = '{$total_visitors}', most_visitor_date = '{$LOC->now}', last_visitor_date = '{$LOC->now}' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$weblog_id'";
		}
		else
		{
			$sql = "UPDATE exp_stats SET last_visitor_date = '{$LOC->now}' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$weblog_id'";
		}
		
		$DB->query($sql);
		
		/** --------------------------------
		/**  Assign the stats
		/** --------------------------------*/
				
		$this->stats = array(
								'recent_member'				=> $query->row['recent_member'],
								'recent_member_id'			=> $query->row['recent_member_id'],
								'total_members'				=> $query->row['total_members'],
								'total_entries'				=> $query->row['total_entries'],
								'total_forum_topics'		=> $query->row['total_forum_topics'],
								'total_forum_posts'			=> $query->row['total_forum_posts'] + $query->row['total_forum_topics'],
								'total_forum_replies'		=> $query->row['total_forum_posts'],
								'total_comments'			=> $query->row['total_comments'],
								'total_trackbacks'			=> $query->row['total_trackbacks'],
								'most_visitors'				=> $query->row['most_visitors'],
								'last_entry_date'			=> $query->row['last_entry_date'],
								'last_forum_post_date'		=> $query->row['last_forum_post_date'],
								'last_comment_date'			=> $query->row['last_comment_date'],
								'last_trackback_date'		=> $query->row['last_trackback_date'],
								'last_cache_clear'		    => $query->row['last_cache_clear'],
								'last_visitor_date'			=> $query->row['last_visitor_date'],
								'most_visitor_date'			=> $query->row['most_visitor_date'],
								'total_logged_in'			=> $total_logged,
								'total_guests'				=> $total_guests,
								'total_anon'				=> $total_anon,
								'current_names'				=> $current_names
							);
		unset($query);

        srand(time());
        if ((rand() % 100) < $SESS->gc_probability) 
        {                 
            $DB->query("DELETE FROM exp_online_users WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND date < $cutoff AND weblog_id = '$weblog_id'");             
        }    
	}
	/* END */



    /** -------------------------------------
    /**  Fetch Weblog ID numbers for query
    /** -------------------------------------*/

	function fetch_weblog_ids()
	{
		global $DB, $PREFS;
		
		$sql = '';
	
		if (USER_BLOG === FALSE)
		{
			$query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n'");
			
			if ($query->num_rows == 0)
			{
				return " weblog_id = '0'";
			}
			
			$sql .= " weblog_id IN (";
				
			foreach ($query->result as $row)
			{
				$sql .= $row['weblog_id'].",";
			}
			
			$sql = substr($sql, 0, -1).") ";
		}
		else
		{
			$sql .= " weblog_id = '".UB_BLOG_ID."'";
		}
	
		return $sql;
	}
	/* END */


    /** -------------------------------
    /**  Update Member Stats
    /** -------------------------------*/
  
    function update_member_stats()
    {
		global $DB, $PREFS;
    	  
		$weblog_id = (USER_BLOG === FALSE) ? 0 : UB_BLOG_ID;

		$query = $DB->query("SELECT MAX(member_id) AS max_id FROM exp_members");
		
		$query = $DB->query("SELECT screen_name, member_id FROM exp_members WHERE member_id = '".$query->row['max_id']."'");
		$name	= $query->row['screen_name'];
		$mid	= $query->row['member_id'];
		
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id NOT IN ('4','2')");
        
        $sql = "UPDATE exp_stats SET total_members = '".$query->row['count']."', recent_member = '".$DB->escape_str($name)."', recent_member_id = '{$mid}' WHERE weblog_id = '$weblog_id'";
                
		$DB->query($sql);
	}
	/* END */

    
    /** -------------------------------
    /**  Update Weblog Stats
    /** -------------------------------*/
  
    function update_weblog_stats($weblog_id = '')
    {
    	global $LOC, $DB, $PREFS;
    	  
        // Update global stats table  
    	  
		$user_blog_id = (USER_BLOG === FALSE) ? 0 : UB_BLOG_ID;
		
		$blog_ids = $this->fetch_weblog_ids();
		
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_weblog_titles WHERE ".$blog_ids." AND entry_date < ".$LOC->now." AND (expiration_date = 0 OR expiration_date > ".$LOC->now.") AND status != 'closed'");
        
        $total = $query->row['count'];
        
        $query = $DB->query("SELECT MAX(entry_date) as max_date FROM exp_weblog_titles WHERE ".$blog_ids." AND entry_date < ".$LOC->now." AND (expiration_date = 0 OR expiration_date > ".$LOC->now.") AND status != 'closed'");
        
        $date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
                                
        $DB->query("UPDATE exp_stats SET total_entries = '$total', last_entry_date = '$date' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$user_blog_id'");
        
        
        // Update exp_weblog table
		
		if ($weblog_id != '')
		{
            $query = $DB->query("SELECT site_id FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
            
            $site_id = $query->row['site_id'];
            
            $query = $DB->query("SELECT COUNT(*) AS count FROM exp_weblog_titles WHERE weblog_id = '$weblog_id' AND entry_date < ".$LOC->now." AND (expiration_date = 0 OR expiration_date > ".$LOC->now.") AND status != 'closed'");
            
            $total = $query->row['count'];
            
            $query = $DB->query("SELECT MAX(entry_date) AS max_date FROM exp_weblog_titles WHERE weblog_id = '$weblog_id' AND entry_date < ".$LOC->now." AND (expiration_date = 0 OR expiration_date > ".$LOC->now.") AND status != 'closed'");
            
            $date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
                                
            $DB->query("UPDATE exp_weblogs SET total_entries = '$total', last_entry_date = '$date' WHERE site_id = '".$DB->escape_str($site_id)."' AND weblog_id = '$weblog_id'");
        }
	}
	/* END */
	
	
	
    /** -------------------------------
    /**  Update Comment Stats
    /** -------------------------------*/
  
    function update_comment_stats($weblog_id = '', $newtime = '', $global=TRUE)
    {  
    	global $LOC, $DB, $PREFS;
    	
    	// Update global stats table
    	
    	if ($global === TRUE)
    	{
			$user_blog_id = (USER_BLOG === FALSE) ? 0 : UB_BLOG_ID;
		
			$blog_ids = $this->fetch_weblog_ids();

        	$query = $DB->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND ".$blog_ids);
        
        	$total = $query->row['count'];
        
        	if ($newtime == '')
        	{
        		$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND ".$blog_ids."");
        	
        		$date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
			}
			else
			{
				$query = $DB->query("SELECT last_comment_date FROM exp_stats WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$user_blog_id'");
				
				$date = ($newtime > $query->row['last_comment_date']) ? $newtime : $query->row['last_comment_date'];
			}
		
			$DB->query("UPDATE exp_stats SET total_comments = '$total', last_comment_date = '$date' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$user_blog_id'");
		}
		
        // Update exp_weblog table

		if ($weblog_id != '')
		{
            $query = $DB->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND weblog_id = '$weblog_id'");
            
            $total = $query->row['count'];
            
            if ($newtime == '')
            {
            	$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND weblog_id = '$weblog_id'");
            
            	$date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
            }
            else
            {
            	$query = $DB->query("SELECT last_comment_date, site_id FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
			
				$date = ($newtime > $query->row['last_comment_date']) ? $newtime : $query->row['last_comment_date'];
            }
                                
            $DB->query("UPDATE exp_weblogs SET total_comments = '$total', last_comment_date = '$date' WHERE weblog_id = '$weblog_id'");
		}
	}
	/* END */


    /** -------------------------------
    /**  Update Trackback Stats
    /** -------------------------------*/
  
    function update_trackback_stats($weblog_id = '')
    {  
    	global $LOC, $DB, $PREFS;

        // Update global stats table

		$user_blog_id = (USER_BLOG === FALSE) ? 0 : UB_BLOG_ID;
		
		$blog_ids = $this->fetch_weblog_ids();

        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE ".$blog_ids);
        
        $total = $query->row['count'];
        
        $query = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks WHERE ".$blog_ids);
        
        $date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
                
		$DB->query("UPDATE exp_stats SET total_trackbacks = '$total', last_trackback_date = '$date' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '$user_blog_id'");
		
        // Update exp_weblog table
		
		if ($weblog_id != '')
		{
            $query = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE weblog_id = '$weblog_id'");
            
            $total = $query->row['count'];
            
            $query = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks WHERE weblog_id = '$weblog_id'");
            
            $date = ($query->num_rows == 0 OR ! is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
                        
            $DB->query("UPDATE exp_weblogs SET total_trackbacks = '$total', last_trackback_date = '$date' WHERE weblog_id = '$weblog_id'");
        }		
	}
	/* END */


	/** --------------------------------
	/**  This method is called when stats are read-only
	/** --------------------------------*/
		
	function load_stats()
	{
		global $DB, $IN, $LOC, $PREFS, $SESS, $STAT;
		
		$time_limit = 15; // Number of minutes to track users
		
		/** --------------------------------
		/**  Fetch current user's name
		/** --------------------------------*/

        if ($SESS->userdata('member_id') != 0)
        {
            $name = ($SESS->userdata['screen_name'] == '') ? $SESS->userdata['username'] : $SESS->userdata['screen_name'];
        }
        else
        {
            $name = '';
        }
        
        // Is user browsing anonymously?
        
        $anon = ( ! $IN->GBL('anon', 'COOKIE')) ? '' : 'y';
		

		/** --------------------------------
		/**  Fetch online users
		/** --------------------------------*/
			
		$cutoff = $LOC->now - ($time_limit * 60);
			
		$query = $DB->query("SELECT * FROM exp_online_users WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND date > $cutoff AND weblog_id = '0' ORDER BY name");
		
		
		/** -------------------------------------------
		/**  Assign users to a multi-dimensional array
		/** -------------------------------------------*/
		
		$total_logged	= 0;
		$total_guests	= 0;
		$total_anon	    = 0;
		$update 		= FALSE;
		$current_names	= array();		
		
		if ($query->num_rows > 0)
		{
            foreach ($query->result as $row)
            {    
            	if ($row['member_id'] == $SESS->userdata('member_id')  AND $row['ip_address'] == $IN->IP AND $row['name'] == $name)
            	{
            		$update = TRUE;
            		$anon = $row['anon'];
            	}
            
				if ($row['member_id'] != 0)
				{
					$current_names[$row['member_id']] = array($row['name'], $row['anon']);
	
					if ($row['anon'] != '')
					{		
						$total_anon++;
					}
					else
					{	
						$total_logged++;
					}
				}
				else
				{
					$total_guests++;
				}
            }
        }
        else
        {
        	$total_guests++;
        }
                    
		/** -------------------------------------------
		/**  This user already counted or no?
		/** -------------------------------------------*/
        
        if ($update == TRUE)
        {
            $total_visitors = $query->num_rows;
        }
        else
        {  
			if ($SESS->userdata('member_id') != 0)
			{
				$current_names[$SESS->userdata('member_id')] = array($name, $anon);
			
				$total_logged++;
			}
			else
			{
				$total_guests++;
			}
			
            $total_visitors = $query->num_rows + 1;
        }
		
		$query = $DB->query("SELECT * FROM exp_stats WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '0'");
			
		$STAT->stats = array(
								'recent_member'				=> $query->row['recent_member'],
								'recent_member_id'			=> $query->row['recent_member_id'],
								'total_members'				=> $query->row['total_members'],
								'total_entries'				=> $query->row['total_entries'],
								'total_forum_topics'		=> $query->row['total_forum_topics'],
								'total_forum_posts'			=> $query->row['total_forum_posts'] + $query->row['total_forum_topics'],
								'total_forum_replies'		=> $query->row['total_forum_posts'],
								'total_comments'			=> $query->row['total_comments'],
								'total_trackbacks'			=> $query->row['total_trackbacks'],
								'most_visitors'				=> $query->row['most_visitors'],
								'last_entry_date'			=> $query->row['last_entry_date'],
								'last_forum_post_date'		=> $query->row['last_forum_post_date'],
								'last_comment_date'			=> $query->row['last_comment_date'],
								'last_trackback_date'		=> $query->row['last_trackback_date'],
								'last_cache_clear'		    => $query->row['last_cache_clear'],
								'last_visitor_date'			=> $query->row['last_visitor_date'],
								'most_visitor_date'			=> $query->row['most_visitor_date'],
								'total_logged_in'			=> $total_logged,
								'total_guests'				=> $total_guests,
								'total_anon'				=> $total_anon,
								'current_names'				=> $current_names
							);
		unset($query);
	}
	/* END */
	
	
    /** --------------------------------
    /**  Module installer
    /** --------------------------------*/

    function stats_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Stats', '$this->version', 'n')";        
    
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

    function stats_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Stats'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Stats'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Stats'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Stats_CP'";
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */


}
// END CLASS
?>