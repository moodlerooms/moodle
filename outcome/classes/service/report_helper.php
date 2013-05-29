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

require_once(dirname(__DIR__).'/factory.php');
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
     * @var outcome_factory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $modarchetypes;

    /**
     * @param outcome_factory $factory
     * @param moodle_database $db
     */
    public function __construct(outcome_factory $factory = null, moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new outcome_factory();
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
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Full of cm_info instances and course_modules_completion record as the data
     */
    public function get_activity_completion_by_course($outcomeid, $courseid, $groupid = 0) {
        $params = array(
            'completionstate' => 1,
            'completion' => 0,
            'area' => 'mod',
            'outcomeid' => $outcomeid,
            'courseid' => $courseid
        );

        list($esql, $eparams) = $this->get_gradebook_users_sql($groupid);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid,
                   ((SUM(CASE WHEN comp.completionstate >= :completionstate THEN 1 ELSE 0 END) / COUNT(cm.id)) * 100) completion
              FROM {outcome} o
                   $esql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.completion > :completion AND areas.area = :area
   LEFT OUTER JOIN {course_modules_completion} comp ON cm.id = comp.coursemoduleid AND comp.userid = u.id

             WHERE o.id = :outcomeid
               AND cm.course = :courseid
          GROUP BY cm.id
", array_merge($params, $eparams));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo   = $modinfo->get_cm($row->cmid);

            unset($row->cmid);
            $progress = $row;

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

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @return cm_info[]
     */
    public function get_performance_associated_content($outcomeid, $courseid) {
        $params = array(
            'outcomeid' => $outcomeid,
            'courseid' => $courseid,
        );

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
             WHERE o.id = :outcomeid AND cm.course = :courseid
          GROUP BY cm.id
        ", $params);

        $content = array();
        $modinfo  = get_fast_modinfo($courseid);
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $content[$cm->id] = $cm;
        }
        return $content;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Keyed by outcome_area_info_abstract instances and the aggregated attempt info as the data
     */
    public function get_activity_attempts_by_course($outcomeid, $courseid, $groupid = 0) {
        $params = array(
            'outcomeid' => $outcomeid,
            'courseid' => $courseid,
        );

        list($esql, $eparams) = $this->get_gradebook_users_sql($groupid);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid, areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid,
                   ((SUM(a.rawgrade) - SUM(a.mingrade)) / (SUM(a.maxgrade) - SUM(a.mingrade)) * 100) avegrade,
                   AVG(a.rawgrade - a.mingrade) points, AVG(a.maxgrade - a.mingrade) possiblepoints
              FROM {outcome} o
              $esql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {outcome_attempts} a ON used.id = a.outcomeusedareaid AND a.userid = u.id
        INNER JOIN (
                      SELECT outcomeusedareaid, userid, MAX(timemodified) timemodified
                        FROM {outcome_attempts}
                    GROUP BY outcomeusedareaid, userid
                    ORDER BY NULL
                   ) latest ON a.outcomeusedareaid = latest.outcomeusedareaid
                           AND a.userid = latest.userid
                           AND a.timemodified = latest.timemodified
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
             WHERE o.id = :outcomeid
               AND cm.course = :courseid
          GROUP BY areas.id
        ", array_merge($params, $eparams));

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

            $aggattemptinfo = array(
                'avegrade' => $row->avegrade,
                'points' => $row->points,
                'possiblepoints' => $row->possiblepoints,
            );
            $attempts->attach($areainfo, $aggattemptinfo);
        }
        return $attempts;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Keyed by cm_info instances and the avg formatted scale as the data.
     */
    public function get_activity_scales_by_course($outcomeid, $courseid, $groupid) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');
        list($gbusersql, $gbuserparams) = $this->get_gradebook_users_sql($groupid);
        $params = array(
            'itemtype' => 'mod',
            'itemnumber' => 0,
            'gradetype' => GRADE_TYPE_SCALE,
            'outcomeid' => $outcomeid,
            'courseid' => $courseid
        );
        $params = array_merge($params, $gbuserparams);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid, AVG(gg.finalgrade) avgscale, gi.*
              FROM {outcome} o
              $gbusersql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module
        INNER JOIN {grade_items} gi ON gi.itemtype = :itemtype
                                   AND gi.iteminstance = cm.instance
                                   AND gi.itemmodule = mods.name
                                   AND gi.itemnumber = :itemnumber
                                   AND gi.gradetype = :gradetype
         LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
             WHERE o.id = :outcomeid AND cm.course = :courseid
          GROUP BY cm.id
        ", $params);

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo = $modinfo->get_cm($row->cmid);
            $avgscale = $row->avgscale;
            unset($row->cmid, $row->avgscale);
            $gradeitem = new grade_item($row);

            if (!is_null($avgscale)) {
                $avgscale = round($avgscale);
            }
            $scalevalue = grade_format_gradevalue($avgscale, $gradeitem);

            $activities->attach($cminfo, $scalevalue);
        }
        $rs->close();

        return $activities;
    }

    public function get_gradebook_users_sql($groupid = 0) {
        global $CFG, $DB, $PAGE;

        // Ensure we have course context.
        $context = context_course::instance($PAGE->course->id);

        list($esql, $eparams) = get_enrolled_sql($context, '', $groupid);
        list($gsql, $rparams) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr');
        list($csql, $cparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');

        $sql = "INNER JOIN {user} u
                INNER JOIN ($esql) e ON e.id = u.id
                INNER JOIN (
                     SELECT DISTINCT ra.userid
                       FROM {role_assignments} ra
                      WHERE ra.roleid $gsql
                        AND ra.contextid $csql
                           ) ra ON ra.userid = u.id";

        return array($sql, array_merge($eparams, $rparams, $cparams));
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return outcome_area_info_interface[]
     */
    public function get_coverage_activities($outcomeid, $courseid) {
        return $this->get_coverage_mods($outcomeid, $courseid, MOD_ARCHETYPE_OTHER);
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return outcome_area_info_interface[]
     */
    public function get_coverage_resources($outcomeid, $courseid) {
        return $this->get_coverage_mods($outcomeid, $courseid, MOD_ARCHETYPE_RESOURCE);
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return SplObjectStorage Keyed by outcome_area_info_interface instances and if the question is used by an activity as data.
     */
    public function get_coverage_questions($outcomeid, $courseid) {
        $coursecontext    = context_course::instance($courseid);
        $componentlikesql = $this->db->sql_like('areas.component', ':componentqtype');
        $contextlikesql   = $this->db->sql_like('ctx.path', ':ctxpath');

        $rs = $this->db->get_recordset_sql("
            SELECT areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, MAX(cm.id) cmid
              FROM {outcome_area_outcomes} ao
        INNER JOIN {outcome} o ON ao.outcomeid = o.id
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid AND areas.area = :qtype
                                         AND $componentlikesql
        INNER JOIN {question} q ON q.id = areas.itemid
        INNER JOIN {question_categories} qc ON qc.id = q.category
        INNER JOIN {context} ctx ON (ctx.id = qc.contextid AND (ctx.id = :contextid OR $contextlikesql))
         LEFT JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
         LEFT JOIN {course_modules} cm ON cm.id = used.cmid AND cm.course = :courseid
             WHERE ao.outcomeid = :outcomeid
          GROUP BY areas.id
        ", array('componentqtype' => 'qtype_%', 'qtype' => 'qtype', 'outcomeid' => $outcomeid,
                 'courseid' => $courseid, 'contextid' => $coursecontext->id,
                 'ctxpath' => $coursecontext->path.'/%'));

        $questions = new SplObjectStorage();
        foreach ($rs as $row) {
            $area            = new outcome_model_area();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $questions->attach($this->factory->build_area_info($area), !is_null($row->cmid));
        }
        return $questions;
    }

    /**
     * @param int $archetype One of MOD_ARCHETYPE_OTHER or MOD_ARCHETYPE_RESOURCE
     * @return array
     */
    public function get_mod_archetypes($archetype) {
        if (empty($this->modarchetypes)) {
            $this->modarchetypes = $this->mods_by_archetype();
        }
        return $this->modarchetypes[$archetype];
    }

    /**
     * @return array
     */
    protected function mods_by_archetype() {
        global $COURSE;
        $modmetadata = get_module_metadata($COURSE, get_module_types_names());

        $modsbyarchetype = array(MOD_ARCHETYPE_OTHER => array(), MOD_ARCHETYPE_RESOURCE => array());

        foreach ($modmetadata as $metadata) {
            if (isset($metadata->archetype) and isset($modsbyarchetype[$metadata->archetype])) {
                $modsbyarchetype[$metadata->archetype][] = $metadata->name;
            }
        }

        return $modsbyarchetype;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $modtype
     * @return outcome_area_info_interface[]
     */
    protected function get_coverage_mods($outcomeid, $courseid, $modtype) {
        $activitymods = $this->get_mod_archetypes($modtype);
        list($activitiesinsql, $activitiesinparmas) = $this->db->get_in_or_equal($activitymods, SQL_PARAMS_NAMED);

        $params = array_merge(array(
            'outcomeid' => $outcomeid,
            'courseid' => $courseid,
        ), $activitiesinparmas);

        $rs = $this->db->get_recordset_sql("
            SELECT areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, cm.id cmid
              FROM {outcome_area_outcomes} ao
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.name $activitiesinsql
             WHERE ao.outcomeid = :outcomeid AND cm.course = :courseid
        ", $params);

        $modinfo  = get_fast_modinfo($courseid);
        $areainfos = array();
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $area            = new outcome_model_area();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $areainfos[] = $this->factory->build_area_info($area, $cm);
        }

        return $areainfos;
    }
}
