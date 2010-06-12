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
 File: core.session.php
-----------------------------------------------------
 Purpose: Session management class.
=====================================================

There are three validation types, set in the config file: 
 
  1. User cookies AND session ID (cs)
        
    This is the most secure way to run a site.  Three cookies are set:
    1. Session ID - This is a unique hash that is randomly generated when someone logs in.
    2. Password hash - The encrypted password of the current user
    3. Unique ID - The permanent unique ID hash associated with the account.
    
    All three cookies expire when you close your browser OR when you have been 
    inactive longer than two hours (one hour in the control panel).
    
    Using this setting does NOT allow 'stay logged-in' capability, as each session has a finite lifespan.

  2. Cookies only - no session ID (c)
    
    With this validation type, a session is not generated, therefore
    users can remain permanently logged in.
    
    This setting is obviously less secure because it does not provide a safety net
    if you share your computer or access your site from a public computer.  It relies
    solely on the password/unique_id cookies.

  3. Session ID only (s).  
    
    Most compatible as it does not rely on cookies at all.  Instead, a URL query string ID 
    is used.
    
    No stay-logged in capability.  The session will expire after one hour of inactivity, so
    in terms of security, it is preferable to number 2.
    
    
    NOTE: The control panel and public pages can each have their own session preference.
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Session {
    
    var $user_session_len = 7200;  // User sessions expire in two hours
    var $cpan_session_len = 3600;  // Admin sessions expire in one hour

    var $c_session        	= 'sessionid';
    var $c_uniqueid       	= 'uniqueid';
    var $c_password       	= 'userhash';
    var $c_expire			= 'expiration';
    var $c_anon             = 'anon';
    var $c_prefix         	= '';
    
    var $sdata            	= array();
    var $userdata         	= array();
    var $tracker            = array();
        
    var $validation_type  	= '';
    var $session_length   	= '';
    
    var $cookies_exist    	= FALSE;
    var $session_exists		= FALSE;
    var $access_cp        	= FALSE;
    
    var $gc_probability   	= 5;  // Garbage collection probability.  Used to kill expired sessions.
    
    var $cache				= array();  // Store data for just this page load.  Multi-dimensional array with module/class name, e.g. $SESS->cache['module']['var_name']


    /** --------------------------------------
    /**  Session constructor
    /** --------------------------------------*/

    function Session()
    {
        global $IN, $DB, $OUT, $PREFS, $FNS, $LOC, $EXT;
        
        // Is the user banned?
        // We only look for banned IPs if it's not a control panel request.
        // We test for banned admins separately in 'core.system.php'
        
        $ban_status = FALSE;
        
        if (REQ != 'CP')
        {
            if ($this->ban_check('ip'))
            {
                switch ($PREFS->ini('ban_action'))
                {
                    case 'message' : return $OUT->fatal_error($PREFS->ini('ban_message'), 0);
                        break;
                    case 'bounce'  : $FNS->bounce($PREFS->ini('ban_destination')); exit;
                        break;
                    default        : $ban_status = TRUE;
                        break;        
                }
            }
        }

		
		/** --------------------------------------
        /**  Set session length.
        /** --------------------------------------*/
        
        $this->session_length = (REQ == 'CP') ? $this->cpan_session_len : $this->user_session_len;
 
		/** --------------------------------------
        /**  Set Default Session Values
        /** --------------------------------------*/
 
        // Set USER-DATA as GUEST until proven otherwise   
             
        $this->userdata = array(
                                'username'          => $IN->GBL('my_name', 'COOKIE'),
                                'screen_name'       => '',
                                'email'             => $IN->GBL('my_email', 'COOKIE'),
                                'url'               => $IN->GBL('my_url', 'COOKIE'),
                                'location'          => $IN->GBL('my_location', 'COOKIE'),
                                'language'          => '',
                                'timezone'          => ($PREFS->ini('default_site_timezone') != '') ? $PREFS->ini('default_site_timezone') : $PREFS->ini('server_timezone'),
                                'daylight_savings'  => ($PREFS->ini('default_site_timezone') != '') ? $PREFS->ini('default_site_dst') : $PREFS->ini('daylight_savings'),
                                'time_format'       => 'us',
                                'group_id'          => '3',
                                'access_cp'         =>  0,
                                'last_visit'		=>  0,
                                'is_banned'         =>  $ban_status,
                                'ignore_list'		=>  array()
                               );
        

        // Set SESSION data as GUEST until proven otherwise
                
        $this->sdata = array(
                                'session_id' 	=>  0,
                                'member_id'  	=>  0,
                                'admin_sess' 	=>  0,
                                'ip_address' 	=>  $IN->IP,
                                'user_agent' 	=>  substr($IN->AGENT, 0, 50),
                                'last_activity'	=>  0
                            );
                            
        // -------------------------------------------
        // 'sessions_start' hook.
        //  - Reset any session class variable
        //  - Override the whole session check
        //  - Modify default/guest settings
        //
        	$edata = $EXT->universal_call_extension('sessions_start', $this);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
                            
		/** --------------------------------------
        /**  Fetch the Session ID
        /** --------------------------------------*/
		
		// A session ID can either come from a cookie or GET data
        
        if ( ! $IN->GBL($this->c_session, 'COOKIE'))
        {
            if ( ! $IN->GBL('S', 'GET'))
            {
                // If session IDs are being used in public pages the session will be found here
            
                if ($IN->SID != '')
                {
                    $this->sdata['session_id'] = $IN->SID;                
                }
            }
            else
            {
                $this->sdata['session_id'] = $IN->GBL('S', 'GET');
            }
        }
        else
        {
            $this->sdata['session_id'] = $IN->GBL($this->c_session, 'COOKIE');
        }
        
		/** --------------------------------------
        /**  Fetch password and unique_id cookies
        /** --------------------------------------*/
                
        if ($IN->GBL($this->c_uniqueid, 'COOKIE')  AND  $IN->GBL($this->c_password, 'COOKIE'))
        {
            $this->cookies_exist = TRUE;
        }
        
		/** --------------------------------------
        /**  Set the Validation Type
        /** --------------------------------------*/

        if (REQ == 'CP')
        {
        	$this->validation = ( ! in_array($PREFS->ini('admin_session_type'), array('cs', 'c', 's'))) ? 'cs' : $PREFS->ini('admin_session_type');
        }
        else
        {
        	$this->validation = ( ! in_array($PREFS->ini('user_session_type'), array('cs', 'c', 's'))) ? 'cs' : $PREFS->ini('user_session_type');
        }
                
		/** --------------------------------------
		/**  Do session IDs exist?
		/** --------------------------------------*/
        
        switch ($this->validation)
        {
        	case 'cs'	: $session_id = ($this->sdata['session_id'] != '0' AND $this->cookies_exist == TRUE) ? TRUE : FALSE;
        		break;
        	case 'c'	: $session_id = ($this->cookies_exist) ? TRUE : FALSE;
        		break;
        	case 's'	: $session_id = ($this->sdata['session_id'] != '0') ? TRUE : FALSE;
        		break;
        }
        
		/** --------------------------------------
		/**  Fetch Session Data
		/** --------------------------------------*/
		
		// IMPORTANT: The session data must be fetched before the member data so don't move this.

		if ($session_id  === TRUE)
		{
			if ($this->fetch_session_data() === TRUE) 
			{
				$this->session_exists = TRUE;
			}
		}

		/** --------------------------------------
		/**  Fetch Member Data
		/** --------------------------------------*/

		$member_data_exists = ($this->fetch_member_data() === TRUE) ? TRUE : FALSE;

		/** --------------------------------------
		/**  Update/Create Session
		/** --------------------------------------*/
		               
		if ($session_id === FALSE OR $member_data_exists === FALSE)
		{ 
			$this->fetch_guest_data();
		}
		else
    	{
			if ($this->session_exists === TRUE)
			{
				$this->update_session();
			}
			else
			{
				if ($this->validation == 'c')
				{
					$this->create_new_session($this->userdata['member_id']);
				}
				else
				{
					$this->fetch_guest_data();
				}
			}
    	}
    	
		/** --------------------------------------
        /**  Update cookies
        /** --------------------------------------*/
        
        $this->update_cookies();
        
		// Fetch "tracker" cookie
        
        if (REQ != 'CP')
        {                     
            $this->tracker = $this->tracker();
		}
		
		/** --------------------------------------
		/**  Kill old sessions
		/** --------------------------------------*/

		$this->delete_old_sessions(); 
		
		/** --------------------------------------
        /**  Merge Session and User Data Arrays
        /** --------------------------------------*/
        
        // We merge these into into one array for portability
        
        $this->userdata = array_merge($this->userdata, $this->sdata);
        
        // -------------------------------------------
        // 'sessions_end' hook.
        //  - Modify the user's session/member data.
        //  - Additional Session or Login methods (ex: log in to other system)
        //
        	$edata = $EXT->universal_call_extension('sessions_end', $this);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        // Garbage collection
        
        unset($this->sdata);
		unset($session_id);
		unset($ban_status);
		unset($member_data_exists);
    }
    /* END */
                
         
                
    /** ----------------------------------------------
    /**  Fetch session data
    /** ----------------------------------------------*/
  
    function fetch_session_data()
    {  
        global $DB, $LOC, $PREFS;

            // Look for session.  Match the user's IP address and browser for added security.
            
            $query = $DB->query("SELECT member_id, admin_sess, last_activity 
                                 FROM   exp_sessions 
                                 WHERE  session_id  = '".$DB->escape_str($this->sdata['session_id'])."'
                                 AND    ip_address  = '".$DB->escape_str($this->sdata['ip_address'])."' 
                                 AND    user_agent  = '".$DB->escape_str($this->sdata['user_agent'])."'".
                                 ((REQ != 'CP') ? " AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'" : '') // Each 'Site' has own Sessions
                                );

            if ($query->num_rows == 0 OR $query->row['member_id'] == 0)
            {
                $this->initialize_session();
            
                return FALSE;               
            }
            
            // Assign member ID to session array
            
            $this->sdata['member_id'] = $query->row['member_id'];
            
            // Is this an admin session?
                            
           $this->sdata['admin_sess'] 	= ($query->row['admin_sess'] == 1) ? 1 : 0;
           
           // Log last activity
           
           $this->sdata['last_activity'] = $query->row['last_activity'];
            
            // If session has expired, delete it and set session data to GUEST
            
            if ($this->validation != 'c')
            {
				if ($query->row['last_activity'] < ($LOC->now - $this->session_length))
				{ 
					$DB->query("DELETE FROM exp_sessions WHERE  session_id  = '".$DB->escape_str($this->sdata['session_id'])."'"); 
					   
					$this->initialize_session();
					
				   return FALSE;
				}
			}
			
        return TRUE;       
    }
    /* END */
  
   
   
    /** ----------------------------------------------
    /**  Fetch guest data
    /** ----------------------------------------------*/
  
    function fetch_guest_data()
    {  
        global $IN, $DB, $LOC, $FNS, $PREFS;

		$query = $DB->query("SELECT * FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id = '3'");
	
		foreach ($query->row as $key => $val)
		{            
			$this->userdata[$key] = $val;                 
		}

		$this->userdata['total_comments']		= 0;                 
		$this->userdata['total_entries']		= 0;     
		$this->userdata['private_messages']		= 0;
		$this->userdata['total_forum_posts']	= 0;
		$this->userdata['total_forum_topics']	= 0;
		$this->userdata['total_forum_replies']	= 0;
		$this->userdata['display_signatures']	= 'y';      
		$this->userdata['display_avatars']		= 'y'; 
		$this->userdata['display_photos']		= 'y'; 

		// The following cookie info is only used with the forum module.
		// It enables us to track "read topics" with users who are not
		// logged in.
		
		// Cookie expiration:  One year
		$expire = (60*60*24*365);
		
		// Has the user been active before? If not we set the "last_activity" to the current time.
		$this->sdata['last_activity'] = ( ! $IN->GBL('last_activity', 'COOKIE')) ? $LOC->now : $IN->GBL('last_activity', 'COOKIE');
		
		// Is the "last_visit" cookie set?  If not, we set the last visit date to ten years ago. 
		// This is a kind of funky thing to do but it enables the forum to show all topics as unread.
		// Since the last_visit stats are only available for logged-in members it doesn't hurt anything to set it this way for guests.
		
		if ( ! $IN->GBL('last_visit', 'COOKIE'))
		{
			$this->userdata['last_visit'] = $LOC->now-($expire*10);
			$FNS->set_cookie('last_visit', $this->userdata['last_visit'], $expire);		
		}
		else
		{
			$this->userdata['last_visit'] = $IN->GBL('last_visit', 'COOKIE');
		}

		// If the user has been inactive longer than the session length we'll
		// set the "last_visit" cooke with the "last_activity" date.
				
        if (($this->sdata['last_activity'] + $this->session_length) < $LOC->now) 
        {
			$this->userdata['last_visit'] = $this->sdata['last_activity'];
			$FNS->set_cookie('last_visit', $this->userdata['last_visit'], $expire);	
        }
        
        // Update the last activity with each page load
		$FNS->set_cookie('last_activity', $LOC->now, $expire);	        
	}
	/* END */
   
                
                
    /** ----------------------------------------------
    /**  Fetch member data
    /** ----------------------------------------------*/
  
    function fetch_member_data()
    {  
        global $IN, $DB, $LOC, $PREFS;

        // Query DB for member data.  Depending on the validation type we'll
        // either use the cookie data or the member ID gathered with the session query.
        
		$sql = " SELECT m.weblog_id, m.tmpl_group_id, m.username, m.screen_name, m.member_id, m.email, m.url, m.location, m.join_date, m.last_visit, m.last_activity, m.total_entries, m.total_comments, m.total_forum_posts, m.total_forum_topics, m.last_forum_post_date, m.language, m.timezone, m.daylight_savings, m.time_format, m.profile_theme, m.forum_theme, m.private_messages, m.accept_messages, m.last_view_bulletins, m.last_bulletin_date, m.display_signatures, m.display_avatars, m.last_email_date, m.notify_by_default, m.ignore_list, ";                       
        
        if (REQ == 'CP')
        {
            $sql .= " m.upload_id, m.cp_theme, m.quick_links, m.quick_tabs, m.template_size, ";
        }
                       
		$sql .= "g.* ";
		
		$sql .= "FROM exp_members AS m, exp_member_groups AS g ";

        if ($this->validation == 'c' || $this->validation == 'cs')
        {
            $sql .= "WHERE	g.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            		 AND 	unique_id = '".$DB->escape_str($IN->GBL($this->c_uniqueid, 'COOKIE'))."'
                     AND    password  = '".$DB->escape_str($IN->GBL($this->c_password, 'COOKIE'))."' 
                     AND    m.group_id = g.group_id";
        }
        else
        {
            $sql .= "WHERE  g.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            		 AND	member_id = '".$DB->escape_str($this->sdata['member_id'])."'
                     AND    m.group_id = g.group_id";
        }
        
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            $this->initialize_session();
            return false;
        }
        
        // Turn the query rows into array values

		foreach ($query->row as $key => $val)
		{
			$this->userdata[$key] = $val;                 
		}
		
		// Create the array for the Ignore List
		$this->userdata['ignore_list'] = ($this->userdata['ignore_list'] == '') ? array() : explode('|', $this->userdata['ignore_list']);
		
		// Fix the values for forum posts and replies
		$this->userdata['total_forum_posts'] = $query->row['total_forum_topics'] + $query->row['total_forum_posts'];
		$this->userdata['total_forum_replies'] = $query->row['total_forum_posts'];
		
		$this->userdata['display_photos'] = $this->userdata['display_avatars'];
		
        /** -----------------------------------------------------
        /**  Are users allowed to localize?
        /** -----------------------------------------------------*/
        
        if ($PREFS->ini('allow_member_localization') == 'n')
        {
        	$this->userdata['timezone'] = ($PREFS->ini('default_site_timezone') != '') ? $PREFS->ini('default_site_timezone') : $PREFS->ini('server_timezone');
       		$this->userdata['daylight_savings'] = ($PREFS->ini('default_site_timezone') != '') ? $PREFS->ini('default_site_dst') : $PREFS->ini('daylight_savings');
 		}
						
        /** -----------------------------------------------------
        /**  Assign Sites, Weblog, Template, and Module Access Privs
        /** -----------------------------------------------------*/
                           
        if (REQ == 'CP')
        {
            // Fetch weblog privileges
            
            $assigned_blogs = array();
         
			if ($this->userdata['group_id'] == 1)
			{
           	 	$result = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs 
           	 						  WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' 
           	 						  AND is_user_blog = 'n'
           	 						  ORDER BY blog_title");
			}
			else
			{
            	$result = $DB->query("SELECT ew.weblog_id, ew.blog_title FROM exp_weblog_member_groups ewmg, exp_weblogs ew
            						  WHERE ew.weblog_id = ewmg.weblog_id
            						  AND ewmg.group_id = '".$DB->escape_str($this->userdata['group_id'])."'
            						  AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            						  ORDER BY ew.blog_title");
            }
            
            if ($result->num_rows > 0)
            {
                foreach ($result->result as $row)
                {
                    $assigned_blogs[$row['weblog_id']] = $row['blog_title'];
                }
            }
            
            $this->userdata['assigned_weblogs'] = $assigned_blogs;

            // Fetch module privileges
            
            $assigned_modules = array();
            
            $result = $DB->query("SELECT module_id FROM exp_module_member_groups WHERE group_id = '".$DB->escape_str($this->userdata['group_id'])."'");
            
            if ($result->num_rows > 0)
            {
                foreach ($result->result as $row)
                {
                    $assigned_modules[$row['module_id']] = TRUE;
                }
            }
                
            $this->userdata['assigned_modules'] = $assigned_modules;
            
            
            // Fetch template group privileges
            
            $assigned_template_groups = array();
            
            $result = $DB->query("SELECT template_group_id FROM exp_template_member_groups WHERE group_id = '".$DB->escape_str($this->userdata['group_id'])."'");
            
            if ($result->num_rows > 0)
            {
                foreach ($result->result as $row)
                {
                    $assigned_template_groups[$row['template_group_id']] = TRUE;
                }
            }
                
            $this->userdata['assigned_template_groups'] = $assigned_template_groups;
            
            // Fetch Assigned Sites Available to User
            
            $assigned_sites = array();
            
			if ($this->userdata['group_id'] == 1)
			{
           	 	$result = $DB->query("SELECT site_id, site_label FROM exp_sites ORDER BY site_label");
			}
			else
			{
				// Those groups that can access the Site's CP, see the site in the 'Sites' pulldown
            	$result = $DB->query("SELECT exp_sites.site_id, exp_sites.site_label 
            						 FROM exp_sites, exp_member_groups
            						 WHERE exp_member_groups.site_id = exp_sites.site_id
            						 AND exp_member_groups.group_id = '".$DB->escape_str($this->userdata['group_id'])."'
            						 AND exp_member_groups.can_access_cp = 'y'
            						 ORDER BY exp_sites.site_label");
            }
            
			if ($result->num_rows > 0)
            {
                foreach ($result->result as $row)
                {
                    $assigned_sites[$row['site_id']] = $row['site_label'];
                }
            }
            
            $this->userdata['assigned_sites'] = $assigned_sites;
        }
		
		
        // Does the member have admin privileges?
        
        if ($query->row['can_access_cp'] == 'y')
        {
            $this->access_cp = TRUE;
        }
        else
        {
            $this->sdata['admin_sess'] = 0; 
        }
        
        // Update the session array with the member_id
        
        if ($this->validation == 'c')
        {
            $this->sdata['member_id'] = $query->row['member_id'];  
        }
             
        // If the user has been inactive for longer than the session length
        // we'll update their last_visit item so that it contains the last_activity
        // date.  That way, we can show the exact time they were last visitng the site.

        if (($this->userdata['last_visit'] == 0) ||
        	(($query->row['last_activity'] + $this->session_length) < $LOC->now))
        {   
        	$last_act = ($query->row['last_activity'] > 0) ? $query->row['last_activity'] : $LOC->now;
        
            $DB->query("UPDATE exp_members set last_visit = '".$last_act."', last_activity = '".$LOC->now."' WHERE member_id = '".$DB->escape_str($this->sdata['member_id'])."'");
        
        	$this->userdata['last_visit'] = $query->row['last_activity'];
        }        
                        
        // Update member 'last activity' date field for this member.
        // We update this ever 5 minutes.  It's used with the session table
        // so we can update sessions
        
        if (($query->row['last_activity'] + 300) < $LOC->now)     
        {        
            $DB->query("UPDATE exp_members set last_activity = '".$LOC->now."' WHERE member_id = '".$DB->escape_str($this->sdata['member_id'])."'");
        }
        

        return true;  
    }
    /* END */
       
            
  
    /** ----------------------------------------------
    /**  Update Member session
    /** ----------------------------------------------*/

    function update_session()
    {  
        global $DB, $FNS, $LOC;
        
        $this->sdata['last_activity'] = $LOC->now;
        
		$DB->query($DB->update_string('exp_sessions', $this->sdata, "session_id ='".$DB->escape_str($this->sdata['session_id'])."'")); 

        // Update session ID cookie
        
        if ($this->validation == 'cs')
        {
            $FNS->set_cookie($this->c_session , $this->sdata['session_id'],  $this->session_length);   
        }
            
        // If we only require cookies for validation, set admin session.   
            
        if ($this->validation == 'c'  AND  $this->access_cp == TRUE)
        {            
            $this->sdata['admin_sess'] = 1;
        }   
        
       	// We'll unset the "last activity" item from the session data array.
       	// We do this to avoid a conflict with the "last_activity" item in the
       	// userdata array since we'll be merging the two arrays in a later step
        
        unset($this->sdata['last_activity']);
    }  
    /* END */


    /** ----------------------------------------------
    /**  Create New Session
    /** ----------------------------------------------*/

    function create_new_session($member_id, $admin_session = FALSE)
    {  
        global $DB, $IN, $LOC, $FNS, $PREFS;
              
		if ($this->validation == 'c' AND $this->access_cp == TRUE)
		{
            $this->sdata['admin_sess'] = 1;
		}
		else
		{
			$this->sdata['admin_sess'] 	= ($admin_session == FALSE) ? 0 : 1;  
		}
				
		$this->sdata['session_id'] 		= $FNS->random();  
		$this->sdata['last_activity']	= $LOC->now;  
		$this->sdata['user_agent']		= substr($IN->AGENT, 0, 50);
		$this->sdata['ip_address']  	= $IN->IP;  
		$this->sdata['member_id']  		= $member_id; 
		$this->sdata['site_id']  		= $PREFS->ini('site_id'); 
		$this->userdata['member_id']	= $member_id;  
		$this->userdata['session_id']	= $this->sdata['session_id'];
		$this->userdata['site_id']		= $PREFS->ini('site_id');
		
		if ($this->validation != 's')
		{
			$FNS->set_cookie($this->c_session , $this->sdata['session_id'], $this->session_length);   
		}
					
		$DB->query($DB->insert_string('exp_sessions', $this->sdata));   
		
		return $this->sdata['session_id'];
    }  
    /* END */
  
  
    /** ----------------------------------------------
    /**  Reset session data as GUEST
    /** ----------------------------------------------*/
  
    function initialize_session()
    {  
        $this->sdata['session_id'] = 0;   
        $this->sdata['admin_sess'] = 0;
        $this->sdata['member_id']  = 0;
    }
    /* END */


    /** ----------------------------------------------
    /**  Update Cookies
    /** ----------------------------------------------*/
  
    function update_cookies()
    {  
		global $IN, $FNS;

        if ($this->cookies_exist == TRUE AND $IN->GBL($this->c_expire, 'COOKIE'))
        {
			$now 	= time() + 300;
			$expire = 60*60*24*365;
			
			if ($IN->GBL($this->c_expire, 'COOKIE') > $now)
			{ 
				$FNS->set_cookie($this->c_expire , time()+$expire, $expire);
				$FNS->set_cookie($this->c_uniqueid , $IN->GBL($this->c_uniqueid, 'COOKIE'), $expire);       
				$FNS->set_cookie($this->c_password , $IN->GBL($this->c_password, 'COOKIE'), $expire); 		

			}
        }
	}
	/* END */
   
    /** ----------------------------------------------
    /**  Fetch a session item
    /** ----------------------------------------------*/
  
    function userdata($which)
    {  
    	return ( ! isset($this->userdata[$which])) ? FALSE : $this->userdata[$which];
	}
	/* END */
         

    /** --------------------------------
    /**  Tracker
    /** --------------------------------*/
    
    // This functions lets us store the visitor's last five pages viewed
    // in a cookie.  We use this to facilitate redirection after logging-in,
    // or other form submissions
        
    function tracker()
    {    
		global $IN, $FNS, $REGX;
		
		$tracker = $IN->GBL('tracker', 'COOKIE');

		if ($tracker != FALSE)
		{
			if (preg_match("#(http:\/\/|https:\/\/|www\.|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $tracker))
			{
				return array();
			}
				
			if (strpos($tracker, ':') !== FALSE)
			{
				$tracker_parts = explode(':', $tracker);
				
				if (current($tracker_parts) != 'a' OR sizeof($tracker_parts) < 3 OR ! is_numeric(next($tracker_parts)))
				{
					return array();
				}
			}
			
			$tracker = unserialize(stripslashes($tracker));
		}
		
		if ( ! is_array($tracker))
		{
			$tracker = array();
		}
				
		$URI = ($IN->URI == '') ? 'index' : $IN->URI;
		
		$URI = str_replace("\\", "/", $URI); 
		
		// If someone is messing with the URI we won't set the cookie
	
		 if ( ! preg_match("#^[a-z0-9\%\_\/\-]+$#i", $URI) && ! isset($_GET['ACT']))
		 {
			return array();
		 }
		
		if ( ! isset($_GET['ACT']))
		{
			if ( ! isset($tracker['0']))
			{
				$tracker[] = $URI;
			}
			else
			{
				if (count($tracker) == 5)
				{
					array_pop($tracker);
				}

				if ($tracker['0'] != $URI)
				{
					array_unshift($tracker, $URI);
				}
			}
			
		}
	    
	    if (REQ == 'PAGE')
	    {	    
            $FNS->set_cookie('tracker', serialize($tracker), '0'); 
		}
		
		return $tracker;
    }
    /* END */
      


    /** ----------------------------------------------
    /**  Check for banned data
    /** ----------------------------------------------*/
  
    function ban_check($type = 'ip', $match = '')
    {  
        global $IN, $FNS, $PREFS, $OUT;
            
		switch ($type)
		{
			case 'ip'			: $ban = $PREFS->ini('banned_ips');
								  $match = $IN->IP;
				break;
			case 'email'		: $ban = $PREFS->ini('banned_emails');
				break;
			case 'username'		: $ban = $PREFS->ini('banned_usernames');
				break;
			case 'screen_name'	: $ban = $PREFS->ini('banned_screen_names');
				break;
		}
        
        if ($ban == '')
        {
            return FALSE;
        }
        
        foreach (explode('|', $ban) as $val)
        {
        	if ($val == '*') continue;
        
        	if (substr($val, -1) == '*')
			{
				$val = str_replace("*", "", $val);

				if (preg_match("#^".preg_quote($val,'#')."#", $match))
				{
					return TRUE;
				}
			}
			elseif (substr($val, 0, 1) == '*')
			{ 
				$val = str_replace("*", "", $val);
        	
				if (preg_match("#".preg_quote($val, '#')."$#", $match))
				{
					return TRUE;
				}
			}
			else
			{
				if (preg_match("#^".preg_quote($val, '#')."$#", $match))
				{
					return TRUE;
				}
			}
        }

        return false;
    }
    /* END */


	/** ----------------------------------------
	/**  Is the nation banned?
	/** ----------------------------------------*/
	
	function nation_ban_check($show_error = TRUE)
	{
		global $PREFS, $OUT, $DB, $IN;
		
		if ($PREFS->ini('require_ip_for_posting') != 'y' OR $PREFS->ini('ip2nation') != 'y')
		{
			return;
		}

		$query = $DB->query("SELECT country FROM exp_ip2nation WHERE ip < INET_ATON('".$DB->escape_str($IN->IP)."') ORDER BY ip DESC LIMIT 0,1");

		if ($query->num_rows == 1)
		{
			$result = $DB->query("SELECT COUNT(*) AS count FROM exp_ip2nation_countries WHERE code = '".$query->row['country']."' AND banned = 'y'");

			if ($result->row['count'] > 0)
			{
				if ($show_error == TRUE)
				{
					return $OUT->fatal_error($PREFS->ini('ban_message'), 0);
				}
				else
				{
					return FALSE;
				}
			}
		}
	}
	/* END */


     
    /** ----------------------------------------------
    /**  Delete old sessions if probability is met
    /** ----------------------------------------------*/
    
    // By default, the probablility is set to 10 percent.
    // That means sessions will only be deleted one
    // out of ten times a page is loaded.
    
    function delete_old_sessions()
    {    
        global $DB, $PREFS, $LOC;
                
        $expire = $LOC->now - $this->session_length;
  
        srand(time());
  
        if ((rand() % 100) < $this->gc_probability) 
        {                 
            $DB->query("DELETE FROM exp_sessions WHERE last_activity < $expire");             
        }    
    }
    /* END */
    
    
    
    /** ----------------------------------------------
    /**  Save password lockout
    /** ----------------------------------------------*/
        
    function save_password_lockout($username = '')
    {    
        global $IN, $DB, $PREFS;
        
		if ($PREFS->ini('password_lockout') == 'n')
		{
         	return; 
        } 
    
		$query = $DB->query("INSERT INTO exp_password_lockout (login_date, ip_address, user_agent, username) VALUES ('".time()."', '".$DB->escape_str($IN->IP)."', '".$DB->escape_str($this->userdata['user_agent'])."', '".$DB->escape_str($username)."')");
    }
    /* END */
    
    
    
    /** ----------------------------------------------
    /**  Check password lockout
    /** ----------------------------------------------*/
        
    function check_password_lockout($username = '')
    {    
        global $IN, $DB, $PREFS;
        
		if ($PREFS->ini('password_lockout') == 'n')
		{
         	return FALSE; 
        } 
        
        if ($PREFS->ini('password_lockout_interval') == '')
        {
         	return FALSE; 
        }
        
        $interval = $PREFS->ini('password_lockout_interval') * 60;
        
        $expire = time() - $interval;
  
  		$sql = "SELECT count(*) AS count 
  				FROM exp_password_lockout 
  				WHERE login_date > $expire 
  				AND ip_address = '".$DB->escape_str($IN->IP)."'
  				AND (user_agent = '".$DB->escape_str($this->userdata['user_agent'])."'
					OR username = '".$DB->escape_str($username)."'
					)";
						
		$query = $DB->query($sql);

		if ($query->row['count'] >= 4)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
    }
    /* END */
    
    
    /** ----------------------------------------------
    /**  Delete old password lockout data
    /** ----------------------------------------------*/
        
    function delete_password_lockout()
    {    
        global $DB, $PREFS;
        
		if ($PREFS->ini('password_lockout') == 'n')
		{
         	return FALSE; 
        } 
                
        $interval = $PREFS->ini('password_lockout_interval') * 60;
        
        $expire = time() - $interval;
  
        srand(time());
  
        if ((rand() % 100) < $this->gc_probability) 
        {                 
            $DB->query("DELETE FROM exp_password_lockout WHERE login_date < $expire");             
        }    
    }
    /* END */
}
// END CLASS
?>