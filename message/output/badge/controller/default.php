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
 * Default Controller
 *
 * @author Mark Nielsen
 * @package message_badge
 */
class message_output_badge_controller_default extends mr_controller {
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
        $this->repo = new message_output_badge_repository_message();
        $this->heading->set('alertstwo');
    }

    /**
     * View unread messages
     *
     * @return string
     */
    public function view_action() {
        return $this->output->messages(
            $this->repo->get_user_unread_messages(50)
        );
    }

    /**
     * Read a message
     *
     * @return string
     */
    public function read_action() {
        global $USER;

        $messageid = required_param('messageid', PARAM_INT);

        try {
            $message = $this->repo->get_message(array('id' => $messageid, 'useridto' => $USER->id));
            message_mark_message_read($message, time());
        } catch (moodle_exception $e) {
            redirect($this->new_url()); // Failed, take them somewhere else
            die;
        }

        return $this->output->message_read($message, $this->new_url());
    }

    /**
     * Marks a message as read and forwards the user to their requested URL
     *
     * @return void
     */
    public function forward_action() {
        global $USER;

        $messageid = required_param('messageid', PARAM_INT);
        $url       = required_param('url', PARAM_URL);

        try {
            $message = $this->repo->get_message(array('id' => $messageid, 'useridto' => $USER->id));
            message_mark_message_read($message, time());
        } catch (moodle_exception $e) {
            // Nothing...
        }
        redirect(new moodle_url($url));
    }
}