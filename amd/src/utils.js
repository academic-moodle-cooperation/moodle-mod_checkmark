// Codereview SN: Get rid of sample comments from tutorials ;)
// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery', 'core/str'], function ($) {
// Codereview SN: make sure that you don't have double empty lines one after the other!

    var Utils = function () {
    };
    var baseurl;
    var loadedclosed = false;
    // Codereview SN: from what I understand, the Utils is a basic class that does a few functions. In order to
    // implement it correctly, you have to assign the function definitions to the prototype of Utils class, and not
    // directly to the object - https://docs.moodle.org/dev/Javascript_Modules#.22Hello_World.22_I_am_a_Javascript_Module :)
    // here you can have a look at the way the greeting class is implemented.
    // When you implement the class that way, you can use "this." inside class functions (as in other languages), and
    // also assign local-to-the-class-instance variables.
    // Here, toggleExamples should look like: (btw, collapseOrExpand can be shortened to toggle :) Or if you keep
    // the name, make sure that the "or" in the name is also capitalized.

    Utils.prototype.toggleExamples = function (show) {
        if (show) {
            this.getForAllExamples('false');
            if (loadedclosed) {
                location.reload();
                return;
            }
            $("th.colexample").show();
            $('#showall').hide();
            $('#hideall').show();
        } else {
            this.getForAllExamples('true');
            $("th.colexample").hide();
            $('#showall').show();
            $('#hideall').hide();
        }
    };

    Utils.prototype.getForAllExamples = function (key) {
        var allexamples = this.getExampleSelectors();
        $.ajax({
            url: baseurl, data: {
                hide: key,
                columns: allexamples
            }
        });
        // ...$.get(this.getBaseUrl()+"?hide=" + key + "&columns=" + allexamples.join(','));!
    };

    // Codereview SN: replace the forEach loopp with a regex that gets the example... name
    // sth like /(example[0-9]+)/;
    // What would be the difference? I need to iterate through the concrete example anyways, don't I?
    Utils.prototype.getExampleSelectors = function () {
        var allexamples = [];

        $("th.colexample").each(function () {

            var classes = $(this).attr("class");
            var classes_arr = classes.split(" ");
            classes_arr.forEach(function (value) {
                if (value.startsWith("example")) {
                    allexamples.push(value);
                }
            });
        });

        return allexamples;
    };
    Utils.prototype.allExamplesCollapsed = function () {
        var stat = true;
        $("th.colexample").each(function () {
            var val = $(this).children('.commands').length;
            if (val > 0) {
                stat = false;
            }
        });
        return stat;
    };
    Utils.prototype.getBaseUrl = function () {
        var url = window.location.href;
        url = url.slice(0, url.lastIndexOf('/')) + "/handlehideall.php";
        return url;
    };
    return {
        init: function () {
            var utils = new Utils();

            $("th.timesubmitted").prepend('<div><a title="Show All" id="showall" aria-expanded="false" ' +
                'aria-controls="mod-checkmark-submissions_r0_c3 mod-checkmark-submissions_r1_c3 mod-checkmark-submissions_r2_c3 ' +
                'mod-checkmark-submissions_r3_c3" ' + 'href="javascript:void(0)">' +
                '<i class="icon fa fa-plus fa-fw " id="showalltoggle" title="Show" aria-label="Show"></i></a></div>');
            $("th.colexample:eq(" + 0 + ")").prepend('<div><a title="Hide All" id="hideall" aria-expanded="true" ' +
                'aria-controls="mod-checkmark-submissions_r0_c8 mod-checkmark-submissions_r1_c8 mod-checkmark-submissions_r2_c8 ' +
                'mod-checkmark-submissions_r3_c8" ' + 'href="javascript:void(0)"><i class="icon fa fa-minus fa-fw "' +
                ' id="hidealltoggle" title="Hide" aria-label="Hide"></i></a></div>');

            require(['core/str'], function (str) {

                var showalltooglePresent = str.get_string('showalltoogle', 'checkmark', '', '');
                var hidealltooglePresent = str.get_string('hidealltoogle', 'checkmark', '', '');

                // Question SN: I have tried roughly 5 hours to make this work but couldn't figure out how.
                $.when(showalltooglePresent).done(function (localizedEditString) {
                    $('#showalltoogle').prop('aria-label', localizedEditString);
                    $('#showalltoogle').prop('title', localizedEditString);
                });
                $.when(hidealltooglePresent).done(function (localizedEditString) {
                    $('#hidealltoogle').prop('aria-label', localizedEditString);
                    $('#hidealltoogle').prop('title', localizedEditString);
                });
            });

            if ($("th.colexample").length > 0 && !utils.allExamplesCollapsed()) {
                $('#showall').hide();
            } else {
                $('#hideall').hide();
                utils.toggleExamples(false);
                loadedclosed = true;
            }
            $(document).ready(function () {
                $('#hideall').click(function () {
                    utils.toggleExamples(false);
                });
                $('#showall').click(function () {
                    utils.toggleExamples(true);
                });
            });
            baseurl = utils.getBaseUrl();
        }
    };
});
