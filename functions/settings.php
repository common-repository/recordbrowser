<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
function recordbrowser_settings_form() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $recordbrowser_options = get_option('recordbrowser_options');
    $perpage = $recordbrowser_options['perpage'];
    $columns = $recordbrowser_options['columns'];
    $singlecolumns = $recordbrowser_options['singlecolumns'];
    $orderby = $recordbrowser_options['orderby'];
    $order = $recordbrowser_options['order'];
    $text =   '<h1>Settings</h1>';
    $url = admin_url('admin.php') . '?page=recordbrowsersettings';
    $text .= '<form method="post" action="' . $url . '">';
    $text .= '<b>Records per page</b><br/>';
    $checked8 = "";
    $checked10 = "";
    $checked12 = "";
    $checked15 = "";

    if($perpage==8) {
        $checked8 = "checked=checked";
    }
    if($perpage==10) {
        $checked10 = "checked=checked";
    }

    if($perpage==12) {
        $checked12 = "checked=checked";
    }

    if($perpage==15) {
        $checked15 = "checked=checked";
    }

    $text .= '<input type = "radio" name = "perpage" value ="8"' .  $checked8 . '> 8<br/>';
    $text .= '<input type = "radio" name = "perpage" value ="10"' . $checked10 . '> 10<br/>';
    $text .= '<input type = "radio" name = "perpage" value ="12"' . $checked12 .'> 12<br/>';
    $text .= '<input type = "radio" name = "perpage" value ="15"' . $checked15 . '> 15<br/>';
    $text .= '<br/><br/>';

    $columns6 = "";
    $columns5 = "";
    $columns4 = "";
    $columns3 = "";
    $columns2 = "";
    $columns1 = "";


    if($columns == 6) {
        $columns6 = "checked=checked";
    }

    if($columns == 5) {
        $columns5 = "checked=checked";
    }

    if($columns == 4) {
        $columns5 = "checked=checked";
    }

    if($columns == 3) {
        $columns3 = "checked=checked";
    }

    if($columns == 2) {
        $columns2 = "checked=checked";
    }

    if($columns == 1) {
        $columns1 = "checked=checked";
    }


    $text .= '<b>How many columns when listing all records?</b><br/>';
    $text .= '<input type = "radio" name = "columns" value ="6" ' . $columns6 . '> 6 (recommended for full-width themes)<br/>';
    $text .= '<input type = "radio" name = "columns" value ="4" ' . $columns4 . '> 4 (recommended for wide themes)<br/>';
    $text .= '<input type = "radio" name = "columns" value ="3" ' . $columns3 . '> 3 (recommended for wider themes)<br/>';
    $text .= '<input type = "radio" name = "columns" value ="2" ' . $columns2 . '> 2 (recommended for fairly slim themes)<br/>';
    $text .= '<input type = "radio" name = "columns" value ="1" ' . $columns1 . '> 1 (recommended for very slim themes)<br/>';

    $singlecolumns2 = "";
    $singlecolumns1 = "";

    if($singlecolumns == 2) {
        $singlecolumns2 = "checked=checked";
    }

    if($singlecolumns == 1) {
        $singlecolumns1 = "checked=checked";
    }


    $text .= '<br/><br/>';
    $text .= '<b>How many columns when listing lyrics?</b><br/>';
    $text .= '<input type = "radio" name = "singlecolumns" value ="2" ' . $singlecolumns2 . '> 2 (recommended for wide themes)<br/>';
    $text .= '<input type = "radio" name = "singlecolumns" value ="1" ' . $singlecolumns1 . ' > 1 (recommended for very slim themes)<br/>';
    $text .= '<br/><br/>';

    $orderby1 = "";
    $orderby2 = "";
    $orderby3 = "";
    $orderby4 = "";
    $orderby5 = "";

    if($orderby == "released") {
        $orderby1 = "checked=checked";
    }

    if($orderby == "bandname") {
        $orderby2 = "checked=checked";
    }

    if($orderby == "title") {
        $orderby3 = "checked=checked";
    }

    if($orderby == "id") {
        $orderby4 = "checked=checked";
    }

    if($orderby == "label") {
        $orderby5 = "checked=checked";
    }



    $text .= '<b>Order by?</b><br/>';
    $text .= '<input type = "radio" name = "orderby" value ="released" ' . $orderby1 . '> Year<br/>';
    $text .= '<input type = "radio" name = "orderby" value ="bandname" ' . $orderby2 . '> Band Name<br/>';
    $text .= '<input type = "radio" name = "orderby" value ="title" ' . $orderby3 . '> Title<br/>';
    $text .= '<input type = "radio" name = "orderby" value ="id" ' . $orderby4 . '> Date Added<br/>';
    $text .= '<input type = "radio" name = "orderby" value ="label" ' . $orderby5 . '>Record Label<br/>';
    $text .= '<br/><br/>';

    $order1 = "";
    $order2 = "";

    if($order == "ASC") {
        $order1 = "checked=checked";
    }

    if($order == "DESC") {
        $order2 = "checked=checked";
    }

    $text .= '<b>ASC or DESC?</b><br/>';
    $text .= '<input type = "radio" name = "order" value ="ASC" ' . $order1 . '> ASC<br/>';
    $text .= '<input type = "radio" name = "order" value ="DESC" ' . $order2 . ' > DESC<br/>';

    $text .= '<br/><br/>';
    $text .= '<br/><br/>';
    $text .= '<input type="hidden" name="sub" value="true">';
    $text .= wp_nonce_field( 'update-options', 'recordbrowser_options_nonce_field' );
    $text .= '<input type="submit" value="Update settings" name="submit" class="button button-primary">';
    $text .= '</form>';
    return $text;

}


function recordbrowser_settings_result($perpage, $columns, $singlecolumn, $orderby, $order) {

    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    check_admin_referer( 'update-options', 'recordbrowser_options_nonce_field');
    //Get options entire array
    $recordbrowser_options = get_option('recordbrowser_options');

    //Alter the options array appropriately
    if(intval($perpage) && $perpage < 30 && intval($columns) &&intval($singlecolumn) && ($order=="DESC" || $order =="ASC") && ($orderby == "released" || $orderby == "bandname" || $orderby == "title" || $orderby == "id" || $orderby == "label")) {
        $recordbrowser_options['perpage'] = $perpage;
        $recordbrowser_options['columns'] = $columns;
        $recordbrowser_options['singlecolumns'] = $singlecolumn;
        $recordbrowser_options['orderby'] = $orderby;
        $recordbrowser_options['order'] = $order;

        //Update entire array
        update_option('recordbrowser_options', $recordbrowser_options);
        $text = '<h1>Settings Changed</h1>';
        $text .= 'Records per Page: ' . $perpage . '<br/>';
        $text .= 'Columns: ' . $columns . '<br>';
        $text .= 'Columns on Single Page: ' . '<br>';
        $text .= 'Order by: ' . $orderby . '<br>';
        $text .= 'Order in which Records are displayed: ' . $order;
        return $text;
    }
    else {
        wp_die('You do not have sufficient permissions to access this page.');
    }

}