<?php


namespace local_qmul_download_feedback;

defined('MOODLE_INTERNAL') || die();

use assign;
use coding_exception;
use mod_assign\event\base;

class all_feedbacks_downloaded extends base
{

	/**
	 * Flag for prevention of direct create() call.
	 *
	 * @var bool
	 */
	protected static $prevent_create_call = TRUE;

	/**
	 * Create instance of event.
	 *
	 * @param assign $assign
	 *
	 * @return \core\event\base
	 * @throws coding_exception
	 */
	public static function create_from_assign(assign $assign)
	{
		$data = array(
			'context' => $assign->get_context(),
			'objectid' => $assign->get_instance()->id
		);
		self::$prevent_create_call = FALSE;
		$event = self::create($data);
		self::$prevent_create_call = TRUE;
		$event->set_assign($assign);
		return $event;
	}

	/**
	 * Returns description of what happened.
	 *
	 * @return string
	 */
	final public function get_description(): string
	{
		return "The user with id '$this->userid' has downloaded all the feedbacks for the assignment " .
			"with course module id '$this->contextinstanceid'.";
	}

	/**
	 * Return localised event name.
	 *
	 * @return string
	 * @throws coding_exception
	 */
	public static function get_name(): string
	{
		return get_string('eventfeedbackviewed', 'mod_assign');
	}

	/**
	 * @inheritDoc
	 * Init method.
	 *
	 * @return void
	 */
	final public function init(): void
	{
		$this->data['crud'] = 'r';
		$this->data['edulevel'] = self::LEVEL_TEACHING;
		$this->data['objecttable'] = 'assign';
	}

	/**
	 * Return legacy data for add_to_log().
	 *
	 * @return array
	 * @throws coding_exception
	 */
	final public function get_legacy_logdata(): array
	{
		$this->set_legacy_logdata('download all feedback', get_string('downloadall', 'assign'));
		return parent::get_legacy_logdata();
	}

	/**
	 * Custom validation.
	 *
	 * @return void
	 * @throws coding_exception
	 */
	final public function validate_data(): void
	{
		if(self::$prevent_create_call) {
			throw new coding_exception('cannot call all_feedbacks_downloaded::create() directly, use all_feedbacks_downloaded::create_from_assign() instead.');
		}

		parent::validate_data();
	}

	/**
	 * @return string[]
	 */
	public static function get_objectid_mapping(): array
	{
		return array('db' => 'assign', 'restore' => 'assign');
	}
}