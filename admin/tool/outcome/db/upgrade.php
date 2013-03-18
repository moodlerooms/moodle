<?php

function xmldb_tool_outcome_upgrade($oldversion) {
    if ($oldversion < 2013031806) {
        // Patched quiz db/events.php but did not bump quiz version
        events_update_definition('mod_quiz');

        upgrade_plugin_savepoint(true, 2013031806, 'tool', 'outcome');
    }
    return true;
}