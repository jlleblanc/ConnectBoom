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
 File: cp.admin.php
-----------------------------------------------------
 Purpose: Control panel Admin page class
=====================================================
*/


if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Admin {

	var $admin_nav = 'click';

	function Admin()
	{
		global $IN, $LANG, $SESS, $LOG, $DSP;

		// This flag determines if a user can edit categories from the publish page.
		$category_exception = ($IN->GBL('M') == 'blog_admin' AND in_array($IN->GBL('P'), array('category_editor', 'edit_category',  'update_category', 'del_category_conf',  'del_category', 'cat_order')) AND $IN->GBL('Z') == 1) ? TRUE : FALSE;

		if ($category_exception == FALSE AND ! $DSP->allowed_group('can_access_admin') AND $IN->GBL('P', 'GET') != 'save_ping_servers')
		{
			return $DSP->no_access_message();
		}

		switch($IN->GBL('M'))
		{
		
			case 'config_mgr' :	
			
				if ( ! $DSP->allowed_group('can_admin_preferences'))
				{
					return $DSP->no_access_message();
				}
				
				switch($IN->GBL('P'))
				{
					case 'update_cfg'	: $this->update_config_prefs();
						break;
					case 'member_cfg'	: $this->member_config_manager();
						break;
					default				: $this->config_manager();
						break;
				}

				break;		
			case 'members' :	
 
				// Instantiate the member administration class
		
				require PATH_CP.'cp.members'.EXT;
				
				$MBR = new Members;
			
				switch($IN->GBL('P'))	
				{
					case 'view_members'				: $MBR->view_all_members();
						break;
					case 'mbr_conf'					: $MBR->member_confirm();
						break;
					case 'mbr_del_conf'				: $MBR->member_delete_confirm();
						break;
					case 'mbr_delete'				: $MBR->member_delete();
						break;
					case 'resend_act_email' 		: $MBR->resend_activation_emails();
						break;
					case 'member_reg_form'			: $MBR->new_member_profile_form();
						break;
					case 'register_member'			: $MBR->create_member_profile();
						break;
					case 'mbr_group_manager'		: $MBR->member_group_manager();
						break;
					case 'edit_mbr_group'			: $MBR->edit_member_group_form();
						break;
					case 'update_mbr_group'			: $MBR->update_member_group();
						break;
					case 'mbr_group_del_conf'		: $MBR->delete_member_group_conf();
						break;
					case 'delete_mbr_group'			: $MBR->delete_member_group();
						break;
					case 'member_banning'			: $MBR->member_banning_forms();
						break;
					case 'save_ban_data'			: $MBR->update_banning_data();
						break;
					case 'profile_fields'			: $MBR->custom_profile_fields();
						break;
					case 'edit_field'				: $MBR->edit_profile_field_form();
						break;
					case 'del_field_conf'			: $MBR->delete_profile_field_conf();
						break;
					case 'delete_field'				: $MBR->delete_profile_field();
						break;
					case 'edit_field_order'			: $MBR->edit_field_order_form();
						break;
					case 'update_field_order'		: $MBR->update_field_order();
						break;
					case 'update_profile_fields'	: $MBR->update_profile_fields();
						break;
					case 'member_search'			: $MBR->member_search_form();
						break;
					case 'do_member_search'			: $MBR->do_member_search();
						break;
					case 'ip_search'				: $MBR->ip_search_form();
						break;
					case 'do_ip_search'				: $MBR->do_ip_search();
						break;
					case 'member_validation'		: $MBR->member_validation();
						break;							
					case 'validate_members'			: $MBR->validate_members();
						break;					
					case 'email_console_logs'		: $MBR->email_console_logs();
						break;					
					case 'view_email'				: $MBR->view_email();
						break;					
					case 'delete_email_console'		: $MBR->delete_email_console_messages();
						break;	
					case 'profile_templates'		: $MBR->profile_templates();
						break;	
					case 'list_templates'			: $MBR->list_templates();
						break;	
					case 'edit_template'			: $MBR->edit_template();
						break;	
					case 'save_template'			: $MBR->save_template();
						break;	
					case 'login_as_member'			: $MBR->login_as_member();
						break;	
					case 'do_login_as_member'		: $MBR->do_login_as_member();
						break;	
					default							: return FALSE;
						break;
					}
					
				break;
			case 'sp_templ' :	
 
				// Instantiate the specialty templates class
		
				require PATH_CP.'cp.specialty_tmp'.EXT;
				
				$SP = new Specialty_Templates;
			
				switch($IN->GBL('P'))	
				{
					case 'mbr_notification_tmpl'		: $SP->mbr_notification_tmpl();
						break;
					case 'edit_notification_tmpl'		: $SP->edit_notification_tmpl();
						break;
					case 'update_notification_tmpl'		: $SP->update_notification_tmpl();
						break;
					case 'offline_tmpl' 				: $SP->offline_template();
						break;
					case 'update_offline_template'		: $SP->update_offline_template();
						break;
					case 'user_messages_tmpl' 			: $SP->user_messages_template();
						break;
					case 'update_user_messages_tmpl'	: $SP->update_user_messages_template();
						break;						
				}				
				break;
			case 'site_admin' :	
			
				if ( ! $DSP->allowed_group('can_admin_sites'))
				{
					return $DSP->no_access_message();
				}
				
				// Instantiate the site administration class
		
				require PATH_CP.'cp.sites'.EXT;
				
				$SA = new SitesAdmin;
			
				switch($IN->GBL('P'))
				{
					case 'sites_list'			: $SA->sites_list();
						break;
					case 'new_site'				: $SA->new_site_form();
						break;
					case 'edit_site'			: $SA->edit_site_form();
						break;
					case 'update_site'			: $SA->update_site();
						break;
					case 'delete_site'			: $SA->delete_site();
						break;
					case 'delete_site_confirm'	: $SA->delete_site_confirm();
						break;
					default						: $SA->sites_list();
						break;
				}		
			break;
			case 'blog_admin' :	
			
				if ($category_exception == FALSE AND ! $DSP->allowed_group('can_admin_weblogs') AND $IN->GBL('P', 'GET') != 'save_ping_servers')
				{
					return $DSP->no_access_message();
				}
				
				// Instantiate the publish administration class
		
				require PATH_CP.'cp.publish_ad'.EXT;
				
				$PA = new PublishAdmin;
			
				switch($IN->GBL('P'))
				{
					case 'blog_list'			: $PA->weblog_overview();
						break;
					case 'new_weblog'			: $PA->new_weblog_form();
						break;
					case 'blog_prefs'			: $PA->edit_blog_form();
						break;
					case 'group_prefs'			: $PA->edit_group_form();
						break;
					case 'create_blog'			: $PA->update_weblog_prefs();
						break;
					case 'update_preferences'	: $PA->update_weblog_prefs();
						break;
					case 'delete_conf'			: $PA->delete_weblog_conf();
						break;
					case 'delete'				: $PA->delete_weblog();
						break;
					case 'categories'			: $PA->category_overview();
						break;
					case 'cat_group_editor'		: $PA->edit_category_group_form();
						break;
					case 'update_cat_group'		: $PA->update_category_group();
						break;
					case 'cat_group_del_conf'	: $PA->delete_category_group_conf();
						break;
					case 'delete_group'			: $PA->delete_category_group();
						break;
					case 'category_editor'		: $PA->category_manager();
						break;
					case 'update_category'		: $PA->update_category();
						break;
					case 'edit_category'		: $PA->edit_category_form();
						break;
					case 'cat_order'			: $PA->change_category_order();
						break;
					case 'global_cat_order'		: $PA->global_category_order();
						break;
					case 'del_category_conf'	: $PA->delete_category_confirm();
						break;
					case 'del_category'			: $PA->delete_category();
						break;
					case 'cat_field_group_edit'	: $PA->category_field_group_manager();
						break;
					case 'del_cat_field_conf'	: $PA->delete_category_field_confirm();
						break;
					case 'del_cat_field'		: $PA->delete_category_field();
						break;
					case 'edit_cat_field'		: $PA->edit_category_field_form();
						break;
					case 'edit_cat_field_order'	: $PA->edit_category_field_order_form();
						break;
					case 'ud_cat_field_order'	: $PA->update_category_field_order();
						break;
					case 'update_cat_fields'	: $PA->update_category_fields();
						break;
					case 'statuses'				: $PA->status_overview();
						break;
					case 'status_group_editor'	: $PA->edit_status_group_form();
						break;
					case 'update_status_group'	: $PA->update_status_group();
						break;
					case 'status_group_del_conf': $PA->delete_status_group_conf();
						break;
					case 'delete_status_group'	: $PA->delete_status_group();
						break;
					case 'status_editor'		: $PA->status_manager();
						break;
					case 'update_status'		: $PA->update_status();
						break;
					case 'edit_status'			: $PA->edit_status_form();
						break;
					case 'del_status_conf'		: $PA->delete_status_confirm();
						break;
					case 'del_status'			: $PA->delete_status();
						break;
					case 'edit_status_order'	: $PA->edit_status_order();
						break;
					case 'update_status_order'	: $PA->update_status_order();
						break;
					case 'custom_fields'		: $PA->field_overview();
						break;
					case 'update_field_group'	: $PA->update_field_group();
						break;
					case 'del_field_group_conf'	: $PA->delete_field_group_conf();
						break;
					case 'delete_field_group'	: $PA->delete_field_group();
						break;
					case 'field_editor'			: $PA->field_manager();
						break;
					case 'edit_field'			: $PA->edit_field_form();
						break;
					case 'update_weblog_fields'	: $PA->update_weblog_fields();
						break;
					case 'field_group_editor'	: $PA->edit_field_group_form();
						break;
					case 'del_field_conf'		: $PA->delete_field_conf();
						break;
					case 'delete_field'			: $PA->delete_field();
						break;
					case 'edit_field_order'		: $PA->edit_field_order_form();
						break;
					case 'update_field_order'	: $PA->update_field_order();
						break;
					case 'edit_fmt_buttons'		: $PA->edit_formatting_buttons();
						break;
					case 'update_fmt_buttons'	: $PA->update_formatting_buttons();
						break;
					case 'html_buttons'			: $PA->html_buttons();
						break;
					case 'save_html_buttons'	: $PA->save_html_buttons();
						break;
					case 'ping_servers'			: $PA->ping_servers();
						break;
					case 'save_ping_servers'	: $PA->save_ping_servers();
						break;
					case 'upload_prefs'			: $PA->file_upload_preferences();
						break;
					case 'edit_upload_pref'		: $PA->edit_upload_preferences_form();
						break;
					case 'update_upload_prefs'	: $PA->update_upload_preferences();
						break;
					case 'del_upload_pref_conf'	: $PA->delete_upload_preferences_conf();
						break;
					case 'del_upload_pref'		: $PA->delete_upload_preferences();
						break;
					default						: return FALSE;
						break;
					}
										
				break;
			case 'utilities' :	
			
				if ( ! $DSP->allowed_group('can_admin_utilities'))
				{
					return $DSP->no_access_message();
				}
				
				// We handle the pMachine import via a different class,
				// so we'll test for that separately
				
				if ($IN->GBL('P') == 'pm_import')
				{
					require PATH_CP.'cp.pm_import'.EXT;
					$PMI = new PM_Import();
					return;
				}


				if ($IN->GBL('P') == 'mt_import')
				{
					require PATH_CP.'cp.mt_import'.EXT;
					$MT= new MT_Import();
					return;
				}
				
				if ($IN->GBL('P') == 'member_import')
				{
					require PATH_CP.'cp.member_import'.EXT;
					$MI = new Member_Import();
					return;
				}
								
				require PATH_CP.'cp.utilities'.EXT;
			
				switch($IN->GBL('P'))
				{
					case 'view_logs'			: $LOG->view_logs();
						break;	
					case 'clear_cplogs'		 	: $LOG->clear_cp_logs();
						break;
					case 'view_search_log'		: $LOG->view_search_log();
						break;	
					case 'view_throttle_log'	: $LOG->view_throttle_log();
						break;	
					case 'blacklist_ips'		: $LOG->blacklist_ips();
						break;	
					case 'clear_search_log'		: $LOG->clear_search_log();
						break;
					case 'clear_cache_form'		: Utilities::clear_cache_form();
						break;		 
					case 'clear_caching'		: Utilities::clear_caching();
						break;		 
					case 'run_query'			: Utilities::sql_manager('run_query');
						break;
					case 'sql_query'			: Utilities::sql_query_form();
						break;
					//case 'sql_backup'			: Utilities::sql_backup();
					//	break;
					//case 'do_sql_backup'		: Utilities::do_sql_backup();
					//	break;
					case 'view_database'		: Utilities::view_database();
						break;
					case 'table_action'			: Utilities::run_table_action();
						break;
					case 'sandr'				: Utilities::search_and_replace_form();
						break;		
					case 'recount_stats'		: Utilities::recount_statistics();
						break;
					case 'recount_prefs'		: Utilities::recount_preferences_form();
						break;
					case 'set_recount_prefs'	: Utilities::set_recount_prefs();
						break;
					case 'do_recount'			: Utilities::do_recount();
						break;
					case 'do_stats_recount'		: Utilities::do_stats_recount();
						break;
					 case 'prune'				: Utilities::data_pruning();
						break;
					 case 'member_pruning'		: Utilities::member_pruning();
						break;
					 case 'prune_member_conf'	: Utilities::prune_member_confirm();
						break;
					 case 'prune_members'		: Utilities::prune_members();
						break;
					 case 'entry_pruning'		: Utilities::entry_pruning();
						break;
					 case 'prune_entry_conf'	: Utilities::prune_entry_confirm();
						break;
					 case 'prune_entries'		: Utilities::prune_entries();
						break;
					 case 'comment_pruning'		: Utilities::comment_pruning();
						break;
					 case 'prune_comment_conf'	: Utilities::prune_comment_confirmation();
						break;
					 case 'prune_comments'		: Utilities::prune_comments();
						break;
					 case 'trackback_pruning'	: Utilities::trackback_pruning();
						break;
					 case 'prune_trackback_conf': Utilities::prune_trackback_confirmation();
						break;
					 case 'prune_trackbacks'	: Utilities::prune_trackbacks();
						break;
					 /* Someday, oh someday...
					 case 'pm_pruning'			: Utilities::pm_pruning();
						break;
					 case 'prune_pm_conf'		: Utilities::prune_pm_confirmation();
						break;
					 case 'prune_pms'			: Utilities::prune_pms();
						break;
					 */
					 case 'topic_pruning'		: Utilities::topic_pruning();
						break;
					 case 'prune_topic_conf'	: Utilities::prune_topic_confirmation();
						break;
					 case 'prune_topics'		: Utilities::prune_topics();
						break;
					case 'run_sandr'			: Utilities::search_and_replace();
						break;
					case 'php_info'			 	: Utilities::php_info();
						break;	
					case 'sql_manager'			: Utilities::sql_info();
						break;
					case 'sql_status'			: Utilities::sql_manager('status');
						break;
					case 'sql_sysvars'			: Utilities::sql_manager('sysvars');
						break;
					case 'sql_plist'			: Utilities::sql_manager('plist');
						break;
					case 'plugin_manager'		: Utilities::plugin_manager();
						break;
					case 'plugin_info'			: Utilities::plugin_info();
						break;
					case 'plugin_remove_conf'	: Utilities::plugin_remove_confirm();
						break;
					case 'plugin_remove'		: Utilities::plugin_remove();
						break;
					case 'plugin_install'		: Utilities::plugin_install('file');
						break;
					case 'import_utilities'		: Utilities::import_utilities();
						break;
					case 'trans_menu'			: Utilities::translate_select();
						break;
					case 'translate'			: Utilities::translate();
						break;
					case 'save_translation'	 	: Utilities::save_translation();
						break;
					case 'extensions_manager'		: Utilities::extensions_manager();
						break;
					case 'toggle_extension_confirm'	: Utilities::toggle_extension_confirm();
						break;	
					case 'toggle_extension'			: Utilities::toggle_extension();
						break;
					case 'extension_settings'		: Utilities::extension_settings();
						break;
					case 'save_extension_settings'	: Utilities::save_extension_settings();
						break;
					default					 	: return FALSE;
						break;
					}
										
				break;
			default	: $this->admin_home_page();
				break;
		}	
	}
	/* END */
	
	
	/** -----------------------------
	/**  Main admin page
	/** -----------------------------*/
	
	function admin_home_page()
	{	
		global $DSP, $DB, $FNS, $SESS, $LANG, $PREFS, $REGX, $EXT, $IN;
	
		if ( ! $DSP->allowed_group('can_access_admin'))
		{
			return $DSP->no_access_message();
		}
		
		// -------------------------------------------
        // 'admin_home_page_start' hook.
        //  - Allows complete rewrite of Admin home page.
        //
        	$edata = $EXT->call_extension('admin_home_page_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
				
		$DSP->title = $LANG->line('system_admin');						 
		$DSP->crumb = $LANG->line('system_admin');	
		$DSP->crumbline = TRUE;
		
		if (isset($_POST['keywords']) && $_POST['keywords'] != '')
		{
			$DSP->body_props .= ' onload="showHideMenu(\'search_results\');"';
		}
		else
		{
			if ( $IN->GBL('area') !== FALSE && 
				in_array($IN->GBL('area'), array('weblog_administration', 'members_and_groups', 'specialty_templates', 'system_preferences', 'utilities')))
			{
				$DSP->body_props .= ' onload="showHideMenu(\''.$IN->GBL('area').'\');"';
			}
			else
			{
				$DSP->body_props .= ' onload="showHideMenu(\'system_admin\');"';
			}
		}
		
        ob_start();
        ?>     
<script type="text/javascript"> 
<!--
	
function showHideMenu(objValue)
{
	document.getElementById('menu_contents').innerHTML = document.getElementById(objValue).innerHTML;
}

//-->
</script> 
        <?php
        
        $buffer = ob_get_contents();
        ob_end_clean();         
        $DSP->body = $buffer;
        

		$menu = array( 
						'weblog_administration'	=> array(
															'weblog_management'		=>	array(AMP.'M=blog_admin'.AMP.'P=blog_list', 'weblog weblogs trackbacks comments posting pinging captcha captchas'),
															'categories'			=>	array(AMP.'M=blog_admin'.AMP.'P=categories', 'category categories'),
															'field_management'	 	=>	array(AMP.'M=blog_admin'.AMP.'P=blog_list'.AMP.'P=custom_fields', 'custom fields relational date textarea formatting'),
															'status_management'		=>	array(AMP.'M=blog_admin'.AMP.'P=statuses', 'status statuses open close highlight color'),
															'file_upload_prefs'		=>	array(AMP.'M=blog_admin'.AMP.'P=upload_prefs', 'upload uploading paths images files directory'),
															'space_1'				=> '-',
															'default_ping_servers' 	=>	array(AMP.'M=blog_admin'.AMP.'P=ping_servers', 'ping pinging weblogs.com technorati ping-o-matic xmlrpc XML-RPC'),
															'default_html_buttons' 	=>	array(AMP.'M=blog_admin'.AMP.'P=html_buttons', 'HTML buttons formatting tags publish'),
															
															'space_2'				=> '-',
															
															'weblog_cfg'			=>	array(AMP.'M=config_mgr'.AMP.'P=weblog_cfg', 'category URL dynamic caching caches pMPro high ascii Pro image resizing')
														 ),
						'members_and_groups' 	=> array(
															'register_member'		=> array(AMP.'M=members'.AMP.'P=member_reg_form', 'register new member'),
															'member_validation'		=> array(AMP.'M=members'.AMP.'P=member_validation', 'activate pending members'),
															'view_members'			=> array(AMP.'M=members'.AMP.'P=view_members', 'view members memberlist email url join date'),
															
															'space_1'				=> '-',
															
															'member_search'		 	=> array(AMP.'M=members'.AMP.'P=member_search', 'search members'),
															'ip_search'		 		=> array(AMP.'M=members'.AMP.'P=ip_search', 'ip address search comments trackbacks entries'),
															
															'space_2'				=> '-',
															
															'member_groups'		 	=> array(AMP.'M=members'.AMP.'P=mbr_group_manager', 'member groups super admin admins superadmin pending guests banned'),
															'custom_profile_fields' => array(AMP.'M=members'.AMP.'P=profile_fields', 'custom member profile fields '),
															'profile_templates'		=> array(AMP.'M=members'.AMP.'P=profile_templates', 'member profile templates login form email console private message messaging public display'),
															'member_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=member_cfg', 'membership members member signature private message messages messaging avatar avatars photos photo registration activation captcha'),

															'space_3'				=> '-',
															
															'user_banning'			=> array(AMP.'M=members'.AMP.'P=member_banning', 'ban banning users banned'),
															'view_email_logs'		=> array(AMP.'M=members'.AMP.'P=email_console_logs', 'email console logs message messages')
													 	),
						'specialty_templates'	=> array(
															'email_notification_template'	=> array(AMP.'M=sp_templ'.AMP.'P=mbr_notification_tmpl', 'email notification template templates registration activiation instructions'),
															'user_messages_template' 		=> array(AMP.'M=sp_templ'.AMP.'P=user_messages_tmpl', 'user message error template'),															
															'offline_template'				=> array(AMP.'M=sp_templ'.AMP.'P=offline_tmpl', 'system offline turned off template')														
													 	),
													 	
						'system_preferences'	=>	array(
															'general_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=general_cfg', 'system offline name index site new version auto check rename weblog section urls'),
															'cp_cfg'				=> array(AMP.'M=config_mgr'.AMP.'P=cp_cfg', 'control panel display language encoding character publish tab'),
															'security_cfg'	 		=> array(AMP.'M=config_mgr'.AMP.'P=security_cfg', 'security session sessions cookie deny duplicate require agent ip username password length'),
															'output_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=output_cfg', 'output debugging error message force query string HTTP headers redirect redirection'),
															'localization_cfg' 		=> array(AMP.'M=config_mgr'.AMP.'P=localization_cfg', 'dst localize localization daylight savings time zone'),
															
															'space_1'				=> '-',
															
															'db_cfg'	 			=> array(AMP.'M=config_mgr'.AMP.'P=db_cfg', 'database setting settings persistent query cache caching'),
															'email_cfg'				=> array(AMP.'M=config_mgr'.AMP.'P=email_cfg', 'email SMTP sendmail PHP Mail batch webmaster tell-a-friend contact form captcha'),
															'mailinglist_cfg'		=> array(AMP.'M=config_mgr'.AMP.'P=mailinglist_cfg', 'mailing list notify notification'),
															
															'space_2'				=> '-',
															
															'image_cfg'	 			=> array(AMP.'M=config_mgr'.AMP.'P=image_cfg', 'image resize resizing thumbnail thumbnails GD netPBM imagemagick magick'),
															'captcha_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=captcha_cfg', 'captcha member truetype'),
															'tracking_cfg'	 		=> array(AMP.'M=config_mgr'.AMP.'P=tracking_cfg', 'referrer referrers tracking stats hit hits'),
															'cookie_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=cookie_cfg', 'cookie cookies prefix domain site'),
															
															'space_3'				=> '-',
															
															'search_log_cfg'		=> array(AMP.'M=config_mgr'.AMP.'P=search_log_cfg', 'search term log logging '),
															'throttling_cfg'		=> array(AMP.'M=config_mgr'.AMP.'P=throttling_cfg', 'throttling config spam spammers bot bots lockout'),
															'censoring_cfg'			=> array(AMP.'M=config_mgr'.AMP.'P=censoring_cfg', 'censor censoring censored'),
															'emoticon_cfg'	 		=> array(AMP.'M=config_mgr'.AMP.'P=emoticon_cfg', 'emoticon emoticons smiley smileys ')
														),
		
													 
						'utilities'				=> array(
															'view_log_files'		=>	array(AMP.'M=utilities'.AMP.'P=view_logs', 'view CP control panel logs '),
															'view_search_log'		=>	array(AMP.'M=utilities'.AMP.'P=view_search_log', 'search term terms'),
															'view_throttle_log'		=>	array(AMP.'M=utilities'.AMP.'P=view_throttle_log', 'throttle throttling log'),
															'space_1'				=> '-',
															//'sql_backup'			=>	array(AMP.'M=utilities'.AMP.'P=sql_backup', 'database backup restore'),
															'sql_manager'			=>	array(AMP.'M=utilities'.AMP.'P=sql_manager', 'MySQL query queries database'),
															//'space_2'				=> '-',
															'plugin_manager'		=>	array(AMP.'M=utilities'.AMP.'P=plugin_manager', 'plugin plugins manager extending install'),
															'extensions_manager'	=>	array(AMP.'M=utilities'.AMP.'P=extensions_manager', 'extension extensions absolute power'),
															'space_3'				=> '-',
															'clear_caching'		 	=>	array(AMP.'M=utilities'.AMP.'P=clear_cache_form', 'clear empty cache caches'),
														 	'data_pruning'			=>	array(AMP.'M=utilities'.AMP.'P=prune', 'prune remove delete member user comment entry trackback membership'),
															'search_and_replace'	=>	array(AMP.'M=utilities'.AMP.'P=sandr', 'find replace search'),
															'recount_stats'		 	=>	array(AMP.'M=utilities'.AMP.'P=recount_stats', 'stats statistics recount redo'),
															'php_info'				=>	array(AMP.'M=utilities'.AMP.'P=php_info', 'php info information settings paths'),
															'space_4'				=> '-',
															'translation_tool'		=>	array(AMP.'M=utilities'.AMP.'P=trans_menu', 'translate translation langugage foreign'),
															'import_utilities'		=>	array(AMP.'M=utilities'.AMP.'P=import_utilities', 'Movable Type pMachine Pro utility import systems')															
													 	)
						);
						
		if (file_exists(PATH_CP.'cp.sites_admin'.EXT) && $PREFS->ini('multiple_sites_enabled') == 'y')
		{
			$LANG->fetch_language_file('sites_admin');
			
			$site_menu['sites_administration']	= array('site_management'		=>	array(AMP.'M=site_admin'.AMP.'P=sites_list', 'site sites set sets administration absolute power'),
														'set_management'		=>	array(AMP.'M=site_admin'.AMP.'P=sets_list', 'set sets weblogs mailinglists members templates'),
													   );
													   
			$menu = array_merge($site_menu, $menu);
		}

		$DSP->body .= $DSP->table('', '0', '', '100%');
		
		// List of our various preference areas begins here
		
		$first_text = 	$DSP->div('tableHeadingAlt')
						.	$DSP->anchor("#", 
										 $LANG->line('system_admin'), 
										 'onclick="showHideMenu(\'system_admin\');return false;"')
						.$DSP->div_c()
						.$DSP->div('profileMenuInner');
						
						
		// ----------------------------------------
		//  The Third Area
		//  Contains our hidden divs full of information and links
		// ----------------------------------------
		
		$content = NL.'<ul>'.NL;
		
		foreach($menu as $key => $value)
		{
			$content .= '<li>'.
						$DSP->anchor("#", $LANG->line($key), 'onclick="showHideMenu(\''.$key.'\');return false;"').
						'</li>'.NL;
		}
		
		$content .= '</ul>'.NL;
	
		$third_text = $DSP->qdiv('default', '', 'menu_contents').
					  "<div id='system_admin' style='display:none; padding:0px;'>"
							.	$DSP->qdiv( 'roundBox',
											"<b class='roundBoxTop'><b class='roundBox1'></b><b class='roundBox2'></b><b class='roundBox3'></b><b class='roundBox4'></b></b>".
											"<div style='padding: 0 0 0 10px'>".
											$DSP->heading($LANG->line('system_admin'), 2).
											$LANG->line('system_admin_blurb').
											$content.
											$DSP->div_c().
											"<b class='roundBoxBottom'><b class='roundBox4'></b><b class='roundBox3'></b><b class='roundBox2'></b><b class='roundBox1'></b></b>")
							.$DSP->div_c();
							
		if (isset($_POST['keywords']) && $_POST['keywords'] != '')
		{
			if (strlen($_POST['keywords']) > 42 && strtolower($_POST['keywords']) == base64_decode('dGhlIGFuc3dlciB0byBsaWZlLCB0aGUgdW5pdmVyc2UsIGFuZCBldmVyeXRoaW5n'))
			{
				return $DSP->error_message($DSP->qdiv('itemWrapper', "forty-two"));
			}
			
			$search_terms = preg_split("/\s+/", strtolower($REGX->keyword_clean($_POST['keywords'])));
			$search_results = '';
		}
		
		$i = 0;
		
		foreach ($menu as $key => $val)
		{
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			if ($this->admin_nav == 'click')
			{
				$first_text .= $DSP->qdiv('navPad', $DSP->anchor("#", $LANG->line($key), 'onclick="showHideMenu(\''.$key.'\');return false;"'));
			}
			else
			{
				$first_text .= $DSP->qdiv('navPad', $DSP->anchor("#", $LANG->line($key), 'onmouseover="showHideMenu(\''.$key.'\');"'));
			}
			
			$content = NL.'<ul>'.NL;
			
			foreach($val as $k => $v)
			{
				if (substr($k, 0, 6) == 'space_')
				{
					$content .= '</ul>'.NL.NL.'<ul>'.NL;
					continue;
				}
			
				$content .= '<li>'.$DSP->anchor(BASE.AMP.'C=admin'.$v['0'], $LANG->line($k)).'</li>'.NL;
				
				if (isset($search_terms))
				{
					if (sizeof(array_intersect($search_terms, explode(' ', strtolower($v['1'])))) > 0)
					{
						$search_results .= '<li>'.$LANG->line($key).' -> '.$DSP->anchor(BASE.AMP.'C=admin'.$v['0'], $LANG->line($k)).'</li>';
					}
				}
			}
			
			$content .= '</ul>'.NL;
			
			$third_text .=  "<div id='".$key."' style='display:none; padding:0px;'>"
							.	$DSP->qdiv( 'roundBox',
											"<b class='roundBoxTop'><b class='roundBox1'></b><b class='roundBox2'></b><b class='roundBox3'></b><b class='roundBox4'></b></b>".
											"<div style='padding: 0 0 0 10px'>".
											$DSP->heading($LANG->line($key), 2).
											$LANG->line($key.'_blurb').
											$content.
											$DSP->div_c().
											"<b class='roundBoxBottom'><b class='roundBox4'></b><b class='roundBox3'></b><b class='roundBox2'></b><b class='roundBox1'></b></b>")
							.$DSP->div_c();
		}
		
		if (isset($search_terms))
		{
			if (strlen($search_results) > 0)
			{
				$search_results = NL.'<ul>'.NL.$search_results.NL.'</ul>';
			}
			else
			{
				$search_results = $LANG->line('no_search_results');
			}
			
			$third_text .=  "<div id='search_results' style='display:none; padding:0px;'>"
							.	$DSP->qdiv( 'roundBox',
											"<b class='roundBoxTop'><b class='roundBox1'></b><b class='roundBox2'></b><b class='roundBox3'></b><b class='roundBox4'></b></b>".
											"<div style='padding: 0 0 0 10px'>".
											$DSP->heading($LANG->line('search_results'), 2).
											$search_results.
											$DSP->div_c().
											"<b class='roundBoxBottom'><b class='roundBox4'></b><b class='roundBox3'></b><b class='roundBox2'></b><b class='roundBox1'></b></b>")
							.	$DSP->div_c();
		}
		
		$first_text .= $DSP->div_c().BR;
	
		// Add in the Search Form 
		$first_text .=  $DSP->qdiv('tableHeadingAlt', $LANG->line('search_preferences'))
						.$DSP->div('profileMenuInner')
						.	$DSP->form_open(array('action' => 'C=admin'))
						.		$DSP->input_text('keywords', '', '20', '120', 'input', '98%')
						.		$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultRight', $DSP->input_submit($LANG->line('search'))))
						.	$DSP->form_close()
						.$DSP->div_c();
						
		// Create the Table				
		$table_row = array( 'first' 	=> array('valign' => "top", 'width' => "220px", 'text' => $first_text),
							'second'	=> array('class' => "default", 'width'  => "8px"),
							'third'		=> array('valign' => "top", 'text' => $third_text));
		
		$DSP->body .= $DSP->table_row($table_row).
					  $DSP->table_c();
		
	}
	/* END */


	/** -----------------------------
	/**  Configuratin Menu data
	/** -----------------------------*/

	function config_data()
	{
		return array(
		
			'general_cfg'		=>	array(
											'multiple_sites_enabled'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'is_system_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'is_site_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),											
											'license_number'			=> '',
											'site_name'					=> '',
											'site_index'				=> '',
											'site_url'					=> '',
											'cp_url'					=> '',
											'theme_folder_url'			=> '',
											'theme_folder_path'			=> '',
											'deft_lang'					=> array('f', 'language_menu'),
											'charset'					=> array('f', 'fetch_encoding'),											
											'xml_lang'					=> array('f', 'fetch_encoding'),
											'max_caches'				=> '',
											'remap_pm_urls'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'remap_pm_dest'				=> '',
											'new_version_check'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'weblog_nomenclature'		=> '',
											'doc_url'					=> '',
											),
											
			'cp_cfg'		=>	array(
											'cp_theme'					=> array('f', 'theme_menu'),
											'sites_tab_behavior'		=> array('s', array('click' => 'click', 'hover' => 'hover', 'none' => "none")),
											'publish_tab_behavior'		=> array('s', array('click' => 'click', 'hover' => 'hover', 'none' => "none"))
											),
											
			'db_cfg'		=>	array(
											'db_conntype'				=> array('s', array('1' => 'persistent', '0' => 'non_persistent')),
											'enable_db_caching'			=> array('r', array('y' => 'yes', 'n' => 'no'))
											),
											
			'output_cfg'		=>	array(
											'send_headers'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'gzip_output'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'force_query_string'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'redirect_method'			=> array('s', array('redirect' => 'location_method', 'refresh' => 'refresh_method')),
											'debug'						=> array('s', array('0' => 'debug_zero', '1' => 'debug_one', '2' => 'debug_two')),
											'show_queries'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'template_debugging'		=> array('r', array('y' => 'yes', 'n' => 'no'))
											),
											
			'weblog_cfg'		=>	array(
											'use_category_name'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'reserved_category_word'	=> '',
											'auto_convert_high_ascii'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'auto_assign_cat_parents'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'new_posts_clear_caches'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'enable_sql_caching'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'word_separator'			=> array('s', array('dash' => 'dash', 'underscore' => 'underscore')),
											'enable_image_resizing' 	=> array('r', array('y' => 'yes', 'n' => 'no')),
											),

			'image_cfg'		=>	array(
											'image_resize_protocol'		=> array('s', array('gd' => 'gd', 'gd2' => 'gd2', 'imagemagick' => 'imagemagick', 'netpbm' => 'netpbm')),
											'image_library_path'		=> '',
											'thumbnail_prefix'			=> ''
											),
			'security_cfg'		=>	array(												
											'admin_session_type'		=> array('s', array('cs' => 'cs_session', 'c' => 'c_session', 's' => 's_session')),
											'user_session_type'	 		=> array('s', array('cs' => 'cs_session', 'c' => 'c_session', 's' => 's_session')),
											'secure_forms'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'deny_duplicate_data'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'redirect_submitted_links'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_username_change' 	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_multi_emails'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_multi_logins'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'require_ip_for_login'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'require_ip_for_posting'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'xss_clean_uploads'			=> array('r', array('y' => 'yes', 'n' => 'no')),											
											'password_lockout'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'password_lockout_interval'	=> '',
											'require_secure_passwords'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_dictionary_pw'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'name_of_dictionary_file'	=> '',
											'un_min_len'				=> '',
											'pw_min_len'				=> ''
											),
											
			'throttling_cfg'		=>	array(	
											'enable_throttling'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'banish_masked_ips'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'max_page_loads'			=> '',
											'time_interval'				=> '',
											'lockout_time'				=> '',
											'banishment_type'			=> array('s', array('404' => '404_page', 'redirect' => 'url_redirect', 'message' => 'show_message')),
											'banishment_url'			=> '',									
											'banishment_message'		=> ''											
										),
			'localization_cfg'	=>	array(	 
											'server_timezone'			=> array('f', 'timezone'),
											'server_offset'				=> '',
											'time_format'				=> array('s', array('us' => 'united_states', 'eu' => 'european')),
											'daylight_savings'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'honor_entry_dst'			=> array('r', array('y' => 'yes', 'n' => 'no'))											
										  ),

			'email_cfg'			=>	array(
											'webmaster_email'			=> '',
											'webmaster_name'			=> '',
											'email_charset'				=> '',
											'email_debug'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'mail_protocol'				=> array('s', array('mail' => 'php_mail', 'sendmail' => 'sendmail', 'smtp' => 'smtp')),
											'smtp_server'				=> '',
											'smtp_username'				=> '',
											'smtp_password'				=> '',
											'email_batchmode'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'email_batch_size'			=> '',
											'mail_format'				=> array('s', array('plain' => 'plain_text', 'html' => 'html')),
											'word_wrap'					=> array('r', array('y' => 'yes', 'n' => 'no')),
											'email_console_timelock'	=> '',
											'log_email_console_msgs'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'email_module_captchas'		=> array('r', array('y' => 'yes', 'n' => 'no'))
										 ),

			'cookie_cfg' 		=>	array(												
											'cookie_domain'				=> '',
											'cookie_path'				=> '',
											'cookie_prefix'				=> ''
										 ),
										 										 
			'captcha_cfg' 		=>	array(												
											'captcha_path'				=> '',
											'captcha_url'				=> '',
											'captcha_font' 				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'captcha_rand' 				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'captcha_require_members' 	=> array('r', array('y' => 'yes', 'n' => 'no'))
										 ),

			'search_log_cfg' 		=>	array(												
											'enable_search_log' 		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'max_logged_searches'		=> ''
										 ),

			'template_cfg' 		=>	array(
											'strict_urls' 				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'site_404'					=> array('f', 'site_404'),
											'save_tmpl_revisions' 		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'max_tmpl_revisions'		=> '',
											'save_tmpl_files' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'tmpl_file_basepath'		=> ''
										 ),
									
			'censoring_cfg' 	=>	array(												
											'enable_censoring' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'censor_replacement'		=> '',
											'censored_words'			=> array('t', array('rows' => '20', 'kill_pipes' => TRUE)),
										 ),
									
			'mailinglist_cfg' 		=>	array(												
											'mailinglist_enabled' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'mailinglist_notify' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'mailinglist_notify_emails'		=> ''
										 ),
			'emoticon_cfg' 		=>	array(												
											'enable_emoticons' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'emoticon_path'				=> ''
										 ),
									
			'tracking_cfg' 		=>	array(
											'enable_online_user_tracking'	=> array('r', array('y' => 'yes', 'n' => 'no'), 'y'),
											'enable_hit_tracking'			=> array('r', array('y' => 'yes', 'n' => 'no'), 'y'),
											'enable_entry_view_tracking'	=> array('r', array('y' => 'yes', 'n' => 'no'), 'y'),
											'log_referrers' 				=> array('r', array('y' => 'yes', 'n' => 'no')),
											'max_referrers'					=> '',
											'dynamic_tracking_disabling'	=> '',
										 )
						);						
	}
	/* END */


	/** -----------------------------
	/**  Configuration sub-text
	/** -----------------------------*/

	// Secondary lines of text used in configuration pages
	// This text appears below any given preference defenition	
	
	function subtext()
	{			
		return array(	
						'site_url'					=> array('url_explanation'),
						'is_site_on'		    	=> array('is_site_on_explanation'),
						'is_system_on'		    	=> array('is_system_on_explanation'),
						'debug'						=> array('debug_explanation'),
						'show_queries'				=> array('show_queries_explanation'),
						'template_debugging'		=> array('template_debugging_explanation'),
						'max_caches'				=> array('max_caches_explanation'),
						'gzip_output'				=> array('gzip_output_explanation'),
						'server_offset'				=> array('server_offset_explain'),
						'default_member_group' 		=> array('group_assignment_defaults_to_two'),
						'smtp_server'				=> array('only_if_smpte_chosen'),
						'smtp_username'				=> array('only_if_smpte_chosen'),
						'smtp_password'				=> array('only_if_smpte_chosen'),
						'email_batchmode'			=> array('batchmode_explanation'),
						'email_batch_size'			=> array('batch_size_explanation'),
						'webmaster_email'			=> array('return_email_explanation'),
						'cookie_domain'				=> array('cookie_domain_explanation'),
						'cookie_prefix'				=> array('cookie_prefix_explain'),
						'cookie_path'				=> array('cookie_path_explain'),
						'secure_forms'				=> array('secure_forms_explanation'),
						'deny_duplicate_data'		=> array('deny_duplicate_data_explanation'),
						'redirect_submitted_links'	=> array('redirect_submitted_links_explanation'),
						'require_secure_passwords'	=> array('secure_passwords_explanation'),
						'allow_dictionary_pw'		=> array('real_word_explanation', 'dictionary_note'),
						'censored_words'			=> array('censored_explanation', 'censored_wildcards'),
						'censor_replacement'		=> array('censor_replacement_info'),
						'password_lockout'			=> array('password_lockout_explanation'),
						'password_lockout_interval'	=> array('login_interval_explanation'),
						'require_ip_for_login'		=> array('require_ip_explanation'),
						'allow_multi_logins'		=> array('allow_multi_logins_explanation'),
						'name_of_dictionary_file' 	=> array('dictionary_explanation'),
						'force_query_string'		=> array('force_query_string_explanation'),
						'enable_image_resizing'		=> array('enable_image_resizing_exp'),
						'image_resize_protocol'		=> array('image_resize_protocol_exp'),
						'image_library_path'		=> array('image_library_path_exp'),
						'thumbnail_prefix'			=> array('thumbnail_prefix_exp'),
						'member_theme'				=> array('member_theme_exp'),
						'require_terms_of_service'	=> array('require_terms_of_service_exp'),
						'email_console_timelock'	=> array('email_console_timelock_exp'),
						'log_email_console_msgs'	=> array('log_email_console_msgs_exp'),
						'use_membership_captcha'	=> array('captcha_explanation'),
						'tmpl_display_mode'			=> array('tmpl_display_mode_exp'),
						'save_tmpl_files'			=> array('save_tmpl_files_exp'),
						'tmpl_file_basepath'		=> array('tmpl_file_basepath_exp'),
						'site_404'					=> array('site_404_exp'),
						'weblog_nomenclature'		=> array('weblog_nomenclature_exp'),
						'enable_sql_caching'		=> array('enable_sql_caching_exp'),
						'email_debug'				=> array('email_debug_exp'),
						'use_category_name'			=> array('use_category_name_exp'),
						'reserved_category_word'	=> array('reserved_category_word_exp'),
						'auto_assign_cat_parents'	=> array('auto_assign_cat_parents_exp'),
						'save_tmpl_revisions'		=> array('template_rev_msg'),
						'max_tmpl_revisions'		=> array('max_revisions_exp'),
						'remap_pm_urls'				=> array('remap_pm_urls_desc'),
						'remap_pm_dest'				=> array('remap_pm_dest_exp'),
						'max_page_loads'			=> array('max_page_loads_exp'),
						'time_interval'				=> array('time_interval_exp'),
						'lockout_time'				=> array('lockout_time_exp'),
						'banishment_type'			=> array('banishment_type_exp'),
						'banishment_url'			=> array('banishment_url_exp'),
						'banishment_message'		=> array('banishment_message_exp'),
						'enable_search_log'			=> array('enable_search_log_exp'),
						'mailinglist_notify_emails'	=> array('separate_emails'),
						'strict_urls'				=> array('strict_urls_info'),
						'dynamic_tracking_disabling'=> array('dynamic_tracking_disabling_info')
					);
	}
	/* END */


	/** -----------------------------
	/**  Configuration manager
	/** -----------------------------*/
	
	// This function displays the various Preferences pages
	
	function config_manager($f_data = '', $subtext = '', $return_loc = '')
	{	
		global $IN, $DSP, $FNS, $LOC, $PREFS, $LANG;
		
		
		if ( ! $DSP->allowed_group('can_admin_preferences'))
		{
			return $DSP->no_access_message();
		}
		
		if ( ! $type = $IN->GBL('P'))
		{
			return FALSE;
		}		
		
		if ($f_data == '')
		{
			// No funny business with the URL
			
			if ( ! in_array($type, array(
											'general_cfg', 
											'cp_cfg', 
											'weblog_cfg',
											'member_cfg',
											'output_cfg',
											'debug_cfg',
											'db_cfg',
											'security_cfg',
											'throttling_cfg',
											'localization_cfg',
											'email_cfg',
											'cookie_cfg',
											'image_cfg',
											'captcha_cfg',
											'template_cfg',
											'censoring_cfg',
											'mailinglist_cfg',
											'emoticon_cfg',
											'tracking_cfg',
											'avatar_cfg',
											'search_log_cfg'
											)
							)
			)
			{
				return $FNS->bounce();
			}
				
			$f_data = $this->config_data();
			
			// don't show or edit the CP URL from masked CPs
			if (defined('MASKED_CP') && MASKED_CP === TRUE)
			{
				unset($f_data['general_cfg']['cp_url']);
			}
			
			if ( ! file_exists(PATH_CORE.'core.sites.php'))
			{
				unset($f_data['general_cfg']['multiple_sites_enabled']);	
			}
			
			if ($PREFS->ini('multiple_sites_enabled') == 'y')
			{
				unset($f_data['general_cfg']['site_name']);
			}
			else
			{
				unset($f_data['general_cfg']['is_site_on']);
				unset($f_data['cp_cfg']['sites_tab_behavior']);
			}
		}

		if ($subtext == '')
		{
			$subtext = $this->subtext();
		}


		/** -----------------------------
		/**  Build the output
		/** -----------------------------*/
		
		$DSP->body	 =	'';
		
		if ($IN->GBL('U'))
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('preferences_updated')));
		}
		
		if ($return_loc == '')
			$return_loc = BASE.AMP.'C=admin'.AMP.'M=config_mgr'.AMP.'P='.$type.AMP.'U=1';
			
		$override = ($IN->GBL('class_override', 'GET') != '') ? AMP.'class_override='.$IN->GBL('class_override', 'GET') : '';
								
		$DSP->body	.=	$DSP->form_open(
										array(
												'action' => 'C=admin'.AMP.'M=config_mgr'.AMP.'P=update_cfg'.$override
											),
										array(
												'return_location' => $return_loc
											)
										);
				
		$DSP->body	.=	$DSP->table('tableBorder', '0', '', '100%');
		$DSP->body	.=	$DSP->tr();
		$DSP->body	.=	$DSP->td('tableHeading', '', '2');
		$DSP->body	.=	$LANG->line($type);
		$DSP->body	.=	$DSP->td_c();
		$DSP->body	.=	$DSP->tr_c();
		
		$i = 0;
		
		/** -----------------------------
		/**  Blast through the array
		/** -----------------------------*/
				
		foreach ($f_data[$type] as $key => $val)				
		{
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			$DSP->body	.=	$DSP->tr();
			
			// If the form type is a textarea, we'll align the text at the top, otherwise, we'll center it
			
			if (is_array($val) AND $val['0'] == 't')
			{
				$DSP->body .= $DSP->td($style, '50%', '', '', 'top');
			}
			else
			{
				$DSP->body .= $DSP->td($style, '50%', '');
			}
			
			/** -----------------------------
			/**  Preference heading
			/** -----------------------------*/
			
			$DSP->body .= $DSP->div('defaultBold');
					
			$label = ( ! is_array($val)) ? $key : '';
		
			$DSP->body .= $LANG->line($key, $label);

			$DSP->body .= $DSP->div_c();
			
			
			/** -----------------------------
			/**  Preference sub-heading
			/** -----------------------------*/
			
			if (isset($subtext[$key]))
			{
				foreach ($subtext[$key] as $sub)
				{
					$DSP->body .= $DSP->qdiv('subtext', $LANG->line($sub));
				}
			}
			
			$DSP->body .= $DSP->td_c();
			
			/** -----------------------------
			/**  Preference value
			/** -----------------------------*/
			
			$DSP->body .= $DSP->td($style, '50%', '');
			
				if (is_array($val))
				{
					/** -----------------------------
					/**  Drop-down menus
					/** -----------------------------*/
								
					if ($val['0'] == 's')
					{
						$DSP->body .= $DSP->input_select_header($key);
				
						foreach ($val['1'] as $k => $v)
						{
							$selected = ($k == $PREFS->ini($key)) ? 1 : '';
						
							$DSP->body .= $DSP->input_select_option($k, $LANG->line($v), $selected);
						}
						
						$DSP->body .= $DSP->input_select_footer();
						
					} 
					elseif ($val['0'] == 'r')
					{
						/** -----------------------------
						/**  Radio buttons
						/** -----------------------------*/
					
						foreach ($val['1'] as $k => $v)
						{
							// little cheat for some values popped into a build update
							if ($PREFS->ini($key) === FALSE)
							{
								$selected = (isset($val['2']) && $k == $val['2']) ? 1 : '';
							}
							else
							{
								$selected = ($k == $PREFS->ini($key)) ? 1 : '';	
							}
						
							$DSP->body .= $LANG->line($v).$DSP->nbs();
							$DSP->body .= $DSP->input_radio($key, $k, $selected).$DSP->nbs(3);
						}					
					}
					elseif ($val['0'] == 't')
					{
						/** -----------------------------
						/**  Textarea fields
						/** -----------------------------*/
						
						// The "kill_pipes" index instructs us to 
						// turn pipes into newlines
						
						if (isset($val['1']['kill_pipes']) AND $val['1']['kill_pipes'] === TRUE)
						{
							$text	= '';
							
							foreach (explode('|', $PREFS->ini($key)) as $exp)
							{
								$text .= $exp.NL;
							}
						}
						else
						{
							$text = stripslashes($PREFS->ini($key));
						}
												
						$rows = (isset($val['1']['rows'])) ? $val['1']['rows'] : '20';
						
						$text = str_replace("\\'", "'", $text);
						
						$DSP->body .= $DSP->input_textarea($key, $text, $rows);
						
					}					
					elseif ($val['0'] == 'f')
					{
						/** -----------------------------
						/**  Function calls
						/** -----------------------------*/
					
						switch ($val['1'])
						{
							case 'language_menu'		: 	$DSP->body .= $FNS->language_pack_names($PREFS->ini($key));
								break;
							case 'fetch_encoding'		:	$DSP->body .= $this->fetch_encoding($key);
								break;
							case 'site_404'				:	$DSP->body .= $this->site_404($PREFS->ini($key));
								break;
							case 'theme_menu'			: 	$DSP->body .= $this->fetch_themes($PREFS->ini($key));
								break;
							case 'timezone'				: 	$DSP->body .= $LOC->timezone_menu($PREFS->ini($key));
								break;
						}
					}
				}
				else
				{
					/** -----------------------------
					/**  Text input fields
					/** -----------------------------*/
					
					$item = str_replace("\\'", "'", $PREFS->ini($key));
					
					$DSP->body .= $DSP->input_text($key, $item, '20', '120', 'input', '100%');
				}
				
			$DSP->body .= $DSP->td_c();
			$DSP->body .= $DSP->tr_c();
		}
				
		$DSP->body .= $DSP->table_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')));
				
		$DSP->body .= $DSP->form_close();
				
		$DSP->title  = $LANG->line($type);	
		
		if ($IN->GBL('P') == 'weblog_cfg')
		{
			$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration'));
			$DSP->crumb .= $DSP->crumb_item($LANG->line($type));
		}
		elseif($IN->GBL('P') != 'template_cfg')
		{
			$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=system_preferences', $LANG->line('system_preferences'));
			$DSP->crumb .= $DSP->crumb_item($LANG->line($type));
		}
		else
		{
			$DSP->crumb .= $LANG->line($type);
		}
	}
	/* END */
	
	
	/** -----------------------------------------
	/**  Member Config Page
	/** -----------------------------------------*/

	function member_config_manager()
	{
		global $IN, $DSP, $LANG, $FNS, $PREFS;
		
		$f_data =  array(
		

			'general_cfg'		=>	array(
											'allow_member_registration'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'req_mbr_activation'		=> array('s', array('none' => 'no_activation', 'email' => 'email_activation', 'manual' => 'manual_activation')),
											'require_terms_of_service'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_member_localization'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'use_membership_captcha'	=> array('r', array('y' => 'yes', 'n' => 'no')),											
											'default_member_group'		=> array('f', 'member_groups'),
											'member_theme'				=> array('f', 'member_theme_menu'),
											'profile_trigger'			=> ''
											),

			'memberlist_cfg'		=>	array(
											'memberlist_order_by'		=> array('s', array('total_posts'		=> 'total_posts', 
																							'screen_name'		=> 'screen_name', 
																							'total_comments'	=> 'total_comments',
																							'total_entries'		=> 'total_entries',
																							'join_date'			=> 'join_date')),
											'memberlist_sort_order'		=> array('s', array('desc' => 'memberlist_desc', 'asc' => 'memberlist_asc')),
											'memberlist_row_limit'		=> array('s', array('10' => '10', '20' => '20', '30' => '30', '40' => '40', '50' => '50', '75' => '75', '100' => '100'))
											),
									
			'notification_cfg'		=>	array(
											'new_member_notification'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'mbr_notification_emails'	=> ''
											),
											
			'pm_cfg'			=>	array(
											'prv_msg_max_chars'			=> '',
											'prv_msg_html_format'		=> array('s', array('safe' => 'html_safe', 'none' => 'html_none', 'all' => 'html_all')),
											'prv_msg_auto_links'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'prv_msg_upload_path'		=> '',
											'prv_msg_max_attachments'	=> '',
											'prv_msg_attach_maxsize'	=> '',
											'prv_msg_attach_total'		=> ''
										 ),
											
			'avatar_cfg'		=>	array(
											'enable_avatars'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'allow_avatar_uploads'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											'avatar_url'			=> '',
											'avatar_path'			=> '',
											'avatar_max_width'		=> '',
											'avatar_max_height'		=> '',
											'avatar_max_kb'			=> ''
											),
			'photo_cfg'		=>	array(
											'enable_photos'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'photo_url'				=> '',
											'photo_path'			=> '',
											'photo_max_width'		=> '',
											'photo_max_height'		=> '',
											'photo_max_kb'			=> ''
											),
			'signature_cfg'		=>	array(
											'allow_signatures'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											'sig_maxlength'				=> '',
											'sig_allow_img_hotlink'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'sig_allow_img_upload'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											'sig_img_url'				=> '',
											'sig_img_path'				=> '',
											'sig_img_max_width'			=> '',
											'sig_img_max_height'		=> '',
											'sig_img_max_kb'			=> ''
											)
			);


		$subtext = array(	
						'profile_trigger'			=> array('profile_trigger_notes'),
						'mbr_notification_emails'	=> array('separate_emails'),
						'default_member_group' 		=> array('group_assignment_defaults_to_two'),
						'avatar_path'				=> array('must_be_path'),
						'photo_path'				=> array('must_be_path'),
						'sig_img_path'				=> array('must_be_path'),
						'allow_member_localization'	=> array('allow_member_loc_notes')
					);
		
		
		if ( ! $DSP->allowed_group('can_admin_preferences'))
		{
			return $DSP->no_access_message();
		}
		
		if ( ! $type = $IN->GBL('P'))
		{
			return FALSE;
		}		


		/** -----------------------------
		/**  Build the output
		/** -----------------------------*/
		
        ob_start();
        ?>     
<script type="text/javascript"> 
<!--
	
var lastShownObj = '';
var lastShownColor = '';	
function showHideMenu(objValue)
{
	if (lastShownObj != '')
	{
		document.getElementById(lastShownObj+'_pointer').getElementsByTagName('a')[0].style.color = lastShownColor;
		document.getElementById(lastShownObj).style.display = 'none';
	}
	
	lastShownObj = objValue;
	lastShownColor = document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color;
	
	document.getElementById(objValue).style.display = 'block';
	document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color = '#000';
}

//-->
</script>         
    
        <?php
        
        $buffer = ob_get_contents();
        ob_end_clean();         
        $DSP->body = $buffer;	
        
        $DSP->body_props .= ' onload="showHideMenu(\'general_cfg\');"';
		
		if ($IN->GBL('U'))
		{
			$DSP->body .= $DSP->qdiv('box', $DSP->qspan('success', $LANG->line('preferences_updated')));
		}
		
			
		$override = ($IN->GBL('class_override', 'GET') != '') ? AMP.'class_override='.$IN->GBL('class_override', 'GET') : '';
				
		$r = $DSP->form_open(
								array(
										'action' => 'C=admin'.AMP.'M=config_mgr'.AMP.'P=update_cfg'.$override
									),
								array(
									'return_location' => BASE.AMP.'C=admin'.AMP.'M=config_mgr'.AMP.'P='.$type.AMP.'U=1'
									)
							);
									
		$r .= $DSP->qdiv('default', '', 'menu_contents');
		
		$i = 0;
		
		/** -----------------------------
		/**  Blast through the array
		/** -----------------------------*/
				
		foreach ($f_data as $menu_head => $menu_array)				
		{
			$r .= '<div id="'.$menu_head.'" style="display: none; padding:0; margin: 0;">';
			$r .= $DSP->table('tableBorder', '0', '', '100%');
			$r .= $DSP->tr();
						
			$r .= "<td class='tableHeadingAlt' id='".$menu_head."2' colspan='2'>";
			$r .= NBS.$LANG->line($menu_head).$DSP->td_c();     
			$r .= $DSP->tr_c();
			
		
			foreach ($menu_array as $key => $val)
			{
				$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
				
				$r	.=	$DSP->tr();
				
				// If the form type is a textarea, we'll align the text at the top, otherwise, we'll center it
				
				if (is_array($val) AND $val['0'] == 't')
				{
					$r .= $DSP->td($style, '50%', '', '', 'top');
				}
				else
				{
					$r .= $DSP->td($style, '50%', '');
				}
				
				/** -----------------------------
				/**  Preference heading
				/** -----------------------------*/
				
				$r .= $DSP->div('defaultBold');
						
				$label = ( ! is_array($val)) ? $key : '';
			
				$r .= $LANG->line($key, $label);
	
				$r .= $DSP->div_c();
				
				
				/** -----------------------------
				/**  Preference sub-heading
				/** -----------------------------*/
				
				if (isset($subtext[$key]))
				{
					foreach ($subtext[$key] as $sub)
					{
						$r .= $DSP->qdiv('subtext', $LANG->line($sub));
					}
				}
				
				$r .= $DSP->td_c();				
				$r .= $DSP->td($style, '50%', '');
				
					if (is_array($val))
					{
						/** -----------------------------
						/**  Drop-down menus
						/** -----------------------------*/
									
						if ($val['0'] == 's')
						{
							$r .= $DSP->input_select_header($key);
					
							foreach ($val['1'] as $k => $v)
							{
								$selected = ($k == $PREFS->ini($key)) ? 1 : '';
							
								$r .= $DSP->input_select_option($k, ( ! $LANG->line($v) ? $v : $LANG->line($v)), $selected);
							}
							
							$r .= $DSP->input_select_footer();
							
						} 
						elseif ($val['0'] == 'r')
						{
							/** -----------------------------
							/**  Radio buttons
							/** -----------------------------*/
						
							foreach ($val['1'] as $k => $v)
							{
								$selected = ($k == $PREFS->ini($key)) ? 1 : '';
							
								$r .= $LANG->line($v).$DSP->nbs();
								$r .= $DSP->input_radio($key, $k, $selected).$DSP->nbs(3);
							}					
						}
						elseif ($val['0'] == 'f')
						{
							/** -----------------------------
							/**  Function calls
							/** -----------------------------*/
						
							switch ($val['1'])
							{
								case 'member_groups'		:	$r .= $this->fetch_member_groups();
									break;	
								case 'member_theme_menu'	: 	$r .= $this->theme_list(PATH_MBR_THEMES, $PREFS->ini($key));
									break;	
							}
						}
						
					}
					else
					{
						/** -----------------------------
						/**  Text input fields
						/** -----------------------------*/
						
						$item = str_replace("\\'", "'", $PREFS->ini($key));
						
						$r .= $DSP->input_text($key, $item, '20', '120', 'input', '100%');
					}
					
				$r .= $DSP->td_c();
			}

			$r .= $DSP->tr_c();
			$r .= $DSP->table_close();			
			$r .= $DSP->div_c();
		}
				
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')));
				
		$r .= $DSP->form_close();
		
		
		/** ----------------------------------
        /**  Create Our All Encompassing Table of Member Goodness
        /** ----------------------------------*/
        
        $DSP->body .= $DSP->table('', '0', '', '100%');
					  
		$menu  = '';
	
		foreach ($f_data as $menu_head => $menu_array)
		{
			$menu .= $DSP->qdiv('navPad', ' <span id="'.$menu_head.'_pointer">&#8226; '.$DSP->anchor("#", $LANG->line($menu_head), 'onclick="showHideMenu(\''.$menu_head.'\');return false;"').'</span>');
		}
		
		$first_text = 	$DSP->div('tableHeadingAlt')
						.	$LANG->line($type)
						.$DSP->div_c()
						.$DSP->div('profileMenuInner')
						.	$menu
						.$DSP->div_c();
						
		// Create the Table				
		$table_row = array( 'first' 	=> array('valign' => "top", 'width' => "220px", 'text' => $first_text),
							'second'	=> array('class' => "default", 'width'  => "8px"),
							'third'		=> array('valign' => "top", 'text' => $r));
		
		$DSP->body .= $DSP->table_row($table_row).
					  $DSP->table_c();
				
		$DSP->title = $LANG->line($type);								
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
					  $DSP->crumb_item($LANG->line($type));								
	}
	/* END */

		
	
	/** -----------------------------------------
	/**  Fetch Member groups
	/** -----------------------------------------*/
		
	function fetch_member_groups()
	{
		global $DB, $LANG, $DSP, $PREFS, $SESS;
		
		$LANG->fetch_language_file('members');
		
    	$english = array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins');

		$query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '1' order by group_title");
			  
		$r = $DSP->input_select_header('default_member_group');
								
		foreach ($query->result as $row)
		{
			$group_title = $row['group_title'];
					
			if (in_array($group_title, $english))
			{
				$group_title = $LANG->line(strtolower(str_replace(" ", "_", $group_title)));
			}
			
			$selected = ($row['group_id'] == $PREFS->ini('default_member_group')) ? 1 : '';
			
			$r .= $DSP->input_select_option($row['group_id'], $group_title, $selected);
		}
		
		$r .= $DSP->input_select_footer();
		
		return $r;
	}
	/* END */
	
	
	
	/** -----------------------------------------
	/**  Update general preferences
	/** -----------------------------------------*/
		
	function update_config_prefs()
	{
		global $IN, $DSP, $LANG, $PREFS, $DB, $FNS, $REGX;

		if ( ! $DSP->allowed_group('can_admin_preferences'))
		{
			return $DSP->no_access_message();
		}
		
		$loc = $IN->GBL('return_location');
		
		// We'll format censored words if they happen to cross our path
		
		if (isset($_POST['censored_words']))
		{
			$_POST['censored_words'] = trim($_POST['censored_words']);

			$_POST['censored_words'] = str_replace(NL, '|', $_POST['censored_words']);

			$_POST['censored_words'] = preg_replace("#\s+#", "", $_POST['censored_words']);
		}

		// Category trigger matches template != biscuit  (biscuits, Robin? Okay! --Derek)
		
		if (isset($_POST['reserved_category_word']) AND $_POST['reserved_category_word'] != $PREFS->ini('reserved_category_word'))
		{
			$query = $DB->query("SELECT template_id, template_name, group_name
								FROM exp_templates t
								LEFT JOIN exp_template_groups g ON t.group_id = g.group_id 
								WHERE (template_name = '".$DB->escape_str($_POST['reserved_category_word'])."'
								OR group_name = '".$DB->escape_str($_POST['reserved_category_word'])."')
								AND t.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' LIMIT 1");
			
			if ($query->num_rows > 0)
			{
				$msg  = $DSP->qdiv('itemWrapper', $LANG->line('category_trigger_duplication'));
				$msg .= $DSP->qdiv('highlight', htmlentities($_POST['reserved_category_word']));
				
				return $DSP->error_message($msg);
			}
		}
				
		/** ----------------------------------------
		/**  Do path checks if needed
		/** ----------------------------------------*/

		$paths = array('sig_img_path', 'avatar_path', 'photo_path', 'captcha_path', 'prv_msg_upload_path');
		
		foreach ($paths as $val)
		{
			if (isset($_POST[$val]) AND $_POST[$val] != '')
			{
				if (substr($_POST[$val], -1) != '/' && substr($_POST[$val], -1) != '\\')
				{
					$_POST[$val] .= '/';
				}
				
				$fp = ($val == 'avatar_path') ? $_POST[$val].'uploads/' : $_POST[$val];
			
				if ( ! @is_dir($fp))
				{
					$msg  = $DSP->qdiv('itemWrapper', $LANG->line('invalid_path'));
					$msg .= $DSP->qdiv('highlight', $fp);
				
					return $DSP->error_message($msg);
				}
				
				if ( ! @is_writable($fp))
				{
					$msg  = $DSP->qdiv('itemWrapper', $LANG->line('not_writable_path'));
					$msg .= $DSP->qdiv('highlight', $fp);
				
					return $DSP->error_message($msg);
				}			
			}
		}
		
		unset($_POST['return_location']);
		
		/** ----------------------------------------
		/**  Preferences Stored in Database For Site
		/** ----------------------------------------*/
		
		if ($PREFS->ini('multiple_sites_enabled') !== 'y' && isset($_POST['site_name']))
		{
			$DB->query($DB->update_string('exp_sites', array('site_label' => $_POST['site_name']), "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
			unset($_POST['site_name']);
		}
		
		$query = $DB->query("SELECT * FROM exp_sites WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				
		// Because Pages is a special snowflake
		if ($PREFS->ini('site_pages') !== FALSE)
		{
			if (isset($_POST['site_url']) OR isset($_POST['site_index']))
			{
				$pages	= $REGX->array_stripslashes(unserialize($query->row['site_pages']));
				
				$url = (isset($_POST['site_url'])) ? $_POST['site_url'].'/' : $PREFS->ini('site_url').'/';
				$url .= (isset($_POST['site_index'])) ? $_POST['site_index'].'/' : $PREFS->ini('site_index').'/';
				
				$pages[$PREFS->ini('site_id')]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);

				$DB->query($DB->update_string('exp_sites', 
											  array('site_pages' => addslashes(serialize($pages))),
											  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
			}
		}

		foreach(array('system', 'weblog', 'template', 'mailinglist', 'member') as $type)
		{
			$prefs	 = $REGX->array_stripslashes(
												unserialize(
												$query->row['site_'.$type.'_preferences']));
			$changes = 'n';
			
			foreach($PREFS->divination($type) as $value)
			{
				if (isset($_POST[$value]))
				{
					$changes = 'y';
					
					$prefs[$value] = str_replace('\\\\', '/',  $_POST[$value]);
					unset($_POST[$value]);
				}
			}
			
			if ($changes == 'y')
			{
				$DB->query($DB->update_string('exp_sites', 
											  array('site_'.$type.'_preferences' => addslashes(serialize($prefs))),
											  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
			}
		}
		
		/** ----------------------------------------
		/**  Certain Preferences might remain in config.php
		/** ----------------------------------------*/

		if (sizeof($_POST) > 0)
		{
			foreach ($_POST as $key => $val)
			{
				$_POST[$key] = stripslashes(str_replace('\\\\', '/', $val));		
			}
			
			$this->update_config_file($_POST, $loc);
		}
		elseif ($loc !== FALSE)
		{		
			$override = ($IN->GBL('class_override', 'GET') != '') ? AMP.'class_override='.$IN->GBL('class_override', 'GET') : '';
		
			$FNS->redirect($loc.$override);
			exit;
		}
	}
	/* END */


	/** -----------------------------------------
	/**  Update config file
	/** -----------------------------------------*/
		
	function update_config_file($newdata = '', $return_loc = FALSE, $remove_values = array())
	{
		global $IN, $FNS;

		if ( ! is_array($newdata) && sizeof($remove_values) == 0)
		{
			return FALSE;
		}
				
		require CONFIG_FILE;
		
		/** -----------------------------------------
		/**  Write config backup file
		/** -----------------------------------------*/
				
		$old  = "<?php\n\n";
		$old .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$old .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$old .= '?'.'>';
		
		$bak_path = str_replace(EXT, '', CONFIG_FILE);
		$bak_path .= '_bak'.EXT;
		
		if ($fp = @fopen($bak_path, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $old, strlen($old));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
		
		/** -----------------------------------------
		/**  Add new data values to config file, remove old ones
		/** -----------------------------------------*/
		
		if (is_array($newdata) && sizeof($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				$val = str_replace("\n", " ", $val);
				$conf[$key] = trim($val);	
			}
		}
		
		if (is_array($remove_values) && sizeof($remove_values) > 0)
		{
			foreach ($remove_values as $val)
			{
				unset($conf[$val]);
			}
		}
		
		reset($conf);
		
		/** -----------------------------------------
		/**  Write config file as a string
		/** -----------------------------------------*/
		
		$new  = "<?php\n\n";
		$new .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{	
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$new .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
		
		/** -----------------------------------------
		/**  Write config file
		/** -----------------------------------------*/

		if ($fp = @fopen(CONFIG_FILE, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		
		if ($return_loc !== FALSE)
		{		
			$override = ($IN->GBL('class_override', 'GET') != '') ? AMP.'class_override='.$IN->GBL('class_override', 'GET') : '';
		
			$FNS->redirect($return_loc.$override);
			exit;
		}
	}	
	/* END */
		
		
		
	/** -------------------------------------------
	/**  Append config file 
	/** -------------------------------------------*/
	
	// This function allows us to add new config file elements.
	// Optionally, items can be removed from the config file
	// by using the second parameter. 
	// Note:  The first array must be associative, but NOT the
	// second one.

	function append_config_file($new_config = array(), $unset = array())
	{
		require CONFIG_FILE;

		/** -----------------------------------------
		/**  Write config backup file
		/** -----------------------------------------*/
		
		$old  = "<?php\n\n";
		$old .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$old .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$old .= '?'.'>';
		
		$bak_path = str_replace(EXT, '', CONFIG_FILE);
		$bak_path .= '_bak'.EXT;

		if ($fp = @fopen($bak_path, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $old, strlen($old));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
		
		/** -----------------------------------------
		/**  Merge new data to the congig file
		/** -----------------------------------------*/
		
		if (is_array($new_config) AND count($new_config) > 0)
		{
			$conf = array_merge($conf, $new_config);
		}
		
		/** -----------------------------------------
		/**  Are we removing items?
		/** -----------------------------------------*/
		
		if (is_array($unset) AND count($unset) > 0)
		{
			foreach ($unset as $kill)
			{
				if (isset($conf[$kill]))
				{
					unset($conf[$kill]);
				}
			}
		}

		/** -----------------------------------------
		/**  Build the config string
		/** -----------------------------------------*/

		$new  = "<?php\n\n";
		$new .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);
		
			$new .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';

		if ( ! $fp = @fopen(CONFIG_FILE, 'wb'))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $new, strlen($new));
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	/* END */
		
		
	/** -----------------------------------------
	/**  Template List for 404 page
	/** -----------------------------------------*/

	function site_404($page = '')
	{
		global $DB, $DSP, $LANG, $PREFS;

		$sql = "SELECT exp_template_groups.group_name, exp_templates.template_name
				FROM   exp_template_groups, exp_templates
				WHERE  exp_template_groups.group_id =  exp_templates.group_id
				AND    exp_template_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";
				
    	if (USER_BLOG !== FALSE)
		{
			$sql .= " AND exp_template_groups.group_id = '".$DB->escape_str(UB_TMP_GRP)."'";
		}
		else
		{
			$sql .= " AND exp_template_groups.is_user_blog = 'n'";
		}
				
		$sql .= " ORDER BY exp_template_groups.group_name, exp_templates.template_name";         
				
		$query = $DB->query($sql);
		
		$r = $DSP->input_select_header('site_404');
		$r .= $DSP->input_select_option('', $LANG->line('none'));

		foreach ($query->result as $row)
		{
			$selected = ($row['group_name'].'/'.$row['template_name'] == $page) ? 1 : '';
		
			$r .= $DSP->input_select_option($row['group_name'].'/'.$row['template_name'], $row['group_name'].'/'.$row['template_name'], $selected);
		}

		$r .= $DSP->input_select_footer();             
	
		return $r;
	}
	/* END */
	
	
	/** -----------------------------------------
	/**  Fetch Control Panel Themes
	/** -----------------------------------------*/
	
	function fetch_themes($default = '')
	{
		global $PREFS, $DSP;
			
		$source_dir = PATH_CP_THEME;
	
		$filelist = array();
	
		if ( ! $fp = @opendir($source_dir)) 
		{ 
			return '';
		} 

		while (false !== ($file = readdir($fp))) 
		{ 
			$filelist[count($filelist)] = $file;
		} 
	
		closedir($fp); 
		sort($filelist);

		$r = $DSP->input_select_header('cp_theme');
			
		for ($i =0; $i < sizeof($filelist); $i++) 
		{
			if ( is_dir(PATH_CP_THEME.$filelist[$i]) && ! preg_match("/[^a-z\_\-0-9]/", $filelist[$i]))
			{			
				$selected = ($filelist[$i] == $default) ? 1 : '';
				
				$name = ucwords(str_replace("_", " ", $filelist[$i]));
				
				$r .= $DSP->input_select_option($filelist[$i], $name, $selected);
			}
		}		

		$r .= $DSP->input_select_footer();

		return $r;
	}
	/* END */
	
	
	
	/** -----------------------------------------
	/**  Show file listing as a pull-down
	/** -----------------------------------------*/
	
	function theme_list($path = '', $default = '', $form_name = 'member_theme')
	{
		global $PREFS, $DSP;
		
		if ($path == '')
			return;
		
		$r = '';

        if ($fp = @opendir($path))
        { 
			$r .= $DSP->input_select_header($form_name);			
			
            while (false !== ($file = readdir($fp)))
            {
				if (@is_dir($path.$file) && $file !== '.' && $file !== '..') 
                {
					$selected = ($file == $default) ? 1 : '';
					
					$name = ucwords(str_replace("_", " ", $file));
					
					$r .= $DSP->input_select_option($file, $name, $selected);
                }
            }         
            
			$r .= $DSP->input_select_footer();
			
			closedir($fp); 
        } 

		return $r;
	}
	/* END */


	/** -----------------------------------------
	/**  Fetch encodings
	/** -----------------------------------------*/

	function fetch_encoding($which)	
	{
		global $FNS, $PREFS;
		
		if ($which == 'xml_lang')
		{
			return $FNS->encoding_menu('languages', 'xml_lang', $PREFS->ini($which));
		}
		elseif ($which == 'charset')
		{
			return $FNS->encoding_menu('charsets', 'charset', $PREFS->ini($which));
		}
	}
	/* END */
}
// END CLASS
?>