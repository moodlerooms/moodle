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
 * Outcome Backup/Restore Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/factory.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_backup {
    /**
     * @var outcome_factory
     */
    protected $factory;

    /**
     * @param outcome_factory $factory
     */
    public function __construct(outcome_factory $factory = null) {
        if (is_null($factory)) {
            $factory = new outcome_factory();
        }
        $this->factory = $factory;
    }

    /**
     * @param restore_structure_step $step
     * @param string $component
     * @param string $area
     * @param int $oldid
     * @return bool|int
     */
    public function remap_attempt_itemid(restore_structure_step $step, $component, $area, $oldid) {
        $normalized = normalize_component($component);
        $backup     = $this->factory->build_backup('outcomesupport_'.$normalized[0]);

        if ($backup instanceof outcome_attempt_restore_interface) {
            return $backup->remap_attempt_itemid($step, $area, $oldid);
        }
        return false;
    }
}
