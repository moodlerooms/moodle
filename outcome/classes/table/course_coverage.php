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
 * Outcome course overage
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @uathor    Sam Chaffee
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/tablelib.php');
require_once(dirname(__DIR__).'/form/course_coverage_filter.php');
require_once(dirname(__DIR__).'/model/outcome_repository.php');
require_once(dirname(__DIR__).'/model/filter_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @author    Sam Chaffee
 */
class outcome_table_course_coverage extends table_sql {
    /**
     * @var outcome_service_report_helper
     */
    protected $reporthelper;

    public function __construct(outcome_form_course_coverage_filter $mform, outcome_service_report_helper $reporthelper) {
        parent::__construct(__CLASS__);

        $this->reporthelper = $reporthelper;

        $columns = array('docnum', 'description', 'resources', 'activities', 'questions');

        $this->set_attribute('id', 'outcome-course-coverage');
        $this->set_attribute('class', 'generaltable generalbox outcome-report-table');
        $this->define_columns($columns);
        $this->sortable(true, 'description');
        $this->collapsible(false);

        $this->generate_sql($mform);
    }

    protected function generate_sql(outcome_form_course_coverage_filter $mform) {
        global $COURSE, $DB;

        $context = context_course::instance($COURSE->id);

        $activitymods = $this->reporthelper->get_mod_archetypes(MOD_ARCHETYPE_OTHER);
        $resourcemods = $this->reporthelper->get_mod_archetypes(MOD_ARCHETYPE_RESOURCE);

        list($activitiesinsql, $activitiesinparmas) = $DB->get_in_or_equal($activitymods, SQL_PARAMS_NAMED);
        list($resourcesinsql, $resourcesinparams) = $DB->get_in_or_equal($resourcemods, SQL_PARAMS_NAMED);
        $componentlikesql = $DB->sql_like('areas3.component', ':componentqtype');
        $contextlikesql   = $DB->sql_like('ctx.path', ':ctxpath');

        $fields = array('o.id', 'o.docnum', 'o.description', 'resources', 'activities', 'questions', 'questionsused');

        $outcomerepo = new outcome_model_outcome_repository();
        $filterrepo  = new outcome_model_filter_repository();

        $filter = $filterrepo->find_one_by(array(
            'outcomesetid' => $mform->get_cached_value('outcomesetid'),
            'courseid'     => $COURSE->id,
        ));
        list($filtersql, $params) = $outcomerepo->filter_to_sql($filter);

        $from = "
            {outcome} o
         $filtersql->join
          LEFT JOIN (
                    SELECT o.id, COUNT(used.id) resources
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
                INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
                INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.course = :courseid
                INNER JOIN {modules} mods ON mods.id = cm.module AND mods.name $resourcesinsql
             GROUP BY o.id
          ) t1
                ON t1.id = o.id

          LEFT JOIN (
                    SELECT o.id, COUNT(DISTINCT cm2.id) activities
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas2 ON areas2.id = ao.outcomeareaid
                INNER JOIN {outcome_used_areas} used2 ON areas2.id = used2.outcomeareaid
                INNER JOIN {course_modules} cm2 ON cm2.id = used2.cmid AND cm2.course = :courseid2
                INNER JOIN {modules} mods2 ON mods2.id = cm2.module AND mods2.name $activitiesinsql
                  GROUP BY o.id
          ) t2
                ON t2.id = o.id

          LEFT JOIN (
                    SELECT o.id, COUNT(DISTINCT ao.id) questions, COUNT(DISTINCT cm3.id) questionsused
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas3 ON areas3.id = ao.outcomeareaid AND areas3.area = :typequestion
                                                     AND $componentlikesql
                INNER JOIN {question} q ON q.id = areas3.itemid
                INNER JOIN {question_categories} qc ON qc.id = q.category
                INNER JOIN {context} ctx ON (ctx.id = qc.contextid AND (ctx.id = :contextid OR $contextlikesql))
                 LEFT JOIN {outcome_used_areas} used3 ON areas3.id = used3.outcomeareaid
                 LEFT JOIN {course_modules} cm3 ON cm3.id = used3.cmid AND cm3.course = :courseid3
                  GROUP BY o.id
            ) t3
                ON t3.id = o.id
        ";

        $where = "$filtersql->where AND o.deleted = :deleted AND o.assessable = :assessable GROUP BY o.id";

        $params['courseid'] = $params['courseid2'] = $params['courseid3'] = $COURSE->id;
        $params['deleted']       = 0;
        $params['assessable'] = 1;
        $params['componentqtype'] = 'qtype_%';
        $params['typequestion']  = 'qtype';
        $params['contextid']  = $context->id;
        $params['ctxpath']  = $context->path.'/%';

        $params = array_merge($params, $activitiesinparmas, $resourcesinparams);

        $this->set_sql(implode(', ', $fields), $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT o.id FROM $from WHERE $where) count", $params);
    }

    /**
     * Based on columns, define default headers
     *
     * @param array $columns
     */
    function define_columns($columns) {
        parent::define_columns($columns);

        $allheaders = array(
            'docnum'      => get_string('id', 'outcome'),
            'description' => get_string('outcome', 'outcome'),
            'resources'  => get_string('resources', 'outcome'),
            'activities'    => get_string('activities', 'outcome'),
            'questions'      => get_string('questions', 'outcome'),
        );

        $headers = array();
        foreach ($columns as $column) {
            if (array_key_exists($column, $allheaders)) {
                $headers[] = $allheaders[$column];
            }
        }
        $this->define_headers($headers);
    }

    function wrap_html_finish() {
        echo html_writer::tag('div', '*'.get_string('questionbanknote', 'outcome'),
                array('id' => 'outcome-coverage-notes'));
    }

    protected function panel_link($row, $action, $text) {
        if ($text == '-') {
            return $text; // Lame check, but don't link "empty" data.
        }
        return html_writer::link('#', $text, array(
            'class'                  => 'dynamic-panel',
            'data-request-action'    => $action,
            'data-request-outcomeid' => $row->id,
        ));
    }

    public function col_docnum($row) {
        return format_string($row->docnum);
    }

    public function col_description($row) {
        return $this->format_text($row->description);
    }

    public function col_resources($row) {
        if (is_null($row->resources)) {
            $row->resources = '-';
        }
        return $this->panel_link($row, 'course_coverage_resources', $row->resources);
    }

    public function col_activities($row) {
        if (is_null($row->activities)) {
            $row->activities = '-';
        }
        return $this->panel_link($row, 'course_coverage_activities', $row->activities);
    }

    public function col_questions($row) {
        if (is_null($row->questions)) {
            $questions = '-';
        } else {
            $questions = $this->panel_link($row, 'course_coverage_questions', $row->questions);
            if ($row->questionsused < $row->questions) {
                $questions .= '*';
            }
        }
        return $questions;
    }

    public function finish_html() {
        global $PAGE;

        parent::finish_html();

        if ($this->started_output) {
            $PAGE->requires->yui_module(
                'moodle-core_outcome-dynamicpanel',
                'M.core_outcome.init_dynamicpanel',
                array(array(
                    'contextId' => $PAGE->context->id,
                    'delegateSelector' => '#outcome-course-coverage',
                    'actionSelector' => 'a.dynamic-panel',
                ))
            );
            $PAGE->requires->strings_for_js(array('close'), 'outcome');
        }
    }
}