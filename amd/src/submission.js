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
define(['core/log'], function(log) {

    /**
     * @constructor
     * @alias module:mod_checkmark/submission
     */
    var Submission = function() {
    };

    /**
     * UpdateSummary updates the displayed summary during submission edit
     *
     * @return {boolean} true if everything's allright (no error handling by now)
     */
    Submission.prototype.updateSummary = function() {
        var examplesNew = 0;
        var gradeNew = 0;

        var els = document.getElementsByTagName('input');
        for(var i = 0; i < els.length; i++) {
            if (els[i].attributes['data-example'] === undefined) {
                continue;
            }
            if (els[i].checked) {
                examplesNew++;
                gradeNew += parseInt(els[i].dataset['grade']);
            }
        }

        document.getElementById('examples').innerHTML = examplesNew.toString();
        document.getElementById('grade').innerHTML = gradeNew.toString();

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
    Submission.prototype.resetSubmissionForm = function(e) {
        e.preventDefault();

        document.getElementById('mform1').reset();
        Submission.prototype.updateSummary();

        return true;
    };

    var instance = new Submission();

    /**
     * Initializer prepares checkmark-data and registers event-listeners for each checkbox
     *
     * @return {boolean} true if everything's ok (no error-handling implemented)
     */
    instance.initializer = function() {
        log.debug('Init checkmark submissions js!', 'checkmark');

        var els = document.getElementsByTagName('input');
        for(var i = 0; i < els.length; i++) {
            if (els[i].attributes['data-example'] == undefined) {
                continue;
            }
            els[i].addEventListener('click', instance.updateSummary);
        }

        // Register event-listener on reset-button to ensure proper data to be displayed on form-reset!
        document.getElementById('id_resetbutton').addEventListener('click', this.resetSubmissionForm);

        // Reset the formular after init to ensure correct checkbox-states after page-reload!
        document.getElementById('mform1').reset();

        // Update summary to display correct data after form-reset!
        this.updateSummary();

        return true;
    };

    return instance;
});
