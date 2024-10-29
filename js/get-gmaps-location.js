// jQuery & GMap based stuff for getting the Author Location from a profile page

jQuery( document ).ready( get_gmaps_location_ready );

function get_gmaps_location_ready()
{
	al_show_map();
}

function al_show_map()
{
	new_location = false;

	// Add in the div to hold the map
	jQuery( '#al_your_location h3' ).after( '<div id="al_map"></div>' );
	// Add an explanation
	jQuery( '#al_map' ).before( '<p>Your current location (if any is set) is shown below as a grey marker. Please drag and zoom around to find your new location on the map below, and click to set it.</p>' );
	
	// Hide the latitude and longitude
	jQuery( '#al_your_location .al_lat_long' ).hide();

	// Add in the actual GMap
	map = new GMap2( document.getElementById( 'al_map' ), {draggableCursor: 'crosshair'} );
	map.setMapType(G_HYBRID_MAP); // Hybrid map, better for bits of the world without roads
	map.addControl( new GLargeMapControl() ); // Full pan/zoom controls
	map.addControl( new GMapTypeControl() ); // Switch between road/satellite and hybrid
	
	var author_location = al_get_author_location();
	
	// Current location icon
	// Like the default (same shadow, etc), but with a custom icon image 
	var currentLocationIcon = new GIcon( G_DEFAULT_ICON, al_plugin_path + '/images/marker-grey.png' );
	
	// Centre the map on the author (if they've entered values)
	if ( author_location ) {
		map.setCenter( author_location, 9 );
		var current_location = new GMarker( author_location, {icon: currentLocationIcon, draggable: false} );
		map.addOverlay( current_location );
		current_location.openInfoWindowHtml( "You are (currently) here." );
	// Otherwise London
	} else {
		map.setCenter( new GLatLng( 51.5, 0 ), 9 );
	}
	
	GEvent.addListener( map, 'click', al_set_new_location);
	
	// If the user previously clicked, and the map has been thrown back for validation, 
	// then we need to show that click on the map.
	var entered_lon = jQuery( '#al_longitude' ).val();
	var entered_lat = jQuery( '#al_latitude' ).val();
	if ( author_location && entered_lat != author_location.lat() && entered_lon != author_location.lng() ) {
		new_location = new GMarker( new GLatLng( entered_lat, entered_lon ), {draggable: false} );
		map.addOverlay( new_location );
		new_location.openInfoWindowHtml( "<div class='al_info_window'>You clicked here a moment ago, please check you've entered the place and country names <a href=\"#al_country_place\">below</a>.</div>" );
	}
}

function al_get_author_location()
{
	author_location = {};
	author_location.lat = jQuery( '#al_latitude_saved' ).val();
	author_location.lon = jQuery( '#al_longitude_saved' ).val();
	// If present, has been validated in PHP
	if ( ! author_location.lat
		&& author_location.lat !== 0 ) {
		return false;
	}
	if ( ! author_location.lon
		&& author_location.lon !== 0 ) {
		return false;
	}
	return new GLatLng( author_location.lat, author_location.lon );
}

function al_set_new_location( overlay, point )
{
	// Discount clicks over the map, which were not actually *on* the map.
	if ( ! point ) return;
	// Where are we?
	var lat = point.lat();
	var lon = point.lng();
	// Remove the old new location (so we don't have more than one new location marker on the map)
	if ( new_location ) map.removeOverlay( new_location );
	// Reset the new_location marker to the new location, and add it to the map
	new_location = new GMarker( new GLatLng( lat, lon ), {draggable: false} );
	map.addOverlay( new_location );
	// Open an info window, so the user knows what's up
	new_location.openInfoWindowHtml( "<div class='al_info_window'><strong>New location latitude and longitude set.</strong> If you're happy with this, please enter the place name and country <a href=\"#al_country_place\">below</a> then update the profile (button at the bottom of the page).</div>" );
	// Set the vars into the lat and long fields
	jQuery( '#al_latitude' ).val( lat );
	jQuery( '#al_longitude' ).val( lon );
}

