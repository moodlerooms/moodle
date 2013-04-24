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
 * Badge Renderer
 *
 * @author Mark Nielsen
 * @package message_badge
 */
class message_badge_renderer extends plugin_renderer_base {
    /**
     * The javascript module used by the presentation layer
     *
     * @return array
     */
    public function get_js_module() {
        return array(
            'name'     => 'message_badge',
            'fullpath' => '/message/output/badge/module.js',
            'requires' => array(
                'base',
                'node',
                'event',
                'overlay',
                'json-parse',
                'io',
            ),
            'strings' => array(
                array('genericasyncfail', 'message_badge'),
            )
        );
    }

    /**
     * Logic to determine if we are using a "mobile view"
     *
     * @return bool
     */
    public function is_mobile() {
        global $PAGE;

        if ($PAGE->devicetypeinuse == 'mobile') {
            return true;
        }
        if (get_selected_theme_for_device_type('mobile') == $PAGE->theme->name) {
            return true;
        }
        return false;
    }

    /**
     * Render the badge element
     *
     * @param null $userid
     * @return string
     */
    public function badge($userid = null) {
        global $USER, $DB, $COURSE, $PAGE;

        // Only for logged in folks and when we are enabled
        if (!isset($USER->message_badge_disabled)) {
            if (!isloggedin() or isguestuser()) {
                $USER->message_badge_disabled = true;
            } else {
                $USER->message_badge_disabled = $DB->record_exists('message_processors', array('name' => 'badge', 'enabled' => 0));
            }
        }
        if ($USER->message_badge_disabled) {
            return '';
        }
        if ($this->is_mobile()) {
            return $this->mobile($userid);
        }

        $repo       = new message_output_badge_repository_message();
        $forwardurl = new moodle_url('/message/output/badge/view.php', array('action' => 'forward', 'courseid' => $COURSE->id));
        $total      = $repo->count_user_unread_messages($userid);

        $PAGE->requires->js_init_call('M.message_badge.init_badge', array($forwardurl->out(false)), false, $this->get_js_module());

        if (!empty($total)) {
            $countdiv = html_writer::tag('div', $total, array('id' => html_writer::random_id(), 'class' => 'message_badge_count'));
        } else {
            $countdiv = '';
        }
        $badgediv = html_writer::tag('div', $countdiv, array('id' => html_writer::random_id(), 'class' => 'message_badge message_badge_hidden'));

        return $badgediv;
    }

    /**
     * Mobile UI for badge
     *
     * @param null|int $userid
     * @return string
     */
    public function mobile($userid = null) {
        global $COURSE;

        $repo  = new message_output_badge_repository_message();
        $total = $repo->count_user_unread_messages($userid);

        if (empty($total)) {
            return '';
        }
        return html_writer::link(new moodle_url('/message/output/badge/view.php', array('courseid' => $COURSE->id)),
               $total, array('class' => 'message_badge_mobile ui-btn-left', 'data-icon' => 'alert'));
    }

    /**
     * Render messages
     *
     * @param message_output_badge_model_message[] $messages
     * @return string
     */
    public function messages(array $messages) {
        global $COURSE;

        $messagehtml = array();
        foreach ($messages as $message) {
            $messagehtml[] = $this->render($message);
        }
        if ($this->is_mobile()) {
            if (empty($messages)) {
                return html_writer::link(new moodle_url('/course/view.php', array('id' => $COURSE->id)), get_string('nomorealerts', 'message_badge'), array('data-role' => 'button', 'data-icon' => 'home'));
            }
            if (!empty($this->page->theme->settings->mswatch)) {
                $showswatch = $this->page->theme->settings->mswatch;
            } else {
                $showswatch = '';
            }
            if ($showswatch == 'lightblue') {
                $dtheme = 'b';
            } else if ($showswatch == 'darkgrey') {
                $dtheme = 'a';
            } else if ($showswatch == 'black') {
                $dtheme = 'a';
            } else if ($showswatch == 'lightgrey') {
                $dtheme = 'c';
            } else if ($showswatch == 'mediumgrey') {
                $dtheme = 'd';
            } else if ($showswatch == 'glassy') {
                $dtheme = 'j';
            } else if ($showswatch == 'yellow') {
                $dtheme = 'e';
            } else if ($showswatch == 'verydark') {
                $dtheme = 'a';
            } else if ($showswatch == 'mrooms') {
                $dtheme = 'm';
            } else {
                $dtheme = 'm';
            }
            return html_writer::alist(
                $messagehtml,
                array('data-role' => 'listview', 'data-inset' => 'true', 'data-theme' => $dtheme, 'class' => 'message_badge_mobile_messages')
            );
        }
        if (!empty($messagehtml)) {
            $hide = ' message_badge_hidden';
        } else {
            $hide = '';
        }
        $messagehtml  = implode('', $messagehtml);
        $messagehtml .= html_writer::tag('div', get_string('nomorealerts', 'message_badge'), array('class' => "message_badge_empty$hide"));

        $title = html_writer::tag('div', $this->output->help_icon('alerts', 'message_badge'), array('class' => 'message_badge_title_help')).
                 html_writer::tag('div', get_string('alerts', 'message_badge'), array('class' => 'message_badge_title'));

        $hddiv   = html_writer::tag('div', $title, array('id' => html_writer::random_id(), 'class' => 'yui3-widget-hd'));
        $bddiv   = html_writer::tag('div', $messagehtml, array('id' => html_writer::random_id(), 'class' => 'yui3-widget-bd message_badge_messages'));
        $ftdiv   = html_writer::tag('div', '', array('id' => html_writer::random_id(), 'class' => 'yui3-widget-ft'));
        $overlay = html_writer::tag('div', $hddiv.$bddiv.$ftdiv, array('id' => html_writer::random_id(), 'class' => 'message_badge_overlay message_badge_hidden'));

        return html_writer::tag('div', $overlay, array('id' => html_writer::random_id(), 'class' => 'message_badge_container'));
    }

    /**
     * Render a single message
     *
     * @param message_output_badge_model_message $message
     * @return string
     */
    public function render_message_output_badge_model_message(message_output_badge_model_message $message) {
        global $COURSE;

        $text = $this->message_text($message);
        $urls = $this->message_urls($message);
        $pic  = $this->message_user_picture($message);

        if ($this->is_mobile()) {
            return html_writer::link(
                new moodle_url('/message/output/badge/view.php', array('action' => 'read', 'courseid' => $COURSE->id, 'messageid' => $message->id)),
                $pic.$text
            );
        }
        $content = html_writer::tag('div', $text.$urls, array('class' => 'message_badge_message_content'));
        return html_writer::tag('div', $pic.$content, array('id' => html_writer::random_id('message'), 'messageid' => $message->id, 'class' => 'message_badge_message'));
    }

    /**
     * @TODO this is mobile focused
     *
     * @param message_output_badge_model_message $message
     * @param moodle_url $url
     * @return string
     */
    public function message_read(message_output_badge_model_message $message, moodle_url $url) {
        $goto = '';
        if (!empty($message->contexturl)) {
            if (!empty($message->contexturlname)) {
                $label = get_string('gotoa', 'message_badge', format_string($message->contexturlname));
            } else {
                $label = get_string('gotoitem', 'message_badge');
            }
            $contexturl = new moodle_url($message->contexturl);
            $contexturl->set_anchor(null); // Anchors don't work with jquery mobile

            $goto = html_writer::link($contexturl, $label, array('data-role' => 'button', 'data-icon' => 'forward'));
        }
        $links = html_writer::link($url, get_string('back', 'message_badge'), array('data-role' => 'button', 'data-icon' => 'back')).$goto;

        return html_writer::tag('div', $links, array('data-role' => 'controlgroup', 'data-type' => 'horizontal')).
               $this->output->container($this->message_text($message, false), 'generalbox');
    }

    /**
     * Render message text
     *
     * @param message_output_badge_model_message $message
     * @param bool $short
     * @return string
     */
    public function message_text(message_output_badge_model_message $message, $short = true) {
        if ($short and !empty($message->smallmessage) and $message->notification) {
            $text = format_text($message->smallmessage);
        } else if ($short) {
            $text = format_string($message->subject);
        } else if (!$message->notification) {
            $text = format_text(s($message->smallmessage));
        } else if (!empty($message->fullmessagehtml)) {
            $text = format_text($message->fullmessagehtml, FORMAT_HTML);
        } else {
            $text = format_text($message->fullmessage, $message->fullmessageformat);
        }
        return html_writer::tag('div', $text, array('class' => 'message_badge_message_text'));
    }

    /**
     * Render message action URLs
     *
     * @param message_output_badge_model_message $message
     * @param string $separator
     * @return string
     */
    public function message_urls(message_output_badge_model_message $message, $separator = '&nbsp;|&nbsp;') {
        global $COURSE;

        $urls   = array();
        $urls[] = html_writer::link(
            new moodle_url('/message/output/badge/view.php', array('controller' => 'ajax', 'action' => 'ignore', 'courseid' => $COURSE->id, 'messageid' => $message->id)),
            html_writer::tag('span', get_string('remove', 'message_badge'), array('class'=> 'message_badge_urltext')),
            array('class' => 'message_badge_ignoreurl')
        );
        $urls[] = html_writer::link(
            new moodle_url('/message/output/badge/view.php', array('controller' => 'ajax', 'action' => 'read', 'courseid' => $COURSE->id, 'messageid' => $message->id)),
            html_writer::tag('span', get_string('read', 'message_badge'), array('class'=> 'message_badge_urltext')),
            array('class' => 'message_badge_readurl')
        );
        if (!empty($message->contexturl)) {
            if (!empty($message->contexturlname)) {
                $label = get_string('gotoa', 'message_badge', format_string($message->contexturlname));
            } else {
                $label = get_string('gotoitem', 'message_badge');
            }
            $urls[] = html_writer::link(
                new moodle_url($message->contexturl),
                html_writer::tag('span', $label, array('class'=> 'message_badge_urltext')),
                array('class' => 'message_badge_contexturl')
            );
        }
        $separator = html_writer::tag('span', $separator, array('class' => 'message_badge_url_separator'));

        return html_writer::tag('div', implode($separator, $urls), array('class' => 'message_badge_urls'));
    }

    /**
     * Render message from user picture
     *
     * @param message_output_badge_model_message $message
     * @return string
     */
    public function message_user_picture(message_output_badge_model_message $message) {
        if ($this->is_mobile()) {
            return $this->output->user_picture($message->get_fromuser(), array('link' => false, 'class' => 'userpicture', 'size' => 78));
        }
        $picture = $this->output->user_picture($message->get_fromuser(), array('link' => false, 'class' => 'userpicture'));
        return html_writer::tag('div', $picture, array('class' => 'message_badge_user_picture'));
    }
}