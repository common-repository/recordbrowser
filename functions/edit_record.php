<?php
//This function lists all the records as clickable links for people to choose which one to edit.
//TODO: make previous / next links for large databases!
function recordbrowser_admin_list_records() {
	global $wpdb;
	$records = $wpdb->prefix . 'recordbrowser_records';
	$text = '<h1>Click the Record you want to edit</h1>';
	$upload = wp_upload_dir();
	$dbrecords = $wpdb->get_var( "SELECT COUNT(*) FROM $records" );
	$result = $wpdb->get_results('SELECT bandname, title, id FROM ' . $records . ' ORDER BY bandname asc', ARRAY_A);
	$url = admin_url( 'admin.php' ) . "?page=recordbrowseredit";
	foreach ( $result as $r) {
        $bandname = stripslashes($r['bandname']);
        $title  = stripslashes($r['title']);
		$unique_url = $url . "&record=" . $r['id'];
	    $text .= '<a href = "'  . $unique_url . '">' . $bandname . ' - ' . $title . '<br>';
	}
	return $text;
}

//list stuff as the user would it when submitting  a new record
function recordbrowser_br2nl($string) {
    return preg_replace('/\<br(\s*)?\/?\>/i', " ", $string);
}

//This function shows us the submit form with the current content in the database
function recordbrowser_admin_edit_record($id) {

    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $error_message_year = "";
    $error_message_type = "";
    $error_message_format = "";
    $error_message_size = "";

    $error_message_bandname = "";
    $error_message_name = "";
    $error_message_catnr = "";
    $error_message_label = "";
    $error_message_country = "";
    $error_message_comments = "";
    $error_message_tracklist = "";
    $error_message_lyrics = "";
    $error_message_buymelink = "";

    $safe_record = TRUE;
    $sub = false;
    if (!empty($_POST)) {
        $address = bloginfo('wpurl');
        $upload = wp_upload_dir();
        $name=sanitize_text_field($_POST['name']);
        $bandname=sanitize_text_field($_POST['bandname']);
        $label=sanitize_text_field($_POST['rlabel']);
        $country=sanitize_text_field($_POST['country']);
        $type=sanitize_text_field($_POST['rtype']);
        $size=sanitize_text_field($_POST['size']);
        $format=sanitize_text_field($_POST['format']);
        $year = sanitize_text_field($_POST['year']);
        $cat = sanitize_text_field($_POST['catnr']);
        $buyme_link = sanitize_text_field($_POST['buyme']);
        $tracklist=$_POST['tracklist'];
        $lyrics=$_POST['lyrics'];
        $sub=sanitize_text_field($_POST['sub']);
        $comments=$_POST['comments'];

        //validating, sanitizing, escaping and checking the user-input before allowing it to be submitted


        //we want to keep the line-breaks in the tracklist/comments/lyrics, but remove any bullshit that might get posted.
        if(!empty($_POST['comments'])) {
            $comments = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST['comments'])));
        }

        if(!empty($_POST['tracklist'])) {
            $tracklist = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST['tracklist'])));
        }

        if(!empty($_POST['lyrics'])) {
            $lyrics = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST['lyrics'])));
        }



        //The year the record was released in must have exactly four numbers!
        $safe_year = intval( $year );
        if ( ! $safe_year ) {
            $safe_record = FALSE;
            $error_message_year = 'The year must be a number with 4 digits!';
        }

        if ( strlen( $year ) != 4 ) {
            $safe_record = FALSE;
            $error_message_year = 'The year must be a number with 4 digits!';
        }

        //Bandname and Album Title must be at least one letter
        if ( strlen( $bandname ) < 1 ) {
            $safe_record = FALSE;
            $error_message_bandname = 'The name of the band must be at least one letter!';
        }


        if ( strlen( $name ) < 1 ) {
            $safe_record = FALSE;
            $error_message_name = 'The title of the album must be at least one letter!';
        }


        if ( $size != 7 && $size != 10 && $size != 12 && $size != "" ) {
            $safe_record = FALSE;
            $error_message_size = 'This is not a correct record size!, only 7", 10", 12", or n/a can be chosen';
        }

        if ( $type != "album" && $type != "single" && $type != "doublealbum" && $type != "ep" && $type != "video" ) {
            $safe_record = FALSE;
            $error_message_type = 'This is not the correct type of a record, only album, double album, single, ep, or video can be chosen!';
        }

        if ( $format != "vinyl" && $format != "cd" && $format != "dvd" && $format != "vhs" && $format != "tape" && $format != "USB" ) {
            $safe_record = FALSE;
            $error_message_size = 'This is not the correct type of a record, only vinyl, cd, dvd, vhs, tape, or USB can be chosen!';
        }

        //URL must be in the proper format!
        if ( recordbrowser_validate_url($buyme_link) == FALSE ) {
            $safe_record = FALSE;
            $error_message_buymelink = 'The Url must be in a proper format, eg: http://www.example.com';
        }
    }
    global $wpdb;
    $records = $wpdb->prefix . 'recordbrowser_records';
    $tracks = $wpdb->prefix . 'recordbrowser_tracks';
    $text = '';
    $result = $wpdb->get_results('SELECT * FROM ' . $records . ' WHERE id = ' . $id , ARRAY_A);

    foreach ($result as $r) {
        if($sub==true && $safe_record == TRUE) {
            check_admin_referer( 'edit_record', 'recordbrowser_edit_record_nonce_field');
            //update the relevant bits on the database and the server


            //delete the tracklist and resubmit it.

            //check if a new image has been uploaded, if so overwrite the old one.

            //if an image has been submitted, we move it to the record covers folder.
            $info = FALSE;
            if(file_exists($_FILES['bigcover']['tmp_name'])) {
                $info = getimagesize($_FILES['bigcover']['tmp_name']);
            }
            else {
                $bigcover = $r['bigcover'];
            }
            if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
                $text .= "this is not an image";
            }
            else {
                $extension = recordbrowser_get_extension($_FILES['bigcover']['name']);
                $extension = strtolower($extension);
                $dateiname="big".date('U').".".$extension;
                $bigcover = $dateiname;
                $destination = trailingslashit( $upload['basedir'] ) . "recordbrowser_covers/" . $dateiname;
                move_uploaded_file($_FILES['bigcover']['tmp_name'], $destination);
            }
            $wpdb->update(
                $records,
                array(
                    'bandname' => $bandname,
                    'title' => $name,
                    'released' => $year,
                    'bigcover' => $bigcover,
                    'format' => $format,
                    'type' => $type,
                    'size' => $size,
                    'country' => $country,
                    'label' => $label,
                    'comment' => $comments,
                    'cat' => $cat,
                    'buyme' => $buyme_link
                ),
                array( 'id' => $id )
            );

            //delete the tracklist and the lyrics
            $wpdb->delete( $tracks, array( 'record_id' => $id ) );

            //re-enter the new tracklist and lyrics!
            $tracklist=nl2br($tracklist);
            $lyrics=nl2br($lyrics);
            $track=explode("<br />",$tracklist);
            $lyrics=explode("----",$lyrics);

            for($i=0;$i<sizeof($track);$i++) {
                $tnr=$i+1;
                $text .= $lyrics[$i];
                $wpdb->insert(
                    $tracks,
                    array(
                        'record_id' => $id,
                        'tracknr' => $tnr,
                        'title' => $track[$i],
                        'lyrics' => $lyrics[$i]
                    )
                );
            }

            $text = '<h1>Your record has been edited</h1>';
            $text .= '<br><a href="' . admin_url( 'admin.php' ) . '?page=recordbrowseredit">Edit more records.</a>';
           return $text;

        }

        else {
            $url = admin_url('admin.php') . "?page=recordbrowseredit";
            $text = '<h1>Edit ' . $r['bandname'] . ' - ' . $r['title'] . '</h1>';
            $text .= '<h2>Record Details</h2>';
            $text .= '<form enctype="multipart/form-data"  method="post" action="' . $url . '"">';
            $text .= '<input type="hidden" name="sub" value="true">';
            $text .= '<input type ="hidden" name="record" value="' . $id . '">';
            $text .= '<input type="hidden" name="action" value="recordbrowseradd" />';
            $text .= '<input type ="hidden" name="bigcover_name" value="' . $r['bigcover'] . '">';
            if ($sub == true) {
                $bandname_value = $bandname;

            } else {
                $bandname_value = $r['bandname'];
            }
            $text .= 'Bandname: ';
            if (strlen($error_message_bandname) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_bandname . ')</span>';
            }

            $text .= '<br/><input type="text" size="40" name="bandname" value = "' . $bandname_value . '"><br/><br/>';
            if ($sub == true) {
                $name_value = $name;

            } else {
                $name_value = $r['title'];
            }
            $text .= 'Title: ';
            if (strlen($error_message_name) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_name . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="name" value = "' . $name_value . '"><br/><br/>';
            if ($sub == true) {
                $label_value = $label;

            } else {
                $label_value = $r['label'];
            }
            $text .= 'Label: ';
            if (strlen($error_message_label) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_label . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="rlabel" value = "' . $label_value . '"><br/><br/>';
            $text .= 'Country: ';
            if ($sub == true) {
                $country_value = $country;

            } else {
                $country_value = $r['country'];
            }
            if (strlen($error_message_country) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_country . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="country" value = "' . $country_value . '"><br/><br/>';

            $text .= 'Catalogue number: ';
            if (strlen($error_message_catnr) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_catnr . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="catnr" value="' . $r['cat'] . '"><br/><br/>';

            if ($sub == true) {
                $year_value = $year;

            } else {
                $year_value = $r['released'];
            }
            $text .= 'Year: ';
            if (strlen($error_message_year) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_year . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="year" value="' . $year_value . '"><br/><br/>';

            if ($sub == true) {
                $buyme_value = $buyme_link;

            } else {
                $buyme_value = $r['buyme'];
            }
            $text .= 'Link to buy record (with http://): ';
            if(strlen( $error_message_buymelink )!=0){
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_buymelink . ')</span>';
            }
            $text .= '<br/> <input type="text" size="40" name="buyme" value="' . $buyme_value . '"><br/><br/>';

            if ($sub == true) {
                $format_value = $format;

            }

            else {
                $format_value = $r['format'];
            }


            $cdchecked = "";
            $vinylchecked = "";
            $vhschecked = "";
            $tapechecked = "";
            $dvdchecked = "";
            $usbchecked = "";
            $format = $r['format'];
            if (strcmp($format_value, "cd") == 0) {
                $cdchecked = 'checked ="checked"';
            }
            if (strcmp($format_value, "vinyl") == 0) {
                $vinylchecked = 'checked = "checked"';
            }
            if (strcmp($format_value, "vhs") == 0) {
                $vhschecked = 'checked = "checked"';
            }
            if (strcmp($format_value, "tape") == 0) {
                $tapechecked = 'checked = "checked"';
            }
            if (strcmp($format_value, "dvd") == 0) {
                $dvdchecked = 'checked = "checked"';
            }
            if (strcmp($format_value, "usb") == 0) {
                $usbchecked = 'checked = "checked"';
            }

            $text .= '<br/> Format:';
            if (strlen($error_message_format) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_format . ')</span>';
            }
            $text .= '<br/><input type="radio" name="format" value="vinyl" ' . $vinylchecked . '>Vinyl &nbsp; &nbsp;';
            $text .= '<input type="radio" name="format" value="cd" ' . $cdchecked . '>CD &nbsp; &nbsp;';
            $text .= '<input type="radio" name="format" value="dvd" ' . $dvdchecked . '>DVD  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="format" value="vhs" ' . $vhschecked . '>VHS  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="format" value="tape" ' . $tapechecked . '>tape  &nbsp; &nbsp';
            $text .= '<input type="radio" name="format" value="USB" ' . $usbchecked . '>USB  &nbsp; &nbsp;';
            $text .= '<br/><br/>';

            if ($sub == true) {
                $type_value = $type;

            } else {
                $type_value = $r['type'];
            }

            $singlechecked = "";
            $epchecked = "";
            $albumchecked = "";
            $doublechecked = "";
            $videochecked = "";

            if (strcmp($type_value, "single") == 0) {
                $singlechecked = 'checked = "checked"';
            }
            if (strcmp($type_value, "ep") == 0) {
                $epchecked = 'checked = "checked"';
            }
            if (strcmp($type_value, "album") == 0) {
                $albumchecked = 'checked = "checked"';
            }
            if (strcmp($type_value, "doublealbum") == 0) {
                $doublechecked = 'checked = "checked"';
            }
            if (strcmp($type_value, "video") == 0) {
                $videochecked = 'checked = "checked"';
            }

            $text .= 'Type: ';
            if (strlen($error_message_type) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_type . ')</span>';
            }
            $text .= '<br/> <input type="radio" name="rtype" value="single" ' . $singlechecked . '>single  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="rtype" value="ep" ' . $epchecked . '>ep  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="rtype" value="doublealbum" ' . $doublechecked . '>doublealbum  &nbsp; &nbsp; ';
            $text .= '<input type="radio" name="rtype" value="album" ' . $albumchecked . '>album  &nbsp; &nbsp; ';
            $text .= '<input type="radio" name="rtype" value="video" ' . $videochecked . '>video  &nbsp; &nbsp;';

            if ($sub == true) {
                $size_value = $size;

            } else {
                $size_value = $r['size'];
            }

            $sevenchecked = "";
            $tenchecked = "";
            $twelvechecked = "";
            $nachecked = "";

            if ($size_value == 7) {
                $sevenchecked = 'checked ="checked"';
            }
            if ($size_value == 10) {
                $tenchecked = 'checked = "checked"';
            }
            if ($size_value == 12) {
                $twelvechecked = 'checked = "checked"';
            }
            else {
                $nachecked = 'checked = "checked"';
            }

            $text .= '<br/><br/>  Size (7" ...)';
            if (strlen($error_message_size) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_size . ')</span>';
            }
            $text .= '<br/> <input type="radio" name="size" value="7" ' . $sevenchecked . '>7"  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="size" value="10" ' . $tenchecked . '>10"  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="size" value="12"' . $twelvechecked . '>12"  &nbsp; &nbsp;';
            $text .= '<input type="radio" name="size" value="" ' . $nachecked . ' >n/a  &nbsp; &nbsp;';
            $text .= '<br/><br/>';
            $text .= '<h2>Comments</h2>';
            if (strlen($error_message_comments) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_comments . ')</span>';
            }

            if($sub == TRUE && !empty($_POST['comments'])){
                $comments = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST['comments'])));
                $comment = esc_textarea($comments);
            }
            else {
                $comment = $r['comment'];
            }
            $text .= '<textarea name="comments" rows="20" cols="50">' . $comment . '</textarea> <br/><br/>';
            //this gets the tracklist and lyrics and lists them.
            $id = $r['id'];
            $details = $wpdb->get_results('SELECT *  FROM ' . $tracks . ' WHERE record_id = ' . $id . ' ORDER BY tracknr asc ', ARRAY_A);
            $tracklist = "";

            $lyrics = "";
            $song = "";
            foreach ($details as $t) {
                $song = stripslashes($t['title']);
                $song = implode("\n", array_map('sanitize_text_field', explode("\n", $song)));
                $tracklist .= $song;
                $lyric = stripslashes($t['lyrics']);
                $lyric = implode("\n", array_map('sanitize_text_field', explode("\n", $lyric)));
                $lyrics .= $lyric . "----";
            }
            $lyrics = recordbrowser_br2nl($lyrics);

            if (!empty($_POST) && !empty($_POST['tracklist'])) {
                $tracklist =  stripslashes($_POST['tracklist']);
                $tracklist = implode("\n", array_map('sanitize_text_field', explode("\n", $tracklist)));
                $tracklist = esc_textarea($tracklist);
            }

            if (!empty($_POST) && !empty($_POST['lyrics'])) {
                $lyrics =  stripslashes($_POST['lyrics']);
                $lyrics = implode("\n", array_map('sanitize_text_field', explode("\n", $lyrics)));
                $lyrics = esc_textarea($lyrics);
            }
            $uploaddir = wp_get_upload_dir(__DIR__);
            $bigcover = $r['bigcover'];
            $text .= '<h2>Tracklist</h2>';
            $text .= '(one track per line, without tracknumbers!!!';
            $text .= '<br/>No blank lines, except to specify a new side on an LP or a new disc on a double CD)';
            if (strlen($error_message_tracklist) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_tracklist . ')</span>';
            }

            $text .= '<br/><textarea name="tracklist" rows="20" cols="50">' . $tracklist . '</textarea>';
            $text .= '<br/><br/>';
            $text .= '<h2>Lyrics</h2>';
            $text .= 'Add "----" between songs. If a new side on a tape or vinyl is started, add "----" twice on separate lines.<br/>';

            if (strlen($error_message_lyrics) != 0) {
                $text .= '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_lyrics . ')</span>';
            }

            $text .= '<textarea name="lyrics" rows="20" cols="50">' . $lyrics . '</textarea>';
            $text .= '<br/><br/><br/>';
            $text .= 'Change Cover:<br/>';
            $text .= '<input type="file" name="bigcover">';
            $text .= '<br/><br/> Old file: <br/><br/>';
            $text .= '<img class ="rbadmin" src="' . $uploaddir['baseurl'] . '/recordbrowser_covers/' . $bigcover . '"><br/><br/>';
            $text .= wp_nonce_field( 'edit_record', 'recordbrowser_edit_record_nonce_field' );
            $text .= '<input type="submit" value="Save Changes" name="submit" class="button button-primary">';
            $text .= '</form>';
            return $text;
        }
    }
}