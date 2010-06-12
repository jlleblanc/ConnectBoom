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
 File: core.system.php
-----------------------------------------------------
 Purpose: This file initializes ExpressionEngine.
 It loads the system preferences and instantiates the 
 base classes. All data flows through this file.
=====================================================
*/



// --------------------------------------------------
//  Turn off magic quotes
// --------------------------------------------------

    @set_magic_quotes_runtime(0);

// ----------------------------------------------
//  Instantiate the Benchmark class
// ----------------------------------------------

    $BM = new Benchmark();
    $BM->mark('start');  // Start the timer        
        
// --------------------------------------------------
//  Kill globals
// --------------------------------------------------

	if ((bool) @ini_get('register_globals'))
	{	
		// Would kind of be "wrong" to unset any of these GLOBALS.
		$protected = array('_SERVER', '_GET', '_POST', '_ENV', '_FILES', '_REQUEST', '_SESSION', 'GLOBALS', 'HTTP_RAW_POST_DATA', 'uri');
		
		foreach (array($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES, $_ENV, (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : array()) as $global)
		{
			if ( ! is_array($global))
			{
				if ( ! in_array($global, $protected))
				{
					unset($GLOBALS[$global]);
				}
			}
			else
			{
				foreach ($global as $key => $val)
				{
					if ( ! in_array($key, $protected))
					{
						unset($GLOBALS[$key]);
					}
					
					if (is_array($val))
					{
						foreach($val as $k => $v)
						{
							if ( ! in_array($k, $protected))
							{
								unset($GLOBALS[$k]);
							}
						}
					}
				}    
			}
		}
	}
    
// --------------------------------------------------
//  Determine system path and site name
// --------------------------------------------------
              
    if ( ! isset($system_path))
    {
        $system_path = './';
	}
	
    if (@realpath($system_path) !== FALSE)
    {
		$system_path = rtrim(realpath($system_path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    } 

    //$system_path = str_replace("\\", "/", $system_path);    
             
    if ( ! isset($config_file))
    {
        $config_file = $system_path .'config'.$ext;
    }

    if ( ! isset($self))
    {
        $self = 'index'.$ext ;
    }
    
	if ( ! isset($site_name))
    {
        $site_name = '';
    }
    else
    {
    	$site_name = preg_replace("/[^a-z0-9\-\_]/i", '', $site_name);
    }

// --------------------------------------------------
//  Security checks
// --------------------------------------------------

    if ( ! in_array($ext, array('.php', '.php4')))
    {
        exit('Disallowed file extension');
    }   

    if (preg_match("#^(http:\/\/|https:\/\/|www\.|ftp|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $system_path))
    {
        exit('Invalid path formatting');
    }   

    if ( ! file_exists($config_file)) 
    {
        exit('Disallowed system path');
    }
        
    if (isset($uri))
	{
		$uri = str_replace(array("\r", "\r\n", "\n", '%3A','%3a','%2F','%2f'), array('', '', '', ':', ':', '/', '/'), $uri);
		
		if (preg_match("#(;|\?|{|}|<|>|http:\/\/|https:\/\/|\w+:/*[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $uri)) 
		{
			exit('Invalid URI');
    	}
    }

// ----------------------------------------------
//  Set base system constants
// ----------------------------------------------

    define('APP_NAME'	,	'ExpressionEngine');
    define('APP_BUILD'	,	'20100430');   
    define('CONFIG_FILE',	$config_file); 
    define('PATH_CACHE'	,	$system_path.'cache/'); 
    define('PATH_LANG'	,	$system_path.'language/'); 
    define('PATH_EXT'  	,	$system_path.'extensions/');
    define('PATH_CORE'	,	$system_path.'core/'); 
    define('PATH_TMPL'	,	$system_path.'templates/'); 
    define('PATH_DICT'	,	$system_path.'dictionary/'); 
    define('PATH_MOD'	,	$system_path.'modules/'); 
    define('PATH_DB'	,	$system_path.'db/'); 
    define('PATH_PI'	,	$system_path.'plugins/'); 
    define('PATH_CP'	,	$system_path.'cp/'); 
    define('PATH_LIB'	,	$system_path.'lib/');
    define('PATH'		,	$system_path);
    define('SELF'		,	$self);
    define('EXT'		,	$ext);
	define('SLASH'		,	'&#47;');
	define('LD'			,	'{');
	define('RD'			,	'}');

    unset($system_path);
    unset($config_file);
    unset($pathinfo);
    unset($self);
    unset($ext);
    

// ----------------------------------------------
//  Set User Blog constants
// ----------------------------------------------
    
    if ( ! isset($user_blog) OR (isset($user_blog_id) && ! is_numeric($user_blog_id)))
    {
        define('USER_BLOG',  FALSE);
    }
    else
    {
        // Note: These variables must be added to each user's 
        // index.php file if they use the User Blogs module
    
        define('USER_BLOG'   	, $user_blog);		// The name of the user blog (directory name)
        define('UB_BLOG_ID'  	, $user_blog_id); 	// The weblog ID of the user blog
        define('UB_FIELD_GRP'	, $user_blog_fg);	// The field group of the user blog
        define('UB_CAT_GRP'  	, $user_blog_cg);	// The category group of the user blog
        define('UB_TMP_GRP'  	, $user_blog_tg);	// The template group of the user blog
    }
    
    
// ----------------------------------------------
//  Fetch config file
// ----------------------------------------------

    require CONFIG_FILE;
    
    if ( ! isset($conf))
    {
        exit("The system does not appear to be installed.");
    }
    
	define('APP_VER', substr($conf['app_version'], 0, 1).'.'.substr($conf['app_version'], 1, 1).'.'.substr($conf['app_version'], 2, 1));   

 
// ----------------------------------------------
// Set error reporting 
// ----------------------------------------------    

    if ($conf['debug'] == 2 AND ! isset($conf['demo_date']))
    {
        error_reporting(E_ALL);
    }
    
// ----------------------------------------------
//  Determine the request type
// ----------------------------------------------

    // There are three possible request types:
    // 1. A control panel request
    // 2. An "action" request
    // 3. A publicly accessed page (template) request

    if ( ! isset($uri))
    {
        define('REQ', 'CP'); 
    }
    elseif (isset($_GET['ACT']) || isset($_POST['ACT']))
	{
		define('REQ', 'ACTION'); 
	}
	else
	{
		define('REQ', 'PAGE');
	}


// ----------------------------------------------
//  Set a liberal script execution time limit for the CP
// ----------------------------------------------

	if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
	{
		if (REQ == 'CP')
		{
			@set_time_limit(300);	
		}
		else
		{
			@set_time_limit(90);
		}
	}


// ----------------------------------------------
// Set configuration exceptions
// ----------------------------------------------    

    // These are configuration exceptions.  In some cases a user might want
    // to manually override a config file setting by adding a variable in
    // the index.php or path.php file.  This loop permits this to happen.
    
    $config_exceptions	= array('site_url', 'site_index', 'site_404', 'template_group', 'template', 'cp_url');
    $global_exceptions	= array('template_group', 'template');
    $exceptions = array();

	foreach ($config_exceptions as $exception)
	{
		if (isset($$exception) AND $$exception != '')
		{
			if (REQ != 'CP' OR $exception == 'cp_url')
			{
				$conf[$exception] = $$exception; // User/Action
			}
			else
			{
				$exceptions[$exception] = $$exception;  // CP
			}
			
			if ( ! in_array($exception, $global_exceptions))
			{
				unset($$exception);
			}
		}
	}
	    
// ----------------------------------------------
//  Instantiate the Preferences class
// ---------------------------------------------- 
    
    require PATH_CORE.'core.prefs'.EXT;        
                
    $PREFS = new Preferences();

    // Assign the config file array to the preferences
    // class so we can transport it as an object
    
    $PREFS->core_ini = $conf;
    $PREFS->default_ini = $conf;
    $PREFS->exceptions = $exceptions;
    
    unset($conf);
    unset($exceptions);
    
// ----------------------------------------------
//  Connect to the database
// ----------------------------------------------
	
	if ( ! in_array($PREFS->ini('db_type'), array('mysql')))
	{
		return FALSE;
	}
	
    require PATH_DB.'db.'.$PREFS->ini('db_type').EXT;
        
    $db_config = array(
                        'hostname'  	=> $PREFS->ini('db_hostname'),
                        'username'  	=> $PREFS->ini('db_username'),
                        'password'  	=> $PREFS->ini('db_password'),
                        'database'  	=> $PREFS->ini('db_name'),
                        'prefix'    	=> $PREFS->ini('db_prefix'),
                        'conntype'  	=> $PREFS->ini('db_conntype'),
                        'debug'			=> ($PREFS->ini('debug') != 0) ? 1 : 0,
                        'show_queries'	=> TRUE,
                        'enable_cache'	=> FALSE
                      );

    $DB = new DB($db_config);

	if ( ! $DB->db_connect(0))
	{
		exit("Database Error:  Unable to connect to your database. Your database appears to be turned off or the database connection settings in your config file are not correct. Please contact your hosting provider if the problem persists.");
	}
		
	if ( ! $DB->select_db())
	{
		exit("Database Error:  Unable to select your database");
	}
    
// ----------------------------------------------
//  Instantiate the regular expressions class
// ---------------------------------------------- 
    
    require PATH_CORE.'core.regex'.EXT;    
    
    $REGX = new Regex();
    
// ----------------------------------------------
//  Set Site Preferences class - Needs DB Connection and Regex
// ---------------------------------------------- 
    
    $PREFS->site_prefs($site_name);
    unset($site_name);
    
    $DB->show_queries = ($PREFS->ini('show_queries') == 'y') ? TRUE : FALSE;
    $DB->enable_cache = ($PREFS->ini('enable_db_caching') == 'y') ? TRUE : FALSE;
    $DB->debug 		  = ($PREFS->ini('debug') != 0) ? 1 : 0;
    
    // Turn off caching if it's a CP or ACTION request
    if (REQ == 'CP' OR REQ == 'ACTION')
    {
    	$DB->enable_cache = FALSE;
    }

// ----------------------------------------------
//  Fetch input data: GET, POST, COOKIE, SERVER
// ----------------------------------------------

    require PATH_CORE.'core.input'.EXT;
        
    $IN = new Input();

	$IN->trim_input = (isset($uri)) ? TRUE : FALSE;
	
	if (isset($global_vars) AND is_array($global_vars))
	{
		$IN->global_vars = $global_vars;
	}
    
    $IN->fetch_input_data();
    
    // Parse URI string if it's not a control panel request.
    // The $uri variable is not set during CP requests
                
    if (isset($uri))
    {
		if (isset($qstr))
		{
			$IN->QSTR = $qstr;
		}
		else
		{
			$IN->parse_uri($uri);
			$IN->parse_qstr();
		}
    }

	if (REQ == 'ACTION')
	{
		// The IN-QSTR variable is not available during
		// action requests so we'll set it.
		
		if ($IN->QSTR == '' AND (count($IN->SEGS) > 0))
		{
			$IN->QSTR = end($IN->SEGS);
		}
	}
	
	/** --------------------------------------
	/**	 To Check Cookies We Need the Input Class.
	/**	 To Instantiate the Input Class We Need Database
	/**	 To Use the Database Class We Need Prefs
	/**	 To Load the Correct Prefs for the CP, We Need To Know the Correct Site to Load Based on Cookies
	/**
	/**  So, If Cookie From CP Tells Us To Load a Different Site's Prefs, We Must Redo A Few Things...
	/** --------------------------------------*/
	
	if (REQ == 'CP' && $IN->GBL('cp_last_site_id', 'COOKIE') !== FALSE && is_numeric($IN->GBL('cp_last_site_id', 'COOKIE')) && $IN->GBL('cp_last_site_id', 'COOKIE') != $PREFS->ini('site_id'))
    {
    	$PREFS->site_prefs('', $IN->GBL('cp_last_site_id', 'COOKIE'));
    }
    
// ----------------------------------------------
//  Theme Paths
// ----------------------------------------------

	if ($PREFS->ini('theme_folder_path') !== FALSE && $PREFS->ini('theme_folder_path') != '')
	{
		$theme_path = preg_replace("#/+#", "/", $PREFS->ini('theme_folder_path').'/');
	}
    else
    {
   		$theme_path = substr(PATH, 0, - strlen($PREFS->ini('system_folder').'/')).'themes/';
		$theme_path = preg_replace("#/+#", "/", $theme_path);
	}
	
	define('PATH_THEMES', 		$theme_path);
    define('PATH_SITE_THEMES',	PATH_THEMES.'site_themes/'); 
    define('PATH_MBR_THEMES',	PATH_THEMES.'profile_themes/'); 
	define('PATH_CP_IMG', 		$PREFS->ini('theme_folder_url', 1).'cp_global_images/');
	
	if (REQ == 'CP')
	{
		define('PATH_CP_THEME', PATH_THEMES.'cp_themes/');
	}
    
	unset($theme_path);


// ----------------------------------------------
//  Is this a stylesheet request?
// ----------------------------------------------

// If so, we'll fetch it and exit.  No need to go further.

    if (isset($_GET['css']) OR (isset($_GET['ACT']) && $_GET['ACT'] == 'css')) 
    {
        require PATH_CORE.'core.style'.EXT;    
        $SS = new Style();
        exit;
    }

// ----------------------------------------------
// Throttle Check
// ----------------------------------------------

	if ($PREFS->ini('enable_throttling') == 'y' AND REQ == 'PAGE')
	{
		require PATH_CORE.'core.throttling'.EXT;    
		
		$THR = new Throttling();
		$THR->throttle_ip_check();
		$THR->throttle_check();
		$THR->throttle_update();
	}

// ----------------------------------------------
//  Check Blacklist
// ----------------------------------------------

	if (REQ != 'CP')
	{
		$blacklist_check = $IN->check_blacklist();
	}
    
// ----------------------------------------------
//  Instantiate the Functions class
// ----------------------------------------------

    require PATH_CORE.'core.functions'.EXT;    
    
    $FNS = new Functions();
    
// ----------------------------------------------
//  Instantiate Extensions Class
// ----------------------------------------------

	require PATH_CORE.'core.extensions'.EXT;
	
	$EXT = new Extensions();
    
// ----------------------------------------------
//  Do we need to remap pMachine URLs?
// ----------------------------------------------

	$FNS->remap_pm_urls();

// ----------------------------------------------
//  Instantiate the Output class
// ----------------------------------------------

    require PATH_CORE.'core.output'.EXT;    
    
    $OUT = new Output();


// ----------------------------------------------
//  Instantiate the Localization class
// ----------------------------------------------

	if (function_exists('date_default_timezone_set'))
	{
		date_default_timezone_set(date_default_timezone_get());
	}
	
    require PATH_CORE.'core.localize'.EXT;    
    
    $LOC = new Localize();
    
// ----------------------------------------------
//  Initialize a session
// ----------------------------------------------

    require PATH_CORE.'core.session'.EXT;

    $SESS = new Session();
    
    // If error reporting is only displayed for Super Admins, we'll enable it
    
    if ($PREFS->ini('debug') == 1 AND $SESS->userdata('group_id') == 1 AND $PREFS->ini('demo_date') === FALSE)
    {
        error_reporting(E_ALL);
    }
	elseif($PREFS->ini('debug') == 1)
	{
		$DB->debug = 0;
	}
   
// ----------------------------------------------
//  Filter GET Data
// ----------------------------------------------

// We need to filter GET data for security.
// We've pre-processed global data eariler, but since
// we didn't have a session yet we were not able to
// determine a condition for filtering

	$IN->filter_get_data(REQ);
    
   
// ----------------------------------------------
//  Update system statistics
// ----------------------------------------------

    require PATH_MOD.'stats/mcp.stats'.EXT;
    $STAT = new Stats_CP();

	if (REQ == 'PAGE' && $PREFS->ini('enable_online_user_tracking') != 'n')
	{
		$STAT->update_stats();
	}

// ----------------------------------------------
//  Instantiate language class
// ----------------------------------------------    
        
    require_once PATH_CORE.'core.language'.EXT;
    $LANG = new Language();
    
    // Fetch core language file
    
    $LANG->fetch_language_file('core');

    
// ----------------------------------------------
//  Is the system turned on?
// ----------------------------------------------  
        
    // Note: super-admins can always view the system
        
    if ($SESS->userdata('group_id') != 1  AND REQ != 'CP')
    {    
        if ($PREFS->ini('is_system_on') == 'y' && ($PREFS->ini('multiple_sites_enabled') != 'y' OR $PREFS->ini('is_site_on') == 'y'))
        {
			if ($SESS->userdata('can_view_online_system') == 'n')
			{
				$OUT->system_off_msg();
            		exit;
            }
        }
        else
        {
			if ($SESS->userdata('can_view_offline_system') == 'n')
			{
				$OUT->system_off_msg();
            		exit;
            }        
        }
    }


// ----------------------------------------------
//  Process the request
// ----------------------------------------------

switch (REQ)
{
	/** ---------------------------
	/**  Action Requests
	/** ---------------------------*/

    case 'ACTION' :
                          
            require PATH_CORE.'core.actions'.EXT;
            
            $ACT = new Action();
    	break;
    	
	/** ---------------------------
	/**  Page Requests
	/** ---------------------------*/
    	
    case 'PAGE' : 
    
    		/** Because of the way that the Sites and Preferences are set up in 1.6, we were not able
    		/** to set the REQ constant as ACTION for this request, so we have to make a little exception
    		/** here, which while it hurts me to be so inelegant, I guess I can live with. */
    		
    		if (isset($_GET['ACT']) && $_GET['ACT'] == 'trackback')
    		{
    			require PATH_CORE.'core.actions'.EXT;
            
            	$ACT = new Action();
    		}
    
    		// If the forum module is installed and the URI contains the "triggering" word
    		// we will override the template parsing class and call the forum class directly.
    		// This permits the forum to be more light-weight as the template engine is 
    		// not needed under normal circumstances. 
    		
			elseif ($PREFS->ini('forum_is_installed') == "y" AND  $PREFS->ini('forum_trigger') != '' AND in_array($IN->fetch_uri_segment(1), preg_split('/\|/', $PREFS->ini('forum_trigger'), -1, PREG_SPLIT_NO_EMPTY)))
			{
				require PATH_MOD.'forum/mod.forum'.EXT;
				$FRM = new Forum();
			}			
       		elseif ($PREFS->ini('profile_trigger') != "" AND $PREFS->ini('profile_trigger') == $IN->fetch_uri_segment(1))
       		{
				// We do the same thing with the member profile area.  
       		
       			if ( ! file_exists(PATH_MOD.'member/mod.member'.EXT))
       			{
       				exit;
       			}
       			else
       			{
					require PATH_MOD.'member/mod.member'.EXT;
					
					$MBR = new Member();  			
					$MBR->_set_properties(
											array(
													'trigger' => $PREFS->ini('profile_trigger')
												)
										);	
		
					$OUT->build_queue($MBR->manager());
				}
       		}
       		else  // Instantiate the template parsing class and parse the requested template/page
       		{       		
       			if (empty($template_group) && empty($template))
       			{
					$pages = $PREFS->ini("site_pages");
					$pages = $pages[$PREFS->ini('site_id')];
					
					$match_uri = ($IN->URI == '') ? '/' : '/'.trim($IN->URI, '/').'/';
					
					if ($pages !== FALSE && isset($pages['uris']) && ($entry_id = array_search($match_uri, $pages['uris'])) !== FALSE)
					{
						$query = $DB->query("SELECT t.template_name, tg.group_name
											 FROM exp_templates t, exp_template_groups tg 
											 WHERE t.group_id = tg.group_id 
											 AND t.template_id = '".$DB->escape_str($pages['templates'][$entry_id])."'");
											 
						if ($query->num_rows > 0)
						{
							/* 
								We do it this way so that we are not messing with any of the segment variables,
								which should reflect the actual URL and not our Pages redirect. We also
								set a new QSTR variable so that we are not interfering with other module's 
								besides the Weblog module (which will use the new Pages_QSTR when available).
							*/
							
							$template_group = $query->row['group_name'];
							$template = $query->row['template_name'];
							$IN->Pages_QSTR = $entry_id;
						}
					}
				}
				
				require PATH_CORE.'core.template'.EXT;
				
				$TMPL = new Template();
							
				// Templates and Template Groups can be hard-coded
				// within either the main triggering file or via an include.
							
				if ( ! isset($template_group)) $template_group = '';
				if ( ! isset($template)) $template = '';
				
				// Parse the template
				$TMPL->run_template_engine($template_group, $template);
			}
    	break;
    	
	/** ---------------------------
	/**  Control Panel Requests
	/** ---------------------------*/
    	
    case 'CP' :
    
			/** ------------------------------------
            /**  Define our base URL
            /** ------------------------------------*/

            $s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata('session_id') : 0;
            
            define('BASE', SELF.'?S='.$s);  
               
			/** ------------------------------------
            /**  Fetch control panel language file
            /** ------------------------------------*/
            
            $LANG->fetch_language_file('cp');

			/** ------------------------------------
            /**  Instantiate Display Class.
            /** ------------------------------------*/

            // This class contains all the HTML elements that are used to create the CP
        
            require PATH_CP.'cp.display'.EXT;
            $DSP = new Display();            
 
			/** ------------------------------------ 
            /**  Instantiate Admin Log Class
            /** ------------------------------------*/

            require PATH_CP.'cp.log'.EXT;
            $LOG = new Logger();
 
			/** ------------------------------------ 
            /**  Class/Method Matrix
            /** ------------------------------------*/
            
            // Map available classes against the query string and
            // require and instantiate the class and/or method associated with it

            $class_map = array(
                               // 'query str'   => array('class name', 'method name)
                               
                                  'default'     => array('Home'),
                                  'login'       => array('Login'),
                                  'reset'       => array('Login',   'reset_password'),
                                  'logout'      => array('Login',   'logout'),
                                  'publish'     => array('Publish', 'request_handler'),   
                                  'edit'        => array('Publish', 'request_handler'),
                                  'templates'   => array('Templates'),
                                  'communicate' => array('Communicate'),
                                  'modules'     => array('Modules'),
                                  'members'     => array('Members'),
                                  'myaccount'   => array('MyAccount'),
                                  'admin'       => array('Admin'),
                                  'sites'		=> array('Sites'),
                               );
                               
			if ( ! file_exists(PATH_CP.'cp.sites.php'))
			{
				unset($class_map['sites']);
			}
			
			/** ------------------------------------ 
            /**  Determine Which Class to Use
            /** ------------------------------------*/
              
            // No admin session exists?  Show login screen
            
            if ($SESS->userdata('admin_sess') == 0 AND $IN->GBL('C', 'GET') != 'reset')
            {
                $C = $class_map['login']['0'];
                $M = '';
            }
            else
            {
            	if ($PREFS->ini('secure_forms') == 'y' && sizeof($_POST) > 0)
            	{
            		if ( ! isset($_POST['XID']))
            		{
            			$FNS->redirect(BASE);
            		}
            		
            		$query = $DB->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            							 WHERE hash = '".$DB->escape_str($_POST['XID'])."' 
            							 AND ip_address = '".$IN->IP."' 
            							 AND date > UNIX_TIMESTAMP()-14400");
        
					if ($query->row['count'] == 0)
					{
						$FNS->redirect(BASE);
					}
					else
					{
						$DB->query("DELETE FROM exp_security_hashes 
									WHERE date < UNIX_TIMESTAMP()-14400
									AND ip_address = '".$IN->IP."'");
									
						unset($_POST['XID']);
					}
            	}
            
                // If the query string is not in the $class_map array, show default page
                
                if ( ! in_array($IN->GBL('C'), array_keys($class_map)))
                {
                    $C = $class_map['default']['0'];
    
                    $M = ( ! isset($class_map['default']['1'])) ? '' : $class_map['default']['1'];
                }
                else
                {
                    $C =  $class_map[$IN->GBL('C')]['0'];
                    
                    $M = ( ! isset($class_map[$IN->GBL('C')]['1'])) ? '' : $class_map[$IN->GBL('C')]['1'];
                }
            }                 
            
			/** ------------------------------------ 
            /**  Load Class Language File
            /** ------------------------------------*/
            
            // Note: Language files must be named the same as the class (lowercase)
                        
            $LANG->fetch_language_file(strtolower($C));

			/** ------------------------------------ 
            /**  Instantiate the Requested Class
            /** ------------------------------------*/
                        
            require PATH_CP.'cp.'.strtolower($C).EXT;
            $EE = new $C;
     
            // If there is a method, call it.
            
            if ($M != '' AND method_exists($EE, $M))
            {
				$EE->$M();				
            }

			/** ------------------------------------ 
            /**  Assemble the control panel
            /** ------------------------------------*/
        
            // No session? Show the login page
            
            if ($SESS->userdata('admin_sess') == 0)
            {
                $DSP->show_login_control_panel();
            }
            else
            {
				/** ------------------------------------ 
                /**  Is user banned?
                /** ------------------------------------*/
				
                // Before rendering the full control panel we'll make sure the user isn't banned
                // But only if they are not a Super Admin, as they can not be banned

                if ($SESS->userdata('group_id') != 1 AND $SESS->ban_check('ip'))
                {
					return $OUT->fatal_error($LANG->line('not_authorized'));
                }
                
				/** ------------------------------------ 
                /**  Display the Control Panel
                /** ------------------------------------*/
            
                // The 'Z' GET variable indicates that we need to show the simplified
                // version of the control panel.  We use this mainly for pop-up pages in
                // which we don't need the navigation.
            
                if ($IN->GBL('Z'))
                {
                    $DSP->show_restricted_control_panel();
                }
                else
                {
                    $DSP->show_full_control_panel();
                }
            }
    break;    
}
// END SWITCH


// ----------------------------------
//  Render the final browser output
// -----------------------------------

    $OUT->display_final_output();
    
    
// ----------------------------------
// Log referrers
// -----------------------------------
	
	if (REQ == 'PAGE')
	{
		$FNS->log_referrer();
	}
     
// ----------------------------------
//  Garbage Collection
// -----------------------------------

// Every 7 days we'll run our garbage collection

	$DB->enable_cache = FALSE;

	if (class_exists('Stats'))
	{ 
		if (isset($STAT->stats['last_cache_clear']) AND $STAT->stats['last_cache_clear'] > 1)
		{
			$last_clear = $STAT->stats['last_cache_clear'];
		}
	}

	if ( ! isset($last_clear) && $PREFS->ini('enable_online_user_tracking') != 'n')
	{
		$query = $DB->query("SELECT last_cache_clear FROM exp_stats WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '0'");
		$last_clear = $query->row['last_cache_clear'];
	}

	if (isset($last_clear) && $LOC->now > $last_clear)
	{
		$expire = $LOC->now + (60*60*24*7);
		$DB->query("UPDATE exp_stats SET last_cache_clear = '{$expire}' WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND weblog_id = '0'");
		
		if ($PREFS->ini('enable_throttling') == 'y')
		{		
			$expire = time() - 180;
			$DB->query("DELETE FROM exp_throttle WHERE last_activity < {$expire}");
		}

		$FNS->clear_spam_hashes();
		$FNS->clear_caching('all');
	}


// ----------------------------------
//  Close database connection
// -----------------------------------
    
    $DB->db_close();


// END OF SYSTEM EXECUTION



/*
=====================================================
 Benchmark Class
-----------------------------------------------------
 Purpose: This class can calculate
 the time difference between any two marked points.
 Multiple mark points can be captured.
=====================================================

 CODE EXAMPLE:
    
 $BM = new Benchmark();
 $BM->mark('FIRST_MARK');

 // Some code happens here

 $BM->mark('SECOND_MARK');
 echo $BM->elapsed('FIRST_MARK', 'SECOND_MARK');
    
 Note: "FIRST_MARK" and "SECOND_MARK" are arbitrary names.
 You can call the mark points anything you want - and you
 can define as many marks as you need without instantiating
 a new object.

*/

class Benchmark {

    var $marker = array();
    
    /** ---------------------------------------------
    /**  Set a marker
    /** ---------------------------------------------*/

    function mark($name)
    {
        $this->marker[$name] = microtime();
    }
  
    /** ---------------------------------------------
    /**  Calculate elapsed time between two points
    /** ---------------------------------------------*/

    function elapsed($point1, $point2, $decimals = 4)
    {
        list($sm, $ss) = explode(' ', $this->marker[$point1]);
        list($em, $es) = explode(' ', $this->marker[$point2]);
                        
        return number_format(($em + $es) - ($sm + $ss), $decimals);
    }
}
// END CLASS
?>