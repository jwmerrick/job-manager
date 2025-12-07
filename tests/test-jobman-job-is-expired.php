<?php
/**
 * Unit test for jobman_job_is_expired() function
 *
 * Add a post with a status publish and and end date in the past
 * Then test that it returns true.  Change the date to future and check that
 * it returns false.
 * File: functions.php
 * Function or Class: jobman_post_status_setup()
 *
 * @category Testing
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */

function test_jobman_job_is_expired() {
    $test = new TestJobmanJobIsExpired();
    $test->run();
}

class TestJobmanJobIsExpired {

    private $post_id;
    private $exipred_success = false;
    private $not_expired_success = false;

    public function __construct() {
        
    }

    public function run() {
        $this->create_post();
        $this->test_expired();
        $this->test_not_expired();
        $this->log_result();
        $this->delete_post();
    }

    private function create_post(){

        // Create a post of the type 'jobman_job' with status 'jobman_archive'
        $test_post = array(
            'post_title'    => 'Test Expired Post',
            'post_content'  => 'This is the test expired post.',
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

    private function test_expired(){

        $this->exipred_success = jobman_job_is_expired( $this->post_id );

    }

    private function test_not_expired(){

        // Set expire 7 days in the future
        $end_date = date ( 'Y-m-d', strtotime('+7 days', time()));
        update_post_meta( $this->post_id, 'displayenddate', $end_date );

        $this->not_expired_success = !jobman_job_is_expired( $this->post_id );

    }

    private function log_result(){

        if ( $this->exipred_success && $this->not_expired_success ){
            error_log ('TestJobmanJobIsExpired: SUCCESS ' . var_export( $this->post_id, true ));
        } else {
            //there was an error in the post insertion, 
            error_log ('TestJobmanJobIsExpired: ERROR: ' . var_export( $this->post_id, true ));
        }

    }

    private function delete_post(){

        wp_delete_post ( $this->post_id, true );
        
    }

}

?>