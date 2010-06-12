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
 File: core.prefs.php
-----------------------------------------------------
 Purpose: This class manages system and user prefs.
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Preferences {
    
    var $core_ini 	 = array();
    var $default_ini = array();
    var $exceptions	 = array();	 // path.php exceptions
    var $cp_cookie_domain = '';
	var $special_tlds = array('com', 'edu', 'net', 'org', 'gov', 'mil', 'int');	// seven special TLDs for cookie domains
	
    /** ------------------------------------------
    /**  Fetch a specific core config variable
    /** ------------------------------------------*/

    function ini($which = '', $slash = FALSE)
    {
        // Note:  Since many prefs we gather are paths, we use the
        // second parameter to checks whether the trailing slash
        // is present.  If not, we'll add it.
        
        if ($which == '')
            return FALSE;
    
        $pref = ( ! isset($this->core_ini[$which])) ? FALSE : $this->core_ini[$which];
        
        if (is_string($pref))
        {
        	if ($slash !== FALSE)
        	{
        		$pref = rtrim($pref, '/').'/';   
        	}
        	
        	$pref = str_replace('\\\\', '\\', $pref);
        }
        
        return $pref;
    }
    /* END */
    
    
    /** ------------------------------------------
    /**  Fetch the Site Prefs from the Database
    /** ------------------------------------------*/
    
    function site_prefs($site_name, $site_id = 1)
	{
		global $DB, $REGX;
		
		if ( ! file_exists(PATH_CORE.'core.sites.php') OR ! isset($this->default_ini['multiple_sites_enabled']) OR $this->default_ini['multiple_sites_enabled'] != 'y')
		{
			$site_name = '';
			$site_id = 1;
		}
		
		if ($site_name != '')
		{
			$query = $DB->query("SELECT es.*
								 FROM exp_sites AS es
								 WHERE es.site_name = '".$DB->escape_str($site_name)."'");
		}
		else
		{
			$query = $DB->query("SELECT es.*
								 FROM exp_sites AS es
								 WHERE es.site_id = '".$DB->escape_str($site_id)."'");	
		}
		
		if ($query->num_rows == 0)
		{
			if ($site_name == '' && $site_id != 1)
			{
				$this->site_prefs('', 1);
				return;
			}
			else
			{
				exit("Site Error:  Unable to Load Site Preferences; No Preferences Found");
			}
		}
		
		/** ------------------------------------------
		/**  Reset Core Preferences back to their Pre-Database Preferences State
		/**  - This way config.php and path.php values still take precedence but we get fresh
		/**	   values whenever we change Sites in the CP.
		/** ------------------------------------------*/
		
		$this->core_ini = $this->default_ini;
		
		$this->core_ini['site_pages'] = FALSE;
		
		/** ------------------------------------------
		/**  Fold in the Preferences in the Database
		/** ------------------------------------------*/
		
		foreach($query->row as $name => $data)
		{	
			if (substr($name, -12) == '_preferences')
			{
				if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
				{
					exit("Site Error:  Unable to Load Site Preferences; Invalid Preference Data");
				}
			
				// Any values in config.php take precedence over those in the database, so it goes second in array_merge()
				$this->core_ini = array_merge($REGX->array_stripslashes(unserialize($data)), $this->core_ini);
			}
			elseif ($name == 'site_pages')
			{
				if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
				{
					$this->core_ini['site_pages'][$query->row['site_id']] = array('uris' => array(), 'templates' => array());
					//$this->core_ini['site_pages']['uris'][1] = '/evil/';
					//$this->core_ini['site_pages']['templates'][1] = 16;
					continue;
				}
				
				$this->core_ini['site_pages'] = $REGX->array_stripslashes(unserialize($data));
			}
			else
			{
				$this->core_ini[str_replace('sites_', 'site_', $name)] = $data;
			}
		}
		
		/** ------------------------------------------
		/**  Control Panel Cookie Domain
		/**	 - Since the cookie domain changes based on the site chosen in the CP and since one
		/**	   could have multiple CPs, some using admin.php with path.php, we have to be a bit more
		/**	   creative in figuring out the correct, usable cookie domain for the CP
		/** ------------------------------------------*/
		
		if (REQ == 'CP' && $this->ini('multiple_sites_enabled') == 'y')
        {
        	$this->cp_cookie_domain = '';
        	
        	if ($site_name != '')
        	{
        		$this->cp_cookie_domain = $this->core_ini['cookie_domain'];
        	}
        	else
        	{
        		if (isset($this->exceptions['site_url']) && $this->exceptions['site_url'] != '')
				{
					$base = $this->exceptions['site_url'];
				}
				else
				{
					$base = $this->default_ini['cp_url'];
				}
				
				$i = 0;
				
				$parts = parse_url($base);

				if (isset($parts['host']))
				{
					if ($REGX->valid_ip($parts['host']) === TRUE)
					{
						 $this->cp_cookie_domain = $parts['host'];
					}
					else
					{
						$host_parts = explode('.', $parts['host']);
						
						if (count($host_parts) > 1)
						{
							// unless the TLD is one of the seven special ones, a cookie domain must have a minimum of
							// 3 periods.  ".example.com" is allowed but ".example.us" for instance, is not.
							// reference: http://wp.netscape.com/newsref/std/cookie_spec.html
							$max_parts = (in_array(strtolower(substr($parts['host'], -3)), $this->special_tlds)) ? 2 : 3;
		
							while(count($host_parts) > 0 && $i < $max_parts)
							{
								$this->cp_cookie_domain = '.'.array_pop($host_parts).$this->cp_cookie_domain; ++$i;
							}
						}
					}
				}
			}
        }
		
		/** ------------------------------------------
		/**  Few More Variables
		/** ------------------------------------------*/		
		
		$this->core_ini['site_short_name']	= $query->row['site_name'];
		$this->core_ini['site_name'] 		= $query->row['site_label']; // Legacy code as 3rd Party modules likely use it

		// Need this so we know the base url a page belongs to
		if (isset($this->core_ini['site_pages'][$query->row['site_id']]))
		{
			$url = $this->ini('site_url').'/';
			$url .= $this->ini('site_index').'/';

			$this->core_ini['site_pages'][$query->row['site_id']]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);
		}

		// master tracking override?
		if ($this->ini('disable_all_tracking') == 'y')
		{
			$this->disable_tracking();
		}
		
		// If we just reloaded, then we reset a few things automatically
		$DB->show_queries = ($this->ini('show_queries') == 'y') ? TRUE : FALSE;
		$DB->enable_cache = ($this->ini('enable_db_caching') == 'y') ? TRUE : FALSE;
	}
    /* END */
    
 
	/** ------------------------------------------
	/**  Disable tracking - used on the fly by certain methods
	/** ------------------------------------------*/
  
	function disable_tracking()
	{
		$this->core_ini['enable_online_user_tracking'] = 'n';
		$this->core_ini['enable_hit_tracking'] = 'n';
		$this->core_ini['enable_entry_view_tracking'] = 'n';
		$this->core_ini['log_referrers'] = 'n';
	}
	/* END */
	
	
	/** ------------------------------------------
    /**  Divine the Location of Prefs in the Database
    /** ------------------------------------------*/
    
    function divination($which)
    {
    	$system_default = array('is_site_on',
								'encryption_type',
								'site_index',
								'site_url',
								'theme_folder_url',
								'theme_folder_path',
								'webmaster_email',
								'webmaster_name',
								'weblog_nomenclature',
								'max_caches',
								'captcha_url',
								'captcha_path',
								'captcha_font',
								'captcha_rand',
								'captcha_require_members',
								'enable_db_caching',
								'enable_sql_caching',
								'force_query_string',
								'show_queries',
								'template_debugging',
								'include_seconds',
								'cookie_domain',
								'cookie_path',
								'user_session_type',
								'admin_session_type',
								'allow_username_change',
								'allow_multi_logins',
								'password_lockout',
								'password_lockout_interval',
								'require_ip_for_login',
								'require_ip_for_posting',
								'allow_multi_emails',
								'require_secure_passwords',
								'allow_dictionary_pw',
								'name_of_dictionary_file',
								'xss_clean_uploads',
								'redirect_method',
								'deft_lang',
								'xml_lang',
								'charset',
								'send_headers',
								'gzip_output',
								'log_referrers',
								'max_referrers',
								'time_format',
								'server_timezone',
								'server_offset',
								'daylight_savings',
								'default_site_timezone',
								'default_site_dst',
								'honor_entry_dst',
								'mail_protocol',
								'smtp_server',
								'smtp_username',
								'smtp_password',
								'email_debug',
								'email_charset',
								'email_batchmode',
								'email_batch_size',
								'mail_format',
								'word_wrap',
								'email_console_timelock',
								'log_email_console_msgs',
								'cp_theme',
								'email_module_captchas',
								'log_search_terms',
								'secure_forms',
								'deny_duplicate_data',
								'redirect_submitted_links',
								'enable_censoring',
								'censored_words',
								'censor_replacement',
								'banned_ips',
								'banned_emails',
								'banned_usernames',
								'banned_screen_names',
								'ban_action',
								'ban_message',
								'ban_destination',
								'enable_emoticons',
								'emoticon_path',
								'recount_batch_total',
								'remap_pm_urls',
								'remap_pm_dest',
								'new_version_check',
								'publish_tab_behavior',
								'sites_tab_behavior',
								'enable_throttling',
								'banish_masked_ips',
								'max_page_loads',
								'time_interval',
								'lockout_time',
								'banishment_type',
								'banishment_url',
								'banishment_message',
								'enable_search_log',
								'max_logged_searches');
		
		$mailinglist_default = array('mailinglist_enabled', 'mailinglist_notify', 'mailinglist_notify_emails');
		
		$member_default = array('un_min_len',
								'pw_min_len',
								'allow_member_registration',
								'allow_member_localization',
								'req_mbr_activation',
								'new_member_notification',
								'mbr_notification_emails',
								'require_terms_of_service',
								'use_membership_captcha',
								'default_member_group',
								'profile_trigger',
								'member_theme',
								'enable_avatars',
								'allow_avatar_uploads',
								'avatar_url',
								'avatar_path',
								'avatar_max_width',
								'avatar_max_height',
								'avatar_max_kb',
								'enable_photos',
								'photo_url',
								'photo_path',
								'photo_max_width',
								'photo_max_height',
								'photo_max_kb',
								'allow_signatures',
								'sig_maxlength',
								'sig_allow_img_hotlink',
								'sig_allow_img_upload',
								'sig_img_url',
								'sig_img_path',
								'sig_img_max_width',
								'sig_img_max_height',
								'sig_img_max_kb',
								'prv_msg_upload_path',
								'prv_msg_max_attachments',
								'prv_msg_attach_maxsize',
								'prv_msg_attach_total',
								'prv_msg_html_format',
								'prv_msg_auto_links',
								'prv_msg_max_chars',
								'memberlist_order_by',
								'memberlist_sort_order',
								'memberlist_row_limit');
								
		$template_default = array('strict_urls',
								  'site_404',
								  'save_tmpl_revisions',
								  'max_tmpl_revisions',
								  'save_tmpl_files',
								  'tmpl_file_basepath');
								  
		$weblog_default = array('enable_image_resizing',
								'image_resize_protocol',
								'image_library_path',
								'thumbnail_prefix',
								'word_separator',
								'use_category_name',
								'reserved_category_word',
								'auto_convert_high_ascii',
								'new_posts_clear_caches',
								'auto_assign_cat_parents');
								
		$name = $which.'_default';
		
		return ${$name};		
    }
    /* END */
    
}
// END CLASS
?>