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
 * Outcome Administration
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

/** @var $CFG stdClass */
require_once(dirname(__DIR__).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/classes/controller/kernel.php');
require_once(__DIR__.'/classes/controller/outcome_set.php');
require_once(__DIR__.'/classes/controller/import.php');
require_once(__DIR__.'/classes/controller/export.php');

$action = required_param('action', PARAM_ALPHAEXT);

admin_externalpage_setup('core_outcomes');

$router = new outcome_controller_router();
$router->add_controller(new outcome_controller_outcome_set());
$router->add_controller(new outcome_controller_import());
$router->add_controller(new outcome_controller_export());

$kernel = new outcome_controller_kernel($router);
$kernel->handle($action);