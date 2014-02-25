/**
 * README.txt
 * @version       2014-02-24
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

# ---------------------------------------------------------------
# Checkmark-Module for Moodle 2.5+
# ---------------------------------------------------------------


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
    Moodle <2.5 or later>

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
