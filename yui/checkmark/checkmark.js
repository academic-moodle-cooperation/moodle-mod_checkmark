// This file is part of Moodle - http://moodle.org/
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

/**
 * JavaScript-Functionality for checkmark based upon Assignment
 *
 * @package       mod
 * @subpackage    checkmark
 * @author        Philipp Hager
 * @copyright     2011 Philipp Hager
 * @since         Moodle 2.1
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
    init_submission:    function(Y, flexiblenaming, var1, var2, grade) {
            // ensure that data is accessible from all methods of M.mod_checkmark
            this.Y = Y;
            this.flexiblenaming = flexiblenaming;
            this.var1 = var1;
            this.var2 = var2;
            this.grade = grade;

            var i = 0;
            var id_fieldname = null;
            var example_count = 0;

            if (flexiblenaming) {
                example_count = this.var1.length;    //example count = number of example-names
            } else {
                example_count = this.var1;    //example count is allready given
            }

            i = 1;
            do {
                //@since Moodle 2.2.1
                id_fieldname = 'input#example'.concat(i.toString());
                if (Y.one(id_fieldname) == null) {
                    //Compatibility to pre 2.2
                    id_fieldname = 'input#id_example'.concat(i.toString());
                }
                Y.on('click', this.update_summary, id_fieldname);    //register event listener
                i++;
            } while (i <= example_count);

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
        var examples_new=0;
        var grade_new=0;
        //defining local variables improves readability
        var var1 = M.mod_checkmark.var1;
        var var2 = M.mod_checkmark.var2;
        var grade = M.mod_checkmark.grade;
        var flexiblenaming = M.mod_checkmark.flexiblenaming;

        if (flexiblenaming) {
            //calculate values using flexible naming (var1 = names[], var2 = grades[])
            for (var i=0; i<var1.length; i++) {
                var number = i+1;
                if (M.mod_checkmark.Y.one("input#example".concat(number.toString())) == null) {
                    //compatibility to pre 2.2
                    var is_checked = M.mod_checkmark.Y.one("input#id_example".concat(number.toString())).get('checked');
                } else {
                    var is_checked = M.mod_checkmark.Y.one("input#example".concat(number.toString())).get('checked');
                }

                if (is_checked) {
                    examples_new++;
                    grade_new += parseInt(var2[i]);
                }
            }
        } else {
            //calculate values using standard naming/grading (var1 = amount, var2 = start number)
            var count = 0;
            var i=0;
            var pointsperex = parseFloat(grade) / parseFloat(var1);
            for (i=1; i<=var1; i++) {
                if (M.mod_checkmark.Y.one("input#example"+i.toString()+'.checkboxgroup1') == null) {
                    //compatibility to pre 2.2
                    var is_checked = M.mod_checkmark.Y.one("input#id_example"+i.toString()+'.checkboxgroup1').get('checked');
                }
                else {
                    var is_checked = M.mod_checkmark.Y.one("input#example"+i.toString()+'.checkboxgroup1').get('checked');
                }
                if (is_checked == 1) {
                    count++;
                }
            }
            examples_new = count;
            grade_new = count*pointsperex;
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

        var grade_selector = '#grade';
        var flexiblenaming_selector = "#flexiblenaming";
        var examplegrades_selector = "#examplegrades";
        var examplecount_selector = "#examplecount";
        if (Y.one(grade_selector) == null) {
            //compatibility to pre 2.2
            grade_selector = '#id_grade';
            flexiblenaming_selector = "#id_flexiblenaming";
            examplegrades_selector = "#id_examplegrades";
            examplecount_selector = "#id_examplecount";
        }
        Y.on('change', this.update_settings, flexiblenaming_selector);
        Y.on('change', this.update_settings, examplegrades_selector);
        Y.on('change', this.update_settings, examplecount_selector);
        //Y.on('change', this.update_settings, grade_selector);
        //Y.on('keydown', this.stripper, "#id_examplegrades");
        //Y.on('keypress', this.stripper, "#id_examplegrades");
        Y.on('keyup', this.stripper, examplegrades_selector);

        if(M.checkmark_local.Y.one("input[name=allready_submit]").get('Value') == 'no') {
            this.update_settings();
        }
    },

    /*
     * update_settings() updates the grade-selector appropriate to the given
     * individual grades (flexiblenaming = 1) or the given amount of examples (flexiblenaming = 0)
     *
     * @return true if everything's allright (no error handling by now)
     */
    update_settings: function() {
        var gradesum = 0;
        var i = 0;

        //first we strip everything we don't need :)
        M.mod_checkmark.stripper(null);

        //if non-numeric scales are used or checkmark isn't graded at all, ignore changes
        var grade_selector = '#grade';
        var flexiblenaming_selector = '#flexiblenaming';
        var examplegrades_selector = '#examplegrades';
        var examplecount_selector = '#examplecount';
        if (M.mod_checkmark.Y.one(grade_selector) == null) {
            //compatibility to pre 2.2
            grade_selector = '#id_grade';
            flexiblenaming_selector = '#id_flexiblenaming';
            examplegrades_selector = '#id_examplegrades';
            examplecount_selector = '#id_examplecount';
        }
        if ((M.mod_checkmark.Y.one(grade_selector).get('value')==0) || (M.mod_checkmark.Y.one(grade_selector).get('value') == -1)) {
            return true;
        }
        if (M.mod_checkmark.Y.one(flexiblenaming_selector).get('checked')) {
            //calculate gradesum using individual grades list
            //replace occurences of more than 1 comma in row through a single one...
            Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/,{2,}/g, ","));
            //strip trailling and following commata
            Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/^,*|,*$/g, ""));
            //get string and strip every character except "," (comma) and numerics
            var temp_string = M.mod_checkmark.Y.one(examplegrades_selector).get('value').replace(/[^0-9,]/, "");
            var temp_array = temp_string.split(M.mod_checkmark.dividing_symbol);
            for (i=0; i<temp_array.length; i++) {
                gradesum += parseInt(temp_array[i]);
            }
        } else {
            //calculate gradesum using example-amount (each example counts 1 point)
            gradesum = M.mod_checkmark.Y.one(examplecount_selector).get('value');
        }

        /*
         * set grade field appropriate to gradesum
         * first two indices (0,1) are other scales
         * then it gets counted downward from 100
         * @todo replace fix index calculation
         *  with lookup of right value
         */
        //var index = 100-gradesum+2;
        grade_obj = M.mod_checkmark.Y.one(grade_selector);
        //myNode.get('children');
        //myNode.next();
        //myNode.one('> selectorString');

        if (!M.mod_checkmark.Y.one(flexiblenaming_selector).get('checked')) {
            if (M.mod_checkmark.Y.one(grade_selector).get('value')%gradesum == 0) {
                //grade is integral multiple of gradesum (= examplecount) so everything's fine
                return true;
            }
        }
        if ((gradesum <= 100) && (gradesum >= 0)) {
            if (M.mod_checkmark.Y.one(grade_selector.concat(" > [value=\'".concat(gradesum.toString()).concat("\']"))) == null) {
                //alert('M.mod_checkmark.Y.one('.concat(grade_selector).concat(" > [value=\'".concat(gradesum.toString()).concat("\']")).concat(') is null'));
                M.mod_checkmark.Y.one(grade_selector.concat(" > [value=\'-1\']")).set('selected', true);
            }
            M.mod_checkmark.Y.one(grade_selector.concat(" > [value=\'"+gradesum.toString()+"\']")).set('selected', true);
        } else {
            M.mod_checkmark.Y.one(grade_selector.concat(" > [value=\'-1\']")).set('selected', true);
        }
        //index = M.mod_checkmark.Y.one("#id_grade > [value="+gradesum+"]").get('index');
        //M.mod_checkmark.Y.one("#id_grade").set('selectedIndex', index);

        return true;
    },

    stripper: function(e) {
        Y = M.mod_checkmark.Y;
        if ((e != null) && (e.keyCode <= 46)) { //no written character
            return true;
        }
        var examplegrades_selector = '#examplegrades';
        if (Y.one(examplegrades_selector) == null) {
            //compatibility to pre 2.2
            examplegrades_selector = '#id_examplegrades';
        }
        Y.one(examplegrades_selector).set('value', Y.one(examplegrades_selector).get('value').replace(/[^0-9,]/g, ""));
        return true;
    },

    /*
     * init_printsettings(Y) prepares printsettings form for JS-functionality
     * first make Y accsesible in all other methods of M.mod_checkmark
     * second register event-listeners for all apropriate elements
     */
    init_printsettings: function(Y) {
        this.Y = Y;

        var combo = new Array();
        combo[0] = "input[name=\"printoptimum\"]";
        combo[1] = "input[name=\"printperpage\"]";
        alert('init!');
        Y.on('change', function(e, combo) {
                    alert('Change in Printoptimum!');
                    // Stop the event's default behavior
                    e.preventDefault();
                    // Stop the event from bubbling up the DOM tree
                    e.stopPropagation();
                    //deactivate textfield and set to 0 if checkbox is checked
                    if(M.mod_checkmark.Y.one(combo[0]).get('checked')) {
                        M.mod_checkmark.Y.one(combo[1]).set('disabled', 'disabled');
                    } else {
                        M.mod_checkmark.Y.one(combo[1]).set('disabled', '');
                    }
                }, combo[0]);
        Y.on('change', function(e, combo) {
                    alert('Change in Printperpage!');
                    // Stop the event's default behavior
                    e.preventDefault();
                    // Stop the event from bubbling up the DOM tree
                    e.stopPropagation();
                    //deactivate textfield and set to 0 if checkbox is checked
                    if(M.mod_checkmark.Y.one(combo[1]).get('value') == 0) {
                        M.mod_checkmark.Y.one(combo[0]).set('checked', 'checked');
                    } else {
                        M.mod_checkmark.Y.one(combo[0]).set('checked', '');
                    }
                }, combo[1]);
    }
};

/*
 * https://github.com/marxjohnson/moodle-block_quickfindlist/blob/dev/module.js - main example-source for this implementation
 */
