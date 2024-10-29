=== Author Location ===
Contributors: simonwheatley
Donate link: http://www.simonwheatley.co.uk/wordpress/
Tags: authors, users, profile, location, geocode, latitude, longitude
Requires at least: 2.6
Tested up to: 2.6.1
Stable tag: 1.0

Allows authors to set their location, and then display it on a Google Map.

== Description ==

Allows authors to set their location, and then display it on a Google Map.

Adds a template tag: `<?php al_list_authors(); ?>` with the following available arguments:

* include_protected_posts=1 : Include protected posts when calculating the latest post.
* echo=1 : Whether to write the map and listing into the page, or return it (so you can assign the HTML to a variable).

The HTML output is fairly heavily classed, for ease of CSS styling, but if you need to adapt it you can. Create a directory in your *theme* called "view", and a directory within that one called "author-listings". Then copy the template files from `view/author-location/` in the plugin directory into your theme directory and amend as you need. If these files exist in these directories in your theme they will override the ones in the plugin directory. This is good because it means that when you update the plugin you can simply overwrite the old plugin directory as you haven't changed any files in it. All hail [John Godley](http://urbangiraffe.com/) for the code which allows this magic to happen. 

Plugin initially produced on behalf of [Puffbox](http://www.puffbox.com).

Is this plugin lacking a feature you want? I'm happy to accept offers of feature sponsorship: [contact me](http://www.simonwheatley.co.uk/contact-me/) and we can discuss your ideas.

Any issues: [contact me](http://www.simonwheatley.co.uk/contact-me/).

== Installation ==

The plugin is simple to install:

1. Download `author-locations.zip`
1. Unzip
1. Upload `author-locations` directory to your `/wp-content/plugins` directory
1. Go to the plugin management page and enable the plugin
1. Give yourself a pat on the back

== Change Log ==

= v0.3b 2008/10/04 =

* Plugin first sees the light of day.

= v0.31b 2008/10/07 =

* Added latest-post-date and latest-post-time tags to the info window template

= v0.32b 2008/10/07 =

* Fixed JS error which was thrown on pages which didn't have a map displayed

= v0.33b 2008/10/09 =

* Added CDATA block wrappers around the inline JavaScript so that it validates

= v1.0 2008/10/09 =

* Made the relevant JS regexes greedy (so the infoWindow template can contain more than one token of the same type)
* Refactored the replacement regex JS for the infoWindow template to run a loop over an array of tokens to be replaced
* First stable release!