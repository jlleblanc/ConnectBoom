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
 File: mcp.metaweblog_api.php
-----------------------------------------------------
 Purpose: Metaweblog API class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Metaweblog_api_CP {

    var $version = '1.0';
    var $field_array = array();
    var $status_array = array();
    var $group_array = array();
    
    
    /** -------------------------------------------
    /**  Constructor
    /** -------------------------------------------*/

	function Metaweblog_api_CP ($switch = TRUE)
	{
		global $IN, $DB;
		
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Metaweblog_api'");
        
        if ($query->row['count'] == 0)
        {
        	return;
        }
        
        /** ----------------------------------
        /**  Update Fields
        /** ----------------------------------*/
        
        if ($DB->table_exists('exp_metaweblog_api'))
        {
        	$existing_fields = array();
        	
        	$new_fields = array('entry_status' => "`entry_status` varchar(50) NOT NULL default 'null' AFTER `metaweblog_parse_type`");        	
        	
        	$query = $DB->query("SHOW COLUMNS FROM exp_metaweblog_api");
        	
        	foreach($query->result as $row)
        	{
        		$existing_fields[] = $row['Field'];
        	}
        	
        	foreach($new_fields as $field => $alter)
        	{
        		if ( ! in_array($field, $existing_fields))
        		{
        			$DB->query("ALTER table exp_metaweblog_api ADD COLUMN {$alter}");
        		}
        	}        	
        }
		
		/** -------------------------------
		/**  On with the show!
		/** -------------------------------*/

		if ($switch)
        {
            switch($IN->GBL('P'))
            {
                case 'create'	:  $this->modify_configuration('new');
                    break;
                case 'modify'	:  $this->modify_configuration();
                	break;
                case 'save'		:  $this->save_configuration();
                    break;
                case 'delete_confirm' : $this->delete_confirm();
                	break;
                case 'delete'	:  $this->delete_configs();
                    break;
                default			:  $this->homepage();
                    break;
            }
        }
	}
	/* END */
	
	
	/** -------------------------------------------
    /**  Control Panel homepage
    /** -------------------------------------------*/

	function homepage($msg = '')
	{
		global $DSP, $LANG, $PREFS, $FNS, $DB;
                        
        $DSP->title  = $LANG->line('metaweblog_api_module_name');
        $DSP->crumb  = $LANG->line('metaweblog_api_module_name');
        
		$DSP->right_crumb($LANG->line('metaweblog_create_new'), BASE.AMP.'C=modules'.AMP.'M=metaweblog_api'.AMP.'P=create');
        
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('metaweblog_configurations')); 
        
        if ($msg != '')
        {
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
        }
                
        $qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $api_url = $FNS->fetch_site_index(0, 0).$qs.'ACT='.$FNS->fetch_action_id('Metaweblog_api', 'incoming');		
        
        $query = $DB->query("SELECT metaweblog_pref_name, metaweblog_id FROM exp_metaweblog_api");
        
        $DSP->body	.=	$DSP->toggle();
                
        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=metaweblog_api'.AMP.'P=delete_confirm', 'name' => 'target', 'id' => 'target'));
    
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr().
						$DSP->table_qcell('tableHeadingAlt', 
											array(
													$LANG->line('metaweblog_config_name').'/'.$LANG->line('edit'),
													$LANG->line('metaweblog_config_url'),
													$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('delete').NBS.NBS
												 )
											).
						$DSP->tr_c();
		
		$i = 0;

		foreach ($query->result as $row)
		{				
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
                      
            $DSP->body .= $DSP->tr();
            
            $url = $api_url.'&id='.$row['metaweblog_id'];
            
            $DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold',
            														$DSP->anchor(BASE.AMP.'C=modules'.
            													   AMP.'M=metaweblog_api'.
            													   AMP.'P=modify'.
            													   AMP.'id='.$row['metaweblog_id'],
            													   $row['metaweblog_pref_name'])), '18%');
            													   
            $DSP->body .= $DSP->table_qcell($style,
												$DSP->span('default', 'style="left:-9000px;position:absolute;"'). // hides the label from everyone but aural screen readers
												$LANG->line('metaweblog_config_url', $row['metaweblog_pref_name'].'_'.$row['metaweblog_id']).
												$DSP->span_c().
												$DSP->input_text($row['metaweblog_pref_name'].'_'.$row['metaweblog_id'], $url, '20', '400', 'input', '90%', "readonly='readonly'"),
											'67%');
                                   													   
            $DSP->body .= $DSP->table_qcell($style, ($row['metaweblog_id'] == '1') ? ' -- ' : $DSP->input_checkbox('toggle[]', $row['metaweblog_id']), '15%');
											
			$DSP->body .= $DSP->tr_c();
		}
		
        $DSP->body	.=	$DSP->table_c(); 
    	
		$DSP->body	.=	$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('delete')));             
        
        $DSP->body	.=	$DSP->form_close();     
	}
	/* END */
	
	
	/** -------------------------------------------
    /**  Modify Configuration
    /** -------------------------------------------*/

    function modify_configuration($id = '')
    { 
        global $IN, $DSP, $LANG, $DB, $SESS, $PREFS;           
        
        $id = ( ! $IN->GBL('id', 'GET')) ? $id : $IN->GBL('id');
        
        if ($id == '')
        {
        	return $this->homepage();
        }
        
        /** ----------------------------
        /**  Form Values
        /** ----------------------------*/
        
        $pref_name			= '';
        $parse_type			= 'n';
        $entry_status		= 'null';
        $field_group_id		= '1';
        $excerpt_field_id	= '0';
        $content_field_id	= '1';
        $more_field_id		= '0';
        $keywords_field_id	= '0';
        $upload_dir			= '1';
        
        if ($id != 'new')
        {
        	$query = $DB->query("SELECT * FROM exp_metaweblog_api WHERE metaweblog_id = '".$DB->escape_str($id)."'");
        	
        	if ($query->num_rows == 0)
        	{
        		return $this->homepage();
        	}
        	
        	foreach($query->row as $name => $pref)
        	{
        		$name    = str_replace('metaweblog_', '', $name);
        		${$name} = $pref;
        	}	
        }
                
        $DSP->title  = $LANG->line('metaweblog_api_module_name');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=metaweblog_api', $LANG->line('metaweblog_api_module_name'));
		$DSP->crumb .= ($id == 'new') ? $DSP->crumb_item($LANG->line('new_config')) : $DSP->crumb_item($LANG->line('modify_config'));
		
		$DSP->body .= ($id == 'new') ? $DSP->qdiv('tableHeading', $LANG->line('new_config')) : $DSP->qdiv('tableHeading', $LANG->line('modify_config'));
		
        $DSP->body .=	$DSP->form_open(
        								array(
        										'action' => 'C=modules'.AMP.'M=metaweblog_api'.AMP.'P=save', 
        										'name'	=> 'configuration',
        										'id' 	=> 'configuration'
        									),
        								array('metaweblog_id' => $id)
        								);
            	
    	/** ---------------------------
    	/**  Begin Creating Form
    	/** ---------------------------*/
    	
    	$LANG->fetch_language_file('publish');
    	
    	$r  =   $this->filtering_menus();
				
		$r .=	$DSP->table('tableBorder', '0', '', '100%');
		$r .=	$DSP->tr();
		$r .=   $DSP->td('tableHeadingAlt', '', '2');
		$r .=   $LANG->line('configuration_options');
		$r .=   $DSP->td_c();
		$r .=	$DSP->tr_c();
		
		$i = 0;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// PREF NAME
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_pref_name', 'metaweblog_pref_name');
		$r .= $DSP->div_c();
		// $r .= $DSP->qdiv('subtext', $LANG->line($sub));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_text('metaweblog_pref_name', $pref_name, '20', '120', 'input', '100%');
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// PARSE TYPE
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_parse_type', 'metaweblog_parse_type');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('metaweblog_parse_type_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('metaweblog_parse_type');
    	$r .= $DSP->input_select_option('y', $LANG->line('yes'), ($parse_type == 'y') ? 'y' : '');
    	$r .= $DSP->input_select_option('n', $LANG->line('no'), ($parse_type == 'n') ? 'y' : '');
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// Entry Status
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_entry_status', 'entry_status');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('entry_status');
    	$r .= $DSP->input_select_option('null', $LANG->line('do_not_set_status'), ($entry_status == 'null') ? 'y' : '');
    	$r .= $DSP->input_select_option('open', $LANG->line('open'), ($entry_status == 'open') ? 'y' : '');
    	$r .= $DSP->input_select_option('closed', $LANG->line('closed'), ($entry_status == 'closed') ? 'y' : '');
    	
    	foreach($this->status_array as $value)
    	{
    		$r .= $DSP->input_select_option($value[1], $value[1], ($value[1] == $entry_status) ? 'y' : '');
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// FIELD GROUP
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_field_group', 'field_group_id');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= "<select name='field_group_id' class='select' onchange='changemenu(this.selectedIndex);'>";
    	
    	foreach($this->group_array as $key => $value)
    	{
    		$r .= $DSP->input_select_option($key, $value, ($key == $field_group_id) ? 'y' : '');
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// EXCERPT FIELDS
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_excerpt_field', 'excerpt_field_id');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('excerpt_field_id');
    	$r .= $DSP->input_select_option('none', $LANG->line('none'));
    	
    	foreach($this->field_array as $value)
    	{
    		if ($value['0'] == $field_group_id)
    		{
    			$r .= $DSP->input_select_option($value['1'], $value['2'], ($value['1'] == $excerpt_field_id) ? 'y' : '');
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// CONTENT FIELDS
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_content_field', 'content_field_id');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('content_field_id');
    	$r .= $DSP->input_select_option('none', $LANG->line('none'));
    	
    	foreach($this->field_array as $value)
    	{
    		if ($value['0'] == $field_group_id)
    		{
    			$r .= $DSP->input_select_option($value['1'], $value['2'], ($value['1'] == $content_field_id) ? 'y' : '');
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// MORE FIELDS
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_more_field', 'more_field_id');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('more_field_id');
    	$r .= $DSP->input_select_option('none', $LANG->line('none'));
    	
    	foreach($this->field_array as $value)
    	{
    		if ($value['0'] == $field_group_id)
    		{
    			$r .= $DSP->input_select_option($value['1'], $value['2'], ($value['1'] == $more_field_id) ? 'y' : '');
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// KEYWORDS FIELDS
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_keywords_field', 'keywords_field_id');
		$r .= $DSP->div_c();
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('keywords_field_id');
    	$r .= $DSP->input_select_option('none', $LANG->line('none'));
    	
    	foreach($this->field_array as $value)
    	{
    		if ($value['0'] == $field_group_id)
    		{
    			$r .= $DSP->input_select_option($value['1'], $value['2'], ($value['1'] == $keywords_field_id) ? 'y' : '');
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		
		// UPLOAD DIRECTORIES
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('metaweblog_upload_dir', 'upload_dir');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('metaweblog_upload_dir_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('upload_dir');
    	$r .= $DSP->input_select_option('none', $LANG->line('none'));
    	
    	if ($SESS->userdata['group_id'] == 1)
        {            
            $query = $DB->query("SELECT id, name, site_label FROM exp_upload_prefs, exp_sites WHERE exp_upload_prefs.site_id = exp_sites.site_id AND is_user_blog = 'n' ORDER BY name");
        }
        else
        {         	
            $sql = "SELECT id, name, site_label FROM exp_upload_prefs, exp_sites WHERE exp_upload_prefs.site_id = exp_sites.site_id ";
            
            if ($PREFS->ini('multiple_sites_enabled') !== 'y')
			{
				$sql .= "AND site_id = '1' ";
			}
        
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
			
				$sql .= " AND is_user_blog = 'n' ";
				
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
				$sql .= " AND weblog_id = '".UB_BLOG_ID."' ";		
			}
        
        	$query = $DB->query($sql);
        }   
    	
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{
    			$r .= $DSP->input_select_option($row['id'], ($PREFS->ini('multiple_sites_enabled') === 'y') ? $row['site_label'].NBS.'-'.NBS.$row['name'] : $row['name'], ($row['id'] == $upload_dir) ? 'y' : '');
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		

		$r .= $DSP->table_c();
		
		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit(($id == 'new') ? $LANG->line('submit') : $LANG->line('update')));
        
		$DSP->body .= $r.$DSP->form_close();       
	}
	/* END */
      

	/** -------------------------------------------
    /**  Save Configuration
    /** -------------------------------------------*/

    function save_configuration()
    {
    	global $IN, $DSP, $LANG, $DB, $OUT;
		
		$required	= array('metaweblog_id', 'metaweblog_pref_name', 'metaweblog_parse_type', 'entry_status',
    						'field_group_id','excerpt_field_id','content_field_id',
    						'more_field_id','keywords_field_id','upload_dir');
    	
    	$data		= array();
    	
    	foreach($required as $var)
    	{
    		if ( ! isset($_POST[$var]) OR $_POST[$var] == '')
    		{
    			return $OUT->show_user_error('submission', $LANG->line('metaweblog_mising_fields'));
    		}
    		
    		$data[$var] = $_POST[$var];
    	}
    	
    	if ($_POST['metaweblog_id'] == 'new' )
    	{
    		$data['metaweblog_id'] = '';    		
    		$DB->query($DB->insert_string('exp_metaweblog_api', $data));
    		$message = $LANG->line('configuration_created');
    	}
    	else
    	{    		
			$DB->query($DB->update_string('exp_metaweblog_api', $data, "metaweblog_id = '".$DB->escape_str($_POST['metaweblog_id'])."'"));
			$message = $LANG->line('configuration_updated');
    	}
    	
    	$this->homepage($message);
    }
    /* END */
      
	/** -------------------------------------------
    /**  Delete Confirm
    /** -------------------------------------------*/

    function delete_confirm()
    { 
        global $IN, $DSP, $LANG;
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->homepage();
        }
        
        $DSP->title = $LANG->line('metaweblog_api_module_name');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=metaweblog_api', $LANG->line('metaweblog_api_module_name'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('delete'));

        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=metaweblog_api'.AMP.'P=delete'));
        
        $i = 0;
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $DSP->body	.=	$DSP->input_hidden('delete[]', $val);
                
                $i++;
            }        
        }
        
		$DSP->body .= $DSP->heading($DSP->qspan('alert', $LANG->line('metaweblog_delete_confirm')));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('metaweblog_delete_question'));
		$DSP->body .= $DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone'));
		$DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('delete')));
		$DSP->body .= $DSP->qdiv('alert',$DSP->div_c());
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->form_close();
    }
    /* END */   
    
    
    
    /** -------------------------------------------
    /**  Delete Configurations
    /** -------------------------------------------*/

    function delete_configs()
    { 
        global $IN, $DSP, $LANG, $SESS, $DB;        
        
        if ( ! $IN->GBL('delete', 'POST'))
        {
            return $this->homepage();
        }

        $ids = array();
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val))
            {
                $ids[] = "metaweblog_id = '".$DB->escape_str($val)."'";
            }        
        }
        
        $IDS = implode(" OR ", $ids);
        
        $DB->query("DELETE FROM exp_metaweblog_api WHERE ".$IDS);
    
        $message = (count($ids) == 1) ? $LANG->line('metaweblog_deleted') : $LANG->line('metaweblogs_deleted');


        return $this->homepage($message);
    }
    /* END */ 
    

	/** -----------------------------------------------------------
    /**  JavaScript filtering code
    /** -----------------------------------------------------------*/
    // This function writes some JavaScript functions that
    // are used to switch the various pull-down menus in the
    // CREATE page
    //-----------------------------------------------------------

    function filtering_menus()
    { 
        global $DSP, $LANG, $SESS, $FNS, $DB, $PREFS;
     
        // In order to build our filtering options we need to gather 
        // all the field groups and fields
        
        $xql = '';
        
        /*
        
        // -----------------------------------
        //  Determine Available Groups
        //
        //  We only allow them to specify
        //  groups that to which they have access
        //  or that are used by a weblog currently
        // -----------------------------------
        
        $groups = array();
        
        $sql = "SELECT field_group FROM exp_weblogs ";
		
		
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n'";
		}
		
		
		$query = $DB->query($sql);

        if ($query->num_rows > 0)
        {            
            foreach ($query->result as $row)
            {
                $groups[] = $row['field_group'];
            }
        }
        
        $xql = "WHERE group_id IN ('".implode("','", $groups)."'";
        */
        
        /** ----------------------------- 
        /**  Weblog Field Groups
        /** -----------------------------*/
        
        $xsql = '';
        
        if ($PREFS->ini('multiple_sites_enabled') !== 'y')
		{
			$xsql = "AND exp_field_groups.site_id = '1' ";
		}
        
        $query = $DB->query("SELECT group_id, group_name, site_label FROM exp_field_groups, exp_sites WHERE exp_field_groups.site_id = exp_sites.site_id ".$xsql);
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->group_array[$row['group_id']]  = str_replace('"','', ($PREFS->ini('multiple_sites_enabled') === 'y') ? $row['site_label'].NBS.'-'.NBS.$row['group_name'] : $row['group_name']);
        	}
		}
        
        
        /** ----------------------------- 
        /**  Custom Weblog Fields
        /** -----------------------------*/
        
        $query = $DB->query("SELECT group_id, field_label, field_id FROM exp_weblog_fields WHERE field_type IN ('text', 'textarea') ORDER BY field_order, field_label");
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
        	}
		}
		
		
		/** ----------------------------- 
        /**  Status Array
        /** -----------------------------*/
        
        $query = $DB->query("SELECT group_id, status FROM exp_statuses WHERE status NOT IN ('open', 'closed') ORDER BY status");
        
        if ($query->num_rows > 0)
        {
        	foreach ($query->result as $row)
        	{
            	$this->status_array[]  = array($row['group_id'], $row['status']);
        	}
        }
		

        
        // Build the JavaScript needed for the dynamic pull-down menus
        // We'll use output buffering since we'll need to return it
        // and we break in and out of php
        
        ob_start();
                
?>

<script type="text/javascript">
<!--

var firstfield = 0;

function changemenu(index)
{ 

  var efields = new Array();
  var cfields = new Array();
  var mfields = new Array();
  var kfields = new Array();
  
  var k = firstfield;
  var l = firstfield;
  var m = firstfield;
  var n = firstfield;
  
  var group = document.configuration.field_group_id.options[index].value;
  
   with(document.configuration.elements['field_group_id'])
   {
<?php
                        
        foreach ($this->group_array as $key => $val)
        {
        	$any = 0;
?>
		if (group == "<?php echo $key ?>")
		{
			efields[k] = new Option("<?php echo $LANG->line('none'); ?>", "0"); k++;
			cfields[l] = new Option("<?php echo $LANG->line('none'); ?>", "0"); l++;
			mfields[m] = new Option("<?php echo $LANG->line('none'); ?>", "0"); m++;
			kfields[n] = new Option("<?php echo $LANG->line('none'); ?>", "0"); n++;<?php
         
            if (count($this->field_array) > 0)
            {            
                foreach ($this->field_array as $k => $v)
                {
                    if ($v['0'] == $key)
                    {
                    	echo "\n";                    
            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page        
?>
			efields[k] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); k++; 
			cfields[l] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); l++; 
			mfields[m] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); m++; 
			kfields[n] = new Option("<?php echo $v['2'];?>", "<?php echo $v['1'];?>"); n++; <?php
                    }
                }
            }
?>
		}
<?php
		}
?>
	}
	
	
	
	with (document.configuration.elements['excerpt_field_id'])
	{
		for (k = length-1; k >= firstfield; k--)
			options[k] = null;
		
		for (k = firstfield; k < efields.length; k++)
			options[k] = efields[k];
		
		options[0].selected = true;
	}
	
	with (document.configuration.elements['content_field_id'])
	{
		for (l = length-1; l >= firstfield; l--)
			options[l] = null;
		
		for (l = firstfield; l < cfields.length; l++)
			options[l] = cfields[l];
		
		options[0].selected = true;
	}
	
	with (document.configuration.elements['more_field_id'])
	{
		for (m = length-1; m >= firstfield; m--)
			options[m] = null;
		
		for (m = firstfield; m < mfields.length; m++)
			options[m] = mfields[m];
		
		options[0].selected = true;
	}
	
	with (document.configuration.elements['keywords_field_id'])
	{
		for (n = length-1; n >= firstfield; n--)
			options[n] = null;
		
		for (n = firstfield; n < kfields.length; n++)
			options[n] = kfields[n];
		
		options[0].selected = true;
	}
	
	
}

//--></script>
        
<?php
                
        $javascript = ob_get_contents();
        
        ob_end_clean();
        
        return $javascript;
     
    }
    /* END */
    
	

    /** -------------------------------------------
    /**  Module installer
    /** -------------------------------------------*/

    function metaweblog_api_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules 
        		  (module_id, module_name, module_version, has_cp_backend) 
        		  VALUES 
        		  ('', 'Metaweblog_api', '$this->version', 'y')";
        		  
    	$sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Metaweblog_api', 'incoming')";
    	
    	$sql[] = "CREATE TABLE IF NOT EXISTS `exp_metaweblog_api` (
    			 `metaweblog_id` int(5) unsigned NOT NULL auto_increment,
    			 `metaweblog_pref_name` varchar(80) NOT NULL default '',
    			 `metaweblog_parse_type` varchar(1) NOT NULL default 'y',
    			 `entry_status` varchar(50) NOT NULL default 'null',
    			 `field_group_id` int(5) unsigned NOT NULL default '0',
    			 `excerpt_field_id` int(7) unsigned NOT NULL default '0',
    			 `content_field_id` int(7) unsigned NOT NULL default '0',
    			 `more_field_id` int(7) unsigned NOT NULL default '0',
    			 `keywords_field_id` int(7) unsigned NOT NULL default '0',
    			 `upload_dir` int(5) unsigned NOT NULL default '1',
    			 PRIMARY KEY (`metaweblog_id`));";			 
    			 
 		$sql[] = "INSERT INTO exp_metaweblog_api (metaweblog_id, metaweblog_pref_name, field_group_id, content_field_id) VALUES ('', 'Default', '1', '2')";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
        return true;
    }
    /* END */
    
    
    /** -------------------------------------------
    /**  Module de-installer
    /** -------------------------------------------*/

    function metaweblog_api_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Metaweblog_api'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Metaweblog_api'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Metaweblog_api'";
        $sql[] = "DROP TABLE IF EXISTS exp_metaweblog_api";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */



}
/* END */
?>