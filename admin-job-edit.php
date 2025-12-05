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

    $return_code = 1;                                               // Default
	if( array_key_exists( 'jobmansubmit', $_REQUEST ) ) {
	// Job form has been submitted. Update the database.
        $jobid = $_REQUEST['jobman-jobid'];
	    check_admin_referer( 'jobman-edit-job-' . $jobid );         // Confirm we're getting a valid call
        error_log ('Editing job #' . $jobid);
        if( $jobid == 'new' ){
            $return_code = jobman_updatedb_add();                   // Add a new job
        } else {
            $return_code = jobman_updatedb_edit();                  // Edit an existing job
        }
	}

	// Decide which alert box to display
	// Note these should be updated to use notice-error, notice-success and notice-info
	// https://developer.wordpress.org/reference/hooks/admin_notices/
	switch ($return_code) {
		case 0:
			$popup_class = 'notice-error';
			$popup_message = __( 'There is no job associated with that Job ID', 'jobman' );
			break;
		case 2:
			$popup_class = 'notice-success';
			$popup_message = __( 'New job created', 'jobman' ) ;
			break;
		case 3:
			$popup_class = 'notice-success';
			$popup_message = __( 'Job updated', 'jobman' );
			break;
		case 4:
			$popup_class = 'notice-error';
			$popup_message = __( 'You do not have permission to edit that Job', 'jobman' ) ;
			break;
	}

    if ( $return_code != 1){
        jobman_admin_notice_popup( $popup_class, $popup_message );
    }

    jobman_jobedit_redirect( $return_code );                        // Return when done processing.
}

// Code from jobman_updatedb responsible for addding a new job
function jobman_updatedb_add(){
    $return_code = 1;
    if( 'new' == $_REQUEST['jobman-jobid'] ) {
        $job_data_raw = jobman_get_req_fields();
        $job_data_san = jobman_sanitize_req_fields($job_data_raw);
        $id = wp_insert_post( jobman_build_new_post() );
        if ($id != 0)                                              // Post created okay
            $return_code = 2;

        $options = get_option( 'jobman_options' );

        // Process the sanitized submitted data
        jobman_updatedb_process( $id, $job_data_san );        
    
        if( $options['plugins']['gxs'] )
            do_action( 'sm_rebuild' );
    }
    return $return_code;
}

// Code from jobman_updatedb responsible for editing an existing job
// Update fields like "jobman-field-X"
// Which may include update or delete file attachment if included or requested
// Or they can be of type checkbox or text
// Then, remaining fields: jobman-displayenddate, jobman-icon, jobman-email,
// jobman-highlighted, jobman-categories
function jobman_updatedb_edit(){
    global $current_user;
    $return_code = 1;
    $data = get_post( $_REQUEST['jobman-jobid'] );

    // User is unable to edit this job
    if( ! current_user_can( 'edit_others_posts' ) && $data->post_author != $current_user->ID )
        return 4;

    $job_data_raw = jobman_get_req_fields();
    $job_data_san = jobman_sanitize_req_fields($job_data_raw);

    $page['ID'] = $job_data_san['jobman-jobid'];
    $id = wp_update_post( $page );                                  // Not sure this is needed for edit
    $options = get_option( 'jobman_options' );

    // Process the sanitized submitted data
    $return_code = jobman_updatedb_process( $id, $job_data_san );

	if( $options['plugins']['gxs'] )
		do_action( 'sm_rebuild' );

    return $return_code;
}

// Update fields for existing job... common to both editing and new
function jobman_updatedb_process( $jobid, $job_data_san ){
    $return_code = 1;
    $options = get_option( 'jobman_options' );
    $page['ID'] = $jobid;
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
                $page['post_date_gmt'] = get_gmt_from_date( $field );
                wp_update_post ( $page );
                break;
            case 'jobman-displayenddate':
                update_post_meta( $jobid, 'displayenddate', stripslashes( $field ) );
                break;
            case 'jobman-icon':
                update_post_meta( $jobid, 'iconid', $field );
                break;
            case 'jobman-highlighted':
                update_post_meta( $jobid, 'highlighted', $field );
                break;
            case 'jobman-categories':
                wp_set_object_terms( $jobid, $field, 'jobman_category', false );
                break;
            case 'jobman-email':
                update_post_meta( $jobid, 'email', $field );
                break;
            default:                                                // Editable ones like 'job-field-X'
                //error_log (var_export($options['job_fields']));
                $field_num = substr( $fid, strlen('jobman-field-') );
                if (is_numeric($field_num)) {
                    $field_type = $options['job_fields'][$field_num]['type'];
                    switch ( $field_type ){
                        case 'file':                                // Should be either delete or upload    
                            if ( $field == 'FILE_UPLOAD' ){
                                $att_id = jobman_job_field_upload( $jobid, $field_num );   
                                update_post_meta( $jobid, 'data' . $field_num, $att_id);  
                            }                               
                            break;
                        case 'checkbox':
                            $field_imp = implode( ', ', $field );   // Need to test and fix this (not working)
                            error_log('Processing Checkbox: ' . var_export($field_imp, true));
                            update_post_meta( $jobid, 'data' . $field_num, $field_imp );
                            break;
                        default:
                            update_post_meta( $jobid, 'data' . $field_num, $field );
                    }
                } elseif ( substr($field_num, 0, 6) == 'delete' ) { // It's a delete attchment request
                    $field_num = substr( $fid, strlen('jobman-field-delete-') );
                    wp_delete_attachment( $job_data_san['jobman-field-current-' . $field_num] );
                    update_post_meta( $jobid, 'data' . $field_num, '');
                    error_log ('jobman_updatedb DELETE!');
                } else {
                    error_log ('jobman_updatedb_edit(): Not sure of type for ' . $fid . 
                    ' I got this for field_num: ' . $field_num);
                }
        }
    }
    $return_code = 3;
    return $return_code;
}

// Return an associative array with the job fields included in the request
// This way we'll only process keys that we're expecting
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
    $job_data = jobman_add_if_exists( $job_data, 'jobman-email' );

    error_log ( 'jobman_get_req_fields(): ' . var_export($job_data, true) );
    return $job_data;
}

// If item exists in the request, add it to the array and return
function jobman_add_if_exists( $job_data, $key ){
    if( array_key_exists( $key, $_REQUEST) )
        $job_data[$key] = $_REQUEST[$key];
    return $job_data;
}

// Sanitize request fields
function jobman_sanitize_req_fields($job_data){
    $job_data_san = array();
    $options = get_option( 'jobman_options' );
    foreach( $job_data as $fid => $field ){
    // Named ones
    switch ($fid){
        case 'jobman-jobid':                                        // Should be "new" or numeric.
            if ( $job_data[$fid] == 'new' )
                $job_data_san[$fid] = 'new';
            elseif ( is_numeric($job_data[$fid]) )
                $job_data_san[$fid] = sanitize_key($job_data[$fid]);
            else   
                error_log ('jobman_santitize_req_fields: Sanitization Failed: ' . $job_data[$fid]);
            break;                                             
        case 'jobman-title':                                        // Should be text.
            $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);
            break;
        case 'jobman-displaystartdate':
            $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);
            break;
        case 'jobman-displayenddate':
            $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);
            break;
        case 'jobman-icon':
            $job_data_san[$fid] = sanitize_key($job_data[$fid]);    
            break;
        case 'jobman-highlighted':                                  // Checkbox should be 0 or 1
            if( ($job_data[$fid] == '0') or ($job_data[$fid] == '1') )
                $job_data_san[$fid] = $job_data[$fid];
            else
                error_log ('jobman_sanitize_req_fields: Sanitization Failed: '. $job_data[$fid]);
            break;
        case 'jobman-categories':                                   // Should be an array of categories
            if ( is_array($job_data[$fid]) ){
                $categories = array();
                foreach ($job_data[$fid] as $i => $categ){
                    if ( term_exists($categ) )
                        $categories[$i] = $categ;
                }
                $job_data_san[$fid] = $categories;              
            } else {
                error_log ('jobman_sanitize_req_fields: Sanitization Failed: '. $job_data[$fid]);
            }
            break;
        case 'jobman-email':
            $job_data_san[$fid] = sanitize_email($job_data[$fid]);
            break;
        default:                                                    // Editable ones like 'job-field-X'
            $field_num = substr( $fid, strlen('jobman-field-') );
            if (is_numeric($field_num)) {
                $field_type = $options['job_fields'][$field_num]['type'];
                switch ( $field_type ){                             // See admin-jobs-settings.php for types
                    case 'text':                        
                        $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);
                        break;
                    case 'radio':
                        $job_data_san[$fid] = $job_data[$fid];      // Need to do.
                        break;
                    case 'checkbox':
                        $job_data_san[$fid] = $job_data[$fid];      // Need to do.
                        break;
                    case 'textarea':
                        $job_data_san[$fid] = sanitize_textarea_field($job_data[$fid]);
                        break;
                    case 'date':
                        $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);      
                        break;
                    case 'file':
                        $job_data_san[$fid] = $job_data[$fid];      // Handled by jobman_job_field_upload
                        break;
                    case 'heading':
                        $job_data_san[$fid] = sanitize_text_field($job_data[$fid]);
                        break;
                    case 'html':
                        $job_data_san[$fid] = $job_data[$fid];      // Need to do.
                        break;
                    case 'blank':                                   // Is this really needed?
                        $job_data_san[$fid] = $job_data[$fid];      // Need to do.
                        break;
                    default:
                        $job_data_san[$fid] = $job_data[$fid];      // Need to do.
                }
            } elseif ( substr($field_num, 0, 6) == 'delete' ) {     // It's a delete attchment request
                $job_data_san[$fid] = $job_data[$fid];              // Need to do.
            } else {
                error_log ('jobman_sanitize_req_fields(): Not sure of type for ' . $fid . 
                ' I got this for field_num: ' . $field_num);
            }
        }
    }
    return $job_data_san;
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
    $file_index = 'jobman-field-' . $field_index;
    $overrides = array( 'action' => 'job_edit' );                   // Check for appropriate action for upload
    $upload = wp_handle_upload ( $_FILES[ $file_index ], $overrides );
    error_log('$upload -> ' . var_export($upload, true));
    if( ! array_key_exists ('error', $upload) ) {
        $filetype = wp_check_filetype( $upload['file'] );
        $attachment = array(
                        'guid' => sanitize_url($upload['url']),
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
        return '';
        error_log( 'jobman_job_field_upload() error: ' . var_export($upload['error'], true));
    }
}

// Redirect helper: back to job list after a job edit
function jobman_jobedit_redirect( $return_code ) {
    $redirect_url = admin_url('admin.php?page=jobman-list-jobs');
	$redirect_url = add_query_arg('_wp_http_referer', admin_url('admin-post.php'), $redirect_url);
	$redirect_url = add_query_arg('_wpnonce', $_REQUEST['_wpnonce'], $redirect_url);
	$redirect_url = add_query_arg('return-code', $return_code, $redirect_url);
    wp_safe_redirect( $redirect_url );
    exit;
}