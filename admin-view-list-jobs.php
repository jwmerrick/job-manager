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