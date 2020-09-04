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
 * Library of static functions for local/qmul_download_feedback
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 */

namespace local_qmul_download_feedback;

use assign;
use assign_feedback_file as assign_feedback_fileAlias;
use context_module;
use Exception;
use html_writer;
use moodle_exception;
use moodle_url;
use stdClass;

# require_once '../../../../config.php';
/** @var object $CFG */
/** @noinspection PhpIncludeInspection */
require_once "{$CFG->dirroot}/enrol/externallib.php";

defined('MOODLE_INTERNAL') || die();

class qmul_download_feedback_lib
{
	/**
	 * @param int $id assignment ID
	 *
	 * @return array of stored files
	 */
	public static function get_feedback_files(int $id): array
	{
		global $DB;
		$feedback_files = [];
		try {
			[$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');
			$context = context_module::instance($cm->id);
			$users = get_enrolled_users($context);
			$fs = get_file_storage();
			foreach(array_keys($users) as $user_id){
				$grading_info = grade_get_grades($course->id, 'mod', 'assign', $cm->instance, [$user_id]);
				foreach($grading_info->items as $item_id => $item){
					if(count($item->grades) > 0) {
						foreach($item->grades as $grade_user_id => $grade){
							if(!is_null($grade->grade)) {
								# {assign grades}.id = {files}.itemid
								$grade_id_sql = "SELECT ag.id FROM {assign_grades} AS ag 
WHERE ag.userid = {$user_id} 
	AND ag.assignment = {$cm->instance}
	AND (ag.timemodified = {$grade->dategraded} OR ag.timecreated = {$grade->dategraded})";
								$grade_id = $DB->get_record_sql($grade_id_sql, NULL, IGNORE_MULTIPLE)->id ?? 0;
								$user_files = $fs->get_area_files($context->id
									, 'assignfeedback_file'
									, 'feedback_files'
									, $grade_id
									, "itemid, filepath, filename"
									, TRUE);
								foreach($user_files as $user_file){
									$feedback_files[$user_file->get_filename()] = $user_file;
								}
							}
						}
					}
				}
			}
		} catch(moodle_exception $e) {
		}
		return $feedback_files;
	}


	/**
	 * @param int $assignment_id
	 *
	 * @return stdClass|null
	 */
	public static function is_assignment_blind(int $assignment_id): ?stdClass
	{
		global $DB;
		try {
			$sql = <<<SQL
SELECT blindmarking FROM {assign} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)
SQL;
			return $DB->get_record_sql($sql, array($assignment_id))->blindmarking;

		} catch(Exception $exception) {
			error_log($exception->getMessage() . ' ' . $exception->getTraceAsString());
			return NULL;
		}
	}

	/**
	 * @param assign $assignment
	 *
	 * @return mixed|null
	 */
	public static function get_feedback_file_plugin(assign $assignment): ?assign_feedback_fileAlias
	{
		$feedback_plugin = NULL;
		if($assignment->is_any_feedback_plugin_enabled()) {
			foreach($assignment->load_plugins('assignfeedback') as $plugin_id => $plugin){
				# we need only the 'file' type plugin named 'Feedback files'
				if($plugin->get_type() === 'file' && $plugin->is_enabled() && $plugin->get_name() === 'Feedback files') {
					$feedback_plugin = $plugin;
				}
			}
		}
		return $feedback_plugin;
	}

	/**
	 * @param int $course_module_id
	 *
	 * @return array
	 */
	public static function get_assign_feedback_file_references(int $course_module_id): array
	{
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
	, stu.id AS student_id
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
	AND f.component = '{$component}'
	AND f.filesize > 0
	AND gg.overridden = 0
	AND gg.hidden = 0
	AND f.status = 0
ORDER BY stu.id		
SQL;
			try {
				$results = $DB->get_records_sql($sql);

			} catch(Exception $exception) {
				error_log($exception->getMessage() . ' ' . $exception->getTraceAsString());
			}
		}
		return $results;
	}

	/**
	 * @param array $files
	 *
	 * @return array
	 */
	public static function get_files_urls(array $files): array
	{
		# $fs = get_file_storage();
		$returns = [];
		foreach($files as $key => $value){
			$url3 = moodle_url::make_pluginfile_url($value->contextid
				, $value->component
				, $value->filearea
				, $value->itemid
				, $value->filepath
				, $value->filename);
			$link = html_writer::link($url3, $value->filename);
			$returns[$value->student_idnumber . '_' . $value->id . '_' . $value->student_id] = $link;
		}
		return $returns;
	}
}