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
            url: baseurl,
            data: {
                hide: key,
                columns: allexamples
            },
            statusCode: {
                200: function () {
                    var url = window.location.href;
                    url = url.replace(/thide=[a-z0-9]+/, '') /* Removes thide=....3242 !*/
                             .replace(/tshow=[a-z0-9]+/, '') /* Removes tshow=....3432 ! */
                             .replace(/\?&/, '?') /* Removes accidentally left ?&.. */
                             .replace(/&&/, '&') /* Removes accidentally left &&.. */
                             .replace(/&$/, ''); /* Removes trailing &.. */
                    window.location.replace(url);
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
        return $('th.colexample > .commands').length === 0;
    };
    Utils.prototype.getBaseUrl = function () {
        return this.baseurl;
    };
    Utils.prototype.clearPointerEventsFromIcons = function () {
        $('.fa-minus,.fa-plus').css('pointer-events','none');
    };
    return {
        init: function () {
            var utils = new Utils();
            var showallContainer = '<th><div id="showallcontainer">';
            showallContainer += '<a id="showall" href="javascript:void(0)">' +
                '<i class="icon fa fa-plus fa-fw " id="showalltoggle"></i></a>';
            showallContainer += '</div></th>';
            $('th.timesubmitted').after(showallContainer);
            var showallPlaceholder = '<td></td>';
            $('td.timesubmitted').after(showallPlaceholder);


            /*
            The additional div and position: absolute is necessary so the tag is not becoming
            part of the flow of the example column thus making it bigger than the others
             */
            var hideallContainer = '<div id="hideallcontainer" style="position: absolute">';
            hideallContainer += '<span id="hidealllabel" style="margin-right: 5px"></span>';
            hideallContainer += '<a id="hideall" href="javascript:void(0);">';
            hideallContainer += '<i class="icon fa fa-minus fa-fw " id="hidealltoggle"></i></a>';
            hideallContainer += '</div><div>&nbsp;</div>';
            $('th.colexample:eq(0)').prepend(hideallContainer);

            var showallColgroup = '<colgroup class="showall" span="1"><col></colgroup>';
            console.log($('colgroup.timesubmitted'));
            $('colgroup.timesubmitted').after(showallColgroup);

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
                $('#showall').prop('aria-label', results[0]).prop('title', results[0]);
                $('#hideall').prop('aria-label', results[1]).prop('title', results[1]);
                $('#hidealllabel').text(results[2]);
            });

            if ($("th.colexample").length > 0 && !utils.allExamplesCollapsed()) {
                $('#hideallcontainer').show();
                $('#showallcontainer').hide();
            } else {
                $('#hideallcontainer').hide();
                $(".colexample").hide();
                $('#showallcontainer').show();
                $('colgroup.examples').hide();
            }

            $(document).ready(function () {
                $('#hideall').click(function () {
                    utils.toggleExamples(false);
                });
                $('#showall').click(function () {
                    utils.toggleExamples(true);
                });
                utils.clearPointerEventsFromIcons();
            }

            );
            baseurl = utils.getBaseUrl();
        }
    };
});
