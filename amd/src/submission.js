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
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_checkmark/submission
     */
    var Submission = function() {
        // Structure: examples = {exkey: {grade: exgrade, name: exname}}!
        this.examples = [];
    };

    /**
     * UpdateSummary updates the displayed summary during submission edit
     *
     * @param {Event} e event object
     * @return {bool} true if everything's allright (no error handling by now)
     */
    Submission.prototype.updateSummary = function(e) {
        var examplesNew = 0;
        var gradeNew = 0;
        // Defining local variables improves readability!
        var examples = e.data.examples;

        // Calculate values using flexible naming (var1 = names[], var2 = grades[])!
        $.each(examples, function(key, cur) {
            var isChecked = null;
            if ($("input#example".concat(key.toString())) === null) {
                // Compatibility to pre 2.2 and current needed ID - TODO: do we need this anymore?
                isChecked = $("input[type=checkbox]#id_example".concat(key.toString())).prop('checked');
            } else {
                isChecked = $("input[type=checkbox]#example".concat(key.toString())).prop('checked');
            }
            if (isChecked) {
                examplesNew++;
                gradeNew += parseInt(cur.grade);
            }
        });

        $("span#examples").html(examplesNew.toString());
        $("span#grade").html(gradeNew.toString());

        return true;
    };

    /**
     * ResetSubmissionForm reset method replaces std-reset-behaviour
     *   I) prevents default reset behaviour
     *  II) resets the form manually
     * III) ensure to display updated data
     * @param {Event} e event-object
     * @return {bool} true if everything's allright (no error handling by now)
     */
    Submission.prototype.resetSubmissionForm = function(e) {
        e.preventDefault();

        $("#mform1")[0].reset();
        e.data.updateSummary(e);

        return true;
    };

    var instance = new Submission();

    /**
     * Initializer prepares checkmark-data and registers event-listeners for each checkbox
     *
     * @param {Array} params contains object with all examples
     * @return {bool} true if everything's ok (no error-handling implemented)
     */
    instance.initializer = function(params) {
            instance.examples = params.examples;

            log.debug('Init checkmark submissions js!', 'checkmark');

            var idFieldname = null;

            $.each(this.examples, function(key) {
                idFieldname = 'input#example'.concat(key.toString());
                log.debug('Attach click handler to ' + idFieldname, 'checkmark');
                $(idFieldname).click(instance, instance.updateSummary);    // Register event listener!
            });

            // Register event-listener on reset-button to ensure proper data to be displayed on form-reset!
            $('#id_resetbutton').click(this, this.resetSubmissionForm);

            // Reset the formular after init to ensure correct checkbox-states after page-reload!
            $("#mform1")[0].reset();

            // Update summary to display correct data after form-reset!
            this.updateSummary({data: this});

            return true;
    };

    return instance;
});
