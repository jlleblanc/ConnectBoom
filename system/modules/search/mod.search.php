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
 File: mod.search.php
-----------------------------------------------------
 Purpose: Search class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Search {

	var	$min_length		= 3;			// Minimum length of search keywords
	var	$cache_expire	= 2;			// How many hours should we keep search caches?
	var	$keywords		= "";
	var	$text_format	= 'xhtml';		// Excerpt text formatting
	var	$html_format	= 'all';		// Excerpt html formatting
	var	$auto_links		= 'y';			// Excerpt auto-linking: y/n
	var	$allow_img_url	= 'n';			// Excerpt - allow images:  y/n
	var	$blog_array 	= array();
	var	$cat_array  	= array();
	var $fields			= array();
	var $num_rows		= 0;


    /** ----------------------------------------
    /**  Perform Search
    /** ----------------------------------------*/
	
	function do_search()
	{
		global $IN, $LANG, $DB, $SESS, $OUT, $FNS, $REGX, $PREFS;
		
        /** ----------------------------------------
        /**  Fetch the search language file
        /** ----------------------------------------*/
        
        $LANG->fetch_language_file('search');
        
        /** ----------------------------------------
        /**  Profile Exception
        /** ----------------------------------------*/
        
        // This is an exception to the normal search routine.
        // It permits us to search for all posts by a particular user's screen name
        // We look for the "mbr" $_GET variable.  If it exsists it will
        // trigger our exception
        
        if ($IN->GBL('mbr'))
        {
			$_POST['RP'] 			= ($IN->GBL('result_path') != '') ? $IN->GBL('result_path') : 'search/results';
			$_POST['keywords']		= '';
			$_POST['exact_match'] 	= 'y';
			$_POST['exact_keyword'] = 'n';
			
		//	$_POST['member_name'] 	= urldecode($IN->GBL('fetch_posts_by'));
			
        }

        /** ----------------------------------------
        /**  Pulldown Addition - Any, All, Exact
        /** ----------------------------------------*/
        
        if (isset($_POST['where']) && $_POST['where'] == 'exact')
        {
        	$_POST['exact_keyword'] = 'y';
        }
        
        /** ----------------------------------------
        /**  Do we have a search results page?
        /** ----------------------------------------*/
        
        // The search results template is specified as a parameter in the search form tag.
        // If the parameter is missing we'll issue an error since we don't know where to 
        // show the results
        
        if ( ! isset($_POST['RP']) OR $_POST['RP'] == '')
        {
            return $OUT->show_user_error('general', array($LANG->line('search_path_error')));
        }
		
        /** ----------------------------------------
        /**  Is the current user allowed to search?
        /** ----------------------------------------*/

        if ($SESS->userdata['can_search'] == 'n' AND $SESS->userdata['group_id'] != 1)
        {            
            return $OUT->show_user_error('general', array($LANG->line('search_not_allowed')));
        }
		
        /** ----------------------------------------
        /**  Flood control
        /** ----------------------------------------*/
        
        if ($SESS->userdata['search_flood_control'] > 0 AND $SESS->userdata['group_id'] != 1)
		{
			$cutoff = time() - $SESS->userdata['search_flood_control'];

			$sql = "SELECT search_id FROM exp_search WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND search_date > '{$cutoff}' AND ";
			
			if ($SESS->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".$DB->escape_str($SESS->userdata('member_id'))."' OR ip_address='".$DB->escape_str($IN->IP)."')";
			}
			else
			{
				$sql .= "ip_address='".$DB->escape_str($IN->IP)."'";
			}
			
			$query = $DB->query($sql);
					
			$text = str_replace("%x", $SESS->userdata['search_flood_control'], $LANG->line('search_time_not_expired'));
				
			if ($query->num_rows > 0)
			{
            	return $OUT->show_user_error('general', array($text));
			}
		}
		
        /** ----------------------------------------
        /**  Did the user submit any keywords?
        /** ----------------------------------------*/
        
        // We only require a keyword if the member name field is blank
        
		if ( ! isset($_GET['mbr']) OR ! is_numeric($_GET['mbr']))
		{        
			if ( ! isset($_POST['member_name']) OR $_POST['member_name'] == '')
			{        
				if ( ! isset($_POST['keywords']) OR $_POST['keywords'] == "")
				{            
					return $OUT->show_user_error('general', array($LANG->line('search_no_keywords')));
				}
			}
		}
		
		/** ----------------------------------------
		/**  Strip extraneous junk from keywords
		/** ----------------------------------------*/

		if ($_POST['keywords'] != "")		
		{
			$this->keywords = $REGX->keyword_clean($_POST['keywords']);
			
			/** ----------------------------------------
			/**  Is the search term long enough?
			/** ----------------------------------------*/
	
			if (strlen($this->keywords) < $this->min_length)
			{
				$text = $LANG->line('search_min_length');
				
				$text = str_replace("%x", $this->min_length, $text);
							
				return $OUT->show_user_error('general', array($text));
			}
			
			$this->keywords = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($this->keywords) : $this->keywords;
			
			/** ----------------------------------------
			/**  Remove "ignored" words
			/** ----------------------------------------*/
		
			if (( ! isset($_POST['exact_keyword']) OR $_POST['exact_keyword'] != 'y') && @include_once(PATH_LIB.'stopwords'.EXT))
			{
				$parts = explode('"', $this->keywords);
				
				$this->keywords = '';
				
				foreach($parts as $num => $part)
				{
					// The odd breaks contain quoted strings.
					if ($num % 2 == 0)
					{
						foreach ($ignore as $badword)
						{        
							$part = preg_replace("/\b".preg_quote($badword, '/')."\b/i","", $part);
						}
					}
					
					$this->keywords .= ($num != 0) ? '"'.$part : $part;
				}
				
				if (trim($this->keywords) == '')
				{
					return $OUT->show_user_error('general', array($LANG->line('search_no_stopwords')));
				}
			}
			
			/** ----------------------------------------
			/**  Log Search Terms
			/** ----------------------------------------*/
			
			$FNS->log_search_terms($this->keywords);
		}
		
		if (isset($_POST['member_name']) AND $_POST['member_name'] != "")
		{
			$_POST['member_name'] = $REGX->xss_clean($_POST['member_name']);
		}
		
        /** ----------------------------------------
        /**  Build and run query
        /** ----------------------------------------*/
        
        $original_keywords = $this->keywords;
		$mbr = ( ! isset($_GET['mbr'])) ? '' : $_GET['mbr'];

        $sql = $this->build_standard_query();
        
        /** ----------------------------------------
        /**  No query results?
        /** ----------------------------------------*/
		
		if ($sql == FALSE)
		{	
			if (isset($_POST['NRP']) AND $_POST['NRP'] != '')
			{
				$hash = $FNS->random('md5');
				
				$data = array(
						'search_id'		=> $hash,
						'search_date'	=> time(),
						'member_id'		=> $SESS->userdata('member_id'),
						'keywords'		=> ($original_keywords != '') ? $original_keywords : $mbr,
						'ip_address'	=> $IN->IP,
						'total_results'	=> 0,
						'per_page'		=> 0,
						'query'			=> '',
						'custom_fields'	=> '',
						'result_page'	=> '',
						'site_id'		=> $PREFS->ini('site_id')
						);
		
				$DB->query($DB->insert_string('exp_search', $data));
				
				return $FNS->redirect($FNS->create_url($FNS->extract_path("='".$_POST['NRP']."'")).$hash.'/');
			}
			else
			{
				return $OUT->show_user_error('off', array($LANG->line('search_no_result')), $LANG->line('search_result_heading'));
			}
		}
		
        /** ----------------------------------------
        /**  If we have a result, cache it
        /** ----------------------------------------*/
		
		$hash = $FNS->random('md5');
		
		$sql = str_replace("\\", "\\\\", $sql);
		
		// This fixes a bug that occurs when a different table prefix is used
        
        $sql = str_replace('exp_', 'MDBMPREFIX', $sql);
		
		$data = array(
						'search_id'		=> $hash,
						'search_date'	=> time(),
						'member_id'		=> $SESS->userdata('member_id'),
						'keywords'		=> ($original_keywords != '') ? $original_keywords : $mbr,
						'ip_address'	=> $IN->IP,
						'total_results'	=> $this->num_rows,
						'per_page'		=> (isset($_POST['RES']) AND is_numeric($_POST['RES']) AND $_POST['RES'] < 999 ) ? $_POST['RES'] : 50,
						'query'			=> addslashes(serialize($sql)),
						'custom_fields'	=> addslashes(serialize($this->fields)),
						'result_page'	=> $_POST['RP'],
						'site_id'		=> $PREFS->ini('site_id')
						);
		
		$DB->query($DB->insert_string('exp_search', $data));
					
        /** ----------------------------------------
        /**  Redirect to search results page
        /** ----------------------------------------*/
					
		$path = $FNS->remove_double_slashes($FNS->create_url($REGX->trim_slashes($_POST['RP'])).$hash.'/');
		
		return $FNS->redirect($path);
	}
	/* END */
	
	
	
	
	/** ---------------------------------------
	/**  Create the search query
	/** ---------------------------------------*/

	function build_standard_query()
	{
		global $DB, $LOC, $FNS, $IN, $PREFS;
		
        $blog_array	= array();
        		
		/** ---------------------------------------
        /**  Fetch the weblog_id numbers
        /** ---------------------------------------*/
			
        // If $_POST['weblog_id'] exists we know the request is coming from the 
        // advanced search form. We set those values to the $blog_id_array        

        if (isset($_POST['weblog_id']) AND is_array($_POST['weblog_id']))
        {
			$blog_id_array = $_POST['weblog_id'];
        }
        
        // Since both the simple and advanced search form have
        // $_POST['weblog'], then we can safely find all of the
        // weblogs available for searching
        
        // By doing this for the advanced search form, we can discover
        // Which weblogs we are or are not supposed to search for, when
        // "Any Weblog" is chosen
        
		$sql = "SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND ";        
									
		if (USER_BLOG !== FALSE)
		{
			// If it's a "user blog" we limit to only their assigned blog
		
			$sql .= "weblog_id = '".UB_BLOG_ID."' ";
		}
		else
		{
			$sql .= "is_user_blog = 'n' ";
			
			if (isset($_POST['weblog']) AND $_POST['weblog'] != '')
			{
				$sql .= $FNS->sql_andor_string($_POST['weblog'], 'blog_name');
			}
		}
							
		$query = $DB->query($sql);
				
		foreach ($query->result as $row)
		{
			$blog_array[] = $row['weblog_id'];
		}        
		
		/** ------------------------------------------------------
		/**  Find the Common Weblog IDs for Advanced Search Form
		/** ------------------------------------------------------*/
		
		if (isset($blog_id_array) && $blog_id_array['0'] != 'null')
		{
			$blog_array = array_intersect($blog_id_array, $blog_array);
		}
						
        /** ----------------------------------------------
        /**  Fetch the weblog_id numbers (from Advanced search)
        /** ----------------------------------------------*/
        
        // We do this up-front since we use this same sub-query in two places

		$id_query = '';
                        
        if (count($blog_array) > 0)
        {                
			foreach ($blog_array as $val)
			{
				if ($val != 'null' AND $val != '')
				{
					$id_query .= " exp_weblog_titles.weblog_id = '".$DB->escape_str($val)."' OR";
				}
        	} 
        	        	
			if ($id_query != '')
			{
				$id_query = substr($id_query, 0, -2);
				$id_query = ' AND ('.$id_query.') ';
			}
        }

        /** ----------------------------------------------
        /**  Limit to a specific member? We do this now
        /**  as there's a potential for this to bring the
        /**  search to an end if it's not a valid member
        /** ----------------------------------------------*/
        
		$member_array	= array();
		$member_ids		= '';
		
        if (isset($_GET['mbr']) AND is_numeric($_GET['mbr']))
        {
			$query = $DB->query("SELECT member_id FROM exp_members WHERE member_id = '".$DB->escape_str($_GET['mbr'])."'");
			
			if ($query->num_rows != 1)
			{
				return FALSE;
			}
			else
			{
				$member_array[] = $query->row['member_id'];
			}
        }
        else
        {
			if (isset($_POST['member_name']) AND $_POST['member_name'] != '')
			{
				$sql = "SELECT member_id FROM exp_members WHERE screen_name ";
				
				if (isset($_POST['exact_match']) AND $_POST['exact_match'] == 'y')
				{
					$sql .= " = '".$DB->escape_str($_POST['member_name'])."' ";
				}
				else
				{
					$sql .= " LIKE '%".$DB->escape_like_str($_POST['member_name'])."%' ";
				}
				
				$query = $DB->query($sql);
			
				if ($query->num_rows == 0)
				{
					return FALSE;
				}
				else
				{
					foreach ($query->result as $row)
					{
						$member_array[] = $row['member_id'];
					}
				}
			}
		}

		// and turn it into a string now so we only implode once
		if (count($member_array) > 0)
		{
			$member_ids = ' IN ('.implode(',', $member_array).') ';
		}
		
		unset($member_array);
		
		
		/** ---------------------------------------
		/**  Fetch the searchable field names
		/** ---------------------------------------*/
				
		$fields = array();
		
		// no need to do this unless there are keywords to search
		if (trim($this->keywords) != '')
		{
			$xql = "SELECT DISTINCT(field_group) FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND ";

			if (USER_BLOG !== FALSE)
			{        
				$xql .= "weblog_id = '".UB_BLOG_ID."' ";
			}
			else
			{
				$xql .= "is_user_blog = 'n' ";
			}

			if ($id_query != '')
			{
				$xql .= $id_query.' ';
				$xql = str_replace('exp_weblog_titles.', '', $xql);
			}

			$query = $DB->query($xql);

			if ($query->num_rows > 0)
			{
				$fql = "SELECT field_id, field_name, field_search FROM exp_weblog_fields WHERE (";

				foreach ($query->result as $row)
				{
					$fql .= " group_id = '".$row['field_group']."' OR";	
				}

				$fql = substr($fql, 0, -2).')';  

				$query = $DB->query($fql);

				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						if ($row['field_search'] == 'y')
						{
							$fields[] = $row['field_id'];
						}

						$this->fields[$row['field_name']] = array($row['field_id'], $row['field_search']);
					}
				}
			}	
		}
				
		/** ---------------------------------------
		/**  Build the main query
		/** ---------------------------------------*/
	
	
		$sql = "SELECT
				DISTINCT(exp_weblog_titles.entry_id)
				FROM exp_weblog_titles
				LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
				LEFT JOIN exp_weblog_data ON exp_weblog_titles.entry_id = exp_weblog_data.entry_id 
				LEFT JOIN exp_comments ON exp_weblog_titles.entry_id = exp_comments.entry_id
				LEFT JOIN exp_category_posts ON exp_weblog_titles.entry_id = exp_category_posts.entry_id
				LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id
				WHERE exp_weblogs.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
				AND ";
								
        /** ----------------------------------------------
        /**  Is this a user blog?
        /** ----------------------------------------------*/

        if (USER_BLOG !== FALSE)
        {        
            $sql .= "exp_weblogs.weblog_id = '".UB_BLOG_ID."' ";
        }
        else
        {
            $sql .= "exp_weblogs.is_user_blog = 'n' ";
        }
        
        /** ----------------------------------------------
        /**  We only select entries that have not expired 
        /** ----------------------------------------------*/
        
        if ( ! isset($_POST['show_future_entries']) OR $_POST['show_future_entries'] != 'yes')
        {
        	$sql .= "\nAND exp_weblog_titles.entry_date < ".$LOC->now." ";
        }
        
        if ( ! isset($_POST['show_expired']) OR $_POST['show_expired'] != 'yes')
        {
        	$sql .= "\nAND (exp_weblog_titles.expiration_date = 0 OR exp_weblog_titles.expiration_date > ".$LOC->now.") ";
        }
        
        /** ----------------------------------------------
        /**  Add status declaration to the query
        /** ----------------------------------------------*/
                
        if (($status = $IN->GBL('status')) !== FALSE)
        {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
        
            $sql .= $FNS->sql_andor_string($status, 'exp_weblog_titles.status');
			
			// add exclusion for closed unless it was explicitly used
			if (strncasecmp($status, 'not ', 4) == 0)
			{
				$status = trim(substr($status, 3));
			}
			
			$stati = explode('|', $status);
			
			if (! in_array('closed', $stati))
			{
				$sql .= "\nAND exp_weblog_titles.status != 'closed' ";				
			}
        }
        else
        {
            $sql .= "AND exp_weblog_titles.status = 'open' ";
        }
        
        /** ----------------------------------------------
        /**  Set Date filtering
        /** ----------------------------------------------*/
        
        if (isset($_POST['date']) AND $_POST['date'] != 0)
        {
			$cutoff = $LOC->now - (60*60*24*$_POST['date']);
			
			if (isset($_POST['date_order']) AND $_POST['date_order'] == 'older')
			{
				$sql .= "AND exp_weblog_titles.entry_date < ".$cutoff." ";
			}
			else
			{
				$sql .= "AND exp_weblog_titles.entry_date > ".$cutoff." ";
			}
        }
        
        /** ----------------------------------------------
        /**  Add keyword to the query
        /** ----------------------------------------------*/
		
		if (trim($this->keywords) != '')
		{
			// So it begins
			$sql .= "\nAND (";
			
			/** -----------------------------------------
			/**  Process our Keywords into Search Terms
			/** -----------------------------------------*/
		
			$this->keywords = stripslashes($this->keywords);
			$terms = array();
			$criteria = (isset($_POST['where']) && $_POST['where'] == 'all') ? 'AND' : 'OR'; 
			
			if (preg_match_all("/\-*\"(.*?)\"/", $this->keywords, $matches))
			{
				for($m=0; $m < sizeof($matches['1']); $m++)
				{
					$terms[] = trim(str_replace('"','',$matches['0'][$m]));
					$this->keywords = str_replace($matches['0'][$m],'', $this->keywords);
				}    
			}
    		
			if (trim($this->keywords) != '')
			{
    			$terms = array_merge($terms, preg_split("/\s+/", trim($this->keywords)));
  			}
  			
  			$not_and = (sizeof($terms) > 2) ? ') AND (' : 'AND';
  			rsort($terms);
			$terms_like = $DB->escape_like_str($terms);
			$terms = $DB->escape_str($terms);

  			/** ----------------------------------
			/**  Search in Title Field
			/** ----------------------------------*/
			
			if (sizeof($terms) == 1 && isset($_POST['where']) && $_POST['where'] == 'word') // Exact word match
			{				
				$sql .= "((exp_weblog_titles.title = '".$terms['0']."' OR exp_weblog_titles.title LIKE '".$terms_like['0']." %' OR exp_weblog_titles.title LIKE '% ".$terms_like['0']." %') ";
				
				// and close up the member clause
				if ($member_ids != '')
				{
					$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
				}
				else
				{
					$sql .= ") \n";
				}
			}			
			elseif ( ! isset($_POST['exact_keyword']))  // Any terms, all terms
			{				
				$mysql_function	= (substr($terms['0'], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';    
				$search_term	= (substr($terms['0'], 0,1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];
				
				// We have three parentheses in the beginning in case
				// there are any NOT LIKE's being used and to allow for a member clause
				$sql .= "\n(((exp_weblog_titles.title $mysql_function '%".$search_term."%' ";
    			
				for ($i=1; $i < sizeof($terms); $i++) 
				{
					$mysql_criteria	= ($mysql_function == 'NOT LIKE' OR substr($terms[$i], 0,1) == '-') ? $not_and : $criteria;
					$mysql_function	= (substr($terms[$i], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';
					$search_term	= (substr($terms[$i], 0,1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];
					
					$sql .= "$mysql_criteria exp_weblog_titles.title $mysql_function '%".$search_term."%' ";
				}
				
				$sql .= ")) ";
				
				// and close up the member clause
				if ($member_ids != '')
				{
					$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
				}
				else
				{
					$sql .= ") \n";
				}
			}
			else // exact phrase match
			{	
				$search_term = (sizeof($terms) == 1) ? $terms_like[0] : $DB->escape_like_str($this->keywords);				
				$sql .= "(exp_weblog_titles.title LIKE '%".$search_term."%' ";
				
				// and close up the member clause
				if ($member_ids != '')
				{
					$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
				}
				else
				{
					$sql .= ") \n";
				}
			}
			
			/** ----------------------------------
			/**  Search in Searchable Fields
			/** ----------------------------------*/
			
			if (isset($_POST['search_in']) AND ($_POST['search_in'] == 'entries' OR $_POST['search_in'] == 'everywhere'))
			{
				if (sizeof($terms) > 1 && isset($_POST['where']) && $_POST['where'] == 'all' && ! isset($_POST['exact_keyword']) && sizeof($fields) > 0)
				{
					// force case insensitivity, but only on 4.0.2 or higher
					if (version_compare(mysql_get_server_info(), '4.0.2', '>=') !== FALSE)
					{
						$concat_fields = "CAST(CONCAT_WS(' ', exp_weblog_data.field_id_".implode(', exp_weblog_data.field_id_', $fields).') AS CHAR)';						
					}
					else
					{
						$concat_fields = "CONCAT_WS(' ', exp_weblog_data.field_id_".implode(', exp_weblog_data.field_id_', $fields).')';
					}
					
					$mysql_function	= (substr($terms['0'], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';    
					$search_term	= (substr($terms['0'], 0,1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];
							
					// Since Title is always required in a search we use OR
					// And then three parentheses just like above in case
					// there are any NOT LIKE's being used and to allow for a member clause
					$sql .= "\nOR ((($concat_fields $mysql_function '%".$search_term."%' ";
    				
					for ($i=1; $i < sizeof($terms); $i++) 
					{
						$mysql_criteria	= ($mysql_function == 'NOT LIKE' OR substr($terms[$i], 0,1) == '-') ? $not_and : $criteria;
						$mysql_function	= (substr($terms[$i], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';
						$search_term	= (substr($terms[$i], 0,1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];
						
						$sql .= "$mysql_criteria $concat_fields $mysql_function '%".$search_term."%' ";
					}
							
					$sql .= ")) ";
									
					// and close up the member clause
					if ($member_ids != '')
					{
						$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
					}
					else
					{
						$sql .= ") \n";
					}
				}
				else
				{
					foreach ($fields as $val)
					{					
						if (sizeof($terms) == 1 && isset($_POST['where']) && $_POST['where'] == 'word')
						{
							$sql .= "\nOR ((exp_weblog_data.field_id_".$val." LIKE '".$terms_like['0']." %' OR exp_weblog_data.field_id_".$val." LIKE '% ".$terms_like['0']." %' OR exp_weblog_data.field_id_".$val." LIKE '% ".$terms_like['0']."' OR exp_weblog_data.field_id_".$val." = '".$terms['0']."') ";
														
							// and close up the member clause
							if ($member_ids != '')
							{
								$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) ";
							}
							else
							{
								$sql .= ") ";
							}
						}
						elseif ( ! isset($_POST['exact_keyword']))
						{
							$mysql_function	= (substr($terms['0'], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';    
							$search_term	= (substr($terms['0'], 0,1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];
							
							// Since Title is always required in a search we use OR
							// And then three parentheses just like above in case
							// there are any NOT LIKE's being used and to allow for a member clause
							$sql .= "\nOR (((exp_weblog_data.field_id_".$val." $mysql_function '%".$search_term."%' ";
    				
							for ($i=1; $i < sizeof($terms); $i++) 
							{
								$mysql_criteria	= ($mysql_function == 'NOT LIKE' OR substr($terms[$i], 0,1) == '-') ? $not_and : $criteria;
								$mysql_function	= (substr($terms[$i], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';
								$search_term	= (substr($terms[$i], 0,1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];
						
								$sql .= "$mysql_criteria exp_weblog_data.field_id_".$val." $mysql_function '%".$search_term."%' ";
							}
							
							$sql .= ")) ";
							
							// and close up the member clause
							if ($member_ids != '')
							{
								$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
							}
							else
							{
								// close up the extra parenthesis
								$sql .= ") \n";
							}
						}
						else
						{
							$search_term = (sizeof($terms) == 1) ? $terms_like[0] : $DB->escape_like_str($this->keywords);	
							$sql .= "\nOR (exp_weblog_data.field_id_".$val." LIKE '%".$search_term."%' ";
							
							// and close up the member clause
							if ($member_ids != '')
							{
								$sql .= " AND (exp_weblog_titles.author_id {$member_ids})) \n";
							}
							else
							{
								// close up the extra parenthesis
								$sql .= ") \n";
							}
						}
					}
				}
			}
			
			/** ----------------------------------
			/**  Search in Comments
			/** ----------------------------------*/
			
			if (isset($_POST['search_in']) AND $_POST['search_in'] == 'everywhere')
			{
				if (sizeof($terms) == 1 && isset($_POST['where']) && $_POST['where'] == 'word')
				{
					$sql .= " OR (exp_comments.comment LIKE '% ".$terms_like['0']." %' ";
					
					// and close up the member clause
					if ($member_ids != '')
					{
						$sql .= " AND (exp_comments.author_id {$member_ids})) \n";
					}
					else
					{
						// close up the extra parenthesis
						$sql .= ") \n";
					}
				}
				elseif ( ! isset($_POST['exact_keyword']))
				{
					$mysql_function	= (substr($terms['0'], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';    
					$search_term	= (substr($terms['0'], 0,1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];
					
					// We have three parentheses in the beginning in case
					// there are any NOT LIKE's being used and to allow a member clause
					$sql .= "\nOR (((exp_comments.comment $mysql_function '%".$search_term."%' ";
					
					for ($i=1; $i < sizeof($terms); $i++) 
					{
						$mysql_criteria	= ($mysql_function == 'NOT LIKE' OR substr($terms[$i], 0,1) == '-') ? $not_and : $criteria;
						$mysql_function	= (substr($terms[$i], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';
						$search_term	= (substr($terms[$i], 0,1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];
					
						$sql .= "$mysql_criteria exp_comments.comment $mysql_function '%".$search_term."%' ";
					}
				
					$sql .= ")) ";
					
					// and close up the member clause
					if ($member_ids != '')
					{
						$sql .= " AND (exp_comments.author_id {$member_ids})) \n";
					}
					else
					{
						// close up the extra parenthesis
						$sql .= ") \n";
					}
				}
				else
				{
					$search_term = (sizeof($terms) == 1) ? $terms_like[0] : $DB->escape_like_str($this->keywords);	
					$sql .= " OR ((exp_comments.comment LIKE '%".$search_term."%') ";
					
					// and close up the member clause
					if ($member_ids != '')
					{
						$sql .= " AND (exp_comments.author_id {$member_ids})) \n";
					}
					else
					{
						// close up the extra parenthesis
						$sql .= ") \n";
					}
				}
			}
			
			// So it ends
			$sql .= ") \n";
		}
		else
		{
			// there are no keywords at all.  Do we still need a member search?
			if ($member_ids != '')
			{
				
				$sql .= "AND (exp_weblog_titles.author_id {$member_ids} ";
				
				// searching comments too?
				if (isset($_POST['search_in']) AND $_POST['search_in'] == 'everywhere')
				{
					$sql .= " OR exp_comments.author_id {$member_ids}";
				}
				
				$sql .= ")";
			}
		}
		//exit($sql);
		
        /** ----------------------------------------------
        /**  Limit query to a specific weblog
        /** ----------------------------------------------*/
                
        if (count($blog_array) > 0)
        {        
			$sql .= $id_query;
        }
        
        /** ----------------------------------------------
        /**  Limit query to a specific category
        /** ----------------------------------------------*/
                
        if (isset($_POST['cat_id']) AND is_array($_POST['cat_id']))
        {        
			$temp = '';
		
			foreach ($_POST['cat_id'] as $val)
			{
				if ($val != 'all' AND $val != '')
				{
					$temp .= " exp_categories.cat_id = '".$DB->escape_str($val)."' OR";
				}
			} 
			
			if ($temp != '')
			{
				$temp = substr($temp, 0, -2);
			
				$sql .= ' AND ('.$temp.') ';
			}
        }
        
        /** ----------------------------------------------
        /**  Are there results?
        /** ----------------------------------------------*/

		$query = $DB->query($sql);
					
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
		$this->num_rows = $query->num_rows;
	
        /** ----------------------------------------------
        /**  Set sort order
        /** ----------------------------------------------*/
	
		$order_by = ( ! isset($_POST['order_by'])) ? 'date' : $_POST['order_by'];
		$orderby = ( ! isset($_POST['orderby'])) ? $order_by : $_POST['orderby'];
	
		$end = '';
		
		switch ($orderby)
		{
			case 'most_comments'	:	$end .= " ORDER BY comment_total ";
				break;
			case 'recent_comment'	:	$end .= " ORDER BY recent_comment_date ";
				break;
			case 'title'			:	$end .= " ORDER BY title ";
				break;
			default					:	$end .= " ORDER BY entry_date ";
				break;
		}
	
		$order = ( ! isset($_POST['sort_order'])) ? 'desc' : $_POST['sort_order'];
		
		if ($order != 'asc' AND $order != 'desc')
			$order = 'desc';
			
		$end .= " ".$order;
       	
       	$sql = "SELECT DISTINCT(t.entry_id), t.entry_id, t.weblog_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.dst_enabled, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.allow_trackbacks, t.sticky, t.entry_date, t.year, t.month, t.day, t.entry_date, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.trackback_total, t.sent_trackbacks, t.recent_trackback_date, t.site_id as entry_site_id,
						w.blog_title, w.blog_name, w.search_results_url, w.search_excerpt, w.blog_url, w.comment_url, w.tb_return_url, w.comment_moderate, w.weblog_html_formatting, w.weblog_allow_img_urls, w.weblog_auto_link_urls, w.enable_trackbacks, w.trackback_use_url_title, w.trackback_field, w.trackback_use_captcha, w.trackback_system_enabled, 
						m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
						md.*,
						wd.*
				FROM exp_weblog_titles		AS t
				LEFT JOIN exp_weblogs 		AS w  ON t.weblog_id = w.weblog_id 
				LEFT JOIN exp_weblog_data	AS wd ON t.entry_id = wd.entry_id 
				LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id 
				LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id 
				WHERE t.entry_id IN (";
        
        foreach ($query->result as $row)
        {
        	$sql .= $row['entry_id'].',';
        }
        
		$sql = substr($sql, 0, -1).') '.$end;        
		
		return $sql;
	}
	/* END */




    /** ----------------------------------------
    /**  Total search results
    /** ----------------------------------------*/
	
	function total_results()
	{
		global $IN, $DB;
        
        /** ----------------------------------------
        /**  Check search ID number
        /** ----------------------------------------*/
        
        // If the QSTR variable is less than 32 characters long we
        // don't have a valid search ID number
        
        if (strlen($IN->QSTR) < 32)
        {
			return '';
        }        
                        
        /** ----------------------------------------
        /**  Fetch ID number and page number
        /** ----------------------------------------*/
        
		$search_id = substr($IN->QSTR, 0, 32);

        /** ----------------------------------------
        /**  Fetch the cached search query
        /** ----------------------------------------*/
			        
		$query = $DB->query("SELECT total_results FROM exp_search WHERE search_id = '".$DB->escape_str($search_id)."'");

		if ($query->num_rows == 1)
		{
			return $query->row['total_results'];
		}
		else
		{
			return 0;
		}
	}
	/* END */
	
	

    /** ----------------------------------------
    /**  Search keywords
    /** ----------------------------------------*/
	
	function keywords()
	{
		global $IN, $DB, $REGX;
        
        /** ----------------------------------------
        /**  Check search ID number
        /** ----------------------------------------*/
        
        // If the QSTR variable is less than 32 characters long we
        // don't have a valid search ID number
        
        if (strlen($IN->QSTR) < 32)
        {
			return '';
        }        
                        
        /** ----------------------------------------
        /**  Fetch ID number and page number
        /** ----------------------------------------*/
        
		$search_id = substr($IN->QSTR, 0, 32);

        /** ----------------------------------------
        /**  Fetch the cached search query
        /** ----------------------------------------*/
			        
		$query = $DB->query("SELECT keywords FROM exp_search WHERE search_id = '".$DB->escape_str($search_id)."'");

		if ($query->num_rows == 1)
		{
			return $REGX->encode_ee_tags($REGX->xml_convert($query->row['keywords']));
		}
		else
		{
			return '';
		}
	}
	/* END */


    /** ----------------------------------------
    /**  Show search results
    /** ----------------------------------------*/
	
	function search_results()
	{
		global $IN, $DB, $TMPL, $LANG, $FNS, $OUT, $LOC, $PREFS, $REGX;

        /** ----------------------------------------
        /**  Fetch the search language file
        /** ----------------------------------------*/
        
        $LANG->fetch_language_file('search');
        
        /** ----------------------------------------
        /**  Check search ID number
        /** ----------------------------------------*/
        
        // If the QSTR variable is less than 32 characters long we
        // don't have a valid search ID number
        
        if (strlen($IN->QSTR) < 32)
        {
            return $OUT->show_user_error('off', array($LANG->line('search_no_result')), $LANG->line('search_result_heading'));        
        }        
                
        /** ----------------------------------------
        /**  Clear old search results
        /** ----------------------------------------*/

		$expire = time() - ($this->cache_expire * 3600);
		
		$DB->query("DELETE FROM exp_search WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND search_date < '$expire'");
        
        /** ----------------------------------------
        /**  Fetch ID number and page number
        /** ----------------------------------------*/
        
        // We cleverly disguise the page number in the ID hash string
                
        $cur_page = 0;
        
        if (strlen($IN->QSTR) == 32)
        {
        	$search_id = $IN->QSTR;
        }
        else
        {
			$search_id = substr($IN->QSTR, 0, 32);
			$cur_page  = substr($IN->QSTR, 32);
        }

        /** ----------------------------------------
        /**  Fetch the cached search query
        /** ----------------------------------------*/
			        
		$query = $DB->query("SELECT * FROM exp_search WHERE search_id = '".$DB->escape_str($search_id)."'");
        
		if ($query->num_rows == 0 OR $query->row['total_results'] == 0)
		{
            return $OUT->show_user_error('off', array($LANG->line('search_no_result')), $LANG->line('search_result_heading'));        
		}
		
		$fields = ($query->row['custom_fields'] == '') ? array() : unserialize(stripslashes($query->row['custom_fields']));
        $sql 	= unserialize(stripslashes($query->row['query']));
        $sql	= str_replace('MDBMPREFIX', 'exp_', $sql);
        
        $per_page = $query->row['per_page'];
        $res_page = $query->row['result_page'];
        
        /** ----------------------------------------
        /**  Run the search query
        /** ----------------------------------------*/
                
        $query = $DB->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT COUNT(*) AS count FROM ', $sql));
        
		if ($query->row['count'] == 0)
		{
            return $OUT->show_user_error('off', array($LANG->line('search_no_result')), $LANG->line('search_result_heading'));        
		}
        
        /** ----------------------------------------
        /**  Calculate total number of pages
        /** ----------------------------------------*/
			
		$current_page =  ($cur_page / $per_page) + 1;
			
        $total_pages = intval($query->row['count'] / $per_page);
        
        if ($query->row['count'] % $per_page) 
        {
            $total_pages++;
        }		
        
		$page_count = $LANG->line('page').' '.$current_page.' '.$LANG->line('of').' '.$total_pages;
		
		/** -----------------------------
    	/**  Do we need pagination?
    	/** -----------------------------*/
		
		// If so, we'll add the LIMIT clause to the SQL statement and run the query again
				
		$pager = ''; 		
		
		if ($query->row['count'] > $per_page)
		{ 											
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}

			$PGR = new Paginate();
						
			$PGR->path			= $FNS->create_url($res_page.'/'.$search_id, 0, 0);
			$PGR->total_count 	= $query->row['count'];
			$PGR->per_page		= $per_page;
			$PGR->cur_page		= $cur_page;
			
			$pager = $PGR->show_links();			
			 
			$sql .= " LIMIT ".$cur_page.", ".$per_page;    
		}
		
		$query = $DB->query($sql);
		
		$output = '';
		
		if ( ! class_exists('Weblog'))
        {
        	require PATH_MOD.'/weblog/mod.weblog'.EXT;
        }
        
        unset($TMPL->var_single['auto_path']);
        unset($TMPL->var_single['excerpt']);
        unset($TMPL->var_single['id_auto_path']);
        unset($TMPL->var_single['full_text']);
        unset($TMPL->var_single['switch']);
        
        foreach($TMPL->var_single as $key => $value)
        {
        	if (substr($key, 0, strlen('member_path')) == 'member_path')
        	{
        		unset($TMPL->var_single[$key]);
        	}
        }

       	$weblog = new Weblog;        

		// This allows the weblog {absolute_count} variable to work
       	$weblog->p_page = ($per_page * $current_page) - $per_page;

       	$weblog->fetch_custom_weblog_fields();
        $weblog->fetch_custom_member_fields();
        $weblog->query = $DB->query($sql);
        
       	if ($weblog->query->num_rows == 0)
        {
        	return $TMPL->no_results();
        }
        
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $weblog->TYPE = new Typography;
        $weblog->TYPE->convert_curly = FALSE;
        $weblog->TYPE->encode_email = FALSE;
        
        $weblog->fetch_categories();
        $weblog->parse_weblog_entries();
        
        $tagdata = $TMPL->tagdata;

		// Does the tag contain "related entries" that we need to parse out?

		if (count($TMPL->related_data) > 0 AND count($weblog->related_entries) > 0)
		{
			$weblog->parse_related_entries();
		}
		
		if (count($TMPL->reverse_related_data) > 0 AND count($weblog->reverse_related_entries) > 0)
		{
			$weblog->parse_reverse_related_entries();
		}
        		
		$output = $weblog->return_data;
		
		$TMPL->tagdata = $tagdata;
		
		/** -----------------------------
		/**  Fetch member path variable
		/** -----------------------------*/
		
		// We do it here in case it's used in multiple places.
		
		$m_paths = array();
		
		if (preg_match_all("/".LD."member_path(\s*=.*?)".RD."/s", $TMPL->tagdata, $matches))
		{ 
			for ($j = 0; $j < count($matches['0']); $j++)
			{        	
				$m_paths[] = array($matches['0'][$j], $FNS->extract_path($matches['1'][$j]));
			}
		}
		
		/** -----------------------------
		/**  Fetch switch param
		/** -----------------------------*/
		
		$switch1 = '';
		$switch2 = '';
		
		if ($switch = $TMPL->fetch_param('switch'))
		{
			if (strpos($switch, '|') !== FALSE)
			{
				$x = explode("|", $switch);
				
				$switch1 = $x['0'];
				$switch2 = $x['1'];
			}
			else
			{
				$switch1 = $switch;
			}
		}	
		
		/** -----------------------------
		/**  Result Loop - Legacy!
		/** -----------------------------*/
		
		$i = 0;
		
        foreach ($query->result as $row)
        {
			if (isset($row['field_id_'.$row['search_excerpt']]) AND $row['field_id_'.$row['search_excerpt']])
			{
				$format = ( ! isset($row['field_ft_'.$row['search_excerpt']])) ? 'xhtml' : $row['field_ft_'.$row['search_excerpt']];
			
				$full_text = $weblog->TYPE->parse_type(strip_tags($row['field_id_'.$row['search_excerpt']]), 
														array(
																'text_format'   => $format,
																'html_format'   => 'safe',
																'auto_links'    => 'y',
																'allow_img_url' => 'n'
														    ));
														    
				$excerpt = strip_tags($full_text);
				$excerpt = trim(preg_replace("/(\015\012)|(\015)|(\012)/", " ", $excerpt));    
				$excerpt = $FNS->word_limiter($excerpt, 50);
			}
			else
			{
				$excerpt = '';
				$full_text = '';
			}
			
			// Parse permalink path
												
			$url = ($row['search_results_url'] != '') ? $row['search_results_url'] : $row['blog_url'];		
						
			$path = $FNS->remove_double_slashes($REGX->prep_query_string($url).'/'.$row['url_title'].'/');
			$idpath = $FNS->remove_double_slashes($REGX->prep_query_string($url).'/'.$row['entry_id'].'/');
						
			$switch = ($i++ % 2) ? $switch1 : $switch2;
			$output = preg_replace("/".LD.'switch'.RD."/", $switch, $output, sizeof(explode(LD.'switch'.RD, $TMPL->tagdata)) - 1);
			$output = preg_replace("/".LD.'auto_path'.RD."/", $path, $output, sizeof(explode(LD.'auto_path'.RD, $TMPL->tagdata)) - 1);
			$output = preg_replace("/".LD.'id_auto_path'.RD."/", $idpath, $output, sizeof(explode(LD.'id_auto_path'.RD, $TMPL->tagdata)) - 1);
			$output = preg_replace("/".LD.'excerpt'.RD."/", preg_quote($excerpt), $output, sizeof(explode(LD.'excerpt'.RD, $TMPL->tagdata)) - 1);
			$output = preg_replace("/".LD.'full_text'.RD."/", preg_quote($full_text), $output, sizeof(explode(LD.'full_text'.RD, $TMPL->tagdata)) - 1);
        
        	// Parse member_path
			
			if (count($m_paths) > 0)
			{
				foreach ($m_paths as $val)
				{					
					$output = preg_replace("/".$val['0']."/", $FNS->create_url($val['1'].'/'.$row['member_id']), $output, 1);
				}
			}
        
        }
        
        
        $TMPL->tagdata = $output;
        
		/** ----------------------------------------
		/**  Parse variables
		/** ----------------------------------------*/
		
		$swap = array(
						'lang:total_search_results'	=>	$LANG->line('search_total_results'),
						'lang:search_engine'		=>	$LANG->line('search_engine'),
						'lang:search_results'		=>	$LANG->line('search_results'),
						'lang:search'				=>	$LANG->line('search'),
						'lang:title'				=>	$LANG->line('search_title'),
						'lang:weblog'				=>	$LANG->line('search_weblog'),
						'lang:excerpt'				=>	$LANG->line('search_excerpt'),
						'lang:author'				=>	$LANG->line('search_author'),
						'lang:date'					=>	$LANG->line('search_date'),
						'lang:total_comments'		=>	$LANG->line('search_total_comments'),
						'lang:recent_comments'		=>	$LANG->line('search_recent_comment_date'),
						'lang:keywords'				=>	$LANG->line('search_keywords')
					);
	
		$TMPL->template = $FNS->var_swap($TMPL->template, $swap);

		/** ----------------------------------------
		/**  Add Pagination
		/** ----------------------------------------*/

		if ($pager == '')
		{
			$TMPL->template = preg_replace("/".LD."if paginate".RD.".*?".LD."&#47;if".RD."/s", '', $TMPL->template);
		}
		else
		{
			$TMPL->template = preg_replace("/".LD."if paginate".RD."(.*?)".LD."&#47;if".RD."/s", "\\1", $TMPL->template);
		}

		$TMPL->template = str_replace(LD.'paginate'.RD, $pager, $TMPL->template);
		$TMPL->template = str_replace(LD.'page_count'.RD, $page_count, $TMPL->template);

        return stripslashes($TMPL->tagdata);
	}
	/* END */




    /** ----------------------------------------
    /**  Simple Search Form
    /** ----------------------------------------*/

    function simple_form()
    {
        global $IN, $FNS, $PREFS, $TMPL, $DB, $LANG;
        
        /** ----------------------------------------
        /**  Create form
        /** ----------------------------------------*/

        $result_page = ( ! $TMPL->fetch_param('result_page')) ? 'search/results' : $TMPL->fetch_param('result_page');
        
        $data['hidden_fields'] = array(
										'ACT'					=> $FNS->fetch_action_id('Search', 'do_search'),
										'XID'					=> '',
										'RP'					=> $result_page,
										'NRP'					=> ($TMPL->fetch_param('no_result_page')) ? $TMPL->fetch_param('no_result_page') : '',
										'RES'					=> $TMPL->fetch_param('results'),
										'status'				=> $TMPL->fetch_param('status'),
										'weblog'				=> $TMPL->fetch_param('weblog'),
										'search_in'				=> $TMPL->fetch_param('search_in'),
										'where'					=> ( ! $TMPL->fetch_param('where')) ? 'all' : $TMPL->fetch_param('where')
										);
										
										
		if ($TMPL->fetch_param('show_expired') !== FALSE)
		{
			$data['hidden_fields']['show_expired'] = $TMPL->fetch_param('show_expired');
		}
		
		if ($TMPL->fetch_param('show_future_entries') !== FALSE)
		{
			$data['hidden_fields']['show_future_entries'] = $TMPL->fetch_param('show_future_entries');
		}      
		
		if ($TMPL->fetch_param('name') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $TMPL->fetch_param('name')))
		{
			$data['name'] = $TMPL->fetch_param('name');
		} 
		
		if ($TMPL->fetch_param('id') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $TMPL->fetch_param('id')))
		{
			$data['id'] = $TMPL->fetch_param('id');
		} 
                             
        $res  = $FNS->form_declaration($data);
                
        $res .= stripslashes($TMPL->tagdata);
        
        $res .= "</form>"; 

        return $res;
	}
	/* END */



    /** ----------------------------------------
    /**  Advanced Search Form
    /** ----------------------------------------*/

    function advanced_form()
    {
        global $IN, $FNS, $PREFS, $TMPL, $DB, $LANG, $REGX;
        
        
        $LANG->fetch_language_file('search');
        
		/** ----------------------------------------
		/**  Fetch weblogs and categories
		/** ----------------------------------------*/
        
        // First we need to grab the name/ID number of all weblogs and categories
		
		$sql = "SELECT blog_title, weblog_id, cat_group FROM exp_weblogs WHERE ";
								
        if (USER_BLOG !== FALSE)
        {
            // If it's a "user blog" we limit to only their assigned blog
        
            $sql .= "exp_weblogs.weblog_id = '".UB_BLOG_ID."' ";
        }
        else
        {
            $sql .= "exp_weblogs.is_user_blog = 'n' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";
        
            if ($weblog = $TMPL->fetch_param('weblog'))
            {
                $xql = "SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";
            
                $xql .= $FNS->sql_andor_string($weblog, 'blog_name');        
                    
                $query = $DB->query($xql);
                
                if ($query->num_rows > 0)
                {
                    if ($query->num_rows == 1)
                    {
                        $sql .= "AND weblog_id = '".$query->row['weblog_id']."' ";
                    }
                    else
                    {
                        $sql .= "AND (";
                        
                        foreach ($query->result as $row)
                        {
                            $sql .= "weblog_id = '".$row['weblog_id']."' OR ";
                        }
                        
                        $sql = substr($sql, 0, - 3);
                        
                        $sql .= ") ";
                    }
                }
            }
        }
                  
		$sql .= " ORDER BY blog_title";
		
		$query = $DB->query($sql);
				
		foreach ($query->result as $row)
		{
			$this->blog_array[$row['weblog_id']] = array($row['blog_title'], $row['cat_group']);
		}        
	
		$nested = ($TMPL->fetch_param('cat_style') !== FALSE && $TMPL->fetch_param('cat_style') == 'nested') ? 'y' : 'n';
		
		$order  = ($nested == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';
		
		$extra = '';
		
		/**  Typically, I would worry about orphaned categories a bit, like in the Weblog module with
		/**  the show="" parameter, but my reasoning is that if someone does not want to show a 
		/**  category then a consequence is that one will not see any of that category's children as well.
		/**  Futher, if someone decides to show a child but NOT its parent, then they are a nutter and
		/**  we do not suffer nutters here at EllisLab, no siree.   -Paul
		  */
		
		if (($categories = $TMPL->fetch_param('category')) !== FALSE)
		{
			$extra = $FNS->sql_andor_string($categories, 'cat_id', 'exp_categories');
		}
	
        $sql = "SELECT exp_categories.group_id, exp_categories.parent_id, exp_categories.cat_id, exp_categories.cat_name 
                FROM exp_categories, exp_category_groups
                WHERE exp_category_groups.group_id = exp_categories.group_id
                {$extra}
                AND exp_category_groups.is_user_blog = 'n'
                ORDER BY {$order}";
        
        $query = $DB->query($sql);
        
        if ($query->num_rows > 0)
        {
        	$categories = array();
        	
			foreach ($query->result as $row)
			{			
				$categories[] = array($row['group_id'], $row['cat_id'], $REGX->entities_to_ascii($row['cat_name']), $row['parent_id']);
			}
			
			if ($nested == 'y')
			{
				foreach($categories as $key => $val)
				{
					if (0 == $val['3']) 
					{
						$this->cat_array[] = array($val['0'], $val['1'], $val['2']);
						$this->category_subtree($val['1'], $categories, $depth=1);
					}
				}
			}
			else
			{
				$this->cat_array = $categories;
			}
		}					
                
		/** ----------------------------------------
		/**  Build select list
		/** ----------------------------------------*/
        
        $weblog_names = "<option value=\"null\" selected=\"selected\">".$LANG->line('search_any_weblog')."</option>\n";
         
		foreach ($this->blog_array as $key => $val)
		{
			$weblog_names .= "<option value=\"".$key."\">".$REGX->form_prep($val['0'])."</option>\n";
		}
                
   
        $tagdata = $TMPL->tagdata; 
        
		/** ----------------------------------------
		/**  Parse variables
		/** ----------------------------------------*/
		
		$swap = array(
						'lang:search_engine'				=>	$LANG->line('search_engine'),
						'lang:search'						=>	$LANG->line('search'),
						'lang:search_by_keyword'			=>	$LANG->line('search_by_keyword'),
						'lang:search_in_titles'				=>	$LANG->line('search_in_titles'),
						'lang:search_in_entries'			=>	$LANG->line('search_entries'),
						'lang:search_everywhere'			=>	$LANG->line('search_everywhere'),
						'lang:search_by_member_name'		=>	$LANG->line('search_by_member_name'),
						'lang:exact_name_match'				=>	$LANG->line('search_exact_name_match'),
						'lang:exact_phrase_match'			=>	$LANG->line('search_exact_phrase_match'),
						'lang:also_search_comments'			=>	$LANG->line('search_also_search_comments'),
						'lang:any_date'						=>	$LANG->line('search_any_date'),
						'lang:today_and'					=>	$LANG->line('search_today_and'),
						'lang:this_week_and'				=>	$LANG->line('search_this_week_and'),
						'lang:one_month_ago_and'			=>	$LANG->line('search_one_month_ago_and'),
						'lang:three_months_ago_and'			=>	$LANG->line('search_three_months_ago_and'),
						'lang:six_months_ago_and'			=>	$LANG->line('search_six_months_ago_and'),
						'lang:one_year_ago_and'				=>	$LANG->line('search_one_year_ago_and'),
						'lang:weblogs'						=>	$LANG->line('search_weblogs'),
						'lang:categories'					=>	$LANG->line('search_categories'),
						'lang:newer'						=>	$LANG->line('search_newer'),
						'lang:older'						=>	$LANG->line('search_older'),
						'lang:sort_results_by'				=>	$LANG->line('search_sort_results_by'),
						'lang:date'							=>	$LANG->line('search_date'),
						'lang:title'						=>	$LANG->line('search_title'),
						'lang:most_comments'				=>	$LANG->line('search_most_comments'),
						'lang:recent_comment'				=>	$LANG->line('search_recent_comment'),
						'lang:descending'					=>	$LANG->line('search_descending'),
						'lang:ascending'					=>	$LANG->line('search_ascending'),
						'lang:search_entries_from'			=>	$LANG->line('search_entries_from'),
						'lang:any_category'					=>	$LANG->line('search_any_category'),
						'lang:search_any_words'				=>	$LANG->line('search_any_words'),
						'lang:search_all_words'				=>	$LANG->line('search_all_words'),
						'lang:search_exact_word'			=>	$LANG->line('search_exact_word'),
						'weblog_names' 						=>	$weblog_names
					);
	
		
		$tagdata = $FNS->var_swap($tagdata, $swap);
		
		$TMPL->template = $FNS->var_swap($TMPL->template, $swap);
        
        /** ----------------------------------------
        /**  Create form
        /** ----------------------------------------*/
                
        $result_page = ( ! $TMPL->fetch_param('result_page')) ? 'search/results' : $TMPL->fetch_param('result_page');
         
		$data['id'] = 'searchform';
        $data['hidden_fields'] = array(
										'ACT'					=> $FNS->fetch_action_id('Search', 'do_search'),
										'XID'					=> '',
										'RP'					=> $result_page,
										'NRP'					=> ($TMPL->fetch_param('no_result_page')) ? $TMPL->fetch_param('no_result_page') : '',
										'RES'					=> $TMPL->fetch_param('results'),
										'status'				=> $TMPL->fetch_param('status'),
										'search_in'				=> $TMPL->fetch_param('search_in')
									  );                              
									  
 		if ($TMPL->fetch_param('weblog') != '')
 		{
 			$data['hidden_fields']['weblog'] = $TMPL->fetch_param('weblog');
 		}
        
        if ($TMPL->fetch_param('show_expired') !== FALSE)
		{
			$data['hidden_fields']['show_expired'] = $TMPL->fetch_param('show_expired');
		}
		
		if ($TMPL->fetch_param('show_future_entries') !== FALSE)
		{
			$data['hidden_fields']['show_future_entries'] = $TMPL->fetch_param('show_future_entries');
		} 
		
		if ($TMPL->fetch_param('name') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $TMPL->fetch_param('name')))
		{
			$data['name'] = $TMPL->fetch_param('name');
		} 
		
		if ($TMPL->fetch_param('id') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $TMPL->fetch_param('id')))
		{
			$data['id'] = $TMPL->fetch_param('id');
		} 
        
        $res  = $FNS->form_declaration($data);
        
        $res .= $this->search_js_switcher($nested, $data['id']);
        
        $res .= stripslashes($tagdata);
        
        $res .= "</form>"; 

        return $res;
    }
    /* END */



    /** ----------------------------------------
    /**  JavaScript weblog/category switch code
    /** ----------------------------------------*/

	function search_js_switcher($nested='n', $id='searchform')
	{
		global $LANG;
		        		
		ob_start();
?>
<script type="text/javascript">
//<![CDATA[

var firstcategory = 1;
var firststatus = 1;

function changemenu(index)
{ 

	var categories = new Array();
	
	var i = firstcategory;
	var j = firststatus;
	
	var theSearchForm = false
	
	if (document.searchform)
	{
		theSearchForm = document.searchform;
	}
	else if (document.getElementById('<?php echo $id; ?>'))
	{
		theSearchForm = document.getElementById('<?php echo $id; ?>');
	}
	
	if (theSearchForm.elements['weblog_id'])
	{
		var weblog_obj = theSearchForm.elements['weblog_id'];
	}
	else
	{
		var weblog_obj = theSearchForm.elements['weblog_id[]'];
	}
	
	var blogs = weblog_obj.options[index].value;
	
	var reset = 0;

	for (var g = 0; g < weblog_obj.options.length; g++)
	{
		if (weblog_obj.options[g].value != 'null' && 
			weblog_obj.options[g].selected == true)
		{
			reset++;
		}
	} 
  
	with (theSearchForm.elements['cat_id[]'])
	{	<?php
						
		foreach ($this->blog_array as $key => $val)
		{
		
		?>
		
		if (blogs == "<?php echo $key ?>")
		{	<?php echo "\n";
			if (count($this->cat_array) > 0)
			{
            	$last_group = 0;

                foreach ($this->cat_array as $k => $v)
                {
                    if (in_array($v['0'], explode('|', $val['1'])))
                    {

                    	if ($last_group == 0 OR $last_group != $v['0'])
                    	{?>
            categories[i] = new Option("-------", ""); i++; <?php echo "\n";
            				$last_group = $v['0'];
                    	}

            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            categories[i] = new Option("<?php echo addslashes($v['2']);?>", "<?php echo $v['1'];?>"); i++; <?php echo "\n";
                    }
                }
			}
			  
			?>

		} // END if blogs
			
		<?php
		 
		} // END OUTER FOREACH
		 
		?> 
								
		if (reset > 1)
		{
			 categories = new Array();
		}

		spaceString = eval("/!-!/g");
		
		with (theSearchForm.elements['cat_id[]'])
		{
			for (i = length-1; i >= firstcategory; i--)
				options[i] = null;
			
			for (i = firstcategory; i < categories.length; i++)
			{
				options[i] = categories[i];
				options[i].text = options[i].text.replace(spaceString, String.fromCharCode(160));
			}
			
			options[0].selected = true;
		}
		
	}
}

//]]>
</script>
	
		<?php
	
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
	
	
		return $buffer;
	}
	/* END */
	
	
	/** --------------------------------
    /**  Category Sub-tree
    /** --------------------------------*/
	function category_subtree($cat_id, $categories, $depth)
    {
        global $DSP, $IN, $DB, $REGX, $LANG;

        $spcr = '!-!';
                  
        $indent = $spcr.$spcr.$spcr.$spcr;
    
        if ($depth == 1)	
        {
            $depth = 4;
        }
        else 
        {	                            
            $indent = str_repeat($spcr, $depth).$indent;
            
            $depth = $depth + 4;
        }
        
        $sel = '';
            
        foreach ($categories as $key => $val) 
        {
            if ($cat_id == $val['3']) 
            {
                $pre = ($depth > 2) ? $spcr : '';
                
              	$this->cat_array[] = array($val['0'], $val['1'], $pre.$indent.$spcr.$val['2']);
                                
                $this->category_subtree($val['1'], $categories, $depth);
            }
        }
    }
    /* END */




}
// END CLASS
?>