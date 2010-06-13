<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}
/**
* 
*/
class Connectboom_CP
{
	var $version = '1.0';

	function Connectboom_CP($switch = true)
	{
		global $IN;

		if ($switch)
		{
			switch($IN->GBL('P'))
			{
				case 'view':		$this->view_connectboom();
					break;
				default:			$this->connectboom_home();
					break;
			}
		}
	}

	function view_connectboom()
	{
		
	}

	function connectboom_home()
	{
		
	}

	function connectboom_module_install()
	{
		global $DB;

		$sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend)"
				."VALUES ('', 'Connectboom', '$this->version', 'n')";

		foreach ($sql as $query)
		{
			$DB->query($query);
		}

		return true;
	}

	function connectboom_module_deinstall()
	{
		global $DB;

		$query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Connectboom'"); 

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '". $query->row['module_id']. "'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Connectboom'";

		foreach ($sql as $query)
		{
			$DB->query($query);
		}

		return true;
	}
}
