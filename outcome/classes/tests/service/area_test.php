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
        $dbmock = $this->getMockBuilder('moodle_database')
            ->setMethods(array('get_field', 'insert_record'))
            ->getMockForAbstractClass();

        $dbmock->expects($this->once())
            ->method('get_field')
            ->withAnyParameters()
            ->will($this->returnValue(false));

        $dbmock->expects($this->once())
            ->method('insert_record')
            ->withAnyParameters()
            ->will($this->returnValue(3));

        $repo = new outcome_model_area_repository($dbmock);

        $model = new outcome_model_area();
        $model->component = 'mod_foo';
        $model->area = 'mod';
        $model->itemid = '10';

        $service = new outcome_service_area($repo);
        $result  = $service->set_area_used($model, 10);

        $this->assertTrue($result);
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

    public function test_get_used_area_id() {
        $cmid = '7';
        $expectedusedareaid = '5';

        $dbmock = $this->getMockBuilder('moodle_database')
            ->setMethods(array('get_record_sql'))
            ->getMockForAbstractClass();

        $dbmock->expects($this->once())
            ->method('get_record_sql')
            ->withAnyParameters()
            ->will($this->returnValue((object) array('areaid' => '100', 'usedareaid' => null)));

        $mock = $this->getMock(
            'outcome_model_area_repository',
            array('set_area_used'),
            array($dbmock)
        );

        $mock->expects($this->once())
            ->method('set_area_used')
            ->with($this->anything(), $cmid)
            ->will($this->returnValue($expectedusedareaid));

        $service = new outcome_service_area($mock);
        $result = $service->get_used_area_id('mod_forum', 'mod', '1', $cmid);

        $this->assertEquals($expectedusedareaid, $result);
    }
}