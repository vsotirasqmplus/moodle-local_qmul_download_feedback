<?php
/*
 * Zip Sending script for local/qmul_download_feedback
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 */

use local_qmul_download_feedback\qmul_download_feedback_lib;
use local_qmul_download_feedback\zip_assign;

include "../../config.php";
// This may take a long time.
core_php_time_limit::raise();

try {
	$id = required_param('id', PARAM_INT);
	$sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);
	$url = new moodle_url('/local/qmul_download_feedback/send_zip.php', ['id' => $id, 'sesskey' => sesskey()]);
	[$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');
	$context = context_module::instance($cm->id);
	require_capability('mod/assign:grade', $context);

	$assignment = new zip_assign($context, $cm, $course);
	$assignment->get_feedback_plugins();

	$feedback_plugin = qmul_download_feedback_lib::get_feedback_file_plugin($assignment);
	if($feedback_plugin) {

		/** @var object $USER */
		$filename = clean_filename($course->shortname . '-' .
								   $assignment->get_instance($USER->id)->name . '-' .
								   $cm->id . '-feedback-files.zip');


		$feedback_files = local_qmul_download_feedback\qmul_download_feedback_lib::get_feedback_files($id);

		# check if there are any files
		if(count($feedback_files) > 0) {
			# errors of the packing such as invalid or missing should be contained
			ob_start();
			$zip_file = $assignment->pack_files($feedback_files);
			# discard error outputs
			ob_end_clean();
			# Send the Zip file
			send_temp_file($zip_file, $filename);
		} else {
			echo 'Feedback files not found';
		}
	} else {
		echo 'Feedback file plugin is not enabled';
	}

} catch(Exception $exception) {

}