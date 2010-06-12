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
 File: cp.myaccount.php
-----------------------------------------------------
 Purpose: User account management functions
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class MyAccount {

    var $username = '';


    /** -----------------------------------
    /**  Constructor
    /** -----------------------------------*/

    function MyAccount()
    {
        global $LANG, $IN, $DB, $DSP;
                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        $LANG->fetch_language_file('member');  

		/** -----------------------------------
		/**  Fetch username/screen name
		/** -----------------------------------*/
                
        $query = $DB->query("SELECT username, screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message();
        }
        
        $this->username = ($query->row['screen_name'] == '') ? $query->row['username']: $query->row['screen_name'];
        
		/** -----------------------------------
		/**  Direct the request
		/** -----------------------------------*/

        switch($IN->GBL('M'))
        {
            case 'edit_profile'          	: $this->member_profile_form();
                break;
            case 'update_profile'        	: $this->update_member_profile();
                break;
            case 'unpw_form'             	: $this->username_password_form();
                break;
            case 'update_unpw'           	: $this->update_username_password();
                break;
            case 'email_settings'        	: $this->email_settings_form();
                break;
            case 'update_email'          	: $this->update_email_settings();
                break;
            case 'edit_preferences'        	: $this->edit_preferences();
                break;
            case 'update_preferences'       : $this->update_preferences();
                break;
            case 'localization'          	: $this->localization_form();
                break;
            case 'localization_update'   	: $this->localization_update();
                break;
            case 'subscriptions'         	: $this->subscriptions_form();
                break;
            case 'unsubscribe'         		: $this->unsubscribe();
                break;
            case 'pingservers'           	: $this->my_ping_servers();
                break;               
            case 'htmlbuttons'           	: $this->htmlbuttons();
                break; 
            case 'update_htmlbuttons'       : $this->update_htmlbuttons();
                break; 
            case 'homepage'					: $this->homepage_builder();
                break;
            case 'set_homepage_prefs'		: $this->set_homepage_prefs();
            	break;
            case 'set_homepage_order'		: $this->set_homepage_order();
            	break;
            case 'theme'				 	: $this->theme_builder();
                break;
            case 'save_theme'				: $this->save_theme();
                break;
            case 'edit_signature'			: $this->edit_signature();
                break;
            case 'update_signature'        	: $this->update_signature();
                break;
            case 'edit_avatar'				: $this->edit_avatar();
                break;
            case 'browse_avatars'			: $this->browse_avatars();
                break;
            case 'select_avatar'			: $this->select_avatar();
                break;
            case 'update_avatar'        	: $this->upload_avatar();
                break;
            case 'edit_photo'     	  	 	: $this->edit_photo();
                break;
            case 'update_photo'     	   	: $this->upload_photo();
                break;
            case 'notepad'               	: $this->notepad();
                break;
            case 'notepad_update'        	: $this->notepad_update();
                break;
            case 'administration'        	: $this->administrative_options();
                break;
            case 'administration_update'	: $this->administration_update();
                break;
            case 'quicklinks'           	: $this->quick_links_form();
                break;
            case 'quicklinks_update'     	: $this->quick_links_update(FALSE);
                break;
            case 'tab_manager'           	: $this->tab_manager();
                break;
            case 'tab_manager_update'    	: $this->quick_links_update(TRUE);
                break;
            case 'bookmarklet'           	: $this->bookmarklet();
                break;
            case 'bookmarklet_fields'    	: $this->bookmarklet_fields();
                break;
            case 'create_bookmarklet'    	: $this->create_bookmarklet();
                break;
            case 'messages'					: $this->messages();            
            	break;
            case 'bulletin_board'           : $this->bulletin_board();
                break;
			case 'ignore_list'				: $this->ignore_list();
				break;
			case 'update_ignore_list'		: $this->update_ignore_list();
				break;
			case 'member_search'			: $this->member_search();
				break;
			case 'do_member_search'			: $this->do_member_search();
				break;
            default                      	: $this->account_wrapper();
                break;
        }
    }
    /* END */
    
   


    /** ------------------------------------------------
    /**  Validate user and get the member ID number
    /** -------------------------------------------------*/

    function auth_id()
    {
        global $DB, $IN, $SESS, $DSP, $LANG;   
        
        // Who's profile are we editing?

        $id = ( ! $IN->GBL('id', 'GP')) ? $SESS->userdata('member_id') : $IN->GBL('id', 'GP');

        // Is the user authorized to edit the profile?
        
        if ($id != $SESS->userdata('member_id'))
        {
            if ( ! $DSP->allowed_group('can_admin_members'))
            {
                return FALSE;
            }

			// Only Super Admins can view Super Admin profiles
			$query = $DB->query("SELECT group_id FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");
			
			if ($query->num_rows == 0)
			{
				return FALSE;
			}
            
            if ($query->row['group_id'] == 1 AND $SESS->userdata['group_id'] != 1)
            {
                return FALSE;
            }        
        }
        
        if ( ! is_numeric($id))
        {
        	return FALSE;
        }
        
        return $id;
    }
    /* END */

    /** ----------------------------------------
    /**  Error Wrapper
    /** ----------------------------------------*/

	function _error_message($msg)
	{
		global $DSP, $LANG;
		
		return $DSP->error_message($LANG->line($msg));
	}
	/* END */
	

    /** ------------------------------------------------
    /**  Left side menu
    /** ------------------------------------------------*/

    function nav($path = '', $text = '')
    {
        global $DSP, $LANG;

        if ($path == '')
            return false;
            
        if ($text == '')
            return false;
        
        return $DSP->qdiv('navPad', $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'M='.$path, $LANG->line($text)));                
    }
    /* END */



    /** ------------------------------------------------
    /**  "My Account" main page wrapper
    /** -------------------------------------------------*/

    function account_wrapper($title = '', $crumb = '', $content = '')
    {
        global $DSP, $DB, $IN, $SESS, $FNS, $LANG, $PREFS;
                          
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        // Default page title if not supplied  
                        
        if ($title == '')
        {
            $title = $LANG->line('my_account');
        }
        
        // Default bread crumb if not supplied
        
        if ($crumb == '')
        {
            if ($id != $SESS->userdata('member_id'))
            {
                $crumb = $LANG->line('user_account');
            }
            else
            {
                $crumb = $LANG->line('my_account');
            }        
        }
        
        // Default content if not supplied

        if ($content == '')
        {
            $content .= $this->profile_homepage();
        }
        
        // Set breadcrumb and title
        
        $DSP->title = $title;
        $DSP->crumb = $crumb;
        		
        ob_start();
        ?>     
        <script type="text/javascript"> 
        <!--
            
        function showhide_menu(which)
        {
        	head = which + '_h';
        	body = which + '_b';
        	        
			if (document.getElementById(head).style.display == "block")
			{
				document.getElementById(head).style.display = "none";
				document.getElementById(body).style.display = "block";
        	}
        	else
        	{
				document.getElementById(head).style.display = "block";
				document.getElementById(body).style.display = "none";
        	}
        }
            
        //-->
        </script>        
    
        <?php
        
        $buffer = ob_get_contents();
        ob_end_clean();         
        $DSP->body = $buffer;
        

		// Build the output
		
		$expand		= '<img src="'.PATH_CP_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />&nbsp;&nbsp;';
		$collapse	= '<img src="'.PATH_CP_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />&nbsp;&nbsp;';		

        $DSP->body	.=	$DSP->table('', '0', '', '100%').
             			$DSP->tr().
             			$DSP->td('', '240px', '', '', 'top');
             			             			             			
		$DSP->body	.=	$DSP->qdiv('tableHeading', $LANG->line('current_member').NBS.NBS.$this->username);
						
		
		$prof_state = (in_array($IN->GBL('M'), array('edit_profile', 'edit_signature', 'edit_avatar', 'browse_avatars', 'edit_photo', 'email_settings', 'unpw_form', 'localization', 'edit_preferences'))) ? TRUE : FALSE;
		
		
		$DSP->body  .= '<div id="menu_profile_h" style="display: '.(($prof_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
		$js = ' onclick="showhide_menu(\'menu_profile\');return false;" onmouseover="navTabOn(\'prof\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'prof\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='prof' ".$js.">";
		$DSP->body .= $expand.$LANG->line('personal_settings');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		 
		$DSP->body .= '<div id="menu_profile_b" style="display: '.(($prof_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
		
		$js = ' onclick="showhide_menu(\'menu_profile\');return false;" onmouseover="navTabOn(\'prof2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'prof2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='prof2' ".$js.">";
		$DSP->body .= $collapse.$LANG->line('personal_settings');
		$DSP->body .= $DSP->div_c();
        $DSP->body	.=	$DSP->div('profileMenuInner').
      					$this->nav('edit_profile'.AMP.'id='.$id, 'edit_profile').
      					$this->nav('edit_signature'.AMP.'id='.$id, 'edit_signature').
      					$this->nav('edit_avatar'.AMP.'id='.$id, 'edit_avatar').
      					$this->nav('edit_photo'.AMP.'id='.$id, 'edit_photo').
        				$this->nav('email_settings'.AMP.'id='.$id, 'email_settings').
       					$this->nav('unpw_form'.AMP.'id='.$id, 'username_and_password');
       					
        if ($PREFS->ini('allow_member_localization') == 'y' OR $SESS->userdata('group_id') == 1)
        {
        	$DSP->body	.= $this->nav('localization'.AMP.'id='.$id, 'localization');
        }
        
        $DSP->body	.= $this->nav('edit_preferences'.AMP.'id='.$id, 'edit_preferences').
       					$DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();

		
		$sub_state = (in_array($IN->GBL('M'), array('subscriptions')) OR in_array($IN->GBL('M'), array('ignore_list'))) ? TRUE : FALSE;
		
		$DSP->body  .= '<div id="menu_sub_h" style="display: '.(($sub_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
		$js = ' onclick="showhide_menu(\'menu_sub\');return false;" onmouseover="navTabOn(\'sub\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'sub\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='sub' ".$js.">";
		$DSP->body .= $expand.$LANG->line('utilities');
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		 
		$DSP->body .= '<div id="menu_sub_b" style="display: '.(($sub_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
		
		$js = ' onclick="showhide_menu(\'menu_sub\');return false;" onmouseover="navTabOn(\'sub2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'sub2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='sub2' ".$js.">";
		$DSP->body .= $collapse.$LANG->line('utilities');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div('profileMenuInner');
		$DSP->body .= $this->nav('subscriptions'.AMP.'id='.$id, 'edit_subscriptions');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div('profileMenuInner');
		$DSP->body .= $this->nav('ignore_list'.AMP.'id='.$id, 'ignore_list');
		$DSP->body .= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();

						
       	/** ---------------------------------
        /**  Private Messaging
        /** ---------------------------------*/
        
        if ($id == $SESS->userdata['member_id'])
        {
        	if ( ! class_exists('Messages'))
			{
				require PATH_CORE.'core.messages'.EXT;
			}
		
			$MESS = new Messages;
			$MESS->create_menu();
			$DSP->body .= $MESS->menu;
		}
				
		$cp_state = (in_array($IN->GBL('M'), array('homepage', 'set_homepage_order', 'theme', 'tab_manager'))) ? TRUE : FALSE;
		
		$DSP->body  .= '<div id="menu_cp_h" style="display: '.(($cp_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
		$js = ' onclick="showhide_menu(\'menu_cp\');return false;" onmouseover="navTabOn(\'mcp\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'mcp\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='mcp' ".$js.">";
		$DSP->body .= $expand.$LANG->line('customize_cp');
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		 
		$DSP->body .= '<div id="menu_cp_b" style="display: '.(($cp_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
		
		$js = ' onclick="showhide_menu(\'menu_cp\');return false;" onmouseover="navTabOn(\'mcp2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'mcp2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='mcp2' ".$js.">";
		$DSP->body .= $collapse.$LANG->line('customize_cp');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div('profileMenuInner');
		$DSP->body .= $this->nav('homepage'.AMP.'id='.$id, 'cp_homepage');
		$DSP->body .= $this->nav('theme'.AMP.'id='.$id, 'cp_theme');
		$DSP->body .= $this->nav('tab_manager'.AMP.'id='.$id, 'tab_manager');
		$DSP->body .= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();
		$DSP->body	.= $DSP->div_c();

        if ($DSP->allowed_group('can_access_publish') AND count($FNS->fetch_assigned_weblogs()) > 0)
        {			
			$blog_state = (in_array($IN->GBL('M'), array('pingservers', 'htmlbuttons', 'bookmarklet', 'bookmarklet_fields', 'create_bookmarklet'))) ? TRUE : FALSE;
				
			$DSP->body  .= '<div id="menu_blog_h" style="display: '.(($blog_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
			$js = ' onclick="showhide_menu(\'menu_blog\');return false;" onmouseover="navTabOn(\'blog\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'blog\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$DSP->body .= $DSP->div();
			$DSP->body .= "<div class='tableHeadingAlt' id='blog' ".$js.">";
			$DSP->body .= $expand.$LANG->line('weblog_preferences');
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			 
			$DSP->body .= '<div id="menu_blog_b" style="display: '.(($blog_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
			
			$js = ' onclick="showhide_menu(\'menu_blog\');return false;" onmouseover="navTabOn(\'blog2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'blog2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$DSP->body .= $DSP->div();
			$DSP->body .= "<div class='tableHeadingAlt' id='blog2' ".$js.">";
			$DSP->body .= $collapse.$LANG->line('weblog_preferences');
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div('profileMenuInner');
			$DSP->body .= $this->nav('pingservers'.AMP.'id='.$id, 'your_ping_servers');
			$DSP->body .= $this->nav('htmlbuttons'.AMP.'id='.$id, 'your_html_buttons');
			$DSP->body .= $this->nav('bookmarklet'.AMP.'id='.$id, 'bookmarklet');
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
        }
        		
		$ex_state = (in_array($IN->GBL('M'), array('quicklinks', 'notepad'))) ? TRUE : FALSE;
								
		$DSP->body  .= '<div id="menu_ex_h" style="display: '.(($ex_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
		$js = ' onclick="showhide_menu(\'menu_ex\');return false;" onmouseover="navTabOn(\'exx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'exx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='exx' ".$js.">";
		$DSP->body .= $expand.$LANG->line('extras');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		 
		$DSP->body .= '<div id="menu_ex_b" style="display: '.(($ex_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
		
		$js = ' onclick="showhide_menu(\'menu_ex\');return false;" onmouseover="navTabOn(\'exx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'exx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
		$DSP->body .= $DSP->div();
		$DSP->body .= "<div class='tableHeadingAlt' id='exx2' ".$js.">";
		$DSP->body .= $collapse.$LANG->line('extras');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div('profileMenuInner');
		$DSP->body .= $this->nav('quicklinks'.AMP.'id='.$id, 'quick_links');
		$DSP->body .= $this->nav('notepad'.AMP.'id='.$id, 'notepad');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
						
        
        if ($DSP->allowed_group('can_admin_members'))
        {			
			$ad_state = (in_array($IN->GBL('M'), array('administration'))) ? TRUE : FALSE;
			$DSP->body  .= '<div id="menu_ad_h" style="display: '.(($ad_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
			$js = ' onclick="showhide_menu(\'menu_ad\');return false;" onmouseover="navTabOn(\'adx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'adx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$DSP->body .= $DSP->div();
			$DSP->body .= "<div class='tableHeadingAlt' id='adx' ".$js.">";
			$DSP->body .= $expand.$LANG->line('administrative_options');
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			 
			$DSP->body .= '<div id="menu_ad_b" style="display: '.(($ad_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
			
			$js = ' onclick="showhide_menu(\'menu_ad\');return false;" onmouseover="navTabOn(\'adx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'adx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$DSP->body .= $DSP->div();
			$DSP->body .= "<div class='tableHeadingAlt' id='adx2' ".$js.">";
			$DSP->body .= $collapse.$LANG->line('administrative_options');
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div('profileMenuInner');
			$DSP->body .= $this->nav('administration'.AMP.'id='.$id, 'member_preferences');
								
			if ($id != $SESS->userdata('member_id'))
			{
				$DSP->body .= $DSP->qdiv('navPad', $DSP->anchor(BASE.AMP.'C=communicate'.AMP.'M=email_mbr'.AMP.'mid='.$id, $LANG->line('member_email')));
			}
			
			if ($id != $SESS->userdata('member_id') &&	$PREFS->ini('req_mbr_activation') == 'email' && $DSP->allowed_group('can_admin_members'))
			{
				$query = $DB->query("SELECT group_id FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");
			
				if ($query->row['group_id'] == '4')
				{
					$LANG->fetch_language_file('members');
					
					$DSP->body .= $DSP->qdiv('navPad', $DSP->anchor(BASE.AMP.'C=admin'.
																		 AMP.'M=members'.
																		 AMP.'P=resend_act_email'.
																		 AMP.'mid='.$id, 
																	$LANG->line('resend_activation_email')));
				}
			}
			
			if ($SESS->userdata['group_id'] == 1 && $id != $SESS->userdata('member_id'))
			{			
				$DSP->body .= $DSP->qdiv('navPad', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=login_as_member'.AMP.'mid='.$id, $LANG->line('login_as_member')));
			}
	
        	if ($DSP->allowed_group('can_delete_members'))
			{			
				$DSP->body .= $DSP->qdiv('navPad', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=members'.AMP.'P=mbr_del_conf'.AMP.'mid='.$id, $LANG->line('delete_member')));
			}

			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
			$DSP->body .= $DSP->div_c();
        }    

		$DSP->body .=	$DSP->div_c();
		$DSP->body .=	$DSP->div_c();

        $DSP->body	.=	$DSP->td_c().
              			$DSP->td('', '8px', '', '', 'top').NBS.$DSP->td_c().
              			$DSP->td('', '', '', '', 'top').
              			$content.
						$DSP->td_c().
						$DSP->tr_c().
						$DSP->table_c();
    }
    /* END */
    


    /** -----------------------------------
    /**  Profile Homepage
    /** -----------------------------------*/
    
    function profile_homepage()
    {
        global $DSP, $LANG, $DB, $PREFS, $LOC;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
    
        $query = $DB->query("SELECT email, ip_address, join_date, last_visit, total_entries, total_comments, last_entry_date, last_comment_date, last_forum_post_date, total_forum_topics, total_forum_posts FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows == 0)
            return false;
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        $i = 0;

        $r  = $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2').$LANG->line('member_stats').NBS.NBS.NBS.$this->username.
              $DSP->tr_c();
              
        $fields = array(
        					'email'				=> $DSP->mailto($email), 
        					'join_date'			=> $LOC->set_human_time($join_date), 
        					'last_visit'		=> ($last_visit == 0 OR $last_visit == '') ? '--' : $LOC->set_human_time($last_visit), 
        					'total_entries'		=> $total_entries, 
        					'total_comments'	=> $total_comments, 
        					'last_entry_date'	=> ($last_entry_date == 0 OR $last_entry_date == '') ? '--' : $LOC->set_human_time($last_entry_date), 
        					'last_comment_date'	=> ($last_comment_date == 0 OR $last_comment_date == '') ? '--' : $LOC->set_human_time($last_comment_date),
        					'user_ip_address'	=> $ip_address
        				);
        				
		if ($PREFS->ini('forum_is_installed') == "y")
		{
			$fields['last_forum_post_date'] = ($last_forum_post_date == 0) ? '--' : $LOC->set_human_time($last_forum_post_date);
			$fields['total_forum_topics'] 	= $total_forum_topics;
			$fields['total_forum_replies'] 	= $total_forum_posts;
			$fields['total_forum_posts']	= $total_forum_posts + $total_forum_topics;
		}

		foreach ($fields as $key => $val)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
	
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line($key)), '50%');
			$r .= $DSP->table_qcell($style, $val, '50%');
			$r .= $DSP->tr_c();		
		}              

        $r .= $DSP->table_c(); 

        return $r;
    }
    /* END */
    


    /** -----------------------------------
    /**  Edit Profile Form
    /** -----------------------------------*/
    
    function member_profile_form()
    {  
        global $IN, $DSP, $DB, $SESS, $REGX, $LOC, $PREFS, $LANG;

        $screen_name    = '';
        $email          = '';
        $url            = '';
               
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $title = $LANG->line('edit_profile');  
        
		/** -----------------------------------
		/**  Fetch profile data
		/** -----------------------------------*/

        $query = $DB->query("SELECT url, location, occupation, interests, aol_im, yahoo_im, msn_im, icq, bio, bday_y, bday_m, bday_d FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");    
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }

		/** -----------------------------------
		/**  Declare form
		/** -----------------------------------*/
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=update_profile')).
              $DSP->input_hidden('id', $id);
		
		/** -----------------------------------
		/**  Birthday Year Menu
		/** -----------------------------------*/
		
		$bd  = $DSP->input_select_header('bday_y');
		$bd .= $DSP->input_select_option('', $LANG->line('year'), ($bday_y == '') ? 1 : '');
		
		for ($i = date('Y', $LOC->now); $i > 1904; $i--)
		{                    					
		  $bd .= $DSP->input_select_option($i, $i, ($bday_y == $i) ? 1 : '');
		}
		
		$bd .= $DSP->input_select_footer();
		
		/** -----------------------------------
		/**  Birthday Month Menu
		/** -----------------------------------*/
		
		$months = array(
							'01' => 'January',
							'02' => 'February',
							'03' => 'March',
							'04' => 'April',
							'05' => 'May',
							'06' => 'June',
							'07' => 'July',
							'08' => 'August',
							'09' => 'September',
							'10' => 'October',
							'11' => 'November',
							'12' => 'December'
						);
		
		$bd .= $DSP->input_select_header('bday_m');		
		$bd .= $DSP->input_select_option('', $LANG->line('month'), ($bday_m == '') ? 1 : '');
		
		for ($i = 1; $i < 13; $i++)
		{
		  if (strlen($i) == 1)
			 $i = '0'.$i;
							
		  $bd .= $DSP->input_select_option($i, $LANG->line($months[$i]), ($bday_m == $i) ? 1 : '');
		}
		
		$bd .= $DSP->input_select_footer();
		
		/** -----------------------------------
		/**  Birthday Day Menu
		/** -----------------------------------*/
		
		$bd .= $DSP->input_select_header('bday_d');		
		$bd .= $DSP->input_select_option('', $LANG->line('day'), ($bday_d == '') ? 1 : '');
		
		for ($i = 31; $i >= 1; $i--)
		{                    
		  $bd .= $DSP->input_select_option($i, $i, ($bday_d == $i) ? 1 : '');
		}
		
		$bd .= $DSP->input_select_footer();

		/** -----------------------------------
		/**  Build Page Output
		/** -----------------------------------*/

        $i = 0;
        
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('profile_updated')));
        }
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
              
        $r .= $LANG->line('profile_form');
        
        $r .= $DSP->td_c().
              $DSP->tr_c();

        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

        $r .= $DSP->tr();
        $r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('birthday')), '25%');
        $r .= $DSP->table_qcell($style, $bd, '75%');
        $r .= $DSP->tr_c();

	  if ($url == '')
		  $url = 'http://';
                             
        $fields = array(
        					'url'			=> array('i', '75'), 
        					'location'		=> array('i', '50'), 
        					'occupation'	=> array('i', '80'), 
        					'interests'		=> array('i', '75'), 
        					'aol_im'		=> array('i', '50'), 
        					'icq'			=> array('i', '50'), 
        					'yahoo_im'		=> array('i', '50'), 
        					'msn_im'		=> array('i', '50'),
        					'bio'			=> array('t', '12')
        				);
        
		foreach ($fields as $key => $val)
		{		
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$align = ($val['0'] == 'i') ? '' : 'top';
	
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line($key)), '', $align);
			
			if ($val['0'] == 'i')
			{
				$r .= $DSP->table_qcell($style, $DSP->input_text($key, $$key, '40', $val['1'], 'input', '100%'));
			}
			elseif ($val['0'] == 't')
			{
				$r .= $DSP->table_qcell($style, $DSP->input_textarea($key, $$key, $val['1'], 'textarea', '100%'));
			}
			$r .= $DSP->tr_c();
		}
			
		/** -----------------------------------
		/**  Extended profile fields
		/** -----------------------------------*/

		$sql = "SELECT * FROM exp_member_fields ";
		
		if ($SESS->userdata['group_id'] != 1)
		{
			$sql .= " WHERE m_field_public = 'y' ";
		}
		
		$sql .= " ORDER BY m_field_order";
		
		                
        $query = $DB->query($sql);
        
        if ($query->num_rows > 0)
        {
        
			$result = $DB->query("SELECT * FROM  exp_member_data WHERE  member_id = '".$DB->escape_str($id)."'");        
			
			if ($result->num_rows > 0)
			{    
				foreach ($result->row as $key => $val)
				{
					$$key = $val;
				}
			}
                
			foreach ($query->result as $row)
			{
				$field_data = ( ! isset( $result->row['m_field_id_'.$row['m_field_id']] )) ? '' : 
										 $result->row['m_field_id_'.$row['m_field_id']];
										 
							
				$width = '100%';
																			  
				$required  = ($row['m_field_required'] == 'n') ? '' : $DSP->required().NBS;     
			
				// Textarea fieled types
			
				if ($row['m_field_type'] == 'textarea')
				{               
					$rows = ( ! isset($row['m_field_ta_rows'])) ? '10' : $row['m_field_ta_rows'];
	
					$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
					$r .= $DSP->tr();
					$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $required.$row['m_field_label']).$DSP->qdiv('default', $required.$row['m_field_description']), '', 'top');
					$r .= $DSP->table_qcell($style, $DSP->input_textarea('m_field_id_'.$row['m_field_id'], $field_data, $rows, 'textarea', $width));
					$r .= $DSP->tr_c();
				}
				else
				{        
					// Text input fields
					
					if ($row['m_field_type'] == 'text')
					{   
						$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
						$r .= $DSP->tr();
						$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $required.$row['m_field_label']).$DSP->qdiv('default', $required.$row['m_field_description']));
						$r .= $DSP->table_qcell($style, $DSP->input_text('m_field_id_'.$row['m_field_id'], $field_data, '20', '100', 'input', $width));
						$r .= $DSP->tr_c();
					}            
	
					// Drop-down lists
					
					elseif ($row['m_field_type'] == 'select')
					{                          
						$d = $DSP->input_select_header('m_field_id_'.$row['m_field_id']);
										
						foreach (explode("\n", trim($row['m_field_list_items'])) as $v)
						{   
							$v = trim($v);
						
							$selected = ($field_data == $v) ? 1 : '';
												
							$d .= $DSP->input_select_option($v, $v, $selected);
						}
						
						$d .= $DSP->input_select_footer();
						
						$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
						$r .= $DSP->tr();
						$r .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $required.$row['m_field_label']).$DSP->qdiv('default', $required.$row['m_field_description']));
						$r .= $DSP->table_qcell($style, $d);
						$r .= $DSP->tr_c();
					}
				}
			}        
		}
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		// END CUSTOM FIELDS	

		$r .= $DSP->table_c(); 
              
        $r.=  $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */



    /** ----------------------------------
    /**  Update member profile
    /** ----------------------------------*/
    
    function update_member_profile()
    {  
        global $IN, $DSP, $DB, $SESS, $PREFS, $FNS, $REGX, $LOG, $LANG, $LOC;
       
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        unset($_POST['id']);
      
		if ($_POST['url'] == 'http://')
			$_POST['url'] = '';       
        
        $fields = array(	'bday_y',
        					'bday_m',
        					'bday_d',
        					'url', 
        					'location', 
        					'occupation', 
        					'interests', 
        					'aol_im', 
        					'icq', 
        					'yahoo_im', 
        					'msn_im',
        					'bio'
        				);

        $data = array();
        
        foreach ($fields as $val)
        {
        	if (isset($_POST[$val]))
        	{
        		$data[$val] = $_POST[$val];	
        	}
        	
        	unset($_POST[$val]);
        }
        
        if (is_numeric($data['bday_d']) AND is_numeric($data['bday_m']))
        {
        	$year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
			$mdays = $LOC->fetch_days_in_month($data['bday_m'], $year);
			
			if ($data['bday_d'] > $mdays)
			{
				$data['bday_d'] = $mdays;
			}
        }        
                            
		if (count($data) > 0)
        $DB->query($DB->update_string('exp_members', $data, "member_id = '".$DB->escape_str($id)."'"));   
                       
        if (count($_POST) > 0)             
        $DB->query($DB->update_string('exp_member_data', $_POST, "member_id = '".$DB->escape_str($id)."'"));   
        
        if ($data['location'] != "" || $data['url'] != "")
        {                           
            $DB->query($DB->update_string('exp_comments', array('location' => $data['location'], 'url' => $data['url']), "author_id = '".$DB->escape_str($id)."'"));   
        }
        
        // We need to update the gallery comments 
        // But!  Only if the table exists
                
        if ($DB->table_exists('exp_gallery_comments'))
        {
            $DB->query($DB->update_string('exp_gallery_comments', array('location' => $data['location'], 'url' => $data['url']), "author_id = '".$DB->escape_str($id)."'"));   
        }
        
                        
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_profile'.AMP.'id='.$id.AMP.'U=1');
        exit;    
    }
    /* END */




    /** -----------------------------------
    /**  Email preferences form
    /** -----------------------------------*/

    function email_settings_form()
    {  
        global $IN, $DSP, $DB, $SESS, $REGX, $PREFS, $LANG;

        $message   = '';
        
                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $title = $LANG->line('email_settings');
        
        $query = $DB->query("SELECT email, accept_admin_email, accept_user_email, notify_by_default, notify_of_pm, smart_notifications FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");    
        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
                
        // Build the output
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=update_email')).
              $DSP->input_hidden('id', $id).
              $DSP->input_hidden('current_email', $query->row['email']);           
           
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('settings_updated')));
        }
         
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
        
        $r .= $title;

        $r .= $DSP->td_c().
              $DSP->tr_c();

        $r .= $DSP->tr();
        $r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $DSP->required().NBS.$LANG->line('email')), '28%');
        $r .= $DSP->table_qcell('tableCellTwo', $DSP->input_text('email', $email, '40', '80', 'input', '100%'), '72%');
        $r .= $DSP->tr_c();
        
        $checkboxes = array('accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'smart_notifications');
        
        foreach ($checkboxes as $val)
        {
			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellOne', '100%', '2');
			$r .= $DSP->input_checkbox($val, 'y', ($$val == 'y') ? 1 : '').NBS.$LANG->line($val);
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
        }

		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '', '2');
        $r .= $DSP->qdiv('itemTitle', $LANG->line('existing_password')).
              $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('existing_password_email'))).
              $DSP->input_pass('password', '', '35', '32', 'input', '310px').BR;
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
        
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
                
		$r .= $DSP->table_c();
        $r .= $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */




    /** -----------------------------------
    /**  Update Email Preferences
    /** -----------------------------------*/

    function update_email_settings()
    {
		global $IN, $DSP, $DB, $SESS, $PREFS, $FNS, $REGX, $LOG, $LANG;

		if (FALSE === ($id = $this->auth_id()))
		{
		    return $DSP->no_access_message();
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

		/** -------------------------------------
		/**  Validate submitted data
		/** -------------------------------------*/

		$query = $DB->query("SELECT email FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");  
		$current_email = $query->row['email'];

		if ( ! class_exists('Validate'))
		{
			require PATH_CORE.'core.validate'.EXT;
		}
		
		$VAL = new Validate(
								array( 
										'member_id'			=> $id,
										'val_type'			=> 'update', // new or update
										'fetch_lang' 		=> FALSE, 
										'require_cpw' 		=> ($current_email != $_POST['email']) ? TRUE :FALSE,
										'enable_log'		=> TRUE,
										'email'				=> $_POST['email'],
										'cur_email'			=> $current_email,
									 	'cur_password'		=> $_POST['password']
									 )
							);

		$VAL->validate_email();
		
		if (count($VAL->errors) > 0)
		{
			return $VAL->show_errors();
		}		

        /** -------------------------------------
        /**  Assign the query data
        /** -------------------------------------*/
                
        $data = array(
                        'email'                 =>  $_POST['email'],
                        'accept_admin_email'    => (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
                        'accept_user_email'     => (isset($_POST['accept_user_email']))  ? 'y' : 'n',
                        'notify_by_default'     => (isset($_POST['notify_by_default']))  ? 'y' : 'n',
                        'notify_of_pm'     		=> (isset($_POST['notify_of_pm']))  ? 'y' : 'n',
                        'smart_notifications'    => (isset($_POST['smart_notifications']))  ? 'y' : 'n'                        
                      );

        $DB->query($DB->update_string('exp_members', $data, "member_id = '$id'"));   
        
        /** -------------------------------------
        /**  Update comments and log email change
        /** -------------------------------------*/
                
        if ($_POST['current_email'] != $_POST['email'])
        {                           
            $DB->query($DB->update_string('exp_comments', array('email' => $_POST['email']), "author_id = '".$DB->escape_str($id)."'"));   
        
			// We need to update the gallery comments 
			// But!  Only if the table exists
						
			if ($DB->table_exists('exp_gallery_comments'))
			{
				$DB->query($DB->update_string('exp_gallery_comments', array('email' => $_POST['email']), "author_id = '".$DB->escape_str($id)."'"));   
			}
        
            $LOG->log_action($VAL->log_msg);
        }
        
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=email_settings'.AMP.'id='.$id.AMP.'U=1'.AMP);
        exit;    
    }
    /* END */



    /** -----------------------------------
    /**  Edit preferences form
    /** -----------------------------------*/

    function edit_preferences()
    {  
        global $IN, $DSP, $DB, $SESS, $REGX, $PREFS, $LANG, $EXT;

        $message   = '';
        
                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $title = $LANG->line('edit_preferences');
        
        $query = $DB->query("SELECT display_avatars, display_signatures, accept_messages FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");  

        
        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
                
        // Build the output
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=update_preferences')).
              $DSP->input_hidden('id', $id);          
           
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('settings_updated')));
        }
         
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
        
        $r .= $title;

        $r .= $DSP->td_c().
              $DSP->tr_c();

        
        $checkboxes = array('display_avatars', 'display_signatures', 'accept_messages');
        
        foreach ($checkboxes as $val)
        {
			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellOne', '100%', '2');
			$r .= $DSP->input_checkbox($val, 'y', ($$val == 'y') ? 1 : '').NBS.$LANG->line($val);
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
        }
        
        // -------------------------------------------
        // 'myaccount_edit_preferences' hook.
		//  - Allows adding of preferences to CP side preferences form
		//
			if ($EXT->active_hook('myaccount_edit_preferences') === TRUE)
			{
				$r .= $EXT->call_extension('myaccount_edit_preferences');
			}	
		//
		// -------------------------------------------

		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
                
		$r .= $DSP->table_c();
        $r .= $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */


    /** -----------------------------------
    /**  Update  Preferences
    /** -----------------------------------*/

    function update_preferences()
    {
        global $IN, $DSP, $DB, $SESS, $PREFS, $FNS, $REGX, $LOG, $LANG, $EXT;
      
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        /** -------------------------------------
        /**  Assign the query data
        /** -------------------------------------*/
                
        $data = array(
                        'accept_messages'		=> (isset($_POST['accept_messages'])) ? 'y' : 'n',
                        'display_avatars'		=> (isset($_POST['display_avatars'])) ? 'y' : 'n',
                        'display_signatures'	=> (isset($_POST['display_signatures']))  ? 'y' : 'n'
                      );

        $DB->query($DB->update_string('exp_members', $data, "member_id = '".$DB->escape_str($id)."'")); 
        
        
        // -------------------------------------------
        // 'myaccount_update_preferences' hook.
		//  - Allows updating of added preferences via CP side preferences form
		//
			$edata = $EXT->call_extension('myaccount_update_preferences', $data);
        	if ($EXT->end_script === TRUE) return;
		//
		// -------------------------------------------
                
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_preferences'.AMP.'id='.$id.AMP.'U=1'.AMP);
        exit;    
    }
    /* END */


    /** -----------------------------------
    /**  Username/Password form
    /** -----------------------------------*/

    function username_password_form()
    {  
        global $IN, $DSP, $DB, $SESS, $REGX, $PREFS, $LANG;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $username  = '';
        $message   = '';
        
		/** -----------------------------------
		/**  Show "successful update" message
		/** -----------------------------------*/
        
        if ($IN->GBL('U'))
        {
            $message = $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('settings_updated')));
            
            if ($IN->GBL('pw_change') == 1)
            {
                $message .= $DSP->qdiv('alert', BR.$LANG->line('password_change_warning').BR.BR);
            }
        }
        
        $title = $LANG->line('username_and_password');
        
		/** -----------------------------------
		/**  Fetch username
		/** -----------------------------------*/
        
        $query = $DB->query("SELECT username, screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");    
        
        $username 		= $query->row['username'];
        $screen_name	= $query->row['screen_name'];
        
		/** -----------------------------------
        /**  Build the output
        /** -----------------------------------*/
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=update_unpw')).
              $DSP->input_hidden('id', $id).
              $DSP->input_hidden('current_username', $query->row['username']).
              $DSP->input_hidden('current_screen_name', $screen_name);              
              
        if ($IN->GBL('U'))
        {
        	$r .= $message;
        }

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
              
		$r .= $title;
              
        $r .= $DSP->td_c().
              $DSP->tr_c();
        
        if ($SESS->userdata['group_id'] != '1' AND $PREFS->ini('allow_username_change') == 'n')
        {
			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellOne', '100%', '2');
			$r .= $LANG->line('username_change_not_allowed');
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
        }
        else
        {
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('username')), '28%');
			$r .= $DSP->table_qcell('tableCellTwo', $DSP->input_text('username', $username, '40', '50', 'input', '100%'), '72%');
			$r .= $DSP->tr_c();
        }
        
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('screen_name')), '28%');
		$r .= $DSP->table_qcell('tableCellTwo', $DSP->input_text('screen_name', $screen_name, '40', '50', 'input', '100%'), '72%');
		$r .= $DSP->tr_c();
	
	
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
	
        $r .= $DSP->div('itemWrapper')
             .$DSP->qdiv('itemTitle', $LANG->line('password_change'))
             .$DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('password_change_exp')))
             .$DSP->qdiv('highlight', $LANG->line('password_change_requires_login'))
             .$DSP->div_c();
             
        $r .= $DSP->qdiv('itemTitle', $LANG->line('new_password'))
             .$DSP->input_pass('password', '', '35', '32', 'input', '300px');

        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('new_password_confirm')).
              $DSP->input_pass('password_confirm', '', '35', '32', 'input', '300px').
              $DSP->div_c();
              
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
              
        $r .= $DSP->div('paddedWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('existing_password')).
              $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('existing_password_exp'))).
              $DSP->input_pass('current_password', '', '35', '32', 'input', '310px');
				  
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();		
		
		$r .= $DSP->table_c();

        $r .= $DSP->div_c();      
        
        $r.=  $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */


    /** -----------------------------------
    /**  Update username and password
    /** -----------------------------------*/

    function update_username_password()
    {  
        global $IN, $DSP, $DB, $SESS, $PREFS, $FNS, $REGX, $LOG, $LANG;
      
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

            
        if ($PREFS->ini('allow_username_change') != 'y' AND $SESS->userdata('group_id') != 1)
        {
            if ($_POST['current_password'] == '')
            {
                $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=unpw_form'.AMP.'id='.$id);
                exit;    
            }
            
            $_POST['username'] = $_POST['current_username'];
        }        

		// If the screen name field is empty, we'll assign is
		// from the username field.              
               
		if ($_POST['screen_name'] == '')
			$_POST['screen_name'] = $_POST['username'];              

        /** -------------------------------------
        /**  Validate submitted data
        /** -------------------------------------*/

		if ( ! class_exists('Validate'))
		{
			require PATH_CORE.'core.validate'.EXT;
		}
				
		$VAL = new Validate(
								array( 
										'member_id'			=> $id,
										'val_type'			=> 'update', // new or update
										'fetch_lang' 		=> FALSE, 
										'require_cpw' 		=> TRUE,
									 	'enable_log'		=> TRUE,
										'username'			=> $_POST['username'],
										'cur_username'		=> $_POST['current_username'],
										'screen_name'		=> stripslashes($_POST['screen_name']),
										'cur_screen_name'	=> stripslashes($_POST['current_screen_name']),
										'password'			=> $_POST['password'],
									 	'password_confirm'	=> $_POST['password_confirm'],
									 	'cur_password'		=> $_POST['current_password']
									 )
							);
														
		$VAL->validate_screen_name();

        if ($PREFS->ini('allow_username_change') == 'y' OR $SESS->userdata['group_id'] == 1)
        {
			$VAL->validate_username();
        }
                       
        if ($_POST['password'] != '')
        {
			$VAL->validate_password();
        }

        /** -------------------------------------
        /**  Display error is there are any
        /** -------------------------------------*/
        
		if (count($VAL->errors) > 0)
		{
			return $VAL->show_errors();
		}		
		
		
        /** -------------------------------------
        /**  Update "last post" and "moderator" forum info if needed
        /** -------------------------------------*/
         
        if ($_POST['current_screen_name'] != $_POST['screen_name'] AND $PREFS->ini('forum_is_installed') == "y")
        {
        	$DB->query("UPDATE exp_forums SET forum_last_post_author = '".$DB->escape_str($_POST['screen_name'])."' WHERE forum_last_post_author_id = '".$id."'");
        	$DB->query("UPDATE exp_forum_moderators SET mod_member_name = '".$DB->escape_str($_POST['screen_name'])."' WHERE mod_member_id = '".$id."'");
        }

        /** -------------------------------------
        /**  Assign the query data
        /** -------------------------------------*/

		$data['screen_name'] = $_POST['screen_name'];

        if ($PREFS->ini('allow_username_change') == 'y' OR $SESS->userdata['group_id'] == 1)
        {
            $data['username'] = $_POST['username'];
        }
        
        // Was a password submitted?

        $pw_change = 0;

        if ($_POST['password'] != '')
        {
            $data['password'] = $FNS->hash(stripslashes($_POST['password']));
            
            if ($id == $SESS->userdata('member_id'))
            {
                $pw_change = 1;
            }
        }

        $DB->query($DB->update_string('exp_members', $data, "member_id = '".$DB->escape_str($id)."'"));   

		if ($_POST['current_screen_name'] != $_POST['screen_name'])
		{  
            $query = $DB->query("SELECT screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($id)."'");

			$screen_name = ($query->row['screen_name'] != '') ? $query->row['screen_name'] : '';

			// Update comments with current member data
		
			$data = array('name' => ($screen_name != '') ? $screen_name : $_POST['username']);
						  
			$DB->query($DB->update_string('exp_comments', $data, "author_id = '".$DB->escape_str($id)."'"));   
        
			// We need to update the gallery comments 
			// But!  Only if the table exists
						
			if ($DB->table_exists('exp_gallery_comments'))
			{
				$DB->query($DB->update_string('exp_gallery_comments', $data, "author_id = '".$DB->escape_str($id)."'"));   
			}			
        }
        
        // Write log file
        
		$LOG->log_action($VAL->log_msg);
		
		// Redirect...

        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=unpw_form'.AMP.'id='.$id.AMP.'U=1'.AMP.'pw_change='.$pw_change);
        exit;    
    }
    /* END */




    /** -----------------------------------
    /**  Ping servers
    /** -----------------------------------*/

    function my_ping_servers()
    {        
        global $IN, $LANG, $FNS, $DSP;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
        
        $message = ($IN->GBL('U', 'GET')) ? $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('pingservers_updated'))) : '';
    
        require PATH_CP.'cp.publish_ad'.EXT;
        
        $PA = new PublishAdmin;

        $title = $LANG->line('ping_servers');

        return $this->account_wrapper($title, $title, $PA->ping_servers($message, $id));
    }    
    /* END */
    
    

    /** -----------------------------------
    /**  HTML buttons
    /** -----------------------------------*/

    function htmlbuttons()
    {        
        global $IN, $LANG, $FNS, $DSP;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
        
        $message = ($IN->GBL('U', 'GET')) ? $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('html_buttons_updated'))) : '';
    
        require PATH_CP.'cp.publish_ad'.EXT;
        
        $PA = new PublishAdmin;

        $title = $LANG->line('html_buttons');

        return $this->account_wrapper($title, $title, $PA->html_buttons($message, $id));
    }    
    /* END */
    
    /** -----------------------------------
    /**  Update HTML buttons
    /** -----------------------------------*/

    function update_htmlbuttons()
    {        
        global $IN, $LANG, $FNS, $DSP;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
    
		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        require PATH_CP.'cp.publish_ad'.EXT;
        
        $PA = new PublishAdmin;

        $title = $LANG->line('html_buttons');

        return $PA->save_html_buttons();
    }    
    /* END */
    
    /** -----------------------------------
    /**  Home Page builder
    /** -----------------------------------*/

    function homepage_builder()
    {
        global $IN, $LANG, $DB, $SESS, $DSP, $EXT;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		$DSP->right_crumb($LANG->line('set_display_order'), BASE.AMP.'C=myaccount'.AMP.'M=set_homepage_order'.AMP.'id='.$id);
                
        $DB->fetch_fields = TRUE;
        
        $prefs = array();        
                
        $sql = "SELECT	recent_entries,
						recent_comments,
						site_statistics,
						notepad,
						bulletin_board,
						pmachine_news_feed";
						
		if ($DSP->allowed_group('can_access_admin') === TRUE)
		{    	
			  $sql .= ",
						member_search_form,
						recent_members";
		}						
						
		$sql .= " FROM exp_member_homepage 
        		  WHERE member_id = '".$DB->escape_str($id)."'";
        		  
        $DB->fetch_fields = TRUE;

        $query = $DB->query($sql);
                
        if ($query->num_rows == 0)
        {        
            foreach ($query->fields as $f)
            {
				$prefs[$f] = 'n';
            }
        }
        else
        {  
        	unset($query->row['member_id']);
              
            foreach ($query->row as $key => $val)
            {
				$prefs[$key] = $val;
            }
        }


        $title = $LANG->line('customize_homepage');
                        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=set_homepage_prefs'));
        $r .= $DSP->input_hidden('id', $id);
        
		if ($IN->GBL('U')) 
		{
			$r .= $DSP->div('');
			$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('preferences_updated')));
			$r .= $DSP->div_c();
		}        
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
			  $DSP->table_qcell('tableHeading', $LANG->line('homepage_preferences')).
			  $DSP->table_qcell('tableHeading', $LANG->line('left_column')).
			  $DSP->table_qcell('tableHeading', $LANG->line('right_column')).
			  $DSP->table_qcell('tableHeading', $LANG->line('do_not_show')).
              $DSP->tr_c();

		$i = 0;
		
		foreach ($prefs as $key => $val)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line($key)));
			$r .= $DSP->table_qcell($style, $DSP->input_radio($key, 'l', ($val == 'l') ? 1 : ''));
			$r .= $DSP->table_qcell($style, $DSP->input_radio($key, 'r', ($val == 'r') ? 1 : ''));
			$r .= $DSP->table_qcell($style, $DSP->input_radio($key, 'n', ($val != 'l' && $val != 'r') ? 1 : ''));
			$r .= $DSP->tr_c();
        }
        
		// -------------------------------------------
        // 'myaccount_homepage_builder' hook.
		//  - Allows adding of new homepage options
		//
			if ($EXT->active_hook('myaccount_homepage_builder') === TRUE)
			{
				$r .= $EXT->call_extension('myaccount_homepage_builder', $i);
			}	
		//
		// -------------------------------------------
        
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '4');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();        
        
        $r .= $DSP->table_c(); 
        
        $r .= $DSP->form_close();

        return $this->account_wrapper($title, $title, $r);
    }
    /* END */


    /** ------------------------------------------------
    /**  Set Homepage Display Order
    /** -------------------------------------------------*/

    function set_homepage_order()
    {
        global $IN, $LANG, $DB, $SESS, $DSP, $EXT;

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        $opts = array(	'recent_entries',
						'recent_comments',
						'site_statistics',
						'notepad',
						'bulletin_board',
						'pmachine_news_feed'
        			);
        			
		if ($DSP->allowed_group('can_access_admin') === TRUE)
		{  
			$opts[] = 'recent_members';
			$opts[] = 'member_search_form';
		}						
        
        $prefs = array();
                
        $sql = "SELECT	*
        		FROM exp_member_homepage 
        		WHERE member_id = '".$DB->escape_str($id)."'";
                
        $query = $DB->query($sql);
					  
		foreach ($query->row as $key => $val)
		{
			if (in_array($key, $opts))
			{
				if ($val != 'n')
				{
					$prefs[$key] = $val;
				}
			}
		}


        $title = $LANG->line('customize_homepage');
        
        $r  = '';
                

        $r .= $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=set_homepage_prefs'));
        $r .= $DSP->input_hidden('id', $id);
        $r .= $DSP->input_hidden('loc', 'set_homepage_order');
        
        $r .= $DSP->table('', '0', '0', '100%').
              $DSP->tr().
              $DSP->td(); 
              
        if (isset($_GET['U']))
        {
        	if ($_GET['U'] == 2)
        	{
        		$r .= $DSP->div('');
        		$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('preferences_updated')));
        		$r .= $DSP->div_c();
        	}
        	else
        	{
        		$r .= $DSP->div('');
        		$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('preferences_updated')));
        		//$r .= $DSP->heading(NBS.$LANG->line('please_update_order'), 5);
        		$r .= $DSP->div_c();
        	}
        }

        $r .= $DSP->td_c()   
             .$DSP->tr_c()      
             .$DSP->table_c();  

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
			  $DSP->table_qcell('tableHeading', $LANG->line('set_display_order')).
			  $DSP->table_qcell('tableHeading', $LANG->line('left_column')).
			  $DSP->table_qcell('tableHeading', $LANG->line('right_column')).
              $DSP->tr_c();

		$i = 0;
		
		foreach ($prefs as $key => $val)
		{
			if (in_array($key, $opts))
			{
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
				$r .= $DSP->tr();
				$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line($key)));
				
              //$DSP->qdiv('', $DSP->input_text('recipient', '', '20', '150', 'input', '300px')).
				
				if ($val == 'l')
				{
					$r .= $DSP->table_qcell($style, $DSP->input_text($key.'_order', $query->row[$key.'_order'], '10', '3', 'input', '50px'));
					$r .= $DSP->table_qcell($style, NBS);
				}
				elseif ($val == 'r')
				{
					$r .= $DSP->table_qcell($style, NBS);
					$r .= $DSP->table_qcell($style, $DSP->input_text($key.'_order', $query->row[$key.'_order'], '10', '3', 'input', '50px'));
				}
				
				$r .= $DSP->tr_c();
			}
        }
        
        // -------------------------------------------
        // 'myaccount_set_homepage_order' hook.
		//  - Allows adding of new homepage options to ordering form
		//
			if ($EXT->active_hook('myaccount_set_homepage_order') === TRUE)
			{
				$r .= $EXT->call_extension('myaccount_set_homepage_order', $i);
			}	
		//
		// -------------------------------------------

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
        
		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '4');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
        
        $r .= $DSP->table_c(); 
        $r .= $DSP->form_close();

        return $this->account_wrapper($title, $title, $r);

	}
	/* END */
    


    /** ------------------------------------------------
    /**  Update Homepage Preferences
    /** -------------------------------------------------*/

    function set_homepage_prefs()
    {
        global $DB, $SESS, $FNS, $EXT, $DSP;   

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		$loc = ( ! isset($_POST['loc'])) ? '' : $_POST['loc'];
        		
		unset($_POST['loc']);
		unset($_POST['id']);
		
		if (! $DSP->allowed_group('can_access_admin'))
		{  
			unset($_POST['recent_members']);
			unset($_POST['member_search_form']);
		}						
		
		$ref = 1;
		
		$reset = array(	
							'recent_entries_order' 				=> 0,
							'recent_comments_order' 			=> 0,
							'recent_members_order' 				=> 0,
							'site_statistics_order' 			=> 0,
							'member_search_form_order' 			=> 0,
							'notepad_order' 					=> 0,
							'bulletin_board_order'				=> 0
						);
				
		if ($loc == 'set_homepage_order')
		{
			$ref = 2;
		
        	$DB->query($DB->update_string('exp_member_homepage', $reset, "member_id = '".$DB->escape_str($id)."'"));
		}
		
        $DB->query($DB->update_string('exp_member_homepage', $_POST, "member_id = '".$DB->escape_str($id)."'"));
        
        
        // -------------------------------------------
        // 'myaccount_set_homepage_prefs' hook.
		//  - Allows setting of new homepage options
		//
			if ($EXT->active_hook('myaccount_set_homepage_prefs') === TRUE)
			{
				$r .= $EXT->call_extension('myaccount_set_homepage_prefs');
			}	
		//
		// -------------------------------------------
        
        // Decide where to redirect based on the value of the submission
        
        foreach ($reset as $key => $val)
        {
        	$key = str_replace('_order', '', $key);
        
        	if (isset($_POST[$key]) AND ($_POST[$key] == 'l' || $_POST[$key] == 'r'))
        	{
				$FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=set_homepage_order'.AMP.'id='.$id.AMP.'U='.$ref);
				exit;    
        	}
        }
                
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=homepage'.AMP.'id='.$id.AMP.'U='.$ref);
        exit;    
	}
	/* END */



    /** ---------------------------------
    /**  Theme builder
    /** ---------------------------------*/
    
    // OK, well, the title is misleading.  Eventually, this will be a full-on
    // theme builder.  Right now it just lets users choose from among pre-defined CSS files

    function theme_builder()
    {
        global $IN, $DB, $DSP, $FNS, $SESS, $PREFS, $LANG;
                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		if ( ! class_exists('Admin'))
		{
			require PATH_CP.'cp.admin'.EXT;
		}

        $title = $LANG->line('cp_theme');
		
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=save_theme'));
        $r .= $DSP->input_hidden('id', $id);
        
        $AD = new Admin;
        
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('preferences_updated')));
        }
                
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
                            
		$r .= $title;
                      
        $r .= $DSP->td_c().
              $DSP->tr_c();
              		
        $theme = ($SESS->userdata['cp_theme'] == '') ? $PREFS->ini('cp_theme') : $SESS->userdata['cp_theme'];

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $LANG->line('choose_theme')), '50%');
		$r .= $DSP->table_qcell('tableCellOne', $AD->fetch_themes($theme), '50%');
		$r .= $DSP->tr_c();		

		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();

        $r .= $DSP->table_c(); 
        $r .= $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */



    /** ---------------------------------
    /**  Save Theme
    /** ---------------------------------*/

    function save_theme()
    {
        global $DB, $FNS;   

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        		
        $DB->query("UPDATE exp_members SET cp_theme = '".$DB->escape_str($_POST['cp_theme'])."' WHERE member_id = '$id'");
                
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=theme'.AMP.'id='.$id);
        exit;    
	}
	/* END */





    /** ------------------------------------------------
    /**  Subscriptions
    /** ------------------------------------------------*/

    function subscriptions_form()
    {
        global $IN, $DSP, $LANG, $DB, $SESS, $FNS, $REGX, $PREFS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        // Set some base values
        
        $blog_subscriptions		= FALSE;
        $galery_subscriptions	= FALSE;
        $forum_subscriptions	= FALSE;
        $result_ids				= array();
        $result_data			= array();
        $perpage				= 75;
        $total_count			= 0;
        $page_links				= '';
        $rownum  				= ( ! $IN->GBL('rownum')) ? 0 : $IN->GBL('rownum'); 
		$qm 					= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $pageurl 				= BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$id;
        
        $query = $DB->query("SELECT email FROM exp_members WHERE member_id = '".$id."'");
        
        if ($query->num_rows != 1)
        {
            return $DSP->no_access_message();
        }

		$email = $query->row['email'];

		/** ----------------------------------------
		/**  Start the page output
		/** ----------------------------------------*/

        $title = $LANG->line('subscriptions');
                
		$r = ($IN->GBL('U')) ? $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('subscriptions_removed'))) : '';
        
        $r .= $DSP->qdiv('tableHeading', $title);
        
		
		/** ----------------------------------------
		/**  Fetch Weblog Comments
		/** ----------------------------------------*/
		
		$query = $DB->query("SELECT DISTINCT(entry_id)  FROM exp_comments WHERE email = '".$DB->escape_str($email)."' AND notify = 'y' ORDER BY comment_date DESC");

		if ($query->num_rows > 0)
		{
			$blog_subscriptions = TRUE;
			
			foreach ($query->result as $row)
			{
				$result_ids[$total_count.'b'] = $row['entry_id'];
				$total_count++;
			}
		}
	        
		/** ----------------------------------------
		/**  Fetch Gallery Comments
		/** ----------------------------------------*/

		// Since the gallery module might not be installed we'll test for it first.
						
        if ($DB->table_exists('exp_gallery_comments'))
		{
			$query = $DB->query("SELECT DISTINCT(entry_id) FROM exp_gallery_comments WHERE email = '".$DB->escape_str($email)."' AND notify = 'y' ORDER BY comment_date DESC");
		
			if ($query->num_rows > 0)
			{
				$galery_subscriptions = TRUE;
				
				foreach ($query->result as $row)
				{
					$result_ids[$total_count.'g'] = $row['entry_id'];
					$total_count++;
				}
			}
		}
		
		
		/** ----------------------------------------
		/**  Fetch Forum Topic Subscriptions
		/** ----------------------------------------*/

		// Since the forum module might not be installed we'll test for it first.
						
		if ($DB->table_exists('exp_forum_subscriptions'))
		{
			$query = $DB->query("SELECT topic_id FROM exp_forum_subscriptions WHERE member_id = '".$DB->escape_str($id)."' ORDER BY subscription_date DESC");
		
			if ($query->num_rows > 0)
			{
				$forum_subscriptions = TRUE;
				
				foreach ($query->result as $row)
				{
					$result_ids[$total_count.'f'] = $row['topic_id'];
					$total_count++;
				}
			}
		}
		
        /** ------------------------------------
		/**  No results?  Bah, how boring...
		/** ------------------------------------*/
		
		if (count($result_ids) == 0)
		{
			$r .= $DSP->qdiv('tableCellTwo', $DSP->qdiv('highlight', $LANG->line('no_subscriptions')));
			
			return $this->account_wrapper($title, $title, $r);
		}
		
		// Sort the array
		
		ksort($result_ids);
				
        /** ---------------------------------
        /**  Do we need pagination?
        /** ---------------------------------*/
        
        $total_rows = count($result_ids);
        
		$rownum = ($rownum == '' || ($perpage > 1 AND $rownum == 1)) ? 0 : $rownum;
		
		if ($rownum > $total_rows)
		{
			$rownum = 0;
		}
					
		$t_current_page = floor(($rownum / $perpage) + 1);
		$total_pages	= intval(floor($total_rows / $perpage));
		
		if ($total_rows % $perpage) 
			$total_pages++;
		
		if ($total_rows > $perpage)
		{
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}
			
			$PGR = new Paginate();				
				
			$PGR->base_url		= $pageurl;
			$PGR->total_count 	= $total_rows;
			$PGR->per_page		= $perpage;
			$PGR->cur_page		= $rownum;
			$PGR->qstr_var      = 'rownum';
			
			$page_links	= $PGR->show_links();
			
			$result_ids = array_slice($result_ids, $rownum, $perpage);
		}
		else
		{
			$result_ids = array_slice($result_ids, 0, $perpage);	
		}


        /** ---------------------------------
        /**  Fetch Weblog Titles
        /** ---------------------------------*/

		if ($blog_subscriptions == TRUE)
		{
			$sql = "SELECT
					exp_weblog_titles.title, exp_weblog_titles.url_title, exp_weblog_titles.weblog_id, exp_weblog_titles.entry_id,
					exp_weblogs.comment_url, exp_weblogs.blog_url	
					FROM exp_weblog_titles
					LEFT JOIN exp_weblogs ON exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
					WHERE entry_id IN (";
		
			$idx = '';
		
			foreach ($result_ids as $key => $val)
			{			
				if (substr($key, strlen($key)-1) == 'b')
				{
					$idx .= $val.",";
				}
			}
		
			$idx = substr($idx, 0, -1);
			
			if ($idx != '')
			{
				$query = $DB->query($sql.$idx.') ');
	
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{		
						$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);
					
						$path = $FNS->remove_double_slashes($REGX->prep_query_string(($row['comment_url'] != '') ? $row['comment_url'] : $row['blog_url']).'/'.$row['url_title'].'/');
						
						$result_data[] = array(
												'path'	=> $DSP->qdiv('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$path, $row['title'], '', TRUE)),
												'id'	=> 'b'.$row['entry_id'],
												'type'	=> $LANG->line('comment')
												);
					}
				}
			}
		}


        /** ---------------------------------
        /**  Fetch Gallery Titles
        /** ---------------------------------*/

		if ($galery_subscriptions == TRUE)
		{
			$sql = "SELECT
					exp_gallery_entries.title, exp_gallery_entries.entry_id, exp_gallery_entries.gallery_id,
					exp_galleries.gallery_comment_url
					FROM exp_gallery_entries
					LEFT JOIN exp_galleries ON exp_gallery_entries.gallery_id = exp_galleries.gallery_id 
					WHERE entry_id IN (";
					
			$idx = '';
		
			foreach ($result_ids as $key => $val)
			{			
				if (substr($key, strlen($key)-1) == 'g')
				{
					$idx .= $val.",";
				}
			}
		
			$idx = substr($idx, 0, -1);
			
			if ($idx != '')
			{
				$query = $DB->query($sql.$idx.') ');
	
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);

						$path = $FNS->remove_double_slashes($REGX->prep_query_string($row['gallery_comment_url'] ).'/'.$row['entry_id'].'/');
					
						$result_data[] = array(
												'path'	=> $DSP->qdiv('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$path, $row['title'], '', TRUE)),
												'id'	=> 'g'.$row['entry_id'],
												'type'	=> $LANG->line('image_gallery')
												);
					}
				}
			}
		}
        		
		
        /** ---------------------------------
        /**  Fetch Forum Topics
        /** ---------------------------------*/

		if ($forum_subscriptions == TRUE)
		{
			$sql = "SELECT title, topic_id, board_forum_url FROM exp_forum_topics, exp_forum_boards 
					WHERE exp_forum_topics.board_id = exp_forum_boards.board_id 
					AND topic_id IN (";
					
			$idx = '';
		
			foreach ($result_ids as $key => $val)
			{			
				if (substr($key, strlen($key)-1) == 'f')
				{
					$idx .= $val.",";
				}
			}
		
			$idx = substr($idx, 0, -1);
			
			if ($idx != '')
			{
				$query = $DB->query($sql.$idx.') ');
	
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);
						$path = $FNS->remove_double_slashes($REGX->prep_query_string($row['board_forum_url'] ).'/viewthread/'.$row['topic_id'].'/');
						$result_data[] = array(
						
												'path'	=> $DSP->qdiv('defaultBold', $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.$path, $row['title'], '', TRUE)),
												'title'	=> $row['title'],
												'id'	=> 'f'.$row['topic_id'],
												'type'	=> $LANG->line('forum_post')
												);
					}
				}
			}
		}
		
		// Build the result table...
		
		// javascript "toggle" code.  Getting a lot of mileage out of Paul's function.
		
		$r .= $DSP->toggle();
		
		$r .= $DSP->form_open(
								array(
										'action' => 'C=myaccount'.AMP.'M=unsubscribe', 
										'name'	=> 'target',
										'id'	=> 'target'
									),
								array('id' => $id)
								);
		
		$r .= $DSP->table('tableBorder', '0', '', '100%').
			  $DSP->tr().
			  $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('title')), '56%').
			  $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('type')), '22%').
			  $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('unsubscribe').NBS.NBS), '22%').
			  $DSP->tr_c();
				
		$i = 0;
		foreach ($result_data as $val)
		{		
			$r .= $DSP->table_qrow(($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', 
												array(
														$val['path'],
														$val['type'],
														$DSP->input_checkbox('toggle[]', $val['id'])
													)
											);
		}
					
					
		// Unsubscribe button

		$r .= $DSP->table_qrow('tableCellTwo', array($page_links, '', $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('unsubscribe')))));
		$r .= $DSP->table_c();
		$r .= $DSP->form_close();
	
        // And we are done!  Finito!

        return $this->account_wrapper($title, $title, $r);
    }
    /* END */



    /** -----------------------------------
    /**  Unsubscribe to subscriptions
    /** -----------------------------------*/

	function unsubscribe()
	{
        global $IN, $DSP, $DB, $FNS, $SESS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
			$FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$id);
			exit;    
        }
        
        $query = $DB->query("SELECT email FROM exp_members WHERE member_id = '".$id."'");
        
        if ($query->num_rows != 1)
        {
            return $DSP->no_access_message();
        }

		$email = $query->row['email'];
                        
        foreach ($_POST as $key => $val)
        { 
			if (strstr($key, 'toggle') AND ! is_array($val))
			{				
            	switch (substr($val, 0, 1))
            	{
            		case "b"	: $DB->query("UPDATE exp_comments SET notify = 'n' WHERE entry_id = '".substr($val, 1)."' AND email = '".$DB->escape_str($email)."'");
            			break;
            		case "g"	: $DB->query("UPDATE exp_gallery_comments SET notify = 'n' WHERE entry_id = '".substr($val, 1)."' AND email = '".$DB->escape_str($email)."'");
            			break;
            		case "f"	: $DB->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '".substr($val, 1)."'");
            			break;
            	}
            }        
        }
	
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$id.AMP.'U=1');
        exit;    
	}
	/* END */



    /** -----------------------------------
    /**  Localization settings
    /** -----------------------------------*/

    function localization_form()
    {
        global $IN, $DB, $DSP, $FNS, $LOC, $SESS, $LANG,$PREFS;
                        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        if ($PREFS->ini('allow_member_localization') == 'n' AND $SESS->userdata('group_id') != 1)
        {
            return $DSP->error_message($LANG->line('localization_disallowed'));
        }
        
        
        $title = $LANG->line('localization_settings');
    
        $query = $DB->query("SELECT timezone,daylight_savings,language FROM exp_members WHERE member_id = '$id'");
        	
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=localization_update')).
              $DSP->input_hidden('id', $id);              

        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('localization_updated')));
        }

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
                            
		$r .= $title;

		$r .= $DSP->td_c().
              $DSP->tr_c();
	
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('timezone')), '50%');
		$r .= $DSP->table_qcell('tableCellTwo', $LOC->timezone_menu(($query->row['timezone'] == '') ? 'UTC' : $query->row['timezone']), '50%');
		$r .= $DSP->tr_c();		

		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
		$r .= $DSP->input_checkbox('daylight_savings', 'y', ($query->row['daylight_savings'] == 'y') ? 1 : '').' '.$LANG->line('daylight_savings_time');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();


		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('time_format')), '50%');
		$r .= $DSP->td('tableCellTwo', '50%');
		$r .= $DSP->input_select_header('time_format');    
		$r .= $DSP->input_select_option('us', $LANG->line('united_states'), ($SESS->userdata['time_format'] == 'us') ? 1 : '');    
		$r .= $DSP->input_select_option('eu', $LANG->line('european'), ($SESS->userdata['time_format'] == 'eu') ? 1 : '');
		$r .= $DSP->input_select_footer();
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();		

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $LANG->line('language')), '50%');
		$r .= $DSP->table_qcell('tableCellOne', $FNS->language_pack_names(($query->row['language'] == '') ? 'english' : $query->row['language']), '50%');
		$r .= $DSP->tr_c();		

		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();

        $r .= $DSP->table_c(); 
        $r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */
    



    /** -----------------------------------
    /**  Localization update
    /** -----------------------------------*/

    function localization_update()
    {
        global $IN, $FNS, $DB, $PREFS, $LANG, $SESS, $PREFS, $REGX;
       
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        if ($PREFS->ini('allow_member_localization') == 'n' AND $SESS->userdata('group_id') != 1)
        {
            return $DSP->error_message($LANG->line('localization_disallowed'));
        }
        
		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        $data['language']    = $FNS->filename_security($_POST['deft_lang']);
        $data['timezone']    = $_POST['server_timezone'];
        $data['time_format'] = $_POST['time_format'];
        
        if ( ! is_dir(PATH_LANG.$data['language']))
        {
            return $DSP->error_message($LANG->line('localization_disallowed'));
        }

        $data['daylight_savings'] = ($IN->GBL('daylight_savings', 'POST') == 'y') ? 'y' : 'n';
        
        $DB->query($DB->update_string('exp_members', $data, "member_id = '$id'"));   
        
        $query = $DB->query("SELECT timezone, daylight_savings FROM exp_members WHERE localization_is_site_default = 'y'");
        
        if ($query->num_rows == 1)
        {
        	$config = array(
        					'default_site_timezone' => $query->row['timezone'],
        					'default_site_dst'		=> $query->row['daylight_savings']
        					);        	
        }
        else
        {
        	$config = array('default_site_timezone' => '', 'default_site_dst'		=> 'n');        	        
        }
        
		//  Update Config Values
        
        $query = $DB->query("SELECT site_system_preferences FROM exp_sites WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
        				
		$prefs = $REGX->array_stripslashes(unserialize($query->row['site_system_preferences']));
											
		foreach($config as $key => $value)
		{
			$prefs[$key] = str_replace('\\', '\\\\', $value);
		}
		
		$DB->query($DB->update_string('exp_sites', 
									  array('site_system_preferences' => addslashes(serialize($prefs))),
									  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));       
        
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=localization'.AMP.'id='.$id.AMP.'U=1');
        exit;    
    }
    /* END */




    /** -----------------------------------
    /**  Edit Signature Form
    /** -----------------------------------*/

    function edit_signature()
    {
        global $IN, $DB, $DSP, $SESS, $LANG, $PREFS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $title = $LANG->line('edit_signature');
        
		/** -------------------------------------
		/**  Create the HTML formatting buttons
		/** -------------------------------------*/

		$buttons = '';
		if ( ! class_exists('Html_buttons'))
		{
			if (include_once(PATH_LIB.'html_buttons'.EXT))
			{
				$BUTT = new Html_buttons();
				$BUTT->allow_img = ($PREFS->ini('sig_allow_img_hotlink') == 'y') ? TRUE : FALSE;
				
				
				$buttons = $BUTT->create_buttons();
				
				$buttons = str_replace(LD.'lang:close_tags'.RD, $LANG->line('close_tags'), $buttons);
			}
		}        
        
		$query = $DB->query("SELECT signature, sig_img_filename, sig_img_width, sig_img_height FROM exp_members WHERE member_id = '{$id}'");
    
	
		$max_kb = ($PREFS->ini('sig_img_max_kb') == '' OR $PREFS->ini('sig_img_max_kb') == 0) ? 50 : $PREFS->ini('sig_img_max_kb');
		$max_w  = ($PREFS->ini('sig_img_max_width') == '' OR $PREFS->ini('sig_img_max_width') == 0) ? 100 : $PREFS->ini('sig_img_max_width');
		$max_h  = ($PREFS->ini('sig_img_max_height') == '' OR $PREFS->ini('sig_img_max_height') == 0) ? 100 : $PREFS->ini('sig_img_max_height');
		$max_size = str_replace('%x', $max_w, $LANG->line('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

    
        $r = "<form method=\"post\" action=\"".BASE.AMP.'C=myaccount'.AMP.'M=update_signature'."\" enctype=\"multipart/form-data\" name=\"submit_post\" id=\"submit_post\" >\n";
    
        $r .= $DSP->input_hidden('id', $id);
              
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('signature_updated')));
        }
              
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
		$r .= $title;
        $r .= $DSP->td_c().
              $DSP->tr_c();
              
        $buttons2 = '
			<select name="size" class="select" onchange="selectinsert(this, \'size\')" >
			<option value="0">'.$LANG->line('size').'</option>
			<option value="1">'.$LANG->line('small').'</option>
			<option value="3">'.$LANG->line('medium').'</option>
			<option value="4">'.$LANG->line('large').'</option>
			<option value="5">'.$LANG->line('very_large').'</option>
			<option value="6">'.$LANG->line('largest').'</option>
			</select>
			
			<select name="color" class="select" onchange="selectinsert(this, \'color\')">
			<option value="0">'.$LANG->line('color').'</option>
			<option value="blue">'.$LANG->line('blue').'</option>
			<option value="green">'.$LANG->line('green').'</option>
			<option value="red">'.$LANG->line('red').'</option>
			<option value="purple">'.$LANG->line('purple').'</option>
			<option value="orange">'.$LANG->line('orange').'</option>
			<option value="yellow">'.$LANG->line('yellow').'</option>
			<option value="brown">'.$LANG->line('brown').'</option>
			<option value="pink">'.$LANG->line('pink').'</option>
			<option value="gray">'.$LANG->line('grey').'</option>
			</select>
        ';
              					
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '18%');
		$r .= $DSP->qdiv('defaultRight', $DSP->qdiv('buttonMode', $LANG->line('guided')."&nbsp;<input type='radio' name='mode' value='guided' onclick='setmode(this.value)' />&nbsp;".$LANG->line('normal')."&nbsp;<input type='radio' name='mode' value='normal' onclick='setmode(this.value)' checked='checked'/>"));
		$r .= $DSP->td_c();			
		$r .= $DSP->td('tableCellTwo', '82%');
		$r .= $buttons;
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();		
	
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '18%');
		$r .= $DSP->qdiv('defaultRight', $DSP->qdiv('buttonMode', $LANG->line('font_formatting')));
		$r .= $DSP->td_c();			
		$r .= $DSP->td('tableCellTwo', '82%');
		$r .= $buttons2;
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();		
	
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '18%', '', '', 'top');
		$r .= $DSP->qdiv('defaultRight', $DSP->qdiv('defaultBold', $LANG->line('signature')));
		$r .= $DSP->td_c();
		$r .= $DSP->td('tableCellTwo', '83%');
		$r .= $DSP->input_textarea('body', $query->row['signature'], '8', 'textarea', '100%');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();	
		
		
		if ($PREFS->ini('sig_allow_img_upload') == 'y')
		{
			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellTwo', '18%');
			$r .= $DSP->qdiv('defaultRight', $DSP->qdiv('defaultBold', $LANG->line('signature_image')));
			$r .= $DSP->td_c();			
			$r .= $DSP->td('tableCellTwo', '82%');
	
			if ($query->row['sig_img_filename'] == '')
			{
				$r .= $LANG->line('no_image_exists');
			}
			else
			{
				$r .= '<img src="'.$PREFS->ini('sig_img_url', TRUE).$query->row['sig_img_filename'].'" border="0" width="'.$query->row['sig_img_width'].'" height="'.$query->row['sig_img_height'].'" title="'.$LANG->line('signature_image').'" />';
			}
	
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();		

			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellTwo', '18%', '', '', 'top');
			$r .= $DSP->qdiv('defaultRight', $DSP->qdiv('defaultBold', $LANG->line('upload_image')));
			$r .= $DSP->td_c();			
			$r .= $DSP->td('tableCellTwo', '82%');

        	$r .= $DSP->qdiv('itemWrapper', "<input type=\"file\" name=\"userfile\" size=\"20\" />");
        	$r .= $DSP->qdiv('itemWrapper',$DSP->qdiv('buttonMode', $max_size));
        	$r .= $DSP->qdiv('itemWrapper',$DSP->qdiv('buttonMode', $LANG->line('allowed_image_types')));
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();		
		}
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->div('buttonWrapper');
		$r .= $DSP->input_submit($LANG->line('update_signature'));
		
		if ($query->row['sig_img_filename'] != '')
		{
			$r .= NBS.NBS.$DSP->input_submit($LANG->line('remove_image'), 'remove');
		}
		$r .= $DSP->div_c();
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();


        $r .= $DSP->table_c(); 
       	$r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */


    /** ----------------------------------
    /**  Update signature
    /** ----------------------------------*/
    
    function update_signature()
    {  
        global $FNS, $DB, $DSP, $LANG, $SESS, $REGX, $PREFS;

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

		$_POST['body'] = $DB->escape_str($REGX->xss_clean($_POST['body']));
	       
		
		$maxlength = ($PREFS->ini('sig_maxlength') == 0) ? 10000 : $PREFS->ini('sig_maxlength');
		
		if (strlen($_POST['body']) > $maxlength)
		{
			return $DSP->error_message(str_replace('%x', $maxlength, $LANG->line('sig_too_big')));
		}


        $DB->query("UPDATE exp_members SET signature = '".$_POST['body']."' WHERE member_id ='".$id."'");
        
        
		/** ----------------------------------------
		/**  Is there an image to upload or remove?
		/** ----------------------------------------*/
				
		if ((isset($_FILES['userfile']) AND $_FILES['userfile']['name'] != '') OR isset($_POST['remove']))
		{
			return $this->upload_signature_image();
		}
        
        
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id.AMP.'U=1');
        exit;    
    }
    /* END */


    /** -----------------------------------
    /**  Edit Avatar
    /** -----------------------------------*/

    function edit_avatar()
    {
        global $IN, $DB, $DSP, $FNS, $LOC, $SESS, $LANG, $PREFS;
                        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		/** ----------------------------------------
		/**  Are avatars enabled?
		/** ----------------------------------------*/
		
		if ($PREFS->ini('enable_avatars') == 'n')
		{
			return $DSP->error_message($LANG->line('avatars_not_enabled'));
		}
        		
		$query = $DB->query("SELECT avatar_filename, avatar_width, avatar_height FROM exp_members WHERE member_id = '".$id."'");
		
		if ($query->row['avatar_filename'] == '')
		{			
			$cur_avatar_url = '';
			$avatar_width 	= '';
			$avatar_height 	= '';
		}
		else
		{
			$cur_avatar_url = $PREFS->ini('avatar_url', TRUE).$query->row['avatar_filename'];
			$avatar_width 	= $query->row['avatar_width'];
			$avatar_height 	= $query->row['avatar_height'];
		}

        $title = $LANG->line('edit_avatar');
        	
        	
       	$r	= '<form method="post" action ="'.BASE.AMP.'C=myaccount'.AMP.'M=update_avatar'.'" enctype="multipart/form-data" >';
        $r .= $DSP->input_hidden('id', $id);              

        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('avatar_updated')));
        }

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
                            
		$r .= $title;

		$r .= $DSP->td_c().
              $DSP->tr_c();
              
		  
		if ($query->row['avatar_filename'] != '')
		{
			$avatar = '<img src="'.$cur_avatar_url.'" border="0" width="'.$avatar_width.'" height="'.$avatar_height.'" title="'.$LANG->line('my_avatar').'" />';
		}
		else
		{
			$avatar = $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_avatar')));
		}
		
		$i = 0;
		$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('current_avatar')), '35%');
		$r .= $DSP->table_qcell($style, $avatar, '65%');
		$r .= $DSP->tr_c();		
		
		
		/** ----------------------------------------
		/**  Are there pre-installed avatars?
		/** ----------------------------------------*/
		
		// We'll make a list of all folders in the "avatar" folder,
		// then check each one to see if they contain images.  If so
		// we will add it to the list
		
		$avatar_path = $PREFS->ini('avatar_path', TRUE);
		
		$extensions = array('.gif', '.jpg', '.jpeg', '.png');
				
		if ($fp = @opendir($avatar_path))
		{
		 	$folders = '';
		 	
			while (FALSE !== ($file = readdir($fp))) 
			{ 
				if (is_dir($avatar_path.$file) AND $file != 'uploads' AND $file != '.' AND $file != '..')
				{
					if ($np = @opendir($avatar_path.$file))
					{
						while (FALSE !== ($innerfile = readdir($np))) 
						{ 
							if (FALSE !== ($pos = strpos($innerfile, '.')))
							{
								if (in_array(substr($innerfile, $pos), $extensions))
								{
									$name = ucwords(str_replace("_", " ", $file));
									$temp = $DSP->qdiv('itemWrapper', $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$file.AMP.'id='.$id, $name));									
									$folders .= $temp;
									
									break;
								}
							}							
						}
						
						closedir($np); 
					}
				}
			} 
		
			closedir($fp); 
		
			if ($folders != '')
			{
				$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
			
				$r .= $DSP->tr();
				$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $DSP->qdiv('itemWrapper', $LANG->line('choose_installed_avatar'))), '35%', 'top');
				$r .= $DSP->table_qcell($style, $folders, '65%');
				$r .= $DSP->td_c();
				$r .= $DSP->tr_c();
			}
		}
				
		/** ----------------------------------------
		/**  Set the default image meta values
		/** ----------------------------------------*/
		
		$max_kb = ($PREFS->ini('avatar_max_kb') == '' OR $PREFS->ini('avatar_max_kb') == 0) ? 50 : $PREFS->ini('avatar_max_kb');
		$max_w  = ($PREFS->ini('avatar_max_width') == '' OR $PREFS->ini('avatar_max_width') == 0) ? 100 : $PREFS->ini('avatar_max_width');
		$max_h  = ($PREFS->ini('avatar_max_height') == '' OR $PREFS->ini('avatar_max_height') == 0) ? 100 : $PREFS->ini('avatar_max_height');
		$max_size = str_replace('%x', $max_w, $LANG->line('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

			
		if ($PREFS->ini('allow_avatar_uploads') == 'y')
		{
			$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
		
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('upload_an_avatar')), '35%');
			$r .= $DSP->table_qcell($style, '<input type="file" name="userfile" size="20" class="input" />', '65%');
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
			
			$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
			
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qspan('highlight_alt', $max_size)), '35%');
			$r .= $DSP->table_qcell($style, $DSP->qspan('highlight_alt', $LANG->line('allowed_image_types')), '65%');
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
		}
		
		if ($PREFS->ini('allow_avatar_uploads') == 'y' OR $cur_avatar_url != '')
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
	
			$r .= $DSP->tr();
			$r .= $DSP->td($style, '', '2');
			$r .= $DSP->div('buttonWrapper');
			
			if ($PREFS->ini('allow_avatar_uploads') == 'y')
			{
				$r .= $DSP->input_submit($LANG->line('upload_avatar'));
			}
			
			if ($cur_avatar_url != '')
			{
				$r .= NBS.NBS.$DSP->input_submit($LANG->line('remove_avatar'), 'remove');			
			}
			
			$r .= $DSP->div_c();
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
		}
					
        $r .= $DSP->table_c(); 
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */
    


    /** -----------------------------------
    /**  Edit Photo
    /** -----------------------------------*/

    function edit_photo()
    {
        global $IN, $DB, $DSP, $FNS, $LOC, $SESS, $LANG, $PREFS;
                        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		/** ----------------------------------------
		/**  Are photos enabled?
		/** ----------------------------------------*/
		
		if ($PREFS->ini('enable_photos') == 'n')
		{
			return $DSP->error_message($LANG->line('photos_not_enabled'));
		}
        		
		$query = $DB->query("SELECT photo_filename, photo_width, photo_height FROM exp_members WHERE member_id = '".$id."'");
		
		if ($query->row['photo_filename'] == '')
		{			
			$cur_photo_url = '';
			$photo_width 	= '';
			$photo_height 	= '';
		}
		else
		{
			$cur_photo_url = $PREFS->ini('photo_url', TRUE).$query->row['photo_filename'];
			$photo_width 	= $query->row['photo_width'];
			$photo_height 	= $query->row['photo_height'];
		}

        $title = $LANG->line('edit_photo');
        	
        	
       	$r	= '<form method="post" action ="'.BASE.AMP.'C=myaccount'.AMP.'M=update_photo'.'" enctype="multipart/form-data" >';
        $r .= $DSP->input_hidden('id', $id);              

        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('photo_updated')));
        }

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
                            
		$r .= $title;

		$r .= $DSP->td_c().
              $DSP->tr_c();
              
		  
		if ($query->row['photo_filename'] != '')
		{
			$photo = '<img src="'.$cur_photo_url.'" border="0" width="'.$photo_width.'" height="'.$photo_height.'" title="'.$LANG->line('my_photo').'" />';
		}
		else
		{
			$photo = $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_photo_exists')));
		}
		
		$i = 0;
		$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('current_photo')), '35%');
		$r .= $DSP->table_qcell($style, $photo, '65%');
		$r .= $DSP->tr_c();		
						
		/** ----------------------------------------
		/**  Set the default image meta values
		/** ----------------------------------------*/
		
		$max_kb = ($PREFS->ini('photo_max_kb') == '' OR $PREFS->ini('photo_max_kb') == 0) ? 50 : $PREFS->ini('photo_max_kb');
		$max_w  = ($PREFS->ini('photo_max_width') == '' OR $PREFS->ini('photo_max_width') == 0) ? 100 : $PREFS->ini('photo_max_width');
		$max_h  = ($PREFS->ini('photo_max_height') == '' OR $PREFS->ini('photo_max_height') == 0) ? 100 : $PREFS->ini('photo_max_height');
		$max_size = str_replace('%x', $max_w, $LANG->line('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

			
		$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
	
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qspan('defaultBold', $LANG->line('upload_photo')), '35%');
		$r .= $DSP->table_qcell($style, '<input type="file" name="userfile" size="20" class="input" />', '65%');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
		
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell($style, $DSP->qdiv('itemWrapper', $DSP->qspan('highlight_alt', $max_size)), '35%');
		$r .= $DSP->table_qcell($style, $DSP->qspan('highlight_alt', $LANG->line('allowed_image_types')), '65%');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
			
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('upload_photo')).NBS.NBS.$DSP->input_submit($LANG->line('remove_photo'), 'remove'));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
			
        $r .= $DSP->table_c(); 
        $r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */



    /** ----------------------------------------
    /**  Browse Avatars
    /** ----------------------------------------*/
	
	function browse_avatars()
	{
		global $IN, $DSP, $DB, $LANG, $PREFS, $SESS, $FNS;
		
		
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		/** ----------------------------------------
		/**  Are avatars enabled?
		/** ----------------------------------------*/
		
		if ($PREFS->ini('enable_avatars') == 'n')
		{
			return $DSP->error_message($LANG->line('avatars_not_enabled'));
		}
			
		/** ----------------------------------------
		/**  Define the paths and get the avatars
		/** ----------------------------------------*/
		
		$avatar_path = $PREFS->ini('avatar_path', TRUE).$FNS->filename_security($IN->GBL('folder')).'/';
		$avatar_url  = $PREFS->ini('avatar_url', TRUE).$FNS->filename_security($IN->GBL('folder')).'/';

		$avatars = $this->_get_avatars($avatar_path);
		
		/** ----------------------------------------
		/**  Did we succeed?
		/** ----------------------------------------*/
		
		if (count($avatars) == 0)
		{
			return $DSP->error_message($LANG->line('avatars_not_found'));		
		}
		
		/** ----------------------------------------
		/**  Pagination anyone?
		/** ----------------------------------------*/
	
		$pagination = '';
		$max_rows	= 2;
		$max_cols	= 3;
		$col_ct		= 0;
		$perpage 	= $max_rows * $max_cols;
		$total_rows = count($avatars);
		$rownum 	= ($IN->GBL('row') == '') ? 0 : $IN->GBL('row');
		$base_url	= BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'id='.$id.AMP.'folder='.$IN->GBL('folder');
		
		if ($rownum > count($avatars)) 
			$rownum = 0;
				
		if ($total_rows > $perpage)
		{		
			$avatars = array_slice($avatars, $rownum, $perpage);
			
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}
			
			$PGR = new Paginate();
				
			$PGR->base_url		= $base_url;
			$PGR->first_url		= $base_url;
			$PGR->qstr_var		= 'row';
			$PGR->total_count 	= $total_rows;
			$PGR->per_page		= $perpage;
			$PGR->cur_page		= $rownum;			
			$pagination			= $PGR->show_links();
		
			// We add this for use later
			
			if ($rownum != '')
			{
				$base_url .= $rownum.'/';
			}
		}
		
		/** ----------------------------------------
		/**  Build the table rows
		/** ----------------------------------------*/
		
		$avstr = '';
		foreach ($avatars as $image)
		{
			if ($col_ct == 0)
			{
				$avstr .= "<tr>\n";
			}
					
			$avstr .= "<td align='center'><img src='".$avatar_url.$image."' border='0' /><br /><input type='radio' name='avatar' value='".$image."' /></td>\n";
			$col_ct++;
			
			if ($col_ct == $max_cols)
			{
				$avstr .= "</tr>";
				$col_ct = 0;
			}			
		}
		
		if ($col_ct < $max_cols AND count($avatars) >= $max_cols)
		{
			for ($i = $col_ct; $i < $max_cols; $i++)
			{
				$avstr .= "<td>&nbsp;</td>\n";
			}
			
			$avstr .= "</tr>";
		}
		
		if ( ! preg_match("#\<\/tr\>$#i", $avstr))
		{
			$avstr .= "</tr>";
		}
				
		/** ----------------------------------------
		/**  Finalize the output
		/** ----------------------------------------*/
			
        $title = $LANG->line('browse_avatars');
        	
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=select_avatar')).
              $DSP->input_hidden('id', $id).
              $DSP->input_hidden('folder', $IN->GBL('folder'));

        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading');
		$r .= $title;
		$r .= $DSP->td_c().
              $DSP->tr_c();
              
              
        $avstr = $DSP->table('', '0', '10', '100%').$avstr.$DSP->table_c();


		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellOne', $avstr);	
		$r .= $DSP->tr_c();
		
		if ($pagination != '')
		{
			$r .= $DSP->tr();
			$r .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultCenter', $pagination));	
			$r .= $DSP->tr_c();
		}
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo');
		$r .= $DSP->div('defaultCenter');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('choose_selected')));
		$r .= $DSP->div_c();
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
				
        $r .= $DSP->table_c(); 
        $r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
	}
	/* END */



    /** ----------------------------------------
    /**  Select Avatar From  Library
    /** ----------------------------------------*/

	function select_avatar()
	{
		global $FNS, $IN, $PREFS, $DB, $LANG, $SESS;
				
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
		/** ----------------------------------------
		/**  Are avatars enabled?
		/** ----------------------------------------*/
		
		if ($PREFS->ini('enable_avatars') == 'n')
		{
			return $DSP->error_message($LANG->line('avatars_not_enabled'));
		}
		
		if ($IN->GBL('avatar') === FALSE OR $IN->GBL('folder') === FALSE)
		{
			return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$IN->GBL('folder'));
		}
		
		$folder	= $FNS->filename_security($IN->GBL('folder'));
		$file	= $FNS->filename_security($IN->GBL('avatar'));

		$basepath = $PREFS->ini('avatar_path', TRUE);		
		$avatar	= $folder.'/'.$file;

		$allowed = $this->_get_avatars($basepath.$folder);

		if ( ! in_array($file, $allowed) OR $folder == 'upload')
		{
			return $DSP->error_message($LANG->line('avatars_not_found'));		
		}

		/** ----------------------------------------
		/**  Fetch the avatar meta-data
		/** ----------------------------------------*/

		if ( ! function_exists('getimagesize')) 
		{
			return $DSP->error_message($LANG->line('image_assignment_error'));
		}
		
		$vals = @getimagesize($basepath.$avatar);
		$width	= $vals['0'];
		$height	= $vals['1'];

		/** ----------------------------------------
		/**  Update DB
		/** ----------------------------------------*/
		
		$DB->query($DB->update_string('exp_members', array('avatar_filename' => $avatar, 'avatar_width' => $width, 'avatar_height' => $height), array('member_id' => $SESS->userdata['member_id'])));
	
		return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.AMP.'id='.$id.AMP.'U=1');
	}
	/* END */

	/** ----------------------------------------
    /**  Get all Avatars from a Folder
    /** ----------------------------------------*/

	function _get_avatars($avatar_path)
	{
	    /** ----------------------------------------
	    /**  Is this a valid avatar folder?
	    /** ----------------------------------------*/

	    $extensions = array('.gif', '.jpg', '.jpeg', '.png');

	    if ( ! @is_dir($avatar_path) OR ! $fp = @opendir($avatar_path))
	    {
	        return array();
	    }

	    /** ----------------------------------------
	    /**  Grab the image names
	    /** ----------------------------------------*/

	    $avatars = array();

	    while (FALSE !== ($file = readdir($fp))) 
	    { 
	        if (FALSE !== ($pos = strpos($file, '.')))
	        {
	            if (in_array(substr($file, $pos), $extensions))
	            {
	                $avatars[] = $file;
	            }
	        }                            
	    }

	    closedir($fp);

	    return $avatars;
	}
	/* END */

    /** ----------------------------------------
    /**  Upload Avatar or Profile Photo
    /** ----------------------------------------*/
    	
	function upload_avatar()
	{    
    	return $this->_upload_image('avatar');
    }

	function upload_photo()
	{    
    	return $this->_upload_image('photo');
    }
    
    function upload_signature_image()
    {
    	return $this->_upload_image('sig');
    }
	
	function _upload_image($type = 'avatar')
	{
		global $DSP, $FNS, $IN, $PREFS, $DB, $LANG, $SESS, $OUT;
		
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        
		switch ($type)
		{
			case 'avatar'	:	
								$edit_image		= 'edit_avatar';
								$enable_pref	= 'allow_avatar_uploads';
								$not_enabled	= 'avatars_not_enabled';
								$remove			= 'remove_avatar';
								$removed		= 'avatar_removed';
								$updated		= 'avatar_updated';
				break;
			case 'photo'	:	
								$edit_image 	= 'edit_photo';
								$enable_pref	= 'enable_photos';
								$not_enabled	= 'photos_not_enabled';
								$remove			= 'remove_photo';
								$removed		= 'photo_removed';
								$updated		= 'photo_updated';
								
				break;
			case 'sig'		:	
								$edit_image 	= 'edit_signature';
								$enable_pref	= 'sig_allow_img_upload';
								$not_enabled	= 'sig_img_not_enabled';
								$remove			= 'remove_sig_image';
								$removed		= 'sig_img_removed';
								$updated		= 'signature_updated';
				break;		
		}
		
		
		/** ----------------------------------------
		/**  Is this a remove request?
		/** ----------------------------------------*/
		
		if ( ! isset($_POST['remove']))
		{
			if ($PREFS->ini($enable_pref) == 'n')
			{
				return $this->_error_message($not_enabled);
			}
		}
		else
		{
			if ($type == 'avatar')
			{
				$query = $DB->query("SELECT avatar_filename FROM exp_members WHERE member_id = '{$id}'");
				
				if ($query->row['avatar_filename'] == '')
				{
					return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.AMP.'id='.$id);
				}
				
				$DB->query("UPDATE exp_members SET avatar_filename = '', avatar_width='', avatar_height='' WHERE member_id = '{$id}' ");

				if (strncmp($query->row['avatar_filename'], 'uploads/', 8) == 0)
				{
					@unlink($PREFS->ini('avatar_path', TRUE).$query->row['avatar_filename']);
				}
				
				return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.AMP.'id='.$id);
			}
			elseif ($type == 'photo')
			{
				$query = $DB->query("SELECT photo_filename FROM exp_members WHERE member_id = '{$id}'");
				
				if ($query->row['photo_filename'] == '')
				{
					return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_photo'.AMP.'id='.$id);
				}
				
				$DB->query("UPDATE exp_members SET photo_filename = '', photo_width='', photo_height='' WHERE member_id = '{$id}' ");
			
				@unlink($PREFS->ini('photo_path', TRUE).$query->row['photo_filename']);
				
				return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_photo'.AMP.'id='.$id);
			}
			else
			{
				$query = $DB->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '{$id}'");
				
				if ($query->row['sig_img_filename'] == '')
				{
					return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id);
				}
				
				$DB->query("UPDATE exp_members SET sig_img_filename = '', sig_img_width='', sig_img_height='' WHERE member_id = '{$id}' ");
			
				@unlink($PREFS->ini('sig_img_path', TRUE).$query->row['sig_img_filename']);
				
				return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id);
			}
		}
		
		
		/** ----------------------------------------
		/**  Do the have the GD library?
		/** ----------------------------------------*/

		if ( ! function_exists('getimagesize')) 
		{
			return $this->_error_message('gd_required');		
		}
										
		/** ----------------------------------------
		/**  Is there $_FILES data?
		/** ----------------------------------------*/
				
		if ( ! isset($_FILES['userfile']))
		{
			return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_'.$type.AMP.'id='.$id);
		}

		/** ----------------------------------------
		/**  Check the image size
		/** ----------------------------------------*/
		
		$size = ceil(($_FILES['userfile']['size']/1024));
		
		if ($type == 'avatar')
		{
			$max_size = ($PREFS->ini('avatar_max_kb') == '' OR $PREFS->ini('avatar_max_kb') == 0) ? 50 : $PREFS->ini('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_size = ($PREFS->ini('photo_max_kb') == '' OR $PREFS->ini('photo_max_kb') == 0) ? 50 : $PREFS->ini('photo_max_kb');
		}
		else
		{
			$max_size = ($PREFS->ini('sig_img_max_kb') == '' OR $PREFS->ini('sig_img_max_kb') == 0) ? 50 : $PREFS->ini('sig_img_max_kb');
		}
		
		$max_size = preg_replace("/(\D+)/", "", $max_size);

		if ($size > $max_size)
		{
			return $OUT->show_user_error('submission', str_replace('%s', $max_size, $LANG->line('image_max_size_exceeded')));
		}
		
		/** ----------------------------------------
		/**  Is the upload path valid and writable?
		/** ----------------------------------------*/
		
		if ($type == 'avatar')
		{
			$upload_path = $PREFS->ini('avatar_path', TRUE).'uploads/';
		}
		elseif ($type == 'photo')
		{
			$upload_path = $PREFS->ini('photo_path', TRUE);
		}
		else
		{
			$upload_path = $PREFS->ini('sig_img_path', TRUE);
		}

		if ( ! @is_dir($upload_path) OR ! is_writable($upload_path))
		{
			return $this->_error_message('image_assignment_error');
		}

		/** -------------------------------------
		/**  Set some defaults
		/** -------------------------------------*/
		
		$filename = $_FILES['userfile']['name'];
		
		if ($type == 'avatar')
		{
			$max_width	= ($PREFS->ini('avatar_max_width') == '' OR $PREFS->ini('avatar_max_width') == 0) ? 100 : $PREFS->ini('avatar_max_width');
			$max_height	= ($PREFS->ini('avatar_max_height') == '' OR $PREFS->ini('avatar_max_height') == 0) ? 100 : $PREFS->ini('avatar_max_height');	
			$max_kb		= ($PREFS->ini('avatar_max_kb') == '' OR $PREFS->ini('avatar_max_kb') == 0) ? 50 : $PREFS->ini('avatar_max_kb');	
		}
		elseif ($type == 'photo')
		{
			$max_width	= ($PREFS->ini('photo_max_width') == '' OR $PREFS->ini('photo_max_width') == 0) ? 100 : $PREFS->ini('photo_max_width');
			$max_height	= ($PREFS->ini('photo_max_height') == '' OR $PREFS->ini('photo_max_height') == 0) ? 100 : $PREFS->ini('photo_max_height');	
			$max_kb		= ($PREFS->ini('photo_max_kb') == '' OR $PREFS->ini('photo_max_kb') == 0) ? 50 : $PREFS->ini('photo_max_kb');
		}
		else
		{
			$max_width	= ($PREFS->ini('sig_img_max_width') == '' OR $PREFS->ini('sig_img_max_width') == 0) ? 100 : $PREFS->ini('sig_img_max_width');
			$max_height	= ($PREFS->ini('sig_img_max_height') == '' OR $PREFS->ini('sig_img_max_height') == 0) ? 100 : $PREFS->ini('sig_img_max_height');	
			$max_kb		= ($PREFS->ini('sig_img_max_kb') == '' OR $PREFS->ini('sig_img_max_kb') == 0) ? 50 : $PREFS->ini('sig_img_max_kb');
		}

		/** ----------------------------------------
		/**  Does the image have a file extension?
		/** ----------------------------------------*/
		
		if (strpos($filename, '.') === FALSE)
		{
			return $OUT->show_user_error('submission', $LANG->line('invalid_image_type'));
		}
		
		/** ----------------------------------------
		/**  Is it an allowed image type?
		/** ----------------------------------------*/
		$x = explode('.', $filename);
		$extension = '.'.end($x);
		
		// We'll do a simple extension check now.
		// The file upload class will do a more thorough check later
		
		$types = array('.jpg', '.jpeg', '.gif', '.png');
		
		if ( ! in_array(strtolower($extension), $types))
		{
			return $OUT->show_user_error('submission', $LANG->line('invalid_image_type'));
		}

		/** -------------------------------------
		/**  Assign the name of the image
		/** -------------------------------------*/
		
		$new_filename = $type.'_'.$id.strtolower($extension);
		
		/** -------------------------------------
		/**  Do they currently have an avatar or photo?
		/** -------------------------------------*/
		
		if ($type == 'avatar')
		{
			$query = $DB->query("SELECT avatar_filename FROM exp_members WHERE member_id = '{$id}'");
			$old_filename = ($query->row['avatar_filename'] == '') ? '' : $query->row['avatar_filename'];
			
			if (strpos($old_filename, '/') !== FALSE)
			{
				$x = explode('/', trim($old_filename, '/'));
				$old_filename =  end($x);
			}
		}
		elseif ($type == 'photo')
		{
			$query = $DB->query("SELECT photo_filename FROM exp_members WHERE member_id = '{$id}'");
			$old_filename = ($query->row['photo_filename'] == '') ? '' : $query->row['photo_filename'];
		}
		else
		{
			$query = $DB->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '{$id}'");
			$old_filename = ($query->row['sig_img_filename'] == '') ? '' : $query->row['sig_img_filename'];
		}
		
		/** -------------------------------------
		/**  Upload the image
		/** -------------------------------------*/

        require PATH_CORE.'core.upload'.EXT;
  
        $UP = new Upload();
       
        $UP->new_name = $new_filename;
        
		$UP->set_upload_path($upload_path);
        $UP->set_allowed_types('img');
   
        if ( ! $UP->upload_file())
        {
			@unlink($UP->new_name);
			
			$info = ($UP->error_msg == 'invalid_filetype') ? "<div class='itempadbig'>".$LANG->line('invalid_image_type')."</div>" : '';
			return $OUT->show_user_error('submission', $LANG->line($UP->error_msg).$info);
        }
		
		/** -------------------------------------
		/**  Do we need to resize?
		/** -------------------------------------*/
		
		$vals	= @getimagesize($UP->new_name);		
		$width	= $vals['0'];
		$height	= $vals['1'];
		
		if ($width > $max_width OR $height > $max_height)
		{
			/** -------------------------------------
			/**  Was resizing successful?
			/** -------------------------------------*/
			
			// If not, we'll delete the uploaded image and
			// issue an error saying the file is to big
		
			if ( ! $this->_image_resize($new_filename, $type))
			{
				@unlink($UP->new_name);

				$max_size = str_replace('%x', $max_width, $LANG->line('max_image_size'));
				$max_size = str_replace('%y', $max_height, $max_size);
				$max_size .= ' - '.$max_kb.'KB';

				return $OUT->show_user_error('submission', $max_size);
			}
		}
		
		/** -------------------------------------
		/**  Check the width/height one last time
		/** -------------------------------------*/
	
		// Since our image resizing class will only reproportion
		// based on one axis, we'll check the size again, just to 
		// be safe.  We need to make absolutely sure that if someone
		// submits a very short/wide image it'll contrain properly
	
		$vals	= @getimagesize($UP->new_name);		
		$width	= $vals['0'];
		$height	= $vals['1'];
		
		if ($width > $max_width OR $height > $max_height)
		{
			$this->_image_resize($new_filename, $type, 'height');
			$vals	= @getimagesize($UP->new_name);		
			$width	= $vals['0'];
			$height	= $vals['1'];
		}
		
		/** -------------------------------------
		/**  Delete the old file if necessary
		/** -------------------------------------*/
		
		if ($old_filename != $new_filename)
		{
			@unlink($upload_path.$old_filename);
		}
		
		/** ----------------------------------------
		/**  Update DB
		/** ----------------------------------------*/

		if ($type == 'avatar')
		{
			$avatar = 'uploads/'.$new_filename;
			$DB->query("UPDATE exp_members SET avatar_filename = '{$avatar}', avatar_width='{$width}', avatar_height='{$height}' WHERE member_id = '{$id}' ");
		}
		elseif ($type == 'photo')
		{
			$DB->query("UPDATE exp_members SET photo_filename = '{$new_filename}', photo_width='{$width}', photo_height='{$height}' WHERE member_id = '{$id}' ");
		}
		else
		{
			$DB->query("UPDATE exp_members SET sig_img_filename = '{$new_filename}', sig_img_width='{$width}', sig_img_height='{$height}' WHERE member_id = '{$id}' ");
		}
        
        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/
        
		return $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id.AMP.'U=1');
	}
	/* END */
	
	
	
    /** ----------------------------------------
    /**  Image Resizing
    /** ----------------------------------------*/
	
	function _image_resize($filename, $type = 'avatar', $axis = 'width')
	{
		global $PREFS;
		
		if ( ! class_exists('Image_lib'))
		{
			require PATH_CORE.'core.image_lib'.EXT;
		}
		
		$IM = new Image_lib();
		
		if ($type == 'avatar')
		{
			$max_width	= ($PREFS->ini('avatar_max_width') == '' OR $PREFS->ini('avatar_max_width') == 0) ? 100 : $PREFS->ini('avatar_max_width');
			$max_height	= ($PREFS->ini('avatar_max_height') == '' OR $PREFS->ini('avatar_max_height') == 0) ? 100 : $PREFS->ini('avatar_max_height');	
			$image_path = $PREFS->ini('avatar_path', TRUE).'uploads/';
		}
		else
		{
			$max_width	= ($PREFS->ini('photo_max_width') == '' OR $PREFS->ini('photo_max_width') == 0) ? 100 : $PREFS->ini('photo_max_width');
			$max_height	= ($PREFS->ini('photo_max_height') == '' OR $PREFS->ini('photo_max_height') == 0) ? 100 : $PREFS->ini('photo_max_height');	
			$image_path = $PREFS->ini('photo_path', TRUE);		
		}

		$res = $IM->set_properties(			
									array(
											'resize_protocol'	=> $PREFS->ini('image_resize_protocol'),
											'libpath'			=> $PREFS->ini('image_library_path'),
											'maintain_ratio'	=> TRUE,
											'master_dim'		=> $axis,
											'file_path'			=> $image_path,
											'file_name'			=> $filename,
											'quality'			=> 75,
											'dst_width'			=> $max_width,
											'dst_height'		=> $max_height
											)
									);
		if ( ! $IM->image_resize())
		{
			return FALSE;
		}
	
		return TRUE;
	}
	/* END */
	

    /** -----------------------------------
    /**  Notepad form
    /** -----------------------------------*/

    function notepad()
    {
        global $IN, $DB, $DSP, $SESS, $LANG;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        $title = $LANG->line('notepad');

		if ($SESS->userdata['group_id'] != 1)
		{
			if ($id != $SESS->userdata('member_id'))
			{
				return $this->account_wrapper($title, $title, $LANG->line('only_self_notpad_access'));
			}
		}
        
        $query = $DB->query("SELECT notepad, notepad_size FROM exp_members WHERE member_id = '$id'");
    
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=notepad_update')).
              $DSP->input_hidden('id', $id);
              
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('notepad_updated')));
        }
              
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
		
		$r .= $title;
              
        $r .= $DSP->td_c().
              $DSP->tr_c();
					
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
		$r .= $LANG->line('notepad_blurb');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '100%', '5');
		$r .= $DSP->input_textarea('notepad', $query->row['notepad'], $query->row['notepad_size'], 'textarea', '100%');
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $LANG->line('notepad_size')), '20%');
		$r .= $DSP->table_qcell('tableCellOne', $DSP->input_text('notepad_size', $query->row['notepad_size'], '4', '2', 'input', '40px'), '80%');
		$r .= $DSP->tr_c();
			
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
			
        $r .= $DSP->table_c(); 

        $r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */


    /** ----------------------------------
    /**  Update notepad
    /** ----------------------------------*/
    
    function notepad_update()
    {  
        global $FNS, $DB, $SESS;

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
	
		if ($SESS->userdata['group_id'] != 1)
		{
			if ($id != $SESS->userdata('member_id'))
			{
				return false;
			}
		}
       
		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        $notepad_size = ( ! is_numeric($_POST['notepad_size'])) ? 18 : $_POST['notepad_size'];

        $DB->query("UPDATE exp_members SET notepad = '".$DB->escape_str($_POST['notepad'])."', notepad_size = '".$notepad_size."' WHERE member_id ='".$id."'");
        
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=notepad'.AMP.'id='.$id.AMP.'U=1');
        exit;    
    }
    /* END */



    /** -----------------------------------
    /**  Administrative options
    /** -----------------------------------*/

    function administrative_options()
    {
        global $IN, $DB, $DSP, $FNS, $SESS, $LANG, $PREFS;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
        
        $title = $LANG->line('administrative_options');
    
        $query = $DB->query("SELECT ip_address, in_authorlist, group_id, localization_is_site_default FROM exp_members WHERE member_id = '$id'");

        foreach ($query->row as $key => $val)
        {
            $$key = $val;
        }
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=administration_update')).    
              $DSP->input_hidden('id', $id);
              
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('administrative_options_updated')));
        }
              
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
		
		$r .= $title;
              
        $r .= $DSP->td_c().
              $DSP->tr_c();
              		              
              
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
            
				$r .= $DSP->tr();
				$r .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line('member_group_assignment')).$DSP->qdiv('itemWrapper', $DSP->qdiv('alert', $LANG->line('member_group_warning'))), '50%');
        
        			$menu = $DSP->input_select_header('group_id');
							
				foreach ($query->result as $row)
				{					
					// If the current user is not a Super Admin
					// we'll limit the member groups in the list
					
					if ($SESS->userdata['group_id'] != 1)
					{
						if ($row['group_id'] == 1)
						{
							continue;
						}
					}                 
	
					$menu .= $DSP->input_select_option($row['group_id'], $row['group_title'], ($row['group_id'] == $group_id) ? 1 : '');
				}
				
				$menu .= $DSP->input_select_footer();
	
				$r .= $DSP->table_qcell('tableCellOne', $menu, '80%');
				$r .= $DSP->tr_c();
            	
			}
        }
	
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
		$r .= $DSP->input_checkbox('in_authorlist', 'y', ($in_authorlist == 'y') ? 1 : '').NBS.$DSP->qspan('defaultBold', $LANG->line('include_in_multiauthor_list'));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '100%', '2');
		$r .= $DSP->input_checkbox('localization_is_site_default', 'y', ($localization_is_site_default == 'y') ? 1 : '').NBS.$DSP->qspan('defaultBold', $LANG->line('localization_is_site_default'));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellTwo', '', '2');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('update')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
	
        $r .= $DSP->table_c();         
        $r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */



    /** -----------------------------------
    /**  Update administrative options
    /** -----------------------------------*/

    function administration_update()
    {
        global $IN, $DB, $DSP, $FNS, $SESS, $LANG, $PREFS, $REGX;
        
        if ( ! $DSP->allowed_group('can_admin_members'))
        {
            return $DSP->no_access_message();
        }
                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
                        
		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        $data['in_authorlist'] = ($IN->GBL('in_authorlist', 'POST') == 'y') ? 'y' : 'n';
        $data['localization_is_site_default'] = ($IN->GBL('localization_is_site_default', 'POST') == 'y') ? 'y' : 'n';
        
        
        if ($IN->GBL('group_id', 'POST'))
        {        
            if ( ! $DSP->allowed_group('can_admin_mbr_groups'))
            {
                return $DSP->no_access_message();
            } 
            
            $data['group_id'] = $_POST['group_id'];
            
            
            if ($_POST['group_id'] == '1')
            {
            	if ($SESS->userdata['group_id'] != '1')
            	{
                	return $DSP->no_access_message();
            	}
            }
			else
			{
				if ($SESS->userdata('member_id') == $id)
				{
            		return $DSP->error_message($LANG->line('super_admin_demotion_alert'));
				}
			}
        } 
        
        $DB->query("UPDATE exp_members set localization_is_site_default = 'n' WHERE localization_is_site_default = 'y'");
        
        $DB->query($DB->update_string('exp_members', $data, "member_id = '$id'"));  
        
        $query = $DB->query("SELECT timezone, daylight_savings FROM exp_members WHERE localization_is_site_default = 'y'");
        
        if ($query->num_rows == 1)
        {
        	$config = array(
        					'default_site_timezone' => $query->row['timezone'],
        					'default_site_dst'		=> $query->row['daylight_savings']
        					);        	
        }
        else
        {
        	$config = array('default_site_timezone' => '', 'default_site_dst'		=> 'n');        	        
        }
        
        //  Update Config Values
        
        $query = $DB->query("SELECT site_system_preferences FROM exp_sites WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
        				
		$prefs = $REGX->array_stripslashes(unserialize($query->row['site_system_preferences']));
											
		foreach($config as $key => $value)
		{
			$prefs[$key] = str_replace('\\', '\\\\', $value);
		}
		
		$DB->query($DB->update_string('exp_sites', 
									  array('site_system_preferences' => addslashes(serialize($prefs))),
									  "site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'"));     
                
        $FNS->redirect(BASE.AMP.'C=myaccount'.AMP.'M=administration'.AMP.'id='.$id.AMP.'U=1');
        exit;    
    }
    /* END */
    
    
    
    /** -----------------------------------------------------------
    /**  Quick links
    /** -----------------------------------------------------------*/

    function quick_links_form()
    { 
        global $IN, $DSP, $REGX, $LANG, $SESS, $DB;
                                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		if ($SESS->userdata['group_id'] != 1)
		{
			if ($id != $SESS->userdata('member_id'))
			{
				return $this->account_wrapper($LANG->line('quick_links'), $LANG->line('quick_links'), $LANG->line('only_self_qucklink_access'));
			}
        }
        
        $r = '';
        
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('quicklinks_updated')));
        }
                
        $r .= $DSP->qdiv('tableHeading', $LANG->line('quick_links'));
                
        $r .= $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=quicklinks_update')).        
              $DSP->input_hidden('id', $id);
        
        $r .= $DSP->table('tableBorder', '0', '', '100%');

		$r .= $DSP->tr()
			 .$DSP->td('tableCellOne', '', 3)
             .$LANG->line('quick_link_description').NBS.NBS.$LANG->line('quick_link_description_more')
        	 .$DSP->td_c()
        	 .$DSP->tr_c();  

		$r .= $DSP->tr().
              $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('link_title'))).
              $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('link_url'))).
              $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('link_order'))).
              $DSP->tr_c();    
                      
        $query = $DB->query("SELECT quick_links FROM exp_members WHERE member_id = '$id'");
         
        $i = 0;

        if ($query->row['quick_links'] != '')
        {             
            foreach (explode("\n", $query->row['quick_links']) as $row)
            {      
                $style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               
                
                $x = explode('|', $row);
                
                $title = (isset($x['0'])) ? $x['0'] : '';
                $link  = (isset($x['1'])) ? $x['1'] : '';
                $order = (isset($x['2'])) ? $x['2'] : $i;
                
                
                $r .= $DSP->tr().
                      $DSP->table_qcell($style, $DSP->input_text('title_'.$i, $title, '20', '40', 'input', '100%'), '40%').
                      $DSP->table_qcell($style, $DSP->input_text('link_'.$i,   $link, '20', '120', 'input', '100%'), '55%').
                      $DSP->table_qcell($style, $DSP->input_text('order_'.$i, $order, '2', '3', 'input', '30px'), '5%').
                      $DSP->tr_c();
            }
        }            
        
		$style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne'; $i++;                               

        $r .= $DSP->tr().
              $DSP->table_qcell($style, $DSP->input_text('title_'.$i,  '', '20', '40', 'input', '100%'), '40%').
              $DSP->table_qcell($style, $DSP->input_text('link_'.$i,  'http://', '20', '120', 'input', '100%'), '60%').
              $DSP->table_qcell($style, $DSP->input_text('order_'.$i, $i, '2', '3', 'input', '30px'), '5%').
              $DSP->tr_c();
              
              
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '3');
		$r .= $DSP->qdiv('bigPad', $DSP->qspan('highlight', $LANG->line('quicklinks_delete_instructions')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
              
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$r .= $DSP->tr();
		$r .= $DSP->td($style, '', '3');
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('submit')));
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();
              
        $r .= $DSP->table_c();
        $r .= $DSP->form_close();
        
        return $this->account_wrapper($LANG->line('quick_links'), $LANG->line('quick_links'), $r);
    }
    /* END */
    
    
      
    /** -----------------------------------------
    /**  Save quick links (or Tabs)
    /** -----------------------------------------*/
        
    function quick_links_update($tabs = FALSE)
    {
        global $IN, $FNS, $LANG, $DB, $DSP, $SESS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		if ($SESS->userdata['group_id'] != 1)
		{
			if ($id != $SESS->userdata('member_id'))
			{
				return false;
			}
		}
		
		// validate for unallowed blank values
		if (empty($_POST)) {
			return $DSP->no_access_message();
		}

        
        $safety = array();
        $dups	= FALSE;
        
        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'title_') AND $val != '')
            {                
                $i = $_POST['order_'.substr($key, 6)];
                                
                if ($i == '' || $i == 0)
                	$_POST['order_'.substr($key, 6)] = 1;
                	
                	                                
                if ( ! isset($safety[$i]))
                {
                	$safety[$i] = true;
                }
                else
                {
					$dups = TRUE;
                }            
			}
		}
		
		if ($dups)
		{
			$i = 1;
		
			foreach ($_POST as $key => $val)
			{
				if (strstr($key, 'title_') AND $val != '')
				{                
					$_POST['order_'.substr($key, 6)] = $i;

					$i++;
				}
			}		
		}
		
		

		// Compile the data

        $data = array();
        
        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'title_') AND $val != '')
            {
                $n = substr($key, 6);
                
                $i = $_POST['order_'.$n];
                
                $data[$i] = $i.'|'.$_POST['title_'.$n].'|'.$_POST['link_'.$n].'|'.$_POST['order_'.$n]."\n";
            }
        }
                           
        sort($data, SORT_NUMERIC);
                        
        $str = '';
        
        foreach ($data as $key => $val)
        {
            $str .= substr(strstr($val, '|'), 1);
        }
        
        if ($tabs == FALSE)
        {
        	$sql = "UPDATE exp_members SET quick_links = '".trim($str)."' WHERE member_id = '$id'";
        	$url = BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$id.AMP.'U=1';
		}
		else
		{
        	$sql = "UPDATE exp_members SET quick_tabs = '".trim($str)."' WHERE member_id = '$id'";
        	$url = BASE.AMP.'C=myaccount'.AMP.'M=tab_manager'.AMP.'id='.$id.AMP.'U=1';
		}
		
		$DB->query($sql);
        $FNS->redirect($url);
        exit;    
    }
    /* END */



    
    /** -------------------------------------------
    /**  Tab Manager
    /** -------------------------------------------*/

    function tab_manager()
    { 
        global $IN, $DSP, $REGX, $LANG, $SESS, $DB;
                                
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

		if ($SESS->userdata['group_id'] != 1)
		{
			if ($id != $SESS->userdata('member_id'))
			{
				return $this->account_wrapper($LANG->line('tab_manager'), $LANG->line('tab_manager'), $LANG->line('only_self_tab_manager_access'));
			}
        }
        
		/** -------------------------------------------
		/**  Build the rows of previously saved links
		/** -------------------------------------------*/

        $query = $DB->query("SELECT quick_tabs FROM exp_members WHERE member_id = '$id'");
         
        $i = 0;
        $total_tabs = 0;
        $hidden		= '';
		$current	= '';
        if ($query->row['quick_tabs'] == '')
        {
        	$tabs_exist = FALSE;
        }
        else
        {
        	$tabs_exist = TRUE;
        
        	$xtabs = explode("\n", $query->row['quick_tabs']);
        
        	$total_tabs = count($xtabs);
        
            foreach ($xtabs as $row)
            {      
                $style = ($i % 2) ? 'tableCellTwo' : 'tableCellOne';                               
                
                $x = explode('|', $row);
                
                $title = (isset($x['0'])) ? $x['0'] : '';
                $link  = (isset($x['1'])) ? $x['1'] : '';
                $order = (isset($x['2'])) ? $x['2'] : $i;
                
                $i++;
                
				if ($IN->GBL('link') == '')
				{
					$current .=	$DSP->tr();
								
					$current .= $DSP->table_qcell($style, $DSP->input_text('title_'.$i, $title, '20', '40', 'input', '95%'), '70%');
					$current .= $DSP->table_qcell($style, $DSP->input_text('order_'.$i, $order, '2', '3', 'input', '30px'), '30%');
				
					$current .= $DSP->tr_c();
				}
				else
				{
					$hidden .= $DSP->input_hidden('title_'.$i, $title);
					$hidden .= $DSP->input_hidden('order_'.$i, $order);
				}
			
				if ($total_tabs <= 1 AND $IN->GBL('link') != '')
				{
					$hidden .= $DSP->input_hidden('order_'.$i, $order);
				}
				
				$hidden .= $DSP->input_hidden('link_'.$i, $link);
            }
        }  

		/** -------------------------------------------
		/**  Type of request
		/** -------------------------------------------*/
	
		$new_link = ($IN->GBL('link') == '') ? FALSE : TRUE;
	
		/** -------------------------------------------
		/**  Create the output
		/** -------------------------------------------*/
                
        $r = '';
        
        if ($IN->GBL('U'))
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('tab_manager_updated')));
        }
                
        $r .= $DSP->qdiv('tableHeading', $LANG->line('tab_manager'));
        $r .= $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=tab_manager_update')).        
              $DSP->input_hidden('id', $id).
              $hidden;
        
        $r .= $DSP->table('tableBorder', '0', '', '100%');

		$r .= $DSP->tr()
			 .$DSP->td('tableCellOne', '', 3)
             .$DSP->qdiv('itemWrapper', $LANG->line('tab_manager_description'))
        	 .$DSP->td_c()
        	 .$DSP->tr_c(); 
        	 
		if ($new_link == FALSE)
		{
			$r .= 
				  $DSP->tr()
				 .$DSP->td('tableCellOne', '', 3)
				 .$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('tab_manager_instructions')))             
				 .$DSP->qdiv('itemWrapper', $DSP->qdiv('highlight_alt', $LANG->line('tab_manager_description_more')));
		} 
		
		if ($tabs_exist == TRUE AND $new_link == FALSE)
		{
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('quicklinks_delete_instructions')));
		}	 
		
		$r .= $DSP->td_c().$DSP->tr_c(); 
        	 
        
		if ($new_link == FALSE)
		{
			if ($tabs_exist == TRUE)
			{
				$r .= $DSP->tr().
					  $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('tab_title'))).
					  $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('tab_order')));
				
				$r .= $DSP->tr_c();    
					  
				$r .= $current;
			}
        }
        else
		{
			$r .= $DSP->table_c();  
			$r .= $DSP->qdiv('defaultSmall', NBS);
			
			$i++;
			
			$r .= $DSP->input_hidden('order_'.$i, $i);

			$r .= $DSP->table('tableBorder', '0', '', '100%');
			$r .=	$DSP->tr().
					$DSP->td('tableHeading', '', 2).$LANG->line('tab_manager_create_new').$DSP->td_c().
				 	$DSP->tr_c();    
		
			$r .= $DSP->tr().
				  $DSP->table_qcell('tableCellTwo', $DSP->qdiv('defaultBold', $LANG->line('new_tab_title'))).
				  $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', $LANG->line('new_tab_url')).NBS.$DSP->qspan('default', $LANG->line('can_not_edit'))).
				  $DSP->tr_c();    
		  
			$newlink = ($IN->GBL('link') != '') ? $IN->GBL('link') : '';
			
			$newlink = str_replace('--', '=', $newlink);
			$newlink = str_replace('/', '&', $newlink);
			
			$linktitle = ($IN->GBL('linkt') != '') ? $REGX->xss_clean(base64_decode($IN->GBL('linkt'))) : '';
			
			// $linktitle = $LANG->line('tab_manager_newlink_title');
			
			$r .= $DSP->tr().
				  $DSP->table_qcell('tableCellOne', $DSP->input_text('title_'.$i, $linktitle, '20', '40', 'input', '100%'), '40%').
				  $DSP->table_qcell('tableCellOne', $DSP->input_text('link_'.$i,  $newlink, '20', '120', 'input', '100%', 'readonly'), '60%').
				  $DSP->tr_c();  			
		}
       
		if ($new_link == TRUE OR $tabs_exist == TRUE)
		{
			$r .= $DSP->tr();
			$r .= $DSP->td('tableCellTwo', '', '2');
			$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit(($new_link == FALSE) ? $LANG->line('update') : $LANG->line('tab_manager_newlink')));
			$r .= $DSP->td_c();
			$r .= $DSP->tr_c();
   		}
   
		$r .= $DSP->table_c();  
		$r .= $DSP->form_close();
        
        return $this->account_wrapper($LANG->line('tab_manager'), $LANG->line('tab_manager'), $r);
    }
    /* END */
    



  

    /** -----------------------------------
    /**  Bookmarklet Form
    /** -----------------------------------*/
    
    function bookmarklet()
    {  
        global $DSP, $DB, $SESS, $FNS, $LANG;

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
        
        $title = $LANG->line('bookmarklet');
        
        if (sizeof($SESS->userdata['assigned_weblogs']) == 0)
        {
            return $DSP->no_access_message($LANG->line('no_blogs_assigned_to_user'));
        }
                
        // Build the output
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=bookmarklet_fields')).
              $DSP->input_hidden('id', $id);

        $r .= $DSP->div('tableBorder');
        $r .= $DSP->qdiv('tableHeading', $title);

        $r .= $DSP->div('tableCellOne');
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('bookmarklet_info'));
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('bookmarklet_name')).
              $DSP->qdiv('itemWrapper', $LANG->line('single_word_no_spaces')).
              $DSP->input_text('bm_name', $LANG->line('bookmarklet'), '35', '50', 'input', '300px').
              $DSP->div_c();
              
        $r .= $DSP->div_c();

        $r .= $DSP->div('tableCellTwo').
        	  $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('weblog_name'));
                            
              $r .= $DSP->input_select_header('weblog_id');
            
            foreach ($SESS->userdata['assigned_weblogs'] as $weblog_id => $weblog_title)
            {
                $r .= $DSP->input_select_option($weblog_id, $weblog_title, '');
            }
            
              $r .= $DSP->input_select_footer();

        $r .= $DSP->div_c();
        $r .= $DSP->div_c();
        
        // Submit button  
        
        $r .= $DSP->div('tableCellOne');
        $r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('bookmarklet_next_step')));
        $r .= $DSP->div_c();

        $r .= $DSP->div_c();
        $r.=  $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */

   
    /** -----------------------------------
    /**  Bookmarklet Form - setp two
    /** -----------------------------------*/
    
    function bookmarklet_fields()
    {  
        global $DSP, $DB, $SESS, $FNS, $LANG;

        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
   
        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
        
        if ( ! is_numeric($_POST['weblog_id']))
        	return FALSE;

        $title = $LANG->line('bookmarklet');
        
        $bm_name = strip_tags($_POST['bm_name']);
        $bm_name = preg_replace("/[\'\"\?\/\.\,\|\$\#\+]/", "", $bm_name);
        $bm_name = preg_replace("/\s+/", "_", $bm_name);
        $bm_name = stripslashes($bm_name);
        
        $query = $DB->query("SELECT field_group FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str($_POST['weblog_id'])."'");

        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message($LANG->line('no_fields_assigned_to_blog'));
        }

        $field_group = $query->row['field_group'];

        $query = $DB->query("SELECT field_id, field_label FROM  exp_weblog_fields WHERE group_id = '$field_group' ORDER BY field_order");
        
        if ($query->num_rows == 0)
        {
            return $DSP->no_access_message($LANG->line('no_blogs_assigned_to_user'));
        }
                
        // Build the output
        
        $r  = $DSP->form_open(array('action' => 'C=myaccount'.AMP.'M=create_bookmarklet'))
             .$DSP->input_hidden('id', $id)
             .$DSP->input_hidden('bm_name',   $bm_name)
             .$DSP->input_hidden('weblog_id', $_POST['weblog_id']);
        
        $r .= $DSP->div('tableBorder');
        $r .= $DSP->qdiv('tableHeading', $title);
                          
        $r .= $DSP->div('tableCellOne').
			  $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $DSP->qdiv('itemTitle', $LANG->line('select_field')));
                            
		$r .= $DSP->input_select_header('field_id');
            
            foreach ($query->result as $row)
            {
                $r .= $DSP->input_select_option('field_id_'.$row['field_id'], $row['field_label'], '');
            }
            
        $r .= $DSP->input_select_footer();
        $r .= $DSP->div_c();
        $r .= $DSP->div_c();

        // Submit button   
        
        $r .= $DSP->div('tableCellOne');
        $r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('safari', 'y', '').' '.$LANG->line('safari_users'));
		$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit($LANG->line('create_the_bookmarklet')));        
        $r .= $DSP->div_c();

        $r .= $DSP->div_c();
        $r.=  $DSP->form_close();
        
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */
   

    /** -----------------------------------
    /**  Create Bookmarklet
    /** -----------------------------------*/
    
    function create_bookmarklet()
    {
        global $LANG, $DSP, $FNS, $PREFS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }

        // Is the user authorized to access the publish page?
        // And does the user have at least one blog assigned?
        // If not, show the no access message

        if ( ! $DSP->allowed_group('can_access_publish') || ! count($FNS->fetch_assigned_weblogs()) > 0)
        {
            return $DSP->no_access_message();
        }
        
        $title = $LANG->line('bookmarklet');
        
        $bm_name   = $_POST['bm_name'];
        $weblog_id = $_POST['weblog_id'];
        $field_id  = $_POST['field_id'];
        
        $safari = (isset($_POST['safari'])) ? TRUE : FALSE;
        
        $path = $PREFS->ini('cp_url').'?C=publish'.AMP.'Z=1'.AMP.'BK=1'.AMP.'weblog_id='.$weblog_id.AMP;
        
        $type = ($safari) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";   
                        
		$r  = $DSP->div('tableBorder').
			  $DSP->qdiv('tableHeading', $title).
			  $DSP->div('tableCellOne').
        	  $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('bookmarklet_created'))).
              $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', $LANG->line('bookmarklet_instructions').NBS.NBS.$DSP->qspan('defaultBold', "<a href=\"javascript:bm=$type;void(bmentry=window.open('".$path."title='+encodeURI(document.title)+'&tb_url='+encodeURI(window.location.href)+'&".$field_id."='+encodeURI(bm),'bmentry','')) \">$bm_name</a>")).
              $DSP->div_c().
              $DSP->div_c().
              $DSP->div_c();
                
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */
    
    
    /** -----------------------------------
    /**  Private Messages Manager
    /** -----------------------------------*/
    
    function messages()
    {
    	global $SESS, $IN;
    	
    	$id = ( ! $IN->GBL('id', 'GP')) ? $SESS->userdata['member_id'] : $IN->GBL('id', 'GP');
    	
    	if ($id != $SESS->userdata['member_id'])
        {
        	return false;
        }
        
        if ( ! class_exists('Messages'))
		{
			require PATH_CORE.'core.messages'.EXT;
		}
		
		$MESS = new Messages;
		$MESS->manager();
		
		// If both the title and the crumb variables are empty, then
		// we have something that does not need to be put in the member
		// wrapper, like a popup.  So, we just return the return_date
		// variable and be done with it.
		
		if ($MESS->title != '' && $MESS->crumb != '')
		{
			return $this->account_wrapper($MESS->title, $MESS->crumb, $MESS->return_data);
		}
		
		return $MESS->return_data;
    }
    /* END */
    
	
	/** -------------------------------------
	/**  Ignore List
	/** -------------------------------------*/
	
	function ignore_list()
	{
	    global $IN, $DB, $DSP, $SESS, $LANG, $PREFS;
        
        if (FALSE === ($id = $this->auth_id()))
        {
            return $DSP->no_access_message();
        }
		
		/** -------------------------------------
		/**  Save any incoming data
		/** -------------------------------------*/
		
		if ($action = $IN->GBL('daction', 'POST'))
		{
			$query = $DB->query("SELECT ignore_list FROM exp_members WHERE member_id = '{$id}'");

			$ignored = ($query->row['ignore_list'] == '') ? array() : array_flip(explode('|', $query->row['ignore_list']));
			
			if ($action == 'delete')
			{
				if ( ! ($member_ids = $IN->GBL('toggle', 'POST')))
				{
					return $DSP->no_access_message();
				}

				foreach ($member_ids as $member_id)
				{
					unset($ignored[$member_id]);
				}
			}
			else
			{
				if ( ! ($screen_name = $IN->GBL('name', 'POST')))
				{
					return $DSP->no_access_message();
				}

				$query = $DB->query("SELECT member_id FROM exp_members WHERE screen_name = '".$DB->escape_str($screen_name)."'");

				if ($query->num_rows == 0)
				{
					return $DSP->error_message($LANG->line('invalid_screen_name_message'));
				}

				if ($query->row['member_id'] == $id)
				{
					return $DSP->error_message($LANG->line('can_not_ignore_self'));
				}

				if ( ! isset($ignored[$query->row['member_id']]))
				{
					$ignored[$query->row['member_id']] = $query->row['member_id'];
				}
			}
			
			$ignored_list = implode('|', array_keys($ignored));

			$DB->query($DB->update_string('exp_members', array('ignore_list' => $ignored_list), "member_id = '{$id}'"));
		}

        $title = $LANG->line('ignore_list');

		$query = $DB->query("SELECT ignore_list FROM exp_members WHERE member_id = '{$id}'");
        
		$ignored = ($query->row['ignore_list'] == '') ? array() : explode('|', $query->row['ignore_list']);
		
		$query = $DB->query("SELECT screen_name, member_id FROM exp_members WHERE member_id IN ('".implode("', '", $ignored)."') ORDER BY screen_name");
		
		$r = "<form method='post' action='".BASE.AMP."C=myaccount".AMP."M=ignore_list".AMP."id={$id}' name='target' id='target' >\n";
		
        $r .= $DSP->input_hidden('id', $id).
			  $DSP->input_hidden('name', '').
			  $DSP->input_hidden('daction', '').
			  $DSP->input_hidden('toggle[]', '');

		if ($action)
        {
        	$r .= $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.$LANG->line('ignore_list_updated')));
        }
              
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
              $DSP->tr().
              $DSP->td('tableHeading', '', '2');
		$r .= $DSP->anchorpop(BASE.AMP.'C=myaccount'.AMP.'M=member_search'.AMP.'Z=1',
									"<img src='".PATH_CP_IMG."search_glass.gif' style='border: 0;' width='12' height='12' alt='".$LANG->line('search_glass')."' />".NBS.NBS
									).$title;
        $r .= $DSP->td_c().
              $DSP->tr_c();
                          					
		/** -------------------------------------
		/**  Javascript for Toggle
		/** -------------------------------------*/
		
		$r .= <<<EOT

			<script type="text/javascript"> 
			//<![CDATA[

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

				if (document.target)
				{
					var theForm = document.target;
				}
				else if (document.getElementById('target'))
				{
					var theForm = document.getElementById('target');
				}
				else
				{
					return false;
				}

				var len = theForm.elements.length;

				for (var i = 0; i < len; i++) 
				{
					var button = theForm.elements[i];

					var name_array = button.name.split("["); 

					if (name_array[0] == "toggle") 
					{
						button.checked = val;
					}
				}

				theForm.toggleflag.checked = val;
			}
			//]]>
			</script>

EOT;
		
		$r .= $DSP->tr();
		$r .= $DSP->td('tableCellOne', '80%', '', '', 'top');
		$r .= $DSP->qdiv('defaultLeft', $DSP->qdiv('defaultBold', $LANG->line('mbr_screen_name')));
		$r .= $DSP->td_c();
		$r .= $DSP->td('tableCellOne', '5%');
		$r .= $DSP->input_checkbox('toggleflag', '', '', "onclick='toggle(this);'");
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();	
		
		if ($query->num_rows == 0)
		{
			// no members ignored
		}
		else
		{
			$i = 0;
			foreach ($query->result as $row)
			{
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				$r .= $DSP->tr();
				$r .= $DSP->td($style);
				$r .= $DSP->anchor(BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id'] , $row['screen_name']);
				$r .= $DSP->td_c();
				$r .= $DSP->td($style).$DSP->input_checkbox('toggle[]', $row['member_id']).$DSP->td_c();
				$r .= $DSP->tr_c();
			}
		}
		
		/** -------------------------------------
		/**  Javascript for Add / Delete Buttons
		/** -------------------------------------*/
		
		$r .= <<<EOF
		<script type="text/javascript"> 
		//<![CDATA[

		function list_addition(member, el)
		{
			var member_text = 'Member Screen Name';

			var Name = (member == null) ? prompt(member_text, '') : member;
			var el = (el == null) ? 'name' : el;

		     if ( ! Name || Name == null)
		     {
		     	return; 
		     }            

			var frm = document.getElementById('target');
			var x;

			for (i = 0; i < frm.length; i++)
			{
				if (frm.elements[i].name == el)
				{
					frm.elements[i].value = Name;
				}
			}

		     document.getElementById('target').submit();
		}

		function dynamic_action(which)
		{
			if (document.getElementById('target').daction)
			{
				document.getElementById('target').daction.value = which;
			}
		}
		//]]>
		</script>
EOF;

		$buttons = "<button type='submit' id='add' name='add' value='add' class='buttons' ".
					"title='".$LANG->line('add_member')."' onclick='dynamic_action(\"add\");list_addition();return false;'>".
					$LANG->line('add_member')."</button>".NBS.NBS.
					"<button type='submit' id='delete' name='delete' value='delete' class='buttons' ".
					"title='".$LANG->line('delete_selected_members')."' onclick='dynamic_action(\"delete\");'>".$LANG->line('delete_member')."</button> &nbsp;&nbsp;";
					
        $r .= $DSP->table_c(); 
		$r .= $DSP->qdiv('defaultRight', $buttons);
		$r .= $DSP->form_close();
    
        return $this->account_wrapper($title, $title, $r);
    }
    /* END */
    

	/** -------------------------------------
	/**  Member Mini Search (Ignore List)
	/** -------------------------------------*/
	
	function member_search($msg = '')
	{
		global $DB, $DSP, $LANG, $PREFS;

		$DSP->title = $LANG->line('member_search');
		
		$r = $DSP->heading($LANG->line('member_search'));
		
		if ($msg != '')
		{
			$r .= $DSP->qdiv('box', $DSP->qdiv('alert', $msg));
		}
		
		$r .= $DSP->div('box');
		
		$r .= "<form method='post' action='".BASE.AMP."C=myaccount".AMP."M=do_member_search".AMP."Z=1' name='member_search' id='member_search' >\n";
		
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemTitle', "<label for='screen_name'>".$LANG->line('mbr_screen_name')."</label>");
		$r .= $DSP->input_text('screen_name', '', '35');
		$r .= $DSP->div_c();
		
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemTitle', "<label for='screen_name'>".$LANG->line('mbr_email_address')."</label>");
		$r .= $DSP->input_text('email', '', '35');
		$r .= $DSP->div_c();
		
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->qdiv('itemTitle', "<label for='group_id'>".$LANG->line('mbr_member_group')."</label>");
		$r .= $DSP->input_select_header('group_id');
		$r .= $DSP->input_select_option('any', $LANG->line('any'));
		
		$query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY group_title");
		
		foreach ($query->result as $row)
		{
			$r .= $DSP->input_select_option($row['group_id'], $row['group_title']);
		}
    	
		$r .= $DSP->input_select_footer();
		$r .= $DSP->div_c();
		
		$r .= $DSP->div_c();
		$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
		$r .= $DSP->form_close();
		
		return $DSP->body = $r;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Do Member Mini Search (Ignore List)
	/** -------------------------------------*/
	
	function do_member_search()
	{
        global $DB, $DSP, $FNS, $LANG, $PREFS;

       	$redirect_url = BASE.AMP."C=myaccount".AMP."M=member_search".AMP."Z=1";
       
        /** -------------------------------------
        /**  Parse the $_POST data
        /** -------------------------------------*/

        if ($_POST['screen_name'] 	== '' &&
        	$_POST['email'] 		== ''
        	) 
        	{
        		$FNS->redirect($redirect_url);
				exit;    
        	}
        	
        $search_query = array();
        
        foreach ($_POST as $key => $val)
		{
			if ($key == 'XID')
			{
				continue;
			}
			if ($key == 'group_id')
			{
				if ($val != 'any')
				{
					$search_query[] = " group_id ='".$DB->escape_str($_POST['group_id'])."'";
				}
			}
			else
			{
				if ($val != '')
				{
					$search_query[] = $key." LIKE '%".$DB->escape_like_str($val)."%'";
				}
			}
		}
		
		if (count($search_query) < 1)
		{
			$FNS->redirect($redirect_url);
			exit; 
		}
                        
  		$Q = implode(" AND ", $search_query);
                
        $sql = "SELECT DISTINCT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_member_groups 
        		WHERE exp_members.group_id = exp_member_groups.group_id 
        		AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
        		AND ".$Q;                 
        
        $query = $DB->query($sql);
               
        if ($query->num_rows == 0)
        {
            return $this->member_search($LANG->line('no_search_results'));
        }
        
        $r = $DSP->table('tableBorder', '0', '10', '100%');
		$r .= $DSP->tr();
		$r .= $DSP->td('tableHeading').$LANG->line('search_results').$DSP->td_c();
		$r .= $DSP->tr_c();
		
		$i = 0;
        foreach($query->result as $row)
        {
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$item = '<a href="#" onclick="opener.dynamic_action(\'add\');opener.list_addition(\''.$row['screen_name'].'\', \'name\');return false;">'.$row['screen_name'].'</a>';
			$r .= $DSP->tr();	
			$r .= $DSP->td($style).$item.$DSP->td_c();
			$r .= $DSP->tr_c();
        }
        
		$r .= $DSP->table_c();
		$r .= $DSP->qdiv('defaultCenter', $DSP->qdiv('highlight', $LANG->line('insert_member_instructions')));
		$r .= $DSP->div('itemWrapper');
		$r .= $DSP->div('defaultCenter');
		$r .= $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP."C=myaccount".AMP."M=member_search".AMP."Z=1", $LANG->line('new_search')));
		$r .= $DSP->div_c();
		$r .= $DSP->div('defaultCenter');
		$r .= $DSP->qdiv('defaultBold', $DSP->anchor('JavaScript:window.close();', $LANG->line('mbr_close_window')));
		$r .= $DSP->div_c();
		$r .= $DSP->div_c();
		
		return $DSP->body = $r;
	}
	/* END */
    
}
/* END */
?>