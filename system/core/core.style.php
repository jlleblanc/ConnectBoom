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
 File: core.style.php
-----------------------------------------------------
 Purpose: This class fetches the requested stylesheet.
 It also caches it in case there are multiple stylesheet
 requests on a single page
=====================================================
*/


class Style {

	function Style()
    {
        global $DB, $PREFS, $IN;
       
    	if (isset($_GET['ACT']) && $_GET['ACT'] == 'css')
		{
			$stylesheet = $IN->fetch_uri_segment(1).'/'.$IN->fetch_uri_segment(2);
		}
		else
		{
			$stylesheet = $_GET['css'];
    	}
    	
    	if (preg_match("/\.v\.([0-9]{10})/", $stylesheet, $match))
    	{
    		$version = $match[1];
    		$stylesheet = str_replace($match[0], '', $stylesheet);  // Remove version info
    	}
    
        if ( $stylesheet == '' OR strpos($stylesheet, '/') === FALSE OR preg_match("#^(http:\/\/|www\.)#i", $stylesheet))
        {
			exit;
        }

		$ex =  explode("/", $stylesheet);
			
		if (count($ex) != 2)
		{	
			exit;
		}
			
		$sql = "SELECT exp_templates.template_data, exp_templates.template_name, exp_templates.save_template_file, exp_templates.edit_date
				FROM   exp_templates, exp_template_groups 
				WHERE  exp_templates.group_id = exp_template_groups.group_id
				AND    exp_templates.template_name = '".$DB->escape_str($ex['1'])."'
				AND    exp_template_groups.group_name = '".$DB->escape_str($ex['0'])."'
				AND    exp_templates.template_type = 'css'
				AND    exp_templates.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";

		$query = $DB->query($sql);

		if ($query->num_rows == 0)
		{
			exit;
		}
		
		$saved_as_file = ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '' AND $query->row['save_template_file'] == 'y') ? TRUE : FALSE;

		if ($saved_as_file === FALSE && function_exists('getallheaders') && isset($version) && $version == $query->row['edit_date'])
		{
			$request = @getallheaders();
			
			if (isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '')
			{
				// The logic of simply outputting the 304 Status without validating the If-Modified-Since
				// header is this:  If the version number is set and the version number is the current
				// one for this CSS Template, then it makes no sense for a browser to send the If-Modified-Since
				// header unless they have called this exact version of the template before.  So,
				// why send them something they have already requested? -Paul
				
				$this->http_status_header(304);
				exit;
					
				// You know, it's here if I am ever proven wrong with the above.
				
				$x = explode(';',$request['If-Modified-Since']);
				
				if ($version == strtotime($x['0']))
				{
					$this->http_status_header(304);
					exit;
				}
			}
		}
		
		/** -----------------------------------------
		/**  Retreive template file if necessary
		/** -----------------------------------------*/
		
		if ($saved_as_file === TRUE)
		{
			$basepath = $PREFS->ini('tmpl_file_basepath', 1);
							
			$basepath .= $ex['0'].'/'.$query->row['template_name'].'.php';
			
			if ($fp = @fopen($basepath, 'rb'))
			{
				flock($fp, LOCK_SH);
				
				$query->row['template_data'] = fread($fp, filesize($basepath)); 
				
				flock($fp, LOCK_UN);
				fclose($fp); 
			}
		}
		
		$query->row['template_data'] = str_replace(LD.'site_url'.RD, stripslashes($PREFS->ini('site_url')), $query->row['template_data']);	
		
		if ($PREFS->ini('send_headers') == 'y')
		{        
			$this->http_status_header(200);
			
			// Temporary fix for PHP as CGI servers because of this bug:
			// http://bugs.php.net/bug.php?id=34537&edit=1
			// Intend to do more research into problem for EE 2.0
			if (function_exists('getallheaders'))
			{
				@header("Last-Modified: ".gmdate("D, d M Y H:i:s", $query->row['edit_date'])." GMT");
			}
			
			@header("Expires: ".gmdate("D, d M Y H:i:s", time()+314159265)." GMT");  // Bit less than ten years
			@header("Cache-Control: max-age=314159265");
		}
		
		@header("Content-type: text/css");
        exit($query->row['template_data']);
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
}
// END CLASS
?>