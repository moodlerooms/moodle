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
 * Outcome Area Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/service/area.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_area_test extends basic_testcase {
    public function test_set_area_used() {
        $mock = $this->getMock(
            'outcome_model_area_repository',
            array('find_one', 'set_area_used')
        );

        $mock->expects($this->once())
            ->method('set_area_used');

        $model = new outcome_model_area();
        $model->component = 'mod_foo';
        $model->area = 'mod';
        $model->itemid = '10';

        $service = new outcome_service_area($mock);
        $service->set_area_used($model, 10);
    }

    public function test_delete_area() {
        $mock = $this->getMock(
            'outcome_model_area_repository',
            array('find_one', 'remove')
        );

        $mock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue(new outcome_model_area()));

        $mock->expects($this->once())
            ->method('remove');

        $service = new outcome_service_area($mock);
        $service->delete_area('mod_foo', 'mod', 10);
    }
}