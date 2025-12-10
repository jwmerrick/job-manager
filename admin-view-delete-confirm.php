<?php
/**
 * Admin job delete confirmation page
 *
 * When the mass-edit option to delete jobs is selected, it redirects here
 * to add a confirmation key to the query before the request is sent-on to
 * admin-jobs-massedit.php for the actual delte operation
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
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
        <input type="hidden" name="action" value="jobman_mass_edit_jobs"> 
        <input type="hidden" name="jobman-delete-confirmed" value="1" />
        <input type="hidden" name="jobman-mass-edit-jobs" value="delete" />
        <input type="hidden" name="jobman-job-ids" value="<?php echo implode( ',', $_REQUEST['job'] ) ?>" />
        <?php wp_nonce_field( 'jobman-mass-delete-jobs' ); ?>
        <h2><?php _e( 'Job Manager: Jobs', 'jobman' ) ?></h2>
        <p class="error"><?php _e( 'This will permanently delete all of the selected jobs. Please confirm that you want to continue.', 'jobman' ) ?></p>
        <p class="submit"><input type="submit" name="submit"  class="button-primary" value="<?php _e( 'Delete Jobs', 'jobman' ) ?>" /></p>
    </form>
</div>