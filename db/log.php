<?php
/**
 * db/log.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$logs = array(
array('module'=>'checkmark', 'action'=>'view', 'mtable'=>'checkmark', 'field'=>'name'),
array('module'=>'checkmark', 'action'=>'add', 'mtable'=>'checkmark', 'field'=>'name'),
array('module'=>'checkmark', 'action'=>'update', 'mtable'=>'checkmark', 'field'=>'name'),
array('module'=>'checkmark', 'action'=>'view submission', 'mtable'=>'checkmark', 'field'=>'name'),
array('module'=>'checkmark', 'action'=>'view print-preview', 'mtable'=>'checkmark',
      'field'=>'name'),
array('module'=>'checkmark', 'action'=>'export pdf', 'mtable'=>'checkmark', 'field'=>'name'),
array('module'=>'checkmark', 'action'=>'upload', 'mtable'=>'checkmark', 'field'=>'name'),
);
