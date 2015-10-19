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
 * README.txt
 * @version       2015-01-14
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# ---------------------------------------------------------------
# FOR Moodle 2.8+
# ---------------------------------------------------------------

Checkmark-Module
===============

OVERVIEW
================================================================================
    The Checkmark-Module enables the teacher to give the student some work
    (i.e. examples to solve) which the student afterwards checks
    if he's able to solve them.
    In class the teacher can check if the students really are able to solve the
    examples and assign grades via moodle.

    main features (among more unmentioned):
    *) using either standard examples (individual starting-number and amount,
       fixed grade per example) or individual naming/grading (names and grades
       can be defined for each example)
    *) autograde submissions using checked examples and examplegrades
    *) submission-overview and submission export to PDF

REQUIREMENTS
================================================================================
    Moodle <2.8 or later>

INSTALLATION
================================================================================
   The zip-archive includes the same directory hierarchy as moodle
   So you only have to copy the files to the correspondent place.
   copy the folder checkmark.zip/mod/checkmark --> moodle/mod/checkmark
   The langfiles normaly can be left into the folder mod/checkmark/lang.
   All languages should be encoded with utf8.
   Included languages are: German (de) and English (en)

    After it you have to run the admin-page of moodle
    (http://your-moodle-site/admin) in your browser.
    You have to be logged in as admin before.
    The installation process will be displayed on the screen.
    That's all.

CHANGELOG
================================================================================
v 2015071504
-------------------------
*) fixed small bug preventing manual grading (not quick grading)

v 2015071503
-------------------------
*) fix no submissions shown/counted because of filtered users lists

v 2015071502
-------------------------
*) fix query in updgrade script breaking in postgres
*) remove obsolete/unused files/code
*) improve support for PostgreSQL
*) fix missing additional user fields in recent_activities_block output
*) refactor code to use autoloading

v 2015071501
-------------------------
*) move plugin settings from config to config plugins

v 2015071500
-------------------------
*) fix PDF export sometimes has bad layout in portrait mode
*) column shown in table header even if column is hidden
*) some small CSS improvements
*) fixed some form validation error messages
*) fixed sorting of print preview and submissions table (sort by lastname by default)
*) fix groups not shown in print preview
*) remove blocking JS calls
*) output (styled) notification message if there's no data to shown
*) enhance autograding confirm message with numbers of autograded entries
*) add posibility to export "signature" column

v 2015011400
-------------------------
*) Replace cron with scheduled task
*) Update JS for new grade settings
*) Replace add_to_log calls with triggered events
*) Fix calculation of checkmark open/closed-state
*) Check language files
*) Add PostgreSQL support
*) Add information about submission time to exports

*) Fixed some minor bugs
