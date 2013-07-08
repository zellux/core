<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\BackgroundJob;

/**
 * Class QueuedJob
 *
 * create a background job that is to be executed once
 *
 * @package OC\BackgroundJob
 */
class JobList {
	/**
	 * @param Job|string $job
	 * @param mixed $argument
	 */
	public function add($job, $argument = null) {
		if (!$this->has($job, $argument)) {
			if ($job instanceof Job) {
				$class = get_class($job);
			} else {
				$class = $job;
			}
			$argument = json_encode($argument);
			$sql = 'INSERT INTO `*PREFIX*jobs`(`class`, `argument`, `last_run`) VALUES(?, ?, 0)';
			\OC_DB::executeAudited($sql, array($class, $argument));
		}
	}

	/**
	 * @param Job|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null) {
		if ($job instanceof Job) {
			$class = get_class($job);
		} else {
			$class = $job;
		}
		if (!is_null($argument)) {
			$argument = json_encode($argument);
			$sql = 'DELETE FROM `*PREFIX*jobs` WHERE `class` = ? AND `argument` = ?';
			\OC_DB::executeAudited($sql, array($class, $argument));
		} else {
			$sql = 'DELETE FROM `*PREFIX*jobs` WHERE `class` = ?';
			\OC_DB::executeAudited($sql, array($class));
		}
	}

	/**
	 * check if a job is in the list
	 *
	 * @param $job
	 * @param mixed $argument
	 * @return bool
	 */
	public function has($job, $argument) {
		if ($job instanceof Job) {
			$class = get_class($job);
		} else {
			$class = $job;
		}
		$argument = json_encode($argument);
		$sql = 'SELECT `id` FROM `*PREFIX*jobs` WHERE `class` = ? AND `argument` = ?';
		$result = \OC_DB::executeAudited($sql, array($class, $argument));
		return (bool)$result->fetchRow();
	}

	/**
	 * get all jobs in the list
	 *
	 * @return Job[]
	 */
	public function getAll() {
		$sql = 'SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs`';
		$result = \OC_DB::executeAudited($sql);
		$jobs = array();
		while ($row = $result->fetchRow()) {
			$jobs[] = $this->buildJob($row);
		}
		return $jobs;
	}

	/**
	 * get the next job in the list
	 *
	 * @return Job
	 */
	public function getNext() {
		$lastId = $this->getLastJob();
		$query = \OC_DB::prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` WHERE `id` > ? ORDER BY `id` ASC', 1);
		$result = \OC_DB::executeAudited($query, array($lastId));
		if ($row = $result->fetchRow()) {
			return $this->buildJob($row);
		} else {
			//begin at the start of the queue
			$query = \OC_DB::prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` ORDER BY `id` ASC', 1);
			$result = \OC_DB::executeAudited($query);
			if ($row = $result->fetchRow()) {
				return $this->buildJob($row);
			} else {
				return null; //empty job list
			}
		}
	}

	/**
	 * @param int $id
	 * @return Job
	 */
	public function getById($id) {
		$sql = 'SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` WHERE `id` = ?';
		$result = \OC_DB::executeAudited($sql, array($id));
		if ($row = $result->fetchRow()) {
			return $this->buildJob($row);
		} else {
			return null;
		}
	}

	/**
	 * get the job object from a row in the db
	 *
	 * @param array $row
	 * @return Job
	 */
	private function buildJob($row) {
		$class = $row['class'];
		/**
		 * @var Job $job
		 */
		$job = new $class();
		$job->setId($row['id']);
		$job->setLastRun($row['last_run']);
		$job->setArgument(json_decode($row['argument']));
		return $job;
	}

	/**
	 * set the job that was last ran
	 *
	 * @param Job $job
	 */
	public function setLastJob($job) {
		\OC_Appconfig::setValue('backgroundjob', 'lastjob', $job->getId());
	}

	/**
	 * get the id of the last ran job
	 *
	 * @return int
	 */
	public function getLastJob() {
		return \OC_Appconfig::getValue('backgroundjob', 'lastjob', 0);
	}

	/**
	 * set the lastRun of $job to now
	 *
	 * @param Job $job
	 */
	public function setLastRun($job) {
		$sql = 'UPDATE `*PREFIX*jobs` SET `last_run` = ? WHERE `id` = ?';
		\OC_DB::executeAudited($sql, array(time(), $job->getId()));
	}
}
