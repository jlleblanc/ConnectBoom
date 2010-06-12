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
 File: mod.blogger_api.php
-----------------------------------------------------
 Purpose: Blogger API Functionality
=====================================================

*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Blogger_api {

    var $return_data	= ''; 						// Bah!
    var $LB				= "\r\n";					// Line Break for Entry Output
    
    var $status			= '';						// Retrieving
    var $weblog			= '';
    var $categories		= '';
    var $fields			= array();
    var $userdata		= array();
    
    var $title			= 'Blogger API Entry';		// Default Title
    var $weblog_id		= '1';						// Default Weblog ID
    var $site_id		= '1';						// Default Site ID
    var $field			= '';						// Default Field ID
    var $field_name		= 'body';					// Default Field Name
    var $ecategories 	= array();					// Categories (new/edit entry)
    var $cat_output		= 'name';					// (id, name) ID or Name Outputted?
    var $assign_parents	= TRUE;						// Assign cat parents to post
    var $cat_parents	= array();					// Parent categories of new/edited entry
    
    var $pref_name		= 'Default';				// Name of preference configuration
    var $block_entry	= false;					// Send entry as one large block, no fields?
    var $field_id		= '2';						// Configruation's Default Field ID
    var $parse_type		= true;						// Use Typography class when sending entry?
    var $text_format	= false;					// Use field's text format with Typography class?
    var $html_format	= 'safe';					// safe, all, none

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Blogger_api()
    {        
    	global $LANG, $DB;
    	
    	$LANG->fetch_language_file('blogger_api');
    	
    	$id = ( isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : '1';
    	
    	/** ----------------------------------------
    	/**  Configuration Options
    	/** ----------------------------------------*/
    	
    	$query = $DB->query("SELECT * FROM exp_blogger WHERE blogger_id = '{$id}'");
    		
    	if ($query->num_rows > 0)
    	{
    		foreach($query->row as $name => $pref)
    		{
    			$name = str_replace('blogger_', '', $name);
    			
    			if ($pref == 'y' OR $pref == 'n')
    			{
    				$this->{$name} = ($pref == 'y') ? true : false;
    			}
    			elseif($name == 'field_id')
    			{
    				$x = explode(':',$pref);
    				$this->field_id = ( ! isset($x['1'])) ? $x['0'] : $x['1'];
    			}
    			else
    			{	
    				$this->{$name} = $pref;
    			}
    		}
    	}
    }
    /* END */
    
    
    /** -----------------------------------------
    /**  USAGE: Incoming Blogger API Requests
    /** -----------------------------------------*/
    
    function incoming()
    {
    	global $LANG;
    
    	/** ---------------------------------
    	/**  Load the XML-RPC Files
    	/** ---------------------------------*/
    	
    	if ( ! class_exists('XML_RPC'))
		{
			require PATH_CORE.'core.xmlrpc'.EXT;
		}
		
		if ( ! class_exists('XML_RPC_Server'))
		{
			require PATH_CORE.'core.xmlrpcs'.EXT;
		}
		
		/* ---------------------------------
    	/*  Specify Functions
    	/*	Normally, we would add a signature and docstring to the array for 
    	/*	each function, but since these are widespread and well known
    	/*	functions I just skipped it.
    	/* ---------------------------------*/
    	
    	$functions = array(	'blogger.getUserInfo'		=> array('function' => 'Blogger_api.getUserInfo'),
    	    				'blogger.getUsersBlogs'		=> array('function' => 'Blogger_api.getUsersBlogs'),
    	    				'blogger.newPost'			=> array('function' => 'Blogger_api.newPost'),
    	    				'blogger.getRecentPosts'	=> array('function' => 'Blogger_api.getRecentPosts'),
    	    				'blogger.getPost'			=> array('function' => 'Blogger_api.getPost'),
    	    				'blogger.editPost'			=> array('function' => 'Blogger_api.editPost'),
    	    				'blogger.deletePost'		=> array('function' => 'Blogger_api.deletePost')
							);    
		
		/** ---------------------------------
    	/**  Instantiate the Server Class
    	/** ---------------------------------*/
    	
		$server = new XML_RPC_Server($functions);
		$server->xss_clean = FALSE; 
    }
    /* END */
    
    
	/** -----------------------------------------
    /**  USAGE: Send User Information
    /** -----------------------------------------*/
    
    function getUserInfo($plist)
    {
    	global $DB, $LANG;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	$response = new XML_RPC_Values(array(
    											'nickname' => 
    											new XML_RPC_Values($this->userdata['screen_name'],'string'),
    											'userid' => 
    											new XML_RPC_Values($this->userdata['member_id'],'string'),
    											'url' => 
    											new XML_RPC_Values($this->userdata['url'],'string'),
    											'email' => 
    											new XML_RPC_Values($this->userdata['email'],'string'),
    											'lastname' => 
    											new XML_RPC_Values('','string'),
    											'firstname' => 
    											new XML_RPC_Values('','string')
    										  ),
    									'struct');
	
		return new XML_RPC_Response($response);
    }
    /* END */
    
    
    /** --------------------------------------
	/**  BLOGGER API: getUsersBlogs
	/** --------------------------------------*/


	function getUsersBlogs($plist)
	{
		global $DB, $LANG;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	$query = $DB->query("SELECT weblog_id, blog_title, blog_url
    						  FROM exp_weblogs
    						  WHERE weblog_id IN (".implode(',', $this->userdata['assigned_weblogs']).")");
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('no_weblogs_found'));
    	}
    	
    	$response = array();
    	
    	foreach($query->result as $row) 
		{
			$weblog = new XML_RPC_Values(array(
												"url" => 
												new XML_RPC_Values($row['blog_url'],"string"),
												"blogid" => 
												new XML_RPC_Values($row['weblog_id'],"string"),
												"blogName" => 
												new XML_RPC_Values($row['blog_title'],"string")),'struct');
		
			array_push($response, $weblog);
		}
	
		return new XML_RPC_Response(new XML_RPC_Values($response, 'array'));
	}
	/* END */
	
	
	
	
	/** -----------------------------------------
    /**  USAGE: Get Recent Posts for User
    /** -----------------------------------------*/
    
    function getRecentPosts($plist, $entry_id = '')
    {
    	global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'])
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Out Weblog Information
    	/** ---------------------------------------*/
    	
    	if ($entry_id == '')
    	{
    		$this->parse_weblog($parameters['1']);
    		$limit = ( ! isset($parameters['4']) OR $parameters['4'] == '0') ? '10' : $parameters['4'];
    	}

    	/** ---------------------------------------
    	/**  Perform Query
    	/** ---------------------------------------*/
    	
    	$sql = "SELECT DISTINCT(exp_weblog_titles.entry_id), exp_weblog_titles.title, exp_weblog_titles.weblog_id,
    			exp_weblog_titles.author_id, exp_weblog_titles.entry_date, exp_weblog_data.*
                FROM   exp_weblog_titles, exp_weblog_data ";
                
        if ($this->categories != '' && $this->categories != 'none')                     
        {
			$sql .= "INNER JOIN exp_category_posts ON exp_weblog_titles.entry_id = exp_category_posts.entry_id ";
		}        
                
		$sql .= "WHERE	exp_weblog_titles.entry_id = exp_weblog_data.entry_id ";
		
		if ($this->userdata['group_id'] != '1' && ! $this->userdata['can_edit_other_entries'])
        {            
            $sql .= "AND exp_weblog_titles.author_id = '".$this->userdata['member_id']."' ";
        }
		
		if ($entry_id != '')
		{
			$sql .= "AND exp_weblog_titles.entry_id = '{$entry_id}' ";
		}
		else
		{
			$sql .= str_replace('exp_weblogs.weblog_id','exp_weblog_titles.weblog_id', $this->weblog_sql)." ";
		}
                
        if ($this->categories != '' && $this->categories != 'none')                     
        {
        	$sql .= $FNS->sql_andor_string($this->categories, 'exp_category_posts.cat_id')." ";
		}
		
		if ($this->status != '')                     
        {
        	$sql .= $FNS->sql_andor_string($this->status, 'exp_weblog_titles.status')." ";
		}
		
		if ($entry_id == '')
		{
			$sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";
		}
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return new XML_RPC_Response('0','802', $LANG->line('no_entries_found'));
		}
		
		if ($entry_id != '')
    	{
    		$this->parse_weblog($query->row['weblog_id']);
    	}
		
		/** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
      	if ($this->parse_type === true)
      	{
        	if ( ! class_exists('Typography'))
        	{
        	    require PATH_CORE.'core.typography'.EXT;
        	}
                
        	$TYPE = new Typography(0); 
        	$TYPE->encode_email = false;
		}
		
		/** ---------------------------------------
    	/**  Process Output
    	/** ---------------------------------------*/
    	
    	$response = array();
    	
		foreach($query->result as $row)
		{
			$entry_content  = '<title>'.$row['title'].'</title>';
			
			// Fields:  Textarea and Text Input Only
			
    		foreach($this->fields as $field_id => $field_data)
    		{
    			if (isset($row['field_id_'.$field_id]))
    			{
    				$field_content = $row['field_id_'.$field_id];
    				
    				if ($this->parse_type === true)
       				{
       					$field_content = $TYPE->parse_type($field_content,
       														 array(	'text_format'	=> ($this->text_format === false) ? 'none' : $field_data['1'],
       														 		'html_format'	=> $this->html_format,
       														 		'auto_links'	=> 'n',
       														 		'allow_img_url'	=> 'n'
       														 	   )
       														 );
					}    			
    			
    				if ($this->block_entry === true)
    				{
    					$entry_content .= (trim($field_content) != '') ? $this->LB.$field_content : '';
    				}
    				else
    				{
    					$entry_content .= $this->LB."<{$field_data['0']}>".$field_content."</{$field_data['0']}>";
    				}
    			}
    		}
    	
    		// Categories
    		
    		$cat_array = array();
    		
    		$sql = "SELECT	exp_categories.cat_name, exp_categories.cat_id
    				FROM	exp_category_posts, exp_categories
    				WHERE	exp_category_posts.cat_id = exp_categories.cat_id
    				AND		exp_category_posts.entry_id = '".$row['entry_id']."' ";
    				
    		$sql .= ($this->cat_output == 'name') ? "ORDER BY cat_name" : "ORDER BY cat_id";
    				
    		$results = $DB->query($sql);
    		
    		if ($results->num_rows > 0)
    		{
    			foreach($results->result as $rrow)
    			{
    				$cat_array[] = ($this->cat_output == 'name') ? $rrow['cat_name'] : $rrow['cat_id'];
    			}
    		}
    		
    		$cats = (sizeof($cat_array) > 0) ? implode('|', $cat_array) : '';
       		$entry_content .= ($this->block_entry === true) ? '' : $this->LB."<category>".$cats."</category>";
    		
    		// Entry Data to XML-RPC form
    		
    		$entry_data = new XML_RPC_Values(array(
    												'userid' => 
    												new XML_RPC_Values($row['author_id'],'string'),
    												'dateCreated' => 
    												new XML_RPC_Values(date('Y-m-d\TH:i:s',$row['entry_date']).'+00:00','dateTime.iso8601'),
    												'blogid' => 
    												new XML_RPC_Values($row['weblog_id'],'string'),
    												'content' => 
    												new XML_RPC_Values($entry_content,'string'),
    												'postid' => 
    												new XML_RPC_Values($row['entry_id'],'string'),
    												'category' =>
    												new XML_RPC_Values($cats,'string'),
    												),
    										   'struct');
			
			array_push($response, $entry_data);	
		}
		
		if ($entry_id != '')
		{
    		return new XML_RPC_Response($entry_data);
    	}
    	else
    	{
    		return new XML_RPC_Response(new XML_RPC_Values($response, 'array'));
    	}
    }
    /* END */



	/** -----------------------------------------
    /**  USAGE: Get Post.
    /** -----------------------------------------*/
    
    function getPost($plist)
    {
    	global $LANG;
    	
    	$parameters = $plist->output_parameters();
    	
    	return $this->getRecentPosts($plist, $parameters['1']);
    }
    /* END */
    
    
    
	/** -----------------------------------------
    /**  USAGE: Delete Post.
    /** -----------------------------------------*/
    
    function deletePost($plist)
    {
    	global $LANG, $DB, $STAT;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if (   $this->userdata['group_id'] != '1' AND
    		 ! $this->userdata['can_delete_self_entries'] AND
    		 ! $this->userdata['can_delete_all_entries'])
       	{
            return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
        }
        
        /** ---------------------------------------
    	/**  Retrieve Entry Information
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT weblog_id, author_id, entry_id
    						 FROM exp_weblog_titles 
    						 WHERE entry_id = '".$parameters['1']."'");
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('no_entry_found'));
    	}
    	
    	/** ---------------------------------------
    	/**  Check Delete Privileges
    	/** ---------------------------------------*/
    	
    	if ($this->userdata['group_id'] != '1')
    	{
    		if ( ! in_array($query->row['weblog_id'], $this->userdata['allowed_blogs']))
			{
				return new XML_RPC_Response('0','802', $LANG->line('unauthorized_action'));
			}
			
			if ($query->row['author_id'] == $this->userdata['member_id'])
            {
                if ( ! $this->userdata['can_delete_self_entries'])
                {             
                    return new XML_RPC_Response('0','802', $LANG->line('unauthorized_action'));
                }
            }
            else
            {
                if ( ! $this->userdata['can_delete_all_entries'])
                {             
                    return new XML_RPC_Response('0','802', $LANG->line('unauthorized_action'));
                }
            }
        }
        
        /** ---------------------------------------
    	/**  Perform Deletion
    	/** ---------------------------------------*/
    	
		$DB->query("DELETE FROM exp_weblog_titles WHERE entry_id = '".$query->row['entry_id']."'");
		$DB->query("DELETE FROM exp_weblog_data WHERE entry_id = '".$query->row['entry_id']."'");
		$DB->query("DELETE FROM exp_category_posts WHERE entry_id = '".$query->row['entry_id']."'");
		$DB->query("DELETE FROM exp_trackbacks WHERE entry_id = '".$query->row['entry_id']."'");
		
		$DB->query("UPDATE exp_members 
					SET total_entries = total_entries-1
					WHERE member_id = '".$query->row['author_id']."'");
					
		$results = $DB->query("SELECT author_id
								FROM exp_comments
								WHERE status = 'o' 
								AND entry_id = '".$query->row['entry_id']."'
								AND author_id != '0'");
								
		if ($results->num_rows > 0)
		{
			foreach($results->result as $row)
			{
				$returns = $DB->query("SELECT COUNT(*) AS count
										FROM exp_comments
										WHERE status = 'o'
										AND entry_id = '".$query->row['entry_id']."'
										AND author_id = '".$row['author_id']."'");
										
				$DB->query("UPDATE exp_members
							SET total_comments = total_comments - ".$returns->row['count']."' 
							WHERE member_id = '".$row['author_id']."'");
			}
		}
					
		$DB->query("DELETE FROM exp_comments WHERE entry_id = '".$query->row['entry_id']."'");
		
    	$STAT->update_weblog_stats($query->row['weblog_id']);
		$STAT->update_comment_stats($query->row['weblog_id']);
		$STAT->update_trackback_stats($query->row['weblog_id']);
		
		return new XML_RPC_Response(new XML_RPC_Values(1,'boolean'));
    }
    /* END */
    
    
    
    
	/** -----------------------------------------
    /**  USAGE: Submit New Post.
    /** -----------------------------------------*/
    
    function newPost($plist)
    {
    	global $DB, $LANG, $FNS, $LOC, $PREFS, $REGX, $IN, $STAT;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Out Weblog Information
    	/** ---------------------------------------*/
    	
    	$this->parse_weblog($parameters['1']);
    	
    	$this->status = ($parameters['5'] == '0') ? 'closed' : 'open';
    	$sticky = 'n';
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Meta-Information
    	/** ---------------------------------------*/
    	
		// using entities because of <title> conversion by xss_clean()
    	if (preg_match('/&lt;title&gt;(.+?)&lt;\/title&gt;/is', $parameters['4'], $matches))
    	{
    		$this->title = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities(trim($matches['1'])) : $matches['1'];
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    	} 
    	
    	if (preg_match('/<weblog_id>(.+?)<\/weblog_id>/is', $parameters['4'], $matches))
    	{
    		$this->weblog_id = trim($matches['1']);
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    		$this->parse_weblog($this->weblog_id);
    	}
    	
    	if (preg_match('/<category>(.*?)<\/category>/is', $parameters['4'], $matches))
    	{
    		$this->categories = trim($matches['1']);
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    		
    		if (strlen($this->categories) > 0)
    		{
    			$this->check_categories("AND exp_weblogs.weblog_id = '{$this->weblog_id}'");
    		}
    	}
    	
    	if (preg_match('/<sticky>(.+?)<\/sticky>/is', $parameters['4'], $matches))
    	{
    		$sticky = (trim($matches['1']) == 'yes' OR trim($matches['1']) == 'y') ? 'y' : 'n';
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    	}
    	
		/** ---------------------------------------
    	/**  Default Weblog Data for weblog_id
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT deft_comments, deft_trackbacks, cat_group,
    						 blog_title, blog_url,
    						 weblog_notify_emails, weblog_notify, comment_url
    						 FROM exp_weblogs
    						 WHERE weblog_id = '{$this->weblog_id}'"); 
    	
    	if ($query->num_rows == 0)
        {
            return new XML_RPC_Response('0','802', $LANG->line('invalid_weblog'));
        }
        
		$notify_address = ($query->row['weblog_notify'] == 'y' AND $query->row['weblog_notify_emails'] != '') ? $query->row['weblog_notify_emails'] : '';

		/** ---------------------------------------
    	/**  URL Title Unique?
    	/** ---------------------------------------*/
		
		$url_title = $REGX->create_url_title($this->title, TRUE);
		
		$sql = "SELECT count(*) AS count 
				FROM exp_weblog_titles 
				WHERE url_title = '".$DB->escape_str($url_title)."' 
				AND weblog_id = '{$this->weblog_id}'";
				
		$results = $DB->query($sql);
		
		// Already have default title
		if ($results->row['count'] > 0)
		{
			// Give it a moblog title
			$inbetween = ($PREFS->ini('word_separator') == 'dash') ? '-' : '_';
			$url_title .= $inbetween.'api';
		
			/** ---------------------------------------
    		/**  Multiple Title Find
    		/** ---------------------------------------*/
			
			$sql = "SELECT count(*) AS count 
					FROM exp_weblog_titles 
					WHERE url_title LIKE '".$DB->escape_like_str($url_title)."%' 
					AND weblog_id = '{$this->weblog_id}'";
					
			$results = $DB->query($sql);
			$url_title .= $results->row['count']+1;
		}
    	
		/** ---------------------------------
        /**  Build our query string
        /** ---------------------------------*/
        
        $metadata = array(
        					'entry_id'          => '',
							'weblog_id'         => $this->weblog_id,
							'author_id'         => $this->userdata['member_id'],
							'title'             => $this->title,
							'url_title'         => $url_title,
							'ip_address'		=> $IN->IP,
							'entry_date'        => $LOC->now,
							'edit_date'			=> gmdate("YmdHis", $LOC->now),
							'year'              => gmdate('Y', $LOC->now),
							'month'             => gmdate('m', $LOC->now),
							'day'               => gmdate('d', $LOC->now),
							'sticky'            => $sticky,
							'status'            => $this->status,
							'allow_comments'    => $query->row['deft_comments'],
							'allow_trackbacks'  => $query->row['deft_trackbacks']
						  );
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Field Data
    	/** ---------------------------------------*/
    	
    	$entry_data = array('weblog_id' => $this->weblog_id);
    	
    	if (sizeof($this->fields) > 0)
    	{
    		foreach($this->fields as $field_id => $afield)
    		{
    			if (preg_match('/<'.$afield['0'].'>(.+?)<\/'.$afield['0'].'>/is', $parameters['4'], $matches))
    			{
    				if ( ! isset($entry_data['field_id_'.$field_id]))
    				{
    					$entry_data['field_id_'.$field_id] = $matches['1'];
    					$entry_data['field_ft_'.$field_id] = $afield['1'];
    				}
    				else
    				{
    					$entry_data['field_id_'.$field_id] .= "\n". $matches['1'];
    				}
    				
    				$parameters['4'] = trim(str_replace($matches['0'], '', $parameters['4']));
    			}
    		}    	
    	}
    	
    	if (trim($parameters['4']) != '')
    	{
    		if ( ! isset($entry_data[$this->field]))
    		{
    			$entry_data['field_id_'.$this->field] = trim($parameters['4']);
    			$entry_data['field_ft_'.$this->field] = $this->fields[$this->field]['1'];
    		}
    		else
    		{
    			$entry_data[$this->field] .= "\n".trim($parameters['4']);
    		}
    	}
    	
    	/** ---------------------------------
        /**  Insert the entry data
        /** ---------------------------------*/
        
        $metadata['site_id'] = $this->site_id;
		    
		$DB->query($DB->insert_string('exp_weblog_titles', $metadata)); 
		
		$entry_data['entry_id']  = $DB->insert_id;
		$entry_data['site_id']   = $this->site_id;
		
		$DB->query($DB->insert_string('exp_weblog_data', $entry_data));
		
    	/** ---------------------------------
        /**  Insert Categories, if any
        /** ---------------------------------*/
        
        if (sizeof($this->ecategories) > 0)
        {
        	foreach($this->ecategories as $catid => $cat_name)
        	{
        		$DB->query("INSERT INTO exp_category_posts 
        					(entry_id, cat_id) 
        					VALUES 
        					('".$entry_data['entry_id']."', '$catid')");
        	}        
        }
        
		/** ----------------------------
		/**  Send admin notification
		/** ----------------------------*/
				
		if ($notify_address != '')
		{         
			$swap = array(
							'name'				=> $this->userdata['screen_name'],
							'email'				=> $this->userdata['email'],
							'weblog_name'		=> $query->row['blog_title'],
							'entry_title'		=> $metadata['title'],
							'entry_url'			=> $FNS->remove_double_slashes($query->row['blog_url'].'/'.$metadata['url_title'].'/'),
							'comment_url'		=> $FNS->remove_double_slashes($query->row['comment_url'].'/'.$metadata['url_title'].'/'));
						 
			
			$template = $FNS->fetch_email_template('admin_notify_entry');

			$email_tit = $FNS->var_swap($template['title'], $swap);
			$email_msg = $FNS->var_swap($template['data'], $swap);
							   
			// We don't want to send a notification if the person
			// leaving the entry is in the notification list
		
			$notify_address = str_replace($this->userdata['email'], "", $notify_address);
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
				
				$email = new EEmail;
				
				foreach (explode(',', $notify_address) as $addy)
				{
					$email->initialize();
					$email->wordwrap = false;
					$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
					$email->to($addy); 
					$email->reply_to($PREFS->ini('webmaster_email'));
					$email->subject($email_tit);	
					$email->message($REGX->entities_to_ascii($email_msg));		
					$email->Send();
				}
			}
		}
        
        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        
        if ($PREFS->ini('new_posts_clear_caches') == 'y')
        {
			$FNS->clear_caching('all');
		}
		else
		{
			$FNS->clear_caching('sql');
		}
        
		/** ---------------------------------------
		/**  Update those stats, stat!
		/** ---------------------------------------*/
		
	    $STAT->update_weblog_stats($this->weblog_id);
    
	    $query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '".$this->userdata['member_id']."'");
	    $total_entries = $query->row['total_entries'] + 1;
                
	    $DB->query("UPDATE exp_members set total_entries = '{$total_entries}', last_entry_date = '".$LOC->now."' WHERE member_id = '".$this->userdata['member_id']."'");
        
        /** ---------------------------------
        /**  Return Entry ID of new entry
        /** ---------------------------------*/
        
        return new XML_RPC_Response(new XML_RPC_Values($entry_data['entry_id'], 'string'));
		
    }
    /* END */
    
    
    
    /** -----------------------------------------
	/**  USAGE: Edit Post
	/** -----------------------------------------*/
    
    function editPost($plist)
    {
    	global $DB, $LANG, $FNS, $LOC, $PREFS, $REGX, $IN;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))

    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'])
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'])
        {            
            // If there aren't any blogs assigned to the user, bail out
            
            if (count($this->userdata['allowed_blogs']) == 0)
            {
                return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
            }
        }
        
    	
    	/** ---------------------------------------
    	/**  Details from Parameters
    	/** ---------------------------------------*/
    	
    	$entry_id = $parameters['1'];
    	
    	$this->status = ($parameters['5'] == '0') ? 'closed' : 'open';
    	$sticky = 'n';
    	
    	/** ---------------------------------------
    	/**  Retrieve Entry Information
    	/** ---------------------------------------*/
    	
    	$sql = "SELECT weblog_id, author_id, title
    			FROM exp_weblog_titles
    			WHERE entry_id = '".$entry_id."' ";
    						 
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('no_entry_found'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'])
        {
        	if ($query->row['author_id'] != $this->userdata['member_id'])
        	{
        		return new XML_RPC_Response('0','802', $LANG->line('entry_uneditable'));
        	}
        } 
        
        $this->weblog_id	= $query->row['weblog_id'];
        $this->title		= $query->row['title'];
        
        $this->parse_weblog($this->weblog_id);
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Meta-Information
    	/** ---------------------------------------*/
    	
		// using entities because of <title> conversion by xss_clean()
    	if (preg_match('/&lt;title&gt;(.+?)&lt;\/title&gt;/is', $parameters['4'], $matches))
    	{
    		$this->title = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities(trim($matches['1'])) : $matches['1'];
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    	} 
    	
    	if (preg_match('/<category>(.*?)<\/category>/is', $parameters['4'], $matches))
    	{
    		$this->categories = trim($matches['1']);
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    		
    		if ($this->categories != '')
    		{
    			$this->check_categories("AND exp_weblogs.weblog_id = '{$this->weblog_id}'", '1');
    		}
    	}
    	
    	if (preg_match('/<sticky>(.+?)<\/sticky>/is', $parameters['4'], $matches))
    	{
    		$sticky = (trim($matches['1']) == 'yes' OR trim($matches['1']) == 'y') ? 'y' : 'n';
    		$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
    	}
    	
    	
		 /** ---------------------------------
        /**  Build our query string
        /** ---------------------------------*/
        
        $metadata = array(
        					'entry_id'			=> $entry_id,
							'title'				=> $this->title,
							'ip_address'		=> $IN->IP,
							'sticky'			=> $sticky,
							'status'			=> $this->status
						  );
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Field Data
    	/** ---------------------------------------*/
    	
    	$entrydata = array('weblog_id' => $this->weblog_id);
    	
    	if (sizeof($this->fields) > 0)
    	{
    		foreach($this->fields as $field_id => $afield)
    		{
    			if ($this->block_entry === true)
    			{
    				// Empty all fields.  Default field will be set with all 
    				// content.
    				
    				$entry_data['field_id_'.$field_id] = '';
    				$entry_data['field_ft_'.$field_id] = $afield['1'];
    			}
    			elseif (preg_match('/<'.$afield['0'].'>(.*?)<\/'.$afield['0'].'>/is', $parameters['4'], $matches))
    			{
    				if ( ! isset($entry_data['field_id_'.$field_id]))
    				{
    					$entry_data['field_id_'.$field_id] = $matches['1'];
    					$entry_data['field_ft_'.$field_id] = $afield['1'];
    				}
    				else
    				{
    					$entry_data['field_id_'.$field_id] .= "\n". $matches['1'];
    				}
    				
    				$parameters['4'] = trim(str_replace($matches['0'], '', $parameters['4']));
    			}
    		}    	
    	}
    	
    	// Default Field for Remaining Content
    	
    	if (trim($parameters['4']) != '' && sizeof($this->fields) > 0)
    	{
    		if ( ! isset($entry_data[$this->field]))
    		{
    			$entry_data['field_id_'.$this->field] = trim($parameters['4']);
    			$entry_data['field_ft_'.$this->field] = $this->fields[$this->field]['1'];
    		}
    		else
    		{
    			$entry_data[$this->field] .= ($this->block_entry === true) ? trim($parameters['4']) : "\n".trim($parameters['4']);
    		}
    	}
    	
    	/** ---------------------------------
        /**  Update the entry data
        /** ---------------------------------*/
		    
		$DB->query($DB->update_string('exp_weblog_titles', $metadata, "entry_id = '$entry_id'"));
		$DB->query($DB->update_string('exp_weblog_data', $entry_data, "entry_id = '$entry_id'"));
		
    	/** ---------------------------------
        /**  Insert Categories, if any
        /** ---------------------------------*/
        
        if (sizeof($this->ecategories) > 0)
        {
        	$DB->query("DELETE FROM exp_category_posts WHERE entry_id = '$entry_id'");
        	
        	foreach($this->ecategories as $cat_id => $cat_name)
        	{
        		$DB->query("INSERT INTO exp_category_posts 
        					(entry_id, cat_id) 
        					VALUES 
        					('".$entry_id."', '$cat_id')");
        	}        
        }
        
        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        
        if ($PREFS->ini('new_posts_clear_caches') == 'y')
        {
			$FNS->clear_caching('all');
		}
		else
		{
			$FNS->clear_caching('sql');
		}
        
        /** ---------------------------------
        /**  Return Boolean TRUE
        /** ---------------------------------*/
        
        return new XML_RPC_Response(new XML_RPC_Values(1,'boolean'));
		
    }
    /* END */
    
    
    
    
    
   	/** ----------------------------------------------
    /**  USAGE: Fetch member data
    /** ----------------------------------------------*/
  
    function fetch_member_data($username, $password)
    {  
        global $DB, $FNS;

        // Query DB for member data.  Depending on the validation type we'll
        // either use the cookie data or the member ID gathered with the session query.
        
        $sql = " SELECT exp_members.weblog_id, 
						exp_members.screen_name, 
						exp_members.member_id, 
						exp_members.email, 
						exp_members.url,
						exp_members.group_id,
						exp_member_groups.*
						FROM exp_members, exp_member_groups 
						WHERE username = '".$DB->escape_str($username)."' 
						AND exp_members.group_id = exp_member_groups.group_id ";
		
		$sha_sql = "AND password = '".$FNS->hash(stripslashes($password))."'";
		$md5_sql = "AND password = '".md5(stripslashes($password))."'";        
        
        $query = $DB->query($sql.$sha_sql);
        
        if ($query->num_rows == 0)
        {
        	$query = $DB->query($sql.$md5_sql);
        	
        	if ($query->num_rows == 0)
        	{
            	return false;
            }
        }
        
        // Turn the query rows into array values
	
		foreach ($query->row as $key => $val)
		{            
			$this->userdata[$key] = $val;                 
		}
		
        /** -------------------------------------------------
        /**  Find Assigned Weblogs
        /** -------------------------------------------------*/
            
        $assigned_blogs = array();
         
		if ($this->userdata['group_id'] == 1)
		{
			$result = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE is_user_blog = 'n'");
		}
		else
		{
			$result = $DB->query("SELECT weblog_id FROM exp_weblog_member_groups WHERE group_id = '".$this->userdata['group_id']."'");
		}
            
		if ($result->num_rows > 0)
		{
			foreach ($result->result as $row)
			{
				$assigned_blogs[] = $row['weblog_id'];
			}
		}
		else
		{
			return false; // Nowhere to Post!!
		}
		
		$this->userdata['assigned_weblogs'] = $assigned_blogs;
		
		return true;  
    }
    /* END */
    
    
    
	/** -----------------------------------------
    /**  USAGE: Parse Out Weblog Parameter Received
    /** -----------------------------------------*/
    

    function parse_weblog($weblog_id)
    {
    	global $DB, $FNS, $LANG, $PREFS;
    	
    	/*
    	Now weblog id can come in many forms:
    	1 - Basic weblog id
    	1|3 - Multiple weblog ids
    	1:5|8|9 - weblog id with category(ies) id(s) specified
    	1:5|8|9:open - weblog id : categories : status
    	*/
    	
    	$x					= explode(':',trim($weblog_id));
    	$this->categories	= ( ! isset($x['1'])) ? '' : trim($x['1']);
    	$this->status		= ( ! isset($x['2'])) ? 'open' : trim($x['2']);
    	
    	$sql				= "SELECT weblog_id, site_id FROM exp_weblogs WHERE ";
    	$this->weblog_sql	= $FNS->sql_andor_string($x['0'], 'exp_weblogs.weblog_id');
       	$sql				= (substr($this->weblog_sql, 0, 3) == 'AND') ? $sql.substr($this->weblog_sql, 3) : $sql.$this->weblog_sql;
        $query				= $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
        	return new XML_RPC_Response('0','802', $LANG->line('invalid_weblog'));        
        }
        
        $this->weblog_id = $query->row['weblog_id'];
        $this->site_id   = $query->row['site_id'];
        
        if ($this->site_id != $PREFS->ini('site_id'))
		{
			$PREFS->site_prefs('', $this->site_id);
		}
    	
    	foreach ($query->result as $row)
    	{
    		if ( ! in_array($row['weblog_id'], $this->userdata['assigned_weblogs']))
    		{
    			return new XML_RPC_Response('0','802', $LANG->line('invalid_weblog'));
    		}
    	}
    	
    	/** ---------------------------------------
    	/**  Check Categories
    	/** ---------------------------------------*/
    	
    	if ($this->categories != '' && $this->categories != 'none')
    	{    	
    		$this->check_categories($this->weblog_sql);		
    	}    	
    	
    	/** ---------------------------------------
    	/**  Find Fields
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT field_name, field_id, field_type, field_fmt FROM exp_weblog_fields, exp_weblogs 
							  WHERE exp_weblogs.field_group = exp_weblog_fields.group_id
							  {$this->weblog_sql} 
							  AND (exp_weblog_fields.field_type = 'textarea'
							  OR exp_weblog_fields.field_type = 'text')
							  ORDER BY field_order");
							  
		foreach($query->result as $row)
		{
			// Default field
			// We try to make it $this->field_name if available otherwise we just use the 
			// first textarea found.
			
			if (($this->field == '' OR $row['field_name'] == $this->field_name) && $row['field_type'] == 'textarea')
			{
				$this->field = $row['field_id'];
			}
			
			$this->fields[$row['field_id']] = array($row['field_name'], $row['field_fmt']);
		}
		
		// Configuation's Field ID trumps all, but only if it is set and found
		// in the fields for the specified weblog
		
		if ($this->field_id != '' && in_array($this->field_id, $this->fields))
		{
			$this->field = $this->field_id;
		}
    }
    /* END */
    
    
    
	/** -----------------------------------------
    /**  USAGE: Check Validity of Categories
    /** -----------------------------------------*/
    
    function check_categories($weblog_sql, $debug = '0')
    {
    	global $DB, $FNS, $LANG;
    	
    	$this->ecategories = array_unique(explode('|', $this->categories));
    		
    	$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.parent_id 
    			FROM   exp_categories, exp_weblogs
    			WHERE  exp_categories.group_id = exp_weblogs.cat_group
    			{$weblog_sql}"; 
    		
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_categories'));
    	}
    	
    	$good		= 0;
    	$all_cats	= array();
    	
    	foreach($query->result as $row)
    	{
    		$all_cats[$row['cat_id']] = $row['cat_name'];
    		
    		if (in_array($row['cat_id'], $this->ecategories) OR in_array($row['cat_name'], $this->ecategories))
    		{
    			$good++;
    			$cat_names[$row['cat_id']] = $row['cat_name'];
    			
    			if ($this->assign_parents == TRUE && $row['parent_id'] != '0')
    			{
    				$this->cat_parents[$row['parent_id']] = 'Parent';
    			}
    		}
    	}
    		
    	if ($good < sizeof($this->ecategories))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_categories'));
    	}
    	else
    	{
    		$this->ecategories = $cat_names;
    		
    		if ($this->assign_parents == TRUE && sizeof($this->cat_parents) > 0)
    		{
    			foreach($this->cat_parents as $kitty => $galore)
    			{
    				$this->ecategories[$kitty] = $all_cats[$kitty];
    			}
    		}
    	}
    }
    /* END */
 
 

	/** -----------------------------------------
    /**  USAGE: Link to Auto Discovery XML
    /** -----------------------------------------*/
    
    function edit_uri()
    {
    	global $FNS, $TMPL, $PREFS;
    	
    	if ($action_id = $FNS->fetch_action_id('Blogger_api', 'edit_uri_output'))
    	{
    		$qs		= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        	$link	= $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id;
        
        	$link .= ( ! isset($TMPL) OR ! $TMPL->fetch_param('weblog_id')) ? '' : '&blog_id='.urlencode($TMPL->fetch_param('weblog_id'));
        	$link .= ( ! isset($TMPL) OR ! $TMPL->fetch_param('config_id')) ? '' : '&config_id='.urlencode($TMPL->fetch_param('config_id'));
        
    		$this->return_data = '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.$link.'" />';	
    	}
    	
    	return $this->return_data;
    }
    /* END */
    



	/** -----------------------------------------
    /**  USAGE: Auto-discovery XML
    /** -----------------------------------------*/
    
    function edit_uri_output()
    {
    	global $PREFS, $FNS, $TMPL;
    	
    	$output = <<<EOT
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
   <service>
      <engineName>ExpressionEngine</engineName> 
      <engineLink>http://expressionengine.com</engineLink>
      <homePageLink>{homepage}</homePageLink>
      <apis>
         <api name="Blogger" preferred="true" apiLink="{api_link}" blogID="{weblog_id}" />
      </apis>
   </service>
</rsd>
EOT;

		$blog_id = ( ! isset($TMPL) OR ! $TMPL->fetch_param('weblog_id')) ? '1' : $TMPL->fetch_param('weblog_id');
		
		// URL Override
		$blog_id = ( ! isset($_GET['weblog_id'])) ? $blog_id : urldecode($_GET['weblog_id']);
		
		$qs			= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
		$site_index	= $FNS->fetch_site_index(0, 0);
        $api_link	= $site_index.$qs.'ACT='.$FNS->fetch_action_id('Blogger_api', 'incoming');
        
        $api_link .= ( ! isset($_GET['config_id'])) ? '' : '&id='.urldecode($_GET['config_id']);

		$output = '<?xml version="1.0"?'.'>'.$this->LB.trim($output);
		$output = str_replace('{api_link}', $api_link, $output);
		$output = str_replace('{homepage}', $site_index, $output);
		$output = str_replace('{weblog_id}', $blog_id, $output);
		
		@header("Content-Type: text/xml");
		exit($output);
		
    }
    /* END */
    
}
/* END */
?>