// Codereview SN: Get rid of sample comments from tutorials ;)
// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery', 'core/str'], function ($, str) {

    var Utils = function () {
        this.baseurl = M.cfg.wwwroot + "/mod/checkmark/handlehideall.php";
    };
    var baseurl;

    Utils.prototype.toggleExamples = function (show) {
        if (show) {
            this.getForAllExamples('false');
        } else {
            this.getForAllExamples('true');
        }
    };

    Utils.prototype.getForAllExamples = function (key) {
        var allexamples = this.getExampleSelectors();
        $.ajax({
            url: baseurl, data: {
                hide: key,
                columns: allexamples
            },
            statusCode: {
                200: function () {
                    if(key === 'false') {
                        location.reload();
                    }
                }
            }
        });
    };

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
        // Codereview SN: this can be shortened to:
        // Didn't get it to work with this command unfortunately
        // return $('th.colexample > .commands').length > 0;
        $("th.colexample").each(function () {
            var val = $(this).children('.commands').length;
            if (val > 0) {
                stat = false;
            }
        });
        return stat;
    };
    Utils.prototype.getBaseUrl = function () {
        return this.baseurl;
    };
    return {
        init: function () {
            var utils = new Utils();

            $("th.timesubmitted").prepend('<div id="hideallcontainer"><span id="showalllabel" style="margin-right: 5px">' +
                '</span><a title="Show All" id="showall" aria-expanded="false" ' +
                'aria-controls="mod-checkmark-submissions_r0_c3 mod-checkmark-submissions_r1_c3 mod-checkmark-submissions_r2_c3 ' +
                'mod-checkmark-submissions_r3_c3" ' + 'href="javascript:void(0)">' +
                '<i class="icon fa fa-plus fa-fw " id="showallcontainer" title="Show" aria-label="Show"></i></a></div>');
            $("th.colexample:eq(" + 0 + ")").prepend('<div id="showallcontainer" style="position: absolute; margin-top: -25px">' +
                '<span id="hidealllabel" style="margin-right: 5px "></span><a title="Hide All" id="hideall" aria-expanded="true" ' +
                'aria-controls="mod-checkmark-submissions_r0_c8 mod-checkmark-submissions_r1_c8 mod-checkmark-submissions_r2_c8 ' +
                'mod-checkmark-submissions_r3_c8" ' + 'href="javascript:void(0)"><i class="icon fa fa-minus fa-fw "' +
                ' id="hidealltoggle" title="Hide" aria-label="Hide"></i></a></div>');

            var strings = [ {
                    key: 'showalltoggle',
                    component: 'checkmark'
                },{
                    key: 'hidealltoggle',
                    component: 'checkmark'
                },{
                    key: 'strexamples',
                    component: 'checkmark'
                }
            ];

            str.get_strings(strings).then(function (results) {
                //console.log(results);
                $('#showalltoogle').prop('aria-label', results[0]).prop('title', results[0]);
                $('#hidealltoogle').prop('aria-label', results[1]).prop('title', results[1]);
                $('#showalllabel').text(results[2]);
                $('#hidealllabel').text(results[2]);
            });

            if ($("th.colexample").length > 0 && !utils.allExamplesCollapsed()) {
                $('#hideallcontainer').hide();
            } else {
                $('#hideallcontainer').hide();
                $("th.colexample").hide();
                $('#hideallcontainer').show();
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
