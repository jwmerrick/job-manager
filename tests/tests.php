<?php
/**
 * Unit tests
 *
 * If JOBMAN_TESTING exists and is set to true, link in the
 * unit tests.  Create a file for each test function, please
 *
 * @category Testing
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */

// Require once the tests you want to include
// Then register them in jobman_tests_add to run on admin_init
require_once ( JOBMAN_DIR . "/tests/test-jobman-post-status-setup.php");
require_once ( JOBMAN_DIR . "/tests/test-jobman-job-is-expired.php");
require_once ( JOBMAN_DIR . "/tests/test-class-job-status.php");

if ( defined ( 'JOBMAN_TESTING' ) ) {
    if ( JOBMAN_TESTING ) {
        add_action ( 'admin_init', 'jobman_tests_run' );
    }
}

function jobman_tests_run(){
    error_log('------JOB-MANAGER-UNIT-TESTS-ENABLED------');
    test_jobman_post_status_setup();
    test_jobman_job_is_expired();
    test_class_job_status();
}

?>