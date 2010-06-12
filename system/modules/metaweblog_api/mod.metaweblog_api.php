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
 File: mod.metaweblog_api.php
-----------------------------------------------------
 Purpose: Metaweblog API Functionality
=====================================================

*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Metaweblog_api {

    var $return_data	= ''; 						// Bah!
    var $LB				= "\r\n";					// Line Break for Entry Output
    
    var $status			= '';						// Retrieving
    var $weblog			= '';
    var $fields			= array();
    var $userdata		= array();
    
    var $title			= 'MetaWeblog API Entry';	// Default Title
    var $weblog_id		= '1';						// Default Weblog ID
    var $site_id		= '1';						// Default Site ID
    var $blog_url		= '';						// Weblog Blog URL for Permalink
    var $comment_url	= '';						// Comment URL for Permalink
    var $deft_category	= '';						// Default Category for Weblog
    
    var $excerpt_field	= '1';						// Default Except Field ID
    var $content_field	= '2';						// Default Content Field ID
    var $more_field		= '3';						// Default More Field ID
    var $keywords_field = '0';						// Default Keywords Field ID
    var $upload_dir		= '';						// Upload Directory for Media Files
    
    var $field_name		= 'body';					// Default Field Name
    var $entry_status	= 'null';					// Entry Status from Configuration
    var $field_data		= array();					// Array of Field Data
    var $field_format	= array();					// Array of Field Formats
    var $categories 	= array();					// Categories (new/edit/get entry)
    var $assign_parents	= TRUE;						// Assign cat parents to post
    var $cat_parents	= array();					// Parent categories of new/edited entry
    
    var $parse_type		= FALSE;					// Use Typography class when sending entry?
    var $html_format	= 'none';					// Weblog's HTML Formatting Preferences

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Metaweblog_api()
    {        
    	global $LANG, $DB, $PREFS;
    	
    	$LANG->fetch_language_file('metaweblog_api');
    	
    	$id = ( isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : '1';
    	
    	$this->assign_parents = ($PREFS->ini('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
    	
    	/** ----------------------------------------
    	/**  Configuration Options
    	/** ----------------------------------------*/
    	
    	$query = $DB->query("SELECT * FROM exp_metaweblog_api WHERE metaweblog_id = '{$id}'");
    		
    	if ($query->num_rows > 0)
    	{
    		foreach($query->row as $name => $pref)
    		{
    			$name = str_replace('metaweblog_', '', $name);
    			$name = str_replace('_id', '', $name);
    			
    			if ($pref == 'y' OR $pref == 'n')
    			{
    				$this->{$name} = ($pref == 'y') ? true : false;
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
    /**  USAGE: Incoming MetaWeblog API Requests
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
    	
    	$functions = array(	'metaWeblog.newPost'		=> array('function' => 'Metaweblog_api.newPost'),
    						'metaWeblog.editPost'		=> array('function' => 'Metaweblog_api.editPost'),
    						'metaWeblog.getPost'		=> array('function' => 'Metaweblog_api.getPost'),
    						'metaWeblog.getCategories'	=> array('function' => 'Metaweblog_api.getCategories'),
    						'metaWeblog.getRecentPosts'	=> array('function' => 'Metaweblog_api.getRecentPosts'),
    						'metaWeblog.deletePost'		=> array('function' => 'Metaweblog_api.deletePost'),
    						'metaWeblog.getUsersBlogs'	=> array('function' => 'Metaweblog_api.getUsersBlogs'),
    						'metaWeblog.newMediaObject' => array('function' => 'Metaweblog_api.newMediaObject'),
    						
    						'blogger.getUserInfo'		=> array('function' => 'Metaweblog_api.getUserInfo'),
    						'blogger.getUsersBlogs'		=> array('function' => 'Metaweblog_api.getUsersBlogs'),
    						'blogger.deletePost'		=> array('function' => 'Metaweblog_api.deletePost'),
    						
    						'mt.getCategoryList'		=> array('function' => 'Metaweblog_api.getCategoryList'),
    						'mt.getPostCategories'		=> array('function' => 'Metaweblog_api.getPostCategories'),
    						'mt.publishPost'			=> array('function' => 'Metaweblog_api.publishPost'),    						
    						'mt.getRecentPostTitles'	=> array('function' => 'Metaweblog_api.getRecentPostTitles'),
    						'mt.setPostCategories'		=> array('function' => 'Metaweblog_api.setPostCategories'),
    						'mt.supportedMethods'		=> array('function' => 'this.listMethods'),
    						'mt.supportedTextFilters'	=> array('function' => 'Metaweblog_api.supportedTextFilters'),
    						'mt.getTrackbackPings'		=> array('function' => 'Metaweblog_api.getTrackbackPings')
							);
							
							
		/** ---------------------------------
    	/**  Instantiate the Server Class
    	/** ---------------------------------*/
    	
		$server = new XML_RPC_Server($functions, TRUE, FALSE);
    }
    /* END */
    
    
	/** -----------------------------------------
    /**  USAGE: Submit New Post.
    /** -----------------------------------------*/
    
    function newPost($plist)
    {
    	global $DB, $LANG, $FNS, $LOC, $PREFS, $REGX, $IN, $STAT;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Out Weblog Information
    	/** ---------------------------------------*/
    	
    	$this->parse_weblog($parameters['0']);
    	
    	if ($this->entry_status != '' && $this->entry_status != 'null')
    	{
    		$this->status = $this->entry_status;
    	}
    	else
    	{
    		$this->status = ($parameters['4'] == '0') ? 'closed' : 'open';
    	}
    	
    	/** ---------------------------------------
    	/**  Default Weblog Data for weblog_id
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT deft_comments, deft_trackbacks, cat_group, deft_category,
    						 blog_title, blog_url, tb_return_url, trackback_field, trackback_system_enabled,
    						 weblog_notify_emails, weblog_notify, comment_url
    						 FROM exp_weblogs 
    						 WHERE weblog_id = '{$this->weblog_id}'"); 
    	
    	if ($query->num_rows == 0)
        {
            return new XML_RPC_Response('0','804', $LANG->line('invalid_weblog'));
        }
        
        foreach($query->row as $key => $value)
        {
        	${$key} =  $value;
        }
        
        $notify_address = ($query->row['weblog_notify'] == 'y' AND $query->row['weblog_notify_emails'] != '') ? $query->row['weblog_notify_emails'] : '';
    	
    	/** ---------------------------------------
    	/**  Parse Data Struct
    	/** ---------------------------------------*/
    	
    	$this->title = $parameters['3']['title'];
    	$ping_urls	 = ( ! isset($parameters['3']['mt_tb_ping_urls'])) ? '' : implode("\n",$parameters['3']['mt_tb_ping_urls']);
    	
    	$this->field_data['excerpt']  = ( ! isset($parameters['3']['mt_excerpt'])) ? '' : $parameters['3']['mt_excerpt'];
		$this->field_data['content']  = ( ! isset($parameters['3']['description'])) ? '' : $parameters['3']['description'];
		$this->field_data['more']	  = ( ! isset($parameters['3']['mt_text_more'])) ? '' : $parameters['3']['mt_text_more'];
		$this->field_data['keywords'] = ( ! isset($parameters['3']['mt_keywords'])) ? '' : $parameters['3']['mt_keywords'];
		
		if (isset($parameters['3']['mt_allow_comments']))
		{
			$deft_comments = ($parameters['3']['mt_allow_comments'] == 1) ? 'y' : 'n';
		}
		
		if (isset($parameters['3']['mt_allow_pings']))
		{
			$deft_trackbacks = ($parameters['3']['mt_allow_pings'] == 1) ? 'y' : 'n';
		}
		
		if (isset($parameters['3']['categories']) && sizeof($parameters['3']['categories']) > 0)
		{
			$cats = array();
			
			foreach($parameters['3']['categories'] as $cat)
			{
				if (trim($cat) != '')
				{
					$cats[] = $cat;
				}
			}
			
			if (sizeof($cats) == 0 && ! empty($deft_category))
			{
				$cats = array($deft_category);
			}
			
			if (sizeof($cats) > 0)
			{
				$this->check_categories(array_unique($cats));
			}
		}
		elseif( ! empty($deft_category))
		{
			$this->check_categories(array($deft_category));
		}
		
		if ( ! empty($parameters['3']['dateCreated']))
		{
			$entry_date = $this->iso8601_decode($parameters['3']['dateCreated']);
		}
		else
		{
			$entry_date = $LOC->now;
		}
		
		
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
        /** --------------------------------*/
        
        $metadata = array(
        					'entry_id'          => '',
							'weblog_id'         => $this->weblog_id,
							'author_id'         => $this->userdata['member_id'],
							'title'             => $this->title,
							'url_title'         => $url_title,
							'ip_address'		=> $IN->IP,
							'entry_date'        => $entry_date,
							'edit_date'			=> gmdate("YmdHis", $entry_date),
							'year'              => gmdate('Y', $entry_date),
							'month'             => gmdate('m', $entry_date),
							'day'               => gmdate('d', $entry_date),
							'status'            => $this->status,
							'allow_comments'    => $deft_comments,
							'allow_trackbacks'  => $deft_trackbacks
						  );
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Field Data
    	/** ---------------------------------------*/
    	
    	$entry_data = array('weblog_id' => $this->weblog_id);
    	
    	// Default formatting for all of the weblog's fields...
    	
    	foreach($this->fields as $field_id => $field_data)
    	{
    		$entry_data['field_ft_'.$field_id] = $field_data['1'];
    	}
    	
    	$convert_breaks = ( ! isset($parameters['3']['mt_convert_breaks'])) ? '' : $parameters['3']['mt_convert_breaks'];
    	
    	if ($convert_breaks != '')
    	{
    		$plugins = $this->fetch_plugins();
    		
    		if ( ! in_array($convert_breaks, $plugins))
    		{
    			$convert_breaks = '';
    		}
    	}
    	
    	if (isset($this->fields[$this->excerpt_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->excerpt_field]))
    		{
    			$entry_data['field_id_'.$this->excerpt_field] .= $this->field_data['excerpt'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->excerpt_field] = $this->field_data['excerpt'];
    		}
    		
			$entry_data['field_ft_'.$this->excerpt_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->excerpt_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->content_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->content_field]))
    		{
    			$entry_data['field_id_'.$this->content_field] .= $this->field_data['content'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->content_field] = $this->field_data['content'];
    		}
    		
			$entry_data['field_ft_'.$this->content_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->content_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->more_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->more_field]))
    		{
    			$entry_data['field_id_'.$this->more_field] .= $this->field_data['more'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->more_field] = $this->field_data['more'];
    		}
    		
			$entry_data['field_ft_'.$this->more_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->more_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->keywords_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->keywords_field]))
    		{
    			$entry_data['field_id_'.$this->keywords_field] .= $this->field_data['keywords'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->keywords_field] = $this->field_data['keywords'];
    		}
    		
			$entry_data['field_ft_'.$this->keywords_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->keywords_field]['1'];
    	}
    	
    	/** ---------------------------------
        /**  DST Setting
        /** ---------------------------------*/
    	
		if ($PREFS->ini('honor_entry_dst') == 'y')
        {
        	$metadata['dst_enabled'] = ($PREFS->ini('daylight_savings') == 'y') ? 'y' : 'n';
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
        
        if (sizeof($this->categories) > 0)
        {
        	foreach($this->categories as $catid => $cat_name)
        	{
        		$DB->query("INSERT INTO exp_category_posts 
        					(entry_id, cat_id) 
        					VALUES 
        					('".$entry_data['entry_id']."', '$catid')");
        	}        
        }
        
        /** ------------------------------------
        /**  Send Pings - So Many Conditions...
        /** ------------------------------------*/
        
        if (trim($ping_urls) != '' && 
        	$trackback_system_enabled == 'y' && 
        	isset($entry_data['field_id_'.$trackback_field]) && 
        	$entry_data['field_id_'.$trackback_field] != '' &&
        	$metadata['status'] != 'closed' &&
        	$entry_date < ($LOC->now + 90))
        {
        	$entry_link = $REGX->prep_query_string(($tb_return_url == '') ? $blog_url : $tb_return_url);
            						
			$entry_link = $FNS->remove_double_slashes($entry_link.'/'.$metadata['url_title'].'/');            
                    
            $tb_data = array(   'entry_id'		=> $entry_data['entry_id'],
                                'entry_link'	=> $FNS->remove_double_slashes($entry_link),
                                'entry_title'	=> $metadata['title'],
                                'entry_content'	=> $entry_data['field_id_'.$trackback_field],
                                'tb_format'		=> $entry_data['field_ft_'.$trackback_field],
                                'weblog_name'	=> $blog_title,
                                'trackback_url'	=> str_replace("\n", ',', $ping_urls)
                            );
                                
            require PATH_MOD.'trackback/mcp.trackback'.EXT;
            
            $TB = new Trackback_CP;
                
            $tb_res = $TB->send_trackback($tb_data);
            
            /** ---------------------------------------
            /**  Update the "sent_trackbacks" field
            /** ---------------------------------------*/
            
            // Fetch the URLs that were sent successfully and update the DB
            
            if (count($tb_res['0']) > 0)
            {
                foreach ($tb_res['0'] as $val)
                {
                    $sent_trackbacks .= $val."\n";
                }
                
                $DB->query("UPDATE exp_weblog_titles SET sent_trackbacks = '$sent_trackbacks' WHERE entry_id = '".$entry_data['entry_id']."'");
            }
            
            $tb_errors = (count($tb_res['1']) > 0) ? TRUE : FALSE;
        }
        
		/** ----------------------------
		/**  Send admin notification
		/** ----------------------------*/
				
		if ($notify_address != '')
		{         
			$swap = array(
							'name'				=> $this->userdata['screen_name'],
							'email'				=> $this->userdata['email'],
							'weblog_name'		=> $blog_title,
							'entry_title'		=> $metadata['title'],
							'entry_url'			=> $FNS->remove_double_slashes($blog_url.'/'.$metadata['url_title'].'/'),
							'comment_url'		=> $FNS->remove_double_slashes($comment_url.'/'.$metadata['url_title'].'/'));
						 
			
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
		
		/** ---------------------------------
        /**  Update Those Sexy Stats, Baby!
        /** ---------------------------------*/
        
        $STAT->update_weblog_stats($this->weblog_id);
        
        $query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '".$this->userdata['member_id']."'");
        $total_entries = $query->row['total_entries'] + 1;
                    
        $DB->query("UPDATE exp_members set total_entries = '{$total_entries}', last_entry_date = '{$entry_date}' WHERE member_id = '".$this->userdata['member_id']."'");

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
    	global $DB, $LANG, $FNS, $LOC, $PREFS, $REGX, $IN, $STAT;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'] && $this->userdata['group_id'] != '1')
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'] && $this->userdata['group_id'] != '1')
        {            
            // If there aren't any blogs assigned to the user, bail out
            
            if (count($this->userdata['assigned_weblogs']) == 0)
            {
                return new XML_RPC_Response('0','804', $LANG->line('invalid_access'));
            }
        }
    	
    	/** ---------------------------------------
    	/**  Retrieve Entry Information
    	/** ---------------------------------------*/
    	
    	$entry_id = $parameters['0'];
    	
    	$sql = "SELECT wt.weblog_id, wt.author_id, wt.title, wt.sent_trackbacks, wt.url_title,
    			wb.blog_title, wb.blog_url, wb.tb_return_url, wb.trackback_field, wb.trackback_system_enabled
    			FROM exp_weblog_titles wt, exp_weblogs wb
    			WHERE wt.weblog_id = wb.weblog_id 
    			AND wt.entry_id = '".$DB->escape_str($entry_id)."' ";
    						 
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','805', $LANG->line('no_entry_found'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'] && $this->userdata['group_id'] != '1')
        {
        	if ($query->row['author_id'] != $this->userdata['member_id'])
        	{
        		return new XML_RPC_Response('0','806', $LANG->line('entry_uneditable'));
        	}
        } 
        
        $this->weblog_id	= $query->row['weblog_id'];
        $this->title		= $query->row['title'];
        
        $this->parse_weblog($this->weblog_id);
        
        if ($this->entry_status != '' && $this->entry_status != 'null')
    	{
    		$this->status = $this->entry_status;
    	}
    	else
    	{
    		$this->status = ($parameters['4'] == '0') ? 'closed' : 'open';
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Weblog Meta-Information
    	/** ---------------------------------------*/
    	
    	$this->title = $parameters['3']['title'];
    	
    	$ping_urls		 = ( ! isset($parameters['3']['mt_tb_ping_urls'])) ? '' : implode("\n",$parameters['3']['mt_tb_ping_urls']);
    	$sent_trackbacks = $query->row['sent_trackbacks'];
    	
    	$this->field_data['excerpt']  = ( ! isset($parameters['3']['mt_excerpt'])) ? '' : $parameters['3']['mt_excerpt'];
		$this->field_data['content']  = ( ! isset($parameters['3']['description'])) ? '' : $parameters['3']['description'];
		$this->field_data['more']	  = ( ! isset($parameters['3']['mt_text_more'])) ? '' : $parameters['3']['mt_text_more'];
		$this->field_data['keywords'] = ( ! isset($parameters['3']['mt_keywords'])) ? '' : $parameters['3']['mt_keywords'];
    	
		/** ---------------------------------
        /**  Build our query string
        /** ---------------------------------*/
        
        $metadata = array(
        					'entry_id'			=> $entry_id,
							'title'				=> $this->title,
							'ip_address'		=> $IN->IP,
							'status'			=> $this->status
						  );
						  
		if (isset($parameters['3']['mt_allow_comments']))
		{
			$metadata['allow_comments'] = ($parameters['3']['mt_allow_comments'] == 1) ? 'y' : 'n';
		}
		
		if (isset($parameters['3']['mt_allow_pings']))
		{
			$metadata['allow_trackbacks'] = ($parameters['3']['mt_allow_pings'] == 1) ? 'y' : 'n';
		}
		
		if ( ! empty($parameters['3']['dateCreated']))
		{
			$metadata['entry_date'] = $this->iso8601_decode($parameters['3']['dateCreated']);
		}
    	
		$metadata['edit_date'] = date("YmdHis");
		
    	/** ---------------------------------------
    	/**  Parse Weblog Field Data
    	/** ---------------------------------------*/
    	
    	$entry_data = array('weblog_id' => $this->weblog_id);
    	
    	$convert_breaks = ( ! isset($parameters['3']['mt_convert_breaks'])) ? '' : $parameters['3']['mt_convert_breaks'];

    	if ($convert_breaks != '')
    	{
    		$plugins = $this->fetch_plugins();
    		
    		if ( ! in_array($convert_breaks, $plugins))
    		{
    			$convert_breaks = '';
    		}
    	}
    	
    	if (isset($this->fields[$this->excerpt_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->excerpt_field]))
    		{
    			$entry_data['field_id_'.$this->excerpt_field] .= $this->field_data['excerpt'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->excerpt_field] = $this->field_data['excerpt'];
    		}
    		
			$entry_data['field_ft_'.$this->excerpt_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->excerpt_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->content_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->content_field]))
    		{
    			$entry_data['field_id_'.$this->content_field] .= $this->field_data['content'];
    		}
    		else
    		{
    			$entry_data['field_id_'.$this->content_field] = $this->field_data['content'];
    		}
    		
			$entry_data['field_ft_'.$this->content_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->content_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->more_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->more_field]))
    		{
    			$entry_data['field_id_'.$this->more_field] .= $this->field_data['more'];
			}
			else
			{
				$entry_data['field_id_'.$this->more_field] = $this->field_data['more'];
			}
			
			$entry_data['field_ft_'.$this->more_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->more_field]['1'];
    	}
    	
    	if (isset($this->fields[$this->keywords_field]))
    	{
    		if (isset($entry_data['field_id_'.$this->keywords_field]))
    		{
    			$entry_data['field_id_'.$this->keywords_field] .= $this->field_data['keywords'];
			}
			else
			{
				$entry_data['field_id_'.$this->keywords_field] = $this->field_data['keywords'];
			}
			
			$entry_data['field_ft_'.$this->keywords_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->keywords_field]['1'];
    	}
    	
    	/** ---------------------------------
        /**  Update the entry data
        /** ---------------------------------*/
		    
		$DB->query($DB->update_string('exp_weblog_titles', $metadata, "entry_id = '$entry_id'"));
		$DB->query($DB->update_string('exp_weblog_data', $entry_data, "entry_id = '$entry_id'"));
		
    	/** ---------------------------------
        /**  Insert Categories, if any
        /** ---------------------------------*/
        
        if ( ! empty($parameters['3']['categories']) && sizeof($parameters['3']['categories']) > 0)
		{
			$this->check_categories($parameters['3']['categories']);
		}
        
        if (sizeof($this->categories) > 0)
        {
        	$DB->query("DELETE FROM exp_category_posts WHERE entry_id = '$entry_id'");
        	
        	foreach($this->categories as $cat_id => $cat_name)
        	{
        		$DB->query("INSERT INTO exp_category_posts 
        					(entry_id, cat_id) 
        					VALUES 
        					('".$entry_id."', '$cat_id')");
        	}        
        }
        
        /** ------------------------------------
        /**  Send Pings - So Many Conditions...
        /** ------------------------------------*/
        
        if (trim($ping_urls) != '' && 
        	$query->row['trackback_system_enabled'] == 'y' && 
        	isset($entry_data['field_id_'.$query->row['trackback_field']]) && 
        	$entry_data['field_id_'.$query->row['trackback_field']] != '' &&
        	$metadata['status'] != 'closed' &&
        	( ! isset($metadata['entry_date']) OR $metadata['entry_date'] < ($LOC->now + 90)))
        {
        	$entry_link = $REGX->prep_query_string(($query->row['tb_return_url'] == '') ? $query->row['blog_url'] : $query->row['tb_return_url']);
            						
			$entry_link = $FNS->remove_double_slashes($entry_link.'/'.$query->row['url_title'].'/');            
                    
            $tb_data = array(   'entry_id'		=> $entry_id,
                                'entry_link'	=> $FNS->remove_double_slashes($entry_link),
                                'entry_title'	=> $metadata['title'],
                                'entry_content'	=> $entry_data['field_id_'.$query->row['trackback_field']],
                                'tb_format'		=> $entry_data['field_ft_'.$query->row['trackback_field']],
                                'weblog_name'	=> $query->row['blog_title'],
                                'trackback_url'	=> str_replace("\n", ',', $ping_urls)
                            );
                                
            require PATH_MOD.'trackback/mcp.trackback'.EXT;
            
            $TB = new Trackback_CP;
                
            $tb_res = $TB->send_trackback($tb_data);
            
            /** ---------------------------------------
            /**  Update the "sent_trackbacks" field
            /** ---------------------------------------*/
            
            // Fetch the URLs that were sent successfully and update the DB
            
            if (count($tb_res['0']) > 0)
            {
                foreach ($tb_res['0'] as $val)
                {
                    $sent_trackbacks .= "\n".$val;
                }
                
                $DB->query("UPDATE exp_weblog_titles SET sent_trackbacks = '$sent_trackbacks' WHERE entry_id = '".$entry_id."'");
            }
            
            $tb_errors = (count($tb_res['1']) > 0) ? TRUE : FALSE;
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
        /**  Count your chickens after they've hatched
        /** ---------------------------------*/
        
        $STAT->update_weblog_stats($this->weblog_id);
        
        /** ---------------------------------
        /**  Return Boolean TRUE
        /** ---------------------------------*/
        
        return new XML_RPC_Response(new XML_RPC_Values(1,'boolean'));
		
    }
    /* END */
    
    /** -----------------------------------------
    /**  MT API:  publishPost
    /** -----------------------------------------*/
    
    function publishPost($plist)
    {
    	global $PREFS, $FNS;
    	
    	/** ---------------------------------
        /**  Clear caches
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
	
	
	/** -----------------------------------------
    /**  USAGE: Get a Single Post
    /** -----------------------------------------*/
    
    function getPost($plist)
    {
    	global $LANG;
    	
    	$parameters = $plist->output_parameters();
    	
    	return $this->getRecentPosts($plist, $parameters['0']);
    }
    /* END */
    
    
	/** -----------------------------------------
    /**  USAGE: Get Recent Posts for User
    /** -----------------------------------------*/
    
    function getRecentPosts($plist, $entry_id = '')
    {
    	global $DB, $LANG, $FNS, $PREFS;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'] && $this->userdata['group_id'] != '1')
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Out Weblog Information
    	/** ---------------------------------------*/
    	
    	if ($entry_id == '')
    	{
    		$this->parse_weblog($parameters['0']);
    		$limit = ( ! empty($parameters['3']) && is_numeric($parameters['3'])) ? $parameters['3'] : '10';
    	}

    	/** ---------------------------------------
    	/**  Perform Query
    	/** ---------------------------------------*/
    	
    	$sql = "SELECT DISTINCT(wt.entry_id), wt.title, wt.url_title, wt.weblog_id, 
    			wt.author_id, wt.entry_date, wt.allow_comments, wt.allow_trackbacks, wt.sent_trackbacks,
    			exp_weblog_data.*
                FROM   exp_weblog_titles wt, exp_weblog_data 
                WHERE wt.entry_id = exp_weblog_data.entry_id ";
		
		if ($this->userdata['group_id'] != '1' && ! $this->userdata['can_edit_other_entries'])
        {            
            $sql .= "AND wt.author_id = '".$this->userdata['member_id']."' ";
        }
		
		if ($entry_id != '')
		{
			$sql .= "AND wt.entry_id = '{$entry_id}' ";
		}
		else
		{
			$sql .= str_replace('exp_weblogs.weblog_id','wt.weblog_id', $this->weblog_sql)." ";
		}
		
		if ($entry_id == '')
		{
			$sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";
		}
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return new XML_RPC_Response('0','805', $LANG->line('no_entries_found'));
		}
		
		if ($entry_id != '')
    	{
    		$this->parse_weblog($query->row['weblog_id']);
    	}
    		
		/** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
      	if ($this->parse_type === TRUE)
      	{
        	if ( ! class_exists('Typography'))
        	{
        	    require PATH_CORE.'core.typography'.EXT;
        	}
                
        	$TYPE = new Typography(); 
        	$TYPE->encode_email = false;
        	$PREFS->core_ini['enable_emoticons'] = 'n';
		}
		
		/** ---------------------------------------
    	/**  Process Output
    	/** ---------------------------------------*/
    	
    	$settings = array();
       	$settings['html_format']	= $this->html_format;
       	$settings['auto_links']		= 'n';
       	$settings['allow_img_url']	= 'y';
    	
    	$response = array();
    	
		foreach($query->result as $row)
		{	
			$convert_breaks = 'none';
			$link = $FNS->remove_double_slashes($this->comment_url.'/'.$row['url_title'].'/');  
	    	
			// Fields:  Textarea and Text Input Only
			
			$this->field_data = array('excerpt' => '', 'content' => '', 'more' => '', 'keywords' => '');
			
    		if (isset($this->fields[$this->excerpt_field]))
    		{
    			if ($this->parse_type === true)
      			{
      				$settings['text_format'] = $row['field_ft_'.$this->excerpt_field];
      		
    				$this->field_data['excerpt'] = $TYPE->parse_type($row['field_id_'.$this->excerpt_field], $settings);
    			}
    			else
    			{
    				$this->field_data['excerpt'] .= $row['field_id_'.$this->excerpt_field];
    			}
    		}
    	
    		if (isset($this->fields[$this->content_field]))
    		{
    			$convert_breaks	= $row['field_ft_'.$this->content_field];
				
				if ($this->parse_type === true)
      			{
      				$settings['text_format'] = $row['field_ft_'.$this->content_field];
      		
    				$this->field_data['content'] = $TYPE->parse_type($row['field_id_'.$this->content_field], $settings);
    			}
    			else
    			{
    				$this->field_data['content'] .= $row['field_id_'.$this->content_field];
    			}
    		}
    	
    		if (isset($this->fields[$this->more_field]))
    		{
    			if ($this->parse_type === true)
      			{
      				$settings['text_format'] = $row['field_ft_'.$this->more_field];
      		
    				$this->field_data['more'] = $TYPE->parse_type($row['field_id_'.$this->more_field], $settings);
    			}
    			else
    			{
    				$this->field_data['more'] .= $row['field_id_'.$this->more_field];
    			}
    		}
    		
    		if (isset($this->fields[$this->keywords_field]))
    		{
    			if ($this->parse_type === true)
      			{
      				$settings['text_format'] = $row['field_ft_'.$this->keywords_field];
      		
    				$this->field_data['keywords'] = $TYPE->parse_type($row['field_id_'.$this->keywords_field], $settings);
    			}
    			else
    			{
    				$this->field_data['keywords'] .= $row['field_id_'.$this->keywords_field];
    			}
    		}
    		
    		
    		// Categories
    		
    		$cat_array = array();
    		
    		$sql = "SELECT	exp_categories.cat_id, exp_categories.cat_name
    				FROM	exp_category_posts, exp_categories
    				WHERE	exp_category_posts.cat_id = exp_categories.cat_id
    				AND		exp_category_posts.entry_id = '".$row['entry_id']."' 
    				ORDER BY cat_id";
    				
    		$results = $DB->query($sql);
    		
    		if ($results->num_rows > 0)
    		{
    			foreach($results->result as $rrow)
    			{
    				$cat_array[] = new XML_RPC_Values($rrow['cat_name'], 'string');
    				//$cat_array[] = new XML_RPC_Values($rrow['cat_id'], 'string');
    			}
    		}
    		
    		// Sent Trackbacks
    		$current_pings = (strlen($query->row['sent_trackbacks']) > 0) ? explode("\n", trim($query->row['sent_trackbacks'])) : array();
    		$pings = array();
    		
    		if (sizeof($current_pings) > 0)
    		{	
    			foreach($current_pings as $value)
    			{
    				$pings[] = new XML_RPC_Values($value, 'string');
    			}
    		}
    		
    		// Entry Data to XML-RPC form
    		
    		$entry_data = new XML_RPC_Values(array(
    												'userid' => 
    												new XML_RPC_Values($row['author_id'],'string'),
    												'dateCreated' => 
    												new XML_RPC_Values(date('Ymd\TH:i:s',$row['entry_date']).'Z','dateTime.iso8601'),
    												'blogid' => 
    												new XML_RPC_Values($row['weblog_id'],'string'),
    												'title' =>
    												new XML_RPC_Values($row['title'], 'string'),
    												'mt_excerpt' => 
    												new XML_RPC_Values($this->field_data['excerpt'],'string'),
    												'description' => 
    												new XML_RPC_Values($this->field_data['content'],'string'),
    												'mt_text_more' => 
    												new XML_RPC_Values($this->field_data['more'],'string'),
    												'mt_keywords' => 
    												new XML_RPC_Values($this->field_data['keywords'],'string'),
    												'mt_convert_breaks' =>
    												new XML_RPC_Values($convert_breaks,'string'),
    												'postid' => 
    												new XML_RPC_Values($row['entry_id'],'string'),
    												'link' => 
    												new XML_RPC_Values($link,'string'),
    												'permaLink' => 
    												new XML_RPC_Values($link,'string'),
    												'categories' =>
    												new XML_RPC_Values($cat_array,'array'),
    												'mt_allow_comments' =>
    												new XML_RPC_Values(($row['allow_comments'] == 'y') ? 1 : 0,'int'),
    												'mt_allow_pings' =>
    												new XML_RPC_Values(($row['allow_trackbacks'] == 'y') ? 1 : 0,'int'),
    												'mt_tb_ping_urls' =>
    												new XML_RPC_Values($pings,'array')
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
    /**  MT API:  getRecentPostTitles
    /** -----------------------------------------*/
    
    function getRecentPostTitles($plist)
    {
    	global $DB, $LANG, $FNS, $PREFS;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'] && $this->userdata['group_id'] != '1')
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
    	
    	/** ---------------------------------------
    	/**  Parse Out Weblog Information
    	/** ---------------------------------------*/
    	
    	$this->parse_weblog($parameters['0']);
    	$limit = ( ! empty($parameters['3']) && is_numeric($parameters['3'])) ? $parameters['3'] : '10';

    	/** ---------------------------------------
    	/**  Perform Query
    	/** ---------------------------------------*/
    	
    	$sql = "SELECT DISTINCT(wt.entry_id), wt.title, wt.weblog_id, 
    			wt.author_id, wt.entry_date
                FROM   exp_weblog_titles wt, exp_weblog_data 
                WHERE wt.entry_id = exp_weblog_data.entry_id ";
		
		if ($this->userdata['group_id'] != '1' && ! $this->userdata['can_edit_other_entries'])
        {            
            $sql .= "AND wt.author_id = '".$this->userdata['member_id']."' ";
        }
		
		$sql .= str_replace('exp_weblogs.weblog_id','wt.weblog_id', $this->weblog_sql)." ";
		
		$sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return new XML_RPC_Response('0','805', $LANG->line('no_entries_found'));
		}

		/** ---------------------------------------
    	/**  Process Output
    	/** ---------------------------------------*/
    	
    	$response = array();
    	
		foreach($query->result as $row)
		{	
    		// Entry Data to XML-RPC form
    		
    		$entry_data = new XML_RPC_Values(array(
    												'userid' => 
    												new XML_RPC_Values($row['author_id'],'string'),
    												'dateCreated' => 
    												new XML_RPC_Values(date('Ymd\TH:i:s',$row['entry_date']).'Z','dateTime.iso8601'),
    												'title' =>
    												new XML_RPC_Values($row['title'], 'string'),
    												'postid' => 
    												new XML_RPC_Values($row['entry_id'],'string'),
    												),
    										   'struct');
    										   
			array_push($response, $entry_data);	
		}
		
		return new XML_RPC_Response(new XML_RPC_Values($response, 'array'));
    }
    /* END */
    
    
    

	/** --------------------------------------
	/**  MT API: getPostCategories
	/** --------------------------------------*/

	function getPostCategories($plist)
	{
		global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	$query = $DB->query("SELECT weblog_id FROM exp_weblog_titles 
    						 WHERE entry_id = '".$DB->escape_str($parameters['0'])."'");
    						 
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','804', $LANG->line('invalid_weblog'));
    	}
    	
    	if ($this->userdata['group_id'] != '1' && ! in_array($query->row['weblog_id'], $this->userdata['assigned_weblogs']))
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
    	
    	$cats = array();
    		
    	$sql = "SELECT	exp_categories.cat_id, exp_categories.cat_name
    			FROM	exp_category_posts, exp_categories
    			WHERE	exp_category_posts.cat_id = exp_categories.cat_id
    			AND		exp_category_posts.entry_id = '".$DB->escape_str($parameters['0'])."' 
    			ORDER BY cat_id";
    				
    	$query = $DB->query($sql);
    		
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{
    			$cat = array();
    			
    			$cat['categoryId'] = new XML_RPC_Values($row['cat_id'],'string');
	      		$cat['categoryName'] = new XML_RPC_Values($row['cat_name'],'string');
	      		
	      		array_push($cats, new XML_RPC_Values($cat, 'struct'));
    		}
    	}
    	
    	return new XML_RPC_Response(new XML_RPC_Values($cats, 'array'));
	}
	/* END */
	
	
	
	/** -----------------------------------------
	/**  MT API: setPostCategories
	/** -----------------------------------------*/
    
    function setPostCategories($plist)
    {
    	global $DB, $LANG, $FNS, $LOC, $PREFS, $REGX, $IN;
    	
    	$parameters = $plist->output_parameters();   	
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_access_edit'] && $this->userdata['group_id'] != '1')
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'] && $this->userdata['group_id'] != '1')
        {            
            // If there aren't any blogs assigned to the user, bail out
            
            if (count($this->userdata['assigned_weblogs']) == 0)
            {
                return new XML_RPC_Response('0','804', $LANG->line('invalid_access'));
            }
        }
    	
    	/** ---------------------------------------
    	/**  Details from Parameters
    	/** ---------------------------------------*/
    	
    	$entry_id = $parameters['0'];
    	
    	/** ---------------------------------------
    	/**  Retrieve Entry Information
    	/** ---------------------------------------*/
    	
    	$sql = "SELECT weblog_id, author_id
    			FROM exp_weblog_titles
    			WHERE entry_id = '".$entry_id."' ";
    						 
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','805', $LANG->line('no_entry_found'));
    	}
    	
    	if ( ! $this->userdata['can_edit_other_entries'] && $this->userdata['group_id'] != '1')
        {
        	if ($query->row['author_id'] != $this->userdata['member_id'])
        	{
        		return new XML_RPC_Response('0','806', $LANG->line('entry_uneditable'));
        	}
        } 
        
        $this->weblog_id	= $query->row['weblog_id'];
        
        $this->parse_weblog($this->weblog_id);
    	
    	/** ---------------------------------------
    	/**  Parse Categories
    	/** ---------------------------------------*/
		
		if ( ! empty($parameters['3']) && sizeof($parameters['3']) > 0)
		{
			$cats = array();
			
			foreach($parameters['3'] as $cat_data)
			{
				$cats[] = $cat_data['categoryId'];
			}
			
			if (sizeof($cats) == 0 && ! empty($this->deft_category))
			{
				$cats = array($this->deft_category);
			}
		
			if (sizeof($cats) > 0)
			{
				$this->check_categories($cats);
			}
		}
		else
		{
			return new XML_RPC_Response(new XML_RPC_Values(1,'boolean'));
			//return new XML_RPC_Response('0','802', $LANG->line('entry_uneditable'));
		}
		
    	/** ---------------------------------
        /**  Insert Categories, if any
        /** ---------------------------------*/
        
        $DB->query("DELETE FROM exp_category_posts WHERE entry_id = '$entry_id'");
        
        if (sizeof($this->categories) > 0)
        {
        	foreach($this->categories as $cat_id => $cat_name)
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


	/** --------------------------------------
	/**  METAWEBLOG API: getCategories
	/** --------------------------------------*/

	function getCategories($plist)
	{
		global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ($this->userdata['group_id'] != '1' && ! in_array($parameters['0'], $this->userdata['assigned_weblogs']))
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_weblog'));
    	}
    	
    	$this->parse_weblog($parameters['0']);
    	
    	$cats = array();
    	
    	$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.cat_description 
    			FROM   exp_categories, exp_weblogs
    			WHERE  FIND_IN_SET(exp_categories.group_id, REPLACE(exp_weblogs.cat_group, '|', ','))
    			AND exp_weblogs.weblog_id = '{$this->weblog_id}'"; 
    		
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{
    			$cat = array();
    			
    			$link = $FNS->remove_double_slashes($this->blog_url.'/C'.$row['cat_id'].'/');
    			
    			$cat['categoryId'] = new XML_RPC_Values($row['cat_id'],'string');
	     		$cat['description'] = new XML_RPC_Values(($row['cat_description'] == '') ? $row['cat_name'] : $row['cat_description'],'string');
	      		$cat['categoryName'] = new XML_RPC_Values($row['cat_name'],'string');
	      		$cat['htmlUrl'] = new XML_RPC_Values($link,'string');
	      		$cat['rssUrl'] = new XML_RPC_Values($link,'string'); // No RSS URL for Categories
	      		
	      		array_push($cats, new XML_RPC_Values($cat, 'struct'));
    		}
    	}
    	
    	return new XML_RPC_Response(new XML_RPC_Values($cats, 'array'));
	}
	/* END */
	
	
	/** --------------------------------------
	/**  MT API: getCategoryList
	/** --------------------------------------*/

	function getCategoryList($plist)
	{
		global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ($this->userdata['group_id'] != '1' && ! in_array($parameters['0'], $this->userdata['assigned_weblogs']))
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_weblog'));
    	}
    	
    	$this->parse_weblog($parameters['0']);
    	
    	$cats = array();
    	
    	$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name 
    			FROM   exp_categories, exp_weblogs
    			WHERE  FIND_IN_SET(exp_categories.group_id, REPLACE(exp_weblogs.cat_group, '|', ','))
    			AND exp_weblogs.weblog_id = '{$this->weblog_id}'"; 
    		
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{
    			$cat = array();
    			
    			$cat['categoryId'] = new XML_RPC_Values($row['cat_id'],'string');
	      		$cat['categoryName'] = new XML_RPC_Values($row['cat_name'],'string');
	      		
	      		array_push($cats, new XML_RPC_Values($cat, 'struct'));
    		}
    	}
    	
    	return new XML_RPC_Response(new XML_RPC_Values($cats, 'array'));
	}
	/* END */
	
	
	/** --------------------------------------
	/**  MT API: getTrackbackPings
	/** --------------------------------------*/

	function getTrackbackPings($plist)
	{
		global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();
    	
    	$pings = array();
    	
    	$sql = "SELECT weblog_id, title, trackback_ip, entry_id
    			FROM   exp_trackbacks
    			WHERE  entry_id = '".$DB->escape_str($parameters['0'])."'"; 
    		
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows > 0)
    	{
    		$results = $DB->query("SELECT blog_url, tb_return_url FROM exp_weblogs WHERE weblog_id = '".$query->row['weblog_id']."'");
    		$this->blog_url = ($results->row['tb_return_url'] == '') ? $results->row['blog_url'] : $results->row['tb_return_url'];
    		
    		foreach($query->result as $row)
    		{
    			$ping = array();
    			
    			$link = $FNS->remove_double_slashes($this->blog_url.'/'.$row['entry_id'].'/');
    			
    			$ping['pingTitle'] = new XML_RPC_Values($row['title'],'string');
	     		$ping['pingURL'] = new XML_RPC_Values($link,'string');
	      		$ping['pingIP'] = new XML_RPC_Values($row['trackback_ip'],'string');
	      		
	      		array_push($cats, new XML_RPC_Values($cat, 'struct'));
    		}
    	}
    	
    	return new XML_RPC_Response(new XML_RPC_Values($cats, 'array'));
	}
	/* END */
	
	
    
    
    /** -----------------------------------------
    /**  USAGE: Parse Out Weblog Parameter Received
    /** -----------------------------------------*/

    function parse_weblog($weblog_id)
    {
    	global $DB, $FNS, $LANG, $PREFS;
    	
    	$weblog_id			= trim($weblog_id);
    	$this->status		= 'open';
    	
    	$sql				= "SELECT weblog_id, blog_url, comment_url, deft_category, weblog_html_formatting, site_id FROM exp_weblogs WHERE ";
    	$this->weblog_sql	= $FNS->sql_andor_string($weblog_id, 'exp_weblogs.weblog_id');
       	$sql				= (substr($this->weblog_sql, 0, 3) == 'AND') ? $sql.substr($this->weblog_sql, 3) : $sql.$this->weblog_sql;
        $query				= $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
        	return new XML_RPC_Response('0','804', $LANG->line('invalid_weblog'));        
        }
        
        $this->weblog_id	 = $query->row['weblog_id'];
        $this->blog_url		 = $query->row['blog_url'];
        $this->comment_url	 = $query->row['comment_url'];
        $this->deft_category = $query->row['deft_category'];
        $this->html_format   = $query->row['weblog_html_formatting'];
        $this->site_id		 = $query->row['site_id'];
        
		if ($this->site_id != $PREFS->ini('site_id'))
		{
			$PREFS->site_prefs('', $this->site_id);
			
			$this->assign_parents = ($PREFS->ini('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
		}
    	
    	foreach ($query->result as $row)
    	{
    		if ( ! in_array($row['weblog_id'], $this->userdata['assigned_weblogs']) && $this->userdata['group_id'] != '1')
    		{
    			return new XML_RPC_Response('0','803', $LANG->line('invalid_weblog'));
    		}
    	}	
    	
    	/** ---------------------------------------
    	/**  Find Fields
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT field_name, field_id, field_type, field_fmt FROM exp_weblog_fields, exp_weblogs 
							  WHERE exp_weblogs.field_group = exp_weblog_fields.group_id
							  {$this->weblog_sql}
							  ORDER BY field_order");
							  
		foreach($query->result as $row)
		{	
			$this->fields[$row['field_id']] = array($row['field_name'], $row['field_fmt']);
		}
    }
    /* END */
    
    
    
	/** -----------------------------------------
    /**  USAGE: Check Validity of Categories
    /** -----------------------------------------*/
    
    function check_categories($array, $debug = '0')
    {
    	global $DB, $FNS, $LANG;
    	
    	$this->categories = array_unique($array);
    	
    	$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.parent_id 
    			FROM   exp_categories, exp_weblogs
    			WHERE  FIND_IN_SET(exp_categories.group_id, REPLACE(exp_weblogs.cat_group, '|', ','))
    			AND exp_weblogs.weblog_id = '{$this->weblog_id}'"; 
    		
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','807', $LANG->line('invalid_categories'));
    	}
    	
    	$good		= 0;
    	$all_cats	= array();
    	
    	foreach($query->result as $row)
    	{
    		$all_cats[$row['cat_id']] = $row['cat_name'];
    		
    		if (in_array($row['cat_id'], $this->categories) OR in_array($row['cat_name'], $this->categories))
    		{
    			$good++;
    			$cat_names[$row['cat_id']] = $row['cat_name'];
    			
    			if ($this->assign_parents == TRUE && $row['parent_id'] != '0')
    			{
    				$this->cat_parents[$row['parent_id']] = 'Parent';
    			}
    		}
    	}
    		
    	if ($good < sizeof($this->categories))
    	{
    		return new XML_RPC_Response('0','807', $LANG->line('invalid_categories'));
    	}
    	else
    	{
    		$this->categories = $cat_names;
    		
    		if ($this->assign_parents == TRUE && sizeof($this->cat_parents) > 0)
    		{
    			foreach($this->cat_parents as $kitty => $galore)
    			{
    				$this->categories[$kitty] = $all_cats[$kitty];
    			}
    		}
    	}
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
            return new XML_RPC_Response('0','808', $LANG->line('invalid_access'));
        }
        
        /** ---------------------------------------
    	/**  Retrieve Entry Information
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT weblog_id, author_id, entry_id
    						 FROM exp_weblog_titles 
    						 WHERE entry_id = '".$parameters['1']."'");
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','805', $LANG->line('no_entry_found'));
    	}
    	
    	/** ---------------------------------------
    	/**  Check Delete Privileges
    	/** ---------------------------------------*/
    	
    	if ($this->userdata['group_id'] != '1')
    	{
    		if ( ! in_array($query->row['weblog_id'], $this->userdata['assigned_weblogs']))
			{
				return new XML_RPC_Response('0','803', $LANG->line('unauthorized_action'));
			}
			
			if ($query->row['author_id'] == $this->userdata['member_id'])
            {
                if ( ! $this->userdata['can_delete_self_entries'])
                {             
                    return new XML_RPC_Response('0','809', $LANG->line('unauthorized_action'));
                }
            }
            else
            {
                if ( ! $this->userdata['can_delete_all_entries'])
                {             
                    return new XML_RPC_Response('0','809', $LANG->line('unauthorized_action'));
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
    
	/** --------------------------------------
	/**  METAWEBLOG API: newMediaObject
	/** --------------------------------------*/

	function newMediaObject($plist)
	{
		global $DB, $LANG, $FNS;
    	
    	$parameters = $plist->output_parameters();
    	
    	if ($this->upload_dir == '')
    	{
    		return new XML_RPC_Response('0','801', $LANG->line('invalid_access'));
    	}
    	
    	if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
    	{
    		return new XML_RPC_Response('0','802', $LANG->line('invalid_access'));
    	}
    	
    	if ($this->userdata['group_id'] != '1' && ! in_array($parameters['0'], $this->userdata['assigned_weblogs']))
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_weblog'));
    	}
    	
    	if ($this->userdata['group_id'] != '1')
    	{
    		$safety = $DB->query("SELECT COUNT(*) AS count 
    							  FROM exp_upload_no_access 
    							  WHERE upload_id = '".$this->upload_dir."' 
    							  AND member_group = '".$this->userdata['group_id']."'");
        
            if ($safety->row['count'] != 0)
            {
                return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
            }
    	}
    	
    	$query = $DB->query("SELECT server_path, url FROM exp_upload_prefs WHERE id = '{$this->upload_dir}'");
    	
    	if ($query->num_rows == 0)
    	{
    		return new XML_RPC_Response('0','803', $LANG->line('invalid_access'));
    	}
        
        /** -------------------------------------
        /**  File name security
        /** -------------------------------------*/
        
        $filename = preg_replace("/\s+/", "_", $parameters['3']['name']);

        $filename = $FNS->filename_security($filename);
        
        /** -------------------------------------
		/**  Upload the image
		/** -------------------------------------*/
		
		$upload_path = $FNS->set_realpath($FNS->remove_double_slashes($query->row['server_path'].'/'));
        
        $filename = $this->unique_filename($filename, $upload_path);
		
		if (!$fp = @fopen($upload_path.$filename,'wb'))
		{
    		return new XML_RPC_Response('0','810', $LANG->line('unable_to_upload'));
    	}
    	
    	@fwrite($fp, $parameters['3']['bits']);// Data base64 decoded by XML-RPC library
		@fclose($fp);
				
		@chmod($upload_path.$filename, 0777);
		
		$response = new XML_RPC_Values(array(
    											'url' => 
    											new XML_RPC_Values($FNS->remove_double_slashes($query->row['url'].'/').$filename,'string'),
    											),
    									'struct');
	
		return new XML_RPC_Response($response);
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  BLOGGER API: Send User Information
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
	/**  METAWEBLOG API: getUsersBlogs
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
    		return new XML_RPC_Response('0','804', $LANG->line('no_weblogs_found'));
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
	
	
	/** -------------------------------------
	/**  ISO-8601 time to server or UTC time
	/** -------------------------------------*/

	function iso8601_decode($time, $utc=TRUE)
	{
		global $PREFS;
	
		// return a time in the localtime, or UTC
		$t = 0;
		
		if (preg_match("#([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})#", $time, $regs))
		{
			/*
			if ($utc === TRUE)
			{
				$t = gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
				
				$time_difference = ($PREFS->ini('server_offset') == '') ? 0 : $PREFS->ini('server_offset');

				$server_time = time()+date('Z');
				$offset_time = $server_time + $time_difference*60;
				$gmt_time = time();

				$diff_gmt_server = ($gmt_time - $server_time) / 3600;
				$diff_weblogger_server = ($offset_time - $server_time) / 3600;
				$diff_gmt_weblogger = $diff_gmt_server - $diff_weblogger_server;
				$gmt_offset = -$diff_gmt_weblogger;
				
				$t -= $gmt_offset;
			}
			*/
			
			$t = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		} 
		return $t;
	}
    /* END */
    
    
    
    /** -------------------------------------
    /**  MT API:  supportedTextFilters
    /** -------------------------------------*/
    
    function supportedTextFilters($plist)
    {
    	$plugin_list = $this->fetch_plugins();
    	
    	$plugins = array();
    	
    	foreach ($plugin_list as $val)
        {        
        	$name = ucwords(str_replace('_', ' ', $val));
        		
			if ($name == 'Br')
			{
				$name = $LANG->line('auto_br');
			}
			elseif ($name == 'Xhtml')
			{
				$name = $LANG->line('xhtml');
			}
        	
        	$plugin = new XML_RPC_Values(array( 'key' => new XML_RPC_Values($val,'string'),
        										'label' => new XML_RPC_Values($name,'string')
        									   ),
        								 'struct');
        								 
        	array_push($plugins, $plugin);
        }
        
        return new XML_RPC_Response(new XML_RPC_Values($plugins, 'array'));
    }
    /* END */
    
    
	/** -------------------------------------
    /**  Fetch installed plugins
    /** -------------------------------------*/
    
    function fetch_plugins()
    {
        global $PREFS;
        
        $exclude = array('auto_xhtml');
    
        $filelist = array('none', 'br', 'xhtml');
    
        if ($fp = @opendir(PATH_PI)) 
        { 
            while (false !== ($file = readdir($fp))) 
            {
            	if ( preg_match("/pi\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
            	{
					$file = substr($file, 3, - strlen(EXT));
					
					if ( ! in_array($file, $exclude))
						$filelist[] = $file;
				}
            }
            
            closedir($fp);
        }
        
        sort($filelist);
		return $filelist;      
    }
    /* END */
    
    /** --------------------------------
  	/**  Guarantees Unique Filename
  	/** --------------------------------*/
  	
  	function unique_filename($filename, $upload_path)
  	{
  		$i = 0;
  		$subtype = '.jpg';
  		
  		/** ------------------------------------
  		/**  Strips out _ and - at end of name part of file name
  		/** ------------------------------------*/
  		
  		$x			= explode('.',$filename);
		$name		=  ( ! isset($x['1'])) ? $filename : $x['0'];
		$sfx		=  ( ! isset($x['1']) OR is_numeric($x[sizeof($x) - 1])) ? $subtype : '.'.$x[sizeof($x) - 1];
		$name		=  (substr($name,-1) == '_' || substr($name,-1) == '-') ? substr($name,0,-1) : $name;
  		$filename	= $name.$sfx;
  		
		while (file_exists($upload_path.$filename))
		{
			$i++;
			$n			=  ($i > 10) ? -2 : -1;
			$x			= explode('.',$filename);
			$name		=  ( ! isset($x['1'])) ? $filename : $x['0'];
			$sfx		=  ( ! isset($x['1'])) ? '' : '.'.$x[sizeof($x) - 1];
			$name		=  ($i==1) ? $name : substr($name,0,$n);
			$name		=  (substr($name,-1) == '_' || substr($name,-1) == '-') ? substr($name,0,-1) : $name;
			$filename	=  $name."$i".$sfx;
		}
		
		return $filename;
	}
    /* END */
    
    
}
/* END */
?>