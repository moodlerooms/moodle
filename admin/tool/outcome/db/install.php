<?php

function xmldb_tool_outcome_install() {
    // Patched quiz db/events.php but did not bump quiz version
    events_update_definition('mod_quiz');
}