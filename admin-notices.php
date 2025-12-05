<?php

add_action ('admin_notices', 'jobman_admin_notice_popup');
add_action('admin_notices', 'jobman_admin_notice_welcome');
add_action('admin_init', 'jobman_nag_ignore');

// Checks transients 'jobman_notice_class' and 'jobman_notice_message'
// If they're set, display the notice
// notice-error
// notice-warning
// notice-success
// notice-info
function jobman_admin_notice_popup(){
	$class = get_transient ( 'jobman_notice_class' );
	$message = get_transient ( 'jobman_notice_message' );
	if ( $class && $message ){
		$class .= ' notice is-dismissible';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ));
	}
}

// Sets transients to trigger admin messages in admin
// Two optional arguments  for the class and the message
// Optional argument for transient duration (default 10 seconds)
// Fire ths function with appropriate arguments to schedule admin notice on next page load.
function jobman_admin_notice ( $class = 'notice-info', $message = 'Job Manager Notice', $seconds = 10 ){
	set_transient ('jobman_notice_class', $class, $seconds);	
	set_transient ('jobman_notice_message', $message, $seconds);
}

/* Display a notice that can be dismissed */
function jobman_admin_notice_welcome() {
	if ( current_user_can( 'install_plugins' ) ){
		global $current_user ;
		$user_id = $current_user->ID;
		/* Check that the user hasn't already clicked to ignore the message */
		if ( ! get_user_meta($user_id, 'jobman_ignore_notice') ) {
			?>
			<div class="updated"><p>
			Thanks! We hope you enjoy using <a href="https://github.com/thomastownsend/job-manager" target="_blank"><b>Job Manager</b></a>.
			Please check-out and contribute to our <a href="https://wordpress.org/support/plugin/job-manager/" target="_blank"><b>Support Forum</b></a>.
			Thanks for your help! | <a href="<?php printf('%1$s', '?jobman_nag_ignore=0'); ?>">Hide Notice</a>
			</p></div>
			<?php
		}
	}
}

// Called from the "Hide Notice" link in the job manager welcome message
// Stores flag in the database to stop showing the message
function jobman_nag_ignore() {
	global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['jobman_nag_ignore']) && '0' == $_GET['jobman_nag_ignore'] ) {
             add_user_meta($user_id, 'jobman_ignore_notice', 'true', true);
	 
        // Always redirect after processing
        wp_safe_redirect( admin_url( 'admin.php?page=jobman-conf' ) );
	}
}

?>