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
 * Coverage information for activities
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @author    Sam Chaffee
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/classes/coverage/abstract.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcomesupport_mod_coverage extends outcome_coverage_abstract {
    public function get_unmapped_content_header() {
        return get_string('unmappedactivitiesandresources', 'outcome');
    }

    public function get_unmapped_content() {
        global $DB;

        $unmapped = $DB->get_records_sql("
            SELECT cm.id
              FROM {course_modules} cm
         LEFT JOIN {outcome_used_areas} used ON used.cmid = cm.id
             WHERE cm.course = :courseid AND used.id IS NULL",
        array('courseid' => $this->courseid));

        $table        = new html_table();
        $table->head  = array(get_string('section'), get_string('title', 'outcome'), '');
        $table->attributes['class'] = 'generaltable outcome-unmapped-activities';

        $rows = array();
        if (!empty($unmapped)) {
            $modinfo = get_fast_modinfo($this->courseid);

            foreach ($unmapped as $cm) {
                $cminfo = $modinfo->get_cm($cm->id);
                $sectionname = course_get_format($this->courseid)->get_section_name($cminfo->sectionnum);
                $mapit = html_writer::link(new moodle_url('/course/mod.php', array('update' => $cm->id)),
                        get_string('map', 'outcome'));
                $rows[] = array($sectionname, format_string($cminfo->name), $mapit);
            }
        }
        $table->data = $rows;
        return $table;
    }
}
