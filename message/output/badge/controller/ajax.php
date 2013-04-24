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
 * @see message_output_badge_repository_message
 */
require_once($CFG->dirroot.'/message/output/badge/repository/message.php');

/**
 * AJAX Controller
 *
 * @author Mark Nielsen
 * @package message_badge
 */
class message_output_badge_controller_ajax extends mr_controller {
    /**
     * @var message_output_badge_repository_message
     */
    private $repo;

    /**
     * Since this handles AJAX, set our own exception handler
     *
     * @return void
     */
    protected function init() {
        set_exception_handler(array($this, 'exception_handler'));
        $this->repo = new message_output_badge_repository_message();
    }

    /**
     * Set's errors through mr_notify
     *
     * @param Exception $e
     * @return void
     */
    public function exception_handler($e) {
        $error = $e->getMessage();
        if (debugging('', DEBUG_DEVELOPER)) {
            $error .= format_backtrace(get_exception_info($e)->backtrace);
        }
        echo json_encode((object) array(
            'error' => get_string('ajaxexception', 'message_badge', $error),
        ));
        die;
    }

    /**
     * Get messages HTML
     */
    public function getmessages_action() {
        echo json_encode((object) array(
            'messages' => $this->output->messages($this->repo->get_user_unread_messages()),
        ));
    }

    /**
     * Marks a message as read and returns the message content to be read
     *
     * @return void
     */
    public function read_action() {
        global $USER;

        $messageid = required_param('messageid', PARAM_INT);

        try {
            $message = $this->repo->get_message(array('id' => $messageid, 'useridto' => $USER->id));

            message_mark_message_read(clone($message), time());

            $subject = format_string($message->subject).' ('.userdate($message->timecreated, get_string('dateformat', 'message_badge')).')';
            $subject = html_writer::tag('div', $subject, array('class' => 'message_badge_message_subject'));
            $close   = html_writer::link('#', get_string('close', 'message_badge'));
            $close   = html_writer::tag('div', $close, array('class' => 'message_badge_message_close'));

            echo json_encode((object) array(
                'args' => $this->repo->count_user_unread_messages(),
                'header' => $close.$subject,
                'body' => $this->output->message_text($message, false),
            ));
        } catch (moodle_exception $e) {
            echo json_encode((object) array(
                'error' => get_string('readmessageerror', 'message_badge'),
            ));
        }
    }

    /**
     * Marks a message as read and returns new badge count
     *
     * @return void
     */
    public function ignore_action() {
        global $USER;

        $messageid = required_param('messageid', PARAM_INT);

        try {
            $message = $this->repo->get_message(array('id' => $messageid, 'useridto' => $USER->id));
            message_mark_message_read($message, time());
        } catch (Exception $e) {
        }

        echo json_encode((object) array(
            'args' => $this->repo->count_user_unread_messages(),
        ));
    }
}