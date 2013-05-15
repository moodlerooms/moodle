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
 * Internal Service: Outcome Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/model/outcome_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_outcome_helper {
    /**
     * @var moodle_database
     */
    protected $db;

    /**
     * @var outcome_model_outcome_repository
     */
    protected $outcomes;

    /**
     * @param outcome_model_outcome_repository $outcomes
     * @param moodle_database $db
     */
    public function __construct(outcome_model_outcome_repository $outcomes = null, moodle_database $db = null) {
        global $DB;

        if (is_null($outcomes)) {
            $outcomes = new outcome_model_outcome_repository();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
        $this->outcomes = $outcomes;
    }

    /**
     * @param outcome_model_outcome_set $outcomeset
     * @param outcome_model_outcome[] $currentoutcomes
     * @param object $data
     */
    public function save_outcome_form_data(outcome_model_outcome_set $outcomeset, $currentoutcomes, $data) {
        if (!$rawoutcomes = $this->extract_raw_outcomes($data)) {
            return;
        }
        $newidmap    = array();
        $transaction = $this->db->start_delegated_transaction();
        foreach ($rawoutcomes as $rawoutcome) {
            $id = clean_param($rawoutcome->id, PARAM_INT);
            if ($id > 0) {
                $outcome = $currentoutcomes[$id];
            } else {
                $outcome = new outcome_model_outcome();
            }
            // Remove ID, no longer required and if negative, breaks saves.
            unset($rawoutcome->id);

            $outcome->outcomesetid = $outcomeset->id;
            $this->map_to_outcome($outcome, $rawoutcome);
            $this->clean_and_validate($outcome);
            $this->resolve_parent_id($outcome, $newidmap);

            $this->outcomes->save($outcome);

            if ($id < 0) {
                $newidmap[$id] = $outcome->id;
            }
        }
        $transaction->allow_commit();
    }

    /**
     * @param object $data
     * @return bool|array
     */
    protected function extract_raw_outcomes($data) {
        if (!empty($data->modifiedoutcomedata)) {
            return json_decode($data->modifiedoutcomedata);
        }
        return false;
    }

    /**
     * Map data to an outcome model
     *
     * @param outcome_model_outcome $model
     * @param array|object $data
     */
    public function map_to_outcome(outcome_model_outcome $model, $data) {
        foreach ($data as $name => $value) {
            if (property_exists($model, $name)) {
                $model->$name = $value;
            }
        }
    }

    /**
     * If the model's parent ID is less than zero, then its parent was
     * a new outcome.  Find the parent ID in the passed ID map.
     *
     * @param outcome_model_outcome $model
     * @param array $newidmap
     * @throws moodle_exception
     */
    protected function resolve_parent_id(outcome_model_outcome $model, array $newidmap) {
        if ($model->parentid < 0) {
            if (array_key_exists($model->parentid, $newidmap)) {
                $model->parentid = $newidmap[$model->parentid];
            } else {
                throw new moodle_exception('failedtosavereasonparent', 'outcome', '', format_string($model->description));
            }
        }
    }

    /**
     * Clean and validate an outcome model.
     *
     * @param outcome_model_outcome $model
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function clean_and_validate(outcome_model_outcome $model) {
        if (!empty($model->id)) {
            $model->id = clean_param($model->id, PARAM_INT);
        }
        if (!empty($model->timemodified)) {
            $model->timemodified = clean_param($model->timemodified, PARAM_INT);
        }
        if (!empty($model->timecreated)) {
            $model->timecreated = clean_param($model->timecreated, PARAM_INT);
        }
        $model->outcomesetid = clean_param($model->outcomesetid, PARAM_INT);
        $model->parentid     = clean_param($model->parentid, PARAM_INT);
        $model->idnumber     = trim(clean_param($model->idnumber, PARAM_TEXT));
        $model->docnum       = trim(clean_param($model->docnum, PARAM_TEXT));
        $model->assessable   = clean_param($model->assessable, PARAM_BOOL);
        $model->deleted      = clean_param($model->deleted, PARAM_BOOL);
        $model->description  = trim(clean_param($model->description, PARAM_TEXT));
        $model->subjects     = clean_param_array($model->subjects, PARAM_TEXT);
        $model->edulevels    = clean_param_array($model->edulevels, PARAM_TEXT);
        $model->sortorder    = clean_param($model->sortorder, PARAM_INT);

        $model->subjects  = array_map('trim', $model->subjects);
        $model->edulevels = array_map('trim', $model->edulevels);

        if ($model->description === '') {
            throw new coding_exception('Outcome description property is required');
        }
        if (empty($model->outcomesetid)) {
            throw new coding_exception('Outcome outcomesetid property is required');
        }
        if ($model->idnumber === '') {
            throw new coding_exception('Outcome idnumber property is required');
        }
        if (!$this->outcomes->is_idnumber_unique($model->idnumber, $model->id)) {
            throw new moodle_exception('outcomeidnumbererror', 'outcome', '', format_string($model->idnumber));
        }

        // Due to cleaning, nulls get converted to zeros or empty strings.  Restore nulls if necessary.
        if ($model->parentid == 0) {
            $model->parentid = null;
        }
        if ($model->docnum === '') {
            $model->docnum = null;
        }
    }

    /**
     * Clean, validate and save an outcome model
     *
     * @param outcome_model_outcome $model
     */
    public function save_outcome(outcome_model_outcome $model) {
        $this->clean_and_validate($model);
        $this->outcomes->save($model);
    }

    /**
     * Repairs any problems in with the sort order for all
     * outcomes within the passed outcome set.
     *
     * @param outcome_model_outcome_set $outcomeset
     */
    public function fix_sort_order(outcome_model_outcome_set $outcomeset) {
        $this->_fix_sort_order(
            $this->outcomes->find_by_outcome_set($outcomeset)
        );
    }

    /**
     * Does the actual repairing of the sort order
     *
     * @param outcome_model_outcome[] $outcomes
     * @param null|int $parentid
     * @param int $sortorder
     */
    protected function _fix_sort_order(array $outcomes, $parentid = null, &$sortorder = 0) {
        foreach ($outcomes as $outcome) {
            if ($outcome->parentid == $parentid) {
                if ($outcome->sortorder != $sortorder) {
                    $outcome->sortorder = $sortorder;
                    $this->outcomes->update_sort_order($outcome);
                }
                $sortorder++;

                $this->_fix_sort_order($outcomes, $outcome->id, $sortorder);
            }
        }
    }
}
