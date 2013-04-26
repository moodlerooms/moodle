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
 * Outcome AJAX Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/abstract.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_controller_outcome_ajax extends outcome_controller_abstract {
    /**
     * @var outcome_model_outcome_repository
     */
    public $outcomes;

    public function init($action) {
        parent::init($action);

        require_once(dirname(__DIR__).'/model/outcome_repository.php');

        $this->outcomes = new outcome_model_outcome_repository();
    }

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     * @throws coding_exception
     */
    public function require_capability($action) {
        global $PAGE;

        switch ($action) {
            case 'is_outcome_idnumber_unique':
                require_capability('moodle/outcome:edit', $PAGE->context);
                break;
            default:
                throw new coding_exception("Missing capability check for $action action");
        }
    }

    /**
     * Determines if an outcome idnumber is unique or not
     *
     * @return string
     */
    public function is_outcome_idnumber_unique_action() {
        require_sesskey();

        $outcomeid = required_param('outcomeid', PARAM_INT);
        $idnumber  = required_param('idnumber', PARAM_TEXT);

        $result = $this->outcomes->is_idnumber_unique($idnumber, $outcomeid);

        return json_encode(array('result' => $result));
    }
}