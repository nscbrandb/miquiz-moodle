<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_miquiz_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019022308) {
        $table = new xmldb_table('miquiz');
        $field = new xmldb_field('statsonlyforfinishedgames');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1); 
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019022308, 'mod', 'miquiz');
    }

    return true;
}