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
 File: mod.ip_to_nation.php
-----------------------------------------------------
 Purpose: IP to Nation mapping 
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Ip_to_nation {

    var $return_data = ''; 
        
    /** ----------------------------------------
    /**  World flags 
    /** ----------------------------------------*/

    function world_flags($ip = '')
    {
        global $TMPL, $REGX, $DB;
        
        if ($ip == '')
        	$ip = $TMPL->tagdata;
   
   		$ip = trim($ip);
   
		if ( ! $REGX->valid_ip($ip))
		{
			$this->return_data = $ip;
			return;
		}
		
		$query = $DB->query("SELECT country FROM exp_ip2nation WHERE ip < INET_ATON('".$DB->escape_str($ip)."') ORDER BY ip DESC LIMIT 0,1");
				
		if ($query->num_rows != 1)
		{
			$this->return_data = $ip;
			return;
		}		
		
		$country = $this->get_country($query->row['country']);

		if ($TMPL->fetch_param('type') == 'text')
		{
			$this->return_data = $country;
		}
		else
		{
			$this->return_data = '<img src="'.$TMPL->fetch_param('image_url').'flag_'.$query->row['country'].'.gif" width="18" height="12" alt="'.$country.'" title="'.$country.'" />';
		}       
		
		return $this->return_data;
    }
    /* END */
    
    
           
    /** ----------------------------------------
    /**  Countries
    /** ----------------------------------------*/

    function get_country($which = '')
    {
		global $TMPL, $SESS;
		
		if (! isset($SESS->cache['ip_to_nation']['countries']))
		{
			if ( ! include_once(PATH_LIB.'countries.php'))
			{
				$TMPL->log_item("IP to Nation Module Error: Countries library file not found");
				return 'Unknown';
			}			

			$SESS->cache['ip_to_nation']['countries'] = $countries;
		}
		
    	if ( ! isset($SESS->cache['ip_to_nation']['countries'][$which]))
    	{
    		return 'Unknown';
    	}
    
    	return $SESS->cache['ip_to_nation']['countries'][$which];
    }
    /* END */

    
}
// END CLASS
?>