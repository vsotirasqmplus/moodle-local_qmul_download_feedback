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

// $Id: download.php, v1.0 2020/09/3  v.sotiras@qmul.ac.uk Exp $

/**
 * This page downloads the feedback files
 * When looking for assignment grades you have to look for assign grades not the grade grades.
 * These grades will be used as itemid for each file to be identified in the files database
 * and downloaded inside the zip file.
 */

require_once('../../config.php');
/** @var $CFG object */
# require_once('lib.php');


try {
	global $PAGE;
	$id = required_param('id', PARAM_INT);
	$sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);

	$url = new moodle_url('/local/qmul_download_feedback/download.php', ['id' => $id, 'sesskey' => sesskey()]);
	$title = 'Assignment Feedback Files Download';
	[$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');

	require_login($course, TRUE, $cm);
	$context = context_module::instance($cm->id);
	$PAGE->set_url($url);
	$PAGE->set_context($context);
	$PAGE->set_title($title);

	require_capability('mod/assign:grade', $context);

	$get_zip = get_string('get_zip', 'local_qmul_download_feedback');
	$click_text = get_string('click_text', 'local_qmul_download_feedback');
	$list_of_files_text = get_string('list_of_files_text', 'local_qmul_download_feedback');
	$id_number = get_string('id_number', 'local_qmul_download_feedback');
	$description = get_string('description', 'local_qmul_download_feedback');

	$files = local_qmul_download_feedback\qmul_download_feedback_lib::get_assign_feedback_file_references($id);
	$urls = local_qmul_download_feedback\qmul_download_feedback_lib::get_files_urls($files);
	/* * @noinspection ForgottenDebugOutputInspection */
	/** @var object $OUTPUT */
	echo $OUTPUT->header();
	echo '<h1>' . $title . '</h1>';
	echo '<h2>' . $get_zip . '</h2>';
	echo '<p>' . $description . '</p>';
	echo '<a type="button" class="btn btn-primary btn-lg" target="_blank" href="send_zip.php?id='
		. $id . '&sesskey=' . sesskey() . '">' . $click_text . '</a>';
	echo '<p/><p/>';
	echo '<h2>' . $list_of_files_text . '</h2>';
	echo '<div style="
max-height:80vh; 
max-width:80vw; 
flex-direction: row; 
flex-wrap: wrap; 
justify-content: flex-start; 
align-content: flex-start; 
flex-flow: row wrap;
overflow-scrolling: auto; 
overflow: auto; 
display: flex;
margin: 1rem;
">';
	echo '<div>';
	$prev_idnumber = '';
	foreach($urls as $key => $url){
		[$idnumber, $file_id, $student] = explode('_', $key);
		if($prev_idnumber !== $idnumber) {
			$profile_link = html_writer::link(new moodle_url('/user/profile.php?id=' . $student),
											  $id_number . ' : ' . $idnumber,
											  ['target' => '_blank'
												  , 'type' => "button"
												  , 'class' => "btn btn-primary"
												  , 'style' => 'margin: 1rem']);
			echo '</div>
<div style="display: inline-flex;
border: black; 
margin: 1rem;
padding: 1rem; 
max-height: 15rem; 
min-height: 3rem; 
min-width: 15rem; 
max-width: 60rem;
flex-direction: column; 
flex-wrap: wrap; 
background: aliceblue;
border-radius: 1rem;
text-wrap: normal;
">', $profile_link, '<br/>';
		}
		$prev_idnumber = $idnumber;
		echo '<p>', $url, '</p>';
	}
	echo '</div>';
	echo $OUTPUT->footer();
} catch(Exception $e) {
	error_log($e->getMessage() . ' ' . $e->getTraceAsString());
}