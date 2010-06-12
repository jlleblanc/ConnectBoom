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
 File: mod.weblog_standalone.php
-----------------------------------------------------
 Purpose: Weblog_standalone class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Weblog_standalone extends Weblog {

	var $categories = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Weblog_standalone()
    { 
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
        global $IN, $FNS, $OUT, $LANG, $SESS, $LOC, $EXT, $PREFS;

		$LANG->fetch_language_file('weblog');
		$LANG->fetch_language_file('publish');
		
		// Ya gotta be logged-in billy bob...

		if ($SESS->userdata('member_id') == 0) 
      	{ 
            return $OUT->show_user_error('general', $LANG->line('weblog_must_be_logged_in'));        
      	}

		/** ----------------------------------------
		/**  Prep data for insertion
		/** ----------------------------------------*/

		if ( ! $IN->GBL('preview', 'POST'))
		{
			unset($_POST['hidden_pings']);
			unset($_POST['status_id']);
			unset($_POST['allow_cmts']);
			unset($_POST['allow_tbks']);
			unset($_POST['sticky_entry']);
		
			if ( ! $IN->GBL('entry_date', 'POST'))
			{
				$_POST['entry_date'] = $LOC->set_human_time($LOC->now);
			}
			
			if ( ! class_exists('Display'))
			{
				require PATH_CP.'cp.display'.EXT;
			}
			
			global $DSP;
			
			$DSP = new Display();
			
			// -------------------------------------------
        	// 'weblog_standalone_insert_entry' hook.
        	//  - Modify any of the POST data for a stand alone entry insert
        	//
        		$edata = $EXT->call_extension('weblog_standalone_insert_entry');
        		if ($EXT->end_script === TRUE) return;
        	//
        	// -------------------------------------------
			
			if ( ! class_exists('Publish'))
			{
				require PATH_CP.'cp.publish'.EXT;
			}
					
			$PB = new Publish();
			
			$PB->assign_cat_parent = ($PREFS->ini('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
			
			return $PB->submit_new_entry(FALSE);
		
		} // END Insert
		
		/** ----------------------------------------
		/**  Preview Entry
		/** ----------------------------------------*/
       
        if ($IN->GBL('PRV', 'POST') == '')
        {
			$LANG->fetch_language_file('weblog');
        
            return $OUT->show_user_error('general', $LANG->line('weblog_no_preview_template'));        
        }
      
		$FNS->clear_caching('all', $_POST['PRV']);
		
		// -------------------------------------------
		// 'weblog_standalone_preview_entry' hook.
		//  - Modify any of the POST data for a stand alone entry preview
		//
			$edata = $EXT->call_extension('weblog_standalone_preview_entry');
			if ($EXT->end_script === TRUE) return;
		//
		// -------------------------------------------
        
        require PATH_CORE.'core.template'.EXT;
        
        global $TMPL;
        
        $TMPL = new Template();
        
		$preview = ( ! $IN->GBL('PRV', 'POST')) ? '' : $IN->GBL('PRV');
		
		if (strpos($preview, '/') === FALSE)
		{
			return FALSE;
		}

		$ex = explode("/", $preview);

		if (count($ex) != 2)
        		return FALSE;

        $TMPL->run_template_engine($ex['0'], $ex['1']);
	}
	/* END */


    /** ----------------------------------------
    /**  Stand-alone version of the entry form
    /** ----------------------------------------*/
    
    function entry_form($return_form = FALSE, $captcha = '')
    {
        global $TMPL, $LANG, $LOC, $OUT, $DB, $IN, $REGX, $FNS, $SESS, $PREFS, $EXT;
        
        $field_data	= '';
        $catlist	= '';
        $status		= '';
        $title		= '';
        $url_title	= '';
        $dst_enabled = $SESS->userdata('daylight_savings');
      
		$LANG->fetch_language_file('weblog');
		
		// No loggy? No looky...
		
		if ($SESS->userdata('member_id') == 0) 
      	{
            return '';    
      	}
      	
		if ( ! $weblog = $TMPL->fetch_param('weblog'))
		{
			return $OUT->show_user_error('general', $LANG->line('weblog_not_specified'));        
      	}
      	
      	// Fetch the action ID number.  Even though we don't need it until later
      	// we'll grab it here.  If not found it means the action table doesn't
      	// contain the ID, which means the user has not updated properly.  Ya know?
      	
      	if ( ! $insert_action = $FNS->fetch_action_id('Weblog', 'insert_new_entry'))
      	{
			return $OUT->show_user_error('general', $LANG->line('weblog_no_action_found'));        
      	}
      	
        // We need to first determine which weblog to post the entry into.

        $assigned_weblogs = $FNS->fetch_assigned_weblogs();
        
        $weblog_id = ( ! $IN->GBL('weblog_id', 'POST')) ? '' : $IN->GBL('weblog_id');

		if ($weblog_id == '')
		{			
			$query = $DB->query("SELECT weblog_id from exp_weblogs WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') AND blog_name = '".$DB->escape_str($weblog)."' AND is_user_blog = 'n'");
	
			if ($query->num_rows == 1)
			{
				$weblog_id = $query->row['weblog_id'];
			}
		}
		
        
        /** ----------------------------------------------
        /**  Security check
        /** ---------------------------------------------*/
                
        if ( ! in_array($weblog_id, $assigned_weblogs))
        { 
        	return $TMPL->no_results();
        }

        /** ----------------------------------------------
        /**  Fetch weblog preferences
        /** ---------------------------------------------*/
                
        $query = $DB->query("SELECT * FROM  exp_weblogs WHERE weblog_id = '$weblog_id'");     
                
        if ($query->num_rows == 0)
        {
            return "The weblog you have specified does not exist.";
        }

        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        if ( ! isset($_POST['weblog_id']))
        {
        	$title		= $default_entry_title;
        	$url_title	= $url_title_prefix;
        }
	
		// -------------------------------------------
		// 'weblog_standalone_form_start' hook.
		//  - Rewrite the Stand Alone Entry Form completely
		//
			$edata = $EXT->call_extension('weblog_standalone_form_start', $return_form, $captcha, $weblog_id);
			if ($EXT->end_script === TRUE) return;
		//
		// -------------------------------------------

        /** ----------------------------------------
        /**  Return the "no cache" version of the form
        /** ----------------------------------------*/

        if ($return_form == FALSE)
        {
    		$nc = '{{NOCACHE_WEBLOG_FORM ';
    		
    		if (count($TMPL->tagparams) > 0)
    		{
    			foreach ($TMPL->tagparams as $key => $val)
    			{
    				$nc .= ' '.$key.'="'.$val.'" ';
    			}
    		}
    		
    		$nc .= '}}'.$TMPL->tagdata.'{{/NOCACHE_FORM}}';
    		
    		return $nc;
        }
                        
                
        /** ----------------------------------------------
        /**  JavaScript For URL Title
        /** ---------------------------------------------*/
        
        $convert_ascii = ($PREFS->ini('auto_convert_high_ascii') == 'y') ? TRUE : FALSE;        
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
        
		$default_entry_title = $REGX->form_prep($default_entry_title);
		
        $url_title_js = <<<EOT
        <script type="text/javascript"> 
        <!--
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
			
			if (document.getElementById("url_title"))
			{
				document.getElementById("url_title").value = "{$url_title_prefix}" + NewText;			
			}
			else
			{
				document.forms['entryform'].elements['url_title'].value = "{$url_title_prefix}" + NewText; 
			}		
		}


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
		
		
		-->
		</script>
EOT;

		// -------------------------------------------
		// 'weblog_standalone_form_urltitle_js' hook.
		//  - Rewrite the Stand Alone Entry Form's URL Title JavaScript
		//
			if ($EXT->active_hook('weblog_standalone_form_urltitle_js') === TRUE)
			{
				$url_title_js = $EXT->call_extension('weblog_standalone_form_urltitle_js', $url_title_js);
				if ($EXT->end_script === TRUE) return;
			}
		//
		// -------------------------------------------


		$LANG->fetch_language_file('publish');


        /** ----------------------------------------
        /**  Compile form declaration and hidden fields
        /** ----------------------------------------*/
        
        $RET = (isset($_POST['RET'])) ? $_POST['RET'] : $FNS->fetch_current_uri();
        $XID = ( ! isset($_POST['XID'])) ? '' : $_POST['XID'];        
        $PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : '{PREVIEW_TEMPLATE}';

        $hidden_fields = array(
                                'ACT'      				=> $insert_action,
                                'RET'      				=> $RET,
                                'PRV'      				=> $PRV,
                                'URI'      				=> ($IN->URI == '') ? 'index' : $IN->URI,
                                'XID'      				=> $XID,
                                'return_url'			=> (isset($_POST['return_url'])) ? $_POST['return_url'] : $TMPL->fetch_param('return'),
                                'author_id'				=> $SESS->userdata('member_id'),
                                'weblog_id'				=> $weblog_id
                              );
                              
        /** ----------------------------------------
        /**  Add status to hidden fields
        /** ----------------------------------------*/
                               
		$status_id = ( ! isset($_POST['status_id'])) ? $TMPL->fetch_param('status') : $_POST['status_id'];
		
		if ($status_id == 'Open' || $status_id == 'Closed')
			$status_id = strtolower($status_id);

		$status_query = $DB->query("SELECT * FROM exp_statuses WHERE group_id = '$status_group' order by status_order");

		if ($status_id != '')
		{	
			$closed_flag = TRUE;
		
			if ($status_query->num_rows > 0)
			{  			
				foreach ($status_query->result as $row)
				{
					if ($row['status'] == $status_id)
						$closed_flag = FALSE;
				}
			}
		
			$hidden_fields['status'] = ($closed_flag == TRUE) ? 'closed' : $status_id;
		}
		
	
        /** ----------------------------------------
        /**  Add "allow" options
        /** ----------------------------------------*/
                                       
		$allow_cmts = ( ! isset($_POST['allow_cmts'])) ? $TMPL->fetch_param('allow_comments') : $_POST['allow_cmts'];

		if ($allow_cmts != '' AND $comment_system_enabled == 'y')
		{		
			$hidden_fields['allow_comments'] = ($allow_cmts == 'yes') ? 'y' : 'n';
		}
		
		$allow_tbks = ( ! isset($_POST['allow_tbks'])) ? $TMPL->fetch_param('allow_trackbacks') : $_POST['allow_tbks'];

		if ($allow_tbks != '')
		{
			$hidden_fields['allow_trackbacks'] = ($allow_tbks == 'yes') ? 'y' : 'n';
		}
		
		$sticky_entry = ( ! isset($_POST['sticky_entry'])) ? $TMPL->fetch_param('sticky_entry') : $_POST['sticky_entry'];

		if ($sticky_entry != '')
		{
			$hidden_fields['sticky'] = ($sticky_entry == 'yes') ? 'y' : 'n';
		}
		
        /** ----------------------------------------
        /**  Add categories to hidden fields
        /** ----------------------------------------*/

		if ($category_id = $TMPL->fetch_param('category'))
		{
			if (isset($_POST['category']))
			{
				foreach ($_POST as $key => $val)
				{                
					if (strstr($key, 'category') AND is_array($val))
					{
						$i =0;
						foreach ($val as $v)
						{
							$hidden_fields['category['.($i++).']'] = $v;
						}
					}            
				}
			}
			else
			{
				if (strpos($category_id, '|') === FALSE)
				{
					$hidden_fields['category[]'] = $category_id;
				}
				else
				{	
					$category_id = trim($category_id, '|');
					
					$i = 0;
					foreach(explode("|", $category_id) as $val)
					{
						$hidden_fields['category['.($i++).']'] = $val;
					}
				}
			}
		}

        /** ----------------------------------------
        /**  Add pings to hidden fields
        /** ----------------------------------------*/
		
		$hidden_pings = ( ! isset($_POST['hidden_pings'])) ? $TMPL->fetch_param('hidden_pings') : $_POST['hidden_pings'];
		
		if ($hidden_pings == 'yes')
		{
			$hidden_fields['hidden_pings'] = 'yes';
		
			$ping_servers = $this->fetch_ping_servers('new');
		
			if (is_array($ping_servers) AND count($ping_servers) > 0)
			{
				$i = 0;
				foreach ($ping_servers as $val)
				{
					if ($val['1'] != '')
						$hidden_fields['ping['.($i++).']'] = $val['0'];
				}
			}
		}

		/** -------------------------------------
		/**  Parse out the tag
		/** -------------------------------------*/

		$tagdata = $TMPL->tagdata;
		
  
        /** ----------------------------------------------
        /**  Upload and Smileys Link
        /** ---------------------------------------------*/
			
		$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
		$cp_url = $PREFS->ini('cp_url').'?S='.$s;  
		
		// -------------------------------------------
        // 'weblog_standalone_form_upload_url' hook.
		//  - Rewrite URL for Upload Link
		//
			if ($EXT->active_hook('weblog_standalone_form_upload_url') === TRUE)
			{
				$upload_url = $EXT->call_extension('weblog_standalone_form_upload_url', $weblog_id);
			}
			else
			{
				$upload_url = $cp_url.'&amp;C=publish&amp;M=file_upload_form&amp;field_group='.$field_group.'&amp;Z=1';
			}
		//
		// -------------------------------------------
		
        $tagdata = str_replace('{upload_url}', $upload_url, $tagdata);
        $tagdata = str_replace('{smileys_url}', $cp_url.'&amp;C=publish&amp;M=emoticons&amp;field_group='.$field_group.'&amp;Z=1', $tagdata);
				
		// Onward...
		
		$which = ($IN->GBL('preview', 'POST')) ? 'preview' : 'new';
		
		
		/** --------------------------------
		/**  Fetch Custom Fields
		/** --------------------------------*/
		
		if ($TMPL->fetch_param('show_fields') !== FALSE)
		{
			if (strncmp('not ', $TMPL->fetch_param('show_fields'), 4) == 0)
			{
				$these = "AND field_name NOT IN ('".str_replace('|', "','", trim(substr($TMPL->fetch_param('show_fields'), 3)))."') ";
			}
			else
			{
				$these = "AND field_name IN ('".str_replace('|', "','", trim($TMPL->fetch_param('show_fields')))."') ";
			}
		}
		else
		{
			$these = '';
		}
		
		$query = $DB->query("SELECT * FROM  exp_weblog_fields WHERE group_id = '$field_group' $these ORDER BY field_order");
		
		$fields = array();
		$date_fields = array();
		$cond = array();
		
		if ($which == 'preview')
		{
			foreach ($query->result as $row)
			{
				$fields['field_id_'.$row['field_id']] = $row['field_name'];
				$cond[$row['field_name']] = '';
				
				if ($row['field_type'] == 'date')
				{
					$date_fields[$row['field_name']] = $row['field_id'];
				}
			}
		}

		/** ----------------------------------------
		/**  Preview
		/** ----------------------------------------*/
		
		if (preg_match("#".LD."preview".RD."(.+?)".LD.'/'."preview".RD."#s", $tagdata, $match))
		{									
			if ($which != 'preview')
			{  
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
			else
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
				
				$title = $TYPE->format_characters(stripslashes($IN->GBL('title', 'POST')));
					
				$match['1'] = str_replace(LD.'title'.RD, $title, $match['1']);
				
				// We need to grab each global array index and do a little formatting
				
				$str = '';
				
				foreach($_POST as $key => $val)
				{            
					if ( ! is_array($val))
					{
						if (strstr($key, 'field_id'))
						{
							$expl = explode('field_id_', $key);
							
							if (in_array($expl['1'], $date_fields))
							{
								$temp_date = $LOC->convert_human_date_to_gmt($_POST['field_id_'.$expl['1']]);
								$temp = $_POST['field_id_'.$expl['1']];								
								$cond[$fields['field_id_'.$expl['1']]] =  $temp_date;
							}
							else
							{
								$cond[$fields['field_id_'.$expl['1']]] =  $_POST['field_id_'.$expl['1']];

								$txt_fmt = ( ! isset($_POST['field_ft_'.$expl['1']])) ? 'xhtml' : $_POST['field_ft_'.$expl['1']];

								$temp = $TYPE->parse_type( stripslashes($val),
													 		array(
																'text_format'   => $txt_fmt,
																'html_format'   => $weblog_html_formatting,
																'auto_links'    => $weblog_allow_img_urls,
																'allow_img_url' => $weblog_auto_link_urls
														   		)
														);
							}
							
							if (isset($fields[$key]))
							{
								$match['1'] = str_replace(LD.$fields[$key].RD, $temp, $match['1']);
							}
													
							$str .= $temp;
						} 
					}
				}

				$match['1'] = str_replace(LD.'display_custom_fields'.RD, $str, $match['1']);
				$match['1'] = $FNS->prep_conditionals($match['1'], $cond);					
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
			}
		}
		
	
		/** -------------------------------------
		/**  Formatting buttons
		/** -------------------------------------*/

		if (preg_match("#".LD."formatting_buttons".RD."#s", $tagdata))
		{	
			if ( ! defined('BASE'))
			{		
				$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
				
				define('BASE', $PREFS->ini('cp_url', FALSE).'?S='.$s);  
			}
			
		
			if ( ! class_exists('Display'))
			{
				require PATH_CP.'cp.display'.EXT;
			}
			
			global $DSP;
			$DSP = new Display;
			
			if ( ! class_exists('Publish'))
			{
				require PATH_CP.'cp.publish'.EXT;
			}
			
			$PUB = new Publish;
		
			$tagdata = str_replace(LD.'formatting_buttons'.RD, str_replace(	'.entryform.', 
																			".getElementById('entryform').", 
																			$PUB->html_formatting_buttons($SESS->userdata('member_id'), 
																										  $field_group)), 
																										  $tagdata);
		}

		/** -------------------------------------
		/**  Fetch the {custom_fields} chunk
		/** -------------------------------------*/
		
		$custom_fields = '';

		if (preg_match("#".LD."custom_fields".RD."(.+?)".LD.'/'."custom_fields".RD."#s", $tagdata, $match))
		{
			$custom_fields = trim($match['1']);
		
			$tagdata = str_replace($match['0'], LD.'temp_custom_fields'.RD, $tagdata);
		}
				
		// If we have custom fields to show, generate them		
		
		if ($custom_fields != '')
		{
			$field_array = array('textarea', 'textinput', 'pulldown', 'date', 'relationship');
			
			$textarea 	= '';
			$textinput 	= '';
			$pulldown	= '';
			$date		= '';
			$relationship = '';
			$rel_options = '';
			$pd_options	= '';
			$required	= '';
			
			foreach ($field_array as $val)
			{
				if (preg_match("#".LD."\s*if\s+".$val.RD."(.+?)".LD.'/'."if".RD."#s", $custom_fields, $match))
				{
					$$val = $match['1'];
					
					if ($val == 'pulldown')
					{
						if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $pulldown, $pmatch))
						{
							$pd_options = $pmatch['1'];
							$pulldown = str_replace ($pmatch['0'], LD.'temp_pd_options'.RD, $pulldown);
						}
					}
				
					if ($val == 'relationship')
					{
						if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $relationship, $pmatch))
						{
							$rel_options = $pmatch['1'];
							$relationship = str_replace ($pmatch['0'], LD.'temp_rel_options'.RD, $relationship);
						}
					}
				
				
					$custom_fields = str_replace($match['0'], LD.'temp_'.$val.RD, $custom_fields);
				}
			}
			
			if (preg_match("#".LD."if\s+required".RD."(.+?)".LD.'/'."if".RD."#s", $custom_fields, $match))
			{			
				$required = $match['1'];
				
				$custom_fields = str_replace($match['0'], LD.'temp_required'.RD, $custom_fields);
			}			
			
			/** --------------------------------
			/**  Parse Custom Fields
			/** --------------------------------*/
						
			$build = '';
		
			foreach ($query->result as $row)
			{
				$temp_chunk = $custom_fields;
				$temp_field = '';
			
				switch ($which)
				{
					case 'preview' : 
							$field_data = ( ! isset( $_POST['field_id_'.$row['field_id']] )) ?  '' : $_POST['field_id_'.$row['field_id']];
							$field_fmt  = ( ! isset( $_POST['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $_POST['field_ft_'.$row['field_id']];
						break;
					case 'edit'    :
							$field_data = ( ! isset( $result->row['field_id_'.$row['field_id']] )) ? '' : $result->row['field_id_'.$row['field_id']];
							$field_fmt  = ( ! isset( $result->row['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $result->row['field_ft_'.$row['field_id']];
						break;
					default        :
							$field_data = '';
							$field_fmt  = $row['field_fmt'];
						break;
				}

										
				/** --------------------------------
				/**  Textarea field types
				/** --------------------------------*/

				if ($row['field_type'] == 'textarea' AND $textarea != '')
				{               									
					$temp_chunk = str_replace(LD.'temp_textarea'.RD, $textarea, $temp_chunk);
				}
				if ($row['field_type'] == 'text' AND $textinput != '')
				{								
					$temp_chunk = str_replace(LD.'temp_textinput'.RD, $textinput, $temp_chunk);
				}
				if ($row['field_type'] == 'rel')
				{		
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
					
					if ($relquery->num_rows > 0)
					{ 
						$relentry_id = '';
						if ( ! isset($_POST['field_id_'.$row['field_id']]))
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
						
						$temp_options = $rel_options;
						$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
						$pdo = $temp_options;
						foreach ($relquery->result as $relrow)
						{
							$temp_options = $rel_options;
							$temp_options = str_replace(LD.'option_name'.RD, $relrow['title'], $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, $relrow['entry_id'], $temp_options);
							$temp_options = str_replace(LD.'selected'.RD, ($relentry_id == $relrow['entry_id']) ? ' selected="selected"' : '', $temp_options);
							
							$pdo .= $temp_options;
						}
					
						$temp_relationship = str_replace(LD.'temp_rel_options'.RD, $pdo, $relationship);	
						$temp_chunk = str_replace(LD.'temp_relationship'.RD, $temp_relationship, $temp_chunk);
					}
				}
				if ($row['field_type'] == 'date' AND $date != '')
				{
					$temp_chunk = $custom_fields;
	
					$date_field = 'field_id_'.$row['field_id'];
					$date_local = 'field_dt_'.$row['field_id'];	
				
					$dtwhich = $which;
					if (isset($_POST[$date_field])) 
					{
						$field_data = $_POST[$date_field];
						$dtwhich = 'preview';
					}
	
					$custom_date = '';
					$localize = FALSE;
					if ($dtwhich != 'preview')
					{	
						$localize = TRUE;
					
						if ($field_data != '' AND isset($result->row['field_dt_'.$row['field_id']]) AND $result->row['field_dt_'.$row['field_id']] != '')
						{					
							$field_data = $LOC->offset_entry_dst($field_data, $dst_enabled);
							$field_data = $LOC->simpl_offset($field_data, $result->row['field_dt_'.$row['field_id']]);
							$localize = FALSE;
						}
					
						if ($field_data != '')
							$custom_date = $LOC->set_human_time($field_data, $localize);
						
						$cal_date = ($LOC->set_localized_time($custom_date) * 1000);
					}
					else
					{				
						$custom_date = $_POST[$date_field];
						$cal_date = ($custom_date != '') ? ($LOC->set_localized_time($LOC->convert_human_date_to_gmt($custom_date)) * 1000) : ($LOC->set_localized_time() * 1000);
					}
					
					$temp_chunk = str_replace(LD.'temp_date'.RD, $date, $temp_chunk);
					$temp_chunk = str_replace(LD.'date'.RD, $custom_date, $temp_chunk);
				}
				elseif ($row['field_type'] == 'select' AND $pulldown != '')
				{  
					if ($row['field_pre_populate'] == 'n')
					{				
						$pdo = '';
					
						if ($row['field_required'] == 'n')
						{
							$temp_options = $pd_options;
							$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
							$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
							$pdo = $temp_options;
						}
						
						foreach (explode("\n", trim($row['field_list_items'])) as $v)
						{  
							$temp_options = $pd_options;
						
							$v = trim($v);
							$temp_options = str_replace(LD.'option_name'.RD, $v, $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, $v, $temp_options);
							$temp_options = str_replace(LD.'selected'.RD, ($v == $field_data) ? ' selected="selected"' : '', $temp_options);
							
							$pdo .= $temp_options;
						}
							
						$temp_pulldown = str_replace(LD.'temp_pd_options'.RD, $pdo, $pulldown);
						$temp_chunk = str_replace(LD.'temp_pulldown'.RD, $temp_pulldown, $temp_chunk);
					}
					else
					{
						// We need to pre-populate this menu from an another weblog custom field
						$pop_query = $DB->query("SELECT field_id_".$row['field_pre_field_id']." FROM exp_weblog_data WHERE weblog_id = ".$row['field_pre_blog_id']."");
						
						if ($pop_query->num_rows > 0)
						{
							$temp_options = $rel_options;
							$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
							$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
							$pdo = $temp_options;

							foreach ($pop_query->result as $prow)
							{
								$pretitle = substr($prow['field_id_'.$row['field_pre_field_id']], 0, 110);
								$pretitle = preg_replace("/\r\n|\r|\n|\t/", ' ', $pretitle);
								$pretitle = $REGX->form_prep($pretitle);

								$temp_options = $rel_options;
								$temp_options = str_replace(LD.'option_name'.RD, $pretitle, $temp_options);
								$temp_options = str_replace(LD.'option_value'.RD, $REGX->form_prep($prow['field_id_'.$row['field_pre_field_id']]), $temp_options);
								$temp_options = str_replace(LD.'selected'.RD, ($prow['field_id_'.$row['field_pre_field_id']] == $field_data) ? ' selected="selected"' : '', $temp_options);								
								$pdo .= $temp_options;
							}
							
							$temp_relationship = str_replace(LD.'temp_rel_options'.RD, $pdo, $relationship);	
							$temp_chunk = str_replace(LD.'temp_relationship'.RD, $temp_relationship, $temp_chunk);
						}
					}						
				} 
				
				
				if ($row['field_required'] == 'y') 
				{
					$temp_chunk = str_replace(LD.'temp_required'.RD, $required, $temp_chunk);
				}
				else
				{
					$temp_chunk = str_replace(LD.'temp_required'.RD, '', $temp_chunk);
				}
				
				$temp_chunk = str_replace(LD.'field_data'.RD, $REGX->form_prep($field_data), $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_date'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_textarea'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_relationship'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_textinput'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_pulldown'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'temp_pd_options'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'calendar_link'.RD, '', $temp_chunk);
				$temp_chunk = str_replace(LD.'calendar_id'.RD, '', $temp_chunk);
				
				$temp_chunk = str_replace(LD.'rows'.RD, ( ! isset($row['field_ta_rows'])) ? '10' : $row['field_ta_rows'], $temp_chunk);
				$temp_chunk = str_replace(LD.'field_label'.RD, $row['field_label'], $temp_chunk);
				$temp_chunk = str_replace(LD.'field_instructions'.RD, $row['field_instructions'], $temp_chunk);
				$temp_chunk = str_replace(LD.'text_direction'.RD, $row['field_text_direction'], $temp_chunk);
				$temp_chunk = str_replace(LD.'maxlength'.RD, $row['field_maxl'], $temp_chunk);
				$temp_chunk = str_replace(LD.'field_name'.RD, 'field_id_'.$row['field_id'], $temp_chunk);
				
				$hidden_fields['field_ft_'.$row['field_id']] = $field_fmt;
				// $temp_chunk .= "\n<input type='hidden' name='field_ft_".$row['field_id']."' value='".$field_fmt."' />\n";
	
				$build .= $temp_chunk;
			}
			
			$tagdata = str_replace(LD.'temp_custom_fields'.RD, stripslashes($build), $tagdata);
		}
		
		
		/** ----------------------------------------
		/**  Categories
		/** ----------------------------------------*/
		
		if (preg_match("#".LD."category_menu".RD."(.+?)".LD.'/'."category_menu".RD."#s", $tagdata, $match))
		{									
			// -------------------------------------------
        	// 'weblog_standalone_form_category_menu' hook.
			//  - Rewrite the displaying of categories, if you dare!
			//
				if ($EXT->active_hook('weblog_standalone_form_category_menu') === TRUE)
				{
					$edata = $EXT->call_extension('weblog_standalone_form_category_menu', $cat_group, $which, $deft_category, $catlist);
					
					$match['1'] = str_replace(LD.'select_options'.RD, $edata, $match['1']);
					$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
					
					if ($EXT->end_script === TRUE) return;
				}	
				else
				{
				
					$this->category_tree_form($cat_group, $which, $deft_category, $catlist);			
					
					if (count($this->categories) == 0)
					{  
						$tagdata = str_replace ($match['0'], '', $tagdata);
					}
					else
					{   
						$c = '';
						foreach ($this->categories as $val)
						{
							$c .= $val;
						}
										
						$match['1'] = str_replace(LD.'select_options'.RD, $c, $match['1']);
						$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
					}
				}
			//
			// -------------------------------------------
		}


		/** ----------------------------------------
		/**  Ping Servers
		/** ----------------------------------------*/
		
		if (preg_match("#".LD."ping_servers".RD."(.+?)".LD.'/'."ping_servers".RD."#s", $tagdata, $match))
		{
			$field = (preg_match("#".LD."ping_row".RD."(.+?)".LD.'/'."ping_row".RD."#s", $tagdata, $match1)) ? $match1['1'] : '';

			if ( ! isset($match1['0']))
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
		
       		$ping_servers = $this->fetch_ping_servers($which);

			if ( ! is_array($ping_servers) OR count($ping_servers) == 0)
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
			else
			{   
				$ping_build = '';
			
				foreach ($ping_servers as $val)
				{
					$temp = $field;
					
					$temp = str_replace(LD.'ping_value'.RD, $val['0'], $temp);						
					$temp = str_replace(LD.'ping_checked'.RD, $val['1'], $temp);
					$temp = str_replace(LD.'ping_server_name'.RD, $val['2'], $temp);
					
					$ping_build .= $temp;
				}
									
				$match['1'] = str_replace ($match1['0'], $ping_build, $match['1']);
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
			}
		}




		/** ----------------------------------------
		/**  Status
		/** ----------------------------------------*/
		
		if (preg_match("#".LD."status_menu".RD."(.+?)".LD.'/'."status_menu".RD."#s", $tagdata, $match))
		{
			if (isset($_POST['status']))
				$deft_status = $_POST['status'];
			
			if ($deft_status == '')
				$deft_status = 'open';
			
			if ($status == '') 
				$status = $deft_status;
							  
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
								
				$r = '';
				
				if ($status_query->num_rows == 0)
				{
					// if there is no status group assigned, only Super Admins can create 'open' entries
					if ($SESS->userdata['group_id'] == 1)
					{
						$selected = ($status == 'open') ? " selected='selected'" : '';
						$r .= "<option value='open'".$selected.">".$LANG->line('open')."</option>";						
					}
					
					$selected = ($status == 'closed') ? " selected='selected'" : '';
					$r .= "<option value='closed'".$selected.">".$LANG->line('closed')."</option>";
				}
				else
				{        		
					$no_status_flag = TRUE;
				
					foreach ($status_query->result as $row)
					{					
						$selected = ($status == $row['status']) ? " selected='selected'" : '';
						
						if ($selected != 1)
						{
							if (in_array($row['status_id'], $no_status_access))
							{
								continue;                
							}
						}
						
						$no_status_flag = FALSE;
						
						$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $LANG->line($row['status']) : $row['status'];
																
						$r .= "<option value='".$REGX->form_prep($row['status'])."'".$selected.">". $REGX->form_prep($status_name)."</option>\n";					
					}
					
					if ($no_status_flag == TRUE)
					{
						$tagdata = str_replace ($match['0'], '', $tagdata);
					}
				}


				$match['1'] = str_replace(LD.'select_options'.RD, $r, $match['1']);
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
		}

		
		/** ----------------------------------------
		/**  Trackback field
		/** ----------------------------------------*/
		
		if (preg_match("#".LD."if\s+trackback".RD."(.+?)".LD.'/'."if".RD."#s", $tagdata, $match))
		{			
			if ($show_trackback_field == 'n')
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
			else
			{			
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
			}
		}
		
		/** ----------------------------------------
		/**  Parse single variables
		/** ----------------------------------------*/
	
        foreach ($TMPL->var_single as $key => $val)
        {              
            /** ----------------------------------------
            /**  {title}
            /** ----------------------------------------*/
            
            if ($key == 'title')
            {
                $title = ( ! isset($_POST['title'])) ? $title : stripslashes($_POST['title']);

                $tagdata = $TMPL->swap_var_single($key, $REGX->form_prep($title), $tagdata);
            }

            /** ----------------------------------------
            /**  {allow_comments}
            /** ----------------------------------------*/
            
            if ($key == 'allow_comments')
            {
				if ($which == 'preview')
				{
					$checked = ( ! isset($_POST['allow_comments']) || $comment_system_enabled != 'y') ? '' : "checked='checked'";
				}
				else
				{
					$checked = ($deft_comments == 'n' || $comment_system_enabled != 'y') ? '' : "checked='checked'";
				}
				
                $tagdata = $TMPL->swap_var_single($key, $checked, $tagdata);
            }

            /** ----------------------------------------
            /**  {allow_trackbacks}
            /** ----------------------------------------*/
            
            if ($key == 'allow_trackbacks')
            {
				if ($which == 'preview')
				{
					$checked = ( ! isset($_POST['allow_trackbacks']) || $trackback_system_enabled != 'y') ? '' : "checked='checked'";
				}
				else
				{
					$checked = ($deft_trackbacks == 'n' || $trackback_system_enabled != 'y') ? '' : "checked='checked'";
				}
				
                $tagdata = $TMPL->swap_var_single($key, $checked, $tagdata);
            }
            
            /** ----------------------------------------
            /**  {dst_enabled}
            /** ----------------------------------------*/
            
            if ($key == 'dst_enabled')
            {
				if ($which == 'preview')
				{
					$checked = (isset($_POST['dst_enabled']) && $PREFS->ini('honor_entry_dst') == 'y') ? "checked='checked'" : '';
				}
				else
				{
					$checked = ($dst_enabled == 'y') ? "checked='checked'" : '';
				}
				
                $tagdata = $TMPL->swap_var_single($key, $checked, $tagdata);
            }

            /** ----------------------------------------
            /**  {sticky}
            /** ----------------------------------------*/
            
            if ($key == 'sticky')
            {
            	$checked = '';
            		
				if ($which == 'preview')
				{
					$checked = ( ! isset($_POST['sticky'])) ? '' : "checked='checked'";
				}
				
                $tagdata = $TMPL->swap_var_single($key, $checked, $tagdata);
            }

            /** ----------------------------------------
            /**  {url_title}
            /** ----------------------------------------*/

            if ($key == 'url_title')
            {
                $url_title = ( ! isset($_POST['url_title'])) ? $url_title : $_POST['url_title'];

                $tagdata = $TMPL->swap_var_single($key, $url_title, $tagdata);
            }
            
            /** ----------------------------------------
            /**  {entry_date}
            /** ----------------------------------------*/

            if ($key == 'entry_date')
            {
                $entry_date = ( ! isset($_POST['entry_date'])) ? $LOC->set_human_time($LOC->now) : $_POST['entry_date'];

                $tagdata = $TMPL->swap_var_single($key, $entry_date, $tagdata);
            }
                        
            /** ----------------------------------------
            /**  {expiration_date}
            /** ----------------------------------------*/

            if ($key == 'expiration_date')
            {
                $expiration_date = ( ! isset($_POST['expiration_date'])) ? '': $_POST['expiration_date'];
                
                $tagdata = $TMPL->swap_var_single($key, $expiration_date, $tagdata);
            }

            /** ----------------------------------------
            /**  {comment_expiration_date}
            /** ----------------------------------------*/

            if ($key == 'comment_expiration_date')
            {
             	$comment_expiration_date = '';
             
				if ($which == 'preview')
				{
                		$comment_expiration_date = ( ! isset($_POST['comment_expiration_date'])) ? '' : $_POST['comment_expiration_date'];
				}
				else
				{
					if ($comment_expiration > 0)
					{
						$comment_expiration_date = $comment_expiration * 86400;
						$comment_expiration_date = $comment_expiration_date + $LOC->now;
						$comment_expiration_date = $LOC->set_human_time($comment_expiration_date);
					}
            	}

                $tagdata = $TMPL->swap_var_single($key, $comment_expiration_date, $tagdata);
            }
            
            
            /** ----------------------------------------
            /**  {trackback_urls}
            /** ----------------------------------------*/

            if ($key == 'trackback_urls')
            {
                $trackback_urls = ( ! isset($_POST['trackback_urls'])) ? '' : stripslashes($_POST['trackback_urls']);

                $tagdata = $TMPL->swap_var_single($key, $trackback_urls, $tagdata);
            }
               
		}             
		
		// -------------------------------------------
        // 'weblog_standalone_form_end' hook.
		//  - Allows adding to end of submission form
		//
			if ($EXT->active_hook('weblog_standalone_form_end') === TRUE)
			{
				$tagdata = $EXT->call_extension('weblog_standalone_form_end', $tagdata);
			}	
		//
		// -------------------------------------------
        
        // Build the form
        
        $data = array(
        				'hidden_fields' => $hidden_fields,
        				'action'		=> $RET,
        				'id'			=> 'entryform'
        				);
                                         
        $res  = $FNS->form_declaration($data);

		if ($TMPL->fetch_param('use_live_url') != 'no')
		{
 			$res .= $url_title_js;
		}
		
        $res .= $tagdata;
        $res .= "</form>"; 
		
		return $res;
    }
    /* END */




    
    /** -----------------------------
    /**  Category tree
    /** -----------------------------*/
    // This function (and the next) create a higherarchy tree
    // of categories.

    function category_tree_form($group_id = '', $action = '', $default = '', $selected = '')
    {  
        global $IN, $REGX, $DB;
  
        // Fetch category group ID number
      
        if ($group_id == '')
        {        
            if ( ! $group_id = $IN->GBL('group_id'))
                return false;
        }
        
        // If we are using the category list on the "new entry" page
        // we need to gather the selected categories so we can highlight
        // them in the form.
        
        if ($action == 'preview')
        {
            $catarray = array();
        
            foreach ($_POST as $key => $val)
            {                
                if (strstr($key, 'category') AND is_array($val))
                {
                		foreach ($val as $k => $v)
                		{
                    		$catarray[$v] = $v;
                		}
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
        
        $query = $DB->query("SELECT cat_name, cat_id, parent_id
                             FROM exp_categories 
                             WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($group_id))."') 
                             ORDER BY parent_id, cat_order");
              
        if ($query->num_rows == 0)
        { 
            return false;
        }  
        
        // Assign the query result to a multi-dimensional array
                    
        foreach($query->result as $row)
        {        
            $cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name']);
        }
	
		$size = count($cat_array) + 1;
			        
        // Build our output...
        
        $sel = '';

        foreach($cat_array as $key => $val) 
        {
            if (0 == $val['0']) 
            {
                if ($action == 'new')
                {
                    $sel = ($default == $key) ? '1' : '';   
                }
                else
                {
                    $sel = (isset($catarray[$key])) ? '1' : '';   
                }
                
				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$val['1']."</option>\n";

                $this->category_subtree_form($key, $cat_array, $depth=1, $action, $default, $selected);
            }
        }
    }
    /* END */
    
    
    
    
    /** -----------------------------------------------------------
    /**  Category sub-tree
    /** -----------------------------------------------------------*/
    // This function works with the preceeding one to show a
    // hierarchical display of categories
    //-----------------------------------------------------------
        
    function category_subtree_form($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
    {
        global $DSP, $IN, $DB, $REGX, $LANG;

        $spcr = "&nbsp;";
        
        
        // Just as in the function above, we'll figure out which items are selected.
        
        if ($action == 'preview')
        {
            $catarray = array();
        
            foreach ($_POST as $key => $val)
            {
                if (strstr($key, 'category') AND is_array($val))
                {
                		foreach ($val as $k => $v)
                		{
						$catarray[$v] = $v;
					}
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
                
				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$pre.$indent.$spcr.$val['1']."</option>\n";

                $this->category_subtree_form($key, $cat_array, $depth, $action, $default, $selected);
            }
        }
    }
    /* END */



    /** ---------------------------------------------------------------
    /**  Fetch ping servers
    /** ---------------------------------------------------------------*/
    // This function displays the ping server checkboxes
    //---------------------------------------------------------------
        
    function fetch_ping_servers($which = 'new')
    {
        global $LANG, $DB, $SESS, $DSP, $PREFS;

        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '".$SESS->userdata('member_id')."'");
        
        $member_id = ($query->row['count'] == 0) ? 0 : $SESS->userdata('member_id');
              
        $query = $DB->query("SELECT id, server_name, is_default FROM exp_ping_servers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND member_id = '$member_id' ORDER BY server_order");

        if ($query->num_rows == 0)
        {
            return false;
        }

		$ping_array = array();        
		
		foreach($query->result as $row)
		{
			if (isset($_POST['preview']))
			{
				$selected = '';
				foreach ($_POST as $key => $val)
				{        
					if (strstr($key, 'ping') AND $val == $row['id'])
					{
						$selected = " checked='checked' ";
						break;
					}        
				}
			}
			else
			{
				$selected = ($row['is_default'] == 'y') ? " checked='checked' " : '';
			}


			$ping_array[] = array($row['id'], $selected, $row['server_name']);
		}
		

        return $ping_array;
    }        
    /* END */
    

      
}
// END CLASS
?>