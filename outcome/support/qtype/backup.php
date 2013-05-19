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
 * Area information for question types
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/classes/attempt/restore_interface.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcomesupport_qtype_backup implements outcome_attempt_restore_interface {

    public function remap_attempt_itemid(restore_structure_step $step, $area, $oldid) {
        global $DB;

        if ($area != 'qtype') {
            return false;
        }
        // We have to use SQL like because the question attempt can be name spaced.
        $likesql = $DB->sql_like('itemname', '?');
        $records = $DB->get_records_select('backup_ids_temp', "$likesql AND backupid = ? AND itemid = ?", array(
            '%question_attempt', $step->get_task()->get_restoreid(), $oldid
        ));

        if (count($records) > 1 or count($records) == 0) {
            return false;
        }
        $record = reset($records);
        if (!empty($record->newitemid)) {
            return $record->newitemid;
        }
        return false;
    }
}
