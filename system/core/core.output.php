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
 File: core.output.php
-----------------------------------------------------
 Purpose: Display class.  All browser output is
 managed by this file.
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Output {

    var $refresh_msg	= TRUE;	// TRUE/FALSE - whether to show the "You will be redirected in 5 seconds" message.
	var $resresh_time	= 1;	// Number of seconds for redirects - there is a silly typo here that for legacy reasons we're leaving in.
    
    var $out_type		= 'webpage';
    var $out_queue		= '';
    var $remove_unparsed_variables = TRUE; // whether to remove left-over variables that had bad syntax

    /** -------------------------------------------
    /**  Build "output queue"
    /** -------------------------------------------*/

    function build_queue($output)
    {
        $this->out_queue = $output;
    }
    /* END */

    /** -------------------------------------------
    /**  Sends Out an HTTP Status Header
    /** -------------------------------------------*/
    
    function http_status_header($code, $text='')
    {
    	if ($text == '')
    	{
    		switch($code)
    		{
    			case 200:
    				$text = 'OK';
    			break;
    			case 304:
    				$text = 'Not Modified';
    			break;
    			case 401:
    				$text = 'Unauthorized';
    			break;
    			case 404:
    				$text = 'Not Found';
    			break;
    		}
    	}
    
    	if (substr(php_sapi_name(), 0, 3) == 'cgi')
    	{
    		@header("Status: {$code} {$text}", TRUE);
    	}
    	elseif ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' OR $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
    	{
    		@header($_SERVER['SERVER_PROTOCOL']." {$code} {$text}", TRUE, $code);
    	}
    	else
    	{
    		@header("HTTP/1.1 {$code} {$text}", TRUE, $code);
    	}
    }
    /* END */
 

    /** -------------------------------------------
    /**  Display the final browser output
    /** -------------------------------------------*/

    function display_final_output($output = '')
    {
        global $IN, $PREFS, $TMPL, $BM, $DB, $SESS, $FNS, $LOC;
        
        /** -----------------------------------
        /**  Fetch the output
        /** -----------------------------------*/
                
        if ($output == '') 
            $output = $this->out_queue;
                        
        /** -----------------------------------
        /**  Start output buffering
        /** -----------------------------------*/
        
        ob_start();

        /** -----------------------------------
        /**  Generate HTTP headers
        /** -----------------------------------*/
        
        if ($PREFS->ini('send_headers') == 'y' && $this->out_type != 'rss' && $this->out_type != '404')
        {        
            $this->http_status_header(200);
            @header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            @header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
            @header("Pragma: no-cache");
        }
    
        if ($this->out_type == 'webpage')
        {        
			@header("Content-Type: text/html; charset=".$PREFS->ini('charset'));
		}    

        /** -----------------------------------
        /**  Generate 404 headers
        /** -----------------------------------*/
        
        if ($this->out_type == '404')
        {        
            $this->http_status_header(404);
            @header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
        }

        /** -----------------------------------
        /**  Send CSS header
        /** -----------------------------------*/

        if ($this->out_type == 'css')
        {
            @header("Content-type: text/css");
        }
        
        /** -----------------------------------
        /**  Send Script header
        /** -----------------------------------*/

        if ($this->out_type == 'js')
        {
            @header("Content-type: text/javascript");
        }
        
        /** -----------------------------------
        /**  Send XML header
        /** -----------------------------------*/

        if ($this->out_type == 'xml')
        {
            //@header("Content-Type: text/xml; charset=".$PREFS->ini('charset'));
            @header("Content-Type: text/xml");
			$output = trim($output);
        }
        
        /** -----------------------------------
        /**  Send RSS header
        /** -----------------------------------*/
        
        if ($this->out_type == 'rss')
        {		
        	$request = ( ! function_exists('getallheaders')) ? array() : @getallheaders();
			
			if (preg_match("|<ee\:last_update>(.*?)<\/ee\:last_update>|",$output,$matches))
			{
				$last_update = $matches['1'];
				$output = str_replace($matches['0'],'',$output);
			}
			else
			{
				$last_update = $LOC->set_gmt();		           
			}
        	
			$output = trim($output);
			
			/** --------------------------------------------
			/**  Check for the 'If-Modified-Since' Header
			/** --------------------------------------------*/
								
			if ($PREFS->ini('send_headers') == 'y' && isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '')
			{
				$x				= explode(';',$request['If-Modified-Since']);
				$modify_tstamp	=  strtotime($x['0']);
			
				/** -------------------------------------
				/**  If no new content, send no data
				/** -------------------------------------*/
				
				if ($last_update <= $modify_tstamp)
				{
					$this->http_status_header(304);
					@exit;
				}

				/** -------------------------------------------------
				/**  Delta Done in RSS Module, send RFC3229 headers
				/** -------------------------------------------------*/
				
				if (isset($request['A-IM']) && (stristr($request['A-IM'],'feed') !== false OR stristr($request['A-IM'],'diffe') !== false))
				{
					$gzip_im = ($PREFS->ini('gzip_output') == 'y') ? ', gzip' : '';
					$main_im = (stristr($request['A-IM'],'diffe') !== false) ? 'diffe' : '';
					$main_im = (stristr($request['A-IM'],'feed') !== false) ? 'feed' : $main_im;
					
					/* The RFC-3229 spec says to use the 226 header code, but
					alas only dev versions of Apache use it.  For the time
					being we will have to use the usual 200 OK response.
					Alternatively, someone could use the Speedy Feed Apache mod
					http://asdf.blogs.com/asdf/2004/09/mod_speedyfeed__3.html
					*/
					
					$apache_modules = ( ! function_exists('apache_get_modules')) ? array() : @apache_get_modules();
					// Looks like this:  Apache/1.3.29 (Unix) PHP/4.3.4
					$apache_version = ( ! function_exists('apache_get_version')) ? '' : @apache_get_version();  
					$apache_version_string = explode(' ',$apache_version);
					$apache_version_number = explode('/',$apache_version_string['0']);
					$apache_version_points = explode('.',$apache_version_number['0']);
					
					
					if (in_array('mod_speedyfeed',$apache_modules))
					{
						@header('HTTP/1.1 226 IM Used');
					}
					else
					{	
						$this->http_status_header(200);
					}
					
					@header('Cache-Control: no-store, im');
					@header('IM: '.$main_im.$gzip_im);
					@header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_update).' GMT');
				}
				else
				{
					$this->http_status_header(200);
					@header('Expires: '.gmdate('D, d M Y H:i:s', $last_update+(60*60)).' GMT'); // One hour
					@header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_update).' GMT');
					@header("Cache-Control: no-store, no-cache, must-revalidate");
					@header("Cache-Control: post-check=0, pre-check=0", false);
					@header("Pragma: no-cache");   		
				}
			}
			else
			{
				$this->http_status_header(200);
				@header('Expires: '.gmdate('D, d M Y H:i:s', $last_update+(60*60)).' GMT'); // One hour
				@header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_update).' GMT');
				@header("Cache-Control: no-store, no-cache, must-revalidate");
				@header("Cache-Control: post-check=0, pre-check=0", false);
				@header("Pragma: no-cache");
			}
            
            @header("Content-Type: text/xml; charset=".$PREFS->ini('charset'));       
            
			/** -----------------------------------
			/**  Swap XML declaration for RSS files
			/** -----------------------------------*/
			$output = preg_replace("/{\?xml(.+?)\?}/", "<?xml\\1?".">", $output);
        }              
              
        /** -----------------------------------
        /**  Fetch the buffered output
        /** -----------------------------------*/
        
        echo $output;
                
        $buffer = ob_get_contents();
                
        ob_end_clean(); 
        
        /** -------------------------------------
        /**  Parse elapsed time and query count
        /** -------------------------------------*/
        
        $BM->mark('end');
        
        if (REQ == 'CP')
        {
            $buffer = str_replace('{cp:elapsed_time}', $BM->elapsed('start', 'end'), $buffer);
        }
        else
        {
            $buffer = str_replace(LD.'elapsed_time'.RD, $BM->elapsed('start', 'end'), $buffer);
            $buffer = str_replace(LD.'total_queries'.RD, $DB->q_count, $buffer);       

            /** --------------------------------------
            /**  Remove bad variables
            /** --------------------------------------*/
            
			// If 'debug' is turned off, we will remove any variables that didn't get parsed due to syntax errors.
	
			if ($PREFS->ini('debug') == 0 AND $this->remove_unparsed_variables == TRUE)
			{
				$buffer = preg_replace("/".LD."[^;\n]+?".RD."/", '', $buffer);
			}
        }
        
        /** ---------------------------------------
        /**  Show queries if enabled for debugging
        /** ---------------------------------------*/
        
        // For security reasons, we won't show the queries 
        // unless the current user is a logged-in Super Admin

        if ($DB->show_queries === TRUE AND isset($DB->queries))
        {
			if ($SESS->userdata['group_id'] == 1 && (REQ == 'CP' OR (is_object($TMPL) && $TMPL->template_type != 'js')))
			{				
				$i = 1;
				
				$buffer .= '<div style="color: #333; background-color: #ededed; margin:10px; padding-bottom:10px;">';
				$buffer .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px; padding: 6px\"><hr size='1'><b>SQL QUERIES</b><hr size='1'></div>";
				
				$highlight = array('SELECT', 'FROM', 'WHERE', 'AND', 'LEFT JOIN', 'ORDER BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE');
				
				foreach ($DB->queries as $val)
				{
					$val = htmlspecialchars($val, ENT_QUOTES);
					
					foreach ($highlight as $bold)
					{
						$val = str_replace($bold, '<b>'.$bold.'</b>', $val);	
					}
									
					$buffer .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px; padding: 6px\"><hr size='1'>";
					$buffer .= "<h5>".$i.'</h5>';
					$buffer .= str_replace("\t", " ", $val);
					$buffer .= "</div>";
					
					$i++;
				}
				
				$buffer .= '</div>';
			}
        }
        
        if (is_object($TMPL) && isset($TMPL->debugging) && $TMPL->debugging === TRUE && $TMPL->template_type != 'js')
        {
        	if ($SESS->userdata['group_id'] == 1)
			{		
				$buffer .= '<div style="color: #333; background-color: #ededed; margin:10px; padding-bottom:10px;">';
				$buffer .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px; padding: 6px\"><hr size='1'><b>TEMPLATE DEBUGGING</b><hr size='1'></div>";
				
				foreach ($TMPL->log as $val)
				{
					$val = str_replace(array("\t", '&amp;nbsp;'), array(' ', '&nbsp;'), htmlentities($val, ENT_QUOTES));
					
					$x = explode(':', $val, 2);
					
					if (sizeof($x) > 1)
					{
						$val = '<strong>'.$x['0'].':</strong>'.$x['1'];
					}
					else
					{
						$val = '<strong>'.$val.'</strong>';
					}
									
					$buffer .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px 12px 6px 22px;\">".$val."</div>";
				}
				
				if (function_exists('memory_get_usage') AND ($usage = memory_get_usage()) != '')
				{
					$buffer .= "<div style='text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px 12px 6px 22px;'><strong>Memory Usage: ".number_format($usage)." bytes</strong></div>";
				}
				
				$buffer .= '</div>';
			}
        }
        
        /** -----------------------------------
        /**  Compress the output
        /** -----------------------------------*/
        
        if ($PREFS->ini('gzip_output') == 'y' AND REQ == 'PAGE')
        {
            ob_start('ob_gzhandler');
        }        

        /** -----------------------------------
        /**  Send it to the browser
        /** -----------------------------------*/
        
        echo $buffer;        
    }
    /* END */
    


    /** -------------------------------------------
    /**  Display fatal error message
    /** -------------------------------------------*/
    
    function fatal_error($error_msg = '', $use_lang = TRUE)
    {
        global $LANG;
        
        $heading = ($use_lang == TRUE && is_object($LANG)) ? $LANG->line('error') : 'Error Message';
        
		$data = array(	'title' 	=> $heading,
						'heading'	=> $heading,
						'content'	=> '<p>'.$error_msg.'</p>'
					 );
										
		$this->show_message($data);
    }
    /* END */
    

    /** -------------------------------------------
    /**  System is off message
    /** -------------------------------------------*/
    
    function system_off_msg()
    {
        global $LANG, $DB, $PREFS;
        
		$query = $DB->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'offline_template'");
		
		echo $query->row['template_data'];
		exit;                        
    }
    /* END */


    /** ----------------------------------------
    /**  Show message
    /** ----------------------------------------*/
    
    // This function and the next enable us to show error
    // messages to users when needed.  For example, when
    // a form is submitted without the required info.
    
    // This is not used in the control panel, only with
    // publicly accessible pages.
     
    function show_message($data, $xhtml = TRUE)
    {
		global $LANG, $DB, $PREFS, $REGX;			
		
		foreach (array('title', 'heading', 'content', 'redirect', 'rate', 'link') as $val)
		{
			if ( ! isset($data[$val]))
			{
				$data[$val] = '';
			}
		}
		
		if ( ! is_numeric($data['rate']) OR $data['rate'] == '')
		{
			$data['rate'] = $this->resresh_time; // There is a silly typo here that for legacy reasons we're leaving in.
		}
		
		$data['meta_refresh']	= ($data['redirect'] != '') ? "<meta http-equiv='refresh' content='".$data['rate']."; url=".$REGX->xss_clean($data['redirect'])."'>" : '';
		$data['charset']		= $PREFS->ini('charset');	
				
		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? $LANG->line('click_if_no_redirect') : '';
		
			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;
			
			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : $REGX->xss_clean($data['link']['0']);
		
			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}
		
		if ($xhtml == TRUE)
		{
			if ( ! class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}
			
			$TYPE = new Typography;
	
			$data['content'] = $TYPE->parse_type(stripslashes($data['content']), array('text_format' => 'xhtml'));
		}   	
    	    	
		$query = $DB->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = 'message_template'");
		
		foreach ($data as $key => $val)
		{
			$query->row['template_data'] = str_replace('{'.$key.'}', $val, $query->row['template_data']);
		}
				
		echo  stripslashes($query->row['template_data']);        
        exit;
    } 
    /* END */
    
  
    /** ----------------------------------------
    /**  Show user error
    /** ----------------------------------------*/
 
    function show_user_error($type = 'submission', $errors, $heading = '')
    {
        global $LANG;
         
		if ($type != 'off')
		{      
			switch($type)
			{
				case 'submission' : $heading = $LANG->line('submission_error');
					break;
				case 'general'    : $heading = $LANG->line('general_error');
					break;
				default           : $heading = $LANG->line('submission_error');
					break;
			}
    	}
        
        $content  = '<ul>';
        
        if ( ! is_array($errors))
        {
			$content.= "<li>".$errors."</li>\n";
        }
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
        }
        
        $content .= "</ul>";
        
        $data = array(	'title' 	=> $LANG->line('error'),
        				'heading'	=> $heading,
        				'content'	=> $content,
        				'redirect'	=> '',
        				'link'		=> array('JavaScript:history.go(-1)', $LANG->line('return_to_previous'))
					 );
                
		$this->show_message($data, 0);
    } 
    /* END */

}
// END CLASS
?>