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
 File: cp.members.php
-----------------------------------------------------
 Purpose: Member management functions
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Members {


    // Default member groups.  We used these for translation purposes
    
    var $english = array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins');
    
    var $perpage = 50;  // Number of results on the "View all member" page
    
    var $no_delete = array('1', '2', '3', '4'); // Member groups that can not be deleted
	

    /** -----------------------------
    /**  Constructor
    /** -----------------------------*/

    function Members()
    {
        global $LANG;
        
        // Fetch the language files
        
        $LANG->fetch_language_file('myaccount');
        $LANG->fetch_language_file('members');
    }
    /* END */
    
        
    /** -----------------------------
    /**  View all members
    /** -----------------------------*/
    
    function view_all_members($message = '')
    {  
        global $IN, $LANG, $DSP, $LOC, $DB, $PREFS;
                
        // These variables are only set when one of the pull-down menus is used
        // We use it to construct the SQL query with
        
        $group_id   = $IN->GBL('group_id', 'GP');
        $order      = $IN->GBL('order', 'GP');        
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_members");
              
        $total_members = $query->row['count'];
        
        // Begin building the page output
        
        $r = $DSP->qdiv('tableHeading', $LANG->line('view_members'));
        
        if ($message != '')
        {
            $r .= $DSP->qdiv('box', $message);
        }
        
        // Declare the "filtering" form
        
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=view_members'));
        
		$DSP->right_crumb($LANG->line('new_member_search'), BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=member_search');

        // Table start
                

        $r .= $DSP->div('box');
        $r .= $DSP->table('', '0', '', '100%').
              $DSP->tr().
              $DSP->td('itemWrapper', '', '5').NL;
        
        // Member group selection pull-down menu
        
        $r .= $DSP->input_select_header('group_id').
              $DSP->input_select_option('', $LANG->line('member_groups')).
              $DSP->input_select_option('', $LANG->line('all'));
        
        // Fetch the names of all member groups and write each one in an <option> field
        
        $query = $DB->query("SELECT group_title, group_id FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' order by group_title");
             
        foreach ($query->result as $row)
        {
			$group_name = $row['group_title'];
					
			if (in_array($group_name, $this->english))
			{
				$group_name = $LANG->line(strtolower(str_replace(" ", "_", $group_name)));
			}
                
            $r .= $DSP->input_select_option($row['group_id'], $group_name, ($group_id == $row['group_id']) ? 1 : '');
        }        

        $r .= $DSP->input_select_footer().
              $DSP->nbs(2);   
                               
        
        // "display order" pull-down menu
        
              $sel_1  = ($order == 'desc')              ? 1 : '';          
              $sel_2  = ($order == 'asc')               ? 1 : '';          
              $sel_3  = ($order == 'username')          ? 1 : '';          
              $sel_4  = ($order == 'username_desc')     ? 1 : '';          
              $sel_5  = ($order == 'screen_name')       ? 1 : '';          
              $sel_6  = ($order == 'screen_name_desc')  ? 1 : '';          
              $sel_7  = ($order == 'email')             ? 1 : '';          
              $sel_8  = ($order == 'email_desc')        ? 1 : '';          
                
        
        $r .= $DSP->input_select_header('order').
              $DSP->input_select_option('desc',  $LANG->line('sort_order'), $sel_1).
              $DSP->input_select_option('asc',   $LANG->line('ascending'), $sel_2).
              $DSP->input_select_option('desc',  $LANG->line('descending'), $sel_1).
              $DSP->input_select_option('username_asc', $LANG->line('username_asc'), $sel_3).
              $DSP->input_select_option('username_desc', $LANG->line('username_desc'), $sel_4).
              $DSP->input_select_option('screen_name_asc', $LANG->line('screen_name_asc'), $sel_5).
              $DSP->input_select_option('screen_name_desc', $LANG->line('screen_name_desc'), $sel_6).
              $DSP->input_select_option('email_asc', $LANG->line('email_asc'), $sel_7).
              $DSP->input_select_option('email_desc', $LANG->line('email_desc'), $sel_8).
              $DSP->input_select_footer().
              $DSP->nbs(2);
                
        
        // Submit button and close filtering form

        $r .= $DSP->input_submit($LANG->line('submit'), 'submit');
                            
        $r .= $DSP->td_c().
              $DSP->td('defaultRight', '', 2).
              $DSP->heading($LANG->line('total_members').NBS.NBS.$total_members.NBS.NBS.NBS.NBS.NBS, 5).
              $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();

        $r .= $DSP->div_c();



        $r .= $DSP->form_close();
        
        // Build the SQL query as well as the query string for the paginate links
        
		$pageurl = BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=view_members';
        
        if ($group_id)
        {
        	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id = ".$group_id);
              
        	$total_count = $query->row['count'];
        }
        else
        {
        	$total_count = $total_members;
       	}
        
        // No result?  Show the "no results" message
        if ($total_count == 0)
        {            
            $r .= $DSP->qdiv('', BR.$LANG->line('no_members_matching_that_criteria'));        
        
            return $DSP->set_return_data(   $LANG->line('view_members'),
                                            $r,
                                            $LANG->line('view_members')
                                        );    
        }        
        
        // Get the current row number and add the LIMIT clause to the SQL query
        
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }

        $sql = "SELECT member_id FROM exp_members ";
        
        if ($group_id)
        {
            $sql .= " WHERE group_id = $group_id";
            
            $pageurl .= AMP.'group_id='.$group_id;
        }
        
                
        $o_sql = " ORDER BY ";        
        
        if ($order)
        {
            $pageurl .= AMP.'order='.$order;
        
            switch ($order)
            {
                case 'asc'              : $o_sql .= "join_date asc";
                    break;
                case 'desc'             : $o_sql .= "join_date desc";
                    break;
                case 'username_asc'     : $o_sql .= "username asc";
                    break;
                case 'username_desc'    : $o_sql .= "username desc";
                    break;
                case 'screen_name_asc'  : $o_sql .= "screen_name asc";
                    break;
                case 'screen_name_desc' : $o_sql .= "screen_name desc";
                    break;
                case 'email_asc'        : $o_sql .= "email asc";
                    break;
                case 'email_desc'       : $o_sql .= "email desc";
                    break;
                default                 : $o_sql .= "join_date desc";
            }
        }
        else
        {
            $o_sql .= "join_date desc";
        }
        
        $query = $DB->query($sql.$o_sql." LIMIT ".$rownum.", ".$this->perpage);  
        
        $sql = "SELECT exp_members.username,
                       exp_members.member_id,
                       exp_members.screen_name,
                       exp_members.email,
                       exp_members.join_date,
                       exp_members.last_visit,
                       exp_member_groups.group_title
                FROM   exp_members, exp_member_groups
                WHERE  exp_members.group_id = exp_member_groups.group_id 
                AND    exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                AND    exp_members.member_id IN ("; 

		foreach ($query->result as $row)
		{
			$sql .= $row['member_id'].',';
		}
		
		$sql = substr($sql, 0, -1).')';

        $query = $DB->query($sql.$o_sql);         
        
		// "select all" checkbox

        $r .= $DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
        $r .= $DSP->magic_checkboxes();

        // Declare the "delete" form
        
        $r .= $DSP->form_open(
        						array(
        								'action'	=> 'C=admin'.AMP.'M=members'.AMP.'P=mbr_conf', 
        								'name'		=> 'target',
        								'id'		=> 'target'
        
        							)
        					);

        // Build the table heading       
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('username')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('screen_name')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('email')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('join_date')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('last_visit')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('member_group')).
              $DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              $DSP->tr_c();
                
        // Loop through the query result and write each table row 
               
        $i = 0;
        
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
                      
            $r .= $DSP->tr();
            
            // Username
            
            $r .= $DSP->table_qcell($style, 
                                    $DSP->anchor(
                                                  BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], 
                                                  '<b>'.$row['username'].'</b>'
                                                )
                                    );
            // Screen name
            
            $screen = ($row['screen_name'] == '') ? "--" : '<b>'.$row['screen_name'].'</b>';
            
            $r .= $DSP->table_qcell($style, $screen);
            
             
            // Email
            
            $r .= $DSP->table_qcell($style, 
                                    $DSP->mailto($row['email'], $row['email'])
                                    );

            // Join date

            $r .= $DSP->td($style).
                  $LOC->convert_timestamp('%Y', $row['join_date']).'-'.
                  $LOC->convert_timestamp('%m', $row['join_date']).'-'.
                  $LOC->convert_timestamp('%d', $row['join_date']).
                  $DSP->td_c();
                  
            // Last visit date

            $r .= $DSP->td($style);
            
                if ($row['last_visit'] != 0)
                {            
                    $r .= $LOC->set_human_time($row['last_visit']);
                }
                else
                {
                    $r .= "--";               
                }
                                      
            $r .= $DSP->td_c();
            
            // Member group
            
            $r .= $DSP->td($style);
            
			$group_name = $row['group_title'];
					
			if (in_array($group_name, $this->english))
			{
				$group_name = $LANG->line(strtolower(str_replace(" ", "_", $group_name)));
			}
            
            $r .= $group_name;
                
            $r .= $DSP->td_c();
            
            // Delete checkbox
            
            $r .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['member_id'], '', ' id="delete_box_'.$row['member_id'].'"'));
                  
            $r .= $DSP->tr_c();
            
        } // End foreach
        

        $r .= $DSP->table_c();
                        
        $r .= $DSP->table('', '0', '', '98%');
        $r .= $DSP->tr().
              $DSP->td();
               
        // Pass the relevant data to the paginate class so it can display the "next page" links
        
        $r .=  $DSP->div('crumblinks').
               $DSP->pager(
                            $pageurl,
                            $total_count,
                            $this->perpage,
                            $rownum,
                            'rownum'
                          ).
              $DSP->div_c().
              $DSP->td_c().
              $DSP->td('defaultRight');
        
        // Delete button
        
        $r .= $DSP->input_submit($LANG->line('submit'));
        
        $r .= NBS.$DSP->input_select_header('action');

        if ($group_id == '4' && $PREFS->ini('req_mbr_activation') == 'email' && $DSP->allowed_group('can_admin_members'))
        {
        	$r .= $DSP->input_select_option('resend', $LANG->line('resend_activation_emails'));
        }
        
        $r .= $DSP->input_select_option('delete', $LANG->line('delete_selected')).
        	  $DSP->input_select_footer().
        	  $DSP->td_c().
              $DSP->tr_c();
              
        // Table end
        
        $r .= $DSP->table_c().
              $DSP->form_close();

        // Set output data        

        $DSP->title = $LANG->line('view_members');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('view_members'));
        $DSP->body  = $r;                                 
    }
    /* END */
    
    /** -----------------------------------------------------------
    /**  Member Action Confirm
    /** -----------------------------------------------------------*/
    
    function member_confirm()
    {
    	if (isset($_POST['action']) && $_POST['action'] == 'resend')
    	{
    		$this->resend_activation_emails();
    	}
    	else
    	{
    		$this->member_delete_confirm();
    	}
    }
    /* END */


    /** -----------------------------------------------------------
    /**  Resend Pending Member's Activation Emails
    /** -----------------------------------------------------------*/

    function resend_activation_emails()
    { 
        global $DSP, $LANG, $DB, $PREFS, $IN, $FNS, $REGX;
        
        if ( ! $DSP->allowed_group('can_admin_members') OR $PREFS->ini('req_mbr_activation') !== 'email')
        {
            return $DSP->no_access_message();
        }
        
        if ($IN->GBL('mid', 'GET') !== FALSE)
        {
        	$_POST['toggle'] = $IN->GBL('mid', 'GET');
        }
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->view_all_members();
        }

        $damned = array();
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {   
                $damned[] = $DB->escape_str($val);
            }        
        }
        
        if (sizeof($damned) == 0)
        {
        	return $this->view_all_members();
        }
        
        $query = $DB->query("SELECT screen_name, username, email, authcode FROM exp_members WHERE member_id IN ('".implode("','", $damned)."')");
        
        if ($query->num_rows == 0)
        {
        	return $this->view_all_members();
        }
        
        $qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
			
		$action_id  = $FNS->fetch_action_id('Member', 'activate_member');
		
		$template = $FNS->fetch_email_template('mbr_activation_instructions');
		
		$swap = array(
						'site_name'			=> stripslashes($PREFS->ini('site_name')),
						'site_url'			=> $PREFS->ini('site_url')
					 );
					 
		if ( ! class_exists('EEmail'))
		{
			require PATH_CORE.'core.email'.EXT;
		}
		
		$email = new EEmail;
		
		foreach($query->result as $row)
		{		
			$swap['name']			= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$swap['activation_url']	= $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$row['authcode'];
			$swap['username']		= $row['username'];
			$swap['email']			= $row['email'];
												
			/** ----------------------------
			/**  Send email
			/** ----------------------------*/
			
			$email->initialize();
			$email->wordwrap = true;
			$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
			$email->to($row['email']); 
			$email->subject($FNS->var_swap($template['title'], $swap));	
			$email->message($REGX->entities_to_ascii($FNS->var_swap($template['data'], $swap)));		
			$email->Send();
		}
        
        return $this->view_all_members($DSP->qdiv('success', $LANG->line(($IN->GBL('mid', 'GET') !== FALSE) ? 'activation_email_resent' : 'activation_emails_resent')));
    }
    /* END */
    
    
    /** -----------------------------------------------------------
    /**  Delete Member (confirm)
    /** -----------------------------------------------------------*/
    // Warning message if you try to delete members
    //-----------------------------------------------------------

    function member_delete_confirm()
    { 
        global $IN, $DSP, $LANG, $DB, $SESS, $PREFS;
        
        if ( ! $DSP->allowed_group('can_delete_members'))
        {
            return $DSP->no_access_message();
        }
        
        $from_myaccount = FALSE;
		$entries_exit = FALSE;
        
        if ($IN->GBL('mid', 'GET') !== FALSE)
        {
        	$from_myaccount = TRUE;
        	$_POST['toggle'] = $IN->GBL('mid', 'GET');
        }
                        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->view_all_members();
        }

        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=mbr_delete'));
        
        $i = 0;
        $damned = array();
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $r .= $DSP->input_hidden('delete[]', $val);
                
                // Is the user trying to delete himself?
                
                if ($SESS->userdata('member_id') == $val)
                {
                	return $DSP->error_message($LANG->line('can_not_delete_self'));
                }
                
                $damned[] = $DB->escape_str($val);
                $i++;
            }        
        }
        
        $r .= $DSP->qdiv('alertHeading', $LANG->line('delete_member'));
        $r .= $DSP->div('box');
        
        if ($i == 1)
        {
			$r .= $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('delete_member_confirm').'</b>');
			
			$query = $DB->query("SELECT screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($damned['0'])."'");
			
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $query->row['screen_name']));
		}
		else
        {
			$r .= '<b>'.$LANG->line('delete_members_confirm').'</b>';
		}
		
        $r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('action_can_not_be_undone')));
        
        /** ----------------------------------------------------------        
        /**  Do the users being deleted have entries assigned to them?
        /** ----------------------------------------------------------*/
        
        $sql = "SELECT COUNT(entry_id) AS count FROM exp_weblog_titles WHERE author_id ";
        
        if ($i == 1)
        {
			$sqlb =  "= '".$DB->escape_str($damned['0'])."'";
		}
		else
		{
			$sqlb = " IN ('".implode("','",$damned)."')";
		}
		
		$query = $DB->query($sql.$sqlb);

		if ($query->row['count'] > 0)
		{
			$entries_exit = TRUE;
			$r .= $DSP->input_hidden('entries_exit', 'yes'); 
		}

		if ($DB->table_exists('exp_gallery_entries') === TRUE)
		{
			$sql = "SELECT COUNT(entry_id) AS count FROM exp_gallery_entries WHERE author_id ";
			$query = $DB->query($sql.$sqlb);

			if ($query->row['count'] > 0)
			{
				$entries_exit = TRUE;
				$r .= $DSP->input_hidden('gallery_entries_exit', 'yes'); 
			}
		}
		
       /** ----------------------------------------------------------        
        /**  If so, fetch the member names for reassigment
        /** ----------------------------------------------------------*/

		if ($entries_exit == TRUE)
		{
			// Fetch the member_group of each user being deleted
			$sql = "SELECT group_id FROM exp_members WHERE member_id ";
        
        	if ($i == 1)
        	{
        		$sql .= " = '".$DB->escape_str($damned['0'])."'";
        	}
        	else
        	{
        		$sql .= " IN ('".implode("','",$damned)."')";
        	}
        	
			$query = $DB->query($sql);
			
			$group_ids[] = 1;

			if ($query->num_rows > 0)
			{
				foreach($query->result as $row)
				{
					$group_ids[] = $row['group_id'];
				}
			}
			
			$group_ids = array_unique($group_ids);
			
			// Find Valid Member Replacements
			$query = $DB->query("SELECT exp_members.member_id, username, screen_name 
								FROM exp_members
								LEFT JOIN exp_member_groups on exp_member_groups.group_id = exp_members.group_id
								WHERE exp_member_groups.group_id IN (".implode(",",$group_ids).")
								AND exp_members.member_id NOT IN ('".implode("','",$damned)."')
								AND (exp_members.in_authorlist = 'y' OR exp_member_groups.include_in_authorlist = 'y')
								AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
								ORDER BY screen_name asc, username asc");							

			if ($query->num_rows == 0)
			{
				$query = $DB->query("SELECT member_id, username, screen_name 
								FROM exp_members
								WHERE group_id = 1 
								AND member_id NOT IN ('".implode("','",$damned)."')
								ORDER BY screen_name asc, username asc");
			}

			$r .= $DSP->div('itemWrapper');
			$r .= $DSP->div('defaultBold');
			$r .= ($i == 1) ? $LANG->line('heir_to_member_entries') : $LANG->line('heir_to_members_entries');
			$r .= $DSP->div_c();
			
			$r .= $DSP->div('itemWrapper');
			$r .= $DSP->input_select_header('heir');
			
			foreach($query->result as $row)
			{
				$r .= $DSP->input_select_option($row['member_id'], ($row['screen_name'] != '') ? $row['screen_name'] : $row['username']);
			}
			
			$r .= $DSP->input_select_footer();
			$r .= $DSP->div_c();
			$r .= $DSP->div_c();
		}        
                      
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('delete'))).
              $DSP->div_c().
              $DSP->form_close();


        $DSP->title = $LANG->line('delete_member');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('delete_member'));         
        $DSP->body  = $r;
    }
    /* END */
    
    
    /** ----------------------------------------------
    /**  Login as Member - SuperAdmins only!
    /** ----------------------------------------------*/

    function login_as_member()
    { 
        global $IN, $DSP, $LANG, $DB, $SESS, $PREFS, $FNS, $LOG;
        
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message();
        }
        
        if (($id = $IN->GBL('mid', 'GET')) === FALSE)
        {
        	return $DSP->no_access_message();
        }
        
        if ($SESS->userdata['member_id'] == $id)
        {
        	return $DSP->no_access_message();
        }
        
        /** ----------------------------------------
        /**  Fetch member data
        /** ----------------------------------------*/

        $sql = "SELECT exp_members.screen_name, exp_member_groups.can_access_cp
                FROM   exp_members, exp_member_groups
                WHERE  member_id = '".$DB->escape_str($id)."'
                AND	   exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                AND    exp_members.group_id = exp_member_groups.group_id";
                
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
        	return $DSP->no_access_message();
        }
        
        $DSP->title = $LANG->line('login_as_member');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('login_as_member'));
        
        
		/** ----------------------------------------
        /**  Create Our Little Redirect Form
        /** ----------------------------------------*/
        
		$r  = $DSP->form_open(
							  array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=do_login_as_member'),
							  array('mid' => $id)
							  );
							  
        $r .= $DSP->qdiv('default', '', 'menu_contents');
        
        $r .= $DSP->table('tableBorder', '0', '', '100%');
        
		$r .= $DSP->tr().
              $DSP->td('tableHeadingAlt', '', '2').$LANG->line('login_as_member').
              $DSP->td_c().
              $DSP->tr_c();
              
        $r .= $DSP->tr().
              $DSP->td('tableCellOne').
              $DSP->qdiv('alert', $LANG->line('action_can_not_be_undone')).
              $DSP->qdiv('itemWrapper', str_replace('%screen_name%', $query->row['screen_name'], $LANG->line('login_as_member_description'))).
              $DSP->td_c().
              $DSP->tr_c();
                
        $r .= $DSP->tr().
              $DSP->td('tableCellTwo');
              
        $r .= $DSP->qdiv('',
        				$DSP->input_radio('return_destination', 'site', 1).$DSP->nbs(3).
        				$LANG->line('site_homepage')
              			);
        
        if ($query->row['can_access_cp'] == 'y')
        {
        	$r .= $DSP->qdiv('',
        		  			$DSP->input_radio('return_destination', 'cp').$DSP->nbs(3).
        		  			$LANG->line('control_panel')
        	      );
        }
              			
		$r .= $DSP->qdiv('',
              			$DSP->input_radio('return_destination', 'other', '').$DSP->nbs(3).
              			$LANG->line('other').NBS.':'.NBS.$DSP->input_text('other_url', $FNS->fetch_site_index(), '30', '80', 'input', '500px')
              			);

        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->tr().
              $DSP->td('tableCellOne').
              $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit'), 'submit')).
              $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c().
              $DSP->div_c();
        
        $DSP->body = $r;
	}
	/* END */
    
    
    /** ----------------------------------------------
    /**  Login as Member - SuperAdmins only!
    /** ----------------------------------------------*/

    function do_login_as_member()
    { 
        global $IN, $DSP, $LANG, $DB, $SESS, $PREFS, $FNS, $LOG, $REGX;
        
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message();
        }
        
        if (($id = $IN->GBL('mid')) === FALSE)
        {
        	return $DSP->no_access_message();
        }
        
        if ($SESS->userdata['member_id'] == $id)
        {
        	return $DSP->no_access_message();
        }
        
        /** ----------------------------------------
        /**  Fetch member data
        /** ----------------------------------------*/

        $sql = "SELECT exp_members.username, exp_members.password, exp_members.unique_id, exp_members.member_id, exp_members.group_id, exp_member_groups.can_access_cp
                FROM   exp_members, exp_member_groups
                WHERE  member_id = '".$DB->escape_str($id)."'
                AND    exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                AND    exp_members.group_id = exp_member_groups.group_id";
                
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
        	return $DSP->no_access_message();
        }
        
        $LANG->fetch_language_file('login');
        
		/** --------------------------------------------------
        /**  Do we allow multiple logins on the same account?
        /** --------------------------------------------------*/
        
        if ($PREFS->ini('allow_multi_logins') == 'n')
        {
            // Kill old sessions first
        
            $SESS->gc_probability = 100;
            
            $SESS->delete_old_sessions();
        
            $expire = time() - $SESS->session_length;
            
            // See if there is a current session

            $result = $DB->query("SELECT ip_address, user_agent 
                                  FROM   exp_sessions 
                                  WHERE  member_id  = '".$query->row['member_id']."'
                                  AND    last_activity > $expire");
                                
            // If a session exists, trigger the error message
                               
            if ($result->num_rows == 1)
            {
                if ($SESS->userdata['ip_address'] != $result->row['ip_address'] || 
                    $SESS->userdata['user_agent'] != $result->row['user_agent'] )
                {
                    return $DSP->error_message($LANG->line('multi_login_warning'));                            
                }               
            } 
        }
        
        /** ----------------------------------------
        /**  Log the SuperAdmin login
        /** ----------------------------------------*/
        
        $LOG->log_action($LANG->line('login_as_user').':'.NBS.$query->row['username']);
        
        /** ----------------------------------------
        /**  Set cookies
        /** ----------------------------------------*/
        
        // Set cookie expiration to one year if the "remember me" button is clicked

        $expire = 0;
        $type = (isset($_POST['return_destination']) && $_POST['return_destination'] == 'cp') ? $PREFS->ini('admin_session_type') : $PREFS->ini('user_session_type');
        
		if ($type != 's')
		{
			$FNS->set_cookie($SESS->c_expire , time()+$expire, $expire);
			$FNS->set_cookie($SESS->c_uniqueid , $query->row['unique_id'], $expire);       
			$FNS->set_cookie($SESS->c_password , $query->row['password'],  $expire);   
			$FNS->set_cookie($SESS->c_anon , 1,  $expire);
		}
        
        /** ----------------------------------------
        /**  Create a new session
        /** ----------------------------------------*/

        $session_id = $SESS->create_new_session($query->row['member_id'], TRUE);
        
        /** ----------------------------------------
        /**  Delete old password lockouts
        /** ----------------------------------------*/
        
		$SESS->delete_password_lockout();

        /** ----------------------------------------
        /**  Redirect the user to the return page
        /** ----------------------------------------*/
        
        $return_path = $FNS->fetch_site_index();
        
		if (isset($_POST['return_destination']))
        {
        	if ($_POST['return_destination'] == 'cp')
        	{
        		$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
				$return_path = $PREFS->ini('cp_url', FALSE).'?S='.$s;
        	}
        	elseif ($_POST['return_destination'] == 'other' && isset($_POST['other_url']) && stristr($_POST['other_url'], 'http'))
        	{
        		$return_path = $REGX->xss_clean(strip_tags($_POST['other_url']));
        	}
        }
        
        $FNS->redirect($return_path);
        exit;
	}
	/* END */
    
    
    
    /** ---------------------------------------------
    /**  Delete Members
    /** ---------------------------------------------*/

    function member_delete()
    { 
        global $IN, $DSP, $PREFS, $LANG, $SESS, $FNS, $DB, $STAT, $EXT;
        

        if ( ! $DSP->allowed_group('can_delete_members'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $IN->GBL('delete', 'POST'))
        {
            return $this->view_all_members();
        }
            
        /** ---------------------------------------------
        /**  Fetch member ID numbers and build the query
        /** ---------------------------------------------*/

        $ids = array();
        $mids = array();
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val) AND $val != '')
            {
                $ids[] = "member_id = '".$DB->escape_str($val)."'";
                $mids[] = $DB->escape_str($val);
            }        
        }
        
        $IDS = implode(" OR ", $ids);

        // SAFETY CHECK
        // Let's fetch the Member Group ID of each member being deleted
        // If there is a Super Admin in the bunch we'll run a few more safeties
                
        $super_admins = 0;
                
        $query = $DB->query("SELECT group_id FROM exp_members WHERE ".$IDS);        
        
        foreach ($query->result as $row)
        {
            if ($query->row['group_id'] == 1)
            {
                $super_admins++;              
            }
        }        
        
        if ($super_admins > 0)
        {
            // You must be a Super Admin to delete a Super Admin
        
            if ($SESS->userdata['group_id'] != 1)
            {
                return $DSP->error_message($LANG->line('must_be_superadmin_to_delete_one'));
            }
            
            // You can't detete the only Super Admin   
                
            $query = $DB->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id = '1'");
            
            if ($super_admins >= $query->row['count'])
            {
                return $DSP->error_message($LANG->line('can_not_delete_super_admin'));
            }
        }
        
        // If we got this far we're clear to delete the members
    
        $DB->query("DELETE FROM exp_members WHERE ".$IDS);
        $DB->query("DELETE FROM exp_member_data WHERE ".$IDS);
        $DB->query("DELETE FROM exp_member_homepage WHERE ".$IDS);
        
        foreach($mids as $val)
        {
        	$message_query = $DB->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '$val' AND message_read = 'n'");
			$DB->query("DELETE FROM exp_message_copies WHERE sender_id = '$val'");
			$DB->query("DELETE FROM exp_message_data WHERE sender_id = '$val'");
			$DB->query("DELETE FROM exp_message_folders WHERE member_id = '$val'");
			$DB->query("DELETE FROM exp_message_listed WHERE member_id = '$val'");
			
			if ($message_query->num_rows > 0)
			{
				foreach($message_query->result as $row)
				{
					$count_query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
					$DB->query($DB->update_string('exp_members', array('private_messages' => $count_query->row['count']), "member_id = '".$row['recipient_id']."'"));
				}
			}
        }
 
        /** ----------------------------------
        /**  Are there forum posts to delete?
        /** ----------------------------------*/
        
		if ($PREFS->ini('forum_is_installed') == "y")
		{
			$DB->query("DELETE FROM exp_forum_subscriptions  WHERE ".$IDS); 
			$DB->query("DELETE FROM exp_forum_pollvotes  WHERE ".$IDS); 

			$IDS = str_replace('member_id', 'admin_member_id', $IDS);
			$DB->query("DELETE FROM exp_forum_administrators WHERE ".$IDS); 
			
			$IDS = str_replace('admin_member_id', 'mod_member_id', $IDS);			
			$DB->query("DELETE FROM exp_forum_moderators WHERE ".$IDS); 

			$IDS = str_replace('mod_member_id', 'author_id', $IDS);
			$DB->query("DELETE FROM exp_forum_topics WHERE ".$IDS);
			
			// Snag the affected topic id's before deleting the members for the update afterwards
			$query = $DB->query("SELECT topic_id FROM exp_forum_posts WHERE ".$IDS);
			
			if ($query->num_rows > 0)
			{
				$topic_ids = array();
				
				foreach ($query->result as $row)
				{
					$topic_ids[] = $row['topic_id'];
				}
				
				$topic_ids = array_unique($topic_ids);
			}
			
			$DB->query("DELETE FROM exp_forum_posts  WHERE ".$IDS); 
			$DB->query("DELETE FROM exp_forum_polls  WHERE ".$IDS); 

			// Kill any attachments
			$query = $DB->query("SELECT attachment_id, filehash, extension, board_id FROM exp_forum_attachments WHERE ".str_replace('author_id', 'member_id', $IDS));
			
			if ($query->num_rows > 0)
			{
				// Grab the upload path
				$res = $DB->query('SELECT board_id, board_upload_path FROM exp_forum_boards');
			
				$paths = array();
				foreach ($res->result as $row)
				{
					$paths[$row['board_id']] = $row['board_upload_path'];
				}
			
				foreach ($query->result as $row)
				{
					if ( ! isset($paths[$row['board_id']]))
					{
						continue;
					}
					
					$file  = $paths[$row['board_id']].$row['filehash'].$row['extension'];
					$thumb = $paths[$row['board_id']].$row['filehash'].'_t'.$row['extension'];
				
					@unlink($file);
					@unlink($thumb);					
			
					$DB->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$row['attachment_id']}'");
				}				
			}		
			
			// Update the forum stats			
			$query = $DB->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");
			
		
			if ( ! class_exists('Forum'))
			{
				require PATH_MOD.'forum/mod.forum'.EXT;
				require PATH_MOD.'forum/mod.forum_core'.EXT;
			}
			
			$FRM = new Forum_Core;
			
			foreach ($query->result as $row)
			{
				$FRM->_update_post_stats($row['forum_id']);
			}
			
			if (isset($topic_ids))
			{
				foreach ($topic_ids as $topic_id)
				{
					$FRM->_update_topic_stats($topic_id);
				}
			}
		}        
        
		/** -------------------------------------
		/**  Delete comments and update entry stats
		/** -------------------------------------*/
		
		$weblog_ids = array();
		
		$IDS = str_replace('member_id', 'author_id', $IDS);
		
		$query = $DB->query("SELECT DISTINCT(entry_id), weblog_id FROM exp_comments WHERE ".$IDS);
		
		if ($query->num_rows > 0)
		{
			$DB->query("DELETE FROM exp_comments WHERE ".$IDS);
			
			foreach ($query->result as $row)
			{
				$weblog_ids[] = $row['weblog_id'];
				
				$query = $DB->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$DB->escape_str($row['entry_id'])."'");
				
				$comment_date = ($query->num_rows == 0 OR !is_numeric($query->row['max_date'])) ? 0 : $query->row['max_date'];
				
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '{$row['entry_id']}' AND status = 'o'");		
				
				$DB->query("UPDATE exp_weblog_titles 
							SET comment_total = '".$DB->escape_str($query->row['count'])."', recent_comment_date = '$comment_date' 
							WHERE entry_id = '{$row['entry_id']}'");
			}
		}
		
		if (count($weblog_ids) > 0)
		{	
			foreach (array_unique($weblog_ids) as $weblog_id)
			{
				$STAT->update_comment_stats($weblog_id);
			}
		}

        /** ----------------------------------
        /**  Reassign Entires to Heir
        /** ----------------------------------*/
        
        $heir_id = $IN->GBL('heir', 'POST');
		$entries_exit = $IN->GBL('entries_exit', 'POST');
		$gallery_entries_exit = $IN->GBL('gallery_entries_exit', 'POST');
		
        
        if ($heir_id !== FALSE && is_numeric($heir_id))
        {
        	if ($entries_exit == 'yes')
			{
				$DB->query("UPDATE exp_weblog_titles SET author_id = '{$heir_id}' WHERE 
					".str_replace('member_id', 'author_id', $IDS));

       			$query = $DB->query("SELECT COUNT(entry_id) AS count, MAX(entry_date) AS entry_date
        						 FROM exp_weblog_titles
        						 WHERE author_id = '{$heir_id}'");
        						   
       			$DB->query("UPDATE exp_members 
        				SET total_entries = '".$DB->escape_str($query->row['count'])."', last_entry_date = '".$DB->escape_str($query->row['entry_date'])."' 
        				WHERE member_id = '{$heir_id}'");
			}

			if ($gallery_entries_exit == 'yes')
			{
       			$DB->query("UPDATE exp_gallery_entries SET author_id = '{$heir_id}' WHERE ".str_replace('member_id', 'author_id', $IDS));
			}
        }
        
        // -------------------------------------------
        // 'cp_members_member_delete_end' hook.
        //  - Additional processing when a member is deleted through the CP
        //
        	$edata = $EXT->call_extension('cp_members_member_delete_end');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        // Update global stats
        
		$STAT->update_member_stats();
            
        $message = (count($ids) == 1) ? $DSP->qdiv('success', $LANG->line('member_deleted')) :
                                        $DSP->qdiv('success', $LANG->line('members_deleted'));

        return $this->view_all_members($message);
    }
    /* END */
     


    /** -----------------------------
    /**  Member group overview
    /** -----------------------------*/
    
    function member_group_manager($message = '')
    {  
        global $LANG, $DSP, $DB, $IN, $PREFS;
        
        $row_limit = 20;
        $paginate = '';
    
        if ( ! $DSP->allowed_group('can_admin_mbr_groups'))
        {
            return $DSP->no_access_message();
        }
    
		$sql = "SELECT group_id, group_title, can_access_cp, is_locked 
				FROM exp_member_groups 
				WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
				ORDER BY exp_member_groups.group_title";
        
        $g_query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
        		
		if ($g_query->num_rows > $row_limit)
		{ 
			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');
												
			$paginate = $DSP->pager(  BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_manager',
									  $g_query->num_rows, 
									  $row_limit,
									  $row_count,
									  'row'
									);
			 
			$sql .= " LIMIT ".$row_count.", ".$row_limit;
		}

		$query = $DB->query($sql);    
		
    
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('member_groups'));
        
        if ($message != '')
        	$DSP->body .= $DSP->qdiv('box', $message);
        
        $DSP->body .= $DSP->table('tableBorder', '0', '', '100%').
                      $DSP->tr().
                      $DSP->table_qcell('tableHeadingAlt', 
                                        array(
                                                $LANG->line('group_title'),
                                                $LANG->line('edit_group'),
                                                $LANG->line('security_lock'),
                                                $LANG->line('group_id'),
                                                $LANG->line('mbrs'),
                                                $LANG->line('delete')
                                             )
                                        ).
                      $DSP->tr_c();
        
        
        $i = 0;
                
        foreach($query->result as $row)
        {
            $group_name = $row['group_title'];
                    
            if (in_array($group_name, $this->english))
            {
                $group_name = $LANG->line(strtolower(str_replace(" ", "_", $group_name)));
            }
        
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
            
            $DSP->body .= $DSP->tr();
            
            $title = ($row['can_access_cp'] == 'y') ? $DSP->qspan('highlight', $DSP->required().NBS.$group_name) : $group_name;
                        
            $DSP->body .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $title), '25%');

            $DSP->body .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_mbr_group'.AMP.'group_id='.$row['group_id'], $LANG->line('edit_group')), '18%');

            $status = ($row['is_locked'] == 'y') ? $DSP->qdiv('highlight', $LANG->line('locked')) : $DSP->qdiv('highlight_alt', $LANG->line('unlocked'));
                        
            $DSP->body .= $DSP->table_qcell($style, $status, '17%');
            
            $DSP->body .= $DSP->table_qcell($style, $row['group_id'], '15%');

			$group_id = $row['group_id'];
			$cquery = $DB->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id = '{$group_id}'");
            $DSP->body .= $DSP->table_qcell($style, $DSP->qspan('lightLinks', '('.$cquery->row['count'].')').NBS.
            								$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=view_members'.AMP.'group_id='.$row['group_id'], 
            											 $LANG->line('view')), '15%');

            $delete = ( ! in_array($row['group_id'], $this->no_delete)) ? $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_del_conf'.AMP.'group_id='.$row['group_id'], $LANG->line('delete')) : '--';

            $DSP->body .= $DSP->table_qcell($style,  $delete, '10%');

            $DSP->body .= $DSP->tr_c();
        }
        
        $DSP->body .= $DSP->table_c();
        
    	if ($paginate != '')
    	{
    		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $paginate));
    	}
        
        $DSP->body .= $DSP->qdiv('bigPad', $DSP->qspan('alert', '*').NBS.$LANG->line('member_has_cp_access'));
                
        $DSP->body .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=edit_mbr_group'));
        
        $DSP->body .= $DSP->div('box');
        $DSP->body .= NBS.NBS.$LANG->line('create_group_based_on_old').$DSP->nbs(3);
        $DSP->body .= $DSP->input_select_header('clone_id');
        
        foreach($g_query->result as $row)
        {
            $DSP->body .= $DSP->input_select_option($row['group_id'], $row['group_title']);
        }
        
        $DSP->body .= $DSP->input_select_footer();
        $DSP->body .= $DSP->nbs(2).$DSP->input_submit();
        $DSP->body .= $DSP->div_c();
        $DSP->body .= $DSP->form_close();
        
            
        $DSP->title  = $LANG->line('member_groups');    
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			   $DSP->crumb_item($LANG->line('member_groups'));    
        
		$DSP->right_crumb($LANG->line('create_new_member_group'), BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_mbr_group');
    }
    /* END */
    
    
    
    /** ----------------------------------
    /**  Edit/Create a member group form
    /** ----------------------------------*/
    
    function edit_member_group_form($msg='')
    {  
        global $IN, $DSP, $DB, $SESS, $LANG, $PREFS;

        /** ----------------------------------------------------
        /**  Only super admins can administrate member groups
        /** ----------------------------------------------------*/
                    
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message($LANG->line('only_superadmins_can_admin_groups'));
        }
        
        $group_id = $IN->GBL('group_id');
        $clone_id = $IN->GBL('clone_id');
        
        $id = ( ! $group_id) ? '3' : $group_id;
        
  
        // Assign the page title

        $title = ($group_id != '') ? $LANG->line('edit_member_group') : $LANG->line('create_member_group');
         
		/** ----------------------------------
        /**  Fetch the Sites
        /** ----------------------------------*/
        
        if ($PREFS->ini('multiple_sites_enabled') == 'y')
        {
        	$sites_query = $DB->query("SELECT * FROM exp_sites ORDER BY site_label");
		}
		else
		{
			$sites_query = $DB->query("SELECT * FROM exp_sites WHERE site_id = '1'");
		}
		
        /** ----------------------------------
        /**  Fetch the member group data
        /** ----------------------------------*/
        
        if ($clone_id != '') $id = $clone_id;
          
        $query = $DB->query("SELECT * FROM exp_member_groups WHERE group_id = '".$DB->escape_str($id)."'");
        
        $result = ($query->num_rows == 0) ? FALSE : TRUE;
        
        $group_data = array();
        
        foreach($query->result as $row)
        {
        	$group_data[$row['site_id']] = $row;
		}
		
		$default_id = $query->row['site_id'];
		
		/** ----------------------------------
        /**  Translate the group title 
        /** ----------------------------------*/
        
        // We only translate this if it has not been edited
        
        $group_title = ($group_id == '') ? '' : $group_data[$default_id]['group_title']; 
        $group_description = ($group_id == '') ? '' : $group_data[$default_id]['group_description']; 
            
        if (isset($this->english[$group_title]))
        {
            $group_title = $LANG->line(strtolower(str_replace(" ", "_", $group_title)));
        }
        
        if ($msg != '')
        {
        	$DSP->body .= $DSP->qdiv('box', $DSP->qdiv('success', $msg));
        }
        
        $DSP->body_props .= ' onload="showHideMenu(\'group_name\');"';
        
        /** ----------------------------------
        /**  Declare form and page heading
        /** ----------------------------------*/
        
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
				if (document.getElementById(lastShownObj+'_pointer'))
				{
					document.getElementById(lastShownObj+'_pointer').getElementsByTagName('a')[0].style.color = lastShownColor;
				}
				
				document.getElementById(lastShownObj + '_on').style.display = 'none';
			}
			
			lastShownObj = objValue;
			
			if (document.getElementById(objValue+'_pointer'))
			{
				lastShownColor = document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color;
			}
			
			document.getElementById(objValue + '_on').style.display = 'block';
			
			if (document.getElementById(objValue+'_pointer'))
			{
				document.getElementById(objValue+'_pointer').getElementsByTagName('a')[0].style.color = '#000';
			}
		}
		
		function switchSite(site_id)
		{
			document.getElementById('site_loader').style.display = 'inline';
			
			// The site loader image is given a second to be seen before we switch to the new Site
			// Origins of image: http://www.ajaxload.info/
			setTimeout('switchSite_action(' + site_id + ')', 1000)
		}
		
		function switchSite_action(site_id)
		{		
			if (document.getElementById('membersMenu'))
			{
				var menuDivs = document.getElementById('membersMenu').getElementsByTagName('div');
				
				for(var i = 0, s = menuDivs.length; i < s; i++)
				{
					if (menuDivs[i].id.indexOf('site_options_') != -1)
					{
						menuDivs[i].style.display = 'none';
					}
				}
			}
		
		
			if (document.getElementById('site_options_' + site_id + '_on'))
			{
				document.getElementById('site_options_' + site_id + '_on').style.display = 'block';
			}
			
			if (lastShownObj != lastShownObj.replace(/^\d+?\_/, ''))
			{
				showHideMenu(site_id + '_' + lastShownObj.replace(/^\d+?\_/, ''));
			}
			else
			{
				showHideMenu(lastShownObj);
			}
			
			document.getElementById('site_loader').style.display = 'none';
		}
		
		//-->
		</script> 
		<?php
			
		$buffer = ob_get_contents();
		ob_end_clean();         
		$DSP->body .= $buffer;
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=update_mbr_group'));
        $r .= $DSP->qdiv('default', '', 'menu_contents');
        
        if ($clone_id != '')
        {
            $group_title = '';
            $group_description = '';
			$r .= $DSP->input_hidden('clone_id', $clone_id);
        }
        
        $r .= $DSP->input_hidden('group_id', $group_id);
                
        /** ----------------------------------
        /**  Group name form field
        /** ----------------------------------*/
        
        $r .= '<div id="group_name_on" style="display: none; padding:0; margin: 0;">'.
        	  $DSP->table('tableBorder', '0', '', '100%').
        	  $DSP->tr().
        	  "<td class='tableHeadingAlt' colspan='2'>".
        	  NBS.$LANG->line('group_name').
        	  $DSP->tr_c().
              $DSP->tr().
              $DSP->td('tableCellOne', '40%').
              $DSP->qdiv('defaultBold', $LANG->line('group_name', 'group_title')).
              $DSP->td_c().
              $DSP->td('tableCellOne', '60%').
              $DSP->input_text('group_title', $group_title, '50', '70', 'input', '100%').
              $DSP->td_c().   
              $DSP->tr_c().
              $DSP->tr_c().
              $DSP->tr().
              $DSP->td('tableCellTwo', '40%', '', '', 'top').
              $DSP->qdiv('defaultBold', $LANG->line('group_description', 'group_description')).
              $DSP->td_c().
              $DSP->td('tableCellTwo', '60%').
              $DSP->input_textarea('group_description', $group_description, 10).
              $DSP->td_c().   
              $DSP->tr_c().
              $DSP->table_c().
			  $DSP->qdiv('defaultSmall', ''); 
			  
			  
        /** ----------------------------------
        /**  Top section of page
        /** ----------------------------------*/
        
        if ($group_id == 1)
        {
            $r .= $DSP->qdiv('box', $LANG->line('super_admin_edit_note'));
        }
        else
        {
            $r .= $DSP->qdiv('box', $DSP->qspan('alert', $LANG->line('warning')).$DSP->nbs(2).$LANG->line('be_careful_assigning_groups'));
        }
        
        $r .= $DSP->qdiv('defaultSmall', '');  
        
        $r .= $DSP->div_c();
              
        /** ----------------------------------
        /**  Group lock
        /** ----------------------------------*/
        
        $r .= '<div id="group_lock_on" style="display: none; padding:0; margin: 0;">';
        
		$r .= $DSP->table('tableBorder', '0', '', '100%');
        
		$r .= $DSP->tr().
              $DSP->td('tableHeadingAlt', '', '2').$LANG->line('group_lock').
              $DSP->td_c().
              $DSP->tr_c();
                
        $r .= $DSP->tr().
              $DSP->td('tableCellTwo', '60%').
              $DSP->qdiv('alert', $LANG->line('enable_lock')).
              $DSP->qdiv('itemWrapper', $LANG->line('lock_description')).
              $DSP->td_c().
              $DSP->td('tableCellTwo', '40%');
                 
              $selected = ($group_data[$default_id]['is_locked'] == 'y') ? 1 : '';
            
        $r .= $LANG->line('locked').NBS.
              $DSP->input_radio('is_locked', 'y', $selected).$DSP->nbs(3);

              $selected = ($group_data[$default_id]['is_locked'] == 'n') ? 1 : '';
            
        $r .= $LANG->line('unlocked').NBS.
              $DSP->input_radio('is_locked', 'n', $selected).$DSP->nbs(3);

        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c().
              $DSP->div_c();
        
        /** ----------------------------------------------------
        /**  Fetch the names and IDs of all weblogs
        /** ----------------------------------------------------*/
        
        $blog_names = array();
        $blog_ids   = array();

        $query = $DB->query("SELECT weblog_id, site_id, blog_title FROM exp_weblogs WHERE is_user_blog = 'n' ORDER BY blog_title");
        
        if ($id == 1)
        {        
        	foreach($query->result as $row)
        	{
        	    $blog_names['weblog_id_'.$row['weblog_id']] = $row['blog_title'];
        	    $group_data[$row['site_id']]['weblog_id_'.$row['weblog_id']] = 'y';            
        	}
        }
        else
        {
        	$res   = $DB->query("SELECT weblog_id FROM exp_weblog_member_groups WHERE group_id = '".$DB->escape_str($id)."' ");
        
        	if ($res->num_rows > 0)
        	{
        	    foreach ($res->result as $row)
        	    {
        	        $blog_ids[$row['weblog_id']] = TRUE;
        	    }
        	}
                                
        	foreach($query->result as $row)
        	{
        	    $status = (isset($blog_ids[$row['weblog_id']])) ? 'y' : 'n';
        	    $blog_names['weblog_id_'.$row['weblog_id']] = $row['blog_title'];
        	    $group_data[$row['site_id']]['weblog_id_'.$row['weblog_id']] = $status;            
        	}
        }
        
        /** ----------------------------------------------------
        /**  Fetch the names and IDs of all modules
        /** ----------------------------------------------------*/
        
        $module_names = array();
        $module_ids   = array();

        $query = $DB->query("SELECT module_id, module_name FROM exp_modules WHERE has_cp_backend = 'y' ORDER BY module_name");
        
        if ($id == 1)
        {
    	    foreach($query->result as $row)
    	    {
    	        $module_names['module_id_'.$row['module_id']] = $row['module_name'];
    	        $group_data['module_id_'.$row['module_id']] = 'y';            
    	    }
        }
        else
        {
        	$res   = $DB->query("SELECT module_id FROM exp_module_member_groups WHERE group_id = '".$DB->escape_str($id)."' ");

	        if ($res->num_rows > 0)
    	    {
    	        foreach ($res->result as $row)
    	        {
    	            $module_ids[$row['module_id']] = TRUE;
    	        }
    	    }
                        
    	    foreach($query->result as $row)
    	    {
    	        $status = (isset($module_ids[$row['module_id']])) ? 'y' : 'n';
    	        $module_names['module_id_'.$row['module_id']] = $row['module_name'];
    	        $group_data['module_id_'.$row['module_id']] = $status;            
    	    }
        }
        
        /** ----------------------------------------------------
        /**  Fetch the names and IDs of all template groups
        /** ----------------------------------------------------*/
        
        $template_names = array();
        $template_ids   = array();
        
        $query = $DB->query("SELECT group_id, group_name, site_id FROM exp_template_groups WHERE is_user_blog = 'n' ORDER BY group_name");
        
        if ($id == 1)
        {
	        foreach($query->result as $row)
	        {
	            $template_names['template_id_'.$row['group_id']] = $row['group_name'];
	            $group_data[$row['site_id']]['template_id_'.$row['group_id']] = 'y';            
	        }
        }
        else
        {
	        $res   = $DB->query("SELECT template_group_id FROM exp_template_member_groups WHERE group_id = '".$DB->escape_str($id)."' ");

	        if ($res->num_rows > 0)
	        {
	            foreach ($res->result as $row)
	            {
	                $template_ids[$row['template_group_id']] = TRUE;
	            }
	        }
                        
	        foreach($query->result as $row)
	        {
	            $status = (isset($template_ids[$row['group_id']])) ? 'y' : 'n';
	            $template_names['template_id_'.$row['group_id']] = $row['group_name'];
	            $group_data[$row['site_id']]['template_id_'.$row['group_id']] = $status;            
	        }
    	}
        
        /** ----------------------------------------------------
        /**  Assign clusters of member groups
        /** ----------------------------------------------------*/
                 
        // NOTE: the associative value (y/n) is the default setting used
        // only when we are showing the "create new group" form

        $G = array(
                    
                'site_access'	 	=> array (
                                                'can_view_online_system'	=> 'n',
                                                'can_view_offline_system'	=> 'n'
                                             ),
                                                                                          
                'mbr_account_privs' => array (
                                                'can_view_profiles'			=> 'n',
                                                'can_email_from_profile'	=> 'n',
												'include_in_authorlist'		=> 'n',
                                                'include_in_memberlist'		=> 'n',
                                                'include_in_mailinglists'	=> 'y',
												'can_delete_self'			=> 'n',
												'mbr_delete_notify_emails'	=> $PREFS->ini('webmaster_email')
                                             ),
                                             
                'commenting_privs' => array (
                                                'can_post_comments'			=> 'n',
                                                'exclude_from_moderation'	=> 'n'
                                             ),
                                             
                'search_privs'		=> array (
                                                'can_search'				=> 'n',
                                                'search_flood_control'		=> '30'
                                             ),

                'priv_msg_privs'	=> array (
                                                'can_send_private_messages'			=> 'n',
												'prv_msg_send_limit'				=> '20',
												'prv_msg_storage_limit'				=> '60',
                                                'can_attach_in_private_messages'	=> 'n',
                                                'can_send_bulletins'				=> 'n'
                                             ),
                                             				
                'global_cp_access'  => array (
                                                'can_access_cp'         	=> 'n'
                                             ),
        
                'cp_section_access' => array (
                                                'can_access_publish'    	=> 'n',
                                                'can_access_edit'       	=> 'n',
                                                'can_access_design'     	=> 'n',
                                                'can_access_comm'       	=> 'n',
                                                'can_access_modules'    	=> 'n',
                                                'can_access_admin'      	=> 'n'
                                             ),
        
                'cp_admin_privs'    => array (
                                                'can_admin_weblogs'     	=> 'n',
                                                'can_admin_templates'   	=> 'n',
                                                'can_admin_members'     	=> 'n',
                                                'can_admin_mbr_groups'  	=> 'n',
                                                'can_admin_mbr_templates'  	=> 'n',
                                                'can_delete_members'    	=> 'n',
                                                'can_ban_users'         	=> 'n',
                                                'can_admin_utilities'   	=> 'n',
                                                'can_admin_preferences' 	=> 'n',
                                                'can_admin_modules'     	=> 'n'
                                             ),
                                             
                'cp_email_privs' => array (
                                                'can_send_email'			=> 'n',
                                                'can_email_member_groups'	=> 'n',
                                                'can_email_mailinglist'		=> 'n',
                                                'can_send_cached_email'		=> 'n',                                                
                                             ),                                             
                                             
                'cp_weblog_privs'   =>  array(
                                                'can_view_other_entries'   => 'n',
                                                'can_delete_self_entries'  => 'n',
                                                'can_edit_other_entries'   => 'n',
                                                'can_delete_all_entries'   => 'n',
                                                'can_assign_post_authors'  => 'n',
                                                'can_edit_categories'	   => 'n',
                                                'can_delete_categories'	   => 'n',
                                             ),

                'cp_weblog_post_privs'   =>  $blog_names,

                                             
                'cp_comment_privs' => array (
                                                'can_moderate_comments'   	=> 'n',
                                                'can_view_other_comments'   => 'n',
                                                'can_edit_own_comments'     => 'n',
                                                'can_delete_own_comments'   => 'n',
                                                'can_edit_all_comments'     => 'n',
                                                'can_delete_all_comments'   => 'n'
                                             ),
                                             
                'cp_template_access_privs' =>  $template_names,
                
				'cp_module_access_privs'   =>  $module_names,
                                             
                   );
                   
        /** --------------------------------------
        /**  Super Admin Group can not be edited
        /** --------------------------------------*/
              
        // If the form being viewed is the Super Admin one we only allow the name to be changed.
        
        if ($group_id == 1)
        {
			$G = array('mbr_account_privs' => array ('include_in_authorlist' => 'n', 'include_in_memberlist' => 'n'));
        }   

        /** ---------------------------------------
        /**  Assign items we want to highlight
        /** ---------------------------------------*/
        
        $alert = array(
                        'can_view_offline_system',
                        'can_access_cp',
                        'can_admin_weblogs', 
                        'can_admin_templates',
                        'can_delete_members',
                        'can_admin_mbr_groups', 
                        'can_admin_mbr_templates',
                        'can_ban_users', 
                        'can_admin_members', 
                        'can_admin_preferences', 
                        'can_admin_modules', 
                        'can_admin_utilities',
                        'can_edit_categories',
                        'can_delete_categories',
						'can_delete_self'
                      );


        /** ---------------------------------------
        /**  Items that should be shown in an input box
        /** ---------------------------------------*/
        
        $tbox = array(
                        'search_flood_control',
						'prv_msg_send_limit',
						'prv_msg_storage_limit',
						'mbr_delete_notify_emails'
                      );

        /** ---------------------------------------
        /**  Render the group matrix
        /** ---------------------------------------*/
		
		$s = 0;
		
		foreach($sites_query->result as $sites)
		{
			foreach ($G as $g_key => $g_val)
			{
				if ($g_key == 'cp_module_access_privs')
				{
					if ($s == 0)
					{
						$add = '';
					}
					else
					{
						continue;
					}
				}
				else
				{
					$add = $sites['site_id'].'_';
				}
				
				/** ----------------------------------
				/**  Start the Table
				/** ----------------------------------*/
							
				$r .= '<div id="'.$add.$g_key.'_on" style="display: none; padding:0; margin: 0;">';
				$r .= $DSP->table('tableBorder', '0', '', '100%');
				$r .= $DSP->tr();
							
				$r .= "<td class='tableHeadingAlt' id='".$g_key."2' colspan='2'>";
				$r .= NBS.$LANG->line($g_key);
				$r .= $DSP->tr_c();
		
				$i = 0;
				
				foreach($g_val as $key => $val)
				{
					if ($g_key == 'cp_module_access_privs')
					{
						$group_data[$sites['site_id']][$key] = $group_data[$key];
					}
					elseif ( ! isset($group_data[$sites['site_id']][$key]))
					{
						continue;
					}
				
					if ($result == FALSE)
					{
						$group_data[$sites['site_id']][$key] = $val;
					}
					
					$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
			   
					$line = $LANG->line($key);                
					
					if (substr($key, 0, 10) == 'weblog_id_')
					{
						$line = $LANG->line('can_post_in').NBS.NBS.$DSP->qspan('alert', $blog_names[$key]);
					}
					
					if (substr($key, 0, 10) == 'module_id_')
					{
						$line = $LANG->line('can_access_mod').NBS.NBS.$DSP->qspan('alert', $module_names[$key]);
					}
					
					if (substr($key, 0, 12) == 'template_id_')
					{
						$line = $LANG->line('can_access_tg').NBS.NBS.$DSP->qspan('alert', $template_names[$key]);
					}
	
										
					$mark = (in_array($key, $alert)) ?  $DSP->qspan('alert', $line) : $DSP->qspan('defaultBold', $line);
				
					$r .= $DSP->tr().
						  $DSP->td($style, '60%').
						  $mark;
												
					$r .= $DSP->td_c().
						  $DSP->td($style, '40%');
					  
					if (in_array($key, $tbox)) 
					{
						$width = ($key == 'mbr_delete_notify_emails') ? '100%' : '100px';
						$length = ($key == 'mbr_delete_notify_emails') ? '255' : '5';
						$r .= $DSP->input_text($add.$key, $group_data[$sites['site_id']][$key], '15', $length, 'input', $width);
					}
					else
					{
						$r .= $LANG->line('yes').NBS.
							  $DSP->input_radio($add.$key, 'y', ($group_data[$sites['site_id']][$key] == 'y') ? 1 : '').$DSP->nbs(3);
		
						$r .= $LANG->line('no').NBS.
							  $DSP->input_radio($add.$key, 'n', ($group_data[$sites['site_id']][$key] == 'n') ? 1 : '').$DSP->nbs(3);
					}
					
					$r .= $DSP->td_c();
					$r .= $DSP->tr_c();
				}
				
				$r .= $DSP->table_c();
				$r .= $DSP->div_c();
			}  
			
			++$s;
		}
					
        /** ---------------------------------------
        /**  Submit button
        /** ---------------------------------------*/
       
        if ($group_id == '')
        {
            $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')).NBS.$DSP->input_submit($LANG->line('submit_and_return'),'return'));
        }
        else
        {
            $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')).NBS.$DSP->input_submit($LANG->line('update_and_return'),'return'));
        }

        $r .= $DSP->form_close();
        
        /** ----------------------------------
        /**  Create List of Sites
        /** ----------------------------------*/
		
		if ($PREFS->ini('multiple_sites_enabled') == 'y')
		{
			$sites_menu  = '<select name="site_list_pulldown" class="select" onchange="switchSite(this.value)">';
		
			foreach($sites_query->result as $sites)
			{
				$sites_menu .= $DSP->input_select_option($sites['site_id'], $sites['site_label']);
			}
			
			$sites_menu = 	$DSP->div('profileMenuInner')
							.	$sites_menu
							.$DSP->input_select_footer()
							.'<span id="site_loader" style="display:none;"><img src="'.PATH_CP_IMG.'loader.gif" width="16" height="16" style="vertical-align:sub;" /></span>'
							.$DSP->div_c();
		}
		else
		{
			$sites_menu = '';
		}
			
		/** ----------------------------------
        /**  Create Our All Encompassing Table of Weblog Goodness
        /** ----------------------------------*/
        
        $DSP->body .= $DSP->table('', '0', '', '100%');
					  
		$menu  = '';
		$menu .= $DSP->qdiv('navPad', ' <span id="group_name_pointer">&#8226; '.$DSP->anchor("#", $LANG->line('group_name'), 'onclick="showHideMenu(\'group_name\');"').'</span>');
		
		if ($group_id != 1)
		{
			$menu .= $DSP->qdiv('navPad', ' <span id="group_lock_pointer">&#8226; '.$DSP->anchor("#", $LANG->line('security_lock'), 'onclick="showHideMenu(\'group_lock\');"').'</span>');
		}
		
		$i = 0;
		foreach($sites_query->result as $sites)
		{
			if ($i != 0)
			{
				$menu .= '<div id="site_options_'.$sites['site_id'].'_on" style="display: none; padding:0; margin: 0;">';
			}
			else
			{
				$menu .= '<div id="site_options_'.$sites['site_id'].'_on" style="display: block; padding:0; margin: 0;">';
			}
		
			foreach ($G as $g_key => $g_val)
			{
				if ($g_key == 'cp_module_access_privs')
				{
					continue;
				}
				else
				{
					$add = $sites['site_id'].'_';
				}
				
				$menu .= $DSP->qdiv('navPad', ' <span id="'.$add.$g_key.'_pointer">&#8226; '.$DSP->anchor("#", $LANG->line($g_key), 'onclick="showHideMenu(\''.$add.$g_key.'\');"').'</span>');
			}
			
			$menu .= $DSP->div_c();
			
			++$i;
		}
		
		if ($group_id != 1)
		{
			// Modules item, which is the same for all sites
			$menu .= $DSP->qdiv('navPad', ' <span id="cp_module_access_privs_pointer">&#8226; '.$DSP->anchor("#", $LANG->line('cp_module_access_privs'), 'onclick="showHideMenu(\'cp_module_access_privs\');"').'</span>');
		}
		
		$first_text = 	$DSP->div('tableHeadingAlt')
						.	$title
						.$DSP->div_c()
						.$sites_menu
						.$DSP->div('profileMenuInner', '', 'membersMenu')
						.	$menu
						.$DSP->div_c();
						
		// Create the Table				
		$table_row = array( 'first' 	=> array('valign' => "top", 'width' => "220px", 'text' => $first_text),
							'second'	=> array('class' => "default", 'width'  => "8px"),
							'third'		=> array('valign' => "top", 'text' => $r));
		
		$DSP->body .= $DSP->table_row($table_row).
					  $DSP->table_c();


        /** ---------------------------------------
        /**  Assign output data
        /** ---------------------------------------*/

        $DSP->title = $title;
        
        if ($group_id != '')
        {
			$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			      $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_manager', $LANG->line('member_groups'))).
									   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_mbr_group'.AMP.'group_id='.$group_data[$default_id]['group_id'], $title)).
									   $DSP->crumb_item($group_data[$default_id]['group_title']);
        }
        else
		{  
			$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  	  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_group_manager', $LANG->line('member_groups'))).
        			  	  $DSP->crumb_item($title); 
		}  
    }
    /* END */
  
    /** -----------------------------
    /**  Create/update a member group
    /** -----------------------------*/
    
    function update_member_group()
    {  
        global $IN, $DSP, $DB, $SESS, $LOG, $LANG;
  
        /** ----------------------------------------------------
        /**  Only super admins can administrate member groups
        /** ----------------------------------------------------*/
                    
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message($LANG->line('only_superadmins_can_admin_groups'));
        }
        
        $edit = TRUE;
        
        $group_id = $IN->GBL('group_id', 'POST');
        $clone_id = $IN->GBL('clone_id', 'POST');

		unset($_POST['group_id']);
        unset($_POST['clone_id']);
  
        // Only super admins can edit the "super admin" group

        if ($group_id == 1  AND $SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message();
        }
    
        // No group name
        
        if ( ! $IN->GBL('group_title', 'POST'))
        {
            return $DSP->error_message($LANG->line('missing_group_title'));
        }
        
        $return = ($IN->GBL('return')) ? TRUE : FALSE;
		unset($_POST['return']);
		
		// New Group? Find Max
		
		if (empty($group_id))
        {
        	$edit = FALSE;
        	
        	$query = $DB->query("SELECT MAX(group_id) as max_group FROM exp_member_groups");
        	
        	$group_id = $query->row['max_group'] + 1;
        }
		
		// get existing category privileges if necessary
		
        if ($edit == TRUE)
		{
			$query = $DB->query("SELECT site_id, can_edit_categories, can_delete_categories FROM exp_member_groups WHERE group_id = '".$DB->escape_str($group_id)."'");
			
			$old_cat_privs = array();
			
			foreach ($query->result as $row)
			{
				$old_cat_privs[$row['site_id']]['can_edit_categories'] = $row['can_edit_categories'];
				$old_cat_privs[$row['site_id']]['can_delete_categories'] = $row['can_delete_categories'];
			}
		}
		
		$query = $DB->query("SELECT site_id FROM exp_sites");
		
		$module_ids = array();
		$weblog_ids = array();
		$template_ids = array();
		$cat_group_privs = array('can_edit_categories', 'can_delete_categories');
				
		foreach($query->result as $row)
		{
			$site_id = $row['site_id'];
			
			/** ----------------------------------------------------
			/**  Remove and Store Weblog and Template Permissions
			/** ----------------------------------------------------*/
			
			$data = array('group_title' 		=> $IN->GBL('group_title', 'POST'),
						  'group_description'	=> $IN->GBL('group_description', 'POST'),
						  'is_locked'			=> $IN->GBL('is_locked', 'POST'),
						  'site_id'				=> $site_id,
						  'group_id'			=> $group_id);
							
			foreach ($_POST as $key => $val)
			{
				if (substr($key, 0, strlen($site_id.'_weblog_id_')) == $site_id.'_weblog_id_')
				{
					if ($val == 'y')
					{
						$weblog_ids[] = substr($key, strlen($site_id.'_weblog_id_'));
					}
				}
				elseif (substr($key, 0, strlen('module_id_')) == 'module_id_')
				{
					if ($val == 'y')
					{
						$module_ids[] = substr($key, strlen('module_id_'));             
					}
				}
				elseif (substr($key, 0, strlen($site_id.'_template_id_')) == $site_id.'_template_id_')
				{
					if ($val == 'y')
					{
						$template_ids[] = substr($key, strlen($site_id.'_template_id_'));                       
					}
				}
				elseif (substr($key, 0, strlen($site_id.'_')) == $site_id.'_')
				{
					$data[substr($key, strlen($site_id.'_'))] = $_POST[$key];
				}
				else
				{
					continue;
				}
				
				unset($_POST[$key]);
			}

			if ($edit === FALSE)
			{	
				$DB->query($DB->insert_string('exp_member_groups', $data));
				
				$uploads = $DB->query("SELECT exp_upload_prefs.id FROM exp_upload_prefs WHERE site_id = '".$DB->escape_str($site_id)."'");
				
				if ($uploads->num_rows > 0)
				{
					foreach($uploads->result as $yeeha)
					{
						$DB->query("INSERT INTO exp_upload_no_access (upload_id, upload_loc, member_group) VALUES ('".$DB->escape_str($yeeha['id'])."', 'cp', '{$group_id}')");
					}
				}  
				
				if ($group_id != 1)
				{
					foreach ($cat_group_privs as $field)
					{
						$privs = array(
										'member_group' => $group_id,
										'field' => $field,
										'allow' => ($data[$field] == 'y') ? TRUE : FALSE,
										'site_id' => $site_id,
										'clone_id' => $clone_id
									);

						$this->_update_cat_group_privs($privs);	
					}
				}
				
				$message = $LANG->line('member_group_created').$DSP->nbs(2).$_POST['group_title'];            
			}
			else
			{			
				unset($data['group_id']);
				
				$DB->query($DB->update_string('exp_member_groups', $data, "group_id = '$group_id' AND site_id = '{$site_id}'"));
				
				if ($group_id != 1)
				{
					// update category group discrete privileges
	
					foreach ($cat_group_privs as $field)
					{
						// only modify category group privs if valye changed, so we do not
						// globally overwrite existing defined privileges carelessly
	
						if ($old_cat_privs[$site_id][$field] != $data[$field])
						{
							$privs = array(
											'member_group' => $group_id,
											'field' => $field,
											'allow' => ($data[$field] == 'y') ? TRUE : FALSE,
											'site_id' => $site_id,
											'clone_id' => $clone_id
										);
	
							$this->_update_cat_group_privs($privs);						
						}
					}
				}
				
				$message = $LANG->line('member_group_updated').$DSP->nbs(2).$_POST['group_title'];
			}
        }
        
        // Update groups
        
        $DB->query("DELETE FROM exp_weblog_member_groups WHERE group_id = '$group_id'");
        $DB->query("DELETE FROM exp_module_member_groups WHERE group_id = '$group_id'");
        $DB->query("DELETE FROM exp_template_member_groups WHERE group_id = '$group_id'");
        
        if (count($weblog_ids) > 0)
        {
			foreach ($weblog_ids as $val)
			{
				$DB->query("INSERT INTO exp_weblog_member_groups (group_id, weblog_id) VALUES ('$group_id', '$val')");
			}
		}
			
        if (count($module_ids) > 0)
        {
			foreach ($module_ids as $val)
			{
				$DB->query("INSERT INTO exp_module_member_groups (group_id, module_id) VALUES ('$group_id', '$val')");
			}
		}
		
        if (count($template_ids) > 0)
        {
			foreach ($template_ids as $val)
			{
				$DB->query("INSERT INTO exp_template_member_groups (group_id, template_group_id) VALUES ('$group_id', '$val')");
			}
     	}   
        
        // Update CP log
        
        $LOG->log_action($message);            
  
  		if ($return == TRUE)
  		{
  			return $this->member_group_manager($DSP->qdiv('success', $message));
  		}
  		
  		$_POST['group_id'] = $group_id;
        return $this->edit_member_group_form($DSP->qdiv('success', $message));  
    }  
    /* END */
    
    
	/** -----------------------------------------------------------
	/**  Update Category Group Discrete Privileges
	/** -----------------------------------------------------------*/
	//  Updates exp_category_groups privilege lists for
	//  editing and deleting categories
	//-----------------------------------------------------------
	
	function _update_cat_group_privs($params)
	{
		global $DB;

		if (! is_array($params) OR empty($params))
		{
			return FALSE;
		}
		
		$expected = array('member_group', 'field', 'allow', 'site_id', 'clone_id');
		
		// turn parameters into variables
		
		foreach ($expected as $key)
		{
			// naughty!
			
			if (! isset($params[$key]))
			{
				return FALSE;
			}
			
			$$key = $params[$key];
		}
		
		$query = $DB->query("SELECT group_id, ".$DB->escape_str($field)." FROM exp_category_groups WHERE site_id = '".$DB->escape_str($site_id)."'");
		
		// nothing to do?
		
		if ($query->num_rows == 0)
		{
			return FALSE;
		}

		foreach ($query->result as $row)
		{
			$can_do = explode('|', rtrim($row[$field], '|'));

			if ($allow === TRUE)
			{
				if (is_numeric($clone_id))
				{
					if (in_array($clone_id, $can_do) OR $clone_id == 1)
					{
						$can_do[] = $member_group;
					}						
				}
				elseif ($clone_id === FALSE)
				{
					$can_do[] = $member_group;
				}
			}
			else
			{
				$can_do = array_diff($can_do, array($member_group));
			}

			$DB->query($DB->update_string('exp_category_groups', array($field => implode('|', $can_do)), "group_id = '{$row['group_id']}'"));
		}
	}
	/* END */
	
	
    /** -----------------------------------------------------------
    /**  Delete member group confirm
    /** -----------------------------------------------------------*/
    // Warning message shown when you try to delete a group
    //-----------------------------------------------------------

    function delete_member_group_conf()
    {  
        global $DSP, $IN, $DB, $SESS, $LANG, $PREFS;
  
        /** ----------------------------------------------------
        /**  Only super admins can delete member groups
        /** ----------------------------------------------------*/
                    
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message($LANG->line('only_superadmins_can_admin_groups'));
        }
        

        if ( ! $group_id = $IN->GBL('group_id'))
        {
            return false;
        }
        
        // You can't delete these groups
                
        if (in_array($group_id, $this->no_delete))
        {
            return $DSP->no_access_message();
        }
        
        
        // Are there any members that are assigned to this group?
        
        $result = $DB->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id = '{$group_id}'");
		$members_exist = ($result->row['count'] > 0) ? TRUE : FALSE;
		

        $query = $DB->query("SELECT group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id = '".$DB->escape_str($group_id)."'");
        
        $DSP->title = $LANG->line('delete_member_group');
        
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=group_manager', $LANG->line('member_groups'))).
        			  $DSP->crumb_item($LANG->line('delete_member_group'));


        $DSP->body = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=delete_mbr_group'.AMP.'group_id='.$group_id))
                    .$DSP->input_hidden('group_id', $group_id);

		$DSP->body .= ($members_exist === TRUE) ? $DSP->input_hidden('reassign', 'y') : $DSP->input_hidden('reassign', 'n');
                    
                    
 		$DSP->body .= $DSP->heading($DSP->qspan('alert', $LANG->line('delete_member_group')))
                     .$DSP->div('box')
                     .$DSP->qdiv('itemWrapper', '<b>'.$LANG->line('delete_member_group_confirm').'</b>')
                     .$DSP->qdiv('itemWrapper', '<i>'.$query->row['group_title'].'</i>')
                     .$DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone').BR.BR);
                    
		if ($members_exist === TRUE)
		{
			$DSP->body .= $DSP->qdiv('defaultBold', str_replace('%x', $result->row['count'], $LANG->line('member_assignment_warning')));
         
         	$DSP->body .= $DSP->div('itemWrapper');
			$DSP->body .= $DSP->input_select_header('new_group_id');				
			
			$query = $DB->query("SELECT group_title, group_id FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND group_id != '{$group_id}' order by group_title");
				 
			foreach ($query->result as $row)
			{
				$group_name = $row['group_title'];
						
				if (in_array($group_name, $this->english))
				{
					$group_name = $LANG->line(strtolower(str_replace(" ", "_", $group_name)));
				}
						
				$DSP->body .= $DSP->input_select_option($row['group_id'], $group_name, '');
			}        
	
			$DSP->body .= $DSP->input_select_footer();
			$DSP->body .= $DSP->div_c();
		}           
                    
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('delete')))
                    .$DSP->div_c()
                    .$DSP->form_close();
    }
    /* END */
    
    
    
    
    /** -----------------------------------
    /**  Delete Member Group
    /** -----------------------------------*/
    
    function delete_member_group()
    {  
        global $DSP, $IN, $DB, $LANG, $SESS, $PREFS;

        /** ----------------------------------------------------
        /**  Only super admins can delete member groups
        /** ----------------------------------------------------*/
                    
        if ($SESS->userdata['group_id'] != 1)
        {
            return $DSP->no_access_message($LANG->line('only_superadmins_can_admin_groups'));
        }

        if ( ! $group_id = $IN->GBL('group_id', 'POST'))
        {
            return false;
        }
                
        if (in_array($group_id, $this->no_delete))
        {
            return $DSP->no_access_message();
        }
        
        $group_id = $DB->escape_str($group_id);
        
        
        if ($IN->GBL('reassign') == 'y' AND $IN->GBL('new_group_id') != FALSE)
        {
        	$new_group = $DB->escape_str($IN->GBL('new_group_id'));
        
        	$DB->query("UPDATE exp_members SET group_id = '{$new_group}' WHERE group_id = '{$group_id}'");
        }

        $DB->query("DELETE FROM exp_member_groups WHERE group_id = '{$group_id}'");
        
        return $this->member_group_manager($DSP->qdiv('success', $LANG->line('member_group_deleted')));
    }    
    /* END */
    
    
    
    /** -----------------------------------
    /**  Create a member profile form
    /** -----------------------------------*/
    
    function new_member_profile_form()
    {  
        global $IN, $DSP, $DB, $LANG, $SESS, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
        $DSP->body_props = " onload=\"document.forms[0].username.focus();\"";
        
        $title = $LANG->line('register_member');
        
        // Build the output
        
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=register_member'));
        
        $r .= $DSP->qdiv('tableHeading', $title);
        $r .= $DSP->div('box');
        $r .= $DSP->itemgroup(
                                $DSP->required().NBS.$LANG->line('username', 'username'),
                                $DSP->input_text('username', '', '35', '32', 'input', '300px')
                              );
                              
        $r .= $DSP->itemgroup(
                                $DSP->required().NBS.$LANG->line('password', 'password'),
                                $DSP->input_pass('password', '', '35', '32', 'input', '300px')
                              );
                              
        $r .= $DSP->itemgroup(
                                $DSP->required().NBS.$LANG->line('password_confirm', 'password_confirm'),
                                $DSP->input_pass('password_confirm', '', '35', '32', 'input', '300px')
                              );
        
        $r .= $DSP->itemgroup(
                                $DSP->required().NBS.$LANG->line('screen_name', 'screen_name'),
                                $DSP->input_text('screen_name', '', '40', '50', 'input', '300px')
                              );
        
        $r .= $DSP->td_c().
              $DSP->td('', '45%', '', '', 'top');
     
        $r .= $DSP->itemgroup(
                                $DSP->required().NBS.$LANG->line('email', 'email'),
                                $DSP->input_text('email', '', '35', '100', 'input', '300px')
                              );
     
                              
           
        // Member groups assignment
                       
        if ($DSP->allowed_group('can_admin_mbr_groups'))
        {
            if ($SESS->userdata['group_id'] != 1)
            {
                $sql = "SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_locked = 'n' order by group_title";
            }
            else
            {
                $sql = "SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' order by group_title";
            }

            $query = $DB->query($sql);
            
            if ($query->num_rows > 0)
            {            
				$r .= $DSP->qdiv(
								 'itemWrapperTop', 
								  $DSP->qdiv('defaultBold', $LANG->line('member_group_assignment'))
								 );
					  
				$r .= $DSP->input_select_header('group_id');
										
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
        }                

		$r .= $DSP->div_c();
		
        // Submit button   
        
        $r .= $DSP->itemgroup( '',
                                $DSP->required(1).$DSP->br(2).$DSP->input_submit($LANG->line('submit'))
                              );
        $r .= $DSP->form_close();
        
        
        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($title);
        $DSP->body  = $r;
    }
    /* END */



    /** ----------------------------------
    /**  Create a member profile
    /** ----------------------------------*/
    
    function create_member_profile()
    {  
        global $IN, $DSP, $DB, $SESS, $PREFS, $FNS, $REGX, $LOC, $LOG, $LANG, $STAT, $EXT;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
        $data = array();
        
        if ($IN->GBL('group_id', 'POST'))
        {        
            if ( ! $DSP->allowed_group('can_admin_mbr_groups'))
            {
                return $DSP->no_access_message();
            } 
            
            $data['group_id'] = $_POST['group_id'];
        }  
        
		/* -------------------------------------------
        /* 'cp_members_member_create_start' hook.
        /*  - Take over member creation when done through the CP
        /*  - Added 1.4.2
        */
        	$edata = $EXT->call_extension('cp_members_member_create_start');
        	if ($EXT->end_script === TRUE) return;
        /*
        // -------------------------------------------*/
            

		// If the screen name field is empty, we'll assign is
		// from the username field.              
               
		if ($_POST['screen_name'] == '')
			$_POST['screen_name'] = $_POST['username'];              

        /** -------------------------------------
        /**  Instantiate validation class
        /** -------------------------------------*/

		if ( ! class_exists('Validate'))
		{
			require PATH_CORE.'core.validate'.EXT;
		}
		
		$VAL = new Validate(
								array( 
										'member_id'			=> '',
										'val_type'			=> 'new', // new or update
										'fetch_lang' 		=> TRUE, 
										'require_cpw' 		=> FALSE,
									 	'enable_log'		=> TRUE,
										'username'			=> $_POST['username'],
										'cur_username'		=> '',
										'screen_name'		=> stripslashes($_POST['screen_name']),
										'cur_screen_name'	=> '',
										'password'			=> $_POST['password'],
									 	'password_confirm'	=> $_POST['password_confirm'],
									 	'cur_password'		=> '',
									 	'email'				=> $_POST['email'],
									 	'cur_email'			=> ''
									 )
							);
		
		$VAL->validate_username();
		$VAL->validate_screen_name();
		$VAL->validate_password();
		$VAL->validate_email();

        /** -------------------------------------
        /**  Display error is there are any
        /** -------------------------------------*/

         if (count($VAL->errors) > 0)
         {            
            return $VAL->show_errors();
         }
         
        // Assign the query data
         
        $data['username']    = $_POST['username'];
        $data['password']    = $FNS->hash(stripslashes($_POST['password']));
        $data['ip_address']  = $IN->IP;
        $data['unique_id']   = $FNS->random('encrypt');
        $data['join_date']   = $LOC->now;
        $data['email']       = $_POST['email'];
        $data['screen_name'] = $_POST['screen_name'];
                      
        // Was a member group ID submitted?
        
        $data['group_id'] = ( ! $IN->GBL('group_id', 'POST')) ? 2 : $_POST['group_id'];

        $DB->query($DB->insert_string('exp_members', $data)); 
        
        $member_id = $DB->insert_id;  
        
        // Create a record in the custom field table
                                       
        $DB->query($DB->insert_string('exp_member_data', array('member_id' => $member_id)));
        
        // Create a record in the member homepage table
                            
        $DB->query($DB->insert_string('exp_member_homepage', array('member_id' => $member_id)));
        
        $message = $LANG->line('new_member_added');
        
        // Write log file
        
        $LOG->log_action($message.$DSP->nbs(2).stripslashes($data['username']));
        
        // -------------------------------------------
        // 'cp_members_member_create' hook.
        //  - Additional processing when a member is created through the CP
        //
        	$edata = $EXT->call_extension('cp_members_member_create', $member_id, $data);
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        // Update global stat
        
		$STAT->update_member_stats();
        
        // Build success message
        
        return $this->view_all_members($DSP->qspan('success', $message).NBS.'<b>'.stripslashes($data['username']).'</b>');   
    }
    /* END */
    
    
    
    /** -----------------------------
    /**  Member banning forms
    /** -----------------------------*/
    
    function member_banning_forms()
    {  
        global $IN, $LANG, $DSP, $PREFS, $DB;
        
        if ( ! $DSP->allowed_group('can_ban_users'))
        {
            return $DSP->no_access_message();
        }
        
        $banned_ips   = $PREFS->ini('banned_ips');
        $banned_emails  = $PREFS->ini('banned_emails');
        $banned_usernames = $PREFS->ini('banned_usernames');
        $banned_screen_names = $PREFS->ini('banned_screen_names');
        
        $out    	= '';
        $ips    	= '';
        $email  	= '';
        $users  	= '';
        $screens	= '';
        
        if ($banned_ips != '')
        {            
            foreach (explode('|', $banned_ips) as $val)
            {
                $ips .= $val.NL;
            }
        }
        
        if ($banned_emails != '')
        {                        
            foreach (explode('|', $banned_emails) as $val)
            {
                $email .= $val.NL;
            }
        }
        
        if ($banned_usernames != '')
        {                        
            foreach (explode('|', $banned_usernames) as $val)
            {
                $users .= $val.NL;
            }
        }

        if ($banned_screen_names != '')
        {                        
            foreach (explode('|', $banned_screen_names) as $val)
            {
                $screens .= $val.NL;
            }
        }

        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=save_ban_data')).
              $DSP->qdiv('tableHeading', $LANG->line('user_banning'));
        
        if ($IN->GBL('U'))
        {
            $r .= $DSP->qdiv('box', $DSP->qdiv('success', $LANG->line('ban_preferences_updated')));
        }
        
		$r .= 	$DSP->table('', '', '', '100%', '').
				$DSP->tr().
				$DSP->td('', '48%', '', '', 'top');        
		
		
		$r .= 	$DSP->div('box').
				$DSP->heading($LANG->line('ip_address_banning', 'banned_ips'), 5).
				$DSP->qdiv('itemWrapper', $DSP->qspan('highlight', $LANG->line('ip_banning_instructions'))).
				$DSP->qdiv('itemWrapper', $LANG->line('ip_banning_instructions_cont')).              
				$DSP->input_textarea('banned_ips', stripslashes($ips), '22', 'textarea', '100%').BR.BR;
		
		$r .= 	$DSP->heading(BR.$LANG->line('ban_options'), 5);
		
		$selected = ($PREFS->ini('ban_action') == 'restrict') ? 1 : '';   
		
		$r .= 	$DSP->div('itemWrapper').
				$DSP->input_radio('ban_action', 'restrict', $selected).NBS. $LANG->line('restrict_to_viewing').BR.
				$DSP->div_c();
		
		$selected    = ($PREFS->ini('ban_action') == 'message') ? 1 : '';
		
		$r .= 	$DSP->div('itemWrapper').
				$DSP->input_radio('ban_action', 'message', $selected).NBS.$LANG->line('show_this_message', 'ban_message').BR.
				$DSP->input_text('ban_message', $PREFS->ini('ban_message'), '50', '100', 'input', '100%').
				$DSP->div_c();
		
		$selected    = ($PREFS->ini('ban_action') == 'bounce') ? 1 : '';
		$destination = ($PREFS->ini('ban_destination') == '') ? 'http://' : $PREFS->ini('ban_destination');
		
		$r .= 	$DSP->div('itemWrapper').
				$DSP->input_radio('ban_action', 'bounce', $selected).NBS.$LANG->line('send_to_site', 'ban_destination').BR.
				$DSP->input_text('ban_destination', $destination, '50', '70', 'input', '100%').
				$DSP->div_c();
		
		$r .= 	$DSP->div().BR.
				$DSP->input_submit($LANG->line('update')).BR.BR.BR.
				$DSP->div_c().
				$DSP->div_c();
		
		$r .= 	$DSP->td_c(). 
				$DSP->td('', '4%', '', '', 'top').NBS.      
				$DSP->td_c(). 
				$DSP->td('', '48%', '', '', 'top');        
		
		$r .=	$DSP->div('box').
				$DSP->heading($LANG->line('email_address_banning', 'banned_emails'), 5).
				$DSP->qdiv('itemWrapper', $DSP->qspan('highlight', $LANG->line('email_banning_instructions'))).
				$DSP->qdiv('itemWrapper', $LANG->line('email_banning_instructions_cont')).
				$DSP->input_textarea('banned_emails', stripslashes($email), '9', 'textarea', '100%').
				$DSP->div_c();
		
		$r .= $DSP->qdiv('defaultSmall', NBS);
		
		$r .= 	$DSP->div('box').
				$DSP->heading($LANG->line('username_banning', 'banned_usernames'), 5).
				$DSP->qdiv('itemWrapper', $DSP->qspan('highlight', $LANG->line('username_banning_instructions'))).
				$DSP->input_textarea('banned_usernames', stripslashes($users), '9', 'textarea', '100%').
				$DSP->div_c();
				
		$r .= $DSP->qdiv('defaultSmall', NBS);
		
		$r .=	$DSP->div('box').
				$DSP->heading($LANG->line('screen_name_banning', 'banned_screen_names'), 5).
				$DSP->qdiv('itemWrapper', $DSP->qspan('highlight', $LANG->line('screen_name_banning_instructions'))).
				$DSP->input_textarea('banned_screen_names', stripslashes($screens), '9', 'textarea', '100%').
				$DSP->div_c();
		
		$r .= 	$DSP->td_c().
				$DSP->tr_c().
				$DSP->table_c();        
		
		$r .= $DSP->form_close();
		
		$DSP->title = $LANG->line('user_banning');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('user_banning'));
		$DSP->body  = $r;
    }
    /* END */


    /** -----------------------------
    /**  Update banning data
    /** -----------------------------*/
    
    function update_banning_data()
    {
        global $IN, $DSP, $DB, $PREFS, $REGX, $FNS;
    
        if ( ! $DSP->allowed_group('can_ban_users'))
        {
            return $DSP->no_access_message();
        }

		if (empty($_POST))
		{
            return $DSP->no_access_message();			
		}
                
		foreach ($_POST as $key => $val)
		{ 
			$_POST[$key] = stripslashes($val);		
		}        

        $banned_ips    			= str_replace(NL, '|', $_POST['banned_ips']);
        $banned_emails 			= str_replace(NL, '|', $_POST['banned_emails']);
        $banned_usernames 		= str_replace(NL, '|', $_POST['banned_usernames']);
        $banned_screen_names 	= str_replace(NL, '|', $_POST['banned_screen_names']);
        
        $destination = ($_POST['ban_destination'] == 'http://') ? '' : $_POST['ban_destination'];
        
        $data = array(	
                        'banned_ips'      		=> $banned_ips,
                        'banned_emails'   		=> $banned_emails,
                        'banned_emails'   		=> $banned_emails,
                        'banned_usernames'		=> $banned_usernames,
                        'banned_screen_names'	=> $banned_screen_names,
                        'ban_action'      		=> $_POST['ban_action'],
                        'ban_message'     		=> $_POST['ban_message'],
                        'ban_destination' 		=> $destination
                     );
               
		/** ----------------------------------------
		/**  Preferences Stored in Database For Site
		/** ----------------------------------------*/
		
		$query = $DB->query("SELECT site_id, site_system_preferences FROM exp_sites");
		
		foreach($query->result AS $row)
		{
			$prefs = array_merge($REGX->array_stripslashes(unserialize($row['site_system_preferences'])), $data);
			
			$query = $DB->query($DB->update_string('exp_sites', 
												   array('site_system_preferences' => addslashes(serialize($prefs))),
												   "site_id = '".$DB->escape_str($row['site_id'])."'"));
		}
		
		$override = ($IN->GBL('class_override', 'GET') != '') ? AMP.'class_override='.$IN->GBL('class_override', 'GET') : '';
		
		$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=member_banning'.AMP.'U=1'.$override);
		exit;
    }
    /* END */
    
    
    

    /** -----------------------------------------------------------
    /**  Custom profile fields
    /** -----------------------------------------------------------*/
    // This function show a list of current member fields and the
    // form that allows you to create a new field.
    //-----------------------------------------------------------

    function custom_profile_fields($group_id = '')
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }

        // Fetch language file
        // There are some lines in the publish administration language file
        // that we need.

        $LANG->fetch_language_file('publish_ad');
        
        $DSP->title  = $LANG->line('custom_member_fields');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			   $DSP->crumb_item($LANG->line('custom_member_fields'));
        
		$DSP->right_crumb($LANG->line('create_new_profile_field'),BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_field');
        
        // Build the output
     
        $r = $DSP->qdiv('tableHeading', $LANG->line('custom_member_fields'));
        
        if ($IN->GBL('U'))
        {
        		$r .= $DSP->qdiv('success', $LANG->line('field_updated'));
        }
     

        $query = $DB->query("SELECT m_field_id, m_field_order, m_field_label FROM  exp_member_fields ORDER BY m_field_order");        
  
        if ($query->num_rows == 0)
        {
			$DSP->body  = $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($LANG->line('no_custom_profile_fields'), 5));
        		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_field', $LANG->line('create_new_profile_field')));
			$DSP->body .= $DSP->div_c();
        
			return;
        } 
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeadingAlt', '', '3').
              $LANG->line('current_fields').
              $DSP->td_c().
              $DSP->tr_c();

        $i = 0;
        
		foreach ($query->result as $row)
		{
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;

			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $row['m_field_order'].$DSP->nbs(2).$DSP->qspan('defaultBold', $row['m_field_label']), '40%');
			$r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_field'.AMP.'m_field_id='.$row['m_field_id'], $LANG->line('edit')), '30%');      
			$r .= $DSP->table_qcell($style, $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=del_field_conf'.AMP.'m_field_id='.$row['m_field_id'], $LANG->line('delete')), '30%');      
			$r .= $DSP->tr_c();
		}
        
        $r .= $DSP->table_c();

		$r .= $DSP->qdiv('paddedWrapper', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_field_order', $LANG->line('edit_field_order')));

        $DSP->body   = $r;  
    }
    /* END */
    
  


    /** -----------------------------------------------------------
    /**  Edit field form
    /** -----------------------------------------------------------*/
    // This function lets you edit an existing custom field
    //-----------------------------------------------------------

    function edit_profile_field_form()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }

        $type = ($m_field_id = $IN->GBL('m_field_id')) ? 'edit' : 'new';
        
        // Fetch language file
        // There are some lines in the publish administration language file
        // that we need.

        $LANG->fetch_language_file('publish_ad');
        
        $total_fields = '';
        
        if ($type == 'new')
        {
            $query = $DB->query("SELECT count(*) AS count FROM exp_member_fields");
            
            $total_fields = $query->row['count'] + 1;
        }
        
        $DB->fetch_fields = TRUE;
        
        $query = $DB->query("SELECT * FROM exp_member_fields WHERE m_field_id = '$m_field_id'");
        
        if ($query->num_rows == 0)
        {
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
        
        
        $r = <<<EOT
        
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
				document.getElementById('textarea_block').style.display = "block";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('select_block').style.display = "none";
        	}
        	else
        	{
				document.getElementById('select_block').style.display = "block";
				document.getElementById('text_block').style.display = "none";
				document.getElementById('textarea_block').style.display = "none";
        	}
        }
        		
		-->
		</script>
EOT;
        
        
        
        
        $title = ($type == 'edit') ? 'edit_member_field' : 'create_member_field';

		$i = 0;
		
        // Form declaration
        
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=update_profile_fields'.AMP.'U=1'));
        $r .= $DSP->input_hidden('m_field_id', $m_field_id);
        $r .= $DSP->input_hidden('cur_field_name', $m_field_name);
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').$LANG->line($title).$DSP->td_c().
              $DSP->tr_c();
              
                
        /** ---------------------------------
        /**  Field name
        /** ---------------------------------*/
        
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('fieldname', 'm_field_name')).$DSP->qdiv('itemWrapper', $LANG->line('fieldname_cont')), '40%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('m_field_name', $m_field_name, '50', '60', 'input', '300px'), '60%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Field label
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('fieldlabel', 'm_field_label')).$DSP->qdiv('itemWrapper', $LANG->line('for_profile_page')), '40%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('m_field_label', $m_field_label, '50', '60', 'input', '300px'), '60%');
		$r .= $DSP->tr_c();		
		 
        /** ---------------------------------
        /**  Field Description
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_description', 'm_field_description')).$DSP->qdiv('itemWrapper', $LANG->line('field_description_info')), '40%');
		$r .= $DSP->table_qcell($style, $DSP->input_textarea('m_field_description', $m_field_description, '4', 'textarea', '100%'), '60%');
		$r .= $DSP->tr_c();		
		 
        /** ---------------------------------
        /**  Field order
        /** ---------------------------------*/
        
        if ($type == 'new')
            $m_field_order = $total_fields;
            
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_order', 'm_field_order')), '40%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('m_field_order', $m_field_order, '4', '3', 'input', '30px'), '60%');
		$r .= $DSP->tr_c();		
		
        /** ---------------------------------
        /**  Field type
        /** ---------------------------------*/

        $sel_1 = ''; $sel_2 = ''; $sel_3 = '';
        $text_js = ($type == 'edit') ? 'none' : 'block';
        $textarea_js = 'none';
        $select_js = 'none';
        $select_opt_js = 'none';

        switch ($m_field_type)
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

        $typemenu = "<select name='m_field_type' class='select' onchange='showhide_element(this.options[this.selectedIndex].value);' >".NL;
		$typemenu .= $DSP->input_select_option('text', 		$LANG->line('text_input'),	$sel_1)
					.$DSP->input_select_option('textarea', 	$LANG->line('textarea'),  	$sel_2)
					.$DSP->input_select_option('select', 	$LANG->line('select_list'), $sel_3)
					.$DSP->input_select_footer();
		

        /** ---------------------------------
        /**  Field width
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		if ($m_field_width == '')
			$m_field_width = '100%';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_width', 'm_field_width')).$DSP->qdiv('itemWrapper', $LANG->line('field_width_cont')), '40%');
		$r .= $DSP->table_qcell($style, $DSP->input_text('m_field_width', $m_field_width, '8', '6', 'input', '60px'), '60%');
		$r .= $DSP->tr_c();		
			
        /** ---------------------------------
        /**  Max-length Field
        /** ---------------------------------*/

		if ($m_field_maxl == '') $m_field_maxl = '100';

		$typopts  = '<div id="text_block" style="display: '.$text_js.'; padding:0; margin:5px 0 0 0;">';		
		$typopts .= $DSP->qdiv('defaultBold', $LANG->line('max_length', 'm_field_maxl')).$DSP->qdiv('itemWrapper', $DSP->input_text('m_field_maxl', $m_field_maxl, '4', '3', 'input', '30px'));
		$typopts .= $DSP->div_c();

        /** ---------------------------------
        /**  Textarea Row Field
        /** ---------------------------------*/

		if ($m_field_ta_rows == '') $m_field_ta_rows = '10';

		$typopts .= '<div id="textarea_block" style="display: '.$textarea_js.'; padding:0; margin:5px 0 0 0;">';		
		$typopts .= $DSP->qdiv('defaultBold', $LANG->line('text_area_rows', 'm_field_ta_rows')).$DSP->qdiv('itemWrapper', $DSP->input_text('m_field_ta_rows', $m_field_ta_rows, '4', '3', 'input', '30px'));
		$typopts .= $DSP->div_c();

        /** ---------------------------------
        /**  Select List Field
        /** ---------------------------------*/

		$typopts .= '<div id="select_block" style="display: '.$select_js.'; padding:0; margin:5px 0 0 0;">';		
		$typopts .= $DSP->qdiv('defaultBold', $LANG->line('pull_down_items', 'm_field_list_items')).$DSP->qdiv('default', $LANG->line('field_list_instructions')).$DSP->input_textarea('m_field_list_items', $m_field_list_items, 10, 'textarea', '400px');
		$typopts .= $DSP->div_c();


        /** ---------------------------------
        /**  Generate the above items
        /** ---------------------------------*/

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qspan('defaultBold', $LANG->line('field_type'))).$typemenu, '50%', 'top');
		$r .= $DSP->table_qcell($style, $typopts, '50%', 'top');
		$r .= $DSP->tr_c();	

		
        /** ---------------------------------
        /**  Field formatting
        /** ---------------------------------*/
                
        $sel_1 = ''; $sel_2 = ''; $sel_3 = '';
        
        switch ($m_field_fmt)
        {
            case 'none'  : $sel_1 = 1;
                break;
            case 'br'    : $sel_2 = 1;
                break;
            case 'xhtml' : $sel_3 = 1;
                break;
            default		 : $sel_3 = 1;
                break;
        }
        
		$typemenu  = $DSP->input_select_header('m_field_fmt')
					.$DSP->input_select_option('none', $LANG->line('none'), $sel_1)
					.$DSP->input_select_option('br', $LANG->line('auto_br'), $sel_2)
					.$DSP->input_select_option('xhtml', $LANG->line('xhtml'), $sel_3)
					.$DSP->input_select_footer();
					
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('field_format')).$DSP->qdiv('itemWrapper', $LANG->line('text_area_rows_cont')), '40%');
		$r .= $DSP->table_qcell($style, $typemenu, '60%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Is field required?
        /** ---------------------------------*/
              
        if ($m_field_required == '') $m_field_required = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_required')), '40%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('m_field_required', 'y', ($m_field_required == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('m_field_required', 'n', ($m_field_required == 'n') ? 1 : ''), '60%');
		$r .= $DSP->tr_c();		
             
      
        /** ---------------------------------
        /**  Is field public?
        /** ---------------------------------*/
              
        if ($m_field_public == '') $m_field_public = 'y';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_public')).$DSP->qdiv('itemWrapper', $LANG->line('is_field_public_cont')), '40%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('m_field_public', 'y', ($m_field_public == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('m_field_public', 'n', ($m_field_public == 'n') ? 1 : ''), '60%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Is field visible in reg page?
        /** ---------------------------------*/
        
        if ($m_field_reg == '') $m_field_reg = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_reg')).$DSP->qdiv('itemWrapper', $LANG->line('is_field_public_cont')), '40%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('m_field_reg', 'y', ($m_field_reg == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('m_field_reg', 'n', ($m_field_reg == 'n') ? 1 : ''), '60%');
		$r .= $DSP->tr_c();		

        /** ---------------------------------
        /**  Is field searchable?
        /** ---------------------------------*/
        /*     
        if ($m_field_search == '') $m_field_search = 'n';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('is_field_searchable')), '40%');
		$r .= $DSP->table_qcell($style, $LANG->line('yes').$DSP->nbs().$DSP->input_radio('m_field_search', 'y', ($m_field_search == 'y') ? 1 : '').$DSP->nbs(3).$LANG->line('no').$DSP->nbs().$DSP->input_radio('m_field_search', 'n', ($m_field_search == 'n') ? 1 : ''), '60%');
		$r .= $DSP->tr_c();		              
        */
              


		$r .= $DSP->table_c();

        $r .= $DSP->div('itemWrapper');
		$r .= $DSP->required(1).BR.BR;
        
        if ($type == 'edit')        
            $r .= $DSP->input_submit($LANG->line('update'));
        else
            $r .= $DSP->input_submit($LANG->line('submit'));
              
        $r .= $DSP->div_c();
        
        $r .= $DSP->form_close();
                
        $DSP->title = $LANG->line('edit_member_field');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=profile_fields', $LANG->line('custom_member_fields'))).
        			  $DSP->crumb_item($LANG->line('edit_member_field'));
        $DSP->body  = $r;
    }
    /* END */
    

    /** -----------------------------------------------------------
    /**  Create/update custom fields
    /** -----------------------------------------------------------*/
    // This function alters the "exp_member_data" table, adding
    // the new custom fields.
    //-----------------------------------------------------------

    function update_profile_fields()
    {  
        global $DSP, $IN, $DB, $REGX, $LANG;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }        
        
        $LANG->fetch_language_file('publish_ad');
        
        // If the $field_id variable is present we are editing an
        // existing field, otherwise we are creating a new one
        
        $edit = (isset($_POST['m_field_id']) AND $_POST['m_field_id'] != '') ? TRUE : FALSE;
        
                
        // Check for required fields

        $error = array();
        
        if ($_POST['m_field_name'] == '')
        {
            $error[] = $LANG->line('no_field_name');
        }
        
        if ($_POST['m_field_label'] == '')
        {
            $error[] = $LANG->line('no_field_label');
        }
        
		// Is the field one of the reserved words?

		if (in_array($_POST['m_field_name'], $DSP->invalid_custom_field_names()))
    	{
        	$error[] = $LANG->line('reserved_word');
    	}

        // Does field name have invalid characters?
        
        if ( ! preg_match("#^[a-z0-9\_\-]+$#i", $_POST['m_field_name'])) 
        {
            $error[] = $LANG->line('invalid_characters');
        }
                  
        // Is the field name taken?
        
        $query = $DB->query("SELECT count(*) as count FROM exp_member_fields WHERE m_field_name = '".$DB->escape_str($_POST['m_field_name'])."'");        
      
        if (($edit == FALSE || ($edit == TRUE && $_POST['m_field_name'] != $_POST['cur_field_name']))
            && $query->row['count'] > 0)
        {
            $error[] = $LANG->line('duplicate_field_name');
        }
        
        unset($_POST['cur_field_name']);        
        
        
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
        
        
        if ($_POST['m_field_list_items'] != '')
        {
            $_POST['m_field_list_items'] = $REGX->convert_quotes($_POST['m_field_list_items']);
        }
             
        // Construct the query based on whether we are updating or inserting
   
        if ($edit === TRUE)
        {
            $n = $_POST['m_field_maxl'];
        
            if ($_POST['m_field_type'] == 'text')
            {
                if ( ! is_numeric($n) || $n == '' || $n == 0)
                {
                    $n = '100';
                }
            
                $f_type = 'varchar('.$n.') NOT NULL';
            }
            else
            {
                $f_type = 'text NOT NULL';
            }
        
            $DB->query("ALTER table exp_member_data CHANGE m_field_id_".$_POST['m_field_id']." m_field_id_".$_POST['m_field_id']." $f_type");            
                    
            $id = $_POST['m_field_id'];
            unset($_POST['m_field_id']);
            
            $DB->query($DB->update_string('exp_member_fields', $_POST, 'm_field_id='.$id));  
        }
        else
        {
            if ($_POST['m_field_order'] == 0 || $_POST['m_field_order'] == '')
            {
                $query = $DB->query("SELECT count(*) AS count FROM exp_member_fields");
            
                $total = $query->row['count'] + 1;
            
                $_POST['m_field_order'] = $total; 
            }
            
            unset($_POST['m_field_id']);
                    
            $DB->query($DB->insert_string('exp_member_fields', $_POST));
                                    
            $DB->query("ALTER table exp_member_data add column m_field_id_{$DB->insert_id} text NOT NULL");  
            
            $sql = "SELECT exp_members.member_id
                    FROM exp_members
                    LEFT JOIN exp_member_data ON exp_members.member_id = exp_member_data.member_id
                    WHERE exp_member_data.member_id IS NULL
                    ORDER BY exp_members.member_id";
            
            $query = $DB->query($sql);
            
            if ($query->num_rows > 0)
            {
				foreach ($query->result as $row)
				{
					$DB->query("INSERT INTO exp_member_data (member_id) values ('{$row['member_id']}')");
				}
			}
        }


        return $this->custom_profile_fields();
    }
    /* END */
 


    /** -----------------------------------------------------------
    /**  Delete field confirm
    /** -----------------------------------------------------------*/
    // Warning message if you try to delete a custom profile field
    //-----------------------------------------------------------

    function delete_profile_field_conf()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $m_field_id = $IN->GBL('m_field_id'))
        {
            return false;
        }
        
        $LANG->fetch_language_file('publish_ad');

        $query = $DB->query("SELECT m_field_label FROM exp_member_fields WHERE m_field_id = '$m_field_id'");
        
        $DSP->title = $LANG->line('delete_field');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=profile_fields', $LANG->line('custom_member_fields'))).
        			  $DSP->crumb_item($LANG->line('edit_member_field'));
        
        $DSP->body = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=delete_field'.AMP.'m_field_id='.$m_field_id))
                    .$DSP->input_hidden('m_field_id', $m_field_id)
                    .$DSP->qdiv('alertHeading', $LANG->line('delete_field'))
                    .$DSP->div('box')
                    .$DSP->qdiv('itemWrapper', '<b>'.$LANG->line('delete_field_confirmation').'</b>')
                    .$DSP->qdiv('itemWrapper', '<i>'.$query->row['m_field_label'].'</i>')
                    .$DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone'))
                    .$DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('delete')))
                    .$DSP->div_c()
                    .$DSP->form_close();
    }
    /* END */
    
   
   
    /** -----------------------------------------------------------
    /**  Delete member profile field
    /** -----------------------------------------------------------*/

    function delete_profile_field()
    {  
        global $DSP, $IN, $DB, $LOG, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }

        if ( ! $m_field_id = $IN->GBL('m_field_id'))
        {
            return false;
        }
        
        $query = $DB->query("SELECT m_field_label FROM exp_member_fields WHERE m_field_id = '$m_field_id'");
        $m_field_label = $query->row['m_field_label'];
                
        $DB->query("ALTER TABLE exp_member_data DROP COLUMN m_field_id_".$m_field_id);
        $DB->query("DELETE FROM exp_member_fields WHERE m_field_id = '$m_field_id'");
        
        $LOG->log_action($LANG->line('profile_field_deleted').$DSP->nbs(2).$m_field_label);        

        return $this->custom_profile_fields();
    }
    /* END */
 
  
    /** -----------------------------------------------------------
    /**  Edit field order
    /** -----------------------------------------------------------*/

    function edit_field_order_form()
    {  
        global $DSP, $IN, $DB, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }

        $LANG->fetch_language_file('publish_ad');
        
        $query = $DB->query("SELECT m_field_label, m_field_name, m_field_order FROM exp_member_fields ORDER BY m_field_order");
        
        if ($query->num_rows == 0)
        {
            return false;
        }
                
        $r  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=update_field_order'));
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%');
               
		$r .= $DSP->td('tableHeading', '', '3').
		$LANG->line('edit_field_order').
		$DSP->td_c().
		$DSP->tr_c();
               
        foreach ($query->result as $row)
        {
            $r .= $DSP->tr();
            $r .= $DSP->table_qcell('tableCellOne', $row['m_field_label']);
            $r .= $DSP->table_qcell('tableCellOne', $DSP->input_text($row['m_field_name'], $row['m_field_order'], '4', '3', 'input', '30px'));      
            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));
                
        $r .= $DSP->form_close();

        $DSP->title = $LANG->line('edit_field_order');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=profile_fields', $LANG->line('custom_member_fields'))).
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
        global $DSP, $IN, $DB, $LANG;

        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
                
        foreach ($_POST as $key => $val)
        {
            $DB->query("UPDATE exp_member_fields SET m_field_order = '$val' WHERE m_field_name = '".$DB->escape_str($key)."'");    
        }
        
        return $this->custom_profile_fields();
    }
    /* END */
    

    /** -----------------------------
    /**  Member search form
    /** -----------------------------*/
    
    function member_search_form($message = '')
    {  
        global $LANG, $DSP, $DB, $PREFS;
        
        $DSP->body  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=do_member_search'));

        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('member_search'));
        
        if ($message != '')
        	$DSP->body .= $DSP->qdiv('box', $message);
        
        $DSP->body .= $DSP->div('box');
        
        $DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('member_search_instructions'));

        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('username', 'username'),
                                        $DSP->input_text('username', '', '35', '100', 'input', '300px')
                                     );
             
        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('email', 'email'),
                                        $DSP->input_text('email', '', '35', '100', 'input', '300px')
                                     );
                              
        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('screen_name', 'screen_name'),
                                        $DSP->input_text('screen_name', '', '35', '100', 'input', '300px')
                                     );
                              
        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('url', 'url'),
                                        $DSP->input_text('url', '', '35', '100', 'input', '300px')
                                     );

        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('ip_address', 'ip_address'),
                                        $DSP->input_text('ip_address', '', '35', '100', 'input', '300px')
                                     );
                              
        $DSP->body .= $DSP->itemgroup(
                                        $DSP->qdiv('defaultBold', $LANG->line('member_group'))
                                     );
                              
        // Member group select list

        $query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_title");
              
        $DSP->body .= $DSP->input_select_header('group_id');
        
        $DSP->body .= $DSP->input_select_option('any', $LANG->line('any'));
                                
        foreach ($query->result as $row)
        {                                
            $DSP->body.= $DSP->input_select_option($row['group_id'], $row['group_title']);
        }
        
        $DSP->body .= $DSP->input_select_footer();
          
        $DSP->body .= $DSP->div_c();
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        
        $DSP->body .= $DSP->form_close();

        $DSP->title = $LANG->line('member_search');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('member_search'));
    }
    /* END */



    /** -----------------------------
    /**  Member search
    /** -----------------------------*/
    
    function do_member_search()
    {  
        global $IN, $LANG, $DSP, $FNS, $LOC, $DB, $PREFS;
        
        $pageurl = BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=do_member_search';
        
        $custom = FALSE;
        
		/** -----------------------------
		/**  Homepage source?
		/** -----------------------------*/
		
		// Since we allow a simplified member search field to be displayed
		// on the Control Panel homepage, we need to set the proper POST variable

        if (isset($_POST['criteria']))
        {
        	if ($_POST['keywords'] == '')
        	{
				$FNS->redirect(BASE);
				exit;    
        	}
        	
        	if (substr($_POST['criteria'], 0, 11) == 'm_field_id_' && is_numeric(substr($_POST['criteria'], 11)))
        	{
        		$custom = TRUE;
        	}
        
			$_POST[$_POST['criteria']] = $_POST['keywords'];
			
			unset($_POST['keywords']);
			unset($_POST['criteria']);
        }
        // Done...
                
		/** --------------------------------
		/**  Parse the GET or POST request
		/** --------------------------------*/
        
        if ($Q = $IN->GBL('Q', 'GET'))
        {
            $Q = stripslashes(base64_decode(urldecode($Q)));
        }
        else  
        {
        	foreach (array('username', 'screen_name', 'email', 'url', 'ip_address') as $pval)
        	{
        		if ( ! isset($_POST[$pval]))
        		{
        			$_POST[$pval] = '';
        		}
        	}
        
        	if (	$_POST['username'] 		== '' &&
        			$_POST['screen_name'] 	== '' &&
        			$_POST['email'] 		== '' &&
        			$_POST['url'] 			== '' &&
        			$_POST['ip_address'] 	== '' &&
        			$custom === FALSE
        		) 
        		{
					$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=member_search');
					exit;    
        		}
                  
            $search_query = array();
    
            foreach ($_POST as $key => $val)
            {
                if ($key == 'group_id')
                {
                    if ($val != 'any')
                    {
                        $search_query[] = " g.group_id ='".$DB->escape_str($_POST['group_id'])."'";
                    }
                }
                elseif ($key != 'exact_match')
                {	
                    if ($val != '')
                    {
                    	if (isset($_POST['exact_match']))
                    	{
                    		$search_query[] = $key." = '".$DB->escape_str($val)."'";
                    	}
                    	else
                    	{
                        	$search_query[] = $key." LIKE '%".$DB->escape_like_str($val)."%'";
                        }
                    }
                }
            }
            
            if (count($search_query) < 1)
            {
                return $this->member_search_form();
            }
                        
            $Q = implode(" AND ", $search_query);            
        }      

        $pageurl .= AMP.'Q='.urlencode(base64_encode(stripslashes($Q)));
                
        $sql = "SELECT DISTINCT 
                       m.username,
                       m.member_id,
                       m.screen_name,
                       m.email,
                       m.join_date,
                       m.ip_address,
                       g.group_title
                FROM   exp_members AS m, exp_member_groups AS g";
                
        if ($custom === TRUE)
        {
        	$sql .= ", exp_member_data AS md";
        }
                
        $sql .= " WHERE m.group_id = g.group_id AND g.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND ".$Q;
        
        if ($custom === TRUE)
        {
        	$sql .= " AND md.member_id = m.member_id";
        }
        
        $query = $DB->query($sql);
                
        // No result?  Show the "no results" message
        
        $total_count = $query->num_rows;
        
        if ($total_count == 0)
        {
            return $this->member_search_form($DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('no_search_results'))));
        }
        
        // Get the current row number and add the LIMIT clause to the SQL query
        
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
                        
        $sql .= " LIMIT ".$rownum.", ".$this->perpage;
        
        // Run the query              
    
        $query = $DB->query($sql);  
        
        // Build the table heading  
        
        $r  = $DSP->qdiv('tableHeading', $LANG->line('member_search_results'));    
        
		// "select all" checkbox

        $r .= $DSP->toggle();
        
		$DSP->right_crumb($LANG->line('new_member_search'), BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=member_search');
		
        $DSP->body_props .= ' onload="magic_check()" ';
        
        $r .= $DSP->magic_checkboxes();
        
        // Declare the "delete" form
        
        $r .= $DSP->form_open(
        						array(
        								'action' => 'C=admin'.AMP.'M=members'.AMP.'P=mbr_del_conf', 
        								'name'	=> 'target',
        								'id'	=> 'target'
        							)
        					);
        
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('username')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('screen_name')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('email')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('ip_address')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('join_date')).
              $DSP->table_qcell('tableHeadingAlt', $LANG->line('member_group')).
              $DSP->table_qcell('tableHeadingAlt', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              $DSP->tr_c();
        
               
        // Loop through the query result and write each table row 
               
        $i = 0;
        
        foreach($query->result as $row)
        {
            $style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
                      
            $r .= $DSP->tr();
            
            // Username
            
            $r .= $DSP->table_qcell($style, 
                                    $DSP->anchor(
                                                  BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], 
                                                  '<b>'.$row['username'].'</b>'
                                                )
                                    );
            // Screen name
            
            $screen = ($row['screen_name'] == '') ? "--" : $row['screen_name'];
            
            $r .= $DSP->table_qcell($style, $screen);
             
            // Email
            
            $r .= $DSP->table_qcell($style, 
                                    $DSP->mailto($row['email'], $row['email'])
                                    );                  
            // IP Address

            $r .= $DSP->td($style);
			$r .= $row['ip_address'];
			$r .= $DSP->td_c();
			
            // Join date

            $r .= $DSP->td($style).
                  $LOC->convert_timestamp('%Y', $row['join_date']).'-'.
                  $LOC->convert_timestamp('%m', $row['join_date']).'-'.
                  $LOC->convert_timestamp('%d', $row['join_date']).
                  $DSP->td_c();
                  
            // Member group
            
            $r .= $DSP->td($style);
            
            $r .= $row['group_title'];
                
            $r .= $DSP->td_c();

            // Delete checkbox
            
            $r .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['member_id'], '', " id='delete_box_".$row['member_id']."'"));
                  
            $r .= $DSP->tr_c();
            
        } // End foreach
        

        $r .= $DSP->table_c();
                        
        $r .= $DSP->table('', '0', '', '98%');
        $r .= $DSP->tr().
              $DSP->td();
               
        // Pass the relevant data to the paginate class so it can display the "next page" links
        
        $r .=  $DSP->div('crumblinks').
               $DSP->pager(
                            $pageurl,
                            $total_count,
                            $this->perpage,
                            $rownum,
                            'rownum'
                          ).
              $DSP->div_c().
              $DSP->td_c().
              $DSP->td('defaultRight');
        
        // Delete button
        
        $r .= $DSP->input_submit($LANG->line('delete')).
              $DSP->td_c().
              $DSP->tr_c();
              
        // Table end
        
        $r .= $DSP->table_c().
              $DSP->form_close();
        
        $DSP->title = $LANG->line('member_search');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('member_search'));
        $DSP->body  = $r;
    }
    /* END */
    
   
    /** -----------------------------
    /**  IP Search Form
    /** -----------------------------*/
    
    function ip_search_form($message = '')
    {  
        global $LANG, $DSP, $DB, $IN;

        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
		$ip = ($IN->GBL('ip_address') != FALSE) ? str_replace('_', '.',$IN->GBL('ip_address')) : '';

        $DSP->body  = $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=do_ip_search'));

        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('ip_search'));
        
        if ($message != '')
        	$DSP->body .= $DSP->qdiv('box', $message);
        
        $DSP->body .= $DSP->div('box');
        
        if ($IN->GBL('error') == 2)
        {
        	$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('ip_search_no_results')));
        }
        elseif ($IN->GBL('error') == 1)
        {
        	$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('ip_search_too_short')));
        }

        
        $DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('ip_search_instructions'));

        $DSP->body .= $DSP->itemgroup(
                                        $LANG->line('ip_address', 'ip_address'),
                                        $DSP->input_text('ip_address', $ip, '35', '100', 'input', '300px')
                                     );
                                        
        $DSP->body .= $DSP->div_c();
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        
        $DSP->body .= $DSP->form_close();

        $DSP->title = $LANG->line('member_search');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('member_search'));
    }
    /* END */

   
    /** -----------------------------
    /**  IP Search
    /** -----------------------------*/
    
    function do_ip_search($message = '')
    {  
        global $IN, $FNS, $LANG, $DSP, $DB, $LOC, $PREFS;

        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
			
		$ip = str_replace('_', '.', $IN->GBL('ip_address'));
		$url_ip = str_replace('.', '_', $ip);
	
		if ($ip == '') 
		{
			$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=ip_search');
			exit;    
		}
	
		if (strlen($ip) < 3) 
		{
			$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=ip_search'.AMP.'error=1'.AMP.'ip_address='.$url_ip);
			exit;    
		}

		
		/** -----------------------------
		/**  Set some defaults for pagination
		/** -----------------------------*/
		
		$w_page = ($IN->GBL('w_page') == FALSE) ? 0 : $IN->GBL('w_page');
		$m_page = ($IN->GBL('m_page') == FALSE) ? 0 : $IN->GBL('m_page');
		$c_page = ($IN->GBL('c_page') == FALSE) ? 0 : $IN->GBL('c_page');
		$g_page = ($IN->GBL('g_page') == FALSE) ? 0 : $IN->GBL('g_page');
		$t_page = ($IN->GBL('t_page') == FALSE) ? 0 : $IN->GBL('t_page');
		$p_page = ($IN->GBL('p_page') == FALSE) ? 0 : $IN->GBL('p_page');
		
		
		$page_url = BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=do_ip_search'.AMP.'ip_address='.$url_ip;
		
		
		$r = '';

		/** -----------------------------
		/**  Find Member Accounts with IP
		/** -----------------------------*/
		
		$sql_a = "SELECT COUNT(*) AS count ";
		$sql_b = "SELECT member_id, username, screen_name, ip_address, email, join_date ";
		$sql   = "FROM exp_members 
				 	WHERE ip_address LIKE '%".$DB->escape_like_str($ip)."%'
					ORDER BY screen_name desc ";
		
		// Run the query the first time to get total for pagination
		$query = $DB->query($sql_a.$sql);
		$total = $query->row['count'];
						
		if ($total > 0)
		{
			if ($total > 10)
			{
				$sql .= " LIMIT ".$m_page.", 10";
			}
			
			// Run the full query
			$query = $DB->query($sql_b.$sql);

			$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
			$r .= $DSP->table_row(array(array('text' => $LANG->line('member_accounts'),'class'   => 'tableHeading', 'colspan' => '4' )));
			$r .= $DSP->table_row(array(
										array('text' => $LANG->line('username'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
										array('text' => $LANG->line('screen_name'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
										array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
										array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
										)
								);
			$i = 0;
			foreach($query->result as $row)
			{
				$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
				$r .= $DSP->table_row(array(
											array('text' => $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['username'].'</b>'), 'class' => $class),
											array('text' => ($row['screen_name'] == '') ? "--" : $row['screen_name'], 'class' => $class),
											array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
											array('text' => $row['ip_address'], 'class' => $class)
											)
									);
			} // End foreach

			$r .= $DSP->table_close();
			
        	if ($total > 10)
        	{
				$r .=  $DSP->div('crumblinks').
					   $DSP->pager(
									$page_url.AMP.'w_page='.$w_page.AMP.'c_page='.$c_page.AMP.'g_page='.$g_page.AMP.'t_page='.$t_page.AMP.'p_page='.$p_page,
									$total,
									10,
									$m_page,
									'm_page'
								  ).
					  $DSP->div_c();
			}
		}

		/** -----------------------------
		/**  Find Weblog Entries with IP
		/** -----------------------------*/
		
		$sql_a = "SELECT COUNT(*) AS count ";
		$sql_b = "SELECT s.site_label, t.entry_id, t.weblog_id, t.title, t.ip_address, m.member_id, m.username, m.screen_name, m.email ";
		
		$sql = "FROM exp_weblog_titles t, exp_members m, exp_sites s
				WHERE t.ip_address LIKE '%".$DB->escape_like_str($ip)."%'
				AND t.site_id = s.site_id
				AND t.author_id = m.member_id
				ORDER BY entry_id desc ";

		// Run the query the first time to get total for pagination
		
		$query = $DB->query($sql_a.$sql);
		$total = $query->row['count'];
						
		if ($total > 0)
		{
			if ($total > 10)
			{
				$sql .= " LIMIT ".$w_page.", 10";
			}
			
			// Run the full query
			$query = $DB->query($sql_b.$sql);
	
			$r .= $DSP->qdiv('defaultSmall', BR);
			$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
			$r .= $DSP->table_row(array(array('text' => $LANG->line('weblog_entries'),'class'   => 'tableHeading', 'colspan' => ($PREFS->ini('multiple_sites_enabled') !== 'y') ? '4' : '5' )));
			
			if ($PREFS->ini('multiple_sites_enabled') !== 'y')
			{
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('title'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
											array('text' => $LANG->line('screen_name'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
			}
			else
			{
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('title'), 'class' => 'tableHeadingAlt', 'width' => '40%'),
											array('text' => $LANG->line('site'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
											array('text' => $LANG->line('screen_name'), 'class' => 'tableHeadingAlt', 'width' => '15%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
			}
			
			
			$i = 0;
			foreach($query->result as $row)
			{
				$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
				
				if ($PREFS->ini('multiple_sites_enabled') !== 'y')
				{
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'], '<b>'.$row['title'].'</b>'), 'class' => $class),
												array('text' => $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['screen_name'].'</b>'), 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				}
				else
				{
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'], '<b>'.$row['title'].'</b>'), 'class' => $class),
												array('text' => $row['site_label'], 'class' => $class),
												array('text' => $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['screen_name'].'</b>'), 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				}
			} // End foreach

			$r .= $DSP->table_close();
			
        	if ($total > 10)
        	{
				$r .=  $DSP->div('crumblinks').
					   $DSP->pager(
									$page_url.AMP.'m_page='.$m_page.AMP.'c_page='.$c_page.AMP.'g_page='.$g_page.AMP.'t_page='.$t_page.AMP.'p_page='.$p_page,
									$total,
									10,
									$w_page,
									'w_page'
								  ).
					  $DSP->div_c();
			}
		}


		/** -----------------------------
		/**  Find Comments with IP
		/** -----------------------------*/
		
		// But only if the comment module is installed
		
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Comment'");
		
		if ($query->row['count'] == 1)
		{
			$sql_a = "SELECT COUNT(*) AS count ";
			$sql_b = "SELECT comment_id, entry_id, weblog_id, author_id, comment, name, email, ip_address "; 
			$sql = "FROM exp_comments 
					WHERE ip_address LIKE '%".$DB->escape_like_str($ip)."%'
					ORDER BY comment_id desc ";
			
			// Run the query the first time to get total for pagination
			
			$query = $DB->query($sql_a.$sql);
			$total = $query->row['count'];
							
			if ($total > 0)
			{
				if ($total > 10)
				{
					$sql .= " LIMIT ".$c_page.", 10";
				}
				
				// Run the full query
				$query = $DB->query($sql_b.$sql);
		
				$r .= $DSP->qdiv('defaultSmall', BR);
				$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
				$r .= $DSP->table_row(array(array('text' => $LANG->line('comments'),'class'   => 'tableHeading', 'colspan' => '4' )));
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('comment'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
											array('text' => $LANG->line('author'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
				$i = 0;
				foreach($query->result as $row)
				{
					if ($row['author_id'] != 0)
					{
						$author = $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['author_id'], '<b>'.$row['name'].'</b>');
					}
					else
					{
						$author = '<b>'.$row['name'].'</b>';
					}
					
					$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor(BASE.AMP.'C=edit'.AMP.'M=edit_comment'.AMP.'weblog_id='.$row['weblog_id'].AMP.'entry_id='.$row['entry_id'].AMP.'comment_id='.$row['comment_id'].AMP.'current_page=0', '<b>'.substr(strip_tags($row['comment']), 0, 45).'...</b>'), 'class' => $class),
												array('text' => $author, 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				} // End foreach
	
				$r .= $DSP->table_close();
				
				if ($total > 10)
				{
					$r .=  $DSP->div('crumblinks').
						   $DSP->pager(
										$page_url.AMP.'m_page='.$m_page.AMP.'w_page='.$w_page.AMP.'g_page='.$g_page.AMP.'t_page='.$t_page.AMP.'p_page='.$p_page,
										$total,
										10,
										$c_page,
										'c_page'
									  ).
						  $DSP->div_c();
				}
			}
		}


		/** -----------------------------
		/**  Find Gallery Comments with IP
		/** -----------------------------*/
		
		// But only if the gallery module is installed
		
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Gallery'");
		
		if ($query->row['count'] == 1)
		{
			$sql_a = "SELECT COUNT(*) AS count ";
			$sql_b = "SELECT e.cat_id, c.gallery_id, c.comment_id, c.entry_id, c.author_id, c.comment, c.name, c.email, c.ip_address "; 
			$sql = "FROM exp_gallery_comments c, exp_gallery_entries e
					WHERE ip_address LIKE '%".$DB->escape_like_str($ip)."%'
					AND c.entry_id = e.entry_id
					ORDER BY c.comment_id desc ";
			
			// Run the query the first time to get total for pagination
			
			$query = $DB->query($sql_a.$sql);
			$total = $query->row['count'];
							
			if ($total > 0)
			{
				if ($total > 10)
				{
					$sql .= " LIMIT ".$g_page.", 10";
				}
				
				// Run the full query
				$query = $DB->query($sql_b.$sql);
		
				$r .= $DSP->qdiv('defaultSmall', BR);
				$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
				$r .= $DSP->table_row(array(array('text' => $LANG->line('gallery_comments'),'class'   => 'tableHeading', 'colspan' => '4' )));
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('comment'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
											array('text' => $LANG->line('author'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
				$i = 0;
				foreach($query->result as $row)
				{
					if ($row['author_id'] != 0)
					{
						$author = $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['author_id'], '<b>'.$row['name'].'</b>');
					}
					else
					{
						$author = '<b>'.$row['name'].'</b>';
					}
										
					$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_comment'.AMP.'gallery_id='.$row['gallery_id'].AMP.'entry_id='.$row['entry_id'].AMP.'comment_id='.$row['comment_id'].AMP.'row='.AMP.'cat_id='.$row['cat_id'], '<b>'.substr($row['comment'], 0, 45).'...</b>'), 'class' => $class),
												array('text' => $author, 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				} // End foreach
	
				$r .= $DSP->table_close();
				
				if ($total > 10)
				{
					$r .=  $DSP->div('crumblinks').
						   $DSP->pager(
										$page_url.AMP.'m_page='.$m_page.AMP.'w_page='.$w_page.AMP.'c_page='.$c_page.AMP.'t_page='.$t_page.AMP.'p_page='.$p_page,
										$total,
										10,
										$g_page,
										'g_page'
									  ).
						  $DSP->div_c();
				}
			}
		}

		/** -----------------------------
		/**  Find Forum Topics with IP
		/** -----------------------------*/
		
		// But only if the forum module is installed
		
		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");
		
		if ($query->row['count'] == 1)
		{
			$sql_a = "SELECT COUNT(*) AS count ";
			$sql_b = "SELECT f.topic_id, f.forum_id, f.title, f.ip_address, m.member_id, m.screen_name, m.email, b.board_forum_url "; 
			$sql = "FROM exp_forum_topics f, exp_members m, exp_forum_boards b
					WHERE f.ip_address LIKE '%".$DB->escape_like_str($ip)."%'
					AND f.board_id = b.board_id
					AND f.author_id = m.member_id
					ORDER BY f.topic_id desc ";
			
			// Run the query the first time to get total for pagination
			
			$query = $DB->query($sql_a.$sql);
			$total = $query->row['count'];
							
			if ($total > 0)
			{
				if ($total > 10)
				{
					$sql .= " LIMIT ".$t_page.", 10";
				}
				
				// Run the full query
				$query = $DB->query($sql_b.$sql);
		
				$r .= $DSP->qdiv('defaultSmall', BR);
				$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
				$r .= $DSP->table_row(array(array('text' => $LANG->line('forum_topics'),'class'   => 'tableHeading', 'colspan' => '4' )));
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('topic'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
											array('text' => $LANG->line('author'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
				$i = 0;
				foreach($query->result as $row)
				{					
					$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);

				
					$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor($FNS->remove_double_slashes($row['board_forum_url'].'/viewthread/').$row['topic_id'].'/', '<b>'.$row['title'].'</b>'), 'class' => $class),
												array('text' => $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['screen_name'].'</b>'), 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				} // End foreach
	
				$r .= $DSP->table_close();
				
				if ($total > 10)
				{
					$r .=  $DSP->div('crumblinks').
						   $DSP->pager(
										$page_url.AMP.'m_page='.$m_page.AMP.'w_page='.$w_page.AMP.'c_page='.$c_page.AMP.'g_page='.$g_page.AMP.'p_page='.$p_page,
										$total,
										10,
										$t_page,
										't_page'
									  ).
						  $DSP->div_c();
				}
			}

		/** -----------------------------
		/**  Find Forum Posts with IP
		/** -----------------------------*/

			$sql_a = "SELECT COUNT(*) AS count ";
			$sql_b = "SELECT p.post_id, p.forum_id, p.body, p.ip_address, m.member_id, m.screen_name, m.email, b.board_forum_url "; 
			$sql = "FROM exp_forum_posts p, exp_members m, exp_forum_boards b
					WHERE p.ip_address LIKE '%".$DB->escape_like_str($ip)."%'
					AND p.author_id = m.member_id
					AND p.board_id = b.board_id
					ORDER BY p.topic_id desc ";
			
			// Run the query the first time to get total for pagination
			
			$query = $DB->query($sql_a.$sql);
			$total = $query->row['count'];
							
			if ($total > 0)
			{
				if ($total > 10)
				{
					$sql .= " LIMIT ".$p_page.", 10";
				}
				
				// Run the full query
				$query = $DB->query($sql_b.$sql);
		
				$r .= $DSP->qdiv('defaultSmall', BR);
				$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
				$r .= $DSP->table_row(array(array('text' => $LANG->line('forum_posts'),'class'   => 'tableHeading', 'colspan' => '4' )));
				$r .= $DSP->table_row(array(
											array('text' => $LANG->line('topic'), 'class' => 'tableHeadingAlt', 'width' => '50%'),
											array('text' => $LANG->line('author'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('email'), 'class' => 'tableHeadingAlt', 'width' => '20%'),
											array('text' => $LANG->line('ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%')
											)
									);
				$i = 0;
				foreach($query->result as $row)
				{
					$class = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
					$r .= $DSP->table_row(array(
												array('text' => $DSP->anchor($FNS->remove_double_slashes($row['board_forum_url'].'/viewreply/').$row['post_id'].'/', '<b>'.substr($row['body'], 0, 45).'</b>'), 'class' => $class),
												array('text' => $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], '<b>'.$row['screen_name'].'</b>'), 'class' => $class),
												array('text' => $DSP->mailto($row['email'], $row['email']), 'class' => $class),
												array('text' => $row['ip_address'], 'class' => $class)
												)
										);
				} // End foreach
	
				$r .= $DSP->table_close();
				
				if ($total > 10)
				{
					$r .=  $DSP->div('crumblinks').
						   $DSP->pager(
										$page_url.AMP.'m_page='.$m_page.AMP.'w_page='.$w_page.AMP.'c_page='.$c_page.AMP.'g_page='.$g_page.AMP.'t_page='.$t_page,
										$total,
										10,
										$p_page,
										'p_page'
									  ).
						  $DSP->div_c();
				}
			}
		}


		/** -----------------------------
		/**  Were there results?
		/** -----------------------------*/
		
		if ($r == '')
		{
			$FNS->redirect(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=ip_search'.AMP.'error=2'.AMP.'ip_address='.$url_ip);
			exit;    
		}
		
		
		$DSP->body  = $r;
        $DSP->title = $LANG->line('ip_search');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('ip_search'));		
	}
	/* END */
    
    /** ---------------------------------
    /**  Member Validation
    /** ---------------------------------*/
    
    function member_validation()
    {
    	global $DSP, $DB, $LANG, $LOC;
    	
    	        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
    	
        $title = $LANG->line('member_validation');
        
        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($title);
        
        $DSP->body = $DSP->qdiv('tableHeading', $title);
        
		$query = $DB->query("SELECT member_id, username, screen_name, email, join_date FROM exp_members WHERE group_id = '4' ORDER BY join_date");

		if ($query->num_rows == 0)
		{
			$DSP->body .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_members_to_validate')));
		
			return;
		}
        
        
        $DSP->body .= 	$DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
        $DSP->body .= $DSP->magic_checkboxes();
        
        $DSP->body .= 	$DSP->form_open(
        									array(
        											'action' => 'C=admin'.AMP.'M=members'.AMP.'P=validate_members', 
        											'name'	=> 'target',
        											'id'	=> 'target'
        										)
        								);
        
        $DSP->body .=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr().
						$DSP->table_qcell('tableHeadingAlt', 
										array(
												NBS,
												$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\""),
												$LANG->line('username'),
												$LANG->line('screen_name'),
												$LANG->line('email'),
												$LANG->line('join_date')
											 )
										).
						$DSP->tr_c();

        
        $i = 0;
		$n = 1;
		      
        foreach ($query->result as $row)
        {
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
            
            $DSP->body .= $DSP->tr();

            $DSP->body .= $DSP->table_qcell($style, $DSP->qspan('', $n++), '1%');
            
            
            // Checkbox
            
            $DSP->body .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['member_id'], '', "id='delete_box_".$row['member_id']."'"), '3%');
            
            // Username          
            
            $DSP->body .= $DSP->table_qcell($style, 
                                    $DSP->anchor(
                                                  BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], 
                                                  '<b>'.$row['username'].'</b>',
                                                  '24%'
                                                )
                                    );
            // Screen name
            
            $screen = ($row['screen_name'] == '') ? "--" : '<b>'.$row['screen_name'].'</b>';
            
            $DSP->body .= $DSP->table_qcell($style, $screen, '24%');
            
            // Email        
                                                 
            $DSP->body .= $DSP->table_qcell($style, $DSP->mailto($row['email']), '24%');
        
            // Join Date        
                                                 
            $DSP->body .= $DSP->table_qcell($style, $LOC->set_human_time($row['join_date']), '24%');
            
            $DSP->body .= $DSP->tr_c();      
        }

        $DSP->body .= $DSP->table_c();
        
		$DSP->body .= $DSP->div('box');
        $DSP->body .= $DSP->input_select_header('action');
		$DSP->body .= $DSP->input_select_option('activate', $LANG->line('validate_selected'), 1);
		$DSP->body .= $DSP->input_select_option('delete', $LANG->line('delete_selected'), '');
        $DSP->body .= $DSP->input_select_footer();
        $DSP->body.= $DSP->qdiv('itemWrapper', BR.$DSP->input_checkbox('send_notification', 'y', 1).NBS.$LANG->line('send_email_notification').BR); 
        $DSP->body .= $DSP->div_c();
        
        $DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
        
        
		$DSP->body .= $DSP->form_close(); 
    }
    /* END */
        
        
    /** ---------------------------------
    /**  Validate/Delete Selected Members
    /** ---------------------------------*/
    
    function validate_members()
    {
		global $IN, $DSP, $DB, $LANG, $PREFS, $REGX, $FNS, $EXT, $STAT;
		    	        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $DSP->allowed_group('can_delete_members'))
        {
        	if ($_POST['action'] == 'delete')
        	{
				return $DSP->no_access_message();
			}
        }

        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->member_validation();
        }

		$send_email = (isset($_POST['send_notification'])) ? TRUE : FALSE;
		
		if ($send_email == TRUE)
		{
			if ($_POST['action'] == 'activate')
			{
				$template = $FNS->fetch_email_template('validated_member_notify');
			}
			else
			{
				$template = $FNS->fetch_email_template('decline_member_validation');
			}
			
			require PATH_CORE.'core.email'.EXT;
			
			$email = new EEmail;
			$email->wordwrap = true;
		}


		$group_id = $PREFS->ini('default_member_group');
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {                   
				if ($send_email == TRUE)
				{
					$query = $DB->query("SELECT username, screen_name, email FROM exp_members WHERE member_id = '$val'");    
					
					if ($query->num_rows == 1 AND $query->row['email'] != "")
					{	
						$swap = array(
										'name'		=> ($query->row['screen_name'] != '') ? $query->row['screen_name'] : $query->row['username'],
										'site_name'	=> stripslashes($PREFS->ini('site_name')),
										'site_url'	=> $PREFS->ini('site_url')
									 );
						
						
						$email_tit = $FNS->var_swap($template['title'], $swap);
						$email_msg = $FNS->var_swap($template['data'], $swap);
										
						$email->initialize();
						$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
						$email->to($query->row['email']); 
						$email->subject($email_tit);	
						$email->message($REGX->entities_to_ascii($email_msg));		
						$email->Send();
					}
				}
				
            	if (isset($_POST['action']) && $_POST['action'] == 'activate')
            	{
					$DB->query("UPDATE exp_members SET group_id = '$group_id' WHERE member_id = '".$DB->escape_str($val)."'");
				}
            	else
            	{
					$DB->query("DELETE FROM exp_members WHERE member_id = '$val'");
					$DB->query("DELETE FROM exp_member_data WHERE member_id = '$val'");
					$DB->query("DELETE FROM exp_member_homepage WHERE member_id = '$val'");
					
					$message_query = $DB->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '$val' AND message_read = 'n'");
					$DB->query("DELETE FROM exp_message_copies WHERE sender_id = '$val'");
					$DB->query("DELETE FROM exp_message_data WHERE sender_id = '$val'");
					$DB->query("DELETE FROM exp_message_folders WHERE member_id = '$val'");
					$DB->query("DELETE FROM exp_message_listed WHERE member_id = '$val'");
					
					if ($message_query->num_rows > 0)
					{
						foreach($message_query->result as $row)
						{
							$count_query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
							$DB->query($DB->update_string('exp_members', array('private_messages' => $count_query->row['count']), "member_id = '".$row['recipient_id']."'"));
						}
					}
            	}
			}
        }
        
		$STAT->update_member_stats();
		
		// -------------------------------------------
        // 'cp_members_validate_members' hook.
        //  - Additional processing when member(s) are validated in the CP
        //  - Added 1.5.2, 2006-12-28
        //
        	$edata = $EXT->call_extension('cp_members_validate_members');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------

        $title = $LANG->line('member_validation');
        
        $DSP->title = $title;
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($title);
        
        $DSP->body = $DSP->qdiv('tableHeading', $title);
        
        $msg = ($_POST['action'] == 'activate') ? $LANG->line('members_are_validated') : $LANG->line('members_are_deleted');

		$DSP->body .= $DSP->qdiv('box', $msg);
	}
	/* END */
	
	
	
    /** ---------------------------------
    /**  View Email Console Logs
    /** ---------------------------------*/
	
	function email_console_logs($message = '')
	{
    	global $IN, $DB, $LANG, $DSP, $LOC;
    
		if ( ! $DSP->allowed_group('can_admin_members'))
		{     
			return $DSP->no_access_message();
		}
    
    
		/** -----------------------------
    	/**  Define base variables
    	/** -----------------------------*/
    	
		$i = 0;

		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		$row_limit 	= 100;
		$paginate	= '';
		$row_count	= 0;
		
        $DSP->title = $LANG->line('email_console_log');
        $DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			  $DSP->crumb_item($LANG->line('email_console_log'));
		
		$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('email_console_log'));
		
        if ($message != '')
        {
			$DSP->body .= $DSP->qdiv('box', $DSP->qdiv('success', $message));
        }
		
		
		/** -----------------------------
    	/**  Run Query
    	/** -----------------------------*/
		
		$sql = "SELECT	cache_id, member_id, member_name, recipient_name, cache_date, subject
				FROM	exp_email_console_cache
				ORDER BY cache_id desc";
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			if ($message == '')
				$DSP->body	.=	$DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_cached_email')));             
			
			return;
		}		
		
		/** -----------------------------
    	/**  Do we need pagination?
    	/** -----------------------------*/
		
		if ($query->num_rows > $row_limit)
		{ 
			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');
						
			$url = BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=email_console_logs';
						
			$paginate = $DSP->pager(  $url,
									  $query->num_rows, 
									  $row_limit,
									  $row_count,
									  'row'
									);
			 
			$sql .= " LIMIT ".$row_count.", ".$row_limit;
			
			$query = $DB->query($sql);    
		}
    			
		
		
        $DSP->body .= $DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
        $DSP->body .= $DSP->magic_checkboxes();
        
        $DSP->body .= $DSP->form_open(
        								array(
        									'action' => 'C=admin'.AMP.'M=members'.AMP.'P=delete_email_console', 
        									'name'	=> 'target',
        									'id'	=> 'target'
        									)
        							);

        $DSP->body .= $DSP->table('tableBorder', '0', '0', '100%').
					  $DSP->tr().
					  $DSP->table_qcell('tableHeadingAlt',
										array(
												NBS,
												$LANG->line('email_title'), 
												$LANG->line('from'), 
												$LANG->line('to'), 
												$LANG->line('date'),
												$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.NBS
											  )
											).
              $DSP->tr_c();
              
		/** -----------------------------
    	/**  Table Rows
    	/** -----------------------------*/
		
		$row_count++;  		
              
		foreach ($query->result as $row)
		{			
			$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
									array(
											$row_count,
													
                  							$DSP->anchorpop(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=view_email'.AMP.'id='.$row['cache_id'].AMP.'Z=1', '<b>'.$row['subject'].'</b>', '600', '580'),
											
											$DSP->qspan('defaultBold', $row['member_name']),
											
											$DSP->qspan('defaultBold', $row['recipient_name']),
											
											$LOC->set_human_time($row['cache_date']),
																																																							
											$DSP->input_checkbox('toggle[]', $row['cache_id'], '', " id='delete_box_".$row['cache_id']."'")

										  )
									);
			$row_count++;  		
		}	
        
        $DSP->body .= $DSP->table_c(); 
					  

    	if ($paginate != '')
    	{
    		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $paginate));
    	}
    
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('delete')));             
        
        $DSP->body .= $DSP->form_close();
	}
	/* END */
	
	

	/** -----------------------------
	/**  View Email
	/** -----------------------------*/

	function view_email()
	{
    	global $IN, $DB, $LANG, $DSP, $LOC;
    
		if ( ! $DSP->allowed_group('can_admin_members'))
		{     
			return $DSP->no_access_message();
		}
		
		$id = $IN->GBL('id');
		
		/** -----------------------------
    	/**  Run Query
    	/** -----------------------------*/
				
		$query = $DB->query("SELECT subject, message, recipient, recipient_name, member_name, ip_address FROM exp_email_console_cache WHERE cache_id = '$id' ");
		
		if ($query->num_rows == 0)
		{
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_cached_email')));             
			
			return;
		}
	
		/** -----------------------------
    	/**  Render output
    	/** -----------------------------*/
				
		$DSP->body .= $DSP->heading(BR.$query->row['subject']);
				
		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/
	  
		if ( ! class_exists('Typography'))
		{
			require PATH_CORE.'core.typography'.EXT;
		}
            
		$TYPE = new Typography;
		
		$DSP->body .= $TYPE->parse_type( $query->row['message'], 
								 array(
											'text_format'   => 'xhtml',
											'html_format'   => 'all',
											'auto_links'    => 'y',
											'allow_img_url' => 'y'
									   )
								);
										
		$DSP->body	.= $DSP->qdiv('', BR); 	
        $DSP->body	.= $DSP->table('tableBorderNoBot', '0', '10', '100%');
		$DSP->body	.= $DSP->tr();
		$DSP->body 	.= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('from')));
		$DSP->body 	.= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $query->row['member_name']));
		$DSP->body 	.= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $query->row['ip_address']));
		$DSP->body 	.= $DSP->tr_c();
		$DSP->body 	.= $DSP->tr();
		$DSP->body 	.= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('to')));
		$DSP->body 	.= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $query->row['recipient_name']));
		$DSP->body 	.= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $DSP->mailto($query->row['recipient'])));
		$DSP->body 	.= $DSP->tr_c();		
		$DSP->body 	.= $DSP->table_c(); 
	}
	/* END */
	 
     
    /** -------------------------------------------
    /**  Delete Emails
    /** -------------------------------------------*/

    function delete_email_console_messages()
    { 
        global $IN, $DSP, $LANG, $DB;
        
		if ( ! $DSP->allowed_group('can_admin_members'))
		{     
			return $DSP->no_access_message();
		}
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->email_console_logs();
        }

        $ids = array();
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $ids[] = "cache_id = '".$DB->escape_str($val)."'";
            }        
        }
        
        $IDS = implode(" OR ", $ids);
        
        $DB->query("DELETE FROM exp_email_console_cache WHERE ".$IDS);
    
        return $this->email_console_logs($LANG->line('email_deleted'));
    }
    /* END */
    
        
    /** -----------------------------
    /**  Member Profile Templates
    /** -----------------------------*/

	// Template Overview

    function profile_templates()
    {
        global $DSP, $IN, $PREFS, $LANG;
  
        if ( ! $DSP->allowed_group('can_admin_mbr_templates'))
        {
            return $DSP->no_access_message();
        }
        
        
		$r  = $DSP->table_open(array('class' => 'tableBorder', 'width' => '60%'));
		 		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $LANG->line('profile_templates'),
											'class'		=> 'tableHeading',
											'colspan'	=> 2
										)
									)
							);
							
		$themes = array();
		
        if ($fp = @opendir(PATH_MBR_THEMES))
        { 
            while (false !== ($file = readdir($fp)))
            {            
                if (is_dir(PATH_MBR_THEMES.$file) AND $file != '.' AND $file != '..' AND $file != '.svn' AND $file != '.cvs') 
                {                
					$themes[] = $file;
                }
            }         
			
			closedir($fp); 
        } 		        
  
        if (count($themes) == 0)
        {
			$r .= $DSP->table_row(array(
										array(
												'text'		=> $DSP->qdiv('defaultBold', $LANG->line('unable_to_find_templates')),
												'class'		=> 'tableCellTwo',
												'colspan'	=> 2
											)
										)
								);
        }  
        else
        {
			$i = 0;
            foreach ($themes as $set)
            {
                $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
                
				$template_name = ucfirst(str_replace("_", " ", $set));
								
				$folder = '<img src="'.PATH_CP_IMG.'folder.gif" border="0"  width="12" height="12" alt="'.$template_name.'" />';
				$r .= $DSP->table_row(array(
											array(
													'text'	=> $i.$DSP->nbs(2),
													'class'	=> $style,
													'width'	=> '2%'
												),
											array(
													'text'	=> $folder.NBS.NBS.$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=list_templates'.AMP.'name='.$set, $template_name),
													'class'	=> $style,
													'width'	=> '98%'
												)
											)
									);
            }
        }
                  
 		$r .= $DSP->table_close();
        
        $DSP->title  = $LANG->line('profile_templates');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			   $DSP->crumb_item($LANG->line('profile_templates'));
        $DSP->body   = $r;  
    }
	/* END */
    
    
    /** -----------------------------
    /**  List Templates within a set
    /** -----------------------------*/
    
    function list_templates()
    {
    	global $IN, $PREFS, $LANG, $DSP, $FNS;
    	
        if ( ! $DSP->allowed_group('can_admin_mbr_templates'))
        {
            return $DSP->no_access_message();
        }
            		
		$path = PATH_MBR_THEMES.$FNS->filename_security($IN->GBL('name')).'/profile_theme'.EXT;
		
		if ( ! file_exists($path))
		{
            return $DSP->no_access_message($LANG->line('unable_to_find_templates'));
		}
    
		if ( ! class_exists('profile_theme'))
		{
            require $path;
		}
		
		$template_name = ucfirst(str_replace("_", " ", $IN->GBL('name')));
		$class_methods = get_class_methods('profile_theme');
		

		$r  = $DSP->table_open(array('class' => 'tableBorder', 'width' => '60%'));
		
		$r .= $DSP->table_row(array(
									array(
											'text'		=> $template_name,
											'class'		=> 'tableHeading'
										)
									)
							);
							

		$t_array = array();
    	foreach ($class_methods as $val)
		{			
			$t_array[$val] = ($LANG->line($val) == FALSE) ? $val : $LANG->line($val); 
		}

		asort($t_array);
							
	    $i = 0;
    	foreach ($t_array as $key => $val)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$folder = '<img src="'.PATH_CP_IMG.'folder.gif" border="0"  width="12" height="12" alt="'.$val.'" />';
			$r .= $DSP->table_row(array(
										array(
												'text'	=> $folder.NBS.NBS.$DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=edit_template'.AMP.'name='.$IN->GBL('name').AMP.'function='.$key, $val)),
												'class'	=> $style
											)
										)
								);
		}
							
        $r .= $DSP->div_c();
 		$r .= $DSP->td_c();
 		$r .= $DSP->tr_c();
 		$r .= $DSP->table_close();
		
                
        $DSP->title  = $template_name;
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=profile_templates', $LANG->line('profile_templates'))).
        			   $DSP->crumb_item($template_name);
        $DSP->body   = $r;  
    }
    /* END */
    
    
    
    
    /** -----------------------------
    /**  Edit Profile Template
    /** -----------------------------*/
    
    function edit_template($name = '', $function = '', $template_data = '')
    {
    	global $IN, $DSP, $LANG, $SESS, $PREFS, $FNS;
    	
        if ( ! $DSP->allowed_group('can_admin_mbr_templates'))
        {
            return $DSP->no_access_message();
        }
        
        $update = ($function != '' AND $name != '') ? TRUE : FALSE;
    	
    	if ($function == '')
    	{
			$function = $IN->GBL('function');
    	}
    	
    	if ($name == '')
    	{
			$name = $IN->GBL('name');
    	}
    			
		$path = PATH_MBR_THEMES.$FNS->filename_security($name).'/profile_theme'.EXT;
		
		if ( ! file_exists($path))
		{
            return $DSP->no_access_message($LANG->line('unable_to_find_template_file'));
		}
    
		if ( ! class_exists('profile_theme'))
		{
            require $path;
		}
		
		$MS = new profile_theme;
		
		$line = ($LANG->line($function) == FALSE) ? $function : $LANG->line($function);
		
    	$r = $DSP->qdiv('tableHeading', $line);
    	
    	if ($update)
    	{
    		$r .= $DSP->qdiv('success', $LANG->line('template_updated'));
    	}
    	
    	$writable = TRUE;
    	
    	if ( ! is_writable($path))
    	{
    	    $writable = FALSE;
    		$r .= $DSP->div('box');
    		$r .= $DSP->qdiv('itemWrapper', $DSP->qspan('alert', $LANG->line('file_not_writable')));
    		$r .= $DSP->qdiv('itemWrapper', $LANG->line('file_writing_instructions'));
    		$r .= $DSP->qdiv('itemWrapper', $DSP->qspan('default', $path));
    		$r .= $DSP->div_c();
    	}
    
        $r .= $DSP->form_open(array('action' => 'C=admin'.AMP.'M=members'.AMP.'P=save_template'))
             .$DSP->input_hidden('name', $name)
             .$DSP->input_hidden('function', $function);
             
		if ($update == FALSE)
		{		
			$template_data = $MS->$function();
		}
      
        $r .= $DSP->div('itemWrapper')  
             .$DSP->input_textarea('template_data', stripslashes($template_data), $SESS->userdata['template_size'], 'textarea', '100%')
             .$DSP->div_c();
             
        if ($writable == TRUE)
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update')));
        
		$r .= $DSP->form_close();
             
		$temp_name = ucfirst(str_replace("_", " ", $name));
        
        $DSP->title  = $LANG->line($function);
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=members_and_groups', $LANG->line('members_and_groups')).
        			   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=profile_templates', $LANG->line('profile_templates'))).
        			   $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=list_templates'.AMP.'name='.$name, $temp_name)).
        			   $DSP->crumb_item($LANG->line($function));
        $DSP->body   = $r;  
    }
    /* END */
    
    
    /** -----------------------------
    /**  Save Template
    /** -----------------------------*/
    
    function save_template()
    {  
    	global $IN, $DSP, $LANG, $SESS, $FNS, $PREFS;
    	
        if ( ! $DSP->allowed_group('can_admin_mbr_templates'))
        {
            return $DSP->no_access_message();
        }
		
		$function		= $IN->GBL('function');
		$name			= $IN->GBL('name');
		$template_data	= $IN->GBL('template_data');
		$path 			= PATH_MBR_THEMES.$FNS->filename_security($name).'/profile_theme'.EXT;
		
		if ( ! file_exists($path))
		{
            return $DSP->no_access_message($LANG->line('unable_to_find_templates'));
		}
    
		if ( ! class_exists('profile_theme'))
		{
            require $path;
		}
		
		$MS = new profile_theme;
		
		$class_methods = get_class_methods('profile_theme');
		
		$methods = array();
		
		foreach ($class_methods as $val)
		{
			if ($val == $function)
			{
				$methods[$val] = stripslashes($template_data);  
			}
			else
			{
				$methods[$val] = stripslashes($MS->$val());  
			}
		}
		
		$str  = "<?php\n\n";
		$str .= '/*'."\n";
		$str .= '====================================================='."\n";
		$str .= ' ExpressionEngine - by EllisLab'."\n";
		$str .= '-----------------------------------------------------'."\n";
		$str .= ' http://expressionengine.com/'."\n";
		$str .= '-----------------------------------------------------'."\n";
		$str .= ' Copyright (c) 2003 - 2010 EllisLab, Inc.'."\n";
		$str .= '====================================================='."\n";
		$str .= ' THIS IS COPYRIGHTED SOFTWARE'."\n";
		$str .= ' PLEASE READ THE LICENSE AGREEMENT'."\n";
		$str .= ' http://expressionengine.com/docs/license.html'."\n";
		$str .= '====================================================='."\n";
		$str .= ' File: ';
		$str .= $name.EXT."\n";
		$str .= '-----------------------------------------------------'."\n";
		$str .= ' Purpose: Member Profile Skin Elements'."\n";
		$str .= '====================================================='."\n";
		$str .= '*/'."\n\n";
		$str .= "if ( ! defined('EXT')){\n\texit('Invalid file request');\n}\n\n";
		$str .= "class profile_theme {\n\n"; 
		 
		foreach ($methods as $key => $val)
		{
			$str .= '//-------------------------------------'."\n";
			$str .= '//  '.$LANG->line($key)."\n";
			$str .= '//-------------------------------------'."\n\n";

			$str .= 'function '.$key.'()'."\n{\nreturn <<< EOF\n";
			$str .= str_replace("\$", "\\$", $val);
			$str .= "\nEOF;\n}\n// END\n\n\n\n\n";
		} 
			 
		$str .= "}\n";
		$str .= '// END CLASS'."\n";
		$str .= '?'.'>';
           
               
		if ( ! $fp = @fopen($path, 'wb'))
		{
            return $DSP->no_access_message($LANG->line('error_opening_template'));
		}
			flock($fp, LOCK_EX);
			fwrite($fp, $str);
			flock($fp, LOCK_UN);
			fclose($fp);
		
		
        // Clear cache files
        
        $FNS->clear_caching('all');
		
		$this->edit_template($name, $function, $template_data);		
	}
	/* END */
	
	    
       
}/* END */
?>