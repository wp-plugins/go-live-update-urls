<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: http://lipeimagination.info/
Description: This Plugin Updates all the URLs in the database to point to the new URL when making your site live or channging domains.
Author: Mat Lipe
Author URI: http://lipeimagination/
Version: 1.2.1
*/
/*  Copyright 2011 Mat Lipe (mat@lipeimagination.info);

    At Lipe Imagination We believe that information should be free. 
	Feel free to do whatever you want with this code as long as your do 
	not replicate the name of the Plugin or try to pass off something else
	as an actual Lipe Imagination Script.
 
*/
	

	
echo $table_prefix;

/* Functions for the options page */	
	function add_url_options(){
		add_options_page("Go Live Setings", "Go Live", "manage_options", basename(__FILE__), "url_options_page");
	}
	
	
	function url_options_page(){
		require('go-live-functions.php');	
		
		 if( isset( $_POST['submit'] ) ){
			$oldurl = $_POST['oldurl'];
			$newurl = $_POST['newurl'];
			make_the_updates($oldurl, $newurl);
		//	update_urls($update_links,$oldurl,$newurl);
			echo '<div id="message" class="updated fade"><p><strong>URLs have been updated.</p></strong><p>You can now uninstall this plugin.</p></div>';
		}
		
		 global $table_prefix; 
		 $pr = strtoupper($table_prefix);
		
?>
	<div class="wrap">
	<h2>Go Live Update Urls</h2>
	<form method="post" action="options-general.php?page=<?php echo basename(__FILE__); ?>">
	<p>This will replace all occurrences "in the entire database" of the old URL with the New URL. <br />Uncheck any tables that you would not like to update.</p>
    <h4> THIS DOES NOT UPDATE THE <?php echo  $pr; ?>OPTIONS TABLE BY DEFAULT DUE TO WIDGET ISSUES. <br>
    YOU MUST MANUALLY CHANGE YOUR SITES URL IN THE DASHBOARD'S GENERAL SETTINGS BEFORE RUNNING THIS PLUGIN! <br>
    IF YOU MUST UPDATE THE <?php echo  $pr; ?>OPTIONS TABLE, RUN THIS PLUGIN THEN CLICK SAVE AT THE BOTTOM ON ALL YOUR WIDGETS, <br>
    THEN RUN THIS PLUGIN WITH THE <?php echo  $pr; ?>OPTIONS BOX CHECKED.</h4>
    <em>Like any other database updating tool, you should always perfrom a backup before running.</em><br>
    <?php make_checked_boxes(); ?>
	<table class="form-table">
	<tr>
	<th scope="row" style="width:150px;"><b>Old URL</b></th>
	<td>
	<input name="oldurl" type="text" id="oldurl" value="" style="width:300px;" />
	</td>
	</tr>
	<tr>
	<th scope="row" style="width:150px;"><b>New URL</b></th>
	<td>
	<input name="newurl" type="text" id="newurl" value="" style="width:300px;" />
	</td>
	</tr>
	</table>
	<p class="submit">
	<input name="submit" value="Make it Happen" type="submit" />
	</p>
	</form>
<?php

} // end of the options_page function

add_action('admin_menu', 'add_url_options'); 
?>