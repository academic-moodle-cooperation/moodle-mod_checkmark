/**
 * JavaScript-Functionality for checkmark based upon Assignment
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_checkmark = M.mod_checkmark || {};

M.mod_checkmark = {
    init_tree:       function(Y, expand_all, htmlid) {
        Y.use('yui2-treeview', function(Y) {
            var tree = new YAHOO.widget.TreeView(htmlid);

            tree.subscribe("clickEvent", function(node, event) {
                // we want normal clicking which redirects to url
                return false;
            });

            if (expand_all) {
                tree.expandAll();
            }

            tree.render();
        });
    },

    /*
     * init_submission(Y, flexiblenaming, var1, var2) prepares checkmark-data
     * and registers event-listeners for each checkbox
     *
     * @param Y                    JS-namespace created by YUI-framework and moodle
     * @param flexiblenaming     boolean - true if individual names/grades are used
     * @param var1                amount of examples (flexiblenaming=0) or array with examplenames (flexiblenaming=1)
     * @param var2                starting number for examples (flexiblenaming=0) or array with examplegrades (flexiblenaming=1)
     * @return    true if everything's ok (no error-handling implemented)
     */
    init_submission:    function(Y, examples) {
            // ensure that data is accessible from all methods of M.mod_checkmark
            this.Y = Y;
            this.examples = examples;

            var id_fieldname = null;

            for (var key in examples) {
                //@since Moodle 2.2.1
                id_fieldname = 'input#example'.concat(key.toString());
                if (Y.one(id_fieldname) == null) {
                    //Compatibility to pre 2.2
                    id_fieldname = 'input#id_example'.concat(key.toString());
                }
                Y.on('click', this.update_summary, id_fieldname);    //register event listener
            }

            //register event-listener on reset-button to ensure proper data to be displayed on form-reset
            Y.on('click', this.reset_submission_form, "#id_resetbutton");

            //reset the formular after init to ensure correct checkbox-states after page-reload
            Y.one("#mform1").reset();

            //update summary to display correct data after form-reset
            this.update_summary();

            return true;
    },

    /*
     * update_summary() updates the displayed summary during submission edit
     * @return true if everything's allright (no error handling by now)
     */
    update_summary: function() {
        var examples_new = 0;
        var grade_new = 0;
        //defining local variables improves readability
        var examples = M.mod_checkmark.examples;

        //calculate values using flexible naming (var1 = names[], var2 = grades[])
        for (var key in examples) {
            if (M.mod_checkmark.Y.one("input#example".concat(key.toString())) == null) {
                //compatibility to pre 2.2
                var is_checked = M.mod_checkmark.Y.one("input[type=checkbox]#id_example".concat(key.toString())).get('checked');
            } else {
                var is_checked = M.mod_checkmark.Y.one("input[type=checkbox]#example".concat(key.toString())).get('checked');
            }
            if (is_checked) {
                examples_new++;
                grade_new += parseInt(examples[key].grade);
            }
        }

        M.mod_checkmark.Y.one("span#examples").setContent(examples_new.toString());
        M.mod_checkmark.Y.one("span#grade").setContent(grade_new.toString());
        //doesn't work with "this.Y.one(~~~)" - why???

        return true;
    },

    /*
     * reset_submission_form(e) reset method replaces std-reset-behaviour
     * first prevents default reset behaviour
     * second resets the form manually
     * third ensure to display updated data
     * @param e    event-object
     * @return true if everything's allright (no error handling by now)
     */
    reset_submission_form: function(e) {
        e.preventDefault();
        M.mod_checkmark.Y.one("#mform1").reset();
        M.mod_checkmark.update_summary();
        return true;
    },

    /*
     * init_settings(Y, dividing_symbol) prepares settings form for JS-functionality
     * first make Y and dividing_symbol accsesible in all other methods of M.mod_checkmark
     * second register event-listeners for all apropriate fields
     */
    init_settings: function(Y, dividing_symbol) {
        this.Y = Y;
        this.dividing_symbol = dividing_symbol;

        var type_selector = '#id_modgrade_type';
        var point_selector = '#id_modgrade_point';
        var flexiblenaming_selector = "#id_flexiblenaming";
        var examplegrades_selector = "#id_examplegrades";
        var examplenames_selector = "#id_examplenames";
        var examplecount_selector = "#id_examplecount";
        Y.on('change', this.update_settings, flexiblenaming_selector);
        Y.on('valuechange', this.update_settings, type_selector);
        Y.on('valuechange', this.valuechange, type_selector);
        Y.on('valuechange', this.update_settings, examplegrades_selector);
        Y.on('valuechange', this.update_settings, examplenames_selector);
        Y.on('valuechange', this.update_settings, examplecount_selector);
        Y.on('blur', this.update_settings, examplegrades_selector);
        Y.on('blur', this.update_settings, examplenames_selector);
        Y.on('blur', this.update_settings, examplecount_selector);
        Y.on('keyup', this.stripper, examplegrades_selector);

        if(M.mod_checkmark.Y.one("input[name=allready_submit]").get('Value') == 'no') {
            this.update_settings();
        }
    },

    /*
     * update_settings() updates the grade-selector appropriate to the given
     * individual grades (flexiblenaming = 1) or the given amount of examples (flexiblenaming = 0)
     *
     * @return true if everything's allright (no error handling by now)
     */
    update_settings: function(e) {
        var gradesum = 0;
        var i = 0;

        // First we strip everything we don't need!
        M.mod_checkmark.stripper(null);

        var type_selector = '#id_modgrade_type';
        var point_selector = '#id_modgrade_point';
        var flexiblenaming_selector = '#id_flexiblenaming';
        var examplegrades_selector = '#id_examplegrades';
        var examplenames_selector = '#id_examplenames';
        var examplecount_selector = '#id_examplecount';

        // If non-numeric scales are used or checkmark isn't graded at all, ignore changes!
        if (M.mod_checkmark.Y.one(type_selector).get('value') != 'point') {
            return true;
        }
        if (M.mod_checkmark.Y.one(flexiblenaming_selector).get('checked')) {
            // Calculate gradesum using individual grades list!
            // Replace occurences of more than 1 comma in row through a single one...
            Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/,{2,}/g, ","));
            // Strip trailling and following commata!
            if (e.type != 'valuechange') {
                Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/^,*|,*$/g, ""));
                Y.one(examplenames_selector).set('value', Y.one(examplenames_selector).get('value').replace(/^,*|,*$/g, ""));
            }
            // Get string and strip every character except "," (comma) and numerics!
            var temp_string = M.mod_checkmark.Y.one(examplegrades_selector).get('value').replace(/[^0-9,]/, "");
            var temp_array = temp_string.split(M.mod_checkmark.dividing_symbol);
            for (i = 0; i < temp_array.length; i++) {
                if(temp_array[i].replace(/[^\d]/g, "") != "") {
                    gradesum += parseInt(temp_array[i].replace(/[^\d]/g, ""));
                }
            }
        } else {
            // Calculate gradesum using example-amount (each example counts 1 point)!
            gradesum = M.mod_checkmark.Y.one(examplecount_selector).get('value');
        }

        if (!M.mod_checkmark.Y.one(flexiblenaming_selector).get('checked')) {
            if (M.mod_checkmark.Y.one(point_selector).get('value') % gradesum == 0) {
                // Grade is integral multiple of gradesum (= examplecount) so everything's fine!
                return true;
            }
        }
        if ((gradesum <= 100) && (gradesum > 0)) {
            M.mod_checkmark.Y.one(type_selector.concat(" > [value=\'point\']")).set('selected', true);
            M.mod_checkmark.Y.one(point_selector).set('value', gradesum);
        } else if (gradesum < 0) {
            M.mod_checkmark.Y.one(scale_selector.concat(" > [value=\'scale\']")).set('selected', true);
        }

        return true;
    },

    stripper: function(e) {
        Y = M.mod_checkmark.Y;
        if ((e != null) && (e.keyCode <= 46)) { //no written character
            return true;
        }
        var examplegrades_selector = '#examplegrades';
        if (Y.one(examplegrades_selector) == null) {
            // Compatibility to pre 2.2
            examplegrades_selector = '#id_examplegrades';
        }
        Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/[^0-9,]/g, ""));
        return true;
    }
};

/*
 * https://github.com/marxjohnson/moodle-block_quickfindlist/blob/dev/module.js - main example-source for this implementation
 */
