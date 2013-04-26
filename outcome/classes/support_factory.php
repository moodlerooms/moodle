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
 * Outcome Support Factory
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/area/info_unknown.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_support_factory {
    /**
     * Finds a class inside of a file that lives in a component.
     *
     * @param string $component The component to find the class file in
     * @param string $filename The file name inside of the component, it should contain our class
     * @param string $suffix This is appended to the component name, together make the class name we want
     * @return bool|string
     * @throws coding_exception
     */
    protected function build_class_name($component, $filename, $suffix) {
        $directory = get_component_directory($component);
        if (empty($directory)) {
            return false;
        }
        $file = $directory.'/'.$filename;
        if (!file_exists($file)) {
            return false;
        }
        require_once($file);

        $classname = $component.'_'.$suffix;
        if (!class_exists($classname)) {
            throw new coding_exception("Expected to find $classname");
        }
        return $classname;
    }

    /**
     * Build an area info instance.
     *
     * @param outcome_model_area $model
     * @param cm_info $cm The course module that the area is associated to (EG: the cmid in outcome_used_areas)
     * @throws coding_exception
     * @return outcome_area_info_interface
     */
    public function build_area_info(outcome_model_area $model, cm_info $cm) {
        $normalized = normalize_component($model->component);
        $classname  = $this->build_class_name('outcomesupport_'.$normalized[0], 'area_info.php', 'area_info');

        if (empty($classname)) {
            $areainfo = new outcome_area_info_unknown();
        } else {
            $areainfo = new $classname();

            if (!$areainfo instanceof outcome_area_info_interface) {
                throw new coding_exception("The $classname must implement outcome_area_info_interface");
            }
        }
        $areainfo->set_area($model);
        $areainfo->set_cm($cm);

        return $areainfo;
    }
}
