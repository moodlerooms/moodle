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
 * Mark Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/mark.php');
require_once(__DIR__.'/mark_history_repository.php');
require_once(__DIR__.'/abstract_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_model_mark_repository extends outcome_model_abstract_repository {
    protected $model = 'outcome_model_mark';
    protected $table = 'outcome_marks';

    /**
     * @var outcome_model_mark_history_repository
     */
    protected $history;

    public function __construct(moodle_database $db = null, outcome_model_mark_history_repository $history = null) {
        parent::__construct($db);

        if (is_null($history)) {
            $history = new outcome_model_mark_history_repository();
        }
        $this->history = $history;
    }

    /**
     * @param int $id The mark ID
     * @param int $strictness
     * @return outcome_model_mark|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return outcome_model_mark|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find_one_by(array $conditions, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by($conditions, $strictness);
    }

    /**
     * @param array $conditions
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return outcome_model_mark[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * @param array $ids
     * @return outcome_model_mark[]
     */
    public function find_by_ids(array $ids) {
        return $this->_find_by_ids($ids);
    }

    /**
     * Save a mark
     *
     * @param outcome_model_mark $model
     */
    public function save(outcome_model_mark $model) {
        $model->timemodified = time();

        if (!empty($model->id)) {
            $this->db->update_record($this->table, $model);
            $this->history->update_mark($model);
        } else {
            $model->timecreated = $model->timemodified;
            $model->id          = $this->db->insert_record($this->table, $model);
            $this->history->create_mark($model);
        }
    }

    /**
     * Remove a mark
     *
     * @param outcome_model_mark $model
     */
    public function remove(outcome_model_mark $model) {
        $this->db->delete_records($this->table, array('id' => $model->id));
        $this->history->delete_mark($model);
        $model->id = null;
    }
}