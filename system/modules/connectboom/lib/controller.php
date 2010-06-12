<?php

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Controller
{	
	var $return_data = '';

	function display()
	{
		global $TMPL;

		$view = 'system/modules/connectboom/views/' . $TMPL->fetch_param('view') . '.php';

		if (is_file($view))
		{
			$model = 'system/modules/connectboom/models/' . $TMPL->fetch_param('view') . '.php';

			if (is_file($model)) {
				include $model;
				$model_class = 'Model_' . $TMPL->fetch_param('view');
				$model = new $model_class();
				$data = $model->getData();
			} else {
				$data = array();
			}

			ob_start();
			include $view;
			$this->return_data .= ob_get_contents();
			ob_end_clean();
		}

		return $this->return_data;
	}
}
