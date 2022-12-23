CHANGELOG
=========

4.1.0 (2022-12-07)
------------------
* [FEATURE] #7005 Change the grade dropdown to a text field when using numerical grades 
* [FEATURE] #7101 Add the possibility for teachers to display the due date of a checkmark activity in their calendars
* [UPDATE] #7402 Changelog is now shipped in the .md format
* [FIXED] #7402 Fix differences between the db/install.xml and db/upgrade.php files

4.0.0 (2022-08-10)
------------------
* [FEATURE] #7200 New activity icon matching the style of 4.0
* [FEATURE] #7237 Adjust structure of the checkmark activity page to fit new style of 4.0
* [FEATURE] #6995 Use resizable textareas for flexiblenaming components
* [FEATURE] #7146 Enable sequential numbering in PDF export
* [FEATURE] #7247 Add an option to reset overide settings
* [CHANGED] #6991 Revised the phrasing of multiple langstrings
* [FIXED] #7179 Use calendar event titels fitting with the ones of core plugins
* [FIXED] #7218 Fixed view all checkmarks failing when a custom user field as set as part of show user identity
* [FIXED] #7192/#7310 Fixed behavior of checkmark when using PHP 8.0
* [FIXED] #7190 Use custom langstrings for activity completion strings
* [FIXED] #7098 Fix an error when using attendence in xlsx and ods export
* [FIXED] #7157 Show urls in calendar subscription


3.11.6 (2022-07-05)
------------------
* [FIXED] #7277 Fix occasional presence of null values in checkmark_checks.state preventing proper point evaluations

3.11.5 (2022-07-05)
------------------
* [FIXED] #7225 Fix fastgrading so all combinations and grades can be submitted

3.11.4 (2022-06-01)
------------------
* [FIXED] #7179 Fix a bug causing xslx/ods download to fail on certain environment configurations

3.11.3 (2022-04-13)
------------------
* [FEATURE] #7139 Relevant dates are shown when show activity details is set
* [FIXED] #7137 Fix a bug preventing simultaneous attendance and grading when no grades were set yet

3.11.2 (2021-07-30)
------------------
* [FEATURE] #6952 Implement webservices - github pull request #62 by Alexander Melem <alexander@melem-software.com>
* [FEATURE] #6990 Remove checkmark activities marked as complete from calender and timeline -
                  github pull request 62 by Leo Dintner
* [FEATURE] #6377 Do not warn on grade automatically when no grades or feedbacks would be overwritten
* [FEATURE] #7001 Add "graded" filter and bulk select to overview and export pages
* [FIXED] #6989 Fix a deprecation warning being displayed when using PHP >8.0 - github pull request #67 by
                Mario Wehr <m.wehr@fh-kaernten.at>
* [FIXED] #6970 Fix download buttons getting disabled after pressing them
* [FIXED] #7007 Fix reset of filter in export page
* [FIXED] #7026 Make grading dashboard on landing page group sensitive to the group selected above it
* [FIXED] #7109 Fix not working require_once statements in lib.php funcitons

3.11.1 (2021-06-10)
------------------
* [FIXED] #6944 Fixed not being able to see a course with both "checkmark" activity and a "recent activity" block.

3.11.0 (2021-05-19)
------------------
* [FEATURE] #6826 Add a dashboard similar to the one in mod_assign to the main page of checkmark
                  showing all key information of the open checkmark activity
* [FEATURE] #6829 Add filter in Submissions/Export to display users with or without a presentation grade
* [FEATURE] #6841 Add activity completion badges to the checkmark landing page
* [FEATURE] #6841 Implement a custom completion rule for the submission of a checkmark
* [FEATURE] #6833 Implement behat generator functions for submissions and grading
* [FEATURE] #6913 List "All" after "100" in the "Submissions shown per page" [github #52/pullrequest #53]
* [FIXED] #6830 Replace deprecated methods and strings with their successors

3.10.1 (2021-03-09)
------------------
* [FIXED] #6836 Fixed a bug causing error messages in checkmarks with active groups when using a MySQL
                database with version number 8 or higher


3.10.0 (2020-11-10)
------------------
* [FEATURE] #6685 Add the option to export every name fragment (e.g. firstname, lastname)
                  in a dedicated column. This is controlled by a checkbox to additional settings
* [FEATURE] #6680 Add unit tests to override dates feature (#6360)
* [CHANGED] #6071 Reorder bulk processing dropdown in order to have "grade automatically" on top
* [CHANGED] #6773 Change "ungraded" bulk select so that all students with "No grade" are selected
                  regardless of an existing graded date.
* [FIXED]   #6773 Include submissions with "No grade" set in "Ungraded" bulk select
* [FIXED]   #6783 Include style definition for overwritten info in plugin css

3.9.1 (2020-09-23)
------------------
* [FIXED]   #6703 Fixed user and group overrides not considering the moodle/site:accessallgroups
                  permission in activities with group mode "Separate groups" set

3.9.0 (2020-06-08)
------------------
* [FEATURE] #6360 Overhaul of user and group override feature to match the workings of the
                  identical feature in mod_assign
* [FEATURE] #6523 Add overview for user overrides and the possibility to edit, duplicate,
                  delete each individual user override
* [FEATURE] #6522 Add overview for group overrides and the possibility to edit, duplicate,
                  delete and change the priority of each individual group override
* [FEATURE] #6607 Implement logging for all insert, change and delete events concerning
                  user/group overrides
* [FEATURE] #6624 Improve override clock popover in Submissions to display the source of the
                  override and a link to change the override
* [FEATURE] #6533 Make "Grant extension" bulk function use the new interface for user overrides
* [FEATURE] #6532 Add filter in Submissions/Export to only display users affected by
                  a user/group override
* [FEATURE] #6379 Add the possibility to upload additional files when creating a checkmark
                  instance. These files are shown to the user beneath the description [gitlab #42]
* [FEATURE] #6531 Add filter in Submissions/Export to only display users who haven't made
                  a submission [github #49]
* [FEATURE] #6328 Update behat tests to ensure compatibility with gitlab CI
* [FIXED]   #6608 Fixed due date overrides for users/groups not changing the due date indicated in
                  the calender event of the activity
* [FIXED]   #6609 Fixed checkmark calender entries only showing when the activity is currently
                  accepting submissions
* [FIXED]   #6618 Fixed individual naming/grading to be unchecked names and grades that don't
                  require individual naming/grading are used
* [FIXED]   #6619 Fixed a bug causing a database error when a grading type other than "Point"
                  is used alongside with individual naming/grading
* [FIXED]   #6649 Fixed presentationfeedback textbox not showing in grading view when
                  grading type "none" is used
* [FIXED]   #6662 Hide html in feedback and presentationfeedback textareas in quickgrade mode
                  and enable line breaks to persist between normal grading and quickgrading

3.8.1 (2020-03-11)
------------------
* [FIXED] #6604 Fixed a bug checking/unchecking overwrite checkboxes when fast grading is set and a all/none bulk selectors are used
* [FIXED] #6591 Fixed a bug preventing grading submissions with 0 points in fastgrade if you previous grading was set
* [FIXED] #6604 Fixed a bug enabling submission of checks after the cut-off date

3.8.0 (2020-02-05)
------------------
* [FEATURE] #5271 Enable teachers to overwrite checks after submissions. This can be done in fastgrade and normal grade mode
* [FEATURE] #6147 Show overwritten checks differently than normal ones in all views
* [FEATURE] #6150 Include overwritten examples in activity log
* [FEATURE] #6436 Display overwritten checks using colors in .xslx and .ods and brackets in all other formats
* [FEATURE] #6364 Enable editing of checked examples without an additional button click. The checkmark preview is only displayed when no submission edit is possible
* [FIXED] #6386 Remove deprecated function leading to integration issues with block_course_overview_campus
* [FIXED] #6497 Change generation of attendance and presentation grading gradebook items to enable compatibility with new grading api
* [FIXED] #6475 Fix bug preventing export of privacy api data under certain circumstances

3.7.1 (2019-11-13)
------------------
* [FEATURE] #6276 Display a second submit button on top of the checkmark preview
* [FIXED] #6222 Fix not possible submission when submitting student is not part of a group in a course with group mode on
* [FIXED] #5890 Remove html tags showing in pdf, xls and odt exports
* [FIXED] #6260 Fix notifications send to users with grading privilege who where not part of a course where the notification originated from

3.7.0 (2019-07-10)
------------------
* [FEATURE] #6146 Improve layout of checks for easier readability
* [FEATURE] #6147 Enable display of overwritten checks for upcoming overwrite checks feature (#5271)
* [FEATURE] #5729 Make all examples in submissions and export collapsible at once
* [FEATURE] #5720 Add default values for availability in site-administration
* [FEATURE] #6129 Add support for editdates plugin
* [FEATURE] #5296 Set field type of cells containing numeric values in .xlsx and .ods to number
* [FIXED] #6254 Separated functionality of filter dropdowns in Submissions and Export view
* [FIXED] #6253 Fixed debug messages occurring when grading
* [FIXED] #6252 Removed overlaying tooltip triggers for collapse/expand buttons in table headers
* [FIXED] #6238 Fix occasional debug notices when previewing a checkmark exercise
* [FIXED] #6257 Fix positioning of separation line between attendance/presentation grading and grading columns

3.6.0 (2019-01-20)
------------------

* Moodle 3.6 compatible version
* [FIXED] #5605 got rid of humongous AMD-params-list and jQuery for submission form js
* [FIXED] #5605 stopped requiring unused libs
* [FIXED] #5848 added missing setting to backups
* [FIXED] #5887 fixed bulk actions setting attendance and creating feedbacks when canceling
* [FIXED] #5866 in v3.4.0 we missed to include a field and a key in install.xml, so we're fixing it now!
* [UPDATE] #5753 added new privacy API methods
* [UPDATE] #5606 updated .travis.yml
* [CHANGED] #5847 removed deprecated/unused helper scripts (fixing github issue 30)


3.5.1 (2018-11-07)
------------------

* [FIXED] #5738 fix attendance grade item being created on bulk action ignoring settings
* [FEATURE] #5269 make example grades and names changeable after submissions are present


3.5.0 (2018-08-17)
------------------

* Moodle 3.5 compatible version
* [FEATURE] #3577 implemented support for privacy API
* [FEATURE] #5017 added the possibility to download separate PDFs for each group as zipped archive in one step
* [CHANGED] #5270 updated layout of submissions table to have proper columns for examples like export table had before
* [CHANGED] #5090 removed german language file from repository
* [CHANGED] #5089 removed long deprecated XLS-export methods
* many small code improvements, bug fixes, etc.


3.4.2 (2018-05-15)
------------------

* [FIXED] #5426 fix non groups members staying selected for export if selected group is changed before pressing export
* [FIXED] #5442 don't download the PDF immediatly when clicking on update data preview if a template has been selected
* [CHANGED] #5436 removed unsupported/unused plagiarism libs and calls


3.4.1 (2018-05-02)
------------------

* [FIXED] #5302 add missing language string for checkmark:manageoverrides
* [FIXED] #5421 fix broken course reset callback


3.4.0 (2018-01-27)
------------------

* Moodle 3.4 compatible version
* [FEATURE] #4894 added the ability to override time available, time due and cutoff-date for certain users or groups!
* [FIXED] #5048 added an event observer to update calendar events, if instance name is changed via inplace-editing on course page
* [FIXED] #4954 UI/UX regression causing only the current page to be exportable
* [FIXED] #4915 order examples fetched from DB by ID, this will hopefully resolve some strange ORDER issues on certain databases
* [CHANGED] #4954 show initial-bars even if all submissions are displayed on one page
* [UPDATE] #4830 use hideIf in moodleforms to enhance UX by hiding everything not really relevant
* various code improvements, removal of unused code, etc.


3.3.4 (2017-10-26)
------------------

* [FEATURE] #4399 quick-export templates to enable a reliable setting for exporting PDFs
* [FEATURE] #4331 setting to force name column to be written in one line in PDF exports
* [FIXED] #4330 UTF-8 symbols in examples' names wont get saved HTML-encoded
* [FIXED] #4860 replaced free text field for page size in submission view with select dropdown, due to huge page size values for
                submissions table caused core's tablelib to allocate huge amounts of memory
* [FIXED] #4867 fixed wrong order when grading single submissions via "next"/"previous"/"save and next"-buttons due to accessing
                settings in old unused memory places (possible regression of #3226)
* [CHANGED] separated submissions tab and export tab (URLs for export tab changed and no tab-parameter needed!)
* removal of unused code, small improvements, etc.


3.3.3 (2017-10-10)
------------------

* [FIXED] #4750 missing field in GROUP BY causing PostgreSQL to raise error when groupmode is either VISIBLE or SEPARATE
                see https://github.com/academic-moodle-cooperation/moodle-mod_checkmark/issues/18
* [FIXED] #4752 fix algorithm used to recognise standard naming schemes to wrongly recognise "2/3 and 1/2", "3" as "2", "3"
                see https://github.com/academic-moodle-cooperation/moodle-mod_checkmark/issues/19
* [FIXED] #4753 fixed problems with checkmark_refresh_events() callback due to paramters having been changed
                between Moodle 3.3.1 and 3.3.2
* [CHANGED] #4767 Admins now have the ability to see the example preview even if thei're not enroled in the course
* added some JSDoc comments


3.3.2 (2017-09-27)
------------------

* [FIXED] #4732 fixed a regression due to #4672 causing erronous cleaning of checkmark individual grades setting


3.3.1 (2017-09-14)
------------------

* [FIXED] #4668 supress HTML output during AJAX call of restore methods, so AJAX-Call for duplication wont return corrupt JSON!
* [FIXED] #4672 fixed some formatting causing Moodle's prechecker to give warnings
* [FIXED] #4678 fixed submission displaying via moodleform breaking grading form (HTML doesn't like nested form elements!)
* [FIXED] #4683 fixed two of mod_checkmark's behat features using the same feature name
* [CHANGED] #4682 updated travis.yml to use moodle-plugin-ci version 2 and run behat tests in firefox and chrome


3.3.0 (2017-08-10)
------------------

* Moodle 3.3 compatible version
* [FEATURE] #4415 Added separate grading-due-date to remind graders
* [FEATURE] #4088 Support for FontAwesome icons
* [FIXED] #4615 Users without submission and without feedback now get selected when selecting 'ungraded entries'
* [FIXED] #4644 Notifications now use the updated submission modification date
* [CHANGED] #4415 Replaced checkmark_print_overview() output with action events
* [CHANGED] #4423 Stopped using table_sql's non public API and display initialsbars not in other places like moodleforms
* [CHANGED] #4614 We now use a frozen Moodle-Form as submission preview
* added plugin specific PHPUnit config file
* improved coding style and code organisation and removed some unnecessary code


3.2.1 (2017-03-02)
------------------

* [FIXED] #4277 Fixed title and header not being set correctly on submissions and export pages
* [FIXED] #4310 Fixed autograding displaying warnings when attendance is in gradebook and coupled
  as well as ignoring in gradebook overwritten attendances!
* [FIXED] Success message after auto-grading uses correct singular now, if only 1 entry was affected
* [FIXED] Fixed behat tests after they were not working properly since the 3.2 update (or since boost was introduced)
* [FIXED] #4325 Fixed presentation grade points form field wrongly set to $CFG->gradepointdefault on every edit


3.2.0 (2016-12-05)
------------------

* Moodle 3.2 compatible version


3.1.3 (2016-12-05)
------------------

* [FEATURE] Added presentation grade as additional grade item
* [FEATURE] #3710 Made description and name searchable
* [FEATURE] #3737 We now use regular text editor for submission feedbacks
* [FEATURE] #3610 We now use gradebook values for presentation grade and attendance if overwritten
* [FIXED] #3989 Fix overwritten presentationgrades not showing correctly in grading form
* [FIXED] #3986 Fix overwritten grades in general feedback not shown correctly
* [FIXED] #3949 Fix user count in messages related to automatic grading
* [CHANGED] #3985 Add notifications if attendances are locked/overriden while trying to set them via bulk actions
* [CHANGED] #3941 Show feedback for instances without grades
* [CHANGED] #3936 Hide grade columns if there is no grade to show and only feedback comments are active
* [CHANGED] #4011 Replace custom CSS classes for late/early/soon/etc. styling with bootstrap classes
* and various smaller bugfixes, fixed warning messages, etc.


3.1.2 (2016-10-03)
------------------

* [FIXED] #3758 Fixed old checkmark_refresh_dates() function to be compatible with 3.1.2 updates
  onwards


3.1.1 (2016-08-24)
------------------

* [FIXED] #3649 Removed accidently doubled upgrade code for attendances with lesser version number


3.1.0 (2016-07-12)
------------------

* Moodle 3.1 compatible version
* [FEATURE] #2812 Add support for attendances tracking
* [FEATURE] #3255 Save column status, sort order, etc. persistently
* [FEATURE] #2815 Improve layout for autograding & (new) attendance tracking bulk action
* [CHANGED] #3226 Rewrite table creation, harmonize tables (submission & export)
* [CHANGED] Migrate old unit test to phpunit
* [FIXED] #3600 Fixed wrong terms used in some German language strings concerning due dates
* [FIXED] #3321, #3575 feedback date not being updated


3.0.1 (2016-10-03)
------------------

* [FIXED] #3758 Fixed old checkmark_refresh_dates() function to be compatible with 3.1.2 updates
  onwards


3.0.0 (2016-03-14)
------------------

* Moodle 3.0 compatible version
* [FIXED] #3180 Bug concerning submission updated event (related user was not shown, caused problems)


2.9.5 (2016-06-10)
------------------

* [FIXED] #3301 Bug showing no grade on dashboard if there's no submission


2.9.4
-----

* [FIXED] #3268 Bug causing amount of unmarked submissions to always be 0 on dashboard


2.9.3
-----

* [FIXED] #3225 Correct the grades in gradebook affected from the bug fixed in 2016012002


2.9.2
-----

* [FIXED] #3214 Bug where grades won't get written to gradebook anymore
* [FIXED] #3215 Broken filter "require grading" in submissions and print-preview tab


2.9.1
-----

* [FIXED] #2953 Hidden comment column breaking exports
* [FIXED] #2953 Wrong amount of affected users in message when autograding
* [FIXED] #2954 Autograding for submissions without feedback when autograding submissions with
  feedback required


2.9.0 (2016-01-20)
------------------

* Moodle 2.9 compatible version.
* [FEATURE] New setting: checkmark/pdfexampleswarning = amount of exampes in instance to trigger
  warning about possibly unreasonable layouted PDF documents if too many examples are displayed
* [CHANGE] #2808 Separated DB table for submissions and feedbacks, students without submission wont
  get an empty submission when the feedback is given anymore
* [CHANGE] Change alternative identifier parameter in view.php from 'a' to 'c'
* Add first behat tests


2.8.4 (2015-10-20)
------------------

* [FIXED] Small bug preventing manual grading (not quick grading)


2.8.3 (2015-10-13)
------------------

* [FIXED] No submissions shown/counted because of filtered users lists


2.8.2 (2015-10-06)
------------------

* [CHANGE] #2662 Refactor code to use autoloading
* [CHANGE] Improve support for PostgreSQL
* [FIXED] #2660 Query in updgrade script breaking in PostgreSQL
* [FIXED] Missing additional user fields in recent_activities_block output
* [REMOVED] Remove obsolete/unused files/code


2.8.1
-----

* [FIXED] #2653 Move plugin settings from config to config plugins


2.8.0 (2015-07-15)
------------------

* Moodle 2.8 compatible version
* [FEATURE] #2268 Enhance autograding confirm message with numbers of autograded entries
* [FEATURE] #2328 Add posibility to export "signature" column
* [CHANGE] Some small CSS improvements
* [FIXED] #2291 PDF export sometimes has bad layout in portrait mode
* [FIXED] #2290 Column shown in table header even if column is hidden
* [FIXED] #2410 Fixed some form validation error messages
* [FIXED] #2346 Sorting of print preview and submissions table (sort by lastname by default)
* [FIXED] #22438 Groups not shown in print preview
* [FIXED] #2428 Removed blocking JS calls
* [FIXED] #2415 Output (styled) notification message if there's no data to shown


2.7 (2015-01-14)
----------------

* Moodle 2.7 compatible version
* [FEATURE] #2089 Add PostgreSQL support
* [FEATURE] #1810 Add information about submission time to exports
* [CHANGE] 1977 Replace cron with scheduled task
* [FIXED] #2061 Update JS for new grade settings
* [FIXED] #1965 Replace add_to_log calls with triggered events
* [FIXED] #2032 Calculation of checkmark open/closed-state
* [FIXED] Check language files
* [FIXED] Some minor bugs


2.6 (2014-10-09)
----------------

* First release for Moodle 2.6
