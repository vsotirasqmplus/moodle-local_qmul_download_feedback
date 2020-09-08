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
 * Settings details.
 *
 * @package    local
 * @subpackage local_qmul_download_feedback
 * @author     Vasileios Sotiras  <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $ADMIN;
try {
    $hassiteconfig = has_capability('moodle/site:config', context_system::instance());
} catch (coding_exception $exception) {
    debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
    $hassiteconfig = false;
} catch (dml_exception $exception) {
    debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
    $hassiteconfig = false;
}
if ($hassiteconfig) {
    try {
        $settings = new admin_settingpage(
            'local_qmul_download_feedback',
            get_string('pluginname', 'local_qmul_download_feedback')
        );

        $settings->add(
            new admin_setting_heading(
                'local_qmul_download_feedback' . '/heading',
                ' ',
                get_string('pluginname_desc', 'local_qmul_download_feedback')
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_qmul_download_feedback' . '/enable',
                get_string('enable', 'local_qmul_download_feedback'),
                get_string('use_url', 'local_qmul_download_feedback'),
                0
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_qmul_download_feedback' . '/label',
                get_string('linklabel', 'local_qmul_download_feedback'),
                get_string('get_zip', 'local_qmul_download_feedback'),
                get_string('get_zip', 'local_qmul_download_feedback')
            )
        );

        $ADMIN->add('localplugins', $settings);
    } catch (Exception $exception) {
        debugging($exception->getMessage() . ' ' . $exception->getTraceAsString(), DEBUG_DEVELOPER);
    }
}
