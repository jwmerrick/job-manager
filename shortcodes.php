<?php
// Adding support for shortcodes in normal pages and posts JM 10/26/21

function jobmansn_list_jobs(){
	global $wp_filter, $wp_current_filter, $wp_query, $wpdb;
	$options = get_option( 'jobman_options' );
	//var_dump ($options);
	$fields = $options['job_fields'];													// Job fields to iterate over for each job
	//var_dump ($fields);
	$content = '';
	
	// Get the list of jobs to dipslay.
	$args = array('post_type' => 'jobman_job', 'suppress_filters' => FALSE );		// Query arguments
	$args['numberposts'] = -1;
	
	add_filter('posts_where', 'jobman_job_live_where', 10, 1);
	add_filter('posts_join', 'jobman_job_live_join', 10, 1);
	add_filter('posts_distinct', 'jobman_job_live_distinct', 10, 1);							// Grab list of jobs
	
	$get_jobs = new WP_Query;
	$jobs = $get_jobs->query($args);	
	//var_dump($get_jobs->request);
	
	remove_filter('posts_where', 'jobman_job_live_where', 10, 1);
	remove_filter('posts_join', 'jobman_job_live_join', 10, 1);
	remove_filter('posts_distinct', 'jobman_job_live_distinct', 10, 1);
	
	// Display the job list, or a nice message.
	$content .= '<h1>Current Openings</h1>';
	if ( count ($jobs) > 0){
		foreach ($jobs as $job){
			$content .= jobmansn_job_entry( $job, $fields );
		}
	} else {
		$content .= '<p>We don\'t currently have any jobs available.  Please check back regularly, ';
		$content .= 'as we frequently post new jobs.  In the meantime, you can also <a href="';
		$content .= jobmansn_get_app_url() . '">send through your resume</a>, which we\'ll keep on file.</p>';
	}
	
	return $content;
	
}

// Takes a job post entry and returns the div to be displayed in the job list
function jobmansn_job_entry( $job, $fields ){
	//var_dump ($job);
	$jobdata = get_post_meta( $job->ID );												// Get job metadata
	//var_dump ($jobdata);
	$desired_fields = ['Salary', 'Start Date', 'Location'];
	$content = '<div><table class="job-table">';										// Job title
	$content .= '<tr><th scope="row">Title</th>';
	$content .= '<td><a href = "' . get_page_link($job) . '">';
	$content .= $job->post_title . '</a></td></tr>';
	foreach ($fields as $fid => $field){												// Fields
		$fielddata = $jobdata['data' . $fid][0];
		if (in_array($field['label'], $desired_fields) && $fielddata != ''){			// If it's one of the ones we want to show on the list, and not empty
			$content .= '<tr><th scope="row">' . $field['label'];
			$content .= '<td>' . $fielddata;
			$content .= '</td>';
			$content .= '</th></tr>';
		}
	}
	$content .= '<tr><td></td>';
	$content .= '<td class="jobs-applynow"><a href="' . jobmansn_apply_url($job) . '">';
	$content .= 'Apply Now</a></td></tr>';												// Apply now
	$content .= '</table><br><br></div>';
	return $content;
}


// Take a job post entry and returns the URL for the application page
function jobmansn_apply_url( $job ){
	$url = jobmansn_get_app_url() . $job->ID . '/';
	return $url;
}

// Retrun application form URL
function jobmansn_get_app_url(){
	$args['post_type'] = 'jobman_app_form';
	$args['numberposts'] = 1;															// There should be only one, but make sure
	$appform = get_posts( $args )[0];
	$url = get_page_link($appform);
	return $url;
}

// Return debug info to the [jobmansn_debug] shortcode
function jobmansn_debug(){
	global $wp_rewrite;
	$content = '';
	$options = get_option( 'jobman_options' );
	
	$content .= '<h3>Job Manager Options Dump</h3>';
	foreach ($options as $key => $value){
		$content .= '<p><b>' . var_export($key, TRUE) . '</b>    ' . var_export($value, TRUE) . '</p>';
	}
	
	foreach ($options['rewrite_rules'] as $key => $rule){
		$content .= '<h3>Job Manager Rewrite Rule:</h3>';
		$content .= '<p><b>' . var_export($key, TRUE) . '</b>    ' . var_export($rule, TRUE) . '</p>';
	}
		
	foreach ($wp_rewrite->rules as $key => $rule){
		$content .= '<h3>Site Rewrite Rule:</h3>';
		$content .= '<p><b>' . var_export($key, TRUE) . '</b>    ' . var_export($rule, TRUE) . '</p>';
	}
	return $content;
}


?>