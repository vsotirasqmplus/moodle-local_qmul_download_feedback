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
/**
 * Library of static functions for local/qmul_download_feedback
 * 4th Sep 2020
 *
 * php version 7.2.0
 *
 * @category Local
 * @package  Local_Qmul_Download_Feedback
 * @author   Vasileios Sotiras <v.sotiras@qmul.ac.uk>
 * @license  GPL v3
 * @link     http://qmplus.qmul.ac.uk
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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/enrol/externallib.php");

defined('MOODLE_INTERNAL') || die();

/**
 * Class qmul_download_feedback_lib
 *
 * @category Local
 * @package  Local_Qmul_Download_Feedback
 * @author   Vasileios Sotiras <v.sotiras@qmul.ac.uk>
 * @license  GPL v3
 * @link     http://qmplus.qmul.ac.uk
 */
class qmul_download_feedback_lib {

    /**
     * @param int $id
     *
     * @return bool
     */
    public static function is_blindmarked(int $id): bool {
        global $DB;
        $blind = '0';
        try {
            $cmid = $DB->get_field('course_modules', 'instance', ['id' => $id], IGNORE_MISSING);
            if ($cmid) {
                $blind = $DB->get_field('assign', 'blindmarking', ['id' => $cmid], IGNORE_MISSING);
            }
        } catch (Exception $exception) {
            debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
        }
        return ($blind == '1');
    }

    /**
     * @param int $id assignment ID
     *
     * @return array of stored files
     */
    public static function get_feedback_files(int $id): array {
        global $DB;
        $files = [];
        try {
            [$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');
            $isblind = self::is_blindmarked((int)$cm->id);
            $context = context_module::instance($cm->id);
            $users = get_enrolled_users($context);
            $fs = get_file_storage();
            foreach (array_keys($users) as $user) {
                $gradinginfo = grade_get_grades($course->id, 'mod', 'assign', $cm->instance, [$user]);
                foreach ($gradinginfo->items as $itemid => $item) {
                    if (count($item->grades) > 0) {
                        foreach ($item->grades as $gradeuserid => $grade) {
                            if (!is_null($grade->grade)) {
                                // Association rule {assign grades}.id = {files}.itemid .
                                $gradeidsql = "SELECT ag.id FROM {assign_grades} AS ag
WHERE ag.userid = {$user}
	AND ag.assignment = {$cm->instance}
	AND (ag.timemodified = {$grade->dategraded} OR ag.timecreated = {$grade->dategraded})";
                                $gradeid = $DB->get_record_sql($gradeidsql, null, IGNORE_MULTIPLE)->id ?? 0;
                                $userfiles = $fs->get_area_files(
                                    $context->id,
                                    'assignfeedback_file',
                                    'feedback_files',
                                    $gradeid,
                                    "itemid, filepath, filename",
                                    true
                                );
                                foreach ($userfiles as $userfile) {
                                    $fileindex = $userfile->get_filename();
                                    // Avoid dot file names.
                                    if ($fileindex !== '.' && strlen($fileindex) > 0) {
                                        // Get the file name and extension.
                                        [$fname, $fext] = self::get_filename_and_extension($fileindex);
                                        // If it is blind marked set the file id as file name.
                                        if ($isblind) {
                                            // Add the anonymous file with its original extension.
                                            $fileindex = $userfile->get_id() . $fext;
                                        } else {
                                            // Avoid overriding existing user files and add idnumber.

                                            // In general terms I should not expect this but just in case.
                                            if (isset($files[$fileindex])) {
                                                if (self::is_group_submit()) {
                                                    // Allow the first file only. Trick the loop.
                                                    $fileindex .= '';
                                                } else {
                                                    // Individual users file name conflict clarification.
                                                    $useridnumber =
                                                        ($users[$user]->idnumber > '') ?
                                                            $users[$user]->idnumber : $users[$user]->username;
                                                    $fileindex = $fname . '_' . $useridnumber . $fext;
                                                }
                                            }
                                        }
                                        // Use a valid filename for the zip.
                                        $fileindex = clean_filename($fileindex);
                                        $files[$fileindex] = $userfile;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (moodle_exception $exception) {
            debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
        }
        return $files;
    }

    private static function get_filename_and_extension(string $filename) {
        $filenamepart = '';
        $fileextension = '';
        $explodedname = explode('.', $filename);
        if (($pieces = count($explodedname)) > 1) {
            $fileextension = '.' . $explodedname[$pieces - 1];
            unset($explodedname[$pieces - 1]);
            $filenamepart = implode('.', $explodedname);
        } else {
            $filenamepart = $filename;
            $fileextension = '';
        }
        return [$filenamepart, $fileextension];
    }

    /**
     * @param assign $assignment
     *
     * @return mixed|null
     */
    public static function get_feedback_file_plugin(assign $assignment): ?assign_feedback_fileAlias {
        $feedbackplugin = null;
        if ($assignment->is_any_feedback_plugin_enabled()) {
            foreach ($assignment->load_plugins('assignfeedback') as $pluginid => $plugin) {
                // We need only the 'file' type plugin named 'Feedback files'.
                if ($plugin->get_type() === 'file' && $plugin->is_enabled() && $plugin->get_name() === 'Feedback files') {
                    $feedbackplugin = $plugin;
                }
            }
        }
        return $feedbackplugin;
    }

    /**
     * @param int $coursemoduleid
     *
     * @return array
     */
    public static function get_assign_feedback_file_references(int $coursemoduleid): array {
        global $DB;
        $results = [];
        if ($coursemoduleid > 0) {
            $contextmodule = CONTEXT_MODULE;
            $type = 'mod';
            $module = 'assign';
            $filearea = 'feedback_files';
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
WHERE cm.id = {$coursemoduleid}
	AND mo.name = '{$module}'
	AND gi.itemtype = '{$type}'
	AND cx.contextlevel = {$contextmodule}
	AND f.filearea = '{$filearea}'
	AND f.component = '{$component}'
	AND f.filesize > 0
	AND gg.overridden = 0
	AND gg.hidden = 0
	AND f.status = 0
ORDER BY stu.id
SQL;
            try {
                $results = $DB->get_records_sql($sql);
            } catch (Exception $exception) {
                debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
            }
        }
        return $results;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    public static function get_files_urls(array $files): array {
        $returns = [];
        foreach ($files as $key => $value) {
            $url3 = moodle_url::make_pluginfile_url(
                $value->contextid,
                $value->component,
                $value->filearea,
                $value->itemid,
                $value->filepath,
                $value->filename
            );
            $link = html_writer::link($url3, $value->filename);
            $returns[$value->student_idnumber . '_' . $value->id . '_' . $value->student_id] = $link;
        }
        return $returns;
    }
}
