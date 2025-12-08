<?php
/**
 * A class for working with job statuses.
 *
 * @category Admin
 * @package job-manager
 * @author Jason Merrick
 * @copyright 2025 Jason Merrick
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 * @version 0.8.4
 * @since 0.8.4
 */

function jobman_update_post_statuses() {
    JobmanJobStatus::flag_expired_posts();
}

class JobmanJobStatus {

    private $jobs;

    public function __construct() {
        
    }

    // If a job is set as 'publish' but it's display end date has passed
    // Then change the status to 'jobman_expired'
    public static function flag_expired_posts(){
        $jobs = get_posts( 'post_type=jobman_job&numberposts=-1&post_status=publish' );
        foreach ( $jobs as $job ){
            self::update_expired( $job );
        }
    }

    // Check expiration date of a single job and updates it's status if it
    // should be flagged as expired
    private static function update_expired( $job ){
        $end_date = get_post_meta( $job->ID, 'displayenddate', true ); 
        if ( ( $end_date != '' ) && ( strtotime( $end_date ) <= time() ) ){
            // It's expired: set to jobman_expired
            // Create an array with the post data to update
            $args = array();
            $args['ID'] = $job->ID;
            $args['post_status'] = 'jobman_expired'; // Set the desired status

            // Update the post in the database
            wp_update_post( $args );
        }
    }
}

?>