<?php
function jobman_create_dashboard( $widths, $functions, $titles, $params = array() ) {
?>
<input type="hidden" id="wp_auto_columns" />
<div id="dashboard-widgets-wrap">
	<div id='dashboard-widgets' class='metabox-holder'>
<?php
	$ii = 0;
	foreach( $widths as $width ) {
?>
		<div id='postbox-container-<?php echo esc_attr( $ii + 1 ); ?>' class='postbox-container' style='width:<?php echo esc_attr( $width ) ?>'>
			<div id='normal-sortables' class='meta-box-sortables'>
<?php
		$jj = 0;
		foreach( $functions[$ii] as $function ) {
			if( array_key_exists( $ii, $params ) && array_key_exists( $jj, $params[$ii] ) )
				jobman_create_widget( $function, $titles[$ii][$jj], $params[$ii][$jj] );
			else
				jobman_create_widget( $function, $titles[$ii][$jj] );
			$jj++;
		}
?>
			</div>
		</div>
<?php
		$ii++;
	}
?>
	</div>
	<div class="clear"></div>
</div>
<?php
}

function jobman_create_widget( $function, $title, $params = array() ) {
?>
				<div id="jobman-<?php echo esc_attr( $function ) ?>" class="postbox jobman-postbox">
					<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle' ) ?>"><br /></div>
					<h3 class='hndle'><span><?php echo esc_html( $title ) ?></span></h3>
					<div class="inside">
<?php
	call_user_func_array( $function, $params );
?>
						<div class="clear"></div>
					</div>
				</div>
<?php
}

function jobman_print_rating_stars( $id, $rating, $callback = 'jobman_rate_application', $readonly = false ) {
	if( $readonly )
		$class = "star-holder-readonly";
	else
		$class = "star-holder";
?>
			        <div class="<?php echo esc_attr( $class ) ?>">
<?php
	if( ! $readonly ) {
?>
						<input type="hidden" id="jobman-rating-<?php echo esc_attr( $id ) ?>" name="jobman-rating" value="<?php echo esc_attr( $rating ) ?>" />
						<input type="hidden" name="callbackid" value="<?php echo esc_attr( $id ) ?>" />
						<input type="hidden" name="callbackfunction" value="<?php echo esc_attr( $callback ) ?>" />
						<a href="#" onclick="jobman_reset_rating('<?php echo esc_js( $id ) ?>', '<?php echo esc_js( $callback ) ?>'); return false;"><?php _e( 'No rating', 'jobman' ) ?></a>
<?php
	}
?>
						<div id="jobman-star-rating-<?php echo esc_attr( $id ) ?>" class="star-rating" style="width: <?php echo esc_attr( $rating * 19 ) ?>px"></div>
<?php
	for( $ii = 1; $ii <= 5; $ii++) {
?>
						<div class="star star<?php echo esc_attr( $ii ) ?>"><img src="<?php echo esc_url( JOBMAN_URL ) ?>/images/star.gif" alt="<?php echo esc_attr( $ii ) ?>" /></div>
<?php
	}
?>
					</div>
<?php
}

function jobman_load_translation_file() {
	load_plugin_textdomain( 'jobman', '', JOBMAN_FOLDER . '/translations' );
}

function jobman_page_taxonomy_setup() {
	// Create our new page types
	register_post_type( 'jobman_job', array( 'exclude_from_search' => false, 'public' => true, 'show_ui' => false, 'singular_name' => __( 'Job', 'jobman' ), 'label' => __( 'Jobs', 'jobman' ) ) );
	register_post_type( 'jobman_joblist', array( 'exclude_from_search' => true ) );
	register_post_type( 'jobman_app_form', array( 'exclude_from_search' => true ) );
	register_post_type( 'jobman_app', array( 'exclude_from_search' => true ) );
	register_post_type( 'jobman_register', array( 'exclude_from_search' => true ) );
	register_post_type( 'jobman_email', array( 'exclude_from_search' => true ) );
	register_post_type( 'jobman_interview', array( 'exclude_from_search' => true ) );

	// Create our new taxonomy thing
	$url = get_page_uri( jobman_get_root() );
	
	if( substr( $url, 0, 1 ) != '/' )
		$url = "/$url";
	
	register_taxonomy( 
		'jobman_category', 
		array( 'jobman_job', 'jobman_app' ), 
		array( 'hierarchical' => false, 
			'label' => __( 'Category', 'series' ), 
			'query_var' => 'jcat', 
			'rewrite' => array( 'slug' => $url )
		 )
	);
}

// Add custom post type 'jobman_archive' for jobs
// Bult in types, 'draft', 'future', and 'publish' will be used the same as core
// Previously 'draft' was used to indicate an archived job
function jobman_post_status_setup(){
	register_post_status( 'jobman_archive', array(
            'label'                     => __( 'Archived Job', 'jobman' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
	) );
}

function jobman_page_hierarchical_setup( $types ) {
	$types[] = 'jobman_job';
	$types[] = 'jobman_joblist';
	$types[] = 'jobman_app_form';
	$types[] = 'jobman_register';

	return $types;
}

function jobman_sort_fields( $a, $b ) {
	if($a['sortorder'] == $b['sortorder'])
		return 0;
	
	return ( $a['sortorder'] < $b['sortorder'] ) ? -1 : 1;
}

function jobman_current_url() {
		$pageURL = 'http';
		
		if( is_ssl() )
			$pageURL .= 's';
		
		$pageURL .= '://';
		
		if( '80' != $_SERVER['SERVER_PORT'] )
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		else
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		return $pageURL;
}

// Adds where clause to query to select for active jobs
function jobman_job_live_where( $where = '' ) {
	global $wpdb;
	$where .= " AND $wpdb->posts.post_date_gmt <= UTC_TIMESTAMP()";
	$where .= " AND jobman_postmeta.meta_key='displayenddate' ";
	$where .= " AND ( jobman_postmeta.meta_value='' OR jobman_postmeta.meta_value >= UTC_TIMESTAMP() ) ";
	error_log('jobmnan_job_live_where: ' . $where);
	return $where;
}

// Joins post metadataa so I can get the job display end date
function jobman_job_live_join( $join = '' ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->postmeta AS jobman_postmeta ON $wpdb->posts.ID = jobman_postmeta.post_id ";
	error_log('jobmnan_job_live_join: ' . $join);
	return $join;
}

function jobman_job_live_distinct( $distinct = '' ) {
	return 'distinct';
}

if( ! function_exists( 'array_insert' ) ) {
	function array_insert( $array, $pos, $val )	{
		$array2 = array_splice( $array, $pos );
		$array[] = $val;
		$array = array_merge( $array, $array2 );
	   
		return $array;
	}
}

// Retrieves and returns the ID for the root Job Manager landing page
function jobman_get_root(){
	$options = get_option( 'jobman_options' );
	return $options['main_page'];
}


// Retrieves and returns the ID for the job manager application page
// Returns 0 if page not found
function jobman_get_app(){
	$data = get_posts( 'post_type=jobman_app_form&numberposts=-1' );
	if( count($data) > 0 )
			$applypage = $data[0]->ID;
	else
			$applypage = 0;
	return $applypage;
}

// Retrieves the application child posts for the indicated job id
function jobman_get_job_apps( $id ){
	$args = array (
		'post_type' => 'jobman_app',
		'meta_key' => 'job',
		'meta_value' => $id,
		'post_status' => 'publish,private',
		'numberposts' => -1
	);
	$children = get_posts ( $args );
	return $children;
}

// Takes the id of the job and returns true if the job is active
// Returns false if it's inactive or couldn't be found
function jobman_job_is_active( $id ){
	$job_active = true;
	$job_post = get_post($id);
	if (is_object($job_post)){

		// Get the post metadata
		$jobmeta = get_post_custom( $id );
		$jobdata = array();	
		foreach( $jobmeta as $key => $value ) {
			if( is_array( $value ) )
				$jobdata[$key] = $value[0];
			else
				$jobdata[$key] = $value;
		}

		// Check if it's expired
		if( array_key_exists('displayenddate', $jobdata) ){
			$end_date = $jobdata['displayenddate'];
			if ( ($end_date != '') && (strtotime($end_date) <= time()) )
				$job_active = false;
		}	 

		// Check if it's in the future
		if( strtotime( $job_post->post_date ) > time() )
			$job_active = false;

		// Check if it's been archived
		if( $job_post->post_status == 'draft')
			$job_active = false;

	} else {
		$job_active = false;
	}
	return $job_active;
}

// Takes the id of the job and returns true if the job is future
// Returns false otherwise (include couldn't be found)
function jobman_job_is_future( $id ){
	$is_future = false;
	$job_post = get_post($id);
	if (is_object($job_post)){
		if( $job_post->post_status == 'future'){
			$is_future = true;
		}
	}
	return $is_future;
}

// Takes the id of the job and returns true if the job is draft
// Returns false otherwise (include couldn't be found)
function jobman_job_is_draft( $id ){
	$is_draft = false;
	$job_post = get_post($id);
	if (is_object($job_post)){
			if( $job_post->post_status == 'draft')
			$is_draft = true;
	}
	return $is_draft;
}

// Takes the id of the job and returns true if the job is archived
// Returns false otherwise (include couldn't be found)
function jobman_job_is_archived( $id ){
	$is_archived = false;
	$job_post = get_post($id);
	if (is_object($job_post)){
			if( $job_post->post_status == 'jobman_archive')
			$is_archived = false;
	}
	return $is_archived;
}

// Takes the id of the job and returns true if the job is expired
// Returns false otherwise (include couldn't be found)
// This means the job status is 'publish' but the display end date has passed
function jobman_job_is_expired( $id ){
	$is_expired = false;
	$job_post = get_post($id);
	if (is_object($job_post)){
		if( $job_post->post_status == 'publish'){
			// Get the post metadata
			$jobmeta = get_post_custom( $id );
			$jobdata = array();	
			foreach( $jobmeta as $key => $value ) {
				if( is_array( $value ) )
					$jobdata[$key] = $value[0];
				else
					$jobdata[$key] = $value;
			}

			// Check if it's expired
			if( array_key_exists('displayenddate', $jobdata) ){
				$end_date = $jobdata['displayenddate'];
				if ( ($end_date != '') && (strtotime($end_date) <= time()) )
					$is_expired = true;
			}
		}	
	}
	return $is_expired;
}

?>