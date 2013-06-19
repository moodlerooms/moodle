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
 * Outcome Marking Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/model/mark_repository.php');
require_once(dirname(__DIR__).'/model/mark_history_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_mark {
    /**
     * @var outcome_model_mark_repository
     */
    protected $marks;

    /**
     * @var outcome_model_mark_history_repository
     */
    protected $history;

    /**
     * @param outcome_model_mark_repository $marks
     * @param outcome_model_mark_history_repository $history
     */
    public function __construct(outcome_model_mark_repository $marks = null,
                                outcome_model_mark_history_repository $history = null) {
        if (is_null($history)) {
            $history = new outcome_model_mark_history_repository();
        }
        if (is_null($marks)) {
            $marks = new outcome_model_mark_repository(null, $history);
        }
        $this->marks   = $marks;
        $this->history = $history;
    }

    /**
     * @param $courseid
     */
    public function remove_course_marks($courseid) {
        $this->marks->remove_by_courseid($courseid);
    }

    /**
     * Remove old mark history records
     */
    public function clean_history() {
        global $CFG;

        if (!empty($CFG->gradehistorylifetime)) { // Value in days.
            $time = time() - ($CFG->gradehistorylifetime * 3600 * 24);
            $this->history->remove_old($time);
        }
    }
}
