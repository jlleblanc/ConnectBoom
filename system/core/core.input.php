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
 File: core.input.php
-----------------------------------------------------
 Purpose: This class fetches all input data from
 the super-global arrays (GET, POST, SERVER, COOKIE).
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Input {

    var $AGENT    	= '';       // The current user's browser data
    var $IP       	= '';       // The current user's IP address
    var $SID      	= '';       // Session ID extracted from the URI segments
    var $URI      	= '';       // The full URI query string: /weblog/comments/124/    
    var $QSTR     	= '';       // Only the query segment of the URI: 124
    var $Pages_QSTR = '';		// For a Pages request, this contains the Entry ID for the Page
    var $SEGS     	= array();  // The segments of the query string in an array
	var $trim_input	= TRUE;
	
	var $global_vars = array();	// The global vars from path.php
	
	var $whitelisted = 'n';		// Is this request whitelisted
	var $blacklisted = 'n';		// Is this request blacklisted.
   
   // These are reserved words that have special meaning when they are the first
   // segment of a URI string.  Template groups can not be named any of these words
      
    var $reserved = array('css', 'trackback');
    
    var $make_safe = array('RET', 'XSS', 'URI', 'ACT');
    
    /** -----------------------------------
    /**  Constructor
    /** -----------------------------------*/

    function Input()
    {
        global $REGX;
    		
		$this->AGENT = ( ! isset($_SERVER['HTTP_USER_AGENT'])) 	? '' : $REGX->xss_clean($_SERVER['HTTP_USER_AGENT']);
		
		$_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);
    }
    /* END */


    /** -----------------------------------
    /**  Fetch incomming GET/POST/IP data
    /** -----------------------------------*/
    
    // All data is filtered for security

    function fetch_input_data()
    {
        global $PREFS, $REGX;
        
        /** -----------------------------------
        /**  Fetch and pre-process Global Vars
        /** -----------------------------------*/
                
        if (is_array($this->global_vars) AND count($this->global_vars) > 0)
        {
            foreach($this->global_vars as $key => $val)
            {				                        
                $this->global_vars[$this->clean_input_keys($key)] = $REGX->xss_clean($this->sanitize($this->clean_input_data($val)));
            }    
        }
        
        /** -----------------------------------
        /**  Fetch and pre-process GET data
        /** -----------------------------------*/
                
        if (is_array($_GET) AND count($_GET) > 0)
        {
            foreach($_GET as $key => $val)
            {				                        
                $_GET[$this->clean_input_keys($key)] = $REGX->xss_clean($this->sanitize($this->clean_input_data($val)));
            }    
        }
        
        /** -----------------------------------
        /**  Fetch and pre-process POST data
        /** -----------------------------------*/
        
        if (is_array($_POST) AND count($_POST) > 0)
        {
            foreach($_POST as $key => $val)
            {                
                if (is_array($val))
                {  
                   // Added this to deal with multi-select lists, as these are sent as a multi-dimensional array

                    foreach($val as $k => $v)
                    {                    
                        $_POST[$this->clean_input_keys($key.'_'.$k)] = $this->clean_input_data($v);
                        $_POST[$this->clean_input_keys($key)][$this->clean_input_keys($k)] = $this->clean_input_data($v);
                    }
                }
                else
                {
                    if (in_array($key, $this->make_safe))
                    {	
                    	$val = $REGX->xss_clean($this->sanitize($val));
                    }
                
                    $_POST[$this->clean_input_keys($key)] = $this->clean_input_data($val);
                }
            }            
        }

        /** -----------------------------------
        /**  Fetch and pre-process COOKIE data
        /** -----------------------------------*/
        
        if (is_array($_COOKIE) AND count($_COOKIE) > 0)
        {
			// Also get rid of specially treated cookies that might be set by a server
			// or silly application, that are of no use to a CI application anyway
			// but that when present will trip our 'Disallowed Key Characters' alarm
			// http://www.ietf.org/rfc/rfc2109.txt
			// note that the key names below are single quoted strings, and are not PHP variables
			unset($_COOKIE['$Version']);
			unset($_COOKIE['$Path']);
			unset($_COOKIE['$Domain']);
			
            foreach($_COOKIE as $key => $val)
            {              
                $_COOKIE[$this->clean_input_keys($key)] = $REGX->xss_clean($this->clean_input_data($val));
            }    
        }


        /** -----------------------------------
        /**  Fetch the IP address
        /** -----------------------------------*/
        
        $CIP = (isset($_SERVER['HTTP_CLIENT_IP']) AND $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
        $RIP = (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : FALSE;
        $FIP = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;
                    
		/* -------------------------------------------
		/* Hidden Configuration Variable
		/* - proxy_ips => List of proxies that may forward the ip address
		/* -------------------------------------------*/

		if ($PREFS->ini('proxy_ips') !== FALSE && $FIP && $RIP)
		{
			$proxies = preg_split('/[\s,]/', $PREFS->ini('proxy_ips'), -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);

			$this->IP = in_array($RIP, $proxies) ? $FIP : $RIP;
		}
		else
		{
			if ($CIP && $RIP)	$this->IP = $CIP;
			elseif ($RIP)		$this->IP = $RIP;
			elseif ($CIP)		$this->IP = $CIP;
			elseif ($FIP)		$this->IP = $FIP;
		}

		if (strstr($this->IP, ','))
		{
			$x = explode(',', $this->IP);
			$this->IP = trim(end($x));
		}
		
		if ( ! $REGX->valid_ip($this->IP))
		{
			$this->IP = '0.0.0.0';
		}
		
		unset($CIP);
		unset($RIP);
		unset($FIP);
    }
    /* END */
    

    /** -----------------------------------
    /**  Filter GET data for security
    /** -----------------------------------*/

	function filter_get_data($request_type = 'PAGE')
	{
		global $FNS, $SESS;

		$filter_keys = TRUE;
	
		if (isset($_GET['BK']) AND isset($_GET['weblog_id']) AND isset($_GET['title']) AND isset($_GET['tb_url']) AND $SESS->userdata['admin_sess'] == 1 AND $request_type == 'CP')
		{
			if (in_array($this->GBL('weblog_id'), $FNS->fetch_assigned_weblogs()))
			{
				$filter_keys = FALSE;
			}		
		}
	
        if (isset($_GET))
        {
            foreach($_GET as $key => $val)
            {
            	if ($filter_keys == TRUE)
            	{
            		if (is_array($val))
            		{
            			exit('Invalid GET Data - Array');
            		}
					elseif (preg_match("#(;|\?|exec\s*\(|system\s*\(|passthru\s*\(|cmd\s*\(|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $val))
					{
						exit('Invalid GET Data');
					}   
            	}
            }    
        }	
	}
	/* END */


    /** --------------------------------------
    /**  Convert programatic characters to entities
    /** --------------------------------------*/
    
	function sanitize($str)
	{
		$bad	= array('$', 		'(', 		')',	 	'%28', 		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');
		
		return str_replace($bad, $good, $str);
	}
    /* END */


    /** --------------------------------------
    /**  Parse URI segments
    /** --------------------------------------*/

    function parse_uri($uri = '')
    {
        global $REGX;
    
        if ($uri != '')
        {
        	// Don't use a reference on this or it messes up the CSS files
            $uri = $REGX->xss_clean($this->sanitize($REGX->trim_slashes($uri)));            
            			                          
            /** --------------------------------------
            /**  Does URI contain a session ID?
            /** --------------------------------------*/
            
            // If so, trim it off and rebuild the URI
                            
            if (substr($uri, 0, 2) == 'S=')
            {
                $ex = explode('/', $uri);
            
                $this->SID = substr($ex['0'], 2);
                
                $uri = '';
                
                if (count($ex) > 1)
                {
                    for ($i = 1; $i < count($ex); $i++)
                    {
                        $uri .= $ex[$i].'/';
                    }
                    
                    $uri = substr($uri, 0, -1);
                }
            }
            
            
            if ($uri != '')
            {
                $x = 0;
                
                $ex = explode("/", $uri);
                
                /** ---------------------------------------
                /**  Maximum Number of Segments Check
                /** ---------------------------------------*/

                // Safety Check:  If the URL contains more than 10 segments
                // we'll show an error message
 
                if (count($ex) > 10)
                {
                	exit("Error: The URL contains too many segments.");
                }
                
                /** ---------------------------------------
                /**  Is the first URI segment reserved?
                /** ---------------------------------------*/
                
                // Reserved segments are treated as Action requests so we'll
                // assign them as $_GET variables. We do this becuase these
                // reserved words are actually Action requests that don't come to 
                // us as normal GET/POST requests.
                            
                if (in_array($ex['0'], $this->reserved))
                {
                    $_GET['ACT'] = $ex['0'];
                    
                    for ($i = 1; $i < count($ex); $i++)
                    {                        
                        $_GET['ACT_'.$i] = $ex[$i];
                    }
                    
                    $x = 1;
                }

                /** ---------------------------------------
                /**  Parse URI segments
                /** ---------------------------------------*/
                
                $n = 1;
                
                $uri = '';

                for ($i = $x; $i < count($ex); $i++)
                {
					// nothing naughty
					if (strpos($ex[$i], '=') !== FALSE && preg_match('#.*(\042|\047).+\s*=.*#i', $ex[$i]))
					{
						$ex[$i] = str_replace(array('"', "'", ' ', '='), '', $ex[$i]);
					}
					
                    $this->SEGS[$n] = $ex[$i];
                    
                    $uri .= $ex[$i].'/';
                    
                    $n++;
                }
                
                $uri = substr($uri, 0, -1);
                
                // Does the URI contain the css request?
                // If so, assign it as a GET variable.
                // This only happens when the "force query string"
                // preference is set.
                
                if (substr($uri, 0, 4) == 'css=')
                {
                    $_GET['css'] = substr($uri, 4);
                }         
                
                // Reassign the full URI
                
                $this->URI = '/'.$uri.'/';

            }            
        }
    }
    /* END */
    
	

    /** -----------------------------------------
    /**  Parse out the $IN->QSTR variable
    /** -----------------------------------------*/

	function parse_qstr()
	{
		global $REGX;
	
		if ( ! $this->fetch_uri_segment(2))
		{
			$this->QSTR = 'index';
		}
		elseif ( ! $this->fetch_uri_segment(3))
		{
			$this->QSTR = $this->fetch_uri_segment(2);
		}
		else
		{
			$this->QSTR = preg_replace("|".'/'.preg_quote($this->fetch_uri_segment(1)).'/'.preg_quote($this->fetch_uri_segment(2))."|", '', $this->URI);
		}
	
		$this->QSTR = $REGX->trim_slashes($this->QSTR);        
	}
	/* END */


    /** -----------------------------------------
    /**  Clean global input data
    /** -----------------------------------------*/

    function clean_input_data($str)
    {
		if (is_array($str))
		{
			$new_array = array();
			foreach ($str as $key => $val)
			{
				$new_array[$this->clean_input_keys($key)] = $this->clean_input_data($val);
			}
			return $new_array;
		}
    
        $str = preg_replace("/(\015\012)|(\015)|(\012)/", "\n", $str); 
        
        if ($this->trim_input == TRUE)
        {
			$str = str_replace("\t", '    ', $str);
			$str = trim($str);
        }
        
        if ( ! get_magic_quotes_gpc())
        {
            $str = addslashes($str);
        }
        
        return $str;
    }
    /* END */



    /** -------------------------------------
    /**  Clean global input keys
    /** -------------------------------------*/
    
    // To prevent malicious users from trying to exploit keys
    // we make sure that keys are only named with alpha-numeric text

    function clean_input_keys($str)
    {    
		 if ( ! preg_match("#^[a-z0-9\:\_\/\-]+$#i", $str))
		 { 
			exit('Disallowed Key Characters');
		 }

        if ( ! get_magic_quotes_gpc())
        {
            $str = addslashes($str);
        }
        
        return $str;
    }
    /* END */
    
    
    /** --------------------------------------------------
    /**  Fetch a URI segment
    /** --------------------------------------------------*/

    function fetch_uri_segment($n = '')
    {    
        return ( ! isset($this->SEGS[$n])) ? FALSE : $this->SEGS[$n];
    }
    /* END */
    
   
    /** --------------------------------------------------
    /**  Retrieve Get/Post/Server/Cookie variables
    /** --------------------------------------------------*/

    function GBL($which, $type = 'GP')
    {
        global $PREFS;
    
        $allowed_types = array('GP', 'GET', 'POST', 'SERVER', 'COOKIE');
        
        if ( ! in_array($type, $allowed_types))
            return false;            
         
        switch($type)
        {
            case 'GP'    : 
                            if ( ! isset($_POST[$which]) )
                            {
                                if ( ! isset($_GET[$which]) )
                                {
                                    return FALSE;                                
                                }
                                else
                                    return $_GET[$which];
                            }
                            else
                                return $_POST[$which];
                break;
            case 'GET'    : return ( ! isset($_GET[$which]) )    ? FALSE : $_GET[$which];    
                break;
            case 'POST'   : return ( ! isset($_POST[$which]) )   ? FALSE : $_POST[$which];
                break;
            case 'SERVER' : return ( ! isset($_SERVER[$which]) ) ? FALSE : $_SERVER[$which];         
                break;    
            case 'COOKIE' : 
                    
                    $prefix = ( ! $PREFS->ini('cookie_prefix')) ? 'exp_' : $PREFS->ini('cookie_prefix').'_';
                    
                    return ( ! isset($_COOKIE[$prefix.$which]) ) ? FALSE : stripslashes($_COOKIE[$prefix.$which]);
                    
                break;    
        }
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Blacklist Checkers - Added EE 1.2
    /** -------------------------------------*/

    function check_blacklist()
    {
		global $DB, $REGX, $PREFS;
		
		/** ---------------------------
		/**  Check the Referrer Too
		/** ---------------------------*/
				
		if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '')
		{
			$test_ref = $REGX->xss_clean($_SERVER['HTTP_REFERER']);
					
			if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $test_ref))
			{
				if (substr($test_ref, 0, 7) == 'http://' AND substr($test_ref, 0, 11) != 'http://www.')
				{
					$test_ref = preg_replace("#^http://(.+?)#", "http://www.\\1", $test_ref);
				}
			}
					
			$_POST['HTTP_REFERER'] = $test_ref;
		}	
		
		if (sizeof($_POST) == 0 OR ! $DB->table_exists('exp_blacklisted'))
		{
			unset($_POST['HTTP_REFERER']);
			return true;
		}
												
		/** ----------------------------
		/**  Whitelisted Items
		/** ----------------------------*/
		
		$whitelisted_ip		= array();
		$whitelisted_url	= array();
		$whitelisted_agent	= array();
		
		if ($DB->table_exists('exp_whitelisted'))
		{
			$results = $DB->query("SELECT whitelisted_type, whitelisted_value FROM exp_whitelisted 
									WHERE whitelisted_value != ''");
		
			if ($results->num_rows > 0)
			{		
				foreach($results->result as $row)
				{
					if ($row['whitelisted_type'] == 'url')
					{
						$whitelisted_url = explode('|', $row['whitelisted_value']);
					}
					elseif($row['whitelisted_type'] == 'ip')
					{
						$whitelisted_ip = explode('|', $row['whitelisted_value']);
					}
					elseif($row['whitelisted_type'] == 'agent')
					{
						$whitelisted_agent = explode('|', $row['whitelisted_value']);
					}
				}
			}
		}
		
		if ($PREFS->ini('cookie_domain') !== FALSE && $PREFS->ini('cookie_domain') != '')
		{
			$whitelisted_url[] = $PREFS->ini('cookie_domain');
		}
		
		$site_url = $PREFS->ini('site_url');
		
		$whitelisted_url[] = $site_url;
		
		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $site_url))
		{
			if (substr($site_url, 0, 7) == 'http://' AND substr($site_url, 0, 11) != 'http://www.')
			{
				$whitelisted_url[] = preg_replace("#^http://(.+?)#", "http://www.\\1", $site_url);
			}
		}
		

		/** -----------------------------
		/**  Domain Names Array
		/** -----------------------------*/
		
		$domains = array('net','com','org','info', 'name','biz','us','de', 'uk');    	
    	
		/** -----------------------------
		/**  Blacklisted Checking
		/** -----------------------------*/
    	
		$query   = $DB->query("SELECT blacklisted_type, blacklisted_value FROM exp_blacklisted");
    	
		if ($query->num_rows == 0)
		{
			unset($_POST['HTTP_REFERER']);
			return true;
		}
    	
		foreach($query->result as $row)
        {
			if ($row['blacklisted_type'] == 'url' && $row['blacklisted_value'] != '' && $this->whitelisted != 'y')	
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);
				
				if ( ! is_array($blacklist_values) OR sizeof($blacklist_values) == 0)
				{
					continue;
				}
			
				foreach ($_POST as $key => $value)
				{
					// Smallest URL Possible
					// Or no external links
					if (is_array($value) OR strlen($value) < 8)
					{
						continue;
					}
					
					// Convert Entities Before Testing
					$value = $REGX->_html_entity_decode($value);
			
					$value .= ' ';
				
					// Clear period from the end of URLs
					$value = preg_replace("#(^|\s|\()((http://|http(s?)://|www\.)\w+[^\s\)]+)\.([\s\)])#i", "\\1\\2{{PERIOD}}\\4", $value);
				
					if (preg_match_all("/([f|ht]+tp(s?):\/\/[a-z0-9@%_.~#\/\-\?&=]+.)".
									   "|(www.[a-z0-9@%_.~#\-\?&]+.)".
									   "|([a-z0-9@%_~#\-\?&]*\.(".implode('|', $domains)."))/si", $value, $matches))
					{       					
						for($i = 0; $i < sizeof($matches['0']); $i++)
						{
							if ($key == 'HTTP_REFERER' OR $key == 'url')
							{
								$matches['0'][$i] = $value;
							}
							
							foreach($blacklist_values as $bad_url)
							{
								if ($bad_url != '' && stristr($matches['0'][$i], $bad_url) !== false)
								{
									$bad = 'y';
									
									/** --------------------------------------
									/**  Check Bad Against Whitelist - URLs
									/** --------------------------------------*/
									
									if ( is_array($whitelisted_url) && sizeof($whitelisted_url) > 0)
									{
										$parts = explode('?',$matches['0'][$i]);
										
										foreach($whitelisted_url as $pure)
										{
											if ($pure != '' && stristr($parts['0'], $pure) !== false)
											{
												$bad = 'n';
												$this->whitelisted = 'y';
												break;
											}
										}
									}
									
									/** --------------------------------------
									/**  Check Bad Against Whitelist - IPs
									/** --------------------------------------*/
									
									if ( is_array($whitelisted_ip) && sizeof($whitelisted_ip) > 0)
									{
										foreach($whitelisted_ip as $pure)
										{
											if ($pure != '' && strpos($this->IP, $pure) !== false)
											{
												$bad = 'n';        										
												$this->whitelisted = 'y';        										
												break;
											}
										}
									}
									
									if ($bad == 'y')
									{
										if ($key == 'HTTP_REFERER')
										{
											$this->blacklisted = 'y';
										}
										else
										{
											exit('Action Denied: Blacklisted Item Found'."\n<br/>".$matches['0'][$i]);
										}
									}
									else
									{
										break;  // Free to move on
									}
								}        					
							}
						}
					}
				}
			}
			elseif($row['blacklisted_type'] == 'ip' && $row['blacklisted_value'] != '' && $this->whitelisted != 'y')
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);
				
				if ( ! is_array($blacklist_values) OR sizeof($blacklist_values) == 0)
				{
					continue;
				}
				
				foreach($blacklist_values as $bad_ip)
				{
					if ($bad_ip != '' && strpos($this->IP, $bad_ip) === 0) 
					{
						$bad = 'y';
						
						if ( is_array($whitelisted_ip) && sizeof($whitelisted_ip) > 0)
						{
							foreach($whitelisted_ip as $pure)
							{
								if ($pure != '' && strpos($this->IP, $pure) !== false)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}
						
						if ($bad == 'y')
						{
							$this->blacklisted = 'y';
							break;
						}
						else
						{
							unset($_POST['HTTP_REFERER']);
							return true; // whitelisted, so end
						}
					}
				}    			
			}
			elseif($row['blacklisted_type'] == 'agent' && $row['blacklisted_value'] != '' && $this->AGENT != '' && $this->whitelisted != 'y')
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);
				
				if ( ! is_array($blacklist_values) OR sizeof($blacklist_values) == 0)
				{
					continue;
				}
				
				foreach($blacklist_values as $bad_agent)
				{
					if ($bad_agent != '' && stristr($this->AGENT, $bad_agent) !== false)
					{
						$bad = 'y';
						
						if ( is_array($whitelisted_ip) && sizeof($whitelisted_ip) > 0)
						{
							foreach($whitelisted_ip as $pure)
							{
								if ($pure != '' && strpos($this->AGENT, $pure) !== false)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}
						
						if ( is_array($whitelisted_agent) && sizeof($whitelisted_agent) > 0)
						{
							foreach($whitelisted_agent as $pure)
							{
								if ($pure != '' && strpos($this->agent, $pure) !== false)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}
						
						if ($bad == 'y')
						{
							$this->blacklisted = 'y';
						}
						else
						{
							unset($_POST['HTTP_REFERER']);
							return true; // whitelisted, so end
						}
					}
				}    			
			}
		}
		
		unset($_POST['HTTP_REFERER']);
    	
    	return true;    	
    }
    /* END */
    
    
}
// END CLASS
?>