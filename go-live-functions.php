<?php
/** Function for the options page */
function gluu_add_url_options(){
	add_options_page("Go Live Setings", "Go Live", "manage_options", basename(__FILE__), "gluu_url_options_page");
}

function gluu_url_options_page(){
	global $table_prefix;

	//If the Form has been submitted make the updates
	if( isset( $_POST['submit'] ) ){
		$oldurl = trim( strip_tags( $_POST['oldurl'] ) );
		$newurl = trim( strip_tags( $_POST['newurl'] ) );
		
		if( gluu_make_the_updates($oldurl, $newurl) ){
			echo '<div id="message" class="updated fade"><p><strong>URLs have been updated.</p></strong></div>';
		} else {
			echo '<div class="error"><p><strong>You must fill out both boxes to make the update!</p></strong></div>';
		}
	}
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
    
    <?php 
       //Make the boxes to select tables
       gluu_make_checked_boxes(); 
    ?>
	<table class="form-table">
		<tr>
			<th scope="row" style="width:150px;"><b>Old URL</b></th>
			<td><input name="oldurl" type="text" id="oldurl" value="" style="width:300px;" /></td>
		</tr>
		<tr>
			<th scope="row" style="width:150px;"><b>New URL</b></th>
			<td><input name="newurl" type="text" id="newurl" value="" style="width:300px;" /></td>
		</tr>
	</table>
	<p class="submit">
	      <input name="submit" value="Make it Happen" type="submit" />
	</p>
	</form>
   <?php

} // end of the options_page function




/**
 * Creates a list of checkboxes for each table
 */
function gluu_make_checked_boxes(){	
	     global $wpdb, $table_prefix;
		 $god_query = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='".$wpdb->dbname."'"; 
		 $all = $wpdb->get_results($god_query);
		   echo '<br>';
		  foreach($all as $v){
			 if($v->TABLE_NAME != $table_prefix .'options'):
			 	printf('<input name="%s" type="checkbox" value="%s" checked /> %s<br>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
			 else:
			 	printf('<input name="%s" type="checkbox" value="%s" /> %s<br>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
			 endif;
		 }
		 
}

/**
 * Updates the datbase
 * @param string $oldurl the old domain
 * @param string $newurl the new domain
 * @since 7/20/12
 */
function gluu_make_the_updates($oldurl, $newurl){
    global $wpdb;
	//If a box was empty
	if( $oldurl == '' || $newurl == '' ){
		return false;
	}
	
	// If the new domain is the old one with a new subdomain like www
	if( strpos($newurl, $oldurl) != false) {
		list( $subdomain ) = explode( '.', $newurl );
		$double_subdomain = $subdomain . '.' . $newurl;  //Create a match to what the broken one will be
	}

	
	//Go throuch each table sent to be updated
	foreach($_POST as $v => $i){
		if($v != 'submit' && $v != 'oldurl' && $v != 'newurl'){

			$god_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS where TABLE_SCHEMA='".$wpdb->dbname."' and TABLE_NAME='".$v."'";
			$all = $wpdb->get_results($god_query);
			foreach($all as $t){
				$update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$oldurl."','".$newurl."')";
				//Run the query
				$wpdb->query($update_query);
				
				//Fix the dub dubs if this was the old domain with a new sub
				if( isset( $double_subdomain ) ){
					$update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$double_subdomain."','".$newurl."')";
					//Run the query
					$wpdb->query($update_query);
				}
			}
		}
	}
	return true;
}
	 