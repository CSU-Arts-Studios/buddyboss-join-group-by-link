<?php
/*
Plugin Name: BuddyBoss Join Group By Link
Plugin URI: https://arts-ed.csu.edu.au
Description: Provides a link that can be used to auto join a group.  Link is available from Groups admin page.
Version: 1.0
Author: Patrick McKenzie
Author URI: http://arts-ed.csu.edu.au
License: GPL2
*/

//Ref: https://codex.buddypress.org/plugindev/groups-admin-add-custom-column/

// add the column
function groups_admin_add_join_link_column( $columns ) {
     
    $columns["auto_join_link"] = "Auto Join Link";
     
    return $columns;
 
}
add_filter( "bp_groups_list_table_get_columns", "groups_admin_add_join_link_column" );
 
 
// add the column data for each row
function groups_admin_join_link_column_content( $retval = "", $column_name, $item ) {
    
    if ( "auto_join_link" !== $column_name ) {
        return $retval;
    }
	 
	return "<a href=" . get_site_url() . "/joingroup/" . $item["id"] .">Copy Link</a>";
}
add_filter( "bp_groups_admin_get_group_custom_column", "groups_admin_join_link_column_content", 10, 3 );


// AUTO JOIN URL AND PROCESSING


add_filter( 'generate_rewrite_rules', function ( $wp_rewrite ){
    $wp_rewrite->rules = array_merge(
        ['joingroup/([^/]+)/?$' => 'index.php?joingroup=$matches[1]'],
        $wp_rewrite->rules
    );
} );
add_filter( 'query_vars', function( $query_vars ){
    $query_vars[] = 'joingroup';
    return $query_vars;
} );
add_action( 'template_redirect', function(){
    $group_id = get_query_var( 'joingroup' );
    if ( $group_id ) {

		//Does the group exist?
		$group_exists = groups_get_groupmeta($group_id);
		if (empty($group_exists)) {
			wp_die(
				"<h1>Sorry!</h1>
				No Group exists using this link.<br>
				<br>
				Please contact your subject coordinator for help.
				","Sorry, we can't join you this group.");
		}else{
			// Group exists in database, lets find out more about the user.
			$current_user_id = get_current_user_id();
			$existing_group_member = groups_is_user_member($current_user_id, $group_id);
	
			//Check if they are an existing member?
			if ($existing_group_member) {
				$url = bp_get_group_permalink() . groups_get_slug($group_id);

				//Existing member, forward them to the group!
				wp_safe_redirect( $url );
				exit;

			}else {
				//Not a member, lets add them.
				groups_join_group( $group_id );

				//Once they've been added, redirect them to the group.
				$url = bp_get_group_permalink() . groups_get_slug($group_id);
				wp_safe_redirect( $url );
				exit;
				
			}			
		}	

	    exit; // Don't forget the exit. If so, WordPress will continue executing the template rendering and will not fing anything, throwing the 'not found page' 
    }
} );

//REF: https://wordpress.stackexchange.com/a/230200
// Write a new permalink entry on code activation
register_activation_hook( __FILE__, 'joingroupbyurl_activation' );
function joingroupbyurl_activation() {
    flush_rewrite_rules(); // Update the permalink entries in the database, so the permalink structure needn't be redone every page load
}

// If the plugin is deactivated, clean the permalink structure
register_deactivation_hook( __FILE__, 'joingroupbyurl_deactivation' );
function joingroupbyurl_deactivation() {
    flush_rewrite_rules();
}

?>