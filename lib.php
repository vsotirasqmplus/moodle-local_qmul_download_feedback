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
 * @noinspection PhpIncludeInspection
 */
/*
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 * Library of functions and constants for module local_qmul_download_feedback
 *
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("{$CFG->dirroot}/mod/assign/locallib.php");

/**
 * @param $setnav
 * @param $context
 *
 * @noinspection PhpUnused
 */
function local_qmul_download_feedback_extend_settings_navigation($setnav, $context) {
    global $CFG;
    try {
        if (!get_config('local_qmul_download_feedback', 'enable')) {
            return;
        }
        $assignadminnavobj = $setnav->find('modulesettings', 70);
        $contextlevel = $context->contextlevel;
        $capable = has_capability('mod/assign:grade', $context);

        // Add link to assignment admin menu.
        if (($contextlevel === CONTEXT_MODULE)
            && isset($assignadminnavobj->text)
            && ($assignadminnavobj->text === 'Assignment administration')
            && $capable
        ) {
            $assignmentinstanceid = $context->instanceid;
            $downloadurl = $CFG->wwwroot
                . '/local/qmul_download_feedback/download.php?id='
                . $assignmentinstanceid . '&sesskey=' . sesskey();
            $linktext = get_config('local_qmul_download_feedback', 'label');
            $assignadminnavobj->add($linktext, new moodle_url($downloadurl));
            array_reverse($assignadminnavobj->find_all_of_type(70), true);
        }
    } catch (dml_exception $e) {
        debugging($e->getMessage() . ' ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
    } catch (coding_exception $e) {
        debugging($e->getMessage() . ' ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
    } catch (moodle_exception $e) {
        debugging($e->getMessage() . ' ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
    }
}
