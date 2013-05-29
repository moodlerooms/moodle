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

    public function get_unmapped_content() {
        global $DB, $PAGE;

        $componentlike = $DB->sql_like('area.component', ':component');
        $coursecontext = context_course::instance($this->courseid);
        $childcontexts = $coursecontext->get_child_contexts();
        $qcontexts     = array_merge(array($coursecontext->id), array_keys($childcontexts));

        list($contextinsql, $contextinparams) = $DB->get_in_or_equal($qcontexts, SQL_PARAMS_NAMED);
        $params = array_merge(array('courseid' => $this->courseid, 'component' => 'qtype_%'), $contextinparams);

        $unmapped = $DB->get_records_sql("
            SELECT q.id, q.name, COUNT(quiz.id) quizcount
              FROM {question} q
        INNER JOIN {question_categories} qc ON qc.id = q.category
         LEFT JOIN {outcome_areas} area ON $componentlike AND area.itemid = q.id
         LEFT JOIN {quiz_question_instances} qqi ON qqi.question = q.id
         LEFT JOIN {quiz} quiz ON quiz.id = qqi.quiz
             WHERE qc.contextid $contextinsql AND area.id IS NULL
          GROUP BY q.id", $params);

        $table        = new html_table();
        $table->head  = array(get_string('question'), get_string('quizzes', 'outcome'), '');
        $table->attributes['class'] = 'generaltable outcome-unmapped-questions';

        $rows = array();
        foreach ($unmapped as $question) {
            $returnurl = $PAGE->url->out_as_local_url();
            $maplink = html_writer::link(new moodle_url('/question/question.php', array('courseid' => $this->courseid,
                    'id' => $question->id, 'returnurl' => $returnurl)), get_string('map', 'outcome'));
            $rows[] = new html_table_row(array(format_text($question->name), $question->quizcount, $maplink));
        }

        $table->data = $rows;
        return $table;
    }
}
