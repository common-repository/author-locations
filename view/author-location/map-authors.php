<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); 

	//  N.B. The HTML for each author is currently marked up as a hCard.

?><div class="located-authors">
	<?php // You may wish to move the styling of the map into your CSS ?>
	<div id="al_map" style="width: 100%; height: 400px; margin-top: 1em;"></div>
	<h3>Where Are Our Authors?</h3>
	<div>
		<ul>
			<?php foreach ( $authors AS $key => $author ) : ?>
				<li class="vcard" id="<?php echo $author->user_login; ?>">
					<!-- This script block makes the map and info windows on the map all work. Best not remove it. -->
					<script type="text/javascript" charset="utf-8">
						//<![CDATA[
						authors[ '<?php echo $author->user_login ?>' ] = {
							user_login: '<?php echo $utility->escape_for_js( $author->user_login ); ?>',
							fn: '<?php echo $utility->escape_for_js( $author->display_name ); ?>',
							url: '<?php echo $utility->escape_for_js( get_author_posts_url( $author->ID, $author->user_nicename ) ); ?>',
							locality: '<?php echo $utility->escape_for_js( $author->author_location['place_name'] ); ?>',
							country_name: '<?php echo $utility->escape_for_js( $author->author_location['country'] ); ?>',
							latitude: '<?php echo $utility->escape_for_js( $author->author_location['latitude'] ); ?>',
							longitude: '<?php echo $utility->escape_for_js( $author->author_location['longitude'] ); ?>',
							// This is what's replacing the {{photo}} token in the info window template, so 
							// specify any widths, heights, etc, here.
							photo_img_element: '<?php echo $utility->escape_for_js(  $author->user_thumbnail( '', '', array('class'=>'photo', 'height'=>'40', 'width'=>'40')  ) ); ?>',
							latest_post_title: '<?php echo $utility->escape_for_js(  $author->latest_post_title() ); ?>',
							latest_post_url: '<?php echo $utility->escape_for_js(  $author->latest_post_permalink() ); ?>',
							latest_post_date: '<?php echo $utility->escape_for_js(  $author->latest_post_date() ); ?>', // If you want to format the date differently, do it here
							latest_post_time: '<?php echo $utility->escape_for_js(  $author->latest_post_time() ); ?>', // If you want to format the time differently, do it here
							1:1
						}
						//]]>
					</script>
					<h3 class="author">
						<!-- hcard name -->
						<a 
							class="url fn" 
							href="<?php echo get_author_posts_url( $author->ID, $author->user_nicename ); ?>" 
							title="<?php echo sprintf( __( 'View all posts by %s' ), $author->display_name ); ?>"
							><?php echo $author->display_name; ?></a>
					</h3><!-- .author .vcard -->
					<!-- hcard photo (.photo class on image) -->
					<?php $author->user_thumbnail( '<p>', '</p>', array('class'=>'photo')  ); ?>
					<?php if ( $author->author_location ) { ?>
						<p><?php echo $author->display_name; ?> is currently in 
							<!-- hcard address -->
							<span class="adr">
								<span class="locality"><?php echo $author->author_location['place_name']; ?></span>, <span class="country-name"><?php echo $author->author_location['country']; ?></span>
							</span><!-- .adr -->
							<!-- GEO microformat, used by the JS so don't remove it, hide it -->
							<span class="geo">
								<span class="location">
									(Latitude: <span class="latitude"><?php echo $author->author_location['latitude']; ?></span>,
									Longitude <span class="longitude"><?php echo $author->author_location['longitude']; ?></span>)
								</span><!-- .location -->
							</span><!-- .geo -->
						</p>
					<?php } ?>
					<p><strong>Latest post:</strong> <span class="title"><a href="<?php $author->latest_post_permalink(); ?>"><?php $author->latest_post_title(); ?></a></span><br />
					<span class="meta">Posted on <?php $author->latest_post_date( 'j F Y' ); ?> at <?php $author->latest_post_time(); ?></span></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
