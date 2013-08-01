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
     * @var outcome_service_coverage_helper
     */
    public $coveragehelper;

    /**
     * @var outcome_service_activity_helper
     */
    public $activityhelper;

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
            case 'course_completion':
            case 'course_attempts':
            case 'course_scales':
            case 'course_coverage_resources':
            case 'course_coverage_activities':
            case 'course_coverage_questions':
            case 'course_performance_associated_content':
                require_capability('moodle/grade:edit', $PAGE->context);
                break;
            default:
                throw new coding_exception("Missing capability check for $action action");
        }

    }

    public function init($action) {
        parent::init($action);

        require_once(dirname(__DIR__).'/model/outcome_repository.php');
        require_once(dirname(__DIR__).'/service/report_helper.php');
        require_once(dirname(__DIR__).'/service/coverage_helper.php');
        require_once(dirname(__DIR__).'/service/activity_helper.php');

        $this->outcomes       = new outcome_model_outcome_repository();
        $this->reporthelper   = new outcome_service_report_helper();
        $this->coveragehelper = new outcome_service_coverage_helper();
        $this->activityhelper = new outcome_service_activity_helper(null, $this->reporthelper);
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
        $activities = $this->activityhelper->get_activity_completion_by_user($outcome->id, $userid, $COURSE->id);

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
        $activities = $this->activityhelper->get_activity_scales_by_user($outcome->id, $userid, $COURSE->id);

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
        $attempts = $this->activityhelper->get_activity_attempts_by_user($outcome->id, $userid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('scales'),
            'body'   => $this->renderer->user_activity_attempt_grades($outcome, $attempts),
        ));
    }

    /**
     * Generate activity completion information for the course optionally limited to a group and
     * all activities that are associated to the same outcome.
     *
     * @return string
     * @throws coding_exception
     */
    public function course_completion_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $groupid   = optional_param('groupid', 0, PARAM_INT);

        $completion = new completion_info($COURSE);
        if (!$completion->is_enabled()) {
            throw new coding_exception('Course completion is not enabled');
        }
        $outcome    = $this->outcomes->find($outcomeid, MUST_EXIST);
        $activities = $this->activityhelper->get_activity_completion_by_course($outcome->id, $COURSE->id, $groupid);

        return json_encode(array(
            'header' => get_string('activitycompletion', 'outcome'),
            'body' => $this->renderer->course_activity_completion($outcome, $activities),
        ));
    }

    /**
     * Generate activity grades for all activities that use
     * scales for their grade item and are also associated
     * to the same outcome.
     *
     * @return string
     */
    public function course_scales_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $groupid   = optional_param('groupid', 0, PARAM_INT);

        $outcome    = $this->outcomes->find($outcomeid, MUST_EXIST);
        $activities = $this->activityhelper->get_activity_scales_by_course($outcome->id, $COURSE->id, $groupid);

        return json_encode(array(
            'header' => get_string('scales'),
            'body'   => $this->renderer->course_activity_scale_grades($outcome, $activities),
        ));
    }

    /**
     * Generate outcome activity attempts for the course optionally limited to a group
     *
     * @return string
     */
    public function course_attempts_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $groupid   = optional_param('groupid', 0, PARAM_INT);

        $outcome  = $this->outcomes->find($outcomeid, MUST_EXIST);
        $attempts = $this->activityhelper->get_activity_attempts_by_course($outcome->id, $COURSE->id, $groupid);

        return json_encode(array(
            'header' => get_string('grades'),
            'body'   => $this->renderer->course_activity_attempt_grades($outcome, $attempts),
        ));
    }

    /**
     * Generates coverage information for resources mapped against outcomes.
     *
     * @return string
     */
    public function course_coverage_resources_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $outcome   = $this->outcomes->find($outcomeid, MUST_EXIST);

        $activities = $this->coveragehelper->get_coverage_resources($outcomeid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('resources', 'outcome'),
            'body'   => $this->renderer->coverage_activities($outcome, $activities),
        ));
    }

    /**
     * Generates coverage information for activities mapped against outcomes.
     *
     * @return string
     */
    public function course_coverage_activities_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $outcome   = $this->outcomes->find($outcomeid, MUST_EXIST);

        $activities = $this->coveragehelper->get_coverage_activities($outcomeid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('activities', 'outcome'),
            'body'   => $this->renderer->coverage_activities($outcome, $activities),
        ));
    }

    /**
     * Generates coverage information for questions mapped against outcomes.
     *
     * @return string
     */
    public function course_coverage_questions_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $outcome   = $this->outcomes->find($outcomeid, MUST_EXIST);

        $questions = $this->coveragehelper->get_coverage_questions($outcomeid, $COURSE->id);
        return json_encode(array(
            'header' => get_string('questions', 'outcome'),
            'body'   => $this->renderer->coverage_questions($outcome, $questions),
        ));
    }

    /**
     * Generates associated activities information for course performance report.
     *
     * @return string
     */
    public function course_performance_associated_content_action() {
        global $COURSE;

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $outcome   = $this->outcomes->find($outcomeid, MUST_EXIST);

        $content = $this->reporthelper->get_performance_associated_content($outcomeid, $COURSE->id);

        return json_encode(array(
            'header' => get_string('associatedcontent', 'outcome'),
            'body'   => $this->renderer->performance_associated_content($outcome, $content),
        ));
    }
}