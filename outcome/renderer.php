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
 * Outcome Renderer
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class core_outcome_renderer extends plugin_renderer_base {
    /**
     * Get outcome description with doc num pre-appended if available.
     *
     * @param outcome_model_outcome $outcome
     * @param string|null $tag Optionally wrap the description with this tag
     * @return string
     */
    public function outcome_display(outcome_model_outcome $outcome, $tag = null) {
        $output = format_text($outcome->description, FORMAT_MOODLE, array(
            'para' => false,
        ));
        if (!empty($outcome->docnum)) {
            $output = format_string($outcome->docnum) . ' - ' . $output;
        }
        if (!empty($tag)) {
            return html_writer::tag($tag, $output, array('class' => 'outcome-description'));
        }
        return $output;
    }

    /**
     * Helper method to sort an array of cm_info classes
     *
     * @param cm_info $a
     * @param cm_info $b
     * @return int
     */
    protected function sort_cms(cm_info $a, cm_info $b) {
        return strnatcasecmp($a->name, $b->name);
    }

    /**
     * Helper method to sort an array of html_table_row classes
     * based on content of first cell
     *
     * @param html_table_row $a
     * @param html_table_row $b
     * @return int
     */
    protected function sort_html_rows(html_table_row $a, html_table_row $b) {
        return strnatcasecmp($a->cells[0]->text, $b->cells[0]->text);
    }

    /**
     * Render the administration of outcome sets
     *
     * @param outcome_table_manage_outcome_sets $table
     */
    public function outcome_sets_admin(outcome_table_manage_outcome_sets $table) {
        global $PAGE;

        $editurl = $PAGE->url;
        $editurl->param('action', 'outcomeset_edit');

        echo html_writer::link($editurl, get_string('addnewoutcomeset', 'outcome'));

        $table->out(50, false);
    }

    /**
     * Render course outcome sets
     *
     * @param outcome_table_course_outcome_sets $table
     */
    public function course_outcome_sets(outcome_table_course_outcome_sets $table) {
        $table->out(50, false);
    }

    /**
     * Render outcome marking table
     *
     * @param moodleform $mform
     * @param outcome_table_marking $table
     */
    public function outcome_marking(moodleform $mform, outcome_table_marking $table) {
        $mform->display();
        $table->out(50, false);
    }

    /**
     * @param object[] $courses
     * @return string
     */
    public function mapped_courses_list($courses) {
        $items = array();
        foreach ($courses as $course) {
            $url   = new moodle_url('/course/view.php', array('id' => $course->id));
            $title = get_string('viewcoursex', 'outcome', format_string($course->fullname));
            $text  = '('.format_string($course->shortname).') '.format_string($course->fullname);

            $items[] = html_writer::link($url, $text, array('title' => $title));
        }
        return html_writer::alist($items);
    }

    /**
     * @param object $user
     * @param outcome_model_outcome $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function user_activity_completion($user, outcome_model_outcome $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('completion', 'outcome'));
            $table->align = array(null, null, 'center');
            $table->attributes['class'] = 'generaltable outcome-user-activity-completion';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                $progress = $activities->getInfo();

                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    $this->activity_completion_icon($user, $activity, $progress),
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param object $user
     * @param cm_info $activity
     * @param null|object $progress
     * @return string
     */
    public function activity_completion_icon($user, cm_info $activity, $progress = null) {
        // Get progress information and state.
        if (!empty($progress)) {
            $state = $progress->completionstate;
            $date  = userdate($progress->timemodified);
        } else {
            $state = COMPLETION_INCOMPLETE;
            $date = '';
        }
        // Work out how it corresponds to an icon.
        switch ($state) {
            case COMPLETION_COMPLETE:
                $completiontype = 'y';
                break;
            case COMPLETION_COMPLETE_PASS:
                $completiontype = 'pass';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $completiontype = 'fail';
                break;
            case COMPLETION_INCOMPLETE:
            default:
                $completiontype = 'n';
        }

        $completionicon = 'completion-'.
            ($activity->completion == COMPLETION_TRACKING_AUTOMATIC ? 'auto' : 'manual').
            '-'.$completiontype;

        $modcontext   = context_module::instance($activity->id);
        $describe     = get_string('completion-'.$completiontype, 'completion');
        $fulldescribe = get_string('progress-title', 'completion', array(
            'state'    => $describe,
            'date'     => $date,
            'user'     => fullname($user),
            'activity' => format_string($activity->name, true, array('context' => $modcontext)),
        ));

        return $this->output->pix_icon('i/'.$completionicon, $describe, 'moodle', array('title' => $fulldescribe));
    }

    /**
     * @param outcome_model_outcome $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function user_activity_scale_grades(outcome_model_outcome $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('scalevalue', 'outcome'));
            $table->attributes['class'] = 'generaltable outcome-user-scales-completion';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                /** @var grade_grade $grade */
                $grade = $activities->getInfo();

                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    grade_format_gradevalue($grade->finalgrade, $grade->grade_item),
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model_outcome $outcome
     * @param SplObjectStorage $attempts
     * @return string
     */
    public function user_activity_attempt_grades(outcome_model_outcome $outcome, SplObjectStorage $attempts) {
        /** @var outcome_area_info_interface $areainfo */
        /** @var cm_info[] $cms */

        $output = $this->outcome_display($outcome, 'p');

        if ($attempts->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->data  = array();
            $table->head  = array(
                get_string('content', 'outcome'),
                get_string('type', 'outcome'),
                get_string('grade'),
                get_string('pointvalue', 'outcome'),
            );
            $table->attributes['class'] = 'generaltable outcome-user-scales-completion';

            // We group by activity, so get all possible activities and sort them.
            $cms = array();
            foreach ($attempts as $areainfo) {
                $cms[$areainfo->get_cm()->id] = $areainfo->get_cm();
            }
            usort($cms, array($this, 'sort_cms'));

            // Now add each activity and any associated attempts to the table.
            foreach ($cms as $cm) {
                $table->data = array_merge($table->data, $this->activity_attempts_to_rows($cm, $attempts));
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * Given an activity, find all attempts and build html_table_row instances
     *
     * @param cm_info $cm
     * @param SplObjectStorage $attempts
     * @return html_table_row[]
     */
    protected function activity_attempts_to_rows(cm_info $cm, SplObjectStorage $attempts) {
        $rows = array();

        // We always add activity row, regardless if there is grade info or not.
        $cmrow = new html_table_row(array(
            format_string($cm->name),
            $cm->get_module_type_name(),
        ));
        $cmrow->attributes['class'] = 'outcome-activity-attempt';

        $attempts->rewind();
        while ($attempts->valid()) {
            /** @var outcome_area_info_interface $areainfo */
            $areainfo = $attempts->current();
            /** @var outcome_model_attempt $attempt */
            $attempt = $attempts->getInfo();

            if ($areainfo->get_cm()->id == $cm->id) {
                if ($areainfo->get_area()->area == 'mod') {
                    // Found the activity attempt, add attempt data to activity row.
                    $row = $cmrow;
                } else {
                    $row = new html_table_row(array($areainfo->get_item_name(), $areainfo->get_area_name()));
                    $row->attributes['class'] = 'outcome-activity-content-attempt';
                    $rows[] = $row;
                }
                $row->cells[] = new html_table_cell(round($attempt->percentgrade).'%');
                $row->cells[] = new html_table_cell(round($attempt->rawgrade - $attempt->mingrade).'/'.
                    round($attempt->maxgrade - $attempt->mingrade));
            }
            $attempts->next();
        }
        // Sort all of the rows underneath the activity.
        usort($rows, array($this, 'sort_html_rows'));

        // Ensure the activity row has enough cells.
        $cmrow->cells = array_pad($cmrow->cells, 4, new html_table_cell('-'));

        // Add the activity row to the front.
        array_unshift($rows, $cmrow);

        return $rows;
    }

    /**
     * Render flash messages
     *
     * @param outcome_output_flash_messages $flashmessages
     * @return string
     */
    public function render_outcome_output_flash_messages(outcome_output_flash_messages $flashmessages) {
        $output = '';
        foreach ($flashmessages->get_messages(outcome_output_flash_messages::GOOD) as $message) {
            $output .= $this->output->notification($message, 'notifysuccess');
        }
        foreach ($flashmessages->get_messages(outcome_output_flash_messages::BAD) as $message) {
            $output .= $this->output->notification($message);
        }
        $flashmessages->clear_all_messages();

        return $output;
    }
}