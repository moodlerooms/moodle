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
 * Outcome Standard Library
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/classes/model/attempt.php');
require_once(__DIR__.'/classes/service/factory.php');

/**
 * This service helps with mapping outcomes
 * and outcome sets to content.
 *
 * @return outcome_service_mapper
 * @see outcome_service_mapper
 */
function outcome_mapper() {
    return outcome_service_factory::build('mapper');
}

/**
 * This service helps with managing outcome areas.
 *
 * @return outcome_service_area
 * @see outcome_service_area
 */
function outcome_area() {
    return outcome_service_factory::build('area');
}

/**
 * This service helps with managing outcome attempts.
 *
 * @return outcome_service_attempt
 * @see outcome_service_attempt
 */
function outcome_attempt() {
    return outcome_service_factory::build('attempt');
}