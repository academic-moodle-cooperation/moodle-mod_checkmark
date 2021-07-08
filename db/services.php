<?php

$functions = [

        'mod_checkmark_get_checkmarks_by_courses' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'get_checkmarks_by_courses',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Get all checkmarks in the given courses',
            'type'          => 'read',
            'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        ],

        'mod_checkmark_get_checkmark' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'get_checkmark',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Get the checkmark with the given id',
            'type'          => 'read',
            'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        ],

        'mod_checkmark_submit' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'submit',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Submit a submission for the checkmark with the given id',
            'type'          => 'write',
            'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        ],

];
