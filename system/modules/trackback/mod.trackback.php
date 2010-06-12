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
 File: mod.trackback.php
-----------------------------------------------------
 Purpose: Trackback class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Trackback {
	
    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/
    
	function Trackback()
	{
	}


    /** ----------------------------------------
    /**  Trackback Entries
    /** ----------------------------------------*/

    function entries()
    {
        global $IN, $DB, $TMPL, $LOC, $FNS;
        
        $return = '';
        
        if ($IN->QSTR == '')
        {
            return false;
        }
        
        $entry_id = $IN->QSTR;

        
        $switch = array();
            
        /** ----------------------------------------
        /**  Build query
        /** ----------------------------------------*/
        
        $sql = "SELECT exp_trackbacks.*, exp_weblog_titles.weblog_id, exp_weblog_titles.allow_trackbacks
                FROM   exp_trackbacks
                LEFT   JOIN exp_weblog_titles ON (exp_weblog_titles.entry_id = exp_trackbacks.entry_id)
                WHERE  exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."')
                AND	";        
        
        $sql .= ( ! is_numeric($entry_id)) ? " exp_weblog_titles.url_title = '".$entry_id."' " : " exp_weblog_titles.entry_id = '$entry_id' ";
        
        $orderby  = ( ! $TMPL->fetch_param('orderby'))  ? 'trackback_date' : $TMPL->fetch_param('orderby');
        
        $sort  = ( ! $TMPL->fetch_param('sort'))  ? 'desc' : $TMPL->fetch_param('sort');
            
        $sql .= "ORDER BY $orderby $sort "; 
        
        if ($TMPL->fetch_param('limit'))
        {
            $sql .= "LIMIT ".$TMPL->fetch_param('limit'); 
        }
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
			return $TMPL->no_results();
        }
        
        if ($query->row['allow_trackbacks'] == 'n')
        {
			return $TMPL->no_results();
        }

        /** ----------------------------------------
        /**  Start the processing loop
        /** ----------------------------------------*/
        
        foreach ($query->result as $row)
        {
            $tagdata = $TMPL->tagdata;     
            
            /** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			
			$tagdata = $FNS->prep_conditionals($tagdata, $row);
            
            /** ----------------------------------------
            /**  Parse "single" variables
            /** ----------------------------------------*/

            foreach ($TMPL->var_single as $key => $val)
            { 
            
				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/
				
				if (strncmp('switch', $key, 6) == 0)
				{
					$sparam = $FNS->assign_parameters($key);
					
					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = explode("|", $sparam['switch']);
						
						if (count($sopt) == 2)
						{
							if (isset($switch[$sparam['switch']]) AND $switch[$sparam['switch']] == $sopt['0'])
							{
								$switch[$sparam['switch']] = $sopt['1'];
								
								$sw = $sopt['1'];									
							}
							else
							{
								$switch[$sparam['switch']] = $sopt['0'];
								
								$sw = $sopt['0'];									
							}
						}
					}
					
					$tagdata = $TMPL->swap_var_single($key, $sw, $tagdata);
				}
              
              
            
                /** ----------------------------------------
                /**  parse trackback date
                /** ----------------------------------------*/
                
                if (strncmp('trackback_date', $key, 14) == 0)
                {
                        $tagdata = $TMPL->swap_var_single(
                                                            $key, 
                                                            $LOC->decode_date($val, $row['trackback_date']), 
                                                            $tagdata
                                                          );
                }
                                        
                /** ----------------------------------------
                /**  parse basic fields - {title}, {content}, {trackback_url}, {weblog_name}, {trackback_id}
                /** ----------------------------------------*/
                 
                if (isset($row[$val]))
                {                    
                    $tagdata = $TMPL->swap_var_single($val, $row[$val], $tagdata);
                }
                    
            }        
        
        
            $return .= $tagdata;
        }
        
        return $return;
    }
    /* END */
    

   
    /** ----------------------------------------
    /**  Trackback URL
    /** ----------------------------------------*/

    // Returns the URL to the trackback server along with
    // the ID number of the entry associated with the TB
    
    function url($return_form = FALSE, $captcha = '')
    {
        global $FNS, $DB, $LANG, $IN, $LOC, $TMPL;
        
        if ($IN->QSTR == '')
            return;
            
        /** ----------------------------------------
        /**  Strip out the entry ID or URL title
        /** ----------------------------------------*/
        
		$qstring = $IN->QSTR;
		$uristr = '';

		if (preg_match("#/P(\d+)#", $qstring, $match))
		{
			$current_page = $match['1'];	
			
			$uristr  = $FNS->remove_double_slashes(str_replace($match['0'], '', $uristr));
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		/** --------------------------------------
		/**  Remove "N" 
		/** --------------------------------------*/

		if (preg_match("#/N(\d+)#", $qstring, $match))
		{			
			$qstring = $FNS->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
                
        $entry_id = trim($qstring);
         
		// If there is a slash in the entry ID we'll kill everything after it.
 		
 		$entry_id = preg_replace("#/.+?#", "", $entry_id);
 		
        /** ----------------------------------------
        /**  Build the query
        /** ----------------------------------------*/
        
		$timestamp = ($TMPL->cache_timestamp != '') ? $LOC->set_gmt($TMPL->cache_timestamp) : $LOC->now;
                
        $sql = "SELECT exp_weblog_titles.entry_id, exp_weblog_titles.allow_trackbacks, exp_weblogs.trackback_use_captcha
				FROM exp_weblog_titles, exp_weblogs 
				WHERE exp_weblog_titles.site_id IN ('".implode("','", $TMPL->site_ids)."')
				AND exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
				AND (expiration_date = 0 || expiration_date > ".$timestamp.") 
				AND status != 'closed' AND ";
        
		$sql .= ( ! is_numeric($entry_id)) ? " url_title = '".$entry_id."' " : " entry_id = '$entry_id' ";
		
		if (USER_BLOG === FALSE) 
		{
			$sql .= " AND exp_weblogs.is_user_blog = 'n' ";			
		}
		else
		{
			$sql .= " AND exp_weblogs.weblog_id = '".UB_BLOG_ID."' ";		
		}
        
        $query = $DB->query($sql);
        
        if ($query->num_rows == 0)
        {
            return '';
        }
        
        if ($query->row['allow_trackbacks'] == 'n')
        {
			$LANG->fetch_language_file('trackback');
            return $LANG->line('trackbacks_not_allowed');
        }
                            
        if ($query->row['trackback_use_captcha'] == 'y')
        {		
			if ($return_form == FALSE)
			{	
				return '{NOCACHE_TRACKBACK_HASH}';
			}
			
			$DB->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".$IN->IP."', '".$captcha."')");
			
			$old = time() - 60*60*2;  // 2 hours
			$DB->query("DELETE FROM exp_captcha WHERE date < ".$old);		
			
			$captcha .= '/';
		}
        
        $server = $FNS->fetch_site_index(1, 0)."trackback/".$query->row['entry_id'].'/'.$captcha;    
    
        return $server;
    }
    /* END */
        

}
// End Trackback Class
?>