<?php


function recordbrowser_add_record() {
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
        $buyme_link = sanitize_text_field($_POST['buymelink']);
        $buyme_link = filter_var($buyme_link, FILTER_SANITIZE_URL);
        $tracklist=$_POST['tracklist'];
        $lyrics=$_POST['lyrics'];
        $sub= sanitize_text_field($_POST['sub']);
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
        if ( recordbrowser_validate_url($buyme_link) != TRUE) {
            $safe_record = FALSE;
            $error_message_buymelink = 'The Url must be in a proper format, eg: http://www.example.com';
        }





    }

	if($sub==true && $safe_record == TRUE) {


        check_admin_referer(  'add_record', 'recordbrowser_add_nonce_field' );
		global $wpdb;
		$rb_records = $wpdb->prefix . 'recordbrowser_records';
		$rb_tracks = $wpdb->prefix . 'recordbrowser_tracks';

        //making sure the user can only upload imagefiles, if there is no image use generic!
        $info = FALSE;
        if(file_exists($_FILES['bigcover']['tmp_name'])) {
            $info = getimagesize($_FILES['bigcover']['tmp_name']);
        }
        if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
            $dateiname="generic.jpg";
        }
        else {
            $extension = recordbrowser_get_extension($_FILES['bigcover']['name']);
            $extension = strtolower($extension);
            $dateiname="big".date('U').".".$extension;
            $bigcover = $dateiname;
            $destination = trailingslashit( $upload['basedir'] ) . "recordbrowser_covers/" . $dateiname;
            move_uploaded_file($_FILES['bigcover']['tmp_name'], $destination);
        }
		//echo $tracklist;
		$tracklist=nl2br($tracklist);
		$tracks=explode("<br />",$tracklist);

        $lyrics=nl2br($lyrics);
        $lyrics=explode("----",$lyrics);
		$owner = "";
		$wpdb->insert( 
			$rb_records, 
			array( 
				'bandname' => $bandname, 
				'title' => $name, 
				'bigcover' => $dateiname, 
				'released' => $year, 
				'format' => $format,
				'type' => $type,
				'size' => $size, 
				'country' => $country, 
				'label' => $label, 
				'comment' => $comments, 
				'cat' => $cat,
                'owner' => get_current_user_id(),
                'buyme' => $buyme_link
			)
		);
		 
		$r = $wpdb->insert_id;
		for($i=0;$i<sizeof($tracks);$i++) {
            if (sizeof($lyrics) == 0) {
                $lyric = "";
            }
            else {
                $lyric = isset($lyrics[1]) ? $lyrics[1] : null;
            }

            if (sizeof($tracks) == 0) {
                $track = "";
            }
            else {
                $track = $tracks[$i];
            }
            $tnr = $i + 1;
            $wpdb->insert(
                $rb_tracks,
                array(
                    'record_id' => $r,
                    'tracknr' => $tnr,
                    'title' => $track,
                    'lyrics' => $lyric
                )
            );
        }
        $text = '<h1>Record has been added</h1>';
        $text .= '<br><a href="' . admin_url( 'admin.php' ) . '?page=recordbrowseradd">Add more records.</a>';
		echo $text;
		}
	else {
?>
		<h1>Add New Record</h1>
		<form enctype="multipart/form-data"  method="post" action="<?php echo admin_url( 'admin.php' ) . "?page=recordbrowseradd"; ?>">
			<h2>Record Details</h2>
			<input type="hidden" name="sub" value="true">
			 <input type="hidden" name="action" value="recordbrowseradd" />
			Bandname:
            <?php if(strlen( $error_message_bandname )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_bandname . ')</span>';} ?>
			<br/><input type="text" minlength="1" maxlength="120   size="90" name="bandname"<?php
            if(!empty($_POST) && !empty($_POST['bandname'])) {
                $bandname = sanitize_text_field($_POST['bandname']);
                    echo ' value="'. esc_textarea($bandname) . '" '; }
            ?>><br/><br/>
			Title:
            <?php if(strlen( $error_message_name )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_name . ')</span>';} ?>
            <br/><input type="text" minlength="1" maxlength="120  size="90" name="name"<?php
            if(!empty($_POST) && !empty($_POST['name'])) {
                echo $name . ' value="'. esc_textarea($name) . '" '; }
            ?>><br/><br/>
			Label:
            <?php if(strlen( $error_message_label )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_label . ')</span>';} ?>
            <br/><input type="text"  size="40" name="rlabel" <?php
            if(!empty($_POST) && !empty($_POST['rlabel'])) {
                $rlabel = sanitize_text_field($_POST['rlabel']);
                    echo ' value="'. esc_textarea($rlabel) . '" '; }
            ?>><br/><br/>
			Country:
            <?php if(strlen( $error_message_country )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_country . ')</span>';} ?>
            <br/><input type="text" size="40" name="country" <?php
            if(!empty($_POST) && !empty($_POST['country'])) {
                $country = sanitize_text_field($_POST['country']);
                    echo ' value="'. esc_textarea($country) . '" '; }
            ?>><br/><br/>
			Catalogue number:
            <?php if(strlen( $error_message_catnr )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_catnr . ')</span>';} ?>
            <br/><input type="text" size="40" name="catnr" <?php
            if(!empty($_POST) && !empty($_POST['catnr'])) {
                $catnr = sanitize_text_field($_POST['year']);
                    echo ' value="'. esc_textarea($catnr) . '" '; }
            ?>><br/><br/>
            Year:
            <?php if(strlen( $error_message_year )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_year . ')</span>';} ?>
            <br/><input type="text" size="40" name="year" minlength="4" maxlength="4" <?php
                if(!empty($_POST) && !empty($_POST['year'])) {
                    $year = sanitize_text_field($_POST['year']);
                    echo ' value="'. esc_textarea($year) . '" '; }
                ?>><br/><br/>
            Link to buy record (with http://):
            <?php if(strlen( $error_message_buymelink )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_buymelink . ')</span>';} ?>
            <br/><input type="text" size="60" name="buymelink"<?php
            if(!empty($_POST) && !empty($_POST['buymelink'])) {
                $buymelink = sanitize_text_field($_POST['buymelink']);
                echo ' value="'. esc_textarea($buymelink) . '" '; }
            ?>><br/><br/>
			<br/>
			Format:
            <?php if(strlen( $error_message_format )!=0){ echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_format . ')</span>';}
            $cdchecked = "";
            $vinylchecked = "";
            $vhschecked = "";
            $tapechecked = "";
            $dvdchecked = "";
            $usbchecked = "";

            if(!empty($_POST) && !empty($_POST['format'])) {
                if (strcmp($_POST['format'], "cd") == 0) {
                    $cdchecked = 'checked ="checked"';
                }
                if (strcmp($_POST['format'], "vinyl") == 0) {
                    $vinylchecked = 'checked = "checked"';
                }
                if (strcmp($_POST['format'], "vhs") == 0) {
                    $vhschecked = 'checked = "checked"';
                }
                if (strcmp($_POST['format'], "tape") == 0) {
                    $tapechecked = 'checked = "checked"';
                }
                if (strcmp($_POST['format'], "dvd") == 0) {
                    $dvdchecked = 'checked = "checked"';
                }
                if (strcmp($_POST['format'], "usb") == 0) {
                    $usbchecked = 'checked = "checked"';
                }
            }
            else {
                $vinylchecked = 'checked = "checked"';
            }
            ?>
            <br/>
			<input type="radio" name="format" value="vinyl" <?php echo $vinylchecked;?>>Vinyl &nbsp; &nbsp;
			<input type="radio" name="format" value="cd" <?php echo $cdchecked;?>>CD &nbsp; &nbsp;
			<input type="radio" name="format" value="dvd" <?php echo $dvdchecked;?>>DVD  &nbsp; &nbsp;
			<input type="radio" name="format" value="vhs" <?php echo $vhschecked;?>>VHS  &nbsp; &nbsp;
			<input type="radio" name="format" value="tape" <?php echo $tapechecked;?>>tape  &nbsp; &nbsp
			<input type="radio" name="format" value="USB" <?php echo $usbchecked;?> >USB  &nbsp; &nbsp
			<br/><br/>
            <?php


            $singlechecked = "";
            $epchecked = "";
            $albumchecked = "";
            $doublechecked = "";
            $videochecked = "";

            if(!empty($_POST) && !empty($_POST['rtype'])) {
                $type = $_POST['rtype'];

                if (strcmp($type, "single") == 0) {
                    $singlechecked = 'checked = "checked"';
                }
                if (strcmp($type, "ep") == 0) {
                    $epchecked = 'checked = "checked"';
                }
                if (strcmp($type, "album") == 0) {
                    $albumchecked = 'checked = "checked"';
                }
                if (strcmp($type, "doublealbum") == 0) {
                    $doublechecked = 'checked = "checked"';
                }
                if (strcmp($type, "video") == 0) {
                    $videochecked = 'checked = "checked"';
                }
            }
            else {
                $singlechecked = 'checked = "checked"';
            }
        ?>

            Type:
            <?php if(strlen( $error_message_type )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_type . ')</span>';} ?>
            <br/>
            <input type="radio" name="rtype" value="single" <?php echo $singlechecked;?>>single  &nbsp; &nbsp;
			<input type="radio" name="rtype" value="ep" <?php echo $epchecked;?>>ep  &nbsp; &nbsp;
			<input type="radio" name="rtype" value="album" <?php echo $albumchecked;?>>album  &nbsp; &nbsp;
			<input type="radio" name="rtype" value="doublealbum" <?php echo $doublechecked;?>>double album  &nbsp; &nbsp;
			<input type="radio" name="rtype" value="video" <?php echo $videochecked;?> >video  &nbsp; &nbsp;
			<br/><br/>

            <?php
            $sevenchecked = "";
            $tenchecked = "";
            $twelvechecked = "";
            $nachecked = "";
            if(!empty($_POST) && !empty($_POST['size'])) {


                if ($_POST['size'] == 7) {
                    $sevenchecked = 'checked ="checked"';
                }
                if ($_POST['size'] == 10) {
                    $tenchecked = 'checked = "checked"';
                }
                if ($_POST['size'] == 12) {
                    $twelvechecked = 'checked = "checked"';
                }
                if ($_POST['size'] == "") {
                    $nachecked = 'checked = "checked"';
                }
            }
            else {
                $sevenchecked = 'checked = "checked"';
            }
            ?>
			Size (7" ...)
            <?php if(strlen( $error_message_size )!=0){ echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_size . ')</span>';} ?>
            <br/>
			<input type="radio" name="size" value="7" <?php echo $sevenchecked;?>>7  &nbsp; &nbsp;
			<input type="radio" name="size" value="10" <?php echo $tenchecked;?>>10  &nbsp; &nbsp;
			<input type="radio" name="size" value="12" <?php echo $twelvechecked;?>>12  &nbsp; &nbsp;
			<input type="radio" name="size" value="" <?php echo $nachecked;?>>n/a  &nbsp; &nbsp;
			<br/><br/>
			<h2>Comments</h2>
            <?php if(strlen( $error_message_comments )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_comments . ')</span><br/>';} ?>
			<textarea name="comments" rows="20" cols="50"><?php
                if(!empty($_POST) && !empty($_POST['comments'])) {
                    $comments = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST['comments'])));
                    echo esc_textarea($comments); } ?>
            </textarea>
		<br/><br/>

		<h2>Tracklist</h2>
		(one track per line, without tracknumbers!!!
		<br/>
		No blank lines, except to specify a new side on an LP or a new disc on a double CD)
		<br/>
            <?php if(strlen( $error_message_tracklist )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_tracklist . ')</span><br/>';} ?>
		<textarea name="tracklist" rows="20" cols="50"> <?php
            if (!empty($_POST) && !empty($_POST['tracklist'])) {
                $tracklist =  stripslashes($_POST['tracklist']);
                $tracklist = implode("\n", array_map('sanitize_text_field', explode("\n", $tracklist)));
                echo esc_textarea($tracklist);
            } ?></textarea>
		<br/><br/>
		<h2>Lyrics</h2>
		add "----" between songs. If a new side on a tape or vinyl is started, add "----" twice on separate lines.<br/>
            <?php if(strlen( $error_message_lyrics )!=0){echo '<span style="color:#ff0071; font-weight:bold; display:inline-block">(' . $error_message_lyrics . ')</span><br/>';} ?>
		<textarea name="lyrics" rows="20" cols="50"><?php
            if (!empty($_POST) && !empty($_POST['lyrics'])) {
                $lyrics =  stripslashes($_POST['lyrics']);
                $lyrics = implode("\n", array_map('sanitize_text_field', explode("\n", $lyrics)));
                echo esc_textarea($lyrics); } ?></textarea>
		<br/><br/>
		<br/> 
		big cover:<br/>
		<input type="file" name="bigcover">
		<br/><br/>
        <?php wp_nonce_field( 'add_record', 'recordbrowser_add_nonce_field' ); ?>
		<input type="submit" value="Save new Record" name="submit" class="button button-primary">

		</form>

<?php
	}
}
