<?php
/*
Plugin Name: Event Post Type
Plugin URI: 
Description: Event Post Type brings events to WordPress in a simple intuitive style.
Author: Joel Bergroth
Version: 1.0.1
Author URI: http://edvindev.wordpress.com
*/

/*  Copyright 2010 Joel Bergroth (email: joel.bergroth@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Todo:
- Datepicker localization
*/

//Constants
define('EVENT_URLPATH', trailingslashit( WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) ) );
//define('EVENT_DIRPATH', trailingslashit( WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) ) );
define('EVENT_LOCATION_TABLE','event_locations');

//Actions
add_action('contextual_help', 'add_help_text', 10, 3);
add_action('init', 'event_post_type');
add_action('save_post', 'event_save_postdata', 10, 2);
add_action("manage_posts_custom_column", "events_columns_content");

//Filters
add_filter('post_updated_messages', 'event_updated_messages');
add_filter("manage_edit-event_columns", "events_columns_title");

//Stylesheets and Scripts
add_action('admin_print_styles', 'event_css');
add_action('admin_print_scripts', 'event_javascript');

function event_css() {

	global $post;
	
	if(isset($post->post_type)) {
	
		if($post->post_type == 'event') {
		
			wp_enqueue_style( 'jquery-ui-css', EVENT_URLPATH . 'css/smoothness/jquery-ui-1.7.3.custom.css');
			wp_enqueue_style( 'event-css', EVENT_URLPATH.'css/event-post-type-admin.css', array() );
			wp_enqueue_style( 'autosuggest-css', EVENT_URLPATH.'css/autosuggest.css', array() );
			
		}
	}
}

function event_javascript() {

	global $post;
	
	if(isset($post->post_type)) {
	
		if($post->post_type == 'event') {
		
			wp_enqueue_script('jquery');
			wp_enqueue_script( 'suggest' );
			
			wp_enqueue_script('event-jquery-ui', EVENT_URLPATH.'js/jquery-ui-1.7.3.custom.min.js', array('jquery'));
			wp_enqueue_script('event-jquery-ui-example', EVENT_URLPATH.'js/jquery.ui.example.js', array('jquery'));
			wp_enqueue_script('event-autosuggest', EVENT_URLPATH.'js/bsn.AutoSuggest_2.1.3_comp.js', array('jquery'));
			
			//Localization for datepicker
			if(WPLANG == 'sv_SE') {
				wp_enqueue_script('event-jquery-ui-datepicker', EVENT_URLPATH.'js/datepicker/jquery.ui.datepicker-sv.js', array('jquery'));
			} 
		}
	}
}


// ------------------------------------------------------------------
// Event Post Type Activation
// ------------------------------------------------------------------
//
// This is executed when Event Post Type is activated from the 
// Plugins page
//

register_activation_hook(__FILE__, 'event_post_type_activation');

function event_post_type_activation() {

	event_post_type_create_location_table();
	
}

// Create location table in database
function event_post_type_create_location_table() {

	global  $wpdb;
	
	$event_location_table = $wpdb->prefix.EVENT_LOCATION_TABLE;
	
	if($wpdb->get_var("SHOW TABLES LIKE '$event_location_table'") != $event_location_table) {
	
		$sql = "CREATE TABLE ".$event_location_table." (
			event_location_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_location_name text NOT NULL,
			event_location_address text NOT NULL,
			event_location_town text NOT NULL,
			event_location_description longtext NOT NULL,
			event_location_latitude float NOT NULL,
			event_location_longitude float NOT NULL,
			UNIQUE KEY (event_location_id)
			) DEFAULT CHARSET=utf8 ;";
		
		$wpdb->query($sql);
		
	}
}


// ------------------------------------------------------------------
// Event Post Type Uninstallation
// ------------------------------------------------------------------
//
// This is executed when Event Post Type is uninstalled 
// (not deactivated) from the Plugins page
//

register_uninstall_hook(__FILE__, 'event_uninstall');

function event_uninstall() {

	global $wpdb;
	
	// Delete table
	$event_location_table = $wpdb->prefix.EVENT_LOCATION_TABLE;
	$wpdb->query("DROP TABLE IF EXISTS $event_location_table");
	
}


// ------------------------------------------------------------------
// Event Custom Post Type Creation
// ------------------------------------------------------------------
//
// This function creates the custom post type
//


function event_post_type() {

	$labels = array(
		'name' => _x('Events', 'post type general name'),
		'singular_name' => _x('Event', 'post type singular name'),
		'add_new' => _x('Add New', 'event'),
		'add_new_item' => __('Add New Event'),
		'edit_item' => __('Edit Event'),
		'new_item' => __('New Event'),
		'view_item' => __('View Event'),
		'search_items' => __('Search Events'),
		'not_found' =>  __('No events found'),
		'not_found_in_trash' => __('No events found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => _x('Events', 'menu name')
	);
	
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 6,
		//'menu_icon' => 'url',
		//'capability_type' => 'event';
		'rewrite' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'supports' => array('title','editor','author','thumbnail'/*,'excerpt','comments'*/),
		'register_meta_box_cb' => 'event_meta_boxes',
		'has_archive' => true,
		'query_var' => true
	);
	
	register_post_type('event',$args);
	
}

function event_meta_boxes() {
	add_meta_box( 'datetime', __( 'Date & Time' ), 'event_datetime_meta_box', 'event', 'normal', 'high' );
	add_meta_box( 'place', __( 'Place' ), 'event_place_meta_box', 'event', 'normal', 'high' );
}

function event_datetime_meta_box() {

	global $post;
	
	//wp_nonce_field( plugin_basename(__FILE__), 'datetime_noncename' );
	
	
	
	$custom = get_post_custom($post->ID);
	
	if (!$custom == '') {
		if(isset($custom["_date_start"][0])) {
			$date_start = $custom["_date_start"][0];
		} else { $date_start = '';}
		if(isset($custom["_time_start"][0])) {
			$time_start = $custom["_time_start"][0];
		} else { $time_start = '';}
		if(isset($custom["_date_end"][0])) {
			$date_end = $custom["_date_end"][0];
		} else { $date_end = '';}
		if(isset($custom["_time_end"][0])) {
			$time_end = $custom["_time_end"][0];
		} else { $time_end = '';}
	}
	else {
		$date_start = '';
		$time_start = '';
		$date_end = '';
		$time_end = '';
	}
	
?>
<script type="text/javascript">
jQuery(document).ready(function($) {

	$('.datepicker').datepicker({
		duration: 'fast',
		dateFormat: 'yy-mm-dd'
	});
	
	$('.datepicker').example('0000-00-00');
	$('.timepicker').example('00:00');
	
});
</script>
<table class="form-table">

	<tr valign="top">
		<th scope="row"><label for="date_start"><?php _e('Start:'); ?></label></th>
		<td>
			<span class="description"><?php _e('Date:'); ?></span><input name="date_start" type="text" id="date_start" value="<?php echo $date_start; ?>" class="regular-date datepicker" />
		</td>
		<td>
			<span class="description"><?php _e('Time:'); ?></span><input name="time_start" type="text" id="time_start" value="<?php echo $time_start; ?>" class="regular-time timepicker" />
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row"><label for="date_end"><?php _e('End:'); ?></label></th>
		<td>
			<span class="description"><?php _e('Date:'); ?></span><input name="date_end" type="text" id="date_end"  value="<?php echo $date_end; ?>" class="regular-date datepicker" />
		</td>
		<td>
			<span class="description"><?php _e('Time:'); ?></span><input name="time_end" type="text" id="time_end"  value="<?php echo $time_end; ?>" class="regular-time timepicker" />
		</td>
	</tr>
	
</table>

<?php
}

function event_place_meta_box() {

	global $post, $wpdb;

	//wp_nonce_field( plugin_basename(__FILE__), 'place_noncename' );
  
	
	$custom = get_post_custom($post->ID);
	
	if(!$custom == '') {
		if(isset($custom["_event_location_id"][0])) {
			$event_location_id = $custom["_event_location_id"][0];
		}
	}
	
	$location_table = $wpdb->prefix.EVENT_LOCATION_TABLE;
	
	if(isset($custom["_event_location_id"][0])) {
		if(!$custom == '') {
			$event_location = $wpdb->get_row("SELECT * FROM $location_table WHERE event_location_id = $event_location_id");
	
			$location_name = $event_location->event_location_name;
			$location_address = $event_location->event_location_address;
			$location_town = $event_location->event_location_town;
		}
	}
	else {
		$location_name = '';
		$location_address = '';
		$location_town = '';
	}

	
	
?>

<script type="text/javascript">
jQuery(document).ready(function($) {

	$('#location_name').example('E.g. Moe\'s Bar');
	
	var options = {
		script:"<?php echo EVENT_URLPATH; ?>location.php?limit=6&",
		varname:"input",
		json:false,
		shownoresults:false,
		timeout:25000000,
		delay:100,
		maxresults:6,
		callback: function (obj) { document.getElementById('location_town').value = obj.town; document.getElementById('location_address').value = obj.address; }
	};
	var as_nonjson = new bsn.AutoSuggest('location_name', options);

});
</script>

<table class="form-table">

	<tr valign="top">
		<th scope="row"><label for="location_name"><?php _e('Name:'); ?></label></th>
		<td>
			<input name="location_name" type="text" id="location_name" value="<?php echo $location_name; ?>" class="regular-text" />
			<span class="description"><?php _e('The name of the place.'); ?></span>
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row"><label for="location_address"><?php _e('Address:'); ?></label></th>
		<td>
			<input name="location_address" type="text" id="location_address" value="<?php echo $location_address; ?>" class="regular-text" />
			<span class="description"><?php _e('The address to the place.'); ?></span>
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row"><label for="location_town"><?php _e('Town:'); ?></label></th>
		<td>
			<input name="location_town" type="text" id="location_town" value="<?php echo $location_town; ?>" class="regular-text" />
			<span class="description"><?php _e('The town in which you can find the place.'); ?></span>
		</td>
	</tr>
	
</table>

<?php
}

function event_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['event'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Event updated. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Event updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Event saved.'),
		8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Book draft updated. <a target="_blank" href="%s">Preview book</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
	
}

function add_help_text($contextual_help, $screen_id, $screen) {

	//$contextual_help .= var_dump($screen); // use this to help determine $screen->id
	
	if ('event' == $screen->id ) {
		$contextual_help =
		'<p>' . __('Things to remember when adding or editing an event:') . '</p>' .
		'<ul>' .
		'<li>' . __('There\'s an important difference between the publish date and the date when the event occurs and they shall not be mixed up.') . '</li>' .
		'</ul>' .
		'<p>' . __('If you want to schedule the event to be published in the future:') . '</p>' .
		'<ul>' .
		'<li>' . __('Under the Publish module, click on the Edit link next to Publish.') . '</li>' .
		'<li>' . __('Change the date to the date to actual publish this article, then click on Ok.') . '</li>' .
		'</ul>' .
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>') . '</p>' .
		'<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>' ;
	}
	/*
	elseif ( 'edit-event' == $screen->id ) {
		$contextual_help = 
		'<p>' . __('This is the help screen displaying the table of blah blah.') . '</p>' ;
	}
	*/
	
	return $contextual_help;
	
}

function event_save_postdata( $post_id ) {

	global $wpdb;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	
	/*if ( !wp_verify_nonce( $_POST['datetime_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	if ( !wp_verify_nonce( $_POST['place_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}*/

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return $post_id;

  	
	// Check permissions
	if(isset($_POST['post_type'])) {
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
					return $post_id;
		}
	}

	// Lägg till 
	if(isset($_POST['date_start'])) {
		$date_start = $_POST['date_start'];
		update_post_meta($post_id, '_date_start', $date_start);
	}
	if(isset($_POST['$time_start'])) {
		$time_start = $_POST['time_start'];
		update_post_meta($post_id, '_time_start', $time_start);
	}
	if(isset($_POST['$date_end'])) {
		$date_end = $_POST['date_end'];
		update_post_meta($post_id, '_date_end', $date_end);
	}
	if(isset($_POST['time_end'])) {
		$time_end = $_POST['time_end'];
		update_post_meta($post_id, '_time_end', $time_end);
	}
	if(isset($_POST['location_name'])) {
		$location_name = $_POST['location_name'];
	}
	if(isset($_POST['location_address'])) {
		$location_address = $_POST['location_address'];
	}
	if(isset($_POST['location_town'])) {
		$location_town = $_POST['location_town'];
	}
  
	
  	if (isset($date_start) and isset($date_end) and $date_start == $date_end ) {
		$date_end = '';
	}
	
	if(isset($location_name)) {
	if(!$location_name == '') {
	
		$location_table = $wpdb->prefix.EVENT_LOCATION_TABLE;
		
		$existing_location = $wpdb->get_row( "SELECT * FROM $location_table WHERE event_location_name = '$location_name' AND event_location_address = '$location_address' AND event_location_town = '$location_town'" );
		
		
		

		if($existing_location) {
		
			$existing_location_id = $existing_location->event_location_id;
			
			update_post_meta($post_id, '_event_location_id', $existing_location_id);
			
		}
		else {
		
	
		$location_data = array( 'event_location_name' => $location_name, 'event_location_address' => $location_address, 'event_location_town' => $location_town );
		
		$wpdb->insert( $location_table, $location_data );
		
		
		$location_id = $wpdb->insert_id;
		
		update_post_meta($post_id, '_event_location_id', $location_id);
		
		}
		
	}
	}
	//if(!$location_address == '') update_post_meta($post_id, '_location_address', $location_address);
	//if(!$location_town == '') update_post_meta($post_id, '_location_town', $location_town);
	

  
}



function events_columns_title($columns) {
	$columns = array(
		"cb" => "<input type=\"checkbox\" />",
		"title" => __('Title'),
		"datetime_start" => "Start",
		//"event_category" => __('Kategori'),
		"author" => __('Author'),
		"date" => __('Published')
	);
	return $columns;
}

function events_columns_content($column) {
	global $post;
	$custom = get_post_custom();
	if(isset($custom["_date_start"][0])) {
		$date_start = $custom["_date_start"][0];
	}
	else { $date_start = ''; }
	if(isset($custom["_time_start"][0])) {
		$time_start = $custom["_time_start"][0];
	}
	else { $time_start = ''; }
	
	
	if ("datetime_start" == $column) echo $date_start.' '.$time_start;
	/*elseif ("event_category" == $column) {
		$event_categories = get_the_terms(0, "event_category");
		$event_category_html = array();
		
		if(!$event_categories == '') {
			foreach ($event_categories as $events_category)
				array_push($event_category_html, '<a href="' . get_term_link($events_category->slug, "event_category") . '">' . $events_category->name . '</a>');
		echo implode($event_category_html, ", ");
		}
		else { echo "<i>Ingen kategori</i>"; }
	}*/
	elseif ("author" == $column) echo $post->author;
}

?>