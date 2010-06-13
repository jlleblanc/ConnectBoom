<?php
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<?php foreach ($data as $row): ?>
	<Placemark>
		<name><?php echo $row['address'] ?></name>
		<description><?php echo $row['description'] ?></description>
		<Point>
			<coordinates><?php echo $row['lng'] . ',' . $row['lat'] ?></coordinates>
		</Point>
	</Placemark>		
<?php endforeach ?>
</kml>
