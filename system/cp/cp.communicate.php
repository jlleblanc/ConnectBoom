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
 File: cp.communicate.php
-----------------------------------------------------
 Purpose: Email sending/management functions
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Communicate {

    var $wrapchars = 76;
    var $mailinglist_exists = FALSE;
    
    /** -----------------------------
    /**  Constructor
    /** -----------------------------*/

    function Communicate()
    {
        global $DSP, $LANG, $IN, $DB;
        
        if ( ! $DSP->allowed_group('can_access_comm'))
        {
            return $DSP->no_access_message();
        }
        
		if (file_exists(PATH_MOD.'mailinglist/mod.mailinglist'.EXT) && $DB->table_exists('exp_mailing_lists') === TRUE)
		{
			$this->mailinglist_exists = TRUE;
		}
		
        // Fetch the needed language files
        
        $LANG->fetch_language_file('communicate');
        

        switch($IN->GBL('M'))
        {
            case 'send_email' 			: $this->send_email();          
                break;
            case 'batch_send' 			: $this->batch_send();          
                break;
            case 'view_cache' 			: $this->view_email_cache();          
                break;
            case 'view_email' 			: $this->view_email();          
                break;
            case 'delete_conf' 			: $this->delete_confirm();          
                break;
            case 'delete' 				: $this->delete_emails();          
                break;
            case 'spellcheck' 			: $this->spellcheck();          
                break;
            case 'spellcheck_iframe' 	: $this->spellcheck_iframe();          
                break;
            case 'view_pending_emails' 	: $this->view_pending_emails();          
                break;
            default           			: $this->email_form();  
                break;
        }     
    }
    /* END */
    
        
    
    /** -----------------------------
    /**  Email form
    /** -----------------------------*/
    
    function email_form()
    {  
        global $IN, $DSP, $DB, $PREFS, $SESS, $LANG;
        
		/** -----------------------------
		/**  Default form values
		/** -----------------------------*/
		
		$member_groups	= array();
		$mailing_lists	= array();
        
        $default = array(
							'from_name'		=> '',
							'from_email' 	=> $SESS->userdata['email'],
							'recipient'  	=> '',
							'cc'			=> '',
							'bcc'			=> '',
							'subject' 		=> '',
							'message'		=> '',
							'plaintext_alt'	=> '',
							'priority'		=>  3,
							'mailtype'		=> $PREFS->ini('mail_format'),
							'word_wrap'		=> $PREFS->ini('word_wrap')
        				);
        
		/** -----------------------------
		/**  Are we emailing a member?
		/** -----------------------------*/

        if ($IN->GBL('M', 'GET') == 'email_mbr' AND $IN->GBL('mid', 'GET') AND $DSP->allowed_group('can_admin_members'))
        {     			
			$query = $DB->query("SELECT email, screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($IN->GBL('mid', 'GET'))."'");
			
			if ($query->num_rows == 1)
			{
				$default['recipient'] = $query->row['email'];
				$default['message'] = $query->row['screen_name'].",";
			}
        }
        
		/** -----------------------------
		/**  Fetch form data
		/** -----------------------------*/

		// If the user is viewing a cached email, we'll gather the data
		
        if ($id = $IN->GBL('id', 'GET'))
        {     
			if ( ! $DSP->allowed_group('can_send_cached_email'))
			{     
				return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
			}
			
			// Fetch cached data        
           
			$query = $DB->query("SELECT * FROM exp_email_cache WHERE cache_id = '".$DB->escape_str($id)."'");
		
			if ($query->num_rows > 0)
			{
				foreach ($query->row as $key => $val)
				{
					if (isset($default[$key]))
					{
						$default[$key] = $val;
					}
				}
			}
			
			// Fetch member group IDs
			
			$query = $DB->query("SELECT group_id FROM exp_email_cache_mg WHERE cache_id = '".$DB->escape_str($id)."'");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$member_groups[] = $row['group_id'];
				}
        	}

			if ($this->mailinglist_exists == TRUE)
			{
				// Fetch mailing list IDs
				
				$query = $DB->query("SELECT list_id FROM exp_email_cache_ml WHERE cache_id = '".$DB->escape_str($id)."'");
				
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$mailing_lists[] = $row['list_id'];
					}
				}
			}
        }
        
       
		/** -----------------------------------
		/**  Turn default data into variables
		/** -----------------------------------*/
        
        foreach ($default as $key => $val)
        {
        	$$key = $val;
        }

		/** -----------------------------------
		/**  Create the email form
		/** -----------------------------------*/
		
        $DSP->title = $LANG->line('communicate');
        $DSP->crumb	= $LANG->line('communicate');
        
		if ($DSP->allowed_group('can_send_cached_email'))
		{     
			$DSP->right_crumb($LANG->line('view_email_cache'), BASE.AMP.'C=communicate'.AMP.'M=view_cache');
		}

        $r = $DSP->form_open(array('action' => 'C=communicate'.AMP.'M=send_email'));

        $r .= $DSP->qdiv('tableHeading', $LANG->line('send_an_email'));		        
		$r .= $DSP->div('box');
		
        $r .= $DSP->table('', '0', '0', '100%').
              $DSP->tr().
              $DSP->td('', '', '', '', 'top');
                     
            /** -----------------------------
            /**  Subject and message feilds
            /** -----------------------------*/
                      
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $DSP->required().NBS.$LANG->line('subject', 'subject')).
              $DSP->qdiv('', $DSP->input_text('subject', $subject, '20', '75', 'input', '96%')).
              $DSP->div_c();
			           
        if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT;
    	}
    	
    	$SPELL = new Spellcheck();
    	
    	$r .= $SPELL->JavaScript(BASE.AMP.'C=communicate'.AMP.'M=spellcheck', TRUE);
              
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $DSP->required().NBS.$LANG->line('message', 'message')).
              $DSP->qdiv('', $DSP->input_textarea('message', $message, 25, 'textarea', '96%')).
              $DSP->div_c();
              
        $iframe_url = BASE.AMP.'C=communicate'.AMP.'M=spellcheck_iframe';
        $field_name = 'message';
              
        $r .= $DSP->table('tableBorderNoBot', '0', '', '96%');
			
		if ($SPELL->enabled === TRUE)
		{
			$r .= $DSP->tr().
				  $DSP->td('tableCellTwoBold', '100%', 2).
				  $DSP->div('itemWrapper').
				  $DSP->anchor('javascript:nullo();', $LANG->line('check_spelling'), 'onclick="eeSpell.getResults(\''.$field_name.'\');return false;"').
				  '<span id="spellcheck_hidden_'.$field_name.'" style="visibility:hidden;">'.
				  NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS.
				  $DSP->anchor('javascript:nullo();', $LANG->line('save_spellcheck'), 'onclick="SP_saveSpellCheck();return false;"').
				  NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS.
				  $DSP->anchor('javascript:nullo();', $LANG->line('revert_spellcheck'), 'onclick="SP_revertToOriginal();return false;"').
				  '</span>'.
				  $DSP->div_c();
	
			$r .= '<iframe src="'.$iframe_url.'" width="100%" style="display:none;" id="spellcheck_frame_'.$field_name.'" class="iframe" name="spellcheck_frame_'.$field_name.'"></iframe>'.
				  '<div id="spellcheck_popup" class="wordSuggestion" style="position:absolute;visibility:hidden;"></div>';
				  
			$r .= $DSP->td_c().
				  $DSP->tr_c();
    	}			   			         
                                
            /** -----------------------------
            /**  Mail formatting buttons
            /** -----------------------------*/

		$extra_js = <<<EOTJS
			<script type="text/javascript">
				function showhide_plaintext_field(val)
				{
					if (val == 'html')
					{
						document.getElementById('plaintext_field').style.display = 'block';
					}
					else
					{
						document.getElementById('plaintext_field').style.display = 'none';
					}
				}
			</script>
EOTJS;
		
        $r .= $extra_js.
			  $DSP->tr().
			  $DSP->td('', '', 2);
			
        $r .= $DSP->table('', '0', '', '100%').
			  $DSP->tr().
			  $DSP->td('tableCellTwoBold', '40%').$LANG->line('mail_format').$DSP->td_c().
              $DSP->td('tableCellTwoBold', '60%').
              $DSP->input_select_header('mailtype', '', '', '', "onchange='showhide_plaintext_field(this.value);return false'").
      		  $DSP->input_select_option('plain', $LANG->line('plain_text'), ($mailtype == 'plain') ? 1 : '').
        	  $DSP->input_select_option('html',  $LANG->line('html'), ($mailtype == 'html') ? 1 : '').
              $DSP->input_select_footer().
          	  $DSP->td_c().
          	  $DSP->tr_c();

		/** ---------------------------------------
		/**  Alternative content field
		/** ---------------------------------------*/

		$r .= $DSP->tr().
			  $DSP->td('', '', 2).
			  $DSP->div('tableCellTwoBold', '', 'plaintext_field', '', ($mailtype == 'html') ? '' : "style='display:none;'").
              $DSP->qdiv('tableCellTwoBold', $LANG->line('plaintext_alt', 'plaintext_alt')).
              $DSP->qdiv('', $DSP->input_textarea('plaintext_alt', $plaintext_alt, 8, 'textarea', '96%')).              
			  $DSP->td_c().
              $DSP->tr_c().
			  $DSP->table_c();

		/** ---------------------------------------
		/**  Text Formatting
		/** ---------------------------------------*/
			
        $r .= $DSP->tr().
              $DSP->td('tableCellOneBold', '40%').$LANG->line('text_formatting').$DSP->td_c().
              $DSP->td('tableCellOneBold', '60%').
              $DSP->input_select_header('text_fmt').
			  $DSP->input_select_option('none', $LANG->line('none'), 1);

		// Fetch formatting plugins

		$list = $this->fetch_plugins();

		foreach($list as $val)
		{
			$name = ucwords(str_replace('_', ' ', $val));

			if ($name == 'Br')
			{
				$name = $LANG->line('auto_br');
			}
			elseif ($name == 'Xhtml')
			{
				$name = $LANG->line('xhtml');
			}

			$r .= $DSP->input_select_option($val, $name);
		}

		$r .= $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();

        $r .= $DSP->tr().
              $DSP->td('tableCellTwoBold', '40%').$LANG->line('word_wrap').$DSP->td_c().
              $DSP->td('tableCellTwoBold', '60%').
              $DSP->input_select_header('wordwrap').
			  $DSP->input_select_option('y', $LANG->line('on'), ($word_wrap == 'y') ? 1 : '').
			  $DSP->input_select_option('n',  $LANG->line('off'), ($word_wrap == 'n') ? 1 : '').
              $DSP->input_select_footer().
              $DSP->td_c().
              $DSP->tr_c();
              
        $r .= $DSP->tr().
              $DSP->td('tableCellOneBold').$LANG->line('priority').$DSP->td_c().
              $DSP->td('tableCellOneBold').
              $DSP->input_select_header('priority').
              $DSP->input_select_option('1', '1 ('.$LANG->line('highest').')',	($priority == 1) ? 1 : '').
              $DSP->input_select_option('2', '2 ('.$LANG->line('high').')',		($priority == 2) ? 1 : '').
              $DSP->input_select_option('3', '3 ('.$LANG->line('normal').')', 	($priority == 3) ? 1 : '').
              $DSP->input_select_option('4', '4 ('.$LANG->line('low').')',		($priority == 4) ? 1 : '').
              $DSP->input_select_option('5', '5 ('.$LANG->line('lowest').')',	($priority == 5) ? 1 : '').
              $DSP->input_select_footer();
  
        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
       
        /** -----------------------------
        /**  Submit button
        /** -----------------------------*/
            
        if ($DSP->allowed_group('can_email_member_groups'))
        {         
        	$r .= $DSP->qdiv('itemWrapperTop', $DSP->input_checkbox('accept_admin_email', 'y', 1).NBS.$LANG->line('honor_email_pref')); 
		}        
        
		$r .= $DSP->qdiv('itemWrapper', $DSP->required(1));
		$r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('send_it')));


        /** -----------------------------
        /**  Right side of page
        /** -----------------------------*/
              
        $r .= $DSP->td_c().
              $DSP->td('', '300px', '', '', 'top');
                
        /** -----------------------------
        /**  Sender/recipient fields
        /** -----------------------------*/

        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('your_name', 'name')).
              $DSP->qdiv('', $DSP->input_text('name', $from_name, '20', '50', 'input', '300px')).
              $DSP->div_c();
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $DSP->required().NBS.$LANG->line('your_email', 'from')).
              $DSP->qdiv('', $DSP->input_text('from', $from_email, '20', '75', 'input', '300px')).
              $DSP->div_c();
        
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemWrapper', '<b>'.$LANG->line('recipient', 'recipient').'</b>').
              $DSP->qdiv('', $LANG->line('separate_emails_with_comma')).
              $DSP->qdiv('', $DSP->input_text('recipient', $recipient, '20', '150', 'input', '300px')).
              $DSP->div_c();
              
        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('cc', 'cc')).
              $DSP->qdiv('', $DSP->input_text('cc', $cc, '20', '150', 'input', '300px')).
              $DSP->div_c();

        $r .= $DSP->div('itemWrapper').
              $DSP->qdiv('itemTitle', $LANG->line('bcc', 'bcc')).
              $DSP->qdiv('', $DSP->input_text('bcc', $bcc, '20', '150', 'input', '300px').BR).
              $DSP->div_c();    
		  
		if ($DSP->allowed_group('can_email_mailinglist') AND $this->mailinglist_exists == TRUE)	  
		{
            $query = $DB->query("SELECT list_id, list_title FROM exp_mailing_lists ORDER BY list_title");
		
			if ($query->num_rows > 0)
			{
				$r .= $DSP->table('tableBorder', '0', '', '300px').
					  $DSP->tr().
					  $DSP->td('tableHeading').
					  $DSP->qdiv('itemWrapper', $LANG->line('send_to_mailinglist')).
					  $DSP->td_c().
					  $DSP->tr_c();
			
				$i = 0;
				foreach ($query->result as $row)
				{
					$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
					$r .= $DSP->tr().
						  $DSP->td($style, '50%').$DSP->qdiv('defaultBold', $DSP->input_checkbox('list_'.$row['list_id'], $row['list_id'], (in_array($row['list_id'], $mailing_lists)) ? 1 : '').$DSP->nbs(1).$row['list_title']).$DSP->td_c()
						 .$DSP->tr_c();
				}        
			
            	$r .= $DSP->table_c(); 
			}		
		}
		
        /** -----------------------------
        /**  Member group selection
        /** -----------------------------*/
              
        if ($DSP->allowed_group('can_email_member_groups'))
        {         
            $r .= $DSP->table('tableBorder', '0', '', '300px').
                  $DSP->tr().
                  $DSP->td('tableHeading').
                  $DSP->qdiv('itemWrapper', $LANG->line('recipient_group')).
                  $DSP->td_c().
                  $DSP->tr_c();
        
            $i = 0;
            
            $query = $DB->query("SELECT group_id, group_title FROM exp_member_groups 
            					 WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
            					 AND include_in_mailinglists = 'y' ORDER BY group_title");
            
            foreach ($query->result as $row)
            {
                $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
        
                $r .= $DSP->tr().
                      $DSP->td($style, '50%').$DSP->qdiv('defaultBold', $DSP->input_checkbox('group_'.$row['group_id'], $row['group_id'], (in_array($row['group_id'], $member_groups)) ? 1 : '').$DSP->nbs(1).$row['group_title']).$DSP->td_c()
                     .$DSP->tr_c();
            }        
        
            $r .= $DSP->table_c(); 
        }
              
        /** -----------------------------
        /**  Table end
        /** -----------------------------*/
              
        $r .= $DSP->td_c()   
             .$DSP->tr_c()      
             .$DSP->table_c();   
             
       	$r .= $DSP->div_c();       	
                        
        $r.=  $DSP->form_close();        
        
        $DSP->body  = $r;
    }
    /* END */



    /** -----------------------------
    /**  Debugging Message
    /** -----------------------------*/

	function debug_message($debug_array)
	{
		global $DSP;
	
		if ( ! is_array($debug_array) OR count($debug_array) == 0)
		{
			return '';
		}
	
		$str = $DSP->div('box').$DSP->div('defaultPad').$DSP->qdiv('itemWrapper', $DSP->heading('Debugging Message', 5));
	
		foreach ($debug_array as $val)
		{
			$str .= $val;
		}
		
		$str .= BR.$DSP->div_c().$DSP->div_c();

		return $str;	
	}
	/* END */
        


    
    /** -----------------------------
    /**  Send email
    /** -----------------------------*/
    
    function send_email()
    {  
        global $DSP, $DB, $IN, $FNS, $REGX, $LANG, $SESS, $LOC, $PREFS;
        
        $debug_msg = '';
        
        /** -----------------------------
        /**  Are we missing any fields?
        /** -----------------------------*/
        
        if ( ! $IN->GBL('from',		'POST') OR
             ! $IN->GBL('subject',	'POST') OR
             ! $IN->GBL('message',	'POST')
           )
        {
            return $DSP->error_message($LANG->line('empty_form_fields'));
        }
        
        /** -----------------------------
        /**  Fetch $_POST data
        /** -----------------------------*/
        
        // We'll turn the $_POST data into variables for simplicity
        
        $groups = array();
        $list_ids = array();
        
        foreach ($_POST as $key => $val)
        {
            if (substr($key, 0, 6) == 'group_')
            {
                $groups[] = $val;
            }            
            elseif (substr($key, 0, 5) == 'list_')
            {
                $list_ids[] = $val;
            }            
            else
            {            
                $$key = stripslashes($val);
            }
        }
        
        
        /** -----------------------------
        /**  Verify privileges
        /** -----------------------------*/

        if (count($groups) > 0  AND  ! $DSP->allowed_group('can_email_member_groups'))
        {     
            return $DSP->no_access_message($LANG->line('not_allowed_to_email_member_groups'));
        }        
          
        if (count($list_ids) > 0 AND  ! $DSP->allowed_group('can_email_mailinglist') AND $this->mailinglist_exists == TRUE)
        {     
            return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
        }        
                
        if (count($groups) == 0  AND count($list_ids) == 0 AND ! $IN->GBL('recipient', 'POST'))
        {
            return $DSP->error_message($LANG->line('empty_form_fields'));
        }
         
         
        /** -------------------------------
        /**  Assign data for caching
        /** -------------------------------*/

        $cache_data = array(
								'cache_id'      		=> '',
								'cache_date'			=> $LOC->now,
								'total_sent'    	   	=> 0,
								'from_name'     		=> $name,
								'from_email'    		=> $from,
								'recipient'    			=> $recipient,
								'cc'    				=> $cc,
								'bcc'    				=> $bcc,
								'recipient_array'		=> '',
								'subject'       		=> $subject,
								'message'       		=> $message,
								'plaintext_alt'			=> $plaintext_alt,
								'mailtype'      		=> $mailtype,
								'text_fmt'				=> $text_fmt,
								'wordwrap'      		=> $wordwrap,
								'priority'      		=> $priority
						   );
        
		/** ---------------------------------------
		/**  Apply text formatting if necessary
		/** ---------------------------------------*/

        if ($text_fmt != 'none' && $text_fmt != '')
		{
			if ( ! class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}

			$TYPE = new Typography(0); 
			$TYPE->parse_smileys = FALSE;
			
			$subject = $TYPE->filter_censored_words($subject);

			$message = $TYPE->parse_type($message, 
											  array(
													'text_format'   => $text_fmt,
													'html_format'   => 'all',
													'auto_links'    => 'n',
													'allow_img_url' => 'y'
												  )
											);	
		}
		
        /** -----------------------------
        /**  Send a single email
        /** -----------------------------*/
        
        if (count($groups) == 0 AND count($list_ids) == 0 )
        { 
            require PATH_CORE.'core.email'.EXT;
            
			$to = ($recipient == '') ? $SESS->userdata['email'] : $recipient;
                        
            $email = new EEmail;        
            $email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
            $email->mailtype  = $mailtype;
            $email->priority  = $priority;
            $email->from($from, $name);	
            $email->to($to); 
            $email->cc($cc); 
            $email->bcc($bcc); 
            $email->subject($subject);	
            $email->message($message, $plaintext_alt);
                        
            $error = FALSE;
                        
            if ( ! $email->Send())
            {
            	$error = TRUE;
            }

            $debug_msg = $this->debug_message($email->debug_msg);
            
            if ($error == TRUE)
            {
                return $DSP->error_message($LANG->line('error_sending_email').$debug_msg, 0);
            }
       
			/** ---------------------------------
			/**  Save cache data
			/** ---------------------------------*/
   			
   			$cache_data['total_sent'] = $this->fetch_total($to, $cc, $bcc);
   			
   			$this->save_cache_data($cache_data);
   
			/** ---------------------------------
			/**  Show success message
			/** ---------------------------------*/
   
            $DSP->set_return_data($LANG->line('email_sent'), $DSP->qdiv('defaultPad', $DSP->qdiv('success', $LANG->line('email_sent_message'))).$debug_msg, $LANG->line('email_sent'));
   
   			// We're done
   
            return;
        }

        //  Send Multi-emails
        
        
        /** ----------------------------------------
        /**  Is Batch Mode set?
        /** ----------------------------------------*/
        
        $batch_mode = $PREFS->ini('email_batchmode');
        $batch_size = $PREFS->ini('email_batch_size');
        
        if ( ! is_numeric($batch_size))
        {
            $batch_mode = 'n';
        }
                

        $emails = array();
       
        /** ---------------------------------
        /**  Fetch member group emails
        /** ---------------------------------*/

		if (count($groups) > 0)
		{
			$sql = "SELECT exp_members.member_id, exp_members.email, exp_members.screen_name 
					FROM   exp_members, exp_member_groups
					WHERE  exp_members.group_id = exp_member_groups.group_id 
					AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' 
					AND include_in_mailinglists = 'y' ";
	
			if (isset($_POST['accept_admin_email']))
			{
				$sql .= "AND exp_members.accept_admin_email = 'y' ";
			}
			
			$sql .= "AND exp_member_groups.group_id IN (";
			
			foreach ($groups as $id)
			{
				$sql .= "'".$DB->escape_str($id)."',";
			}
	
			$sql = substr($sql, 0, -1);
			
			$sql .= ")";
			
			// Run the query
	
			$query = $DB->query($sql);
						
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					$emails['m'.$row['member_id']] = array($row['email'], $row['screen_name']);					
				}
			}
		}
        
        
        /** ---------------------------------
        /**  Fetch mailing list emails
        /** ---------------------------------*/
        
		$list_templates = array();

		if ($this->mailinglist_exists == TRUE)
		{
			if (count($list_ids) > 0)
			{	
				$sql = "SELECT authcode, email, list_id FROM exp_mailing_list WHERE list_id IN (";
				
				foreach ($list_ids as $id)
				{
					$sql .= "'".$DB->escape_str($id)."',";
					
					// Fetch the template for each list
					
					$query = $DB->query("SELECT list_template, list_title FROM exp_mailing_lists WHERE list_id = '".$DB->escape_str($id)."'");
					$list_templates[$id] = array('list_template' => $query->row['list_template'], 'list_title' => $query->row['list_title']);
				}
		
				$sql = substr($sql, 0, -1);
				$sql .= ")";
			
				$sql .= " ORDER BY user_id";
	
				$query = $DB->query($sql);
				
				// No result?  Show error message
				
				if ($query->num_rows == 0 && sizeof($emails) == 0)
				{
					return $DSP->set_return_data($LANG->line('send_an_email'), $DSP->qdiv('defaultPad', $DSP->qdiv('alert', $LANG->line('no_email_matching_criteria'))), $LANG->line('send_an_email'));
				}
		
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						$emails['l'.$row['authcode']] = array($row['email'], $row['list_id']);
					}
				}
			}
		}
		
        /** ----------------------------------------
        /**  Kill duplicates
        /** ----------------------------------------*/

		$cleaned_emails = array();

        foreach($emails as $key => $value)
        {
			if (is_array($value))
			{
				$val = $value['0'];
			}
			else
			{
				$val = $value;
			}
			
			if ( ! isset($cleaned_emails[$key]))
			{
				$cleaned_emails[$key]  = $value;
			}
        }

        $emails = $cleaned_emails;

        /** ----------------------------------------
        /**  After all that, do we have any emails?
        /** ----------------------------------------*/
			
		if (count($emails) == 0 AND $recipient == '')
		{
			return $DSP->set_return_data($LANG->line('send_an_email'), $DSP->qdiv('defaultPad', $DSP->qdiv('alert', $LANG->line('no_email_matching_criteria'))), $LANG->line('send_an_email'));
		}
		
		
		/** ----------------------------------------
		/**  Do we have any CCs or BCCs?
		/** ----------------------------------------*/
	
		//  If so, we'll send those separately first
		
		$total_sent = 0;
		
		$recips = array();
		
		if ($cc != '' || $bcc != '')
		{				
			if ( ! class_exists('EEmail'))
			{
				require PATH_CORE.'core.email'.EXT;
			}
			
			$to = ($recipient == '') ? $SESS->userdata['email'] : $recipient;
						
			$email = new EEmail;    
			$email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			$email->mailtype  = $mailtype;
			$email->priority  = $priority;
			$email->from($from, $name);	
			$email->to($to); 
			$email->cc($cc); 
			$email->bcc($bcc); 
			$email->subject($subject);	
			$email->message($message, $plaintext_alt);	
			
            $error = FALSE;
            
            if ( ! $email->Send())
            {
            	$error = TRUE;
            }
            
            $debug_msg = $this->debug_message($email->debug_msg);
            
            if ($error == TRUE)
            {
                return $DSP->error_message($LANG->line('error_sending_email').$debug_msg, 0);
            }
			
   			$total_sent = $this->fetch_total($to, $cc, $bcc);
		}
		else
		{
			// No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle
		
			if ($recipient != '')
				$recips = $this->convert_recipients($recipient);
		}
		
		if (count($recips) > 0)
		{
			$emails = array_merge($emails, $recips);
		}
	
		//  Store email cache
		
		$cache_data['recipient_array'] = addslashes(serialize($emails));
		$cache_data['total_sent'] = 0;

		$id = $this->save_cache_data($cache_data, $groups, $list_ids);

        /** ----------------------------------------
        /**  If batch-mode is not set, send emails
        /** ----------------------------------------*/
 
        if (count($emails) <= $batch_size)
        {
            $batch_mode = 'n';
        }
                
        if ($batch_mode == 'n')
        {
 			$action_id  = $FNS->fetch_action_id('Mailinglist', 'unsubscribe');
         
			if ( ! class_exists('EEmail'))
			{
				require PATH_CORE.'core.email'.EXT;
			}
									
			$email = new EEmail;    
			$email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			$email->mailtype  = $mailtype;
			$email->priority  = $priority;
                        
			foreach ($emails as $key => $val)
			{
				$screen_name = '';
				$list_id = FALSE;
			
				if (is_array($val) AND substr($key, 0, 1) == 'm')
				{
					$screen_name = $val['1'];
					$val = $val['0'];
				}
				elseif (is_array($val) AND substr($key, 0, 1) == 'l')
				{
					$list_id = $val['1'];
					$val = $val['0'];
				}

				$email->initialize();
				$email->to($val); 
				$email->from($from, $name);	
				$email->subject($subject);
				
				// We need to add the unsubscribe link to emails - but only ones
				// from the mailing list.  When we gathered the email addresses
				// above, we added one of three prefixes to the array key:
				//
				// m = member id
				// l = mailing list
				// r = general recipient
				
				// Make a copy so we don't mess up the original
				$msg = $message;
				$msg_alt = $plaintext_alt;
												
				if (substr($key, 0, 1) == 'l')
				{
					$msg = $this->parse_template($list_templates[$list_id], $msg, $action_id, substr($key, 1), $mailtype);
					$msg_alt = $this->parse_template($list_templates[$list_id], $msg_alt, $action_id, substr($key, 1), 'plain');
				}

				$msg = str_replace('{name}', $screen_name, $msg);
				$msg_alt = str_replace('{name}', $screen_name, $msg_alt);				
				
				$email->message($msg, $msg_alt);	
				
				$error = FALSE;
				
				if ( ! $email->Send())
				{
					$error = TRUE;
				}
				
				$debug_msg = $this->debug_message($email->debug_msg);
				
				if ($error == TRUE)
				{
					// Let's adjust the recipient array up to this point
					reset($recipient_array);
					$recipient_array = addslashes(serialize(array_slice($recipient_array, $i)));
            	
					$DB->query("UPDATE exp_email_cache SET total_sent = '$total_sent', recipient_array = '$recipient_array' WHERE cache_id = '".$DB->escape_str($id)."'");				
				
					return $DSP->error_message($LANG->line('error_sending_email').$debug_msg, 0);
				}
													
				$total_sent++;
			}
			
			
			/** ----------------------------------------
			/**  Update email cache
			/** ----------------------------------------*/
	
			$DB->query("UPDATE exp_email_cache SET total_sent = '$total_sent', recipient_array = '' WHERE cache_id = '".$DB->escape_str($id)."'");
			
			/** ----------------------------------------
			/**  Success Mesage
			/** ----------------------------------------*/
			$DSP->set_return_data(
								$LANG->line('email_sent'), 
								$DSP->qdiv('defaultPad', $DSP->qdiv('success', $LANG->line('email_sent_message'))).$DSP->qdiv('defaultPad', $DSP->qdiv('', $LANG->line('total_emails_sent').NBS.NBS.$total_sent)).$debug_msg,
								$LANG->line('email_sent')
								);
        
         	// We're done
        
        	return;
        }
               
		
        /** ----------------------------------------
        /**  Start Batch-Mode
        /** ----------------------------------------*/
        
        // Turn on "refresh"
        // By putting the URL in the $DSP->refresh variable we'll tell the
        // system to write a <meta> refresh header, starting the batch process
        
        $DSP->refresh = BASE.AMP.'C=communicate'.AMP.'M=batch_send'.AMP.'id='.$id;
        $DSP->ref_rate = 6;
        
        // Kill the bread-crumb links, just to keep it away from the user
        
        $DSP->show_crumb = FALSE;
    
        // Write the initial message, telling the user the batch processor is about to start
    
        $r  = $DSP->heading(BR.$LANG->line('sending_email'));
        $r .= $DSP->qdiv('itemWrapper', $LANG->line('batchmode_ready_to_begin'));
        $r .= $DSP->qdiv('', $DSP->qdiv('alert', $LANG->line('batchmode_warning')));
    
    
        $DSP->body = $r;
    }
    /* END */
    

	/** ----------------------------------------
	/**  Add unsubscribe link to emails
	/** ----------------------------------------*/

	function parse_template($template, $message, $action_id, $code, $mailtype='plain')
	{
		global $PREFS, $LANG, $FNS;

		if (is_array($template))
		{
			$list_title = $template['list_title'];
			$temp = $template['list_template'];
		}
		else
		{
			$list_title = '';
			$temp = $template;
		}

		$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
		$link_url = $FNS->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$code;
		
		$temp = str_replace('{unsubscribe_url}', $link_url, $temp);	

		if ($mailtype == 'html')
		{
			$temp =  preg_replace("/\{if\s+html_email\}(.+?)\{\/if\}/si", "\\1", $temp);
			$temp =  preg_replace("/\{if\s+plain_email\}.+?\{\/if\}/si", "", $temp);
		}
		else
		{
			$temp =  preg_replace("/\{if\s+plain_email\}(.+?)\{\/if\}/si", "\\1", $temp);
			$temp =  preg_replace("/\{if\s+html_email\}.+?\{\/if\}/si", "", $temp);
		}

		$temp = str_replace('{mailing_list}', $list_title, $temp);
		
		return str_replace('{message_text}', $message, $temp);
	}
	/* END */
    
    
    
    
    /** ------------------------------------
    /**  Convert recipient string to array
    /** ------------------------------------*/

    function convert_recipients($recipients = '')
    {
		$emails = array();
	
		$ct = 0;
	
		if ($recipients != '')
		{
			$recipients = trim(str_replace(",,", ",", $recipients), ',');
					
			if (strpos($recipients, ',') !== FALSE)
			{					
				$x = explode(',', $recipients);
									
				for ($i = 0; $i < count($x); $i ++)
					$emails['r'.$ct] = trim($x[$i]);
			}
			else
			{
				$emails['r'.$ct] = $recipients;
			}
		}
    
    		return $emails;
    }
    /* END */
    


    /** -----------------------------
    /**  Count total recipients
    /** -----------------------------*/
    
	function fetch_total($to, $cc, $bcc)
    {
		$total = 0;
	
		if ($to != '')
		{
			$total += count(explode(",", $to));	
		}	
		if ($cc != '')
		{
			$total += count(explode(",", $cc));	
		}	
		if ($bcc != '')
		{
			$total += count(explode(",", $bcc));	
		}	
    
   		return $total;
    }
    /* END */
    
    
    /** -----------------------------
    /**  Save cache data
    /** -----------------------------*/
    
    function save_cache_data($cache_data, $groups = '', $list_ids = '')
    {
    	global $DB;
		
		// We don't cache emails sent by "user blogs"
		
		if (USER_BLOG != FALSE)
		{
			return;
		}

        $DB->query($DB->insert_string('exp_email_cache', $cache_data)); 
        
        $cache_id = $DB->insert_id;
        
        if (is_array($groups))
        {
			if (count($groups) > 0)
			{			
				foreach ($groups as $id)
				{
					$DB->query("INSERT INTO exp_email_cache_mg (cache_id, group_id) VALUES ('$cache_id', '".$DB->escape_str($id)."')");
				}
			}
   		}

        if (is_array($list_ids))
        {
			if (count($list_ids) > 0)
			{			
				foreach ($list_ids as $id)
				{
					$DB->query("INSERT INTO exp_email_cache_ml (cache_id, list_id) VALUES ('$cache_id', '".$DB->escape_str($id)."')");
				}
			}
   		}
   		
   		return $cache_id;
	}    
	/* END */
    
    
    /** -----------------------------
    /**  Send email in batch mode
    /** -----------------------------*/
    
    function batch_send()
    {  
        global $IN, $DSP, $FNS, $LANG, $DB, $SESS, $PREFS, $REGX;
                
        $DSP->title = $LANG->line('communicate');
        $DSP->show_crumb = FALSE;
        $debug_msg = '';
                
        if ( ! $id = $IN->GBL('id'))
        {
            return $DSP->error_message($LANG->line('problem_with_id'), 0);
        }

        /** -----------------------------
		/**  Fetch mailing list IDs
		/** -----------------------------*/
        
		$list_templates = array();

		if ($this->mailinglist_exists == TRUE)
		{
			$query = $DB->query("SELECT list_id FROM exp_email_cache_ml WHERE cache_id = '".$DB->escape_str($id)."'");
			
			if ($query->num_rows > 0)
			{
				foreach ($query->result as $row)
				{
					// Fetch the template for each list
					$query = $DB->query("SELECT list_template, list_title FROM exp_mailing_lists WHERE list_id = '".$row['list_id']."'");
					$list_templates[$row['list_id']] = array('list_template' => $query->row['list_template'], 'list_title' => $query->row['list_title']);
				}
			}
    	}
    
        /** -----------------------------
        /**  Fetch cached email
        /** -----------------------------*/
        
        $query = $DB->query("SELECT * FROM exp_email_cache WHERE cache_id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows == 0)
        {
            return $DSP->error_message($LANG->line('cache_data_missing'), 0);
        }
        
        // Turn the result fields into variables
        
        foreach ($query->row as $key => $val)
        {
            if ($key == 'recipient_array')
            {
                $$key = $REGX->array_stripslashes(unserialize($val));
            }
            else
            {
                $$key = $val;
            }
        }
        
        /** -------------------------------------------------
        /**  Determine which emails correspond to this batch
        /** -------------------------------------------------*/
        
        $finished = FALSE;
        
        $total = count($recipient_array);
        
        $batch = $PREFS->ini('email_batch_size');
               
        if ($batch > $total)
        {
            $batch = $total;
            
            $finished = TRUE;
        }
        
		/** ---------------------------------------
		/**  Apply text formatting if necessary
		/** ---------------------------------------*/
		
        if ($text_fmt != 'none' && $text_fmt != '')
		{
			if ( ! class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}

			$TYPE = new Typography(0); 
			$TYPE->parse_smileys = FALSE;

			$message = $TYPE->parse_type($message, 
											  array(
													'text_format'   => $text_fmt,
													'html_format'   => 'all',
													'auto_links'    => 'n',
													'allow_img_url' => 'y'
												  )
											);	
		}
		
        /** ---------------------
        /**  Send emails
        /** ---------------------*/
        
		$action_id  = $FNS->fetch_action_id('Mailinglist', 'unsubscribe');

        require PATH_CORE.'core.email'.EXT;
        
        $email = new EEmail;    
        $email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
        $email->mailtype  = $mailtype;
        $email->priority  = $priority;
        
        $i = 0;
        
        foreach ($recipient_array as $key => $val)
        {
			if ($i == $batch)
			{
				break;
			}
		
			$screen_name = '';
			$list_id = FALSE;
		
			if (is_array($val) AND substr($key, 0, 1) == 'm')
			{
				$screen_name = $val['1'];
				$val = $val['0'];
			}
			elseif (is_array($val) AND substr($key, 0, 1) == 'l')
			{
				$list_id = $val['1'];
				$val = $val['0'];
			}
        
            $email->initialize();
            $email->from($from_email, $from_name);	
            $email->to($val); 
            $email->subject($subject);	
                        
			// m = member id
			// l = mailing list
			// r = general recipient
			
			// Make a copy so we don't mess up the original
			$msg = $message;
			$msg_alt = $plaintext_alt;
						
			if (substr($key, 0, 1) == 'l')
			{
				$msg = $this->parse_template($list_templates[$list_id], $msg, $action_id, substr($key, 1), $mailtype);
				$msg_alt = $this->parse_template($list_templates[$list_id], $msg_alt, $action_id, substr($key, 1), 'plain');
			}
			
			$msg = str_replace('{name}', $screen_name, $msg);
			$msg_alt = str_replace('{name}', $screen_name, $msg_alt);				
			
			$email->message($msg, $msg_alt);	            	
            
            $error = FALSE;
            
            if ( ! $email->Send())
            {
				$error = TRUE;
            }

            $debug_msg = $this->debug_message($email->debug_msg);
            
            if ($error == TRUE)
			{
				// Let's adjust the recipient array up to this point
				reset($recipient_array);
				$recipient_array = addslashes(serialize(array_slice($recipient_array, $i)));
				$n = $total_sent + $i;
            	
				$DB->query("UPDATE exp_email_cache SET total_sent = '$n', recipient_array = '$recipient_array' WHERE cache_id = '".$DB->escape_str($id)."'");				
				
				
				return $DSP->error_message($LANG->line('error_sending_email').$debug_msg, 0);
            }
                        
            $i++;
        }
        
		$n = $total_sent + $i;
           
        /** ------------------------
        /**  More batches to do...
        /** ------------------------*/
                
        if ($finished == FALSE)
        {
			reset($recipient_array);
		
			$recipient_array = addslashes(serialize(array_slice($recipient_array, $i)));
        	
            $DB->query("UPDATE exp_email_cache SET total_sent = '$n', recipient_array = '$recipient_array' WHERE cache_id = '".$DB->escape_str($id)."'");
                    
            $DSP->refresh = BASE.AMP.'C=communicate'.AMP.'M=batch_send'.AMP.'id='.$id;
            $DSP->ref_rate = 4;

            $r  = $DSP->heading(BR.$LANG->line('sending_email'));
            
            $stats = str_replace("%x", ($total_sent + 1), $LANG->line('currently_sending_batch'));
            $stats = str_replace("%y", $n, $stats);
            
            $r .= $DSP->qdiv('itemWrapper', $stats);
            
        		$remaining = $total - $batch;
            
            $r .= $DSP->qdiv('itemWrapper', $LANG->line('emails_remaining').NBS.NBS.$remaining);
                        
            $r .= $DSP->qdiv('', $DSP->qdiv('alert', $LANG->line('batchmode_warning')));
            
        }
        
        /** ------------------------
        /**  Finished!
        /** ------------------------*/
        
        else
        {
            $DB->query("UPDATE exp_email_cache SET total_sent = '$n', recipient_array = '' WHERE cache_id = '".$DB->escape_str($id)."'");
        
            $r  = $DSP->heading(BR.$LANG->line('email_sent'));
            
            $r .= $DSP->qdiv('success', $LANG->line('all_email_sent_message'));
            
            $total = $total_sent + $batch;
            
            $r .= $DSP->qdiv('itemWrapper', $LANG->line('total_emails_sent').NBS.NBS.$total);
        }
            
        $DSP->body = $r;
    }
    /* END */
    
    
    
    
	/** ------------------------
	/**  View Email Cache
	/** ------------------------*/
    
	function view_email_cache($message = '')
	{  
    	global $IN, $DB, $LANG, $DSP, $LOC, $REGX;
    
		if ( ! $DSP->allowed_group('can_send_cached_email'))
		{     
			return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
		}
    
		/** -----------------------------
    		/**  Define base variables
    		/** -----------------------------*/
    	
		$i = 0;

		$s1 = 'tableCellOne';
		$s2 = 'tableCellTwo';
		
		$row_limit 	= 50;
		$paginate	= '';
		$row_count	= 0;
		
        $DSP->title = $LANG->line('previous_email');
        $DSP->crumb = $LANG->line('previous_email');
		
		$DSP->body  = $DSP->qdiv('tableHeading', $LANG->line('previous_email'));
		
        if ($message != '')
        {
			$DSP->body .= $DSP->qdiv('success', $message);
        }
		
		
		/** -----------------------------
    	/**  Run Query
    	/** -----------------------------*/
		
		$sql = "SELECT * FROM exp_email_cache ORDER BY cache_id desc";
		
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			if ($message == '')
				$DSP->body	.=	$DSP->qdiv('box', $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_cached_email'))));             
			
			return;
		}		
		
			/** -----------------------------
    		/**  Do we need pagination?
    		/** -----------------------------*/
		
		if ($query->num_rows > $row_limit)
		{ 
			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');
						
			$url = BASE.AMP.'C=communicate'.AMP.'M=view_cache';
						
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

        $DSP->body .= $DSP->form_open(
        								array(
        										'action'	=> 'C=communicate'.AMP.'M=delete_conf', 
        										'name'		=> 'target',
        										'id'		=> 'target'
        										)
        							);

        $DSP->body .= $DSP->table('tableBorder', '0', '0', '100%').
					  $DSP->tr().
					  $DSP->table_qcell('tableHeading',
										array(
												NBS,
												$LANG->line('email_title'), 
												$LANG->line('email_date'),
												$LANG->line('total_recipients'),
												$LANG->line('status'),
												$LANG->line('resend'),
												$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$LANG->line('delete').NBS.NBS
											  
											  )
											).
              $DSP->tr_c();
              
		/** -----------------------------
    	/**  Table Rows
    	/** -----------------------------*/
		
		$row_count++;  		
              
		foreach ($query->result as $row)
		{			
			if ($row['recipient_array'] != '')
			{
				$total_failed = count($REGX->array_stripslashes(unserialize($row['recipient_array'])));
				$failed_recipients = $LANG->line('incomplete').NBS.NBS.$DSP->anchor(BASE.AMP.'C=communicate'.AMP.'M=batch_send'.AMP.'id='.$row['cache_id'], $LANG->line('finish_sending').NBS.$total_failed);
			}
			else
			{
  				$failed_recipients = $LANG->line('complete');				
			}			
			
			$DSP->body	.=	$DSP->table_qrow( ($i++ % 2) ? $s1 : $s2, 
									array(
											$row_count,
													
$DSP->anchorpop(BASE.AMP.'C=communicate'.AMP.'M=view_email'.AMP.'id='.$row['cache_id'].AMP.'Z=1', '<b>'.$row['subject'].'</b>', '600', '580'),

											$LOC->set_human_time($row['cache_date']),
											
											$row['total_sent'],
											
											$failed_recipients,
																						
											$DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=communicate'.AMP.'id='.$row['cache_id'], $LANG->line('resend'))),
																						
											$DSP->input_checkbox('toggle[]', $row['cache_id'])

										  )
									);
			$row_count++;  		
		}	
        
        $DSP->body .= $DSP->table_c(); 

		if ($paginate != '')
		{
			$DSP->body .= $DSP->qdiv('itemWrapper', $paginate);
		}
    
		$DSP->body .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('delete')));             
        
        $DSP->body .= $DSP->form_close();
    }
    /* END */
    

   
	/** ------------------------
	/**  View a specific email
	/** ------------------------*/
    
	function view_email()
	{  
    	global $IN, $DB, $LANG, $DSP, $LOC;
    
		if ( ! $DSP->allowed_group('can_send_cached_email'))
		{     
			return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
		}
		
		$id = $IN->GBL('id');
    
		
		/** -----------------------------
    	/**  Run Query
    	/** -----------------------------*/
				
		$query = $DB->query("SELECT mailtype, subject, message FROM exp_email_cache WHERE cache_id = '".$DB->escape_str($id)."' ");
		
		if ($query->num_rows == 0)
		{
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', $LANG->line('no_cached_email')));             
			
			return;
		}
	
		/** -----------------------------
    	/**  Clean up message
    	/** -----------------------------*/
		
		// If the message was submitted in HTML format
		// we'll remove everything except the body
		
		$message = $query->row['message'];
		
		if ($query->row['mailtype'] == 'html')
		{
        	$message = (preg_match("/<body.*?".">(.*)<\/body>/is", $message, $match)) ? $match['1'] : $message;
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
		
		$DSP->body .= $TYPE->parse_type( $message, 
								 array(
											'text_format'   => 'xhtml',
											'html_format'   => 'all',
											'auto_links'    => 'y',
											'allow_img_url' => 'y'
									   )
								);
    }
    /* END */
    
    

    /** -------------------------------------------
    /**  Delete Confirm
    /** -------------------------------------------*/

    function delete_confirm()
    { 
        global $IN, $DSP, $LANG;
        
		if ( ! $DSP->allowed_group('can_send_cached_email'))
		{     
			return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
		}
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->view_email_cache();
        }
        
        $DSP->title = $LANG->line('delete_emails');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=communicate'.AMP.'M=view_cache', $LANG->line('view_email_cache'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('delete_emails'));

        $DSP->body	.=	$DSP->form_open(array('action' => 'C=communicate'.AMP.'M=delete'));
        
        $i = 0;
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $DSP->body	.=	$DSP->input_hidden('delete[]', $val);
                $i++;
            }        
        }
        
		$DSP->body .= $DSP->heading($DSP->qspan('alert', $LANG->line('delete_confirm')));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('delete_question'));
		$DSP->body .= $DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone'));
		$DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('delete')));
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->form_close();
    }
    /* END */
    
    
    
    /** -------------------------------------------
    /**  Delete Emails
    /** -------------------------------------------*/

    function delete_emails()
    { 
        global $IN, $DSP, $LANG, $DB;
        
		if ( ! $DSP->allowed_group('can_send_cached_email'))
		{     
			return $DSP->no_access_message($LANG->line('not_allowed_to_email_mailinglist'));
		}
        
        if ( ! $IN->GBL('delete', 'POST'))
        {
            return $this->view_email_cache();
        }
        

        $ids = array();
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val))
            {
                $ids[] = "cache_id = '".$DB->escape_str($val)."'";
            }        
        }
        
        $IDS = implode(" OR ", $ids);
        
        $DB->query("DELETE FROM exp_email_cache WHERE ".$IDS);
        $DB->query("DELETE FROM exp_email_cache_mg WHERE ".$IDS);
        $DB->query("DELETE FROM exp_email_cache_ml WHERE ".$IDS);
    
        return $this->view_email_cache($LANG->line('email_deleted'));
    }
    /* END */
    
 

	/** -------------------------------------
	/**  Word wrap - DEPRECATED 1.6.3
	/**  use EEmail::word_wrap()
	/** -------------------------------------*/
	 
	function word_wrap($str, $chars = '')
	{	
		if ($chars == '')
			$chars = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		
		$lines = explode("\n", $str);
		
		$output = "";

		while (list(, $thisline) = each($lines)) 
		{
			if (strlen($thisline) > $chars)
			{
				$line = "";
				
				$words = explode(" ", $thisline);
				
				while(list(, $thisword) = each($words)) 
				{
					while((strlen($thisword)) > $chars) 
					{
						if (stristr($thisword, '{unwrap}') !== FALSE OR stristr($thisword, '{/unwrap}') !== FALSE)
						{
							break;
						}
					
						$cur_pos = 0;
						
						for($i=0; $i < $chars - 1; $i++)
						{
							$output .= $thisword[$i];
							$cur_pos++;
						}
						
						$output .= "\n";
						
						$thisword = substr($thisword, $cur_pos, (strlen($thisword) - $cur_pos));
					}
					
					if ((strlen($line) + strlen($thisword)) > $chars) 
					{
						$output .= $line."\n";
						
						$line = $thisword." ";
					} 
					else 
					{
						$line .= $thisword." ";
					}
				}
	
				$output .= $line."\n";
			} 
			else 
			{
				$output .= $thisline."\n";
			}
		}

		return $output;	
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  Base IFRAME for Spell Check
    /** -----------------------------------------*/

    function spellcheck_iframe()
    {
		if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::iframe();
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  Spell Check for Textareas
    /** -----------------------------------------*/

    function spellcheck()
    {
    	if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::check();
	}
	/* END */
	
	/** -------------------------------------
	/**  Fetch installed plugins
	/** -------------------------------------*/
	
	function fetch_plugins()
	{
		$exclude = array('auto_xhtml');
	
		$filelist = array('br', 'xhtml');
		
		$ext_len = strlen(EXT);
		
		if ($fp = @opendir(PATH.'plugins/')) 
		{ 
			while (FALSE !== ($file = readdir($fp))) 
			{ 
				if (substr($file, -$ext_len) == EXT && strncmp($file, 'pi.', 3) == 0)
				{
					$file = substr($file, 3, -$ext_len);
					
					if ( ! in_array($file, $exclude))
					{
						$filelist[] = $file;
						
					}
				}
			} 
		} 
	
		closedir($fp); 
		sort($filelist);
		return $filelist;	  
	}
 
}
// END CLASS
?>