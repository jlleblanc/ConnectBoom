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
 File: core.login.php
-----------------------------------------------------
 Purpose: Admin authentication class.
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Login {


    /** --------------------------------------
    /**  Constructor
    /** --------------------------------------*/

    function Login()
    {
        global $IN, $DSP;    
        
        switch($IN->GBL('M'))
        {
            case 'auth'        	: $this->authenticate();
                break;
            case 'update_un_pw'	: $this->update_un_pw();
            	break;
            case 'logout'      	: $this->logout();
                break;
            case 'forgot'      	: $this->forgotten_password_form();
                break;
            case 'send_forgot' 	: $this->retrieve_forgotten_password();
                break;
            default            	: $this->login_form();
                break;
        }    
    }
    /* END */
    
    

    /** ---------------------------------------
    /**  Log-in form
    /** ---------------------------------------*/

    function login_form($message = '')
    {
        global $LANG, $DSP, $PREFS, $IN;
        
        $DSP->body_props = " onload=\"document.forms[0].username.focus();\"";

        $qstr = '';

        if ( ! isset($_SERVER['QUERY_STRING']))
        {
            if (isset($_SERVER['REQUEST_URI']))
            {
                $qstr = $_SERVER['REQUEST_URI'];
            }
        }
        else
        {
            $qstr = $_SERVER['QUERY_STRING'];
        }
                         
            $r = ($message != '') ? $DSP->qdiv('highlight', BR.$message) : $DSP->qdiv('default', BR.BR); 
       
            $r .= $DSP->div('leftPad');
            
            $r .= $DSP->form_open(
            						array('action' => 'C=login'.AMP.'M=auth'),
            						array('return_path' => SELF)
            					);
            
                            
        if ($IN->GBL('BK', 'GET') AND $qstr != '')
        {
            $qstr = preg_replace("#.*?C=publish(.*?)#", "C=publish\\1", $qstr);
        
            $r .= $DSP->input_hidden('bm_qstr', $qstr);
        }
                        
		$r .=
			$DSP->qdiv('default', BR.$LANG->line('username', 'username')).
			$DSP->qdiv('itemWrapper', $DSP->input_text('username', '', '20', '32', 'input', '150px')).
			$DSP->qdiv('default', BR.$LANG->line('password', 'password')).
			$DSP->qdiv('itemWrapper', $DSP->input_pass('password', '', '20', '32', 'input', '150px'));
		
		if ($PREFS->ini('admin_session_type') == 'c')
		{
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('default', $DSP->input_checkbox('remember_me', '1', '', ' id="remember_me"').$LANG->line('remember_me', 'remember_me')));
		}

		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit')));
			
		$r .= $DSP->form_close();
			
		$r .= $DSP->qdiv('default', BR.$DSP->anchor(BASE.AMP.'C=login'.AMP.'M=forgot', $LANG->line('forgot_password')));
		  
		$r .= $DSP->div_c();    
                
        $DSP->body = $DSP->qdiv('loginBox', $r);
        $DSP->title = $LANG->line('login');                
    }  
    /* END */



    /** --------------------------------------
    /**  Authenticate user
    /** --------------------------------------*/

    function authenticate()
    {
        global $IN, $DSP, $LANG, $SESS, $PREFS, $OUT, $LOC, $FNS, $REGX, $LOG, $DB, $EXT;

        /** ----------------------------------------
        /**  No username/password?  Bounce them...
        /** ----------------------------------------*/
    
        if ( ! $IN->GBL('username', 'POST') || ! $IN->GBL('password', 'POST'))
        {
            return $this->login_form();
        }
        
		/* -------------------------------------------
		/* 'login_authenticate_start' hook.
		/*  - Take control of CP authentication routine
		/*  - Added EE 1.4.2
		*/
			$edata = $EXT->call_extension('login_authenticate_start');
			if ($EXT->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
        
        /** ----------------------------------------
        /**  Is IP and User Agent required for login?
        /** ----------------------------------------*/
    
        if ($PREFS->ini('require_ip_for_login') == 'y')
        {
			if ($SESS->userdata['ip_address'] == '' || $SESS->userdata['user_agent'] == '')
			{
            	return $this->login_form($LANG->line('unauthorized_request'));
           	}
        }
        
        /** ----------------------------------------
        /**  Check password lockout status
        /** ----------------------------------------*/
		
		if ($SESS->check_password_lockout($IN->GBL('username', 'POST')) === TRUE)
		{
			$line = $LANG->line('password_lockout_in_effect');
		
			$line = str_replace("%x", $PREFS->ini('password_lockout_interval'), $line);
		
            return $this->login_form($line);
		}
		        
        /** ----------------------------------------
        /**  Fetch member data
        /** ----------------------------------------*/

        $sql = "SELECT exp_members.password, exp_members.unique_id, exp_members.member_id, exp_members.group_id, exp_member_groups.can_access_cp
                FROM   exp_members, exp_member_groups
                WHERE  username = '".$DB->escape_str($IN->GBL('username', 'POST'))."'
                AND    exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
                AND    exp_members.group_id = exp_member_groups.group_id";
                
        $query = $DB->query($sql);
        
        
        /** ----------------------------------------
        /**  Invalid Username
        /** ----------------------------------------*/

        if ($query->num_rows == 0)
        {
			$SESS->save_password_lockout($IN->GBL('username', 'POST'));
        
            return $this->login_form($LANG->line('no_username'));
        }
        
        /** ----------------------------------------
        /**  Check password
        /** ----------------------------------------*/

        $password = $FNS->hash(stripslashes($IN->GBL('password', 'POST')));
        
        if ($query->row['password'] != $password)
        {
            // To enable backward compatibility with pMachine we'll test to see 
            // if the password was encrypted with MD5.  If so, we will encrypt the
            // password using SHA1 and update the member's info.
            
            $orig_enc_type = $PREFS->ini('encryption_type');
            $PREFS->core_ini['encryption_type'] = ($PREFS->ini('encryption_type') == 'md5') ? 'sha1' : 'md5';
			$password = $FNS->hash(stripslashes($IN->GBL('password', 'POST')));

            if ($query->row['password'] == $password)
            {
            	$PREFS->core_ini['encryption_type'] = $orig_enc_type;
				$password = $FNS->hash(stripslashes($IN->GBL('password', 'POST')));
            
                $sql = "UPDATE exp_members 
                        SET    password = '".$password."' 
                        WHERE  member_id = '".$DB->escape_str($query->row['member_id'])."' ";
                        
                $DB->query($sql);
            }
            else
            {
				/** ----------------------------------------
				/**  Invalid password
				/** ----------------------------------------*/
					
				$SESS->save_password_lockout($IN->GBL('username', 'POST'));
	
                return $this->login_form($LANG->line('no_password'));
            }
        }
        
        
        /** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        
        // Super Admins can't be banned
        
        if ($query->row['group_id'] != 1)
        {
            if ($SESS->ban_check())
            {
                return $OUT->fatal_error($LANG->line('not_authorized'));
            }
        }
        
        /** ----------------------------------------
        /**  Is user allowed to access the CP?
        /** ----------------------------------------*/
        
        if ($query->row['can_access_cp'] != 'y')
        {
            return $this->login_form($LANG->line('not_authorized'));        
        }
        
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
                    return $this->login_form($LANG->line('multi_login_warning'));                            
                }               
            } 
        }  
        
        /** ----------------------------------------
        /**  Is the UN/PW the correct length?
        /** ----------------------------------------*/
        
        // If the admin has specfified a minimum username or password length that
        // is longer than the current users's data we'll have them update their info.
        // This will only be an issue if the admin has changed the un/password requiremements
        // after member accounts already exist.
        
        $uml = $PREFS->ini('un_min_len');
        $pml = $PREFS->ini('pw_min_len');
        
        $ulen = strlen($IN->GBL('username', 'POST'));
        $plen = strlen($IN->GBL('password', 'POST'));
        
        if ($ulen < $uml OR $plen < $pml)
        {
            return $this->un_pw_update_form();
        }

        
        /** ----------------------------------------
        /**  Set cookies
        /** ----------------------------------------*/
        
        // Set cookie expiration to one year if the "remember me" button is clicked

        $expire = ( ! isset($_POST['remember_me'])) ? '0' : 60*60*24*365;

		if ($PREFS->ini('admin_session_type') != 's')
		{
			$FNS->set_cookie($SESS->c_expire , time()+$expire, $expire);
			$FNS->set_cookie($SESS->c_uniqueid , $query->row['unique_id'], $expire);       
			$FNS->set_cookie($SESS->c_password , $password,  $expire);   
			$FNS->set_cookie($SESS->c_anon , 1,  $expire);
		}
		
		if ( isset($_POST['site_id']) && is_numeric($_POST['site_id']))
		{
			$FNS->set_cookie('cp_last_site_id', $IN->GBL('site_id', 'POST'), 0);
		}
        
        /** ----------------------------------------
        /**  Create a new session
        /** ----------------------------------------*/

        $session_id = $SESS->create_new_session($query->row['member_id'], TRUE);
        
        // -------------------------------------------
		// 'cp_member_login' hook.
		//  - Additional processing when a member is logging into CP
		//
			$edata = $EXT->call_extension('cp_member_login', $query->row);
			if ($EXT->end_script === TRUE) return;
		//
		// -------------------------------------------
               
        /** ----------------------------------------
        /**  Log the login
        /** ----------------------------------------*/
        
        // We'll manually add the username to the Session array so
        // the LOG class can use it.
        $SESS->userdata['username']  = $IN->GBL('username', 'POST');
        
        $LOG->log_action($LANG->line('member_logged_in'));
        
        /** ----------------------------------------
        /**  Delete old password lockouts
        /** ----------------------------------------*/
        
		$SESS->delete_password_lockout();

        /** ----------------------------------------
        /**  Redirect the user to the CP home page
        /** ----------------------------------------*/

        $return_path = $REGX->decode_qstr($IN->GBL('return_path', 'POST').'?S='.$session_id);
        
        if ($IN->GBL('bm_qstr', 'POST'))
        {
            $return_path .= AMP.$IN->GBL('bm_qstr', 'POST');
        }

        $FNS->redirect($return_path);
        exit;    
    }
    /* END */
    
    
    

    /** ---------------------------------------
    /**  Username/password update form
    /** ---------------------------------------*/
    
    // If the username or password is too short we'll give users
    // a chance to update them.

	function un_pw_update_form($message = '')
	{
        global $LANG, $DSP, $PREFS, $IN;
        
        $LANG->fetch_language_file('member');
        
        $uml = $PREFS->ini('un_min_len');
        $pml = $PREFS->ini('pw_min_len');
        
        $ulen = strlen($IN->GBL('username', 'POST'));
        $plen = strlen($IN->GBL('password', 'POST'));
       
        $r  = $DSP->form_open(array('action' => 'C=login'.AMP.'M=update_un_pw'));
        
		$r .= $DSP->div('loginInnerBox');		
		$r .= $DSP->qdiv('alert', $LANG->line('access_notice'));
		
		if ($ulen < $uml)
		{
			$r .= $DSP->qdiv('itemWrapperTop', $DSP->qdiv('highlight_alt', str_replace('%x', $uml, $LANG->line('un_len'))));
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', str_replace('%x', $ulen, $LANG->line('yun_len'))));
		}
	
		if ($plen < $pml)
		{
			$r .= $DSP->qdiv('itemWrapperTop', $DSP->qdiv('highlight_alt', str_replace('%x', $pml, $LANG->line('pw_len'))));
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', str_replace('%x', $plen, $LANG->line('ypw_len'))));
		}
		
		$r .= $DSP->qdiv('padBotBorder', NBS);
		
		if ($message != '')
		{
			$r .= $DSP->qdiv('alert', BR.$message);
		}
		
		if ($ulen < $uml)
		{
			$new_username = ($IN->GBL('new_username') !== FALSE) ? $IN->GBL('new_username') : '';
		
			$r .=	$DSP->qdiv('default', BR.$LANG->line('choose_new_un', 'new_username')).
					$DSP->qdiv('itemWrapper', $DSP->input_text('new_username', $new_username, '20', '32', 'input', '150px'));
		}		

		if ($plen < $pml)
		{				
			$r .=	$DSP->qdiv('default', BR.$LANG->line('choose_new_pw', 'new_password')).
					$DSP->qdiv('itemWrapper', $DSP->input_pass('new_password', '', '20', '32', 'input', '150px'));
					
			$r .=	$DSP->qdiv('itmeWrapper', $LANG->line('confirm_new_pw', 'new_password_confirm')).
					$DSP->qdiv('itemWrapper', $DSP->input_pass('new_password_confirm', '', '20', '32', 'input', '150px'));
		}                
                
                
		$r .= $DSP->qdiv('padBotBorder', NBS);
                
                
		$r .=
			$DSP->qdiv('default', BR.$LANG->line('existing_username', 'username')).
			$DSP->qdiv('itemWrapper', $DSP->input_text('username', $IN->GBL('username', 'POST'), '20', '32', 'input', '150px')).
			$DSP->qdiv('default', BR.$LANG->line('existing_password', 'password')).
			$DSP->qdiv('itemWrapper', $DSP->input_pass('password', '', '20', '32', 'input', '150px'));
		

		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));
			
		  
		$r .= $DSP->div_c();    
		$r .= $DSP->form_close();

                
        $DSP->body = $DSP->qdiv('loginBox', $r);
        $DSP->title = $LANG->line('login');                
	}
	/* END */
	
	
    
    /** -------------------------------------
    /**  Update Username or Password
    /** -------------------------------------*/
	
	function update_un_pw()
	{
		global $DSP, $IN, $DB, $FNS, $SESS, $PREFS, $OUT, $LANG;
		
        $LANG->fetch_language_file('member');
		
		$missing = FALSE;
		
		if ( ! isset($_POST['new_username']) AND  ! isset($_POST['new_password']))
		{
			$missing = TRUE;
		}
		
		if ((isset($_POST['new_username']) AND $_POST['new_username'] == '') || (isset($_POST['new_password']) AND $_POST['new_password'] == ''))
		{
			$missing = TRUE;
		}
		
		if ($IN->GBL('username', 'POST') == '' OR $IN->GBL('password', 'POST') == '')
		{
			$missing = TRUE;
		}

		if ($missing == TRUE)
		{
			return $this->un_pw_update_form($LANG->line('all_fields_required'));
		}
		
        /** ----------------------------------------
        /**  Check password lockout status
        /** ----------------------------------------*/
		
		if ($SESS->check_password_lockout($IN->GBL('username', 'POST')) === TRUE)
		{		
			$line = str_replace("%x", $PREFS->ini('password_lockout_interval'), $LANG->line('password_lockout_in_effect'));		
			return $this->un_pw_update_form($line);
		}
		        		
        /** ----------------------------------------
        /**  Fetch member data
        /** ----------------------------------------*/

        $sql = "SELECT member_id, group_id
                FROM   exp_members
                WHERE  username = '".$DB->escape_str($IN->GBL('username', 'POST'))."'
                AND    password = '".$FNS->hash(stripslashes($IN->GBL('password', 'POST')))."'";                
                
        $query = $DB->query($sql);
        $member_id = $query->row['member_id'];
        
        /** ----------------------------------------
        /**  Invalid Username or Password
        /** ----------------------------------------*/

        if ($query->num_rows == 0)
        {
			$SESS->save_password_lockout($IN->GBL('username', 'POST'));
			return $this->un_pw_update_form($LANG->line('invalid_existing_un_pw'));
        }
        
        /** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        
        // Super Admins can't be banned
        
        if ($query->row['group_id'] != 1)
        {
            if ($SESS->ban_check())
            {
                return $OUT->fatal_error($LANG->line('not_authorized'));
            }
        }
        		
        /** -------------------------------------
        /**  Instantiate validation class
        /** -------------------------------------*/

		if ( ! class_exists('Validate'))
		{
			require PATH_CORE.'core.validate'.EXT;
		}
		
		$new_un  = (isset($_POST['new_username'])) ? $_POST['new_username'] : '';
		$new_pw  = (isset($_POST['new_password'])) ? $_POST['new_password'] : '';
		$new_pwc = (isset($_POST['new_password_confirm'])) ? $_POST['new_password_confirm'] : '';

		$VAL = new Validate(
								array( 
										'val_type'			=> 'new',
										'fetch_lang' 		=> TRUE, 
										'require_cpw' 		=> FALSE,
									 	'enable_log'		=> FALSE,
										'username'			=> $new_un,
										'password'			=> $new_pw,
									 	'password_confirm'	=> $new_pwc,
									 	'cur_password'		=> $_POST['password'],
									 )
							);
		
		$un_exists = (isset($_POST['new_username']) AND $_POST['new_username'] != '') ? TRUE : FALSE;
		$pw_exists = (isset($_POST['new_password']) AND $_POST['new_password'] != '') ? TRUE : FALSE;
				
		if ($un_exists)
			$VAL->validate_username();
		if ($pw_exists)
			$VAL->validate_password();
		
        /** -------------------------------------
        /**  Display error is there are any
        /** -------------------------------------*/

         if (count($VAL->errors) > 0)
         {
         	$er = '';
         	
         	foreach ($VAL->errors as $val)
         	{
         		$er .= $val.BR;
         	}
         
			return $this->un_pw_update_form($er);
         }
         
         
		if ($un_exists)
		{
			$DB->query("UPDATE exp_members SET username = '".$DB->escape_str($_POST['new_username'])."' WHERE member_id = '{$member_id}'");
		}	
						
		if ($pw_exists)
		{
			$DB->query("UPDATE exp_members SET password = '".$FNS->hash(stripslashes($_POST['new_password']))."' WHERE member_id = '{$member_id}'");
		}	
                  
		$DSP->body  = $DSP->div('loginBox').BR.BR.BR;
		$DSP->body .= $DSP->qdiv('success', $LANG->line('unpw_updated'));
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor('index.php', $LANG->line('return_to_login')));
		$DSP->body .= $DSP->div_c();
	}
    /* END */
    
    
    /** -------------------------------------
    /**  Log-out
    /** -------------------------------------*/

    function logout()
    {
        global $IN, $SESS, $EXT, $FNS, $LOG, $LOG, $LANG, $DB;

        $DB->query("DELETE FROM exp_online_users WHERE ip_address = '{$IN->IP}' AND member_id = '".$DB->escape_str($SESS->userdata('member_id'))."'");

        $DB->query("DELETE FROM exp_sessions WHERE session_id = '".$DB->escape_str($SESS->userdata['session_id'])."'");
                
		$FNS->set_cookie($SESS->c_uniqueid);       
		$FNS->set_cookie($SESS->c_password);   
		$FNS->set_cookie($SESS->c_session);   
		$FNS->set_cookie($SESS->c_expire);   
		$FNS->set_cookie($SESS->c_anon);   
        $FNS->set_cookie('read_topics');  
        $FNS->set_cookie('tracker');  

        $LOG->log_action($LANG->line('member_logged_out'));

		/* -------------------------------------------
		/* 'cp_member_logout' hook.
		/*  - Perform additional actions after logout
		/*  - Added EE 1.6.1
		*/
			$edata = $EXT->call_extension('cp_member_logout');
			if ($EXT->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		       
        $FNS->redirect(SELF);
        exit;
    }
    /* END */
    
    

    /** ---------------------------------------
    /**  Forgotten password form
    /** ---------------------------------------*/

    function forgotten_password_form($message = '')
    {
        global $LANG, $DB, $DSP, $IN;        
        
        $email = ( ! $IN->GBL('email', 'POST')) ? '' : $IN->GBL('email'); 
                
        $msg = $DSP->div('default').BR.BR;
        
        if ($message != '')
        {
            $msg .= $message.BR.BR; 
        }
        else
        {
        	$msg .= BR.BR;
        }
               
        $r =
            $DSP->form_open(array('action' => 'C=login'.AMP.'M=send_forgot')).
            $msg.
            $DSP->qdiv('default', $LANG->line('submit_email_address')).
            $DSP->qdiv('', $DSP->input_text('email', $email, '20', '80', 'input', '250px')).
            $DSP->qdiv('default', BR.$DSP->input_submit($LANG->line('submit'))).
            $DSP->qdiv('default', BR.BR.$DSP->anchor(BASE, $LANG->line('return_to_login')).BR.BR).
            $DSP->form_close().
            $DSP->div_c();
                
        $DSP->set_return_data($LANG->line('forgotten_password'), $DSP->qdiv('loginBox', $r));
    }  
    /* END */


    /** ---------------------------------------
    /**  Retrieve forgotten password
    /** ---------------------------------------*/

    function retrieve_forgotten_password()
    {
        global $LANG, $PREFS, $FNS, $DSP, $IN, $DB;
        
        if ( ! $address = $IN->GBL('email', 'POST'))
        {
            return $this->forgotten_password_form();
        }
        
		$address = strip_tags($address);
        
        // Fetch user data
                        
        $query = $DB->query("SELECT member_id, username FROM exp_members WHERE email ='".$DB->escape_str($address)."'");
        
        if ($query->num_rows == 0)
        {
            return $this->forgotten_password_form($LANG->line('no_email_found'));
        }
        
        $member_id = $query->row['member_id'];
        $username  = $query->row['username'];
        
        // Kill old data from the reset_password field
        
        $time = time() - (60*60*24);
        
        $DB->query("DELETE FROM exp_reset_password WHERE date < $time OR member_id = '".$DB->escape_str($member_id)."'");
        
        // Create a new DB record with the temporary reset code
        
        $rand = $FNS->random('alpha', 8);
                
        $data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());
         
        $DB->query($DB->insert_string('exp_reset_password', $data));
        
        // Buid the email message
        
        $message  = $username.",".
                    $DSP->nl(2).
                    $LANG->line('reset_link').
                    $DSP->nl(2).
                    $PREFS->ini('cp_url')."?C=reset&id=".$rand.
                    $DSP->nl(2).
                    $LANG->line('password_will_be_reset').
                    $DSP->nl(2).
                    $LANG->line('ignore_password_message');
         
         
        // Instantiate the email class
             
        require PATH_CORE.'core.email'.EXT;
        
        $email = new EEmail;
        $email->wordwrap = true;
        $email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
        $email->to($address); 
        $email->subject($LANG->line('your_new_login_info'));	
        $email->message($message);	
        
        if ( ! $email->Send())
        {
            $res = $LANG->line('error_sending_email');
        } 
        else 
        {   
            $res = $LANG->line('forgotten_email_sent');
        }
        
        
        $return = 	$DSP->div('loginBox'). 
					$DSP->div('default').
					$DSP->br(4).
					$res.
					$DSP->br(5).
					$DSP->anchor(BASE, $LANG->line('return_to_login')).
					$DSP->br(5).
					$DSP->div_c().
					$DSP->div_c();

        $DSP->set_return_data(
                                $LANG->line('forgotten_password'), 
                                
                                $return
							);
    }  
    /* END */


    /** ---------------------------------------
    /**  Reset password
    /** ---------------------------------------*/

    function reset_password()
    {
        global $LANG, $PREFS, $FNS, $DSP, $IN, $DB;
        
        if ( ! $id = $IN->GBL('id', 'GET'))
        {
            return $this->login_form();
        }
        
        $time = time() - (60*60*24);
                   
        // Get the member ID from the reset_password field   
                
        $query = $DB->query("SELECT member_id FROM exp_reset_password WHERE resetcode ='$id' and date > $time");
        
        if ($query->num_rows == 0)
        {
            return $this->login_form();
        }
        
        $member_id = $query->row['member_id'];
                
        // Fetch the user data
                
        $query = $DB->query("SELECT username, email FROM exp_members WHERE member_id ='$member_id'");
        
        if ($query->num_rows == 0)
        {
            return $this->login_form();
        }
        
        $address   = $query->row['email'];
        $username  = $query->row['username'];
                
        $rand = $FNS->random('alpha', 8);
        
        // Update member's password
               
        $DB->query("UPDATE exp_members SET password = '".$FNS->hash($rand)."' WHERE member_id = '".$DB->escape_str($member_id)."'");
        
        // Kill old data from the reset_password field
        
        $DB->query("DELETE FROM exp_reset_password WHERE date < $time OR member_id = '".$DB->escape_str($member_id)."'");
                
                
        // Buid the email message
        
        $message  = $username.",".
                    $DSP->nl(2).
                    $LANG->line('new_login_info').
                    $DSP->nl(2).
                    $LANG->line('username').': '.$username.
                    $DSP->nl(1).
                    $LANG->line('password').': '.$rand;
         
         
        // Instantiate the email class
             
        require PATH_CORE.'core.email'.EXT;
        
        $email = new EEmail;
        $email->wordwrap = true;
        $email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));
        $email->to($address); 
        $email->subject($LANG->line('your_new_login_info'));	
        $email->message($message);	
        
        if ( ! $email->Send())
        {
            $res = $LANG->line('error_sending_email');
        } 
        else 
        {   
            $res = $LANG->line('password_has_been_reset');
        }
        
        $return = 	$DSP->div('loginBox'). 
					$DSP->div('default').
					$DSP->br(4).
					$res.
					$DSP->br(5).
					$DSP->anchor(BASE, $LANG->line('return_to_login')).
					$DSP->br(5).
					$DSP->div_c().
					$DSP->div_c();
        
        $DSP->set_return_data(
                                $LANG->line('forgotten_password'), 
                                
                                $return
                              );
    }  
    /* END */
      
}
// END CLASS
?>