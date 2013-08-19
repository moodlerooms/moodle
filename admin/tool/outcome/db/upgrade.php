<?php

function xmldb_tool_outcome_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013031806) {
        // Patched quiz db/events.php but did not bump quiz version
        events_update_definition('mod_quiz');

        upgrade_plugin_savepoint(true, 2013031806, 'tool', 'outcome');
    }

    if ($oldversion < 2013031807) {

        // Define field itemid to be added to outcome_attempts
        $table = new xmldb_table('outcome_attempts');
        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

        // Conditionally launch add field itemid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index itemid (not unique) to be added to outcome_attempts
        $index = new xmldb_index('itemid', XMLDB_INDEX_NOTUNIQUE, array('itemid'));

        // Conditionally launch add index itemid
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031807, 'tool', 'outcome');
    }

    if ($oldversion < 2013031808) {

        // Define field timemodified to be added to outcome_attempts
        $table = new xmldb_table('outcome_attempts');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'rawgrade');

        // Conditionally launch add field timemodified
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031808, 'tool', 'outcome');
    }

    if ($oldversion < 2013031809) {

        // Define table outome_marks to be renamed to outcome_marks
        $table = new xmldb_table('outome_marks');

        // Launch rename table for outome_marks
        $dbman->rename_table($table, 'outcome_marks');

        // Define table outome_marks_history to be renamed to outcome_marks_history
        $table = new xmldb_table('outome_marks_history');

        // Launch rename table for outome_marks_history
        $dbman->rename_table($table, 'outcome_marks_history');

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031809, 'tool', 'outcome');
    }

    if ($oldversion < 2013031810) {

        // Define field action to be added to outcome_marks_history
        $table = new xmldb_table('outcome_marks_history');
        $field = new xmldb_field('action', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null, 'id');

        // Conditionally launch add field action
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031810, 'tool', 'outcome');
    }

    if ($oldversion < 2013031811) {
        $DB->execute('
            UPDATE {outcome} o
               SET o.description = o.name
             WHERE o.description IS NULL
                OR o.description = ""
        ');

        // Changing nullability of field description on table outcome to not null
        $table = new xmldb_table('outcome');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'docnum');

        // Launch change of nullability for field description
        $dbman->change_field_notnull($table, $field);

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031811, 'tool', 'outcome');
    }

    if ($oldversion < 2013031812) {

        // Define field name to be dropped from outcome
        $table = new xmldb_table('outcome');
        $field = new xmldb_field('name');

        // Conditionally launch drop field name
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031812, 'tool', 'outcome');
    }

    if ($oldversion < 2013031813) {

        if (!empty($CFG->enableoutcomes)) {
            set_config('enable', 1, 'core_outcome');
        } else {
            set_config('enable', 0, 'core_outcome');
        }
        set_config('core_outcome_enable', 0);

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031813, 'tool', 'outcome');
    }

    if ($oldversion < 2013031814) {
        $rs = $DB->get_recordset_sql('
            SELECT outcomeid, userid, MIN(timecreated) timecreated
              FROM {outcome_marks}
             WHERE result = ?
          GROUP BY outcomeid, userid
        ', array(1));

        foreach ($rs as $row) {
            $DB->insert_record('outcome_awards', (object) array(
                'outcomeid'   => $row->outcomeid,
                'userid'      => $row->userid,
                'timecreated' => $row->timecreated,
            ));
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031814, 'tool', 'outcome');
    }

    if ($oldversion < 2013031815) {

        // Define key outcomeusedareaid (foreign) to be dropped form outcome_attempts
        $table = new xmldb_table('outcome_attempts');
        $key   = new xmldb_key('outcomeusedareaid', XMLDB_KEY_FOREIGN, array('outcomeusedareaid'), 'outcome_used_areas', array('id'));

        // Launch drop key outcomeusedareaid
        $dbman->drop_key($table, $key);

        // Define index outcomeusedareaid_userid (not unique) to be added to outcome_attempts
        $table = new xmldb_table('outcome_attempts');
        $index = new xmldb_index('outcomeusedareaid_userid', XMLDB_INDEX_NOTUNIQUE, array('outcomeusedareaid', 'userid'));

        // Conditionally launch add index outcomeusedareaid_userid
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // outcome savepoint reached
        upgrade_plugin_savepoint(true, 2013031815, 'tool', 'outcome');
    }

    return true;
}