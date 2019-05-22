// Codereview SN: Get rid of sample comments from tutorials ;)
// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery', 'core/str'], function($, str) {
// Codereview SN: make sure that you don't have double empty lines one after the other!

    var Utils = function(){};

    var excount;
    var baseurl;
// Codereview SN: here too.


    // Codereview SN: from what I understand, the Utils is a basic class that does a few functions. In order to
    // implement it correctly, you have to assign the function definitions to the prototype of Utils class, and not
    // directly to the object - https://docs.moodle.org/dev/Javascript_Modules#.22Hello_World.22_I_am_a_Javascript_Module :)
    // here you can have a look at the way the greeting class is implemented.
    // When you implement the class that way, you can use "this." inside class functions (as in other languages), and
    // also assign local-to-the-class-instance variables.
    // Here, collapseorExpandExamples should look like: (btw, collapseOrExpand can be shortened to toggle :) Or if you keep
    // the name, make sure that the "or" in the name is also capitalized.

    Utils.prototype.collapseOrExpandExamples = function(show) {
        if (show) {
            this.getForAllExamples('tshow');
        }
    }

    Utils.prototype.collapseorExpandExamples = function(show) {
        // Codereview SN: make sure you have an empty space between if and () (valid for all constructions - if, for, while, switch, catch, try..)
        if(show) {
            Utils.getForAllExamples('tshow');
            $("th.colexample").show();
            $('#showall').hide();
            $('#hideall').show();
        }
        else {
            Utils.getForAllExamples('thide');
            $("th.colexample").hide();
            $('#showall').show();
            $('#hideall').hide();
        }
    };

    Utils.getForAllExamples = function (key) {
        var allexamples = Utils.getExampleSelectors();
        //console.log(path);
        allexamples.forEach(function (value) {
            $.ajax({
                url: baseurl,
                data: {
                    action: 'hide',
                    columns: []
                }
            });
            $.get(baseurl+"&" + key + "=" + value);
        });
    };

    // Codereview SN: replace the forEach loopp with a regex that gets the example... name
    // sth like /(example[0-9]+)/;
    Utils.getExampleSelectors = function () {
        var allexamples = [];

        $("th.colexample").each(function () {

            var classes = $(this).attr("class");
            var classes_arr  = classes.split(" ");
            classes_arr.forEach(function (value) {
                if(value.startsWith("example")) {
                    allexamples.push(value);
                }
            });
        });
        //console.log(allexamples);
        return allexamples;

    };
    Utils.getBaseUrl = function () {
        var url = $("th.colexample:eq(" + excount + ") a:last").attr('href');
        url = url.substring(0,url.lastIndexOf("&"));
        //console.log(url);
        return url;
    };

    /*Utils.setLocString = require(['core/str'], function(str,strid,fieldid) {
        var string = str.get_string(strid,'checkmark','','');
        $.when(string).done(function(localizedEditString) {$(fieldid).text()=localizedEditString});
    });
    */


    return {
        init: function() {
            // Codereview SN: get rid of sample code ;)
            // Put whatever you like here. $ is available
            // to you as normal.

            excount = Math.floor($("th.colexample").length / 2) - 1;
            //console.log(excount);


            // start retrieving the localized string; store the promise that some time in the future the string will be there.
            var editaPresent = str.get_string('edita', 'core');
            // as soon as the string is retrieved, i.e. the promise has been fulfilled,
            // edit the text of a UI element so that it then is the localized string
            // Note: $.when can be used with an arbitrary number of promised things
            $.when(editaPresent).done(function(localizedEditString) {


                $("th.timesubmitted").prepend('<div><a title="Show All" id="showall" aria-expanded="false" ' +
                    'aria-controls="mod-checkmark-submissions_r0_c3 mod-checkmark-submissions_r1_c3 mod-checkmark-submissions_r2_c3 ' +
                    'mod-checkmark-submissions_r3_c3" ' + 'href="javascript:void(0)">' +
                    '<i class="icon fa fa-plus fa-fw " title="Show" aria-label="Show"></i></a></div>');
                $("someUIElementSelector").text = localizedEditString;

            });
            $("th.colexample:eq(" + excount + ")").prepend('        <a title="Hide All" id="hideall" aria-expanded="true" ' +
                'aria-controls="mod-checkmark-submissions_r0_c8 mod-checkmark-submissions_r1_c8 mod-checkmark-submissions_r2_c8 ' +
                'mod-checkmark-submissions_r3_c8" ' + 'href="javascript:void(0)"><i class="icon fa fa-minus fa-fw "' +
                ' title="Hide" aria-label="Hide"></i></a></br>\n');

            if(excount > 0) {
                $('#showall').hide();
            }
            else {
                $('#hideall').hide();
            }

            var utils = new Utils();

            $(document).ready(function() {
                $('#hideall').click(function(){utils.collapseorExpandExamples(false);});
                $('#showall').click(function(){utils.collapseorExpandExamples(true);});
            });
            baseurl = utils.getBaseUrl();

        }
    };

});
