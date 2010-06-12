<?php  if ( ! defined('EXT')) exit('No direct script access allowed');
/**
 * Geotagger
 *
 * An ExpressionEngine Extension that geotags addresses.
 *
 * @package		ExpressionEngine
 * @author		Natural Logic
 * @copyright	Copyright (c) 2009, Natural Logic LLC
 * @link		http://www.natural-logic.com/software/geotagger-for-expression-engine/
 * @since		Version 1
 * 
 */

if ( ! defined('NL_GO_version')){
	define("NL_GO_version",			"1.1.4");
	define("NL_GO_docs_url",		"http://www.natural-logic.com/software/geotagger-for-expression-engine/");
	define("NL_GO_addon_id",		"Geotagger");
	define("NL_GO_label_class",		"geotagger");
	define("NL_GO_cache_name",		"nlogic_geotagger");
}

class Geotagger {

	var $settings		= array();
	var $name			= 'Geotagger';
	var $version		= NL_GO_version;
	var $description	= 'Geotags addresses and returns latitude/longitude points';
	var $settings_exist	= 'y';
	var $docs_url		= NL_GO_docs_url;
	var $google_maps_api_key_conf = FALSE;

	/**
	 * Constructor PHP 4
	 */
	function Geotagger($settings = '')
	{
		$this->__construct($settings);
	}

	/**
	* PHP 5 Constructor
	*
	* @param	$settings	mixed	Array with settings or FALSE
	* @return	void
	*/
	function __construct( $settings="" )
	{
		global $IN, $SESS;
		$this->settings = $this->_get_settings();
	}
	
	/**
	 * EE extension settings
	 * 
	 */
	
	function _get_settings( $refresh = FALSE, $return_all = FALSE )
	{
		global $SESS, $DB, $REGX, $LANG, $PREFS;

		$settings = FALSE;

		// Get the settings for the extension
		if(isset($SESS->cache[NL_GO_cache_name]['settings']) === FALSE || $refresh === TRUE)
		{
			// check the db for extension settings
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '".__CLASS__."' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows > 0 && $query->row['settings'] != '')
			{
				// save them to the cache
				$SESS->cache[NL_GO_cache_name]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}

		// check to see if the session has been set
		if(empty($SESS->cache[NL_GO_cache_name]['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $SESS->cache[NL_GO_cache_name]['settings'] : $SESS->cache[NL_GO_cache_name]['settings'][$PREFS->ini('site_id')];
		}
		
		$this->google_maps_api_key_conf = (isset($PREFS->core_ini['nl_go_api']) ? $PREFS->core_ini['nl_go_api'] : FALSE); 
		
		if ($this->google_maps_api_key_conf) 
		{
			$settings['google_maps_api_key'] = $this->google_maps_api_key_conf;	
		}
		
		return $settings;
	}
	
	/**
	 * activate extension and register hooks
	 * 
	 */
	function activate_extension()
	{
		global $DB;

		$default_settings = $this->_build_default_settings();

		$query = $DB->query("SELECT * FROM exp_sites");

		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$settings[$row['site_id']] = $default_settings;
			}
		}

		$query = $DB->query("SELECT * FROM exp_weblogs");

		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$settings[$row['site_id']]['weblogs'][$row['weblog_id']] = array(
					'display_tab'		=> 'n',
					'address'	=> '',
					'city'	=> '',
					'state'	=> '',
					'zip'	=> '',
					'latitude'	=> '',
					'longitude'	=> '',
					'show_fields_in_geo' => 'n',
					'zoom_level' => '13'
				);
			}
		}
		
		$hooks = array(
			'publish_form_new_tabs'					=> 'publish_form_new_tabs',
			'publish_form_new_tabs_block'			=> 'publish_form_new_tabs_block',
			'publish_form_start'					=> 'publish_form_start'
		);

		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
											array('extension_id' 	=> '',
												'class'			=> get_class($this),
												'method'		=> $method,
												'hook'			=> $hook,
												'settings'		=> addslashes(serialize($settings)),
												'priority'		=> 10,
												'version'		=> $this->version,
												'enabled'		=> "y"
											)
										);
		}

		foreach ($sql as $query)
		{
			$DB->query($query);
		}
	}
	
	/**
	 * Update extension
	 * 
	 */
	function update_extension($current = '')
	{
		global $DB, $EXT;
		
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }
		
	    $DB->query("UPDATE exp_extensions 
               SET version = '".$DB->escape_str($this->version)."' 
               WHERE class = '".__CLASS__."'");
	}
	
	/**
	 * Uninstall extension
	 *
	 */
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '".get_class($this)."'");
	}

	/**
	 * Extension settings form
	 * 
	 */
	function settings_form( $current )
	{
		global $DB, $DSP, $LANG, $IN, $PREFS, $SESS;

		$settings = $this->_get_settings();

		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('label_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));

		$DSP->crumb .= $DSP->crumb_item($LANG->line('label_title') . " <small>{$this->version}</small>");

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_label_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));
		
		$DSP->body = '';

		$DSP->body .= $DSP->heading($LANG->line('label_title') . " <small>{$this->version}</small>");
		
		$DSP->body .= $DSP->form_open(
								array(
									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'
								),
								array('name' => strtolower(__CLASS__))
		);
	
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));
		
		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("label_site_settings")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '30%')
			. $DSP->qdiv('defaultBold', $LANG->line('label_enable_site'))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. "<select name='enable'>"
						. $DSP->input_select_option('y', "Yes", (($settings['enable'] == 'y') ? 'y' : '' ))
						. $DSP->input_select_option('n', "No", (($settings['enable'] == 'n') ? 'n' : '' ))
						. $DSP->input_select_footer()
			. $DSP->td_c()
			. $DSP->tr_c()
			. $DSP->table_c();
			
		// Google Maps API Key
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));
		
		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("label_google_maps")
			. $DSP->td_c()
			. $DSP->tr_c();	
					
		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '30%')
			. $DSP->qdiv('defaultBold', $LANG->line('label_google_maps_api_key'))
			. $DSP->td_c()
			. $DSP->td('tableCellOne');
			
		if ($this->google_maps_api_key_conf)
		{
			$DSP->body .= $DSP->input_text('google_maps_api_key', $settings['google_maps_api_key'], '200', '120', 'input', '620px', 'readonly')
				. '<span style="color:#666;display:block;font-style:italic;">'.$LANG->line('label_config').'</span>';
		}else
		{
			$DSP->body .= $DSP->input_text('google_maps_api_key', $settings['google_maps_api_key'], '200', '120', 'input', '620px', '');
		}

		$DSP->body .= $DSP->td_c()
			. $DSP->tr_c()
			. $DSP->table_c();
			
		// Display Tab and Geotagger Field Mappings
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '3')
			. $LANG->line("label_weblog_settings")
			. $DSP->td_c()
			. $DSP->tr_c();

		$weblogs = $DB->query("SELECT * FROM exp_weblogs WHERE site_id = " . $PREFS->ini('site_id'));
		
		$DSP->body .= "<script type='text/javascript'>
				function toggleFieldMappings(val, weblog_id) {
					if (val == 'n') {
						$('.field_map_'+weblog_id).hide();
					}else{						
						$('.field_map_'+weblog_id).show();
					}					
				}
				</script>";
				
		$gypsy_installed = $DB->query("SHOW COLUMNS FROM exp_weblog_fields LIKE 'gypsy_weblogs'");

		if ($weblogs->num_rows > 0)
		{
			$i = 0;
			
			foreach($weblogs->result as $row)
			{				
				$class = ($i % 2) ? 'tableCellTwo':'tableCellOne';
				$weblog_settings = array();
				
				
				if (isset($this->settings['weblogs'][$row['weblog_id']]))
				{
					$weblog_settings = $this->settings['weblogs'][$row['weblog_id']];
				}else
				{
					$weblog_settings =  array(
											'display_tab' 		=> 'n',
											'address'	=> '',
											'city'	=> '',
											'state'	=> '',
											'zip'	=> '',
											'latitude'	=> '',
											'longitude'	=> '',
											'show_fields_in_geo' => 'n',
											'zoom_level' => '13'
											);						
				}	
				
				// find the fields for this weblog
				if ($gypsy_installed->num_rows > 0)
				{
					$fields = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = ".$row['field_group']." OR gypsy_weblogs LIKE '%".$row['weblog_id']."%' ORDER BY field_label");
				}else
				{
					$fields = $DB->query("SELECT field_id, field_label FROM exp_weblog_fields WHERE group_id = ".$row['field_group']." ORDER BY field_label");
				}				

				$DSP->body .= $DSP->tr()
					. "<td class='{$class}' valign='top' width='30%'>"
					. $DSP->qdiv('defaultBold', $row['blog_title']);
					
				$DSP->body .= "<td class='{$class}'>"
					."<table><tr><td width='200px'>"
					. "<small>".$LANG->line('label_display_tab').":</small>"
					. $DSP->td_c()
					. "<td>"
					. "<select name='weblogs[{$row['weblog_id']}][display_tab]' onchange='toggleFieldMappings(this.value,".$row['weblog_id'].")'>"
						. $DSP->input_select_option('y', "Yes", (($weblog_settings['display_tab'] == 'y') ? 'y' : '' ))
						. $DSP->input_select_option('n', "No", (($weblog_settings['display_tab'] == 'n') ? 'y' : '' ))
						. $DSP->input_select_footer()
					. $DSP->td_c()
					. $DSP->tr_c();

				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
									
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_map_zoom_level').":</small>"
					. $DSP->td_c()
					. "<td>"
					. "<input name='weblogs[{$row['weblog_id']}][zoom_level]' value='".$weblog_settings['zoom_level']."' size='5' />"				
					. $DSP->td_c()
					. $DSP->tr_c();
				
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
									
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_show_fields_in_geo').":</small>"
					. $DSP->td_c()
					. "<td>"
					. "<select name='weblogs[{$row['weblog_id']}][show_fields_in_geo]'>"
					. $DSP->input_select_option('y', "Yes", (($weblog_settings['show_fields_in_geo'] == 'y') ? 'y' : '' ))
					. $DSP->input_select_option('n', "No", (($weblog_settings['show_fields_in_geo'] == 'n') ? 'y' : '' ))
					. $DSP->input_select_footer()				
					. $DSP->td_c()
					. $DSP->tr_c();
					
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}						
				$DSP->body .= "><td colspan='2'><strong><small>".$LANG->line('label_fm_heading')."</small></strong></td></tr>";
					
				//address
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_address').":</small>"
					. $DSP->td_c()
					. "<td>";
					
				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][address]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .= $DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['address'] == $field['field_id']) ? 'y' : ''));
					}
					
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c();
					
				//city
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_city').":</small>"
					. $DSP->td_c()
					. "<td>";	

				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][city]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .=$DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['city'] == $field['field_id']) ? 'y' : ''));
					}
					$DSP->body .= $DSP->input_select_option(0, $LANG->line('label_fm_na'),(($weblog_settings['city'] == 0) ? 'y' : ''));
				
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c();
					
				//state
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_state').":</small>"
					. $DSP->td_c()
					. "<td>";
					
				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][state]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .=$DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['state'] == $field['field_id']) ? 'y' : ''));
					}
					$DSP->body .= $DSP->input_select_option(0, $LANG->line('label_fm_na'),(($weblog_settings['state'] == 0) ? 'y' : ''));
				
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c();
					
				//zip
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_zip').":</small>"
					. $DSP->td_c()
					. "<td>";
					
				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][zip]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .=$DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['zip'] == $field['field_id']) ? 'y' : ''));
					}
					$DSP->body .= $DSP->input_select_option(0, $LANG->line('label_fm_na'),(($weblog_settings['zip'] == 0) ? 'y' : ''));
				
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c();					
					
				//latitude
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_latitude').":</small>"
					. $DSP->td_c()
					. "<td>";
					
				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][latitude]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .=$DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['latitude'] == $field['field_id']) ? 'y' : ''));
					}
				
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c();					

				//longitude
				$DSP->body .= "<tr class='field_map_".$row['weblog_id']."'";
				if ($weblog_settings['display_tab'] == 'n')
				{
					$DSP->body .= " style='display:none'";
				}
				
				$DSP->body .= "><td width='200px'>"
					. "<small>".$LANG->line('label_fm_longitude').":</small>"
					. $DSP->td_c()
					. "<td>";
					
				if ($fields->num_rows > 0)
				{
					$DSP->body .= "<select name='weblogs[{$row['weblog_id']}][longitude]'>";
					
					foreach($fields->result as $field)	
					{
						$DSP->body .=$DSP->input_select_option($field['field_id'], $field['field_label'],(($weblog_settings['longitude'] == $field['field_id']) ? 'y' : ''));
					}
				
					$DSP->body .=	$DSP->input_select_footer();
				}else
				{
					$DSP->body .= $LANG->line('label_fm_none');
				}
				
				$DSP->body .= $DSP->td_c()
					. $DSP->tr_c()
					. $DSP->table_c();
				$i++;
			}
		}
		else
		{
			$DSP->body .= "<tr><td colspan='2' class='tableCellOne'><p class='highlight'>" . $LANG->line('no_weblogs_msg') . "</p></td></tr>";
		}

		$DSP->body .= $DSP->table_c();

		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit())
					. $DSP->form_c();
	}

	/**
	 * Save extension form settings
	 * 
	 */	
	function save_settings()
	{
		global $DB, $IN, $PREFS, $REGX, $SESS;

		$default_settings = $this->_build_default_settings();

		$_POST = $REGX->xss_clean(array_merge($default_settings, $_POST));

		unset($_POST['name']);

		foreach ($_POST['weblogs'] as $key => $value)
		{
			unset($_POST['weblogs_' . $key]);
		}

		$settings = $this->_get_settings(TRUE, TRUE);

		$settings[$PREFS->ini('site_id')] = $_POST;

		$query = $DB->query($sql = "UPDATE exp_extensions SET settings = '" . addslashes(serialize($settings)) . "' WHERE class = '".$DB->escape_str(__CLASS__)."'");
	}

	/**
	 * Build base settings
	 * 
	 */	
	function _build_default_settings()
	{
		global $DB, $PREFS;

		$default_settings = array(
								'enable' 				=> 'y',
								'weblogs'				=> array(),
								'google_maps_api_key'	=> '',
							);

		$query = $DB->query("SELECT * FROM exp_weblogs WHERE site_id = '".$PREFS->core_ini['site_id']."'");

		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				$default_settings['weblogs'][$row['weblog_id']] = array(
					'display_tab' 		=> 'n',
					'address'	=> '',
					'city'	=> '',
					'state'	=> '',
					'zip'	=> '',
					'latitude'	=> '',
					'longitude'	=> '',
					'show_fields_in_geo' => 'n',
					'zoom_level' => '13'
				);
			}
		}
		return $default_settings;
	}

	/**
	 * Get the entry id for use later
	 * 
	 */	
	function publish_form_start( $which, $submission_error, $entry_id, $hidden )
	{
		global $IN, $SESS;

		$weblog_id = $IN->GBL("weblog_id");

		if(empty($entry_id) === TRUE) $entry_id = $IN->GBL("entry_id");

		$SESS->cache[NL_GO_cache_name]['publish_form_entry_id'] = $entry_id;
	}

	/**
	 * Add the Geotagger tab to publish form
	 * 
	 */
	function publish_form_new_tabs( $publish_tabs, $weblog_id, $entry_id, $hidden )
	{
		global $EXT, $PREFS, $SESS, $LANG;

		if($EXT->last_call !== FALSE)
		{
			$publish_tabs = $EXT->last_call;
		}
		
		$LANG->fetch_language_file('geotagger');	

		if( $this->settings['enable'] == 'y' && ( isset($this->settings['weblogs'][$weblog_id]) && $this->settings['weblogs'][$weblog_id]['display_tab'] == "y" )
		)
		{
			$publish_tabs['gcnl'] = $LANG->line('label_title');
		}
		return $publish_tabs;
	}

	/**
	 * Add content to the Geotagger tab
	 * 
	 */	
	function publish_form_new_tabs_block( $weblog_id )
	{
		global $DB, $EXT, $PREFS, $SESS, $LANG, $REGX, $IN, $DSP;

		$LANG->fetch_language_file('geotagger');		
				
		$entry_id = $SESS->cache[NL_GO_cache_name]['publish_form_entry_id'];

		$ret = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';

		if(	$this->settings['enable'] == 'y' && ( isset($this->settings['weblogs'][$weblog_id]) && $this->settings['weblogs'][$weblog_id]['display_tab'] == "y" )
		)
		{			
			$ret .= "<!-- Start Geotagger Tab -->";
			$ret .= "<div id='blockgcnl' style='display:none'>";
			$ret .= $DSP->div('publishTabWrapper');
			$ret .= $DSP->div('publishBox');
  			$ret .= '<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key='.trim($this->settings['google_maps_api_key']).'"></script>'.NL;
			$ret .= '<script type="text/javascript" src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key='.trim($this->settings['google_maps_api_key']).'"></script>'.NL;

			$ret .= '
			<script type="text/javascript">
			//<![CDATA[
				
			var addr_field_id;
			var city_field_id;
			var st_field_id;
			var zip_field_id;
			var lat_field_id;
			var lng_field_id;
			var fade_bg = "#fec424";
			var map_zoom_level;
					
			$(function() {
					addr_field_id = '.$this->settings['weblogs'][$weblog_id]['address'].';
					city_field_id = '.$this->settings['weblogs'][$weblog_id]['city'].';	
					st_field_id = '.$this->settings['weblogs'][$weblog_id]['state'].';
					zip_field_id = '.$this->settings['weblogs'][$weblog_id]['zip'].';
					lat_field_id = '.$this->settings['weblogs'][$weblog_id]['latitude'].';
					lng_field_id = '.$this->settings['weblogs'][$weblog_id]['longitude'].';	
					map_zoom_level = '.(($this->settings['weblogs'][$weblog_id]['zoom_level'] == "") ? 13 : $this->settings['weblogs'][$weblog_id]['zoom_level']).';
			';
					
			if ($this->settings['weblogs'][$weblog_id]['show_fields_in_geo'] == "y")
			{
				
			$ret .= '									
					var addr_parent_div = $("#field_pane_on_"+addr_field_id).parent();
					addr_parent_div.appendTo("#geo_form");
																			
					var city_parent_div = $("#field_pane_on_"+city_field_id).parent();
					city_parent_div.appendTo("#geo_form");

					var st_parent_div = $("#field_pane_on_"+st_field_id).parent();
					st_parent_div.appendTo("#geo_form");
					
					var zip_parent_div = $("#field_pane_on_"+zip_field_id).parent();
					zip_parent_div.appendTo("#geo_form");
					
					var lat_parent_div = $("#field_pane_on_"+lat_field_id).parent();
					lat_parent_div.appendTo("#geo_form");
					
					var lng_parent_div = $("#field_pane_on_"+lng_field_id).parent();
					lng_parent_div.appendTo("#geo_form");
					';
			}
			
			$ret .= '
					
					$("#gcnl").bind("click", function(e) {
						nlEditGeo(lat_field_id, lng_field_id);
					});					
			})			

		    function nlEditGeo(lat_field_id, lng_field_id) {
				if (GBrowserIsCompatible()) {
					
					var lat_field = $("#field_id_"+lat_field_id);
					var lng_field = $("#field_id_"+lng_field_id);
					
					if (lat_field.val().length > 0 && lng_field.val().length > 0) {
						var map = new GMap2(document.getElementById("geo_map_canvas"), {size:new GSize(500,300)});
						var point = new GLatLng(lat_field.val(), lng_field.val());
						map.setCenter(point, map_zoom_level);
						map.setUIToDefault();
						map.disableScrollWheelZoom();
			            var marker = new GMarker(point, {draggable: true});
	
				        GEvent.addListener(marker, "dragstart", function() {
				          map.closeInfoWindow();
				        });

				        GEvent.addListener(marker, "dragend", function() {
					      var point = marker.getLatLng();
					      lat_field.val(point.lat());
					  	  lng_field.val(point.lng());
					      $("#geo_messages").html("' . $LANG->line('msg_lat_updated') . ' "+point.lat()+"<br/>' . $LANG->line('msg_lng_updated') . ' "+point.lng())
						  $("#geo_messages").show();
						  $("#geo_messages").effect( "highlight" , { color: fade_bg }, 3000);						     
				        });	
				  		
			            map.addOverlay(marker);						
					}					
				}
			}
			
			function nlGeo() {
				 if (GBrowserIsCompatible()) {
					
					// make sure geo messages are hidden
					$("#geo_messages").hide();
					
					var field_values = new Array();
					
					// get the field values
					var addr_field = (addr_field_id != 0) ?  $("#field_id_"+addr_field_id).val() : "";
					if (addr_field === undefined) {
						addr_field =  $("select[name=\'field_id_"+addr_field_id+"\'] :selected").text();
						if (addr_field === undefined) {
							addr_field = "";
						}
					}				

					var city_field = (city_field_id != 0) ?  $("#field_id_"+city_field_id).val() : "";					
					if (city_field === undefined) {
						city_field =  $("select[name=\'field_id_"+city_field_id+"\'] :selected").text();					
						if (city_field === undefined) {
							city_field = "";
						}
					}
					
					var st_field = (st_field_id != 0) ?  $("#field_id_"+st_field_id).val() : "";				
					if (st_field === undefined) {
						st_field =  $("select[name=\'field_id_"+st_field_id+"\'] :selected").text();
						if (st_field === undefined) {
							st_field = "";
						}
					}
					
					var zip_field = (zip_field_id != 0) ?  $("#field_id_"+zip_field_id).val() : "";
					
					if (addr_field != "") {
						field_values.push(addr_field);	
					}

					if (city_field != "") {
						field_values.push(city_field);	
					}		

					if (st_field != "") {
						field_values.push(st_field);	
					}
					
					if (zip_field != "") {
						field_values.push(zip_field);	
					}	
					
					var map = new GMap2(document.getElementById("geo_map_canvas"), {size:new GSize(500,300)});
					var geotagger = new GClientGeocoder();
					
					var address = ""; 
					
					for(var z=0; z<field_values.length; z++) {
						address += field_values[z];
						address += (z+1 != field_values.length) ? ", " : "";
					}
					
					var regex_uk = new RegExp("(GIR 0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]|[A-HK-Y][0-9]([0-9]|[ABEHMNPRV-Y]))|[0-9][A-HJKS-UW])[ ]*[0-9][ABD-HJLNP-UW-Z]{2})", "i");
					var local;
					
					if (address.match(regex_uk)) {
						local = new GlocalSearch();
						
						local.setSearchCompleteCallback(null, function() {	
							if (local.results[0]) {
								displayMap(address, map, new GLatLng(local.results[0].lat, local.results[0].lng));
							}
						});
						
						local.execute(address + ", UK");
						
					}else{
						if (geotagger) {
					        geotagger.getLatLng(
					          address,
					          function(point) {
					            if (!point) {
					              $("#geo_messages").html("' . $LANG->line('msg_geo_error') . '");
								  $("#geo_messages").show();	
								  $("#geo_messages").effect( "highlight" , { color: fade_bg }, 3000);
					            } else {							  
									displayMap(address, map, point);
					            }
					          }
					        );
						
						}						
					}
 				}
			}
			
			function displayMap(address, map, point) {
				  var lat_field = $("#field_id_"+lat_field_id);
				  var lng_field = $("#field_id_"+lng_field_id);
									
	              map.setCenter(point, map_zoom_level);
				  map.setUIToDefault();
				  
				  lat_field.val(point.lat());
				  lng_field.val(point.lng());
				
				  $("#geo_messages").html("' . $LANG->line('msg_geo_address') . ' "+address+"<br/>' . $LANG->line('msg_lat_updated') . ' "+point.lat()+"<br/>' . $LANG->line('msg_lng_updated') . ' "+point.lng());
				  $("#geo_messages").show();	
				  $("#geo_messages").effect( "highlight" , { color: fade_bg }, 3000);	
				
	              var marker = new GMarker(point, {draggable: true});
	
			        GEvent.addListener(marker, "dragstart", function() {
			          map.closeInfoWindow();
			        });

			        GEvent.addListener(marker, "dragend", function() {
				      var point = marker.getLatLng();
				      lat_field.val(point.lat());
				  	  lng_field.val(point.lng());
				      $("#geo_messages").html("' . $LANG->line('msg_lat_updated') . ' "+point.lat()+"<br/>' . $LANG->line('msg_lng_updated') . ' "+point.lng())
					  $("#geo_messages").show();
					  $("#geo_messages").effect( "highlight" , { color: fade_bg }, 3000);						     
			        });	
				  		
	              map.addOverlay(marker);				
			}
			//]]>
			</script>
			<style type="text/css">
			    #geo_wrap { clear:both; overflow:auto; width:920px; margin-bottom: 20px; margin-top: 10px; }
			    #geo_map_canvas { width: 500px; height:300px; float: right; margin-left: 20px; }
			    #geo_details { width: 400px; float: left; }
				#geo_details p { padding: 1em; margin-right: 20px; margin-bottom: 10px }
				#geo_details p#geo_messages { background:#ffffcc; border:1px solid #cccc99; padding:1em; display:none; margin-right:20px; margin-left: 10px; }
				#geo_details a.btn { background: #142129; color: #ffffff; -moz-border-radius: 11px; -webkit-border-radius: 11px; padding: 6px 14px; }
				#geo_details a:hover.btn { background: #1D7FC6 }
				#geo_form { border-top:1px solid #B1B6D2; }
			</style>
			';
			$ret .= "<div id='geo_wrap'>";
			$ret .= "<div id='geo_details'>";
			if ($this->settings['weblogs'][$weblog_id]['show_fields_in_geo'] == "n")
			{			
				if ($entry_id != '')
				{
					$ret .= "<p><strong>" . $LANG->line('msg_existing_geo') . "</strong></p>";
				}else
				{
					$ret .= "<p><strong>" . $LANG->line('msg_before_geo') . "</strong></p>";
				}
			}	
			$ret .= "<p id='geo_messages'></p>";
			if ($this->settings['weblogs'][$weblog_id]['show_fields_in_geo'] == "y")
			{
				$ret .= "<div id='geo_form'></div>";
			}
			if ($entry_id != '')
			{
				$ret .= "<p><a href='javascript:nlGeo();' title='" . $LANG->line('btn_geo_update') . "' class='btn'>" . $LANG->line('btn_geo_update') . " &rarr;</a></p>";
			}else
			{
				$ret .= "<p><a href='javascript:nlGeo();' title='" . $LANG->line('btn_geo') . "' class='btn'>" . $LANG->line('btn_geo') . " &rarr;</a></p>";
			}	
			$ret .= "</div>";	
			$ret .= "<div id='geo_map_canvas'></div>";		
			$ret .= "</div>";	
			

			$ret .= $DSP->div_c();
			$ret .= $DSP->div_c();
			$ret .= $DSP->div_c();
			$ret .= "<!-- End Geotagger Tab -->";
		}
		return $ret;
	}
	
	
}
// END CLASS Geotagger

/* End of file ext.geotagger.php */
/* Location: ./system/extensions/ext.geotagger.php */