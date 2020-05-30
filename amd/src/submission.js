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
 * submission.js
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_checkmark/submission
  */
define(['core/log', 'jquery'], function(log, $) {

    /**
     * @constructor
     * @alias module:mod_checkmark/submission
     */
    let Submission = function() {
    };

    /**
     * UpdateSummary updates the displayed summary during submission edit
     *
     * @return {boolean} true if everything's allright (no error handling by now)
     */
    Submission.prototype.updateSummary = function() {
        let examplesNew = 0;
        let gradeNew = 0;

        $('input[data-example]').each(function() {
            if (this.checked) {
                examplesNew++;
                gradeNew += parseInt(this.dataset.grade);
            }
        });

        $('#examples').html(examplesNew.toString());
        $('#grade').html(gradeNew.toString());
        return true;
    };

    /**
     * ResetSubmissionForm reset method replaces std-reset-behaviour
     *   I) prevents default reset behaviour
     *  II) resets the form manually
     * III) ensure to display updated data
     * @param {Event} e event-object
     * @return {boolean} true if everything's allright (no error handling by now)
     */
    Submission.prototype.resetSubmissionForm = function(event) {
        event.preventDefault();
        $('.submissionform')[0].reset();
        Submission.prototype.updateSummary();
        return true;
    };

    let instance = new Submission();

    /**
     * Initializer prepares checkmark-data and registers event-listeners for each checkbox
     *
     * @return {boolean} true if everything's ok (no error-handling implemented)
     */
    instance.initializer = function() {
        log.debug('Init checkmark submissions js!', 'checkmark');

        $('input[data-example]').on('click', instance.updateSummary);

        // Register event-listener on reset-button to ensure proper data to be displayed on form-reset!
        $('#id_resetbutton').on('click', this.resetSubmissionForm);

        // Reset the formular after init to ensure correct checkbox-states after page-reload!
        const form = $('.submissionform')[0];
        if (form) {
            form.reset();
        }


        // Update summary to display correct data after form-reset!
        this.updateSummary();

        return true;
    };

    return instance;
});
