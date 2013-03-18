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
 * Outcome Set Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/abstract.php');

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Mark Nielsen
 */
class outcome_controller_outcome_set extends outcome_controller_abstract {
    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     */
    public function require_capability($action) {
        global $PAGE;

        require_capability('moodle/outcome:edit', $PAGE->context);
    }

    /**
     * Administer all outcome sets at the site level
     */
    public function outcomeset_action() {
        global $COURSE;

        require_once(dirname(__DIR__).'/table/manage_outcome_sets.php');

        add_to_log($COURSE->id, 'outcome', 'view outcome sets', 'admin.php?action=outcomeset');

        $table = new outcome_table_manage_outcome_sets();
        $table->define_baseurl($this->new_url());

        $this->renderer->outcome_sets_admin($table);
    }

    /**
     * Editing an outcome set
     */
    public function outcomeset_edit_action() {
        global $COURSE;

        require_once(dirname(__DIR__).'/form/outcome_set.php');
        require_once(dirname(__DIR__).'/model/outcome_repository.php');
        require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

        $outcomesetid = optional_param('outcomesetid', 0, PARAM_INT);

        $returnurl = $this->new_url(array('action' => 'outcomeset'));
        $formurl   = $this->new_url(array('action' => 'outcomeset_edit', 'outcomesetid' => $outcomesetid));
        $mform     = new outcome_form_outcome_set($formurl);

        $outcomesetrepo = new outcome_model_outcome_set_repository();
        $outcomerepo    = new outcome_model_outcome_repository();

        if (!empty($outcomesetid)) {
            $outcomeset = $outcomesetrepo->find($outcomesetid, MUST_EXIST);
        } else {
            $outcomeset = new outcome_model_outcome_set();
        }
        $outcomes = $outcomerepo->find_by_outcome_set($outcomeset);

        if ($mform->is_cancelled()) {
            redirect($returnurl);
        } else if ($data = $mform->get_data()) {
            $outcomeset->name = $data->name;
            $outcomeset->idnumber = $data->idnumber;
            $outcomeset->description = $data->description;
            $outcomeset->provider = $data->provider;
            $outcomeset->region = $data->region;

            $outcomesetrepo->save($outcomeset);

            if (!empty($data->modifiedoutcomedata)) {
                $rawoutcomes = json_decode($data->modifiedoutcomedata);
                $newidmap = array();

                foreach ($rawoutcomes as $rawoutcome) {

                    $id = clean_param($rawoutcome->id, PARAM_INT);
                    if ($id > 0) {
                        $outcome = $outcomes[$id];
                    } else {
                        $outcome = new outcome_model_outcome();
                    }
                    $outcome->outcomesetid = $outcomeset->id;
                    $outcome->parentid = clean_param($rawoutcome->parentid, PARAM_INT);
                    $outcome->idnumber = clean_param($rawoutcome->idnumber, PARAM_TEXT);
                    $outcome->name = clean_param($rawoutcome->name, PARAM_TEXT);
                    $outcome->docnum = clean_param($rawoutcome->docnum, PARAM_TEXT);
                    $outcome->assessable = clean_param($rawoutcome->assessable, PARAM_BOOL);
                    $outcome->deleted = clean_param($rawoutcome->deleted, PARAM_BOOL);
                    $outcome->description = clean_param($rawoutcome->description, PARAM_TEXT);
                    $outcome->subjects = clean_param_array($rawoutcome->subjects, PARAM_TEXT);
                    $outcome->edulevels = clean_param_array($rawoutcome->edulevels, PARAM_TEXT);
                    $outcome->sortorder = clean_param($rawoutcome->sortorder, PARAM_INT);

                    if ($outcome->parentid < 0) {
                        if (array_key_exists($outcome->parentid, $newidmap)) {
                            $outcome->parentid = $newidmap[$outcome->parentid];
                        } else {
                            $this->flashmessages->bad('failedtosavereasonparent', format_string($outcome->name));
                        }
                    } else if ($outcome->parentid == 0) {
                        $outcome->parentid = null;
                    }
                    $outcomerepo->save($outcome);

                    if ($id < 0) {
                        $newidmap[$id] = $outcome->id;
                    }
                }
                // TODO Post process to validate sortorder?
            }
            if (!$this->flashmessages->has_messages(outcome_output_flash_messages::BAD)) {
                $this->flashmessages->good('changessavedtox', format_string($outcomeset->name));
            }
            add_to_log($COURSE->id, 'outcome', 'edit outcome set', 'admin.php?action=outcomeset', $outcomeset->id);

            redirect($returnurl);
        }
        if (!empty($outcomesetid)) {
            $mform->set_data(get_object_vars($outcomeset));
            $mform->set_data(array('outcomedata' => json_encode(array_values($outcomes))));
        }
        $mform->display();
    }

    /**
     * Delete an outcome set
     */
    public function outcomeset_delete_action() {
        global $COURSE;

        require_sesskey();

        require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $repo = new outcome_model_outcome_set_repository();
        $outcomeset = $repo->find($outcomesetid, MUST_EXIST);
        $repo->remove($outcomeset);

        $restoreurl = $this->new_url(array(
            'action'       => 'outcomeset_restore',
            'sesskey'      => sesskey(),
            'outcomesetid' => $outcomeset->id
        ));
        $this->flashmessages->good('outcomesetdeleted', array(
            'name' => format_string($outcomeset->name),
            'undo' => html_writer::link($restoreurl, get_string('undo', 'outcome')),
        ));

        add_to_log($COURSE->id, 'outcome', 'delete outcome set', 'admin.php?action=outcomeset', $outcomeset->id);

        redirect($this->new_url(array('action' => 'outcomeset')));
    }

    /**
     * Restore a previously deleted outcome set
     */
    public function outcomeset_restore_action() {
        global $COURSE;

        require_sesskey();

        require_once(dirname(__DIR__).'/model/outcome_set_repository.php');

        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $repo = new outcome_model_outcome_set_repository();
        $outcomeset = $repo->find($outcomesetid, MUST_EXIST);
        $repo->restore($outcomeset);

        $this->flashmessages->good('outcomesetrestored', format_string($outcomeset->name));

        add_to_log($COURSE->id, 'outcome', 'restore outcome set', 'admin.php?action=outcomeset', $outcomeset->id);

        redirect($this->new_url(array('action' => 'outcomeset')));
    }
}