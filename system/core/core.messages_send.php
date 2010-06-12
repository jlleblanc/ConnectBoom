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
 File: core.messages.php
-----------------------------------------------------
 Purpose: Private Messages
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Messages_send extends Messages {

	/** -----------------------------------
    /**  Constructor
    /** -----------------------------------*/

    function Messages_send()
    {
    }
    /* END */
    
    
	/** -------------------------------------
    /**  Uploading Attachments
    /** -------------------------------------*/

	function _attach_file()
	{
		global $IN, $DB, $FNS, $PREFS, $LOC, $LANG, $SESS;
		
		/** -------------------------------------
		/**  Check the paths
		/** -------------------------------------*/
		
		if ($this->upload_path == '')
		{
			return $LANG->line('unable_to_recieve_attach');
		}

		if ( ! @is_dir($this->upload_path) OR ! @is_writable($this->upload_path))
		{
			return $LANG->line('unable_to_recieve_attach');
		}
		
		/** -------------------------------------
		/**  Are there previous attachments?
		/** -------------------------------------*/
        
        $this->attachments = array();
        $attachments_size  = 0;
        
        if ($IN->GBL('attach') !== FALSE && $IN->GBL('attach') != '')
        {
        	$query = $DB->query("SELECT attachment_id, attachment_size, attachment_location
        						 FROM exp_message_attachments
        						 WHERE attachment_id IN ('".str_replace('|', "','", $IN->GBL('attach'))."')");
 			
 			if ($query->num_rows + 1 > $this->max_attachments)
 			{
 				return $LANG->line('no_more_attachments');
 			}
 			elseif ($query->num_rows > 0)
 			{
 				foreach($query->result as $row)
 				{
 					if ( ! file_exists($row['attachment_location']))
 					{
 						continue;
 					}
 					
 					$this->attachments[] = $row['attachment_id'];
 					$attachments_size += $row['attachment_size'];
        		}
        	}
        }
        
        
        /** -------------------------------------
		/**  Attachment too hefty?
		/** -------------------------------------*/
        
        if ($this->attach_maxsize != 0 && ($attachments_size + ($_FILES['userfile']['size'] /1024)) > $this->attach_maxsize)
        {
        	return $LANG->line('attach_too_large');
        }
        
		/** -------------------------------------
		/**  Fetch the size of all attachments
		/** -------------------------------------*/

		if ($this->attach_total != '0')
		{
			$query = $DB->query("SELECT SUM(attachment_size) AS total FROM exp_message_attachments WHERE is_temp != 'y'");
			
			if ( ! empty($query->row['total']))
			{	
        		// Is the size of the new file (along with the previous ones) too large?
        		                                                        
				if (ceil($query->row['total'] + ($_FILES['userfile']['size']/1024)) > ($this->attach_total * 1000))
				{
        			return $LANG->line('too_many_attachments');
				}
			}
		}
		
		/** -------------------------------------
		/**  Separate the filename form the extension
		/** -------------------------------------*/
		
		if ( ! class_exists('Image_lib'))
		{
			require PATH_CORE.'core.image_lib'.EXT;
		}
		
		$IM = new Image_lib();
		
		$split = $IM->explode_name($_FILES['userfile']['name']);
		$filename  = $split['name'];
		$extension = $split['ext'];		
		
		$filehash = $FNS->random('alpha', 20);
		
		/** -------------------------------------
		/**  Upload the image
		/** -------------------------------------*/
		
		if ( ! class_exists('Upload'))
		{
        	require PATH_CORE.'core.upload'.EXT;
        }
        
        $UP = new Upload();
       
		$UP->set_upload_path($this->upload_path);
        $UP->set_allowed_types('all');
       
        $UP->new_name = $filehash.$extension;
                        
        if ( ! $UP->upload_file())
        {
			@unlink($UP->new_name);
			
			if ($UP->error_msg == 'invalid_filetype')
			{
				$info = implode(', ', $UP->allowed_mimes);
			
				$info  = "<div class='default'>".$LANG->line($UP->error_msg).
						 "<div class='default'>".$LANG->line('allowed_mimes').'&nbsp;'.$info."</div>";
				
				return $info;
			}
			
			return $UP->error_msg;
        }

		/** -------------------------------------
		/**  Insert into Database
		/** -------------------------------------*/
		
		$this->temp_message_id = $FNS->random('nozero', 10);
      
      	$data = array(
      					'attachment_id'			=> '',
      					'sender_id'				=> $this->member_id,
      					'message_id'			=> $this->temp_message_id,
      					'attachment_name'		=> $filename.$extension,
      					'attachment_hash'		=> $filehash,
      					'attachment_extension'  => $extension,
      					'attachment_location'	=> $UP->new_name,
      					'attachment_date'		=> $LOC->now,
      					'attachment_size'		=> ceil($UP->file_size/1024)
      				);      
      				
		$DB->query($DB->insert_string('exp_message_attachments', $data));	
		$attach_id = $DB->insert_id;
		
		
		/** -------------------------------------
		/**  Change file name with attach ID
		/** -------------------------------------*/
		
		// For convenience we use the attachment ID number as the prefix for all files.
		// That way they will be easier to manager.
		
		// OK, whatever you say, Rick.  -Paul
		
		if (file_exists($UP->new_name))
		{
			$final_name = $attach_id.'_'.$filehash;
			$final_path = $UP->upload_path.$final_name.$extension;
			
			if (rename($UP->new_name, $final_path))
			{
				chmod($final_path, 0777);			
				$DB->query("UPDATE exp_message_attachments SET attachment_hash = '{$final_name}', attachment_location = '{$final_path}'  WHERE attachment_id = '{$attach_id}'");
			}
		}
		
		/** -------------------------------------
		/**  Load Attachment into array
		/** -------------------------------------*/
				
		$this->attachments[] = $attach_id;
        
        /* -------------------------------------
        /*  Delete Temp Attachments Over 48 Hours Old
		/*
		/*  The temp attachments are kept so long because
		/*  of draft messages that may contain attachments
		/*  but will not be sent until later.  I think 48
		/*  hours is enough time.  Any longer and the attachment
		/*  is gone but the message remains.
        /* -------------------------------------*/
		
		$expire = $LOC->now - 24*60*60;
		
		$result = $DB->query("SELECT attachment_location FROM exp_message_attachments 
							  WHERE attachment_date < $expire
							  AND is_temp = 'y'");
							  
		if ($result->num_rows > 0)
		{
			foreach ($result->result as $row)
			{
				@unlink($row['attachment_location']);
			}
				
			$DB->query("DELETE FROM exp_message_attachments WHERE attachment_date < $expire AND is_temp='y'");			
		}
		
		return TRUE;
	}
	/* END */
	
	
	
	
	/** -------------------------------------
    /**  Duplicate Attachments for Forwards
    /** -------------------------------------*/

	function _duplicate_files()
	{
		global $IN, $DB, $FNS, $PREFS, $LOC, $LANG, $SESS;
		
		if (sizeof($this->attachments) == 0)
		{
			return TRUE;
		}
		
		/** -------------------------------------
		/**  Check the paths
		/** -------------------------------------*/
		
		if ($this->upload_path == '')
		{
			return $LANG->line('unable_to_recieve_attach');
		}

		if ( ! @is_dir($this->upload_path) OR ! @is_writable($this->upload_path))
		{
			return $LANG->line('unable_to_recieve_attach');
		}
		
		/** -------------------------------------
		/**  Fetch the size of all attachments
		/** -------------------------------------*/

		if ($this->attach_total != '0')
		{
			$query = $DB->query("SELECT SUM(attachment_size) AS total FROM exp_message_attachments WHERE is_temp != 'y'");
			
			if ( ! empty($query->row['total']))
			{
				$total = $query->row['total'];
			}
			else
			{
				$total = 0;
			}
		}
		
		
		/** -------------------------------------
		/**  Get Attachment Data
		/** -------------------------------------*/
 		
 		$results = $DB->query("SELECT attachment_name, attachment_size,
 							   attachment_location, attachment_extension
 							   FROM exp_message_attachments
 							   WHERE attachment_id IN ('".implode("','", $this->attachments)."')");
 							   
 		if ($query->num_rows == 0)
 		{
 			return TRUE;
 		}
 		
 		$this->attachments = array();
 		
 		foreach($results->result as $row)
 		{
 			if ( ! file_exists($row['attachment_location']))
 			{
 				continue;
 			}
 			
 			/** -------------------------------------
			/**  Check Against Max
			/** -------------------------------------*/

			if ($this->attach_total != '0')
			{
				if (ceil($total + $row['attachment_size']) > ($this->attach_total * 1000))
				{
        			return $LANG->line('too_many_attachments');
				}
			}
			
			/** -------------------------------------
			/**  Duplicate File
			/** -------------------------------------*/
			
			$filehash = $FNS->random('alpha', 20);
	        
	        $new_name = $filehash.$row['attachment_extension'];
	        
	        $new_location = $this->upload_path.$new_name;
	        
	        if (@copy($row['attachment_location'], $new_location))
	        {
	        	chmod($new_location, 0777);
	        }
	
			/** -------------------------------------
			/**  Insert into Database
			/** -------------------------------------*/
			
			$this->temp_message_id = $FNS->random('nozero', 10);
      	
      		$data = array(
      						'attachment_id'			=> '',
      						'sender_id'				=> $this->member_id,
      						'message_id'			=> $this->temp_message_id,
      						'attachment_name'		=> $row['attachment_name'],
      						'attachment_hash'		=> $filehash,
      						'attachment_extension'  => $row['attachment_extension'],
      						'attachment_location'	=> $new_location,
      						'attachment_date'		=> $LOC->now,
      						'attachment_size'		=> $row['attachment_size']
      					);      
      				
			$DB->query($DB->insert_string('exp_message_attachments', $data));	
			$attach_id = $DB->insert_id;
		
		
			/** -------------------------------------
			/**  Change file name with attach ID
			/** -------------------------------------*/
			
			// For convenience we use the attachment ID number as the prefix for all files.
			// That way they will be easier to manager.
			
			// OK, whatever you say, Rick.  -Paul
			
			if (file_exists($new_location))
			{
				$final_name = $attach_id.'_'.$filehash;
				$final_path = $this->upload_path.$final_name.$row['attachment_extension'];
				
				if (rename($new_location, $final_path))
				{
					chmod($final_path, 0777);
				}
				
				$DB->query("UPDATE exp_message_attachments 
							SET attachment_hash = '{$final_name}', attachment_location = '{$final_path}' 
							WHERE attachment_id = '{$attach_id}'");
			}
		
			/** -------------------------------------
			/**  Load Attachment into array
			/** -------------------------------------*/
					
			$this->attachments[] = $attach_id;
        }
        
		return TRUE;
	}
	/* END */
 	


	/** -------------------------------------
    /**  Submission Error Display
    /** -------------------------------------*/
	
	function _remove_attachment($id)
	{
		global $IN, $DB, $SESS;
	
		$query = $DB->query("SELECT attachment_location FROM exp_message_attachments
							WHERE attachment_id = '".$DB->escape_str($id)."'
							AND sender_id = '".$SESS->userdata['member_id']."'");

		if ($query->num_rows == 0)
		{
			return;
		}
		
		@unlink($query->row['attachment_location']);

		$DB->query("DELETE FROM exp_message_attachments WHERE attachment_id = '{$id}'");
		
		$this->attachments = array();
		
		$x = explode("|", $IN->GBL('attach'));
		
		foreach ($x as $val)
		{
			if ($val != $id)
			{
				$this->attachments[] = $val;
			}
		}
	}
	/* END */
 	 	

 	/** -----------------------------------
    /**  Send Message
    /** -----------------------------------*/

    function send_message()
    {
    	global $LANG, $DB, $IN, $LOC, $FNS, $SESS, $REGX, $PREFS;
    	
    	$submission_error = array();
    	
    	/** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        
        if ($SESS->userdata['is_banned'] === TRUE)
        {            
			return $this->_error_page();
        }
     
        /** ----------------------------------------
        /**  Is the IP or User Agent unavalable?
        /** ----------------------------------------*/

		if ($IN->IP == '0.0.0.0' || $SESS->userdata['user_agent'] == '')
		{            
			return $this->_error_page();
		}
		
		/** -------------------------------------
		/**  Status Setting
		/** -------------------------------------*/
		
		if ($IN->GBL('preview') OR $IN->GBL('remove'))
		{
			$status = 'preview';
		}
		elseif($IN->GBL('draft'))
		{
			$status = 'draft';
		}
		else
		{
			$status = 'sent';
		}
		
		/** -------------------------------------
		/**  Already Sent?
		/** -------------------------------------*/
		
		if ($IN->GBL('message_id') !== FALSE && is_numeric($IN->GBL('message_id')))
		{
			$query = $DB->query("SELECT message_status FROM exp_message_data WHERE message_id = '".$DB->escape_str($IN->GBL('message_id'))."'");
			
			if ($query->num_rows > 0 && $query->row['message_status'] == 'sent')
			{
				return $this->_error_page($LANG->line('messsage_already_sent'));
			}
		}
		
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- prv_msg_waiting_period => How many hours after becoming a member until they can PM?
        /* -------------------------------------------*/
        
        $waiting_period = ($PREFS->ini('prv_msg_waiting_period') !== FALSE) ? (int) $PREFS->ini('prv_msg_waiting_period') : 1;
        
        if ($SESS->userdata['join_date'] > ($LOC->now - $waiting_period * 60 * 60))
        {
        	return $this->_error_page(str_replace(array('%time%', '%email%', '%site%'), 
        										  array($waiting_period, $FNS->encode_email($PREFS->ini('webmaster_email')), $PREFS->ini('site_name')), 
        										  $LANG->line('waiting_period_not_reached')));
        }
        
        
        /* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- prv_msg_throttling_period => How many seconds between PMs?
        /* -------------------------------------------*/
        
        if ($status == 'sent' && $SESS->userdata['group_id'] != 1)
        {
        	$period = ($PREFS->ini('prv_msg_throttling_period') !== FALSE) ? (int) $PREFS->ini('prv_msg_throttling_period') : 30;
        
        	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_data d
        						 WHERE d.sender_id = '".$DB->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_date > ".$DB->escape_str($LOC->now - $period));
								 
			if ($query->row['count'] > 0)
			{
				return $this->_error_page(str_replace('%x', $period, $LANG->line('send_throttle')));
			}
		}
		
		
		/** ------------------------------------------
		/**  Is there a recipient, subject, and body?
		/** ------------------------------------------*/
		
		if ($IN->GBL('recipients') == '' && $status == 'sent')
		{
			$submission_error[] = $LANG->line('empty_recipients_field');
		}
		elseif ($IN->GBL('subject') == '')
		{
			$submission_error[] = $LANG->line('empty_subject_field');
		}
		elseif ($IN->GBL('body') == '')
		{
			$submission_error[] = $LANG->line('empty_body_field');
		}
		
		/** -------------------------------------------
		/**  Deny Duplicate Data
		/** -------------------------------------------*/
        
        if ($PREFS->ini('deny_duplicate_data') == 'y')
        {
        	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_data d
        						 WHERE d.sender_id = '".$DB->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_body = '".$DB->escape_str($REGX->xss_clean($IN->GBL('body')))."'");
								 
			if ($query->row['count'] > 0)
			{
				return $this->_error_page($LANG->line('duplicate_message_sent'));
			}
		}
		
		/** ------------------------------------------
		/**  Valid Recipients? - Only Checked on Sent
		/** ------------------------------------------*/
		
		$recipients = $this->convert_recipients($IN->GBL('recipients'), 'array', 'member_id');
		
		$cc = (trim($IN->GBL('cc')) == '') ? array() : $this->convert_recipients($IN->GBL('cc'), 'array', 'member_id');
		
		$recip_orig	= sizeof($recipients);
		$cc_orig	= sizeof($cc);
		
		// Make sure CC does not contain members in Recipients
		$cc = array_diff($cc, $recipients);
		
		if(sizeof($recipients) == 0 && $status == 'sent')
		{
			$submission_error[] = $LANG->line('empty_recipients_field');
		}
		
		if($this->invalid_name === TRUE)
		{
			$submission_error[] = $LANG->line('invalid_username');
		}
		
		/** ------------------------------------------
		/**  Too Big for Its Britches?
		/** ------------------------------------------*/
		
		if ($this->max_chars != 0 && strlen($IN->GBL('body')) > $this->max_chars)
		{
			$submission_error[] = str_replace('%max%', $this->max_chars, $LANG->line('message_too_large'));
		}
		
		/** -------------------------------------
		/**  Super Admins get a free pass
		/** -------------------------------------*/
		
		if ($SESS->userdata('group_id') != 1)
		{
			/** ------------------------------------------
			/**  Sender Allowed to Send More Messages?
			/** ------------------------------------------*/

			$query = $DB->query("SELECT COUNT(c.copy_id) AS count 
								 FROM exp_message_copies c, exp_message_data d
								 WHERE c.message_id = d.message_id
								 AND c.sender_id = '".$DB->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_date > ".($LOC->now - 24*60*60));

			if (($query->row['count'] + sizeof($recipients) + sizeof($cc)) > $this->send_limit)
			{
				$submission_error[] = $LANG->line('sending_limit_warning');
			}

			/** ------------------------------------------
			/**  Sender Allowed to Store More Messages?
			/** ------------------------------------------*/

			if ($this->storage_limit != '0' && ($IN->GBL('sent_copy') !== FALSE && $IN->GBL('sent_copy') == 'y'))
			{
				if ($this->total_messages == '')
	    		{
	    			$this->storage_usage();
	    		}

				if (($this->total_messages + 1) > $this->storage_limit)
				{
					$submission_error[] = $LANG->line('storage_limit_warning');
				}
			}			
		}
		
		/** -------------------------------------
		/**  Upload Path Set?
		/** -------------------------------------*/
		
		if ($this->upload_path == '' && (isset($_POST['remove']) || (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name'] != '')))
		{
			$submission_error[] = $LANG->line('unable_to_recieve_attach');
		}
		
		/** -------------------------------------
		/**  Attachments?
		/** -------------------------------------*/

		if ($IN->GBL('attach') !== FALSE && $IN->GBL('attach') != '')
		{
			$this->attachments = explode('|', $_POST['attach']);
		}
		
		/* -------------------------------------
		/*  Create Forward Attachments
		/*
		/*  We have to copy the attachments for
		/*  forwarded messages.  We only do this
		/*  when the compose messaage page is first
		/*  submitted.  We have a special variable
		/*  called 'create_attach' to tell us when
		/*  that is.
		/* -------------------------------------*/
		
		if ($this->attach_allowed == 'y' && $this->upload_path != '' && sizeof($this->attachments) > 0 && $IN->GBL('create_attach'))
		{
			if (($message = $this->_duplicate_files()) !== TRUE)
			{
				$submission_error[] = $message.BR;
			}
		}
		
		/** -------------------------------------
		/**  Is this a remove attachment request?
		/** -------------------------------------*/

		if (isset($_POST['remove']) && $this->upload_path != '')
		{
			$id = key($_POST['remove']);
			
			if (is_numeric($id))
			{
				$this->_remove_attachment($id);
				
				// Treat an attachment removal like a draft, where we do not
				// see the preview only the message.
				
				$this->hide_preview = TRUE;  
			}
		}
		
		/** -------------------------------------
		/**  Do we have an attachment to deal with?
		/** -------------------------------------*/
	
		if ($this->attach_allowed == 'y')
		{
			if ($this->upload_path != '' AND isset($_FILES['userfile']['name']) AND $_FILES['userfile']['name'] != '')
			{
				$preview = ($IN->GBL('preview', 'POST') !== FALSE) ? TRUE : FALSE;
				
				if (($message = $this->_attach_file()) !== TRUE)
				{	
					$submission_error[] = $message.BR;
				}
			}
		}
		
		/** -----------------------------------
		/**  Check Overflow
		/** -----------------------------------*/
		
		$details  = array();
		$details['overflow_recipients'] = array();
		$details['overflow_cc'] = array();
		
		for($i=0, $size = sizeof($recipients); $i < $size; $i++)
		{
			if ($this->_check_overflow($recipients[$i]) === FALSE)
			{
				$details['overflow_recipients'][] = $recipients[$i];
				unset($recipients[$i]);
			}
		}
			
		for($i=0, $size = sizeof($cc); $i < $size; $i++)
		{
			if ($this->_check_overflow($cc[$i]) === FALSE)
			{
				$details['overflow_cc'][] = $cc[$i];
				unset($cc[$i]);
			}
		}

		/* -------------------------------------------------
		/*  If we have people unable to receive a message
		/*  because of an overflow we make the message a 
		/*  preview and will send a message to the sender.
		/* -------------------------------------*/

		if (sizeof($details['overflow_recipients']) > 0 OR sizeof($details['overflow_cc']) > 0)
		{
			sort($recipients);
			sort($cc);
			$overflow_names = array();
			
			/* -------------------------------------
			/*  Send email alert regarding a full
			/*  inbox to these users, load names
			/*  for error message
			/* -------------------------------------*/
			
			global $PREFS;
			
			$query = $DB->query("SELECT exp_members.screen_name, exp_members.email, exp_members.accept_messages, exp_member_groups.prv_msg_storage_limit
								 FROM exp_members
								 LEFT JOIN exp_member_groups ON exp_member_groups.group_id = exp_members.group_id
								 WHERE exp_members.member_id IN ('".implode("','",array_merge($details['overflow_recipients'], $details['overflow_cc']))."')
								 AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
			
			if ($query->num_rows > 0)
			{
				if ( ! class_exists('EEmail'))
				{
					require PATH_CORE.'core.email'.EXT;
				}

				$email = new EEmail;
				$email->wordwrap = true;
				
				$swap = array(
							  'sender_name'			=> $SESS->userdata('screen_name'),
							  'site_name'			=> stripslashes($PREFS->ini('site_name')),
							  'site_url'			=> $PREFS->ini('site_url')
							  );
				
				$template = $FNS->fetch_email_template('pm_inbox_full');
				$email_tit = $FNS->var_swap($template['title'], $swap);
				$email_msg = $FNS->var_swap($template['data'], $swap);

				foreach($query->result as $row)
				{
					$overflow_names[] = $row['screen_name'];
					
					if ($row['accept_messages'] != 'y')
					{
						continue;
					}
					
					$email->initialize();
					$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
					$email->to($row['email']); 
					$email->subject($email_tit);	
					$email->message($FNS->var_swap($email_msg, array('recipient_name' => $row['screen_name'], 'pm_storage_limit' => $row['prv_msg_storage_limit'])));		
					$email->Send();
				}	
			}
		
			$submission_error[] = str_replace('%overflow_names%', implode(', ', $overflow_names), $LANG->line('overflow_recipients'));
		}
					
		/** ----------------------------------------
		/**  Submission Errors Force a Preview
		/** ----------------------------------------*/
		
		if (sizeof($submission_error) > 0)
		{
			$status = 'preview';
			$this->hide_preview = TRUE;
			$this->invalid_name = FALSE;
		}
		
		/* -------------------------------------
		/*  Check Blocked on Sent
		/*  
		/*  If a message is blocked, we will not notify
		/*  the sender of this and simply proceed.
		/* -------------------------------------*/
		
		if ($status == 'sent')
		{		
			$sql = "SELECT member_id FROM exp_message_listed
					WHERE listed_type = 'blocked'
					AND listed_member = '{$this->member_id}'
					AND 
					(
					member_id IN ('".implode("','", $recipients)."')";
						
			if (sizeof($cc) > 0)
			{
				$sql .= "OR
						 member_id IN ('".implode("','", $cc)."')";
			}
			
			$sql .= ")";
				
			$blocked = $DB->query($sql);
				
			if ($blocked->num_rows > 0)
			{	
				foreach($blocked->result as $row)
				{
					$details['blocked'][] = $row['member_id'];
				}
				
				$recipients = array_diff($recipients, $details['blocked']);
				$cc = (sizeof($cc) > 0) ? array_diff($cc, $details['blocked']) : array();
				
				sort($recipients);
				sort($cc);
			}
		}

		/** -------------------------------------
		/**  Store Data
		/** -------------------------------------*/
		
		$data = array('message_id' 			=> '',
					  'sender_id' 			=> $this->member_id,
					  'message_date' 		=> $LOC->now,
					  'message_subject' 	=> $REGX->xss_clean($IN->GBL('subject')),
					  'message_body'		=> $REGX->xss_clean($IN->GBL('body')),
					  'message_tracking' 	=> ( ! $IN->GBL('tracking')) ? 'n' : 'y',
					  'message_attachments' => (sizeof($this->attachments) > 0) ? 'y' : 'n',
					  'message_recipients'	=> implode('|', $recipients),
					  'message_cc'			=> implode('|', $cc),
					  'message_hide_cc'		=> ( ! $IN->GBL('hide_cc')) ? 'n' : 'y',
					  'message_sent_copy'	=> ( ! $IN->GBL('sent_copy')) ? 'n' : 'y',
					  'total_recipients'	=> (sizeof($recipients) + sizeof($cc)),
					  'message_status'		=> $status);
		
		if ($IN->GBL('message_id') && is_numeric($IN->GBL('message_id')))
		{
			/* -------------------------------------
			/*  Preview or Draft previously submitted.
			/*  So, we're updating an already existing message
			/* -------------------------------------*/
			
			$message_id = $IN->GBL('message_id');
			unset($data['message_id']);
			
			$DB->query($DB->update_string('exp_message_data', $data, "message_id = '".$DB->escape_str($message_id)."'"));
		}
		else
		{
			$DB->query($DB->insert_string('exp_message_data', $data));
		
			$message_id = $DB->insert_id;
		}
		
		/** -----------------------------------------
		/**  Send out Messages to Recipients and CC
		/** -----------------------------------------*/
		
		if ($status == 'sent')
		{
			$copy_data = array( 'copy_id'	 => '',	
								'message_id' => $message_id,
								'sender_id'	 => $this->member_id);
			
			/** -----------------------------------------
			/**  Send out Messages to Recipients and CC
			/** -----------------------------------------*/
		
			for($i=0, $size = sizeof($recipients); $i < $size; $i++)
			{
				$copy_data['recipient_id'] 		= $recipients[$i];
				$copy_data['message_authcode']	= $FNS->random('alpha', 10);
				$DB->query($DB->insert_string('exp_message_copies', $copy_data));
			}
			
			for($i=0, $size = sizeof($cc); $i < $size; $i++)
			{
				$copy_data['recipient_id']		= $cc[$i];
				$copy_data['message_authcode']	= $FNS->random('alpha', 10);
				$DB->query($DB->insert_string('exp_message_copies', $copy_data));
			}
			
			/** ----------------------------------
			/**  Increment exp_members.private_messages
			/** ----------------------------------*/
			
			$DB->query("UPDATE exp_members SET private_messages = private_messages + 1
						WHERE member_id IN ('".implode("','",array_merge($recipients, $cc))."')");
						
			/** ----------------------------------
			/**  Send Any and All Email Notifications
			/** ----------------------------------*/
			
			$query = $DB->query("SELECT screen_name, email FROM exp_members
								 WHERE member_id IN ('".implode("','",array_merge($recipients, $cc))."')
								 AND notify_of_pm = 'y'
								 AND member_id != {$this->member_id}");
								 
			if ($query->num_rows > 0)
			{
				global $PREFS;
				
				if ( ! class_exists('Typography'))
				{
					require PATH_CORE.'core.typography'.EXT;
				}
		
				$TYPE = new Typography(0); 
 				$TYPE->smileys = FALSE;
				$TYPE->highlight_code = TRUE;
				
				if ($PREFS->ini('enable_censoring') == 'y' && $PREFS->ini('censored_words') != '')
				{
					$subject = $TYPE->filter_censored_words($REGX->xss_clean($IN->GBL('subject')));
				}
				else
				{
					$subject = $REGX->xss_clean($IN->GBL('subject'));
				}

				$body = $TYPE->parse_type(stripslashes($REGX->xss_clean($IN->GBL('body'))),
													   array('text_format'   => 'none',
													   		 'html_format'   => 'none',
													   		 'auto_links'    => 'n',
													   		 'allow_img_url' => 'n'
													   		 ));
				
				if ( ! class_exists('EEmail'))
				{
					require PATH_CORE.'core.email'.EXT;
				}
				
				$email = new EEmail;
				$email->wordwrap = true;
				
				$swap = array(
							  'sender_name'			=> $SESS->userdata('screen_name'),
							  'message_subject'		=> $subject, 
							  'message_content'		=> $body,
							  'site_name'			=> stripslashes($PREFS->ini('site_name')),
							  'site_url'			=> $PREFS->ini('site_url')
							  );
				
				$template = $FNS->fetch_email_template('private_message_notification');
				$email_tit = $FNS->var_swap($template['title'], $swap);
				$email_msg = $FNS->var_swap($template['data'], $swap);
			
				foreach($query->result as $row)
				{	
					$email->initialize();
					$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
					$email->to($row['email']); 
					$email->subject($email_tit);	
					$email->message($REGX->entities_to_ascii($FNS->var_swap($email_msg, array('recipient_name' => $row['screen_name']))));		
					$email->Send();
				}
			}
		}
		
		/** -------------------------------------
		/**  Sent Copy?
		/** -------------------------------------*/
		
		if ($status == 'sent' && $data['message_sent_copy'] == 'y')
		{
			$copy_data['recipient_id'] 		= $this->member_id;
			$copy_data['message_authcode']	= $FNS->random('alpha', 10);
			$copy_data['message_folder']	= '2';  // Sent Message Folder
			$copy_data['message_read']		= 'y';  // Already read automatically
			$DB->query($DB->insert_string('exp_message_copies', $copy_data));
		}
		
		/** -------------------------------------
		/**  Replying or Forwarding?
		/** -------------------------------------*/
		
		if ($status == 'sent' && ($IN->GBL('replying') !== FALSE OR $IN->GBL('forwarding') !== FALSE))
		{
			$copy_id = ($IN->GBL('replying') !== FALSE) ? $IN->GBL('replying') : $IN->GBL('forwarding');
			$status  = ($IN->GBL('replying') !== FALSE) ? 'replied' : 'forwarded';
			
			$DB->query("UPDATE exp_message_copies SET message_status = '{$status}' WHERE copy_id = '{$copy_id}'");
		}
		
		/** -------------------------------------
		/**  Correct Member ID for Attachments
		/** -------------------------------------*/
		
		if (sizeof($this->attachments) > 0)
		{
			$DB->query("UPDATE exp_message_attachments SET message_id = '{$message_id}' 
						WHERE attachment_id IN ('".implode("','", $this->attachments)."')");
		}
		
		/** -------------------------------------
		/**  Remove Temp Status for Attachments
		/** -------------------------------------*/
		
		if ($status == 'sent')
		{	
			$DB->query("UPDATE exp_message_attachments SET is_temp = 'n' WHERE message_id = '{$message_id}'");
		}
		
		/** -------------------------------------
		/**  Redirect Them
		/** -------------------------------------*/
		
		if ($status == 'preview')
		{
			return $this->compose($message_id, $submission_error);
		}
		elseif($status == 'draft')
		{
			$this->drafts();
		}
		else
		{
			$FNS->redirect($this->_create_path('inbox'));
		}
    }
    /* END */

	
 	

}
/* END */
?>