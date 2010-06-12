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
 File: cp.publish.php
-----------------------------------------------------
 Purpose: The main weblog class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Publish {

	var $assign_cat_parent	= TRUE;
	var $direct_return		= FALSE;
    var $categories			= array();
    var $cat_parents		= array();
    var $smileys			= array();
    var $glossary			= array();
    var $nest_categories	= 'y';
    var $cat_array			= array();
    
    var $SPELL					= FALSE;
    var $comment_chars			= 25;
    var $comment_leave_breaks	= 'n';
	var $url_title_error		= FALSE;
	
	var $installed_modules		= array();

    /** ------------------------
    /**  Request handler
    /** ------------------------*/
  
    function request_handler()
    {
        global $IN, $DSP, $LANG, $FNS, $PREFS, $DB;

		$this->assign_cat_parent = ($PREFS->ini('auto_assign_cat_parents') == 'n') ? FALSE : TRUE; 
		
		$query = $DB->query("SELECT LOWER(module_name) as name FROM exp_modules");
		
		foreach($query->result as $row)
		{
			$this->installed_modules[$row['name']] = $row['name'];
		}

        switch ($IN->GBL('M'))
        {
            case 'new_entry'        	: ( ! $IN->GBL('preview', 'POST')) ? $this->submit_new_entry() : $this->new_entry_form('preview');            
                break;
            case 'entry_form'       	: $this->new_entry_form();
                break;
            case 'edit_entry'       	: $this->new_entry_form('edit');
                break;
            case 'view_entry'       	: $this->view_entry();
                break;
            case 'view_entries'     	: $this->edit_entries();
                break;
            case 'multi_edit'     		: $this->multi_edit_form();
                break;
            case 'update_multi_entries'	: $this->update_multi_entries();
                break;
            case 'entry_category_update': $this->multi_entry_category_update();
            	break;
            case 'delete_conf'      	: $this->delete_entries_confirm();
                break;	
            case 'delete_entries'   	: $this->delete_entries();
                break;
            case 'view_comments'    	: $this->view_comments();
                break;
            case 'view_trackbacks'    	: $this->view_trackbacks();
                break;
            case 'move_comments_form'	: $this->move_comments_form();
            	break;
            case 'move_comments'		: $this->move_comments();
            	break;
            case 'edit_comment'     	: $this->edit_comment_form();
                break;
            case 'edit_trackback'     	: $this->edit_trackback_form();
                break;
            case 'change_status'     	: $this->change_comment_status();
                break;                
            case 'update_comment'   	: $this->update_comment();
                break;
            case 'update_trackback'   	: $this->update_trackback();
                break;
            case 'modify_comments'	 	: $this->modify_comments();
                break;
            case 'del_comment_conf' 	: $this->delete_comment_confirm();
                break;
            case 'del_comment'      	: $this->delete_comment();
                break;
            case 'view_pings'       	: $this->view_previous_pings();
                break;
            case 'file_upload_form' 	: $this->file_upload_form();
                break;
            case 'upload_file'      	: $this->upload_file();
                break;
            case 'file_browser'     	: $this->file_browser();
                break;
            case 'replace_file'     	: $this->replace_file();
                break;
            case 'image_options'		: $this->image_options_form();
            	break;
            case 'create_thumb'			: $this->create_thumb();
            	break;
            case 'spellcheck_iframe'	: $this->spellcheck_iframe();
            	break;
            case 'spellcheck'			: $this->spellcheck();
            	break;
            case 'emoticons'        	: $this->emoticons();
                break;
            default  :
                        
                    if ($IN->GBL('C') == 'publish')
                    {
						if ($IN->GBL('BK'))
						{
							return $this->new_entry_form();
						}
                    
                        $assigned_weblogs = $FNS->fetch_assigned_weblogs();
                                            
                        if (count($assigned_weblogs) == 0)
                        {
                            return $DSP->no_access_message($LANG->line('unauthorized_for_any_blogs'));
                        }
                        else
                        {
                            if (count($assigned_weblogs) == 1)
                            {
                                return $this->new_entry_form();
                            }
                            else
                            {
                                return $this->weblog_select_list();
                            }
                        }
                    }
                    else
                    {
                       return $this->edit_entries();
                    }        
             break;
        }
    }
    /* END */
    



    /** --------------------------------------------
    /**  Weblog selection menu
    /** --------------------------------------------*/
    // This function shows a list of available weblogs.
    // This list will be displayed when a user clicks the
    // "publish" link when more than one weblog exist.
    //--------------------------------------------

    function weblog_select_list($add='')
    {
        global $IN, $DSP, $DB, $LANG, $FNS, $SESS;
        
                
        if ($IN->GBL('C') == 'publish')
        {
            $blurb  = $LANG->line('select_blog_to_post_in');
            $title  = $LANG->line('publish');
            $action = 'C=publish'.AMP.'M=entry_form';
        }
        else
        {
            $blurb  = $LANG->line('select_blog_to_edit');
            $title  = $LANG->line('edit');
            $action = 'C=edit'.AMP.'M=view_entries';
        }
    
        /** -------------------------------------------------
        /**  Fetch the blogs the user is allowed to post in
        /** -------------------------------------------------*/

        $links = array();
        
        $i = 0;
        
        foreach ($SESS->userdata['assigned_weblogs'] as $weblog_id => $weblog_title)
        { 
            $links[] = $DSP->table_qrow(($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.$action.AMP.'weblog_id='.$weblog_id.$add, $weblog_title)));
        }
        
        // If there are no allowed blogs, show a message
        
        if (count($links) < 1)
        {
            return $DSP->no_access_message($LANG->line('unauthorized_for_any_blogs'));
        }
        
        $DSP->body .= $DSP->table('tableBorder', '0', '', '100%')
        			 .$DSP->table_qrow('tableHeading', $blurb);
        
        foreach ($links as $val)
        {
            $DSP->body .= $val;     
        }  
        
        $DSP->body .= $DSP->table_c();
                        
        $DSP->title = $title;
        $DSP->crumb = $title;
    }
    /* END */



    /** --------------------------------------------
    /**  Weblog "new entry" form
    /** --------------------------------------------*/
    // This function displays the form used to submit, edit, or
    // preview new weblog entries with.  
    //--------------------------------------------

    function new_entry_form($which = 'new', $submission_error = '', $entry_id='', $hidden = array())
    {
        global $DSP, $LANG, $LOC, $DB, $IN, $REGX, $FNS, $SESS, $PREFS, $EXT;
        
        $title            			= '';
        $url_title        			= '';
        $url_title_prefix			= '';
        $default_entry_title		= '';
        $status           			= '';
        $expiration_date  			= '';
        $comment_expiration_date 	= '';
        $entry_date       			= '';
        $sticky           			= '';
        $allow_trackbacks 			= '';
        $trackback_urls   			= '';
        $field_data       			= '';
        $allow_comments   			= '';
        $preview_text     			= '';
        $catlist          			= '';
        $author_id        			= '';
        $tb_url           			= '';
        $bookmarklet      			= FALSE;
        $version_id					= $IN->GBL('version_id');
        $version_num				= $IN->GBL('version_num');
        $dst_enabled				= $SESS->userdata('daylight_savings');
		$weblog_id					= '';
        
        if ($PREFS->ini('site_pages') !== FALSE)
        {
        	$LANG->fetch_language_file('pages');
        }
        
		$publish_tabs				= array('form'		=> $LANG->line('publish_form'),
											'date'		=> $LANG->line('date'),
											'cat'		=> $LANG->line('categories'),
											'option'	=> $LANG->line('options'),
											'tb'		=> $LANG->line('trackbacks'),
											'ping'		=> $LANG->line('pings'),
											'forum'		=> $LANG->line('forum'),
											'revisions'	=> $LANG->line('revisions'),
											'pages'		=> $LANG->line('pages_module_name'),
											'show_all'	=> $LANG->line('show_all'),
											);
      
        /** ------------------------------------------------------------------
        /**  We need to first determine which weblog to post the entry into.
        /** ------------------------------------------------------------------*/

        $assigned_weblogs = $FNS->fetch_assigned_weblogs();

		// if it's an edit, we just need the entry id and can figure out the rest
        if ($IN->GBL('entry_id', 'GET') !== FALSE AND is_numeric($IN->GBL('entry_id', 'GET')) AND $weblog_id == '')
        {
            $query = $DB->query("SELECT weblog_id FROM exp_weblog_titles WHERE entry_id = '".$DB->escape_str($IN->GBL('entry_id', 'GET'))."'");
			
			if ($query->num_rows == 1)
			{
				$weblog_id = $query->row['weblog_id'];
			}
        }

        if ($weblog_id == '' AND ! ($weblog_id = $IN->GBL('weblog_id', 'GP')))
        {
            // Does the user have their own blog?
            
            if ($SESS->userdata['weblog_id'] != 0)
            {
                $weblog_id = $SESS->userdata['weblog_id'];
            }
            elseif (sizeof($assigned_weblogs) == 1)
            {
            	$weblog_id = $assigned_weblogs['0'];
            }
            else
            {
                $query = $DB->query("SELECT weblog_id from exp_weblogs WHERE is_user_blog = 'n'");
      
                if ($query->num_rows == 1)
                {
                    $weblog_id = $query->row['weblog_id'];
                }
                else
                {
                    return false;
                }
            }
        }

        if ( ! is_numeric($weblog_id))
        	return FALSE;
        	
        /** ----------------------------------------------
        /**  Security check
        /** ---------------------------------------------*/
                
        if ( ! in_array($weblog_id, $assigned_weblogs))
        {
            return $DSP->no_access_message($LANG->line('unauthorized_for_this_blog'));
        }
        
		// -------------------------------------------
        // 'publish_form_start' hook.
        //  - Allows complete rewrite of Publish page.
        //  - Added $hidden: 1.6.0
        //
        	$edata = $EXT->call_extension('publish_form_start', $which, $submission_error, $entry_id, $hidden);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
		// -------------------------------------------
        // 'publish_form_headers' hook.
        //  - Adds content to headers for Publish page.
		//  - Added $weblog_id: 1.6
		//  - Added $hidden: 1.6.0
        //
        	$DSP->extra_header .= $EXT->call_extension('publish_form_headers', $which, $submission_error, $entry_id, $weblog_id, $hidden);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        // -------------------------------------------
        // 'publish_form_new_tabs' hook.
		//  - Allows adding of new tabs to submission form
		//  - Added: 1.4.1
		//  - Added $hidden: 1.6.0
		//
			if ($EXT->active_hook('publish_form_new_tabs') === TRUE)
			{
				$publish_tabs = $EXT->call_extension('publish_form_new_tabs', $publish_tabs, $weblog_id, $entry_id, $hidden);
			}	
		//
		// -------------------------------------------
		
		/** ----------------------------------------------
		/**  If Still Set, Show All Goes at the End
		/** ---------------------------------------------*/
		
		if (isset($publish_tabs['show_all']))
		{
			unset($publish_tabs['show_all']);
			$publish_tabs['show_all'] = $LANG->line('show_all');
		}
            
        /** ----------------------------------------------
        /**  Fetch weblog preferences
        /** ---------------------------------------------*/

        $query = $DB->query("SELECT * FROM  exp_weblogs WHERE weblog_id = '".$DB->escape_str($weblog_id)."'");     
                
        if ($query->num_rows == 0)
        {
            return $DSP->error_message($LANG->line('no_weblog_exits'));
        }
        
        // -------------------------------------------
        // 'publish_form_weblog_preferences' hook.
        //  - Modify weblog preferences
        //  - Added: 1.4.1
        //
			if ($EXT->active_hook('publish_form_weblog_preferences') === TRUE)
			{
				$query->row = $EXT->call_extension('publish_form_weblog_preferences', $query->row);
			}	
        //
        // -------------------------------------------

        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        /** ----------------------------------------------
        /**  Fetch Revision if Necessary
        /** ---------------------------------------------*/
        
        $show_revision_cluster = ($enable_versioning == 'y') ? 'y' : 'n';
        
        if ($which == 'new')
        {
			$versioning_enabled = ($enable_versioning == 'y') ? 'y' : 'n';
        }
        else
        {
			$versioning_enabled = (isset($_POST['versioning_enabled'])) ? 'y' : 'n';
        }
        
        if (is_numeric($version_id))
        {
        	$entry_id = $IN->GBL('entry_id');
			$revquery = $DB->query("SELECT version_data FROM exp_entry_versioning WHERE entry_id = '{$entry_id}' AND version_id = '{$version_id}'");
        
			if ($revquery->num_rows == 1)
			{	
				$_POST = $REGX->array_stripslashes(@unserialize($revquery->row['version_data']));
				$_POST['entry_id'] = $entry_id;
				$which = 'preview';
			}
			unset($revquery);
        }
        
        /** ---------------------------------------
        /**  Insane Idea to Have Defaults and Prefixes
        /** ---------------------------------------*/
        
        if ($which == 'edit')
        {
        	$url_title_prefix = '';
        }
        elseif ($which == 'new')
        {
        	$title 		= $default_entry_title;
        	$url_title	= $url_title_prefix;
        }
                  
        // --------------------------------------------------------------------       
        // The $which variable determines what the page should show:
        //  If $which = 'new' we'll show a blank "new entry" page
        //  If $which = "preview", the user has clicked the "preview" button.
        //  If $which = "edit", we are editing an already existing entry.
        //  If $which = 'save', like a preview, but also an edit.
        // --------------------------------------------------------------------              

        if ($which == 'edit')
        {
            if ( ! $entry_id = $IN->GBL('entry_id', 'GET'))
            {
                return false;
            }
            
            // Fetch the weblog data
        
            $sql = "SELECT t.*, d.*
                    FROM   exp_weblog_titles AS t, exp_weblog_data AS d
                    WHERE  t.entry_id	= '$entry_id'
                    AND    t.weblog_id	= '$weblog_id'
                    AND    t.entry_id	=  d.entry_id"; 
        
            $result = $DB->query($sql);
            				
            if ($result->num_rows == 0)
            {
                return $DSP->error_message($LANG->line('no_weblog_exits'));
            }
            
            if ($result->row['author_id'] != $SESS->userdata('member_id'))
            {    
                if ( ! $DSP->allowed_group('can_edit_other_entries'))
                {
                    return $DSP->no_access_message();
                }
            }            
        
			// -------------------------------------------
			// 'publish_form_entry_data' hook.
			//  - Modify entry's data
			//  - Added: 1.4.1
			//
				if ($EXT->active_hook('publish_form_entry_data') === TRUE)
				{
					$result->row = $EXT->call_extension('publish_form_entry_data', $result->row);
				}	
			//
			// -------------------------------------------
            
            foreach ($result->row as $key => $val)
            {
                $$key = $val;
            }
        }

        /** ---------------------------------------------
        /**  Assign page title based on type of request
        /** ---------------------------------------------*/
        
        switch ($which)
        {
            case 'edit'		:  $DSP->title = $LANG->line('edit_entry');
                break;
            case 'save'		:  $DSP->title = $LANG->line('edit_entry');
                break;
            case 'preview'	:  $DSP->title = $LANG->line('preview');
                break;
            default			:  $DSP->title = $LANG->line('new_entry');
                break;        
        }

        /** ----------------------------------------------
        /**  Assign breadcrumb
        /** ---------------------------------------------*/
        
        $DSP->crumb = $DSP->title.$DSP->crumb_item($blog_title);

		$activate_calendars = '"';

		if ($show_date_menu == 'y')
		{
		// Setup some onload items
		
			$activate_calendars = 'activate_calendars();" ';			
			$DSP->extra_header .= '<script type="text/javascript">
			// depending on timezones, local settings and localization prefs, its possible for js to misinterpret the day, 
			// but the humanized time is correct, so we activate the humanized time to sync the calendar
		
			function activate_calendars() {
				update_calendar(\'entry_date\', document.getElementById(\'entry_date\').value);
				update_calendar(\'expiration_date\', document.getElementById(\'expiration_date\').value);';
				if ($comment_system_enabled == 'y')
				{				
					$DSP->extra_header .= "\n\t\t\t\t".'update_calendar(\'comment_expiration_date\', document.getElementById(\'comment_expiration_date\').value);';
				}
			$DSP->extra_header .= "\n\t\t\t\t"."current_month	= '';
				current_year	= '';
				last_date	= '';";
			$DSP->extra_header .= "\n".'}
			</script>';
		}


		/* -------------------------------------
		/*  Publish Page Title Focus
		/*  
		/*  makes the title field gain focus when the page is loaded
		/*  
		/*  Hidden Configuration Variable
		/*  - publish_page_title_focus => Set focus to the tile? (y/n)
		/* -------------------------------------*/
		
		if ($which != 'edit' && $PREFS->ini('publish_page_title_focus') !== 'n')
		{
	        $load_events = 'document.forms[0].title.focus();set_catlink();';			
		}
		else
		{
			$load_events = 'set_catlink();';
		}
	
		$DSP->body_props .= ' onload="'.$load_events.$activate_calendars;
		
        
        // -------------------------------------------
        // 'publish_form_body_props' hook.
		//  - Allows setting of the body properties
		//
			$edata = $EXT->call_extension('publish_form_body_props');
			if ($EXT->end_script === TRUE) return;
		//
		// -------------------------------------------
        
        /** ----------------------------------------------
        /**  Are we using the bookmarklet?
        /** ---------------------------------------------*/
        
        if ($IN->GBL('BK', 'GP'))
        {
            $bookmarklet = TRUE;
            
            $tb_url = $IN->GBL('tb_url', 'GP');
        }
        
        /** ----------------------------------------------
        /**  Start building the page output
        /** ---------------------------------------------*/
        
        $r = '';
        
        /** ----------------------------------------------
        /**  Form header and hidden fields  
        /** ---------------------------------------------*/
        
        $BK = ($bookmarklet == TRUE) ? AMP.'BK=1'.AMP.'Z=1' : '';
                    
        if ($IN->GBL('C') == 'publish')
        {
            $r .= $DSP->form_open(
            						array(
            								'action' => 'C=publish'.AMP.'M=new_entry'.$BK, 
            								'name'	=> 'entryform',
            								'id'	=> 'entryform'
            							)
            					);
        }
        else
        {
            $r .= $DSP->form_open(
            						array(
            								'action' => 'C=edit'.AMP.'M=new_entry'.$BK, 
            								'name'	=> 'entryform',
            								'id'	=> 'entryform'
            							)
            					);
        }
        
        $r .= $DSP->input_hidden('weblog_id', $weblog_id);    
        
        foreach($hidden as $key => $value)
        {
        	$r .= $DSP->input_hidden($key, $value);
        }
        
        if ($IN->GBL('entry_id', 'POST'))
        {
            $entry_id = $IN->GBL('entry_id');
        }
            
        if (isset($entry_id))
        {
            $r .= $DSP->input_hidden('entry_id', $entry_id); 
        }
        
        if ($bookmarklet == TRUE)
        {
            $r .= $DSP->input_hidden('tb_url', $tb_url); 
        } 
        
        /** --------------------------------
        /**  Fetch Custom Fields
        /** --------------------------------*/

		// Even though we don't need this query until laters we'll run the 
		// query here so that we can show previews in the proper order. 
        
		// -------------------------------------------
		// 'publish_form_field_query' hook.
		//  - Allows control over the field query, controlling what fields will be displayed
		//
			if (isset($EXT->extensions['publish_form_field_query']))
			{
				$field_query = $EXT->call_extension('publish_form_field_query', $this, $field_group);
			}
			else
			{
				$field_query = $DB->query("SELECT * FROM  exp_weblog_fields WHERE group_id = '$field_group' ORDER BY field_order");
			}
		//
		// -------------------------------------------
        

        /** ----------------------------------------------
        /**  Javascript stuff
        /** ---------------------------------------------*/
        
        $convert_ascii = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? TRUE : FALSE;
      	
        // "title" input Field        
        if ($IN->GBL('title', 'GET'))
        {
            $title = $this->bm_qstr_decode($IN->GBL('title', 'GET'));
        }
        
        $word_separator = $PREFS->ini('word_separator') != "dash" ? '_' : '-';
        
       	if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT;
    	}
    	
    	$this->SPELL = new Spellcheck();
    	$spellcheck_js = $this->SPELL->JavaScript(BASE.'&C=publish&M=spellcheck');
    	
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
    	
    	/** -------------------------------------
    	/**  Publish Tabs JavaScript
    	/** -------------------------------------*/
    	
    	$publish_tabs_javascript = "var blockarray	= new Array(".(sizeof($publish_tabs) - 1).")\n";
    	$p = 0;
    	foreach($publish_tabs as $short => $long)
    	{
    		$publish_tabs_javascript .= "\t\t".'blockarray['.$p.'] = "block'.$short.'"'."\n"; $p++;
    	}
        
		$default_entry_title = $REGX->form_prep($default_entry_title);
		
        $r .= <<<EOT
        
        <script type="text/javascript"> 
        <!--
        
        /** ------------------------------------
        /**  Swap out categories
        /** -------------------------------------*/
     
     	// This is used by the "edit categories" feature
     	
     	function set_catlink()
     	{
     		if (document.getElementById('cateditlink'))
     		{
     			if (browser == "IE" && OS == "Mac")  
				{
					document.getElementById('cateditlink').style.display = "none";
				}
				else
				{
					document.getElementById('cateditlink').style.display = "block";
				}
			}
     	}
     	
        function swap_categories(str)
        {
        	document.getElementById('categorytree').innerHTML = str;	
        }
        
        /** ------------------------------------
        /**  Array Helper Functions
        /** -------------------------------------*/

        function getarraysize(thearray)
        {
            for (i = 0; i < thearray.length; i++)
            {
                if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
                {
                    return i;
                }
            }
            
            return thearray.length;
        }        
        
        // Array push
        function arraypush(thearray, value)
        {
            thearray[getarraysize(thearray)] = value;
        }
        
        // Array pop
        function arraypop(thearray)
        {
            thearraysize = getarraysize(thearray);
            retval = thearray[thearraysize - 1];
            delete thearray[thearraysize - 1];
            return retval;
        }		
		
        /** ------------------------------------
        /**  Live URL Title Function
        /** -------------------------------------*/
        
        function liveUrlTitle()
        {
        	var defaultTitle = '{$default_entry_title}';
			var NewText = document.getElementById("title").value;
			
			if (defaultTitle != '')
			{
				if (NewText.substr(0, defaultTitle.length) == defaultTitle)
				{
					NewText = NewText.substr(defaultTitle.length);
				}	
			}
			
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
			
			if (document.getElementById("url_title"))
			{
				document.getElementById("url_title").value = "{$url_title_prefix}" + NewText;			
			}
			else
			{
				document.forms['entryform'].elements['url_title'].value = "{$url_title_prefix}" + NewText; 
			}		
		}

        /** ------------------------------------
        /**  Publish Option Tabs Open/Close
        /** -------------------------------------*/

		{$publish_tabs_javascript}
		
		function showblock(which)
		{					
			for (i = 0 ; i < blockarray.length; i++ )
			{			
				if (document.getElementById(blockarray[i]))
				{
					if (which == 'blockshow_all')
					{
						document.getElementById(blockarray[i]).style.display = "block";
					}
					else
					{
						document.getElementById(blockarray[i]).style.display = "none";
					}
				}
				
				var menu = blockarray[i].substring(5) + 'menu';	
				
				if (document.getElementById(menu))
				{
					document.getElementById(menu).style.display = "none";						
				}					
			}
			
			var menu = which.substring(5) + 'menu';	
								
			if (document.getElementById(which))
			{			
				document.getElementById(which).style.display = "block";
				document.getElementById(menu).style.display = "block";				
			}			
		}	
		
		function styleswitch(link)
		{                 
			if (document.getElementById(link).className == 'publishTabs')
			{
				document.getElementById(link).className = 'publishTabsHover';
			}
		}
	
		function stylereset(link)
		{                 
			if (document.getElementById(link).className == 'publishTabsHover')
			{
				document.getElementById(link).className = 'publishTabs';
			}
		}
		
        /** ------------------------------------
        /**  Glossary Item Insert
        /** -------------------------------------*/

        function glossaryInsert(item, id, tag)
        {        
			selField = "field_id_" + id;	
			taginsert('other', tag, '');      
        }
             
        /** ------------------------------------
        /**  Smiley Insert
        /** -------------------------------------*/
		
        function add_smiley(smiley, id)
        {
			selField = "field_id_" + id;	
			taginsert('other', " " + smiley + " ", '');
			
        	showhide_smileys(id);
        }
        
        
        {$spellcheck_js}
        
        
        /** ------------------------------------
        /**  Show/Hide Similey Pane
        /** -------------------------------------*/

        var open_panes = new Array();
        
        function showhide_smileys(id)
        {
        	cid = 'smileys_' + id;
        	gl = 'glossary_' + id;
        	sp = 'spellcheck_field_id_' + id;
        	
        	
			if (document.getElementById(cid))
			{
				if (document.getElementById(cid).style.display == "block")
				{
                	hide_open_panes();
				}
				else
				{				
					document.getElementById(cid).style.display = "block";
					document.getElementById(gl).style.display = "none";
					
					if (document.getElementById(sp))
					{
						document.getElementById(sp).style.display = "none";
					}
					
					hide_open_panes();
					arraypush(open_panes, cid);
				}
			}
        }
           
        /** ------------------------------------
        /**  Show/hide Glossary Pane
        /** -------------------------------------*/

        function showhide_glossary(id)
        {  
        	cid = 'glossary_' + id;
        	sm = 'smileys_' + id;
        	sp = 'spellcheck_field_id_' + id;
        	
        
			if (document.getElementById(cid))
			{
				if (document.getElementById(cid).style.display == "block")
				{
                	hide_open_panes();
				}
				else
				{
					document.getElementById(cid).style.display = "block";
					document.getElementById(sm).style.display = "none";

					if (document.getElementById(sp))
					{
						document.getElementById(sp).style.display = "none";
					}
					
					hide_open_panes();
					arraypush(open_panes, cid);
				}
			}
        }
        
        
        /** ------------------------------------
        /**  Show/hide Spellcheck Pane
        /** -------------------------------------*/

        function showhide_spellcheck(id)
        {  
        	cid = 'spellcheck_field_id_' + id;
        	sm = 'smileys_' + id;
        	gl = 'glossary_' + id;
        
			if (document.getElementById(cid))
			{
				if (document.getElementById(cid).style.display == "block")
				{
                	SP_closeSpellCheck();
                	
                	hide_open_panes();
				}
				else
				{
					document.getElementById(cid).style.display = "block";
					document.getElementById(sm).style.display = "none";
					document.getElementById(gl).style.display = "none";
				
					eeSpell.getResults('field_id_'+id);
				
					hide_open_panes();
					arraypush(open_panes, cid);
				}
			}
        }
      
        /** ------------------------------------
        /**  Close Open Panes
        /** -------------------------------------*/

        function hide_open_panes()
        {
			if (open_panes[0])
			{
				while (open_panes[0])
				{
					clearState = arraypop(open_panes);
					document.getElementById(clearState).style.display = "none";
				}
			}	
   		}


        /** ------------------------------------
        /**  Generic show/hide
        /** -------------------------------------*/

        function showhide_item(id)
        {
			if (document.getElementById(id).style.display == "block")
			{
				document.getElementById(id).style.display = "none";
        	}
        	else
        	{
				document.getElementById(id).style.display = "block";
        	}
        }
		
		
        /** ------------------------------------
        /**  Show/hide Fields
        /** -------------------------------------*/

        function showhide_field(id)
        {
        	f_off = 'field_pane_off_' + id;
        	f_on  = 'field_pane_on_' + id;
        
			if (document.getElementById(f_off).style.display == "block")
			{
				document.getElementById(f_off).style.display = "none";
				document.getElementById(f_on).style.display = "block";
        	}
        	else
        	{
				document.getElementById(f_off).style.display = "block";
				document.getElementById(f_on).style.display = "none";
        	}
        }

        // Remove the Preview from the DOM so it isn't added to submitted content
		document.getElementById('entryform').onsubmit = function()
		{
			if (document.getElementById('entryform').hasChildNodes(document.getElementById('previewBox')) == true)
			{
				document.getElementById('entryform').removeChild(document.getElementById('previewBox'));
			}
		}

		-->
		</script>
EOT;

		$r .= NL.NL;
		
		if ($bookmarklet == TRUE)
		{
			$r .= $DSP->qdiv('defaultSmall', NBS);
		}
		
        /** ----------------------------------------------
        /**  Are we previewing an entry?
        /** ---------------------------------------------*/
        
        if ($which == 'preview')
        {
            /** ----------------------------------------
            /**  Instantiate Typography class
            /** ----------------------------------------*/
          
            if ( ! class_exists('Typography'))
            {
                require PATH_CORE.'core.typography'.EXT;
            }
            
            $TYPE = new Typography;
            $TYPE->convert_curly = FALSE;
            
            $this->smileys = $TYPE->smiley_array;
    
    		$preview = ($version_id == FALSE) ? $LANG->line('preview') : $LANG->line('version_preview');
    		
    		if (is_numeric($version_num))
    		{
    			$preview = str_replace('%s', $version_num, $preview);
    		}
    
    		$prv_title = ($submission_error == '') ? $preview : $DSP->qspan('alert', $LANG->line('error'));
    
			$r .= '<fieldset class="previewBox" id="previewBox">';
			$r .= '<legend class="previewItemTitle">&nbsp;'.$prv_title.'&nbsp;</legend>';
                          
			if ($submission_error == '')
            {
            	$r .= $DSP->heading($TYPE->format_characters(stripslashes($IN->GBL('title', 'POST'))));
            }
            
            // We need to grab each global array index and do a little formatting
            
            $preview_build = array();
            
            foreach($_POST as $key => $val)
            {            
                // Gather categories.  Since you can select as many categories as you want
                // they are submitted as an array.  The $_POST['category'] index
                // contains a sub-array as the value, therefore we need to loop through 
                // it and assign discrete variables.
                
                if (is_array($val))
                {
                    foreach($val as $k => $v)
                    {
                    	$_POST[$k] = $v;
                    }

					if ($key == 'category' OR $key == 'ping')
					{
						unset($_POST[$key]);
					}
				}
                else
                {
					if ($submission_error == '')
					{
						if (strstr($key, 'field_id'))
						{
							$expl = explode('field_id_', $key);
							
							// Pass the entry data to the typography class
													
							$txt_fmt = ( ! isset($_POST['field_ft_'.$expl['1']])) ? 'xhtml' : $_POST['field_ft_'.$expl['1']];
							
							$p_open  = ($txt_fmt != 'xhtml') ? '<p>'  : '';
							$p_close = ($txt_fmt != 'xhtml') ? '</p>' : '';
						
							$preview_build['field_id_'.$expl['1']] = $p_open.$TYPE->parse_type( stripslashes($val), 
													 array(
																'text_format'   => $txt_fmt,
																'html_format'   => $weblog_html_formatting,
																'auto_links'    => $weblog_auto_link_urls,
																'allow_img_url' => $weblog_allow_img_urls
														   )
													).$p_close;
													
							/** ----------------------------
							/**  Certain tags might cause havoc, so we remove them
							/** ----------------------------*/
							
							$preview_build['field_id_'.$expl['1']] = preg_replace("#<script([^>]*)>.*?</script>#is", '', $preview_build['field_id_'.$expl['1']]);
							$preview_build['field_id_'.$expl['1']] = preg_replace("#<form([^>]*)>(.*?)</form>#is", '\2', $preview_build['field_id_'.$expl['1']]);
						} 
					}
                    
                    $val = stripslashes($val);
                
                    $_POST[$key] = $val;
                }
                        
               $$key = $val;
            }
            
            // Show the preview.  We do it this way in order to honor
            // the custom field order since we can't guarantee that $_POST
            // data will be in the correct order
            
            if (count($preview_build) > 0)
            {
				foreach ($field_query->result as $row)
				{
					if (isset($preview_build['field_id_'.$row['field_id']]))
					{
						$r .= $preview_build['field_id_'.$row['field_id']];
					}
				}
            }
            
            // Do we have a forum topic preview?
            
            if ($PREFS->ini('forum_is_installed') == "y")
            {
            	if ($IN->GBL('forum_title') != '')
            	{
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_title', 'title')).
									$DSP->qdiv('', $IN->GBL('forum_title'))
									);
            	}
            
            	if ($IN->GBL('forum_body') != '')
            	{
					$forum_body = $TYPE->parse_type( stripslashes($IN->GBL('forum_body')), 
													 array(
																'text_format'   => 'xhtml',
																'html_format'   => 'safe',
																'auto_links'    => 'y',
																'allow_img_url' => 'y'
														   )
													);       	
            	
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_body', 'title')).
									$DSP->qdiv('', $forum_body)
									);
            	}
            }
            
			// -------------------------------------------
			// 'publish_form_preview_additions' hook.
			//  - Add content to preview
			//  - As this is a preview, content can be gotten from $_POST
			//  - Added: 1.4.1
			//
				if ($EXT->active_hook('publish_form_preview_additions') === TRUE)
				{
					$r .= $EXT->call_extension('publish_form_preview_additions');
				}	
			//
			// -------------------------------------------
            
       		// Are there any errors?
            
			if ($submission_error != '')
			{
				$r .= $DSP->qdiv('highlight', $submission_error);
			}                    
	
			$r .= '</fieldset>';
        }
        // END PREVIEW
        
        
        // QUICK SAVE:  THE PREVIEW PART
        if ($which == 'save')
        {
        	foreach($_POST as $key => $val)
            { 
                if (is_array($val))
                {
                    foreach($val as $k => $v)
                    {
                    	$_POST[$k] = $v;
                    }
                    
					if ($key == 'category' OR $key == 'ping')
					{
						unset($_POST[$key]);
					}
                }
                else
                {    
                	$val = stripslashes($val);
                	
					$_POST[$key] = $val;
                }
                
                if ($key != 'entry_id')
                {
              		$$key = $val;
              	}
				
				// we need to unset this or it will cause the forum tab to not display the existing connection
				unset($forum_topic_id);
            }
            
            $r .= '<fieldset class="previewBox" id="previewBox">';
			$r .= '<legend class="previewItemTitle">&nbsp;'.$LANG->line('quick_save').'&nbsp;</legend></fieldset>';
        }
        // END SAVE        

        
        /** --------------------------------
        /**  Weblog pull-down menu
        /** --------------------------------*/
        
		$menu_weblog = '';
		
		$show_weblog_menu = 'y';
		
		if ($show_weblog_menu == 'n')
		{	
			$r .= $DSP->input_hidden('new_weblog', $weblog_id); 
		}
		elseif($which != 'new')
		{  		
			/** --------------------------------
			/**  Create weblog menu
			/** --------------------------------*/
			
			$query = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs 
								 WHERE status_group = '$status_group' 
								 AND cat_group = '".$DB->escape_str($cat_group)."'
								 AND field_group = '$field_group'
								 AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
								 ORDER BY blog_title");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					if ($SESS->userdata['group_id'] == 1 OR in_array($row['weblog_id'], $assigned_weblogs))
					{
						if (isset($_POST['new_weblog']) && is_numeric($_POST['new_weblog']))
						{
							$selected = ($_POST['new_weblog'] == $row['weblog_id']) ? 1 : '';
						}
						else
						{
							$selected = ($weblog_id == $row['weblog_id']) ? 1 : '';
						}
						
						$menu_weblog .= $DSP->input_select_option($row['weblog_id'], $REGX->form_prep($row['blog_title']), $selected);
					}
				}
				
				if ($menu_weblog != '')
				{
					$menu_weblog = $DSP->input_select_header('new_weblog').$menu_weblog.$DSP->input_select_footer();
				}
			}
		}
        
        
        
        /** --------------------------------
        /**  Status pull-down menu
        /** --------------------------------*/
        
		$menu_status = '';

        if ($deft_status == '')
        	$deft_status = 'open';
        
        if ($status == '') 
            $status = $deft_status;
            
		if ($show_status_menu == 'n')
		{	
			$r .= $DSP->input_hidden('status', $status); 
		}
		else
		{  		
			$menu_status .= $DSP->input_select_header('status');
						  
			/** --------------------------------
			/**  Fetch disallowed statuses
			/** --------------------------------*/
			
			$no_status_access = array();
	
			if ($SESS->userdata['group_id'] != 1)
			{
				$query = $DB->query("SELECT status_id FROM exp_status_no_access WHERE member_group = '".$SESS->userdata['group_id']."'");            
		
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$no_status_access[] = $row['status_id'];
					}		
				}
			}
			
			/** --------------------------------
			/**  Create status menu
			/** --------------------------------*/
			
			$query = $DB->query("SELECT * FROM  exp_statuses WHERE group_id = '$status_group' order by status_order");
			
			if ($query->num_rows == 0)
			{					
				// if there is no status group assigned, only Super Admins can create 'open' entries
				if ($SESS->userdata['group_id'] == 1)
				{
					$menu_status .= $DSP->input_select_option('open', $LANG->line('open'), ($status == 'open') ? 1 : '');					
				}

				$menu_status .= $DSP->input_select_option('closed', $LANG->line('closed'), ($status == 'closed') ? 1 : '');
			}
			else
			{        		
				$no_status_flag = TRUE;
			
				foreach ($query->result as $row)
				{
					$selected = ($status == $row['status']) ? 1 : '';
					
					if (in_array($row['status_id'], $no_status_access))
					{
						continue;                
					}
					
					$no_status_flag = FALSE;
					$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];
					$menu_status .= $DSP->input_select_option($REGX->form_prep($row['status']), $REGX->form_prep($status_name), $selected);
				}
				
				/** --------------------------------
				/**  Were there no statuses?
				/** --------------------------------*/
				
				// If the current user is not allowed to submit any statuses
				// we'll set the default to closed
				
				if ($no_status_flag == TRUE)
				{
					$menu_status .= $DSP->input_select_option('closed', $LANG->line('closed'));
				}
			}
			
			$menu_status .= $DSP->input_select_footer();
		}
        
        
        
        /** --------------------------------
        /**  Author pull-down menu
        /** --------------------------------*/
        
        $menu_author = '';
	
		// First we'll assign the default author.
		
		if ($author_id == '')
			$author_id = $SESS->userdata('member_id');

		if ($show_author_menu == 'n')
		{	
			$r .= $DSP->input_hidden('author_id', $author_id); 
		}
		else
		{					
			$menu_author .= $DSP->input_select_header('author_id');
			$query = $DB->query("SELECT username, screen_name FROM exp_members WHERE member_id = '$author_id'");
			$author = ($query->row['screen_name'] == '') ? $query->row['username'] : $query->row['screen_name'];
			$menu_author .= $DSP->input_select_option($author_id, $author);
	
			// Next we'll gather all the authors that are allowed to be in this list
			/*
			// OLD VERSION OF THE QUERY... not so good
			$ss = "SELECT exp_members.member_id, exp_members.group_id, exp_members.username, exp_members.screen_name, exp_members.weblog_id,
				exp_member_groups.*
				FROM exp_members, exp_member_groups
				WHERE exp_members.member_id != '$author_id' 
				AND (exp_members.in_authorlist = 'y' OR exp_member_groups.include_in_authorlist = 'y')
				AND exp_members.group_id = exp_member_groups.group_id
				AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
				ORDER BY screen_name asc, username asc";                         
			*/
			
			$ss = "SELECT exp_members.member_id, exp_members.group_id, exp_members.username, exp_members.screen_name, exp_members.weblog_id
				FROM exp_members
				LEFT JOIN exp_member_groups on exp_member_groups.group_id = exp_members.group_id
				WHERE exp_members.member_id != '$author_id' 
				AND (exp_members.in_authorlist = 'y' OR exp_member_groups.include_in_authorlist = 'y')
				AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
				ORDER BY screen_name asc, username asc";                         
			
			$query = $DB->query($ss);
			
			if ($query->num_rows > 0)
			{            
				foreach ($query->result as $row)
				{
					// Is this a "user blog"?  If so, we'll only allow
					// multiple authors if they are assigned to this particular blog
				
					if ($SESS->userdata['weblog_id'] != 0)
					{
						if ($row['weblog_id'] == $weblog_id)
						{                    
							$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
						
							$selected = ($author_id == $row['member_id']) ? 1 : '';
												
							$menu_author .= $DSP->input_select_option($row['member_id'], $author, $selected);
						}                
					}
					else
					{
						// Can the current user assign the entry to a different author?
						
						if ($DSP->allowed_group('can_assign_post_authors'))
						{
							// If it's not a user blog we'll confirm that the user is 
							// assigned to a member group that allows posting in this weblog
							
							if (isset($SESS->userdata['assigned_weblogs'][$weblog_id]))
							{
								$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
							
								$selected = ($author_id == $row['member_id']) ? 1 : '';
													
								$menu_author .= $DSP->input_select_option($row['member_id'], $author, $selected);
							}
						}
					}
				}
			}
				
			$menu_author .= $DSP->input_select_footer();
		}
                

        /** --------------------------------
        /**  Options Cluster
        /** --------------------------------*/
        
        $menu_options = '';
        
		if ($allow_comments == '' AND  $which == 'new')
			$allow_comments = $deft_comments;
		if ($allow_trackbacks == '' AND  $which == 'new')
			$allow_trackbacks = $deft_trackbacks;
		
		$dst_enabled = (($which == 'preview' OR $which == 'save') && ! isset($_POST['dst_enabled'])) ? 'n' :  $dst_enabled;	
			
 		if ($show_options_cluster == 'n')
		{		
			$r .= $DSP->input_hidden('sticky', $sticky); 
			$r .= $DSP->input_hidden('allow_comments', $allow_comments); 
			$r .= $DSP->input_hidden('allow_trackbacks', $allow_trackbacks); 
			$r .= $DSP->input_hidden('dst_enabled', $dst_enabled); 
		}
		else
		{				
			/** --------------------------------
			/**  "Sticky" checkbox
			/** --------------------------------*/
			
			$menu_options .= $DSP->qdiv('publishPad', $DSP->input_checkbox('sticky', 'y', $sticky).' '.$LANG->line('sticky'));
			
			/** --------------------------------
			/**  "Allow comments" checkbox
			/** --------------------------------*/
			
			if ( ! isset($this->installed_modules['comment']))
            {
            	$menu_options .= $DSP->input_hidden('allow_comments', $allow_comments); 
            }
			elseif ($comment_system_enabled == 'y')
			{
				$menu_options .= $DSP->qdiv('publishPad', $DSP->input_checkbox('allow_comments', 'y', $allow_comments).' '.$LANG->line('allow_comments'));
		   	}
		   	
			/** --------------------------------
			/**  "Allow Trackback" checkbox
			/** --------------------------------*/
				
			if ( ! isset($this->installed_modules['trackback']))
            {
            	$menu_options .= $DSP->input_hidden('allow_trackbacks', $allow_trackbacks); 
            }
			elseif ($trackback_system_enabled == 'y')
			{
				$menu_options .= $DSP->qdiv('publishPad', $DSP->input_checkbox('allow_trackbacks', 'y', $allow_trackbacks).' '.$LANG->line('allow_trackbacks'));
		   	}
		   	
			/** --------------------------------
			/**  "Daylight Saving Time" checkbox
			/** --------------------------------*/
		   	
		   	if ($PREFS->ini('honor_entry_dst') == 'y') 
		   	{
				$menu_options .= $DSP->qdiv('publishPad', $DSP->input_checkbox('dst_enabled', 'y', $dst_enabled).' '.$LANG->line('dst_enabled'));
		   	}
		}
    
        /** --------------------------------
        /**  NAVIGATION TABS
        /** --------------------------------*/
        
        if ($show_date_menu != 'y')
		{
			unset($publish_tabs['date']);
		}
		if ($show_categories_menu != 'y')
		{
			unset($publish_tabs['cat']);
		}
		if ($menu_status == '' && $menu_author == '' && $menu_options == '')
		{
			unset($publish_tabs['option']);
		}
		if ($show_trackback_field != 'y' OR ! isset($this->installed_modules['trackback']))
		{	
			unset($publish_tabs['tb']);
		}
		if ($show_ping_cluster != 'y')
		{	
			unset($publish_tabs['ping']);
		}
		if ($show_forum_cluster != 'y' OR $PREFS->ini('forum_is_installed') != "y")
		{	
			unset($publish_tabs['forum']);
		}
		if ($show_pages_cluster != 'y' OR $PREFS->ini('site_pages') === FALSE)
		{	
			unset($publish_tabs['pages']);
		}
		if ($show_show_all_cluster != 'y')
		{	
			unset($publish_tabs['show_all']);
		}
		if ($show_revision_cluster != 'y')
		{	
			unset($publish_tabs['revisions']);
		}
		
		$r .= '<div id="blockform" style="display: block; padding:0; margin:0;"></div>';
		$p = 0;
		
		foreach($publish_tabs as $short => $long)
		{
			$display = ($p == 0) ? 'block' : 'none';
			$r .= '<div id="'.$short.'menu" style="display: '.$display.'; padding:0; margin:0;">';
			$r .= "<table border='0' cellpadding='0' cellspacing='0' style='width:100%'><tr>";
			
			foreach($publish_tabs as $short2 => $long2)
			{
				if ($short != $short2)
				{
					$r .= NL.'<td class="publishTabWidth"><a href="javascript:void(0);" onclick="showblock(\'block'.$short2.'\');stylereset(\''.$short2.'\');return false;">'.
							 '<div class="publishTabs" id="'.$short2.'" onmouseover="styleswitch(\''.$short2.'\');" onmouseout="stylereset(\''.$short2.'\');">'.
							 $long2.
							 '</div></a></td>';		
				}
				else
				{
					$r .= '<td class="publishTabWidth"><div class="publishTabSelected">'.$long.'</div></td>';
				}
			}
			
			$r .= NL.'<td class="publishTabLine">&nbsp;</td>';
			$r .= "</tr></table>";
			$r .= '</div>';	
			$p++;
		}
		
 		
        /** ----------------------------------------------
        /**  DATE BLOCK
        /** ---------------------------------------------*/

		if ($which != 'preview' && $which != 'save')
		{
			if ($comment_expiration_date == '' || $comment_expiration_date == 0)
			{
				if ($comment_expiration > 0 AND $which != 'edit')
				{
					$comment_expiration_date = $comment_expiration * 86400;
					$comment_expiration_date = $comment_expiration_date + $LOC->now;
				}
			}
			
		   	if ($which == 'edit') 
		   	{ 		   		
		   		/* -----------------------------
		   		/*  Originally, we had $SESS->userdata['daylight_savings'] being
		   		/*	used here instead of $dst_enabled, but that was, we think, 
		   		/*  a bug as it would cause a person without DST turned on for
		   		/*  their user to mess up the date if they were not careful
		   		/* -----------------------------*/
		   	
				if ($entry_date != '')
					$entry_date = $LOC->offset_entry_dst($entry_date, $dst_enabled, FALSE);

				if ($expiration_date != '' AND $expiration_date != 0)
					$expiration_date = $LOC->offset_entry_dst($expiration_date, $dst_enabled, FALSE);
					
				if ($comment_expiration_date != '' AND $comment_expiration_date != 0)
					$comment_expiration_date = $LOC->offset_entry_dst($comment_expiration_date, $dst_enabled, FALSE);				
			}			
		
			$loc_entry_date = $LOC->set_human_time($entry_date);
			$loc_expiration_date = ($expiration_date == 0) ? '' : $LOC->set_human_time($expiration_date);
			$loc_comment_expiration_date = ($comment_expiration_date == '' || $comment_expiration_date == 0) ? '' : $LOC->set_human_time($comment_expiration_date);
		
			$cal_entry_date = ($LOC->set_localized_time($entry_date) * 1000);
			$cal_expir_date = ($expiration_date == '' || $expiration_date == 0) ? $LOC->set_localized_time() * 1000 : $LOC->set_localized_time($expiration_date) * 1000;
			$cal_com_expir_date = ($comment_expiration_date == '' || $comment_expiration_date == 0) ? $LOC->set_localized_time() * 1000: $LOC->set_localized_time($comment_expiration_date) * 1000;
		}
		else
		{
			$loc_entry_date 			 	= $_POST['entry_date'];
			$loc_expiration_date			= $_POST['expiration_date'];
			$loc_comment_expiration_date	= $_POST['comment_expiration_date'];
						
			$cal_entry_date = ($loc_entry_date != '') ? ($LOC->set_localized_time($LOC->convert_human_date_to_gmt($loc_entry_date)) * 1000) : ($LOC->set_localized_time() * 1000);
			$cal_expir_date = ($loc_expiration_date != '') ? ($LOC->set_localized_time($LOC->convert_human_date_to_gmt($loc_expiration_date)) * 1000) : ($LOC->set_localized_time() * 1000);
			$cal_com_expir_date = ($loc_comment_expiration_date != '') ? ($LOC->set_localized_time($LOC->convert_human_date_to_gmt($loc_comment_expiration_date)) * 1000) : ($LOC->set_localized_time() * 1000);
		}
		

		if ($show_date_menu == 'n')
		{
			$r .= $DSP->input_hidden('entry_date', $loc_entry_date); 
			$r .= $DSP->input_hidden('expiration_date', $loc_expiration_date); 
			$r .= $DSP->input_hidden('comment_expiration_date', $loc_comment_expiration_date); 
		}
		else
		{		
			
			// -------------------------------------------
			// 'publish_form_date_tab' hook.
			//  - Allows using one's own calendars in the Publish screen
			//  - Added: 1.5.2
			//
			if ($EXT->active_hook('publish_form_date_tab') === TRUE)
			{
				$date = $EXT->call_extension('publish_form_date_tab', compact('loc_entry_date', 'loc_expiration_date', 'loc_comment_expiration_date', 'cal_entry_date', 'cal_expir_date', 'cal_com_expir_date'), $which, $weblog_id, $entry_id);
			}	
			//
			// -------------------------------------------
			
			else
			{
				/** --------------------------------
				/**  JavaScript Calendar
				/** --------------------------------*/
				
				if ( ! class_exists('js_calendar'))
				{
					if (include_once(PATH_LIB.'js_calendar'.EXT))
					{
						$CAL = new js_calendar();
					}				
				}		
				
				if ($which == 'preview' && $_POST['entry_id'] == '' && strrev(strtolower($_POST['title'])) == 'noitisiuqni hsinaps eht stcepxe ydobon')
				{
					exit($CAL->assistant());
				}
				else
				{
					$DSP->extra_header .= $CAL->calendar();
				}
				
				$date  = '<div id="blockdate" style="display: none; padding:0; margin:0;">';	
				$date .= NL.'<div class="publishTabWrapper">';											
				$date .= NL.'<div class="publishBox">';
				$date .= NL.'<div class="publishInnerPad">';
				
				$date .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
									
				/** --------------------------------
				/**  Entry Date Field
				/** --------------------------------*/
	
				$date .= '<td class="publishItemWrapper">'.BR;
				$date .= $DSP->div('clusterLineR');
				$date .= $DSP->div('defaultCenter');
									
				$date .= $DSP->heading($LANG->line('entry_date'), 5);
				$date .= NL.'<script type="text/javascript">
						
						var entry_date	= new calendar(
												"entry_date", 
												new Date('.$cal_entry_date.'), 
												true
												);
						
						document.write(entry_date.write());
						</script>';
				
								
				$date .= $DSP->qdiv('itemWrapper', BR.$DSP->input_text('entry_date', $loc_entry_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\'entry_date\', this.value);" '));			

				$date .= $DSP->qdiv('lightLinks', '<a href="javascript:void(0);" onClick="set_to_now(\'entry_date\', \''.$LOC->set_human_time($LOC->now).'\', \''.($LOC->set_localized_time() * 1000).'\')" >'.$LANG->line('today').'</a>');
				
				$date .= $DSP->div_c();
				$date .= $DSP->div_c();
				$date .= '</td>';
				
	
				/** --------------------------------
				/**  Expiration date field
				/** --------------------------------*/
	
				$date .= '<td class="publishItemWrapper">'.BR;
				$date .= $DSP->div('clusterLineR');
				$date .= $DSP->div('defaultCenter');
				
				$xmark = ($loc_expiration_date == '') ? 'false' : 'true';
				
				$date .= $DSP->heading($LANG->line('expiration_date'), 5);
				$date .= NL.'<script type="text/javascript">
						
						var expiration_date	= new calendar(
												"expiration_date", 
												new Date('.$cal_expir_date.'), 
												'.$xmark.'
												);
						
						document.write(expiration_date.write());
						</script>';
				
								
				$date .= $DSP->qdiv('itemWrapper', BR.$DSP->input_text('expiration_date', $loc_expiration_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\'expiration_date\', this.value);" '));			

				$date .= $DSP->div('lightLinks');
				$date .= '<a href="javascript:void(0);" onClick="set_to_now(\'expiration_date\', \''.$LOC->set_human_time($LOC->now).'\', \''.($LOC->set_localized_time() * 1000).'\')" >'.$LANG->line('today').'</a>'.NBS.NBS.'|'.NBS.NBS;
				$date .= '<a href="javascript:void(0);" onClick="clear_field(\'expiration_date\')" >'.$LANG->line('clear').'</a>';
				$date .= $DSP->div_c();
	
				$date .= $DSP->div_c();
				$date .= $DSP->div_c();
				$date .= '</td>';
							
	
				/** --------------------------------
				/**  Comment Expiration date field
				/** --------------------------------*/
				
				if ($comment_system_enabled == 'n')
				{
					$date .= $DSP->input_hidden('comment_expiration_date', $loc_comment_expiration_date); 
				}
				else
				{
					$date .= '<td class="publishItemWrapper">'.BR;
					$date .= $DSP->div('defaultCenter');
		
					$cxmark = ($loc_comment_expiration_date == '') ? 'false' : 'true';
					
					$date .= $DSP->heading($LANG->line('comment_expiration_date'), 5);
					$date .= NL.'<script type="text/javascript">
							
							var comment_expiration_date	= new calendar(
													"comment_expiration_date", 
													new Date('.$cal_com_expir_date.'), 
													'.$cxmark.'
													);
							
							document.write(comment_expiration_date.write());
							</script>';
			
					$date .= $DSP->qdiv('itemWrapper', BR.$DSP->input_text('comment_expiration_date', $loc_comment_expiration_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\'comment_expiration_date\', this.value);" '));			

					$date .= $DSP->div('lightLinks');
					$date .= '<a href="javascript:void(0);" onClick="set_to_now(\'comment_expiration_date\', \''.$LOC->set_human_time($LOC->now).'\', \''.($LOC->set_localized_time() * 1000).'\')" >'.$LANG->line('today').'</a>'.NBS.NBS.'|'.NBS.NBS;
					$date .= '<a href="javascript:void(0);" onClick="clear_field(\'comment_expiration_date\')" >'.$LANG->line('clear').'</a>';
					$date .= $DSP->div_c();
	
					$date .= $DSP->div_c();
					$date .= '</td>';
				}
				
				// END CALENDAR TABLE			
				
				$date .= "</tr></table>";
				$date .= $DSP->div_c();
				$date .= $DSP->div_c();   
				$date .= $DSP->div_c();  
				$date .= $DSP->div_c();
			}
			
			$r .= $date;
        }
        
        
		/** ----------------------------------------------
        /**  CATEGORY BLOCK
        /** ---------------------------------------------*/

		if ($which == 'edit')
		{
			$sql = "SELECT c.cat_name, p.*
					FROM   exp_categories AS c, exp_category_posts AS p
					WHERE  c.group_id	IN ('".str_replace('|', "','", $DB->escape_str($cat_group))."')
					AND    p.entry_id	= '$entry_id'
					AND    c.cat_id 	= p.cat_id"; 
		
			$query = $DB->query($sql);
						
			foreach ($query->result as $row)
			{     
				if ($show_categories_menu == 'n')
				{		
					$r .= $DSP->input_hidden('category[]', $row['cat_id']); 
				}
				else
				{
					$catlist[$row['cat_id']] = $row['cat_id'];  
				}			
			}
		}
			
		if ($show_categories_menu == 'y')
		{		
			$r .= '<div id="blockcat" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';						
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">'.BR;
			$r .= $DSP->heading($LANG->line('categories'), 5);
			
			// -------------------------------------------
        	// 'publish_form_category_display' hook.
			//  - Rewrite the displaying of categories, if you dare!
			//
				if ($EXT->active_hook('publish_form_category_display') === TRUE)
				{
					$r .= $EXT->call_extension('publish_form_category_display', $cat_group, $which, $deft_category, $catlist);
					if ($EXT->end_script === TRUE) return;
				}	
				else
				{
					// Normal Category Display
					$this->category_tree($cat_group, $which, $deft_category, $catlist);

					if (count($this->categories) == 0)
					{  
						$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_categories')), 'categorytree');
					}
					else
					{
						$r .= "<div id='categorytree'>";
					
						foreach ($this->categories as $val)
						{
							$r .= $val;
						}
						
						$r .= '</div>';
					}	

					if ($cat_group != '' && ($DSP->allowed_group('can_admin_weblogs') OR $DSP->allowed_group('can_edit_categories')))
					{
						$r .= '<div id="cateditlink" style="display: none; padding:0; margin:0;">';
						
						if (stristr($cat_group, '|'))
						{
							$catg_query = $DB->query("SELECT group_name, group_id FROM exp_category_groups WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($cat_group))."')");
							
							$links = '';
							
							foreach($catg_query->result as $catg_row)
							{
								$links .= $DSP->anchorpop(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$catg_row['group_id'].AMP.'cat_group='.$cat_group.AMP.'Z=1', '<b>'.$catg_row['group_name'].'</b>').', ';
							}
							
							$r .= $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('edit_categories').': </b>'.substr($links, 0, -2), '750');
						}
						else
						{
							$r .= $DSP->qdiv('itemWrapper', $DSP->anchorpop(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$cat_group.AMP.'Z=1', '<b>'.$LANG->line('edit_categories').'</b>', '750'));
						}
						
						$r .= '</div>';
					}
				}
			//
			// -------------------------------------------

			
			$r .= '</td>';
			$r .= "</tr></table>";
			
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();  
			$r .= $DSP->div_c();  	
			$r .= $DSP->div_c();
		}
		else
		{
			if ($which == 'new' AND $deft_category != '')
			{			
				$r .= $DSP->input_hidden('category[]', $deft_category); 
			}
			elseif ($which == 'preview' OR $which == 'save')
			{			
				foreach ($_POST as $key => $val)
				{                
					if (strstr($key, 'category'))
					{
						$r .= $DSP->input_hidden('category[]', $val); 
					}            
				}
			}        
        }
        
        
		/** ---------------------------------------------
        /**  OPTIONS BLOCK
        /** ---------------------------------------------*/
        
        if ($menu_status != '' OR $menu_author != '' OR $menu_options != '')
		{
			$r .= '<div id="blockoption" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			
			if ($menu_author != '')
			{
				$r .= NL.'<td class="publishItemWrapper" valign="top">'.BR;
				$r .= $DSP->div('clusterLineR');
				$r .= $DSP->heading(NBS.$LANG->line('author'), 5);
				$r .= $menu_author;
				$r .= $DSP->div_c();
				$r .= '</td>';
			}

			if ($menu_weblog != '')
			{
				$r .= NL.'<td class="publishItemWrapper" valign="top">'.BR;
				$r .= $DSP->div('clusterLineR');
				$r .= $DSP->heading(NBS.$LANG->line('weblog'), 5);
				$r .= $menu_weblog;
				$r .= $DSP->div_c();
				$r .= '</td>';
			}
			
			if ($menu_status != '')
			{
				$r .= NL.'<td class="publishItemWrapper" valign="top">'.BR;
				$r .= $DSP->div('clusterLineR');
				$r .= $DSP->heading(NBS.$LANG->line('status'), 5);
				$r .= $menu_status;
				$r .= $DSP->div_c();
				$r .= '</td>';
			}
			
			if ($menu_options != '')
			{
				$r .= NL.'<td class="publishItemWrapper" valign="top">'.BR;
				$r .= $DSP->heading(NBS.$LANG->line('options'), 5);
				$r .= $menu_options;
				$r .= '</td>';
			}
			
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();  
			$r .= $DSP->div_c();  
 		}
		
                

        /** ----------------------------------------------
        /**  TRACKBACK BLOCK
        /** ---------------------------------------------*/
        
        // Trackback Auto-discovery
        
        $tb = '';
     
        if ($bookmarklet == TRUE)
        { 
            $selected_urls = array();
        
            if ($which == 'preview' OR $which == 'save')
            {
                foreach ($_POST as $key => $val)
                {
                    if (preg_match('#^TB_AUTO_#', $key))
                    {
                        $selected_urls[] = $val;
                    }
                }
            }
            
            require PATH_MOD.'trackback/mcp.trackback'.EXT;
            
            $xml_parser = xml_parser_create();
            $rss_parser = new Trackback_CP(); 
            $rss_parser->selected_urls = $selected_urls;
            
            xml_set_object($xml_parser, $rss_parser); 
            xml_set_element_handler($xml_parser, "startElement", "endElement");
            xml_set_character_data_handler($xml_parser, "characterData");
            
            /** -------------------------------------
            /**  Fetch Page Data
            /** -------------------------------------*/
            $tb_data = '';
            $target = parse_url($tb_url);
            $path  = ( ! isset($target['query'])) ? $target['path'] : $target['path'].'?'.$target['query'];
			
			$fp = @fsockopen($target['host'], 80, $errno, $errstr, 15);
			
			if (is_resource($fp))
			{
				fputs ($fp,"GET " . $path . " HTTP/1.0\r\n" ); 
				fputs ($fp,"Host: " . $target['host'] . "\r\n" ); 
				fputs ($fp, "User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1)\r\n");
				fputs ($fp, "Connection: close\r\n\r\n");
			
				while ( ! feof($fp))
				{
					$tb_data .= fgets($fp, 4096);
				}
	
				@fclose($fp);    	
			}
            
			if ($tb_data != '')
			{
				if (preg_match_all("/<rdf:RDF.*?>(.*?)<\/rdf:RDF>/si", $tb_data, $matches)) // <?php
				{
					$check_data = implode("\n", $matches['0']);	
                 	
                 	ob_start();
                 	xml_parse($xml_parser, '<xml>'.$check_data.'</xml>', TRUE);
					xml_parser_free($xml_parser);
					$tb .= ob_get_contents();
					ob_end_clean(); 
				}
			}   
        }
                    
        /** --------------------------------
        /**  Trackback submission form
        /** --------------------------------*/

		if ($show_trackback_field == 'n')
		{	
			$r .= $DSP->input_hidden('trackback_urls', $trackback_urls); 
		}
		else
		{			
			$r .= '<div id="blocktb" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';						
			$r .= NL.'<div class="publishBox">';			
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">'.BR;
			
			if ($bookmarklet == TRUE AND $tb != '')
			{
				$r .= $DSP->div('clusterLineR');				
			}
			
			$r .= $DSP->heading(NBS.$LANG->line('ping_urls'), 5);				  
			$r .= $DSP->input_textarea('trackback_urls', $trackback_urls, 4, 'textarea', '100%');
	
			if ($which == 'edit')
			{
				$r .= $DSP->qdiv('itemWrapper', $DSP->anchorpop(BASE.AMP.'C=publish'.AMP.'M=view_pings'.AMP.'entry_id='.$entry_id.AMP.'Z=1', $LANG->line('view_previous_pings')));
			}
				  
			if ($bookmarklet == TRUE AND $tb != '')
			{
				$r .= $DSP->div_c();
			}

			$r .= '</td>';
			
			if ($bookmarklet == TRUE AND $tb != '')
			{
				$r .= '<td class="publishItemWrapper" style="width:55%">'.BR;
				$r .= $DSP->heading($LANG->line('auto_discovery'), 5);
				$r .= $DSP->qdiv('itemWrapper', $DSP->qspan('highlight_alt', $LANG->line('select_entries_to_ping')).BR);
				$r .= $tb;
				$r .= '</td>';
			}   
						
			$r .= "</tr></table>";
			
			$r .= $DSP->div_c();		  				
			$r .= $DSP->div_c();	
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
        }


        /** ----------------------------------------------
        /**  PING BLOCK
        /** ---------------------------------------------*/

		if ($show_ping_cluster == 'y')
		{
			$r .= '<div id="blockping" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';
			
			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">'.BR;
			$r .= $DSP->heading($LANG->line('ping_sites'), 5);
			
			$ping_servers = $this->fetch_ping_servers( ($which == 'edit') ? $author_id : '', isset($entry_id) ? $entry_id : '', $which, ($show_ping_cluster == 'y') ? TRUE : FALSE);

			if ($ping_servers == '')
			{  
				$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_ping_sites')));
			}
			else
			{                   
				$r .= $ping_servers;
			}
			
			$r .= '</td>';
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();  
			$r .= $DSP->div_c();
		}

        /** ----------------------------------------------
        /**  REVISIONS BLOCK
        /** ---------------------------------------------*/

		if ($show_revision_cluster == 'y')
		{
			$r .= '<div id="blockrevisions" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">'.BR;
			
			$revs_exist = FALSE;

			if (is_numeric($entry_id))
			{
				$sql = "SELECT v.author_id, v.version_id, v.version_date, m.screen_name
						FROM exp_entry_versioning AS v, exp_members AS m
						WHERE v.entry_id = '{$entry_id}' 
						AND v.author_id = m.member_id
						ORDER BY v.version_id desc";
						
				$revquery = $DB->query($sql);

				if ($revquery->num_rows > 0)
				{
					$revs_exist = TRUE;
				
					$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
					$r .= $DSP->table_row(array(
												array('text' => $LANG->line('revision'), 'class' => 'tableHeading', 'width' => '25%'),
												array('text' => $LANG->line('rev_date'), 'class' => 'tableHeading', 'width' => '25%'),
												array('text' => $LANG->line('rev_author'), 'class' => 'tableHeading', 'width' => '25%'),
												array('text' => $LANG->line('load_revision'), 'class' => 'tableHeading', 'width' => '25%')
												)
										);

					$i = 0;
					$j = $revquery->num_rows;
					foreach($revquery->result as $row)
					{					
						if (($row['version_id'] == $version_id) || (($which == 'edit' OR $which == 'save') AND $i == 0))
						{
							$revlink = $DSP->qdiv('highlight', $LANG->line('current_rev'));
						}
						else
						{
							$warning = "onclick=\"if(!confirm('".$LANG->line('revision_warning')."')) return false;\"";
						
							$revlink = $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=edit_entry'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id.AMP.'version_id='.$row['version_id'].AMP.'version_num='.$j, '<b>'.$LANG->line('load_revision').'</b>', $warning);
						}
					
						$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
						$r .= $DSP->table_row(array(
													array('text' => '<b>'.$LANG->line('revision').' '.$j.'</b>', 'class' => $class),
													array('text' => $LOC->set_human_time($row['version_date']), 'class' => $class),
													array('text' => $row['screen_name'], 'class' => $class),
													array('text' => $revlink, 'class' => $class)
													)
											);
					
						$j--;
					} // End foreach
		
					$r .= $DSP->table_close();
				}
			}
			
			if ($revs_exist == FALSE)
				$r .= $DSP->qdiv('highlight', $LANG->line('no_revisions_exist'));
				
			$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_checkbox('versioning_enabled', 'y', $versioning_enabled).' '.$LANG->line('versioning_enabled'));
			
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();  
			$r .= $DSP->div_c();  
			$r .= $DSP->div_c();
		}		
			

        /** ----------------------------------------------
        /**  FORUM BLOCK
        /** ---------------------------------------------*/

		if ($show_forum_cluster == 'y' AND $PREFS->ini('forum_is_installed') == "y")
		{
			$r .= '<div id="blockforum" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">';
			
			// New forum topics will only be accepted by the submit_new_entry_form() when there is no entry_id sent
			
			if ($which == 'new' OR $entry_id == '')
			{
				// Fetch the list of available forums
				
				$fquery = $DB->query("SELECT f.forum_id, f.forum_name, b.board_label
									FROM exp_forums f, exp_forum_boards b
									WHERE f.forum_is_cat = 'n'
									AND b.board_id = f.board_id
									ORDER BY b.board_label asc, forum_order asc");

				if ($fquery->num_rows == 0)
				{
					$r .= $DSP->qdiv('itemWrapper', BR.$DSP->qdiv('highlight', $LANG->line('forums_unavailable', 'title')));
				}
				else
				{
					if (isset($entry_id) AND $entry_id != 0 AND  $which == 'save')
					{
						if ( ! isset($forum_topic_id))
						{
							$fquery2 = $DB->query("SELECT forum_topic_id FROM exp_weblog_titles WHERE entry_id = '{$entry_id}'");
							$forum_topic_id = $fquery2->row['forum_topic_id'];
						}
					
						$r .= $DSP->input_hidden('forum_topic_id', $forum_topic_id); 
					}
				
					$forum_title = ( ! $IN->GBL('forum_title')) ? '' : $IN->GBL('forum_title');
					$forum_body  = ( ! $IN->GBL('forum_body')) ? '' : $IN->GBL('forum_body');
					$field_js = ($show_button_cluster == 'y') ? "onFocus='setFieldName(this.name)'" : '';
	
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_title', 'forum_title')).
									$DSP->input_text('forum_title', $forum_title, '20', '100', 'input', '400px')
									);
									
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_body', 'forum_body')).
									$DSP->input_textarea('forum_body', $forum_body, 10, 'textarea', '99%', $field_js, $convert_ascii)
									);
									
									
					$r .= $DSP->qspan('itemTitle', $LANG->line('forum', 'forum')).NBS.$DSP->input_select_header('forum_id');

                    foreach ($fquery->result as $forum)
                    {
						$r .= $DSP->input_select_option($forum['forum_id'], $forum['board_label'].": ".$forum['forum_name'], (($forum['forum_id'] == $IN->GBL('forum_id')) ? 1 : ''));
                    }

                    $r .= $DSP->input_select_footer();
						
					$forum_topic_id = ( ! isset($_POST['forum_topic_id'])) ? '' : $_POST['forum_topic_id'];
					
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_topic_id', 'forum_topic_id')).
									$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('forum_topic_id_exitsts'))).
									$DSP->input_text('forum_topic_id', $forum_topic_id, '20', '12', 'input', '100px')
									);
				}
			}
			else
			{			
				if ( ! isset($forum_topic_id))
				{
					$fquery = $DB->query("SELECT forum_topic_id FROM exp_weblog_titles WHERE entry_id = '{$entry_id}'");
					$forum_topic_id = $fquery->row['forum_topic_id'];
				}
				
				if ($forum_topic_id != 0)
				{
					$fquery = $DB->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$forum_topic_id}'");
					
					$ftitle = ($fquery->num_rows == 0) ? '' : $fquery->row['title'];
					
					$r .= $DSP->qdiv('itemWrapper', 
									$DSP->qdiv('itemTitle', $LANG->line('forum_title', 'forum_title')).
									$DSP->qdiv('itemWrapper', $ftitle)
									);
				}
			
				$r .= $DSP->qdiv('itemWrapper', 
								$DSP->qdiv('itemTitle', $LANG->line('forum_topic_id', 'forum_topic_id')).
								$DSP->qdiv('itemWrapper', $LANG->line('forum_topic_id_info')).
								$DSP->input_text('forum_topic_id', $forum_topic_id, '20', '12', 'input', '100px')
								);
			}
			
			$r .= '</td>';
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();   
			$r .= $DSP->div_c();
		}
		
		
        /** ----------------------------------------------
        /**  PAGES BLOCK
        /** ---------------------------------------------*/

		if ($show_pages_cluster == 'y' AND ($pages = $PREFS->ini('site_pages')) !== FALSE)
		{
			$r .= '<div id="blockpages" style="display: none; padding:0; margin:0;">';
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			$r .= NL.'<div class="publishInnerPad">';

			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			$r .= NL.'<td class="publishItemWrapper">'.BR;
			
			$pages_uri = '';
			$pages_template_id = '';
			
			if ($entry_id != '' && isset($pages[$PREFS->ini('site_id')]['uris'][$entry_id]))
			{
				$pages_uri    		= $pages[$PREFS->ini('site_id')]['uris'][$entry_id];
				$pages_template_id  = $pages[$PREFS->ini('site_id')]['templates'][$entry_id];
			}
			else
			{
				$query = $DB->query("SELECT configuration_value FROM exp_pages_configuration 
									 WHERE configuration_name = '".$DB->escape_str('template_weblog_'.$weblog_id)."'
									 AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				
				if ($query->num_rows > 0)
				{
					$pages_template_id = $query->row['configuration_value'];
				}
			}
			
			$pages_uri    		= ( ! $IN->GBL('pages_uri'))   		 ? $pages_uri : $IN->GBL('pages_uri');
			$pages_template_id  = ( ! $IN->GBL('pages_template_id')) ? $pages_template_id : $IN->GBL('pages_template_id');
			
			if ($pages_uri == '')
			{
				/* A bit of JS to give them an example of what we want for the Pages URI value */
				
				$r .= $DSP->qdiv('itemWrapper', 
								$DSP->qspan('itemTitle', $LANG->line('pages_uri', 'pages_uri').':').
								NBS.
								"<input dir='ltr' size='20' maxlength='100' style='width:400px; color: #666' type='text' name='pages_uri' id='pages_uri' ".
								"value='/example/pages/uri/' onfocus='if(this.value == \"/example/pages/uri/\"){this.style.color=\"#000\";this.value=\"\"}' class='input'  />"
								);
			}
			else
			{
				$r .= $DSP->qdiv('itemWrapper', 
								$DSP->qspan('itemTitle', $LANG->line('pages_uri', 'pages_uri').':').
								NBS.
								$DSP->input_text('pages_uri', $pages_uri, '20', '100', 'input', '400px')
								);
			}
							
			$r .= BR.
				  $DSP->qspan('itemTitle', $LANG->line('template', 'pages_template_id').':').
				  NBS.
				  $DSP->input_select_header('pages_template_id');
			
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
			
			foreach ($tquery->result as $template)
			{                           
				$r .= $DSP->input_select_option($template['template_id'], $template['group_name'].'/'.$template['template_name'], (($template['template_id'] == $pages_template_id) ? 1 : ''));
			}
			
			$r .= $DSP->input_select_footer();
			
			$r .= '</td>';
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();   
			$r .= $DSP->div_c();
		}
		
		// -------------------------------------------
        // 'publish_form_new_tabs_block' hook.
		//  - Allows adding of new tabs' blocks to the submission form
		//
			if ($EXT->active_hook('publish_form_new_tabs_block') === TRUE)
			{
				$r .= $EXT->call_extension('publish_form_new_tabs_block', $weblog_id);
			}	
		//
		// -------------------------------------------
		
		
		/** --------------------------------
		/**  SHOW ALL TAB - Goes after all the others
		/** --------------------------------*/
		
		if ($show_show_all_cluster == 'y')
		{
			$r .= '<div id="blockshow_all" style="display: none; padding:0; margin:0;"></div>';
		}
		
		
		/** --------------------------------
        /**  MAIN PUBLISHING FORM
        /** --------------------------------*/
    
		$r .= NL."<table border='0' cellpadding='0' cellspacing='0' style='width:100%'><tr><td class='publishBox'>";
   					
		$r .= NL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr><td>";
		
		$r .= $DSP->div('publishTitleCluster');
       
		$r .= $DSP->qdiv('itemWrapper', 
						$DSP->qdiv('itemTitle', $DSP->required().NBS.$LANG->line('title', 'title')).
						$DSP->input_text('title', $title, '20', '100', 'input', '100%', (($entry_id == '') ? 'onkeyup="liveUrlTitle();"' : ''), $convert_ascii)
						);

        /** --------------------------------
        /**  "URL title" input Field
        /** --------------------------------*/
		
		if ($show_url_title == 'n' AND $this->url_title_error === FALSE)
		{	
			$r .= $DSP->input_hidden('url_title', $url_title); 
		}
		else
		{
			$r .= $DSP->qdiv('itemWrapper',
							  $DSP->qdiv('itemTitle', $LANG->line('url_title', 'url_title')).
							  $DSP->input_text('url_title', $url_title, '20', '75', 'input', '100%')
						 );
		}
        
        $r .= $DSP->div_c();
        
		$r .= '</td>';
		$r .= '<td style="width:350px;padding-top: 4px;" valign="top">';
		
        /** --------------------------------
        /**  Submit/Preview buttons        
        /** --------------------------------*/
                
		$r .= $DSP->div('submitBox').$DSP->input_submit($LANG->line('preview'), 'preview').NBS.$DSP->input_submit($LANG->line('quick_save'), 'save').NBS;
		$r .= ($IN->GBL('C') == 'publish') ? $DSP->input_submit($LANG->line('submit'), 'submit') : $DSP->input_submit($LANG->line('update'), 'submit');
		$r .= $DSP->div_c();
        
        /** --------------------------------
        /**  Upload link        
        /** --------------------------------*/
        
		$up_img = '<img src="'.PATH_CP_IMG.'upload_file.gif" border="0"  width="16" height="16" alt="'.$LANG->line('file_upload').'" />';
		
		$r .= $DSP->div('uploadBox');

		// -------------------------------------------
        // 'publish_form_upload_link' hook.
		//  - Rewrite URL for Upload Link
		//
			if ($EXT->active_hook('publish_form_upload_link') === TRUE)
			{
				$r .= $EXT->call_extension('publish_form_upload_link', $up_img);
			}
			else
			{
				$r .= $DSP->anchorpop(BASE.AMP.'C=publish'.AMP.'M=file_upload_form'.AMP.'field_group='.$field_group.AMP.'Z=1', $up_img.'&nbsp;'.$LANG->line('upload_file'), '520', '600');
			}
		//
		// -------------------------------------------

        $r .= NBS.$DSP->div_c();

		$r .= "</td></tr></table>";
  
        /** --------------------------------
        /**  HTML formatting buttons
        /** --------------------------------*/
		
		if ($show_button_cluster == 'y')
		{		
        	$r .= $this->html_formatting_buttons('', $field_group, FALSE, $weblog_allow_img_urls);
		}
		else
		{
			$r .= $this->insert_javascript();
		}
               
        /** --------------------------------
        /**  Custom Fields
        /** --------------------------------*/
        
        $r .= $DSP->qdiv('publishLine');
        
        if ($this->SPELL->enabled === TRUE)
        {
        	$r .= '<div id="spellcheck_popup" class="wordSuggestion" style="position:absolute;visibility:hidden;"></div>'.NL; // Spell Check Word Suggestion Box
		}
		
		$expand		= '<img src="'.PATH_CP_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />';
		$collapse	= '<img src="'.PATH_CP_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />';
                
        foreach ($field_query->result as $row)
        {
            switch ($which)
            {
                case 'preview' : 
                        $field_data = ( ! isset( $_POST['field_id_'.$row['field_id']] )) ?  '' : $_POST['field_id_'.$row['field_id']];
                        $field_fmt  = ( ! isset( $_POST['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $_POST['field_ft_'.$row['field_id']];
                    break;
                case 'save' : 
                        $field_data = ( ! isset( $_POST['field_id_'.$row['field_id']] )) ?  '' : $_POST['field_id_'.$row['field_id']];
                        $field_fmt  = ( ! isset( $_POST['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $_POST['field_ft_'.$row['field_id']];
                    break;
                case 'edit'    :
                        $field_data = ( ! isset( $result->row['field_id_'.$row['field_id']] )) ? '' : $result->row['field_id_'.$row['field_id']];
                        $field_fmt  = ( ! isset( $result->row['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $result->row['field_ft_'.$row['field_id']];
                    break;
                default        :
                
                        $tb_url   = ( ! isset($_GET['tb_url'])) ? '' : $_GET['tb_url'];
                        $tb_field = ( ! isset($_GET['field_id_'.$row['field_id']])) ? '' : $_GET['field_id_'.$row['field_id']];
                        
                        $field_data = ( ! isset( $_GET['field_id_'.$row['field_id']] )) ? '' :  $this->bm_qstr_decode($tb_url."\n\n".$tb_field);
                        $field_fmt  = $row['field_fmt'];
                    break;
            }
                                
            $required		= ($row['field_required'] == 'n') ? '' : $DSP->required().NBS;   
            $text_direction	= ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr'; 
            
			$flink = $DSP->qdiv('itemWrapper', '<label for="field_id_'.
												$row['field_id'].
												'"><a href="javascript:void(0);" onclick="showhide_field(\''.
												$row['field_id'].
												'\');return false;">{ICON}<b>'.
												NBS.NBS.$required.$row['field_label'].
												'</b></a></label>');
			
			// Enclosing DIV for each row
			$r .= $DSP->div('publishRows');
			
			if ($row['field_is_hidden'] == 'y')
			{
				$r .= '<div id="field_pane_off_'.$row['field_id'].'" style="display: block; padding:0; margin:0;">';		
				$r .= str_replace('{ICON}', $expand, $flink);
				$r .= $DSP->div_c();					
				$r .= '<div id="field_pane_on_'.$row['field_id'].'" style="display: none; padding:0; margin:0;">';
				$r .= str_replace('{ICON}', $collapse, $flink);
			
			}
			else
			{
				$r .= '<div id="field_pane_off_'.$row['field_id'].'" style="display: none; padding:0; margin:0;">';		
				$r .= str_replace('{ICON}', $expand, $flink);
				$r .= $DSP->div_c();					
				$r .= '<div id="field_pane_on_'.$row['field_id'].'" style="display: block; padding:0; margin:0;">';
				$r .= str_replace('{ICON}', $collapse, $flink);                
			}
			
			/** --------------------------------
            /**  Instructions for Field
            /** --------------------------------*/
			            
            if (trim($row['field_instructions']) != '')
            {
            	$r .= $DSP->qdiv('paddedWrapper', 
            					 $DSP->qspan('defaultBold', $LANG->line('instructions')).
            					 $row['field_instructions']);
            }
            
            /** --------------------------------
            /**  Textarea field types
            /** --------------------------------*/

            if ($row['field_type'] == 'textarea')
            {               
                $rows = ( ! isset($row['field_ta_rows'])) ? '10' : $row['field_ta_rows'];
                
                $field_js = ($show_button_cluster == 'y') ? "onFocus='setFieldName(this.name)'" : '';
                
                // This table fixes a Safari bug.  Kill the table once Safari has fixed it.
				$r .= "<table border='0' cellpadding='0' cellspacing='0' style='width:99%;margin-bottom:0;'><tr><td>";
                
				// -------------------------------------------
        		// 'publish_form_field_textarea' hook.
				//  - Allows modification of the field textareas
				//
				if ($EXT->active_hook('publish_form_field_textarea') === TRUE)
				{
					$r .= $EXT->call_extension('publish_form_field_textarea', $row['field_id'], $field_data, $rows, $field_js, $convert_ascii, $text_direction);
				}
				else
				{
					$r .= $DSP->input_textarea('field_id_'.$row['field_id'], $field_data, $rows, 'textarea', '100%', $field_js, $convert_ascii, $text_direction);
				}
				//
				// -------------------------------------------
				                
                if ($row['field_show_fmt'] == 'y')
                {
					$r .= $this->text_formatting_buttons($row['field_id'], $field_fmt);
				}
				else
				{				
        			$r .= $DSP->input_hidden('field_ft_'.$row['field_id'], $field_fmt); 
				}
				// Safari Fix
                $r .= "</td></tr></table>";

                /** --------------------------------
                /**  Smileys Pane
                /** --------------------------------*/
                
                if ($row['field_show_fmt'] == 'y')
                {
					$r .= '<div id="smileys_'.$row['field_id'].'" style="display: none; padding:0; margin:0;">';
					$r .= NL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr><td>";
					$r .= NL.'<div class="clusterBox">';
					$r .= NL.'<div class="publishItemWrapper">';
					$r .= $this->fetch_emoticons($row['field_id']);
					$r .= NL.'</div>';
					$r .= NL.'</div>';
					$r .= $DSP->td_c();           
					$r .= $DSP->tr_c();
					$r .= $DSP->table_c();
					$r .= NL.'</div>';
					
					/** --------------------------------
					/**  Glossary Pane
					/** --------------------------------*/
									
					$r .= '<div id="glossary_'.$row['field_id'].'" style="display: none; padding:0; margin:0;">';
					$r .= NL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr><td>";
					$r .= $this->fetch_glossary($row['field_id']);			
					$r .= $DSP->td_c();           
					$r .= $DSP->tr_c();
					$r .= $DSP->table_c();
					$r .= NL.'</div>';
					
					
					/** --------------------------------
					/**  Spell Check Pane
					/** --------------------------------*/
					
					if ($this->SPELL->enabled === TRUE)
					{	
						$spacer = NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS;
						
						$r .= '<div id="spellcheck_field_id_'.$row['field_id'].'" style="display: none; padding:0; margin:0;">';
						$r .= NL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr><td>";
						$r .= NL.'<div class="clusterBox">';
						$r .= NL.'<div class="publishItemWrapper">';
						
						$r .= $DSP->div('highlight').$LANG->line('spell_check');
						$r .= '<span id="spellcheck_hidden_field_id_'.$row['field_id'].'" style="visibility:hidden;">'.$spacer;
						$r .= '<a href="javascript:void(0);" onclick="SP_saveSpellCheck();return false">';
						$r .= $LANG->line('save_spellcheck').'</a>'.$spacer;
						$r .= '<a href="javascript:void(0);" onclick="SP_revertToOriginal();return false">';
						$r .= $LANG->line('revert_spellcheck').'</a></span>';
						$r .= $DSP->div_c();
						
						$r .= BR.BR;
						$r .= '<iframe src="'.BASE.AMP.'C=publish'.AMP.'M=spellcheck_iframe" width="100%" style="display:none; border:1px solid #6600CC;" id="spellcheck_frame_field_id_'.$row['field_id'].'" name="spellcheck_frame_field_id_'.$row['field_id'].'"></iframe>';
						$r .= NL.'</div>';
						$r .= NL.'</div>';
						$r .= $DSP->td_c();           
						$r .= $DSP->tr_c();
						$r .= $DSP->table_c();
						$r .= NL.'</div>';
					}
				}
            }
            /** --------------------------------
            /**  Date field types
            /** --------------------------------*/
            elseif ($row['field_type'] == 'date')
            {  
				if ( ! class_exists('js_calendar'))
				{
					if (include_once(PATH_LIB.'js_calendar'.EXT))
					{
						$CAL = new js_calendar();
						$DSP->extra_header .= $CAL->calendar();
					}				
				}			

				// This table fixes a Safari bug.  Kill the table once Safari has fixed it.
				$r .= "<table border='0' cellpadding='0' cellspacing='0' style='margin-bottom:0;'><tr><td>";	
	
				$date_field = 'field_id_'.$row['field_id'];
				$date_local = 'field_dt_'.$row['field_id'];	
				
				if ($field_data == 0)
					$field_data = '';
				
				$dtwhich = $which;
				if (isset($_POST[$date_field])) 
				{
					$field_data = $_POST[$date_field];
					$dtwhich = ($which != 'save') ? 'preview' : '';
				}

				$custom_date = '';
				$localize = FALSE;
				if ($dtwhich != 'preview' OR $submission_error != '')
				{	
					$localize = TRUE;
				
					if ($field_data != '' AND isset($result))
					{
						if (isset($result->row['field_dt_'.$row['field_id']]) AND $result->row['field_dt_'.$row['field_id']] != '')
						{
							$field_data = $LOC->offset_entry_dst($field_data, $dst_enabled);
							$field_data = $LOC->simpl_offset($field_data, $result->row['field_dt_'.$row['field_id']]);
							$localize = FALSE;
						}
					}
				
					if ($field_data != '')
						$custom_date = $LOC->set_human_time($field_data, $localize);
					
					$cal_date = ($LOC->set_localized_time($field_data) * 1000);
				}
				else
				{				
					$custom_date = $_POST[$date_field];
					$cal_date = ($custom_date != '') ? ($LOC->set_localized_time($LOC->convert_human_date_to_gmt($custom_date)) * 1000) : ($LOC->set_localized_time() * 1000);
				}
				
				/** --------------------------------
				/**  JavaScript Calendar
				/** --------------------------------*/
						
				$cal_img = '<a href="javascript:void(0);" onClick="showhide_item(\'calendar'.$date_field.'\');"><img src="'.PATH_CP_IMG.'calendar.gif" border="0"  width="16" height="16" alt="'.$LANG->line('calendar').'" /></a>';
				$r .= $DSP->input_text($date_field, $custom_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\''.$date_field.'\', this.value);" ', $text_direction).$cal_img;
				
				$r .= '<div id="calendar'.$date_field.'" style="display:none;margin:4px 0 0 0;padding:0;">';
				
				$xmark = ($custom_date == '') ? 'false' : 'true';
				$r .= NL.'<script type="text/javascript">
						
						var '.$date_field .' = new calendar(
												"'.$date_field.'", 
												new Date('.$cal_date.'), 
												'.$xmark.'
												);
						
						document.write('.$date_field.'.write());
						</script>'.NL;
						
				$r .= '</div>';

				$r .= $DSP->div_c();
				$r .= $DSP->div_c();
				
				$localized = ( ! isset($_POST['field_offset_'.$row['field_id']])) ? (($localize == FALSE) ? 'n' : 'y') : $_POST['field_offset_'.$row['field_id']];

				$r .= $DSP->div('itemWrapper').$DSP->div('lightLinks');					
				$r .= $DSP->input_select_header('field_offset_'.$row['field_id']);
				$r .= $DSP->input_select_option('y', $LANG->line('localized_date'), ($localized == 'y') ? 1 : 0);
				$r .= $DSP->input_select_option('n', $LANG->line('fixed_date'), ($localized == 'n') ? 1 : 0);
				$r .= $DSP->input_select_footer().NBS.NBS;
				$r .= '<a href="javascript:void(0);" onClick="set_to_now(\''.$date_field.'\', \''.$LOC->set_human_time($LOC->now).'\', \''.($LOC->set_localized_time() * 1000).'\')" >'.$LANG->line('today').'</a>'.NBS.NBS.'|'.NBS.NBS;
				$r .= '<a href="javascript:void(0);" onClick="clear_field(\''.$date_field.'\');" >'.$LANG->line('clear').'</a>';
				$r .= $DSP->div_c();
				$r .= $DSP->div_c();

				// Safari
				$r .= "</td></tr></table>"; 
            }
            /** --------------------------------
            /**  Relationship field types
            /** --------------------------------*/
            elseif ($row['field_type'] == 'rel')
            { 
				// This table fixes a Safari bug.  Kill the table once Safari has fixed it.
				$r .= "<table border='0' cellpadding='0' cellspacing='0' style='margin-bottom:0;'><tr><td>";	
            
            	if ($row['field_related_to'] == 'blog')
            	{
            		$relto = 'exp_weblog_titles';
            		$relid = 'weblog_id';
            	}
            	else
            	{
            		$relto = 'exp_gallery_entries';
            		$relid = 'gallery_id';
            	}
            	
            	if ($row['field_related_orderby'] == 'date')
            		$row['field_related_orderby'] = 'entry_date';
            	
            	
				$sql = "SELECT entry_id, title FROM ".$relto." WHERE ".$relid." = '".$DB->escape_str($row['field_related_id'])."' ";
				$sql .= "ORDER BY ".$row['field_related_orderby']." ".$row['field_related_sort'];
				
				if ($row['field_related_max'] > 0)
				{
					$sql .= " LIMIT ".$row['field_related_max'];
				}
				
				$relquery = $DB->query($sql);
				
				if ($relquery->num_rows == 0)
				{
					$r .= $DSP->qdiv('highlight_alt', $LANG->line('no_related_entries'));
				}
				else
				{
					$relentry_id = '';
					if ( ! isset($_POST['field_id_'.$row['field_id']]) OR $which == 'save')
					{
						$relentry = $DB->query("SELECT rel_child_id FROM exp_relationships WHERE rel_id = '".$DB->escape_str($field_data)."'");
					
						if ($relentry->num_rows == 1)
						{
							$relentry_id = $relentry->row['rel_child_id'];
						}
					}
					else
					{
						$relentry_id = $_POST['field_id_'.$row['field_id']];
					}
					
					$r .= $DSP->input_select_header('field_id_'.$row['field_id']);
					$r .= $DSP->input_select_option('', '--', '', "dir='{$text_direction}'");
					
					foreach ($relquery->result as $relrow)
					{
						$r .= $DSP->input_select_option($relrow['entry_id'], 
														$relrow['title'], 
														($relentry_id == $relrow['entry_id']) ? 1 : 0, 
														"dir='{$text_direction}'");
					}
					
					$r .= $DSP->input_select_footer();
				}
            
				// Safari
				$r .= "</td></tr></table>"; 
            }
			/** --------------------------------
			/**  Text input field types
			/** --------------------------------*/
			elseif ($row['field_type'] == 'text')
			{   
				// This table fixes a Safari bug.  Kill the table once Safari has fixed it.
				$r .= "<table border='0' cellpadding='0' cellspacing='0' style='width:99%;margin-bottom:0;'><tr><td>";

				// -------------------------------------------
				// 'publish_form_field_text_input' hook.
				//  - Allows modification of the field text inputs
				//
				
					$field_js = ($show_button_cluster == 'y') ? "onFocus='setFieldName(this.name)'" : '';
					if ($EXT->active_hook('publish_form_field_text_input') === TRUE)
					{
						$r .= $EXT->call_extension('publish_form_field_text_input', $row['field_id'], $field_data, $row['field_maxl'], $field_js, $convert_ascii, $text_direction);
					}
					else
					{
						$r .= $DSP->input_text('field_id_'.$row['field_id'], $field_data, '50', $row['field_maxl'], 'input', '100%', $field_js, $convert_ascii, $text_direction);
					}
				//
				// ------------------------------------------- 	  
					  
				if ($row['field_show_fmt'] == 'y')
				{
					$r .= $this->text_formatting_buttons($row['field_id'], $field_fmt);
				}
				else
				{				
					$r .= $DSP->input_hidden('field_ft_'.$row['field_id'], $field_fmt); 
				}
				// Safari
				$r .= "</td></tr></table>"; 
			}            

			/** --------------------------------
			/**  Drop-down lists
			/** --------------------------------*/
			
			elseif ($row['field_type'] == 'select')
			{
				// -------------------------------------------
				// 'publish_form_field_select_header' hook.
				//  - Allows modification of the field select header
				//
					if ($EXT->active_hook('publish_form_field_select_header') === TRUE)
					{
						$r .= $EXT->call_extension('publish_form_field_select_header', $row['field_id'], $field_data, $text_direction);
					}
					else
					{
						$r .= $DSP->input_select_header('field_id_'.$row['field_id'], '', '');
					}
				//
				// -------------------------------------------
				
				if ($row['field_pre_populate'] == 'n')
				{
					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{                    
						$v = trim($v);
					
						$selected = ($v == $field_data) ? 1 : '';
						
						// -------------------------------------------
						// 'publish_form_field_select_option' hook.
						//  - Allows modification of the field selection options
						//  - Version 1.4.2 : Added $field_data variable
						//
							if ($EXT->active_hook('publish_form_field_select_option') === TRUE)
							{
								$r .= $EXT->call_extension('publish_form_field_select_option', $v, $v, $selected, $field_data);
							}
							else
							{
								$v = $REGX->form_prep($v);
								$r .= $DSP->input_select_option($v, $v, $selected, "dir='{$text_direction}'");
							}
						//
						// -------------------------------------------
					}
				}
				else
				{
					// We need to pre-populate this menu from an another weblog custom field
											
					$pop_query = $DB->query("SELECT field_id_".$row['field_pre_field_id']." FROM exp_weblog_data WHERE weblog_id = ".$row['field_pre_blog_id']."");
					
					$r .= $DSP->input_select_option('', '--', '', $text_direction);
					if ($pop_query->num_rows > 0)
					{
						foreach ($pop_query->result as $prow)
						{							
							$selected = ($prow['field_id_'.$row['field_pre_field_id']] == $field_data) ? 1 : '';
							$pretitle = substr($prow['field_id_'.$row['field_pre_field_id']], 0, 110);
							$pretitle = preg_replace("/\r\n|\r|\n|\t/", ' ', $pretitle);
							$pretitle = $REGX->form_prep($pretitle);
							
							$r .= $DSP->input_select_option($REGX->form_prep($prow['field_id_'.$row['field_pre_field_id']]), $pretitle, $selected, $text_direction);
						}
					}
				}
				
				$r .= $DSP->input_select_footer();
					  
				if ($row['field_show_fmt'] == 'y')
				{
					$r .= $this->text_formatting_buttons($row['field_id'], $field_fmt);
				}
				else
				{				
					$r .= $DSP->input_hidden('field_ft_'.$row['field_id'], $field_fmt); 
				}					
			}
			
			/** ---------------------------------------------
			/**  Custom Field Types - Created By Extensions
			/** ---------------------------------------------*/
			
			else
			{
				/* -------------------------------------------
				/* 'publish_form_field_unique' hook.
				/*  - Allows adding of unique custom fields via extensions
				/*  - Added 1.4.2
				*/
					if ($EXT->active_hook('publish_form_field_unique') === TRUE)
					{
						$r .= $EXT->call_extension('publish_form_field_unique', $row, $field_data, $text_direction);
					}
				/*
				/* -------------------------------------------*/
			}
            
			// Close Div -  SHOW/HIDE FIELD PANES
			$r .= $DSP->div_c();            

			// Close outer DIV
			$r .= $DSP->div_c(); 
        }
        
        
        // -------------------------------------------
        // 'publish_form_end' hook.
		//  - Allows adding to end of submission form
		//
			if ($EXT->active_hook('publish_form_end') === TRUE)
			{
				$r .= $EXT->call_extension('publish_form_end', $weblog_id);
			}	
		//
		// -------------------------------------------

         
        /** ----------------------------------------------
        /**  END PUBLISH FORM BLOCK
        /** ---------------------------------------------*/
         
        $r .= "</td></tr></table>";
        
        $r .= $DSP->form_close();
         
        if ($this->direct_return == TRUE)
        {
        	return $r;
        }

        $DSP->body = $r;        
    }
    /* END */


    /** -------------------------------------
    /**  Convert quotes in trackback titles
    /** -------------------------------------*/
 
    // This function converts any quotes found in RDF titles
    // to entities.  This is used in the trackback auto-discovery feature
    // to prevent a bug that happens if weblog entry titles contain quotes
    
    function convert_tb_title_entities($matches)
    {
        $matches['2'] = trim($matches['2']);
        $matches['2'] = preg_replace("/^\"/", '', $matches['2']); 
        $matches['2'] = preg_replace("/\"$/", '', $matches['2']);
        $matches['2'] = str_replace("\"", "&quot;", $matches['2']);
        
        return $matches['1']."\"".$matches['2']."\"\n".$matches['3'];
    }
    /* END */


    /** -------------------------------------
    /**  Bookmarklet query string decode
    /** -------------------------------------*/

    function bm_qstr_decode($str)
    {
        global $REGX;
    
        $str = str_replace("%20",    " ",       $str); 
        $str = str_replace("%uFFA5", "&#8226;", $str); 
        $str = str_replace("%uFFCA", " ",       $str); 
        $str = str_replace("%uFFC1", "-",       $str);
        $str = str_replace("%uFFC9", "...",     $str); 
        $str = str_replace("%uFFD0", "-",       $str); 
        $str = str_replace("%uFFD1", "-",       $str);
        $str = str_replace("%uFFD2", "\"",      $str); 
        $str = str_replace("%uFFD3", "\"",      $str); 
        $str = str_replace("%uFFD4", "\'",      $str); 
        $str = str_replace("%uFFD5", "\'",      $str);
        
        $str =  preg_replace("/\%u([0-9A-F]{4,4})/e","'&#'.base_convert('\\1',16,10).';'", $str);
        
        $str = $REGX->xss_clean(stripslashes(urldecode($str))); 
                
        return $str;
    }
    /* END */


    /** ----------------------------------------
    /**  Fetch the parent category ID
    /** ----------------------------------------*/
    
	function fetch_category_parents($cat_array = '')
	{
		global $DB, $PREFS;
		
		if (count($cat_array) == 0)
		{
			return;
		}

		$sql = "SELECT parent_id FROM exp_categories WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND (";
		
		foreach($cat_array as $val)
		{
			$sql .= " cat_id = '$val' OR ";
		}
		
		$sql = substr($sql, 0, -3).")";
		
		$query = $DB->query($sql);
				
		if ($query->num_rows == 0)
		{
			return;
		}
		
		$temp = array();

		foreach ($query->result as $row)
		{
			if ($row['parent_id'] != 0)
			{
				$this->cat_parents[] = $row['parent_id'];
				
				$temp[] = $row['parent_id'];
			}
		}
	
		$this->fetch_category_parents($temp);
	}   
   
   
   
   
    /** ---------------------------------------------------------------
    /**  Weblog entry submission handler
    /** ---------------------------------------------------------------*/
    // This function receives a new or edited weblog entry and
    // stores it in the database.  It also sends trackbacks and pings
    //---------------------------------------------------------------

    function submit_new_entry($cp_call = TRUE)
    {
        global $IN, $PREFS, $OUT, $LANG, $FNS, $LOC, $DSP, $DB, $SESS, $STAT, $REGX, $EXT;

		$url_title 		= '';
		$tb_format		= 'xhtml';
        $tb_errors		= FALSE;
        $ping_errors	= FALSE;
		$revision_post 	= $_POST;
		$return_url		= ( ! $IN->GBL('return_url', 'POST')) ? '' : $IN->GBL('return_url');
        unset($_POST['return_url']);
		
		if ($PREFS->ini('site_pages') !== FALSE)
		{
			$LANG->fetch_language_file('pages');
		}
		
        if ( ! $weblog_id = $IN->GBL('weblog_id', 'POST') OR ! is_numeric($weblog_id))
        {
            return false;
        } 
        
		$assigned_weblogs = $FNS->fetch_assigned_weblogs();

        /** ----------------------------------------------
        /**  Security check
        /** ---------------------------------------------*/

        if ( ! in_array($weblog_id, $assigned_weblogs))
        {
            return false;
        }

		// -------------------------------------------
        // 'submit_new_entry_start' hook.
        //  - Add More Stuff to do when you first submit an entry
        //  - Added 1.4.2
        //
        	$edata = $EXT->call_extension('submit_new_entry_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
                
        /** -----------------------------
        /**  Does entry ID exist?  And is valid for this weblog?
        /** -----------------------------*/
        
        if (($entry_id = $IN->GBL('entry_id', 'POST')) !== FALSE && is_numeric($entry_id))
		{
			// we grab the author_id now as we use it later for author validation
			$query = $DB->query("SELECT entry_id, author_id FROM exp_weblog_titles WHERE entry_id = '".$DB->escape_str($entry_id)."' AND weblog_id = '".$DB->escape_str($weblog_id)."'");
			
			if ($query->num_rows != 1)
			{
				return FALSE;
			}
			else
			{
				$entry_id = $query->row['entry_id'];
				$orig_author_id = $query->row['author_id'];
			}
		}
		else
		{
			$entry_id = '';
		}
		
        /** -----------------------------
        /**  Weblog Switch?
        /** -----------------------------*/
        
        $old_weblog = '';
        
        if (($new_weblog = $IN->GBL('new_weblog', 'POST')) !== FALSE && $new_weblog != $weblog_id)
        {
        	$query = $DB->query("SELECT status_group, cat_group, field_group, weblog_id 
        						 FROM exp_weblogs 
        						 WHERE weblog_id IN ('".$DB->escape_str($weblog_id)."', '".$DB->escape_str($new_weblog)."')");
								 
			if ($query->num_rows == 2)
			{
				if ($query->result['0']['status_group'] == $query->result['1']['status_group'] &&
					$query->result['0']['cat_group'] == $query->result['1']['cat_group'] &&
					$query->result['0']['field_group'] == $query->result['1']['field_group'])
				{
					if ($SESS->userdata['group_id'] == 1)
					{
						$old_weblog = $weblog_id;
						$weblog_id = $new_weblog;
					}
					else
					{
						$assigned_weblogs = $FNS->fetch_assigned_weblogs();
						
						if (in_array($new_weblog, $assigned_weblogs))
						{
							$old_weblog = $weblog_id;
							$weblog_id = $new_weblog;
						}
					}
				}
			}
        }
        
        
        /** -----------------------------
        /**  Fetch Weblog Prefs
        /** -----------------------------*/
        
		$query = $DB->query("SELECT blog_title, blog_url, comment_url, deft_status, enable_versioning,  enable_qucksave_versioning, max_revisions, weblog_notify, weblog_notify_emails, ping_return_url, rss_url, tb_return_url, trackback_field, comment_system_enabled, trackback_system_enabled FROM exp_weblogs WHERE weblog_id = '".$weblog_id."'");
		
		$blog_title					= $REGX->ascii_to_entities($query->row['blog_title']);
		$blog_url					= $query->row['blog_url'];
		$ping_url					= ($query->row['ping_return_url'] == '') ? $query->row['blog_url'] : $query->row['ping_return_url'];
		$tb_url						= ($query->row['tb_return_url'] == '') ? $query->row['blog_url'] : $query->row['tb_return_url'];
		$rss_url					= $query->row['rss_url'];
		$deft_status				= $query->row['deft_status'];
		$comment_url				= $query->row['comment_url'];
		$trackback_field			= $query->row['trackback_field'];		        
		$comment_system_enabled		= $query->row['comment_system_enabled'];		        
		$trackback_system_enabled	= $query->row['trackback_system_enabled'];	
		$notify_address = ($query->row['weblog_notify'] == 'y' AND $query->row['weblog_notify_emails'] != '') ? $query->row['weblog_notify_emails'] : '';
		$enable_versioning			= $query->row['enable_versioning'];
		$enable_qucksave_versioning	= $query->row['enable_qucksave_versioning'];
		$max_revisions				= $query->row['max_revisions'];		        
                
        /** -----------------------------
        /**  Error trapping
        /** -----------------------------*/
                        
        $error = array();
        
        // Fetch language file
        
        $LANG->fetch_language_file('publish_ad');
        
        /** ---------------------------------
        /**  No entry title? Assign error.
        /** ---------------------------------*/
        
        if ( ! $title = strip_tags(trim(stripslashes($IN->GBL('title', 'POST')))))
        {
            $error[] = $LANG->line('missing_title');
        }
   
        /** ---------------------------------------------
        /**  No date? Assign error.
        /** ---------------------------------------------*/
            
        if ( ! $IN->GBL('entry_date', 'POST'))
        {
            $error[] = $LANG->line('missing_date');
        }
        
        /** ---------------------------------------------
        /**  Convert the date to a Unix timestamp
        /** ---------------------------------------------*/
        
        $entry_date = $LOC->convert_human_date_to_gmt($IN->GBL('entry_date', 'POST'));
                     
        if ( ! is_numeric($entry_date)) 
        {
			// Localize::convert_human_date_to_gmt() returns verbose errors
			if ($entry_date !== FALSE)
			{
				$error[] = $entry_date.NBS.NBS.'('.$LANG->line('entry_date').')';
			}
			else
			{
				$error[] = $LANG->line('invalid_date_formatting');
			}
        }

        /** ---------------------------------------------
        /**  Convert expiration date to a Unix timestamp
        /** ---------------------------------------------*/
        
        if ( ! $IN->GBL('expiration_date', 'POST'))
        {
            $expiration_date = 0;
        }
        else
        {
            $expiration_date = $LOC->convert_human_date_to_gmt($IN->GBL('expiration_date', 'POST'));

            if ( ! is_numeric($expiration_date)) 
            {
				// Localize::convert_human_date_to_gmt() returns verbose errors
				if ($expiration_date !== FALSE)
				{
					$error[] = $expiration_date.NBS.NBS.'('.$LANG->line('expiration_date').')';
				}
				else
				{
					$error[] = $LANG->line('invalid_date_formatting');
				}
            }
        }
        
        /** ---------------------------------------------
        /**  Convert comment expiration date timestamp
        /** ---------------------------------------------*/
        
        if ( ! $IN->GBL('comment_expiration_date', 'POST'))
        {
            $comment_expiration_date = 0;
        }
        else
        {
            $comment_expiration_date = $LOC->convert_human_date_to_gmt($IN->GBL('comment_expiration_date', 'POST'));

            if ( ! is_numeric($comment_expiration_date)) 
            { 
				// Localize::convert_human_date_to_gmt() returns verbose errors
				if ($comment_expiration_date !== FALSE)
				{
					$error[] = $comment_expiration_date.NBS.NBS.'('.$LANG->line('comment_expiration_date').')';
				}
				else
				{
					$error[] = $LANG->line('invalid_date_formatting');
				}
            }
        }

        /** --------------------------------------
        /**  Are all requred fields filled out?
        /** --------------------------------------*/
        
         $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE field_required = 'y'");
         
         if ($query->num_rows > 0)
         {
            foreach ($query->result as $row)
            {
                if (isset($_POST['field_id_'.$row['field_id']]) AND $_POST['field_id_'.$row['field_id']] == '') 
                {
                    $error[] = $LANG->line('custom_field_empty').NBS.$row['field_label'];
                }           
            }
         }
   
        /** --------------------------------------
        /**  Are there any custom date fields?
        /** --------------------------------------*/
        
         $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE field_type = 'date'");
         
         if ($query->num_rows > 0)
         {
            foreach ($query->result as $row)
            {
                if (isset($_POST['field_id_'.$row['field_id']]) AND $_POST['field_id_'.$row['field_id']] != '') 
                {
					$_POST['field_ft_'.$row['field_id']] = 'none';
                
            		$custom_date = $LOC->convert_human_date_to_gmt($_POST['field_id_'.$row['field_id']]);
                
					if ( ! is_numeric($custom_date)) 
					{ 
						// Localize::convert_human_date_to_gmt() returns verbose errors
						if ($custom_date !== FALSE)
						{
							$error[] = $custom_date.NBS.NBS.'('.$row['field_label'].')';
						}
						else
						{
							$error[] = $LANG->line('invalid_date_formatting');
						}
					}
					else
					{
						$custom_date = $LOC->offset_entry_dst($custom_date, $IN->GBL('dst_enabled', 'POST'));
						
						$_POST['field_id_'.$row['field_id']] = $custom_date;
						
						if ( ! isset($_POST['field_offset_'.$row['field_id']]))
						{
							$_POST['field_dt_'.$row['field_id']] = '';
						}
						else
						{
							if ($_POST['field_offset_'.$row['field_id']] == 'y')
							{
								$_POST['field_dt_'.$row['field_id']] = '';
							}
							else
							{
								$_POST['field_dt_'.$row['field_id']] = $SESS->userdata('timezone');
							}
						}				
					}
                }           
            }
         }
      
        /** ---------------------------------
        /**  Fetch xml-rpc ping server IDs
        /** ---------------------------------*/
                       
        $ping_servers = array();
        
        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'ping') AND ! is_array($val))
            {
                $ping_servers[] = $val;
                unset($_POST[$key]);
            }        
        }

        /** -------------------------------------
        /**  Pre-process Trackback data
        /** -------------------------------------*/
           
        // If the weblog submission was via the bookmarklet we need to fetch the trackback URLs
        
        $tb_auto_urls = '';
        
        if ($IN->GBL('BK', 'GP'))
        {        
            foreach ($_POST as $key => $val)
            {
                if (preg_match('#^TB_AUTO_#', $key))
                {
                    $tb_auto_urls .= $val.NL;
                }
            }
        }
        
        // Join the manually submitted trackbacks with the auto-disovered ones
        
        $trackback_urls = $IN->GBL('trackback_urls');
        
        if ($tb_auto_urls != '')
        {
            $trackback_urls .= NL.$tb_auto_urls;
        }
        
        /** --------------------------------------
        /**  Is weblog data present?
        /** --------------------------------------*/
        
        // In order to send pings or trackbacks, the weblog needs a title and URL
        
        if ($trackback_urls != '' && ($blog_title == '' || $tb_url == ''))
        {
			$error[] = $LANG->line('missing_weblog_data_for_pings');
        }
        
        if (count($ping_servers) > 0 && ($blog_title == '' || $ping_url == ''))
        {
			$error[] = $LANG->line('missing_weblog_data_for_pings');
        }
        
        
        /** --------------------------------------
        /**  Is the title unique?
        /** --------------------------------------*/
        
        if ($title != '')
        {			
            /** ---------------------------------
            /**  Do we have a URL title?
            /** ---------------------------------*/
            
            // If not, create one from the title
            
            $url_title = $IN->GBL('url_title');
            
            if ( ! $url_title)
            {
				$url_title = $REGX->create_url_title($title, TRUE);
            }
            
			// Kill all the extraneous characters.  
			// We want the URL title to pure alpha text
            
            if ($entry_id != '')
			{
				$url_query = $DB->query("SELECT url_title FROM exp_weblog_titles WHERE entry_id = '$entry_id'");
				
				if ($url_query->row['url_title'] != $url_title)
				{
					$url_title = $REGX->create_url_title($url_title);
				}
			}
			else
			{
            	$url_title = $REGX->create_url_title($url_title);
			}
            
			// Is the url_title a pure number?  If so we show an error.
			
			if (is_numeric($url_title))
			{
				$this->url_title_error = TRUE;
				$error[] = $LANG->line('url_title_is_numeric');
			}
            
			/** -------------------------------------
			/**  Is the URL Title empty?  Can't have that
			/** -------------------------------------*/
			
			if (trim($url_title) == '')
			{
				$this->url_title_error = TRUE;
				$error[] = $LANG->line('unable_to_create_url_title');
				
				$msg = '';

	            foreach($error as $val)
	            {
	                $msg .= $DSP->qdiv('itemWrapper', $val);  
	            }
				
				if ($cp_call == TRUE)
				{
					return $this->new_entry_form('preview', $msg);
				}
				else
				{
					return $OUT->show_user_error('general', $error);
				}
			}
			
            /** ---------------------------------
            /**  Is URL title unique?
            /** ---------------------------------*/
             
			// Field is limited to 75 characters, so trim url_title before querying
			$url_title = substr($url_title, 0, 75);
			$e_sql = '';
            
			$sql = "SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title = '".$DB->escape_str($url_title)."' AND weblog_id = '$weblog_id'";
			
			if ($entry_id != '')
			{
				$e_sql = " AND entry_id != '$entry_id'";
			}
			
			$query = $DB->query($sql.$e_sql);
				 
			if ($query->row['count'] > 0)
			{				 
				// We may need some room to add our numbers- trim url_title to 70 characters
				$url_title = substr($url_title, 0, 70);
			
				// Check again
				$sql = "SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title = '".$DB->escape_str($url_title).
				"' AND weblog_id = '$weblog_id'".$e_sql;
			
				$query = $DB->query($sql);
				 
				if ($query->row['count'] > 0)
				{
					$url_create_error = FALSE;
					
					$sql = "SELECT url_title, MID(url_title, ".(strlen($url_title) + 1).") + 1 AS next_suffix FROM ".
					 	"exp_weblog_titles WHERE weblog_id = '".$weblog_id."' ".
					 	"AND url_title REGEXP('".preg_quote($DB->escape_str($url_title))."[0-9]*$') ".
					 	"AND weblog_id = '".$weblog_id."'".$e_sql." ORDER BY next_suffix DESC LIMIT 1";

					$query = $DB->query($sql);
					
					// Did something go tragically wrong?  
					if ($query->num_rows == 0)
					{
						$url_create_error = TRUE;
						$error[] = $LANG->line('unable_to_create_url_title');
					}
					
					// Is the appended number going to kick us over the 75 character limit?
					if ($query->row['next_suffix'] > 99999)
					{
						$url_create_error = TRUE;
						$error[] = $LANG->line('url_title_not_unique');
					}
			
					if ($url_create_error == FALSE)
					{
						$url_title = $url_title.$query->row['next_suffix'];
			
						// little double check for safety
						$sql = "SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title = '".$DB->escape_str($url_title).
							"' AND weblog_id = '$weblog_id'".$e_sql;
			
						$query = $DB->query($sql);
				
						if ($query->row['count'] > 0)
						{
							$error[] = $LANG->line('unable_to_create_url_title');
						}
					}
				}
			}
        }
                
		// Did they name the URL title "index"?  That's a bad thing which we disallow
		
		if ($url_title == 'index')
		{
			$this->url_title_error = TRUE;
			$error[] = $LANG->line('url_title_is_index');
		}
		
		/** -------------------------------------
        /**  Validate Page URI
        /** -------------------------------------*/
        
		if ($PREFS->ini('site_pages') !== FALSE && 
			$IN->GBL('pages_uri', 'POST') !== FALSE && $IN->GBL('pages_uri', 'POST') != '' && 
			$IN->GBL('pages_uri', 'POST') != '/example/pages/uri/'
			)
		{
			if ( ! is_numeric($IN->GBL('pages_template_id', 'POST')))
			{
				$error[] = $LANG->line('invalid_template');
			}
		
			$page_uri = preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '', str_replace($PREFS->ini('site_url'), '', $IN->GBL('pages_uri')));
			
			if ($page_uri !== $IN->GBL('pages_uri', 'POST'))
			{
				$error[] = $LANG->line('invalid_page_uri');
			}
			
			/** -------------------------------------
			/**  Check if Duplicate Page URI
			/**  - Do NOT delete this as the $static_pages variable is used further down
			/** -------------------------------------*/
			
        	$static_pages = $PREFS->ini('site_pages');
			
			$uris = (isset($static_pages[$PREFS->ini('site_id')]['uris'])) ? $static_pages[$PREFS->ini('site_id')]['uris'] : array();
			
			if ($entry_id != '')
			{
				unset($uris[$entry_id]);
			}
			
			if (in_array($IN->GBL('pages_uri', 'POST'), $uris))
			{
				$error[] = $LANG->line('duplicate_page_uri');
			}
			
			unset($uris);
        }

        /** ---------------------------------------
        /**  Validate Author ID
        /** ---------------------------------------*/

        $author_id = ( ! $IN->GBL('author_id', 'POST')) ? $SESS->userdata('member_id'): $IN->GBL('author_id', 'POST');
		
		if ($author_id != $SESS->userdata['member_id'] && ! $DSP->allowed_group('can_edit_other_entries'))
		{
			$error[] = $LANG->line('not_authorized');
		}
		
		if (isset($orig_author_id) && $author_id != $orig_author_id && (! $DSP->allowed_group('can_edit_other_entries') OR ! $DSP->allowed_group('can_assign_post_authors')))
		{
			$error[] = $LANG->line('not_authorized');
		}
		
		if ($author_id != $SESS->userdata['member_id'] && $SESS->userdata['group_id'] != 1)
		{
			// we only need to worry about this if the author has changed
			if (! isset($orig_author_id) OR $author_id != $orig_author_id)
			{
				if (! $DSP->allowed_group('can_assign_post_authors'))
				{
					$error[] = $LANG->line('not_authorized');
				}
				else
				{				
					$allowed_authors = array();

					$ss = "SELECT exp_members.member_id
						   FROM exp_members
						   LEFT JOIN exp_member_groups on exp_member_groups.group_id = exp_members.group_id
						   WHERE (exp_members.in_authorlist = 'y' OR exp_member_groups.include_in_authorlist = 'y')
						   AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";

					$query = $DB->query($ss);

					if ($query->num_rows > 0)
					{					   
						foreach ($query->result as $row)
						{
							// Is this a "user blog"?  If so, we'll only allow
							// authors if they are assigned to this particular blog

							if ($SESS->userdata['weblog_id'] != 0)
							{
								if ($row['weblog_id'] == $weblog_id)
								{                    
									$allowed_authors[] = $row['member_id'];
								}                
							}
							else
							{
								$allowed_authors[] = $row['member_id'];
							}
						}
					}

					if (! in_array($author_id, $allowed_authors))
					{
						$error[] = $LANG->line('invalid_author');
					}
				}
			}			
		}
		
		/** ---------------------------------------
		/**  Validate status
		/** ---------------------------------------*/
		
		$status = ($IN->GBL('status', 'POST') == FALSE) ? $deft_status : $IN->GBL('status', 'POST');
		
		if ($SESS->userdata['group_id'] != 1)
		{
			$disallowed_statuses = array();
			$valid_statuses = array();
			
			$sq = "SELECT s.status_id, s.status
				   FROM exp_statuses AS s
				   LEFT JOIN exp_status_groups AS sg ON sg.group_id = s.group_id
				   LEFT JOIN exp_weblogs AS w ON w.status_group = sg.group_id
				   WHERE w.weblog_id = '".$DB->escape_str($weblog_id)."'";
			
			$query = $DB->query($sq);

			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$valid_statuses[$row['status_id']] = strtolower($row['status']); // lower case to match MySQL's case-insensitivity
				}
			}
			
			$dsq = "SELECT exp_status_no_access.status_id, exp_statuses.status
					FROM exp_status_no_access, exp_statuses
					WHERE exp_statuses.status_id = exp_status_no_access.status_id
					AND exp_status_no_access.member_group = '".$SESS->userdata['group_id']."'";
			
			$query = $DB->query($dsq);
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$disallowed_statuses[$row['status_id']] = strtolower($row['status']); // lower case to match MySQL's case-insensitivity
				}

				$valid_statuses = array_diff_assoc($valid_statuses, $disallowed_statuses);
			}
			
			if (! in_array(strtolower($status), $valid_statuses))
			{
				// if there are no valid statuses, set to closed
				$status = 'closed';
			}
		}

        /** ---------------------------------
        /**  Do we have an error to display?
        /** ---------------------------------*/

         if (count($error) > 0)
         {
            $msg = '';
            
            foreach($error as $val)
            {
                $msg .= $DSP->qdiv('itemWrapper', $val);  
            }
           	
           	
           	if ($cp_call == TRUE)
				return $this->new_entry_form('preview', $msg);
			else
				return $OUT->show_user_error('general', $error);        
         }   
                    
        /** ---------------------------------
        /**  Fetch catagories
        /** ---------------------------------*/

        // We do this first so we can destroy the category index from 
        // the $_POST array since we use a separate table to store categories in
                        
        if (isset($_POST['category']) AND is_array($_POST['category']))
        {
			foreach ($_POST['category'] as $cat_id)
			{
				$this->cat_parents[] = $cat_id;
			}
			
			if ($this->assign_cat_parent == TRUE)
			{
				$this->fetch_category_parents($_POST['category']);            
			}
        }
		unset($_POST['category']);


        /** ---------------------------------
        /**  Fetch previously sent trackbacks
        /** ---------------------------------*/
        
        // If we are editing an existing entry, fetch the previously sent trackbacks
        // and add the new trackback URLs to them
        
        $sent_trackbacks = '';
        
        if ($trackback_urls != '' AND $entry_id != '')
        {
            $sent_trackbacks = trim($trackback_urls)."\n";
            
            $query = $DB->query("SELECT sent_trackbacks FROM exp_weblog_titles WHERE entry_id = '$entry_id'");
        
            if ($query->num_rows > 0)
            {
                $sent_trackbacks = $query->row['sent_trackbacks'];
            }
        }           
            
        /** ---------------------------------
        /**  Set "mode" cookie
        /** ---------------------------------*/

        // We do it now so we can destry it from the POST array
        
        if (isset($_POST['mode']))
        {    
            $FNS->set_cookie('mode' , $_POST['mode'], 60*60*24*182);       
            unset($_POST['mode']);
        }
        

		if ($cp_call == TRUE)
		{
			$allow_comments		= ($IN->GBL('allow_comments', 'POST') == 'y') ? 'y' : 'n';
			$allow_trackbacks	= ($IN->GBL('allow_trackbacks', 'POST') == 'y') ? 'y' : 'n';
		}
		else
		{
			$allow_comments		= ($IN->GBL('allow_comments', 'POST') !== 'y' || $comment_system_enabled == 'n') ? 'n' : 'y';
			$allow_trackbacks	= ($IN->GBL('allow_trackbacks', 'POST') !== 'y' || $trackback_system_enabled == 'n') ? 'n' : 'y';
		}
  
        /** --------------------------------------
        /**  Do we have a relationship?
        /** --------------------------------------*/

        // If the entry being submitted is the "parent" entry we need to compile and cache the "child" entry.
        
		$query = $DB->query("SELECT field_id, field_related_to, field_related_id FROM exp_weblog_fields WHERE field_type = 'rel'");
		
		$rel_updates = array();
		
		if ($query->num_rows > 0)
		{
            foreach ($query->result as $row)
            {
                if (isset($_POST['field_id_'.$row['field_id']])) 
                {
					$_POST['field_ft_'.$row['field_id']] = 'none';
					$rel_exists = FALSE;                

					// If editing an existing entry....
					// Does an existing relationship exist? If so, we may not  need to recompile the data
					if ($entry_id != '')
					{
						// First we fetch the previously stored related entry ID.
						$rel_query = $DB->query("SELECT field_id_".$row['field_id']." FROM exp_weblog_data WHERE entry_id = '".$entry_id."'");
						
						// If the previous ID matches the current ID being submitted it means that
						// the existing relationship has not changed so there's no need to recompile.
						// If it has changed we'll clear the old relationship.
						
						if (is_numeric($rel_query->row['field_id_'.$row['field_id']]))
						{
							if ($rel_query->row['field_id_'.$row['field_id']] == $_POST['field_id_'.$row['field_id']])
							{
								$rel_exists = TRUE;
							}
							else
							{
								$DB->query("DELETE FROM exp_relationships WHERE rel_id = '".$rel_query->row['field_id_'.$row['field_id']]."'");
							}
						}
					}
                                
					if (is_numeric($_POST['field_id_'.$row['field_id']]) AND $rel_exists == FALSE)
					{					
						$reldata = array(
											'type'			=> $row['field_related_to'],
											'parent_id'		=> $entry_id,
											'child_id'		=> $_POST['field_id_'.$row['field_id']],
											'related_id'	=> $weblog_id
										);
						
						$_POST['field_id_'.$row['field_id']] = $FNS->compile_relationship($reldata, TRUE);
						$rel_updates[] = $_POST['field_id_'.$row['field_id']];
					}
                }           
            }
		}

        /** ---------------------------------
        /**  Build our query data
        /** ---------------------------------*/
        
        if ($enable_versioning == 'n')
        {
        	$version_enabled = 'y';
        }
        else
        {
        	$version_enabled = (isset($_POST['versioning_enabled'])) ? 'y' : 'n';
        }
        
        
        $data = array(  
                        'entry_id'          		=> '',
                        'weblog_id'         		=> $weblog_id,
                        'author_id'         		=> $author_id,
                        'site_id'					=> $PREFS->ini('site_id'),
                        'ip_address'        		=> $IN->IP,
                        'title'             		=> ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($title) : $title,
                        'url_title'         		=> $url_title,
                        'entry_date'        		=> $entry_date,
                        'edit_date'					=> date("YmdHis"),
                        'versioning_enabled'		=> $version_enabled,
                        'year'              		=> date('Y', $entry_date),
                        'month'             		=> date('m', $entry_date),
                        'day'               		=> date('d', $entry_date),
                        'expiration_date'   		=> $expiration_date,
                        'comment_expiration_date'	=> $comment_expiration_date,
                        'sticky'            		=> ($IN->GBL('sticky', 'POST') == 'y') ? 'y' : 'n',
                        'status'            		=> $status,
                        'allow_comments'    		=> $allow_comments,
                        'allow_trackbacks'  		=> $allow_trackbacks,
                        'forum_topic_id'			=> ($IN->GBL('forum_topic_id') != '' AND is_numeric($IN->GBL('forum_topic_id'))) ? trim($IN->GBL('forum_topic_id')) : 0
                     );
                     
        
        // If we have the "honor_entry_dst" pref turned on we need to reverse the effects.
        
        if ($PREFS->ini('honor_entry_dst') == 'y')
        {
        	$data['dst_enabled'] = ($IN->GBL('dst_enabled', 'POST') == 'y') ? 'y' : 'n';
        }
        
        /** ---------------------------------
        /**  Insert the entry
        /** ---------------------------------*/
        
        if ($entry_id == '')
        {  
            $DB->query($DB->insert_string('exp_weblog_titles', $data)); 
            $entry_id = $DB->insert_id;  
            
            /** ------------------------------------
            /**  Update Relationships
            /** ------------------------------------*/
            
            if (sizeof($rel_updates) > 0)
            {
            	$DB->query("UPDATE exp_relationships SET rel_parent_id = '".$entry_id."' WHERE rel_id IN (".implode(',', $rel_updates).")");
            }
            
            /** ------------------------------------
            /**  Insert the custom field data
            /** ------------------------------------*/
            
            $cust_fields = array('entry_id' => $entry_id, 'weblog_id' => $weblog_id);
            
            foreach ($_POST as $key => $val)
            {
				if (strstr($key, 'field_offset_'))
				{
					unset($_POST[$key]);
					continue;
				}
            
                if (strstr($key, 'field'))
                {
					if ($key == 'field_ft_'.$trackback_field)
					{
						$tb_format = $val;
					}
										
					if (strstr($key, 'field_id_') AND ! is_numeric($val))
					{
						$cust_fields[$key] = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($val) : $val;
					}
					else
					{
						$cust_fields[$key] = $val;
					}
                }        
            }
            
            if (count($cust_fields) > 0)
            {
            	$cust_fields['site_id'] = $PREFS->ini('site_id');
            	
                // Submit the custom fields
                $DB->query($DB->insert_string('exp_weblog_data', $cust_fields));
            }
                        
            /** ------------------------------------
            /**  Update member stats
            /** ------------------------------------*/
            
            if ($data['author_id'] == $SESS->userdata('member_id'))
            {
                $total_entries = $SESS->userdata['total_entries'] +1;
            }
            else
            {
                $query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '".$data['author_id']."'");
                $total_entries = $query->row['total_entries'] + 1;
            }
                                    
            $DB->query("UPDATE exp_members set total_entries = '$total_entries', last_entry_date = '".$LOC->now."' WHERE member_id = '".$data['author_id']."'");
                         
            /** -------------------------------------
            /**  Set page title and success message
            /** -------------------------------------*/
                            
            $type = 'new';
            $page_title = 'entry_has_been_added';
            $message = $LANG->line($page_title);
            
            /** -------------------------------------
            /**  Is there a forum post?
            /** -------------------------------------*/
            
			if ($PREFS->ini('forum_is_installed') == "y" AND $IN->GBL('forum_title') != '' AND $IN->GBL('forum_body') != '')
			{				
				$query = $DB->query("SELECT board_id FROM exp_forums WHERE forum_id = '".$DB->escape_str($IN->GBL('forum_id'))."'");
				
				if ($query->num_rows > 0)
				{
					$title = $this->_convert_forum_tags($IN->GBL('forum_title'));
					$body = $this->_convert_forum_tags(str_replace('{permalink}', 
																	$FNS->remove_double_slashes($comment_url.'/'.$url_title.'/'), 
																	$IN->GBL('forum_body')));
																	
					$DB->query($DB->insert_string('exp_forum_topics',  
											array(
													'topic_id'				=> '',
													'forum_id'				=> $IN->GBL('forum_id'),
													'board_id'				=> $query->row['board_id'],
													'topic_date'			=> $LOC->now,
													'title'					=> $REGX->xss_clean($title),
													'body'					=> $REGX->xss_clean($body),
	                        						'author_id'         	=> $author_id,												
													'ip_address'			=> $IN->IP,
													'last_post_date'		=> $LOC->now,
													'last_post_author_id'	=> $author_id,
													'sticky'				=> 'n',
													'status'				=> 'o',
													'announcement'			=> 'n',
													'poll'					=> 'n',
													'parse_smileys'			=> 'y',
													'thread_total'			=> 1						
												 )
											)
										);
					$topic_id = $DB->insert_id;

					$rand = $author_id.$FNS->random('alpha', 8);

					$DB->query("UPDATE exp_weblog_titles SET forum_topic_id = '{$topic_id}' WHERE entry_id = '{$entry_id}'");	
					$DB->query("INSERT INTO exp_forum_subscriptions (topic_id, member_id, subscription_date, hash) 
															 		VALUES 
															 		('{$topic_id}', '{$author_id}', '{$LOC->now}', '{$rand}')");

					// Update the forum stats

					if ( ! class_exists('Forum'))
					{
						require PATH_MOD.'forum/mod.forum'.EXT;
						require PATH_MOD.'forum/mod.forum_core'.EXT;
					}
					Forum_Core::_update_post_stats($IN->GBL('forum_id'));

					// Update member post total
					$DB->query("UPDATE exp_members SET last_forum_post_date = '{$LOC->now}' WHERE member_id = '".$author_id."'");																	
				}			
            }
				
			/** ----------------------------
			/**  Send admin notification
			/** ----------------------------*/
					
			if ($notify_address != '')
			{         
				$swap = array(
								'name'				=> $SESS->userdata('screen_name'),
								'email'				=> $SESS->userdata('email'),
								'weblog_name'		=> $blog_title,
								'entry_title'		=> $title,
								'entry_url'			=> $FNS->remove_double_slashes($blog_url.'/'.$url_title.'/'),
								'comment_url'		=> $FNS->remove_double_slashes($comment_url.'/'.$url_title.'/')
							 );
				
				$template = $FNS->fetch_email_template('admin_notify_entry');
	
				$email_tit = $FNS->var_swap($template['title'], $swap);
				$email_msg = $FNS->var_swap($template['data'], $swap);
								   
				// We don't want to send a notification if the person
				// leaving the entry is in the notification list
			
				if (stristr($notify_address, $SESS->userdata['email']))
				{
					$notify_address = str_replace($SESS->userdata('email'), "", $notify_address);				
				}
				
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
           
        }        
        else
        {
            /** ---------------------------------
            /**  Update an existing entry
            /** ---------------------------------*/
            
            if ($PREFS->ini('honor_entry_dst') == 'y')
			{
				$data['entry_date'] = $LOC->offset_entry_dst($data['entry_date'], $data['dst_enabled']);
	
				if ($data['expiration_date'] != '' AND $data['expiration_date'] != 0)
					$data['expiration_date'] = $LOC->offset_entry_dst($data['expiration_date'], $data['dst_enabled']);
	
				if ($data['comment_expiration_date'] != '' AND $data['comment_expiration_date'] != 0)
					$data['comment_expiration_date'] = $LOC->offset_entry_dst($data['comment_expiration_date'], $data['dst_enabled']);
			}
			
            // First we need to see if the author of the entry has changed.
       
            $query = $DB->query("SELECT author_id FROM exp_weblog_titles WHERE entry_id = '$entry_id'");
            
            $old_author = $query->row['author_id'];
            
            if ($old_author != $data['author_id'])
            {
                // Decremenet the counter on the old author
            
                $query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '$old_author'");

                $total_entries = $query->row['total_entries'] - 1;
            
                $DB->query("UPDATE exp_members set total_entries = '$total_entries' WHERE member_id = '$old_author'");
                      
                // Increment the counter on the new author
            
                $query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '".$data['author_id']."'");

                $total_entries = $query->row['total_entries'] + 1;
            
                $DB->query("UPDATE exp_members set total_entries = '$total_entries' WHERE member_id = '".$data['author_id']."'");
            }
        
            /** ------------------------------------
            /**  Update the entry
            /** ------------------------------------*/
                    
            unset($data['entry_id']);            
			$topic_id = $data['forum_topic_id'];

            $DB->query($DB->update_string('exp_weblog_titles', $data, "entry_id = '$entry_id'"));   
        
            /** ------------------------------------
            /**  Update the custom fields
            /** ------------------------------------*/
           
            $cust_fields = array('weblog_id' =>  $weblog_id);            
            foreach ($_POST as $key => $val)
            {
				if (strstr($key, 'field_offset_'))
				{
					// removed the unset in 1.6.5 as the localization was being lost on quicksave
					// unset($_POST[$key]);
					continue;
				}
            
                if (strstr($key, 'field'))
                {
					if ($key == 'field_ft_'.$trackback_field)
					{
						$tb_format = $val;
					}
										
					if (strstr($key, 'field_id_') AND ! is_numeric($val))
					{
						$cust_fields[$key] = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($val) : $val;
					}
					else
					{
						$cust_fields[$key] = $val;
					}
                }        
            }
                         
            if (count($cust_fields) > 0)
            {
                // Update the custom fields
                $DB->query($DB->update_string('exp_weblog_data', $cust_fields, "entry_id = '$entry_id'"));   
            }
            
            /** ------------------------------------
            /**  Delete categories
            /** ------------------------------------*/
                        
            // We will resubmit all categories next
                        
            $DB->query("DELETE FROM exp_category_posts WHERE entry_id = '$entry_id'");
            
            /** ------------------------------------
            /**  Set page title and success message
            /** ------------------------------------*/
            
            $type = 'update';
            $page_title = 'entry_has_been_updated';
            $message = $LANG->line($page_title);
        }
        
        /** ---------------------------------
        /**  Insert categories
        /** ---------------------------------*/
        
        if ($this->cat_parents > 0)
        { 
        	$this->cat_parents = array_unique($this->cat_parents);

        	sort($this->cat_parents);
        	
            foreach($this->cat_parents as $val)
            {
            	if ($val != '')
            	{
                	$DB->query("INSERT INTO exp_category_posts (entry_id, cat_id) VALUES ('$entry_id', '$val')");
                }
            }
        }
        
        
        /** --------------------------------------
        /**  Is this entry a child of another parent?
        /** --------------------------------------*/
        		
		// If the entry being submitted is a "child" of another parent
		// we need to re-compile and cache the data.  Confused?  Me too...
		
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_relationships WHERE rel_type = 'blog' AND rel_child_id = '".$DB->escape_str($entry_id)."'");
        
        if ($query->row['count'] > 0)
        {
			$reldata = array(
								'type'		=> 'blog',
								'child_id'	=> $entry_id
							);
				
			$FNS->compile_relationship($reldata, FALSE);
        }
        
        /** --------------------------------------
        /**  Is this entry a parent of a child?
        /** --------------------------------------*/
		
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_relationships 
							 WHERE rel_parent_id = '".$DB->escape_str($entry_id)."'
							 AND reverse_rel_data != ''");
        
        if ($query->row['count'] > 0)
        {
			$reldata = array(
								'type'		=> 'blog',
								'parent_id'	=> $entry_id
							);
				
			$FNS->compile_relationship($reldata, FALSE, TRUE);
        }
        
		/** -------------------------------------
		/**  Is there a forum post to update
		/** -------------------------------------*/
		
		if ($PREFS->ini('forum_is_installed') == "y" AND $IN->GBL('forum_title') != '' AND $IN->GBL('forum_body') != '' AND $topic_id != 0)
		{
			$title = $this->_convert_forum_tags($IN->GBL('forum_title'));
			$body = $this->_convert_forum_tags(str_replace('{permalink}', 
															$FNS->remove_double_slashes($comment_url.'/'.$url_title.'/'), 
															$IN->GBL('forum_body')));
			
			$DB->query("UPDATE exp_forum_topics SET title = '{$title}', body = '{$body}' WHERE topic_id = '{$topic_id}' ");	
			
			// Update the forum stats
			if ( ! class_exists('Forum'))
			{
				require PATH_MOD.'forum/mod.forum'.EXT;
				require PATH_MOD.'forum/mod.forum_core'.EXT;
			}
			Forum_Core::_update_post_stats($IN->GBL('forum_id'));
		}
		
		/** -------------------------------------
		/**  Is there a Page being updated or created?
		/** -------------------------------------*/
		
		if ($PREFS->ini('site_pages') !== FALSE && 
			$IN->GBL('pages_uri', 'POST') !== FALSE && $IN->GBL('pages_uri', 'POST') != '' && $IN->GBL('pages_uri', 'POST') != '/example/pages/uri/' &&
			is_numeric($IN->GBL('pages_template_id', 'POST')))
		{
			/** ----------------------------------------
			/**  Update the Very, Most Current Pages Data for Site
			/** ----------------------------------------*/

			$site_id = $PREFS->ini('site_id');
			
			$static_pages[$site_id]['uris'][$entry_id]      = '/'.trim(preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '', str_replace($PREFS->ini('site_url'), '', $IN->GBL('pages_uri'))), '/').'/';
			$static_pages[$site_id]['templates'][$entry_id] = preg_replace("#[^0-9]+$#i", '', $IN->GBL('pages_template_id', 'POST'));
			
			if ($static_pages[$site_id]['uris'][$entry_id] == '//')
			{
				$static_pages[$site_id]['uris'][$entry_id] = '/';
			}

			$DB->query($DB->update_string('exp_sites', 
										  array('site_pages' => addslashes(serialize($static_pages))),
										  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
		}
		
        /** ----------------------------------------
        /**  Save revisions if needed
        /** ----------------------------------------*/
        
        if ( ! isset($_POST['versioning_enabled']))
        {
        	$enable_versioning = 'n';
        }
        
        if (isset($_POST['save']) AND $enable_qucksave_versioning == 'n')
        {
        	$enable_versioning = 'n';
        }
                
        if ($enable_versioning == 'y')
        {            
			$DB->query("INSERT INTO exp_entry_versioning (version_id, entry_id, weblog_id, author_id, version_date, version_data) VALUES ('', '".$entry_id."', '".$weblog_id."', '".$SESS->userdata('member_id')."', '".$LOC->now."', '".addslashes(serialize($revision_post))."')");
			
			// Clear old revisions if needed
			$max = (is_numeric($max_revisions) AND $max_revisions > 0) ? $max_revisions : 10;
	
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_entry_versioning WHERE entry_id = '".$entry_id."'");
			
			if ($query->row['count'] > $max)
			{
				$query = $DB->query("SELECT version_id FROM exp_entry_versioning WHERE entry_id = '".$entry_id."' ORDER BY version_id desc limit ".$max);
			
				$ids = '';
				foreach ($query->result as $row)
				{
					$ids .= $row['version_id'].',';
				}
				$ids = substr($ids, 0, -1);
			
				$DB->query("DELETE FROM exp_entry_versioning WHERE version_id NOT IN (".$ids.") AND entry_id = '".$entry_id."'");
			}
        }
        
        //---------------------------------
        // Quick Save Returns Here
        //  - does not process pings
        //  - does not update stats
        //  - does not empty caches
        //---------------------------------
                
        if (isset($_POST['save']))
		{
        	return $this->new_entry_form('save', '', $entry_id);
        }
        
        /** ----------------------------------------
        /**  Update global stats
        /** ----------------------------------------*/
        
		if ($old_weblog != '')
		{
			// Change weblog_id in exp_comments
			if (isset($this->installed_modules['comment']))
			{
				$DB->query("UPDATE exp_comments SET weblog_id = '$weblog_id' WHERE entry_id = '$entry_id'");
			}
			
			$STAT->update_weblog_stats($old_weblog);
		}

		$STAT->update_weblog_stats($weblog_id);
		

        /** ---------------------------------
        /**  Send trackbacks
        /** ---------------------------------*/
                
        $tb_body = ( ! isset($_POST['field_id_'.$trackback_field])) ? '' : $_POST['field_id_'.$trackback_field];
        
        if ($trackback_urls != '' AND $tb_body != '' AND $data['status'] != 'closed' AND $data['entry_date'] < ($LOC->now + 90))
        {
            $entry_link = $REGX->prep_query_string($tb_url);
            						
			$entry_link = $FNS->remove_double_slashes($entry_link.'/'.$url_title.'/');            
                    
            $tb_data = array(   'entry_id'			=> $entry_id,
                                'entry_link'		=> $FNS->remove_double_slashes($entry_link),
                                'entry_title'		=> $title,
                                'entry_content'		=> $tb_body,
                                'tb_format'			=> $tb_format,
                                'weblog_name'		=> $blog_title,
                                'trackback_url'		=> $trackback_urls
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
                
                $DB->query("UPDATE exp_weblog_titles SET sent_trackbacks = '$sent_trackbacks' WHERE entry_id = '$entry_id'");
            }
            
            if (count($tb_res['1']) > 0)
            {
                $tb_errors = TRUE;
            }                    
        }
        
        /** ---------------------------------
        /**  Send xml-rpc pings
        /** ---------------------------------*/
        
        $ping_message = '';

        if (count($ping_servers) > 0)
        {
			// We only ping entries that are posted now, not in the future
		
			if (($entry_date-90) < $LOC->now)
			{
				$ping_result = $this->send_pings($ping_servers, $blog_title, $ping_url, $rss_url);
				
				if (is_array($ping_result) AND count($ping_result) > 0)
				{
					$ping_errors = TRUE;
									
					$ping_message .= $DSP->qdiv('highlight', $DSP->qdiv('defaultBold', $LANG->line('xmlrpc_ping_errors')));
					
					foreach ($ping_result as $val)
					{
						$ping_message .= $DSP->qdiv('highlight', $DSP->qspan('highlight_bold', $val['0']).' - '.$val['1']);
					}
				}
			}
        	
            /** ---------------------------------
            /**  Save ping button state
            /** ---------------------------------*/
            
            $DB->query("DELETE FROM exp_entry_ping_status WHERE entry_id = '$entry_id'");
        
			foreach ($ping_servers as $val)
			{
				$DB->query("INSERT INTO exp_entry_ping_status (entry_id, ping_id) VALUES ('$entry_id', '$val')");
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
		
		// -------------------------------------------
        // 'submit_new_entry_end' hook.
        //  - Add More Stuff to Do For Entry
        //  - 1.5.2 => Added $ping_message variable
        //
        	$edata = $EXT->call_extension('submit_new_entry_end', $entry_id, $data, $ping_message);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        /** ---------------------------------------
        /**  Show ping erors if there are any
        /** ---------------------------------------*/
        
        if ($tb_errors == TRUE || $ping_errors == TRUE)
        {  
           	if ($cp_call == TRUE)
           	{
				$r  = $DSP->qdiv('success', $LANG->line($page_title).BR.BR);
			 
				if (isset($tb_res['1']) AND count($tb_res['1']) > 0)
				{
					$r .= $DSP->qdiv('highlight', $DSP->qdiv('defaultBold', $LANG->line('trackback_url_errors')));
					
					foreach ($tb_res['1'] as $val)
					{
						$r .= $DSP->qdiv('highlight', $DSP->qspan('highlight_bold', $val['0']).' - '.$val['1']);
					}
				} 
							
				$r .= $ping_message;
					
				$r .= $DSP->qdiv('', BR.$DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$IN->GBL('weblog_id', 'POST').AMP.'entry_id='.$entry_id, $LANG->line('click_to_view_your_entry')));
           	
				return $DSP->set_return_data($LANG->line('publish'),$r);	
			}
        }
                
        /** ---------------------------------
        /**  Redirect to ths "success" page
        /** ---------------------------------*/
        
        if ($cp_call == TRUE)
        {
        	$loc = BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id.AMP.'U='.$type;
        }
        else
        {
			$FNS->template_type = 'webpage';
			$loc = ($return_url == '') ? $FNS->fetch_site_index() : $FNS->create_url($return_url, 1, 1);
        }
        
        // -------------------------------------------
        // 'submit_new_entry_redirect' hook.
        //  - Modify Redirect Location
        //  - 1.5.2 => Added $cp_call variable
        //
        	if ($EXT->active_hook('submit_new_entry_redirect') === TRUE)
        	{
        		$loc = $EXT->call_extension('submit_new_entry_redirect', $entry_id, $data, $cp_call);
        		if ($EXT->end_script === TRUE) return;
        	}
        //
        // -------------------------------------------
        
        // -------------------------------------------
        // 'submit_new_entry_absolute_end' hook.
        //  - Add More Stuff to Do For Entry
        //	- Still allows Trackback/Ping error messages
        //
        	$edata = $EXT->call_extension('submit_new_entry_absolute_end', $entry_id, $data);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
           
        $FNS->redirect($loc);
        exit;        
    }
    /* END */
    
        
	/** ---------------------------------
	/**  Send Pings
	/** ---------------------------------*/
    
    function send_pings($ping_servers, $blog_title, $ping_url, $rss_url)
    {
		global $DB, $PREFS;
		
		$sql = "SELECT server_name, server_url, port FROM exp_ping_servers WHERE id IN (";
		
		foreach ($ping_servers as $id)
		{
			$sql .= "'$id',";    	
		}
    	
    	$sql = substr($sql, 0, -1).') ';
    	
		$query = $DB->query($sql);
    	
		if ($query->num_rows == 0)
		{
			return FALSE;    	
		}
    	
		if ( ! class_exists('XML_RPC'))
		{
			require PATH_CORE.'core.xmlrpc'.EXT;
		}
		
		$XRPC = new XML_RPC;
		
		$result = array();
    	
		foreach ($query->result as $row)
		{
			if (($response = $XRPC->weblogs_com_ping($row['server_url'], $row['port'], $blog_title, $ping_url, $rss_url)) !== TRUE)
			{
				$result[] = array($row['server_name'], $response);
			}
		}		
		
		return $result;
    }
    /* END */
    
    
    
    /** ----------------------------------------
    /**  Convert forum special characters
    /** ----------------------------------------*/

	function _convert_forum_tags($str)
	{	
		$str = str_replace('{include:', '&#123;include:', $str);
		$str = str_replace('{path:', '&#123;path:', $str);
		$str = str_replace('{lang:', '&#123;lang:', $str);
		
		return $str;
	}
	/* END */
    
    /** --------------------------------------------
    /**  Category tree
    /** --------------------------------------------*/
    // This function (and the next) create a higherarchy tree
    // of categories.  There are two versions of the tree. The
    // "text" version is a list of links allowing the categories
    // to be edited.  The "form" version is displayed in a 
    // multi-select form on the new entry page.
    //--------------------------------------------

    function category_tree($group_id = '', $action = '', $default = '', $selected = '')
    {  
        global $DSP, $IN, $REGX, $DB;
  
        // Fetch category group ID number
      
        if ($group_id == '')
        {        
            if ( ! $group_id = $IN->GBL('group_id'))
                return false;
        }
        
        // If we are using the category list on the "new entry" page
        // and the person is returning to the edit page after previewing,
        // we need to gather the selected categories so we can highlight
        // them in the form.
        
        if ($action == 'preview' OR $action == 'save')
        {
            $catarray = array();
        
            foreach ($_POST as $key => $val)
            {                
                if (strstr($key, 'category'))
                {
                    $catarray[$val] = $val;
                }            
            }
        }

        if ($action == 'edit')
        {
            $catarray = array();
            
            if (is_array($selected))
            {
                foreach ($selected as $key => $val)
                {
                    $catarray[$val] = $val;
                }
            }
        }
            
        // Fetch category groups
        
        if ( ! is_numeric(str_replace('|', "", $group_id)))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT cat_name, cat_id, parent_id, group_id
                             FROM exp_categories 
                             WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') 
                             ORDER BY group_id, parent_id, cat_order");
              
        if ($query->num_rows == 0)
        {
            return false;
        }     
        
        // Assign the query result to a multi-dimensional array
                    
        foreach($query->result as $row)
        {        
            $cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name'], $row['group_id']);
        }
	
		$size = count($cat_array) + 1;
	
		$this->categories[] = $DSP->input_select_header('category[]', 1, $size);
        
        // Build our output...
        
        $sel = '';

        foreach($cat_array as $key => $val) 
        {
            if (0 == $val['0']) 
            {
            	if (isset($last_group) && $last_group != $val['2'])
            	{
            		$this->categories[] = $DSP->input_select_option('', '-------');
            	}
            
                if ($action == 'new')
                {
                    $sel = ($default == $key) ? '1' : '';   
                }
                else
                {
                    $sel = (isset($catarray[$key])) ? '1' : '';   
                }
                                
				$this->categories[] = $DSP->input_select_option($key, $val['1'], $sel);
                $this->category_subtree($key, $cat_array, $depth=1, $action, $default, $selected);
                
                $last_group = $val['2'];
            }
        }
        
        $this->categories[] = $DSP->input_select_footer();
    }
    /* END */
    
    
    
    
    /** --------------------------------------------
    /**  Category sub-tree
    /** --------------------------------------------*/
    // This function works with the preceeding one to show a
    // hierarchical display of categories
    //--------------------------------------------
        
    function category_subtree($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
    {
        global $DSP, $IN, $DB, $REGX, $LANG;

        $spcr = "&nbsp;";
        
        
        // Just as in the function above, we'll figure out which items are selected.
        
        if ($action == 'preview' OR $action == 'save')
        {
            $catarray = array();
        
            foreach ($_POST as $key => $val)
            {
                if (strstr($key, 'category'))
                {
                    $catarray[$val] = $val;
                }            
            }
        }
        
        if ($action == 'edit')
        {
            $catarray = array();
            
            if (is_array($selected))
            {
                foreach ($selected as $key => $val)
                {
                    $catarray[$val] = $val;
                }
            }
        }
                
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
            
        foreach ($cat_array as $key => $val) 
        {
            if ($cat_id == $val['0']) 
            {
                $pre = ($depth > 2) ? "&nbsp;" : '';
                
                if ($action == 'new')
                {
                    $sel = ($default == $key) ? '1' : '';   
                }
                else
                {
                    $sel = (isset($catarray[$key])) ? '1' : '';   
                }
                
				$this->categories[] = $DSP->input_select_option($key, $pre.$indent.$spcr.$val['1'], $sel);                                
                $this->category_subtree($key, $cat_array, $depth, $action, $default, $selected);
            }
        }
    }
    /* END */
            
    

    /** ---------------------------------------------------------------
    /**  Text formatting buttons
    /** ---------------------------------------------------------------*/
    // This function displays radio buttons used to select
    // between xhtml, auto <br /> and "none" on the new entry page 
    //---------------------------------------------------------------

    function text_formatting_buttons($id, $default = 'xhtml')
    {
        global $DB, $DSP, $LANG;

		$LANG->fetch_language_file('publish_ad');
    
		if ($default == '')
			$default = 'xhtml';
			
        $query = $DB->query("SELECT field_fmt FROM exp_field_formatting WHERE field_id = '$id' AND field_fmt != 'none' ORDER BY field_fmt");

		$spacer = NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS;
		
		if ($this->SPELL->enabled === TRUE)
		{
			$spell_check = ' <a href="javascript:void(0);" onclick="showhide_spellcheck(\''.$id.'\');return false;"><b>'.
						   $LANG->line('check_spelling').'</b></a>'.$spacer;
		}
		else
		{
			$spell_check = '';
		}
        
        $glossary = ' <a href="javascript:void(0);" onclick="showhide_glossary(\''.$id.'\');return false;"><b>'.$LANG->line('html_glossary').'</b></a>'.$spacer;
        $smileys = ' <a href="javascript:void(0);" onclick="showhide_smileys(\''.$id.'\');return false;"><b>'.$LANG->line('emoticons').'</b></a>'.$spacer;
                
        $r =  $DSP->div('xhtmlWrapper').$DSP->qspan('lightLinks', $spell_check.$glossary.$smileys).$DSP->qspan('xhtmlWrapperLight', $LANG->line('newline_format'));
        
		$r .= $DSP->input_select_header('field_ft_'.$id);
                
        if ($query->num_rows > 0)
        {
			foreach ($query->result as $row)
			{
        		$name = ucwords(str_replace('_', ' ', $row['field_fmt']));
        		
				if ($name == 'Br')
				{
					$name = $LANG->line('auto_br');
				}
				elseif ($name == 'Xhtml')
				{
					$name = $LANG->line('xhtml');
				}
	
        		$sel = ($default == $row['field_fmt']) ? 1 : 0;
        			
				$r .= $DSP->input_select_option($row['field_fmt'], $name, $sel);			
			}
        }
        
		$sel = ($default == 'none') ? 1 : 0;

		$r .= $DSP->input_select_option('none', $LANG->line('none'), $sel);
		$r .= $DSP->input_select_footer().NBS;
		$r .= $DSP->div_c();
                
		return $r;
    }
    /* END */
                
        
    /** ---------------------------------------------------------------
    /**  Fetch ping servers
    /** ---------------------------------------------------------------*/
    // This function displays the ping server checkboxes
    //---------------------------------------------------------------
        
    function fetch_ping_servers($member_id = '', $entry_id = '', $which = 'new', $show = TRUE)
    {
        global $LANG, $DB, $SESS, $DSP, $PREFS;
        
        $sent_pings = array();
        
        if ($entry_id != '')
        {
        	$query = $DB->query("SELECT ping_id FROM exp_entry_ping_status WHERE entry_id = '$entry_id'");
        	
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$sent_pings[$row['ping_id']] = TRUE;
				}
			}
        }
        
        if ($member_id == '')
        {        
            $member_id = $SESS->userdata('member_id');
        }

        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '".$SESS->userdata('member_id')."'");
        
        $member_id = ($query->row['count'] == 0) ? 0 : $SESS->userdata('member_id');
              
        $query = $DB->query("SELECT id, server_name, is_default FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' ORDER BY server_order");

        if ($query->num_rows == 0)
        {
            return false;
        }
                
        $r = '';
        
        
        ob_start();
    
        ?>
		<script type="text/javascript">
		<!--
		function toggle(thebutton)
		{
			var checkbox_list = document.getElementById('pingDiv').getElementsByTagName('input');

			for (i=0; i<checkbox_list.length; i++) //for (var i in checkbox_list) feels more elegant... but IE... alas
			{
				checkbox_list[i].checked = (thebutton.checked) ? true : false;
			}
		}
		//-->
		</script>
        <?php
    
        $r .= ob_get_contents();
                
        ob_end_clean(); 

		$r .= '<div id="pingDiv" class="publishPad">';

		foreach($query->result as $row)
		{
			if (isset($_POST['preview']))
			{
				$selected = '';
				foreach ($_POST as $key => $val)
				{        
					if (strstr($key, 'ping') AND $val == $row['id'])
					{
						$selected = 1;
						break;
					}        
				}
			}
			else
			{
				if ($entry_id != '')
				{
					$selected = (isset($sent_pings[$row['id']])) ? 1 : '';
				}
				else
				{
					$selected = ($row['is_default'] == 'y') ? 1 : '';
				}
			}
			
			if ($which == 'edit')
			{
				$selected = '';
			}
			
			if ($show == TRUE)
			{
				$r .= $DSP->input_checkbox('ping[]', $row['id'], $selected).' '.$row['server_name'].'<br />';
			}
			else
			{
				if ($which != 'edit' AND $selected == 1)
				{
					$r .= $DSP->input_hidden('ping[]', $row['id']); 
				}
			}
		}
		
		if ($show == TRUE)
		{
			$r .= $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$DSP->qspan('highlight_alt', $LANG->line('select_all'));
		}

		$r .= '</div>';
		return $r;
    }        
    /* END */
    
    
       
        
    /** ---------------------------------------------------------------
    /**  HTML formatting buttons
    /** ---------------------------------------------------------------*/
    // This function and the next display the HTML formatting buttons
    //---------------------------------------------------------------
    
    function default_buttons($close = TRUE, $allow_img_urls = 'y')
    {
        global $DSP, $LANG, $PREFS;

		$buttons = array(
						  'link'      => array("javascript:promptTag(\"link\");", ''),
						  'email'     => array("javascript:promptTag(\"email\");", ''),
						  'image'     => array("javascript:promptTag(\"image\");", ''),
						  'close_all' => array("javascript:closeall();", "")
						);
						
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- remove_close_all_button => Remove the Close All button from the Publish/Edit page (y/n)
		/*	  Useful because most browsers no longer need it and Admins might want it gone
        /* -------------------------------------------*/
						
		if ($close !== TRUE OR $PREFS->ini('remove_close_all_button') === 'y') 
		{
			unset($buttons['close_all']);
		}
		
		if ($allow_img_urls != 'y')
		{
			unset($buttons['image']);
		}
		   
		$r = '';
		$i = 0;
			
		foreach ($buttons as $k => $v)
		{                    
			if ($i == 0 AND $close == false)
			{
				$r .= $DSP->td('htmlButtonOuterL');
			}
			else
			{
				$r .= $DSP->td('htmlButtonOuter');
			}
			
			$i++;
		
			$r .= 
				  $DSP->div('htmlButtonInner').              
				  $DSP->div('htmlButtonA', '', $k).
				  $DSP->anchor($v['0'], $LANG->line($k), $v['1']).
				  $DSP->div_c().
				  $DSP->div_c().
				  $DSP->td_c();
		}
    
        return $r;
    }
    
    /** ---------------------------------------------------------------
    /**  HTML formatting buttons
    /** ---------------------------------------------------------------*/
    // This function and the above display the HTML formatting buttons
    //---------------------------------------------------------------

    function html_formatting_buttons($member_id = '', $field_group, $extra_js = TRUE, $weblog_allow_img_urls = 'y')
    {
        global $DSP, $IN, $SESS, $DB, $LANG, $PREFS;
        
        if ($member_id == '')
        {        
            $member_id = $SESS->userdata('member_id');
        }
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id'");
        
        $member_id = ($query->row['count'] == 0) ? 0 : $SESS->userdata('member_id');
        
        $query_one = $DB->query("SELECT * FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' AND tag_row = '1' ORDER BY tag_order");
        $query_two = $DB->query("SELECT * FROM exp_html_buttons WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' AND  tag_row = '2' ORDER BY tag_order");
                 
        if ($query_one->num_rows == 0  AND $query_two->num_rows == 0)
        {
            return false;
        }                 
                          
        $data  = array();

        if ($query_one->num_rows > 0)
        {
            $data[] = $query_one->result;
        }
        
        if ($query_two->num_rows > 0)
        {
            $data[] = $query_two->result;
        }
            
		$r = NL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%;margin-bottom:3px;'><tr><td>";
            
        $r .= $DSP->div('buttonInsert').
              $DSP->div('itemWrapper');
              
        if (count($data) > 0)
        {
            if ( ! $mode = $IN->GBL('mode', 'POST'))
            {
                if ( ! $mode = $IN->GBL('mode', 'COOKIE'))
                {
                    $mode = '';
                }
            }
        
            if ($mode == 'guided')
            {
                $guided = "checked='checked'";
                $normal = "";
            }
            else
            {
                $normal = "checked='checked'";
                $guided = "";
            }
        
        
            $r .= $DSP->div('smallLinks').'<b>'.$LANG->line('button_mode').'</b>'.$DSP->nbs(3).
                  $LANG->line('guided').NBS.
                  "<input type='radio' name='mode' value='guided' onclick='setmode(this.value)' $guided/>".
                  $DSP->nbs(2).
                  $LANG->line('normal').NBS.
                  "<input type='radio' name='mode' value='normal' onclick='setmode(this.value)' $normal/>".
                  $DSP->nbs(6);
        }
            $r .= 
				$DSP->div_c().
				$DSP->div_c();
              
        $jsvars = array();
              
        if (count($data) == 0)
        {
            $r  .= $DSP->table('buttonMargin', '0', '', '').
                   $DSP->tr().
                   $this->default_buttons(FALSE, $weblog_allow_img_urls).
                   $DSP->tr_c().
                   $DSP->table_c();
        }
        else
        {
            $rows = (count($data) == 1) ? 1 : 2;
            
            $n = 0;
            $i = 0;

            foreach ($data as $groups)
            {                 
                $r  .= $DSP->table('buttonMargin', '0', '', '').
                       $DSP->tr();
                
                $edge = false;
                
                foreach ($groups as $row)
                {
                    $accesskey = ($row['accesskey'] != '') ? "accesskey=\"".trim($row['accesskey'])."\" " : "";

                    $jsfunc = $accesskey."onclick='taginsert(this, \"".htmlspecialchars(addslashes($row['tag_open']))."\", \"".htmlspecialchars(addslashes($row['tag_close']))."\")'";
                    
                    $jsvars[] = 'button_'.$i;
                    
                    if ($edge == false)
                    {
                        $r .= $DSP->td('htmlButtonOuterL');
                    }
                    else
                    {
                        $r .= $DSP->td('htmlButtonOuter');
                        
                        $edge = true;
                    }
                          
                          
                    $r .= $DSP->div('htmlButtonInner').              
                          "<div class='htmlButtonA' id='button_".$i."'>".
                          $DSP->anchor('javascript:nullo()', htmlspecialchars(trim($row['tag_name'])), " name='button_{$i}' $jsfunc").                          
                          $DSP->div_c().
                          $DSP->div_c().
                          $DSP->td_c();       
                          
                          $i++;   
                          
                          $edge = true;
                }    
                    
                if ($rows == 1 || ($rows == 2 AND $n == 0))
                {
                    $r .= $this->default_buttons(TRUE, $weblog_allow_img_urls);                
                }
                          
                $r .=              
                      $DSP->tr_c().
                      $DSP->table_c();
                                        
                $n ++;       
            }
        }
        
        $r .= $DSP->div_c();

		$r .= $DSP->td_c().             
			  $DSP->tr_c().
			  $DSP->table_c();
								

        ob_start();
        
        ?>     
        
        <script type="text/javascript"> 
        <!--
   		
   		<?php
   		
   		if ($extra_js !== FALSE)
   		{
   		
   		?>		
		
		/** ------------------------------------
        /**  Array Helper Functions
        /** -------------------------------------*/

        function getarraysize(thearray)
        {
            for (i = 0; i < thearray.length; i++)
            {
                if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
                {
                    return i;
                }
            }
            
            return thearray.length;
        }        
        
        // Array push
        function arraypush(thearray, value)
        {
            thearray[getarraysize(thearray)] = value;
        }
        
        // Array pop
        function arraypop(thearray)
        {
            thearraysize = getarraysize(thearray);
            retval = thearray[thearraysize - 1];
            delete thearray[thearraysize - 1];
            return retval;
        }	
		
		<?php
		
		}
		
		?>
        
        var no_cursor     = "<?php echo $LANG->line('html_buttons_no_cursor'); ?>";
        var url_text      = "<?php echo $LANG->line('html_buttons_url_text'); ?>";
        var webpage_text  = "<?php echo $LANG->line('html_buttons_webpage_text'); ?>";
        var title_text	  = "<?php echo $LANG->line('html_buttons_title_text'); ?>";
        var image_text    = "<?php echo $LANG->line('html_buttons_image_text'); ?>";
        var email_text    = "<?php echo $LANG->line('html_buttons_email_text'); ?>";
        var email_title   = "<?php echo $LANG->line('html_buttons_email_title'); ?>";
        var enter_text    = "<?php echo $LANG->line('html_buttons_enter_text'); ?>";
        		
        <?php
            
            echo "\n";
        
            foreach ($jsvars as $val)
            {
                echo "var $val = 0;\n";
            }
            
        ?>
        
        var tagarray  	= new Array();
        var usedarray 	= new Array();
        var running		= 0;
        
        function nullo()
        {
            return;
        }
                
        //  State change
        
        function styleswap(link)
        {
            if (document.getElementById(link).className == 'htmlButtonA')
            {
                document.getElementById(link).className = 'htmlButtonB';
            }
            else
            {
                document.getElementById(link).className = 'htmlButtonA';
            }
			
			if (document.getElementById('close_all').className == 'htmlButtonA')
			{
				document.getElementById('close_all').className = 'htmlButtonB';
			}            
        }
        
        //  Set button mode
        
        function setmode(which)
        {	
            if (which == 'guided')
                selMode = 'guided';
            else
                selMode = 'normal';
        }
        
        // Clear state
        
        function clear_state()
        {
            if (usedarray[0])
            {
                while (usedarray[0])
                {
                    clearState = arraypop(usedarray);
                    eval(clearState + " = 0");
                    document.getElementById(clearState).className = 'htmlButtonA';
                }
                
				if (document.getElementById('close_all').className == 'htmlButtonB')
				{
					document.getElementById('close_all').className = 'htmlButtonA';
				}            
            }	
        }
                
        // Prompted tags
        
        function promptTag(which)
        {
            if ( ! selField)
            {
                alert(no_cursor);
                return;
            }
        
            if ( ! which)
                return;
                
            var theSelection = "";  
            eval("var theField = document.getElementById('entryform')." + selField + ";");
            
            if (document.selection)
            {	
                if (document.selection.createRange().text)
                {
                	theSelection = document.selection.createRange().text; 
                }
            }
            else if ( ! isNaN(theField.selectionEnd))
			{
				var selLength = theField.textLength;
				var selStart = theField.selectionStart;
				var selEnd = theField.selectionEnd;
				if (selEnd <= 2 && typeof(selLength) != 'undefined')
					selEnd = selLength;
				
				var s1 = (theField.value).substring(0,selStart);
				var s2 = (theField.value).substring(selStart, selEnd)
				var s3 = (theField.value).substring(selEnd, selLength);
				theSelection = s2;
			}
            
                
        
            if (which == "link")
            {
                var URL = prompt(url_text, "http://");
                        
                if ( ! URL || URL == 'http://' || URL == null)
                    return; 
                
                var Name = prompt(webpage_text, theSelection);
                
                if (Name == null)
                {
                	return;
                }
                
                if ( ! Name)
                {
                	Name = URL;
                }
         
                
                var Title = prompt(title_text, theSelection);
                
                if (Title == null)
                    return; 
                
                if (Title == "")
                {
                	var Title = Name;
                }
            
				Title = Title.replace(/\"/g, '&quot;');

               var Link = '<a href="' + URL + '" title="' + Title + '">' + Name + '<'+'/a>';
            }
            
            
            if (which == "email")
            {
                var Email = prompt(email_text, "");
                
                if ( ! Email || Email == null)
                    return; 
                    
                var Title = prompt(email_title, theSelection);
                
                if (Title == null)
                	return;
            
                if (!Title || Title == "")
                    Title = Email;
            
            	var Link = '{' + 'encode="' + Email + '" title="' + Title + '"}';
            
               // var Link = '<a href="mailto:' + Email + '">' + Title + '<'+'/a>';                
            }
        
            if (which == "image")
            {
                var URL   = prompt(image_text, "http://");
                
                if ( ! URL || URL == null)
                    return; 
            
                var Link = '<img src="' + URL + '" />';
            }
        	
        	if (document.selection) 
            {		            
            	theField.focus();
            
            	document.selection.createRange().text = Link;
			}
            else if ( ! isNaN(theField.selectionEnd))
			{
				var newStart = s1.length + Link.length;
				theField.value = s1 + Link + s3;
				
				theField.focus();
				theField.selectionStart = newStart;
				theField.selectionEnd = newStart;
				return;
			}
			else
			{
            	eval("document.getElementById('entryform')." + selField + ".value += Link");		
            }
            
            theSelection = '';         
            theField.blur();        
            theField.focus();
            return;
        }
        
        // Close all tags
        
        function closeall()
        {	
            if (tagarray[0])
            {
                while (tagarray[0])
                {
                    closeTag = arraypop(tagarray);
                    eval("document.getElementById('entryform')." + selField + ".value += closeTag");			
                }
            }
            
            clear_state();	
            running = 0;
            curField = eval("document.getElementById('entryform')." + selField);
            curField.focus();
        }
        
        //-->
        </script>
        
        <?php

    $javascript = ob_get_contents();
    
    ob_end_clean();

    return $this->insert_javascript().$javascript.$r;

    }
    /* END */
        
    /** ---------------------------------------------------------------
    /**  JavaScript For Inserting pMCode, Glossary, and Smileys
    /** ---------------------------------------------------------------*/
    
	function insert_javascript()
	{
		ob_start();
        
        ?>     
        
        <script type="text/javascript"> 
        <!--
        
		var selField  = false;
        var selMode   = "normal";
   		
  		//  Dynamically set the textarea name
        
        function setFieldName(which)
        {
            if (which != selField)
            {
                selField = which;
                
                clear_state();
                        
                tagarray  = new Array();
                usedarray = new Array();
                running	  = 0;
            }
        }
        
        // Insert tag
        function taginsert(item, tagOpen, tagClose)
        {
            // Determine which tag we are dealing with
        
            var which = eval('item.name');
            
            if ( ! selField)
            {
                alert(no_cursor);
                return false;
            }
            
            var theSelection = false;  
            var result		 = false
            eval("var theField = document.getElementById('entryform')." + selField + ";");
            
            if (selMode == 'guided')
            {
                data = prompt(enter_text, "");
                
                if ((data != null) && (data != ""))
                {
                    result =  tagOpen + data + tagClose;			
                }
            }
        
        
            // Is this a Windows user?
            // If so, add tags around selection
        
            if (document.selection) 
            {
            	theSelection = document.selection.createRange().text;
            	
            	theField.focus();
            
            	if (theSelection)
            	{
                	document.selection.createRange().text = (result == false) ? tagOpen + theSelection + tagClose : result;
                }
                else
                {
                	document.selection.createRange().text = (result == false) ? tagOpen + tagClose : result;
                }
                
                theSelection = '';
                
                theField.blur();
                theField.focus();
                
                return;
            }
            else if ( ! isNaN(theField.selectionEnd))
			{
				var scrollPos = theField.scrollTop;
				var selLength = theField.textLength;
				var selStart = theField.selectionStart;
				var selEnd = theField.selectionEnd;
				if (selEnd <= 2 && typeof(selLength) != 'undefined')
					selEnd = selLength;

				var s1 = (theField.value).substring(0,selStart);
				var s2 = (theField.value).substring(selStart, selEnd)
				var s3 = (theField.value).substring(selEnd, selLength);
				
				if (result == false)
				{
					var newStart = selStart + tagOpen.length + s2.length + tagClose.length;
				
					theField.value = (result == false) ? s1 + tagOpen + s2 + tagClose + s3 : result;
				}
				else
				{
					var newStart = selStart + result.length;
				
					theField.value = s1 + result + s3;
				}
				
				theField.focus();
				theField.selectionStart = newStart;
				theField.selectionEnd = newStart;
				theField.scrollTop = scrollPos;
				return;
			}
			else if (selMode == 'guided')
			{
				eval("document.submit_post." + selField + ".value += result");			
				
				curField = eval("document.submit_post." + selField);
				curField.blur();
				curField.focus();	
				return;		
			}
			
            // Add single open tags
            
            if (item == 'other')
            {
            	eval("document.getElementById('entryform')." + selField + ".value += tagOpen");
            }
            else if (eval(which) == 0)
            {
                var result = tagOpen;
                
                eval("document.getElementById('entryform')." + selField + ".value += result");			
                eval(which + " = 1");
                
                arraypush(tagarray, tagClose);
                arraypush(usedarray, which);
                
                running++;
                               
                styleswap(which);
            }
            else
            {
                // Close tags
            
                n = 0;
                
                for (i = 0 ; i < tagarray.length; i++ )
                {
                    if (tagarray[i] == tagClose)
                    {
                        n = i;
                        
                        running--;
                        
						while (tagarray[n])
						{
							closeTag = arraypop(tagarray);
							eval("document.getElementById('entryform')." + selField + ".value += closeTag");			
						}
						
						while (usedarray[n])
						{
							clearState = arraypop(usedarray);
							eval(clearState + " = 0");
							document.getElementById(clearState).className = 'htmlButtonA';
						}						
                    }
                }
                 
				if (running <= 0 && document.getElementById('close_all').className == 'htmlButtonB')
				{
					document.getElementById('close_all').className = 'htmlButtonA';
				}                
                
            }
            
            curField = eval("document.getElementById('entryform')." + selField);
            curField.blur();
            curField.focus();	
        }
        
        //-->
        </script>
        
        <?php

		$javascript = ob_get_contents();
		
		ob_end_clean();
	
		return $javascript;
	}
	/* END */


    /** ---------------------------------------------------------------
    /**  View previous pings
    /** ---------------------------------------------------------------*/
    // This function lets you look at trackback pings that you sent previously
    //---------------------------------------------------------------

    function view_previous_pings()
    {
        global $IN, $DSP, $LANG, $DB;
           
        if ( ! $entry_id = $IN->GBL('entry_id', 'GP'))
        {
            return false;
        }
        
        if ( ! is_numeric($entry_id))
        {
        	return false;
        }
        
        $query = $DB->query("SELECT sent_trackbacks FROM  exp_weblog_titles WHERE entry_id = '$entry_id'");        
        
        if ($query->num_rows == 0)
        {
            return false;
        }


        $DSP->title = $LANG->line('view_previous_pings');
        $DSP->crump = $LANG->line('view_previous_pings');
        
        $DSP->body  = $DSP->div('fieldWrapper').
                      $DSP->div('bold').
                      $LANG->line('previiously_pinged_urls').
                      $DSP->div_c().
                      $DSP->input_textarea('trackback_urls', $query->row['sent_trackbacks'], 12, 'textarea', '99%').
                      $DSP->div_c();        
    }   
    /* END */
   
   
   
    
   
   
//=====================================================================
//  "EDIT" PAGE FUNCTIONS
//=====================================================================
    
       
   
    /** --------------------------------------------
    /**  Edit weblogs page
    /** --------------------------------------------*/
    // This function is called when the EDIT tab is clicked
    //--------------------------------------------

    function edit_entries($weblog_id = '', $message = '')
    {
    	global $LANG, $DSP;
    
    	$DSP->title  = $LANG->line('edit_weblog_entries');
        $DSP->crumb  = $LANG->line('edit_weblog_entries');
        $DSP->body  .= $this->view_entries($weblog_id, $message);
    }
    /* END */
    
    function view_entries($weblog_id = '', $message = '', $extra_sql = '', $search_url = '', $form_url = '', $action = '', $extra_fields_search='', $extra_fields_entries='')
    {
    	global $IN, $LANG, $DSP, $FNS, $LOC, $DB, $SESS, $REGX, $PREFS, $EXT;
        
        // Security check
        
        if ( ! $DSP->allowed_group('can_access_edit'))
        {
            return $DSP->no_access_message();
        }
        
        /** --------------------------------------------
        /**  Fetch weblog ID numbers assigned to the current user
        /** --------------------------------------------*/
          
		$allowed_blogs = $FNS->fetch_assigned_weblogs();
        
		if (empty($allowed_blogs))
		{
			return $DSP->no_access_message($LANG->line('no_weblogs'));
		}
        
        // -------------------------------------------
        // 'edit_entries_start' hook.
        //  - Allows complete rewrite of Edit Entries page.
        //
        	$edata = $EXT->call_extension('edit_entries_start', $weblog_id, $message);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
                                
        /** ------------------------------
        /**  Fetch Color Library
        /** ------------------------------*/
        
        // We use this to assist with our status colors
        
        if (file_exists(PATH.'lib/colors'.EXT))
        {
			include (PATH.'lib/colors'.EXT);
        }
        else
        {	
			$colors = '';
        }
                
        // We need to determine which weblog to show entries from.
        // if the weblog_id global doesn't exist we'll show all weblogs
        // combined
                
        if ($weblog_id == '')
        {
            $weblog_id = $IN->GBL('weblog_id', 'GP');
        }
        
        if ($weblog_id == 'null' OR $weblog_id === FALSE OR ! is_numeric($weblog_id))
        {
            $weblog_id = '';
        }
                
        $cat_group = '';
        $cat_id = $IN->GBL('cat_id', 'GP');
        $status = $IN->GBL('status', 'GP');      
        $order  = $IN->GBL('order', 'GP');
        $date_range = $IN->GBL('date_range', 'GP');
		$total_blogs = count($allowed_blogs);
              
        // Begin building the page output
                
        $r = $DSP->qdiv('tableHeading', $LANG->line('edit_weblog_entries'));
        
        // Do we have a message to show?
        // Note: a message is displayed on this page after editing or submitting a new entry
        
        if ($IN->GBL("U") == 'mu')
        {
  			$message = $DSP->qdiv('success', $LANG->line('multi_entries_updated'));      
        }
 
        if ($message != '')
        {
            $r .= $message;
        }     
        
        // Declare the "filtering" form
        
        $s = $DSP->form_open(
        						array(
        								'action'	=> ($search_url != '') ? $search_url : 'C=edit'.AMP.'M=view_entries', 
        								'name'		=> 'filterform',
        								'id'		=> 'filterform'
        							)
        					);
        					
        $s .= $extra_fields_search;
        
        // If we have more than one weblog we'll write the JavaScript menu switching code       
        
        if ($total_blogs > 1)
        {      
            $s .= Publish::filtering_menus();
        }
        
        // Table start
        
        $s .= $DSP->div('box');
        $s .= $DSP->table('', '0', '', '100%').
              $DSP->tr().
              $DSP->td('itemWrapper', '', '7').NL;
        
        // If we have more than one blog we'll add the "onchange" method to
        // the form so that it'll automatically switch categories and statuses
        
        if ($total_blogs > 1)
        {       
            $s .= "<select name='weblog_id' class='select' onchange='changemenu(this.selectedIndex);'>\n";
        }
        else
        {
            $s .= "<select name='weblog_id' class='select'>\n";
        }
        
        
        // Design note:  Becuase the JavaScript code dynamically switches the information inside the
        // pull-down menus we can't show any particular menu in a "selected" state unless there is only
        // one weblog.  Remember that each weblog is fully independent, so it can have its own 
        // categories, statuses, etc. 
        
        // Weblog selection pull-down menu
                
        // Fetch the names of all weblogs and write each one in an <option> field
        
        $sql = "SELECT blog_title, weblog_id, cat_group FROM exp_weblogs";
        
        // If the user is restricted to specific blogs, add that to the query
        
        if ($SESS->userdata['group_id'] == 1)
        {
            $sql .= " WHERE is_user_blog = 'n'";
        }
        else
        {
            $sql .= " WHERE weblog_id IN (";
        
            foreach ($allowed_blogs as $val)
            {
                $sql .= "'".$val."',"; 
            }
            
            $sql = substr($sql, 0, -1).')';
        }

        $sql .= " AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' order by blog_title";    
                
        $query = $DB->query($sql);
                
        if ($query->num_rows == 1)
        {
        	$weblog_id = $query->row['weblog_id'];
            $cat_group = $query->row['cat_group'];
        }
        elseif($weblog_id != '')
        {
        	foreach($query->result as $row)
        	{
        		if ($row['weblog_id'] == $weblog_id)
        		{
        			$weblog_id = $row['weblog_id'];
            		$cat_group = $row['cat_group'];
        		}
        	}
        }
        
        $s .= $DSP->input_select_option('null', $LANG->line('filter_by_weblog'));
        
        if ($query->num_rows > 1)
        {
			$s .= $DSP->input_select_option('null',  $LANG->line('all'));
        }
        
        $selected = '';
        
        foreach ($query->result as $row)
        {
            if ($weblog_id != '')
            {               
                $selected = ($weblog_id == $row['weblog_id']) ? 'y' : '';          
            }      
        
            $s .= $DSP->input_select_option($row['weblog_id'], $row['blog_title'], $selected);
        }        

        $s .= $DSP->input_select_footer().
              $DSP->nbs(2);
        
        
        // Category pull-down menu
        
        $s .= $DSP->input_select_header('cat_id').
              $DSP->input_select_option('', $LANG->line('filter_by_category'));
              
        if ($total_blogs > 1)
        {               
			$s .= $DSP->input_select_option('all', $LANG->line('all'), ($cat_id == 'all') ? 'y' : '');
		}
              
		$s .= $DSP->input_select_option('none', $LANG->line('none'), ($cat_id == 'none') ? 'y' : '');

        if ($cat_group != '')
        {
        	if (TRUE)
			{
				$corder = ($this->nest_categories == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';
        	
				$query = $DB->query("SELECT cat_id, cat_name, group_id, parent_id FROM exp_categories WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY ".$corder);
				
				$categories = array();
				
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{			
						$categories[] = array($row['group_id'], $row['cat_id'], $REGX->entities_to_ascii($row['cat_name']), $row['parent_id']);
					}
					
					if ($this->nest_categories == 'y')
					{
						$this->cat_array = array();
						
						foreach($categories as $key => $val)
						{
							if (0 == $val['3']) 
							{
								$this->cat_array[] = array($val['0'], $val['1'], $val['2']);
								$this->category_edit_subtree($val['1'], $categories, $depth=1);
							}
						}
					}
					else
					{
						$this->cat_array = $categories;
					}
				}
			
				foreach($this->cat_array as $key => $val)
				{
					if ( ! in_array($val['0'], explode('|',$cat_group)))
					{
						unset($this->cat_array[$key]);
					}
				}
			}
		
            foreach ($this->cat_array as $ckey => $cat)
            {
            	if ($ckey-1 < 0 OR ! isset($this->cat_array[$ckey-1]))
            	{
            		$s .= $DSP->input_select_option('', '-------');
            	}
            
            	$s .= $DSP->input_select_option($cat['1'], str_replace('!-!', '&nbsp;', $cat['2']), (($cat_id == $cat['1']) ? 'y' : ''));
            	
            	if (isset($this->cat_array[$ckey+1]) && $this->cat_array[$ckey+1]['0'] != $cat['0'])
            	{
            		$s .= $DSP->input_select_option('', '-------');
            	}
			}
        }

        $s .= $DSP->input_select_footer().
              $DSP->nbs(2);
        
        
        // Status pull-down menu
        
        $s .= $DSP->input_select_header('status').
              $DSP->input_select_option('', $LANG->line('filter_by_status')).
              $DSP->input_select_option('all', $LANG->line('all'), ($status == 'all') ? 1 : '');
        
        if ($weblog_id != '')
        {     
            $rez = $DB->query("SELECT status_group FROM exp_weblogs WHERE weblog_id = '$weblog_id'");                                    
        
            $query = $DB->query("SELECT status FROM exp_statuses WHERE group_id = '".$DB->escape_str($rez->row['status_group'])."' ORDER BY status_order");                            
                            
            if ($query->num_rows > 0)
            {
                foreach ($query->result as $row)
                {
                    $selected = ($status == $row['status']) ? 1 : '';   
					$status_name = ($row['status'] == 'closed' OR $row['status'] == 'open') ?  $LANG->line($row['status']) : $row['status'];                
                    $s .= $DSP->input_select_option($row['status'], $status_name, $selected);
                }
            }
        } 
        else
        {
			 $s .= $DSP->input_select_option('open', $LANG->line('open'), ($status == 'open') ? 1 : '');
			 $s .= $DSP->input_select_option('closed', $LANG->line('closed'), ($status == 'closed') ? 1 : '');
        }
        
        $s .= $DSP->input_select_footer().
              $DSP->nbs(2);
        
        // Date range pull-down menu
        
        $sel_1 = ($date_range == '1')   ? 1 : '';          
		$sel_2 = ($date_range == '7')   ? 1 : '';          
		$sel_3 = ($date_range == '31')  ? 1 : '';          
		$sel_4 = ($date_range == '182') ? 1 : '';          
		$sel_5 = ($date_range == '365') ? 1 : '';          
        
        $s .= $DSP->input_select_header('date_range').
              $DSP->input_select_option('', $LANG->line('date_range')).
              $DSP->input_select_option('1', $LANG->line('today'), $sel_1).
              $DSP->input_select_option('7', $LANG->line('past_week'), $sel_2).
              $DSP->input_select_option('31', $LANG->line('past_month'), $sel_3).
              $DSP->input_select_option('182', $LANG->line('past_six_months'), $sel_4).
              $DSP->input_select_option('365', $LANG->line('past_year'), $sel_5).
              $DSP->input_select_option('', $LANG->line('any_date')).
              $DSP->input_select_footer().
              $DSP->nbs(2);
        
        
        // Display order pull-down menu
    
		$sel_1 = ($order == 'desc')  ? 1 : '';          
		$sel_2 = ($order == 'asc')   ? 1 : '';          
		$sel_3 = ($order == 'alpha') ? 1 : '';          
        
        $s .= $DSP->input_select_header('order').
              $DSP->input_select_option('desc', $LANG->line('order'), $sel_1).
              $DSP->input_select_option('asc', $LANG->line('ascending'), $sel_2).
              $DSP->input_select_option('desc', $LANG->line('descending'), $sel_1).
              $DSP->input_select_option('alpha', $LANG->line('alpha'), $sel_3).
              $DSP->input_select_footer().
              $DSP->nbs(2);
              
        // Results per page pull-down menu
        
        if ( ! ($perpage = $IN->GBL('perpage', 'GP')))
        {
        	$perpage = $IN->GBL('perpage', 'COOKIE');
        }
        if ($perpage == '')
			$perpage = 50;       
        
        $FNS->set_cookie('perpage' , $perpage, 60*60*24*182);   
                       
        $s .= $DSP->input_select_header('perpage').
              $DSP->input_select_option('25', '25 '.$LANG->line('results'), ($perpage == 25)  ? 1 : '').
              $DSP->input_select_option('50', '50 '.$LANG->line('results'), ($perpage == 50)  ? 1 : '').
              $DSP->input_select_option('75', '75 '.$LANG->line('results'), ($perpage == 75)  ? 1 : '').
              $DSP->input_select_option('100', '100 '.$LANG->line('results'), ($perpage == 100)  ? 1 : '').
              $DSP->input_select_option('150', '150 '.$LANG->line('results'), ($perpage == 150)  ? 1 : '').
              $DSP->input_select_footer().
              $DSP->nbs(2);              
        
        $s .= $DSP->td_c().
              $DSP->tr_c().        
              $DSP->tr().
              $DSP->td('itemWrapper', '', '7').NL;
        
        if (isset($_POST['keywords'])) 
        {
        	$keywords = $REGX->keyword_clean($_POST['keywords']);
        }
        elseif (isset($_GET['keywords'])) 
        {
        	$keywords = $REGX->keyword_clean(base64_decode($_GET['keywords']));
        }
        else
        {
			$keywords = '';
        }
        
        if (substr(strtolower($keywords), 0, 3) == 'ip:')
		{
			$keywords = str_replace('_','.',$keywords);
		}
		
		// Because of the auto convert we prepare a specific variable
		// with the converted ascii characters while leaving the $keywords
		// variable intact for display and URL purposes
		
		$search_keywords = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? $REGX->ascii_to_entities($keywords) : $keywords;
        
        $exact_match = ($IN->GBL('exact_match', 'GP') != '') ? $IN->GBL('exact_match', 'GP') : '';
        
        $s .= $DSP->div('default').$LANG->line('keywords', 'keywords').NBS.NBS;
        $s .= $DSP->input_text('keywords', stripslashes($keywords), '40', '200', 'input', '200px').NBS.NBS;        
		$s .= $DSP->input_checkbox('exact_match', 'yes', $exact_match).NBS.$LANG->line('exact_match').NBS.NBS;
        
        $search_in = ($IN->GBL('search_in', 'GP') != '') ? $IN->GBL('search_in', 'GP') : 'title';

        $s .= $DSP->input_select_header('search_in').
              $DSP->input_select_option('title', $LANG->line('title_only'), ($search_in == 'title') ? 1 : '').
              $DSP->input_select_option('body', $LANG->line('title_and_body'), ($search_in == 'body') ? 1 : '').
              $DSP->input_select_option('everywhere', $LANG->line('title_body_comments'), ($search_in == 'everywhere') ? 1 : '').
              (( ! isset($this->installed_modules['comment'])) ? '' : $DSP->input_select_option('comments', $LANG->line('comments'), ($search_in == 'comments') ? 1 : '')).
              (( ! isset($this->installed_modules['trackback'])) ? '' : $DSP->input_select_option('trackbacks', $LANG->line('trackbacks'), ($search_in == 'trackbacks') ? 1 : '')).
              $DSP->input_select_footer().
              $DSP->nbs(2);
       
        // Submit button and form close

        $s .= $DSP->input_submit($LANG->line('search'), 'submit');
        $s .= $DSP->div_c();
                      
        $s .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
        $s .= $DSP->div_c();
        $s .= $DSP->form_close();
        
        
		// -------------------------------------------
        // 'edit_entries_search_form' hook.
        //  - Allows complete rewrite of Edit Entries Search form.
        //
        	if ($EXT->active_hook('edit_entries_search_form') === TRUE)
        	{
        		$r .= $EXT->call_extension('edit_entries_search_form', $s);
        		if ($EXT->end_script === TRUE) return;
        	}
        	else
        	{
        		$r .= $s;
        	}
        //
        // -------------------------------------------
        
        
        /** ------------------------------
        /**  Build the main query
        /** ------------------------------*/
        if ($search_url != '')
        {
        	$pageurl = BASE.AMP.$search_url;
        }
        else
        {
        	$pageurl = BASE.AMP.'C=edit'.AMP.'M=view_entries';
        }
        
		$sql_a = "SELECT ";
		
		if ($search_in == 'comments')
		{
			$sql_b = "DISTINCT(exp_comments.comment_id) ";
		}
		elseif ($search_in == 'trackbacks')
		{
			$sql_b = "DISTINCT(exp_trackbacks.trackback_id) ";
		}
		else
		{
			$sql_b = ($cat_id == 'none' || $cat_id != "") ? "DISTINCT(exp_weblog_titles.entry_id) " : "exp_weblog_titles.entry_id ";
		}
		
		$sql = "FROM exp_weblog_titles
				LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id ";
				
		if ($keywords != '')
		{
			if ($search_in != 'title')
				$sql .= "LEFT JOIN exp_weblog_data ON exp_weblog_titles.entry_id = exp_weblog_data.entry_id ";
				
			if ($search_in == 'everywhere' OR $search_in == 'comments')
			{
				$sql .= "LEFT JOIN exp_comments ON exp_weblog_titles.entry_id = exp_comments.entry_id ";
			}
			elseif($search_in == 'trackbacks')
			{
				$sql .= "LEFT JOIN exp_trackbacks ON exp_weblog_titles.entry_id = exp_trackbacks.entry_id ";
			}
		}
		elseif ($search_in == 'comments')
		{
			$sql .= "LEFT JOIN exp_comments ON exp_weblog_titles.entry_id = exp_comments.entry_id ";
		}
		elseif ($search_in == 'trackbacks')
		{
			$sql .= "LEFT JOIN exp_trackbacks ON exp_weblog_titles.entrY_id = exp_trackbacks.entry_id ";
		}
				
		$sql .= "LEFT JOIN exp_members ON exp_members.member_id = exp_weblog_titles.author_id ";
						  
        if ($cat_id == 'none' || $cat_id != "")                     
        {
			$sql .= "LEFT JOIN exp_category_posts ON exp_weblog_titles.entry_id = exp_category_posts.entry_id
					 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
        }
        
        if (is_array($extra_sql) && isset($extra_sql['tables']))
        {
        	$sql .= ' '.$extra_sql['tables'].' ';
        }
        
        // -------------------------------------------
        // 'edit_entries_search_tables' hook.
        //  - Add additional parts to the TABLES part of query
        //
        	if ($EXT->active_hook('edit_entries_search_tables') === TRUE)
        	{
        		$sql .= $EXT->call_extension('edit_entries_search_tables');
        	}
        //
        // -------------------------------------------
        
        
        // Limit to weblogs assigned to user  
        
        if ($SESS->userdata('member_id') == 0)
        {
            $sql .= " WHERE is_user_blog = 'n' AND exp_weblogs.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
        }
        else
        {
            $sql .= " WHERE exp_weblogs.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND exp_weblog_titles.weblog_id IN (";
        
            foreach ($allowed_blogs as $val)
            {
                $sql .= "'".$val."',"; 
            }
            
            $sql = substr($sql, 0, -1).')';
            
            if ( ! $DSP->allowed_group('can_edit_other_entries') AND ! $DSP->allowed_group('can_view_other_entries'))
            {
        		$sql .= " AND exp_weblog_titles.author_id = ".$SESS->userdata('member_id'); 
            }            
        }
        
        if (is_array($extra_sql) && isset($extra_sql['where']))
        {
        	$sql .= ' '.$extra_sql['where'].' ';
        }              

		if ($keywords != '')
		{
			$pageurl .= AMP.'keywords='.base64_encode($keywords);

			if ($search_in == 'trackbacks' OR $search_in == 'comments')
			{
				// When searching in comments and trackbacks we do not want to
				// search the entry title.  However, by removing this we would
				// have to make the rest of the query creation code below really messy
				// so we simply check for an empty title, which should never happen.
				// That makes this check pointless and allows us some cleaner code. -Paul
				
				$sql .= " AND (exp_weblog_titles.title = '' ";
			}
			else
			{
				if ($exact_match != 'yes')
				{			
					$sql .= " AND (exp_weblog_titles.title LIKE '%".$DB->escape_like_str($search_keywords)."%' ";
				}
				else
				{	
					$pageurl .= AMP.'exact_match=yes';
				
					$sql .= " AND (exp_weblog_titles.title = '".$DB->escape_str($search_keywords)."' OR exp_weblog_titles.title LIKE '".$DB->escape_like_str($search_keywords)." %' OR exp_weblog_titles.title LIKE '% ".$DB->escape_like_str($search_keywords)." %' ";
				}
			}
			
			$pageurl .= AMP.'search_in='.$search_in;
			
			if ($search_in == 'body' OR $search_in == 'everywhere')
			{
				/** ---------------------------------------
				/**  Fetch the searchable field names
				/** ---------------------------------------*/
						
				$fields = array();
						
				$xql = "SELECT DISTINCT(field_group) FROM exp_weblogs WHERE ";
				
				$xql .= (USER_BLOG !== FALSE) ? "weblog_id = '".UB_BLOG_ID."' " : "is_user_blog = 'n' ";
				
				if ($weblog_id != '')
				{
					$xql .= " AND weblog_id = '".$DB->escape_str($weblog_id)."' ";
				}
							
				$query = $DB->query($xql);
				
				if ($query->num_rows > 0)
				{
					$fql = "SELECT field_id, field_type FROM exp_weblog_fields WHERE group_id IN (";
				
					foreach ($query->result as $row)
					{
						$fql .= "'".$row['field_group']."',";	
					}
					
					$fql = substr($fql, 0, -1).')';  
									
					$query = $DB->query($fql);
					
					if ($query->num_rows > 0)
					{
						foreach ($query->result as $row)
						{
							if ($row['field_type'] == 'text' OR $row['field_type'] == 'textarea' OR $row['field_type'] == 'select')
							{
								$fields[] = $row['field_id'];
							}
						}
					}
				}
			
				foreach ($fields as $val)
				{					
					if ($exact_match != 'yes')
					{
						$sql .= " OR exp_weblog_data.field_id_".$val." LIKE '%".$DB->escape_like_str($search_keywords)."%' ";				
					}
					else
					{
						$sql .= "  OR (exp_weblog_data.field_id_".$val." LIKE '".$DB->escape_like_str($search_keywords)." %' OR exp_weblog_data.field_id_".$val." LIKE '% ".$DB->escape_like_str($search_keywords)." %' OR exp_weblog_data.field_id_".$val." = '".$DB->escape_str($search_keywords)."') ";
					}
				}
			}
			
			if ($search_in == 'everywhere' OR $search_in == 'comments')
			{
				if ($search_in == 'comments' && (substr(strtolower($search_keywords), 0, 3) == 'ip:' OR substr(strtolower($search_keywords), 0, 4) == 'mid:'))
				{
					if (substr(strtolower($search_keywords), 0, 3) == 'ip:')
					{
						$sql .= " OR (exp_comments.ip_address = '".$DB->escape_str(str_replace('_','.',substr($search_keywords, 3)))."') ";
					}
					elseif(substr(strtolower($search_keywords), 0, 4) == 'mid:')
					{
						$sql .= " OR (exp_comments.author_id = '".$DB->escape_str(substr($search_keywords, 4))."') ";
					}
				}
				else
				{
					$sql .= " OR (exp_comments.comment LIKE '%".$DB->escape_like_str($keywords)."%') "; // No ASCII conversion here!
				}
			}
			elseif ($search_in == 'trackbacks')
			{
				if ($search_in == 'trackbacks' && substr(strtolower($search_keywords), 0, 3) == 'ip:')
				{
					$sql .= " OR (exp_trackbacks.trackback_ip = '".$DB->escape_str(str_replace('_','.',substr($search_keywords, 3)))."') ";
				}
				else
				{
					$sql .= " OR (CONCAT_WS(' ', exp_trackbacks.content, exp_trackbacks.title, exp_trackbacks.weblog_name) LIKE '%".$DB->escape_like_str($keywords)."%') ";  // No ASCII conversion here either!
				}
			}
			
			$sql .= ")";
        }
                       
        
        if ($weblog_id)
        {
            $pageurl .= AMP.'weblog_id='.$weblog_id;
        
            $sql .= " AND exp_weblog_titles.weblog_id = $weblog_id";
        }
        
        if ($date_range)
        {
            $pageurl .= AMP.'date_range='.$date_range;
        
            $date_range = time() - ($date_range * 60 * 60 * 24);
                    
            $sql .= " AND exp_weblog_titles.entry_date > $date_range";
        }
             
        if (is_numeric($cat_id))
        {
            $pageurl .= AMP.'cat_id='.$cat_id;
        
            $sql .= " AND exp_category_posts.cat_id = '$cat_id'     
                      AND exp_category_posts.entry_id = exp_weblog_titles.entry_id ";    
        }
        
        if ($cat_id == 'none')
        {
            $pageurl .= AMP.'cat_id='.$cat_id;
        
            $sql .= " AND exp_category_posts.entry_id IS NULL ";             
        }        
                
        if ($status && $status != 'all')
        {
            $pageurl .= AMP.'status='.$status;
        
            $sql .= " AND exp_weblog_titles.status = '$status'";        
        }
        
        // -------------------------------------------
        // 'edit_entries_search_where' hook.
        //  - Add additional parts to the WHERE clause of search
        //
        	if ($EXT->active_hook('edit_entries_search_where') === TRUE)
        	{
        		$sql .= $EXT->call_extension('edit_entries_search_where');
        	}
        //
        // -------------------------------------------
        
        $end = " ORDER BY ";        
        
        if ($order)
        {
            $pageurl .= AMP.'order='.$order;
        
            switch ($order)
            {
                case 'asc'   : $end .= "entry_date asc";
                    break;
                case 'desc'  : $end .= "entry_date desc";
                    break;
                case 'alpha' : $end .= "title asc";
                    break;
                default      : $end .= "entry_date desc";
            }
        }
        else
        {
            $end .= "entry_date desc";
        }
         
        /** ------------------------------
        /**  Are there results?
        /** ------------------------------*/
              
		$query = $DB->query($sql_a.$sql_b.$sql);
            
        // No result?  Show the "no results" message
        
        $total_count = $query->num_rows;
        
        if ($total_count == 0)
        {            
            $r .= $DSP->qdiv('highlight', BR.$LANG->line('no_entries_matching_that_criteria'));
        
            return $DSP->set_return_data(
                                            $LANG->line('edit').$DSP->crumb_item($LANG->line('edit_weblog_entries')), 
                                            $r,
                                            $LANG->line('edit_weblog_entries')
                                        );    
        }
                
        // Get the current row number and add the LIMIT clause to the SQL query
        
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
                
        /** --------------------------------------------
        /**  Run the query again, fetching ID numbers
        /** --------------------------------------------*/
    
		$query = $DB->query($sql_a.$sql_b.$sql.$end." LIMIT ".$rownum.", ".$perpage);        

		$pageurl .= AMP.'perpage='.$perpage;
		
		if ($search_in == 'comments')
		{
			$comment_array = array();
			
			foreach ($query->result as $row)
        	{
        		$comment_array[] = $row['comment_id'];
        	}
        	
        	if ($keywords == '')
        	{
        		$pageurl .= AMP.'keywords='.base64_encode($keywords).AMP.'search_in='.$search_in;
        	}
        	
        	$pagination_links = $DSP->pager($pageurl, $total_count, $perpage, $rownum, 'rownum');
        	
        	return $this->view_comments('', '', '',  FALSE, array_unique($comment_array), $pagination_links, $rownum);
		}
		elseif ($search_in == 'trackbacks')
		{
			$trackback_array = array();
			
			foreach ($query->result as $row)
        	{
        		$trackback_array[] = $row['trackback_id'];
        	}
        	
        	if ($keywords == '')
        	{
        		$pageurl .= AMP.'keywords='.base64_encode($keywords).AMP.'search_in='.$search_in;
        	}
        	
        	$pagination_links = $DSP->pager($pageurl, $total_count, $perpage, $rownum, 'rownum');
        	
        	return $this->view_comments('', '', $message, TRUE, array_unique($trackback_array));
		}

        /** --------------------------------------------
        /**  Fetch the weblog information we need later
        /** --------------------------------------------*/

		$sql = "SELECT weblog_id, blog_name FROM exp_weblogs ";
				
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n'";
		}
		
		$sql .= "AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";
		        
        $w_array = array();
        
        $result = $DB->query($sql);

        if ($result->num_rows > 0)
        {            
            foreach ($result->result as $rez)
            {
                $w_array[$rez['weblog_id']] = $rez['blog_name'];
            }
        }
        
        /** --------------------------------------------
        /**  Fetch the status highlight colors
        /** --------------------------------------------*/
        
        $cql = "SELECT exp_weblogs.weblog_id, exp_weblogs.blog_name, exp_statuses.status, exp_statuses.highlight
                 FROM  exp_weblogs, exp_statuses, exp_status_groups
                 WHERE exp_status_groups.group_id = exp_weblogs.status_group
                 AND   exp_status_groups.group_id = exp_statuses.group_id
                 AND   exp_statuses.highlight != ''
                 AND   exp_status_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ";
                 
        
        // Limit to weblogs assigned to user
        
        if ($SESS->userdata['weblog_id'] != 0)
        {
            $sql .= " AND exp_weblogs.weblog_id IN (";
        
            foreach ($allowed_blogs as $val)
            {
                $sql .= "'".$val."',"; 
            }
            
            $sql = substr($sql, 0, -1).')';
        }
        else
        {
            $cql .= " AND is_user_blog = 'n'";     
        }
        
        
        $result = $DB->query($cql);
        
        $c_array = array();

        if ($result->num_rows > 0)
        {            
            foreach ($result->result as $rez)
            {            
                $c_array[$rez['weblog_id'].'_'.$rez['status']] = str_replace('#', '', $rez['highlight']);
            }
        }

		// "select all" checkbox

        $r .= $DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
		$r .= $DSP->magic_checkboxes();
		
        // Build the item headings  
        
        // Declare the "multi edit actions" form
        
        $r .= $DSP->form_open(
        						array(
        								'action' => ($form_url != '') ? $form_url : 'C=edit'.AMP.'M=multi_edit', 
        								'name'	=> 'target',
        								'id'	=> 'target'
        							)
        					);
        					
        $r .= $extra_fields_entries;
              
        /** --------------------------------------------
        /**  Build the output table
        /** --------------------------------------------*/
        
        $o  = $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', '#').
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('title')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('view')).
              (( ! isset($this->installed_modules['comment'])) ? '' : $DSP->table_qcell('tableHeadingAlt', $LANG->line('comments'))).
              (( ! isset($this->installed_modules['trackback'])) ? '' : $DSP->table_qcell('tableHeadingAlt', $LANG->line('trackbacks'))).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('author')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('date')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('weblog')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('status'));
              
        // -------------------------------------------
        // 'edit_entries_additional_tableheader' hook.
        //  - Add another cell row to display, title here
        //
        	if ($EXT->active_hook('edit_entries_additional_tableheader') === TRUE)
        	{
        		$o .= $EXT->call_extension('edit_entries_additional_tableheader', $query->row);
        	}
        //
        // -------------------------------------------
              
        $o .= $DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              $DSP->tr_c();
              
        // -------------------------------------------
		// 'edit_entries_modify_tableheader' hook.
		//  - Allows modifying or rewrite of Edit sections Table Header.
		//
			if ($EXT->active_hook('edit_entries_modify_tableheader') === TRUE)
			{
				$r .= $EXT->call_extension('edit_entries_modify_tableheader', $o);
				if ($EXT->end_script === TRUE) return;
			}
			else
			{
				$r .= $o;
			}
		//
		// -------------------------------------------
       
       	
        /** ----------------------------------------------
        /**  Build and run the full SQL query
        /** ----------------------------------------------*/
        
		$sql = "SELECT ";
		
		$sql .= ($cat_id == 'none' || $cat_id != "") ? "DISTINCT(exp_weblog_titles.entry_id), " : "exp_weblog_titles.entry_id, ";
		
		$sql .= "exp_weblog_titles.weblog_id,         
				exp_weblog_titles.title, 
				exp_weblog_titles.author_id, 
				exp_weblog_titles.status, 
				exp_weblog_titles.entry_date, 
				exp_weblog_titles.dst_enabled,
				exp_weblog_titles.comment_total, 
				exp_weblog_titles.trackback_total,
				exp_weblogs.live_look_template,
				exp_members.username,
				exp_members.email,
				exp_members.screen_name";
		
		// -------------------------------------------
        // 'edit_entries_search_fields' hook.
        //  - Add additional parts to the FIELDS part of query
        //
        	if ($EXT->active_hook('edit_entries_search_fields') === TRUE)
        	{
        		$sql .= $EXT->call_extension('edit_entries_search_fields');
        	}
        //
        // -------------------------------------------
				
		$sql .= " FROM exp_weblog_titles
				  LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
				  LEFT JOIN exp_members ON exp_members.member_id = exp_weblog_titles.author_id ";
						  
        if ($cat_id != 'none' AND $cat_id != "")                     
        {
			$sql .= "INNER JOIN exp_category_posts ON exp_weblog_titles.entry_id = exp_category_posts.entry_id
					 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
        }        
                
        $sql .= "WHERE exp_weblog_titles.entry_id IN (";
        
        foreach ($query->result as $row)
        {
        	$sql .= $row['entry_id'].',';
        }
        
		$sql = substr($sql, 0, -1).') '.$end;        
       
		$query = $DB->query($sql);        
       
		// load the site's templates
		$templates = array();
		
		$tquery = $DB->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
		
		if ($tquery->num_rows > 0)
		{
			foreach ($tquery->result as $row)
			{
				$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}
		
 		// Loop through the main query result and write each table row 
                       
        $i = 0;
       
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
                      
            $tr  = $DSP->tr();
            
            // Entry ID number
            
            $tr .= $DSP->table_qcell($style, $row['entry_id']);
            
            
            // Weblog entry title (view entry)
            
            $tr .= $DSP->table_qcell($style, 
                                    $DSP->anchor(
                                                  BASE.AMP.'C=edit'.AMP.'M=edit_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'], 
                                                  '<b>'.$row['title'].'</b>'
                                                )
                                    );
            // Edit entry
                        
            $show_link = TRUE;
            
            if ($row['live_look_template'] != 0 && isset($templates[$row['live_look_template']]))
			{
				$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
				
				$view_link = $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.
									$FNS->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']),
									$LANG->line('live_look'), '', TRUE);
			}
			else
			{
				if (($row['author_id'] != $SESS->userdata('member_id')) && ! $DSP->allowed_group('can_edit_other_entries'))
	            {
					$show_link = FALSE;
	            }

				$view_url  = BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'];

	            $view_link = ($show_link == FALSE) ? '--' : $DSP->anchor($view_url, $LANG->line('view'));				
			}
            
            
            $tr .= $DSP->table_qcell($style, $view_link); 
            
            // Comment count
            
            $show_link = TRUE;
			
			if ($row['author_id'] == $SESS->userdata('member_id'))
			{
				if ( ! $DSP->allowed_group('can_edit_own_comments') AND 
					 ! $DSP->allowed_group('can_delete_own_comments') AND 
					 ! $DSP->allowed_group('can_moderate_comments'))
				{
            		$show_link = FALSE;
				}
			}
			else
			{
				if ( ! $DSP->allowed_group('can_edit_all_comments') AND 
					 ! $DSP->allowed_group('can_delete_all_comments') AND 
					 ! $DSP->allowed_group('can_moderate_comments'))
				{
            		$show_link = FALSE;
				}
			}
            
            if ( isset($this->installed_modules['comment']))
            {
				//  Comment Link
				if ($show_link !== FALSE)
				{
					$res = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$row['entry_id']."'");$DB->q_count--;
					$view_url = BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'];
				}
				
				$view_link = ($show_link == FALSE) ? $DSP->qdiv('lightLinks', '--') : $DSP->qspan('lightLinks', '('.($res->row['count']).')').NBS.$DSP->anchor($view_url, $LANG->line('view'));
				
				$tr .= $DSP->table_qcell($style, $view_link);
			}
			
            if ( isset($this->installed_modules['trackback']))
            {
   				// Trackback Link
				if ($show_link !== FALSE)
				{
					$res = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");$DB->q_count--;
					$view_url = BASE.AMP.'C=edit'.AMP.'M=view_trackbacks'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'];
				}
				
				$view_link = ($show_link == FALSE) ? $DSP->qdiv('lightLinks', '--') : $DSP->qspan('lightLinks', '('.($res->row['count']).')').NBS.$DSP->anchor($view_url, $LANG->line('view'));
				
				$tr .= $DSP->table_qcell($style, $view_link);
			}
				
            // Username
            
            $name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
            $name = $DSP->anchor('mailto:'.$row['email'], $name, 'title="Send an email to '.$name.'"');
            
            $tr .= $DSP->table_qcell($style, $DSP->qdiv('smallLinks', $name));
                  
            // Date
            
			$date_fmt = ($SESS->userdata['time_format'] != '') ? $SESS->userdata['time_format'] : $PREFS->ini('time_format');

			if ($date_fmt == 'us')
			{
				$datestr = '%m/%d/%y %h:%i %a';
			}
			else
			{
				$datestr = '%Y-%m-%d %H:%i';
			}
            
		   	if ($PREFS->ini('honor_entry_dst') == 'y') 
		   	{ 		   		
				if ($row['dst_enabled'] == 'n' AND $SESS->userdata('daylight_savings') == 'y')
				{
					if ($row['entry_date'] != '')
						$row['entry_date'] -= 3600;
				}
				elseif ($row['dst_enabled'] == 'y' AND $SESS->userdata('daylight_savings') == 'n')
				{		
					if ($row['entry_date'] != '')
						$row['entry_date'] += 3600;
				}
			}			
            
            // -------------------------------------------
        	// 'edit_entries_decode_date' hook.
        	//  - Change how the date is formatted in the edit entries list
        	//
        		if ($EXT->active_hook('edit_entries_decode_date') === TRUE)
        		{
        			$tr .= $EXT->call_extension('edit_entries_decode_date', $row['entry_date']);
        		}
        		else
        		{
	            	$tr .= $DSP->td($style).$DSP->qdiv('smallNoWrap',  $LOC->decode_date($datestr, $row['entry_date'], TRUE)).$DSP->td_c();        		
        		}
        	//
        	// -------------------------------------------
                        
            // Weblog

            $tr .= $DSP->table_qcell($style, (isset($w_array[$row['weblog_id']])) ? $DSP->qdiv('smallNoWrap', $w_array[$row['weblog_id']]) : '');

            // Status
            
            $tr .= $DSP->td($style);
            
            $status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];
            
			if (isset($c_array[$row['weblog_id'].'_'.$row['status']]) AND $c_array[$row['weblog_id'].'_'.$row['status']] != '')
			{			
				$color = $c_array[$row['weblog_id'].'_'.$row['status']];
				
				$prefix = (is_array($colors) AND ! array_key_exists(strtolower($color), $colors)) ? '#' : '';
			
				$tr .= "<div style='color:".$prefix.$color.";'>".$status_name.'</div>';
			
			}
			else
			{
				if ($row['status'] == 'open')
				{
					$tr .= "<div style='color:#009933;'>".$status_name.'</div>';
				}
				elseif ($row['status'] == 'closed')
				{
					$tr .= "<div style='color:#990000;'>".$status_name.'</div>';
				}
				else
				{
					$tr .= $status_name;
				}
			}
                
            $tr .= $DSP->td_c();
            
            // -------------------------------------------
        	// 'edit_entries_additional_celldata' hook.
        	//  - Add another cell to display?
        	//
        		if ($EXT->active_hook('edit_entries_additional_celldata') === TRUE)
        		{
        			$tr .= $EXT->call_extension('edit_entries_additional_celldata', $row);
        		}
        	//
        	// -------------------------------------------
            
            // Delete checkbox
            
            $tr .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['entry_id'], '' , ' id="delete_box_'.$row['entry_id'].'"'));
                  
            $tr .= $DSP->tr_c();
            
            // -------------------------------------------
			// 'edit_entries_modify_tablerow' hook.
			//  - Allows modifying or rewrite of entry row in Edit section.
			//
				if ($EXT->active_hook('edit_entries_modify_tablerow') === TRUE)
				{
					$r .= $EXT->call_extension('edit_entries_modify_tablerow', $tr);
					if ($EXT->end_script === TRUE) return;
				}
				else
				{
					$r .= $tr;
				}
			//
			// -------------------------------------------
            
        } // End foreach
        
        $r .= $DSP->table_c();
            
        $r .= $DSP->table('', '0', '', '100%');
        $r .= $DSP->tr().
              $DSP->td();
        
        // Pass the relevant data to the paginate class
        
        $r .=  $DSP->div('crumblinks').
               $DSP->pager(
                            $pageurl,
                            $total_count,
                            $perpage,
                            $rownum,
                            'rownum'
                          ).
              $DSP->div_c().
              $DSP->td_c().
              $DSP->td('defaultRight');
              
        $r .= $DSP->input_hidden('pageurl', base64_encode($pageurl));     
              
        // Delete button
        
        $r .= $DSP->div('itemWrapper');
        
        $r .= $DSP->input_submit($LANG->line('submit'));
        
        if ($action == '')
        {
        	$r .= NBS.$DSP->input_select_header('action').
        	      $DSP->input_select_option('edit', $LANG->line('edit_selected')).
        	      $DSP->input_select_option('delete', $LANG->line('delete_selected')).
        	      $DSP->input_select_option('edit', '------').
        	      $DSP->input_select_option('add_categories', $LANG->line('add_categories')).
        	      $DSP->input_select_option('remove_categories', $LANG->line('remove_categories'));
        	      
        	// -------------------------------------------
        	// 'edit_entries_extra_actions' hook.
			//  - Add more options to the actions form at the bottom of the Edit screen
			//
				if ($EXT->active_hook('edit_entries_extra_actions') === TRUE)
				{
					$r .= $EXT->call_extension('edit_entries_extra_actions');
				}	
			//
			// -------------------------------------------        
        	      
        	$r .= $DSP->input_select_footer();
		}
		else
		{
			$r .= $action;
		}
              
        $r .= $DSP->div_c();
        
		$r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
              
        
        $r .= $DSP->form_close();
        

        // Set output data        
        
        return $r;                             
    }
    /* END */
 


	/** --------------------------------
    /**  Category Sub-tree
    /** --------------------------------*/
	function category_edit_subtree($cat_id, $categories, $depth)
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
                                
                $this->category_edit_subtree($val['1'], $categories, $depth);
            }
        }
    }
    /* END */
 
 
    /** --------------------------------------------
    /**  JavaScript filtering code
    /** --------------------------------------------*/
    // This function writes some JavaScript functions that
    // are used to switch the various pull-down menus in the
    // EDIT page
    //--------------------------------------------

    function filtering_menus()
    { 
        global $DSP, $LANG, $SESS, $FNS, $DB, $REGX, $PREFS;
     
        // In order to build our filtering options we need to gather 
        // all the weblogs, categories and custom statuses
        
        $blog_array   = array();
        $cat_array    = array();
        $status_array = array();
        
		$allowed_blogs = $FNS->fetch_assigned_weblogs();

		if (count($allowed_blogs) > 0)
		{
			// Fetch weblog titles
			
			$sql = "SELECT blog_title, weblog_id, cat_group, status_group FROM exp_weblogs";
					
			if ($SESS->userdata['group_id'] == 1)
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
			
			$sql .= " AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY blog_title";
			
			$query = $DB->query($sql);
					
			foreach ($query->result as $row)
			{
				$blog_array[$row['weblog_id']] = array($row['blog_title'], $row['cat_group'], $row['status_group']);
			}        
        }
        
        $order = ($this->nest_categories == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';
        	
		$query = $DB->query("SELECT cat_id, cat_name, group_id, parent_id FROM exp_categories WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY ".$order);
		
		$categories = array();
		
		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{			
				$categories[] = array($row['group_id'], $row['cat_id'], $REGX->entities_to_ascii($row['cat_name']), $row['parent_id']);
			}
			
			if ($this->nest_categories == 'y')
			{
				foreach($categories as $key => $val)
				{
					if (0 == $val['3']) 
					{
						$this->cat_array[] = array($val['0'], $val['1'], $val['2']);
						$this->category_edit_subtree($val['1'], $categories, $depth=1);
					}
				}
			}
			else
			{
				$this->cat_array = $categories;
			}
		} 
             
            
        $query = $DB->query("SELECT group_id, status FROM exp_statuses WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY status_order");
            
        foreach ($query->result as $row)
        {
            $status_array[]  = array($row['group_id'], $row['status']);
        }
        
        // Build the JavaScript needed for the dynamic pull-down menus
        // We'll use output buffering since we'll need to return it
        // and we break in and out of php
        
        ob_start();
                
?>

<script type="text/javascript">
<!--

var firstcategory = 1;
var firststatus = 1;

function changemenu(index)
{ 

  var categories = new Array();
  var statuses   = new Array();
  
  var i = firstcategory;
  var j = firststatus;
  
  var blogs = document.filterform.weblog_id.options[index].value;
  
    with(document.filterform.cat_id)
    {
        if (blogs == "null")
        {    
            categories[i] = new Option("<?php echo $LANG->line('all'); ?>", ""); i++;
            categories[i] = new Option("<?php echo $LANG->line('none'); ?>", "none"); i++;
    
            statuses[j] = new Option("<?php echo $LANG->line('all'); ?>", ""); j++;
            statuses[j] = new Option("<?php echo $LANG->line('open'); ?>", "open"); j++;
            statuses[j] = new Option("<?php echo $LANG->line('closed'); ?>", "closed"); j++;
        }
        
       <?php
                        
        foreach ($blog_array as $key => $val)
        {
        
        ?>
        
        if (blogs == "<?php echo $key ?>")
        {
            categories[i] = new Option("<?php echo $LANG->line('all'); ?>", ""); i++; 
            categories[i] = new Option("<?php echo $LANG->line('none'); ?>", "none"); i++; <?php echo "\n";
         
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
            
            statuses[j] = new Option("<?php echo $LANG->line('all'); ?>", ""); j++;
            <?php
    
            if (count($status_array) > 0)
            {
                foreach ($status_array as $k => $v)
                {
                    if ($v['0'] == $val['2'])
                    {
                    
					$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  $LANG->line($v['1']) : $v['1'];
            ?> 
            statuses[j] = new Option("<?php echo $status_name; ?>", "<?php echo $v['1']; ?>"); j++; <?php
                    }
                }
            }                    
             
            ?> 

        } // END if blogs
            
        <?php
         
        } // END OUTER FOREACH
         
        ?> 
        
        spaceString = eval("/!-!/g");
        
        with (document.filterform.cat_id)
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
        
        with (document.filterform.status)
        {
            for (i = length-1; i >= firststatus; i--)
                options[i] = null;
            
            for (i = firststatus;i < statuses.length; i++)
                options[i] = statuses[i];
            
            options[0].selected = true;
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
 

    /** --------------------------------------------
    /**  Multi Edit Form
    /** --------------------------------------------*/
 
    function multi_edit_form()
    { 
        global $IN, $DB, $DSP, $LANG, $FNS, $SESS, $REGX, $LOC, $PREFS, $EXT;
        
        if ( ! $DSP->allowed_group('can_access_edit'))
        {
            return $DSP->no_access_message();
        }
        
        // -------------------------------------------
        // 'multi_edit_start' hook.
        //  - Allows complete control of the Multi Edit Form
        //  - Useful if someone adds an action to the Edit section actions select list
        //
        	$edata = $EXT->call_extension('multi_edit_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        if ( ! in_array($IN->GBL('action', 'POST'), array('edit', 'delete', 'add_categories', 'remove_categories')))
        { 
            return $DSP->no_access_message();
        }
                
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->edit_entries();
        }
        
        if ($IN->GBL('action', 'POST') == 'delete')
        {
        	return $this->delete_entries_confirm();
        }
        
		/** -----------------------------
		/**  Fetch the entry IDs 
		/** -----------------------------*/
		
        $entry_ids = array();
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
            	if ($val != '')
            	{
					$entry_ids[] = $val;
				}
            }        
        }
        
        // Are there still any entry IDs at this point?
        // If not, we'll show an unauthorized message.
        
        if (count($entry_ids) == 0)
        {
            return $DSP->no_access_message($LANG->line('unauthorized_to_edit'));
        }
        
        
		/** -----------------------------
		/**  Build and run the query
		/** -----------------------------*/
		        
		$sql_top = "SELECT t.entry_id, t.weblog_id, t.author_id, t.title, t.url_title, t.entry_date, t.dst_enabled, t.status, t.allow_comments, t.allow_trackbacks, t.sticky, w.comment_system_enabled, w.show_options_cluster
					FROM exp_weblog_titles AS t, exp_weblogs AS w
					WHERE t.entry_id IN (";

		$sql = '';
        foreach ($entry_ids as $id)
        {        	
        	$sql .= $id.',';
        }
        
        $sql = substr($sql, 0, -1).') ';
        $sql .= "AND t.weblog_id = w.weblog_id ORDER BY entry_date desc";
        		
		$query = $DB->query($sql_top.$sql);
		
		/** -----------------------------
		/**  Security check...
		/** -----------------------------*/
        	
		// Before we show anything we have to make sure that the user is allowed to 
		// access the blog the entry is in, and if the user is trying
		// to edit an entry authored by someone else they are allowed to
		
		$disallowed_ids = array();			
		$assigned_weblogs = $FNS->fetch_assigned_weblogs();
		
		foreach ($query->result as $row)
		{
			if ( ! in_array($row['weblog_id'], $assigned_weblogs))
			{	
				$disallowed_ids = $row['entry_id'];			
			}
			
			if ($row['author_id'] != $SESS->userdata('member_id'))
			{    
				if ( ! $DSP->allowed_group('can_edit_other_entries'))
				{ 
				   $disallowed_ids = $row['entry_id'];
				}
			}
			
			if (count($disallowed_ids) > 0)
			{
				$disallowed_ids = array_unique($disallowed_ids);
			}
		}
		
		/** -----------------------------
		/**  Are there disallowed posts? 
		/** -----------------------------*/

		// If so, we have to remove them....
		
		if (count($disallowed_ids) > 0)
		{
			$new_ids = array_diff($entry_ids, $disallowed_ids);
			
			// After removing the disallowed entry IDs are there any left?
			
			if (count($new_ids) == 0)
			{
				return $DSP->no_access_message($LANG->line('unauthorized_to_edit'));
			}
        
			// Run the query one more time with the proper IDs.
		
			$sql = '';
			foreach ($new_ids as $id)
			{        	
				$sql .= $id.',';
			}
			unset($query);
			
			$sql = substr($sql, 0, -1).') ';
			$sql .= "ORDER BY entry_date desc";
			$query = $DB->query($sql_top.$sql);
		}
		
		/** -----------------------------
		/**  Adding/Removing of Categories Breaks Off to Their Own Function
		/** -----------------------------*/
		
		if ($IN->GBL('action', 'POST') == 'add_categories')
        {
        	return $this->multi_categories_edit('add', $query);
        }
        elseif ($IN->GBL('action', 'POST') == 'remove_categories')
        {
        	return $this->multi_categories_edit('remove', $query);
        }

		/** -----------------------------
		/**  Fetch the weblog preferences
		/** -----------------------------*/
		// We need these in order to fetch the status groups and options.

		$sql = "SELECT weblog_id, status_group, deft_status FROM exp_weblogs WHERE weblog_id IN(";
		
		$weblog_ids = array();
		foreach ($query->result as $row)
		{
			$weblog_ids[] = $row['weblog_id'];
			
			$sql .= $row['weblog_id'].',';
		}
		
		$weblog_query = $DB->query(substr($sql, 0, -1).')');
		
		
		/** --------------------------------
		/**  Fetch disallowed statuses
		/** --------------------------------*/
		
		$no_status_access = array();

		if ($SESS->userdata['group_id'] != 1)
		{
			$result = $DB->query("SELECT status_id FROM exp_status_no_access WHERE member_group = '".$SESS->userdata('group_id')."'");            
	
			if ($result->num_rows > 0)
			{
				foreach ($result->result as $row)
				{
					$no_status_access[] = $row['status_id'];
				}		
			}
		}
		
		
		
		/** -----------------------------
		/**  Build the output
		/** -----------------------------*/
		
        $r  = $DSP->form_open(array('action' => 'C=edit'.AMP.'M=update_multi_entries'));
		$r .= '<div class="tableHeading">'.$LANG->line('multi_entry_editor').'</div>';	
		
		if (isset($_POST['pageurl']))
		{
			$r .= $DSP->input_hidden('redirect', $REGX->xss_clean($_POST['pageurl']));
		}
				
		foreach ($query->result as $row)
		{
			$r .= $DSP->input_hidden('entry_id['.$row['entry_id'].']', $row['entry_id']);
			$r .= $DSP->input_hidden('weblog_id['.$row['entry_id'].']', $row['weblog_id']);
			
		   	if ($PREFS->ini('honor_entry_dst') == 'y') 
		   	{
				$r .= $DSP->input_hidden('dst_enabled['.$row['entry_id'].']', $row['dst_enabled']);
			}			
		
			$r .= NL.'<div class="publishTabWrapper">';	
			$r .= NL.'<div class="publishBox">';
			
			$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";	
			
			$r .= NL.'<td class="publishItemWrapper" valign="top" style="width:45%;">'.BR;
			$r .= $DSP->div('clusterLineR');
			
			$r .= $DSP->heading($LANG->line('title'), 5).
				  $DSP->input_text('title['.$row['entry_id'].']', $row['title'], '20', '100', 'input', '95%', 'onkeyup="liveUrlTitle();"');
			
			$r .= $DSP->qdiv('defaultSmall', NBS);
			
			$r .= $DSP->heading($LANG->line('url_title'), 5).
				  $DSP->input_text('url_title['.$row['entry_id'].']', $row['url_title'], '20', '75', 'input', '95%');
				
			$r .= $DSP->div_c();
			$r .= '</td>';
			
			/** --------------------------------
			/**  Status pull-down menu
			/** --------------------------------*/
			
			$status_queries = array();
			$status_menu = '';
			
			foreach ($weblog_query->result as $weblog_row)
			{	
				if ($weblog_row['weblog_id'] != $row['weblog_id'])
					continue;
			
				$status_query = $DB->query("SELECT * FROM exp_statuses WHERE group_id = '".$weblog_row['status_group']."' order by status_order");
				
				$menu_status = '';
				
				if ($status_query->num_rows == 0)
				{					
					$menu_status .= $DSP->input_select_option('open', $LANG->line('open'), ($row['status'] == 'open') ? 1 : '');				
					$menu_status .= $DSP->input_select_option('closed', $LANG->line('closed'), ($row['status'] == 'closed') ? 1 : '');
				}
				else
				{        		
					$no_status_flag = TRUE;
				
					foreach ($status_query->result as $status_row)
					{
						$selected = ($row['status'] == $status_row['status']) ? 1 : '';
						
						if (in_array($status_row['status_id'], $no_status_access))
						{
							continue;                
						}
						
						$no_status_flag = FALSE;
						$status_name = ($status_row['status'] == 'open' OR $status_row['status'] == 'closed') ? $LANG->line($status_row['status']) : $REGX->form_prep($status_row['status']);
						$menu_status .= $DSP->input_select_option($REGX->form_prep($status_row['status']), $status_name, $selected);
					}
					
					/** --------------------------------
					/**  Were there no statuses?
					/** --------------------------------*/
					
					// If the current user is not allowed to submit any statuses
					// we'll set the default to closed
					
					if ($no_status_flag == TRUE)
					{
						$menu_status .= $DSP->input_select_option('closed', $LANG->line('closed'));
					}
				}	
				
				$status_menu = $menu_status;
			}		
				
			$r .= NL.'<td class="publishItemWrapper" valign="top" style="width:25%;">'.BR;
			$r .= $DSP->div('clusterLineR');
			$r .= $DSP->heading($LANG->line('entry_status'), 5);
			$r .= $DSP->input_select_header('status['.$row['entry_id'].']');
			$r .= $status_menu;
			$r .= $DSP->input_select_footer();
			
			$r .= $DSP->div('itemWrapperTop');
			$r .= $DSP->heading($LANG->line('entry_date'), 5);
			$r .= $DSP->input_text('entry_date['.$row['entry_id'].']', $LOC->set_human_time($row['entry_date']), '18', '23', 'input', '150px');
			$r .= $DSP->div_c();
			
			$r .= $DSP->div_c();
			$r .= '</td>';
			
			$r .= NL.'<td class="publishItemWrapper" valign="top" style="width:30%;">'.BR;
			
			if ($row['show_options_cluster'] == 'n')
			{
				$r .= $DSP->input_hidden('sticky['.$row['entry_id'].']', $row['sticky']);
			}
			else
			{
				$r .= $DSP->heading(NBS.$LANG->line('options'), 5);
				$r .= $DSP->qdiv('publishPad', $DSP->input_checkbox('sticky['.$row['entry_id'].']', 'y', $row['sticky']).' '.$LANG->line('sticky'));
			}
			
			if ( ! isset($this->installed_modules['comment']) OR $row['comment_system_enabled'] == 'n' OR $row['show_options_cluster'] == 'n')
            {
            	$r .= $DSP->input_hidden('allow_comments['.$row['entry_id'].']', $row['allow_comments']);
            }
            else
            {
				$r .= $DSP->qdiv('publishPad', $DSP->input_checkbox('allow_comments['.$row['entry_id'].']', 'y', $row['allow_comments']).' '.$LANG->line('allow_comments'));
			}
			
			if ( ! isset($this->installed_modules['trackback']) OR $row['show_options_cluster'] == 'n')
            {
            	$r .= $DSP->input_hidden('allow_trackbacks['.$row['entry_id'].']', $row['allow_trackbacks']);
            }
            else
            {
				$r .= $DSP->qdiv('publishPad', $DSP->input_checkbox('allow_trackbacks['.$row['entry_id'].']', 'y', $row['allow_trackbacks']).' '.$LANG->line('allow_trackbacks'));
			}
			
			$r .= '</td>';
			
			$r .= "</tr></table>";
					
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();  
		}
		
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update'))).
              $DSP->form_close();

        $DSP->title = $LANG->line('multi_entry_editor');
        $DSP->crumb = $LANG->line('multi_entry_editor');         
        $DSP->body  = $r;
	}
	/* END */
 
 
    /** -----------------------------------------
    /**  Update Multi Entries
    /** -----------------------------------------*/

	function update_multi_entries()
	{
		global $DSP, $DB, $LANG, $REGX, $FNS, $LOC, $PREFS, $SESS;
	
        if ( ! $DSP->allowed_group('can_access_edit'))
        {
            return $DSP->no_access_message();
        }
	
		if ( ! is_array($_POST['entry_id']))
		{
            return $DSP->no_access_message();
		}
	
		$LANG->fetch_language_file('publish_ad');
		
		foreach ($_POST['entry_id'] as $id)
		{
			$weblog_id = $_POST['weblog_id'][$id];
		
			$data = array(
							'title'				=> strip_tags($_POST['title'][$id]),
							'url_title'			=> $_POST['url_title'][$id],
							'entry_date'		=> $_POST['entry_date'][$id],
							'status'			=> $_POST['status'][$id],
							'sticky'       		=> (isset($_POST['sticky'][$id]) AND $_POST['sticky'][$id] == 'y') ? 'y' : 'n',
							'allow_comments'	=> (isset($_POST['allow_comments'][$id]) AND $_POST['allow_comments'][$id] == 'y') ? 'y' : 'n',
							'allow_trackbacks'	=> (isset($_POST['allow_trackbacks'][$id]) AND $_POST['allow_trackbacks'][$id] == 'y') ? 'y' : 'n'
							);

			$error = array();
			
			/** ---------------------------------
			/**  No entry title? Assign error.
			/** ---------------------------------*/
			
			if ($data['title'] == "")
			{
				$error[] = $LANG->line('missing_title');
			}
	
			/** --------------------------------------
			/**  Is the title unique?
			/** --------------------------------------*/
			
			if ($data['title'] != '')
			{			
				/** ---------------------------------
				/**  Do we have a URL title?
				/** ---------------------------------*/
				
				// If not, create one from the title
				
				
				if ($data['url_title'] == '')
				{
					$data['url_title'] = $REGX->create_url_title($data['title'], TRUE);
				}
				
				// Kill all the extraneous characters.  
				// We want the URL title to pure alpha text
				
				$data['url_title'] = $REGX->create_url_title($data['url_title']);
				
				// Is the url_title a pure number?  If so we show an error.
				
				if (is_numeric($data['url_title']))
				{
					$error[] = $LANG->line('url_title_is_numeric');
				}
				
				/** ---------------------------------
				/**  Is URL title unique?
				/** ---------------------------------*/
				 
				$unique = FALSE;
				$i = 0;
				
				while ($unique == FALSE)
				{
					$temp = ($i == 0) ? $data['url_title'] : $data['url_title'].$i;
					$i++;

					$sql = "SELECT count(*) AS count FROM exp_weblog_titles WHERE url_title = '".$DB->escape_str($temp)."' AND weblog_id = '".$DB->escape_str($weblog_id)."'";
				
					if ($id != '')
					{
						$sql .= " AND entry_id != '".$DB->escape_str($id)."'";
					}
				
					 $query = $DB->query($sql);
					 
					 if ($query->row['count'] == 0)
					 {				 
						$unique = TRUE;
					 }
					 
					 // Safety
					 if ($i >= 50)
					 {
						$error[] = $LANG->line('url_title_not_unique');
						break;
					 }
				}
				
				$data['url_title'] = $temp;
			}
					
			/** ---------------------------------------------
			/**  No date? Assign error.
			/** ---------------------------------------------*/
				
			if ($data['entry_date'] == '')
			{
				$error[] = $LANG->line('missing_date');
			}
	
			/** ---------------------------------------------
			/**  Convert the date to a Unix timestamp
			/** ---------------------------------------------*/
			
			$data['entry_date'] = $LOC->convert_human_date_to_gmt($data['entry_date']);
						 
			if ( ! is_numeric($data['entry_date'])) 
			{ 
				// Localize::convert_human_date_to_gmt() returns verbose errors
				if ($data['entry_date'] !== FALSE)
				{
					$error[] = $data['entry_date'];
				}
				else
				{
					$error[] = $LANG->line('invalid_date_formatting');
				}
			}
			
			/** ---------------------------------
			/**  Do we have an error to display?
			/** ---------------------------------*/
	
			 if (count($error) > 0)
			 {
				$msg = '';
				
				foreach($error as $val)
				{
					$msg .= $DSP->qdiv('itemWrapper', $val);  
				}
				
				return $DSP->error_message($msg);
			 }
			 
			/** ---------------------------------
			/**  Day, Month, and Year Fields
			/** ---------------------------------*/
			 
			$data['year']	= date('Y', $data['entry_date']);
            $data['month']	= date('m', $data['entry_date']);
            $data['day']	= date('d', $data['entry_date']);
					
			/** ---------------------------------
			/**  Update the entry
			/** ---------------------------------*/
							
            $DB->query($DB->update_string('exp_weblog_titles', $data, "entry_id = '$id'"));   
		}
		
        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        
		$entry_ids = "'";

		foreach($_POST['entry_id'] as $id)
		{
			$entry_ids .= $DB->escape_str($id)."', '";
		}

		$entry_ids = substr($entry_ids, 0, -3);

		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_relationships
							WHERE rel_parent_id IN ({$entry_ids})
							OR rel_child_id IN ({$entry_ids})");
		
		$clear_rel = ($query->row['count'] > 0) ? TRUE : FALSE;

        if ($PREFS->ini('new_posts_clear_caches') == 'y')
        {
			$FNS->clear_caching('all', '', $clear_rel);
		}
		else
		{
			$FNS->clear_caching('sql', '', $clear_rel);
		}

		if (isset($_POST['redirect']) && ($redirect = base64_decode($REGX->xss_clean($_POST['redirect']))) !== FALSE)
		{
			$FNS->redirect($REGX->xss_clean($redirect));
		}
		else
		{
        	$FNS->redirect(BASE.AMP.'C=edit'.AMP.'U=mu');
        }
        
        exit;        
	}
	/* END */


	/** --------------------------------------------
    /**  Multi Categories Edit Form
    /** --------------------------------------------*/
 
    function multi_categories_edit($type, $query)
    { 
        global $IN, $DB, $DSP, $LANG, $OUT;
        
        if ( ! $DSP->allowed_group('can_access_edit'))
        {
            return $DSP->no_access_message();
        }
        
       	if ($query->num_rows == 0)
        {
            return $DSP->no_access_message($LANG->line('unauthorized_to_edit'));
        }
        
		/** -----------------------------
		/**  Fetch the cat_group
		/** -----------------------------*/
		
		/* Available from $query:	entry_id, weblog_id, author_id, title, url_title, 
									entry_date, dst_enabled, status, allow_comments, 
									allow_trackbacks, sticky
		*/

		$sql = "SELECT DISTINCT cat_group FROM exp_weblogs WHERE weblog_id IN(";
		
		$weblog_ids = array();
		$entry_ids  = array();
		
		foreach ($query->result as $row)
		{
			$weblog_ids[] = $row['weblog_id'];
			$entry_ids[] = $row['entry_id'];
			
			$sql .= $row['weblog_id'].',';
		}
		
		$group_query = $DB->query(substr($sql, 0, -1).')');
		
		$valid = 'n';
		
		if ($group_query->num_rows > 0)
		{
			$valid = 'y';
			$last  = explode('|', $group_query->row['cat_group']);
			
			foreach($group_query->result as $row)
			{
				$valid_cats = array_intersect($last, explode('|', $row['cat_group']));
				
				if (sizeof($valid_cats) == 0)
				{
					$valid = 'n';
					break;
				}
			}
		}
		
		if ($valid == 'n')
		{
			return $OUT->show_user_error('submission', $LANG->line('no_category_group_match'));
		}
		
		$this->category_tree(($cat_group = implode('|', $valid_cats)));
		
		if (count($this->categories) == 0)
		{  
			$cats = $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_categories')), 'categorytree');
		}
		else
		{
			$cats = "<div id='categorytree'>";
		
			foreach ($this->categories as $val)
			{
				$cats .= $val;
			}
			
			$cats .= '</div>';
		}
		
		if ($DSP->allowed_group('can_admin_weblogs') OR $DSP->allowed_group('can_edit_categories'))
		{
			$cats .= '<div id="cateditlink" style="padding:0; margin:0;display:none;">';
			
			if (stristr($cat_group, '|'))
			{
				$catg_query = $DB->query("SELECT group_name, group_id FROM exp_category_groups WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($cat_group))."')");
				
				$links = '';
				
				foreach($catg_query->result as $catg_row)
				{
					$links .= $DSP->anchorpop(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$catg_row['group_id'].AMP.'cat_group='.$cat_group.AMP.'Z=1', '<b>'.$catg_row['group_name'].'</b>').', ';
				}
				
				$cats .= $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('edit_categories').': </b>'.substr($links, 0, -2), '750');
			}
			else
			{
				$cats .= $DSP->qdiv('itemWrapper', $DSP->anchorpop(BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$cat_group.AMP.'Z=1', '<b>'.$LANG->line('edit_categories').'</b>', '750'));
			}
			
			$cats .= '</div>';
		}
		
		/** -----------------------------
		/**  Build the output
		/** -----------------------------*/
		
		$r  = $DSP->form_open(
								array(
										'action'	=> 'C=edit'.AMP.'M=entry_category_update', 
										'name'		=> 'entryform',
										'id'		=> 'entryform'
									 ),
								array(
										'entry_ids' => implode('|', $entry_ids), 
										'type'		=> ($type == 'add') ? 'add' : 'remove'
									 )
							);
							
		$r .= <<<EOT
        
        <script type="text/javascript"> 
        <!--
        
        /** ------------------------------------
        /**  Swap out categories
        /** -------------------------------------*/
     
     	// This is used by the "edit categories" feature
     	
     	function set_catlink()
     	{
     		if (document.getElementById('cateditlink'))
     		{
     			if (browser == "IE" && OS == "Mac")  
				{
					document.getElementById('cateditlink').style.display = "none";
				}
				else
				{
					document.getElementById('cateditlink').style.display = "block";
				}
			}
     	}
     	
        function swap_categories(str)
        {
        	document.getElementById('categorytree').innerHTML = str;	
        }
        
		-->
		</script>
EOT;
							
		$r .= '<div class="tableHeading">'.$LANG->line('multi_entry_category_editor').'</div>';
				
		$r .= NL.'<div class="publishTabWrapper">';	
		$r .= NL.'<div class="publishBox">';
		
		$r .= $DSP->heading(($type == 'add') ? $LANG->line('add_categories') : $LANG->line('remove_categories'), 5);
			
		$r .= NL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";
		$r .= NL.'<td class="publishItemWrapper" valign="top" style="width:45%;">'.BR;
		$r .= $cats;
		$r .= '</td>';
		$r .= "</tr></table>";
		
		$r .= $DSP->div_c();
		$r .= $DSP->div_c();
		
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update'))).
              $DSP->form_close();

		$DSP->body_props .= ' onload="set_catlink();" ';
        $DSP->title = $LANG->line('multi_entry_category_editor');
        $DSP->crumb = $LANG->line('multi_entry_category_editor');         
        $DSP->body  = $r;
	}
	/* END */
	
	/** --------------------------------------------
    /**  Update Multiple Entries with Categories
    /** --------------------------------------------*/
 
 	function multi_entry_category_update()
 	{
		global $IN, $DB, $DSP, $LANG, $PREFS, $FNS, $OUT;
        
        if ( ! $DSP->allowed_group('can_access_edit'))
        {
            return $DSP->no_access_message();
        }
        
       	if ($IN->GBL('entry_ids') === FALSE OR $IN->GBL('type') === FALSE)
        {
            return $DSP->no_access_message($LANG->line('unauthorized_to_edit'));
        }
        
        if ($IN->GBL('category') === FALSE OR ! is_array($_POST['category']) OR sizeof($_POST['category']) == 0)
		{
			return $OUT->show_user_error('submission', $LANG->line('no_categories_selected'));
		}
		
		/** ---------------------------------
        /**  Fetch categories
        /** ---------------------------------*/

        // We do this first so we can destroy the category index from 
        // the $_POST array since we use a separate table to store categories in

		foreach ($_POST['category'] as $cat_id)
		{
			$this->cat_parents[] = $cat_id;
		}
		
		if ($this->assign_cat_parent == TRUE)
		{
			$this->fetch_category_parents($_POST['category']);            
		}

    	$this->cat_parents = array_unique($this->cat_parents);

    	sort($this->cat_parents);

		unset($_POST['category']);

		$ids = array();
        
		foreach (explode('|', $_POST['entry_ids']) as $entry_id)
		{
			$ids[] = $DB->escape_str($entry_id);
		}
		
		unset($_POST['entry_ids']);
		
		$entries_string = implode("','", $ids);
        
		/** -----------------------------
		/**  Get Category Group IDs
		/** -----------------------------*/

		$query = $DB->query("SELECT DISTINCT exp_weblogs.cat_group FROM exp_weblogs, exp_weblog_titles
							 WHERE exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
							 AND exp_weblog_titles.entry_id IN ('".$entries_string."')");
							 
		$valid = 'n';
		
		if ($query->num_rows > 0)
		{
			$valid = 'y';
			$last  = explode('|', $query->row['cat_group']);
			
			foreach($query->result as $row)
			{
				$valid_cats = array_intersect($last, explode('|', $row['cat_group']));
				
				if (sizeof($valid_cats) == 0)
				{
					$valid = 'n';
					break;
				}
			}
		}
		
		if ($valid == 'n')
		{
			return $DSP->show_user_error($LANG->line('no_category_group_match'));
		}
		
		/** -----------------------------
		/**  Remove Valid Cats, Then Add...
		/** -----------------------------*/
		
		$query = $DB->query("SELECT cat_id FROM exp_categories 
                             WHERE group_id IN ('".implode("','", $valid_cats)."')
                             AND cat_id IN ('".implode("','", $this->cat_parents)."')");

		$valid_cat_ids = array();
		
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$DB->query("DELETE FROM exp_category_posts WHERE cat_id = ".$row['cat_id']." AND entry_id IN ('".$entries_string."')");
				$valid_cat_ids[] = $row['cat_id'];
			}
		}
		
		if ($IN->GBL('type') == 'add')
		{
			$insert_cats = array_intersect($this->cat_parents, $valid_cat_ids);
			// How brutish...
			foreach($ids as $id)
			{		
		        foreach($insert_cats as $val)
		        {
		            $DB->query($DB->insert_string('exp_category_posts', array('entry_id' => $id, 'cat_id' => $val)));
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
		
		return $this->edit_entries('', $DSP->qdiv('success', $LANG->line('multi_entries_updated')));
 	}
 	/* END */
 
    /** --------------------------------------------
    /**  View weblog entry
    /** --------------------------------------------*/
    // This function displays an individual weblog entry 
    //--------------------------------------------

    function view_entry()
    {
        global $DSP, $LANG, $FNS, $DB, $IN, $REGX, $SESS, $EXT, $LOC, $PREFS;
        
        // -------------------------------------------
        // 'view_entry_start' hook.
        //  - Allows complete rewrite of View Entry page.
        //
        	$edata = $EXT->call_extension('view_entry_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        if ( ! $entry_id = $IN->GBL('entry_id', 'GET'))
        {
            return false;
        }

        if ( ! $weblog_id = $IN->GBL('weblog_id', 'GET'))
        {
            return false;
        }
        
        $assigned_weblogs = $FNS->fetch_assigned_weblogs();
   
   		if ( ! in_array($weblog_id, $assigned_weblogs))
        {
            return $DSP->no_access_message($LANG->line('unauthorized_for_this_blog'));
        }
        
        
        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
        
        $TYPE = new Typography;
        $TYPE->convert_curly = FALSE;
                
        $query = $DB->query("SELECT weblog_html_formatting, weblog_allow_img_urls, weblog_auto_link_urls from exp_weblogs WHERE weblog_id = '$weblog_id'");

		if ($query->num_rows > 0)
		{
			foreach ($query->row as $key => $val)
			{        
				$$key = $val;
			}
        }
        
        $message = '';
        
        if ($U = $IN->GBL('U'))
        {
            $message = ($U == 'new') ? $DSP->qdiv('success', $LANG->line('entry_has_been_added')) : $DSP->qdiv('success', $LANG->line('entry_has_been_updated'));
        }
                
        $query = $DB->query("SELECT field_group FROM  exp_weblogs WHERE weblog_id = '$weblog_id'");        
        
        if ($query->num_rows == 0)
        {
            return false;
        }
        
        $field_group = $query->row['field_group'];
        
    
        $query = $DB->query("SELECT field_id, field_type FROM exp_weblog_fields WHERE group_id = '$field_group' ORDER BY field_order");
        
        $fields = array();
        
        foreach ($query->result as $row)
        {
            $fields['field_id_'.$row['field_id']] = $row['field_type'];
        }        
            
    
        $sql = "SELECT exp_weblog_titles.*, exp_weblog_data.*, exp_weblogs.*
                FROM   exp_weblog_titles, exp_weblog_data, exp_weblogs
                WHERE  exp_weblog_titles.entry_id = '$entry_id'
                AND    exp_weblog_titles.entry_id = exp_weblog_data.entry_id
				AND    exp_weblogs.weblog_id = exp_weblog_titles.weblog_id"; 
    
        $result = $DB->query($sql);
        
        $show_edit_link = TRUE;
        $show_comments_link = TRUE;
            
        if ($result->row['author_id'] != $SESS->userdata('member_id'))
        {    
            if ( ! $DSP->allowed_group('can_view_other_entries'))
            {
                return $DSP->no_access_message();
            }
            
            if ( ! $DSP->allowed_group('can_edit_other_entries'))
            {
        		$show_edit_link = FALSE;
        	}
        	
        	if ( ! $DSP->allowed_group('can_view_other_comments') AND 
				 ! $DSP->allowed_group('can_delete_all_comments') AND  
				 ! $DSP->allowed_group('can_moderate_comments'))
			{
            	$show_comments_link = FALSE;
			}
        }
        else
        {
        	if ( ! $DSP->allowed_group('can_edit_own_comments') AND 
				 ! $DSP->allowed_group('can_delete_own_comments') AND
				 ! $DSP->allowed_group('can_moderate_comments'))
			{
            	$show_comments_link = FALSE;
			}
        }

		$r = '';
		
		if ($message != '')
			$r .= $DSP->qdiv('box', $message);
		
        if ($result->num_rows > 0)
        {			
			$r .= $DSP->qdiv('tableHeading', stripslashes($result->row['title']));
			$r .= $DSP->div('box');
				
			foreach ($fields as $key => $val)
			{
				if (isset($result->row[$key]) AND $val != 'rel' and $result->row[$key] != '')
				{
					$expl = explode('field_id_', $key);
					
					if (isset($result->row['field_dt_'.$expl['1']]))
					{
						if ($result->row[$key] > 0)
						{
							$localize = TRUE;
							$date = $result->row[$key];
							if ($result->row['field_dt_'.$expl['1']] != '')
							{
								$date = $LOC->offset_entry_dst($date, $result->row['dst_enabled']);
								$date = $LOC->simpl_offset($date, $result->row['field_dt_'.$expl['1']]);
								$localize = FALSE;
							}
							
							$r .= $LOC->set_human_time($date, $localize);
						}
					}
					else
					{
						$r .= $TYPE->parse_type( stripslashes($result->row[$key]), 
												 array(
															'text_format'   => $result->row['field_ft_'.$expl['1']],
															'html_format'   => $weblog_html_formatting,
															'auto_links'    => $weblog_auto_link_urls,
															'allow_img_url' => $weblog_allow_img_urls,
													   )
												);
					}
				}
			}
			
			$r .= $DSP->div_c();
		}  
        
        if ($show_edit_link)
        {
			$r .= $DSP->qdiv('itemWrapperTop', $DSP->qdiv('defaultBold', $DSP->anchor(
								BASE.AMP.'C=edit'.AMP.'M=edit_entry'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, 
								$LANG->line('edit_this_entry')
							  )));
		}
		
		if ($show_comments_link)
        {	
        	if (isset($this->installed_modules['comment']))
        	{				
				$res = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$entry_id."'");$DB->q_count--;
				
				$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $DSP->anchor(
									BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, 
									$LANG->line('view_comments').NBS.'('.$res->row['count'].')'
								  )));
			}
			
			
			if ( isset($this->installed_modules['trackback']))
			{
				$res = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE entry_id = '".$entry_id."'");$DB->q_count--;
        	
				$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $DSP->anchor(
									BASE.AMP.'C=edit'.AMP.'M=view_trackbacks'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, 
									$LANG->line('view_trackbacks').NBS.'('.$res->row['count'].')'
								  )));
			}
		}
		
		if ($result->row['live_look_template'] != 0)
		{
			$res = $DB->query("SELECT exp_template_groups.group_name, exp_templates.template_name
								FROM exp_template_groups, exp_templates
								WHERE exp_template_groups.group_id = exp_templates.group_id
								AND exp_templates.template_id = '".$DB->escape_str($result->row['live_look_template'])."'");
			
			if ($res->num_rows == 1)
			{
				$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
				
				$r .= $DSP->qdiv('itemWrapper', 
								$DSP->qdiv('defaultBold', 
									$DSP->anchor($FNS->fetch_site_index().$qm.'URL='.
									$FNS->create_url($res->row['group_name'].'/'.$res->row['template_name'].'/'.$entry_id),
									$LANG->line('live_look'), '', TRUE)
								)
							);
			}
		}
		
		// -------------------------------------------
		// 'view_entry_end' hook.
		//  - Add content to end of view entry page
		//  - Added: 1.4.1
		//
			if ($EXT->active_hook('view_entry_end') === TRUE)
			{
				$r .= $EXT->call_extension('view_entry_end', $entry_id);
			}	
		//
		// -------------------------------------------
							
		$DSP->set_return_data( 
								$LANG->line('view_entry'),
								$r, 
								$LANG->line('view_entry')
							  ); 
    }
    /* END */

 
    /** --------------------------------------------
    /**  Delete Entries (confirm)
    /** --------------------------------------------*/
    // Warning message if you try to delete an entry
    //--------------------------------------------

    function delete_entries_confirm()
    { 
        global $IN, $DB, $DSP, $LANG;
        
        if ( ! $DSP->allowed_group('can_delete_self_entries') AND
             ! $DSP->allowed_group('can_delete_all_entries'))
        {
            return $DSP->no_access_message();
        }
                
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->edit_entries();
        }
        	
        $r  = $DSP->form_open(array('action' => 'C=edit'.AMP.'M=delete_entries'));
        
        $i = 0;
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
            	if ($val != '')
            	{
                	$r .= $DSP->input_hidden('delete[]', $val);
                	$i++;
                }
            }        
        }
	
        $r .= $DSP->qdiv('alertHeading', $LANG->line('delete_confirm'));
        $r .= $DSP->div('box');
        
        if ($i == 1)
            $r .= $DSP->qdiv('defaultBold', $LANG->line('delete_entry_confirm'));
        else
            $r .= $DSP->qdiv('defaultBold', $LANG->line('delete_entries_confirm'));
        		
		// if it's just one entry, let's be kind and show a title
		if (count($_POST['toggle']) == 1)
		{
			$query = $DB->query('SELECT title FROM exp_weblog_titles WHERE entry_id = "'.$DB->escape_str($_POST['toggle'][0]).'"');

			if ($query->num_rows == 1)
			{				
				$r .= $DSP->br(1).
					  $DSP->qdiv('defaultBold', str_replace('%title', $query->row['title'], $LANG->line('entry_title_with_title')));
			}
		}
		
        $r .= $DSP->br(1).
              $DSP->qdiv('alert', $LANG->line('action_can_not_be_undone')).
              $DSP->br().
              $DSP->input_submit($LANG->line('delete')).
              $DSP->div_c().
              $DSP->form_close();

        $DSP->title = $LANG->line('delete_confirm');
        $DSP->crumb = $LANG->line('delete_confirm');         
        $DSP->body  = $r;
    }
    /* END */
    
    
    
    /** --------------------------------------------
    /**  Delete Entries
    /** --------------------------------------------*/
    // Kill the specified entries
    //--------------------------------------------

    function delete_entries()
    { 
        global $IN, $DSP, $LANG, $SESS, $DB, $FNS, $STAT, $EXT, $PREFS;
        
        if ( ! $DSP->allowed_group('can_delete_self_entries') AND
             ! $DSP->allowed_group('can_delete_all_entries'))
        {
            return $DSP->no_access_message();
        }
                
        if ( ! $IN->GBL('delete', 'POST'))
        {
            return $this->edit_entries();
        }
        
		// -------------------------------------------
        // 'delete_entries_start' hook.
        //  - Take control of entry deletion script
        //
        	$edata = $EXT->call_extension('delete_entries_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
                
        $sql = 'SELECT weblog_id, author_id, entry_id FROM exp_weblog_titles WHERE (';
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val))
            {                    
                $sql .= " entry_id = '".$DB->escape_str($val)."' OR ";
            }        
        }

        $sql = substr($sql, 0, -3).')';
        
        $query = $DB->query($sql);
        
        $allowed_blogs = $FNS->fetch_assigned_weblogs();
        $authors = array();
        
        foreach ($query->result as $row)
        {
			if ($SESS->userdata['group_id'] != 1)
			{
				if ( ! in_array($row['weblog_id'], $allowed_blogs))
				{
					return $this->edit_entries();
				}
			}
			
            if ($row['author_id'] == $SESS->userdata('member_id'))
            {
                if ( ! $DSP->allowed_group('can_delete_self_entries'))
                {             
                    return $DSP->no_access_message($LANG->line('unauthorized_to_delete_self'));
                }
            }
            else
            {
                if ( ! $DSP->allowed_group('can_delete_all_entries'))
                {             
                    return $DSP->no_access_message($LANG->line('unauthorized_to_delete_others'));
                }
            }
            
            $authors[$row['entry_id']] = $row['author_id'];
        }
        
		// gather related fields, we use this later if needed
		$fquery = $DB->query("SELECT field_id FROM exp_weblog_fields WHERE field_type = 'rel'");
		
		$entries = array();

        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val))
            {
				if ( ! is_numeric($val))
					continue;
            
                $query = $DB->query("SELECT weblog_id FROM exp_weblog_titles WHERE entry_id = '".$DB->escape_str($val)."'");
            	
				if ($query->num_rows == 0)
					continue;
					
				$entries[] = $val;
					
                $weblog_id = $query->row['weblog_id'];                
            
				$DB->query("DELETE FROM exp_weblog_titles WHERE entry_id = '{$val}'");
				$DB->query("DELETE FROM exp_weblog_data WHERE entry_id = '{$val}'");
				$DB->query("DELETE FROM exp_category_posts WHERE entry_id = '{$val}'");
				$DB->query("DELETE FROM exp_trackbacks WHERE entry_id = '{$val}'");
				$DB->query("DELETE FROM exp_relationships WHERE rel_parent_id = '{$val}'");

				/** -------------------------------------
				/**  Check for silly children
				/** -------------------------------------*/
				$child_results = $DB->query("SELECT rel_id FROM exp_relationships WHERE rel_child_id = '{$val}'");
				
				if ($child_results->num_rows > 0)
				{
					// We have children, so we need to do a bit of housekeeping
					// so parent entries don't continue to try to reference them
					$cids = array();
					
					foreach ($child_results->result as $row)
					{
						$cids[] = $row['rel_id'];
					}
					
					$CIDS = "'".implode("', '", $cids)."'";
										
					foreach($fquery->result as $row)
					{
						$field = 'field_id_'.$row['field_id'];
						 $DB->query($DB->update_string('exp_weblog_data', array($field => '0'), "{$field} IN ({$CIDS})"));
					}					
					
					$DB->query("DELETE FROM exp_relationships WHERE rel_child_id = '{$val}'");	
				}
				
				$query = $DB->query("SELECT total_entries FROM exp_members WHERE member_id = '".$authors[$val]."'");

				$tot = $query->row['total_entries'];
				
				if ($tot > 0)
					$tot -= 1;

                $DB->query("UPDATE exp_members set total_entries = '".$tot."' WHERE member_id = '".$authors[$val]."'");                

                $query = $DB->query("SELECT count(*) AS count FROM exp_comments WHERE status = 'o' AND entry_id = '$val' AND author_id = '".$authors[$val]."'");

                if ($query->row['count'] > 0)
                {
                    $count = $query->row['count'];
                
                    $query = $DB->query("SELECT total_comments FROM exp_members WHERE member_id = '".$authors[$val]."'");

                    $DB->query("UPDATE exp_members set total_comments = '".($query->row['total_comments'] - $count)."' WHERE member_id = '".$authors[$val]."'");                
                }

                $DB->query("DELETE FROM exp_comments WHERE entry_id = '$val'");
                
                // -------------------------------------------
				// 'delete_entries_loop' hook.
				//  - Add additional processing for entry deletion in loop
				//  - Added: 1.4.1
				//
					$edata = $EXT->call_extension('delete_entries_loop', $val, $weblog_id);
					if ($EXT->end_script === TRUE) return;
				//
				// -------------------------------------------
                
                // Update statistics
                
                $STAT->update_weblog_stats($weblog_id);
                $STAT->update_comment_stats($weblog_id);
                $STAT->update_trackback_stats($weblog_id);
            }        
        }
        
        /** ----------------------------------------
		/**  Delete Pages Stored in Database For Entries
		/** ----------------------------------------*/
		
		if (sizeof($entries) > 0 && $PREFS->ini('site_pages') !== FALSE)
		{
			$pages = $PREFS->ini('site_pages');
			
			if (isset($pages[$PREFS->ini('site_id')]['uris']))
			{
				foreach($entries as $entry_id)
				{
					unset($pages[$PREFS->ini('site_id')]['uris'][$entry_id]);
					unset($pages[$PREFS->ini('site_id')]['templates'][$entry_id]);
				}
				
				$PREFS->core_ini['site_pages'][$PREFS->ini('site_id')] = $pages[$PREFS->ini('site_id')];
				
				$DB->query($DB->update_string('exp_sites', 
											  array('site_pages' => addslashes(serialize($pages))),
											  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));
			}
		}
		
        /** ---------------------------------
        /**  Clear caches
        /** ---------------------------------*/
        
        $FNS->clear_caching('all');
        
		// -------------------------------------------
        // 'delete_entries_end' hook.
        //  - Add additional processing for entry deletion
        //
        	$edata = $EXT->call_extension('delete_entries_end');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        /** ----------------------------------------
        /**  Return success message
        /** ----------------------------------------*/

        $message = $DSP->div('success').$LANG->line('entries_deleted').$DSP->div_c();

        return $this->edit_entries('', $message);
    }
    /* END */
     
     

    /** --------------------------------------------
    /**  File upload form
    /** --------------------------------------------*/

    function file_upload_form()
    {
        global $IN, $DSP, $LANG, $SESS, $DB, $EXT, $PREFS;
        
        // -------------------------------------------
        // 'file_upload_form_start' hook.
        //  - Allows complete rewrite of File Upload Form page.
        //
        	$edata = $EXT->call_extension('file_upload_form_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        $LANG->fetch_language_file('filebrowser');
                
        $DSP->title = $LANG->line('file_upload');
        
        $DSP->body  = $DSP->qdiv('smallLinks', NBS);
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('file_upload'));
                
        $DSP->body .= $DSP->div('box').BR;
        

        if ($SESS->userdata['group_id'] == 1)
        {            
            $query = $DB->query("SELECT id, name FROM exp_upload_prefs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n' ORDER BY name");
        }
        else
        {         	
            $sql = "SELECT id, name FROM exp_upload_prefs ";
        
			if (USER_BLOG === FALSE) 
			{
				$query = $DB->query("SELECT upload_id FROM exp_upload_no_access WHERE member_group = '".$SESS->userdata['group_id']."'");
					  
				$idx = array();
				
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{	
						$idx[] = $row['upload_id'];
					}
				}
			
				$sql .= " WHERE is_user_blog = 'n' ";
				
				if (count($idx) > 0)
				{	
					foreach ($idx as $val)
					{
						$sql .= " AND id != '".$val."' ";
					}
				}
			}
			else
			{
				$sql .= " WHERE weblog_id = '".UB_BLOG_ID."' ORDER BY name";		
			}
			
			$sql .= " AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
        
        	$query = $DB->query($sql);
        }   
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }        

        $DSP->body .= "<form method=\"post\" action=\"".BASE.AMP.'C=publish'.AMP.'M=upload_file'.AMP.'Z=1'."\" enctype=\"multipart/form-data\">\n";
        
        $DSP->body .= $DSP->input_hidden('field_group', $IN->GBL('field_group', 'GET'));

        $DSP->body .= $DSP->qdiv('', "<input type=\"file\" name=\"userfile\" size=\"20\" />".BR.BR);
        
        $DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('select_destination_dir'));
        
        $DSP->body .= $DSP->input_select_header('destination');
                                
        foreach ($query->result as $row)
        {
            $DSP->body .= $DSP->input_select_option($row['id'], $row['name']);
        }
        
		$DSP->body .= $DSP->input_select_footer();
                      

        $DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('upload')).$DSP->br(2));

        $DSP->body .= $DSP->form_close();
        
        $DSP->body .= $DSP->div_c();
        
        /** -------------------------------
        /**  File Browser
        /** -------------------------------*/
        
        $DSP->body .= $DSP->qdiv('', BR.BR);

        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('file_browser'));        
        $DSP->body .= $DSP->div('box');
        
        $DSP->body .= "<form method=\"post\" action=\"".BASE.AMP.'C=publish'.AMP.'M=file_browser'.AMP.'Z=1'."\" enctype=\"multipart/form-data\">\n";
        
        $DSP->body .= $DSP->input_hidden('field_group', $IN->GBL('field_group', 'GET'));
        
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $LANG->line('select_destination_dir'));
        
        $DSP->body .= $DSP->input_select_header('directory');
                                
        foreach ($query->result as $row)
        {
            $DSP->body .= $DSP->input_select_option($row['id'], $row['name']);
        }
        
		$DSP->body .= $DSP->input_select_footer();
                      

        $DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('view')));

        $DSP->body .= $DSP->form_close();
        $DSP->body .= BR.BR.$DSP->div_c();

        $DSP->body .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="JavaScript:window.close();">'.$LANG->line('close_window').'</a></div>');
        
        /** ---------------------------
        /**  End File Browser
        /** ---------------------------*/
    }
    /* END */



    /** ----------------------------------
    /**  Upload File
    /** ----------------------------------*/

    function upload_file()
    {
        global $IN, $DSP, $DB, $LANG, $SESS;  
        
        $id = $IN->GBL('destination');
        $field_group = $IN->GBL('field_group');
                
        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows == 0)
        {
            return;
        }
        
        if ($SESS->userdata['group_id'] != 1)
        {
            $safety = $DB->query("SELECT count(*) AS count FROM exp_upload_no_access WHERE upload_id = '".$query->row['id']."' AND upload_loc = 'cp' AND member_group = '".$SESS->userdata['group_id']."'");
        
            if ($safety->row['count'] != 0)
            {
            	exit('no access');
                return $DSP->no_access_message();
            }
        }
            
        require PATH_CORE.'core.upload'.EXT;
        
        $UP = new Upload();
       
        if ($UP->set_upload_path($query->row['server_path']) !== TRUE)
        {
        	return $UP->show_error();
        }
        
        $UP->set_max_width($query->row['max_width']);
        $UP->set_max_height($query->row['max_height']);
        $UP->set_max_filesize($query->row['max_size']);
        $UP->set_allowed_types(($SESS->userdata['group_id'] == 1) ? 'all' : $query->row['allowed_types']);
                        
        if ( ! $UP->upload_file())
        {
        	return $UP->show_error();
        }
        
		global $UL; $UL = $UP;
                
        if ($UL->file_exists == TRUE)
        {        
           return $this->file_exists_warning();
        }
        
		$this->finalize_uploaded_file(
										array(
												'id'			=> $id,
												'field_group'	=> $field_group,
												'file_name'		=> $UP->file_name,
												'is_image'		=> $UP->is_image,
												'step'			=> 1
											)			
									);			
    }
    /* END */
    
    	

	/** ----------------------------------
    /**  File Browser
    /** ----------------------------------*/

    function file_browser()
    {
        global $IN, $DSP, $DB, $LANG, $SESS, $EXT;
        
		// -------------------------------------------
        // 'file_browser_start' hook.
        //  - Allows complete rewrite of File Browser page.
        //
        	$edata = $EXT->call_extension('file_browser_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        $LANG->fetch_language_file('filebrowser');
        
        $id = $IN->GBL('directory');
        $field_group = $IN->GBL('field_group');
        
        $DSP->title = $LANG->line('file_browser');  

        $r = $DSP->qdiv('tableHeading', $LANG->line('file_browser'));        
        $r .= $DSP->div('box');
                
        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows == 0)
        {
            return;
        }
        
        if ($SESS->userdata['group_id'] != 1)
        {
            $safety = $DB->query("SELECT count(*) AS count FROM exp_upload_no_access WHERE upload_id = '".$query->row['id']."' AND upload_loc = 'cp' AND member_group = '".$SESS->userdata['group_id']."'");
        
            if ($safety->row['count'] != 0)
            {
            	exit('no access');
                return $DSP->no_access_message();
            }
        }
           
        if (! class_exists('File_Browser'))
        { 
        	require PATH_CP.'cp.filebrowser'.EXT;
        }
        
        $FP = new File_Browser();
       
        $FP->set_upload_path($query->row['server_path']);
        $directory_url 		= $query->row['url'];
        $pre_format 		= addslashes($query->row['pre_format']);
        $post_format 		= addslashes($query->row['post_format']);
        $properties 		= ($query->row['properties']  != '') ? " ".addslashes($query->row['properties']) : "";
        $file_pre_format 	= addslashes($query->row['file_pre_format']);
        $file_post_format 	= addslashes($query->row['file_post_format']);
        $file_properties 	= ($query->row['file_properties']  != '') ? " ".addslashes($query->row['file_properties']) : "";
        
        $FP->create_filelist();
        
        if (sizeof($FP->filelist) == 0)
        {
        	return $DSP->error_message($LANG->line('fp_no_files'));
        }
        
        $r .= <<<EOT

<script type="text/javascript">
<!--

var item=new Array();
var width=new Array();
var height=new Array();

EOT;
		foreach ($FP->filelist as $key => $file_info)
    	{
    		$r .= "item[$key] = '".addslashes($file_info['name'])."';\n";
    		
            if ($file_info['type'] == 'image')
            {
            	$r .= "width[$key] = ".$file_info['width'].";\n";
            	$r .= "height[$key] = ".$file_info['height'].";\n";
            }
        }
	
	$r .= <<<EOT

function showimage()
{

	var loc_w = 350;
	var loc_h = 0;
	for (var i=0; i < document.browser.elements['file[]'].length; i++)
	{
		if (document.browser.elements['file[]'].options[i].selected == true)
		{
			var t = document.browser.elements['file[]'].options[i].value;
			
			if (width[t])
			{
				var loc = '{$directory_url}'+item[t];
				window.open(loc,'Image'+t,'width='+width[t]+',height='+height[t]+',screenX='+loc_w+',screenY='+loc_h+',top='+loc_h+',left='+loc_w+',toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1');
				loc_w = loc_w + width[t];
				loc_h = loc_h + 100;
			}
		}
	}
	
	return false;
}

function fileplacer()
{
	var done = 'n';
	var file = '';
	var insert = '';
	var field_value = 'field_id_1';
	var pre_format  = '{$pre_format}';
	var post_format = '{$post_format}';
	var properties  = '{$properties}';
	var file_pre_format  = '{$file_pre_format}';
	var file_post_format = '{$file_post_format}';
	var file_properties  = '{$file_properties}';
	
	for (var i=0; i < document.browser.field.length; i++)
	{
		if (document.browser.field.options[i].selected == true)
		{
			field_value = document.browser.field.options[i].value;
		}
	}
	
	for (var i=0; i < document.browser.elements['file[]'].length; i++)
	{
		if (document.browser.elements['file[]'].options[i].selected == true)
		{
			done = 'n';
			var t = document.browser.elements['file[]'].options[i].value;
			
			if (width[t])
			{
				var file = item[t];
				file = '<img src="{filedir_{$id}}' + file + '"'+ properties + ' width="'+width[t]+'" height="'+height[t]+'" />';
				var input = pre_format + file + post_format
				opener.document.getElementById('entryform').elements[field_value].value += input+' ';
				done = 'y';
			}
			
			if (done == 'n')
			{
				var file = item[t];
				file = '<a href="{filedir_{$id}}' + file + '"'+ file_properties + '>'+file+'</a>';
				var input = file_pre_format + file + file_post_format
				opener.document.getElementById('entryform').elements[field_value].value += input;
			}
		}
	}
	
	return false;
}


function urlplacer()
{
	var field_value  = 'field_id_1';
	var insert_value = '';
	
	for (var i=0; i < document.browser.field.length; i++)
	{
		if (document.browser.field.options[i].selected == true)
		{
			field_value = document.browser.field.options[i].value;
		}
	}
	
	for (var i=0; i < document.browser.elements['file[]'].length; i++)
	{
		if (document.browser.elements['file[]'].options[i].selected == true)
		{
			var t = document.browser.elements['file[]'].options[i].value;
			
			insert_value += '{filedir_{$id}}' + item[t] + ' ';
		}
	}
	
	if (insert_value.length > 0)
	{
		opener.document.getElementById('entryform').elements[field_value].value += insert_value.slice(0, insert_value.length - 1);
	}
	
	return false;
}



//-->
</script>

EOT;
        
        
        
        $r .= "<form method=\"post\" name='browser' action=\"".BASE.AMP.'C=publish'.AMP.'M=file_browser'."\" enctype=\"multipart/form-data\">\n";
        
        $r .= $DSP->input_hidden('directory', $id);
        
        $r .= $DSP->qdiv('itemTitle', $LANG->line('fb_select_files'));
        
        $r .= $DSP->div('itemWrapper').$DSP->input_select_header('file[]','y',10);
        
        foreach ($FP->filelist as $key => $file_info)
        {
        	$display_name = (isset($file_info['type']) && $file_info['type'] == 'image') ? $file_info['name'].NBS.NBS.NBS : $file_info['name'].'*'.NBS.NBS.NBS;
            $r .= $DSP->input_select_option($key, $display_name);
        }
        
		$r .= $DSP->input_select_footer().$DSP->div_c();
		
		
        $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = '".$field_group."' AND field_type NOT IN ('date', 'rel', 'select') ORDER BY field_order");
               
        $r .= $DSP->div('itemWrapper').$DSP->qdiv('itemTitle', $LANG->line('fb_select_field'));
               
        $r .= $DSP->input_select_header('field');        
        
        foreach ($query->result as $row)
        {        
            $r .= $DSP->qdiv('', $DSP->input_select_option('field_id_'.$row['field_id'], $row['field_label']));
        }
        
        $r .= $DSP->input_select_footer().$DSP->div_c();
        
        $view_text = (sizeof($FP->filelist) > 1) ? $LANG->line('fb_view_images') : $LANG->line('fb_view_image');
        $insert_text = (sizeof($FP->filelist) > 1) ? $LANG->line('fb_insert_links') : $LANG->line('fb_insert_link');
        $url_text = (sizeof($FP->filelist) > 1) ? $LANG->line('fb_insert_urls') : $LANG->line('fb_insert_url');
		
		$r .= $DSP->qdiv('', BR.$DSP->input_submit($view_text,'submit',' onclick="return showimage(); return false;"')
			  .NBS.NBS
			  .$DSP->input_submit($insert_text, 'submit',' onclick="return fileplacer(); return false;"')
			  .NBS.NBS
			  .$DSP->input_submit($url_text, 'submit',' onclick="return urlplacer(); return false;"'));

        $r .= $DSP->form_close();
        $r .= BR.BR.$DSP->div_c();
        $r .= $DSP->qdiv('defaultCenter',BR.$LANG->line('fb_non_images'));
        $r .= $DSP->qdiv('defaultCenter', BR.'<a href="JavaScript:window.close();">'.$LANG->line('close_window').'</a>');
		
		$DSP->body = $r;	
    }
    /* END */



    /** --------------------------------------------
    /**  File Exists Warning message
    /** --------------------------------------------*/

    function file_exists_warning()
    {
        global $IN, $DSP, $LANG, $UL;
                
        $field_group = $IN->GBL('field_group');
        
        $original_file	= (isset($_FILES['userfile']['name'])) ? $_FILES['userfile']['name'] : $_POST['original_file'];
        $file_name		= (isset($_POST['file_name'])) ? $_POST['file_name'] : $_FILES['userfile']['name'];
        $destination		= (isset($_POST['id'])) ? $_POST['id'] : $_POST['destination'];
        $is_image		= (isset($_POST['is_image'])) ? $_POST['is_image'] : $UL->is_image;
        $width			= (isset($_POST['width'])) ? $_POST['width'] : $UL->width;
        $height			= (isset($_POST['height'])) ? $_POST['height'] : $UL->height;
        $imgtype			= (isset($_POST['imgtype'])) ? $_POST['imgtype'] : $UL->imgtype;


        $DSP->title = $LANG->line('file_upload');
        
		$DSP->body .= $DSP->qdiv('smallLinks', NBS);
        $DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('warning'));
		$DSP->body .= $DSP->div('box');
        
        $DSP->body .= $DSP->qdiv('highlight', $LANG->line('file_exists'));
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $LANG->line('overwrite_instructions'));

        $DSP->body .= $DSP->form_open(array('action' => 'C=publish'.AMP.'M=replace_file'.AMP.'Z=1'));

        $DSP->body .= $DSP->input_text('file_name', $file_name, '40', '100', 'input', '200px');
        
        $DSP->body .= $DSP->input_hidden('original_file', $original_file);
        $DSP->body .= $DSP->input_hidden('temp_file_name', $file_name);
        $DSP->body .= $DSP->input_hidden('field_group', $field_group);
        $DSP->body .= $DSP->input_hidden('is_image', $is_image);
        $DSP->body .= $DSP->input_hidden('width', $width);
        $DSP->body .= $DSP->input_hidden('height', $height);
        $DSP->body .= $DSP->input_hidden('imgtype', $imgtype);
        $DSP->body .= $DSP->input_hidden('id', $destination);

        $DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('submit')));

        $DSP->body .= $DSP->form_close();
        $DSP->body .= BR.$DSP->div_c();
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="JavaScript:window.close();">'.$LANG->line('close_window').'</a></div>');
    }
    /* END */



    /** -----------------------------------
    /**  Overwrite file
    /** -----------------------------------*/

    function replace_file()
    {
        global $IN, $DSP, $LANG, $DB, $SESS; 
        
        $id          	= $IN->GBL('id'); 
        $file_name   	= $IN->GBL('file_name');  
        $temp_file_name	= $IN->GBL('temp_file_name');  
        $is_image    	= $IN->GBL('is_image');
        $field_group 	= $IN->GBL('field_group');
        
        require PATH_CORE.'core.upload'.EXT;
        
        $UP = new Upload();

		if ($UP->remove_spaces == 1)
        {
            $file_name = preg_replace("/\s+/", "_", $file_name);
            $temp_file_name = preg_replace("/\s+/", "_", $temp_file_name);
        }

		$query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($id)."'");
		
		if ($SESS->userdata['group_id'] != 1)
        {
            $safety = $DB->query("SELECT count(*) AS count FROM exp_upload_no_access 
            					  WHERE upload_id = '".$DB->escape_str($query->row['id'])."' 
            					  AND upload_loc = 'cp' 
            					  AND member_group = '".$DB->escape_str($SESS->userdata['group_id'])."'");
        
            if ($safety->row['count'] != 0)
            {
                return $DSP->no_access_message();
            }
        }
       
        if ($UP->set_upload_path($query->row['server_path']) !== TRUE)
        {
        	return $UP->show_error();
        }
        
        $UP->set_max_width($query->row['max_width']);
        $UP->set_max_height($query->row['max_height']);
        $UP->set_max_filesize($query->row['max_size']);
        $UP->set_allowed_types(($SESS->userdata['group_id'] == 1) ? 'all' : $query->row['allowed_types']);
		
		$UP->set_upload_path($query->row['server_path']);
        
        if ($temp_file_name != $file_name)
        {
			if (file_exists($query->row['server_path'].$file_name))
			{
				return $this->file_exists_warning();
        	}
        }
                
        if ( ! $UP->file_overwrite() === TRUE)
        {
			return $UP->show_error();
     	}   
        
		$this->finalize_uploaded_file(
										array(
												'id'			=> $id,
												'field_group'	=> $field_group,
												'file_name'		=> $file_name,
												'is_image'		=> $is_image,
												'step'			=> 1
											)			
									);			
    }

    /* END */
    


    /** --------------------------------------------
    /**  Image options form
    /** --------------------------------------------*/

    function image_options_form()
    {
        global $IN, $DSP, $LANG, $DB, $UL;
                
        $id				= (isset($_POST['id'])) ? $_POST['id'] : $_POST['destination'];
        $file_name		= (isset($_POST['file_name'])) ? $_POST['file_name'] : $_FILES['userfile']['name'];
        $is_image		= (isset($_POST['is_image'])) ? $_POST['is_image'] : $UL->is_image;
        $width			= (isset($_POST['width'])) ? $_POST['width'] : $UL->width;
        $height			= (isset($_POST['height'])) ? $_POST['height'] : $UL->height;
        $imgtype			= (isset($_POST['imgtype'])) ? $_POST['imgtype'] : $UL->imgtype;  // 2 = jpg  3 = png
        $field_group 	= $IN->GBL('field_group');
        
        
        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($id)."'");        
        $max_w = ($query->row['max_width'] == '')  ? '1000' : $query->row['max_width'];
        $max_h = ($query->row['max_height'] == '') ? '1000' : $query->row['max_height'];
        
        $max_w = str_replace(array(',', '.'), array('', ''), $max_w);
        $max_h = str_replace(array(',', '.'), array('', ''), $max_h);
        
                
        $DSP->title = $LANG->line('file_upload');        
                                     
        ob_start();
        ?>
		<script type="text/javascript">
		
		function changeDimUnits(f, side)
		{
			var unit = (side == "w")? f.width_unit : f.height_unit;
			var orig = (side == "w")? f.width_orig : f.height_orig;
			var curr = (side == "w")? f.width : f.height;
			
			curr.value = (unit.options[unit.selectedIndex].value == "pixels") ? Math.round(orig.value * curr.value / 100.0) : Math.round((curr.value / orig.value) * 100.0);
			
			return;
		}
		
		function changeDimValue(f, side)
		{
			var max 	= (side == "h") ? <?php echo $max_w; ?>	: <?php echo $max_h; ?>;
			var max_alt	= (side == "h") ? <?php echo $max_h; ?>	: <?php echo $max_w; ?>;			
			var unit	= (side == "w") ? f.width_unit	: f.height_unit;
			var orig	= (side == "w") ? f.width_orig	: f.height_orig;
			var curr	= (side == "w") ? f.width 		: f.height;
			var t_unit	= (side == "h") ? f.width_unit	: f.height_unit;
			var t_orig	= (side == "h") ? f.width_orig	: f.height_orig;
			var t_curr	= (side == "h") ? f.width		: f.height;
			
			var ratio	= (unit.options[unit.selectedIndex].value == "pixels") ? curr.value/orig.value : curr.value/100;
			var res = (t_unit.value == "pixels") ? Math.floor(ratio * t_orig.value) : Math.round(ratio * 100);

			var res_alt = (unit.value == "pixels") ? Math.floor(ratio * orig.value) : Math.round(ratio * 100);		
				
			if (res > max || res_alt > max_alt)
			{
				if (f.constrain.checked)
					t_curr.value = t_orig.value;
				if (f.constrain.checked || res_alt > max_alt)
				curr.value	 = (unit.options[unit.selectedIndex].value == "pixels") ? 
								Math.min(curr.value, orig.value) : curr.value = Math.min(curr.value, 100);
			}
			else
			{
				if (f.constrain.checked)
					t_curr.value = res;
			}
						
			return;
		}
								
		</script>
		<?php
		
        $DSP->body .= ob_get_contents();
                
        ob_end_clean(); 

        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('resize_image'));        
        $DSP->body .= $DSP->div('box');
        
        $DSP->body .= $DSP->qdiv('', $LANG->line('thumb_instructions').NBS.NBS.$LANG->line('close_for_no_change'));        
                        
        $DSP->body .= $DSP->form_open(
        								array(
        										'action'	=> 'C=publish'.AMP.'M=create_thumb'.AMP.'Z=1', 
        										'name'		=> 'fileOptions',
        										'id'		=> 'fileOptions',
        									),
        								
        								array(
        										'field_group' 	=> $field_group,
        										'is_image'		=> $is_image,
        										'imgtype'		=> $imgtype,
        										'file_name'		=> $file_name,
        										'id'			=> $id,
        										'width_orig'	=> $width,
        										'height_orig'	=> $height
        									)
        							);
        
       		
		$DSP->body .= BR."<fieldset class='thumb' name=\"thumb_settings\" id=\"thumb_settings\" >";
					
		$DSP->body .= "<legend>".$LANG->line('thumb_settings')."</legend>";
		
		$DSP->body .= $DSP->div('thumbPad');

		$DSP->body .= $DSP->table('', '6', '0', '');
		  
		$DSP->body .= $DSP->table_qrow( 'none', 
								array(
										NBS.$LANG->line('width'),
										$DSP->input_text('width', $width, '4', '4', 'input', '40px', " onchange=\"changeDimValue(this.form, 'w');\" "),
  
										"<select name='width_unit' class='select' onchange=\"changeDimUnits(this.form, 'w')\"  >".
										$DSP->input_select_option('pixels', $LANG->line('pixels'), 1).
										$DSP->input_select_option('percent',$LANG->line('percent')).
										$DSP->input_select_footer()
									  )
								);
			  
		
		$DSP->body .= $DSP->table_qrow( 'none', 
								array(
										$LANG->line('height'),
										$DSP->input_text('height', $height, '4', '4', 'input', '40px', " onchange=\"changeDimValue(this.form, 'h');\" "),
									  
										"<select name='height_unit' class='select' onchange=\"changeDimUnits(this.form, 'h')\"  >".
										$DSP->input_select_option('pixels', $LANG->line('pixels'), 1).
										$DSP->input_select_option('percent', $LANG->line('percent')).
										$DSP->input_select_footer()
									  )
								);		
		
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('none', '', '3');
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('constrain', '1', 1).NBS.$LANG->line('constrain_proportions'));
		
		$DSP->body .= $DSP->qdiv('', BR.$DSP->input_radio('source', 'copy', 1).NBS.$LANG->line('create_thumb_copy'));
		$DSP->body .= $DSP->qdiv('', $DSP->input_radio('source', 'orig').NBS.$LANG->line('resize_original'));
		
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
		$DSP->body .= $DSP->table_c();
				
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= "</fieldset>";	
		
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('resize_image')));
		$DSP->body .= BR.$DSP->div_c();
				        
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="JavaScript:window.close();">'.$LANG->line('close_window').'</a></div>');
        $DSP->body .= $DSP->form_close();        
    }
    /* END */



    /** -----------------------------------
    /**  Create image thumbnail
    /** -----------------------------------*/

    function create_thumb()
    {
        global $IN, $DSP, $LANG, $PREFS, $LANG, $DB;
        
        if ($_POST['width_unit'] == 'percent')
        {
        	$_POST['width'] = ceil($_POST['width']/100 * $_POST['width_orig']);
        }
        
        if ($_POST['height_unit'] == 'percent')
        {
        	$_POST['height'] = ceil($_POST['height']/100 * $_POST['height_orig']);
        }
        
        foreach ($_POST as $key => $val)
        {
        	$$key = $val;
        }
        
        //print_r($_POST); exit;
        
        if ($width == $width_orig AND $height == $height_orig)
        {
			return $DSP->error_message($LANG->line('image_size_not_different'));
        }
                
        if ($width != $width_orig OR $height_orig != $height)
        {
			$query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($id)."'");
	
			$thumb_prefix = ($PREFS->ini('thumbnail_prefix') == '') ? 'thumb' : $PREFS->ini('thumbnail_prefix');

			/** --------------------------------
			/**  Invoke the Image Lib Class
			/** --------------------------------*/
	
			require PATH_CORE.'core.image_lib'.EXT;
			$IM = new Image_lib();
					
			/** --------------------------------
			/**  Resize the image
			/** --------------------------------*/
						
			$res = $IM->set_properties(			
										array(
												'resize_protocol'	=> $PREFS->ini('image_resize_protocol'),
												'libpath'			=> $PREFS->ini('image_library_path'),
												'thumb_prefix'		=> ($source == 'orig') ? '' : $thumb_prefix,
												'file_path'			=> $query->row['server_path'],
												'file_name'			=> $file_name,
												'dst_width'			=> $width,
												'dst_height'		=> $height,
												'maintain_ratio'	=> FALSE
												)
										);
										
			if ($res === FALSE OR ! $IM->image_resize())
			{
				return $IM->show_error();
			}
							
		}
		
		$this->finalize_uploaded_file(
										array(
												'id'			=> $id,
												'field_group'	=> $field_group,
												'orig_name'		=> $file_name,
												'file_name'		=> $IM->thumb_name,
												'is_image'		=> 1,
												'step'			=> 2,
												'source'		=> $source
											)			
									);			
	}
	/* END */



    /** ---------------------------------------
    /**  Finalize Uploaded File
    /** ---------------------------------------*/

    function finalize_uploaded_file($data)
    {
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        // Fetch upload preferences
                
        $query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = '".$DB->escape_str($data['id'])."'");
        
        if ($data['is_image'] == 1)
        {
       		$properties = ($query->row['properties']  != '') ? " ".addslashes($query->row['properties']) : "";
        }
        else
        {
        	$properties = ($query->row['file_properties']  != '') ? " ".addslashes($query->row['file_properties']) : "";
        }
                
        $popup_link = '';
        $popup_thumb = '';
		$pre_format = addslashes($query->row['pre_format']);
		$post_format = addslashes($query->row['post_format']);
		$file_pre_format = addslashes($query->row['file_pre_format']);
		$file_post_format = addslashes($query->row['file_post_format']);
		$file_url = '{filedir_'.$data['id'].'}'.$data['file_name'];
		$props = ($data['is_image'] == 1) ? $pre_format : $file_pre_format;
                        
        if ($data['is_image'] == 1)
        {        
            $imgsrc = '<img src="'.$file_url.'"'.$properties;
            
            $wh = '';
            
            if (function_exists('getimagesize')) 
            {
                $imgdim = @getimagesize($query->row['server_path'].$data['file_name']);
                
                if (is_array($imgdim)) 
                {
                    $imgsrc .= " width=\"".$imgdim['0']."\" height=\"".$imgdim['1']."\"";
                    $wh = "width=".($imgdim['0']+15).",height=".($imgdim['1']+15).",";
                }
                
                if (isset($data['orig_name']) AND $data['orig_name'] != '')
                {
					$imgdim = @getimagesize($query->row['server_path'].$data['orig_name']);
					
					if (is_array($imgdim)) 
					{
						$wh = "width=".($imgdim['0']+15).",height=".($imgdim['1']+15).",";
					}                
                }
                
            }
            
            $imgsrc .= " />";
            
			$filename = (isset($data['orig_name']) AND $data['orig_name'] != '') ? $data['orig_name'] : $data['file_name'];
            
			$eh = "onclick=\"window.open(\'{filedir_".$data['id']."}".$filename."\',\'popup\',\'".$wh."scrollbars=no,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0\'); return false\"";
            
            $popup_link = $props."<a href=\"{filedir_".$data['id']."}".$filename."\" $eh>".$filename."</a>".$post_format;
            $popup_thumb = $props."<a href=\"{filedir_".$data['id']."}".$filename."\" $eh>".$imgsrc."</a>".$post_format;
        
        	$props .= $imgsrc;
        }
        else
        {
            $props .= '<a href="'.$file_url.'"'.$properties.'>'.$data['file_name'].'</a>';
        }
        
        $props .= ($data['is_image'] == 1) ? $post_format : $file_post_format;
        
        
        $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = '".$data['field_group']."' AND field_type NOT IN ('date', 'rel', 'select') ORDER BY field_order");
        
        if ($query->num_rows == 0)
        {
            return $DSP->error_message($LANG->line('no_entry_fields'));        
        }
                
        ob_start();
        
        ?>     
        
        <script type="text/javascript"> 
        <!--

        function fileplacer() 
        {
        	if (document.upload.style[1].checked)
        	{
        		var file = '<?php echo $file_url; ?>';
        	}
            else if (document.upload.nonimage.value == 'yes') 
            {
				var file = '<?php echo $props; ?>';
            }
			else
			{
				if (document.upload.style[0].checked) 
				{
					var file = '<?php echo $props; ?>';
				}
				else if (document.upload.style[2].checked) 
				{
					var file = '<?php echo $popup_link; ?>';
				}
				else
				{
					var file = '<?php echo $popup_thumb; ?>';
				}
			}
        
        <?php
        
        $n = 0;
        
        foreach ($query->result as $row)
        {
        ?>
            if (document.upload.which[<?php echo $n; ?>].selected) 
            {
                opener.document.getElementById('entryform').field_id_<?php echo $row['field_id']; ?>.value += file;
            }
        <?php
            
            $n++;
         }
         ?>
         
         return false;
        }
        
        //-->
        </script>
        
        <?php

        $javascript = ob_get_contents();
        
        ob_end_clean();
        
        
        $DSP->title = $LANG->line('file_upload');
        
        $DSP->body = $javascript;
                
		$DSP->body .= $DSP->div('box');
        
        if ($data['step'] == 1)
        {
			$DSP->body .= $DSP->div('itemWrapper');
			$DSP->body .= $DSP->qspan('success', $LANG->line('file_uploaded').NBS);
			$DSP->body .= $DSP->qspan('defaultBold', $data['file_name']);
			$DSP->body .= $DSP->div_c();
        }
        else
        {
        	if (isset($data['source']) AND $data['source'] == 'copy')
        		$DSP->body .= $DSP->qdiv('success', $LANG->line('thumbnail_created'));
        	else
        		$DSP->body .= $DSP->qdiv('success', $LANG->line('image_resized'));
        }
        
		$DSP->body .= $DSP->div_c();
                
               	
		if ($data['step'] == 1 AND $data['is_image'] == 1 AND $PREFS->ini('enable_image_resizing') == 'y')
		{
			$DSP->body .= "<form name='upload' method='post' action='".BASE.AMP.'C=publish'.AMP.'M=image_options'.AMP.'Z=1'."' >";  

			global $UL;
						
			$width		= (isset($_POST['width'])) ? $_POST['width'] : $UL->width;
			$height		= (isset($_POST['height'])) ? $_POST['height'] : $UL->height;
			$imgtype	= (isset($_POST['imgtype'])) ? $_POST['imgtype'] : $UL->imgtype;  // 2 = jpg  3 = png
			
			$DSP->body .= $DSP->input_hidden('id', $data['id']);
			$DSP->body .= $DSP->input_hidden('field_group', $data['field_group']);
			$DSP->body .= $DSP->input_hidden('is_image', $data['is_image']);
			$DSP->body .= $DSP->input_hidden('file_name', $data['file_name']);
			$DSP->body .= $DSP->input_hidden('width', $width);
			$DSP->body .= $DSP->input_hidden('height', $height);
			$DSP->body .= $DSP->input_hidden('imgtype', $imgtype);
			
			$DSP->body .= $DSP->qdiv('smallLinks', NBS);
			$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('resize_image'));
			$DSP->body .= $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('thumb_instructions'));
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('thumb_info')));
        	$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('resize_image')));
			$DSP->body .= $DSP->qdiv('smallLinks', '');        	
			$DSP->body .= $DSP->div_c();
		}
		else
		{
			$DSP->body .= "<form name='upload' method='post' action='JavaScript:window.close()' >";  
		}
		
		$DSP->body .= $DSP->qdiv('smallLinks', NBS);
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('select_action'));
		$DSP->body .= $DSP->div('box');
	        
        if ($data['is_image'] == 1)
        {
			$DSP->body .= $DSP->input_hidden('nonimage', 'no');
			$DSP->body .= "<fieldset class='thumb' name=\"thumb_settings\" id=\"thumb_settings\" >";
			$DSP->body .= "<legend>&nbsp;<b>".$LANG->line('file_type')."</b>&nbsp;</legend>";
			
			$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'embed',  1).NBS.$LANG->line('embedded'));
			$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'url',  '').NBS.$LANG->line('url_only'));
			$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'popuplink', '').NBS.$LANG->line('popup_link'));
			
			if (isset($_POST['source']) AND $_POST['source'] == 'copy')
			{
				$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'popupthumb', '').NBS.$LANG->line('popup_thumb'));
			}
			
			$DSP->body .= "</fieldset>";
        }
        else
        {
			$DSP->body .= $DSP->input_hidden('nonimage', 'yes');
			
			$DSP->body .= "<fieldset class='thumb' name=\"thumb_settings\" id=\"thumb_settings\" >";
			$DSP->body .= "<legend>&nbsp;<b>".$LANG->line('file_type')."</b>&nbsp;</legend>";
			
			$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'embed',  1).NBS.$LANG->line('embedded'));
			$DSP->body .= $DSP->qdiv('', $DSP->input_radio('style', 'url',  '').NBS.$LANG->line('url_only'));
			$DSP->body .= "</fieldset>";
        }
        
		$DSP->body .= BR."<fieldset class='thumb' name=\"thumb_settings\" id=\"thumb_settings\" >";
		$DSP->body .= "<legend>&nbsp;<b>".$LANG->line('image_location')."</b>&nbsp;</legend>";
        
        $i = 1;
        
		$DSP->body .= $DSP->input_select_header('which');
		
        foreach ($query->result as $row)
        {        
			$DSP->body .= $DSP->input_select_option('field_id_'.$row['field_id'], $row['field_label'], ($i == 1) ? 1 : 0);	
            $i++;
        }
            
		$DSP->body .= $DSP->input_select_footer();
                
		$DSP->body .= "</fieldset>";
		
		$line = ($data['is_image'] == 1) ? 'place_image' : 'place_file';
      
		$DSP->body .= $DSP->div('itemWrapper');        	
        $DSP->body .= BR."<input type='submit'  value='".$LANG->line($line)."' onclick='return fileplacer();' class='submit' />";
        $DSP->body .= NBS.NBS.NBS."<input type='submit'  value='".$LANG->line($line.'_close')."' onclick='fileplacer();window.close();' class='submit' />";
		$DSP->body .= $DSP->div_c();
      
		$DSP->body .= $DSP->qdiv('smallLinks', '');        	
		$DSP->body .= $DSP->div_c();
		
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="JavaScript:window.close();">'.$LANG->line('close_window').'</a></div>');
      	$DSP->body .= $DSP->form_close();
    }
    /* END */

  
    /** ---------------------------------------------
    /**  Fetch HTML Glossary
    /** ----------------------------------------------*/

    function fetch_glossary($field_id)
    {
    	global $DSP, $LANG;
    	
		$r = '';
    	
    	if (count($this->glossary) == 0)
    	{
			$is_glossary = TRUE;

			if ( ! @include_once(PATH_LIB.'glossary.php'))
			{
				$is_glossary = FALSE;
			}
			
			if ( ! isset($glossary) OR ! is_array($glossary))
			{
				$is_glossary = FALSE;
			}
			
			if ($is_glossary == FALSE)
			{
				$r .= '<div class="markupWrapper">';
				$r .= $DSP->qdiv('highlight', $LANG->line('no_glossary'));
				$r .= '</div>';
				
				return $r;
			}
			
			$this->glossary = $glossary;
    	}

		$ckey	= 0;    	
    	$rows	= count($this->glossary);
    	$crow	= 0;
 
    	$td_width = round(100/$rows);
    	
		$r .= "<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' width='99%'><tr>";	
		
		foreach ($this->glossary as $key => $val)
		{
			$end	= FALSE;
			$end2	= FALSE;
			if ($ckey != $key)
			{
				$r .= '<td class="publishItemWrapper" width="'.$td_width.'%" valign="top">';
				$ckey = $key;
				$end = TRUE;
				
				$crow++;
				if ($crow < $rows)
				{
					$r .= '<div class="clusterLineR">';
					$end2 = TRUE;
				}								
			}
		
			foreach ($val as $k => $v)
			{			
				$link = "onclick='glossaryInsert(this, \"".$field_id."\", \"".htmlspecialchars(addslashes($v['1']))."\")'";						
				$line = ( ! $LANG->line($v['0'])) ?  ucwords(str_replace(' ', '&nbsp;', str_replace('_', ' ', $v['0']))) : $LANG->line($v['0']);

				$r .= $DSP->qdiv('publishSmPad', $DSP->qdiv('lightLinks', '<a href="javascript:void(0);" '.$link.'>'.$line.'</a>'));
			}
			
			if ($end == TRUE)
			{
				if ($end2 == TRUE)
				{
					$r .= '</div>';
					$end2 = FALSE;
				}
			
				$r .= '</td>';
				$end = FALSE;
			}		
		}
		
		$r .= '</tr></table>';

    	return $r;    	
	}
	/* END */
	

    /** ---------------------------------------------
    /**  Fetch Emoticons
    /** ----------------------------------------------*/

    function fetch_emoticons($field_id)
    {
        global $IN, $DSP, $PREFS, $LANG;
          
		if ( ! is_file(PATH_MOD.'emoticon/emoticons'.EXT))
        {
			return $DSP->qdiv('highlight', BR.$LANG->line('no_smileys'));
        }

		if ($this->smileys === FALSE OR count($this->smileys) == 0)
		{
			include_once PATH_MOD.'emoticon/emoticons'.EXT;
						
			if ( ! isset($smileys) OR ! is_array($smileys))
			{
				return $DSP->qdiv('highlight', BR.$LANG->line('no_smileys'));
			}
			
			$this->smileys = $smileys;
		}
        
        $path = $PREFS->ini('emoticon_path', 1);
               
        $r = $DSP->table('', '0', '4', '100%');
        
        $i = 1;
        
        $dups = array();
        
        foreach ($this->smileys as $key => $val)
        {
            if ($i == 1)
            {
                $r .= "<tr>\n";                
            }
            
            if (in_array($this->smileys[$key]['0'], $dups))
            	continue;
            
            $r .= "<td><a href=\"#\" onclick=\"return add_smiley('".$key."', '".$field_id."');\"><img src=\"".$path.$this->smileys[$key]['0']."\" width=\"".$this->smileys[$key]['1']."\" height=\"".$this->smileys[$key]['2']."\" title=\"".$this->smileys[$key]['3']."\" alt=\"".$this->smileys[$key]['3']."\" border=\"0\" /></a></td>\n";

			$dups[] = $this->smileys[$key]['0'];

            if ($i == 8)
            {
                $r .= "</tr>\n";                
                
                $i = 1;
            }
            else
            {
                $i++;
            }      
        }
        
        $r = rtrim($r);
                
        if (substr($r, -5) != "</tr>")
        {
            $r .= "</tr>\n";
        }
        
        $r .= $DSP->table_c();

		return $r;
    }
    /* END */
  

	/** ---------------------------------------
    /**  View trackbacks
    /** ---------------------------------------*/

    function view_trackbacks($weblog_id = '', $entry_id = '', $message = '')
    {
    	global $EXT;
    	
		// -------------------------------------------
        // 'view_trackbacks_start' hook.
        //  - Allows complete rewrite of View Trackbacks page.
        //
        	$edata = $EXT->call_extension('view_trackbacks_start', $weblog_id, $entry_id, $message);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
    
    	$this->view_comments($weblog_id, $entry_id, $message, TRUE);
    }


    /** ---------------------------------------
    /**  View comments and trackback
    /** ---------------------------------------*/

    function view_comments($weblog_id = '', $entry_id = '', $message = '', $show_trackbacks=FALSE, $id_array='', $pagination_links = '', $rownum='')
    {
        global $IN, $DSP, $SESS, $DB, $DSP, $FNS, $LANG, $LOC, $PREFS, $EXT;
        
        // -------------------------------------------
        // 'view_comments_start' hook.
        //  - Allows complete rewrite of View Comments/Trackbacks page.
        //
        	$edata = $EXT->call_extension('view_comments_start', $weblog_id, $entry_id, $message, $show_trackbacks, $id_array, $pagination_links, $rownum);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
		$page_next			= '';
		$page_previous		= '';
		$current_page		= 0;
		$t_current_page		= '';
		$total_pages		= 1;
		$limit				= 75;
		
        /* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- view_comment_chars => Number of characters to display (#)
		/*	- view_comment_leave_breaks => Create <br />'s based on line breaks? (y/n)
        /* -------------------------------------------*/
		
		$this->comment_chars		= ($PREFS->ini('view_comment_chars') !== FALSE) ? $PREFS->ini('view_comment_chars') : $this->comment_chars;
		$this->comment_leave_breaks = ($PREFS->ini('view_comment_leave_breaks') !== FALSE) ? $PREFS->ini('view_comment_leave_breaks') : $this->comment_leave_breaks;
    
		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
    
        /** ---------------------------------------
        /**  Assign page header and breadcrumb
        /** ---------------------------------------*/
        
        $DSP->title = ($show_trackbacks === TRUE) ? $LANG->line('trackbacks') : $LANG->line('comments');
        $DSP->crumb = ($show_trackbacks === TRUE) ? $LANG->line('trackbacks') : $LANG->line('comments');
        
        $r = $DSP->qdiv('tableHeading', ($show_trackbacks === TRUE) ? $LANG->line('trackbacks') : $LANG->line('comments'));

        $validate = ($IN->GBL('validate', 'GET') == 1) ? TRUE : FALSE;
        
		if ($validate OR (is_array($id_array) && $show_trackbacks === FALSE))
		{    	
			if ( ! $DSP->allowed_group('can_moderate_comments'))
			{
				return $DSP->no_access_message();
			}     
			
			if (is_array($id_array))
			{
				$validate = TRUE;
				
				$r = $DSP->qdiv('tableHeading', $LANG->line('comments').' - '.$LANG->line('search'));
				
				$sql = "SELECT exp_comments.*, exp_weblogs.blog_name, exp_weblog_titles.title as entry_title
						FROM exp_comments, exp_weblogs, exp_weblog_titles
						WHERE exp_comments.comment_id IN ('".implode("','",$id_array)."')
						AND exp_comments.entry_id = exp_weblog_titles.entry_id
						AND exp_comments.weblog_id = exp_weblogs.weblog_id ";
			}
			else
			{
				$sql = "SELECT exp_comments.*, exp_weblogs.blog_name, exp_weblog_titles.title as entry_title
						FROM exp_comments, exp_weblogs, exp_weblog_titles
						WHERE exp_comments.status = 'c'
						AND exp_comments.entry_id = exp_weblog_titles.entry_id
						AND exp_comments.weblog_id = exp_weblogs.weblog_id ";		
							
				$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
			}
			
			$sql .= "ORDER BY comment_date DESC LIMIT 0,250";
			
			$query = $DB->query($sql);
						
			if ($query->num_rows == 0)
			{
				if ($IN->GBL('U', 'GET') == 1)
				{
					$r .= $DSP->qdiv('success',$LANG->line('status_changed'));
				}
				else
				{
					$r .= $DSP->qdiv('', $LANG->line('no_entries_matching_that_criteria'));
				}
				
        		return $r;
			}
			        						
			$comment_text_formatting = 'xhtml';
			$comment_html_formatting = 'safe';
			$comment_allow_img_urls  = 'n';
			$comment_auto_link_urls	 = 'y';
	
			$i = 0;
			foreach ($query->result as $row)
			{
				$results['c'.$row['comment_id']] = $query->result[$i];
				$i++;
			}
		}
		elseif(is_array($id_array) && $show_trackbacks === TRUE)
		{
			$validate = TRUE;
				
			$r = $DSP->qdiv('tableHeading', $LANG->line('trackbacks').' - '.$LANG->line('search'));
				
			$sql = "SELECT exp_trackbacks.*, exp_weblogs.blog_name, exp_weblog_titles.title as entry_title
					FROM exp_trackbacks, exp_weblogs, exp_weblog_titles
					WHERE exp_trackbacks.trackback_id IN ('".implode("','",$id_array)."')
					AND exp_trackbacks.entry_id = exp_weblog_titles.entry_id
					AND exp_trackbacks.weblog_id = exp_weblogs.weblog_id ";
					
			$query = $DB->query($sql);
						
			if ($query->num_rows == 0)
			{
				$r .= $DSP->qdiv('', $LANG->line('no_entries_matching_that_criteria'));
        		
        		return $r;
			}
			        						
			$comment_text_formatting = 'xhtml';
			$comment_html_formatting = 'safe';
			$comment_allow_img_urls  = 'n';
			$comment_auto_link_urls	 = 'y';
	
			$i = 0;
			foreach ($query->result as $row)
			{
				$results['t'.$row['trackback_id']] = $query->result[$i];
				$i++;
			}
		}
		else
		{
			if ($entry_id == '')
			{
				if ( ! $entry_id = $IN->GBL('entry_id', 'GET'))
				{
					return false;
				}
			}
			
			if ($weblog_id == '')
			{
				if ( ! $weblog_id = $IN->GBL('weblog_id', 'GET'))
				{
					return false;
				}
			}
			
			if (USER_BLOG !== FALSE)
			{        
				if ($weblog_id != UB_BLOG_ID)
				{
					return false;
				}
			}	
			
			if ( ! is_numeric($entry_id) OR ! is_numeric($weblog_id))
			{
				return FALSE;
			}
			
			
			/** ---------------------------------------
			/**  Fetch Author ID and verify privs
			/** ---------------------------------------*/
		
			$query = $DB->query("SELECT author_id, title FROM exp_weblog_titles WHERE entry_id = '$entry_id'");
			
			if ($query->num_rows == 0)
			{
				return $DSP->error_message($LANG->line('no_weblog_exits'));
			}
			
			if ($query->row['author_id'] != $SESS->userdata('member_id'))
			{    
				if ( ! $DSP->allowed_group('can_view_other_comments'))
				{
					return $DSP->no_access_message();
				}
			}
			
			$et = $query->row['title'];
			
			$r = $DSP->qdiv('tableHeading', (($show_trackbacks === TRUE) ? $LANG->line('trackbacks') : $LANG->line('comments')).' - '.$et);
			
			//---------------------------------------
			// Fetch comment display preferences
			// Also used for displaying trackbacks, so
			// we leave it in here - Paul
			//---------------------------------------
		
			$query = $DB->query("SELECT comment_text_formatting, 
										comment_html_formatting,
										comment_allow_img_urls,
										comment_auto_link_urls
										FROM exp_weblogs 
										WHERE weblog_id = '$weblog_id'");
			
			
			if ($query->num_rows == 0)
			{
				return $DSP->error_message($LANG->line('no_weblog_exits'));
			}
			
			foreach ($query->row as $key => $val)
			{
				$$key = $val;
			}	   
        
			/** ----------------------------------------
			/**  Fetch comment ID numbers
			/** ----------------------------------------*/
        
        	$temp = array();
        	$i = 0;        
        	
			$comments_exist = FALSE;
			
			if ($show_trackbacks === FALSE)
			{
				$query = $DB->query("SELECT comment_date, comment_id FROM exp_comments WHERE entry_id = '$entry_id' ORDER BY comment_date");	
	
				if ($query->num_rows > 0)
				{
					$comments_exist = TRUE;
					foreach ($query->result as $row)
					{
						$i++;
						$temp[$row['comment_date'].$i] = 'c'.$row['comment_id'];
					}
				}
			}
		
			/** ----------------------------------------
			/**  Fetch trackback ID numbers
			/** ----------------------------------------*/
		
			$trackbacks_exist = FALSE;
			
			if ($show_trackbacks === TRUE)
			{
				$query = $DB->query("SELECT trackback_date, trackback_id FROM exp_trackbacks WHERE entry_id = '$entry_id' ORDER BY trackback_date");
				
				if ($query->num_rows > 0)
				{
					$trackbacks_exist = TRUE;
					foreach ($query->result as $row)
					{
						$i++;
						$temp[$row['trackback_date'].$i] = 't'.$row['trackback_id'];
					}
				}
			}
		
    	    /** ------------------------------------
    	    /**  No results?  No reason to continue...
    	    /** ------------------------------------*/
			
			if (count($temp) == 0)
			{
				return $DSP->body = $DSP->qdiv('', $LANG->line('no_comments_or_trackbacks'));
			}
		
			// Sort the array based on the keys (which contain the Unix timesamps
			// of the comments and trackbacks)
			
			ksort($temp);
		
			// Create a new, sequentially indexed array
			
			$result_ids = array();
			
			foreach ($temp as $val)
			{
				$result_ids[$val] = $val;
			}
		
			// $result_ids = array_reverse($result_ids);
		
			   		
	        /** ---------------------------------
	        /**  Do we need pagination?
	        /** ---------------------------------*/
	        
	        if ($IN->GBL('current_page'))
	        {
	        	$current_page = $IN->GBL('current_page');
	        }
    
	        $total_rows = count($result_ids);
        						
			$current_page = ($current_page == '' || ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;
			
			if ($current_page > $total_rows)
			{
				$current_page = 0;
			}
						
			$t_current_page = floor(($current_page / $limit) + 1);
			$total_pages	= intval(floor($total_rows / $limit));
			
			if ($total_rows % $limit) 
				$total_pages++;
		
			if ($total_rows > $limit)
			{
				if ( ! class_exists('Paginate'))
				{
					require PATH_CORE.'core.paginate'.EXT;
				}
				
				$PGR = new Paginate();
				
				if ($show_trackbacks === FALSE)
				{
					$basepath = BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id;    
				}
				else
				{
					$basepath = BASE.AMP.'C=edit'.AMP.'M=view_trackbacks'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id; 
				}
				
				$pagination_links = $DSP->pager(  $basepath,
												  $total_rows, 
												  $limit,
												  $current_page,
												  'current_page'
												);
				
				if ((($total_pages * $limit) - $limit) > $current_page)
				{
					$page_next = $basepath.'P'.($current_page + $limit).'/';
				}
				
				if (($current_page - $limit ) >= 0) 
				{						
					$page_previous = $basepath.'P'.($current_page - $limit).'/';
				}
			}
	
			if ($current_page == '')
			{		
				$result_ids = array_slice($result_ids, 0, $limit);	
			}
			else
			{
				$result_ids = array_slice($result_ids, $current_page, $limit);	
			}			   		
			   		
			   		
	        /** -----------------------------------
	        /**  Fetch Comments if necessary
	        /** -----------------------------------*/
        
	        $results = $result_ids;
	                
			if ($comments_exist == TRUE)
			{
				$com = '';
				foreach ($result_ids as $val)
				{
					if (substr($val, 0, 1) == 'c')
					{
						$com .= substr($val, 1).",";
					}
				}
				
				if ($com != '')
				{			
					$sql = "SELECT 
							exp_comments.comment_id, exp_comments.entry_id, exp_comments.status, exp_comments.weblog_id, exp_comments.author_id, exp_comments.name, exp_comments.email, exp_comments.url, exp_comments.location, exp_comments.ip_address, exp_comments.comment_date, exp_comments.comment
							FROM exp_comments 
							WHERE exp_comments.comment_id  IN (".substr($com, 0, -1).")";
					
					$query = $DB->query($sql);
					
					if ($query->num_rows > 0)
					{
						$i = 0;
						foreach ($query->result as $row)
						{
							if (isset($results['c'.$row['comment_id']]))
							{
								$results['c'.$row['comment_id']] = $query->result[$i];
								$i++;
							}
						}
					}								
				}
	        }
		
			
        	/** -----------------------------------
        	/**  Fetch Trackbacks if necessary
        	/** -----------------------------------*/
			
			if ($trackbacks_exist == TRUE)
        	{
				$trb = '';
				foreach ($result_ids as $val)
				{			
					if (substr($val, 0, 1) == 't')
					{
						$trb .= substr($val, 1).",";
					}
				}
			
				if ($trb != '')
				{
					$sql = "SELECT exp_trackbacks.* FROM exp_trackbacks 
							WHERE exp_trackbacks.trackback_id IN (".substr($trb, 0, -1).")";			
				
					$query = $DB->query($sql);
				
					if ($query->num_rows > 0)
					{
						$i = 0;
						foreach ($query->result as $row)
						{
							if (isset($results['t'.$row['trackback_id']]))
							{
								$results['t'.$row['trackback_id']] = $query->result[$i];
								$i++;
							}
						}
					}
				}
        	}					   
		// END IF VALIDATE
		}       
            
        if ($message != '')
        	$r .= $DSP->qdiv('box', $message);
        
        /** ---------------------------------------
        /**  Instantiate the Typography class
        /** ---------------------------------------*/

        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
        
        $TYPE = new Typography;
        $val = ($validate) ? AMP.'validate=1' : '';

        /** ---------------------------------------
        /**  Create Table Header
        /** ---------------------------------------*/
        
        $r .= $DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
		$r .= $DSP->magic_checkboxes();
        
        $r .= $DSP->form_open(
        						array(
        								'action' => 'C=edit'.AMP.'M=modify_comments', 
        								'name'	=> 'target',
        								'id'	=> 'target'
        							)
        					);
        
        $r .= $DSP->input_hidden('current_page',  $rownum);
        
        if ($IN->GBL('keywords') !== FALSE)
        {
        	$r .= $DSP->input_hidden('keywords',   $IN->GBL('keywords'));
        }

		if ($show_trackbacks === TRUE)
		{
        	$r .= $DSP->table('tableBorder', '0', '', '100%').
        	      $DSP->tr().
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('title')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('weblog')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('date')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('comment_ip')).
        	      $DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('action')).
        	      $DSP->tr_c();
        }
        else
        {
        	$r .= $DSP->table('tableBorder', '0', '', '100%').
        	      $DSP->tr().
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('comment')).
        	      (($validate === TRUE) ? $DSP->table_qcell('tableHeadingAlt', $LANG->line('weblog')) : '').
        	      (($validate === TRUE) ? $DSP->table_qcell('tableHeadingAlt', $LANG->line('view_entry')) : '').
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('author')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('email')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('date')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('comment_ip')).
        	      $DSP->table_qcell('tableHeadingAlt', $LANG->line('status')).
        	      $DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('action')).
        	      $DSP->tr_c();
        }
        
        
        /** -------------------------------
        /**  Show comments
        /** -------------------------------*/
        
        $comment_flag = FALSE;
        $trackback_flag = FALSE;
   
        foreach ($results as $id => $row)
        {        
        	if ( ! is_array($row))
        		continue;
        		
            /** -------------------------------
            /**  Show Comments
            /** -------------------------------*/
            
            if (substr($id, 0, 1) == 'c') 
            {
            	$comment_flag = TRUE;
            	
            	if ($this->comment_leave_breaks == 'y')
            	{
            		$row['comment'] = str_replace(array("\n","\r"),
            									  '<br />',
            									  strip_tags($row['comment'])
            									  );
            	}
            	else
            	{
            		$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), '', $row['comment']));
            	}
            	
            	if ($this->comment_chars != 0)
            	{
            		$row['comment'] = $FNS->char_limiter(trim($row['comment']), $this->comment_chars);
            	}
               
				if (is_array($id_array))
				{
                 	$edit_comment =  $DSP->anchor(BASE.AMP.'C=edit'.
                 									   AMP.'M=edit_comment'.
                 									   AMP.'weblog_id='.$row['weblog_id'].
                 									   AMP.'keywords='.$IN->GBL('keywords').
                 									   AMP.'entry_id='.$row['entry_id'].
                 									   AMP.'comment_id='.$row['comment_id'].
                 									   AMP.'current_page='.$rownum.$val,
                 								  	$row['comment']);
                 }
                 else
                 {
                 	$edit_comment =  $DSP->anchor(BASE.AMP.'C=edit'.
                 									   AMP.'M=edit_comment'.
                 									   AMP.'weblog_id='.$row['weblog_id'].
                 									   AMP.'entry_id='.$row['entry_id'].
                 									   AMP.'comment_id='.$row['comment_id'].
                 									   AMP.'current_page='.$current_page.$val, 
                 								  $row['comment']);
                 }
               
				
				$r .= $DSP->tr()
					  .	$DSP->td('tableCellTwo')
					  .		$edit_comment
					  .	$DSP->td_c();
					  
				if ($validate === TRUE)
				{
					// Weblog entry title (view entry)
            
            		$show_link = TRUE;
            
            		if (($row['author_id'] != $SESS->userdata('member_id')) && ! $DSP->allowed_group('can_edit_other_entries'))
            		{
						$show_link = FALSE;
            		}
            
					$entry_url   = BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'];
					$entry_title = $FNS->char_limiter(trim(strip_tags($row['entry_title'])), 26); // Paul's Age!
				
					$r .= 	$DSP->td('tableCellTwo')
						  .		$row['blog_name']
						  .	$DSP->td_c()
						  
						  .	$DSP->td('tableCellTwo')
						  .		(($show_link == FALSE) ? $entry_title : $DSP->anchor($entry_url, $entry_title))
						  .	$DSP->td_c();
				}
				
				if ($row['author_id'] == '0')
				{
					$mid_search = $row['name'];
				}
				else
				{
					$mid_search = $DSP->anchor(BASE.AMP.'C=edit'.
													AMP.'M=view_entries'.
													AMP.'search_in=comments'.
													AMP.'order=desc'.
													AMP.'keywords='.base64_encode('mid:'.$row['author_id']), 
												$row['name']);
				}
				
				$r .= 	$DSP->td('tableCellTwo')
					  .		$mid_search
					  .	$DSP->td_c();
					  
				$email = ($row['email'] != '') ? $DSP->mailto($row['email'], $row['email']) : NBS.'--'.NBS;
				
				$r .= 	$DSP->td('tableCellTwo')
						  .		$email
						  .	$DSP->td_c();
				
				if ($row['status'] == 'o')
                 {
                 	$status = 'close';
                 	$status_label = $LANG->line('open');
                 }
                 else
                 {
                 	$status = 'open';    
                 	$status_label = $LANG->line('closed');
                 }
                 
                 if (is_array($id_array))
                 {
                 	$status_change = $DSP->anchor(BASE.AMP.'C=edit'.
                 									   AMP.'M=change_status'.
                 									   AMP.'search_in=comments'.
                 									   AMP.'weblog_id='.$row['weblog_id'].
                 									   AMP.'keywords='.$IN->GBL('keywords').
                 									   AMP.'comment_id='.$row['comment_id'].
                 									   AMP.'current_page='.$rownum.
                 									   AMP.'status='.$status.$val, $status_label);
                 }
                 else
                 {
                 	$status_change = $DSP->anchor(BASE.AMP.'C=edit'.
                 									   AMP.'M=change_status'.
                 									   AMP.'weblog_id='.$weblog_id.
                 									   AMP.'entry_id='.$entry_id.
                 									   AMP.'comment_id='.$row['comment_id'].
                 									   AMP.'current_page='.$current_page.
                 									   AMP.'status='.$status.$val, $status_label);
                 }
                 
                 $ip_search = $DSP->anchor(BASE.AMP.'C=edit'.
                 								AMP.'M=view_entries'.
                 								AMP.'search_in=comments'.
                 								AMP.'order=desc'.
                 								AMP.'keywords='.base64_encode('ip:'.str_replace('.','_',$row['ip_address'])), 
                 							$row['ip_address']);
                 							
				$r .= 	$DSP->td('tableCellTwo')
					  .		$LOC->set_human_time($row['comment_date'])
					  .	$DSP->td_c() 
					  
					  .	$DSP->td('tableCellTwo')
					  .		$ip_search
					  .	$DSP->td_c()
					  
					  .	$DSP->td('tableCellTwo')
					  .		 $status_change
					  .	$DSP->td_c()
					  
					  .	$DSP->td('tableCellTwo')
                      .		$DSP->input_checkbox('toggle[]', $id, '', "id='delete_box_{$id}'")
                      .	$DSP->td_c()
					  
					  .$DSP->tr_c();             
            }            
        
            /** -------------------------------
            /**  Show Trackbacks
            /** -------------------------------*/
            
            elseif (substr($id, 0, 1) == 't') 
            {                    
            	$trackback_flag = TRUE;
            	
            	$ip_search = $DSP->anchor(BASE.AMP.'C=edit'.
                 								AMP.'M=view_entries'.
                 								AMP.'search_in=trackbacks'.
                 								AMP.'order=desc'.
                 								AMP.'keywords='.base64_encode('ip:'.str_replace('.','_',$row['trackback_ip'])), 
                 							$row['trackback_ip']);
            
            	$r .= $DSP->tr()
            		  
            		  .	$DSP->td('tableCellTwo')
                      .		$DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=edit_trackback'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'].AMP.'trackback_id='.$row['trackback_id'], $row['title'])
                      .	$DSP->td_c()
            	
            		  .	$DSP->td('tableCellTwo')
            		  .		$DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$row['trackback_url'], $row['weblog_name'])
                      .	$DSP->td_c()
                      
                      .	$DSP->td('tableCellTwo')
                      .		'<nobr>'.$LOC->set_human_time($row['trackback_date']).'</nobr>'
                      .	$DSP->td_c()
                      
                      .	$DSP->td('tableCellTwo')
                      .		$ip_search
                      .	$DSP->td_c()
                      
                      .	$DSP->td('tableCellTwo')
                      .		$DSP->input_checkbox('toggle[]', $id, '', "id='delete_box_{$id}'")
                      .	$DSP->td_c()
                      
                      .	$DSP->tr_c();
            }
        
        }
        // END FOREACH
        
        if ($comment_flag === FALSE && $trackback_flag === TRUE)
        {
        	$options =  $DSP->input_select_header('action').
						$DSP->input_select_option('delete', $LANG->line('delete_selected')).
						$DSP->input_select_footer();
        }
        else
		{
			
			$options =  $DSP->input_select_header('action').
						$DSP->input_select_option('close', $LANG->line('close_selected')).
						$DSP->input_select_option('open', $LANG->line('open_selected')).
						$DSP->input_select_option('delete', $LANG->line('delete_selected'));
						
			if ( $DSP->allowed_group('can_edit_all_comments') OR
				 $DSP->allowed_group('can_moderate_comments'))
				{
					$options .= $DSP->input_select_option('null', '------').
								$DSP->input_select_option('move', $LANG->line('move_selected'));
				}
				
			$options .= $DSP->input_select_footer();
        }
        
        $r .= $DSP->table_c();
        
        $r .= 		$DSP->table('', '0', '', '100%')
        		.		$DSP->tr()
              	.			$DSP->td('defaultRight')
              	.				$DSP->input_submit($LANG->line('submit')).NBS.NBS.$options
              	.			$DSP->td_c()
              	.		$DSP->tr_c()
              	.	$DSP->table_c()
              	.$DSP->form_close();
        
        if ($pagination_links != '')
        {
        	$r .= $DSP->qdiv('itemWrapper', $pagination_links);
        }
        
        $DSP->body = $r;
        
    }
    /* END */
    
	/** -----------------------------------------
    /**  Move comments form
    /** -----------------------------------------*/

    function move_comments_form()
    {
        global $IN, $DSP, $DB, $LANG, $PREFS, $REGX, $FNS, $SESS, $STAT;
        
        $weblog_id		= $IN->GBL('weblog_id');
        $entry_id		= $IN->GBL('entry_id');
        
        if($IN->GBL('comment_ids') !== FALSE)
        {
        	$comments = explode('|', $IN->GBL('comment_ids'));
        	
        	foreach($comments as $key => $val)
        	{
        		$comments[$key] = $DB->escape_str($val);
        	}
		}
		else
		{	
			$comments	= array();
			
			foreach ($_POST as $key => $val)
			{        
				if (strstr($key, 'toggle') AND ! is_array($val))
				{
					if (substr($val, 0, 1) == 'c')
					{
						$comments[] = $DB->escape_str(substr($val, 1));
					}
				}
			}
			
			if($IN->GBL('comment_id') !== FALSE && is_numeric($IN->GBL('comment_id')))
			{
				$comments[] = $DB->escape_str($IN->GBL('comment_id'));
			}
		}
		
		if (sizeof($comments) == 0)
		{
			return $this->edit_entries();
		}
		
		if ( ! $DSP->allowed_group('can_moderate_comments') && ! $DSP->allowed_group('can_edit_all_comments'))
		{
			return $DSP->no_access_message();
		}
        	
        if ($DSP->allowed_group('can_edit_all_comments'))
		{     
			// Can Edit All Comments
			$sql = "SELECT exp_comments.comment_id
					FROM   exp_comments
					WHERE  exp_comments.comment_id IN ('".implode("','", $comments)."')";
		}
		else
		{
			// Can Moderate Comments, but only from non-USER blogs.
			$sql = "SELECT exp_comments.comment_id
					FROM exp_comments, exp_weblogs
					WHERE exp_comments.comment_id IN ('".implode("','", $comments)."') 
					AND exp_comments.weblog_id = exp_weblogs.weblog_id ";
					
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
		}
        
        /** -------------------------------
        /**  Retrieve Our Results
        /** -------------------------------*/
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }
        
        $comment_ids  = array();
        
        foreach($query->result as $row)
        {
        	$comment_ids[]  = $row['comment_id'];
        }
        
        /** -------------------------------
        /**  Create Our Form
        /** -------------------------------*/
   		
		$r = $DSP->input_hidden('comment_ids', implode('|', $comments));
		
		if ($IN->GBL('keywords') !== FALSE)
        {
        	$r .= $DSP->input_hidden('keywords',   $IN->GBL('keywords'));
        }
        
        if ($IN->GBL('current_page') !== FALSE)
        {
        	$r .= $DSP->input_hidden('current_page',  $IN->GBL('current_page'));
        }
        
        $actions = NBS.$DSP->input_select_header('action').
        	      	   $DSP->input_select_option('move', $LANG->line('move_comments_to_entry')).
        	      	   $DSP->input_select_footer();
        
        
        $DSP->title = $LANG->line('choose_entry_for_comment_move');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_comments', $LANG->line('comments')).$DSP->crumb_item($LANG->line('choose_entry_for_comment_move'));

        $DSP->body .= $this->view_entries(	'', 
											'', 
											'',
											'C=edit'.AMP.'M=move_comments_form', 
											'C=edit'.AMP.'M=move_comments', 
											$actions,
											$r,
											$r);
											
		$DSP->body = preg_replace("/".str_replace('REPLACE_HERE', 
        											  '.*?', 
        											  preg_quote($DSP->qdiv('tableHeading', 'REPLACE_HERE'), 
        											  			 '/')
        											 )."/",
        							  $DSP->qdiv('tableHeading', $LANG->line('choose_entry_for_comment_move')), 
        							  $DSP->body,
        							  1);
        
    }
    /* END */
    
    
    
    /** -----------------------------------------
    /**  Moving comments
    /** -----------------------------------------*/

    function move_comments()
    {
        global $IN, $DSP, $DB, $LANG, $PREFS, $REGX, $FNS, $SESS, $STAT;
        
        $weblog_id		= $IN->GBL('weblog_id');
        $entry_id		= $IN->GBL('entry_id');
        
        if ( ! $DSP->allowed_group('can_moderate_comments') && ! $DSP->allowed_group('can_edit_all_comments'))
		{
			return $DSP->no_access_message();
		}
        
		if($IN->GBL('comment_ids') !== FALSE)
        {
        	$comments = explode('|', $IN->GBL('comment_ids'));
        	
        	foreach($comments as $key => $val)
        	{
        		$comments[$key] = $DB->escape_str($val);
        	}
		}
		else
		{
			return $this->edit_entries();
		}
		
		$new_entries = array();
			
		foreach ($_POST as $key => $val)
		{        
			if (strstr($key, 'toggle') AND ! is_array($val))
			{
				$new_entries[] = $val;
			}
		}
		
		if (sizeof($new_entries) == 0)
		{
			return $this->move_comments_form();
		}
		elseif(sizeof($new_entries) > 1)
		{
			return $DSP->error_message($LANG->line('choose_only_one_entry'));
		}
		
		$query = $DB->query("SELECT weblog_id, entry_id FROM exp_weblog_titles WHERE entry_id = '".$DB->escape_str($new_entries['0'])."'");
		
		$new_entry_id = $query->row['entry_id'];
		$new_weblog_id = $query->row['weblog_id'];
        	
        if ($DSP->allowed_group('can_edit_all_comments'))
		{     
			// Can Edit All Comments
			$sql = "SELECT exp_comments.comment_id, exp_comments.weblog_id, exp_comments.entry_id
					FROM   exp_comments
					WHERE  exp_comments.comment_id IN ('".implode("','", $comments)."')";
		}
		else
		{
			// Can Moderate Comments, but only from non-USER blogs.
			$sql = "SELECT exp_comments.comment_id, exp_comments.weblog_id, exp_comments.entry_id
					FROM exp_comments, exp_weblogs
					WHERE exp_comments.comment_id IN ('".implode("','", $comments)."') 
					AND exp_comments.weblog_id = exp_weblogs.weblog_id ";
					
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
		}
        
        /** -------------------------------
        /**  Retrieve Our Results
        /** -------------------------------*/
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }
        
        $comment_ids  = array();
        $entry_ids  = array($new_entry_id);
        $weblog_ids = array($new_weblog_id);
        
        /** -------------------------------
        /**  Move Comments
        /** -------------------------------*/
        
  		foreach($query->result as $row)
        {
        	$DB->query($DB->update_string('exp_comments', array('weblog_id' => $new_weblog_id, 'entry_id' => $new_entry_id), "comment_id = '".$row['comment_id']."'"));
        
        	$comment_ids[]  = $row['comment_id'];
        	$entry_ids[]  = $row['entry_id'];
        	$weblog_ids[] = $row['weblog_id'];
        }
        
        /** -------------------------------
        /**  Recounts
        /** -------------------------------*/
        
		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$DB->escape_str($entry_id)."'");
		
			$comment_date = ($query->num_rows == 0 OR !is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
		
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$DB->escape_str($entry_id)."' AND status = 'o'");
		
			$DB->query("UPDATE exp_weblog_titles SET comment_total = '".($query->row['count'])."', recent_comment_date = '$comment_date' WHERE entry_id = '".$DB->escape_str($entry_id)."'");      
		}
		
		// Quicker and updates just the weblogs
		foreach(array_unique($weblog_ids) as $weblog_id) { $STAT->update_comment_stats($weblog_id, '', FALSE); }
		
		// Updates the total stats
		$STAT->update_comment_stats();
				
		$FNS->clear_caching('all');
		
		$FNS->redirect(BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$new_weblog_id.AMP.'entry_id='.$new_entry_id.AMP.'U=1'.$val);
		exit;
    }
    /* END */


    /** -----------------------------------------
    /**  Edit comments form
    /** -----------------------------------------*/

    function edit_comment_form()
    {
        global $IN, $DB, $DSP, $LANG, $SESS, $EXT;
        
        // -------------------------------------------
        // 'edit_comment_form_start' hook.
        //  - Allows complete rewrite of Edit Comment page.
        //
        	$edata = $EXT->call_extension('edit_comment_form_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        $comment_id 	= $IN->GBL('comment_id');
        $weblog_id  	= $IN->GBL('weblog_id');
        $entry_id   	= $IN->GBL('entry_id');
        $current_page	= $IN->GBL('current_page');
        
        
        if ($comment_id == FALSE OR ! is_numeric($comment_id) OR ! is_numeric($weblog_id) OR ! is_numeric($entry_id))
        {
            return $DSP->no_access_message();
        }   
        
        $validate = 0;
        if ($IN->GBL('validate') == 1)
        {
			if ( ! $DSP->allowed_group('can_moderate_comments'))
			{
				return $DSP->no_access_message();
			} 
			
			$sql = "SELECT exp_comments.*
					FROM exp_comments, exp_weblogs
					WHERE comment_id = '$comment_id' ";
						
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
															
			$query = $DB->query($sql);			
			
        	$validate = 1;
        }
        else
        {
			if ( ! $DSP->allowed_group('can_edit_all_comments'))
			{
				if ( ! $DSP->allowed_group('can_edit_own_comments'))
				{     
					return $DSP->no_access_message();
				}
				else
				{
					$sql = "SELECT exp_weblog_titles.author_id 
							FROM   exp_weblog_titles, exp_comments
							WHERE  exp_weblog_titles.entry_id = exp_comments.entry_id
							AND    exp_comments.comment_id = '$comment_id'";
	
					$query = $DB->query($sql);
					
					if ($query->row['author_id'] != $SESS->userdata('member_id'))
					{
						return $DSP->no_access_message();
					}
				}
			}
			
        		$query = $DB->query("SELECT * FROM exp_comments WHERE comment_id = '$comment_id'");
		}        
        
        if ($query->num_rows == 0)
        {
        	return false;
        }
        
        foreach ($query->row as $key => $val)
        {
        	$$key = $val;
        }
        
        $r  = $DSP->form_open(array('action' => 'C=edit'.AMP.'M=update_comment'));
        $r .= $DSP->input_hidden('comment_id', $comment_id);
        $r .= $DSP->input_hidden('author_id',  $author_id);
        $r .= $DSP->input_hidden('weblog_id',  $weblog_id);
        $r .= $DSP->input_hidden('current_page',  $current_page);
        $r .= $DSP->input_hidden('entry_id',   $entry_id);
        $r .= $DSP->input_hidden('validate',   $validate);
        
        if ($IN->GBL('keywords') !== FALSE)
        {
        	$r .= $DSP->input_hidden('keywords',   $IN->GBL('keywords'));
        }
                
        $r .= $DSP->qdiv('tableHeading', $LANG->line('edit_comment'));
        
        if ($author_id == 0)
        {
			$r .= $DSP->itemgroup(
									$DSP->required().NBS.$LANG->line('name', 'name'),
									$DSP->input_text('name', $name, '40', '100', 'input', '300px')
								  );
												
			$r .= $DSP->itemgroup(
									$DSP->required().NBS.$LANG->line('email', 'email'),
									$DSP->input_text('email', $email, '35', '100', 'input', '300px')
								  );
		 
	
			$r .= $DSP->itemgroup(
									$LANG->line('url', 'url'),
									$DSP->input_text('url', $url, '40', '100', 'input', '300px')
								  );
								  
			$r .= $DSP->itemgroup(
									$LANG->line('location', 'location'),
									$DSP->input_text('location', $location, '40', '100', 'input', '300px')
								  );
         }   
         
		$r .= $DSP->input_textarea('comment', $comment, '20', 'textarea', '100%');
        
        // Submit button   
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        
        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('edit_comment');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, $LANG->line('comments')).$DSP->crumb_item($LANG->line('edit_comment'));
        $DSP->body  = $r;
    }
    /* END */
    
    
    
	/** -----------------------------------------
    /**  Edit trackbacks form
    /** -----------------------------------------*/

    function edit_trackback_form()
    {
        global $IN, $DB, $DSP, $LANG, $SESS, $EXT;
        
        // -------------------------------------------
        // 'edit_trackback_form' hook.
        //  - Allows complete rewrite of Edit Trackback page.
        //
        	$edata = $EXT->call_extension('edit_trackback_form');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        $trackback_id 	= $IN->GBL('trackback_id');
        $weblog_id  	= $IN->GBL('weblog_id');
        $entry_id   	= $IN->GBL('entry_id');
        $current_page	= $IN->GBL('current_page');
        
        if ($trackback_id == FALSE OR ! is_numeric($trackback_id) OR ! is_numeric($weblog_id) OR ! is_numeric($entry_id))
        {
            return $DSP->no_access_message();
        }   
        
        if ( ! $DSP->allowed_group('can_edit_all_comments'))
		{
			if ( ! $DSP->allowed_group('can_edit_own_comments'))
			{     
				return $DSP->no_access_message();
			}
			else
			{
				$sql = "SELECT exp_weblog_titles.author_id 
						FROM   exp_weblog_titles, exp_trackbacks
						WHERE  exp_weblog_titles.entry_id = exp_trackbacks.entry_id
						AND    exp_trackbacks.trackback_id = '$trackback_id'";
	
				$query = $DB->query($sql);
					
				if ($query->row['author_id'] != $SESS->userdata('member_id'))
				{
					return $DSP->no_access_message();
				}
			}
		}
		
		$query = $DB->query("SELECT * FROM exp_trackbacks WHERE trackback_id = '$trackback_id'");     
        
        if ($query->num_rows == 0)
        {
        	return false;
        }
        
        foreach ($query->row as $key => $val)
        {
        	$$key = $val;
        }
        
        
        $r  = $DSP->form_open(array('action' => 'C=edit'.AMP.'M=update_trackback'));
        $r .= $DSP->input_hidden('trackback_id', $trackback_id);
        $r .= $DSP->input_hidden('weblog_id',  $weblog_id);
        $r .= $DSP->input_hidden('current_page',  $current_page);
        $r .= $DSP->input_hidden('entry_id',   $entry_id);
                
        $r .= $DSP->qdiv('tableHeading', $LANG->line('edit_trackback'));
        
        $r .= $DSP->itemgroup(
								$DSP->required().NBS.$LANG->line('title', 'title'),
								$DSP->input_text('title', $title, '40', '100', 'input', '300px')
							 );
        
        $r .= $DSP->itemgroup(
								$DSP->required().NBS.$LANG->line('weblog', 'weblog'),
								$DSP->input_text('weblog', $weblog_name, '50', '100', 'input', '300px')
							 );
							 
		$r .= $DSP->itemgroup(
								$DSP->required().NBS.$LANG->line('url', 'url'),
								$DSP->input_text('url', $trackback_url, '50', '125', 'input', '300px')
							 );
         
		$r .= BR.$DSP->input_textarea('tb_content', $content, '20', 'textarea', '100%');
        
        // Submit button   
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        
        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('edit_trackback');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_trackbacks'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, $LANG->line('comments')).$DSP->crumb_item($LANG->line('edit_trackback'));
        $DSP->body  = $r;
    }
    /* END */
    
    
    


    /** -----------------------------------------
    /**  Update comment
    /** -----------------------------------------*/

    function update_comment()
    {
        global $IN, $DSP, $DB, $LANG, $REGX, $SESS, $FNS, $EXT;
    
        $comment_id = $IN->GBL('comment_id');
        $author_id  = $IN->GBL('author_id');
        $weblog_id  = $IN->GBL('weblog_id');
        $entry_id   = $IN->GBL('entry_id');                        

        if ($comment_id == FALSE OR ! is_numeric($comment_id) OR ! is_numeric($weblog_id) OR ! is_numeric($entry_id))
        {
            return $DSP->no_access_message();
        }   

        if ($author_id === FALSE)
        {
            return $DSP->no_access_message();
        }    
        
        if ($IN->GBL('validate') == 1)
        {
			if ( ! $DSP->allowed_group('can_moderate_comments'))
			{
				return $DSP->no_access_message();
			}    
			
			$sql = "SELECT COUNT(*) AS count 
					FROM exp_comments, exp_weblogs
					WHERE comment_id = '$comment_id' ";
						
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
															
			$query = $DB->query($sql);			
	
			if ($query->row['count'] == 0)
			{
				return $DSP->no_access_message();
			}			
        }
        else
        {        
			if ( ! $DSP->allowed_group('can_edit_all_comments'))
			{
				if ( ! $DSP->allowed_group('can_edit_own_comments'))
				{     
					return $DSP->no_access_message();
				}
				else
				{
					$sql = "SELECT exp_weblog_titles.author_id 
							FROM   exp_weblog_titles, exp_comments
							WHERE  exp_weblog_titles.entry_id = exp_comments.entry_id
							AND    exp_comments.comment_id = '$comment_id'";
	
					$query = $DB->query($sql);
					
					if ($query->row['author_id'] != $SESS->userdata('member_id'))
					{
						return $DSP->no_access_message();
					}
				}
			}
        }
        
        /** ---------------------------------------
        /**  Fetch comment display preferences
        /** ---------------------------------------*/
    
        $query = $DB->query("SELECT exp_weblogs.comment_require_email
                                    FROM exp_weblogs, exp_comments
                                    WHERE exp_comments.weblog_id = exp_weblogs.weblog_id
                                    AND exp_comments.comment_id = '$comment_id'");
        
        
        if ($query->num_rows == 0)
        {
            return $DSP->error_message($LANG->line('no_weblog_exits'));
        }
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }


        /** -------------------------------------
        /**  Error checks
        /** -------------------------------------*/
		
		$error = array();

		if ($author_id == 0)
		{
			// Fetch language file
			
			$LANG->fetch_language_file('myaccount');
			
            if ($comment_require_email == 'y')
            {
				/** -------------------------------------
				/**  Is email missing?
				/** -------------------------------------*/
				
				if ($_POST['email'] == '')
				{
					$error[] = $LANG->line('missing_email');
				}
				
				/** -------------------------------------
				/**  Is email valid?
				/** -------------------------------------*/
				
				if ( ! $REGX->valid_email($_POST['email']))
				{
					$error[] = $LANG->line('invalid_email_address');
				}
				
				
				/** -------------------------------------
				/**  Is email banned?
				/** -------------------------------------*/
				
				if ($SESS->ban_check('email', $_POST['email']))
				{
					$error[] = $LANG->line('banned_email');
				}
			}
		}

		/** -------------------------------------
		/**  Is comment missing?
		/** -------------------------------------*/
		
		if ($_POST['comment'] == '')
		{
			$error[] = $LANG->line('missing_comment');
		}

        
        /** -------------------------------------
        /**  Display error is there are any
        /** -------------------------------------*/

         if (count($error) > 0)
         {
            $msg = '';
            
            foreach($error as $val)
            {
                $msg .= $val.'<br />';  
            }
            
            return $DSP->error_message($msg);
         }

		// Build query
		
		if ($author_id == 0)
		{
			$data = array(
							'name'		=> $_POST['name'],	
							'email'		=> $_POST['email'],	
							'url'		=> $_POST['url'],	
							'location'	=> $_POST['location'],	
							'comment'	=> $_POST['comment']	
						 );
		}
		else
		{
		
			$data = array(
							'comment'	=> $_POST['comment']	
						 );
		}

			
		$DB->query($DB->update_string('exp_comments', $data, "comment_id = '$comment_id'")); 
		
		// -------------------------------------------
        // 'update_comment_additional' hook.
        //  - Add additional processing on comment update.
        //
        	$edata = $EXT->call_extension('update_comment_additional', $comment_id, $data);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
		
		$FNS->clear_caching('all');				

		$current_page = ( ! isset($_POST['current_page'])) ? 0 : $_POST['current_page'];
		
		if ($IN->GBL('keywords') !== FALSE)
		{
			$url = BASE.AMP.'C=edit'.
						AMP.'M=view_entries'.
						AMP.'search_in=comments'.
						AMP.'rownum='.$current_page.
						AMP.'order=desc'.
						AMP.'keywords='.$IN->GBL('keywords');
		}
		elseif ($IN->GBL('validate', 'POST') == 1)
		{
			$url = BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'validate=1';
		}
		else
		{
			$url = BASE.AMP.'C=edit'.
						AMP.'M=view_comments'.
						AMP.'weblog_id='.$weblog_id.
						AMP.'entry_id='.$entry_id.
						AMP.'current_page='.$current_page;
		}
		
		$FNS->redirect($url);
		exit;
	}
	/* END */
	
	
	
	/** -----------------------------------------
    /**  Update trackback
    /** -----------------------------------------*/

    function update_trackback()
    {
        global $IN, $DSP, $DB, $LANG, $REGX, $SESS, $FNS, $EXT;
    
        $trackback_id = $IN->GBL('trackback_id');
        $weblog_id    = $IN->GBL('weblog_id');
        $entry_id     = $IN->GBL('entry_id');                        

        if ($trackback_id == FALSE OR ! is_numeric($trackback_id) OR ! is_numeric($weblog_id) OR ! is_numeric($entry_id))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $DSP->allowed_group('can_edit_all_comments'))
		{
			if ( ! $DSP->allowed_group('can_edit_own_comments'))
			{     
				return $DSP->no_access_message();
			}
			else
			{
				$sql = "SELECT exp_weblog_titles.author_id 
						FROM   exp_weblog_titles, exp_trackbacks
						WHERE  exp_weblog_titles.entry_id = exp_trackbacks.entry_id
						AND    exp_trackbacks.trackback_id = '$trackback_id'";
	
				$query = $DB->query($sql);
					
				if ($query->row['author_id'] != $SESS->userdata('member_id'))
				{
					return $DSP->no_access_message();
				}
			}
		}

        /** -------------------------------------
        /**  Error checks
        /** -------------------------------------*/
		
		$error = array();

		/** -------------------------------------
		/**  Is content missing?
		/** -------------------------------------*/
		
		foreach(array('url', 'weblog', 'title', 'tb_content') as $value)
		
		if (!isset($_POST[$value]) OR $_POST[$value] == '')
		{
			$error[] = $LANG->line('field_blank');
		}
        
        /** -------------------------------------
        /**  Display error is there are any
        /** -------------------------------------*/

         if (count($error) > 0)
         {
            $msg = '';
            
            $error = array_unique($error);
            
            foreach($error as $val)
            {
                $msg .= $val.'<br />';  
            }
            
            return $DSP->error_message($msg);
         }

		// Build query
		
		$data = array(	'title'			=> $_POST['title'],
						'weblog_name'	=> $_POST['weblog'],
						'trackback_url'	=> $_POST['url'],
						'content'		=> $_POST['tb_content']);
			
		$DB->query($DB->update_string('exp_trackbacks', $data, "trackback_id = '$trackback_id'")); 
		
		// -------------------------------------------
        // 'update_trackback_additional' hook.
        //  - Add additional processing on trackback update.
        //
        	$edata = $EXT->call_extension('update_trackback_additional', $trackback_id, $data);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
		
		$FNS->clear_caching('all');

		$current_page = ( ! isset($_POST['current_page'])) ? 0 : $_POST['current_page'];
		
		$url = BASE.AMP.'C=edit'.AMP.'M=view_trackbacks'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id.AMP.'current_page='.$current_page;
		$FNS->redirect($url);
		exit;
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  Modify Comments
    /** -----------------------------------------*/

    function modify_comments()
    {
		global $IN;
		
		switch($IN->GBL('action', 'POST'))
		{
			case 'open':
				$this->change_comment_status('open');
			break;
			case 'close':
				$this->change_comment_status('close');
			break;
			case 'move':
				$this->move_comments_form();
			break;
			default: 
				$this->delete_comment_confirm();
			break;
		}
	}
	/* END */

    /** -----------------------------------------
    /**  Delete comment/trackback confirmation
    /** -----------------------------------------*/

    function delete_comment_confirm()
    {
        global $IN, $DSP, $DB, $LANG, $SESS;
        
        $weblog_id    = $IN->GBL('weblog_id');
        $entry_id     = $IN->GBL('entry_id');
        
        if ( ! $IN->GBL('toggle', 'POST') && ! $IN->GBL('comment_id') && ! $IN->GBL('trackback_id'))
        {
            return $this->edit_entries();
        }
        
        $comments	= array();
        $trackbacks	= array();
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                if (substr($val, 0, 1) == 't')
                {
                	$trackbacks[] = $DB->escape_str(substr($val, 1));
                }
                elseif (substr($val, 0, 1) == 'c')
                {
                	$comments[] = $DB->escape_str(substr($val, 1));
                }
            }
        }
        
        if($IN->GBL('comment_id') !== FALSE && is_numeric($IN->GBL('comment_id')))
        {
        	$comments[] = $DB->escape_str($IN->GBL('comment_id'));
		}
		elseif($IN->GBL('trackback_id') !== FALSE && is_numeric($IN->GBL('trackback_id')))
        {
        	$trackbacks[] = $DB->escape_str($IN->GBL('trackback_id'));
		}
        
        
        if ($IN->GBL('validate') == 1)
        {
        	if (sizeof($comments) == 0)
			{
				return $DSP->no_access_message();
			}
        
			if ( ! $DSP->allowed_group('can_moderate_comments'))
			{
				return $DSP->no_access_message();
			}    
						
			$sql = "SELECT COUNT(*) AS count 
					FROM exp_comments, exp_weblogs
					WHERE comment_id IN ('".implode("','", $comments)."') ";
						
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
						
			$query = $DB->query($sql);			
	
			if ($query->row['count'] == 0)
			{
				return $DSP->no_access_message();
			}			
        }
		else
		{
			if ( ! $DSP->allowed_group('can_delete_all_comments'))
			{
				if ( ! $DSP->allowed_group('can_delete_own_comments'))
				{     
					return $DSP->no_access_message();
				}
				else
				{
					if (sizeof($comments) > 0)
					{
						$sql = "SELECT exp_weblog_titles.author_id, exp_comments.comment_id 
								FROM   exp_weblog_titles, exp_comments
								WHERE  exp_weblog_titles.entry_id = exp_comments.entry_id
								AND    exp_comments.comment_id IN ('".implode("','", $comments)."')";
					}
					elseif (sizeof($trackbacks) > 0)
					{
						$sql = "SELECT exp_weblog_titles.author_id, exp_trackbacks.trackback_id
								FROM   exp_weblog_titles, exp_trackbacks
								WHERE  exp_weblog_titles.entry_id = exp_trackbacks.entry_id
								AND    exp_trackbacks.trackback_id IN ('".implode("','", $trackbacks)."')";
					}
					
					$trackbacks	= array();
					$comments	= array();
					
					$query = $DB->query($sql);
					
					if ($query->num_rows > 0)
					{
						foreach($query->result as $row)
						{
							if ($row['author_id'] == $SESS->userdata('member_id'))
							{
								if (isset($row['trackback_id']))
								{
									$trackbacks[] = $row['trackback_id'];
								}
								else
								{
									$comments[] = $row['comment_id'];
								}
							}
						}
					}
				}
			}
   		}
   		
   		if (sizeof($trackbacks) == 0 && sizeof($comments) == 0)
        {
        	return $this->edit_entries();
        }
   		
   		$r  = $DSP->form_open(array('action' => 'C=edit'.AMP.'M=del_comment'));
        
        $validate = ($IN->GBL('validate') == 1) ? 1 : 0;
        
		$r .= $DSP->input_hidden('validate', $validate);
		$r .= $DSP->input_hidden('comment_ids', implode('|', $comments));
		$r .= $DSP->input_hidden('trackback_ids', implode('|', $trackbacks));
		
		if ($IN->GBL('keywords') !== FALSE)
        {
        	$r .= $DSP->input_hidden('keywords',   $IN->GBL('keywords'));
        	$r .= $DSP->input_hidden('current_page',  $IN->GBL('current_page'));
        }
                
        $r .= $DSP->qdiv('alertHeading', $LANG->line('delete_confirm'));
        $r .= $DSP->div('box');
        
        if (sizeof($comments) > 0)
        {
        	if (sizeof($comments) == 1)
        	{
            	$r .= '<b>'.$LANG->line('delete_comment_confirm').'</b>';
            }
            else
            {
            	$r .= '<b>'.$LANG->line('delete_comments_confirm').'</b>';
            }
        }
        else
        {
        	if (sizeof($trackbacks) == 1)
        	{
            	$r .= '<b>'.$LANG->line('delete_trackback_confirm').'</b>';
            }
            else
            {
            	$r .= '<b>'.$LANG->line('delete_trackbacks_confirm').'</b>';
            }
        }
        
        $r .= $DSP->br(2).
              $DSP->qdiv('alert', $LANG->line('action_can_not_be_undone')).
              $DSP->br().
              $DSP->input_submit($LANG->line('delete')).
              $DSP->div_c().
              $DSP->form_close();

        $DSP->title = $LANG->line('delete_confirm');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id, $LANG->line('comments')).$DSP->crumb_item($LANG->line('edit_comment'));
        $DSP->body  = $r;
    }
    /* END */


    /** -----------------------------------------
    /**  Change Comment Status
    /** -----------------------------------------*/

    function change_comment_status($status='')
    {
        global $IN, $DSP, $DB, $LANG, $PREFS, $REGX, $FNS, $SESS, $STAT;
        
        $weblog_id		= $IN->GBL('weblog_id');
        $entry_id		= $IN->GBL('entry_id');
        $current_page	= $IN->GBL('current_page');
        
        $comments	= array();
        $trackbacks	= array();
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                if (substr($val, 0, 1) == 'c')
                {
                	$comments[] = $DB->escape_str(substr($val, 1));
                }
            }
        }
        
        if($IN->GBL('comment_id') !== FALSE && is_numeric($IN->GBL('comment_id')))
        {
        	$comments[] = $DB->escape_str($IN->GBL('comment_id'));
		}
		
		if (sizeof($comments) == 0)
		{
			return $DSP->no_access_message();
		}
		
		if ( ! $DSP->allowed_group('can_moderate_comments') && ! $DSP->allowed_group('can_edit_all_comments'))
		{
			return $DSP->no_access_message();
		}
        	
        if ($DSP->allowed_group('can_edit_all_comments'))
		{     
			// Can Edit All Comments
			$sql = "SELECT exp_comments.entry_id, exp_comments.weblog_id, exp_comments.author_id
					FROM   exp_comments
					WHERE  exp_comments.comment_id IN ('".implode("','", $comments)."')";
		}
		else
		{
			// Can Moderate Comments, but only from non-USER blogs.
			$sql = "SELECT exp_comments.entry_id, exp_comments.weblog_id, exp_comments.author_id
					FROM exp_comments, exp_weblogs
					WHERE exp_comments.comment_id IN ('".implode("','", $comments)."') 
					AND exp_comments.weblog_id = exp_weblogs.weblog_id ";
					
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
		}
        
        /** -------------------------------
        /**  Retrieve Our Results
        /** -------------------------------*/
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }
        
        $entry_ids  = array();
        $author_ids = array();
        $weblog_ids = array();
        
        foreach($query->result as $row)
        {
        	$entry_ids[]  = $row['entry_id'];
        	$author_ids[] = $row['author_id'];
        	$weblog_ids[] = $row['weblog_id'];
        }
        
        $entry_ids  = array_unique($entry_ids);
        $author_ids = array_unique($author_ids);
        $weblog_ids = array_unique($weblog_ids);
        
        /** -------------------------------
        /**  Change Status
        /** -------------------------------*/
        
        $status = ($status == 'close' OR (isset($_GET['status']) AND $_GET['status'] == 'close')) ? 'c' : 'o';
        
        $DB->query("UPDATE exp_comments SET status = '$status' WHERE comment_id IN ('".implode("','", $comments)."') ");
        
		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$DB->escape_str($entry_id)."'");
		
			$comment_date = ($query->num_rows == 0 OR !is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
		
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$DB->escape_str($entry_id)."' AND status = 'o'");
		
			$DB->query("UPDATE exp_weblog_titles SET comment_total = '".($query->row['count'])."', recent_comment_date = '$comment_date' WHERE entry_id = '".$DB->escape_str($entry_id)."'");      
		}
		
		// Quicker and updates just the weblogs
		foreach(array_unique($weblog_ids) as $weblog_id) { $STAT->update_comment_stats($weblog_id, '', FALSE); }
		
		// Updates the total stats
		$STAT->update_comment_stats();
		
		foreach(array_unique($author_ids) as $author_id)
		{
			$res = $DB->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
			$comment_total = $res->row['comment_total'];
			$comment_date  = (!empty($res->row['comment_date'])) ? $res->row['comment_date'] : 0;
				
			$DB->query($DB->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));   
		}
		
		/** ----------------------------------------
		/**  Send email notification
		/** ----------------------------------------*/

		if ($status == 'o')
		{
			/** ----------------------------------------
			/**  Instantiate Typography class
			/** ----------------------------------------*/
		  
			if ( ! class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}
					
			$TYPE = new Typography(0); 
			
			/** ----------------------------------------
			/**  Go Through Array of Entries
			/** ----------------------------------------*/
			
			foreach ($comments as $comment_id)
			{
				$query = $DB->query("SELECT comment, name, email, comment_date, entry_id
									 FROM exp_comments 
									 WHERE comment_id = '".$DB->escape_str($comment_id)."'");
			
				/*  
				Find all of the unique commenters for this entry that have
				notification turned on, posted at/before this comment
				and do not have the same email address as this comment. 
				*/
				
				$results = $DB->query("SELECT DISTINCT(email), name, comment_id 
									   FROM exp_comments 
									   WHERE status = 'o' 
									   AND entry_id = '".$DB->escape_str($query->row['entry_id'])."'
									   AND notify = 'y'
									   AND email != '".$DB->escape_str($query->row['email'])."'
									   AND comment_date <= '".$DB->escape_str($query->row['comment_date'])."'");
									   
				$recipients = array();
				
				if ($results->num_rows > 0)
				{
					foreach ($results->result as $row)
					{
						$recipients[] = array($row['email'], $row['comment_id'], $row['name']);   
					}
				}
		
				$email_msg = '';
						
				if (count($recipients) > 0)
				{	
					$comment = $TYPE->parse_type( $query->row['comment'], 
												   array(
															'text_format'   => 'none',
															'html_format'   => 'none',
															'auto_links'    => 'n',
															'allow_img_url' => 'n'
														)
												);
				
					$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
		
					$action_id  = $FNS->fetch_action_id('Comment_CP', 'delete_comment_notification');
					
					$results = $DB->query("SELECT wt.title, wt.url_title, w.blog_title, w.comment_url, w.blog_url
										   FROM exp_weblog_titles wt, exp_weblogs w 
										   WHERE wt.entry_id = '".$DB->escape_str($query->row['entry_id'])."'
										   AND wt.weblog_id = w.weblog_id");
					
					$com_url = ($results->row['comment_url'] == '') ? $results->row['blog_url'] : $results->row['comment_url'];
							
					$swap = array(
									'name_of_commenter'			=> $query->row['name'],
									'name'						=> $query->row['name'],
									'weblog_name'				=> $results->row['blog_title'],
									'entry_title'				=> $results->row['title'],
									'site_name'					=> stripslashes($PREFS->ini('site_name')),
									'site_url'					=> $PREFS->ini('site_url'),
									'comment'					=> $comment,
									'comment_id'				=> $comment_id,
									'comment_url'				=> $FNS->remove_double_slashes($com_url.'/'.$results->row['url_title'].'/')
								 );
					
					$template = $FNS->fetch_email_template('comment_notification');
					$email_tit = $FNS->var_swap($template['title'], $swap);
					$email_msg = $FNS->var_swap($template['data'], $swap);
		
					/** ----------------------------
					/**  Send email
					/** ----------------------------*/
					
					if ( ! class_exists('EEmail'))
					{
						require PATH_CORE.'core.email'.EXT;
					}
					
					$email = new EEmail;
					$email->wordwrap = true;
	
					$sent = array();
	
					foreach ($recipients as $val)
					{
						if ( ! in_array($val['0'], $sent))
						{
							$title	 = $email_tit;
							$message = $email_msg;
						
							// Deprecate the {name} variable at some point
							$title	 = str_replace('{name}', $val['2'], $title);
							$message = str_replace('{name}', $val['2'], $message);
	
							$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
							$message = str_replace('{name_of_recipient}', $val['2'], $message);
						
						
							$title	 = str_replace('{notification_removal_url}', $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $title);
							$message = str_replace('{notification_removal_url}', $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $message);
						
							$email->initialize();
							$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
							$email->to($val['0']); 
							$email->subject($title);	
							$email->message($REGX->entities_to_ascii($message));		
							$email->Send();
		
							$sent[] = $val['0'];
						}
					}            
				}
			}		
		}	
				
		$FNS->clear_caching('all');		
		
		$val = ($IN->GBL('validate') == 1) ? AMP.'validate=1' : '';
		
		if ($IN->GBL('search_in') !== FALSE)
		{        									   
			$url = BASE.AMP.'C=edit'.
						AMP.'M=view_entries'.
						AMP.'search_in=comments'.
						AMP.'rownum='.$IN->GBL('current_page').
						AMP.'order=desc'.
						AMP.'keywords='.$IN->GBL('keywords');
		}
		else
		{
			$url = BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id.AMP.'current_page='.$current_page.AMP.'U=1'.$val;
		}
		
		$FNS->redirect($url);
		exit;
    }
    /* END */


    /** -----------------------------------------
    /**  Delete comment/trackback
    /** -----------------------------------------*/

    function delete_comment()
    {
        global $IN, $DSP, $DB, $LANG, $SESS, $FNS, $STAT, $EXT;
       
        $comment_id   = $IN->GBL('comment_ids');
        $trackback_id = $IN->GBL('trackback_ids');
        
        if ($trackback_id == FALSE AND $comment_id == FALSE)
        {
            return $DSP->no_access_message();
        }
        

        if ($comment_id != FALSE)
        {
        	if ( ! preg_match("/^[0-9]+$/", str_replace('|', '', $comment_id)))
        	{
        		return $DSP->no_access_message();
        	}
        
            $sql = "SELECT exp_weblog_titles.author_id, exp_weblog_titles.entry_id, exp_weblog_titles.weblog_id, exp_weblog_titles.comment_total
                    FROM   exp_weblog_titles, exp_comments
                    WHERE  exp_weblog_titles.entry_id = exp_comments.entry_id
                    AND    exp_comments.comment_id IN ('".str_replace('|', "','", $DB->escape_str($comment_id))."')";
        }
        else
        {
        	if ( ! is_numeric(str_replace('|', '', $trackback_id)))
        	{
        		return $DSP->no_access_message();
        	}
        
            $sql = "SELECT exp_weblog_titles.author_id, exp_trackbacks.entry_id, exp_weblog_titles.weblog_id, exp_weblog_titles.trackback_total
                    FROM   exp_weblog_titles, exp_trackbacks
                    WHERE  exp_weblog_titles.entry_id = exp_trackbacks.entry_id
                    AND    exp_trackbacks.trackback_id IN ('".str_replace('|', "','", $DB->escape_str($trackback_id))."')";
        }
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }
        
        $entry_ids  = array();
        $author_ids = array();
        $weblog_ids = array();
        
        foreach($query->result as $row)
        {
        	$entry_ids[]  = $row['entry_id'];
        	$author_ids[] = $row['author_id'];
        	$weblog_ids[] = $row['weblog_id'];
        }
        
        $entry_ids  = array_unique($entry_ids);
        $author_ids = array_unique($author_ids);
        $weblog_ids = array_unique($weblog_ids);
        
        /** -------------------------------
        /**  Validation Checks
        /** -------------------------------*/
        		
        if ($IN->GBL('validate') == 1)
        {
			if ( ! $DSP->allowed_group('can_moderate_comments'))
			{
				return $DSP->no_access_message();
			}   
			
			$sql = "SELECT COUNT(*) AS count 
					FROM exp_comments, exp_weblogs
					WHERE comment_id IN ('".str_replace('|', "','", $DB->escape_str($comment_id))."') ";
						
			$sql .= (USER_BLOG !== FALSE) ? "AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' " : "AND exp_weblogs.is_user_blog = 'n' ";
						
			$query = $DB->query($sql);			
	
			if ($query->row['count'] == 0)
			{
				return $DSP->no_access_message();
			}						
        }
		else
		{
			if ( ! $DSP->allowed_group('can_delete_all_comments'))
			{
				if ( ! $DSP->allowed_group('can_delete_own_comments'))
				{     
					return $DSP->no_access_message();
				}
				else
				{
					foreach($query->result as $row)
					{
						if ($row['author_id'] != $SESS->userdata('member_id'))
						{
							return $DSP->no_access_message();
						}
					}
				}
			}
		}
		
		/** --------------------------------
		/**  Update Entry and Weblog Stats
		/** --------------------------------*/
       
		if ($comment_id != FALSE)
		{
            $DB->query("DELETE FROM exp_comments WHERE comment_id IN ('".str_replace('|', "','", $DB->escape_str($comment_id))."')");
            
			foreach($entry_ids as $entry_id)
			{
            	$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$DB->escape_str($entry_id)."'");
            
            	$comment_date = ($query->num_rows == 0 OR !is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
            
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$DB->escape_str($entry_id)."' AND status = 'o'");
            
            	$DB->query("UPDATE exp_weblog_titles SET comment_total = '".($query->row['count'])."', recent_comment_date = '$comment_date' WHERE entry_id = '".$DB->escape_str($entry_id)."'");      
            }
            
        	// Quicker and updates just the weblogs
        	foreach($weblog_ids as $weblog_id) { $STAT->update_comment_stats($weblog_id, '', FALSE); }
        	
        	// Updates the total stats
        	$STAT->update_comment_stats();
        	
        	foreach($author_ids as $author_id)
        	{
        		$res = $DB->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
				$comment_total = $res->row['comment_total'];
				$comment_date  = (!empty($res->row['comment_date'])) ? $res->row['comment_date'] : 0;
					
				$DB->query($DB->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));   
        	}
        	
            $msg = $LANG->line('comment_deleted');            
        }
        else
        {
            $DB->query("DELETE FROM exp_trackbacks WHERE trackback_id IN ('".str_replace('|', "','", $DB->escape_str($trackback_id))."')");
            
            foreach($entry_ids as $entry_id)
			{	
				$query = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks WHERE entry_id = '".$DB->escape_str($entry_id)."'");
            
            	$trackback_date = ($query->num_rows == 0 OR !is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
            
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE entry_id = '".$DB->escape_str($entry_id)."'");
            
            	$DB->query("UPDATE exp_weblog_titles SET trackback_total = '".($query->row['count'])."', recent_trackback_date = '$trackback_date' WHERE entry_id = '$entry_id'");      
			}
			
			foreach($weblog_ids as $weblog_id) { $STAT->update_trackback_stats($weblog_id); }
			
           	$msg = $LANG->line('trackback_deleted');
        }
        
        // -------------------------------------------
        // 'delete_comment_additional' hook.
        //  - Add additional processing on comment delete
        //
        	$edata = $EXT->call_extension('delete_comment_additional');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
		$FNS->clear_caching('all');		
        
		if ($IN->GBL('validate', 'POST') == 1)
		{
			$FNS->redirect(BASE.AMP.'C=edit'.AMP.'M=view_comments'.AMP.'validate=1');
			exit;
		}		        

        $message = $DSP->qdiv('success', $msg);
        
        if ($IN->GBL('keywords') !== FALSE)
        {
        	$url = BASE.AMP.'C=edit'.
						AMP.'M=view_entries'.
						AMP.'search_in='.(($comment_id != FALSE) ? 'comments' : 'trackbacks').
						AMP.'rownum='.$IN->GBL('current_page').
						AMP.'order=desc'.
						AMP.'keywords='.$IN->GBL('keywords');
			
			$FNS->redirect($url);
        	exit;   
        }
        elseif ($comment_id != FALSE)
        {
        	$this->view_comments($weblog_id, $entry_id, $message);
        }
		else
		{
			$this->view_trackbacks($weblog_id, $entry_id, $message);
		}
    }
    /* END */
    
    /** -----------------------------------------
    /**  Base IFRAME for Spell Check
    /** -----------------------------------------*/

    function spellcheck_iframe()
    {
    	global $DSP;
    	
		if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::iframe($DSP->fetch_stylesheet());
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  Spell Check for Textareas
    /** -----------------------------------------*/

    function spellcheck()
    {
    	if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::check();
	}
	/* END */
	
	
	
  
    /** -----------------------------------------
    /**  Emoticons window - used with the stand-alone entry form
    /** -----------------------------------------*/

    function emoticons()
    {
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        
        if ( ! $field_group = $IN->GBL('field_group', 'GET'))
        {
            return;
        }
        
        
        if ( ! is_file(PATH_MOD.'emoticon/emoticons'.EXT))
        {
            return $DSP->error_message($LANG->line('no_emoticons'));        
        }
        else
        {
            require PATH_MOD.'emoticon/emoticons'.EXT;
        }
        
        if ( ! is_array($smileys))
        {
            return;
        }
        
        
        $path = $PREFS->ini('emoticon_path', 1);
        
        $query = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = '".$field_group."' AND field_type != 'rel' AND field_type != 'date' AND field_type != 'select' ORDER BY field_order");
        
        if ($query->num_rows == 0)
        {
            return $DSP->error_message($LANG->line('no_entry_fields'));        
        }
                
        ob_start();
        
        ?>     
        
        <script type="text/javascript"> 
        <!--

        function add_smiley(smiley)
        {
            var  form = document.forms[0];  
        <?php
        
        $n = 0;
        
        foreach ($query->result as $row)
        {
			$js_element = ($query->num_rows > 1) ? "[{$n}]" : '';
        ?>

            if (form.which<?php echo $js_element; ?>.checked) 
            {
                opener.document.getElementById('entryform').field_id_<?php echo $row['field_id']; ?>.value += " " + smiley + " ";
                window.close();
                opener.window.document.getElementById('entryform').field_id_<?php echo $row['field_id']; ?>.focus();
            }
        <?php
            
            $n++;
         }
         ?>
        }
        
        //-->
        </script>
        
        <?php

        $javascript = ob_get_contents();
        
        ob_end_clean();
        
        
        $r = $javascript;
        
        $r .= $DSP->heading($LANG->line('emoticons'));
        
        $r .= $DSP->qdiv('', BR.$LANG->line('choose_a_destination_for_emoticon').BR.BR);
        
        $r .= "<form name='upload' method='post' action='' >";        
        
        $i = 1;
        
        foreach ($query->result as $row)
        {
            $selected = ($i == 1) ? 1 : 0;
        
            $r .= $DSP->qdiv('', $DSP->input_radio('which', 'field_id_'.$row['field_id'],  $selected).NBS.$row['field_label']);
        
            $i++;
        }
        
        $r .= $DSP->qdiv('', BR.$LANG->line('click_emoticon').BR.BR);
        
        
        $r .= $DSP->table('', '0', '10', '100%');
        
        $i = 1;
        
        $dups = array();
        
        foreach ($smileys as $key => $val)
        {
            if ($i == 1)
            {
                $r .= "<tr>\n";                
            }
            
            if (in_array($smileys[$key]['0'], $dups))
            	continue;
            
            $r .= "<td><a href=\"#\" onclick=\"return add_smiley('".$key."');\"><img src=\"".$path.$smileys[$key]['0']."\" width=\"".$smileys[$key]['1']."\" height=\"".$smileys[$key]['2']."\" alt=\"".$smileys[$key]['3']."\" border=\"0\" /></a></td>\n";

			$dups[] = $smileys[$key]['0'];

            if ($i == 8)
            {
                $r .= "</tr>\n";                
                
                $i = 1;
            }
            else
            {
                $i++;
            }      
        }
        
        $r = rtrim($r);
                
        if (substr($r, -5) != "</tr>")
        {
            $r .= "</tr>\n";
        }
        
        $r .= $DSP->table_c();
        
        $r .= "</form>";
        
        $DSP->body  = $r;   
        $DSP->title = $LANG->line('file_upload');
    }
    /* END */
  
}
// END CLASS
?>