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
 * Outcome Report AJAX Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/abstract.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_controller_report_ajax extends outcome_controller_abstract {
    /**
     * @var outcome_model_outcome_repository
     */
    public $outcomes;

    /**
     * @var outcome_service_report_helper
     */
    public $reporthelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     * @throws coding_exception
     */
    public function require_capability($action) {
        global $PAGE;

        switch ($action) {
            case 'user_completion':
            case 'user_scales':
            case 'user_attempts':
                require_capability('moodle/outcome:edit', $PAGE->context);
                break;
            default:
                throw new coding_exception("Missing capability check for $action action");
        }

    }

    public function init($action) {
        parent::init($action);

        require_once(dirname(__DIR__).'/model/outcome_repository.php');
        require_once(dirname(__DIR__).'/service/report_helper.php');

        $this->outcomes = new outcome_model_outcome_repository();
        $this->reporthelper = new outcome_service_report_helper();
    }

    /**
     * Generate activity completion information for a user and
     * all activities that are associated to the same outcome.
     *
     * @return string
     * @throws coding_exception
     */
    public function user_completion_action() {
        global $DB, $COURSE;

        $userid    = required_param('userid', PARAM_INT);
        $outcomeid = required_param('outcomeid', PARAM_INT);

        $completion = new completion_info($COURSE);
        if (!$completion->is_enabled()) {
            throw new coding_exception('Course completion is not enabled');
        }
        $user       = $DB->get_record('user', array('id' => $userid), 'id, firstname, lastname', MUST_EXIST);
        $outcome    = $this->outcomes->find($outcomeid, MUST_EXIST);
        $activities = $this->reporthelper->get_activity_completion_by_user($outcome->id, $userid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('activitycompletion', 'outcome'),
            'body' => $this->renderer->user_activity_completion($user, $outcome, $activities),
        ));
    }

    /**
     * Generate activity grades for all activities that use
     * scales for their grade item and are also associated
     * to the same outcome.
     *
     * @return string
     */
    public function user_scales_action() {
        global $COURSE;

        $userid    = required_param('userid', PARAM_INT);
        $outcomeid = required_param('outcomeid', PARAM_INT);

        $outcome    = $this->outcomes->find($outcomeid, MUST_EXIST);
        $activities = $this->reporthelper->get_activity_scales_by_user($outcome->id, $userid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('scales'),
            'body'   => $this->renderer->user_activity_scale_grades($outcome, $activities),
        ));
    }

    /**
     * Generate outcome activity attempts for a user.
     *
     * @return string
     */
    public function user_attempts_action() {
        global $COURSE;

        $userid    = required_param('userid', PARAM_INT);
        $outcomeid = required_param('outcomeid', PARAM_INT);

        $outcome  = $this->outcomes->find($outcomeid, MUST_EXIST);
        $attempts = $this->reporthelper->get_activity_attempts_by_user($outcome->id, $userid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('scales'),
            'body'   => $this->renderer->user_activity_attempt_grades($outcome, $attempts),
        ));
    }
}