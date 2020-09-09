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
function qmul_download_feedback_show_feedback_comments_link(int $cmid): string {
    global $DB, $CFG;
    $html = '';
    if ($cmid > 0) {
        $stu = $DB->sql_concat_join("' '", ['cm.course', 'co.shortname', 'cm.id', 'ma.name', 'ac.assignment']);
        $tea = $DB->sql_concat_join("' '", ['tea.idnumber', 'tea.username', 'tea.firstname', 'tea.lastname']);
        $sql = "
SELECT
       ag.id, $stu as student, $tea as teacher, ag.grade, ac.commenttext
FROM {course_modules} AS cm
JOIN {assign} AS ma ON ma.id = cm.instance
JOIN {modules} AS mo ON mo.id = cm.module
JOIN {course} AS co ON co.id = cm.course
JOIN {assignfeedback_comments} AS ac ON ac.assignment = ma.id
JOIN {assign_grades} AS ag ON ag.id = ac.grade
JOIN {user} AS stu ON stu.id = ag.userid
JOIN {user} AS tea ON tea.id = ag.grader
WHERE mo.name  = :module AND cm.id = :cmid ";
        try {
            $records = $DB->get_records_sql($sql, ['module' => 'assign', 'cmid' => $cmid]);
            if ($records) {
                $action = new moodle_url('/local/qmul_download_feedback/feedback_comments.php'
                    , ['id' => $cmid, 'sesskey' => sesskey()]);
                $html .= html_writer::link($action,
                                           get_string('feedback_comments', 'local_qmul_download_feedback'), ['target' => '_blank']);
            } else {
                $html .= '<p><strong>' . get_string('no_feedback_comments', 'local_qmul_download_feedback') . '</strong></p>';
            }
        } catch (Exception $exception) {
            if ($CFG->debug) {
                debugging('COMMENTS_SQL_ERR ' . $exception->getMessage()
                          . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
            }
        }
    }
    return $html;
}

function local_qmul_download_feedback_get_text_feedback(int $cmid): string {
    $html = qmul_download_feedback_show_feedback_comments_link($cmid);
    if ($html) {
        $html = html_writer::start_div('success', ['font-weight' => 'bold']);
        $h1 = '<h1>';

        try {
            list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'assign');
            if ($course) {
                $h1 .= $course->shortname . '&emsp;';
            }
            if ($cm) {
                $h1 .= $cm->name;
            }
            $h1 .= '</h1>';
            $html .= $h1;
            $html .= '<h2>' . get_string('feedback_comments_header', 'block_assign_get_feedback') . '</h2>';
            $html .= local_qmul_download_feedback_get_feedback_comments($cmid);
        } catch (Exception $exception) {
            debugging('COMMENTS_SQL_ERR ' . $exception->getMessage()
                      . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
        }
        $html .= html_writer::end_div();

    }

    return $html;
}
/**
 * @param int $cmid
 *
 * @return string
 */
function local_qmul_download_feedback_get_feedback_comments(int $cmid): string {
    global $DB;
    $html = '';
    if ($cmid > 0) {
        $assgn = $DB->sql_concat_join("'<br/>'", ['ag.id', 'cm.course', 'co.shortname', 'cm.id', 'ma.name', 'ac.assignment']);
        $stu = $DB->sql_concat_join("'<br/>'", ['stu.idnumber', 'stu.username', 'stu.firstname', 'stu.middlename', 'stu.lastname']);
        $tea = $DB->sql_concat_join("'<br/>'", ['tea.idnumber', 'tea.username', 'tea.firstname', 'tea.middlename', 'tea.lastname']);
        $sql = "
SELECT
       $assgn AS assignment,
       $stu AS student,
       $tea AS teacher,
       ag.grade,
       ac.commenttext AS feedback_text
FROM {course_modules} AS cm
JOIN {assign} AS ma ON ma.id = cm.instance
JOIN {modules} AS mo ON mo.id = cm.module
JOIN {course} AS co ON co.id = cm.course
JOIN {assignfeedback_comments} AS ac ON ac.assignment = ma.id
JOIN {assign_grades} AS ag ON ag.id = ac.grade
JOIN {user} AS stu ON stu.id = ag.userid
JOIN {user} AS tea ON tea.id = ag.grader
WHERE mo.name  = :module AND cm.id = :cmid AND ac.commenttext > '' ";
        try {
            $records = $DB->get_records_sql($sql, ['module' => 'assign', 'cmid' => $cmid]);
            if ($records) {
                $header = 0;
                $html .= '<table>';
                foreach ($records as $record) {
                    $html .= '<tr>';
                    foreach ($record as $name => $value) {
                        // Disallow scripts inside the feedback comments to be executed in the browser.
                        $value = str_replace(array('<script', '</script'), array('<filtered', '</filtered'), $value);
                        $html .= '<td style="vertical-align:top;text-align: left;border-bottom: 1px solid #ddd;">';
                        if ($header === 0) {
                            $html .= "<h3>$name</h3><hr/>$value";
                        } else {
                            $html .= $value;
                        }
                        $html .= '</td>';
                    }
                    $header = 1;
                    $html .= '</tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<p>' . get_string('no_feedback_comments', 'block_assign_get_feedback') . '</p>';
            }
        } catch (Exception $e) {
            if (isset($CFG->debug)) {
                debugging($e->getMessage() . ' ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
            }
        }
    }
    return $html;
}
