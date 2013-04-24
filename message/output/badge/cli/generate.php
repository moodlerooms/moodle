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
 * Generate Messages Script
 *
 * This is for testing purposes only!
 *
 * @author Mark Nielsen
 * @package message_badge
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

global $CFG, $DB;

require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/message/lib.php');

list($options, $unrecognized) = cli_get_params(
    array('count' => 20, 'from-user' => 0, 'to-user' => 0, 'help' => false),
    array('c' => 'count', 'f' => 'from-user', 't' => 'to-user', 'h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo "Message generation script.

This is for testing purposes only!

This will send personal messages between two users.  This is
handy for populating the badge with data.  Be sure to route
\"Personal messages between users\" to the Alert Badge or
whatever you like.

Options:
-c, --count     Number of messages to send
-f, --from-user The User ID of the from user
-t, --to-user   The User ID of the from user
-h, --help      Print out this help

Example:
/usr/bin/php message/output/badge/cli/generate.php --from-user=X --to-user=Y --count=50
";
    die;
}

$from  = clean_param($options['from-user'], PARAM_INT);
$to    = clean_param($options['to-user'], PARAM_INT);
$count = clean_param($options['count'], PARAM_INT);

$from = $DB->get_record('user', array('id' => $from), '*', MUST_EXIST);
$to   = $DB->get_record('user', array('id' => $to), '*', MUST_EXIST);

cli_heading('SENDING MESSAGES');
for ($i = 0; $i < $count; $i++) {
    message_post_message($from, $to, "Generated message $i: ".complex_random_string(5), FORMAT_PLAIN);
}
cli_heading('DONE');