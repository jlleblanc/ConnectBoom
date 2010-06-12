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
 File: core.typography.php
-----------------------------------------------------
 Purpose: Typographic rendering class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Typography {

    var $single_line_pgfs			= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
    var $text_format    			= 'xhtml';  // xhtml, br, none, or lite
    var $html_format    			= 'safe';   // safe, all, none
    var $auto_links     			= 'y'; 
    var $allow_img_url  			= 'n';
    var $parse_images   			= FALSE;
    var $encode_email   			= TRUE;
	var $encode_type				= 'javascript'; // javascript or noscript
    var $use_span_tags  			= TRUE;
    var $popup_links    			= FALSE;
    var $bounce						= '';
    var $smiley_array        		= FALSE;
    var $parse_smileys				= TRUE;
    var $highlight_code				= TRUE;
    var $convert_curly				= TRUE;		// Convert Curly Brackets Into Entities
    var $emoticon_path  			= '';
    var $site_index					= '';
    var $word_censor    			= FALSE;
    var $censored_words 			= array();
    var $censored_replace			= '';
    var $file_paths     			= array();
    var $text_fmt_types				= array('xhtml', 'br', 'none', 'lite');
    var $text_fmt_plugins			= array();
    var $html_fmt_types				= array('safe', 'all', 'none');
    var $yes_no_syntax				= array('y', 'n');
    var $code_chunks				= array();
    var $code_counter				= 0;
	var $http_hidden 				= 'ed9f01a60cc1ac21bf6f1684e5a3be23f38a51b9'; // hash to protect URLs in [url] pMcode
 	
	// Block level elements that should not be wrapped inside <p> tags
	var $block_elements = 'address|blockquote|div|dl|fieldset|form|h\d|hr|noscript|object|ol|p|pre|script|table|ul';
	
	// Elements that should not have <p> and <br /> tags within them.
	var $skip_elements	= 'p|pre|ol|ul|dl|object|table|h\d';
	
	// Tags we want the parser to completely ignore when splitting the string.
	var $inline_elements = 'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|i|img|ins|input|label|map|kbd|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';

	// array of block level elements that require inner content to be within another block level element
	var $inner_block_required = array('blockquote');
	
	// the last block element parsed
	var $last_block_element = '';
		
	// whether or not to protect quotes within { curly braces }
	var $protect_braced_quotes = FALSE;
    
    /** -------------------------------------
    /**  Allowed tags
    /** -------------------------------------*/
    
    // Note: The decoding array is associative, allowing more precise mapping
           
    var $safe_encode = array('b', 'i', 'em', 'del', 'ins', 'strong', 'pre', 'code', 'blockquote', 'abbr');
    
    var $safe_decode = array(
                                'b'             => 'b', 
                                'i'             => 'i',
                                'em'            => 'em',
								'del'			=> 'del',
								'ins'			=> 'ins',
                                'strong'        => 'strong', 
                                'pre'           => 'pre', 
                                'code'          => 'code', 
                                'blockquote'    => 'blockquote',
                                'quote'         => 'blockquote',
                                'QUOTE'         => 'blockquote',
								'abbr'			=> 'abbr'
                             );
    


    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Typography($parse_images = TRUE, $allow_headings = TRUE)
    {
        global $PREFS, $FNS;

    	$this->protect_braced_quotes = TRUE;

        if ($parse_images == TRUE)
        {
            $this->file_paths = $FNS->fetch_file_paths();
        }
        
        $this->parse_images = $parse_images;
        
        if ($allow_headings == TRUE)
        {
        	foreach (array('h2', 'h3', 'h4', 'h5', 'h6') as $val)
        	{
        		$this->safe_encode[] = $val;
        		$this->safe_decode[$val] = $val;
        	}
        }
            
        /** -------------------------------------
        /**  Fetch emoticon prefs
        /** -------------------------------------*/
        
        if ($PREFS->ini('enable_emoticons') == 'y')
        {
            if (is_file(PATH_MOD.'emoticon/emoticons'.EXT))
            {
                require PATH_MOD.'emoticon/emoticons'.EXT;
                
                if (is_array($smileys))
                {
                    $this->smiley_array = $smileys;
                    $this->emoticon_path = $PREFS->ini('emoticon_path', 1);
                }
            }
        }
        
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- popup_link => Have links created by Typography class open in a new window (y/n)
        /* -------------------------------------------*/
        
        if ($PREFS->ini('popup_link') !== FALSE)
        {
            $this->popup_links = ($PREFS->ini('popup_link') == 'y') ? TRUE : FALSE;
        }

        /** -------------------------------------
        /**  Fetch word censoring prefs
        /** -------------------------------------*/
        
        if ($PREFS->ini('enable_censoring') == 'y' AND $PREFS->ini('censored_words') != '')
        {
			if ($PREFS->ini('censor_replacement') !== FALSE)
			{
				$this->censored_replace = $PREFS->ini('censor_replacement');
			}
		
			$words = preg_replace("/\s+/", "", trim($PREFS->ini('censored_words')));
			
			$words = str_replace('||', '|', $words);
	
			if (substr($words, -1) == "|")
			{
				$words = substr($words, 0, -1);
			}
					
			$this->censored_words = explode("|", $words);
			
			if (count($this->censored_words) > 0)
			{
				$this->word_censor = TRUE;
			}
        }
        

        /** -------------------------------------
        /**  Fetch plugins
        /** -------------------------------------*/
        
		$this->text_fmt_plugins = $this->fetch_plugins();  
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Fetch installed plugins
    /** -------------------------------------*/
    
    function fetch_plugins()
    {
        global $PREFS;
        
        $exclude = array('auto_xhtml');
    
        $filelist = array();
    
        if ($fp = @opendir(PATH_PI)) 
        { 
            while (false !== ($file = readdir($fp))) 
            {
            	if ( preg_match("/pi\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
            	{
					$file = substr($file, 3, - strlen(EXT));
				
					if ( ! in_array($file, $exclude))
					{
						$filelist[] = $file;
					}
				}
            } 
            
            closedir($fp);
        } 
    
        sort($filelist);
		return $filelist;      
    }
    /* END */


    /** ----------------------------------------
    /**  Parse file paths
    /** ----------------------------------------*/

    function parse_file_paths($str)
    {
        global $DB;
        
        if ($this->parse_images == FALSE OR count($this->file_paths) == 0)
        {
            return $str;
        }
        
        foreach ($this->file_paths as $key => $val)
        {
			$str = str_replace(array("{filedir_{$key}}", "&#123;filedir_{$key}&#125;"), $val, $str);
        }

        return $str;
    }
    /* END */


    /** -------------------------------------
    /**  Typographic parser
    /** -------------------------------------*/
    
    // Note: The processing order is very important in this function so don't change it!
    
    function parse_type($str, $prefs = '')
    {
    	global $REGX, $FNS, $EXT, $IN;
    	     
        if ($str == '')
        {
            return;    
        }
        
		if (strpos($this->inline_elements, '|u|strike') === FALSE)
		{
			$this->inline_elements .= '|u|strike';
		}

        // -------------------------------------------
        // 'typography_parse_type_start' hook.
		//  - Modify string prior to all other typography processing
		//
			if ($EXT->active_hook('typography_parse_type_start') === TRUE)
			{
				$str = $EXT->call_extension('typography_parse_type_start', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

        /** -------------------------------------
        /**  Encode PHP tags
        /** -------------------------------------*/
        
        // Before we do anything else, we'll convert PHP tags into character entities.
        // This is so that PHP submitted in weblog entries, comments, etc. won't get parsed.
        // Since you can enable templates to parse PHP, it would open up a security
        // hole to leave PHP submitted in entries and comments intact.
        
		$str = $REGX->encode_php_tags($str);

        /** -------------------------------------
        /**  Encode EE tags
        /** -------------------------------------*/
		
		// Next, we need to encode EE tags contained in entries, comments, etc. so that they don't get parsed.
				
		$str = $REGX->encode_ee_tags($str, $this->convert_curly);  
		    
        /** -------------------------------------
        /**  Set up our preferences
        /** -------------------------------------*/
        
        if (is_array($prefs))
        {
            if (isset($prefs['text_format']))
            {
				if ($prefs['text_format'] != 'none')
				{
					if (in_array($prefs['text_format'], $this->text_fmt_types))
					{
						$this->text_format = $prefs['text_format'];
					}
					else
					{
						if (in_array($prefs['text_format'], $this->text_fmt_plugins) AND file_exists(PATH_PI.'pi.'.$prefs['text_format'].EXT))
						{
							$this->text_format = $prefs['text_format'];
						}
					}
				}
				else
				{
					$this->text_format = 'none';
				}
            }
        
            if (isset($prefs['html_format']) AND in_array($prefs['html_format'], $this->html_fmt_types))
            {
                $this->html_format = $prefs['html_format'];
            }
        
            if (isset($prefs['auto_links']) AND in_array($prefs['auto_links'], $this->yes_no_syntax))
            {
                $this->auto_links = $prefs['auto_links'];
            }

            if (isset($prefs['allow_img_url'])  AND in_array($prefs['allow_img_url'], $this->yes_no_syntax))
            {
            	$this->allow_img_url = $prefs['allow_img_url'];
            }
        }
        
        /** -------------------------------------
        /**  Are single lines considered paragraphs?
        /** -------------------------------------*/
                
		if ($this->single_line_pgfs != TRUE)
		{
			if ($this->text_format == 'xhtml' AND ! preg_match("/(\015\012)|(\015)|(\012)/", $str))
			{
				$this->text_format = 'lite';
			}
        }
        
        /** -------------------------------------
        /**  Fix emoticon bug
        /** -------------------------------------*/
        
        $str = str_replace(array('>:-(', '>:('), array(':angry:', ':mad:'), $str);
        
        
        /** -------------------------------------
        /**  Highlight text within [code] tags
        /** -------------------------------------*/
        
        // If highlighting is enabled, we'll highlight <pre> tags as well.
        
        if ($this->highlight_code == TRUE)
        {
			$str = str_replace(array('[pre]', '[/pre]'), array('[code]', '[/code]'), $str);
        }        
            
		// We don't want pMcode parsed if it's within code examples so we'll convert the brackets
        
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
		{      		
			for ($i = 0; $i < count($matches['1']); $i++)
			{				
				$temp = str_replace(array('[', ']'), array('&#91;', '&#93;'), $matches['1'][$i]);
				$str  = str_replace($matches['0'][$i], '[code]'.$temp.'[/code]', $str);
			}			
		}
        
		if ($this->highlight_code == TRUE)
		{
			$str = $this->text_highlight($str);
		}
		else
		{
			$str = str_replace(array('[code]', '[/code]'),	array('<code>', '</code>'),	$str);
		}        

        /** -------------------------------------
        /**  Strip IMG tags if not allowed
        /** -------------------------------------*/

        if ($this->allow_img_url == 'n')
        {
            $str = $this->strip_images($str);
        }

        /** -------------------------------------
        /**  Format HTML
        /** -------------------------------------*/
    
        $str = $this->format_html($str);

        /** -------------------------------------
        /**  Auto-link URLs and email addresses
        /** -------------------------------------*/
                
        if ($this->auto_links == 'y' AND $this->html_format != 'none')
        {
            $str = $this->auto_linker($str);
        }
        
		/** -------------------------------------
        /**  Parse file paths (in images)
        /** -------------------------------------*/

        $str = $this->parse_file_paths($str);

		/** ---------------------------------------
		/**  Convert HTML links in CP to pMcode
		/** ---------------------------------------*/
		
		// Forces HTML links output in the control panel to pMcode so they will be formatted
		// as redirects, to prevent the control panel address from showing up in referrer logs
		// except when sending emails, where we don't want created links piped through the site
		
		if (REQ == 'CP' && $IN->GBL('M', 'GET') != 'send_email')
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}
		
        /** -------------------------------------
        /**  Decode pMcode
        /** -------------------------------------*/
    
        $str = $this->decode_pmcode($str);
  
        /** -------------------------------------
        /**  Format text
        /** -------------------------------------*/

        switch ($this->text_format)
        {
            case 'none';
                break;
            case 'xhtml'	: $str = $this->auto_typography($str);
                break;
            case 'lite'		: $str = $this->format_characters($str);  // Used with weblog entry titles
                break;
            case 'br'		: $str = $this->nl2br_except_pre($str);
                break;
            default			:
            
			if ( ! class_exists('Template'))
			{
				global $TMPL;
				require PATH_CORE.'core.template'.EXT;
				$TMPL = new Template();
			}            
			
			$plugin = ucfirst($prefs['text_format']);
			
			if ( ! class_exists($plugin))
			{	
				require_once PATH_PI.'pi.'.$prefs['text_format'].EXT;
			}
			
			if (class_exists($plugin))
			{
				$PLG = new $plugin($str);
			
				if (isset($PLG->return_data))
				{
					$str = $PLG->return_data;
				}
			}
            
            	break;
        }
        
        /** -------------------------------------
        /**  Parse emoticons
        /** -------------------------------------*/

        $str = $this->emoticon_replace($str);
        
        /** -------------------------------------
        /**  Parse censored words
        /** -------------------------------------*/

        $str = $this->filter_censored_words($str);

        /** ------------------------------------------
        /**  Decode and spam-protect email addresses
        /** ------------------------------------------*/
        
        // {encode="you@yoursite.com" title="Click Me"}
        
        // Note: We only do this here if it's a CP request since the
        // template parser handles this for page requets
        
        if (REQ == 'CP')
        {
			if (preg_match_all("/\{encode=(.+?)\}/i", $str, $matches))
			{	
				for ($j = 0; $j < count($matches['0']); $j++)
				{	
					$str = str_replace($matches['0'][$j], $FNS->encode_email($matches['1'][$j]), $str);
				}
			}  		
        }
        
        // Standard email addresses
        
        $str = $this->decode_emails($str);
        
        /** ------------------------------------------
        /**  Insert the cached code tags
        /** ------------------------------------------*/
        
		// The hightlight function called earlier converts the original code strings into markers
		// so that the auth_xhtml function doesn't attempt to process the highlighted code chunks.
		// Here we convert the markers back to their correct state.
        
        if (count($this->code_chunks) > 0)
        {
        	foreach ($this->code_chunks as $key => $val)
        	{
       			$str = str_replace('{'.$key.'yH45k02wsSdrp}', $val, $str);
        	}

			$this->code_chunks = array();
        }
        
        // -------------------------------------------
        // 'typography_parse_type_end' hook.
		//  - Modify string after all other typography processing
		//
			if ($EXT->active_hook('typography_parse_type_end') === TRUE)
			{
				$str = $EXT->call_extension('typography_parse_type_end', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

        return $str;
    }
    /* END */


    /** -------------------------------------
    /**  Format HTML
    /** -------------------------------------*/

    function format_html($str)
    {
    	global $REGX;
    
        $html_options = array('all', 'safe', 'none');
    
        if ( ! in_array($this->html_format, $html_options))
        {
            $this->html_format = 'safe';
        }

        if ($this->html_format == 'all')
        {
            return $str;
        }

        if ($this->html_format == 'none')
        {
            return $this->encode_tags($str);
        }
    
        /** -------------------------------------
        /**  Permit only safe HTML
        /** -------------------------------------*/
        
        $str = $REGX->xss_clean($str);
        
        // We strip any JavaScript event handlers from image links or anchors
        // This prevents cross-site scripting hacks.
        
     	$js = array(   
						'onblur',
						'onchange',
						'onclick',
						'onfocus',
						'onload',
						'onmouseover',
						'onmouseup',
						'onmousedown',
						'onselect',
						'onsubmit',
						'onunload',
						'onkeypress',
						'onkeydown',
						'onkeyup',
						'onresize'
					);
        
        
		foreach ($js as $val)
		{
			$str = preg_replace("/<img src\s*=(.+?)".$val."\s*\=.+?\>/i", "<img src=\\1 />", $str);
			$str = preg_replace("/<a href\s*=(.+?)".$val."\s*\=.+?\>/i", "<a href=\\1>", $str);			
		}        
        
        // Turn <br /> tags into newlines
        
		$str = preg_replace("#<br>|<br />#i", "\n", $str);
		
		// Strip paragraph tags
		
		$str = preg_replace("#<p>|<p[^>]*?>|</p>#i", "",  preg_replace("#<\/p><p[^>]*?>#i", "\n", $str));

        // Convert allowed HTML to pMcode
        
        foreach($this->safe_encode as $val)
        {
            $str = preg_replace("#<".$val.">(.+?)</".$val.">#si", "[$val]\\1[/$val]", $str);
        }

        // Convert anchors to pMcode
        // We do this to prevent allowed HTML from getting converted in the next step
        // Old method would only convert links that had href= as the first tag attribute
		// $str = preg_replace("#<a\s+href=[\"'](\S+?)[\"'](.*?)\>(.*?)</a>#si", "[url=\"\\1\"\\2]\\3[/url]", $str);
		        
		$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);

        // Convert image tags pMcode

		$str = str_replace("/>", ">", $str);
        
        $str = preg_replace("#<img(.*?)src=\s*[\"'](.+?)[\"'](.*?)\s*\>#si", "[img]\\2\\3\\1[/img]", $str);

        $str = preg_replace( "#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)\.(jpg|jpeg|gif|png)#i", "\\1[img]http\\4://\\5\\6.\\7[/img]", $str);

        return $this->encode_tags($str);
    }
    /* END */



    /** -------------------------------------
    /**  Auto link URLs and email addresses
    /** -------------------------------------*/

    function auto_linker($str)
    {
    	global $FNS, $PREFS, $IN;
    	  
        $str .= ' ';
        
        // We don't want any links that appear in the control panel (in weblog entries, comments, etc.)
        // to point directly at URLs.  Why?  Becuase the control panel URL will end up in people's referrer logs, 
        // This would be a bad thing.  So, we'll point all links to the "bounce server"
                
		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';

        $this->bounce = ((REQ == 'CP' && $IN->GBL('M', 'GET') != 'send_email') || $PREFS->ini('redirect_submitted_links') == 'y') ? $FNS->fetch_site_index().$qm.'URL=' : '';
        
        // Protect URLs that are already in [url] pMCode
        $str = preg_replace("/(\[url[^\]]*?\])http/is", '${1}'.$this->http_hidden, str_replace('[url=http', '[url='.$this->http_hidden, $str));
        
        // New version.  Blame Paul if it doesn't work
        // The parentheses on the end attempt to call any content after the URL. 
        // This way we can make sure it is not [url=http://site.com]http://site.com[/url]
		$str = preg_replace_callback("#(^|\s|\(|..\])((http(s?)://)|(www\.))(\w+[^\s\)\<\[]+)#im", array(&$this, 'auto_linker_callback'), $str);

        // Auto link email
        $str = preg_replace("/(^|\s|\(|\>)([a-zA-Z0-9_\.\-]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", "\\1[email]\\2@\\3.\\4[/email]", $str);
        
         // Clear period(s) from the end of emails
        $str = preg_replace("|(\.+)\[\/email\]|i ", "[/email]\\1", $str);
        
        // UnProtect URLs that are already in [url] pMCode
        $str = str_replace($this->http_hidden, 'http', $str);
 
        return substr($str, 0, -1);  // Removes space added above
    }
    /* END */
    
	/** -------------------------------------
    /**  Callback function used above
    /** -------------------------------------*/
    function auto_linker_callback($matches)
    {
    	global $PREFS;
    	
    	//  If it is in pMCode, then we do not auto link
    	if (strtolower($matches['1']) == 'mg]' OR 
    		strtolower($matches['1']) == 'rl]' OR
    		strtolower(substr(trim($matches[6]), 0, 6)) == '[/url]'
    	   )
		{
			return $matches['0'];
    	}
    	
    	/** -----------------------------------
		/**  Moved the Comment and Period Modification Here
		/** -----------------------------------*/
    	
		$end = '';
		
    	if (preg_match("/^(.+?)([\.\,]+)$/",$matches['6'], $punc_match))
    	{
    		$end = $punc_match[2];
    		$matches[6] = $punc_match[1];
    	}
		
		/** -----------------------------------
		/**  Modified 2006-02-07 to send back pMCode instead of HTML.  Insures correct sanitizing.
		/** -----------------------------------*/
		
		return	$matches['1'].'[url=http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].']http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'[/url]'.
				$end;
		
		/** -----------------------------------
		/**  Old Way
		/** -----------------------------------*/
		
		$url_core = (REQ == 'CP' || $PREFS->ini('redirect_submitted_links') == 'y') ? urlencode($matches['6']) : $matches['6'];

    	return	$matches['1'].'<a href="'.$this->bounce.'http'.
				$matches['4'].'://'.
				$matches['5'].
				$url_core.'"'.(($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '').'>http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'</a>'.
				$end;
    }
	/* END */


    /** -------------------------------------
    /**  Decode pMcode
    /** -------------------------------------*/

    function decode_pmcode($str)
    {
    	global $FNS, $PREFS, $IN;
		
        /** -------------------------------------
        /**  Remap some deprecated tags with valid counterparts
        /** -------------------------------------*/
		
		$str = str_replace(array('[strike]', '[/strike]', '[u]', '[/u]'), array('[del]', '[/del]', '[em]', '[/em]'), $str);
		
        /** -------------------------------------
        /**  Decode pMcode array map
        /** -------------------------------------*/
               
        foreach($this->safe_decode as $key => $val)
        {
			$str = str_replace(array('['.$key.']', '[/'.$key.']'),	array('<'.$val.'>', '</'.$val.'>'),	$str);
        }

        /** -------------------------------------
        /**  Decode codeblock division for code tag
        /** -------------------------------------*/

		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				$str = str_replace('[div class="codeblock"]{'.$key.'yH45k02wsSdrp}[/div]', '<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>', $str);
			}
 		}
        
        /** -------------------------------------
        /**  Decode color tags
        /** -------------------------------------*/
        
        if ($this->use_span_tags == TRUE)
        {
            $str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<span style=\"color:\\1;\">\\2</span>",$str);
        }    
        else
        {
            $str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<font color=\"\\1\">\\2</font>", $str);
        }
        
        /** -------------------------------------
        /**  Decode size tags
        /** -------------------------------------*/

        if ($this->use_span_tags == TRUE)
        {
            $str = preg_replace_callback("/\[size=(.*?)\](.*?)\[\/size\]/si", array($this, "font_matrix"),$str);
        }    
        else
        {
            $str = preg_replace("/\[size=(.*?)\](.*?)\[\/size\]/si", "<font color=\"\\1\">\\2</font>", $str);
        }

        /** -------------------------------------
        /**  Convert [url] tags to links 
        /** -------------------------------------*/
        
        $qm		= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $bounce	= ((REQ == 'CP' && $IN->GBL('M', 'GET') != 'send_email') || $PREFS->ini('redirect_submitted_links') == 'y') ? $FNS->fetch_site_index().$qm.'URL=' : '';

        $bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie'); // everything else
        $bad_things2 = array('[', '(', ')', '!', '*', '>', '<', "\t", 'document.cookie'); // style,title attributes
        $exceptions	 = array('http://', 'https://', 'irc://', 'feed://', 'ftp://', 'ftps://', 'mailto:', '/', '#');
        $allowed	 = array('rel', 'title', 'class', 'style', 'target');
        
        if (preg_match_all("/\[url(.*?)\](.*?)\[\/url\]/i", $str, $matches))
        {
        	for($i=0, $s=sizeof($matches['0']), $add=TRUE; $i < $s; ++$i)
        	{
        		$matches['1'][$i] = trim($matches['1'][$i]);
        		
        		$url = ($matches['1'][$i] != '') ? trim($matches['1'][$i]) : $matches['2'][$i];
        		$extra = '';
        		
				// remove all attributes except for the href in "Safe" HTML formatting
				// Also force links output in the CP with the Typography class as "safe" so that
				// any other tag attributes that it might have are not slapped in with the URL
        		if (($this->html_format == 'safe' OR REQ == 'CP') && stristr($matches['1'][$i],' '))
        		{
        			for($a=0, $sa=sizeof($allowed); $a < $sa; ++$a)
        			{
        				if (($p1 = strpos($url, $allowed[$a].'=')) !== FALSE)
        				{
        					$marker = substr($url, $p1 + strlen($allowed[$a].'='), 1);

        					if ($marker != "'" && $marker != '"') continue;
        					
        					$p2	= strpos(substr($url, $p1 + strlen($allowed[$a].'=') + 1), $marker);
        					
        					if ($p2 === FALSE) continue;
        					
        					// Do not make me explain the math here, it gives me a headache - Paul
        					
        					$inside = str_replace((($allowed[$a] == 'style' OR $allowed[$a] == 'title') ? $bad_things2 : $bad_things), 
        										  '', 
        										  substr($url, $p1 + strlen($allowed[$a].'=') + 1, $p2));
        										  
        					$extra .= ' '.$allowed[$a].'='.$marker.$inside.$marker;
        				}
        			}
        			
					// remove everything but the URL up to the first space
       				$url = substr($url, 0, strpos($url, ' '));

					// get rid of opening = and surrounding quotes
					$url = preg_replace(array('/^=(\042|\047)?/', '/(\042|\047)$/'), '', $url);

					// url encode a few characters that we want to allow, in the wiki for example
					$url = str_replace(array('"', "'", '!'), array('%22', '%27', '%21'), $url);
        		}
				else
				{
					// get rid of opening = and surrounding quotes (again for allow all!)
					$url = preg_replace(array('/^=(\042|\047)?/', '/(\042|\047)$/'), '', $url);	
				}
				
        		// Clean out naughty stuff from URL.
        		$url = ($this->html_format == 'all') ? str_replace($bad_things2, '', $url) : str_replace($bad_things, '', $url);
        		        		
        		$add = TRUE;
        		
        		foreach($exceptions as $exception)
        		{
        			if (substr($url, 0, strlen($exception)) == $exception)
        			{
        				$add = FALSE; break;
        			}
        		}
        	
        		if ($add === TRUE)
        		{
        			$url = "http://".$url;
        		}
        		
        		$extra .= (($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '');
        		
        		if ($bounce != '')
        		{
        			$url = urlencode($url);
        		}
        		
        		$str = str_replace($matches['0'][$i], '<a href="'.$bounce.trim($url).'"'.$extra.'>'.$matches['2'][$i]."</a>", $str);
        	}
        }

        /** -------------------------------------
        /**  Image tags
        /** -------------------------------------*/

        // [img] and [/img]
        
        if ($this->allow_img_url == 'y')
        {        
            $str = preg_replace_callback("/\[img\](.*?)\[\/img\]/i", array($this, "image_sanitize"), $str);
            //$str = preg_replace("/\[img\](.*?)\[\/img\]/i", "<img src=\\1 />", $str);
        }
        elseif($this->auto_links == 'y' && $this->html_format != 'none')
        {
        	if (preg_match_all("/\[img\](.*?)\[\/img\]/i", $str, $matches))
        	{
        		for($i=0, $s=sizeof($matches['0']); $i < $s; ++$i)
        		{
        			$str = str_replace($matches['0'][$i], '<a href="'.$bounce.str_replace($bad_things, '', $matches['1'][$i]).'">'.str_replace($bad_things, '', $matches['1'][$i])."</a>", $str);
        		}
        	}
        }
        else
        {
            $str = preg_replace("/\[img\](.*?)\[\/img\]/i", "\\1", $str);
        }
        
        // Add quotes back to image tag if missing
        
        if (preg_match("/\<img src=[^\"\'].*?\>/i", $str))
        {
			$str = preg_replace("/<img src=([^\"\'\s]+)(.*?)\/\>/i", "<img src=\"\\1\" \\2/>", $str);
        }
        
        /** -------------------------------------
        /**  Style tags
        /** -------------------------------------*/
        
        // [style=class_name]stuff..[/style]  
    
        $str = preg_replace("/\[style=(.*?)\](.*?)\[\/style\]/si", "<span class=\"\\1\">\\2</span>", $str);    

		/** ---------------------------------------
		/**  Attributed quotes, used in the Forum module
		/** ---------------------------------------*/
		
		// [quote author="Brett" date="11231189803874"]...[/quote]
		
		if (preg_match_all('/\[quote\s+(author=".*?"\s+date=".*?")\]/si', $str, $matches))
		{
			for ($i = 0; $i < count($matches['1']); $i++)
			{			
				$str = str_replace('[quote '.$matches['1'][$i].']', '<blockquote '.$matches['1'][$i].'>', $str);
			}        
		}
		
        return $str;
    }
    /* END */
    
    /** -----------------------------------------
    /**  Make images safe
    /** -----------------------------------------*/
    
    // This simply removes parenthesis so that javascript event handlers
    // can't be invoked. 

	function image_sanitize($matches)
	{		
		$url = str_replace(array('(', ')'), '', $matches['1']);
		
		if (preg_match("/\s+alt=(\"|\')([^\\1]*?)\\1/", $matches['1'], $alt_match))
		{
			$url = trim(str_replace($alt_match['0'], '', $url));
			$alt = str_replace(array('"', "'"), '', $alt_match['2']);
		}
		else
		{
			$alt = str_replace(array('"', "'"), '', $url);
			
			if (substr($alt, -1) == '/')
			{
				$alt = substr($alt, 0, -1);
			}
			
			$alt = substr($alt, strrpos($alt, '/')+1);
		}
		
		return "<img src=".$url." alt='".$alt."' />";
	}

    
    /** -----------------------------------------
    /**  Decode and spam protect email addresses
    /** -----------------------------------------*/

    function decode_emails($str)
    {                    
        // [email=your@yoursite]email[/email]

        $str = preg_replace_callback("/\[email=(.*?)\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
        
        // [email]joe@xyz.com[/email]

        $str = preg_replace_callback("/\[email\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
        
        return $str;
    }
    /* END */
    

    /** -------------------------------------
    /**  Format Email via callback
    /** -------------------------------------*/

    function create_mailto($matches)
    {   
        $title = ( ! isset($matches['2'])) ? $matches['1'] : $matches['2'];
    
        if ($this->encode_email == TRUE)
        {
            return $this->encode_email($matches['1'], $title, TRUE);
        }
        else
        {
            return "<a href=\"mailto:".$matches['1']."\">".$title."</a>";        
        }
    }
    /* END */
    

    /** ----------------------------------------
    /**  Font sizing matrix via callback
    /** ----------------------------------------*/

    function font_matrix($matches)
    {
        switch($matches['1'])
        {
            case 1  : $size = '9px';
                break;
            case 2  : $size = '11px';
                break;
            case 3  : $size = '14px';
                break;
            case 4  : $size = '16px';
                break;
            case 5  : $size = '18px';
                break;
            case 6  : $size = '20px';
                break;
            default : $size = '11px';
                break;
        }
    
        return "<span style=\"font-size:".$size.";\">".$matches['2']."</span>";
    }
    /* END */

    
    
    /** -------------------------------------
    /**  Encode tags
    /** -------------------------------------*/
    
    function encode_tags($str) 
    {  
		return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
    }
    /* END */



    /** -------------------------------------
    /**  Strip IMG tags
    /** -------------------------------------*/

    function strip_images($str)
    {    
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?)\s*\>#", "\\1", $str);
                
        return $str;
    }
    /* END */



    /** -------------------------------------
    /**  Emoticon replacement
    /** -------------------------------------*/

    function emoticon_replace($str)
    {
        if ($this->smiley_array === FALSE OR $this->parse_smileys === FALSE)
        {
            return $str;
        }
        
        $str = ' '.$str;
        
        foreach ($this->smiley_array as $key => $val)
		{
			if (strpos($str, $key) !== FALSE)
			{
				$img = "<img src=\"".$this->emoticon_path.$this->smiley_array[$key]['0']."\" width=\"".$this->smiley_array[$key]['1']."\" height=\"".$this->smiley_array[$key]['2']."\" alt=\"".$this->smiley_array[$key]['3']."\" style=\"border:0;\" />";
			
				foreach(array(' ', "\t", "\n", "\r", '.', ',', '>') as $char)
				{
					$str = str_replace($char.$key, $char.$img, $str);
				}
			}
		}
        
        return ltrim($str);
    }
    /* END */



    /** -------------------------------------
    /**  Word censor
    /** -------------------------------------*/

    function filter_censored_words($str)
    {
    	global $REGX;
    	
        if ($this->word_censor == FALSE)
        {
            return $str;    
        }
        
        $str = ' '.$str.' ';

		// \w, \b and a few others do not match on a unicode character
		// set for performance reasons. As a result words like über
		// will not match on a word boundary. Instead, we'll assume that
		// a bad word will be bookeneded by any of these characters.
		$delim = '[-_\'\"`(){}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

		foreach ($this->censored_words as $badword)
		{
			// We have entered the high ASCII range, which means it is likely
			// that this character is a complete word or symbol that is not 
			// allowed. So, instead of a preg_replace with a word boundary
			// we simply do a string replace for this bad word.
			if ((strlen($badword) == 4 OR strlen($badword) == 2) && stristr($badword, '*') === FALSE && ord($badword['0']) > 127 && ord($badword['1']) > 127)
			{
				$str = str_replace($badword, (($this->censored_replace != '') ? $this->censored_replace : '#'), $str);
			}
			else
			{
				if ($this->censored_replace != '')
				{
					$str = preg_replace("/({$delim})(".str_replace('\*', '\w*?', preg_quote($badword, '/')).")({$delim})/i", "\\1{$this->censored_replace}\\3", $str);
				}
				else
				{
					$str = preg_replace("/({$delim})(".str_replace('\*', '\w*?', preg_quote($badword, '/')).")({$delim})/ie", "'\\1'.str_repeat('#', strlen('\\2')).'\\3'", $str);
				}
			}
		}

        return trim($str);
    }
    /* END */



    /** -------------------------------------
    /**  Colorize code strings
    /** -------------------------------------*/
		
	function text_highlight($str)
	{		
		// No [code] tags?  No reason to live.  Goodbye cruel world...
		
		if ( ! preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
		{      
			return $str;
		}
		
		for ($i = 0; $i < count($matches['1']); $i++)
		{
			$temp = trim($matches['1'][$i]);
			//$temp = $this->decode_pmcode(trim($matches['1'][$i]));
			
			// Turn <entities> back to ascii.  The highlight string function
			// encodes and highlight brackets so we need them to start raw 
			
			$temp = str_replace(array('&lt;', '&gt;'), array('<', '>'), $temp);
			
			// Replace any existing PHP tags to temporary markers so they don't accidentally
			// break the string out of PHP, and thus, thwart the highlighting.
			// While we're at it, convert EE braces
			
			$temp = str_replace(array('<?', '?>', '{', '}', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '</script>'), 
									  array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  $temp);
				
			
			// The highlight_string function requires that the text be surrounded
			// by PHP tags, which we will remove later
			$temp = '<?php '.$temp.' ?>'; // <?
			
			// All the magic happens here, baby!	
			$temp = highlight_string($temp, TRUE);

			// Prior to PHP 5, the highligh function used icky <font> tags
			// so we'll replace them with <span> tags.
			
			if (abs(PHP_VERSION) < 5)
			{
				$temp = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $temp);
				$temp = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $temp);
			}
			
			// Remove our artificially added PHP, and the syntax highlighting that came with it
			$temp = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $temp);
			$temp = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $temp);
			$temp = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $temp);
			
			// Replace our markers back to PHP tags.

			$temp = str_replace(array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  array('&lt;?', '?&gt;', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '&lt;/script&gt;'), 
									  $temp); //<?

			// Cache the code chunk and insert a marker into the original string.
			// we do this so that the auth_xhtml function which gets called later
			// doesn't process our new code chunk
						
			$this->code_chunks[$this->code_counter] = $temp;

			$str = str_replace($matches['0'][$i], '[div class="codeblock"]{'.$this->code_counter.'yH45k02wsSdrp}[/div]', $str);
			
			$this->code_counter++;
		}        

		return $str;
	}
	/* END */
	


    /** -------------------------------------
    /**  NL to <br /> - Except within <pre>
    /** -------------------------------------*/
    
    function nl2br_except_pre($str)
    {
        $ex = explode("pre>",$str);
        $ct = count($ex);
        
        $newstr = "";
        
        for ($i = 0; $i < $ct; $i++)
        {
            if (($i % 2) == 0)
                $newstr .= nl2br($ex[$i]);
            else 
                $newstr .= $ex[$i];
            
            if ($ct - 1 != $i) 
                $newstr .= "pre>";
        }
        
        return $newstr;
    }
    /* END */


    /** -------------------------------------
    /**  Convert ampersands to entities
    /** -------------------------------------*/

    function convert_ampersands($str)
    {
        $str = preg_replace("/&#(\d+);/", "AMP14TX903DVGHY4QW\\1;", $str);
        $str = preg_replace("/&(\w+);/",  "AMP14TX903DVGHY4QT\\1;", $str);
        
        return str_replace(array("&","AMP14TX903DVGHY4QW","AMP14TX903DVGHY4QT"),array("&amp;", "&#","&"), $str);
	}
    /* END */

	// --------------------------------------------------------------------

	/**
	 * Old version - use auto_typography() now
	 */	
	function xhtml_typography($str)
	{
		return $this->auto_typography($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Auto Typography
	 *
	 * This function converts text, making it typographically correct:
	 * 	- Converts double spaces into paragraphs.
	 * 	- Converts single line breaks into <br /> tags
	 * 	- Converts single and double quotes into correctly facing curly quote entities.
	 * 	- Converts three dots into ellipsis.
	 * 	- Converts double dashes into em-dashes.
	 *  - Converts two spaces into entities
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	whether to reduce more then two consecutive newlines to two
	 * @return	string
	 */
	function auto_typography($str, $reduce_linebreaks = FALSE)
	{
		if ($str == '')
		{
			return '';
		}

		// Standardize Newlines to make matching easier
		if (strpos($str, "\r") !== FALSE)
		{
			$str = str_replace(array("\r\n", "\r"), "\n", $str);			
		}
			
		// Reduce line breaks.  If there are more than two consecutive linebreaks
		// we'll compress them down to a maximum of two since there's no benefit to more.
		if ($reduce_linebreaks === TRUE)
		{
			$str = preg_replace("/\n\n+/", "\n\n", $str);
		}   

		// HTML comment tags don't conform to patterns of normal tags, so pull them out separately, only if needed
		$html_comments = array();
		if (strpos($str, '<!--') !== FALSE)
		{
			if (preg_match_all("#(<!\-\-.*?\-\->)#s", $str, $matches))
			{
				for ($i = 0, $total = count($matches[0]); $i < $total; $i++)
				{
					$html_comments[] = $matches[0][$i];
					$str = str_replace($matches[0][$i], '{@HC'.$i.'}', $str);
				}
			}
		}
		
		// match and yank <pre> tags if they exist.  It's cheaper to do this separately since most content will
		// not contain <pre> tags, and it keeps the PCRE patterns below simpler and faster
		if (strpos($str, '<pre') !== FALSE)
		{
			$str = preg_replace_callback("#<pre.*?>.*?</pre>#si", array($this, '_protect_characters'), $str);
		}
		
		// Convert quotes within tags to temporary markers.
		$str = preg_replace_callback("#<.+?>#si", array($this, '_protect_characters'), $str);

		// Do the same with braces if necessary
		if ($this->protect_braced_quotes === TRUE)
		{
			$str = preg_replace_callback("#\{.+?\}#si", array($this, '_protect_characters'), $str);		
		}
				
		// Convert "ignore" tags to temporary marker.  The parser splits out the string at every tag 
		// it encounters.  Certain inline tags, like image tags, links, span tags, etc. will be 
		// adversely affected if they are split out so we'll convert the opening bracket < temporarily to: {@TAG}
		$str = preg_replace("#<(/*)(".$this->inline_elements.")([ >])#i", "{@TAG}\\1\\2\\3", $str);

		// Split the string at every tag.  This expression creates an array with this prototype:
		// 
		// 	[array]
		// 	{
		// 		[0] = <opening tag>
		// 		[1] = Content...
		// 		[2] = <closing tag>
		// 		Etc...
		// 	}	
		$chunks = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
		// Build our finalized string.  We cycle through the array, skipping tags, and processing the contained text	
		$str = '';
		$process = TRUE;
		$paragraph = FALSE;
		$current_chunk = 0;
		$total_chunks = count($chunks);
		
		foreach ($chunks as $chunk)
		{ 
			$current_chunk++;
			
			// Are we dealing with a tag? If so, we'll skip the processing for this cycle.
			// Well also set the "process" flag which allows us to skip <pre> tags and a few other things.
			if (preg_match("#<(/*)(".$this->block_elements.").*?>#", $chunk, $match))
			{
				if (preg_match("#".$this->skip_elements."#", $match[2]))
				{
					$process =  ($match[1] == '/') ? TRUE : FALSE;
				}
				
				if ($match[1] == '')
				{
					$this->last_block_element = $match[2];
				}

				$str .= $chunk;
				continue;
			}
			
			if ($process == FALSE)
			{
				$str .= $chunk;
				continue;
			}
			
			//  Force a newline to make sure end tags get processed by _format_newlines()
			if ($current_chunk == $total_chunks)
			{
				$chunk .= "\n";  
			}
			
			//  Convert Newlines into <p> and <br /> tags
			$str .= $this->_format_newlines($chunk);
		}
		
		// No opening block level tag?  Add it if needed.
		if ( ! preg_match("/^\s*<(?:".$this->block_elements.")/i", $str))
		{
			$str = preg_replace("/^(.*?)<(".$this->block_elements.")/i", '<p>$1</p><$2', $str);
		}
		
		// Convert quotes, elipsis, em-dashes, non-breaking spaces, and ampersands
		$str = $this->format_characters($str);
		
		// restore HTML comments
		for ($i = 0, $total = count($html_comments); $i < $total; $i++)
		{
			// remove surrounding paragraph tags, but only if there's an opening paragraph tag
			// otherwise HTML comments at the ends of paragraphs will have the closing tag removed
			// if '<p>{@HC1}' then replace <p>{@HC1}</p> with the comment, else replace only {@HC1} with the comment
			$str = preg_replace('#(?(?=<p>\{@HC'.$i.'\})<p>\{@HC'.$i.'\}(\s*</p>)|\{@HC'.$i.'\})#s', $html_comments[$i], $str);
		}
				
		// Final clean up
		$table = array(
		
						// If the user submitted their own paragraph tags within the text
						// we will retain them instead of using our tags.
						'/(<p[^>*?]>)<p>/'		=> '$1', // <?php BBEdit syntax coloring bug fix
						
						// Reduce multiple instances of opening/closing paragraph tags to a single one
						'#(</p>)+#'			=> '</p>',
						'/(<p>\W*<p>)+/'	=> '<p>',
						
						// Clean up stray paragraph tags that appear before block level elements
						'#<p></p><('.$this->block_elements.')#'	=> '<$1',

						// Clean up stray non-breaking spaces preceeding block elements
						'#(&nbsp;\s*)+<('.$this->block_elements.')#'	=> '  <$2',

						// Replace the temporary markers we added earlier
						'/\{@TAG\}/'		=> '<',
						'/\{@DQ\}/'			=> '"',
						'/\{@SQ\}/'			=> "'",
						'/\{@DD\}/'			=> '--',
						'/\{@NBS\}/'		=> '  '

						);
		
		// Do we need to reduce empty lines?
		if ($reduce_linebreaks === TRUE)
		{
			$table['#<p>\n*</p>#'] = '';
		}
		else
		{
			// If we have empty paragraph tags we add a non-breaking space
			// otherwise most browsers won't treat them as true paragraphs
			$table['#<p></p>#'] = '<p>&nbsp;</p>';
		}
		
		return preg_replace(array_keys($table), $table, $str);

	}
	
	// --------------------------------------------------------------------

	/**
	 * Format Characters
	 *
	 * This function mainly converts double and single quotes
	 * to curly entities, but it also converts em-dashes,
	 * double spaces, and ampersands
	 */
	function format_characters($str)
	{
		static $table;
		
		if ( ! isset($table))
		{
			$table = array(					
							// nested smart quotes, opening and closing
							// note that rules for grammar (English) allow only for two levels deep
							// and that single quotes are _supposed_ to always be on the outside
							// but we'll accommodate both
							// Note that in all cases, whitespace is the primary determining factor
							// on which direction to curl, with non-word characters like punctuation
							// being a secondary factor only after whitespace is addressed.
							'/\'"(\s|$)/'					=> '&#8217;&#8221;$1',
							'/(^|\s|<p>)\'"/'				=> '$1&#8216;&#8220;',
							'/\'"(\W)/'						=> '&#8217;&#8221;$1',
							'/(\W)\'"/'						=> '$1&#8216;&#8220;',
							'/"\'(\s|$)/'					=> '&#8221;&#8217;$1',
							'/(^|\s|<p>)"\'/'				=> '$1&#8220;&#8216;',
							'/"\'(\W)/'						=> '&#8221;&#8217;$1',
							'/(\W)"\'/'						=> '$1&#8220;&#8216;',

							// single quote smart quotes
							'/\'(\s|$)/'					=> '&#8217;$1',
							'/(^|\s|<p>)\'/'				=> '$1&#8216;',
							'/\'(\W)/'						=> '&#8217;$1',
							'/(\W)\'/'						=> '$1&#8216;',

							// double quote smart quotes
							'/"(\s|$)/'						=> '&#8221;$1',
							'/(^|\s|<p>)"/'					=> '$1&#8220;',
							'/"(\W)/'						=> '&#8221;$1',
							'/(\W)"/'						=> '$1&#8220;',

							// apostrophes
							"/(\w)'(\w)/"					=> '$1&#8217;$2',

							// Em dash and ellipses dots
							'/\s?\-\-\s?/'					=> '&#8212;',
							'/(\w)\.{3}/'					=> '$1&#8230;',

							// double space after sentences
							'/(\W)  /'						=> '$1&nbsp; ',

							// ampersands, if not a character entity
							'/&(?!#?[a-zA-Z0-9]{2,};)/'		=> '&amp;'
						);
		}

		return preg_replace(array_keys($table), $table, $str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Old version - use format_characters() now
	 */	
	function light_xhtml_typography($str)
	{
		return $this->format_characters($str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Format Newlines
	 *
	 * Converts newline characters into either <p> tags or <br />
	 *
	 */	
	function _format_newlines($str)
	{
		if ($str == '')
		{
			return $str;
		}
		
		if (strpos($str, "\n") === FALSE  && ! in_array($this->last_block_element, $this->inner_block_required))
		{
			return $str;
		}
		
		// Convert two consecutive newlines to paragraphs
		$str = str_replace("\n\n", "</p>\n\n<p>", $str);
		
		// Convert single spaces to <br /> tags
		$str = preg_replace("/([^\n])(\n)([^\n])/", "\\1<br />\\2\\3", $str);
		
		// Wrap the whole enchilada in enclosing paragraphs
		if ($str != "\n")
		{
			$str =  '<p>'.$str.'</p>';
		}

		// Remove empty paragraphs if they are on the first line, as this
		// is a potential unintended consequence of the previous code
		$str = preg_replace("/<p><\/p>(.*)/", "\\1", $str, 1);
		
		return $str;
	}

	// --------------------------------------------------------------------
		
	/**
	 * Protect Characters
	 *
	 * Protects special characters from being formatted later
	 * We don't want quotes converted within tags so we'll temporarily convert them to {@DQ} and {@SQ}
 	 * and we don't want double dashes converted to emdash entities, so they are marked with {@DD}
 	 * likewise double spaces are converted to {@NBS} to prevent entity conversion
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	function _protect_characters($match)
	{
		return str_replace(array("'",'"','--','  '), array('{@SQ}', '{@DQ}', '{@DD}', '{@NBS}'), $match[0]);
	}

	// --------------------------------------------------------------------
		
    /** -------------------------------------
    /**  Encode Email Address
    /** -------------------------------------*/

    function encode_email($email, $title = '', $anchor = TRUE)
    {
		global $FNS, $TMPL, $LANG;
	
		if (is_object($TMPL) AND isset($TMPL->encode_email) AND $TMPL->encode_email == FALSE)
		{
			return $email;
		}
	
        if ($title == "")
            $title = $email;
        
		if (isset($this->encode_type) AND $this->encode_type == 'noscript')
		{
			$email = str_replace(array('@', '.'), array(' '.$LANG->line('at').' ', ' '.$LANG->line('dot').' '), $email);
			return $email;
		}
		
        $bit = array();
        
        if ($anchor == TRUE)
        { 
            $bit[] = '<'; $bit[] = 'a '; $bit[] = 'h'; $bit[] = 'r'; $bit[] = 'e'; $bit[] = 'f'; $bit[] = '='; $bit[] = '\"'; $bit[] = 'm'; $bit[] = 'a'; $bit[] = 'i'; $bit[] = 'l';  $bit[] = 't'; $bit[] = 'o'; $bit[] = ':';
        }
        
        for ($i = 0; $i < strlen($email); $i++)
        {
            $bit[] .= " ".ord(substr($email, $i, 1));
        }
        
        $temp	= array();
        
        if ($anchor == TRUE)
        {        
            $bit[] = '\"'; $bit[] = '>';
            
            for ($i = 0; $i < strlen($title); $i++)
            {
            	$ordinal = ord($title[$i]);
			
				if ($ordinal < 128)
				{
					$bit[] = " ".$ordinal;            
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
		
						$bit[] = " ".$number;
						$count = 1;
						$temp = array();
					}   
				}
            }
            
            $bit[] = '<'; $bit[] = '/'; $bit[] = 'a'; $bit[] = '>';
       }
        
        $bit = array_reverse($bit);
		$span_id = 'eeEncEmail_'.$FNS->random('alpha', 10);
        ob_start();
        
?>
<span id='<?php echo $span_id; ?>'>.<?php echo $LANG->line('encoded_email'); ?></span><script type="text/javascript">
//<![CDATA[
var l=new Array();
var output = '';
<?php
    
    $i = 0;
    foreach ($bit as $val)
    {
?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php
    }
?>

for (var i = l.length-1; i >= 0; i=i-1){ 
if (l[i].substring(0, 1) == ' ') output += "&#"+unescape(l[i].substring(1))+";"; 
else output += unescape(l[i]);
}
document.getElementById('<?php echo $span_id; ?>').innerHTML = output;
//]]>
</script><?php

        $buffer = ob_get_contents();
        ob_end_clean(); 
        return $buffer;        
    }
    /* END */

}
// END CLASS
?>