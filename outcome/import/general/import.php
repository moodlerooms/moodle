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
 * General Importer
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)).'/classes/import/abstract.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcomeimport_general_import extends outcome_import_abstract {

    public function process_file($file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'xml') {
            throw new moodle_exception('pleaseusexml', 'outcomeimport_general');
        }
        $reader = new XMLReader();
        $reader->open($file);

        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            switch ($reader->name) {
                case 'outcomeSet':
                    $this->process_outcome_set($reader);
                    break;
                case 'outcome':
                    $this->process_outcome($reader);
                    break;
            }
        }
        $reader->close();
    }

    /**
     * We are inside of a <outcomeSet> tag, read until
     * the end of the tag while extracting outcome set attributes.
     *
     * @param XMLReader $reader
     * @return outcome_model_outcome_set
     * @throws coding_exception
     */
    public function process_outcome_set(XMLReader $reader) {
        $model = new outcome_model_outcome_set();

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::END_ELEMENT and $reader->name == 'outcomeSet') {
                break;
            }
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name == 'id') {
                continue;
            }
            if (property_exists($model, $reader->name)) {
                $this->read_value($model->{$reader->name}, $reader);
            }
        }
        $this->save_outcome_set($model);

        return $model;
    }

    /**
     * We are inside of a <outcome> tag, read until
     * the end of the tag while extracting outcome attributes.
     *
     * @param XMLReader $reader
     * @throws moodle_exception
     * @return outcome_model_outcome
     */
    public function process_outcome(XMLReader $reader) {
        $model = new outcome_model_outcome();
        $oldid = null;

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::END_ELEMENT and $reader->name == 'outcome') {
                break;
            }
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            switch ($reader->name) {
                case 'id':
                    $this->read_value($oldid, $reader);
                    break;
                case 'parentid':
                    if ($reader->isEmptyElement) {
                        break;
                    }
                    $this->read_value($parentid, $reader);
                    $model->parentid = $this->get_map_value($parentid);
                    break;
                case 'edulevel':
                    $reader->read();
                    if ($reader->hasValue) {
                        $model->edulevels[] = $reader->value;
                    }
                    break;
                case 'subject':
                    $reader->read();
                    if ($reader->hasValue) {
                        $model->subjects[] = $reader->value;
                    }
                    break;
                default:
                    if (property_exists($model, $reader->name)) {
                        $this->read_value($model->{$reader->name}, $reader);
                    }
                    break;
            }
        }
        if (empty($oldid)) {
            throw new moodle_exception('failedtofindid', 'outcomeimport_general');
        }
        $this->save_outcome($model);
        $this->set_map_value($oldid, $model->id);

        return $model;
    }

    /**
     * Helper method: read XML value and if it has a value, then
     * set it to the passed property.
     *
     * @param $property
     * @param XMLReader $reader
     */
    public function read_value(&$property, XMLReader $reader) {
        $reader->read();
        if ($reader->hasValue) {
            $property = $reader->value;
        }
    }
}
