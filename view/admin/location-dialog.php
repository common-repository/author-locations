<?php 
if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE ) {
	$is_profile_page = true;
} else {
	$is_profile_page = false;
}
?>
<div id="al_your_location">
	<h3><?php $is_profile_page? _e('Where are you?') : _e('Where is this user?'); ?></h3>
	<!-- This value affirms the presence of this dialog. -->
	<input type="hidden" name="al_present" value="1" />
	<!-- These are the values, if any, which are saved in the user metadata. -->
	<input type="hidden" id="al_latitude_saved" name="al_latitude_saved" value="<?php echo $saved[ 'latitude' ]; ?>" />
	<input type="hidden" id="al_longitude_saved" name="al_longitude_saved" value="<?php echo $saved[ 'longitude' ]; ?>" />
	<table class="form-table">
		<tr class="al_country_place" id="al_country_place">
			<th><?php _e('Place and country'); ?></th>
			<td><input type="text" name="al_place_name" id="al_place_name" size="16" value="<?php echo $location_data[ 'place_name' ]; ?>" /> <label for="al_place_name"><?php _e("Place Name"); ?></label><br />
				<input type="text" name="al_country" id="al_country" size="16" value="<?php echo $location_data[ 'country' ]; ?>" /> <label for="al_country"><?php _e("Country"); ?><br />
			</td>
		</tr>
		<tr class="al_lat_long">
			<th><?php _e('Location'); ?></th>
			<td><input type="text" name="al_latitude" id="al_latitude" size="16" value="<?php echo $location_data[ 'latitude' ]; ?>" /> <label for="al_latitude"><?php _e("Latitude"); ?></label><br />
				<input type="text" name="al_longitude" id="al_longitude" size="16" value="<?php echo $location_data[ 'longitude' ]; ?>" /> <label for="al_longitude"><?php _e("Longitude"); ?><br />
			</td>
		</tr>
	</table>
</div>