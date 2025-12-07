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
            jobman_admin_joblist_render_single ( $job, $showexpired );
// 			$cats = wp_get_object_terms( $job->ID, 'jobman_category' );
// 			$cats_arr = array();
// 			if( count( $cats ) > 0 ) {
// 				foreach( $cats as $cat ) {
// 					$cats_arr[] = $cat->name;
// 				}
// 			}
// 			$catstring = implode( ', ', $cats_arr );

// 			$displayenddate = get_post_meta( $job->ID, 'displayenddate', true );

// 			$display = false;

// 			// Decide whether to display under "Active" jobs or "Expired" jobs in the job list
// 			// I like 'future' jobs to show as future in the top part of the list
// 			if ( ( $job->post_status == 'publish' ) || ( $job->post_status == 'future' ) ){
// 				if ( '' == $displayenddate || strtotime( $displayenddate ) > time() ){
// 					$display = true;
// 				}
// 			}

// 			if( ! ( $display || $showexpired ) ) {
// 				$expiredjobs[] = $job;
// 				continue;
// 			}

// 			$future = false;
// 			if( strtotime( $job->post_date ) > time() )
// 				$future = true;

// 			$children = get_posts( "post_type=jobman_app&meta_key=job&meta_value=$job->ID&post_status=publish,private&numberposts=-1" );
// 			if( count( $children ) > 0 )
// 				$applications = '<a href="' . admin_url( "admin.php?page=jobman-list-applications&amp;jobman-jobid=$job->ID" ) . '">' . count( $children ) . '</a>';
// 			else
// 				$applications = 0;

// 			$class = "live";
// 			if( $future )
// 				$class = "future";
// 			elseif( ! $display )
// 				$class = "expired";
// ?>
// 			<tr class="<?= $class ?>">
// 				<th scope="row" class="check-column">
// <?php
// 			if( current_user_can( 'edit_others_posts' ) || $job->post_author == $current_user->ID ) {
// ?>
// 				<input type="checkbox" name="job[]" value="<?php echo $job->ID ?>" />
// <?php
// 			}
// ?>
// 				</th>
// 				<td class="post-title page-title column-title">
// 					<strong>
// 						<a href="?page=jobman-list-jobs&amp;jobman-jobid=<?php echo $job->ID ?>">
// 							<?php echo $job->post_title ?>
// 						</a>
// 					</strong>
// 				<div class="row-actions">
// <?php
// 			if( current_user_can( 'edit_others_posts' ) || $job->post_author == $current_user->ID ) {
// ?>
// 				<a href="?page=jobman-list-jobs&amp;jobman-jobid=<?php echo $job->ID ?>"><?php _e( 'Edit', 'jobman' ) ?></a> |
// <?php
// 			}
// ?>
// 				<a href="<?php echo get_page_link( $job->ID ) ?>"><?php _e( 'View', 'jobman' ) ?></a>
// <?php
// 			if( current_user_can( 'edit_others_posts' ) || $job->post_author == $current_user->ID ) {
// 				$url = wp_nonce_url( admin_url('admin-post.php'), 'jobman-mass-edit-jobs' );
// 				$url = add_query_arg('action', 'jobman_mass_edit_jobs', $url);
// 				$url = add_query_arg('job[]', $job->ID, $url);
// 				if( $display ) {
// 					$url = add_query_arg('jobman-mass-edit-jobs', 'archive', $url);
// ?>
// 				| <a href="<?= $url; ?>"><?php _e( 'Archive', 'jobman' ) ?></a>
// <?php
// 				}
// 				else {
// 					$url = add_query_arg('jobman-mass-edit-jobs', 'unarchive', $url);
// ?>
// 				| <a href="<?= $url; ?>"><?php _e( 'Unarchive', 'jobman' ) ?></a>
// <?php
// 				}
// 			}
// ?>
// 				</div></td>
// 				<td><?= $catstring ?></td>
// <?php
// 			if( count( $fields ) ) {
// 				foreach( $fields as $id => $field ) {
// 					if( array_key_exists( 'listdisplay', $field ) && $field['listdisplay'] ) {
// 						$data = get_post_meta( $job->ID, "data$id", true );
// 						if( ! empty( $data ) ) {
// 							if( 'file' == $field['type'] )
// 								$data = '<a href="' . wp_get_attachment_url( $data ) . '">' . __( 'Download', 'jobman' ) . '</a>';
// 							else if( is_array( $data ) )
// 								$data = implode( ', ', $data );
// 						}
// ?>
// 				<td><?= $data ?></td>
// <?php
// 					}
// 				}
// 			}
// 			$status = __( 'Live', 'jobman' );
// 			if( $future )
// 				$status = __( 'Future', 'jobman' );
// 			else if( ! $display )
// 				$status = __( 'Expired', 'jobman' );
// ?>
// 				<td>
// 					<?php echo date( 'Y-m-d', strtotime( $job->post_date ) ) ?> - 
// 					<?php echo ( '' == $displayenddate )?( __( 'End of Time', 'jobman' ) ):( $displayenddate ) ?>
// 					<br/>
// 				<?= $status ?></td>
// 				<td><?= $applications ?></td>
// 			</tr>
 <?php
		}
		return $expiredjobs;
}

// Renders the html for a a single job to be displayed in the job list
function jobman_admin_joblist_render_single( $job, $showexpired ){
    $cats = wp_get_object_terms( $job->ID, 'jobman_category' );
    $cats_arr = array();
    if( count( $cats ) > 0 ) {
        foreach( $cats as $cat ) {
            $cats_arr[] = $cat->name;
        }
    }
    $catstring = implode( ', ', $cats_arr );

    $displayenddate = get_post_meta( $job->ID, 'displayenddate', true );

    $display = false;

    // Decide whether to display under "Active" jobs or "Expired" jobs in the job list
    // I like 'future' jobs to show as future in the top part of the list
    if ( ( $job->post_status == 'publish' ) || ( $job->post_status == 'future' ) ){
        if ( '' == $displayenddate || strtotime( $displayenddate ) > time() ){
            $display = true;
        }
    }



}

?>