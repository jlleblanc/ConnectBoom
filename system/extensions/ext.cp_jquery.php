<?php  if ( ! defined('EXT')) exit('No direct script access allowed');
/**
 * jQuery for the Control Panel
 *
 * An ExpressionEngine Extension that allows the loading of jQuery and its
 * UI library for use in the ExpressionEngine Control Panel
 *
 * @package		ExpressionEngine
 * @author		nGen Works and the ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2010, nGen Works and EllisLab, Inc.
 * @license		http://creativecommons.org/licenses/by-sa/3.0/
 * @link		http://www.ngenworks.com/software/ee/cp_jquery/
 * @since		Version 1.1
 * @filesource
 * 
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
 * or send a letter to Creative Commons, 171 Second Street, Suite 300,
 * San Francisco, California, 94105, USA.
 * 
 */
class Cp_jquery {

	var $settings		= array();
	var $name			= 'jQuery for the Control Panel';
	var $version		= '1.1.1';
	var $description	= 'Adds the jQuery javascript library for use in the control panel.';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.ngenworks.com/software/ee/cp_jquery/';

	/**
	 * Constructor
	 */
	function Cp_jquery($settings = '')
	{
		$this->settings = $settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Register hooks by adding them to the database
	 */
	function activate_extension()
	{
		global $DB;

		// default settings
		$settings =	array();
		$settings['jquery_src']		= 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js';
		$settings['jquery_ui_src']	= 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/jquery-ui.min.js';
		
		$hook = array(
						'extension_id'	=> '',
						'class'			=> __CLASS__,
						'method'		=> 'add_js',
						'hook'			=> 'show_full_control_panel_end',
						'settings'		=> serialize($settings),
						'priority'		=> 1,
						'version'		=> $this->version,
						'enabled'		=> 'y'
					);
	
		$DB->query($DB->insert_string('exp_extensions',	$hook));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * No updates yet.
	 * Manual says this function is required.
	 * @param string $current currently installed version
	 */
	function update_extension($current = '')
	{
		global $DB, $EXT;

		if ($current < '1.1.1')
		{
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE class = '".$DB->escape_str(__CLASS__)."'");
			
			$this->settings = unserialize($query->row['settings']);
			unset($this->settings['load_jquery']);
			unset($this->settings['load_jquery_ui']);
			
			$DB->query($DB->update_string('exp_extensions', array('settings' => serialize($this->settings), 'version' => $this->version), array('class' => __CLASS__)));
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Uninstalls extension
	 */
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '".__CLASS__."'");
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * EE extension settings
	 * @return array
	 */
	function settings()
	{
		$settings = array();
		
		$settings['jquery_src']		= '';
		$settings['jquery_ui_src']	= '';
		
		return $settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Adds script tags to the head of CP pages
	 * 
	 * We add the jQuery libraries to the top of the head tag to ensure that they 
	 * are before any other javascript that could use the libraries.
	 * 
	 * @param string $html Final html of the control panel before display
	 * @return string Modified HTML
	 */
	function add_js($html)
	{
		global $EXT;
	
		$html = ($EXT->last_call !== FALSE) ? $EXT->last_call : $html;
	
		$find = '<head>';
		
		$replace  = "<head>\n";
		$replace .= '<script type="text/javascript" src="'.$this->settings['jquery_src'].'"></script>'."\n";
		$replace .= '<script type="text/javascript" src="'.$this->settings['jquery_ui_src'].'"></script>'."\n";
	
		$html = str_replace($find, $replace, $html);
	
		return $html;
	}
	
	// --------------------------------------------------------------------
	
}
// END CLASS Cp_jquery

/* End of file ext.cp_jquery.php */
/* Location: ./system/extensions/ext.cp_jquery.php */