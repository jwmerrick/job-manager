<?php
/**
 * Unit test for class JobmanJobStatus()
 *
 * Add a post with an end date in the past, run the status check in 
 * JobmanJobStatus::flag_expired_posts().  Confirm that the status has been
 * changed to 'jobman_expired'.  Then delete the test post.
 *
 * @category Testing
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */

function test_class_job_status() {
    $test = new TestClassJobStatus();
    $test->run();
}

class TestClassJobStatus {

    private $post_id;
    private $create_success;
    private $update_success;
    private $delete_success;

    public function __construct() {
        
    }

    public function run() {
        $this->create_test_post();
        $this->confirm_create_okay();
        $this->execute_status_update();
        $this->confirm_update_success();
        $this->delete_test_post();
        $this->log_result();
    }

    private function create_test_post(){

        // Create a post of the type 'jobman_job' with status 'jobman_archive'
        $test_post = array(
            'post_title'    => 'Test Class Job Status Post',
            'post_content'  => 'This is the test post.',
            'post_type'     => 'jobman_job',
            'post_status'   => 'publish',
            'post_author'   => 1
        );

        // Insert the post into the database.
        $this->post_id = wp_insert_post( $test_post );
        $currentTimestamp = time(); // Get the current Unix timestamp
        // Subtract 7 days from the current timestamp
        $sevenDaysAgoTimestamp = strtotime('-7 days', $currentTimestamp);
        $end_date = date ( 'Y-m-d', $sevenDaysAgoTimestamp );
        add_post_meta ( $this->post_id, 'displayenddate', $end_date );

    }

    private function confirm_create_okay(){

        if ( $this->post_id != 0 ){
            $this->create_success = true;
        } else {
            $this->create_success = false;
        }

    }

    private function execute_status_update(){

        JobmanJobStatus::flag_expired_posts();

    }

    private function confirm_update_success(){

        $job_status = get_post_status( $this->post_id );
        if ( $job_status == 'jobman_expired' ) {
            $this->update_success = true;
        } else {
            $this->update_success = false;
        }

    }

    private function delete_test_post(){

        $delete_result = wp_delete_post ( $this->post_id, true );
        if ( is_object ($delete_result) ){
            $this->delete_success = true;
        } else {
            $this->delete_success = false;
        }
        
    }

    private function log_result(){

        if ( !$this->create_success ){
            error_log ( 'TestClassJobStatus: CREATE FAILED' );
        } elseif ( !$this->update_success ){
            error_log ( 'TestClassJobStatus: UPDATE FAILED' );
        } elseif ( !$this->delete_success ){
            error_log ( 'TestClassJobStatus: DELETE FAILED' );
        } else {
            error_log ('TestClassJobStatus: SUCCESS ' . var_export( $this->post_id, true ));
        }
    }

}

?>