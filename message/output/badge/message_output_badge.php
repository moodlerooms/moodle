<?php
/**
 * Alert Badge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package message_badge
 * @author Mark Nielsen
 */

/**
 * @see messsage_badge
 */
require_once($CFG->dirroot.'/message/output/lib.php');

/**
 * Badge Message
 *
 * @author Mark Nielsen
 * @package messsage_badge
 */
class message_output_badge extends message_output {
    /**
     * Processor IDs
     *
     * @var array
     */
    protected static $processorids = array();

    /**
     * @return string
     */
    protected function get_type() {
        return 'badge';
    }

    /**
     * Gets the processor ID from the DB and cache's it.
     *
     * Throws exception if it doesn't exist (AKA not installed properly)
     *
     * @return int
     */
    protected function get_processorid() {
        global $DB;

        if (!array_key_exists($this->get_type(), self::$processorids)) {
            self::$processorids[$this->get_type()] = $DB->get_field('message_processors', 'id', array('name' => $this->get_type()), MUST_EXIST);
        }
        return self::$processorids[$this->get_type()];
    }

    /**
     * Process the message
     *
     * @param stdClass $message
     * @return boolean
     */
    public function send_message($message) {
        global $DB;

        // Prevent users from getting messages to themselves (happens with forum notifications)
        if ($message->userfrom->id != $message->userto->id) {
            try {
                $DB->insert_record('message_working', (object) array(
                    'processorid'     => $this->get_processorid(),
                    'unreadmessageid' => $message->savedmessageid,
                ));
            } catch (Exception $e) {
            }
        }
        return true;
    }

    /**
     * Creates necessary fields in the messaging user configuration form
     *
     * @param $preferences
     * @return bool
     */
    function config_form($preferences) {
        return false;
    }

    /**
     * Parses the user configuration form and saves it into preferences array
     *
     * @param $form
     * @param $preferences
     * @return void
     */
    public function process_form($form, &$preferences) {
    }

    /**
     * Loads the user preferences data from database to put on the user configuration form (initial load)
     *
     * @param $preferences
     * @param $userid
     * @return void
     */
    public function load_data(&$preferences, $userid) {
    }
}