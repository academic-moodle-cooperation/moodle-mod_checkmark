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
 * checkboxcontroller.js
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_checkmark/checkboxcontroller
  */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_checkmark/checkboxcontroller
     */
    var Checkboxcontroller = function() {
        // Controller ID!
        this.ID = '';
        // Table ID!
        this.table = $('.usersubmissions table.submissions');
    };

    /**
     * Function updateSummary() updates the displayed summary during submission edit
     *
     * @param {Event} e event object
     * @return {bool} true if everything's alright (no error handling by now)
     */
    Checkboxcontroller.prototype.updateCheckboxes = function(e) {
        e.preventDefault();
        e.stopPropagation();

        var type = e.data.type;

        log.info('Update checkboxes (type = ' + type + ')');

        // The .checkboxgroup1 selector is needed so only selection checkboxes are checked or unchecked.
        if (type == 'all') {
            $(e.data.inst.table + ' input[type="checkbox"].checkboxgroup1').prop('checked', true);
        } else if (type == 'none') {
            $(e.data.inst.table + ' input[type="checkbox"].checkboxgroup1').prop('checked', false);
        } else {
            var checkboxes = $(e.data.inst.table + ' input[type="checkbox"]');

            checkboxes.each(function(idx, current) {
                if ($(current).data(type) == 1) {
                    $(current).prop('checked', true);
                } else {
                    $(current).prop('checked', false);
                }
            });
        }

        return true;
    };

    var instance = new Checkboxcontroller();

    /**
     * Initializer prepares checkmark-data and registers event-listeners for each checkbox
     *
     * @param {array} params contains table and ID selectors
     * @return {bool} true if everything's ok (no error-handling implemented)
     */
    instance.initializer = function(params) {
        log.debug("Init checkboxcontroller (" + params.id + ")!\nfor table " + params.table, 'checkmark');

        if (params.table !== '') {
            instance.table = params.table;
        }
        if (params.id !== '') {
            instance.id = params.id;
        } else {
            instance.id = '';
        }

        $('#' + instance.id + ' a.all').on('click', null, {inst: this, type: 'all'}, this.updateCheckboxes);
        $('#' + instance.id + ' a.none').on('click', null, {inst: this, type: 'none'}, this.updateCheckboxes);
        $('#' + instance.id + ' a.submitted').on('click', null, {inst: this, type: 'submitted'}, this.updateCheckboxes);
        $('#' + instance.id + ' a.ungraded').on('click', null, {inst: this, type: 'ungraded'}, this.updateCheckboxes);

        return true;
    };

    return instance;
});
