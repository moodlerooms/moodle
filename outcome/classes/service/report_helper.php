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
 * Internal Service: Report Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/support_factory.php');
require_once(dirname(__DIR__).'/model/area.php');
require_once(dirname(__DIR__).'/model/attempt.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_report_helper {
    /**
     * @var moodle_database
     */
    protected $db;

    /**
     * @var outcome_support_factory
     */
    protected $factory;

    /**
     * @param outcome_support_factory $factory
     * @param moodle_database $db
     */
    public function __construct(outcome_support_factory $factory = null, moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new outcome_support_factory();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
        $this->factory = $factory;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of cm_info instances and course_modules_completion record as the data
     */
    public function get_activity_completion_by_user($outcomeid, $userid, $courseid) {
        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, comp.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.completion > ? AND areas.area = ?
   LEFT OUTER JOIN {course_modules_completion} comp ON cm.id = comp.coursemoduleid AND comp.userid = ?
             WHERE o.id = ?
               AND cm.course = ?
        ', array(0, 'mod', $userid, $outcomeid, $courseid));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo   = $modinfo->get_cm($row->cmid);
            $progress = null;

            if (!empty($row->id)) {
                unset($row->cmid);
                $progress = $row;
            }
            $activities->attach($cminfo, $progress);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of cm_info instances and the user's grade_grades record as the data
     */
    public function get_activity_scales_by_user($outcomeid, $userid, $courseid) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');

        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, gi.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module
        INNER JOIN {grade_items} gi ON gi.itemtype = ?
                                   AND gi.iteminstance = cm.instance
                                   AND gi.itemmodule = mods.name
                                   AND gi.itemnumber = ?
                                   AND gi.gradetype = ?
             WHERE o.id = ?
               AND cm.course = ?
        ', array('mod', 0, GRADE_TYPE_SCALE, $outcomeid, $courseid));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo = $modinfo->get_cm($row->cmid);

            unset($row->cmid);
            $gradeitem = new grade_item($row);
            $grade     = $gradeitem->get_grade($userid, false);
            $grade->grade_item = $gradeitem;

            $activities->attach($cminfo, $grade);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of outcome_area_info_abstract instances and the outcome_model_attempt as the data
     */
    public function get_activity_attempts_by_user($outcomeid, $userid, $courseid) {
        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, a.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {outcome_attempts} a ON used.id = a.outcomeusedareaid AND a.userid = ?
        INNER JOIN (
                      SELECT outcomeusedareaid, userid, MAX(timemodified) timemodified
                        FROM {outcome_attempts}
                    GROUP BY outcomeusedareaid, userid
                    ORDER BY NULL
                   ) latest ON a.outcomeusedareaid = latest.outcomeusedareaid
                           AND a.userid = latest.userid
                           AND a.timemodified = latest.timemodified
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
             WHERE o.id = ?
               AND cm.course = ?
        ', array($userid, $outcomeid, $courseid));

        $modinfo  = get_fast_modinfo($courseid);
        $attempts = new SplObjectStorage();
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $area            = new outcome_model_area();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $areainfo = $this->factory->build_area_info($area, $cm);

            $attempt = new outcome_model_attempt();
            foreach ($row as $name => $value) {
                if (property_exists($attempt, $name)) {
                    $attempt->$name = $value;
                }
            }
            $attempts->attach($areainfo, $attempt);
        }
        return $attempts;
    }
}
