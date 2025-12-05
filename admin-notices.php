<?php

add_action ('admin_notices', 'jobman_admin_notice');

// Checks transients 'jobman_notice_class' and 'jobman_notice_message'
// If they're set, display the notice
// notice-error
// notice-warning
// notice-success
// notice-info
function jobman_admin_notice(){
	$class = get_transient ( 'jobman_notice_class' );
	$message = get_transient ( 'jobman_notice_message' );
	if ( $class && $message ){
		$class .= ' notice is-dismissible';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ));
	}
}

// Sets transients to trigger admin messages in admin
// Two optional arguments  for the class and the message
function jobman_admin_notice_popup ( $class = 'notice-info', $message = 'Job Manager Notice' ){
	$seconds = 10;
	set_transient ('jobman_notice_class', $class, $seconds);	
	set_transient ('jobman_notice_message', $message, $seconds);
}

?>