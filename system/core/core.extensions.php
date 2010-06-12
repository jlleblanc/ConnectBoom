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
 File: core.extensions.php
-----------------------------------------------------
 Purpose: Controls Any EE Extensions.
=====================================================
*/



if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Extensions {  
   
    var $extensions 	= array();
    var $OBJ			= array();	// Current Instantiated Object
    var $end_script		= FALSE;	// To return or not to return
    var $last_call		= FALSE;	// The data returned from the last called method for this hook
    var $in_progress	= '';		// Last hook called.  Prevents loops.
    
    var $s_cache		= array();	// Array of previously unserialized settings
    
    var $version_numbers = array(); // To track the version of an extension
  
	/** ---------------------------------------
    /**  Constructor
    /** ---------------------------------------*/
    
    function Extensions()
  	{
  		global $DB, $PREFS;
  		
  		if ($PREFS->ini('allow_extensions') == 'y')
  		{
  			$query = $DB->query("SELECT DISTINCT ee.* FROM exp_extensions ee WHERE enabled = 'y' ORDER BY hook, priority ASC, class");
  			
  			if ($query->num_rows > 0)
  			{
  				$this->extensions = array();
  				
  				foreach($query->result as $row)
  				{
  					// There is a possiblity that there will be three extensions for a given
  					// hook and that two of them will call the same class but different methods
  					// while the third will have a priority that places it between those two.
  					// The chance is pretty remote and I cannot think offhand why someone
  					// would do this, but I have learned that our users and developers are
  					// a crazy bunch so I should make shite like this work initially and not
  					// just fix it later.
  					
  					// However, it makes no sense for a person to call the same class but different
  					// methods for the same hook at the same priority.  I feel confident in this.
  					// If someone does do this I will just have to point out the fact that they
  					// are a complete nutter.
  					
					// force the classname to conform to standard casing
					$row['class'] = ucfirst(strtolower($row['class']));
					
  					$this->extensions[$row['hook']][$row['priority']][$row['class']] = array($row['method'], $row['settings'], $row['version']);
  					
  					$this->version_numbers[$row['class']] = $row['version'];
  				}
  			}
  		}
  	}
  	/* END */
	
	
	/** ---------------------------------------
    /**  Extension Hook Method
    /**  - Used in ExpressionEngine to call an extension based on whichever hook is being triggered
    /** ---------------------------------------*/
    
    function call_extension($which, $parameter_one='')
    {
    	global $PREFS;
    	
		/** -----------------------------
		/**  Reset Our Variables
		/** -----------------------------*/
		
		$this->end_script	= FALSE;
		$this->last_call	= FALSE;
    	
    	/** -----------------------------
		/**  A Few Checks
		/** -----------------------------*/
		
		if ( ! isset($this->extensions[$which])) return;
		if ($PREFS->ini('allow_extensions') != 'y') return;
		if ($this->in_progress == $which) return;
		
    	/** -----------------------------
		/**  Get Arguments, Call the New Universal Method
		/** -----------------------------*/
		
    	$args = func_get_args();
    	
    	if (sizeof($args) == 1)
    	{
    		$args = array($which, '');
    	}
    	
    	if (version_compare(PHP_VERSION, '5.3') >= 0)
		{
			foreach ($args as $k => $v)
			{
				$args[$k] =& $args[$k];
			}
		}
    	
    	return call_user_func_array(array(&$this, 'universal_call_extension'), $args);
    }
    
	/** ---------------------------------------
    /**  The Universal Caller (Added in EE 1.6)
    /**  - Originally, using call_extension(), objects could not be called by reference in PHP 4
    /**  and thus could not be directly modified.  I found a clever way around that restriction
    /**  by always having the second argument gotten by reference.  The problem (and the reason
    /**  there is a call_extension() hook above) is that not all extension hooks have a second argument
    /**  and the PHP developers in their infinite wisdom decided that only variables could be passed
    /**  by reference.  So, call_extension() does a little magic to make sure there is always a second
    /**  argument and universal_call_extension() handles all of the object and reference handling
    /**  when needed.  -Paul
    /** ---------------------------------------*/

	function universal_call_extension($which, &$parameter_one)
	{
		global $PREFS, $REGX, $FNS, $OUT, $TMPL;
		
		/** -----------------------------
		/**  Reset Our Variables
		/** -----------------------------*/
		
		$this->end_script	= FALSE;
		$this->last_call	= FALSE;
		$php5_args			= array();
		
		/** -----------------------------
		/**  Anything to Do Here?
		/** -----------------------------*/
		
		if ( ! isset($this->extensions[$which])) return;
		if ($PREFS->ini('allow_extensions') != 'y') return;
		if ($this->in_progress == $which) return;
		
		$this->in_progress = $which;
		
		/** -----------------------------
		/**  Retrieve arguments for function
		/** -----------------------------*/
		
		if (is_object($parameter_one) && version_compare(PHP_VERSION, '5.0.0', '<') == 1)
		{
			$php4_object = TRUE;
			$args = array_slice(func_get_args(), 1);
		}
		else
		{
			$php4_object = FALSE;
			$args = array_slice(func_get_args(), 1);
		}

		if (version_compare(PHP_VERSION, '5') >= 0)
		{
			foreach($args as $k => $v)
			{
				$php5_args[$k] =& $args[$k];
			}
		}
		
		/** ------------------------------------------
		/**  Go through all the calls for this hook
		/** ------------------------------------------*/
		
		foreach($this->extensions[$which] as $priority => $calls)
		{
			foreach($calls as $class => $metadata)
			{
				/** ---------------------------------
				/**  Determine Path of Extension
				/** ---------------------------------*/
							
				$class_name = ucfirst($class);
				$path = PATH_EXT.'ext.'.$FNS->filename_security(strtolower($class)).EXT;
			
				if ( ! file_exists($path))
				{
					$error = 'Unable to load the following extension file:<br /><br />'.'ext.'.$FNS->filename_security(strtolower($class)).EXT;
					return $OUT->fatal_error($error);
				}
				
				/** --------------------------------
				/**  Include File
				/** --------------------------------*/
			
				if ( ! class_exists($class_name))
    	    	{
    	        	require $path;
    	    	}
    	    	
    	    	/** --------------------------------
				/**  A Bit of Meta
				/** --------------------------------*/
    	    	
    	    	$method	= $metadata['0'];
    	    	
    	    	// Unserializing and serializing is relatively slow, so we 
    	    	// cache the settings just in case multiple hooks are calling the 
    	    	// same extension multiple times during a single page load.  
    	    	// Thus, speeding it all up a bit.
    	    	
    	    	if (isset($this->s_cache[$class_name]))
    	    	{
    	    		$settings = $this->s_cache[$class_name];
    	    	}
    	    	else
    	    	{
    	    		$settings = ($metadata['1'] == '') ? '' : $REGX->array_stripslashes(unserialize($metadata['1']));
    	    		$this->s_cache[$class_name] = $settings;
    	    	}
				
				// -----------------------------------------
    	    	//  Call the class(s)
    	    	//
    	    	//  Each method could easily have its own settings,
    	    	//  so we have to send the settings each time
    	    	// -----------------------------------------
    	    	
    	    	$this->OBJ[$class_name] = new $class_name($settings);
    	    	
    	    	/** -----------------------------------------
    	    	/**  Update Extension First?
    	    	/** -----------------------------------------*/
    	    	
    	    	if ($this->OBJ[$class_name]->version > $this->version_numbers[$class_name] && method_exists($this->OBJ[$class_name], 'update_extension') === TRUE)
    	    	{
    	    		$update = call_user_func_array(array(&$this->OBJ[$class_name], 'update_extension'), array($this->version_numbers[$class_name]));
    	    		
    	    		$this->version_numbers[$class_name] = $this->OBJ[$class_name]->version;  // reset master
    	    	}
    	    	
    	    	// -----------------------------------------
    	    	//  Call Method and Store Returned Data
    	    	//
    	    	//  We put this in a class variable so that any extensions 
    	    	//  called after this one can retrieve the returned data from
    	    	//  previous methods and view/maniuplate that returned data
    	    	//  opposed to any original arguments the hook sent. In theory...
    	    	// -----------------------------------------
    	    	
				if (is_object($TMPL) && method_exists($TMPL, 'log_item'))
				{
					$TMPL->log_item('Calling Extension Class/Method: '.$class_name.'/'.$method);
				}
				
				if ($php4_object === TRUE)
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), array(&$parameter_one) + $args);
				}
				elseif ( ! empty($php5_args))
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), $php5_args);
				}
				else
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), $args);
				}
				
				$this->in_progress = '';
				
				// --------------------------------
				//  A $EXT->end_script value of TRUE means that the called 
				//	method wishes us to stop the calling of the main script.
				//  In this case, even if there are methods after this one for
				//  the hook we still stop the script now because extensions with
				//  a higher priority call the shots and thus override any 
				//  extensions with a lower priority.
				// --------------------------------
				
				if ($this->end_script === TRUE) return $this->last_call;
			}
		}
		
		/** --------------------------
		/**  Bottom's Up!
		/** --------------------------*/
		
		return $this->last_call;
	}
	/* END */
	
	/** ---------------------------------------
    /**  Check If Hook Has Activated Extension
    /** ---------------------------------------*/

	function active_hook($which)
	{
		if (isset($this->extensions[$which])) return TRUE;
		
		return FALSE;
    }
    /* END */

}
// END CLASS
?>