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
 File: mod.comment.php
-----------------------------------------------------
 Purpose: Commenting class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Comment {

	// Maximum number of comments.  This is a safety valve
	// in case the user doesn't specify a maximum

	var $limit = 100;
	
	
	// Show anchor?
	// TRUE/FALSE
	// Determines whether to show the <a name> anchor above each comment
	
	var $show_anchor = FALSE; 
	
	
	// Comment Expiration Mode
	// 0 -	Comments only expire if the comment expiration field in the PUBLISH page contains a value.
	// 1 -	If the comment expiration field is blank, comments will still expire if the global preference
	// 		is set in the Weblog Preferences page.  Use this option only if you used EE prior to
	//		version 1.1 and you want your old comments to expire.
	
	var $comment_expiration_mode = 0;


	function Comment()
	{
		global $REGX;
				
		$fields = array('name', 'email', 'url', 'location', 'comment');
		
		foreach ($fields as $val)
		{
			if (isset($_POST[$val] ))
			{
				$_POST[$val] = $REGX->encode_ee_tags($_POST[$val], TRUE);
				
				if ($val == 'comment')
				{
					$_POST[$val] = $REGX->xss_clean($_POST[$val]);
				}
			}
		}
	}	
	/* END */



    /** ----------------------------------------
    /**  Comment Entries
    /** ----------------------------------------*/

    function entries()
    {
        global $IN, $DB, $TMPL, $LOC, $PREFS, $REGX, $FNS, $SESS, $EXT;
        
        // Base variables
        
        $return 		= '';
        $current_page	= '';
        $qstring		= $IN->QSTR;
        $uristr			= $IN->URI;
        $switch 		= array();
		$search_link	= '';

        // Pagination variables
		
		$paginate			= FALSE;
		$paginate_data		= '';
		$pagination_links	= '';
		$page_next			= '';
		$page_previous		= '';
		$current_page		= 0;
		$t_current_page		= '';
		$total_pages		= 1;
				
		if ($TMPL->fetch_param('dynamic') == 'off')
		{
			$dynamic = FALSE;
		}
		else
		{
			$dynamic = TRUE;
		}
		
		$force_entry = FALSE;
		
		if ($TMPL->fetch_param('entry_id') !== FALSE OR $TMPL->fetch_param('url_title') !== FALSE)
		{
			$force_entry = TRUE;
		}		
    
        /** ----------------------------------------------
        /**  Do we allow dynamic POST variables to set parameters?
        /** ----------------------------------------------*/

		if ($TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{			
			foreach (explode('|', $TMPL->fetch_param('dynamic_parameters')) as $var)
			{			
				if (isset($_POST[$var]) AND in_array($var, array('weblog', 'limit', 'sort', 'orderby')))
				{
					$TMPL->tagparams[$var] = $_POST[$var];
				}
			}
		}		
        		                
		/** --------------------------------------
		/**  Parse page number
		/** --------------------------------------*/
		
		// We need to strip the page number from the URL for two reasons:
		// 1. So we can create pagination links
		// 2. So it won't confuse the query with an improper proper ID
				
		if ( ! $dynamic)
		{
			if (preg_match("#N(\d+)#", $qstring, $match) OR preg_match("#/N(\d+)#", $qstring, $match))
			{
				$current_page = $match['1'];	
				$uristr  = $FNS->remove_double_slashes(str_replace($match['0'], '', $uristr));
			}
			
		}
		else
		{		
			if (preg_match("#/P(\d+)#", $qstring, $match))
			{
				$current_page = $match['1'];	
				
				$uristr  = $FNS->remove_double_slashes(str_replace($match['0'], '', $uristr));
				$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
			}
		}
		
		if  ($dynamic == TRUE OR $force_entry == TRUE)
		{
			// see if entry_id or url_title parameter is set
			if ($entry_id = $TMPL->fetch_param('entry_id'))
			{	
				$entry_sql = " entry_id = '".$DB->escape_str($entry_id)."' ";
			}
			elseif ($url_title = $TMPL->fetch_param('url_title'))
			{
				$entry_sql = " url_title = '".$DB->escape_str($url_title)."' ";
			}
			else
			{
				// If there is a slash in the entry ID we'll kill everything after it.
				$entry_id = trim($qstring); 		
				$entry_id = preg_replace("#/.+#", "", $entry_id);
				$entry_sql = ( ! is_numeric($entry_id)) ? " url_title = '".$DB->escape_str($entry_id)."' " : " entry_id = '".$DB->escape_str($entry_id)."' ";
			}
		
			/** ----------------------------------------
			/**  Do we have a vaild entry ID number?
			/** ----------------------------------------*/
			
			$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;
					
			$sql = "SELECT entry_id, exp_weblog_titles.weblog_id 
					FROM exp_weblog_titles, exp_weblogs 
					WHERE exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
					AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."') ";
			
			if ($TMPL->fetch_param('show_expired') !== 'yes')
			{
				$sql .= "AND (expiration_date = 0 || expiration_date > ".$timestamp.") ";
			}
			
			$sql .= "AND status != 'closed' AND ";
			
			$sql .= $entry_sql;
	
			/** ----------------------------------------------
			/**  Limit to/exclude specific weblogs
			/** ----------------------------------------------*/
		
			if (USER_BLOG !== FALSE)
			{
				// If it's a "user blog" we limit to only their assigned blog			
				$sql .= " AND exp_weblogs.weblog_id = '".$DB->escape_str(UB_BLOG_ID)."' ";		
			}
			else
			{ 
				$sql .= "AND exp_weblogs.is_user_blog = 'n' ";
			
				if ($weblog = $TMPL->fetch_param('weblog') OR $TMPL->fetch_param('site'))
				{
					$xql = "SELECT weblog_id FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') ";
				
					if ($weblog !== FALSE)
					{
						$xql .= $FNS->sql_andor_string($weblog, 'blog_name');
					}
					
					$query = $DB->query($xql);
				
					if ($query->num_rows == 1)
					{
						$sql .= "AND exp_weblog_titles.weblog_id = '".$query->row['weblog_id']."' ";
					}
					elseif ($query->num_rows > 1)
					{
						$sql .= "AND (";
						
						foreach ($query->result as $row)
						{
							$sql .= "exp_weblog_titles.weblog_id = '".$row['weblog_id']."' OR ";
						}
						
						$sql = substr($sql, 0, - 3);
						
						$sql .= ") ";
					}
				}
			}				

			$query = $DB->query($sql);
			
			// Bad ID?  See ya!
			
			if ($query->num_rows == 0)
			{
				return false;
			}
			unset($sql);
			
			// We'll reassign the entry ID so it's the true numeric ID
							
			$entry_id = $query->row['entry_id'];		
		}                
        
        
        // If the comment tag is being used in freeform mode
        // we need to fetch the weblog ID numbers
        
        $w_sql = '';
        
        if ( ! $dynamic)
        {		
			if (USER_BLOG !== FALSE)
			{
				// If it's a "user blog" we limit to only their assigned blog
			
				$w_sql .= "AND weblog_id = '".UB_BLOG_ID."' ";
			}
			else
			{			
				if ($weblog = $TMPL->fetch_param('weblog') OR $TMPL->fetch_param('site'))
				{
					$xql = "SELECT weblog_id FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') ";
				
					if ($weblog !== FALSE)
					{
						$xql .= $FNS->sql_andor_string($weblog, 'blog_name');
					}

					$query = $DB->query($xql);
					
					if ($query->num_rows == 0)
					{
						return $TMPL->no_results();
					}
					else
					{
						if ($query->num_rows == 1)
						{
							$w_sql .= "AND weblog_id = '".$query->row['weblog_id']."' ";
						}
						else
						{
							$w_sql .= "AND (";
							
							foreach ($query->result as $row)
							{
								$w_sql .= "weblog_id = '".$row['weblog_id']."' OR ";
							}
							
							$w_sql = substr($w_sql, 0, - 3);
							
							$w_sql .= ") ";
						}
					}
				}
			}        
        }
			
        /** ----------------------------------------
        /**  Set trackback flag
        /** ----------------------------------------*/
        
        // Depending on whether the {if trackbacks} conditional
        // is present will determine whether we need to show trackbacks
        
        $show_trackbacks = (preg_match("/".LD."if\s+trackbacks".RD.".+?".LD.SLASH."if".RD."/s", $TMPL->tagdata)) ? TRUE : FALSE;

		/** ----------------------------------------
		/**  Set sorting and limiting
		/** ----------------------------------------*/
		
		if ( ! $dynamic)
		{
			$limit = ( ! $TMPL->fetch_param('limit')) ? 100 : $TMPL->fetch_param('limit');
			$sort  = ( ! $TMPL->fetch_param('sort'))  ? 'desc' : $TMPL->fetch_param('sort');
		}
		else
		{
			$limit = ( ! $TMPL->fetch_param('limit')) ? $this->limit : $TMPL->fetch_param('limit');
			$sort  = ( ! $TMPL->fetch_param('sort'))  ? 'asc' : $TMPL->fetch_param('sort');
		}
		
		$allowed_sorts = array('date', 'email', 'location', 'name', 'url');

        
		/** ----------------------------------------
		/**  Fetch comment ID numbers
		/** ----------------------------------------*/
        
        $temp = array();
        $i = 0;        
        
		$comments_exist = FALSE;
		
		// Left this here for backward compatibility
		// We need to deprecate the "order_by" parameter
		
		if ($TMPL->fetch_param('orderby') != '')
		{
			$order_by = $TMPL->fetch_param('orderby');
		}
		else
		{
			$order_by = $TMPL->fetch_param('order_by');
		}		
	
		$order_by  = ($order_by == 'date' OR ! in_array($order_by, $allowed_sorts))  ? 'comment_date' : $order_by;		
		
		if ( ! $dynamic)
		{
			// When we are only showing comments and it is not based on an entry id or url title
			// in the URL, we can make the query much more efficient and save some work.
		
			$e_sql = (isset($entry_id) && $entry_id != '') ? "AND entry_id = '".$DB->escape_str($entry_id)."' ": '';
			
			if ($show_trackbacks === FALSE)
			{
				$this_page = ($current_page == '' || ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;
				$this_sort = (strtolower($sort) == 'desc') ? 'DESC' : 'ASC';
		
				$sql = "SELECT comment_date, comment_id FROM exp_comments 
						WHERE status = 'o' ".$e_sql.$w_sql." 
						ORDER BY ".$order_by." ".$this_sort."
						LIMIT {$this_page}, ".$limit;
				
				$query = $DB->query($sql);
				
				$count_query = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE status = 'o' ".$e_sql.$w_sql);
				
				$total_rows = $count_query->row['count'];
			}
			else
			{
				$sql = "SELECT comment_date, comment_id FROM exp_comments WHERE status = 'o' ".$e_sql.$w_sql." ORDER BY ".$order_by;		
			}
			
			$query = $DB->query($sql);
		}
		else
		{
			$query = $DB->query("SELECT comment_date, comment_id FROM exp_comments WHERE entry_id = '".$DB->escape_str($entry_id)."' AND status = 'o' ORDER BY ".$order_by);	
		}

		if ($query->num_rows > 0)
		{
			$comments_exist = TRUE;
			foreach ($query->result as $row)
			{
				$key = $row['comment_date'];
				
				while(isset($temp[$key]))
				{
					$key++;
				}
				
				$temp[$key] = 'c'.$row['comment_id'];
			}
		}
		
		/** ----------------------------------------
		/**  Fetch trackback ID numbers
		/** ----------------------------------------*/
	
		$trackbacks_exist = FALSE;
		
		if ($show_trackbacks)
		{
			if ( ! $dynamic)
			{
				$t_sql = '';
			
				if ($w_sql != '')
				{
					$t_sql = trim($w_sql);
					
					$t_sql = "WHERE ".substr($t_sql, 3);
				}
			
				$sql = "SELECT trackback_date, trackback_id FROM exp_trackbacks ".$t_sql." ORDER BY trackback_date";	
				
				$query = $DB->query($sql);
			}
			else
			{
				$query = $DB->query("SELECT trackback_date, trackback_id FROM exp_trackbacks WHERE entry_id = '".$DB->escape_str($entry_id)."' ORDER BY trackback_date");	
			}		
	
			if ($query->num_rows > 0)
			{
				$trackbacks_exist = TRUE;
				foreach ($query->result as $row)
				{
					$key = $row['trackback_date'];
				
					while(isset($temp[$key]))
					{
						$key++;
					}
					
					$temp[$key] = 't'.$row['trackback_id'];
				}
			}
		}
		
        /** ------------------------------------
        /**  No results?  No reason to continue...
        /** ------------------------------------*/
		
		if (count($temp) == 0)
		{
        	return $TMPL->no_results();
		}
		
		// Sort the array based on the keys (which contain the Unix timesamps
		// of the comments and trackbacks)
		
		if ($order_by == 'comment_date')
		{
			ksort($temp);
		}
			
		// Create a new, sequentially indexed array
		
		$result_ids = array();
		
		foreach ($temp as $val)
		{
			$result_ids[$val] = $val;
		}
	
		// Reverse the array if order is descending
		
		if ($sort == 'desc')
		{
			$result_ids = array_reverse($result_ids);
		}	
		
        /** ---------------------------------
        /**  Do we need pagination?
        /** ---------------------------------*/
        
        // When showing only comments and no using the URL, then we already have this value
        
        if ($dynamic OR $show_trackbacks === TRUE)
        {
        	$total_rows = count($result_ids);
        }
        
		if (preg_match("/".LD."paginate(.*?)".RD."(.+?)".LD.SLASH."paginate".RD."/s", $TMPL->tagdata, $match))
		{
			$paginate		= TRUE;
			$paginate_data	= $match['2'];
			$anchor = '';
			
			if ($match['1'] != '')
			{
				if (preg_match("/anchor.*?=[\"|\'](.+?)[\"|\']/", $match['1'], $amatch))
				{
					$anchor = '#'.$amatch['1'];
				}
			}
		
			$TMPL->tagdata = preg_replace("/".LD."paginate.*?".RD.".+?".LD.SLASH."paginate".RD."/s", "", $TMPL->tagdata);
						
			$current_page = ($current_page == '' || ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;
			
			if ($current_page > $total_rows)
			{
				$current_page = 0;
			}
						
			$t_current_page = floor(($current_page / $limit) + 1);
			$total_pages	= intval(floor($total_rows / $limit));
			
			if ($total_rows % $limit) 
				$total_pages++;
			
			if ($total_rows > $limit)
			{
				if ( ! class_exists('Paginate'))
				{
					require PATH_CORE.'core.paginate'.EXT;
				}
				
				$PGR = new Paginate();

				$deft_tmpl = '';
				
				if ($uristr == '')
				{
					if (USER_BLOG !== FALSE)
					{			
						$query = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$DB->escape_str(UB_TMP_GRP)."'");
						$deft_tmpl = $query->row['group_name'].'/index/';
					}
					else
					{
				
						if ($PREFS->ini('template_group') == '')
						{
							$query = $DB->query("SELECT group_name FROM exp_template_groups WHERE is_site_default = 'y' AND is_user_blog = 'n'");
							$deft_tmpl = $query->row['group_name'].'/index/';
						}
						else
						{
							$deft_tmpl  = $PREFS->ini('template_group').'/';
							$deft_tmpl .= ($PREFS->ini('template') == '') ? 'index' : $PREFS->ini('template');
							$deft_tmpl .= '/';
						}
					}
				}
													
				$basepath = $FNS->remove_double_slashes($FNS->create_url($uristr, 1, 0).'/'.$deft_tmpl);
				
				$first_url = (substr($basepath, -5) == '.php/') ? substr($basepath, 0, -1) : $basepath;
				
				if ($TMPL->fetch_param('paginate_base'))
				{				
					$pbase = $REGX->trim_slashes($TMPL->fetch_param('paginate_base'));
					
					$pbase = str_replace("&#47;index", "/", $pbase);
					
					if ( ! strstr($basepath, $pbase))
					{
						$basepath = $FNS->remove_double_slashes($basepath.'/'.$pbase.'/');
					}
				}				
				
				$PGR->first_url 	= $first_url;
				$PGR->path			= $basepath;
				$PGR->prefix		= ( ! $dynamic) ? 'N' : 'P';
				$PGR->total_count 	= $total_rows;
				$PGR->per_page		= $limit;
				$PGR->cur_page		= $current_page;
				$PGR->suffix		= $anchor;
				
				$pagination_links = $PGR->show_links();
				
				if ((($total_pages * $limit) - $limit) > $current_page)
				{
					$page_next = $basepath.'P'.($current_page + $limit).'/';
				}
				
				if (($current_page - $limit ) >= 0) 
				{						
					$page_previous = $basepath.'P'.($current_page - $limit).'/';
				}
			}
			else
			{
				$current_page = '';
			}
		}
		
		// When only non-dynamic comments are show, all results are valid as the 
		// query is restricted with a LIMIT clause
		
		if ($dynamic OR $show_trackbacks === TRUE)
		{
			if ($current_page == '')
			{		
				$result_ids = array_slice($result_ids, 0, $limit);	
			}
			else
			{
				$result_ids = array_slice($result_ids, $current_page, $limit);	
			}
		}
		
        /** -----------------------------------
        /**  Fetch Comments if necessary
        /** -----------------------------------*/
        
        $results = $result_ids;
        $mfields = array();
                
		if ($comments_exist == TRUE)
		{
			$com = '';
			foreach ($result_ids as $val)
			{
				if (substr($val, 0, 1) == 'c')
				{
					$com .= substr($val, 1).",";
				}
			}
			
			if ($com != '')
			{	
				/** ----------------------------------------
				/**  "Search by Member" link
				/** ----------------------------------------*/
				// We use this with the {member_search_path} variable
				
				$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $TMPL->tagdata, $match)) ? $match['1'] : 'search/results';
				$result_path = str_replace("\"", "", $result_path);
				$result_path = str_replace("'",  "", $result_path);
		
				$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
				$search_link = $FNS->fetch_site_index(0, 0).$qs.'ACT='.$FNS->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';

				$sql = "SELECT 
						exp_comments.comment_id, exp_comments.entry_id, exp_comments.weblog_id, exp_comments.author_id, exp_comments.name, exp_comments.email, exp_comments.url, exp_comments.location as c_location, exp_comments.ip_address, exp_comments.comment_date, exp_comments.edit_date, exp_comments.comment, exp_comments.notify, exp_comments.site_id AS comment_site_id,
						exp_members.location, exp_members.occupation, exp_members.interests, exp_members.aol_im, exp_members.yahoo_im, exp_members.msn_im, exp_members.icq, exp_members.group_id, exp_members.member_id, exp_members.signature, exp_members.sig_img_filename, exp_members.sig_img_width, exp_members.sig_img_height, exp_members.avatar_filename, exp_members.avatar_width, exp_members.avatar_height, exp_members.photo_filename, exp_members.photo_width, exp_members.photo_height, 
						exp_member_data.*,
						exp_weblog_titles.title, exp_weblog_titles.url_title, exp_weblog_titles.author_id AS entry_author_id,
						exp_weblogs.comment_text_formatting, exp_weblogs.comment_html_formatting, exp_weblogs.comment_allow_img_urls, exp_weblogs.comment_auto_link_urls, exp_weblogs.blog_url, exp_weblogs.comment_url, exp_weblogs.blog_title 
						FROM exp_comments 
						LEFT JOIN exp_weblogs ON exp_comments.weblog_id = exp_weblogs.weblog_id 
						LEFT JOIN exp_weblog_titles ON exp_comments.entry_id = exp_weblog_titles.entry_id 
						LEFT JOIN exp_members ON exp_members.member_id = exp_comments.author_id 
						LEFT JOIN exp_member_data ON exp_member_data.member_id = exp_members.member_id
						WHERE exp_comments.comment_id  IN (".substr($com, 0, -1).")";
				
				$query = $DB->query($sql);
				
				if ($query->num_rows > 0)
				{
					$i = 0;
					foreach ($query->result as $row)
					{
						if (isset($results['c'.$row['comment_id']]))
						{
							$results['c'.$row['comment_id']] = $query->result[$i];
							$i++;
						}
					}
				}
				
				/** ----------------------------------------
				/**  Fetch custom member field IDs
				/** ----------------------------------------*/
			
				$query = $DB->query("SELECT m_field_id, m_field_name FROM exp_member_fields");
						
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{        		
						$mfields[$row['m_field_name']] = $row['m_field_id'];
					}
				}
								
			}			
        }
		
			
        /** -----------------------------------
        /**  Fetch Trackbacks if necessary
        /** -----------------------------------*/
		
		if ($trackbacks_exist == TRUE)
        {
			$trb = '';
			foreach ($result_ids as $val)
			{			
				if (substr($val, 0, 1) == 't')
				{
					$trb .= substr($val, 1).",";
				}
			}
			
			if ($trb != '')
			{
				$sql = "SELECT 
						exp_trackbacks.trackback_id, exp_trackbacks.title, exp_trackbacks.content, exp_trackbacks.weblog_name, exp_trackbacks.trackback_url, exp_trackbacks.trackback_date, exp_trackbacks.trackback_ip,
						exp_weblog_titles.weblog_id, exp_weblog_titles.allow_trackbacks, exp_weblog_titles.url_title
						FROM exp_trackbacks 
						LEFT JOIN exp_weblog_titles ON (exp_weblog_titles.entry_id = exp_trackbacks.entry_id)
						WHERE exp_trackbacks.trackback_id IN (".substr($trb, 0, -1).")";			
			
				$query = $DB->query($sql);
				
				if ($query->num_rows > 0)
				{
					$i = 0;
					foreach ($query->result as $row)
					{
						if (isset($results['t'.$row['trackback_id']]))
						{
							$results['t'.$row['trackback_id']] = $query->result[$i];
							$i++;
						}
					}
				}
			}
        }
        
        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $TYPE = new Typography(FALSE, FALSE);
        
        
        /** ----------------------------------------
        /**  Fetch all the date-related variables
        /** ----------------------------------------*/
        
        $gmt_comment_date	= array();
        $comment_date		= array();
        $trackback_date		= array();
        $edit_date			= array();
        
        // We do this here to avoid processing cycles in the foreach loop
        
        $date_vars = array('gmt_comment_date', 'comment_date', 'trackback_date', 'edit_date');
                
		foreach ($date_vars as $val)
		{					
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
					$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
					
					switch ($val)
					{
						case 'comment_date' 	: $comment_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_comment_date' : $gmt_comment_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'trackback_date'	: $trackback_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'edit_date'		: $edit_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}
		
		/** ----------------------------------------
        /**  Protected Variables for Cleanup Routine
        /** ----------------------------------------*/
		
		// Since comments do not necessarily require registration, and since
		// you are allowed to put member variables in comments, we need to kill
		// left-over unparsed junk.  The $member_vars array is all of those
		// member related variables that should be removed.
		
		$member_vars = array('location', 'occupation', 'interests', 'aol_im', 'yahoo_im', 'msn_im', 'icq', 
							 'signature', 'sig_img_filename', 'sig_img_width', 'sig_img_height', 
							 'avatar_filename', 'avatar_width', 'avatar_height', 
							 'photo_filename', 'photo_width', 'photo_height');
							 
		$member_cond_vars = array();
		
		foreach($member_vars as $var)
		{
			$member_cond_vars[$var] = '';
		}
                
        
        /** ----------------------------------------
        /**  Start the processing loop
        /** ----------------------------------------*/
        
        $item_count = 0;
        
        $relative_count = 0;
        $absolute_count = ($current_page == '') ? 0 : $current_page;
        $total_results  = sizeof($results);
        
        foreach ($results as $id => $row)
        {        
        	if ( ! is_array($row))
        		continue;
        		
        	$relative_count++;
        	$absolute_count++;
        	
        	$row['count']			= $relative_count;
            $row['absolute_count']	= $absolute_count;
            $row['total_comments']	= $total_rows;
            $row['total_results']	= $total_results;
        
        	// This lets the {if location} variable work
        	
			if ($comments_exist == TRUE AND isset($row['author_id']))
			{
				if ($row['author_id'] == 0)
					$row['location'] = $row['c_location'];
			}
			
            $tagdata = $TMPL->tagdata;
            
            // -------------------------------------------
			// 'comment_entries_tagdata' hook.
			//  - Modify and play with the tagdata before everyone else
			//
				if ($EXT->active_hook('comment_entries_tagdata') === TRUE)
				{
					$tagdata = $EXT->call_extension('comment_entries_tagdata', $tagdata, $row);
					if ($EXT->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------
            
            /** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/

			$cond = array_merge($member_cond_vars, $row);
			$cond['comments']		= (substr($id, 0, 1) == 't') ? 'FALSE' : 'TRUE';
			$cond['trackbacks']		= (substr($id, 0, 1) == 'c') ? 'FALSE' : 'TRUE';
			$cond['logged_in']			= ($SESS->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= ($SESS->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$cond['allow_comments'] 	= (isset($row['allow_comments']) AND $row['allow_comments'] == 'n') ? 'FALSE' : 'TRUE';
			$cond['allow_trackbacks'] 	= (isset($row['allow_trackbacks']) AND $row['allow_trackbacks'] == 'n') ? 'FALSE' : 'TRUE';
			$cond['signature_image']	= ( ! isset($row['sig_img_filename']) OR $row['sig_img_filename'] == '' OR $PREFS->ini('enable_signatures') == 'n' OR $SESS->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']				= ( ! isset($row['avatar_filename']) OR $row['avatar_filename'] == '' OR $PREFS->ini('enable_avatars') == 'n' OR $SESS->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']				= ( ! isset($row['photo_filename']) OR $row['photo_filename'] == '' OR $PREFS->ini('enable_photos') == 'n' OR $SESS->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['is_ignored']			= ( ! isset($row['member_id']) OR ! in_array($row['member_id'], $SESS->userdata['ignore_list'])) ? 'FALSE' : 'TRUE';
			
			if ( isset($mfields) && is_array($mfields) && sizeof($mfields) > 0)
			{
				foreach($mfields as $key => $value)
				{
					if (isset($row['m_field_id_'.$value]))
						$cond[$key] = $row['m_field_id_'.$value];
				}
			}
			
			$tagdata = $FNS->prep_conditionals($tagdata, $cond);
     
            /** ----------------------------------------
            /**  Parse "single" variables
            /** ----------------------------------------*/

            foreach ($TMPL->var_single as $key => $val)
            { 
            
				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/
				
				if (strncmp($key, 'switch', 6) == 0)
				{
					$sparam = $FNS->assign_parameters($key);
					
					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = @explode("|", $sparam['switch']);
						
						$sw = $sopt[($relative_count + count($sopt) - 1) % count($sopt)];
						
						/*  Old style switch parsing
						/*
						if (count($sopt) == 2)
						{ 
							if (isset($switch[$sparam['switch']]) AND $switch[$sparam['switch']] == $sopt['0'])
							{
								$switch[$sparam['switch']] = $sopt['1'];
								
								$sw = $sopt['1'];									
							}
							else
							{
								$switch[$sparam['switch']] = $sopt['0'];
								
								$sw = $sopt['0'];									
							}
						}
						*/
					}
					
					$tagdata = $TMPL->swap_var_single($key, $sw, $tagdata);
				}
              
            
            
                /** ----------------------------------------
                /**  parse permalink
                /** ----------------------------------------*/
                
                if (strncmp('permalink', $key, 9) == 0 && isset($row['comment_id']))
                {                     
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($uristr.'#'.$row['comment_id'], 0, 0), 
														$tagdata
													 );
                }                

                /** ----------------------------------------
                /**  parse comment_path or trackback_path
                /** ----------------------------------------*/
                
                if (preg_match("#^(comment_path|trackback_path|entry_id_path)#", $key))
                {                       
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($FNS->extract_path($key).'/'.$row['entry_id']), 
														$tagdata
													 );
                }


                /** ----------------------------------------
                /**  parse title permalink
                /** ----------------------------------------*/
                
                if (preg_match("#^(title_permalink|url_title_path)#", $key))
                { 
					$path = ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX') ? $FNS->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($path, 1, 0), 
														$tagdata
													 );
                }
            
                /** ----------------------------------------
                /**  parse comment date
                /** ----------------------------------------*/
                
                if (isset($comment_date[$key]) AND $comments_exist == TRUE AND isset($row['comment_date']))
                {                
					foreach ($comment_date[$key] as $dvar)
					{
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['comment_date'], TRUE), $val);		
					}

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
                
                /** ----------------------------------------
                /**  parse GMT comment date
                /** ----------------------------------------*/
                
                if (isset($gmt_comment_date[$key]) AND $comments_exist == TRUE AND isset($row['comment_date']))
                {                
					foreach ($gmt_comment_date[$key] as $dvar)
					{
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['comment_date'], FALSE), $val);		
					}

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
                
                /** ----------------------------------------
                /**  parse trackback date
                /** ----------------------------------------*/
              
                if (isset($trackback_date[$key]) AND $trackbacks_exist == TRUE AND isset($row['trackback_date']))
                {
					foreach ($trackback_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['trackback_date'], TRUE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
                
                /** ----------------------------------------
                /**  parse "last edit" date
                /** ----------------------------------------*/
                
                if (isset($edit_date[$key]))
                {
                	if (isset($row['edit_date']))
                	{
						foreach ($edit_date[$key] as $dvar)
							$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $LOC->timestamp_to_gmt($row['edit_date']), TRUE), $val);					
	
						$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                	}                
                }

                
                /** ----------------------------------------
                /**  {member_search_path}
                /** ----------------------------------------*/
                
				if (strncmp('member_search_path', $key, 18) == 0)
                {                   
					$tagdata = $TMPL->swap_var_single($key, $search_link.$row['author_id'], $tagdata);
                }
                
                
                // Prep the URL
                
                if (isset($row['url']))
                {
                	$row['url'] = $REGX->prep_url($row['url']);
            	}
            	
            	/** ----------------------------------------
                /**  {author}
                /** ----------------------------------------*/

                if ($key == "author")
                {                    
                   	$tagdata = $TMPL->swap_var_single($val, (isset($row['name'])) ? $row['name'] : '', $tagdata);
                }

                /** ----------------------------------------
                /**  {url_or_email} - Uses Raw Email Address, Like Weblog Module
                /** ----------------------------------------*/
                
                if ($key == "url_or_email" AND isset($row['url']))
                {
                    $tagdata = $TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $row['email'], $tagdata);
                }


                /** ----------------------------------------
                /**  {url_as_author}
                /** ----------------------------------------*/

                if ($key == "url_as_author" AND isset($row['url']))
                {                    
                    if ($row['url'] != '')
                    {
                        $tagdata = $TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
                    }
                    else
                    {
                        $tagdata = $TMPL->swap_var_single($val, $row['name'], $tagdata);
                    }
                }

                /** ----------------------------------------
                /**  {url_or_email_as_author}
                /** ----------------------------------------*/
                
                if ($key == "url_or_email_as_author" AND isset($row['url']))
                {                    
                    if ($row['url'] != '')
                    {
                        $tagdata = $TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
                    }
                    else
                    {
                    	if ($row['email'] != '')
                    	{
                        	$tagdata = $TMPL->swap_var_single($val, $TYPE->encode_email($row['email'], $row['name']), $tagdata);
                        }
                        else
                        {
                        	$tagdata = $TMPL->swap_var_single($val, $row['name'], $tagdata);
                        }
                    }
                }
                
                /** ----------------------------------------
                /**  {url_or_email_as_link}
                /** ----------------------------------------*/
                
                if ($key == "url_or_email_as_link" AND isset($row['url']))
                {                    
                    if ($row['url'] != '')
                    {
                        $tagdata = $TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
                    }
                    else
                    {  
                    	if ($row['email'] != '')
                    	{                    
                        	$tagdata = $TMPL->swap_var_single($val, $TYPE->encode_email($row['email']), $tagdata);
                        }
                        else
                        {
                        	$tagdata = $TMPL->swap_var_single($val, $row['name'], $tagdata);
                        }
                    }
                }
               
               if (substr($id, 0, 1) == 'c')
               {
					/** ----------------------------------------
					/**  {comment_auto_path}
					/** ----------------------------------------*/
					
					if ($key == "comment_auto_path")
					{           
						$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
						
						$tagdata = $TMPL->swap_var_single($key, $path, $tagdata);
					}
				   
					/** ----------------------------------------
					/**  {comment_url_title_auto_path}
					/** ----------------------------------------*/
					
					if ($key == "comment_url_title_auto_path" AND $comments_exist == TRUE)
					{ 
						$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
						
						$tagdata = $TMPL->swap_var_single(
															$key, 
															$path.$row['url_title'].'/', 
															$tagdata
														 );
					}
		  
					/** ----------------------------------------
					/**  {comment_entry_id_auto_path}
					/** ----------------------------------------*/
					
					if ($key == "comment_entry_id_auto_path" AND $comments_exist == TRUE)
					{           
						$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
						
						$tagdata = $TMPL->swap_var_single(
															$key, 
															$path.$row['entry_id'].'/', 
															$tagdata
														 );
					}
				   
				   
					/** ----------------------------------------
					/**  parse comment field
					/** ----------------------------------------*/
					
					if ($key == 'comment' AND isset($row['comment']))
					{
						// -------------------------------------------
						// 'comment_entries_comment_format' hook.
						//  - Play with the tagdata contents of the comment entries
						//
							if ($EXT->active_hook('comment_entries_comment_format') === TRUE)
							{
								$comment = $EXT->call_extension('comment_entries_comment_format', $row);
								if ($EXT->end_script === TRUE) return;
							}
							else
							{
								$comment = $TYPE->parse_type( $row['comment'], 
															   array(
																		'text_format'   => $row['comment_text_formatting'],
																		'html_format'   => $row['comment_html_formatting'],
																		'auto_links'    => $row['comment_auto_link_urls'],
																		'allow_img_url' => $row['comment_allow_img_urls']
																	)
															);
							}
						//
						// -------------------------------------------
						
						$tagdata = $TMPL->swap_var_single($key, $comment, $tagdata);                
					}
				}
                
                
                /** ----------------------------------------
                /**  {location}
                /** ----------------------------------------*/
				
				if ($key == 'location' AND (isset($row['location']) || isset($row['c_location'])))
				{					
					$tagdata = $TMPL->swap_var_single($key, (empty($row['location'])) ? $row['c_location'] : $row['location'], $tagdata);
				}
               
               
                /** ----------------------------------------
                /**  {signature}
                /** ----------------------------------------*/
                
                if ($key == "signature")
                {                
					if ($SESS->userdata('display_signatures') == 'n' OR  ! isset($row['signature']) OR $row['signature'] == '' OR $SESS->userdata('display_signatures') == 'n')
					{			
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key,
														$TYPE->parse_type($row['signature'], array(
																					'text_format'   => 'xhtml',
																					'html_format'   => 'safe',
																					'auto_links'    => 'y',
																					'allow_img_url' => $PREFS->ini('sig_allow_img_hotlink')
																				)
																			), $tagdata);
					}
                }
                
                
                if ($key == "signature_image_url")
                {                  
					if ($SESS->userdata('display_signatures') == 'n' OR $row['sig_img_filename'] == ''  OR $SESS->userdata('display_signatures') == 'n')
					{			
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $TMPL->swap_var_single('signature_image_width', '', $tagdata);
						$tagdata = $TMPL->swap_var_single('signature_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, $PREFS->ini('sig_img_url', TRUE).$row['sig_img_filename'], $tagdata);
						$tagdata = $TMPL->swap_var_single('signature_image_width', $row['sig_img_width'], $tagdata);
						$tagdata = $TMPL->swap_var_single('signature_image_height', $row['sig_img_height'], $tagdata);						
					}
                }

                if ($key == "avatar_url")
                {        
					if ( ! isset($row['avatar_filename']))
						$row['avatar_filename'] = '';
                
					if ($SESS->userdata('display_avatars') == 'n' OR $row['avatar_filename'] == ''  OR $SESS->userdata('display_avatars') == 'n')
					{			
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $TMPL->swap_var_single('avatar_image_width', '', $tagdata);
						$tagdata = $TMPL->swap_var_single('avatar_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, $PREFS->ini('avatar_url', 1).$row['avatar_filename'], $tagdata);
						$tagdata = $TMPL->swap_var_single('avatar_image_width', $row['avatar_width'], $tagdata);
						$tagdata = $TMPL->swap_var_single('avatar_image_height', $row['avatar_height'], $tagdata);						
					}
                }
                
                if ($key == "photo_url")
                {        
					if ( ! isset($row['photo_filename']))
						$row['photo_filename'] = '';
                
					if ($SESS->userdata('display_photos') == 'n' OR $row['photo_filename'] == ''  OR $SESS->userdata('display_photos') == 'n')
					{			
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $TMPL->swap_var_single('photo_image_width', '', $tagdata);
						$tagdata = $TMPL->swap_var_single('photo_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, $PREFS->ini('photo_url', 1).$row['photo_filename'], $tagdata);
						$tagdata = $TMPL->swap_var_single('photo_image_width', $row['photo_width'], $tagdata);
						$tagdata = $TMPL->swap_var_single('photo_image_height', $row['photo_height'], $tagdata);						
					}
                }
               
               
                /** ----------------------------------------
                /**  parse basic fields
                /** ----------------------------------------*/
                 
                if (isset($row[$val]) && $val != 'member_id')
                {                    
                    $tagdata = $TMPL->swap_var_single($val, $row[$val], $tagdata);
                }
                
                /** ----------------------------------------
                /**  parse custom member fields
                /** ----------------------------------------*/
                                
                if ( isset($mfields[$val]))
                {
                	// Since comments do not necessarily require registration, and since
					// you are allowed to put custom member variables in comments, 
					// we delete them if no such row exists
					
                	$return_val = (isset($row['m_field_id_'.$mfields[$val]])) ? $row['m_field_id_'.$mfields[$val]] : '';
                
                	$tagdata = $TMPL->swap_var_single(
                                                        $val, 
                                                        $return_val, 
                                                        $tagdata
                                                      );
                }
                
				/** ----------------------------------------
				/**  Clean up left over member variables
				/** ----------------------------------------*/
				
				if (in_array($val, $member_vars))
				{
					$tagdata = str_replace(LD.$val.RD, '', $tagdata);
				}
			}
            
            if ($this->show_anchor == TRUE)
            {
				$return .= "<a name=\"".$item_count."\"></a>\n";
            }
            
            $return .= $tagdata;
			
			$item_count++;                        
        }
     
		/** ----------------------------------------
		/**  Parse path variable
		/** ----------------------------------------*/
        
        $return = preg_replace_callback("/".LD."\s*path=(.+?)".RD."/", array(&$FNS, 'create_url'), $return);

		/** ----------------------------------------
		/**  Add pagination to result
		/** ----------------------------------------*/

        if ($paginate == TRUE)
        {
        	$paginate_data = str_replace(LD.'current_page'.RD, 	$t_current_page, 	$paginate_data);
        	$paginate_data = str_replace(LD.'total_pages'.RD,		$total_pages,  		$paginate_data);
        	$paginate_data = str_replace(LD.'pagination_links'.RD,	$pagination_links,	$paginate_data);
        	
        	if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.SLASH."if".RD."/s", $paginate_data, $match))
        	{
        		if ($page_previous == '')
        		{
        			 $paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.SLASH."if".RD."/s", '', $paginate_data);
        		}
        		else
        		{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_previous, $match['1']);
				
					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
        	}
        	
        	if (preg_match("/".LD."if next_page".RD."(.+?)".LD.SLASH."if".RD."/s", $paginate_data, $match))
        	{
        		if ($page_next == '')
        		{
        			 $paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.SLASH."if".RD."/s", '', $paginate_data);
        		}
        		else
        		{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_next, $match['1']);
				
					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
        	}
        
			$position = ( ! $TMPL->fetch_param('paginate')) ? '' : $TMPL->fetch_param('paginate');
			
			switch ($position)
			{
				case "top"	: $return  = $paginate_data.$return;
					break;
				case "both"	: $return  = $paginate_data.$return.$paginate_data;
					break;
				default		: $return .= $paginate_data;
					break;
			}
        }
        
        return $return;
    }
    /* END */



    /** ----------------------------------------
    /**  Comment Submission Form
    /** ----------------------------------------*/

    function form($return_form = FALSE, $captcha = '')
    {
        global $IN, $FNS, $PREFS, $SESS, $TMPL, $LOC, $DB, $REGX, $LANG, $EXT;
        
        $qstring = $IN->QSTR;
                
		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/
		
		if (preg_match("#/P\d+#", $qstring, $match))
		{			
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		// Figure out the right entry ID
		// Order of precedence: POST, entry_id=, url_title=, $qstring
		if (isset($_POST['entry_id']))
		{
			$entry_sql = " entry_id = '".$DB->escape_str($_POST['entry_id'])."' ";
		}
		elseif ($entry_id = $TMPL->fetch_param('entry_id'))
		{	
			$entry_sql = " entry_id = '".$DB->escape_str($entry_id)."' ";
		}
		elseif ($url_title = $TMPL->fetch_param('url_title'))
		{
			$entry_sql = " url_title = '".$DB->escape_str($url_title)."' ";
		}
		else
		{
			// If there is a slash in the entry ID we'll kill everything after it.
			$entry_id = trim($qstring); 		
			$entry_id = preg_replace("#/.+#", "", $entry_id);
			$entry_sql = ( ! is_numeric($entry_id)) ? " url_title = '".$DB->escape_str($entry_id)."' " : " entry_id = '".$DB->escape_str($entry_id)."' ";
		}
				
        /** ----------------------------------------
        /**  Are comments allowed?
        /** ----------------------------------------*/
        
        $sql = "SELECT exp_weblog_titles.entry_id, exp_weblog_titles.entry_date, exp_weblog_titles.comment_expiration_date, exp_weblog_titles.allow_comments, exp_weblogs.comment_system_enabled, exp_weblogs.comment_use_captcha, exp_weblogs.comment_expiration FROM exp_weblog_titles, exp_weblogs ";
                
		$sql .= " WHERE {$entry_sql}";
		
		$sql .= " AND exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
				  AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."') 
				  AND status != 'closed' ";
		
		if ($weblog = $TMPL->fetch_param('weblog'))
		{
			$xql = "SELECT weblog_id FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') ";
				
			$xql .= $FNS->sql_andor_string($weblog, 'blog_name');
						
			$query = $DB->query($xql);
			
			if ($query->num_rows == 0)
			{
				return false;
			}	
			elseif ($query->num_rows == 1)
			{
				$sql .= "AND exp_weblog_titles.weblog_id = '".$query->row['weblog_id']."' ";
			}
			else
			{
				$sql .= "AND (";
						
				foreach ($query->result as $row)
				{
					$sql .= "exp_weblog_titles.weblog_id = '".$row['weblog_id']."' OR ";
				}
						
				$sql = substr($sql, 0, - 3);
						
				$sql .= ") ";
			}
		}
        
        $query = $DB->query($sql);

        if ($query->num_rows == 0)
        {
            return false;
        }
        
        if ($query->row['allow_comments'] == 'n' || $query->row['comment_system_enabled'] == 'n')
        {
			$LANG->fetch_language_file('comment');
			return $LANG->line('cmt_commenting_has_expired');
        }
        
        /** ----------------------------------------
        /**  Return the "no cache" version of the form
        /** ----------------------------------------*/
        
        if ($return_form == FALSE)
        {
			if ($query->row['comment_use_captcha'] == 'n')
			{
				$TMPL->tagdata = str_replace(LD.'captcha'.RD, '', $TMPL->tagdata);             
			}
			
			$nc = '';
			
			if (is_array($TMPL->tagparams) AND count($TMPL->tagparams) > 0)
    		{
    			foreach ($TMPL->tagparams as $key => $val)
    			{
    				$nc .= ' '.$key.'="'.$val.'" ';
    			}
    		}

    		return '{NOCACHE_COMMENT_FORM="'.$nc.'"}'.$TMPL->tagdata.'{/NOCACHE_FORM}';
        }
                

        /** ----------------------------------------
        /**  Has commenting expired?
        /** ----------------------------------------*/
        
        $mode = ( ! isset($this->comment_expiration_mode)) ? 0 : $this->comment_expiration_mode;
                
        if ($mode == 0)
        {
			if ($query->row['comment_expiration_date'] > 0)
			{	
				if ($LOC->now > $query->row['comment_expiration_date'])
				{
					$LANG->fetch_language_file('comment');
				
					return $LANG->line('cmt_commenting_has_expired');
				}
			}        
        }
        else
        {
			if ($query->row['comment_expiration'] > 0)
			{
				 $days = $query->row['entry_date'] + ($query->row['comment_expiration'] * 86400);
	
				if ($LOC->now > $days)
				{
					$LANG->fetch_language_file('comment');
									
					return $LANG->line('cmt_commenting_has_expired');
				}
			}        
        }        
		
        $tagdata = $TMPL->tagdata; 
        
        // -------------------------------------------
		// 'comment_form_tagdata' hook.
		//  - Modify, add, etc. something to the comment form
		//
			if ($EXT->active_hook('comment_form_tagdata') === TRUE)
			{
				$tagdata = $EXT->call_extension('comment_form_tagdata', $tagdata);
				if ($EXT->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
        
        /** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/
		
		$cond = array();
		$cond['logged_in']			= ($SESS->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= ($SESS->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		
		if ($query->row['comment_use_captcha'] == 'n')
		{
			$cond['captcha'] = 'FALSE';            
		}
		elseif ($query->row['comment_use_captcha'] == 'y') 
		{            	
			$cond['captcha'] =  ($PREFS->ini('captcha_require_members') == 'y'  || 
								($PREFS->ini('captcha_require_members') == 'n' AND $SESS->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';          
		}
		
		$tagdata = $FNS->prep_conditionals($tagdata, $cond);
       	
       	/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/
                
        foreach ($TMPL->var_single as $key => $val)
        {              
            /** ----------------------------------------
            /**  parse {name}
            /** ----------------------------------------*/
            
            if ($key == 'name')
            {
                $name = ($SESS->userdata['screen_name'] != '') ? $SESS->userdata['screen_name'] : $SESS->userdata['username'];
            
                $name = ( ! isset($_POST['name'])) ? $name : $_POST['name'];
            
                $tagdata = $TMPL->swap_var_single($key, $REGX->form_prep($name), $tagdata);
            }
                    
            /** ----------------------------------------
            /**  parse {email}
            /** ----------------------------------------*/
            
            if ($key == 'email')
            {
                $email = ( ! isset($_POST['email'])) ? $SESS->userdata['email'] : $_POST['email'];
              
                $tagdata = $TMPL->swap_var_single($key, $REGX->form_prep($email), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {url}
            /** ----------------------------------------*/
            
            if ($key == 'url')
            {
                $url = ( ! isset($_POST['url'])) ? $SESS->userdata['url'] : $_POST['url'];
                
                if ($url == '')
                    $url = 'http://';

                $tagdata = $TMPL->swap_var_single($key, $REGX->form_prep($url), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {location}
            /** ----------------------------------------*/
            
            if ($key == 'location')
            { 
                $location = ( ! isset($_POST['location'])) ? $SESS->userdata['location'] : $_POST['location'];

                $tagdata = $TMPL->swap_var_single($key, $REGX->form_prep($location), $tagdata);
            }
          
            /** ----------------------------------------
            /**  parse {comment}
            /** ----------------------------------------*/
            
            if ($key == 'comment')
            {
                $comment = ( ! isset($_POST['comment'])) ? '' : $_POST['comment'];
            
                $tagdata = $TMPL->swap_var_single($key, $comment, $tagdata);
            }
            
            /** ----------------------------------------
            /**  parse {captcha_word}
            /** ----------------------------------------*/
            
			if ($key == 'captcha_word')
			{
				$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
			}
			
            /** ----------------------------------------
            /**  parse {save_info}
            /** ----------------------------------------*/
            
            if ($key == 'save_info')
            {
                $save_info = ( ! isset($_POST['save_info'])) ? '' : $_POST['save_info'];
                       
                $notify = ( ! isset($SESS->userdata['notify_by_default'])) ? $IN->GBL('save_info', 'COOKIE') : $SESS->userdata['notify_by_default'];
                        
                $checked   = ( ! isset($_POST['PRV'])) ? $notify : $save_info;
            
                $tagdata = $TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
            }
            
            /** ----------------------------------------
            /**  parse {notify_me}
            /** ----------------------------------------*/
            
            if ($key == 'notify_me')
            {
            	$checked = '';
            	
            	if ( ! isset($_POST['PRV']))
            	{
					if ($IN->GBL('notify_me', 'COOKIE'))
					{
						$checked = $IN->GBL('notify_me', 'COOKIE');
					}
					
					if (isset($SESS->userdata['notify_by_default']))
					{
						$checked = ($SESS->userdata['notify_by_default'] == 'y') ? 'yes' : '';
					}					
				}
				
				if (isset($_POST['notify_me']))
				{
					$checked = $_POST['notify_me'];
				}
                            
                $tagdata = $TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
            }
        }

        /** ----------------------------------------
        /**  Create form
        /** ----------------------------------------*/

        $RET = (isset($_POST['RET'])) ? $_POST['RET'] : $FNS->fetch_current_uri();
        $PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : $TMPL->fetch_param('preview');
        $XID = (isset($_POST['XID'])) ? $_POST['XID'] : '';
                
        $hidden_fields = array(
                                'ACT'      	=> $FNS->fetch_action_id('Comment', 'insert_new_comment'),
                                'RET'      	=> $RET,
                                'URI'      	=> ($IN->URI == '') ? 'index' : $IN->URI,
                                'PRV'      	=> $PRV,
                                'XID'      	=> $XID,
                                'entry_id' 	=> $query->row['entry_id']
                              );
                              
		if ($query->row['comment_use_captcha'] == 'y')
		{	
			if (preg_match("/({captcha})/", $tagdata))
			{							
				$tagdata = preg_replace("/{captcha}/", $FNS->create_captcha(), $tagdata);
			}        
		} 
		
		// -------------------------------------------
		// 'comment_form_hidden_fields' hook.
		//  - Add/Remove Hidden Fields for Comment Form
		//
			if ($EXT->active_hook('comment_form_hidden_fields') === TRUE)
			{
				$hidden_fields = $EXT->call_extension('comment_form_hidden_fields', $hidden_fields);
				if ($EXT->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		
		// -------------------------------------------
		// 'comment_form_action' hook.
		//  - Modify action="" attribute for comment form
		//  - Added 1.4.2
		//
			if ($EXT->active_hook('comment_form_action') === TRUE)
			{
				$RET = $EXT->call_extension('comment_form_action', $RET);
				if ($EXT->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		
		$data = array(
						'hidden_fields'	=> $hidden_fields,
						'action'		=> $RET,
						'id'			=> 'comment_form'
					);
					
		if ($TMPL->fetch_param('name') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $TMPL->fetch_param('name'), $match))
		{
			$data['name'] = $TMPL->fetch_param('name');
		}
				
        $res  = $FNS->form_declaration($data);  
        
        $res .= stripslashes($tagdata);
        $res .= "</form>";
        
		// -------------------------------------------
		// 'comment_form_end' hook.
		//  - Modify, add, etc. something to the comment form at end of processing
		//
			if ($EXT->active_hook('comment_form_end') === TRUE)
			{
				$res = $EXT->call_extension('comment_form_end', $res);
				if ($EXT->end_script === TRUE) return $res;
			}
		//
		// -------------------------------------------
        
        
		return str_replace('&#47;', '/', $res);
    }
    /* END */




    /** ----------------------------------------
    /**  Preview
    /** ----------------------------------------*/

    function preview()
    {
        global $IN, $TMPL, $FNS, $DB, $SESS, $LOC, $REGX, $EXT, $LANG, $OUT;
        
        $entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : $IN->QSTR;
     
        if ( ! is_numeric($entry_id) OR empty($_POST['comment']))
        {
            return FALSE;
        }

        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $TYPE = new Typography(FALSE, FALSE);
        $TYPE->encode_email = FALSE;               
        
        $sql = "SELECT exp_weblogs.comment_text_formatting, exp_weblogs.comment_html_formatting, exp_weblogs.comment_allow_img_urls, exp_weblogs.comment_auto_link_urls, exp_weblogs.comment_max_chars
                FROM   exp_weblogs, exp_weblog_titles
                WHERE  exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
                AND    exp_weblog_titles.entry_id = '".$DB->escape_str($entry_id)."'";        
                        
        $query = $DB->query($sql);
        
		if ($query->num_rows == 0)
		{ 
			return '';
		}
        
		/** -------------------------------------
		/**  Check size of comment
		/** -------------------------------------*/
       
        if ($query->row['comment_max_chars'] != '' AND $query->row['comment_max_chars'] != 0)
        {        
            if (strlen($_POST['comment']) > $query->row['comment_max_chars'])
            {
                $str = str_replace("%n", strlen($_POST['comment']), $LANG->line('cmt_too_large'));
                
                $str = str_replace("%x", $query->row['comment_max_chars'], $str);
            			
				return $OUT->show_user_error('submission', $str);
            }
        }

        if ($query->num_rows == '')
        {
            $formatting = 'none';
        }
        else
        {
            $formatting = $query->row['comment_text_formatting'];
        }
        
        $tagdata = $TMPL->tagdata; 
        
		// -------------------------------------------
		// 'comment_preview_tagdata' hook.
		//  - Play with the tagdata contents of the comment preview
		//
			if ($EXT->active_hook('comment_preview_tagdata') === TRUE)
			{
				$tagdata = $EXT->call_extension('comment_preview_tagdata', $tagdata);
				if ($EXT->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
                
        /** ----------------------------------------
        /**  Fetch all the date-related variables
        /** ----------------------------------------*/
        
        $comment_date = array();
        
		if (preg_match_all("/".LD."comment_date\s+format=[\"'](.*?)[\"']".RD."/s", $tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
				
				$comment_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
			}
		}
		
        /** ----------------------------------------
        /**  Set defaults based on member data as needed
        /** ----------------------------------------*/		
		
		if (isset($_POST['name']) AND $_POST['name'] != '')
		{
			$name = stripslashes($IN->GBL('name', 'POST'));
		}
		elseif ($SESS->userdata['screen_name'] != '')
		{
			$name = $SESS->userdata['screen_name'];
		}
		else
		{
			$name = '';
		}

		foreach (array('email', 'url', 'location') as $v)
		{
			if (isset($_POST[$v]) AND $_POST[$v] != '')
			{
				${$v} = stripslashes($IN->GBL($v, 'POST'));
			}
			elseif ($SESS->userdata[$v] != '')
			{
				${$v} = $SESS->userdata[$v];
			}
			else
			{
				${$v} = '';
			}		
		}
				
		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/
		
		$cond = $_POST; // Sanitized on input and also in prep_conditionals, so no real worries here
		$cond['logged_in']			= ($SESS->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= ($SESS->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		$cond['name']				= $name;	
		$cond['email']				= $email;		
		$cond['url']				= ($url == 'http://') ? '' : $url;
		$cond['location']			= $location;
		
		$tagdata = $FNS->prep_conditionals($tagdata, $cond);
		
        
        /** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/
        
        foreach ($TMPL->var_single as $key => $val)
        {   
			/** ----------------------------------------
			/**  {name}
			/** ----------------------------------------*/

			if ($key == 'name')
			{
                $tagdata = $TMPL->swap_var_single($key, $name, $tagdata);                
			}
        
			/** ----------------------------------------
			/**  {email}
			/** ----------------------------------------*/
						
			if ($key == 'email')
			{
                $tagdata = $TMPL->swap_var_single($key, $email, $tagdata);                
			}
        
			/** ----------------------------------------
			/**  {url}
			/** ----------------------------------------*/
			
			if ($key == 'url')
			{
                $tagdata = $TMPL->swap_var_single($key, $url, $tagdata);                
			}
        
			/** ----------------------------------------
			/**  {location}
			/** ----------------------------------------*/
			
			if ($key == 'location')
			{
                $tagdata = $TMPL->swap_var_single($key, $location, $tagdata);                
			}
                        
			// Prep the URL
			
			if ($url != '')
			{
				$url = $REGX->prep_url($url);
			}

			/** ----------------------------------------
			/**  {url_or_email}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email")
			{
				$temp = $url;
				
				if ($temp == '' AND $email != '')
				{
					$temp = $TYPE->encode_email($email, '', 0);
				}
			
				$tagdata = $TMPL->swap_var_single($val, $temp, $tagdata);
			}

			/** ----------------------------------------
			/**  {url_or_email_as_author}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email_as_author")
			{                    
				if ($url != '')
				{
					$tagdata = $TMPL->swap_var_single($val, "<a href=\"".$url."\">".$name."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = $TMPL->swap_var_single($val, $TYPE->encode_email($email, $name), $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}
			
			/** ----------------------------------------
			/**  {url_or_email_as_link}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email_as_link")
			{                    
				if ($url != '')
				{
					$tagdata = $TMPL->swap_var_single($val, "<a href=\"".$url."\">".$url."</a>", $tagdata);
				}
				else
				{  
					if ($email != '')
					{                    
						$tagdata = $TMPL->swap_var_single($val, $TYPE->encode_email($email), $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

            /** ----------------------------------------
            /**  parse comment field
            /** ----------------------------------------*/
            
            if ($key == 'comment')
            {                              
				// -------------------------------------------
				// 'comment_preview_comment_format' hook.
				//  - Play with the tagdata contents of the comment preview
				//
					if ($EXT->active_hook('comment_preview_comment_format') === TRUE)
					{
						$data = $EXT->call_extension('comment_preview_comment_format', $query->row);
						if ($EXT->end_script === TRUE) return;
					}
					else
					{
						$data = $TYPE->parse_type( stripslashes($IN->GBL('comment', 'POST')), 
												 array(
														'text_format'   => $query->row['comment_text_formatting'],
														'html_format'   => $query->row['comment_html_formatting'],
														'auto_links'    => $query->row['comment_auto_link_urls'],
														'allow_img_url' => $query->row['comment_allow_img_urls']
													   )
												);
					}
				//
				// -------------------------------------------

                $tagdata = $TMPL->swap_var_single($key, $data, $tagdata);                
            }
            		
			/** ----------------------------------------
			/**  parse comment date
			/** ----------------------------------------*/
			
			if (isset($comment_date[$key]))
			{                
				foreach ($comment_date[$key] as $dvar)
				{
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $LOC->now, TRUE), $val);		
				}

				$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
			}
			
		}
        
        return $tagdata;
    }
    /* END */



    /** ----------------------------------------
    /**  Preview handler
    /** ----------------------------------------*/

    function preview_handler()
    {
        global $IN, $OUT, $LANG, $FNS, $REGX;
                
        if ($IN->GBL('PRV', 'POST') == '')
        {
            $error[] = $LANG->line('cmt_no_preview_template_specified');
            
            return $OUT->show_user_error('general', $error);        
        }
        
        if ( ! isset($_POST['PRV']) or $_POST['PRV'] == '')
        {
        	exit('Preview template not specified in your comment form tag');
        }
        
        $_POST['PRV'] = $REGX->trim_slashes($REGX->xss_clean($_POST['PRV']));
      
		$FNS->clear_caching('all', $_POST['PRV']);
        $FNS->clear_caching('all', $_POST['RET']);

        require PATH_CORE.'core.template'.EXT;
     
        global $TMPL;

        $TMPL = new Template();
        
		$preview = ( ! $IN->GBL('PRV', 'POST')) ? '' : $IN->GBL('PRV');

		if (strpos($preview, '/') === FALSE)
        {
			$preview = '';
        }
        else
        {
			$ex = explode("/", $preview);

			if (count($ex) != 2)
			{
				$preview = '';
			}
        }
        	        	
        if ($preview == '')
        {
			$group = 'weblog';
			$templ = 'preview';
        }
		else
		{
			$group = $ex['0'];
			$templ = $ex['1'];
		}        
                        
        $TMPL->run_template_engine($group, $templ);
    }
    /* END */




    /** ----------------------------------------
    /**  Insert new comment
    /** ----------------------------------------*/

    function insert_new_comment()
    {
        global $IN, $SESS, $PREFS, $DB, $FNS, $OUT, $LANG, $REGX, $LOC, $STAT, $EXT;
    
        $default = array('name', 'email', 'url', 'comment', 'location', 'entry_id');
        
        foreach ($default as $val)
        {
			if ( ! isset($_POST[$val]))
			{
				$_POST[$val] = '';
			}
        }           

		// No entry ID?  What the heck are they doing?
        if ( ! is_numeric($_POST['entry_id']))
        {
        	return false;
        }
                
        // If the comment is empty, bounce them back
        
        if ($_POST['comment'] == '')
        {
        	if ( ! isset($_POST['RET']) OR $_POST['RET'] == '')
        	{
        		return false;
        	}
        	
            $FNS->redirect($_POST['RET']);
        }
               
        /** ----------------------------------------
        /**  Fetch the comment language pack
        /** ----------------------------------------*/
        
        $LANG->fetch_language_file('comment');
        
        /** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        
        if ($SESS->userdata['is_banned'] == TRUE)
        {            
            return $OUT->show_user_error('general', array($LANG->line('not_authorized')));
        }
                
        /** ----------------------------------------
        /**  Is the IP address and User Agent required?
        /** ----------------------------------------*/
                
        if ($PREFS->ini('require_ip_for_posting') == 'y')
        {
        	if ($IN->IP == '0.0.0.0' || $SESS->userdata['user_agent'] == "")
        	{            
            	return $OUT->show_user_error('general', array($LANG->line('not_authorized')));
        	}        	
        } 
        
        /** ----------------------------------------
		/**  Is the nation of the user banend?
		/** ----------------------------------------*/
		$SESS->nation_ban_check();			
                
        /** ----------------------------------------
        /**  Can the user post comments?
        /** ----------------------------------------*/
        
        if ($SESS->userdata['can_post_comments'] == 'n')
        {
            $error[] = $LANG->line('cmt_no_authorized_for_comments');
            
            return $OUT->show_user_error('general', $error);
        }
        
        /** ----------------------------------------
        /**  Blacklist/Whitelist Check
        /** ----------------------------------------*/
        
        if ($IN->blacklisted == 'y' && $IN->whitelisted == 'n')
        {
        	return $OUT->show_user_error('general', array($LANG->line('not_authorized')));
        }  
                         
        /** ----------------------------------------
        /**  Is this a preview request?
        /** ----------------------------------------*/
        
        if (isset($_POST['preview']))
        {            
            return $this->preview_handler();
        }
        
        // -------------------------------------------
        // 'insert_comment_start' hook.
        //  - Allows complete rewrite of comment submission routine.
        //  - Or could be used to modify the POST data before processing
        //
        	$edata = $EXT->call_extension('insert_comment_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        /** ----------------------------------------
        /**  Fetch weblog preferences
        /** ----------------------------------------*/
        
        $sql = "SELECT exp_weblog_titles.title, 
                       exp_weblog_titles.url_title,
                       exp_weblog_titles.weblog_id,
                       exp_weblog_titles.author_id,
                       exp_weblog_titles.comment_total,
                       exp_weblog_titles.allow_comments,
                       exp_weblog_titles.entry_date,
                       exp_weblog_titles.comment_expiration_date,
                       exp_weblogs.blog_title,
                       exp_weblogs.comment_system_enabled,
                       exp_weblogs.comment_max_chars,
                       exp_weblogs.comment_use_captcha,
                       exp_weblogs.comment_timelock,
                       exp_weblogs.comment_require_membership,
                       exp_weblogs.comment_moderate,
                       exp_weblogs.comment_require_email,
                       exp_weblogs.comment_notify,
                       exp_weblogs.comment_notify_authors,
                       exp_weblogs.comment_notify_emails,
                       exp_weblogs.comment_expiration
                FROM   exp_weblog_titles, exp_weblogs
                WHERE  exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
                AND    exp_weblog_titles.entry_id = '".$DB->escape_str($_POST['entry_id'])."'
				AND    exp_weblog_titles.status != 'closed' ";
                
        // -------------------------------------------
		// 'insert_comment_preferences_sql' hook.
		//  - Rewrite or add to the comment preference sql query
		//  - Could be handy for comment/weblog restrictions
		//
			if ($EXT->active_hook('insert_comment_preferences_sql') === TRUE)
			{
				$sql = $EXT->call_extension('insert_comment_preferences_sql', $sql);
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------
                
        $query = $DB->query($sql);        
        
        unset($sql);
                
        if ($query->num_rows == 0)
        {
            return false;
        }

        /** ----------------------------------------
        /**  Are comments allowed?
        /** ----------------------------------------*/

        if ($query->row['allow_comments'] == 'n' || $query->row['comment_system_enabled'] == 'n')
        {            
            return $OUT->show_user_error('submission', $LANG->line('cmt_comments_not_allowed'));
        }
        
        /** ----------------------------------------
        /**  Has commenting expired?
        /** ----------------------------------------*/
        
        if ($this->comment_expiration_mode == 0)
        {
			if ($query->row['comment_expiration_date'] > 0)
			{	
				if ($LOC->now > $query->row['comment_expiration_date'])
				{					
					return $OUT->show_user_error('submission', $LANG->line('cmt_commenting_has_expired'));
				}
			}        
        }
        else
        {
			if ($query->row['comment_expiration'] > 0)
			{
				 $days = $query->row['entry_date'] + ($query->row['comment_expiration'] * 86400);
	
				if ($LOC->now > $days)
				{					
					return $OUT->show_user_error('submission', $LANG->line('cmt_commenting_has_expired'));
				}
			}        
        }        
                
        /** ----------------------------------------
        /**  Is there a comment timelock?
        /** ----------------------------------------*/

        if ($query->row['comment_timelock'] != '' AND $query->row['comment_timelock'] > 0)
        {
			if ($SESS->userdata['group_id'] != 1)        
			{
				$time = $LOC->now - $query->row['comment_timelock'];
			
				$result = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE comment_date > '$time' AND ip_address = '$IN->IP' ");
			
				if ($result->row['count'] > 0)
				{
					return $OUT->show_user_error('submission', str_replace("%s", $query->row['comment_timelock'], $LANG->line('cmt_comments_timelock')));
				}
			}
        }
        
        /** ----------------------------------------
        /**  Do we allow duplicate data?
        /** ----------------------------------------*/

        if ($PREFS->ini('deny_duplicate_data') == 'y')
        {
			if ($SESS->userdata['group_id'] != 1)        
			{			
				$result = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE comment = '".$DB->escape_str($_POST['comment'])."' ");
			
				if ($result->row['count'] > 0)
				{					
					return $OUT->show_user_error('submission', $LANG->line('cmt_duplicate_comment_warning'));
				}
			}
        }
        
				
        /** ----------------------------------------
        /**  Assign data
        /** ----------------------------------------*/

        $author_id				= $query->row['author_id'];
        $entry_title			= $query->row['title'];
        $url_title	        	= $query->row['url_title'];
        $blog_title         	= $query->row['blog_title'];
        $weblog_id          	= $query->row['weblog_id'];
        $comment_total      	= $query->row['comment_total'] + 1;
        $require_membership 	= $query->row['comment_require_membership'];
        $comment_moderate		= ($SESS->userdata['group_id'] == 1 OR $SESS->userdata['exclude_from_moderation'] == 'y') ? 'n' : $query->row['comment_moderate'];
        $author_notify			= $query->row['comment_notify_authors'];

		$notify_address = ($query->row['comment_notify'] == 'y' AND $query->row['comment_notify_emails'] != '') ? $query->row['comment_notify_emails'] : '';

        /** ----------------------------------------
        /**  Start error trapping
        /** ----------------------------------------*/
        
        $error = array();
        
        if ($SESS->userdata('member_id') != 0)        
        {
            // If the user is logged in we'll reassign the POST variables with the user data
            
             $_POST['name']     = ($SESS->userdata['screen_name'] != '') ? $SESS->userdata['screen_name'] : $SESS->userdata['username'];
             $_POST['email']    =  $SESS->userdata['email'];
             $_POST['url']      =  $SESS->userdata['url'];
             $_POST['location'] =  $SESS->userdata['location'];
        }
        
        
        /** ----------------------------------------
        /**  Is membership is required to post...
        /** ----------------------------------------*/
        
        if ($require_membership == 'y')
        {        
            // Not logged in
        
            if ($SESS->userdata('member_id') == 0)
            {                
                return $OUT->show_user_error('submission', $LANG->line('cmt_must_be_member'));
            }
            
            // Membership is pending
            
            if ($SESS->userdata['group_id'] == 4)
            {                
                return $OUT->show_user_error('general', $LANG->line('cmt_account_not_active'));
            }
                        
        }
        else
        {                              
            /** ----------------------------------------
            /**  Missing name?
            /** ----------------------------------------*/
            
            if ($_POST['name'] == '')
            {
                $error[] = $LANG->line('cmt_missing_name');
            }
            
			/** -------------------------------------
			/**  Is name banned?
			/** -------------------------------------*/
		
			if ($SESS->ban_check('screen_name', $_POST['name']))
			{
                $error[] = $LANG->line('cmt_name_not_allowed');
			}
            
            /** ----------------------------------------
            /**  Missing or invalid email address
            /** ----------------------------------------*/
    
            if ($query->row['comment_require_email'] == 'y')
            {
                if ($_POST['email'] == '')
                {
                    $error[] = $LANG->line('cmt_missing_email');
                }
                elseif ( ! $REGX->valid_email($_POST['email']))
                {
                    $error[] = $LANG->line('cmt_invalid_email');
                }
            }
        }
        
		/** -------------------------------------
		/**  Is email banned?
		/** -------------------------------------*/
		
		if ($_POST['email'] != '')
		{
			if ($SESS->ban_check('email', $_POST['email']))
			{
				$error[] = $LANG->line('cmt_banned_email');
			}
		}	
        
        /** ----------------------------------------
        /**  Is comment too big?
        /** ----------------------------------------*/
        
        if ($query->row['comment_max_chars'] != '' AND $query->row['comment_max_chars'] != 0)
        {        
            if (strlen($_POST['comment']) > $query->row['comment_max_chars'])
            {
                $str = str_replace("%n", strlen($_POST['comment']), $LANG->line('cmt_too_large'));
                
                $str = str_replace("%x", $query->row['comment_max_chars'], $str);
            
                $error[] = $str;
            }
        }
        
        /** ----------------------------------------
        /**  Do we have errors to display?
        /** ----------------------------------------*/
                
        if (count($error) > 0)
        {
           return $OUT->show_user_error('submission', $error);
        }
        
        /** ----------------------------------------
        /**  Do we require captcha?
        /** ----------------------------------------*/
		
		if ($query->row['comment_use_captcha'] == 'y')
		{	
			if ($PREFS->ini('captcha_require_members') == 'y'  ||  ($PREFS->ini('captcha_require_members') == 'n' AND $SESS->userdata('member_id') == 0))
			{
				if ( ! isset($_POST['captcha']) || $_POST['captcha'] == '')
				{
					return $OUT->show_user_error('submission', $LANG->line('captcha_required'));
				}
				else
				{
					$res = $DB->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".$DB->escape_str($_POST['captcha'])."' AND ip_address = '".$IN->IP."' AND date > UNIX_TIMESTAMP()-7200");
				
					if ($res->row['count'] == 0)
					{
						return $OUT->show_user_error('submission', $LANG->line('captcha_incorrect'));
					}
				
					$DB->query("DELETE FROM exp_captcha WHERE (word='".$DB->escape_str($_POST['captcha'])."' AND ip_address = '".$IN->IP."') OR date < UNIX_TIMESTAMP()-7200");
				}
			}
		}
                
        /** ----------------------------------------
        /**  Build the data array
        /** ----------------------------------------*/
        
        $notify = ($IN->GBL('notify_me', 'POST')) ? 'y' : 'n';
        
 		$cmtr_name	= $REGX->xss_clean($_POST['name']);
 		$cmtr_email	= $_POST['email'];
 		$cmtr_url	= $REGX->xss_clean($REGX->prep_url($_POST['url']));
 		$cmtr_loc	= $REGX->xss_clean($_POST['location']);
        
        $data = array(
                        'weblog_id'     => $weblog_id,
                        'entry_id'      => $_POST['entry_id'],
                        'author_id'     => $SESS->userdata('member_id'),
                        'name'          => $cmtr_name,
                        'email'         => $cmtr_email,
                        'url'           => $cmtr_url,
                        'location'      => $cmtr_loc,
                        'comment'       => $REGX->xss_clean($_POST['comment']),
                        'comment_date'  => $LOC->now,
                        'ip_address'    => $IN->IP,
                        'notify'        => $notify,
                        'status'		=> ($comment_moderate == 'y') ? 'c' : 'o',
                        'site_id'		=> $PREFS->ini('site_id')
                     );
                     
        // -------------------------------------------
		// 'insert_comment_insert_array' hook.
		//  - Modify any of the soon to be inserted values
		//
			if ($EXT->active_hook('insert_comment_insert_array') === TRUE)
			{
				$data = $EXT->call_extension('insert_comment_insert_array', $data);
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------

      
        /** ----------------------------------------
        /**  Insert data
        /** ----------------------------------------*/
      
        if ($PREFS->ini('secure_forms') == 'y')
        {
            $query = $DB->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$DB->escape_str($_POST['XID'])."' AND ip_address = '".$IN->IP."' AND date > UNIX_TIMESTAMP()-7200");
        
            if ($query->row['count'] > 0)
            {
                $sql = $DB->insert_string('exp_comments', $data);

                $DB->query($sql);
                
                $comment_id = $DB->insert_id;
                                
                $DB->query("DELETE FROM exp_security_hashes WHERE (hash='".$DB->escape_str($_POST['XID'])."' AND ip_address = '".$IN->IP."') OR date < UNIX_TIMESTAMP()-7200");
            }
            else
            {
                $FNS->redirect(stripslashes($_POST['RET']));
            }
        }
        else
        {
            $sql = $DB->insert_string('exp_comments', $data);
        
            $DB->query($sql);
            
            $comment_id = $DB->insert_id;
        }
        
        if ($comment_moderate == 'n')
        {       
			/** ------------------------------------------------
			/**  Update comment total and "recent comment" date
			/** ------------------------------------------------*/
			
			$DB->query("UPDATE exp_weblog_titles SET comment_total = '$comment_total', recent_comment_date = '".$LOC->now."' WHERE entry_id = '".$DB->escape_str($_POST['entry_id'])."'");
		 
			/** ----------------------------------------
			/**  Update member comment total and date
			/** ----------------------------------------*/
			
			if ($SESS->userdata('member_id') != 0)
			{
				$query = $DB->query("SELECT total_comments FROM exp_members WHERE member_id = '".$SESS->userdata('member_id')."'");
	
				$DB->query("UPDATE exp_members SET total_comments = '".($query->row['total_comments'] + 1)."', last_comment_date = '".$LOC->now."' WHERE member_id = '".$SESS->userdata('member_id')."'");                
			}
			
			/** ----------------------------------------
			/**  Update comment stats
			/** ----------------------------------------*/
			
			$STAT->update_comment_stats($weblog_id, $LOC->now);
			
			/** ----------------------------------------
			/**  Fetch email notification addresses
			/** ----------------------------------------*/
			
			$query = $DB->query("SELECT DISTINCT(email), name, comment_id, author_id FROM exp_comments WHERE status = 'o' AND entry_id = '".$DB->escape_str($_POST['entry_id'])."' AND notify = 'y'");
			
			$recipients = array();
					
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					if ($row['email'] == "" AND $row['author_id'] != 0)
					{
						$result = $DB->query("SELECT email, screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($row['author_id'])."'");
						
						if ($result->num_rows == 1)
						{
							$recipients[] = array($result->row['email'], $row['comment_id'], $result->row['screen_name']);
						}
					}
					elseif ($row['email'] != "")
					{
						$recipients[] = array($row['email'], $row['comment_id'], $row['name']);   
					}            
				}
			}
        }
                
        /** ----------------------------------------
        /**  Fetch Author Notification
        /** ----------------------------------------*/
                
		if ($author_notify == 'y')
		{
			$result = $DB->query("SELECT email FROM exp_members WHERE member_id = '".$DB->escape_str($author_id)."'");
			$notify_address	.= ','.$result->row['email'];
		}
        
        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $TYPE = new Typography(FALSE, FALSE); 
 		$TYPE->smileys = FALSE;
		$comment = $REGX->xss_clean($_POST['comment']);
		$comment = $TYPE->parse_type( $comment, 
									   array(
												'text_format'   => 'none',
												'html_format'   => 'none',
												'auto_links'    => 'n',
												'allow_img_url' => 'n'
											)
									);
        
        /** ----------------------------
        /**  Send admin notification
        /** ----------------------------*/
                
        if ($notify_address != '')
        {         
			$swap = array(
							'name'				=> $cmtr_name,
							'name_of_commenter'	=> $cmtr_name,
							'email'				=> $cmtr_email,
							'url'				=> $cmtr_url,
							'location'			=> $cmtr_loc,
							'weblog_name'		=> $blog_title,
							'entry_title'		=> $entry_title,
							'comment_id'		=> $comment_id,
							'comment'			=> $comment,
							'comment_url'		=> $FNS->remove_session_id($_POST['RET']),
							'delete_link'		=> $PREFS->ini('cp_url').'?S=0&C=edit'.'&M=del_comment_conf'.'&weblog_id='.$weblog_id.'&entry_id='.$_POST['entry_id'].'&comment_id='.$comment_id
						 );
			
			$template = $FNS->fetch_email_template('admin_notify_comment');

			$email_tit = $FNS->var_swap($template['title'], $swap);
			$email_msg = $FNS->var_swap($template['data'], $swap);
			                   
			// We don't want to send an admin notification if the person
			// leaving the comment is an admin in the notification list
			
			if ($_POST['email'] != '')
			{
				if (strpos($notify_address, $_POST['email']) !== FALSE)
				{
					$notify_address = str_replace($_POST['email'], "", $notify_address);				
				}
			}
			
			$notify_address = $REGX->remove_extra_commas($notify_address);
			
			if ($notify_address != '')
			{						
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/
				
				if ( ! class_exists('EEmail'))
				{
					require PATH_CORE.'core.email'.EXT;
				}
				
				$replyto = ($data['email'] == '') ? $PREFS->ini('webmaster_email') : $data['email'];
					 
				$email = new EEmail;
				
				$sent = array();
				
				foreach (explode(',', $notify_address) as $addy)
				{
					if (in_array($addy, $sent)) continue;
					
					$email->initialize();	
					$email->wordwrap = false;
					$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
					$email->to($addy); 
					$email->reply_to($replyto);
					$email->subject($email_tit);	
					$email->message($REGX->entities_to_ascii($email_msg));		
					$email->Send();
					
					$sent[] = $addy;
				}
			}
        }

        /** ----------------------------------------
        /**  Send user notifications
        /** ----------------------------------------*/
 
		if ($comment_moderate == 'n')
        {       
			$email_msg = '';
					
			if (count($recipients) > 0)
			{
				$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
	
				$action_id  = $FNS->fetch_action_id('Comment_CP', 'delete_comment_notification');
			
				$swap = array(
								'name_of_commenter'	=> $cmtr_name,
								'weblog_name'		=> $blog_title,
								'entry_title'		=> $entry_title,
								'site_name'			=> stripslashes($PREFS->ini('site_name')),
								'site_url'			=> $PREFS->ini('site_url'),
								'comment_url'		=> $FNS->remove_session_id($_POST['RET']),
								'comment_id'		=> $comment_id,
								'comment'			=> $comment
							 );
				
				$template = $FNS->fetch_email_template('comment_notification');
				$email_tit = $FNS->var_swap($template['title'], $swap);
				$email_msg = $FNS->var_swap($template['data'], $swap);
	
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/
				
				if ( ! class_exists('EEmail'))
				{
					require PATH_CORE.'core.email'.EXT;
				}
				
				$email = new EEmail;
				$email->wordwrap = true;
				
				$cur_email = ($_POST['email'] == '') ? FALSE : $_POST['email'];
				
				if ( ! isset($sent)) $sent = array();
				
				foreach ($recipients as $val)
				{
					// We don't notify the person currently commenting.  That would be silly.
					
					if ($val['0'] != $cur_email AND ! in_array($val['0'], $sent))
					{
						$title	 = $email_tit;
						$message = $email_msg;

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);
					
						$title	 = str_replace('{notification_removal_url}', $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $title);
						$message = str_replace('{notification_removal_url}', $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $message);
										
						$email->initialize();
						$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
						$email->to($val['0']); 
						$email->subject($title);	
						$email->message($REGX->entities_to_ascii($message));		
						$email->Send();
						
						$sent[] = $val['0'];
					}
				}            
			}
			
			/** ----------------------------------------
			/**  Clear cache files
			/** ----------------------------------------*/
			
			$FNS->clear_caching('all', $FNS->fetch_site_index().$_POST['URI']);
			
			// clear out the entry_id version if the url_title is in the URI, and vice versa
			if (preg_match("#\/".preg_quote($url_title)."\/#", $_POST['URI'], $matches))
			{
				$FNS->clear_caching('all', $FNS->fetch_site_index().preg_replace("#".preg_quote($matches['0'])."#", "/{$data['entry_id']}/", $_POST['URI']));
			}
			else
			{
				$FNS->clear_caching('all', $FNS->fetch_site_index().preg_replace("#{$data['entry_id']}#", $url_title, $_POST['URI']));
			}
		}
                
        /** ----------------------------------------
        /**  Set cookies
        /** ----------------------------------------*/
		
		if ($notify == 'y')
		{        
			$FNS->set_cookie('notify_me', 'yes', 60*60*24*365);
		}
		else
		{
			$FNS->set_cookie('notify_me', 'no', 60*60*24*365);
		}

        if ($IN->GBL('save_info', 'POST'))
        {        
            $FNS->set_cookie('save_info',   'yes',              60*60*24*365);
            $FNS->set_cookie('my_name',     $_POST['name'],     60*60*24*365);
            $FNS->set_cookie('my_email',    $_POST['email'],    60*60*24*365);
            $FNS->set_cookie('my_url',      $_POST['url'],      60*60*24*365);
            $FNS->set_cookie('my_location', $_POST['location'], 60*60*24*365);
        }
        else
        {
			$FNS->set_cookie('save_info',   'no', 60*60*24*365);
			$FNS->set_cookie('my_name',     '');
			$FNS->set_cookie('my_email',    '');
			$FNS->set_cookie('my_url',      '');
			$FNS->set_cookie('my_location', '');
        }
        
        // -------------------------------------------
        // 'insert_comment_end' hook.
        //  - More emails, more processing, different redirect
        //  - $comment_id added 1.6.1
		//
        	$edata = $EXT->call_extension('insert_comment_end', $data, $comment_moderate, $comment_id);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        /** -------------------------------------------
        /**  Bounce user back to the comment page
        /** -------------------------------------------*/
        
        if ($comment_moderate == 'y')
        {
			$data = array(	'title' 	=> $LANG->line('cmt_comment_accepted'),
							'heading'	=> $LANG->line('thank_you'),
							'content'	=> $LANG->line('cmt_will_be_reviewed'),
							'redirect'	=> $_POST['RET'],							
							'link'		=> array($_POST['RET'], $LANG->line('cmt_return_to_comments')),
							'rate'		=> 3
						 );
					
			$OUT->show_message($data);
		}
		else
		{
        	$FNS->redirect($_POST['RET']);
    	}
    }
    /* END */

}
// END CLASS
?>