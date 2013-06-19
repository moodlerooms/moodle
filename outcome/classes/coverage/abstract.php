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
 * Coverage abstract
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @author    Sam Chaffee
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/interface.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 * @author    Sam Chaffee
 */
abstract class outcome_coverage_abstract implements outcome_coverage_interface {
    /**
     * @var int
     */
    protected $courseid;

    /**
     * @param $courseid
     */
    public function set_courseid($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Get SQL that gets all mappable outcomes
     *
     * @return array|bool
     */
    public function course_outcomes_sql() {

        require_once(dirname(dirname(__DIR__)).'/classes/model/outcome_repository.php');
        require_once(dirname(dirname(__DIR__)).'/classes/model/filter_repository.php');

        $outcomerepo = new outcome_model_outcome_repository();
        $filterrepo  = new outcome_model_filter_repository();

        $filters = $filterrepo->find_by_course($this->courseid);

        if (empty($filters)) {
            return false;
        }
        $params  = array();
        $queries = array();
        foreach ($filters as $filter) {
            list($filtersql, $filterparams) = $outcomerepo->filter_to_sql($filter, true);

            $queries[] = "SELECT o.id FROM {outcome} o $filtersql->join WHERE $filtersql->where $filtersql->groupby";
            $params    = array_merge($params, $filterparams);
        }
        $sql = '('.implode(') UNION (', $queries).')';

        return array($sql, $params);
    }
}
