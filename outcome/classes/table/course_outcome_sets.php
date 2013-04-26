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
 * View course outcome sets and related information
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/tablelib.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_table_course_outcome_sets extends table_sql {
    public function __construct() {
        global $COURSE;

        parent::__construct(__CLASS__);

        $this->set_attribute('id', 'course-outcome-sets');
        $this->define_columns(array('name', 'coverage', 'reports'));
        $this->define_headers(array(
            get_string('fullname', 'outcome'),
            get_string('coverage', 'outcome'),
            get_string('reports', 'outcome'),
        ));
        $this->no_sorting('coverage');
        $this->no_sorting('reports');
        $this->collapsible(false);

        $from = '{outcome_sets} s INNER JOIN {outcome_used_sets} us ON s.id = us.outcomesetid';
        $this->set_sql('s.id, s.name', $from, 'us.courseid = ? AND s.deleted = ?', array($COURSE->id, 0));
    }

    public function col_name($row) {
        return format_string($row->name);
    }

    public function col_coverage($row) {
        return 'todo';
    }

    public function col_reports($row) {
        global $PAGE;

        $reports = array();
        if (has_capability('moodle/grade:edit', $PAGE->context)) {
            $reports[] = $this->_report_link($row, 'report:marking');
        }
        return implode(' ', $reports);
    }

    protected function _report_link($row, $identifier, $action = null) {
        if (is_null($action)) {
            $action = str_replace(':', '_', $identifier);
        }
        /** @var $url moodle_url */
        $url = clone($this->baseurl);
        $url->params(array(
            'action'            => $action,
            'forceoutcomesetid' => $row->id,
        ));
        return html_writer::link($url, get_string($identifier, 'outcome'),
            array('title' => get_string($identifier.'x', 'outcome', format_string($row->name))));
    }
}