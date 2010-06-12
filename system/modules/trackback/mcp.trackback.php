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
 File: mcp.trackback.php
-----------------------------------------------------
 Purpose: Trackback class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Trackback_CP {

    var $version        = '1.1';
    var $tag            = "";
    var $insideitem     = false;
	var $tb_bad_urls    = array();
	var $tb_good_urls   = array();
    var $selected_urls  = array();
    var $convert_ascii  = 'y';

    /** -------------------------
    /**  Constructor
    /** -------------------------*/
    
    function Trackback_CP()
    {
    }
    /* END */
      
 
    /** ----------------------------------------
    /**  Send Trackback
    /** ----------------------------------------*/
    
	function send_trackback($tb_data)
	{
		global $REGX, $FNS, $PREFS;
		
		if ( ! is_array($tb_data))
		{ 
		    return false;
		}
		
		
        /** ----------------------------------------
        /**  Pre-process data
        /** ----------------------------------------*/
		
		$required = array('entry_id', 'entry_link', 'entry_title', 'entry_content', 'trackback_url', 'weblog_name', 'tb_format');
		
		foreach ($tb_data as $key => $val)
		{
		    if ( ! in_array($key, $required))
		    { 
		        return false;
		    }
		    
		    switch ($key)
		    {
		        case 'trackback_url' : $$key = $this->extract_trackback_urls($val);
		            break;
		        case 'entry_content' : $$key = $FNS->char_limiter($REGX->xml_convert(strip_tags(stripslashes($val))));
		            break;
		        case 'entry_link'	 : $$key = str_replace('&#45;', '-', $REGX->xml_convert(strip_tags(stripslashes($val))));
		        	break;
		        default              : $$key = $REGX->xml_convert(strip_tags(stripslashes($val)));
		            break;
		    }
		    
			/** ----------------------------------------
			/**  Convert High ASCII Characters
			/** ----------------------------------------*/
		
			if ($this->convert_ascii == 'y' OR $PREFS->ini('auto_convert_high_ascii') == 'y')
			{
				if ($key == 'entry_content')
				{
					$$key = $REGX->ascii_to_entities($$key);
				}
				elseif ($key == 'entry_title')
				{
					$$key = $REGX->ascii_to_entities($$key);
				}
				elseif($key == 'weblog_name')
				{
					$$key = $REGX->ascii_to_entities($$key);
				}
			}
		}
		
		/** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
      
        if ( ! class_exists('Typography'))
        {
            require PATH_CORE.'core.typography'.EXT;
        }
                
        $TYPE = new Typography(0); 
        $TYPE->encode_email = false;
        
		$entry_content = $REGX->xss_clean($entry_content);
		$entry_content = $TYPE->parse_type( $entry_content, 
									   array(
												'text_format'   => ( ! isset($tb_data['tb_format'])) ? 'none' : $tb_data['tb_format'],
												'html_format'   => 'none',
												'auto_links'    => 'n',
												'allow_img_url' => 'n'
											)
									);
		
        /** ----------------------------------------
        /**  Assign our data string
        /** ----------------------------------------*/
        
		$data = "url=".rawurlencode($entry_link).
				"&title=".rawurlencode($entry_title).
				"&blog_name=".rawurlencode($weblog_name).
				"&excerpt=".rawurlencode($entry_content).
				"&charset=".rawurlencode($PREFS->ini('charset')); 
        
                
        /** ----------------------------------------
        /**  Send Trackbacks
        /** ----------------------------------------*/
        
        if (count($trackback_url) > 0)
        {
            foreach ($trackback_url as $url)
            {
                if ( ! $this->previously_sent_trackbacks($entry_id, $url))
                {                
                    $this->process_trackback($url, $data);
                }
            }	
        }

        return array($this->tb_good_urls, $this->tb_bad_urls);
    }	
	/* END */
	
	

	
    /** ----------------------------------------
    /**  Extract trackback URL(s)
    /** ----------------------------------------*/
      
    function extract_trackback_urls($urls)
    {           
		// Remove the pesky white space and replace with a comma.
		
		$urls = preg_replace("/\s*(\S+)\s*/", "\\1,", $urls);
		
		// If they use commas too, then get rid of the doubles.
		
		$urls = str_replace(",,", ",", $urls);
		
		// Remove any comma that might be at the end
		
		if (substr($urls, -1) == ",")
		{
			$urls = substr($urls, 0, -1);
		}
				
		// Break into an array via commas
		
		$urls = preg_split('/[,]/', $urls);
		
		// Removes duplicates.  Reduce user error...one of our mantras
		
        $urls = array_unique($urls);
        
        array_walk($urls, array($this, 'check_trackback_url_prefix')); 
        
        return $urls;
	}
	/* END */
		

    /** ----------------------------------------
    /**  Check URL prefix for http://
    /** ----------------------------------------*/
    
    // Via callback in array_walk

    function check_trackback_url_prefix($url)
    {
        $url = trim($url);

        if (substr($url, 0, 4) != "http")
        {
            $url = "http://".$url;
        }
    }
    /* END */



    /** ----------------------------------------
    /**  Previously sent trackbacks
    /** ----------------------------------------*/
		
    function previously_sent_trackbacks($entry_id, $url)
    {
        global $DB;
                                   
        $query = $DB->query("SELECT count(*) as count FROM exp_weblog_titles WHERE entry_id = '$entry_id' AND sent_trackbacks LIKE '%".$DB->escape_like_str($url)."%'");   
    
        if ($query->row['count'] == 0)
            return false;
        else
            return true;
    }
	/* END */
	
	
	
	
    /** ----------------------------------------
    /**  Process Trackback
    /** ----------------------------------------*/
    
	function process_trackback($url, $data)
	{
        $target = parse_url($url);
	
        /** ----------------------------------------
        /**  Can we open the socket?
        /** ----------------------------------------*/
	          			                
        if ( ! $fp = @fsockopen($target['host'], 80))
        {
            $this->tb_bad_urls[] = array($url, 'Invalid Connection');
            
            return;          
        }

        /** ----------------------------------------
        /**  Assign path
        /** ----------------------------------------*/
        
        $ppath = ( ! isset($target['path'])) ? $url : $target['path'];
        
        $path = (isset($target['query']) && $target['query'] != "") ? $ppath.'?'.$target['query'] : $ppath;

        /** ----------------------------------------
        /**  Add ID to data string
        /** ----------------------------------------*/

        if ($id = $this->find_remote_id($url))
        {
            $data = "tb_id=".$id."&".$data;
        }
 
        /** ----------------------------------------
        /**  Transfter data to remote server
        /** ----------------------------------------*/

        fputs ($fp, "POST " . $path . " HTTP/1.0\r\n" ); 
        fputs ($fp, "Host: " . $target['host'] . "\r\n" ); 
        fputs ($fp, "Content-type: application/x-www-form-urlencoded\r\n" ); 
        fputs ($fp, "Content-length: " . strlen($data) . "\r\n" ); 
        fputs ($fp, "User-Agent: ExpressionEngine/" . APP_VER . "\r\n");
        fputs ($fp, "Connection: close\r\n\r\n" ); 
        fputs ($fp, $data);
   
        /** ----------------------------------------
        /**  Did we make a love connection?
        /** ----------------------------------------*/
        
        $response = "";
        
        while(!feof($fp))
            $response .= fgets($fp, 128);
        
        @fclose($fp);
        
		if ( ! preg_match("#<error>0<\/error>#i", $response))
		{
			$message = 'Unknown Error';
			
			if (preg_match("/<message>(.*?)<\/message>/is", $response, $match))
			{
				$message = trim($match['1']);
			}
			
            $this->tb_bad_urls[] = array($url, $message);             
		}
		else
		{
			$this->tb_good_urls[] = $url;
		}
	}
	/* END */
			
	
	
	/** ----------------------------------------
    /**  Find Trackback URL's ID
    /** ----------------------------------------*/
    
	function find_remote_id($url) {
		
		$tb_id = "";
		
        if (strstr($url, '?'))
		{
			$tb_array = explode('/', $url);
			$tb_end   = $tb_array[count($tb_array)-1];
			
			if ( ! is_numeric($tb_end))
			{
				$tb_end  = $tb_array[count($tb_array)-2];
			}
			
			$tb_array = explode('=', $tb_end);
			$tb_id    = $tb_array[count($tb_array)-1];
		}
		else
		{		
			$tb_array = explode('/', trim($url, '/'));
			$tb_id    = $tb_array[count($tb_array)-1];
			
			if ( ! is_numeric($tb_id))
			{
				$tb_id  = $tb_array[count($tb_array)-2];
			}
		}	
				
		if ( ! preg_match ("/^([0-9]+)$/", $tb_id)) 
		{
		    return false;
		}
		else
		{
		    return $tb_id;
		}		
	}
	/* END */
	


    /** ---------------------------------------
    /**  Receive a trackback
    /** ---------------------------------------*/

	function receive_trackback()
	{
	    global $EXT, $REGX, $DB, $IN, $FNS, $LANG, $LOC, $PREFS, $STAT, $SESS;
	    
        /** ----------------------------------------
		/**  Is the nation of the user banend?
		/** ----------------------------------------*/
		$SESS->nation_ban_check();			
	    
	    
	    $entry_id = ( ! isset($_POST['tb_id'])) ? '' : strip_tags($_POST['tb_id']);
	    $charset  = ( ! isset($_POST['charset'])) ? 'auto' : strtoupper(trim($_POST['charset']));
	    
	    if ($entry_id != '' && ! is_numeric($entry_id))
	    {
	    	$entry_id = '';
	    }
	    
        if ($entry_id == '' && ! isset($_GET['ACT_1']))
        {
            return $this->trackback_response(1);
        }
            
        if ($entry_id == '' && ! is_numeric($_GET['ACT_1']))
        {
            return $this->trackback_response(1);
        }
        
        $id = ($entry_id == '') ? $_GET['ACT_1'] : $entry_id;
                                
        /** -----------------------------------
        /**  Verify and pre-process post data
        /** -----------------------------------*/

        $required_post_data = array('url', 'title', 'blog_name', 'excerpt');
            
        foreach ($required_post_data as $val)
        {
            if ( ! isset($_POST[$val]) || $_POST[$val] == '')
            {
                return $this->trackback_response(1);
            }
            
            if ($val != 'url')
            {
            	if (function_exists('mb_convert_encoding'))
				{
            		$_POST[$val] = mb_convert_encoding($_POST[$val], strtoupper($PREFS->ini('charset')), strtoupper($charset));
				}
				elseif(function_exists('iconv'))
				{
					$return = @iconv(($charset != 'auto') ? strtoupper($charset) : '', strtoupper($PREFS->ini('charset')), $_POST[$val]);
					
					if ($return !== FALSE)
					{
						$_POST[$val] = $return;
					}
				}
				elseif(function_exists('utf8_encode') && strtoupper($PREFS->ini('charset') == 'UTF-8'))
				{
					$_POST[$val] = utf8_encode($_POST[$val]);
				}
            }
            
            $_POST[$val] = ($val != 'url') ? $REGX->xml_convert(strip_tags($_POST[$val]), TRUE) : strip_tags($_POST[$val]);
        }
        
        /** ----------------------------
        /**  Fetch preferences 
        /** ----------------------------*/
        
        $sql = "SELECT exp_weblog_titles.title, 
                       exp_weblog_titles.url_title,
                       exp_weblog_titles.site_id,
                       exp_weblog_titles.allow_trackbacks, 
                       exp_weblog_titles.trackback_total, 
                       exp_weblog_titles.weblog_id,
                       exp_weblogs.blog_title,
                       exp_weblogs.blog_url,
                       exp_weblogs.trackback_system_enabled,
                       exp_weblogs.comment_url,
                       exp_weblogs.comment_notify,
                       exp_weblogs.comment_notify_emails,
                       exp_weblogs.comment_notify_authors,
                       exp_weblogs.trackback_max_hits,
                       exp_weblogs.trackback_use_captcha
                FROM   exp_weblog_titles, exp_weblogs
                WHERE  exp_weblog_titles.weblog_id = exp_weblogs.weblog_id
                AND    exp_weblog_titles.entry_id = '".$DB->escape_str($id)."'";
                
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
            return $this->trackback_response(1);
		}
		
		foreach ($query->row as $key => $val)
		{
		    $$key = $val;
		}
		
        /** ----------------------------
        /**  Are pings allowed?
        /** ----------------------------*/
		
		if ($allow_trackbacks == 'n' || $trackback_system_enabled == 'n')
		{
            return $this->trackback_response(1);
		}

        /** -----------------------------------
        /**  Do we require the TB Captcha?
        /** -----------------------------------*/

        if ($trackback_use_captcha == 'y')
        {
        	// First we see if the captcha is passed from input class
        	
        	$captcha = (isset($_GET['ACT_2'])) ? $_GET['ACT_2'] : '';  

			// If not, we need to fetch it from: $_POST['url']
			
			if ($captcha == '')
			{
				$url = $IN->URI;
					
				$url_array	= explode('/', trim($url, '/'));
				$captcha	= $url_array[count($url_array)-1];
			}
			
			// Captchas are 8 characters long, so if the string we just fetched
			// is not then send them to the corn fields.

			if (strlen($captcha) < 8)
			{
           		return $this->trackback_response(3);
			}
			
			// Query the captcha table
			
			$res = $DB->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".$DB->escape_str($captcha)."' AND date > UNIX_TIMESTAMP()-7200");
		
			// No cappy?  Very crappy...
			
			if ($res->row['count'] == 0)
			{
           		return $this->trackback_response(3);
			}
		
			// Kill the captcha and any old, expired ones from the DB.
			
			$DB->query("DELETE FROM exp_captcha WHERE word='".$DB->escape_str($captcha)."' OR date < UNIX_TIMESTAMP()-7200");
			
			// We need to remove the captcha string from the end of the URL
			// before we store it in the database.

			$_POST['url'] = str_replace($captcha, '', $_POST['url']);
			
			$_POST['url'] = $FNS->remove_double_slashes($_POST['url']);
        }
		// end captcha stuff...
		
		
		/** ----------------------------
        /**  Blacklist/Whitelist Check
        /** ----------------------------*/
		
		if ($IN->blacklisted == 'y' && $IN->whitelisted == 'n')
        {
        	return $this->trackback_response(3);
        }		
        
        /** ----------------------------
        /**  Spam check
        /** ----------------------------*/

        $last_hour = $LOC->now - 3600;

        $query = $DB->query("SELECT COUNT(*) as count FROM exp_trackbacks WHERE trackback_ip = '".$IN->IP."' AND trackback_date > '$last_hour'");

		if ($query->row['count'] >= $trackback_max_hits)
		{
			return $this->trackback_response(4);
		}
		
        /** ----------------------------
        /**  Check for previous pings
        /** ----------------------------*/

        $query = $DB->query("SELECT COUNT(*) as count FROM exp_trackbacks WHERE trackback_url = '".$DB->escape_str($_POST['url'])."' AND entry_id = '".$DB->escape_str($id)."'");

		if ($query->row['count'] > 0)
		{
			return $this->trackback_response(2);
		}
		
		
        /** ----------------------------------------
        /**  Limit size of excerpt
        /** ----------------------------------------*/
		
		$content = $FNS->char_limiter($_POST['excerpt']);		
       
        /** ----------------------------------------
        /**  Do we allow duplicate data?
        /** ----------------------------------------*/

        if ($PREFS->ini('deny_duplicate_data') == 'y')
        {
			$query = $DB->query("SELECT count(*) AS count FROM exp_trackbacks WHERE content = '".$DB->escape_str($content)."' ");
		
			if ($query->row['count'] > 0)
			{					
				return $this->trackback_response(2);
			}
        }		

        /** ----------------------------
        /**  Insert the trackback
        /** ----------------------------*/
        
        $data = array(
                        'entry_id'       => $id,
                        'weblog_id'		 => $weblog_id,
                        'title'          => $_POST['title'],
                        'content'        => $content,
                        'weblog_name'    => $_POST['blog_name'],
                        'trackback_url'  => $REGX->xml_convert($_POST['url']),
                        'trackback_date' => $LOC->now,
                        'trackback_ip'   => $IN->IP,
                        'site_id'		 => $site_id
                     );
		
		/* -------------------------------------
		/*  'insert_trackback_insert_array' hook.
		/*  - Modify any of the soon to be inserted values
		*/  
			if ($EXT->active_hook('insert_trackback_insert_array') === TRUE)
			{
				$data = $EXT->call_extension('insert_trackback_insert_array', $data);
				if ($EXT->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/
        
        $DB->query($DB->insert_string('exp_trackbacks', $data));
        
        $trackback_id = $DB->insert_id;
        
        if ($DB->affected_rows == 0) 
        {
            return $this->trackback_response(3);
        }
                
        /** ------------------------------------------------
        /**  Update trackback count and "recent trackback" date
        /** ------------------------------------------------*/
        
		$query = $DB->query("SELECT trackback_total, author_id FROM exp_weblog_titles WHERE entry_id = '$id'");

		$trackback_total = $query->row['trackback_total'] + 1;
		$author_id = $query->row['author_id'];

		$DB->query("UPDATE exp_weblog_titles SET trackback_total = '$trackback_total', recent_trackback_date = '".$LOC->now."'  WHERE entry_id = '$id'");
		$DB->query("UPDATE exp_weblogs SET last_trackback_date = '".$LOC->now."'  WHERE weblog_id = '$weblog_id'");

        /** ----------------------------------------
        /**  Update global stats
        /** ----------------------------------------*/
        
        $STAT->update_trackback_stats($weblog_id);
        
        /** ----------------------------------------
		/**  Fetch Notification Emails
		/** ----------------------------------------*/
		
		$notify_emails = '';
		
		if ($comment_notify == 'y' AND $comment_notify_emails != '')
		{
			$notify_emails = $comment_notify_emails;
		}
        
        if ($comment_notify_authors == 'y')
        {			
			$result = $DB->query("SELECT email FROM exp_members WHERE member_id = '".$DB->escape_str($author_id)."'");
			$notify_emails	.= ','.$result->row['email'];
        }

        /** ----------------------------
        /**  Send notification
        /** ----------------------------*/

        if ($notify_emails != '')
        {        
            /** ----------------------------
            /**  Build email message
            /** ----------------------------*/
            
            $delete_link = $PREFS->ini('cp_url').'?S=0&C=edit'.
            				'&M=del_comment_conf'.
            				'&weblog_id='.$weblog_id.
            				'&entry_id='.$id.
            				'&trackback_id='.$trackback_id;
            
			$swap = array(
							'entry_title'			=> $title,
							'comment_url'			=> $FNS->remove_double_slashes($comment_url.'/'.$url_title.'/'),
							'sending_weblog_name'	=> stripslashes($_POST['blog_name']),
							'sending_entry_title'	=> stripslashes($_POST['title']),
							'sending_weblog_url'	=> $_POST['url'],
							'trackback_id'			=> $trackback_id,
							'trackback_ip'			=> $IN->IP,
							'delete_link'			=> $delete_link
						 );
			
			$template = $FNS->fetch_email_template('admin_notify_trackback');
			
			$email_msg = $FNS->var_swap($template['data'], $swap);
			$email_tit = $FNS->var_swap($template['title'], $swap);
            
            /** ----------------------------
            /**  Send email
            /** ----------------------------*/
            
            require PATH_CORE.'core.email'.EXT;
                        
            $email = new EEmail;
            
            foreach (explode(',', $notify_emails) as $addy)
			{            
				if ($addy == '') continue;
			
				$email->initialize();
				$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
				$email->to($addy); 
				$email->subject($email_tit);	
				$email->message($REGX->entities_to_ascii($email_msg));		
				$email->Send();
			}
        }
        

        /** ----------------------------
        /**  Return response
        /** ----------------------------*/

        return $this->trackback_response(0);
    
    }    
    /* END */
        
    
	/** ----------------------------------------
    /**  Send Trackback Responses
    /** ----------------------------------------*/

	function trackback_response($code=1)
	{
		if ($code == 0)
			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<response>\n<error>0</error>\n</response>";
		elseif ($code == 1)
			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<response>\n<error>1</error>\n<message>Incomplete Information</message>\n</response>";
		elseif ($code == 2)
			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<response>\n<error>1</error>\n<message>Trackback already received</message>\n</response>";
		elseif ($code == 3)
			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<response>\n<error>1</error>\n<message>Trackback unable to be accepted</message>\n</response>";
		elseif ($code == 4)
			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<response>\n<error>1</error>\n<message>Trackback hourly limit exceeded for this IP address</message>\n</response>";
	
		exit;
	}
	/* END */
	
	
    /** -----------------------
    /**  XML Start Element
    /** -----------------------*/

    function startElement($parser, $name, $attrs)
    {
        global $DSP, $REGX;
            
        if ($this->insideitem)
        {
            $this->tag = $name;
        }
        elseif ($name == "RDF:DESCRIPTION")
        {
            $url = $attrs['TRACKBACK:PING'];
            
            $title = $attrs['DC:TITLE'];
            
            $selected = (in_array($url, $this->selected_urls)) ? "checked=\"checked\"" : "";
            
            echo $DSP->qdiv('', "<input type=\"checkbox\" name=\"TB_AUTO_{$url}\" value=\"$url\" $selected />".NBS.NBS.$title);
            
            $this->insideitem = true;            
        }
    }
    /* END */
  
    
    /** -----------------------
    /**  XML End Element
    /** -----------------------*/

    function endElement($parser, $name)
    {
        global $insideitem, $tag; 
        
        if ($name == "RDF:DESCRIPTION")
        {
            $this->insideitem = false;
        }
    }
    /* END */
    
    
    /** -----------------------
    /**  XML CDATA
    /** -----------------------*/

    function characterData($parser, $data)
    {
        // Nothing between the tag.
    }
    

    /** ----------------------------------------
    /**  Module installer
    /** ----------------------------------------*/

    function trackback_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Trackback', '$this->version', 'n')";
        $sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Trackback_CP', 'receive_trackback')";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
        return true;
    }
    /* END */
    
    
    
    /** ----------------------------------------
    /**  Module de-installer
    /** ----------------------------------------*/

    function trackback_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Trackback'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Trackback'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Trackback'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Trackback_CP'";

        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */



}
// END CLASS
?>