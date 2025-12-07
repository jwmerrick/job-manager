<?php
function jobman_list_jobs() {
	$options = get_option( 'jobman_options' );
	$fields = $options['job_fields'];

	$return_code = 1;													// Key to banner-message
	error_log ( var_export($_REQUEST, true) );

	// Figure out which page to display and display it.
	// Actual request handling in admin-job-edit.php and admin-jobs-massedit.php
	if( array_key_exists( 'jobman-mass-edit-jobs', $_REQUEST ) ) {
		if( 'delete' == $_REQUEST['jobman-mass-edit-jobs'] ) {
			if( ! array_key_exists( 'jobman-delete-confirmed', $_REQUEST ) ) {
				check_admin_referer( 'jobman-mass-edit-jobs' );
				jobman_job_delete_confirm();
				return;
			}
		}
	} elseif ( isset( $_REQUEST['jobman-jobid'] ) ) {
		$return_code = jobman_edit_job( $_REQUEST['jobman-jobid'] );
		if( 1 == $return_code )
			return;
	}

	// Handle returned values from admin-job-edit.php or admin-jobs-massedit.php
	$referrer_url = wp_get_referer();
	$referrer_url_naked = remove_query_arg( null, $referrer_url );
	if ($referrer_url_naked == admin_url('admin-post.php')){
		if( array_key_exists('return-code', $_REQUEST ) ){
			$return_code = (int)$_REQUEST['return-code'];
			if( ($return_code >= 0) && ($return_code <= 4) ){
				$return_code = $return_code;
				error_log ('jobman_list_jobs(): Got return code ' . $return_code);
			}
		}
	}

	// If the request is not for a mass delete (which triggers the confirm dispaly)
	// or to edit an individual job (which triggerd the job edit form), then we fall
	// through to display the job list.

	include ( 'admin-view-list-jobs.php' );

}

function jobman_add_job(){
	jobman_edit_job( 'new' );
}

function jobman_edit_job( $jobid ) {
	global $wp_version;
	$options = get_option( 'jobman_options' );

	if( array_key_exists( 'jobmansubmit', $_REQUEST ) ) {
		// Job form has been submitted. Update the database.
		error_log( 'Job Manager: Call to admin.php with jobmansubmit set.  Please update to use admin-post.php.');
		check_admin_referer( 'jobman-edit-job-$jobid' );
	}

	if( 'new' == $jobid ) {
		$title = __( 'Job Manager: New Job', 'jobman' );
		$submit = __( 'Create Job', 'jobman' );
		$job = array();
		$display_jobid = __( 'New', 'jobman' );
	}
	else {
		$title = __( 'Job Manager: Edit Job', 'jobman' );
		$submit = __( 'Update Job', 'jobman' );

		$job = get_post( $jobid );
		if( NULL == $job )
			// No job associated with that id.
			return 0;

		$display_jobid = $jobid;
	}

	if( isset( $job->ID ) ) {
		$jobid = $job->ID;
		$jobmeta = get_post_custom( $job->ID );
		$jobcats = wp_get_object_terms( $job->ID, 'jobman_category' );
	}
	else {
		$jobmeta = array();
		$jobcats = array();
	}

	$icons = $options['icons'];

	$jobdata = array();
	foreach( $jobmeta as $key => $value ) {
		if( is_array( $value ) )
			$jobdata[$key] = $value[0];
		else
			$jobdata[$key] = $value;
	}

	if( user_can_richedit() && version_compare( $wp_version, '3.3-aortic-dissection', '<' ) ) {
			wp_tiny_mce( false, array( 'editor_selector' => 'jobman-editor' ) );
	}
?>
	<form action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" method="post">
	<input type="hidden" name="action" value="job_edit"> 
	<input type="hidden" name="jobmansubmit" value="1" />
	<input type="hidden" name="jobman-jobid" value="<?= $jobid ?>" />
	<?php wp_nonce_field( "jobman-edit-job-$jobid"); ?>
	<div class="wrap">
		<h2><?= $title ?></h2>
		<table id="jobman-job-edit" class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Job ID', 'jobman' ) ?></th>
				<td><?= $display_jobid ?></td>
				<td></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Categories', 'jobman' ) ?></th>
				<td><div class="jobman-categories-list">
<?php
	$categories = get_terms( 'jobman_category', 'hide_empty=0' );
	if( count( $categories ) > 0 ) {
		foreach( $categories as $cat ) {
			$checked = '';
			if( 'new' != $jobid ) {
				foreach( $jobcats as $jobcat ) {
					if( $cat->term_id == $jobcat->term_id ) {
						$checked = ' checked="checked"';
						break;
					}
				}
			}
?>
					<!-- Hidden control to send none when all unchecked -->
					<input type="hidden" name="jobman-categories[]" value="" />
					<input type="checkbox" name="jobman-categories[]" value="<?php echo $cat->slug ?>"<?= $checked ?> /> <?php echo $cat->name ?><br/>
<?php
		}
	}
?>
				</div></td>
				<td>
					<span class="description">
						<?php _e( 'Categories that this job belongs to. It will be displayed in the job list for each category selected.', 'jobman' ) ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Icon', 'jobman' ) ?></th>
				<td><div class="jobman-icons-list">
<?php
	if( count( $icons ) > 0 ) {
		foreach( $icons as $icon ) {
			if( isset( $jobdata['iconid'] ) && $icon == $jobdata['iconid'] )
				$checked = ' checked="checked"';
			else
				$checked = '';

			$post = get_post( $icon );
?>
					<input type="radio" name="jobman-icon" value="<?= $icon ?>"<?= $checked ?> /> 
					<img src="<?php echo wp_get_attachment_url( $icon ) ?>" /> <?php echo $post->post_title ?><br/>
<?php
		}
	}

	if( ! isset( $jobdata['iconid'] ) || 0 == $jobdata['iconid'] )
		$checked = ' checked="checked"';
	else
		$checked = '';
?>
					<input type="radio" name="jobman-icon"<?= $checked ?> value="" /> <?php _e( 'No Icon', 'jobman' ) ?><br/>
				</div></td>
				<td><span class="description"><?php _e( 'Icon to display for this job in the Job List', 'jobman' ) ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Title', 'jobman' ) ?></th>
				<td>
					<input class="regular-text code" type="text" name="jobman-title" 
					value="<?php echo ( isset( $job->post_title ) )?( $job->post_title ):( '' ) ?>" />
				</td>
				<td></td>
			</tr>
<?php
	$fields = $options['job_fields'];
	$content = '';
	if( count( $fields ) > 0 ) {
		uasort( $fields, 'jobman_sort_fields' );
		foreach( $fields as $id => $field ) {
			if( 'new' == $jobid )
				$data = $field['data'];
			else if( array_key_exists( "data$id", $jobdata ) )
				$data = $jobdata["data$id"];
			else
				$data = '';

			if( 'heading' != $field['type'] )
				echo '<tr>';

			if( ! array_key_exists( 'description', $field ) )
				$field['description'] = '';

			switch( $field['type'] ) {
				case 'text':
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th>";
					else
						echo '<td class="th"></td>';

					echo "<td><input type='text' name='jobman-field-$id' value='$data' /></td>";
					echo "<td><span class='description'>{$field['description']}</span></td></tr>";
					break;
				case 'radio':
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th><td>";
					else
						echo '<td class="th"></td><td>';

					$values = explode( "\n", strip_tags( $field['data'] ) );
					$display_values = explode( "\n", $field['data'] );

					foreach( $values as $key => $value ) {
						$checked = '';
						if( $value == $data )
							$checked = ' checked="checked"';
						echo "<input type='radio' name='jobman-field-$id' value='" . trim( $value ) . "'$checked /> {$display_values[$key]}<br/>";
					}
					echo '</td>';
					echo "<td><span class='description'>{$field['description']}</span></td></tr>";
					break;
				case 'checkbox':									// The intent was to have an array of checkboxes I think?
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th><td>";
					else
						echo '<td class="th"></td><td>';

					$values = explode( "\n", strip_tags( $field['data'] ) );
					$display_values = explode( "\n", $field['data'] );

					if( 'new' == $jobid )
						$data = array();
					else
						$data = explode( "\n", strip_tags( $data ) );

					foreach( $values as $key => $value ) {
						$value = trim( $value );
						$checked = '';
						if( in_array( $value, $data ) )
							$checked = ' checked="checked"';
						?> 
							<!-- Hidden input to send blank string when all are unchecked -->
							<input type="hidden" name="jobman-field-<?= $id ?>[]" value="" />				
						<?php
						echo "<input type='checkbox' name='jobman-field-{$id}[]' value='$value'$checked /> {$display_values[$key]}<br/>";
					}
					echo '</td>';
					echo "<td><span class='description'>{$field['description']}</span></td></tr>";
					break;
				case 'textarea':
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th>";
					else
						echo '<td class="th"></td>';

					if( '' == $field['description'] )
						echo "<td colspan='2'>";
					else
						echo '<td>';

					if( user_can_richedit() && version_compare( $wp_version, '3.3-aortic-dissection', '<' )) {
						echo "<p id='field-toolbar-$id' class='jobman-editor-toolbar'><a class='toggleHTML'>" . __( 'HTML', 'jobman' ) . '</a><a class="active toggleVisual">' . __( 'Visual', 'jobman' ) . '</a></p>';
						echo "<textarea class='large-text code jobman-editor jobman-field-$id' name='jobman-field-$id' id='jobman-field-$id' rows='7'>$data</textarea></td>";
					}
					else {
						$settings = array(
							'editor_class' => "large-text code jobman-editor jobman-field-$id"
						);
						wp_editor( $data, "jobman-field-$id", $settings );
					}

					if( '' == $field['description'] )
						echo '</tr>';
					else
						echo "<td><span class='description'>{$field['description']}</span></td></tr>";

					break;
				case 'date':
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th>";
					else
						echo '<td class="th"></td>';

					echo "<td><input type='text' class='datepicker' name='jobman-field-$id' value='$data' /></td>";
					echo "<td><span class='description'>{$field['description']}</span></td></tr>";
					break;
				case 'file':
					if( '' != $field['label'] )
						echo "<th scope='row'>{$field['label']}</th>";
					else
						echo '<td class="th"></td>';

					echo '<td>';
					echo "<input type='file' name='jobman-field-$id' />";

					if( ! empty( $data ) ) {
						echo '<br/><a href="' . wp_get_attachment_url( $data ) . '">' . wp_get_attachment_url( $data ) . '</a>';
						echo "<input type='hidden' name='jobman-field-current-$id' value='$data' />";
						echo "<br/><input type='checkbox' name='jobman-field-delete-$id' value='1' />" . __( 'Delete File?', 'jobman' );
					}

					echo "</td>";
					echo "<td><span class='description'>{$field['description']}</span></td></tr>";
					break;
				case 'heading':
					echo '</table>';
					echo "<h3>{$field['label']}</h3>";
					echo "<table>";
//					$tablecount++;
//					$totalrowcount--;
					$rowcount = 0;
					break;
				case 'html':
					echo "<td colspan='3'>$data</td></tr>";
					break;
				case 'blank':
					echo '<td colspan="3">&nbsp;</td></tr>';
					break;
			}

			$previd = "jobman-field-$id";
		}
	}
?>
			<tr>
				<th scope="row"><?php _e( 'Display Start Date', 'jobman' ) ?></th>
				<td>
					<input class="datepicker" type="text" name="jobman-displaystartdate" 
						value="<?php echo ( 'new' != $jobid )?( date( 'Y-m-d', strtotime( $job->post_date ) ) ):( '' ) ?>" />
				</td>
				<td>
					<span class="description">
						<?php _e( 'The date this job should start being displayed on the site. To start displaying 
						immediately, leave blank.', 'jobman' ) ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Display End Date', 'jobman' ) ?></th>
				<td>
					<input class="datepicker" type="text" name="jobman-displayenddate" 
						value="<?php echo ( array_key_exists( 'displayenddate', $jobdata ) )?( $jobdata['displayenddate'] ):( '' ) ?>" />
				</td>
				<td>
					<span class="description">
						<?php _e( 'The date this job should stop being displayed on the site. To display indefinitely, 
						leave blank.', 'jobman' ) ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Application Email', 'jobman' ) ?>
				</th>
				<td>
					<input class="regular-text" type="text" name="jobman-email" 
						value="<?php echo ( array_key_exists( 'email', $jobdata ) )?( $jobdata['email'] ):( '' ) ?>" />
				</td>
				<td>
					<span class="description">
						<?php _e( 'The email address to notify when an application is submitted for this job. For 
						default behaviour (category email or global email), leave blank.', 'jobman' ) ?>
					</span>
				</td>
			</tr>
<?php
	$checked = '';
	if( array_key_exists( 'highlighted', $jobdata ) && $jobdata['highlighted'] )
		$checked = ' checked="checked"';
?>
			<tr>
				<th scope="row"><?php _e( 'Highlighted?', 'jobman' ) ?></th>
				<td>
					<!-- Hidden control to send a '0' when unchecked -->
					<input type="hidden" name="jobman-highlighted" value="0">				
					<input type="checkbox" name="jobman-highlighted" value="1" <?= $checked ?>/>
				</td>
				<td>
					<span class="description"><?php _e( 'Mark this job as highlighted? For the behaviour of highlighted
					 jobs, see the Display Settings admin page.', 'jobman' ) ?>
					</span>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="submit" class="button-primary" value="<?= $submit ?>" />
			<?php $preview = __('Preview Job', 'jobman'); ?>
			<input type="submit" name="preview" class="button-primary" value="<?= $preview ?>" style="margin-left: 1em;"/>
		</p>
	</div>
	</form>
<?php
	return 1;
}

function jobman_job_delete_confirm() {
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
<?php
}

?>