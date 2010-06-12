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
 File: core.template.php
-----------------------------------------------------
 Purpose: Template parsing class.
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Template {
        
    var $loop_count      	=   0;			// Main loop counter.    
    var $depth           	=   0;			// Sub-template loop depth
    var $in_point        	=  '';			// String position of matched opening tag
    var $template        	=  '';			// The requested template (page)
    var $final_template     =  '';			// The finalized template
    var $fl_tmpl         	=  '';			// 'Floating' copy of the template.  Used as a temporary "work area".
    var $cache_hash      	=  '';			// md5 checksum of the template name.  Used as title of cache file.
    var $cache_status   	=  '';			// Status of page cache (NO_CACHE, CURRENT, EXPIRED)
    var $cache_timestamp	=  ''; 
    var $template_type  	=  '';			// Type of template (webpage, rss)
    var $embed_type			=  '';			// Type of template for embedded template
    var $template_hits   	=   0;
    var $php_parse_location =  'output';	// Where in the chain the PHP gets parsed
	var $template_edit_date	=	'';			// Template edit date
    
    var $encode_email		=  TRUE;		// Whether to use the email encoder.  This is set automatically
    var $hit_lock_override	=  FALSE;		// Set to TRUE if you want hits tracked on sub-templates
    var $hit_lock        	=  FALSE;		// Lets us lock the hit counter if sub-templates are contained in a template
    var $parse_php			=  FALSE;		// Whether to parse PHP or not
    var $protect_javascript =  TRUE;		// Protect javascript in conditionals
  
	var	$templates_sofar	= '';			// Templates processed so far, subtemplate tracker 
    var $tag_data        	= array();		// Data contained in tags
    var $modules         	= array();		// List of installed modules
    var $module_data		= array();		// Data for modules from exp_weblogs
    var $plugins         	= array();		// List of installed plug-ins
    var $native_modules		= array();		// List of native modules with EE
    
    var $var_single      	= array();		// "Single" variables
    var $var_cond        	= array();		// "Conditional" variables
    var $var_pair        	= array();		// "Paired" variables
    var $global_vars        = array();		// This array can be set via the path.php file
    var $embed_vars         = array();		// This array can be set via the {embed} tag
    var $segment_vars		= array();		// Array of segment variables
        
	var $tagparts			= array();		// The parts of the tag: {exp:comment:form}
	var $tagdata			= '';			// The chunk between tag pairs.  This is what modules will utilize
    var $tagproper			= '';			// The full opening tag
    var $no_results			= '';			// The contents of the {if no_results}{/if} conditionals
    var $no_results_block	= '';			// The {if no_results}{/if} chunk
	var $search_fields		= array();		// Tag parameters that begin with 'search:'
    
    var $related_data		= array();		//  A multi-dimensional array containing any related tags
    var $related_id			= '';			// Used temporarily for the related ID number
    var $related_markers	= array();		// Used temporarily
    
    var $site_ids			= array();		// Site IDs for the Sites Request for a Tag
    var $sites				= array();		// Array of sites with site_id as key and site_name as value, used to determine site_ids for tag, above.
    var $site_prefs_cache	= array();		// Array of cached site prefs, to allow fetching of another site's template files

    var $reverse_related_data = array();	//  A multi-dimensional array containing any reverse related tags
   
    var $t_cache_path    	= 'tag_cache/';	 // Location of the tag cache file
    var $p_cache_path    	= 'page_cache/'; // Location of the page cache file
    var $disable_caching	=  FALSE;
    
    var $debugging			= FALSE;		// Template parser debugging on?
    var $cease_processing	= FALSE;		// Used with no_results() method.
    var $log				= array();		// Log of Template processing
    var $start_microtime	= 0;			// For Logging (= microtime())
    
    var $strict_urls		= FALSE;		// Whether to make URLs operate strictly or not.  This is set via a template global pref
    
    var $realm				= 'ExpressionEngine Template';  // Localize?

    var $marker = '0o93H7pQ09L8X1t49cHY01Z5j4TT91fGfr'; // Temporary marker used as a place-holder for template data
    

    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Template()
    {
    	global $IN, $PREFS;
    	
    	$this->native_modules = array('blacklist', 'comment', 'email', 'forum',
    								  'gallery', 'mailinglist', 'member', 'query',
    								  'referrer', 'rss', 'search', 'stats', 
    								  'trackback', 'updated_sites', 'weblog', 
    								  'simple_commerce', 'commerce');
    								  
    	$this->global_vars = $IN->global_vars;
    	
    	if ($PREFS->ini('multiple_sites_enabled') != 'y')
    	{
    		$this->sites[$PREFS->ini('site_id')] = $PREFS->ini('site_short_name');
    	}
    	
    	if ($PREFS->ini('template_debugging') === 'y' && $this->start_microtime == 0)
    	{
    		$this->debugging = TRUE;
    		
    		if (phpversion() < 5)
    		{
    			list($usec, $sec) = explode(" ", microtime());
    			$this->start_microtime = ((float)$usec + (float)$sec);
    		}
    		else
    		{
    			$this->start_microtime = microtime(TRUE);
    		}
    	}
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Run the template engine
    /** -------------------------------------*/

    function run_template_engine($template_group = '', $template = '')
    {
        global $OUT, $IN, $FNS, $PREFS;
        
        $this->log_item(" - Begin Template Processing - ");
                
        // Set the name of the cache folder for both tag and page caching
        
        if ($IN->URI != '')
        {          
            $this->t_cache_path .= md5($FNS->fetch_site_index().$IN->URI).'/';
            $this->p_cache_path .= md5($FNS->fetch_site_index().$IN->URI).'/';        
        }
        else
        {
            $this->t_cache_path .= md5($PREFS->ini('site_url').'index'.$IN->QSTR).'/';
            $this->p_cache_path .= md5($PREFS->ini('site_url').'index'.$IN->QSTR).'/';
        }
        
        
		// We limit the total number of cache files in order to
		// keep some sanity with large sites or ones that get
		// hit by over-ambitious crawlers.
		
		if ($this->disable_caching == FALSE)
		{		
			if ($dh = @opendir(PATH_CACHE.'page_cache'))
			{
				$i = 0;
				while (false !== (readdir($dh)))
				{
					$i++;
				}
				
				$max = ( ! $PREFS->ini('max_caches') OR ! is_numeric($PREFS->ini('max_caches')) OR $PREFS->ini('max_caches') > 1000) ? 1000 : $PREFS->ini('max_caches');
				
				if ($i > $max)
				{
					$FNS->clear_caching('page');
				}			
			}   
        }
        
		$this->log_item("URI: ".$IN->URI);
		$this->log_item("Path.php Template: {$template_group}/{$template}");

        $this->process_template($template_group, $template, FALSE);
        
		$this->log_item(" - End Template Processing - ");
		$this->log_item("Parse Global Variables");

		if ($this->template_type == 'static')
		{
			$this->final_template = $this->restore_xml_declaration($this->final_template);
		}
		else
		{
       		$this->final_template = $this->parse_globals($this->final_template);
		}
		
		$this->log_item("Template Parsing Finished");
		
       	$OUT->out_type = $this->template_type;
       	$OUT->build_queue($this->final_template); 
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Process Template
    /** -------------------------------------*/

    function process_template($template_group = '', $template = '', $sub = FALSE, $site_id = '')
    {
        global $LOC, $PREFS, $REGX, $LANG, $IN, $FNS;
        
		// add this template to our subtemplate tracker
		$this->templates_sofar = $this->templates_sofar.'|'.$site_id.':'.$template_group.'/'.$template.'|';

		/** -------------------------------------
        /**  Fetch the requested template
        /** -------------------------------------*/
		// The template can either come from the DB or a cache file

		// Do not use a reference!
		
        $this->cache_status = 'NO_CACHE';
        
        $this->log_item("Retrieving Template");
						        
		$this->template = ($template_group != '' AND $template != '') ? $this->fetch_template($template_group, $template, FALSE, $site_id) : $this->parse_template_uri();
			
		$this->log_item("Template Type: ".$this->template_type);	
			
		/** -------------------------------------
        /**  Static Content, No Parsing
        /** -------------------------------------*/
				
		if ($this->template_type == 'static' OR $this->embed_type == 'static')
		{
			if ($sub == FALSE)
			{
				$this->final_template = $this->template;
			}
			
			return;
		}
		
		/* -------------------------------------
		/*  "Smart" Static Parsing
		/*  
		/*  Performed on embedded webpage templates only that do not have 
		/*	ExpressionEngine tags or PHP in them.
		/*  
		/*  Hidden Configuration Variable
		/*  - smart_static_parsing => Bypass parsing of templates that could be 
		/*	of the type 'static' but aren't? (y/n)
		/* -------------------------------------*/

		if ($PREFS->ini('smart_static_parsing') !== 'n' && $this->embed_type == 'webpage' && ! stristr($this->template, LD) && ! stristr($this->template, '<?'))
		{
			$this->log_item("Smart Static Parsing Triggered");
			
			if ($sub == FALSE)
			{
				$this->final_template = $this->template;
			}
			
			return;
		}
		
		/** -------------------------------------
        /**  Replace "logged_out" variables
        /** -------------------------------------*/
		// We do this for backward compatibility
		// Note:  My plan is to deprecate this, but we need to update
		// every template in an installation with the new syntax.
		// I would have done it for 1.2 but I added this late, after the
		// beta testers already got a copy, so we'll do it in a future update.
		
		$logvars = array('NOT_LOGGED_IN' => 'logged_out', 'not_logged_in' => 'logged_out', 'LOGGED_IN' => 'logged_in');
		
		foreach($logvars as $key => $val)
		{
			$this->template = str_replace(LD.'if '.$key.RD, LD.'if '.$val.RD, $this->template);
        }
                
		/** -------------------------------------
        /**  Parse URI segments
        /** -------------------------------------*/
        
        // This code lets admins fetch URI segments which become
        // available as:  {segment_1} {segment_2}        
                
		for ($i = 1; $i < 10; $i++)
		{
			$this->template = str_replace(LD.'segment_'.$i.RD, $IN->fetch_uri_segment($i), $this->template); 
			$this->segment_vars['segment_'.$i] = $IN->fetch_uri_segment($i);
		}
		
		/** -------------------------------------
        /**  Parse {embed} tag variables
        /** -------------------------------------*/
		
		if ($sub === TRUE && count($this->embed_vars) > 0)
		{
			$this->log_item("Embed Variables (Keys): ".implode('|', array_keys($this->embed_vars)));
			$this->log_item("Embed Variables (Values): ".trim(implode('|', $this->embed_vars)));
		
			foreach ($this->embed_vars as $key => $val)
			{
				// add 'embed:' to the key for replacement and so these variables work in conditionals
				$this->embed_vars['embed:'.$key] = $val;
				unset($this->embed_vars[$key]);
				$this->template = str_replace(LD.'embed:'.$key.RD, $val, $this->template); 
			}
		}
		
		// cleanup of leftover/undeclared embed variables
		// don't worry with undeclared embed: vars in conditionals as the conditionals processor will handle that adequately
		if (strpos($this->template, LD.'embed:') !== FALSE)
		{
			$this->template = preg_replace('/'.LD.'embed:(.+?)'.RD.'/', '', $this->template);
		}
		
		/** --------------------------------------------------
        /**  Parse 'Site' variables
        /** --------------------------------------------------*/

		$this->log_item("Parsing Site Variables");

		// load site variables into the global_vars array
		foreach (array('site_id', 'site_label', 'site_short_name') as $site_var)
		{
			$this->global_vars[$site_var] = stripslashes($PREFS->ini($site_var));
		}
		
		/** -------------------------------------
        /**  Parse manual variables
        /** -------------------------------------*/
		// These are variables that can be set in the path.php file
		
		if (count($this->global_vars) > 0)
		{
			$this->log_item("Global Path.php Variables (Keys): ".implode('|', array_keys($this->global_vars)));
			$this->log_item("Global Path.php Variables (Values): ".trim(implode('|', $this->global_vars)));
		
			foreach ($this->global_vars as $key => $val)
			{
				$this->template = str_replace(LD.$key.RD, $val, $this->template); 
			}
		}
		
		/** -------------------------------------
		/**  Parse date format string "constants"
		/** -------------------------------------*/
		
		$date_constants	= array('DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
								'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
								'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%O',
								'DATE_RFC822'	=>	'%D, %d %M %y %H:%i:%s %O',
								'DATE_RFC850'	=>	'%l, %d-%M-%y %H:%m:%i UTC',
								'DATE_RFC1036'	=>	'%D, %d %M %y %H:%i:%s %O',
								'DATE_RFC1123'	=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_RFC2822'	=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_RSS'		=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_W3C'		=>	'%Y-%m-%dT%H:%i:%s%Q'
								);
		foreach ($date_constants as $key => $val)
		{
			$this->template = str_replace(LD.$key.RD, $val, $this->template);
		}
		
		$this->log_item("Parse Date Format String Constants");
		
        /** --------------------------------------------------
        /**  Template's Last Edit time {template_edit_date format="%Y %m %d %H:%i:%s"}
        /** --------------------------------------------------*/

   		if (strpos($this->template, LD.'template_edit_date') !== FALSE && preg_match_all("/".LD."template_edit_date\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
   		{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{				
				$this->template = preg_replace("/".$matches['0'][$j]."/", $LOC->decode_date($matches['2'][$j], $this->template_edit_date), $this->template, 1);				
			}
		}  

		/** --------------------------------------------------
        /**  Current time {current_time format="%Y %m %d %H:%i:%s"}
        /** --------------------------------------------------*/

   		if (strpos($this->template, LD.'current_time') !== FALSE && preg_match_all("/".LD."current_time\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
   		{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{				
				$this->template = preg_replace("/".preg_quote($matches['0'][$j], '/')."/", $LOC->decode_date($matches['2'][$j], $LOC->now), $this->template, 1);				
			}
		}
		
		$this->template = str_replace(LD.'current_time'.RD, $LOC->now, $this->template);
		
		$this->log_item("Parse Current Time Variables");
		
		/** -------------------------------------
        /**  Is the main template cached?
        /** -------------------------------------*/
        // If a cache file exists for the primary template
        // there is no reason to go further.
        // However we do need to fetch any subtemplates

        if ($this->cache_status == 'CURRENT' AND $sub == FALSE)
        {
        	$this->log_item("Cached Template Used");
        
        	$this->template = $this->parse_nocache($this->template);
        
        	/** -------------------------------------
			/**  Smite Our Enemies:  Advanced Conditionals
			/** -------------------------------------*/
			
			if (stristr($this->template, LD.'if'))
			{
				$this->template = $this->advanced_conditionals($this->template);
			}
			
			$this->log_item("Conditionals Parsed, Processing Sub Templates");
        
			$this->final_template = $this->template;
			$this->process_sub_templates($this->template); 	
			return;
        }

        // Remove whitespace from variables.
        // This helps prevent errors, particularly if PHP is used in a template
        $this->template = preg_replace("/".LD."\s*(\S+)\s*".RD."/U", LD."\\1".RD, $this->template);

		/** -------------------------------------
		/**  Parse Input Stage PHP
		/** -------------------------------------*/
		
		if ($this->parse_php == TRUE AND $this->php_parse_location == 'input' AND $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Input");
			$this->template = $this->parse_template_php($this->template);	
		}
		
		/** -------------------------------------
		/**  Smite Our Enemies:  Conditionals
		/** -------------------------------------*/
		
		$this->log_item("Parsing Segment, Embed, and Global Vars Conditionals");
		
		$this->template = $this->segment_conditionals($this->template);
		$this->template = $this->array_conditionals($this->template, $this->embed_vars);
		$this->template = $this->array_conditionals($this->template, $this->global_vars);

		/** -------------------------------------
		/**  Set global variable assignment
		/** -------------------------------------*/

   		if (preg_match_all("/".LD."assign_variable:(.+?)=([\"\'])([^\\2]*?)\\2".RD."/i", $this->template, $matches))
   		{	
   			$this->log_item("Processing Assigned Variables: ".trim(implode('|', $matches['1'])));
   			
			for ($j = 0; $j < count($matches['0']); $j++)
			{				
				$this->template = str_replace($matches['0'][$j], "", $this->template);
				$this->template = str_replace(LD.$matches['1'][$j].RD, $matches['3'][$j], $this->template);
			}
		}
		
		/** -------------------------------------
		/**  Process the template
		/** -------------------------------------*/
		
		// Replace forward slashes with entity to prevent preg_replace errors.
		$this->template = str_replace('/', SLASH, $this->template);

		// Fetch installed modules and plugins if needed
		if (count($this->modules) == 0)
		{
			$this->fetch_modules();
		}
		
		if (count($this->plugins) == 0)
		{
			$this->fetch_plugins();
		}
	
		// Parse the template.
		
		$this->log_item(" - Beginning Tag Processing - ");
		
		while (is_int(strpos($this->template, LD.'exp:')))
		{
			// Initialize values between loops
			$this->tag_data 	= array();
			$this->var_single	= array();
			$this->var_cond		= array();
			$this->var_pair		= array(); 
			$this->loop_count 	= 0;
			
			$this->log_item("Parsing Tags in Template");

			// Run the template parser
			$this->parse_template();
			
			$this->log_item("Processing Tags");
			
			// Run the class/method handler
			$this->class_handler();
			
			if ($this->cease_processing === TRUE)
			{
				return;
			}
		}
		
		$this->log_item(" - End Tag Processing - ");
		
		// Decode forward slash entities back to ascii
		
		$this->template = str_replace(SLASH, '/', $this->template);
		
		/** -------------------------------------
		/**  Parse Output Stage PHP
		/** -------------------------------------*/
		
		if ($this->parse_php == TRUE AND $this->php_parse_location == 'output' AND $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Output");
			$this->template = $this->parse_template_php($this->template);	
		}
					
		/** -------------------------------------
        /**  Write the cache file if needed
        /** -------------------------------------*/
				
		if ($this->cache_status == 'EXPIRED')
		{ 
			$this->template = $FNS->insert_action_ids($this->template);
			$this->write_cache_file($this->cache_hash, $this->template, 'template');
		}
		
		/** -------------------------------------
		/**  Parse Our Uncacheable Forms
		/** -------------------------------------*/
		
		$this->template = $this->parse_nocache($this->template);
		
		/** -------------------------------------
		/**  Smite Our Enemies:  Advanced Conditionals
		/** -------------------------------------*/
		
		if (stristr($this->template, LD.'if'))
		{
			$this->log_item("Processing Advanced Conditionals");
			$this->template = $this->advanced_conditionals($this->template);
		}
		
		// <?php  This fixes a BBEdit bug that makes the list of function not work right.  Seems related to the PHP declarations above.
		
		
		/** -------------------------------------
        /**  Build finalized template
        /** -------------------------------------*/
		
		// We only do this on the first pass.
		// The sub-template routine will insert embedded
		// templates into the master template
        
        if ($sub == FALSE)
        {
        	$this->final_template = $this->template;
			$this->process_sub_templates($this->template); 
        }
     }
    /* END */
        
     
        
    /** -------------------------------------
    /**  Parse embedded sub-templates
    /** -------------------------------------*/

    function process_sub_templates($template)
    {
        global $REGX, $FNS, $LANG, $PREFS, $DB;

		/** -------------------------------------
		/**  Match all {embed=bla/bla} tags
		/** -------------------------------------*/
        
        $matches = array();
    
        if ( ! preg_match_all("/(".LD."embed\s*=)(.*?)".RD."/s", $template, $matches))
        {
			return;
        }

		/** -------------------------------------
		/**  Loop until we have parsed all sub-templates
		/** -------------------------------------*/
		
		// For each embedded tag we encounter we'll run the template parsing
		// function - AND - through the beauty of recursive functions we
		// will also call THIS function as well, allowing us to parse 
		// infinitely nested sub-templates in one giant loop o' love
        
        $this->log_item(" - Processing Sub Templates (Depth: ".($this->depth+1).") - ");
        
        $i = 0;
        $this->depth++;
        
        $this->log_item("List of Embeds: ".str_replace(array('"', "'"), '', trim(implode(',', $matches['2']))));

		// re-match the full tag of each if necessary before we start processing
		// necessary evil in case template globals are used inside the embed tag,
		// doing this within the processing loop will result in leaving unparsed
		// embed tags e.g. {embed="foo/bar" var="{global_var}/{custom_field}"}
		$temp = $template;
		foreach ($matches[2] as $key => $val)
		{
			if (strpos($val, LD) !== FALSE)
			{
				$matches[0][$key] = $FNS->full_tag($matches[0][$key], $temp);
				$matches[2][$key] = substr(str_replace($matches[1][$key], '', $matches[0][$key]), 0, -1);
				$temp = str_replace($matches[0][$key], '', $temp);
			}
		}

        foreach($matches['2'] as $key => $val)
		{ 
			$parts = preg_split("/\s+/", $val, 2);
			
			$this->embed_vars = (isset($parts['1'])) ? $FNS->assign_parameters($parts['1']) : array();
			
			if ($this->embed_vars === FALSE)
			{
				$this->embed_vars = array();
			}
			
			$val = $REGX->trim_slashes($REGX->strip_quotes($parts['0']));

			if ( ! stristr($val, '/'))
			{   
				continue;
			}
	
			$ex = explode("/", trim($val));
			
			if (count($ex) != 2)
			{
				continue;
			}
			
			/** ----------------------------------
			/**  Determine Site
			/** ----------------------------------*/
			
			$site_id = $PREFS->ini('site_id');
			
			if (stristr($ex[0], ':'))
			{
				$name = substr($ex[0], 0, strpos($ex[0], ':'));
				
				if ($PREFS->ini('multiple_sites_enabled') == 'y')
				{
					if (sizeof($this->sites) == 0)
					{
						$sites_query = $DB->query("SELECT site_id, site_name FROM exp_sites");
						
						foreach($sites_query->result as $row)
						{
							$this->sites[$row['site_id']] = $row['site_name'];
						}
					}
					
					$site_id = array_search($name, $this->sites);
					
					if (empty($site_id))
					{
						$site_id = $PREFS->ini('site_id');
					}
				}
				
				$ex[0] = str_replace($name.':', '', $ex[0]);
			}
			
			
			/** ----------------------------------
			/**  Loop Prevention
			/** ----------------------------------*/

			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- template_loop_prevention => 'n' 
				Whether or not loop prevention is enabled - y/n
			/* -------------------------------------------*/
		
			if (substr_count($this->templates_sofar, '|'.$site_id.':'.$ex['0'].'/'.$ex['1'].'|') > 1 && $PREFS->ini('template_loop_prevention') != 'n')
			{
				$this->final_template = ($PREFS->ini('debug') >= 1) ? str_replace('%s', $ex['0'].'/'.$ex['1'], $LANG->line('template_loop')) : "";
				return;				
			}
				
			/** ----------------------------------
			/**  Process Subtemplate
			/** ----------------------------------*/
			
			$this->log_item("Processing Sub Template: ".$ex['0']."/".$ex['1']);
				
			$this->process_template($ex['0'], $ex['1'], TRUE, $site_id);

			$this->final_template = str_replace($matches['0'][$key], $this->template, $this->final_template);

			$this->embed_type = '';
			
			// Here we go again!  Wheeeeeee.....				
			$this->process_sub_templates($this->template);
			
			// pull the subtemplate tracker back a level to the parent template
			$this->templates_sofar = substr($this->templates_sofar, 0, - strlen('|'.$site_id.':'.$ex[0].'/'.$ex[1].'|'));
		}

        $this->depth--;

        if ($this->depth == 0)
        {
        	$this->templates_sofar = '';
        }
        
    }
    /* END */



    /** -------------------------------------
    /**  Parse the template
    /** -------------------------------------*/

    function parse_template()
    {
    	global $FNS;
    
        while (TRUE)  
        {
            // Make a "floating" copy of the template which we'll progressively slice into pieces with each loop
            
            $this->fl_tmpl = $this->template;
                    
            // Identify the string position of the first occurence of a matched tag
            
            $this->in_point = strpos($this->fl_tmpl, LD.'exp:');
                              
            // If the above variable returns false we are done looking for tags
            // This single conditional keeps the template engine from spiraling 
            // out of control in an infinite loop.        
            
            if (FALSE === $this->in_point)
            {
                break;
            }
            else
            {
                /** ------------------------------------------
                /**  Process the tag data
                /** ------------------------------------------*/
                
                // These REGEXs parse out the various components contained in any given tag.
                
                // Grab the opening portion of the tag: {exp:some:tag param="value" param="value"}

                if ( ! preg_match("/".LD.'exp:'.".*?".RD."/s", $this->fl_tmpl, $matches))
                {
                	$this->template = preg_replace("/".LD.'exp:'.".*?$/", '', $this->template);
                	break;
                }
                
                $this->log_item("Tag: ".$matches['0']);
                
                // Checking for variables/tags embedded within tags
                // {exp:weblog:entries weblog="{master_weblog_name}"}                
                if (stristr(substr($matches['0'], 1), LD) !== false)
                {
                	$matches['0'] = $FNS->full_tag($matches['0']);
                }
                                
                $raw_tag = preg_replace("/(\r\n)|(\r)|(\n)|(\t)/", ' ', $matches['0']);
                                
                $tag_length = strlen($raw_tag);
                
                $data_start = $this->in_point + $tag_length;

                $tag  = trim(substr($raw_tag, 1, -1));
                $args = trim((preg_match("/\s+.*/", $tag, $matches))) ? $matches['0'] : '';
                $tag  = trim(str_replace($args, '', $tag));  
                
                $cur_tag_close = LD.SLASH.$tag.RD;
                
                // -----------------------------------------
                
                // Assign the class name/method name and any parameters
                 
                $class = $this->assign_class(substr($tag, strlen('exp') + 1));
                $args  = $FNS->assign_parameters($args);
                
				// standardized mechanism for "search" type parameters get some extra lovin'
				
				$search_fields = array();
				
				if ($args !== FALSE)
				{
					foreach ($args as $key => $val)
					{
						if (strncmp($key, 'search:', 7) == 0)
						{
							$search_fields[substr($key, 7)] = str_replace(SLASH, '/', $val);
						}
					}					
				}
				
                // Trim the floating template, removing the tag we just parsed.
                
                $this->fl_tmpl = substr($this->fl_tmpl, $this->in_point + $tag_length);
                
                $out_point = strpos($this->fl_tmpl, $cur_tag_close);
                
                // Do we have a tag pair?
                
                if (FALSE !== $out_point)
                { 
                    // Assign the data contained between the opening/closing tag pair
                    
                    $this->log_item("Closing Tag Found");
                
                    $block = substr($this->template, $data_start, $out_point);  
                    
                    // Fetch the "no_results" data
                    
                    $no_results = '';
                    $no_results_block = '';
                    
					if (preg_match("/".LD."if no_results".RD."(.*?)".LD.SLASH."if".RD."/s", $block, $match)) 
					{
						// Match the entirety of the conditional, dude.  Bad Rick!
						
						if (stristr($match['1'], LD.'if'))
						{
							$match['0'] = $FNS->full_tag($match['0'], $block, LD.'if', LD.SLASH."if".RD);
						}
						
						$no_results = substr($match['0'], strlen(LD."if no_results".RD), -strlen(LD.SLASH."if".RD));
						
						$no_results_block = $match['0'];
					}
					        
                    // Define the entire "chunk" - from the left edge of the opening tag 
                    // to the right edge of closing tag.

                    $out_point = $out_point + $tag_length + strlen($cur_tag_close);
                                        
                    $chunk = substr($this->template, $this->in_point, $out_point);
                }
                else
                {
                    // Single tag...
                    
                    $this->log_item("No Closing Tag");
                    
                    $block = ''; // Single tags don't contain data blocks
                    
					$no_results = '';
					$no_results_block = '';
                
                    // Define the entire opening tag as a "chunk"
                
                    $chunk = substr($this->template, $this->in_point, $tag_length);                
                }
                                
                // Strip the "chunk" from the template, replacing it with a unique marker.
                
                if (stristr($raw_tag, 'random'))
                {
                	$this->template = preg_replace("|".preg_quote($chunk)."|s", 'M'.$this->loop_count.$this->marker, $this->template, 1);
				}
				else
				{
					$this->template = str_replace($chunk, 'M'.$this->loop_count.$this->marker, $this->template);
				}
				
                $cfile = md5($chunk); // This becomes the name of the cache file

                // Build a multi-dimensional array containing all of the tag data we've assembled
                  
                $this->tag_data[$this->loop_count]['tag']				= $raw_tag;
                $this->tag_data[$this->loop_count]['class']				= $class['0'];
                $this->tag_data[$this->loop_count]['method']			= $class['1'];
                $this->tag_data[$this->loop_count]['tagparts']			= $class;
                $this->tag_data[$this->loop_count]['params']			= $args;
                $this->tag_data[$this->loop_count]['chunk']				= $chunk; // Matched data block - including opening/closing tags          
                $this->tag_data[$this->loop_count]['block']				= $block; // Matched data block - no tags
                $this->tag_data[$this->loop_count]['cache']				= $this->cache_status($cfile, $args); 
                $this->tag_data[$this->loop_count]['cfile']				= $cfile;
                $this->tag_data[$this->loop_count]['no_results']		= $no_results;
                $this->tag_data[$this->loop_count]['no_results_block']	= $no_results_block;
				$this->tag_data[$this->loop_count]['search_fields']		= $search_fields;
            
            } // END IF

          // Increment counter            
          $this->loop_count++;  

       } // END WHILE
    }
    /* END */


    /** -------------------------------------
    /**  Class/Method handler
    /** -------------------------------------*/

    function class_handler()
    {    
    	global $FNS, $TMPL, $DB, $PREFS;
    
        $classes = array();
        
        // Fill an array with the names of all the classes that we previously extracted from the tags
                
        for ($i = 0; $i < count($this->tag_data); $i++)
        {
            // Should we use the tag cache file?

            if ($this->tag_data[$i]['cache'] == 'CURRENT')
            {            
                // If so, replace the marker in the tag with the cache data
                
                $this->log_item("Tag Cached and Cache is Current");
                
                $this->replace_marker($i, $this->get_cache_file($this->tag_data[$i]['cfile']));
            }
            else
            {
                // Is a module or plug-in being requested?
            
                if ( ! in_array($this->tag_data[$i]['class'] , $this->modules))
                {                 
                    if ( ! in_array($this->tag_data[$i]['class'] , $this->plugins))
                    {                    
                        global $LANG, $PREFS, $OUT;
                        
                        $this->log_item("Invalid Tag");

                        if ($PREFS->ini('debug') >= 1)
                        {
                        	if ($this->tag_data[$i]['tagparts']['0'] == $this->tag_data[$i]['tagparts']['1'] &&
                        		! isset($this->tag_data[$i]['tagparts']['2']))
                        	{
                        		unset($this->tag_data[$i]['tagparts']['1']);
                        	}
                        
                            $error  = $LANG->line('error_tag_syntax');
                            $error .= '<br /><br />';
                            $error .= htmlspecialchars(LD);
                            $error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);                
                            $error .= htmlspecialchars(RD);
                            $error .= '<br /><br />';
                            $error .= $LANG->line('error_fix_syntax');
            
                            $OUT->fatal_error($error);                         
                        }
                        else
                            return false;             
                    }
                    else
                    {
                        $classes[] = 'pi.'.$this->tag_data[$i]['class'];
                        $this->log_item("Plugin Tag: ".ucfirst($this->tag_data[$i]['class']).'/'.$this->tag_data[$i]['method']);
                    }
                }
                else
                {
                    $classes[] = $this->tag_data[$i]['class'];
                    $this->log_item("Module Tag: ".ucfirst($this->tag_data[$i]['class']).'/'.$this->tag_data[$i]['method']);
                }
            }
        }

        // Remove duplicate class names and re-order the array
        
        $classes = array_values(array_unique($classes));
        
        // Dynamically require the file that contains each class
        
        $this->log_item("Including Files for Tag and Modules");
        
        for ($i = 0; $i < count($classes); $i++)
        {
            // But before we do, make sure it hasn't already been included...
            
			if ( ! class_exists($classes[$i]))
			{
                if (substr($classes[$i], 0, 3) == 'pi.')
                {
                    require_once PATH_PI.$classes[$i].EXT;                 
                }
                else
                {
                    require_once PATH_MOD.$classes[$i].'/mod.'.$classes[$i].EXT;
                }
			}
        }
        
        /** -----------------------------------
        /**  Only Retrieve Data if Not Done Before and Modules Being Called
        /** -----------------------------------*/
        
        if (sizeof($this->module_data) == 0 && sizeof(array_intersect($this->modules, $classes)) > 0)
        {
        	$query = $DB->query("SELECT module_version, module_name FROM exp_modules");
        	
        	foreach($query->result as $row)
        	{
        		$this->module_data[$row['module_name']] = array('version' => $row['module_version']);
        	}
        }
                
        // Final data processing

        // Loop through the master array containing our extracted template data
        
        $this->log_item("Beginning Final Tag Data Processing");
        
        reset($this->tag_data);
        
        for ($i = 0; $i < count($this->tag_data); $i++)
        { 
            if ($this->tag_data[$i]['cache'] != 'CURRENT')
            {
            	$this->log_item("Calling Class/Method: ".ucfirst($this->tag_data[$i]['class'])."/".$this->tag_data[$i]['method']);
            
            	/* ---------------------------------
				/*  Plugin as Parameter
				/*
				/*  - Example: weblog="{exp:some_plugin}"
				/*  - A bit of a hidden feature.  Has been tested but not quite
				/*  ready to say it is ready for prime time as I might want to 
				/*  move it to earlier in processing so that if there are 
				/*  multiple plugins being used as parameters it is only called
				/*  once instead of for every single parameter. - Paul
				/* ---------------------------------*/
				
				if (substr_count($this->tag_data[$i]['tag'], LD.'exp') > 1 && isset($this->tag_data[$i]['params']['parse']) && $this->tag_data[$i]['params']['parse'] == 'inward')
				{
					foreach($this->tag_data[$i]['params'] as $name => $param)
					{
						if (stristr($this->tag_data[$i]['params'][$name], LD.'exp'))
						{
							$this->log_item("Plugin in Parameter, Processing Plugin First");
						
							$TMPL2 = $FNS->clone_object($this);
							
							while (is_int(strpos($TMPL2->tag_data[$i]['params'][$name], LD.'exp:')))
							{
								$TMPL = new Template();
								$TMPL->start_microtime = $this->start_microtime;
								$TMPL->template = $TMPL2->tag_data[$i]['params'][$name];
								$TMPL->tag_data	= array();
								$TMPL->var_single = array();
								$TMPL->var_cond	= array();
								$TMPL->var_pair	= array();
								$TMPL->plugins = $TMPL2->plugins;
								$TMPL->modules = $TMPL2->modules;
								$TMPL->parse_template();
								$TMPL->class_handler();
								$TMPL->loop_count = 0;
								$TMPL2->tag_data[$i]['params'][$name] = $TMPL->template;
								$TMPL2->log = array_merge($TMPL2->log, $TMPL->log);
							}
							
							foreach (get_object_vars($TMPL2) as $key => $value)
							{
								$this->$key = $value;
							}
							
							unset($TMPL2);
						
							$TMPL = $this;
						}
					}
				}
            
				/** ---------------------------------
				/**  Nested Plugins...
				/** ---------------------------------*/
				
				if (in_array($this->tag_data[$i]['class'] , $this->plugins) && strpos($this->tag_data[$i]['block'], LD.'exp:') !== false)
				{
					if ( ! isset($this->tag_data[$i]['params']['parse']) OR $this->tag_data[$i]['params']['parse'] != 'inward')
					{
						$this->log_item("Nested Plugins in Tag, Parsing Outward First");
						
						$TMPL2 = $FNS->clone_object($this);
					
						while (is_int(strpos($TMPL2->tag_data[$i]['block'], LD.'exp:')))
						{
							$TMPL = new Template();
							$TMPL->start_microtime = $this->start_microtime;
							$TMPL->template = $TMPL2->tag_data[$i]['block'];
							$TMPL->tag_data	= array();
							$TMPL->var_single = array();
							$TMPL->var_cond	= array();
							$TMPL->var_pair	= array();
							$TMPL->plugins = $TMPL2->plugins;
							$TMPL->modules = $TMPL2->modules;
							$TMPL->parse_template();
							$TMPL->class_handler();
							$TMPL->loop_count = 0;
							$TMPL2->tag_data[$i]['block'] = $TMPL->template;
							$TMPL2->log = array_merge($TMPL2->log, $TMPL->log);
						}
						
						foreach (get_object_vars($TMPL2) as $key => $value)
						{
  							$this->$key = $value;
						}
						
						unset($TMPL2);
					
						$TMPL = $this;
					}
				}
				
                // Assign the data chunk, parameters
                
                // We moved the no_results_block here because of nested tags. The first 
                // parsed tag has priority for that conditional.
                $this->tagdata   		= str_replace($this->tag_data[$i]['no_results_block'], '', $this->tag_data[$i]['block']);
                $this->tagparams 		= $this->tag_data[$i]['params']; 
                $this->tagchunk  		= $this->tag_data[$i]['chunk'];
                $this->tagproper		= $this->tag_data[$i]['tag'];
                $this->tagparts			= $this->tag_data[$i]['tagparts'];
                $this->no_results		= $this->tag_data[$i]['no_results'];
				$this->search_fields	= $this->tag_data[$i]['search_fields'];
                
				/** -------------------------------------
				/**  Assign Sites for Tag
				/** -------------------------------------*/
                
				$this->_fetch_site_ids();
                
				/** -------------------------------------
				/**  Relationship Data Pulled Out
				/** -------------------------------------*/
				
				// If the weblog:entries tag or search:search_results is being called
                // we need to extract any relationship data that might be present.
                // Note: This needs to happen before extracting the variables
                // in the tag so it doesn't get confused as to which entry the
                // variables belong to.
                
                if (($this->tag_data[$i]['class'] == 'weblog' AND $this->tag_data[$i]['method'] == 'entries')
					OR ($this->tag_data[$i]['class'] == 'search' AND $this->tag_data[$i]['method'] == 'search_results'))
                {                
                	$this->tagdata = $this->assign_relationship_data($this->tagdata);
                }         

                // Fetch the variables for this particular tag
                                                              
                $vars = $FNS->assign_variables($this->tag_data[$i]['block']);
                
                if (count($this->related_markers) > 0)
                {
                	foreach ($this->related_markers as $mkr)
                	{
                		if ( ! isset($vars['var_single'][$mkr]))
                		{
                			$vars['var_single'][$mkr] = $mkr;
                		}
                	}

                	$this->related_markers = array();
                }

                $this->var_single	= $vars['var_single'];
                $this->var_pair		= $vars['var_pair'];

                //  Redundant see above loop for related_markers - R.S.
                //if ($this->related_id != '')
                //{
                //	$this->var_single[$this->related_id] = $this->related_id;
                //	$this->related_id = '';
                //}
                
				//  Assign Conditional Variables
				
				if ( ! in_array($this->tag_data[$i]['class'],$this->native_modules))
				{
					$this->var_cond = $FNS->assign_conditional_variables($this->tag_data[$i]['block'], SLASH, LD, RD);
                }
                
                // Assign the class name and method name
            
                $class_name = ucfirst($this->tag_data[$i]['class']);
                
                if ($class_name == 'Commerce')
                {
                	// The Commerce module is special in that it has its own modules and its 
                	// constructor handles everything for us
                	$meth_name = 'commerce';
                }
                else
                {
                	$meth_name  = $this->tag_data[$i]['method'];
                }
                
                // Dynamically instantiate the class.
                // If module, only if it is installed...
                
                if (in_array($this->tag_data[$i]['class'], $this->modules) && ! isset($this->module_data[$class_name]))
                {
                	$this->log_item("Problem Processing Module: Module Not Installed");
                }
                else
                {
                	$this->log_item(" -> Class Called: ".$class_name);
                	
                	$EE = new $class_name();
                }
                
                /** ----------------------------------
				/**  Does method exist?  Is This A Module and Is It Installed?
				/** ----------------------------------*/
        
                if ((in_array($this->tag_data[$i]['class'], $this->modules) && ! isset($this->module_data[$class_name])) OR ! method_exists($EE, $meth_name))
                {
                    global $LANG, $PREFS, $OUT;
                    
                    $this->log_item("Tag Not Processed: Method Inexistent or Module Not Installed");

                    if ($PREFS->ini('debug') >= 1)
                    {                        
                        if ($this->tag_data[$i]['tagparts']['0'] == $this->tag_data[$i]['tagparts']['1'] &&
                        	! isset($this->tag_data[$i]['tagparts']['2']))
                        {
                        	unset($this->tag_data[$i]['tagparts']['1']);
                        }
                        
                        $error  = $LANG->line('error_tag_module_processing');
                        $error .= '<br /><br />';
                        $error .= htmlspecialchars(LD);
                        $error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);                 
                        $error .= htmlspecialchars(RD);
                        $error .= '<br /><br />';
                        $error .= str_replace('%x', $this->tag_data[$i]['class'], str_replace('%y', $meth_name, $LANG->line('error_fix_module_processing')));
                        
                        $OUT->fatal_error($error);
                     }
                     else
                        return;
                }    
                
                /*
                
                OK, lets grab the data returned from the class.
                
                First, however, lets determine if the tag has one or two segments.  
                If it only has one, we don't want to call the constructor again since
                it was already called during instantiation.
         
                Note: If it only has one segment, only the object constructor will be called.
                Since constructors can't return a value just by initialializing the object
                the output of the class must be assigned to a variable called $this->return_data
                               
                */
                
                $this->log_item(" -> Method Called: ".$meth_name);
                
                if (strtolower($class_name) == $meth_name)
                {
                    $return_data = (isset($EE->return_data)) ? $EE->return_data : '';
                }
                else
                {
                    $return_data = $EE->$meth_name();
                }
                
                /** ----------------------------------
                /**  404 Page Triggered, Cease All Processing of Tags From Now On
                /** ----------------------------------*/
                
                if ($this->cease_processing === TRUE)
                {
                	return;
                }
                
                $this->log_item(" -> Data Returned");
              
               // Write cache file if needed
               
                if ($this->tag_data[$i]['cache'] == 'EXPIRED')
                {
                    $this->write_cache_file($this->tag_data[$i]['cfile'], $return_data);
                }
                
                // Replace the temporary markers we added earlier with the fully parsed data
              
                $this->replace_marker($i, $return_data);
                
                // Initialize data in case there are susequent loops                
                
                $this->var_single = array();
                $this->var_cond   = array();
                $this->var_pair   = array();
                
                unset($return_data);
                unset($class_name);    
                unset($meth_name);    
                unset($EE);
            }
        }
    }
    /* END */

    /** -------------------------------------
    /**  Assign the related data 
    /** -------------------------------------*/
	
	// Weblog entries can have related entries embedded within them.
	// We'll extract the related tag data, stash it away in an array, and
	// replace it with a marker string so that the template parser
	// doesn't see it.  In the weblog class we'll check to see if the 
	// $TMPL->related_data array contains anything.  If so, we'll celebrate
	// wildly.

	function assign_relationship_data($chunk)
	{
		global $FNS;
		
		$this->related_markers = array();
		
   		if (preg_match_all("/".LD."related_entries\s+id\s*=\s*[\"\'](.+?)[\"\']".RD."(.+?)".LD.SLASH."related_entries".RD."/is", $chunk, $matches))
		{  		
			$this->log_item("Assigning Related Entry Data");
			
			$no_rel_content = '';
			
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$rand = $FNS->random('alpha', 8);
				$marker = LD.'REL['.$matches['1'][$j].']'.$rand.'REL'.RD;
				
				if (preg_match("/".LD."if no_related_entries".RD."(.*?)".LD.SLASH."if".RD."/s", $matches['2'][$j], $no_rel_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rel_match['1'], LD.'if'))
					{
						$match['0'] = $FNS->full_tag($no_rel_match['0'], $matches['2'][$j], LD.'if', LD.SLASH."if".RD);
					}
					
					$no_rel_content = substr($no_rel_match['0'], strlen(LD."if no_related_entries".RD), -strlen(LD.SLASH."if".RD));
				}
				
				$this->related_markers[] = $matches['1'][$j];
				$vars = $FNS->assign_variables($matches['2'][$j]);
				
				// Depreciated as redundant.  R.S.
				//$this->related_id = $matches['1'][$j];
				
				$this->related_data[$rand] = array(
											'marker'			=> $rand,
											'field_name'		=> $matches['1'][$j],
											'tagdata'			=> $matches['2'][$j],
											'var_single'		=> $vars['var_single'],
											'var_pair' 			=> $vars['var_pair'],
											'var_cond'			=> $FNS->assign_conditional_variables($matches['2'][$j], SLASH, LD, RD),
											'no_rel_content'	=> $no_rel_content
										);
										
				$chunk = str_replace($matches['0'][$j], $marker, $chunk);					
			}
		}

		if (preg_match_all("/".LD."reverse_related_entries\s*(.*?)".RD."(.+?)".LD.SLASH."reverse_related_entries".RD."/is", $chunk, $matches))
		{  		
			$this->log_item("Assigning Reverse Related Entry Data");
		
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$rand = $FNS->random('alpha', 8);
				$marker = LD.'REV_REL['.$rand.']REV_REL'.RD;
				$vars = $FNS->assign_variables($matches['2'][$j]);
				
				$no_rev_content = '';

				if (preg_match("/".LD."if no_reverse_related_entries".RD."(.*?)".LD.SLASH."if".RD."/s", $matches['2'][$j], $no_rev_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rev_match['1'], LD.'if'))
					{
						$match['0'] = $FNS->full_tag($no_rev_match['0'], $matches['2'][$j], LD.'if', LD.SLASH."if".RD);
					}
					
					$no_rev_content = substr($no_rev_match['0'], strlen(LD."if no_reverse_related_entries".RD), -strlen(LD.SLASH."if".RD));
				}
				
				$this->reverse_related_data[$rand] = array(
															'marker'			=> $rand,
															'tagdata'			=> $matches['2'][$j],
															'var_single'		=> $vars['var_single'],
															'var_pair' 			=> $vars['var_pair'],
															'var_cond'			=> $FNS->assign_conditional_variables($matches['2'][$j], SLASH, LD, RD),
															'params'			=> $FNS->assign_parameters($matches['1'][$j]),
															'no_rev_content'	=> $no_rev_content
														);
										
				$chunk = str_replace($matches['0'][$j], $marker, $chunk);					
			}
		}
	
		return $chunk;
	}
	/* END */


    /** -------------------------------------
    /**  Assign class and method name
    /** -------------------------------------*/

    function assign_class($tag)
    {
        $result = array();
    
        // Grab the class name and method names contained 
        // in the tag and assign them to variables.
        
		$result = explode(':', $tag);
        
        // Tags can either have one segment or two:
        // {exp:first_segment}
        // {exp:first_segment:second_segment}
        //
        // These two segments represent either a "class:constructor"
        // or a "class:method".  We need to determine which one it is.
        
        if (count($result) == 1)
        {                
            $result['0'] = trim($result['0']);
            $result['1'] = trim($result['0']);
        }
        else
        {                
            foreach($result as $key => $value)
            {
            	$result[$key] = trim($result[$key]);
            }
        }
        
        return $result;
    }
    /* END */


    /** ---------------------------------------
    /**  Fetch a specific parameter
    /** ---------------------------------------*/
        
    function fetch_param($which)
    {
        return ( ! isset($this->tagparams[$which])) ? FALSE : $this->tagparams[$which];
    }
    /* END */


    /** ---------------------------------------
    /**  Swap single variables with final value
    /** ---------------------------------------*/

    function swap_var_single($search, $replace, $source)
    {
        return str_replace(LD.$search.RD, $replace, $source);  
    }
    /* END */


    /** ---------------------------------------
    /**  Swap variable pairs with final value
    /** ---------------------------------------*/

    function swap_var_pairs($open, $close, $source)
    {
        return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.SLASH.$close.RD."/s", "\\1", $source); 
    }
    /* END */


    /** ---------------------------------------
    /**  Delete variable pairs
    /** ---------------------------------------*/

    function delete_var_pairs($open, $close, $source)
    {
        return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.SLASH.$close.RD."/s", "", $source); 
    }
    /* END */



    /** ---------------------------------------
    /**  Swap conditional variables
    /** ---------------------------------------*/

    function swap_conditional($search, $replace, $source)
    {
        return str_replace($search, $replace, $source); 
    }
    /* END */


    /** -----------------------------------------
    /**  Fetch the data in-between two variables
    /** -----------------------------------------*/

    function fetch_data_between_var_pairs($str, $variable)
    {
        if ($str == '' || $variable == '')
            return;
        
        if ( ! preg_match("/".LD.$variable.".*?".RD."(.*?)".LD.SLASH.$variable.RD."/s", $str, $match))
               return;
 
        return $match['1'];        
    }
    /* END */


    /** -------------------------------------
    /**  Parse PHP in template
    /** -------------------------------------*/

     function parse_template_php($str)
     {
     	global $FNS;
     	
		ob_start();
		
		echo $FNS->evaluate($str);
		
		$str = ob_get_contents();
		
		ob_end_clean(); 
		
		$this->parse_php = FALSE;
		
		return $str;
     }
     /* END */
     

    /** ---------------------------------------
    /**  Replace marker with final data
    /** ---------------------------------------*/

    function replace_marker($i, $return_data)
    {       
        $this->template = str_replace('M'.$i.$this->marker, $return_data, $this->template);
    }
    /* END */


    /** -----------------------------------------
    /**  Set caching status
    /** -----------------------------------------*/

    function cache_status($cfile, $args, $cache_type = 'tag')
    {
        // Three caching states:  

        // NO_CACHE = do not cache 
        // EXPIRED  = cache file has expired
        // CURRENT  = cache file has not expired
                        
        if ( ! isset($args['cache']))
            return 'NO_CACHE';
            
        if ($args['cache'] != 'yes')
            return 'NO_CACHE';

        $cache_dir = ($cache_type == 'tag') ? PATH_CACHE.$this->t_cache_path : $cache_dir = PATH_CACHE.$this->p_cache_path;

        $cache_file = $cache_dir.'t_'.$cfile;
        
        if ( ! file_exists($cache_file))
            return 'EXPIRED';
        
        if ( ! $fp = @fopen($cache_file, 'rb'))
            return 'EXPIRED';
            
            flock($fp, LOCK_SH);
            
			$timestamp = trim(@fread($fp, filesize($cache_file)));
			            
            flock($fp, LOCK_UN);
            
            fclose($fp);
            
            $refresh = ( ! isset($args['refresh'])) ? 0 : $args['refresh']; 
                    
        if (time() > ($timestamp + ($refresh * 60)))
        {
            return 'EXPIRED';   
        }
        else
        {
            if ( ! file_exists($cache_dir.'c_'.$cfile))
            {
                return 'EXPIRED';
            }
            
            $this->cache_timestamp = $timestamp;
            
            return 'CURRENT';
        } 
    }
    /* END */



    /** -----------------------------------------
    /**  Get cache file
    /** -----------------------------------------*/

    function get_cache_file($cfile, $cache_type = 'tag')
    {
        $cache = '';

        $cache_dir = ($cache_type == 'tag') ? PATH_CACHE.$this->t_cache_path : $cache_dir = PATH_CACHE.$this->p_cache_path;

        $fp = @fopen($cache_dir.'c_'.$cfile, 'rb');
        
        flock($fp, LOCK_SH);
                    
        $cache = @fread($fp, filesize($cache_dir.'c_'.$cfile));
                    
        flock($fp, LOCK_UN);
        
        fclose($fp);
        
        return $cache;
    }
    /* END */



    /** -----------------------------------------
    /**  Write cache file
    /** -----------------------------------------*/

    function write_cache_file($cfile, $data, $cache_type = 'tag')
    {
		global $PREFS;
		
    	if ($this->disable_caching == TRUE)
    	{
    		return;
    	}
    	
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

		if ($cache_type == 'tag' && $PREFS->ini('disable_tag_caching') == 'y')
		{
			return;
		}

        $cache_dir  = ($cache_type == 'tag') ? PATH_CACHE.$this->t_cache_path : $cache_dir = PATH_CACHE.$this->p_cache_path;
        $cache_base = ($cache_type == 'tag') ? PATH_CACHE.'tag_cache' : PATH_CACHE.'page_cache';
                
        $time_file  = $cache_dir.'t_'.$cfile;
        $cache_file = $cache_dir.'c_'.$cfile;
        
        $dirs = array($cache_base, $cache_dir);
        
        foreach ($dirs as $dir)
        {       
			if ( ! @is_dir($dir))
			{
				if ( ! @mkdir($dir, 0777))
				{
					return;
				}

				if ($dir == $cache_base && $fp = @fopen($dir.'/index.html', 'wb'))
				{
					fclose($fp);					
				}
								
				@chmod($dir, 0777);            
			}
        }
                
        // Write the timestamp file
        if ( ! $fp = @fopen($time_file, 'wb'))
            return;

        flock($fp, LOCK_EX);
        fwrite($fp, time());
        flock($fp, LOCK_UN);
        fclose($fp);
        
		@chmod($time_file, 0777); 

        // Write the data cache

        if ( ! $fp = @fopen($cache_file, 'wb'))
            return;

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);
        
		@chmod($cache_file, 0777); 
    }
    /* END */


    /** -------------------------------------
    /**  Parse Template URI Data
    /** -------------------------------------*/

    function parse_template_uri()
    {
        global $PREFS, $LANG, $OUT, $DB, $LOC, $IN, $REGX;
        
        $this->log_item("Parsing Template URI");
        
        // Does the first segment exist?  No?  Show the default template   
        if ($IN->fetch_uri_segment(1) === FALSE)
        {     
			return $this->fetch_template('', 'index', TRUE);
        }
        // Is only the pagination showing in the URI?
        elseif(count($IN->SEGS) == 1 && preg_match("#^(P\d+)$#", $IN->fetch_uri_segment(1), $match))
        {
        	$IN->QSTR = $match['1'];
        	return $this->fetch_template('', 'index', TRUE);
        }
        
        // Set the strict urls pref
        if ($PREFS->ini('strict_urls') !== FALSE)
        {
        	$this->strict_urls = ($PREFS->ini('strict_urls') == 'y') ? TRUE : FALSE;
        }

        // At this point we know that we have at least one segment in the URI, so  
		// let's try to determine what template group/template we should show
		
		// Is the first segment the name of a template group?
		$query = $DB->query("SELECT group_id FROM exp_template_groups WHERE group_name = '".$DB->escape_str($IN->fetch_uri_segment(1))."' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");
	
		// Template group found!
		if ($query->num_rows == 1)
		{
			// Set the name of our template group
			$template_group = $IN->fetch_uri_segment(1);

			// Set the group_id so we can use it in the next query
			$group_id = $query->row['group_id'];
		
			// Does the second segment of the URI exist? If so...
			if ($IN->fetch_uri_segment(2) !== FALSE)
			{
				// Is the second segment the name of a valid template?
				$query = $DB->query("SELECT COUNT(*) AS count FROM exp_templates WHERE group_id = '{$group_id}' AND template_name = '".$DB->escape_str($IN->fetch_uri_segment(2))."'");
			
				// We have a template name!
				if ($query->row['count'] == 1)
				{
					// Assign the template name
					$template = $IN->fetch_uri_segment(2);
					
					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					$IN->QSTR = ( ! $IN->fetch_uri_segment(3) AND $IN->fetch_uri_segment(2) != 'index') ? '' : $REGX->trim_slashes(substr($IN->URI, strlen('/'.$IN->fetch_uri_segment(1).'/'.$IN->fetch_uri_segment(2))));
				}
				else // A valid template was not found
				{				
					// Set the template to index		
					$template = 'index';
				   
					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					$IN->QSTR = ( ! $IN->fetch_uri_segment(3)) ? $IN->fetch_uri_segment(2) : $REGX->trim_slashes(substr($IN->URI, strlen('/'.$IN->fetch_uri_segment(1))));
				}
			}
			// The second segment of the URL does not exist
			else
			{
				// Set the template as "index"
				$template = 'index';
			}
		}
		// The first segment in the URL does NOT correlate to a valid template group.  Oh my!
		else 
		{
			// If we are enforcing strict URLs we need to show a 404
			if ($this->strict_urls == TRUE)
			{
				if ($PREFS->ini('site_404'))
				{
					$this->log_item("Template group and template not found, showing 404 page");
					return $this->fetch_template('', '', FALSE);
				}
				else
				{
					return $this->_404();
				}
			}
			
			// We we are not enforcing strict URLs, so Let's fetch the the name of the default template group
			$result = $DB->query("SELECT group_name, group_id FROM exp_template_groups WHERE is_site_default = 'y' AND site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'");

			// No result?  Bail out...
			// There's really nothing else to do here.  We don't have a valid template group in the URL
			// and the admin doesn't have a template group defined as the site default.
			if ($result->num_rows == 0)
			{
				// Turn off caching 
				$this->disable_caching = TRUE;

				// Show the user-specified 404
				if ($PREFS->ini('site_404'))
				{
					$this->log_item("Template group and template not found, showing 404 page");
					return $this->fetch_template('', '', FALSE);
				}
				else
				{
					// Show the default 404
					return $this->_404();
				}
			}
			
			// Since the first URI segment isn't a template group name, could it be the name of a template in the default group?			
			$query = $DB->query("SELECT COUNT(*) AS count FROM exp_templates WHERE group_id = '".$result->row['group_id']."' AND template_name = '".$DB->escape_str($IN->fetch_uri_segment(1))."'");
		
			// We found a valid template!
			if ($query->row['count'] == 1)
			{ 
				// Set the template group name from the prior query result (we use the default template group name)
				$template_group	= $result->row['group_name'];

				// Set the template name
				$template = $IN->fetch_uri_segment(1);				

				// Re-assign the query string variable in the Input class so the various tags can show the correct data
				if ($IN->fetch_uri_segment(2))
				{
					$IN->QSTR = $REGX->trim_slashes(substr($IN->URI, strlen('/'.$IN->fetch_uri_segment(1))));
				}			
			}
			// A valid template was not found.  At this point we do not have either a valid template group or a valid template name in the URL
			else
			{
				// Turn off caching 
				$this->disable_caching = TRUE;

				// is 404 preference set, we wet our group/template names as blank.
				// The fetch_template() function below will fetch the 404 and show it
				if ($PREFS->ini('site_404'))
				{
					$template_group = '';
					$template = '';
					$this->log_item("Template group and template not found, showing 404 page");
				}
				else
				// No 404 preference is set so we will show the index template from the default template group
				{
					$IN->QSTR = $REGX->trim_slashes($IN->URI);
					$template_group	= $result->row['group_name'];
					$template = 'index';
					$this->log_item("Showing index. Template not found: ".$IN->fetch_uri_segment(1));
				}
			}		
		}

		// Fetch the template!
       return $this->fetch_template($template_group, $template, FALSE);
    }
   // END

    /** -----------------------------------------
    /**  404 page
    /** -----------------------------------------*/

	function _404()
	{
		global $OUT;
		$this->log_item("404 Page Returned");
		$OUT->http_status_header(404);
		echo '<html><head><title>404 Page Not Found</title></head><body><h1>Status: 404 Page Not Found</h1></body></html>';
		exit;	
	}


    /** -----------------------------------------
    /**  Fetch the requested template
    /** -----------------------------------------*/

    function fetch_template($template_group, $template, $show_default = TRUE, $site_id = '')
    {
        global $PREFS, $LANG, $OUT, $DB, $IN, $SESS, $FNS, $LOC;
        
        if ($site_id == '' OR ! is_numeric($site_id))
        {
        	$site_id = $PREFS->ini('site_id');
        }
        
        $this->log_item("Retrieving Template from Database: ".$template_group.'/'.$template);
         
        $sql_404 = '';
        
		/** ---------------------------------------
		/**  Is this template supposed to be "hidden"?
		/** ---------------------------------------*/
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- hidden_template_indicator => '.' 
			The character(s) used to designate a template as "hidden"
		/* -------------------------------------------*/
	
		$hidden_indicator = ($PREFS->ini('hidden_template_indicator') === FALSE) ? '.' : $PREFS->ini('hidden_template_indicator');			
		
		if ($this->depth == 0 AND substr($template, 0, 1) == $hidden_indicator)
		{			
			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- hidden_template_404 => y/n 
				If a hidden template is encountered, the default behavior is
				to throw a 404.  With this set to 'n', the template group's
				index page will be shown instead
			/* -------------------------------------------*/
			
			if ($PREFS->ini('hidden_template_404') !== 'n')
			{				
				$x = explode("/", $PREFS->ini('site_404'));
				
				if (isset($x['0']) AND isset($x['1']))
				{
					$OUT->out_type = '404';
					$this->template_type = '404';
					
					$sql_404 = " AND exp_template_groups.group_name='".$DB->escape_str($x['0'])."' AND exp_templates.template_name='".$DB->escape_str($x['1'])."'";
				}
				else
				{
					$template = 'index';
				}
			}
			else
			{
				$template = 'index';
			}
		}
		
        if ($template_group == '' && $show_default == FALSE && USER_BLOG === FALSE && $PREFS->ini('site_404') != '')
        {
			$treq = $PREFS->ini('site_404');
			
			$x = explode("/", $treq);

			if (isset($x['0']) AND isset($x['1']))
			{
				$OUT->out_type = '404';
				$this->template_type = '404';
				
				$sql_404 = " AND exp_template_groups.group_name='".$DB->escape_str($x['0'])."' AND exp_templates.template_name='".$DB->escape_str($x['1'])."'";
			}	
        }
         
        $sql = "SELECT exp_templates.template_name, 
                       exp_templates.template_id, 
                       exp_templates.template_data, 
                       exp_templates.template_type,
                       exp_templates.edit_date,
                       exp_templates.save_template_file,
                       exp_templates.cache, 
                       exp_templates.refresh, 
                       exp_templates.no_auth_bounce, 
                       exp_templates.enable_http_auth,
                       exp_templates.allow_php, 
                       exp_templates.php_parse_location,
                       exp_templates.hits,
                       exp_template_groups.group_name
                FROM   exp_template_groups, exp_templates
                WHERE  exp_template_groups.group_id = exp_templates.group_id
                AND    exp_template_groups.site_id = '".$DB->escape_str($site_id)."' ";
                
		if ($sql_404 != '')
        {
			$sql .= $sql_404;
		}
		else
		{
			if ($template != '')
				$sql .= " AND exp_templates.template_name = '".$DB->escape_str($template)."' ";
		
			if ($show_default == TRUE && USER_BLOG === FALSE)
			{
				$sql .= "AND exp_template_groups.is_site_default = 'y'";				
			}
			else
			{
				$sql .= "AND exp_template_groups.group_name = '".$DB->escape_str($template_group)."'";
			}
        }
                
        $query = $DB->query($sql);

        if ($query->num_rows == 0)
        {
        	$this->log_item("Template Not Found");
            return FALSE;
        }
        
        $this->log_item("Template Found");
		
		/** ----------------------------------------------------
        /**  HTTP Authentication
        /** ----------------------------------------------------*/
		
		if ($query->row['enable_http_auth'] == 'y')
        {
        	$this->log_item("HTTP Authentication in Progress");
        
        	$results = $DB->query("SELECT member_group 
        						  FROM exp_template_no_access 
        						  WHERE template_id = '".$DB->escape_str($query->row['template_id'])."'");
        						  
        	$not_allowed_groups = array('2', '3', '4');
        	
        	if ($results->num_rows > 0)
        	{
        		foreach($results->result as $row)
        		{
        			$not_allowed_groups[] = $row['member_group'];
        		}
        	}		  
        
        	if ($this->template_authentication_check_basic($not_allowed_groups) !== TRUE)
        	{
        	    $this->template_authentication_basic();
        	}
        }


        /** ----------------------------------------------------
        /**  Is the current user allowed to view this template?
        /** ----------------------------------------------------*/
        
        if ($query->row['enable_http_auth'] != 'y' && $query->row['no_auth_bounce'] != '')
        {
        	$this->log_item("Determining Template Access Privileges");
        
            $result = $DB->query("SELECT count(*) AS count FROM exp_template_no_access WHERE template_id = '".$DB->escape_str($query->row['template_id'])."' AND member_group = '".$DB->escape_str($SESS->userdata['group_id'])."'");
            
            if ($result->row['count'] > 0)
            { 
            	if ($this->depth > 0)
            	{
            		return '';
            	}
            
                $sql = "SELECT	a.template_id, a.template_data, a.template_name, a.template_type, a.edit_date, a.save_template_file, a.cache, a.refresh, a.hits, a.allow_php, a.php_parse_location, b.group_name
                        FROM	exp_templates a, exp_template_groups b
                        WHERE	a.group_id = b.group_id
                        AND		template_id = '".$DB->escape_str($query->row['no_auth_bounce'])."'";
        
                $query = $DB->query($sql);
            }
        }
        
        if ($query->num_rows == 0)
        {
            return false;
        }
        
        /** -----------------------------------------
        /**  Is PHP allowed in this template?
        /** -----------------------------------------*/
        
		if ($query->row['allow_php'] == 'y' AND $PREFS->ini('demo_date') == FALSE)
		{
			$this->parse_php = TRUE;
			
			$this->php_parse_location = ($query->row['php_parse_location'] == 'i') ? 'input' : 'output';
		}
		
        /** -----------------------------------------
        /**  Increment hit counter
        /** -----------------------------------------*/

        if (($this->hit_lock == FALSE OR $this->hit_lock_override == TRUE) AND $PREFS->ini('enable_hit_tracking') != 'n')
        {
            $this->template_hits = $query->row['hits'] + 1;
            $this->hit_lock = TRUE;
            
            $DB->query("UPDATE exp_templates SET hits = '".$this->template_hits."' WHERE template_id = '".$DB->escape_str($query->row['template_id'])."'");
        }
        
        /** -----------------------------------------
        /**  Set template edit date
        /** -----------------------------------------*/

		$this->template_edit_date = $query->row['edit_date'];

        /** -----------------------------------------
        /**  Set template type for our page headers
        /** -----------------------------------------*/

        if ($this->template_type == '')
        { 
            $this->template_type = $query->row['template_type'];
            $FNS->template_type = $query->row['template_type'];
            
            /** -----------------------------------------
			/**  If JS or CSS request, reset Tracker Cookie
			/** -----------------------------------------*/
            
            if ($this->template_type == 'js' OR $this->template_type == 'css')
            {
            	if (sizeof($SESS->tracker) <= 1)
            	{
            		$SESS->tracker = array();
            	}
            	else
            	{
            		$removed = array_shift($SESS->tracker);
            	}
            	
            	$FNS->set_cookie('tracker', serialize($SESS->tracker), '0'); 
            }
        }
        
        if ($this->depth > 0)
        {
        	$this->embed_type = $query->row['template_type'];
        }
        
        /** -----------------------------------------
        /**  Cache Override
        /** -----------------------------------------*/
        
        // We can manually set certian things not to be cached, like the
        // search template and the member directory after it's updated
        
     	// Note: I think search caching is OK.
		// $cache_override = array('member' => 'U', 'search' => FALSE);
		
		$cache_override = array('member');

        foreach ($cache_override as $val)
        {
			if (preg_match("#^/".preg_quote($val, '#')."/#", $IN->URI))
			{
				$query->row['cache'] = 'n';
			}
        }
        
        /** -----------------------------------------
        /**  Retreive cache
        /** -----------------------------------------*/
                      
		$this->cache_hash = md5($site_id.'-'.$template_group.'-'.$template);

        if ($query->row['cache'] == 'y')
        {
            $this->cache_status = $this->cache_status($this->cache_hash, array('cache' => 'yes', 'refresh' => $query->row['refresh']), 'template');
         
            if ($this->cache_status == 'CURRENT')
            {
                return $this->convert_xml_declaration($this->get_cache_file($this->cache_hash, 'template'));                
            }            
        }
        
		/** -----------------------------------------
        /**  Retreive template file if necessary
        /** -----------------------------------------*/
        
        if ($query->row['save_template_file'] == 'y')
        {
        	$site_switch = FALSE;
        	
        	if ($PREFS->ini('site_id') != $site_id)
        	{
        		$site_switch = $PREFS->core_ini;
        		
        		if (isset($this->site_prefs_cache[$site_id]))
        		{
        			$PREFS->core_ini = $this->site_prefs_cache[$site_id];
        		}
        		else
        		{
        			$PREFS->site_prefs('', $site_id);
					$this->site_prefs_cache[$site_id] = $PREFS->core_ini;
        		}
        	}

        	if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        	{
				$this->log_item("Retrieving Template from File");
				
				$basepath = rtrim($PREFS->ini('tmpl_file_basepath'), '/').'/';
										
				$basepath .= $query->row['group_name'].'/'.$query->row['template_name'].'.php';
				
				if ($fp = @fopen($basepath, 'rb'))
				{
					flock($fp, LOCK_SH);
					
					$query->row['template_data'] = (filesize($basepath) == 0) ? '' : fread($fp, filesize($basepath)); 
					
					flock($fp, LOCK_UN);
					fclose($fp); 
				}
			}
			
			if ($site_switch !== FALSE)
			{
				$PREFS->core_ini = $site_switch;
			}
        }
        
		// standardize newlines
		$query->row['template_data'] = preg_replace("/(\015\012)|(\015)|(\012)/", "\n", $query->row['template_data']); 

        return $this->convert_xml_declaration($this->remove_ee_comments($query->row['template_data']));
    }
    /* END */


	/** -------------------------------------
	/**  "no results" tag
	/** -------------------------------------*/
	
	function no_results()
	{
		global $FNS, $PREFS, $OUT;
	
        if ( ! preg_match("/".LD."redirect\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $this->no_results, $match))
        {
			$this->log_item("Returning No Results Content");
			return $this->no_results;
        }
        else
        {
			$this->log_item("Processing No Results Redirect");
			
			if ($match['2'] == "404")
			{
				$template = explode('/', $PREFS->ini('site_404'));
				
				if (isset($template['1']))
				{
					$this->log_item('Processing "'.$template['0'].'/'.$template['1'].'" Template as 404 Page');
					$OUT->out_type = "404";
					$this->template_type = "404";
					$this->process_template($template['0'], $template['1']);
					$this->cease_processing = TRUE;
				}
				else
				{
					$this->log_item('404 redirect requested, but no 404 page is specified in the Global Template Preferences');
					return $this->no_results;
				}
			}
			else
			{
				return $FNS->redirect($FNS->create_url($FNS->extract_path("=".str_replace("&#47;", "/", $match['2']))));
			}
        }
	}
	/* END */


	/** -------------------------------------
	/**  Convert XML declaration in RSS page
	/** -------------------------------------*/
	
	// This fixes a parsing error when PHP is used in RSS templates

	function convert_xml_declaration($str)
	{		
		return preg_replace("/\<\?xml(.+?)\?\>/", "<XXML\\1/XXML>", $str);
	}
	/* END */
	
	/** -------------------------------------
	/**  Restore XML declaration in RSS page
	/** -------------------------------------*/
	
	function restore_xml_declaration($str)
	{		
		return preg_replace("/\<XXML(.+?)\/XXML\>/", "<?xml\\1?>", $str); // <?
	}
	/* END */
	
	/** -------------------------------------
	/**  Remove EE Template Comments
	/** -------------------------------------*/

	function remove_ee_comments($str)
	{	
		return preg_replace("/\{!--.*?--\}/s", '', $str);
	}
	/* END */
	

    /** -------------------------------------
    /**  Fetch installed modules
    /** -------------------------------------*/
    
    function fetch_modules()
    {   
        if (sizeof($this->modules) == 0 && $fp = @opendir(PATH_MOD)) 
        { 
            while (false !== ($file = readdir($fp))) 
            {
            	if ( is_dir(PATH_MOD.$file) && ! preg_match("/[^a-z\_0-9]/", $file))
				{
					$this->modules[] = $file;
                }
            } 
			closedir($fp); 
        } 
    }
    /* END */



    /** -------------------------------------
    /**  Fetch installed plugins
    /** -------------------------------------*/
    
    function fetch_plugins()
    {     
        if ($fp = @opendir(PATH_PI)) 
        { 
            while (false !== ($file = readdir($fp))) 
            {
            	if ( preg_match("/pi\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
				{                            
					$this->plugins[] = substr($file, 3, - strlen(EXT));
				}
            } 
            
			closedir($fp); 
        } 
    }
    /* END */



    /** ------------------------------------------------
    /**  Parse global variables and non-cachable stuff
    /** ------------------------------------------------*/

    // The syntax is generally: {global:variable_name}
    
    function parse_globals($str)
    {    
        global $LANG, $PREFS, $FNS, $IN, $DB, $LOC, $SESS;
        
        $charset 	= '';
        $lang		= '';
		$user_vars	= array('member_id', 'group_id', 'group_description', 'group_title', 'member_group', 'username', 'screen_name', 'email', 'ip_address', 'location', 'total_entries', 'total_comments', 'private_messages', 'total_forum_posts', 'total_forum_topics', 'total_forum_replies');            
        
        /** --------------------------------------------------
        /**  Redirect - if we have one of these, no need to go further
        /** --------------------------------------------------*/
     	
		if (strpos($str, LD.'redirect') !== FALSE)
		{
			if (preg_match("/".LD."redirect\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $str, $match))
			{
				if ($match['2'] == "404")
				{
					$template = explode('/', $PREFS->ini('site_404'));

					if (isset($template['1']))
					{
						$this->log_item('Processing "'.$template['0'].'/'.$template['1'].'" Template as 404 Page');
						$this->template_type = "404";
						$this->process_template($template['0'], $template['1']);
						$this->cease_processing = TRUE;
						// the resulting template will not have globals parsed unless we do this
						return $this->parse_globals($this->final_template);
					}
					else
					{
						$this->log_item('404 redirect requested, but no 404 page is specified in the Global Template Preferences');
						return $this->_404();
					}
				}
				else
				{
					// $FNS->redirect() exit;s on its own
					$FNS->redirect($FNS->create_url($FNS->extract_path("=".str_replace("&#47;", "/", $match['2']))));
				}
			}
		}
		
        /** --------------------------------------------------
        /**  Restore XML declaration if it was encoded
        /** --------------------------------------------------*/
       
        $str = $this->restore_xml_declaration($str);
        
        /** --------------------------------------------------
        /**  {hits}
        /** --------------------------------------------------*/

		$str = str_replace(LD.'hits'.RD, $this->template_hits, $str);  
        
        /** --------------------------------------------------
        /**  {ip_address} and {ip_hostname}
        /** --------------------------------------------------*/
        
		$str = str_replace(LD.'ip_address'.RD, $IN->IP, $str); 
         
		// Turns out gethostbyaddr() is WAY SLOW on many systems so I'm killing it.         
        // $str = str_replace(LD.'ip_hostname'.RD, @gethostbyaddr($IN->IP), $str); 
		
		$str = str_replace(LD.'ip_hostname'.RD, $IN->IP, $str); 
                                
        /** --------------------------------------------------
        /**  {homepage}
        /** --------------------------------------------------*/
        
        $str = str_replace(LD.'homepage'.RD, $FNS->fetch_site_index(), $str); 
                
        /** --------------------------------------------------
        /**  {site_name} {site_url} {site_index}
        /** --------------------------------------------------*/
        
        $str = str_replace(LD.'site_name'.RD, stripslashes($PREFS->ini('site_name')), $str);
        $str = str_replace(LD.'site_url'.RD, stripslashes($PREFS->ini('site_url')), $str);
        $str = str_replace(LD.'site_index'.RD, stripslashes($PREFS->ini('site_index')), $str);
        $str = str_replace(LD.'webmaster_email'.RD, stripslashes($PREFS->ini('webmaster_email')), $str);

        /** --------------------------------------------------
        /**  Stylesheet variable: {stylesheet=group/template}
        /** --------------------------------------------------*/

        $qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        
        if (preg_match_all("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", $str, $css_matches))
        {
        	$css_versions = array();
        
        	if ($PREFS->ini('send_headers') == 'y')
        	{
        		$sql = "SELECT t.template_name, tg.group_name, t.edit_date FROM exp_templates t, exp_template_groups tg
        				WHERE  t.group_id = tg.group_id
        				AND    t.template_type = 'css'
        				AND    t.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
        	
        		foreach($css_matches[1] as $css_match)
        		{
        			$ex = explode('/', $css_match, 2);
        			
        			if (isset($ex[1]))
        			{
        				$css_parts[] = "(t.template_name = '".$DB->escape_str($ex[1])."' AND tg.group_name = '".$DB->escape_str($ex[0])."')";
        			}
        		}
				
				$css_query = ( ! isset($css_parts)) ? $DB->query($sql) : $DB->query($sql.' AND ('.implode(' OR ', $css_parts) .')');
        		
        		if ($css_query->num_rows > 0)
        		{
        			foreach($css_query->result as $row)
        			{
        				$css_versions[$row['group_name'].'/'.$row['template_name']] = $row['edit_date'];
        			}
        		}
        	}
        	
        	for($ci=0, $cs=sizeof($css_matches[0]); $ci < $cs; ++$ci)
        	{
        		$str = str_replace($css_matches[0][$ci], $FNS->fetch_site_index().$qs.'css='.$css_matches[1][$ci].(isset($css_versions[$css_matches[1][$ci]]) ? '.v.'.$css_versions[$css_matches[1][$ci]] : ''), $str);
        	}
        
        	unset($css_matches);
        	unset($css_versions);
        }
          
        /** --------------------------------------------------
        /**  Email encode: {encode="you@yoursite.com" title="click Me"}
        /** --------------------------------------------------*/
        
        if ($this->encode_email == TRUE)
        {
			if (preg_match_all("/".LD."encode=(.+?)".RD."/i", $str, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{	
					$str = preg_replace('/'.preg_quote($matches['0'][$j], '/').'/', $FNS->encode_email($matches['1'][$j]), $str, 1);
				}
			}  		
		}
		else
		{
			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- encode_removed_text => Text to display if there is an {encode=""} 
				tag but emails are not to be encoded
			/* -------------------------------------------*/
		
			$str = preg_replace("/".LD."\s*encode=(.+?)".RD."/", 
								($PREFS->ini('encode_removed_text') !== FALSE) ? $PREFS->ini('encode_removed_text') : '', 
								$str);
		}
		
        /** --------------------------------------------------
        /**  Path variable: {path=group/template}
        /** --------------------------------------------------*/
		
		$str = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&$FNS, 'create_url'), $str);
        
        /** --------------------------------------------------
        /**  Debug mode: {debug_mode}
        /** --------------------------------------------------*/
        
        $str = str_replace(LD.'debug_mode'.RD, ($PREFS->ini('debug') > 0) ? $LANG->line('on') : $LANG->line('off'), $str);
                
        /** --------------------------------------------------
        /**  GZip mode: {gzip_mode}
        /** --------------------------------------------------*/

        $str = str_replace(LD.'gzip_mode'.RD, ($PREFS->ini('gzip_output') == 'y') ? $LANG->line('enabled') : $LANG->line('disabled'), $str);
                
        /** --------------------------------------------------
        /**  App version: {version}
        /** --------------------------------------------------*/
        
        $str = str_replace(LD.'app_version'.RD, APP_VER, $str); 
        $str = str_replace(LD.'version'.RD, APP_VER, $str); 
         
        /** --------------------------------------------------
        /**  App version: {build}
        /** --------------------------------------------------*/
        
        $str = str_replace(LD.'app_build'.RD, APP_BUILD, $str); 
        $str = str_replace(LD.'build'.RD, APP_BUILD, $str);

        /** --------------------------------------------------
        /**  {charset} and {lang}
        /** --------------------------------------------------*/
        
		if (preg_match("/\{charset\}/i", $str) OR preg_match("/\{lang\}/i", $str))
        {
			if ( ! USER_BLOG)
			{
				$str = str_replace(LD.'charset'.RD, $PREFS->ini('charset'), $str); 
				$str = str_replace(LD.'lang'.RD, $PREFS->ini('xml_lang'), $str); 
			}
			else
			{
				$query = $DB->query("SELECT blog_lang, blog_encoding FROM exp_weblogs WHERE weblog_id = '".$DB->escape_str(UB_BLOG_ID)."'");
			
				$str = str_replace(LD.'charset'.RD, $query->row['blog_encoding'], $str); 
				$str = str_replace(LD.'lang'.RD, $query->row['blog_lang'], $str); 
			}
        }
        
        /** --------------------------------------------------
        /**  Parse User-defined Global Variables
        /** --------------------------------------------------*/
     	
		$ub_id = ( ! defined('UB_BLOG_ID')) ? 0 : UB_BLOG_ID; 
     
		$query = $DB->query("SELECT variable_name, variable_data FROM exp_global_variables 
							 WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' 
							 AND user_blog_id = '".$DB->escape_str($ub_id)."' ");
		
		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{
				$str = str_replace(LD.$row['variable_name'].RD, $row['variable_data'], $str); 
			}
		}	
		
		/** --------------------------------------------------
		/**  {member_profile_link}
		/** --------------------------------------------------*/

		if ($SESS->userdata('member_id') != 0)
		{
			$name = ($SESS->userdata['screen_name'] == '') ? $SESS->userdata['username'] : $SESS->userdata['screen_name'];
			
			$path = "<a href='".$FNS->create_url('/member/'.$SESS->userdata('member_id'))."'>".$name."</a>";
			
			$str = str_replace(LD.'member_profile_link'.RD, $path, $str);
		}
		else
		{
			$str = str_replace(LD.'member_profile_link'.RD, '', $str);
		}
		
		/** -----------------------------------
		/**  Fetch captcha
		/** -----------------------------------*/
		
		if (preg_match("/({captcha})/", $str))
		{
			$str = preg_replace("/{captcha}/", $FNS->create_captcha(), $str);
		}        
					
		/** -----------------------------------
		/**  Add security hashes to forms
		/** -----------------------------------*/
		
		// We do this here to keep the security hashes from being cached
		
		$str = $FNS->add_form_security_hash($str);
		
		/** -----------------------------------
		/**  Add Action IDs form forms and links
		/** -----------------------------------*/
		
		$str = $FNS->insert_action_ids($str);
		
		/** -----------------------------------
		/**  Parse non-cachable variables
		/** -----------------------------------*/
		
		$SESS->userdata['member_group'] = $SESS->userdata['group_id'];
	
		foreach ($user_vars as $val)
		{
			if (isset($SESS->userdata[$val]) AND ($val == 'group_description' OR strval($SESS->userdata[$val]) != ''))
			{
				$str = str_replace(LD.$val.RD, $SESS->userdata[$val], $str);                 
				$str = str_replace('{out_'.$val.'}', $SESS->userdata[$val], $str);
				$str = str_replace('{global->'.$val.'}', $SESS->userdata[$val], $str);
				$str = str_replace('{logged_in_'.$val.'}', $SESS->userdata[$val], $str);
			}
		}
		
		return $str;
    }
    /* END */
    
    
    /** -----------------------------------
	/**  Parse Forms that Cannot Be Cached
	/** -----------------------------------*/
    
    function parse_nocache($str)
    {
    	global $FNS;
    	
    	if ( ! stristr($str, '{NOCACHE'))
    	{
    		return $str;
    	}
    	
    	/** -----------------------------------
		/**  Generate Comment Form if needed
		/** -----------------------------------*/
		
		// In order for the comment form not to cache the "save info"
		// data we need to generate dynamically if necessary

		if (preg_match_all("#{NOCACHE_(\S+)_FORM=\"(.*?)\"}(.+?){/NOCACHE_FORM}#s", $str, $match))
		{
			for($i=0, $s=sizeof($match['0']); $i < $s; $i++)
			{
				$class = $FNS->filename_security(strtolower($match['1'][$i]));
		
				if ( ! class_exists($class))
				{
					require PATH_MOD.$class.'/mod.'.$class.EXT;
				}
						
				$this->tagdata = $match['3'][$i];
			
				$vars = $FNS->assign_variables($match['3'][$i], '/');			
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];
				
				$this->tagparams = $FNS->assign_parameters($match['2'][$i]);
				
				$this->var_cond = $FNS->assign_conditional_variables($match['3'][$i], '/', LD, RD);
				
				// Assign sites for the tag
				$this->_fetch_site_ids();

				if ($class == 'gallery')
					$str = str_replace($match['0'][$i], Gallery::comment_form(TRUE, $FNS->cached_captcha), $str);	
				elseif ($class == 'comment')
					$str = str_replace($match['0'][$i], Comment::form(TRUE, $FNS->cached_captcha), $str);	
				
				$str = str_replace('{PREVIEW_TEMPLATE}', $match['2'][$i], $str);	
			}
		}
		/** -----------------------------------
		/**  Generate Stand-alone Publish form
		/** -----------------------------------*/

		if (preg_match_all("#{{NOCACHE_WEBLOG_FORM(.*?)}}(.+?){{/NOCACHE_FORM}}#s", $str, $match))
		{
			for($i=0, $s=sizeof($match['0']); $i < $s; $i++)
			{
				if ( ! class_exists('Weblog'))
				{
					require PATH_MOD.'weblog/mod.weblog'.EXT;
				}
				
				$this->tagdata = $match['2'][$i];
				
				$vars = $FNS->assign_variables($match['2'][$i], '/');
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];
				
				$this->tagparams = $FNS->assign_parameters($match['1'][$i]);
				
				// Assign sites for the tag
				$this->_fetch_site_ids();

				$XX = new Weblog();
				$str = str_replace($match['0'][$i], $XX->entry_form(TRUE, $FNS->cached_captcha), $str);
				$str = str_replace('{PREVIEW_TEMPLATE}', (isset($_POST['PRV'])) ? $_POST['PRV'] : $this->fetch_param('preview'), $str);	
			}
		}
				
		/** -----------------------------------
		/**  Generate Trackback hash if needed
		/** -----------------------------------*/

		if (preg_match("#{NOCACHE_TRACKBACK_HASH}#s", $str, $match))
		{		
			if ( ! class_exists('Trackback'))
			{
				require PATH_MOD.'trackback/mod.trackback'.EXT;
			}
										
			$str = str_replace($match['0'], Trackback::url(TRUE, $FNS->random('alpha', 8)), $str);	
		}
		
		return $str;
    
    }
    /* END */



	/** -----------------------------------
	/**  Parse advanced conditionals conditionals
	/** -----------------------------------*/

	function advanced_conditionals($str)
	{
		global $SESS, $IN, $FNS, $PREFS, $LOC;
		
		if (stristr($str, LD.'if') === false)
			return $str;
			
		/* ---------------------------------
		/*	Hidden Configuration Variables
		/*  - protect_javascript => Prevents advanced conditional parser from processing anything in <script> tags
		/* ---------------------------------*/
			
		if ($PREFS->ini('protect_javascript') == 'n')
		{
			$this->protect_javascript = FALSE;
		}
		
		$user_vars	= array('member_id', 'group_id', 'group_description', 'group_title', 'username', 'screen_name', 
							'email', 'ip_address', 'location', 'total_entries', 
							'total_comments', 'private_messages', 'total_forum_posts', 'total_forum_topics', 'total_forum_replies');
		
		for($i=0,$s=sizeof($user_vars), $data = array(); $i < $s; ++$i)
		{
			$data[$user_vars[$i]] = $SESS->userdata[$user_vars[$i]];
			$data['logged_in_'.$user_vars[$i]] = $SESS->userdata[$user_vars[$i]];
		}
		
		// Define an alternate variable for {group_id} since some tags use
		// it natively, causing it to be unavailable as a global
		
		$data['member_group'] = $SESS->userdata['group_id'];
		
		// Logged in and logged out variables
		$data['logged_in'] = ($SESS->userdata['member_id'] == 0) ? 'FALSE' : 'TRUE';
		$data['logged_out'] = ($SESS->userdata['member_id'] != 0) ? 'FALSE' : 'TRUE';
		
		// current time
		$data['current_time'] = $LOC->now;
		
		/** ------------------------------------
		/**  Member Group in_group('1') function, Super Secret!  Shhhhh!
		/** ------------------------------------*/
		
		if (preg_match_all("/in_group\(([^\)]+)\)/", $str, $matches))
		{
			$groups = (is_array($SESS->userdata['group_id'])) ? $SESS->userdata['group_id'] : array($SESS->userdata['group_id']);
		
			for($i=0, $s=sizeof($matches[0]); $i < $s; ++$i)
			{
				$check = explode('|', str_replace(array('"', "'"), '', $matches[1][$i]));
				
				$str = str_replace($matches[0][$i], (sizeof(array_intersect($check, $groups)) > 0) ? 'TRUE' : 'FALSE', $str);
			}
		}
		
		/** ------------------------------------
		/**  Final Prep, Safety On
		/** ------------------------------------*/

		$str = $FNS->prep_conditionals($str, array_merge($this->segment_vars, $this->embed_vars, $this->global_vars, $data), 'y');
				
		/** ------------------------------------
		/**  Protect Already Existing Unparsed PHP
		/** ------------------------------------*/
		
		$opener = '90Parse89Me34Not18Open';
		$closer = '90Parse89Me34Not18Close';
		
		$str = str_replace(array('<?', '?'.'>'), 
    					   array($opener.'?', '?'.$closer), 
    					   $str);
    	
    	/** ------------------------------------
		/**  Protect <script> tags
		/** ------------------------------------*/
		
    	$protected = array();
    	$front_protect = '89Protect17';
    	$back_protect  = '21Me01Please47';
		
		if ($this->protect_javascript !== FALSE && 
			stristr($str, '<script') && 
			preg_match_all("/<script.*?".">.*?<\/script>/is", $str, $matches))
		{
			for($i=0, $s=sizeof($matches['0']); $i < $s; ++$i)
			{
				$protected[$front_protect.$i.$back_protect] = $matches['0'][$i];
			}
			
			$str = str_replace(array_values($protected), array_keys($protected), $str);
		}
		
		/** ------------------------------------
		/**  Convert EE Conditionals to PHP 
		/** ------------------------------------*/
		
		$str = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $str);
		$str = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $str);
		$str = $this->parse_template_php($str);
		
		/** ------------------------------------
		/**  Unprotect <script> tags
		/** ------------------------------------*/
		
		if (sizeof($protected) > 0)
		{
			$str = str_replace(array_keys($protected), array_values($protected), $str);
		}
		
		/** ------------------------------------
		/**  Unprotect Already Existing Unparsed PHP
		/** ------------------------------------*/
		
		$str = str_replace(array($opener.'?', '?'.$closer), 
    					   array('<'.'?', '?'.'>'), 
    					   $str);
		
		return $str;
	} 
	/* END */
	
	
	/** -----------------------------------
	/**  Parse segment conditionals
	/** -----------------------------------*/

	function segment_conditionals($str)
	{
		global $SESS, $IN, $FNS;
		
		if ( ! preg_match("/".LD."if\s+segment_.+".RD."/", $str))
		{
			return $str;
		}
		
		$this->var_cond = $FNS->assign_conditional_variables($str);
		
		foreach ($this->var_cond as $val)
		{
			// Make sure this is for a segment conditional
			// And that this is not an advanced conditional
			
			if ( ! preg_match('/^segment_\d+$/i', $val['3']) OR
				sizeof(preg_split("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&|\|\|)/", $val['0'])) > 2 OR
				stristr($val['2'], 'if:else') OR
				stristr($val['0'], 'if:else'))
			{
				continue;	
			}
			
			$cond = $FNS->prep_conditional($val['0']);
			
			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));
			
			if ( ! stristr($rcond, '"') && ! stristr($rcond, "'")) continue;
			
			$n = substr($val['3'], 8);
			$temp = (isset($IN->SEGS[$n])) ? $IN->SEGS[$n] : '';

			$lcond = str_replace($val['3'], "\$temp", $lcond);
			
			if (stristr($rcond, '\|') !== FALSE OR stristr($rcond, '&') !== FALSE)
			{
				$rcond	  = trim($rcond);
				$operator = trim(substr($rcond, 0, strpos($rcond, ' ')));
				$check	  = trim(substr($rcond, strpos($rcond, ' ')));
			
				$quote = substr($check, 0, 1);
				
				if (stristr($rcond, '\|') !== FALSE)
				{
					$array =  explode('\|', str_replace($quote, '', $check));
					$break_operator = ' || ';
				}
				else
				{
					$array =  explode('&', str_replace($quote, '', $check));
					$break_operator = ' && ';
				}
				
				$rcond  = $operator.' '.$quote;
				
				$rcond .= implode($quote.$break_operator.$lcond.' '.$operator.' '.$quote, $array).$quote;
			}
			
			$cond = $lcond.' '.$rcond;
			  
			$cond = str_replace("\|", "|", $cond);

			eval("\$result = (".$cond.");");
								
			if ($result)
			{
				$str = str_replace($val['1'], $val['2'], $str);                 
			}
			else
			{
				$str = str_replace($val['1'], '', $str);                 
			}   
		}		
		
		return $str;
		
	} /* END */
	
	
	/** -----------------------------------
	/**  Parse Global Vars conditionals
	/** -----------------------------------*/
	
	function global_vars_conditionals($str)
	{
		return $this->array_conditionals($str, $this->global_vars);
	}

	function array_conditionals($str, $vars = array())
	{
		global $SESS, $IN, $FNS;
		
		if (sizeof($vars) == 0 OR ! stristr($str, LD.'if'))
		{
			return $str;
		}
	
		$this->var_cond = $FNS->assign_conditional_variables($str);
		
		if (sizeof($this->var_cond) == 0)
		{
			return $str;
		}
		
		foreach ($this->var_cond as $val)
		{
			// Make sure there is such a $global_var
			// And that this is not an advanced conditional
			
			if ( ! isset($vars[$val['3']]) OR 
				sizeof(preg_split("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&|\|\|)/", $val['0'])) > 2 OR 
				stristr($val['2'], 'if:else') OR
				stristr($val['0'], 'if:else'))
			{
				continue;	
			}
			
			$cond = $FNS->prep_conditional($val['0']);
			
			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));
			
			if ( ! stristr($rcond, '"') && ! stristr($rcond, "'")) continue;
			
			$temp = $vars[$val['3']];

			$lcond = str_replace($val['3'], "\$temp", $lcond);
			
			if (stristr($rcond, '\|') !== FALSE OR stristr($rcond, '&') !== FALSE)
			{
				$rcond	  = trim($rcond);
				$operator = trim(substr($rcond, 0, strpos($rcond, ' ')));
				$check	  = trim(substr($rcond, strpos($rcond, ' ')));
			
				$quote = substr($check, 0, 1);
				
				if (stristr($rcond, '\|') !== FALSE)
				{
					$array =  explode('\|', str_replace($quote, '', $check));
					$break_operator = ' || ';
				}
				else
				{
					$array =  explode('&', str_replace($quote, '', $check));
					$break_operator = ' && ';
				}
				
				$rcond  = $operator.' '.$quote;
				
				$rcond .= implode($quote.$break_operator.$lcond.' '.$operator.' '.$quote, $array).$quote;
			}
			
			$cond = $lcond.' '.$rcond;
			  
			$cond = str_replace("\|", "|", $cond);

			eval("\$result = (".$cond.");");
								
			if ($result)
			{
				$str = str_replace($val['1'], $val['2'], $str);                 
			}
			else
			{
				$str = str_replace($val['1'], '', $str);                 
			}   
		}		
		
		return $str;
		
	} /* END */
	
	
	/** ----------------------------------
	/**  Add an Item to the Template Log
	/** ----------------------------------*/
	
	function log_item($str)
	{
		global $SESS;
		
		if ($this->debugging !== TRUE OR $SESS->userdata['group_id'] != 1)
		{
			return;
		}
	
		if ($this->depth > 0)
		{
			$str = str_repeat('&nbsp;', $this->depth * 5).$str;
		}
		
		if (phpversion() < 5)
		{
			list($usec, $sec) = explode(" ", microtime());
			$time = ((float)$usec + (float)$sec) - $this->start_microtime;
		}
		else
		{
			$time = microtime(TRUE)-$this->start_microtime;
		}
		
		$this->log[] = '('.number_format($time, 6).') '.$str;
	}
	/* END */
	
	
	/** ----------------------------------
	/**  Template Authentication - Basic
	/** ----------------------------------*/
	
	function template_authentication_basic()
	{
		global $PREFS, $OUT;
		
		@header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
    	$OUT->http_status_header(401);
    	@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
    	exit("HTTP/1.0 401 Unauthorized");
	}
	/* END */
	
	/** ----------------------------------
	/**  Template Authentication - Digest
	/** ----------------------------------*/
	
	function template_authentication_digest()
	{
		global $PREF, $OUT;
		
		@header('WWW-Authenticate: Digest realm="'.$this->realm.'",gop="auth", nonce="'.uniqid('').'", opaque="'.md5($this->realm).'"');
    	$OUT->http_status_header(401);
    	@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
    	exit("HTTP/1.0 401 Unauthorized");
	}
	/* END */
	
	
	/** ----------------------------------
	/**  Check Template Authentication - Digest
	/** ----------------------------------*/
	
	function template_authentication_check_digest($not_allowed_groups = array())
	{
		global $DB, $SESS, $PREFS, $FNS;
		
		if ( ! in_array('2', $not_allowed_groups))
		{
			$not_allowed_groups[] = 2;
			$not_allowed_groups[] = 3;
			$not_allowed_groups[] = 4;
		}
		
		if (empty($_SERVER) OR ! isset($_SERVER['PHP_AUTH_DIGEST']))
    	{
            return FALSE;
        }
        
        $required = array('uri'			=> '', 
        				  'response'	=> '', 
        				  'realm'		=> $this->realm,
        				  'username'	=> '',
        				  'nonce'		=> 1,
        				  'nc'			=> 1,
        				  'cnonce'		=> 1,
        				  'qop'			=> 1);
        
        $params = $FNS->assign_parameters($_SERVER['PHP_AUTH_DIGEST']);
        
        extract($required);
        extract($params);
        
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($SESS->check_password_lockout($username) === TRUE)
		{
			return FALSE;   
		}
    	
    	/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/
		
		$query = $DB->query("SELECT password, group_id FROM exp_members WHERE username = '".$DB->escape_str($username)."'");
		
		if ($query->num_rows == 0)
		{
			$SESS->save_password_lockout($username);
			return FALSE;
		}
		
		if (in_array($query->row['group_id'], $not_allowed_groups))
		{
			return FALSE;
		}
		
		$parts = array(
						md5($username.':'.$realm.':'.$query->row['password']),
						md5($_SERVER['REQUEST_METHOD'].':'.$uri)
					  );
					  
		$valid_response = md5($parts['0'].':'.$nonce.':'.$nc.':'.$cnonce.':'.$qop.':'.$parts['1']);
		
		if ($valid_response == $response)
		{
			return TRUE;
		}
		else
		{
			$SESS->save_password_lockout($username);
			
			return FALSE;
		}
	}
	/* END */
	
	
	/** ----------------------------------
	/**  Check Template Authentication - Basic
	/** ----------------------------------*/
	
	function template_authentication_check_basic($not_allowed_groups = array())
	{
		global $DB, $SESS, $PREFS, $FNS;
		
		if ( ! in_array('2', $not_allowed_groups))
		{
			$not_allowed_groups[] = 2;
			$not_allowed_groups[] = 3;
			$not_allowed_groups[] = 4;
		}
		
		/** ----------------------------------
		/**  Find Username, Please
		/** ----------------------------------*/

    	if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_USER']))
    	{
            $user = $_SERVER['PHP_AUTH_USER'];
        }
        elseif ( !empty($_ENV) && isset($_ENV['REMOTE_USER']))
        {
            $user = $_ENV['REMOTE_USER'];
        }
        elseif ( @getenv('REMOTE_USER'))
        {
            $user = getenv('REMOTE_USER');
        }
        elseif ( ! empty($_ENV) && isset($_ENV['AUTH_USER']))
        {
            $user = $_ENV['AUTH_USER'];
        }
        elseif ( @getenv('AUTH_USER'))
        {
            $user = getenv('AUTH_USER');
        }
        
		/** ----------------------------------
		/**  Find Password, Please
		/** ----------------------------------*/
        
        if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_PW']))
        {
            $pass = $_SERVER['PHP_AUTH_PW'];
        }
        elseif ( ! empty($_ENV) && isset($_ENV['REMOTE_PASSWORD']))
        {
            $pass = $_ENV['REMOTE_PASSWORD'];
        }
        elseif ( @getenv('REMOTE_PASSWORD'))
        {
            $pass = getenv('REMOTE_PASSWORD');
        }
        elseif ( ! empty($_ENV) && isset($_ENV['AUTH_PASSWORD']))
        {
            $pass = $_ENV['AUTH_PASSWORD'];
        }
        elseif ( @getenv('AUTH_PASSWORD'))
        {
            $pass = getenv('AUTH_PASSWORD');
        }
        
        /** ----------------------------------
		/**  Authentication for IIS
		/** ----------------------------------*/
    	
    	if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
    	{
			if ( isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($HTTP_AUTHORIZATION, 6)));
			}
			elseif ( ! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
			}
			elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
			}
		}
    	
		/** ----------------------------------
		/**  Authentication for FastCGI
		/** ----------------------------------*/
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{	
			if (!empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
			}
			elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
			}
		}
    	
    	if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
    	{
    		return FALSE;
    	}
    	
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($SESS->check_password_lockout($user) === TRUE)
		{
			return FALSE;   
		}
    	
    	/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/
		
		$query = $DB->query("SELECT password, group_id FROM exp_members WHERE username = '".$DB->escape_str($user)."'");
		
		if ($query->num_rows == 0)
		{
			$SESS->save_password_lockout($user);
			return FALSE;
		}
		
		if (in_array($query->row['group_id'], $not_allowed_groups))
		{
			return FALSE;
		}
		
		if ($query->row['password'] == $FNS->hash(stripslashes($pass)))
		{
			return TRUE;
		}
		
		$orig_enc_type = $PREFS->ini('encryption_type');
		$PREFS->core_ini['encryption_type'] = ($PREFS->ini('encryption_type') == 'md5') ? 'sha1' : 'md5';
		
		if ($query->row['password'] == $FNS->hash(stripslashes($pass)))
		{
			return TRUE;
		}
		else
		{
			$SESS->save_password_lockout($user);
			
			return FALSE;
		}
	}
	/* END */

	
	/** ---------------------------------------
	/**  Fetch Template site id's for tags
	/** ---------------------------------------*/
	
	function _fetch_site_ids()
	{
		global $DB, $PREFS;
		
		$this->site_ids = array();
        
        if (isset($this->tagparams['site']))
        {
        	if (sizeof($this->sites) == 0 && $PREFS->ini('multiple_sites_enabled') == 'y')
        	{
        		$sites_query = $DB->query("SELECT site_id, site_name FROM exp_sites");
        		
        		foreach($sites_query->result as $row)
        		{
        			$this->sites[$row['site_id']] = $row['site_name'];
        		}
        	}
        	
        	if (substr($this->tagparams['site'], 0, 4) == 'not ')
			{
				$sites = array_diff($this->sites, explode('|', substr($this->tagparams['site'], 4)));	
			}
			else
			{
				$sites = array_intersect($this->sites, explode('|', $this->tagparams['site']));
			}
			
			// Let us hear it for the preservation of array keys!
			$this->site_ids = array_flip($sites);
        }
        
        // If no sites were assigned via parameter, then we use the current site's
        // Templates, Weblogs, and various Site data
        
        if (sizeof($this->site_ids) == 0)
        {
        	$this->site_ids[] = $PREFS->ini('site_id');
        }
	}
}
// END CLASS
?>