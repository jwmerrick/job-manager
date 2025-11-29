<?php
// Refactoring admin-jobs to support fixing security issues.
// Need a new endpoint for job edit admin.
// Was jobman_edit_job handlers
// Modifying to use admin_post.php instead: https://developer.wordpress.org/reference/hooks/admin_post_action/
// JM 11/28/25

add_action( 'admin_post_job_edit', 'jobman_admin_job_edit' );

// Callback for admin-post.php when action "job_edit" is passed
function jobman_admin_job_edit() {
    // Handle request then generate response using echo or leaving PHP and using HTML
    error_log ('job edit handler triggered...');
    error_log ( var_export($_REQUEST, true) );

	if( array_key_exists( 'jobmansubmit', $_REQUEST ) ) {
	// Job form has been submitted. Update the database.
        $jobid = $_REQUEST['jobman-jobid'];
	    check_admin_referer( 'jobman-edit-job-' . $jobid );         // Confirm we're getting a valid call
        error_log ('Editing job #' . $jobid);
        if( $jobid == 'new' ){
            jobman_updatedb_add();
        } else {
            jobman_updatedb_edit();
        }
	}

    jobman_jobedit_redirect();
}

// Build a blank post outline to use for creating new a job
function jobman_build_new_post(){
	global $wpdb, $current_user;
	$options = get_option( 'jobman_options' );

	wp_get_current_user();

	if( array_key_exists( 'jobman-displaystartdate', $_REQUEST ) && ! empty( $_REQUEST['jobman-displaystartdate'] ) )
		$displaystartdate = date( 'Y-m-d H:i:s', strtotime( stripslashes( $_REQUEST['jobman-displaystartdate'] ) ) );
	else
		$displaystartdate = date( 'Y-m-d H:i:s' );

	$page = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_status' => 'publish',
				'post_content' => '',
				'post_name' => strtolower( str_replace( ' ', '-', $_REQUEST['jobman-title'] ) ),
				'post_title' => stripslashes( html_entity_decode( $_REQUEST['jobman-title'] ) ),
				'post_type' => 'jobman_job',
				'post_date' => $displaystartdate,
				'post_date_gmt' => $displaystartdate,
				'post_parent' => $options['main_page']);

    return $page;
}

// Code from jobman_updatedb responsible for addding a new job
function jobman_updatedb_add(){
    if( 'new' == $_REQUEST['jobman-jobid'] ) {
            $id = wp_insert_post( jobman_build_new_post() );
            $options = get_option( 'jobman_options' );
            $fields = $options['job_fields'];
            if( count( $fields ) > 0 ) {
                foreach( $fields as $fid => $field ) {
                    if($field['type'] != 'file' && ( ! array_key_exists( "jobman-field-$fid", $_REQUEST ) || '' == $_REQUEST["jobman-field-$fid"] ) )
                        continue;

                    if( 'file' == $field['type'] && ! array_key_exists( "jobman-field-$fid", $_FILES ) )
                        continue;

                    $data = '';
                    switch( $field['type'] ) {
                        case 'file':
                            if( is_uploaded_file( $_FILES["jobman-field-$fid"]['tmp_name'] ) ) {
                                $upload = wp_upload_bits( $_FILES["jobman-field-$fid"]['name'], NULL, file_get_contents( $_FILES["jobman-field-$fid"]['tmp_name'] ) );
                                $filetype = wp_check_filetype( $upload['file'] );
                                if( ! $upload['error'] ) {
                                    $attachment = array(
                                                    'post_title' => '',
                                                    'post_content' => '',
                                                    'post_status' => 'publish',
                                                    'post_mime_type' => $filetype['type']
                                                );
                                    $data = wp_insert_attachment( $attachment, $upload['file'], $id );
                                    $attach_data = wp_generate_attachment_metadata( $data, $upload['file'] );
                                    wp_update_attachment_metadata( $data, $attach_data );
                                }
                            }
                            break;
                        case 'checkbox':
                            $data = implode( ', ', $_REQUEST["jobman-field-$fid"] );
                            break;
                        default:
                            $data = $_REQUEST["jobman-field-$fid"];
                    }

                    add_post_meta( $id, "data$fid", $data, true );
                }
            }

            add_post_meta( $id, 'displayenddate', stripslashes( $_REQUEST['jobman-displayenddate'] ), true );
            add_post_meta( $id, 'iconid', $_REQUEST['jobman-icon'], true );
            add_post_meta( $id, 'email', $_REQUEST['jobman-email'], true );

            if( array_key_exists( 'jobman-highlighted', $_REQUEST ) && $_REQUEST['jobman-highlighted'] )
                add_post_meta( $id, 'highlighted', 1, true );
            else
                add_post_meta( $id, 'highlighted', 0, true );
        }
}

// Code from jobman_updatedb responsible for editing an existing job
function jobman_updatedb_edit(){
    global $current_user;
    $data = get_post( $_REQUEST['jobman-jobid'] );

    if( ! current_user_can( 'edit_others_posts' ) && $data->post_author != $current_user->ID )
        return 4;

    $page['ID'] = $_REQUEST['jobman-jobid'];
    $id = wp_update_post( $page );
    $options = get_option( 'jobman_options' );
    $fields = $options['job_fields'];
    if( count( $fields ) > 0 ) {
        foreach( $fields as $fid => $field ) {
            if( 'file' == $field['type'] && ! array_key_exists( "jobman-field-$fid", $_FILES ) && ! array_key_exists( "jobman-field-delete-$fid", $_REQUEST ) )
                continue;

            $data = '';
            switch( $field['type'] ) {
                case 'file':
                    if( array_key_exists( "jobman-field-delete-$fid", $_REQUEST ) ) {
                        wp_delete_attachment( $_REQUEST["jobman-field-current-$fid"] );
                    }
                    else if( is_uploaded_file( $_FILES["jobman-field-$fid"]['tmp_name'] ) ) {
                        $upload = wp_upload_bits( $_FILES["jobman-field-$fid"]['name'], NULL, file_get_contents( $_FILES["jobman-field-$fid"]['tmp_name'] ) );
                        if( ! $upload['error'] ) {
                            // Delete the old attachment
                            if( array_key_exists( "jobman-field-current-$fid", $_REQUEST ) )
                                wp_delete_attachment( $_REQUEST["jobman-field-current-$fid"] );
                            $filetype = wp_check_filetype( $upload['file'] );
                            $attachment = array(
                                            'post_title' => '',
                                            'post_content' => '',
                                            'post_status' => 'publish',
                                            'post_mime_type' => $filetype['type']
                                        );
                            $data = wp_insert_attachment( $attachment, $upload['file'], $id );
                            $attach_data = wp_generate_attachment_metadata( $data, $upload['file'] );
                            wp_update_attachment_metadata( $data, $attach_data );
                        }
                        else {
                            $data = get_post_meta( $id, "data$fid", true );
                        }
                    }
                    else {
                        $data = get_post_meta( $id, "data$fid", true );
                    }
                    break;
                case 'checkbox':
                    if( array_key_exists( "jobman-field-$fid", $_REQUEST ) && is_array( $_REQUEST["jobman-field-$fid"] ) )
                        $data = implode( ', ', $_REQUEST["jobman-field-$fid"] );
                    break;
                default:
                    if( array_key_exists( "jobman-field-$fid", $_REQUEST ) )
                        $data = $_REQUEST["jobman-field-$fid"];
            }

            update_post_meta( $id, "data$fid", $data );
        }
    }

    update_post_meta( $id, 'displayenddate', stripslashes( $_REQUEST['jobman-displayenddate'] ) );
    if( array_key_exists( 'jobman-icon', $_REQUEST) ){
        update_post_meta( $id, 'iconid', $_REQUEST['jobman-icon'] );
    }
    update_post_meta( $id, 'email', $_REQUEST['jobman-email'] );

    if( array_key_exists( 'jobman-highlighted', $_REQUEST ) && $_REQUEST['jobman-highlighted'] )
        update_post_meta( $id, 'highlighted', 1 );
    else
        update_post_meta( $id, 'highlighted', 0 );
}

// Redirect helper: back to job list after a job edit
function jobman_jobedit_redirect() {
    $redirect_url = admin_url('admin.php?page=jobman-list-jobs');
    wp_safe_redirect( $redirect_url );
    exit;
}

?>