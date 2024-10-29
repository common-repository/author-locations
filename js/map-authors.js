// jQuery & GMap based stuff for getting the Author Location from a profile page

jQuery( document ).ready( get_gmaps_location_ready );

function get_gmaps_location_ready()
{
	if ( ! jQuery( '#al_map' ).length ) return;
	al_add_map();
	for ( user_login in authors  ) {
		al_map_author( user_login );
	}
}

function al_add_map()
{
	// Add in the actual GMap
	map = new GMap2( document.getElementById( 'al_map' ) );
	map.setMapType( G_HYBRID_MAP ); // Hybrid map, better for bits of the world without roads
	map.addControl( new GLargeMapControl() ); // Full pan/zoom controls
	map.addControl( new GMapTypeControl() ); // Switch between road/satellite and hybrid
	// Centred on London
	map.setCenter( new GLatLng( 51.5, 0 ), 1 );
}

function al_map_author( user_login )
{
	var author = authors[ user_login ];
	// Some authors may not have locations
	if ( ! author[ 'latitude' ] || ! author[ 'longitude' ] ) return;
	// Reset the new_location marker to the new location, and add it to the map
	var author_location = new GLatLng( author[ 'latitude' ], author[ 'longitude' ] );
	var author_marker = new GMarker( author_location, {draggable: false} );
	map.addOverlay( author_marker );
	// Replace all the JS template tokens
	var tokens = new Array( 
		{ key: "user-login", replacement: author[ 'user_login' ] },
		{ key: "fn", replacement: author[ 'fn' ] },
		{ key: "url", replacement: author[ 'url' ] },
		{ key: "locality", replacement: author[ 'locality' ] },
		{ key: "country-name", replacement: author[ 'country_name' ] },
		{ key: "latitude", replacement: author[ 'latitude' ] },
		{ key: "longitude", replacement: author[ 'longitude' ] },
		{ key: "photo-img-element", replacement: author[ 'photo_img_element' ] },
		{ key: "latest-post-title", replacement: author[ 'latest_post_title' ] },
		{ key: "latest-post-url", replacement: author[ 'latest_post_url' ] },
		{ key: "latest-post-date", replacement: author[ 'latest_post_date' ] },
		{ key: "latest-post-time", replacement: author[ 'latest_post_title' ] },
		{ key: "user-login", replacement: author[ 'user_login' ] }
	);
	var author_info_html = info_html;
	for ( var i = 0; i < tokens.length; i++ ) {
		var re = new RegExp( "{{" + tokens[ i ].key + "}}", "gi" );
		author_info_html = author_info_html.replace( re , tokens[ i ].replacement);
	}
	// Listen for a click
	GEvent.addListener( author_marker, 'click', function( overlay, point ) {
		author_marker.openInfoWindowHtml( author_info_html );
	});
	// Add a "show on map" link
	jQuery( this ).find( '.geo' ).before( '(<a href="#" class="reveal">show on author map</a>).' );
	jQuery( this ).find( '.reveal' ).click( function() { map.panTo( author_marker.getLatLng() ); author_marker.openInfoWindowHtml( author_info_html ); } );
	// Hide lat/long
	jQuery( '.geo' ).hide();
}


