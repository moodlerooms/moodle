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
 * Outcome Attempt Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/model/area_repository.php');
require_once(dirname(__DIR__).'/model/attempt_repository.php');
require_once(dirname(__DIR__).'/attempt/mod_grade.php');

/**
 * Outcome Attempt Service
 *
 * This class assists with common use cases
 * when dealing with outcome attempts.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_attempt {
    /**
     * @var outcome_model_attempt_repository
     */
    protected $attempts;

    /**
     * @var outcome_attempt_mod_grade
     */
    protected $modattempt;

    /**
     * @param outcome_model_attempt_repository $attempts
     * @param outcome_attempt_mod_grade $modattempt
     */
    public function __construct(outcome_model_attempt_repository $attempts = null,
                                outcome_attempt_mod_grade $modattempt = null) {

        if (is_null($attempts)) {
            $attempts = new outcome_model_attempt_repository();
        }
        if (is_null($modattempt)) {
            $modattempt = new outcome_attempt_mod_grade();
        }
        $this->attempts = $attempts;
        $this->modattempt = $modattempt;
    }

    /**
     * Fetch a single outcome attempt
     *
     * @param array $conditions
     * @param int $strictness
     * @return bool|outcome_model_attempt
     */
    public function get_attempt(array $conditions, $strictness = IGNORE_MISSING) {
        return $this->attempts->find_one_by($conditions, $strictness);
    }

    /**
     * Find attempts against a particular item ID
     *
     * @param $usedareaid
     * @param $itemid
     * @param array $userids Optional, restrict to a list of users
     * @return outcome_model_attempt[]
     */
    public function get_item_attempts($usedareaid, $itemid, array $userids = array()) {
        return $this->attempts->find_by_itemid($usedareaid, $itemid, $userids);
    }

    /**
     * @param outcome_model_attempt $model
     * @param bool $duplicatecheck Perform a duplicate check (be 100% sure if disabling!)
     */
    public function save_attempt(outcome_model_attempt $model, $duplicatecheck = true) {
        $this->attempts->save($model, $duplicatecheck);
    }

    /**
     * Determine if the attempt(s) exists
     *
     * @param array $conditions
     * @return bool
     */
    public function attempts_exist(array $conditions) {
        return $this->attempts->exists_by($conditions);
    }

    /**
     * Delete an attempt
     *
     * @param outcome_model_attempt $model
     * @throws coding_exception
     */
    public function remove_attempt(outcome_model_attempt $model) {
        if (empty($model->id)) {
            throw new coding_exception('The attempt model must have the ID set');
        }
        $this->attempts->remove($model);
    }

    /**
     * For a given activity, sync all grades to outcome attempts.
     *
     * @param int $cmid Course module ID
     * @param string|bool $modulename Pass the module name if you have it
     */
    public function sync_mod_attempts_with_gradebook($cmid, $modulename = false) {
        $cm = get_coursemodule_from_id($modulename, $cmid);
        $this->modattempt->sync_with_grade_item($cm);
    }

    /**
     * Sync an activity grade with an outcome attempt.
     *
     * It is safe to pass any grade as it will be validated and
     * ignored if it is not the correct type.
     *
     * If calling this for several grades, it is faster to process grades
     * if they are grouped by the grade item.
     *
     * @param grade_grade $grade
     * @param bool $deleted
     */
    public function sync_mod_attempt_with_grade(grade_grade $grade, $deleted = false) {
        $this->modattempt->sync_with_grade($grade, $deleted);
    }

    /**
     * Remove attempts associated to a course module ID
     *
     * @param int $cmid The course module ID to have its attempts removed
     * @param outcome_model_area|array|null|boolean $excludedareas If null or boolean, then this is ignored.
     *        If a single area or multiple areas are passed, then these will not have their attempts removed.
     */
    public function remove_mod_attempts($cmid, $excludedareas = null) {
        if ($excludedareas instanceof outcome_model_area) {
            $excludedareas = array($excludedareas);
        } else if (!is_array($excludedareas)) {
            $excludedareas = array();
        }
        $this->attempts->remove_by_cmid($cmid, $excludedareas);
    }
}
