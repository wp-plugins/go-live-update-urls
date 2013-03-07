<?php
/**
 * Methods for the Go Live Update Urls Plugin
 * @author Mat Lipe
 * @since 2.1
 * 
 * @TODO Cleanup the Names and formatting
 */
class GoLiveUpdateUrls{
    var $oldurl = false;
    var $newurl = false;
    var $double_subdomain = false; //keep track if going to a subdomain
    
    /**
     * @since 2.0
     */
    function __construct(){
        //Add the settings to the admin menu
        add_action('admin_menu', array( $this,'gluu_add_url_options') );
        
        //Add the CSS
        add_action( 'admin_head', array( $this,'css') );
    }
    
    /**
     * For adding Css to the admin 
     *
     * @since 2.0
     */
    function css(){
      ?><style type="text/css"><?php
            include( $this->fileHyercy('go-live-update-urls.css') );
      ?></style><?php
        
    }
    

    /**
     * Menu Under Tools Menu
     * 
     * @since 2.0
     */
    function gluu_add_url_options(){
       add_management_page("go-live-setting", "Go Live", "manage_options", basename(__FILE__), array( $this,"adminToolsPage") );
    }




    /**
     * Output the Admin Page for using this plugin
     * 
     * @since 2.0
     * 
     */
    function adminToolsPage(){
        global $table_prefix;

        //If the Form has been submitted make the updates
        if( isset( $_POST['gluu-submit'] ) ){
            $this->oldurl = trim( strip_tags( $_POST['oldurl'] ) );
            $this->newurl = trim( strip_tags( $_POST['newurl'] ) );
        
            if( $this->gluu_make_the_updates() ){
                echo '<div id="message" class="updated fade"><p><strong>URLs have been updated.</p></strong></div>';
            } else {
                echo '<div class="error"><p><strong>You must fill out both boxes to make the update!</p></strong></div>';
            }
        }
        
        require( $this->fileHyercy('admin-tools-page.php') );
} 


   /**
    * Allows for Overwritting files in the child theme
    * @since 2.0
    * @param string $file the name of the file to overwrite
    * 
    * @param bool $url if the file is being used for a url
    */  
    function fileHyercy( $file , $url = false){
        if ( !$theme_file = locate_template(array('go-live-update-urls/'.$file)) ) {
             $theme_file = GLUU_VIEWS_DIR . $file;
        } 
        return $theme_file;
    
    }



   /**
    * Creates a list of checkboxes for each table
    * 
    * @since 2.0
    * @uses by the view admin-tools-page.php
    * 
    * @filter 'gluu_table_checkboxes' with 2 param
    *     * $output - the html formatted checkboxes
    *     * $tables - the complete tables object
    * 
    * 
    */
    function makeCheckBoxes(){ 
         global $wpdb;
         $god_query = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='".$wpdb->dbname."'"; 
         $tables = $wpdb->get_results($god_query);
         
         $output =  '<ul id="gluu-checkboxes">';
          foreach($tables as $v){
             if($v->TABLE_NAME == $wpdb->options):
                $output .= sprintf('<li><input name="%s" type="checkbox" value="%s" checked /> %s - <strong><em>Seralized Safe</strong></em></li>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
             else:
                $output .= sprintf('<li><input name="%s" type="checkbox" value="%s" checked /> %s</li>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
             endif;
         }
          
         $output .= '</ul>';
          
         return apply_filters('gluu_table_checkboxes', $output, $tables ); 
          
         
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
     * @since 2.1
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
        $rows = $wpdb->get_results("SELECT $primary_key_column, $column FROM $table WHERE $column LIKE 'a:%' OR $column LIKE 'O:%'");
        
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
     * Replaces all the occurances of a string in a multi dementional array or Object
     * 
     * @uses itself to call each level of the array
     * @since 2.1
     * 
     * @param array|object|string $data to change
     * @param string $old the old string
     * @param string $new the new string
     * @param bool [optional] $changeKeys to replace string in keys as well - defaults to false
     * 
     */
    function replaceTree( $data, $old, $new, $changeKeys = false ){
        
        if( is_string($data) ){
            return str_replace( $old, $new, $data );            
        }
        
        if( !($is_array = is_array( $data )) && !is_object($data) ){
            return $data;
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
