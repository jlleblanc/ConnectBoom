<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

include 'system/modules/connectboom/lib/model.php';

class Model_map extends Model
{
	function getData()
	{
		global $TMPL;

		$lat = $TMPL->fetch_param('lat');
		$lng = $TMPL->fetch_param('lng');

		if ($lat && $lng) {
			$query = "SELECT field_id_22 AS lat, field_id_23 AS lng, field_id_7 AS address, field_id_4 AS description "
					."FROM exp_weblog_data "
					."WHERE field_id_22 != '{$lat}' AND field_id_23 != '{$lng}'";

			$results = $this->db->query($query);

			return $results->result;
		}

		return array();
	}
}
