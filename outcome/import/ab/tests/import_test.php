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
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/import.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcomeimport_ab_import_test extends basic_testcase {

    public function test_process_file() {
        $setmock = $this->getMock('outcome_service_outcome_set_helper', array('save_outcome_set'));
        $setmock->expects($this->once())
            ->method('save_outcome_set')
            ->with($this->isInstanceOf('outcome_model_outcome_set'));

        $outcomemock = $this->getMock('outcome_service_outcome_helper', array('save_outcome'));
        $outcomemock->expects($this->exactly(2))
            ->method('save_outcome')
            ->with($this->isInstanceOf('outcome_model_outcome'));

        $import = new outcomeimport_ab_import($outcomemock, $setmock);
        $import->process_file(__DIR__.'/fixtures/Base.xml');

        $this->assertInstanceOf('outcome_model_outcome_set', $import->get_result());
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_process_file_json() {
        $import = new outcomeimport_ab_import();
        $import->process_file('./NotRealFile.json');
    }

    public function test_process_outcome_set() {
        $mock = $this->getMock('outcome_service_outcome_set_helper', array('save_outcome_set'));
        $mock->expects($this->once())
            ->method('save_outcome_set')
            ->with($this->isInstanceOf('outcome_model_outcome_set'));

        $import = new outcomeimport_ab_import(null, $mock);
        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/standard_document.xml');
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'standard_document') {
                $model = $import->process_outcome_set($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('outcome_model_outcome_set', $model);
        $this->assertSame($model, $import->get_outcomeset());
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('NAME', $model->name);
        $this->assertEquals('REVISION', $model->revision);
        $this->assertEquals('PROVIDER', $model->provider);
        $this->assertEquals('REGION', $model->region);
        $this->assertEquals(array('SUBJECT'), $import->get_subjects());
        $this->assertEquals(array('EDULEVEL'), $import->get_education_levels('EDULEVELCODE'));
    }

    public function test_process_outcomes() {
        $mock = $this->getMock('outcome_service_outcome_helper', array('save_outcome'));
        $mock->expects($this->once())
            ->method('save_outcome')
            ->with($this->isInstanceOf('outcome_model_outcome'));

        $outcomeset     = new outcome_model_outcome_set();
        $outcomeset->id = 1;

        $import = new outcomeimport_ab_import($mock);
        $import->set_outcomeset($outcomeset);
        $import->add_education_level('EDULEVELCODE', 'EDULEVEL');
        $import->add_subject('SUBJECTCODE', 'SUBJECT');

        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/standard.xml');

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'standard') {
                $model = $import->process_outcomes($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('outcome_model_outcome', $model);
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('DESCRIPTION', $model->description);
        $this->assertEquals(0, $model->assessable);
        $this->assertEquals(array('EDULEVEL'), $model->edulevels);
        $this->assertEquals(array('SUBJECT'), $model->subjects);
        $this->assertNull($model->parentid);
    }

    public function test_process_outcome() {
        $mock = $this->getMock('outcome_service_outcome_helper', array('save_outcome'));
        $mock->expects($this->once())
            ->method('save_outcome')
            ->with($this->isInstanceOf('outcome_model_outcome'));

        $outcomeset     = new outcome_model_outcome_set();
        $outcomeset->id = 1;

        $root = new outcome_model_outcome();
        $root->id = 1;
        $root->edulevels = array('EDULEVEL');
        $root->subjects = array('SUBJECT');

        $import = new outcomeimport_ab_import($mock);
        $import->set_outcomeset($outcomeset);

        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/item.xml');

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'item') {
                $model = $import->process_outcome($root, $reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('outcome_model_outcome', $model);
        $this->assertEquals($root->id, $model->parentid);
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('DESCRIPTION', $model->description);
        $this->assertEquals(1, $model->assessable);
        $this->assertEquals('DOCNUM', $model->docnum);
        $this->assertEquals(array('EDULEVEL'), $model->edulevels);
        $this->assertEquals(array('SUBJECT'), $model->subjects);

        // We assert null because our mock prevents ID from being set.
        $this->assertNull($import->get_map_value('IDNUMBER'), 'Outcome registered itself in the map');
    }
}