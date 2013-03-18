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
 * Outcome Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/area.php');
require_once(__DIR__.'/filter.php');
require_once(__DIR__.'/outcome.php');
require_once(__DIR__.'/outcome_set.php');
require_once(__DIR__.'/abstract_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @todo We probably want to provide a way to optionally include outcome metadata for increased performance
 */
class outcome_model_outcome_repository extends outcome_model_abstract_repository {
    protected $model = 'outcome_model_outcome';
    protected $table = 'outcome';

    /**
     * Metadata fields in the outcome model
     *
     * These are retrieved and saved to outcome_metadata table.
     *
     * @var array
     */
    protected $metadatafields = array('edulevels', 'subjects');

    /**
     * @param moodle_recordset $rs
     * @param outcome_model_outcome[] $outcomes
     * @return outcome_model_outcome[]
     */
    protected function map_metadata(moodle_recordset $rs, array $outcomes) {
        foreach ($rs as $metadata) {
            $outcomes[$metadata->outcomeid]->{$metadata->name}[] = $metadata->value;
        }
        $rs->close();

        return $outcomes;
    }

    /**
     * @param outcome_model_outcome[] $outcomes
     * @return outcome_model_outcome[]
     */
    protected function find_metadata(array $outcomes) {
        $rs = $this->db->get_recordset_list('outcome_metadata', 'outcomeid', array_keys($outcomes), 'id');
        return $this->map_metadata($rs, $outcomes);
    }

    /**
     * Converts a filter into SQL that queries
     * outcomes that are filtered by the filter.
     *
     * @param outcome_model_filter $filter
     * @param string $extrawhere Additions to the where clause
     * @param array $extraprams Extra params needed for the extra where clause
     * @return array SQL and params
     */
    protected function filter_to_sql(outcome_model_filter $filter, $extrawhere = '', $extraprams = array()) {
        $joins      = array();
        $ors        = array();
        $params     = array();
        $joinparams = array();
        $template   = 'LEFT JOIN {outcome_metadata} %1$s ON (o.id = %1$s.outcomeid AND %1$s.name = ?)';

        foreach ($filter->filter as $info) {
            $ands = array();
            foreach ($this->metadatafields as $field) {
                if (array_key_exists($field, $info) and !is_null($info[$field])) {
                    $ands[]   = "$field.value = ?";
                    $params[] = $info[$field];

                    if (!array_key_exists($field, $joins)) {
                        $joins[$field] = sprintf($template, $field);
                        $joinparams[] = $field;
                    }
                }
            }
            // If empty, then bail because we are selecting all in the outcome set.
            if (empty($ands)) {
                return array(
                    "SELECT o.* FROM {outcome} o WHERE o.outcomesetid = ? $extrawhere",
                    array_merge(array($filter->outcomesetid), $extraprams)
                );
            }
            $ors[] = implode(' AND ', $ands);
        }
        $joinsql = implode(' ', $joins);
        $orsql   = '('.implode(' OR ', $ors).')';

        return array("
            SELECT o.*
              FROM {outcome} o $joinsql
             WHERE $orsql AND o.outcomesetid = ? $extrawhere
          GROUP BY o.id
        ", array_merge($joinparams, $params, array($filter->outcomesetid), $extraprams));
    }

    /**
     * @param int $id The outcome set ID
     * @param int $strictness
     * @return outcome_model_outcome|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return outcome_model_outcome|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find_one_by(array $conditions, $strictness = IGNORE_MISSING) {
        $model = $this->_find_one_by($conditions, $strictness);
        if ($model !== false) {
            $this->find_metadata(array($model->id => $model));
        }
        return $model;
    }

    /**
     * @param array $conditions
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return outcome_model_outcome[]
     * @todo More efficient way to load up metadata?
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->find_metadata($this->_find_by($conditions, $sort, $limitfrom, $limitnum));
    }

    /**
     * @param array $ids
     * @return outcome_model_outcome[]
     */
    public function find_by_ids(array $ids) {
        return $this->find_metadata($this->_find_by_ids($ids));
    }

    /**
     * Find all outcomes in a given outcome set.
     *
     * @param outcome_model_outcome_set $outcomeset
     * @return outcome_model_outcome[]
     */
    public function find_by_outcome_set(outcome_model_outcome_set $outcomeset) {
        $rs     = $this->db->get_recordset('outcome', array('outcomesetid' => $outcomeset->id));
        $metars = $this->db->get_recordset_sql('
            SELECT m.*
              FROM {outcome} o
              JOIN {outcome_metadata} m ON o.id = m.outcomeid
             WHERE o.outcomesetid = ?
          ORDER BY m.id
        ', array($outcomeset->id));

        return $this->map_metadata($metars, $this->map_to_models($rs));
    }

    /**
     * Find all outcomes that match the passed filter
     *
     * @param outcome_model_filter $filter
     * @param bool $mappable Only return the ones that are mappable
     * @return outcome_model_outcome[]
     */
    public function find_by_filter(outcome_model_filter $filter, $mappable = true) {
        $extrawheresql = '';
        $extraparams   = array();
        if ($mappable) {
            $extrawheresql = "AND o.deleted = ? AND o.assessable = ?";
            $extraparams   = array(0, 1);
        }
        list($sql, $params) = $this->filter_to_sql($filter, $extrawheresql, $extraparams);

        $rs = $this->db->get_recordset_sql($sql, $params);

        $metars = $this->db->get_recordset_sql("
            SELECT m.*
              FROM {outcome_metadata} m
        INNER JOIN (
            $sql
        ) o ON o.id = m.outcomeid
          ORDER BY m.id
        ", $params);

        return $this->map_metadata($metars, $this->map_to_models($rs));
    }

    /**
     * Find outcomes that are associated to a particular outcome area
     *
     * @param outcome_model_area $area
     * @param bool $mappable Only return the ones that are mappable
     * @return outcome_model_outcome[]
     */
    public function find_by_area(outcome_model_area $area, $mappable = true) {
        $select = 'a.id = ?';
        $params = array($area->id);

        if ($mappable) {
            $select .= ' AND s.deleted = ? AND o.deleted = ? AND o.assessable = ?';
            $params = array_merge($params, array(0, 0, 1));
        }
        $rs = $this->db->get_recordset_sql("
            SELECT o.*
              FROM {outcome} o
              JOIN {outcome_sets} s ON s.id = o.outcomesetid
              JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
              JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE $select
        ", $params);

        $metars = $this->db->get_recordset_sql("
            SELECT m.*
              FROM {outcome} o
              JOIN {outcome_metadata} m ON o.id = m.outcomeid
              JOIN {outcome_sets} s ON s.id = o.outcomesetid
              JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
              JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE $select
          ORDER BY m.id
        ", $params);

        return $this->map_metadata($metars, $this->map_to_models($rs));
    }

    /**
     * Gets assessable outcomes that are mapped to an area
     * and are also filtered.
     *
     * @param outcome_model_area $area
     * @param outcome_model_filter $filter
     * @return object[]
     */
    public function find_by_area_and_filter(outcome_model_area $area, outcome_model_filter $filter) {
        list($sql, $params) = $this->filter_to_sql(
            $filter,
            "AND o.deleted = ? AND o.assessable = ?",
            array(0, 1)
        );
        $rs = $this->db->get_recordset_sql("
            SELECT o.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
        INNER JOIN (
                  $sql
              ) filter ON filter.id = o.id
             WHERE a.id = ?
        ", array_merge($params, array($area->id)));

        return $this->map_to_models($rs);
    }

    /**
     * Finds outcomes for related items
     *
     * @param string $component
     * @param string $area
     * @param array $itemids
     * @return array
     */
    public function find_by_area_itemids($component, $area, array $itemids) {
        list($sql, $params) = $this->db->get_in_or_equal($itemids);

        $params[] = $component;
        $params[] = $area;

        $rs = $this->db->get_recordset_sql("
            SELECT o.*, a.itemid
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE a.itemid $sql
               AND a.component = ?
               AND a.area = ?
        ", $params);

        $results = array();
        foreach ($rs as $row) {
            $itemid = $row->itemid;
            unset($row->itemid);
            if (!array_key_exists($itemid, $results)) {
                $results[$itemid] = array();
            }
            $results[$itemid][$row->id] = $this->map_to_model($row);
        }
        return $results;
    }

    /**
     * Determine if the outcome idnumber is unique or not
     *
     * @param string $idnumber
     * @param null|int $outcomeid If passed, then this outcome is ignored
     * @return bool
     */
    public function is_idnumber_unique($idnumber, $outcomeid = null) {
        $select = 'idnumber = ?';
        $params = array($idnumber);

        if (!empty($outcomeid)) {
            $select .= ' AND id != ?';
            $params[] = $outcomeid;
        }
        return !$this->db->record_exists_select('outcome', $select, $params);
    }

    /**
     * Save an outcome
     *
     * Warning: all metadata (EG: edulevels and subjects) must
     * be present or they will be deleted.
     *
     * @param outcome_model_outcome $model
     * @return $this
     */
    public function save(outcome_model_outcome $model) {
        $model->timemodified = time();

        if (!empty($model->id)) {
            $this->db->update_record('outcome', $model);
            $this->db->delete_records('outcome_metadata', array('outcomeid' => $model->id));
        } else {
            $model->timecreated = $model->timemodified;
            $model->id = $this->db->insert_record('outcome', $model);
        }
        foreach ($this->metadatafields as $name) {
            foreach ($model->$name as $value) {
                $this->db->insert_record('outcome_metadata', (object) array(
                    'outcomeid' => $model->id,
                    'name'      => $name,
                    'value'     => $value,
                ));
            }
        }
        return $this;
    }
}