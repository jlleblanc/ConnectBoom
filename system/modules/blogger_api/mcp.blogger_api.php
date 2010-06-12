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
 File: mcp.blogger_api.php
-----------------------------------------------------
 Purpose: Blogger API class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Blogger_api_CP {

    var $version = '1.0';
    
    /** -------------------------------------------
    /**  Constructor
    /** -------------------------------------------*/

	function Blogger_api_CP ($switch = TRUE)
	{
		global $IN, $DB;
		
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Blogger_api'");
        
        if ($query->row['count'] == 0)
        {
        	return;
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
                        
        $DSP->title = $LANG->line('blogger_api_module_name');
        $DSP->crumb = $LANG->line('blogger_api_module_name');
        
		$DSP->right_crumb($LANG->line('blogger_create_new'), BASE.AMP.'C=modules'.AMP.'M=blogger_api'.AMP.'P=create');
       
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('bloger_configurations')); 
        
        if ($msg != '')
        {
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
        }
                
        $qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $api_url = $FNS->fetch_site_index(0, 0).$qs.'ACT='.$FNS->fetch_action_id('Blogger_api', 'incoming');		
        
        $query = $DB->query("SELECT blogger_pref_name, blogger_id FROM exp_blogger");
        
        $DSP->body	.=	$DSP->toggle();
                
        $DSP->body	.=	$DSP->form_open(
        								array(
        										'action' => 'C=modules'.AMP.'M=blogger_api'.AMP.'P=delete_confirm', 
        										'name'	=> 'target',
        										'id'	=> 'target'
        									)
        								);

        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr().
						$DSP->table_qcell('tableHeadingAlt', 
											array(
													$LANG->line('blogger_config_name').'/'.$LANG->line('edit'),
													$LANG->line('blogger_config_url'),
													$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('delete').NBS.NBS
												 )
											).
						$DSP->tr_c();
		
		$i = 0;

		foreach ($query->result as $row)
		{				
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
                      
            $DSP->body .= $DSP->tr();
            
            $url = $api_url.'&id='.$row['blogger_id'];
            
            $DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold',
            														$DSP->anchor(BASE.AMP.'C=modules'.
            													   AMP.'M=blogger_api'.
            													   AMP.'P=modify'.
            													   AMP.'id='.$row['blogger_id'],
            													   $row['blogger_pref_name'])), '18%');       													   

            $DSP->body .= $DSP->table_qcell($style,
												$DSP->span('default', 'style="left:-9000px;position:absolute;"'). // hides the label from everyone but aural screen readers
												$LANG->line('blogger_config_url', $row['blogger_pref_name'].'_'.$row['blogger_id']).
												$DSP->span_c().
												$DSP->input_text($row['blogger_pref_name'].'_'.$row['blogger_id'], $url, '20', '400', 'input', '90%', "readonly='readonly'"),
											'67%');
                                   													   
            $DSP->body .= $DSP->table_qcell($style, ($row['blogger_id'] == '1') ? ' -- ' : $DSP->input_checkbox('toggle[]', $row['blogger_id']), '15%');
											
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
        global $IN, $DSP, $LANG, $DB;           
        
        $id = ( ! $IN->GBL('id', 'GET')) ? $id : $IN->GBL('id');
        
        if ($id == '')
        {
        	return $this->homepage();
        }
        
        /** ----------------------------
        /**  Form Values
        /** ----------------------------*/
        
        $field_id		= '1:2';
        $pref_name		= '';
        $block_entry	= 'n';
        $parse_type		= 'y';
        $text_format	= 'false';
        $html_format	= 'safe';
        
        if ($id != 'new')
        {
        	$query = $DB->query("SELECT * FROM exp_blogger WHERE blogger_id = '".$DB->escape_str($id)."'");
        	
        	if ($query->num_rows == 0)
        	{
        		return $this->homepage();
        	}
        	
        	foreach($query->row as $name => $pref)
        	{
        		$name    = str_replace('blogger_', '', $name);
        		${$name} = $pref;
        	}	
        }
                
        $DSP->title = $LANG->line('blogger_api_module_name');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blogger_api', $LANG->line('blogger_api_module_name'));
		$DSP->crumb .= ($id == 'new') ? $DSP->crumb_item($LANG->line('new_config')) : $DSP->crumb_item($LANG->line('modify_config'));
		
		$DSP->body .= ($id == 'new') ? $DSP->qdiv('tableHeading', $LANG->line('new_config')) : $DSP->qdiv('tableHeading', $LANG->line('modify_config'));
		
        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=blogger_api'.AMP.'P=save'));
        $DSP->body	.=	$DSP->input_hidden('id', $id);
        
        /** ---------------------------
    	/**  Fetch Weblogs
    	/** ---------------------------*/
    	
    	$weblog_array = array();
    	
    	$sql = "SELECT weblog_id, field_group, blog_title FROM exp_weblogs ";
				
		if (USER_BLOG !== FALSE)
		{
			$sql .= " WHERE exp_weblogs.weblog_id = '".UB_BLOG_ID."'";
		}
		else
		{
			$sql .= " WHERE exp_weblogs.is_user_blog = 'n'";
		}
        
        $query = $DB->query($sql." ORDER BY blog_title");

        if ($query->num_rows > 0)
        {            
            foreach ($query->result as $row)
            {
                $weblog_array[$row['weblog_id']] = array($row['field_group'], $row['blog_title']);
            }
        }
        
        /** ---------------------------
    	/**  Fetch Fields
    	/** ---------------------------*/
    	
    	$field_array = array();
    	
    	$query = $DB->query("SELECT field_id, group_id, field_name 
    						  FROM exp_weblog_fields 
    						  WHERE field_type = 'textarea'
    						  ORDER BY field_name");
    	
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$field_array[] = array($row['field_id'], $row['group_id'], $row['field_name']);
        	}
        }
        
        /** ---------------------------
    	/**  Fields to Weblogs
    	/** ---------------------------*/
    	
    	$weblog_fields = array();
    	
    	foreach($weblog_array as $weblog_id => $meta_weblog)
    	{
    		for($i = 0; $i < sizeof($field_array); $i++)
    		{
    			if ($field_array[$i]['1'] == $meta_weblog['0'])
    			{
    				$weblog_fields[$weblog_id][] = array($field_array[$i]['0'], $field_array[$i]['2']);
    			}
    		}
    	}
    	
    	/** ---------------------------
    	/**  Begin Creating Form
    	/** ---------------------------*/
    					
		$r  =	$DSP->table('tableBorder', '0', '', '100%');
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
		$r .= $LANG->line('blogger_pref_name', 'pref_name');
		$r .= $DSP->div_c();
		// $r .= $DSP->qdiv('subtext', $LANG->line($sub));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_text('pref_name', $pref_name, '20', '120', 'input', '100%');
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// DEFAULT FIELD ID
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('blogger_default_field', 'field_id');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('blogger_default_field_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('field_id');
    	
    	$t = 1;
    	
    	$x = explode(':',$field_id);
    	$weblog_match = ( ! isset($x['1'])) ? '1' : $x['0'];
    	$field_match  = ( ! isset($x['1'])) ? $x['0'] : $x['1'];
    	
    	foreach($weblog_fields as $weblog_id => $field_data)
    	{
    		$p = '';
    		$t++;
    		for($i = 0; $i < sizeof($field_data); $i++)
    		{
    			$selected = ($weblog_id == $weblog_match && $field_data[$i]['0'] == $field_match) ? 'y' : '';
    			$p .= $DSP->input_select_option($weblog_id.':'.$field_data[$i]['0'], $weblog_array[$weblog_id]['1'].' : '.$field_data[$i]['1'], $selected);
    		}
    		
    		if ($p != '')
    		{
    			$r .= $p;
    			
    			if ($t <= sizeof($weblog_fields))
    			{
    				$r .= $DSP->input_select_option('',NBS.'----------'.NBS);
    			}
    		}
    	}
    	
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// BLOCK ENTRY
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('blogger_block_entry', 'block_entry');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('blogger_block_entry_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('block_entry');
    	$r .= $DSP->input_select_option('yes', $LANG->line('yes'), ($block_entry == 'y') ? 'y' : '');
    	$r .= $DSP->input_select_option('no', $LANG->line('no'), ($block_entry == 'n') ? 'y' : '');
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// PARSE TYPE
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('blogger_parse_type', 'parse_type');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('blogger_parse_type_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('parse_type');
    	$r .= $DSP->input_select_option('y', $LANG->line('yes'), ($parse_type == 'y') ? 'y' : '');
    	$r .= $DSP->input_select_option('n', $LANG->line('no'), ($parse_type == 'n') ? 'y' : '');
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// TEXT FORMAT
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('blogger_text_format', 'text_format');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('blogger_text_format_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('text_format');
    	$r .= $DSP->input_select_option('y', $LANG->line('yes'), ($block_entry == 'y') ? 'y' : '');
    	$r .= $DSP->input_select_option('n', $LANG->line('no'), ($block_entry == 'n') ? 'y' : '');
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i++;
		$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		// HTML FORMAT
		$r .= $DSP->td($style, '50%', '');
		$r .= $DSP->div('defaultBold');
		$r .= $LANG->line('blogger_html_format', 'html_format');
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('subtext', $LANG->line('blogger_html_format_subtext'));
    	$r .= $DSP->td_c();
    	
    	$r .= $DSP->td($style, '50%', '');
    	$r .= $DSP->input_select_header('html_format');    	
    	$r .= $DSP->input_select_option('none', $LANG->line('html_format_none'), ($html_format == 'none') ? 'y' : '');
    	$r .= $DSP->input_select_option('safe', $LANG->line('html_format_safe'), ($html_format == 'safe') ? 'y' : '');
    	$r .= $DSP->input_select_option('all', $LANG->line('html_format_all'), ($html_format == 'all') ? 'y' : '');
    	$r .= $DSP->input_select_footer();
    	$r .= $DSP->td_c();
		$r .= $DSP->tr_c();

		$r .= $DSP->table_c();
		
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit(($id == 'new') ? $LANG->line('submit') : $LANG->line('update')));
        
		$DSP->body .= $r.$DSP->form_close();       
	}
	/* END */
      

	/** -------------------------------------------
    /**  Save Configuration
    /** -------------------------------------------*/

    function save_configuration()
    {
    	global $IN, $DSP, $LANG, $DB;
    	
    	$required	= array('id', 'pref_name', 'field_id', 'block_entry', 'parse_type', 'text_format', 'html_format');
    	$data		= array();
    	
    	foreach($required as $var)
    	{
    		if ( ! isset($_POST[$var]) OR $_POST[$var] == '')
    		{
    			return $OUT->show_user_error('submission', $LANG->line('blogger_mising_fields'));
    		}
    		
    		$data['blogger_'.$var] = $_POST[$var];
    	}
    	
    	if ($_POST['id'] == 'new' )
    	{
    		$data['blogger_id'] = '';    		
    		$DB->query($DB->insert_string('exp_blogger', $data));
    		$message = $LANG->line('configuration_created');
    	}
    	else
    	{    		
			$DB->query($DB->update_string('exp_blogger', $data, "blogger_id = '".$DB->escape_str($_POST['id'])."'"));
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
        
        $DSP->title = $LANG->line('blogger_api_module_name');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=blogger_api', $LANG->line('blogger_api_module_name'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('delete'));

        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=blogger_api'.AMP.'P=delete'));
        
        $i = 0;
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $DSP->body	.=	$DSP->input_hidden('delete[]', $val);
                
                $i++;
            }        
        }
        
		$DSP->body .= $DSP->heading($DSP->qspan('alert', $LANG->line('blogger_delete_confirm')));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('blogger_delete_question'));
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
                $ids[] = "blogger_id = '".$DB->escape_str($val)."'";
            }        
        }
        
        $IDS = implode(" OR ", $ids);
        
        $DB->query("DELETE FROM exp_blogger WHERE ".$IDS);
    
        $message = (count($ids) == 1) ? $LANG->line('blogger_deleted') : $LANG->line('bloggers_deleted');


        return $this->homepage($message);
    }
    /* END */ 
    
	

    /** -------------------------------------------
    /**  Module installer
    /** -------------------------------------------*/

    function blogger_api_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules 
        		  (module_id, module_name, module_version, has_cp_backend) 
        		  VALUES 
        		  ('', 'Blogger_api', '$this->version', 'y')";
        		  
    	$sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Blogger_api', 'incoming')";
    	$sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Blogger_api', 'edit_uri_output')";
    	$sql[] = "CREATE TABLE IF NOT EXISTS `exp_blogger` (
    			 `blogger_id` int(5) unsigned NOT NULL auto_increment,
    			 `blogger_pref_name` varchar(80) NOT NULL default '',
    			 `blogger_field_id` varchar(10) NOT NULL default '1:2',
    			 `blogger_block_entry` char(1) NOT NULL default 'n',
    			 `blogger_parse_type` char(1) NOT NULL default 'y',
    			 `blogger_text_format` char(1) NOT NULL default 'n',
    			 `blogger_html_format` varchar(50) NOT NULL default '',
    			  PRIMARY KEY (`blogger_id`));";
 		$sql[] = "INSERT INTO exp_blogger (blogger_id, blogger_pref_name, blogger_html_format) VALUES ('', 'Default', 'safe')";

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

    function blogger_api_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Blogger_api'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Blogger_api'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Blogger_api'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Blogger_api'";
        $sql[] = "DROP TABLE IF EXISTS exp_blogger";

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