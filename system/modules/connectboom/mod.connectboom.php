<?php

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Connectboom
{
	var $return_data = '';

	/**
	 * Expression Engine seems to only have view support in the control panel,
	 * this is very sad. In place of that, I'm shoehorning a view system in.
	 *
	 * @return void
	 * @author Joseph LeBlanc
	 */
	function Connectboom()
	{
		global $TMPL;

		$view = 'system/modules/connectboom/views/' . $TMPL->fetch_param('view') . '.php';

		if (is_file($view))
		{
			ob_start();
			include $view;
			$this->return_data .= ob_get_contents();
			ob_end_clean();
		}

		return $this->return_data;
	}
}
