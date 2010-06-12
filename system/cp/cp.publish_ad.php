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
 File: cp.publish_ad.php
-----------------------------------------------------
 Purpose: The publish administration functions
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class PublishAdmin {

	var $reserved = array('random', 'date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date');

	// Default "open" and "closed" status colors

	var $status_color_open   = '009933';
	var $status_color_closed = '990000';
	
	// Category arrays
	
	var $categories = array();
	var $cat_update = array();
	
	var $temp;


    /** -----------------------------------------------------------
    /**  Constructor
    /** -----------------------------------------------------------*/
    // All it does it fetch the language file needed by the class
    //-----------------------------------------------------------

    function PublishAdmin()
    {
        global $LANG, $DSP;
            
        // Fetch language file
        
        $LANG->fetch_language_file('publish_ad');
    }
    /* END */



    /** -----------------------------------------------------------
    /**  Weblog management page
    /** -----------------------------------------------------------*/
    // This function displays the "weblog management" page
    // accessed via the "admin" tab
    //-----------------------------------------------------------

    function weblog_overview($message = '')
    {
        global $LANG, $DSP, $DB, $PREFS;  
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
                
        $DSP->title  = $LANG->line('weblog_management');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('weblog_management'));

		$DSP->right_crumb($LANG->line('create_new_weblog'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=new_weblog');

        // Fetch weblogs
        
        $query = $DB->query("SELECT weblog_id, blog_name, blog_title FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n' ORDER BY blog_title");
        
        if ($query->num_rows == 0)
        {
			$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('weblog_management'));      
			$DSP->body .= $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($LANG->line('no_weblogs_exist'), 5));
        	$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor( BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=new_weblog', $LANG->line('create_new_weblog')));
			$DSP->body .= $DSP->div_c();
        
			return;
        }     
            
        $r = $DSP->qdiv('tableHeading', $LANG->line('weblog_management'));
        
        if ($message != '')
        {
			$r .= $DSP->qdiv('box', stripslashes($message));
        }

        $r .= $DSP->table('tableBorder', '0', '', '100%');
              
        $r .= $DSP->tr().
              $DSP->td('tableHeadingAlt', '30px').$LANG->line('weblog_id').$DSP->td_c().
              $DSP->td('tableHeadingAlt').$LANG->line('weblog_name').$DSP->td_c().
              $DSP->td('tableHeadingAlt', '', '4').$LANG->line('weblog_short_name').$DSP->td_c().
              $DSP->tr_c();
        
        $i = 0;
        
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
          
            $r .= $DSP->tr();
            
            $r .= $DSP->table_qcell($style, $DSP->qspan('default', $row['weblog_id']));

            $r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $row['blog_title']).$DSP->nbs(5));
            
            $r .= $DSP->table_qcell($style, $DSP->qspan('default', $row['blog_name']).$DSP->nbs(5));
            
            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_prefs'.AMP.'weblog_id='.$row['weblog_id'], 
                                $LANG->line('edit_preferences')
                              ));
            
            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$row['weblog_id'], 
                                $LANG->line('edit_groups')
                              ));

            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete_conf'.AMP.'weblog_id='.$row['weblog_id'], 
                                $LANG->line('delete')
                              ));
                                                                          
            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();

        // Assign output data
        
        $DSP->body = $r;                            
            
    }
    /* END */



    /** --------------------------------------------------------------
    /**  "Create new weblog" form
    /** --------------------------------------------------------------*/
    // This function displays the form used to create a new weblog
    //--------------------------------------------------------------

    function new_weblog_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG, $FNS, $PREFS;
       
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        $r = <<<EOT
<script type="text/javascript"> 
<!--

function show_hide(id)
{
	if (document.getElementById(id))
	{
		if (document.getElementById(id).style.display == 'none')
		{
			document.getElementById(id).style.display = 'block';
		}
		else
		{
			document.getElementById(id).style.display = 'none';
		}
	}
}

//-->
</script> 
EOT;

        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=create_blog'));
               
        $r .= $DSP->table('tableBorder', '0', '', '100%');
		$r .= $DSP->tr()
			.$DSP->td('tableHeading', '', '2').$LANG->line('create_new_weblog').$DSP->td_c()
			.$DSP->tr_c();
			
			
		// Weblog "full name" field
        
        $r .= $DSP->tr().
              $DSP->table_qcell('tableCellTwo', $DSP->required().NBS.$DSP->qspan('defaultBold', $LANG->line('full_weblog_name', 'blog_title'))).
              $DSP->table_qcell('tableCellTwo', $DSP->input_text('blog_title', '', '20', '100', 'input', '260px')).
              $DSP->tr_c();
			        
        // Weblog "short name" field
        
        $r .= $DSP->tr().
              $DSP->table_qcell('tableCellOne', $DSP->required().NBS.$DSP->qspan('defaultBold', $LANG->line('short_weblog_name', 'blog_name')).$DSP->qdiv('', $LANG->line('single_word_no_spaces')), '40%').
              $DSP->table_qcell('tableCellOne', $DSP->input_text('blog_name', '', '20', '40', 'input', '260px'), '60%').
              $DSP->tr_c();
		
		// Duplicate Preferences Select List
		
		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('duplicate_weblog_prefs')));

		$w  = $DSP->input_select_header('duplicate_weblog_prefs');
		$w .= $DSP->input_select_option('', $LANG->line('do_not_duplicate'));
		
		$wquery = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY blog_name");
		
		if ($wquery->num_rows > 0)
		{
			foreach($wquery->result as $row)
			{
				$w .= $DSP->input_select_option($row['weblog_id'], $row['blog_title']);
			}
		}
		
		$w .= $DSP->input_select_footer();
		
		$r .= $DSP->table_qcell('tableCellTwo', $w).
			  $DSP->tr_c();

		// Edit Group Preferences option
		
        $r .= $DSP->tr().
              $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $LANG->line('edit_group_prefs')), '40%').
              $DSP->table_qcell('tableCellOne', $DSP->input_radio('edit_group_prefs', 'y', '', 'onclick="show_hide(\'group_preferences\');"').
              									NBS.$LANG->line('yes').
              									NBS.NBS.
              									$DSP->input_radio('edit_group_prefs', 'n', 1, 'onclick="show_hide(\'group_preferences\');"').
              									NBS.$LANG->line('no'), '60%').
              $DSP->tr_c();
          
        $r .= $DSP->table_c().BR;
			  
        
        
        // GROUP FIELDS
        
        $g = '';
        $i = 0;
        $cat_group = '';
        $status_group = '';
        $field_group = '';
        
        $r .= $DSP->div('', '', 'group_preferences', '', 'style="display:none;"');
        $r .= $DSP->table('tableBorder', '0', '', '100%');
		$r .= $DSP->tr().
			  $DSP->td('tableHeadingAlt', '100%', 2).$LANG->line('edit_group_prefs').$DSP->td_c().
			  $DSP->tr_c();

        // Category group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
           
        $query = $DB->query("SELECT group_id, group_name FROM exp_category_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
        
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('category_group')), '40%', 'top');
        
        $g .= $DSP->td($style).
              $DSP->input_select_header('cat_group[]', ($query->num_rows > 0) ? 'y' : '');
        
        $selected = '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);
                 
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {                           
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name']);
            }
        }
        
        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
        
    
        // Status group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
        $query = $DB->query("SELECT group_id, group_name FROM exp_status_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
    
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('status_group')));
              
        $g .= $DSP->td($style).
              $DSP->input_select_header('status_group');
        
        $selected = '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);
    
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $selected = ($status_group == $row['group_id']) ? 1 : '';
                                        
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name'], $selected);
            }
        }

        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
        
            
        // Field group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
        $query = $DB->query("SELECT group_id, group_name FROM exp_field_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
    
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_group')));
        
        $g .= $DSP->td($style).
              $DSP->input_select_header('field_group');
        
        $selected = '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $selected = ($field_group == $row['group_id']) ? 1 : '';
                                        
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name'], $selected);
            }
        }

        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c().BR.
              $DSP->div_c();
                
        $r .= $g;
                
        // Table end
        
        // Create Template
        
             
		if ($DSP->allowed_group('can_admin_templates'))
		{		
			$r .= $DSP->table('tableBorder', '0', '', '100%')
				 .$DSP->tr()
				 .$DSP->td('tableHeadingAlt', '', '3').$LANG->line('template_creation').$DSP->td_c()
				 .$DSP->tr_c();
			
			$r .= $DSP->tr()
				 .$DSP->table_qcell('tableCellOne', $DSP->input_radio('create_templates', 'no', 1), '2%')
				 .$DSP->td('tableCellOne', '', '3').$DSP->qdiv('defaultBold', $LANG->line('no')).$DSP->td_c()
				 .$DSP->tr_c();
			
						
			$data = $FNS->create_directory_map(PATH_THEMES.'site_themes/', TRUE);
			
			$d = '&nbsp;';
			
			if (count($data) > 0)
			{              
				$d = $DSP->input_select_header('template_theme');
					
				foreach ($data as $val)
				{
					if ($val == 'rss.php')
						continue;
						
					if ( ! file_exists(PATH_THEMES.'site_themes/'.$val.'/'.$val.'.php'))
					{
						continue;
					}
				
					$nval = str_replace("_", " ", $val);
					$nval = ucwords($nval);
				
					$d .= $DSP->input_select_option($val, $nval);
				}
				
				$d .= $DSP->input_select_footer();
			}
			
			$r .= $DSP->tr()
				 .$DSP->table_qcell('tableCellTwo', $DSP->input_radio('create_templates', 'theme', ''), '2%', 'top')
				 .$DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('use_a_theme'), '38%')
				 .$DSP->qdiv('itemWrapper',$DSP->input_checkbox('add_rss', 'y', 0).' '.$LANG->line('include_rss_templates')))
				 .$DSP->table_qcell('tableCellTwo', $d, '60%')
				 .$DSP->tr_c();
			   
			$sql = "SELECT group_id, group_name, exp_sites.site_label
					FROM   exp_template_groups, exp_sites
					WHERE  exp_template_groups.site_id = exp_sites.site_id ";
					
			if ($PREFS->ini('multiple_sites_enabled') !== 'y')
			{
				$sql .= "AND exp_template_groups.site_id = '1' ";
			}
			 
			if (USER_BLOG == TRUE)
			{
				$sql .= "AND exp_template_groups.group_id = '".$SESS->userdata['tmpl_group_id']."'";
			}
			else
			{
				$sql .= "AND exp_template_groups.is_user_blog = 'n'";
			}
					
			$sql .= " ORDER BY exp_template_groups.group_name";         
					
					
			$query = $DB->query($sql);
					
					
			$d  = $DSP->input_select_header('old_group_id');
					
			foreach ($query->result as $row)
			{
				$d .= $DSP->input_select_option($row['group_id'], ($PREFS->ini('multiple_sites_enabled') == 'y') ? $row['site_label'].NBS.'-'.NBS.$row['group_name'] : $row['group_name']);
			}
			
			$d .= $DSP->input_select_footer();
			
			
			$r .= $DSP->tr()
				 .$DSP->table_qcell('tableCellOne', $DSP->input_radio('create_templates', 'duplicate', ''))
				 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('duplicate_group')))
				 .$DSP->table_qcell('tableCellOne', $d)
				 .$DSP->tr_c();
			
			$r .= $DSP->tr()
				 .$DSP->table_qcell('tableCellTwo', NBS)
				 .$DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $DSP->required().$LANG->line('template_group_name')).$DSP->qdiv('', $LANG->line('new_group_instructions')).$DSP->qdiv('', $LANG->line('single_word_no_spaces')))
				 .$DSP->td('tableCellTwo', '', '').$DSP->input_text('group_name', '', '16', '50', 'input', '130px').$DSP->td_c()
				 .$DSP->tr_c();
				 
			$r .= $DSP->table_c();
        }

        
        // Submit button
		$r .= $DSP->qdiv('itemWrapper', $DSP->required(1));
        $r .= $DSP->qdiv('', $DSP->input_submit($LANG->line('submit')));
              
        $r .= $DSP->form_close();
        
        // Assign output data
        
        $DSP->title = $LANG->line('create_new_weblog');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list', $LANG->line('weblog_management'))).
        			  $DSP->crumb_item($LANG->line('new_weblog'));
        $DSP->body  = $r;                
    }
    /* END */

 
    /** -----------------------------------------------------------
    /**  Weblog preference submission handler
    /** -----------------------------------------------------------*/
    // This function receives the submitted weblog preferences
    // and stores them in the database.
    //-----------------------------------------------------------

    function update_weblog_prefs()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $FNS, $PREFS, $SESS, $LOC;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        // If the $weblog_id variable is present we are editing an
        // existing weblog, otherwise we are creating a new one
        
        $edit = (isset($_POST['weblog_id'])) ? TRUE : FALSE;
        
        $add_rss = (isset($_POST['add_rss'])) ? TRUE : FALSE;
        unset($_POST['add_rss']);
        
        $return = ($IN->GBL('return')) ? TRUE : FALSE; 
        unset($_POST['return']);
        
        unset($_POST['edit_group_prefs']);
		
		$dupe_id = $IN->GBL('duplicate_weblog_prefs');
		unset($_POST['duplicate_weblog_prefs']);
		
        // Check for required fields

        $error = array();
        
        if ($_POST['blog_name'] == '')
        {
            $error[] = $LANG->line('no_weblog_name');
        }
          
        if ($_POST['blog_title'] == '')
        {
            $error[] = $LANG->line('no_weblog_title');
        }
        
        if (preg_match('/[^a-z0-9\-\_]/i', $_POST['blog_name']))
        {
            $error[] = $LANG->line('invalid_short_name');
        }
        
        if (isset($_POST['url_title_prefix']) && $_POST['url_title_prefix'] != '')
        {
        	$_POST['url_title_prefix'] = strtolower(strip_tags($_POST['url_title_prefix']));
        	
        	if ( ! preg_match("/^[\w\-]+$/", $_POST['url_title_prefix']))
        	{
        	    $error[] = $LANG->line('invalid_url_title_prefix');
        	}
        }
  
         if (count($error) > 0)
         {
            $msg = '';
            
            foreach($error as $val)
            {
                $msg .= $val.BR;  
            }
            
            return $DSP->error_message($msg);
         }  
         
         if (isset($_POST['comment_expiration']))
         {
			if ( ! is_numeric($_POST['comment_expiration']) || $_POST['comment_expiration'] == '')
			{
				$_POST['comment_expiration'] = 0;
			}
         }
         
        // Is the weblog name taken?
        
        $sql = "SELECT COUNT(*) AS count FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND blog_name = '".$DB->escape_str($_POST['blog_name'])."'";
        
        if ($edit == TRUE)
        {
            $sql .= " AND weblog_id != '".$DB->escape_str($_POST['weblog_id'])."'";
        } 
        
        $query = $DB->query($sql);        
      
        if ($query->row['count'] > 0)
        {
            return $DSP->error_message($LANG->line('taken_weblog_name'));
        }
        
        
		/** -----------------------------------------
		/**  Template Error Trapping
		/** -----------------------------------------*/

		if ($edit == FALSE)
		{
			$create_templates	= $IN->GBL('create_templates');
			$old_group_id		= $IN->GBL('old_group_id');
			$group_name			= strtolower($IN->GBL('group_name', 'POST'));
			$template_theme		= $FNS->filename_security($IN->GBL('template_theme'));
	
			unset($_POST['create_templates']);
			unset($_POST['old_group_id']);
			unset($_POST['group_name']);
			unset($_POST['template_theme']);
	
			if ($create_templates != 'no')
			{
				$LANG->fetch_language_file('templates');
			
				if ( ! $DSP->allowed_group('can_admin_templates'))
				{
					return $DSP->no_access_message();
				}
	
				if ( ! $group_name)
				{
					return $DSP->error_message($LANG->line('group_required'));
				}
				
				if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $group_name))
				{
					return $DSP->error_message($LANG->line('illegal_characters'));
				}
				
				$reserved[] = 'act';
				$reserved[] = 'trackback';
				
				if ($PREFS->ini("forum_is_installed") == 'y' AND $PREFS->ini("forum_trigger") != '')
				{
					$reserved[] = $PREFS->ini("forum_trigger");
				}
	
				if (in_array($group_name, $reserved))
				{
					return $DSP->error_message($LANG->line('reserved_name'));
				}
				
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_template_groups 
									 WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' 
									 AND group_name = '".$DB->escape_str($group_name)."'");
				
				if ($query->row['count'] > 0)
				{
					return $DSP->error_message($LANG->line('template_group_taken'));
				}
			}
        }
        
		/** -----------------------------------------
		/**  Create Weblog
		/** -----------------------------------------*/
                          
        // Construct the query based on whether we are updating or inserting
                
        if (isset($_POST['apply_expiration_to_existing']))
        {        	
        	$this->update_comment_expiration($_POST['weblog_id'], $_POST['comment_expiration']);
        }
        
		unset($_POST['apply_expiration_to_existing']);
		
		if (isset($_POST['cat_group']) && is_array($_POST['cat_group']))
		{
			foreach($_POST['cat_group'] as $key => $value)
			{
				unset($_POST['cat_group_'.$key]);
			}
			
			$_POST['cat_group'] = implode('|', $_POST['cat_group']);
		}
   
        if ($edit == FALSE)
        {  
            unset($_POST['weblog_id']);
            unset($_POST['clear_versioning_data']);
            
            $_POST['blog_url']      = $FNS->fetch_site_index();
            $_POST['blog_lang']     = $PREFS->ini('xml_lang');
            $_POST['blog_encoding'] = $PREFS->ini('charset');            
            
            // Assign field group if there is only one
            
            if ( ! isset($_POST['field_group']) OR (isset($_POST['field_group']) && ! is_numeric($_POST['field_group'])))
            {
            	$query = $DB->query("SELECT group_id FROM exp_field_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
            
            	if ($query->num_rows == 1)
            	{
            	    $_POST['field_group'] = $query->row['group_id'];
            	}
            }
            
            // Insert data
            
            $_POST['site_id'] = $PREFS->ini('site_id');

			// duplicating preferences?
			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				$wquery = $DB->query("SELECT * FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str($dupe_id)."'");

				if ($wquery->num_rows == 1)
				{
					$exceptions = array('weblog_id', 'site_id', 'blog_name', 'blog_title', 'total_entries',
										'total_comments', 'total_trackbacks', 'last_entry_date', 'last_comment_date',
										'last_trackback_date');

					foreach($wquery->row as $key => $val)
					{
						// don't duplicate fields that are unique to each weblog
						if (! in_array($key, $exceptions))
						{
							switch ($key)
							{
								// category, field, and status fields should only be duped
								// if both weblogs are assigned to the same group of each
								case 'cat_group':
									// allow to implicitly set category group to "None"
									if (! isset($_POST[$key]))
									{
										$_POST[$key] = $val;
									}
									break;
								case 'status_group':
								case 'field_group':
									if (! isset($_POST[$key]) OR $_POST[$key] == '')
									{
										$_POST[$key] = $val;
									}
									break;
								case 'deft_status':
									if (! isset($_POST['status_group']) OR $_POST['status_group'] == $wquery->row['status_group'])
									{
										$_POST[$key] = $val;
									}
									break;
								case 'search_excerpt':
									if (! isset($_POST['field_group']) OR $_POST['field_group'] == $wquery->row['field_group'])
									{
										$_POST[$key] = $val;
									}
									break;
								case 'deft_category':
									if (! isset($_POST['cat_group']) OR count(array_diff(explode('|', $_POST['cat_group']), explode('|', $wquery->row['cat_group']))) == 0)
									{
										$_POST[$key] = $val;
									}
									break;
								case 'blog_url':
								case 'comment_url':
								case 'search_results_url':
								case 'tb_return_url':
								case 'ping_return_url':
								case 'rss_url':
									if ($create_templates != 'no')
									{
										if ( ! isset($old_group_name))
										{
											$gquery = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$DB->escape_str($old_group_id)."'");
											$old_group_name = $gquery->row['group_name'];
										}
										
										$_POST[$key] = str_replace("/{$old_group_name}/", "/{$group_name}/", $val);
									}
									else
									{
										$_POST[$key] = $val;
									}
									break;
								default :
									$_POST[$key] = $val;
									break;
							}
						}
					}
				}
			}

            $sql = $DB->insert_string('exp_weblogs', $_POST);

            $DB->query($sql);
            
            $insert_id = $DB->insert_id;
            $weblog_id = $insert_id;
            
            $success_msg = $LANG->line('weblog_created');
            
            $crumb = $DSP->crumb_item($LANG->line('new_weblog'));

            $LOG->log_action($success_msg.$DSP->nbs(2).$_POST['blog_title']);            
        }
        else
        {
        	if (isset($_POST['clear_versioning_data']))
        	{
        		$DB->query("DELETE FROM exp_entry_versioning WHERE weblog_id  = '".$DB->escape_str($_POST['weblog_id'])."'");
				unset($_POST['clear_versioning_data']);
        	}
        
        
            $sql = $DB->update_string('exp_weblogs', $_POST, 'weblog_id='.$DB->escape_str($_POST['weblog_id']));  
            $DB->query($sql);
            $weblog_id = $DB->escape_str($_POST['weblog_id']);

            $success_msg = $LANG->line('weblog_updated');
            
            $crumb = $DSP->crumb_item($LANG->line('update'));
        }
        
		/** -----------------------------------------
		/**  Create Templates
		/** -----------------------------------------*/

		if ($edit == FALSE)
		{
			if ($create_templates != 'no')
			{
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_template_groups WHERE is_user_blog = 'n'");
				$group_order = $query->row['count'] +1;
			
				$DB->query(
							$DB->insert_string(
												 'exp_template_groups', 
												  array(
														 'group_id'        => '', 
														 'group_name'      => $group_name,
														 'group_order'     => $group_order,
														 'is_site_default' => 'n',
														 'site_id'		   => $PREFS->ini('site_id')
													   )
											   )      
							);
							
				$group_id = $DB->insert_id;
							
				if ($create_templates == 'duplicate')
				{		
					$query = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$DB->escape_str($old_group_id)."'");
					$old_group_name = $query->row['group_name'];
				
					$query = $DB->query("SELECT template_name, template_data, template_type, template_notes, cache, refresh, no_auth_bounce, allow_php, php_parse_location FROM exp_templates WHERE group_id = '".$DB->escape_str($old_group_id)."'");
				
					if ($query->num_rows == 0)
					{
						$DB->query(
								$DB->insert_string(
												   'exp_templates', 
													array(
														   'template_id'   => '', 
														   'group_id'      => $group_id,
														   'template_name' => 'index',
														   'edit_date'	   => $LOC->now,
														   'site_id'	   => $PREFS->ini('site_id')
														 )
												 )
								);
					}
					else
					{		
						$old_blog_name = '';
					
						foreach ($query->result as $row)
						{
							if ($old_blog_name == '')
							{
								if (preg_match_all("/weblog=[\"'](.+?)[\"']/", $row['template_data'], $matches))
								{
									for ($i = 0; $i < count($matches['1']); $i++)
									{
										if (substr($matches['1'][$i], 0, 1) != '{')
										{
											$old_blog_name = $matches['1'][$i];
											break;
										}
									}
								}
							}
												
							$temp = str_replace('weblog="'.$old_blog_name.'"', 'weblog="'.$_POST['blog_name'].'"', $row['template_data']);
							$temp = str_replace("weblog='".$old_blog_name."'", 'weblog="'.$_POST['blog_name'].'"', $temp);
							$temp = preg_replace("/{stylesheet=.+?\/(.+?)}/", "{stylesheet=".$group_name."/\\1}", $temp);					
				
							$temp = preg_replace("#assign_variable:master_weblog_name=\".+?\"#", 'assign_variable:master_weblog_name="'.$_POST['blog_name'].'"', $temp);
							$temp = preg_replace("#assign_variable:master_weblog_name=\'.+?\'#", "assign_variable:master_weblog_name='".$_POST['blog_name']."'", $temp);
							$temp = preg_replace('#assign_variable:my_template_group=(\042|\047)([^\\1]*?)\\1#', "assign_variable:my_template_group=\\1{$group_name}\\1", $temp);
							
							$temp = preg_replace("#".$old_group_name."/(.+?)#", $group_name."/\\1", $temp);
				
							$data = array(
											'template_id'    		=> '',
											'group_id'       		=> $group_id,
											'template_name'  		=> $row['template_name'],
											'template_notes'  		=> $row['template_notes'],
											'cache'  				=> $row['cache'],
											'refresh'  				=> $row['refresh'],
											'no_auth_bounce'  		=> $row['no_auth_bounce'],
											'php_parse_location'	=> $row['php_parse_location'],
											'allow_php'  			=> ($SESS->userdata['group_id'] == 1) ? $row['allow_php'] : 'n',
											'template_type' 		=> $row['template_type'],
											'template_data'  		=> $temp,
											'edit_date'				=> $LOC->now,
											'site_id'				=> $PREFS->ini('site_id')
										 );
							
									$DB->query($DB->insert_string('exp_templates', $data));
							}
					}
				}
				else
				{
					$type = 'core';
					if ($fp = @opendir(PATH_MOD)) 
					{ 
						while (false !== ($file = readdir($fp))) 
						{
							if (strpos($file, '.') === FALSE)
							{
								if ($file == 'mailinglist')
								{
									$type = 'full';
									break;
								}
							}
						} 
						closedir($fp); 
					} 
									
				
					require PATH_THEMES.'site_themes/'.$template_theme.'/'.$template_theme.'.php';
					
					foreach ($template_matrix as $tmpl)
					{
						$Q[] = array($tmpl['0'](), "INSERT INTO exp_templates(template_id, group_id, template_name, template_type, template_data, edit_date, site_id) 
													VALUES ('', '$group_id', '".$DB->escape_str($tmpl['0'])."', '".$DB->escape_str($tmpl['1'])."', '{template}', '".$LOC->now."', '".$DB->escape_str($PREFS->ini('site_id'))."')");
					}
					
					if ($add_rss == TRUE)
					{
						require PATH_THEMES.'site_themes/rss/rss.php';					
						$Q[] = array(rss_2(), "INSERT INTO exp_templates(template_id, group_id, template_name, template_type, template_data, edit_date, site_id) 
											   VALUES ('', '$group_id', 'rss_2.0', 'rss', '{template}', '".$DB->escape_str($LOC->now)."', '".$DB->escape_str($PREFS->ini('site_id'))."')");
											   
						$Q[] = array(atom(), "INSERT INTO exp_templates(template_id, group_id, template_name, template_type, template_data, edit_date, site_id) 
											  VALUES ('', '$group_id', 'atom', 'rss', '{template}', '".$DB->escape_str($LOC->now)."', '".$DB->escape_str($PREFS->ini('site_id'))."')");
					}				
				
					foreach ($Q as $val)
					{
						$temp = $val['0'];
						
						$temp = str_replace('weblog="weblog1"', 'weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace("weblog='weblog1'", 'weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace('my_weblog="weblog1"', 'my_weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace("my_weblog='weblog1'", 'my_weblog="'.$_POST['blog_name'].'"', $temp);
						
						$temp = str_replace('weblog="default_site"', 'weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace("weblog='default_site'", 'weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace('my_weblog="default_site"', 'my_weblog="'.$_POST['blog_name'].'"', $temp);
						$temp = str_replace("my_weblog='default_site'", 'my_weblog="'.$_POST['blog_name'].'"', $temp);
						
						$temp = str_replace('my_template_group="site"', 'my_template_group="'.$group_name.'"', $temp);
						$temp = str_replace("my_template_group='site'", 'my_template_group="'.$group_name.'"', $temp);
						
						$temp = str_replace("{stylesheet=weblog/weblog_css}", "{stylesheet=".$group_name."/site_css}", $temp);
						$temp = str_replace("{stylesheet=site/site_css}", "{stylesheet=".$group_name."/site_css}", $temp);
						
						$temp = str_replace('assign_variable:master_weblog_name="weblog1"', 'assign_variable:master_weblog_name="'.$_POST['blog_name'].'"', $temp);
						$temp = preg_replace("#weblog/(.+?)#", $group_name."/\\1", $temp);

						$temp = addslashes($temp);
						$sql  = str_replace('{template}', $temp, $val['1']);
					
						$DB->query($sql);
					}
				}
			}
        } 
        
        $message = $DSP->qdiv('itemWrapper', $DSP->qspan('success', $success_msg).NBS.NBS.'<b>'.$_POST['blog_title'].'</b>');

		if ($edit == FALSE OR $return === TRUE)
        	return $this->weblog_overview($message);
        else
        	return $this->edit_blog_form($message, $weblog_id);
    }
    /* END */
    
    

    /** -------------------------------------------
    /**  Update weblog entries with comment expiration
    /** -------------------------------------------*/

    function update_comment_expiration($weblog_id = '', $expiration = '')
    {
        global $DSP, $IN, $DB, $LOG, $LANG, $FNS, $PREF;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if ($weblog_id == '')
        {
        		return FALSE;
        }
        
        if ($expiration == '')
        		$expiration = 0;
        
        $time = $expiration * 86400;
        
        $expdate = '';
        
        $query = $DB->query("SELECT entry_id, entry_date FROM exp_weblog_titles WHERE weblog_id = '".$DB->escape_str($weblog_id)."'");
        
        if ($query->num_rows > 0)
        {
			foreach ($query->result as $row)
			{
				if ($expiration > 0)
				{
					$expdate = $row['entry_date'] + $time;
				}
				
				$DB->query("UPDATE exp_weblog_titles SET comment_expiration_date = '$expdate' WHERE entry_id = '".$DB->escape_str($row['entry_id'])."'");
			}
        }
        
		return;    
    }
    /* END */


    /** -------------------------------------------
    /**  Create pull-down optios from dirctory map
    /** -------------------------------------------*/

    function render_map_as_select_options($zarray, $array_name = '') 
    {	
        foreach ($zarray as $key => $val)
        {
            if ( is_array($val))
            {
                if ($array_name != "")
                    $key = $array_name.'/'.$key;
            
                $this->render_map_as_select_options($val, $key);
            }		
            else
            {
                if ($array_name <> "")
                    $val = $array_name.'/'.$val;
				
				if (substr($val, -4) == '.php')
				{
					if ($val != 'theme_master.php')
					{				   
						$this->template_map[] = $val;
					}
				}
			}
        }
    }
    /* END */


    /** -----------------------------------------------------------
    /**  Weblog preferences form
    /** -----------------------------------------------------------*/
    // This function displays the form used to edit the various 
    // preferences for a given weblog
    //-----------------------------------------------------------

    function edit_blog_form($msg='', $weblog_id='')
    {  
        global $DSP, $IN, $DB, $REGX, $LANG, $FNS, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        // Set default values
        
        $i            = 0;
        $blog_name    = '';
        $blog_title   = '';
        $cat_group    = '';
        $status_group = '';
        
        
        // If we don't have the $weblog_id variable, bail out.
        
        if ($weblog_id == '')
        {
        	if ( ! $weblog_id = $IN->GBL('weblog_id'))
        	{
        	    return FALSE;
        	}
        }
        
        if ( ! is_numeric($weblog_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT * FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        if ($msg != '')
        {
        	$DSP->body .= $DSP->qdiv('box', $msg);
        }
        
        $DSP->body_props .= ' onload="showHideMenu(\'weblog\');"';
                        
        // Build the output
        
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
		document.getElementById(lastShownObj + '_on').style.display = 'none';
	}
	
	lastShownObj = objValue;
	lastShownColor = document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color;
	
	document.getElementById(objValue + '_on').style.display = 'block';
	document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color = '#000';
}

//-->
</script> 
        <?php
        
        $buffer = ob_get_contents();
        ob_end_clean();         
        $DSP->body .= $buffer;
        
        // Third table cell contains are preferences in hidden <div>'s
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_preferences'));
        $r .= $DSP->input_hidden('weblog_id', $weblog_id);
        
        $r .= $DSP->qdiv('default', '', 'menu_contents');
          
		$r .= '<div id="weblog_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='weblog2' colspan='2'>";
		$r .= NBS.$LANG->line('weblog_base_setup').$DSP->td_c();     
        $r .= $DSP->tr_c();
        
        /** -------------------------
        /**  General settings
        /** ------------------------*/
        
        // Weblog "full name" field
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->required().NBS.$DSP->qspan('defaultBold', $LANG->line('full_weblog_name', 'blog_title')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('blog_title', $blog_title, '20', '100', 'input', '260px'), '50%').
              $DSP->tr_c();
        
        // Weblog "short name" field

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
        
        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->required().NBS.$DSP->qspan('defaultBold', $LANG->line('short_weblog_name', 'blog_name')).$DSP->nbs(2).'-'.$DSP->nbs(2).$LANG->line('single_word_no_spaces'), '50%').
              $DSP->table_qcell($style, $DSP->input_text('blog_name', $blog_name, '20', '40', 'input', '260px'), '50%').
              $DSP->tr_c();
                            
        // Weblog descriptions field
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('blog_description', 'blog_descriptions')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('blog_description', $blog_description, '50', '225', 'input', '100%'), '50%').
              $DSP->tr_c();
        
        
        // Weblog Language
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('blog_lang', 'blog_lang')), '50%').
              $DSP->table_qcell($style, $FNS->encoding_menu('languages', 'blog_lang', $blog_lang), '50%').
              $DSP->tr_c();
        
        // Weblog Encoding
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('blog_encoding', 'blog_encoding')), '50%').
              $DSP->table_qcell($style, $FNS->encoding_menu('charsets', 'blog_encoding', $blog_encoding), '50%').
              $DSP->tr_c().
              $DSP->table_c();
              
		$r .= $DSP->div_c();	
                              
        /** ---------------------------
        /**  Paths
        /** ---------------------------*/
          
		$r .= '<div id="paths_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='paths2' colspan='2'>";
		$r .= NBS.$LANG->line('paths').$DSP->td_c();     
        $r .= $DSP->tr_c();
        
        // Weblog URL field
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('blog_url', 'blog_url')).$DSP->qdiv('default', $LANG->line('weblog_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('blog_url', $blog_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();
              
        // comment URL
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_url', 'comment_url')).$DSP->qdiv('default', $LANG->line('comment_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('comment_url', $comment_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();
        
        // Search results URL
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('search_results_url', 'search_results_url')).$DSP->qdiv('default', $LANG->line('search_results_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('search_results_url', $search_results_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();
              
        // TB return URL
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('tb_return_url', 'tb_return_url')).$DSP->qdiv('default', $LANG->line('tb_return_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('tb_return_url', $tb_return_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();

 		// Ping pMachine URL      
       
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('ping_return_url', 'ping_return_url')).$DSP->qdiv('default', $LANG->line('ping_return_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('ping_return_url', $ping_return_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();
		
		// RSS URL - Extended Ping 
       
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('rss_url', 'rss_url')).$DSP->qdiv('default', $LANG->line('rss_url_exp')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('rss_url', $rss_url, '50', '80', 'input', '100%'), '50%').
              $DSP->tr_c();

		// live_look_template

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('live_look_template')))
             .$DSP->td($style, '50%')
			 .$DSP->input_select_header('live_look_template')
			 .$DSP->input_select_option('0', $LANG->line('no_live_look_template'), ($live_look_template == 0) ? '1' : 0);

		$sql = "SELECT tg.group_name, t.template_id, t.template_name
				FROM   exp_template_groups tg, exp_templates t
				WHERE  tg.group_id = t.group_id
				AND    tg.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";

		if (USER_BLOG == TRUE)
		{
			$sql .= "AND tg.group_id = '".$SESS->userdata['tmpl_group_id']."' ";
		}
		else
		{
			$sql .= "AND tg.is_user_blog = 'n' ";
		}

		$sql .= " ORDER BY tg.group_name, t.template_name";

		$tquery = $DB->query($sql);
		
		if ($tquery->num_rows > 0)
		{
			foreach ($tquery->result as $template)
			{                           
				$r .= $DSP->input_select_option($template['template_id'], $template['group_name'].'/'.$template['template_name'], (($template['template_id'] == $live_look_template) ? 1 : ''));
			}			
		}

		$r .= $DSP->input_select_footer()
        	 .$DSP->td_c()
             .$DSP->tr_c();

		$r .= $DSP->tr_c().
              $DSP->table_c();
    
		$r .= $DSP->div_c();	

        
        /** ---------------------------
        /**  Administrative settings
        /** ---------------------------*/
          
		$r .= '<div id="admin_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='admin2' colspan='2'>";
		$r .= NBS.$LANG->line('default_settings').$DSP->td_c();     
        $r .= $DSP->tr_c();
                
        
        // Default status menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('default_status')), '50%');
              
        $r .= $DSP->td($style, '50%').
              $DSP->input_select_header('deft_status');
        
        $query = $DB->query("SELECT * FROM exp_statuses WHERE group_id = '".$DB->escape_str($status_group)."' ORDER BY status");
        
        if ($query->num_rows == 0)
        {
			$selected = ($deft_status == 'open') ? 1 : '';
				
			$r .= $DSP->input_select_option('open', $LANG->line('open'), $selected);
	
			$selected = ($deft_status == 'closed') ? 1 : '';
			
			$r .= $DSP->input_select_option('closed', $LANG->line('closed'), $selected);        
        }
        else
        {
            foreach ($query->result as $row)
            {
                $selected = ($deft_status == $row['status']) ? 1 : '';
                
				$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];
                                    
                $r .= $DSP->input_select_option($row['status'], $status_name, $selected);
            }
        }
        
        $r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
                

        // Default category menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('default_category')), '50%');
              
        $r .= $DSP->td($style, '50%').
              $DSP->input_select_header('deft_category');
        
        $selected = '';
            
        $r .= $DSP->input_select_option('', $LANG->line('none'), $selected);
 
		$cats = implode("','", $DB->escape_str(explode('|', $cat_group)));
        $query = $DB->query("SELECT CONCAT(g.group_name, ': ', c.cat_name) as display_name, c.cat_id, c.cat_name, g.group_name
							FROM  exp_categories c, exp_category_groups g
							WHERE g.group_id = c.group_id
							AND c.group_id IN ('{$cats}') ORDER BY display_name");

        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $selected = ($deft_category == $row['cat_id']) ? 1 : '';
                                    
                $r .= $DSP->input_select_option($row['cat_id'], $row['display_name'], $selected);
            }
        }
        
        $r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
                


        // Enable comments
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('deft_comments')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('deft_comments', 'y', ($deft_comments == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('deft_comments', 'n', ($deft_comments == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();
             
        // Enable trackback pings
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('deft_trackbacks')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($deft_trackbacks == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('deft_trackbacks', 'y', $selected).$DSP->nbs(3);

              $selected = ($deft_trackbacks == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('deft_trackbacks', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             
       // Default field for search excerpt
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('search_excerpt')), '50%');
            
        $r .= $DSP->td($style, '50%');
              
        $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE field_search = 'y' AND group_id = '".$DB->escape_str($field_group)."'");              

		$r .= $DSP->input_select_header('search_excerpt');
	
		foreach ($query->result as $row)
		{
			$selected = ($search_excerpt == $row['field_id']) ? 1 : '';
				
			$r .= $DSP->input_select_option($row['field_id'], $row['field_label'], $selected);
		}
		
		$r .= $DSP->input_select_footer();
        
                
        $r .= $DSP->td_c().
              $DSP->tr_c();
              
		$r .= $DSP->table_c();
        $r .= $DSP->div_c();
        
        
        /** ---------------------------
        /**  Weblog posting settings
        /** ---------------------------*/
		$r .= '<div id="posting_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='posting2' colspan='2'>";
		$r .= NBS.$LANG->line('weblog_settings').$DSP->td_c();     
        $r .= $DSP->tr_c();
              
        // HTML formatting
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('weblog_html_formatting')), '50%');
              
        $r .= $DSP->td($style, '50%').
              $DSP->input_select_header('weblog_html_formatting');

        $selected = ($weblog_html_formatting == 'none') ? 1 : '';
            
        $r .= $DSP->input_select_option('none', $LANG->line('convert_to_entities'), $selected);

        $selected = ($weblog_html_formatting == 'safe') ? 1 : '';
        
        $r .= $DSP->input_select_option('safe', $LANG->line('allow_safe_html'), $selected);
                
        $selected = ($weblog_html_formatting == 'all') ? 1 : '';
        
        $r .= $DSP->input_select_option('all', $LANG->line('allow_all_html'), $selected);
                
        $r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();


        // Allow IMG URLs?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('weblog_allow_img_urls')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($weblog_allow_img_urls == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('weblog_allow_img_urls', 'y', $selected).$DSP->nbs(3);

              $selected = ($weblog_allow_img_urls == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('weblog_allow_img_urls', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();


        // Auto link URLs?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('auto_link_urls')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($weblog_auto_link_urls == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('weblog_auto_link_urls', 'y', $selected).$DSP->nbs(3);

              $selected = ($weblog_auto_link_urls == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('weblog_auto_link_urls', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();

        $r .= $DSP->table_c();
        $r .= $DSP->div_c();
                



        /** ---------------------------
        /**  Versioning settings
        /** ---------------------------*/
          
		$r .= '<div id="versioning_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='versioning2' colspan='2'>";
		$r .= NBS.$LANG->line('versioning').$DSP->td_c();     
        $r .= $DSP->tr_c();
              

        // Enable Versioning?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('enable_versioning')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($enable_versioning == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('enable_versioning', 'y', $selected).$DSP->nbs(3);

              $selected = ($enable_versioning == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('enable_versioning', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();


        // Enable Quicksave versioning
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('enable_qucksave_versioning')).BR.$LANG->line('quicksave_note'), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($enable_qucksave_versioning == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('enable_qucksave_versioning', 'y', $selected).$DSP->nbs(3);

              $selected = ($enable_qucksave_versioning == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('enable_qucksave_versioning', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             
        // Max Revisions
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

		$x = $DSP->qdiv('itemWrapper', $DSP->input_checkbox('clear_versioning_data', 'y', 0).' '.$DSP->qspan('highlight', $LANG->line('clear_versioning_data')));

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('max_revisions')).BR.$LANG->line('max_revisions_note'), '50%').
              $DSP->table_qcell($style, $DSP->input_text('max_revisions', $max_revisions, '30', '4', 'input', '100%').$x, '50%').
              $DSP->tr_c();


        $r .= $DSP->table_c();
        $r .= $DSP->div_c();
                

        /** ---------------------------
        /**  Notifications
        /** ---------------------------*/
          
		$r .= '<div id="not_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='not2' colspan='2'>";
		$r .= NBS.$LANG->line('notification_settings').$DSP->td_c();     
        $r .= $DSP->tr_c();

        // Weblog notify?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('weblog_notify')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($weblog_notify == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('weblog_notify', 'y', $selected).$DSP->nbs(3);

              $selected = ($weblog_notify == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('weblog_notify', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();

        // Weblog emails
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_notify_emails')).BR.$LANG->line('comment_notify_note'), '50%').
              $DSP->table_qcell($style, $DSP->input_text('weblog_notify_emails', $weblog_notify_emails, '50', '255', 'input', '100%'), '50%').
              $DSP->tr_c();


        // Comment notify?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_notify')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_notify == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_notify', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_notify == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_notify', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();

        // Comment emails
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_notify_emails', 'comment_notify_emails')).BR.$LANG->line('comment_notify_note'), '50%').
              $DSP->table_qcell($style, $DSP->input_text('comment_notify_emails', $comment_notify_emails, '50', '255', 'input', '100%'), '50%').
              $DSP->tr_c();
              
        // Comment notify authors?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_notify_authors')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_notify_authors == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_notify_authors', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_notify_authors == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_notify_authors', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
              

        $r .= $DSP->table_c();
        $r .= $DSP->div_c();
        
        /** ---------------------------
        /**  Comment posting settings
        /** ---------------------------*/
          
		$r .= '<div id="comm_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='comm2' colspan='2'>";
		$r .= NBS.$LANG->line('comment_prefs').$DSP->td_c();     
        $r .= $DSP->tr_c();

        // Are comments enabled?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_system_enabled')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_system_enabled == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_system_enabled', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_system_enabled == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_system_enabled', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();



        // Require membership for comment posting?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_require_membership')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_require_membership == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_require_membership', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_require_membership == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_require_membership', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             
             
        // Use captcha
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_use_captcha')).$DSP->qdiv('default', $LANG->line('captcha_explanation')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_use_captcha == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_use_captcha', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_use_captcha == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_use_captcha', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();


        // Require email address for comment posting?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_require_email')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_require_email == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_require_email', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_require_email == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_require_email', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             
             
        // Require comment moderation?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_moderate')).$DSP->qdiv('itemWrapper', $LANG->line('comment_moderate_exp')), '50%')
      
             .$DSP->td($style, '50%');
        
              $selected = ($comment_moderate == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_moderate', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_moderate == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_moderate', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             

        // Max characters in comments

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
        
        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_max_chars', 'comment_max_chars')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('comment_max_chars', $comment_max_chars, '10', '5', 'input', '50px'), '50%').
              $DSP->tr_c();
              
              
        // Comment Timelock

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
        
        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('comment_timelock', 'comment_timelock')).$DSP->qdiv('itemWrapper', $LANG->line('comment_timelock_desc')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('comment_timelock', $comment_timelock, '10', '5', 'input', '50px'), '50%').
              $DSP->tr_c();
  
        // Comment expiration

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
        
        if ($comment_expiration == '')
        	$comment_expiration = 0;
        	
		$x = $DSP->qdiv('itemWrapper', $DSP->input_checkbox('apply_expiration_to_existing', 'y', 0).' '.$LANG->line('update_existing_comments'));
        
        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('comment_expiration', 'comment_expiration')).$DSP->qdiv('itemWrapper', $LANG->line('comment_expiration_desc')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('comment_expiration', $comment_expiration, '10', '5', 'input', '50px').$x, '50%').
              $DSP->tr_c();
              
  
              
        // Default comment text formatting
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_text_formatting')), '50%');
              
        $r .= $DSP->td($style, '50%').
              $DSP->input_select_header('comment_text_formatting');

        $selected = ($comment_text_formatting == 'none') ? 1 : '';
            
        $r .= $DSP->input_select_option('none', $LANG->line('none'), $selected);

        $selected = ($comment_text_formatting == 'xhtml') ? 1 : '';
        
        $r .= $DSP->input_select_option('xhtml', $LANG->line('xhtml'), $selected);
                
        $selected = ($comment_text_formatting == 'br') ? 1 : '';
        
        $r .= $DSP->input_select_option('br', $LANG->line('auto_br'), $selected);
                
        $r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();

              
        // HTML formatting
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_html_formatting')), '50%');
              
        $r .= $DSP->td($style, '50%').
              $DSP->input_select_header('comment_html_formatting');

        $selected = ($comment_html_formatting == 'none') ? 1 : '';
            
        $r .= $DSP->input_select_option('none', $LANG->line('convert_to_entities'), $selected);

        $selected = ($comment_html_formatting == 'safe') ? 1 : '';
        
        $r .= $DSP->input_select_option('safe', $LANG->line('allow_safe_html'), $selected);
                
        $selected = ($comment_html_formatting == 'all') ? 1 : '';
        
        $r .= $DSP->input_select_option('all', $LANG->line('allow_all_html_not_recommended'), $selected);
                
        $r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();


        // Allow IMG URLs?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('comment_allow_img_urls')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_allow_img_urls == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_allow_img_urls', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_allow_img_urls == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_allow_img_urls', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();


        // Auto link URLs?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('auto_link_urls')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($comment_auto_link_urls == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('comment_auto_link_urls', 'y', $selected).$DSP->nbs(3);

              $selected = ($comment_auto_link_urls == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('comment_auto_link_urls', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();


        $r .= $DSP->table_c();
        $r .= $DSP->div_c();
            
        /** ---------------------------
        /**  Trackbacks
        /** ---------------------------*/
          
		$r .= '<div id="tb_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='tb2' colspan='2'>";
		$r .= NBS.$LANG->line('trackback_settings').$DSP->td_c();     
        $r .= $DSP->tr_c();
              
        // Are trackbacks enabled?
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('trackback_system_enabled')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($trackback_system_enabled == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('trackback_system_enabled', 'y', $selected).$DSP->nbs(3);

              $selected = ($trackback_system_enabled == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('trackback_system_enabled', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();

                          
        // Add trackback RDF to your pages
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('enable_trackbacks')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($enable_trackbacks == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('enable_trackbacks', 'y', $selected).$DSP->nbs(3);

              $selected = ($enable_trackbacks == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('enable_trackbacks', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
        
		// Use Entry ID or URL Title?
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('trackback_use_url_title')).$DSP->qdiv('default', $LANG->line('trackback_use_url_title_exp')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($trackback_use_url_title == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('trackback_use_url_title', 'y', $selected).$DSP->nbs(3);

              $selected = ($trackback_use_url_title == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('trackback_use_url_title', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();

        // Max trackback hits per hour
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('trackback_max_hits', 'trackback_max_hits')), '50%').
              $DSP->table_qcell($style, $DSP->input_text('trackback_max_hits', $trackback_max_hits, '15', '16', 'input', '80px'), '50%').
              $DSP->tr_c();
             
        // Use captcha
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('trackback_use_captcha')).$DSP->qdiv('default', $LANG->line('trackback_captcha_exp')), '50%')
             .$DSP->td($style, '50%');
        
              $selected = ($trackback_use_captcha == 'y') ? 1 : '';
                
        $r .= $LANG->line('yes')
             .$DSP->input_radio('trackback_use_captcha', 'y', $selected).$DSP->nbs(3);

              $selected = ($trackback_use_captcha == 'n') ? 1 : '';

        $r .= $LANG->line('no')
             .$DSP->input_radio('trackback_use_captcha', 'n', $selected)
             .$DSP->td_c()
             .$DSP->tr_c();
             
             
        // Default field for trackback
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('trackback_field')), '50%');
            
        $r .= $DSP->td($style, '50%');
              
        $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = '".$DB->escape_str($field_group)."'");              
              
        if ($query->num_rows == 0)
        {
            $r .= '<b>'.$LANG->line('no_field_group_selected').'</b>';
        }
        else
        {
            $r .= $DSP->input_select_header('trackback_field');
        
            foreach ($query->result as $row)
            {
                $selected = ($trackback_field == $row['field_id']) ? 1 : '';
                    
                $r .= $DSP->input_select_option($row['field_id'], $row['field_label'], $selected);
            }
            
            $r .= $DSP->input_select_footer();
        } 
        
        $r .=  $DSP->td_c()
        	  .$DSP->tr_c();

		$r .= $DSP->table_c();
        $r .= $DSP->div_c();

        
        /** ---------------------------
        /**  Publish Page customization
        /** ---------------------------*/
          
		$r .= '<div id="cust_on" style="display: none; padding:0; margin: 0;">';
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        $r .= $DSP->tr();
        		
		$r .= "<td class='tableHeadingAlt' id='cust2' colspan='2'>";
		$r .= NBS.$LANG->line('publish_page_customization').$DSP->td_c();     
        $r .= $DSP->tr_c();
    
        // show_url_title
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_url_title')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_url_title', 'y', ($show_url_title == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_url_title', 'n', ($show_url_title == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();
              

        // show_button_cluster
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_button_cluster')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_button_cluster', 'y', ($show_button_cluster == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_button_cluster', 'n', ($show_button_cluster == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();


        // show_trackback_field
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_trackback_field')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_trackback_field', 'y', ($show_trackback_field == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_trackback_field', 'n', ($show_trackback_field == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();


        // show_author_menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_author_menu')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_author_menu', 'y', ($show_author_menu == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_author_menu', 'n', ($show_author_menu == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();


        // show_status_menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_status_menu')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_status_menu', 'y', ($show_status_menu == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_status_menu', 'n', ($show_status_menu == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();


        // show_date_menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_date_menu')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_date_menu', 'y', ($show_date_menu == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_date_menu', 'n', ($show_date_menu == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();

        // show_options_cluster
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_options_cluster')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_options_cluster', 'y', ($show_options_cluster == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_options_cluster', 'n', ($show_options_cluster == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();

        // show_ping_cluster
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_ping_cluster')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_ping_cluster', 'y', ($show_ping_cluster == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_ping_cluster', 'n', ($show_ping_cluster == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();


        // show_categories_menu
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_categories_menu')), '50%')
             .$DSP->td($style, '50%');
        $r .= $LANG->line('yes')
             .$DSP->input_radio('show_categories_menu', 'y', ($show_categories_menu == 'y') ? 1 : '').$DSP->nbs(3);
        $r .= $LANG->line('no')
             .$DSP->input_radio('show_categories_menu', 'n', ($show_categories_menu == 'n') ? 1 : '')
             .$DSP->td_c()
             .$DSP->tr_c();
             
             
        // show_forum_cluster
        
        if ($PREFS->ini('forum_is_installed') == "y")
        {
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
	
			$r .= $DSP->tr()
				 .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_forum_cluster')), '50%')
				 .$DSP->td($style, '50%');
			$r .= $LANG->line('yes')
				 .$DSP->input_radio('show_forum_cluster', 'y', ($show_forum_cluster == 'y') ? 1 : '').$DSP->nbs(3);
			$r .= $LANG->line('no')
				 .$DSP->input_radio('show_forum_cluster', 'n', ($show_forum_cluster == 'n') ? 1 : '')
				 .$DSP->td_c()
				 .$DSP->tr_c();
		}
		
		// show_pages_cluster
        
        if ($PREFS->ini('site_pages') !== FALSE)
        {
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;
	
			$r .= $DSP->tr()
				 .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_pages_cluster')), '50%')
				 .$DSP->td($style, '50%');
			$r .= $LANG->line('yes')
				 .$DSP->input_radio('show_pages_cluster', 'y', ($show_pages_cluster == 'y') ? 1 : '').$DSP->nbs(3);
			$r .= $LANG->line('no')
				 .$DSP->input_radio('show_pages_cluster', 'n', ($show_pages_cluster == 'n') ? 1 : '')
				 .$DSP->td_c()
				 .$DSP->tr_c();
		}
		
		// Show All Cluster
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

		$r .= $DSP->tr()
			 .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('show_show_all_cluster')), '50%')
			 .$DSP->td($style, '50%');
		$r .= $LANG->line('yes')
			 .$DSP->input_radio('show_show_all_cluster', 'y', ($show_show_all_cluster == 'y') ? 1 : '').$DSP->nbs(3);
		$r .= $LANG->line('no')
			 .$DSP->input_radio('show_show_all_cluster', 'n', ($show_show_all_cluster == 'n') ? 1 : '')
			 .$DSP->td_c()
			 .$DSP->tr_c();
		
		// default_entry_title
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('default_entry_title')), '50%')
             .$DSP->td($style, '50%')
             .$DSP->input_text('default_entry_title', $default_entry_title, '50', '255', 'input', '100%')
        	 .$DSP->td_c()
             .$DSP->tr_c();
             
		// url_title_prefix
        
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo' ;

        $r .= $DSP->tr()
             .$DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('url_title_prefix')).$DSP->nbs(2).'-'.$DSP->nbs(2).$LANG->line('single_word_no_spaces'))
             .$DSP->td($style, '50%')
             .$DSP->input_text('url_title_prefix', $url_title_prefix, '50', '255', 'input', '100%')
        	 .$DSP->td_c()
             .$DSP->tr_c();

        $r .= $DSP->table_c();
        $r .= $DSP->div_c();
                         
                   
        // BOTTOM SECTION OF PAGE
                

        // Text: * Indicates required fields
          
        $r .= $DSP->div('itemWrapper');

        $r .= $DSP->qdiv('itemWrapper', $DSP->required(1));
    
        // "Submit" button

        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')).NBS.$DSP->input_submit($LANG->line('update_and_return'),'return'));

        $r.= $DSP->div_c().$DSP->form_close();
        
        /** ----------------------------------
        /**  Create Our All Encompassing Table of Weblog Goodness
        /** ----------------------------------*/
        
        $DSP->body .= $DSP->table('', '0', '', '100%');
		
		// List of our various preference areas begins here
		
		$areas = array("weblog"		=> "weblog_base_setup",
					   "paths"		=> "paths",
					   "admin"		=> "default_settings",
					   "posting"	=> "weblog_settings",
					   "versioning"	=> "versioning",
					   "not"		=> "notification_settings",
					   "comm"		=> "comment_prefs",
					   "tb"			=> "trackback_settings",
					   "cust"		=> "publish_page_customization");
					  
		$menu = '';
		
		foreach($areas as $area => $area_lang)
		{
			$menu .= $DSP->qdiv('navPad', ' <span id="'.$area.'_pointer">&#8226; '.$DSP->anchor("#", $LANG->line($area_lang), 'onclick="showHideMenu(\''.$area.'\');"').'</span>');
		}
		
		$first_text = 	$DSP->div('tableHeadingAlt')
						.	$blog_title
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
        
        
        $DSP->title = $LANG->line('edit_weblog_prefs');
        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list', $LANG->line('weblog_management'))).
        			  $DSP->crumb_item($LANG->line('edit_weblog_prefs'));
    }
    /* END */
  
  
  
    /** -----------------------------------------------------------
    /**  Weblog group preferences form
    /** -----------------------------------------------------------*/
    // This function displays the form used to edit the various 
    // preferences and group assignements for a given weblog
    //-----------------------------------------------------------

    function edit_group_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
   
		// Set default values
        
        $i = 0;
        
        // If we don't have the $weblog_id variable, bail out.
        
        if ( ! $weblog_id = $IN->GBL('weblog_id'))
        {
            return FALSE;
        }
            
        $query = $DB->query("SELECT * FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str($weblog_id)."'");
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
                        
        // Build the output
        
        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_preferences'));
        $DSP->body .= $DSP->input_hidden('weblog_id', $weblog_id);
        $DSP->body .= $DSP->input_hidden('blog_name',  $blog_name);
        $DSP->body .= $DSP->input_hidden('blog_title', $blog_title);
         $DSP->body .= $DSP->input_hidden('return', '1');
        
		$DSP->body .= $DSP->table('tableBorder', '0', '', '100%');
		$DSP->body .= $DSP->tr().
			  $DSP->td('tableHeading', '100%').$LANG->line('edit_group_prefs').$DSP->td_c().
			  $DSP->tr_c();
				
        $DSP->body .= $DSP->tr().
              $DSP->table_qcell('tableCellTwo', $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $blog_title)), '50%').
              $DSP->tr_c().
			  $DSP->table_c();
			  
        $DSP->body .= $DSP->table('tableBorder', '0', '', '100%');
        $DSP->body .= $DSP->tr();
        $DSP->body .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('preference'));
        $DSP->body .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('value'));
        $DSP->body .= $DSP->tr_c();
        
        
        // GROUP FIELDS
        
        $g = '';

        // Category group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
           
        $query = $DB->query("SELECT group_id, group_name FROM exp_category_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
        
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('category_group')), '40%', 'top');
        
        $g .= $DSP->td($style).
              $DSP->input_select_header('cat_group[]', ($query->num_rows > 0) ? 'y' : '');
        
        $selected = (empty($cat_group)) ? 1 : '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);

        if ($query->num_rows > 0)
        {
        	$cat_group = explode('|', $cat_group);
        
            foreach ($query->result as $row)
            {
                $selected = (in_array($row['group_id'], $cat_group)) ? 1 : '';
                                        
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name'], $selected);
            }
        }
        
        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
    
        // Status group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
        $query = $DB->query("SELECT group_id, group_name FROM exp_status_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
    
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('status_group')));
              
        $g .= $DSP->td($style).
              $DSP->input_select_header('status_group');
        
        $selected = '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);
    
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $selected = ($status_group == $row['group_id']) ? 1 : '';
                                        
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name'], $selected);
            }
        }

        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
        
            
        // Field group select list
        
        $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
        $query = $DB->query("SELECT group_id, group_name FROM exp_field_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_name");
    
        $g .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_group')));
        
        $g .= $DSP->td($style).
              $DSP->input_select_header('field_group');
        
        $selected = '';

        $g .= $DSP->input_select_option('', $LANG->line('none'), $selected);
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $selected = ($field_group == $row['group_id']) ? 1 : '';
                                        
                $g .= $DSP->input_select_option($row['group_id'], $row['group_name'], $selected);
            }
        }

        $g .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
              
                
        $DSP->body .= $g;
        
        // BOTTOM SECTION OF PAGE
                
        // Table end
        
        $DSP->body .= $DSP->table_c();
          
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')));

        $DSP->body .= $DSP->form_close();         
        
        $DSP->title = $LANG->line('edit_group_prefs');
        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list', $LANG->line('weblog_management'))).
        			  $DSP->crumb_item($LANG->line('edit_group_prefs'));
    }
    /* END */
    
    
    
    /** -----------------------------------------------------------
    /**  Delete weblog confirm
    /** -----------------------------------------------------------*/
    // Warning message shown when you try to delete a weblog
    //-----------------------------------------------------------

    function delete_weblog_conf()
    {  
        global $DSP, $IN, $DB, $LANG;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }  

        if ( ! $weblog_id = $IN->GBL('weblog_id'))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT blog_title FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str($weblog_id)."'");
        
        $DSP->title = $LANG->line('delete_weblog');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list', $LANG->line('weblog_administration'))).
        			  $DSP->crumb_item($LANG->line('delete_weblog'));

		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete'.AMP.'weblog_id='.$weblog_id,
												'heading'	=> 'delete_weblog',
												'message'	=> 'delete_weblog_confirmation',
												'item'		=> $query->row['blog_title'],
												'extra'		=> '',
												'hidden'	=> array('weblog_id' => $weblog_id)
											)
										);	
    }
    /* END */
    
   
   
    /** -----------------------------------------------------------
    /**  Delete weblog
    /** -----------------------------------------------------------*/
    // This function deletes a given weblog
    //-----------------------------------------------------------

    function delete_weblog()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $PREFS, $STAT;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
  
        if ( ! $weblog_id = $IN->GBL('weblog_id'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($weblog_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT blog_title FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str($weblog_id)."'");
        
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
        $blog_title = $query->row['blog_title'];
        
        $LOG->log_action($LANG->line('weblog_deleted').NBS.NBS.$blog_title); 
        
        $query = $DB->query("SELECT entry_id, author_id FROM exp_weblog_titles WHERE weblog_id = '{$weblog_id}'");
        
		$entries = array();
		$authors = array();
		
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
				$entries[] = $row['entry_id'];
				$authors[] = $row['author_id'];
            }
        }

		$authors = array_unique($authors);
		
		// gather related fields, we use this later if needed
		$fquery = $DB->query("SELECT field_id FROM exp_weblog_fields WHERE field_type = 'rel'");
		
		$DB->query("DELETE FROM exp_weblog_data WHERE weblog_id = '{$weblog_id}'");
		
        $DB->query("DELETE FROM exp_weblog_titles WHERE weblog_id = '{$weblog_id}'");  
                
        $DB->query("DELETE FROM exp_weblogs WHERE weblog_id = '{$weblog_id}'");

		$DB->query("DELETE FROM exp_comments WHERE weblog_id = '{$weblog_id}'");
		
		$DB->query("DELETE FROM exp_trackbacks WHERE weblog_id = '{$weblog_id}'");

        /** ----------------------------------------
		/**  Delete Pages Stored in Database For Entries
		/** ----------------------------------------*/
		
		if (sizeof($entries) > 0 && $PREFS->ini('site_pages') !== FALSE)
		{
			$pages = $PREFS->ini('site_pages');
			
			if (sizeof($pages) > 0)
			{
				foreach($entries as $entry_id)
				{
					unset($pages['uris'][$entry_id]);
					unset($pages['templates'][$entry_id]);
				}
				
				$PREFS->core_ini['site_pages'] = $pages;
				
				$DB->query($DB->update_string('exp_sites', 
											  array('site_pages' => addslashes(serialize($pages))),
											  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
			}
		}
		
		/** ---------------------------------------
		/**  Clear relationships and catagories
		/** ---------------------------------------*/
		
		if (! empty($entries))
		{
			$ENTRY_IDS = implode(',', $entries);

			// Clear the exp_category_posts table
			$DB->query("DELETE FROM exp_category_posts WHERE entry_id IN ({$ENTRY_IDS})");

			// Now it's relationships turn
			$DB->query("DELETE FROM exp_relationships WHERE rel_parent_id IN ({$ENTRY_IDS})");

			$child_results = $DB->query("SELECT rel_id FROM exp_relationships WHERE rel_child_id IN ({$ENTRY_IDS})");

			if ($child_results->num_rows > 0)
			{
				// We have children, so we need to do a bit of housekeeping
				// so parent entries don't continue to try to reference them
				$cids = array();

				foreach ($child_results->result as $row)
				{
					$cids[] = $row['rel_id'];
				}

				$CIDS = implode(',', $cids);

				foreach($fquery->result as $row)
				{
					 $DB->query($DB->update_string('exp_weblog_data', array('field_id_'.$row['field_id'] => '0'), 'field_id_'.$row['field_id']." IN ({$CIDS})"));
				}					
			}

			$DB->query("DELETE FROM exp_relationships WHERE rel_child_id IN ({$ENTRY_IDS})");	
		}
		
		/** ---------------------------------------
		/**  Update author stats
		/** ---------------------------------------*/
		
		foreach ($authors as $author_id)
		{
			$query = $DB->query("SELECT count(entry_id) AS count FROM exp_weblog_titles WHERE author_id = '{$author_id}'");
			$total_entries = $query->row['count'];
			
			$query = $DB->query("SELECT count(comment_id) AS count FROM exp_comments WHERE author_id = '{$author_id}'");
			$total_comments = $query->row['count'];
			
			$DB->query($DB->update_string('exp_members', array( 'total_entries' => $total_entries,'total_comments' => $total_comments), "member_id = '{$author_id}'"));   			
		}
		
		/** ---------------------------------------
		/**  McFly, update the stats!
		/** ---------------------------------------*/
		
		$STAT->update_weblog_stats();
        $STAT->update_comment_stats('', '', TRUE);
        $STAT->update_trackback_stats();

        return $this->weblog_overview($DSP->qspan('success', $LANG->line('weblog_deleted').NBS.NBS.'<b>'.$blog_title.'</b>'));
    }
    /* END */
   
   
   
   
   
   
//=====================================================================
//  CATEGORY ADMINISTRATION FUNCTIONS
//=====================================================================
   
   
   
    /** -----------------------------------------------------------
    /**  Category overview page
    /** -----------------------------------------------------------*/
    // This function displays the "categories" page, accessed
    // via the "admin" tab
    //-----------------------------------------------------------

    function category_overview($message = '')
    {
        global $LANG, $DSP, $SESS, $DB, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
             
        $DSP->title  = $LANG->line('category_groups');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			   $DSP->crumb_item($LANG->line('category_groups'));

		$DSP->right_crumb($LANG->line('create_new_category_group'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor');

        // Fetch category groups
        
        $sql = "SELECT group_id, group_name FROM exp_category_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND exp_category_groups.is_user_blog = 'n' ORDER BY group_name";
                
        $query = $DB->query($sql);
              
        if ($query->num_rows == 0)
        {   
			$DSP->body  = $DSP->heading($LANG->line('categories')); 
			
			$DSP->body .= stripslashes($message);
			$DSP->body .= $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($LANG->line('no_category_group_message'), 5));
        		$DSP->body .= $DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor', $LANG->line('create_new_category_group')));
			$DSP->body .= $DSP->div_c();
        
			return;
        }     

		// Fetch count of custom fields per group
		
		$cfcount = array();
		$cfq = $DB->query("SELECT COUNT(*) AS count, group_id FROM exp_category_fields GROUP BY group_id");

		if ($cfq->num_rows > 0)
		{
			foreach ($cfq->result as $row)
			{
				$cfcount[$row['group_id']] = $row['count'];
			}
		}

        $r  = '';      
             
       	if ($message != '')
       		$r .= stripslashes($message);
              
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '6').
              $LANG->line('categories').
              $DSP->td_c().
              $DSP->tr_c();
        
        $i = 0;
        
        foreach($query->result as $row)
        {
        	// It is not efficient to put this query in the loop.
        	// Originally I did it with a join above, but there is a bug on OS X Server
        	// that I couldn't find a work-around for.  So... query in the loop it is.
        
        	$res = $DB->query("SELECT COUNT(*) AS count FROM exp_categories WHERE group_id = '".$row['group_id']."'");
        	$count = $res->row['count'];
        	
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
            

            $r .= $DSP->tr().
                  $DSP->td($style, '5%').
                  $DSP->qspan('defaultBold', $row['group_id']).
                  $DSP->td_c().
                  $DSP->td($style, '30%').
                  $DSP->qspan('defaultBold', $row['group_name']).
                  $DSP->td_c();
            
            $r .= $DSP->table_qcell($style,
                  '('.$count.')'.$DSP->nbs(2).         
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('add_edit_categories')
                              ));
            

            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('edit_group_name')
                              ));


            $r .= $DSP->table_qcell($style,
				  '('.((isset($cfcount[$row['group_id']])) ? $cfcount[$row['group_id']] : '0').')'.$DSP->nbs(2).
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_field_group_edit'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('manage_custom_fields')
                              ));
			
			
            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_group_del_conf'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('delete_group')
                              )).
                  $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();
        $DSP->body = $r;
    }
    /* END */
   

	/** -----------------------------------------------------------
	/**  Category Field Group Form
	/** -----------------------------------------------------------*/
	// This function displays the field group management form
	// and allows you to delete, modify, or create a
	// category custom field
	//-----------------------------------------------------------
	
	function category_field_group_manager($group_id = '', $msg = FALSE)
	{
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
         $message = ($msg == TRUE) ? $DSP->qdiv('success', $LANG->line('preferences_updated')) : '';

        if ($group_id == '')
        {
            if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
            {
                return FALSE;
            }
        }
        elseif ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        // Fetch the name of the category group
        
        $query = $DB->query("SELECT group_name FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");

        $r  = $DSP->qdiv('tableHeading', $LANG->line('category_group').':'.$DSP->nbs(2).$query->row['group_name']);
        
        if ($message != '')
        {
			$r .= $DSP->qdiv('box', stripslashes($message));
		}
     
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeadingAlt', '40%', '1').$LANG->line('field_label').$DSP->td_c().
              $DSP->td('tableHeadingAlt', '20%', '1').$LANG->line('field_name').$DSP->td_c().
              $DSP->td('tableHeadingAlt', '40%', '2').$LANG->line('field_type').$DSP->td_c().
              $DSP->tr_c();

        $query = $DB->query("SELECT field_id, field_name, field_label, field_type, field_order FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY field_order");
        
  
        if ($query->num_rows == 0)
        {
            $r .= $DSP->tr().
                  $DSP->td('tableCellTwo', '', 3).
                  '<b>'.$LANG->line('no_field_groups').'</br>'.
                  $DSP->td_c().
                  $DSP->tr_c();
        }  

        $i = 0;
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

                $r .= $DSP->tr();

                $r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_cat_field'.AMP.'group_id='.$group_id.AMP.'field_id='.$row['field_id'], $row['field_order'].$DSP->nbs(2).$row['field_label'])));      
                
                $r .= $DSP->table_qcell($style, $row['field_name']);
                
                
                switch ($row['field_type'])
                {
                	case 'text' :  $field_type = $LANG->line('text_input');
                		break;
                	case 'textarea' :  $field_type = $LANG->line('textarea');
                		break;
                	case 'select' :  $field_type = $LANG->line('select_list');
                		break;
                }

                $r .= $DSP->table_qcell($style, $field_type);
                $r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_cat_field_conf'.AMP.'group_id='.$group_id.AMP.'field_id='.$row['field_id'], $LANG->line('delete')));      
                $r .= $DSP->tr_c();
            }
        }
        
        $r .= $DSP->table_c();
       
        if ($query->num_rows > 0)
        {
            $r .= $DSP->qdiv('paddedWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_cat_field_order'.AMP.'group_id='.$group_id, $LANG->line('edit_field_order')));
        }

        $DSP->title = $LANG->line('custom_category_fields');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($LANG->line('custom_category_fields'));

		$DSP->right_crumb($LANG->line('create_new_custom_field'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_cat_field'.AMP.'group_id='.$group_id);
        $DSP->body  = $r;
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Edit Category Field Order Form
	/** -----------------------------------------------------------*/
	// This function displays the form to modify the field display
	// order in the control panel
	//-----------------------------------------------------------
	
	function edit_category_field_order_form()
	{
		global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT field_id, field_label, field_order FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY field_order");
        
        if ($query->num_rows == 0)
        {
            return FALSE;
        }
                
 		
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=ud_cat_field_order'));
        $r .= $DSP->input_hidden('group_id', $group_id);
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%');
		$r .= $DSP->tr()
			.$DSP->td('tableHeading', '', '2').$LANG->line('edit_field_order').$DSP->td_c()
			.$DSP->tr_c();

        foreach ($query->result as $row)
        {
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell('tableCellOne', $row['field_label'], '40%');
            $r .= $DSP->table_qcell('tableCellOne', $DSP->input_text('field_id_'.$row['field_id'], $row['field_order'], '4', '3', 'input', '30px'));      
            $r .= $DSP->tr_c();
        }
        $r .= $DSP->table_c();

        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('edit_field_order');
        $DSP->crumb =
                    $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
                    $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_field_group_edit'.AMP.'group_id='.$group_id, $LANG->line('custom_category_fields'))).
                    $DSP->crumb_item($LANG->line('edit_field_order'));

        $DSP->body  = $r;
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Update category field order
	/** -----------------------------------------------------------*/
	// This function updates the field order for category custom fields
	//-----------------------------------------------------------
	
	function update_category_field_order()
	{
        global $DSP, $IN, $DB, $LANG, $PREFS;


        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        unset($_POST['group_id']);

        foreach ($_POST as $key => $val)
        {
			// remove 'field_id_' from key
			$field_id = substr($key, 9);
			
            $DB->query("UPDATE exp_category_fields SET field_order = '".$DB->escape_str($val)."'
						WHERE group_id = '".$DB->escape_str($group_id)."' AND field_id = '".$DB->escape_str($field_id)."'");    
        }
        
        return $this->category_field_group_manager($group_id);		
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Edit Category Custom Field
	/** -----------------------------------------------------------*/
	// This function displays the form to edit or create
	// a category custom field
	//-----------------------------------------------------------
	
	function edit_category_field_form()
	{
		global $DSP, $IN, $DB, $REGX, $LANG, $EXT, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
            return $DSP->no_access_message();
		}
		
        $type = ($field_id = $IN->GBL('field_id')) ? 'edit' : 'new';

        $total_fields = '';

		/** ---------------------------------------
		/**  Validate the group_id and field_id
		/** ---------------------------------------*/
		
        if ($type == 'new')
        {
			$query = $DB->query("SELECT group_id FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."'");
  			
 			$total_fields = $query->num_rows + 1;
			$field_id = '';
			
			if ($query->num_rows > 0)
			{
				$group_id = $query->row['group_id'];				
			}
			else
			{
				// if there are no existing category fields yet for this group, this allows us to still validate the group_id
				$gquery = $DB->query("SELECT COUNT(*) AS count FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				
				if ($gquery->row['count'] != 1)
				{
					return $DSP->no_access_message();
				}
			}
        }
		else
		{
			$query = $DB->query("SELECT field_id, group_id FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' AND field_id = '".$DB->escape_str($field_id)."'");
			
			if ($query->num_rows == 0)
			{
				return FALSE;
			}
			
			$field_id = $query->row['field_id'];
			$group_id = $query->row['group_id'];
		}

        $DB->fetch_fields = TRUE;

        $query = $DB->query("SELECT f.field_id, f.field_name, f.site_id, f.field_label, f.field_type, f.field_default_fmt, f.field_show_fmt,
							f.field_list_items, f.field_maxl, f.field_ta_rows, f.field_text_direction, f.field_required, f.field_order,
							g.group_name
							FROM exp_category_fields AS f, exp_category_groups AS g
							WHERE f.group_id = g.group_id
							AND g.group_id = '{$group_id}'
							AND f.field_id = '{$field_id}'");

        $data = array();

        if ($query->num_rows == 0)
        {
            foreach ($query->fields as $f)
            {
            	$data[$f] = '';
                $$f = '';
            }
        }
        else
        {        
            foreach ($query->row as $key => $val)
            {
            	$data[$key] = $val;
                $$key = $val;
            }
        }

		// Adjust $group_name for new custom fields
		// as we display this later

		if ($group_name == '')
		{
			$query = $DB->query("SELECT group_name FROM exp_category_groups WHERE group_id = '{$group_id}'");

			if ($query->num_rows > 0)
			{
				$group_name = $query->row['group_name'];
			}
		}

        // JavaScript Stuff

        $val = $LANG->line('field_val');

        $r = "";

		ob_start();
		?>        
        <script type="text/javascript"> 
        <!--

        function showhide_element(id)
        {         
        	if (id == 'text')
        	{
				document.getElementById('text_block').style.display = "block";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('select_block').style.display = "none";
        	}
			else if (id == 'textarea')
			{
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "block";
				document.getElementById('select_block').style.display = "none";			
			}
        	else if (id == 'select')
        	{
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('select_block').style.display = "block";
        	}
        }

		function format_update_block(oldfmt, newfmt)
		{
			if (oldfmt == newfmt)
			{
				document.getElementById('update_formatting').style.display = "none";
				document.field_form.update_formatting.checked=false;	
			}
			else
			{
				document.getElementById('update_formatting').style.display = "block";
			}

		}
		
		-->
		</script>
		<?php

		$js = ob_get_contents();
		ob_end_clean();

		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_js' hook.
		/*  - Allows modifying or adding onto Category Field JS
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_js') === TRUE)
			{
				$js = $EXT->call_extension('publish_admin_edit_cat_field_js', $data, $js);
			}
		/*
		/* -------------------------------------------*/

		$r .= $js;
		$r .= NL.NL;
        $typopts  = '';

        // Form declaration

        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_cat_fields', 'name' => 'field_form'));
        $r .= $DSP->input_hidden('group_id', $group_id);
        $r .= ($type == 'edit') ? $DSP->input_hidden('field_id', $field_id) : '';

        $title = ($type == 'edit') ? 'edit_cat_field' : 'create_new_cat_field';

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').$LANG->line($title).NBS.NBS."(".$LANG->line('category_group').": {$group_name})".$DSP->td_c().
              $DSP->tr_c();

        $i = 0;

        /** ---------------------------------
        /**  Field name
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('field_name', 'field_name')).$DSP->qdiv('itemWrapper', $LANG->line('field_name_cont')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_name', $field_name, '20', '60', 'input', '260px'), '50%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Field Label
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('field_label', 'field_label')).$DSP->qdiv('', $LANG->line('cat_field_label_info')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_label', $field_label, '20', '60', 'input', '260px'), '50%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Field type
        /** ---------------------------------*/

        $sel_1 = ''; $sel_2 = ''; $sel_3 = '';
        $text_js = ($type == 'edit') ? 'none' : 'block';
        $textarea_js = 'none';
        $select_js = 'none';
        $select_opt_js = 'none';

        switch ($field_type)
        {
            case 'text'     : $sel_1 = 1; $text_js = 'block';
                break;
            case 'textarea' : $sel_2 = 1; $textarea_js = 'block';
                break;
            case 'select'   : $sel_3 = 1; $select_js = 'block'; $select_opt_js = 'block';
                break;
        }

        /** ---------------------------------
        /**  Create the pull-down menu
        /** ---------------------------------*/

        $typemenu = "<select name='field_type' class='select' onchange='showhide_element(this.options[this.selectedIndex].value);' >".NL;
		$typemenu .= $DSP->input_select_option('text', 		$LANG->line('text_input'),	$sel_1)
					.$DSP->input_select_option('textarea', 	$LANG->line('textarea'),  	$sel_2)
					.$DSP->input_select_option('select', 	$LANG->line('select_list'), $sel_3);

		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_type_pulldown' hook.
		/*  - Allows modifying or adding onto Category Field Type Menu Pulldown
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_type_pulldown') === TRUE)
			{
				$typemenu = $EXT->call_extension('publish_admin_edit_cat_field_type_pulldown', $data, $typemenu);
			}
		/*
		/* -------------------------------------------*/

		$typemenu .= $DSP->input_select_footer();

		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_type_cellone' hook.
		/*  - Allows modifying or adding onto Category Field Type - First Table Cell
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_type_cellone') === TRUE)
			{
				$typemenu = $EXT->call_extension('publish_admin_edit_cat_field_type_cellone', $data, $typemenu);
			}
		/*
		/* -------------------------------------------*/

        /** ---------------------------------
        /**  Select List Field
        /** ---------------------------------*/

		$typopts .= '<div id="select_block" style="display: '.$select_js.'; padding:0; margin:5px 0 0 0;">';		
		$typopts .= '<div id="populate_block_man" style="padding:0; margin:5px 0 0 0;">';		
		$typopts .= $DSP->qdiv('defaultBold', $LANG->line('field_list_items', 'field_list_items')).$DSP->qdiv('default', $LANG->line('field_list_instructions')).$DSP->input_textarea('field_list_items', $field_list_items, 10, 'textarea', '400px');
		$typopts .= $DSP->div_c();
		$typopts .= $DSP->div_c();

		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_type_celltwo' hook.
		/*  - Allows modifying or adding onto Category Field Type - Second Table Cell
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_type_celltwo') === TRUE)
			{
				$typopts = $EXT->call_extension('publish_admin_edit_cat_field_type_celltwo', $data, $typopts);
			}
		/*
		/* -------------------------------------------*/

        /** ---------------------------------
        /**  Max-length Field
        /** ---------------------------------*/

		if ($type != 'edit')
			$field_maxl = 128;

		$z  = '<div id="text_block" style="display: '.$text_js.'; padding:0; margin:5px 0 0 0;">';		
		$z .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_text('field_maxl', $field_maxl, '4', '3', 'input', '30px').NBS.$LANG->line('field_max_length', 'field_maxl'));
		$z .= $DSP->div_c();

        /** ---------------------------------
        /**  Textarea Row Field
        /** ---------------------------------*/

		if ($type != 'edit')
			$field_ta_rows = 6;

		$z .= '<div id="textarea_block" style="display: '.$textarea_js.'; padding:0; margin:5px 0 0 0;">';		
		$z .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_text('field_ta_rows', $field_ta_rows, '4', '3', 'input', '30px').NBS.$LANG->line('textarea_rows', 'field_ta_rows'));
		$z .= $DSP->div_c();

        /** ---------------------------------
        /**  Generate the above items
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('field_type'))).$typemenu.$z, '50%', 'top');
		$r .= $DSP->table_qcell($style, $typopts, '50%');
		$r .= $DSP->tr_c();	


        /** ---------------------------------
        /**  Show field formatting?
        /** ---------------------------------*/

        if ($field_show_fmt == '') 
        	$field_show_fmt = 'y';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

        /** ---------------------------------
        /**  Field Formatting
        /** ---------------------------------*/


		if ($field_name != '')
        	$typemenu = "<select name='field_default_fmt' class='select' onchange='format_update_block(this.options[this.selectedIndex].value, \"".$field_default_fmt."\");' >".NL;
		else
			$typemenu  = $DSP->input_select_header('field_default_fmt');

		$typemenu .= $DSP->input_select_option('none', $LANG->line('none'), ($field_default_fmt == 'none') ? 1 : '');
		
		// Fetch formatting plugins
		
		$list = $this->fetch_plugins();
		
		foreach($list as $val)
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
			
			$selected = ($field_default_fmt == $val) ? 1 : '';

			$typemenu .= $DSP->input_select_option($val, $name, $selected);
		}

		$typemenu  .= $DSP->input_select_footer();

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$y  = '<div id="formatting_block" style="padding:0; margin:0 0 0 0;">';
		$y .= $typemenu;
		$y .= $DSP->qdiv('itemWrapper', $DSP->input_radio('field_show_fmt', 'y', ($field_show_fmt == 'y') ? 1 : '').$LANG->line('show_formatting_buttons').BR.$DSP->input_radio('field_show_fmt', 'n', ($field_show_fmt == 'n') ? 1 : '').$LANG->line('hide_formatting_buttons'));
		$y .= $DSP->div_c();

		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_format' hook.
		/*  - Allows modifying or adding onto Default Text Formatting Cell
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_format') === TRUE)
			{
				$y = $EXT->call_extension('publish_admin_edit_cat_field_format', $data, $y);
			}
		/*
		/* -------------------------------------------*/

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('deft_field_formatting')), '50%', 'top');
		$r .= $DSP->table_qcell($style, $y, '50%');
		$r .= $DSP->tr_c();	

		/** ---------------------------------
        /**  Text Direction
        /** ---------------------------------*/

        if ($field_text_direction == '') $field_text_direction = 'ltr';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('text_direction')), '50%');
		$r .= $DSP->table_qcell($style, 
										'<div id="direction_available" style="padding:0; margin:0 0 0 0;">'.
										$LANG->line('ltr').$DSP->nbs().
										$DSP->input_radio('field_text_direction', 'ltr', ($field_text_direction == 'ltr') ? 1 : '').
										$DSP->nbs(3).
										$LANG->line('rtl').$DSP->nbs().
										$DSP->input_radio('field_text_direction', 'rtl', ($field_text_direction == 'rtl') ? 1 : '').
										$DSP->div_c());
		$r .= $DSP->tr_c();			


        /** ---------------------------------
        /**  Is field required?
        /** ---------------------------------*/

        if ($field_required == '') $field_required = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_required')), '50%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('field_required', 'y', ($field_required == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('field_required', 'n', ($field_required == 'n') ? 1 : ''), '50%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Field order
        /** ---------------------------------*/

        if ($type == 'new')
            $field_order = $total_fields;

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_order', 'field_order')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_order', $field_order, '4', '3', 'input', '30px'), '50%');
		$r .= $DSP->tr_c();		


		/* -------------------------------------------
		/* 'publish_admin_edit_cat_field_extra_row' hook.
		/*  - Allows modifying or adding onto the Category Field settings table
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('publish_admin_edit_cat_field_extra_row') === TRUE)
			{
				$r = $EXT->call_extension('publish_admin_edit_cat_field_extra_row', $data, $r);
			}
		/*
		/* -------------------------------------------*/

		$r .= $DSP->table_c();

        $r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemWrapper', $DSP->required(1));

		if ($field_name != '')
		{
			$r .= '<div id="update_formatting" style="display: none; padding:0; margin:0 0 0 0;">';
			$r .= $DSP->div('itemWrapper');
			$r .= $DSP->qdiv('alert', $LANG->line('fmt_has_changed'));
			$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('update_formatting', 'y', 0).' '.$DSP->qspan('alert', $LANG->line('update_existing_cat_fields')));
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
		}		


        if ($type == 'edit')        
            $r .= $DSP->input_submit($LANG->line('update'));
        else
            $r .= $DSP->input_submit($LANG->line('submit'));

        $r .= $DSP->div_c();


        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('custom_category_fields');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($LANG->line('custom_category_fields'));
        $DSP->body  = $r;
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Update Category Fields
	/** -----------------------------------------------------------*/
	// This function updates or creates category fields
	//-----------------------------------------------------------
	
	function update_category_fields()
	{
	    global $DSP, $FNS, $IN, $DB, $REGX, $LANG, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }        

        // Are we editing or creating?

        $edit = (($field_id = $IN->GBL('field_id')) !== FALSE AND is_numeric($field_id)) ? TRUE : FALSE;
		
		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
            return $DSP->no_access_message();
		}

        // Check for required fields

        $error = array();

        if ($_POST['field_name'] == '')
        {
            $error[] = $LANG->line('no_field_name');
        }
        else
        {
        	// Is the field one of the reserved words?

			if (in_array($_POST['field_name'], $DSP->invalid_custom_field_names()))
        	{
            	$error[] = $LANG->line('reserved_word');
        	}
        }

        if ($_POST['field_label'] == '')
        {
            $error[] = $LANG->line('no_field_label');
        }

        // Does field name contain invalid characters?

        if ( ! preg_match("#^[a-z0-9\_\-]+$#i", $_POST['field_name'])) 
        {
            $error[] = $LANG->line('invalid_characters');
        }

        // Field name must be unique across category groups
		
		if ($edit == FALSE)
		{
	        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_category_fields WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND field_name = '".$DB->escape_str($_POST['field_name'])."'");

	        if ($query->row['count'] > 0)
	        {
	            $error[] = $LANG->line('duplicate_field_name');
	        }			
		}

        // Are there errors to display?

        if (count($error) > 0)
        {
            $str = '';

            foreach ($error as $msg)
            {
                $str .= $msg.BR;
            }

            return $DSP->error_message($str);
        }

        if ($_POST['field_list_items'] != '')
        {
            $_POST['field_list_items'] = $REGX->convert_quotes($_POST['field_list_items']);
        }

		if ( ! in_array($_POST['field_type'], array('text', 'textarea', 'select')))
		{
			$_POST['field_text_direction'] = 'ltr';
		}

        // Construct the query based on whether we are updating or inserting

        if ($edit === TRUE)
        {
			// validate field id
			
			$query = $DB->query("SELECT field_id FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' AND field_id = '".$DB->escape_str($field_id)."'");
			
			if ($query->num_rows == 0)
			{
				return FALSE;
			}

        	// Update the formatting for all existing entries
            if (isset($_POST['update_formatting']))
			{
				$DB->query("UPDATE exp_category_field_data SET field_ft_{$field_id} = '".$DB->escape_str($_POST['field_default_fmt'])."'");
			}

            unset($_POST['group_id']);
            unset($_POST['update_formatting']);

			$DB->query($DB->update_string('exp_category_fields', $_POST, "field_id='".$field_id."'"));              
        }
        else
        {
    		unset($_POST['update_formatting']);
			
            if ($_POST['field_order'] == 0 || $_POST['field_order'] == '')
            {
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."'");

				$_POST['field_order'] = $query->num_rows + 1;
            }

            $_POST['site_id'] = $PREFS->ini('site_id');

			$DB->query($DB->insert_string('exp_category_fields', $_POST));
			
			$insert_id = $DB->insert_id;
								
			$DB->query("ALTER TABLE exp_category_field_data ADD COLUMN field_id_{$insert_id} text NOT NULL");
			$DB->query("ALTER TABLE exp_category_field_data ADD COLUMN field_ft_{$insert_id} varchar(40) NOT NULL default 'none'");
			$DB->query("UPDATE exp_category_field_data SET field_ft_{$insert_id} = '".$DB->escape_str($_POST['field_default_fmt'])."'");
       }

		$FNS->clear_caching('all', '', TRUE);

        return $this->category_field_group_manager($group_id, $edit);	
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Delete Category Custom Field Confirmation
	/** -----------------------------------------------------------*/
	// This function displays a confirmation form for deleting
	// a category custom field
	//-----------------------------------------------------------
	
	function delete_category_field_confirm()
	{
		global $DSP, $IN, $DB, $LANG, $REGX;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($field_id = $IN->GBL('field_id')) === FALSE OR ! is_numeric($field_id))
        {
            return FALSE;
        }
		
		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return FALSE;
        }
		
        $query = $DB->query("SELECT field_label FROM exp_category_fields
							WHERE field_id = '".$DB->escape_str($field_id)."'
							AND group_id = '".$DB->escape_str($group_id)."'");
        
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
        $DSP->title = $LANG->line('delete_field');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_cat_field'));        
        
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_cat_field'.AMP.'group_id='.$group_id.AMP.'field_id='.$field_id,
												'heading'	=> 'delete_cat_field',
												'message'	=> 'delete_cat_field_confirmation',
												'item'		=> $query->row['field_label'],
												'extra'		=> '',
												'hidden'	=> array('field_id' => $field_id)
											)
										);
	}
	/* END */
	
	
	
	/** -----------------------------------------------------------
	/**  Delete Category Field
	/** -----------------------------------------------------------*/
	// This function deletes a category field
	//-----------------------------------------------------------
	
	function delete_category_field()
	{
		global $DSP, $FNS, $IN, $DB, $LOG, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($field_id = $IN->GBL('field_id', 'POST')) === FALSE OR ! is_numeric($field_id))
        {
            return FALSE;
        }
		
		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return FALSE;
        }
        
		$query = $DB->query("SELECT field_id, field_name FROM exp_category_fields WHERE field_id = '".$DB->escape_str($field_id)."' AND group_id = '".$DB->escape_str($group_id)."'");
		
		if ($query->num_rows == 0)
		{
			return $DSP->no_access_message();
		}
				
		$DB->query("DELETE FROM exp_category_fields WHERE field_id = {$field_id}");
		$DB->query("ALTER TABLE exp_category_field_data DROP COLUMN field_id_{$field_id}");
        $DB->query("ALTER TABLE exp_category_field_data DROP COLUMN field_ft_{$field_id}");

        $LOG->log_action($LANG->line('cat_field_deleted').$DSP->nbs(2).$query->row['field_name']); 
		
		$FNS->clear_caching('all', '', TRUE);
		
        return $this->category_field_group_manager($group_id);
	}
	/* END */
	
	
	
    /** -----------------------------------------------------------
    /**  Category group form
    /** -----------------------------------------------------------*/
    // This function shows the form used to define a new category
    // group or edit an existing one
    //-----------------------------------------------------------

    function edit_category_group_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // Set default values
        
        $edit       = FALSE;
        $group_id   = '';
        $group_name = '';
        $field_html_formatting = 'all';
		$can_edit = array();
		$can_delete = array();
			
        // If we have the group_id variable, it's an edit request, so fetch the category data
        
        if ($group_id = $IN->GBL('group_id'))
        {
            $edit = TRUE;
            
            if ( ! is_numeric($group_id))
            {
            	return FALSE;
            }
            
            $query = $DB->query("SELECT * FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
            
            foreach ($query->row as $key => $val)
            {
                $$key = $val;
            }
			
			// convert our | separated list of privileges into an array
			$can_edit_categories = explode('|', rtrim($can_edit_categories, '|'));
			$can_delete_categories = explode('|', rtrim($can_delete_categories, '|'));
        }
		else
		{
			$can_edit_categories = array();
			$can_delete_categories = array();
		}

		/** ---------------------------------------
		/**  Grab member groups with potential privs
		/** ---------------------------------------*/
		
		$query = $DB->query("SELECT group_id, group_title, can_edit_categories, can_delete_categories
							FROM exp_member_groups
							WHERE group_id NOT IN (1,2,3,4)
							AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
							
		foreach ($query->result as $row)
		{
			if ($row['can_edit_categories'] == 'y')
			{
				$can_edit[$row['group_id']] = $row['group_title'];
			}
			
			if ($row['can_delete_categories'] == 'y')
			{
				$can_delete[$row['group_id']] = $row['group_title'];
			}
		}
        
        $title = ($edit == FALSE) ? $LANG->line('create_new_category_group') : $LANG->line('edit_category_group');
                
        // Build our output
        
        $r = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_cat_group'));
     
        if ($edit == TRUE)
            $r .= $DSP->input_hidden('group_id', $group_id);
        
        
        $r .= $DSP->qdiv('tableHeading', $title);
                
        $r .= $DSP->div('box').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('name_of_category_group', 'group_name'))).
              $DSP->qdiv('itemWrapper', $DSP->input_text('group_name', $group_name, '20', '50', 'input', '300px'));
		
        $r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('cat_field_html_formatting', 'field_html_formatting'))).
			  $DSP->div('itemWrapper').
			  $DSP->input_select_header('field_html_formatting');

        $selected = ($field_html_formatting == 'none') ? 1 : '';

        $r .= $DSP->input_select_option('none', $LANG->line('convert_to_entities'), $selected);

        $selected = ($field_html_formatting == 'safe') ? 1 : '';

        $r .= $DSP->input_select_option('safe', $LANG->line('allow_safe_html'), $selected);

        $selected = ($field_html_formatting == 'all') ? 1 : '';

        $r .= $DSP->input_select_option('all', $LANG->line('allow_all_html'), $selected);

        $r .= $DSP->input_select_footer().
              $DSP->div_c();

		/** ---------------------------------------
		/**  Can Edit Categories drill down
		/** ---------------------------------------*/
		
		if (! empty($can_edit))
		{
			$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('can_edit_categories', 'can_edit_categories'))).
				  $DSP->div('itemWrapper').
				  $DSP->input_select_header('can_edit_categories[]', TRUE, (count($can_edit) > 8) ? 8 : count($can_edit) + 1, '30%;');
				
			foreach ($can_edit as $group_id => $group_title)
			{
		        $selected = (in_array($group_id, $can_edit_categories)) ? 1 : '';

		        $r .= $DSP->input_select_option($group_id, $group_title, $selected);				
			}
			
	        $r .= $DSP->input_select_footer().
	              $DSP->div_c();			
		}
		else
		{
			$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('can_edit_categories'))).
				  $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', str_replace('%x', strtolower($LANG->line('edit')), $LANG->line('no_member_groups_available'))).
				  BR.$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_manager', $LANG->line('member_groups')));
		}
		
		/** ---------------------------------------
		/**  Can Delete Categories drill down
		/** ---------------------------------------*/
		
		if (! empty($can_delete))
		{
			$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('can_delete_categories', 'can_delete_categories'))).
				  $DSP->div('itemWrapper').
				  $DSP->input_select_header('can_delete_categories[]', TRUE, (count($can_delete) > 8) ? 8 : count($can_delete) + 1, '30%;');
				
			foreach ($can_delete as $group_id => $group_title)
			{
		        $selected = (in_array($group_id, $can_delete_categories)) ? 1 : '';

		        $r .= $DSP->input_select_option($group_id, $group_title, $selected);				
			}
			
	        $r .= $DSP->input_select_footer().
	              $DSP->div_c();			
		}
		else
		{
			$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('can_delete_categories'))).
				  $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', str_replace('%x', strtolower($LANG->line('delete')), $LANG->line('no_member_groups_available'))).
				  BR.$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_manager', $LANG->line('member_groups')));
		}
		
		$r .= $DSP->div_c(); // main box
        
        $r .= $DSP->div('itemWrapperTop');
        
        if ($edit == FALSE)
            $r .= $DSP->input_submit($LANG->line('submit'));
        else
            $r .= $DSP->input_submit($LANG->line('update'));
    
		$r .= $DSP->div_c();
		
        $r .= $DSP->form_close();

        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($title);
        $DSP->body  = $r;                

    }
    /* END */
   

    /** -----------------------------------------------------------
    /**  Create/update category group
    /** -----------------------------------------------------------*/
    // This function receives the submission from the group
    // form and stores it in the database
    //-----------------------------------------------------------

    function update_category_group()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // If the $group_id variable is present we are editing an
        // existing group, otherwise we are creating a new one
        
        $edit = (isset($_POST['group_id'])) ? TRUE : FALSE;
                
        if ($_POST['group_name'] == '')
        {
            return $this->edit_category_group_form();
        }
        
        // this should never happen, but protect ourselves!

		if ( ! isset($_POST['field_html_formatting']) OR ! in_array($_POST['field_html_formatting'], array('all', 'none', 'safe')))
		{
			return $this->edit_category_group_form();
		}
		
		// check for bad characters in group name
		
        if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $_POST['group_name']))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }
  
        // Is the group name taken?
        
        $sql = "SELECT count(*) as count FROM exp_category_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_name = '".$DB->escape_str($_POST['group_name'])."'";
        
        if ($edit == TRUE)
        {
            $sql .= " AND group_id != '".$DB->escape_str($_POST['group_id'])."'";
        } 
        
        $query = $DB->query($sql);        
      
        if ($query->row['count'] > 0)
        {
            return $DSP->error_message($LANG->line('taken_category_group_name'));
        }
		
		// make data array of variables from our POST data, so we can ignore
		// some unwanted keys before INSERTing / UPDATEing
		
		$data = array();
		
		foreach ($_POST as $key => $val)
		{
			if (strpos($key, 'can_edit_categories_') !== FALSE OR strpos($key, 'can_delete_categories_') !== FALSE)
			{
				continue;
			}
			
			$data[$key] = $val;
		}
		
		// Set our pipe delimited privileges for edit / delete
		
		if (isset($data['can_edit_categories']) and is_array($data['can_edit_categories']))
		{
			$data['can_edit_categories'] = implode('|', $data['can_edit_categories']);
		}
		else
		{
			$data['can_edit_categories'] = '';
		}
		
		if (isset($data['can_delete_categories']) and is_array($data['can_delete_categories']))
		{
			$data['can_delete_categories'] = implode('|', $data['can_delete_categories']);
		}
		else
		{
			$data['can_delete_categories'] = '';
		}
		
        // Construct the query based on whether we are updating or inserting
   
        if ($edit == FALSE)
        {  
            unset($data['group_id']);
            
            $data['site_id'] = $PREFS->ini('site_id');

            $sql = $DB->insert_string('exp_category_groups', $data);  
            
            $success_msg = $LANG->line('category_group_created');
            
            $crumb = $DSP->crumb_item($LANG->line('new_weblog'));
            
            $LOG->log_action($LANG->line('category_group_created').$DSP->nbs(2).$data['group_name']); 

        }
        else
        {        
            $sql = $DB->update_string('exp_category_groups', $data, 'group_id='.$DB->escape_str($data['group_id']));  
            
            $success_msg = $LANG->line('category_group_updated');
            
            $crumb = $DSP->crumb_item($LANG->line('update'));
        }

        
        $DB->query($sql);
        
        $message  = $DSP->div('box');
        $message .= $DSP->qdiv('defaultBold', $success_msg.NBS.$DSP->qspan('success', $data['group_name']));

        if ($edit == FALSE)
        {            
            $query = $DB->query("SELECT weblog_id from exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n'");
            
            if ($query->num_rows > 0)
            {				
				$message .= $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('assign_group_to_weblog')));
				
				if ($query->num_rows == 1)
				{
					$link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->row['weblog_id'];                
				}
				else
				{
					$link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list';
				}
				
				$message .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.$link, $LANG->line('click_to_assign_group')));
            }
        }
        
        $message .= $DSP->div_c();

        return $this->category_overview($message);
    }
    /* END */
      
    
    /** -----------------------------------------------------------
    /**  Delete category group confirm
    /** -----------------------------------------------------------*/
    // Warning message if you try to delete a category group
    //-----------------------------------------------------------

    function delete_category_group_conf()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT group_name FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        $DSP->title = $LANG->line('delete_group');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_group'));

		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete_group'.AMP.'group_id='.$group_id,
												'heading'	=> 'delete_group',
												'message'	=> 'delete_cat_group_confirmation',
												'item'		=> $query->row['group_name'],
												'extra'		=> '',
												'hidden'	=> array('group_id' => $group_id)
											)
										);	
    }
    /* END */
    
   
   
    /** -----------------------------------------------------------
    /**  Delete categroy group
    /** -----------------------------------------------------------*/
    // This function deletes the category group and all 
    // associated catetgories
    //-----------------------------------------------------------

    function delete_category_group()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $FNS;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT group_name, group_id FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
        $name = $query->row['group_name'];
        $group_id = $query->row['group_id'];
		
		/** ---------------------------------------
		/**  Delete from exp_category_posts
		/** ---------------------------------------*/
		
		$query = $DB->query("SELECT cat_id FROM exp_categories WHERE group_id = {$group_id}");
		
		if ($query->num_rows > 0)
		{
			$cat_ids = array();
			
			foreach ($query->result as $row)
			{
				$cat_ids[] = $row['cat_id'];
			}
			
			$DB->query("DELETE FROM exp_category_posts WHERE cat_id IN (".implode(',', $cat_ids).")");
		}
		
        $DB->query("DELETE FROM exp_category_groups WHERE group_id = {$group_id}");
        
        $DB->query("DELETE FROM exp_categories WHERE group_id = {$group_id}");
        
		/** ---------------------------------------
		/**  Delete category field data
		/** ---------------------------------------*/
		
		$query = $DB->query("SELECT field_id FROM exp_category_fields WHERE group_id = {$group_id}");
		
		if ($query->num_rows > 0)
		{
			$field_ids = array();
			
			foreach ($query->result as $row)
			{
				$field_ids[] = $row['field_id'];
			}
			
			foreach ($field_ids as $field_id)
			{
				$DB->query("ALTER TABLE exp_category_field_data DROP COLUMN field_id_{$field_id}");
		        $DB->query("ALTER TABLE exp_category_field_data DROP COLUMN field_ft_{$field_id}");
			}
		}
		
		$DB->query("DELETE FROM exp_category_fields WHERE group_id = {$group_id}");
		
		$DB->query("DELETE FROM exp_category_field_data WHERE group_id = {$group_id}");
		
        $message = $DSP->qdiv('box', $DSP->qspan('success', $LANG->line('category_group_deleted')).NBS.NBS.'<b>'.$name.'</b>');
        
        $LOG->log_action($LANG->line('category_group_deleted').$DSP->nbs(2).$name);        

		$FNS->clear_caching('all', '', TRUE);
		
        return $this->category_overview($message);
    }
    /* END */
    
    
    
    /** -----------------------------------------------------------
    /**  Category tree
    /** -----------------------------------------------------------*/
    // This function (and the next) create a hierarchical tree
    // of categories. 
    //-----------------------------------------------------------

    function category_tree($type = 'text', $group_id = '', $p_id = '', $sort_order = 'a')
    {  
        global $DSP, $IN, $REGX, $DB, $PREFS, $LANG;
  
        // Fetch category group ID number
      
        if ($group_id == '')
        {        
            if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
            {
            	return FALSE;
            }
        }
        elseif ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
            
        // Fetch category groups
        
		$sql = "SELECT cat_name, cat_id, parent_id FROM exp_categories WHERE group_id = '".$DB->escape_str($group_id)."' ";
                             
		$sql .= ($sort_order == 'a') ? "ORDER BY parent_id, cat_name" : "ORDER BY parent_id, cat_order";
                             
        $query = $DB->query($sql);
              
        if ($query->num_rows == 0)
        {
            return FALSE;
        }     
        
        // Assign the query result to a multi-dimensional array
                    
        foreach($query->result as $row)
        {        
            $cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name']);
        }
        
        if ($type == 'data')
        {
        	return $cat_array;
        }
                
		$up		= '<img src="'.PATH_CP_IMG.'arrow_up.gif" border="0"  width="16" height="16" alt="" title="" />';
		$down	= '<img src="'.PATH_CP_IMG.'arrow_down.gif" border="0"  width="16" height="16" alt="" title="" />';

        // Build our output...
		$can_delete = TRUE;
        if ($IN->GBL('Z') == 1)
        {
			if ($DSP->allowed_group('can_delete_categories') OR $DSP->allowed_group('can_admin_weblogs'))
			{
				$can_delete = TRUE;
			}
			else
			{
				$can_delete = FALSE;
			}
		}
        
        
        $zurl  = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';
                 
        foreach($cat_array as $key => $val) 
        {        
            if (0 == $val['0']) 
            {
            	if ($type == 'table')
            	{
            		if ($can_delete == TRUE)
						$delete = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_category_conf'.AMP.'cat_id='.$key.$zurl, $LANG->line('delete'));
            		else
            			$delete = $LANG->line('delete');
            			
					$this->categories[] =  
					
					$DSP->table_qrow( 'tableCellTwo', 
											array($key,
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.AMP.'order=up'.$zurl, $up).NBS.
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.AMP.'order=down'.$zurl, $down),
												$DSP->qdiv('defaultBold', NBS.$val['1']),              								
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_category'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.$zurl, $LANG->line('edit')),              								
												$delete
										)
									);	
				}
				else
				{				
                	$this->categories[] = $DSP->input_select_option($key, $val['1'], ($key == $p_id) ? '1' : '');
				}					
					
				$this->category_subtree($key, $cat_array, $group_id, $depth=0, $type, $p_id);
				
            }
        } 
    }
    /* END */
    
    
    
    
    /** --------------------------------------
    /**  Category sub-tree
    /** --------------------------------------*/
        
    function category_subtree($cat_id, $cat_array, $group_id, $depth, $type, $p_id)
    {
        global $DSP, $IN, $DB, $REGX, $LANG, $PREFS;

		if ($type == 'table')
		{
			$spcr = '<img src="'.PATH_CP_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
			$indent = $spcr.'<img src="'.PATH_CP_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';
		}
		else
		{	
			$spcr = '&nbsp;';
        		$indent = $spcr.$spcr.$spcr.$spcr;
		}

		$up   = '<img src="'.PATH_CP_IMG.'arrow_up.gif" border="0"  width="16" height="16" alt="" title="" />';
		$down = '<img src="'.PATH_CP_IMG.'arrow_down.gif" border="0"  width="16" height="16" alt="" title="" />';
        
    
        if ($depth == 0)	
        {
            $depth = 1;
        }
        else 
        {	                            
            $indent = str_repeat($spcr, $depth+1).$indent;
            $depth = ($type == 'table') ? $depth + 1 : $depth + 4;
        }
        
		$can_delete = TRUE;
        if ($IN->GBL('Z') == 1)
        {
			if ($DSP->allowed_group('can_delete_categories') OR $DSP->allowed_group('can_admin_weblogs'))
			{
				$can_delete = TRUE;
			}
			else
			{
				$can_delete = FALSE;
			}
		}
        $zurl = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';

        foreach ($cat_array as $key => $val) 
        {				
            if ($cat_id == $val['0']) 
            {
                $pre = ($depth > 2) ? "&nbsp;" : '';
                  
                if ($type == 'table')
                {
            		if ($can_delete == TRUE)
						$delete = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_category_conf'.AMP.'cat_id='.$key.$zurl, $LANG->line('delete'));
            		else
            			$delete = $LANG->line('delete');
                
					$this->categories[] =  
					
					$DSP->table_qrow( 'tableCellTwo', 
											array($key,
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.AMP.'order=up'.$zurl, $up).NBS.
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.AMP.'order=down'.$zurl, $down),
												$DSP->qdiv('defaultBold', $pre.$indent.NBS.$val['1']),
												$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_category'.AMP.'cat_id='.$key.AMP.'group_id='.$group_id.$zurl, $LANG->line('edit')),
												$delete
											)
										);
				}
				else
				{
                	$this->categories[] = $DSP->input_select_option($key, $pre.$indent.NBS.$val['1'], ($key == $p_id) ? '1' : '');
				}
        
				$this->category_subtree($key, $cat_array, $group_id, $depth, $type, $p_id);    
            }
        }
    }
    /* END */
        
  
    /** --------------------------------------
    /**  Change Category Order
    /** --------------------------------------*/
        
	function change_category_order()
	{
		global $DB, $FNS, $DSP, $IN;

        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}

        // Fetch required globals
        
        foreach (array('cat_id', 'group_id', 'order') as $val)
        {
        	if ( ! isset($_GET[$val]))
        	{
				return FALSE;
        	}
        
        	$$val = $_GET[$val];
        }
        
        $zurl = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';
        
        // Return Location
        $return = BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id.$zurl;
        
		// Fetch the parent ID
		
		$query = $DB->query("SELECT parent_id FROM exp_categories WHERE cat_id = '".$DB->escape_str($cat_id)."'");
		$parent_id = $query->row['parent_id'];
		
		// Is the requested category already at the beginning/end of the list?
		
		$dir = ($order == 'up') ? 'asc' : 'desc';
		
		$query = $DB->query("SELECT cat_id FROM exp_categories WHERE group_id = '".$DB->escape_str($group_id)."' AND parent_id = '".$DB->escape_str($parent_id)."' ORDER BY cat_order {$dir} LIMIT 1");
			
		if ($query->row['cat_id'] == $cat_id)
		{
			$FNS->redirect($return);
			exit;        
		}
		
		// Fetch all the categories in the parent
				
		$query = $DB->query("SELECT cat_id, cat_order FROM exp_categories WHERE group_id = '".$DB->escape_str($group_id)."' AND  parent_id = '".$DB->escape_str($parent_id)."' ORDER BY cat_order asc");
		
		// If there is only one category, there is nothing to re-order
		
		if ($query->num_rows <= 1)
		{
			$FNS->redirect($return);
			exit;        
		}
		
		// Assign category ID numbers in an array except the category being shifted.
		// We will also set the position number of the category being shifted, which
		// we'll use in array_shift()
	
		$flag	= '';
		$i		= 1;
		$cats	= array();
		
		foreach ($query->result as $row)
		{
			if ($cat_id == $row['cat_id'])
			{
				$flag = ($order == 'down') ? $i+1 : $i-1;
			}
			else
			{
				$cats[] = $row['cat_id'];				
			}
			
			$i++;
		}
						
		array_splice($cats, ($flag -1), 0, $cat_id);
		
		// Update the category order for all the categories within the given parent
		
		$i = 1;
		
		foreach ($cats as $val)
		{
			$DB->query("UPDATE exp_categories SET cat_order = '$i' WHERE cat_id = '$val'");
			
			$i++;
		}
		
		// Switch to custom order
		
        $DB->query("UPDATE exp_category_groups SET sort_order = 'c' WHERE group_id = '".$DB->escape_str($group_id)."'");

		$FNS->redirect($return);
		exit;        
	}
	/* END */
	
	
        
    /** -----------------------------------------------------------
    /**  Category management page
    /** -----------------------------------------------------------*/
    // This function shows the list of current categories, as
    // well as the form used to submit a new category
    //-----------------------------------------------------------

    function category_manager($group_id = '', $update = FALSE)
    {  
        global $DSP, $IN, $DB, $LANG, $SESS;
                    
        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}

        if ($group_id == '')
        {
            if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
            {
            	return FALSE;
            }
        }
		
		/** ---------------------------------------
		/**  Check discrete privileges
		/** ---------------------------------------*/
		
		if ($IN->GBL('Z') == 1)
		{
			$query = $DB->query("SELECT can_edit_categories FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
			
			if ($query->num_rows == 0)
			{
				return FALSE;
			}
			
			$can_edit = explode('|', rtrim($query->row['can_edit_categories'], '|'));

			if ($SESS->userdata['group_id'] != 1 AND ! in_array($SESS->userdata['group_id'], $can_edit))
			{
				return $DSP->no_access_message();
			}
		}
        
        $zurl = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';

        $query = $DB->query("SELECT group_name, sort_order FROM  exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        $group_name = $query->row['group_name'];
        $sort_order = $query->row['sort_order'];
        
        $r = '';
        
        if ($IN->GBL('Z') == 1)
        {
			$url = BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_category'.AMP.'group_id='.$group_id.$zurl;
			$js = ' onclick="navjump(\''.$url.'\');"  onmouseover="navCrumbOn();" onmouseout="navCrumbOff();" ';
			$r .= $DSP->anchor($url, '<div class="crumblinksR" style="width:300px;margin-left:auto;" id="rcrumb" '.$js.'>'.$DSP->qdiv('itemWrapper', $LANG->line('new_category')).'</div>');        
		}
        
        $r .= $DSP->qdiv('tableHeading', $group_name);
        
        if ($update != FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qspan('success', $LANG->line('category_updated')));
        }
              
        // Fetch the category tree  
        
        $this->category_tree('table', $group_id, '', $sort_order);

        if (count($this->categories) == 0)
        {
            $r .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_category_message')));
        }
        else
        {
			$r .= $DSP->table('tableBorder', '0', '0').
				  $DSP->tr().
				  $DSP->table_qcell('tableHeadingAlt', 'ID', '2%').
				  $DSP->table_qcell('tableHeadingAlt', $LANG->line('order'), '8%').				  
				  $DSP->table_qcell('tableHeadingAlt', $LANG->line('category_name'), '50%').				  
				  $DSP->table_qcell('tableHeadingAlt', $LANG->line('edit'), '20%').
				  $DSP->table_qcell('tableHeadingAlt', $LANG->line('delete'), '20%');
			$r .= $DSP->tr_c();
                                
            foreach ($this->categories as $val)
            {
				$prefix = (strlen($val['0']) == 1) ? NBS.NBS : NBS;
				$r .= $val;
            }
			
			$r .= $DSP->table_c(); 
			
			$r .= $DSP->qdiv('defaultSmall', '');
		
			// Category order
			
			if ($IN->GBL('Z') == FALSE)
			{
				$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=global_cat_order'.AMP.'group_id='.$group_id.$zurl));
				$r .= $DSP->div('box320');
				$r .= $DSP->qdiv('defaultBold', $LANG->line('global_sort_order'));
				$r .= $DSP->div('itemWrapper');
				$r .= $DSP->input_radio('sort_order', 'a', ($sort_order == 'a') ? 1 : '').NBS.$LANG->line('alpha').NBS.NBS.$DSP->input_radio('sort_order', 'c', ($sort_order != 'a') ? 1 : '').NBS.$LANG->line('custom');
				$r .= NBS.NBS.NBS.$DSP->input_submit($LANG->line('update'));			
				$r .= $DSP->div_c();
				$r .= $DSP->div_c();
				$r .= $DSP->form_close();
			}
        }

		// Build category tree for javascript replacement
		
		if ($IN->GBL('Z') == 1)
		{
			if ( ! class_exists('Publish'))
			{
				require PATH_CP.'cp.publish'.EXT;
			}
			
			$PUB = new Publish();
			$PUB->category_tree(($IN->GBL('cat_group') !== FALSE) ? $IN->GBL('cat_group') : $IN->GBL('group_id'), 'new', '', '', ($IN->GBL('integrated') == 'y') ? 'y' : 'n');
			
			$cm = "";
			foreach ($PUB->categories as $val)
			{
				$cm .= $val;
			}			
			$cm = preg_replace("/(\r\n)|(\r)|(\n)/", '', $cm);
			
			$DSP->extra_header = '
			<script type="text/javascript"> 
				
				function update_cats() 
				{
					var str = "'.$cm.'";
					opener.swap_categories(str);
					window.close();
				}
				
			</script>';

			// $r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultCenter', '<a href="javascript:update_cats();"><b>'.$LANG->line('update_publish_cats').'</b></a>')); 
			
			$r .= '<form>';
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultCenter', '<input type="submit" value="'.NBS.$LANG->line('update_publish_cats').NBS.'" onclick="update_cats();"/>'  )); 
			$r .= '</form>';
		}
      
      
       // Assign output data     
              
        $DSP->title = $LANG->line('categories');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($LANG->line('categories'));
		$DSP->right_crumb($LANG->line('new_category'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_category'.AMP.'group_id='.$group_id);
        $DSP->body  = $r;
    }
    /* END */
    
    
    /** -----------------------------------
    /**  Set Global Category Order
    /** -----------------------------------*/
    
    function global_category_order()
    {
        global $DSP, $IN, $DB, $FNS;
          
        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}

		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}
        
        $order = ($_POST['sort_order'] == 'a') ? 'a' : 'c';
        
		$query = $DB->query("SELECT sort_order FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
		if ($order == 'a')
		{
			if ( ! isset($_POST['override']))
			{
				return $this->global_category_order_confirm();
			}
			else
			{
				$this->reorder_cats_alphabetically();
			}
		}
		
		$DB->query("UPDATE exp_category_groups SET sort_order = '$order' WHERE group_id = '".$DB->escape_str($group_id)."'");
        
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id);
		exit;        
    }
    /* END */


    /** --------------------------------------
    /**  Category order change confirm
    /** --------------------------------------*/

    function global_category_order_confirm()
    {
        global $DSP, $IN, $DB, $LANG;
  
        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}

		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}
        
        $DSP->title = $LANG->line('global_sort_order');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id, $LANG->line('categories'))).
        			  $DSP->crumb_item($LANG->line('global_sort_order'));

        $DSP->body = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=global_cat_order'.AMP.'group_id='.$group_id))
                    .$DSP->input_hidden('sort_order', $_POST['sort_order'])
                    .$DSP->input_hidden('override', 1)
                    .$DSP->qdiv('tableHeading', $LANG->line('global_sort_order'))
                    .$DSP->div('box')
                    .$DSP->qdiv('defaultBold', $LANG->line('category_order_confirm_text'))
                    .$DSP->qdiv('alert', BR.$LANG->line('category_sort_warning').BR.BR)
                    .$DSP->div_c()
                    .$DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
                    .$DSP->form_close();
    }
    /* END */


    /** --------------------------------
    /**  Re-order Categories Alphabetically
    /** --------------------------------*/
    
    function reorder_cats_alphabetically()
    {
        global $DSP, $IN, $DB;

        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}

		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}
    	    	
		$data = $this->process_category_group($group_id);
		
		if (count($data) == 0)
		{
			return FALSE;
		}

		foreach($data as $cat_id => $cat_data)
		{
			$DB->query("UPDATE exp_categories SET cat_order = '{$cat_data['1']}' WHERE cat_id = '{$cat_id}'");
		}
    	
    	return TRUE;
    }
    /* END */


    /** --------------------------------
    /**  Process nested category group
    /** --------------------------------*/

    function process_category_group($group_id)
    {  
        global $DB;
        
        $sql = "SELECT cat_name, cat_id, parent_id FROM exp_categories WHERE group_id ='$group_id' ORDER BY parent_id, cat_name";
        
        $query = $DB->query($sql);
              
        if ($query->num_rows == 0)
        {
            return FALSE;
        }
                            
        foreach($query->result as $row)
        {        
            $this->cat_update[$row['cat_id']]  = array($row['parent_id'], '1', $row['cat_name']);
        }
     	
		$order = 0;
    	
        foreach($this->cat_update as $key => $val) 
        {
            if (0 == $val['0'])
            {    
				$order++;
				$this->cat_update[$key]['1'] = $order;
				$this->process_subcategories($key);  // Sends parent_id
            }
        } 
        
        return $this->cat_update;
    }
    /* END */
    
    
    
    /** --------------------------------
    /**  Process Subcategories
    /** --------------------------------*/
        
    function process_subcategories($parent_id)
    {        
        $order = 0;
        
		foreach($this->cat_update as $key => $val) 
        {
            if ($parent_id == $val['0'])
            {
            	$order++;
            	$this->cat_update[$key]['1'] = $order;            	            	            	
				$this->process_subcategories($key);
			}
        }
    }
    /* END */



    /** -----------------------------------------------------------
    /**  New / Edit category form
    /** -----------------------------------------------------------*/
    // This function displays an existing category in a form
    // so that it can be edited.
    //-----------------------------------------------------------

    function edit_category_form()
    {
        global $DSP, $EXT, $IN, $DB, $REGX, $LANG, $PREFS, $SESS;

        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}
		
		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
            return $DSP->no_access_message();
		}
		
		/** ---------------------------------------
		/**  Check discrete privileges
		/** ---------------------------------------*/
		
		if ($IN->GBL('Z') == 1)
		{
			$query = $DB->query("SELECT can_edit_categories FROM exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
			
			if ($query->num_rows == 0)
			{
				return FALSE;
			}
			
			$can_edit = explode('|', rtrim($query->row['can_edit_categories'], '|'));

			if ($SESS->userdata['group_id'] != 1 AND ! in_array($SESS->userdata['group_id'], $can_edit))
			{
				return $DSP->no_access_message();
			}
		}
		
		$cat_id = $IN->GBL('cat_id');
		
		// Get the category sort order for the parent select field later on
		
        $query = $DB->query("SELECT sort_order FROM  exp_category_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        $sort_order = $query->row['sort_order'];

        $default = array('cat_name', 'cat_url_title', 'cat_description', 'cat_image', 'cat_id', 'parent_id');
    
   		if ($cat_id)
 		{
			$query = $DB->query("SELECT cat_id, cat_name, cat_url_title, cat_description, cat_image, group_id, parent_id FROM  exp_categories WHERE cat_id = '$cat_id'");
					
			if ($query->num_rows == 0)
			{
				return $DSP->no_access_message();
			}
			
			foreach ($default as $val)
			{
				$$val = $query->row[$val];
			}
		}
		else
		{
			foreach ($default as $val)
			{
				$$val = '';
			}
		}
		
        // Build our output

        $title = ( ! $cat_id) ? 'new_category' : 'edit_category';

        $zurl = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';
        
        $DSP->title = $LANG->line($title);
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor( BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
                      $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id, $LANG->line('categories'))).
                      $DSP->crumb_item($LANG->line($title));
        
        $word_separator = $PREFS->ini('word_separator') != "dash" ? '_' : '-';

    	/** -------------------------------------
    	/**  Create Foreign Character Conversion JS
    	/** -------------------------------------*/
    	
		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		*/  
			if (isset($EXT->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = $EXT->call_extension('foreign_character_conversion_array');
			}
			else
			{
		    	$foreign_characters = array('223'	=>	"ss", // ß
		    								'224'	=>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
		    								'227'	=>	"ae", '230'	=>	"ae", '228' => "ae",
		    								'231'	=>	"c",
		    								'232'	=>	"e",  // è
		    								'233'	=>	"e",  // é
		    								'234'	=>	"e",  // ê  								
		    								'235'	=>	"e",  // ë
		    								'236'	=>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
		    								'241'	=>	"n",
		    								'242'	=>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
		    								'246'	=>	"oe", // ö
		    								'249'	=>  "u",  '250' =>  "u", '251' => "u",
		    								'252'	=>	"ue", // ü
		    								'255'	=>	"y",
		    								'257'	=>	"aa", 
											'269'	=>	"ch", 
											'275'	=>	"ee", 
											'291'	=>	"gj", 
											'299'	=>	"ii", 
											'311'	=>	"kj", 
											'316'	=>	"lj", 
											'326'	=>	"nj", 
											'353'	=>	"sh", 
											'363'	=>	"uu", 
											'382'	=>	"zh",
											'256'	=>	"aa", 
											'268'	=>	"ch", 
											'274'	=>	"ee", 
											'290'	=>	"gj", 
											'298'	=>	"ii", 
											'310'	=>	"kj", 
											'315'	=>	"lj", 
											'325'	=>	"nj", 
											'352'	=>	"sh", 
											'362'	=>	"uu", 
											'381'	=>	"zh",
		    								);				
			}
		/*
		/* -------------------------------------*/
	
    	$foreign_replace = '';
    	
    	foreach($foreign_characters as $old => $new)
    	{
    		$foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
    	}

        $r = <<<SCRIPPITYDOO
        <script type="text/javascript"> 
        <!--
		/** ------------------------------------
        /**  Live URL Title Function
        /** -------------------------------------*/

        function liveUrlTitle()
        {
			var NewText = document.getElementById("cat_name").value;

			NewText = NewText.toLowerCase();

			var separator = "{$word_separator}";

			// Foreign Character Attempt

			var NewTextTemp = '';
			for(var pos=0; pos<NewText.length; pos++)
			{
				var c = NewText.charCodeAt(pos);

				if (c >= 32 && c < 128)
				{
					NewTextTemp += NewText.charAt(pos);
				}
				else
				{
					{$foreign_replace}
				}
			}

			var multiReg = new RegExp(separator + '{2,}', 'g');
			
			NewText = NewTextTemp;
			
			NewText = NewText.replace('/<(.*?)>/g', '');
			NewText = NewText.replace(/\s+/g, separator);
			NewText = NewText.replace(/\//g, separator);
			NewText = NewText.replace(/[^a-z0-9\-\._]/g,'');
			NewText = NewText.replace(/\+/g, separator);
			NewText = NewText.replace(multiReg, separator);
			NewText = NewText.replace(/-$/g,'');
			NewText = NewText.replace(/_$/g,'');
			NewText = NewText.replace(/^_/g,'');
			NewText = NewText.replace(/^-/g,'');
			NewText = NewText.replace(/\.+$/g,'');

			document.getElementById("cat_url_title").value = NewText;			
	
		}
		-->
		</script>
SCRIPPITYDOO;

        $r .= $DSP->qdiv('tableHeading', $LANG->line($title));
        
        $r .= $DSP->form_open(array('id' => 'category_form', 'action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_category'.$zurl)).
              $DSP->input_hidden('group_id', $group_id);
              
        if ($cat_id)
        {
			$r .= $DSP->input_hidden('cat_id', $cat_id);
        }
        		
		$r .= $DSP->div('box');
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $DSP->required().NBS.$LANG->line('category_name', 'cat_name'))).
              $DSP->input_text('cat_name', $cat_name, '20', '100', 'input', '400px', (( ! $cat_id) ? 'onkeyup="liveUrlTitle();"' : ''), TRUE).
              $DSP->div_c();
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('category_url_title', 'cat_url_title'))).
              $DSP->input_text('cat_url_title', $cat_url_title, '20', '75', 'input', '400px', '', TRUE).
              $DSP->div_c();
		
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('category_description', 'cat_description'))).
			  $DSP->input_textarea('cat_description', $cat_description, 4, 'textarea', '400px').
              $DSP->div_c();
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('defaultBold', $LANG->line('category_image', 'cat_image')).
              $DSP->qdiv('itemWrapper', $DSP->qdiv('', $LANG->line('category_img_blurb'))).
              $DSP->input_text('cat_image', $cat_image, '40', '120', 'input', '400px').
              $DSP->div_c();              
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('category_parent'))).
              $DSP->input_select_header('parent_id').     
              $DSP->input_select_option('0', $LANG->line('none'));
        
        $this->category_tree('list', $group_id, $parent_id, $sort_order);

		foreach ($this->categories as $val)
		{
			$prefix = (strlen($val['0']) == 1) ? NBS.NBS : NBS;
			$r .= $val;
		}

        $r .= $DSP->input_select_footer().
              $DSP->div_c();

		/** ---------------------------------------
		/**  Display custom fields
		/** ---------------------------------------*/
		
		$field_query = $DB->query("SELECT * FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY field_order");
		$data_query = $DB->query("SELECT * FROM exp_category_field_data WHERE cat_id = '".$DB->escape_str($cat_id)."'");
		
		if ($field_query->num_rows > 0)
		{
			$r .= $DSP->qdiv('publishLine', '');
			
			foreach ($field_query->result as $row)
			{
				$convert_ascii = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? TRUE : FALSE;
				
				$r .= $DSP->div('publishRows');
				
				$field_content 	= (! isset($data_query->row['field_id_'.$row['field_id']])) ? '' : $data_query->row['field_id_'.$row['field_id']];
				$field_fmt 		= (! isset($data_query->row['field_ft_'.$row['field_id']])) ? $row['field_default_fmt'] : $data_query->row['field_ft_'.$row['field_id']];
				$text_direction = $row['field_text_direction'];
				$id 			= $row['field_id'];
				
				$width = '100%';

				$required  = ($row['field_required'] == 'n') ? '' : $DSP->required().NBS;     
				$format_sel = '';
				
				if ($row['field_show_fmt'] == 'y')
                {
					$format_sel = $DSP->div('itemWrapper').$DSP->qspan('xhtmlWrapperLight', $LANG->line('formatting'));
					$format_sel .= $DSP->input_select_header('field_ft_'.$id);

					$format_sel .= $DSP->input_select_option('none', $LANG->line('none'), ($field_fmt == 'none') ? 1 : '');

					// Fetch formatting plugins

					$list = $this->fetch_plugins();

					foreach($list as $val)
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

						$selected = ($field_fmt == $val) ? 1 : '';

						$format_sel .= $DSP->input_select_option($val, $name, $selected);
					}

					$format_sel .= $DSP->input_select_footer();
					$format_sel .= $DSP->div_c();
				}
				else
				{
					$r .= $DSP->input_hidden('field_ft_'.$id, $field_fmt);
				}
				
				switch ($row['field_type'])
				{
					case 'textarea'	:
						$rows = ( ! isset($row['field_ta_rows'])) ? '10' : $row['field_ta_rows'];
					
						$r .= $DSP->div('itemWrapper').
				              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $required.$row['field_label'])).
				              $DSP->input_textarea('field_id_'.$id, $field_content,
													$rows, 'textarea', $width, '', $convert_ascii, $text_direction).
				              $format_sel.
							  $DSP->div_c();
						break;
					case 'text'		:
						$r .= $DSP->div('itemWrapper').
				              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $required.$row['field_label'])).
				              $DSP->input_text('field_id_'.$id, $field_content,
												'50', $row['field_maxl'], 'input', $width, '', $convert_ascii, $text_direction).
				              $format_sel.
							  $DSP->div_c();					
						break;
					case 'select'	:
						$r .= $DSP->div('itemWrapper').
				              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $required.$row['field_label']));
						$r .= $DSP->input_select_header('field_id_'.$id);
				
						foreach (explode("\n", trim($row['field_list_items'])) as $v)
						{
							$v = trim($v);
							$selected = ($v == $field_content) ? 1 : '';
							$v = $REGX->form_prep($v);
							$r .= $DSP->input_select_option($v, $v, $selected, "dir='{$text_direction}'");
						}
						
						$r .= $DSP->input_select_footer().
							  $format_sel.
							  $DSP->div_c();
						break;
				}
				
				$r .= $DSP->div_c();
			}
		}
		// end custom fields
				
		$r .= $DSP->div_c();

		/** ---------------------------------------
		/**  Submit Button
		/** ---------------------------------------*/
		
        $r .= $DSP->div('itemWrapperTop');
		$r .= ( ! $cat_id) ? $DSP->input_submit($LANG->line('submit')) : $DSP->input_submit($LANG->line('update'));
		$r .= $DSP->div_c();

        $r .= $DSP->form_close();
		
        $DSP->body = $r;                                  
    }
    /* END */
    
    

    /** -----------------------------------------------------------
    /**  Category submission handler
    /** -----------------------------------------------------------*/
    // This function receives the category information after
    // being submitted from the form (new or edit) and stores
    // the info in the database.
    //-----------------------------------------------------------

    function update_category()
    {
        global $DB, $DSP, $IN, $REGX, $PREFS, $LANG, $EXT, $FNS;
        
        if ($IN->GBL('Z') == 1)
        {
			if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_edit_categories'))
			{
				return $DSP->no_access_message();
			}
		}
		else
		{
			if ( ! $DSP->allowed_group('can_admin_weblogs'))
			{
				return $DSP->no_access_message();
			}
		}
        
		if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
		{
            return $DSP->no_access_message();
		}

        $edit = ( ! $IN->GBL('cat_id', 'POST')) ? FALSE : TRUE;

        
        if ( ! $IN->GBL('cat_name', 'POST'))
        {
            return $this->category_manager($group_id);
        }
        
		/** ---------------------------------------
		/**  Create and validate Category URL Title
		/** ---------------------------------------*/

        if ( ! $IN->GBL('cat_url_title'))
        {
            $_POST['cat_url_title'] = $REGX->create_url_title($_POST['cat_name'], TRUE);
        }
        
		// Kill all the extraneous characters.  
		// We want the URL title to pure alpha text
        
       	$_POST['cat_url_title'] = $REGX->create_url_title($_POST['cat_url_title']);
        
		// Is the cat_url_title a pure number?  If so we show an error.
		
		if (is_numeric($_POST['cat_url_title']))
		{
			return $DSP->error_message($LANG->line('cat_url_title_is_numeric'));
		}
        
		/** -------------------------------------
		/**  Is the Category URL Title empty?  Can't have that
		/** -------------------------------------*/
		
		if (trim($_POST['cat_url_title']) == '')
		{
			return $DSP->error_message($LANG->line('unable_to_create_cat_url_title'));
		}
		
		/** ---------------------------------------
		/**  Cat URL Title must be unique within the group
		/** ---------------------------------------*/
		
		$sql = "SELECT COUNT(*) AS count FROM exp_categories
				WHERE cat_url_title = '".$DB->escape_str($_POST['cat_url_title'])."'
				AND group_id = '".$DB->escape_str($group_id)."' ";
		
		if ($edit === TRUE)
		{
			$sql .= "AND cat_id != '".$DB->escape_str($_POST['cat_id'])."'";
		}
		
		$query = $DB->query($sql);
		
		if ($query->row['count'] > 0)
		{
			return $DSP->error_message($LANG->line('duplicate_cat_url_title'));
		}
		
		
		/** ---------------------------------------
		/**  Finish data prep for insertion
		/** ---------------------------------------*/
		
		if ($PREFS->ini('auto_convert_high_ascii') == 'y')
		{
			$_POST['cat_name'] =  $REGX->ascii_to_entities($_POST['cat_name']);
		}
		
		
		$_POST['cat_name'] = str_replace('<', '&lt;', $_POST['cat_name']);
		$_POST['cat_name'] = str_replace('>', '&gt;', $_POST['cat_name']);

		/** ---------------------------------------
		/**  Pull out custom field data for later insertion
		/** ---------------------------------------*/
		
		$fields = array();

		foreach ($_POST as $key => $val)
		{
			if (strpos($key, 'field') !== FALSE)
			{
				$fields[$key] = $val;
				unset($_POST[$key]);
			}
		}			

		/** ---------------------------------------
		/**  Check for missing required custom fields
		/** ---------------------------------------*/
		
		$query = $DB->query("SELECT field_id, field_label FROM exp_category_fields WHERE group_id = '".$DB->escape_str($group_id)."' AND field_required = 'y'");

		$missing = array();

		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{
				if ( ! isset($fields['field_id_'.$row['field_id']]) OR $fields['field_id_'.$row['field_id']] == '')
				{
					$missing[] = $row['field_label'];
				}
			}
		}
				
		// Are there errors to display?
        
        if (count($missing) > 0)
        {
            $str = $LANG->line('missing_required_fields').BR.BR;
            
            foreach ($missing as $msg)
            {
                $str .= $msg.BR;
            }
            
            return $DSP->error_message($str);
        }

		// -------------------------------------------
        // 'publish_admin_update_category' hook.
        //  - New or Update Category script processing
        //
        	$edata = $EXT->call_extension('publish_admin_update_category');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        $_POST['site_id'] = $PREFS->ini('site_id');

        if ($edit == FALSE)
        {
            $sql = $DB->insert_string('exp_categories', $_POST);     
			$DB->query($sql);
            $update = FALSE;
            
			// need this later for custom fields
			$field_cat_id = $DB->insert_id;
			
			/** ------------------------
			/**  Re-order categories
			/** ------------------------*/
			
			// When a new category is inserted we need to assign it an order.
			// Since the list of categories might have a custom order, all we
			// can really do is position the new category alphabetically.
             
            // First we'll fetch all the categories alphabetically and assign
            // the position of our new category
            
            $query = $DB->query("SELECT cat_id, cat_name FROM exp_categories WHERE group_id = '".$DB->escape_str($group_id)."' AND parent_id = '".$DB->escape_str($_POST['parent_id'])."' ORDER BY cat_name asc");
            
            $position = 0;
            $cat_id = '';
            
            foreach ($query->result as $row)
            {
            	if ($_POST['cat_name'] == $row['cat_name'])
            	{
            		$cat_id = $row['cat_id'];
            		break;
            	}	
            
            	$position++;
            }
            
            // Next we'll fetch the list of categories ordered by the custom order
            // and create an array with the category ID numbers
        		
            $query = $DB->query("SELECT cat_id, cat_name FROM exp_categories WHERE group_id = '".$DB->escape_str($group_id)."' AND parent_id = '".$DB->escape_str($_POST['parent_id'])."' AND cat_id != '".$DB->escape_str($cat_id)."' ORDER BY cat_order");
    
			$cat_array = array();
    
            foreach ($query->result as $row)
            {
				$cat_array[] = $row['cat_id'];
    		}
    		
    		// Now we'll splice in our new category to the array.
    		// Thus, we now have an array in the proper order, with the new
    		// category added in alphabetically
    
			array_splice($cat_array, $position, 0, $cat_id);

			// Lastly, update the whole list
			
			$i = 1;
			foreach ($cat_array as $val)
			{
				$DB->query("UPDATE exp_categories SET cat_order = '$i' WHERE cat_id = '$val'");
				$i++;
			}
        }
        else
        {
        
            if ($_POST['cat_id'] == $_POST['parent_id'])
            {
                $_POST['parent_id'] = 0;  
            }
            
            /** -----------------------------
            /**  Check for parent becoming child of its child...oy!
            /** -----------------------------*/
            
            $query = $DB->query("SELECT parent_id, group_id FROM exp_categories WHERE cat_id = '".$DB->escape_str($IN->GBL('cat_id', 'POST'))."'");
            
            if ($IN->GBL('parent_id') !== 0 && $query->num_rows > 0 && $query->row['parent_id'] !== $IN->GBL('parent_id'))
            {
            	$children  = array();
            	$cat_array = $this->category_tree('data', $query->row['group_id']);
            	
            	foreach($cat_array as $key => $values)
            	{
            		if ($values['0'] == $IN->GBL('cat_id', 'POST'))
            		{
            			$children[] = $key;
            		}
            	}
            	
            	if (sizeof($children) > 0)
            	{
            		if (($key = array_search($IN->GBL('parent_id'), $children)) !== FALSE)
            		{
            			$DB->query($DB->update_string('exp_categories', array('parent_id' => $query->row['parent_id']), "cat_id = '".$children[$key]."'"));
            		}
					
					/** --------------------------
					/**  Find All Descendants
					/** --------------------------*/
					
					else
					{
						while(sizeof($children) > 0)
						{
							$now = array_shift($children);
							
							foreach($cat_array as $key => $values)
							{
								if ($values[0] == $now)
								{
									if ($key == $IN->GBL('parent_id'))
									{
										$DB->query($DB->update_string('exp_categories', array('parent_id' => $query->row['parent_id']), "cat_id = '".$key."'"));
										break 2;
									}
									
									$children[] = $key;
								}
							}
						}
					}
				}
            }
        

            $sql = $DB->update_string(
                                        'exp_categories',
                                        
                                        array(
                                                'cat_name'  		=> $IN->GBL('cat_name', 'POST'),
												'cat_url_title'		=> $IN->GBL('cat_url_title', 'POST'),
                                                'cat_description'	=> $IN->GBL('cat_description', 'POST'),
                                                'cat_image' 		=> $IN->GBL('cat_image', 'POST'),
                                                'parent_id' 		=> $IN->GBL('parent_id', 'POST')
                                             ),
                                            
                                        array(
                                                'cat_id'    => $IN->GBL('cat_id', 'POST'),
                                                'group_id'  => $IN->GBL('group_id', 'POST')            
                                              )                
                                     );    
               
			$DB->query($sql);
			$update = TRUE;
			
			// need this later for custom fields
			$field_cat_id = $IN->GBL('cat_id', 'POST');
        }
		
		/** ---------------------------------------
		/**  Insert / Update Custom Field Data
		/** ---------------------------------------*/
		

		if ($edit == FALSE)
		{
			$fields['site_id'] = $PREFS->ini('site_id');
			$fields['cat_id'] = $field_cat_id;
			$fields['group_id'] = $group_id;
			
			$DB->query($DB->insert_string('exp_category_field_data', $fields));
		}
		elseif (! empty($fields))
		{
			$DB->query($DB->update_string('exp_category_field_data', $fields, array('cat_id' => $field_cat_id)));
		}
		
		$FNS->clear_caching('relationships');

        return $this->category_manager($group_id, $update);
    }
    /* END */
    

    /** -------------------------------------
    /**  Delete category confirm
    /** ------------------------------------*/

	function delete_category_confirm()
	{
        global $DSP, $IN, $DB, $LANG, $SESS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_delete_categories') )
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $cat_id = $IN->GBL('cat_id'))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT cat_name, group_id FROM exp_categories WHERE cat_id = '$cat_id'");
        
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
        /** ---------------------------------------
		/**  Check discrete privileges
		/** ---------------------------------------*/
		
		if ($IN->GBL('Z') == 1)
		{
			$zquery = $DB->query("SELECT can_delete_categories FROM exp_category_groups WHERE group_id = '".$DB->escape_str($query->row['group_id'])."'");
			
			if ($zquery->num_rows == 0)
			{
				return FALSE;
			}
			
			$can_delete = explode('|', rtrim($zquery->row['can_delete_categories'], '|'));

			if ($SESS->userdata['group_id'] != 1 AND ! in_array($SESS->userdata['group_id'], $can_delete))
			{
				return $DSP->no_access_message();
			}
		}
		
        $DSP->title = $LANG->line('delete_category');        
        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor( BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=categories', $LANG->line('category_groups'))).
                      $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$query->row['group_id'], $LANG->line('categories'))).
                      $DSP->crumb_item($LANG->line('delete_category'));
   		
        $zurl = ($IN->GBL('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= ($IN->GBL('cat_group') !== FALSE) ? AMP.'cat_group='.$IN->GBL('cat_group') : '';
        $zurl .= ($IN->GBL('integrated') !== FALSE) ? AMP.'integrated='.$IN->GBL('integrated') : '';

		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_category'.AMP.'group_id='.$query->row['group_id'].AMP.'cat_id='.$cat_id.$zurl,
												'heading'	=> 'delete_category',
												'message'	=> 'delete_category_confirmation',
												'item'		=> $query->row['cat_name'],
												'extra'		=> '',
												'hidden'	=> ''
											)
										);	
	}
	/* END */


    /** -----------------------------------------------------------
    /**  Delete category
    /** -----------------------------------------------------------*/
    // Deletes a cateogory and removes it from all weblog entries
    //-----------------------------------------------------------

    function delete_category()
    {  
        global $DSP, $IN, $DB, $SESS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs') AND ! $DSP->allowed_group('can_delete_categories') )
        {
            return $DSP->no_access_message();
        }

        if ( ! $cat_id = $IN->GBL('cat_id'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($cat_id))
        {
        	return FALSE;
        }

        $query = $DB->query("SELECT group_id FROM exp_categories WHERE cat_id = '".$DB->escape_str($cat_id)."'");
        
		if ($query->num_rows == 0)
		{
			return FALSE;
		}
		
        /** ---------------------------------------
		/**  Check discrete privileges
		/** ---------------------------------------*/
		
		if ($IN->GBL('Z') == 1)
		{
			$zquery = $DB->query("SELECT can_delete_categories FROM exp_category_groups WHERE group_id = '".$DB->escape_str($query->row['group_id'])."'");
			
			if ($zquery->num_rows == 0)
			{
				return FALSE;
			}
			
			$can_delete = explode('|', rtrim($zquery->row['can_delete_categories'], '|'));

			if ($SESS->userdata['group_id'] != 1 AND ! in_array($SESS->userdata['group_id'], $can_delete))
			{
				return $DSP->no_access_message();
			}
		}
		
        $group_id = $query->row['group_id'];
        
        $DB->query("DELETE FROM exp_category_posts WHERE cat_id = '".$DB->escape_str($cat_id)."'");
        
        $DB->query("UPDATE exp_categories SET parent_id = '0' WHERE parent_id = '".$DB->escape_str($cat_id)."' AND group_id = '".$DB->escape_str($group_id)."'");
        
        $DB->query("DELETE FROM exp_categories WHERE cat_id = '".$DB->escape_str($cat_id)."' AND group_id = '".$DB->escape_str($group_id)."'");
        
		$DB->query("DELETE FROM exp_category_field_data WHERE cat_id = '".$DB->escape_str($cat_id)."'");
		
        $this->category_manager($group_id);
    }
    /* END */
    

  

   
//=====================================================================
//  STATUS ADMINISTRATION FUNCTIONS
//=====================================================================

  
  
    /** -----------------------------------------------------------
    /**  Status overview page
    /** -----------------------------------------------------------*/
    // This function show the list of current status groups.
    // It is accessed by clicking "Custom entry statuses"
    // in the "admin" tab
    //-----------------------------------------------------------

    function status_overview($message = '')
    {
        global $LANG, $DSP, $DB, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
                
        $DSP->title  = $LANG->line('status_groups');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			   $DSP->crumb_item($LANG->line('status_groups'));

		$DSP->right_crumb($LANG->line('create_new_status_group'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_group_editor');

        // Fetch category groups
        
        $sql = "SELECT exp_status_groups.group_id, exp_status_groups.group_name,
                COUNT(exp_statuses.group_id) as count 
                FROM exp_status_groups
                LEFT JOIN exp_statuses ON (exp_status_groups.group_id = exp_statuses.group_id)
                WHERE exp_status_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                GROUP BY exp_status_groups.group_id
                ORDER BY exp_status_groups.group_name";        
        
        $query = $DB->query($sql);              
              
        if ($query->num_rows == 0)
        {
			$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('status_groups'));   
			
			if ($message != '')
			{
				$DSP->body .= $DSP->qdiv('box', stripslashes($message));
			}
			$DSP->body .= $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($LANG->line('no_status_group_message'), 5));
        	$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_group_editor', $LANG->line('create_new_status_group')));
			$DSP->body .= $DSP->div_c();
        
			return;
        }     
		
		$r = '';
              
		if ($message != '')
		{
			$r .= $DSP->qdiv('box', stripslashes($message));
		}

        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '4').
              $LANG->line('status_groups').
              $DSP->td_c().
              $DSP->tr_c();
        

        $i = 0;
        
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

            $r .= $DSP->tr();
            
            $r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $row['group_name']));
            

            $r .= $DSP->table_qcell($style, 
                  '('.$row['count'].')'.$DSP->nbs(2).          
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('add_edit_statuses')
                              ));

            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_group_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('edit_status_group_name')
                              ));


            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_group_del_conf'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('delete_status_group')
                              ));

            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();

        $DSP->body  = $r;
    }
    /* END */
  
  

    /** -----------------------------------------------------------
    /**  New/edit status group form
    /** -----------------------------------------------------------*/
    // This function lets you create or edit a status group
    //-----------------------------------------------------------

    function edit_status_group_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG;
      
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        

        // Set default values
        
        $edit       = FALSE;
        $group_id   = '';
        $group_name = '';
        
        // If we have the group_id variable it's an edit request, so fetch the status data
        
        if ($group_id = $IN->GBL('group_id'))
        {
            $edit = TRUE;
            
            if ( ! is_numeric($group_id))
            {
            	return FALSE;
            }
            
            $query = $DB->query("SELECT * FROM exp_status_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
            
            foreach ($query->row as $key => $val)
            {
                $$key = $val;
            }
        }    
        
            
        if ($edit == FALSE)
            $title = $LANG->line('create_new_status_group');
        else
            $title = $LANG->line('edit_status_group');        
        
        // Build our output
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_status_group'));
     
        if ($edit == TRUE)
            $r .= $DSP->input_hidden('group_id', $group_id);
            
        
        $r .= $DSP->qdiv('tableHeading', $title);
                
        $r .= $DSP->div('box').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('name_of_status_group', 'group_name'))).
              $DSP->qdiv('itemWrapper', $DSP->input_text('group_name', $group_name, '20', '50', 'input', '260px'));

		$r .= $DSP->div_c();
                              
        $r .= $DSP->div('itemWrapperTop');
        if ($edit == FALSE)
            $r .= $DSP->input_submit($LANG->line('submit'));
        else
            $r .= $DSP->input_submit($LANG->line('update'));
    
		$r .= $DSP->div_c();
        $r .= $DSP->form_close();

        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=statuses', $LANG->line('status_groups'))).
        			  $DSP->crumb_item($title);
        $DSP->body  = $r;                
    }
    /* END */


    /** -----------------------------------------------------------
    /**  Status group submission handler
    /** -----------------------------------------------------------*/
    // This function receives the submitted status group data
    // and puts it in the database
    //-----------------------------------------------------------

    function update_status_group()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // If the $group_id variable is present we are editing an
        // existing group, otherwise we are creating a new one
        
        $edit = (isset($_POST['group_id'])) ? TRUE : FALSE;
                
        if ($_POST['group_name'] == '')
        {
            return $this->edit_status_group_form();
        }
        
        if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $_POST['group_name']))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }       
  
        // Is the group name taken?
        
        $sql = "SELECT count(*) as count FROM exp_status_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_name = '".$DB->escape_str($_POST['group_name'])."'";
        
        if ($edit == TRUE)
        {
            $sql .= " AND group_id != '".$DB->escape_str($_POST['group_id'])."'";
        } 
        
        $query = $DB->query($sql);        
      
        if ($query->row['count'] > 0)
        {
            return $DSP->error_message($LANG->line('taken_status_group_name'));
        }
   
   
        // Construct the query based on whether we are updating or inserting
   
        if ($edit == FALSE)
        {  
            unset($_POST['group_id']);
			
			$_POST['site_id'] = $PREFS->ini('site_id');
            $DB->query($DB->insert_string('exp_status_groups', $_POST));  
            
            $group_id = $DB->insert_id;
            
			$DB->query("INSERT INTO exp_statuses (status_id, site_id, group_id, status, status_order, highlight) VALUES ('', '".$DB->escape_str($PREFS->ini('site_id'))."', '$group_id', 'open', '1', '$this->status_color_open')");
			$DB->query("INSERT INTO exp_statuses (status_id, site_id, group_id, status, status_order, highlight) VALUES ('', '".$DB->escape_str($PREFS->ini('site_id'))."', '$group_id', 'closed', '2', '$this->status_color_closed')");
            
            $success_msg = $LANG->line('status_group_created');
            
            $crumb = $DSP->crumb_item($LANG->line('new_status'));
            
            $LOG->log_action($LANG->line('status_group_created').$DSP->nbs(2).$_POST['group_name']);            
        }
        else
        {        
            $DB->query($DB->update_string('exp_status_groups', $_POST, 'group_id='.$DB->escape_str($_POST['group_id'])));  
            
            $success_msg = $LANG->line('status_group_updated');
            
            $crumb = $DSP->crumb_item($LANG->line('update'));
        }
        
        
        $message = $DSP->qdiv('itemWrapper', $DSP->qspan('success', $success_msg).NBS.NBS.'<b>'.$_POST['group_name'].'</b>');

        if ($edit == FALSE)
        {            
            $query = $DB->query("SELECT weblog_id from exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n'");
            
            if ($query->num_rows > 0)
            {
                $message .= $DSP->div('itemWrapper').$DSP->span('alert').$LANG->line('assign_group_to_weblog').$DSP->span_c().$DSP->nbs(2);
                
                if ($query->num_rows == 1)
                {
                    $link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->row['weblog_id'];                
                }
                else
                {
                    $link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list';
                }
                
                $message .= $DSP->anchor(BASE.AMP.$link, $LANG->line('click_to_assign_group')).$DSP->div_c();
            }
        }
        
        return $this->status_overview($message);
    }
    /* END */
      

  
    /** -----------------------------------------------------------
    /**  Delete status group confirm
    /** -----------------------------------------------------------*/
    // Warning message shown when you try to delete a status group
    //-----------------------------------------------------------

    function delete_status_group_conf()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }	

        $query = $DB->query("SELECT group_name FROM exp_status_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        
        $DSP->title = $LANG->line('delete_group');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=statuses', $LANG->line('status_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_group'));
        
        
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete_status_group'.AMP.'group_id='.$group_id,
												'heading'	=> 'delete_group',
												'message'	=> 'delete_status_group_confirmation',
												'item'		=> $query->row['group_name'],
												'extra'		=> '',
												'hidden'	=> array('group_id' => $group_id)
											)
										);	
    }
    /* END */
    
   
   
    /** -----------------------------------------------------------
    /**  Delete status group
    /** -----------------------------------------------------------*/
    // This function nukes the status group and associated statuses
    //-----------------------------------------------------------

    function delete_status_group()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT group_name FROM exp_status_groups WHERE group_id = '".$DB->escape_str($group_id)."'");

        $name = $query->row['group_name'];
        
        $DB->query("DELETE FROM exp_status_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        $DB->query("DELETE FROM exp_statuses WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        $LOG->log_action($LANG->line('status_group_deleted').$DSP->nbs(2).$name);        
        
        $message = $DSP->qspan('success', $LANG->line('status_group_deleted')).$DSP->nbs(2).'<b>'.$name.'</b>';

        return $this->status_overview($message);
    }
    /* END */
    
    
    
    /** -----------------------------------------------------------
    /**  Status manager
    /** -----------------------------------------------------------*/
    // This function lets you create/edit statuses
    //-----------------------------------------------------------

    function status_manager($group_id = '', $update = FALSE)
    {  
        global $DSP, $IN, $DB, $SESS, $LANG, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ($group_id == '')
        {
            if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
            {
                return FALSE;
            }
        }
        elseif ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $i = 0;
     
     	;
     
        $r = '';
        
        if ($update == TRUE)
        {
        	if (isset($_GET['group_id']))
        	{
				$r .=  $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('status_created')));
        	}
        	else
        	{
				$r .=  $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('status_updated')));
        	}
        }

        $r .= $DSP->table('', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('', '55%', '', '', 'top');
        
     
        $query = $DB->query("SELECT group_name FROM  exp_status_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
          
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '3').
              $DSP->qspan('altLink', $LANG->line('status_group').':').$DSP->nbs(2).$query->row['group_name'].
              $DSP->td_c().
              $DSP->tr_c();        

        $query = $DB->query("SELECT status_id, status FROM  exp_statuses WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY status_order");
        
        $total = $query->num_rows + 1;
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

				$del = ($row['status'] != 'open' AND $row['status'] != 'closed') ? $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_status_conf'.AMP.'status_id='.$row['status_id'], $LANG->line('delete')) : '--';

				$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];

                $r .= $DSP->tr().
                      $DSP->table_qcell($style, $DSP->qspan('defaultBold', $status_name)).
                      $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_status'.AMP.'status_id='.$row['status_id'], $LANG->line('edit'))).
                      $DSP->table_qcell($style, $del).
                      $DSP->tr_c();
            }
        }
        
        $r .= $DSP->table_c();        
             
        $r .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_status_order'.AMP.'group_id='.$group_id, $LANG->line('change_status_order')));   
        
        $r .= $DSP->td_c().
              $DSP->td('rightCel', '45%', '', '', 'top');
        
        // Build the right side output
        
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_status'.AMP.'group_id='.$group_id)).
              $DSP->input_hidden('group_id', $group_id);

        $r .= $DSP->qdiv('tableHeading', $LANG->line('create_new_status'));
        
        $r .= $DSP->div('box');
        
        $r .= $DSP->qdiv('', $DSP->qdiv('itemWrapper', $LANG->line('status_name', 'status')).$DSP->input_text('status', '', '30', '60', 'input', '260px'));
                
        $r .= $DSP->qdiv('',  $DSP->qdiv('itemWrapper', $LANG->line('status_order', 'status_order')).$DSP->input_text('status_order', $total, '20', '3', 'input', '50px'));

        $r .= $DSP->qdiv('',  $DSP->qdiv('itemWrapper', $LANG->line('highlight', 'highlight')).$DSP->input_text('highlight', '', '20', '30', 'input', '120px'));
                
		$r .= $DSP->div_c();


        if (USER_BLOG == FALSE AND $SESS->userdata['group_id'] == 1)
        {
        
            $query = $DB->query("SELECT group_id, group_title 
            					FROM exp_member_groups 
            					WHERE group_id != '1' 
            					AND group_id != '2' 
            					AND can_access_cp = 'y'
            					AND can_access_publish = 'y'
            					AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            					ORDER BY group_title");
            
            $table_end = TRUE;
            
			if ($query->num_rows == 0)
			{
				$table_end = FALSE;			
			}
			else
			{
				$r .= $DSP->qdiv('itemWrapperTop', $DSP->heading($LANG->line('restrict_status_to_group'), 5));
			
				$r .= $DSP->table('tableBorder', '0', '', '100%').
					  $DSP->tr().
					  $DSP->td('tableHeading', '', '').
					  $LANG->line('member_group').
					  $DSP->td_c().
					  $DSP->td('tableHeading', '', '').
					  $LANG->line('can_edit_status').
					  $DSP->td_c().
					  $DSP->tr_c();
			
			
					$i = 0;
				
				$group = array();
            
				foreach ($query->result as $row)
				{
						$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
						$r .= $DSP->tr().
							  $DSP->td($style, '50%').
							  $row['group_title'].
							  $DSP->td_c().
							  $DSP->td($style, '50%');
							  
						$selected = ( ! isset($group[$row['group_id']])) ? 1 : '';
							
						$r .= $LANG->line('yes').NBS.
							  $DSP->input_radio('access_'.$row['group_id'], 'y', $selected).$DSP->nbs(3);
						   
						$selected = (isset($group[$row['group_id']])) ? 1 : '';
							
						$r .= $LANG->line('no').NBS.
							  $DSP->input_radio('access_'.$row['group_id'], 'n', $selected).$DSP->nbs(3);
			
						$r .= $DSP->td_c()
							 .$DSP->tr_c();
				}        
        	}
        } 
        
		if ($table_end == TRUE)
			$r .= $DSP->table_c(); 
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
		
        $r .= $DSP->form_close();
              
        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
        
  
        $DSP->title = $LANG->line('statuses');
  
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=statuses', $LANG->line('status_groups'))).
        			  $DSP->crumb_item($LANG->line('statuses'));

        $DSP->body  = $r;  
    }
    /* END */
    


    /** -----------------------------------------------------------
    /**  Status submission handler
    /** -----------------------------------------------------------*/
    // This function recieves the submitted status data and
    // inserts it in the database.
    //-----------------------------------------------------------

    function update_status()
    {
        global $DB, $DSP, $LANG, $IN, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        $edit = ( ! $IN->GBL('status_id', 'POST')) ? FALSE : TRUE;
        
      
        if ( ! $IN->GBL('status', 'POST'))
        {
            return $this->status_manager($IN->GBL('group_id', 'POST'));
        }
        
        if ( ! preg_match( "#^([-a-z0-9_\+ ])+$#i", $IN->GBL('status', 'POST')))
        {
            return $DSP->error_message($LANG->line('invalid_status_name'));
        }
        
        
        $data = array(
						'status'     	=> $IN->GBL('status', 'POST'),
						'status_order'	=> (is_numeric($IN->GBL('status_order', 'POST'))) ? $IN->GBL('status_order', 'POST') : 0,
						'highlight'		=> $IN->GBL('highlight', 'POST')
				  	);        
        
        if ($edit == FALSE)
        {
        		$query = $DB->query("SELECT count(*) AS count FROM exp_statuses WHERE status = '".$DB->escape_str($_POST['status'])."' AND group_id = '".$DB->escape_str($_POST['group_id'])."'");
		
			if ($query->row['count'] > 0)
			{
				return $DSP->error_message($LANG->line('duplicate_status_name'));
			}
			
			$data['group_id'] = $_POST['group_id'];
			$data['site_id'] = $PREFS->ini('site_id');
			
			$sql = $DB->insert_string('exp_statuses', $data);     
			
			$DB->query($sql);
			
			$status_id = $DB->insert_id;        	
        }
        else
        {        	
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_statuses WHERE status = '".$DB->escape_str($_POST['status'])."' AND group_id = '".$DB->escape_str($_POST['group_id'])."' AND status_id != '".$DB->escape_str($_POST['status_id'])."'");
		
			if ($query->row['count'] > 0)
			{
				return $DSP->error_message($LANG->line('duplicate_status_name'));
			}
			
			$status_id = $IN->GBL('status_id');
        
            $sql = $DB->update_string(
                                        'exp_statuses', 
										 $data,                                        
                                         array(
                                                'status_id'  => $status_id,
                                                'group_id'   => $IN->GBL('group_id', 'POST')
                                              )
                                     );
			$DB->query($sql);
			
			$DB->query("DELETE FROM exp_status_no_access WHERE status_id = '$status_id'");
			
			// If the status name has changed, we need to update weblog entries with the new status.
			
			if ($_POST['old_status'] != $_POST['status'])
			{
				$query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND status_group = '".$DB->escape_str($_POST['group_id'])."'");
				
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$DB->query("UPDATE exp_weblog_titles SET status = '".$DB->escape_str($_POST['status'])."' 
									WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' 
									AND status = '".$DB->escape_str($_POST['old_status'])."' 
									AND weblog_id = '".$row['weblog_id']."'");
					}
				}
			}                                    
		}
		
		
		// Set access privs
						
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 7) == 'access_' AND $val == 'n')
			{				
				$DB->query("INSERT INTO exp_status_no_access (status_id, member_group) VALUES ('$status_id', '".substr($key, 7)."')");
			}
		}   

        return $this->status_manager($IN->GBL('group_id', 'POST'), TRUE);
    }
    /* END */
   
   
   
    /** -------------------------------------
    /**  Edit status form
    /** -------------------------------------*/

    function edit_status_form()
    {
        global $DSP, $IN, $DB, $REGX, $SESS, $LANG, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
    
        if (($status_id = $IN->GBL('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return FALSE;
        }
    
        $query = $DB->query("SELECT * FROM  exp_statuses WHERE status_id = '$status_id'");
        
        $group_id  		= $query->row['group_id'];
        $status    		= $query->row['status'];
        $status_order	= $query->row['status_order'];
        $color     		= $query->row['highlight'];
        $status_id 		= $query->row['status_id'];

        // Build our output
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_status')).
              $DSP->input_hidden('status_id', $status_id).
              $DSP->input_hidden('old_status',  $status).
              $DSP->input_hidden('group_id',  $group_id);

		

        $r .= $DSP->qdiv('tableHeading', $LANG->line('edit_status'));        
        $r .= $DSP->div('box');
        		
		if ($status == 'open' OR $status == 'closed')
		{
			$r .= $DSP->input_hidden('status', $status);

        	$r .= $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('status_name', 'status').':').NBS.$DSP->qspan('highlight_alt_bold', $LANG->line($status)));
		}
        else
        {
        	$r .= $DSP->qdiv('', $DSP->qdiv('itemWrapper', $LANG->line('status_name', 'status')).$DSP->input_text('status', $status, '30', '60', 'input', '260px'));
        }
        
        $r .= $DSP->qdiv('', $DSP->qdiv('itemWrapper', $LANG->line('status_order', 'status_order')).$DSP->input_text('status_order', $status_order, '20', '3', 'input', '50px'));
          
        $r .= $DSP->qdiv('', $DSP->qdiv('itemWrapper', $LANG->line('highlight', 'highlight')).$DSP->input_text('highlight', $color, '30', '30', 'input', '120px'));
        
        $r .= $DSP->div_c();
        
        if (USER_BLOG == FALSE AND $SESS->userdata['group_id'] == 1)
        {
        
            $query = $DB->query("SELECT group_id, group_title 
            					FROM exp_member_groups 
								WHERE group_id NOT IN (1,2,3,4)
            					AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            					ORDER BY group_title");
           	$table_end = TRUE;
           
			if ($query->num_rows == 0)
			{			
				$table_end = FALSE;	
			}
			else
			{
				$r .= $DSP->qdiv('itemWrapperTop', $DSP->heading($LANG->line('restrict_status_to_group'), 5));
			
				$r .= $DSP->table('tableBorder', '0', '', '100%').
					  $DSP->tr().
					  $DSP->td('tableHeadingAlt', '', '').
					  $LANG->line('member_group').
					  $DSP->td_c().
					  $DSP->td('tableHeadingAlt', '', '').
					  $LANG->line('can_edit_status').
					  $DSP->td_c().
					  $DSP->tr_c();
			
					$i = 0;
				
				$group = array();
			  
				
				$result = $DB->query("SELECT member_group FROM exp_status_no_access WHERE status_id = '$status_id'");
				
				if ($result->num_rows != 0)
				{
					foreach($result->result as $row)
					{
						$group[$row['member_group']] = TRUE;
					}
				}
            
            
				foreach ($query->result as $row)
				{
						$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
						$r .= $DSP->tr().
							  $DSP->td($style, '50%').
							  $row['group_title'].
							  $DSP->td_c().
							  $DSP->td($style, '50%');
							  
						$selected = ( ! isset($group[$row['group_id']])) ? 1 : '';
							
						$r .= $LANG->line('yes').NBS.
							  $DSP->input_radio('access_'.$row['group_id'], 'y', $selected).$DSP->nbs(3);
						   
						$selected = (isset($group[$row['group_id']])) ? 1 : '';
							
						$r .= $LANG->line('no').NBS.
							  $DSP->input_radio('access_'.$row['group_id'], 'n', $selected).$DSP->nbs(3);
			
						$r .= $DSP->td_c()
							 .$DSP->tr_c();
				}        
			
        	}
        }        

		if ($table_end == TRUE)
			$r .= $DSP->table_c(); 

        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
        $r .= $DSP->form_close();
        
        $DSP->title = $LANG->line('edit_status');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=statuses', $LANG->line('status_groups'))).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$group_id, $LANG->line('statuses'))).
        			  $DSP->crumb_item($LANG->line('edit_status'));

        $DSP->body  = $r;
    }
    /* END */
    
 
    /** -------------------------------------------
    /**  Delete status confirm
    /** -------------------------------------------*/

    function delete_status_confirm()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($status_id = $IN->GBL('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT status, group_id FROM exp_statuses WHERE status_id = '$status_id'");
        
        $DSP->title = $LANG->line('delete_status');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$query->row['group_id'], $LANG->line('status_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_status'));
        
        
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_status'.AMP.'status_id='.$status_id,
												'heading'	=> 'delete_status',
												'message'	=> 'delete_status_confirmation',
												'item'		=> $query->row['status'],
												'extra'		=> '',
												'hidden'	=> ''
											)
										);	
    }
    /* END */
 

    /** -------------------------------------------
    /**  Delete status
    /** -------------------------------------------*/

    function delete_status()
    {  
        global $DSP, $IN, $DB, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($status_id = $IN->GBL('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT status, group_id FROM exp_statuses WHERE status_id = '$status_id'");
        
        $group_id = $query->row['group_id'];
        $status   = $query->row['status'];
        
        $query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND status_group = '$group_id'");
        
        if ($query->num_rows > 0)
        {
        	$DB->query("UPDATE exp_weblog_titles SET status = 'closed' WHERE status = '$status' AND weblog_id = '".$DB->escape_str($query->row['weblog_id'])."'");
        }

        if ($status != 'open' AND $status != 'closed')
        {    
        	$DB->query("DELETE FROM exp_statuses WHERE status_id = '$status_id' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id = '".$DB->escape_str($group_id)."'");
        }
        
        $this->status_manager($group_id);
    }
    /* END */
    
    
    
    /** -------------------------------------------
    /**  Edit status order
    /** -------------------------------------------*/
    
	function edit_status_order()
	{    
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT status, status_id, status_order FROM  exp_statuses WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY status_order");
        
        if ($query->num_rows == 0)
        {
            return FALSE;
        }

        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_status_order'));
        $r .= $DSP->input_hidden('group_id', $group_id);
                                
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').
              $LANG->line('change_status_order').
              $DSP->td_c().
              $DSP->tr_c();        
        
        
                
        foreach ($query->result as $row)
        {
        	$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];
        
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell('tableCellOne', $status_name);
            $r .= $DSP->table_qcell('tableCellOne', $DSP->input_text($row['status_id'], $row['status_order'], '4', '3', 'input', '30px'));      
            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();

        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')));

        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('change_status_order');
        
        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=statuses', $LANG->line('status_groups'))).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$group_id, $LANG->line('statuses'))).
        			  $DSP->crumb_item($LANG->line('change_status_order'));
        

        $DSP->body  = $r;
    
    }
    /* END */
    
    
    /** ---------------------------------------
    /**  Update status order
    /** ---------------------------------------*/

    function update_status_order()
    {  
        global $DSP, $IN, $DB, $LANG;

        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return FALSE;
        }
        
        unset($_POST['group_id']);
                
        foreach ($_POST as $key => $val)
        {
            $DB->query("UPDATE exp_statuses SET status_order = '$val' WHERE status_id = '$key'");    
        }
        
        return $this->status_manager($group_id);
    }
    /* END */
        
  
//=====================================================================
//  CUSTOM FIELD FUNCTIONS
//=====================================================================
 
 
 
  
    /** -----------------------------------------------------------
    /**  Custom field overview page
    /** -----------------------------------------------------------*/
    // This function show the "Custom weblog fields" page,
    // accessed via the "admin" tab
    //-----------------------------------------------------------

    function field_overview($message = '')
    {
        global $LANG, $DSP, $DB, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // Fetch field groups
        
        $sql = "SELECT exp_field_groups.group_id, exp_field_groups.group_name,
                COUNT(exp_weblog_fields.group_id) as count 
                FROM exp_field_groups
                LEFT JOIN exp_weblog_fields ON (exp_field_groups.group_id = exp_weblog_fields.group_id)
                WHERE exp_field_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                GROUP BY exp_field_groups.group_id
                ORDER BY exp_field_groups.group_name";        
        
        $query = $DB->query($sql);
              
        if ($query->num_rows == 0)
        {
            return $DSP->set_return_data(
                                        $LANG->line('admin').$DSP->crumb_item($LANG->line('field_groups')), 
                                        
                                        $DSP->heading($LANG->line('field_groups')).
                                        stripslashes($message).
                                        $DSP->qdiv('itemWrapper', $LANG->line('no_field_group_message')).
                                        $DSP->qdiv('itmeWrapper',
                                        $DSP->anchor(
                                                        BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=field_group_editor', 
                                                        $LANG->line('create_new_field_group')
                                                     )),
                                      
                                        $LANG->line('field_groups')
                                      );  
        }     
              
		
		
        $r = '';
        
        if ($message != '')
        {
        	$r .= $DSP->qdiv('box', stripslashes($message));              
        }

        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '4').
              $LANG->line('field_group').
              $DSP->td_c().
              $DSP->tr_c();
        
        $i = 0;  
        
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

            $r .= $DSP->tr().
                  $DSP->table_qcell($style, $DSP->qspan('defaultBold', $row['group_name']));
            
            $r .= $DSP->table_qcell($style,
                  '('.$row['count'].')'.$DSP->nbs(2).
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=field_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('add_edit_fields')
                               ));

            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=field_group_editor'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('edit_field_group_name')
                               ));

            $r .= $DSP->table_qcell($style,
                  $DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_field_group_conf'.AMP.'group_id='.$row['group_id'], 
                                $LANG->line('delete_field_group')
                               ));

            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();

        $DSP->title  = $LANG->line('field_groups');    
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			   $DSP->crumb_item($LANG->line('field_groups'));
        
		$DSP->right_crumb($LANG->line('create_new_field_group'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=field_group_editor');
        $DSP->body = $r;
    }
    /* END */
  


    /** -----------------------------------------------------------
    /**  New/edit field group form
    /** -----------------------------------------------------------*/
    // This function lets you create/edit a custom field group
    //-----------------------------------------------------------

    function edit_field_group_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // Set default values
        
        $edit       = FALSE;
        $group_id   = '';
        $group_name = '';
        
        // If we have the group_id variable it's an edit request, so fetch the field data
        
        if ($group_id = $IN->GBL('group_id'))
        {
            $edit = TRUE;
            
            if ( ! is_numeric($group_id))
            {
            	return FALSE;
            }
            
            $query = $DB->query("SELECT group_name, group_id FROM exp_field_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
            
            foreach ($query->row as $key => $val)
            {
                $$key = $val;
            }
        }   

        if ($edit == FALSE)
            $title = $LANG->line('new_field_group');
        else
            $title = $LANG->line('edit_field_group_name');
        
        // Build our output

        $r = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_field_group'));
     
        if ($edit == TRUE)
            $r .= $DSP->input_hidden('group_id', $group_id);
            
		
            
        $r .= $DSP->qdiv('tableHeading', $title);
            
        $r .= $DSP->div('box');
        $r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('field_group_name', 'group_name')));
        $r .= $DSP->input_text('group_name', $group_name, '20', '50', 'input', '300px');
        $r .= $DSP->br(2);
        $r .= $DSP->div_c();

        $r .= $DSP->div('itemWrapperTop');

        if ($edit == FALSE)
            $r .= $DSP->input_submit($LANG->line('submit'));
        else
            $r .= $DSP->input_submit($LANG->line('update'));
        $r .= $DSP->div_c();

        $r .= $DSP->form_close();

        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
        			  $DSP->crumb_item($title);
        $DSP->body  = $r;
    }
    /* END */
 
 
    /** -----------------------------------------------------------
    /**  Field group submission handler
    /** -----------------------------------------------------------*/
    // This function receives the submitted group data and puts
    // it in the database
    //-----------------------------------------------------------

    function update_field_group()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // If the $group_id variable is present we are editing an
        // existing group, otherwise we are creating a new one
        
        $edit = (isset($_POST['group_id'])) ? TRUE : FALSE;
        
        
        if ($_POST['group_name'] == '')
        {
            return $this->edit_field_group_form();
        }
        
        if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $_POST['group_name']))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }              
  
        // Is the group name taken?
        
        $sql = "SELECT COUNT(*) AS count FROM exp_field_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_name = '".$DB->escape_str($_POST['group_name'])."'";
        
        if ($edit == TRUE)
        {
            $sql .= " AND group_id != '".$DB->escape_str($_POST['group_id'])."'";
        } 
        
        $query = $DB->query($sql);        
      
        if ($query->row['count'] > 0)
        {
            return $DSP->error_message($LANG->line('taken_field_group_name'));
        }
   
        // Construct the query based on whether we are updating or inserting
   
        if ($edit == FALSE)
        {  
            unset($_POST['group_id']);
            
            $_POST['site_id'] = $PREFS->ini('site_id');

            $sql = $DB->insert_string('exp_field_groups', $_POST);  
            
            $success_msg = $LANG->line('field_group_created');
            
            $crumb = $DSP->crumb_item($LANG->line('new_field_group'));
            
            $LOG->log_action($LANG->line('field_group_created').$DSP->nbs(2).$_POST['group_name']);            
        }
        else
        {        
            $sql = $DB->update_string('exp_field_groups', $_POST, 'group_id='.$_POST['group_id']);  
            
            $success_msg = $LANG->line('field_group_updated');
            
            $crumb = $DSP->crumb_item($LANG->line('update'));
        }
        
        $DB->query($sql);
        
        $message = $DSP->qdiv('itemWrapper', $DSP->qspan('success', $success_msg.$DSP->nbs(2)).$DSP->qspan('defaultBold', $_POST['group_name']));

        if ($edit == FALSE)
        {            
            $query = $DB->query("SELECT weblog_id from exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n'");
            
            if ($query->num_rows > 0)
            {
                $message .= $DSP->div('itemWrapper').$DSP->qspan('highlight', $LANG->line('assign_group_to_weblog')).$DSP->nbs(2);
                
                if ($query->num_rows == 1)
                {
                    $link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->row['weblog_id'];                
                }
                else
                {
                    $link = 'C=admin'.AMP.'M=blog_admin'.AMP.'P=blog_list';
                }
                
                $message .= $DSP->anchor(BASE.AMP.$link, $LANG->line('click_to_assign_group'));
                
                $message .= $DSP->div_c();
            }
        }
        

        return $this->field_overview($message);
    }
    /* END */
      
 
 
    
    /** -----------------------------------------------------------
    /**  Delete field group confirm
    /** -----------------------------------------------------------*/
    // Warning message if you try to delete a field group
    //-----------------------------------------------------------

    function delete_field_group_conf()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }

        $query = $DB->query("SELECT group_name FROM exp_field_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        
        $DSP->title = $LANG->line('delete_group');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_group'));
                
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete_field_group'.AMP.'group_id='.$group_id,
												'heading'	=> 'delete_field_group',
												'message'	=> 'delete_field_group_confirmation',
												'item'		=> $query->row['group_name'],
												'extra'		=> '',
												'hidden'	=> array('group_id' => $group_id)
											)
										);	
    }
    /* END */
    
   
   
    /** -------------------------------------------
    /**  Delete field group
    /** -------------------------------------------*/

    function delete_field_group()
    {  
        global $DSP, $FNS, $IN, $DB, $LOG, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
                
        $query = $DB->query("SELECT group_name FROM exp_field_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
        $name = $query->row['group_name'];
        
        $query = $DB->query("SELECT field_id, field_type FROM exp_weblog_fields WHERE group_id ='$group_id'");
                
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_id_".$row['field_id']);
                $DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_ft_".$row['field_id']);
                
				if ($row['field_type'] == 'date')
				{
					$DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_dt_".$row['field_id']);
				}

		        $DB->query("DELETE FROM exp_field_formatting WHERE field_id = '".$DB->escape_str($row['field_id'])."'");
		        $DB->query("UPDATE exp_weblogs SET search_excerpt = 0 WHERE search_excerpt = '".$DB->escape_str($row['field_id'])."'");		       
            }
        }
        
        $DB->query("DELETE FROM exp_field_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
       	$DB->query("DELETE FROM exp_weblog_fields WHERE group_id = '".$DB->escape_str($group_id)."'");
        
        $LOG->log_action($LANG->line('field_group_deleted').$DSP->nbs(2).$name);                
        
        $message = $DSP->qdiv('itemWrapper', $DSP->qspan('success', $LANG->line('field_group_deleted')).NBS.NBS.'<b>'.$name.'</b>');
		
		$FNS->clear_caching('all', '', TRUE);
		
        return $this->field_overview($message);
    }
    /* END */
    
    
 
    /** -----------------------------------------------------------
    /**  Field manager
    /** -----------------------------------------------------------*/
    // This function show a list of current fields and the
    // form that allows you to create a new field.
    //-----------------------------------------------------------

    function field_manager($group_id = '', $msg = FALSE)
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
         $message = ($msg == TRUE) ? $DSP->qdiv('success', $LANG->line('preferences_updated')) : '';

        if ($group_id == '')
        {
            if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
            {
                return FALSE;
            }
        }
        elseif ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        // Fetch the name of the field group
        
        $query = $DB->query("SELECT group_name FROM  exp_field_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
                                            
        $r  = $DSP->qdiv('tableHeading', $LANG->line('field_group').':'.$DSP->nbs(2).$query->row['group_name']);
        
        if ($message != '')
        {
			$r .= $DSP->qdiv('box', stripslashes($message));
		}
     
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeadingAlt', '40%', '1').$LANG->line('field_label').$DSP->td_c().
              $DSP->td('tableHeadingAlt', '20%', '1').$LANG->line('field_name').$DSP->td_c().
              $DSP->td('tableHeadingAlt', '40%', '2').$LANG->line('field_type').$DSP->td_c().
              $DSP->tr_c();

        $query = $DB->query("SELECT field_id, field_order, field_name, field_label, field_type FROM  exp_weblog_fields WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY field_order");
        
  
        if ($query->num_rows == 0)
        {
            $r .= $DSP->tr().
                  $DSP->td('tableCellTwo', '', 3).
                  '<b>'.$LANG->line('no_field_groups').'</br>'.
                  $DSP->td_c().
                  $DSP->tr_c();
        }  

        $i = 0;
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

                $r .= $DSP->tr();
                
                $r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_field'.AMP.'field_id='.$row['field_id'], $row['field_order'].$DSP->nbs(2).$row['field_label'])));      
                
                $r .= $DSP->table_qcell($style, $row['field_name']);
                
                $field_type = ($LANG->line($row['field_type']) === FALSE) ? '' : $LANG->line($row['field_type']);
                
                switch ($row['field_type'])
                {
                	case 'text' :  $field_type = $LANG->line('text_input');
                		break;
                	case 'textarea' :  $field_type = $LANG->line('textarea');
                		break;
                	case 'select' :  $field_type = $LANG->line('select_list');
                		break;
                	case 'date' :  $field_type = $LANG->line('date_field');
                		break;
                	case 'rel' :  $field_type = $LANG->line('relationship');
                		break;
                }

                $r .= $DSP->table_qcell($style, $field_type);
                $r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_field_conf'.AMP.'field_id='.$row['field_id'], $LANG->line('delete')));      
                $r .= $DSP->tr_c();
            }
        }
        
        $r .= $DSP->table_c();

        if ($query->num_rows > 0)
        {
            $r .= $DSP->qdiv('paddedWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_field_order'.AMP.'group_id='.$group_id, $LANG->line('edit_field_order')));
        }
        
        $DSP->title = $LANG->line('custom_fields');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
        			  $DSP->crumb_item($LANG->line('custom_fields'));

		$DSP->right_crumb($LANG->line('create_new_custom_field'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_field'.AMP.'group_id='.$group_id);
        $DSP->body  = $r;  
    }
    /* END */
    
  
 
    /** -----------------------------------------------------------
    /**  Edit field form
    /** -----------------------------------------------------------*/
    // This function lets you edit an existing custom field
    //-----------------------------------------------------------

    function edit_field_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG, $EXT, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

		$field_id = $IN->GBL('field_id');
		
        $type = ($field_id) ? 'edit' : 'new';
        
        $total_fields = '';
        
        if ($type == 'new')
        {
            $query = $DB->query("SELECT COUNT(*) AS count FROM exp_weblog_fields WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
            
            $total_fields = $query->row['count'] + 1;
        }
        
        $DB->fetch_fields = TRUE;
        
        $query = $DB->query("SELECT f.*, g.group_name FROM exp_weblog_fields AS f, exp_field_groups AS g
							WHERE f.group_id = g.group_id
							AND g.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
							AND f.field_id = '{$field_id}'");
        
        $data = array();
        
        if ($query->num_rows == 0)
        {
            foreach ($query->fields as $f)
            {
            	$data[$f] = '';
                $$f = '';
            }
        }
        else
        {        
            foreach ($query->row as $key => $val)
            {
            	$data[$key] = $val;
                $$key = $val;
            }
        }
        
        if ($group_id == '')
        {
			$group_id = $IN->GBL('group_id');
        }
        
		// Adjust $group_name for new custom fields
		// as we display this later
		
		if ($group_name == '')
		{
			$query = $DB->query("SELECT group_name FROM exp_field_groups WHERE group_id = '{$group_id}'");

			if ($query->num_rows > 0)
			{
				$group_name = $query->row['group_name'];
			}
		}
        
		// Is the gallery installed?
		// We check this here so that the JS can know
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Gallery'");
        $is_gallery_installed = ($query->row['count'] == 0) ? FALSE : TRUE;
        
        
        // JavaScript Stuff
        
        $val = $LANG->line('field_val');
        
        $r = "";
        
		ob_start();
		?>        
        <script type="text/javascript"> 
        <!--
        
        function showhide_element(id)
        {         
        	if (id == 'text')
        	{
				document.getElementById('text_block').style.display = "block";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('select_block').style.display = "none";
				document.getElementById('pre_populate').style.display = "none";			
				document.getElementById('date_block').style.display = "none";
				document.getElementById('rel_block').style.display = "none";	
				document.getElementById('relationship_type').style.display = "none";
				document.getElementById('formatting_block').style.display = "block";
				document.getElementById('formatting_unavailable').style.display = "none";
				document.getElementById('direction_available').style.display = "block";
				document.getElementById('direction_unavailable').style.display = "none";
        	}
        	else if (id == 'textarea')
        	{
				document.getElementById('textarea_block').style.display = "block";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('select_block').style.display = "none";
				document.getElementById('pre_populate').style.display = "none";
				document.getElementById('date_block').style.display = "none";
				document.getElementById('rel_block').style.display = "none";
				document.getElementById('relationship_type').style.display = "none";
				document.getElementById('formatting_block').style.display = "block";
				document.getElementById('formatting_unavailable').style.display = "none";
				document.getElementById('direction_available').style.display = "block";
				document.getElementById('direction_unavailable').style.display = "none";
        	}
        	else if (id == 'select')
        	{
				document.getElementById('select_block').style.display = "block";
				document.getElementById('pre_populate').style.display = "block";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('date_block').style.display = "none";
				document.getElementById('rel_block').style.display = "none";
				document.getElementById('relationship_type').style.display = "none";
				document.getElementById('formatting_block').style.display = "block";
				document.getElementById('formatting_unavailable').style.display = "none";
				document.getElementById('direction_available').style.display = "block";
				document.getElementById('direction_unavailable').style.display = "none";
        	}
        	else if (id == 'date')
        	{
				document.getElementById('date_block').style.display = "block";
				document.getElementById('select_block').style.display = "none";
				document.getElementById('pre_populate').style.display = "none";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('rel_block').style.display = "none";
				document.getElementById('relationship_type').style.display = "none";	
				document.getElementById('formatting_block').style.display = "none";		
				document.getElementById('formatting_unavailable').style.display = "block";
				document.getElementById('direction_available').style.display = "none";
				document.getElementById('direction_unavailable').style.display = "block";
				
				<?php if ($field_id != "") echo 'format_update_block(1,1);'; ?>
        	}
        	else if (id == 'rel')
        	{
				document.getElementById('rel_block').style.display = "block";
				document.getElementById('select_block').style.display = "none";
				document.getElementById('pre_populate').style.display = "none";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "none";
				document.getElementById('date_block').style.display = "none";
				document.getElementById('relationship_type').style.display = "block";	
				document.getElementById('formatting_block').style.display = "none";
				document.getElementById('formatting_unavailable').style.display = "block";
				document.getElementById('direction_available').style.display = "block";
				document.getElementById('direction_unavailable').style.display = "none";
				<?php if ($field_id != "") echo 'format_update_block(1,1);'; ?>
        	}
        }
        
        function pre_populate(id)
        {
        	if (id == 'n')
        	{
				document.getElementById('populate_block_man').style.display = "block";
				document.getElementById('populate_block_blog').style.display = "none";
        	}
        	else
        	{
				document.getElementById('populate_block_blog').style.display = "block";
				document.getElementById('populate_block_man').style.display = "none";
        	}
        }
        
        
        function relationship_type(id)
        {
        	if (id == 'blog')
        	{
				document.getElementById('related_block_blog').style.display = "block";
				document.getElementById('sortorder_block').style.display = "block";
				document.getElementById('related_block_gallery').style.display = "none";
        	}
        	else
        	{
				document.getElementById('related_block_gallery').style.display = "block";
				document.getElementById('related_block_blog').style.display = "none";
				<?php
				if ($is_gallery_installed == FALSE)
				{
				?>
				document.getElementById('sortorder_block').style.display = "none";
				<?php
				}
				?>
				
        	}
        }
        
        
		function format_update_block(oldfmt, newfmt)
		{
			if (oldfmt == newfmt)
			{
				document.getElementById('update_formatting').style.display = "none";
				document.field_form.update_formatting.checked=false;	
			}
			else
			{
				document.getElementById('update_formatting').style.display = "block";
			}
		
		}
        
        function validate(id)
        {
		  if (id == "") 
		  {
		  	alert("<?php echo $LANG->line('field_val'); ?>");
		  	return FALSE;
		  }	
        }

		-->
		</script>
		<?php
		
		$js = ob_get_contents();
		ob_end_clean();
		
		/* -------------------------------------------
		/* 'publish_admin_edit_field_js' hook.
		/*  - Allows modifying or adding onto Custom Weblog Field JS
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_js') === TRUE)
			{
				$js = $EXT->call_extension('publish_admin_edit_field_js', $data, $js);
			}
		/*
		/* -------------------------------------------*/

		$r .= $js;
		$r .= NL.NL;
        $typopts  = '';
                
        // Form declaration
        
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_weblog_fields', 'name' => 'field_form'));
        $r .= $DSP->input_hidden('group_id', $group_id);
        $r .= $DSP->input_hidden('field_id', $field_id);
		$r .= $DSP->input_hidden('site_id', $PREFS->ini('site_id'));
        
        $title = ($type == 'edit') ? 'edit_field' : 'create_new_custom_field';
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').$LANG->line($title).NBS.NBS."(".$LANG->line('field_group').": {$group_name})".$DSP->td_c().
              $DSP->tr_c();
              
        $i = 0;
            
        /** ---------------------------------
        /**  Field name
        /** ---------------------------------*/
        
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('field_name', 'field_name')).$DSP->qdiv('itemWrapper', $LANG->line('field_name_cont')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_name', $field_name, '20', '60', 'input', '260px'), '50%');
		$r .= $DSP->tr_c();		
                
        /** ---------------------------------
        /**  Field Label
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('field_label', 'field_label')).$DSP->qdiv('', $LANG->line('field_label_info')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_label', $field_label, '20', '60', 'input', '260px'), '50%');
		$r .= $DSP->tr_c();		
		
		/** ---------------------------------
        /**  Field Instructions
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_instructions', 'field_instructions')).$DSP->qdiv('', $LANG->line('field_instructions_info')), '50%', 'top');
		$r .= $DSP->table_qcell($style, $DSP->input_textarea('field_instructions', $field_instructions, '6', 'textarea', '99%'), '50%', 'top');
		$r .= $DSP->tr_c();
		
        /** ---------------------------------
        /**  Field type
        /** ---------------------------------*/

        $sel_1 = ''; $sel_2 = ''; $sel_3 = ''; $sel_4 = ''; $sel_5 = '';
        $text_js = ($type == 'edit') ? 'none' : 'block';
        $textarea_js = 'none';
        $select_js = 'none';
        $select_opt_js = 'none';
        $date_js = 'none';
        $rel_js = 'none';
        $rel_type_js = 'none';

        switch ($field_type)
        {
            case 'text'     : $sel_1 = 1; $text_js = 'block';
                break;
            case 'textarea' : $sel_2 = 1; $textarea_js = 'block';
                break;
            case 'select'   : $sel_3 = 1; $select_js = 'block'; $select_opt_js = 'block';
                break;
            case 'date'		: $sel_4 = 1; $date_js = 'block';
                break;
            case 'rel'   	: $sel_5 = 1; $rel_js = 'block'; $rel_type_js = 'block';
                break;
        }
        
        /** ---------------------------------
        /**  Create the pull-down menu
        /** ---------------------------------*/

        $typemenu = "<select name='field_type' class='select' onchange='showhide_element(this.options[this.selectedIndex].value);' >".NL;
		$typemenu .= $DSP->input_select_option('text', 		$LANG->line('text_input'),	$sel_1)
					.$DSP->input_select_option('textarea', 	$LANG->line('textarea'),  	$sel_2)
					.$DSP->input_select_option('select', 	$LANG->line('select_list'), $sel_3)
					.$DSP->input_select_option('date', 		$LANG->line('date_field'),	$sel_4)
					.$DSP->input_select_option('rel', 		$LANG->line('relationship'), $sel_5);
					
		/* -------------------------------------------
		/* 'publish_admin_edit_field_type_pulldown' hook.
		/*  - Allows modifying or adding onto Custom Weblog Field Type Menu Pulldown
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_type_pulldown') === TRUE)
			{
				$typemenu = $EXT->call_extension('publish_admin_edit_field_type_pulldown', $data, $typemenu);
			}
		/*
		/* -------------------------------------------*/
					
		$typemenu .= $DSP->input_select_footer();

        /** ---------------------------------
        /**  Create the "populate" radio buttons
        /** ---------------------------------*/

		if ($field_pre_populate == '')
			$field_pre_populate = 'n';
					
		$typemenu .= '<div id="pre_populate" style="display: '.$select_opt_js.'; padding:0; margin:5px 0 0 0;">';		
		$typemenu .= $DSP->qdiv('default',$DSP->input_radio('field_pre_populate', 'n', ($field_pre_populate == 'n') ? 1 : 0, " onclick=\"pre_populate('n');\"").' '.$LANG->line('field_populate_manually'));
		$typemenu .= $DSP->qdiv('default',$DSP->input_radio('field_pre_populate', 'y', ($field_pre_populate == 'y') ? 1 : 0, " onclick=\"pre_populate('y');\"").' '.$LANG->line('field_populate_from_blog'));
		$typemenu .= $DSP->div_c();
		
        /** ---------------------------------
        /**  Create the "relationship with" radio buttons
        /** ---------------------------------*/
		
		if ($field_related_to == '')
			$field_related_to = 'blog';
		
		$typemenu .= '<div id="relationship_type" style="display: '.$rel_type_js.'; padding:0; margin:5px 0 0 0;">';		
		$typemenu .= $DSP->qdiv('default',$DSP->input_radio('field_related_to', 'blog', ($field_related_to == 'blog') ? 1 : 0, " onclick=\"relationship_type('blog');\"").' '.$LANG->line('related_to_blog'));
		$typemenu .= $DSP->qdiv('default',$DSP->input_radio('field_related_to', 'gallery', ($field_related_to == 'gallery') ? 1 : 0, " onclick=\"relationship_type('gallery');\"").' '.$LANG->line('related_to_gallery'));
		$typemenu .= $DSP->div_c();
		
		
		/* -------------------------------------------
		/* 'publish_admin_edit_field_type_cellone' hook.
		/*  - Allows modifying or adding onto Custom Weblog Field Type - First Table Cell
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_type_cellone') === TRUE)
			{
				$typemenu = $EXT->call_extension('publish_admin_edit_field_type_cellone', $data, $typemenu);
			}
		/*
		/* -------------------------------------------*/
		
        /** ---------------------------------
        /**  Select List Field
        /** ---------------------------------*/

		$typopts .= '<div id="select_block" style="display: '.$select_js.'; padding:0; margin:5px 0 0 0;">';		
	
        /** ---------------------------------
		/**  Populate Manually
		/** ---------------------------------*/
		
		$man_populate_js = ($field_pre_populate == 'n') ? 'block' : 'none';
		$typopts .= '<div id="populate_block_man" style="display: '.$man_populate_js.'; padding:0; margin:5px 0 0 0;">';		
		$typopts .= $DSP->qdiv('defaultBold', $LANG->line('field_list_items', 'field_list_items')).$DSP->qdiv('default', $LANG->line('field_list_instructions')).$DSP->input_textarea('field_list_items', $field_list_items, 10, 'textarea', '400px');
		$typopts .= $DSP->div_c();
		
		
        /** ---------------------------------
		/**  Populate via an existing field
		/** ---------------------------------*/

		$blog_populate_js = ($field_pre_populate == 'y') ? 'block' : 'none';
		$typopts .= '<div id="populate_block_blog" style="display: '.$blog_populate_js.'; padding:0; margin:5px 0 0 0;">';
		
		// Fetch the weblog names
		$query = $DB->query("SELECT weblog_id, blog_title, field_group FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY blog_title asc");

		// Create the drop-down menu		
		$typopts .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('select_weblog_for_field')));
        $typopts .= "<select name='field_pre_populate_id' class='select' onchange='validate(this.options[this.selectedIndex].value);' >".NL;
		
		foreach ($query->result as $row)
		{
			// Fetch the field names
			$rez = $DB->query("SELECT field_id, field_label FROM  exp_weblog_fields WHERE group_id = '".$row['field_group']."' ORDER BY field_label asc");

			$typopts .= $DSP->input_select_option('', $row['blog_title']);	
			foreach ($rez->result as $frow)
			{
				$sel = ($field_pre_blog_id == $row['weblog_id'] AND $field_pre_field_id == $frow['field_id']) ? 1 : 0;
			
				$typopts .= $DSP->input_select_option($row['weblog_id'].'_'.$frow['field_id'], NBS.'-'.NBS.$frow['field_label'], $sel);		
			}
		}
		$typopts .= $DSP->input_select_footer();		
		$typopts .= $DSP->div_c();
		
		$typopts .= $DSP->div_c();
		
										
        /** ---------------------------------
        /**  Date type
        /** ---------------------------------*/

		$typopts .= '<div id="date_block" style="display: '.$date_js.'; padding:0; margin:0;">';		
		$typopts .= NBS;
		$typopts .= $DSP->div_c();


        /** ---------------------------------
		/**  Populate via a relationsihp
		/** ---------------------------------*/
        
        // Outer DIV for blog and gallery relationships
		$typopts .= '<div id="rel_block" style="display: '.$rel_js.'; padding:0; margin:0;">';		
               
        /** ---------------------------------
		/**  Weblog Relationships
		/** ---------------------------------*/
		
		$related_to_block = ($field_related_to == 'blog') ? 'block' : 'none';
		$typopts .= '<div id="related_block_blog" style="display: '.$related_to_block.'; padding:0; margin:0;">';

		// Create the drop-down menu		
		$typopts .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('select_related_blog')));

		// Fetch the weblog names
		
		$sql = "SELECT weblog_id, blog_title, site_label FROM exp_weblogs, exp_sites
				WHERE exp_weblogs.site_id = exp_sites.site_id ";
		
		if ($PREFS->ini('multiple_sites_enabled') !== 'y')
		{
			$sql .= "AND exp_weblogs.site_id = '1' ";
		}
		
		$query = $DB->query($sql."ORDER BY blog_title asc");

		$typopts .= $DSP->input_select_header('field_related_blog_id');
		
		foreach ($query->result as $row)
		{
			$sel = ($field_related_id == $row['weblog_id']) ? 1 : 0;
			$typopts .= $DSP->input_select_option($row['weblog_id'], ($PREFS->ini('multiple_sites_enabled') == 'y') ? $row['site_label'].NBS.'-'.NBS.$row['blog_title'] : $row['blog_title'], $sel);		
		}
		
		$typopts .= $DSP->input_select_footer();		
		$typopts .= $DSP->div_c();

        /** ---------------------------------
		/**  Gallery Relationships
		/** ---------------------------------*/

		$related_to_block = ($field_related_to == 'gallery') ? 'block' : 'none';
		$typopts .= '<div id="related_block_gallery" style="display: '.$related_to_block.'; padding:0; margin:0;">';
        
        if ($is_gallery_installed == FALSE)
        {
			$typopts .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('gallery_not_installed')));
        }
        else
        {
			// Create the drop-down menu		
			$typopts .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('select_related_gallery')));
	
			// Fetch the Gallery Names
			$query = $DB->query("SELECT gallery_id, gallery_full_name FROM exp_galleries ORDER BY gallery_full_name asc");
	
			$typopts .= $DSP->input_select_header('field_related_gallery_id');
			foreach ($query->result as $row)
			{
				$sel = ($field_related_id == $row['gallery_id']) ? 1 : 0;
				$typopts .= $DSP->input_select_option($row['gallery_id'], $row['gallery_full_name'], $sel);		
			}
			$typopts .= $DSP->input_select_footer();		
        }
		
		$typopts .= $DSP->div_c();

        /** ---------------------------------
		/**  Sorting for relationships
		/** ---------------------------------*/
        
		$typopts .= '<div id="sortorder_block" style="display: block; padding:0; margin:0;">';
		$typopts .= $DSP->div('itemWrapper');
		$typopts .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('display_criteria')));

		$typopts .= $DSP->input_select_header('field_related_orderby');	
		$typopts .= $DSP->input_select_option('title', $LANG->line('orderby_title'), (($field_related_orderby == '' OR $field_related_orderby == 'title') ? 1 : 0));
		$typopts .= $DSP->input_select_option('date', $LANG->line('orderby_date'), ($field_related_orderby == 'date') ? 1 : 0);
		$typopts .= $DSP->input_select_footer();		

		$typopts .= NBS.$LANG->line('in').NBS;

		$typopts .= $DSP->input_select_header('field_related_sort');	
		$typopts .= $DSP->input_select_option('desc', $LANG->line('sort_desc'), (($field_related_sort == '' OR $field_related_sort == 'desc') ? 1 : 0));
		$typopts .= $DSP->input_select_option('asc', $LANG->line('sort_asc'), ($field_related_sort == 'asc') ? 1 : 0);
		$typopts .= $DSP->input_select_footer();	
		
		$typopts .= NBS.$LANG->line('limit').NBS;

		$typopts .= $DSP->input_select_header('field_related_max');	
		$typopts .= $DSP->input_select_option('0', $LANG->line('all'), (($field_related_max == '' OR $field_related_max == 0) ? 1 : 0));
		$typopts .= $DSP->input_select_option('25', 25, ($field_related_max == 25) ? 1 : 0);
		$typopts .= $DSP->input_select_option('50', 50, ($field_related_max == 50) ? 1 : 0);
		$typopts .= $DSP->input_select_option('100', 100, ($field_related_max == 100) ? 1 : 0);
		$typopts .= $DSP->input_select_option('250', 250, ($field_related_max == 250) ? 1 : 0);
		$typopts .= $DSP->input_select_option('500', 500, ($field_related_max == 500) ? 1 : 0);
		$typopts .= $DSP->input_select_option('1000', 1000, ($field_related_max == 1000) ? 1 : 0);
		$typopts .= $DSP->input_select_footer();	

		$typopts .= $DSP->div_c();
		$typopts .= $DSP->div_c();

        /** ---------------------------------
		/**  END outer DIV for relationships
		/** ---------------------------------*/

		$typopts .= $DSP->div_c();
		
		/* -------------------------------------------
		/* 'publish_admin_edit_field_type_celltwo' hook.
		/*  - Allows modifying or adding onto Custom Weblog Field Type - Second Table Cell
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_type_celltwo') === TRUE)
			{
				$typopts = $EXT->call_extension('publish_admin_edit_field_type_celltwo', $data, $typopts);
			}
		/*
		/* -------------------------------------------*/


        /** ---------------------------------
        /**  Max-length Field
        /** ---------------------------------*/
		
		if ($type != 'edit')
			$field_maxl = 128;
		
		$z  = '<div id="text_block" style="display: '.$text_js.'; padding:0; margin:5px 0 0 0;">';		
		$z .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_text('field_maxl', $field_maxl, '4', '3', 'input', '30px').NBS.$LANG->line('field_max_length', 'field_maxl'));
		$z .= $DSP->div_c();

        /** ---------------------------------
        /**  Textarea Row Field
        /** ---------------------------------*/

		if ($type != 'edit')
			$field_ta_rows = 6;

		$z .= '<div id="textarea_block" style="display: '.$textarea_js.'; padding:0; margin:5px 0 0 0;">';		
		$z .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_text('field_ta_rows', $field_ta_rows, '4', '3', 'input', '30px').NBS.$LANG->line('textarea_rows', 'field_ta_rows'));
		$z .= $DSP->div_c();

        /** ---------------------------------
        /**  Generate the above items
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('field_type'))).$typemenu.$z, '50%', 'top');
		$r .= $DSP->table_qcell($style, $typopts, '50%');
		$r .= $DSP->tr_c();	
		
		
		
        /** ---------------------------------
        /**  Show field formatting?
        /** ---------------------------------*/
                      
        if ($field_show_fmt == '') 
        	$field_show_fmt = 'y';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
        /** ---------------------------------
        /**  Field Formatting
        /** ---------------------------------*/


		if ($field_id != '')
        	$typemenu = "<select name='field_fmt' class='select' onchange='format_update_block(this.options[this.selectedIndex].value, \"".$field_fmt."\");' >".NL;
		else
			$typemenu  = $DSP->input_select_header('field_fmt');
							
		if ($type == 'new')
		{
			$menulink  = '';
			$typemenu .= $DSP->input_select_option('none', 	$LANG->line('none'), 	'')
						.$DSP->input_select_option('br',	$LANG->line('auto_br'), '')
						.$DSP->input_select_option('xhtml',	$LANG->line('xhtml'), 	1);
		}
		else
		{
			$confirm = "onclick=\"if(!confirm('".$LANG->line('list_edit_warning')."')) return false;\"";
			$menulink = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_fmt_buttons'.AMP.'id='.$field_id, '<b>'.$LANG->line('edit_list').'</b>', $confirm);
		
			$typemenu .= $DSP->input_select_option('none', $LANG->line('none'), ($field_fmt == 'none') ? 1 : '');
		
			$query = $DB->query("SELECT field_fmt FROM exp_field_formatting WHERE field_id = '$field_id' AND field_fmt != 'none' ORDER BY field_fmt");
		
			foreach ($query->result as $row)
			{
				$fmtname = ucwords(str_replace('_', ' ', $row['field_fmt']));
					
				if ($fmtname == 'Br')
				{
					$fmtname = $LANG->line('auto_br');
				}
				elseif ($fmtname == 'Xhtml')
				{
					$fmtname = $LANG->line('xhtml');
				}       		
					
				$sel = ($field_fmt == $row['field_fmt']) ? 1 : '';
			
				$typemenu .= $DSP->input_select_option($row['field_fmt'], $fmtname, $sel);
			}
		}
		
		$typemenu  .= $DSP->input_select_footer();

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		
		$formatting_block = ($field_type == 'date' OR $field_type == 'rel') ? 'none' : 'block';
		$y  = '<div id="formatting_block" style="display: '.$formatting_block.'; padding:0; margin:0 0 0 0;">';
		$y .= $typemenu.NBS.NBS.$menulink;
		$y .= $DSP->qdiv('itemWrapper', $DSP->input_radio('field_show_fmt', 'y', ($field_show_fmt == 'y') ? 1 : '').$LANG->line('show_formatting_buttons').BR.$DSP->input_radio('field_show_fmt', 'n', ($field_show_fmt == 'n') ? 1 : '').$LANG->line('hide_formatting_buttons'));
		$y .= $DSP->div_c();
	
		$formatting_block = ($field_type == 'date' OR $field_type == 'rel') ? 'block' : 'none';
		$y .= '<div id="formatting_unavailable" style="display: '.$formatting_block.'; padding:0; margin:0 0 0 0;">';
		$y .= $DSP->qdiv('highlight', $LANG->line('formatting_no_available'));
		$y .= $DSP->div_c();
		
		/* -------------------------------------------
		/* 'publish_admin_edit_field_format' hook.
		/*  - Allows modifying or adding onto Default Text Formatting Cell
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_format') === TRUE)
			{
				$y = $EXT->call_extension('publish_admin_edit_field_format', $data, $y);
			}
		/*
		/* -------------------------------------------*/
			
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('deft_field_formatting')), '50%', 'top');
		$r .= $DSP->table_qcell($style, $y, '50%');
		$r .= $DSP->tr_c();	
		
		/** ---------------------------------
        /**  Text Direction
        /** ---------------------------------*/
              
        if ($field_text_direction == '') $field_text_direction = 'ltr';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$direction_available = (in_array($field_type, array('text', 'textarea', 'select', 'rel', ''))) ? 'block' : 'none';
		$direction_unavailable = (in_array($field_type, array('text', 'textarea', 'select', 'rel', ''))) ? 'none' : 'block';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('text_direction')), '50%');
		$r .= $DSP->table_qcell($style, 
										'<div id="direction_available" style="display: '.$direction_available.'; padding:0; margin:0 0 0 0;">'.
										$LANG->line('ltr').$DSP->nbs().
										$DSP->input_radio('field_text_direction', 'ltr', ($field_text_direction == 'ltr') ? 1 : '').
										$DSP->nbs(3).
										$LANG->line('rtl').$DSP->nbs().
										$DSP->input_radio('field_text_direction', 'rtl', ($field_text_direction == 'rtl') ? 1 : '').
										$DSP->div_c().
										
										'<div id="direction_unavailable" style="display: '.$direction_unavailable.'; padding:0; margin:0 0 0 0;">'.
										$DSP->qdiv('highlight', $LANG->line('direction_unavailable')).
										$DSP->div_c(),
										'50%');
		$r .= $DSP->tr_c();			
        
        
        /** ---------------------------------
        /**  Is field required?
        /** ---------------------------------*/
              
        if ($field_required == '') $field_required = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_required')), '50%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('field_required', 'y', ($field_required == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('field_required', 'n', ($field_required == 'n') ? 1 : ''), '50%');
		$r .= $DSP->tr_c();		
             
        /** ---------------------------------
        /**  Is field searchable?
        /** ---------------------------------*/
        if ($field_search == '') $field_search = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_searchable')), '50%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('field_search', 'y', ($field_search == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('field_search', 'n', ($field_search == 'n') ? 1 : ''), '50%');
		$r .= $DSP->tr_c();

        /** ---------------------------------
        /**  Is field hidden?
        /** ---------------------------------*/
        
        if ($field_is_hidden == '') 
        	$field_is_hidden = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('field_is_hidden')).$DSP->qdiv('itemWrapper', $LANG->line('hidden_field_blurb')), '50%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('field_is_hidden', 'n', ($field_is_hidden == 'n') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('field_is_hidden', 'y', ($field_is_hidden == 'y') ? 1 : ''), '50%');
		$r .= $DSP->tr_c();

		
        /** ---------------------------------
        /**  Field order
        /** ---------------------------------*/
        
        if ($type == 'new')
            $field_order = $total_fields;
            
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_order', 'field_order')), '50%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('field_order', $field_order, '4', '3', 'input', '30px'), '50%');
		$r .= $DSP->tr_c();		
		
		
		/* -------------------------------------------
		/* 'publish_admin_edit_field_extra_row' hook.
		/*  - Allows modifying or adding onto the Custom Field settings table
		/*  - Added 1.4.2
		*/
			if ($EXT->active_hook('publish_admin_edit_field_extra_row') === TRUE)
			{
				$r = $EXT->call_extension('publish_admin_edit_field_extra_row', $data, $r);
			}
		/*
		/* -------------------------------------------*/
			

		
                
		$r .= $DSP->table_c();
		
        $r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemWrapper', $DSP->required(1));

		if ($field_id != '')
		{
			$r .= '<div id="update_formatting" style="display: none; padding:0; margin:0 0 0 0;">';
			$r .= $DSP->div('itemWrapper');
			$r .= $DSP->qdiv('alert', $LANG->line('fmt_has_changed'));
			$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('update_formatting', 'y', 0).' '.$DSP->qspan('alert', $LANG->line('update_existing_fields')));
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
		}		
                
        
        if ($type == 'edit')        
            $r .= $DSP->input_submit($LANG->line('update'));
        else
            $r .= $DSP->input_submit($LANG->line('submit'));
              
        $r .= $DSP->div_c();
        
        
        $r .= $DSP->form_close();
        
        $DSP->title = $LANG->line('custom_fields');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
        			  $DSP->crumb_item($LANG->line('custom_fields'));
        $DSP->body  = $r;

    }
    /* END */
    
 
 
    /** -------------------------------------------
    /**  Create/update custom fields
    /** -------------------------------------------*/

    function update_weblog_fields()
    {  
        global $DSP, $FNS, $IN, $DB, $REGX, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }        
        
        // If the $field_id variable has data we are editing an
        // existing group, otherwise we are creating a new one
        
        $edit = ( ! isset($_POST['field_id']) OR $_POST['field_id'] == '') ? FALSE : TRUE;
        
        // We need this as a variable as we'll unset the array index
       
        $group_id = $_POST['group_id'];
        
        if ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
				
        // Check for required fields

        $error = array();
        
		// little check in case they switched sites in MSM after leaving a window open.
		// otherwise the landing page will be extremely confusing
		if ( ! isset($_POST['site_id']) OR $_POST['site_id'] != $PREFS->ini('site_id'))
		{
			$error[] = $LANG->line('site_id_mismatch');
		}
		
        if ($_POST['field_name'] == '')
        {
            $error[] = $LANG->line('no_field_name');
        }
        else
        {
        	// Is the field one of the reserved words?
        	
			if (in_array($_POST['field_name'], $DSP->invalid_custom_field_names()))
        	{
            	$error[] = $LANG->line('reserved_word');
        	}
        }
        
        if ($_POST['field_label'] == '')
        {
            $error[] = $LANG->line('no_field_label');
        }
        
        // Does field name contain invalide characters?
        
        if ( ! preg_match("#^[a-z0-9\_\-]+$#i", $_POST['field_name'])) 
        {
            $error[] = $LANG->line('invalid_characters');
        }
          
        // Is the field name taken?

        $sql = "SELECT COUNT(*) AS count FROM exp_weblog_fields WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND field_name = '".$DB->escape_str($_POST['field_name'])."'";
        
        if ($edit == TRUE)
        {
            $sql .= " AND group_id != '$group_id'";
        } 
        
        $query = $DB->query($sql);        
      
        if ($query->row['count'] > 0)
        {
            $error[] = $LANG->line('duplicate_field_name');
        }

        // Are there errors to display?
        
        if (count($error) > 0)
        {
            $str = '';
            
            foreach ($error as $msg)
            {
                $str .= $msg.BR;
            }
            
            return $DSP->error_message($str);
        }
        
        if ($_POST['field_list_items'] != '')
        {
            $_POST['field_list_items'] = $REGX->convert_quotes($_POST['field_list_items']);
        }
        
		if ( ! isset($_POST['field_pre_populate_id']) OR $_POST['field_pre_populate_id'] == '')
		{
        	$_POST['field_pre_populate'] = 'n';
        }
        
        if ($_POST['field_pre_populate'] == 'y')
        {
			$x = explode('_', $_POST['field_pre_populate_id']);
			
			$_POST['field_pre_blog_id']	= $x['0'];
			$_POST['field_pre_field_id'] = $x['1'];
        }
        
       if ($_POST['field_related_to'] == 'blog')
       {
			$_POST['field_related_id'] =  (isset($_POST['field_related_blog_id'])) ? $_POST['field_related_blog_id'] : '0';
       }
       else
       {
			$_POST['field_related_id'] =  (isset($_POST['field_related_gallery_id'])) ? $_POST['field_related_gallery_id'] : '0';
       }
        
		unset($_POST['field_related_blog_id']);
		unset($_POST['field_related_gallery_id']);
		unset($_POST['field_pre_populate_id']);
		
		if ( ! in_array($_POST['field_type'], array('text', 'textarea', 'select', 'rel')))
		{
			$_POST['field_text_direction'] = 'ltr';
		}
             
        // Construct the query based on whether we are updating or inserting
   
        if ($edit === TRUE)
        {
        	if ( ! is_numeric($_POST['field_id']))
        	{
        		return FALSE;
        	}
        	
        	// Date or relationship types don't need formatting.
        	if ($_POST['field_type'] == 'date' OR $_POST['field_type'] == 'rel') 
        	{
        		$_POST['field_fmt'] = 'none';
        		$_POST['update_formatting'] = 'y';
        	}
        	
        	// Update the formatting for all existing entries
            if (isset($_POST['update_formatting']))
            	$DB->query("UPDATE exp_weblog_data SET field_ft_".$_POST['field_id']." = '".$DB->escape_str($_POST['field_fmt'])."'");            
        
            unset($_POST['group_id']);
            unset($_POST['update_formatting']);
            
            // Do we need to alter the table in order to deal with a new data type?
	  
			$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id = '".$DB->escape_str($_POST['field_id'])."'");
			
			if ($query->row['field_type'] != $_POST['field_type'])
			{
				if ($query->row['field_type'] == 'rel')
				{
					$rquery = $DB->query("SELECT field_id_".$DB->escape_str($_POST['field_id'])." AS rel_id FROM exp_weblog_data WHERE field_id_".$DB->escape_str($_POST['field_id'])." != '0'");

					if ($rquery->num_rows > 0)
					{
						$rel_ids = array();

						foreach ($rquery->result as $row)
						{
							$rel_ids[] = $row['rel_id'];
						}

						$REL_IDS = "('".implode("', '", $rel_ids)."')";
						$DB->query("DELETE FROM exp_relationships WHERE rel_id IN {$REL_IDS}");
					}
				}
				
				if ($query->row['field_type'] == 'date')
				{
					$DB->query("ALTER TABLE exp_weblog_data DROP COLUMN `field_dt_".$DB->escape_str($_POST['field_id'])."`");
				}
				
				switch($_POST['field_type'])
				{
					case 'date'	:
						$DB->query("ALTER TABLE exp_weblog_data CHANGE COLUMN field_id_".$DB->escape_str($_POST['field_id'])." field_id_".$DB->escape_str($_POST['field_id'])." int(10) NOT NULL");		
						$DB->query("ALTER table exp_weblog_data CHANGE COLUMN field_ft_".$DB->escape_str($_POST['field_id'])." field_ft_".$DB->escape_str($_POST['field_id'])." tinytext NULL");
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_dt_".$DB->escape_str($_POST['field_id'])." varchar(8) NOT NULL AFTER field_ft_".$DB->escape_str($_POST['field_id']).""); 
					break;
					case 'rel'	:
						$DB->query("ALTER TABLE exp_weblog_data CHANGE COLUMN field_id_".$DB->escape_str($_POST['field_id'])." field_id_".$DB->escape_str($_POST['field_id'])." int(10) NOT NULL");		
						$DB->query("ALTER table exp_weblog_data CHANGE COLUMN field_ft_".$DB->escape_str($_POST['field_id'])." field_ft_".$DB->escape_str($_POST['field_id'])." tinytext NULL");
					break;
					default		:
						$DB->query("ALTER TABLE exp_weblog_data CHANGE COLUMN field_id_".$DB->escape_str($_POST['field_id'])." field_id_".$DB->escape_str($_POST['field_id'])." text NOT NULL");		
						$DB->query("ALTER table exp_weblog_data CHANGE COLUMN field_ft_".$DB->escape_str($_POST['field_id'])." field_ft_".$DB->escape_str($_POST['field_id'])." tinytext NULL");
					break;
				}
			}
						
			$DB->query($DB->update_string('exp_weblog_fields', $_POST, 'field_id='.$DB->escape_str($_POST['field_id']).' AND group_id='.$group_id));              
        }
        else
        {
            unset($_POST['update_formatting']);
        
            if ($_POST['field_order'] == 0 || $_POST['field_order'] == '')
            {
                $query = $DB->query("SELECT count(*) AS count FROM exp_weblog_fields WHERE group_id = '".$DB->escape_str($group_id)."'");            
                $_POST['field_order'] = $query->row['count'] + 1; 
            }
                    
            $DB->query($DB->insert_string('exp_weblog_fields', $_POST));
            
            $insert_id = $DB->insert_id;

			if ($_POST['field_type'] == 'date' OR $_POST['field_type'] == 'rel')
			{
				$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_id_".$insert_id." int(10) NOT NULL");				
				$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_ft_".$insert_id." tinytext NULL");   
				
				if ($_POST['field_type'] == 'date')
					$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_dt_".$insert_id." varchar(8) NOT NULL");   
            }
            else
            {
				$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_id_".$insert_id." text NOT NULL");
				$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_ft_".$insert_id." tinytext NULL");
				$DB->query("UPDATE exp_weblog_data SET field_ft_".$insert_id." = '".$DB->escape_str($_POST['field_fmt'])."'");
            }            
      		   
			foreach (array('none', 'br', 'xhtml') as $val)
			{
				$DB->query("INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ('$insert_id', '$val')");    
			}
       }

		$FNS->clear_caching('all', '', TRUE);
		
        return $this->field_manager($group_id, $edit);
    }
    /* END */
 
 
 
    /** -----------------------------------------------------------
    /**  Delete field confirm
    /** -----------------------------------------------------------*/
    // Warning message if you try to delete a custom field
    //-----------------------------------------------------------

    function delete_field_conf()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $field_id = $IN->GBL('field_id'))
        {
            return FALSE;
        }

        $query = $DB->query("SELECT field_label FROM exp_weblog_fields WHERE field_id = '$field_id'");
        
        $DSP->title = $LANG->line('delete_field');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_field'));        
        
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=delete_field'.AMP.'field_id='.$field_id,
												'heading'	=> 'delete_field',
												'message'	=> 'delete_field_confirmation',
												'item'		=> $query->row['field_label'],
												'extra'		=> '',
												'hidden'	=> array('field_id' => $field_id)
											)
										);	
    }
    /* END */
    
   
   
    /** -----------------------------------------------------------
    /**  Delete field
    /** -----------------------------------------------------------*/
    // This function alters the "exp_weblog_data" table, dropping
    // the fields
    //-----------------------------------------------------------

    function delete_field()
    {  
        global $DSP, $FNS, $IN, $DB, $LOG, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $field_id = $IN->GBL('field_id', 'POST'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($field_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT group_id, field_type, field_label FROM exp_weblog_fields WHERE field_id = '".$DB->escape_str($field_id)."'");
        $group_id = $query->row['group_id'];
        $field_label = $query->row['field_label'];
        $field_type = $query->row['field_type'];
		
		if ($field_type == 'rel')
		{
			$rquery = $DB->query("SELECT field_id_".$DB->escape_str($field_id)." AS rel_id FROM exp_weblog_data WHERE field_id_".$DB->escape_str($field_id)." != '0'");

			if ($rquery->num_rows > 0)
			{
				$rel_ids = array();
				
				foreach ($rquery->result as $row)
				{
					$rel_ids[] = $row['rel_id'];
				}
				
				$REL_IDS = "('".implode("', '", $rel_ids)."')";
				$DB->query("DELETE FROM exp_relationships WHERE rel_id IN {$REL_IDS}");
			}
		}
		
		if ($field_type == 'date')
		{
			$DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_dt_".$DB->escape_str($field_id));
		}

        $DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_id_".$DB->escape_str($field_id));
        $DB->query("ALTER TABLE exp_weblog_data DROP COLUMN field_ft_".$DB->escape_str($field_id));
        $DB->query("DELETE FROM exp_weblog_fields WHERE field_id = '".$DB->escape_str($field_id)."'");
        $DB->query("DELETE FROM exp_field_formatting WHERE field_id = '".$DB->escape_str($field_id)."'");
        $DB->query("UPDATE exp_weblogs SET search_excerpt = 0 WHERE search_excerpt = '".$DB->escape_str($field_id)."'");

        $LOG->log_action($LANG->line('field_deleted').$DSP->nbs(2).$field_label);        
		
		$FNS->clear_caching('all', '', TRUE);
		
        return $this->field_manager($group_id);
    }
    /* END */
 
 
 
 
    /** -----------------------------------------------------------
    /**  Edit field order
    /** -----------------------------------------------------------*/
    // This function shows the form that lets you change the 
    // order that fields appear in
    //-----------------------------------------------------------

    function edit_field_order_form()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if (($group_id = $IN->GBL('group_id')) === FALSE OR ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT field_label, field_name, field_order FROM exp_weblog_fields WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY field_order");
        
        if ($query->num_rows == 0)
        {
            return FALSE;
        }
                
 		
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_field_order'));
        $r .= $DSP->input_hidden('group_id', $group_id);
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%');
		$r .= $DSP->tr()
			.$DSP->td('tableHeading', '', '2').$LANG->line('edit_field_order').$DSP->td_c()
			.$DSP->tr_c();

        foreach ($query->result as $row)
        {
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell('tableCellOne', $row['field_label'], '40%');
            $r .= $DSP->table_qcell('tableCellOne', $DSP->input_text($row['field_name'], $row['field_order'], '4', '3', 'input', '30px'));      
            $r .= $DSP->tr_c();
        }
        $r .= $DSP->table_c();

        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('edit_field_order');
        $DSP->crumb =
                    $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
                    $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=field_editor'.AMP.'group_id='.$group_id, $LANG->line('custom_fields'))).
                    $DSP->crumb_item($LANG->line('edit_field_order'));

        $DSP->body  = $r;
    }
    /* END */
 
 
 
 
    /** -----------------------------------------------------------
    /**  Update field order
    /** -----------------------------------------------------------*/
    // This function receives the field order submission
    //-----------------------------------------------------------

    function update_field_order()
    {  
        global $DSP, $IN, $DB, $LANG, $PREFS;


        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return FALSE;
        }
        
        unset($_POST['group_id']);
                
        foreach ($_POST as $key => $val)
        {
            $DB->query("UPDATE exp_weblog_fields SET field_order = '$val' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND field_name = '$key'");    
        }
        
        return $this->field_manager($group_id);
    }
    /* END */
 
 
    /** -------------------------------------
    /**  Fetch installed plugins
    /** -------------------------------------*/
    
    function fetch_plugins()
    {
        global $PREFS;
        
        $exclude = array('auto_xhtml');
    
        $filelist = array('br', 'xhtml');
    
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
 
 
 
    /** -----------------------------------------------------------
    /**  Edit Formatting Buttons
    /** -----------------------------------------------------------*/
    // This function shows the form that lets you edit the
    // contents of the entry formatting pull-down menu
    //-----------------------------------------------------------

    function edit_formatting_buttons()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $id = $IN->GBL('id'))
        {
            return FALSE;
        }
        
 		
		$plugins = $this->fetch_plugins();
                
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_fmt_buttons'));
        $r .= $DSP->input_hidden('field_id', $id);
        $r .= $DSP->input_hidden('none', 'y');
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%');
        
	
		$r .= $DSP->tr();
		$r .= $DSP->td('tableHeading', '', '2');
		$r .= $LANG->line('formatting_options');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
        
        $query = $DB->query("SELECT field_fmt FROM exp_field_formatting WHERE field_id = '$id' AND field_fmt != 'none' ORDER BY field_fmt");

		$plugs = array();
		
		foreach ($query->result as $row)
		{
			$plugs[] = $row['field_fmt'];
		}

		$i = 0;		
		
        foreach ($plugins as $val)
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
        
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
        
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $name));
			$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio($val, 'y', (in_array($val, $plugs)) ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio($val, 'n', ( ! in_array($val, $plugs)) ? 1 : ''), '60%');
            $r .= $DSP->tr_c();
        }
        
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

        $r .= $DSP->tr();
        $r .= $DSP->td($style, '', '2');
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
        $r .= $DSP->td_c();
        $r .= $DSP->tr_c();
        $r .= $DSP->table_c();

        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('formatting_options');
        $DSP->crumb =
                    $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=custom_fields', $LANG->line('field_groups'))).
                    $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_field'.AMP.'field_id='.$id, $LANG->line('custom_fields'))).
                    $DSP->crumb_item($LANG->line('formatting_options'));

        $DSP->body  = $r;
    }
    /* END */
 
 
 
 
    /** ---------------------------------------
    /**  Update Formatting Buttons
    /** ---------------------------------------*/

    function update_formatting_buttons()
    {  
        global $DSP, $FNS, $IN, $DB;


        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $id = $IN->GBL('field_id', 'POST'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }
        
        unset($_POST['field_id']);
        
		$DB->query("DELETE FROM exp_field_formatting WHERE field_id = '$id'");    
                
        foreach ($_POST as $key => $val)
        {
        	if ($val == 'y')
           	 $DB->query("INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ('$id', '$key')");    
        }
        		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_field'.AMP.'field_id='.$id);
		exit;        
    }
    /* END */
 
 
 
 
    
    /** -----------------------------------------------------------
    /**  HTML Buttons
    /** -----------------------------------------------------------*/
    // This function lets you edit the HTML buttons
    //-----------------------------------------------------------

    function html_buttons($message = '', $id = 0)
    { 
        global $IN, $DSP, $REGX, $LANG, $DB, $PREFS;
                
        if ($id == 0 AND ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }
        
        $r = '';
        
        if ($message != '')
        	$r .= $DSP->qdiv('box', stripslashes($message));
        
        if ($id != 0)
        {
            $r .= $DSP->qdiv('tableHeading', $LANG->line('html_buttons'));
        }
        else
        {
            $r .= $DSP->qdiv('tableHeading', $LANG->line('default_html_buttons'));
        	$r .= $DSP->qdiv('box', $LANG->line('define_html_buttons'));
        }
        
        if ($id === 0)
        {
        	$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=save_html_buttons')).
            	  $DSP->body .= $DSP->input_hidden('member_id', "$id");
		}
		else
		{
			$r .= $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=update_htmlbuttons')).
            	  $DSP->body .= $DSP->input_hidden('member_id', "$id");
		}
		
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('tag_name')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('tag_open')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('tag_close')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('accesskey')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('tag_order')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('row')).
              $DSP->tr_c();
              
              
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$id'");          

        $member_id = ($query->row['count'] == 0 AND ! isset($_GET['U'])) ? 0 : $id;
        
        $query = $DB->query("SELECT * FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' ORDER BY tag_row, tag_order");          
              
        $i = 0;
        
        if ($query->num_rows > 0)
        {     
            foreach ($query->result as $row)
            {      
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;                               
                
                $tag_row = $DSP->input_select_header('tag_row_'.$i);
                $selected = ($row['tag_row'] == '1') ? 1 : '';
                $tag_row .= $DSP->input_select_option('1', '1', $selected);
                $selected = ($row['tag_row'] == '2') ? 1 : '';
                $tag_row .= $DSP->input_select_option('2', '2', $selected);
                $tag_row .= $DSP->input_select_footer();
                
                $r .= $DSP->tr().
                      $DSP->table_qcell($style, $DSP->input_text('tag_name_'.$i,  $row['tag_name'], '20', '40', 'input', '100%'), '16%').
                      $DSP->table_qcell($style, $DSP->input_text('tag_open_'.$i,  $row['tag_open'], '20', '120', 'input', '100%'), '37%').
                      $DSP->table_qcell($style, $DSP->input_text('tag_close_'.$i, $row['tag_close'], '20', '120', 'input', '100%'), '37%').
                      $DSP->table_qcell($style, $DSP->input_text('accesskey_'.$i, $row['accesskey'], '2', '1', 'input', '30px'), '3%').
                      $DSP->table_qcell($style, $DSP->input_text('tag_order_'.$i, $row['tag_order'], '2', '2', 'input', '30px'), '3%').
                      $DSP->table_qcell($style, $tag_row, '4%').
                      $DSP->tr_c();
            }
        }   
		
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
		
		$tag_row  = $DSP->input_select_header('tag_row_'.$i);
		$tag_row .= $DSP->input_select_option('1', '1', '');
		$tag_row .= $DSP->input_select_option('2', '2', '');
		$tag_row .= $DSP->input_select_footer();
                  
        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->input_text('tag_name_'.$i, '', '20', '40', 'input', '100%'), '16%').
              $DSP->table_qcell($style, $DSP->input_text('tag_open_'.$i, '', '20', '120', 'input', '100%'), '37%').
              $DSP->table_qcell($style, $DSP->input_text('tag_close_'.$i,'', '20', '120', 'input', '100%'), '37%').
              $DSP->table_qcell($style, $DSP->input_text('accesskey_'.$i, '', '2', '1', 'input', '30px'), '3%').
              $DSP->table_qcell($style, $DSP->input_text('tag_order_'.$i, '', '2', '2', 'input', '30px'), '3%').
              $DSP->table_qcell($style, $tag_row, '4%').
              $DSP->tr_c();

			  
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '6');
        $r .= $DSP->qdiv('highlight', NBS.$LANG->line('htmlbutton_delete_instructions'));     
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('submit')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
              
        $r .= $DSP->table_c();              
        $r .= $DSP->form_close();

        if ($id == 0)
        {
            $DSP->title = $LANG->line('default_html_buttons');
            $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  	  $DSP->crumb_item($LANG->line('default_html_buttons'));
            $DSP->body  = $r;    
        }
        else
        {
            return $r;
        }
    }
    /* END */
    
    
      
    /** -----------------------------------------
    /**  Save HTML formatting buttons
    /** -----------------------------------------*/
        
    function save_html_buttons()
    {
        global $IN, $FNS, $LANG, $DB, $DSP, $PREFS;
        
        $id = $IN->GBL('member_id');
                
        if ($id == 0 AND ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }

        $data = array();
        
        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'tag_name_') AND $val != '')
            {
                $n = substr($key, 9);
                
                $data[] = array(
                                 'member_id' => $id,
                                 'tag_name'  => $_POST['tag_name_'.$n],
                                 'tag_open'  => $_POST['tag_open_'.$n],
                                 'tag_close' => $_POST['tag_close_'.$n],
                                 'accesskey' => $_POST['accesskey_'.$n],
                                 'tag_order' => $_POST['tag_order_'.$n],
                                 'tag_row'   => $_POST['tag_row_'.$n],
                                 'site_id'	 => $PREFS->ini('site_id'),
                                );
            }
        }


        $DB->query("DELETE FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$id'");

        foreach ($data as $val)
        {                       
            $DB->query($DB->insert_string('exp_html_buttons', $val));
        }
        
        $message = $DSP->qdiv('success', $LANG->line('preferences_updated'));

        if ($id == 0)
        {
            $this->html_buttons($message);
        }
        else
        {
            $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=htmlbuttons'.AMP.'id='.$id.AMP.'U=1');
            exit;    
        }
    }
    /* END */




    /** -----------------------------------------------------------
    /**  Ping servers
    /** -----------------------------------------------------------*/
    // This function lets you edit the ping servers
    //-----------------------------------------------------------

    function ping_servers($message = '', $id = '0')
    { 
        global $IN, $DSP, $REGX, $LANG, $DB, $PREFS;
        
        if ($id == 0 AND ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! is_numeric($id))
        {
			return FALSE;        
        }
        
        $r = '';
        
        if ($message != '')
        	$r .= $DSP->qdiv('box', stripslashes($message));
        
		
        
        if ($id != 0)
        {
            $r .= $DSP->qdiv('tableHeading', $LANG->line('ping_servers'));
        }
        else
        {
            $r .= $DSP->qdiv('tableHeading', $LANG->line('default_ping_servers'));
            
			$r .= $DSP->qdiv('box', $LANG->line('define_ping_servers'));
        }        
        
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=save_ping_servers')).
              $DSP->body .= $DSP->input_hidden('member_id', "$id");

        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('server_name')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('server_url')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('port')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('protocol')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('is_default')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('server_order')).
              $DSP->tr_c();
              
              
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$id'");
        
        $member_id = ($query->row['count'] == 0  AND ! isset($_GET['U'])) ? 0 : $id;
        
        $query = $DB->query("SELECT * FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' ORDER BY server_order");          
              
        $i = 0;
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {      
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;                               
                
                $protocol  = $DSP->input_select_header('ping_protocol_'.$i);            
                $protocol .= $DSP->input_select_option('xmlrpc', 'xmlrpc');
                $protocol .= $DSP->input_select_footer();
                
                $default = $DSP->input_select_header('is_default_'.$i);
                $selected = ($row['is_default'] == 'y') ? 1 : '';
                $default .= $DSP->input_select_option('y', $LANG->line('yes'), $selected);
                $selected = ($row['is_default'] == 'n') ? 1 : '';
                $default .= $DSP->input_select_option('n', $LANG->line('no'), $selected);
                $default .= $DSP->input_select_footer();
                
                $r .= $DSP->tr().
                      $DSP->table_qcell($style, $DSP->input_text('server_name_'.$i,  $row['server_name'], '20', '40', 'input', '100%'), '25%').
                      $DSP->table_qcell($style, $DSP->input_text('server_url_'.$i,   $row['server_url'], '20', '150', 'input', '100%'), '55%').
                      $DSP->table_qcell($style, $DSP->input_text('server_port_'.$i, $row['port'], '2', '4', 'input', '30px'), '5%').
                      $DSP->table_qcell($style, $protocol, '5%').
                      $DSP->table_qcell($style, $default, '5%').
                      $DSP->table_qcell($style, $DSP->input_text('server_order_'.$i, $row['server_order'], '2', '3', 'input', '30px'), '5%').
                      $DSP->tr_c();
            }
        }
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

		$protocol  = $DSP->input_select_header('ping_protocol_'.$i);            
		$protocol .= $DSP->input_select_option('xmlrpc', 'xmlrpc');
		$protocol .= $DSP->input_select_footer();
		
		$default = $DSP->input_select_header('is_default_'.$i);
		$default .= $DSP->input_select_option('y', $LANG->line('yes'));
		$default .= $DSP->input_select_option('n', $LANG->line('no'));
		$default .= $DSP->input_select_footer();

		$r .= $DSP->tr().
			  $DSP->table_qcell($style, $DSP->input_text('server_name_'.$i,  '', '20', '40', 'input', '100%'), '25%').
			  $DSP->table_qcell($style, $DSP->input_text('server_url_'.$i,  '', '20', '120', 'input', '100%'), '55%').
			  $DSP->table_qcell($style, $DSP->input_text('server_port_'.$i, '80', '2', '4', 'input', '30px'), '5%').
			  $DSP->table_qcell($style, $protocol, '5%').
			  $DSP->table_qcell($style, $default, '5%').
			  $DSP->table_qcell($style, $DSP->input_text('server_order_'.$i, '', '2', '3', 'input', '30px'), '5%').
			  $DSP->tr_c();
			  
			  
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '6');
        $r .= $DSP->qdiv('highlight', NBS.$LANG->line('pingserver_delete_instructions'));     
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('submit')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();			  
              
        $r .= $DSP->table_c();       
                           
        $r .= $DSP->form_close();

        if ($id == 0)
        {
            $DSP->title = $LANG->line('default_ping_servers');
            $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  	  $DSP->crumb_item($LANG->line('default_ping_servers'));
            $DSP->body  = $r; 
        }
        else
        {
            return $r;
        }
    }
    /* END */
    
    
      
    /** -----------------------------------------
    /**  Save ping servers
    /** -----------------------------------------*/
        
    function save_ping_servers()
    {
        global $IN, $FNS, $LANG, $DB, $DSP, $PREFS;
        
        $id = $IN->GBL('member_id');
        
        if ($id == 0 AND ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }
                
        $data = array();
        
        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'server_name_') AND $val != '')
            {
                $n = substr($key, 12);
                
                $data[] = array(
                                 'member_id'     => $id,
                                 'server_name'   => $_POST['server_name_'.$n],
                                 'server_url'    => $_POST['server_url_'.$n],
                                 'port'          => $_POST['server_port_'.$n],
                                 'ping_protocol' => $_POST['ping_protocol_'.$n],
                                 'is_default'    => $_POST['is_default_'.$n],
                                 'server_order'  => $_POST['server_order_'.$n],
                                 'site_id'		 => $PREFS->ini('site_id')
                                );
            }
        }


        $DB->query("DELETE FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$id'");

        foreach ($data as $val)
        {
            $DB->query($DB->insert_string('exp_ping_servers', $val));
        }
        
        $message = $DSP->qdiv('success', $LANG->line('preferences_updated'));
        
        
        if ($id == 0)
        {
            $this->ping_servers($message);
        }
        else
        {
            $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=pingservers'.AMP.'id='.$id.AMP.'U=1');
            exit;    
        }
    }
    /* END */



    /** -----------------------------------------------------------
    /**  File Upload Preferences Page
    /** -----------------------------------------------------------*/

    function file_upload_preferences($update = '')
    {
        global $DSP, $IN, $DB, $LANG, $PREFS;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        $r = '';
        
        if ($update != '')
        {
            $r .= $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('preferences_updated')));
        }
     
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '3').
              $LANG->line('current_upload_prefs').
              $DSP->td_c().
              $DSP->tr_c();

        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n' ORDER BY name");
  
        if ($query->num_rows == 0)
        {
            $r .= $DSP->tr().
                  $DSP->td('tableCellTwo', '', '3').
                  '<b>'.$LANG->line('no_upload_prefs').'</b>'.
                  $DSP->td_c().
                  $DSP->tr_c();
        }  

        $i = 0;
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

                $r .= $DSP->tr();
                $r .= $DSP->table_qcell($style, $i.$DSP->nbs(2).$DSP->qspan('defaultBold', $row['name']), '40%');
                $r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_upload_pref'.AMP.'id='.$row['id'], $LANG->line('edit')), '30%');      
                $r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_upload_pref_conf'.AMP.'id='.$row['id'], $LANG->line('delete')), '30%');      
                $r .= $DSP->tr_c();
            }
        }
        
        $r .= $DSP->table_c();
                
        $DSP->title  = $LANG->line('file_upload_preferences');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			   $DSP->crumb_item($LANG->line('file_upload_preferences'));
		$DSP->right_crumb($LANG->line('create_new_upload_pref'), BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=edit_upload_pref');        
        $DSP->body   = $r;  
    }
    /* END */



    /** --------------------------------------
    /**  New/Edit Upload Preferences form
    /** --------------------------------------*/

    function edit_upload_preferences_form()
    {
        global $DSP, $IN, $DB, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }
        
		
        
        $id = $IN->GBL('id');
        
        $type = ($id !== FALSE) ? 'edit' : 'new';
                
        $DB->fetch_fields = TRUE;
        
        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '$id' AND is_user_blog = 'n'");
        
        if ($query->num_rows == 0)
        {
			if ($id != '')
				return $DSP->no_access_message();
        
            foreach ($query->fields as $f)
            {
                $$f = '';
            }
        }
        else
        {        
            foreach ($query->row as $key => $val)
            {
                $$key = $val;
            }
        }
        
        // Form declaration
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=blog_admin'.AMP.'P=update_upload_prefs'));
        $r .= $DSP->input_hidden('id', $id);
        $r .= $DSP->input_hidden('cur_name', $name);
                
        $r .= $DSP->table('tableBorder', '0', '', '100%').
			  $DSP->td('tableHeading', '', '2');
			  
        if ($type == 'edit')        
            $r .= $LANG->line('edit_file_upload_preferences');
        else
            $r .= $LANG->line('new_file_upload_preferences');

			$r .= $DSP->td_c().
				  $DSP->tr_c();
        
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';        
        
        
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('upload_pref_name', 'upload_pref_name')),
									$DSP->input_text('name', $name, '50', '50', 'input', '100%')
									  )
								);
	
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('server_path', 'server_path')),
									$DSP->input_text('server_path', $server_path, '50', '100', 'input', '100%')
									  )
								);
								
        
        if ($url == '')
            $url = 'http://';
	
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('url_to_upload_dir', 'url_to_upload_dir')),
									$DSP->input_text('url', $url, '50', '100', 'input', '100%')
									  )
								);
                
        
        if ($allowed_types == '')
        	$allowed_types = 'img';
                
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('allowed_types', 'allowed_types')),
									$DSP->input_radio('allowed_types', 'img', ($allowed_types == 'img') ? 1 : '').NBS.$LANG->line('images_only')
									.NBS.NBS.NBS.$DSP->input_radio('allowed_types', 'all', ($allowed_types == 'all') ? 1 : '').NBS.$LANG->line('all_filetypes')
									  )
								);
                
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('max_size', 'max_size')),
									$DSP->input_text('max_size', $max_size, '15', '16', 'input', '90px')
									  )
								);
                
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('max_height', 'max_height')),
									$DSP->input_text('max_height', $max_height, '10', '6', 'input', '60px')
									  )
								);
								
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('max_width', 'max_width')),
									$DSP->input_text('max_width', $max_width, '10', '6', 'input', '60px')
									  )
								);
		
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('properties', 'properties')),
									$DSP->input_text('properties', $properties, '50', '120', 'input', '100%')
									  )
								);
                
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('pre_format', 'pre_format')),
									$DSP->input_text('pre_format', $pre_format, '50', '120', 'input', '100%')
									  )
								);
								
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('post_format', 'post_format')),
									$DSP->input_text('post_format', $post_format, '50', '120', 'input', '100%')
									  )
								);
								
								
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('file_properties', 'file_properties')),
									$DSP->input_text('file_properties', $file_properties, '50', '120', 'input', '100%')
									  )
								);
                
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('file_pre_format', 'file_pre_format')),
									$DSP->input_text('file_pre_format', $file_pre_format, '50', '120', 'input', '100%')
									  )
								);
								
		$r .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
								array(
								
									$DSP->qspan('defaultBold', $LANG->line('file_post_format', 'file_post_format')),
									$DSP->input_text('file_post_format', $file_post_format, '50', '120', 'input', '100%')
									  )
								);
                
        $r .= $DSP->table_c();                        
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->heading($LANG->line('restrict_to_group'), 5).$LANG->line('restrict_notes_1').$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('restrict_notes_2'))));    


        $query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE group_id != '1' AND group_id != '2' AND group_id != '3' AND group_id != '4' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_title");

		if ($query->num_rows > 0)
		{
			$r .= $DSP->table('tableBorder', '0', '', '100%').
				  $DSP->tr().
				  $DSP->td('tableHeading', '', '').
				  $LANG->line('member_group').
				  $DSP->td_c().
				  $DSP->td('tableHeading', '', '').
				  $LANG->line('can_upload_files').
				  $DSP->td_c().
				  $DSP->tr_c();
		
			$i = 0;
			
			$group = array();
			
			$sql = "SELECT member_group FROM exp_upload_no_access ";
					
			if ($id != '')
			{	
				$sql .= "WHERE upload_id = '$id'";
			}
			
			$result = $DB->query($sql);
			
			if ($result->num_rows != 0)
			{
				foreach($result->result as $row)
				{
					$group[$row['member_group']] = TRUE;
				}
			}
				
			foreach ($query->result as $row)
			{
					$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
					$r .= $DSP->tr().
						  $DSP->td($style, '50%').
						  $row['group_title'].
						  $DSP->td_c().
						  $DSP->td($style, '50%');
						  
					$selected = ( ! isset($group[$row['group_id']])) ? 1 : '';
						
					$r .= $LANG->line('yes').NBS.
						  $DSP->input_radio('access_'.$row['group_id'], 'y', $selected).$DSP->nbs(3);
					   
					$selected = (isset($group[$row['group_id']])) ? 1 : '';
						
					$r .= $LANG->line('no').NBS.
						  $DSP->input_radio('access_'.$row['group_id'], 'n', $selected).$DSP->nbs(3);
		
					$r .= $DSP->td_c()
						 .$DSP->tr_c();
			}        
			$r .= $DSP->table_c(); 
		}
	
        $r .= $DSP->div('itemWrapper')
             .$DSP->qdiv('itemWrapper', $DSP->required(1));
        
        if ($type == 'edit')        
            $r .= $DSP->input_submit($LANG->line('update'));
        else
            $r .= $DSP->input_submit($LANG->line('submit'));
              
        $r .= $DSP->div_c();
        $r .= $DSP->form_close();

		$lang_line = ($type == 'edit') ? 'edit_file_upload_preferences' : 'create_new_upload_pref';
		
        $DSP->title = $LANG->line($lang_line);
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=upload_prefs', $LANG->line('file_upload_prefs'))).
        			  $DSP->crumb_item($LANG->line($lang_line));
        $DSP->body  = $r;
    }
    /* END */




    /** ------------------------------------
    /**  Update upload preferences
    /** ------------------------------------*/

    function update_upload_preferences()
    {
        global $DSP, $IN, $DB, $LANG, $FNS, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        // If the $id variable is present we are editing an
        // existing field, otherwise we are creating a new one
        
        $edit = (isset($_POST['id']) AND $_POST['id'] != '' && is_numeric($_POST['id'])) ? TRUE : FALSE;
                
        // Check for required fields

        $error = array();
        
        if ($_POST['name'] == '')
        {
            $error[] = $LANG->line('no_upload_dir_name');
        }
        
        if ($_POST['server_path'] == '')
        {
            $error[] = $LANG->line('no_upload_dir_path');
        }
        
        if ($_POST['url'] == '' OR $_POST['url'] == 'http://')
        {
            $error[] = $LANG->line('no_upload_dir_url');
        }

		if (substr($_POST['server_path'], -1) != '/' AND substr($_POST['server_path'], -1) != '\\')
		{
			$_POST['server_path'] .= '/';
		}
		
		$_POST['url'] = rtrim($_POST['url'], '/').'/';
          
        // Is the name taken?

        $sql = "SELECT count(*) as count FROM exp_upload_prefs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND name = '".$DB->escape_str($_POST['name'])."'";
        
        $query = $DB->query($sql);        
      
        if (($edit == FALSE || ($edit == TRUE && strtolower($_POST['name']) != strtolower($_POST['cur_name']))) && $query->row['count'] > 0)
        {
            $error[] = $LANG->line('duplicate_dir_name');
        }
               
        
        // Are there errors to display?
        
        if (count($error) > 0)
        {
            $str = '';
            
            foreach ($error as $msg)
            {
                $str .= $msg.BR;
            }
            
            return $DSP->error_message($str);
        }

        $id = $IN->GBL('id');
        
        unset($_POST['id']);
        unset($_POST['cur_name']);        

        $data = array();
        $no_access = array();

        $DB->query("DELETE FROM exp_upload_no_access WHERE upload_id = '$id'");
        
        foreach ($_POST as $key => $val)
        {
            if (substr($key, 0, 7) == 'access_')
            {
                if ($val == 'n')
                {
                	$no_access[] = substr($key, 7);
                }
            }
            else
            {
                $data[$key] = $val;
            }
        }   

        // Construct the query based on whether we are updating or inserting
   
        if ($edit === TRUE)
        {        
            $DB->query($DB->update_string('exp_upload_prefs', $data, 'id='.$id));  
        }
        else
        {               
        	$data['site_id'] = $PREFS->ini('site_id');
        	
            $DB->query($DB->insert_string('exp_upload_prefs', $data));
            $id = $DB->insert_id;
        }
        
        if (sizeof($no_access) > 0)
        {
        	foreach($no_access as $member_group)
        	{
        		$DB->query("INSERT INTO exp_upload_no_access (upload_id, upload_loc, member_group) VALUES ('$id', 'cp', '".$DB->escape_str($member_group)."')");
        	}
        }
        
        
        // Clear database cache
        
        $FNS->clear_caching('db');

        return $this->file_upload_preferences(1);
    }
    /* END */




    /** --------------------------------------
    /**  Upload preferences delete confirm
    /** --------------------------------------*/

    function delete_upload_preferences_conf()
    {
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $id = $IN->GBL('id'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }

        $query = $DB->query("SELECT name FROM exp_upload_prefs WHERE id = '$id'");
        
        $DSP->title = $LANG->line('delete_upload_preference');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=weblog_administration', $LANG->line('weblog_administration')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=upload_prefs', $LANG->line('file_upload_prefs'))).
        			  $DSP->crumb_item($LANG->line('delete_upload_preference'));
   
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=admin'.AMP.'M=blog_admin'.AMP.'P=del_upload_pref'.AMP.'id='.$id,
												'heading'	=> 'delete_upload_preference',
												'message'	=> 'delete_upload_pref_confirmation',
												'item'		=> $query->row['name'],
												'extra'		=> '',
												'hidden'	=> array('id', $id)
											)
										);	
    }
    /* END */



    /** --------------------------------------
    /**  Delete upload preferences
    /** --------------------------------------*/

    function delete_upload_preferences()
    {
        global $DSP, $IN, $DB, $LOG, $FNS, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_weblogs'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $id = $IN->GBL('id'))
        {
            return FALSE;
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }
        
        $DB->query("DELETE FROM exp_upload_no_access WHERE upload_id = '$id'");
        
        $query = $DB->query("SELECT name FROM exp_upload_prefs WHERE id = '$id'");
        
        $name = $query->row['name'];
        
        $DB->query("DELETE FROM exp_upload_prefs WHERE id = '$id'");
        
        $LOG->log_action($LANG->line('upload_pref_deleted').$DSP->nbs(2).$name);     
        
        // Clear database cache
        
        $FNS->clear_caching('db');

        return $this->file_upload_preferences();
    }
    /* END */

}
// END CLASS
?>