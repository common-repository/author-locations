<?php
/*
Plugin Name: Author Location
Plugin URI: http://wordpress.org/extend/plugins/author-locations/
Description: Provides author location facility.
Author: Simon Wheatley
Version: 1.0
Author URI: http://simonwheatley.co.uk/wordpress/
*/

/*
======================================================================================
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This script is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.
======================================================================================
@author     Simon Wheatley (http://simonwheatley.co.uk)
@version    0.1
@copyright  Copyright &copy; 2008 Simon Wheatley, All Rights Reserved
======================================================================================
*/

require_once ( dirname (__FILE__) . '/author.php' );
require_once ( dirname (__FILE__) . '/plugin.php' );
require_once ( dirname (__FILE__) . '/utility.php' );

require_once( ABSPATH . 'wp-includes/class-snoopy.php' );
require_once( ABSPATH . '/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php' );

/*
 * @package default
 * @author Simon Wheatley
 * @copyright Copyright (C) Simon Wheatley
 **/

class AuthorLocation extends AuthorLocation_Plugin
{
	
	/**
	 * An array of errors to show the user.
	 *
	 * @var Array
	 **/
	protected $errors = array();
	
	/**
	 * An array of user error messages.
	 *
	 * @var Array (of translatable strings)
	 **/
	protected $error_msgs = array();
	
	/**
	 * An array of user entered location data
	 * usually before save, and possibly invalid
	 *
	 * @var Array
	 **/
	protected $location_data = array();
	
	/**
	 *
	 * @var Array
	 **/
	protected $published_author_ids = array();
	
	/**
	 *
	 * @var Array
	 **/
	protected $published_authors = array();
	
	/**
	 *
	 **/
	protected $utility;
	
	// HTTP client stuff
	
	protected $user_agent;

	protected $fetch_time_out;

	protected $use_gzip;
	
	/**
	 * Constructor for this class. 
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function __construct()
	{
		$this->init_error_messages();
		$this->register_plugin( 'author-location', __FILE__ );
		// Everything OK?
		$this->add_action( 'init', 'sanity_checks' );
		// Add our scripts, CSS, etc in to the main blog viewing screens
		$this->add_action( 'wp_head', 'view_enqueuing', 0 );
		$this->add_action( 'wp_head', 'info_window_html' );
		// Show the new location panel in the user profile edit screen
		$this->add_action( 'edit_user_profile', 'location_dialog' );
		$this->add_action( 'show_user_profile', 'location_dialog' );
		// Grab values when POSTed
		$this->add_action( 'load-profile.php', 'load_profile' );
		$this->add_action( 'load-user-edit.php', 'load_profile' );
		// Add some JS vars into the page
		$this->add_action( 'admin_print_scripts-profile.php', 'js_paths', 5 );
		$this->add_action( 'admin_print_scripts-user-edit.php', 'js_paths', 5 );
		// Encode any errors or notices to the redirect URL (what a pain)
		$this->add_filter( 'wp_redirect', 'append_errors' );
		// Append currently entered location data, for the user to correct (if necessary)
		$this->add_filter( 'wp_redirect', 'append_location_data' );
		// Show any errors or notices
		$this->add_action( 'admin_notices' );
		// Shortcode
		$this->add_shortcode ('map-authors', 'shortcode_map_authors');
		// HTTP client stuff
		$this->user_agent = 'WordPress/' . $GLOBALS['wp_version'];
		$this->fetch_time_out = 5; // 5 second timeout
		$this->use_gzip = true; // Use gzip encoding to fetch remote files if supported
		// Utility class
		$this->utility = new AuthorLocationUtility();
	}
	
	function sanity_checks()
	{
		// Check there's a constant defining the GOOGLE_MAPS_API_KEY
		if ( ! defined( 'GOOGLE_MAPS_API_KEY' ) ) {
			$this->errors[] = 'no_api_key_defined';
		// Check the constant is sane
		} else if ( GOOGLE_MAPS_API_KEY == '' || GOOGLE_MAPS_API_KEY == 'your-key-goes-here' ) {
			$this->errors[] = 'api_key_looks_wrong';
		}
	}
	
	function init_error_messages()
	{
		// Useful var
		$loc_anchor = '#al_your_location';
		$place_anchor = "#al_country_place";
		// User data validation messages
		$this->error_msgs[ 'no_lat_or_long' ] = sprintf( __( 'You must enter a latitude and a longitude for <a href="%s">your location</a>.' ), $loc_anchor );
		$this->error_msgs[ 'no_place_name' ] = sprintf( __( 'Please enter a <a href="%s">place name</a> for your location.' ),  $place_anchor );
		$this->error_msgs[ 'no_country' ] = sprintf( __( 'Please enter a <a href="%s">country</a> for your location.' ),  $place_anchor );
		$this->error_msgs[ 'invalid_latitude' ] = sprintf( __( 'Please enter <a href="%s">the latitude</a> as a decimal number between -90 and +90' ), $loc_anchor );
		$this->error_msgs[ 'invalid_longitude' ] = sprintf( __( 'Please enter <a href="%s">the longitude</a> as a decimal number between -180 and +180' ),  $loc_anchor );
		// API key messages
		$this->error_msgs[ 'no_api_key_defined' ] = __( 'Please add a line into your wp-config.php as follows (you can get a Google Maps API key <a href="http://code.google.com/apis/maps/signup.html" title="signup for a Google Maps API key">from here</a>): <br /><code><strong>define( "GOOGLE_MAPS_API_KEY", "your-key-goes-here" );</strong></code>. <br />No data can be saved until you have entered this key.' );
		$this->error_msgs[ 'api_key_looks_wrong' ] = __( 'Please check your wp-config.php file, to ensure that your <a href="http://code.google.com/apis/maps/signup.html" title="signup for a Google Maps API key">Google Maps API key</a> is in correctly. It should look like this: <br /><code><strong>define( "GOOGLE_MAPS_API_KEY", "your-key-goes-here" );</strong></code>. <br />No data can be saved until you have entered this key correctly.' );		
	}
	
	function profile_enqueuing()
	{
		wp_enqueue_script( 'jquery' ); // Probably present, but let's be sure
		$google_maps_js = 'http://maps.google.com/maps?file=api&v=2&key=' . GOOGLE_MAPS_API_KEY;
		wp_enqueue_script( 'google_maps', $google_maps_js );
		$get_gmaps_location_js = $this->url() . '/js/get-gmaps-location.js';
		wp_enqueue_script( 'get_gmaps_location_js', $get_gmaps_location_js );
		
		// Add the CSS
		$get_gmaps_location_css = $this->url() . '/css/get-gmaps-location.css';
		wp_enqueue_style( 'get_gmaps_location_css', $get_gmaps_location_css );
	}
	
	/**
	 * Enqueue various CSS and JS scripts to show maps on the blog.
	 *
	 * @return void
	 **/
	function view_enqueuing()
	{
		wp_enqueue_script( 'jquery' ); // Probably present, but let's be sure
		$google_maps_js = 'http://maps.google.com/maps?file=api&v=2&key=' . GOOGLE_MAPS_API_KEY;
		wp_enqueue_script( 'google_maps', $google_maps_js );
		$map_authors_js = $this->url() . '/js/map-authors.js';
		wp_enqueue_script( 'map_authors_js', $map_authors_js );
		
		// Add the CSS
//		$get_gmaps_location_css = $this->url() . '/css/get-gmaps-location.css';
//		wp_enqueue_style( 'get_gmaps_location_css', $get_gmaps_location_css );
	}
	
	/**
	 * Render an HTML SCRIPT element containing some vars which are inconvenient
	 * to get the the JS in another way.
	 *
	 * @return void
	 **/
	function js_paths()
	{
		$template_vars = array();
		$template_vars[ 'plugin_path' ] = $this->url();
		
		// Render the template
		$this->render_admin ( 'js-paths', $template_vars );
	}
	
	/**
	 * 
	 *
	 * @return void
	 **/
	function info_window_html()
	{
		// Capture the info HTML template
		$html = $this->capture( 'map-authors-info-window-html', array() );
		$html = $this->utility->strip_newlines( $html );
		$escaped_html = $this->utility->escape_for_js( $html );

		// Put that escaped HTML into a second template
		$template_vars = array();
		$template_vars[ 'html' ] = $escaped_html;
		// Render the template
		$this->render( 'map-author-js', $template_vars );
	}
	
	/**
	 * Render some HTML for the location dialog (long and lat fields)
	 *
	 * @return void
	 **/
	function location_dialog()
	{
		$template_vars = array();
		// Get the long and lat for the current user
		$profileuser = $this->get_profileuser();
		// Merge location data from that which has been stored against the user,
		// and that which has been entered during this session.
		$saved = (array) get_usermeta( $profileuser->ID, 'author_location' );
		$previous = $this->session_location_data();
		$location_data = $saved;
		// Merge the data from this session over the previously saved data
		if ( $previous ) {
			$location_data = array_merge( $saved, $previous );
		}
		$template_vars[ 'location_data' ] = $location_data;
		$template_vars[ 'saved' ] = $saved;
		
		// Render the template
		$this->render_admin ( 'location-dialog', $template_vars );
	}
	
	function session_location_data()
	{
		$slashed = @ $_GET[ 'al_data' ];
		if ( ! $slashed ) {
			return false;
		}
		// F'ing slashes
		$serialised = stripslashes( $slashed );
		$location_data = unserialize( $serialised );
		return $location_data;
	}
	
	function load_profile()
	{
		$this->set_location();
		$this->profile_enqueuing();
	}
	
	/**
	 * Check for the POSTed variables for Longitude and Latitude when the user saves the profile,
	 * and save them against the appropriate user.
	 *
	 * @return void
	 **/
	function set_location()
	{
		// First check that there's a dialog present
		$present = (bool) @ $_POST['al_present'];
		if ( ! $present ) return; // Better luck next time
		// OK. Good to go... get the other fields
		$lat = (float) @ $_POST['al_latitude'];
		$long = (float) @ $_POST['al_longitude'];
		$place_name = @ $_POST['al_place_name'];
		$country = @ $_POST['al_country'];
		// Bung it all in the location_data property array
		$this->location_data[ 'latitude' ] = $lat;
		$this->location_data[ 'longitude' ] = $long;
		$this->location_data[ 'place_name' ] = $place_name;
		$this->location_data[ 'country' ] = $country;
		// Validate
		if ( ! $this->location_data[ 'place_name' ] ) {
			$this->errors[] = 'no_place_name';
		}
		if ( ! $this->location_data[ 'country' ] ) {
			$this->errors[] = 'no_country';
		}
		// Both lat & long present?
		if ( ! $this->location_data[ 'latitude' ] 
			&& $this->location_data[ 'latitude' ] !== (float) 0 ) {
			$this->errors[] = 'no_lat_or_long';
		} else if ( ! $this->location_data[ 'longitude' ] 
			&& $this->location_data[ 'longitude' ] !== (float) 0 ) {
			$this->errors[] = 'no_lat_or_long';
		// Let's validate then.
		} else {
			$this->validate_latitude();
			$this->validate_longitude();
		}
		// Any errors? Don't save the values.
		if ( $this->errors ) {
			return;
		}
		// Set the usermeta
		$profileuser = $this->get_profileuser();
		update_usermeta( $profileuser->ID, 'author_location', $this->location_data );
	}
	
	/**
	 * WP Filter
	 * Append a serialised array of error codes to the redirect, so they are
	 * displayed on the next page.
	 *
	 * @param string $location The URL string to be redirected to
	 * @return string The amended URL string to be redirected to
	 **/
	function append_errors( $location )
	{
		// If we've no errors, then just pass it straight through
		if ( ! $this->errors ) return $location;
		// Serialise and encode the errors array, and add to the location query string
		$serialised = serialize( $this->errors );
		$encoded = urlencode( $serialised );
		$location = add_query_arg( 'al_errors', $encoded, $location );
		// We don't want the anchor element on the URL
		return $location;
	}
	
	/**
	 * WP Filter
	 * Append a serialised array of the currently entered location data 
	 * to the redirect, so they can be displayed on the next page.
	 *
	 * @param string $location The URL string to be redirected to
	 * @return string The amended URL string to be redirected to
	 **/
	function append_location_data( $location )
	{
		// If we've no errors, then just pass it straight through
		if ( ! $this->location_data ) return $location;
		// Serialise and encode the location data array, and add to the location query string
		$serialised = serialize( $this->location_data );
		$encoded = urlencode( $serialised );
		$location = add_query_arg( 'al_data', $encoded, $location );
		return $location;
	}
	
	/**
	 * Show any errors or messages in the standard style. Called on admin pages.
	 *
	 * @return void
	 **/
	function admin_notices()
	{
		// Any errors in the errors property array?
		foreach ( $this->errors AS & $error_code ) {
			$this->render_error( $this->error_msgs[ $error_code ] );
		}
		// Get any errors from the GET params
		$slashed = @ $_GET[ 'al_errors' ];
		if ( ! $slashed ) {
			return;
		}
		// F'ing slashes
		$serialised = stripslashes( $slashed );
		$errors = (array) unserialize( $serialised );
		if ( ! $errors ) return;
		// After all that, any errors? Display them.
		foreach ( $errors AS & $error_code ) {
			$this->render_error( $this->error_msgs[ $error_code ] );
		}
		
	}
	
	/**
	 * Validate a value as a latitude. Put any errors into the errors array
	 * property. I believe a Latitude is a float, between -90 and +90
	 *
	 * @return void
	 **/
	function validate_latitude()
	{
		// Both errors served by the same message
		if ( ! is_float( $this->location_data[ 'latitude' ] ) 
			|| $this->location_data[ 'latitude' ] > 90 
			|| $this->location_data[ 'latitude' ] < -90 ) {
			$this->errors[] = 'invalid_latitude';
		}
	}
	
	/**
	 * Validate a value as a longitude. Put any errors into the errors array
	 * property.  I believe a Longitude is a float, between -180 and +180
	 *
	 * @return void
	 **/
	function validate_longitude()
	{
		// Both errors served by the same message
		if ( ! is_float( $this->location_data[ 'longitude' ] ) 
			|| $this->location_data[ 'longitude' ] > 180 
			|| $this->location_data[ 'longitude' ] < -180 ) {
			$this->errors[] = 'invalid_longitude';
		}
	}
	
	/**
	 * Get the user object for the currently edited profile.
	 *
	 * @return WP User object
	 **/
	private function get_profileuser()
	{
		$user_id = (int) @ $_REQUEST[ 'user_id' ];
		
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
		}
		return get_user_to_edit( $user_id );
	}
	
	function shortcode_map_authors( $atts, $content = '' )
	{
		$defaults = array( 'include_protected_posts' => false );
		extract( shortcode_atts( $defaults, $atts ) );
		// Pass off to the template method to remain as DRY as possible
		return $this->list_authors( $includ_protected_posts, false );
	}

	/**
	 * A template method to print the published authors. Default HTML can be overriden
	 * by adding a new template file into view/author-location/authors.php.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function list_authors( $include_protected_posts, $echo = true )
	{		
		$template_vars = array();
		$template_vars['authors'] = $this->get_published_authors( $include_protected_posts );
		$template_vars['utility'] = & $this->utility;
		
		// Capture the HTML
		$output = $this->capture( 'map-authors', $template_vars );
		if ( ! $echo ) {
			error_log( 'Not echoing' );
			return $output;
		}
		error_log( 'Echoing' );
		echo $output;
	}

	/**
	 * A getter which returns an array of published authors as WP_User objects
	 *
	 * @return array An array of WP_User objects.
	 * @author Simon Wheatley
	 **/
	protected function get_published_authors( $include_protected_posts )
	{
		// Maybe this has already been done?
		if ( ! empty( $this->published_authors ) ) return $this->published_authors;
		// ...obviously not
		$author_ids = $this->published_author_ids( $include_protected_posts );
		foreach ( $author_ids AS $author_id ) {
			$author = new ALoc_Author( $author_id );
			$author->include_protected_posts = $this->include_protected_posts;
			$this->published_authors[] = $author;
		}
		// All ready.
		return $this->published_authors;
	}
	
	/**
	 * A method to return the list of IDs for published authors. N.B. This means that
	 * if an author has not published at least one post, they will not be shown.
	 *
	 * @return array WordPress User IDs for the authors who have been (in)active in the last 30 days.
	 * @author Simon Wheatley
	 **/
	protected function published_author_ids( $include_protected_posts )
	{
		// Maybe this has already been done?
		if ( ! empty( $this->author_ids ) ) return $this->author_ids;
		// ...obviously not

		global $wpdb;
		// SWTODO: This does NOT cope with posts being marked "private", which is different to password protecting posts
		$unprepared_sql  = "SELECT DISTINCT post_author FROM $wpdb->posts ";
		$unprepared_sql .= "WHERE post_status = 'publish' AND post_type = 'post' ";
		// It strikes me that post_password might be NULL or the empty string, best check both
		if ( ! $include_protected_posts ) $unprepared_sql .= "AND ( post_password IS NULL OR post_password = '' ) ";
		$unprepared_sql .= "ORDER BY post_date_gmt DESC ";
		$sql = $wpdb->prepare( $unprepared_sql );

		$this->published_author_ids = $wpdb->get_col( $sql );
		return $this->published_author_ids;
	}

}

/**
 * Instantiate the plugin
 *
 * @global
 **/

$AuthorLocation = new AuthorLocation();

/**
 * A template tag function which wraps the list_authors method from the 
 * AuthorLocations class for namespace convenience.
 *
 * @param string $args optional A string of URL GET alike variables which are parsed into params for the method call
 * @return void Prints some HTML
 * @author Simon Wheatley
 **/
function al_list_authors( $args = null )
{
	global $AuthorLocation;

	// Traditional WP argument munging.
	$defaults = array(
		'include_protected_posts' => false,
		'echo' => true
	);
	$r = wp_parse_args( $args, $defaults );
	
	// Sort out include_protected_posts arg
	if ( $r['include_protected_posts'] == 'yes' ) {
		$r['include_protected_posts'] = true;
	}
	if ( $r['include_protected_posts'] == 'no' ) {
		$r['include_protected_posts'] = false;
	}
	// Now cast to a boolean to be sure
	$r['include_protected_posts'] = (bool) $r['include_protected_posts'];
	
	// Sort out echo arg
	if ( $r['echo'] === 'yes' ) {
		$r['echo'] = true;
	}
	if ( $r['echo'] === 'no' ) {
		$r['echo'] = false;
	}
	// Now cast to a boolean to be sure
	$r['echo'] = (bool) $r['echo'];
	
	// Set the protected posts
	$include_protected_posts = $r['include_protected_posts'];
	
	// Set the echo (or not)
	$echo = $r['echo'];
	
	// Call the method
	$AuthorLocation->list_authors( $include_protected_posts, $echo );
}

?>