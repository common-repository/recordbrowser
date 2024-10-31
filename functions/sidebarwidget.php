<?php
$lim=3;
$url="";
$pre_url="";


if(!empty($_POST) && $_POST['rbreload'] == true) {
    echo recordbrowser_slider_wiget($lim, $url, $pre_url);
}