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
 * settings.js
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_checkmark/settings
  */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_checkmark/settings
     */
    var Settings = function() {
        this.dividingSymbol = ',';
    };

    /*
     * updateSettings() updates the grade-selector appropriate to the given
     * individual grades (flexiblenaming = 1) or the given amount of examples (flexiblenaming = 0)
     *
     * @return true if everything's allright (no error handling by now)
     */
    Settings.prototype.updateSettings = function(e) {
        var gradeSum = 0;
        var i = 0;

        // First we strip everything we don't need!
        e.data.stripper(e);

        var typeSelector = '#id_grade_modgrade_type';
        var pointSelector = '#id_grade_modgrade_point';
        var flexibleNamingSelector = '#id_flexiblenaming';
        var exampleGradesSelector = '#id_examplegrades';
        var exampleNamesSelector = '#id_examplenames';
        var exampleCountSelector = '#id_examplecount';

        // If non-numeric scales are used or checkmark isn't graded at all, ignore changes!
        if ($(typeSelector).val() !== 'point') {
            return true;
        }
        if ($(flexibleNamingSelector).prop('checked')) {
            // Calculate gradesum using individual grades list!
            // Replace occurences of more than 1 comma in row through a single one...
            var regex1 = new RegExp(e.data.dividingSymbol + "{2,}", "g");
            $(exampleGradesSelector).val($(exampleGradesSelector).val().replace(regex1, e.data.dividingSymbol));
            // Strip trailling and following commata!
            if (e.type !== 'valuechange') {
                var regex2 = new RegExp("^" + e.data.dividingSymbol + "*|" + e.data.dividingSymbol + "*$", "g");
                $(exampleGradesSelector).val($(exampleGradesSelector).val().replace(regex2, ""));
                $(exampleNamesSelector).val($(exampleNamesSelector).val().replace(regex2, ""));
            }
            // Get string and strip every character except "," (comma) and numerics!
            var regex3 = new RegExp("[^0-9" + e.data.dividingSymbol + "]");
            var tempString = $(exampleGradesSelector).val().replace(regex3, "");
            var tempArray = tempString.split(e.data.dividingSymbol);
            for (i = 0; i < tempArray.length; i++) {
                if (tempArray[i].replace(/[^\d]/g, "") !== "") {
                    gradeSum += parseInt(tempArray[i].replace(/[^\d]/g, ""));
                }
            }
        } else {
            // Calculate gradesum using example-amount (each example counts 1 point)!
            gradeSum = $(exampleCountSelector).val();
        }

        if (!$(flexibleNamingSelector).prop('checked')) {
            if ($(pointSelector).val() % gradeSum === 0) {
                // Grade is integral multiple of gradesum (= examplecount) so everything's fine!
                return true;
            }
        }
        if ((gradeSum <= 100) && (gradeSum > 0)) {
            $(typeSelector).val('point');
            $(pointSelector).val(gradeSum);
        } else if (gradeSum < 0) {
            $(typeSelector).val('scale');
        }

        return true;
    };

    Settings.prototype.stripper = function(e) {
        if ((e !== null) && (e.which <= 46)) { // That means: no written character!
            return true;
        }
        var exampleGradesSelector = '#examplegrades';
        if (!$(exampleGradesSelector).length) {
            // Compatibility to pre 2.2 and current needed ID - TODO: do we need this anymore?
            exampleGradesSelector = '#id_examplegrades';
        }

        var regex = new RegExp("[^0-9" + e.data.dividingSymbol + "]", "g");
        $(exampleGradesSelector).val($(exampleGradesSelector).val().replace(regex, ""));
        return true;
    };

    var instance = new Settings();

    /*
     * initializer(config) prepares settings form for JS-functionality
     */
    instance.initializer = function(config) {
        instance.dividingSymbol = config.dividingSymbol;

        log.info('Initialize settings JS', 'checkmark');

        var typeSelector = '#id_grade_modgrade_type';
        var flexibleNamingSelector = "#id_flexiblenaming";
        var exampleGradesSelector = "#id_examplegrades";
        var exampleNamesSelector = "#id_examplenames";
        var exampleCountSelector = "#id_examplecount";
        $(flexibleNamingSelector).click(instance, instance.updateSettings);
        $(typeSelector).change(instance, instance.updateSettings);
        $(exampleGradesSelector).change(instance, instance.updateSettings);
        $(exampleNamesSelector).change(instance, instance.updateSettings);
        $(exampleCountSelector).change(instance, instance.updateSettings);
        $(exampleGradesSelector).blur(instance, instance.updateSettings);
        $(exampleNamesSelector).blur(instance, instance.updateSettings);
        $(exampleCountSelector).blur(instance, instance.updateSettings);
        $(exampleGradesSelector).keyup(instance, instance.stripper);

        if ($("input[name=allready_submit]").val() === 'no') {
            instance.updateSettings({data: instance});
        }
    };

    return instance;
});
