<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: http://lipeimagination.info/
Description: This Plugin Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: http://lipeimagination/
Version: 1.3
*/
/*  
    Mat Lipe (mat@lipeimagination.info);

    At Lipe Imagination We believe that information should be free. 
	Feel free to do whatever you want with this code as long as your do 
	not replicate the name of the Plugin or try to pass off something else
	as an actual Lipe Imagination Script.
 
*/
	
//Bring in the functions
require('go-live-functions.php');

//Add the settings to the admin menu
add_action('admin_menu', 'gluu_add_url_options');
	
