<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
function recordbrowser_display_single() {
    $recordbrowser_options = get_option('recordbrowser_options');
    $singlecolumn = $recordbrowser_options['singlecolumns'];
    $text ='';
	global $wpdb;
	$records = $wpdb->prefix . 'recordbrowser_records';
	$tracks = $wpdb->prefix . 'recordbrowser_tracks';
	$id = $_REQUEST['recordid'];
	$result = $wpdb->get_results('SELECT * FROM ' . $records . ' WHERE id = ' . $id , ARRAY_A);
    $uploaddir = wp_get_upload_dir(__DIR__);
	foreach ($result as $r) {
        if ($singlecolumn != 1) {
            $comment = stripslashes($r['comment']);
            $text .= '<div class="row details">';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">';
            $text .= '<img class ="displaysingle" src="' . $uploaddir['baseurl'] . '/recordbrowser_covers/' . $r['bigcover'] . '"></div>';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">';
            $text .= '<span class="band"><a href="?bandname=' . stripslashes($r['bandname'] . '">' . $r['bandname']) . '</a></span><br/>';
            $text .= '<span class="albumtitle"><a href="#">' . stripslashes($r['title']) . '</a>';
            $text .= '</span><br/>' . $r['label'] . ' ';
            $text .= $r['country'] . ' ';
            $text .= '<span class="year">' . $r['released'] . '<br/>';

            $text .= '<span class="format">';
            if (!empty($r['size'])) {
                $text .= $r['size'] . '" ';
            }
            $text .= $r['format'] . '</span>, ';
            $text .= '<span class="type">' . $r['type'] . '</span><br/>';
            if (!empty($r['comment'])) {
                $comment = stripslashes($r['comment']);
            }
            if(!empty($r['buyme']) && recordbrowser_validate_url($r['buyme']) == TRUE) {
                $buyme_link = filter_var($r['buyme'], FILTER_SANITIZE_URL);
                $text .= ' <span><a href="' . $buyme_link . '">Buy this record</a></span>';
            }
            $text .= '<span class="comment">' . $comment . '<br/><br/></span></div>';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-4 col-lg-4"><h4>Tracklist</h4>';
            $details = $wpdb->get_results('SELECT *  FROM ' . $tracks . ' WHERE record_id = ' . $id . ' ORDER BY tracknr asc ', ARRAY_A);
            //$text .= count($details);
            foreach ($details as $t) {
                $track = stripslashes($t['title']);
                if (!empty($t['title']) && !empty($t['lyrics'])) {
                    $text .= '<a href="#' . $t['id'] . '">' . $track . '</a><br/>';
                }
                if (!empty($t['title']) && empty($t['lyrics'])) {
                    $text .= '<a>' . $track . '</a><br/>';
                }
            }
        } else {
            $comment = stripslashes($r['comment']);
            $text .= '<div class="row details">';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">';
            $text .= '<img class ="displaysingle" src="' . $uploaddir['baseurl'] . '/recordbrowser_covers/' . $r['bigcover'] . '"></div>';
            $text .= '</div><div class="row details">';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">';
            $text .= '<span class="band"><a href="?bandname='. $r['bandname'] . '">' . $r['bandname'] . '</a></span><br/>';
            $text .= '<span class="albumtitle"><a href="#">' . stripslashes($r['title']) . '</a>';
            $text .= '</span><br/>';
            $text .= $r['country'] . ' - ';
            $text .= '<span class="year">' . $r['released'] . '<br/>';

            $text .= '<span class="format">';
            if (!empty($r['size'])) {
                $text .= $r['size'] . '" ';
            }
            $text .= $r['format'] . '</span>, ';
            $text .= '<span class="type"' . $r['type'] . '</span><br/>';
            if (!empty($r['comment'])) {
                $comment = stripslashes($r['comment']);
            }
            $text .= '<span class="comment">' . $comment . '<br/><br/></span>';
            if(!empty($r['buyme']) && recordbrowser_validate_url($r['buyme']) == TRUE) {
                $buyme_link = filter_var($r['buyme'], FILTER_SANITIZE_URL);
                $text .= ' <span><a href="' . $buyme_link . '">Buy this record</a></span>';
            }
            $text .= '</div>';
            $text .= '</div><div class="row details">';
            $text .= '<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12"><h4>Tracklist</h4>';

            $details = $wpdb->get_results('SELECT *  FROM ' . $tracks . ' WHERE record_id = ' . $id . ' ORDER BY tracknr asc ', ARRAY_A);
            //$text .= count($details);
            foreach ($details as $t) {
                $track = stripslashes($t['title']);
                if (!empty($t['title']) && !empty($t['lyrics'])) {
                    $text .= '<a href="#' . $t['id'] . '">' . $track . '</a><br/>';
                }
                if (!empty($t['title']) && empty($t['lyrics'])) {
                    $text .= '<a>' . $track . '</a><br/>';
                }
            }
        }

        $x = 0;
        $text .= '</div></div> <!-- END row, END col --> ';
        $text .= '<div class="row">';

        if (!empty($t['lyrics'])) {
            $text .= '<hr class="rb">';
            foreach ($details as $t) {
                $track = stripslashes($t['title']);
                if (!empty($track)) {
                    if ($singlecolumn != 1) {
                        if ($x % 2 == 0 && $x != 0) {
                            $text .= '</div><div class="row">';
                        }
                        $x++;
                        $text .= '<div class = "col-xs-12 col-sm-12 col-md-6 col-lg-6">';
                        $text .= '<a name="' . $t['id'] . '">&nbsp;<h3>' . $track . '</h3></a>';
                        $lyrics = stripslashes($t['lyrics']);
                        $text .= $lyrics;
                        $text .= '<br><br/><a href="#top">top</a></div> <!-- END COL -->';
                    } else {
                        $text .= '<div class = "col-xs-12 col-sm-12 col-md-12 col-lg-12">';
                        $text .= '<a name="' . $t['id'] . '">&nbsp;<h3>' . $track . '</h3></a>';
                        $lyrics = stripslashes($t['lyrics']);
                        $text .= $lyrics;
                        $text .= '<br><br/><a href="#top">top</a></div> <!-- END COL -->';
                        $text .= '</div><div class="row">';

                    }
                }
            }
        }
    }

	return $text;
}
