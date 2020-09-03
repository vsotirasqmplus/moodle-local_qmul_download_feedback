<?php

namespace local_qmul_download_feedback;

global $CFG;
# require_once "{$CFG->libdir}/filestorage/stored_file.php";
# require_once "{$CFG->libdir}/filebrowser/virtual_root_file.php";
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/filelib.php');

use assign;
use assign_header;
use coding_exception;
use mod_assign\event\all_submissions_downloaded;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use stored_file;
use virtual_root_file;

class assign_feedback_download extends assign
{
	public function __construct($context, $cm, $course)
	{
		parent::__construct($context, $cm, $course);
	}

	/*
	 * @return bool
	 *
	 *
	 */
	public function download_all_feedbacks_by_id()
	{
		global $CFG, $DB;

		$this->require_view_grades();

		$context = $this->get_context();

		//Load all users with submit
		$students = get_enrolled_users($context, "mod/assign:submit", NULL, 'u.*', NULL, NULL, NULL, $this->show_only_active_users());

		$this->submissionplugins = parent::get_submission_plugins();

		//Build a list of files to zip
		$files_for_zipping = array();
		$fs = get_file_storage();

		$group_mode = groups_get_activity_groupmode($this->get_course_module());
		//All users
		$group_id = 0;
		$group_name = '';
		if($group_mode) {
			$group_id = groups_get_activity_group($this->get_course_module(), TRUE);
			$group_name = groups_get_group_name($group_id) . '-';
		}

		$filename = clean_filename($this->get_course()->shortname . '-' .
								   $this->get_instance()->name . '-' .
								   $group_name . $this->get_course_module()->id . '.zip');

		// Get all the files for each student.
		foreach($students as $student){
			$userid = $student->id;

			if((groups_is_member($group_id, $userid) or !$group_mode or !$group_id)) {
				// Get the plugins to add their own files to the zip.

				$submission_group = FALSE;
				$group_name = '';
				if($this->get_instance()->teamsubmission) {
					$submission = $this->get_group_submission($userid, 0, FALSE);
					$submission_group = $this->get_submission_group($userid);
					if($submission_group) {
						$group_name = $submission_group->name . '-';
					} else {
						$group_name = get_string('defaultteam', 'assign') . '-';
					}
				} else {
					$submission = $this->get_user_submission($userid, FALSE);
				}


				//This bit of code writes the files names
				//It used to be displayed only if module is blind marked
				$prefix = str_replace('_', ' ', $group_name . get_string('username', 'local_qmul_download_feedback'));
				$prefix = clean_filename($prefix . '_' . $student->username . '_' . get_string('studentid', 'local_qmul_download_feedback') . '_' . $student->idnumber . '_');


				if($submission) {
					foreach($this->submissionplugins as $plugin){
						if($plugin->is_enabled() && $plugin->is_visible()) {
							$plugin_files = $plugin->get_files($submission, $student);
							foreach($plugin_files as $zip_filename => $file){
								$subtype = $plugin->get_subtype();
								$type = $plugin->get_type();
								$prefixed_filename = clean_filename($prefix .
																   $subtype .
																   '_' .
																   $type .
																   '_' .
																   $zip_filename);
								$files_for_zipping[$prefixed_filename] = $file;
							}
						}
					}
				}
			}
		}
		$result = '';
		if(count($files_for_zipping) == 0) {
			$header = new assign_header($this->get_instance(),
										$this->get_context(),
										'',
										$this->get_course_module()->id,
										get_string('downloadall', 'assign'));
			$result .= $this->get_renderer()->render($header);
			$result .= $this->get_renderer()->notification(get_string('nosubmission', 'assign'));
			$url = new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id,
				'action' => 'grading'));
			$result .= $this->get_renderer()->continue_button($url);
			$result .= $this->view_footer();
		} else if($zipfile = $this->pack_files($files_for_zipping)) {
			# \mod_assign\event\all_submissions_downloaded::create_from_assign($this)->trigger();
			// Send file and delete after sending.
			send_temp_file($zipfile, $filename);
			// We will not get here - send_temp_file calls exit.
		}
		return $result;

	}


	/**
	 * @param int  $user_id
	 * @param int  $int
	 * @param bool $FALSE
	 *
	 * @return bool|int
	 * @noinspection MissingReturnTypeInspection
	 */
	private function get_group_feedback(int $user_id, int $int, bool $FALSE)
	{
		// TODO: instead of get_group_submission
		# get_string('nofeedback', 'assign') # should be defined
		return $user_id ?? $int ?? $FALSE;
	}

	/**
	 * @param int $user_id
	 *
	 * @return int
	 */
	private function get_feedback_group(int $user_id): int
	{
		// TODO: instead of get_group_submission
		return $user_id;
	}

	/**
	 * @param int  $user_id
	 * @param bool $FALSE
	 *
	 * @return bool|int
	 * @noinspection ProperNullCoalescingOperatorUsageInspection
	 * @noinspection MissingReturnTypeInspection
	 */
	public function get_user_feedback(int $user_id, bool $FALSE)
	{
		// TODO: instead of get_user_submission
		return $this->get_user_submission($user_id, $FALSE);
	}

	public function get_assignment_is_blind($assignment_id): bool
	{
		global $DB;

		$sql = "SELECT blindmarking FROM {assign} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)";

		try {
			$result = $DB->get_record_sql($sql, array($assignment_id));
		} catch(\dml_exception $exception) {

		}

		return $result->blindmarking;
	}


}
