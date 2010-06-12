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
 http://expressionengine.com/docs/license/
=====================================================
 File: cp.mt_import.php
-----------------------------------------------------
 Purpose: Movable Type Import Utility
=====================================================

http://www.sixapart.com/movabletype/docs/mtimport

*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class MT_Import {

    var $m_batch    	= 1000;
    var $b_batch    	= 100;
    var $cat_array 		= array();
    var $author_array 	= array();
    var $status_array 	= array();
    var $field_array 	= array();
    var $english		= array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins');
    
	/**
	 * Expands the MT Export format to include new fields besides EXCERPT, BODY, KEYWORDS, and EXTENDED BODY to allow the importing of unlimited data chunks into EE's Custom Fields
	 *
	 * The new fields in the MT Export file will have the key EXTRA FIELD-# where the hash is the number for that field.
	 *
	 * Increase the value to have 
	 *
	 * @var integer
	 */

	var $extra_fields	= 0;

    /** -------------------------------------------
    /**  Constructor
    /** -------------------------------------------*/
    
    function MT_Import() 
    {
        global $IN, $DSP, $LANG, $SESS, $PREFS;
        
        // You have to be a Super Admin to access this page
        
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message();
        }
        
        
        // Fetch the language file
                
        $LANG->fetch_language_file('mt_import');
        
    
        switch($IN->GBL('F'))
        {
            case 'check'                : $this->check_file();
                break;
            case 'perform_import'		: $this->perform_import();
                break;
            default						: $this->mt_import_main_page();
                break;
        }
    }
    /* END */
    
    
    
    /** -------------------------------------------
    /**  Movable Type Import Main Page
    /** -------------------------------------------*/
    
    function mt_import_main_page($msg = '') 
    {
        global $IN, $DSP, $LANG, $PREFS;
         
        $DSP->title = $LANG->line('mt_import_utility');
        
        if ($DSP->crumb == '')
        {
            $DSP->crumb = $LANG->line('mt_import_utility');
        }
        
        $r  = $DSP->qdiv('tableHeading', $LANG->line('mt_import_utility'));
    
        if ( ! $PREFS->ini('mt_import_file'))
        {
            $r .= $DSP->qdiv('tableHeadingAlt', $DSP->heading($LANG->line('mt_import_welcome'), 5));
        }
            
        if ( ! $PREFS->ini('mt_import_file'))
        {
            $r .= $DSP->qdiv('box', $this->mt_file_form($msg));
        }
        else
        {            
            $r .= $this->check_file();
        }    
    
        $DSP->body = $r;
    } 
    /* END */
        
    
    /** -------------------------------------------
    /**  MT File form
    /** -------------------------------------------*/
    
    function mt_file_form($message = '')
    {
        global $DSP, $IN, $LANG, $DB, $PREFS, $SESS;
		
		$mt_file   = ( ! $IN->GBL('mt_file', 'POST'))  ? '' : $_POST['mt_file'];
            
		// Find weblogs
		$sql = "SELECT weblog_id, blog_title, status_group, cat_group, field_group FROM exp_weblogs";
		
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".$DB->escape_str(UB_BLOG_ID)."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n'";
		}
		
		$sql .= " ORDER BY blog_title";
		
		$query = $DB->query($sql);
		
		$w = '';
		$first_stati_group	= '';
		$first_cat_group	= '';
		
		$first_field_group	= '';
		$second_field_group	= '';
		$third_field_group	= '';
		$fourth_field_group	= '';		

		/** --------------------------
		/**  Weblog Options
		/** --------------------------*/
		
		foreach ($query->result as $row)
		{
			$w .= $DSP->input_select_option($row['weblog_id'], $row['blog_title'])."\n";
			
			if ( ! isset($first_weblog))
			{
				$first_weblog = array($row['status_group'],$row['cat_group'], $row['field_group']);
			}
		}
		
		/** ---------------------------
		/**  Default Status, Categories, and Fields
		/** ---------------------------*/
		
		if ( ! isset($first_weblog) || sizeof($first_weblog) == 0)
		{
			return $this->mt_import_main_page($DSP->qdiv('alert', BR.$LANG->line('no_weblogs')));
		}
		else
		{
			$results = $DB->query("SELECT status FROM exp_statuses
								   WHERE group_id = '".$DB->escape_str($first_weblog['0'])."'
								   ORDER BY status");
                                                    
			if ($results->num_rows > 0)
			{
				foreach ($results->result as $cat_row)
				{
					$selected = ($cat_row['status'] == 'open') ? 'y' : '';
					$first_stati_group .= $DSP->input_select_option($cat_row['status'], $cat_row['status'], $selected)."\n";
				}  
			}
			
			/** -------------------------------
			/**  Create Default Category Array
			/** -------------------------------*/
			
			$sql = "SELECT exp_categories.group_id, exp_categories.parent_id, exp_categories.cat_id, exp_categories.cat_name 
                	FROM exp_categories,exp_category_groups
                	WHERE exp_category_groups.group_id = exp_categories.group_id 
                	AND exp_category_groups.group_id IN ('".str_replace('|', "','", $DB->escape_str($first_weblog['1']))."')
                	AND exp_categories.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
        
			if ($SESS->userdata['weblog_id'] != 0)
			{
				$sql .= " AND exp_categories.group_id = '".$DB->escape_str($query->row['cat_id'])."'";
			}
			else
			{
				$sql .= " AND exp_category_groups.is_user_blog = 'n'";
			}
        
			$sql .= " ORDER BY group_id, parent_id, cat_name";
        
			$query = $DB->query($sql);
            
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$categories[$row['cat_id']] = array($row['group_id'], $row['cat_name'], $row['parent_id']);
				}
        
				foreach($categories as $key => $val)
				{
					if (0 == $val['2']) 
					{
            			$this->cat_array[] = array($val['0'], $key, $val['1']);
            			$this->category_subtree($key, $categories, $depth=1);
        			}
        		}	
        	} 
			
			foreach ($this->cat_array as $k => $v)
            {
            	$first_cat_group .= $DSP->input_select_option($v['1'],str_replace(' ','&nbsp;',$v['2']))."\n";
            }
            
            $this->cat_array = array();
            
            
            /** --------------------------
            /**  Default Field Values
            /** --------------------------*/
            
            $field_results = $DB->query("SELECT field_label, field_id 
            					   FROM exp_weblog_fields 
            					   WHERE field_type IN ('textarea', 'text', 'select')
            					   AND group_id = '".$DB->escape_str($first_weblog['2'])."' 
            					   ORDER BY field_order");
        
        	if ($field_results->num_rows > 0)
			{
				$e = 0;
				foreach ($field_results->result as $field_row)
				{
					$chosen = ($field_row['field_label'] == 'Summary' || $e == 0) ? 'y' : ''; 
					$first_field_group	.= $DSP->input_select_option($field_row['field_id'],$field_row['field_label'], $chosen)."\n";
					$chosen = ($field_row['field_label'] == 'Body' || $e == 1) ? 'y' : ''; 
					$second_field_group	.= $DSP->input_select_option($field_row['field_id'],$field_row['field_label'], $chosen)."\n";
					$chosen = ($field_row['field_label'] == 'Extended text' || $e == 2) ? 'y' : ''; 
					$third_field_group	.= $DSP->input_select_option($field_row['field_id'],$field_row['field_label'], $chosen)."\n";
					$chosen = ($field_row['field_label'] == 'Keywords' || $e == 3) ? 'y' : ''; 
					$fourth_field_group	.= $DSP->input_select_option($field_row['field_id'],$field_row['field_label'], $chosen)."\n";
					$e++;
				}
            }     
            
		}
	        
        
        /** -----------------------------
        /**  File Location on Server
        /** -----------------------------*/
        
        $r  = $DSP->heading($LANG->line('import_info'));
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('file_blurb')));

		if ($message != '')
		{
			$r .= $DSP->qdiv('itemWrapper',$DSP->qdiv('alert',$message));
        }
        
		$r .= $DSP->qdiv('simpleLine', NBS);                  
        
		$r .= $this->filtering_menus('mt_form');

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=mt_import'.AMP.'F=check','name' => 'mt_form'));
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('file_info')).
			  $DSP->qdiv('itemWrapper', $LANG->line('file_blurb2')).
			  $DSP->input_text('mt_file', $mt_file, '40', '70', 'input', '300px').
			  $DSP->div_c();
			  
		$r .= $DSP->qdiv('simpleLine', NBS);                  
        
        /** --------------------------
		/**  Weblog Pull-down Menu
		/** --------------------------*/
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('weblog_select'));
             
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('field_blurb'));
            
		// Had to write this out since function did not allow addition of JS
		$r .= "\n".'<select name="weblog_id" class="select" onchange="changemenu(this.selectedIndex);">'."\n";
            
		$r .= $w;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
            
		/** ----------------------------
		/**  Category Multi-select Menu
		/** ----------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('category_select'));
            
		$r .= $DSP->input_select_header('category[]', 'y',8);
             
		$r .= $DSP->input_select_option('auto_sub', $LANG->line('auto_create_sub'),'y');
		$r .= $DSP->input_select_option('auto_nosub', $LANG->line('auto_create_nosub'));
		$r .= $DSP->input_select_option('none', $LANG->line('none'));
		$r .= $DSP->input_select_option('none', '--------');
        $r .= $first_cat_group;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		/** ----------------------------
		/**  Excerpt Pull-down Menu
		/** ----------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('excerpt_select'));
            
		$r .= $DSP->input_select_header('excerpt_id');
             
		$r .= $DSP->input_select_option('none', $LANG->line('none'));
            
		$r .= $first_field_group;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		
		/** ----------------------------
		/**  Body Pull-down Menu
		/** ----------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('body_select'));
            
		$r .= $DSP->input_select_header('body_id');
             
		$r .= $DSP->input_select_option('none', $LANG->line('none'));
            
		$r .= $second_field_group;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		
		/** ------------------------------
		/**  Extended Pull-down Menu
		/** ------------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('extended_select'));
            
		$r .= $DSP->input_select_header('extended_id');
             
		$r .= $DSP->input_select_option('none', $LANG->line('none'));
            
		$r .= $third_field_group;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		
		/** ------------------------------
		/**  Keywords Pull-down Menu
		/** ------------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('keywords_select'));
            
		$r .= $DSP->input_select_header('keywords_id');
             
		$r .= $DSP->input_select_option('none', $LANG->line('none'));
            
		$r .= $fourth_field_group;
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		/** ------------------------------
		/** Extra Fields
		/** ------------------------------*/
		
		$i = 1;
		
		if (is_numeric($this->extra_fields) && $this->extra_fields > 0)
		{
			while(TRUE)
			{
				$r .= $DSP->div('itemWrapper').
			  		   $DSP->qdiv('itemTitle', $LANG->line('field_for_extra_field').NBS.'EXTRA FIELD-'.$i);
            
				$r .= $DSP->input_select_header('extra_field_'.$i.'_id');
					 
				$r .= $DSP->input_select_option('none', $LANG->line('none'));
					
				if ($field_results->num_rows > 0)
				{
					foreach ($field_results->result as $field_row)
					{
						$r .= $DSP->input_select_option($field_row['field_id'], $field_row['field_label'], '')."\n";
					}
				} 
			
				$r .= $DSP->input_select_footer();
					
				$r .= $DSP->div_c();
			
				$i++;
				
				if ($i > $this->extra_fields)
				{
					break;
				}
			}
			
			$r .= $DSP->qdiv('simpleLine', NBS);
		}
		
		
		/** ----------------------------
		/**  Status Pull-down Menu
		/** ----------------------------*/
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('status_select'));
            
		$r .= $DSP->input_select_header('status_default');
            
		if ($first_stati_group != '')
		{
			$r .= $first_stati_group;
		}
		else
		{
			$r .= $DSP->input_select_option('open',$LANG->line('open') , 'y')."\n";
			$r .= $DSP->input_select_option('closed',$LANG->line('closed'))."\n";
		}
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('itemTitle',$LANG->line('use_status')).
			  $DSP->input_checkbox('use_status', 'y', 1).
			  NBS.
			  NBS.NBS.$LANG->line('use_status_text'));
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		
		/** ----------------------------
		/**  Formatting Options
		/** ----------------------------*/
		
		if ( ! class_exists('PublishAdmin'))
		{
			require PATH_CP.'cp.publish_ad'.EXT;
		}
		
		$PA = new PublishAdmin;
		$plugins = $PA->fetch_plugins();
		$LANG->fetch_language_file('publish_ad');		
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('format_select'));
            
		$r .= $DSP->input_select_header('format_default');
		
		$r .= $DSP->input_select_option('none',$LANG->line('none'))."\n"; 
            
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
        	
        	$r .= $DSP->input_select_option($val,$name)."\n";        	
       	}
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('itemTitle',$LANG->line('use_format')).
			  $DSP->input_checkbox('use_format', 'y', 1).
			  NBS.
			  NBS.NBS.$LANG->line('use_format_text').
			  BR.BR.$LANG->line('use_format_text2'));
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		
		/** ----------------------------
		/**  Members Pull-down Menu
		/** ----------------------------*/
        
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('admin_select'));
            
		$r .= $DSP->input_select_header('member_id');
            
		$query =$DB->query("SELECT exp_members.member_id, exp_members.screen_name, exp_members.username 
							FROM exp_members, exp_member_groups
							WHERE exp_member_groups.group_id = exp_members.group_id
							AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
							AND ((exp_member_groups.can_access_cp = 'y'
							AND exp_member_groups.can_access_publish = 'y')
							OR (exp_members.group_id = '1'))");
    
		foreach ($query->result as $row)
		{
			if ($row['screen_name'] == '')
			{
				$row['screen_name'] = $row['username'];
			}
			
			$r .= $DSP->input_select_option($row['member_id'], $row['screen_name']);
		}
    
		$r .= $DSP->input_select_footer();
            
		$r .= $DSP->div_c();
		
		$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('itemTitle',$LANG->line('use_author')).
			  $DSP->input_checkbox('use_author', 'y', 1).
			  NBS.
			  NBS.NBS.$LANG->line('use_author_text'));
		
		/** ----------------------------
		/**  Create Members from Commenters
		/** ----------------------------*/
		
		$r .=  $DSP->qdiv('simpleLine', NBS);
		
		$r .= BR.$DSP->qdiv('itemWrapper', $DSP->qdiv('itemTitle',$LANG->line('create_comment_members')).
			  $DSP->input_checkbox('create_comment_members', 'y', 1).
			  NBS.
			  NBS.NBS.$LANG->line('create_commenters_text'));
			  
		
		$r .= BR.$DSP->div('itemWrapper').$DSP->qdiv('itemTitle',$LANG->line('comment_group'));		
		
		if ($SESS->userdata['group_id'] != 1)
		{
			$sql = "SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_locked = 'n' order by group_title";
		}
		else
		{
			$sql = "SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' order by group_title";
		}
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			$r .= $LANG->line('no_member_groups');
		}
		else
		{
			$r .= $DSP->input_select_header('member_group_id');
			
			foreach ($query->result as $row)
			{
				$selected = ($row['group_id'] == 5) ? 1 : '';
				
				if ($row['group_id'] == 1 AND $SESS->userdata['group_id'] != 1)
				{
					continue;
				}
				
				$group_title = $row['group_title'];
				
				if (in_array($group_title, $this->english))
				{
					$group_title = $LANG->line(strtolower(str_replace(" ", "_", $group_title)));
				}
				
				$r .= $DSP->input_select_option($row['group_id'], $group_title, $selected);
			}
				
			$r .= $DSP->input_select_footer();
		}
		
		$r .= $DSP->div_c();
		
		$r .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('submit_info'), 'submit'));
	
		$r .= $DSP->form_close();

        return $r;
    }    
    /* END */
    
    
    
   /** -------------------------------------------
    /**  Check File and Save Location in Config File
    /** -------------------------------------------*/
    
    function check_file()
    {
        global $DSP, $IN, $FNS, $LANG;
        
        // Check for required fields
        
        $required = array('mt_file', 'weblog_id', 'category', 'excerpt_id', 'body_id', 'extended_id', 'keywords_id', 'format_default', 'member_id');
        
        if ($this->extra_fields > 0)
        {
        	$i = 1;
        	
        	while(TRUE)
        	{
        		$required[] = 'extra_field_'.$i.'_id';
        		
        		$i++;
				
				if ($i > $this->extra_fields)
				{
					break;
				}
        	}
        }
        
        foreach($required as $field)
        {
			if ( ! isset($_POST[$field]) || $_POST[$field] == '')
			{
        	 	return $this->mt_import_main_page($DSP->qdiv('alert', BR.$LANG->line('empty_field_warning')));
			}        
        }
        
        $realpath = realpath($_POST['mt_file']);
        
        if ( ! file_exists($realpath))
        {
        	return $this->mt_import_main_page($DSP->qdiv('alert', BR.$LANG->line('invalid_path')));
        }
        
        if ( ! function_exists('file_get_contents'))
        {
        	$lines = file($realpath);
        	$data = implode('', $lines);
        	unset($lines);
        }
        else
        {
        	$data = file_get_contents($realpath);
        }
        
        if (strpos($data,'--------') === false)
        {
        	return $this->mt_import_main_page($DSP->qdiv('alert', BR.$LANG->line('invalid_file')));
        }
     	
     	unset($data);
     	
     	/** -------------------------------
     	/**  Category Information
     	/** -------------------------------*/
     	
     	if (is_array($_POST['category']))
		{
			$temp = '';
			foreach($_POST['category'] as $post_value)
			{
				$temp .= $IN->clean_input_data($post_value)."|";
			}
			
			$_POST['category'] = (strlen($temp) > 1 && substr($temp,-1) == '|') ? substr($temp,0,-1) : $temp;
		}
     	
     
     	// Write the new (and much larger) meta data to the config file
     	// FIELDS:  mt_file, weblog_id, category, excerpt_id, body_id, extended_id, keywords_id
        // status_default, use_status, format_default, use_format, member_id, use_author
        // create_comment_members, member_group_id
        
        $pm_config = array(
                            'mt_file_location'			=> addslashes($realpath),
                            'mt_weblog_selection'		=> $_POST['weblog_id'],                            
                            'mt_category_selection'		=> $_POST['category'],
                            'mt_excerpt_selection'		=> $_POST['excerpt_id'],
                            'mt_body_selection'			=> $_POST['body_id'],
                            'mt_extended_selection'		=> $_POST['extended_id'],
                            'mt_keywords_selection'		=> $_POST['keywords_id'],
                            
                            'mt_status_default'			=> ( ! isset($_POST['status_default']) || $_POST['status_default'] == '') ? 'closed' : $_POST['status_default'],
                            'mt_use_status'				=> ( ! isset($_POST['use_status']) || $_POST['use_status'] == '') ? 'n' : $_POST['use_status'],
                            'mt_format_default'			=> ( ! isset($_POST['format_default']) || $_POST['format_default'] == '') ? 'br' : $_POST['format_default'],
                            'mt_use_format'				=> ( ! isset($_POST['use_format']) || $_POST['use_format'] == '') ? 'n' : $_POST['use_format'],
                            
                            'mt_member_id'				=> $_POST['member_id'],
                            'mt_use_author'				=> ( ! isset($_POST['use_author']) || $_POST['use_author'] == '') ? 'n' : $_POST['use_author'],
                            
                            'mt_create_comment_members'	=> ( ! isset($_POST['create_comment_members']) || $_POST['create_comment_members'] == '') ? 'n' : $_POST['create_comment_members'],
                            'mt_member_group_id'		=> ( ! isset($_POST['member_group_id']) || $_POST['member_group_id'] == '') ? '5' : $_POST['member_group_id']
                            );
                            
		if ($this->extra_fields > 0)
        {
        	$i = 1;
        	
        	while(TRUE)
        	{
        		$pm_config['mt_extra_field_'.$i.'_selection'] = $_POST['extra_field_'.$i.'_id'];
        		
        		$i++;
				
				if ($i > $this->extra_fields)
				{
					break;
				}
        	}
        }
        
        Admin::append_config_file($pm_config);
        
        
        /** -----------------------------------------
        /**  Redirect to main import page
        /** -----------------------------------------*/

        $FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=mt_import'.AMP.'F=perform_import');
        exit;     
    }
    /* END */
    
        
    /** -------------------------------------------
    /**  Import File and Find category info
    /** -------------------------------------------*/
    
    function perform_import()
    {
        global $DSP, $IN, $FNS, $LANG, $PREFS, $DB;
        global $STAT, $IN, $LOC, $REGX, $SESS;
        
        /** --------------------------
        /**  MT Import Settings
        /** --------------------------*/
        
        $file_location 			= $PREFS->ini('mt_file_location');			
        $weblog_selection 		= $PREFS->ini('mt_weblog_selection');		
        $category_selection 	= $PREFS->ini('mt_category_selection');	
        $summary_selection 		= $PREFS->ini('mt_excerpt_selection');		
        $body_selection 		= $PREFS->ini('mt_body_selection');		
        $extended_selection 	= $PREFS->ini('mt_extended_selection');
        $keywords_selection 	= $PREFS->ini('mt_keywords_selection');

        $status_default 		= $PREFS->ini('mt_status_default');	
        $use_status 			= $PREFS->ini('mt_use_status');		
        $format_default 		= $PREFS->ini('mt_format_default');	
        $use_format 			= $PREFS->ini('mt_use_format');		

        $member_id 				= $PREFS->ini('mt_member_id');	
        $use_author 			= $PREFS->ini('mt_use_author');	

        $create_comment_members	= $PREFS->ini('mt_create_comment_members');
        $member_group_id		= $PREFS->ini('mt_member_group_id');
        
        $members_created		= 0;
        
        /** --------------------------
        /**  Create Categories defaults
        /** --------------------------*/
        
        $auto_sub = 'n';
        $auto_nosub = 'n';
        
        if(strpos($category_selection,'auto_sub') !== false)
        {
        	$auto_sub = 'y';        
        }
        elseif(strpos($category_selection,'auto_nosub') !== false)
        {
        	$auto_nosub = 'y'; 
        }
	
		/** ---------------------------------
		/**  At Least One Field Must Be Used
		/** ---------------------------------*/
		
		$fields = array('summary', 'body', 'extended', 'keywords');
		
		$set = 'n';
		foreach ($fields as $field)
		{
			$name = $field.'_selection';
			if (${$name} != '' && ${$name} != 'none')
			{
				$set = 'y';
			}
		}
        
        if ($set == 'n')
        {
        	return $this->mt_import_main_page($LANG->line('unable_to_import'));
        }
        
		/** ---------------------------------
		/**  Extra Fields
		/** ---------------------------------*/
		
		if ($this->extra_fields > 0)
        {
        	$i = 1;
        	
        	while(TRUE)
        	{
        		// Preference
        		$name  = 'extra_field_'.$i.'_selection';
        		$$name = $PREFS->ini('mt_extra_field_'.$i.'_selection');
        		
        		// Storage Array for Data
        		$name  = 'extra_field_'.$i;
        		$$name = array();
        		
        		// Fields List for Processing
        		$fields[] = $name;
        		
        		$i++;
				
				if ($i > $this->extra_fields)
				{
					break;
				}
        	}
        }
        
        
        
        /** -----------------------------
        /**  Valid Member Group Check
        /** -----------------------------*/
		
		if ($create_comment_members	== 'y')
		{
			if ($SESS->userdata['group_id'] != 1)
			{
				$sql = "SELECT COUNT(*) AS count 
						FROM exp_member_groups 
						WHERE is_locked = 'n' 
						AND group_id = '{$member_group_id}'
						AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
						ORDER BY group_title";
				
				$query = $DB->query($sql);
				
				if ($query->row['count'] == 0 || $member_group_id == '')
				{
					return $this->mt_import_main_page($LANG->line('unable_to_import'));
				}
			}
		}
		
		/** --------------------------
		/**  Valid Stati for Weblog
		/** --------------------------*/
		
		$query = $DB->query("SELECT status FROM exp_statuses, exp_weblogs
							 WHERE exp_weblogs.status_group = exp_statuses.group_id
							 AND exp_weblogs.weblog_id = '{$weblog_selection}'");
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->status_array[]  = strtolower($row['status']);
        	}
        }
        else
        {
        	$this->status_array = array('open','closed');	
        }  
        
        /** ----------------------------
		/**  Valid Formatting Options
		/** ----------------------------*/
		
		if ( ! class_exists('PublishAdmin'))
		{
			require PATH_CP.'cp.publish_ad'.EXT;
		}
		
		$PA = new PublishAdmin;
		$plugins = $PA->fetch_plugins();
        $LANG->fetch_language_file('publish_ad');

        /** --------------------------------------
        /**  MT IMPORT BEGINS
        /** --------------------------------------*/
        
        if ( ! function_exists('file_get_contents'))
        {
        	$lines = file($file_location);
        	$data = implode('', $lines);
        	unset($lines);
        }
        else
        {
        	$data = file_get_contents($file_location);
        }
        
        // All tabs into spaces.
        $data = preg_replace("/(\t)/", ' ', $data);
        
        // Make all line breaks into one type of identifiable line break marker.
        $LB = '9serLBR3ngti';
        $data = preg_replace("/(\r\n|\n|\r)/", $LB, $data);
        
        if (strpos($data,$LB.'--------'.$LB) === false)
        {
        	return $this->mt_import_main_page($LANG->line('invalid_file'));
        }        
        
        // Break it up by entries.
        $entries = explode($LB.'--------'.$LB, $data);
        unset($data);
        
        // Our various data arrays
        $titles		= array();
        $dates		= array();
        $body		= array();
        $extended	= array();
        $summary	= array();
        $keywords	= array();
        
        $author				= array(); // Author of entry
        $screen_names		= array(); // Screen names used
        $usernames			= array(); // Usernames used
        $ip_addresses		= array(); // IP addresses used
        $no_screen_names	= array(); // Confirmed screen name with no member
        $no_usernames		= array(); // Confirmed username with no member
        
        $comments	= array();
        $trackbacks	= array();
        
        $allow_comments	= array();
        $allow_pings	= array();
        $convert_breaks	= array();
        $status			= array();
        
        $primary_categories	= array();
        $categories			= array();
        
        $comment_members		= array();
        $comment_members_data	= array();
        $comment_members_email	= array();
        
        $id = 0;
        
		foreach($entries as $entry)
        {	
        	if (trim($entry) == '')
        	{
        		continue;
        	}
        	
        	$sections = explode($LB."-----".$LB,$entry);
        	
        	unset($entry);
        	
        	// We expect at least two sections
        	if ( ! isset($sections['1']))
        	{
        		unset($sections);
        		continue;
        	}
        	
        	/** -----------------------------------
        	/**  Grab entry data and put into arrays 
        	/** -----------------------------------*/
        	
        	$first_section = explode($LB,$sections['0']);
        	$allow_comments[$id] = 1;
        	$allow_pings[$id] = 0;
        	$convert_breaks[$id] = $format_default;
        	$status[$id] = $status_default;
        	$member[$id] = $member_id;
        	
        	for ($i=0; $i < sizeof($first_section); $i++)
        	{
        		if (trim($first_section[$i]) == '')
        		{
        			continue;
        		}
        		
        		$parts = explode(':',$first_section[$i]);
        		if (sizeof($parts) < 2)
        		{
        			continue;
        		}
        		
        		// TITLE
        		if (strpos($parts['0'],'TITLE') !== false)
				{
        			$titles[$id] = trim(str_replace('TITLE:','',$first_section[$i]));
        		}
                      
				// DATE - keep in format, change later
				if (strpos($parts['0'],'DATE') !== false)
				{
				     $dates[$id] = trim(str_replace('DATE:','',$first_section[$i]));
				}
				
				// STATUS
				if (strpos($parts['0'], 'STATUS') !== false && $use_status == 'y')
				{
					$temp_status = trim(str_replace('STATUS:','',$first_section[$i]));
					if ($temp_status == 'Publish')
					{
						$status[$id] = 'open';
					}
					elseif ($temp_status == 'Draft')
					{
						$status[$id] = 'closed';
					}
					elseif(in_array(strtolower($temp_status),$this->status_array))
					{
						$which = array_search(strtolower($temp_status),$this->status_array);
						$status[$id] = $this->status_array[$which];
					}
				}
				
				// AUTHOR
				if (strpos($parts['0'], 'AUTHOR') !== false && $use_author == 'y')
				{
					$author[$id] = trim(str_replace('AUTHOR:','',$first_section[$i]));					
				}
				
				// META DATA
				if (strpos($parts['0'],'ALLOW COMMENTS') !== false)
				{
				     $allow_comments[$id] = trim(str_replace('ALLOW COMMENTS:','',$first_section[$i]));
				}
				if (strpos($parts['0'],'ALLOW PINGS') !== false)
				{
				     $allow_pings[$id] = trim(str_replace('ALLOW PINGS:','',$first_section[$i]));
				}
				if (strpos($parts['0'],'CONVERT BREAKS') !== false && $use_format == 'y')
				{
					$temp_format = trim(str_replace('CONVERT BREAKS:', '',$first_section[$i]));
					
					if ($temp_format == '1')
					{
						$convert_breaks[$id] = 'br';
					}
					elseif($temp_format == '0')
					{
						$convert_breaks[$id] = 'none';
					}
					elseif($temp_format == '__default__')
					{
						$convert_breaks[$id] = $format_default;
					}
					else
					{
						foreach ($plugins as $val)
        				{   
        					if ($temp_format == $val)
        					{
        						$convert_breaks[$id] = $val;
        						break;
        					}
        					
        					$name = ucwords(str_replace('_', ' ', $val));
        			
							if ($name == 'Br')
							{
								$name = $LANG->line('auto_br');
							}
							elseif ($name == 'Xhtml')
							{
								$name = $LANG->line('xhtml');
							}
        					
        					if ($temp_format == $val)
        					{
        						$convert_breaks[$id] = $val;
        						break;
        					}
        				}
       				}
				}
				
				// PRIMARY CATEGORY
				if (strpos($parts['0'],'PRIMARY CATEGORY') !== false)
				{
					$primary_categories[$id] = trim(str_replace('PRIMARY CATEGORY:','',$first_section[$i]));
				}
				
				// CATEGORY 
				elseif (strpos($parts['0'],'CATEGORY') !== false)
				{
					// Catch for people who make primary and category equal to each other.
					if (isset($primary_categories[$id]) && trim($parts['1']) == $primary_categories[$id])
					{
						continue;
					}
					
					$categories[$id][] = trim(str_replace('CATEGORY:','',$first_section[$i]));
				}
			}
			// End section 1
                
			// More MT logic:
			// If no primary category and there is a single category, then category becomes primary category
			if ( ! isset($primary_categories[$id]) && isset($categories[$id]) && sizeof ($categories[$id]) > 0)
			{
				$primary_categories[$id] = $categories[$id]['0'];
				unset($categories[$id]['0']);
			}
			
			// Data Check
			if ( ! isset($dates[$id]) || ! isset($titles[$id]) || str_replace($LB, '', trim($titles[$id])) == '' || str_replace($LB, '', trim($dates[$id])) == '')
			{
				continue;
			}
                      
			// Go through the rest of the sections
		
			for ($i=1; $i < sizeof ($sections); $i++)
			{
				// EXTENDED BODY
				preg_match("/EXTENDED BODY:(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['1']))
				{
					$extended[$id] = trim($meta_info['1']);
					continue;
				}
				
				// EXTRA FIELD
				preg_match("/EXTRA FIELD\-(\d+?):(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['2']))
				{
					$name  = 'extra_field_'.$meta_info['1'];
        			${$name}[$id] = trim($meta_info['2']);
					continue;
				}
                      
				// BODY
				preg_match("/BODY:(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['1']))
				{
				     $body[$id] = trim($meta_info['1']);
				     continue;
				}
				
				// EXCERPT
				preg_match("/EXCERPT:(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['1']))
				{
				     $summary[$id] = trim($meta_info['1']);
				     continue;
				}
				
				// KEYWORDS
				preg_match("/KEYWORDS:(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['1']))
				{
				     $keywords[$id] = trim($meta_info['1']);
				     continue;
				}
				
				// COMMENTS
				preg_match("/COMMENT:(.*)/", $sections[$i], $meta_info);
				if (isset($meta_info['1']))
				{
					if ( ! isset($c)) $c = 0;
					$cparts = explode($LB, $meta_info['1']);
					
					foreach($cparts as $cpart)
					{
						if (strpos($cpart,'AUTHOR:') !== false)
						{
							$comments[$id][$c]['author'] = trim(str_replace('AUTHOR:','',$cpart));
							$meta_info['1'] = str_replace($cpart.$LB,'',$meta_info['1']);
						}
						elseif (strpos($cpart,'DATE:') !== false)
						{
						     $comments[$id][$c]['date'] = trim(str_replace('DATE:','',$cpart));
						     $meta_info['1'] = str_replace($cpart.$LB,'',$meta_info['1']);
						}
						elseif (strpos($cpart,'EMAIL:') !== false)
						{
						     $comments[$id][$c]['email'] = trim(str_replace('EMAIL:','',$cpart));
						     $meta_info['1'] = str_replace($cpart.$LB,'',$meta_info['1']);
						}
						elseif (strpos($cpart,'URL:') !== false)
						{
						     $comments[$id][$c]['url'] = trim(str_replace('URL:','',$cpart));
						     $meta_info['1'] = str_replace($cpart.$LB,'',$meta_info['1']);
						}
						elseif (strpos($cpart,'IP:') !== false)
						{
						     $comments[$id][$c]['ip'] = trim(str_replace('IP:','',$cpart));
						     $meta_info['1'] = str_replace($cpart.$LB,'',$meta_info['1']);
						}
					}
					
					// Required
					if ( ! isset($comments[$id][$c]['author']) || ! isset($comments[$id][$c]['date']))
					{
						unset($comments[$id][$c]);
						continue;
					}
        		     
					// Clean up comment body
					$meta_info['1'] = str_replace('COMMENT:'.$LB, '', $meta_info['1']);
					while(substr($meta_info['1'],0,strlen($LB)) == $LB)
					{
					     $meta_info['1'] = substr($meta_info['1'], strlen($LB));
					}
					
					while(substr($meta_info['1'],-strlen($LB)) == $LB)
					{
					     $meta_info['1'] = substr($meta_info['1'], 0, -strlen($LB));
					}
					
					/** -------------------------
					/**  Store comment body
					/** -------------------------*/
					
					$comments[$id][$c]['body'] = trim($meta_info['1']);
					
					/** ------------------------
					/**  Store Comment User
					/** ------------------------*/
					
					if ($create_comment_members == 'y' && isset($comments[$id][$c]['email']))
					{
						if (!in_array(strtolower($comments[$id][$c]['author']),$comment_members) && !in_array(strtolower($comments[$id][$c]['email']),$comment_members_email))
						{
							$comment_members[]			= $comments[$id][$c]['author'];	// Unique authors
							$comment_members_email[]	= $comments[$id][$c]['email'];	// Unique emails
							$comment_members_data[]		= $comments[$id][$c]; 
						}
					}
					
					$c++; // C++, get it? Ha!
					continue;
        	     }
        	     
        	     // TRACKBACKS
        	     preg_match("/PING:(.*)/", $sections[$i], $meta_info);
        	     if (isset($meta_info['1']))
        	     {
        	     	if ( ! isset($t)) $t = 0;
        	     	$tparts = explode($LB, $meta_info['1']);
        	     	
        	     	foreach($tparts as $tpart)
        	     	{
						if (strpos($tpart,'TITLE:') !== false)
						{
						     $trackbacks[$id][$t]['title'] = trim(str_replace('TITLE:','',$tpart));
						     $meta_info['1'] = str_replace($tpart,'',$meta_info['1']);
						}
						elseif (strpos($tpart,'DATE:') !== false)
						{
						     $trackbacks[$id][$t]['date'] = trim(str_replace('DATE:','',$tpart));
						     $meta_info['1'] = str_replace($tpart,'',$meta_info['1']);
						}
						elseif (strpos($tpart,'URL:') !== false)
						{
						     $trackbacks[$id][$t]['url'] = trim(str_replace('URL:','',$tpart));
						     $meta_info['1'] = str_replace($tpart,'',$meta_info['1']);
						}
						elseif (strpos($tpart,'IP:') !== false)
						{
						     $trackbacks[$id][$t]['ip'] = trim(str_replace('IP:','',$tpart));
						     $meta_info['1'] = str_replace($tpart,'',$meta_info['1']);
						}
						elseif (strpos($tpart,'BLOG NAME:') !== false)
						{
						     $trackbacks[$id][$t]['blog_name'] = trim(str_replace('BLOG NAME','',$tpart));
						     $meta_info['1'] = str_replace($tpart,'',$meta_info['1']);
						}
					}
					
					// Required fields is four
					// Only IP is not required.
					if (sizeof($trackbacks[$id][$t]) < 4 && isset($trackbacks[$id][$t]['ip']))
					{
						unset($trackbacks[$id][$t]);
						continue;
					}
					
					// Clean up Trackback body
					$meta_info['1'] = str_replace('PING:'.$LB, '', $meta_info['1']);
					while(substr($meta_info['1'],0,strlen($LB)) == $LB)
					{
						$meta_info['1'] = substr($meta_info['1'], strlen($LB));
					}
					
					while(substr($meta_info['1'],-strlen($LB)) == $LB)
					{
						$meta_info['1'] = substr($meta_info['1'], 0, -strlen($LB));
					}
					
					// Store trackback body
					$trackbacks[$id][$t]['body'] = trim($meta_info['1']);
                       
					$t++;
				}
			}
			// End of all sections
			
			// Data Check
			if ( ! isset($body[$id]) || str_replace($LB, '', trim($body[$id]) == ''))
			{
				continue;
			}
			
			$id++;
        	$c = 0;
        	$t = 0;
		}
        
        
        /** -----------------------------
        /**  Category Creation
        /** -----------------------------*/
        
        // Find category group for this weblog
		$query = $DB->query("SELECT cat_group, site_id FROM exp_weblogs WHERE weblog_id = '{$weblog_selection}'");

		$weblog_cat_id = $query->row['cat_group'];
		$site_id = $query->row['site_id'];
        
        if ($auto_sub == 'y' || $auto_nosub == 'y')
        {
        	$cleaned_primary_categories = array_unique($primary_categories);
        	$cleaned_categories = array();        
        	
			// Find Unique Categories, store via primary category name
			foreach ($categories as $eid => $cat_array)
			{
				foreach ($cat_array as $cat)
				{
					$pim = (isset($primary_categories[$eid]) && $primary_categories[$eid] != $cat) ? $primary_categories[$eid] : 0;
					$pim = $IN->clean_input_data($pim);
					
					if ( ! isset($cleaned_categories[$pim]))
					{
						$cleaned_categories[$pim][] = $cat;
						continue;
					}
					
					if ( ! in_array($cat,$cleaned_categories[$pim]))
					{
						$cleaned_categories[$pim][] = $cat;
					}
				}
			}
	
			// Category ID Arrays
			$primary_cat_ids = array();
			$regular_cat_ids = array();
        
			// Check for these primary categories.  If not there, create
			if (sizeof($cleaned_primary_categories) > 0)
			{
				foreach($cleaned_primary_categories as $key => $prim)
				{
				    $name = $IN->clean_input_data($prim);
				    
				    if ($name == '')
				    {
			        	continue;
			    	}
			    
			    	$query =$DB->query("SELECT cat_id
			    	                   	FROM exp_categories
			    	                   	WHERE exp_categories.cat_name = '".$DB->escape_str($name)."'
			    	                   	AND exp_categories.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
			    	                   	AND exp_categories.parent_id = '0'
			    	                   	AND exp_categories.group_id IN ('".str_replace('|', "','", $DB->escape_str($weblog_cat_id))."')");
			                                     
			    	if ($query->num_rows == 0)
			    	{	
			    		// Create primary category
			    		$insert_array = array('group_id'  		=> $weblog_cat_id,
			    	                          'cat_name' 		=> $name,
			    	                          'cat_url_title'	=> $REGX->create_url_title($name, TRUE),
			    	                          'cat_image' 		=> '',
			    	                          'parent_id'   	=> '0',
			    	                          'site_id'			=> $site_id
			    	                          );
			    	
			    		$DB->query($DB->insert_string('exp_categories', $insert_array));       
			    		$primary_cat_ids[$name] = $DB->insert_id;

						// Create category_field_data
			    		$insert_array = array('cat_id'	 		=> $primary_cat_ids[$name],
			    	                          'site_id'			=> $site_id,
											  'group_id'  		=> $weblog_cat_id
			    	                          );
			    	
			    		$DB->query($DB->insert_string('exp_category_field_data', $insert_array));
			    	}
			    	else
			    	{
			    	     $primary_cat_ids[$name] = $query->row['cat_id'];
			    	     unset($cleaned_primary_categories[$key]);
			    	}
				}
			}
			// End creation of primary categories
			
		
			// Check for these categories.  If not there, create.
			if (sizeof($cleaned_categories) > 0)
			{
				foreach($cleaned_categories as $parent_name => $cat_array)
				{				    
				    if ($parent_name == '' || sizeof($cat_array) == 0)
				    {
				         continue;
				    }
				    
					$pid = 0;
					
				    if ($auto_sub == 'y')
				    {	
				    	$sql = "SELECT cat_id 
				    			FROM exp_categories
				    			WHERE exp_categories.cat_name = '".$DB->escape_str($parent_name)."'
				    			AND exp_categories.group_id = '{$weblog_cat_id}'
				    			AND exp_categories.parent_id = '0'
				    			AND exp_categories.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
				    		
				   		$query = $DB->query($sql);
				    	$pid = ($query->num_rows > 0) ? $query->row['cat_id'] : '0';
				    }
				    
				    foreach($cat_array as $cid => $cat)
				    {
				    	$cat = $IN->clean_input_data($cat);
				    	
				    	$query = $DB->query("SELECT cat_id
			    							FROM exp_categories
			    							WHERE exp_categories.cat_name = '".$DB->escape_str($cat)."'
			    							AND exp_categories.group_id = '{$weblog_cat_id}'
			    							AND exp_categories.parent_id = '{$pid}'
			    							AND exp_categories.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				    	
			                                     
			    		if ($query->num_rows == 0)
			    		{
			    			// Create category
			    			$insert_array = array(	'group_id'		=> $weblog_cat_id,
			    									'cat_name' 		=> $cat,
			    									'cat_url_title' => $REGX->create_url_title($cat, TRUE),
			    									'cat_image' 	=> '',
			    									'parent_id'		=> $pid,
			    									'site_id'		=> $site_id
			    									);
			    	
			    			$sql = $DB->insert_string('exp_categories', $insert_array);   
			    			$DB->query($sql);  
							$cat_insert_id = $DB->insert_id;
			    			$regular_cat_ids[$cat_insert_id] = array($cat,$pid);
							
							// Create category_field_data
				    		$insert_array = array('cat_id'	 		=> $cat_insert_id,
				    	                          'site_id'			=> $site_id,
												  'group_id'  		=> $weblog_cat_id
			    		                          );
			    	
			    			$DB->query($DB->insert_string('exp_category_field_data', $insert_array));
			    		}
			    		else
			    		{
			       		 	$regular_cat_ids[$query->row['cat_id']] = array($cat,$pid);
			       		  	unset($cleaned_categories[$parent_name][$cid]);
			    		}
					}
				}
			}
		}	
		
		
		/** ----------------------------
		/**  Create Comment Memberships
		/** ----------------------------*/
		
		if ($create_comment_members	== 'y' && sizeof($comment_members_data) > 0)
		{			
			/** -------------------------------------
			/**  Instantiate validation class
			/** -------------------------------------*/
			
			if ( ! class_exists('Validate'))
			{
				require PATH_CORE.'core.validate'.EXT;
			}
				
			$members_data	= array();
			
			foreach($comment_members_data as $comment_data)
			{
				$com_name	= ( ! isset($comment_data['author'])) ? 'Anonymous' : stripslashes($comment_data['author']);
				$com_email	= ( ! isset($comment_data['email'])) ? '' : stripslashes($comment_data['email']); 
				$com_url	= ( ! isset($comment_data['url'])) ? '' : stripslashes($comment_data['url']); 
				$com_ip		= ( ! isset($comment_data['ip'])) ? '' : stripslashes($comment_data['ip']);
				
				$username = preg_replace("/[\||\'|\"|\!]/", '', $com_name);
				$password = $FNS->random('alpha',5);
		
				$VAL = new Validate(
									array( 
											'member_id'			=> '',
											'val_type'			=> 'new', // new or update
											'fetch_lang' 		=> FALSE, 
											'require_cpw' 		=> FALSE,
										 	'enable_log'		=> TRUE,
											'username'			=> $username,
											'cur_username'		=> '',
											'screen_name'		=> $com_name,
											'cur_screen_name'	=> '',
											'password'			=> $password,
										 	'password_confirm'	=> $password,
										 	'cur_password'		=> '',
										 	'email'				=> $com_email,
										 	'cur_email'			=> ''
										 )
									);
		
				$VAL->validate_username();
				$VAL->validate_screen_name();
				$VAL->validate_password();
				$VAL->validate_email();

        		if (count($VAL->errors) > 0)
         		{            
            		continue;
         		}
					
				$data['username']    = $username;
        		$data['password']    = $password;
        		$data['ip_address']  = $com_ip;
        		$data['unique_id']   = $FNS->random('encrypt');
       			$data['join_date']   = $LOC->now;
        		$data['email']       = $com_email;
        		$data['screen_name'] = $com_name;
        		$data['url']		 = $com_url;
        		$data['group_id']	 = $member_group_id;
				
				$DB->query($DB->insert_string('exp_members', $data)); 
				
				$new_member_id = $DB->insert_id;
				$members_data[] = "('{$new_member_id}')";
				$members_created++;
				
				$usernames[$new_member_id] = $username;
				$screen_names[$new_member_id] = $com_name;
				$ip_addresses[$new_member_id] = $com_ip;
			}
			
			if(sizeof($members_data) > 0)
			{
				// Create records in the custom field table
				$DB->query("INSERT INTO exp_member_data (member_id) VALUES ".implode(',',$members_data));
				
				// Create records in the member homepage table
				$DB->query("INSERT INTO exp_member_homepage (member_id) VALUES ".implode(',',$members_data));
			}
			
			$STAT->update_member_stats();
		}
		// END Comment Memberships	


		
		/** ----------------------------
		/**  Data Arrays
		/** ----------------------------*/
        
        // Get our default member's IP address
        $result = $DB->query("SELECT member_id, ip_address FROM exp_members WHERE member_id = '{$member_id}'");
        $ip_addresses[$member_id] = ($result->num_rows == 0) ? '0.0.0.0' : $result->row['ip_address'];
        $total = $id;
        $comments_entered = 0;
        $trackbacks_entered = 0;
        
        
		for ($id=0; $id < $total; $id++)
		{
			// Function to create MT Export Date format to gmt
			$entry_date = $this->convert_mt_date_to_gmt($dates[$id]);
		
			$titles[$id] = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($titles[$id]) : $titles[$id];
       	
			$url_title = $REGX->create_url_title($titles[$id], TRUE);

			$results = $DB->query("SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title = '".$DB->escape_str($url_title)."' AND weblog_id = '$weblog_selection'");
		
			// Already have default title
			if ($results->row['count'] > 0)
			{		
				/** ------------------------------------------------
				/**  Check for multiple instances like default title
				/** ------------------------------------------------*/
			
				$results = $DB->query("SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title LIKE '".$DB->escape_like_str($url_title)."%' AND weblog_id = '$weblog_selection'");
				$url_title .= $results->row['count']+1;
			}
		
			$comments_allowed = ($allow_comments[$id] ==  1) ? 'y' : 'n';
			$trackbacks_allowed = ($allow_pings[$id] ==  1) ? 'y' : 'n';
			
			/** -----------------------------
			/**  Recent Comment date
			/** -----------------------------*/
			if (isset($comments[$id]) && sizeof($comments[$id]) > 0)
			{
				$recent_comment_date = time();
				for($c=0; $c < sizeof($comments[$id]); $c++)
				{
					$date = $this->convert_mt_date_to_gmt(stripslashes($comments[$id][$c]['date']));
					if ($date < $recent_comment_date)
					{
						$recent_comment_date = $date;
					}
				}
			}
			else
			{
				$recent_comment_date = 0;
			}
			
			/** --------------------------------
			/**  Recent Trackback date
			/** --------------------------------*/
			if (isset($trackbacks[$id]) && sizeof($trackbacks[$id]) > 0)
			{
				$recent_trackback_date = time();
				
				for($t=0; $t < sizeof($trackbacks[$id]); $t++)
				{
					$date = $this->convert_mt_date_to_gmt(stripslashes($trackbacks[$id][$t]['date']));
					
					if ($date < $recent_trackback_date)
					{
						$recent_trackback_date = $date;
					}
				}
			}
			else
			{	
				$recent_trackback_date = 0;
			}
              
			$comment_total = ( ! isset($comments[$id])) ? 0 : sizeof($comments[$id]);
			$trackback_total = ( ! isset($trackbacks[$id])) ? 0 : sizeof($trackbacks[$id]);     
			
			/** -------------------------------
			/**  Determine Author?
			/** -------------------------------*/
			
			if ($use_author == 'y' && isset($author[$id]))
			{
				$temp_author = $author[$id];
				$temp_username = preg_replace("/[\||\'|\"|\!]/", '', $temp_author);
					
				if (in_array($temp_author, $no_screen_names) || in_array($temp_username, $no_usernames))
				{
					// Nothing
					// Check already done, not found, so it is default
				}
				elseif(in_array($temp_username,$usernames))
				{
					$which = array_search($temp_author,$usernames);
					$member[$id] = $which;
				}
				elseif(in_array($temp_author,$screen_names))
				{
					$which = array_search($temp_author,$screen_names);
					$member[$id] = $which;
				}
				else
				{
					// Darn! Time for a query
					$query = $DB->query("SELECT member_id, screen_name, username, ip_address FROM exp_members 
										 WHERE screen_name = '{$temp_author}' OR username = '{$temp_username}'");
						
					if($query->num_rows == 1)
					{
						$member[$id] = $query->row['member_id'];
						
						$usernames[$query->row['member_id']]	= $query->row['username'];
						$screen_names[$query->row['member_id']]	= $query->row['screen_name'];	
						$ip_addresses[$query->row['member_id']]	= $query->row['ip_address'];	
					}
					elseif ($query->num_rows > 1)
					{
						foreach($query->result as $row)
						{						
							if ($row['username'] == $temp_username)
							{
								$member[$id] = $row['member_id'];
							}
							
							$usernames[$row['member_id']]		= $row['username'];
							$screen_names[$row['member_id']]	= $row['screen_name'];
							$ip_addresses[$row['member_id']]	= $row['ip_address'];
						}
					}

					else
					{
						$no_screen_names[]	= $temp_author;
						$no_usernames[]		= $temp_username;
					}				
				}
	    	}
	    	
	    	if ( ! isset($ip_addresses[$member[$id]]))
	    	{
        		$result = $DB->query("SELECT member_id, ip_address FROM exp_members WHERE member_id = '".$DB->escape_str($member[$id])."'");
        		$ip_addresses[$member[$id]] = ($result->num_rows == 0) ? '0.0.0.0' : $result->row['ip_address'];
        	}
	    	
	    	/** -------------------------
	    	/**  Weblog Entry's Data
	    	/** -------------------------*/
			$data = array(
							'entry_id'       		=> '',
							'weblog_id'				=> $weblog_selection,
							'author_id'    			=> $member[$id],
							'ip_address'    		=> $ip_addresses[$member[$id]],
							'title'         		=> $titles[$id],
							'url_title'       		=> $url_title,
							'status'           		=> $status[$id],
							'allow_comments'  		=> $comments_allowed,
							'allow_trackbacks'		=> $trackbacks_allowed,
							'entry_date'        	=> $entry_date, // Converted to GMT above
							'year'           		=> date("Y", $entry_date), // Already converted to GMT
							'month'      			=> date("m", $entry_date), // so we just need to use date()
							'day'               	=> date("d", $entry_date), // for year, month, and day, I think.
							'expiration_date'   	=> 0,
							'recent_comment_date'   => $recent_comment_date,
							'recent_trackback_date'	=> $recent_trackback_date,
							'comment_total'      	=> $comment_total,
							'trackback_total'     	=> $trackback_total,
							'site_id'				=> $site_id
							);
                              
			$DB->query($DB->insert_string('exp_weblog_titles', $data));
			$entry_id = $DB->insert_id;
              
			/** ------------------------------------
			/**  Insert the custom field data
			/** ------------------------------------*/
			
			$cust_fields = array('entry_id' => $entry_id, 'weblog_id' => $weblog_selection, 'site_id' => $site_id);
              
			// $summary_selection, $body_selection, $extended_selection, $keywords_selection
			
			foreach ($fields as $field)
			{
				$name = $field.'_selection';
				
				if (${$name} != '' && ${$name} != 'none' && isset(${$field}[$id]))
				{
					$field_data = trim(str_replace($LB,"\n", ${$field}[$id]));
					$key = 'field_id_'.${$name};
					
					// Make sure the field data was not just some line breaks
                    if (strlen($field_data) > 0)
                    {
                    	$field_data = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($field_data) : $field_data;
                    	
                    	$cust_fields[$key] = ( ! isset($cust_fields[$key])) ? $field_data : $cust_fields[$key]."\n".$field_data;
                    	$key2 = str_replace('field_id_', 'field_ft_', $key);
                    	$cust_fields[$key2] = $convert_breaks[$id];
					}
					
					unset(${$field}[$id]);
					unset($field_data);
				}
			}
              
			$DB->query($DB->insert_string('exp_weblog_data', $cust_fields));
			unset($cust_fields);
              
              
			if ($auto_sub == 'y' || $auto_nosub == 'y')
			{
				/** --------------------------------
				/**  Insert primary categories
				/** --------------------------------*/
				
				if ( isset($primary_categories[$id]))
				{
					if (isset($primary_cat_ids[$primary_categories[$id]]))
					{
						$parent_id = $primary_cat_ids[$primary_categories[$id]];
						$DB->query("INSERT INTO exp_category_posts (entry_id, cat_id) VALUES ('{$entry_id}', '{$parent_id}')");
					}
				}
				
				/** ------------------------------
				/**  Insert categories
				/** ------------------------------*/
              
				if ( isset($categories[$id]) && sizeof($categories[$id]) > 0)
				{
					$cats_insert = '';
					foreach($categories[$id] as $cat)
					{
						$parent_id  = ( ! isset($parent_id)) ? '0' : $parent_id;
						
						foreach($regular_cat_ids as $cat_id => $cat_id_array)
						{
                        	if (is_array($cat_id_array) && $cat_id_array['0'] == $cat)
                        	{
                        		if (isset($cat_id_array['1']) && ($parent_id == $cat_id_array['1'] || ($auto_nosub == 'y' && $cat_id_array['1'] == '0')))
                        		{
                        			$cats_insert .= "('{$entry_id}', '{$cat_id}'),";
								}
							}
						}
					}
					
					if ($cats_insert != '')
					{
						$DB->query("INSERT INTO exp_category_posts (entry_id, cat_id) VALUES ".substr($cats_insert,0,-1));
					}
				}
			}
			
			/** --------------------------------------------
			/**  Additional Selected Categories for Entries
			/** --------------------------------------------*/
			
			// Note, these are possible, even if a user has chosen to 
			// auto create categories for the imported entries.
			// This way an admin can have the categories inserted for these
			// entries and still assign them to a new EE category or two
			
			if ($category_selection != '' && $category_selection != 'none')
			{
				$cat_inserts = explode('|',$category_selection); 

				if (sizeof($cat_inserts) > 0)
				{
					$vsql = '';
					foreach($cat_inserts as $cat_insert)
					{
						if ($cat_insert != '' && is_numeric($cat_insert))
						{
							$vsql .= "('{$entry_id}', '".$DB->escape_str($cat_insert)."'),";
						}
					}
					
					
					if (strlen($vsql) > 0)
					{
						$DB->query("INSERT INTO exp_category_posts (entry_id, cat_id) VALUES ".substr($vsql,0,-1));
					}
				}
			}
			
			/** ---------------------------------
			/**  Insert the comment data
			/** -------------------------------*/
			
			if ( isset($comments[$id]) && sizeof($comments[$id]) > 0)
			{
				// $comments[$id][$c]['body'], ['ip'], ['author'], ['url'], ['email'], ['date']
				
				$comments_insert = '';
				
				for($c=0; $c < sizeof($comments[$id]); $c++)
				{
					$com_name = ( ! isset($comments[$id][$c]['author'])) ? 'Anonymous' : stripslashes($comments[$id][$c]['author']);
					$com_email = ( ! isset($comments[$id][$c]['email'])) ? '' : stripslashes($comments[$id][$c]['email']); 
					$com_url = ( ! isset($comments[$id][$c]['url'])) ? '' : stripslashes($comments[$id][$c]['url']); 
					$com_ip = ( ! isset($comments[$id][$c]['ip'])) ? '' : stripslashes($comments[$id][$c]['ip']);
					$com_body = str_replace($LB, "\n", stripslashes($comments[$id][$c]['body'])); 
                   
					$com_date = $this->convert_mt_date_to_gmt($comments[$id][$c]['date']);
					
					$com_username = preg_replace("/[\||\'|\"|\!]/", '', $com_name);
					
					if(in_array($com_username,$usernames))
					{
						$author_id = array_search($com_username,$usernames);
					}
					elseif(in_array($com_name,$screen_names))
					{
						$author_id = array_search($com_name,$screen_names);
					}
					else
					{
						$author_id = 0; 
					}
					
					$data = array(
									'weblog_id'		=> $weblog_selection,
									'entry_id'		=> $entry_id,
									'author_id'		=> $author_id,
									'name'			=> $com_name,
									'email'			=> $com_email,
									'url'			=> $com_url,
									'location'		=> '',
									'comment'		=> $com_body,
									'comment_date'	=> $com_date,
									'ip_address'	=> $com_ip,
									'notify'		=> 'n',
									'site_id'		=> $site_id
									);
                                                          
					$DB->query($DB->insert_string('exp_comments', $data));
					$comments_entered++;
				}
			}
			
			/** ---------------------------------
			/**  Insert the trackback data
			/** ---------------------------------*/
			
			if ( isset($trackbacks[$id]) && sizeof($trackbacks[$id]) > 0)
			{
				// $trackbacks[$id][$t]['title'], ['date'], ['url'], ['ip'],['blog_name'], ['body']
				
				for($t=0; $t < sizeof($trackbacks[$id]); $t++)
				{
					$ping_title = stripslashes($trackbacks[$id][$t]['title']); 
					$blog_name = stripslashes($trackbacks[$id][$t]['blog_name']); 
					$ping_url = stripslashes($trackbacks[$id][$t]['url']);
					$ping_ip = ( ! isset($trackbacks[$id][$t]['ip'])) ? '' : stripslashes($trackbacks[$id][$t]['ip']); 
					$ping_body = str_replace($LB, "\n", stripslashes($trackbacks[$id][$t]['body'])); 
					$ping_date = $this->convert_mt_date_to_gmt($trackbacks[$id][$t]['date']);
					
					$data = array(
									'weblog_id'			=> $weblog_selection,
									'entry_id'			=> $entry_id,
									'title'				=> $ping_title,
									'content'			=> $ping_body,
									'weblog_name'		=> $blog_name,
									'trackback_url'		=> $ping_url,
									'trackback_date'	=> $ping_date,
									'trackback_ip'		=> $ping_ip,
									'site_id'			=> $site_id
									);
					
					$DB->query($DB->insert_string('exp_trackbacks', $data));
					$trackbacks_entered++;
				}
			}
		}
		// END of importing entries 
		
		/** --------------------------
		/**  OPTIMIZE
		/** --------------------------*/
		
		$DB->query("OPTIMIZE TABLE exp_comments");
		$DB->query("OPTIMIZE TABLE exp_members");
		$DB->query("OPTIMIZE TABLE exp_trackbacks");
		$DB->query("OPTIMIZE TABLE exp_weblogs");
		$DB->query("OPTIMIZE TABLE exp_weblog_titles");
		$DB->query("OPTIMIZE TABLE exp_weblog_data");
		
		/** --------------------------
		/**  Clear out config.php
		/** --------------------------*/
		
		$this->clear_config_prefs();
		
		/** ---------------------------
		/**  Display Success Message
		/** ---------------------------*/
		
		if ($auto_sub == 'y' || $auto_nosub == 'y')
		{
			$cats = 0;
			
			foreach($cleaned_categories as $parent_name => $cats_array)
			{
				$cats = $cats + sizeof($cats_array);
			}
			
			$categories_entered = sizeof($cleaned_primary_categories) + $cats;
		}
		
		$DSP->title = $LANG->line('mt_import_utility');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=mt_import', $LANG->line('mt_import_utility')).$DSP->crumb_item($LANG->line('import_complete'));
        
        $r = $DSP->qdiv('tableHeading', $LANG->line('import_complete'));
        $r .= $DSP->div('box');
        
        $r .= $DSP->qdiv('success', $LANG->line('you_are_done_importing'));
        
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('total_weblog_entries').NBS.$id);
        
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('total_weblog_comments').NBS.$comments_entered);
        
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('total_weblog_trackbacks').NBS.$trackbacks_entered);
        
        if (isset($categories_entered) && $categories_entered > 0)
        {
             $r .= $DSP->qdiv('itemWrapper', $LANG->line('total_categories_entered').NBS.$categories_entered);
        }
        
        if ($members_created > 0)
        {
             $r .= $DSP->qdiv('itemWrapper', $LANG->line('members_created').NBS.$members_created);
        }
        
        $r .= $DSP->qdiv('itemWrapper', BR.$DSP->qdiv('highlight', $LANG->line('more_importing_info')));
        $r .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=mt_import', $LANG->line('return_to_import')));
        
        $r .= $DSP->heading($LANG->line('recalculate_statistics'), 2);
        $r .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('click_to_reset_statistics')));
        $r .= $DSP->div_c();
         
        $DSP->body = $r;
        
    }
    /* END */
    
    

     /** ------------------------------------------- 
     /**  Converts the human-readable date used in the MT export format
     /** -------------------------------------------*/
     
    function convert_mt_date_to_gmt($datestr = '')
    {
        global $LANG, $LOC;
    
        if ($datestr == '')
            return false;
                    
            $datestr = trim($datestr);
            $datestr = str_replace('/','-',$datestr);
            $datestr = preg_replace("/\040+/", "\040", $datestr);

            if ( ! preg_match("#^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{2,4}\040[0-9]{1,2}:[0-9]{1,2}.*$#", $datestr))
            {
                return $LANG->line('invalid_date_formatting');
            }

            $split = preg_split("/\040/", $datestr);

            $ex = explode("-", $split['0']);            
            
            $month = (strlen($ex['0']) == 1) ? '0'.$ex['0']  : $ex['0'];
            $day   = (strlen($ex['1']) == 1) ? '0'.$ex['1']  : $ex['1'];
            $year  = (strlen($ex['2']) == 2) ? '20'.$ex['2'] : $ex['2'];

            $ex = explode(":", $split['1']); 
            
            $hour = (strlen($ex['0']) == 1) ? '0'.$ex['0'] : $ex['0'];
            $min  = (strlen($ex['1']) == 1) ? '0'.$ex['1'] : $ex['1'];

            if (isset($ex['2']) AND preg_match("#[0-9]{1,2}#", $ex['2']))
            {
                $sec  = (strlen($ex['2']) == 1) ? '0'.$ex['2'] : $ex['2'];
            }
            else
            {
                $sec = date('s');
            }
            
            if (isset($split['2']))
            {
                $ampm = strtolower($split['2']);
                
                if (substr($ampm, 0, 1) == 'p' AND $hour < 12)
                    $hour = $hour + 12;
                    
                if (substr($ampm, 0, 1) == 'a' AND $hour == 12)
                    $hour =  '00';
                    
                if (strlen($hour) == 1)
                    $hour = '0'.$hour;
            }

        if ($year < 1902 || $year > 2037)            
        {
            return $LANG->line('date_outside_of_range');
        }
                
        $time = $LOC->set_gmt(mktime($hour, $min, $sec, $month, $day, $year));

        // Offset the time by one hour if the user is submitting a date
        // in the future or past so that it is no longer in the same
        // Daylight saving time.
        
        if (date("I", $LOC->now))
        { 
            if ( ! date("I", $time))
            {
               $time -= 3600;            
            }
        }
        else
        {
            if (date("I", $time))
            {
                $time += 3600;           
            }
        }

        $time += $LOC->set_localized_offset();

        return $time;      
    }
    /* END */

    
    
 
    /** -------------------------------------------
    /**  clear config data
    /** -------------------------------------------*/
 
 	function clear_config_prefs()
 	{
 		global $DSP, $LANG;
 	
		require CONFIG_FILE;
		
		$newdata = array();
	 
		/** -----------------------------------------
		/**  Write config backup file
		/** -----------------------------------------*/
				
		$old  = "<?php\n\n";
		$old .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			if (substr($key, 0, 3) != 'mt_')
			{
				$newdata[$key] = $val;
			}
		
			$val = str_replace("\\'", "'", $val);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$old .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$old .= '?'.'>';
		

		if ($fp = @fopen('config_bak'.EXT, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $old, strlen($old));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
				
		/** -----------------------------------------
		/**  Write config file as a string
		/** -----------------------------------------*/
		
		$new  = "<?php\n\n";
		$new .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($newdata as $key => $val)
		{
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$new .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
		
		/** -----------------------------------------
		/**  Write config file
		/** -----------------------------------------*/

		if ($fp = @fopen('config'.EXT, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
 	} 
  	/* END */
  	
  	
  	
	/** -----------------------------------------------------------
    /**  JavaScript filtering code
    /** -----------------------------------------------------------*/
    // This function writes some JavaScript functions that
    // are used to switch the various pull-down menus in the
    // setup page
    //-----------------------------------------------------------

    function filtering_menus($form_name)
    { 
        global $DSP, $LANG, $SESS, $FNS, $DB, $PREFS;
     
        // In order to build our filtering options we need to gather 
        // all the weblogs, categories and custom statuses
        
		$allowed_blogs = $FNS->fetch_assigned_weblogs();

		if (count($allowed_blogs) > 0)
		{
			// Fetch weblog titles
			
			$sql = "SELECT blog_title, weblog_id, cat_group, status_group, field_group FROM exp_weblogs";
					
			if ( ! $DSP->allowed_group('can_edit_other_entries') || $SESS->userdata['weblog_id'] != 0)
			{
				$sql .= " WHERE weblog_id IN (";
			
				foreach ($allowed_blogs as $val)
				{
					$sql .= "'".$val."',"; 
				}
				
				$sql = substr($sql, 0, -1).')';
			}
			else
			{
				$sql .= " WHERE is_user_blog = 'n'";
			}
			
			$sql .= " ORDER BY blog_title";
			
			$query = $DB->query($sql);
					
			foreach ($query->result as $row)
			{
				$this->blog_array[$row['weblog_id']] = array(str_replace('"','',$row['blog_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}        
        }
        
        $sql = "SELECT exp_categories.group_id, exp_categories.parent_id, exp_categories.cat_id, exp_categories.cat_name 
                FROM exp_categories, exp_category_groups
                WHERE exp_category_groups.group_id = exp_categories.group_id
                AND exp_categories.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
        
        if ($SESS->userdata['weblog_id'] != 0)
        {
            $sql .= " AND exp_categories.group_id = '".$query->row['cat_id']."'";
        }
        else
        {
            $sql .= " AND exp_category_groups.is_user_blog = 'n'";
        }
        
        $sql .= " ORDER BY group_id, parent_id, cat_name";
        
        $query = $DB->query($sql);
            
        if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
        	{
            	$categories[$row['cat_id']] = array($row['group_id'], str_replace('"','',$row['cat_name']), $row['parent_id']);
        	}
        
        	foreach($categories as $key => $val)
        	{
        		if (0 == $val['2']) 
            	{
            		$this->cat_array[] = array($val['0'], $key, $val['1']);
            		$this->category_subtree($key, $categories, $depth=1);
        		}
        	}	
        } 
            
            
        $query = $DB->query("SELECT group_id, status FROM exp_statuses ORDER BY status_order");
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->status_array[]  = array($row['group_id'], $row['status']);
        	}
        }
        
        
        $query = $DB->query("SELECT group_id, field_label, field_id FROM exp_weblog_fields WHERE field_type IN ('textarea', 'text', 'select') ORDER BY field_order");
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
        	}
		}

		/** ----------------------------- 
        /**  SuperAdmins
        /** -----------------------------*/
        
        $sql = "SELECT exp_members.member_id, exp_members.username, exp_members.screen_name 
				FROM exp_members
				WHERE exp_members.group_id = '1'"; 

        $query = $DB->query($sql);
        
        foreach ($query->result as $row)
       	{
       		$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
       		
       		foreach($this->blog_array as $key => $value)
       		{
       			$this->author_array[]  = array($key, $row['member_id'], str_replace('"','',$author));
       		}
       	}
		
		/** ----------------------------- 
        /**  Assignable Weblog Authors
        /** -----------------------------*/
        
		$sql = "SELECT exp_members.member_id, exp_weblogs.weblog_id, exp_members.group_id, exp_members.username, exp_members.screen_name 
				FROM exp_weblogs, exp_members, exp_weblog_member_groups 
				WHERE (exp_weblog_member_groups.weblog_id = exp_weblogs.weblog_id OR exp_weblog_member_groups.weblog_id IS NULL) 
				AND exp_members.group_id = exp_weblog_member_groups.group_id"; 

        $query = $DB->query($sql);
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
       		{
       			$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
       		
       			$this->author_array[]  = array($row['weblog_id'], $row['member_id'], str_replace('"','',$author));
       		}
   		}
        
        // Build the JavaScript needed for the dynamic pull-down menus
        // We'll use output buffering since we'll need to return it
        // and we break in and out of php
        
        ob_start();
                
?>

<script type="text/javascript">
<!--

var firstcategory = 0;
var firststatus = 0;
var firstfield = 0;
var firstauthor = 0;

function changemenu(index)
{ 

  var categories = new Array();
  var statuses   = new Array();
  var fields     = new Array();
  var fields2    = new Array();
  var fields3    = new Array();
  var fields4    = new Array();
  var authors    = new Array();
  
<?php
$i = 0;

while(++$i <= $this->extra_fields)
{
	echo '  var extra_field_'.$i.'_id = new Array();'."\n";
}
?>
  
  var i = firstcategory;
  var j = firststatus;
  var k = firstfield;
  var l = firstauthor;
  var m = firstfield;
  var n = firstfield;
  var o = firstfield;
  
  var blogs = document.<?php echo $form_name; ?>.weblog_id.options[index].value;
 
  
    with(document.<?php echo $form_name; ?>.elements['category[]'])
    {
        if (blogs == "null")
        {            
            categories[i] = new Option("<?php echo $LANG->line('auto_create_sub'); ?>","auto_sub"); i++;
            categories[i] = new Option("<?php echo $LANG->line('auto_create_nosub'); ?>","auto_nosub"); i++;
            categories[i] = new Option("<?php echo $LANG->line('none'); ?>", "none"); i++;
    
            statuses[j] = new Option("<?php echo $LANG->line('open'); ?>", "open"); j++;
            statuses[j] = new Option("<?php echo $LANG->line('closed'); ?>", "closed"); j++;
            
            fields[k] = new Option("<?php echo $LANG->line('none'); ?>", "none"); k++;
            
            authors[l] = new Option("<?php echo $LANG->line('none'); ?>", "none"); l++;
        }
        
       <?php
                        
        foreach ($this->blog_array as $key => $val)
        {
        
        ?>
        
        if (blogs == "<?php echo $key ?>")
        {
            categories[i] = new Option("<?php echo $LANG->line('auto_create_sub'); ?>","auto_sub"); i++;
            categories[i] = new Option("<?php echo $LANG->line('auto_create_nosub'); ?>","auto_nosub"); i++;
            categories[i] = new Option("<?php echo $LANG->line('none'); ?>", "none"); i++; <?php echo "\n";
         
            if (count($this->cat_array) > 0)
            {            
                foreach ($this->cat_array as $k => $v)
                {
                	//$v['2'] = str_replace('&nbsp;',' ',$v['2']);
                    if ($v['0'] == $val['1'])
                    {
                    
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            categories[i] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); i++; <?php echo "\n";
                    }
                }
            }
            
            $set = 'n';
            if (count($this->status_array) > 0)
            {
                foreach ($this->status_array as $k => $v)
                {
                    if ($v['0'] == $val['2'])
                    {
                    	$set = 'y';
						$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  $LANG->line($v['1']) : $v['1'];
            ?> 
            statuses[j] = new Option("<?php echo $status_name; ?>", "<?php echo $v['1']; ?>"); j++; <?php
                    }
                }
            }
           
           	if ($set == 'n')
            {
            ?>
            
            statuses[j] = new Option("<?php echo $LANG->line('open'); ?>", "open"); j++;
            statuses[j] = new Option("<?php echo $LANG->line('closed'); ?>", "closed"); j++;<?php 
            }
            ?>
            
            
            fields[k] = new Option("<?php echo $LANG->line('none'); ?>", "none"); k++; <?php echo "\n";
         
            if (count($this->field_array) > 0)
            {
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $val['3'])
                    {                  
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            fields[k] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); k++; <?php echo "\n";
                    }
                }
            }                    
             
            ?>
            
            
            fields2[m] = new Option("<?php echo $LANG->line('none'); ?>", "none"); m++; <?php echo "\n";
         
            if (count($this->field_array) > 0)
            {
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $val['3'])
                    {                  
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            fields2[m] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); m++; <?php echo "\n";
                    }
                }
            }                    
	
		$i = 0;
		
		while(++$i <= $this->extra_fields)
		{
			
            ?>
            var f<?php echo $i; ?> = 0;
            <?php echo 'extra_field_'.$i.'_id'; ?>[f<?php echo $i; ?>] = new Option("<?php echo $LANG->line('none'); ?>", "none"); f<?php echo $i; ?>++; <?php echo "\n";
         
            if (count($this->field_array) > 0)
            {
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $val['3'])
                    {                  
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            <?php echo 'extra_field_'.$i.'_id'; ?>[f<?php echo $i; ?>] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); f<?php echo $i; ?>++; <?php echo "\n";
                    }
                }
            }                    
       }      
            ?>
            
            
            
            
            fields3[n] = new Option("<?php echo $LANG->line('none'); ?>", "none"); n++; <?php echo "\n";
         
            if (count($this->field_array) > 0)
            {
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $val['3'])
                    {                  
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            fields3[n] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); n++; <?php echo "\n";
                    }
                }
            }                    
             
            ?>
            
            
            fields4[o] = new Option("<?php echo $LANG->line('none'); ?>", "none"); o++; <?php echo "\n";
         
            if (count($this->field_array) > 0)
            {
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $val['3'])
                    {                  
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            fields4[o] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); o++; <?php echo "\n";
                    }
                }
            }                    
             
            ?>
            
            authors[l] = new Option("<?php echo $LANG->line('none'); ?>", "none"); l++; <?php echo "\n";              
         
            if (count($this->author_array) > 0)
            {
            	$inserted_authors = array();
            	
                foreach ($this->author_array as $k => $v)
                {
                    if ($v['0'] == $key && ! in_array($v['1'],$inserted_authors))
                    {
                    	$inserted_authors[] = $v['1'];
                                        

            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
            ?>
            authors[l] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); l++; <?php
                    }
                }
            }
              
            ?>


        } // END if blogs
            
        <?php
         
        } // END OUTER FOREACH
         
        ?> 
        with (document.<?php echo $form_name; ?>.elements['category[]'])
        {
            for (i = length-1; i >= firstcategory; i--)
                options[i] = null;
            
            for (i = firstcategory; i < categories.length; i++)
                options[i] = categories[i];
            
            options[0].selected = true;
        }
        
        with (document.<?php echo $form_name; ?>.excerpt_id)
        {
            for (i = length-1; i >= firstfield; i--)
                options[i] = null;
            
            for (i = firstfield; i < fields.length; i++)
            {
                options[i] = fields[i];
                
                if (options[i].text == 'Summary')
                {
                    options[i].selected = true;
                }
            } 
        }
        
        with (document.<?php echo $form_name; ?>.body_id)
        {
            for (i = length-1; i >= firstfield; i--)
                options[i] = null;
            
            for (i = firstfield; i < fields2.length; i++)
            {
                options[i] = fields2[i];
                
                if (options[i].text == 'Body')
                {
                    options[i].selected = true;
                }
            } 
        }
        
<?php

$i = 1;
		
		if (is_numeric($this->extra_fields) && $this->extra_fields > 0)
		{
			while(TRUE)
			{
?>

        with (document.<?php echo $form_name; ?>.<?php echo 'extra_field_'.$i.'_id'; ?>)
        {
            for (i = length-1; i >= 0; i--)
                options[i] = null;
            
            for (i = 0; i < <?php echo 'extra_field_'.$i.'_id'; ?>.length; i++)
            {
                options[i] = <?php echo 'extra_field_'.$i.'_id'; ?>[i];
            } 
        }


<?php
			
				$i++;
				
				if ($i > $this->extra_fields)
				{
					break;
				}
			}
		}



?>
        
        with (document.<?php echo $form_name; ?>.extended_id)
        {
            for (i = length-1; i >= firstfield; i--)
                options[i] = null;
            
            for (i = firstfield; i < fields3.length; i++)
            {
                options[i] = fields3[i];
                
                if (options[i].text == 'Extended text')
                {
                    options[i].selected = true;
                }
            } 
        }
        
        with (document.<?php echo $form_name; ?>.keywords_id)
        {
            for (i = length-1; i >= firstfield; i--)
                options[i] = null;
            
            for (i = firstfield; i < fields4.length; i++)
            {
                options[i] = fields4[i];
                
                if (options[i].text == 'Keywords')
                {
                    options[i].selected = true;
                }
            }           
        }
        
        with (document.<?php echo $form_name; ?>.status_default)
        {
            for (i = length-1; i >= firststatus; i--)
                options[i] = null;
            
            for (i = firststatus;i < statuses.length; i++)
                options[i] = statuses[i];
            
            options[0].selected = true;
        }
        
        with (document.<?php echo $form_name; ?>.member_id)
        {
            for (i = length-1; i >= firstauthor; i--)
                options[i] = null;
            
            for (i = firstauthor;i < authors.length; i++)
            {
                options[i] = authors[i];
                if (options[i].value == <?php echo $SESS->userdata('member_id'); ?>)
                {
                	options[i].selected = true;          
                }
            }
        }
    }
}

//--></script>
        
<?php
                
        $javascript = ob_get_contents();
        
        ob_end_clean();
        
        return $javascript;
     
    }
    /* END */
    
    
	/** --------------------------------
    /**  Category Sub-tree
    /** --------------------------------*/
	function category_subtree($cat_id, $categories, $depth)
    {
        global $DSP, $IN, $DB, $REGX, $LANG;

        $spcr = " ";
                  
        $indent = $spcr.$spcr.$spcr.'|_'.$spcr;
    
        if ($depth == 1)	
        {
            $depth = 2;
        }
        else 
        {	                            
            $indent = str_repeat($spcr, $depth).$indent;
            
            $depth = $depth + 4;
        }
        
        $sel = '';
            
        foreach ($categories as $key => $val) 
        {
            if ($cat_id == $val['2']) 
            {
                $pre = ($depth > 2) ? " " : '';
                
                $this->cat_array[] = array($val['0'], $key, '|'.$pre.$indent.$spcr.$val['1']);
                                
                $this->category_subtree($key, $categories, $depth);
            }
        }
    }
    /* END */
  	
  	
  	
  	
  	
}
// END CLASS
?>