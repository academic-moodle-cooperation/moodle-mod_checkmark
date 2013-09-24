# ---------------------------------------------------------------
# This software is provided under the GNU General Public License
# http://www.gnu.org/licenses/gpl.html
# with Copyright © 2009 onwards
#
# Dipl.-Ing. Andreas Hruska
# andreas.hruska@tuwien.ac.at
# 
# Dipl.-Ing. Mag. rer.soc.oec. Katarzyna Potocka
# katarzyna.potocka@tuwien.ac.at
# 
# Vienna University of Technology
# Teaching Support Center
# Guﬂhausstraﬂe 28/E015
# 1040 Wien
# http://tsc.tuwien.ac.at/
# ---------------------------------------------------------------
# FOR Moodle 2.5+
# ---------------------------------------------------------------

README.txt
v.2013-08-16


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

CHANGELOG
================================================================================
24.09.2013
- use site-config settings to determine which additional user-fields should be
  displayed in overview
- new DB-structure - we got rid of old comma separated lists in db-fields
  and use a modern normalized db-structure
- improved pdf-output via shared printing library (common to modules from VUT)
- various bugfixes and language-improvements

18.05.2012
- new function "select all/none"
included a JS-driven link or non-JS-driven button to (de)select all submissions
in submissions-view and print-preview
