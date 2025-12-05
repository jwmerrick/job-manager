<?php
// Refactoring admin-jobs to support fixing security issues.
// Need a new endpoint for jobs mass edit from the job list admin.
// Was jobman_list_jobs handlers
// JM 11/28/25

add_action( 'admin_post_jobman_mass_edit_jobs', 'jobman_admin_mass_edit_jobs' );

// Callback for admin-post.php when action "jmass_edit_jobs" is passed
// Requested should be Delete, Archive or Unarchive
function jobman_admin_mass_edit_jobs() {
    // Handle request then generate response using echo or leaving PHP and using HTML
    error_log ('Job mass edit handler triggered...');
    error_log ( var_export($_REQUEST, true) );

    if (array_key_exists('jobman-mass-edit-jobs', $_REQUEST)){
        $req_action = sanitize_text_field($_REQUEST['jobman-mass-edit-jobs']);
        switch ($req_action) {
            case 'delete':
                if( array_key_exists( 'jobman-delete-confirmed', $_REQUEST ) ) {
                    check_admin_referer( 'jobman-mass-delete-jobs' );
                    jobman_job_delete();
			    } else {											
                    check_admin_referer( 'jobman-mass-edit-jobs' );
					jobman_massedit_delconf_redirect();
			    }
                break;
            case 'archive':
                check_admin_referer( 'jobman-mass-edit-jobs' );
			    jobman_job_archive();
                break;
            case 'unarchive':
                check_admin_referer( 'jobman-mass-edit-jobs' );
			    jobman_job_unarchive();
                break;
            default:
                error_log( 'jobman_admin_mass_edit_jobs(): Unsure what to do with requested action ' . $req_action);
        }
    }

    jobman_massedit_redirect();                                      // Return when done processing.
}

function jobman_job_delete() {
	$options = get_option( 'jobman_options' );

	$jobs = explode( ',', $_REQUEST['jobman-job-ids'] );

	// Get the file fields
	$file_fields = array();
	foreach( $options['job_fields'] as $id => $field ) {
		if( 'file' == $field['type'] )
			$file_fields[] = $id;
	}

	foreach( $jobs as $job ) {
		// Remove reference from applications
		$apps = get_posts( 'post_type=jobman_app&numberposts=-1&meta_key=job&meta_value=' . $job );
		if( ! empty( $apps ) ) {
			foreach( $apps as $app ) {
				delete_post_meta( $app->ID, 'job', $job );
			}
		}

		$jobmeta = get_post_custom( $job );
		$jobdata = array();
		if( is_array( $jobmeta ) ) {
			foreach( $jobmeta as $key => $value ) {
				if( is_array( $value ) )
					$jobdata[$key] = $value[0];
				else
					$jobdata[$key] = $value;
			}
		}

		// Delete any files uploaded
		foreach( $file_fields as $fid ) {
			if( array_key_exists( "data$fid", $jobdata )  && '' != $jobdata["data$fid"] )
				wp_delete_post( $jobdata["data$fid"] );
		}
		// Delete the job
		wp_delete_post( $job );
	}

	jobman_admin_notice_popup ('notice-info', __('Jobs deleted', 'jobman'));

}

function jobman_job_archive() {
	$jobs = $_REQUEST['job'];

	if( ! is_array( $jobs ) )
		return;

	$data = array( 'post_status' => 'draft' );
	foreach( $jobs as $job ) {
		$data['ID'] = $job;
		wp_update_post( $data );
	}
}

function jobman_job_unarchive() {
	$jobs = $_REQUEST['job'];

	if( ! is_array( $jobs ) )
		return;

	$data = array( 'post_status' => 'publish' );
	foreach( $jobs as $job ) {
		$data['ID'] = $job;
		$data['post_date'] = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		$data['post_date_gmt'] = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		wp_update_post( $data );

		update_post_meta( $job, 'displayenddate', '' );
	}
}

// Redirect helper: delete confirm if delete requested but not yet confirmed
function jobman_massedit_delconf_redirect() {
	$redirect_url = add_query_arg(
		'jobman-mass-edit-jobs', 'delete',
		admin_url('admin.php?page=jobman-list-jobs'));
	$redirect_url = add_query_arg(
		'_wpnonce', $_REQUEST['_wpnonce'],
		$redirect_url);
	wp_safe_redirect( $redirect_url );
	$redirect_url = add_query_arg(
		'job', $_REQUEST['job'],
		$redirect_url);
	wp_safe_redirect( $redirect_url );
	exit;
}

// Redirect helper: back to job list after a mass edit
function jobman_massedit_redirect() {
    $redirect_url = admin_url('admin.php?page=jobman-list-jobs');
	$redirect_url = add_query_arg('_wp_http_referer', admin_url('admin-post.php'), $redirect_url);
	$redirect_url = add_query_arg('_wpnonce', $_REQUEST['_wpnonce'], $redirect_url);
	wp_safe_redirect( $redirect_url );
    exit;
}