<?php

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

include 'system/modules/connectboom/lib/controller.php';

class Connectboom extends Controller
{
	function Connectboom()
	{
		$this->display();
	}
}
