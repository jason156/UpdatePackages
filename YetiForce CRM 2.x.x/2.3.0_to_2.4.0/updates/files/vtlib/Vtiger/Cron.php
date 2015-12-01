<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

include_once 'vtlib/Vtiger/Utils.php';
require_once('include/database/PearDatabase.php');

/**
 * Provides API to work with Cron tasks
 * @package vtlib
 */
class Vtiger_Cron
{

	protected static $schemaInitialized = false;
	protected static $instanceCache = array();
	static $STATUS_DISABLED = 0;
	static $STATUS_ENABLED = 1;
	static $STATUS_RUNNING = 2;
	static $STATUS_COMPLETED = 3;
	protected $data;

	/**
	 * Constructor
	 */
	protected function __construct($values)
	{
		$this->data = $values;
		self::$instanceCache[$this->getName()] = $this;
	}

	/**
	 * set the value to the data
	 * @param type $value,$key
	 */
	protected function set($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Get id reference of this instance.
	 */
	function getId()
	{
		return $this->data['id'];
	}

	/**
	 * Get name of this task instance.
	 */
	function getName()
	{
		return decode_html($this->data['name']);
	}

	/**
	 * Get the frequency set.
	 */
	function getFrequency()
	{
		return intval($this->data['frequency']);
	}

	/**
	 * Get the status
	 */
	function getStatus()
	{
		return intval($this->data['status']);
	}

	/**
	 * Get the timestamp lastrun started.
	 */
	function getLastStart()
	{
		return intval($this->data['laststart']);
	}

	/**
	 * Get the timestamp lastrun ended.
	 */
	function getLastEnd()
	{
		return intval($this->data['lastend']);
	}

	/**
	 * Get the user datetimefeild
	 */
	function getLastEndDateTime()
	{
		if ($this->data['lastend'] != NULL) {
			$lastEndDateTime = new DateTimeField(date('Y-m-d H:i:s', $this->data['lastend']));
			return $lastEndDateTime->getDisplayDateTimeValue();
		} else {
			return '';
		}
	}

	/**
	 *
	 * get the last start datetime field
	 */
	function getLastStartDateTime()
	{
		if ($this->data['laststart'] != NULL) {
			$lastStartDateTime = new DateTimeField(date('Y-m-d H:i:s', $this->data['laststart']));
			return $lastStartDateTime->getDisplayDateTimeValue();
		} else {
			return '';
		}
	}

	/**
	 * Get Time taken to complete task
	 */
	function getTimeDiff()
	{
		$lastStart = $this->getLastStart();
		$lastEnd = $this->getLastEnd();
		$timeDiff = $lastEnd - $lastStart;
		return $timeDiff;
	}

	/**
	 * Get the configured handler file.
	 */
	function getHandlerFile()
	{
		return $this->data['handler_file'];
	}

	/**
	 * Get the Module name
	 */
	function getModule()
	{

		return $this->data['module'];
	}

	/**
	 * get the Sequence
	 */
	function getSequence()
	{
		return $this->data['sequence'];
	}

	/**
	 * get the description of cron
	 */
	function getDescription()
	{
		return $this->data['description'];
	}

	/**
	 * Check if task is right state for running.
	 */
	function isRunnable()
	{
		// Take care of last time (end - on success, start - if timedout)
		// Take care to start the cron im
		$lastTime = ($this->getLastStart() > 0) ? $this->getLastStart() : $this->getLastEnd();
		$elapsedTime = time() - $lastTime;
		return ($elapsedTime >= ($this->getFrequency() - 60));
	}

	/**
	 * Helper function to check the status value.
	 */
	function statusEqual($value)
	{
		$status = intval($this->data['status']);
		return $status == $value;
	}

	/**
	 * Is task in running status?
	 */
	function isRunning()
	{
		return $this->statusEqual(self::$STATUS_RUNNING);
	}

	/**
	 * Is task enabled?
	 */
	function isEnabled()
	{
		return $this->statusEqual(self::$STATUS_ENABLED);
	}

	/**
	 * Is task disabled?
	 */
	function isDisabled()
	{
		return $this->statusEqual(self::$STATUS_DISABLED);
	}

	/**
	 * Update status
	 */
	function updateStatus($status)
	{
		switch (intval($status)) {
			case self::$STATUS_DISABLED:
			case self::$STATUS_ENABLED:
			case self::$STATUS_RUNNING:
				break;
			default:
				throw new Exception('Invalid status');
		}
		self::querySilent('UPDATE vtiger_cron_task SET status=? WHERE id=?', array($status, $this->getId()));
	}
	/*
	 * update frequency
	 */

	function updateFrequency($frequency)
	{
		self::querySilent('UPDATE vtiger_cron_task SET frequency=? WHERE id=?', array($frequency, $this->getId()));
	}

	/**
	 * Mark this instance as running.
	 */
	function markRunning()
	{
		$time = time();
		self::querySilent('UPDATE vtiger_cron_task SET status=?, laststart=? WHERE id=?', array(self::$STATUS_RUNNING, $time, $this->getId()));
		return $this->set('laststart', $time);
	}

	/**
	 * Mark this instance as finished.
	 */
	function markFinished()
	{
		$time = time();
		self::querySilent('UPDATE vtiger_cron_task SET status=?, lastend=? WHERE id=?', array(self::$STATUS_ENABLED, $time, $this->getId()));
		return $this->set('lastend', $time);
	}

	/**
	 * Detect if the task was started by never finished.
	 */
	function hadTimeout()
	{
		if (!$this->isRunning()) {
			return false;
		}
		$maxExecutionTime = intval(ini_get('max_execution_time'));
		if ($maxExecutionTime == 0) {
			$maxExecutionTime = vglobal('maxExecutionCronTime');
		}
		$time = $this->getLastEnd();
		if ($time == 0) {
			$time = $this->getLastStart();
		}
		if (time() > ($time + $maxExecutionTime)) {
			return true;
		}
		return false;
	}

	/**
	 * Execute SQL query silently (even when table doesn't exist)
	 */
	protected static function querySilent($sql, $params = false)
	{
		$adb = PearDatabase::getInstance();
		$old_dieOnError = $adb->dieOnError;

		$adb->dieOnError = false;
		$result = $adb->pquery($sql, $params);
		$adb->dieOnError = $old_dieOnError;
		return $result;
	}

	/**
	 * Initialize the schema.
	 */
	protected static function initializeSchema()
	{
		if (!self::$schemaInitialized) {
			if (!Vtiger_Utils::CheckTable('vtiger_cron_task')) {
				Vtiger_Utils::CreateTable('vtiger_cron_task', '(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(100) UNIQUE KEY, handler_file VARCHAR(100) UNIQUE KEY,
					frequency int, laststart int(11) unsigned, lastend int(11) unsigned, status int,module VARCHAR(100),
                                        sequence int,description TEXT )', true);
			}
			self::$schemaInitialized = true;
		}
	}

	static function nextSequence()
	{
		$adb = PearDatabase::getInstance();
		$result = self::querySilent('SELECT MAX(sequence) as sequence FROM vtiger_cron_task ORDER BY SEQUENCE');
		if ($result && $adb->getRowCount($result)) {
			$sequence = $adb->getSingleValue($result);
		}
		if ($sequence == NULL) {
			$sequence = 1;
		}
		return $sequence + 1;
	}

	/**
	 * Register cron task.
	 */
	static function register($name, $handler_file, $frequency, $module = 'Home', $status = 1, $sequence = 0, $description = '')
	{
		self::initializeSchema();
		$adb = PearDatabase::getInstance();
		$instance = self::getInstance($name);
		if ($sequence == 0) {
			$sequence = self::nextSequence();
		}
		self::querySilent('INSERT INTO vtiger_cron_task (name, handler_file, frequency, status, sequence,module,description) VALUES(?,?,?,?,?,?,?)', array($name, $handler_file, $frequency, $status, $sequence, $module, $description));
	}

	/**
	 * De-register cron task.
	 */
	static function deregister($name)
	{
		self::querySilent('DELETE FROM vtiger_cron_task WHERE name=?', array($name));
		if (isset(self::$instanceCache["$name"])) {
			unset(self::$instanceCache["$name"]);
		}
	}

	/**
	 * Get instances that are active (not disabled)
	 */
	static function listAllActiveInstances($byStatus = 0)
	{
		$adb = PearDatabase::getInstance();

		$instances = array();
		if ($byStatus == 0) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE status <> ? ORDER BY SEQUENCE', array(self::$STATUS_DISABLED));
		} else {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task  ORDER BY SEQUENCE');
		}
		if ($result && $adb->num_rows($result)) {
			while ($row = $adb->fetch_array($result)) {
				$instances[] = new Vtiger_Cron($row);
			}
		}
		return $instances;
	}

	/**
	 * Get instance of cron task.
	 */
	static function getInstance($name)
	{
		$adb = PearDatabase::getInstance();

		$instance = false;
		if (isset(self::$instanceCache["$name"])) {
			$instance = self::$instanceCache["$name"];
		}

		if ($instance === false) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE name=?', array($name));
			if ($result && $adb->num_rows($result)) {
				$instance = new Vtiger_Cron($adb->fetch_array($result));
			}
		}
		return $instance;
	}

	/**
	 * Get instance of cron job by id
	 */
	static function getInstanceById($id)
	{
		$adb = PearDatabase::getInstance();
		$instance = false;
		if (isset(self::$instanceCache[$id])) {
			$instance = self::$instanceCache[$id];
		}


		if ($instance === false) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE id=?', array($id));
			if ($result && $adb->num_rows($result)) {
				$instance = new Vtiger_Cron($adb->fetch_array($result));
			}
		}
		return $instance;
	}

	static function listAllInstancesByModule($module)
	{
		$adb = PearDatabase::getInstance();

		$instances = array();
		$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE module=?', array($module));
		if ($result && $adb->num_rows($result)) {
			while ($row = $adb->fetch_array($result)) {
				$instances[] = new Vtiger_Cron($row);
			}
		}
		return $instances;
	}

	function unlockTask()
	{
		$this->updateStatus(self::$STATUS_ENABLED);
	}

	/**
	 * Delete all cron tasks associated with module
	 * @param Vtiger_Module Instnace of module to use
	 */
	static function deleteForModule($moduleInstance)
	{
		$db = PearDatabase::getInstance();
		$db->delete('vtiger_cron_task', 'module = ?', [$moduleInstance->name]);
	}
}
