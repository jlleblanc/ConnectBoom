<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

include 'system/modules/connectboom/lib/model.php';

class Model_kml extends Model
{
	public function getData()
	{
		$query = "SELECT field_id_22 AS lat, field_id_23 AS lng, field_id_7 AS address, field_id_4 AS description "
				."FROM exp_weblog_data "
				."WHERE field_id_22 != '' AND field_id_23 != ''";

		$results = $this->db->query($query);

		return $results->result;
	}
}
