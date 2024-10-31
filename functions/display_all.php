<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];

function recordbrowsertoggle_lyrics($clicker,$id, $lyrics){
    $text ='<script>';
    $text .='jQuery(document).ready(function () {
        jQuery(".l' . $id . '").click(function () {
            jQuery(".' . $id . '").slideToggle();
        });
    });';
    $text .= '</script>';
    $text .= '<a name ="open" class="l' . $id . ' title">' . $clicker . '</a>';
    $text .= ' (<em><a name ="open" class="l' . $id . ' title">' . $lyrics . '</em></a>)';

    return $text;
}

function recordbrowsertoggle_options($clicker,$id){
    $text ='<script>';
    $text .='jQuery(document).ready(function () {
        jQuery(".l' . $id . '").click(function () {
            jQuery(".' . $id . '").slideToggle();
        });
    });';
    $text .= '</script>';
    $text .= '<a name ="open" class="l' . $id . ' title toggle_options">' . $clicker . '</a>';

    return $text;
}


function refactor_display_all() {
    $recordbrowser_options = get_option('recordbrowser_options');
    $tracktitle = "";
    $text = '';
    $result = 0;
    $r = 0;
    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $tracks = $wpdb->prefix . 'recordbrowser_tracks';


    if (!empty($_REQUEST['bandname'])) {
        $bandname=$_REQUEST['bandname'];
        $bandname = filter_var ($bandname,FILTER_SANITIZE_STRING);
        $wherebandname = ' WHERE bandname = "' . $bandname . '" ';
    }

    else {
        $wherebandname = '';

    }


    if (!empty($_REQUEST['lim'])) {
        $lim=$_REQUEST['lim'];
    }


    else {
        $lim=0;
    }
    $current_url = basename($_SERVER['REQUEST_URI']);
    $displayclass = '';

    if(!array_key_exists('sortrecords',$_REQUEST)){
        $_REQUEST['sortrecords']="";
    }

    if(!array_key_exists('sort',$_REQUEST)){
        $_REQUEST['sort']="";
    }
    //Allows the users to custom sort the records
    if((empty($_REQUEST['sortrecords']) && empty($_REQUEST['sort']))) {
        $displayclass = 'display:none;';
    }
    $text .= '<div class = "recordbrowser_options"  style = "' . $displayclass . '">';
    $text .= '<div class="row">';
    $text .= '<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">';
    $text .= 'Order by: ';

    $asc = '';
    $desc = '';
    if($_REQUEST['sort'] == "ASC"){
        $asc = ' class = current"';
    }
    if($_REQUEST['sort'] == "DESC"){
        $desc = ' class = current"';
    }
	$sortrecordoptions = array("released", "title", "bandname");
	for ($i=0; $i<sizeof($sortrecordoptions); $i++) {
	    $sortby_url = recordbrowser_create_url($current_url, "sortrecords", $sortrecordoptions[$i]);
        $text .= '<a href="' . $sortby_url .'"';
        if($_REQUEST['sortrecords'] == $sortrecordoptions[$i]){
            $text .= ' class = "current"';
        }
        $text .= '>' . $sortrecordoptions[$i] . '</a>';
        if($i<sizeof($sortrecordoptions)-1){
            $text .= ' || ';
        }

    }
    $text .= '<br />Order: ';
    $sort_url = recordbrowser_create_url($current_url, "sort", "ASC");
    $text .= '<a href="' . $sort_url .'" class="'. $asc . '">ASC</a> || ';
    $sort_url = recordbrowser_create_url($current_url, "sort", "DESC");
    $text .= '<a href="' . $sort_url .'" class="'. $desc . '">DESC</a>';

	$text .= '</div>';
    $text .= '</div>';
    $text .= '</div>';
    $text .= recordbrowsertoggle_options("Toggle options", "recordbrowser_options");
	$text .= '<br /> <br />';
    $limit = $recordbrowser_options['perpage'];
    $rowbreak = $recordbrowser_options['columns'];
    $columns = 12/$rowbreak;
	$upload = wp_upload_dir();
	$upload_dir = $upload['basedir'];
    $ascdesc = sanitize_sql_orderby($recordbrowser_options['order']);
	if(!empty($_REQUEST['sortrecords']) && in_array($_REQUEST['sortrecords'], $sortrecordoptions)) {
    $order_records_by = sanitize_sql_orderby($_REQUEST['sortrecords']);
        if(empty($_REQUEST['sort'])) {
            $ascdesc = "ASC";
        }
        else {
            $ascdesc = sanitize_sql_orderby($recordbrowser_options['order']);
        }
    }

    else {
    $order_records_by = sanitize_sql_orderby($recordbrowser_options['orderby']);
    }

    if(!empty($_REQUEST['sort']) && ($_REQUEST['sort'] == "ASC" || $_REQUEST['sort'] == "DESC" ) ) {
        $ascdesc = sanitize_sql_orderby($_REQUEST['sort']);

    }



    $countsql =  $records . $wherebandname;
    $dbrecords = $wpdb->get_var( "SELECT COUNT(*) FROM $countsql");
	$result = $wpdb->get_results('SELECT * FROM ' . $records . $wherebandname . ' ORDER BY ' . $order_records_by  . ' ' . $ascdesc .  '  LIMIT ' . sanitize_sql_orderby($lim) .', ' . sanitize_sql_orderby($limit) , ARRAY_A);
	$x=0;
	$text .= '<div class="row">';
    $uploaddir = wp_get_upload_dir(__DIR__);
	foreach ( $result as $r) {
		$id = $r['id'];
		$detail_url = recordbrowser_create_url($current_url,"recordid", $id );
		$query="SELECT *  FROM " . $tracks . " WHERE record_id = $id  ORDER BY tracknr asc ";
		$details =  $wpdb->get_results ($query, ARRAY_A);
		if ( $x !=0 && $x%$rowbreak ==0) {
			$text = $text . "</div><div class='row'>"; //creating a new row every four records.
		}
		$text .= '<div class="col-xs-12 col-sm-12 col-md-6 col-lg-' . $columns . ' platte">';
        $text .= '<img class ="displayall" src="' . $uploaddir['baseurl'] . '/recordbrowser_covers/' . $r['bigcover'] . '"><br>';
		$text .=  '<span class="band"><a href="?bandname=' . stripslashes($r['bandname']) . '">' . stripslashes($r['bandname']) . '</a></span><br/>';
		$text .=  '<span class="albumtitle"><a href="' . $detail_url .'">' . stripslashes($r['title']) . '</a></span>';
		if(!empty($r['label'])) {
			$text .=   '</span><br/><span class = "year">' . $r['label'] . '</span>';
		}
		// "<a href=\"?country=". $r['country']."\">".$r['country']."</a> - ".
		if(!empty($r['released'])) {
			$text .=  ' <span class = "year">, ' . $r['released'] . '</span><br/> ';
		}
		if(!empty($r['format'])) {
		$text .=  '<span class="format">';
		}
		if(!empty($r['size'])) {
			$text .=  $r['size'] . '" ';
		}
		if(!empty($r['format'])) {
			$text .=  $r['format'] . '</span>, ';
		}
		$text .=   ' <span class = "type">' . $r['type'] . '</span><br/>';
		$comment = '';
        $comment = '';
        if(!empty($r['comment']))  {
            $comment=stripslashes($r['comment']);
        }
        $text .=  ' <span>' . $comment . '</span>';

        if(!empty($r['buyme']) && recordbrowser_validate_url($r['buyme']) == TRUE) {
            $buyme_link = filter_var($r['buyme'], FILTER_SANITIZE_URL);
            $text .= ' <span><a href="' . $buyme_link . '">Buy this record</a></span>';
        }

        $text .= '<hr class = "rb" /> ';
		$text .= '<div class= "tracklist">';
		$side=false;
		$sidecounter=1;
		$tracknr=1;
		foreach ($details as $t) {
            $track=trim($t['title']);
            $track1=stripslashes($track);
            $track2=addslashes($track);
            $track3=addslashes($track2);
            if(($r['format']=="vinyl"||$r['format']=="tape") && (empty($track3)||$tracknr==1)) {
                $text .= '<h3 class="side">Side ' . $sidecounter . '</h3>';
                $sidecounter++;
            }
            if(!empty($track1)) {
                $text .= '<span class="tracknr">' . $tracknr . '</span>';
                $tracknr++;

                if (!empty($t['lyrics'])) {
                    $text .= recordbrowsertoggle_lyrics($track1, $t['id'], "lyrics");
                    $text .= '<div  class ="' . $t['id'] . '" style = "display:none;margin-bottom:15px;" id="' . $r['id'] . $tracknr . '">';
                    $lyrics1 = stripslashes($t['lyrics']);
                    $text .= $lyrics1;
                    $text .= '<br/>(<em><a name= "close">x</a>)</em>';
                    $text .= '</div>';
                } else {
                    $text .= '<a class = "title">' . $track1 . '</a>';
                }

            }

            $text .=  '<br/>';
            $text .=  '<div style = "display:none;margin-bottom:15px;" id="' . $r['id'] . $tracknr . '">';
            $lyrics1 = stripslashes($t['lyrics']);
            $text .=  $lyrics1;
            $text .=  '</div>'; //closing the lyrics div
        }
		$text .= '</div></div>'; //closing tracklist and col
		$x++;
	}
$text = $text . '</div>'; //closing the class .row div
?>

<?php
$prev=$lim-12;
if ($prev < 0) {
    $prev=0;
}
$next=$lim +12;

$text .= '<br clear="all"><nav class="navigation post-navigation" role="navigation"> <div class="nav-links" style="margin-top:80px;">';
if ($lim !=0) {
    $prev_url = recordbrowser_create_url($current_url, "lim", $prev);
    $text .=	'<div class="nav-previous"><a href="' .  $prev_url . '" rel="prev"><i class="fa fa-chevron-left"></i> <span class="post-title">neuere</span></a></div>';
}
if($lim+12 < $dbrecords - 1) {
    $next_url = recordbrowser_create_url($current_url, "lim", $next);
    $text .= '<div class="nav-next"><a href="' . $next_url . '" rel="next"><span class="post-title">ältere<i class="fa fa-chevron-right"></i></span></a></div>';
}
$text .= '</div> </nav>';
return $text;
}