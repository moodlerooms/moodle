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
 * Outcome capabilities
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'moodle/outcome:mapoutcomes' => array(
        'captype'              => 'write',
        'contextlevel'         => CONTEXT_SYSTEM,
        'archetypes'           => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
    ),

    'moodle/outcome:mapoutcomesets' => array(
        'captype'              => 'write',
        'contextlevel'         => CONTEXT_COURSE,
        'archetypes'           => array(
            'coursecreator'  => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
    ),

    'moodle/outcome:edit' => array(

        'riskbitmask'  => RISK_SPAM | RISK_PERSONAL | RISK_XSS | RISK_CONFIG | RISK_DATALOSS,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array()
    ),

    'moodle/outcome:import' => array(

        'riskbitmask'  => RISK_SPAM | RISK_XSS | RISK_CONFIG,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array()
    ),

    'moodle/outcome:export' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array()
    ),

);
