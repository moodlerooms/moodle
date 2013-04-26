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
 * Outcome Mapping AJAX Controller
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
class outcome_controller_mapping_ajax extends outcome_controller_abstract {
    /**
     * @var outcome_model_outcome_repository
     */
    public $outcomes;

    /**
     * @var outcome_model_outcome_set_repository
     */
    public $outcomesets;

    /**
     * @var outcome_model_filter_repository
     */
    public $filters;

    public function init($action) {
        parent::init($action);

        require_once(dirname(__DIR__).'/model/filter_repository.php');
        require_once(dirname(__DIR__).'/model/outcome_repository.php');
        require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

        $this->filters     = new outcome_model_filter_repository();
        $this->outcomes    = new outcome_model_outcome_repository();
        $this->outcomesets = new outcome_model_outcome_set_repository();
    }

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
            case 'get_mapped_courses':
                require_capability('moodle/outcome:edit', $PAGE->context);
                break;
            case 'get_mappable_outcome_sets_menu':
            case 'get_outcome_set_filter_menus':
                require_capability('moodle/outcome:mapoutcomesets', $PAGE->context);
                break;
            case 'get_mappable_outcomes':
                require_capability('moodle/outcome:mapoutcomes', $PAGE->context);
                break;
            default:
                throw new coding_exception("Missing capability check for $action action");
        }
    }

    /**
     * Get a list of courses that are mapped to a particular outcome set
     *
     * @return string
     */
    public function get_mapped_courses_action() {
        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $outcomeset = $this->outcomesets->find($outcomesetid);
        $courses    = $this->outcomesets->fetch_mapped_courses($outcomeset);

        return json_encode(array(
            'header' => get_string('coursesmappedtox', 'outcome', format_string($outcomeset->name)),
            'body' => $this->renderer->mapped_courses_list($courses),
        ));
    }

    /**
     * Fetches a list of outcome sets that can be mapped to a course
     *
     * @return string
     */
    public function get_mappable_outcome_sets_menu_action() {

        $outcomesets = $this->outcomesets->find_by(array('deleted' => 0), 'name');

        if (empty($outcomesets)) {
            return json_encode(array());
        }
        $result = array(array(
            'id' => 0,
            'name' => get_string('chooseoutcomeset', 'outcome'),
        ));
        foreach ($outcomesets as $outcomeset) {
            $result[] = array(
                'id' => $outcomeset->id,
                'name' => format_string($outcomeset->name),
            );
        }
        return json_encode($result);
    }

    /**
     * Fetches two arrays: one with all of the possible education levels
     * and one with all of the possible subjects for a given outcome set.
     *
     * @return string
     */
    public function get_outcome_set_filter_menus_action() {

        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $outcomeset = $this->outcomesets->find($outcomesetid, MUST_EXIST);

        return json_encode(array(
            'edulevels' => $this->outcomesets->fetch_metadata_values($outcomeset, 'edulevels'),
            'subjects' => $this->outcomesets->fetch_metadata_values($outcomeset, 'subjects'),
        ));
    }

    /**
     * Fetches two arrays: one with all of the outcome sets
     * that are used in this course and one with all of the
     * mappable outcomes that belong to the outcome sets
     * in the first list.
     *
     * @return string
     */
    public function get_mappable_outcomes_action() {
        global $PAGE;

        /** @var $context context_course */
        $context = $PAGE->context->get_course_context();

        if ($context->instanceid == SITEID) {
            $outcomesets = $this->outcomesets->find_by(array('deleted' => 0));
            $outcomes    = $this->outcomes->find_by(array('deleted' => 0, 'assessable' => 1));
        } else {
            $filters     = $this->filters->find_by_course($context->instanceid);
            $outcomesets = $this->outcomesets->find_used_by_course($context->instanceid);

            $outcomes = array();
            foreach ($filters as $filter) {
                $outcomes = array_merge($outcomes, $this->outcomes->find_by_filter($filter));
            }
        }

        return json_encode(array(
            'outcomeSetList' => array_values($outcomesets),
            'outcomeList' => array_values($outcomes),
        ));
    }
}