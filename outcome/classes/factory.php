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
 * Outcome Factory
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_factory {
    /**
     * Finds a class inside of a file that lives in a component.
     *
     * @param string $component The component to find the class file in
     * @param string $filename The file name inside of the component, it should contain our class
     * @param string $suffix This is appended to the component name, together make the class name we want
     * @param bool $required Require that the class file exists
     * @throws coding_exception
     * @return bool|string
     */
    protected function build_class_name($component, $filename, $suffix, $required = true) {
        $directory = get_component_directory($component);
        if (empty($directory)) {
            return false;
        }
        $file = $directory.'/'.$filename;
        if (!file_exists($file)) {
            if ($required) {
                throw new coding_exception("Failed to find $filename in $component");
            }
            return false;
        }
        require_once($file);

        $classname = $component.'_'.$suffix;
        if (!class_exists($classname)) {
            throw new coding_exception("Expected to find $classname in $filename in $component");
        }
        return $classname;
    }

    /**
     * @param string $class Create a new instance of this class
     * @param null|string $parent Ensure that this is the parent class
     * @return mixed
     * @throws coding_exception
     */
    protected function build_generic_instance($class, $parent = null) {
        if (!is_null($parent)) {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isSubclassOf($parent)) {
                throw new coding_exception("The $class must be a subclass of $parent");
            }
        }
        return new $class();
    }

    /**
     * Build an area info instance.
     *
     * @param outcome_model_area $model
     * @param null|cm_info $cm The course module that the area is associated to (EG: the cmid in outcome_used_areas)
     * @throws coding_exception
     * @return outcome_area_info_interface
     */
    public function build_area_info(outcome_model_area $model, cm_info $cm = null) {
        require_once(__DIR__.'/area/info_unknown.php');

        $normalized = normalize_component($model->component);
        $classname  = $this->build_class_name('outcomesupport_'.$normalized[0], 'area_info.php', 'area_info', false);

        if (empty($classname)) {
            $areainfo = new outcome_area_info_unknown();
        } else {
            $areainfo = $this->build_generic_instance($classname, 'outcome_area_info_interface');
        }
        $areainfo->set_area($model);
        if ($cm instanceof cm_info) {
            $areainfo->set_cm($cm);
        }

        return $areainfo;
    }

    /**
     * Build an outcome backup instance.
     *
     * @param string $component A outcome support component name
     * @return mixed
     * @throws coding_exception
     */
    public function build_backup($component) {
        return $this->build_generic_instance(
            $this->build_class_name($component, 'backup.php', 'backup')
        );
    }

    /**
     * Build an outcome import instance.
     *
     * @param string $component A outcome import component name
     * @return outcome_import_interface
     * @throws coding_exception
     */
    public function build_importer($component) {
        require_once(__DIR__.'/import/interface.php');

        return $this->build_generic_instance(
            $this->build_class_name($component, 'import.php', 'import'),
            'outcome_import_interface'
        );
    }

    /**
     * Build an outcome export instance.
     *
     * @param string $component A outcome export component name
     * @return outcome_export_interface
     * @throws coding_exception
     */
    public function build_exporter($component) {
        require_once(__DIR__.'/export/interface.php');

        return $this->build_generic_instance(
            $this->build_class_name($component, 'export.php', 'export'),
            'outcome_export_interface'
        );
    }

    /**
     * Build an outcome coverage instance.
     *
     * @param $component
     * @return outcome_coverage_interface
     */
    public function build_coverage($component) {
        require_once(__DIR__.'/coverage/interface.php');

        return $this->build_generic_instance(
            $this->build_class_name($component, 'coverage.php', 'coverage'),
            'outcome_coverage_interface'
        );
    }

    /**
     * Build outcome coverage instances.
     *
     * @return outcome_coverage_interface[]
     */
    public function build_coverages() {
        $supportcomponents = get_plugin_list('outcomesupport');

        $coverages = array();
        foreach ($supportcomponents as $pluginname => $path) {
            try {
                $coverages[] = $this->build_coverage('outcomesupport_'.$pluginname);
            } catch (Exception $e) {
                // Just ignore this for now.
            }
        }

        return $coverages;
    }
}
