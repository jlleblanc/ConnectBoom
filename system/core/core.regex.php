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
 File: core.regex.php
-----------------------------------------------------
 Purpose: Regular expression library.
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Regex {

	var $xss_hash = '';
	
	/* never allowed, string replacement */
	var $never_allowed_str = array(
									'document.cookie'	=> '[removed]',
									'document.write'	=> '[removed]',
									'.parentNode'		=> '[removed]',
									'.innerHTML'		=> '[removed]',
									'window.location'	=> '[removed]',
									'-moz-binding'		=> '[removed]',
									'<!--'				=> '&lt;!--',
									'-->'				=> '--&gt;',
									'<![CDATA['			=> '&lt;![CDATA['
									);
	/* never allowed, regex replacement */
	var $never_allowed_regex = array(
										"javascript\s*:"			=> '[removed]',
										"expression\s*(\(|&\#40;)"	=> '[removed]', // CSS and IE
										"vbscript\s*:"				=> '[removed]', // IE, surprise!
										"Redirect\s+302"			=> '[removed]'
									);


    /** -------------------------------------
    /**  Validate Email Address
    /** -------------------------------------*/

    function valid_email($address)
    {
		if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address))
			return false;
		else		
			return true;
    }
    /* END */
    


    /** -------------------------------------
    /**  Validate IP Address
    /** -------------------------------------*/

    function valid_ip($ip)
    {
		$ip_segments = explode('.', $ip);
		
		// Always 4 segments needed
		if (count($ip_segments) != 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}
		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be 
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}
		
		return TRUE;
    }
    /* END */


    /** -------------------------------------
    /**  Prep URL
    /** -------------------------------------*/

    function prep_url($str = '')
    {
		if ($str == 'http://' || $str == '')
		{
			return '';
		}
		
		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}
		
		return $str;
    }
    /* END */



    /** -------------------------------------
    /**  Prep Query String
    /** -------------------------------------*/
    
    // This function checks to see if "Force Query Strings" is on
    // If so it adds a question mark to the URL if needed

	function prep_query_string($str)
	{
		global $PREFS;
		
		if (stristr($str, '.php') AND preg_match("#\/index\/$#", $str))
		{
			$str = substr($str, 0, -6);
		}
		
		if ( ! stristr($str, '?') AND $PREFS->ini('force_query_string') == 'y')
		{
			if (stristr($str, '.php'))
			{
				$str = preg_replace("#(.+?)\.php(.*?)#", "\\1.php?\\2", $str);
			}
			else
			{
				$str .= "?";
			}
		}
		
		return $str;
	}
	/* END */


    /** -------------------------------------
    /**  Decode query string entities
    /** -------------------------------------*/

    function decode_qstr($str)
    {
    	return str_replace(array('&#46;','&#63;','&amp;'),
    					   array('.','?','&'),
    					   $str);
    }
    /* END */


    /** --------------------------------------------
    /**  Format HTML so it appears correct in forms
    /** --------------------------------------------*/

    function form_prep($str = '', $strip = 0)
    {
    	global $FNS;
    
        if ($str == '')
        {
            return '';
        }
    
        if ($strip != 0)
        {
            $str = stripslashes($str);
        }
        
        // $str = $FNS->entities_to_ascii($str);
    
		$str = htmlspecialchars($str);
		$str = str_replace("'", "&#39;", $str);
        
        return $str;
    }
    /* END */


    /** -----------------------------------------
    /**  Convert PHP tags to entities
    /** -----------------------------------------*/

    function encode_php_tags($str)
    {        
    	return str_replace(array('<?php', '<?PHP', '<?', '?'.'>'), 
    					   array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), 
    					   $str);
    					   
    					   // <?php BBEdit fix
	}
	/* END */


    /** -------------------------------------
    /**  Convert EE Tags
    /** -------------------------------------*/

	function encode_ee_tags($str, $convert_curly=FALSE)
	{
		if ($str != '')
		{
			if ($convert_curly === TRUE)
			{
				$str = str_replace(array('{', '}'), array('&#123;', '&#125;'), $str);
			}
			else
			{
				$str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
				$str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
				$str = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $str);
				$str = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $str);
			}
		}
		
		return $str;
	}
	/* END */


    /** ----------------------------------------------
    /**  Convert single and double quotes to entites
    /** ----------------------------------------------*/

    function convert_quotes($str)
    {    
    	return str_replace(array("\'","\""), array("&#39;","&quot;"), $str);
    }
    /* END */



    /** -------------------------------------
    /**  Convert reserved XML characters
    /** -------------------------------------*/

    function xml_convert($str, $protect_all = FALSE)
    {
        $temp = '848ff8if9a6fb627facGGcdbcce6';
        
        $str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);
        
        if ($protect_all === TRUE)
        {
        	$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
        }
        
        $str = str_replace(array("&","<",">","\"", "'", "-"),
        				   array("&amp;", "&lt;", "&gt;", "&quot;", "&#39;", "&#45;"),
        				   $str);
            
        $str = preg_replace("/$temp(\d+);/","&#\\1;",$str);
        
       	if ($protect_all === TRUE)
       	{
			$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		}
		
        return stripslashes($str);
    }    
    /* END */


    /** ----------------------------------------
    /**  ASCII to Entities
    /** ----------------------------------------*/

	function ascii_to_entities($str)
	{
		$count	= 1;
		$out	= '';
		$temp	= array();
			
		for ($i = 0, $s = strlen($str); $i < $s; $i++)
		{
			$ordinal = ord($str[$i]);
			
			if ($ordinal < 128)
			{
				/*
					If the $temp array has a value but we have moved on, then it seems only
					fair that we output that entity and restart $temp before continuing -Paul
				*/
				if (count($temp) == 1)
				{
					$out  .= '&#'.array_shift($temp).';';
					$count = 1;
				}
				
				$out .= $str[$i];            
			}
			else
			{
				if (count($temp) == 0)
				{
					$count = ($ordinal < 224) ? 2 : 3;
				}
				
				$temp[] = $ordinal;
				
				if (count($temp) == $count)
				{
					$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
	
					$out .= '&#'.$number.';';
					$count = 1;
					$temp = array();
				}   
			}   
		}
		
		return $out;
	}
	/* END */
	
	
    /** ----------------------------------------
    /**  Entities to ASCII
    /** ----------------------------------------*/

	function entities_to_ascii($str, $all = TRUE)
	{
		global $PREFS;
		
		if (preg_match_all('/\&#(\d+)\;/', $str, $matches))
		{
			if (FALSE && function_exists('mb_convert_encoding'))
			{
				$str = mb_convert_encoding($str, strtoupper($PREFS->ini('charset')), 'HTML-ENTITIES'); 
			}
			else
			{
				// Converts to UTF-8 Bytes
				// http://us2.php.net/manual/en/function.chr.php#55978
				
				for ($i = 0, $s = count($matches['0']); $i < $s; $i++)
				{				
					$digits = $matches['1'][$i];
		
					$out = '';
			
					if ($digits < 128)
					{
						$out .= '&#'.$digits.';';
					} 
					elseif ($digits < 2048)
					{
						$out .= chr(192 + (($digits - ($digits % 64)) / 64));
						$out .= chr(128 + ($digits % 64));
					} 
					else
					{
						$out .= chr(224 + (($digits - ($digits % 4096)) / 4096));
						$out .= chr(128 + ((($digits % 4096) - ($digits % 64)) / 64));
						$out .= chr(128 + ($digits % 64));
					}
					
					// This is a temporary fix for people who are foolish enough not to use UTF-8
					// A more detailed fix could be put in, but the likelihood of this occurring is rare
					// and this is entire functionality is probably going away in 2.0. -Paul
					if(strtolower($PREFS->ini('charset')) == 'iso-8859-1')
					{
						$out = utf8_decode($out);
					}
			
					$str = str_replace($matches['0'][$i], $out, $str);				
				}
			}
		}
		
		if ($all)
		{
			$str = str_replace(array("&amp;", "&lt;", "&gt;", "&quot;", "&#39;", "&#45;"),
							   array("&","<",">","\"", "'", "-"),
	        				   $str);
		}
		
		return $str;
	}
	/* END */



    /** -------------------------------------------------
    /**  Trim slashes "/" from front and back of string
    /** -------------------------------------------------*/

    function trim_slashes($str)
    {
        if (substr($str, 0, 1) == '/')
		{
			$str = substr($str, 1);
		}
		
		if (substr($str, 0, 5) == "&#47;")
		{
			$str = substr($str, 5);
		}
		
		if (substr($str, -1) == '/')
		{
			$str = substr($str, 0, -1);
		}
		
		if (substr($str, -5) == "&#47;")
		{
			$str = substr($str, 0, -5);
		}

        return $str;
    }
    /* END */


    /** -------------------------------------------------
    /**  Removes double commas from string
    /** -------------------------------------------------*/

    function remove_extra_commas($str)
    {
		// Removes space separated commas as well as leading and trailing commas
		$str =  implode(',', preg_split('/[\s,]+/', $str, -1,  PREG_SPLIT_NO_EMPTY));
		        
        return $str;
    }
    /* END */
    
    
    /** -------------------------------------------------
    /**  Strip quotes
    /** -------------------------------------------------*/

    function strip_quotes($str)
    {
    	return str_replace(array('"', "'"), '', $str);
    }
    /* END */


	/** ----------------------------------------
	/**  Clean Keywords - used for searching
	/** ----------------------------------------*/
	
	function keyword_clean($str)
	{	
		//$str = strtolower($str);
		$str = strip_tags($str);
		
		// We allow some words with periods. 
		// This array defines them.
		// Note:  Do not include periods in the array.
		
		$allowed = array( 
							'Mr',
							'Ms',
							'Mrs',
							'Dr'
						);
		
		foreach ($allowed as $val)
		{
			$str = str_replace($val.".", $val."T9nbyrrsXCXv0pqemUAq8ff", $str);
		}
	
		// Remove periods unless they are within a word
	
		$str = preg_replace("#\.*(\s|$)#", " ", $str);
		
		// These are disallowed characters
	
		$chars = array(
						","	,
						"("	,
						")"	,
						"+"	,
						"!"	,
						"?"	,
						"["	,
						"]"	,
						"@"	,
						"^"	,
						"~"	,
						"*"	,
						"|"	,
						"\n",
						"\t"
					  );
		
				
		$str = str_replace($chars, ' ', $str);

		$str = preg_replace("(\s+)", " ", $str);
	
		// Put allowed periods back
		$str = str_replace('T9nbyrrsXCXv0pqemUAq8ff', '.', $str);
		
		// Kill naughty stuff...
		
		$str = $this->xss_clean($str);
		
		return trim($str);
	}
	/* END */


    /** -------------------------------------------------
    /**  Convert disallowed characters into entities
    /** -------------------------------------------------*/

	function convert_dissallowed_chars($str)
	{
		$bad = array(
						"\("	=>	"&#40;", 
						"\)"	=>	"&#41;",
						'\$'	=>	"&#36;",
						"%28"	=>	"&#40;",	// (  
						"%29"	=>	"&#41;",	// ) 
						"%2528"	=>	"&#40;",	// (
						"%24"	=>	"&#36;"		// $
					);

        foreach ($bad as $key => $val)
        {
			$str = preg_replace("#".$key."#i", $val, $str);   
        }

		return $str;
	}
	/* END */
	
	/** -------------------------------------------------
    /**  A Random Hash Used for Protecting URLs
    /** -------------------------------------------------*/

	function xss_protection_hash()
	{	
		global $FNS;
		
		if ($this->xss_hash == '')
		{
			/*
			 * We cannot use the $FNS random() method, so we create something that while
			 * not perfectly random will serve our purposes well enough
			 */
			 
			if (phpversion() >= 4.2)
				mt_srand();
			else
				mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
			
			$this->xss_hash = md5(time() + mt_rand(0, 1999999999));
		}
		
		return $this->xss_hash;
	}
	/* END */
	
	
    /** -------------------------------------------------
    /**  XSS hacking stuff
    /** -------------------------------------------------*/

	function xss_clean($str, $is_image = FALSE)
	{	
		global $PREFS;

		/* ----------------------------------
		/*  Every so often an array will be sent to this function,
		/*  and so we simply go through the array, clean, and return
		/* ----------------------------------*/
		
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->xss_clean($str[$key]);
			}
			
			return $str;
		}
		
		$charset = strtoupper($PREFS->ini('charset'));
				
		/*
		 * Remove Invisible Characters
		 */
		$str = $this->_remove_invisible_characters($str);
		
		/*
		 * Protect GET variables in URLs
		 */
		 
		 // 901119URL5918AMP18930PROTECT8198
		 
		$str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_protection_hash()."\\1=\\2", $str);
		
		/*
		 * Validate standard character entities
		 *
		 * Add a semicolon if missing.  We do this to enable
		 * the conversion of entities to ASCII later.
		 *
		 */
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
		
		/*
		 * Validate UTF16 two byte encoding (x00) 
		 *
		 * Just as above, adds a semicolon if missing.
		 *
		 */
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);
		
		/*
		 * Un-Protect GET variables in URLs
		 */
		 
		$str = str_replace($this->xss_protection_hash(), '&', $str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Use rawurldecode() so it does not removes plus signs
		 *
		 */	
      	$str = rawurldecode($str);

		/*
		 * Convert character entities to ASCII 
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 *
		 */
		
		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
		 
		$str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_html_entity_decode_callback'), $str);

		/*
		 * Remove Invisible Characters Again!
		 */
		$str = $this->_remove_invisible_characters($str);
			
		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
		 * so we use str_replace.
		 *
		 */

 		if (strpos($str, "\t") !== FALSE)
		{
			$str = str_replace("\t", ' ', $str);
		}
		
		/*
		 * Capture converted string for later comparison
		 */
		$converted_string = $str;
		
		/*
		 * Not Allowed Under Any Conditions
		 */	
		
		foreach ($this->never_allowed_str as $key => $val)
		{
			$str = str_replace($key, $val, $str);   
		}
	
		foreach ($this->never_allowed_regex as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}
	
		/*
		 * Makes PHP tags safe
		 *
		 *  Note: XML tags are inadvertently replaced too:
		 *
		 *	<?xml
		 *
		 * But it doesn't seem to pose a problem.
		 *
		 */
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and closing tags every so often
			// so we skip those and only do the long opening tags.
			$str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
		}
		
		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 *
		 */		
		$words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
		foreach ($words as $word)
		{
			$temp = '';
			
			for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}
			
			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}
	
		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared
		 * to these simplified non-capturing preg_match(), especially if the pattern exists in the string
		 */
		do
		{
			$original = $str;
	
			if (preg_match("/<a/i", $str))
			{
				$str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
			}
	
			if (preg_match("/<img/i", $str))
			{
				$str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
			}
	
			if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
			{
				$str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
			}
		}
		while($original != $str);
		
		unset($original);

		/*
		 * Remove JavaScript Event Handlers
		 *
		 * Note: This code is a little blunt.  It removes
		 * the event handler and anything upto the closing >, 
		 * but it's unlkely to be a problem.
		 *
		 */
		$event_handlers = array('[^a-z_\-]on\w*','xmlns');

		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images, including namespacing, 
			 * so we have to allow this for images. -Paul
			 */
			unset($event_handlers[array_search('xmlns', $event_handlers)]);
		}
		
		$str = preg_replace("#<([^><]+?)(".implode('|', $event_handlers).")(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str);
	
		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list 
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 *
		 */		
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);
				
		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed.  Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:		eval&#40;'some code'&#41;
		 *
		 */
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
						
		/*
		 * Final clean up
		 *
		 * This adds a bit of extra precaution in case
		 * something got through the above filters
		 *
		 */	
		foreach ($this->never_allowed_str as $key => $val)
		{
			$str = str_replace($key, $val, $str);   
		}
	
		foreach ($this->never_allowed_regex as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}
		
		/* ----------------------------------
		/*  Images are Handled in a Special Way
		/*  - Essentially, we want to know that after all of the character conversion is done whether
		/*  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
		/*  However, if the string post-conversion does not matched the string post-removal of XSS,
		/*  then it fails, as there was unwanted XSS code found and removed/changed during processing.
		/* ----------------------------------*/
		
		if ($is_image === TRUE)
		{
			if ($str == $converted_string)
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
				
		return $str;
	}
	// END xss_clean()	
	
	/** -------------------------------------------------
    /**  Remove Invisible Characters
	/**  This prevents sandwiching null characters
	/**  between ascii characters, like Java\0script.
    /** -------------------------------------------------*/
	
	function _remove_invisible_characters($str)
	{
		static $non_displayables;
	
		if ( ! isset($non_displayables))
		{
			// every control character except newline (dec 10), carriage return (dec 13), and horizontal tab (dec 09),
			$non_displayables = array(
										'/%0[0-8bcef]/',			// url encoded 00-08, 11, 12, 14, 15
										'/%1[0-9a-f]/',				// url encoded 16-31
										'/[\x00-\x08]/',			// 00-08
										'/\x0b/', '/\x0c/',			// 11, 12
										'/[\x0e-\x1f]/'				// 14-31
									);
		}

		do
		{
			$cleaned = $str;
			$str = preg_replace($non_displayables, '', $str);
		}
		while ($cleaned != $str);

		return $str;
	}
	// END _remove_invisible_characters()
	
	/** -------------------------------------------------
    /**  Compact Exploded Words
	/**  Callback function for xss_clean() to remove whitespace from
	/**  things like j a v a s c r i p t
    /** -------------------------------------------------*/

	function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}
	// END _compact_exploded_words()
	
	/** -------------------------------------------------
    /**  Sanitize Naughty HTML
    /**  Callback function for xss_clean() to remove naughty HTML elements
    /** -------------------------------------------------*/

	function _sanitize_naughty_html($matches)
	{
		// encode opening brace
		$str = '&lt;'.$matches[1].$matches[2].$matches[3];
		
		// encode captured opening or closing brace to prevent recursive vectors
		$str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
		
		return $str;
	}
	// END _sanitize_naughty_html()
	
	/** -------------------------------------------------
    /**  JS Link Removal
    /**  Callback function to sanitize links
    /** -------------------------------------------------*/

	function _js_link_removal($match)
	{
		$attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
	}
	// END _js_link_removal()
	
	/** -------------------------------------------------
    /**  JS Image Removal
	/**  Callback function to sanitize image tags
    /** -------------------------------------------------*/
	
	function _js_img_removal($match)
	{
		$attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
	}
	// END _js_img_removal()

	/** -------------------------------------------------
    /**  Filter Attributes
	/**  Filters tag attributes for consistency and safety
    /** -------------------------------------------------*/
	
	function _filter_attributes($str)
	{
		$out = '';
		
		// EE 1.x adds slashes to all input, so there's a good chance we'll encounter attr=\"foo\" which
		// we account for with by optionally matching on the octal of a backslash (\134) before the quote
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\134)?(\042|\047)([^\\2]*?)\\2#is', $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$out .= preg_replace("#/\*.*?\*/#s", '', $match);
			}			
		}

		return $out;
	}
	// END _filter_attributes()
	
    /** -------------------------------------------------
    /**  Create URL Title
    /** -------------------------------------------------*/

	function create_url_title($str, $lowercase = FALSE)
	{
		global $PREFS;
		
		if (function_exists('mb_convert_encoding'))
		{
			$str = mb_convert_encoding($str, 'ISO-8859-1', 'auto');
		}
		elseif(function_exists('iconv') AND ($iconvstr = @iconv('', 'ISO-8859-1', $str)) !== FALSE)
		{
			$str = $iconvstr;
		}
		else
		{
			$str = utf8_decode($str);
		}
		
		if ($lowercase === TRUE)
		{
			$str = strtolower($str);	
		}
		
		$str = preg_replace_callback('/(.)/', array($this, "convert_accented_characters"), $str);
		
		$str = strip_tags($str);
		
		// Use dash or underscore as separator		
		$replace = ($PREFS->ini('word_separator') == 'dash') ? '-' : '_';
		
		$trans = array(
						'&\#\d+?;'				=> '',
						'&\S+?;'				=> '',
						'\s+'					=> $replace,
						'[^a-z0-9\-\._]'		=> '',
						$replace.'+'			=> $replace,
						$replace.'$'			=> $replace,
						'^'.$replace			=> $replace,
						'\.+$'					=> ''
					  );
					   
		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);
		} 
		
		$str = trim(stripslashes($str));

		return $str;
	}
	/* END */
	
	
	/** ---------------------------------------
	/**  Convert Accented Characters to Unaccented Equivalents
	/** ---------------------------------------*/
	
	function convert_accented_characters($match)
	{
		global $EXT;

		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		*/  
			if (isset($EXT->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = $EXT->call_extension('foreign_character_conversion_array');
			}
			else
			{
		    	$foreign_characters = array('223'	=>	"ss", // ß
		    								'224'	=>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
		    								'227'	=>	"ae", '230'	=>	"ae", '228' => "ae",
		    								'231'	=>	"c",
		    								'232'	=>	"e",  // è
		    								'233'	=>	"e",  // é
		    								'234'	=>	"e",  // ê  								
		    								'235'	=>	"e",  // ë
		    								'236'	=>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
		    								'241'	=>	"n",
		    								'242'	=>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
		    								'246'	=>	"oe", // ö
		    								'249'	=>  "u",  '250' =>  "u", '251' => "u",
		    								'252'	=>	"ue", // ü
		    								'255'	=>	"y",
		    								'257'	=>	"aa", 
											'269'	=>	"ch", 
											'275'	=>	"ee", 
											'291'	=>	"gj", 
											'299'	=>	"ii", 
											'311'	=>	"kj", 
											'316'	=>	"lj", 
											'326'	=>	"nj", 
											'353'	=>	"sh", 
											'363'	=>	"uu", 
											'382'	=>	"zh",
											'256'	=>	"aa", 
											'268'	=>	"ch", 
											'274'	=>	"ee", 
											'290'	=>	"gj", 
											'298'	=>	"ii", 
											'310'	=>	"kj", 
											'315'	=>	"lj", 
											'325'	=>	"nj", 
											'352'	=>	"sh", 
											'362'	=>	"uu", 
											'381'	=>	"zh",
		    								);				
			}
		/*
		/* -------------------------------------*/
    								
    	$ord = ord($match['1']);
    		
		if (isset($foreign_characters[$ord]))
		{
			return $foreign_characters[$ord];
		}
		else
		{
			return $match['1'];
		}
	}
	/* END */
	
	/** -------------------------------------------------
    /**  Used for a callback in XSS Clean
    /** -------------------------------------------------*/
    
    function _convert_attribute($match)
    {
    	return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }
    
    /* END */
	
	
	/** -------------------------------------------------
    /**  Replacement for html_entity_decode()
    /** -------------------------------------------------*/
    
    /*
    NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the 
    character set, and the PHP developers said they were not back porting the
    fix to versions other than PHP 5.x.
    */
    
    function _html_entity_decode_callback($match)
    {
    	global $PREFS;
    	return $this->_html_entity_decode($match[0], strtoupper($PREFS->ini('charset')));
    }
	
	function _html_entity_decode($str, $charset='ISO-8859-1') 
	{
		if (stristr($str, '&') === FALSE) return $str;
	
		// The reason we are not using html_entity_decode() by itself is because
		// while it is not technically correct to leave out the semicolon
		// at the end of an entity most browsers will still interpret the entity
		// correctly.  html_entity_decode() does not convert entities without
		// semicolons, so we are left with our own little solution here. Bummer.
		
		if ( ! in_array(strtoupper($charset), 
						array('ISO-8859-1', 'ISO-8859-15', 'UTF-8', 'cp866', 'cp1251', 'cp1252', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'Shift_JIS', 'EUC-JP')))
		{
			$charset = 'ISO-8859-1';
		}
	
		if (function_exists('html_entity_decode') && (strtolower($charset) != 'utf-8' OR version_compare(phpversion(), '5.0.0', '>=')))
		{
			$str = html_entity_decode($str, ENT_QUOTES, $charset);
			$str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
			return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
		}
		
		// Numeric Entities
		$str = preg_replace('~&#x(0*[0-9a-f]{2,5});{0,1}~ei', 'chr(hexdec("\\1"))', $str);
		$str = preg_replace('~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str);
	
		// Literal Entities - Slightly slow so we do another check
		if (stristr($str, '&') === FALSE)
		{
			$str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
		}
		
		return $str;
	}
	/* END */
	
	function unhtmlentities($str)
	{
		return $this->_html_entity_decode($str);
	}


	/** -------------------------------------------------
    /**  Removes slashes from array
    /** -------------------------------------------------*/

     function array_stripslashes($vals)
     {
     	if (is_array($vals))
     	{	
     		foreach ($vals as $key=>$val)
     		{
     			$vals[$key] = $this->array_stripslashes($val);
     		}
     	}
     	else
     	{
     		$vals = stripslashes($vals);
     	}
     	
     	return $vals;
	}
	/* END */

}
// END CLASS
?>