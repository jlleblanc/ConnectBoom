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
 File: mod.weblog.php
-----------------------------------------------------
 Purpose: Weblog class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Weblog {

    var $limit	= '100';   // Default maximum query results if not specified.

  	// These variable are all set dynamically
  	
    var $query;
    var $TYPE;  
    var $entry_id				= '';
    var	$uri					= '';
    var $uristr					= '';
    var $return_data    		= '';     	// Final data 
    var $tb_action_id   		= '';
	var $basepath				= '';
	var $hit_tracking_id		= FALSE;
    var	$sql					= FALSE;
    var $display_tb_rdf			= FALSE;
    var $cfields        		= array();
    var $dfields				= array();
    var $rfields				= array();
    var $mfields        		= array();
    var $categories     		= array();
	var $catfields				= array();
    var $weblog_name     		= array();
    var $weblogs_array			= array();
    var $related_entries		= array();
    var $reverse_related_entries= array();
    var $reserved_cat_segment 	= '';
	var $use_category_names		= FALSE;
	var $dynamic_sql			= FALSE;
	var $tb_captcha_hash		= '';
	var $cat_request			= FALSE;
	var $enable					= array();	// modified by various tags with disable= parameter

    // These are used with the nested category trees
    
    var $category_list  		= array();
	var $cat_full_array			= array();
	var $cat_array				= array();
	var $temp_array				= array();
	var $category_count			= 0;   

	// Pagination variables
	
    var $paginate				= FALSE;
	var $field_pagination		= FALSE;
    var $paginate_data			= '';
    var $pagination_links		= '';
    var $page_next				= '';
    var $page_previous			= '';
	var $current_page			= 1;
	var $total_pages			= 1;
	var $multi_fields			= array();
	var $display_by				= '';
	var $total_rows				=  0;
	var $pager_sql				= '';
	var $p_limit				= '';
	var $p_page					= '';

	
	// SQL Caching
	
	var $sql_cache_dir			= 'sql_cache/';
	
	// Misc. - Class variable usable by extensions
	var $misc					= FALSE;

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Weblog()
    { 
		global $PREFS, $IN, $TMPL;
				
		$this->p_limit = $this->limit;
		
		$this->QSTR = ($IN->Pages_QSTR != '') ? $IN->Pages_QSTR : $IN->QSTR;
		
		if ($PREFS->ini("use_category_name") == 'y' AND $PREFS->ini("reserved_category_word") != '')
		{
			$this->use_category_names	= $PREFS->ini("use_category_name");
			$this->reserved_cat_segment	= $PREFS->ini("reserved_category_word");
		}
		
		// a number tags utilize the disable= parameter, set it here
		if (is_object($TMPL))
		{
			$this->_fetch_disable_param();			
		}
    }
    /* END */


    /** ----------------------------------------
    /**  Initialize values
    /** ----------------------------------------*/

    function initialize()
    {
        $this->sql 			= '';
        $this->return_data	= '';
    }
    /* END */
    
    
    /** ----------------------------------------
    /**  Fetch Cache
    /** ----------------------------------------*/

    function fetch_cache($identifier = '')
    {
    	global $IN, $TMPL;
    		    		
		$tag = ($identifier == '') ? $TMPL->tagproper : $TMPL->tagproper.$identifier;
		
		if ($TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{			
			foreach (explode('|', $TMPL->fetch_param('dynamic_parameters')) as $var)
			{			
				if (isset($_POST[$var]) AND in_array($var, array('weblog', 'entry_id', 'category', 'orderby', 'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from', 'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month', 'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'cat_limit', 'month_limit', 'offset', 'author_id')))
				{
					$tag .= $var.'="'.$_POST[$var].'"';
				}
				
				if (isset($_POST[$var]) && strncmp($var, 'search:', 7) == 0)
				{
					$tag .= $var.'="'.substr($_POST[$var], 7).'"';
				}
			}
		}
    	
		$cache_file = PATH_CACHE.$this->sql_cache_dir.md5($tag.$this->uri);
    
		if ( ! $fp = @fopen($cache_file, 'rb'))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_SH);
		$sql = @fread($fp, filesize($cache_file));
		flock($fp, LOCK_UN);
		fclose($fp);	
		
		return $sql;
    }
	/* END */
	
    /** ----------------------------------------
    /**  Save Cache
    /** ----------------------------------------*/

	function save_cache($sql, $identifier = '')
	{
		global $IN, $TMPL;
	
		$tag = ($identifier == '') ? $TMPL->tagproper : $TMPL->tagproper.$identifier;
	
		$cache_dir  = PATH_CACHE.$this->sql_cache_dir;
		$cache_file = $cache_dir.md5($tag.$this->uri);
			
		if ( ! @is_dir($cache_dir))
		{
			if ( ! @mkdir($cache_dir, 0777))
			{
				return FALSE;
			}
			
			if ($fp = @fopen($cache_dir.'/index.html', 'wb'))
			{
				fclose($fp);				
			}
			
			@chmod($cache_dir, 0777);            
		}	
		
		if ( ! $fp = @fopen($cache_file, 'wb'))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_EX);
		fwrite($fp, $sql);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($cache_file, 0777);
		
		return TRUE;
	}
	/* END */
	

    /** ----------------------------------------
    /**  Weblog entries
    /** ----------------------------------------*/

    function entries()
    {
        global $IN, $PREFS, $DB, $TMPL, $FNS;
        
        // If the "related_categories" mode is enabled
        // we'll call the "related_categories" function
        // and bail out.
        
        if ($TMPL->fetch_param('related_categories_mode') == 'on')
        {
        	return $this->related_entries();
        }
		// Onward...
                 
        $this->initialize();
        
		$this->uri = ($this->QSTR != '') ? $this->QSTR : 'index.php';
				 
        if ($this->enable['custom_fields'] == TRUE)
        {
        	$this->fetch_custom_weblog_fields();
        }
        
        if ($this->enable['member_data'] == TRUE)
        {
        	$this->fetch_custom_member_fields();
        }
        	
        if ($this->enable['pagination'] == TRUE)
        {
			$this->fetch_pagination_data();
		}
        
        $save_cache = FALSE;
        
        if ($PREFS->ini('enable_sql_caching') == 'y')
        {  
			if (FALSE == ($this->sql = $this->fetch_cache()))
			{        			
				$save_cache = TRUE;
			}
			else
			{
				if ($TMPL->fetch_param('dynamic') != 'off')
				{
					if (preg_match("#(^|\/)C(\d+)#", $this->QSTR, $match) OR in_array($this->reserved_cat_segment, explode("/", $this->QSTR)))
					{
						$this->cat_request = TRUE;
					}
				}
			}

			
			if (FALSE !== ($cache = $this->fetch_cache('pagination_count')))
			{
				if (FALSE !== ($this->fetch_cache('field_pagination')))
				{
					if (FALSE !== ($pg_query = $this->fetch_cache('pagination_query')))
					{
						$this->paginate = TRUE;
						$this->field_pagination = TRUE;
						$this->create_pagination(trim($cache), $DB->query(trim($pg_query)));  
					}
				}
				else
				{
					$this->create_pagination(trim($cache));        		
				}
			}
        }
        
        if ($this->sql == '')
        {
	        $this->build_sql_query();
    	}

        if ($this->sql == '')
        {
        	return $TMPL->no_results();
        }

        if ($save_cache == TRUE)
        {
			$this->save_cache($this->sql);
		}
                
        $this->query = $DB->query($this->sql);
                        
        if ($this->query->num_rows == 0)
        {
        	return $TMPL->no_results();
        }
        
		/* -------------------------------------
		/*  "Relaxed" View Tracking
		/*  
		/*  Some people have tags that are used to mimic a single-entry
		/*  page without it being dynamic. This allows Entry View Tracking
		/*  to work for ANY combination that results in only one entry
		/*  being returned by the tag, including weblog query caching.
		/*
		/*  Hidden Configuration Variable
		/*  - relaxed_track_views => Allow view tracking on non-dynamic
		/*  	single entries (y/n)
		/* -------------------------------------*/

		if ($PREFS->ini('relaxed_track_views') === 'y' AND $this->query->num_rows == 1)
		{
			$this->hit_tracking_id = $this->query->row['entry_id'];
		}

        $this->track_views();

        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $this->TYPE = new Typography;   
        $this->TYPE->convert_curly = FALSE;
          
        if ($this->enable['categories'] == TRUE)
        {
       		$this->fetch_categories();
       	}
        
        if ($this->enable['trackbacks'] == TRUE)
        {
        	$this->tb_action_id = $FNS->fetch_action_id('Trackback_CP', 'receive_trackback');
		}
		
        $this->parse_weblog_entries();
            
        if ($this->enable['pagination'] == TRUE)
        {
			$this->add_pagination_data();
		}
		
		// Does the tag contain "related entries" that we need to parse out?

		if (count($TMPL->related_data) > 0 AND count($this->related_entries) > 0)
		{
			$this->parse_related_entries();
		}
		
		if (count($TMPL->reverse_related_data) > 0 AND count($this->reverse_related_entries) > 0)
		{
			$this->parse_reverse_related_entries();
		}
                                
        return $this->return_data;        
    }
    /* END */


    /** ----------------------------------------
    /**  Process related entries
    /** ----------------------------------------*/

	function parse_related_entries()
	{
		global $TMPL, $DB, $REGX, $FNS;
		
		$sql = "SELECT rel_id, rel_parent_id, rel_child_id, rel_type, rel_data
				FROM exp_relationships 
				WHERE rel_id IN (";
		
		$templates = array();
		foreach ($this->related_entries as $val)
		{ 
			$x = explode('_', $val);
			$sql .= "'".$x['0']."',";
			$templates[] = array($x['0'], $x['1'], $TMPL->related_data[$x['1']]);
		}
				
		$sql = substr($sql, 0, -1).')';
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
			return;
		
		/* --------------------------------
		/*  Without this the Related Entries were inheriting the parameters of
		/*  the enclosing Weblog Entries tag.  Sometime in the future we will
		/*  likely allow Related Entries to have their own parameters
		/* --------------------------------*/ 
		
		$TMPL->tagparams = array('rdf'=> "off");
			
		$return_data = $this->return_data;
		
		foreach ($templates as $temp)
		{
			foreach ($query->result as $row)
			{
				if ($row['rel_id'] != $temp['0'])
					continue;
				
				/* --------------------------------------
				/*  If the data is emptied (cache cleared), then we 
				/*  rebuild it with fresh data so processing can continue.
				/* --------------------------------------*/
				
				if (trim($row['rel_data']) == '')
				{
					$rewrite = array(
									 'type'			=> $row['rel_type'],
									 'parent_id'	=> $row['rel_parent_id'],
									 'child_id'		=> $row['rel_child_id'],
									 'related_id'	=> $row['rel_id']
								);
		
					$FNS->compile_relationship($rewrite, FALSE);
					
					$results = $DB->query("SELECT rel_data FROM exp_relationships WHERE rel_id = '".$row['rel_id']."'");
					$row['rel_data'] = $results->row['rel_data'];
				}
				
				/** --------------------------------------
				/**  Begin Processing
				/** --------------------------------------*/
				
				$this->initialize();
				
				if ($reldata = @unserialize($row['rel_data']))
				{
					$TMPL->var_single	= $temp['2']['var_single'];
					$TMPL->var_pair		= $temp['2']['var_pair'];
					$TMPL->var_cond		= $temp['2']['var_cond'];
					$TMPL->tagdata		= $temp['2']['tagdata'];
		
					if ($row['rel_type'] == 'blog')
					{
						// Bug fix for when categories were not being inserted
						// correctly for related weblog entries.  Bummer.
						
						if (sizeof($reldata['categories'] == 0) && ! isset($reldata['cats_fixed']))
						{
							$fixdata = array(
											'type'			=> $row['rel_type'],
											'parent_id'		=> $row['rel_parent_id'],
											'child_id'		=> $row['rel_child_id'],
											'related_id'	=> $row['rel_id']
										);
						
							$FNS->compile_relationship($fixdata, FALSE);
							$reldata['categories'] = $FNS->cat_array;
							$reldata['category_fields'] = $FNS->catfields;
						}
					
						$this->query = $reldata['query'];
						$this->categories = array($this->query->row['entry_id'] => $reldata['categories']);
						
						if (isset($reldata['category_fields']))
						{
							$this->catfields = array($this->query->row['entry_id'] => $reldata['category_fields']);
						}

						$this->parse_weblog_entries();
						
						$marker = LD."REL[".$row['rel_id']."][".$temp['2']['field_name']."]".$temp['1']."REL".RD;
						$return_data = str_replace($marker, $this->return_data, $return_data);					
					}
					elseif ($row['rel_type'] == 'gallery')
					{									
						if ( ! class_exists('Gallery'))
						{
							include_once PATH_MOD.'gallery/mod.gallery'.EXT;
						}
						
						$GAL = new Gallery;
						$GAL->one_entry = TRUE;
						$GAL->query = $reldata['query'];
						$GAL->TYPE = $this->TYPE;
						$GAL->parse_gallery_tag();
						$GAL->parse_gallery_entries();

						$marker = LD."REL[".$row['rel_id']."][".$temp['2']['field_name']."]".$temp['1']."REL".RD;
						$return_data = str_replace($marker, $GAL->return_data, $return_data);
					}
				}
			}
		}
		
		$this->return_data = $return_data;
	}
	/* END */
	
	
	/** ----------------------------------------
    /**  Process reverse related entries
    /** ----------------------------------------*/

	function parse_reverse_related_entries()
	{
		global $TMPL, $DB, $REGX, $FNS;
		
		$sql = "SELECT rel_id, rel_parent_id, rel_child_id, rel_type, reverse_rel_data
				FROM exp_relationships
				WHERE rel_child_id IN ('".implode("','", array_keys($this->reverse_related_entries))."')
				AND rel_type = 'blog'";

		$query = $DB->query($sql);

		if ($query->num_rows == 0)
		{
			// remove Reverse Related tags for these entries
			
			foreach ($this->reverse_related_entries as $entry_id => $templates)
			{
				foreach($templates as $tkey => $template)
				{
					$this->return_data = str_replace(LD."REV_REL[".$TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $TMPL->reverse_related_data[$template]['no_rev_content'], $this->return_data);		
				}
			}

			return;
		}
		
		/** --------------------------------
		/**  Data Processing Time
		/** --------------------------------*/
		
		$entry_data = array();
		
		for ($i = 0, $total = count($query->result); $i < $total; $i++)
		{
    		$row = array_shift($query->result);

			/* --------------------------------------
			/*  If the data is emptied (cache cleared or first process), then we 
			/*  rebuild it with fresh data so processing can continue.
			/* --------------------------------------*/
			
			if (trim($row['reverse_rel_data']) == '')
			{
				$rewrite = array(
								 'type'			=> $row['rel_type'],
								 'parent_id'	=> $row['rel_parent_id'],
								 'child_id'		=> $row['rel_child_id'],
								 'related_id'	=> $row['rel_id']
							);
	
				$FNS->compile_relationship($rewrite, FALSE, TRUE);
				
				$results = $DB->query("SELECT reverse_rel_data FROM exp_relationships WHERE rel_parent_id = '".$row['rel_parent_id']."'");
				$row['reverse_rel_data'] = $results->row['reverse_rel_data'];
			}
			
			/** --------------------------------------
			/**  Unserialize the entries data, please
			/** --------------------------------------*/
			
			if ($revreldata = @unserialize($row['reverse_rel_data']))
			{
				$entry_data[$row['rel_child_id']][$row['rel_parent_id']] = $revreldata;
			}
		}
		
		/* --------------------------------
		/*  Without this the Reverse Related Entries were inheriting the parameters of
		/*  the enclosing Weblog Entries tag, which is not appropriate.
		/* --------------------------------*/ 
		
		$TMPL->tagparams = array('rdf'=> "off");
			
		$return_data = $this->return_data;
		
		foreach ($this->reverse_related_entries as $entry_id => $templates)
		{
			/** --------------------------------------
			/**  No Entries?  Remove Reverse Related Tags and Continue to Next Entry
			/** --------------------------------------*/
			
			if ( ! isset($entry_data[$entry_id]))
			{
				foreach($templates as $tkey => $template)
				{
					$return_data = str_replace(LD."REV_REL[".$TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $TMPL->reverse_related_data[$template]['no_rev_content'], $return_data);		
				}
				
				continue;
			}
			
			/** --------------------------------------
			/**  Process Our Reverse Related Templates
			/** --------------------------------------*/
			
			foreach($templates as $tkey => $template)
			{	
				$i = 0;
				$cats = array();
				
				$params = $TMPL->reverse_related_data[$template]['params'];
				
				if ( ! is_array($params))
				{
					$params = array('open');
				}
				elseif( ! isset($params['status']))
				{
					$params['status'] = 'open';
				}
				
				/** --------------------------------------
				/**  Entries have to be ordered, sorted and other stuff
				/** --------------------------------------*/
				
				$new	= array();
				$order	= ( ! isset($params['orderby'])) ? 'date' : $params['orderby'];
				$offset	= ( ! isset($params['offset']) OR ! is_numeric($params['offset'])) ? 0 : $params['offset'];
				$limit	= ( ! isset($params['limit']) OR ! is_numeric($params['limit'])) ? 100 : $params['limit'];
				$sort	= ( ! isset($params['sort']))	 ? 'asc' : $params['sort'];
				$random = ($order == 'random') ? TRUE : FALSE;
				
				$base_orders = array('random', 'date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date', 'entry_id', 
									 'view_count_one', 'view_count_two', 'view_count_three', 'view_count_four');
									
				$str_sort = array('title', 'url_title', 'username', 'screen_name');
	
				if ( ! in_array($order, $base_orders))
				{
					$set = 'n';
					foreach($this->cfields as $site_id => $cfields)
					{
						if ( isset($cfields[$order]))
						{
							$order = 'field_id_'.$cfields[$order]; 
							$set = 'y';
							break;
						}
					}
				
					if ( $set == 'n' )
					{
						$order = 'date';
					}
				}
				
				if ($order == 'date' OR $order == 'random') 
				{
					$order = 'entry_date';
				}
				
				if (isset($params['weblog']) && trim($params['weblog']) != '')
				{
					if (sizeof($this->weblogs_array) == 0)
					{
						$results = $DB->query("SELECT weblog_id, blog_name FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') AND is_user_blog = 'n'");
						
						foreach($results->result as $row)
						{
							$this->weblogs_array[$row['weblog_id']] = $row['blog_name'];
						}
					}
					
					$weblogs = explode('|', trim($params['weblog']));
					$allowed = array();
					
					if (strncmp($weblogs[0], 'not ', 4) == 0)
					{
						$weblogs['0'] = trim(substr($weblogs['0'], 3));
						$allowed	  = $this->weblogs_array;

						foreach($weblogs as $name)
						{
							if (in_array($name, $allowed))
							{
								foreach (array_keys($allowed, $name) AS $k)
								{
									unset($allowed[$k]);
								}
							}
						}
					}
					else
					{
						foreach($weblogs as $name)
						{
							if (in_array($name, $this->weblogs_array))
							{
								foreach (array_keys($this->weblogs_array, $name) AS $k)
								{
									$allowed[$k] = $name;
								}
							}
						}
					}
				}
				
				$stati = array();
				
				if (isset($params['status']) && trim($params['status']) != '')
				{	
					$stati	= explode('|', trim($params['status']));
					$status_state = 'positive';
					
					if (substr($stati['0'], 0, 4) == 'not ')
					{
						$stati['0'] = trim(substr($stati['0'], 3));
						$status_state = 'negative';
						$stati[] = 'closed';
					}
				}
				
				// lower case to match MySQL's case-insensitivity
				$stati = array_map('strtolower', $stati);
				
				$r = 1;  // Fixes a problem when a sorting key occurs twice

				foreach($entry_data[$entry_id] as $relating_data)
				{
					if ( ! isset($params['weblog']) OR array_key_exists($relating_data['query']->row['weblog_id'], $allowed))
					{
						$post_fix = ' '.$r;
					
						if (isset($stati) && ! empty($stati) && isset($relating_data['query']->row[$order]))
						{
							if ($status_state == 'negative' && ! in_array(strtolower($relating_data['query']->row['status']), $stati))
							{
								$new[$relating_data['query']->row[$order].$post_fix] = $relating_data;
							}
							elseif($status_state == 'positive' && in_array(strtolower($relating_data['query']->row['status']), $stati))
							{
								$new[$relating_data['query']->row[$order].$post_fix] = $relating_data;
							}
						}
						elseif (strtolower($relating_data['query']->row['status']) == 'open')
						{
							$new[$relating_data['query']->row[$order].$post_fix] = $relating_data;
						}
						
						++$r;
					}
				}
				
				// Note uksort($new, 'strnatcasecmp'); did not handle spaces well so used ksort instead
		
				if ($random === TRUE)
				{
					shuffle($new);
				}
				elseif ($sort == 'asc') // 1 to 10, A to Z
				{
					if (in_array($order, $str_sort))
					{
						ksort($new);
					}
					else
					{
						uksort($new, 'strnatcasecmp'); 
					}
				}
				else
				{
					if (in_array($order, $str_sort))
					{
						ksort($new);
					}
					else
					{
						uksort($new, 'strnatcasecmp'); 
					}
					
					$new = array_reverse($new, TRUE);
				}
				
				$output_data[$entry_id] = array_slice($new, $offset, $limit);
				
				if (sizeof($output_data[$entry_id]) == 0)
				{
					$return_data = str_replace(LD."REV_REL[".$TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $TMPL->reverse_related_data[$template]['no_rev_content'], $return_data);		
					continue;
				}
				
				/** --------------------------------------
				/**  Finally!  We get to process our parents
				/** --------------------------------------*/
				
				foreach($output_data[$entry_id] as $relating_data)
				{
					if ($i == 0)
					{
						$query = $FNS->clone_object($relating_data['query']); 
					}
					else
					{
						$query->result[] = $relating_data['query']->row;
					}
					
					$cats[$relating_data['query']->row['entry_id']] = $relating_data['categories'];
					
					++$i;
				}
				
				$query->num_rows = $i;
							
				$this->initialize();
				
				$TMPL->var_single	= $TMPL->reverse_related_data[$template]['var_single'];
				$TMPL->var_pair		= $TMPL->reverse_related_data[$template]['var_pair'];
				$TMPL->var_cond		= $TMPL->reverse_related_data[$template]['var_cond'];
				$TMPL->tagdata		= $TMPL->reverse_related_data[$template]['tagdata'];
													
				$this->query = $query;
				$this->categories = $cats;
				$this->parse_weblog_entries();
				
				$return_data = str_replace(	LD."REV_REL[".$TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, 
											$this->return_data, 
											$return_data);		
			}
		}
		
		$this->return_data = $return_data;
	}
	/* END */


    /** ----------------------------------------
    /**  Track Views
    /** ----------------------------------------*/

	function track_views()
	{
		global $DB, $TMPL, $PREFS;
		
		if ($PREFS->ini('enable_entry_view_tracking') == 'n')
		{
			return;
		}
		
		if ( ! $TMPL->fetch_param('track_views') OR $this->hit_tracking_id === FALSE OR ! in_array($TMPL->fetch_param('track_views'), array("one", "two", "three", "four")))
		{
			return;
		}
		
		if ($this->field_pagination == TRUE AND $this->p_page > 0)
		{
			return;
		}
	
		$column = "view_count_".$TMPL->fetch_param('track_views');
				
		$sql = "UPDATE exp_weblog_titles SET {$column} = ({$column} + 1) WHERE ";
		
		$sql .= (is_numeric($this->hit_tracking_id)) ? "entry_id = {$this->hit_tracking_id}" : "url_title = '".$DB->escape_str($this->hit_tracking_id)."'";
					
		$DB->query($sql);
	}
	/* END */


    /** ----------------------------------------
    /**  Fetch pagination data
    /** ----------------------------------------*/

    function fetch_pagination_data()
    {
		global $TMPL, $FNS, $EXT;
				
		if (preg_match("/".LD."paginate".RD."(.+?)".LD.SLASH."paginate".RD."/s", $TMPL->tagdata, $match))
		{ 
			if ($TMPL->fetch_param('paginate_type') == 'field')
			{ 
				if (preg_match("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", $TMPL->tagdata, $mmatch))
				{
					$this->multi_fields = $FNS->fetch_simple_conditions($mmatch['1']);
					$this->field_pagination = TRUE;
				}
			}
			
			// -------------------------------------------
			// 'weblog_module_fetch_pagination_data' hook.
			//  - Works with the 'weblog_module_create_pagination' hook
			//  - Developers, if you want to modify the $this object remember
			//    to use a reference on function call.
			//
				if ($EXT->active_hook('weblog_module_fetch_pagination_data') === TRUE)
				{
					$edata = $EXT->universal_call_extension('weblog_module_fetch_pagination_data', $this);
					if ($EXT->end_script === TRUE) return;
				}
			//
			// -------------------------------------------
			
			$this->paginate	= TRUE;
			$this->paginate_data = $match['1'];
						
			$TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.SLASH."paginate".RD."/s", "", $TMPL->tagdata);
		}
	}
	/* END */
	
	
	/** ----------------------------------------
    /**  Add pagination data to result
    /** ----------------------------------------*/
    
    function add_pagination_data()
    {
    	global $TMPL;

		if ($this->pagination_links == '')
		{
		//	return;
		}
		
        if ($this->paginate == TRUE)
        {
			$this->paginate_data = str_replace(LD.'current_page'.RD, 		$this->current_page, 		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'total_pages'.RD,			$this->total_pages,  		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'pagination_links'.RD,	$this->pagination_links,	$this->paginate_data);
        	
        	if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.SLASH."if".RD."/s", $this->paginate_data, $match))
        	{
        		if ($this->page_previous == '')
        		{
        			 $this->paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.SLASH."if".RD."/s", '', $this->paginate_data);
        		}
        		else
        		{
					$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_previous, $match['1']);
					$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_previous, $match['1']);
				
					$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
				}
        	}
        	
        	
        	if (preg_match("/".LD."if next_page".RD."(.+?)".LD.SLASH."if".RD."/s", $this->paginate_data, $match))
        	{
        		if ($this->page_next == '')
        		{
        			 $this->paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.SLASH."if".RD."/s", '', $this->paginate_data);
        		}
        		else
        		{
					$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_next, $match['1']);
					$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_next, $match['1']);
				
					$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
				}
        	}
                
			$position = ( ! $TMPL->fetch_param('paginate')) ? '' : $TMPL->fetch_param('paginate');
			
			switch ($position)
			{
				case "top"	: $this->return_data  = $this->paginate_data.$this->return_data;
					break;
				case "both"	: $this->return_data  = $this->paginate_data.$this->return_data.$this->paginate_data;
					break;
				default		: $this->return_data .= $this->paginate_data;
					break;
			}
        }	
    }
    /* END */
    
    
    /** ----------------------------------------
    /**  Fetch custom weblog field IDs
    /** ----------------------------------------*/

    function fetch_custom_weblog_fields()
    {
        global $DB, $SESS, $TMPL;
        
		if (isset($SESS->cache['weblog']['custom_weblog_fields']) && isset($SESS->cache['weblog']['date_fields']) && isset($SESS->cache['weblog']['relationship_fields']))
		{
			$this->cfields = $SESS->cache['weblog']['custom_weblog_fields'];
			$this->dfields = $SESS->cache['weblog']['date_fields'];
			$this->rfields = $SESS->cache['weblog']['relationship_fields'];
			return;
		}
		
        // Gotta catch 'em all!
        $sql = "SELECT field_id, field_type, field_name, site_id 
        		FROM exp_weblog_fields";
                        
        $query = $DB->query($sql);
                
        foreach ($query->result as $row)
        {
        	// Assign date fields
        	if ($row['field_type'] == 'date')
        	{
				$this->dfields[$row['site_id']][$row['field_name']] = $row['field_id'];
        	}
			// Assign relationship fields
        	if ($row['field_type'] == 'rel')
        	{
				$this->rfields[$row['site_id']][$row['field_name']] = $row['field_id'];
        	}
        	
        	// Assign standard fields
            $this->cfields[$row['site_id']][$row['field_name']] = $row['field_id'];
        }

  		$SESS->cache['weblog']['custom_weblog_fields'] = $this->cfields;
		$SESS->cache['weblog']['date_fields'] = $this->dfields;
		$SESS->cache['weblog']['relationship_fields'] = $this->rfields;
    }
    /* END */



    /** ----------------------------------------
    /**  Fetch custom member field IDs
    /** ----------------------------------------*/

    function fetch_custom_member_fields()
    {
        global $DB;
        
        $query = $DB->query("SELECT m_field_id, m_field_name, m_field_fmt FROM exp_member_fields");
                
        foreach ($query->result as $row)
        { 
            $this->mfields[$row['m_field_name']] = array($row['m_field_id'], $row['m_field_fmt']);
        }
    }
    /* END */


    /** ----------------------------------------
    /**  Fetch categories
    /** ----------------------------------------*/

    function fetch_categories()
    {
        global $DB, $TMPL;
        
		if ($this->enable['category_fields'] === TRUE)
		{
			$query = $DB->query("SELECT field_id, field_name FROM exp_category_fields WHERE site_id IN ('".implode("','", $TMPL->site_ids)."')");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}
			
			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}
		
        $sql = "SELECT c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description, c.parent_id,
						p.cat_id, p.entry_id, c.group_id {$field_sqla}
				FROM	(exp_categories AS c, exp_category_posts AS p)
				{$field_sqlb}
				WHERE	c.cat_id = p.cat_id
				AND		p.entry_id IN (";
                
        $categories = array();
                
        foreach ($this->query->result as $row)
        {
            $sql .= "'".$row['entry_id']."',"; 
            
            $categories[] = $row['entry_id'];
        }
        
        $sql = substr($sql, 0, -1).')';
        
        $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
        
        $query = $DB->query($sql);

        if ($query->num_rows == 0)
        {
            return;
        }
        		
        foreach ($categories as $val)
        {
            $this->temp_array = array();
            $this->cat_array  = array();
            $parents = array();
        
            foreach ($query->result as $row)
            {    
                if ($val == $row['entry_id'])
                {
                    $this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);
                    
					foreach ($row as $k => $v)
					{
						if (strpos($k, 'field') !== FALSE)
						{
							$this->temp_array[$row['cat_id']][$k] = $v;
						}
					}
					
                    if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) $parents[$row['parent_id']] = '';
                    unset($parents[$row['cat_id']]);
                }              
            }
            
            if (count($this->temp_array) == 0)
            {
                $temp = FALSE;
            }
            else
            {
            	foreach($this->temp_array as $k => $v) 
				{			
					if (isset($parents[$v['1']])) $v['1'] = 0;
				
					if (0 == $v['1'])
					{    
						$this->cat_array[] = $v;
						$this->process_subcategories($k);
					}
				}
			}
			
			$this->categories[$val] = $this->cat_array;
        }

        unset($this->temp_array);
        unset($this->cat_array);
    }
    /* END */
   
   

    /** ----------------------------------------
    /**  Build SQL query
    /** ----------------------------------------*/

    function build_sql_query($qstring = '')
    {
        global $IN, $DB, $TMPL, $SESS, $LOC, $FNS, $REGX, $PREFS;
        
        $entry_id		= '';
        $year			= '';
        $month			= '';
        $day			= '';
        $qtitle			= '';
        $cat_id			= '';
        $corder			= array();
		$offset			=  0;
		$page_marker	= FALSE;
        $dynamic		= TRUE;
        
        $this->dynamic_sql = TRUE;
                 
        /** ----------------------------------------------
        /**  Is dynamic='off' set?
        /** ----------------------------------------------*/
        
        // If so, we'll override all dynamically set variables
        
		if ($TMPL->fetch_param('dynamic') == 'off')
		{		
			$dynamic = FALSE;
		}  
		
        /** ----------------------------------------------
        /**  Do we allow dynamic POST variables to set parameters?
        /** ----------------------------------------------*/

		if ($TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{			
			foreach (explode('|', $TMPL->fetch_param('dynamic_parameters')) as $var)
			{			
				if (isset($_POST[$var]) AND in_array($var, array('weblog', 'entry_id', 'category', 'orderby', 'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from', 'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month', 'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'cat_limit', 'month_limit', 'offset', 'author_id')))
				{
					$TMPL->tagparams[$var] = $_POST[$var];
				}
				
				if (isset($_POST[$var]) && strncmp($var, 'search:', 7) == 0)
				{
					$TMPL->search_fields[substr($var, 7)] = $_POST[$var];
				}
			}
		}		
		
        /** ----------------------------------------------
        /**  Parse the URL query string
        /** ----------------------------------------------*/
        
        $this->uristr = $IN->URI;

        if ($qstring == '')
			$qstring = $this->QSTR;
			
		$this->basepath = $FNS->create_url($this->uristr, 1);
		
		if ($qstring == '')
		{
			if ($TMPL->fetch_param('require_entry') == 'yes')
			{
				return '';
			}
		}
		else
		{
			/** --------------------------------------
			/**  Do we have a pure ID number?
			/** --------------------------------------*/
		
			if (is_numeric($qstring) AND $dynamic)
			{
				$entry_id = $qstring;
			}
			else
			{
				/** --------------------------------------
				/**  Parse day
				/** --------------------------------------*/
				
				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match) AND $dynamic)
				{											
					$partial = substr($match['0'], 0, -3);
					
					if (preg_match("#(\d{4}/\d{2})#", $partial, $pmatch))
					{											
						$ex = explode('/', $pmatch['1']);
						
						$year =  $ex['0'];
						$month = $ex['1'];  
					}
					
					$day = $match['1'];
										
					$qstring = $REGX->trim_slashes(str_replace($match['0'], $partial, $qstring));
				}
				
				/** --------------------------------------
				/**  Parse /year/month/
				/** --------------------------------------*/
				
				// added (^|\/) to make sure this doesn't trigger with url titles like big_party_2006
				if (preg_match("#(^|\/)(\d{4}/\d{2})(\/|$)#", $qstring, $match) AND $dynamic)
				{		
					$ex = explode('/', $match['2']);
					
					$year	= $ex['0'];
					$month	= $ex['1'];


					$qstring = $REGX->trim_slashes(str_replace($match['2'], '', $qstring));

					// Removed this in order to allow archive pagination
					// $this->paginate = FALSE;
				}
				
				/** --------------------------------------
				/**  Parse ID indicator
				/** --------------------------------------*/

				if (preg_match("#^(\d+)(.*)#", $qstring, $match) AND $dynamic)
				{
					$seg = ( ! isset($match['2'])) ? '' : $match['2'];
				
					if (substr($seg, 0, 1) == "/" OR $seg == '')
					{
						$entry_id = $match['1'];	
						$qstring = $REGX->trim_slashes(preg_replace("#^".$match['1']."#", '', $qstring));
					}
				}
				

				/** --------------------------------------
				/**  Parse page number
				/** --------------------------------------*/
				
				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match) AND ($dynamic OR $TMPL->fetch_param('paginate')))
				{	
					$this->p_page = (isset($match['2'])) ? $match['2'] : $match['1'];	
						
					$this->basepath = $FNS->remove_double_slashes(str_replace($match['0'], '', $this->basepath));
							
					$this->uristr  = $FNS->remove_double_slashes(str_replace($match['0'], '', $this->uristr));
					
					$qstring = $REGX->trim_slashes(str_replace($match['0'], '', $qstring));
					
					$page_marker = TRUE;
				}

				/** --------------------------------------
				/**  Parse category indicator
				/** --------------------------------------*/
				
				// Text version of the category

				if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $dynamic AND $TMPL->fetch_param('weblog'))
				{
					$qstring = preg_replace("/(.*?)".preg_quote($this->reserved_cat_segment)."\//i", '', $qstring);
						
					$sql = "SELECT DISTINCT cat_group FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') AND ";
					
					if (USER_BLOG !== FALSE)
					{
						$sql .= " weblog_id='".UB_BLOG_ID."'";
					}
					else
					{
						$xsql = $FNS->sql_andor_string($TMPL->fetch_param('weblog'), 'blog_name');
						
						if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);
						
						$sql .= ' '.$xsql;
					}
						
					$query = $DB->query($sql);

					if ($query->num_rows > 0)
					{
						$valid = 'y';
						$last  = explode('|', $query->row['cat_group']);
						$valid_cats = array();
						
						foreach($query->result as $row)
						{
							if ($TMPL->fetch_param('relaxed_categories') == 'yes')
							{
								$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
							}
							else
							{
								$valid_cats = array_intersect($last, explode('|', $row['cat_group']));								
							}
							
							$valid_cats = array_unique($valid_cats);
							
							if (sizeof($valid_cats) == 0)
							{
								$valid = 'n';
								break;
							}
						}
					}
					else
					{
						$valid = 'n';
					}

					if ($valid == 'y')
					{
						// the category URL title should be the first segment left at this point in $qstring,
						// but because prior to this feature being added, category names were used in URLs,
						// and '/' is a valid character for category names.  If they have not updated their
						// category url titles since updating to 1.6, their category URL title could still
						// contain a '/'.  So we'll try to get the category the correct way first, and if
						// it fails, we'll try the whole $qstring
						
						$cut_qstring = array_shift($temp = explode('/', $qstring));
						
						$result = $DB->query("SELECT cat_id FROM exp_categories 
											  WHERE cat_url_title='".$DB->escape_str($cut_qstring)."' 
											  AND group_id IN ('".implode("','", $valid_cats)."')");

						if ($result->num_rows == 1)
						{
							$qstring = str_replace($cut_qstring, 'C'.$result->row['cat_id'], $qstring);
						}
						else
						{
							// give it one more try using the whole $qstring
							$result = $DB->query("SELECT cat_id FROM exp_categories 
												  WHERE cat_url_title='".$DB->escape_str($qstring)."' 
												  AND group_id IN ('".implode("','", $valid_cats)."')");

							if ($result->num_rows == 1)
							{
								$qstring = 'C'.$result->row['cat_id'];
							}
						}
					}
				}

				// Numeric version of the category

				if (preg_match("#(^|\/)C(\d+)#", $qstring, $match) AND $dynamic)
				{		
					$this->cat_request = TRUE;
					
					$cat_id = $match['2'];	
														
					$qstring = $REGX->trim_slashes(str_replace($match['0'], '', $qstring));
				}
				
				/** --------------------------------------
				/**  Remove "N" 
				/** --------------------------------------*/
				
				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{					
					$this->uristr  = $FNS->remove_double_slashes(str_replace($match['0'], '', $this->uristr));
					
					$qstring = $REGX->trim_slashes(str_replace($match['0'], '', $qstring));
				}
		
				/** --------------------------------------
				/**  Parse URL title
				/** --------------------------------------*/

				if (($cat_id == '' AND $year == '') OR $TMPL->fetch_param('require_entry') == 'yes')
				{
					if (strstr($qstring, '/'))
					{
						$xe = explode('/', $qstring);
						$qstring = current($xe);
					}
					
					if ($dynamic == TRUE)
					{
						$sql = "SELECT count(*) AS count 
								FROM  exp_weblog_titles, exp_weblogs 
								WHERE exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
								AND   exp_weblog_titles.url_title = '".$DB->escape_str($qstring)."'";
						
						if (USER_BLOG !== FALSE)
						{
							$sql .= " AND exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
						}
						else
						{
							$sql .= " AND exp_weblogs.is_user_blog = 'n' AND exp_weblogs.site_id IN ('".implode("','", $TMPL->site_ids)."') ";
						}

						$query = $DB->query($sql);
						
						if ($query->row['count'] == 0)
						{
							if ($TMPL->fetch_param('require_entry') == 'yes')
							{
								return '';
							}
						
							$qtitle = '';
						}
						else
						{
							$qtitle = $qstring;
						}
					}
				}
			}
		}
		    
				    		        
        /** ----------------------------------------------
        /**  Entry ID number
        /** ----------------------------------------------*/
        
        // If the "entry ID" was hard-coded, use it instead of
        // using the dynamically set one above
        
		if ($TMPL->fetch_param('entry_id'))
		{
			$entry_id = $TMPL->fetch_param('entry_id');
		}
		
		/** ----------------------------------------------
        /**  Only Entries with Pages
        /** ----------------------------------------------*/
        
		if ($TMPL->fetch_param('show_pages') !== FALSE && in_array($TMPL->fetch_param('show_pages'), array('only', 'no')) && ($pages = $PREFS->ini('site_pages')) !== FALSE)
		{
			// consider entry_id
			if ($TMPL->fetch_param('entry_id') !== FALSE)
			{
				$not = FALSE;

				if (strncmp($entry_id, 'not', 3) == 0)
				{
					$not = TRUE;
					$entry_id = trim(substr($entry_id, 3));
				}

				$ids = explode('|', $entry_id);

				if ($TMPL->fetch_param('show_pages') == 'only')
				{
					if ($not === TRUE)
					{
						$entry_id = implode('|', array_diff(array_flip($pages['uris']), explode('|', $ids)));
					}
					else
					{
						$entry_id = implode('|',array_diff($ids, array_diff($ids, array_flip($pages['uris']))));
					}
				}
				else
				{
					if ($not === TRUE)
					{
						$entry_id = "not {$entry_id}|".implode('|', array_flip($pages['uris']));
					}
					else
					{
						$entry_id = implode('|',array_diff($ids, array_flip($pages['uris'])));
					}
				}
				echo $entry_id;
			}
			else
			{
				$entry_id = (($TMPL->fetch_param('show_pages') == 'no') ? 'not ' : '').implode('|', array_flip($pages['uris']));
			}
		}	

        /** ----------------------------------------------
        /**  Assing the order variables
        /** ----------------------------------------------*/
		
		$order  = $TMPL->fetch_param('orderby');
		$sort   = $TMPL->fetch_param('sort');
		$sticky = $TMPL->fetch_param('sticky');
		
		/** -------------------------------------
		/**  Multiple Orders and Sorts...
		/** -------------------------------------*/
		
		if ($order !== FALSE && stristr($order, '|'))
		{
			$order_array = explode('|', $order);
			
			if ($order_array['0'] == 'random')
			{
				$order_array = array('random');
			}
		}
		else
		{
			$order_array = array($order);
		}
		
		if ($sort !== FALSE && stristr($sort, '|'))
		{
			$sort_array = explode('|', $sort);
		}
		else
		{
			$sort_array = array($sort);
		}
		
		/** -------------------------------------
		/**  Validate Results for Later Processing
		/** -------------------------------------*/
					
		$base_orders = array('random', 'entry_id', 'date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date',
							 'view_count_one', 'view_count_two', 'view_count_three', 'view_count_four');
				
		foreach($order_array as $key => $order)
		{
			if ( ! in_array($order, $base_orders))
			{	
				if (FALSE !== $order)
				{
					$set = 'n';
					
					/** -------------------------------------
					/**  Site Namespace is Being Used, Parse Out
					/** -------------------------------------*/
					
					if (strpos($order, ':') !== FALSE)
					{
						$order_parts = explode(':', $order, 2);
						
						if (isset($TMPL->site_ids[$order_parts[0]]) && isset($this->cfields[$TMPL->site_ids[$order_parts[0]]][$order_parts[1]]))
						{
							$corder[$key] = $this->cfields[$TMPL->site_ids[$order_parts[0]]][$order_parts[1]];
							$order_array[$key] = 'custom_field';
							$set = 'y';
						}
					}
					
					/** -------------------------------------
					/**  Find the Custom Field, Cycle Through All Sites for Tag
					/**  - If multiple sites have the same short_name for a field, we do a CONCAT ORDERBY in query
					/** -------------------------------------*/
					
					if ($set == 'n')
					{
						foreach($this->cfields as $site_id => $cfields)
						{
							// Only those sites specified
							if ( ! in_array($site_id, $TMPL->site_ids))
							{
								continue;
							}
						
							if (isset($cfields[$order]))
							{
								if ($set == 'y')
								{
									$corder[$key] .= '|'.$cfields[$order];
								}
								else
								{
									$corder[$key] = $cfields[$order];
									$order_array[$key] = 'custom_field';
									$set = 'y';
								}
							}
						}
					}
					
					if ($set == 'n')
					{
						$order_array[$key] = FALSE;
					}
				}
			}
			
			if ( ! isset($sort_array[$key]))
			{
				$sort_array[$key] = 'desc';
			}
		}
		
		foreach($sort_array as $key => $sort)
		{
			if ($sort == FALSE || ($sort != 'asc' AND $sort != 'desc'))
			{
				$sort_array[$key] = "desc";
			}
		}
		
		// fixed entry id ordering
		if (($fixed_order = $TMPL->fetch_param('fixed_order')) === FALSE OR preg_match('/[^0-9\|]/', $fixed_order))
		{
			$fixed_order = FALSE;
		}
		else
		{
			// MySQL will not order the entries correctly unless the results are constrained
			// to matching rows only, so we force the entry_id as well
			$entry_id = $fixed_order;
			$fixed_order = preg_split('/\|/', $fixed_order, -1, PREG_SPLIT_NO_EMPTY);
			
			// some peeps might want to be able to 'flip' it
			// the default sort order is 'desc' but in this context 'desc' has a stronger "reversing"
			// connotation, so we look not at the sort array, but the tag parameter itself, to see the user's intent
			if ($sort == 'desc')
			{
				$fixed_order = array_reverse($fixed_order);
			}
		}

        /** ----------------------------------------------
        /**  Build the master SQL query
        /** ----------------------------------------------*/
		
		$sql_a = "SELECT ";
		
		$sql_b = ($TMPL->fetch_param('category') || $TMPL->fetch_param('category_group') || $cat_id != '' || $order_array['0'] == 'random') ? "DISTINCT(t.entry_id) " : "t.entry_id ";
				
		if ($this->field_pagination == TRUE)
		{
			$sql_b .= ",wd.* ";
		}

		$sql_c = "COUNT(t.entry_id) AS count ";
		
		$sql = "FROM exp_weblog_titles AS t
				LEFT JOIN exp_weblogs ON t.weblog_id = exp_weblogs.weblog_id ";
				
		if ($this->field_pagination == TRUE)
		{
			$sql .= "LEFT JOIN exp_weblog_data AS wd ON t.entry_id = wd.entry_id ";
		}
		elseif (in_array('custom_field', $order_array))
		{
			$sql .= "LEFT JOIN exp_weblog_data AS wd ON t.entry_id = wd.entry_id ";
		}
		
		$sql .= "LEFT JOIN exp_members AS m ON m.member_id = t.author_id ";
				
						  
        if ($TMPL->fetch_param('category') || $TMPL->fetch_param('category_group') || $cat_id != '')                      
        {
        	/* --------------------------------
        	/*  We use LEFT JOIN when there is a 'not' so that we get 
        	/*  entries that are not assigned to a category.
        	/* --------------------------------*/
        	
        	if ((substr($TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr($TMPL->fetch_param('category'), 0, 3) == 'not') && $TMPL->fetch_param('uncategorized_entries') !== 'n')
        	{
        		$sql .= "LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
						 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
        	}
        	else
        	{
        		$sql .= "INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
						 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
			}
        }
        
		// join data table if we're searching fields
		if (! empty($TMPL->search_fields) && strpos($sql, 'exp_weblog_data AS wd') === FALSE)
		{
			$sql .= "LEFT JOIN exp_weblog_data AS wd ON wd.entry_id = t.entry_id ";
		}
		
        $sql .= "WHERE t.entry_id !='' AND t.site_id IN ('".implode("','", $TMPL->site_ids)."') ";

        /** ----------------------------------------------
        /**  We only select entries that have not expired 
        /** ----------------------------------------------*/
        
		$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;
		        
        if ($TMPL->fetch_param('show_future_entries') != 'yes')
        {
			$sql .= " AND t.entry_date < ".$timestamp." ";
        }
        
        if ($TMPL->fetch_param('show_expired') != 'yes')
        {        
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > ".$timestamp.") ";
        }
        
        /** ----------------------------------------------
        /**  Limit query by post ID for individual entries
        /** ----------------------------------------------*/
         
        if ($entry_id != '')
        {           
        	$sql .= $FNS->sql_andor_string($entry_id, 't.entry_id').' ';
        }
        
        /** ----------------------------------------------
        /**  Limit query by post url_title for individual entries
        /** ----------------------------------------------*/
         
        if ($url_title = $TMPL->fetch_param('url_title'))
        {           
        	$sql .= $FNS->sql_andor_string($url_title, 't.url_title').' ';
        }
        
        /** ----------------------------------------------
        /**  Limit query by entry_id range
        /** ----------------------------------------------*/
                
        if ($entry_id_from = $TMPL->fetch_param('entry_id_from'))
        {
            $sql .= "AND t.entry_id >= '$entry_id_from' ";
        }
        
        if ($entry_id_to = $TMPL->fetch_param('entry_id_to'))
        {
            $sql .= "AND t.entry_id <= '$entry_id_to' ";
        }
                
        /** ----------------------------------------------
        /**  Exclude an individual entry
        /** ----------------------------------------------*/

		if ($not_entry_id = $TMPL->fetch_param('not_entry_id'))
		{
			$sql .= ( ! is_numeric($not_entry_id)) 
					? "AND t.url_title != '{$not_entry_id}' " 
					: "AND t.entry_id  != '{$not_entry_id}' ";
		}

        /** ----------------------------------------------
        /**  Limit to/exclude specific weblogs
        /** ----------------------------------------------*/
    
        if (USER_BLOG !== FALSE)
        {
            // If it's a "user blog" we limit to only their assigned blog
        
            $sql .= "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' ";
        }
        else
        {
            $sql .= "AND exp_weblogs.is_user_blog = 'n' ";
        
            if ($weblog = $TMPL->fetch_param('weblog'))
            {
                $xql = "SELECT weblog_id FROM exp_weblogs WHERE ";
            
                $str = $FNS->sql_andor_string($weblog, 'blog_name');
                
                if (substr($str, 0, 3) == 'AND')
                    $str = substr($str, 3);
                
                $xql .= $str;            
                    
                $query = $DB->query($xql);
                
                if ($query->num_rows == 0)
                {
					return '';
                }
                else
                {
                    if ($query->num_rows == 1)
                    {
                        $sql .= "AND t.weblog_id = '".$query->row['weblog_id']."' ";
                    }
                    else
                    {
                        $sql .= "AND (";
                        
                        foreach ($query->result as $row)
                        {
                            $sql .= "t.weblog_id = '".$row['weblog_id']."' OR ";
                        }
                        
                        $sql = substr($sql, 0, - 3);
                        
                        $sql .= ") ";
                    }
                }
            }
        }

        /** ----------------------------------------------------
        /**  Limit query by date range given in tag parameters
        /** ----------------------------------------------------*/

        if ($TMPL->fetch_param('start_on'))
            $sql .= "AND t.entry_date >= '".$LOC->convert_human_date_to_gmt($TMPL->fetch_param('start_on'))."' ";

        if ($TMPL->fetch_param('stop_before'))
            $sql .= "AND t.entry_date < '".$LOC->convert_human_date_to_gmt($TMPL->fetch_param('stop_before'))."' ";
                

        /** -----------------------------------------------------
        /**  Limit query by date contained in tag parameters
        /** -----------------------------------------------------*/
			        
        if ($TMPL->fetch_param('year') || $TMPL->fetch_param('month') || $TMPL->fetch_param('day'))
        {
            $year	= ( ! is_numeric($TMPL->fetch_param('year'))) 	? date('Y') : $TMPL->fetch_param('year');
            $smonth	= ( ! is_numeric($TMPL->fetch_param('month')))	? '01' : $TMPL->fetch_param('month');
            $emonth	= ( ! is_numeric($TMPL->fetch_param('month')))	? '12':  $TMPL->fetch_param('month');
            $day	= ( ! is_numeric($TMPL->fetch_param('day')))	? '' : $TMPL->fetch_param('day');
            
            if ($day != '' && ! is_numeric($TMPL->fetch_param('month')))
            {
				$smonth = date('m');
				$emonth = date('m');
            }
            
            if (strlen($smonth) == 1) $smonth = '0'.$smonth;
            if (strlen($emonth) == 1) $emonth = '0'.$emonth;
        
			if ($day == '')
			{
				$sday = 1;
				$eday = $LOC->fetch_days_in_month($emonth, $year);
			}
			else
			{
				$sday = $day;
				$eday = $day;
			}
			
			$stime = $LOC->set_gmt(mktime(0, 0, 0, $smonth, $sday, $year));
			$etime = $LOC->set_gmt(mktime(23, 59, 59, $emonth, $eday, $year));  
			
			$sql .= " AND t.entry_date >= ".$stime." AND t.entry_date <= ".$etime." ";            
        }
        else
        {
            /** ------------------------------------------------
            /**  Limit query by date in URI: /2003/12/14/
            /** -------------------------------------------------*/
            
            if ($year != '' AND $month != '' AND $dynamic == TRUE)
            {
            	if ($day == '')
            	{
            		$sday = 1;
            		$eday = $LOC->fetch_days_in_month($month, $year);
            	}
            	else
            	{
            		$sday = $day;
            		$eday = $day;
            	}
            	            	
				$stime = $LOC->set_gmt(mktime(0, 0, 0, $month, $sday, $year));
				$etime = $LOC->set_gmt(mktime(23, 59, 59, $month, $eday, $year)); 
				
				if (date("I", $LOC->now) AND ! date("I", $stime))
				{
					$stime -= 3600;            
				}
				elseif ( ! date("I", $LOC->now) AND date("I", $stime))
				{
					$stime += 3600;            
				}
		
				$stime += $LOC->set_localized_offset();
				
				if (date("I", $LOC->now) AND ! date("I", $etime))
				{ 
					$etime -= 3600;            
				}
				elseif ( ! date("I", $LOC->now) AND date("I", $etime))
				{ 
					$etime += 3600;            
				}
		
				$etime += $LOC->set_localized_offset();
				
        		$sql .= " AND t.entry_date >= ".$stime." AND t.entry_date <= ".$etime." ";
            }
            else
            {
				$this->display_by = $TMPL->fetch_param('display_by');

                $lim = ( ! is_numeric($TMPL->fetch_param('limit'))) ? '1' : $TMPL->fetch_param('limit');
                 
                /** -------------------------------------------
                /**  If display_by = "month"
                /** -------------------------------------------*/
                 
                if ($this->display_by == 'month')
                {   
					// We need to run a query and fetch the distinct months in which there are entries
				
					$dql = "SELECT t.year, t.month ".$sql;
					
					/** ----------------------------------------------
					/**  Add status declaration
					/** ----------------------------------------------*/
									
					if ($status = $TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',   'open',   $status);
						$status = str_replace('Closed', 'closed', $status);
						
						$sstr = $FNS->sql_andor_string($status, 't.status');
						
						if ( ! preg_match("#\'closed\'#i", $sstr))
						{
							$sstr .= " AND t.status != 'closed' ";
						}
						
						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}
					
					/** --------------------
					/**  Add Category Limit
					/** --------------------*/
					
					if ($cat_id != '')
					{
						$dql .= "AND exp_category_posts.cat_id = '".$DB->escape_str($cat_id)."' ";
					}
				
					$query = $DB->query($dql);
				
					$distinct = array();
                	
					if ($query->num_rows > 0)
					{
						foreach ($query->result as $row)
						{ 
							$distinct[] = $row['year'].$row['month'];
						}
						
						$distinct = array_unique($distinct);
						
						sort($distinct);
						
						if ($sort_array['0'] == 'desc')
						{
							$distinct = array_reverse($distinct);
						}
						
						$this->total_rows = count($distinct);
						
						$cur = ($this->p_page == '') ? 0 : $this->p_page;
						
						$distinct = array_slice($distinct, $cur, $lim);	
						
						if ($distinct != FALSE)
						{
							$sql .= "AND (";
							
							foreach ($distinct as $val)
							{                	
								$sql .= "(t.year  = '".substr($val, 0, 4)."' AND t.month = '".substr($val, 4, 2)."') OR";
							}
							
							$sql = substr($sql, 0, -2).')';
						}
                	}                    
                }
                
                
                /** -------------------------------------------
                /**  If display_by = "day"
                /** -------------------------------------------*/
                
                elseif ($this->display_by == 'day')
                {   
					// We need to run a query and fetch the distinct days in which there are entries
					
					$dql = "SELECT t.year, t.month, t.day ".$sql;
					
					/** ----------------------------------------------
					/**  Add status declaration
					/** ----------------------------------------------*/
									
					if ($status = $TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',   'open',   $status);
						$status = str_replace('Closed', 'closed', $status);
						
						$sstr = $FNS->sql_andor_string($status, 't.status');
						
						if ( ! preg_match("#\'closed\'#i", $sstr))
						{
							$sstr .= " AND t.status != 'closed' ";
						}
						
						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}
					
					/** --------------------
					/**  Add Category Limit
					/** --------------------*/
					
					if ($cat_id != '')
					{
						$dql .= "AND exp_category_posts.cat_id = '".$DB->escape_str($cat_id)."' ";
					}
					
					$query = $DB->query($dql);
				
					$distinct = array();
                	
					if ($query->num_rows > 0)
					{
						foreach ($query->result as $row)
						{ 
							$distinct[] = $row['year'].$row['month'].$row['day'];
						}
						
						$distinct = array_unique($distinct);
						sort($distinct);
						
						if ($sort_array['0'] == 'desc')
						{
							$distinct = array_reverse($distinct);
						}
						
						$this->total_rows = count($distinct);
						
						$cur = ($this->p_page == '') ? 0 : $this->p_page;
						
						$distinct = array_slice($distinct, $cur, $lim);	
				
						if ($distinct != FALSE)
						{
							$sql .= "AND (";
							
							foreach ($distinct as $val)
							{                	
								$sql .= "(t.year  = '".substr($val, 0, 4)."' AND t.month = '".substr($val, 4, 2)."' AND t.day   = '".substr($val, 6)."' ) OR";
							}
							
							$sql = substr($sql, 0, -2).')';
                		}
                	}                    
                }
                
				/** -------------------------------------------
                /**  If display_by = "week"
                /** -------------------------------------------*/
                
                elseif ($this->display_by == 'week')
                {   
                	/** ---------------------------------
					/*	 Run a Query to get a combined Year and Week value.  There is a downside
					/*	 to this approach and that is the lack of localization and use of DST for 
					/*	 dates.  Unfortunately, without making a complex and ultimately fubar'ed
					/*   PHP script this is the best approach possible.
					/*  ---------------------------------*/
				
					$loc_offset = $LOC->set_localized_offset();
					
					if ($TMPL->fetch_param('start_day') === 'Monday')
					{
						$yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%x%v') AS yearweek ";
						$dql = 'SELECT '.$yearweek.$sql;
					}
					else
					{
						$yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%X%V') AS yearweek ";
						$dql = 'SELECT '.$yearweek.$sql;
					}
					
					/** ----------------------------------------------
					/**  Add status declaration
					/** ----------------------------------------------*/
									
					if ($status = $TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',   'open',   $status);
						$status = str_replace('Closed', 'closed', $status);
						
						$sstr = $FNS->sql_andor_string($status, 't.status');
						
						if ( ! preg_match("#\'closed\'#i", $sstr))
						{
							$sstr .= " AND t.status != 'closed' ";
						}
						
						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}
					
					/** --------------------
					/**  Add Category Limit
					/** --------------------*/
					
					if ($cat_id != '')
					{
						$dql .= "AND exp_category_posts.cat_id = '".$DB->escape_str($cat_id)."' ";
					}
					
					$query = $DB->query($dql);
				
					$distinct = array();
                	
					if ($query->num_rows > 0)
					{
						/** ---------------------------------
						/*	 Sort Default is ASC for Display By Week so that entries are displayed
						/*   oldest to newest in the week, which is how you would expect.
						/*  ---------------------------------*/
					
						if ($TMPL->fetch_param('sort') === FALSE)
						{
							$sort_array['0'] = 'asc';
						}
					
						foreach ($query->result as $row)
						{ 
							$distinct[] = $row['yearweek'];
						}
						
						$distinct = array_unique($distinct);
						rsort($distinct);
						
						/* Old code, didn't really do anything....
						*
						if ($TMPL->fetch_param('week_sort') == 'desc')
						{
							$distinct = array_reverse($distinct);
						}
						*
						*/
						
						$this->total_rows = count($distinct);
						$cur = ($this->p_page == '') ? 0 : $this->p_page;
						
						/** ---------------------------------
						/*	 If no pagination, then the Current Week is shown by default with
						/*	 all pagination correctly set and ready to roll, if used.
						/*  ---------------------------------*/
						
						if ($TMPL->fetch_param('show_current_week') === 'yes' && $this->p_page == '')
						{
							if ($TMPL->fetch_param('start_day') === 'Monday')
							{
								$query = $DB->query("SELECT DATE_FORMAT(CURDATE(), '%x%v') AS thisWeek");
							}
							else
							{
								$query = $DB->query("SELECT DATE_FORMAT(CURDATE(), '%X%V') AS thisWeek");	
							}
							
							foreach($distinct as $key => $week)
							{
								if ($week == $query->row['thisWeek'])
								{
									$cur = $key;
									$this->p_page = $key;
									break;
								}
							}
						}
						
						$distinct = array_slice($distinct, $cur, $lim);
				
						/** ---------------------------------
						/*	 Finally, we add the display by week SQL to the query
						/*  ---------------------------------*/
				
						if ($distinct != FALSE)
						{
							// A Rough Attempt to Get the Localized Offset Added On
							
							$offset = $LOC->set_localized_offset();
							$dst_on = (date("I", $LOC->now) == 1) ? TRUE : FALSE;
											
							$sql .= "AND (";
							
							foreach ($distinct as $val)
							{
								if ($dst_on === TRUE AND (substr($val, 4) < 13 OR substr($val, 4) >= 43))
								{
									$sql_offset = "- 3600";            
								}
								elseif ($dst_on === FALSE AND (substr($val, 4) >= 13 AND substr($val, 4) < 43))
								{
									$sql_offset = "+ 3600";
								}
								else
								{
									$sql_offset = '';
								}
							
								if ($TMPL->fetch_param('start_day') === 'Monday')
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date {$sql_offset}), '%x%v') = '".$val."' OR";
								}
								else
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date {$sql_offset}), '%X%V') = '".$val."' OR";
								}
							}
							
							$sql = substr($sql, 0, -2).')';
                		}
                	}	
                }
            }
        }
        
        
        /** ----------------------------------------------
        /**  Limit query "URL title"
        /** ----------------------------------------------*/
         
        if ($qtitle != '' AND $dynamic)
        {    
			$sql .= "AND t.url_title = '".$DB->escape_str($qtitle)."' ";
			
			// We use this with hit tracking....
			
			$this->hit_tracking_id = $qtitle;
        }
                

		// We set a global variable which we use with entry hit tracking
		
		if ($entry_id != '' AND $this->entry_id !== FALSE)
		{
			$this->hit_tracking_id = $entry_id;
		}
        
        /** ----------------------------------------------
        /**  Limit query by category
        /** ----------------------------------------------*/
                
        if ($TMPL->fetch_param('category'))
        {
        	if (stristr($TMPL->fetch_param('category'), '&'))
        	{
        		/** --------------------------------------
        		/**  First, we find all entries with these categories
        		/** --------------------------------------*/
        		
        		$for_sql = (substr($TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr($TMPL->fetch_param('category'), 3)) : $TMPL->fetch_param('category');
        		
        		$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id ".
						$sql.
						$FNS->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');
        	
        		//exit($csql);
        	
        		$results = $DB->query($csql); 
        							  
        		if ($results->num_rows == 0)
        		{
					return;
        		}
        		
        		$type = 'IN';
        		$categories	 = explode('&', $TMPL->fetch_param('category'));
        		$entry_array = array();
        		
        		if (substr($categories['0'], 0, 3) == 'not')
        		{
        			$type = 'NOT IN';
        			
        			$categories['0'] = trim(substr($categories['0'], 3));
        		}
        		
        		foreach($results->result as $row)
        		{
        			$entry_array[$row['cat_id']][] = $row['entry_id'];
        		}
        		
        		if (sizeof($entry_array) < 2 OR sizeof(array_diff($categories, array_keys($entry_array))) > 0)
        		{
					return;
        		}
        		
        		$chosen = call_user_func_array('array_intersect', $entry_array);
        		
        		if (sizeof($chosen) == 0)
        		{
					return;
        		}
        		
        		$sql .= "AND t.entry_id ".$type." ('".implode("','", $chosen)."') ";
        	}
        	else
        	{
        		if (substr($TMPL->fetch_param('category'), 0, 3) == 'not' && $TMPL->fetch_param('uncategorized_entries') !== 'n')
        		{
        			$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
        		}
        		else
        		{
        			$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
        		}
        	}
        }
        
        if ($TMPL->fetch_param('category_group'))
        {
            if (substr($TMPL->fetch_param('category_group'), 0, 3) == 'not' && $TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
        }
        
        if ($TMPL->fetch_param('category') === FALSE && $TMPL->fetch_param('category_group') === FALSE)
        {
            if ($cat_id != '' AND $dynamic)
            {           
                $sql .= " AND exp_categories.cat_id = '".$DB->escape_str($cat_id)."' ";
            }
        }
        
        /** ----------------------------------------------
        /**  Limit to (or exclude) specific users
        /** ----------------------------------------------*/
        
        if ($username = $TMPL->fetch_param('username'))
        {
            // Shows entries ONLY for currently logged in user
        
            if ($username == 'CURRENT_USER')
            {
                $sql .=  "AND m.member_id = '".$SESS->userdata('member_id')."' ";
            }
            elseif ($username == 'NOT_CURRENT_USER')
            {
                $sql .=  "AND m.member_id != '".$SESS->userdata('member_id')."' ";
            }
            else
            {                
                $sql .= $FNS->sql_andor_string($username, 'm.username');
            }
        }
        
        /** ----------------------------------------------
        /**  Limit to (or exclude) specific author id(s)
        /** ----------------------------------------------*/
        
        if ($author_id = $TMPL->fetch_param('author_id'))
        {
            // Shows entries ONLY for currently logged in user
        
            if ($author_id == 'CURRENT_USER')
            {
                $sql .=  "AND m.member_id = '".$SESS->userdata('member_id')."' ";
            }
            elseif ($author_id == 'NOT_CURRENT_USER')
            {
                $sql .=  "AND m.member_id != '".$SESS->userdata('member_id')."' ";
            }
            else
            {                
                $sql .= $FNS->sql_andor_string($author_id, 'm.member_id');
            }
        }
   
        /** ----------------------------------------------
        /**  Add status declaration
        /** ----------------------------------------------*/
                        
        if ($status = $TMPL->fetch_param('status'))
        {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
			
			$sstr = $FNS->sql_andor_string($status, 't.status');
			
			if ( ! preg_match("#\'closed\'#i", $sstr))
			{
				$sstr .= " AND t.status != 'closed' ";
			}
			
			$sql .= $sstr;
        }
        else
        {
            $sql .= "AND t.status = 'open' ";
        }
            
        /** ----------------------------------------------
        /**  Add Group ID clause
        /** ----------------------------------------------*/
        
        if ($group_id = $TMPL->fetch_param('group_id'))
        {
            $sql .= $FNS->sql_andor_string($group_id, 'm.group_id');
        }
         
    	/** ---------------------------------------
    	/**  Field searching
    	/** ---------------------------------------*/
	
		if (! empty($TMPL->search_fields))
		{
			foreach ($TMPL->search_fields as $field_name => $terms)
			{
				if (isset($this->cfields[$PREFS->ini('site_id')][$field_name]))
				{
					if (strncmp($terms, '=', 1) ==  0)
					{
						/** ---------------------------------------
						/**  Exact Match e.g.: search:body="=pickle"
						/** ---------------------------------------*/
						
						$terms = substr($terms, 1);
						
						// special handling for IS_EMPTY
						if (strpos($terms, 'IS_EMPTY') !== FALSE)
						{
							$terms = str_replace('IS_EMPTY', '', $terms);
							
							$add_search = $FNS->sql_andor_string($terms, 'wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name]);

							// remove the first AND output by $FNS->sql_andor_string() so we can parenthesize this clause
							$add_search = substr($add_search, 3);
							
							$conj = ($add_search == '') ? '' : ((strncmp($terms, 'not ', 4) != 0) ? 'OR' : 'AND');

							if (strncmp($terms, 'not ', 4) == 0)
							{
								$sql .= 'AND ('.$add_search.' '.$conj.' wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name].' != "") ';
							}
							else
							{
								$sql .= 'AND ('.$add_search.' '.$conj.' wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name].' = "") ';
							}
						}
						else
						{
							$sql .= $FNS->sql_andor_string($terms, 'wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name]).' ';							
						}
					}
					else
					{
						/** ---------------------------------------
						/**  "Contains" e.g.: search:body="pickle"
						/** ---------------------------------------*/
						
						if (strncmp($terms, 'not ', 4) == 0)
						{
							$terms = substr($terms, 4);
							$like = 'NOT LIKE';
						}
						else
						{
							$like = 'LIKE';
						}
						
						if (strpos($terms, '&&') !== FALSE)
						{
							$terms = explode('&&', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'OR' : 'AND';
						}
						else
						{
							$terms = explode('|', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'AND' : 'OR';
						}
						
						$sql .= ' AND (';
						
						foreach ($terms as $term)
						{
							if ($term == 'IS_EMPTY')
							{
								$sql .= ' wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name].' '.$like.' "" '.$andor;
							}
							elseif (strpos($term, '\W') !== FALSE) // full word only, no partial matches
							{
								$not = ($like == 'LIKE') ? ' ' : ' NOT ';
								
								// Note: MySQL's nutty POSIX regex word boundary is [[:>:]]
								// we add slashes because $DB->escape_str() strips slashes before adding, and
								// we need them to remain intact or MySQL will not parse the regular expression properly
								$term = '([[:<:]]|^)'.addslashes(preg_quote(str_replace('\W', '', $term))).'([[:>:]]|$)';
								
								$sql .= ' wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name].$not.'REGEXP "'.$DB->escape_str($term).'" '.$andor;
							}
							else
							{
								$sql .= ' wd.field_id_'.$this->cfields[$PREFS->ini('site_id')][$field_name].' '.$like.' "%'.$DB->escape_like_str($term).'%" '.$andor;								
							}
						}
						
						$sql = substr($sql, 0, -strlen($andor)).') ';
					}
				}
			}
		}
		  
        /** --------------------------------------------------
        /**  Build sorting clause
        /** --------------------------------------------------*/
        
        // We'll assign this to a different variable since we
        // need to use this in two places
        
        $end = 'ORDER BY ';

		if ($fixed_order !== FALSE)
		{
			$end .= 'FIELD(t.entry_id, '.implode(',', $fixed_order).') ';
		}
		else
		{
			// Used to eliminate sort issues with duplicated fields below
			$entry_id_sort = $sort_array[0];		

			if (FALSE === $order_array['0'])
			{
				if ($sticky == 'off')
				{
					$end .= "t.entry_date";
				}
				else
				{
					$end .= "t.sticky desc, t.entry_date";
				}

				if ($sort_array['0'] == 'asc' || $sort_array['0'] == 'desc')
				{
					$end .= " ".$sort_array['0'];
				}
			}
			else
			{
				if ($sticky != 'off')
				{
					$end .= "t.sticky desc, ";
				}

				foreach($order_array as $key => $order)
				{
					if (in_array($order, array('view_count_one', 'view_count_two', 'view_count_three', 'view_count_four')))
					{
						$view_ct = substr($order, 10);
						$order	 = "view_count";
					}

					if ($key > 0) $end .= ", ";

					switch ($order)
					{
						case 'entry_id' :
							$end .= "t.entry_id";
						break;

						case 'date' : 
							$end .= "t.entry_date";
						break;

						case 'edit_date' : 
							$end .= "t.edit_date";
						break;

						case 'expiration_date' : 
							$end .= "t.expiration_date";
						break;

						case 'title' : 
							$end .= "t.title";
						break;

						case 'url_title' : 
							$end .= "t.url_title";
						break;

						case 'view_count' : 
							$vc = $order.$view_ct;

							$end .= " t.{$vc} ".$sort_array[$key];

							if (sizeof($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'comment_total' : 
							$end .= "t.comment_total ".$sort_array[$key];

							if (sizeof($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'most_recent_comment' : 
							$end .= "t.recent_comment_date ".$sort_array[$key];

							if (sizeof($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'username' : 
							$end .= "m.username";
						break;

						case 'screen_name' : 
							$end .= "m.screen_name";
						break;

						case 'custom_field' :

							if (strpos($corder[$key], '|'))
							{
								$end .= "CONCAT(wd.field_id_".implode(", wd.field_id_", explode('|', $corder[$key])).")";
							}
							else
							{
								$end .= "wd.field_id_".$corder[$key];
							}
						break;

						case 'random' : 
								$end = "ORDER BY rand()";  
								$sort_array[$key] = FALSE;
						break;

						default       : 
							$end .= "t.entry_date";
						break;
					}

					if ($sort_array[$key] == 'asc' || $sort_array[$key] == 'desc')
					{
						// keep entries with the same timestamp in the correct order
						$end .= " {$sort_array[$key]}";
					}
				}
			}

			// In the event of a sorted field containing identical information as another
			// entry (title, entry_date, etc), they will sort on the order they were entered
			// into ExpressionEngine, with the first "sort" parameter taking precedence. 
			// If no sort parameter is set, entries will descend by entry id.
			if ( ! in_array('entry_id', $order_array))
			{
				$end .= ", t.entry_id ".$entry_id_sort;			
			}	
		}

		/** ----------------------------------------
		/**  Determine the row limits
		/** ----------------------------------------*/
		// Even thouth we don't use the LIMIT clause until the end,
		// we need it to help create our pagination links so we'll
		// set it here
                
		if ($cat_id  != '' && is_numeric($TMPL->fetch_param('cat_limit')))
		{
			$this->p_limit = $TMPL->fetch_param('cat_limit');
		}
		elseif ($month != '' && is_numeric($TMPL->fetch_param('month_limit')))
		{
			$this->p_limit = $TMPL->fetch_param('month_limit');
		}
		else
		{
			$this->p_limit  = ( ! is_numeric($TMPL->fetch_param('limit')))  ? $this->limit : $TMPL->fetch_param('limit');
		}

        /** ----------------------------------------------
        /**  Is there an offset?
        /** ----------------------------------------------*/
		// We do this hear so we can use the offset into next, then later one as well
		$offset = ( ! $TMPL->fetch_param('offset') OR ! is_numeric($TMPL->fetch_param('offset'))) ? '0' : $TMPL->fetch_param('offset');

		/** ----------------------------------------
		/**  Do we need pagination?
		/** ----------------------------------------*/
		
		// We'll run the query to find out
		
		if ($this->paginate == TRUE)
		{		
			if ($this->field_pagination == FALSE)
			{			
				$this->pager_sql = $sql_a.$sql_b.$sql;
				$query = $DB->query($this->pager_sql);
				$total = $query->num_rows;
												
				// Adjust for offset
				if ($total >= $offset)
					$total = $total - $offset;
				
				$this->create_pagination($total);
			}
			else
			{
				$this->pager_sql = $sql_a.$sql_b.$sql;
				
				$query = $DB->query($this->pager_sql);
				
				$total = $query->num_rows;
				
				$this->create_pagination($total, $query);
				
				if ($PREFS->ini('enable_sql_caching') == 'y')
				{			
					$this->save_cache($this->pager_sql, 'pagination_query');
					$this->save_cache('1', 'field_pagination');
				}
			}
					
			if ($PREFS->ini('enable_sql_caching') == 'y')
			{			
				$this->save_cache($total, 'pagination_count');
			}
		}
               
        /** ----------------------------------------------
        /**  Add Limits to query
        /** ----------------------------------------------*/
	
		$sql .= $end;
		
		if ($this->paginate == FALSE)
			$this->p_page = 0;

		// Adjust for offset
		$this->p_page += $offset;

		if ($this->display_by == '')
		{ 
			if (($page_marker == FALSE AND $this->p_limit != '') || ($page_marker == TRUE AND $this->field_pagination != TRUE))
			{
				$sql .= ($this->p_page == '') ? " LIMIT ".$offset.', '.$this->p_limit : " LIMIT ".$this->p_page.', '.$this->p_limit;  
			}
			elseif ($entry_id == '' AND $qtitle == '')
			{ 
				$sql .= ($this->p_page == '') ? " LIMIT ".$this->limit : " LIMIT ".$this->p_page.', '.$this->limit;
			}
		}
		else
		{
			if ($offset != 0)
			{
				$sql .= ($this->p_page == '') ? " LIMIT ".$offset.', '.$this->p_limit : " LIMIT ".$this->p_page.', '.$this->p_limit;  
			}
		}
 
        /** ----------------------------------------------
        /**  Fetch the entry_id numbers
        /** ----------------------------------------------*/
                
		$query = $DB->query($sql_a.$sql_b.$sql);  
		
		//exit($sql_a.$sql_b.$sql);
        
        if ($query->num_rows == 0)
        {
			$this->sql = '';
			return;
        }
        		        
        /** ----------------------------------------------
        /**  Build the full SQL query
        /** ----------------------------------------------*/
        
        $this->sql = "SELECT ";

        if ($TMPL->fetch_param('category') || $TMPL->fetch_param('category_group') || $cat_id != '')                      
        {
        	// Using DISTINCT like this is bogus but since
        	// FULL OUTER JOINs are not supported in older versions
        	// of MySQL it's our only choice
        
			$this->sql .= " DISTINCT(t.entry_id), ";
        }
        
		if ($this->display_by == 'week' && isset($yearweek))
		{
			$this->sql .= $yearweek.', ';
		}
		
        // DO NOT CHANGE THE ORDER
        // The exp_member_data table needs to be called before the exp_members table.
	
		$this->sql .= " t.entry_id, t.weblog_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.dst_enabled, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.allow_trackbacks, t.sticky, t.entry_date, t.year, t.month, t.day, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.trackback_total, t.sent_trackbacks, t.recent_trackback_date, t.site_id as entry_site_id,
						w.blog_title, w.blog_name, w.blog_url, w.comment_url, w.tb_return_url, w.comment_moderate, w.weblog_html_formatting, w.weblog_allow_img_urls, w.weblog_auto_link_urls, w.enable_trackbacks, w.trackback_use_url_title, w.trackback_field, w.trackback_use_captcha, w.trackback_system_enabled, 
						m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
						md.*,
						wd.*
				FROM exp_weblog_titles		AS t
				LEFT JOIN exp_weblogs 		AS w  ON t.weblog_id = w.weblog_id 
				LEFT JOIN exp_weblog_data	AS wd ON t.entry_id = wd.entry_id 
				LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id 
				LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id ";

        $this->sql .= "WHERE t.entry_id IN (";
        
        $entries = array();
        
        // Build ID numbers (checking for duplicates)
        
        foreach ($query->result as $row)
        {        	
			if ( ! isset($entries[$row['entry_id']]))
			{
				$entries[$row['entry_id']] = 'y';
			}
			else
			{
				continue;
			}
        	
        	$this->sql .= $row['entry_id'].',';
        }
        
        unset($query);
        unset($entries);
        	
		$this->sql = substr($this->sql, 0, -1).') ';
		
		// modify the ORDER BY if displaying by week		
		if ($this->display_by == 'week' && isset($yearweek))
		{		
			$weeksort = ($TMPL->fetch_param('week_sort') == 'desc') ? 'DESC' : 'ASC';
			$end = str_replace('ORDER BY ', 'ORDER BY yearweek '.$weeksort.', ', $end);
		}
		
		$this->sql .= $end;        
    }    
    /* END */




	/** ----------------------------------------
	/**  Create pagination
	/** ----------------------------------------*/

	function create_pagination($count = 0, $query = '')
	{
		global $FNS, $TMPL, $IN, $REGX, $EXT, $PREFS, $SESS;
		
		// -------------------------------------------
		// 'weblog_module_create_pagination' hook.
		//  - Rewrite the pagination function in the Weblog module
		//  - Could be used to expand the kind of pagination available
		//  - Paginate via field length, for example
		//
			if ($EXT->active_hook('weblog_module_create_pagination') === TRUE)
			{
				$edata = $EXT->universal_call_extension('weblog_module_create_pagination', $this);
				if ($EXT->end_script === TRUE) return;
			}
		//
        // -------------------------------------------
		
		
		if ($this->paginate == TRUE)
		{
			/* --------------------------------------
			/*  For subdomain's or domains using $template_group and $template
			/*  in path.php, the pagination for the main index page requires
			/*  that the template group and template are specified.
			/* --------------------------------------*/
		
			if (($IN->URI == '' OR $IN->URI == '/') && $PREFS->ini('template_group') != '' && $PREFS->ini('template') != '')
			{
				$this->basepath = $FNS->create_url($PREFS->ini('template_group').'/'.$PREFS->ini('template'), 1);
			}
			
			if ($this->basepath == '')
			{
				$this->basepath = $FNS->create_url($IN->URI, 1);
				
				if (preg_match("#^P(\d+)|/P(\d+)#", $this->QSTR, $match))
				{					
					$this->p_page = (isset($match['2'])) ? $match['2'] : $match['1'];	
					$this->basepath = $FNS->remove_double_slashes(str_replace($match['0'], '', $this->basepath));
				}				
			}
		
			/** ----------------------------------------
			/**  Standard pagination - base values
			/** ----------------------------------------*/
		
			if ($this->field_pagination == FALSE)
			{
				if ($this->display_by == '')
				{					
					if ($count == 0)
					{
						$this->sql = '';
						return;
					}
				
					$this->total_rows = $count;
				}
				
				if ($this->dynamic_sql == FALSE)
				{
					$cat_limit = FALSE;
					if ((in_array($this->reserved_cat_segment, explode("/", $IN->URI)) 
						AND $TMPL->fetch_param('dynamic') != 'off' 
						AND $TMPL->fetch_param('weblog'))
						|| (preg_match("#(^|\/)C(\d+)#", $IN->URI, $match) AND $TMPL->fetch_param('dynamic') != 'off'))
					{		
						$cat_limit = TRUE;
					}
					
					if ($cat_limit && is_numeric($TMPL->fetch_param('cat_limit')))
					{
						$this->p_limit = $TMPL->fetch_param('cat_limit');
					}
					else
					{
						$this->p_limit  = ( ! is_numeric($TMPL->fetch_param('limit')))  ? $this->limit : $TMPL->fetch_param('limit');				
					}	
				}
				
				$this->p_page = ($this->p_page == '' || ($this->p_limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;
				
				if ($this->p_page > $this->total_rows)
				{
					$this->p_page = 0;
				}
								
				$this->current_page = floor(($this->p_page / $this->p_limit) + 1);
				
				$this->total_pages = intval(floor($this->total_rows / $this->p_limit));
			}
			else
			{
				/** ----------------------------------------
				/**  Field pagination - base values
				/** ----------------------------------------*/
										
				if ($count == 0)
				{
					$this->sql = '';
					return;
				}
						
				$m_fields = array();
				
				foreach ($this->multi_fields as $val)
				{
					foreach($this->cfields as $site_id => $cfields)
					{
						if (isset($cfields[$val]))
						{
							if (isset($query->row['field_id_'.$cfields[$val]]) AND $query->row['field_id_'.$cfields[$val]] != '')
							{ 
								$m_fields[] = $val;
							}
						}
					}
				}
														
				$this->p_limit = 1;
				
				$this->total_rows = count($m_fields);

				$this->total_pages = $this->total_rows;
				
				if ($this->total_pages == 0)
					$this->total_pages = 1;
				
				$this->p_page = ($this->p_page == '') ? 0 : $this->p_page;
				
				if ($this->p_page > $this->total_rows)
				{
					$this->p_page = 0;
				}
				
				$this->current_page = floor(($this->p_page / $this->p_limit) + 1);
				
				if (isset($m_fields[$this->p_page]))
				{
					$TMPL->tagdata = preg_replace("/".LD."multi_field\=[\"'].+?[\"']".RD."/s", LD.$m_fields[$this->p_page].RD, $TMPL->tagdata);
					$TMPL->var_single[$m_fields[$this->p_page]] = $m_fields[$this->p_page];
				}
			}
					
			/** ----------------------------------------
			/**  Create the pagination
			/** ----------------------------------------*/
			
			if ($this->total_rows % $this->p_limit) 
			{
				$this->total_pages++;
			}	
			
			if ($this->total_rows > $this->p_limit)
			{
				if ( ! class_exists('Paginate'))
				{
					require PATH_CORE.'core.paginate'.EXT;
				}
				
				$PGR = new Paginate();
				
				if ( ! stristr($this->basepath, SELF) AND $PREFS->ini('site_index') != '')
				{
					$this->basepath .= SELF.'/';
				}
																	
				if ($TMPL->fetch_param('paginate_base'))
				{				
					$this->basepath = $FNS->create_url($REGX->trim_slashes($TMPL->fetch_param('paginate_base')));
				}				

				$first_url = (preg_match("#\.php/$#", $this->basepath)) ? substr($this->basepath, 0, -1) : $this->basepath;
								
				$PGR->first_url 	= $first_url;
				$PGR->path			= $this->basepath;
				$PGR->prefix		= 'P';
				$PGR->total_count 	= $this->total_rows;
				$PGR->per_page		= $this->p_limit;
				$PGR->cur_page		= $this->p_page;

				$this->pagination_links = $PGR->show_links();
				
				if ((($this->total_pages * $this->p_limit) - $this->p_limit) > $this->p_page)
				{
					$this->page_next = $this->basepath.'P'.($this->p_page + $this->p_limit).'/';
				}
				
				if (($this->p_page - $this->p_limit ) >= 0) 
				{						
					$this->page_previous = $this->basepath.'P'.($this->p_page - $this->p_limit).'/';
				}
			}
			else
			{
				$this->p_page = '';
			}
		}
	}
	/* END */
	


    /** ----------------------------------------
    /**  Parse weblog entries
    /** ----------------------------------------*/

    function parse_weblog_entries()
    {
        global $IN, $DB, $TMPL, $FNS, $SESS, $LOC, $PREFS, $REGX, $EXT;
        
        $switch = array();
                        
        /** ----------------------------------------
        /**  Set default date header variables
        /** ----------------------------------------*/

        $heading_date_hourly  = 0;
        $heading_flag_hourly  = 0;
        $heading_flag_weekly  = 1;
        $heading_date_daily   = 0;
        $heading_flag_daily   = 0;
        $heading_date_monthly = 0;
        $heading_flag_monthly = 0;
        $heading_date_yearly  = 0;
        $heading_flag_yearly  = 0;
                
        /** ----------------------------------------
        /**  Fetch the "category chunk"
        /** ----------------------------------------*/
        
        // We'll grab the category data now to avoid processing cycles in the foreach loop below
        
        $cat_chunk = array();
        
        if (preg_match_all("/".LD."categories(.*?)".RD."(.*?)".LD.SLASH.'categories'.RD."/s", $TMPL->tagdata, $matches))
        {
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$cat_chunk[] = array($matches['2'][$j], $FNS->assign_parameters($matches['1'][$j]), $matches['0'][$j]);
			}
      	}
      	      	
        /** ----------------------------------------
        /**  Fetch all the date-related variables
        /** ----------------------------------------*/
        
        $entry_date 		= array();
        $gmt_date 			= array();
        $gmt_entry_date		= array();
        $edit_date 			= array();
        $gmt_edit_date		= array();
        $expiration_date	= array();
		$week_date			= array();
        
        // We do this here to avoid processing cycles in the foreach loop
        
        $date_vars = array('entry_date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date', 'expiration_date', 'recent_comment_date', 'week_date');
                
		foreach ($date_vars as $val)
		{					
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(array(LD,RD), '', $matches['0'][$j]);
					
					switch ($val)
					{
						case 'entry_date' 			: $entry_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_date'				: $gmt_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_entry_date'		: $gmt_entry_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'edit_date' 			: $edit_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_edit_date'		: $gmt_edit_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'expiration_date' 		: $expiration_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'recent_comment_date' 	: $recent_comment_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
						case 'week_date'			: $week_date[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}
      	
      	// Are any of the custom fields dates?
      	
      	$custom_date_fields = array();
      	
      	if (count($this->dfields) > 0)
      	{
      		foreach ($this->dfields as $site_id => $dfields)
      		{
      			foreach($dfields as $key => $value)
      			{
					if (preg_match_all("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $TMPL->tagdata, $matches))
					{
						for ($j = 0; $j < count($matches['0']); $j++)
						{
							$matches['0'][$j] = str_replace(array(LD,RD), '', $matches['0'][$j]);
							
							$custom_date_fields[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
						}
					}
				}
			}
      	}

		// And the same again for reverse related entries

		$reverse_markers = array();
		
		if (preg_match_all("/".LD."REV_REL\[([^\]]+)\]REV_REL".RD."/", $TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$reverse_markers[$matches['1'][$j]] = '';
			}
		}
      	
        /** ----------------------------------------
        /**  "Search by Member" link
        /** ----------------------------------------*/
		// We use this with the {member_search_path} variable
		
		$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $TMPL->tagdata, $match)) ? $match['1'] : 'search/results';
		$result_path = str_replace(array("\"","'"), "", $result_path);
		
		$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
		$search_link = $FNS->fetch_site_index(0, 0).$qs.'ACT='.$FNS->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';
      	
        /** ----------------------------------------
        /**  Start the main processing loop
        /** ----------------------------------------*/

		// -------------------------------------------
		// 'weblog_entries_query_result' hook.
		//  - Take the whole query object, do what you wish
		//  - added 1.6.7
		//
			if ($EXT->active_hook('weblog_entries_query_result') === TRUE)
			{
				$this->query = $EXT->call_extension('weblog_entries_query_result', $this, $this->query);
				if ($EXT->end_script === TRUE) return $TMPL->tagdata;
			}
		//
		// -------------------------------------------

        $tb_captcha = TRUE;
        $total_results = count($this->query->result);
        
        $site_pages = $PREFS->ini('site_pages');

		foreach ($this->query->result as $count => $row)
        {         
            // Fetch the tag block containing the variables that need to be parsed
        
            $tagdata = $TMPL->tagdata;

			$row['count']				= $count+1;
			$row['page_uri']			= '';
			$row['page_url']			= '';
			$row['total_results']		= $total_results;
			$row['absolute_count']		= $this->p_page + $row['count'];
            
            if ($site_pages !== FALSE && isset($site_pages[$row['site_id']]['uris'][$row['entry_id']]))
            {
            	$row['page_uri'] = $site_pages[$row['site_id']]['uris'][$row['entry_id']];
            	$row['page_url'] = $FNS->create_page_url($site_pages[$row['site_id']]['url'], $site_pages[$row['site_id']]['uris'][$row['entry_id']]);
            }

            // -------------------------------------------
			// 'weblog_entries_tagdata' hook.
			//  - Take the entry data and tag data, do what you wish
			//
				if ($EXT->active_hook('weblog_entries_tagdata') === TRUE)
				{
					$tagdata = $EXT->call_extension('weblog_entries_tagdata', $tagdata, $row, $this);
					if ($EXT->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			// -------------------------------------------
			// 'weblog_entries_row' hook.
			//  - Take the entry data, do what you wish
			//  - added 1.6.7
			//
				if ($EXT->active_hook('weblog_entries_row') === TRUE)
				{
					$row = $EXT->call_extension('weblog_entries_row', $this, $row);
					if ($EXT->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			/** ----------------------------------------
            /**  Trackback RDF?
            /** ----------------------------------------*/
			
			if ($TMPL->fetch_param('rdf') == "on")
			{
				$this->display_tb_rdf = TRUE;
			}
			elseif ($TMPL->fetch_param('rdf') == "off")
			{
				$this->display_tb_rdf = FALSE;                    	
			}
			elseif ($row['enable_trackbacks'] == 'y')
			{
				$this->display_tb_rdf = TRUE;
			}
			else
			{
				$this->display_tb_rdf = FALSE;
			}
              
            /** ----------------------------------------
            /**  Adjust dates if needed
            /** ----------------------------------------*/
            
            // If the "dst_enabled" item is set in any given entry
            // we need to offset to the timestamp by an hour
            
            if ( ! isset($row['dst_enabled']))
            	$row['dst_enabled'] = 'n';
              
			if ($row['entry_date'] != '')
				$row['entry_date'] = $LOC->offset_entry_dst($row['entry_date'], $row['dst_enabled'], FALSE);

			if ($row['expiration_date'] != '' AND $row['expiration_date'] != 0)
				$row['expiration_date'] = $LOC->offset_entry_dst($row['expiration_date'], $row['dst_enabled'], FALSE);
				
			if ($row['comment_expiration_date'] != '' AND $row['comment_expiration_date'] != 0)
				$row['comment_expiration_date'] = $LOC->offset_entry_dst($row['comment_expiration_date'], $row['dst_enabled'], FALSE);				
                
			/** ------------------------------------------
			/**  Reset custom date fields
			/** ------------------------------------------*/
			
			// Since custom date fields columns are integer types by default, if they 
			// don't contain any data they return a zero.
			// This creates a problem if conditionals are used with those fields.
			// For example, if an admin has this in a template:  {if mydate == ''}
			// Since the field contains a zero it would never evaluate true.
			// Therefore we'll reset any zero dates to nothing.
												
			if (isset($this->dfields[$row['site_id']]) && count($this->dfields[$row['site_id']]) > 0)
			{
				foreach ($this->dfields[$row['site_id']] as $dkey => $dval)
				{	
					// While we're at it, kill any formatting
					$row['field_ft_'.$dval] = 'none';
					if (isset($row['field_id_'.$dval]) AND $row['field_id_'.$dval] == 0)
					{
						$row['field_id_'.$dval] = '';
					}
				}
			}
			// While we're at it, do the same for related entries.
			if (isset($this->rfields[$row['site_id']]) && count($this->rfields[$row['site_id']]) > 0)
			{
				foreach ($this->rfields[$row['site_id']] as $rkey => $rval)
				{	
					$row['field_ft_'.$rval] = 'none';
				}
			}

			// Reverse related markers
			$j = 0;

			foreach ($reverse_markers as $k => $v)
			{
				$this->reverse_related_entries[$row['entry_id']][$j] = $k;
				$tagdata = str_replace(	LD."REV_REL[".$k."]REV_REL".RD, LD."REV_REL[".$k."][".$row['entry_id']."]REV_REL".RD, $tagdata);
				$j++;
			}
				
            /** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			
			$cond = $row;
			$cond['logged_in']			= ($SESS->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= ($SESS->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			
			if (($row['comment_expiration_date'] > 0 && $LOC->now > $row['comment_expiration_date']) OR $row['allow_comments'] == 'n')
			{
				$cond['allow_comments'] = 'FALSE'; 
			}
			else
			{
				$cond['allow_comments'] = 'TRUE';  
			}
			
			foreach (array('avatar_filename', 'photo_filename', 'sig_img_filename') as $pv)
			{
				if ( ! isset($row[$pv]))
					$row[$pv] = '';
			}
			
			$cond['allow_trackbacks']		= ($row['allow_trackbacks'] == 'n' OR $row['trackback_system_enabled'] == '') ? 'FALSE' : 'TRUE';
			$cond['signature_image']		= ($row['sig_img_filename'] == '' OR $PREFS->ini('enable_signatures') == 'n' OR $SESS->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']					= ($row['avatar_filename'] == '' OR $PREFS->ini('enable_avatars') == 'n' OR $SESS->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']					= ($row['photo_filename'] == '' OR $PREFS->ini('enable_photos') == 'n' OR $SESS->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['forum_topic']			= ($row['forum_topic_id'] == 0) ? 'FALSE' : 'TRUE';
			$cond['not_forum_topic']		= ($row['forum_topic_id'] != 0) ? 'FALSE' : 'TRUE';
			$cond['comment_tb_total']		= $row['comment_total'] + $row['trackback_total'];
			$cond['category_request']		= ($this->cat_request === FALSE) ? 'FALSE' : 'TRUE';
			$cond['not_category_request']	= ($this->cat_request !== FALSE) ? 'FALSE' : 'TRUE';
			$cond['weblog']					= $row['blog_title'];
			$cond['weblog_short_name']		= $row['blog_name'];
			$cond['author']					= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$cond['photo_url']				= $PREFS->ini('photo_url', 1).$row['photo_filename'];
			$cond['photo_image_width']		= $row['photo_width'];
			$cond['photo_image_height']		= $row['photo_height'];
			$cond['avatar_url']				= $PREFS->ini('avatar_url', 1).$row['avatar_filename'];
			$cond['avatar_image_width']		= $row['avatar_width'];
			$cond['avatar_image_height']	= $row['avatar_height'];	
			$cond['signature_image_url']	= $PREFS->ini('sig_img_url', 1).$row['sig_img_filename'];
			$cond['signature_image_width']	= $row['sig_img_width'];
			$cond['signature_image_height']	= $row['sig_img_height'];
			$cond['relative_date']			= $LOC->format_timespan($LOC->now - $row['entry_date']);
			
			if (isset($this->cfields[$row['site_id']]))
			{
				foreach($this->cfields[$row['site_id']] as $key => $value)
				{
					$cond[$key] = ( ! isset($row['field_id_'.$value])) ? '' : $row['field_id_'.$value];
				}
			}
			
			foreach($this->mfields as $key => $value)
			{
				if (isset($row['m_field_id_'.$value['0']]))
				{
					$cond[$key] = $this->TYPE->parse_type($row['m_field_id_'.$value['0']],
														  array(
																'text_format'   => $value['1'],
																'html_format'   => 'safe',
																'auto_links'    => 'y',
																'allow_img_url' => 'n'
															  )
														 );	
				}
			}
			
			$tagdata = $FNS->prep_conditionals($tagdata, $cond);

            /** ----------------------------------------
            /**  Parse Variable Pairs
            /** ----------------------------------------*/

           foreach ($TMPL->var_pair as $key => $val)
            {     
                /** ----------------------------------------
                /**  parse categories
                /** ----------------------------------------*/
                
                if (strncmp('categories', $key, 10) == 0)
                {
                    if (isset($this->categories[$row['entry_id']]) AND is_array($this->categories[$row['entry_id']]) AND count($cat_chunk) > 0)
                    {	
						foreach ($cat_chunk as $catkey => $catval)
						{
							$i = 0;
							
							$cats = '';
							
							if (isset($catval['1']['limit']))
							{
								$cat_limit = $catval['1']['limit'];
							}
							else
							{
								$cat_limit = FALSE;
							}
							
							//  We do the pulling out of categories before the "prepping" of conditionals
							//  So, we have to do it here again too.  How annoying...
							$catval[0] = $FNS->prep_conditionals($catval[0], $cond);
							$catval[2] = $FNS->prep_conditionals($catval[2], $cond);
							
							$not_these		  = array();
							$these			  = array();
							$not_these_groups = array();
							$these_groups	  = array();
							
							if (isset($catval['1']['show']))
							{
								if (strncmp('not ', $catval[1]['show'], 4) == 0)
								{
									$not_these = explode('|', trim(substr($catval['1']['show'], 3)));
								}
								else
								{
									$these = explode('|', trim($catval['1']['show']));
								}
							}
								
							if (isset($catval['1']['show_group']))
							{
								if (strncmp('not ', $catval[1]['show_group'], 4) == 0)
								{
									$not_these_groups = explode('|', trim(substr($catval['1']['show_group'], 3)));
								}
								else
								{
									$these_groups = explode('|', trim($catval['1']['show_group']));
								}
							}
								
							foreach ($this->categories[$row['entry_id']] as $k => $v)
							{
								if (in_array($v['0'], $not_these) OR (isset($v['5']) && in_array($v['5'], $not_these_groups)))
								{
									continue;
								}
								elseif( (sizeof($these) > 0 && ! in_array($v['0'], $these)) OR
								 		(sizeof($these_groups) > 0 && isset($v['5']) && ! in_array($v['5'], $these_groups)))
								{
									continue;
								}
								
								$temp = $catval['0'];
							   
								if (preg_match_all("#".LD."path=(.+?)".RD."#", $temp, $matches))
								{
									foreach ($matches['1'] as $match)
									{																				
										if ($this->use_category_names == TRUE)
										{
											$temp = preg_replace("#".LD."path=.+?".RD."#", $FNS->remove_double_slashes($FNS->create_url($match).'/'.$this->reserved_cat_segment.'/'.$v['6'].'/'), $temp, 1);
										}
										else
										{
											$temp = preg_replace("#".LD."path=.+?".RD."#", $FNS->remove_double_slashes($FNS->create_url($match).'/C'.$v['0'].'/'), $temp, 1);
										}
									}
								}
								else
								{							
									$temp = preg_replace("#".LD."path=.+?".RD."#", $FNS->create_url("SITE_INDEX"), $temp);
								}
								
								$cat_vars = array('category_name'			=> $v['2'],
												  'category_url_title'		=> $v['6'],
												  'category_description'	=> (isset($v['4'])) ? $v['4'] : '',
												  'category_group'			=> (isset($v['5'])) ? $v['5'] : '',												  
												  'category_image'			=> $v['3'],
												  'category_id'				=> $v['0'],
												  'parent_id'				=> $v['1']);												

								// add custom fields for conditionals prep

								foreach ($this->catfields as $cv)
								{
									$cat_vars[$cv['field_name']] = ( ! isset($v['field_id_'.$cv['field_id']])) ? '' : $v['field_id_'.$cv['field_id']];
								}
														
								$temp = $FNS->prep_conditionals($temp, $cat_vars);
								
								$temp = str_replace(array(LD."category_id".RD,
														  LD."category_name".RD,
														  LD."category_url_title".RD,
														  LD."category_image".RD,
														  LD."category_group".RD,														  
														  LD.'category_description'.RD,
														  LD.'parent_id'.RD),														
													array($v['0'],
														  $v['2'],
														  $v['6'],
														  $v['3'],
														  (isset($v['5'])) ? $v['5'] : '',														  
														  (isset($v['4'])) ? $v['4'] : '',
														  $v['1']														
														  ),
													$temp);

								foreach($this->catfields as $cv2)
								{
									if (isset($v['field_id_'.$cv2['field_id']]) AND $v['field_id_'.$cv2['field_id']] != '')
									{
										$field_content = $this->TYPE->parse_type($v['field_id_'.$cv2['field_id']],
																					array(
																						  'text_format'		=> $v['field_ft_'.$cv2['field_id']],
																						  'html_format'		=> $v['field_html_formatting'],
																						  'auto_links'		=> 'n',
																						  'allow_img_url'	=> 'y'
																						)
																				);								
										$temp = str_replace(LD.$cv2['field_name'].RD, $field_content, $temp);											
									}
									else
									{
										// garbage collection
										$temp = str_replace(LD.$cv2['field_name'].RD, '', $temp);
									}

									$temp = $FNS->remove_double_slashes($temp);
								}

								$cats .= $temp;

								if ($cat_limit !== FALSE && $cat_limit == ++$i)
								{
									break;
								}
							}
							
							$cats = rtrim(str_replace("&#47;", "/", $cats));
							
							if (is_array($catval['1']) AND isset($catval['1']['backspace']))
							{
								$cats = substr($cats, 0, - $catval['1']['backspace']);
							}							
							
							$cats = str_replace("/", "&#47;", $cats);
													
							$tagdata = str_replace($catval['2'], $cats, $tagdata);                        
						}
                    }
                    else
                    {
                        $tagdata = $TMPL->delete_var_pairs($key, 'categories', $tagdata);
                    }
                }
            
                /** ----------------------------------------
                /**  parse date heading
                /** ----------------------------------------*/
                
                if (strncmp('date_heading', $key, 12) == 0)
                {   
                    // Set the display preference
                    
                    $display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';
                    
                    /** ----------------------------------------
                    /**  Hourly header
                    /** ----------------------------------------*/
                    
                    if ($display == 'hourly')
                    {
                        $heading_date_hourly = date('YmdH', $LOC->set_localized_time($row['entry_date']));
                                                
                        if ($heading_date_hourly == $heading_flag_hourly)
                        {
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_heading', $tagdata);
                        
                            $heading_flag_hourly = $heading_date_hourly;    
                        }
                    } 
   
                    /** ----------------------------------------
                    /**  Weekly header
                    /** ----------------------------------------*/
                    
                    elseif ($display == 'weekly')
                    {    
						$temp_date = $LOC->set_localized_time($row['entry_date']);
                    	
						// date()'s week variable 'W' starts weeks on Monday per ISO-8601.
						// By default we start weeks on Sunday, so we need to do a little dance for
						// entries made on Sundays to make sure they get placed in the right week heading
						if (strtolower($TMPL->fetch_param('start_day')) != 'monday' && date('w', $LOC->set_localized_time($row['entry_date'])) == 0)
						{
							// add 7 days to toss us into the next ISO-8601 week
							$heading_date_weekly = date('YW', $temp_date + 604800);
						}
						else
						{
							$heading_date_weekly = date('YW', $temp_date);
						}
                    	
                        if ($heading_date_weekly == $heading_flag_weekly)
                        {
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_heading', $tagdata);
                            
                            $heading_flag_weekly = $heading_date_weekly;
                        }
                    } 
   
                    /** ----------------------------------------
                    /**  Monthly header
                    /** ----------------------------------------*/

                    elseif ($display == 'monthly')
                    {
                        $heading_date_monthly = date('Ym', $LOC->set_localized_time($row['entry_date']));
                                                
                        if ($heading_date_monthly == $heading_flag_monthly)
                        {
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_heading', $tagdata);
                        
                            $heading_flag_monthly = $heading_date_monthly;    
                        }
                    } 
                    
                    /** ----------------------------------------
                    /**  Yearly header
                    /** ----------------------------------------*/

                    elseif ($display == 'yearly')
                    {
                        $heading_date_yearly = date('Y', $LOC->set_localized_time($row['entry_date']));

                        if ($heading_date_yearly == $heading_flag_yearly)
                        {
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_heading', $tagdata);
                        
                            $heading_flag_yearly = $heading_date_yearly;    
                        }
                    }
                    
                    /** ----------------------------------------
                    /**  Default (daily) header
                    /** ----------------------------------------*/

                    else
                    {
                        $heading_date_daily = date('Ymd', $LOC->set_localized_time($row['entry_date']));

                        if ($heading_date_daily == $heading_flag_daily)
                        {
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_heading', $tagdata);
                        
                            $heading_flag_daily = $heading_date_daily;    
                        }
                    }                    
                }
                // END DATE HEADING
                
                
                /** ----------------------------------------
                /**  parse date footer
                /** ----------------------------------------*/
                
                if (strncmp('date_footer', $key, 11) == 0)
                {   
                    // Set the display preference
                    
                    $display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';
                    
                    /** ----------------------------------------
                    /**  Hourly footer
                    /** ----------------------------------------*/
                    
                    if ($display == 'hourly')
                    {
                        if ( ! isset($this->query->result[$row['count']]) OR 
                        	date('YmdH', $LOC->set_localized_time($row['entry_date'])) != date('YmdH', $LOC->set_localized_time($this->query->result[$row['count']]['entry_date'])))
                        {
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
                        }
                    } 
   
                    /** ----------------------------------------
                    /**  Weekly footer
                    /** ----------------------------------------*/
                    
                    elseif ($display == 'weekly')
                    {
                        if ( ! isset($this->query->result[$row['count']]) OR 
                        	date('YW', $LOC->set_localized_time($row['entry_date'])) != date('YW', $LOC->set_localized_time($this->query->result[$row['count']]['entry_date'])))
                        {
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
                        }
                    } 
   
                    /** ----------------------------------------
                    /**  Monthly footer
                    /** ----------------------------------------*/

                    elseif ($display == 'monthly')
                    {                           
                        if ( ! isset($this->query->result[$row['count']]) OR 
                        	date('Ym', $LOC->set_localized_time($row['entry_date'])) != date('Ym', $LOC->set_localized_time($this->query->result[$row['count']]['entry_date'])))
                        {
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
                        }
                    } 
                    
                    /** ----------------------------------------
                    /**  Yearly footer
                    /** ----------------------------------------*/

                    elseif ($display == 'yearly')
                    {
                        if ( ! isset($this->query->result[$row['count']]) OR 
                        	date('Y', $LOC->set_localized_time($row['entry_date'])) != date('Y', $LOC->set_localized_time($this->query->result[$row['count']]['entry_date'])))
                        {
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
                        }
                    }
                    
                    /** ----------------------------------------
                    /**  Default (daily) footer
                    /** ----------------------------------------*/

                    else
                    {
                        if ( ! isset($this->query->result[$row['count']]) OR 
                        	date('Ymd', $LOC->set_localized_time($row['entry_date'])) != date('Ymd', $LOC->set_localized_time($this->query->result[$row['count']]['entry_date'])))
                        {
                            $tagdata = $TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
                        }
                        else
                        {        
                            $tagdata = $TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
                        }
                    }                    
                }
                // END DATE FOOTER
                
            }
            // END VARIABLE PAIRS
            
            
            
            /** ----------------------------------------
            /**  Parse "single" variables
            /** ----------------------------------------*/

            foreach ($TMPL->var_single as $key => $val)
            {    
                /** ------------------------------------------------
                /**  parse simple conditionals: {body|more|summary}
                /** ------------------------------------------------*/
                
                // Note:  This must happen first.
                
                if (stristr($key, '|') && is_array($val))
                {                
					foreach($val as $item)
					{
						// Basic fields
									
						if (isset($row[$item]) AND $row[$item] != "")
						{                    
							$tagdata = $TMPL->swap_var_single($key, $row[$item], $tagdata);
							
							continue;
						}
		
						// Custom weblog fields
						
						if ( isset( $this->cfields[$row['site_id']][$item] ) AND isset( $row['field_id_'.$this->cfields[$row['site_id']][$item]] ) AND $row['field_id_'.$this->cfields[$row['site_id']][$item]] != "")
						{																											
							$entry = $this->TYPE->parse_type( 
															   $row['field_id_'.$this->cfields[$row['site_id']][$item]], 
															   array(
																		'text_format'   => $row['field_ft_'.$this->cfields[$row['site_id']][$item]],
																		'html_format'   => $row['weblog_html_formatting'],
																		'auto_links'    => $row['weblog_auto_link_urls'],
																		'allow_img_url' => $row['weblog_allow_img_urls']
																	)
															 );
			
							$tagdata = $TMPL->swap_var_single($key, $entry, $tagdata);                
															 
							continue;                                                               
						}
					}
					
					// Garbage collection
					$val = '';
					$tagdata = $TMPL->swap_var_single($key, "", $tagdata);                
                }
            
            
				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/
				
				if (preg_match("/^switch\s*=.+/i", $key))
				{
					$sparam = $FNS->assign_parameters($key);
					
					$sw = '';
					
					if (isset($sparam['switch']))
					{
						$sopt = explode("|", $sparam['switch']);

						$sw = $sopt[($count + count($sopt)) % count($sopt)];

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
                /**  parse entry date
                /** ----------------------------------------*/
                
                if (isset($entry_date[$key]))
                {
					foreach ($entry_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['entry_date'], TRUE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
            
                /** ----------------------------------------
                /**  Recent Comment Date
                /** ----------------------------------------*/

                if (isset($recent_comment_date[$key]))
                {
                    if ($row['recent_comment_date'] != 0)
                    {
						foreach ($recent_comment_date[$key] as $dvar)
							$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['recent_comment_date'], TRUE), $val);					
	
						$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);	
                    }
                    else
                    {
                        $tagdata = str_replace(LD.$key.RD, "", $tagdata); 
                    }                
                }
            
                /** ----------------------------------------
                /**  GMT date - entry date in GMT
                /** ----------------------------------------*/
                
                if (isset($gmt_entry_date[$key]))
                {
					foreach ($gmt_entry_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['entry_date'], FALSE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
                
                if (isset($gmt_date[$key]))
                {
					foreach ($gmt_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['entry_date'], FALSE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }
                                
                /** ----------------------------------------
                /**  parse "last edit" date
                /** ----------------------------------------*/
                
                if (isset($edit_date[$key]))
                {
					foreach ($edit_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $LOC->timestamp_to_gmt($row['edit_date']), TRUE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }                
                
                /** ----------------------------------------
                /**  "last edit" date as GMT
                /** ----------------------------------------*/
                
                if (isset($gmt_edit_date[$key]))
                {
					foreach ($gmt_edit_date[$key] as $dvar)
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $LOC->timestamp_to_gmt($row['edit_date']), FALSE), $val);					

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
                }

                
                /** ----------------------------------------
                /**  parse expiration date
                /** ----------------------------------------*/
                
                if (isset($expiration_date[$key]))
                {
                    if ($row['expiration_date'] != 0)
                    {
						foreach ($expiration_date[$key] as $dvar)
							$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['expiration_date'], TRUE), $val);					
	
						$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);	
                    }
                    else
                    {
                        $tagdata = str_replace(LD.$key.RD, "", $tagdata); 
                    }
                }                


                /** ----------------------------------------
                /**  "week_date"
                /** ----------------------------------------*/
                
				if (isset($week_date[$key]))
				{				
					// Subtract the number of days the entry is "into" the week to get zero (Sunday)
					// If the entry date is for Sunday, and Monday is being used as the week's start day,
					// then we must back things up by six days
			
					$offset = 0;
			
					if (strtolower($TMPL->fetch_param('start_day')) == 'monday')
					{
						$day_of_week = $LOC->convert_timestamp('%w', $row['entry_date'], TRUE);
					
						if ($day_of_week == '0')
						{
							$offset = -518400; // back six days
						}
						else
						{
							$offset = 86400; // plus one day
						}
					}

					$week_start_date = $row['entry_date'] - ($LOC->convert_timestamp('%w', $row['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

					foreach ($week_date[$key] as $dvar)
					{
						$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $week_start_date, TRUE), $val);
					}

					$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);					
				}

              
                /** ----------------------------------------
                /**  parse profile path
                /** ----------------------------------------*/
                
                if (strncmp('profile_path', $key, 12) == 0)
                {
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($FNS->extract_path($key).'/'.$row['member_id']), 
														$tagdata
													 );
                }
                                
                /** ----------------------------------------
                /**  {member_search_path}
                /** ----------------------------------------*/
                
                if (strncmp('member_search_path', $key, 18) == 0)
                {
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$search_link.$row['member_id'], 
														$tagdata
													 );
                }


                /** ----------------------------------------
                /**  parse comment_path or trackback_path
                /** ----------------------------------------*/
                
                if (preg_match("#^(comment_path|trackback_path|entry_id_path)#", $key))
                {                       
					$path = ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX') ? $FNS->extract_path($key).'/'.$row['entry_id'] : $row['entry_id'];

					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($path, 1), 
														$tagdata
													 );
                }

                /** ----------------------------------------
                /**  parse URL title path
                /** ----------------------------------------*/
                
                if (strncmp('url_title_path', $key, 14) == 0)
                { 
					$path = ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX') ? $FNS->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($path, 1), 
														$tagdata
													 );
                }

                /** ----------------------------------------
                /**  parse title permalink
                /** ----------------------------------------*/
                
                if (strncmp('title_permalink', $key, 15) == 0)
                { 
					$path = ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX') ? $FNS->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($path, 1, 0), 
														$tagdata
													 );
                }
                
                /** ----------------------------------------
                /**  parse permalink
                /** ----------------------------------------*/
                
                if (strncmp('permalink', $key, 9) == 0)
                {                     
					$path = ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX') ? $FNS->extract_path($key).'/'.$row['entry_id'] : $row['entry_id'];
                
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$FNS->create_url($path, 1, 0), 
														$tagdata
													 );
                }
           
           
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
                
                if ($key == "comment_url_title_auto_path")
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
                
                if ($key == "comment_entry_id_auto_path")
                {           
                	$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
                	
					$tagdata = $TMPL->swap_var_single(
														$key, 
														$path.$row['entry_id'].'/', 
														$tagdata
													 );
                }
            
                /** ----------------------------------------
                /**  {author}
                /** ----------------------------------------*/
                
                if ($key == "author")
                {
                    $tagdata = $TMPL->swap_var_single($val, ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'], $tagdata);
                }

                /** ----------------------------------------
                /**  {weblog}
                /** ----------------------------------------*/
                
                if ($key == "weblog")
                {
                    $tagdata = $TMPL->swap_var_single($val, $row['blog_title'], $tagdata);
                }
                
                /** ----------------------------------------
                /**  {weblog_short_name}
                /** ----------------------------------------*/
                
                if ($key == "weblog_short_name")
                {
                    $tagdata = $TMPL->swap_var_single($val, $row['blog_name'], $tagdata);
                }
                
                /** ----------------------------------------
                /**  {relative_date}
                /** ----------------------------------------*/
                
                if ($key == "relative_date")
                {
                    $tagdata = $TMPL->swap_var_single($val, $LOC->format_timespan($LOC->now - $row['entry_date']), $tagdata);
                }

                /** ----------------------------------------
                /**  {trimmed_url} - used by Atom feeds
                /** ----------------------------------------*/
                
                if ($key == "trimmed_url")
                {
					$blog_url = (isset($row['blog_url']) AND $row['blog_url'] != '') ? $row['blog_url'] : '';
				
					$blog_url = str_replace(array('http://','www.'), '', $blog_url);
					$xe = explode("/", $blog_url);
					$blog_url = current($xe);
                
                    $tagdata = $TMPL->swap_var_single($val, $blog_url, $tagdata);
                }
                
                /** ----------------------------------------
                /**  {relative_url} - used by Atom feeds
                /** ----------------------------------------*/
                
                if ($key == "relative_url")
                {
					$blog_url = (isset($row['blog_url']) AND $row['blog_url'] != '') ? $row['blog_url'] : '';
					$blog_url = str_replace('http://', '', $blog_url);
                	
					if ($x = strpos($blog_url, "/"))
					{
						$blog_url = substr($blog_url, $x + 1);
					}
					
					$blog_url = rtrim($blog_url, '/');
					
                    $tagdata = $TMPL->swap_var_single($val, $blog_url, $tagdata);
                }
                
                /** ----------------------------------------
                /**  {url_or_email}
                /** ----------------------------------------*/
                
                if ($key == "url_or_email")
                {
                    $tagdata = $TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $row['email'], $tagdata);
                }

                /** ----------------------------------------
                /**  {url_or_email_as_author}
                /** ----------------------------------------*/
                
                if ($key == "url_or_email_as_author")
                {
                    $name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
                    
                    if ($row['url'] != '')
                    {
                        $tagdata = $TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$name."</a>", $tagdata);
                    }
                    else
                    {
                        $tagdata = $TMPL->swap_var_single($val, $this->TYPE->encode_email($row['email'], $name), $tagdata);
                    }
                }
                
                
                /** ----------------------------------------
                /**  {url_or_email_as_link}
                /** ----------------------------------------*/
                
                if ($key == "url_or_email_as_link")
                {                    
                    if ($row['url'] != '')
                    {
                        $tagdata = $TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
                    }
                    else
                    {                        
                        $tagdata = $TMPL->swap_var_single($val, $this->TYPE->encode_email($row['email']), $tagdata);
                    }
                }
               
               
                /** ----------------------------------------
                /**  parse {comment_tb_total}
                /** ----------------------------------------*/
                
                if ($key == 'comment_tb_total')
                {
                    $tagdata = $TMPL->swap_var_single($val, ($row['comment_total'] + $row['trackback_total']), $tagdata);
                }
                
           
                /** ----------------------------------------
                /**  {signature}
                /** ----------------------------------------*/
                
                if ($key == "signature")
                {        
					if ($SESS->userdata('display_signatures') == 'n' OR $row['signature'] == '' OR $SESS->userdata('display_signatures') == 'n')
					{			
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key,
														$this->TYPE->parse_type($row['signature'], array(
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
                /**  parse {title}
                /** ----------------------------------------*/
                
                if ($key == 'title')
                {                      
                	$row['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $row['title']);
                    $tagdata = $TMPL->swap_var_single($val,  $this->TYPE->format_characters($row['title']), $tagdata);
                }
                    
                /** ----------------------------------------
                /**  parse basic fields (username, screen_name, etc.)
                /** ----------------------------------------*/
                 
                if (isset($row[$val]))
                {                    
                    $tagdata = $TMPL->swap_var_single($val, $row[$val], $tagdata);
                }

               
                /** ----------------------------------------
                /**  parse custom date fields
                /** ----------------------------------------*/

                if (isset($custom_date_fields[$key]) && isset($this->dfields[$row['site_id']]))
                {
                	foreach ($this->dfields[$row['site_id']] as $dkey => $dval)
                	{           
                		if (! preg_match("/^".preg_quote($dkey, '/')."\s/", $key))
                			continue;
                			
                		if ($row['field_id_'.$dval] == 0 OR $row['field_id_'.$dval] == '')
                		{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);	
							continue;
                		}
                		
						// use a temporary variable in case the custom date variable is used
						// multiple times with different formats; prevents localization from
						// occurring multiple times on the same value
						$temp_val = $row['field_id_'.$dval];
						
                		$localize = TRUE;
						if (isset($row['field_dt_'.$dval]) AND $row['field_dt_'.$dval] != '')
						{ 
							$localize = TRUE;
							if ($row['field_dt_'.$dval] != '')
							{
								$temp_val = $LOC->offset_entry_dst($temp_val, $row['dst_enabled']);
								$temp_val = $LOC->simpl_offset($temp_val, $row['field_dt_'.$dval]);
								$localize = FALSE;
							}
                		}

						foreach ($custom_date_fields[$key] as $dvar)
							$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $temp_val, $localize), $val);	

							$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);	
                	}
                }
         
                /** ----------------------------------------
                /**  Assign Related Entry IDs
                /** ----------------------------------------*/
				
				// When an entry has related entries within it, since the related entry ID 
				// is stored in the custom field itself we need to pull it out and set it
				// aside so that when the related stuff is parsed out we'll have it.
				// We also need to modify the marker in the template so that we can replace
				// it with the right entry
								
                if (isset($this->rfields[$row['site_id']][$val]))
                {
                	// No relationship?  Ditch the marker
                	if ( !  isset($row['field_id_'.$this->cfields[$row['site_id']][$val]]) OR 
                		 $row['field_id_'.$this->cfields[$row['site_id']][$val]] == 0 OR
                		 ! preg_match_all("/".LD."REL\[".$val."\](.+?)REL".RD."/", $tagdata, $match)
                		)
                	{
						// replace the marker with the {if no_related_entries} content
						preg_match_all("/".LD."REL\[".$val."\](.+?)REL".RD."/", $tagdata, $matches);
						
						foreach ($matches['1'] as $match)
						{
							$tagdata = preg_replace("/".LD."REL\[".$val."\](.+?)REL".RD."/", $TMPL->related_data[$match]['no_rel_content'], $tagdata);
						}
                	}
                	else
                	{
						for ($j = 0; $j < count($match['1']); $j++)
						{						
							$this->related_entries[] = $row['field_id_'.$this->cfields[$row['site_id']][$val]].'_'.$match['1'][$j];
							$tagdata = preg_replace("/".LD."REL\[".$val."\](.+?)REL".RD."/", LD."REL[".$row['field_id_'.$this->cfields[$row['site_id']][$val]]."][".$val."]\\1REL".RD, $tagdata);						
						}
						
						$tagdata = $TMPL->swap_var_single($val, '', $tagdata);                
                	}
				}
               
				// Clean up any unparsed relationship fields
				
				if (isset($this->rfields[$row['site_id']]) && sizeof($this->rfields[$row['site_id']]) > 0)
				{
					$tagdata = preg_replace("/".LD."REL\[".preg_quote($val,'/')."\](.+?)REL".RD."/", "", $tagdata);
				}
				
                /** ----------------------------------------
                /**  parse custom weblog fields
                /** ----------------------------------------*/
                                
                if (isset($this->cfields[$row['site_id']][$val]))
                {
                	if ( ! isset($row['field_id_'.$this->cfields[$row['site_id']][$val]]) OR $row['field_id_'.$this->cfields[$row['site_id']][$val]] == '')
                	{
						$entry = '';               
                	}
                	else
                	{
						// This line of code fixes a very odd bug that happens when you place EE tags in weblog entries
						// For some inexplicable reason, we have to convert the tag to entities before sending it to 
						// the typography class below or else it tries to get parsed as a tag.  What is totally baffling
						// is that the typography class converts tags, so we shouldn't have to do it here.  I can't
						// figure out a solution, however.
					
							$entry = $this->TYPE->parse_type( 
																$REGX->encode_ee_tags($row['field_id_'.$this->cfields[$row['site_id']][$val]]), 
																array(
																		'text_format'   => $row['field_ft_'.$this->cfields[$row['site_id']][$val]],
																		'html_format'   => $row['weblog_html_formatting'],
																		'auto_links'    => $row['weblog_auto_link_urls'],
																		'allow_img_url' => $row['weblog_allow_img_urls']
																	  )
															  );
                     	}

					// prevent accidental parsing of other weblog variables in custom field data
					if (strpos($entry, '{') !== FALSE)
					{
	                    $tagdata = $TMPL->swap_var_single($val, str_replace(array('{', '}'), array('60ba4b2daa4ed4', 'c2b7df6201fdd3'), $entry), $tagdata);
					}
					else
					{
	                    $tagdata = $TMPL->swap_var_single($val, $entry, $tagdata);
					}
                }
                
                /** ----------------------------------------
                /**  parse custom member fields
                /** ----------------------------------------*/
                
                if ( isset( $this->mfields[$val]) AND isset($row['m_field_id_'.$this->mfields[$val]['0']]))
                {                
                    $tagdata = $TMPL->swap_var_single(
                                                        $val, 
                                                        $this->TYPE->parse_type( 
																				$row['m_field_id_'.$this->mfields[$val]['0']], 
																				array(
																						'text_format'   => $this->mfields[$val]['1'],
																						'html_format'   => 'safe',
																						'auto_links'    => 'y',
																						'allow_img_url' => 'n'
																					  )
																			  ), 
                                                        $tagdata
                                                      );
                }
               

            }
            // END SINGLE VARIABLES 

			// do we need to replace any curly braces that we protected in custom fields?
			if (strpos($tagdata, '60ba4b2daa4ed4') !== FALSE)
			{
				$tagdata = str_replace(array('60ba4b2daa4ed4', 'c2b7df6201fdd3'), array('{', '}'), $tagdata);
			}
               
            /** ----------------------------------------
            /**  Compile trackback data
            /** ----------------------------------------*/
                        
            if ($this->display_tb_rdf == TRUE)
            {
                $categories = '';
                
                if (isset($this->categories[$row['entry_id']]))
                {                    
                    if (is_array($this->categories[$row['entry_id']]))
                    {
                        foreach ($this->categories[$row['entry_id']] as $k => $v)
                        {
                            $categories .= $REGX->xml_convert($v['2']).',';
                        } 
                        
                        $categories = substr($categories, 0, -1);                           
                    }
                }
                
                
				/** ----------------------------------------
				/**  Build Trackback RDF
				/** ----------------------------------------*/
								
				if ($row['trackback_use_captcha'] == 'y' AND $tb_captcha == TRUE)
				{			
					$this->tb_captcha_hash = $FNS->random('alpha', 8);
					
					$DB->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".$IN->IP."', '".$this->tb_captcha_hash."')");
					
					$this->tb_captcha_hash .= '/';
					
					$tb_captcha = FALSE;
				}
				
				$ret_url = ($row['tb_return_url'] == '') ? $row['blog_url'] : $row['tb_return_url'];
            
				$this->TYPE->encode_email = FALSE;		
				
				$tb_desc = $this->TYPE->parse_type( 
												   $FNS->char_limiter((isset($row['field_id_'.$row['trackback_field']])) ? $row['field_id_'.$row['trackback_field']] : ''), 
												   array(
															'text_format'   => 'none',
															'html_format'   => 'none',
															'auto_links'    => 'n',
															'allow_img_url' => 'y'
														)
												 );
					 
				$row['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $row['title']);
				$identifier = ($row['trackback_use_url_title'] == 'y') ? $row['url_title'] : $row['entry_id'];
															 
                $TB = array(
                             'about'        => $FNS->remove_double_slashes($ret_url.'/'.$identifier.'/'),
                             'ping'         => $FNS->fetch_site_index(1, 0).'trackback/'.$row['entry_id'].'/'.$this->tb_captcha_hash,
                             'title'        => $REGX->xml_convert($row['title']),
                             'identifier'   => $FNS->remove_double_slashes($ret_url.'/'.$identifier.'/'),
                             'subject'      => $REGX->xml_convert($categories),
                             'description'  => $REGX->xml_convert($tb_desc),
                             'creator'      => $REGX->xml_convert(($row['screen_name'] != '') ? $row['screen_name'] : $row['username']),
                             'date'         => $LOC->set_human_time($row['entry_date'], 0, 1).' GMT'
                            );
            
                $tagdata .= $this->trackback_rdf($TB);    
                
                $this->display_tb_rdf = FALSE;        
            }
            
            // -------------------------------------------
			// 'weblog_entries_tagdata_end' hook.
			//  - Take the final results of an entry's parsing and do what you wish
			//
				if ($EXT->active_hook('weblog_entries_tagdata_end') === TRUE)
				{
					$tagdata = $EXT->call_extension('weblog_entries_tagdata_end', $tagdata, $row, $this);
					if ($EXT->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------
                        
            $this->return_data .= $tagdata;
            
        }
        // END FOREACH LOOP
        
        // Kill multi_field variable        
        $this->return_data = preg_replace("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", "", $this->return_data);
        
        // Do we have backspacing?
        // This can only be used when RDF data is not present.
        
		if ($back = $TMPL->fetch_param('backspace') AND $this->display_tb_rdf != TRUE)
		{
			if (is_numeric($back))
			{
				$this->return_data = rtrim(str_replace("&#47;", "/", $this->return_data));
				$this->return_data = substr($this->return_data, 0, - $back);
				$this->return_data = str_replace("/", "&#47;", $this->return_data);
			}
		}		
    }
    /* END */



    /** ----------------------------------------
    /**  Weblog Info Tag
    /** ----------------------------------------*/

    function info()
    {
        global $TMPL, $DB, $LANG;
        
        if ( ! $blog_name = $TMPL->fetch_param('weblog'))
        {
        	return '';
        }
        
        if (count($TMPL->var_single) == 0)
        {
        	return '';
        }
        
        $params = array(
        					'blog_title',
        					'blog_url',
        					'blog_description',
        					'blog_lang',
        					'blog_encoding'
        					);        
        
        $q = '';
        
		foreach ($TMPL->var_single as $val)
		{
			if (in_array($val, $params))
			{
				$q .= $val.',';
			}
		}
        
        $q = substr($q, 0, -1);
        
        if ($q == '')
        		return '';
        

		$sql = "SELECT ".$q." FROM exp_weblogs ";
				
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n' AND site_id IN ('".implode("','", $TMPL->site_ids)."') ";
		
			if ($blog_name != '')
			{
				$sql .= " AND blog_name = '".$DB->escape_str($blog_name)."'";
			}
		}
				
		$query = $DB->query($sql);

		if ($query->num_rows != 1)
		{
			return '';
		}
		
		foreach ($query->row as $key => $val)
		{
			$TMPL->tagdata = str_replace(LD.$key.RD, $val, $TMPL->tagdata);
		}

		return $TMPL->tagdata;
	}
	/* END */
	


    /** ----------------------------------------
    /**  Weblog Name
    /** ----------------------------------------*/

    function weblog_name()
    {
        global $TMPL, $DB, $LANG;

		$blog_name = $TMPL->fetch_param('weblog');
		
		if (isset($this->weblog_name[$blog_name]))
		{
			return $this->weblog_name[$blog_name];
		}

		$sql = "SELECT blog_title FROM exp_weblogs ";
				
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n' AND site_id IN ('".implode("','", $TMPL->site_ids)."') ";
		
			if ($blog_name != '')
			{
				$sql .= " AND blog_name = '".$DB->escape_str($blog_name)."'";
			}
		}
				
		$query = $DB->query($sql);

		if ($query->num_rows == 1)
		{
			$this->weblog_name[$blog_name] = $query->row['blog_title'];
		
			return $query->row['blog_title'];
		}
		else
		{
			return '';
		}
	}
	/* END */
	
	
    /** ----------------------------------------
    /**  Weblog Category Totals
    /** ----------------------------------------*/
    
    // Need to finish this function.  It lets a simple list of cagegories
    // appear along with the post total.

    function category_totals()
    {
		$sql = "SELECT count( exp_category_posts.entry_id ) AS count, 
				exp_categories.cat_id, 
				exp_categories.cat_name 
				FROM exp_categories 
				LEFT JOIN exp_category_posts ON exp_category_posts.cat_id = exp_categories.cat_id 
				GROUP BY exp_categories.cat_id 
				ORDER BY group_id, parent_id, cat_order";
	}
	/* END */


	
	
	

    /** ----------------------------------------
    /**  Weblog Categories
    /** ----------------------------------------*/

    function categories()
    {
        global $TMPL, $LOC, $FNS, $REGX, $DB, $LANG, $EXT;
        
        // -------------------------------------------
		// 'weblog_module_categories_start' hook.
		//  - Rewrite the displaying of categories, if you dare!
		//
			if ($EXT->active_hook('weblog_module_categories_start') === TRUE)
			{
				return $EXT->call_extension('weblog_module_categories_start');
			}
		//
        // -------------------------------------------
	
		if (USER_BLOG !== FALSE)
		{
		    $group_id = $DB->escape_str(UB_CAT_GRP);
		}
		else
		{
            $sql = "SELECT DISTINCT cat_group FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') ";
            
            if ($weblog = $TMPL->fetch_param('weblog'))
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('weblog'), 'blog_name');
			}
		    
		    $query = $DB->query($sql);
		        
            if ($query->num_rows != 1)
            {
                return '';
            }
            
            $group_id = $query->row['cat_group'];
            
			if ($category_group = $TMPL->fetch_param('category_group'))
			{
				if (substr($category_group, 0, 4) == 'not ')
				{
					$x = explode('|', substr($category_group, 4));
					
					$groups = array_diff(explode('|', $group_id), $x);	
				}
				else
				{
					$x = explode('|', $category_group);
					
					$groups = array_intersect(explode('|', $group_id), $x);
				}
				
				if (sizeof($groups) == 0)
				{
					return '';
				}
				else
				{
					$group_id = implode('|', $groups);
				}
			}
            
		}
			
		$parent_only = ($TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;
		                        		
		$path = array();
		
		if (preg_match_all("#".LD."path(=.+?)".RD."#", $TMPL->tagdata, $matches)) 
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{			
				if ( ! isset($path[$matches['0'][$i]]))
				{
					$path[$matches['0'][$i]] = $FNS->create_url($FNS->extract_path($matches['1'][$i]));
				}
			}
		}
		                
		$str = '';
		
		if ($TMPL->fetch_param('style') == '' OR $TMPL->fetch_param('style') == 'nested')
        {
			$this->category_tree(
									array(
											'group_id'		=> $group_id, 
											'template'		=> $TMPL->tagdata, 
											'path'			=> $path, 
											'blog_array' 	=> '',
											'parent_only'	=> $parent_only,
											'show_empty'	=> $TMPL->fetch_param('show_empty')
										  )
								);
				
						
			if (count($this->category_list) > 0)
			{
				$i = 0;
				
				$id_name = ( ! $TMPL->fetch_param('id')) ? 'nav_categories' : $TMPL->fetch_param('id');
				$class_name = ( ! $TMPL->fetch_param('class')) ? 'nav_categories' : $TMPL->fetch_param('class');
				
				$this->category_list['0'] = '<ul id="'.$id_name.'" class="'.$class_name.'">'."\n";
			
				foreach ($this->category_list as $val)
				{
					$str .= $val;                    
				}
			}
		}
		else
		{
			// fetch category field names and id's
			
			if ($this->enable['category_fields'] === TRUE)
			{
				$query = $DB->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", $TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."')");

				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
					}
				}

				$field_sqla = ", cg.field_html_formatting, fd.* ";
				$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
								LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
			}
			else
			{
				$field_sqla = '';
				$field_sqlb = '';
			}
			
			$show_empty = $TMPL->fetch_param('show_empty');
		
			if ($show_empty == 'no')
			{	
				// First we'll grab all category ID numbers
			
				$query = $DB->query("SELECT cat_id, parent_id 
									 FROM exp_categories 
									 WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."')
									 ORDER BY group_id, parent_id, cat_order");
				
				$all = array();
				
				// No categories exist?  Let's go home..
				if ($query->num_rows == 0)
					return false;
				
				foreach($query->result as $row)
				{
					$all[$row['cat_id']] = $row['parent_id'];
				}
				
				// Next we'l grab only the assigned categories
			
				$sql = "SELECT DISTINCT(exp_categories.cat_id), parent_id FROM exp_categories
						LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id
						LEFT JOIN exp_weblog_titles ON exp_category_posts.entry_id = exp_weblog_titles.entry_id
						WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."')
						AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."')
						AND exp_category_posts.cat_id IS NOT NULL ";

		        if (($status = $TMPL->fetch_param('status')) !== FALSE)
		        {
					$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
		            $sql .= $FNS->sql_andor_string($status, 'exp_weblog_titles.status');
		        }
		        else
		        {
		            $sql .= "AND exp_weblog_titles.status != 'closed' ";
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
				
				if ($parent_only === TRUE)
				{
					$sql .= " AND parent_id = 0";
				}
				
				$sql .= " ORDER BY group_id, parent_id, cat_order";
				
				$query = $DB->query($sql);
				if ($query->num_rows == 0)
					return false;
					
				// All the magic happens here, baby!!
				
				foreach($query->result as $row)
				{
					if ($row['parent_id'] != 0)
					{
						$this->find_parent($row['parent_id'], $all);
					}	
					
					$this->cat_full_array[] = $row['cat_id'];
				}
			
				$this->cat_full_array = array_unique($this->cat_full_array);
					
				$sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
				FROM exp_categories AS c
				{$field_sqlb}
				WHERE c.cat_id IN (";
		
				foreach ($this->cat_full_array as $val)
				{
					$sql .= $val.',';
				}
			
				$sql = substr($sql, 0, -1).')';
				
				$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
				
				$query = $DB->query($sql);
					  
				if ($query->num_rows == 0)
					return false;        
			}
			else
			{		
				$sql = "SELECT c.cat_name, c.cat_url_title, c.cat_image, c.cat_description, c.cat_id, c.parent_id {$field_sqla}
						FROM exp_categories AS c
						{$field_sqlb}
						WHERE c.group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') ";
						
				if ($parent_only === TRUE)
				{
					$sql .= " AND c.parent_id = 0";
				}
				
				$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
							
				$query = $DB->query($sql);
								  
				if ($query->num_rows == 0)
				{
					return '';
				}
			}  

			// Here we check the show parameter to see if we have any 
			// categories we should be ignoring or only a certain group of 
			// categories that we should be showing.  By doing this here before
			// all of the nested processing we should keep out all but the 
			// request categories while also not having a problem with having a 
			// child but not a parent.  As we all know, categories are not asexual.
		
			if ($TMPL->fetch_param('show') !== FALSE)
			{
				if (strncmp('not ', $TMPL->fetch_param('show'), 4) == 0)
				{
					$not_these = explode('|', trim(substr($TMPL->fetch_param('show'), 3)));
				}
				else
				{
					$these = explode('|', trim($TMPL->fetch_param('show')));
				}
			}
						
			foreach($query->result as $row)
			{ 
				if (isset($not_these) && in_array($row['cat_id'], $not_these))
				{
					continue;
				}
				elseif(isset($these) && ! in_array($row['cat_id'], $these))
				{
					continue;
				}
			
				$this->temp_array[$row['cat_id']]  = array($row['cat_id'], $row['parent_id'], '1', $row['cat_name'], $row['cat_description'], $row['cat_image'], $row['cat_url_title']);
			
				foreach ($row as $key => $val)
				{
					if (strpos($key, 'field') !== FALSE)
					{
						$this->temp_array[$row['cat_id']][$key] = $val;
					}
				}
			}
															
			foreach($this->temp_array as $key => $val) 
			{				
				if (0 == $val['1'])
				{    
					$this->cat_array[] = $val;
					$this->process_subcategories($key);
				}
			}

			unset($this->temp_array);

			if ( ! class_exists('Typography'))
	        {
	            require PATH_CORE.'core.typography'.EXT;
	        }

			$this->TYPE = new Typography;   
			$this->TYPE->convert_curly = FALSE;
			
			$this->category_count = 0;
			$total_results = count($this->cat_array);
			
			foreach ($this->cat_array as $key => $val)
			{
				$chunk = $TMPL->tagdata;
				
				$cat_vars = array('category_name'			=> $val['3'],
								  'category_url_title'		=> $val['6'],
								  'category_description'	=> $val['4'],
								  'category_image'			=> $val['5'],
								  'category_id'				=> $val['0'],
								  'parent_id'				=> $val['1']
								);

				// add custom fields for conditionals prep

				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}
				
				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;
				
				$chunk = $FNS->prep_conditionals($chunk, $cat_vars);
			
				$chunk = str_replace(array(LD.'category_name'.RD,
										   LD.'category_url_title'.RD,
										   LD.'category_description'.RD,
										   LD.'category_image'.RD,
										   LD.'category_id'.RD,
										   LD.'parent_id'.RD),
									 array($val['3'],
										   $val['6'],
									 	   $val['4'],
									 	   $val['5'],
									 	   $val['0'],
										   $val['1']),
									$chunk);

				foreach($path as $k => $v)
				{	
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($k, $FNS->remove_double_slashes($v.'/'.$this->reserved_cat_segment.'/'.$val['6'].'/'), $chunk); 
					}
					else
					{
						$chunk = str_replace($k, $FNS->remove_double_slashes($v.'/C'.$val['0'].'/'), $chunk); 
					}
				}
				
				// parse custom fields
				
				foreach($this->catfields as $cv)
				{
					if (isset($val['field_id_'.$cv['field_id']]) AND $val['field_id_'.$cv['field_id']] != '')
					{
						$field_content = $this->TYPE->parse_type($val['field_id_'.$cv['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$cv['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);								
						$chunk = str_replace(LD.$cv['field_name'].RD, $field_content, $chunk);	
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$cv['field_name'].RD, '', $chunk);
					}
				}

				/** --------------------------------
				/**  {count}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}
				
				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}
				
				$str .= $chunk;
			}
		    
			if ($TMPL->fetch_param('backspace'))
			{            
				$str = rtrim(str_replace("&#47;", "/", $str));
				$str = substr($str, 0, - $TMPL->fetch_param('backspace'));
				$str = str_replace("/", "&#47;", $str);
			}
		}

        return $str;
    }
    /* END */

    
    /** --------------------------------
    /**  Process Subcategories
    /** --------------------------------*/
        
    function process_subcategories($parent_id)
    {        
    	foreach($this->temp_array as $key => $val) 
        {
            if ($parent_id == $val['1'])
            {
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
        }
    }
    /* END */


    /** ----------------------------------------
    /**  Category archives
    /** ----------------------------------------*/

    function category_archive()
    {
        global $TMPL, $LOC, $FNS, $REGX, $DB, $LANG;		
		
		if (USER_BLOG !== FALSE)
		{
		    $group_id = $DB->escape_str(UB_CAT_GRP);
		    
		    $weblog_id = $DB->escape_str(UB_BLOG_ID);
		}
		else
		{
            $sql = "SELECT DISTINCT cat_group, weblog_id FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') ";
            
            if ($weblog = $TMPL->fetch_param('weblog'))
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('weblog'), 'blog_name');
			}
		    
		    $query = $DB->query($sql);
		        
            if ($query->num_rows != 1)
            {
                return '';
            }
            
            $group_id = $query->row['cat_group'];
            $weblog_id = $query->row['weblog_id'];
		}
		
		        
		$sql = "SELECT exp_category_posts.cat_id, exp_weblog_titles.entry_id, exp_weblog_titles.title, exp_weblog_titles.url_title, exp_weblog_titles.entry_date
		        FROM exp_weblog_titles, exp_category_posts
		        WHERE weblog_id = '$weblog_id'
		        AND exp_weblog_titles.entry_id = exp_category_posts.entry_id ";
		        
		$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;
        
        if ($TMPL->fetch_param('show_future_entries') != 'yes')
        {
			$sql .= "AND exp_weblog_titles.entry_date < ".$timestamp." ";
        }
        
        if ($TMPL->fetch_param('show_expired') != 'yes')
        {
			$sql .= "AND (exp_weblog_titles.expiration_date = 0 OR exp_weblog_titles.expiration_date > ".$timestamp.") ";
        }
        		        
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
        
        if ($TMPL->fetch_param('show') !== FALSE)
		{
			$sql .= $FNS->sql_andor_string($TMPL->fetch_param('show'), 'exp_category_posts.cat_id').' ';
        }
        
			
		$orderby  = $TMPL->fetch_param('orderby');
					
		switch ($orderby)
		{
			case 'date'					: $sql .= "ORDER BY exp_weblog_titles.entry_date";
				break;
			case 'expiration_date'		: $sql .= "ORDER BY exp_weblog_titles.expiration_date";
				break;
			case 'title'				: $sql .= "ORDER BY exp_weblog_titles.title";
				break;
			case 'comment_total'		: $sql .= "ORDER BY exp_weblog_titles.entry_date";
				break;
			case 'most_recent_comment'	: $sql .= "ORDER BY exp_weblog_titles.recent_comment_date desc, exp_weblog_titles.entry_date";
				break;
			default						: $sql .= "ORDER BY exp_weblog_titles.title";
				break;
		}
		
		$sort = $TMPL->fetch_param('sort');
		
		switch ($sort)
		{
			case 'asc'	: $sql .= " asc";
				break;
			case 'desc'	: $sql .= " desc";
				break;
			default		: $sql .= " asc";
				break;
		}			
		        				
		$result = $DB->query($sql);
		$blog_array = array();
		
		$parent_only = ($TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;

        $cat_chunk  = (preg_match("/".LD."categories\s*".RD."(.*?)".LD.SLASH."categories\s*".RD."/s", $TMPL->tagdata, $match)) ? $match['1'] : '';        
		
		$c_path = array();
		
		if (preg_match_all("#".LD."path(=.+?)".RD."#", $cat_chunk, $matches)) 
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{			
				$c_path[$matches['0'][$i]] = $FNS->create_url($FNS->extract_path($matches['1'][$i]));
			}
		}		
        
        $tit_chunk = (preg_match("/".LD."entry_titles\s*".RD."(.*?)".LD.SLASH."entry_titles\s*".RD."/s", $TMPL->tagdata, $match)) ? $match['1'] : '';        

		$t_path = array();
		
		if (preg_match_all("#".LD."path(=.+?)".RD."#", $tit_chunk, $matches)) 
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{			
				$t_path[$matches['0'][$i]] = $FNS->create_url($FNS->extract_path($matches['1'][$i]));
			}
		}
		
		$id_path = array();
		
		if (preg_match_all("#".LD."entry_id_path(=.+?)".RD."#", $tit_chunk, $matches)) 
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{			
				$id_path[$matches['0'][$i]] = $FNS->create_url($FNS->extract_path($matches['1'][$i]));
			}
		}
		
		$entry_date = array();
		
		preg_match_all("/".LD."entry_date\s+format\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/s", $tit_chunk, $matches);
		{
			$j = count($matches['0']);
			for ($i = 0; $i < $j; $i++)
			{
				$matches['0'][$i] = str_replace(array(LD,RD), '', $matches['0'][$i]);
				
				$entry_date[$matches['0'][$i]] = $LOC->fetch_date_params($matches['2'][$i]);
			}
		}

		$str = '';

		if ($TMPL->fetch_param('style') == '' OR $TMPL->fetch_param('style') == 'nested')
        {
			if ($result->num_rows > 0 && $tit_chunk != '')
			{        		
        			$i = 0;	
				foreach($result->result as $row)
				{
					$chunk = "<li>".str_replace(LD.'category_name'.RD, '', $tit_chunk)."</li>";
					
					foreach($t_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, $FNS->remove_double_slashes($tval.'/'.$row['url_title'].'/'), $chunk); 
					}
					
					foreach($id_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, $FNS->remove_double_slashes($tval.'/'.$row['entry_id'].'/'), $chunk); 
					}
					
					foreach($TMPL->var_single as $key => $val)
					{
						if (isset($entry_date[$key]))
						{
							foreach ($entry_date[$key] as $dval)
							{
								$val = str_replace($dval, $LOC->convert_timestamp($dval, $row['entry_date'], TRUE), $val);							
							}
							$chunk = $TMPL->swap_var_single($key, $val, $chunk);
						}
						
					}
			
					$blog_array[$i.'_'.$row['cat_id']] = str_replace(LD.'title'.RD, $row['title'], $chunk);
					$i++;
				}
			}
			
			$this->category_tree(
									array(
											'group_id'		=> $group_id, 
											'weblog_id'		=> $weblog_id,
											'path'			=> $c_path,
											'template'		=> $cat_chunk,
											'blog_array' 	=> $blog_array,
											'parent_only'	=> $parent_only,
											'show_empty'	=> $TMPL->fetch_param('show_empty')
										  )
								);
						
			if (count($this->category_list) > 0)
			{			
				$id_name = ($TMPL->fetch_param('id') === FALSE) ? 'nav_cat_archive' : $TMPL->fetch_param('id');
				$class_name = ($TMPL->fetch_param('class') === FALSE) ? 'nav_cat_archive' : $TMPL->fetch_param('class');
				
				$this->category_list['0'] = '<ul id="'.$id_name.'" class="'.$class_name.'">'."\n";
				
				foreach ($this->category_list as $val)
				{
					$str .= $val; 
				}
			}
		}
		else
		{
			// fetch category field names and id's
			
			if ($this->enable['category_fields'] === TRUE)
			{
				$query = $DB->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", $TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."')");

				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
					}
				}

				$field_sqla = ", cg.field_html_formatting, fd.* ";
				$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
								LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";
			}
			else
			{
				$field_sqla = '';
				$field_sqlb = '';
			}
			
			$sql = "SELECT DISTINCT (c.cat_id), c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.parent_id {$field_sqla}
					FROM (exp_categories AS c";
					
			$sql .= ") {$field_sqlb}";
			
			if ($TMPL->fetch_param('show_empty') == 'no')
			{
				$sql .= " LEFT JOIN exp_category_posts ON c.cat_id = exp_category_posts.cat_id ";
	
				if ($weblog_id != '')
				{
					$sql .= " LEFT JOIN exp_weblog_titles ON exp_category_posts.entry_id = exp_weblog_titles.entry_id ";
				}
			}
	
			$sql .= " WHERE c.group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') ";

			if ($TMPL->fetch_param('show_empty') == 'no')
			{
				
				if ($weblog_id != '')
				{
					$sql .= "AND exp_weblog_titles.weblog_id = '".$weblog_id."' ";
				}
				else
				{
					$sql .= " AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."') ";
				}
	
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
		
				if ($TMPL->fetch_param('show_empty') == 'no')
				{
					$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";
				}
			}
			
			if ($TMPL->fetch_param('show') !== FALSE)
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('show'), 'c.cat_id').' ';
        	}
					
			if ($parent_only == TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}
			
			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
		 	$query = $DB->query($sql);               
               
            if ($query->num_rows > 0)
            {					
            	if ( ! class_exists('Typography'))
		        {
		            require PATH_CORE.'core.typography'.EXT;
		        }

				$this->TYPE = new Typography;   
				$this->TYPE->convert_curly = FALSE;
				
				$used = array();
            
                foreach($query->result as $row)
                { 
					if ( ! isset($used[$row['cat_name']]))
					{
						$chunk = $cat_chunk;
					
						$cat_vars = array('category_name'			=> $row['cat_name'],
										  'category_url_title'		=> $row['cat_url_title'],
										  'category_description'	=> $row['cat_description'],
										  'category_image'			=> $row['cat_image'],
										  'category_id'				=> $row['cat_id'],										
										  'parent_id'				=> $row['parent_id']
										);

						foreach ($this->catfields as $v)
						{
							$cat_vars[$v['field_name']] = ( ! isset($row['field_id_'.$v['field_id']])) ? '' : $row['field_id_'.$v['field_id']];
						}
										
						$chunk = $FNS->prep_conditionals($chunk, $cat_vars);
					
						$chunk = str_replace( array(LD.'category_id'.RD,
													LD.'category_name'.RD,
													LD.'category_url_title'.RD,
													LD.'category_image'.RD,
													LD.'category_description'.RD,													
													LD.'parent_id'.RD),
											  array($row['cat_id'],
											  		$row['cat_name'],
													$row['cat_url_title'],
											  		$row['cat_image'],
											  		$row['cat_description'],
											  		$row['parent_id']),											
											  $chunk);
												
						foreach($c_path as $ckey => $cval)
						{
							$cat_seg = ($this->use_category_names == TRUE) ? $this->reserved_cat_segment.'/'.$row['cat_url_title'] : 'C'.$row['cat_id'];
							$chunk = str_replace($ckey, $FNS->remove_double_slashes($cval.'/'.$cat_seg.'/'), $chunk); 
						}
						
						// parse custom fields

						foreach($this->catfields as $cfv)
						{
							if (isset($row['field_id_'.$cfv['field_id']]) AND $row['field_id_'.$cfv['field_id']] != '')
							{
								$field_content = $this->TYPE->parse_type($row['field_id_'.$cfv['field_id']],
																			array(
																				  'text_format'		=> $row['field_ft_'.$cfv['field_id']],
																				  'html_format'		=> $row['field_html_formatting'],
																				  'auto_links'		=> 'n',
																				  'allow_img_url'	=> 'y'
																				)
																		);								
								$chunk = str_replace(LD.$cfv['field_name'].RD, $field_content, $chunk);	
							}
							else
							{
								// garbage collection
								$chunk = str_replace(LD.$cfv['field_name'].RD, '', $chunk);
							}
						}
					
						$str .= $chunk;
						$used[$row['cat_name']] = TRUE;
					}

					foreach($result->result as $trow)
					{
						if ($trow['cat_id'] == $row['cat_id'])
						{			
							$chunk = str_replace(array(LD.'title'.RD, LD.'category_name'.RD), 
												 array($trow['title'],$row['cat_name']),
												 $tit_chunk);
					
							foreach($t_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, $FNS->remove_double_slashes($tval.'/'.$trow['url_title'].'/'), $chunk); 
							}
							
							foreach($id_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, $FNS->remove_double_slashes($tval.'/'.$trow['entry_id'].'/'), $chunk); 
							}
							
							foreach($TMPL->var_single as $key => $val)
							{
								if (isset($entry_date[$key]))
								{
									foreach ($entry_date[$key] as $dval)
									{
										$val = str_replace($dval, $LOC->convert_timestamp($dval, $trow['entry_date'], TRUE), $val);							
									}
									$chunk = $TMPL->swap_var_single($key, $val, $chunk);
								}

							}
							
							$str .= $chunk;
						}
					}
                }
		    }
		    
			if ($TMPL->fetch_param('backspace'))
			{            
				$str = rtrim(str_replace("&#47;", "/", $str));
				$str = substr($str, 0, - $TMPL->fetch_param('backspace'));
				$str = str_replace("/", "&#47;", $str);
			}
		}
				
        return $str;
    }
    /* END */


    /** --------------------------------
    /**  Locate category parent
    /** --------------------------------*/
    // This little recursive gem will travel up the
    // category tree until it finds the category ID
    // number of any parents.  It's used by the function 
    // below

	function find_parent($parent, $all)
	{	
		foreach ($all as $cat_id => $parent_id)
		{
			if ($parent == $cat_id)
			{
				$this->cat_full_array[] = $cat_id;
				
				if ($parent_id != 0)
					$this->find_parent($parent_id, $all);				
			}
		}
	}
	/* END */


    /** --------------------------------
    /**  Category Tree
    /** --------------------------------*/

    // This function and the next create a nested, hierarchical category tree

    function category_tree($cdata = array())
    {  
        global $FNS, $REGX, $DB, $TMPL, $FNS, $LOC;
        
        $default = array('group_id', 'weblog_id', 'path', 'template', 'depth', 'blog_array', 'parent_only', 'show_empty');
        
        foreach ($default as $val)
        {
        	$$val = ( ! isset($cdata[$val])) ? '' : $cdata[$val];
        }
        
        if ($group_id == '')
        {
            return false;
        }
        
		if ($this->enable['category_fields'] === TRUE)
		{
			$query = $DB->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", $TMPL->site_ids)."')
								AND group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."')");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}
			
			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}
		
		/** -----------------------------------
		/**  Are we showing empty categories
		/** -----------------------------------*/
		
		// If we are only showing categories that have been assigned to entries
		// we need to run a couple queries and run a recursive function that
		// figures out whether any given category has a parent.
		// If we don't do this we will run into a problem in which parent categories
		// that are not assigned to a blog will be supressed, and therefore, any of its
		// children will be supressed also - even if they are assigned to entries.
		// So... we will first fetch all the category IDs, then only the ones that are assigned
		// to entries, and lastly we'll recursively run up the tree and fetch all parents.
		// Follow that?  No?  Me neither... 
           
		if ($show_empty == 'no')
		{	
			// First we'll grab all category ID numbers
		
			$query = $DB->query("SELECT cat_id, parent_id FROM exp_categories 
								 WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') 
								 ORDER BY group_id, parent_id, cat_order");
			
			$all = array();
			
			// No categories exist?  Back to the barn for the night..
			if ($query->num_rows == 0)
				return false;
			
			foreach($query->result as $row)
			{
				$all[$row['cat_id']] = $row['parent_id'];
			}	
			
			// Next we'l grab only the assigned categories
		
			$sql = "SELECT DISTINCT(exp_categories.cat_id), parent_id 
					FROM exp_categories
					LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id 
					LEFT JOIN exp_weblog_titles ON exp_category_posts.entry_id = exp_weblog_titles.entry_id ";
					
			$sql .= "WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') ";
			
			$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";
			
			if ($weblog_id != '')
			{
				$sql .= "AND exp_weblog_titles.weblog_id = '".$weblog_id."' ";
			}
			else
			{
				$sql .= "AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."') ";
			}
			
			if (($status = $TMPL->fetch_param('status')) !== FALSE)
	        {
				$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
	            $sql .= $FNS->sql_andor_string($status, 'exp_weblog_titles.status');
	        }
	        else
	        {
	            $sql .= "AND exp_weblog_titles.status != 'closed' ";
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
			
			if ($parent_only === TRUE)
			{
				$sql .= " AND parent_id = 0";
			}
			
			$sql .= " ORDER BY group_id, parent_id, cat_order";
			
			$query = $DB->query($sql);
			if ($query->num_rows == 0)
				return false;
				
			// All the magic happens here, baby!!
			
			foreach($query->result as $row)
			{
				if ($row['parent_id'] != 0)
				{
					$this->find_parent($row['parent_id'], $all);
				}	
				
				$this->cat_full_array[] = $row['cat_id'];
			}
        
        	$this->cat_full_array = array_unique($this->cat_full_array);
        		
			$sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
			FROM exp_categories AS c
			{$field_sqlb}
			WHERE c.cat_id IN (";
        
        	foreach ($this->cat_full_array as $val)
        	{
        		$sql .= $val.',';
        	}
        
			$sql = substr($sql, 0, -1).')';
			
			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
			
			$query = $DB->query($sql);
				  
			if ($query->num_rows == 0)
				return false;        
        }
		else
		{
			$sql = "SELECT DISTINCT(c.cat_id), c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
					FROM exp_categories AS c
					{$field_sqlb}
					WHERE c.group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') ";
					
			if ($parent_only === TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}
			
			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
			
			$query = $DB->query($sql);
				  
			if ($query->num_rows == 0)
				return false;
		}		

		// Here we check the show parameter to see if we have any 
		// categories we should be ignoring or only a certain group of 
		// categories that we should be showing.  By doing this here before
		// all of the nested processing we should keep out all but the 
		// request categories while also not having a problem with having a 
		// child but not a parent.  As we all know, categories are not asexual
		
		if ($TMPL->fetch_param('show') !== FALSE)
		{
			if (strncmp('not ', $TMPL->fetch_param('show'), 4) == 0)
			{
				$not_these = explode('|', trim(substr($TMPL->fetch_param('show'), 3)));
			}
			else
			{
				$these = explode('|', trim($TMPL->fetch_param('show')));
			}
		}
		
		foreach($query->result as $row)
		{
			if (isset($not_these) && in_array($row['cat_id'], $not_these))
			{
				continue;
			}
			elseif(isset($these) && ! in_array($row['cat_id'], $these))
			{
				continue;
			}
		
			$this->cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['cat_url_title']);

			foreach ($row as $key => $val)
			{
				if (strpos($key, 'field') !== FALSE)
				{
					$this->cat_array[$row['cat_id']][$key] = $val;
				}
			}
		}

    	$this->temp_array = $this->cat_array;
    	
    	$open = 0;
		
		if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $this->TYPE = new Typography;   
        $this->TYPE->convert_curly = FALSE;
		
		$this->category_count = 0;
		$total_results = count($this->cat_array);
		
        foreach($this->cat_array as $key => $val) 
        { 
            if (0 == $val['0'])
            {
				if ($open == 0)
				{
					$open = 1;
					
					$this->category_list[] = "<ul>\n";
				}
				
				$chunk = $template;
				
				$cat_vars = array('category_name'			=> $val['1'],
								  'category_url_title'		=> $val['4'],
								  'category_description'	=> $val['3'],
								  'category_image'			=> $val['2'],
								  'category_id'				=> $key,
								  'parent_id'				=> $val['0']
								);
				
				// add custom fields for conditionals prep
				
				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}
				
				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;
				
				$chunk = $FNS->prep_conditionals($chunk, $cat_vars);
				
				$chunk = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,											
											LD.'parent_id'.RD),
									  array($key,
									  		$val['1'],
											$val['4'],
									  		$val['2'],
									  		$val['3'],									
									  		$val['0']),
									  $chunk);
            					
				foreach($path as $pkey => $pval)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($pkey, $FNS->remove_double_slashes($pval.'/'.$this->reserved_cat_segment.'/'.$val['4'].'/'), $chunk); 
					}
					else
					{
						$chunk = str_replace($pkey, $FNS->remove_double_slashes($pval.'/C'.$key.'/'), $chunk); 
					}
				}	
            	
				// parse custom fields

				foreach($this->catfields as $cval)
				{
					if (isset($val['field_id_'.$cval['field_id']]) AND $val['field_id_'.$cval['field_id']] != '')
					{
						$field_content = $this->TYPE->parse_type($val['field_id_'.$cval['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$cval['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);								
						$chunk = str_replace(LD.$cval['field_name'].RD, $field_content, $chunk);	
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$cval['field_name'].RD, '', $chunk);
					}
				}
				
				/** --------------------------------
				/**  {count}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}
				
				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}
											
				$this->category_list[] = "\t<li>".$chunk;            	
				
				if (is_array($blog_array))
				{
					$fillable_entries = 'n';
					
					foreach($blog_array as $k => $v)
					{
						$k = substr($k, strpos($k, '_') + 1);
					
						if ($key == $k)
						{
							if ($fillable_entries == 'n')
							{
								$this->category_list[] = "\n\t\t<ul>\n";
								$fillable_entries = 'y';
							}
														
							$this->category_list[] = "\t\t\t$v";
						}
					}
				}
				
				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$this->category_list[] = "\t\t</ul>\n";
				}
								
				$this->category_subtree(
											array(
													'parent_id'		=> $key, 
													'path'			=> $path, 
													'template'		=> $template,
													'blog_array' 	=> $blog_array
												  )
									);
				$t = '';
				
				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$t .= "\t";
				}
				
				$this->category_list[] = $t."</li>\n";
				
				unset($this->temp_array[$key]);
				
				$this->close_ul(0);
            }
        }        
    }
    /* END */
    
    
    
    /** --------------------------------
    /**  Category Sub-tree
    /** --------------------------------*/
        
    function category_subtree($cdata = array())
    {
        global $TMPL, $FNS;
        
        $default = array('parent_id', 'path', 'template', 'depth', 'blog_array', 'show_empty');
        
        foreach ($default as $val)
        {
        		$$val = ( ! isset($cdata[$val])) ? '' : $cdata[$val];
        }
        
        $open = 0;
        
        if ($depth == '') 
        		$depth = 1;
                
		$tab = '';
		for ($i = 0; $i <= $depth; $i++)
			$tab .= "\t";
		
		$total_results = count($this->cat_array);
		
		foreach($this->cat_array as $key => $val) 
        {
            if ($parent_id == $val['0'])
            {
            	if ($open == 0)
				{
					$open = 1;            		
					$this->category_list[] = "\n".$tab."<ul>\n";
				}
				
				$chunk = $template;
				
				$cat_vars = array('category_name'			=> $val['1'],
								  'category_url_title'		=> $val['4'],
								  'category_description'	=> $val['3'],
								  'category_image'			=> $val['2'],
								  'category_id'				=> $key,
								  'parent_id'				=> $val['0']);								
			
				// add custom fields for conditionals prep

				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}
				
				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;
				
				$chunk = $FNS->prep_conditionals($chunk, $cat_vars);
				
				$chunk = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),											
									  array($key,
									  		$val['1'],
											$val['4'],
									  		$val['2'],
									  		$val['3'],
									  		$val['0']),									
									  $chunk);
		
				foreach($path as $pkey => $pval)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($pkey, $FNS->remove_double_slashes($pval.'/'.$this->reserved_cat_segment.'/'.$val['4'].'/'), $chunk); 
					}
					else
					{
						$chunk = str_replace($pkey, $FNS->remove_double_slashes($pval.'/C'.$key.'/'), $chunk); 
					}
				}	
				
				// parse custom fields

				foreach($this->catfields as $ccv)
				{
					if (isset($val['field_id_'.$ccv['field_id']]) AND $val['field_id_'.$ccv['field_id']] != '')
					{
						$field_content = $this->TYPE->parse_type($val['field_id_'.$ccv['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$ccv['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);								
						$chunk = str_replace(LD.$ccv['field_name'].RD, $field_content, $chunk);	
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$ccv['field_name'].RD, '', $chunk);
					}
				}
				
				/** --------------------------------
				/**  {count}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}
				
				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/
				
				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}
				
				$this->category_list[] = $tab."\t<li>".$chunk;
				
				if (is_array($blog_array))
				{
					$fillable_entries = 'n';
					
					foreach($blog_array as $k => $v)
					{
						$k = substr($k, strpos($k, '_') + 1);
					
						if ($key == $k)
						{
							if ( ! isset($fillable_entries) || $fillable_entries == 'n')
							{
								$this->category_list[] = "\n{$tab}\t\t<ul>\n";
								$fillable_entries = 'y';
							}
							
							$this->category_list[] = "{$tab}\t\t\t$v";            			
						}
					}
				}
				 
				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$this->category_list[] = "{$tab}\t\t</ul>\n";
				}
				 
				$t = '';
												
				if ($this->category_subtree(
											array(
													'parent_id'		=> $key, 
													'path'			=> $path, 
													'template'		=> $template,
													'depth' 			=> $depth + 2,
													'blog_array' 	=> $blog_array
												  )
									) != 0 );
			
			if (isset($fillable_entries) && $fillable_entries == 'y')
			{
				$t .= "$tab\t";
			}        
							
				$this->category_list[] = $t."</li>\n";
				
				unset($this->temp_array[$key]);
				
				$this->close_ul($parent_id, $depth + 1);
            }
        } 
        return $open; 
    }
    /* END */



    /** --------------------------------
    /**  Close </ul> tags
    /** --------------------------------*/

	// This is a helper function to the above
	
    function close_ul($parent_id, $depth = 0)
    {	
		$count = 0;
		
		$tab = "";
		for ($i = 0; $i < $depth; $i++)
		{
			$tab .= "\t";
		}
    	
        foreach ($this->temp_array as $val)
        {
         	if ($parent_id == $val['0']) 
         	
         	$count++;
        }
            
        if ($count == 0) 
        	$this->category_list[] = $tab."</ul>\n";
    }
	/* END */




    /** ----------------------------------------
    /**  Weblog "category_heading" tag
    /** ----------------------------------------*/

    function category_heading()
    {
        global $IN, $TMPL, $FNS, $DB, $EXT;

		if ($this->QSTR == '')
		{
		    return;
        }
        
        // -------------------------------------------
		// 'weblog_module_category_heading_start' hook.
		//  - Rewrite the displaying of category headings, if you dare!
		//
			if ($EXT->active_hook('weblog_module_category_heading_start') === TRUE)
			{
				$TMPL->tagdata = $EXT->call_extension('weblog_module_category_heading_start');
				if ($EXT->end_script === TRUE) return $TMPL->tagdata;
			}
		//
        // -------------------------------------------
        
        $qstring = $this->QSTR;
        
		/** --------------------------------------
		/**  Remove page number 
		/** --------------------------------------*/
		
		if (preg_match("#/P\d+#", $qstring, $match))
		{
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		/** --------------------------------------
		/**  Remove "N" 
		/** --------------------------------------*/

		if (preg_match("#/N(\d+)#", $qstring, $match))
		{			
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		// Is the category being specified by name?

		if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $TMPL->fetch_param('weblog'))
		{
			$qstring = preg_replace("/(.*?)".preg_quote($this->reserved_cat_segment)."\//i", '', $qstring);
				
			$sql = "SELECT DISTINCT cat_group FROM exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') AND ";
			
			if (USER_BLOG !== FALSE)
			{
				$sql .= " weblog_id='".UB_BLOG_ID."'";
			}
			else
			{
				$xsql = $FNS->sql_andor_string($TMPL->fetch_param('weblog'), 'blog_name');
				
				if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);
				
				$sql .= ' '.$xsql;
			}
				
			$query = $DB->query($sql);

			if ($query->num_rows > 0)
			{
				$valid = 'y';
				$last  = explode('|', $query->row['cat_group']);
				$valid_cats = array();
				
				foreach($query->result as $row)
				{
					if ($TMPL->fetch_param('relaxed_categories') == 'yes')
					{
						$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
					}
					else
					{
						$valid_cats = array_intersect($last, explode('|', $row['cat_group']));								
					}
					
					$valid_cats = array_unique($valid_cats);
					
					if (sizeof($valid_cats) == 0)
					{
						$valid = 'n';
						break;
					}
				}
			}
			else
			{
				$valid = 'n';
			}
			
			if ($valid == 'y')
			{
				// the category URL title should be the first segment left at this point in $qstring,
				// but because prior to this feature being added, category names were used in URLs,
				// and '/' is a valid character for category names.  If they have not updated their
				// category url titles since updating to 1.6, their category URL title could still
				// contain a '/'.  So we'll try to get the category the correct way first, and if
				// it fails, we'll try the whole $qstring
				
				$arr = explode('/', $qstring);
				$cut_qstring = array_shift($arr);
				
				$result = $DB->query("SELECT cat_id FROM exp_categories 
									  WHERE cat_url_title='".$DB->escape_str($cut_qstring)."' 
									  AND group_id IN ('".implode("','", $valid_cats)."')");

				if ($result->num_rows == 1)
				{
					$qstring = str_replace($cut_qstring, 'C'.$result->row['cat_id'], $qstring);
				}
				else
				{
					// give it one more try using the whole $qstring
					$result = $DB->query("SELECT cat_id FROM exp_categories 
										  WHERE cat_url_title='".$DB->escape_str($qstring)."' 
										  AND group_id IN ('".implode("','", $valid_cats)."')");

					if ($result->num_rows == 1)
					{
						$qstring = 'C'.$result->row['cat_id'];
					}
				}
			}
		}

		// Is the category being specified by ID?

		if ( ! preg_match("#(^|\/)C(\d+)#", $qstring, $match))
		{					
			return $TMPL->no_results();
		}

		// fetch category field names and id's
		
		if ($this->enable['category_fields'] === TRUE)
		{
			// limit to correct category group
			$gquery = $DB->query("SELECT group_id FROM exp_categories WHERE cat_id = '".$DB->escape_str($match['2'])."'");
			
			if ($gquery->num_rows == 0)
			{
				return $TMPL->no_results();
			}
			
			$query = $DB->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", $TMPL->site_ids)."')
								AND group_id = '".$gquery->row['group_id']."'");

			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}

			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}
						
		$query = $DB->query("SELECT c.cat_name, c.parent_id, c.cat_url_title, c.cat_description, c.cat_image {$field_sqla}
							FROM exp_categories AS c
							{$field_sqlb}
							WHERE c.cat_id = '".$DB->escape_str($match['2'])."'");
		
		if ($query->num_rows == 0)
		{
			return $TMPL->no_results();
		}

		$cat_vars = array('category_name'			=> $query->row['cat_name'],
						  'category_description'	=> $query->row['cat_description'],
						  'category_image'			=> $query->row['cat_image'],
						  'category_id'				=> $match['2'],
						  'parent_id'				=> $query->row['parent_id']);

		// add custom fields for conditionals prep

		foreach ($this->catfields as $v)
		{
			$cat_vars[$v['field_name']] = ( ! isset($query->row['field_id_'.$v['field_id']])) ? '' : $query->row['field_id_'.$v['field_id']];
		}
		
		$TMPL->tagdata = $FNS->prep_conditionals($TMPL->tagdata, $cat_vars);
				
		$TMPL->tagdata = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
							 	 	  array($match['2'],
											$query->row['cat_name'],
											$query->row['cat_url_title'],
											$query->row['cat_image'],
											$query->row['cat_description'],
											$query->row['parent_id']),											
							  		  $TMPL->tagdata);

		// parse custom fields

		if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }

		$this->TYPE = new Typography;   
		$this->TYPE->convert_curly = FALSE;
						
		// parse custom fields

		foreach($this->catfields as $ccv)
		{
			if (isset($query->row['field_id_'.$ccv['field_id']]) AND $query->row['field_id_'.$ccv['field_id']] != '')
			{
				$field_content = $this->TYPE->parse_type($query->row['field_id_'.$ccv['field_id']],
															array(
																  'text_format'		=> $query->row['field_ft_'.$ccv['field_id']],
																  'html_format'		=> $query->row['field_html_formatting'],
																  'auto_links'		=> 'n',
																  'allow_img_url'	=> 'y'
																)
														);								
				$TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, $field_content, $TMPL->tagdata);	
			}
			else
			{
				// garbage collection
				$TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, '', $TMPL->tagdata);
			}
		}
		
		return $TMPL->tagdata;
    }
    /* END */
    
    
	/** ---------------------------------------
	/**  Next / Prev entry tags
	/** ---------------------------------------*/
	
	function next_entry()
	{
		return $this->next_prev_entry('next');
	}
	
	function prev_entry()
	{
		return $this->next_prev_entry('prev');
	}
	
	function next_prev_entry($which = 'next')
	{
		global $DB, $FNS, $IN, $LOC, $SESS, $TMPL;
		
		$which = ($which != 'next' AND $which != 'prev') ? 'next' : $which;
		$sort = ($which == 'next') ? 'ASC' : 'DESC';

		// Don't repeat our work if we already know the single entry page details
		if (! isset($SESS->cache['weblog']['single_entry_id']) OR ! isset($SESS->cache['weblog']['single_entry_date']))
		{
			// no query string?  Nothing to do...
			if (($qstring = $this->QSTR) == '')
			{
				return;
			}

			/** --------------------------------------
			/**  Remove page number 
			/** --------------------------------------*/

			if (preg_match("#/P\d+#", $qstring, $match))
			{			
				$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
			}

			/** --------------------------------------
			/**  Remove "N" 
			/** --------------------------------------*/

			if (preg_match("#/N(\d+)#", $qstring, $match))
			{	
				$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
			}

			if (strpos($qstring, '/') !== FALSE)
			{	
				$qstring = substr($qstring, 0, strpos($qstring, '/'));
			}

			/** ---------------------------------------
			/**  Query for the entry id and date
			/** ---------------------------------------*/
			
			$sql = 'SELECT t.entry_id, t.entry_date
					FROM (exp_weblog_titles AS t)
					LEFT JOIN exp_weblogs AS w ON w.weblog_id = t.weblog_id ';
							
	        if (is_numeric($qstring))
	        {
				$sql .= " WHERE t.entry_id = '".$DB->escape_str($qstring)."' ";
	        }
	        else
	        {
				$sql .= " WHERE t.url_title = '".$DB->escape_str($qstring)."' ";
	        }
			
			if (USER_BLOG === FALSE)
			{
				$sql .= " AND w.is_user_blog = 'n' AND w.site_id IN ('".implode("','", $TMPL->site_ids)."') ";

				if ($blog_name = $TMPL->fetch_param('weblog'))
				{
					$sql .= $FNS->sql_andor_string($blog_name, 'blog_name', 'w');
				}
			}
			else
			{
				$sql .= " AND t.weblog_id = '".UB_BLOG_ID."' ";
			}

			$query = $DB->query($sql);

			// no results or more than one result?  Buh bye!
			if ($query->num_rows != 1)
			{
				$TMPL->log_item('Weblog Next/Prev Entry tag error: Could not resolve single entry page id.');
				return;
			}
			
			$SESS->cache['weblog']['single_entry_id'] = $query->row['entry_id'];
			$SESS->cache['weblog']['single_entry_date'] = $query->row['entry_date'];
		}
		
		/** ---------------------------------------
		/**  Find the next / prev entry
		/** ---------------------------------------*/

		$ids = '';
	
		// Get included or excluded entry ids from entry_id parameter
		if (($entry_id = $TMPL->fetch_param('entry_id')) != FALSE)
		{
			$ids = $FNS->sql_andor_string($entry_id, 't.entry_id').' ';
		}
	
		$sql = 'SELECT t.entry_id, t.title, t.url_title
				FROM (exp_weblog_titles AS t)
				LEFT JOIN exp_weblogs AS w ON w.weblog_id = t.weblog_id ';
		
		/* --------------------------------
		/*  We use LEFT JOIN when there is a 'not' so that we get 
		/*  entries that are not assigned to a category.
		/* --------------------------------*/

		if ((substr($TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr($TMPL->fetch_param('category'), 0, 3) == 'not') && $TMPL->fetch_param('uncategorized_entries') !== 'n')
		{
			$sql .= 'LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}
		elseif($TMPL->fetch_param('category_group') OR $TMPL->fetch_param('category'))
		{
			$sql .= 'INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}
		
		$sql .= ' WHERE t.entry_id != '.$SESS->cache['weblog']['single_entry_id'].' '.$ids;
		
		$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;

	    if ($TMPL->fetch_param('show_future_entries') != 'yes')
	    {			
	    	$sql .= " AND t.entry_date < {$timestamp} ";
	    }
		
		// constrain by date depending on whether this is a 'next' or 'prev' tag
		if ($which == 'next')
		{
			$sql .= ' AND t.entry_date >= '.$SESS->cache['weblog']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.$SESS->cache['weblog']['single_entry_date'].', t.entry_id > '.$SESS->cache['weblog']['single_entry_id'].', 1) ';
		}
		else
		{
			$sql .= ' AND t.entry_date <= '.$SESS->cache['weblog']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.$SESS->cache['weblog']['single_entry_date'].', t.entry_id < '.$SESS->cache['weblog']['single_entry_id'].', 1) ';
		}
		
	    if ($TMPL->fetch_param('show_expired') != 'yes')
	    {
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > {$timestamp}) ";
	    }

	    if (USER_BLOG === FALSE)
	    {
			$sql .= " AND w.is_user_blog = 'n' AND w.site_id IN ('".implode("','", $TMPL->site_ids)."') ";

	        if ($blog_name = $TMPL->fetch_param('weblog'))
	        {
	            $sql .= $FNS->sql_andor_string($blog_name, 'blog_name', 'w')." ";
	        }
	    }
	    else
	    {
			$sql .= " AND t.weblog_id = '".UB_BLOG_ID."' ";
	    }

		if ($status = $TMPL->fetch_param('status'))
	    {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);

			$sql .= $FNS->sql_andor_string($status, 't.status')." ";
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}
		
		/** ----------------------------------------------
	    /**  Limit query by category
	    /** ----------------------------------------------*/

	    if ($TMPL->fetch_param('category'))
	    {
	    	if (stristr($TMPL->fetch_param('category'), '&'))
	    	{
	    		/** --------------------------------------
	    		/**  First, we find all entries with these categories
	    		/** --------------------------------------*/

	    		$for_sql = (substr($TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr($TMPL->fetch_param('category'), 3)) : $TMPL->fetch_param('category');

	    		$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id, ".
						str_replace('SELECT', '', $sql).
						$FNS->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

	    		$results = $DB->query($csql); 

	    		if ($results->num_rows == 0)
	    		{
					return;
	    		}

	    		$type = 'IN';
	    		$categories	 = explode('&', $TMPL->fetch_param('category'));
	    		$entry_array = array();

	    		if (substr($categories['0'], 0, 3) == 'not')
	    		{
	    			$type = 'NOT IN';

	    			$categories['0'] = trim(substr($categories['0'], 3));
	    		}

	    		foreach($results->result as $row)
	    		{
	    			$entry_array[$row['cat_id']][] = $row['entry_id'];
	    		}

	    		if (sizeof($entry_array) < 2 OR sizeof(array_diff($categories, array_keys($entry_array))) > 0)
	    		{
					return;
	    		}

	    		$chosen = call_user_func_array('array_intersect', $entry_array);

	    		if (sizeof($chosen) == 0)
	    		{
					return;
	    		}

	    		$sql .= "AND t.entry_id ".$type." ('".implode("','", $chosen)."') ";
	    	}
	    	else
	    	{
	    		if (substr($TMPL->fetch_param('category'), 0, 3) == 'not' && $TMPL->fetch_param('uncategorized_entries') !== 'n')
	    		{
	    			$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
	    		}
	    		else
	    		{
	    			$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
	    		}
	    	}
	    }

	    if ($TMPL->fetch_param('category_group'))
	    {
	        if (substr($TMPL->fetch_param('category_group'), 0, 3) == 'not' && $TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= $FNS->sql_andor_string($TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
	    }

		$sql .= " ORDER BY t.entry_date {$sort}, t.entry_id {$sort} LIMIT 1";

		$query = $DB->query($sql);

		if ($query->num_rows == 0)
		{
			return;
		}
		
		/** ---------------------------------------
		/**  Replace variables
		/** ---------------------------------------*/
		
		if (strpos($TMPL->tagdata, LD.'path=') !== FALSE)
		{
			$path  = (preg_match("#".LD."path=(.+?)".RD."#", $TMPL->tagdata, $match)) ? $FNS->create_url($match['1']) : $FNS->create_url("SITE_INDEX");
			$path .= '/'.$query->row['url_title'].'/';	
			$TMPL->tagdata = preg_replace("#".LD."path=.+?".RD."#", $path, $TMPL->tagdata);	
		}
		
		if (strpos($TMPL->tagdata, LD.'id_path=') !== FALSE)
		{
			$id_path  = (preg_match("#".LD."id_path=(.+?)".RD."#", $TMPL->tagdata, $match)) ? $FNS->create_url($match['1']) : $FNS->create_url("SITE_INDEX");
			$id_path .= '/'.$query->row['entry_id'].'/';			

			$TMPL->tagdata = preg_replace("#".LD."id_path=.+?".RD."#", $id_path, $TMPL->tagdata);
		}
		
		if (strpos($TMPL->tagdata, LD.'url_title') !== FALSE)
		{
			$TMPL->tagdata = str_replace(LD.'url_title'.RD, $query->row['url_title'], $TMPL->tagdata);		
		}
		
		if (strpos($TMPL->tagdata, LD.'entry_id') !== FALSE)
		{
			$TMPL->tagdata = str_replace(LD.'entry_id'.RD, $query->row['entry_id'], $TMPL->tagdata);		
		}
		
		if (strpos($TMPL->tagdata, LD.'title') !== FALSE)
		{
			$TMPL->tagdata = str_replace(LD.'title'.RD, $query->row['title'], $TMPL->tagdata);		
		}
		
		if (strpos($TMPL->tagdata, '_entry->title') !== FALSE)
		{
			$TMPL->tagdata = preg_replace('/'.LD.'(?:next|prev)_entry->title'.RD.'/', $query->row['title'], $TMPL->tagdata);		
		}		

		return $FNS->remove_double_slashes(stripslashes($TMPL->tagdata));
	}
	/* END */
	

    /** ----------------------------------------
    /**  Weblog "month links"
    /** ----------------------------------------*/

    function month_links()
    {
        global $TMPL, $LOC, $FNS, $REGX, $DB, $LANG, $SESS;
        
        $return = '';
        
        /** ----------------------------------------
        /**  Build query
        /** ----------------------------------------*/
        
        // Fetch the timezone array and calculate the offset so we can localize the month/year
        $zones = $LOC->zones();
        
        $offset = ( ! isset($zones[$SESS->userdata['timezone']]) || $zones[$SESS->userdata['timezone']] == '') ? 0 : ($zones[$SESS->userdata['timezone']]*60*60);        
        		
		if (substr($offset, 0, 1) == '-')
		{
			$calc = 'entry_date - '.substr($offset, 1);
		}
		elseif (substr($offset, 0, 1) == '+')
		{
			$calc = 'entry_date + '.substr($offset, 1);
		}
		else
		{
			$calc = 'entry_date + '.$offset;
		}
                
        $sql = "SELECT DISTINCT year(FROM_UNIXTIME(".$calc.")) AS year, 
        				MONTH(FROM_UNIXTIME(".$calc.")) AS month 
        				FROM exp_weblog_titles 
        				WHERE entry_id != ''
        				AND site_id IN ('".implode("','", $TMPL->site_ids)."') ";
                
                
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
        /**  Limit to/exclude specific weblogs
        /** ----------------------------------------------*/
    
        if (USER_BLOG !== FALSE)
        {
            $sql .= "AND weblog_id = '".UB_BLOG_ID."' ";
        }
        else
        {
       
            if ($weblog = $TMPL->fetch_param('weblog'))
            {
                $wsql = "SELECT weblog_id FROM exp_weblogs WHERE is_user_blog = 'n' AND site_id IN ('".implode("','", $TMPL->site_ids)."') ";
            
                $wsql .= $FNS->sql_andor_string($weblog, 'blog_name');
                                
                $query = $DB->query($wsql);
                
                if ($query->num_rows > 0)
                {
                    $sql .= " AND ";
                
                    if ($query->num_rows == 1)
                    {
                        $sql .= "weblog_id = '".$query->row['weblog_id']."' ";
                    }
                    else
                    {
                        $sql .= "(";
                        
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
        
		/** ----------------------------------------------
        /**  Add status declaration
        /** ----------------------------------------------*/
                        
        if ($status = $TMPL->fetch_param('status'))
        {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
			
			$sstr = $FNS->sql_andor_string($status, 'status');
			
			if ( ! preg_match("#\'closed\'#i", $sstr))
			{
				$sstr .= " AND status != 'closed' ";
			}
			
			$sql .= $sstr;
        }
        else
        {
            $sql .= "AND status = 'open' ";
        }
        
        $sql .= " ORDER BY entry_date";
		
		switch ($TMPL->fetch_param('sort'))
		{
			case 'asc'	: $sql .= " asc";
				break;
			case 'desc'	: $sql .= " desc";
				break;
			default		: $sql .= " desc";
				break;
		} 
                
        if (is_numeric($TMPL->fetch_param('limit')))
        {
            $sql .= " LIMIT ".$TMPL->fetch_param('limit');  
        }
                
        $query = $DB->query($sql);

        if ($query->num_rows == 0)
        {
            return '';
        }
        
        $year_limit   = (is_numeric($TMPL->fetch_param('year_limit'))) ? $TMPL->fetch_param('year_limit') : 50;
        $total_years  = 0;
        $current_year = '';
        
        foreach ($query->result as $row)
        { 
            $tagdata = $TMPL->tagdata;
							
			$month = (strlen($row['month']) == 1) ? '0'.$row['month'] : $row['month'];
			$year  = $row['year'];
				
            $month_name = $LOC->localize_month($month);
            
            /** ----------------------------------------
            /**  Dealing with {year_heading}
            /** ----------------------------------------*/
            
			if (isset($TMPL->var_pair['year_heading']))
			{
				if ($year == $current_year)
				{
					$tagdata = $TMPL->delete_var_pairs('year_heading', 'year_heading', $tagdata);
				}
				else
				{
					$tagdata = $TMPL->swap_var_pairs('year_heading', 'year_heading', $tagdata);
					
					$total_years++;
            	
					if ($total_years > $year_limit)
					{	
						break;
					}
				}
				
				$current_year = $year;
			}
            
			/** ---------------------------------------
			/**  prep conditionals
			/** ---------------------------------------*/
			
			$cond = array();
			
			$cond['month']			= $LANG->line($month_name['1']);
			$cond['month_short']	= $LANG->line($month_name['0']);
			$cond['month_num']		= $month;
			$cond['year']			= $year;
			$cond['year_short']		= substr($year, 2);
			
			$tagdata = $FNS->prep_conditionals($tagdata, $cond);
			
            /** ----------------------------------------
            /**  parse path
            /** ----------------------------------------*/
                        
            foreach ($TMPL->var_single as $key => $val)
            {
            	if (strncmp('path', $key, 4) == 0)
                { 
                    $tagdata = $TMPL->swap_var_single(
                                                        $val, 
                                                        $FNS->create_url($FNS->extract_path($key).'/'.$year.'/'.$month), 
                                                        $tagdata
                                                      );
                }

                /** ----------------------------------------
                /**  parse month (long)
                /** ----------------------------------------*/
                
                if ($key == 'month')
                {    
                    $tagdata = $TMPL->swap_var_single($key, $LANG->line($month_name['1']), $tagdata);
                }
                
                /** ----------------------------------------
                /**  parse month (short)
                /** ----------------------------------------*/
                
                if ($key == 'month_short')
                {    
                    $tagdata = $TMPL->swap_var_single($key, $LANG->line($month_name['0']), $tagdata);
                }
                
                /** ----------------------------------------
                /**  parse month (numeric)
                /** ----------------------------------------*/
                
                if ($key == 'month_num')
                {    
                    $tagdata = $TMPL->swap_var_single($key, $month, $tagdata);
                }
                
                /** ----------------------------------------
                /**  parse year
                /** ----------------------------------------*/
                
                if ($key == 'year')
                {    
                    $tagdata = $TMPL->swap_var_single($key, $year, $tagdata);
                }
                
                /** ----------------------------------------
                /**  parse year (short)
                /** ----------------------------------------*/
                
                if ($key == 'year_short')
                {    
                    $tagdata = $TMPL->swap_var_single($key, substr($year, 2), $tagdata);
                }
             }
             
             $return .= trim($tagdata)."\n";
         }
             
        return $return;    
    }
    /* END */


    /** ----------------------------------------
    /**  Related Categories Mode
    /** ----------------------------------------*/

	// This function shows entries that are in the same category as
	// the primary entry being shown.  It calls the main "weblog entries"
	// function after setting some variables to control the content.
	//
	// Note:  We have deprecated the calling of this tag directly via its own tag.
	// Related entries are now shown using the standard {exp:weblog:entries} tag.
	// The reason we're deprecating it is to avoid confusion since the weblog tag
	// now supports relational capability via a pair of {related_entries} tags.
	// 
	// To show "related entries" the following parameter is added to the {exp:weblog:entries} tag:
	//
	// related_categories_mode="on"
	
	function related_entries()
	{
		global $DB, $IN, $TMPL, $LOC, $FNS;
				
		if ($this->QSTR == '')
		{
			return false;
		}
		
        $qstring = $this->QSTR;
        
		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/
		
		if (preg_match("#/P\d+#", $qstring, $match))
		{			
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		/** --------------------------------------
		/**  Remove "N" 
		/** --------------------------------------*/

		if (preg_match("#/N(\d+)#", $qstring, $match))
		{			
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		/** --------------------------------------
		/**  Make sure to only get one segment
		/** --------------------------------------*/
		
		if (strstr($qstring, '/'))
		{	
			$qstring = substr($qstring, 0, strpos($qstring, '/'));
		}

		/** ----------------------------------
		/**  Find Categories for Entry
		/** ----------------------------------*/
		
		$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name
				FROM exp_weblog_titles
				INNER JOIN exp_category_posts ON exp_weblog_titles.entry_id = exp_category_posts.entry_id
				INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id 
				WHERE exp_categories.cat_id IS NOT NULL 
				AND exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."') ";
	
		$sql .= ( ! is_numeric($qstring)) ? "AND exp_weblog_titles.url_title = '".$DB->escape_str($qstring)."' " : "AND exp_weblog_titles.entry_id = '".$DB->escape_str($qstring)."' ";
				
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $TMPL->no_results();
		}
		
		/** ----------------------------------
		/**  Build category array
		/** ----------------------------------*/
		
		$cat_array = array();
		
		// We allow the option of adding or subtracting cat_id's
		$categories = ( ! $TMPL->fetch_param('category'))  ? '' : $TMPL->fetch_param('category');
		
		if (strncmp('not ', $categories, 4) == 0)
		{
			$categories = substr($categories, 4);
			$not_categories = explode('|',$categories);
		}
		else
		{
			$add_categories = explode('|',$categories);
		}
		
		foreach($query->result as $row)
		{
			if ( ! isset($not_categories) || array_search($row['cat_id'], $not_categories) === false)
			{ 
				$cat_array[] = $row['cat_id'];
			}
		}
		
		// User wants some categories added, so we add these cat_id's
		
		if (isset($add_categories) && sizeof($add_categories) > 0)
		{
			foreach($add_categories as $cat_id)
			{
				$cat_array[] = $cat_id;	
			}
		}
		
		// Just in case
		$cat_array = array_unique($cat_array);
		
		if (sizeof($cat_array) == 0)
		{
			return $TMPL->no_results();
		}
		
		/** ----------------------------------
		/**  Build category string
		/** ----------------------------------*/
		
		$cats = '';
		
		foreach($cat_array as $cat_id)
		{
			if ($cat_id != '')
			{
				$cats .= $cat_id.'|';
			}
		}
		$cats = substr($cats, 0, -1);
				
		/** ----------------------------------
		/**  Manually set paramters
		/** ----------------------------------*/
		
		$TMPL->tagparams['category']		= $cats;		
		$TMPL->tagparams['dynamic']			= 'off';
		$TMPL->tagparams['rdf']				= 'off';
		$TMPL->tagparams['not_entry_id']	= $qstring; // Exclude the current entry
		
		// Set user submitted paramters
		
		$params = array('weblog', 'username', 'status', 'orderby', 'sort');
		
		foreach ($params as $val)
		{
			if ($TMPL->fetch_param($val) != FALSE)
			{
				$TMPL->tagparams[$val] = $TMPL->fetch_param($val);
			}
		}
		
		if ( ! is_numeric($TMPL->fetch_param('limit')))
		{
			$TMPL->tagparams['limit'] = 10;
		}
		
		/** ----------------------------------
		/**  Run the weblog parser
		/** ----------------------------------*/
		
        $this->initialize();
		$this->entry_id 	= '';
		$qstring 			= '';  
		
		if ($this->enable['custom_fields'] == TRUE && $TMPL->fetch_param('custom_fields') == 'on')
        {
        	$this->fetch_custom_weblog_fields();
        }

        $this->build_sql_query();
        
        if ($this->sql == '')
        {
        	return $TMPL->no_results();
        }
        
        $this->query = $DB->query($this->sql);
        
        if ($this->query->num_rows == 0)
        {
            return $TMPL->no_results();
        }
        
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $this->TYPE = new Typography;
        $this->TYPE->convert_curly = FALSE;
        
        if ($TMPL->fetch_param('member_data') !== FALSE && $TMPL->fetch_param('member_data') == 'on')
        {
        	$this->fetch_custom_member_fields();
        }
        
        $this->parse_weblog_entries();
        		
		return $this->return_data;
	}
	/* END */
        
    
    
	/** ---------------------------------------
	/**  Fetch Disable Parameter
	/** ---------------------------------------*/
	
	function _fetch_disable_param()
	{
		global $TMPL;
		
		$this->enable = array(
        					'categories' 		=> TRUE,
							'category_fields'	=> TRUE, 
        					'custom_fields'		=> TRUE, 
        					'member_data'		=> TRUE, 
        					'pagination' 		=> TRUE,
        					'trackbacks'		=> TRUE
        					);
        
		if ($disable = $TMPL->fetch_param('disable'))
		{
			if (strpos($disable, '|') !== FALSE)
			{				
				foreach (explode("|", $disable) as $val)
				{
					if (isset($this->enable[$val]))
					{  
						$this->enable[$val] = FALSE;
					}
				}
			}
			elseif (isset($this->enable[$disable]))
			{
				$this->enable[$disable] = FALSE;
			}
		}
	}
	/* END */
	
	
	
    /** ----------------------------------------
    /**  Weblog Calendar
    /** ----------------------------------------*/
    
    function calendar()
    {
    	global $EXT;
    	
    	// -------------------------------------------
		// 'weblog_module_calendar_start' hook.
		//  - Rewrite the displaying of the calendar tag
		//
			if ($EXT->active_hook('weblog_module_calendar_start') === TRUE)
			{
				$edata = $EXT->call_extension('weblog_module_calendar_start');
				if ($EXT->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------
    
    	if ( ! class_exists('Weblog_calendar'))
		{
			require PATH_MOD.'weblog/mod.weblog_calendar.php';
		}
		
		$WC = new Weblog_calendar();
		return $WC->calendar();
    }
    /* END */
    

    /** ----------------------------------------
    /**  Trackback RDF
    /** ----------------------------------------*/

    function trackback_rdf($TB)
    {
        
return "<!--
<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
         xmlns:trackback=\"http://madskills.com/public/xml/rss/module/trackback/\"
         xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
<rdf:Description
    rdf:about=\"".$TB['about']."\"
    trackback:ping=\"".$TB['ping']."\"
    dc:title=\"".$TB['title']."\"
    dc:identifier=\"".$TB['identifier']."\" 
    dc:subject=\"".$TB['subject']."\"
    dc:description=\"".$TB['description']."\"
    dc:creator=\"".$TB['creator']."\"
    dc:date=\"".$TB['date']."\" />
</rdf:RDF>
-->";
    }
    /* END */



    /** ----------------------------------------
    /**  Insert a new weblog entry
    /** ----------------------------------------*/
    
    // This function serves dual purpose:
    // 1. It allows submitted data to be previewed
    // 2. It allows submitted data to be inserted

	function insert_new_entry()
	{
		if ( ! class_exists('Weblog_standalone'))
		{
			require PATH_MOD.'weblog/mod.weblog_standalone.php';
		}
		
		$WS = new Weblog_standalone();
		$WS->insert_new_entry();
	}
	/* END */


    /** ----------------------------------------
    /**  Stand-alone version of the entry form
    /** ----------------------------------------*/
    
    function entry_form($return_form = FALSE, $captcha = '')
    {
       if ( ! class_exists('Weblog_standalone'))
		{
			require PATH_MOD.'weblog/mod.weblog_standalone.php';
		}
		
		$WS = new Weblog_standalone();
		return $WS->entry_form($return_form, $captcha); 
    }
    /* END */

}
// END CLASS
?>