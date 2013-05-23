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
 * Custom admin settings
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die;

class admin_setting_special_outcomes extends admin_setting_configselect {
    public function __construct() {
        parent::__construct('core_outcome/enable', new lang_string('enableoutcomes', 'grades'),
            new lang_string('enableoutcomes_help', 'outcome'), 0, null);
    }

    public function load_choices() {
        $this->choices = array(
            0 => new lang_string('disabled', 'outcome'),
            1 => new lang_string('legacyoutcomes', 'outcome'),
            2 => new lang_string('newoutcomes', 'outcome'),
            3 => new lang_string('bothlegacynew', 'outcome'),
        );
        return true;
    }

    public function write_setting($data) {
        $old = $new = 0;
        if ($data == 1) {
            $old = 1; // Enable legacy outcomes.
        } else if ($data == 2) {
            $new = 1; // Enable new outcomes.
        } else if ($data == 3) {
            $old = $new = 1; // Enable both.
        }
        set_config('enableoutcomes', $old);
        set_config('core_outcome_enable', $new);

        return parent::write_setting($data);
    }
}