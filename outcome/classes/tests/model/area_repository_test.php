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
 * Outcome Set Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/model/area_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_model_area_repository_test extends advanced_testcase {
    /**
     * @var outcome_model_area
     */
    protected $_area;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/base.php');
        $this->loadDataSet($this->createArrayDataSet($data));
        $this->_area = $this->_expected_area($data['outcome_areas'][0]);
    }

    protected function _expected_area($data) {
        $model = new outcome_model_area();
        foreach ($data as $name => $value) {
            $model->$name = $value;
        }
        return $model;
    }

    public function test_find() {
        $repo  = new outcome_model_area_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_area, $found);
    }

    public function test_find_miss() {
        $repo = new outcome_model_area_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_one() {
        $repo  = new outcome_model_area_repository();
        $found = $repo->find_one('mod_forum', 'mod', '1');
        $this->assertEquals($this->_area, $found);
    }

    public function test_find_by() {
        $repo  = new outcome_model_area_repository();
        $found = $repo->find_by(array('component' => 'mod_forum'));
        $this->assertEquals(array('1' => $this->_area), $found);
    }

    public function test_save() {
        global $DB;

        $model            = new outcome_model_area();
        $model->component = 'mod_forum';
        $model->area      = 'mod';
        $model->itemid    = '2';

        $repo = new outcome_model_area_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertTrue($DB->record_exists('outcome_areas', array('id' => $model->id)));
    }

    public function test_save_update() {
        $repo = new outcome_model_area_repository();
        $repo->save($this->_area);
    }

    public function test_save_area_outcomes() {
        global $DB;

        $outcome = new outcome_model_outcome();
        $outcome->id = 3;

        $repo = new outcome_model_area_repository();
        $repo->save_area_outcomes($this->_area, array('3' => $outcome));

        $this->assertTrue($DB->record_exists('outcome_area_outcomes', array('outcomeid' => 1)), 'Existing outcome remains');
        $this->assertTrue($DB->record_exists('outcome_area_outcomes', array('outcomeid' => 3)), 'New outcome added');
    }

    public function test_set_area_used() {
        global $DB;

        $outcome = new outcome_model_outcome();
        $outcome->id = 3;

        $repo = new outcome_model_area_repository();
        $repo->set_area_used($this->_area, 1);

        $this->assertTrue($DB->record_exists('outcome_used_areas', array('outcomeareaid' => $this->_area->id)));
    }

    public function test_set_area_used_by_many() {
        global $DB;

        $outcome = new outcome_model_outcome();
        $outcome->id = 3;

        $repo = new outcome_model_area_repository();
        $repo->set_area_used_by_many($this->_area, array(1, 2));

        $this->assertEquals(2, $DB->count_records('outcome_used_areas', array('outcomeareaid' => $this->_area->id)));
    }

    public function test_unset_area_used() {
        global $DB;

        $outcome     = new outcome_model_outcome();
        $outcome->id = 3;

        $repo = new outcome_model_area_repository();
        $repo->set_area_used($this->_area, 1);

        $this->assertTrue($DB->record_exists('outcome_used_areas', array('outcomeareaid' => $this->_area->id)));

        $repo->unset_area_used($this->_area, 1);

        $this->assertFalse($DB->record_exists('outcome_used_areas', array('outcomeareaid' => $this->_area->id)));
    }

    public function test_remove() {
        global $DB;

        $repo = new outcome_model_area_repository();
        $repo->set_area_used($this->_area, 1);
        $repo->remove($this->_area);

        $this->assertFalse($DB->record_exists('outcome_areas', array('id' => $this->_area->id)));
    }
}