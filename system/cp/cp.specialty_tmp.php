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
 File: cp.specialty_tmp.php
-----------------------------------------------------
 Purpose: Special Purpose Templates
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Specialty_Templates {

	var $ignore = array('offline_template', 'message_template');
	

	/** ---------------------------------
	/**  Constructor
	/** ---------------------------------*/

	function Specialty_Templates()
	{
		global $LANG;
		
	    $LANG->fetch_language_file('specialty_tmp');
	}
	/* END */



	/** ---------------------------------
	/**  Offline template
	/** ---------------------------------*/
		
	function offline_template($message = '')
	{
		global $DSP, $DB, $REGX, $LANG, $PREFS;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
				
		$DSP->title = $LANG->line('offline_template');						 
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=specialty_templates', $LANG->line('specialty_templates')).
					  $DSP->crumb_item($LANG->line('offline_template'));	
		
		$DSP->body = $DSP->qdiv('tableHeading', $LANG->line('offline_template'));
		
		$DSP->body .= $DSP->qdiv('box', $DSP->qdiv('defaultBold', $LANG->line('offline_template_desc')));
		
		if ($message != '')
		{
        	$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));		
		}
		
		$query = $DB->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'offline_template'");
		
        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=sp_templ'.AMP.'P=update_offline_template'));
      
        $DSP->body .= $DSP->div('itemWrapper')  
					 .$DSP->input_textarea('template_data', $query->row['template_data'], '25', 'textarea', '100%')
					 .$DSP->div_c();
					 
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
             		 .$DSP->form_close();
	}
	/* END */
	
	
	/** ---------------------------------
	/**  Update Offline Template
	/** ---------------------------------*/
		
	function update_offline_template()
	{
		global $DB, $DSP, $LANG, $PREFS;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! isset($_POST['template_data']))
        {
        	return FALSE;
        }
	
		$DB->query("UPDATE exp_specialty_templates SET template_data = '".$DB->escape_str($_POST['template_data'])."' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'offline_template'");
	
		$this->offline_template($LANG->line('template_updated'));
	}
	/* END */
	
	

	/** ---------------------------------
	/**  User Messages Template
	/** ---------------------------------*/
		
	function user_messages_template($message = '')
	{
		global $DSP, $DB, $REGX, $LANG, $PREFS;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
				
		$DSP->title = $LANG->line('user_messages_template');						 
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=specialty_templates', $LANG->line('specialty_templates')).
					  $DSP->crumb_item($LANG->line('user_messages_template'));	
		
		$DSP->body = $DSP->qdiv('tableHeading', $LANG->line('user_messages_template'));
		
		$DSP->body .= $DSP->qdiv('box', $LANG->line('user_messages_template_desc'));
		
		$DSP->body .= $DSP->qdiv('box', 
												$DSP->qspan('alert', $LANG->line('user_messages_template_warning')).
												$DSP->qspan('defaultBold', '{title} {meta_refresh} {heading} {content} {link}')
								);
		
		if ($message != '')
		{
        	$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));		
		}
		
		$query = $DB->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'message_template'");
		
        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=sp_templ'.AMP.'P=update_user_messages_tmpl'));
      
        $DSP->body .= $DSP->div('itemWrapper')  
					 .$DSP->input_textarea('template_data', $query->row['template_data'], '25', 'textarea', '100%')
					 .$DSP->div_c();
					 
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
             		 .$DSP->form_close();
	}
	/* END */
	
	
	/** ---------------------------------
	/**  Update Offline Template
	/** ---------------------------------*/
		
	function update_user_messages_template()
	{
		global $DB, $DSP, $LANG, $PREFS;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! isset($_POST['template_data']))
        {
        	return FALSE;
        }
	
		$DB->query("UPDATE exp_specialty_templates SET template_data = '".$DB->escape_str($_POST['template_data'])."' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'message_template'");
	
		$this->user_messages_template($LANG->line('template_updated'));
	}
	/* END */
	
  
    /** ---------------------------------
    /**  Member notification templates
    /** ---------------------------------*/
    
    function mbr_notification_tmpl($message = '')
    {
        global $DSP, $IN, $DB, $LANG, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
        $DSP->title  = $LANG->line('email_notification_template');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=specialty_templates', $LANG->line('specialty_templates')).
					   $DSP->crumb_item($LANG->line('email_notification_template'));
             
        $r = '';
        
        if ($message != '')
        {
        	$r .= $DSP->qdiv('box',$DSP->qdiv('success', $LANG->line('template_updated')), '', "style='width:60%;'");
        }
     
        $r .= $DSP->table('tableBorder', '0', '10', '60%').
              $DSP->tr().
              $DSP->td('tableHeading').
              $LANG->line('email_notification_template').
              $DSP->td_c().
              $DSP->tr_c();
              
        $str = '';
        
        foreach ($this->ignore as $val)
        {
        	$str .= " template_name != '".$val."' AND";
        }
        
        $str = substr($str, 0, -3);
              
		$sql = "SELECT * 
				FROM  exp_specialty_templates 
				WHERE ".$str."
				AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
				ORDER BY template_name";


        $query = $DB->query($sql);
        
        $i = 0;
        
		foreach ($query->result as $row)
		{
			if ($PREFS->ini('forum_is_installed') != 'y' AND strpos($row['template_name'], 'forum_') !== FALSE)
			{
				continue;
			}
		
			$templates[$LANG->line($row['template_name'])] = $row['template_id'];
		}
		
		ksort($templates);
		
		foreach ($templates as $key => $val)
		{		
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
	
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=sp_templ'.AMP.'P=edit_notification_tmpl'.AMP.'id='.$val, $key)));
			$r .= $DSP->tr_c();
		}	
		        
        $r .= $DSP->table_c();
                
        $DSP->body = $r;  
    }
    /* END */
    
    
    /** ----------------------------------
    /**  Edit Email Notification Template
    /** ----------------------------------*/
    
    function edit_notification_tmpl()
    {  
		global $IN, $DSP, $DB, $REGX, $LANG, $PREFS;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $id = $IN->GBL('id'))
        {
        	return;
        }
        
        if ( ! is_numeric($id))
        {
        	return;
        }
				
		$query = $DB->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE template_id = '$id'");
		
		if ($query->num_rows == 0)
		{
			return;
		}
		
		// Available Variables for each template
		
		$vars = array(
						'admin_notify_reg'						=> array('name', 'username', 'email', 'site_name', 'control_panel_url'),
						'admin_notify_entry'					=> array('weblog_name', 'entry_title', 'entry_url', 'comment_url', 'name', 'email'),
						'admin_notify_comment'					=> array('weblog_name', 'entry_title', 'comment_url', 'comment', 'comment_id', 'name', 'url', 'email', 'location'),
						'admin_notify_trackback'				=> array('entry_title', 'comment_url', 'sending_weblog_name', 'sending_entry_title', 'sending_weblog_url', 'trackback_id'),
						'admin_notify_forum_post'				=> array('name_of_poster', 'forum_name', 'title', 'body', 'thread_url', 'post_url'),
						'admin_notify_mailinglist'				=> array('email', 'mailing_list'),
						'mbr_activation_instructions'			=> array('name',  'username', 'email', 'activation_url', 'site_name', 'site_url'),
						'forgot_password_instructions'			=> array('name', 'reset_url', 'site_name', 'site_url'),
						'reset_password_notification'			=> array('name', 'username', 'password', 'site_name', 'site_url'),
						'decline_member_validation'				=> array('name', 'site_name', 'site_url'),
						'validated_member_notify'				=> array('name', 'site_name', 'site_url'),
						'mailinglist_activation_instructions'	=> array('activation_url', 'site_name', 'site_url', 'mailing_list'),
						'comment_notification'					=> array('name_of_commenter', 'name_of_recipient', 'weblog_name', 'entry_title', 'comment_url', 'comment', 'notification_removal_url', 'site_name', 'site_url', 'comment_id'),
						'admin_notify_gallery_comment'			=> array('name_of_commenter', 'gallery_name', 'entry_title', 'comment_url', 'comment', 'comment_id'),
						'gallery_comment_notification'			=> array('name_of_commenter', 'name_of_recipient', 'gallery_name', 'entry_title', 'comment_url', 'comment', 'notification_removal_url', 'site_name', 'site_url', 'comment_id'),
						'forum_post_notification'				=> array('name_of_recipient', 'forum_name', 'title', 'thread_url', 'body', 'post_url'),
						'private_message_notification'			=> array('sender_name', 'recipient_name','message_subject', 'message_content', 'site_url', 'site_name'),
						'pm_inbox_full'							=> array('sender_name', 'recipient_name', 'pm_storage_limit','site_url', 'site_name'),
						'forum_moderation_notification'			=> array('name_of_recipient', 'forum_name', 'moderation_action', 'title', 'thread_url'),
						'forum_report_notification'				=> array('forum_name', 'reporter_name', 'author', 'body', 'reasons', 'notes', 'post_url')
					);
		
		$vstr = '';
		
		if (isset($vars[$query->row['template_name']]))
		{
			foreach ($vars[$query->row['template_name']] as $val)
			{			
				$vstr .= $DSP->qdiv('highlight', '{'.$val.'}');
			}

			if ($query->row['template_name'] == 'admin_notify_comment' || $query->row['template_name'] == 'admin_notify_trackback')
			{
				$vstr .= $DSP->qdiv('highlight', "{unwrap}{delete_link}{/unwrap}");
			}
			
			$vstr = trim($vstr);
						
			$vstr = $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('available_variables')). $DSP->qdiv('itemWrapper', $vstr));
		}
				
		$DSP->title = $LANG->line('email_notification_template');						 
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=specialty_templates', $LANG->line('specialty_templates')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=sp_templ'.AMP.'P=mbr_notification_tmpl', $LANG->line('email_notification_template'))).
					  $DSP->crumb_item($LANG->line($query->row['template_name']));

        $DSP->body = $DSP->qdiv('tableHeading', $LANG->line($query->row['template_name']));

		$DSP->body .= $DSP->div('box');		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line($query->row['template_name'].'_desc')));
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $vstr;
		$DSP->body .= $DSP->div_c();
		
		
        $DSP->body .= $DSP->form_open(
        								array('action' => 'C=admin'.AMP.'M=sp_templ'.AMP.'P=update_notification_tmpl'),
        								array('id' => $id)
        							);
        
		$DSP->body .= $DSP->div('box');
        $DSP->body .= $DSP->div('itemWrapper')
        			 .$DSP->heading($LANG->line('email_title'), 5)
                     .$DSP->input_text('data_title', $query->row['data_title'], '50', '80', 'input', '400px')
					 .$DSP->div_c();
      
        $DSP->body .= $DSP->div('itemWrapper')
                	 .$DSP->heading($LANG->line('email_message'), 5) 
					 .$DSP->input_textarea('template_data', $query->row['template_data'], '17', 'textarea', '100%')
					 .$DSP->div_c();
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div('box');		
		$DSP->body .= $DSP->heading($LANG->line('use_this_template'), 5);
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('use_this_template_exp'));
					 
		$selected = ($query->row['enable_template'] == 'y') ? 1 : '';
		
		$DSP->body .= $LANG->line('yes').NBS.$DSP->input_radio('enable_template', 'y', $selected).$DSP->nbs(3);
			 
		$selected = ($query->row['enable_template'] == 'n') ? 1 : '';
		
		$DSP->body .= $LANG->line('no').NBS.$DSP->input_radio('enable_template', 'n', $selected).$DSP->nbs(3);
		$DSP->body .= $DSP->div_c();
		
					 
		$DSP->body .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('update')))
             		 .$DSP->form_close();
	}
	/* END */
    
    
    /** ----------------------------------
    /**  Update Notification Templates
    /** ----------------------------------*/
    
    function update_notification_tmpl()
    {
		global $DB, $DSP, $LANG;
	
        if ( ! $DSP->allowed_group('can_admin_preferences'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! isset($_POST['template_data'])  ||  ! isset($_POST['id']))
        {
        	return FALSE;
        }
	
		$DB->query("UPDATE exp_specialty_templates SET data_title = '".$DB->escape_str($_POST['data_title'])."', template_data = '".$DB->escape_str($_POST['template_data'])."', enable_template = '".$DB->escape_str($_POST['enable_template'])."' WHERE template_id = '".$DB->escape_str($_POST['id'])."'");
    
    	$this->mbr_notification_tmpl(1);
    }
    /* END */


	
}
// END CLASS
?>