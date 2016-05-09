// This file is part of mod_grouptool for Moodle - http://moodle.org/
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
 * @package       mod_grouptool
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_grouptool/submission
  */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_grouptool/submission
     */
    var Submission = function() {
        // Structure: examples = {exkey: {grade: exgrade, name: exname}}!
        this.examples = [];
    };

    /*
     * update_summary() updates the displayed summary during submission edit
     * @return true if everything's allright (no error handling by now)
     */
    Submission.prototype.update_summary = function(e) {
        var examples_new = 0;
        var grade_new = 0;
        // Defining local variables improves readability!
        var examples = e.data.examples;

        // Calculate values using flexible naming (var1 = names[], var2 = grades[])!
        $.each(examples, function(key, cur) {
            var is_checked = null;
            if ($("input#example".concat(key.toString())) === null) {
                // Compatibility to pre 2.2 and current needed ID - TODO: do we need this anymore?
                is_checked = $("input[type=checkbox]#id_example".concat(key.toString())).prop('checked');
            } else {
                is_checked = $("input[type=checkbox]#example".concat(key.toString())).prop('checked');
            }
            if (is_checked) {
                examples_new++;
                grade_new += parseInt(cur.grade);
            }
        });

        $("span#examples").html(examples_new.toString());
        $("span#grade").html(grade_new.toString());

        return true;
    };

    /*
     * reset_submission_form(e) reset method replaces std-reset-behaviour
     * first prevents default reset behaviour
     * second resets the form manually
     * third ensure to display updated data
     * @param e    event-object
     * @return true if everything's allright (no error handling by now)
     */
    Submission.prototype.reset_submission_form = function(e) {
        e.preventDefault();

        $("#mform1")[0].reset();
        e.data.update_summary(e);

        return true;
    };

    var instance = new Submission();

    /*
     * initializer(config) prepares checkmark-data
     * and registers event-listeners for each checkbox
     *
     * @param config contains object with all examples
     * @return    true if everything's ok (no error-handling implemented)
     */
    instance.initializer = function(params) {
            instance.examples = params.examples;

            log.debug('Init checkmark submissions js!', 'checkmark');

            var id_fieldname = null;

            $.each(this.examples, function(key) {
                id_fieldname = 'input#example'.concat(key.toString());
                log.debug('Attach click handler to ' + id_fieldname, 'checkmark');
                $(id_fieldname).click(instance, instance.update_summary);    // Register event listener!
            });

            // Register event-listener on reset-button to ensure proper data to be displayed on form-reset!
            $('#id_resetbutton').click(this, this.reset_submission_form);

            // Reset the formular after init to ensure correct checkbox-states after page-reload!
            $("#mform1")[0].reset();

            // Update summary to display correct data after form-reset!
            this.update_summary({data: this});

            return true;
    };

    return instance;
});
