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
 File: core.language.php
-----------------------------------------------------
 Purpose: This class manages language files.
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Language {
    
    var $language   = array();
    var $cur_used   = array();
    
    
    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Language()
    {
    }
    /* END */
    
    
    /** -------------------------------------
    /**  Fetch a language file
    /** -------------------------------------*/

    function fetch_language_file($which = '', $package = '')
    {
        global $IN, $OUT, $LANG, $SESS, $PREFS, $FNS;
        
        if ($which == '')
        {
            return;
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
        
        // Sec.ur.ity code.  ::sigh::
		$which = str_replace(array('lang.', EXT), '', $which);
		$package = ($package == '') ? $FNS->filename_security($which) : $FNS->filename_security($package);
		$user_lang = $FNS->filename_security($user_lang);
        
        if ($which == 'sites_cp')
        {
			$phrase = 'base'.'6'.'4_d'.'ecode';
        	eval($phrase(preg_replace("|\s+|is", '', "Z2xvYmFsICREQiwgJERTUDsgJEVFX1NpdGVzID0gbmV3IEVFX1NpdGVzKCk7CiRzdHJpbmcgPSBiYXNlNjRfZGVjb2RlKCRFRV9TaXRlcy0+dGh
			lX3NpdGVzX2FsbG93ZWQuJEVFX1NpdGVzLT5udW1fc2l0ZXNfYWxsb3dlZC4kRUVfU2l0ZXMtPnNpdGVzX2FsbG93ZWRfbnVtKTsKJGhhc2ggPSBtZDUoIk1TTSBCeSBFbGxpc0xhYiIpOwoJCmZvciAo
			JGkgPSAwLCAkc3RyID0gIiI7ICRpIDwgc3RybGVuKCRzdHJpbmcpOyAkaSsrKQp7Cgkkc3RyIC49IHN1YnN0cigkc3RyaW5nLCAkaSwgMSkgXiBzdWJzdHIoJGhhc2gsICgkaSAlIHN0cmxlbigkaGFza
			CkpLCAxKTsKfQoKJHN0cmluZyA9ICRzdHI7Cgpmb3IgKCRpID0gMCwgJGRlYyA9ICIiOyAkaSA8IHN0cmxlbigkc3RyaW5nKTsgJGkrKykKewoJJGRlYyAuPSAoc3Vic3RyKCRzdHJpbmcsICRpKyssID
			EpIF4gc3Vic3RyKCRzdHJpbmcsICRpLCAxKSk7Cn0KCiRhbGxvd2VkID0gc3Vic3RyKGJhc2U2NF9kZWNvZGUoc3Vic3RyKGJhc2U2NF9kZWNvZGUoc3Vic3RyKGJhc2U2NF9kZWNvZGUoc3Vic3RyKCR
			kZWMsMikpLDUpKSw0KSksMik7CgokcXVlcnkgPSAkREItPnF1ZXJ5KCJTRUxFQ1QgQ09VTlQoKikgQVMgY291bnQgRlJPTSBleHBfc2l0ZXMiKTsKCmlmICggISBpc19udW1lcmljKCRhbGxvd2VkKSBP
			UiAkcXVlcnktPnJvd1siY291bnQiXSA+PSAkYWxsb3dlZCkKewoJJHRoaXMtPmxhbmd1YWdlWyJjcmVhdGVfbmV3X3NpdGUiXSA9ICIiOwoJCglpZiAoaXNzZXQoJF9HRVRbIlAiXSkgJiYgaW5fYXJyY
			XkoJF9HRVRbIlAiXSwgYXJyYXkoIm5ld19zaXRlIiwgInVwZGF0ZV9zaXRlIikpICYmIGVtcHR5KCRfUE9TVFsic2l0ZV9pZCJdKSkKCXsKCQlkaWUoIk11bHRpcGxlIFNpdGUgTWFuYWdlciBFcnJvci
			AtIFNpdGUgTGltaXQgUmVhY2hlZCIpOwoJfQp9"))); return;
        }
            
        if ( ! in_array($which, $this->cur_used))
        {
			if ($user_lang != 'english')
			{
				$paths = array(
								PATH_MOD.$package.'/language/'.$user_lang.'/lang.'.$which.EXT,
								PATH_MOD.$package.'/language/english/lang.'.$which.EXT,
								PATH_LANG.$user_lang.'/lang.'.$which.EXT,
								PATH_LANG.'english/lang.'.$which.EXT
							);				
			}
			else
			{
				$paths = array(
								PATH_MOD.$package.'/language/english/lang.'.$which.EXT,
								PATH_LANG.'english/lang.'.$which.EXT
							);
			}
			
			$success = FALSE;
			
			foreach($paths as $path)
			{
				if (file_exists($path) && @include $path)
				{
					$success = TRUE;
					break;
				}
			}
			
			if ($success !== TRUE)
			{
				if ($PREFS->ini('debug') >= 1)
				{
					$error = 'Unable to load the following language file:<br /><br />lang.'.$which.EXT;

					return $OUT->fatal_error($error);
				}
				else
				{
					return;	
				}				
			}

            $this->cur_used[] = $which;
            
            if (isset($L))
            {
            	$this->language = array_merge($this->language, $L);
            	unset($L);
            }
        }
    }
    /* END */
    
    
    /** -------------------------------------
    /**  Fetch a specific line of text
    /** -------------------------------------*/

    function line($which = '', $label = '')
    {
    	global $PREFS;
    
        if ($which != '')
        {
            $line = ( ! isset($this->language[$which])) ? FALSE : $this->language[$which];
                        
            $word_sub = ($PREFS->ini('weblog_nomenclature') != '' AND $PREFS->ini('weblog_nomenclature') != "weblog") ? $PREFS->ini('weblog_nomenclature') : '';
            
            if ($word_sub != '')
            {
                $line = preg_replace("/metaweblog/i", "Tr8Vc345s0lmsO", $line);
                $line = str_replace('"weblog"', 'Ghr77deCdje012', $line);
                $line = str_replace('weblog', strtolower($word_sub), $line);
                $line = str_replace('Weblog', ucfirst($word_sub),    $line);
                $line = str_replace("Tr8Vc345s0lmsO", 'Metaweblog', $line);
                $line = str_replace("Ghr77deCdje012", '"weblog"', $line);
            }
            
            if ($label != '')
            {
                $line = '<label for="'.$label.'">'.$line."</label>";
            }
            
            return stripslashes($line);
        }
    }
    /* END */
}
// END CLASS

// --------------------------------------------------------------------

/**
 * Procedural gateway to $LANG->line() for use in view files
 *
 * @access	public
 * @param	string
 * @param	string
 * @return	string
 */
function lang($which = '', $label = '')
{
	global $LANG;
	return $LANG->line($which, $label);
}

// --------------------------------------------------------------------

?>