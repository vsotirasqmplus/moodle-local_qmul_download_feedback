<?php /** @noinspection PhpIncludeInspection */ // $Id: lib.php, v1.0 2015/04/16  v.sotiras@qmul.ac.uk Exp $
/*
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 */
/**
 * Library of functions and constants for module local_qmul_download_feedback
 *
 */
global $CFG;

require_once("{$CFG->dirroot}/mod/assign/locallib.php");


/*
 * Alters assignment settings menu
 * @param $set_nav stdClass navigation
 * @param $context stdClass context
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
		if(($contextlevel === CONTEXT_MODULE) && isset($assign_admin_nav_obj->text) && ($assign_admin_nav_obj->text === 'Assignment administration') && $capable) {

			$assignment_instance_id = $context->instanceid;


			$download_url = $CFG->wwwroot . '/local/qmul_download_feedback/download.php?id=' . $assignment_instance_id .'&sesskey='.sesskey();
			$link_text = get_config('local_qmul_download_feedback', 'label');

			$assign_admin_nav_obj->add($link_text, new moodle_url($download_url));


			//remove the default download all feedbacks link from the assignment menu
			/*$assign_admin_nav_all_types =*/
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




