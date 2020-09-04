<?php // $Id: download.php, v1.0 2020/09/3  v.sotiras@qmul.ac.uk Exp $

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

	$get_zip = 'Download all as zip archive';
	$click_text = 'Click to get the archive';
	$list_of_files_text = 'Individual Feedback Files List';
	$id_number = 'Student ID';
	$file_number = ''; # 'File ID';
	$file_feedback = 'Feedback file';
	$description = 'Please allow enough time to accumulate and archive all the available files before you are prompted to save it';

	$files = local_qmul_download_feedback\qmul_download_feedback_lib::get_assign_feedback_file_references($id);
	$urls = local_qmul_download_feedback\qmul_download_feedback_lib::get_files_urls($files);
	/* * @noinspection ForgottenDebugOutputInspection */
	/** @var object $OUTPUT */
	echo $OUTPUT->header();
	echo '<h1>' . $title . '</h1>';
	echo '<h2>' . $get_zip . '</h2>';
	echo '<p>' . $description . '</p>';
	echo '<a type="button" class="btn btn-primary" target="_blank" href="send_zip.php?id='
		. $id . '&sesskey=' . sesskey() . '">' . $click_text . '</a>';
	echo '<p/><p/>';
	echo '<h2>' . $list_of_files_text . '</h2>';

	# echo $id_number, '&emsp;', $file_number, '&emsp;', $file_feedback, '<br/>';
	$prev_idnumber = '';
	foreach($urls as $key => $url){
		[$idnumber, $file_id, $student] = explode('_', $key);
		if($prev_idnumber !== $idnumber) {
			$profile_link = html_writer::link(new moodle_url('/user/profile.php?id=' . $student),
											  $id_number . ' : ' . $idnumber,
											  ['target' => '_blank', 'type'=>"button", 'class'=>"btn btn-info"]);
			echo '<br/>', $profile_link, '<br/>';
		}
		$prev_idnumber = $idnumber;
		echo $url, '<br/>';
	}
	echo $OUTPUT->footer();
} catch(coding_exception $e) {
} catch(moodle_exception $e) {
}

