<?php // $Id: download.php, v1.0 2020/09/3  v.sotiras@qmul.ac.uk Exp $

/**
 * This page downloads the feedback files
 * When looking for assignment grades you have to look for assign grades not the grade grades.
 * These grades will be used as itemid for each file to be identified in the files database
 * and downloaded inside the zip file.
 */

require_once('../../config.php');
/** @var $CFG object */
require_once("{$CFG->dirroot}/mod/assign/locallib.php");
require_once('lib.php');
require_once "{$CFG->dirroot}/enrol/externallib.php";

// This may take a long time.
core_php_time_limit::raise();

/*
 * We need to access a protected method inside the assign class to create zip files.
 * A proposal is to make a child class just for this minor issue
*/

class zip_assign extends assign
{
	/** @var context the context of the course module for this assign instance
	 *               (or just the course if we are creating a new one)
	 */
	private $context;

	/** @var cm_info the course module for this assign instance */
	private $coursemodule;

	/** @var stdClass the course this assign instance belongs to */
	private $course;

	/**
	 * Generate zip file from array of given files.
	 *
	 * @param array $files_for_zipping - array of files to pass into archive_to_pathname.
	 *                                 This array is indexed by the final file name and each
	 *                                 element in the array is an instance of a stored_file object.
	 *
	 * @return false|string filename of a temporary fie
	 *         not have a .zip extension - it is a temp file.
	 */
	final public function pack_files($files_for_zipping)
	{
		global $CFG;
		// Create path for new zip file.
		$temp_zip = tempnam($CFG->tempdir . '/', 'assignment_');
		// Zip files.
		$zipper = new zip_packer();
		if($zipper->archive_to_pathname($files_for_zipping, $temp_zip)) {
			return $temp_zip;
		}
		return FALSE;
	}

}

function get_assign_feedback_file_references(int $course_module_id): array
{
	# $files = get_assign_feedback_file_references($id);
	# $urls = get_files_urls($files);
	/* * @noinspection ForgottenDebugOutputInspection */
	# echo '<pre>' . print_r($urls, TRUE) . '</pre>';

	global $DB;
	$results = [];
	if($course_module_id > 0) {
		$context_module = CONTEXT_MODULE;
		$type = 'mod';
		$module = 'assign';
		$file_area = 'feedback_files';
		$component = 'assignfeedback_file';
		$sql = <<<SQL
SELECT 
	  f.id
	, f.contextid
	, stu.idnumber AS student_idnumber
	, gi.itemname AS assignment_name
	, aff.numfiles
	, f.component
	, f.filearea
	, f.itemid
	, f.filename
	, f.filepath
FROM {course_modules} AS cm
JOIN {modules} AS mo ON mo.id = cm.module
JOIN {assign} AS ma ON ma.id = cm.instance
JOIN {context} AS cx ON cx.instanceid = cm.id
JOIN {assign_grades} AS ag ON ag.assignment = ma.id
JOIN {user} AS stu ON stu.id = ag.userid
JOIN {grade_items} AS gi ON gi.courseid = cm.course
	AND gi.iteminstance = ma.id
	AND gi.itemmodule = mo.name
JOIN {grade_grades} AS gg ON gg.itemid = gi.id
	AND gg.userid = stu.id
JOIN {user} AS tea ON tea.id = gg.usermodified
JOIN {assignfeedback_file} AS aff ON aff.assignment = ma.id
	AND aff.grade = ag.id
JOIN {files} AS f ON f.contextid = cx.id
	AND f.itemid = ag.id
WHERE cm.id = {$course_module_id}
	AND mo.name = '{$module}'
	AND gi.itemtype = '{$type}'
	AND cx.contextlevel = {$context_module}
	AND f.filearea = '{$file_area}'
	and f.component = '{$component}'
	and f.filesize > 0
	and gg.overridden = 0
	and gg.hidden = 0
	and f.status = 0
order by 1		
SQL;
		try {
			$results = $DB->get_records_sql($sql);

		} catch(Exception $exception) {
			/** @noinspection ForgottenDebugOutputInspection */
			error_log($exception->getMessage() . ' ' . $exception->getTraceAsString());
		}
	}
	return $results;
}

function get_files_urls(array $files): array
{
	# $fs = get_file_storage();
	$returns = [];
	foreach($files as $key => $value){
		# $file = $fs->get_file($value->contextid, $value->component, $value->filearea, $value->itemid, $value->filepath, $value->filename);
		$url3 = moodle_url::make_pluginfile_url($value->contextid, $value->component, $value->filearea, $value->itemid, $value->filepath, $value->filename);
		$link = html_writer::link($url3, $value->filename);
		$returns[$value->student_idnumber . '_' . $value->id] = $link;
	}
	return $returns;
}

try {
	global $PAGE;
	$id = required_param('id', PARAM_INT);

	$url = new moodle_url('/local/qmul_download_feedback/download.php', ['id' => $id]);

	[$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');

	require_login($course, TRUE, $cm);
	$context = context_module::instance($cm->id);
	$PAGE->set_url($url);
	$PAGE->set_context($context);

	require_capability('mod/assign:grade', $context);

	$users = get_enrolled_users($context);


	#$grade_item_grademax = $grading_info->items[0]->grademax;
	#foreach ($users as $user) {
	#	$user_final_grade = $grading_info->items[0]->grades[$user->id];
	#}

	$assignment = new zip_assign($context, $cm, $course);
	$assignment->get_feedback_plugins();
	if($assignment->is_any_feedback_plugin_enabled()) {
		foreach($assignment->load_plugins('assignfeedback') as $plugin_id => $plugin){
			# we need only the 'file' type plugin named 'Feedback files'
			if($plugin->get_type() === 'file' and $plugin->is_enabled() and $plugin->get_name() === 'Feedback files') {
				$feedback_plugin = $plugin;
			}
		}
	}
	if($feedback_plugin) {
		# echo '<pre>' . print_r($feedback_plugin, TRUE) . '</pre>';
		$fs = get_file_storage();
		/** @var object $USER */
		$filename = clean_filename($course->shortname . '-' .
								   $assignment->get_instance($USER->id)->name . '-' .
								   $cm->id . '.zip');
		$feedback_files = [];
		$user_grades = [];
		foreach(array_keys($users) as $user_id){
			$grading_info = grade_get_grades($course->id, 'mod', 'assign', $cm->instance, [$user_id]);
			foreach($grading_info->items as $item_id => $item){
				if(count($item->grades) > 0) {
					foreach($item->grades as $grade_user_id => $grade){
						if(!is_null($grade->grade)) {
							# $grade_id_sql = "SELECT gg.id FROM {grade_grades} AS gg WHERE gg.userid = {$user_id} AND gg.itemid = {$item->id} AND (gg.timemodified = {$grade->dategraded} OR gg.timecreated = {$grade->dategraded})";
							# An attempt to get the grade grades record for the user failed to identify feedback files
							# Second attempt will be based on assign grades
							$grade_id_sql = "SELECT ag.id FROM {assign_grades} AS ag WHERE ag.userid = {$user_id} AND ag.assignment = {$cm->instance} AND (ag.timemodified = {$grade->dategraded} OR ag.timecreated = {$grade->dategraded})";
							# $grade_id = $DB->get_record_sql($grade_id_sql, NULL, IGNORE_MULTIPLE);
							/** @var object $DB */
							$grade_id = $DB->get_record_sql($grade_id_sql, NULL, IGNORE_MULTIPLE)->id ?? 0;
							$user_grades[$item->id][$user_id][$grade_id] = $grade;
							# $feedback_files[$grade_id] = $fs->get_area_files($context->id, 'assignfeedback_file', 'feedback_files', $grade_id, "itemid, filepath, filename", FALSE);
							$user_files = $fs->get_area_files($context->id, 'assignfeedback_file', 'feedback_files', $grade_id, "itemid, filepath, filename", FALSE);
							foreach($user_files as $id => $user_file){
								# error_log($user_file->file_record->filename);
								$feedback_files[$user_file->get_filename()] = $user_file;
							}
						}
					}
				}
			}
		}
		# check if there are any files
		if(count($feedback_files) > 0) {
			# errors of the packing should be contained
			ob_start();
			$zip_file = $assignment->pack_files($feedback_files);
			# discard error outputs
			ob_end_clean();
			# Send the Zip file
			send_temp_file($zip_file, $filename);
		} else {
			echo 'Feedback files not found';
		}
		# echo '<pre>' . print_r(array_keys($feedback_files), TRUE) . '</pre>';
	} else {
		echo 'Feedback file plugin is not enabled';
	}
} catch(coding_exception $e) {
} catch(moodle_exception $e) {
}
