<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * db/access.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/*
 * Capability definitions for the checkmark module.
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 *
 * CAPABILITY NAMING CONVENTION
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 *
 * frontpage        Alle Nutzer/innen, die auf der Startseite eingeloggt sind
 * user             Alle eingeloggten Nutzer/innen
 * guest            Gäste haben minimale Rechte und dürfen normalerweise keine Texte eingeben.
 * student          Teilnehmer/innen haben in einem Kurs grundsätzlich weniger Rechte.
 * teacher          Trainer/innen ohne Bearbeitungsrecht dürfen in Kursen unterrichten und
 *                      Teilnehmer/innen bewerten, aber sie können nichts verändern
 * editingteacher   Trainer/innen dürfen in einem Kurs alles tun, auch Aktivitäten bearbeiten und
 *                      Teilnehmer/innen beurteilen
 * coursecreator    Kursersteller/innen dürfen neue Kurse anlegen
 * manager          Manager/innen können alle Kurse betreten und bearbeiten, ohne in die Kurse
 *                      eingeschrieben zu sein.
 */

$capabilities = array(
        'mod/checkmark:addinstance' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'legacy' => array(
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                )
        ),

    'mod/checkmark:view' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
)
),

    'mod/checkmark:view_preview' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
)
),

    'mod/checkmark:submit' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
)
),

    'mod/checkmark:grade' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
)
),
);


