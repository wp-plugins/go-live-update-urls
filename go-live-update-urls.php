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
    var $double_subdomain = false; //keep track if going to a subdomain
    
    
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
        $this->double_subdomain = $subdomain . '.' . $newurl;  //Create a match to what the broken one will be
    }

    
    //Go throuch each table sent to be updated
    foreach($_POST as $v => $i){
        
        //Send the options table through the seralized safe Update
        if( $v == $wpdb->options ){
          $this->UpdateSeralizedTable($wpdb->options, 'option_value'); 
        }
        
        if($v != 'submit' && $v != 'oldurl' && $v != 'newurl'){

            $god_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS where TABLE_SCHEMA='".$wpdb->dbname."' and TABLE_NAME='".$v."'";
            $all = $wpdb->get_results($god_query);
            foreach($all as $t){
                $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$oldurl."','".$newurl."')";
                //Run the query
                $wpdb->query($update_query);
                
                //Fix the dub dubs if this was the old domain with a new sub
                if( isset( $this->double_subdomain ) ){
                    $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$this->double_subdomain."','".$newurl."')";
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
     */
    function UpdateSeralizedTable( $table, $column = false ){
        global $wpdb;
        $pk = $wpdb->get_results("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
        $primary_key_column = $pk[0]->Column_name;

        //Get all the Seralized Rows and Replace them properly
        $rows = $wpdb->get_results("SELECT $primary_key_column, $column FROM $table WHERE $column LIKE 'a:%' OR $column LIKE 'o:%'");
        
        foreach( $rows as $row ){
            if( is_bool($data = @unserialize($row->{$column})) ) continue;

            $clean = $this->replaceTree($data, $this->oldurl, $this->newurl);
            //If we switch to a submain we have to run this again to remove the doubles
            if( $this->double_subdomain ){
                  $clean = $this->replaceTree($clean, $this->double_subdomain, $this->newurl); 
            }
            
            //Add the newly seralized array back into the database
            $wpdb->query("UPDATE $table SET $column='".serialize($clean)."' WHERE $primary_key_column='".$row->{$primary_key_column}."'");     
       
        }
    }
    
    
    
    /**
     * Replaces all the urls in a multi dementional array or Object
     * 
     * @uses itself to call each level of the array
     * @uses $oldurl and $newurl Class Vars
     * @since 2.0
     * 
     * @param array|object|string $data to change
     * @param string $old the old string
     * @param string $new the new string
     * @param bool [optional] $changeKeys to replace string in keys as well - defaults to false
     * 
     */
    function replaceTree( $data, $old, $new, $changeKeys = false ){
        if( !($is_array = is_array( $data )) && !is_object( $data) ){
            return str_replace( $old, $new, $data );            
        }
            
        foreach( $data as $key => $item ){
            if( $changeKeys ){
                $key = str_replace( $old, $new, $key );
            }
            
            if( $is_array  ){
                    $data[$key] = $this->replaceTree($item, $old, $new);
            } else {
                    $data->{$key} = $this->replaceTree($item, $old, $new);
            }
        }
        return $data;
    }
    
    
    

}

$GoLiveUpdateUrls = new GoLiveUpdateUrls();
	