<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
/**
 * Plugin Name: Recordbrowser
 * Plugin URI: http://recordbrowser.com
 * Description: This is a plugin to organize your record collection (or if you're an artist, your discography)
 * Version: 1.1.7
 * Author: Vanessa Roseline Siegl
 * Author URI: http://clodia.net
 * License: GPLv2 or later.
 */

//TODO  Add action hook or whatever for deleting / uninstalling the plugin!
//TODO  search function (records and lyrics)
//TODO  edit record - previous and next links
//TODO  add multiple records at once
//TODO  custom page title
//TODO switch over to templates
//TODO localize
//TODO extend Ajax sidebar widget for different sorting options
//TODO allow users to add custom colors for everything (custom css)


include( plugin_dir_path( __FILE__ ) . 'functions/lyrics.php');
include( plugin_dir_path( __FILE__ ) . 'functions/install.php');
include( plugin_dir_path( __FILE__ ) . 'functions/display_single.php');
include( plugin_dir_path( __FILE__ ) . 'functions/display_all.php');
include( plugin_dir_path( __FILE__ ) . 'functions/add_record.php');
include( plugin_dir_path( __FILE__ ) . 'functions/edit_record.php');
include( plugin_dir_path( __FILE__ ) . 'functions/delete_record.php');
include( plugin_dir_path( __FILE__ ) . 'functions/settings.php');
include( plugin_dir_path( __FILE__ ) . 'functions/sidebarwidget.php');

include( plugin_dir_path( __FILE__ ) . 'classes/Recordbrowser_Slider.php');
//recordbrowser_install();
//$photo = plugin_dir_path( __FILE__ ) . 'images/bessie.jpg';
//recordbrowser_install_data($photo);
recordbrowser_update_db_check();

register_activation_hook( __FILE__, 'recordbrowser_install' );
register_activation_hook( __FILE__, 'recordbrowser_install_data' );
add_action( 'plugins_loaded', 'recordbrowser_update_db_check' );

// register jquery and style on initialization

add_action('init', 'recordbrowser_register_script');
function recordbrowser_register_script(){
	wp_register_style( 'recordbrowser_style', plugins_url('/css/style.css', __FILE__), false, '1.0.0', 'all');
	wp_register_style( 'bootstrap3', plugins_url('/bootstrap/css/grid12.css', __FILE__), false, '1.0.0', 'all');
}


// use the registered jquery and style above
add_action('wp_enqueue_scripts', 'recordbrowser_enqueue_style');
function recordbrowser_enqueue_style(){
	wp_enqueue_style( 'recordbrowser_style' );
	wp_enqueue_style( 'bootstrap3' );
}

//ajax!  recordbrowsersidebarwidget.js
function recordbrowser_enqueue_ajax() {
    // embed the javascript file that makes the AJAX request
    wp_register_script( 'recordbrowser-randomrecord-widget', plugin_dir_url( __FILE__ ) . 'js/recordbrowsersidebarwidget.js', array( 'json2', 'jquery' ));
    wp_enqueue_script( 'recordbrowser-randomrecord-widget');
// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
    wp_localize_script( 'recordbrowser-randomrecord-widget', 'RandomRecord', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

}
add_action('init', 'recordbrowser_enqueue_ajax');


function recordbrowser_validate_url($url) {
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if
    ((!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url) && !empty($url)) || (!empty($url) && substr($url, 0, 4) != 'http')){
        return FALSE;
    }
    else {
        return TRUE;
    }
}

function recordbrowser_create_url($url, $id, $value) {
$host ="";
    $query="";
    $url_parts = parse_url($url);
    if (isset($url_parts['host'])){
        $host = $url_parts['host'];
    }

    if (isset($url_parts['query'])){
        $query = $url_parts['query'];
    }
    parse_str($query, $params);
    $params[$id] = $value;
    $query = http_build_query($params);
    return $host . '?' . $query;
}

function recordbrowser_create_url_better($url, $id, $value) {
    $host ="";
    $query="";
    $url_parts = parse_url($url);
    if (isset($url_parts['path']) && !empty($url_parts['path'])){
        $path = $url_parts['path'];
    }

    if (isset($url_parts['host'])){
        $host = $url_parts['host'];
    }
    $final = $host  . $path;

    if (isset($url_parts['query'])){
        $query = $url_parts['query'];
    }
    parse_str($query, $params);
    $params[$id] = $value;
    $query = http_build_query($params);
    return $final . '?' . $query;
}

function recordbrowser_slider_wiget( $lim, $url, $pre_url) {
    if(!intval($lim)){
        die("wrong lim");
    }

    if(!empty($url)  && recordbrowser_validate_url($url) != TRUE) {
        die ("wrong url");
    }

    if(!empty ($pre_url) && $pre_url != "http://" && $pre_url != "https://") {
        die ("wrong pre url");
    }

    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $random_records = "SELECT  DISTINCT * FROM ". $records . " ORDER BY RAND() LIMIT " . sanitize_text_field($lim);
    $result = $wpdb->get_results($random_records, ARRAY_A);
    $uploaddir = wp_get_upload_dir(__DIR__);
    $text="";
    foreach ($result as $r) {
        $text .= "<div class='recordbrowser_widget_record'>";
        if(!empty($url)  && recordbrowser_validate_url($url) == TRUE && ($pre_url=="http://" || $pre_url == "https://")) {

            $widget_detail_url = recordbrowser_create_url_better(sanitize_text_field($url), "recordid", $r['id']);
            $widget_band_url = recordbrowser_create_url_better(sanitize_text_field($url), "bandname", stripslashes($r['bandname']));
            $widget_band_text="<a href='" . sanitize_text_field($pre_url) . $widget_band_url . "'>" . stripslashes($r['bandname']) . "</a>";
            $widget_detail_text="<a href='" . sanitize_text_field($pre_url) . $widget_detail_url . "'>" . stripslashes($r['title']) . "</a>";
        }
        else {
            $widget_band_text= stripslashes($r['bandname']) ;
            $widget_detail_text= stripslashes($r['title']);
        }
        $text .= "<img class ='widget_displayall' src='" . $uploaddir['baseurl'] . "/recordbrowser_covers/" . $r['bigcover'] ."'><br />";
        $text .= "<div class='recordbrowser_widget_details'>";
        $text .=  "<span class='widget_band'>" .$widget_band_text. "</span><br/>";
        $text .= "<span class='widget_albumtitle'>" . $widget_detail_text . "</span></div></div>";
    }
    return $text;
}

function recordbrowser_random_records( $lim)
{
    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $random_records = "SELECT  * FROM " . $records . " ORDER BY RAND() LIMIT 5";
    $result = $wpdb->get_results($random_records, ARRAY_A);
    return $result;
}




// Setup Ajax action hook
add_action( 'wp_ajax_recordbrowser_slider_reload', 'recordbrowser_slider_reload' );
add_action( 'wp_ajax_nopriv_recordbrowser_slider_reload', 'recordbrowser_slider_reload' );
//jQuery(".' . $id . '").replaceWith ("' . $bah . '", function() {
function recordbrowser_slider_reload($clicker,$id, $lim, $url, $pre_url) {
    $recordlist=recordbrowser_random_records( $lim);
    foreach ($recordlist as $myrecord) {
        $bigcover=$myrecord['bigcover'];
        $id = $myrecord['id'];
        $title = $myrecord['title'];
        $bandname = $myrecord['bandname'];
    }
    if(!empty($_POST)) {
        $results = recordbrowser_slider_wiget( $_POST['limit'], $_POST['rb_url'], $_POST['rb_pre_url']);
        // Return the String
        echo $results;
    }

    else {
        echo "No data received";
    }
    exit();
}





function recordbrowser_set_page_title(  ) {
	global $wpdb;
	$records = $wpdb->prefix . 'recordbrowser_records'; 
	if ( !empty($_REQUEST['recordid']) ) {
		$result = $wpdb->get_results('SELECT title FROM ' . $records . 'WHERE id = ' . $_REQUEST['recordid'], ARRAY_A);
		foreach ( $result as $r) {
			$title .= $r['title'];
		}
	}
	
	$title .= get_bloginfo( 'name' );
	return $title;
}
add_filter( 'wp_title', 'recordbrowser_set_page_title', 10, 2 );




function recordbrowser_list_the_records( ) {
	
	if(empty($_REQUEST['recordid'])) {
		$text = refactor_display_all();
		
		}
	else {
		$text = recordbrowser_display_single();
		
	}
	echo $text;
}

add_shortcode( 'recordbrowser', 'recordbrowser_list_the_records' );


add_action('admin_menu', 'recordbrowser_menu_pages');

function recordbrowser_menu_pages() {
	// Add the top-level admin menu
	$page_title = 'Recordbrowser Settings';
	$menu_title = 'Recordbrowser';
	$capability = 'manage_options';
	$menu_slug = 'recordbrowsersettings';
	$function = 'recordbrowser_settings';
	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

	// Add submenu page with same slug as parent to ensure no duplicates
	$sub_menu_title = 'Settings';
	add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

// Now add the submenu page for Help
	$submenu_page_title = 'Recordbrowser Help';
	$submenu_title = 'Help';
	$submenu_slug = 'recordbrowserhelp';
	$submenu_function = 'recordbrowser_help';
	add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
	$submenu_page_title = 'Recordbrowser Add New Record';
	$submenu_title = 'Add New Record';
	$submenu_slug = 'recordbrowseradd';
	$submenu_function = 'recordbrowser_add';
	add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);

	$submenu_page_title = 'Recordbrowser Edit Records';
	$submenu_title = 'Edit Records';
	$submenu_slug = 'recordbrowseredit';
	$submenu_function = 'recordbrowser_edit';
	add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
	
	$submenu_page_title = 'Recordbrowser Delete Records';
	$submenu_title = 'Delete Records';
	$submenu_slug = 'recordbrowserdelete';
	$submenu_function = 'recordbrowser_delete';
	add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
	

}

function recordbrowser_settings() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	else {
	    echo '<div class="wrap">';
        if(empty($_POST['sub'])) {
            $text = recordbrowser_settings_form();
        }

        else {
            $perpage = sanitize_text_field($_POST['perpage']);
            $columns = sanitize_text_field($_POST['columns']);
            $singlecolumn = sanitize_text_field($_POST['singlecolumns']);
            $orderby = sanitize_text_field($_POST['orderby']);
            $order = sanitize_text_field($_POST['order']);
            $text = recordbrowser_settings_result($perpage, $columns, $singlecolumn, $orderby, $order);
        }
        echo $text . '</div>';
	}
	
}

function recordbrowser_help() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	$text = '<div class="wrap"> <h1>Recordbrowser </h1>
    Contributors: Vanessa Roseline Siegl
    Tags: music, music collecting, discography
    Plugin Homepage: <a href="http://www.recordbrowser.com">www.recordbrowser.com</a>
    Creator Homepage: <a href="http://clodia.net">clodia.net</a>
    Requires at least: 3.7.3
    Tested up to: 3.7.3
    License: GPLv2 or later.
    Stable tag: 1.1
    
    This plugin allows users to organize their record collection or discography and present it to visitors.
    <h2>Description</h2>
    This is directed at anyone who wants to display a discography on their webpage. 
    It could be you are a musician, or you run a label, or you have a record store, or even just want to show off your collection to the world.
    
    I programmed this years ago in an attempt to make a multi-user site, but then decided not to pursue it any further. It did get used on several music sites I was running,
    the only one that is s still active is the German language <a href="http://www.conoroberst.de/diskografie/">Conor Oberst</a> fansite.
    
    
    <h2>Installation</h2>
    Get it directly from the repository or download it and upload the extracted folder in the plugins folder on your server!
    Embed in any page or post using the shortcode [recordbrowser].
    
   <h2>Changelog</h2>

    <h3>v1.1.0 - 4/11/2017</h3>h3>
    * It is now possible for the visitor of the site to sort the records by whatever parameter they want.
    * The visitor can now choose to only view records by a specific artist by clicking on the bandname.
    * Removed the ability to click currently not searchable stuff (label etc).
    * Fixed previous / next links.
    * Added option that allows the user to put a link where to buy a specific record.
    * Fixed "Edit Record" so that it remembers the comment upon submit in case there\'s an error in one of the fields.
    * Fixed the display error for "Edit Record".
    * Other minor display fixes in the admin area.
    * Fixed some confused code for display single.
    
    
    <h2>Upcoming Features</h2>
    Widgets for the sidebar
    Override settings via the shortcode
    Featured records
    Upload songs
    Localization
    Add multiple records at once
    <h2>Ideas / Need Help?</h2>
    Contact me at <a href="mailto:vanessa.siegl@gmail.com">vanessa.siegl@gmail.com</a>.
    <h2>Want me to host you?</h2>
    Get a subdomain on <b>recordbrowser.com</b>b> -> http://yourname.recordbrowser.com and start displaying your records to the world!
    Contact me at <a href="mailto:vanessa.siegl@gmail.com">vanessa.siegl@gmail.com</a> for terms&conditions.
    <h2>Misc</h2>
    The record the plugin adds into your database upon installation is by <a href="https://en.wikipedia.org/wiki/Bessie_Smith">Bessie Smith</a>. 
    She was a queer Blues Singer early in the last century and is pretty awesome. I recommend that you check her out. ';
        $text = nl2br($text);
	$text .= '<h2>Support Me</h2>I put a lot of work in creating and maintaining this plugin. The selfhosted option will always be free of charge, 
	but a donation, or if you are a musician one of your records, would be muchly appreciated<br/><br/>';
	$text .='<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="hosted_button_id" value="JRGXJW5XEY4VQ">
    <input type="image" src="https://www.paypalobjects.com/en_US/AT/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form></div>';
	echo $text;

	// Render the HTML for the Help page or include a file that does
}

function recordbrowser_edit() {
    $text ="";
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	else {
        // Render the HTML for the Settings page or include a file that does
        if (empty($_REQUEST['record']) && empty($_POST['record']) || !intval( $_REQUEST['record'] )) {
            $text .= recordbrowser_admin_list_records();
        } else {
            $text = recordbrowser_admin_edit_record($_REQUEST['record']);
        }
        echo $text;
    }
}


function recordbrowser_delete() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	else {
        // Render the HTML for the Settings page or include a file that does
        if (empty($_REQUEST['record']) && empty($_POST['record'])) {
            $text = recordbrowser_list_all();
        } else {
            $checked = $_POST['record'];
            $text = recordbrowser_delete_records($checked);
        }
        echo $text;
    }
}


function recordbrowser_get_extension($str)  {
	$i = strrpos($str,".");
	if (!$i)  { 
	return ""; 
	}
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}





//$message = new recordbrowser_addrecord_message();
function recordbrowser_add() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	recordbrowser_add_record();
}




// This widget allows users to display a slider with randomly selected records from their collection in the sidebar or other widget areas!

// register widget
// register Recordbrowser_Slider widget
function register_recordbrowser_slider() {
    register_widget('Recordbrowser_Slider_Widget');
}
add_action( 'widgets_init', 'register_recordbrowser_slider' );
