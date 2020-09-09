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
 * $Id: download.php, v1.0 2020/09/03  <v.sotiras@qmul.ac.uk> Exp $
 */
require_once('../../config.php');
require_once('locallib.php');
try {
    global $OUTPUT;
    global $PAGE;
    $id = required_param('id', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);

    $url = new moodle_url('/local/qmul_download_feedback/download.php', ['id' => $id, 'sesskey' => sesskey()]);
    $title = 'Assignment Feedback Files Download';
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');

    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_title($title);

    require_capability('mod/assign:grade', $context);

    $getzip = get_string('get_zip', 'local_qmul_download_feedback');
    $clicktext = get_string('click_text', 'local_qmul_download_feedback');
    $examtext = get_string('examine_archive', 'local_qmul_download_feedback');
    $listoffilestext = get_string('list_of_files_text', 'local_qmul_download_feedback');
    $idnumber = get_string('id_number', 'local_qmul_download_feedback');
    $description = get_string('description', 'local_qmul_download_feedback');

    $files = local_qmul_download_feedback\qmul_download_feedback_lib::get_assign_feedback_file_references($id);
    $urls = local_qmul_download_feedback\qmul_download_feedback_lib::get_files_urls($files);

    echo $OUTPUT->header();
    echo '<h1>' . $title . '</h1>';
    echo '<h2>' . $getzip . '</h2>';
    echo '<p>' . $description . '</p>';
    echo '<a type="button" class="btn btn-primary btn-lg" target="_blank" href="send_zip.php?id='
        . $id . '&sesskey=' . sesskey() . '">' . $clicktext . '</a>';
    echo '<p>&emsp;</p>';
    echo '<a type="button" class="btn btn-primary btn-lg" target="_blank" href="send_zip.php?id='
        . $id . '&sesskey=' . sesskey() . '&test=1">' . $examtext . '</a>';
    echo '<p>&emsp;</p>';
    echo '<h2>' . $listoffilestext . '</h2>';
    echo '<button class="btn btn-info" onclick="lqdf_toggle()" id="lqdftoggle" type="button" >'
        . get_string('toggle_files', 'local_qmul_download_feedback') . '</button>';
    ?>
    <br/><br/>
    <script>
        function lqdf_toggle() {
            var elem = document.getElementById('lqdf_files');
            if (elem) {
                if (elem.style.display === "none") {
                    elem.style.display = "flex";
                } else {
                    elem.style.display = "none";
                }
            }
        }
    </script>
    <div id="lqdf_files" style="
max-height:80vh;
max-width:80vw;
flex-direction: row;
flex-wrap: wrap;
justify-content: flex-start;
align-content: flex-start;
flex-flow: row wrap;
overflow-scrolling: auto;
overflow: auto;
margin: 1rem;
display: none;
">
        <div>
    <?php
    $previdnumber = '';
    foreach ($urls as $key => $url) {
        [$idnumber, $fileid, $student] = explode('_', $key);
        if ($previdnumber !== $idnumber) {
            $profilelink = html_writer::link(
                new moodle_url('/user/profile.php?id=' . $student),
                $idnumber . ' : ' . $idnumber,
                ['target' => '_blank'
                    , 'type' => "button"
                    , 'class' => "btn btn-primary"
                    , 'style' => 'margin: 1rem']
            );
            echo '</div>
<div style="display: inline-flex;
overflow: auto;
text-wrap: normal;
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
">', '<div style="display: grid; float: top">', $profilelink, '</div>';
        }
        $previdnumber = $idnumber;
        echo '<div style="float: top; position: relative">', $url, '</div>';
    }
    echo '</div>
</div>';
    echo '<h2>' . get_string('feedback_comments', 'local_qmul_download_feedback') . '</h2>';
    $feedbacktextlink = qmul_download_feedback_show_feedback_comments_link($id);
    $feedbacktextlink = str_replace('<a ', '<a type="button" class="btn btn-primary btn-lg" ', $feedbacktextlink);
    echo $feedbacktextlink;
    echo $OUTPUT->footer();
} catch (Exception $e) {
    debugging($e->getMessage() . ' ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
}
