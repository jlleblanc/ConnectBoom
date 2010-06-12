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
 File: mod.referrer.php
-----------------------------------------------------
 Purpose: Referrer tracking class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Referrer {

    var $return_data  = '';

    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Referrer()
    {
        $this->referrer_rows();
    }
    /* END */

    
    /** -------------------------------------
    /**  Show referers
    /** -------------------------------------*/

    function referrer_rows()
    {
        global $TMPL, $DB, $PREFS, $LOC, $FNS;
        
        $switch = array();
        
        $pop =  ($TMPL->fetch_param('popup') == 'yes') ? ' target="_blank" ' : '';        
                

        /** -------------------------------------
        /**  Build query
        /** -------------------------------------*/

        $sql = "SELECT * FROM exp_referrers ";

        if (USER_BLOG === FALSE)
        {
            $sql .= "WHERE user_blog = '' ";
        }
        else
        {
            $sql .= "WHERE user_blog = '".USER_BLOG."' ";
        }
        
        $sql .= "AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' ORDER BY ref_id desc ";
        
    
        if ( ! $TMPL->fetch_param('limit'))
        {
            $sql .= "LIMIT 100";
        }
        else
        {
            $sql .= "LIMIT ".$TMPL->fetch_param('limit');
        }

        $query = $DB->query($sql);
		$site_url = $PREFS->ini('site_url');
        
        /** -------------------------------------
        /**  Parse result
        /** -------------------------------------*/
        
        if ($query->num_rows > 0)
        {
            foreach ($query->result as $row)
            {
                $tagdata = $TMPL->tagdata; 
                
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
                    /**  {ref_from}
                    /** ----------------------------------------*/
                    
                    if ($key == "ref_from")
                    {
                        $from = '<a href="'.$this->encode_ee_tags($row['ref_from']).'"'.$pop.'>'.$this->encode_ee_tags($row['ref_from']).'</a>';
                    
                        $tagdata = $TMPL->swap_var_single($val, $from, $tagdata);
                    }
    
                
                    /** ----------------------------------------
                    /**  {ref_to}
                    /** ----------------------------------------*/
                    
                    if ($key == "ref_to")
                    {		
            			$to_short = str_replace($site_url, '', $row['ref_to']);
                    
                        $to  = '<a href="'.$this->encode_ee_tags($row['ref_to']).'">'.$this->encode_ee_tags($to_short).'</a>';
                    
                        $tagdata = $TMPL->swap_var_single($val, $to, $tagdata);
                    }
                    
                     /** ----------------------------------------
                    /**  {ref_ip}
                    /** ----------------------------------------*/
                    
                    if ($key == "ref_ip")
                    {
                       $ip = ( ! isset($row['ref_ip'])) ? '-' : $row['ref_ip'];
                    	
                       $tagdata = $TMPL->swap_var_single($val, $ip, $tagdata);
                    }
                    
                    
                    /** ----------------------------------------
                    /**  {ref_agent}
                    /** ----------------------------------------*/
                    
                    if ($key == "ref_agent")
                    {
                       $agent = ( ! isset($row['ref_agent'])) ? '-' : $this->encode_ee_tags($row['ref_agent']);
                    	
                       $tagdata = $TMPL->swap_var_single($val, $agent, $tagdata);
                    }
                    
                    
                    /** ----------------------------------------
                    /**  {ref_agent_short}
                    /** ----------------------------------------*/
                    
                    if ($key == "ref_agent_short")
                    {
                       $agent = ( ! isset($row['ref_agent'])) ? '-' : preg_replace("/(.+?)\s+.*/", "\\1", $this->encode_ee_tags($row['ref_agent']));
                    	
                       $tagdata = $TMPL->swap_var_single($val, $agent, $tagdata);
                    }
                
                
                    /** ----------------------------------------
                    /**  {ref_date}
                    /** ----------------------------------------*/
                    
                    if (strncmp('ref_date', $key, 8) == 0)
                    {
                    	if ( ! isset($row['ref_date']) || $row['ref_date'] == 0)
                    	{
                    		$date = '-';
                    	}
                    	else
  						{
							$date = $LOC->decode_date($val, $row['ref_date']);
                    	}
                        $tagdata = $TMPL->swap_var_single($key, $date, $tagdata);
                    }                    
                }
                
                $this->return_data .= $tagdata;
            }

        }
        
    }
    /* END */
	
	
	/** -------------------------------------
    /**  Encode EE Tags
    /** -------------------------------------*/

    function encode_ee_tags($str)
    {
    	if ($str != '')
    	{
        	$str = str_replace('{', '&#123;', $str);
        	$str = str_replace('}', '&#125;', $str);
        }
        
        return $str;
    }
    /* END */


}
// END CLASS
?>