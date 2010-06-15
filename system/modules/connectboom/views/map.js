$(document).ready(function() {

	function makeGoogleMap (options, kml) {
		var map = new google.maps.Map($('#map_canvas')[0], myOptions);

		if (kml != '') {
			var georssLayer = new google.maps.KmlLayer('http://www.connectboom.com/index.php/site/kml');
			georssLayer.setMap(map);
		};

		return map;
	}

	if (connectboom_pin.lat == '') {
		var myLatlng = new google.maps.LatLng(38.89, -77.04);
		var myOptions = {
			zoom: 12,
			center: myLatlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};

		makeGoogleMap(myOptions, 'http://www.connectboom.com/index.php/site/kml');
	} else {
		var myLatlng = new google.maps.LatLng(connectboom_pin.lat, connectboom_pin.lng);
		var myOptions = {
			zoom: 9,
			center: myLatlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};

		var map = makeGoogleMap(myOptions, '');
	};

});
