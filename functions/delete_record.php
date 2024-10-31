<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
function recordbrowser_list_all() {

    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }


    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $text = '<h1>Select the Record(s) you want to delete</h1>';
    //$dbrecords = $wpdb->get_var( "SELECT COUNT(*) FROM $records" );
    $result = $wpdb->get_results('SELECT bandname, title, id FROM ' . $records . ' ORDER BY bandname asc', ARRAY_A);
    $url = admin_url('admin.php') . "?page=recordbrowserdelete";
    $text .= '<form method="post" action="' . $url . '">';
    foreach ($result as $r) {
        $bandname = stripslashes($r['bandname']);
        $title  = stripslashes($r['title']);
        $text .= '<input type = "checkbox" name = "record[]" value = "' . $r['id'] . '">' . $bandname . ' - ' . $title . '<br>';
    }
    $text .= wp_nonce_field( 'delete_record', 'recordbrowser_delete_nonce_field' );
    $text .= '<input type="submit" value="Delete" name="submit" class="button button-primary">
    </form>';
    return $text;
}

function recordbrowser_delete_records($id) {
    check_admin_referer(  'delete_record', 'recordbrowser_delete_nonce_field' );
    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $tracks = $wpdb->prefix . 'recordbrowser_tracks';
    for($i=0;$i<sizeof($id);$i++) {
        $record = $id[$i];
        if(intval($record)) {
            $wpdb->delete($records, array('id' => $record));
            $wpdb->delete($tracks, array('record_id' => $record));
        }
    }
    $text = '<h1>Done</h1>The selected Records have been deleted!';
    $text .= '<br><a href="' . admin_url( 'admin.php' ) . '?page=recordbrowserdelete">Delete more records.</a>';
    return $text;

}