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

/** @noinspection PhpIncludeInspection */
/*
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 * Library of functions and constants for module local_qmul_download_feedback
 *
 */
global $CFG;

require_once("{$CFG->dirroot}/mod/assign/locallib.php");

/**
 * @param $set_nav
 * @param $context
 */
function local_qmul_download_feedback_extend_settings_navigation($set_nav, $context)
{
	global $CFG;
	try {
		if(!get_config('local_qmul_download_feedback', 'enable')) {
			return;
		}
		$assign_admin_nav_obj = $set_nav->find('modulesettings', 70);
		$contextlevel = $context->contextlevel;
		$capable = has_capability('mod/assign:grade', $context);

		//add link to assignment admin menu
		if(($contextlevel === CONTEXT_MODULE)
			&& isset($assign_admin_nav_obj->text)
			&& ($assign_admin_nav_obj->text === 'Assignment administration')
			&& $capable) {
			$assignment_instance_id = $context->instanceid;
			$download_url = $CFG->wwwroot
				. '/local/qmul_download_feedback/download.php?id='
				. $assignment_instance_id .'&sesskey='.sesskey();
			$link_text = get_config('local_qmul_download_feedback', 'label');
			$assign_admin_nav_obj->add($link_text, new moodle_url($download_url));
			array_reverse($assign_admin_nav_obj->find_all_of_type(70), TRUE);
		}
	} catch(dml_exception $e) {
		error_log($e->getMessage() . ' ' . $e->getTraceAsString());
	} catch(coding_exception $e) {
		error_log($e->getMessage() . ' ' . $e->getTraceAsString());
	} catch(moodle_exception $e) {
		error_log($e->getMessage() . ' ' . $e->getTraceAsString());
	}
}