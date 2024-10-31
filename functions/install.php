<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
global $recordbrowser_db_version;
$recordbrowser_db_version = "1.0.0";
global $text;
$text = " ";

//creates the two database tables and folders we need to store the information about our records.
function recordbrowser_install() {
	global $wpdb;
	global $recordbrowser_db_version;
	global $tableprefix;
	$options = get_option('recordbrowser_options');
    //Get options entire array

    //if there is no options array yet, fill it with standard settings.
    $recordbrowser_ = get_option('recordbrowser_options');
    $installed_version = $recordbrowser_['dbversion'];
    $no_options = wp_cache_get( 'no_options', 'options' );
    if ( !isset( $no_options['perpage'] ) ) {
        //if there is no options array yet, fill it with standard settings.
        $recordbrowser_['perpage'] = 12;
        $recordbrowser_['columns'] = 2;
        $recordbrowser_['singlecolumns'] = 1;
        $recordbrowser_['orderby'] = "released";
        $recordbrowser_['order'] = "DESC";
    }



	$tableprefix = $wpdb->prefix . 'recordbrowser_';

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

	if ( $installed_version != $recordbrowser_db_version ) {
	// Create table for the songs with lyrics 
	$tracks = $tableprefix . 'tracks';
	$sql = "CREATE TABLE " . $tracks . " (
	   id int NOT NULL AUTO_INCREMENT ,
	   record_id int NOT NULL ,
	   tracknr int NOT NULL ,
	   title  varchar(250) ,
	   lyrics text ,
	   length time ,
	   PRIMARY KEY  (id)
	) ". $charset_collate ."; ";
	dbDelta($sql);
	
	// Create table for the records with all the information needed 
	$records = $tableprefix . 'records';
	$sql = "CREATE TABLE " . $records . " (
	   id int NOT NULL AUTO_INCREMENT ,
	   owner varchar(250) ,
	   title varchar(250) ,
	   bandname varchar(250) ,
	   released year ,
	   country varchar(250) ,
	   cat varchar(250) ,
	   format varchar(250) ,
	   type varchar(250) ,
	   label varchar(250) ,
	   buyme varchar(250) ,	 
	   bigcover varchar(250) ,
	   sound varchar(250) ,
	   size int ,
	   comment text ,
	   PRIMARY KEY  (id)
	) ". $charset_collate . "; ";
	dbDelta($sql);
        //Update entire array
    $recordbrowser_['dbversion'] =  $recordbrowser_db_version;
    update_option('recordbrowser_options', $recordbrowser_);
	}
	$upload = wp_upload_dir();
	$upload_dir = $upload['basedir'];
	$upload_covers = $upload_dir . '/recordbrowser_covers';
	$upload_songs = $upload_dir . '/recordbrowser_songs';
	$wp_fs_d = new WP_Filesystem_Direct( new StdClass() );
	if ( !$wp_fs_d->is_dir( $upload_covers ) && !$wp_fs_d->mkdir( $upload_covers, 0777 ) ) {
		die;
	}
	if ( !$wp_fs_d->is_dir( $upload_songs ) && !$wp_fs_d->mkdir( $upload_songs, 0777 ) ) {
		die;
	}
}


function recordbrowser_install_data($photo) {
	$upload = wp_upload_dir();
    $destination1 = trailingslashit( $upload['basedir'] ) . "recordbrowser_covers/generic.jpg";
    copy (plugin_dir_path( __DIR__ ) . 'images/generic.jpg', $destination1);
	global $wpdb;
	$records = $wpdb->prefix . 'recordbrowser_records';
	$tracks = $wpdb->prefix . 'recordbrowser_tracks';
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $records" );
	if ($count == 0){
	    	//do here
		$bandname = "Bessie Smith";
		$title = "The Bessie Smith Story - Volume 4";
		$year = "1955";
		$format = "vinyl";
		$type = "album";
		$size = "12";
		$label = "Columbia Records";
		$comment = " Re-release from the 1920s and 1930s";
		$tracklist = "Back Water Blues <br />Preachin' The Blues <br /> Moan, Mourners <br /> He's Got Me Goin <br /> Blue Spirit Blues <br /> On Revival Day <br /><br />  Trombone Cholly	 <br /> Send Me To the 'Lectric Chair<br /> Long Old Road<br /> Shipwreck Blues	<br /> Empty Bed Blues";
        $lyrics = lyrics();
        $track=explode("<br />",$tracklist);
        $lyric=nl2br($lyrics);
        $lyric=explode("----",$lyric);
	
		
		
        $dateiname="big".date('U').".jpg";
        $destination = trailingslashit( $upload['basedir'] ) . "recordbrowser_covers/" . $dateiname;
        copy (plugin_dir_path( __DIR__ ) . 'images/bessie.jpg', $destination);



	
		$wpdb->insert( 
			$records, 
			array( 
				'title' => $title, 
				'bandname' => $bandname, 
				'bigcover' => $dateiname,
				'released' => $year,
				'format' => $format,
				'type' => $type,
				'size' => $size,
				'label' => $label,
			) 
		);
			$insert_id = $wpdb->insert_id;
			for($i=0;$i<sizeof($lyric);$i++) {
			  	$tnr=$i+1;
				$wpdb->insert( 
				$tracks, 
				array( 
					'record_id' => $insert_id, 
					'tracknr' => $tnr, 
					'title' => $track[$i],
					'lyrics' =>$lyric[$i]
				), 
				array( 
					'%s', 
					'%s', 
					'%s', 
					'%s' 
				) 
			);
		}

	}
}


function recordbrowser_update_db_check() {
    global $recordbrowser_db_version;
    if ( get_site_option( ' recordbrowser_db_version' ) !=  $recordbrowser_db_version ) {
        recordbrowser_install();
    }
}