<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}
?>
<script type="text/javascript" charset="utf-8">
<?php if (isset($data['lat'])): ?>
	var connectboom_pin = {
		lat: '<?php echo $data["lat"] ?>',
		lng: '<?php echo $data["lng"] ?>',
		address: '<?php echo addslashes(trim($data["address"])) ?>',
		description: '<?php echo addslashes(trim($data["description"])) ?>'
	};
<?php else: ?>
	var connectboom_pin;
<?php endif ?>
</script>
<script type="text/javascript" charset="utf-8" src="http://maps.google.com/maps/api/js?v=3.1&amp;sensor=false"></script>
<script type="text/javascript" charset="utf-8" src="http://connectboom.com/system/modules/connectboom/views/jquery-1.4.2.min.js"></script>
<script type="text/javascript" charset="utf-8" src="http://connectboom.com/system/modules/connectboom/views/map.js"></script>
<h1>Connectboom</h1>

<div id="map_canvas" style="width: 500px; height: 300px;"></div>
