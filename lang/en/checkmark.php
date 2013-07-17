<?php
// This file is made for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die;

/**
 * Strings for component 'mod_checkmark', language 'en', branch 'MOODLE_21_STABLE'
 *
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



/*
 * Block for checkmark-specific strings
 */
$string['activateindividuals'] = 'Activate individual function';
$string['strassignment'] = 'Assignment';
$string['autograde_all'] = 'Grade all submissions';
$string['autograde_custom'] = 'Grade selected users';
$string['autograde_req'] = 'Grade ungraded';
$string['autograde_str'] = 'Autograding';
$string['autograde_str_help'] = 'Autograding calculates users grades according to points per example and checked examples. It adds the points for each checked example and uses this as the users grade.'.
                                '<ul><li>grade selected users - grades just these users, which are checked in the list. If a user hasn\'t submitted anything, a empty submission get\'s added.</li>'.
                                    '<li>grade who needs grading - grades every submission which is more up to date than the corresponding grading</li>'.
                                    '<li>grade all submissions - grades all present submissions (for this instance). Does NOT add empty submissions.</li></ul><br />'.
                                    'The grade gets calculated based on chosen examplegrades and checked examples:'.
                                    '<ul><li>standard-grading: here each example is equally weighted (integral grade per example). The grade is calculated by multiplication of the sum of checked examples with the quotient of checkmark-grade and checkmark-count.</li>'.
                                        '<li>individual example-weights: the grade is the sum of examplegrades for each checked example (according to instance-settings).</li></ul>';
$string['autograde_strall'] = 'All submissions';
$string['autograde_stronesubmission'] = '1 submission';
$string['autograde_strmultiplesubmissions'] = '{$a} submissions';
$string['autograde_strreq'] = 'Submissions which require a grade-update';
$string['autogradebuttonstitle'] = 'Calculate submission-grades for checkmark {$a}';
$string['autograde_failed'] = 'Autograding failed!';
$string['autograde_success'] = 'Autograde successful! {$a} submissions updated.';
$string['autograde_error'] = 'An error occured during autograding.';
$string['autograde_confirm'] = 'You are about to update grades and feedback for {$a}. These will be overwritten!';
$string['autograde_notsupported'] = 'This scale is not supported by automatic grading.';
$string['autograde_no_users_selected'] = 'You have not selected any user to grade. Select the required users via the checkboxes in the left column of the table below.';
$string['checkmark_overviewsummary'] = 'You\'ve checked {$a->checked_examples} / {$a->total_examples} examples ({$a->checked_grade} / {$a->total_grade} points)<br />{$a->grade}<br />';
$string['checkmark_summary'] = 'You\'ve checked <span id="examples">{$a->checked_examples}</span> out of {$a->total_examples} examples.<br />(<span id="grade">{$a->checked_grade}</span> out of a maximum of {$a->total_grade} points)';
$string['checkmarkstatstitle'] = 'Courseoverview Checkmark';
$string['checkmark:view_preview'] = 'Preview';
$string['count_individuals_mismatch'] = 'The amount of individual names({$a->namecount}) doesn\'t match the amount of individual grades({$a->gradecount})!';
$string['datasettingstitle'] = 'Data settings';
$string['data_settings'] = 'Print settings';
$string['data_preview_help'] = 'Click on [+] or [-] for showing or hiding columns in the print-preview.';
$string['data_preview'] = 'Data preview';
$string['duedatevalidation'] = 'Due date must be after the available from date.';
$string['element_disabled'] = 'The following element is disabled because of existing submissions.';
$string['elements_disabled'] = 'The following elements are disabled because of existing submissions.';
$string['end_of_submission_for'] = 'End of submission for {$a}';
$string['examplegrades'] = 'Individual grades';
$string['examplegrades_help'] = 'Defines the weight of every example. These will be separated by the delimiter given in parentheses.';
$string['examplenames'] = 'Individual names';
$string['examplenames_help'] = 'Defines the example names. These will be separated by the delimiter given in parentheses.';
$string['example_preview_title'] = 'Example preview';
$string['example_preview_title_help'] = 'The example preview shows an overview of the checkmark options.';
$string['firstexamplenumber'] = 'Number of first example';
$string['firstexamplenumber_help'] = 'Number of the first example. The following examples get by 1 increased numbers of their predecessor.';
$string['flexiblenaming'] = 'Individual naming/grading';
$string['flexiblenaming_help'] = 'If activated individual names and grades are beeing used for each example.';
$string['gradesum_mismatch'] = 'The sum of the individual grades won\'t match the chosen total grades ({$a->gradesum}/{$a->maxgrade})!';
$string['grade_mismatch'] = 'The grade has to be an integral multiple of the amount of examples!';
$string['grade'] = 'grade';
$string['grade_help'] = 'When individual functions are activated the grade has to be the sum of all examplegrades (max 100).<br />When you\'re using standardexamples the grade has to be a integral multiple of the examplecount. If so the points per example get automatically adjusted.<br />With activated JavaScript the grade gets selected automatically when using individual functions. In the case of usage of standard-examples with activated JavaScript only integral multiples of the examplecount will be accepted.';
$string['nostudents'] = 'There are no users to be displayed!';
$string['nostudentsmatching'] = 'There are no users matching the current settings!';
$string['nosubmission'] = 'No checkmarks have been submitted';
$string['nousers'] = 'There are no users to be displayed!';
$string['numberofexamples'] = 'Number of examples';
$string['numberofexamples_help'] = 'Examplecount in this checkmark.';
$string['optimum'] = 'optimum';
$string['printsettingstitle'] = 'Print settings';
$string['strallononepage'] = 'print all on one page';
$string['strautograded'] = '[autograded]';
$string['strexample'] = 'example';
$string['strexamples'] = 'examples';
$string['strlandscape'] = 'landscape';
$string['strlarge'] = 'large';
$string['strmedium'] = 'medium';
$string['strpageorientation'] = 'Page orientation';
$string['strpapersizes'] = 'paper size';
$string['strportrait'] = 'portrait';
$string['strpoint'] = 'point';
$string['strpoints'] = 'points';
$string['strprint'] = 'Create PDF';
$string['strprintheader'] = 'Print header/footer';
$string['strprintheader_help'] = ' print header/footer if checked';
$string['strprintheaderlong'] = 'print header/footer if checked';
$string['strprintpreview'] = 'Print preview';
$string['strprintpreviewtabalt'] = 'Open print preview';
$string['strrefreshdata'] = 'Update data preview';
$string['strsmall'] = 'small';
$string['strstdexamplecount'] = 'Examplecount';
$string['strstdexamplecountdesc'] = 'is the amount of how many examples will be used by default';
$string['strstdexamplestart'] = 'First example number';
$string['strstdexamplestartdesc'] = 'is the number of the first example';
$string['strstdnames'] = 'Standardnames';
$string['strstdnamesdesc'] = 'Examplenames if "individual naming" is used';
$string['strstdgrades'] = 'Standardgrades';
$string['strstdgradesdesc'] = 'Grades if "individual naming" is used';
$string['strsubmissions'] = 'submissions';
$string['strsubmissionstabalt'] = 'open submissions view';
$string['strsum'] = 'Sum';
$string['strtextsize'] = 'Textsize';
$string['strvalidmsgtime'] = 'Duration of message validity';
$string['strvalidmsgtimedesc'] = 'Amount of days after which grading notifications to users won\'t be sent anymore.';
$string['str_userid'] = 'Student ID';
/*
 * End of Block for checkmark specific strings
 */

/*
 * Block with standard-checkmark-strings (adopted to checkmark)
 */
$string['allowresubmit'] = 'Allow resubmitting';
$string['allowresubmit_help'] = 'If enabled, students will be allowed to resubmit checkmarks after they have been graded (to be regraded).';
$string['alreadygraded'] = 'Your submission has already been graded and resubmission is not allowed.';
$string['checkmarkdetails'] = 'Checkmark details';
$string['checkmark:grade'] = 'Grade checkmark';
$string['checkmarkmail'] = '{$a->teacher} posted feedback on your
checkmark submission for \'{$a->checkmark}\'

You can see it appended to your checkmark submission:

    {$a->url}';
$string['checkmarkmailhtml'] = '{$a->teacher} posted feedback on your
checkmark submission for \'<i>{$a->checkmark}</i>\'<br /><br />
You can see it appended to your <a href="{$a->url}">checkmark submission</a>.';
$string['checkmarkmailsmall'] = '{$a->teacher} posted feedback on your
checkmark submission for \'{$a->checkmark}\' You can see it appended to your submission';
$string['checkmarkname'] = 'Checkmark name';
$string['checkmark:submit'] = 'Submit checkmark';
$string['checkmarksubmission'] = 'Checkmark submissions';
$string['checkmark:view'] = 'View checkmark';
$string['availabledate'] = 'Available from';
$string['cannotviewcheckmark'] = 'You can not view this checkmark';
$string['comment'] = 'Comment';
$string['configshowrecentsubmissions'] = 'Everyone can see notifications of submissions in recent activity reports.';
$string['coursemisconf'] = 'Course is misconfigured';
$string['currentgrade'] = 'Current grade in gradebook';
$string['deleteallsubmissions'] = 'Delete all submissions';
$string['description'] = 'Description';
$string['downloadall'] = 'Download all checkmarks as .zip';
$string['due'] = 'Checkmark due';
$string['duedate'] = 'Due date';
$string['duedateno'] = 'No due date';
$string['early'] = '{$a} early';
$string['editmysubmission'] = 'Edit my submission';
$string['addsubmission'] = 'Add submission';
$string['emailstudents'] = 'Email alerts to students';
$string['emailteachermail'] = '{$a->username} has updated the checkmark submission
for \'{$a->checkmark}\' at {$a->timeupdated}.

It is available here:

    {$a->url}';
$string['emailteachermailhtml'] = '{$a->username} has updated the checkmark submission
for <i>\'{$a->checkmark}\'  at {$a->timeupdated}</i><br /><br />
It is <a href="{$a->url}">available on the web site</a>.';
$string['emailteachers'] = 'Email alerts to teachers';
$string['emailteachers_help'] = 'If enabled, teachers receive email notification whenever students add or update a checkmark submission.

Only teachers who are able to grade the particular checkmark are notified. For example, if the course uses separate groups, teachers restricted to particular groups won\'t receive notification about students in other groups.';
$string['emptysubmission'] = 'You have not submitted anything yet';
$string['enablenotification'] = 'Send notifications';
$string['enablenotification_help'] = 'If enabled, students will be notified once their checkmark submissions are graded.';
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
$string['maximumgrade'] = 'Maximum grade';
$string['messageprovider:checkmark_updates'] = 'Checkmark notifications';
$string['modulename'] = 'Checkmark';
$string['modulename_help'] = 'Checkmarks enable the teacher to specify an assignment where students have to check marks which can then be graded.';
$string['modulenameplural'] = 'Checkmarks';
$string['newsubmissions'] = 'Checkmarks submitted';
$string['nocheckmarks'] = 'There are no checkmarks yet';
$string['noattempts'] = 'No attempts have been made on this checkmark';
$string['nomoresubmissions'] = 'No further submissions are allowed.';
$string['nonnegativeintrequired'] = 'Needs to be greater or equal zero (>= 0) and integral';
$string['notavailableyet'] = 'Sorry, this checkmark is not yet available.<br />Checkmark instructions will be displayed here on the date given below.';
$string['notgradedyet'] = 'Not graded yet';
$string['norequiregrading'] = 'There are no checkmarks that require grading';
$string['nosubmisson'] = 'No checkmarks have been submitted';
$string['notsubmittedyet'] = 'Not submitted yet';
$string['operation'] = 'Operation';
$string['optionalsettings'] = 'Optional settings';
$string['page-mod-checkmark-x'] = 'Every checkmark module page';
$string['page-mod-checkmark-view'] = 'Checkmark module main page';
$string['page-mod-checkmark-submissions'] = 'Checkmark module submission page';
$string['pagesize'] = 'Submissions shown per page';
$string['popupinnewwindow'] = 'Open in a popup window';
$string['posintrequired'] = 'Needs to be greater than zero (> 0) and integral';
$string['pluginadministration'] = 'Checkmark administration';
$string['pluginname'] = 'Checkmark';
$string['preventlate'] = 'Prevent late submissions';
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
$string['usermisconf'] = 'User is misconfigured';
$string['usernosubmit'] = 'Sorry, you are not allowed to submit a checkmark.';
$string['viewfeedback'] = 'View checkmark grades and feedback';
$string['viewmysubmission'] = 'View my submission';
$string['viewsubmissions'] = 'View {$a} submitted checkmarks';
$string['yoursubmission'] = 'Your submission';
/*
 * End of block with standard-strings
 */
