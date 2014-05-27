<?PHP
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
 * This file contains the moodle hooks for the submission comments plugin
 *
 * @package   assignsubmission_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 *
 * Callback method for data validation---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return bool
 */
function assignsubmission_comments_comment_validate(stdClass $options) {
    global $USER, $CFG, $DB;

    if ($options->commentarea != 'submission_comments' &&
            $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assign_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assignment = new assign($context, null, null);

    if ($assignment->get_instance()->id != $submission->assignment) {
        throw new comment_exception('invalidcontext');
    }
    if (!has_capability('mod/assign:grade', $context)) {
        if (!has_capability('mod/assign:submit', $context)) {
            throw new comment_exception('nopermissiontocomment');
        } else if ($assignment->get_instance()->teamsubmission) {
            $group = $assignment->get_submission_group($USER->id);
            $groupid = 0;
            if ($group) {
                $groupid = $group->id;
            }
            if ($groupid != $submission->groupid) {
                throw new comment_exception('nopermissiontocomment');
            }
        } else if ($submission->userid != $USER->id) {
            throw new comment_exception('nopermissiontocomment');
        }
    }

    return true;
}

/**
 * Permission control method for submission plugin ---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return array
 */
function assignsubmission_comments_comment_permissions(stdClass $options) {
    global $USER, $CFG, $DB;

    if ($options->commentarea != 'submission_comments' &&
            $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assign_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assignment = new assign($context, null, null);

    if ($assignment->get_instance()->id != $submission->assignment) {
        throw new comment_exception('invalidcontext');
    }

    if ($assignment->get_instance()->teamsubmission &&
        !$assignment->can_view_group_submission($submission->groupid)) {
        return array('post' => false, 'view' => false);
    }

    if (!$assignment->get_instance()->teamsubmission &&
        !$assignment->can_view_submission($submission->userid)) {
        return array('post' => false, 'view' => false);
    }

    return array('post' => true, 'view' => true);
}

/**
 * Callback called by comment::get_comments() and comment::add(). Gives an opportunity to enforce blind-marking.
 *
 * @param array $comments
 * @param stdClass $options
 * @return array
 * @throws comment_exception
 */
function assignsubmission_comments_comment_display($comments, $options) {
    global $CFG, $DB, $USER;

    if ($options->commentarea != 'submission_comments' &&
        $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assign_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;
    $cm = $options->cm;
    $course = $options->courseid;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assignment = new assign($context, $cm, $course);

    if ($assignment->get_instance()->id != $submission->assignment) {
        throw new comment_exception('invalidcontext');
    }

    if ($assignment->is_blind_marking() && !empty($comments)) {
        // Blind marking is being used, may need to map unique anonymous ids to the comments.
        $usermappings = array();
        $hiddenuserstr = trim(get_string('hiddenuser', 'assign'));
        $guestuser = guest_user();

        foreach ($comments as $comment) {
            // Anonymize the comments.
            // Sam C - Added conditionality to anonymizing comment back.
            if (($USER->id != $comment->userid) && !has_capability('mod/assign:grade', $assignment->get_context(), $comment->userid)) {
            if (empty($usermappings[$comment->userid])) {
                // The blind-marking information for this commenter has not been generated; do so now.
                $anonid = $assignment->get_uniqueid_for_user($comment->userid);
                $commenter = new stdClass();
                $commenter->firstname = $hiddenuserstr;
                $commenter->lastname = $anonid;
                $commenter->picture = 0;
                $commenter->id = $guestuser->id;
                $commenter->email = $guestuser->email;
                $commenter->imagealt = $guestuser->imagealt;

                // Temporarily store blind-marking information for use in later comments if necessary.
                $usermappings[$comment->userid]->fullname = fullname($commenter);
                $usermappings[$comment->userid]->avatar = $assignment->get_renderer()->user_picture($commenter,
                        array('size'=>18, 'link' => false));
            }

            // Set blind-marking information for this comment.
            $comment->fullname = $usermappings[$comment->userid]->fullname;
            $comment->avatar = $usermappings[$comment->userid]->avatar;
            $comment->profileurl = null;
            } // Sam C - Added conditionality to anonymizing comment back.
        }
    }

    // Rewrite file urls.
    foreach ($comments as $comment) {
        $comment->content = file_rewrite_pluginfile_urls($comment->content, 'pluginfile.php', $options->context->id,
            'assignsubmission_comments', 'comments', $comment->id);
    }

    return $comments;
}

/**
 * Callback to force the userid for all comments to be the userid of the submission and NOT the global $USER->id. This
 * is required by the upgrade code. Note the comment area is used to identify upgrades.
 *
 * @param stdClass $comment
 * @param stdClass $param
 */
function assignsubmission_comments_comment_add(stdClass $comment, stdClass $param) {

    global $DB;
    if ($comment->commentarea == 'submission_comments_upgrade') {
        $submissionid = $comment->itemid;
        $submission = $DB->get_record('assign_submission', array('id' => $submissionid));

        $comment->userid = $submission->userid;
        $comment->commentarea = 'submission_comments';
    }
}

/**
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param $options
 * @return bool
 */
function assignsubmission_comments_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    global $CFG, $DB;

    // Make sure this is the comments area.
    if ($filearea !== 'comments') {
        return false;
    }

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    // Get the comment record.
    $commentid = (int)array_shift($args);
    if (!$comment = $DB->get_record('comments', array('id' => $commentid))) {
        return false;
    }

    // Get the submission record.
    if (!$submission = $DB->get_record('assign_submission', array('id' => $comment->itemid))) {
        return false;
    }

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assignment = new assign($context, null, null);

    if ($assignment->get_instance()->id != $submission->assignment) {
        return false;
    }

    if ($assignment->get_instance()->teamsubmission &&
        !$assignment->can_view_group_submission($submission->groupid)) {
        return false;
    }

    if (!$assignment->get_instance()->teamsubmission &&
        !$assignment->can_view_submission($submission->userid)) {
        return false;
    }

    // Try to get the file.
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/assignsubmission_comments/$filearea/$commentid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 86400, 0, true, $options);
}
