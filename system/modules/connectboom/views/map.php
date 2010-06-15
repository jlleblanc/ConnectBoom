<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}
?>
<?php if (isset($data['lat'])): ?>
<script type="text/javascript" charset="utf-8">
	connectboom_pin = {
		lat: '<?php echo $data["lat"] ?>',
		lng: '<?php echo $data["lng"] ?>',
		address: '<?php echo $data["address"] ?>',
		description: '<?php echo $data["description"] ?>'
	}
</script>
<?php endif ?>
<script type="text/javascript" charset="utf-8" src="http://maps.google.com/maps/api/js?v=3.1&amp;sensor=false"></script>
<script type="text/javascript" charset="utf-8" src="http://connectboom.com/system/modules/connectboom/views/jquery-1.4.2.min.js"></script>
<script type="text/javascript" charset="utf-8" src="http://connectboom.com/system/modules/connectboom/views/map.js"></script>
<h1>Connectboom</h1>

<div id="map_canvas" style="width: 500px; height: 300px;"></div>
