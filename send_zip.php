<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
			echo get_string('files_not_found', 'local_qmul_download_feedback');
		}
	} else {
		echo get_string('feedback_file_plugin_not_enabled', 'local_qmul_download_feedback');
	}
} catch(Exception $exception) {
	error_log($exception->getMessage() . ' ' . $exception->getTraceAsString());
}