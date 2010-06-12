<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

abstract class Model
{
	protected $db;

	function __construct()
	{
		global $DB;

		$this->db = $DB;
	}

	abstract public function getData();
}
