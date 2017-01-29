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
 * Strings for component 'mod_checkmark', language 'en', branch 'MOODLE_21_STABLE'
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['checkmark:addinstance'] = 'Add checkmark instance';
$string['checkmark:grade'] = 'Grade checkmark';
$string['checkmark:trackattendance'] = 'Track student\'s attendance';
$string['checkmark:gradepresentation'] = 'Grade presentation';
$string['checkmark:submit'] = 'Submit checkmark';
$string['checkmark:view'] = 'View checkmark';
$string['checkmark:view_preview'] = 'Preview';

$string['absent'] = 'Absent';
$string['activateindividuals'] = 'Activate individual function';
$string['strassignment'] = 'Assignment';
$string['all'] = 'All';
$string['all_absent'] = 'All absent participants';
$string['all_attendant'] = 'All attendant participants';
$string['all_unknown'] = 'All with unknown attendance status';
$string['alwaysshowdescription'] = 'Always show description';
$string['alwaysshowdescription_help'] = 'If disabled, the checkmarks description above will only become visible to students at the "Allow submissions from" date.';
$string['attendance'] = 'Attendance';
$string['attendance_help'] = 'The colum status represents the students attendance:<br /><ul><li>? - unknown</li><li>✓ - present</li><li>✖ - absent</li></ul>';
$string['attendance_not_set_grade_locked'] = 'The attendance for {$a} couldn\'t be set, because the grade is locked or overridden in gradebook.';
$string['attendancestatus'] = 'Current status of your attendance';
$string['attendancegradebook'] = 'Record attendance in gradebook';
$string['attendancegradebook_help'] = 'If you activate this feature, attendances will be visible in gradebook as separate grade item: <ul><li>Empty grade - status unknown</li><li>Grade 0 - if absent</li><li>Grade 1 - if attendant</li></ul>';
$string['attendancegradelink'] = 'Link attendance to automatic calculation of submission-grades';
$string['attendancegradelink_help'] = 'Activate this setting in order to link the saved attendances with the automatically calculated grades of the checkmark. Points of the checkmark module are only aggregated, when the person\'s attendance was marked accordingly.';
$string['attendancegradelink_hint'] = 'Note: Automatically calculated grades are linked to the attendance.';
$string['attendant'] = 'Attendant';
$string['autograde_strall'] = 'All submissions';
$string['autograde_strchanged'] = 'Due to the auto-grading the grades for <strong>{$a}</strong> student(s) will be changed.';
$string['autograde_strmultiplesubmissions'] = '{$a} submissions';
$string['autograde_stronesubmission'] = '1 submission';
$string['autograde_strreq'] = 'Submissions which require a grade-update';
$string['autogradebuttonstitle'] = 'Calculate submission-grades for checkmark {$a}';
$string['autograde_confirm'] = 'You are about to update grades and feedback for <strong>{$a}</strong>. The former grades and feedback will be overwritten.';
$string['autograde_confirm_continue'] = 'Are you shure you wan\'t to continue?';
$string['autograde_error'] = 'An error occurred during auto-grading.';
$string['autograde_failed'] = 'Auto-grading failed!';
$string['autograde_success'] = 'Auto-grading successful! {$a} submissions updated.';
$string['autograde_users_with_unknown_attendance'] = 'ATTENTION: {$a} submissions could NOT be graded automatically due to unknown attendance status!';
$string['autograde_notsupported'] = 'This scale is not supported by automatic grading.';
$string['autograde_non_numeric_grades'] = 'Auto-grading requires a numeric maximum grade to be set. This can be configured in instance-settings.';
$string['bulk_no_users_selected'] = 'You haven\'t selected any user. Select the required users via the checkboxes in the left column of the table below.';
$string['cantfixevent'] = 'Couldn\'t fix event with ID {$a->id}, named {$a->name} ({$a->matches} matches).';
$string['cfg_nowarning'] = 'No warning';
$string['cfg_pdfexampleswarning'] = 'Amount of examples to display a warning for PDF export';
$string['cfg_pdfexampleswarning_desc'] = 'Minimum amount of examples above which a warning is displayed, that no reasonable PDF export can be guaranteed. Usually you don\'t have to change this, except you don\'t want to have it shown at all or if you want to tweak it for your installation.';
$string['checkbrokengradebookgrades'] = 'Check broken gradebook grades';
$string['checkbrokengradebookgrades_desc'] = 'Due to a bug in version 2.9.1 of the Checkmark, grades have not been transfered to gradebook correctly.
The following submissions have been affected by this bug:';
$string['checkbrokengradebookgrades_mail'] = 'Due to a bug in version 2.9.1 of the Checkmark, grades have not been transfered to gradebook correctly.
The following submissions have been affected by this bug and were automatically fixed:';
$string['checkmark_overviewsummary'] = 'You\'ve checked {$a->checked_examples} / {$a->total_examples} examples ({$a->checked_grade} / {$a->total_grade} points)<br />{$a->grade}<br />';
$string['checkmark_summary'] = 'You\'ve checked <span id="examples">{$a->checked_examples}</span> out of {$a->total_examples} examples.<br />(<span id="grade">{$a->checked_grade}</span> out of a maximum of {$a->total_grade} points)';
$string['checkmarkstatstitle'] = 'Course overview checkmark';
$string['choose'] = 'With Selection...';
$string['couldfixevent'] = 'Could successfully fix event with ID {$a->id}, named {$a->name}.';
$string['count_individuals_mismatch'] = 'The amount of individual names({$a->namecount}) doesn\'t match the amount of individual grades({$a->gradecount})!';
$string['datasettingstitle'] = 'Data settings';
$string['data_settings'] = 'Print settings';
$string['data_preview_help'] = 'Click on [+] or [-] for showing or hiding columns in the print-preview.';
$string['data_preview'] = 'Data preview';
$string['duedatevalidation'] = 'Due date must be after the available from date.';
$string['element_disabled'] = 'The following element is disabled because of existing submissions.';
$string['elements_disabled'] = 'The following elements are disabled because of existing submissions.';
$string['end_of_submission_for'] = 'End of submission for {$a}';
$string['eventgradeupdated'] = 'Grade updated';
$string['eventsubmissionsexported'] = 'Submission exported';
$string['eventsubmissionupdated'] = 'Submission updated';
$string['eventviewprintpreview'] = 'Viewed print preview';
$string['eventviewsubmissions'] = 'Viewed submissions';
$string['examplegrades'] = 'Individual grades';
$string['examplegrades_help'] = 'Defines the weight of every example. These will be separated by the delimiter given in parentheses.';
$string['examplenames'] = 'Individual names';
$string['examplenames_help'] = 'Defines the example names. These will be separated by the delimiter given in parentheses.';
$string['exampleprefix'] = 'Individual prefix';
$string['exampleprefix_help'] = 'Optionally enter an individual prefix that will be automatically put in front of all individual names. This field may be left empty.';
$string['example_preview_title'] = 'Example preview';
$string['example_preview_title_help'] = 'The example preview shows an overview of the checkmark options.';
$string['filter'] = 'Filter';
$string['firstexamplenumber'] = 'Number of first example';
$string['firstexamplenumber_help'] = 'Number of the first example. The following examples get by 1 increased numbers of their predecessor.';
$string['flexiblenaming'] = 'Individual naming/grading';
$string['flexiblenaming_help'] = 'If activated individual names and grades are being used for each example.';
$string['format'] = 'Format';
$string['grade_automatically'] = 'grade automatically';
$string['gradesum_mismatch'] = 'The sum of the individual grades won\'t match the chosen total grades ({$a->gradesum}/{$a->maxgrade})!';
$string['grade_mismatch'] = 'The grade has to be an integral multiple of the amount of examples!';
$string['grade'] = 'Grade';
$string['grade_help'] = 'When individual functions are activated the grade has to be the sum of all example grades (maximum 100).<br />When you\'re using standard examples the grade has to be a integral multiple of the example count. If so the points per example get automatically adjusted.<br />With activated JavaScript the grade gets selected automatically when using individual functions. In the case of usage of standard-examples with activated JavaScript only integral multiples of the example count will be accepted.';
$string['informstudents'] = 'Send notifications';
$string['informstudents_help'] = 'If activated, the participant receives a notification about his/her attendance status.';
$string['nostudents'] = 'There are no users to be displayed!';
$string['nostudentsmatching'] = 'There are no users matching the current settings!';
$string['nosubmission'] = 'No checkmarks have been submitted';
$string['nousers'] = 'There are no users to be displayed!';
$string['numberofexamples'] = 'Number of examples';
$string['numberofexamples_help'] = 'Example count in this checkmark.';
$string['optimum'] = 'Optimum';
$string['presentationfeedback'] = 'Presentation feedback';
$string['presentationfeedback_table'] = 'Feedback (presentation)';
$string['presentationgrade'] = 'Presentation grade';
$string['presentationgrade_table'] = 'Grade (presentation)';
$string['presentationgrade_short'] = 'PT';
$string['presentationgradebook'] = 'Show presentation grade in gradebook';
$string['presentationgradebook_help'] = 'This option controls if the presentation grade will be displayed as extra grade item in gradebook.';
$string['presentationgrading'] = 'Track presentation grades';
$string['presentationgrading_help'] = 'If activated presentation grades can be recorded as extra element of grading information.';
$string['presentationgrading_grade'] = 'Grade presentation';
$string['presentationgrading_grade_help'] = 'This elements control how the presentation can be graded. For the grade you can select either <ul><li><strong>none</strong> no grade will be recorded, but you are able to use a text as feedback.</li><li><strong>scale</strong> Use a scale to grade student\'s presentation</li><li><strong>point</strong> use a numerical value to grade student\'s presentation</li></ul>';
$string['presentationheader'] = 'Presentation grading';
$string['printsettingstitle'] = 'Export settings';
$string['search:activity'] = 'Checkmark - activity information';
$string['sendnotifications'] = 'Send notifications';
$string['select'] = 'Select';
$string['selection'] = 'Selection';
$string['setabsent'] = 'mark as absent';
$string['setabsentandgrade'] = 'mark as absent and grade';
$string['setattendant'] = 'mark as attendant';
$string['setattendantandgrade'] = 'mark as attendant and grade';
$string['signature'] = 'Signature';
$string['start'] = 'start';
$string['strallononepage'] = 'Print all on one page';
$string['strautograded'] = '[auto-graded]';
$string['strexample'] = 'Example';
$string['strexamples'] = 'Examples';
$string['strlandscape'] = 'Landscape';
$string['strlarge'] = 'Large';
$string['strmedium'] = 'Medium';
$string['pdfpageorientation'] = 'Page orientation';
$string['pdfsettings'] = 'PDF Settings';
$string['strpapersizes'] = 'Paper size';
$string['strportrait'] = 'Portrait';
$string['strpoint'] = 'Point';
$string['strpoints'] = 'Points';
$string['strprint'] = 'Download file';
$string['pdfprintheader'] = 'Print header/-footer';
$string['pdfprintheader_help'] = 'Print header/footer if checked';
$string['pdfprintheaderlong'] = 'Print header/footer if checked';
$string['strprintpreview'] = 'Export';
$string['strprintpreviewtabalt'] = 'Open export';
$string['strrefreshdata'] = 'Update data preview';
$string['strsmall'] = 'Small';
$string['strstdexamplecount'] = 'Example count';
$string['strstdexamplecountdesc'] = 'Is the amount of how many examples will be used by default';
$string['strstdexamplestart'] = 'First example number';
$string['strstdexamplestartdesc'] = 'Is the number of the first example';
$string['strstdnames'] = 'Standard names';
$string['strstdnamesdesc'] = 'Example names if "individual naming" is used';
$string['strstdgrades'] = 'Standard grades';
$string['strstdgradesdesc'] = 'Grades if "individual naming" is used';
$string['strsubmissions'] = 'Submissions';
$string['strsubmissionstabalt'] = 'Open submissions view';
$string['strsum'] = 'Sum';
$string['pdftextsize'] = 'Text size';
$string['strvalidmsgtime'] = 'Duration of message validity';
$string['strvalidmsgtimedesc'] = 'Amount of days after which grading notifications to users won\'t be sent any more.';
$string['str_userid'] = 'Student ID';
$string['trackattendance'] = 'Track attendance';
$string['trackattendance_help'] = 'Activate this setting in order to enable tracking of participant\'s attendance.';
$string['ungraded'] = 'Ungraded';
$string['unknown'] = 'Unknown';
/*
 * End of Block for checkmark specific strings
 */

/*
 * Block with standard-checkmark-strings (adopted to checkmark)
 */
$string['allowresubmit'] = 'Allow resubmitting';
$string['allowresubmit_help'] = 'If enabled, students will be allowed to resubmit checkmarks after they have been graded (to be regraded).';
$string['alreadygraded'] = 'Your submission has already been graded and resubmission is not allowed.';
$string['bulk'] = 'Bulk processing';
$string['bulk_help'] = 'Here you can perform actions like "setting attendance", "automatic grading" or combinations thereof on multiple entries.<br />
<ul>
<li><strong>mark as attendant:</strong> Mark selected users as attendant</li>
<li><strong>mark as absent:</strong> Mark selected users as absent</li>
<li><strong>grade automatically:</strong> Calculate automatic grade for selected users</li>
<li><strong>mark as attendant and grade:</strong> Mark selected users as attendant and grade them automatically right afterwards</li>
<li><strong>mark as absent and grade:</strong> Mark selected users as absent and grade them automatically right afterwards</li>
</ul><br />
Note: if attendency is linked to the grades only attendant users will be awarded points for checked examples.
Absent users will be graded with 0 points and users with unknown attendancy will be skipped at all!';
$string['checkmarkdetails'] = 'Checkmark details';
$string['checkmarkmail'] = '{$a->grader} posted feedback on your
checkmark submission for \'{$a->checkmark}\'

You can see it appended to your checkmark submission:

    {$a->url}';
$string['checkmarkmailhtml'] = '{$a->grader} posted feedback on your
checkmark submission for \'<i>{$a->checkmark}</i>\'<br /><br />
You can see it appended to your <a href="{$a->url}">checkmark submission</a>.';
$string['checkmarkmailsmall'] = '{$a->grader} posted feedback on your
checkmark submission for \'{$a->checkmark}\' You can see it appended to your submission';
$string['checkmarkname'] = 'Checkmark name';
$string['checkmarks'] = 'Checkmarks';
$string['checkmarksubmission'] = 'Checkmark submissions';
$string['availabledate'] = 'Allow submissions from';
$string['availabledate_help'] = 'Begin of the submission period. After this date students are able to submit.';
$string['cannotviewcheckmark'] = 'You can not view this checkmark';
$string['checksummary'] = 'Checkmark summary';
$string['comment'] = 'Comment';
$string['configshowrecentsubmissions'] = 'Everyone can see notifications of submissions in recent activity reports.';
$string['coursemisconf'] = 'Course is miss-configured';
$string['currentgrade'] = 'Current grade in grade book';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If activated this marks the end of the submission period. After this date, no student will be able to submit. If disabled students are allowed to submit even after the due date.';
$string['deleteallsubmissions'] = 'Delete all submissions';
$string['description'] = 'Description';
$string['downloadall'] = 'Download all checkmarks as .zip';
$string['due'] = 'Checkmark due';
$string['duedate'] = 'Due date';
$string['duedate_help'] = 'End of nominal submission period. After this date students are able to submit, but their submission will be marked as late.';
$string['duedateno'] = 'No due date';
$string['early'] = '{$a} early';
$string['editmysubmission'] = 'Edit my submission';
$string['addsubmission'] = 'Add submission';
$string['emailstudents'] = 'Email alerts to students';
$string['emailteachermail'] = '{$a->username} has updated the checkmark submission
for \'{$a->checkmark}\' on {$a->dayupdated} at {$a->timeupdated}.

It is available here:

    {$a->url}';
$string['emailteachermailhtml'] = '{$a->username} has updated the checkmark submission
for <i>\'{$a->checkmark}\' on {$a->dayupdated} at {$a->timeupdated}</i><br /><br />
It is <a href="{$a->url}">available on the web site</a>.';
$string['emailteachers'] = 'Email alerts to teachers';
$string['emailteachers_help'] = 'If enabled, teachers receive email notification whenever students add or update a checkmark submission.

Only teachers who are able to grade the particular checkmark are notified. For example, if the course uses separate groups, teachers restricted to particular groups won\'t receive notification about students in other groups.';
$string['emptysubmission'] = 'You have not submitted anything yet';
$string['enablenotification'] = 'Send notifications';
$string['enablenotification_help'] = 'If enabled, students will be notified once their checkmark submissions (or corresponding presentations) are graded or their attendancy is marked.';
$string['errornosubmissions'] = 'There are no submissions available to download';
$string['failedupdatefeedback'] = 'Failed to update submission feedback for user {$a}';
$string['feedback'] = 'Feedback';
$string['feedbackfromteacher'] = 'Feedback from the {$a}';
$string['feedbackupdated'] = 'Submissions feedback updated for {$a} people';
$string['graded'] = 'Graded';
$string['guestnosubmit'] = 'Sorry, guests are not allowed to submit a checkmark. You have to log in/ register before you can submit your answer.';
$string['guestnoupload'] = 'Sorry, guests are not allowed to upload';
$string['hideintro'] = 'Hide description until "Available from" date';
$string['hideintro_help'] = 'If enabled, the checkmark description is hidden before the "Available from" date. Only the checkmark name is displayed.';
$string['invalidcheckmark'] = 'Incorrect checkmark';
$string['invalidid'] = 'Checkmark ID was incorrect';
$string['invaliduserid'] = 'Invalid user ID';
$string['itemstocount'] = 'Count';
$string['lastgrade'] = 'Last grade';
$string['late'] = '{$a} late';
$string['manycolumnsinpdfwarning'] = 'Attention: due to a high example-count and limited space a reasonable export to PDF cannot be ensured. Try to hide unnecessary columns or use XLSX or ODS export instead and adapt the layout in your spreadsheet application.';
$string['maximumgrade'] = 'Maximum grade';
$string['messageprovider:checkmark_updates'] = 'Checkmark notifications';
$string['modulename'] = 'Checkmark';
$string['modulename_help'] = 'Checkmarks enable the teacher to specify an assignment where students have to check marks which can then be graded.';
$string['modulenameplural'] = 'Checkmarks';
$string['newsubmissions'] = 'Checkmarks submitted';
$string['nocheckmarks'] = 'There are no checkmarks yet';
$string['noattempts'] = 'No attempts have been made on this checkmark';
$string['nomoresubmissions'] = 'No further submissions are allowed.';
$string['nonnegativeintrequired'] = 'Needs to be greater or equal zero (>= 0) and integral!';
$string['notavailableyet'] = 'Sorry, this checkmark is not yet available.<br />Checkmark instructions will be displayed here on the date given below.';
$string['notgradedyet'] = 'Not graded yet';
$string['norequiregrading'] = 'There are no checkmarks that require grading';
$string['nosubmisson'] = 'No checkmarks have been submitted';
$string['notsubmittedyet'] = 'Not submitted yet';
$string['notactive'] = 'Not active';
$string['operation'] = 'Operation';
$string['optionalsettings'] = 'Optional settings';
$string['page-mod-checkmark-x'] = 'Every checkmark module page';
$string['page-mod-checkmark-view'] = 'Checkmark module main page';
$string['page-mod-checkmark-submissions'] = 'Checkmark module submission page';
$string['pagesize'] = 'Submissions shown per page';
$string['pagesize_help'] = 'Choose "Optimum" to optimize the distribution of list entries according to the chosen text size and page orientation, if there are plenty of participants registered in your course.';
$string['pdfpagesize'] = 'Submissions shown per page';
$string['pdfpagesize_help'] = 'Choose "Optimum" to optimize the distribution of list entries according to the chosen text size and page orientation, if there are plenty of participants registered in your course.';
$string['popupinnewwindow'] = 'Open in a pop-up window';
$string['posintst100required'] = 'Has to be an integral in the interval [0,100] (0 <= X <= 100)!';
$string['posintrequired'] = 'Needs to be greater than zero (> 0) and integral!';
$string['pluginadministration'] = 'Checkmark administration';
$string['pluginname'] = 'Checkmark';
$string['quickgrade'] = 'Allow quick grading';
$string['quickgrade_help'] = 'If enabled, multiple checkmarks can be graded on one page. Add grades and comments then click the "Save all my feedback" button to save all changes for that page.';
$string['requiregrading'] = 'Require grading';
$string['reviewed'] = 'Reviewed';
$string['saveallfeedback'] = 'Save all my feedback';
$string['showrecentsubmissions'] = 'Show recent submissions';
$string['submission'] = 'Submission';
$string['submissionfeedback'] = 'Submission feedback';
$string['submissions'] = 'Submissions';
$string['submissionsamount'] = '{$a->submitted} of {$a->total} students submitted already.';
$string['submissionsaved'] = 'Your changes have been saved';
$string['submissionsgraded'] = '{$a->graded} of {$a->all} submissions graded';
$string['submissionsnotgraded'] = '{$a->reqgrading} of {$a->all} submissions not graded';
$string['submitcheckmark'] = 'Submit your checkmark using this form';
$string['submitted'] = 'Submitted';
$string['submitted_entries'] = 'Submitted';
$string['usermisconf'] = 'User is miss-configured';
$string['usernosubmit'] = 'Sorry, you are not allowed to submit a checkmark.';
$string['viewfeedback'] = 'View checkmark grades and feedback';
$string['summary_abs'] = 'x/y examples checked';
$string['summary_rel'] = '% examples checked';
$string['viewmysubmission'] = 'View my submission';
$string['viewsubmissions'] = 'View {$a} submitted checkmarks';
$string['yoursubmission'] = 'Your submission';
/*
 * End of block with standard-strings
 */

// Deprecated since Moodle 3.1!
$string['autograde_all'] = 'Grade all submissions';
$string['autograde_custom'] = 'Grade selected users';
$string['autograde_no_users_selected'] = 'You have not selected any user to grade. Select the required users via the checkboxes in the left column of the table below.';
$string['autograde_req'] = 'Grade ungraded';
$string['autograde_str'] = 'Auto-grading';
$string['autograde_str_help'] = 'Auto-grading calculates users grades according to points per example and checked examples. It adds the points for each checked example and uses this as the users grade. <ul><li>grade selected users - grades just these users, which are checked in the list. If a user hasn\'t submitted anything, a empty submission get\'s added.</li><li>grade who needs grading - grades every submission which is more up to date than the corresponding grading</li><li>grade all submissions - grades all present submissions (for this instance). Does NOT add empty submissions.</li></ul><br />The grade gets calculated based on chosen example grades and checked examples:<ul><li>standard-grading: here each example is equally weighted (integral grade per example). The grade is calculated by multiplication of the sum of checked examples with the quotient of checkmark-grade and checkmark-count.</li><li>individual example-weights: the grade is the sum of example grades for each checked example (according to instance-settings).</li></ul>';