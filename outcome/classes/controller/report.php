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
 * Outcome Reports Controller
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
class outcome_controller_report extends outcome_controller_abstract {
    /**
     * @var outcome_service_award
     */
    protected $awardservice;

    /**
     * @var outcome_service_mark_helper
     */
    protected $markhelper;

    /**
     * @var outcome_service_report_helper
     */
    protected $reporthelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     * @throws coding_exception
     */
    public function require_capability($action) {
        global $PAGE;

        switch($action) {
            case 'default':
            case 'report_marking':
            case 'report_course_performance':
            case 'report_course_coverage':
                require_capability('moodle/grade:edit', $PAGE->context);
                break;
            case 'report_course_unmapped':
                require_capability('moodle/outcome:mapoutcomes', $PAGE->context);
                break;
            default:
                throw new coding_exception("Missing capability check for $action action");
        }
    }

    public function init($action) {
        parent::init($action);

        require_once(dirname(__DIR__).'/service/award.php');
        require_once(dirname(__DIR__).'/service/mark_helper.php');
        require_once(dirname(__DIR__).'/service/report_helper.php');

        $this->awardservice = new outcome_service_award();
        $this->reporthelper = new outcome_service_report_helper();
        $this->markhelper   = new outcome_service_mark_helper();
    }

    /**
     * Overview of course outcome sets and their reports, etc.
     */
    public function default_action() {
        global $PAGE, $COURSE, $OUTPUT;

        require_once(dirname(__DIR__).'/table/course_outcome_sets.php');

        add_to_log($COURSE->id, 'outcome', 'view course outcome sets', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomesetsforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('outcomesets', 'outcome'));

        $table = new outcome_table_course_outcome_sets();
        $table->define_baseurl($this->new_url());

        echo $OUTPUT->heading(get_string('outcomesets', 'outcome'));
        $this->renderer->course_outcome_sets($table);
    }

    /**
     * Outcome marking table.
     */
    public function report_marking_action() {
        global $PAGE, $COURSE, $USER, $OUTPUT;

        require_once(dirname(__DIR__).'/table/marking.php');
        require_once(dirname(__DIR__).'/form/marking_filter.php');

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view outcome marking', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('completionmarkingforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:marking', 'outcome'));

        $mform = new outcome_form_marking_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        if (optional_param('savemarkings', 0, PARAM_BOOL)) {
            require_sesskey();

            $outcomeids    = optional_param_array('outcomeids', array(), PARAM_INT);
            $markids       = optional_param_array('markids', array(), PARAM_INT);
            $earnedmarkids = optional_param_array('earnedmarkids', array(), PARAM_INT);
            $userid        = $mform->get_cached_value('userid');

            $updated = array_merge(
                $this->markhelper->mark_outcomes_as_earned($COURSE->id, $USER->id, $userid, $outcomeids),
                $this->markhelper->update_mark_earned($USER->id, $markids, $earnedmarkids)
            );
            $errors = $this->awardservice->update_awards_by_marks($updated);
            foreach ($errors as $error) {
                $this->flashmessages->add_string($error);
            }
            $this->flashmessages->good('changessaved', null, 'moodle');
            redirect($this->new_url());
        }

        $table = new outcome_table_marking($mform);
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:marking', 'outcome'));
        $this->renderer->outcome_marking($mform, $table);
    }

    /**
     * Outcomes course performance table.
     */
    public function report_course_performance_action() {
        global $PAGE, $COURSE, $OUTPUT;

        require_once(dirname(__DIR__).'/table/course_performance.php');
        require_once(dirname(__DIR__).'/form/course_performance_filter.php');

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view course outcome performance', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomeperformanceforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_performance', 'outcome'));

        $mform = new outcome_form_course_performance_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        $table = new outcome_table_course_performance($mform, $this->reporthelper);
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:course_performance', 'outcome'));
        $this->renderer->outcome_course_performance($mform, $table);
    }

    /**
     * Outcomes course coverage table.
     */
    public function report_course_coverage_action() {
        global $PAGE, $COURSE, $OUTPUT;

        require_once(dirname(__DIR__).'/mod_archetype.php');
        require_once(dirname(__DIR__).'/table/course_coverage.php');
        require_once(dirname(__DIR__).'/form/course_coverage_filter.php');

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view course outcome coverage', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomecoverageforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_coverage', 'outcome'));

        $mform = new outcome_form_course_coverage_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        $table = new outcome_table_course_coverage($mform, new outcome_mod_archetype());
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:course_coverage', 'outcome'));
        $this->renderer->outcome_course_coverage($mform, $table);
    }

    /**
     * Outcomes course unmapped content tables.
     */
    public function report_course_unmapped_action() {
        global $PAGE, $COURSE, $OUTPUT;

        require_once(dirname(__DIR__).'/factory.php');

        add_to_log($COURSE->id, 'outcome', 'view course outcome unmapped', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomeunmappedforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_unmapped', 'outcome'));

        $factory = new outcome_factory();
        $coverages = $factory->build_coverages();

        echo $OUTPUT->heading(get_string('report:course_unmapped', 'outcome'));
        foreach ($coverages as $coverage) {
            $coverage->set_courseid($COURSE->id);
            $this->renderer->outcome_course_unmapped($coverage);
        }
    }
}