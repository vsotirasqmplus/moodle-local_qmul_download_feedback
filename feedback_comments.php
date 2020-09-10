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
 * This page shows links to downloads the feedback files
 * When looking for assignment grades you have to look for assign grades not the grade grades.
 * These grades will be used as itemid for each file to be identified in the files database
 * and downloaded inside the zip file.
 * $Id: feedback_comments.php, v1.0 2020/09/09  <v.sotiras@qmul.ac.uk> Exp $
 */

require_once('../../config.php');
require_once('locallib.php');
global $PAGE;
try {
    $id = required_param('id', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);

    $url = new moodle_url('/local/qmul_download_feedback/feedback_comments.php', ['id' => $id, 'sesskey' => sesskey()]);
    $title = 'Assignment Feedback Files Download';
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');

    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_title($title);

    require_capability('mod/assign:grade', $context);
    $feedbacktextlink = local_qmul_download_feedback_get_text_feedback($id);
    echo $feedbacktextlink;

} catch (Exception $exception) {
    debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
}
