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
 * Area information interface
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
interface outcome_area_info_interface {
    /**
     * Set the area - get information based on this.
     *
     * @param outcome_model_area $model
     * @return mixed
     */
    public function set_area(outcome_model_area $model);

    /**
     * @return outcome_model_area
     */
    public function get_area();

    /**
     * The course module that is using this area.
     *
     * @param cm_info $cm
     * @return mixed
     */
    public function set_cm(cm_info $cm);

    /**
     * @return cm_info
     */
    public function get_cm();

    /**
     * Get the human readable type, EG: Activity, Question, Rubric
     *
     * @return string
     */
    public function get_area_name();

    /**
     * Get the name of what was attempted
     *
     * Examples:
     *      For an activity attempt, get the activity's name
     *      For a question attempt, get the question's name
     *
     * @return string
     */
    public function get_item_name();
}
