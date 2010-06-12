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
 File: cp.templates.php
-----------------------------------------------------
 Purpose: The template management functions
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Templates {

    var $template_map   = array();
    
    // Reserved Template names
    var $reserved_names = array('act', 'css', 'trackback');
    
    // Reserved Global Variable names
    var $reserved_vars  = array(
								'lang',
								'charset',
								'homepage',
								'debug_mode',
								'gzip_mode',
								'version',
								'elapsed_time',
								'hits',
								'total_queries',
								'XID_HASH'
    							);
    							

    function Templates()
    {
        global $IN, $DSP, $PREFS;
        
		if ($PREFS->ini("use_category_name") == 'y' AND $PREFS->ini("reserved_category_word") != '')
		{
			$this->reserved_names[] = $PREFS->ini("reserved_category_word");
		}
		
		if ($PREFS->ini("forum_is_installed") == 'y' AND $PREFS->ini("forum_trigger") != '')
		{
			$this->reserved_names[] = $PREFS->ini("forum_trigger");
		}
		
		if ($PREFS->ini("profile_trigger") != '')
		{
			$this->reserved_names[] = $PREFS->ini("profile_trigger");
		}		
		        
		if ($IN->GBL('tgpref', 'GP') AND $IN->GBL('M') != '')
		{
			$DSP->url_append = AMP.'tgpref='.$IN->GBL('tgpref', 'GP');
		}

        switch($IN->GBL('M'))
        {
            case 'global_variables'      : $this->global_variables();
                break;
            case 'edit_global_var'       : $this->edit_global_variable();
                break;
            case 'update_global_var'     : $this->update_global_variable();
                break;
            case 'delete_global_var'     : $this->global_variable_delete_conf();
                break;
            case 'do_delete_global_var'  : $this->delete_global_variable();
                break;
            case 'new_tg_form'           : $this->edit_template_group_form();
                break;
            case 'edit_tg_form'          : $this->edit_template_group_form();
                break;
            case 'update_tg'             : $this->update_template_group();
                break;
            case 'edit_tg_order'         : $this->edit_template_group_order_form();
                break;
            case 'update_tg_order'       : $this->update_template_group_order();
                break;
            case 'tg_del_conf'           : $this->template_group_del_conf();
                break;
            case 'delete_tg'             : $this->template_group_delete();
                break;
            case 'new_templ_form'        : $this->new_template_form();
                break;
            case 'new_template'          : $this->create_new_template();
                break;
            case 'tmpl_del_conf'         : $this->template_del_conf();
                break;
            case 'delete_template'       : $this->delete_template();
                break;
            case 'edit_template'         : $this->edit_template();
                break;
            case 'update_template'       : $this->update_template();
                break;
            case 'edit_preferences'      : $this->edit_preferences();
                break;
            case 'update_template_prefs' : $this->update_template_prefs();
                break;
            case 'template_access'        : $this->template_access();
                break;
            case 'update_template_access' : $this->update_template_access();
                break;
            case 'revision_history'      : $this->view_template_revision();
                break;
            case 'clear_revisions'       : $this->clear_revision_history();
                break;
            case 'export_tmpl'           : $this->export_templates_form();
                break;
            case 'export'                : $this->export_templates();
                break;
            case 'export_template'       : $this->export_template();
                break;
            case 'template_prefs_manager': $this->template_prefs_manager();
            	break;
            case 'update_manager_prefs'	 : $this->update_manager_prefs();
            	break;
            default                      : $this->template_manager();
                break;
        }    
    }
    /* END */


	/** -----------------------------
    /**  Template Preferences Manager
    /** -----------------------------*/
    
    function template_prefs_manager($message = '', $group_id = '')
    {  
        global $IN, $DSP, $DB, $SESS, $LANG, $REGX, $PREFS;
        
		if ( ! $DSP->allowed_group('can_admin_templates'))
		{
			return $DSP->no_access_message();
		}
                
        if ($IN->GBL('id') !== FALSE)
        {
        	$group_id = $IN->GBL('id');
        }
        
        $user_blog = FALSE;
        
		$DSP->crumbline = TRUE;

        if ($SESS->userdata['tmpl_group_id'] != 0)
        {
            $user_blog = TRUE;
        }
    	
    	/** -------------------------------------
    	/**  Opening Remarks
    	/** -------------------------------------*/
    	
    	$DSP->title  = $LANG->line('template_preferences_manager');        
        $DSP->crumb  = $LANG->line('template_preferences_manager');   
        $r = $DSP->qdiv('tableHeading', $LANG->line('template_preferences_manager'));
    	
		ob_start();
		?>


<script type="text/javascript">

function showHideTemplate(htmlObj)
{
	if (isNaN(htmlObj.value) || htmlObj.value == '') return;
	
	for (var g = 0; g < htmlObj.options.length; g++)
	{
		if (document.getElementById('template_group_div_' + htmlObj.options[g].value))
		{
			extTextDiv = document.getElementById('template_group_div_' + htmlObj.options[g].value);
			
			if (htmlObj.options[g].selected == true)
			{
				if (extTextDiv.style.display != 'block')
				{
					extTextDiv.style.display = "block";
				}
			}
			else if(extTextDiv.style.display != 'none')
			{
				extTextDiv.style.display = "none";
			}
		}
	}
}

</script>

<?php

		$r .= ob_get_contents();
		ob_end_clean();   
		
		/** -------------------------------------
    	/**  Retrieve Valid Template Groups and Templates
    	/** -------------------------------------*/
              
        if ($SESS->userdata['group_id'] != 1 && (sizeof($SESS->userdata['assigned_template_groups']) == 0 OR $DSP->allowed_group('can_admin_templates') == FALSE))
        {
        	$r .= $DSP->qdiv('', $LANG->line('no_templates_assigned'));
        	return $DSP->body = $r;
        }
        
        $sql  = "SELECT tg.group_id, tg.group_name, t.template_id, t.template_name
				 FROM exp_template_groups tg , exp_templates t
				 WHERE tg.group_id = t.group_id
				 AND tg.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
			 
		if ($user_blog === TRUE)
		{
			$sql .= " AND t.group_id = '".$SESS->userdata['tmpl_group_id']."'";
		}
		else
		{
			$sql .= " AND is_user_blog = 'n'";
		}

		if ($SESS->userdata['group_id'] != 1)
		{
			$sql .= " AND t.group_id IN (";
		
			foreach ($SESS->userdata['assigned_template_groups'] as $key => $val)
			{
				$sql .= "'$key',";
			}
			
			$sql = substr($sql, 0, -1).")";
		}
		
		$sql .= " ORDER BY tg.group_order, t.group_id, t.template_name";  
			 
		$query = $DB->query($sql);
		
		/** -------------------------------------
    	/**  Nothing?
    	/** -------------------------------------*/
		
		if ($query->num_rows == 0)
		{
			$DSP->body .= $DSP->qdiv('alert', $LANG->line('no_templates_available'));
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=templates', $LANG->line('back')));
			return;
		}
		
		/** -------------------------------------
    	/**  Create Our MultiSelect Lists
    	/** -------------------------------------*/
		
		$current_group = 0;
		
		$groups		= "<select onchange='showHideTemplate(this);' name='template_groups' class='multiselect' size='10' multiple='multiple' style='width:160px'>";
		$templates	= $DSP->div('default', '', 'template_group_div_'.$query->row['group_id'], '', ($group_id == $query->row['group_id']) ? '' : 'style="display: none; padding:0;"').
					  $DSP->input_select_header('template_group_'.$query->row['group_id'].'[]', 'y', 8);
		
		foreach ($query->result as $row)
		{
			if ($row['group_id'] != $current_group)
			{
				$groups		.=	$DSP->input_select_option($row['group_id'], $REGX->form_prep($row['group_name']), ($group_id == $row['group_id']) ? 'y' : '');
				
				if ($current_group != 0)
				{
					$templates	.=	$DSP->input_select_footer().
									$DSP->div_c().
									$DSP->div('default', '', 'template_group_div_'.$row['group_id'], '', ($group_id == $row['group_id']) ? '' : 'style="display: none; padding:0;"').
									$DSP->input_select_header('template_group_'.$row['group_id'].'[]', 'y', 8);
				}
			}
			
			$templates .= $DSP->input_select_option($row['template_id'], $REGX->form_prep($row['template_name']), '');
			
			$current_group = $row['group_id'];
		}
		
		$groups		.=	$DSP->input_select_footer();
		$templates	.=	$DSP->input_select_footer().$DSP->div_c();
   
   		/** -------------------------------------
    	/**  Templates and Form
    	/** -------------------------------------*/

        $r .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_manager_prefs', 'name' => 'templateManagement', 'id' => 'templateManagement'));
	
		if ($message != '')
		{
			$r .= $DSP->table('tableBorder', '0', '', '100%')
			 	  .	$DSP->tr()
			 	  .		$DSP->table_qcell('tableCellOne', $DSP->qspan('success', $LANG->line('preferences_updated')))
			 	  .	$DSP->tr_c()
			 	  .$DSP->table_c();
		}
		
		$r .= $DSP->table('tableBorder', '0', '', '100%')
			 .$DSP->tr()
			 .$DSP->table_qcell('tableHeadingAlt', $LANG->line('template_groups'))
			 .$DSP->table_qcell('tableHeadingAlt', $LANG->line('selected_templates'))
			 .$DSP->td_c()
			 .$DSP->tr_c()
			 .$DSP->tr()
			 .$DSP->table_qcell('tableCellOne', $groups, '400px', 'top')
			 .$DSP->table_qcell('tableCellOne', $templates, '400px', 'top')
			 .$DSP->tr_c()
			 .$DSP->table_c();
			 
		/** -------------------------------------
    	/**  Preferences
    	/** -------------------------------------*/
		
		$r .= BR.$DSP->table('tableBorder', '0', '', '100%')
			 .$DSP->tr()
			 .$DSP->table_qcell('tableHeadingAlt', $LANG->line('type'));
			 
			 
		$r .= $DSP->td('tableHeadingAlt', '', '1').$LANG->line('cache_enable').$DSP->td_c();
		
		$r .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('refresh_interval'));
			 
        if ($SESS->userdata['group_id'] == 1)
        {                      			 
			$r .= $DSP->td('tableHeadingAlt').$LANG->line('enable_php').$DSP->td_c();
			$r .= $DSP->td('tableHeadingAlt').$LANG->line('parse_stage').$DSP->td_c();
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
        	$r .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('save_template_file'));
		}
		
		$r .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('hit_counter'));			
		
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();	

		$r .= $DSP->td('tableCellOne', '', '1').NBS.$DSP->td_c()
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', ''))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('refresh_in_minutes')));
			 
		if ($SESS->userdata['group_id'] == 1)
		{   
			$r .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', ''))
				 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', ''));
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
        	$r .= $DSP->td('tableCellOne', '', '1').NBS.$DSP->td_c();
		}
			 
		$r .= $DSP->td('tableCellOne', '', '1').NBS.$DSP->td_c();
			 

		$r .= $DSP->tr_c();
        
        $style = 'tableCellOne';
		  
		$r .= $DSP->tr();
		
		$t  = $DSP->input_select_header('template_type');		
		$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'), 1);
		$t .= $DSP->input_select_option('css', $LANG->line('css_stylesheet'));		
		$t .= $DSP->input_select_option('js', $LANG->line('js'));
		$t .= $DSP->input_select_option('rss', $LANG->line('rss'));
		$t .= $DSP->input_select_option('static', $LANG->line('static'));
		$t .= $DSP->input_select_option('webpage', $LANG->line('webpage'));
		$t .= $DSP->input_select_option('xml', $LANG->line('xml'));
		$t .= $DSP->input_select_footer();        
		$r .= $DSP->table_qcell($style, $t);
		
		$t  = $DSP->input_select_header('cache');		
		$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'),'');
		$t .= $DSP->input_select_option('y', $LANG->line('yes'));	
		$t .= $DSP->input_select_option('n', $LANG->line('no'));	
		$t .= $DSP->input_select_footer();
								
		$r .= $DSP->table_qcell($style, $t);
		

		$r .= $DSP->table_qcell($style, $DSP->input_text('refresh', '0', '8', '6', 'input', '50px'));
																	  
		 
		if ($SESS->userdata['group_id'] == 1)
		{   
			$t  = $DSP->input_select_header('allow_php');		
			$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'),'');
			$t .= $DSP->input_select_option('y', $LANG->line('yes'));	
			$t .= $DSP->input_select_option('n', $LANG->line('no'));	
			$t .= $DSP->input_select_footer();
		
			$r .= $DSP->table_qcell($style, $t);
			
			$t  = $DSP->input_select_header('php_parse_location');		
			$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'),'');
			$t .= $DSP->input_select_option('i', $LANG->line('input'));	
			$t .= $DSP->input_select_option('o', $LANG->line('output'));	
			$t .= $DSP->input_select_footer();
			
			$r .= $DSP->table_qcell($style, $t);
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
        	$t  = $DSP->input_select_header('save_template_file');		
			$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'),'');
			$t .= $DSP->input_select_option('y', $LANG->line('yes'));	
			$t .= $DSP->input_select_option('n', $LANG->line('no'));	
			$t .= $DSP->input_select_footer();
			
			$r .= $DSP->table_qcell($style, $t);
		}

		$r .= $DSP->table_qcell($style, $DSP->input_text('hits', '', '6', '13', 'input', '50px'));
 

		$r .=$DSP->tr_c();
					  
		$r .= $DSP->table_c();
			
		if ($SESS->userdata['group_id'] == 1)
		{   
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('security_warning')));
		}
		
		/** -------------------------------------
    	/**  Access
    	/** -------------------------------------*/

		$r .= BR.$DSP->table('tableBorder', '0', '', '100%').
			  $DSP->tr().
			  $DSP->td('tableHeadingAlt', '', 2).
			  $LANG->line('template_access').
			  $DSP->tr_c().
			  $DSP->tr().
			  $DSP->td('tableCellOne', '', '').
			  $DSP->qdiv('defaultBold', $LANG->line('member_group')).
			  $DSP->td_c().
			  $DSP->td('tableCellOne', '', '').
			  $DSP->qdiv('defaultBold', $LANG->line('can_view_template')).
			  $DSP->td_c().
			  $DSP->tr_c();
	
		$i = 0;
		
		$query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '1' ORDER BY group_title");
		$access_e = array();
		
		foreach ($query->result as $row)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
	
			$r .= $DSP->tr().
				  $DSP->td($style, '40%').
				  $row['group_title'].
				  $DSP->td_c().
				  $DSP->td($style, '60%');
				
			$r .= $LANG->line('yes').NBS.
				  $DSP->input_radio('access_'.$row['group_id'], 'y', '').$DSP->nbs(3);
				
			$r .= $LANG->line('no').NBS.
				  $DSP->input_radio('access_'.$row['group_id'], 'n', '').$DSP->nbs(3);
				  
			$r .= $LANG->line('do_not_change').NBS.
				  $DSP->input_radio('access_'.$row['group_id'], 'null', 1).$DSP->nbs(3);
			
			$r .= $DSP->td_c()
				 .$DSP->tr_c();
			
			$access_e[] = "access_{$row['group_id']}";
		}        
	
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $this->template_access_toggle($access_e);

		$r .= $DSP->tr().
			  $DSP->td($style, '40%').
			  $DSP->qdiv('defaultBold', $LANG->line('select_all')).
			  $DSP->td_c().
			  $DSP->td($style, '60%');

		$r .= $LANG->line('yes').NBS.
			  $DSP->input_radio('can_view', 'y', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $LANG->line('no').NBS.
			  $DSP->input_radio('can_view', 'n', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $LANG->line('do_not_change').NBS.
			  $DSP->input_radio('can_view', 'null', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3);
		
		$r .= $DSP->td_c().
			  $DSP->tr_c();
			
		$r .= $DSP->table_c(); 
		
		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('no_access_select_blurb'), 5);
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('no_access_instructions'));
		
		$sql = "SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
				FROM   exp_template_groups, exp_templates
				WHERE  exp_template_groups.group_id =  exp_templates.group_id
				AND    exp_template_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
				
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
				
		$r .=  $DSP->div()
			  .$DSP->input_select_header('no_auth_bounce')
			  .$DSP->input_select_option('null', $LANG->line('do_not_change'), '1');
				
		foreach ($query->result as $row)
		{
			$r .= $DSP->input_select_option($row['template_id'], $row['group_name'].'/'.$row['template_name'], '');
		}
		
		$r .= $DSP->input_select_footer().BR.BR;
		
		$t  = $DSP->input_select_header('enable_http_auth');		
		$t .= $DSP->input_select_option('null', $LANG->line('do_not_change'), 1);
		$t .= $DSP->input_select_option('y', $LANG->line('yes'));	
		$t .= $DSP->input_select_option('n', $LANG->line('no'));	
		$t .= $DSP->input_select_footer();
		
		$r .= $DSP->div('paddedTop');
		$r .= $DSP->heading($LANG->line('enable_http_authentication'), 5);
		$r .= $DSP->qdiv('itemWrapper', $t); 
		$r .= $DSP->div_c(); 
		$r .= $DSP->div_c(); 
		$r .= $DSP->div_c().BR; 
			 
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
             .$DSP->form_close();

        $DSP->body = $r;
	}
	/* END */
	
	
	/** -----------------------------
    /**  Template Preferences Manager - UPDATE
    /** -----------------------------*/
    
    function update_manager_prefs()
    {  
        global $IN, $DSP, $DB, $SESS, $LANG, $REGX, $OUT, $PREFS;
        
		if ( ! $DSP->allowed_group('can_admin_templates'))
		{
			return $DSP->no_access_message();
		}
		
        $user_blog = ($SESS->userdata['tmpl_group_id'] != 0) ? TRUE : FALSE;
		
		/** -------------------------------------
    	/**  Determine Valid Template Groups and Templates
    	/** -------------------------------------*/
              
        if ($SESS->userdata['group_id'] != 1 && (sizeof($SESS->userdata['assigned_template_groups']) == 0 OR $DSP->allowed_group('can_admin_templates') == FALSE))
        {
        	return $DSP->no_access_message();
        }
        
        $sql  = "SELECT t.template_id, t.group_id
				 FROM exp_template_groups tg , exp_templates t
				 WHERE tg.group_id = t.group_id 
				 AND tg.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
			 
		if ($user_blog === TRUE)
		{
			$sql .= " AND t.group_id = '".$SESS->userdata['tmpl_group_id']."'";
		}
		else
		{
			$sql .= " AND is_user_blog = 'n'";
		}
		
		if ($SESS->userdata['group_id'] != 1)
		{
			$sql .= " AND t.group_id IN (";
		
			foreach ($SESS->userdata['assigned_template_groups'] as $key => $val)
			{
				$sql .= "'$key',";
			}
			
			$sql = substr($sql, 0, -1).")";
		}
			 
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $DSP->no_access_message();
		}
		
		$templates = array();
		
		foreach($_POST as $key => $value)
		{
			if (substr($key, 0, strlen('template_group_')) == 'template_group_' && is_array($value))
			{
				foreach($value as $template)
				{
					$templates[] = $DB->escape_str($template);
				}
			}
		}
		
		if (sizeof($templates) == 0)
		{
			$OUT->show_user_error('submission', $LANG->line('no_templates_selected'));
		}
		
		/** -------------------------------------
    	/**  Template Preferences
    	/** -------------------------------------*/
		
		$data = array();
		
		if (in_array($_POST['template_type'], array('css', 'js', 'rss', 'static', 'webpage', 'xml')))
		{
			$data['template_type'] = $_POST['template_type'];
		}
		
		if ($_POST['cache'] == 'y' OR $_POST['cache'] == 'n')
		{
			$data['cache'] = $_POST['cache'];
			
			if ($_POST['refresh'] != '' && is_numeric($_POST['refresh']))
			{
				$data['refresh'] = $_POST['refresh'];
			}
		}
		
		if ($SESS->userdata['group_id'] == 1)
		{
			if ($_POST['allow_php'] == 'y' OR $_POST['allow_php'] == 'n')
			{
				$data['allow_php'] = $_POST['allow_php'];
				
				if ($_POST['php_parse_location'] == 'i' OR $_POST['php_parse_location'] == 'o')
				{
					$data['php_parse_location'] = $_POST['php_parse_location'];
				}
			}
		}
		
		if ($_POST['hits'] != '' && is_numeric($_POST['hits']))
		{
			$data['hits'] = $_POST['hits'];
		}
		
		if ($_POST['enable_http_auth'] == 'y' OR $_POST['enable_http_auth'] == 'n')
		{
			$data['enable_http_auth'] = $_POST['enable_http_auth'];
		}
		
		if ($_POST['no_auth_bounce'] != 'null')
		{
			$data['no_auth_bounce'] = $_POST['no_auth_bounce'];
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
		{
			if ($_POST['save_template_file'] != 'null')
			{
				$data['save_template_file'] = $_POST['save_template_file'];
			}
		}
		
		if (sizeof($data) > 0)
		{
			$DB->query($DB->update_string('exp_templates', $data, "template_id IN ('".implode("','", $templates)."')"));
		}
		
		/** -------------------------------------
    	/**  Template Access
    	/** -------------------------------------*/
    	
    	$yes = array();
    	$no  = array();
    	
    	$query = $DB->query("SELECT group_id FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '1' ORDER BY group_title");
    	
    	if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				if ( isset($_POST['access_'.$row['group_id']]))
				{
					if ($_POST['access_'.$row['group_id']] == 'y')
					{
						$yes[] = $row['group_id'];
					}
					elseif($_POST['access_'.$row['group_id']] == 'n')
					{
						$no[] = $row['group_id'];
					}
				}
			}
		}
		
		if ( ! empty($yes) OR ! empty($no))
		{			
			$access = array();
			
			if (sizeof($no) > 0)
			{
				foreach($templates as $template)
				{
					$access[$template] = $no;
				}
			}
			
			$query = $DB->query("SELECT * FROM exp_template_no_access WHERE template_id IN ('".implode("','", $templates)."')");
			
			if ($query->num_rows > 0)
			{
				foreach($query->result as $row)
				{
					if ( ! in_array($row['member_group'], $yes) && ! in_array($row['member_group'], $no))
					{
						$access[$row['template_id']][] = $row['member_group'];
					}
				}
			}
			
			$query = $DB->query("DELETE FROM exp_template_no_access WHERE template_id IN ('".implode("','", $templates)."')");
			
			foreach($access as $template => $groups)
			{
				if ( empty($groups)) continue;
				
				foreach($groups as $group)
				{
					$DB->query($DB->insert_string('exp_template_no_access', array('template_id' => $template, 'member_group' => $group)));
				}
			}
		}
		
		$this->template_prefs_manager('y');
	}
	/* END */



    /** -----------------------------
    /**  Verify access privileges
    /** -----------------------------*/

    function template_access_privs($data = '')
    {
    	global $SESS, $DB;
    	
    	// If the user is a Super Admin, return true
    	
		if ($SESS->userdata['group_id'] == 1)
		{
    		return TRUE;
		}    	
    
    	$template_id = '';
    	$group_id	 = '';    
    
    	if (is_array($data))
    	{
    		if (isset($data['template_id']))
    		{
    			$template_id = $data['template_id'];
    		}
    	
    		if (isset($data['group_id']))
    		{
    			$group_id = $data['group_id'];
    		}
    	}
    
    
        if ($group_id == '')
        {
        	if ($template_id == '')
        	{
        		return FALSE;
        	}
        	else
        	{
           		$query = $DB->query("SELECT group_id, template_name FROM exp_templates WHERE template_id = '".$DB->escape_str($template_id)."'");
           		
           		$group_id = $query->row['group_id'];
            }
        }
                
                
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
			$access = FALSE;
			
			foreach ($SESS->userdata['assigned_template_groups'] as $key => $val)
			{
				if ($group_id == $key)
				{
					$access = TRUE;
					break;
				}
			}
		
			if ($access == FALSE)
			{
				return FALSE;
			}
        }
        else
        {
			if ($group_id != $SESS->userdata['tmpl_group_id'] )
			{
				return FALSE;
			}
        }        

		return TRUE;
    }
    /* END */


    /** -----------------------------
    /**  Template Preferences
    /** -----------------------------*/
    
    function edit_preferences($group_id = '')
    {  
        global $IN, $DSP, $DB, $SESS, $LANG, $PREFS;
        
		if ( ! $DSP->allowed_group('can_admin_templates'))
		{
			return $DSP->no_access_message();
		}
                
        if ($group_id == '')
        {
            if ( ! $group_id = $IN->GBL('id'))
            {
                return false;
            }
            
            $message = '';
        }
        else
        {
            $message = $DSP->qdiv('success', $LANG->line('preferences_updated'));
        }
        
        if ( ! is_numeric($group_id))
        {
        	return FALSE;
        }
        
        $query = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '$group_id'");
    
    	if ($query->num_rows == 0)
    	{
    		return FALSE;
    	}
    	    
        $DSP->title  = $LANG->line('template_preferences');        
        $DSP->crumb  = $LANG->line('template_preferences');   

        $r  = $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_template_prefs'))
             .$DSP->input_hidden('group_id', $group_id);
             
             
		$r .= $DSP->qdiv('tableHeading', $LANG->line('template_preferences').NBS.NBS.'('.$query->row['group_name'].')');
		
		if ($message != '')
		{
			$r .= $DSP->table('tableBorder', '0', '', '100%')
			 	  .	$DSP->tr()
			 	  .		$DSP->table_qcell('tableCellOne', $message)
			 	  .	$DSP->tr_c()
			 	  .$DSP->table_c();
		}
		
		$r .= $DSP->table('tableBorder', '0', '', '100%')
			 .$DSP->tr()
			 .$DSP->table_qcell('tableHeadingAlt', $LANG->line('name_of_template'))
			 .$DSP->table_qcell('tableHeadingAlt', $LANG->line('type'));
			 
			 
		$r .= $DSP->td('tableHeadingAlt', '', '2').$LANG->line('cache_enable').$DSP->td_c();
		
		$r .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('refresh_interval'));
			 
        if ($SESS->userdata['group_id'] == 1)
        {                      			 
			$r .= $DSP->td('tableHeadingAlt', '', '2').$LANG->line('enable_php').$DSP->td_c();
			$r .= $DSP->td('tableHeadingAlt', '', '2').$LANG->line('parse_stage').$DSP->td_c();
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
        	$r .= $DSP->td('tableHeadingAlt', '', '2').$LANG->line('save_template_file').$DSP->td_c();
		}
		
		$r .= $DSP->table_qcell('tableHeadingAlt', $LANG->line('hit_counter'));			
		
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();	

		$r .= $DSP->td('tableCellOne', '', '2').NBS.$DSP->td_c()
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('yes')))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('no')))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('refresh_in_minutes')));
			 
		if ($SESS->userdata['group_id'] == 1)
		{   
			$r .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('yes')))
				 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('no')))
				 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('input')))
				 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('output')));
		}
		
		if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
			$r .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('yes')))
			 	  .
			 	  $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('no')));
		}
			 
		$r .= $DSP->td('tableCellOne', '', '1').NBS.$DSP->td_c();
			 

		$r .= $DSP->tr_c();

		$i = 0;				
        
        // Fetch template preferences
        
        $query = $DB->query("SELECT template_id, template_name, template_type, group_id, save_template_file, allow_php, php_parse_location, no_auth_bounce, cache, refresh, hits FROM exp_templates WHERE group_id = '$group_id' ORDER BY template_name");
      		
		foreach ($query->result as $row)
		{     
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$id = $row['template_id'].'__';
		  
			$r .= $DSP->tr();
			
			$old = $DSP->input_hidden($id.'old_name', $row['template_name']);
			
			if ($row['template_name'] == 'index')
			{
				$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $row['template_name']).$old);
			}
			else
			{
				$r .= $DSP->table_qcell($style, $DSP->input_text($id.'template_name', $row['template_name'], '15', '50', 'input', '110px').$old);
			}
			
			$t  = $DSP->input_select_header($id.'template_type');		
			$t .= $DSP->input_select_option('css', $LANG->line('css_stylesheet'), ($row['template_type'] == 'css') ? 1 : '');		
			$t .= $DSP->input_select_option('js', $LANG->line('js'), ($row['template_type'] == 'js') ? 1 : '');
			$t .= $DSP->input_select_option('rss', $LANG->line('rss'), ($row['template_type'] == 'rss') ? 1 : '');
			$t .= $DSP->input_select_option('static', $LANG->line('static'), ($row['template_type'] == 'static') ? 1 : '');
			$t .= $DSP->input_select_option('webpage', $LANG->line('webpage'), ($row['template_type'] == 'webpage') ? 1 : '');
			$t .= $DSP->input_select_option('xml', $LANG->line('xml'), ($row['template_type'] == 'xml') ? 1 : '');
			$t .= $DSP->input_select_footer();        
			$r .= $DSP->table_qcell($style, $t);
									
			$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'cache', 'y', ($row['cache'] == 'y') ? 1 : ''));
			$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'cache', 'n', ($row['cache'] == 'n') ? 1 : ''));
			

			$r .= $DSP->table_qcell($style, $DSP->input_text($id.'refresh', $row['refresh'], '8', '6', 'input', '50px'));
							  											  
			 
			if ($SESS->userdata['group_id'] == 1)
			{   
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'allow_php', 'y', ($row['allow_php'] == 'y') ? 1 : ''));
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'allow_php', 'n', ($row['allow_php'] == 'n') ? 1 : ''));
				
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'php_parse_location', 'i', ($row['php_parse_location'] == 'i') ? 1 : ''));
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'php_parse_location', 'o', ($row['php_parse_location'] == 'o') ? 1 : ''));
			}
			
			if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
			{
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'save_template_file', 'y', ($row['save_template_file'] == 'y') ? 1 : ''));
				$r .= $DSP->table_qcell($style, $DSP->input_radio($id.'save_template_file', 'n', ($row['save_template_file'] == 'n') ? 1 : ''));
			}
	
			$r .= $DSP->table_qcell($style, $DSP->input_text($id.'hits', $row['hits'], '6', '13', 'input', '50px'));
	 

			$r .=$DSP->tr_c();				 
		}
					  
		$r .= $DSP->table_c();
			
		if ($SESS->userdata['group_id'] == 1)
		{   
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('security_warning')));
		} 
			 
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')))
             .$DSP->form_close();

        $DSP->body = $r;
	}
	/* END */


 
	/** -------------------------------
    /**  Update Template Preferences
    /** -------------------------------*/
    
    function update_template_prefs()
    {
        global $IN, $DSP, $DB, $SESS, $LANG, $PREFS;
            
        
        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return false;
        }
        
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
        else
        {
            if ($group_id != $SESS->userdata['tmpl_group_id'] )
            {
                return $DSP->no_access_message();
            }
        }
        
        $idx = array();
        
        foreach ($_POST as $k => $val)
        {
        	if ( ! stristr($k, "__"))
				continue;
				
			$temp = explode("__", $k);

        	$id = $temp['0'];
        	$idx[] = $temp['0'];
        	        
        	if (isset($_POST[$id.'__template_name']))
        	{
				if ($_POST[$id.'__template_name'] == '')
				{
					return $DSP->error_message($LANG->line('missing_name'));
				}
				if ( ! preg_match("#^[a-zA-Z0-9_\.-]+$#i", $_POST[$id.'__template_name']))
				{
					return $DSP->error_message($LANG->line('illegal_characters'));
				}
				
				if (in_array($_POST[$id.'__template_name'], $this->reserved_names))
				{
					return $DSP->error_message($LANG->line('reserved_name'));
				}
				
				if ($_POST[$id.'__template_name'] != $_POST[$id.'__old_name'])
				{
					$query = $DB->query("SELECT COUNT(*) AS count FROM exp_templates WHERE template_name='".$DB->escape_str($_POST[$id.'__template_name'])."' AND group_id = '$group_id'");
					
					if ($query->row['count'] > 0)
					{
						return $DSP->error_message($LANG->line('template_name_taken'));
					}  					
				}
            }
        }
                
       foreach ($idx as $id)
       {
        	$data = array();
            
        	if (isset($_POST[$id.'__template_name']))
        	{
				$data['template_name'] = $_POST[$id.'__template_name'];	
			}
            
			$data['cache'] = $_POST[$id.'__cache'];
			$data['refresh'] = ( ! is_numeric($_POST[$id.'__refresh'])) ? '1' : $_POST[$id.'__refresh'];
			$data['hits'] = ( ! is_numeric($_POST[$id.'__hits'])) ? '0' : $_POST[$id.'__hits'];
			$data['template_type'] = $_POST[$id.'__template_type'];
			
			if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
			{
				$data['save_template_file'] = $_POST[$id.'__save_template_file'];
			}
			
			if ($SESS->userdata['group_id'] == 1)
			{   
				$data['php_parse_location'] = $_POST[$id.'__php_parse_location'];
				$data['allow_php'] = (isset($_POST[$id.'__allow_php']) AND $_POST[$id.'__allow_php'] == 'y' AND $SESS->userdata['group_id'] == 1) ? 'y' : 'n';
			}

			$DB->query($DB->update_string('exp_templates', $data, "template_id = '$id'"));
        }
                
        return $this->edit_preferences($group_id);
    }    
    /* END */



    /** -----------------------------
    /**  Template default page
    /** -----------------------------*/
    
    function template_manager()
    {  
        global $IN, $DSP, $DB, $PREFS, $FNS, $SESS, $LANG, $REGX, $EXT;
        
        // -------------------------------------------
        // 'template_manager_start' hook.
        //  - Allows complete rewrite of Templates page.
        //
        	$edata = $EXT->call_extension('template_manager_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        $user_blog = FALSE;
        
		$DSP->crumbline = TRUE;

        if ($SESS->userdata['tmpl_group_id'] != 0)
        {
            $user_blog = TRUE;
        }

        switch ($IN->GBL('MSG'))
        {
            case '01' : $message = $LANG->line('template_group_created');
                break;
            case '02' : $message = $LANG->line('template_group_updated');
                break;
            case '03' : $message = $LANG->line('template_group_deleted');
                break;
            case '04' : $message = $LANG->line('template_created');
                break;
            case '05' : $message = $LANG->line('template_deleted');
                break;
            default   : $message = "";
                break;
        }        
        
        $DSP->title  = $LANG->line('design');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=templates', $LANG->line('design')).$DSP->crumb_item($LANG->line('template_management'));   

        if ($user_blog === FALSE AND $DSP->allowed_group('can_admin_templates'))
        {
			$DSP->right_crumb($LANG->line('create_new_template_group'), BASE.AMP.'C=templates'.AMP.'M=new_tg_form');
        }

		ob_start();
		?>


<script type="text/javascript">

function showHideTemplate(htmlObj)
{
	if (isNaN(htmlObj.value) || htmlObj.value == '') return;
	
	for (var g = 0; g < htmlObj.options.length; g++)
	{
		if (document.getElementById('extText' + htmlObj.options[g].value))
		{
			extTextDiv = document.getElementById('extText' + htmlObj.options[g].value);
			
			if (htmlObj.options[g].selected == true)
			{
				if (extTextDiv.style.display != 'block')
				{
					extTextDiv.style.display = "block";
				}
			}
			else if(extTextDiv.style.display != 'none')
			{
				extTextDiv.style.display = "none";
			}
		}
	}
}

</script>

<?php

		$r = ob_get_contents();
		ob_end_clean();            
            
        $r .= $DSP->table('', '', '', '97%')
             .$DSP->tr()
             .$DSP->td('', '', '', '', 'top')
             .$DSP->heading($LANG->line('template_management'));
             
        if ($message != '')
        {
            $r .= $DSP->qdiv('success', $message);
        }
        
        if ($IN->GBL('keywords', 'POST') !== FALSE && trim($IN->GBL('keywords', 'POST')) != '')
        {
        	$r .= $DSP->qspan('defaultBold', $LANG->line('search_terms')).NBS.NBS.$DSP->qspan('success', stripslashes($IN->GBL('keywords', 'POST')));
        }

        $r .= $DSP->td_c()
             .$DSP->td('', '', '', '', 'top');
             
		$r .= $DSP->div('defaultRight');
		
        if ($DSP->allowed_group('can_admin_templates') || $user_blog !== FALSE)
        {
            $r .= $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=global_variables', '<b>'.$LANG->line('global_variables').'</b>');
        }
         
        if ($user_blog === FALSE AND $DSP->allowed_group('can_admin_templates'))
        {
            $r .= NBS.NBS.'|'.NBS.NBS.$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=config_mgr'.AMP.'P=template_cfg'.AMP.'class_override=templates', '<b>'.$LANG->line('global_template_preferences').'</b>');
            $r .= NBS.NBS.'|'.NBS.NBS.$DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=edit_tg_order', '<b>'.$LANG->line('edit_template_group_order').'</b>');
            $r .= NBS.NBS.'|'.NBS.NBS.$DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=template_prefs_manager', '<b>'.$LANG->line('template_preferences_manager').'</b>');
        }
        
        $r .= $DSP->div_c();
        
        $r .= $DSP->td_c()
             .$DSP->tr_c()
             .$DSP->table_c();
		 
		$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
		$sitepath = $FNS->fetch_site_index(0, 0).$qs.'URL='.$FNS->fetch_site_index();
                
        $sitepath = rtrim($sitepath, '/').'/';
              
        if ($SESS->userdata['group_id'] != 1 && (sizeof($SESS->userdata['assigned_template_groups']) == 0 OR $DSP->allowed_group('can_admin_templates') == FALSE))
        {
        	$r .= $DSP->qdiv('', $LANG->line('no_templates_assigned'));
        	return $DSP->body = $r;
        }
        else
        {
			$sql  = "SELECT tg.group_id, tg.group_name, tg.is_site_default, 
							t.template_id, t.template_name, t.template_type, t.hits, t.enable_http_auth
					 FROM exp_template_groups tg , exp_templates t
					 WHERE tg.group_id = t.group_id 
					 AND tg.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
				 
			if ($user_blog === TRUE)
			{
				$sql .= " AND t.group_id = '".$SESS->userdata['tmpl_group_id']."'";
			}
			else
			{
				$sql .= " AND is_user_blog = 'n'";
			}
			
			if ($SESS->userdata['group_id'] != 1)
			{
				$sql .= " AND t.group_id IN (";
			
				foreach ($SESS->userdata['assigned_template_groups'] as $key => $val)
				{
					$sql .= "'$key',";
				}
				
				$sql = substr($sql, 0, -1).")";
			}
			
			if ($IN->GBL('keywords', 'POST') !== FALSE && trim($IN->GBL('keywords', 'POST')) != '')
			{
				$keywords = $REGX->keyword_clean(stripslashes($IN->GBL('keywords', 'POST')));
				
				if (trim($keywords) == '')
				{
					$DSP->body .= $DSP->qdiv('alert', $LANG->line('no_results'));
					$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=templates', $LANG->line('back')));
					return;
				}
				
				$terms = array();
			
				if (preg_match_all("/\-*\"(.*?)\"/", $keywords, $matches))
				{
					for($m=0; $m < sizeof($matches['1']); $m++)
					{
						$terms[] = trim(str_replace('"','',$matches['0'][$m]));
						$keywords = str_replace($matches['0'][$m],'', $keywords);
					}    
				}
				
				if (trim($keywords) != '')
				{
					$terms  = array_merge($terms, preg_split("/\s+/", trim($keywords)));
				}
				
				rsort($terms);
				$not_and = (sizeof($terms) > 2) ? ') AND (' : 'AND';
				$criteria = 'AND';
				
				$mysql_function	= (substr($terms['0'], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';    
				$search_term	= (substr($terms['0'], 0,1) == '-') ? substr($terms['0'], 1) : $terms['0'];
				
				// We have two parentheses in the beginning in case
				// there are any NOT LIKE's being used
				$sql .= "\nAND (t.template_data $mysql_function '%".$DB->escape_like_str($search_term)."%' ";
    			
				for ($i=1; $i < sizeof($terms); $i++) 
				{
					if (trim($terms[$i]) == '') continue;
					$mysql_criteria	= ($mysql_function == 'NOT LIKE' OR substr($terms[$i], 0,1) == '-') ? $not_and : $criteria;
					$mysql_function	= (substr($terms[$i], 0,1) == '-') ? 'NOT LIKE' : 'LIKE';
					$search_term	= (substr($terms[$i], 0,1) == '-') ? substr($terms[$i], 1) : $terms[$i];
					
					$sql .= "$mysql_criteria t.template_data $mysql_function '%".$DB->escape_like_str($search_term)."%' ";
				}
				
				$sql .= ") \n";
			}
			
			$sql .= " ORDER BY tg.group_order, t.group_id, t.template_name";  
				 
			$query = $DB->query($sql);
			
			if ($query->num_rows == 0)
			{
				if (isset($keywords))
				{
					$DSP->body .= $DSP->qdiv('alert', $LANG->line(isset($keywords) ? 'no_results' : 'no_templates_available'));
					$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=templates', $LANG->line('back')));
				}
				else
				{
					$DSP->body .= $DSP->qdiv('alert', $LANG->line('no_templates_available'));
				}
				return;
			}
			
			$r .= $DSP->table_open(array('width' => '99%', 'cellpadding' => '1'))
					.$DSP->tr()
						."<td valign='top' style='width:180px; padding-top:1px'>"
							.$DSP->div('itemWrapper')
								.$DSP->div('templateEditBox')
									.$DSP->qdiv('tableHeadingAlt', $LANG->line('choose_group'))
									.$DSP->div('templatePrefBox')
									.$DSP->div('defaultCenter')
										."<select onchange='showHideTemplate(this);' name='template_groups' class='multiselect' size='15' multiple='multiple' style='width:160px'>";
			$current_group = 0;
			foreach($query->result as $e => $row)
			{
				if ($row['group_id'] == $current_group) continue;
				$current_group = $row['group_id'];
				
				if (isset($_GET['tgpref']) && is_numeric($_GET['tgpref']) && $_GET['tgpref'] == $row['group_id'])
				{
					if ($row['is_site_default'] == 'y')
					{
						$r .= $DSP->input_select_option($row['group_id'], '* '.$REGX->form_prep($row['group_name']), 'y', "class='highlight_alt2'");
					}
					else
					{
						$r .= $DSP->input_select_option($row['group_id'], $REGX->form_prep($row['group_name']), 'y');
					}
				}
				else
				{
					if ($row['is_site_default'] == 'y')
					{
						$r .= $DSP->input_select_option($row['group_id'], '* '.$REGX->form_prep($row['group_name']), ($e > 0 OR isset($_GET['tgpref'])) ? '' : 'y', "class='highlight_alt2'");
					}
					else
					{
						$r .= $DSP->input_select_option($row['group_id'], $REGX->form_prep($row['group_name']), ($e > 0 OR isset($_GET['tgpref'])) ? '' : 'y');
					}
				}
				
				if ($row['is_site_default'] == 'y')
				{
					$default_group = $row['group_name'];
				}
			}
			
			$default_text = '';
			
			if (isset($default_group))
			{
				$default_text = $DSP->div('defaultCenter').
								$DSP->qspan('defaultBold', $LANG->line('default_template_group')).NBS.
								$default_group.
								$DSP->div_c();
			}
			
			$r .= $DSP->input_select_footer().
				  $default_text.
				  $DSP->div_c().
				  $DSP->div_c().
				  $DSP->div_c().
				  $DSP->div_c().
				  $DSP->qdiv('tableHeadingAlt', $LANG->line('search'))
						.$DSP->div('profileMenuInner')
						.	$DSP->form_open(array('action' => 'C=templates'))
						.		$DSP->input_text('keywords', '', '20', '120', 'input', '100%')
						.		$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultRight', $DSP->input_submit($LANG->line('search'))))
						.	$DSP->form_close()
						.$DSP->div_c().
				  $DSP->td_c().
				  $DSP->table_qcell('', '', '8px').
				  $DSP->td('', '', '', '', 'top');
				
			$x = 1;
			$j = 1;
			
			$out = '';
			$current_group = 0;
			
			$t = '';
			
			foreach ($query->result as $row)
			{
				if ($row['group_id'] != $current_group)
				{
					if ($current_group != 0)
					{
						$t .= $DSP->table_c();
			
						$t .= $DSP->td_c()   
							 .$DSP->tr_c()      
							 .$DSP->table_c();      
		
						if ($user_blog === FALSE AND $reqflag == TRUE)
						{
							$t .= $DSP->qdiv('itemWrapper', $DSP->required($LANG->line('default_site_page')));
						}
						
						if (isset($_GET['tgpref']) && is_numeric($_GET['tgpref']) && $_GET['tgpref'] == $current_group)
						{
							$r .= '<div id="extText'.$current_group.'"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
						}
						elseif ( ! isset($_GET['tgpref']) && $query->row['group_id'] == $current_group)
						{
							$r .= '<div id="extText'.$current_group.'"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
						}
						else
						{
							$r .= '<div id="extText'.$current_group.'" style="display: none; padding:0;"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
						}
						
						$t = '';
						
						$x++;
					}				
				
					$template_group  = $row['group_name'];
					$is_site_default = $row['is_site_default'];
				
					$t .= $DSP->table('', '', '', '100%')
						 .$DSP->tr()
						 .$DSP->td('templateEditBox', '20%', '', '', 'top');
	
					$t .= "<div class='tableHeadingAlt'>".NBS.'<b>'.$template_group."</b></div>";
					
					$t .= $DSP->table('', '', '', '100%')
						 .$DSP->tr()
						 .$DSP->td('templatePrefBox', '', '', '', 'top');
						 
					$t .= $DSP->div('templateprefpad');
									
					$t .= $DSP->div('leftPad');
						 
					if ($DSP->allowed_group('can_admin_templates'))
					{
						$t .= $DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=edit_preferences'.AMP.'id='.$row['group_id'].AMP.'tgpref='.$row['group_id'], $LANG->line('preferences')));
					}
						 
					$t .= $DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=new_templ_form'.AMP.'id='.$row['group_id'].AMP.'tgpref='.$row['group_id'], $LANG->line('create_new_template')));
		   
					if ($user_blog === FALSE AND $DSP->allowed_group('can_admin_templates'))
					{
						$t .= $DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=edit_tg_form'.AMP.'id='.$row['group_id'].AMP.'tgpref='.$row['group_id'], $LANG->line('edit_template_group')))
							 .$DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=tg_del_conf'.AMP.'id='.$row['group_id'].AMP.'tgpref='.$row['group_id'], $LANG->line('delete_template_group')));
					}
					
					// TEMPLATE EXPORT LINK
					$t .= $DSP->qdiv('itemWrapper',  $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=export_tmpl'.AMP.'id='.$row['group_id'].AMP.'tgpref='.$row['group_id'], $LANG->line('export_templates')));
					
					$t .= $DSP->div_c();
					$t .= $DSP->div_c();
						 
					$t .= $DSP->td_c()
						 .$DSP->tr_c()
						 .$DSP->table_c();
		
					$t .= $DSP->td_c()
						 .$DSP->td('defaultSmall', '1%').NBS;
	
					$t .= $DSP->td_c()
						 .$DSP->td('templateEditBox', '79%', '', '', 'top');
	
								
					$t .= $DSP->table('', '0', '', '100%')
						 .$DSP->tr()
						 .$DSP->table_qcell('tableHeading', $LANG->line('template_name').' / '.$LANG->line('edit'), '40%')
						 .$DSP->table_qcell('tableHeading', $LANG->line('hits'), '15%')
						 .$DSP->table_qcell('tableHeading', $LANG->line('view'), '15%')
						 .$DSP->table_qcell('tableHeading', $LANG->line('access'), '15%')
						 .$DSP->table_qcell('tableHeading', $LANG->line('delete'), '15%')
						 .$DSP->tr_c();
						 
					$i = 0;
					$reqflag = FALSE;
				}
				
				$current_group = $row['group_id'];
				
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				  
				$t .= $DSP->tr();
				
				$default = ($is_site_default == 'y' AND $row['template_name'] == 'index') ? $DSP->required() : '';
				
				$viewurl = $sitepath;

				if ($row['template_type'] == 'css')
				{
					$viewurl  = substr($viewurl, 0, -1);
					$viewurl .= $qs.$template_group.'/'.$row['template_name'].'/';
				}
				else
				{
					$viewurl .= $template_group.'/'.$row['template_name'].'/';
				}
				
				$img_type = ($row['template_name'] == 'index') ? 'index' : $row['template_type'];

				/* -------------------------------------------
				/*	Hidden Configuration Variable
				/*	- hidden_template_indicator => '.' 
					The character(s) used to designate a template as "hidden"
				/* -------------------------------------------*/
				
				$hidden_indicator = ($PREFS->ini('hidden_template_indicator') === FALSE) ? '.' : $PREFS->ini('hidden_template_indicator');			
				$hidden = (substr($row['template_name'], 0, 1) == $hidden_indicator) ? '_hidden' : '';
				
				$edit_img = "<img src='".PATH_CP_IMG."{$img_type}_icon{$hidden}.png' border='0' width='16' height='16' alt='".$LANG->line('view')."' />";
				$edit_url = BASE.AMP.'C=templates'.AMP.'M=edit_template'.AMP.'id='.$row['template_id'].AMP.'tgpref='.$row['group_id'];
				
				$protected = ($row['enable_http_auth'] == 'y') ? '&nbsp;&nbsp;<img src="'.PATH_CP_IMG.'key.gif" border="0"  width="12" height="12" alt="'.$LANG->line('http_auth_protected').'" title="'.$LANG->line('http_auth_protected').'" />' : '';
				$t .= $DSP->table_qcell($style, $DSP->anchor($edit_url, $edit_img).NBS.NBS.NBS.$DSP->anchor($edit_url, $default.'<b>'.$row['template_name'].'</b>').$protected);
				
				$t .= $DSP->table_qcell($style, $row['hits']);

				$t .= $DSP->table_qcell($style, $DSP->pagepop($viewurl, $LANG->line('view')));

				$key_url = BASE.AMP.'C=templates'.AMP.'M=template_access'.AMP.'id='.$row['template_id'].AMP.'tgpref='.$row['group_id'];
				
				$t .= $DSP->table_qcell($style, $DSP->anchor($key_url, $LANG->line('access')));
					
				$del_url = BASE.AMP.'C=templates'.AMP.'M=tmpl_del_conf'.AMP.'id='.$row['template_id'].AMP.'tgpref='.$row['group_id'];
				
				$delete =  ($row['template_name'] == 'index') ? '--' : $DSP->anchor($del_url, $LANG->line('delete'));

				$t .= $DSP->table_qcell($style, $delete)
					 .$DSP->tr_c();
					 
				if ($default != '' AND $reqflag == FALSE)
					$reqflag = TRUE;
			}			
		}


		$t .= $DSP->table_c();


		$t .= $DSP->td_c()   
			 .$DSP->tr_c()      
			 .$DSP->table_c();      

		if ($user_blog === FALSE AND $reqflag == TRUE)
		{
			$t .= $DSP->qdiv('itemWrapper', $DSP->required($LANG->line('default_site_page')));
		}
		
		if (isset($_GET['tgpref']) && is_numeric($_GET['tgpref']) && $_GET['tgpref'] == $row['group_id'])
		{
			$r .= '<div id="extText'.$row['group_id'].'"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
		}
		elseif ( ! isset($_GET['tgpref']) && $query->row['group_id'] == $row['group_id'])
		{
			$r .= '<div id="extText'.$row['group_id'].'"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
		}
		else
		{
			$r .= '<div id="extText'.$row['group_id'].'" style="display: none; padding:0;"><div class="itemWrapper">'.$t.'</div></div>'.NL.NL;
		}
		
		$x++;
		
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_close();
        
        $DSP->body = $r;        
    }
    /* END */
    
    
  
    
    /** ---------------------------------
    /**  New/Edit Template Group Form
    /** ---------------------------------*/

    function edit_template_group_form()
    {
        global $DSP, $IN, $DB, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
        
        $edit            = FALSE;
        $group_id        = '';
        $group_name      = '';
        $group_order     = '';
        $is_site_default = '';
        
                
        if ($group_id = $IN->GBL('id'))
        {
            $edit = TRUE;
            
            if ( ! is_numeric($group_id))
            {
            	return false;
            }
            
            $query = $DB->query("SELECT group_id, group_name, is_site_default FROM exp_template_groups WHERE group_id = '$group_id'");
            
            foreach ($query->row as $key => $val)
            {
                $$key = $val;
            }
        }
        
        
        $title = ($edit == FALSE) ? $LANG->line('new_template_group_form') : $LANG->line('edit_template_group_form');
                
        // Build the output
        
        $DSP->title = $title;
        $DSP->crumb = $title;      
        
        $DSP->body = $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_tg'));
     
        if ($edit == TRUE)
        {
            $DSP->body .= $DSP->input_hidden('group_id', $group_id);
            $DSP->body .= $DSP->input_hidden('old_name', $group_name);
        }
        
        $DSP->body .= $DSP->qdiv('tableHeading', $title);
                
        $DSP->body .=  $DSP->div('box').$DSP->div('paddedWrapper')
                      .$DSP->qdiv('itemWrapper', '<b>'.$LANG->line('name_of_template_group', 'group_name').'</b>')
                      .$DSP->qdiv('itemWrapper', $LANG->line('template_group_instructions'))
                      .$DSP->qdiv('itemWrapper', $LANG->line('undersores_allowed'))
                      .$DSP->qdiv('itemWrapper', $DSP->input_text('group_name', $group_name, '20', '50', 'input', '300px'))
                      .$DSP->div_c();
              
        
        if ($edit == FALSE)
        {
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('duplicate_existing_group').BR)); 
        
			$sql = "SELECT group_name, group_id, site_label
					FROM   exp_template_groups, exp_sites 
					WHERE  exp_sites.site_id = exp_template_groups.site_id ";
					
			if ($PREFS->ini('multiple_sites_enabled') !== 'y')
			{
				$sql .= "AND exp_template_groups.site_id = '1' ";
			}		
			 
			if (USER_BLOG == TRUE)
			{
				$sql .= "AND exp_template_groups.group_id = '".$SESS->userdata['tmpl_group_id']."' ";
			}
			else
			{
				$sql .= "AND exp_template_groups.is_user_blog = 'n' ";
			}
					
			$sql .= " ORDER BY group_name";
					
			$query = $DB->query($sql);
					
			$DSP->body .= $DSP->input_select_header('duplicate_group');
			
			$DSP->body .= $DSP->input_select_option('false', $LANG->line('do_not_duplicate_group'));
					
			foreach ($query->result as $row)
			{
				$DSP->body .= $DSP->input_select_option($row['group_id'], ($PREFS->ini('multiple_sites_enabled') == 'y') ? $row['site_label'].' - '.$row['group_name'] : $row['group_name']);
			}
			
			$DSP->body .= $DSP->input_select_footer();			
        }
        
        $selected = ($is_site_default == 'y') ? 1 : '';
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('is_site_default', 'y', $selected).NBS.$LANG->line('is_site_default').BR.BR); 
		$DSP->body .= $DSP->div_c();
           
           
        if ($edit == FALSE)
            $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        else
            $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
    
        $DSP->body .= $DSP->form_close();
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Create/Update Template Group
    /** -------------------------------------*/

    function update_template_group()
    {
        global $DSP, $IN, $DB, $FNS, $LANG, $SESS, $LOC, $PREFS;
      
		$group_id = $IN->GBL('group_id');
        
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $group_name = $IN->GBL('group_name', 'POST'))
        {
            return $DSP->error_message($LANG->line('form_is_empty'));
        }
        
        if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $group_name))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }
        
        if (in_array($group_name, $this->reserved_names))
        {
            return $DSP->error_message($LANG->line('reserved_name'));
        }
        
        $query = $DB->query("SELECT count(*) AS count FROM exp_template_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_name = '".$DB->escape_str($group_name)."'");
        
        if (($IN->GBL('old_name', 'POST') != $group_name) AND $query->row['count'] > 0)
        {
            return $DSP->error_message($LANG->line('template_group_taken'));
		}
		elseif ($query->row['count'] > 1)
		{
            return $DSP->error_message($LANG->line('template_group_taken'));
		}

        
        $is_site_default = ($IN->GBL('is_site_default', 'POST') == 'y' ) ? 'y' : 'n';
              
        if ($is_site_default == 'y')
        {
            $DB->query("UPDATE exp_template_groups SET is_site_default = 'n' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ");
        }
               
        
        if ( ! $group_id)
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
                                                     'is_site_default' => $is_site_default,
                                                     'site_id'		   => $PREFS->ini('site_id')
                                                   )
                                           )      
                        );
                        
            $group_id = $DB->insert_id;
            
            $duplicate = FALSE;
                        
			if (is_numeric($_POST['duplicate_group']))
			{  
				$query = $DB->query("SELECT template_name, template_data, template_type, template_notes, cache, refresh, no_auth_bounce, allow_php, php_parse_location FROM exp_templates WHERE group_id = '".$DB->escape_str($_POST['duplicate_group'])."'");
			
				if ($query->num_rows > 0)
				{
					$duplicate = TRUE;
				}
			}
			
			
			if ( ! $duplicate)
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
				foreach ($query->result as $row)
				{				
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
									'template_data'  		=> $row['template_data'],
									'edit_date'				=> $LOC->now,
									'site_id'				=> $PREFS->ini('site_id')
								 );
					
							$DB->query($DB->insert_string('exp_templates', $data, TRUE));
				}
            }
                        
            $message = '01';
        }
        else
        {
            $DB->query(
                        $DB->update_string(
                                            'exp_template_groups', 
                                             array('group_name' => $group_name, 'is_site_default' => $is_site_default), 
                                             array('group_id'   => $group_id)
                                          )
                      );              
       
            $message = '02';

        }
        
		$append = ($IN->GBL('tgpref', 'GP')) ? AMP.'tgpref='.$IN->GBL('tgpref', 'GP') : '';
		                
        $FNS->redirect(BASE.AMP.'C=templates'.AMP.'MSG='.$message.$append);
        exit;     
    }
    /* END */
    
    
    
    /** -------------------------------
    /**  Template Group Delete Confirm
    /** -------------------------------*/

    function template_group_del_conf()
    {
        global $DSP, $DB, $IN, $LANG;
        
        
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
    
        $DSP->title  = $LANG->line('template_group_del_conf');        
        $DSP->crumb  = $LANG->line('template_group_del_conf');        
    
        if ( ! $group_id = $IN->GBL('id'))
        {
            return false;
        }

        $query = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$DB->escape_str($group_id)."'");       

		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=templates'.AMP.'M=delete_tg',
												'heading'	=> 'delete_template_group',
												'message'	=> 'delete_this_group',
												'item'		=> $query->row['group_name'],
												'extra'		=> 'all_templates_will_be_nuked',
												'hidden'	=> array('group_id' => $group_id)
											)
										);	       
    }
    /* END */
    
    
    
    /** -------------------------------
    /**  Delete Template Group
    /** -------------------------------*/

    function template_group_delete()
    {
        global $DSP, $DB, $IN, $FNS, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return false;
        }
        
        if ( ! is_numeric($group_id))
        {
        	return false;
        }
        
        // We need to delete all the saved template data in the versioning table
        $query = $DB->query("SELECT template_id FROM exp_templates WHERE group_id = '$group_id'");
		
		if ($query->num_rows > 0)
		{
			$sql = "DELETE FROM exp_revision_tracker WHERE ";
			$sqlb = '';
		
			foreach ($query->result as $row)
			{
				$sqlb .= " item_id = '".$row['template_id']."' OR";
			}
			
			$sqlb = substr($sqlb, 0, -2);
			$DB->query($sql.$sqlb);

        	$DB->query("DELETE FROM exp_template_no_access WHERE ".str_replace('item_id', 'template_id', $sqlb));
        	$DB->query("DELETE FROM exp_templates WHERE group_id = '$group_id'");
        }

        $DB->query("DELETE FROM exp_template_groups WHERE group_id = '$group_id'");
                
        $FNS->redirect(BASE.AMP.'C=templates'.AMP.'MSG=03');
        exit;     
    }
    /* END */
    
    
    
    /** -------------------------------
    /**  Edit template group order
    /** -------------------------------*/

    function edit_template_group_order_form()
    {
        global $DSP, $DB, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
                
        $r  = $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_tg_order'));
        
	
        $r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->table_qcell('tableHeading', $LANG->line('edit_group_order')).
			  $DSP->table_qcell('tableHeading', $LANG->line('order')).
			  $DSP->tr_c();
			

        $query = $DB->query("SELECT group_id, group_order, group_name FROM exp_template_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n' ORDER BY group_order asc");
        
		$i = 0;
		$templates = array();

        foreach ($query->result as $row)
        {
        	$templates[$row['group_id']] = $row['group_name']; 
        
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

            $r .= $DSP->tr()
                 .$DSP->table_qcell($style, '<b>'.$row['group_name'].'</b>')
                 .$DSP->table_qcell($style, $DSP->input_text($row['group_id'], $row['group_order'], '4', '3', 'input', '30px'))      
                 .$DSP->tr_c();
        }
        
        natcasesort($templates);
        
        $r .= $DSP->table_c();

		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
        $r .= $DSP->form_close();
    
        $DSP->title  = $LANG->line('edit_group_order');        
        $DSP->crumb  = $LANG->line('edit_group_order');   
        
        $js = "<script>\n<!--\n\n\tfunction alphabetize_order()\n\t{\n";
        
        $i = 1;
        foreach($templates as $key => $template)
        {
        	$js .= "\t\tdocument.getElementById('".$key."').value = '{$i}'\n";
        	
        	$i++;
        }
        
        $js .= "\t}\n\n//-->\n</script>\n";
        
        $DSP->right_crumb($LANG->line('alphabetize_group_order'), '', 'onclick="alphabetize_order();return false;"');

        $DSP->body = $js.$r;
    }
    /* END */




    /** -------------------------------
    /**  Update Template Group Order
    /** -------------------------------*/

    function update_template_group_order()
    {  
        global $DSP, $IN, $DB, $FNS, $LANG;
      
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
        
        foreach ($_POST as $key => $val)
        {
            $DB->query("UPDATE exp_template_groups SET group_order = '$val' WHERE group_id = '$key'");    
        }
        
		$append = ($IN->GBL('tgpref', 'GP')) ? AMP.'tgpref='.$IN->GBL('tgpref', 'GP') : '';
        $FNS->redirect(BASE.AMP.'C=templates'.$append);
        exit;
    }
    /* END */
  
  

  
    /** -----------------------------
    /**  New Template Form
    /** -----------------------------*/

    function new_template_form()
    {
        global $DSP, $IN, $FNS, $DB, $SESS, $LANG, $PREFS;
        
        if ( ! $group_id = $IN->GBL('id'))
        {
            return false;
        }
        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }
        
        $user_blog = ($SESS->userdata['tmpl_group_id'] == 0) ? FALSE : TRUE;
                        
        // Build the output
        
        $DSP->title = $LANG->line('new_template_form');
        $DSP->crumb = $LANG->line('new_template_form');      
        
        $r  = $DSP->form_open(array('action' => 'C=templates'.AMP.'M=new_template'));
        $r .= $DSP->input_hidden('group_id', $group_id);
        
        $r .= $DSP->qdiv('tableHeading', $LANG->line('new_template_form'));        

        $r .= $DSP->div('box');                
        $r .= $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('name_of_template', 'template_name').'</b>')
             .$DSP->qdiv('itemWrapper', $LANG->line('template_group_instructions'))
             .$DSP->qdiv('itemWrapper', $LANG->line('undersores_allowed'))
             .$DSP->qdiv('', $DSP->input_text('template_name', '', '20', '50', 'input', '240px'));
                 
                 
        $r .= $DSP->div('itemWrapper').'<b>'.$LANG->line('template_type').'</b>';
        $r .= $DSP->input_select_header('template_type');
        $r .= $DSP->input_select_option('webpage', $LANG->line('webpage'), 1);             
        $r .= $DSP->input_select_option('rss', $LANG->line('rss'), '');
        $r .= $DSP->input_select_option('css', $LANG->line('css_stylesheet'), '');
        $r .= $DSP->input_select_option('js', $LANG->line('js'), '');
        $r .= $DSP->input_select_option('static', $LANG->line('static'), '');
    	$r .= $DSP->input_select_option('xml', $LANG->line('xml'), '');
    	$r .= $DSP->input_select_footer();
        $r .= $DSP->div_c();
        $r .= $DSP->div_c();
            
		$r .= $DSP->table('tableBorder', '0', '', '100%')
			 .$DSP->tr()
			 .$DSP->td('tableHeadingAlt', '', '3').$LANG->line('choose_default_data').$DSP->td_c()
			 .$DSP->tr_c();
		
		$r .= $DSP->tr()
			 .$DSP->table_qcell('tableCellOne', $DSP->input_radio('data', 'none', 1))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('blank_template')))
			 .$DSP->table_qcell('tableCellOne', NBS)
			 .$DSP->tr_c();
        
        
        $data = $FNS->create_directory_map(PATH_TMPL);
        
        $d = '';
        
        if (count($data) > 0)
        {              
            $d = $DSP->input_select_header('library');
            
            $this->render_map_as_select_options($data);
    
            foreach ($this->template_map as $val)
            {
                $d .= $DSP->input_select_option($val, substr($val, 0, -4));
            }
            
            $d .= $DSP->input_select_footer();
        }
        
		$r .= $DSP->tr()
			 .$DSP->table_qcell('tableCellOne', $DSP->input_radio('data', 'library', ''))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('template_from_library')))
			 .$DSP->table_qcell('tableCellOne', $d)
			 .$DSP->tr_c();
           
        $sql = "SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id, exp_sites.site_label
                FROM   exp_template_groups, exp_templates, exp_sites
                WHERE  exp_template_groups.group_id =  exp_templates.group_id
                AND    exp_template_groups.site_id = exp_sites.site_id";
                
         
        if ($user_blog == TRUE)
        {
            $sql .= " AND exp_template_groups.group_id = '".$SESS->userdata['tmpl_group_id']."'";
        }
        else
        {
            $sql .= " AND exp_template_groups.is_user_blog = 'n'";
        }
        
        if ($PREFS->ini('multiple_sites_enabled') !== 'y')
		{
			$sql .= "AND exp_template_groups.site_id = '1' ";
		}
                
        $sql .= " ORDER BY exp_sites.site_label, exp_template_groups.group_order, exp_templates.template_name";         
                
                
        $query = $DB->query($sql);
                
                
        $d  = $DSP->input_select_header('template');
                
        foreach ($query->result as $row)
        {
            $d .= $DSP->input_select_option($row['template_id'], (($PREFS->ini('multiple_sites_enabled') === 'y') ? $row['site_label'].NBS.'-'.NBS : '').$row['group_name'].'/'.$row['template_name']);
        }
        
        $d .= $DSP->input_select_footer();
        
        
		$r .= $DSP->tr()
			 .$DSP->table_qcell('tableCellOne', $DSP->input_radio('data', 'template', ''))
			 .$DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('an_existing_template')))
			 .$DSP->table_qcell('tableCellOne', $d)
			 .$DSP->tr_c()
			 .$DSP->table_c();
               
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')))
             .$DSP->form_close();

        $DSP->body = $r;          
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
				if ($array_name != '')
				{
					$val = $array_name.'/'.$val;
				}
				
				if (preg_match("#\.(tpl|css|js)$#", $val))
				{    
					$this->template_map[] = $val;
				}
			}
            
        }
    }
    /* END */



    /** -------------------------------
    /**  Create new template
    /** -------------------------------*/
    
    function create_new_template()
    {
        global $DSP, $IN, $DB, $LOC, $FNS, $SESS, $LANG, $PREFS;
        

        if ( ! $template_name = $IN->GBL('template_name', 'POST'))
        {
            return $DSP->error_message($LANG->line('you_must_submit_a_name'));
        }
                 
        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
        	return $DSP->no_access_message();
        }
        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }
        
        $user_blog = ($SESS->userdata['tmpl_group_id'] == 0) ? FALSE : TRUE;

        
        if ($user_blog == TRUE && $group_id != $SESS->userdata['tmpl_group_id'])
        {
        	return $DSP->no_access_message();
        }
             
        if ( ! preg_match("#^[a-zA-Z0-9_\.-]+$#i", $template_name))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }
        
        if (in_array($template_name, $this->reserved_names))
        {
            return $DSP->error_message($LANG->line('reserved_name'));
        }
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_templates WHERE group_id = '".$DB->escape_str($_POST['group_id'])."' AND template_name = '".$DB->escape_str($_POST['template_name'])."'");
        
        if ($query->row['count'])
        {
            return $DSP->error_message($LANG->line('template_name_taken'));
        }
        
                
        $template_data = '';
        
        $template_type = $_POST['template_type'];
        
        if ($_POST['data'] == 'library' && isset($_POST['library']))
        {
        	$parts = explode('/', $_POST['library']);
        
        	if (sizeof($parts) == 1)
        	{
        		$_POST['library'] = $FNS->filename_security($parts[0]);
        	}
        	else
        	{
        		$_POST['library'] = '';
        		
        		foreach($parts as $part)
        		{
        			$_POST['library'] .= $FNS->filename_security($part).'/';
        		}
        	}
        	
        	$_POST['library'] = rtrim($_POST['library'], '/');
        	
            if ($fp = @fopen(PATH_TMPL.$_POST['library'], 'r'))
            {
            	$size = filesize(PATH_TMPL.$_POST['library']);
            	
                $template_data = ($size > 0) ? fread($fp, $size) : '';
                fclose($fp);
            }
                        
			$data = array(
							'template_id'    => '',
							'group_id'       => $_POST['group_id'],
							'template_name'  => $_POST['template_name'],
							'template_type'  => $template_type,
							'template_data'  => $template_data,
							'edit_date'		 => $LOC->now,
							'site_id'		 => $PREFS->ini('site_id')
						 );
						 
        	$DB->query($DB->insert_string('exp_templates', $data));
            
        }
        elseif ($_POST['data'] == 'template')
        {
            $query = $DB->query("SELECT tg.group_name, template_name, template_data, template_type, template_notes, cache, refresh, no_auth_bounce, allow_php, php_parse_location, save_template_file 
            					 FROM exp_templates t, exp_template_groups tg 
            					 WHERE t.template_id = '".$DB->escape_str($_POST['template'])."'
            					 AND tg.group_id = t.group_id");
            
            if ($PREFS->ini('save_tmpl_files') == 'y' && $PREFS->ini('tmpl_file_basepath') != '' && $query->row['save_template_file'] == 'y')
            {
            	$basepath = rtrim($PREFS->ini('tmpl_file_basepath'), '/').'/';
									
				$basepath .= $query->row['group_name'].'/'.$query->row['template_name'].'.php';
				
				if ($fp = @fopen($basepath, 'rb'))
				{
					flock($fp, LOCK_SH);
					
					$query->row['template_data'] = (filesize($basepath) == 0) ? '' : fread($fp, filesize($basepath)); 
					
					flock($fp, LOCK_UN);
					fclose($fp); 
				}
            }
            
            $template_data = $query->row['template_data'];
            
            if ($template_type != $query->row['template_type'])
                $template_type = $query->row['template_type'];
                
			$data = array(
							'template_id'    		=> '',
							'group_id'       		=> $_POST['group_id'],
							'template_name'  		=> $_POST['template_name'],
							'template_notes'  		=> $query->row['template_notes'],
							'cache'  				=> $query->row['cache'],
							'refresh'  				=> $query->row['refresh'],
							'no_auth_bounce'  		=> $query->row['no_auth_bounce'],
							'php_parse_location'	=> $query->row['php_parse_location'],
							'allow_php'  			=> ($SESS->userdata['group_id'] == 1) ? $query->row['allow_php'] : 'n',
							'template_type' 		=> $template_type,
							'template_data'  		=> $template_data,
							'edit_date'				=> $LOC->now,
							'site_id'				=> $PREFS->ini('site_id')
						 );                
				
				$DB->query($DB->insert_string('exp_templates', $data, TRUE));
        }
        else
        {
			$data = array(
							'template_id'    => '',
							'group_id'       => $_POST['group_id'],
							'template_name'  => $_POST['template_name'],
							'template_type'  => $template_type,
							'template_data'  => '',
							'edit_date'		 => $LOC->now,
							'site_id'		 => $PREFS->ini('site_id')
						 );
        
        	$DB->query($DB->insert_string('exp_templates', $data));
        }
        
		$append = ($IN->GBL('tgpref', 'GP')) ? AMP.'tgpref='.$IN->GBL('tgpref', 'GP') : '';
        $FNS->redirect(BASE.AMP.'C=templates'.AMP.'MSG=04'.$append);
        exit;     
    }
    /* END */
        
    
    
    /** -------------------------------
    /**  Template Delete Confirm
    /** -------------------------------*/

    function template_del_conf()
    {
        global $DSP, $DB, $IN, $SESS, $LANG;
        
        if ( ! $id = $IN->GBL('id'))
        {
            return false;
        }
        
        if ( ! is_numeric($id))
        {
        	return false;
        }
        
        $query = $DB->query("SELECT group_id, template_name FROM exp_templates WHERE template_id = '$id'");   
        
        $group_id	= $query->row['group_id'];
        $name		= $query->row['template_name'];
                
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
				if ( ! $this->template_access_privs(array('group_id' => $group_id)))
				{
					return $DSP->no_access_message();
				}
            }
        }
        else
        {
            if ($group_id != $SESS->userdata['tmpl_group_id'] )
            {
                return $DSP->no_access_message();
            }
        }
    
        $DSP->title  = $LANG->line('template_del_conf');        
        $DSP->crumb  = $LANG->line('template_del_conf');        
        
		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=templates'.AMP.'M=delete_template',
												'heading'	=> 'delete_template',
												'message'	=> 'delete_this_template',
												'item'		=> $name,
												'extra'		=> '',
												'hidden'	=> array('template_id' => $id)
											)
										);	
    }
    /* END */
    
    
    
    /** -------------------------------
    /**  Delete Template
    /** -------------------------------*/

    function delete_template()
    {
        global $DSP, $IN, $LANG, $FNS, $SESS, $DB;
        
        if ( ! $id = $IN->GBL('template_id', 'POST'))
        {
            return false;
        }
        
        if ( ! is_numeric($id))
        {
        	return false;
        }
        
        $query = $DB->query("SELECT group_id FROM exp_templates WHERE template_id = '$id'");   
        
        $group_id = $query->row['group_id'];
                
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
				if ( ! $this->template_access_privs(array('group_id' => $group_id)))
				{
					return $DSP->no_access_message();
				}
            }
        }
        else
        {
            if ($group_id != $SESS->userdata['tmpl_group_id'] )
            {
                return $DSP->no_access_message();
            }
        }        
        
        $DB->query("DELETE FROM exp_revision_tracker WHERE item_id = '$id' AND item_table = 'exp_templates' and item_field = 'template_data' ");

        $DB->query("DELETE FROM exp_template_no_access WHERE template_id = '$id'");
        $DB->query("DELETE FROM exp_templates WHERE template_id = '$id'");
        
		$append = ($IN->GBL('tgpref', 'GP')) ? AMP.'tgpref='.$IN->GBL('tgpref', 'GP') : '';
		        
        $FNS->redirect(BASE.AMP.'C=templates'.AMP.'MSG=05'.$append);
        exit;     
    }
    /* END */
    
    
    
    /** -----------------------------
    /**  Template Member Access
    /** -----------------------------*/
    
    function template_access($template_id = '')
    {
        global $IN, $DSP, $DB, $SESS, $LANG, $PREFS;
                
		if ( ! $DSP->allowed_group('can_admin_templates'))
		{
			return $DSP->no_access_message();
		}
		                
        if ($template_id == '')
        {
            if ( ! $template_id = $IN->GBL('id'))
            {
                return false;
            }
            
            $message = '';
        }
        else
        {
            $message = $DSP->qdiv('success', $LANG->line('preferences_updated'));
        }
        
		if (defined('UB_TMP_GRP'))
        {
        	$query = $DB->query("SELECT group_id FROM exp_templates WHERE template_id = '".$DB->escape_str($template_id)."'");
        	
        	if ($query->num_rows != 1)
        	{
				return $DSP->no_access_message();
        	}
        	
        	if ($query->row['group_id'] != UB_TMP_GRP)
        	{
				return $DSP->no_access_message();
        	}
        }
        
        $query = $DB->query("SELECT template_name, enable_http_auth, group_id, no_auth_bounce FROM exp_templates WHERE template_id = '".$DB->escape_str($template_id)."'");
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
    
        $DSP->title  = $LANG->line('template_access');        
        $DSP->crumb  = $LANG->line('template_access');      
    
        $r  = $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_template_access', 'name' => 'templateManagement', 'id' => 'templateManagement'))
             .$DSP->input_hidden('template_id', $template_id)
             .$DSP->input_hidden('group_id', $group_id)
             .$DSP->qdiv('tableHeading', $LANG->line('template_access').NBS.'('.$template_name.')')
             .$message;
        
		$r .= $DSP->qdiv('box', $DSP->qdiv('highlight_alt', $LANG->line('group_restriction')));

		$r .= $DSP->table('tableBorder', '0', '', '100%').
			  $DSP->tr().
			  $DSP->td('tableHeading', '', '').
			  $LANG->line('member_group').
			  $DSP->td_c().
			  $DSP->td('tableHeading', '', '').
			  $LANG->line('can_view_template').
			  $DSP->td_c().
			  $DSP->tr_c();
	
		$i = 0;
		
		$group = array();
		
		$result = $DB->query("SELECT member_group FROM exp_template_no_access WHERE template_id = '".$DB->escape_str($template_id)."'");
		
		if ($result->num_rows != 0)
		{
			foreach($result->result as $row)
			{
				$group[$row['member_group']] = TRUE;
			}
		}
		
		$query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '1' ORDER BY group_title");
		$access_e = array();
		
		$r2 = '';
		
		foreach ($query->result as $row)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
	
			$r2 .= $DSP->tr().
				   $DSP->td($style, '40%').
				   $row['group_title'].
				   $DSP->td_c().
				   $DSP->td($style, '60%');
				  
			$selected = ( ! isset($group[$row['group_id']])) ? 1 : '';
				
			$r2 .= $LANG->line('yes').NBS.
				   $DSP->input_radio('access_'.$row['group_id'], 'y', $selected).$DSP->nbs(3);
			   
			$selected = (isset($group[$row['group_id']])) ? 1 : '';
				
			$r2 .= $LANG->line('no').NBS.
				   $DSP->input_radio('access_'.$row['group_id'], 'n', $selected).$DSP->nbs(3);

			$r2 .= $DSP->td_c()
				  .$DSP->tr_c();
			
			$access_e[] = "access_{$row['group_id']}";
		}
		
		$access_e[] = 'can_view';
		$access_e[] = 'can_view2';
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r3 = $this->template_access_toggle($access_e).
			  $DSP->tr().
			  $DSP->td('tableCellOne', '40%').
			  $DSP->qdiv('defaultBold', $LANG->line('select_all')).
			  $DSP->td_c().
			  $DSP->td('tableCellOne', '60%').
			  $LANG->line('yes').NBS.
			  $DSP->input_radio('can_view', 'y', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $LANG->line('no').NBS.
			  $DSP->input_radio('can_view', 'n', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r4 = $this->template_access_toggle($access_e).
			  $DSP->tr().
			  $DSP->td($style, '40%').
			  $DSP->qdiv('defaultBold', $LANG->line('select_all')).
			  $DSP->td_c().
			  $DSP->td($style, '60%').
			  $LANG->line('yes').NBS.
			  $DSP->input_radio('can_view2', 'y', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $LANG->line('no').NBS.
			  $DSP->input_radio('can_view2', 'n', '', "onclick=\"toggle_access(this);\"").$DSP->nbs(3).
			  $DSP->td_c().
			  $DSP->tr_c();
			
		$r .= $r3.$r2.$r4.$DSP->table_c(); 
		
		$r .= $DSP->div('box');
		$r .= $DSP->heading($LANG->line('no_access_select_blurb'), 5);
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('no_access_instructions'));
		
		$sql = "SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
				FROM   exp_template_groups, exp_templates
				WHERE  exp_template_groups.group_id =  exp_templates.group_id
				AND    exp_template_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
				
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
				
		$r .=  $DSP->div()
			  .$DSP->input_select_header('no_auth_bounce');
				
		foreach ($query->result as $row)
		{
			$selected = ($row['template_id'] == $no_auth_bounce) ? 1 : '';
		
			$r .= $DSP->input_select_option($row['template_id'], $row['group_name'].'/'.$row['template_name'], $selected);
		}
		
		$r .= $DSP->input_select_footer().BR.BR; 
		$r .= $DSP->div('paddedTop');
		$r .= $DSP->heading($LANG->line('enable_http_authentication'), 5);
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('enable_http_authentication_subtext'));
		$r .= $DSP->qdiv('itemWrapper', $DSP->qspan('alert', $LANG->line('enable_http_authentication_note')));
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('enable_http_auth', 'y', ($enable_http_auth == 'y') ? 'y' : '')
										.NBS.NBS.$LANG->line('enable_http_authentication')); 
		$r .= $DSP->div_c(); 
		$r .= $DSP->div_c(); 
		$r .= $DSP->div_c(); 
	  
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')))
             .$DSP->form_close();
    
        $DSP->body = $r;
    }
    /* END */
    
 
 
	/** -------------------------------
    /**  Update Template Access
    /** -------------------------------*/
    
    function update_template_access()
    {
        global $IN, $DSP, $DB, $SESS, $LANG;
            
        if ( ! $template_id = $IN->GBL('template_id', 'POST'))
        {
            return false;
        }
        
        if ( ! is_numeric($template_id))
        {
        	return false;
        }
        
        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return false;
        }
        
        $query = $DB->query("SELECT group_id, template_name FROM exp_templates WHERE template_id = '".$DB->escape_str($template_id)."'");
                
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
        else
        {
            if ($query->row['group_id'] != $SESS->userdata['tmpl_group_id'] )
            {
                return $DSP->no_access_message();
            }
        }        
        
                        
        $DB->query("DELETE FROM exp_template_no_access WHERE template_id = '".$DB->escape_str($template_id)."'");
        
        $no_auth = FALSE;
                        
        foreach ($_POST as $key => $val)
        {
            if (substr($key, 0, 7) == 'access_' AND $val == 'n')
            {
                $no_auth = TRUE;
                
                $DB->query("INSERT INTO exp_template_no_access (template_id, member_group) VALUES ('$template_id', '".substr($key, 7)."')");
            }
        } 
        
        $data['no_auth_bounce']	  = ($no_auth == TRUE) ? $_POST['no_auth_bounce'] : '';
        $data['enable_http_auth'] = (isset($_POST['enable_http_auth'])) ? 'y' : 'n';
        
        $DB->query($DB->update_string('exp_templates', $data, "template_id = '$template_id'"));
        
        return $this->template_access($template_id);
    }    
    /* END */
    
    
	/** ----------------------------------------
	/**  JavaScript Toggle Code for Permissions
	/** ----------------------------------------*/

	function template_access_toggle($elements)
	{
        ob_start();
    
        ?>
        <script type="text/javascript"> 
        <!--
    
        function toggle_access(thebutton)
        {   
	        var val = thebutton.value;
<?php
			foreach($elements as $element)
			{
?>				
				var len = document.getElementById('templateManagement').<?php echo $element; ?>.length;
				
				for (var i = 0; i < len; i++)
				{
					if (document.getElementById('templateManagement').<?php echo $element; ?>[i].value == val)
					{
						document.getElementById('templateManagement').<?php echo $element; ?>[i].checked = true;										
					}
				}
<?php		} ?>

        }
        
        //-->
        </script>
        <?php
    
        $out = ob_get_contents();
			
        ob_end_clean(); 

		return $out;	
	}
	/* END */
	
       
    /** -------------------------------
    /**  Edit Template
    /** -------------------------------*/

    function edit_template($template_id = '', $message = '')
    {
        global $DSP, $IN, $DB, $EXT, $PREFS, $SESS, $FNS, $LOC, $LANG;
                
        if ($template_id == '')
        {
            if ( ! $template_id = $IN->GBL('id'))
            {
                return false;
            }
        }
        
        if ( ! is_numeric($template_id))
        {
        	return false;
        }
        
        $user_blog = ($SESS->userdata['tmpl_group_id'] == 0) ? FALSE : TRUE;
        
        $query = $DB->query("SELECT group_id, template_name, save_template_file, template_data, template_notes, template_type, edit_date, last_author_id FROM exp_templates WHERE template_id = '$template_id'");
        
        $group_id = $query->row['group_id'];
        $template_type = $query->row['template_type'];
        
        $result = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$group_id."'");
                                               
        $template_group  = $result->row['group_name']; 
                        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }
        
        $template_data  	= $query->row['template_data'];   
        $template_name  	= $query->row['template_name']; 
        $template_notes 	= $query->row['template_notes']; 
        $save_template_file	= $query->row['save_template_file']; 
        
		    
		$date_fmt = ($SESS->userdata['time_format'] != '') ? $SESS->userdata['time_format'] : $PREFS->ini('time_format');

		if ($date_fmt == 'us')
		{
			$datestr = '%m/%d/%y %h:%i %a';
		}
		else
		{
			$datestr = '%Y-%m-%d %H:%i';
		}

		$edit_date = $LOC->decode_date($datestr, $query->row['edit_date'], TRUE);
		
		$mquery = $DB->query("SELECT screen_name FROM exp_members WHERE member_id = ".$query->row['last_author_id']);

		if ($mquery->num_rows == 0)
		{
			// this feature was added in 1.6.5, so existing templates following that update will have a member_id of '0'
			// and will not have a known value until the template is edited again.
			$last_author = '';
		}
		else
		{
			$last_author = $mquery->row['screen_name'];
		}

		/* -------------------------------------
		/*  'edit_template_start' hook.
		/*  - Allows complete takeover of the template editor
		/*  - Added 1.6.0
		*/  
			$edata = $EXT->call_extension('edit_template_start', $query, $template_id, $message);
			if ($EXT->end_script === TRUE) return;
		/*
		/* -------------------------------------*/
		
        // Clear old revisions
        
        if ($PREFS->ini('save_tmpl_revisions') == 'y')
        {
			$maxrev = $PREFS->ini('max_tmpl_revisions');
	
			if ($maxrev != '' AND is_numeric($maxrev) AND $maxrev > 0)
			{  
				$res = $DB->query("SELECT tracker_id FROM exp_revision_tracker WHERE item_id = '$template_id' AND item_table = 'exp_templates' AND item_field ='template_data' ORDER BY tracker_id DESC");
				
				if ($res->num_rows > 0  AND $res->num_rows > $maxrev)
				{
					$flag = '';
					
					$ct = 1;
					foreach ($res->result as $row)
					{
						if ($ct >= $maxrev)
						{
							$flag = $row['tracker_id'];
							break;
						}
						
						$ct++;
					}
					
					if ($flag != '')
					{
						$DB->query("DELETE FROM exp_revision_tracker WHERE tracker_id < $flag AND item_id = '".$DB->escape_str($template_id)."' AND item_table = 'exp_templates' AND item_field ='template_data'");
					}
				}
			}
        }
        
          
        if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '' AND $save_template_file == 'y')
        {
			$basepath = rtrim($PREFS->ini('tmpl_file_basepath'), '/').'/';
			
			$basepath .= $template_group.'/'.$template_name.'.php';
		
			if ($file = $DSP->file_open($basepath))
			{
				$template_data = $file;
			}
        }
                          
		$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
		$sitepath = $FNS->fetch_site_index(0, 0).$qs.'URL='.$FNS->fetch_site_index();
                     
		$sitepath = rtrim($sitepath, '/').'/';
        
        if ($template_type == 'css')
        {
        	$sitepath = substr($sitepath, 0, -1);
        	$sitepath .= $qs.'css='.$template_group.'/'.$template_name.'/';
        }
        else
        {
        	$sitepath .= $template_group.(($template_name == 'index') ? '/' : '/'.$template_name.'/');
    	}
    	
        $DSP->title  = $LANG->line('edit_template').' | '.$template_name;        
        $DSP->crumb  = $LANG->line('edit_template');
		$DSP->right_crumb($LANG->line('view_rendered_template'), $sitepath, '', TRUE);
        
        ob_start();
        
        ?>     
        <script type="text/javascript"> 
        <!--
        
            function viewRevision()
            {	
                var id = document.forms.revisions.revision_history.value;
                
                if (id == "")
                {
                    return false;
                }
                else if (id == "clear")
                {
                    var items = document.forms.revisions.revision_history;
          
                    for (i = items.length -1; i >= 1; i--)
                    {
                        items.options[i] = null;
                    }
                    
                    document.forms.revisions.revision_history.options[0].selected = true;
                    
                    flipButtonText(1);
                    
                    window.open ("<?php echo BASE.'&C=templates&M=clear_revisions&id='.$template_id.'&Z=1'; ?>" ,"Revision", "width=500, height=260, location=0, menubar=0, resizable=0, scrollbars=0, status=0, titlebar=0, toolbar=0, screenX=60, left=60, screenY=60, top=60");

                    return false;                    
                }
                else
                {
                    window.open ("<?php echo BASE.'&C=templates&M=revision_history&Z=1'; ?>&id="+id ,"Revision");

                    return false;
                }
                return false;
            }
            
            function flipButtonText(which)
            {	
                if (which == "clear")
                {
                    document.forms.revisions.submit.value = '<?php echo $LANG->line('clear'); ?>';
                }
                else
                {
                    document.forms.revisions.submit.value = '<?php echo $LANG->line('view'); ?>';
                }
            }
            
        function showhide_notes()
        {
			if (document.getElementById('notes').style.display == "block")
			{
				document.getElementById('notes').style.display = "none";
				document.getElementById('noteslink').style.display = "block";				
        	}
        	else
        	{
				document.getElementById('notes').style.display = "block";
				document.getElementById('noteslink').style.display = "none";
        	}
        }
            
        //-->
        </script>        
    
        <?php
        
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
        
        $r  = $buffer;
         
        $r .= $DSP->form_open(
        						array(
        								'action'	=> '', 
        								'name'		=> 'revisions',
        								'id'		=> 'revisions'
        							),
        							
        						array(
        								'template_id' => $template_id
        							)
        					);
        
                
        $r .= $DSP->table('', '', '', '100%')
             .$DSP->tr()
             .$DSP->td('tableHeading')
             .$LANG->line('template_name').NBS.NBS.$template_group.'/'.$template_name.NBS.NBS
			 .'('.$LANG->line('last_edit').NBS.$edit_date
			 .(($last_author != '') ? NBS.$LANG->line('by').NBS.$last_author : '').')'
             .$DSP->td_c();
             
        $r .= $DSP->td('tableHeading')
             .$DSP->div('defaultRight');
             
        if ($user_blog == FALSE)
        {             
             $r .= "<select name='revision_history' class='select' onchange='flipButtonText(this.options[this.selectedIndex].value);'>"
                 .NL
                 .$DSP->input_select_option('', $LANG->line('revision_history'));
                 
            $rquery = $DB->query("SELECT tracker_id, item_date, screen_name FROM exp_revision_tracker LEFT JOIN exp_members ON exp_members.member_id = exp_revision_tracker.item_author_id WHERE item_table = 'exp_templates' AND item_field = 'template_data' AND item_id = '".$DB->escape_str($template_id)."' ORDER BY tracker_id DESC");
    
            if ($rquery->num_rows > 0)
            {             
                foreach ($rquery->result as $row)
                {
                    $r .= $DSP->input_select_option($row['tracker_id'], $LOC->set_human_time($row['item_date']).' ('.$row['screen_name'].')');
                }  
                 
                $r .= $DSP->input_select_option('clear', $LANG->line('clear_revision_history'));  
            }
            
            $r .= $DSP->input_select_footer()
                 .$DSP->input_submit($LANG->line('view'), 'submit', "onclick='return viewRevision();'");
        }
        else
        {
            $r .= NBS; 
        }             
        $r .=  $DSP->div_c()
              .$DSP->td_c()
              .$DSP->tr_c()
              .$DSP->table_c()
              .$DSP->form_close();

        $r .= $message;
                
        $r .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_template'))
             .$DSP->input_hidden('template_id', $template_id);
      
        $r .= $DSP->qdiv('templatepad', $DSP->input_textarea('template_data', $template_data, $SESS->userdata['template_size'], 'textarea', '100%'));

        $notelink	= ' <a href="javascript:void(0);" onclick="showhide_notes();return false;"><b>'.$LANG->line('template_notes').'</b></a>';
		$expand		= '<img src="'.PATH_CP_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />';
		$collapse	= '<img src="'.PATH_CP_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />';
		
		$js = ' onclick="showhide_notes();return false;" onmouseover="navTabOn(\'noteopen\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'noteopen\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		
		$r .= '<div id="noteslink" style="display: block; padding:0; margin: 0;">';
		$r .= "<div class='tableHeadingAlt' id='noteopen' ".$js.">";
		$r .= $expand.' '.$LANG->line('template_notes');
		$r .= $DSP->div_c();
		$r .= $DSP->div_c();

		$js = ' onclick="showhide_notes();return false;" onmouseover="navTabOn(\'noteclose\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'noteclose\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';

		$r .= '<div id="notes" style="display: none; padding:0; margin: 0;">';
		$r .= "<div class='tableHeadingAlt' id='noteclose' ".$js.">";
		$r .= $collapse.' '.$LANG->line('template_notes');
        $r .= $DSP->div_c();
		$r .= $DSP->div('templatebox');
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('template_notes_desc'));
        $r .= $DSP->input_textarea('template_notes', $template_notes, '24', 'textarea', '100%');
        $r .= $DSP->div_c();
		$r .= $DSP->div_c();

		$r .= $DSP->div('templatebox');
        $r .= $DSP->table('', '', '6', '100%')
             .$DSP->tr()
             .$DSP->td('', '25%', '', '', 'top')
             .$DSP->div('bigPad');
             
        if ($user_blog == FALSE AND $PREFS->ini('save_tmpl_revisions') == 'y')
        {  
              $selected = ($PREFS->ini('save_tmpl_revisions') == 'y') ? 1 : '';
        
             $r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('save_history', 'y', $selected).NBS.NBS.$LANG->line('save_history'));
        }
        
        if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
			$selected = ($save_template_file == 'y') ? 1 : '';
	
			$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('save_template_file', 'y', $selected).NBS.NBS.$LANG->line('save_template_file'));
		}
       
       
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')).NBS.$DSP->input_submit($LANG->line('update_and_return'),'return'));

        
		$r .= $DSP->td_c()
             .$DSP->td('', '25%', '', '', 'top')
             .$DSP->div('bigPad')
			 .$DSP->qdiv('itemWrapper', $DSP->input_text('columns', $SESS->userdata['template_size'], '4', '2', 'input', '30px').NBS.NBS.$LANG->line('template_size'))
             .$DSP->div_c()
			 .$DSP->td_c()
             .$DSP->tr_c()
             .$DSP->table_c()
             .$DSP->div_c();
		 
		$r .= $DSP->form_close();
		
		// TEMPLATE EXPORT LINK
		
		$r .= <<<EOT
		
		<script type="text/javascript"> 
        <!--
        
		function export_template()
		{
			document.forms['export_template_form'].export_data.value = document.getElementById('template_data').value;
			document.forms['export_template_form'].submit();
		}
		
		//-->
        </script> 
		
EOT;
		
		$r .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=export_template'.AMP.'id='.$group_id.AMP.'tid='.$template_id,
									'id'	 => "export_template_form"),
							  array('export_data' => ''));
		$r .= $DSP->qdiv('itemWrapper', $DSP->anchor('javascript:nullo();', $LANG->line('export_template'), 'onclick="export_template();return false;"'));
		$r .= $DSP->form_close();
        
		/* -------------------------------------
		/*  'edit_template_end' hook.
		/*  - Allows content to be added to the output
		/*  - Added 1.6.0
		*/  
			if ($EXT->active_hook('edit_template_end') === TRUE)
			{
				$r .= $EXT->call_extension('edit_template_end', $query, $template_id);
				if ($EXT->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/
		
        $DSP->body = $r;
    }
    /* END */
 
    
    /** -------------------------------   
    /**  Update Template
    /** -------------------------------*/

    function update_template()
    {
        global $PREFS, $DSP, $IN, $DB, $EXT, $LOC, $SESS, $FNS, $LANG;

        if ( ! $template_id = $IN->GBL('template_id', 'POST'))
        {
            return false;
        }
        
        if ( ! is_numeric($template_id))
        {
        	return false;
        }
        
        if ( ! $this->template_access_privs(array('template_id' => $template_id)))
        {
        	return $DSP->no_access_message();
        }
        
        $save_result = FALSE;
        $save_template_file = ($IN->GBL('save_template_file', 'POST') == 'y') ? 'y' : 'n';
        
		/** -------------------------------   
		/**  Save template as file
		/** -------------------------------*/
		        
        // Depending on how things are set up we might save the template data in a text file
        
		if ($PREFS->ini('tmpl_file_basepath') != '' AND $PREFS->ini('save_tmpl_files') == 'y')
		{
			$query = $DB->query("SELECT exp_templates.template_name, exp_templates.save_template_file, exp_template_groups.group_name 
								FROM exp_templates 
								LEFT JOIN exp_template_groups ON exp_templates.group_id = exp_template_groups.group_id
								WHERE template_id = '".$DB->escape_str($template_id)."'");
		
			if ($save_template_file == 'y')
			{
				$tdata = array(
								'template_id'		=> $template_id,
								'template_group'	=> $query->row['group_name'],
								'template_name'		=> $query->row['template_name'],
								'template_data'		=> $_POST['template_data'],
								'edit_date'			=> $LOC->now,
								'last_author_id'	=> $SESS->userdata['member_id']
								);
								
				$save_result = $this->update_template_file($tdata);
			}
			else
			{
				// If the template was previously saved as a text file,
				// but the checkbox was not selected this time we'll
				// delete the file
				
				if ($query->row['save_template_file'] == 'y')
				{
					$basepath = rtrim($PREFS->ini('tmpl_file_basepath'), '/').'/';
					
					$basepath .= $query->row['group_name'].'/'.$query->row['template_name'].'.php';
				
					@unlink($basepath);				
				}
			}
    	}
    	
		/** -------------------------------   
		/**  Save revision cache
		/** -------------------------------*/
		
        if ($IN->GBL('save_history', 'POST') == 'y')
        {
            $data = array(
                            'tracker_id' 		=> '',
                            'item_id'    		=> $template_id,
                            'item_table'		=> 'exp_templates',
                            'item_field'		=> 'template_data',
                            'item_data'			=> $_POST['template_data'],
                            'item_date'  		=> $LOC->now,
							'item_author_id'	=> $SESS->userdata['member_id']
                         );
    
            $DB->query($DB->insert_string('exp_revision_tracker', $data));
        }
        
		/** -------------------------------   
		/**  Save Template
		/** -------------------------------*/

        $DB->query($DB->update_string('exp_templates', array('template_data' => $_POST['template_data'], 'edit_date' => $LOC->now, 'last_author_id' => $SESS->userdata['member_id'], 'save_template_file' => $save_template_file, 'template_notes' => $_POST['template_notes']), "template_id = '$template_id'")); 
        
        if (is_numeric($_POST['columns']))
        {  
            if ($SESS->userdata['template_size'] != $_POST['columns'])
            {
                $DB->query("UPDATE exp_members SET template_size = '".$DB->escape_str($_POST['columns'])."' WHERE member_id = '".$SESS->userdata('member_id')."'");
           
                $SESS->userdata['template_size'] = $_POST['columns'];
            }
        }
        
        // Clear cache files
        $FNS->clear_caching('all');
    
        $message = $DSP->qdiv('success', $LANG->line('template_updated'));
        
        if ($save_template_file == 'y' AND $save_result == FALSE)
        {
        	$message .= $DSP->qdiv('alert', $LANG->line('template_not_saved'));
        }
        
		/* -------------------------------------
		/*  'update_template_end' hook.
		/*  - Add more things to do for template
		/*  - Added 1.6.0
		*/  
			$edata = $EXT->call_extension('update_template_end', $template_id, $message);
    		if ($EXT->end_script === TRUE) return;
		/*
		/* -------------------------------------*/
		
		if (($tgpref = $IN->GBL('tgpref')) AND is_numeric($tgpref) AND isset($_POST['return']))
		{
			$FNS->redirect(BASE.AMP.'C=templates'.AMP.'tgpref='.$tgpref);
        	exit;
		}
		else
		{
	        return $this->edit_template($template_id, $message);			
		}
    }
    /* END */
  
  
  
    /** -----------------------------
    /**  Update Template File
    /** -----------------------------*/
   
	function update_template_file($data)
	{
		global $PREFS, $DB;
		
        if ( ! $this->template_access_privs(array('template_id' => $data['template_id'])))
        {
        	return FALSE;
        }
		
		if ($PREFS->ini('save_tmpl_files') == 'n' OR $PREFS->ini('tmpl_file_basepath') == '')
        {	
			return FALSE;
   		}
   
   		$basepath = $PREFS->ini('tmpl_file_basepath', TRUE);
   		   
		if ( ! @is_dir($basepath) OR ! is_writable($basepath))
		{
			return FALSE;
		}
		
		$basepath .= $data['template_group'];
		
		if ( ! @is_dir($basepath))
		{
			if ( ! @mkdir($basepath, 0777))
			{
				return FALSE;
			}
			@chmod($basepath, 0777); 
		}

        if ( ! $fp = @fopen($basepath.'/'.$data['template_name'].'.php', 'wb'))
        {
        	return FALSE;
        }
        else
        {
			flock($fp, LOCK_EX);
			fwrite($fp, stripslashes($data['template_data']));
			flock($fp, LOCK_UN);
			fclose($fp);
			
			@chmod($basepath.'/'.$data['template_name'].'.php', 0777); 
		}
				   
		return TRUE;   
   }
   /* END */
   
    
    /** -----------------------------
    /**  View Revision History
    /** -----------------------------*/

    function view_template_revision()
    {
        global $DSP, $REGX, $IN, $DB, $LANG;
                
        if ( ! $id = $IN->GBL('id'))
        {
            return false;
        }
        
		$query = $DB->query("SELECT item_id FROM exp_revision_tracker WHERE tracker_id = '".$DB->escape_str($id)."' AND item_table = 'exp_templates' AND item_field = 'template_data'");
		        
        if ($query->num_rows == 0)
        {
        	return false;
        }
                                
        if ( ! $this->template_access_privs(array('template_id' => $query->row['item_id'])))
        {
        	return $DSP->no_access_message();
        }
        
        $DSP->title  = $LANG->line('revision_history');        
        $DSP->crumb  = $LANG->line('revision_history');     
        
        $query = $DB->query("SELECT item_data FROM exp_revision_tracker WHERE tracker_id = '".$DB->escape_str($id)."' ");
        
        $DSP->body = $DSP->input_textarea('template_data', $query->row['item_data'], 26, 'textarea', '100%');
        $DSP->body .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="JavaScript:window.close();"><b>'.$LANG->line('close_window').'</b></a></div>');        
    }
    /* END */


   
    /** -----------------------------
    /**  Clear Revision History
    /** -----------------------------*/

    function clear_revision_history()
    {
        global $DSP, $DB, $IN, $LANG;
    
        if ( ! $DSP->allowed_group('can_admin_templates'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $id = $IN->GBL('id'))
        {
            return false;
        }
    
        $DSP->title  = $LANG->line('revision_history');        
        $DSP->crumb  = $LANG->line('revision_history');        
        
        $query = $DB->query("DELETE FROM exp_revision_tracker WHERE item_id = '".$DB->escape_str($id)."' AND item_table = 'exp_templates' AND item_field ='template_data'");
    
        $DSP->body = $DSP->qdiv('defaultCenter', BR.BR.'<b>'.$LANG->line('history_cleared').'</b>'.BR.BR.BR);
        
        $DSP->body .= $DSP->qdiv('defaultCenter', "<a href='javascript:window.close();'>".$LANG->line('close_window')."</a>".BR.BR.BR);
    
    }
    /* END */
   
   


    /** -----------------------------
    /**  Export template form
    /** -----------------------------*/
    
    function export_templates_form($group_id = '')
    {
        global $IN, $SESS, $DSP, $DB, $LANG;
        
        if ($group_id == '')
        {            
            if ( ! $group_id = $IN->GBL('id'))
            {
                return false;
            }
       }
                      
        if ($SESS->userdata['tmpl_group_id'] != 0)
        {
            $group_id = $SESS->userdata['tmpl_group_id'];
        }
        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }       
        
        $LANG->fetch_language_file('admin');

        $sql  = "SELECT group_name FROM exp_template_groups WHERE group_id = '".$DB->escape_str($group_id)."'";  
                          
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return false;
        }
                
        $r = $this->toggle_code();
        
        $r .= $DSP->qdiv('tableHeading', $LANG->line('export_templates'));
        $r .= $DSP->qdiv('box', $DSP->qdiv('highlight_alt', $LANG->line('choose_templates')));

        $r .= $DSP->form_open(
        						array(
        								'action' => 'C=templates'.AMP.'M=export'.AMP.'id='.$group_id, 
        								'name'	=> 'templates',
        								'id'	=> 'templates'
        							)
        					);

        $template_group  = $query->row['group_name'];
            
        $r .= $DSP->table('tableBorder', '0', '', '100%')
             .$DSP->tr()
             .$DSP->table_qcell('tableHeadingAlt', $LANG->line('template_name'), '32%')
             .$DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.NBS.$LANG->line('select_all'), '17%')
             .$DSP->td_c()
             .$DSP->tr_c();

        $i = 0;
        
        $res = $DB->query("SELECT template_id, template_name, hits FROM exp_templates WHERE group_id = '".$DB->escape_str($group_id)."' ORDER BY template_name");
            
        foreach ($res->result as $val)
        {     
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
          
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $val['template_name']));

            $r .= $DSP->table_qcell($style, "<input type='checkbox' name=\"template[".$val['template_id']."]\" value='y' />")
                 .$DSP->tr_c();
        }
                
        $r .= $DSP->table_c();
        
        $r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('export_will_be_zip')));
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('export')));
        $r .= $DSP->form_close();
       
        $DSP->title = $LANG->line('export_templates');        
        $DSP->crumb = $LANG->line('export_templates');
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
                        
            var len = document.templates.elements.length;
        
            for (var i = 0; i < len; i++) 
            {
                var button = document.templates.elements[i];
                
                var name_array = button.name.split("["); 
                
                if (name_array[0] == "template") 
                {
                    button.checked = val;
                }
            }
            
            document.templates.toggleflag.checked = val;
        }
        
        //-->
        </script>
        <?php
    
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
        
        return $buffer;
    } 
    /* END */
	
	/** -----------------------------
    /**  Export single template
    /** -----------------------------*/
    
    function export_template()
    {
    	global $IN;
    	
    	if ( ! is_numeric($IN->GBL('tid')))
    	{
    		return FALSE;
    	}
    	
		$_POST['template'][$IN->GBL('tid')] = $IN->GBL('tid');
		
		$this->export_templates('default');
	}
	/* END */
    
    /** -----------------------------
    /**  Export templates
    /** -----------------------------*/
    
    function export_templates($type='zip')
    {
        global $IN, $SESS, $DSP, $DB, $LOC, $FNS, $LANG;
        
        if ( ! $group_id = $IN->GBL('id'))
        {
            return false;
        }
        
        /** --------------------------------------
        /**  Is the user allowed to export?
        /** --------------------------------------*/
                
        if ($SESS->userdata['tmpl_group_id'] != 0)
        {
            $group_id = $SESS->userdata['tmpl_group_id'];
        }
        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }       

        /** --------------------------------------
        /**  No templates?  Bounce them back
        /** --------------------------------------*/

        if ( ! isset($_POST['template']))
        {
            return $this->export_templates_form($group_id);
        }

        /** --------------------------------------
        /**  Is the selected compression supported?
        /** --------------------------------------*/

        if ( ! @function_exists('gzcompress') && $type == 'zip') 
        {
            return $DSP->error_message($LANG->line('unsupported_compression'));
        }

        /** --------------------------------------
        /**  Assign the name of the of the folder
        /** --------------------------------------*/
        
        $query = $DB->query("SELECT group_name, is_site_default FROM exp_template_groups WHERE group_id = '$group_id'");
                        
        $directory = $query->row['group_name'].'_tmpl';
            
        /** --------------------------------------
        /**  Fetch the template data and zip it
        /** --------------------------------------*/
        
        if ($type == 'default' && sizeof($_POST['template']) == 1)
        {
        	$directory = $query->row['group_name'].'_';
        
        	$query = $DB->query("SELECT template_data, template_name, template_type FROM exp_templates WHERE template_id = '".$DB->escape_str(array_pop(array_keys($_POST['template'])))."'");
        	
        	$output		 = ( ! isset($_POST['export_data']) OR $_POST['export_data'] == '') ? $query->row['template_data'] : stripslashes($_POST['export_data']);
        	$directory	.= $query->row['template_name'];
        	
        	switch($query->row['template_type'])
        	{
        		case 'css' :
        			$suffix = 'css';
        			$content_type = 'application/force-download';
        		break;
        		case 'js' :
        			$suffix = 'js';
        			$content_type = 'application/force-download';
        		break;
        		case 'rss' :
        			$suffix = 'xml';
        			$content_type = 'application/force-download';
        		break;
        		case 'static' :
        			$suffix = 'txt';
        			$content_type = 'application/force-download';
        		break;
        		case 'webpage' :
        			$suffix = 'html';
        			$content_type = 'application/force-download';
        		break;
        		case 'xml' :
        			$suffix = 'xml';
        			$content_type = 'application/force-download';
        		break;
        		default :
        			$suffix = 'txt';
        			$content_type = 'application/force-download';
        		break;
        	}
        }
        else
		{
			require PATH_CP.'cp.utilities'.EXT;
			
			$zip = new Zipper;
			
			$temp_data = array();
			
		   // $zip->add_dir($directory.'/');
	
			foreach ($_POST['template'] as $key => $val)
			{
				$query = $DB->query("SELECT template_data, template_name FROM exp_templates WHERE template_id = '".$DB->escape_str($key)."'");
	
				$zip->add_file($query->row['template_data'], $directory.'/'.$query->row['template_name'].'.txt');
			}
			
			$suffix = 'zip';
			$content_type = 'application/x-zip';
		}
        
        /** -------------------------------------------
        /**  Write out the headers
        /** -------------------------------------------*/
        
        ob_start();                
        
        if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
        {
            header('Content-Type: '.$content_type);
            header('Content-Disposition: inline; filename="'.$directory.'.'.$suffix.'"');
			header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } 
        else 
        {
            header('Content-Type: '.$content_type);
            header('Content-Disposition: attachment; filename="'.$directory.'.'.$suffix.'"');
			header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
            header('Pragma: no-cache');
        }
        
        if (isset($output))
        {
        	echo $output;
        }
        else
        {
        	echo $zip->output_zipfile();
        }          
        
        $buffer = ob_get_contents();
        
        ob_end_clean(); 
        
        echo $buffer;
        
        exit;    
    }
    /* END */
    
    
    
    
    /** -----------------------------
    /**  Global Variables
    /** -----------------------------*/
    
    function global_variables($message = '')
    {
    	global $DSP, $DB, $LANG, $SESS, $PREFS;
    
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
    
    	$DSP->title = $LANG->line('global_variables');
    	$DSP->crumb = $LANG->line('global_variables');		
		$DSP->right_crumb($LANG->line('create_new_global_variable'), BASE.AMP.'C=templates'.AMP.'M=edit_global_var');
    	    	
    	if ($message != '')
    	{
    		$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));
    	}
    	
		$i = 0;
		
		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		$id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
		
		$query = $DB->query("SELECT variable_id, variable_name, variable_data FROM exp_global_variables WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND user_blog_id = '".$DB->escape_str($id)."' ORDER BY variable_name ASC");
		
		
		/** -----------------------------
    	/**  Table Header
    	/** -----------------------------*/

        $DSP->body .= $DSP->table('tableBorder', '0', '0', '100%').
					  $DSP->tr().
					  $DSP->table_qcell('tableHeading',
										($query->num_rows == 0) ? 
											array($LANG->line('global_variables')) : 
											array($LANG->line('global_variables'), 
												  $LANG->line('global_variable_syntax'),
												  $LANG->line('delete')
												 )
										).
					  $DSP->tr_c();
					  
		/** -----------------------------
    	/**  Table Rows
    	/** -----------------------------*/

        if ($query->num_rows == 0)
        {
			$DSP->body .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
											array(
													$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_global_variables')))
												  )
											);
        }
        else
        {
			foreach ($query->result as $row)
			{			
			
				$DSP->body .= $DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
										array(
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=edit_global_var'.AMP.'id='.$row['variable_id'], $row['variable_name'])),
												$DSP->qspan('defaultBold', '{'.$row['variable_name'].'}'),
												$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=templates'.AMP.'M=delete_global_var'.AMP.'id='.$row['variable_id'], $LANG->line('delete'))),
											  )
										);
			}
		}	
        
        $DSP->body .= $DSP->table_c(); 
    }
    /* END */
    
    


    /** -----------------------------
    /**  Create/Edit Global Variables
    /** -----------------------------*/
    
    function edit_global_variable()
    {
    	global $IN, $DSP, $DB, $LANG, $SESS, $PREFS;
    
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
    
    	$DSP->title = $LANG->line('global_variables');
    	$DSP->crumb = $LANG->line('global_variables');

    	$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('global_variables'));
    	
        $DSP->body .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_global_var'));
     
     	$variable_name = '';
     	$variable_data = '';
     	
     	$id = $IN->GBL('id');
     	
		$ub_id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
     
        if ($id != FALSE)
        {            
			$query = $DB->query("SELECT variable_name, variable_data, user_blog_id FROM exp_global_variables WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND variable_id = '".$DB->escape_str($id)."' ");
			
			if ($query->num_rows == 1)
			{
				if ($query->row['user_blog_id'] == $ub_id)
				{
            		$DSP->body .= $DSP->input_hidden('id', $id);
            		
					$variable_name = $query->row['variable_name'];
					$variable_data = $query->row['variable_data'];
				}
            }
        }
                
        $DSP->body .=  $DSP->div('box')
                      .$DSP->heading(BR.$LANG->line('variable_name', 'variable_name'), 5)
                      .$DSP->qdiv('itemWrapper',  $LANG->line('template_group_instructions'))
                      .$DSP->qdiv('itemWrapper',  $LANG->line('undersores_allowed'))
                      .$DSP->qdiv('itemWrapper', $DSP->input_text('variable_name', $variable_name, '20', '50', 'input', '240px'))
                      .$DSP->heading(BR.$LANG->line('variable_data'), 5)
             		  .$DSP->input_textarea('variable_data', $variable_data, '15', 'textarea', '100%')
                      .$DSP->div_c();
              
        $DSP->body .=  $DSP->div('itemWrapperTop');
        
        if ($id == FALSE)
            $DSP->body .= $DSP->input_submit($LANG->line('submit'));
        else
            $DSP->body .= $DSP->input_submit($LANG->line('update'));
        $DSP->body .= $DSP->div_c();
    
        $DSP->body .= $DSP->form_close();
    }
    /* END */
    




    /** -----------------------------
    /**  Insert/Update a Global Var
    /** -----------------------------*/
    
    function update_global_variable()
    {
    	global $IN, $DSP, $DB, $LANG, $SESS, $PREFS;
    
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
    

		if ($_POST['variable_name'] == '' || $_POST['variable_data'] == '')
		{
            return $DSP->error_message($LANG->line('all_fields_required'));
		}
        
        if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $_POST['variable_name']))
        {
            return $DSP->error_message($LANG->line('illegal_characters'));
        }
        
        if (in_array($_POST['variable_name'], $this->reserved_vars))
        {
            return $DSP->error_message($LANG->line('reserved_name'));
        }
    		
     	$id = $IN->GBL('id');
     	
		$ub_id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
     	
     
        if ($id != FALSE)
        {            
			$DB->query("UPDATE exp_global_variables SET variable_name = '".$DB->escape_str($_POST['variable_name'])."', variable_data = '".$DB->escape_str($_POST['variable_data'])."' WHERE variable_id = '$id' AND user_blog_id = '$ub_id'");
        
        	$msg = $LANG->line('global_var_updated');
        }
		else
		{
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_global_variables WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND variable_name = '".$DB->escape_str($_POST['variable_name'])."'");

			if ($query->row['count'] > 0)
			{
				return $DSP->error_message($LANG->line('duplicate_var_name'));
			}
			
			$DB->query("INSERT INTO exp_global_variables (variable_id, site_id, variable_name, variable_data, user_blog_id) VALUES ('', '".$DB->escape_str($PREFS->ini('site_id'))."', '".$DB->escape_str($_POST['variable_name'])."',  '".$DB->escape_str($_POST['variable_data'])."', '$ub_id')");

        	$msg = $LANG->line('global_var_created');
		}

		return $this->global_variables($msg);
    }
    /* END */



    /** -----------------------------
    /**  Global Var Delete Conf
    /** -----------------------------*/

	function global_variable_delete_conf()
	{
        global $DSP, $DB, $IN, $LANG, $SESS;
        
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
    
        $DSP->title  = $LANG->line('delete_global_variable');        
        $DSP->crumb  = $LANG->line('delete_global_variable');        
    
        if ( ! $id = $IN->GBL('id'))
        {
            return false;
        }
             	
		$ub_id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
     
		$query = $DB->query("SELECT variable_name FROM exp_global_variables WHERE variable_id = '".$DB->escape_str($id)."' AND user_blog_id = '".$DB->escape_str($ub_id)."' ");
		
		if ($query->num_rows == 0)
		{
			return false;
		}
        

		$DSP->body = $DSP->delete_confirmation(
										array(
												'url'		=> 'C=templates'.AMP.'M=do_delete_global_var',
												'heading'	=> 'delete_global_variable',
												'message'	=> 'delete_this_variable',
												'item'		=> $query->row['variable_name'],
												'extra'		=> '',
												'hidden'	=> array('id' => $id)
											)
										);	       
	}
	/* END */



    /** -----------------------------
    /**  Delete Global Variable
    /** -----------------------------*/

	function delete_global_variable()
	{
        global $DSP, $DB, $IN, $LANG, $SESS;
        
        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
            if ( ! $DSP->allowed_group('can_admin_templates'))
            {
                return $DSP->no_access_message();
            }
        }
    
        if ( ! $id = $IN->GBL('id', 'POST'))
        {
            return false;
        }
             	
		$ub_id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
     
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_global_variables WHERE variable_id = '".$DB->escape_str($id)."' AND user_blog_id = '".$DB->escape_str($ub_id)."' ");
		
		if ($query->row['count'] == 0)
		{
			return false;
		}
        
		$DB->query("DELETE FROM exp_global_variables WHERE variable_id = '".$DB->escape_str($id)."' AND user_blog_id = '".$DB->escape_str($ub_id)."' ");


		return $this->global_variables($LANG->line('variable_deleted'));
	}
	/* END */


    
}
// END CLASS
?>