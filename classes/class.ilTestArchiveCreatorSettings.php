<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Object settings for the test archive creator plugin
 */
class ilTestArchiveCreatorSettings
{
	/** @var string archive status */
	public $status = ilTestArchiveCreatorPlugin::STATUS_INACTIVE;

	/** @var ilDateTime */
	public $schedule;

	/** @var string pass selection */
	public $pass_selection;

	/** @var  string  selection of the random questions to include in the archive */
	public $random_questions;

    /** @var bool include questions */
    public $include_questions;

    /** @var bool include answers */
    public $include_answers;

    /** @var bool questions_with_best_solution */
    public $questions_with_best_solution;

    /** @var bool answers_with_best_solution */
    public $answers_with_best_solution;

    /** @var bool min_rendering_wait */
    public $min_rendering_wait;

    /** @var bool max_rendering_wait */
    public $max_rendering_wait;

    /** @var float */
	public $zoom_factor;

	/** @var string */
	public $orientation;

	/** @var  ilDB $db */
	protected $db;

	/** @var ilTestArchiveCreatorPlugin $plugin */
	protected $plugin;

	/** @var  int id of the test object */
	protected $obj_id;


	/**
	 * ilTestArchiveCreatorSettings constructor.
	 * @param ilTestArchiveCreatorPlugin $plugin
	 * @param int $obj_id test object id
	 */
	public function __construct($plugin, $obj_id)
	{
		global $DIC;

		$this->plugin = $plugin;
		$this->db = $DIC->database();
		$this->obj_id = $obj_id;
		$this->read();
	}

	/**
	 * Read the archive settings
	 */
	protected function read()
	{
		// read the saved settings
		$query = "SELECT * FROM tarc_ui_settings WHERE obj_id = " . $this->db->quote($this->obj_id,'integer');
		$result = $this->db->query($query);
		if ($row = $this->db->fetchAssoc($result))
		{
			$this->status = (string) $row['status'];
			if (!empty($row['schedule'])) {
				$this->schedule = new ilDateTime($row['schedule'], IL_CAL_DATETIME);
			}

            $this->include_questions = (bool) $row['include_questions'];
            $this->include_answers = (bool) $row['include_answers'];
            $this->questions_with_best_solution = (bool) $row['questions_with_best_solution'];
            $this->answers_with_best_solution = (bool) $row['answers_with_best_solution'];
			$this->pass_selection = (string) $row['pass_selection'];
			$this->random_questions = (string) $row['random_questions'];
			$this->zoom_factor = (float) $row['zoom_factor'];
			$this->orientation = (string) $row['orientation'];
            $this->min_rendering_wait = (int) $row['min_rendering_wait'];
            $this->max_rendering_wait = (int) $row['max_rendering_wait'];
		}
		else {
			// initialize walues with those if the global configuration
			$config = $this->plugin->getConfig();
			$this->include_questions = (bool) $config->include_questions;
			$this->include_answers = (bool) $config->include_answers;
			$this->questions_with_best_solution = (bool) $config->questions_with_best_solution;
			$this->answers_with_best_solution = (bool) $config->answers_with_best_solution;
			$this->pass_selection = (string) $config->pass_selection;
			$this->random_questions = (string) $config->random_questions;
			$this->zoom_factor = (float) $config->zoom_factor;
			$this->orientation = (string) $config->orientation;
			$this->min_rendering_wait = (int) $config->min_rendering_wait;
			$this->max_rendering_wait = (int) $config->max_rendering_wait;
		}
	}

	/**
	 * Save the archive settings
	 * @return  boolean     success
	 */
	public function save()
	{
		$rows = $this->db->replace('tarc_ui_settings',
			array(
				'obj_id' => array('integer', $this->obj_id)
			),
			array(
				'status' => array('text', $this->status),
				'schedule' => array('timestamp', isset($this->schedule) ? $this->schedule->get(IL_CAL_DATETIME) : null),
				'include_questions' => array('integer', $this->include_questions),
                'include_answers' => array('integer', $this->include_answers),
                'questions_with_best_solution' => array('integer', $this->questions_with_best_solution),
                'answers_with_best_solution' => array('integer', $this->answers_with_best_solution),
                'pass_selection' => array('text', $this->pass_selection),
				'random_questions' => array('text', $this->random_questions),
				'zoom_factor' => array('float', $this->zoom_factor),
				'orientation' => array('string', $this->orientation),
                'min_rendering_wait' => array('integer', $this->min_rendering_wait),
                'max_rendering_wait' => array('integer', $this->max_rendering_wait)
			)
		);
		return $rows > 0;
	}

	/**
	 * Get the object ids of tests with scheduled archive creation that are due
	 * @return int[]
	 */
	public static function getScheduledObjects()
	{
		global $DIC;
		$db = $DIC->database();

		require_once('Services/Calendar/classes/class.ilDateTime.php');
		$time = new ilDateTime(time(), IL_CAL_UNIX);

		$query = "SELECT obj_id FROM tarc_ui_settings WHERE status = %s AND schedule <= %s";
		$result = $db->queryF($query,
			array('text', 'text'),
			array(ilTestArchiveCreatorPlugin::STATUS_PLANNED, $time->get(IL_CAL_DATETIME))
		);

		$obj_ids = array();
		while ($row = $db->fetchAssoc($result)) {
			$obj_ids[] = $row['obj_id'];
		}
		return $obj_ids;
	}

	/**
	 * Delete the archive settings of a test
	 * @param integer object id
	 */
	public static function deleteForObject($obj_id)
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM tarc_ui_settings WHERE obj_id = ' . $db->quote($obj_id,'integer');

		$db->manipulate($query);
	}

}