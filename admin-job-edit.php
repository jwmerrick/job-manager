<?php
// Refactoring admin-jobs to support fixing security issues.
// Need a new endpoint for job edit admin.
// Was jobman_edit_job handlers
// Modifying to use admin-post.php instead: https://developer.wordpress.org/reference/hooks/admin_post_action/
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
            jobman_updatedb_add();                                  // Add a new job
        } else {
            jobman_updatedb_edit();                                 // Edit an existing job
        }
	}

    jobman_jobedit_redirect();                                      // Return when done processing.
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
                        $data = sanitize_text_field( $_REQUEST["jobman-field-$fid"] );
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

        if( array_key_exists( 'jobman-categories', $_REQUEST ) )
            wp_set_object_terms( $id, $_REQUEST['jobman-categories'], 'jobman_category', false );

        if( $options['plugins']['gxs'] )
            do_action( 'sm_rebuild' );
    }
}

// Code from jobman_updatedb responsible for editing an existing job
// Update fields like "jobman-field-X"
// Which may include update or delete file attachment if included or requested
// Or they can be of type checkbox or text
// Then, remaining fields: jobman-displayenddate, jobman-icon, jobman-email,
// jobman-highlighted, jobman_category
function jobman_updatedb_edit(){
    global $current_user;
    $data = get_post( $_REQUEST['jobman-jobid'] );

    // User is unable to edit this job
    if( ! current_user_can( 'edit_others_posts' ) && $data->post_author != $current_user->ID )
        return 4;

    $job_data_raw = jobman_get_req_fields();
    $job_data_san = jobman_sanitize_req_fields($job_data_raw);

    $page['ID'] = $job_data_san['jobman-jobid'];
    $id = wp_update_post( $page );
    $options = get_option( 'jobman_options' );

    // Process the sanitized submitted data
    foreach( $job_data_san as $fid => $field ){
        // Named ones
        switch ($fid){
            case 'jobman-jobid':
                break;                                              // Don't change the id
            case 'jobman-title':
                $page['post_title'] = $field;
                wp_update_post ( $page );
                break;
            case 'jobman-displaystartdate':
                $page['post_date'] = $field;
                wp_update_post ( $page );
                break;
            case 'jobman-displayenddate':
                update_post_meta( $id, 'displayenddate', stripslashes( $field ) );
                break;
            case 'jobman-icon':
                update_post_meta( $id, 'iconid', $field );
                break;
            case 'jobman-highlighed':
                update_post_meta( $id, 'highlighted', $field );
                break;
            case 'jobman-categories':
                wp_set_object_terms( $id, $field, 'jobman_category', false );
                break;
        // Editable ones like 'job-field-X'
            default:
                //error_log (var_export($options['job_fields']));
                $field_num = substr( $fid, strlen('jobman-field-') );
                if (is_numeric($field_num)) {
                    $field_type = $options['job_fields'][$field_num]['type'];
                    switch ( $field_type ){
                        case 'file':                                // Should be either delete or upload    
                            if ( $field == 'FILE_UPLOAD' ){
                                $att_id = jobman_job_field_upload( $id, $field_num );   
                                update_post_meta( $id, 'data' . $field_num, $att_id);  
                            }                               
                            break;
                        case 'checkbox':
                            $field_imp = implode( ', ', $field );   // Need to test this
                            update_post_meta( $id, 'data' . $field_num, $field_imp );
                            break;
                        default:
                            update_post_meta( $id, 'data' . $field_num, $field );
                    }
                } elseif ( substr($field_num, 0, 6) == 'delete' ) { // It's a delete attchment request
                    $field_num = substr( $fid, strlen('jobman-field-delete-') );
                    wp_delete_attachment( $job_data_san['jobman-field-current-' . $field_num] );
                    update_post_meta( $id, 'data' . $field_num, '');
                    error_log ('jobman_updatedb DELETE!');
                } else {
                    error_log ('jobman_updatedb_edit(): Not sure of type for ' . $fid . 
                    ' I got this for field_num: ' . $field_num);
                }
        }
    }

	if( $options['plugins']['gxs'] )
		do_action( 'sm_rebuild' );
}

// Return an associative array with the job fields included in the request
function jobman_get_req_fields(){
    $job_data = array();
    $options = get_option( 'jobman_options' );
    $fields = $options['job_fields'];

    $job_data = jobman_add_if_exists( $job_data, 'jobman-jobid' );
    $job_data = jobman_add_if_exists( $job_data, 'jobman-title' );
    if( count( $fields ) > 0 ) {
        foreach( $fields as $fid => $field ) {
            if( $field['type'] == 'file' ){
                if (is_uploaded_file( $_FILES["jobman-field-$fid"]['tmp_name'] ))
                    $job_data['jobman-field-' . $fid] = "FILE_UPLOAD";
            } else {
                $job_data = jobman_add_if_exists( $job_data, 'jobman-field-' . $fid);
            }
            $job_data = jobman_add_if_exists( $job_data, 'jobman-field-delete-' . $fid);
            $job_data = jobman_add_if_exists( $job_data, 'jobman-field-current-' . $fid);
        }
    }
    $job_data = jobman_add_if_exists( $job_data, 'jobman-displaystartdate' );
    $job_data = jobman_add_if_exists( $job_data, 'jobman-displayenddate' );
    $job_data = jobman_add_if_exists( $job_data, 'jobman-icon' );
    $job_data = jobman_add_if_exists( $job_data, 'jobman-highlighted' );
    $job_data = jobman_add_if_exists( $job_data, 'jobman-categories' );

    error_log ( 'jobman_get_req_fields(): ' . var_export($job_data, true) );
    return $job_data;
}

// Sanitize request fields
function jobman_sanitize_req_fields($job_data){
    return $job_data;
}

// If item exists in the requesst, add it to the array and return
function jobman_add_if_exists( $job_data, $key ){
    if( array_key_exists( $key, $_REQUEST) )
        $job_data[$key] = $_REQUEST[$key];
    return $job_data;
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

// Handle requested file upload in new job or job edit
// Returns ID for attachment post to store in metadata
function jobman_job_field_upload ( $job_id, $field_index ){
    //$upload = wp_upload_bits( $_FILES["jobman-field-$fid"]['name'], NULL, file_get_contents( $_FILES["jobman-field-$fid"]['tmp_name'] ) );
    $file_index = 'jobman-field-' . $field_index;
    $overrides = array( 'action' => 'job_edit' );                   // Check for appropriate action for upload
    $upload = wp_handle_upload ( $_FILES[ $file_index ], $overrides );
    error_log('$upload -> ' . var_export($upload, true));
    if( ! array_key_exists ('error', $upload) ) {
    //                         // Delete the old attachment
    //                         if( array_key_exists( "jobman-field-current-$fid", $job_data_san ) )
    //                             wp_delete_attachment( $job_data_san["jobman-field-current-$fid"] );
        $filetype = wp_check_filetype( $upload['file'] );
        $attachment = array(
                        'guid' => $upload['url'],
                        'post_title' => '',
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_mime_type' => $filetype['type']
                    );
        $data = wp_insert_attachment( $attachment, $upload['file'], $job_id );
        $attach_data = wp_generate_attachment_metadata( $data, $upload['file'] );
        error_log('$attach_data -> ' . var_export($attach_data, true));
        wp_update_attachment_metadata( $data, $attach_data );
        return $data;
    } else {                                                        // Upload error
 //       $data = get_post_meta( $id, "data$fid", true );
        error_log( 'jobman_job_field_upload() error: ' . var_export($upload['error'], true));
    }

}

// Redirect helper: back to job list after a job edit
function jobman_jobedit_redirect() {
    $redirect_url = admin_url('admin.php?page=jobman-list-jobs');
    wp_safe_redirect( $redirect_url );
    exit;
}

?>