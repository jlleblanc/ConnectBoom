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
 File: cp.home.php
-----------------------------------------------------
 Purpose: The control panel home page
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Home {

	var $limit		= 10;  // The number of items to show in the "recent entries" and "recent comments" display
	var $methods 	= array();
	var $query	 	= array();
	var $messages	= array();
	var $stats_ct	= 0;
	var $style_one 	= 'tableCellOne';
	var $style_two 	= 'tableCellTwo';
	
	var $conn_failure = FALSE;

    /** -----------------------------
    /**  Constructor
    /** -----------------------------*/

    function Home()
    {
        global $IN, $DB, $PREFS, $FNS, $LANG, $DSP, $LOC, $SESS;
        
		/** --------------------------------
		/**  Does the install file exist?
		/** --------------------------------*/
		
		// If so, we will issue a warning  

        $path = str_replace($PREFS->ini('system_folder'), '', PATH);
        
		if ($PREFS->ini('demo_date') === FALSE && @file_exists($FNS->remove_double_slashes($path.'/install'.EXT)))
		{
			$this->messages[] = $DSP->qdiv('alert', $LANG->line('install_lock_warning'));
			$this->messages[] = $DSP->qdiv('itemWrapper', $LANG->line('install_lock_removal'));
		}
		
		/** --------------------------------
		/**  Demo account expiration
		/** --------------------------------*/
		
		// We use this code for pMachine.com demos.
		// Since it's only a couple lines of code we'll leave it in 
		// the master files even though it's not needed for normal use.

		if ($PREFS->ini('demo_date'))
		{
			$expiration = ( ! $PREFS->ini('demo_expiration')) ? (60*60*24*30) : $PREFS->ini('demo_expiration');
			$this->messages[] = $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('demo_expiration').NBS.NBS.$LOC->format_timespan(($PREFS->ini('demo_date') + $expiration) - time())));
		}
		// -- End Demo Code
		
		
		/** --------------------------------
		/**  Referrer pruning
		/** --------------------------------*/
	
		if ($PREFS->ini('log_referrers') == 'y')
		{
			$query = $DB->query("SELECT count(ref_id) AS count FROM exp_referrers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
			$max_refs = $PREFS->ini('max_referrers');
			
			if ( ! is_numeric($max_refs) OR $max_refs == 0)
				$max_refs = 500;
	
			if ($query->row['count'] > $max_refs)
			{
				$query = $DB->query("SELECT MAX(ref_id) AS max_id FROM exp_referrers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				
				if ($query->num_rows > 0 && is_numeric($query->row['max_id']))
				{			
					$id = $query->row['max_id'] - $max_refs;
					$DB->query("DELETE FROM exp_referrers WHERE ref_id < $id AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				}
			}
		}
		
		/** --------------------------------
		/**  Version Check
		/** --------------------------------*/
		
		if ($SESS->userdata['group_id'] == 1 AND $PREFS->ini('new_version_check') == 'y')
		{
			$page_url = 'http://expressionengine.com/eeversion.txt';
			$target = parse_url($page_url);
			
			$fp = @fsockopen($target['host'], 80, $errno, $errstr, 5);
			
			if (is_resource($fp))
			{
				fputs ($fp,"GET ".$page_url." HTTP/1.0\r\n" ); 
				fputs ($fp,"Host: ".$target['host'] . "\r\n" ); 
				fputs ($fp,"User-Agent: EE/EllisLab PHP/\r\n");
				fputs ($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n\r\n");
			
				$ver = '';
			
				while ( ! feof($fp))
				{
					$ver .= trim(fgets($fp, 128));
				}
				
				fclose($fp);
							
				if ($ver != '')
				{
					$ver = trim(str_replace('Version:', '', strstr($ver, 'Version:')));
					
					if ($ver > APP_VER)
					{
						$line = str_replace('%s', $ver, $LANG->line('new_version_available'));
					
						$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';	
						$this->messages[] = $DSP->qdiv('success', $DSP->anchor($FNS->fetch_site_index().$qm.'URL=https://secure.expressionengine.com/download.php', $line));
					}
				}
			}
			else
			{
				$this->conn_failure = TRUE;
			}
		}
		
        // Available methods
        
        $this->methods = array(	
								'recent_entries',
								'recent_comments',
								'site_statistics',
								'notepad',
								'bulletin_board',
								'pmachine_news_feed'
							);
								
		if ($DSP->allowed_group('can_access_admin') === TRUE)
		{  
			$this->methods[] = 'recent_members';
			$this->methods[] = 'member_search_form';
		}						

        switch($IN->GBL('M'))
        {
            case 'notepad_update'		: $this->notepad_update();
				break;
            default	 					: $this->home_page();
				break;
		}						
    }
    /* END */
    
    
        
    /** -----------------------------
    /**  Control panel home page
    /** -----------------------------*/
    
    function home_page()
    {  
        global $SESS, $LANG, $DB, $DSP, $EXT, $PREFS;
        
        // -------------------------------------------
        // 'control_panel_home_page' hook.
        //  - Allows complete rewrite of CP home page
        //
        	$edata = $EXT->universal_call_extension('control_panel_home_page', $this);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
		/** ----------------------------------
		/**  Fetch stats
		/** ----------------------------------*/
        
        $sql = "SELECT * FROM exp_stats WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND ";
        
        if (USER_BLOG !== FALSE)
        {
			$sql .= " weblog_id = '".$DB->escape_str(UB_BLOG_ID)."'";         
        }
        else
        {
        	$sql .= " weblog_id = '0'";
        }
        
        $this->query = $DB->query($sql);
                           
		$DSP->title = $LANG->line('main_menu');
		
		//$LANG->fetch_language_file('myaccount');
		//$DSP->crumb = $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'M=homepage', $LANG->line('customize_cp'));
		                                     
		/** ----------------------------------
		/**  Fetch the user display prefs
		/** ----------------------------------*/
		
		// We'll fill two arrays.  One containing the left side options, the other containing the right side

		$left 	= array();
		$right 	= array();

		$query = $DB->query("SELECT * FROM exp_member_homepage WHERE member_id = '".$DB->escape_str($SESS->userdata('member_id'))."'");

		if ($query->num_rows > 0)
		{
			foreach ($query->row as $key => $val)
			{
				if ($val == 'l')
				{
					$left[$query->row[$key.'_order'].'_'.$key] = $key;
				}
				elseif ($val == 'r')
				{
					$right[$query->row[$key.'_order'].'_'.$key] = $key;
				}
			}
		}
		
		/** ----------------------------------
		/**  Sort the arrays
		/** ----------------------------------*/
		
		ksort($left);
		ksort($right);
		
		reset($left);
		reset($right);
		
		/** ----------------------------------
		/**  Build the page heading
		/** ----------------------------------*/
		
        $user = ($SESS->userdata['screen_name'] == '') ? $SESS->userdata['username'] : $SESS->userdata['screen_name'];

		$DSP->right_crumb($LANG->line('current_user').NBS.NBS.$user, BASE.AMP.'C=myaccount');
    		
		/** ----------------------------------
		/**  Show system messages if they exist
		/** ----------------------------------*/

		if (count($this->messages) > 0)
		{
			$DSP->body	.=	$DSP->div('box');
			foreach ($this->messages as $msg)
			{
				$DSP->body .= $msg;
			}
			
			$DSP->body .=	$DSP->div_c();
			$DSP->body .= $DSP->qdiv('defaultSmall', '');
		}	
		
		$DSP->body	.=	$DSP->table('', '0', '0', '100%');
		
		/** ----------------------------------
		/**  Build the left page display
		/** ----------------------------------*/
        
        if (count($left) > 0)
        {
			$DSP->body	.=	$DSP->tr();
			$DSP->body	.=	$DSP->td('leftColumn', '50%', '', '', 'top');
        
        	foreach ($left as $meth)
        	{
        		if (in_array($meth, $this->methods))
        		{
        			$DSP->body .= $this->$meth();
					$DSP->body .= $DSP->qdiv('defaultSmall', '');
        		}
				// -------------------------------------------
				// 'control_panel_home_page_left_option' hook.
				//  - Allows adding of new option to left site of CP home page
				//
				elseif ($EXT->active_hook('control_panel_home_page_left_option') === TRUE)
				{
					$DSP->body .= $EXT->call_extension('control_panel_home_page_left_option', $meth);
				}	
				//
				// -------------------------------------------
        	}
        	
			$DSP->body	.=	$DSP->td_c();        	
        }
        
		/** ----------------------------------
		/**  Build the right page display
		/** ----------------------------------*/
                
        if (count($right) > 0)
        {
			$DSP->body	.=	$DSP->td('rightColumn', '50%', '', '', 'top');
        
        	foreach ($right as $meth)
        	{
        		if (in_array($meth, $this->methods))
        		{
        			$DSP->body .= $this->$meth();
					$DSP->body .= $DSP->qdiv('defaultSmall', '');
        		}
        		// -------------------------------------------
				// 'control_panel_home_page_right_option' hook.
				//  - Allows adding of new option to right site of CP home page
				//
				elseif ($EXT->active_hook('control_panel_home_page_right_option') === TRUE)
				{
					$DSP->body .= $EXT->call_extension('control_panel_home_page_right_option', $meth);
				}	
				//
				// -------------------------------------------
        	}

			$DSP->body	.=	$DSP->td_c();        	
        }
        		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->table_c();
    }
    /* END */
    
    
    
  
    /** -----------------------------
    /**  Recent entries
    /** -----------------------------*/
    
    function recent_entries()
    {  
		global $DB, $DSP, $LANG, $FNS, $SESS, $PREFS;
		    	
        $sql = "SELECT 
                       exp_weblog_titles.weblog_id, 
					   exp_weblog_titles.author_id,
                       exp_weblog_titles.entry_id,         
                       exp_weblog_titles.title, 
                       exp_weblog_titles.comment_total, 
                       exp_weblog_titles.trackback_total
                FROM   exp_weblog_titles, exp_weblogs
				WHERE  exp_weblogs.weblog_id = exp_weblog_titles.weblog_id";
                                
        if ($SESS->userdata['weblog_id'] != 0)
        {        
        	$sql .= " AND exp_weblog_titles.weblog_id = '".$SESS->userdata['weblog_id']."'"; 
        }
        else
        {
            $sql .= " AND is_user_blog = 'n' AND exp_weblog_titles.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
				
			if ($SESS->userdata['group_id'] != 1)
            { 
				if ( ! $DSP->allowed_group('can_view_other_entries') AND
					 ! $DSP->allowed_group('can_edit_other_entries') AND
					 ! $DSP->allowed_group('can_delete_all_entries'))
				{
				
					$sql .= " AND exp_weblog_titles.author_id = '".$SESS->userdata('member_id')."' ";
				}
            
                $allowed_blogs = $FNS->fetch_assigned_weblogs();
                
                // If the user is not assigned a weblog we want the
                // query to return false, so we'll use a dummy ID number
                
                if (count($allowed_blogs) == 0)
                {
                    $sql .= " AND exp_weblog_titles.weblog_id = '0'";
                }
                else
                {
                    $sql .= " AND exp_weblog_titles.weblog_id IN (";
                
                    foreach ($allowed_blogs as $val)
                    {
                        $sql .= "'".$val."',"; 
                    }
                    
                    $sql = substr($sql, 0, -1).')';
                }
           }            
        }
        

        $sql .= " ORDER BY entry_date desc LIMIT ".$this->limit; 
        
		$query = $DB->query($sql);
    	
		/** -----------------------------
		/**  Define alternating style
		/** -----------------------------*/
		
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		/** -----------------------------
		/**  Table Header
		/** -----------------------------*/

        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading',
                                ($query->num_rows == 0) ? 
                                	array($LANG->line('most_recent_entries')) : 
                                	array($LANG->line('most_recent_entries'), $LANG->line('comments'))
                                ).
              $DSP->tr_c();
			  
		/** -----------------------------
		/**  Table Rows
		/** -----------------------------*/
              
        if ($query->num_rows == 0)
        {
			$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
									array(
											$LANG->line('no_entries')
										  )
									);
        }
        else
        {
			foreach ($query->result as $row)
			{
				$total = $row['comment_total'] + $row['trackback_total'];
				
				$which = 'view_entry';
								
				if ($row['author_id'] == $SESS->userdata('member_id'))
				{
					$which = 'edit_entry';
				}
				else
				{
					if ($DSP->allowed_group('can_edit_other_entries'))
					{
						$which = 'edit_entry';
					}
				}
							
				$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
										array(
										
											$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M='.$which.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'], $row['title'])),
											$DSP->qspan('', $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'], '('.$total.')'))
											  )
										);
			}	
        }
        
        $r .= $DSP->table_c(); 
        
    	return $r;
	}
	/* END */
  
  
    /** -----------------------------
    /**  Recent comments
    /** -----------------------------*/
    
    function recent_comments()
    {  
    	global $DB, $DSP, $LANG, $SESS, $FNS, $LOC, $PREFS;
    	    	
        $sql = "SELECT 
                       exp_weblog_titles.weblog_id, 
                       exp_weblog_titles.author_id,
                       exp_weblog_titles.entry_id,         
                       exp_weblog_titles.title, 
                       exp_weblog_titles.recent_comment_date,
                       exp_weblog_titles.recent_trackback_date
                FROM   exp_weblog_titles, exp_weblogs
				WHERE  exp_weblogs.weblog_id = exp_weblog_titles.weblog_id";
                        
        if ($SESS->userdata['weblog_id'] != 0)
        {        
        		$sql .= " AND exp_weblog_titles.weblog_id = '".$SESS->userdata['weblog_id']."' AND exp_weblog_titles.site_id = '".$DB->escape_str($PREFS->ini('site_id')) ."' "; 
        }
        else
        {
            $sql .= " AND is_user_blog = 'n' AND exp_weblog_titles.site_id = '".$DB->escape_str($PREFS->ini('site_id')) ."' ";
				
			if ($SESS->userdata['group_id'] != 1)
			{        
				if ( ! $DSP->allowed_group('can_view_other_comments') AND
					 ! $DSP->allowed_group('can_moderate_comments') AND
					 ! $DSP->allowed_group('can_delete_all_comments') AND
					 ! $DSP->allowed_group('can_edit_all_comments'))
				{
				
					$sql .= " AND exp_weblog_titles.author_id = '".$SESS->userdata('member_id')."' ";
				}
				
				$allowed_blogs = $FNS->fetch_assigned_weblogs();
				
				// If the user is not assigned a weblog we want the
				// query to return false, so we'll use a dummy ID number
				
				if (count($allowed_blogs) == 0)
				{
					$sql .= " AND exp_weblog_titles.weblog_id = '0'";
				}
				else
				{
					$sql .= " AND exp_weblog_titles.weblog_id IN (";
				
					foreach ($allowed_blogs as $val)
					{
						$sql .= "'".$val."',"; 
					}
					
					$sql = substr($sql, 0, -1).')';
				}
			}
        }
                                
        $sql .= " AND (recent_comment_date != '' || recent_trackback_date != '')
				  ORDER BY GREATEST(recent_comment_date, recent_trackback_date) desc
        		  	LIMIT ".$this->limit; 

		$query = $DB->query($sql);
		
		/** -----------------------------
		/**  Define alternating style
		/** -----------------------------*/
		
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		/** -----------------------------
		/**  Table Header
		/** -----------------------------*/

        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading',
                                ($query->num_rows == 0) ? 
                                	array($LANG->line('most_recent_comments')) : 
                                	array($LANG->line('most_recent_comments'), $LANG->line('date'))
                                ).
              $DSP->tr_c();
              
		/** -----------------------------
		/**  Table Rows
		/** -----------------------------*/

        if ($query->num_rows == 0)
        {
			$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
									array(
											$LANG->line('no_comments')
										  )
									);
        }
        else
        {
			foreach ($query->result as $row)
			{			
				$date = ($row['recent_comment_date'] > $row['recent_trackback_date']) ? $row['recent_comment_date'] : $row['recent_trackback_date'];
			
				$entry_url = BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'];
			
				$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
										array(
												$DSP->qdiv('defaultBold', $DSP->anchor($entry_url, $row['title'])),
												$DSP->qdiv('nowrap', $LOC->set_human_time($date))
											  )
										);
			}
		}	
        
        $r .= $DSP->table_c(); 

		return $r;
	}
	/* END */



    /** -----------------------------
    /**  Recent members
    /** -----------------------------*/
    
    function recent_members()
    {  
    		global $DB, $DSP, $LANG, $LOC, $SESS;
    	
        $sql = "SELECT member_id, username, screen_name, group_id, join_date
                FROM   exp_members
                ORDER BY join_date desc
                LIMIT 10";

		$query = $DB->query($sql);
    	
		/** -----------------------------
		/**  Define alternating style
		/** -----------------------------*/
		
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		/** -----------------------------
		/**  Table Header
		/** -----------------------------*/

        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading',
                                array(
                                		$LANG->line('recent_members'), 
                                		$LANG->line('join_date')
                                	 )
                                ).
              $DSP->tr_c();
              
		/** -----------------------------
		/**  Table Rows
		/** -----------------------------*/
				
		foreach ($query->result as $row)
		{
			$name = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
		
			$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
									array(
									
										$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], $name)),
										$LOC->set_human_time($row['join_date'])
										  )
									);
		}	
        
        $r .= $DSP->table_c(); 

		return $r;
	}
	/* END */



    /** -----------------------------
    /**  Site statistics
    /** -----------------------------*/
    
    function site_statistics()
    {  
    		global $DSP, $LANG, $PREFS, $SESS, $DB;
    	
		/** -----------------------------
		/**  Define alternating style
		/** -----------------------------*/
		
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		/** -----------------------------
		/**  Table Header
		/** -----------------------------*/

        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', 
                                array(
                                        $LANG->line('site_statistics'),
                                        $LANG->line('value')
                                     )
                                ).
              $DSP->tr_c();
  
  
		if ($SESS->userdata['group_id'] == 1)
		{    	
			$r .= $this->system_status();
			$r .= $this->system_version();
		}
		
		$r .= $this->total_weblog_entries();
		
		$r .= $this->total_comments();							

		$r .= $this->total_trackbacks();							
	
		$r .= $this->total_hits();
		
		if ($SESS->userdata['group_id'] == 1)
		{    	
			$r .= $this->total_members();
	
			$r .= $this->total_validating_members();			
		}		
		
        if ($DSP->allowed_group('can_moderate_comments'))
        {
			$r .= $this->total_validating_comments();
        }        
		
        $r .= $DSP->table_c(); 

		return $r;
	}
	/* END */


	/** -----------------------------
	/**  Version Data
	/** -----------------------------*/
  
	function system_version()
	{
  		global $LANG, $DSP;
  		
        $LANG->fetch_language_file('modules');
  		
		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('module_version')),
										APP_VER
									  )
								);
	}  
  	/* END */



	/** -----------------------------
	/**  Total Hits
	/** -----------------------------*/
	
	function total_hits()
	{
  		global $DB, $LANG, $SESS, $DSP;	

		$sql = "SELECT SUM(exp_templates.hits) AS total
				FROM   exp_templates, exp_template_groups
				WHERE  exp_templates.group_id = exp_template_groups.group_id";
		
        if ($SESS->userdata['weblog_id'] != 0)
        {        
        		$sql .= " AND exp_templates.group_id = '".$DB->escape_str(UB_TMP_GRP)."'"; 
        }
        else
        {
            $sql .= " AND exp_template_groups.is_user_blog = 'n'";
        }
		
				
		$query = $DB->query($sql);

		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('total_hits')),
										$query->row['total']
									  )
								);	
	}
	/* END */



	/** -----------------------------
	/**  Total Validating Members
	/** -----------------------------*/
			
	function total_validating_members()
	{  
  		global $DB, $LANG, $DSP, $PREFS;
  		
  		$total = 0;
  		
		if ($PREFS->ini('req_mbr_activation') == 'manual')
		{  		
			$query = $DB->query("SELECT count(member_id) AS count FROM exp_members WHERE group_id = '4'");
	
			$total = $query->row['count'] ;
		}
		
		$link = ($total > 0) ? $DSP->required().NBS.$DSP->anchor(BASE.AMP.'C=admin&M=members&P=member_validation', $LANG->line('total_validating_members')) : $LANG->line('total_validating_members');

		return $DSP->table_qrow(($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $link),
										$total
									  )
								);
		
  	}
  	/* END */


	/** -----------------------------
	/**  Total Validating Comments
	/** -----------------------------*/
			
	function total_validating_comments()
	{  
  		global $DB, $LANG, $DSP, $PREFS;
  		
  		$total = 0;
	
		$query = $DB->query("SELECT count(comment_id) AS count FROM exp_comments WHERE status = 'c' AND site_id = '".$DB->escape_str($PREFS->ini('site_id')) ."'");

		$total = $query->row['count'];
		
		$link = ($total > 0) ? $DSP->required().NBS.$DSP->anchor(BASE.AMP.'C=edit&M=view_comments&validate=1', $LANG->line('total_validating_comments')) : $LANG->line('total_validating_comments');

		return $DSP->table_qrow(($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $link),
										$total
									  )
								);
		
  	}
  	/* END */


	/** -----------------------------
	/**  Total Members
	/** -----------------------------*/
  
	function total_members()
	{
  		global $DB, $LANG, $DSP;
  		
		$query = $DB->query("SELECT count(member_id) AS count FROM exp_members");

		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('total_members')),
										$query->row['count']
									  )
								);
	}  
  	/* END */



	/** -----------------------------
	/**  Total Trackbacks
	/** -----------------------------*/
			
	function total_trackbacks()
	{ 
  		global $DB, $LANG, $DSP;

		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('total_trackbacks')),
										$this->query->row['total_trackbacks']
									  )
								);
  	}
	/* END */
	
	
	  
	/** -----------------------------
	/**  Total Comments
	/** -----------------------------*/
			
	function total_comments()
	{  
  		global $DB, $LANG, $DSP;

		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('total_comments')),
										$this->query->row['total_comments']
									  )
								);
	}
	/* END */
	
	
	  
	/** -----------------------------
	/**  Total Weblog Entries
	/** -----------------------------*/

	function total_weblog_entries()
	{  
  		global $DB, $LANG, $DSP;
				
		return $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('total_entries')),
										$this->query->row['total_entries']
									  )
								);
	}
	/* END */



	/** -----------------------------
	/**  System status
	/** -----------------------------*/

	function system_status()
	{
  		global $DB, $LANG, $DSP, $PREFS;
		
		$r = $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
								array(
										$DSP->qspan('defaultBold', $LANG->line('system_status')),
										($PREFS->ini('is_system_on') == 'y') ? $DSP->qdiv('highlight_alt_bold', $LANG->line('online')) : $DSP->qdiv('highlight_bold', $LANG->line('offline'))
									  )
								);
								
		if ($PREFS->ini('multiple_sites_enabled') == 'y')
		{
			$r .= $DSP->table_qrow( ($this->stats_ct++ % 2) ? $this->style_one : $this->style_two, 
									array(
											$DSP->qspan('defaultBold', $LANG->line('site_status')),
											($PREFS->ini('is_site_on') == 'y' && $PREFS->ini('is_system_on') == 'y') ? $DSP->qdiv('highlight_alt_bold', $LANG->line('online')) : $DSP->qdiv('highlight_bold', $LANG->line('offline'))
										  )
									);
		}
		
		return $r;
	}  
  	/* END */



    /** -----------------------------
    /**  Member search form
    /** -----------------------------*/
    
    function member_search_form()
    {  
        global $LANG, $DSP, $DB, $SESS, $PREFS;

        $r = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=do_member_search'));
        
        $r .= $DSP->div('box');
        
		$r .= $DSP->heading($LANG->line('member_search') ,5);

		$r .= $DSP->qdiv('itemWrapper', $LANG->line('search_instructions', 'keywords'));

		$r .= $DSP->qdiv('itemWrapper', $DSP->input_text('keywords', '', '35', '100', 'input', '100%'));

        $r .= $DSP->input_select_header('criteria');
        $r .= $DSP->input_select_option('username', 	$LANG->line('search_by'));
		$r .= $DSP->input_select_option('username', 	$LANG->line('username'));
		$r .= $DSP->input_select_option('screen_name', 	$LANG->line('screen_name'));
		$r .= $DSP->input_select_option('email',		$LANG->line('email_address'));
		$r .= $DSP->input_select_option('url', 			$LANG->line('url'));
		$r .= $DSP->input_select_option('ip_address', 	$LANG->line('ip_address'));
		
		$query = $DB->query("SELECT m_field_label, m_field_id FROM exp_member_fields ORDER BY m_field_label");
		
		if ($query->num_rows > 0)
		{
			$r .= $DSP->input_select_option('username', '---');
			
			foreach($query->result as $row)
			{
				$r .= $DSP->input_select_option('m_field_id_'.$row['m_field_id'], $row['m_field_label']);
			}
		}
		
        $r .= $DSP->input_select_footer();
                              
        // Member group select list
		
		if ($SESS->userdata['group_id'] != '1')
		{
        	$query = $DB->query("SELECT group_id, group_title FROM  exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '1' order by group_title");
        }
        else
        {
        	$query = $DB->query("SELECT group_id, group_title FROM  exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_title");
        }
              
        $r.= $DSP->input_select_header('group_id');
        
        $r.= $DSP->input_select_option('any', $LANG->line('member_group'));
        $r.= $DSP->input_select_option('any', $LANG->line('any'));
                                
        foreach ($query->result as $row)
        {                                
            $r .= $DSP->input_select_option($row['group_id'], $row['group_title']);
        }
        
        $r .= $DSP->input_select_footer();
        
        $r .= NBS.$LANG->line('exact_match').NBS.$DSP->input_checkbox('exact_match', 'y').NBS.NBS;
        
        $r.= $DSP->input_submit($LANG->line('submit'));
        
        // END select list
        
        $r.= $DSP->div_c();
                        
        $r.= $DSP->form_close();
        
        return $r;
	}
	/* END */
	
    /** -----------------------------
    /**  Validating members
    /** -----------------------------*/
    
    function validating_members()
    {  
    	global $DSP;
    	
  		return  $DSP->heading('validating_members', 5);
	}
	/* END */
	
  
    /** -----------------------------
    /**  Bulletin Board
    /** -----------------------------*/
    
    function bulletin_board()
    {  
        global $DB, $DSP, $SESS, $LANG, $LOC;
                
        $query = $DB->query("SELECT m.screen_name, b.bulletin_message, b.bulletin_date
        					 FROM exp_member_bulletin_board b, exp_members m
        					 WHERE b.sender_id = m.member_id
        					 AND b.bulletin_group = '".$DB->escape_str($SESS->userdata('group_id'))."' 
        					 AND bulletin_date < ".$LOC->now."
        					 AND 
        					 (
        					 	b.bulletin_expires > ".$LOC->now."
        					 	OR
        					 	b.bulletin_expires = 0
        					 )
        					 ORDER BY b.bulletin_date DESC
        					 LIMIT 2");
        					 
        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', $LANG->line('bulletin_board')).
              $DSP->tr_c();
              
        $i = 0;
        					 
        if ($query->num_rows == 0)
        {
        	$r .= $DSP->table_qrow( ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', 
									array(
											$LANG->line('no_bulletins')
										  )
									);
        }
        else
		{	
			foreach($query->result as $row)
			{
				$r .= $DSP->table_qrow( ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', 
										array(
												$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('bulletin_sender')).':'.NBS.$row['screen_name']).
												$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('bulletin_date')).':'.NBS.$LOC->set_human_time($row['bulletin_date'])).
												$DSP->qdiv('itemWrapper', $DSP->input_textarea('notepad', $row['bulletin_message'], 10, 'textarea', '100%', "readonly='readonly'"))
											  )
										);
			}
		}
		
        return $r.$DSP->table_c();
	}
	/* END */
	
	/** -----------------------------
    /**  Notepad
    /** -----------------------------*/
    
    function notepad()
    {  
        global $DB, $DSP, $SESS, $LANG;
                
        $query = $DB->query("SELECT notepad, notepad_size FROM exp_members WHERE member_id = '".$DB->escape_str($SESS->userdata('member_id'))."'");
        
    		return
        		 $DSP->form_open(array('action' => 'C=home'.AMP.'M=notepad_update'))
        		.$DSP->qdiv('tableHeading', $LANG->line('notepad'))
        		.$DSP->input_textarea('notepad', $query->row['notepad'], 10, 'textarea', '100%')
        		.$DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
        		.$DSP->form_close();    	
	}
	/* END */
	

    /** ----------------------------------
    /**  Update notepad
    /** ----------------------------------*/
    
    function notepad_update()
    {  
        global $DB, $FNS, $SESS;

        $DB->query("UPDATE exp_members SET notepad = '".$DB->escape_str($_POST['notepad'])."' WHERE member_id ='".$SESS->userdata('member_id')."'");
        
        $FNS->redirect(BASE);
        exit;    
    }
    /* END */
	
	
	/** -------------------------------------
	/**  pMachine News Feed
	/** -------------------------------------*/
	
	function pmachine_news_feed()
	{
		global $DB, $DSP, $PREFS, $FNS, $SESS, $LANG, $LOC;
		
		if ($this->conn_failure === TRUE OR ! file_exists(PATH_PI.'pi.magpie'.EXT))
		{
			return $r = '';
		}

        $r  = $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', $LANG->line('pmachine_news_feed')).
              $DSP->tr_c();

		define('MAGPIE_CACHE_AGE', 60*60*24*3); // set cache to 3 days
		define('MAGPIE_CACHE_DIR', PATH_CACHE.'magpie_cache/');
		define('MAGPIE_DEBUG', 0);

		if ( ! class_exists('Magpie'))
        {
        	require PATH_PI.'pi.magpie'.EXT;
        }
		
        $feed = fetch_rss('http://expressionengine.com/feeds/rss/cpnews/');

		$i = 0;
		
		if ( ! is_object($feed) OR count($feed->items) == 0)
		{
			$r .= $DSP->table_qrow( ($i++ %2) ? 'tableCellOne' : 'tableCellTwo',
									array($LANG->line('no_news'))
									);
		}
		else
		{
			$total = count($feed->items);
			$j = 0;
			
			ob_start();
			?>
<script type="text/javascript"> 
<!--

function showHide(el)
{
	if (document.getElementById(el).style.display == 'block')
	{
		document.getElementById(el).style.display = 'none';
	}
	else
	{
		document.getElementById(el).style.display = 'block';
	};
}

//-->
</script>
			<?php
			
			$buffer = ob_get_contents();
	        ob_end_clean();         
	        $r .= $buffer;
			
			$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
			
			for ($i = 0; $i < $total, $i < 3; $i++)
			{
				$title = $feed->items[$i]['title'];

				$date = $LOC->set_human_time($LOC->set_gmt(strtotime(preg_replace("/(20[10][0-9]\-[0-9]{2}\-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2})Z/", 
																				 '\\1 \\2 UTC',
																				 $feed->items[$i]['pubdate']))));
				$content = $feed->items[$i]['description'];
				$link = $feed->items[$i]['link'];
				
				if ( ! class_exists('Typography'))
				{
					require PATH_CORE.'core.typography'.EXT;
				}

				$TYPE = new Typography;
				
				$content = $TYPE->parse_type($content, 
												  		array(
																'text_format'   => 'xhtml',
																'html_format'   => 'y',
																'auto_links'    => 'y',
																'allow_img_url' => 'y'
																)
								 			);
								
				$r .= $DSP->table_qrow( ($j++ %2) ? 'tableCellOne' : 'tableCellTwo',
										array(
												$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$link, $title, "onclick='showHide(\"news_item_{$i}\"); return false;'").NBS.'('.$date.')')).
												$DSP->qdiv('itemWrapper', $content, "news_item_{$i}", "style='display: none;'")
												)
										);			
			}
			
			$r .= $DSP->table_qrow( ($j++ %2) ? 'tableCellOne' : 'tableCellTwo',
									array(
											$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL=http://expressionengine.com/blog/',  $LANG->line('more_news'), "onclick='window.open(this.href); return false;'")))
											)
									);
		}
		
        return $r.$DSP->table_c();
	}
	/* END */
	
	
}
// END CLASS
?>