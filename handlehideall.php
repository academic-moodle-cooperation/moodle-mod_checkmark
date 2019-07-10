<?php

/**
 *
 * @package       moodle36
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2019
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();

$columns = optional_param_array('columns', false, PARAM_ALPHANUMEXT);
$hide = optional_param('hide', false, PARAM_BOOL);

$uniqueid = 'mod-checkmark-submissions';

$prefs = json_decode(get_user_preferences('flextable_' . $uniqueid), true);

foreach ($columns as $col) {
    if ($hide) {
        $prefs['collapse'][$col] = true;
        if (array_key_exists($col, $prefs['sortby'])) {
            unset($prefs['sortby'][$col]);
        }
    } else {
        $prefs['collapse'][$col] = false;
    }
}


set_user_preference('flextable_' . $uniqueid, json_encode($prefs));
