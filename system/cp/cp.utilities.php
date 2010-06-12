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
 File: cp.utilities.php
-----------------------------------------------------
 Purpose: Utilities
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Utilities {


    /** -------------------------------------------
    /**  Extensions Manager
    /** -------------------------------------------*/

	function extensions_manager($message = '')
	{
        global $DSP, $IN, $PREFS, $LANG, $DB, $FNS, $EXT;
        
        $debug					= TRUE;
        $extension_files		= array();
        $extensions_installed	= array();
		
		/** ---------------------------------------
		/**  Extensions Available
		/** ---------------------------------------*/
		
		$i = 0;
		if ($fp = @opendir(PATH_EXT))
        { 
            while (false !== ($file = readdir($fp)))
            {
                if (substr($file, -strlen(EXT)) == EXT && substr($file, 0, 4) == 'ext.') 
                {
					$extension_files[$i] = substr($file, 4, -strlen(EXT));
					
					$i++;
                }
            }         
			
			closedir($fp); 
        }
        
        /** ---------------------------------------
		/**  Extensions Enabled
		/** ---------------------------------------*/
        
        $query = $DB->query("SELECT class, version FROM exp_extensions WHERE enabled = 'y'");
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row) $extensions_installed[strtolower($row['class'])] = $row['version'];
        }
        
		/** ---------------------------------------
		/**  Create Output
		/** ---------------------------------------*/
		
		$DSP->crumbline = FALSE;
		
		if ($PREFS->ini('allow_extensions') == 'y')
		{
			$DSP->right_crumb($LANG->line('disable_extensions'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable');
		}
		else
		{
			$DSP->right_crumb($LANG->line('enable_extensions'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=enable');
    	}
    	
        $r = $DSP->qdiv('tableHeading', $LANG->line('extensions_manager'));
    	             
        if ($message != '')
        {
            $r .= $DSP->qdiv('success', $message);
        }
        
        // List of Extensions Table
        
        $r .= $DSP->table('tableBorder', '0', '0', '100%');              
  
        if (count($extension_files) == 0)
        {
            $r .= $DSP->tr().
                  $DSP->td('tableCellTwo', '', '2').
                  '<b>'.$LANG->line('no_extensions_exist').'</b>'.
                  $DSP->td_c().
                  $DSP->tr_c();
        }
        else
        {
        	$r .=	$DSP->tr().
              		$DSP->td('tableHeadingAlt', '55%').
              		$LANG->line('extension_name').
              		$DSP->td_c().
              		$DSP->td('tableHeadingAlt', '15%').
              		$LANG->line('documentation').
              		$DSP->td_c().
              		$DSP->td('tableHeadingAlt', '15%').
              		$LANG->line('settings').
              		$DSP->td_c().
              		$DSP->td('tableHeadingAlt', '15%').
              		$LANG->line('status').
              		$DSP->td_c().
              		$DSP->tr_c();
        }

        $i = 0;
        
        if (count($extension_files) > 0)
        {
        	$extension_meta  = array('description', 'settings_exist', 'docs_url', 'name', 'version');
        	$qm 			 = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        	
            foreach ($extension_files as $extension_name)
            {
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
				/** ------------------------------------
				/**  Load Extension and Fetch Metadata
				/** ------------------------------------*/
				
				$meta 		= array();
				$class_name = ucfirst($extension_name);
				
				if ( ! class_exists($class_name))
        		{
        			if ($debug === TRUE)
        			{
        				include(PATH_EXT.'ext.'.$extension_name.EXT);
        			}
        			else
        			{
        				@include(PATH_EXT.'ext.'.$extension_name.EXT);
        			}
        			
        			if ( ! class_exists($class_name)) continue;
        		}
        			
        		$OBJ = new $class_name();
        			
        		foreach($extension_meta as $meta_item)
        		{
        			${$meta_item} = ( ! isset($OBJ->{$meta_item})) ? '' : $OBJ->{$meta_item};
        		}
        		
        		if ($name == '')
        		{
        			$name = ucwords(str_replace('_',' ',$extension_name));
        		}
        		
        		/** ------------------------------------
				/**  Different Output depending on current status
				/** ------------------------------------*/
				
				if ($PREFS->ini('allow_extensions') == 'y' && isset($extensions_installed[$extension_name]))
				{
					// Double check that the extension is up to date
					// If not, then quickly run the update script to make
					// sure that we are up to date before changing any settings
					
					if ($OBJ->version > $EXT->version_numbers[$class_name] && method_exists($OBJ, 'update_extension') === TRUE)
        			{
        				$update = $OBJ->update_extension($EXT->version_numbers[$class_name]);
        				
        				$EXT->version_numbers[$class_name] = $OBJ->version;
    	    		}
				
					$installed = $LANG->line('extension_enabled') . ' ('.$DSP->anchor(BASE.AMP.'C=admin'.
																		 AMP.'M=utilities'.
																		 AMP.'P=toggle_extension'.
																		 AMP.'which=disable'.
																		 AMP.'name='.$extension_name,
																		 $LANG->line('disable_extension'),
																		 "onclick='if(!confirm(\"".
																		$LANG->line('toggle_extension_confirmation').
																		"\")) return false;'").')';
																		 
					$link = $DSP->qspan('defaultBold', $name).' (v.'.$version.')';
					
					if ($description != '' && $description != '')
					{
						$link .= NL.$DSP->br().NL.$description;
					}
					
					$settings_link = $DSP->anchor(BASE.AMP.'C=admin'.
											  AMP.'M=utilities'.
											  AMP.'P=extension_settings'.
											  AMP.'name='.$extension_name,
											  $LANG->line('settings'));
				}
				else
				{
				
					$link = $DSP->qspan('defaultLight', $name.' (v.'.$version.')');
				
					if ($PREFS->ini('allow_extensions') == 'y')
					{
						$installed = $LANG->line('extension_disabled') . ' ('.$DSP->anchor(BASE.AMP.'C=admin'.
																		 AMP.'M=utilities'.
																		 AMP.'P=toggle_extension'.
																		 AMP.'which=enable'.
																		 AMP.'name='.$extension_name,
																		 $LANG->line('enable_extension'),
																		 "onclick='if(!confirm(\"".
																		$LANG->line('toggle_extension_confirmation').
																		"\")) return false;'").')';
					}
					else
					{
						$installed = $LANG->line('extension_disabled');
					}
					
					$settings_link = $DSP->qspan('defaultLight', $LANG->line('settings'));
				}
											  
				if ($docs_url != '')
				{
					$docs_url = $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.urlencode($docs_url), $LANG->line('documentation'), '', TRUE);
				}
					
				$r .= 	$DSP->tr()
						.	$DSP->table_qcell($style, $link, '55%')
						.	$DSP->table_qcell($style, ($docs_url == '') ? '--' : $docs_url, '15%')
						.	$DSP->table_qcell($style, ($settings_exist != 'y') ? '--' : $settings_link, '15%')
						.	$DSP->table_qcell($style, $installed, '15%')
						.$DSP->tr_c();
						
				unset($OBJ);
            }
        }
        
        $r .= $DSP->table_c();
        
        $DSP->title  = $LANG->line('extensions_manager');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($LANG->line('extensions_manager'));
        $DSP->body   = $r;  
	}
	/* END */
	
	
	/** -------------------------------------------
    /**  Extension Settings Form
    /** -------------------------------------------*/

	function extension_settings($message = '')
	{
        global $DSP, $IN, $PREFS, $LANG, $DB, $FNS, $REGX;
        
		if ($PREFS->ini('allow_extensions') != 'y')
		{
			return $DSP->no_access_message();
		}
		
        if ($IN->GBL('name') === FALSE OR ! preg_match("/^[a-z0-9][\w.-]*$/i",$IN->GBL('name'))) return false;
        
        $class_name = ucfirst($IN->GBL('name'));
        $current	= array();
        
        /** ---------------------------------------
		/**  Extensions Enabled
		/** ---------------------------------------*/
        
        $query = $DB->query("SELECT settings FROM exp_extensions 
        					 WHERE enabled = 'y' AND class = '".$DB->escape_str($class_name)."'
        					 LIMIT 1");
        
        if ($query->num_rows > 0 && $query->row['settings'] != '')
        {
        	$current = $REGX->array_stripslashes(unserialize($query->row['settings']));
        }
        
        /** -----------------------------
    	/**  Call Extension File
    	/** -----------------------------*/
        
        if ( ! class_exists($class_name))
        {
        	@include(PATH_EXT.'ext.'.$IN->GBL('name').EXT);
        			
        	if ( ! class_exists($class_name)) return false;
        }
        			
        $OBJ = new $class_name();
        
        foreach(array('description', 'settings_exist', 'docs_url', 'name', 'version') as $meta_item)
        {
        	${$meta_item} = ( ! isset($OBJ->{$meta_item})) ? '' : $OBJ->{$meta_item};
        }
        		
        if ($name == '')
        {
        	$name = ucwords(str_replace('_',' ',$extension_name));
        }
        
        // -----------------------------------
    	//  Fetch Extension Language file
    	//
    	//  If there are settings, then there is a language file
    	//  because we need to know all the various variable names in the settings
    	//  form.  I was tempted to give these language files a prefix but I 
    	//  decided against it for the sake of simplicity and the fact that 
    	//  a module might have extension's bundled with them and it would make
    	//  sense to have the same language file for both.
    	// -----------------------------------

		$LANG->fetch_language_file($IN->GBL('name'));
		
		/** ---------------------------------------
		/**  Creating Their Own Settings Form?
		/** ---------------------------------------*/
		
		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			return $OBJ->settings_form($current);
		}
		
		/** ---------------------------------------
		/**  Right Crumb Tab
		/** ---------------------------------------*/
		
		$DSP->crumbline = TRUE;
		
		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));
    	
    	/** -----------------------------
    	/**  Create Page's Content
    	/** -----------------------------*/
    	
       	$r = $DSP->table('', '', '', '100%')
             .$DSP->tr()
             .$DSP->td('default', '', '', '', 'top')
             .$DSP->heading($LANG->line('extension_settings'));
             
        $qm		= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $docs	= ($docs_url == '') ? '' : ' ['.$DSP->anchor($FNS->fetch_site_index().$qm.'URL='.urlencode($docs_url), $LANG->line('documentation'), '', TRUE).']';

        $r .= $DSP->td_c()
             .$DSP->td('default', '', '', '', 'middle')
             .$DSP->qdiv('defaultRight', '<strong>'.$docs.'</strong>'.NBS.NBS)
             .$DSP->td_c()
             .$DSP->tr_c()
             .$DSP->tr()
             .$DSP->td('default', '100%', '2', '', 'top');
             
        $r .= Utilities::extension_settings_form($name, $IN->GBL('name'), $OBJ->settings(), $current);
             
		$r .=  $DSP->td_c()
			  .$DSP->tr_c()
			  .$DSP->table_c()
			  .$DSP->td_c()
			  .$DSP->tr_c()
			  .$DSP->table_c();
			  
		$DSP->title  = $LANG->line('extension_settings');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));
        $DSP->crumb .= $DSP->crumb_item($name);
        $DSP->body   = $r;  
	}
	/* END */
	
	
	/** -----------------------------
    /**  Store Extension Settings
    /** -----------------------------*/
    
    function save_extension_settings()
    {
		global $IN, $DB, $FNS;
		
		// Basic security check
       	if ( ! preg_match("/^[a-z0-9][\w.-]*$/i",$IN->GBL('name'))) return false;
       	
       	if ( ! class_exists(ucfirst($IN->GBL('name'))))
     	{
     		include(PATH_EXT.'ext.'.strtolower($IN->GBL('name')).EXT);
		}
		
		// Ok, I admit that we should be able to simply unset the 'name' value
		// from the $_POST array and simply insert that into the database.
		// I, Paul Burdick of the Sinister House geeks, am slowly becoming
		// anal retentive in my young age and decided to make sure only those
		// settings specified by the extension are inserted AND that there is
		// always an empty string in the really rare chance that one is not
		// specified.
        			
        if (class_exists(ucfirst($IN->GBL('name'))))
        {
        	$class_name = ucfirst($IN->GBL('name'));
        				
        	$OBJ = new $class_name();
        	
        	/** ---------------------------------------
			/**  Processing Their Own Settings Form?
			/** ---------------------------------------*/
			
			if (method_exists($OBJ, 'settings_form') === TRUE)
			{
				$OBJ->save_settings();
				
				$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager');
				exit;
			}
        				
        	if (method_exists($OBJ, 'settings') === TRUE)
        	{
        		$settings = $OBJ->settings();
        	}
        	
        	$insert = array();
        	
        	foreach($settings as $key => $value)
        	{
        		if ( ! is_array($value))
        		{
        			$insert[$key] = ($IN->GBL($key, 'POST') !== FALSE) ? $IN->GBL($key, 'POST') : $value;
        		}
        		elseif (is_array($value) && isset($value['1']) && is_array($value['1']))
        		{
        			if(is_array($IN->GBL($key, 'POST')) OR $value[0] == 'ms')
        			{
        				$data = (is_array($IN->GBL($key, 'POST'))) ? $IN->GBL($key, 'POST') : array();
        				
        				$data = array_intersect($data, array_keys($value['1']));
        			}
        			else
        			{
        				if ($IN->GBL($key, 'POST') === FALSE)
        				{
        					$data = ( ! isset($value['2'])) ? '' : $value['2'];
        				}
        				else
        				{
        					$data = $IN->GBL($key, 'POST');
        				}
        			}
        			
        			$insert[$key] = $data;
        		}
        		else
        		{
        			$insert[$key] = ($IN->GBL($key, 'POST') !== FALSE) ? $IN->GBL($key, 'POST') : '';
        		}
        	}

			$DB->query("UPDATE exp_extensions SET settings = '".addslashes(serialize($insert))."' WHERE class = '".$DB->escape_str(ucfirst($IN->GBL('name')))."'");
		}
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager');
		exit;
	}
	/* END */
	
	
	
	/** -----------------------------
    /**  Create Form Automagically
    /** -----------------------------*/
    
    function extension_settings_form($extension_name, $name, $fdata, $data)
    {
    	global $DSP, $LANG;				

		$r  =	$DSP->form_open(
									array(
											'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
											'name'	=> 'settings_'.$name,
											'id'	=> 'settings_'.$name
										),
										
									array('name' => $name)
								);
		
		
		$r .=	$DSP->table('tableBorder', '0', '', '100%');
		$r .=	$DSP->tr();
		$r .=   $DSP->td('tableHeadingAlt', '', '2');
		$r .=   $extension_name;
		$r .=   $DSP->td_c();
		$r .=	$DSP->tr_c();
		
		$i = 0;
		
		/** -----------------------------
		/**  Blast through the array
		/** -----------------------------*/
				
		foreach ($fdata as $key => $val)				
		{		
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			
			$default_data = (is_array($val)) ? '' : $val;
			
			$data[$key] = ( ! isset($data[$key])) ? $default_data : $data[$key];
			
			if (!is_array($val) || $val['0'] != 'sf')
			{
				$r .=	$DSP->tr();
				
				// If the form type is a textarea, we'll align the text at the top, otherwise, we'll center it
			
				if (is_array($val) && ($val['0'] == 't' OR $val['0'] == 'ms' OR ($val['0'] == 'c' && sizeof($val['1']) > 1)))
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
				
				// Fix for array form variables like cat_id[]
				// Such names to do no work well with the
				// translation utility sadly.
				
				if (($LANG->line($key) === false || $LANG->line($key) == '') && strpos($key, '[]') !== false)
				{
					if ($LANG->line(str_replace('[]','',$key)) === FALSE)
					{
						$r .= '<label for="'.$key.'">'.ucwords(str_replace('_', ' ', $key))."</label>";
					}
					else
					{
						$r .= $LANG->line(str_replace('[]','',$key), $label);
					}
				}
				else
				{
					$r .= $LANG->line($key, $label);
				}
	
				$r .= $DSP->div_c();
			
			
				/** -----------------------------
				/**  Preference sub-heading
				/** -----------------------------*/
			
				$r .= $DSP->td_c();
			
				/** -----------------------------
				/**  Preference value
				/** -----------------------------*/
				
				$r .= $DSP->td($style, '50%', '');
			}
			
				if (is_array($val))
				{
					/** -----------------------------
					/**  Drop-down menus
					/** -----------------------------*/
								
					if ($val['0'] == 's' || $val['0'] == 'ms')
					{
						$multi = ($val['0'] == 'ms') ? "class='multiselect' size='8' multiple='multiple'" : "class='select'";
						$nkey = ($val['0'] == 'ms') ? $key.'[]' : $key;
					
						if (isset($val['2']))
						{
							$r .= "<select name='{$nkey}' $multi ".$val['2'].">\n";
						}
						else
						{
							$r .= "<select name='{$nkey}' $multi >\n";
						}
						
						$data[$key] = ($data[$key] == '') ? $val['2'] : $data[$key];
						
						foreach ($val['1'] as $k => $v)
						{
							if ($val['0'] == 's' || ! is_array($data[$key]))
							{
								$selected = ($k == $data[$key]) ? 1 : '';
							}
							elseif(is_array($data[$key]))
							{
								$selected = (in_array($k,$data[$key])) ? 1 : '';								
							}
						
							$name = ($LANG->line($v) == false OR $key == 'weblog_id') ? $v : $LANG->line($v);
						
							$r .= $DSP->input_select_option($k, $name, $selected);
						}
						
						$r .= $DSP->input_select_footer();
						
					} 
					elseif ($val['0'] == 'r')
					{
						/** -----------------------------
						/**  Radio buttons
						/** -----------------------------*/
						
						if ( ! isset($val['2']))
						{
							$val['2'] = '';
						}
						
						$data[$key] = ($data[$key] == '') ? $val['2'] : $data[$key];
					
						foreach ($val['1'] as $k => $v)
						{
							$selected = ($k == $data[$key]) ? 1 : '';
						
							$r .= $LANG->line($v).$DSP->nbs();
							$r .= $DSP->input_radio($key, $k, $selected, ( ! isset($val['3'])) ? '' : $val['3']).$DSP->nbs(3);
						}					
					}
					elseif ($val['0'] == 'c')
					{
						/** -----------------------------
						/**  Checkboxes
						/** -----------------------------*/
						
						if ( ! isset($val['2']))
						{
							$val['2'] = '';
						}
						
						$data[$key] = ($data[$key] == '') ? $val['2'] : $data[$key];
					
						foreach ($val['1'] as $k => $v)
						{
							$selected = ($k == $data[$key]) ? 1 : '';
							
							if (sizeof($val['1']) == 1)
							{
								$r .= $DSP->input_checkbox($key, $k, $selected);
							}
							else
							{
								$r .= $DSP->qdiv('publishPad', $DSP->input_checkbox($key, $k, $selected).' '.$LANG->line($v));
							}							
						}					
					}
					elseif ($val['0'] == 't')
					{
						/** -----------------------------
						/**  Textarea fields
						/** -----------------------------*/
						
						// The "kill_pipes" index instructs us to 
						// turn pipes into newlines
						
						$data[$key] = ($data[$key] == '') ? $val['1'] : $data[$key];
						
						if (isset($val['2']['kill_pipes']) AND $val['2']['kill_pipes'] === TRUE)
						{
							$text	= '';
							
							foreach (explode('|', $data[$key]) as $exp)
							{
								$text .= $exp.NL;
							}
						}
						else
						{
							$text = $data[$key];
						}
												
						$rows = (isset($val['2']['rows'])) ? $val['2']['rows'] : '15';
						
						$r .= $DSP->input_textarea($key, $text, $rows);
						
					}
					elseif ($val['0'] == 'f' || $val['0'] == 'sf')
					{
						switch($val['1'])
						{
							case 'new_table' :  
							
								$i = 0;
								// Close current tables
								$r .= $DSP->table_c();	
								
								$r .= $DSP->div_c();
								
								// Open new table
								$r .= $DSP->div('', '', $key);
																
								$r .= $DSP->table('tableBorder', '0', '', '100%');
								$r .= $DSP->tr();
								$r .= $DSP->td('tableHeadingAlt', '', '2');
								$r .= $LANG->line($val['2']);
								$r .= $DSP->td_c();
								$r .= $DSP->tr_c();	
						
							break;
						}
					}
				}
				else
				{
					/** -----------------------------
					/**  Text input fields
					/** -----------------------------*/
				
					$r .= $DSP->input_text($key, $data[$key], '20', '120', 'input', '100%');
				}
				
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
		}
				
		$r .= $DSP->table_c();
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit'), 'submit'));
		
		return $r;
    }
    /* END */
	
	
	/** -------------------------------------------
    /**  Plugin Manager
    /** -------------------------------------------*/

	// Helper function used to sort plugins
	function _plugin_title_sorter($a, $b)
	{
		return strnatcasecmp($a['title'], $b['title']);
	}

	function plugin_manager($message = '')
	{
        global $DSP, $IN, $PREFS, $LANG, $FNS;
     
		if ( ! @include_once(PATH_LIB.'pclzip.lib.php'))
		{
			return $DSP->no_access_message('PclZip Library does not appear to be installed.  It is required.');
		}     
     
		$is_writable = (is_writable(PATH_PI) && $PREFS->ini('demo_date') == FALSE) ? TRUE : FALSE;
        
        $plugins = array();
		$info 	= array();
		
		if ($fp = @opendir(PATH_PI))
        { 
            while (false !== ($file = readdir($fp)))
            {
            	if ( preg_match("/^pi\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
            	{
					if ( ! @include_once(PATH_PI.$file))
					{
						continue;
                	}
                	
                    $name = str_replace('pi.', '', $file);
                	$name = str_replace(EXT, '', $name);
                                    
					$plugins[] = $name;
					                    
                    $info[$name] = $plugin_info;
                }
            }         
			
			closedir($fp); 
        } 	

  		if ( in_array('magpie', $plugins) && $PREFS->ini('demo_date') == FALSE)
      		$r = '<div style="float: left; width: 69%; margin-right: 2%;">';
      	else
      		$r = '<div style="float: left; width: 100%;">';
  		        
		if ($is_writable)
		{
              $r .= $DSP->form_open(
              							array(
              									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_remove_conf', 
              									'name'	=> 'target',
              									'id'	=> 'target'
              								)
              						);
              $r .= $DSP->toggle();
        }
        
        if ($message != '')
        {
        	$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $message));
        }
              
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', ($is_writable) ? '97%' : '100%', '').
              count($plugins).' '.$LANG->line('plugin_installed').
              $DSP->td_c();
              
		if ($is_writable)
		{
			$r .= $DSP->td('tableHeading', '3%', '').
				  $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").
				  $DSP->td_c();
		}
		
		$r .= $DSP->tr_c();
  
        if (count($plugins) == 0)
        {
            $r .= $DSP->tr().
                  $DSP->td('tableCellTwo', '', '2').
                  '<b>'.$LANG->line('no_plugins_exist').'</b>'.
                  $DSP->td_c().
                  $DSP->tr_c();
        }  

        $i = 0;
        
        if (count($plugins) > 0)
        {
            foreach ($plugins as $plugin)
            {
				$version = '(v.'.trim($info[$plugin]['pi_version']).')';
				$update = '';
				
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
				$name = $DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_info'.AMP.'name='.$plugin, $info[$plugin]['pi_name']));
				$description = $info[$plugin]['pi_description'];
				
				$r .= $DSP->tr();
				
				$r .= $DSP->table_qcell($style, $name.' '.$version.' '.$update.$DSP->br().$description, ($is_writable) ? '85%' : '100%');
		  
				if ($is_writable)
				{
					$r .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $plugin), '15%');
				}
				
				$r .= $DSP->tr_c();
            }
        }
        
        $r .= $DSP->table_c();
             
		if ($is_writable)
		{
             $r .= $DSP->div('itemWrapper', 'right')
				 .$DSP->input_submit($LANG->line('plugin_remove'))
				 .$DSP->div_c()
				 .$DSP->form_close();
		}
		
		$r .= $DSP->div_c();

             
        /** -------------------------------------------
        /**  Latest Plugin Table
        /** -------------------------------------------*/
        
        // Do we have the Magpie plugin so we can parse the EE plugin RSS feed?
        if (in_array('magpie', $plugins) && $PREFS->ini('demo_date') == FALSE)
        {
        	$request = 'http://expressionengine.com/feeds/pluginlist/';
    		
    		$target = parse_url($request);
			
			$fp = @fsockopen($target['host'], 80, $errno, $errstr, 15);
			
			$code = '';
			
			if (is_resource($fp))
			{
				fputs ($fp,"GET " . $request . " HTTP/1.0\r\n" ); 
				fputs ($fp,"Host: " . $target['host'] . "\r\n" ); 
				fputs ($fp,"User-Agent: EE/EllisLab PHP/" . phpversion() . "\r\n\r\n");
				
				$getting_headers = true;
			
				while ( ! feof($fp))
				{
					$line = fgets($fp, 4096);
					
					if ($getting_headers == false)
					{
						$code .= $line;
					}
					elseif (trim($line) == '')
					{
						$getting_headers = false;
					}
				}
	
				@fclose($fp);    	
			}
            
            $plugins = new MagpieRSS($code);
			
			$i = 0;
			
			if (count($plugins->items) > 0)
			{
				// Example pagination: &perpage=10&page=10&sortby=alpha
				$paginate = '';
				$extra = ''; // Will hold sort method
				$total_rows = count($plugins->items);
				$perpage = ( ! $IN->GBL('perpage')) ? 10 : $IN->GBL('perpage');
				$page = ( ! $IN->GBL('page')) ? 0 : $IN->GBL('page');
				$sortby = ( ! $IN->GBL('sortby')) ? '' : $IN->GBL('sortby');
				$base = BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_manager';				
				
				if ($sortby == 'alpha')
				{
					usort($plugins->items, array('Utilities', '_plugin_title_sorter'));
					$extra = AMP.'sortby=alpha';
					$link = $DSP->anchor($base, $LANG->line('plugin_by_date'));
					$title = $LANG->line('plugins').$DSP->qspan('defaultSmall', $LANG->line('plugin_by_letter').' : '.$link);
				}
				else
				{
					$link = $DSP->anchor($base.AMP.'sortby=alpha', $LANG->line('plugin_by_letter'));
					$title = $LANG->line('plugins').$DSP->qspan('defaultSmall', $LANG->line('plugin_by_date').' : '.$link);
				}

				$ten_plugins = array_slice($plugins->items, $page, $perpage-1);
				
				// Latest Plugins Table
				$r .= '<div style="float: left; width: 29%; clear: right;">';
				
				$r .= $DSP->table('tableBorder', '0', '10', '100%').
					$DSP->tr().
					$DSP->td('tableHeadingAlt', '', '').
					$title.
					$DSP->td_c().
					$DSP->tr_c();
				
				$curl_installed = ( ! extension_loaded('curl') || ! function_exists('curl_init')) ? FALSE : TRUE;
				
				$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';	
				
				foreach ($ten_plugins as $item)
				{
					$attr = explode('|', $item['dc']['subject']);
					$dl = $attr[0];
					$version = '(v.'.$attr[1].')';
					$require = ( ! $attr[2] ) ? '' : $DSP->br().$DSP->qspan('highlight', $LANG->line('plugin_requires').': '.$attr[2]);
					
					$name = $DSP->qspan('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$item['link'], $item['title']));
					$description = $FNS->word_limiter($item['description'], '20');
					
					$install = ( ! class_exists('PclZip') || ! $is_writable || ! $curl_installed) ? '' : $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_install'.AMP.'file='.$dl, '<span style=\'color:#009933;\'>'.$LANG->line('plugin_install').'</span>');
					
					$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
					
					$r .= $DSP->tr();
					
					$r .= $DSP->table_qcell($style, $name.' '.$version.$DSP->nbs().$require.$DSP->qdiv('itemWrapper', $description).$install, '60%');

					$r .= $DSP->tr_c();
				}
				
				$r .= $DSP->table_c();
					
				if ($total_rows > $perpage)
				{		 
					$paginate = $DSP->pager(  BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_manager'.$extra.AMP.'perpage='.$perpage,
											  $total_rows, 
											  $perpage,
											  $page,
											  'page'
											);
				}
				
				$r .= $DSP->qdiv('itemWrapper', $paginate.BR.BR);
				$r .= $DSP->div_c();
			}
			
		}
                
        $DSP->title  = $LANG->line('plugin_manager');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($LANG->line('plugin_manager'));
        $DSP->body   = $r;  
	}
	/* END */


    /** -------------------------------------------
    /**  Plugin Info
    /** -------------------------------------------*/

    function plugin_info()
    {
		global $IN, $DSP, $LANG, $FNS, $PREFS;
		
		// Basic security check
		if ( ! preg_match("/^[a-z0-9][\w.-]*$/i",$IN->GBL('name'))) return false;
		
		$name = $IN->GBL('name');
		
		if ( ! @include(PATH_PI.'pi.'.$name.EXT))
		{
			return $DSP->error_message('Unable to load the following plugin: '.$name.EXT);
		}     
		
		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
			                
        $DSP->title  = ucwords(str_replace("_", " ", $name));
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_manager', $LANG->line('plugin_manager'))).
					   $DSP->crumb_item(ucwords(str_replace("_", " ", $name)));

        $i = 0;
        
        $r  = $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').
              $LANG->line('plugin_information').
              $DSP->td_c().
              $DSP->tr_c();  
             		
		if ( ! isset($plugin_info) OR ! is_array($plugin_info))
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$name = ucwords(str_replace("_", " ", $name));

			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('pi_name')), '30%');
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $name), '70%');
			$r .= $DSP->tr_c();
			
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->tr();
			$r .= $DSP->td($style, '', '2').$DSP->qspan('default', $LANG->line('no_additional_info'));
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
              
        }
        else
        {        
			foreach ($plugin_info as $key => $val)
			{ 
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
				$item = ($LANG->line($key) != FALSE) ? $LANG->line($key) : ucwords(str_replace("_", " ", $key));
				
				if ($key == 'pi_author_url')
				{
					if (substr($val, 0, 4) != "http") 
						$val = "http://".$val; 
						
						$val = $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$val, $val, '', 1);
				}
				
				if ($key == 'pi_usage')
					$val = nl2br(htmlspecialchars($val));
					
				$r .= $DSP->tr();
				$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $item), '30%', 'top');
				$r .= $DSP->table_qcell($style, $DSP->qspan('default', $val), '70%');
				$r .= $DSP->tr_c();
			}
  		}

        $r .= $DSP->table_c();
        
		$DSP->body = $r;
	}
	/* END */
	
	/** -------------------------------------------
    /**  Plugin Extraction from ZIP file
    /** -------------------------------------------*/
	
	function plugin_install()
	{		
        global $IN, $DSP, $LANG, $PREFS;
        
		if ($PREFS->ini('demo_date') != FALSE)
		{
            return $DSP->no_access_message();
		}
		
		if ( ! @include_once(PATH_LIB.'pclzip.lib.php'))
		{
			return $DSP->error_message($LANG->line('plugin_zlib_missing'));
		}
		
		if ( ! is_writable(PATH_PI))
		{
			return $DSP->error_message($LANG->line('plugin_folder_not_writable'));
		}
		
		if ( ! extension_loaded('curl') || ! function_exists('curl_init'))
		{
			return $DSP->error_message($LANG->line('plugin_no_curl_support'));
		}
        
        $file = $IN->GBL('file');
                
        $local_name = basename($file);
        $local_file = PATH_PI.$local_name;
        
		// Get the remote file
		$c = curl_init($file);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		
		// prevent a PHP warning on certain servers
		if (! ini_get('safe_mode') && ! ini_get('open_basedir'))
		{
		    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
		}
		
		$code = curl_exec($c);
		curl_close($c);
	    
	    $file_info = pathinfo($local_file);
	
	    if ($file_info['extension'] == 'txt' ) // Get rid of any notes/headers in the TXT file
        {
			$code = strstr($code, '<?php');
		}
	    
	    if ( ! $fp = fopen($local_file, 'wb'))
	    {
			return $DSP->error_message($LANG->line('plugin_problem_creating_file'));
	    }
	    
	    flock($fp, LOCK_EX);
	    fwrite($fp, $code);
	    flock($fp, LOCK_UN);
	    fclose($fp);

	    @chmod($local_file, 0777);
	    
        // Check file information so we know what to do with it
        
		if ($file_info['extension'] == 'txt' ) // We've got a TXT file!
        {
			$new_file = basename($local_file, '.txt');
			if ( ! rename($local_file, PATH_PI.$new_file))
			{
				$message = $LANG->line('plugin_install_other');
			}
			else
			{
				@chmod($new_file, 0777);
				$message = $LANG->line('plugin_install_success');
			}
        }
        else if ($file_info['extension'] == 'zip' ) // We've got a ZIP file!
        {
        	// Unzip and install plugin
			if (class_exists('PclZip'))
			{
				$zip = new PclZip($local_file);
				chdir(PATH_PI);
				$ok = @$zip->extract('');
				
				if ($ok)
				{
					$message = $LANG->line('plugin_install_success');
					unlink($local_file);
				}
				else
				{
					$message = $LANG->line('plugin_error_uncompress');
				}
				
				chdir(PATH);
			}
			else
			{
				$message = $LANG->line('plugin_error_no_zlib');
			}
        }
        else
        {
        		$message = $LANG->line('plugin_install_other');
        }
		
		return Utilities::plugin_manager($message);

	}
	/* END */
	
	
	/** -------------------------------------------
    /**  Plugin Removal Confirmation
    /** -------------------------------------------*/
    
    function plugin_remove_confirm()
    {
        global $IN, $DSP, $LANG, $PREFS;

		if ($PREFS->ini('demo_date') != FALSE)
		{
            return $DSP->no_access_message();
		}
		
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_remove'));
        
        $i = 0;
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $r .= $DSP->input_hidden('deleted[]', $val);
                
                $i++;
            }        
        }
                
        $message = ($i == 1) ? 'plugin_single_confirm' : 'plugin_multiple_confirm';
                
		$r	.= 	 $DSP->qdiv('alertHeading', $LANG->line('plugin_delete_confirm'))
				.$DSP->div('box')
				.$DSP->qdiv('itemWrapper', '<b>'.$LANG->line($message).'</b>');
				
		$r .=	 $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('action_can_not_be_undone')))
				.$DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('deinstall')).BR)
				.$DSP->div_c()
				.$DSP->form_close();
				
		
        
        $DSP->title = $LANG->line('plugin_delete_confirm');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_manager', $LANG->line('plugin_manager'))).
					  $DSP->crumb_item($LANG->line('plugin_delete_confirm'));         
        $DSP->body  = $r;
    }
    
    /** -------------------------------------------
    /**  Plugin Removal
    /** -------------------------------------------*/
    
    function plugin_remove()
    {
        global $IN, $DSP, $LANG, $PREFS;
        
		if ($PREFS->ini('demo_date') != FALSE)
		{
            return $DSP->no_access_message();
		}
		
        $deleted = $IN->GBL('deleted');
        $message = '';
        $style = '';
        $i = 0;
        
        $DSP->title  = $LANG->line('plugin_removal');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=plugin_manager', $LANG->line('plugin_manager'))).
					   $DSP->crumb_item($LANG->line('plugin_removal'));
        
        $r  = $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '').
              $LANG->line('plugin_removal_status').
              $DSP->td_c().
              $DSP->tr_c();
        
        foreach ( $deleted as $name )
        {
        		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
        	
            if (unlink(PATH_PI.'pi.'.$name.'.php'))
				$message = $LANG->line('plugin_removal_success').' '.ucwords(str_replace("_", " ", $name));
			else
				$message = $LANG->line('plugin_removal_error').' '.ucwords(str_replace("_", " ", $name)).'.';
				
			$r .= $DSP->tr();
       		$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $message)), '100%');
        		$r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();
        
		$DSP->body = $r;
    }
    /* END */
    
    
	/** -------------------------------------------
    /**  Disable Extensions Confirmation
    /** -------------------------------------------*/
    
    function toggle_extension_confirm()
    {
        global $IN, $DSP, $LANG;
        
        // Basic security check
        if ($IN->GBL('name') !== FALSE && ! preg_match("/^[a-z0-9][\w.-]*$/i",$IN->GBL('name'))) return false;

        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension'));
        
       	if ($IN->GBL('which') == 'enable')
        {
        	$message = ($IN->GBL('name') !== FALSE) ? 'enable_extension_conf' : 'enable_extensions_conf';
        	
        	$r .= $DSP->input_hidden('which', 'enable');
        }
        else
        {
        	$message = ($IN->GBL('name') !== FALSE) ? 'disable_extension_conf' : 'disable_extensions_conf';
        	
        	$r .= $DSP->input_hidden('which', 'disable');
        }
        
        $r .= $DSP->input_hidden('name', ($IN->GBL('name') !== FALSE) ? $IN->GBL('name') : '');
                
		$r	.= 	 $DSP->qdiv('alertHeading', $LANG->line($message))
				.$DSP->div('box')
				.$DSP->qdiv('itemWrapper', '<b>'.$LANG->line('toggle_extension_confirmation').'</b>');
				
		$r .=	 $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('submit')).BR)
				.$DSP->div_c()
				.$DSP->form_close();
				
		
        
        $DSP->title = $LANG->line('extensions_manager');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager'))).
					  $DSP->crumb_item($LANG->line($message));         
        $DSP->body  = $r;
    }
    
    /** -------------------------------------------
    /**  Toggle Extension/s
    /** -------------------------------------------*/
    
    function toggle_extension()
    {
        global $IN, $FNS, $DB, $PREFS;

        $message = '';
        
        if ($IN->GBL('name') !== FALSE && $IN->GBL('name') != '')
        {
        	// Basic security check
       		if ( ! preg_match("/^[a-z0-9][\w.-]*$/i",$IN->GBL('name'))) return false;
        
        	// Disable/Enable Single Extension
        	
        	if ($IN->GBL('which') == 'enable')
        	{
        		// Check if the Extension is already installed and just disabled
        		// If so we just turn it back on.  If not, we have to activate
        		// the extension.  We have the enabled field so that if someone
        		// disables a parameter we can still have the extension's settings
        		// in the database and not lost to the ether.
        		
        		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_extensions WHERE class = '".$DB->escape_str(ucfirst($IN->GBL('name')))."'");
        	
        		if ($query->row['count'] == 0)
        		{
        			if ( ! class_exists(ucfirst($IN->GBL('name'))))
        			{
        				include(PATH_EXT.'ext.'.$IN->GBL('name').EXT);
        			}
        			
        			if (class_exists(ucfirst($IN->GBL('name'))))
        			{
        				$class_name = ucfirst($IN->GBL('name'));
        				
        				$OBJ = new $class_name();
        				
        				if (method_exists($OBJ, 'activate_extension') === TRUE)
        				{
        					$activate = $OBJ->activate_extension();
        				}
        			}
        		}
        		else
        		{
        			$DB->query("UPDATE exp_extensions SET enabled = 'y' WHERE class = '".$DB->escape_str(ucfirst($IN->GBL('name')))."'");
        		}
        	}
        	else
        	{
        		$DB->query("UPDATE exp_extensions SET enabled = 'n' WHERE class = '".$DB->escape_str(ucfirst($IN->GBL('name')))."'");
        		
        		if ( ! class_exists(ucfirst($IN->GBL('name'))))
				{
					include(PATH_EXT.'ext.'.$IN->GBL('name').EXT);
				}
				
				if (class_exists(ucfirst($IN->GBL('name'))))
				{
					$class_name = ucfirst($IN->GBL('name'));
					
					$OBJ = new $class_name();
					
					if (method_exists($OBJ, 'disable_extension') === TRUE)
					{
						$disable = $OBJ->disable_extension();
					}
				}
        	}
        }
        else
        {
        	// Disable/Enable All Extensions
        	
        	if ($IN->GBL('which') == 'enable')
        	{
        		Admin::update_config_file(array('allow_extensions' => "y"));
        	}
        	else
        	{
        		Admin::update_config_file(array('allow_extensions' => "n"));
        	}
        }
        
        $FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager');
		exit;
    }
    /* END */
    

    /** -------------------------------------------
    /**  SQL Manager
    /** -------------------------------------------*/

    function sql_info()
    {
        global $DB, $DSP, $PREFS, $LOC, $LANG;
        
		$i = 0;
		$style_one 	= 'tableCellOne';
		$style_two 	= 'tableCellTwo';
			
        
        $query = $DB->query("SELECT version() AS ver");
		
        $DSP->title = $LANG->line('utilities');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($LANG->line('sql_manager'));
                                
        $DSP->body = $DSP->qdiv('tableHeading', $LANG->line('sql_manager'));
              
		/** -----------------------------
    	/**  Table Header
    	/** -----------------------------*/

        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr().
						$DSP->table_qcell('tableHeadingAlt', 
											array(
													$LANG->line('sql_info'),
													$LANG->line('value')
												 )
											).
						$DSP->tr_c();
						
					
		/** -------------------------------------------
		/**  Database Type
		/** -------------------------------------------*/
  		
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('database_type')),
												$PREFS->ini('db_type')
											  )
										);			
  		
  		
  		
		/** -------------------------------------------
		/**  SQL Version
		/** -------------------------------------------*/
  		
		$query = $DB->query("SELECT version() AS ver");
				
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('sql_version')),
												$query->row['ver']
											  )
										);	
										
										

        $DB->fetch_fields = TRUE;

        $query = $DB->query("SHOW TABLE STATUS FROM `".$PREFS->ini('db_name')."`");

		$totsize = 0;
		$records = 0;
		
		$prelen = strlen($DB->prefix);
		
        foreach ($query->result as $val)
        {
            if (strncmp($val['Name'], $DB->prefix, $prelen) != 0)
            {
                continue;
            }
                                
            $totsize += ($val['Data_length'] + $val['Index_length']);
            $records += $val['Rows'];
        }

			
		/** -------------------------------------------
		/**  Database Records
		/** -------------------------------------------*/
						
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('records')),
												$records
											  )
										);
			
		/** -------------------------------------------
		/**  Database Size
		/** -------------------------------------------*/

        $size = Utilities::byte_format($totsize);
        
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('database_size')),
												$size['0'].' '.$size['1']
											  )
										);

			
		/** -------------------------------------------
		/**  Database Uptime
		/** -------------------------------------------*/
			
        $query = $DB->query("SHOW STATUS");
		
		$uptime  = '';
		$queries = '';
				
		foreach ($query->result as $key => $val)
		{
            foreach ($val as $v)
            {
				if (preg_match("#^uptime#i", $v))
				{
					$uptime = $key;
				}
				
				if (preg_match("#^questions#i", $v))
				{
					$queries = $key;
				}
			}		
		}    
		
				                   
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('database_uptime')),
												$LOC->format_timespan($query->result[$uptime]['Value'])
											  )
										);			
       						
		/** -------------------------------------------
		/**  Total Server Queries
		/** -------------------------------------------*/
       						
       /*
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $LANG->line('total_queries')),
												number_format($query->result[$queries]['Value'])
											  )
										);			
		*/
		
		
        $DSP->body	.=	$DSP->table_c(); 
        
		/** -------------------------------------------
		/**  SQL Utilities
		/** -------------------------------------------*/
       				
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr().
						$DSP->table_qcell('tableHeading', 
											array(
													$LANG->line('sql_utilities'),
												 )
											).
						$DSP->tr_c();
						

		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=view_database', $LANG->line('view_database')))
											  )
										);			
       						
       /*
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_backup', $LANG->line('sql_backup')))
											  )
										);			
		*/
		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_query', $LANG->line('sql_query')))
											  )
										);			


		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_status', $LANG->line('sql_status')))
											  )
										);			


		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_sysvars', $LANG->line('sql_system_vars')))
											  )
										);			

		$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $style_one : $style_two, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_plist', $LANG->line('sql_processlist')))
											  )
										);			

        $DSP->body	.=	$DSP->table_c(); 
    }
    /* END */
 
  
    
    /** -------------------------------------------
    /**  SQL Manager
    /** -------------------------------------------*/

    function sql_manager($process = '', $return = FALSE)
    {  
        global $DSP, $IN, $DB, $REGX, $SESS, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
                
        // We use this conditional only for demo installs.
        // It prevents users from using the SQL manager

		if ($PREFS->ini('demo_date') != FALSE)
		{
            return $DSP->no_access_message();
		}
		                
        $run_query = FALSE;
        $row_limit = 100;
        $paginate  = '';

        
        // Set the "fetch fields" flag to true so that
        // the Query function will return the field names
        
        $DB->fetch_fields = TRUE;
        
        $crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'));

        switch($process)
        {
            case 'plist'    : 
                                $sql 	= "SHOW PROCESSLIST";
								$query  = $DB->query($sql);
                                $title  = $LANG->line('sql_processlist');
                                $crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager')));
                                $crumb .= $DSP->crumb_item($LANG->line('sql_processlist'));
                break;
            case 'sysvars'  : 
								$sql 	= "SHOW VARIABLES";
                                $query	= $DB->query($sql);
                                $title	= $LANG->line('sql_system_vars');
                                $crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager')));
                                $crumb .= $DSP->crumb_item($LANG->line('sql_system_vars'));
                break;
            case 'status'    : 
                                $sql = "SHOW STATUS";
								$query 	= $DB->query($sql); 
                                $title 	= $LANG->line('sql_status');
                                $crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager')));
                                $crumb .= $DSP->crumb_item($LANG->line('sql_status'));
                break;
            case 'run_query' : 
            					$DB->debug = ($IN->GBL('debug', 'POST') !== FALSE) ? TRUE : FALSE;
                                $run_query = TRUE;
                                $title	= $LANG->line('query_result');
								$crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager')));
                                $crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_query', $LANG->line('sql_query')));
                                $crumb .= $DSP->crumb_item($LANG->line('query_result'));
                break;
            default           : return;
                break;
        }

    
        // Fetch the query.  It can either come from a
        // POST request or a url encoded GET request
        
        if ($run_query == TRUE)
        { 
            if ( ! $sql = stripslashes($IN->GBL('thequery', 'POST')))
            {
                if ( ! $sql = $IN->GBL('thequery', 'GET'))
                {
                    return Utilities::sql_query_form();
                }
                else
                {
                    $sql = base64_decode($sql);
                }
            }
                                    
            $sql = trim(str_replace(";", "", $sql));
                        
                        
            // Determine if the query is one of the non-allowed types
    
            $qtypes = array('FLUSH', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK');
                    
            foreach ($qtypes as $type)
            {
                if (preg_match("/(^|\s)".$type."\s/si", $sql))
                {            
                    return $DSP->error_message($LANG->line('sql_not_allowed'));
                }
            }
            
            // If it's a DELETE query, require that a Super Admin be the one submitting it
            
            if (preg_match("#^(DELETE|ALTER|DROP)#i", trim($sql)))
            {
				if ($SESS->userdata['group_id'] != '1')
				{
					return $DSP->no_access_message();
				}
            }
            
            // If it's a SELECT query we'll see if we need to limit
            // the result total and add pagination links
            
            if (stristr($sql, 'SELECT'))
            {
                if ( ! preg_match("/LIMIT\s+[0-9]/i", $sql))
                {
                	// Modify the query so we get the total sans LIMIT
                	
					$row  = ( ! $IN->GBL('ROW')) ? 0 : $IN->GBL('ROW');
                	$new_sql = $sql." LIMIT ".$row.", ".$row_limit;

					// Have to handle this differently for MySQL < 4
					
					if (version_compare(mysql_get_server_info(), '4.0-alpha', '<'))
					{
						$temp_sql = preg_replace("/^(\s*SELECT).+?(FROM .+?)/s", "\\1 count(*) AS count \\2", $sql);

						if ( ! $result = $DB->query($temp_sql))
						{
						   return $DSP->set_return_data( $title, 
														 $DSP->heading($title).
														 $DSP->qdiv('highlight', $LANG->line('sql_no_result')),
														 $crumb
														); 
						}

						if (strpos($temp_sql, 'count(*)') === FALSE)
						{
							$total_results = $result->num_rows;
						}
						else
						{
							$total_results = $result->row['count'];
						}

						// run the data query with the limit
						$query = $DB->query($new_sql);
					}
					else
					{
						// magically delicious method for MySQL 4 and above

	                	$new_sql = preg_replace("/^(\s*SELECT)/", "\\1 SQL_CALC_FOUND_ROWS ", $new_sql);

	                    if ( ! $query = $DB->query($new_sql))
	                    {
						   return $DSP->set_return_data( $title, 
														 $DSP->heading($title).
														 $DSP->qdiv('highlight', $LANG->line('sql_no_result')),
														 $crumb
														); 
	                    }

	                    $result = $DB->query("SELECT FOUND_ROWS() AS total_rows");

	                    $total_results = $result->row['total_rows'];	
					}					


					if ($total_results > $row_limit)
					{  
						$url = BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=run_query'.AMP.'thequery='.base64_encode($sql);

						$paginate = $DSP->pager(  $url,
												  $total_results, 
												  $row_limit,
												  $row,
												  'ROW'
												);
					}
                }
            }

			if ( ! isset($new_sql))
			{
				if ( ! $query = $DB->query($sql))
				{
					return $DSP->set_return_data( $title, 
													 $DSP->heading($title).
													 $DSP->qdiv('highlight', $LANG->line('sql_no_result')),
													 $crumb
													);
				}
			}
                        
            $qtypes = array('INSERT', 'UPDATE', 'DELETE', 'ALTER', 'CREATE', 'DROP', 'TRUNCATE');

            $write = FALSE;
            
            if (preg_match("#^(".implode('|', $qtypes).")#i", $sql))
            {
            	$write = TRUE;
            }
                        
            if ($write == TRUE)
            {
                $affected = ($DB->affected_rows > 0) ? $DSP->qdiv('', $LANG->line('total_affected_rows').NBS.$DB->affected_rows) : $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('sql_good_query')));
     
           		$out = $DSP->div('box').
					   $DSP->qdiv('defaultBold', $LANG->line('query')).
					   $DSP->qdiv('bigPad', $REGX->xss_clean($sql)).
					   $DSP->qdiv('defaultBold bigPad', $affected).
					   $DSP->div_c();
					
				return $DSP->set_return_data( $title,                             
                                              $DSP->heading($title).
                                              $DSP->qdiv('success', $LANG->line('sql_good_result')).
											  $out,
                                              $crumb
                                             ); 
            }
        }
             
        // No result?  All that effort for nothing?
       
        if ($query->num_rows == 0)
        {
           return $DSP->set_return_data( $title, 
                                         $DSP->heading($title).
                                         $DSP->qdiv('highlight', $LANG->line('sql_no_result')),
                                         $crumb
                                        ); 
        }

        
        // Build the output
     
		$r  = $DSP->div('box').
			  $DSP->qdiv('defaultBold', $LANG->line('query')).
			  $DSP->qdiv('bigPad', $REGX->xss_clean($sql)).
			  $DSP->qdiv('defaultBold bigPad', str_replace('%x', (isset($total_results)) ? $total_results : $query->num_rows, $LANG->line('total_results'))).
			  $DSP->div_c();
			
        $r .= $DSP->qdiv('tableHeading', $title);     
        $r .= $DSP->table('tableBorder', '0', '10', '100%')
             .$DSP->tr();
        
        foreach ($query->fields as $f)
        {
            $r .= $DSP->td('tableHeadingAlt').$f.$DSP->td_c();
        }
        
        $r .= $DSP->tr_c();

        // Build our table rows

        $i = 0;
                        
        foreach ($query->result as $key => $val)
        {
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
        
            $r .= $DSP->tr();
            
            foreach ($val as $k => $v)
            {
                $r .= $DSP->td($style).htmlspecialchars($v).$DSP->td_c();
            }
            
            $r .= $DSP->tr_c();
        }
                
        $r.= $DSP->table_c();

        if ($paginate != '')
        {
            $r .= $DSP->qdiv('', $paginate);
        }


		if ($return == FALSE)
		{	
			$DSP->title = $title;
			$DSP->crumb = $crumb;
			$DSP->body  = $r;
		}
		else
		{
			return $r;
		}                                            
    }
    /* END */
    
    


    /** -------------------------------------------
    /**  Delete cache file form
    /** -------------------------------------------*/

    function clear_cache_form($message = FALSE)
    {  
        global $DSP, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
                
        $DSP->title = $LANG->line('clear_caching');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($LANG->line('clear_caching'));
        
        $DSP->body = $DSP->qdiv('tableHeading', $LANG->line('clear_caching'));        
        
        if ($message == TRUE)
        {
            $DSP->body  .= $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('cache_deleted')));                            
        }

		$DSP->body .= $DSP->div('box');
        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=clear_caching'));
        
        $DSP->body .= $DSP->div('itemWrapper');
        
        if ( ! isset($_POST['type']))
        {
            $_POST['type'] = 'all';         
        } 
                
        $selected = ($_POST['type'] == 'page') ? 1 : '';

        $DSP->body .= $DSP->input_radio('type', 'page', $selected).$LANG->line('page_caching').BR; 
        
        $selected = ($_POST['type'] == 'tag') ? 1 : '';
        
        $DSP->body .= $DSP->input_radio('type', 'tag', $selected).$LANG->line('tag_caching').BR;
    
        $selected = ($_POST['type'] == 'db') ? 1 : '';

        $DSP->body .= $DSP->input_radio('type', 'db', $selected).$LANG->line('db_caching').BR;
        
        $selected = ($_POST['type'] == 'relationships') ? 1 : '';

        $DSP->body .= $DSP->input_radio('type', 'relationships', $selected).$LANG->line('cached_relationships').BR;
        
        $selected = ($_POST['type'] == 'all') ? 1 : '';

        $DSP->body .= $DSP->input_radio('type', 'all', $selected).$LANG->line('all_caching');
        
        $DSP->body .= $DSP->div_c();
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('submit')));
        $DSP->body .= $DSP->form_close();
        $DSP->body .= $DSP->div_c();
    }
    /* END */
    
 
 
    /** -------------------------------------------
    /**  Delete cache files
    /** -------------------------------------------*/

    function clear_caching()
    {  
        global $FNS;
        
        if ( ! isset($_POST['type']))
        {
        	 return Utilities::clear_cache_form();
        }
 
        $FNS->clear_caching($_POST['type'], '', TRUE);
        
        return Utilities::clear_cache_form(TRUE);
    }
    /* END */
 
 
    /** -------------------------------------------
    /**  SQL backup form
    /** -------------------------------------------*/

    function sql_backup()
    {  
        global $DSP, $LANG;
        
        return;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        ob_start();
    
        ?>
        <script type="text/javascript"> 
        <!--

            function setType()
            {
                document.forms[0].type[0].checked = true;
            }
        
        //-->
        </script>
        <?php
    
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
        
        $DSP->title  = $LANG->line('sql_backup');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager')));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('sql_backup'));
        
        $DSP->body  = $buffer;                            

        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=do_sql_backup'));
        
		
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('sql_backup'));
                       
        $DSP->body .= $DSP->div('box');        
        $DSP->body .= $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('backup_info').'</b>');
       	$DSP->body .= '<fieldset class="box320">';
        $DSP->body .= '<legend>'.$DSP->qdiv('defaultBold', $LANG->line('archive_destination')).'</legend>';
        $DSP->body .= $DSP->input_radio('file', 'y', 1).$LANG->line('save_as_file').BR; 
        $DSP->body .= $DSP->input_radio('file', 'n', '', " onclick=\"setType();\"").$LANG->line('view_in_browser');  
        $DSP->body .= '</fieldset>';

        $DSP->body .= '<fieldset class="box320">';
        $DSP->body .= '<legend>'.$DSP->qdiv('defaultBold', $LANG->line('archive_type')).'</legend>';
        $DSP->body .= $DSP->input_radio('type', 'text').$LANG->line('plain_text').BR;
        $DSP->body .= $DSP->input_radio('type', 'zip', 1).$LANG->line('zip').BR;
        $DSP->body .= $DSP->input_radio('type', 'gzip').$LANG->line('gzip').NBS.$LANG->line('mac_no_zip');
        $DSP->body .= '</fieldset>';
        
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('ignore_noncritical', 'y', 1).$LANG->line('ignore_noncritical'));
        
        $DSP->body .= $DSP->div_c();
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        $DSP->body .= $DSP->form_close();
    }
    /* END */
    
    
    
    
    /** -------------------------------------------
    /**  Do SQL backup
    /** -------------------------------------------*/

    function do_sql_backup($type = '')
    {  
        global $IN, $DSP, $DB, $LANG, $LOC;
        
        return;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        

        // Names of tables we do not want the data backed up from
        
        if ($IN->GBL('ignore_noncritical', 'POST') == 'y') 
        {
			$ignore = array(
							'exp_security_hashes ', 
							'exp_sessions', 
							'exp_cp_log', 
							'exp_revision_tracker', 
							'exp_search', 
							'exp_email_console_cache'
							);
		}
		else
		{
			$ignore = array();
		}
        
        /** ---------------------------------------------------------
        /**  Are we backing up the full database or separate tables?
        /** ---------------------------------------------------------*/

        if ($type == '')
        {
            $type = $_POST['type'];
            
            $file = ($IN->GBL('file', 'POST') == 'y') ? TRUE : FALSE;
        }
        else
        {
            switch ($_POST['table_action'])
            {
                case 'BACKUP_F' : $type = 'text'; $file = TRUE;
                    break;
                case 'BACKUP_Z' : $type = 'zip';  $file = TRUE;
                    break;
                case 'BACKUP_G' : $type = 'gzip'; $file = TRUE;
                    break;
                default         : $type = 'text'; $file = FALSE;
                    break;
            }
        }

        
        /** ------------------------------------------------------------
        /**  Build the output headers only if we are downloading a file
        /** ------------------------------------------------------------*/
        
        ob_start();

        if ($file)
        {
            // Assign the name of the of the backup file
            
            $now = $LOC->set_localized_time();
            
            $filename = $DB->database.'_'.date('y', $now).date('m', $now).date('d', $now);
        
        
            switch ($type)
            {
                case 'zip' :
                
                            if ( ! @function_exists('gzcompress')) 
                            {
                                return $DSP->error_message($LANG->line('unsupported_compression'));
                            }
                
                            $ext  = 'zip';
                            $mime = 'application/x-zip';
                                    
                    break;
                case 'gzip' :
                
                            if ( ! @function_exists('gzencode')) 
                            {
                                return $DSP->error_message($LANG->line('unsupported_compression'));
                            }
                
                            $ext  = 'gz';
                            $mime = 'application/x-gzip';
                    break;
                default     :
                
                            $ext = 'sql';
                            
                            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE") || strstr($_SERVER['HTTP_USER_AGENT'], "OPERA")) 
                            {
                                $mime = 'application/octetstream';
                            }
                            else
                            {
                                $mime = 'application/octet-stream';
                            }
                
                    break;
            }
            
            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
            {
                header('Content-Type: '.$mime);
                header('Content-Disposition: inline; filename="'.$filename.'.'.$ext.'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
            } 
            else 
            {
                header('Content-Type: '.$mime);
                header('Content-Disposition: attachment; filename="'.$filename.'.'.$ext.'"');
                header('Expires: 0');
                header('Pragma: no-cache');
            }
        }
        else
        {
            echo $DSP->qdiv('tableHeading', $LANG->line('sql_backup'));
            
            echo '<pre>';
        }                 

        /** -------------------------------------------
        /**  Fetch the table names
        /** -------------------------------------------*/
        
        $DB->fetch_fields = TRUE;
        
        // Individual tables
        
        if (isset($_POST['table_action'])) 
        {
            foreach ($_POST['table'] as $key => $val)
            {
                $tables[] = $key;    
            }
        }
        
        // the full database
        
        else
        {
            $tables = $DB->fetch_tables();
        }
        
        $i = 0;
        
        foreach ($tables as $table)
        { 
            /** -------------------------------------------
            /**  Fetch the table structure
            /** -------------------------------------------*/

            echo NL.NL.'#'.NL.'# TABLE STRUCTURE FOR: '.$table.NL.'#'.NL.NL;
                
            echo 'DROP TABLE IF EXISTS '.$table.';'.NL.NL;
        
			$query = $DB->query("SHOW CREATE TABLE `".$DB->database.'`.'.$table);
			
			foreach ($query->result['0'] as $val)
			{
			    if ($i++ % 2)
			    {   
			    	//$val = str_replace('`', '', $val).NL.NL;
			    	//$val = preg_replace('/CREATE(.*\))/s', "CREATE\\1;", $val);
			    	//$val = str_replace('TYPE=MyISAM', '',	$val);
			    	
			    	echo $val.';'.NL.NL;
			    }
			}
			
			
			if ( ! in_array($table, $ignore))
			{
                /** -------------------------------------------
                /**  Fetch the data in the table
                /** -------------------------------------------*/
                
                $query = $DB->query("SELECT * FROM $table");
                
                if ($query->num_rows == 0)
                {
                    continue;
                }
                
                /** -------------------------------------------
                /**  Assign the field name
                /** -------------------------------------------*/
                
                $fields = '';
                
                foreach ($query->fields as $f)
                {
                    $fields .= $f . ', ';            
                }
            
                $fields = preg_replace( "/, $/" , "" , $fields);
                
                /** -------------------------------------------
                /**  Assign the value in each field
                /** -------------------------------------------*/
                                         
                foreach ($query->result as $val)
                {
                    $values = '';
                
                    foreach ($val as $v)
                    {
                        $v = str_replace(array("\x00", "\x0a", "\x0d", "\x1a"), array('\0', '\n', '\r', '\Z'), $v);   
                        $v = str_replace(array("\n", "\r", "\t"), array('\n', '\r', '\t'), $v);   
                        $v = str_replace('\\', '\\\\',	$v);
                        $v = str_replace('\'', '\\\'',	$v);
                        $v = str_replace('\\\n', '\n',	$v);
                        $v = str_replace('\\\r', '\r',	$v);
                        $v = str_replace('\\\t', '\t',	$v);

						$values .= "'".$v."'".', ';
                    }
                    
                    $values = preg_replace( "/, $/" , "" , $values);
                    
                    if ($file == FALSE)
                    {
                        $values = htmlspecialchars($values);
                    }
                    
                    // Build the INSERT string
        
                    echo 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');'.NL;
                }
            }
        }
        // END WHILE LOOP
        
        
        if ($file == FALSE)
        {
            echo '</pre>';
        }


        $buffer = ob_get_contents();
        
        ob_end_clean(); 
        
        
        /** -------------------------------------------
        /**  Create the selected output file
        /** -------------------------------------------*/
        
        if ($file)
        {
            switch ($type)
            {
                case 'zip' :  
                              $zip = new Zipper;
                                
                              $zip->add_file($buffer, $filename.'.sql');
                                
                              echo $zip->output_zipfile();                
                    break;
                case 'gzip' : echo gzencode($buffer);
                    break;
                 default    : echo $buffer;
                    break;
            }
            
            exit;
        }
        else
        {
			        
        
            $DSP->title = $LANG->line('utilities');
            $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  	  $DSP->crumb_item($LANG->line('utilities'));
            $DSP->body = $buffer;
        }        
    }
    /* END */
    

  
    /** -------------------------------------------
    /**  SQL tables
    /** -------------------------------------------*/

    function view_database()
    {  
        global $DSP, $DB, $PREFS, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }

		
        
        $DB->fetch_fields = TRUE;

        $query = $DB->query("SHOW TABLE STATUS FROM `".$PREFS->ini('db_name')."`");
        
        // Build the output
        
        $r = Utilities::toggle_code();
        $r .= $DSP->form_open(
        						array(
        								'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=table_action', 
        								'name' 	=> 'tables',
        								'id'	=> 'tables'
        							)
        						);

        $r .= $DSP->qdiv('tableHeading', $LANG->line('view_database'));
        $r .= $DSP->table('tableBorder', '0', '10', '100%')
             .$DSP->tr()                            
             .$DSP->td('tableHeadingAlt', '4%').$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$DSP->td_c()
             .$DSP->td('tableHeadingAlt', '36%').$LANG->line('table_name').$DSP->td_c()
             .$DSP->td('tableHeadingAlt', '15%').$LANG->line('browse').$DSP->td_c()
             .$DSP->td('tableHeadingAlt', '15%').$LANG->line('records').$DSP->td_c()
             .$DSP->td('tableHeadingAlt', '15%').$LANG->line('size').$DSP->td_c()
             .$DSP->tr_c();

        // Build our table rows

        $i = 0;
        $records = 0;
        $tables  = 0;
        $totsize = 0;

       $prelen = strlen($DB->prefix);

        foreach ($query->result as $val)
        {
            if (strncmp($val['Name'], $DB->prefix, $prelen) != 0)
            {
                continue;
            }
        
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
            
            $len  = $val['Data_length'] + $val['Index_length'];
            
            $size = Utilities::byte_format($len, 3);
                                
            $r .= $DSP->tr()            
                 .$DSP->td($style, '4%')."<input type='checkbox' name=\"table[".$val['Name']."]\" value='y' />".$DSP->td_c()
                 .$DSP->td($style, '36%').'<b>'.$val['Name'].'</b>'.$DSP->td_c()
                 .$DSP->td($style, '15%')                 
                 .$DSP->anchor(
                                BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=run_query'.AMP.'thequery='.base64_encode("SELECT * FROM ".$val['Name']),
                                $LANG->line('browse')
                            )           
                 .$DSP->td_c()
                 .$DSP->td($style, '15%').$val['Rows'].$DSP->td_c()
                 .$DSP->td($style, '15%').$size['0'].' '.$size['1'].$DSP->td_c()
                 .$DSP->tr_c();
                  
            $records += $val['Rows'];
            $totsize += $len;
            $tables++;
        }

        $size = Utilities::byte_format($totsize);
    
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

        $r .= $DSP->tr()
             .$DSP->td($style).NBS.$DSP->td_c()
             .$DSP->td($style).'<b>'.$tables.NBS.$LANG->line('tables').'</b>'.$DSP->td_c()
             .$DSP->td($style).NBS.$DSP->td_c()
             .$DSP->td($style).'<b>'.$records.'</b>'.$DSP->td_c()
             .$DSP->td($style).'<b>'.$size['0'].' '.$size['1'].'</b>'.$DSP->td_c()
             .$DSP->tr_c();
                
    
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

        $r .= $DSP->tr()
             .$DSP->td($style, '', '1')
             .$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")
             .$DSP->td_c()
             .$DSP->td($style, '', '1')
             .$LANG->line('select_all')
             .$DSP->td_c();
             
        $r .= $DSP->td($style, '', '4')
             .$DSP->input_select_header('table_action')
             .$DSP->input_select_option('OPTIMIZE', $LANG->line('optimize_table'))
             .$DSP->input_select_option('REPAIR',   $LANG->line('repair_table'))
             //.$DSP->input_select_option('BACKUP_V', $LANG->line('view_table_sql'))
             //.$DSP->input_select_option('BACKUP_F', $LANG->line('backup_tables_file'))
             //.$DSP->input_select_option('BACKUP_Z', $LANG->line('backup_tables_zip'))
             //.$DSP->input_select_option('BACKUP_G', $LANG->line('backup_tables_gzip'))
             .$DSP->input_select_footer()
             .$DSP->input_submit($LANG->line('submit'))
             .$DSP->td_c()
             .$DSP->tr_c()
             .$DSP->table_c();

        $r .= $DSP->form_close();
        
        $DSP->title = $LANG->line('view_database');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager'))).
					  $DSP->crumb_item($LANG->line('view_database'));
        $DSP->body  = $r;
    }
    /* END */
    
    

    /** -------------------------------------------
    /**  JavaScript toggle code
    /** -------------------------------------------*/

    function toggle_code()
    {
        ob_start();
    
        ?>
        <script type="text/javascript"> 
        <!--
    
        function toggle(thebutton)
        {
            if (thebutton.checked) 
            {
               val = true;
            }
            else
            {
               val = false;
            }
                        
            var len = document.tables.elements.length;
        
            for (var i = 0; i < len; i++) 
            {
                var button = document.tables.elements[i];
                
                var name_array = button.name.split("["); 
                
                if (name_array[0] == "table") 
                {
                    button.checked = val;
                }
            }
            
            document.tables.toggleflag.checked = val;
        }
        
        //-->
        </script>
        <?php
    
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
        
        return $buffer;
    } 
    /* END */
   


    /** ----------------------------------
    /**  Number format
    /** ----------------------------------*/
  
    function byte_format($num)
    {
        if ($num >= 1000000000) 
        {
            $num = round($num/107374182)/10;
            $unit  = 'GB';
        }
        elseif ($num >= 1000000) 
        {
            $num = round($num/104857)/10;
            $unit  = 'MB';
        }
        elseif ($num >= 1000) 
        {
            $num = round($num/102)/10;
            $unit  = 'KB';
        }
        else
        {
            $unit = 'Bytes';
        }

        return array(number_format($num, 1), $unit);
    }
    /* END */



    /** -------------------------------------------
    /**  Run table action (repair/optimize)
    /** -------------------------------------------*/

    function run_table_action()
    {
        global $DSP, $DB, $PREFS, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! isset($_POST['table']))
        {
            return $DSP->error_message($LANG->line('no_buttons_selected'));
        }
        
        $action = array('OPTIMIZE', 'REPAIR');

        if ( ! in_array($_POST['table_action'], $action))
        {
            return Utilities::do_sql_backup($_POST['table_action']);
        }
        
        $title = $LANG->line(strtolower($_POST['table_action']));
        
		
        $r  = $DSP->qdiv('tableHeading', $title);
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%');
        $r .= $DSP->tr();
        
        
        $DB->fetch_fields = TRUE;
                        
        $query = $DB->query("ANALYZE TABLE exp_members");

        foreach ($query->fields as $f)
        {
            $r .= $DSP->td('tableHeadingAlt').$f.$DSP->td_c();
        }
            
        $r .= $DSP->tr_c();
        
        $i = 0;
        
        foreach ($_POST['table'] as $key => $val)
        {                    
            $sql = $_POST['table_action']." TABLE ".$key;
            
            $query = $DB->query($sql);

            foreach ($query->result as $key => $val)
            {
                $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
            
                $r .= $DSP->tr();
                
                foreach ($val as $k => $v)
                {
                    $r .= $DSP->td($style).$v.$DSP->td_c();
                }
                
                $r .= $DSP->tr_c();
            }
                    
        }
        
        $r.= $DSP->table_c();

       // Set the return data

        $DSP->title = $LANG->line('utilities').$DSP->crumb_item($title);
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager'))).
					  $DSP->crumb_item($title);
        $DSP->body  = $r;                                                  
    }
    /* END */

    
    
    /** -------------------------------------------
    /**  SQL query form
    /** -------------------------------------------*/

    function sql_query_form()
    {  
        global $DSP, $LANG, $SESS;
        
        if ($SESS->userdata['group_id'] != '1')
        {
            return $DSP->no_access_message();
        }
        
        $DSP->title = $LANG->line('utilities');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=sql_manager', $LANG->line('sql_manager'))).
					  $DSP->crumb_item($LANG->line('sql_query'));                             
                                      
		                                      
        $DSP->body =  $DSP->qdiv('tableHeading', $LANG->line('sql_query'))   
        			 .$DSP->div('box')
                     .$DSP->qdiv('itemWrapper', $LANG->line('sql_query_instructions'))
                     .$DSP->qdiv('itemWrapper', '<b>'.$LANG->line('advanced_users_only').'</b>')
                     .$DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=run_query'))
                     .$DSP->input_textarea('thequery', '', '10', 'textarea', '100%')
                     .$DSP->qdiv('itemWrapper', $DSP->input_checkbox('debug', 'y', 0, "id='debug'").$LANG->line('sql_query_debug', 'debug'))
                     .$DSP->input_submit($LANG->line('submit'), 'submit')
                     .$DSP->div_c()
                     .$DSP->form_close();    
    }
    /* END */
    
    
   
    /** -------------------------------------------
    /**  Search and Replace form
    /** -------------------------------------------*/

    function search_and_replace_form()
    {  
        global $DSP, $DB, $LANG, $PREFS, $SESS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        // Select menu of available fields where a replacement can occur.
        
        $r  = $DSP->input_select_header('fieldname');
        $r .= $DSP->input_select_option('', '--');
        $r .= $DSP->input_select_option('preferences', $LANG->line('site_preferences'));
        
        foreach($SESS->userdata('assigned_sites') as $site_id => $site_label)
        {
        	$r .= $DSP->input_select_option('site_preferences_'.$site_id, NBS.NBS.NBS.NBS.NBS.NBS.$site_label);
        }
        
        $r .= $DSP->input_select_option('', '--');
        $r .= $DSP->input_select_option('title', $LANG->line('weblog_entry_title'));
        $r .= $DSP->input_select_option('', '--');
        $r .= $DSP->input_select_option('', $LANG->line('weblog_fields'));
       
        // Fetch the weblog fields
        
        $sql = "SELECT exp_field_groups.group_id, exp_field_groups.group_name, exp_field_groups.site_id
        		FROM exp_weblogs, exp_field_groups
        		WHERE exp_weblogs.field_group = exp_field_groups.group_id
        		AND exp_weblogs.is_user_blog = 'n'";
        
		$query = $DB->query($sql);
		
		$fg_array = array();
		$sql_b = '';

		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{
				$sql_b .= "'".$row['group_id']."',";
		
				$fg_array[$row['group_id']] = $row['group_name'];
			}
		
			$sql_b = substr($sql_b, 0, -1);
		
			$sql = "SELECT group_id, field_id, field_label, site_label FROM exp_weblog_fields, exp_sites 
				WHERE exp_sites.site_id = exp_weblog_fields.site_id 
				AND group_id IN (".$sql_b.")";
  		
			if ($PREFS->ini('multiple_sites_enabled') !== 'y')
			{
				$sql .= "AND exp_weblog_fields.site_id = '1' ";
			}
		
			$sql .= " ORDER BY site_label, group_id, field_label";
        
        	$query = $DB->query($sql);
        
        	$site = '';
        
        	foreach ($query->result as $row)
        	{
        		if (($PREFS->ini('multiple_sites_enabled') == 'y'))
        		{
        			if (($site == '' OR $site != $row['site_label']))
        			{
        				$r .= $DSP->input_select_option('', NBS.NBS.NBS.NBS.NBS.NBS.$row['site_label']);
        			}
        		
        			$site = $row['site_label'];
        		
        			$r .= $DSP->input_select_option('field_id_'.$row['field_id'], NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.$row['field_label'].' ('.$fg_array[$row['group_id']].')');
        		}
        		else
        		{
        			$r .= $DSP->input_select_option('field_id_'.$row['field_id'], NBS.NBS.NBS.NBS.NBS.NBS.$row['field_label'].' ('.$fg_array[$row['group_id']].')');
        		}
        	}
		}
        
        $r .= $DSP->input_select_option('', '--');
        
        $r .= $DSP->input_select_option('template_data', $LANG->line('templates'));
        
        $r .= $DSP->input_select_option('', '--');
        
        $LANG->fetch_language_file('templates');
        
        $r .= $DSP->input_select_option('template_data', $LANG->line('template_groups'));
        
        $sql = "SELECT group_id, group_name, site_label FROM exp_template_groups, exp_sites WHERE exp_sites.site_id = exp_template_groups.site_id ";
        
        if ($PREFS->ini('multiple_sites_enabled') !== 'y')
		{
			$sql .= "AND exp_template_groups.site_id = '1' ";
		}
        
        $sql .= "ORDER BY site_label, group_name";
        
        $query = $DB->query($sql);
        
        $site = '';
        
        foreach($query->result as $row)
        {
        	if (($PREFS->ini('multiple_sites_enabled') == 'y'))
        	{
        		if (($site == '' OR $site != $row['site_label']))
        		{
        			$r .= $DSP->input_select_option('', NBS.NBS.NBS.NBS.NBS.NBS.$row['site_label']);
        		}
        		
        		$site = $row['site_label'];
        		
        		$r .= $DSP->input_select_option('template_'.$row['group_id'], NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.NBS.$row['group_name']);
        	}
        	else
        	{
				$r .= $DSP->input_select_option('template_'.$row['group_id'], NBS.NBS.NBS.NBS.NBS.NBS.$row['group_name']);
			}
        }
                
        
        $r .= $DSP->input_select_footer();
        
        $DSP->title = $LANG->line('utilities');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
					  $DSP->crumb_item($LANG->line('search_and_replace'));                             
                                      
        $DSP->body = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=run_sandr')).
        			$DSP->qdiv('tableHeading', $LANG->line('search_and_replace')). 
        			$DSP->div('box').                   
					$DSP->qdiv('itemWrapper',$LANG->line('sandr_instructions')).
					$DSP->div('itemWrapper').
					$DSP->qspan('alert', $LANG->line('advanced_users_only')).
					$DSP->div_c().
										
					$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', BR.$LANG->line('search_term'))).
					$DSP->input_textarea('searchterm', '', '4').						
					$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', BR.$LANG->line('replace_term'))).
					$DSP->input_textarea('replaceterm', '', '4').
					$DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', BR.$LANG->line('replace_where'))).
					$r.
					$DSP->qdiv('alert', BR.$LANG->line('be_careful').NBS.NBS.$LANG->line('action_can_not_be_undone')).
					$DSP->qdiv('defaultBold', BR.$LANG->line('search_replace_disclaimer').BR).
					$DSP->div_c().
					$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit'), 'submit')).
					$DSP->form_close();
    }
    /* END */
   
  
  
    /** -------------------------------------------
    /**  Search and replace
    /** -------------------------------------------*/

    function search_and_replace()
    {  
        global $DSP, $IN, $DB, $LANG, $LOC, $REGX, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
  
		$search  	= $DB->escape_str($IN->GBL('searchterm'));
		$replace 	= $DB->escape_str($IN->GBL('replaceterm'));
		$field   	= $IN->GBL('fieldname');
		
        if ( ! $search  || ! $replace || ! $field)
        {
           return Utilities::search_and_replace_form();
        }
        
        if ($field == 'title')
        {
        	$sql = "UPDATE `exp_weblog_titles` SET `$field` = REPLACE(`$field`, '$search', '$replace')";
        }
        elseif ($field == 'preferences' OR substr($field, 0, strlen('site_preferences_')) == 'site_preferences_')
        {
        	$rows = 0;
        	
        	if ($field == 'preferences')
        	{
        		$site_id = $PREFS->ini('site_id');
        	}
        	else
        	{
        		$site_id = substr($field, strlen('site_preferences_'));
        	}
        	
			/** -------------------------------------------
			/**  Site Preferences in Certain Tables/Fields
			/** -------------------------------------------*/
			
        	$preferences = array('exp_weblogs'			=> array('blog_title', 
        														 'blog_url', 
        														 'comment_url',
        														 'blog_description', 
        														 'comment_notify_emails', 
        														 'weblog_notify_emails',
        														 'search_results_url',
        														 'tb_return_url',
        														 'ping_return_url',
        														 'rss_url'),
        						 'exp_upload_prefs'		=> array('server_path',
        						 								 'properties',
        						 								 'file_properties',
        						 								 'url'),
        						 'exp_member_groups'	=> array('group_title',
        						 								 'group_description',
        						 								 'mbr_delete_notify_emails'),
        						 'exp_global_variables'	=> array('variable_data'),
        						 'exp_categories'		=> array('cat_image'),
        						 'exp_galleries'		=> array('gallery_full_name',
        						 								 'gallery_url',
        						 								 'gallery_upload_path',
        						 								 'gallery_image_url',
        						 								 'gallery_batch_path',
        						 								 'gallery_batch_url',
        						 								 'gallery_wm_image_path',
        						 								 'gallery_wm_test_image_path',
        						 								 'gallery_comment_url',
        						 								 'gallery_comment_notify_emails'),
        						 'exp_forums'			=> array('forum_name',
        						 								 'forum_notify_emails',
        						 								 'forum_notify_emails_topics'),
        						 'exp_forum_boards'		=> array('board_label',
        						 								 'board_forum_url',
        						 								 'board_upload_path',
        						 								 'board_notify_emails',
        						 								 'board_notify_emails_topics')
        						 								 );
        						 								 
        	unset($preferences['exp_galleries']); // Not Site Specific?
        						 								 
        	foreach($preferences as $table => $fields)
        	{
        		if ( ! $DB->table_exists($table) OR $table == 'exp_forums')
        		{
        			continue;
        		}
        		
        		$site_field = ($table == 'exp_forum_boards') ? 'board_site_id' : 'site_id';
        	
        		foreach($fields as $field)
        		{
        			$DB->query("UPDATE `{$table}` 
        						SET `{$field}` = REPLACE(`{$field}`, '$search', '$replace') 
        						WHERE `$site_field` = '".$DB->escape_str($site_id)."'");
        						
        			$rows += $DB->affected_rows;
        		}
        	}
        	
        	if ($DB->table_exists('exp_forums'))
        	{
        		$query = $DB->query("SELECT board_id FROM exp_forum_boards WHERE board_site_id = '".$DB->escape_str($site_id)."'");
        		
        		if ($query->num_rows > 0)
        		{
        			foreach($query->result as $row)
        			{
        				foreach($preferences['exp_forums'] as $field)
						{
							$DB->query("UPDATE `exp_forums` 
										SET `{$field}` = REPLACE(`{$field}`, '$search', '$replace') 
										WHERE `board_id` = '".$DB->escape_str($row['board_id'])."'");
										
							$rows += $DB->affected_rows;
						}
        			}
        		}
        	}
        	
			/** -------------------------------------------
			/**  Site Preferences in Database
			/** -------------------------------------------*/
        	
        	$query = $DB->query("SELECT * FROM exp_sites WHERE site_id = '".$DB->escape_str($site_id)."'");
				
			foreach(array('system', 'weblog', 'template', 'mailinglist', 'member') as $type)
			{
				$prefs	 = $REGX->array_stripslashes(
													unserialize(
													$query->row['site_'.$type.'_preferences']));
				
				foreach($PREFS->divination($type) as $value)
				{
					$prefs[$value] = str_replace($search, $replace, $PREFS->ini($value));
				}
				
				$DB->query($DB->update_string('exp_sites', 
											  array('site_'.$type.'_preferences' => addslashes(serialize($prefs))),
											  "site_id = '".$DB->escape_str($site_id)."'"));
												  
			$rows += $DB->affected_rows;
			}
        }
        elseif ($field == 'template_data')
        {
        	$sql = "UPDATE `exp_templates` SET `$field` = REPLACE(`$field`, '$search', '$replace'), `edit_date` = '".$LOC->now."' WHERE group_id IN (";
        
			$query = $DB->query("SELECT group_id FROM exp_template_groups WHERE is_user_blog = 'n'");
			
			foreach ($query->result as $row)
			{
				$sql .= "'".$row['group_id']."',";
			}
			
			$sql = substr($sql, 0, -1).')';        
        }
        elseif(preg_match('#^template_#i', $field))
        {
        	$sql = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '$search', '$replace'), edit_date = '".$LOC->now."'
        			WHERE group_id = '".$DB->escape_str(substr($field,9))."'";
        }
        else
        {
        	$sql = "UPDATE `exp_weblog_data` SET `".$DB->escape_str($field)."` = REPLACE(`".$DB->escape_str($field)."`, '$search', '$replace') WHERE weblog_id IN (";
        
			$query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE is_user_blog = 'n'");
			
			foreach ($query->result as $row)
			{
				$sql .= "'".$row['weblog_id']."',";
			}
			
			$sql = substr($sql, 0, -1).')';
		}
        
        if ( isset($sql))
        {
        	$DB->query($sql);
        	$rows = $DB->affected_rows; 
        }
        
        $DSP->set_return_data(
                                $LANG->line('utilities'),
                                
                                $DSP->qdiv('tableHeading', $LANG->line('search_and_replace')).                    
                                $DSP->qdiv('box', $LANG->line('rows_replaced').NBS.NBS.$rows),

                                $LANG->line('search_and_replace')
                              ); 
    }
    /* END */
        
    
    /** -------------------------------------------
    /**  Data pruning
    /** -------------------------------------------*/
    
    function data_pruning()
    {  
        global $DSP, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
                
		$r  = $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('data_pruning'),
											'class'		=> 'tableHeading',
										)
									)
							);

		$r .= $DSP->table_row(array(
									array(
											'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=member_pruning', $LANG->line('member_pruning'))),
											'class'	=> 'tableCellTwo'
										)
									)
							);
			  
			  
		$r .= $DSP->table_row(array(
									array(
											'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=entry_pruning', $LANG->line('weblog_entry_pruning'))),
											'class'	=> 'tableCellTwo'
										)
									)
							);
			  
		$r .= $DSP->table_row(array(
									array(
											'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=comment_pruning', $LANG->line('comment_pruning'))),
											'class'	=> 'tableCellTwo'
										)
									)
							);
							
		$r .= $DSP->table_row(array(
									array(
											'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=trackback_pruning', $LANG->line('trackback_pruning'))),
											'class'	=> 'tableCellTwo'
										)
									)
							);
		
		/* Someday, oh someday...
		$r .= $DSP->table_row(array(
									array(
											'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=pm_pruning', $LANG->line('pm_pruning'))),
											'class'	=> 'tableCellTwo'
										)
									)
							);
		*/
								
		if ($PREFS->ini('forum_is_installed') == "y")						
        {
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=topic_pruning', $LANG->line('topic_pruning'))),
												'class'	=> 'tableCellTwo'
											)
										)
								);
        }
        
 		$r .= $DSP->table_close();

                
        $DSP->set_return_data($LANG->line('utilities'), $r, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
															$DSP->crumb_item($LANG->line('data_pruning')));     
    }
    /* END */



    /** -------------------------------------------
    /**  Membership pruning
    /** -------------------------------------------*/
    
    function member_pruning()
    {  
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
         
		$r = '';
         
        if ($IN->GBL('update') !== FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_member_pruning'))));
        }

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_member_conf'));
            
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('member_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);

			$data  = $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('mbr_prune_x_days')));
			$data .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('mbr_prune_zero_note')));

			
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $data,
												'class'	=> 'tableCellTwo',
												'width'	=> '65%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '35%'
											)
										)
								);
								
								
		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $DSP->qdiv('itemWrapper', $DSP->input_checkbox('post_filter', 'y', 1).NBS.$LANG->line('mbr_prune_never_posted')),
											'class'		=> 'tableCellTwo',
											'colspan'	=> '2'
										)
									)
							);
							
 		$r .= $DSP->table_close();
		
		$query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != 1 ORDER BY group_title");

		$r .= $DSP->qdiv('tableHeading', $LANG->line('mbr_prune_groups'));
							
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(
									array(
											'text' 	=> $LANG->line('must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);

		$i = 0;
		foreach ($query->result as $row)
		{
			// Translate groups if needed
			
            $group_name = $row['group_title'];
                                        
            if (in_array($group_name, array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins')))
            {
                $group_name = $LANG->line(strtolower(str_replace(" ", "_", $group_name)));
            }
            
            $group_name = str_replace(' ', NBS, $group_name);
            
			/** ----------------------------------------
			/**  Write group rows
			/** ----------------------------------------*/

			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->table_row(array(
										array(
												'text' 	=> $DSP->qdiv('defaultBold', $DSP->input_checkbox('group_'.$row['group_id'], 'y').NBS.$group_name),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}				
								
 		$r .= $DSP->table_close();
			
 		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();
 		
        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('member_pruning'));
 		
        $DSP->set_return_data($LANG->line('member_pruning'), $r, $c);     
    }
	/* END */



    /** -------------------------------------------
    /**  Prune Member Confirmation
    /** -------------------------------------------*/

    function prune_member_confirm()
    {  
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit member groups?
		/** ---------------------------------------*/
		
		$groups = FALSE;
			
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				$groups = TRUE;
				break;
			}
		}
		
		if ($groups == FALSE)
		{
			return $DSP->error_message($LANG->line('must_submit_group'));
		}

		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_members',
												'heading'		=> 'member_pruning',
												'message'		=> 'prune_member_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('member_pruning'));

        $DSP->set_return_data($LANG->line('member_pruning'), $r, $c);     
    }
	/* END */


    /** -------------------------------------------
    /**  Prune Member Data
    /** -------------------------------------------*/

    function prune_members()
    {  
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }

		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'), 2);
		}
		
		/** ---------------------------------------
		/**  Assign the member groups
		/** ---------------------------------------*/

		$group_filter = '';		
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				if (substr($key, 6) != 1)
				{
					$group_filter .= "'".substr($key, 6)."',";
				}
			}
		}
		
		/** ---------------------------------------
		/**  Did they submit member groups?
		/** ---------------------------------------*/
	
		if ($group_filter == '')
		{
			return $DSP->error_message($LANG->line('must_submit_group'), 2);
		}

		$group_filter = " group_id != '1' AND group_id IN (".substr($group_filter, 0, -1).')';

		$days_ago = ($_POST['days_ago'] > 0) ? ($LOC->now - (60*60*24*$_POST['days_ago'])) : '';

		/** ---------------------------------------
		/**  Fetch the member IDs
		/** ---------------------------------------*/
		
		if ( ! isset($_POST['post_filter']))
		{
			$sql = "SELECT member_id FROM exp_members WHERE ".$group_filter;

			if ($days_ago != '')
			{
				$sql .= " AND join_date < {$days_ago}";
			}
		}
		else
		{
			$sql = "SELECT m.member_id FROM exp_members m
					LEFT JOIN exp_weblog_titles ON (exp_weblog_titles.author_id = m.member_id)
					LEFT JOIN exp_comments ON (exp_comments.author_id = m.member_id) ";
			
			if ($PREFS->ini('forum_is_installed') == "y")
			{
				$sql .= "LEFT JOIN exp_forum_topics ON (exp_forum_topics.author_id = m.member_id)
						 LEFT JOIN exp_forum_posts ON (exp_forum_posts.author_id = m.member_id) ";
			}
	
			$sql .= "WHERE ".$group_filter."
					 AND exp_weblog_titles.author_id IS NULL
					 AND exp_comments.author_id IS NULL ";
					 
			if ($PREFS->ini('forum_is_installed') == "y")
			{		 
				$sql .= "AND exp_forum_topics.author_id IS NULL
						 AND exp_forum_posts.author_id IS NULL ";
			}
					 
			if ($days_ago != '')
			{
				$sql .= " AND m.join_date < {$days_ago}";
			}			
		}

		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->error_message($LANG->line('no_members_matched'), 2);
		}


		$total = 0;
		foreach ($query->result as $row)
		{
			$id = $row['member_id'];
			if ($PREFS->ini('forum_is_installed') == "y")
			{
				$DB->query("DELETE FROM exp_forum_administrators WHERE admin_member_id = '{$id}'");
				$DB->query("DELETE FROM exp_forum_moderators WHERE mod_member_id = '{$id}'");			
			}			
			
			$DB->query("DELETE FROM exp_members WHERE member_id = '{$id}'");
			$DB->query("DELETE FROM exp_member_data WHERE member_id = '{$id}'");
			$DB->query("DELETE FROM exp_member_homepage WHERE member_id = '{$id}'");
			
			$message_query = $DB->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '$id' AND message_read = 'n'");
			$DB->query("DELETE FROM exp_message_copies WHERE sender_id = '$id'");
			$DB->query("DELETE FROM exp_message_data WHERE sender_id = '$id'");
			$DB->query("DELETE FROM exp_message_folders WHERE member_id = '$id'");
			$DB->query("DELETE FROM exp_message_listed WHERE member_id = '$id'");
			
			if ($message_query->num_rows > 0)
			{
				foreach($message_query->result as $row)
				{
					$count_query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
					$DB->query($DB->update_string('exp_members', array('private_messages' => $count_query->row['count']), "member_id = '".$row['recipient_id']."'"));
				}
			}
			
			$total++;
        }
        
        // Update global stats
		$STAT->update_member_stats();
            
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=member_pruning'.AMP.'update='.$total);
		exit;
	}
	/* END */


    /** -------------------------------------------
    /**  Weblog Entry pruning
    /** -------------------------------------------*/
    
    function entry_pruning()
    {  
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
         
		$r = '';
         
        if ($IN->GBL('update') !== FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_entry_pruning'))));
        }

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_entry_conf'));

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('weblog_entry_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);

			$data  = $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('weblog_prune_x_days')));
			$data .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_checkbox('comment_filter', 'y', 1).NBS.$LANG->line('weblog_prune_never_posted'));

			$r .= $DSP->table_row(array(
										array(
												'text'	=> $data,
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											)
										)
								);
           								
 		$r .= $DSP->table_close();
		
		$query = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs ORDER BY blog_title");

		$r .= $DSP->qdiv('tableHeading', $LANG->line('select_prune_blogs'));
							
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(
									array(
											'text' 	=> $LANG->line('must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);


		$i = 0;
		foreach ($query->result as $row)
		{
			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->table_row(array(
										array(
												'text' 	=> $DSP->qdiv('defaultBold', $DSP->input_checkbox('blog_'.$row['weblog_id'], 'y').NBS.$row['blog_title']),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}				
								
 		$r .= $DSP->table_close();
			                  
 		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();
 		
        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('weblog_entry_pruning'));
 		
        $DSP->set_return_data($LANG->line('weblog_entry_pruning'), $r, $c);     
    }
	/* END */



	/** ---------------------------------------
	/**  Weblog Entry Pruning Confirmation
	/** ---------------------------------------*/

	function prune_entry_confirm()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
			
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs = TRUE;
				break;
			}
		}		
		
		
		if ($blogs == FALSE)
		{
			return $DSP->error_message($LANG->line('must_submit_blog'));
		}

		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_entries',
												'heading'		=> 'weblog_entry_pruning',
												'message'		=> 'prune_entry_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('weblog_entry_pruning'));

        $DSP->set_return_data($LANG->line('weblog_entry_pruning'), $r, $c);     
	}
	/* END */



	/** ---------------------------------------
	/**  Prune Entries
	/** ---------------------------------------*/

	function prune_entries()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
		$blog_ids = array();
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs .= "'".substr($key, 5)."',";
				$blog_ids[] = substr($key, 5);
			}
		}
	
		if ($blogs == '')
		{
			return $DSP->error_message($LANG->line('must_submit_blog'), 2);
		}

		$blogs = " w.weblog_id IN (".substr($blogs, 0, -1).')';

		$days_ago = ($_POST['days_ago'] > 0) ? ($LOC->now - (60*60*24*$_POST['days_ago'])) : '';

		/** ---------------------------------------
		/**  Fetch the entry IDs
		/** ---------------------------------------*/
		
		if ( ! isset($_POST['comment_filter']))
		{
			$sql = "SELECT w.entry_id FROM exp_weblog_titles w WHERE ".$blogs;

			if ($days_ago != '')
			{
				$sql .= " AND w.entry_date < {$days_ago}";
			}
		}
		else
		{
			$sql = "SELECT w.entry_id FROM exp_weblog_titles w
					LEFT JOIN exp_comments ON (exp_comments.entry_id = w.entry_id) 
					WHERE ".$blogs."
					AND exp_comments.entry_id IS NULL ";
					 
			if ($days_ago != '')
			{
				$sql .= " AND w.entry_date < {$days_ago}";
			}			
		}

		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->error_message($LANG->line('no_entries_matched'), 2);
		}
		
		$total = 0;
		foreach ($query->result as $row)
		{
			$id = $row['entry_id'];
			$DB->query("DELETE FROM exp_weblog_titles WHERE entry_id = '$id'");
			$DB->query("DELETE FROM exp_weblog_data WHERE entry_id = '$id'");
			$DB->query("DELETE FROM exp_category_posts WHERE entry_id = '$id'");
			$DB->query("DELETE FROM exp_trackbacks WHERE entry_id = '$id'");
			$DB->query("DELETE FROM exp_comments WHERE entry_id = '$id'");
			
			$total++;
        }
        
        // Update global stats
		$STAT->update_member_stats();
		
		foreach ($blog_ids as $id)
		{
			$STAT->update_weblog_stats($id);
			$STAT->update_comment_stats($id, '' , FALSE); // Weblog, Not Global
			$STAT->update_trackback_stats($id);
		}
		
		$STAT->update_comment_stats();  // Global Comment Stats
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=entry_pruning'.AMP.'update='.$total);
		exit;
	}
	/* END */



    /** -------------------------------------------
    /**  Comment pruning
    /** -------------------------------------------*/
    
    function comment_pruning()
    {  
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
         
		$r = '';
         
        if ($IN->GBL('update') !== FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_commennt_pruning'))));
        }

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_comment_conf'));

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('comment_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);
							
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('comment_prune_x_days'))),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											)
										)
								);
           								
		
 		$r .= $DSP->table_close();
		
		$query = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs ORDER BY blog_title");

		$r .= $DSP->qdiv('tableHeading', $LANG->line('select_prune_blogs'));
							
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(
									array(
											'text' 	=> $LANG->line('must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);

		$i = 0;
		foreach ($query->result as $row)
		{
			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->table_row(array(
										array(
												'text' 	=> $DSP->qdiv('defaultBold', $DSP->input_checkbox('blog_'.$row['weblog_id'], 'y').NBS.$row['blog_title']),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}				
								
 		$r .= $DSP->table_close();
							
                  
 		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();
 		
        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('comment_pruning'));
 		
        $DSP->set_return_data($LANG->line('comment_pruning'), $r, $c);     
    }
	/* END */



	/** ---------------------------------------
	/**  Comment Pruning Confirmation
	/** ---------------------------------------*/

	function prune_comment_confirmation()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
			
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs = TRUE;
				break;
			}
		}		
		
		
		if ($blogs == FALSE)
		{
			return $DSP->error_message($LANG->line('must_submit_blog'));
		}

		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_comments',
												'heading'		=> 'comment_pruning',
												'message'		=> 'prune_comment_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('weblog_entry_pruning'));

        $DSP->set_return_data($LANG->line('weblog_entry_pruning'), $r, $c);     
	}
	/* END */



	/** ---------------------------------------
	/**  Prune Comments
	/** ---------------------------------------*/

	function prune_comments()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
		$blog_ids = array();
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs .= "'".substr($key, 5)."',";
				$blog_ids[] = substr($key, 5);
			}
		}
	
		if ($blogs == '')
		{
			return $DSP->error_message($LANG->line('must_submit_blog'), 2);
		}

		$blogs = " weblog_id IN (".substr($blogs, 0, -1).')';

		$days_ago = ($_POST['days_ago'] > 0) ? ($LOC->now - (60*60*24*$_POST['days_ago'])) : '';

		/** ---------------------------------------
		/**  Fetch the comment IDs
		/** ---------------------------------------*/
		
		$sql = "SELECT comment_id, entry_id FROM exp_comments WHERE ".$blogs;

		if ($days_ago != '')
		{
			$sql .= " AND comment_date < {$days_ago}";
		}

		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->error_message($LANG->line('no_comments_matched'), 2);
		}
		
		$total = 0;
		foreach ($query->result as $row)
		{
			$id = $row['comment_id'];
			$entry_id = $row['entry_id'];
			
			$DB->query("DELETE FROM exp_comments WHERE comment_id = '$id'");
			$total++;
			
            $res = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '{$entry_id}'");
            $comment_date  = ($res->num_rows == 0 OR ! is_numeric($res->row['max_date'])) ? 0 : $res->row['max_date'];
			$res = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '{$entry_id}' AND status = 'o'");
            $DB->query("UPDATE exp_weblog_titles SET comment_total = '".($res->row['count'])."', recent_comment_date = '$comment_date' WHERE entry_id = '{$entry_id}'");			
        }
        
        // Update global stats
		foreach ($blog_ids as $id)
		{  
			$STAT->update_comment_stats($id, '' , FALSE);
		}
		
		$STAT->update_comment_stats(); // Global comment stats only
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=comment_pruning'.AMP.'update='.$total);
		exit;
	}
	/* END */


    /** -------------------------------------------
    /**  Trackback pruning
    /** -------------------------------------------*/
    
    function trackback_pruning()
    {  
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
         
		$r = '';
         
        if ($IN->GBL('update') !== FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_trackback_pruning'))));
        }

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_trackback_conf'));

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('trackback_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);
							
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('trackback_prune_x_days'))),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											)
										)
								);
           								
		
 		$r .= $DSP->table_close();
		
		$query = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs ORDER BY blog_title");

		$r .= $DSP->qdiv('tableHeading', $LANG->line('select_prune_blogs'));
							
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(
									array(
											'text' 	=> $LANG->line('must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);
		$i = 0;
		foreach ($query->result as $row)
		{
			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->table_row(array(
										array(
												'text' 	=> $DSP->qdiv('defaultBold', $DSP->input_checkbox('blog_'.$row['weblog_id'], 'y').NBS.$row['blog_title']),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}				
								
 		$r .= $DSP->table_close();
			
 		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();
 		
        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('trackback_pruning'));
 		
        $DSP->set_return_data($LANG->line('trackback_pruning'), $r, $c);     
    }
	/* END */



	/** ---------------------------------------
	/**  trackback Pruning Confirmation
	/** ---------------------------------------*/

	function prune_trackback_confirmation()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
			
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs = TRUE;
				break;
			}
		}		
		
		
		if ($blogs == FALSE)
		{
			return $DSP->error_message($LANG->line('must_submit_blog'));
		}

		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_trackbacks',
												'heading'		=> 'trackback_pruning',
												'message'		=> 'prune_trackback_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('weblog_entry_pruning'));

        $DSP->set_return_data($LANG->line('weblog_entry_pruning'), $r, $c);     
	}
	/* END */



	/** ---------------------------------------
	/**  Prune trackbacks
	/** ---------------------------------------*/

	function prune_trackbacks()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit blog IDs?
		/** ---------------------------------------*/
		
		$blogs = FALSE;
		$blog_ids = array();
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs .= "'".substr($key, 5)."',";
				$blog_ids[] = substr($key, 5);
			}
		}
	
		if ($blogs == '')
		{
			return $DSP->error_message($LANG->line('must_submit_blog'), 2);
		}

		$blogs = " weblog_id IN (".substr($blogs, 0, -1).')';

		$days_ago = ($_POST['days_ago'] > 0) ? ($LOC->now - (60*60*24*$_POST['days_ago'])) : '';

		/** ---------------------------------------
		/**  Fetch the trackback IDs
		/** ---------------------------------------*/
		
		$sql = "SELECT trackback_id FROM exp_trackbacks WHERE ".$blogs;

		if ($days_ago != '')
		{
			$sql .= " AND trackback_date < {$days_ago}";
		}

		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->error_message($LANG->line('no_trackbacks_matched'), 2);
		}
		

		$total = 0;
		foreach ($query->result as $row)
		{
			$id = $row['trackback_id'];
			$DB->query("DELETE FROM exp_trackbacks WHERE trackback_id = '$id'");
			$total++;
			
            $res = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks WHERE entry_id = '{$id}'");
            $trackback_date = ($res->num_rows == 0 OR ! is_numeric($res->row['max_date'])) ? 0 : $res->row['max_date'];
			$res = $DB->query("SELECT COUNT(*) AS count FROM exp_trackbacks WHERE entry_id = '{$id}'");
            $DB->query("UPDATE exp_weblog_titles SET trackback_total = '".($res->row['count'])."', recent_trackback_date = '$trackback_date' WHERE entry_id = '{$id}'");      
        }
        
        // Update global stats
		foreach ($blog_ids as $id)
		{  
			$STAT->update_trackback_stats($id);
		}
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=trackback_pruning'.AMP.'update='.$total);
		exit;
	}
	/* END */



	/** -------------------------------------
	/**  Private Message Pruning
	/** -------------------------------------*/
	
	function pm_pruning()
	{
		global $IN, $DSP, $LANG;
		
		if ( ! $DSP->allowed_group('can_admin_utilities'))
		{
			return $DSP->no_access_message();
		}
		
		$r = '';
		
		if ($IN->GBL('update') !== FALSE)
		{
			$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_pm_pruning'))));
		}
		
		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_pm_conf'));
		
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('pm_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);
			
			$data  = $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('pm_prune_x_days')));
			
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $data,
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '30', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											)
										)
								);
			
		$r .= $DSP->table_close();
		
		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();

		$c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('pm_pruning'));
 		
        $DSP->set_return_data($LANG->line('pm_pruning'), $r, $c);
	}
	/* END */
	
	
	
	/** -------------------------------------
	/**  Private Message Pruning Confirmation
	/** -------------------------------------*/
	
	function prune_pm_confirmation()
	{
		global $DSP, $LANG;
		
		if ( ! $DSP->allowed_group('can_admin_utilities'))
		{
			return $DSP->no_access_message();
		}
		
		/** -------------------------------------
		/**  Did they submit the number of days?
		/** -------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_pms',
												'heading'		=> 'pm_pruning',
												'message'		=> 'prune_pm_confirm_msg',
												'hidden'		=> $_POST
											)
										);
										
		$c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('pm_pruning'));
 		
        $DSP->set_return_data($LANG->line('pm_pruning'), $r, $c);
	}
	/* END */
	
	
	
	/** -------------------------------------
	/**  Prune Private Messages
	/** -------------------------------------*/
	
	function prune_pms()
	{
		global $DB, $DSP, $FNS, $LANG, $LOC;
		
		if ( ! $DSP->allowed_group('can_admin_utilities'))
		{
			return $DSP->no_access_message();
		}
		
		/** -------------------------------------
		/**  Did they submit the number of days?
		/** -------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		if ( ! class_exists('Messages'))
		{
			require PATH_CORE.'core.messages'.EXT;
		}
		
		$MESS = new Messages;
		$MESS->delete_expiration = $_POST['days_ago'];
		
		$deletion_time = $LOC->now - ($MESS->delete_expiration*24*60*60);
		$member_ids = array();
		
		/** -------------------------------------
		/**  Members who have old deleted messages
		/** -------------------------------------*/
		
		$query = $DB->query("SELECT DISTINCT recipient_id FROM exp_message_copies
							 WHERE message_deleted = 'y'
    						 AND message_time_read < $deletion_time");
					
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=pm_pruning'.AMP.'update='.$total);
		exit;		
	}
	/* END */
	
	
		
	/** -------------------------------------------
    /**  Forum Topic pruning
    /** -------------------------------------------*/
    
   	function topic_pruning()
    {  
        global $IN, $DSP, $LANG, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
         
		$r = '';
         
        if ($IN->GBL('update') !== FALSE)
        {
        	$r .= $DSP->qdiv('box', $DSP->qdiv('success', str_replace('%x', $IN->GBL('update'), $LANG->line('good_topic_pruning'))));
       	}

		$r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_topic_conf'));

		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('topic_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);

			$data  = $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('topic_prune_x_days')));
			$data .= $DSP->qdiv('itemWrapper', NBS.NBS.$DSP->input_checkbox('post_filter', 'y', 1).NBS.$LANG->line('prune_if_no_posts'));

			$r .= $DSP->table_row(array(
										array(
												'text'	=> $data,
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											),
										array(
												'text'	=> $DSP->input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'class'	=> 'tableCellTwo',
												'width'	=> '50%'
											)
										)
								);
           								
 		$r .= $DSP->table_close();
		
		$query = $DB->query("SELECT forum_id, forum_name FROM exp_forums WHERE forum_is_cat = 'n' ORDER BY forum_order");

		$r .= $DSP->qdiv('tableHeading', $LANG->line('select_prune_topics'));
							
		$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $DSP->table_row(array(
									array(
											'text' 	=> $LANG->line('must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);		
		$i = 0;
		foreach ($query->result as $row)
		{
			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $DSP->table_row(array(
										array(
												'text' 	=> $DSP->qdiv('defaultBold', $DSP->input_checkbox('forum_id_'.$row['forum_id'], 'y').NBS.$row['forum_name']),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}				
								
 		$r .= $DSP->table_close();
                  
 		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
 		$r .= $DSP->form_close();
 		
        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('topic_pruning'));
 		
        $DSP->set_return_data($LANG->line('topic_pruning'), $r, $c);     
    }
	/* END */



	/** ---------------------------------------
	/**  Forum Topic Pruning Confirmation
	/** ---------------------------------------*/

	function prune_topic_confirmation()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit forum IDs?
		/** ---------------------------------------*/
		
		$forums = FALSE;
			
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 9) == 'forum_id_')
			{
				$forums = TRUE;
				break;
			}
		}		
		
		
		if ($forums == FALSE)
		{
			return $DSP->error_message($LANG->line('must_submit_forums'));
		}

		$r = $DSP->delete_confirmation(
										array(
												'url'			=> 'C=admin'.AMP.'M=utilities'.AMP.'P=prune_topics',
												'heading'		=> 'topic_pruning',
												'message'		=> 'prune_topic_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=prune', $LANG->line('data_pruning'))).
			 $DSP->crumb_item($LANG->line('topic_pruning'));

        $DSP->set_return_data($LANG->line('topic_pruning'), $r, $c);     
	}
	/* END */



	/** ---------------------------------------
	/**  Prune Forum Topics
	/** ---------------------------------------*/

	function prune_topics()
	{
        global $DSP, $FNS, $LANG, $DB, $PREFS, $LOC, $STAT;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
		/** ---------------------------------------
		/**  Did they submit the number of day?
		/** ---------------------------------------*/
		
		if ( ! is_numeric($_POST['days_ago']))
		{
			return $DSP->error_message($LANG->line('must_submit_number'));
		}
		
		/** ---------------------------------------
		/**  Did they submit topic IDs?
		/** ---------------------------------------*/
		
		$forums = FALSE;
		$topic_ids = array();
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 9) == 'forum_id_')
			{
				$forums .= "'".substr($key, 9)."',";
				$topic_ids[] = substr($key, 9);
			}
		}
	
		if ($forums == '')
		{
			return $DSP->error_message($LANG->line('must_submit_forums'), 2);
		}

		$forums = " t.forum_id IN (".substr($forums, 0, -1).')';

		$days_ago = (is_numeric($_POST['days_ago']) AND $_POST['days_ago'] > 0) ? ($LOC->now - (60*60*24*$_POST['days_ago'])) : '';

		/** ---------------------------------------
		/**  Fetch the topic IDs
		/** ---------------------------------------*/
		
		if ( ! isset($_POST['post_filter']))
		{
			$sql = "SELECT t.topic_id FROM exp_forum_topics t WHERE ".$forums;
	
			if ($days_ago != '')
			{
				$sql .= " AND t.topic_date < {$days_ago}";
			}
		}
		else
		{
			$sql = "SELECT t.topic_id FROM exp_forum_topics t 
					LEFT JOIN exp_forum_posts p ON (p.topic_id = t.topic_id)
					WHERE p.topic_id IS NULL
					AND ".$forums;
	
			if ($days_ago != '')
			{
				$sql .= " AND t.topic_date < {$days_ago}";
			}
		}
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->error_message($LANG->line('no_topics_matched'), 2);
		}
		
		$total = 0;
		foreach ($query->result as $row)
		{
			$id = $row['topic_id'];
			$DB->query("DELETE FROM exp_forum_topics WHERE topic_id = '{$id}'");
			$DB->query("DELETE FROM exp_forum_posts  WHERE topic_id = '{$id}'");
			$DB->query("DELETE FROM exp_forum_subscriptions  WHERE topic_id = '{$id}'");
			$total++;
        }
        
	   
		/** -------------------------------------
		/**  Update stats
		/** -------------------------------------*/

		include_once PATH_MOD.'forum/mod.forum'.EXT;
		include_once PATH_MOD.'forum/mod.forum_core'.EXT;

        foreach ($topic_ids as $id)
        {
        	Forum_Core::_update_post_stats($id);
        }
                
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=topic_pruning'.AMP.'update='.$total);
		exit;
	}
	/* END */


   
    /** -------------------------------------------
    /**  Recalculate Statistics - Main Page
    /** -------------------------------------------*/

    function recount_statistics()
    {  
        global $DSP, $LANG, $DB, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        // Does the forum exist?
		$forum_exists = FALSE;
		if ($PREFS->ini('forum_is_installed') == "y")
		{
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");
			
			if ($query->row['count'] > 0)
			{
				$forum_exists = TRUE;
			}
		}
	
        if ($forum_exists == FALSE)
        {
        	$sources = array('exp_members', 'exp_weblog_titles');
        }
        else
        {
        	$sources = array('exp_members', 'exp_weblog_titles', 'exp_forums', 'exp_forum_topics');
        }
        
        $DSP->title = $LANG->line('utilities');        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  $DSP->crumb_item($LANG->line('recount_stats')); 
		$DSP->right_crumb($LANG->line('set_recount_prefs'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_prefs');
     
        $r = $DSP->qdiv('tableHeading', $LANG->line('recalculate'));
        
        $r .= $DSP->qdiv('box', $LANG->line('recount_info'));   
        
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', 
                                array(
                                        $LANG->line('source'),
                                        $LANG->line('records'),
                                        $LANG->line('action')
                                     )
                                ).
                $DSP->tr_c();
        
        $i = 0;

        foreach ($sources as $val)
        {
			$query = $DB->query("SELECT COUNT(*) AS count FROM $val");
		  
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$r .= $DSP->tr();
			
			// Table name
			$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line($val)), '20%');
	
			// Table rows
			$r .= $DSP->table_qcell($style, $query->row['count'], '20%');
					
			// Action
			$r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=do_recount'.AMP.'TBL='.$val, $LANG->line('do_recount')), '20%');  
        }          

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $DSP->tr();
		
		// Table name
		$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('site_statistics')), '20%');

		// Table rows
		$r .= $DSP->table_qcell($style, '4', '20%');
				
		// Action
		$r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=do_stats_recount', $LANG->line('do_recount')), '20%');  

        $r .= $DSP->table_c();
        
        $DSP->body = $r;
    }
    /* END */
    

    /** -------------------------------------------
    /**  Recount preferences form
    /** -------------------------------------------*/

    function recount_preferences_form()
    {  
        global $IN, $DSP, $LANG, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        $recount_batch_total = $PREFS->ini('recount_batch_total');  
        
        $DSP->title = $LANG->line('utilities');        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('recount_stats'))).
			 		  $DSP->crumb_item($LANG->line('set_recount_prefs'));
					  
        $r = $DSP->qdiv('tableHeading', $LANG->line('set_recount_prefs')); 
        
        if ($IN->GBL('U'))
        {
            $r .= $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('preference_updated')));
        }
        
        $r .= $DSP->form_open(
								array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=set_recount_prefs'),
								array('return_location' => BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_prefs'.AMP.'U=1')
							);
        
        $r .= $DSP->div('box');
        
        $r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('recount_instructions')));
        
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('recount_instructions_cont'));
                
        $r .= $DSP->input_text('recount_batch_total', $recount_batch_total, '7', '5', 'input', '60px');

        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
                
        $r .= $DSP->div_c();
        $r .= $DSP->form_close();
        
        $DSP->body = $r;      
    }
    /* END */

    
    
    /** -------------------------------------------
    /**  Update recount preferences
    /** -------------------------------------------*/

    function set_recount_prefs()
    {  
        global $IN, $LANG, $DSP, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        $total = $IN->GBL('recount_batch_total');
        
        if ($total == '' || ! is_numeric($total))
        {
            return Utilities::recount_preferences_form();
        }
        
        $this->update_config_prefs(array('recount_batch_total' => $total));
    }
    /* END */


    /** -------------------------------------------
    /**  Do General Statistics Recount
    /** -------------------------------------------*/

    function do_stats_recount()
    {  
        global $DSP, $LANG, $STAT, $DB, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        $original_site_id = $PREFS->ini('site_id');
        
        $query = $DB->query("SELECT site_id FROM exp_sites");
        
        foreach($query->result as $row)
		{
			$PREFS->core_ini['site_id'] = $row['site_id'];
		
			$STAT->update_comment_stats();
			$STAT->update_member_stats();
			$STAT->update_weblog_stats();
			$STAT->update_trackback_stats();
		}
        
        $PREFS->core_ini['site_id'] = $original_site_id;
        
        $DSP->title = $LANG->line('utilities');        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('recalculate'))).
			 		  $DSP->crumb_item($LANG->line('recounting'));

		$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('site_statistics'));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('success', $LANG->line('recount_completed'));
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('return_to_recount_overview')));         
		$DSP->body .= $DSP->div_c();	
	}
	/* END */


    /** -------------------------------------------
    /**  Do member/weblog recount
    /** -------------------------------------------*/

    function do_recount()
    {  
        global $IN, $DSP, $LANG, $DB, $PREFS, $LOC;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
                
        if ( ! $table = $IN->GBL('TBL', 'GET'))
        {
            return false;
        }
        
        $sources = array('exp_members', 'exp_weblog_titles', 'exp_forums', 'exp_forum_topics');
        
        if ( ! in_array($table, $sources))
        {
            return false;
        }
   
   		if ( ! isset($_GET['T']))
   		{
        	$num_rows = FALSE;
        }
        else
        {
        	$num_rows = $_GET['T'];
			settype($num_rows, 'integer');
        }
        
        $batch = $PREFS->ini('recount_batch_total');
       	
		if ($table == 'exp_members')
		{
			// Check to see if the forum module is installed
			
			$forum_exists = FALSE;
			if ($PREFS->ini('forum_is_installed') == "y")
			{
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");
				
				if ($query->row['count'] > 0)
				{
					$forum_exists = TRUE;
				}
			}
		
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_members");
			
			$total_rows = $query->row['count'];
		
			if ($num_rows !== FALSE)
			{			
				$query = $DB->query("SELECT member_id FROM exp_members ORDER BY member_id LIMIT $num_rows, $batch");
				
				foreach ($query->result as $row)
				{
					$res = $DB->query("SELECT count(entry_id) AS count FROM exp_weblog_titles WHERE author_id = '".$row['member_id']."'");
					$total_entries = $res->row['count'];
				
					$res = $DB->query("SELECT count(comment_id) AS count FROM exp_comments WHERE author_id = '".$row['member_id']."'");
					$total_comments = $res->row['count'];
					
					$res = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['member_id']."' AND message_read = 'n'");
					$total_pms = $res->row['count'];
										
					if ($forum_exists == FALSE)
					{
						$DB->query($DB->update_string('exp_members', array( 'total_entries' => $total_entries,'total_comments' => $total_comments, 'private_messages' => $total_pms), "member_id = '".$row['member_id']."'"));   
					}
					else
					{
						$res = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE author_id = '".$row['member_id']."'");
						$total_forum_topics = $res->row['count'];
						
						$res = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE author_id = '".$row['member_id']."'");
						$total_forum_posts = $res->row['count'];

						$DB->query($DB->update_string('exp_members', array( 'total_entries' => $total_entries,'total_comments' => $total_comments, 'private_messages' => $total_pms, 'total_forum_topics' => $total_forum_topics, 'total_forum_posts' => $total_forum_posts), "member_id = '".$row['member_id']."'"));   
					}
				}
			}
		}
		elseif ($table == 'exp_weblog_titles')
		{
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_weblog_titles");
			
			$total_rows = $query->row['count'];
		
			if ($num_rows !== FALSE)
			{			
				$query = $DB->query("SELECT entry_id FROM exp_weblog_titles ORDER BY entry_id LIMIT $num_rows, $batch");
				
				foreach ($query->result as $row)
				{
					$res = $DB->query("SELECT count(comment_id) AS count FROM exp_comments WHERE entry_id = '".$row['entry_id']."' AND status = 'o'");
					$comment_total = $res->row['count'];
					
					$res = $DB->query("SELECT MAX(comment_date) as recent FROM exp_comments WHERE entry_id = '".$row['entry_id']."' AND status = 'o'");
					$comment_date = $res->row['recent'];
					
					$res = $DB->query("SELECT count(trackback_id) AS count FROM exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");
					$trackback_total = $res->row['count'];
					
					$res = $DB->query("SELECT MAX(trackback_date) as recent FROM exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");
					$trackback_date = $res->row['recent'];
				   
					$DB->query($DB->update_string('exp_weblog_titles', array( 'comment_total' => $comment_total, 'recent_comment_date' => $comment_date, 'trackback_total' => $trackback_total, 'recent_trackback_date' => $trackback_date), "entry_id = '".$row['entry_id']."'"));   
				}
			}
		}
		elseif ($table == 'exp_forums')
		{
			$query = $DB->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{					
					$forum_id = $row['forum_id'];
					
					$res1 = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
					$total1 = $res1->row['count'];

					$res2 = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
					$total2 = $res2->row['count'];
					
					$DB->query("UPDATE exp_forums SET forum_total_topics = '{$total1}', forum_total_posts = '{$total2}' WHERE forum_id = '{$forum_id}'");
							
				}
				
				$total_done = 1;
				$total_rows = 0;
			}	
		}
		elseif ($table == 'exp_forum_topics')
		{
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_topics");
			$total_rows = $query->row['count'];
	
			$query = $DB->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n' ORDER BY forum_id");
			
			foreach ($query->result as $row)
			{					
				$forum_id = $row['forum_id'];
			
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
				$data['forum_total_topics'] = $query->row['count'];
				
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
				$data['forum_total_posts'] = $query->row['count'];
				
				$query = $DB->query("SELECT topic_id, title, topic_date, last_post_date, last_post_author_id, screen_name
									FROM exp_forum_topics, exp_members
									WHERE member_id = last_post_author_id
									AND forum_id = '{$forum_id}' 
									ORDER BY last_post_date DESC LIMIT 1");
		
				$data['forum_last_post_id'] 		= ($query->num_rows == 0) ? 0 : $query->row['topic_id'];
				$data['forum_last_post_title'] 		= ($query->num_rows == 0) ? '' : $query->row['title'];
				$data['forum_last_post_date'] 		= ($query->num_rows == 0) ? 0 : $query->row['topic_date'];
				$data['forum_last_post_author_id']	= ($query->num_rows == 0) ? 0 : $query->row['last_post_author_id'];
				$data['forum_last_post_author']		= ($query->num_rows == 0) ? '' : $query->row['screen_name'];
		
				$query = $DB->query("SELECT post_date, author_id, screen_name 
									FROM exp_forum_posts, exp_members
									WHERE  member_id = author_id
									AND forum_id = '{$forum_id}' 
									ORDER BY post_date DESC LIMIT 1");
		
				if ($query->num_rows > 0)
				{
					if ($query->row['post_date'] > $data['forum_last_post_date'])
					{
						$data['forum_last_post_date'] 		= $query->row['post_date'];
						$data['forum_last_post_author_id']	= $query->row['author_id'];
						$data['forum_last_post_author']		= $query->row['screen_name'];
					}
				}
		
				$DB->query($DB->update_string('exp_forums', $data, "forum_id='{$forum_id}'"));
				unset($data);
				/** -------------------------------------
				/**  Update global forum stats
				/** -------------------------------------*/
				
				$query = $DB->query("SELECT forum_id FROM exp_forums");
				
				$total_topics = 0;
				$total_posts  = 0;
				
				foreach ($query->result as $row)
				{
					$q = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '".$row['forum_id']."'");
					$total_topics = ($total_topics == 0) ? $q->row['count'] : $total_topics + $q->row['count'];
								
					$q = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '".$row['forum_id']."'");	
					$total_posts = ($total_posts == 0) ? $q->row['count'] : $total_posts + $q->row['count'];
				}
				
				$DB->query("UPDATE exp_stats SET total_forum_topics = '{$total_topics}', total_forum_posts = '{$total_posts}' WHERE weblog_id = '0'");
			}
		
			$total_done = 1;
			$total_rows = 0;
			
			$query = $DB->query("SELECT topic_id FROM exp_forum_topics WHERE thread_total <= 1");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$res = $DB->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."'");
					$count = ($res->row['count'] == 0) ? 1 : $res->row['count'] + 1;
					
					$DB->query("UPDATE exp_forum_topics SET thread_total = '{$count}' WHERE topic_id = '".$row['topic_id']."'");
				}
			}
		}


        $DSP->title = $LANG->line('utilities');        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('recalculate'))).
			 		  $DSP->crumb_item($LANG->line('recounting'));

        
        $r = <<<EOT
        
        <script type="text/javascript"> 
        <!--

        function standby()
        {
			if (document.getElementById('batchlink').style.display == "block")
			{
				document.getElementById('batchlink').style.display = "none";
				document.getElementById('wait').style.display = "block";
        	}
        }
		
		-->
		</script>
EOT;

		$r .= NL.NL;

        $r .= $DSP->qdiv('tableHeading', $LANG->line('recalculate')); 
        $r .= $DSP->div('box');
        
		if ($num_rows === FALSE)
			$total_done = 0;
		else
			$total_done = $num_rows + $batch;


        if ($total_done >= $total_rows)
        {
            $r .= $DSP->qdiv('success', $LANG->line('recount_completed'));
            $r .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=recount_stats', $LANG->line('return_to_recount_overview')));         
        }
        else
        {
			$r .= $DSP->qdiv('itemWrapper', $LANG->line('total_records').NBS.$total_rows);
			$r .= $DSP->qdiv('itemWRapper', $LANG->line('items_remaining').NBS.($total_rows - $total_done));
        
            $line = $LANG->line('click_to_recount');
        
        	$to = (($total_done + $batch) >= $total_rows) ? $total_rows : ($total_done + $batch);
        	        
            $line = str_replace("%x", $total_done, $line);
            $line = str_replace("%y", $to, $line);
            
            $link = "<a href='".BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=do_recount'.AMP.'TBL='.$table.AMP.'T='.$total_done."'  onclick='standby();'><b>".$line."</b></a>";
			$r .= '<div id="batchlink" style="display: block; padding:0; margin:0;">';
            $r .= $DSP->qdiv('itemWrapper', BR.$link); 
			$r .= $DSP->div_c();

            
			$r .= '<div id="wait" style="display: none; padding:0; margin:0;">';
			$r .= $DSP->qdiv('success', BR.$LANG->line('standby_recount'));
			$r .= $DSP->div_c();

        }
        
		$r .= $DSP->div_c();
		
        $DSP->body = $r;
   }
   /* END */
   

    /** -------------------------------------------
    /**  Import Utilities Select Page
    /** -------------------------------------------*/

    function import_utilities($message = '')
    {  
		global $LANG, $DSP;
        
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr();

		$DSP->body	.=	$DSP->td('tableHeading', '');
		$DSP->body	.=	$LANG->line('import_utilities');
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();
		
		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=pm_import', $LANG->line('import_from_pm')));
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();

		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=mt_import', $LANG->line('import_from_mt')));
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();
		
		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=member_import', $LANG->line('member_import')));
		$DSP->body	.=	$DSP->td_c();

		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->table_c();
		
		$DSP->title = $LANG->line('import_utilities');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  $DSP->crumb_item($LANG->line('import_utilities'));
	}
	/* END */


    /** -------------------------------------------
    /**  Translation select page
    /** -------------------------------------------*/

    function translate_select($message = '')
    {  
        global $DSP, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
                
        $r  = $DSP->qdiv('tableHeading', $LANG->line('translation_tool'));
        
        if ($message != '')
        {
            $r .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));  
        }
        
        if ( ! is_writeable(PATH.'translations/'))
        {
            $r .= $DSP->div('box');        
            $r .= $DSP->qdiv('alert', $LANG->line('translation_dir_unwritable'));        
            $r .= BR;
            $r .= $LANG->line('please_set_permissions');
            $r .= $DSP->br(2);
            $r .= '<b><i>translations</i></b>';
            $r .= BR;
            $r .= $DSP->div_c();
        }
        else
        {
            $r .= $DSP->div('box');
            $r .= $DSP->heading($LANG->line('choose_translation_file'), 5);
            $source_dir = PATH_LANG.'english/';
                
            if ($fp = @opendir($source_dir)) 
            { 
                while (false !== ($file = readdir($fp))) 
                {
                	if ( preg_match("/lang\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
                	{
						$r .= $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=translate'.AMP.'F='.$file, $file);
						$r .= BR;
                    }
                } 
            } 
        }         
                
        $DSP->set_return_data(
                                $LANG->line('utilities'), 
                                $r,
                                $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).$DSP->crumb_item($LANG->line('translation_tool'))
                              );     
    }
    /* END */
    


    /** -------------------------------------------
    /**  Translate tool
    /** -------------------------------------------*/

    function translate()
    {  
        global $DSP, $IN, $LANG, $FNS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        $source_dir = PATH_LANG.'english/';
        
        $dest_dir = PATH.'translations/';
        
        $which = $FNS->filename_security($_GET['F']);
          	
        require $source_dir.$which;
        
        $M = $L;
        
        unset($L);
            
        if (file_exists($dest_dir.$which))
        {
            $writable = ( ! is_writeable($dest_dir.$which)) ? FALSE : TRUE;
        
            require $dest_dir.$which;
        }
        else
        {
            $writable = TRUE;    
        
            $L = $M;
        }
        
        $r  = $DSP->qdiv('tableHeading', $LANG->line('translation_tool'));
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_translation'));
        $r .= $DSP->input_hidden('filename', $which);
        
        $r .= $DSP->div('box');
                
        foreach ($M as $key => $val)
        {
            if ($key != '')
            {
                $trans = ( ! isset($L[$key])) ? '' : $L[$key];
                        
                $r .= $DSP->qdiv('itemWrapper', BR.'<b>'.stripslashes($val).'</b>');
                
                $trans = str_replace(array("&", "'"), array( "&amp;", "&#39;"), $trans);
                
                if (strlen($trans) < 125)
                {
                    $r .= "<input type='text' name='".$key."' value='".stripslashes($trans)."' size='90'  class='input' style='width:95%'><br />\n";
                }
                else
                {
                    $r .= "<textarea style='width:95%' name='".$key."'  cols='90' rows='5' class='textarea' >".stripslashes($trans)."</textarea>";                
                }
            }
        }
        
        $r .= $DSP->div_c();

        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('save_changes')));        

                
        $DSP->set_return_data(
                                $LANG->line('utilities'), 
                                                    
                                $r,
                                
                                $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
			 		  			$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=trans_menu', $LANG->line('translation_tool'))).
			 		  			$DSP->crumb_item($which)
                              );     
    }
    /* END */


    /** -------------------------------------------
    /**  Save translation
    /** -------------------------------------------*/

    function save_translation()
    {  
        global $DSP, $LANG, $FNS;
        
        if ( ! $DSP->allowed_group('can_admin_utilities'))
        {
            return $DSP->no_access_message();
        }
        
        $dest_dir = PATH.'translations/';
        $filename = $FNS->filename_security($_POST['filename']);
        
    
        unset($_POST['filename']);
    
    
        $str = '<?php'."\n".'$L = array('."\n\n\n";
    
        foreach ($_POST as $key => $val)
        {
            $val = str_replace("<",  "&lt;",   $val);
            $val = str_replace(">",  "&gt;",   $val);
            $val = str_replace("'",  "&#39;",  $val);
            $val = str_replace("\"", "&quot;", $val);
            $val = stripslashes($val);
        
            $str .= "\"$key\" =>\n\"$val\",\n\n";
        }
    
        $str .= "''=>''\n);\n?".">";
        
        // Make sure any existing file is writeable
        if (file_exists($dest_dir.$filename))
        {
        	@chmod($dest_dir.$filename, 0777);
        }
        
        $fp = @fopen($dest_dir.$filename, 'wb');    

        @flock($fp, LOCK_EX);        
        fwrite($fp, $str);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return Utilities::translate_select($LANG->line('file_saved'));        
    
    }
    /* END */
        
    
    
    /** -------------------------------------------
    /**  PHP INFO
    /** -------------------------------------------*/

    // The default PHP info page has a lot of HTML we don't want, 
    // plus it's gawd-awful looking, so we'll clean it up.
    // Hopefully this won't break if different versions/platforms render 
    // the default HTML differently    
    
    function php_info()
    {  
        global $DSP, $PREFS, $LANG;
        
        
        // We use this conditional only for demo installs.
        // It prevents users from viewing this function

		if ($PREFS->ini('demo_date') != FALSE)
		{
            return $DSP->no_access_message();
		}
        
        ob_start();
        
        phpinfo();
        
        $buffer = ob_get_contents();
        
        ob_end_clean();
        
        $output = (preg_match("/<body.*?".">(.*)<\/body>/is", $buffer, $match)) ? $match['1'] : $buffer;
        $output = preg_replace("/width\=\".*?\"/", "width=\"100%\"", $output);        
        $output = preg_replace("/<hr.*?>/", "<br />", $output); // <?
        $output = preg_replace("/<a href=\"http:\/\/www.php.net\/\">.*?<\/a>/", "", $output);
        $output = preg_replace("/<a href=\"http:\/\/www.zend.com\/\">.*?<\/a>/", "", $output);
        $output = preg_replace("/<a.*?<\/a>/", "", $output);// <?
        $output = preg_replace("/<th(.*?)>/", "<th \\1 align=\"left\" class=\"tableHeading\">", $output); 
        $output = preg_replace("/<tr(.*?).*?".">/", "<tr \\1>\n", $output);
        $output = preg_replace("/<td.*?".">/", "<td valign=\"top\" class=\"tableCellOne\">", $output);
        $output = preg_replace("/cellpadding=\".*?\"/", "cellpadding=\"2\"", $output);
        $output = preg_replace("/cellspacing=\".*?\"/", "", $output);
        $output = preg_replace("/<h2 align=\"center\">PHP License<\/h2>.*?<\/table>/si", "", $output);
        $output = preg_replace("/ align=\"center\"/", "", $output);
        $output = preg_replace("/<table(.*?)bgcolor=\".*?\">/", "\n\n<table\\1>", $output);
        $output = preg_replace("/<table(.*?)>/", "\n\n<table\\1 class=\"tableBorderNoBot\" cellspacing=\"0\">", $output);
        $output = preg_replace("/<h2>PHP License.*?<\/table>/is", "", $output);
        $output = preg_replace("/<br \/>\n*<br \/>/is", "", $output);
        $output = str_replace("<h1></h1>", "", $output);
        $output = str_replace("<h2></h2>", "", $output);
                                                
        $DSP->set_return_data(
                                $LANG->line('php_info'), 
                                $output, 
                                $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).$DSP->crumb_item($LANG->line('php_info'))
                             );
    }
    /* END */
    
}
// END CLASS



// ---------------------------------------------
//  Zip compression class
// ---------------------------------------------
//
// This class is based on a library aquired at Zend:
// http://www.zend.com/codex.php?id=696&single=1


class Zipper {

    var $zdata  = array();
    var $cdir   = array();
    var $offset = 0;

    
    /** -------------------------------------------
    /**  Compress directories
    /** -------------------------------------------*/

    function add_dir($name)
    {
        $name =str_replace ("\\", "/", $name);
        
        $fd = "\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00"    
              .pack("V", 0)
              .pack("V", 0)
              .pack("V", 0)
              .pack("v", strlen($name))
              .pack("v", 0)
              .$name;
        
        $this->cdata[] = $fd;
                
        $cd = "\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00"
              .pack("V", 0)
              .pack("V", 0)
              .pack("V", 0)
              .pack("v", strlen ($name))
              .pack("v", 0)
              .pack("v", 0)
              .pack("v", 0)
              .pack("v", 0)
              .pack("V", 16)
              .pack("V", $this->offset)
              .$name;
        
        $this->offset = strlen(implode('', $this->cdata));
        
        $this->cdir[] = $cd;
    }
    /* END */


    /** -------------------------------------------
    /**  Compress files
    /** -------------------------------------------*/

    function add_file($data, $name)
    {
        $name = str_replace("\\", "/", $name);
        
        $u_len = strlen($data);
        $crc   = crc32($data);
        $data  = gzcompress($data);
		$data  = substr($data, 2, -4);
        $c_len = strlen($data);
        
        $fd = "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00\x00\x00\x00\x00"
              .pack("V", $crc)
              .pack("V", $c_len)
              .pack("V", $u_len)
              .pack("v", strlen($name))
              .pack("v", 0)
              .$name
              .$data;
        
        $this->zdata[] = $fd;
                
        $cd = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00\x00\x00\x00\x00"
              .pack("V", $crc)
              .pack("V", $c_len)
              .pack("V", $u_len)
              .pack("v", strlen ($name))
              .pack("v", 0)
              .pack("v", 0)
              .pack("v", 0)
              .pack("v", 0)
              .pack("V", 32 )
              .pack("V", $this->offset)
              .$name;
  
        $this->offset = strlen(implode('', $this->zdata));
        
        $this->cdir[] = $cd;
    }
    /* END */


    /** -------------------------------------------
    /**  Output final zip file
    /** -------------------------------------------*/

    function output_zipfile()
    {

        $data = implode("", $this->zdata);
        $cdir = implode("", $this->cdir);


        return   $data
                .$cdir
                ."\x50\x4b\x05\x06\x00\x00\x00\x00"
                .pack("v", sizeof($this->cdir))
                .pack("v", sizeof($this->cdir))
                .pack("V", strlen($cdir))
                .pack("V", strlen($data))
                ."\x00\x00";
    }
    /* END */
}
// END CLASS
?>