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
 * @see message_output_badge_model_message
 */
require_once($CFG->dirroot.'/message/output/badge/model/message.php');

/**
 * Message Repository
 *
 * @author Mark Nielsen
 * @package message_badge
 */
class message_output_badge_repository_message {
    /**
     * @param array $conditions
     * @return message_output_badge_model_message
     */
    public function get_message(array $conditions) {
        global $DB;

        return new message_output_badge_model_message(
            $DB->get_record('message', $conditions, '*', MUST_EXIST)
        );
    }

    /**
     * Get the number of unread messages for a user
     *
     * @param null|int $userid Defaults to current user
     * @return int
     */
    public function count_user_unread_messages($userid = null) {
        global $DB, $USER;

        if (is_null($userid)) {
            $userid = $USER->id;
        }
        return $DB->count_records_sql("
            SELECT COUNT(*)
              FROM {message} m
        INNER JOIN {message_working} w ON m.id = w.unreadmessageid
        INNER JOIN {message_processors} p ON p.id = w.processorid AND p.name = ?
             WHERE m.useridto = ?
        ", array('badge', $userid));
    }

    /**
     * Get a user's unread messages
     *
     * @param int $limit
     * @param null|int $userid
     * @return message_output_badge_model_message[]
     */
    public function get_user_unread_messages($limit = 50, $userid = null) {
        global $DB, $USER;

        if (is_null($userid)) {
            $userid = $USER->id;
        }
        $select  = user_picture::fields('u', null, 'useridfrom', 'fromuser');
        $records = $DB->get_records_sql("
            SELECT m.*, $select
              FROM {message} m
        INNER JOIN {user} u ON u.id = m.useridfrom
        INNER JOIN {message_working} w ON m.id = w.unreadmessageid
        INNER JOIN {message_processors} p ON p.id = w.processorid AND p.name = ?
             WHERE m.useridto = ?
          ORDER BY m.timecreated DESC
        ", array('badge', $userid), 0, $limit);

        $messages = array();
        foreach ($records as $record) {
            $message = new message_output_badge_model_message($record);
            $message->set_fromuser(user_picture::unalias($record, null, 'useridfrom', 'fromuser'));

            $messages[$message->id] = $message;
        }
        return $messages;
    }

    /**
     * Removes all messages from the working table that are
     * from the user in the passed message's useridfrom field.
     *
     * @param message_output_badge_model_message $message
     * @return void
     */
    public function remove_working_from_user(message_output_badge_model_message $message) {
        global $DB;

        $DB->execute('
            DELETE w
              FROM {message} m
        INNER JOIN {message_working} w ON m.id = w.unreadmessageid
        INNER JOIN {message_processors} p ON p.id = w.processorid
             WHERE p.name = ?
               AND m.useridfrom = ?
        ', array('badge', $message->useridfrom));
    }
}