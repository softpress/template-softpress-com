
var geocoder = new google.maps.Geocoder();

function centerMap(options)
{
	return new google.maps.Map(document.getElementById(options.item), options);
}

function placeMarker(map, options)
{
	setTimeout(function() { 
		var marker = new google.maps.Marker(options);
		if(options.content)
			var infowindow = new google.maps.InfoWindow({ content: options.content });
		if(options.animate) {
			google.maps.event.addListener(marker, 'animation_changed', function() {
				if(options.showInfo) 
					infowindow.open(map, marker);
			});
		} else {
			if(options.showInfo) 
				infowindow.open(map, marker);
		}
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.open(map, marker);
		});
	}, options.delay*1000);
}

function geocode(address, cb)
{
	geocoder.geocode({'address':address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			cb(results[0].geometry.location);
		} else {
			alert("Geocode was not successful for the following reason: " + status);
		}
	});
}

function addMarker(map, markerOptions)
{
	if(markerOptions.address) {
		geocode(markerOptions.address, function(latlng) {
			markerOptions.position = latlng;
			markerOptions.map = map;
			if(markerOptions.animate) {
				markerOptions.animation = google.maps.Animation.DROP;
			}
			placeMarker(map, markerOptions);
		});
	}
	else {
		markerOptions.map = map;
		if(markerOptions.animate) {
			markerOptions.animation = google.maps.Animation.DROP;
		}
		placeMarker(map, markerOptions);
	}
}

function fwCreateMap(options) {
	var map;
	if(!options.address) {
		map = centerMap(options);
		for(var i in options.markers)
			addMarker(map, options.markers[i]);
	}
	else {
		geocode(options.address, function(latlng) {
			options.center = latlng;
			map = centerMap(options);
			for(var i in options.markers)
				addMarker(map, options.markers[i]);
		});
	}
}
