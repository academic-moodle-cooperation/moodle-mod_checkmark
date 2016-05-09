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
 * settings.js
 *
 * @package       mod_grouptool
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_grouptool/settings
  */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_grouptool/settings
     */
    var Settings = function() {
        this.dividing_symbol = ',';
    };

    /*
     * update_settings() updates the grade-selector appropriate to the given
     * individual grades (flexiblenaming = 1) or the given amount of examples (flexiblenaming = 0)
     *
     * @return true if everything's allright (no error handling by now)
     */
    Settings.prototype.update_settings = function(e) {
        var gradesum = 0;
        var i = 0;

        // First we strip everything we don't need!
        e.data.stripper(e);

        var type_selector = '#id_modgrade_type';
        var point_selector = '#id_modgrade_point';
        var flexiblenaming_selector = '#id_flexiblenaming';
        var examplegrades_selector = '#id_examplegrades';
        var examplenames_selector = '#id_examplenames';
        var examplecount_selector = '#id_examplecount';

        // If non-numeric scales are used or checkmark isn't graded at all, ignore changes!
        if ($(type_selector).val() != 'point') {
            return true;
        }
        if ($(flexiblenaming_selector).prop('checked')) {
            // Calculate gradesum using individual grades list!
            // Replace occurences of more than 1 comma in row through a single one...
            var regex1 = new RegExp(e.data.dividing_symbol + "{2,}", "g");
            $(examplegrades_selector).val($(examplegrades_selector).val().replace(regex1, e.data.dividing_symbol));
            // Strip trailling and following commata!
            if (e.type != 'valuechange') {
                var regex2 = new RegExp("^" + e.data.dividing_symbol + "*|" + e.data.dividing_symbol + "*$", "g");
                $(examplegrades_selector).val($(examplegrades_selector).val().replace(regex2, ""));
                $(examplenames_selector).val($(examplenames_selector).val().replace(regex2, ""));
            }
            // Get string and strip every character except "," (comma) and numerics!
            var regex3 = new RegExp("[^0-9" + e.data.dividing_symbol + "]");
            var temp_string = $(examplegrades_selector).val().replace(regex3, "");
            var temp_array = temp_string.split(e.data.dividing_symbol);
            for (i = 0; i < temp_array.length; i++) {
                if(temp_array[i].replace(/[^\d]/g, "") !== "") {
                    gradesum += parseInt(temp_array[i].replace(/[^\d]/g, ""));
                }
            }
        } else {
            // Calculate gradesum using example-amount (each example counts 1 point)!
            gradesum = $(examplecount_selector).val();
        }

        if (!$(flexiblenaming_selector).prop('checked')) {
            if ($(point_selector).val() % gradesum === 0) {
                // Grade is integral multiple of gradesum (= examplecount) so everything's fine!
                return true;
            }
        }
        if ((gradesum <= 100) && (gradesum > 0)) {
            $(type_selector).val('point');
            $(point_selector).val(gradesum);
        } else if (gradesum < 0) {
            $(type_selector).val('scale');
        }

        return true;
    };

    Settings.prototype.stripper = function(e) {
        if ((e !== null) && (e.which <= 46)) { // That means: no written character!
            return true;
        }
        var examplegrades_selector = '#examplegrades';
        if (!$(examplegrades_selector).length) {
            // Compatibility to pre 2.2 and current needed ID - TODO: do we need this anymore?
            examplegrades_selector = '#id_examplegrades';
        }
        var regex = new RegExp("[^0-9" + e.data.dividing_symbol + "]", "g");
        $(examplegrades_selector).val($(examplegrades_selector).val().replace(regex, ""));
        return true;
    };

    var instance = new Settings();

    /*
     * initializer(config) prepares settings form for JS-functionality
     */
    instance.initializer = function(config) {
        this.dividing_symbol = config.dividing_symbol;

        log.info('Initialize settings JS', 'checkmark');

        var type_selector = '#id_modgrade_type';
        var flexiblenaming_selector = "#id_flexiblenaming";
        var examplegrades_selector = "#id_examplegrades";
        var examplenames_selector = "#id_examplenames";
        var examplecount_selector = "#id_examplecount";
        $(flexiblenaming_selector).click(this, this.update_settings);
        $(type_selector).change(this, this.update_settings);
        $(examplegrades_selector).change(this, this.update_settings);
        $(examplenames_selector).change(this, this.update_settings);
        $(examplecount_selector).change(this, this.update_settings);
        $(examplegrades_selector).blur(this, this.update_settings);
        $(examplenames_selector).blur(this, this.update_settings);
        $(examplecount_selector).blur(this, this.update_settings);
        $(examplegrades_selector).keyup(this, this.stripper);

        if($("input[name=allready_submit]").val() === 'no') {
            this.update_settings({data: this});
        }
    };

    return instance;
});