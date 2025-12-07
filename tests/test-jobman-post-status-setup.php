<?php
/**
 * Unit test for jobman_post_status_setup() function
 *
 * Add a post of the type, echo that it was created, then delete it
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

function test_jobman_post_status_setup() {
    $test = new TestJobmanPostStatusSetup();
    $test->run();
}

class TestJobmanPostStatusSetup {

    private $post_id;
    private $delete_result;

    public function __construct() {
        
    }

    public function run() {
        $this->create_post();
        $this->log_result();
        $this->delete_post();
    }

    private function create_post(){

        // Create a post of the type 'jobman_job' with status 'jobman_archive'
        $test_post = array(
            'post_title'    => 'Test Archived Post',
            'post_content'  => 'This is the test archived post.',
            'post_type'     => 'jobman_job',
            'post_status'   => 'jobman_archive',
            'post_author'   => 1
        );

        // Insert the post into the database.
        $this->post_id = wp_insert_post( $test_post );

    }

    private function log_result(){

        if ( !is_wp_error($this->post_id) ){
            error_log ('TestJobmanPostStatusSetup: SUCCESS ' . var_export( $this->post_id, true ));
        } else {
            //there was an error in the post insertion, 
            error_log ('TestJobmanPostStatusSetup: ERROR: ' . $this->post_id->get_error_message());
        }

    }

    private function delete_post(){

        $this->delete_result = wp_delete_post ( $this->post_id, true );
        
    }

}

?>