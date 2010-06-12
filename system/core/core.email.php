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
 File: core.email.php
-----------------------------------------------------
 Purpose: Send email
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class EEmail {

	//	Public variables.

	var	$protocol		= "mail";				// mail/sendmail/smtp
	var	$mailpath		= "/usr/sbin/sendmail";	// Sendmail path
	var	$smtp_host		= "";					// SMTP Server.  Example: mail.earthlink.net
	var	$smtp_user		= "";					// SMTP Username
	var	$smtp_pass		= "";					// SMTP Password
	var	$smtp_auth		= false;				// true/false.  Does SMTP require authentication?
	var	$smtp_port		= "25";					// SMTP Port
	var	$smtp_timeout	= 5;					// SMTP Timeout in seconds
	var	$debug			= false;				// true/false.  True displays messages, false does not
	var	$wordwrap		= false;				// true/false  Turns word-wrap on/off
	var	$wrapchars		= "76";					// Number of characters to wrap at.
	var	$mailtype		= "text";				// text/html  Defines email formatting
	var	$charset		= "utf-8";				// Default char set: iso-8859-1 or us-ascii
	var	$encoding		= "8bit";				// Default bit depth (8bit = non-US char sets)
	var	$multipart		= "mixed";				// "mixed" (in the body) or "related" (separate)
	var	$validate		= false;				// true/false.  Enables email validation
	var	$priority		= "3";					// Default priority (1 - 5)
	var	$newline		= "\n";					// Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)

	var $crlf			= "\n";					// The RFC 2045 compliant CRLF for quoted-printable is "\r\n".  Apparently some servers,
												// even on the receiving end think they need to muck with CRLFs, so using "\n", while
												// distasteful, is the only one that seems to work for all environments. - Derek
												
	var	$bcc_batch_mode	= false;				// true/false  Turns on/off Bcc batch feature
	var	$bcc_batch_tot	= 250;					// If bcc_batch_mode = true, sets max number of Bccs in each batch
	var $safe_mode		= FALSE;				// TRUE/FALSE - when servers are in safe mode they can't use the 5th parameter of mail()
	var $send_multipart	= TRUE;					// TRUE/FALSE - Yahoo does not like multipart alternative, so this is an override.  Set to FALSE for Yahoo.
	
	//-------------------------------------------------------------------------------------------	
	//	Private variables.  Do not modify

	var	$subject		= "";
	var	$body			= "";
	var $plaintext_body	= "";
	var	$finalbody		= "";
	var	$alt_boundary	= "";
	var	$atc_boundary	= "";
	var	$header_str		= "";
	var	$smtp_connect	= "";
	var	$useragent		= "";
	var $replyto_flag	= FALSE;
	var	$debug_msg		= array();
	var	$recipients		= array();
	var	$cc_array		= array();
	var	$bcc_array		= array();
	var	$headers		= array();
	var	$attach_name	= array();
	var	$attach_type	= array();
	var	$attach_disp	= array();
	var	$protocols		= array('mail', 'sendmail', 'smtp');
	var	$base_charsets	= array('iso-8859-1', 'us-ascii');
	var	$bit_depths		= array('7bit', '8bit');
	var	$priorities		= array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

	
	// END VARIABLES ----------------------------------------------------------------------------
	


	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	
	function EEmail($init = TRUE)
	{
		global $PREFS;
		
		if ($init != TRUE)
			return;
			
		$this->useragent = APP_NAME.' '.APP_VER;
		$this->initialize();
		$this->set_config_values();		
	}	
	/* END */


	/** -------------------------------------
	/**  Set config values
	/** -------------------------------------*/

	function set_config_values()
	{
		global $PREFS;
		
		$this->protocol = ( ! in_array( $PREFS->ini('mail_protocol'), $this->protocols)) ? 'mail' : $PREFS->ini('mail_protocol');
		$this->charset = ($PREFS->ini('email_charset') == '') ? 'utf-8' : $PREFS->ini('email_charset');
		$this->smtp_host = $PREFS->ini('smtp_server');
		$this->smtp_user = $PREFS->ini('smtp_username');
		$this->smtp_pass = $PREFS->ini('smtp_password');
		
		$this->safe_mode = (@ini_get("safe_mode") == 0) ? FALSE : TRUE;
		
        $this->smtp_auth = ( ! $this->smtp_user AND ! $this->smtp_pass) ? FALSE : TRUE;	
        
		$this->debug = ($PREFS->ini('email_debug') == 'y') ? TRUE : FALSE;
		
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- email_newline => Default newline.
		/*  - email_crlf => CRLF used in quoted-printable encoding
        /* -------------------------------------------*/
		
		$this->newline = ($PREFS->ini('email_newline') !== FALSE) ? $PREFS->ini('email_newline') : $this->newline;
		$this->crlf = ($PREFS->ini('email_crlf') !== FALSE) ? $PREFS->ini('email_crlf') : $this->crlf;
	}
	/* END */


	/** -------------------------------------
	/**  Initialize Variables
	/** -------------------------------------*/

	function initialize()
	{
		$this->subject		= "";
		$this->body			= "";
		$this->finalbody	= "";
		$this->header_str	= "";
		$this->replyto_flag = FALSE;
		$this->recipients	= array();
		$this->headers		= array();
		$this->debug_msg	= array();
		
		$this->add_header('User-Agent', $this->useragent);				
		$this->add_header('Date', $this->set_date());
	}
	/* END */


	/** -------------------------------------
	/**  From
	/** -------------------------------------*/
	 
	function from($from, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $from, $match))
			$from = $match['1'];

		if ($this->validate)
			$this->validate_email($this->str_to_array($from));
		
		// prepare the display name
		if ($name != '')
		{
			$name = stripslashes($name);

			// only use Q encoding if there are characters that would require it
			if ( ! preg_match('/[\200-\377]/', $name))
			{
				// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			}
			else
			{
				$name = $this->prep_q_encoding($name, TRUE);
			}
		}

		$this->add_header('From', $name.' <'.$from.'>');
		$this->add_header('Return-Path', '<'.$from.'>');
	}
	/* END */


	/** -------------------------------------
	/**  Reply To
	/** -------------------------------------*/
	 
	function reply_to($replyto, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $replyto, $match))
			$replyto = $match['1'];

		if ($this->validate)
			$this->validate_email($this->str_to_array($replyto));	

		if ($name == '')
		{
			$name = $replyto;
		}

		if (substr($name, 0, 1) != '"')
		{
			$name = '"'.$name.'"';
		}

		$this->add_header('Reply-To', $name.' <'.$replyto.'>');
		$this->replyto_flag = TRUE;
	}
	/* END */


	/** -------------------------------------
	/**  Recipients
	/** -------------------------------------*/
		
	function to($to)
	{
		$to = $this->str_to_array($to);
		
		$to = $this->clean_email($to);
	
		if ($this->validate)
			$this->validate_email($to);
			
		if ($this->get_protocol() != 'mail')
			$this->add_header('To', implode(", ", $to));

		switch ($this->get_protocol())
		{
			case 'smtp'		: $this->recipients = $to;
			break;
			case 'sendmail'	: $this->recipients = implode(", ", $to);
			break;
			case 'mail'		: $this->recipients = implode(", ", $to);
			break;
		}	
	}
	/* END */


	/** -------------------------------------
	/**  Cc
	/** -------------------------------------*/
	
	function cc($cc)
	{	
		$cc = $this->str_to_array($cc);
		
		$cc = $this->clean_email($cc);

		if ($this->validate)
			$this->validate_email($cc);

		$this->add_header('Cc', implode(", ", $cc));
		
		if ($this->get_protocol() == "smtp")
			$this->cc_array = $cc;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Bcc
	/** -------------------------------------*/

	function bcc($bcc, $limit = '')
	{
		if ($limit != '' && is_numeric($limit))
		{
			$this->bcc_batch_mode = true;

			$this->bcc_batch_tot = $limit;
		}

		$bcc = $this->str_to_array($bcc);

		$bcc = $this->clean_email($bcc);
		
		if ($this->validate)
			$this->validate_email($bcc);

		if (($this->get_protocol() == "smtp") || ($this->bcc_batch_mode && count($bcc) > $this->bcc_batch_tot))
			$this->bcc_array = $bcc;
		else
			$this->add_header('Bcc', implode(", ", $bcc));
	}
	/* END */


	/** -------------------------------------
	/**  Message subject
	/** -------------------------------------*/
	 
	function subject($subject)
	{
		$subject = $this->prep_q_encoding($subject);
		$this->add_header('Subject', $subject);
	}
	/* END */


	/** -------------------------------------
	/**  Message body
	/** -------------------------------------*/
	 
	function message($body, $alt = '')
	{
		global $FNS;
		
		$body = $FNS->insert_action_ids($body);
		$body = rtrim(str_replace("\r", "", $body));
		
		if ($alt != '')
		{
			$alt = $FNS->insert_action_ids($alt);
			$alt = rtrim(str_replace("\r", "", $alt));
		}
		
		if ($this->wordwrap === TRUE  AND  $this->mailtype != 'html')
			$this->body = $this->word_wrap($body);
		else
			$this->body = $body;	
		
		if ($this->mailtype == 'html')
		{
			$this->plaintext_body = ($alt == '') ? $this->prep_quoted_printable(stripslashes($body)) : $this->prep_quoted_printable($alt);
			$this->body = $this->prep_quoted_printable($body);
		}
		
		$this->body = stripslashes($this->body);
	}
	/* END */
	

	/** -------------------------------------
	/**  Add header item
	/** -------------------------------------*/

	function add_header($header, $value)
	{
		$this->headers[$header] = $value;
	}
	/* END */


	/** -------------------------------------------
	/**  Convert sring into an array
	/** -------------------------------------------*/

	function str_to_array($email)
	{
		if ( ! is_array($email))
		{
			if (strpos($email, ',') !== FALSE)
			{
				$email = preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY);
			}
			else
			{
				$email = trim($email);
				settype($email, "array");
			}
		}
		
		return $email;
	}
	/* END */


	/** -------------------------------------
	/**  Set boundaries
	/** -------------------------------------*/

	function set_boundaries()
	{
		$this->alt_boundary = "B_ALT_".uniqid(''); // mulipart/alternative
		$this->atc_boundary = "B_ATC_".uniqid(''); // attachment boundary
	}
	/* END */


	/** -------------------------------------
	/**  Set Message ID
	/** -------------------------------------*/

	function set_message_id()
	{
		$from = $this->headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);
	
        return  "<".uniqid('').strstr($from, '@').">";	        
	}
	/* END */


	/** -------------------------------------
	/**  Get Debug value
	/** -------------------------------------*/

	function get_debug()
	{
		return $this->debug;
	}
	/* END */

		
	/** -------------------------------------
	/**  Get protocol (mail/sendmail/smtp)
	/** -------------------------------------*/

	function get_protocol($return = true)
	{
		$this->protocol = strtolower($this->protocol);
	
		$this->protocol = ( ! in_array($this->protocol, $this->protocols)) ? 'mail' : $this->protocol;
		
		if ($return == true) return $this->protocol;
	}
	/* END */
	

	/** -------------------------------------
	/**  Get mail encoding (7bit/8bit)
	/** -------------------------------------*/

	function get_encoding($return = true)
	{		
		$this->encoding = ( ! in_array($this->encoding, $this->bit_depths)) ? '7bit' : $this->encoding;
		
		if ( ! in_array($this->charset, $this->base_charsets)) 
			$this->encoding = "8bit";
			
		if ($return == true) return $this->encoding;
	}
	/* END */
	
	
	/** -----------------------------------------
	/**  Get content type (text/html/attachment)
	/** -----------------------------------------*/

	function get_content_type()
	{	
			if	($this->mailtype == 'html' &&  count($this->attach_name) == 0)
				return 'html';
	
		elseif	($this->mailtype == 'html' &&  count($this->attach_name)  > 0)
				return 'html-attach';				
				
		elseif	($this->mailtype == 'text' &&  count($this->attach_name)  > 0)
				return 'plain-attach';
				
		  else	return 'plain';
	}
	/* END */


	/** -------------------------------------
	/**  Set RFC 822 Date
	/** -------------------------------------*/
	 
	function set_date()
	{
		$timezone = date("Z");
		
		$operator = (substr($timezone, 0, 1) == '-') ? '-' : '+';
		
		$timezone = abs($timezone);
			
		$timezone = ($timezone/3600) * 100 + ($timezone % 3600) /60;
		
		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}
	/* END */


	/** -------------------------------------
	/**  Mime message
	/** -------------------------------------*/

	function mime_message()
	{
		return "This is a multi-part message in MIME format.".$this->newline."Your email application may not support this format.";
	}
	/* END */


	/** -------------------------------------
	/**  Validate Email Address
	/** -------------------------------------*/
	 
	function validate_email($email)
	{
		global $REGX;
	
		if ( ! is_array($email))
		{
			if ($this->get_debug())
				$this->add_error_message("The email validation method must be passed an array.");
					
			return FALSE;
		}

		foreach ($email as $val)
		{
			if ( ! $REGX->valid_email($val)) 
			{
				if ($this->get_debug())
					$this->add_error_message("Invalid Address: ". $val);
				
				return FALSE;
			}
		}
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Email Validation
	/** -------------------------------------*/

	function valid_email($address)
	{
		if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address))
			return false;
		else 
			return true;
	}
	/* END */


	/** ---------------------------------------------------------
	/**  Clean Extended Email Address: Joe Smith <joe@smith.com>
	/** ---------------------------------------------------------*/
	 
	function clean_email($email)
	{
		if ( ! is_array($email))
		{
			if (preg_match('/\<(.*)\>/', $email, $match))
           		return $match['1'];
           	else
           		return $email;
		}
			
		$clean_email = array();

		for ($i=0; $i < count($email); $i++) 
		{
			if (preg_match( '/\<(.*)\>/', $email[$i], $match))
           		$clean_email[] = $match['1'];
           	else
           		$clean_email[] = $email[$i];
		}
		
		return $clean_email;
	}
	/* END */
	
	
	/** ------------------------------------------------
	/**  Strip HTML from message body
	/** ------------------------------------------------*/
	//	This function provides the raw message for use
	//	in plain-text headers of HTML-formatted emails

	function strip_html()
	{
		$body = ($this->plaintext_body != '') ? $this->plaintext_body : $this->body;
		
		if (preg_match('@\<body(.*)\</body\>@i', $body, $match))
		{
			$body = $match['1'];
		
			$body = substr($body, strpos($body, ">") + 1);
		}
		
		$body = trim(strip_tags($body));

		$body = preg_replace( '#<!--(.*)--\>#', "", $body);
		
		$body = str_replace("\t", "", $body);
		
		for ($i = 20; $i >= 3; $i--)
		{
			$n = "";
			
			for ($x = 1; $x <= $i; $x ++)
				 $n .= $this->newline;
		
			$body = str_replace($n, $this->newline.$this->newline, $body);	
		}

		return $this->word_wrap($body, '76');
	}
	/* END */
	

	/** -------------------------------------
	/**  Word Wrap
	/** -------------------------------------*/

	function word_wrap($str, $charlim = '')
	{
		// Set the character limit
		if ($charlim == '')
		{
			$charlim = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		}
		
		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);
		
		// Standardize newlines
		$str = preg_replace("/\r\n|\r/", "\n", $str);
		
		// If the current word is surrounded by {unwrap} tags we'll 
		// strip the entire chunk and replace it with a marker.
		$unwrap = array();
		if (preg_match_all("|(\{unwrap\}.+?\{/unwrap\})|s", $str, $matches))
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{
				$unwrap[] = $matches['1'][$i];				
				$str = str_replace($matches['1'][$i], "{{unwrapped".$i."}}", $str);
			}
		}
		
		// Use PHP's native function to do the initial wordwrap.  
		// We set the cut flag to FALSE so that any individual words that are 
		// too long get left alone.  In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", FALSE);
		
		// Split the string into individual lines of text and cycle through them
		$output = "";
		foreach (explode("\n", $str) as $line) 
		{
			// Is the line within the allowed character count?
			// If so we'll join it to the output and continue
			if (strlen($line) <= $charlim)
			{
				$output .= $line.$this->newline;			
				continue;
			}
				
			$temp = '';
			while((strlen($line)) > $charlim) 
			{
				// If the over-length word is a URL we won't wrap it
				if (preg_match("!\[url.+\]|://|wwww.!", $line))
				{
					break;
				}

				// Trim the word down
				$temp .= substr($line, 0, $charlim-1);
				$line = substr($line, $charlim-1);
			}
			
			// If $temp contains data it means we had to split up an over-length 
			// word into smaller chunks so we'll add it back to our current line
			if ($temp != '')
			{
				$output .= $temp.$this->newline.$line;
			}
			else
			{
				$output .= $line;
			}

			$output .= $this->newline;
		}

		// Put our markers back
		if (count($unwrap) > 0)
		{	
			foreach ($unwrap as $key => $val)
			{
				$output = str_replace("{{unwrapped".$key."}}", $val, $output);
			}
		}

		return $output;	
	}
	/* END */

	function prep_quoted_printable($str, $charlim = '')
	{
		// Set the character limit
		// Don't allow over 76, as that will make servers and MUAs barf
		// all over quoted-printable data
		if ($charlim == '' OR $charlim > '76')
		{
			$charlim = '76';
		}

		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		// Standardize newlines
		$str = preg_replace("/\r\n|\r/", "\n", $str);
		
		// kill nulls
		$str = preg_replace('/\x00+/', '', $str);
		
		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);
		
		// Break into an array of lines
		$lines = preg_split("/\n/", $str);

	    $escape = '=';
	    $output = '';

		foreach ($lines as $line)
		{
			$length = strlen($line);
			$temp = '';

			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output
			for ($i = 0; $i < $length; $i++)
			{
				// Grab the next character
				$char = substr($line, $i, 1);
				$ascii = ord($char);
				
				// Convert spaces and tabs but only if it's the end of the line
				if ($i == ($length - 1))
				{
					$char = ($ascii == '32' OR $ascii == '9') ? $escape.sprintf('%02s', dechex($ascii)) : $char;
				}

				// encode = signs
				if ($ascii == '61')
				{
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
				}

				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ((strlen($temp) + strlen($char)) >= $charlim)
				{
					$output .= $temp.$escape.$this->crlf;
					$temp = '';
				}

				// Add the character to our temporary line
				$temp .= $char;
			}

			// Add our completed line to the output
			$output .= $temp.$this->crlf;
		}

		// get rid of extra CRLF tacked onto the end
		$output = substr($output, 0, strlen($this->crlf) * -1);

		return $output;
	}
	/* END */
	
	
	function prep_q_encoding($str, $from = FALSE)
	{
		global $PREFS;
		
		$str = str_replace(array("\r", "\n"), array('', ''), trim($str));

		// Line length must not exceed 76 characters, so we adjust for
		// a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($PREFS->ini('charset'));

		// these special characters must be converted too
		$convert = array('_', '=', '?');
		
		if ($from === TRUE)
		{
			$convert[] = ',';
			$convert[] = ';';
		}
		
		$output = '';
		$temp = '';

		for ($i = 0, $length = strlen($str); $i < $length; $i++)
		{
			// Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);
			
			// convert ALL non-printable ASCII characters and our specials
			if ($ascii < 32 OR $ascii > 126 OR in_array($char, $convert))
			{
				$char = '='.dechex($ascii);
			}
			
			// handle regular spaces a bit more compactly than =20
			if ($ascii == 32)
			{
				$char = '_';
			}
			
			// If we're at the character limit, add the line to the output,
			// reset our temp variable, and keep on chuggin'
			if ((strlen($temp) + strlen($char)) >= $limit)
			{
				$output .= $temp.$this->crlf;
				$temp = '';
			}

			// Add the character to our temporary line
			$temp .= $char;
		}
		
		$str = $output.$temp;
		
		// wrap each line with the shebang, charset, and transfer encoding
		// the preceding space on successive lines is required for header "folding"
		$str = trim(str_replace($this->crlf, "\n", $str));
		$str = trim(preg_replace('/^(.*)$/m', ' =?'.$PREFS->ini('charset').'?Q?$1?=', $str));

		if ($this->get_protocol() == 'mail')
		{
			// mail() will replace any control character besides CRLF with a space
			// so we need to force those line endings in that case
			$str = trim(str_replace("\n", "\r\n", $str));			
		}
		else
		{
			$str = trim(str_replace("\n", $this->crlf, $str));			
		}

		return $str;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Assign file attachments
	/** -------------------------------------*/
	
	function attach($filename, $disposition = 'attachment')
	{			
		$this->attach_name[] = $filename;
		
		$this->attach_type[] = $this->mime_types(next(explode('.', basename($filename))));
		
		$this->attach_disp[] = $disposition; // Can also be 'inline'  Not sure if it matters 
	}
	/* END */


	/** -------------------------------------
	/**  Build final headers
	/** -------------------------------------*/

	function build_headers()
	{
		$this->add_header('X-Sender', $this->clean_email($this->headers['From']));
		$this->add_header('X-Mailer', $this->useragent);		
		$this->add_header('X-Priority', $this->priorities[$this->priority - 1]);
		$this->add_header('Message-ID', $this->set_message_id());		
		$this->add_header('Mime-Version', '1.0');
	}
	/* END */


	/** -------------------------------------
	/**  Write Headers as a string
	/** -------------------------------------*/
	
	function write_header_string()
	{
		if ($this->protocol == 'mail')
		{		
			$this->subject = $this->headers['Subject'];
			
			unset($this->headers['Subject']);
		}	

		reset($this->headers);
		
		$this->header_str = "";
				
		foreach($this->headers as $key => $val) 
		{
			$val = trim($val);
		
			if ($val != "")
			{
				$this->header_str .= $key.": ".$val.$this->newline;
			}
		}
		
		if ($this->get_protocol() == 'mail')
		{
			$this->header_str = rtrim($this->header_str);
		}
	}
	/* END */


	/** -------------------------------------
	/**  Build Final Body and attachments
	/** -------------------------------------*/

	function build_finalbody()
	{
		$this->set_boundaries();
		
		$this->write_header_string();
		
		$hdr = ($this->get_protocol() == 'mail') ? $this->newline : '';

		switch ($this->get_content_type())
		{
			case 'plain' :
							
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding();

				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;
					$this->finalbody = $this->body;
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline . $this->body;
				
				$this->finalbody = $hdr;
						
				return;
			
			break;
			case 'html' :
							
				if ($this->send_multipart === FALSE)
				{
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				}
				else
				{
					$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->newline . $this->newline;
					$hdr .= $this->mime_message() . $this->newline . $this->newline;
					$hdr .= "--" . $this->alt_boundary . $this->newline;
					
					$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: quoted-printable" . $this->newline . $this->newline;
					$hdr .= $this->strip_html() . $this->newline . $this->newline . "--" . $this->alt_boundary . $this->newline;
				
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				}
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;
					$this->finalbody = $this->body . $this->newline . $this->newline;
					
					if ($this->send_multipart !== FALSE)
					{
						$this->finalbody .= "--" . $this->alt_boundary . "--";
					}
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline;
				
				if ($this->send_multipart !== FALSE)
				{
					$hdr .= "--" . $this->alt_boundary . "--";
				}

				$this->finalbody = $hdr;
				
				return;
		
			break;
			case 'plain-attach' :
	
				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->atc_boundary."\"" . $this->newline . $this->newline;
				$hdr .= $this->mime_message() . $this->newline . $this->newline;
				$hdr .= "--" . $this->atc_boundary . $this->newline;
	
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding();
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;		
					
					$body  = $this->body . $this->newline . $this->newline;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline;

			break;
			case 'html-attach' :
			
				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->atc_boundary."\"" . $this->newline . $this->newline;
				$hdr .= $this->mime_message() . $this->newline . $this->newline;
				$hdr .= "--" . $this->atc_boundary . $this->newline;
	
				$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->newline .$this->newline;
				$hdr .= "--" . $this->alt_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: quoted-printable" . $this->newline . $this->newline;
				$hdr .= $this->strip_html() . $this->newline . $this->newline . "--" . $this->alt_boundary . $this->newline;
	
				$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: quoted-printable";
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;	
					
					$body  = $this->body . $this->newline . $this->newline; 
					$body .= "--" . $this->alt_boundary . "--" . $this->newline . $this->newline;				
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline;
				$hdr .= "--" . $this->alt_boundary . "--" . $this->newline . $this->newline;

			break;
		}

		$attachment = array();

		$z = 0;
		
		for ($i=0; $i < count($this->attach_name); $i++)
		{
			$filename = $this->attach_name[$i];
			
			$basename = basename($filename);
			
			$ctype = $this->attach_type[$i];
						
			if (!file_exists($filename))
			{
				$this->add_error_message("Unable to locate this attachment: ".$filename); 
			}			

			$h  = "--".$this->atc_boundary.$this->newline;
			$h .= "Content-type: ".$ctype."; ";
			$h .= "name=\"".$basename."\"".$this->newline;
			$h .= "Content-Disposition: ".$this->attach_disp[$i].";".$this->newline;
			$h .= "Content-Transfer-Encoding: base64".$this->newline;

			$attachment[$z++] = $h;
			
			$file = filesize($filename) +1;
			
			$fp = fopen($filename, 'r');
			
			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
				
			fclose($fp);
		}

		if ($this->get_protocol() == 'mail')
		{
			$this->finalbody = $body . implode($this->newline, $attachment).$this->newline."--".$this->atc_boundary."--";	
			
			return;
		}
		
		$this->finalbody = $hdr.implode($this->newline, $attachment).$this->newline."--".$this->atc_boundary."--";	
		
		return;	
	}
	/* END */
		

	/** -------------------------------------
	/**  Send Email
	/** -------------------------------------*/

	function send()
	{	
		// Was the reply-to header set?
		// If not we'll do it now...
		
		if ($this->replyto_flag == FALSE)
		{
			$this->reply_to($this->headers['From']);
		}
	
		if (( ! isset($this->recipients) AND ! isset($this->headers['To']))  AND
			( ! isset($this->bcc_array) AND ! isset($this->headers['Bcc'])) AND
			( ! isset($this->headers['Cc'])))
		{
			if ($this->get_debug())
					$this->error_message("You must include recipients: To, Cc, or Bcc");
					
			return FALSE;
		}

		$this->build_headers();
		
		if ($this->bcc_batch_mode  AND  count($this->bcc_array) > 0)
		{		
			if (count($this->bcc_array) > $this->bcc_batch_tot)
				return $this->batch_bcc_send();
		}
		
		$this->build_finalbody();
						
		if ( ! $this->mail_spool())
			return false;
		else
			return true;
	}
	/* END */
	
	
	/** --------------------------------------------------
	/**  Batch Bcc Send.  Sends groups of Bccs in batches
	/** --------------------------------------------------*/

	function batch_bcc_send()
	{
		$float = $this->bcc_batch_tot -1;
		
		$flag = 0;
		
		$set = "";
		
		$chunk = array();		
		
		for ($i = 0; $i < count($this->bcc_array); $i++)
		{
			if (isset($this->bcc_array[$i]))
				$set .= ", ".$this->bcc_array[$i];
		
			if ($i == $float)
			{	
				$chunk[] = substr($set, 1);
				
				$float = $float + $this->bcc_batch_tot;
						
				$set = "";
			}
			
			if ($i == count($this->bcc_array)-1)
					$chunk[] = substr($set, 1);	
		}

		for ($i = 0; $i < count($chunk); $i++)
		{
			unset($this->headers['Bcc']);

			unset($bcc);

			$bcc = $this->str_to_array($chunk[$i]);

			$bcc = $this->clean_email($bcc);
	
			if ($this->protocol != 'smtp')
				$this->add_header('Bcc', implode(", ", $bcc));
			else
				$this->bcc_array = $bcc;
			
			$this->build_finalbody();

			$this->mail_spool();		
		}
	}
	/* END */


	/** -------------------------------------
	/**  Unwrap special elements
	/** -------------------------------------*/

    function unwrap_specials()
    {
        $this->finalbody = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, 'remove_nl_callback'), $this->finalbody);
    }
    /* END */


	/** -------------------------------------
	/**  Strip line-breaks via callback
	/** -------------------------------------*/

    function remove_nl_callback($matches)
    {
        return preg_replace("/(\r\n)|(\r)|(\n)/", "", $matches['1']);    
    }
    /* END */


	/** -------------------------------------
	/**  Spool mail to the mail server
	/** -------------------------------------*/

	function mail_spool()
	{
	    $this->unwrap_specials();

		switch ($this->get_protocol())
		{
			case 'mail'	:
			
					if ( ! $this->send_with_mail())
					{
						if ($this->get_debug())
							$this->add_error_message("Unable to send email using PHP mail().  Your server might not be configured to send mail using this method.");
							
						return FALSE;
					}
			break;
			case 'sendmail'	: 
								
					if ( ! $this->send_with_sendmail())
					{
						if ($this->get_debug())
							$this->add_error_message("Unable to send email using Sendmail");
							
						return FALSE;
					}
			break;
			case 'smtp'	: 
								
					if ( ! $this->send_with_smtp())
					{
						if ($this->get_debug())
							$this->add_error_message("Unable to send email using SMTP");
							
						return FALSE;
					}
			break;

		}

		$this->good_message("Your message has been successfully sent using ".$this->get_protocol());
			
		return true;
	}
	/* END */
	

	/** -------------------------------------
	/**  Send using mail()
	/** -------------------------------------*/

	function send_with_mail()
	{
		if ($this->safe_mode == TRUE)
		{
			if ( ! mail($this->recipients, $this->subject, $this->finalbody, $this->header_str))
				return false;
			else
				return true;		
		}
		else
		{
			if ( ! mail($this->recipients, $this->subject, $this->finalbody, $this->header_str, "-f ".$this->clean_email($this->headers['From'])))
				return false;
			else
				return true;
		}
	}
	/* END */


	/** -------------------------------------
	/**  Send using Sendmail
	/** -------------------------------------*/

	function send_with_sendmail()
	{
		$fp = @popen($this->mailpath . " -oi -f ".$this->clean_email($this->headers['From'])." -t", 'w');
		
		if ($fp === FALSE OR $fp === NULL)
		{
			// server probably has popen disabled, so nothing we can do to get a verbose error.
			return FALSE;
		}
		
		fputs($fp, $this->header_str);		
		fputs($fp, $this->finalbody);
		
	    $status = pclose($fp);
	    
		if (version_compare(PHP_VERSION, '4.2.3') == -1)
		{
			$status = $status >> 8 & 0xFF;
	    }
		
		if ($this->get_debug())
		{
			$this->add_error_message('Status: '.$status.'.');	
		}

		if ($status != 0)
		{
			if ($this->get_debug())
			{
				$this->add_error_message("Status: {$status} - Unable to open a socket to Sendmail. Please check settings.");
			}

			return FALSE;
		}
		
		return TRUE;
	}
	/* END */


	/** -------------------------------------
	/**  Send using SMTP
	/** -------------------------------------*/

	function send_with_smtp()
	{	
	    if ($this->smtp_host == '')
	    {	
			if ($this->get_debug())
					$this->add_error_message('You did not specify a SMTP hostname');
		
			return FALSE;
		}

		$this->smtp_connect();
		
		$this->smtp_authenticate();
		
		$this->send_command('from', $this->clean_email($this->headers['From']));

		foreach($this->recipients as $val)
			$this->send_command('to', $val);
			
		if (count($this->cc_array) > 0)
		{
			foreach($this->cc_array as $val)
			{
				if ($val != "")
				$this->send_command('to', $val);
			}
		}

		if (count($this->bcc_array) > 0)
		{
			foreach($this->bcc_array as $val)
			{
				if ($val != "")
				$this->send_command('to', $val);
			}
		}
		
		$this->send_command('data');
		
		
		// $this->send_data($this->header_str . $this->newline . $this->finalbody);
		
		// perform dot transformation on any lines that begin with a dot
		$this->send_data($this->header_str . preg_replace('/^\./m', '..$1', $this->finalbody));
		
		$this->send_data('.');

		$reply = $this->get_data();
		
		$this->good_message($reply);			

		if (substr($reply, 0, 3) != '250')
		{
			if ($this->get_debug())
				$this->add_error_message('Failed to send SMTP email. Error: '.$reply);
			
			return FALSE;
		}

		$this->send_command('quit');
		
		return true;
	}
	/* END */
	

	/** -------------------------------------
	/**  SMTP Connect
	/** -------------------------------------*/

	function smtp_connect()
	{
	
		$this->smtp_connect = fsockopen($this->smtp_host, 
										$this->smtp_port,
										$errno, 
										$errstr, 
										$this->smtp_timeout);

		if( ! is_resource($this->smtp_connect))
		{								
			if ($this->get_debug())
				$this->add_error_message("Unable to open SMTP socket. Error Number: ".$errno." Error Msg: ".$errstr);
				
			return FALSE;
		}

		$this->good_message($this->get_data());

		return $this->send_command('hello');
	}
	/* END */


	/** -------------------------------------
	/**  Send SMTP command
	/** -------------------------------------*/

	function send_command($cmd, $data = '')
	{
		switch ($cmd)
		{
			case 'hello' :
		
					if ($this->smtp_auth || $this->get_encoding() == '8bit')
						$this->send_data('EHLO '.$this->get_hostname());
					else
						$this->send_data('HELO '.$this->get_hostname());
						
						$resp = 250;
			break;
			case 'from' :
			
						$this->send_data('MAIL FROM:<'.$data.'>');

						$resp = 250;
			break;
			case 'to'	:
			
						$this->send_data('RCPT TO:<'.$data.'>');

						$resp = 250;			
			break;
			case 'data'	:
			
						$this->send_data('DATA');

						$resp = 354;			
			break;
			case 'quit'	:
		
						$this->send_data('QUIT');
						
						$resp = 221;
			break;
		}
		
		$reply = $this->get_data();	
		
		if ($this->get_debug())
			$this->debug_msg[] = "<pre>".$cmd.": ".$reply."</pre>";

		if (substr($reply, 0, 3) != $resp)
		{
			if ($this->get_debug())
				$this->add_error_message('Failed to Send Command. Error: '.$reply);
				
			return FALSE;
		}
			
		if ($cmd == 'quit')
			fclose($this->smtp_connect);
	
		return true;
	}
	/* END */


	/** -------------------------------------
	/**  SMTP Authenticate
	/** -------------------------------------*/

	function smtp_authenticate()
	{	
		if ( ! $this->smtp_auth)
			return true;
			
		if ($this->smtp_user == ""  AND  $this->smtp_pass == "")
		{
			if ($this->get_debug())
				$this->add_error_message('Error: You must assign an SMTP username and password.');
			return FALSE;
		}

		
		$this->send_data('AUTH LOGIN');

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '334')
		{
			if ($this->get_debug())
				$this->add_error_message('Failed to send AUTH LOGIN command. Error: '.$reply);
			
			return FALSE;
		}

		$this->send_data(base64_encode($this->smtp_user));

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '334')
		{
			if ($this->get_debug())
				$this->add_error_message('Failed to authenticate username. Error: '.$reply);
			
			return FALSE;
		}

		$this->send_data(base64_encode($this->smtp_pass));

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '235')
		{
			if ($this->get_debug())
				$this->add_error_message('Failed to authenticate password. Error: '.$reply);
			
			return FALSE;
		}
	
		return true;
	}
	/* END */


	/** -------------------------------------
	/**  Send SMTP data
	/** -------------------------------------*/

	function send_data($data)
	{
		if ( ! fwrite($this->smtp_connect, $data . $this->newline))
		{
			if ($this->get_debug())
				$this->add_error_message('Unable to send data: '.$data);
			
			return FALSE;
		}
		else
			return true;
	}
	/* END */


	/** -------------------------------------
	/**  Get SMTP data
	/** -------------------------------------*/

	function get_data()
	{
        $data = "";
    
		while ($str = fgets($this->smtp_connect, 512)) 
		{            
			$data .= $str;
			
			if (substr($str, 3, 1) == " ")
				break; 	
    	}
    	
    	return $data;
	}
	/* END */


	/** -------------------------------------
	/**  Get Hostname
	/** -------------------------------------*/
	function get_hostname()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';	
	}
	/* END */


	/** -------------------------------------
	/**  Add error Message
	/** -------------------------------------*/

	function add_error_message($msg)
	{					
		$this->debug_msg[] = $msg.'<br />';
		
		return FALSE;
	}
	/* END */
	
	

	/** -------------------------------------
	/**  Show Error Message
	/** -------------------------------------*/

	function show_error_message()
	{		
		$msg = '';
		
		if (count($this->debug_msg) > 0)
		{
			foreach ($this->debug_msg as $val)
			{
				$msg .= $val;
			}
		}
		
		return $msg;
	}
	/* END */
	
		
	/** -------------------------------------
	/**  Good Message
	/** -------------------------------------*/

	function good_message($msg)
	{					
		if ($this->get_debug())	
			$this->debug_msg[] = $msg."<br />";
	}
	/* END */

	/** -------------------------------------
	/**  Print Sent Message
	/** -------------------------------------*/

	function print_message()
	{		
		$this->debug_msg[] = 
			"<pre>".
			$this->header_str."\n".
			$this->subject."\n".
			$this->finalbody.
			"</pre>";
	}
	/* END */


	/** -------------------------------------
	/**  Mime Types
	/** -------------------------------------*/
	
	function mime_types($ext = "")
	{
		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'doc'	=>	'application/msword',
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class'	=>	'application/octet-stream',
						'psd'	=>	'application/octet-stream',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	'application/pdf',
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	'application/vnd.ms-excel',
						'ppt'	=>	'application/vnd.ms-powerpoint',
						'wbxml'	=>	'application/vnd.wap.wbxml',
						'wmlc'	=>	'application/vnd.wap.wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml'	=>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml'	=>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=>	'application/zip',
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	'image/jpeg',
						'jpg'	=>	'image/jpeg',
						'jpe'	=>	'image/jpeg',
						'png'	=>	'image/png',
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml'	=>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	'text/plain',
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie'	=>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	'application/msword',
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);

		return ( ! isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}
	/* END */
}
// END CLASS
?>