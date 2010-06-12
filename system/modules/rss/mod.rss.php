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
 File: mod.rss.php
-----------------------------------------------------
 Purpose: RSS generating class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Rss {
	
	var $debug = FALSE;
	
    /** -------------------------------------
    /**  RSS feed
    /** -------------------------------------*/
    
    // This function fetches the weblog metadata used in
    // the channel section of RSS feeds
    
    // Note: The item elements are generated using the weblog class

    function feed()
    {
        global $DB, $LOC, $LANG, $TMPL, $FNS, $OUT, $PREFS;
        
        $TMPL->encode_email = FALSE;
 
		if ($TMPL->fetch_param('debug') == 'yes')
		{
			$this->debug = TRUE;
		}
		     
        if (USER_BLOG !== FALSE)
        {
            $weblog = USER_BLOG;
        }
        else
        {
            if ( ! $weblog = $TMPL->fetch_param('weblog'))
            {
            	$LANG->fetch_language_file('rss');
                return $this->_empty_feed($LANG->line('no_weblog_specified'));
            }
        } 
        
        /** ------------------------------------------
		/**  Create Meta Query
		/** ------------------------------------------*/

		// Since UTC_TIMESTAMP() is what we need, but it is not available until
		// MySQL 4.1.1, we have to use this ever so clever SQL to figure it out:
		// DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND )
		// -Paul

		$sql = "SELECT exp_weblog_titles.entry_id, exp_weblog_titles.entry_date, exp_weblog_titles.edit_date, 
				GREATEST((UNIX_TIMESTAMP(exp_weblog_titles.edit_date) + 
						 (UNIX_TIMESTAMP(DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND)) - UNIX_TIMESTAMP())),
						exp_weblog_titles.entry_date) AS last_update
				FROM exp_weblog_titles
				LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_weblog_titles.author_id
				WHERE exp_weblog_titles.entry_id !=''
				AND exp_weblogs.site_id IN ('".implode("','", $TMPL->site_ids)."') ";

                        
        if (USER_BLOG !== FALSE)
        {        
            $sql .= "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' ";
        }
        else
        {
            $sql .= "AND exp_weblogs.is_user_blog = 'n' ";
        
			$xql = "SELECT weblog_id FROM exp_weblogs WHERE ";
		
			$str = $FNS->sql_andor_string($weblog, 'blog_name');
			
			if (substr($str, 0, 3) == 'AND')
				$str = substr($str, 3);
			
			$xql .= $str;            
				
			$query = $DB->query($xql);
			
			if ($query->num_rows == 0)
			{
            	$LANG->fetch_language_file('rss');
                return $this->_empty_feed($LANG->line('rss_invalid_weblog'));
			}
			
			if ($query->num_rows == 1)
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
 
        /** ----------------------------------------------
        /**  We only select entries that have not expired 
        /** ----------------------------------------------*/
        
		$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;
		        
        if ($TMPL->fetch_param('show_future_entries') != 'yes')
        {
			$sql .= " AND exp_weblog_titles.entry_date < ".$timestamp." ";
        }
        
        if ($TMPL->fetch_param('show_expired') != 'yes')
        {        
			$sql .= " AND (exp_weblog_titles.expiration_date = 0 OR exp_weblog_titles.expiration_date > ".$timestamp.") ";
        }
 
        /** ----------------------------------------------
        /**  Add status declaration
        /** ----------------------------------------------*/
        
		$sql .= "AND exp_weblog_titles.status != 'closed' ";
        
        if ($status = $TMPL->fetch_param('status'))
        {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
        
            $sql .= $FNS->sql_andor_string($status, 'exp_weblog_titles.status');
        }
        else
        {
            $sql .= "AND exp_weblog_titles.status = 'open' ";
        }
                
        /** ----------------------------------------------
        /**  Limit to (or exclude) specific users
        /** ----------------------------------------------*/
        
        if ($username = $TMPL->fetch_param('username'))
        {
            // Shows entries ONLY for currently logged in user
        
            if ($username == 'CURRENT_USER')
            {
                $sql .=  "AND exp_members.member_id = '".$SESS->userdata['member_id']."' ";
            }
            elseif ($username == 'NOT_CURRENT_USER')
            {
                $sql .=  "AND exp_members.member_id != '".$SESS->userdata['member_id']."' ";
            }
            else
            {                
                $sql .= $FNS->sql_andor_string($username, 'exp_members.username');
            }
        }
        
        // Find Edit Date
        $query = $DB->query($sql." ORDER BY last_update desc LIMIT 1");
        
        if ($query->num_rows > 0)
        {
        	$last_update = $query->row['last_update'] + ($LOC->set_server_time() - $LOC->now);
        	$edit_date = $query->row['edit_date'];
        	$entry_date = $query->row['entry_date'];
        }
                  
        $sql .= " ORDER BY exp_weblog_titles.entry_date desc LIMIT 1";
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
			$LANG->fetch_language_file('rss');
			return $this->_empty_feed($LANG->line('no_matching_entries'));
        }
        
        $entry_id = $query->row['entry_id'];
        
        $sql = "SELECT 	exp_weblogs.weblog_id, exp_weblogs.blog_title, exp_weblogs.blog_url, exp_weblogs.blog_lang, exp_weblogs.blog_encoding, exp_weblogs.blog_description,
                       	exp_weblog_titles.entry_date,
                      	exp_members.email, exp_members.username, exp_members.screen_name, exp_members.url
                FROM	exp_weblog_titles
				LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_weblog_titles.author_id
				WHERE exp_weblog_titles.entry_id = '$entry_id'
				AND exp_weblogs.site_id IN ('".implode("','", $TMPL->site_ids)."') "; 
				
        $query = $DB->query($sql);
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        $request		= ( ! function_exists('getallheaders')) ? array() : @getallheaders();
        $start_on		= '';
        $diffe_request	= false;
        $feed_request	= false;
        
        /** --------------------------------------------
        /**  Check for 'diff -e' request
        /** --------------------------------------------*/
        
        if (isset($request['A-IM']) && stristr($request['A-IM'],'diffe') !== false)
        {
			$items_start = strpos($TMPL->tagdata, '{exp:weblog:entries');
			
			if ($items_start !== false)
			{
				// We add three, for three line breaks added later in the script
				$diffe_request = count(preg_split("/(\r\n)|(\r)|(\n)/", trim(substr($TMPL->tagdata,0,$items_start)))) + 3;
			}
        }
        
        /** --------------------------------------------
        /**  Check for 'feed' request
        /** --------------------------------------------*/
        
        if (isset($request['A-IM']) && stristr($request['A-IM'],'feed') !== false)
        {
			$feed_request = true;
			$diffe_request = false;
        }
        
        
        /** --------------------------------------------
        /**  Check for the 'If-Modified-Since' Header
        /** --------------------------------------------*/
        		
        if ($PREFS->ini('send_headers') == 'y' && isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '')
        {
			$x				= explode(';',$request['If-Modified-Since']);
			$modify_tstamp	=  strtotime($x['0']);
			
        	// ---------------------------------------------------------
        	//  If new content *and* 'feed' or 'diffe', create start on time.
        	//  Otherwise, we send back a Not Modified header
        	// ---------------------------------------------------------

        	if ($last_update <= $modify_tstamp)
	       	{
				$OUT->http_status_header(304);
				@exit;
       		}
       		else
       		{
       			if ($diffe_request !== false OR $feed_request !== false)
       			{
       				//$start_on = $LOC->set_human_time($LOC->set_server_time($modify_tstamp), FALSE);
       				$start_on = gmdate('Y-m-d h:i A',$LOC->set_localized_time($modify_tstamp));
       			}
       		}
        }        		
                
        $chunks = array();
        $marker = 'H94e99Perdkie0393e89vqpp'; 
		
		if (preg_match_all("/{exp:weblog:entries.+?{".SLASH."exp:weblog:entries}/s", $TMPL->tagdata, $matches))
		{
			for($i = 0; $i < count($matches['0']); $i++)
			{
				$TMPL->tagdata = str_replace($matches['0'][$i], $marker.$i, $TMPL->tagdata);
				
				// Remove limit if we have a start_on and dynamic_start
				if ($start_on != '' && stristr($matches['0'][$i],'dynamic_start="on"'))
				{
					$matches['0'][$i] = preg_replace("/limit=[\"\'][0-9]{1,5}[\"\']/", '', $matches['0'][$i]);
				}
				
				// Replace dynamic_start="on" parameter with start_on="" param
				$start_on_switch = ($start_on != '') ? 'start_on="'.$start_on.'"' : '';
				$matches['0'][$i] = preg_replace("/dynamic_start\s*=\s*[\"|']on[\"|']/i", $start_on_switch, $matches['0'][$i]);
				
				$chunks[$marker.$i] = $matches['0'][$i];
			}
		}
		
        /** ----------------------------------------
        /**  Fetch all the date-related variables
        /** ----------------------------------------*/
        
        // We do this here to avoid processing cycles in the foreach loop
        
        $entry_date_array 		= array();
        $gmt_date_array 		= array();
        $gmt_entry_date_array	= array();
        $edit_date_array 		= array();
        $gmt_edit_date_array	= array();
        
        $date_vars = array('date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date');
                
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
						case 'date' 			: $entry_date_array[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_date'			: $gmt_date_array[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_entry_date'	: $gmt_entry_date_array[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'edit_date' 		: $edit_date_array[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_edit_date'	: $gmt_edit_date_array[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}
      	
		
		foreach ($TMPL->var_single as $key => $val)
		{          
			/** ----------------------------------------
			/**  {weblog_id}
			/** ----------------------------------------*/
			
			if ($key == 'weblog_id')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$weblog_id, 
															$TMPL->tagdata
														);
			}


			/** ----------------------------------------
			/**  {encoding}
			/** ----------------------------------------*/
			
			if ($key == 'encoding')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$blog_encoding, 
															$TMPL->tagdata
														);
			}


			/** ----------------------------------------
			/**  {weblog_language}
			/** ----------------------------------------*/
			
			if ($key == 'weblog_language')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$blog_lang, 
															$TMPL->tagdata
														);
			}


			/** ----------------------------------------
			/**  {weblog_description}
			/** ----------------------------------------*/
			
			if ($key == 'weblog_description')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$blog_description, 
															$TMPL->tagdata
														);
			}
			
			/** ----------------------------------------
			/**  {weblog_url}
			/** ----------------------------------------*/
			
			if ($key == 'weblog_url')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$blog_url, 
															$TMPL->tagdata
														);
			}

			/** ----------------------------------------
			/**  {weblog_name}
			/** ----------------------------------------*/
			
			if ($key == 'weblog_name')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$blog_title, 
															$TMPL->tagdata
														);
			}


			/** ----------------------------------------
			/**  {email}
			/** ----------------------------------------*/
			
			if ($key == 'email')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$email, 
															$TMPL->tagdata
														);
			}
			
			
			/** ----------------------------------------
			/**  {url}
			/** ----------------------------------------*/
			
			if ($key == 'url')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single(
															$key, 
															$url, 
															$TMPL->tagdata
														);
			}

			/** ----------------------------------------
			/**  {date}
			/** ----------------------------------------*/
			
			if (isset($entry_date_array[$key]))
			{
				foreach ($entry_date_array[$key] as $dvar)
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $entry_date, TRUE), $val);					

				$TMPL->tagdata = $TMPL->swap_var_single($key, $val, $TMPL->tagdata);					
			}
                				
			
			/** ----------------------------------------
			/**  GMT date - entry date in GMT
			/** ----------------------------------------*/
			
			if (isset($gmt_entry_date_array[$key]))
			{
				foreach ($gmt_entry_date_array[$key] as $dvar)
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $entry_date, FALSE), $val);					

				$TMPL->tagdata = $TMPL->swap_var_single($key, $val, $TMPL->tagdata);					
			}
			
			if (isset($gmt_date_array[$key]))
			{
				foreach ($gmt_date_array[$key] as $dvar)
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $entry_date, FALSE), $val);					

				$TMPL->tagdata = $TMPL->swap_var_single($key, $val, $TMPL->tagdata);					
			}
		

			/** ----------------------------------------
			/**  parse "last edit" date
			/** ----------------------------------------*/
			
			if (isset($edit_date_array[$key]))
			{
				foreach ($edit_date_array[$key] as $dvar)
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $edit_date, TRUE), $val);					

				$TMPL->tagdata = $TMPL->swap_var_single($key, $val, $TMPL->tagdata);					
			}                
			
			/** ----------------------------------------
			/**  "last edit" date as GMT
			/** ----------------------------------------*/
			
			if (isset($gmt_edit_date_array[$key]))
			{
				foreach ($gmt_edit_date_array[$key] as $dvar)
					$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $LOC->timestamp_to_gmt($edit_date), FALSE), $val);					

				$TMPL->tagdata = $TMPL->swap_var_single($key, $val, $TMPL->tagdata);					
			}
			
			/** ----------------------------------------
			/**  {author}
			/** ----------------------------------------*/
			
			if ($key == 'author')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single($val, ($screen_name != '') ? $screen_name : $username, $TMPL->tagdata);
			}
			
			/** ----------------------------------------
			/**  {version}
			/** ----------------------------------------*/
			
			if ($key == 'version')
			{                     
				$TMPL->tagdata = $TMPL->swap_var_single($val, APP_VER, $TMPL->tagdata);
			}
					
			/** ----------------------------------------
			/**  {trimmed_url} - used by Atom feeds
			/** ----------------------------------------*/
			
			if ($key == "trimmed_url")
			{
				$blog_url = (isset($blog_url) AND $blog_url != '') ? $blog_url : '';
			
				$blog_url = str_replace('http://', '', $blog_url);
				$blog_url = str_replace('www.', '', $blog_url);
				$ex = explode("/", $blog_url);
				$blog_url = current($ex);
			
				$TMPL->tagdata = $TMPL->swap_var_single($val, $blog_url, $TMPL->tagdata);
			}			
		}
    	     	    
        if (count($chunks) > 0)
        {        	
			$diff_top = ($start_on != '' && $diffe_request !== false) ? "1,".($diffe_request-1)."c\n" : '';
			
			// Last Update Time
			$TMPL->tagdata = '<ee:last_update>'.$last_update."</ee:last_update>\n\n".$diff_top.trim($TMPL->tagdata);
			
			// Diffe stuff before items
			if ($diffe_request !== false)
			{
				$TMPL->tagdata = str_replace($marker.'0', "\n.\n".$diffe_request."a\n".$marker.'0', $TMPL->tagdata);
				$TMPL->tagdata = str_replace($marker.(count($chunks)-1), $marker.(count($chunks)-1)."\n.\n$\n-1\n;c\n", $TMPL->tagdata);
			}
			
			foreach ($chunks as $key => $val)
			{
				$TMPL->tagdata = str_replace($key, $val, $TMPL->tagdata);	
			}
        }
        
        // 'ed' input mode is terminated by entering a single period  (.) on a line
        $TMPL->tagdata = ($diffe_request !== false) ? trim($TMPL->tagdata)."\n.\n" : trim($TMPL->tagdata);
        
        return $TMPL->tagdata;  
    }
    /* END */

	/** -------------------------------------
	/**  Empty feed handler
	/** -------------------------------------*/
    
	function _empty_feed($error = '')
	{
		global $FNS, $TMPL;
		
		if ($error != '')
		{
			$TMPL->log_item($error);
		}
		
		$empty_feed = '';

		if (preg_match("/".LD."if empty_feed".RD."(.*?)".LD.SLASH."if".RD."/s", $TMPL->tagdata, $match)) 
		{
			if (stristr($match['1'], LD.'if'))
			{
				$match['0'] = $FNS->full_tag($match['0'], $TMPL->tagdata, LD.'if', LD.SLASH."if".RD);
			}
			
			$empty_feed = substr($match['0'], strlen(LD."if empty_feed".RD), -strlen(LD.SLASH."if".RD));
			
			$empty_feed = str_replace(LD.'error'.RD, $error, $empty_feed);
		}
		
		if ($empty_feed == '')
		{
			$empty_feed = $this->_default_empty_feed($error);
		}
		
		return $empty_feed;
	}
	/* END */

	/** -------------------------------------
	/**  Default empty feed
	/** -------------------------------------*/
	
	function _default_empty_feed($error = '')
	{
		global $LANG, $LOC, $PREFS;
		
		$LANG->fetch_language_file('rss');
		
		$encoding	= $PREFS->ini('charset');
		$title		= $PREFS->ini('site_name');
		$link		= $PREFS->ini('site_url');
		$version	= APP_VER;
		$pubdate	= date('D, d M Y H:i:s', $LOC->now).' GMT';
		$content	= ($this->debug === TRUE && $error != '') ? $error : $LANG->line('empty_feed');
		
		return <<<HUMPTYDANCE
<?xml version="1.0" encoding="{$encoding}"?>
<rss version="2.0">
	<channel>
	<title>{$title}</title>
	<link>{$link}</link>
	<description></description>
	<docs>http://www.rssboard.org/rss-specification</docs>
	<generator>ExpressionEngine v{$version} http://expressionengine.com/</generator>
	
	<item>
		<title>{$content}</title>
		<description>{$content}</description>
		<pubDate>{$pubdate}</pubDate>
	</item>
	</channel>
</rss>		
HUMPTYDANCE;
	}
	/* END */
	
}
// END CLASS
?>