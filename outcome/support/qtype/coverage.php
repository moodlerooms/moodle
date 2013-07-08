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
 * Coverage information for questions
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
 * @author    Sam Chaffee
 */
class outcomesupport_qtype_coverage extends outcome_coverage_abstract {
    public function get_unmapped_content_header() {
        return get_string('unmappedquestions', 'outcome');
    }

    protected function question_context_sql() {
        global $DB;

        $systemcontext = context_system::instance();
        $coursecontext = context_course::instance($this->courseid);
        $childcontexts = $coursecontext->get_child_contexts();
        $qcontexts     = array_merge(array($systemcontext->id, $coursecontext->id), array_keys($childcontexts));

        return $DB->get_in_or_equal($qcontexts, SQL_PARAMS_NAMED);
    }

    /**
     * Get a list of questions are not mapped
     * to any mappable outcomes.
     *
     * @return array
     */
    protected function get_unmapped_questions() {
        global $DB;

        $systemcontext = context_system::instance();
        $componentlike = $DB->sql_like('area.component', ':component');
        list($contextinsql, $params) = $this->question_context_sql();
        $params['component'] = 'qtype_%';
        $params['courseid'] = $this->courseid;
        $params['systemctx1'] = $systemcontext->id;
        $params['systemctx2'] = $systemcontext->id;

        $unmapped = $DB->get_records_sql("
            SELECT q.id, q.name, COUNT(quiz.id) quizcount
              FROM {question} q
        INNER JOIN {question_categories} qc ON qc.id = q.category
         LEFT JOIN {outcome_areas} area ON $componentlike AND area.itemid = q.id
         LEFT JOIN {quiz_question_instances} qqi ON qqi.question = q.id
         LEFT JOIN {quiz} quiz ON quiz.id = qqi.quiz AND quiz.course = :courseid
             WHERE qc.contextid $contextinsql AND area.id IS NULL
               AND (qc.contextid != :systemctx1 OR (qc.contextid = :systemctx2 AND quiz.id IS NOT NULL))
          GROUP BY q.id", $params);

        return $this->add_invalid_mapped_questions($unmapped);
    }

    /**
     * This adds questions that are mapped to outcomes, but
     * those outcomes are not associated to the course.
     *
     * @param array $unmapped
     * @return array
     */
    protected function add_invalid_mapped_questions($unmapped) {
        global $DB;

        $systemcontext = context_system::instance();
        $outcomesql = $this->course_outcomes_sql();
        if (empty($outcomesql)) {
            return $unmapped;
        }
        list($sql, $params) = $outcomesql;
        $componentlike = $DB->sql_like('area.component', ':component');
        list($contextinsql, $contextinparams) = $this->question_context_sql();

        $params = array_merge(array(
            'component'  => 'qtype_%',
            'courseid'   => $this->courseid,
            'systemctx1' => $systemcontext->id,
            'systemctx2' => $systemcontext->id
        ), $contextinparams, $params);

        $mapinfos = $DB->get_records_sql("
            SELECT q.id, q.name, COUNT(outcomes.id) valid, COUNT(quiz.id) quizcount
              FROM {question} q
        INNER JOIN {question_categories} qc ON qc.id = q.category
        INNER JOIN {outcome_areas} area ON $componentlike AND area.itemid = q.id
        INNER JOIN {outcome_area_outcomes} ao ON area.id = ao.outcomeareaid
         LEFT JOIN {quiz_question_instances} qqi ON qqi.question = q.id
         LEFT JOIN {quiz} quiz ON quiz.id = qqi.quiz AND quiz.course = :courseid
         LEFT JOIN ($sql) outcomes ON outcomes.id = ao.outcomeid
             WHERE qc.contextid $contextinsql
               AND (qc.contextid != :systemctx1 OR (qc.contextid = :systemctx2 AND quiz.id IS NOT NULL))
          GROUP BY q.id", $params);

        foreach ($mapinfos as $mapinfo) {
            if (empty($mapinfo->valid) and !array_key_exists($mapinfo->id, $unmapped)) {
                $unmapped[$mapinfo->id] = $mapinfo;
            }
        }
        return $unmapped;
    }

    public function get_unmapped_content() {
        global $PAGE;

        $table        = new html_table();
        $table->head  = array(get_string('question'), get_string('quizzes', 'outcome'), '');
        $table->data  = array();
        $table->attributes['class'] = 'generaltable outcome-unmapped-questions';

        $unmapped = $this->get_unmapped_questions();
        foreach ($unmapped as $question) {
            $returnurl = $PAGE->url->out_as_local_url();
            $maplink = html_writer::link(new moodle_url('/question/question.php', array('courseid' => $this->courseid,
                    'id' => $question->id, 'returnurl' => $returnurl)), get_string('map', 'outcome'));
            $table->data[] = new html_table_row(array(format_text($question->name), $question->quizcount, $maplink));
        }
        return $table;
    }
}
