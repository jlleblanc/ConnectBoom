<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

// abstract class
class Model
{
	var $db;

	function Model()
	{
		global $DB;

		$this->db = $DB;
	}

	// Abstract method
	function getData() {
		
	}
}
