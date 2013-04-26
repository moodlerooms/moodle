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
 * Abstract form for report filtering
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/abstract_cached.php');
require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

abstract class outcome_form_abstract_filter extends outcome_form_abstract_cached {
    /**
     * Don't cache the submit button
     *
     * @return array
     */
    public function cache_blacklist() {
        return array_merge(
            parent::cache_blacklist(),
            array('submitbutton')
        );
    }

    /**
     * Standard filter header
     */
    public function define_filter_header() {
        $this->_form->addElement('header', 'filters', get_string('filters', 'outcome'));
    }

    /**
     * Standard filter button
     */
    public function define_filter_buttons() {
        $this->_form->addElement('submit', 'submitbutton', get_string('filter', 'outcome'));
    }

    /**
     * Add a select drop-down of course outcome sets.
     */
    public function define_outcome_sets() {
        global $COURSE;

        $repo = new outcome_model_outcome_set_repository();
        $outcomesets = $repo->find_used_by_course($COURSE->id, 's.name');

        $options = array();
        foreach ($outcomesets as $outcomeset) {
            $options[$outcomeset->id] = format_string($outcomeset->name);
        }
        reset($options);

        $mform = $this->_form;
        $mform->addElement('select', 'outcomesetid', get_string('outcomeset', 'outcome'), $options);
        $mform->setDefault('outcomesetid', key($options));
        $mform->setType('outcomesetid', PARAM_INT);
    }

    /**
     * Add a drop-down populated with course users
     *
     * @todo Add a next button
     */
    public function define_course_users() {
        global $PAGE;

        $users = get_enrolled_users($PAGE->context, '', 0, 'u.id, u.firstname, u.lastname');

        $options = array();
        foreach ($users as $user) {
            $options[$user->id] = fullname($user);
        }
        collatorlib::asort($options);
        reset($options);

        $mform = $this->_form;
        $mform->addElement('select', 'userid', get_string('user', 'outcome'), $options);
        $mform->setDefault('userid', key($options));
        $mform->setType('userid', PARAM_INT);
    }
}