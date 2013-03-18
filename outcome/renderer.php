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