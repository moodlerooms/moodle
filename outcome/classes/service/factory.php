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
 * Service Factory
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/model/area_repository.php');
require_once(dirname(__DIR__).'/model/filter_repository.php');
require_once(dirname(__DIR__).'/model/outcome_repository.php');
require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

/**
 * Service Factory
 *
 * At first glance, some of the build_* methods in this class
 * may look overly simple and redundant, but it was done like this
 * to support services that may require additional arguments
 * or depend on other services.  If this happens, then
 * the overall structure of this class does not need to be
 * modified and it will remain fast.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_factory {
    /**
     * Instances of services
     *
     * @var array
     */
    protected static $services = array();

    /**
     * Creates a singleton instance of a service
     *
     * @param string $name The service name to build
     * @return mixed
     */
    public static function build($name) {
        if (!isset(self::$services[$name])) {
            self::$services[$name] = call_user_func(array(__CLASS__, "build_$name"));
        }
        return self::$services[$name];
    }

    protected static function build_mapper() {
        require_once(__DIR__."/mapper.php");
        return new outcome_service_mapper();
    }

    protected static function build_area() {
        require_once(__DIR__."/area.php");
        return new outcome_service_area();
    }

    protected static function build_attempt() {
        require_once(__DIR__."/attempt.php");
        return new outcome_service_attempt();
    }

    protected static function build_backup() {
        require_once(__DIR__."/backup.php");
        return new outcome_service_backup();
    }
}