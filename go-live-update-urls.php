<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: http://lipeimagination.info/
Description: This Plugin Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: http://lipeimagination/
Version: 2.0
*/



	
/**
 * Methods for the Go Live Update Urls Plugin
 * @author Mat Lipe
 * @since 2.0
 * 
 * @TODO Cleanup the Names and formatting
 */
class GoLiveUpdateUrls{
    var $oldurl = false;
    var $newurl = false;
    
    
    function __construct(){
        //Add the settings to the admin menu
        add_action('admin_menu', array( $this,'gluu_add_url_options') );
    }
    

    /**
    * options page
    */
    function gluu_add_url_options(){
       add_options_page("Go Live Setings", "Go Live", "manage_options", basename(__FILE__), array( $this,"gluu_url_options_page") );
    }

function gluu_url_options_page(){
    global $table_prefix;

    //If the Form has been submitted make the updates
    if( isset( $_POST['submit'] ) ){
        $this->oldurl = trim( strip_tags( $_POST['oldurl'] ) );
        $this->newurl = trim( strip_tags( $_POST['newurl'] ) );
        
        if( $this->gluu_make_the_updates() ){
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
       $this->gluu_make_checked_boxes(); 
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
 * 
 * @uses the oldurl and newurl set above
 * @since 2.27.13
 */
function gluu_make_the_updates(){
    global $wpdb;
    
    $oldurl = $this->oldurl;
    $newurl = $this->newurl;
    
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
        
        //Send the options table through the seralized safe Update
        if( $v == $wpdb->options ){
          $this->UpdateSeralizedTable($wpdb->options, 'option_value'); 
          continue;  
        }
        
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
                    
                    //Fix the emails breaking by being appended the new subdomain
                    $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '@".$newurl."','@".$oldurl."')";
                    $wpdb->query($update_query);
                }

            }
        }
    }
    return true;
}


/**
 * Goes through a table line by line and updates it
 * 
 * @uses for tables which may contain seralized arrays
 * @since 2.0
 * 
 * @param string $table the table to go through
 * @param string $column to column in the table to go through
 * 
 * @TODO Make go through all columns if not specifed. Currently only Works when Specified
 */
function UpdateSeralizedTable( $table, $column = false ){
    global $wpdb;

    $pk = $wpdb->get_results("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    $primary_key_column = $pk[0]->Column_name;

    $rows = $wpdb->get_results("SELECT $primary_key_column, $column FROM $table");
    
    
    
#-- Start Here
    foreach( $rows as $row ){
        $data = unserialize($row[$column]);
        if( is_array( $data ) ){
            
            ## need this to loop through each array and sub array
            ## create a looping method to call itself
            
            ## each array key needs to be converted one at a time then set back to the original array
            ## If false or not array simply switch the value and set it back to result
          
        }
    }
    
    
    _p( $r ); 
    
    
}



}

$GoLiveUpdateUrls = new GoLiveUpdateUrls;
	