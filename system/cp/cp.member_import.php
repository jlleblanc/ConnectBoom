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
 File: cp.member_import.php
-----------------------------------------------------
 Purpose: Member Import Utility
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class Member_Import {
	
	var $errors			= array();
	var $members		= array();
	var $default_fields	= array();
	var $delimiter;
	var $enclosure;
	var $crumbbase		= '';
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	
	function Member_Import()
	{
		global $IN, $DSP, $LANG, $SESS;

		// You have to be a Super Admin to access this page
        
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message();
        }
		
		// set breadcrumb base
		$this->crumbbase = BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=member_import';
		
		// Fetch the language file
		$LANG->fetch_language_file('member_import');
		
		switch($IN->GBL('F'))
		{
			case 'convert_data'			:	$this->convert_data();
				break;
			case 'xml_import'			:	$this->xml_import();
				break;
			case 'confirm_xml_form'		:	$this->confirm_xml_form();
				break;
			case 'process_xml'			:	$this->process_xml();
				break;
			case 'pair_fields'			:	$this->pair_fields();
				break;
			case 'confirm_data_form'	:	$this->confirm_data_form();
				break;
			case 'create_xml'			:	$this->create_xml();
				break;
			default						:	$this->member_import_main_page();
				break;
		}
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Member Import Main Page
	/** -------------------------------------*/
	
	function member_import_main_page()
	{
		global $DSP, $LANG;
         
        $DSP->title = $LANG->line('member_import_utility');

		$DSP->crumb = $LANG->line('member_import_utility');
        
		$r  = $DSP->qdiv('tableHeading', $LANG->line('member_import_utility'));
		$r .= $DSP->qdiv('tableHeadingAlt', $DSP->heading($LANG->line('member_import_welcome'), 5));
		
		$r .= $DSP->div('box');
		$r .= $DSP->qdiv('defaultBold', $DSP->anchor($this->crumbbase.AMP.'F=xml_import', $LANG->line('import_from_xml')));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_from_xml_blurb'));
		$r .= $DSP->div_c();
		
		$r .= $DSP->div('box');
		$r .= $DSP->qdiv('defaultBold', $DSP->anchor($this->crumbbase.AMP.'F=convert_data', $LANG->line('convert_from_delimited')));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('convert_from_delimited_blurb'));
		$r .= $DSP->div_c();
		
        $DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  XML Import
	/** -------------------------------------*/
	
	function xml_import()
	{
		global $DB, $DSP, $LANG, $LOC, $SESS, $PREFS;
		
        $DSP->title = $LANG->line('import_from_xml');

		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).$DSP->crumb_item($LANG->line('import_from_xml'));

		$r  = $DSP->qdiv('tableHeading', $LANG->line('import_from_xml'));
		$r .= $DSP->qdiv('tableHeadingAlt', $DSP->heading($LANG->line('import_from_xml_blurb'), 5));
		
		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('import_info'));
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('info_blurb')));
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=member_import'.AMP.'F=confirm_xml_form','name' => 'file_form'));
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('xml_file_loc')).
			  $DSP->qdiv('itemWrapper', $LANG->line('xml_file_loc_blurb')).
			  $DSP->input_text('xml_file', '', '40', '70', 'input', '300px').
			  $DSP->div_c();
			
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->heading($LANG->line('default_settings'));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('default_settings_blurb'));
		
		/** -------------------------------------
		/**  Fetch Member Groups
		/** -------------------------------------*/
		
		$query = $DB->query("SELECT DISTINCT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' order by group_title");
		
		$menu  = $DSP->input_select_header('group_id');
		
		foreach ($query->result as $row)
		{
			$menu .= $DSP->input_select_option($row['group_id'], $row['group_title']);
		}
		
		$menu .= $DSP->input_select_footer();
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('default_group_id')).
			  $menu.
			  $DSP->div_c();
		
		/** -------------------------------------
		/**  Languages
		/** -------------------------------------*/
		
		$source_dir = PATH_LANG;

    	$dirs = array();

		if ($fp = @opendir($source_dir)) 
		{ 
			while (FALSE !== ($file = readdir($fp))) 
			{ 
				if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
				{
					$dirs[] = $file;
				}
			}			
			closedir($fp); 
		}

		sort($dirs);
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('language')).
			  $DSP->input_select_header('language').
			  $DSP->input_select_option($LANG->line('none'), $LANG->line('none'));
			
		foreach ($dirs as $dir)
		{
			$r .= $DSP->input_select_option($dir, ucfirst($dir));
		}
		
		$r .= $DSP->input_select_footer();
		$r .= $DSP->div_c();
		
		/** -------------------------------------
		/**  Timezone
		/** -------------------------------------*/
		
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('timezone')).
			  $LOC->timezone_menu('UTC').
			  $DSP->div_c();

		/** -------------------------------------
		/**  Time Format
		/** -------------------------------------*/

		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('time_format')).
			  $DSP->input_select_header('time_format').
			  $DSP->input_select_option('us', $LANG->line('united_states')).
			  $DSP->input_select_option('eu', $LANG->line('european')).
			  $DSP->input_select_footer().
			  $DSP->div_c();

		/** -------------------------------------
		/**  DST setting
		/** -------------------------------------*/

		$dst = ($SESS->userdata('daylight_savings') == 'y') ? 1 : 0;

		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('daylight_savings')).
			  $DSP->qdiv('itemWrapper', $DSP->input_checkbox('daylight_savings', 'y', $dst).
						'<label for="daylight_savings">'.$LANG->line('dst_enabled').'</label>').
			  $DSP->div_c();

		$r .= $DSP->qdiv('simpleLine', NBS);
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit('Submit'));
		$r .= $DSP->form_close();
		$r .= $DSP->div_c();
		
		$DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  XML Form Confirmation
	/** -------------------------------------*/
	
	function confirm_xml_form()
	{
		global $DB, $DSP, $IN, $LANG, $LOC;
		
		/** -------------------------------------
		/**  Snag POST data, prepared for db insertion
		/** -------------------------------------*/
		
		$data = array(
						'xml_file'   		=> ( ! $IN->GBL('xml_file', 'POST'))  ? '' : $IN->GBL('xml_file', 'POST'),
						'group_id' 			=> $IN->GBL('group_id', 'POST'),
						'language' 			=> ($IN->GBL('language', 'POST') == $LANG->line('none')) ? '' : $IN->GBL('language', 'POST'),
						'timezone' 			=> $IN->GBL('server_timezone', 'POST'),
						'time_format' 		=> $IN->GBL('time_format', 'POST'),
						'daylight_savings' 	=> ($IN->GBL('daylight_savings', 'POST') == 'y') ? 'y' : 'n'
					 );
					
		if ($data['xml_file'] == '')
		{
			return $DSP->error_message($LANG->line('no_file_submitted'));
		}
		
		/** -------------------------------------
		/**  Begin Output
		/** -------------------------------------*/

		$DSP->title = $LANG->line('confirm_details');

		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
					  $DSP->crumb_item($DSP->anchor($this->crumbbase.AMP.'F=xml_import', $LANG->line('import_from_xml'))).
					  $DSP->crumb_item($LANG->line('confirm_details'));

		$r  = $DSP->qdiv('tableHeading', $LANG->line('confirm_details'));
		
		$r .= $DSP->qdiv('box', $DSP->qdiv('itemWrapper', $LANG->line('confirm_details_blurb')));
		$r .= $DSP->div('box');

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'style' => 'width: 600px'));
		$r .= $DSP->table_row(array(
									array('class' => 'tableHeadingAlt', 'text' => $LANG->line('option')),
									array('class' => 'tableHeadingAlt', 'text' => $LANG->line('value'))
									)
							 );
		$i = 0;
		
		foreach ($data as $key => $val)
		{

			$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			/** -------------------------------------
			/**  Our values need pretty-fication
			/** -------------------------------------*/
			
			switch ($key)
			{
				case 'group_id':
					$query = $DB->query("SELECT DISTINCT group_title FROM exp_member_groups where group_id = '".$data[$key]."'");
					$val = $query->row['group_title'];
					break;
				case 'language':
					$val = ($val == '') ? 'None' : ucfirst($val);
					break;
				case 'timezone':
					$val = $LANG->line($val);
					break;
				case 'daylight_savings':
					$val = ($val == 'y') ? $LANG->line('yes') : $LANG->line('no');
					break;
				case 'time_format':
					$val = ($val == 'us') ? $LANG->line('united_states') : $LANG->line('european');
					break;
			}
			
			$r .= $DSP->table_row(array(
										array('class' => $class, 'style' => 'width: 200px', 'text' => $LANG->line($key)),
										array('class' => $class, 'text' => $DSP->span('highlight_alt').$val.$DSP->span_c())
										)
								 );
		}
		
		$r .= $DSP->table_close();
		$r .= $DSP->div_c();
		
		$r .= $DSP->qdiv('box alert', $LANG->line('member_id_warning'));
		
		$r .= $DSP->div('box');
		$r .= $DSP->form_open(
								array(
										'action'	=> 'C=admin'.AMP.'M=utilities'.AMP.'P=member_import'.AMP.'F=process_xml',
										'method'	=> 'post',
										'name'		=> 'confirmForm',
										'id'		=> 'confirmForm',
									 ),
								$data
							 );
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('import')));
		$r .= $DSP->form_close();
		$r .= $DSP->div_c();
		
		$DSP->body = $r;
	}
	
	
	/** -------------------------------------
	/**  Check and Process XML File
	/** -------------------------------------*/
	
	function process_xml()
	{
		global $DSP, $IN, $LANG;

		$xml_file   = ( ! $IN->GBL('xml_file', 'POST'))  ? '' : $IN->GBL('xml_file', 'POST');

		/** -------------------------------------
		/**  Check file path
		/** -------------------------------------*/
		
		if (($exists = $this->check_file($xml_file)) !== TRUE)
		{
			return;
		}
		
		/** -------------------------------------
		/**  Read XML file contents
		/** -------------------------------------*/
		
		if (function_exists('file_get_contents'))
		{
			$contents = file_get_contents($xml_file);
		}
		else
		{
			$fp = fopen($xml_file, 'r');
			$contents = fread($fp, filesize($xml_file));
			fclose($fp);
		}
		
		/** -------------------------------------
		/**  Instantiate EE_XMLparser Class
		/** -------------------------------------*/
		
		if ( ! class_exists('EE_XMLparser'))
		{
		    require PATH_CORE.'core.xmlparser'.EXT;
		}

		$XML = new EE_XMLparser;

		// parse XML data
		$xml = $XML->parse_xml($contents);

		$this->validate_xml($xml);
		
		/** -------------------------------------
		/**  Show Errors
		/** -------------------------------------*/

		if (count($this->errors) > 0)
		{
			$msg = '';
			
			foreach($this->errors as $error)
			{
				foreach($error as $val)
				{
					$msg .= $val.'<br />';
				}
			}
			
			return $DSP->error_message($msg);
		}

		/** -------------------------------------
		/**  Ok! Cross Fingers and do it!
		/** -------------------------------------*/
		
		$imports = $this->do_import();

		/** -------------------------------------
		/**  Begin Output
		/** -------------------------------------*/
		
		$DSP->title = $LANG->line('xml_imported');

		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
					  $DSP->crumb_item($LANG->line('xml_imported'));

		$r  = $DSP->qdiv('tableHeading', $LANG->line('import_success'));
		$r .= $DSP->div('box');
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_success_blurb'));
		$r .= $DSP->qdiv('itemWrapper', str_replace('%x', $imports, $LANG->line('total_members_imported')));
		$r .= $DSP->div_c();
		
		$DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Validate XML Member file
	/** -------------------------------------*/
	
	function validate_xml($xml)
	{
		global $DB, $DSP, $FNS, $LANG, $PREFS;
		
		if ( ! class_exists('Validate'))
		{
			require PATH_CORE.'core.validate'.EXT;
		}
		
		$VAL = new Validate(
								array(
										'member_id'			=> '',
										'val_type'			=> 'new',
										'fetch_lang'		=> TRUE,
										'require_cpw'		=> FALSE,
										'enable_log'		=> FALSE,
										'cur_username'		=> '',
										'cur_screen_name'	=> '',
										'cur_password'		=> '',
										'cur_email'			=> '',
									)
							);
		
		$i = 0;
		
		/** -------------------------------------
		/**  Retreive Valid fields from database
		/** -------------------------------------*/
		
		$query = $DB->query("SHOW COLUMNS FROM exp_members");
		foreach ($query->result as $row)
		{
			$this->default_fields[$row['Field']] = '';
		}
		
		// we don't allow <unique_id>
		unset($this->default_fields['unique_id']);
		
		$u = array(); // username garbage array
		$s = array(); // screen_name garbage array
		$e = array(); // email garbage array
		$m = array(); // member_id garbage array
		
		if (is_array($xml->children[0]->children))
		{
			foreach($xml->children as $member)
			{
				if ($member->tag == "member")
				{
					foreach($member->children as $tag)
					{
						// Is the XML tag an allowed database field
						if ( ! isset($this->default_fields[$tag->tag]) || $tag->tag == 'unique_id')
						{
							// We have a special XML format for birthdays that doesn't match the database fields
							if ($tag->tag == 'birthday')
							{
								foreach($tag->children as $birthday)
								{
									switch ($birthday->tag)
									{
										case 'day':
											$this->members[$i]['bday_d'] = $birthday->value;
											break;
										case 'month':
											$this->members[$i]['bday_m'] = $birthday->value;
											break;
										case 'year':
											$this->members[$i]['bday_y'] = $birthday->value;
											break;
										default:
											$this->errors[] = array($LANG->line('invalid_tag')." '&lt;".$birthday->tag."&gt;'");
											break;
									}
								}
								if ( ! isset($this->members[$i]['bday_d']) || ! isset($this->members[$i]['bday_m']) || ! isset($this->members[$i]['bday_y']))
								{
									$this->errors[] = array($LANG->line('missing_birthday_child'));
								}
							}
							else
							{
								// not a database field and not a <birthday> so club it like a baby seal!
								$this->errors[] = array($LANG->line('invalid_tag')." '&lt;".$tag->tag."&gt;'");							
							}
						}
				
						$this->members[$i][$tag->tag] = $tag->value;
						
						/* -------------------------------------
						/*  username, screen_name, and email
						/*  must be validated and unique
						/* -------------------------------------*/
						
						switch ($tag->tag)
						{
							case 'username':
								$VAL->username = $tag->value;
								if ( ! in_array($tag->value, $u))
								{
									$u[] = $tag->value;
								}
								else
								{
									$this->errors[] = array($LANG->line('duplicate_username').$tag->value);
								}
								break;
							case 'screen_name':
								$VAL->screen_name = $tag->value;
								if ( ! in_array($tag->value, $s))
								{
									$s[] = $tag->value;
								}
								else
								{
									$this->errors[] = array($LANG->line('duplicate_screen_name').$tag->value);
								}
								break;
							case 'email':
								if ( ! in_array($tag->value, $e) OR $PREFS->ini('allow_multi_emails') == 'y')
								{
									$e[] = $tag->value;
								}
								else
								{
									$this->errors[] = array($LANG->line('duplicate_email').$tag->value);
								}
								$VAL->email = $tag->value;
								break;
							case 'member_id':
								if ( ! in_array($tag->value, $m))
								{
									$m[] = $tag->value;
								}
								else
								{
									$this->errors[] = array(str_replace("%x", $tag->value, $LANG->line('duplicate_member_id')));
								}
								break;
							case 'password':
								// encode password if it is type="text"
								$this->members[$i][$tag->tag] = ($tag->attributes['type'] == 'text') ? $FNS->hash($tag->value) : $tag->value;
								break;
						}
					}
			
					$username 		= (isset($this->members[$i]['username'])) ? $this->members[$i]['username'] : '';
					$screen_name 	= (isset($this->members[$i]['screen_name'])) ? $this->members[$i]['screen_name'] : '';
					$email 			= (isset($this->members[$i]['email'])) ? $this->members[$i]['email'] : '';
			
					/* -------------------------------------
					/*  Validate separately to display
					/*  exact problem
					/* -------------------------------------*/
		
					$VAL->validate_username();

					if ( ! empty($VAL->errors))
					{
						foreach($VAL->errors as $key => $val)
						{
							$VAL->errors[$key] = $val." (Username: '".$username."' - ".$LANG->line('within_user_record')." '".$username."')";
						}
						$this->errors[] = $VAL->errors;
						unset($VAL->errors);
					}
			
					$VAL->validate_screen_name();
			
					if ( ! empty($VAL->errors))
					{
						foreach($VAL->errors as $key => $val)
						{
							$VAL->errors[$key] = $val." (Screen Name: '".$screen_name."' - ".$LANG->line('within_user_record')." '".$username."')";
						}
						$this->errors[] = $VAL->errors;
						unset($VAL->errors);
					}
			
					$VAL->validate_email();
			
					if ( ! empty($VAL->errors))
					{
						foreach($VAL->errors as $key => $val)
						{
							$VAL->errors[$key] = $val." (Email: '".$email."' - ".$LANG->line('within_user_record')." '".$username."')";
						}
						$this->errors[] = $VAL->errors;
						unset($VAL->errors);
					}
					
					/** -------------------------------------
					/**  Add a random hash if no password is defined
					/** -------------------------------------*/
					
					if ( ! isset($this->members[$i]['password']))
					{
						$this->members[$i]['password'] = $FNS->hash(mt_rand());
					}
					$i++;
				}
				else
				{
					/** -------------------------------------
					/**  Element isn't <member>
					/** -------------------------------------*/
					
					$this->errors[] = array($LANG->line('invalid_element'));
				}
			}
		}
		else
		{
			/** -------------------------------------
			/**  No children of the root element
			/** -------------------------------------*/
			
			$this->errors[] = array($LANG->line('invalid_xml'));
		}
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Perform XML Member Import
	/** -------------------------------------*/
	
	function do_import()
	{
		global $DB, $DSP, $FNS, $IN, $LANG, $LOC, $STAT;
		
		/** -------------------------------------
		/**  Set our optional default values
		/** -------------------------------------*/
		
		$this->default_fields['group_id']			= $IN->GBL('group_id', 'POST');
		$this->default_fields['language']			= ($IN->GBL('language', 'POST') == $LANG->line('none')) ? '' : $IN->GBL('language', 'POST');
		$this->default_fields['timezone']			= $IN->GBL('timezone', 'POST');
		$this->default_fields['time_format']		= $IN->GBL('time_format', 'POST');
		$this->default_fields['daylight_savings']	= ($IN->GBL('daylight_savings', 'POST') == 'y') ? 'y' : 'n';
		$this->default_fields['ip_address']			= '0.0.0.0';
		$this->default_fields['join_date']			= $LOC->now;
		
		/** -------------------------------------
		/**  Rev it up, no turning back!
		/** -------------------------------------*/
		
		$new_ids = array();
		$counter = 0;
		
		foreach ($this->members as $member)
		{
			$data = array();
			$dupe = FALSE;

			foreach ($this->default_fields as $key => $val)
			{
				if (isset($member[$key]))
				{
					$data[$key] = $member[$key];						
				}
				elseif ($val != '')
				{
					$data[$key] = $val;
				}
			}

			/** -------------------------------------
			/**  Add a unique_id for each member
			/** -------------------------------------*/
			
			$data['unique_id'] = $FNS->random('encrypt');
			
			/* -------------------------------------
			/*  See if we've already imported a member with this member_id -
			/*  could possibly occur if an auto_increment value is used
			/*  before a specified member_id.
			/* -------------------------------------*/
			
			if (isset($data['member_id']))
			{
				if (isset($new_ids[$data['member_id']]))
				{
					/* -------------------------------------
					/*  Grab the member so we can re-insert it after we
					/*  take care of this nonsense
					/* -------------------------------------*/
					$dupe = TRUE;
					$tempdata = $DB->query("SELECT * FROM exp_members WHERE member_id = '".$data['member_id']."'");				
				}
			}
			/* -------------------------------------
			/*  Shove it in!
			/*  We are using REPLACE as we want to overwrite existing members if a member id is specified
			/* -------------------------------------*/
			
			$sql = str_replace('INSERT', 'REPLACE', $DB->insert_string('exp_members', $data));
			$DB->query($sql);

			/** -------------------------------------
			/**  Add the member id to the array of imported member id's
			/** -------------------------------------*/
			
			$new_ids[$DB->insert_id] = '';

			/** -------------------------------------
			/**  Insert the old auto_incremented member, if necessary
			/** -------------------------------------*/
			
			if ($dupe === TRUE)
			{
				unset($tempdata->row['member_id']); // dump the member_id so it can auto_increment a new one
				$DB->query($DB->insert_string('exp_members', $tempdata->row));
				$new_ids[$DB->insert_id] = '';
			}
	
			$counter++;			
		}
		
		/** -------------------------------------
		/**  Add records to exp_member_data and exp_member_homepage tables for all imported members
		/** -------------------------------------*/
		
		$values = '';
		
		foreach ($new_ids as $key => $val)
		{
			$values .= "('$key'),";
		}
		
		$values = substr($values, 0, -1);
		
		$DB->query("INSERT INTO exp_member_data (member_id) VALUES ".$values);
		$DB->query("INSERT INTO exp_member_homepage (member_id) VALUES ".$values);
		
		/** -------------------------------------
		/**  Update Statistics
		/** -------------------------------------*/
		
		$STAT->update_member_stats();
		
		return $counter;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Convert Delimited Data
	/** -------------------------------------*/
	
	function convert_data()
	{
		global $DSP, $LANG;
		
		/** -------------------------------------
		/**  Begin Output
		/** -------------------------------------*/
		
		$DSP->title = $LANG->line('convert_from_delimited');

		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
					  $DSP->crumb_item($LANG->line('convert_from_delimited'));
        
		$r  = $DSP->qdiv('tableHeading', $LANG->line('convert_from_delimited'));
		$r .= $DSP->qdiv('tableHeadingAlt', $DSP->heading($LANG->line('convert_from_delimited_blurb'), 5));

		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('import_info'));
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('info_blurb')));
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->form_open(array(
									'action' => 'C=admin'.AMP.
												'M=utilities'.AMP.
												'P=member_import'.AMP.
												'F=pair_fields',
									'name' => 'file_form'
									)
							 );
            
		$r .= $DSP->div('itemWrapper').
			  $DSP->qdiv('itemTitle', $LANG->line('delimited_file_loc')).
			  $DSP->qdiv('itemWrapper', $LANG->line('file_loc_blurb')).
			  $DSP->input_text('member_file', '', '40', '70', 'input', '300px').
			  $DSP->div_c();
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemTitle', $LANG->line('delimiter'));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('delimiter_blurb'));
		$delimit_opts = array('tab' => "tab", 'comma' => ',' , 'other' => 'other');
		
		foreach ($delimit_opts as $key => $val)
		{
			$checked = ($key == 'tab') ? 1 : 0;
			$r .= $DSP->input_radio('delimiter', $val, $checked);			
			$r .= '<label for="delimiter">'.$LANG->line($key).'</label>';
			$r .= $DSP->nbs(2);
		}

		$r .= $DSP->input_text('delimiter_special', '', 5, 2, 'input', 5, 'onclick="this.form.delimiter[2].click();return false"');		
		$r .= $DSP->div_c();
		
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemTitle', $LANG->line('enclosure'));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('enclosure_blurb'));
		$r .= $DSP->qdiv('highlight_alt', '<code>'.$LANG->line('enclosure_example').'</code>');
		$r .= $DSP->qdiv('itemWrapper',
						 '<label for="enclosure">'.$LANG->line('enclosure_label').'</label>'.
						 $DSP->input_text('enclosure', '', 5, 2, 'input', 5)
						);
		$r .= $DSP->div_c();
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit')));
		$r .= $DSP->form_close();
		$r .= $DSP->div_c();
		
        $DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Pair delimited data with Member fields
	/** -------------------------------------*/
	
	function pair_fields()
	{
		global $DB, $DSP, $IN, $LANG;

		/** -------------------------------------
		/**  Snag form POST data
		/** -------------------------------------*/

		$member_file		= ( ! $IN->GBL('member_file', 'POST'))  ? '' : $IN->GBL('member_file', 'POST');
		
		switch ($IN->GBL('delimiter', 'POST'))
		{
			case 'tab'	:	$this->delimiter = "\t"; break;
			case ','	:	$this->delimiter = ","; break;
			case 'other':
				$this->delimiter = trim($IN->GBL('delimiter_special', 'POST'));
				preg_match("/[\w\d]*/", $this->delimiter, $matches);
				if ($matches[0] != '')
					return $DSP->error_message($LANG->line('alphanumeric_not_allowed'));
				break;
		}

		$this->enclosure 	= ( ! $IN->GBL('enclosure', 'POST')) ? '' : $this->prep_enclosure($IN->GBL('enclosure', 'POST'));

		/** -------------------------------------
		/**  Make sure file exists
		/** -------------------------------------*/
		
		$exists = $this->check_file($member_file);
		if ($exists !== TRUE)
			return;

		if ($this->delimiter == '')
		{
			return $DSP->error_message(str_replace('%x', $LANG->line('other'), $LANG->line('no_delimiter')));
		}
		
		/** -------------------------------------
		/**  Read data file into an array
		/** -------------------------------------*/
		
		$fields = $this->datafile_to_array($member_file);
		
		if (count($fields[0]) < 3)
		{
			// No point going further if there aren't even the minimum required
			return $DSP->error_message($LANG->line('not_enough_fields'));
		}
		
		/** -------------------------------------
		/**  Begin Output
		/** -------------------------------------*/
		
		$DSP->title = $LANG->line('member_import_utility');
        
		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
					  $DSP->crumb_item($DSP->anchor($this->crumbbase.AMP.'F=convert_data', $LANG->line('convert_from_delimited'))).
				   	  $DSP->crumb_item($LANG->line('assign_fields'));

		$r  = $DSP->qdiv('tableHeading', $LANG->line('member_import_utility'));
		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('assign_fields'));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('assign_fields_blurb'));
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('password_field_warning')));
		$r .= $DSP->div_c();
		
		/** -------------------------------------
		/**  Retreive Valid fields from database
		/** -------------------------------------*/
		
		$query = $DB->query("SHOW COLUMNS FROM exp_members");

		foreach ($query->result as $row)
		{
			$this->default_fields[$row['Field']] = '';
		}
		
		// we do not allow <unique_id> in our XML format
		unset($this->default_fields['unique_id']);
		
		ksort($this->default_fields);

		$select_options = '';
		
		foreach ($this->default_fields as $key => $val)
		{
			$select_options .= $DSP->input_select_option($key, $key);
		}
		
		/** -------------------------------------
		/**  Display table and form
		/** -------------------------------------*/
		
		$r .= $DSP->div('box');
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('required_fields')));

		$r .= $DSP->form_open(
								array(
										'action'	=> 'C=admin'.AMP.'M=utilities'.AMP.'P=member_import'.AMP.'F=confirm_data_form',
										'method'	=> 'post',
										'name'		=> 'entryform',
										'id'		=> 'entryform'
									),
								array(
										'member_file'		=> $IN->GBL('member_file', 'POST'),
										'delimiter'			=> $IN->GBL('delimiter', 'POST'),
										'enclosure'			=> $this->enclosure,
										'delimiter_special'	=> $this->delimiter
									)
							 );

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'style' => 'width: 600px'));
		$r .= $DSP->table_row(array(
									array('class' => 'tableHeading', 'text' => $LANG->line('your_data')),
									array('class' => 'tableHeading', 'text' => $LANG->line('member_fields'))
									)
							 );
		$i = 0;
		
		foreach ($fields[0] as $field)
		{
			$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			$r .= $DSP->table_row(array(
										array('class' => $class, 'text' => $field),
										array(
											 	'class'	=>  $class,
												'text' 	=>  $DSP->input_select_header('field_'.$i).
															$select_options.
															$DSP->input_select_footer()
											 )
										)
								 );
		}
		
		$r .= $DSP->table_close();
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('encrypt', 'y').'<label for="encrypt">'.$LANG->line('plaintext_passwords').'</label>');
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit());
		
		$r .= $DSP->form_close();
		$r .= $DSP->div_c();
		
		$r .= $DSP->qdiv('simpleLine', NBS);
		
		$r .= $DSP->div_c();
		
		$DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Confirm Data to XML Form
	/** -------------------------------------*/
	
	function confirm_data_form()
	{
		global $DSP, $IN, $LANG;
		
		/** -------------------------------------
		/**  Snag POST data
		/** -------------------------------------*/
		
		$member_file		= ( ! $IN->GBL('member_file', 'POST'))  ? '' : $IN->GBL('member_file', 'POST');
		switch ($IN->GBL('delimiter', 'POST'))
		{
			case 'tab'	:	$this->delimiter = "\t"; break;
			case ','	:	$this->delimiter = ","; break;
			case 'other':	$this->delimiter = $IN->GBL('delimiter_special', 'POST');
		}
		$this->enclosure 	= ( ! $IN->GBL('enclosure', 'POST')) ? '' : $this->prep_enclosure($IN->GBL('enclosure', 'POST'));
		$encrypt			= ($IN->GBL('encrypt', 'POST') == 'y') ? TRUE : FALSE;

		/** -------------------------------------
		/**  Get field pairings
		/** -------------------------------------*/

		$paired = array();
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'field')
			{
				if (in_array($val, $paired))
				{
					return $DSP->error_message(str_replace("%x", $val, $LANG->line('duplicate_field_assignment')));
				}

				$paired[$key] = $val;				
			}
		}
		
		if (! in_array('username', $paired))
		{
			return $DSP->error_message($LANG->line('missing_username_field'));
		}
		
		if (! in_array('screen_name', $paired))
		{
			return $DSP->error_message($LANG->line('missing_screen_name_field'));
		}
		
		if (! in_array('email', $paired))
		{
			return $DSP->error_message($LANG->line('missing_email_field'));
		}
		
		/** -------------------------------------
		/**  Read the data file
		/** -------------------------------------*/
		
		$fields = $this->datafile_to_array($member_file);
				
		/** -------------------------------------
		/**  Begin Output
		/** -------------------------------------*/
		
		$DSP->title = $LANG->line('member_import_utility');
        
		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
					  $DSP->crumb_item($DSP->anchor($this->crumbbase.AMP.'F=convert_data', $LANG->line('convert_from_delimited'))).
					  $DSP->crumb_item($LANG->line('confirm_field_assignment'));
											
		$r  = $DSP->qdiv('tableHeading', $LANG->line('member_import_utility'));
		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('confirm_field_assignment'));
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('confirm_field_assignment_blurb'));
		$r .= $DSP->div_c();
		
		/** -------------------------------------
		/**  Begin form and table
		/** -------------------------------------*/
		
		$r .= $DSP->div('box');
		$r .= $DSP->form_open(
								array(
										'action'	=> 'C=admin'.AMP.'M=utilities'.AMP.'P=member_import'.AMP.'F=create_xml',
										'method'	=> 'post',
										'name'		=> 'entryform',
										'id'		=> 'entryform'
									),
								array(
										'member_file'		=> $IN->GBL('member_file', 'POST'),
										'delimiter'			=> $IN->GBL('delimiter', 'POST'),
										'delimiter_special'	=> $this->delimiter,
										'enclosure'			=> $this->enclosure,
										'encrypt'			=> $IN->GBL('encrypt', 'POST')
									)
							 );
		foreach ($paired as $key => $val)
		{
			$r .= $DSP->input_hidden($key, $val);			
		}

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'style' => 'width: 600px'));
		$r .= $DSP->table_row(array(
									array('class' => 'tableHeading', 'text' => $LANG->line('your_data')),
									array('class' => 'tableHeading', 'text' => $LANG->line('member_fields'))
									)
							 );
		$i = 0;
		
		foreach ($fields[0] as $key => $val)
		{
			$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			$r .= $DSP->table_row(array(
										array('class' => $class, 'text' => $val),
										array(
											 	'class'	=>  $class,
												'text' 	=>  $DSP->span('highlight_alt').$paired['field_'.($key + 1)].$DSP->span_c()
											 )
										)
								 );
		}
		
		$r .= $DSP->table_close();

		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', ($encrypt === TRUE) ? $LANG->line('plaintext_passwords') : $LANG->line('encrypted_passwords')));
		$r .= $DSP->qdiv('simpleLine', NBS);
		$r .= $DSP->qdiv('radio', $DSP->input_radio('type', 'view').$LANG->line('view_in_browser'));
		$r .= $DSP->qdiv('radio', $DSP->input_radio('type', 'download', 1).$LANG->line('download'));

		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit());
		
		$r .= $DSP->form_close();
		$r .= $DSP->div_c();
		
		$DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Create XML File
	/** -------------------------------------*/
	
	function create_xml()
	{
		global $DSP, $IN, $LANG, $OUT;
		
		/** -------------------------------------
		/**  Snag POST data
		/** -------------------------------------*/
		
		$member_file		= ( ! $IN->GBL('member_file', 'POST'))  ? '' : $IN->GBL('member_file', 'POST');
		
		switch ($IN->GBL('delimiter', 'POST'))
		{
			case 'tab'	:	$this->delimiter = "\t"; break;
			case ','	:	$this->delimiter = ","; break;
			case 'other':	$this->delimiter = $IN->GBL('delimiter_special', 'POST');
		}
		
		$this->enclosure 	= ( ! $IN->GBL('enclosure', 'POST')) ? '' : $this->prep_enclosure($IN->GBL('enclosure', 'POST'));
		$encrypt			= ($IN->GBL('encrypt', 'POST') == 'y') ? TRUE : FALSE;
		$type				= $IN->GBL('type', 'POST');
		
		/** -------------------------------------
		/**  Read file contents
		/** -------------------------------------*/
		
		if (function_exists('file_get_contents'))
		{
			$contents = file_get_contents($member_file);
		}
		else
		{
			$fp = fopen($member_file, 'r');
			$contents = fread($fp, filesize($member_file));
			fclose($fp);
		}
		
		/** -------------------------------------
		/**  Get structure
		/** -------------------------------------*/
		$structure = array();
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'field')
			{
				$structure[] = $val;				
			}
		}

		/** -------------------------------------
		/**  Instantiate EE_XMLparser Class
		/** -------------------------------------*/
		
		if ( ! class_exists('EE_XMLparser'))
		{
		    require PATH_CORE.'core.xmlparser'.EXT;
		}

		$XML = new EE_XMLparser;
		
		/** -------------------------------------
		/**  Convert the data to XML
		/** -------------------------------------*/

		$params = array(
							'data'			=> $contents,
							'structure'		=> $structure,
							'root'			=> 'members',
							'element'		=> 'member',
							'delimiter'		=> $this->delimiter,
							'enclosure'		=> $this->enclosure
						);
		
		$xml = $XML->delimited_to_xml($params);
		
		/** -------------------------------------
		/**  Add type="text" parameter for plaintext passwords
		/** -------------------------------------*/
		
		if ($encrypt === TRUE)
		{
			$xml = str_replace('<password>', '<password type="text">', $xml);
		}
		
		if ( ! empty($XML->errors))
		{
			$OUT->show_user_error('general', $XML->errors);
			exit;
		}
		
		/** -------------------------------------
		/**  Output to browser or download
		/** -------------------------------------*/
		
		switch ($type)
		{
			case 'view'		: $this->view_xml($xml); break;
			case 'download' : $this->download_xml($xml); break;
		}
	}
	/* END */
	
	
	/** -------------------------------------
	/**  View XML in browser
	/** -------------------------------------*/
	
	function view_xml($xml)
	{
		global $DSP, $LANG;
		
		$DSP->title = $LANG->line('member_import_utility');
        
		$DSP->crumb = $DSP->anchor($this->crumbbase, $LANG->line('member_import_utility')).
								   $DSP->crumb_item($LANG->line('view_xml'));

		$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('member_import_utility'));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->heading($LANG->line('view_xml'));
		
		$xml = str_replace("\n", BR, htmlentities($xml));
		$xml = str_replace("\t", $DSP->NBS(4), $xml);

		$DSP->body .= $xml;
		$DSP->body .= $DSP->div_c();
	}
	/* END */
	
	/** -------------------------------------
	/**  I wondered why the baseball was getting bigger. Then it hit me.
	/** -------------------------------------*/
	
	/** -------------------------------------
	/**  Download XML file
	/** -------------------------------------*/
	
	function download_xml($xml)
	{
		global $DSP, $LANG, $LOC;
		
		$now = $LOC->set_localized_time();
        
        $filename = 'member_'.date('y', $now).date('m', $now).date('d', $now).'.xml';
		
		ob_start();
		
		if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE") || strstr($_SERVER['HTTP_USER_AGENT'], "OPERA")) 
        {
            $mime = 'application/octetstream';
        }
        else
        {
            $mime = 'application/octet-stream';
        }

		if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
        {
            header('Content-Type: '.$mime);
            header('Content-Disposition: inline; filename="'.$filename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } 
        else 
        {
            header('Content-Type: '.$mime);
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Expires: 0');
            header('Pragma: no-cache');
        }
		
		echo $xml;
		
		$buffer = ob_get_contents();
        
        ob_end_clean();

		echo $buffer;
		exit;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Validate file path
	/** -------------------------------------*/
	
	function check_file($file)
	{
		global $DSP, $LANG;
		
		if ($file == '')
		{
			return $DSP->error_message($LANG->line('no_file_submitted'));
		}
		
		if ( ! file_exists($file))
		{
			return $DSP->error_message($LANG->line('invalid_path').$file);
		}
		
		return TRUE;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Read delimited data file into array
	/** -------------------------------------*/
	
	function datafile_to_array($file)
	{
		$contents = file($file);
		$fields = array();

		/** -------------------------------------
		/**  Parse file into array
		/** -------------------------------------*/

		if ($this->enclosure == '')
		{
			foreach ($contents as $line)
			{
				$fields[] = explode($this->delimiter, $line);
			}			
		}
		else
		{
			foreach ($contents as $line)
			{
				preg_match_all("/".preg_quote($this->enclosure)."(.*?)".preg_quote($this->enclosure)."/si", $line, $matches);
				$fields[] = $matches[1];
			}
		}

		return $fields;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Prep Enclosure
	/** -------------------------------------*/
	
	function prep_enclosure($enclosure)
	{
		// undo changes made by form prep as we need the literal characters
		// and htmlspecialchars_decode() doesn't exist until PHP 5, so...
		$enclosure = str_replace('&#39;', "'", $enclosure);
		$enclosure = str_replace('&amp;', "&", $enclosure);
		$enclosure = str_replace('&lt;', "<", $enclosure);
		$enclosure = str_replace('&gt;', ">", $enclosure);
		$enclosure = str_replace('&quot;', '"', $enclosure);
		$enclosure = stripslashes($enclosure);
		
		return $enclosure;
	}
}
/* END */

?>