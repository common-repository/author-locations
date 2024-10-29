<?php
	if (!defined ('ABSPATH')) die ('No direct access allowed');
	/* 
	 * This template governs the info window HTML for the GMap that visitors to the blog see
	 * 
	 * The following template tokens are available
	 * * {{user-login}} - The WP user login
	 * * {{fn}} - The WP display name
	 * * {{url}} - The WP author URL
	 * * {{locality}} - The place name for the current author location
	 * * {{country-name}} - The country name for the current author location
	 * * {{latitude}} - The latitude of the current author location
	 * * {{longitude}} - The longitude of the current author location
	 * * {{latest-post-title}} - The title of the latest post by that author
	 * * {{latest-post-url}} - The URL of the latest post by that author
	 * * {{latest-post-date}} - The date of the latest post by that author (formatted 
	 *                          as the blog default by, errr, default... change the 
	 *                          formatting in map-authors.php, in the JS blocks).
	 * * {{latest-post-time}} - The time of the latest post by that author (see above for formatting)
	 * * {{photo}} - The User Photo plugin thumbnail - I've saved the best for last, as the photo
	 *               token is slightly "intricate". It is replaced with an entire HTML image
	 *               element, the setup for that IMG element is in the map-authors.php template.
	 * 
	 * N.B. This HTML is currently marked up as a hCard.
	 *
	 */
?>
<div class="authorInfoHtml vcard">
	<p>{{photo-img-element}}
	<span><a class="fn" href="{{url}}">{{fn}}</a></span> 
	is currently in 
	<span class="locality">{{locality}}</span>,
	<span class="country-name">{{country-name}}</span>.</p>
	<p class=""><strong>Latest post:</strong> <span class="title"><a href="{{latest-post-url}}">{{latest-post-title}}</a> (posted on {{latest-post-date}} at {{latest-post-time}})</span></p>
	<br />
	<br />
</div>