<?php

          # creates a list of checked boxes for each table
function make_checked_boxes(){	
	      global $wpdb;
		 $god_query = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='".$wpdb->dbname."'"; 
		 
		 $all = $wpdb->get_results($god_query);
		   echo '<br>';
		  foreach($all as $v){
			 if($v->TABLE_NAME != 'wp_options'):
			 printf('<input name="%s" type="checkbox" value="%s" checked /> %s<br>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
			 else:
			 printf('<input name="%s" type="checkbox" value="%s" /> %s<br>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
			 
			 endif;
		 }
		 
}

#--------------- Updates the database ----------------------------------------

function make_the_updates($oldurl, $newurl){
	global $wpdb;
	foreach($_POST as $v => $i){
		if($v != 'submit' && $v != 'oldurl' && $v != 'newurl'){
			$god_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS where TABLE_SCHEMA='".$wpdb->dbname."' and TABLE_NAME='".$v."'";
			$all = $wpdb->get_results($god_query);
			
			
			foreach($all as $t){
				$update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$oldurl."','".$newurl."')";
				
				$wpdb->query($update_query);
			}
			
		}
		
	}
	
}

		  
?>		 