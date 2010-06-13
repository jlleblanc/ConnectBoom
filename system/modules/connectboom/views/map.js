$(document).ready(function() {
	var myLatlng = new google.maps.LatLng(38.89, -77.04);
	var myOptions = {
		zoom: 12,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	var map = new google.maps.Map($('#map_canvas')[0], myOptions);

	var georssLayer = new google.maps.KmlLayer('http://www.connectboom.com/index.php/site/kml');

	console.debug(georssLayer);

	georssLayer.setMap(map);
});
