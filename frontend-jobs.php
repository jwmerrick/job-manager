<?php // encoding: UTF-8
function jobman_display_jobs_list( $cat ) {
	global $jobman_shortcode_jobs, $jobman_shortcode_all_jobs, $jobman_shortcode_category, $jobman_shortcodes, $jobman_field_shortcodes, $wp_query;
	global $wpdb;
	$options = get_option( 'jobman_options' );

	$content = '';

	$root = jobman_get_root();
	$page = get_post( $root );

	if( 'all' != $cat ) {
		$page->post_type = 'jobman_joblist';
		$page->post_title = __( 'Jobs Listing', 'jobman' );
	}

	if( 'all' != $cat ) {
		$jobman_shortcode_category = $category = get_term( $cat, 'jobman_category' );
		if( NULL == $category ) {
			$cat = 'all';
		}
		else {			
			$page->post_title = $category->name;
			$page->post_parent = $root;
			$page->post_name = $category->slug;
		}
	}

	$args = array(
				'post_type' => 'jobman_job',
				'suppress_filters' => false,
			    'jobman_filter'    => true 							// <-- custom flag
			);

	if( ! empty( $options['sort_by'] ) ) {
		switch( $options['sort_by'] ) {
			case 'title':
				$args['orderby'] = 'title';
				break;
			case 'dateposted':
				$args['orderby'] = 'date';
				break;
			case 'closingdate':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = 'displayenddate';
				break;
			default:
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = $options['sort_by'];
				break;
		}
	}

	if( $options['jobs_per_page'] > 0 ) {
		$args['numberposts'] = $options['jobs_per_page'];
		$args['posts_per_page'] = $options['jobs_per_page'];

		if( array_key_exists( 'page', $wp_query->query_vars ) && $wp_query->query_vars['page'] > 1 )
			$args['offset'] = ( $wp_query->query_vars['page'] - 1 ) * $options['jobs_per_page'];
	}
	else {
		$args['numberposts'] = -1;
	}

	if( in_array( $options['sort_order'], array( 'asc', 'desc' ) ) )
		$args['order'] = $options['sort_order'];
	else
		$args['order'] = 'asc';

	if( 'all' != $cat )
		$args['jcat'] = $category->slug;

	add_filter( 'posts_where', 'jobman_job_live_where' );
	add_filter( 'posts_join', 'jobman_job_live_join' );
	add_filter( 'posts_distinct', 'jobman_job_live_distinct' );

	// Debugging
	add_filter('posts_request', function( $sql, \WP_Query $query ) {
		if ( $query->get('jobman_filter') ) {
			error_log( 'Final SQL (jobman main query): ' . $sql );
		}
		return $sql;
	}, 10, 2);

	$jobs = get_posts( $args );

	error_log ( 'jobman_display_jobs_list: ' . $wpdb->last_query );

	$args['posts_per_page'] = '';
	$args['offset'] = '';
	$args['numberposts'] = -1;
	$jobman_shortcode_all_jobs = get_posts( $args );

	remove_filter( 'posts_where', 'jobman_job_live_where' );
	remove_filter( 'posts_join', 'jobman_job_live_join' );
	remove_filter( 'posts_distinct', 'jobman_job_live_distinct' );

	if( $options['user_registration'] ) {
		if( 'all' == $cat && $options['loginform_main'] )
			$content .= jobman_display_login();
		else if( 'all' != $cat && $options['loginform_category'] )
			$content .= jobman_display_login();
	}

	$related_cats = array();
	foreach( $jobs as $id => $job ) {
		// Get related categories
		if( $options['related_categories'] ) {
			$categories = wp_get_object_terms( $job->ID, 'jobman_category' );
			if( count( $categories ) > 0 ) {
				foreach( $categories as $cat ) {
					$related_cats[] = $cat->slug;
				}
			}
		}
	}
	$related_cats = array_unique( $related_cats );

	if( $options['related_categories'] && count( $related_cats ) > 0 ) {
		$links = array();
		foreach( $related_cats as $rc ) {
			$cat = get_term_by( 'slug', $rc, 'jobman_category' );
			$links[] = '<a href="'. get_term_link( $cat->slug, 'jobman_category' ) . '" title="' . sprintf( __( 'Jobs for %s', 'jobman' ), $cat->name ) . '">' . $cat->name . '</a>';
		}

		$content .= '<h3>' . __( 'Related Categories', 'jobman' ) . '</h3>';
		$content .= implode(', ', $links) . '<br>';
	}

	// If we can find an application page, then wrap the whole job list in a form
	$applyform = false;
	$applypage = jobman_get_app();
	if( $applypage != 0 ) {
		$applyform = true;
		$url = get_page_link( $applypage );
		$content .= "<form action='$url' method='post'>";
	}

	if( count( $jobs ) > 0 ) {
		if( 'sticky' == $options['highlighted_behaviour'] )
			// Sort the sticky jobs to the top
			uasort( $jobs, 'jobman_sort_highlighted_jobs' );

		$template = $options['templates']['job_list'];
		$jobman_shortcode_jobs = $jobs;
		$content .= do_shortcode( $template );
	}
	else {
		$content .= '<p>';
		if( 'all' == $cat ||  ! isset( $category->term_id ) ) {
			$content .= jobman_nojobs_message();
		}
		else {
			$url = get_page_link( jobman_get_app() );
			$structure = get_option( 'permalink_structure' );
			if( '' == $structure ) {
				$url .= '&amp;c=' . $category->term_id;
			}
			else {
				if( substr( $url, -1 ) == '/' )
					$url .= $category->slug . '/';
				else
					$url .= '/' . $category->slug;
			}
			$content .= jobman_nojobs_message( $category-> slug );
		}
	}
	$content .= '</p>';

	if( $applyform )
		$content .= '</form>';

	$page->post_content = $content;

	return array( $page );
}

function jobman_sort_highlighted_jobs( $a, $b ) {
	$ahighlighted = get_post_meta( $a->ID, 'highlighted', true );
	$bhighlighted = get_post_meta( $b->ID, 'highlighted', true );

	if( $ahighlighted == $bhighlighted )
		return 0;

	if( 1 == $ahighlighted )
		return -1;

	return 1;
}

// Creates a page to return a single job post
// Or a message if the requested job is inactive
function jobman_display_job( $job ) {
	global $jobman_shortcode_job, $jobman_shortcodes, $jobman_field_shortcodes;
	$options = get_option( 'jobman_options' );

	$content = '';

	if( is_string( $job ) || is_int( $job ) )
		$job = get_post( $job );

	if( $options['user_registration'] && $options['loginform_job'] )
		$content .= jobman_display_login();

	if( !jobman_job_is_active($job->ID) ) {
		return jobman_page_inactive();
	}

	$template = $options['templates']['job'];
	$jobman_shortcode_job = $job;
	$content .= do_shortcode( $template );
	$page = $job;
	$page->post_title = $options['text']['job_title_prefix'] . $job->post_title;
	$page->post_content = $content;

	return array( $page );
}

// Creates a page to return with a message that the job is inactive
function jobman_page_inactive(){
	$page = get_post( jobman_get_root() );
	$page->post_type = 'jobman_job';
	$page->post_title = __( 'This job doesn\'t exist', 'jobman' );
	$message = 'Perhaps you followed an out-of-date link? Please check out the <a href="%s">jobs we have currently available</a>.';
	$message = __($message, 'jobman');
	$message = sprintf( $message, get_page_link( jobman_get_root() ));
	$content = '<p>' . $message . '</p>';
	$page->post_content = $content;
	return array( $page );
}

// Returns a string indicating no jobs available or no jobs
// in the requested category, as appropriate
function jobman_nojobs_message( $cat = 'all' ){
	if ($cat=='all'){
		$message = "We currently don't have any jobs available. ";
		$message .= "Please check back regularly, as we frequently post new jobs. ";
		$message .= "In the meantime, you can also <a href='%s'>send through your résumé</a>, which we'll keep on file.";
		$message = __($message, 'jobman');
		$message = sprintf($message, get_page_link( jobman_get_app() ));
	} else {
		$message = "We currently don't have any jobs available in this area. ";
		$message .= "Please check back regularly, as we frequently post new jobs. ";
		$message .= "In the mean time, you can also <a href='%s'>send through your résumé</a>, which we'll keep on file, ";
		$message .= "and you can check out the <a href='%s'>jobs we have available in other areas</a>.";
		$message = __($message, 'jobman');
		$message = sprintf($message, get_page_link( jobman_get_app() ), get_page_link( jobman_get_root() ));
	}
	return $message;
}

?>