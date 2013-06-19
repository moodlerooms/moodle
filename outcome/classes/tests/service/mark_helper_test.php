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
 * Mark Helper Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/service/mark_helper.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_mark_helper_test extends basic_testcase {
    public function test_mark_outcomes_as_earned() {
        $outcomeids = array('1', '2');

        $outcomesmock = $this->getMock('outcome_model_outcome_repository', array('find_by_ids'));
        $outcomesmock->expects($this->once())
            ->method('find_by_ids')
            ->with($this->equalTo($outcomeids))
            ->will($this->returnValue(array(
                new outcome_model_outcome(), new outcome_model_outcome())
            ));

        $mock = $this->getMock(
            'outcome_service_mark_helper',
            array('mark_outcome_as_earned'),
            array(null, $outcomesmock)
        );
        $mock->expects($this->exactly(2))
            ->method('mark_outcome_as_earned')
            ->withAnyParameters();

        $mock->mark_outcomes_as_earned(5, 7, 10, $outcomeids);
    }

    public function test_mark_outcome_as_earned() {
        $model = new outcome_model_outcome();
        $model->id = 1;
        $model->assessable = 1;

        $marksmock = $this->getMock('outcome_model_mark_repository', array('find_one_by', 'save'));
        $marksmock->expects($this->once())
            ->method('find_one_by')
            ->withAnyParameters()
            ->will($this->returnValue(false));

        $marksmock->expects($this->once())
            ->method('save')
            ->withAnyParameters();

        $service = new outcome_service_mark_helper($marksmock);
        $service->mark_outcome_as_earned('5', '7', '10', $model);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_mark_non_assessable_outcome_as_earned() {
        $model = new outcome_model_outcome();
        $model->id = 1;
        $model->assessable = 0;

        $service = new outcome_service_mark_helper();
        $service->mark_outcome_as_earned('5', '7', '10', $model);
    }

    public function test_update_mark_earned() {
        $mark1 = new outcome_model_mark();
        $mark1->id = 1;
        $mark1->result = outcome_model_mark::NOT_EARNED;

        $mark2 = new outcome_model_mark();
        $mark2->id = 2;
        $mark2->result = outcome_model_mark::EARNED;

        $mark3 = new outcome_model_mark();
        $mark3->id = 3;
        $mark3->result = outcome_model_mark::NOT_EARNED;

        $mark4 = new outcome_model_mark();
        $mark4->id = 4;
        $mark4->result = outcome_model_mark::EARNED;

        $marksmock = $this->getMock('outcome_model_mark_repository', array('find_by_ids', 'save'));
        $marksmock->expects($this->once())
            ->method('find_by_ids')
            ->withAnyParameters()
            ->will($this->returnValue(array($mark1, $mark2, $mark3, $mark4)));

        $marksmock->expects($this->at(1))
            ->method('save')
            ->with($this->identicalTo($mark1));

        $marksmock->expects($this->at(2))
            ->method('save')
            ->with($this->identicalTo($mark2));

        $service = new outcome_service_mark_helper($marksmock);
        $service->update_mark_earned(7, array(1, 2, 3, 4), array(1, 4));

        $this->assertEquals(outcome_model_mark::EARNED, $mark1->result);
        $this->assertEquals(outcome_model_mark::NOT_EARNED, $mark2->result);
        $this->assertEquals(outcome_model_mark::NOT_EARNED, $mark3->result);
        $this->assertEquals(outcome_model_mark::EARNED, $mark4->result);

        $this->assertEquals(7, $mark1->graderid);
        $this->assertEquals(7, $mark2->graderid);
        $this->assertNotEquals(7, $mark3->graderid);
        $this->assertNotEquals(7, $mark4->graderid);
    }
}