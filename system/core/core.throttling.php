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
 File: core.throttling.php
-----------------------------------------------------
 Purpose: This class permits server load reduction
 by identifying repeated requests by bots/spammers, etc.
=====================================================
*/


class Throttling {

	var $max_page_loads = 10;
	var $time_interval	= 5;
	var $lockout_time	= 30;
	var $current_data	= FALSE;

    function Throttling()
    {
		global $PREFS;
		
		if ( ! is_numeric($PREFS->ini('max_page_loads')))
		{
			$PREFS->core_ini['enable_throttling'] = 'n';
			return;
		}
		else
		{
			$this->max_page_loads = $PREFS->ini('max_page_loads');
		}

		if (is_numeric($PREFS->ini('time_interval')))
		{
			$this->time_interval = $PREFS->ini('time_interval');
		}

		if (is_numeric($PREFS->ini('lockout_time')))
		{
			$this->lockout_time = $PREFS->ini('lockout_time');
		}
    }
    /* END */
    
    /** ----------------------------------------------
    /**  Is there a valid IP for this user?
    /** ----------------------------------------------*/
 
 	function throttle_ip_check()
 	{
 		global $IN, $PREFS;

		if ($PREFS->ini('enable_throttling') != 'y')
			return FALSE;


		if ($PREFS->ini('banish_masked_ips') == 'y' AND $IN->IP == '0.0.0.0' OR $IN->IP == '')
		{
			$this->banish();
		}
  	}
  	/* END */
  	

    /** ----------------------------------------------
    /**  Throttle Check
    /** ----------------------------------------------*/
        
    function throttle_check()
    {    
        global $IN, $DB, $PREFS;
                        
		if ($PREFS->ini('enable_throttling') != 'y')
			return FALSE;
                        
		$expire = time() - $this->time_interval;
		
		$query = $DB->query("SELECT hits, locked_out, last_activity FROM exp_throttle WHERE ip_address= '".$DB->escape_str($IN->IP)."'");
							 
		if ($query->num_rows == 0) $this->current_data = array();
  
  		if ($query->num_rows == 1)
  		{
  			$this->current_data = $query->row;

			$lockout = time() - $this->lockout_time;
	
			if ($query->row['locked_out'] == 'y' AND $query->row['last_activity'] > $lockout)
			{
				$this->banish();
				exit;
			}

  			if ($query->row['last_activity'] > $expire)
  			{
  				if ($query->row['hits'] == $this->max_page_loads)
  				{
  					// Lock them out and banish them...
					$DB->query("UPDATE exp_throttle SET locked_out = 'y', last_activity = '".time()."' WHERE ip_address= '".$DB->escape_str($IN->IP)."'");
					$this->banish();
					exit;
  				}
  			}
  		}
    }
    /* END */
  	
    /** ----------------------------------------------
    /**  Throttle Update
    /** ----------------------------------------------*/

    function throttle_update()
    {
    	global $IN, $DB, $PREFS;
    	
		if ($PREFS->ini('enable_throttling') != 'y')
			return FALSE;
    	
    	if ($this->current_data === FALSE)
    	{
			$query = $DB->query("SELECT hits, last_activity FROM exp_throttle WHERE ip_address= '".$DB->escape_str($IN->IP)."'");
			$this->current_data = ($query->num_rows == 1) ? $query->row : array();
		}
		
		if (sizeof($this->current_data) > 0)
		{
			$expire = time() - $this->time_interval;
			
			if ($this->current_data['last_activity'] > $expire) 
			{
				$hits = $this->current_data['hits'] + 1;
			}
			else
			{
				$hits = 1;
			}
							
			$DB->query("UPDATE exp_throttle SET hits = '{$hits}', last_activity = '".time()."', locked_out = 'n' WHERE ip_address= '".$DB->escape_str($IN->IP)."'");
		}
		else
		{
			$DB->query("INSERT INTO exp_throttle (ip_address, last_activity, hits) VALUES ('".$DB->escape_str($IN->IP)."', '".time()."', '1')");
		}
    }
    /* END */
    
  
    /** ----------------------------------------------
    /**  Banish User
    /** ----------------------------------------------*/
        
	function banish()
	{
		global $PREFS, $FNS;
		
		$type = (($PREFS->ini('banishment_type') == 'redirect' AND $PREFS->ini('banishment_url') == '')  OR ($PREFS->ini('banishment_type') == 'message' AND $PREFS->ini('banishment_message') == '')) ?  '404' : $PREFS->ini('banishment_type');
		
		switch ($type)
		{
			case 'redirect' :	$loc = ( ! preg_match("#^http://#i", $PREFS->ini('banishment_url'))) ? 'http://'.$PREFS->ini('banishment_url') : $PREFS->ini('banishment_url');
								header("Location: {$loc}");
				break;
			case 'message'	:	echo stripslashes($PREFS->ini('banishment_message'));
				break;
			default			:	header("Status: 404 Not Found"); echo "Status: 404 Not Found";
				break;
		}
		
		exit;	
	}
	/* END */
   
   
}
// END CLASS
?>