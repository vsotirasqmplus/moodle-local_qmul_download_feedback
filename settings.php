<?php
/**
 * Settings details.
 *
 * @package    local
 * @subpackage local_qmul_download_feedback
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $ADMIN, $hassiteconfig;
$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
if($hassiteconfig) {
	try {
		$settings = new admin_settingpage('local_qmul_download_feedback', get_string('pluginname', 'local_qmul_download_feedback'));

		$settings->add(new admin_setting_heading('local_qmul_download_feedback' . '/heading', ' ', get_string('pluginname_desc', 'local_qmul_download_feedback')));

		$settings->add(new admin_setting_configcheckbox('local_qmul_download_feedback' . '/enable', get_string('enable', 'local_qmul_download_feedback'), get_string('use_url', 'local_qmul_download_feedback'), 0));

		$settings->add(new admin_setting_configtext('local_qmul_download_feedback' . '/label', get_string('linklabel', 'local_qmul_download_feedback'), '', 'Download all feedbacks'));

		$ADMIN->add('localplugins', $settings);
	} catch(Exception $exception) {
		/** @noinspection ForgottenDebugOutputInspection */
		error_log($exception->getMessage() . ' ' . $exception->getTraceAsString());
	}
}