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
 * Internal Service: Outcome Set Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_service_outcome_set_helper {
    /**
     * @var outcome_model_outcome_set_repository
     */
    protected $outcomesets;

    /**
     * @param outcome_model_outcome_set_repository $outcomesets
     */
    public function __construct(outcome_model_outcome_set_repository $outcomesets = null) {

        if (is_null($outcomesets)) {
            $outcomesets = new outcome_model_outcome_set_repository();
        }
        $this->outcomesets = $outcomesets;
    }

    /**
     * Save outcome set form data.  Data should already be validated!
     *
     * @param outcome_model_outcome_set $model
     * @param object $data
     */
    public function save_outcome_set_form_data(outcome_model_outcome_set $model, $data) {
        $model->name        = trim($data->name);
        $model->idnumber    = trim($data->idnumber);
        $model->description = $data->description;
        $model->provider    = trim($data->provider);
        $model->region      = trim($data->region);

        $this->outcomesets->save($model);
    }
}
