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
 File: core.functions.php
-----------------------------------------------------
 Purpose: Shared system functions.
=====================================================
*/



if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Functions {  
   
    var $seed 			= FALSE; // Whether we've seeded our rand() function.  We only seed once per script execution
    var $cached_url		= array();
    var $cached_path	= array();
    var $cached_index	= array();
    var $cached_captcha	= '';
    var $template_map	= array();
    var $template_type	= '';
    var $action_ids		= array();
    var $file_paths     = array();
    var $conditional_debug = FALSE;
	var $catfields		= array();
  
  
    /** ----------------------------------------
    /**  Set Full server path 
    /** ----------------------------------------*/
   
	function set_realpath($path)
	{
        if (@realpath($path) !== FALSE)
        {
            $path = realpath($path).'/';
        }
    
		return str_replace("\\", "/", $path);	
	}
	/* END */
	
   
    /** ----------------------------------------
    /**  Fetch base site index
    /** ----------------------------------------*/
 
    function fetch_site_index($add_slash = 0, $sess_id = 1)
    {
        global $PREFS, $TMPL, $SESS;
        
        if (isset($this->cached_index[$add_slash.$sess_id.$this->template_type]))
        {
        	return $this->cached_index[$add_slash.$sess_id.$this->template_type];
        }
                
        $url = $PREFS->ini('site_url', 1);
                
        if (USER_BLOG !== FALSE)
        {
            $url .= USER_BLOG.'/';
        }
        
        $url .= $PREFS->ini('site_index');
                
        if ($PREFS->ini('force_query_string') == 'y')
        {
        	$url .= '?';
        }        

		if (is_object($SESS) && ! empty($SESS->userdata['session_id']) && REQ != 'CP' && $sess_id == 1 && 
			$PREFS->ini('user_session_type') != 'c' && $this->template_type == 'webpage')
		{ 
			$url .= "/S=".$SESS->userdata('session_id')."/";
		}
        
        if ($add_slash == 1)
        {
            if (substr($url, -1) != '/')
            {
                $url .= "/";
            }
        }
        
		$this->cached_index[$add_slash.$sess_id.$this->template_type] = $url;
        return $url;
    } 
    /* END */
        

    /** ----------------------------------------
    /**  Create a custom URL
    /** ----------------------------------------*/
    
    // The input to this function is parsed and added to the
    // full site URL to create a full URL/URI
    
    function create_url($segment, $trailing_slash = true, $sess_id = 1)
    {
        global $PREFS, $REGX, $SESS;
                
        // Since this function can be used via a callback
        // we'll fetch the segiment if it's an array
        
        if (is_array($segment))
        {
            $segment = $segment['1'];
        }
        
        if (isset($this->cached_url[$segment]))
        {
        	return $this->cached_url[$segment];
        }

		$full_segment = $segment;         
		$segment = str_replace(array("'", '"'), '', $segment);
		$segment = preg_replace("/(.+?(&#47;|\/))index(&#47;|\/)(.*?)/", "\\1\\2", $segment);       
		$segment = preg_replace("/(.+?(&#47;|\/))index$/", "\\1", $segment);
		        
        /** --------------------------
        /**  Specials
        /** --------------------------*/
        
        // These are exceptions to the normal path rules
        
        if (strtolower($segment) == 'site_index')
        {
            return $this->fetch_site_index();
        }
        
        if (strtolower($segment)  == 'logout')
        {
            $qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
            return $this->fetch_site_index(0, 0).$qs.'ACT='.$this->fetch_action_id('Member', 'member_logout');
        }
                
        // END Specials
                  
        $base = $this->fetch_site_index(0, $sess_id).'/'.$REGX->trim_slashes($segment);
        
        if (substr($base, -1) != '/' && $trailing_slash == TRUE)
        {
            $base .= '/';
        }
        
		$out = $this->remove_double_slashes($base);           
                       
		$this->cached_url[$full_segment] = $out;
                       
        return $out;
    }
    /* END */


	function create_page_url($base_url, $segment, $trailing_slash = true)
	{
       global $REGX;
       
       $base = $base_url.'/'.$REGX->trim_slashes($segment);
       
       if (substr($base, -1) != '/' && $trailing_slash == TRUE)
       {
           $base .= '/';
       }
       
       $out = $this->remove_double_slashes($base);
               
       return $out;          
	}



    /** ----------------------------------------
    /**  Fetch site index with URI query string
    /** ----------------------------------------*/
 
    function fetch_current_uri()
    { 
        global $IN;
           
		return $this->remove_double_slashes($this->fetch_site_index().$IN->URI);
    } 
    /* END */
    
    
    /** -----------------------------------------
    /**  Remove duplicate slashes from URL
    /** -----------------------------------------*/
    
    // With all the URL/URI parsing/building, there is the potential
    // to end up with double slashes.  This is a clean-up function.

    function remove_double_slashes($str)
    {  
		$str = str_replace("://", "{:SS}", $str);
		$str = str_replace(":&#47;&#47;", "{:SHSS}", $str);  // Super HTTP slashes saved!
		$str = preg_replace("#/+#", "/", $str);
		$str = preg_replace("/(&#47;)+/", "/", $str);
		$str = str_replace("&#47;/", "/", $str);
		$str = str_replace("{:SHSS}", ":&#47;&#47;", $str);
		$str = str_replace("{:SS}", "://", $str);
	
		return $str;
    }
    /* END */

    
    /** ----------------------------------------
    /**  Remove session ID from string
    /** ----------------------------------------*/
    
    // This function is used mainly by the Input class to strip
    // session IDs if they are used in public pages.
 
    function remove_session_id($str)
    {
		return preg_replace("#S=.+?/#", "", $str);
    } 
    /* END */


    /** -----------------------------------------
    /**  Extract path info
    /** -----------------------------------------*/
    
    // We use this to extract the template group/template name
    // from path variables, like {some_var path="weblog/index"}

    function extract_path($str)
    {
        global $REGX;
                    
        if (preg_match("#=(.*)#", $str, $match))
        {        
			if (isset($this->cached_path[$match['1']]))
			{
				return $this->cached_path[$match['1']];
			}
        
        	$path = $REGX->trim_slashes(str_replace(array("'",'"'), "", $match['1']));
        	
        	if (substr($path, -6) == 'index/')
			{
				$path = str_replace('/index', '', $path);
			}
			
			if (substr($path, -5) == 'index')
			{
				$path = str_replace('/index', '', $path);
			}
			
			$this->cached_path[$match['1']] = $path;
        
            return $path;
        }
        else
        {
            return 'SITE_INDEX';
        }
    }
    /* END */

        
    /** ----------------------------------------
    /**  Replace variables
    /** ----------------------------------------*/
		
	function var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}
	
		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}
	
		return $str;
	}
	/* END */


    /** ----------------------------------------
    /**  Redirect
    /** ----------------------------------------*/
    
    function redirect($location)
    {    
        global $PREFS;
                
        $location = str_replace('&amp;', '&', $this->insert_action_ids($location));
                
        switch($PREFS->ini('redirect_method'))
        {
            case 'refresh' : header("Refresh: 0;url=$location");
                break;
            default        : header("Location: $location");
                break;
        }
        
        exit;
    }
    /* END */


    /** ----------------------------------------
    /**  Bounce
    /** ----------------------------------------*/
    
    function bounce($location = '')
    {
        if ($location == '')
            $location = BASE;
            
        $this->redirect($location);
        exit;
    }
    /* END */
    
    

    /** -------------------------------------------------
    /**  Convert a string into an encrypted hash
    /** -------------------------------------------------*/
    
    // SHA1 or MD5 is supported
    
    function hash($str)
    {
		global $PREFS;
		
		if ($PREFS->ini('encryption_type') == 'md5')
		{
			return md5($str);
		}
    		    
        if ( ! function_exists('sha1'))
        {
            if ( ! function_exists('mhash'))
            {
				if ( ! class_exists('SHA'))
				{
					require PATH_CORE.'core.sha1'.EXT;    
				}
            
                $SH = new SHA;

                return $SH->encode_hash($str);            
            }
            else
            {
                return bin2hex(mhash(MHASH_SHA1, $str));
            }
        }
        else
        {
            return sha1($str);
        }
    }
    /* END */



    /** -------------------------------------------------
    /**  Random number/password generator
    /** -------------------------------------------------*/
    
    function random($type = 'encrypt', $len = 8)
    {
        if ($this->seed == FALSE)
        {
            if (phpversion() >= 4.2)
                mt_srand();
            else
                mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
            
            $this->seed = TRUE;
        }
                        
        switch($type)
        {
            case 'basic'	: return mt_rand();  
              break;
            case 'alpha'	:
            case 'numeric'	:
            case 'nozero'	:
            
            		switch ($type)
            		{
            			case 'alpha'	:	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            				break;
            			case 'numeric'	:	$pool = '0123456789';
            				break;
            			case 'nozero'	:	$pool = '123456789';
            				break;
            		}

					$str = '';
                
                    for ($i=0; $i < $len; $i++) 
                    {    
                        $str .= substr($pool, mt_rand(0, strlen($pool) -1), 1); 
                    }
                    return $str;      
              break;
            case 'md5'		: return md5(uniqid(mt_rand(), TRUE)); 
              break; 
            case 'encrypt'	: return $this->hash(uniqid(mt_rand(), TRUE)); 
              break; 
        }        
    }
    /* END */
 
 
    
    /** ----------------------------------------
    /**  Form declaration
    /** ----------------------------------------*/
    
    // This function is used by modules when they need to create forms
    
    function form_declaration($data)
    {
        global $PREFS, $EXT, $REGX;
        
        $deft = array(
        				'hidden_fields'	=> array(),
        				'action'		=> '', 
        				'id'			=> '',
        				'secure'		=> TRUE,
        				'enctype' 		=> '',
        				'onsubmit'		=> '',
        			);
        
        
        foreach ($deft as $key => $val)
        {
        	if ( ! isset($data[$key]))
        	{
        		$data[$key] = $val;
        	}
        }
        
        if (is_array($data['hidden_fields']) && ! isset($data['hidden_fields']['site_id']))
        {
        	$data['hidden_fields']['site_id'] = $PREFS->ini('site_id');
        }
        
        
        // -------------------------------------------
		// 'form_declaration_modify_data' hook.
		//  - Modify the $data parameters before they are processed
		//
			if ($EXT->active_hook('form_declaration_modify_data') === TRUE)
			{
				$data = $EXT->call_extension('form_declaration_modify_data', $data);
			}
		//
		// -------------------------------------------
		
		// -------------------------------------------
		// 'form_declaration_return' hook.
		//  - Take control of the form_declaration function
		//
			if ($EXT->active_hook('form_declaration_return') === TRUE)
			{
				$form = $EXT->call_extension('form_declaration_return', $data);
				if ($EXT->end_script === TRUE) return $form;
			}
		//
		// -------------------------------------------
            
        if ($data['action'] == '')
        {
            $data['action'] = $this->fetch_site_index();
        }
        
        if ($data['onsubmit'] != '')
        {
            $data['onsubmit'] = 'onsubmit="'.trim($data['onsubmit']).'"';
        }
        
        $data['action'] = rtrim($data['action'], '?');
        
        $data['name']	= (isset($data['name']) && $data['name'] != '') ? "name='".$data['name']."' "	: '';
        $data['id']		= ($data['id'] != '') 							? "id='".$data['id']."' " 		: '';

		if ($data['enctype'] == 'multi' OR strtolower($data['enctype']) == 'multipart/form-data')
		{
			$data['enctype'] = 'enctype="multipart/form-data" ';
		}
        
        $form  = '<form '.$data['id'].$data['name'].'method="post" action="'.$data['action'].'" '.$data['onsubmit'].' '.$data['enctype'].">\n";
       
        if ($data['secure'] == TRUE)
        {
			if ($PREFS->ini('secure_forms') == 'y')
			{
				if ( ! isset($data['hidden_fields']['XID']))
				{
					$data['hidden_fields'] = array_merge(array('XID' => '{XID_HASH}'), $data['hidden_fields']);
				}
				elseif ($data['hidden_fields']['XID'] == '')
				{
					$data['hidden_fields']['XID']  = '{XID_HASH}';
				}
			}
		}
	
        if (is_array($data['hidden_fields']))
        {
			$form .= "<div class='hiddenFields'>\n";
			
			foreach ($data['hidden_fields'] as $key => $val)
			{
				$form .= '<input type="hidden" name="'.$key.'" value="'.$REGX->form_prep($val).'" />'."\n";
			}
			
			$form .= "</div>\n\n";
		}
		      
        return $form;
    }
    /* END */
    
    
    
    /** ----------------------------------------
    /**  Form backtrack
    /** ----------------------------------------*/
    
    // This function lets us return a user to a previously
    // visited page after submitting a form.  The page
    // is determined by the offset that the admin
    // places in each form
    
    function form_backtrack($offset = '')
    {
        global $SESS, $PREFS;        
        
		$ret = $this->fetch_site_index();
		
		if ($offset != '')
		{
            if (isset($SESS->tracker[$offset]))
            {
                if ($SESS->tracker[$offset] != 'index')
                {
                    return $this->remove_double_slashes($this->fetch_site_index().$SESS->tracker[$offset]);
                }
            }
		}
		
		if (isset($_POST['RET']))
		{
			if (substr($_POST['RET'], 0, 1) == '-')
			{
				$return = str_replace("-", "", $_POST['RET']);
				
				if (isset($SESS->tracker[$return]))
				{
					if ($SESS->tracker[$return] != 'index')
					{
						$ret = $this->fetch_site_index().$SESS->tracker[$return];
					}
				}
			}
			else
			{
				$_POST['RET'] = str_replace(SLASH, '/', $_POST['RET']);
				
				if (strpos($_POST['RET'], '/') !== FALSE)
				{
					if (stristr($_POST['RET'], 'http://') OR 
						stristr($_POST['RET'], 'https://') OR 
						stristr($_POST['RET'], 'www.'))
					{
						$ret = $_POST['RET'];
					}
					else
					{
						$ret = $this->create_url($_POST['RET']);
					}
				}
				else
				{
					$ret = $_POST['RET'];
				}
			}
		
			// We need to slug in the session ID if the admin is running
			// their site using sessions only.  Normally the $FNS->fetch_site_index()
			// function adds the session ID automatically, except in cases when the 
			// $_POST['RET'] variable is set. Since the login routine relies on the RET
			// info to know where to redirect back to we need to sandwich in the session ID.
		
			if ($PREFS->ini('user_session_type') != 'c')
			{                
				if ($SESS->userdata['session_id'] != '' && ! stristr($ret, $SESS->userdata['session_id']))
				{
					$url = $PREFS->ini('site_url', 1);
							
					if (USER_BLOG !== FALSE)
					{
						$url .= USER_BLOG.'/';
					}
					
					$url .= $PREFS->ini('site_index');
			
					if ($PREFS->ini('force_query_string') == 'y')
					{
						$url .= '?';
					}        
			
					$sess_id = "/S=".$SESS->userdata['session_id']."/";
	
					$ret = str_replace($url, $url.$sess_id, $ret);			
				}            
			}			
		} 
		
        return $this->remove_double_slashes($ret);
    }
    /* END */


    /** ----------------------------------------
    /**  eval() 
    /** ----------------------------------------*/
    
    // Evaluates a string as PHP
    
    function evaluate($str)
    {    
		return eval('?>'.$str.'<?php ');
		
		// ?><?php // BBEdit syntax coloring bug fix
    }
    /* END */


    /** ----------------------------------------
    /**  Encode email from template callback
    /** ----------------------------------------*/

	function encode_email($str)
	{
		$email = (is_array($str)) ? trim($str['1']) : trim($str);
		
		$title = '';
		$email = str_replace(array('"', "'"), '', $email);
		
		if ($p = strpos($email, "title="))
		{
			$title = substr($email, $p + 6);
			$email = trim(substr($email, 0, $p));
		}
	
		if ( ! class_exists('Typography'))
		{
			require PATH_CORE.'core.typography'.EXT;
		}
		
		return Typography::encode_email($email, $title, TRUE);
	}
	/* END */


    /** ----------------------------------------
    /**  Delete spam prevention hashes
    /** ----------------------------------------*/
     
    function clear_spam_hashes()
    {
        global $PREFS, $DB;
     
        if ($PREFS->ini('secure_forms') == 'y')
        {
			$DB->query("DELETE FROM exp_security_hashes WHERE date < UNIX_TIMESTAMP()-7200");
        }    
    }
    /* END */



    /** ----------------------------------------
    /**  Set Cookie
    /** ----------------------------------------*/
    
    function set_cookie($name = '', $value = '', $expire = '')
    {    
        global $PREFS;
		
		if ( ! is_numeric($expire))
		{
			$expire = time() - 86500;
		}
		else
		{
			if ($expire > 0)
			{
				$expire = time() + $expire;
			}
			else
			{
				$expire = 0;
			}
		}
                    
        $prefix = ( ! $PREFS->ini('cookie_prefix')) ? 'exp_' : $PREFS->ini('cookie_prefix').'_';
        $path   = ( ! $PREFS->ini('cookie_path'))   ? '/'    : $PREFS->ini('cookie_path');
        
        if (REQ == 'CP' && $PREFS->ini('multiple_sites_enabled') == 'y')
        {
        	$domain = $PREFS->cp_cookie_domain;
        }
        else
        {
			$domain = ( ! $PREFS->ini('cookie_domain')) ? '' : $PREFS->ini('cookie_domain');
		}
		
        $value = stripslashes($value);
                    
        setcookie($prefix.$name, $value, $expire, $path, $domain, 0);
    }
    /* END */



    /** ----------------------------------------
    /**  Character limiter
    /** ----------------------------------------*/

    function char_limiter($str, $num = 500)
    {
        if (strlen($str) < $num)
        {
            return $str;
        }
        
        $str = str_replace("\n", " ", $str);        
        
        $str = preg_replace("/\s+/", " ", $str);

		if (strlen($str) <= $num)
		{
			return $str;
		}
		$str = trim($str);
		
        $out = "";
		
        foreach (explode(" ", trim($str)) as $val)
        {
			$out .= $val;
			
			if (strlen($out) >= $num)
			{
				return (strlen($out) == strlen($str)) ? $out : $out.'&#8230;';
			}
			
			$out .= ' ';
        }
    }
    /* END */



    /** ----------------------------------------
    /**  Word limiter
    /** ----------------------------------------*/
    
    function word_limiter($str, $num = 100)
    {
        if (strlen($str) < $num) 
        {
            return $str;
        }
        
		// allows the split to work properly with multi-byte Unicode characters
		if (version_compare(phpversion(), '4.3.2', '>') === TRUE)
		{
			$word = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);	
		}
		else
		{
			$word = preg_split('/\s/', $str, -1, PREG_SPLIT_NO_EMPTY);
		}
        
		if (count($word) <= $num)
		{
			return $str;
		}
                
        $str = "";
                 
        for ($i = 0; $i < $num; $i++) 
        {
            $str .= $word[$i]." ";
        }

        return trim($str).'&#8230;'; 
    }
    /* END */
	

    /** ----------------------------------------
    /**  Fetch Email Template
    /** ----------------------------------------*/
	
	function fetch_email_template($name)
	{
		global $IN, $DB, $SESS, $PREFS;

		$query = $DB->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND template_name = '".$DB->escape_str($name)."'");

		// Unlikely that this is necessary but it's possible a bad template request could
		// happen if a user hasn't run the update script.
		if ($query->num_rows == 0)
		{
			return array('title' => '', 'data' => '');
		}

		if ($query->row['enable_template'] == 'y')
		{
			return array('title' => $query->row['data_title'], 'data' => $query->row['template_data']);
		}
		
        if ($SESS->userdata['language'] != '')
        {
            $user_lang = $SESS->userdata['language'];
        }
        else
        {
			if ($IN->GBL('language', 'COOKIE'))
			{
				$user_lang = $IN->GBL('language', 'COOKIE');
			}
			elseif ($PREFS->ini('deft_lang') != '')
			{
                $user_lang = $PREFS->ini('deft_lang');
            }
            else
            {
                $user_lang = 'english';
            }
        }
        
        $user_lang = $this->filename_security($user_lang);

		if ( function_exists($name))
		{
			$title = $name.'_title';
		
			return array('title' => $title(), 'data' => $name());
		}
		else
		{
			if ( ! @include(PATH_LANG.$user_lang.'/email_data'.EXT))
			{
				return array('title' => $query->row['data_title'], 'data' => $query->row['template_data']);
			}
			
			if (function_exists($name))
			{
				$title = $name.'_title';
		
				return array('title' => $title(), 'data' => $name());
			}
			else
			{
				return array('title' => $query->row['data_title'], 'data' => $query->row['template_data']);
			}
		}
	}
	/* END */
	

    /** -----------------------------------------
    /**  Create character encoding menu
    /** -----------------------------------------*/
        
    function encoding_menu($which, $name, $selected = '')
    {
        global $DSP;       

		$files = array('languages', 'charsets');
		
		if ( ! in_array($which, $files))
		{
			return FALSE;
		}
		
        
        $file = PATH.'lib/'.$which.EXT;    
			
		if ( ! file_exists($file)) 
		{
			return FALSE;
		}   

		include($file);
        
		$r = $DSP->input_select_header($name);
		
		foreach ($$which as $key => $val)
		{
			if ($which == 'languages')
			{
				$r .= $DSP->input_select_option($val, $key, ($selected == $val) ? 1 : '');
			}
			else
			{
				$r .= $DSP->input_select_option($val, $val, ($selected == $val) ? 1 : '');
			}
		}
		
		$r .= $DSP->input_select_footer();
		
		return $r;
	}
	/* END */



    /** -----------------------------
    /**  Create Directory Map
    /** -----------------------------*/

    function create_directory_map($source_dir, $top_level_only = FALSE)
    {
        if ( ! isset($filedata))
            $filedata = array();
        
        if ($fp = @opendir($source_dir))
        { 
            while (FALSE !== ($file = readdir($fp)))
            {
                if (@is_dir($source_dir.$file) && substr($file, 0, 1) != '.' AND $top_level_only == FALSE) 
                {       
                    $temp_array = array();
                     
                    $temp_array = $this->create_directory_map($source_dir.$file."/");   
                    
                    $filedata[$file] = $temp_array;
                }
                elseif (substr($file, 0, 1) != "." && $file != 'index.html')
                {
                    $filedata[] = $file;
                }
            }         
            return $filedata;        
        } 
    }
    /* END */

 
    /** -------------------------------------------
    /**  Create pull-down optios from dirctory map
    /** -------------------------------------------*/

    function render_map_as_select_options($zarray, $array_name = '') 
    {	
        foreach ($zarray as $key => $val)
        {
            if ( is_array($val))
            {
                if ($array_name != "")
                    $key = $array_name.'/'.$key;
            
                $this->render_map_as_select_options($val, $key);
            }		
            else
            {
                if ($array_name != "")
                {
                    $val = $array_name.'/'.$val;
				}
				
				if (substr($val, -4) == '.php')
				{
					if ($val != 'theme_master.php')
					{				   
						$this->template_map[] = $val;
					}
				}
			}
        }
    }
    /* END */


    /** -----------------------------------------
    /**  Fetch names of installed language packs
    /** -----------------------------------------*/
        
    function language_pack_names($default)
    {
        global $PREFS;
            
		$source_dir = PATH_LANG;

    	$dirs = array();

		if ($fp = @opendir($source_dir))
		{
			while (FALSE !== ($file = readdir($fp)))
			{
				if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
				{
					$dirs[] = $file;
				}
			}
			closedir($fp);
		}

		sort($dirs);
		
		$r  = "<div class='default'>";
		$r .= "<select name='deft_lang' class='select'>\n";

		foreach ($dirs as $dir)
		{
			$selected = ($dir == $default) ? " selected='selected'" : '';
			$r .= "<option value='{$dir}'{$selected}>".ucfirst($dir)."</option>\n";
		}

        $r .= "</select>";
        $r .= "</div>";

        return $r;
    }
    /* END */
    
    
    
    /** -----------------------------------------
    /**  Delete cache files
    /** -----------------------------------------*/
        
    function clear_caching($which, $sub_dir = '', $relationships=FALSE)
    {
        global $IN, $DB, $PREFS;
            
        $actions = array('page', 'tag', 'db', 'sql', 'relationships', 'all');
        
        if ( ! in_array($which, $actions))
            return;
		
		/* -------------------------------------
		/*  Disable Tag Caching
		/*  
		/*  All for you, Nevin!  Disables tag caching, which if used unwisely
		/*  on a high traffic site can lead to disastrous disk i/o
		/*  This setting allows quick thinking admins to temporarily disable
		/*  it without hacking or modifying folder permissions
		/*  
		/*  Hidden Configuration Variable
		/*  - disable_tag_caching => Disable tag caching? (y/n)
		/* -------------------------------------*/

		if ($which == 'tag' && $PREFS->ini('disable_tag_caching') == 'y')
		{
			return;
		}
		
        if ($sub_dir != '')
        {
            $sub_dir = '/'.md5($sub_dir).'/';
        }
                        
        switch ($which)
        {
            case 'page' : $this->delete_directory(PATH_CACHE.'page_cache'.$sub_dir);
                break;
            case 'db'   : $this->delete_directory(PATH_CACHE.'db_cache'.$sub_dir);
                break;
            case 'tag'  : $this->delete_directory(PATH_CACHE.'tag_cache'.$sub_dir);
                break;
            case 'sql'  : $this->delete_directory(PATH_CACHE.'sql_cache'.$sub_dir);
                break;
            case 'relationships' : $DB->query("UPDATE exp_relationships SET rel_data = '', reverse_rel_data = ''");
            	break;
            case 'all'  : 
						$this->delete_directory(PATH_CACHE.'page_cache'.$sub_dir);
						$this->delete_directory(PATH_CACHE.'db_cache'.$sub_dir);
						$this->delete_directory(PATH_CACHE.'sql_cache'.$sub_dir);

						if ($PREFS->ini('disable_tag_caching') != 'y')
						{
							$this->delete_directory(PATH_CACHE.'tag_cache'.$sub_dir);
						}
                          
						if ($relationships === TRUE)
						{
							$DB->query("UPDATE exp_relationships SET rel_data = '', reverse_rel_data = ''");
						}
                break;
        }            
    }
    /* END */
    
    
       
    /** -----------------------------------------
    /**  Delete Direcories
    /** -----------------------------------------*/

    function delete_directory($path, $del_root = FALSE)
    {
		$path = rtrim($path, '/');
		
		if ( ! is_dir($path))
		{
			return FALSE;
		}
		
		// let's try this the sane way first
		@exec("mv {$path} {$path}_delete", $out, $ret);
		
		if (isset($ret) && $ret == 0)
		{
			if ($del_root === FALSE)
			{
				@mkdir($path, 0777);
				
				if ($fp = @fopen($path.'/index.html', 'wb'))
				{
					fclose($fp);
				}				
			}

			@exec("rm -r -f {$path}_delete");
		}
		else
		{
		    if ( ! $current_dir = @opendir($path))
	        {
	        	return;
	        }

	        while($filename = @readdir($current_dir))
	        {        
				if ($filename != "." AND $filename != "..")
				{
					if (@is_dir($path.'/'.$filename))
					{
						if (substr($filename, 0, 1) != '.')
						{
	            	    	$this->delete_directory($path.'/'.$filename, TRUE);
	            	    }
					}
					else
					{
	              	  @unlink($path.'/'.$filename);
					}
				}
	        }

	        closedir($current_dir);

			if (substr($path, -6) == '_cache' && $fp = @fopen($path.'/index.html', 'wb'))
			{
				fclose($fp);			
			}

	        if ($del_root == TRUE)
	        {
	            @rmdir($path);
	        }	
		}
    }
    /* END */
 

    /** -----------------------------------------
    /**  Fetch allowed weblogs
    /** -----------------------------------------*/
    
    // This function fetches the ID numbers of the
    // weblogs assigned to the currently logged in user.

    function fetch_assigned_weblogs($all_sites = FALSE)
    {
        global $SESS, $DB, $PREFS;
    
        $allowed_blogs = array();
        
        // If the 'weblog_id' index is not zero, it means the
        // current user has been assigned a specifc blog
        
        if (isset($SESS->userdata['weblog_id']) AND $SESS->userdata['weblog_id'] != 0)
        {
            $allowed_blogs[] = $SESS->userdata['weblog_id'];
        }
        else
        {
        	if (REQ == 'CP' AND isset($SESS->userdata['assigned_weblogs']) && $all_sites === FALSE)
			{
				$allowed_blogs = array_keys($SESS->userdata['assigned_weblogs']);
			}
            elseif ($SESS->userdata['group_id'] == 1)
            {
            	if ($all_sites === TRUE)
            	{
					$query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE is_user_blog = 'n'");
				}
				else
				{
					$query = $DB->query("SELECT weblog_id FROM exp_weblogs WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND is_user_blog = 'n'");
				}
				
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
                	    $allowed_blogs[] = $row['weblog_id'];
            		}
            	}
            }
            else
            {
				if ($all_sites === TRUE)
            	{
            		$result = $DB->query("SELECT exp_weblog_member_groups.weblog_id FROM exp_weblog_member_groups 
										  WHERE exp_weblog_member_groups.group_id = '".$DB->escape_str($SESS->userdata['group_id'])."'");
				}
				else
				{
					$result = $DB->query("SELECT exp_weblogs.weblog_id FROM exp_weblogs, exp_weblog_member_groups 
										  WHERE exp_weblogs.weblog_id = exp_weblog_member_groups.weblog_id
										  AND exp_weblogs.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
										  AND exp_weblog_member_groups.group_id = '".$DB->escape_str($SESS->userdata['group_id'])."'");
				}
				
				if ($result->num_rows > 0)
				{
					foreach ($result->result as $row)
					{
						$allowed_blogs[] = $row['weblog_id'];
					}
				}
            }
        }
        
        return array_values($allowed_blogs);
    }
    /* END */

 
    /** -----------------------------------------
    /**  Fetch allowed template group
    /** -----------------------------------------*/
    
    // This function fetches the ID number of the
    // template assigned to the currently logged in user.

    function fetch_assigned_template_group()
    {
        global $SESS;
    
        $allowed_tg = 0;
                
        if ($SESS->userdata['tmpl_group_id'] != 0)
        {
            $allowed_tg = $SESS->userdata['tmpl_group_id'];
        }
        
        return $allowed_tg;
    }
    /* END */

 
    /** ----------------------------------------------
    /**  Log Search terms
    /** ----------------------------------------------*/
  
    function log_search_terms($terms = '', $type = 'site')
    {  
        global $IN, $SESS, $DB, $LOC, $PREFS, $REGX;
        
        if ($terms == '')
        	return;
        	
        if ($PREFS->ini('enable_search_log') == 'n')
        	return;        	

		$search_log = array(
								'id'			=> '',
								'member_id'		=> $SESS->userdata('member_id'),
								'screen_name'	=> $SESS->userdata('screen_name'),
								'ip_address'	=> $IN->IP,
								'search_date'	=> $LOC->now,
								'search_type'	=> $type,
								'search_terms'	=> $REGX->xml_convert($REGX->encode_ee_tags($REGX->xss_clean($terms), TRUE)),
								'site_id'		=> $PREFS->ini('site_id')
							);
								
		$DB->query($DB->insert_string('exp_search_log', $search_log));
		
		/** ----------------------------------
		/**  Prune Database
		/** ----------------------------------*/
		
		srand(time());
		if ((rand() % 100) < 5) 
		{ 
			$max = ( ! is_numeric($PREFS->ini('max_logged_searches'))) ? 500 : $PREFS->ini('max_logged_searches');
		
			$query = $DB->query("SELECT MAX(id) as search_id FROM exp_search_log WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
			
			if (isset($query->row['search_id']) && $query->row['search_id'] > $max)
			{
				$DB->query("DELETE FROM exp_search_log WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND id < ".($query->row['search_id']-$max)."");
			}
		}
		
	}
	/* END */
 
 
    /** ----------------------------------------------
    /**  Log Referrer data
    /** ----------------------------------------------*/
  
    function log_referrer()
    {  
        global $IN, $PREFS, $DB, $LOC, $REGX, $SESS;
        
        /** ----------------------------------------
		/**  Is the nation of the user banend?
		/** ----------------------------------------*/
        
		if ($SESS->nation_ban_check(FALSE) === FALSE)
			return;
        
        
        if ($PREFS->ini('log_referrers') == 'n' OR ! isset($_SERVER['HTTP_REFERER']))
        {
            return;
        }
        
        $site_url 	= $PREFS->ini('site_url');
        $ref 		= ( ! isset($_SERVER['HTTP_REFERER'])) ? '' : $REGX->xss_clean($REGX->_html_entity_decode($_SERVER['HTTP_REFERER']));
        $test_ref	= strtolower($ref); // Yes, a copy, not a reference
        $domain		= ( ! $PREFS->ini('cookie_domain')) ? '' : $PREFS->ini('cookie_domain');
        
        /** ---------------------------------------------
        /**  Throttling - Ten hits a minute is the limit
        /** ---------------------------------------------*/
        	
        $query = $DB->query("SELECT COUNT(*) AS count 
        					 FROM exp_referrers
        					 WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
        					 AND (ref_from = '".$DB->escape_str($ref)."' OR ref_ip = '{$IN->IP}')
        					 AND ref_date > '".($LOC->now-60)."'");
        					 
        if ($query->row['count'] > 10)
        {
        	return FALSE;
        }
        
        if (stristr($ref, '{') !== FALSE OR stristr($ref, '}') !== FALSE)
        {
        	return FALSE;
        }        
        
		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $ref))
		{
			if (substr($test_ref, 0, 7) == 'http://' AND substr($test_ref, 0, 11) != 'http://www.')
			{
				$test_ref = preg_replace("#^http://(.+?)#", "http://www.\\1", $test_ref);
			}
		}
		
		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $site_url))
		{
			if (substr($site_url, 0, 7) == 'http://' AND substr($site_url, 0, 11) != 'http://www.')
			{
				$site_url = preg_replace("#^http://(.+?)#", "http://www.\\1", $site_url);
			}
		}
				                
        if ($test_ref != '' 
        	&& ! stristr($test_ref, $site_url)
        	&& ($domain == '' || !stristr($test_ref,$domain))
        	&& ($IN->whitelisted == 'y' OR $IN->blacklisted == 'n'))
        {
        	
        	/** --------------------------------
        	/**  INSERT into database
        	/** --------------------------------*/
        	
			$ref_to = $REGX->xss_clean($this->fetch_current_uri());
			
			if (stristr($ref_to, '{') !== FALSE OR stristr($ref_to, '}') !== FALSE)
        	{
        		return FALSE;
        	}
			
			$insert_data = array (  'ref_id'  	=>  '',
									'ref_from' 	=> $ref,
									'ref_to'  	=> $ref_to,
									'user_blog'	=> (USER_BLOG === FALSE) ? '' : USER_BLOG,
									'ref_ip'   	=> $IN->IP,
									'ref_date'	=> $LOC->now,
									'ref_agent'	=> $IN->AGENT,
									'site_id'	=> $PREFS->ini('site_id')
									);
	
			$DB->query($DB->insert_string('exp_referrers', $insert_data));
			
			/** ----------------------------------
			/**  Prune Database
			/** ----------------------------------*/
			
			srand(time());
			if ((rand() % 100) < 5) 
			{        
				$max = ( ! is_numeric($PREFS->ini('max_referrers'))) ? 500 : $PREFS->ini('max_referrers');
		
				$query = $DB->query("SELECT MAX(ref_id) as ref_id FROM exp_referrers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
				
				if (isset($query->row['ref_id']) && $query->row['ref_id'] > $max)
				{
					$DB->query("DELETE FROM exp_referrers WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND ref_id < ".($query->row['ref_id']-$max)."");
				}
			}
        }
    }
	/* END */
    
        
    /** ----------------------------------------------
    /**  Fetch Action ID
    /** ----------------------------------------------*/
  
    function fetch_action_id($class, $method)
    {  
        global $DB;
        
        if ($class == '' || $method == '')
        {
            return FALSE;
        }
        
        $this->action_ids[ucfirst($class)][$method] = $method;
        
        return LD.'AID:'.ucfirst($class).':'.$method.RD;
    }
    /* END */
    
	
	/** ----------------------------------------------
    /**  Insert Action IDs
    /** ----------------------------------------------*/
  
    function insert_action_ids($str)
    {  
    	global $DB;
    	
    	if (count($this->action_ids) == 0) return $str;
    	
       	$sql = "SELECT action_id, class, method FROM exp_actions WHERE";
			
		foreach($this->action_ids as $key => $value)
		{
			foreach($value as $k => $v)
			{
				$sql .= " (class= '".$DB->escape_str($key)."' AND method = '".$DB->escape_str($v)."') OR";
			}
		}
		
		$query = $DB->query(substr($sql, 0, -3));
		
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$str = str_replace(LD.'AID:'.$row['class'].':'.$row['method'].RD, $row['action_id'], $str);
			}
		}
		
		return $str;
    }
    /* END */
    
    
    /** ----------------------------------------
    /**  Compile and cache relationship data
    /** ----------------------------------------*/
   
	// This is used when submitting new weblog entries or gallery posts.
	// It serializes the related entry data.  The reason it's in this 
	// file is becuase it gets called from the publish class and the
	// gallery class so we need it somewhere that is accessible to both.
	
	function compile_relationship($data, $parent_entry = TRUE, $reverse = FALSE)
	{
		global $DB;
						
		if ($data['type'] == 'blog' OR ($reverse === TRUE && $parent_entry === FALSE))
		{
			$sql = "SELECT t.entry_id, t.weblog_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.dst_enabled, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.allow_trackbacks, t.sticky, t.entry_date, t.year, t.month, t.day, t.entry_date, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.trackback_total, t.sent_trackbacks, t.recent_trackback_date, t.site_id as entry_site_id,
					w.blog_title, w.blog_name, w.blog_url, w.comment_url, w.tb_return_url, w.comment_moderate, w.weblog_html_formatting, w.weblog_allow_img_urls, w.weblog_auto_link_urls, w.enable_trackbacks, w.trackback_field, w.trackback_use_captcha, w.trackback_system_enabled, 
					m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
					md.*,
					wd.*
			FROM exp_weblog_titles		AS t
			LEFT JOIN exp_weblogs 		AS w  ON t.weblog_id = w.weblog_id 
			LEFT JOIN exp_weblog_data	AS wd ON t.entry_id = wd.entry_id 
			LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id 
			LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id 
			WHERE t.entry_id = '".(($reverse === TRUE && $parent_entry === FALSE) ? $data['parent_id'] : $data['child_id'])."'";
			
			$entry_query = $DB->query($sql);
	
			// Is there a category group associated with this blog?
			$query = $DB->query("SELECT cat_group FROM  exp_weblogs WHERE weblog_id = '".$entry_query->row['weblog_id']."'"); 
			$cat_group = (trim($query->row['cat_group']) == '') ? FALSE : $query->row['cat_group'];

			$this->cat_array = array();
			$cat_array = array();
	
			if ($cat_group !== FALSE)
			{
				$this->get_categories($cat_group, ($reverse === TRUE && $parent_entry === FALSE) ? $data['parent_id'] : $data['child_id']);
				$cat_array = $this->cat_array;
			}
			
			if ($parent_entry == TRUE)
			{
				$DB->query("INSERT INTO exp_relationships (rel_id, rel_parent_id, rel_child_id, rel_type, rel_data) 
							VALUES ('', '".$data['parent_id']."', '".$data['child_id']."', '".$data['type']."',
									'".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."')");
				return $DB->insert_id;
			}
			else
			{
				if ($reverse === TRUE)
				{
					$DB->query("UPDATE exp_relationships 
								SET reverse_rel_data = '".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."' 
								WHERE rel_type = '".$DB->escape_str($data['type'])."' AND rel_parent_id = '".$data['parent_id']."'");
				}
				else
				{
					$DB->query("UPDATE exp_relationships 
								SET rel_data = '".addslashes(serialize(array('query' => $entry_query, 'cats_fixed' => '1', 'categories' => $cat_array)))."' 
								WHERE rel_type = 'blog' AND rel_child_id = '".$data['child_id']."'");
				}
			}		
		}
		elseif ($data['type'] == 'gallery')
		{
			$sql = "SELECT e.*, 
					p.gallery_image_url, p.gallery_thumb_prefix, p.gallery_medium_prefix, p.gallery_text_formatting, p.gallery_auto_link_urls, p.gallery_cf_one_formatting, p.gallery_cf_one_auto_link, p.gallery_cf_two_formatting, p.gallery_cf_two_auto_link, p.gallery_cf_three_formatting, p.gallery_cf_three_auto_link, p.gallery_cf_four_formatting, p.gallery_cf_four_auto_link, p.gallery_cf_five_formatting, p.gallery_cf_five_auto_link, p.gallery_cf_six_formatting, p.gallery_cf_six_auto_link,					
					c.cat_folder, c.cat_name, c.cat_description,
					m.screen_name, m.username 
					FROM exp_gallery_entries			AS e
					LEFT JOIN exp_galleries				AS p ON p.gallery_id = e.gallery_id
					LEFT JOIN exp_gallery_categories	AS c ON c.cat_id = e.cat_id
					LEFT JOIN exp_members				AS m ON e.author_id = m.member_id
					WHERE e.entry_id = '".$data['child_id']."'";
					
			$sql = str_replace("\t", " ", $sql);
		
			$entry_query = $DB->query($sql);
	
			if ($parent_entry == TRUE)
			{
				$DB->query("INSERT INTO exp_relationships (rel_id, rel_parent_id, rel_child_id, rel_type, rel_data) VALUES ('', '".$data['parent_id']."', '".$data['child_id']."', '".$data['type']."', '".addslashes(serialize(array('query' => $entry_query)))."')");
				return $DB->insert_id;
			}
			else
			{
				$DB->query("UPDATE exp_relationships SET rel_data = '".addslashes(serialize(array('query' => $entry_query)))."' WHERE rel_type = 'gallery' AND rel_child_id = '".$data['child_id']."'");
			}
		}
	}
	/* END */
	
	/** --------------------------------
    /**  Get Categories for Weblog Entry/Entries
    /** --------------------------------*/
        
    function get_categories($cat_group, $entry_id)
    {        
		global $DB;
		
		// fetch the custom category fields
		$field_sqla = '';
		$field_sqlb = '';
		
		$query = $DB->query("SELECT field_id, field_name FROM exp_category_fields WHERE group_id IN ('".str_replace('|', "','", $DB->escape_str($cat_group))."')");
			
		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{
				$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
			}

			
			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id 
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}
		

		$sql = "SELECT	c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, p.cat_id, c.parent_id, c.cat_description, c.group_id 
				{$field_sqla}
				FROM		(exp_categories AS c, exp_category_posts AS p)
				{$field_sqlb}
				WHERE		c.group_id	IN ('".str_replace('|', "','", $DB->escape_str($cat_group))."')
				AND			p.entry_id	= '".$entry_id."'
				AND			c.cat_id 	= p.cat_id
				ORDER BY	c.parent_id, c.cat_order";
	
		$sql = str_replace("\t", " ", $sql);
		$query = $DB->query($sql);
		
		$this->cat_array = array();
		$parents = array();
				
		if ($query->num_rows > 0)
		{
			$this->temp_array = array();
			
			foreach ($query->result as $row)
			{    
				$this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);
						
				if ($field_sqla != '')
				{
					foreach ($row as $k => $v)
					{
						if (strpos($k, 'field') !== FALSE)
						{
							$this->temp_array[$row['cat_id']][$k] = $v;
						}
					}
				}

				if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) $parents[$row['parent_id']] = '';
				unset($parents[$row['cat_id']]);              
			}
				
			foreach($this->temp_array as $k => $v) 
			{			
				if (isset($parents[$v['1']])) $v['1'] = 0;
					
				if (0 == $v['1'])
				{    
					$this->cat_array[] = $v;
					$this->process_subcategories($k);
				}
			}
		
			unset($this->temp_array);
		}
    }
    /* END */



	/** --------------------------------
    /**  Process Subcategories
    /** --------------------------------*/
        
    function process_subcategories($parent_id)
    {        
    	foreach($this->temp_array as $key => $val) 
        {
            if ($parent_id == $val['1'])
            {
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
        }
    }
    /* END */
    
	/** -----------------------------------
	/**  Add security hashes to forms
	/** -----------------------------------*/

   function add_form_security_hash($str)
   {
   		global $PREFS, $IN, $DB;
   
		if ($PREFS->ini('secure_forms') == 'y')
		{
			if (preg_match_all("/({XID_HASH})/", $str, $matches))
			{
				$db_reset = FALSE;
				
				// Disable DB caching if it's currently set
				
				if ($DB->enable_cache == TRUE)
				{
					$DB->enable_cache = FALSE;
					$db_reset = TRUE;
				}
			
				// Add security hashes
				
				$sql = "INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES";
				
				foreach ($matches['1'] as $val)
				{
					$hash = $this->random('encrypt');
					$str = preg_replace("/{XID_HASH}/", $hash, $str, 1);
					$sql .= "(UNIX_TIMESTAMP(), '".$IN->IP."', '".$hash."'),";
				}
				
				$DB->query(substr($sql,0,-1));
				
				// Re-enable DB caching
				
				if ($db_reset == TRUE)
				{
					$DB->enable_cache = TRUE;                
				}
			}
		}
   
   		return $str;	
	}
   
	/** -----------------------------------
	/**  Remap pMachine Pro URLs
	/** -----------------------------------*/
	//  Since pM URLs are different than EE URLs,
	//  for those who have migrated from pM we will
	//  check the URL formatting.  If the request is
	//  for a pMachine URL, we'll remap it to the new EE location

	function remap_pm_urls()
	{
		global $DB, $IN, $PREFS;

		if ($PREFS->ini('remap_pm_urls') == 'y' AND $PREFS->ini('remap_pm_dest') !== FALSE AND $IN->URI != '')
		{
			$p_uri = ( ! isset($_GET['id'])) ? $IN->URI : '/'.$_GET['id'].'/';
			
			if (preg_match("#^/[0-9]{1,6}\_[0-9]{1,4}\_[0-9]{1,4}\_[0-9]{1,4}.*$#", $p_uri))
			{
				$pentry_id = substr($p_uri, 1, (strpos($p_uri, '_')-1));
			}
			elseif (preg_match("#^/P[0-9]{1,6}.*$#", $p_uri))
			{	
				$p_uri = str_replace("/", "", $p_uri);
				$pentry_id = substr($p_uri, 1);
			}
				
			if (isset($pentry_id) AND $pentry_id != '')
			{
				$query = $DB->query("SELECT url_title FROM exp_weblog_titles WHERE pentry_id = '".$DB->escape_str($pentry_id)."'");
				
				if ($query->num_rows == 1)
				{
					$this->redirect($PREFS->ini('remap_pm_dest', 1).$query->row['url_title'].'/');
					exit;
				}
			}
		}	    
	}
	/* END */
    
    
	/** -----------------------------------
	/**  Generate Captcha
	/** -----------------------------------*/

	function create_captcha($old_word = '')
	{
		global $DB, $IN, $PREFS, $SESS, $EXT;
		
		if ($PREFS->ini('captcha_require_members') == 'n' AND $SESS->userdata['member_id'] != 0)
		{
			return '';
		}
		
		// -------------------------------------------
        // 'create_captcha_start' hook.
		//  - Allows rewrite of how CAPTCHAs are created
		//
			if ($EXT->active_hook('create_captcha_start') === TRUE)
			{
				$edata = $EXT->call_extension('create_captcha_start', $old_word);
				if ($EXT->end_script === TRUE) return $edata;
			}	
		//
		// -------------------------------------------
		
			
		$img_path	= $PREFS->ini('captcha_path', 1, TRUE);
		$img_url	= $PREFS->ini('captcha_url', 1);
		$use_font	= ($PREFS->ini('captcha_font') == 'y') ? TRUE : FALSE;
				
		$font_face	= "texb.ttf";
		$font_size	= 16;
		
		$expiration = 60*60*2;  // 2 hours
		
		$img_width	= 140;	// Image width
		$img_height	= 30;	// Image height	
				
		if ($img_path == '' || $img_url == '')
		{
			return FALSE;
		}

		if ( ! @is_dir($img_path)) 
		{
			return FALSE;
		}
		
        if ( ! is_writable($img_path))
        {
			return FALSE;
		}
		
        if ( ! file_exists(PATH.'lib/words'.EXT))
        {
			return FALSE;
		}	
		
		if ( ! extension_loaded('gd'))
		{
			return FALSE;
		}
		
		if (substr($img_url, -1) != '/') $img_url .= '/';
		
		
		// Disable DB caching if it's currently set
		
		$db_reset = FALSE;
		if ($DB->enable_cache == TRUE)
		{
			$DB->enable_cache = FALSE;
			$db_reset = TRUE;
		}
		
		/** -----------------------------------
		/**  Remove old images
		/**  Add a bit of randomness so we aren't processing these
		/**  files on every single page load	
		/** -----------------------------------*/
		
		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);
		
		if ((mt_rand() % 100) < $SESS->gc_probability)
		{
			$old = time() - $expiration;
			$DB->query("DELETE FROM exp_captcha WHERE date < ".$old);

	        $current_dir = @opendir($img_path);

	        while($filename = @readdir($current_dir))
	        {        
				if ($filename != "." and $filename != ".." and $filename != "index.html")
	            {
	            	$name = str_replace(".jpg", "", $filename);

					if (($name + $expiration) < $now)
					{
						@unlink($img_path.$filename);
					}
	            }
	        }

	        closedir($current_dir);			
		}
	
		/** -----------------------------------
		/**  Fetch and insert word
		/** -----------------------------------*/
	
		if ($old_word == '')
		{
			require PATH.'lib/words'.EXT;
			$word = $words[array_rand($words)];
			
			if ($PREFS->ini('captcha_rand') == 'y')
			{
				$word .= $this->random('nozero', 2);
			}

			$DB->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".$IN->IP."', '".$DB->escape_str($word)."')");		
		}
		else
		{
			$word = $old_word;
		}
		
		$this->cached_captcha = $word;
		
 		/** -----------------------------------
		/**  Determine angle and position	
		/** -----------------------------------*/
		
		$length	= strlen($word);
		$angle	= ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
		$x_axis	= rand(6, (360/$length)-16);			
		$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);
		
		/** -----------------------------------
		/**  Create image
		/** -----------------------------------*/
				
		$im = ImageCreate($img_width, $img_height);
				
		/** -----------------------------------
		/**  Assign colors
		/** -----------------------------------*/
		
		$bg_color		= ImageColorAllocate($im, 255, 255, 255);
		$border_color	= ImageColorAllocate($im, 153, 102, 102);
		$text_color		= ImageColorAllocate($im, 204, 153, 153);
		$grid_color		= imagecolorallocate($im, 255, 182, 182);
		$shadow_color	= imagecolorallocate($im, 255, 240, 240);

		/** -----------------------------------
		/**  Create the rectangle
		/** -----------------------------------*/
		
		ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);
		
		/** -----------------------------------
		/**  Create the spiral pattern
		/** -----------------------------------*/
		
		$theta		= 1;
		$thetac		= 6;  
		$radius		= 12;  
		$circles	= 20;  
		$points		= 36;

		for ($i = 0; $i < ($circles * $points) - 1; $i++) 
		{
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points );
			$x = ($rad * cos($theta)) + $x_axis;
			$y = ($rad * sin($theta)) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos($theta)) + $x_axis;
			$y1 = ($rad1 * sin($theta )) + $y_axis;
			imageline($im, $x, $y, $x1, $y1, $grid_color);
			$theta = $theta - $thetac;
		}

		//imageline($im, $img_width, $img_height, 0, 0, $grid_color);
		
		/** -----------------------------------
		/**  Write the text
		/** -----------------------------------*/
		
		$font_path = PATH.'fonts/'.$font_face;
		
		if ($use_font == TRUE)
		{
			if ( ! file_exists($font_path))
			{
				$use_font = FALSE;
			}		
		}
				
		if ($use_font == FALSE OR ! function_exists('imagettftext'))
		{
			$font_size = 5;
			ImageString($im, $font_size, $x_axis, $img_height/3.8, $word, $text_color);
		}
		else
		{
			imagettftext($im, $font_size, $angle, $x_axis, $img_height/1.5, $text_color, $font_path, $word);
		}

		/** -----------------------------------
		/**  Create the border
		/** -----------------------------------*/

		imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $border_color);		

		/** -----------------------------------
		/**  Generate the image
		/** -----------------------------------*/
		
		$img_name = $now.'.jpg';

		ImageJPEG($im, $img_path.$img_name);
		
		$img = "<img src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" alt=\" \" />";
		
		ImageDestroy($im);
	
		// Re-enable DB caching
		
		if ($db_reset == TRUE)
		{
			$DB->enable_cache = TRUE;                
		}
		
		return $img;
	}
	/* END */
    


    /** ---------------------------------------------------------------    
    /**  SQL "AND" or "OR" string for conditional tag parameters
    /** ---------------------------------------------------------------*/

    // This function lets us build a specific type of query
    // needed when tags have conditional parameters:
    //
    // {exp:some_tag  param="value1|value2|value3"}
    //
    // Or the parameter can contain "not":
    //
    // {exp:some_tag  param="not value1|value2|value3"}
    //
    // This function explodes the pipes and constructs a series of AND
    // conditions or OR conditions
    
    // We should probably put this in the DB class but it's not
    // something that is typically used

    function sql_andor_string($str, $field, $prefix = '', $null=FALSE)
    {
    	global $DB;
    
        if ($str == "" || $field == "")
        {
            return '';
        }
            
        $sql = '';
        
        if ($prefix != '')
            $prefix .= '.';
    
        if (preg_match("/\|/", $str))
        {
            $ex = explode("|", $str);
        
            if (count($ex) > 0)
            {
				if (strncmp($ex[0], 'not ', 4) == 0)
                {
                    $ex['0'] = substr($ex['0'], 3);
                    
                    $parts = array();
                    
                    for ($i = 0; $i < count($ex); $i++)
                    {
                        $ex[$i] = trim($ex[$i]);
                        
                        if ($ex[$i] != "")
                        {
                            $parts[] = "'".$DB->escape_str($ex[$i])."'"; 
                        }   
                    }       
                    
                    if (count($parts) > 0)
                    {
                    	if ($null === TRUE)
                    	{
                    		$sql .= "AND ($prefix"."$field NOT IN (".implode(',', $parts).') OR '.$prefix.$field.' IS NULL)';
                    	}
                    	else
                    	{
                    		$sql .= "AND $prefix"."$field NOT IN (".implode(',', $parts).')';
                    	}
                    }
                }
                else
                {
                    $parts = array();
                
                    for ($i = 0; $i < count($ex); $i++)
                    {
                        $ex[$i] = trim($ex[$i]);
                        
                        if ($ex[$i] != "")
                        {
                        	$parts[] = "'".$DB->escape_str($ex[$i])."'";
                        }  
                    }

					if (substr($sql, -2) == 'OR')
					{
						$sql = substr($sql, 0, -2);
					}

                    if (count($parts) > 0)
                    {
                    	if ($null === TRUE)
                    	{
                    		$sql .= "AND (".$prefix.$field." IN (".implode(',', $parts).') OR '.$prefix.$field.' IS NULL)';	
                    	}
                    	else
                    	{
                    		$sql .= "AND ".$prefix.$field." IN (".implode(',', $parts).') ';	
                    	}
                    }
                }             
            }
        }
        else
        {
        	if (strncmp($str, 'not ', 4) == 0)
            {
                $str = trim(substr($str, 3));
                
				if ($null === TRUE)
				{
					$sql .= "AND (".$prefix.$field." != '".$DB->escape_str($str)."' OR ".$prefix.$field." IS NULL)";
				}
				else
				{
					$sql .= "AND ".$prefix.$field." != '".$DB->escape_str($str)."'";
				}
            }
            else
            {
            	if ($null === TRUE)
            	{
            		$sql .= "AND (".$prefix.$field." = '".$DB->escape_str($str)."' OR ".$prefix.$field." IS NULL)";
            	}
            	else
            	{
        			$sql .= "AND ".$prefix.$field." = '".$DB->escape_str($str)."'";
            	}
            }
        }

        return $sql;        
    }
    /* END */
    
    

	/** ---------------------------------------
	/**  Assign Conditional Variables
	/** ---------------------------------------*/

	function assign_conditional_variables($str, $slash = '/', $LD = '{', $RD = '}')
	{        
        // The first half of this function simply gathers the openging "if" tags
        // and a numeric value that corresponds to the depth of nesting.
    	// The second half parses out the chunks
		
		$conds 		= array();
		$var_cond	= array();
		
		$modified_str = $str; // Not an alias!
		
		// Find the conditionals.
		// Added a \s in there to make sure it does not match {if:elseif} or {if:else} would would give 
		// us a bad array and cause havoc.
		if ( ! preg_match_all("/".$LD."if(\s.*?)".$RD."/s", $modified_str, $eek))
		{
			return $var_cond;
		}
		
		$total_conditionals = count($eek['0']);
		
		// Mark all opening conditionals, sequentially.
		if (count($modified_str) > 0)
		{
			 for ($i = 0; $i < $total_conditionals; $i++)
			 {
				  // Embedded variable fix
				  if ($ld_location = strpos($eek['1'][$i],$LD))
				  {
					   if (preg_match_all("|".preg_quote($eek['0'][$i])."(.*?)".$RD."|s", $modified_str, $fix_eek))
					   {
						   if (count($fix_eek) > 0)
						   {
								$eek['0'][$i] = $fix_eek['0']['0'];
								$eek['1'][$i] .= $RD.$fix_eek['1']['0'];
						   }
						}
				  }
			 
				  $modified_string_length = strlen($eek['1'][$i]);
				  $replace_value[$i] = $LD.'if'.$i;
				  $p1 = strpos($modified_str,$eek['0'][$i]);
				  $p2 = $p1+strlen($replace_value[$i].$eek['1'][$i])-strlen($i);
				  $p3 = strlen($modified_str);
				  $modified_str = substr($modified_str,0,$p1).$replace_value[$i].$eek['1'][$i].substr($modified_str,$p2, $p3);
			 }
		}
		
		// Mark all closing conditions.
		$closed_position = array();
		for ($t=$i-1; $t >= 0; $t--)
		{
			 // Find the conditional's start
			 $coordinate = strpos($modified_str, $LD.'if'.$t);
			 
			 // Find the shortned string.
			 $shortened = substr($modified_str, $coordinate);
			 
			 // Find the conditional's end. Should be first closing tag.
			 $closed_position = strpos($shortened,$LD.$slash.'if'.$RD);
			 
			 // Location of the next closing tag in main content var
			 $p1 = $coordinate + $closed_position;
			 $p2 = $p1 + strlen($LD.$slash.'if'.$t.$RD) - 1;
			 
			 $modified_str = substr($modified_str,0,$p1).$LD.$slash.'if'.$t.$RD.substr($modified_str,$p2);
		}
		
		// Create Rick's array
		for ($i = 0; $i < $total_conditionals; $i++)
		{
			$p1 = strpos($modified_str, $LD.'if'.$i.' ');
			$p2 = strpos($modified_str, $LD.$slash.'if'.$i.$RD);
			$length = $p2-$p1;
			$text_range = substr($modified_str,$p1,$length);
			
			// We use \d here because we want to look for one of the 'marked' conditionals, but 
			// not an Advanced Conditional, which would have a colon
			if (preg_match_all("/".$LD."if(\d.*?)".$RD."/", $text_range, $depth_check))
			{
				// Depth is minus one, since it counts itself
				$conds[] = array($LD.'if'.$eek['1'][$i].$RD, count($depth_check['0']));	
			}
		}

		// Create detailed conditional array

		$float = $str;
        $CE = $LD.$slash.'if'.$RD;
		$offset = strlen($CE);
		$start = 1;
		$duplicates = array();
				
		foreach ($conds as $key => $val)
		{	
			if ($val['1'] > $start) $start = $val['1'];
			
			$open_tag = strpos($float, $val['0']);
						
			$float = substr($float, $open_tag);
			
			$temp = $float;
			$len  = 0;
			$duplicates = array();
			
			$i = 1;
		
			while (FALSE !== ($in_point = strpos($temp, $CE)))
			{		
				$temp = substr($temp, $in_point + $offset);
				
				$len += $in_point + $offset;
							
				if ($i === $val['1'])
				{					
					$tag = str_replace($LD, '', $val['0']);
					$tag = str_replace($RD, '', $tag);
					
					$outer = substr($float, 0, $len);
					
					if (isset($duplicates[$val['1']]) && in_array($outer, $duplicates[$val['1']]))
					{
						break;
					}
					
					$duplicates[$val['1']][] = $outer;
				
					$inner = substr($outer, strlen($val['0']), -$offset);
					
		            $tag = str_replace("|", "\|", $tag);
		            
					$tagb = preg_replace("/^if/", "", $tag);

					$field = ( ! preg_match("#(\S+?)\s*(\!=|==|<|>|<=|>=|<>)#s", $tag, $match)) ? trim($tagb) : $match['1'];  
					
					// Array prototype:
					// offset 0: the full opening tag sans delimiters:  if extended
					// offset 1: the complete conditional chunk
					// offset 2: the inner conditional chunk
					// offset 3: the field name
				
					$var_cond[$val['1']][] = array($tag, $outer, $inner, $field);
					
					$float = substr($float, strlen($val['0']));
				
					break;
				}
			
				$i++;
			}
		}
		
		/** -------------------------
		/**  Parse Order
		/** -------------------------*/
		
		$final_conds = array();
		
		for ($i=$start; $i > 0; --$i)
		{
			if (isset($var_cond[$i])) $final_conds = array_merge($final_conds, $var_cond[$i]);
		}
		
		return $final_conds;
	}
	/* END */


    

    /** ---------------------------------------
    /**  Assign Tag Variables
    /** ---------------------------------------*/
    /*
        This function extracts the variables contained within the current tag 
        being parsed and assigns them to one of three arrays.
        
        There are three types of variables:
        
        Simple variables: {some_variable}
    
        Paired variables: {variable} stuff... {/variable}

        Contidionals: {if something != 'val'} stuff... {if something}
        
        Each of the three variables is parsed slightly different and appears in its own array
        
    */
            
    function assign_variables($str = '', $slash = SLASH)
    {
    	global $FNS;
    	
    	$return['var_single']	= array();
    	$return['var_pair']		= array();

        if ($str == '')
        {
            return $return;
        }   
            
            
        // No variables?  No reason to continue...
    
        if ( ! preg_match_all("/".LD."(.+?)".RD."/", $str, $matches))
        {
            return $return;
        }
        
        $temp_close = array();
        $temp_misc  = array();
        $slash_length = strlen($slash);
                
        foreach($matches['1'] as $key => $val)
        {
            if (substr($val, 0, 3) != 'if ' && 
            	substr($val, 0, 3) != 'if:' &&
            	substr($val, 0, $slash_length+2) != $slash."if")
            {
                if (stristr($val, '{'))
                {
                    if (preg_match("/(.+?)".LD."(.*)/s", $val, $matches2))
                    {
                    	$temp_misc[] = $matches2['2'];
                    }
                }
                elseif (substr($val, 0, $slash_length) == $slash)
                {
                    $temp_close[] = str_replace($slash, '', $val);
                }
                else
                {
                    $temp_misc[] = $val;
                }
            }
            elseif (stristr($val, '{')) // Variable in conditional.  ::sigh::
            {
            	$full_conditional = substr($this->full_tag($matches['0'][$key], $str), 1, -1);
            	
            	if (preg_match_all("/".LD."(.*?)".RD."/s", $full_conditional, $cond_vars))
				{
					$temp_misc = array_merge($temp_misc, $cond_vars['1']);
				}
            }
        }
        
        $temp_pair = array();
        
        foreach($temp_misc as $item)
        {
            foreach($temp_close as $row)
            {
            	if (substr($item, 0, strlen($row)) == $row)
                {
                    $temp_pair[] = $item;
                }
            }
        }
        
        $temp_single = array_unique(array_diff($temp_misc, $temp_pair));
        $temp_pair   = array_unique($temp_pair);
        
		/** ---------------------------------------
		/**  Assign Single Variables
		/** ---------------------------------------*/
		
		$var_single = array();
                        
        foreach($temp_single as $val)
        {  
            // simple conditionals
            
            if (stristr($val, '\|') && substr($val, 0, 6) != 'switch' && substr($val, 0, 11) != 'multi_field')
        	{
                $var_single[$val] = $this->fetch_simple_conditions($val);
            }
            
            // date variables
            
            elseif (preg_match("/.+?\s+?format/", $val))
            {
                $var_single[$val] = $this->fetch_date_variables($val);  
            }
            
            // single variables
            
            else
            {
                $var_single[$val] = $val;  
            }
        }

		/** ---------------------------------------
		/**  Assign Variable Pairs
		/** ---------------------------------------*/
		
		$var_pair = array();
            
        foreach($temp_pair as $val)
        {
            $var_pair[$val] = $this->assign_parameters($val);       
        }
        
        
        $return['var_single']	= $var_single;
        $return['var_pair']		= $var_pair;
        
        return $return;
    }
    /* END */
    
    
	/** -------------------------------------
    /**  Find the Full Opening Tag
    /** -------------------------------------*/

    function full_tag($str, $chunk='', $open='', $close='')
    {
    	global $TMPL;
    	
    	if ($chunk == '') $chunk = (is_object($TMPL)) ? $TMPL->fl_tmpl : '';
    	if ($open == '')  $open  = LD;
    	if ($close == '') $close = RD;
		
		// Warning: preg_match() Compilation failed: regular expression is too large at offset #
		// This error will occur if someone tries to stick over 30k-ish strings as tag parameters that also happen to include curley brackets.
		// Instead of preventing the error, we let it take place, so the user will hopefully visit the forums seeking assistance
		if ( ! preg_match("/".preg_quote($str, '/')."(.*?)".$close."/s", $chunk, $matches))
    	{
    		return $str;
    	}
    	
		if (isset($matches['1']) && $matches['1'] != '' && stristr($matches['1'], $open) !== false)
		{
			$matches['0'] = $this->full_tag($matches['0'], $chunk, $open, $close);
		}
		
		return $matches['0'];
	}
	/* END */




    /** ---------------------------------------
    /**  Fetch simple conditionals
    /** ---------------------------------------*/

    function fetch_simple_conditions($str)
    {
        if ($str == '')
            return;
            
        $str = trim($str, '|');
            
        $str = str_replace(' ', '', trim($str));        
        
        return explode('|', $str);
    }
    /* END */



    /** ---------------------------------------
    /**  Fetch date variables
    /** ---------------------------------------*/
    //
    // This function looks for a variable that has this prototype:
    //
    // {date format="%Y %m %d"}
    //
    // If found, returns only the datecodes: %Y %m %d

    function fetch_date_variables($datestr)
    {
        if ($datestr == '')
            return;
        
        if ( ! preg_match("/format\s*=\s*[\'|\"](.*?)[\'|\"]/s", $datestr, $match))
               return FALSE;
        
        return $match['1'];
    }
    /* END */




    /** -------------------------------------
    /**  Return parameters as an array
    /** -------------------------------------*/
    
    //  Creates an associative array from a string
    //  of parameters: sort="asc" limit="2" etc.
    
    function assign_parameters($str)
    {
        if ($str == "")
            return FALSE;
                        
		// \047 - Single quote octal
		// \042 - Double quote octal
		
		// I don't know for sure, but I suspect using octals is more reliable than ASCII.
		// I ran into a situation where a quote wasn't being matched until I switched to octal.
		// I have no idea why, so just to be safe I used them here. - Rick
		
		/* ---------------------------------------
		/* matches['0'] => attribute and value
		/* matches['1'] => attribute name
		/* matches['2'] => single or double quote
		/* matches['3'] => attribute value
		/* ---------------------------------------*/
		
		preg_match_all("/(\S+?)\s*=\s*(\042|\047)([^\\2]*?)\\2/is",  $str, $matches, PREG_SET_ORDER);

		if (count($matches) > 0)
		{
			$result = array();
		
			foreach($matches as $match)
			{
				$result[$match['1']] = (trim($match['3']) == '') ? $match['3'] : trim($match['3']);
			}

			return $result;
		}

        return FALSE;
    }
    /* END */

    /** ---------------------------------------
    /**  Prep conditional
    /** ---------------------------------------*/
    
    // This function lets us do a little prepping before
    // running any conditionals through eval()

	function prep_conditional($cond = '')
	{
		$cond = preg_replace("/^if/", "", $cond);
		
		if (preg_match("/(\S+)\s*(\!=|==|<=|>=|<>|<|>)\s*(.+)/", $cond, $match))
		{
			$cond = trim($match['1']).' '.trim($match['2']).' '.trim($match['3']);
		}
			
		$rcond	= substr($cond, strpos($cond, ' '));
		$cond	= str_replace($rcond, str_replace(SLASH, '/', $rcond), $cond);
			
		// Since we allow the following shorthand condition: {if username}
		// but it's not legal PHP, we'll correct it by adding:  != ''
		
		if ( ! preg_match("/(\!=|==|<|>|<=|>=|<>)/", $cond))
		{
			$cond .= ' != "" ';
		}                
	
		return trim($cond);
	}
	/* END */
	


	/** ---------------------------------------
    /**  Prep conditionals
    /** ---------------------------------------*/
    
    function reverse_key_sort($a, $b) {return strlen($b) > strlen($a);}

	function prep_conditionals($str, $vars, $safety='n', $prefix='')
	{				
		global $REGX;
		
		if (count($vars) == 0) return $str;

		$switch  = array();
		$protect = array();
		$prep_id = $this->random('alpha', 3);
		$embedded_tags = (stristr($str, LD.'exp:')) ? TRUE : FALSE;
		
		// Temp bug fix for the 1.x branch
		
		if (isset($vars['logged_in']) && ! isset($vars['logged_in_member_id']))
		{
			$md5_key = (string) hexdec($prep_id.md5('logged_in_member_id'));
			$protect['logged_in_member_id'] = $md5_key;
			$switch[$md5_key] = 'logged_in_member_id';
		}
		
		$valid = array('!=','==','<=','>=','<','>','<>',
					   'AND', 'XOR', 'OR','&&','||',
					   ')','(',
					   'TRUE', 'FALSE');
		
		$str = str_replace(LD.'if:else'.RD, 'c831adif9wel5ed9e', $str);
		
		// The ((else)*if) is actually faster than (elseif|if) in PHP 5.0.4, 
		// but only by a half a thousandth of a second.  However, why not be
		// as efficient as possible?  It also gives me a chance to catch some
		// user error mistakes.

		if (preg_match_all("/".preg_quote(LD)."((if:else)*if)\s+(.*?)".preg_quote(RD)."/", $str, $matches))
		{
			// PROTECT QUOTED TEXT
			//  That which is in quotes should be protected and ignored as it will screw
			//  up the parsing if the variable is found within a string
			
			if (preg_match_all('/([\"\'])([^\\1]*?)\\1/s', implode(' ', $matches['3']), $quote_matches))
			{
				foreach($quote_matches['0'] as $quote_match)
				{
					$md5_key = (string) hexdec($prep_id.md5($quote_match));
					$protect[$quote_match] = $md5_key;
					$switch[$md5_key] = $quote_match;
				}
				
				$matches['3'] = str_replace(array_keys($protect), array_values($protect), $matches['3']);
				
				// Remove quoted values altogether to find variables...
				$matches['t'] = str_replace($valid, ' ', str_replace(array_values($protect), '', $matches['3']));
			}
			else
			{
				$matches['t'] = str_replace($valid, ' ', $matches['3']);
			}
			
			// FIND WHAT WE NEED, NOTHING MORE!
			// On reedmaniac.com with no caching this code below knocked off, 
			// on average, about .07 seconds on a .34 page load. Not too shabby.
			// Sadly, its influence is far less on a cached page.  Ah well...
			
			$data		= array();

			foreach($matches['t'] as $cond)
			{
				if (trim($cond) == '') continue;
				
				$x = preg_split("/\s+/", trim($cond)); $i=0;
				
				do
				{
					if (array_key_exists($x[$i], $vars))
					{
						$data[$x[$i]] = $vars[$x[$i]];
					}
					elseif($embedded_tags === TRUE && ! is_numeric($x[$i]))
					{
						$data[$x[$i]] = $x[$i];
					}
					elseif(strncmp($x[$i], 'embed:', 6) == 0)
					{
						$data[$x[$i]] = '';
					}
					
					if ($i > 500) break; ++$i;
				}	
				while(isset($x[$i]));
			}

			// Reverse Key Length Sorting
			// This should prevent, for example, the variable 'comment' from 
			// overwriting the variable 'comments'.  I tried using create_function()
			// here but it was significantly slower than calling an already existing
			// method in the class.  Not sure why.
			
			uksort($data, array($this, 'reverse_key_sort'));

			if ($safety == 'y')
			{
				// Make sure we have the same amount of opening conditional tags 
				// as closing conditional tags.
				$tstr = preg_replace("/<script.*?>.*?<\/script>/is", '', $str);
				
				$opening = substr_count($tstr, LD.'if') - substr_count($tstr, LD.'if:elseif');
				$closing = substr_count($tstr, LD.'/if'.RD);
				
				if ($opening > $closing)
				{
					$str .= str_repeat(LD.'/if'.RD, $opening-$closing);
				}
			}
		
			// Prep the data array to remove characters we do not want
			// And also just add the quotes around the value for good measure.
			
			while (list($key) = each($data))
			{
				if ( is_array($data[$key])) continue;
			
				// TRUE AND FALSE values are for short hand conditionals,
				// like {if logged_in} and so we have no need to remove
				// unwanted characters and we do not quote it.
				
				if ($data[$key] != 'TRUE' && $data[$key] != 'FALSE' && ($key != $data[$key] OR $embedded_tags !== TRUE))
				{
					if (stristr($data[$key], '<script'))
					{
						$data[$key] = preg_replace("/<script.*?>.*?<\/script>/is", '', $data[$key]);
					}
					
					$data[$key] = '"'.
								  str_replace(array("'", '"', '(', ')', '$', '{', '}', "\n", "\r", '\\'), 
											  array('&#39;', '&#34;', '&#40;', '&#41;', '&#36;', '', '', '', '', '&#92;'), 
											  (strlen($data[$key]) > 100) ? substr(htmlspecialchars($data[$key]), 0, 100) : $data[$key]
											  ).
								  '"';
				}
				
				$md5_key = (string) hexdec($prep_id.md5($key));
				$protect[$key] = $md5_key;
				$switch[$md5_key] = $data[$key];
				
				if ($prefix != '')
				{
					$md5_key = (string) hexdec($prep_id.md5($prefix.$key));
					$protect[$prefix.$key] = $md5_key;
					$switch[$md5_key] = $data[$key];
				}
			}

			$matches['3'] = str_replace(array_keys($protect), array_values($protect), $matches['3']);
			
			if ($safety == 'y')
			{
				$matches['s'] = str_replace($protect, '^', $matches['3']);
				$matches['s'] = preg_replace('/"(.*?)"/s', '^', $matches['s']);
				$matches['s'] = preg_replace("/'(.*?)'/s", '^', $matches['s']);
				$matches['s'] = str_replace($valid, '  ', $matches['s']);
				$matches['s'] = preg_replace("/(^|\s+)[0-9]+(\s|$)/", ' ', $matches['s']); // Remove unquoted numbers
				$done = array();
			}
			
			for($i=0, $s = count($matches['0']); $i < $s; ++$i)
			{	
				if ($safety == 'y' && ! in_array($matches['0'][$i], $done))
				{
					$done[] = $matches['0'][$i];
					
					// -------------------------------------
					//  Make sure someone did put in an {if:else conditional}
					//  when they likely meant to have an {if:elseif conditional}
					// -------------------------------------
					
					if ($matches['2'][$i] == '' && 
						substr($matches['3'][$i], 0, 5) == ':else' && 
						$matches['1'][$i] == 'if')
					{
						$matches['3'][$i] = substr($matches['3'][$i], 5);
						$matches['2'][$i] == 'elseif';
						
						trigger_error('Invalid Conditional, Assumed ElseIf : '.str_replace(' :else', 
																						   ':else', 
																						   $matches['0'][$i]), 
									  E_USER_WARNING);
					}
				
					// -------------------------------------
					//  If there are parentheses, then we
					//  try to make sure they match up correctly.
					// -------------------------------------
					
					$left  = substr_count($matches['3'][$i], '(');
					$right = substr_count($matches['3'][$i], ')');
					
					if ($left > $right)
					{
						$matches['3'][$i] .= str_repeat(')', $left-$right);
					}
					elseif ($right > $left)
					{
						$matches['3'][$i] = str_repeat('(', $right-$left).$matches['3'][$i];
					}
					
					/** -------------------------------------
					/**  Check for unparsed variables
					/** -------------------------------------*/
					
					if (trim($matches['s'][$i]) != '' && trim($matches['s'][$i]) != '^')
					{
						$x = preg_split("/\s+/", trim($matches['s'][$i]));
					
						for($j=0, $sj=count($x); $j < $sj; ++$j)
						{
							if ($x[$j] == '^') continue;
													
							if (substr($x[$j], 0, 1) != '^')
							{
								// We have an unset variable in the conditional.  
								// Set the unparsed variable to FALSE
								$matches['3'][$i] = str_replace($x[$j], 'FALSE', $matches['3'][$i]);
								
								if ($this->conditional_debug === TRUE)
								{
									trigger_error('Unset EE Conditional Variable ('.$x[$j].') : '.$matches['0'][$i], 
												  E_USER_WARNING);
								}
							}
							else
							{	
								// There is a partial variable match being done
								// because they are doing something like segment_11
								// when there is no such variable but there is a segment_1
								// echo  $x[$j]."\n<br />\n";
								
								trigger_error('Invalid EE Conditional Variable: '.
											  $matches['0'][$i], 
											  E_USER_WARNING);
								
								// Set entire conditional to FALSE since it fails
								$matches['3'][$i] = 'FALSE';
							}
						}
					}
				}
				
				$matches['3'][$i] = LD.$matches['1'][$i].' '.trim($matches['3'][$i]).RD;
			}
			
			$str = str_replace($matches['0'], $matches['3'], $str);

			$str = str_replace(array_keys($switch), array_values($switch), $str);
		}
		
		unset($data);
		unset($switch);
		unset($matches);
		unset($protect);
		
		$str = str_replace('c831adif9wel5ed9e',LD.'if:else'.RD, $str);
		
		return $str;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  File name security
	/** -------------------------------------*/
	
	function filename_security($str)
	{
		$bad = array(
						"../",
						"./",
						"<!--",
						"-->",
						"<",
						">",
						"'",
						'"',
						'&',
						'$',
						'#',
						'{',
						'}',
						'[',
						']',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c", 	// <
						"%3e", 		// >
						"%0e", 		// >
						"%28", 		// (  
						"%29", 		// ) 
						"%2528", 	// (
						"%26", 		// &
						"%24", 		// $
						"%3f", 		// ?
						"%3b", 		// ;
						"%3d"		// =
        			);
        			

		$str =  stripslashes(str_replace($bad, '', $str));
		
		return $str;
	}
	/* END */

	
	/** ----------------------------------------
    /**  Fetch file upload paths
    /** ----------------------------------------*/

    function fetch_file_paths()
    {
        global $DB;
        
        if (count($this->file_paths) > 0)
        {
        	return $this->file_paths;
        }
        
        $query = $DB->query("SELECT id, url FROM exp_upload_prefs");
        
        if ($query->num_rows == 0)
        {
            return;
        }
                
        foreach ($query->result as $row)
        {            
            $this->file_paths[$row['id']] = $row['url'];
        }
        
        return $this->file_paths;
    }
    /* END */
    
	/** ----------------------------------------
    /**  Clones an Object
    /**  - This is required because of the way PHP 5 handles the passing of objects
    /**  - http://acko.net/node/54
    /** ----------------------------------------*/
    
	function clone_object($object)
	{ 
		return version_compare(phpversion(), '5.0') < 0 ? $object : clone($object); 
	}
	/* END */
	
}
// END CLASS
?>