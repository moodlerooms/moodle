<?php

function xmldb_tool_outcome_install() {
    global $CFG;

    // Patched quiz db/events.php but did not bump quiz version
    events_update_definition('mod_quiz');

    if (!empty($CFG->enableoutcomes)) {
        set_config('enable', 1, 'core_outcome');
    } else {
        set_config('enable', 0, 'core_outcome');
    }
    set_config('core_outcome_enable', 0);
}