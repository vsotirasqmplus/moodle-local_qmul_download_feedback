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

require('../../config.php');
try {
    require_login();
    // This may take a long time.
    core_php_time_limit::raise();
    global $CFG;
    global $PAGE, $OUTPUT;
    $id = required_param('id', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);
    $test = optional_param('test', '0', PARAM_INT);
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');
    $url = new moodle_url('/local/qmul_download_feedback/send_zip.php', ['id' => $id, 'sesskey' => $sesskey]);
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');
    $PAGE->set_context($context);
    $PAGE->set_url($url);
    $PAGE->set_title(get_string('get_zip', 'local_qmul_download_feedback'));
    require_capability('mod/assign:grade', $context);

    $assignment = new zip_assign($context, $cm, $course);
    $assignment->get_feedback_plugins();

    $feedbackplugin = qmul_download_feedback_lib::get_feedback_file_plugin($assignment);
    if ($feedbackplugin) {
        global $USER;
        $filename = clean_filename(
            $course->shortname . '-' .
            $assignment->get_instance($USER->id)->name . '-' .
            $cm->id . '-feedback-files.zip'
        );

        $feedbackfiles = local_qmul_download_feedback\qmul_download_feedback_lib::get_feedback_files($id);

        // Check if there are any files.
        if (count($feedbackfiles) > 0) {
            // Errors of the packing such as invalid or missing should be contained.
            ob_start();
            $zipfile = $assignment->pack_files($feedbackfiles);
            // Record error outputs.
            $errors = strip_tags(purify_html(ob_get_clean())) . "<br/>";
            if (isset($errors)) {
                $errors = str_replace('line', "<br/>line", $errors);
                $errors = str_replace('Can not', "<br/>Can not", $errors);
                // Remove call stack lines.
                $errlines = [];
                foreach (explode('<br/>', $errors) as $line) {
                    if (strpos($line, 'Can not zip ') !== false) {
                        $errlines[] = $line . '<br/>';
                    }
                }
                if (count($errlines) > 0) {
                    $errors = implode($errlines);
                }
                // The  starting part of the line Format [Wed Sep 09 02:26:54.151203 2020] .
                $ms = sprintf('%09.6f', date('s') + fmod(microtime(true), 1));
                $type = ' [php7:notice] ';
                $date = date("[D M d H:i:{$ms} Y]", time());
                file_put_contents("php://stderr", $date . $type . $errors);
            }
            if ($test === 1) {
                if ($errors) {
                    echo $OUTPUT->header();
                    echo '<h1>' . get_string('exam_error', 'local_qmul_download_feedback'), '</h1>';
                    echo $errors;
                    echo $OUTPUT->footer();
                } else {
                    echo $OUTPUT->header();
                    echo '<h1>' . get_string('exam_fine', 'local_qmul_download_feedback'), '</h1>';
                    echo $OUTPUT->footer();
                }
            } else {
                if ($zipfile) {
                    // Send the Zip file.
                    send_temp_file($zipfile, $filename);
                } else {
                    echo $OUTPUT->header();
                    echo get_string('files_not_found', 'local_qmul_download_feedback');
                    echo $OUTPUT->footer();
                }
            }
        } else {
            echo $OUTPUT->header();
            echo get_string('files_not_found', 'local_qmul_download_feedback');
            echo $OUTPUT->footer();
        }
    } else {
        echo $OUTPUT->header();
        echo get_string('feedback_file_plugin_not_enabled', 'local_qmul_download_feedback');
        echo $OUTPUT->footer();
    }
} catch (Exception $exception) {
    debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
}
