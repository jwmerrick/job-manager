<?php
/**
 * Unit test for jobman_page_taxonomy_setup() function
 *
 * Add a post of the type, echo that it was created, then delete it
 * File: functions.php
 * Function or Class: jobman_page_taxonomy_setup()
 *
 * @category Testing
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */

function test_jobman_page_taxonomy_setup() {
    $test = new TestJobmanPageTaxonomySetup();
    $test->run();
}

class TestJobmanPageTaxonomySetup {

    public function __construct() {
        
    }

    public function run() {

    }

    private function create_post(){


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


        
    }

}

?>