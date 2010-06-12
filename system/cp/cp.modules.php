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
 File: cp.modules.php
-----------------------------------------------------
 Purpose: The module management class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Modules {

	var $lang_overrides = array('forum' => 'forum_cp');

    /** -----------------------------
    /**  Constructor
    /** -----------------------------*/

    function Modules()
    {
        global $IN;
        
        switch($IN->GBL('M'))
        {
            case FALSE  :   $this->module_home_page();
                break;
            case 'INST' :   $this->module_installer();
                break;
            default     :   $this->module_handler();
                break;
        }    
    }
    /* END */
    
   
    
    /** -----------------------------
    /**  Module home page
    /** -----------------------------*/
    
    function module_home_page($message = '')
    {  
        global $DSP, $LANG, $SESS, $DB;
        
        if ( ! $DSP->allowed_group('can_access_modules'))
        {
            return $DSP->no_access_message();
        }
        
		/** -----------------------------
		/**  Assing page title
		/** -----------------------------*/
        
        $title = $LANG->line('modules');
        
        $DSP->title = $title;
        $DSP->crumb = $title;        
        
        // Set access status
        
        $can_admin = ( ! $DSP->allowed_group('can_admin_modules')) ? FALSE : TRUE;
        
        
        /** -----------------------------------------------
        /**  Fetch all module names from "modules" folder
        /** -----------------------------------------------*/
                
        $modules = array();
    
        if ($fp = @opendir(PATH_MOD)) 
        { 
            while (false !== ($file = readdir($fp))) 
            {
            	if ( is_dir(PATH_MOD.$file) && ! preg_match("/[^a-z\_0-9]/", $file))
            	{               
					$LANG->fetch_language_file(( ! isset($this->lang_overrides[$file])) ? $file : $this->lang_overrides[$file]);
                                        
                    $modules[] = ucfirst($file);
                }
            } 
        }         
        closedir($fp); 
        
        sort($modules);

        /** --------------------------------------
        /**  Fetch the installed modules from DB
        /** --------------------------------------*/
        
        $query = $DB->query("SELECT module_name, module_version, has_cp_backend FROM exp_modules ORDER BY module_name");
        
        $installed_mods = array();
        
        foreach ($query->result as $row)
        {
            $installed_mods[$row['module_name']] = array($row['module_version'], $row['has_cp_backend']);
        }
   
   
        /** --------------------------------------
        /**  Fetch allowed Modules for a particular user
        /** --------------------------------------*/
        $sql = "SELECT exp_modules.module_name 
				FROM exp_modules, exp_module_member_groups
				WHERE exp_module_member_groups.group_id = '".$SESS->userdata['group_id']."'
				AND exp_modules.module_id = exp_module_member_groups.module_id
				ORDER BY module_name";
				        		
        $query = $DB->query($sql);
        
        $allowed_mods = array();
        
		if ($query->num_rows == 0 AND ! $can_admin)
		{
			return $DSP->body = $DSP->qdiv('', $LANG->line('module_no_access'));
		}	
        
        foreach ($query->result as $row)
        {
            $allowed_mods[] = $row['module_name'];
        }
   
        /** --------------------------------------
        /**  Build page output
        /** --------------------------------------*/
                
        $r = '';
        
        if ($message != '')
        	$r .= $DSP->qdiv('box', $message);
        
        $r .= $DSP->table('tableBorder', '0', '0', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', 
                                array(
										NBS,
                                        $LANG->line('module_name'),
                                        $LANG->line('module_description'),
                                        $LANG->line('module_version'),
                                        $LANG->line('module_status'),
                                        $LANG->line('module_action')
                                     )
                                ).
              $DSP->tr_c();

        
        $i = 0;
		$n = 1;
      
        foreach ($modules as $mod)
        {
			if ( ! $can_admin)
			{
				if (! in_array($mod, $allowed_mods))
				{
					continue;
				}
			}
        	        	
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
            
            $r .= $DSP->tr();

            $r .= $DSP->table_qcell($style, $DSP->qspan('', $n++), '1%');
            
            // Module Name          
                        
            $name = ($LANG->line(strtolower($mod).'_module_name') != FALSE) ? $LANG->line(strtolower($mod).'_module_name') : $mod;            
                    
            if (isset($installed_mods[$mod]) AND $installed_mods[$mod]['1'] == 'y')
            {
				$name = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.$mod, $name);
            }
                                                 
            $r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $name), '29%');            
            
            // Module Description
            
            $r .= $DSP->table_qcell($style, $LANG->line(strtolower($mod).'_module_description'), '36%');
        
            
            // Module Version

            $version = ( ! isset($installed_mods[$mod])) ?  '--' : $installed_mods[$mod]['0'];
            
            $r .= $DSP->table_qcell($style, $version, '10%');


            // Module Status
        
            $status = ( ! isset($installed_mods[$mod]) ) ? 'not_installed' : 'installed';
        		
			$in_status = str_replace(" ", "&nbsp;", $LANG->line($status));
        
            $show_status = ($status == 'not_installed') ? $DSP->qspan('highlight', $in_status) : $DSP->qspan('highlight_alt', $in_status);
        
            $r .= $DSP->table_qcell($style, $show_status, '12%');
            
            // Module Action
            
            $action = ($status == 'not_installed') ? 'install' : 'deinstall';
            
            $show_action = ($can_admin) ? $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=INST'.AMP.'MOD='.$mod, $LANG->line($action)) : '--';
            
            $r .= $DSP->table_qcell($style, $show_action, '10%');  
            
            
            $r .= $DSP->tr_c();      
        }

        $r .= $DSP->table_c();
        
        if ($message != '')
            $DSP->crumb_ov = TRUE;
        
        $DSP->body  = $r;
    }
    /* END */
    
    
    
    /** -----------------------------
    /**  Module handler
    /** -----------------------------*/

    function module_handler()
    {
        global $LANG, $IN, $DSP, $DB, $OUT, $SESS, $FNS;
        
        if ( ! $DSP->allowed_group('can_access_modules'))
        {
            return $DSP->no_access_message();
        }    
        
        if ( ! $MOD = $IN->GBL('M', 'GET'))
        {
            return false;
        }
        
        
        $module = $FNS->filename_security(strtolower($MOD));

        if ($SESS->userdata['group_id'] != 1)
        {
        	$query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = '".$DB->escape_str(ucfirst($module))."'"); 
			
			if ($query->num_rows == 0)
			{
				return false;
			}
			
			$access = FALSE;
						
			foreach ($SESS->userdata['assigned_modules'] as $key => $val)
			{
				if ($key == $query->row['module_id'])
				{
					$access = TRUE;
					break;
				}
			}
			
			if ($access == FALSE)
			{
				return $DSP->no_access_message();
			}    
		}
                
        $LANG->fetch_language_file(( ! isset($this->lang_overrides[$module])) ? $module : $this->lang_overrides[$module]); 
        
        $class  = ucfirst($MOD).'_CP';
                
        $path = PATH_MOD.$module.'/mcp.'.$module.EXT;
        
        if ( ! is_file($path))
        {
            $OUT->fatal_error($LANG->line('module_can_not_be_found'));
        }
        
		// set the path to view files for this module
		$DSP->view_path = PATH_MOD.$module.'/views/';
		
        require $path;
        
        $MOD = new $class;
    }
    /* END */


    
    /** ----------------------------------
    /**  Module installer / De-installer
    /** ----------------------------------*/

    function module_installer()
    {
        global $LANG, $IN, $DSP, $DB, $OUT, $FNS;
        
        if ( ! $DSP->allowed_group('can_admin_modules'))
        {
            return $DSP->no_access_message();
        }    
        
        if ( ! $module = $IN->GBL('MOD', 'GET'))
        {
            return false;
        }
        
        $module = $FNS->filename_security(strtolower($module));
        
        $class  = ucfirst($module).'_CP';
       
        $query = $DB->query("SELECT count(*) AS count FROM exp_modules WHERE module_name = '".$DB->escape_str(ucfirst($module))."'");
        
        if ($query->row['count'] != 0)
        {
			if ( ! $IN->GBL('DO', 'POST'))
			{
				return $this->deinstall_confirm($module);
			}
        	
        	$method = $module.'_module_deinstall';
        	
        	$error = 'module_deinstall_error';
        }
        else
        {
        	$method = $module.'_module_install';
        	
        	$error = 'module_install_error';
        }                
        
        $path = PATH_MOD.$module.'/mcp.'.$module.EXT;
        
        if ( ! is_file($path))
        {
            $OUT->fatal_error($LANG->line('module_can_not_be_found'));
        }
        
		if ( ! class_exists($class))
		{
        	require $path;
		}        
		
        
        $MOD = new $class(0);

		$MOD->$method();

        $LANG->fetch_language_file($module);        

        $line = (stristr($method, 'deinstall')) ? $LANG->line('module_has_been_removed') : $LANG->line('module_has_been_installed');
	
		$name = ($LANG->line($module.'_module_name') == FALSE) ? ucfirst($module) : $LANG->line($module.'_module_name');

        $message = $DSP->qspan('success', $line).NBS.$DSP->qspan('defaultBold', $name);

        $this->module_home_page($message);
    }
    /* END */
    
    
    /** ----------------------------------
    /**  De-install Confirm
    /** ----------------------------------*/

    function deinstall_confirm($module = '')
    {
        global $DSP, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_modules'))
        {
            return $DSP->no_access_message();
        }
        
        if ($module == '')
        {
            return $DSP->no_access_message();
        }

        $DSP->title	= $LANG->line('delete_module');
		$DSP->crumb	= $LANG->line('delete_module');

        $DSP->body	 =	$DSP->form_open(
        								array('action' => 'C=modules'.AMP.'M=INST'.AMP.'MOD='.$module),
        								array('DO' => 'TRUE')
        								);

		$DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('delete_module'));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('delete_module_confirm'));		
		$DSP->body .= $DSP->qdiv('defaultBold', BR.ucfirst($module));
		$DSP->body .= $DSP->qdiv('alert', BR.$LANG->line('data_will_be_lost')).BR;
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('delete_module')));
		$DSP->body .= $DSP->form_close();
    }
    /* END */
    
    
}
// END CLASS
?>