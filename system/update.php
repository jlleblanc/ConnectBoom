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
 File: update.php
-----------------------------------------------------
 Purpose: Update class
=====================================================
*/




// ------------------------------
//  Set-up base preferences
// ------------------------------

error_reporting(E_ALL);
@set_magic_quotes_runtime(0);

$path = pathinfo(__FILE__);

define('EXT',			'.'.$path['extension']);
define('PATH',			'./'); 
define('PATH_DB',		'./db/'); 
define('PATH_CORE',		'./core/');
define('PATH_LANG',		'./language/');
define('PATH_MOD',		'./modules/');
define('PATH_EXT',		'./extensions/');
define('PATH_PI',		'./plugins/');
define('CONFIG_FILE',	'config'.EXT);

// ------------------------------
//  Fetch config file
// ------------------------------

if ( ! @include('config'.EXT))
{
	?>
	<p>An error occurred while attempting to fetch your config.php file. Please check the file permissions</p>
	<?php
	exit;
}
elseif( ! isset($conf))
{
	?>
	<p>An error occurred while attempting to fetch your config.php file. The file seems to contain no configuration values.</p>
	<?php
	exit;
}

// ------------------------------
//  Connect to the database
// ------------------------------

require PATH_DB.'db.'.$conf['db_type'].EXT;
	
$db_config = array(
					'hostname'  	=> $conf['db_hostname'],
					'username'  	=> $conf['db_username'],
					'password'  	=> $conf['db_password'],
					'database'  	=> $conf['db_name'],
					'prefix'    	=> $conf['db_prefix'],
					'conntype'  	=> $conf['db_conntype'],
					'debug'			=> 1,
					'show_queries'	=> FALSE,
					'enable_cache'	=> FALSE
				  );

$DB = new DB($db_config);
$DB->error_header = "<div class='error'>Error:  The following error was encountered</div>";
$DB->error_footer = Update::page_footer();

if ( ! $DB->db_connect(0))
{
	exit("Database Error:  Unable to connect to your database. Your database appears to be turned off or the database connection settings in your config file are not correct. Please contact your hosting provider if the problem persists.");
}
if ( ! $DB->select_db())
{
	exit("Database Error:  Unable to select your database");
}

// Check for "strict mode", some queries used are incompatible
if (version_compare(mysql_get_server_info(), '4.1-alpha', '>='))
{
	$mode_query = $DB->query("SELECT CONCAT(@@global.sql_mode, @@session.sql_mode) AS sql_mode");

	if (strpos(strtoupper($mode_query->row['sql_mode']), 'STRICT') !== FALSE)
	{
		Update::page_header();
		$DB->db_error("Database Error: ExpressionEngine will not run on a MySQL server operating in strict mode");
	}	
}

// ------------------------------
//  Instantiate the Update class
// ------------------------------

$UD = new Update();
$UD->conf = $conf;
$UD->update_manager();



class Update {

	var $cur 			= '100';
	var $update_dir 	= './updates/';
	var $update_files	= array();
	var $conf			= array();
	var $next_link		= '';
	var $newest			= '';
	var $database_error	= FALSE;
	
	/** -----------------------------
	/**  Update Manager
	/** -----------------------------*/

	function update_manager()
	{	
		global $DB;
		
		// EXCEPTIONS
		// We need to deal with a couple possible issues.
	
		// If the 'app_version' index is not present in the config file we are 
		// dealing with the public beta.  If so, we'll write it and redirect.
	
		if ( ! isset($this->conf['app_version']))
		{
			$data['app_version'] = 0;
			$this->append_config_file($data);
			header("location: update".EXT); 
			exit;
		}	
		
		// This fixes a bug introduced in the installation script for v 1.3.1
		
		if ($this->conf['app_version'] == 130)
		{
			$query = $DB->query("SELECT * FROM exp_members LIMIT 1");
		
			if (isset($query->row['accept_messages']))
			{
				$data['app_version'] = 131;
				$this->append_config_file($data);
				header("location: update".EXT); 
				exit;
			}
		}
		
		
		// Fetch the names of all the update scripts in the "update" directory.
		// We use this info to create the update links and to know
		// which update file is next in line to be called.
		
		$this->fetch_update_script_names();
		
		// Create the page header
		
		$this->page_header();		
		
		$action = (isset($_GET['action'])) ? $_GET['action'] : '';
		
		if ($action == 'optimize')
		{
			$this->optimize_tables();
			echo $this->optimize_complete_message();
			echo $this->page_footer();
			exit;
		}

		if ( empty($action) || ! isset($this->update_files['0']))
		{	
			$this->default_content();
		}
		else
		{			
			// Create the link to the requested update file
		
			$file = $this->update_dir.'ud_'.$this->update_files['0'].EXT;
		
			if ( ! file_exists($file))
			{
				$this->error_message();
				echo $this->page_footer();
				exit;
			}
			
			// Require the update file and invoke the class
			
			require $file;
			
			$XD = new Updater;
			
			// If the do_update() function returns false we have a problem.
			
			if ( ! $XD->do_update())
			{
				$this->error_message();
				echo $this->page_footer();
				exit;
			}
			
			// Update the config file with the app_version we just installed
			
			$data['app_version'] = $this->update_files['0'];
			
			$this->update_config_file($data);
			
			$this->conf['app_version'] = $this->update_files['0'];
			
			// Slice the array so we can move onto the next update file
		
			$this->update_files = array_slice($this->update_files, 1);	
			
			// Show the appropriate success message
			
			if (count($this->update_files) > 0)
			{
				$this->good_update_message();
			}
			else
			{
				$this->update_finished_message();
			}
		}
		
		// Create the link to the update file
		
		$this->next_link();
		
		// Page footer
		
		echo $this->page_footer();
	}
	/* END */


	/** -----------------------------
	/**  Default Content
	/** -----------------------------*/

	function default_content()
	{
		global $conf;
		
		$from = 'Public Beta 1';

		$fver = ( ! isset($this->conf['app_version']) || $this->conf['app_version'] == 0) ? $from : $this->conf['app_version'];
					
		if (is_numeric($fver))
		{
			$from = '';
			
			if ($fver == '009')
			{
				$from .= 'Public Beta 2';
			}
			else
			{
				$from .= 'Version '.substr($fver, 0, 1).'.'.substr($fver, 1, 1);
	
				if (substr($fver, 2, 1) != 0)
				{
					$from .= '.'.substr($fver, 2, 1);
				}
			}
		}
	?>
	
	<h1>Welcome!</h1>
		
	<?php
	
		if ($this->newest == $this->conf['app_version'])
		{
		?>
		<p><strong>You are currently running ExpressionEngine:</strong> <?php echo $from; ?></p>
		<p>This is the most current version!</p>
		<?php
		}
		else
		{
			if ( ! $this->is_really_writable('config'.EXT))
			{
				?>
				<div class="alertbox"><b>Warning:</b>&nbsp; Your config.php file is not writable.<br /><br />Before proceeding please set the file permssions to 666 on the file named config.php.<br /><br />Once you have done so please reload this page.</div>
				<?php
				echo $this->page_footer();
				exit;
			}
			else
			{
				?>
				<p><strong>You are currently running ExpressionEngine:</strong> <?php echo $from; ?></p>
				<?php
				
				/*
				
				$modules = array('blacklist', 'blogger_api', 'comment', 'email', 
								 'emoticon', 'gallery', 'ip_to_nation', 'mailinglist', 
								 'member', 'metaweblog_api', 'moblog', 'query',
								 'referrer', 'rss', 'search', 'simple_commerce',
								 'stats', 'trackback', 'updated_sites', 'weblog', 'wiki');
								
				$plugins = array('char_limit', 'magpie', 'randomizer', 'word_limit', 'xml_encode');
				
				foreach(array(PATH_EXT, PATH_MOD, PATH_PI) as $source_dir)
				{
					if ($fp = @opendir($source_dir))
					{ 
						while (FALSE !== ($file = readdir($fp)))
						{
							if (substr($file, 0, 1) != "." && $file != 'index.html')
							{
								if($source_dir == PATH_EXT && substr($file, 0, 4) == 'ext.' && substr($file, -4) == '.php')
								{
									$files[] = PATH_EXT.$file;
								}
								elseif ($source_dir == PATH_MOD && is_dir(PATH_MOD.$file) && ! in_array($file, $modules))
								{
									$files[] = PATH_MOD.$file.'/';
									
									if (file_exists(PATH_LANG.'english/lang.'.$file.'.php'))
									{
										$files[] = "./language/english/lang.{$file}.php";
									}
								}
								elseif($source_dir == PATH_PI && substr($file, 0, 3) == 'pi.' && substr($file, -4) == '.php' && ! in_array(substr($file, 3, -4), $plugins))
								{
									$files[] = PATH_PI.$file;
								}
							}
						}   
					} 
				}
				
				
				if (sizeof($files) > 0)
				{
					echo '<div class="shade"><h2>Add-On Files List:</h2><p>Please backup up these files and directories locally before updating ExpressionEngine.</p><p>';
					
					sort($files);
					
					foreach($files as $file)
					{
						echo './'.$conf['system_folder'].substr($file, 1)."<br />\n";
					}
				
					echo '</p></div>';
				}
				
				*/
				
				?>
				<p>If you have followed the <a href="http://expressionengine.com/docs/installation/update.html" onclick="window.open(this.href); return false;">Update Instructions</a> 
				and are ready to update your system, please click the link below</p>
				
				<?php
			}
		}
	}
	/* END */



	/** -----------------------------
	/**  Good update message
	/** -----------------------------*/

	function good_update_message()
	{
		$cver = ( ! isset($this->conf['app_version']) || $this->conf['app_version'] == 0) ? $this->cur : $this->conf['app_version'];
					
		if (is_numeric($cver))
		{
			$this->cur = '';
			
			if ($cver == '009')
			{
				$this->cur .= 'Public Beta 2';
			}
			else
			{
				$this->cur .= 'Version '.substr($cver, 0, 1).'.'.substr($cver, 1, 1);
	
				if (substr($cver, 2, 1) != 0)
				{
					$this->cur .= '.'.substr($cver, 2, 1);
				}
			}
		}
	?>	
	
	<h3>Good!</h3>
			
	<p>You have updated to: <?php echo $this->cur; ?></p>
	
	<p>
	Please click the link below to continue to the next step.
	</p>
	
	<?php
	}
	/* END */



	/** -----------------------------
	/**  Update finished message
	/** -----------------------------*/

	function update_finished_message()
	{
		$cur = '100';

		$cver = ( ! isset($this->conf['app_version']) || $this->conf['app_version'] == 0) ? $cur : $this->conf['app_version'];
					
		if (is_numeric($cver))
		{
			$cur = ' ';
			
			if ($cver == '009')
			{
				$cur .= 'Public Beta 2';
			}
			else
			{
				$cur .= 'Version '.substr($cver, 0, 1).'.'.substr($cver, 1, 1);
	
				if (substr($cver, 2, 1) != 0)
				{
					$cur .= '.'.substr($cver, 2, 1);
				}
				
				if ($cver == '100')
				{
					$cur .= ' Final';
				}
			}
		}
	?>	
	
	<h1>Success!!</h1>
	
	<p><b>You have successfully updated to ExpressionEngine <?php echo $cur; ?>!</b><br /><br /></p>
	
	<h2>IMPORTANT:</h2>
	
	<p>
	- It is recommended that you take this opportunity to <b>optimize</b> your database tables.  This action may take some time, so please do not close or refresh your browser window until you are notified that the action is complete.  You may initiate this task by clicking here: <a href="update.php?action=optimize">Optimize Tables</a>
	</p>
	
	<p>
	- Please be sure to read the <a href="http://expressionengine.com/docs/installation/update.html#notes" onclick="window.open(this.href); return false">Version Notes</a> 
	for important information about this version.</p>
	
	<p class="red">
	- Using your FTP program, please delete THIS file (<b>update.php</b>) from your server, as well as the entire <b>updates</b> directory.&nbsp; Leaving these items on your server presents a security risk.
	</p>

	<?php
	}
	/* END */



	/** -----------------------------
	/**  Error Message
	/** -----------------------------*/

	function error_message()
	{
	?>	
	
	<h2>ERROR</h2>
			
	<p>
	An error was encountered while performing this update.&nbsp; 
	</p>
	
	<?php
		if ($this->database_error != FALSE)
		{
			echo '<p>Unable to perform the necessary database queries.<br />Please make sure your hosting account permits you the following GRANT privileges for your database tables:  ALTER, CREATE, DROP</p>';
		}
	?>
	<p>
	Please contact <a href="mailto:support@expressionengine.com">support@expressionengine.com</a> for assistance.
	</p>
	
	
	<?php
	}
	/* END */
	
	
	
	/** -----------------------------
	/**  Create the next link
	/** -----------------------------*/

	function next_link()
	{
		if (count($this->update_files) > 0)
		{
			$from = 'Public Beta 1';

			$fver = ( ! isset($this->conf['app_version']) || $this->conf['app_version'] == 0) ? $from : $this->conf['app_version'];
				
			if ($fver == '009')
			{
				$from = 'Public Beta 2';
			}
			else
			{
				if (is_numeric($fver))
				{
					$from = 'Version '.substr($fver, 0, 1).'.'.substr($fver, 1, 1);
		
					if (substr($fver, 2, 1) != 0)
					{
						$from .= '.'.substr($fver, 2, 1);
					}
				}
			}
			
			$tver = $this->update_files['0'];
			
			$to = ' ';
			
			if ($tver == '009')
			{
				$to .= 'Public Beta 2';
			}
			else
			{
				$to .= 'Version '.substr($tver, 0, 1).'.'.substr($tver, 1, 1);
				
				if (substr($tver, 2, 1) != 0)
				{
					$to .= '.'.substr($tver, 2, 1);
				}
				
				if ($tver == '100')
				{
					$to .= ' Final';
				}
			}
			
			
			$path = 'update'.EXT.'?action=ud';

			echo "<p><br /><a href='".$path."' id='update_link'>Update from {$from}&nbsp; to&nbsp; {$to}</a></p>";
			
		}
	}
	/* END */



	/** -----------------------------
	/**  Fetch Available Updates
	/** -----------------------------*/
	
	// This function reads though the "updates" directory and
	// makes a list of all available updates

	function fetch_update_script_names()
	{
		$cur =  ( ! isset($this->conf['app_version'])) ? 0 : $this->conf['app_version'];
	
		if ( ! $fp = @opendir($this->update_dir)) 
			return false;
			
		$this->newest = $cur;
			
		while (false !== ($file = readdir($fp))) 
		{
			if (substr($file, 0, 3) == 'ud_' && substr($file, -strlen(EXT)) == EXT)
			{			
				$file = str_replace(EXT,  '', $file);
				$file = str_replace('ud_', '', $file);
				
				if ($file > $cur)
				{
					$this->update_files[] = $file;
				}
			}
		} 
		
		closedir($fp); 
		
		if (sizeof($this->update_files) > 0)
		{
			sort($this->update_files, SORT_NUMERIC);
			reset($this->update_files);
		
			$this->newest = end($this->update_files);
			reset($this->update_files);
		}
	}
	/* END */

	
	/** ---------------------------------------
	/**  Check if the config file is writable
	/** ---------------------------------------*/
	
	function is_really_writable($file)
	{
		// is_writable() returns TRUE on Windows servers
		// when you really can't write to the file
		// as the OS reports to PHP as FALSE only if the
		// read-only attribute is marked.  Ugh?
		
		if (($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}
		else
		{
			fclose($fp);
			return TRUE;
		}
	}
	/* END */
	
	
    /** ------------------------------------------
    /**  Fetch a specific core config variable
    /** ------------------------------------------*/
    
    function ini($which = '', $slash = FALSE)
    {
        // Note:  Since many prefs we gather are paths, we use the
        // second parameter to checks whether the trailing slash
        // is present.  If not, we'll add it.
        
        if ($which == '')
            return FALSE;
    
        $pref = ( ! isset($this->conf[$which])) ? FALSE : $this->conf[$which];
                
        if ($pref !== FALSE AND $slash !== FALSE)
        {
        	$pref = rtrim($pref, '/').'/';
        }
        
        if (is_bool($pref))
        {
        	return $pref;
        }

		return str_replace('\\\\', '\\', $pref);
    }
    /* END */
	
	
	/** -----------------------------------------
	/**  Update config file
	/** -----------------------------------------*/
		
	// Note:  The input must be an array

	function update_config_file($newdata = array(), $current_config = array())
	{
		if (count($current_config) == 0)
		{
			require CONFIG_FILE;
		}
		else
		{
			$conf = $current_config;
		}
		
		/** -----------------------------------------
		/**  Write config backup file
		/** -----------------------------------------*/
				
		$old  = "<?php\n\n";
		$old .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);
		
			$old .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$old .= '?'.'>';
		
		$bak_path = str_replace(EXT, '', CONFIG_FILE);
		$bak_path .= '_bak'.EXT;
		
		if ($fp = @fopen($bak_path, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $old, strlen($old));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
		
		/** -----------------------------------------
		/**  Add new data values to config file
		/** -----------------------------------------*/
		
		if (count($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				$val = str_replace("\n", " ", $val);
			
				if (isset($conf[$key]))
				{			
					$conf[$key] = trim($val);	
				}
			}
			
			reset($conf);
		}
		
		/** -----------------------------------------
		/**  Write config file as a string
		/** -----------------------------------------*/
		
		$new  = "<?php\n\n";
		$new .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$new .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
		
		/** -----------------------------------------
		/**  Write config file
		/** -----------------------------------------*/

		if ($fp = @fopen(CONFIG_FILE, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}	
	/* END */
		
		
		
	/** -------------------------------------------
	/**  Append config file 
	/** -------------------------------------------*/
	
	// This function allows us to add new config file elements
	
	// Note:  The input must be an array

	function append_config_file($new_config)
	{
		require CONFIG_FILE;

		if ( ! is_array($new_config))
			return false;
		
		/** -----------------------------------------
		/**  Write config backup file
		/** -----------------------------------------*/
		
		$old  = "<?php\n\n";
		$old .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\'", "'", $val);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("\"", "\\\"", $val);

			$old .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$old .= '?'.'>';
		
		$bak_path = str_replace(EXT, '', CONFIG_FILE);
		$bak_path .= '_bak'.EXT;

		if ($fp = @fopen($bak_path, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $old, strlen($old));			
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
		
		/** -----------------------------------------
		/**  Merge new data to the congig file
		/** -----------------------------------------*/
		
		$conf = array_merge($conf, $new_config);		
				
		$new  = "<?php\n\n";
		$new .= "if ( ! defined('EXT')){\nexit('Invalid file request');\n}\n\n";
	 
		foreach ($conf as $key => $val)
		{
			$val = str_replace("\\'", "'", $val);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("\"", "\\\"", $val);
		
			$new .= "\$conf['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
				
		if ($fp = @fopen(CONFIG_FILE, 'wb'))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Optimize Tables
	/** -------------------------------------*/
	
	function optimize_tables()
	{
		global $DB;
		
		if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
		{
			@set_time_limit(0);
		}
		
		$DB->fetch_tables();
		$sql = '';
		
		foreach ($DB->tables_list as $table)
		{
			switch ($table)
			{
				case 'exp_security_hashes'		:
				case 'exp_sessions'				:
				case 'exp_search'				:
					$DB->query("TRUNCATE `{$table}`");
					break;
				default	:
					$sql .= "`{$table}`, ";
			}
		}

		$DB->query("OPTIMIZE TABLE ".substr($sql, 0, -2));
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Optimize Complete Message
	/** -------------------------------------*/
	
	function optimize_complete_message()
	{
		return <<<OTHELLO
		<h1>Optimization Complete!</h1>

		<p><b>You have successfully optimized your tables!</b><br /><br /></p>

		<h2>DON'T FORGET:</h2>
		<p class="red">
		- Please be sure to read the <a href="http://expressionengine.com/docs/installation/update.html#notes" onclick="window.open(this.href); return false">Version Notes</a> 
		for important information about this version.</p>

		<p class="red">
		- Using your FTP program, please delete THIS file (<b>update.php</b>) from your server, as well as the entire <b>updates</b> directory.&nbsp; Leaving these items on your server presents a security risk.
		</p>	
OTHELLO;
	}
	/* END */
	

	/** -----------------------------
	/**  Page Header
	/** -----------------------------*/
	
	function page_header()
	{
		global $conf;
		
		$xy = explode('/', rtrim($conf['cp_url'], '/'));
		$file = end($xy);
		
		if ($file == 'index.php')
		{
			$img_dir = str_replace('/'.$file, '/updates', rtrim($conf['cp_url'], '/')).'/';
		}
		else
		{
			$img_dir = rtrim($conf['cp_url'], '/').'/updates/';
		}
	
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">

<head>
<title>ExpressionEngine | Update Wizard</title>

<meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<meta http-equiv='expires' content='-1' />
<meta http-equiv= 'pragma' content='no-cache' />

<style type='text/css'>


body {
  margin:             0;
  padding:            0;
  font-family:        Verdana, Geneva, Helvetica, Trebuchet MS, Sans-serif;
  font-size:          12px;
  color:              #333;
  background-color:   #455087;
  }
  
 
a {
  font-size:          12px;
  text-decoration:    underline;
  font-weight:        bold;
  color:              #330099;
  background-color:   transparent;
  }
  
a:visited {
  color:              #330099;
  background-color:   transparent;
  }

a:active {
  color:              #ccc;
  background-color:   transparent;
  }

a:hover {
  color:              #000;
  text-decoration:    none;
  background-color:   transparent;
  }
  
#content {
background:   #fff;
width:        760px;
margin-top: 25px;
margin-right: auto;
margin-left: auto;
border: 1px solid #000;
}

#innercontent {
margin: 20px 30px 0 20px;
}

#pageheader {  
 background: #696EA4 url(<?php echo $img_dir; ?>header_bg.jpg) repeat-x left top;
 border-bottom: 1px solid #000;
}
.solidLine { 
  border-top:  #999 1px solid;
}
.rightheader {  
 background-color:  transparent;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         12px;
 font-weight:		bold;
 color:				#fff;
 padding:			0 22px 0 20px;
}


.error {
  font-family:        Verdana, Trebuchet MS, Arial, Sans-serif;
  font-size:          13px;
  margin-bottom:      8px;
  font-weight:        bold;
  color:              #990000;
}

.shade {
  background-color:   #f6f6f6;
  padding: 10px 0 10px 12px;
  margin-top: 10px;
  margin-bottom: 20px;
  border:      #7B81A9 1px solid;
}

.stephead {
  font-family:        Arial, Trebuchet MS, Verdana, Sans-serif;
  font-size:          18px;
  font-weight:		  bold;
  color:              #999;
  letter-spacing:     2px;
  margin:      			0;
  background-color:   transparent;
}


.settingHead {
  font-family:        Arial, Trebuchet MS, Verdana, Sans-serif;
  font-size:          18px;
  font-weight:		  bold;
  color:              #990000;
  letter-spacing:     2px;
  margin-top:         10px;
  margin-bottom:      10px;
  background-color:   transparent;
}

h1 {
  font-family:        Verdana, Trebuchet MS, Arial, Sans-serif;
  font-size:          16px;
  font-weight:        bold;
  color:              #5B6082;
  margin-top:         15px;
  margin-bottom:      16px;
  background-color:   transparent;
  border-bottom:      #7B81A9 2px solid;
}

h2 {
  font-family:        Arial, Trebuchet MS, Verdana, Sans-serif;
  font-size:          14px;
  color:              #000;
  letter-spacing:     2px;
  margin-top:         6px;
  margin-bottom:      6px;
  background-color:   transparent;
}
h3 {
  font-family:        Arial, Trebuchet MS, Verdana, Sans-serif;
  font-size:          18px;
  color:              #000;
  letter-spacing:     2px;
  margin-top:         15px;
  margin-bottom:      15px;
  border-bottom:      #7B81A9 1px dashed;
  background-color:   transparent;
}

h4 {
  font-family:        Verdana, Geneva, Trebuchet MS, Arial, Sans-serif;
  font-size:          16px;
  font-weight:        bold;
  color:              #000;
  margin-top:         5px;
  margin-bottom:      14px;
  background-color:   transparent;
}
h5 {
  font-family:        Verdana, Geneva, Trebuchet MS, Arial, Sans-serif;
  font-size:          12px;
  font-weight:        bold;
  color:              #000;
  margin-top:         16px;
  margin-bottom:      0;
  background-color:   transparent;
}

p {
  font-family:        Verdana, Geneva, Trebuchet MS, Arial, Sans-serif;
  font-size:          12px;
  font-weight:        normal;
  color:              #333;
  margin-top:         4px;
  margin-bottom:      8px;
  background-color:   transparent;
}

.botBorder {
  margin-bottom:      8px;
  border-bottom:      #7B81A9 1px dashed;
  background-color:   transparent;
}


li {
  font-family:        Verdana, Trebuchet MS, Arial, Sans-serif;
  font-size:          11px;
  margin-bottom:      4px;
  color:              #000;
  margin-left:		  10px;
}

.pad {
padding:  1px 0 4px 0;
}
.center {
text-align: center;
}
strong {
  font-weight: bold;
}

i {
  font-style: italic;
}
  
.red {
  color:              #990000;
}
 
.copyright {
  text-align:         center;
  font-family:        Verdana, Geneva, Helvetica, Trebuchet MS, Sans-serif;
  font-size:          9px;
  color:              #999999;
  line-height:        15px;
  margin-top:         20px;
  margin-bottom:      15px;
  padding:            20px;
  }
  
.border {
  border-bottom:      #7B81A9 1px dashed;
}


form {
 margin:            0;
 padding:           0;
 border:            0;
}
.hidden {
 margin:            0;
 padding:           0;
 border:            0;
}
.input {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 height:            1.7em;
 padding:           0;
 margin:        	0;
} 
.textarea {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 padding:           0;
 margin:        	0;
}
.select {
 background-color:  #fff;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 font-weight:       normal;
 letter-spacing:    .1em;
 color:             #333;
 margin-top:        2px;
 margin-bottom:     2px;
} 
.multiselect {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 background-color:  #fff;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 margin-top:        2px;
 margin-top:        2px;
} 
.radio {
 color:             transparent;
 background-color:  transparent;
 margin-top:        4px;
 margin-bottom:     4px;
 padding:           0;
 border:            0;
}
.checkbox {
 background-color:  transparent;
 color:				transparent;
 padding:           0;
 border:            0;
}
.submit {
 background-color:  #fff;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 font-weight:       normal;
 border-top:		1px solid #989AB6;
 border-left:		1px solid #989AB6;
 border-right:		1px solid #434777;
 border-bottom:		1px solid #434777;
 letter-spacing:    .1em;
 padding:           1px 3px 2px 3px;
 margin:        	0;
 background-color:  #6C73B4;
 color:             #fff;
}  

</style>

</head>

<body>
<div id='content'>
<div id='pageheader'>
<table style="width:100%;" height="50" border="0" cellpadding="0" cellspacing="0">
<tr>
<td style="width:45%;"><img src="<?php echo $img_dir; ?>ee_logo.jpg" width="260" height="80" border="0" alt="ExpressionEngine" /></td>
<td style="width:55%;" align="right" class="rightheader">Update Wizard</td>
</tr>
</table>
</div>
<div id="innercontent">
	<?php
	}
	/* END */
	
	
	
	/** -----------------------------
	/**  Page Footer
	/** -----------------------------*/

	function page_footer()
	{
		return "
			<div class='copyright'>ExpressionEngine by EllisLab - &#169; Copyright 2003 - 2010 - EllisLab, Inc. - All Rights Reserved</div>
			</div>
			</div>
			</body>
			</html>
		";
	}
	/* END */

}
// END CLASS

?>