<?php

class Connectboom
{
	var $return_data = '';

	function Connectboom()
	{

	}

	function index()
	{
		$this->return_data = 'connectboom';
		return $this->return_data;
	}
}
