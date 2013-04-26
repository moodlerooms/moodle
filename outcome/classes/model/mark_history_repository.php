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
 * Mark History Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/mark.php');
require_once(__DIR__.'/mark_history.php');
require_once(__DIR__.'/abstract_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_model_mark_history_repository extends outcome_model_abstract_repository {
    protected $model = 'outcome_model_mark_history';
    protected $table = 'outcome_marks_history';

    /**
     * @param outcome_model_mark $mark
     * @return outcome_model_mark_history
     */
    public function map_mark_to_model(outcome_model_mark $mark) {
        $model                = new outcome_model_mark_history();
        $model->outcomemarkid = $mark->id;
        $model->courseid      = $mark->courseid;
        $model->outcomeid     = $mark->outcomeid;
        $model->userid        = $mark->userid;
        $model->graderid      = $mark->graderid;
        $model->result        = $mark->result;

        return $model;
    }

    public function create_mark(outcome_model_mark $mark) {
        $this->handle_action($mark, outcome_model_mark_history::ACTION_CREATE);
    }

    public function update_mark(outcome_model_mark $mark) {
        $this->handle_action($mark, outcome_model_mark_history::ACTION_UPDATE);
    }

    public function delete_mark(outcome_model_mark $mark) {
        $this->handle_action($mark, outcome_model_mark_history::ACTION_DELETE);
    }

    public function handle_action(outcome_model_mark $mark, $action) {
        $model         = $this->map_mark_to_model($mark);
        $model->action = $action;

        $this->save($model);
    }

    public function save(outcome_model_mark_history $model) {
        $model->timecreated = time();
        $model->id = $this->db->insert_record($this->table, $model);
    }
}