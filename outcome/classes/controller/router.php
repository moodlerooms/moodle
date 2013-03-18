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
 * Controller Router
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/interface.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_controller_router {
    /**
     * @var SplObjectStorage
     */
    protected $controllers;

    public function __construct() {
        $this->controllers = new SplObjectStorage();
    }

    /**
     * Add a controller to the router
     *
     * The router routes actions to the controllers
     * by first come, first serve.
     *
     * @param outcome_controller_interface $controller
     * @return $this
     */
    public function add_controller(outcome_controller_interface $controller) {
        $this->controllers->attach($controller);
        return $this;
    }

    /**
     * @return SplObjectStorage This is filled with instances of outcome_controller_interface
     */
    public function get_controllers() {
        return $this->controllers;
    }

    /**
     * Routes an action.
     *
     * The router routes actions to the controllers
     * by first come, first serve.
     *
     * @param $action
     * @return string|void|boolean|null
     * @throws coding_exception
     */
    public function route_action($action) {
        $method = "{$action}_action";
        foreach ($this->controllers as $controller) {
            $reflection = new ReflectionClass($controller);
            if (!$reflection->hasMethod($method)) {
                continue;
            } else if ($reflection->getMethod($method)->isPublic() !== true) {
                throw new coding_exception("The controller callback is not public: $method");
            }
            return array($controller, $method);
        }
        throw new coding_exception("Unable to handle request for $method");
    }
}