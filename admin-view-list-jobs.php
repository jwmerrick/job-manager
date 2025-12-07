<?php
/**
 * Admin Job List View
 *
 * This is the HTML for the Job List View refactored from original
 * admin-jobs.php
 *
 * @category Admin
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */
?>

<div class="wrap">
    <h2><?php _e( 'Job Manager: Jobs List', 'jobman' ) ?></h2>
    <form action="" method="post">
        <input type="hidden" name="jobman-jobid" value="new" />
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php _e( 'New Job', 'jobman' ) ?>" />
        </p>
    </form>
    <?php $jobs = get_posts( 'post_type=jobman_job&numberposts=-1&post_status=publish,draft,future' );  ?>
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
        <input type="hidden" name="action" value="jobman_mass_edit_jobs"> 
        <?php wp_nonce_field( 'jobman-mass-edit-jobs' ); ?>
    <table id="jobman-jobs-list" class="widefat page fixed" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" id="cb" class="column-cb check-column"><input type="checkbox"></th>
                <th scope="col"><?php _e( 'Title', 'jobman' ) ?></th>
                <th scope="col"><?php _e( 'Categories', 'jobman' ) ?></th>
                <?php
                $fieldcount = 0;
                if( count( $fields ) > 0 ) {
                    foreach( $fields as $field ) {
                        if( array_key_exists( 'listdisplay', $field ) && $field['listdisplay'] ) {
                        $fieldcount++;
                ?>
                <th scope="col"><?php echo $field['label'] ?></th>
                <?php } } } ?>
                <th scope="col"><?php _e( 'Display Dates', 'jobman' ) ?></th>
                <th scope="col"><?php _e( 'Applications', 'jobman' ) ?></th>
            </tr>
        </thead>
        <?php
        if( count( $jobs ) > 0 ) {
            $expired = jobman_list_jobs_data( $jobs, false );
            if( count( $expired ) ) {
        ?>
        <tr class="jobman-expired-jobs">
            <td colspan="<?php echo $fieldcount + 5 ?>">
                <?php _e( 'Expired and Archived Jobs', 'jobman' ) ?>
            </td>
        </tr>
        <?php } jobman_list_jobs_data( $expired, true ); } else { $fieldcount += 5; ?>
        <tr>
            <td colspan="<?php echo $fieldcount ?>"><?php _e( 'There are currently no jobs in the system.', 'jobman' ) ?></td>
        </tr>
        <?php } ?>
    </table>
    <div class="alignleft actions">
        <select name="jobman-mass-edit-jobs">
            <option value=""><?php _e( 'Bulk Actions', 'jobman' ) ?></option>
            <option value="delete"><?php _e( 'Delete', 'jobman' ) ?></option>
            <option value="archive"><?php _e( 'Archive', 'jobman' ) ?></option>
            <option value="unarchive"><?php _e( 'Unarchive', 'jobman' ) ?></option>
        </select>
        <input type="submit" value="<?php _e( 'Apply', 'jobman' ) ?>" name="submit" class="button-secondary action" />
    </div>
    </form>
</div>

<?php
// Generates the html for the job list, and return a list of expired jobs
// Can be called back with that same list of expired jobs and $showexpired
// set to true in order to generate the html for the list of expired jobs.
function jobman_list_jobs_data( $jobs, $showexpired = false ) {
		global $current_user;

		if( ! is_array( $jobs ) || count( $jobs ) <= 0 )
			return;

		$options = get_option( 'jobman_options' );
		$fields = $options['job_fields'];

		wp_get_current_user();

		$expiredjobs = array();
		foreach( $jobs as $job ) {
            if ( jobman_job_is_expired( $job->ID ) || jobman_job_is_archived ( $job->ID ) ){
                $expiredjobs[] = $job;
            }
            jobman_admin_joblist_render_single ( $job, $showexpired );
        }

		return $expiredjobs;
}

// Renders the html for a a single job to be displayed in the job list
function jobman_admin_joblist_render_single( $job, $showexpired ){
    $id = $job->ID;
	$options = get_option( 'jobman_options' );
    $cats = wp_get_object_terms( $id, 'jobman_category' );
    $cats_arr = array();
    if( count( $cats ) > 0 ) {
        foreach( $cats as $cat ) {
            $cats_arr[] = $cat->name;
        }
    }
    $catstring = implode( ', ', $cats_arr );

    $displayenddate = get_post_meta( $id, 'displayenddate', true );
    if ( $displayenddate == '' ){
        $displayenddate = __( 'End of Time', 'jobman' );
    }

    // Decide whether to display under "Active" jobs or "Expired" jobs in the job list
    // I like 'future' jobs to show as future in the top part of the list
    $display = false;
    if ( !$showexpired ){
        if ( jobman_job_is_draft($id) || jobman_job_is_future($id) || jobman_job_is_active($id) ){
            $display = true;
        }
    } else {
        if ( jobman_job_is_expired($id) || jobman_job_is_archived($id) ){
            $display = true;
        }
    }

    // If it's an active job but show_expired is set or vice-versa, do no more
    if ( !$display ){
        return;
    }

    $num_apps = 0;
    // Figure out if there are applications available and, if so, include a link
    if ( $num_apps = count( jobman_get_job_apps($id) ) ){
        $apps_link = admin_url( 'admin.php?page=jobman-list-applications&amp;jobman-jobid=' . $id );
    }

    $class = 'live';
    if ( jobman_job_is_draft($id) || jobman_job_is_future($id) ){
        $class = 'future';
    } elseif ( jobman_job_is_expired($id) || jobman_job_is_archived($id) ){
        $class = 'expired';
    }

    $can_edit = false;
    if ( current_user_can( 'edit_others_posts' ) ){
        $can_edit = true;
    } elseif ( get_post($id)->post_author == get_current_user_id() ){
        $can_edit = true;
    }

    $edit_link = admin_url( 'admin.php?page=jobman-list-jobs&amp;jobman-jobid=' . $id );

    $view_link = get_page_link( $id );

    $massedit_link = wp_nonce_url( admin_url('admin-post.php'), 'jobman-mass-edit-jobs' );
    $massedit_link = add_query_arg('action', 'jobman_mass_edit_jobs', $massedit_link);
    $massedit_link = add_query_arg('job[]', $id, $massedit_link);

	$archive_link = add_query_arg('jobman-mass-edit-jobs', 'archive', $massedit_link);

    $unarchive_link = add_query_arg('jobman-mass-edit-jobs', 'unarchive', $massedit_link);

    // Items to show above display dates in list
    $data = '';
    $fields = $options['job_fields'];
	if( count( $fields ) ) {
		foreach( $fields as $key => $field ) {
			if( array_key_exists( 'listdisplay', $field ) && $field['listdisplay'] ) {
				$data = get_post_meta( $job->ID, "data$key", true );
                if( ! empty( $data ) ) {
                    if( 'file' == $field['type'] ){
                        $data = '<a href="' . wp_get_attachment_url( $data ) . '">' . __( 'Download', 'jobman' ) . '</a>';
                    } elseif( is_array( $data ) ){
                        $data = implode( ', ', $data );
                    }
                }
            }            
        }
    }

    $status = __( 'Live', 'jobman' );
    if( jobman_job_is_draft($id) ){
        $status = __( 'Draft', 'jobman' );
    } elseif( jobman_job_is_future($id) ){
        $status = __( 'Future', 'jobman' );
    } elseif( jobman_job_is_expired($id) ){
    	$status = __( 'Expired', 'jobman' );
    } elseif( jobman_job_is_archived($id) ){
        $status = __( 'Archived', 'jobman' );
    }

?>

<tr class="<?= $class ?>">
    <th scope="row" class="check-column">
        <?php if ( $can_edit ) { ?>
            <input type="checkbox" name="job[]" value="<?php echo $job->ID ?>" />
        <?php } ?>
    </th>
    <td class="post-title page-title column-title">
		<strong>
			<a href="?page=jobman-list-jobs&amp;jobman-jobid=<?php echo $job->ID ?>">
				<?php echo $job->post_title ?>
			</a>
		</strong>
		<div class="row-actions">
            <?php if ( $can_edit ){ ?>
			    <a href="<?= $edit_link ?>"><?php _e( 'Edit', 'jobman' ) ?></a> |
            <?php } ?>
			<a href="<?= $view_link ?>"><?php _e( 'View', 'jobman' ) ?></a>
            <?php if ( $can_edit && $display ){ ?>
			    | <a href="<?= $archive_link ?>"><?php _e( 'Archive', 'jobman' ) ?></a>
            <?php } ?>
            <?php if ( $can_edit && !$display ){ ?>
			    | <a href="<?= $unarchive_link ?>"><?php _e( 'Unarchive', 'jobman' ) ?></a>
            <?php } ?>
        </div>
    </td>
 	<td>
        <?= $catstring ?>
    </td>
    <td>
        <?= $data ?>
 		<?php echo date( 'Y-m-d', strtotime( $job->post_date ) ) ?> - 
 		<?= $displayenddate ?>
		<br/>
		<?= $status ?>
    </td>
	<td>
        <?php count ( jobman_get_job_apps( $id ) ); ?>
    </td>
</tr>

<?php } ?>