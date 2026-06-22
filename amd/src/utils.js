define(['jquery', 'core/str'], function($, str) {

    var Utils = function() {
        this.baseurl = M.cfg.wwwroot + "/mod/checkmark/handlehideall.php";
    };
    var baseurl;

    Utils.prototype.toggleColumns = function(show, columns) {
        this.setColumnsCollapsed(show ? 'false' : 'true', columns);
    };

    Utils.prototype.setColumnsCollapsed = function(key, columns) {
        $.ajax({
            url: baseurl,
            data: {
                hide: key,
                columns: columns
            },
            statusCode: {
                "200": function() {
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

    Utils.prototype.toggleExamples = function(show) {
        this.toggleColumns(show, this.getExampleSelectors());
    };

    Utils.prototype.togglePresentation = function(show) {
        this.toggleColumns(show, this.getPresentationSelectors());
    };

    Utils.prototype.getExampleSelectors = function() {
        var allexamples = [];

        $("th.colexample").each(function() {

            var classes = $(this).attr("class") || '';
            var classesArray = classes.split(" ");
            classesArray.forEach(function(value) {
                if (value.startsWith("example")) {
                    allexamples.push(value);
                }
            });
        });
        return allexamples;
    };
    Utils.prototype.getPresentationSelectors = function() {
        return ['presentationgrade', 'presentationfeedback', 'presentationtimemodified'];
    };
    Utils.prototype.allExamplesCollapsed = function() {
        return $('th.colexample > .commands').length === 0;
    };
    Utils.prototype.allPresentationCollapsed = function() {
        return $('th.presentationgrade > .commands, th.presentationfeedback > .commands, ' +
            'th.presentationtimemodified > .commands').length === 0;
    };
    Utils.prototype.getBaseUrl = function() {
        return this.baseurl;
    };
    Utils.prototype.clearPointerEventsFromIcons = function() {
        $('.fa-minus,.fa-plus').css('pointer-events', 'none');
    };
    Utils.prototype.positionExamplesToggle = function() {
        var toggle = $('#hideallcontainer');
        if (!toggle.is(':visible')) {
            return;
        }

        var firstExample = $('th.colexample').first();
        var lastExample = $('th.colexample').last();
        var scrollContainer = firstExample.closest('.no-overflow');
        if (!firstExample.length || !lastExample.length || !scrollContainer.length) {
            toggle.css('left', '');
            return;
        }

        var padding = 8;
        var firstLeft = firstExample.offset().left;
        var lastRight = lastExample.offset().left + lastExample.outerWidth();
        var viewportLeft = scrollContainer.offset().left;
        var viewportRight = viewportLeft + scrollContainer.outerWidth();
        var toggleWidth = toggle.outerWidth();
        var minimumLeft = firstLeft + padding;
        var maximumLeft = Math.min(lastRight - toggleWidth - padding, viewportRight - toggleWidth - padding);
        var preferredLeft = Math.max(minimumLeft, viewportLeft + padding);
        var boundedLeft = Math.min(preferredLeft, maximumLeft);

        toggle.css('left', Math.max(0, boundedLeft - firstLeft) + 'px');
    };
    return {
        init: function() {
            var utils = new Utils();
            baseurl = utils.getBaseUrl();
            var hasPresentation = $('#page-mod-checkmark-submissions colgroup.presentation').length > 0;
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
            var hideallContainer = '<div id="hideallcontainer" class="checkmark-examples-toggle">';
            hideallContainer += '<span id="hidealllabel"></span>';
            hideallContainer += '<a id="hideall" href="javascript:void(0);">';
            hideallContainer += '<i class="icon fa fa-minus fa-fw " id="hidealltoggle"></i></a>';
            hideallContainer += '</div><div>&nbsp;</div>';
            $('th.colexample:eq(0)').addClass('checkmark-first-example').prepend(hideallContainer);

            var showallColgroup = '<colgroup class="showall" span="1"><col></colgroup>';
            $('colgroup.timesubmitted').after(showallColgroup);

            if (hasPresentation) {
                var showpresentationContainer = '<th class="showpresentationcolumn">';
                showpresentationContainer += '<div id="showpresentationcontainer" class="checkmark-presentation-toggle">';
                showpresentationContainer += '<span id="showpresentationlabel"></span>';
                showpresentationContainer += '<a id="showpresentation" href="javascript:void(0)">';
                showpresentationContainer += '<i class="icon fa fa-plus fa-fw " id="showpresentationtoggle"></i></a>';
                showpresentationContainer += '</div></th>';
                var presentationHeaderAnchor = $('th.outcome').last();
                if (presentationHeaderAnchor.length === 0) {
                    presentationHeaderAnchor = $('th.finalgrade').last();
                }
                presentationHeaderAnchor.after(showpresentationContainer);

                var showpresentationPlaceholder = '<td class="showpresentationcolumn"></td>';
                if ($('td.outcome').length > 0) {
                    $('td.outcome').after(showpresentationPlaceholder);
                } else {
                    $('td.finalgrade').after(showpresentationPlaceholder);
                }

                var hidepresentationContainer = '<div id="hidepresentationcontainer" class="checkmark-presentation-toggle">';
                hidepresentationContainer += '<span id="hidepresentationlabel"></span>';
                hidepresentationContainer += '<a id="hidepresentation" href="javascript:void(0);">';
                hidepresentationContainer += '<i class="icon fa fa-minus fa-fw " id="hidepresentationtoggle"></i></a>';
                hidepresentationContainer += '</div>';
                $('th.presentationgrade, th.presentationfeedback, th.presentationtimemodified')
                    .first().prepend(hidepresentationContainer);

                var showpresentationColgroup = '<colgroup class="showpresentation" span="1"><col></colgroup>';
                $('colgroup.status_and_gradebook').after(showpresentationColgroup);
            }

            var strings = [{
                    key: 'showalltoggle',
                    component: 'checkmark'
                }, {
                    key: 'hidealltoggle',
                    component: 'checkmark'
                }, {
                    key: 'strexamples',
                    component: 'checkmark'
                }, {
                    key: 'showpresentationtoggle',
                    component: 'checkmark'
                }, {
                    key: 'hidepresentationtoggle',
                    component: 'checkmark'
                }, {
                    key: 'strpresentation',
                    component: 'checkmark'
                }
            ];

            str.get_strings(strings).then(function(results) {
                $('#showall').prop('aria-label', results[0]).prop('title', results[0]);
                $('#hideall').prop('aria-label', results[1]).prop('title', results[1]);
                $('#hidealllabel').text(results[2]);
                $('#showpresentation').prop('aria-label', results[3]).prop('title', results[3]);
                $('#hidepresentation').prop('aria-label', results[4]).prop('title', results[4]);
                $('#showpresentationlabel').text(results[5]);
                $('#hidepresentationlabel').text(results[5]);
                utils.positionExamplesToggle();
            });

            if ($("th.colexample").length > 0 && !utils.allExamplesCollapsed()) {
                $('#hideallcontainer').show();
                $('#showallcontainer').hide();
                utils.positionExamplesToggle();
            } else {
                $('#hideallcontainer').hide();
                $(".colexample").hide();
                $('#showallcontainer').show();
                $('colgroup.examples').hide();
            }

            if (hasPresentation) {
                if ($("th.presentationgrade, th.presentationfeedback, th.presentationtimemodified").length > 0 &&
                        !utils.allPresentationCollapsed()) {
                    $('#hidepresentationcontainer').show();
                    $('.showpresentationcolumn').hide();
                    $('colgroup.showpresentation').hide();
                } else {
                    $('#hidepresentationcontainer').hide();
                    $(".presentationgrade, .presentationfeedback, .presentationtimemodified").hide();
                    $('.showpresentationcolumn').show();
                    $('colgroup.presentation').hide();
                }
            }

            $(document).ready(function() {
                $('#hideall').click(function() {
                    utils.toggleExamples(false);
                });
                $('#showall').click(function() {
                    utils.toggleExamples(true);
                });
                $('th.colexample').first().closest('.no-overflow').on('scroll', function() {
                    utils.positionExamplesToggle();
                });
                $(window).on('resize', function() {
                    utils.positionExamplesToggle();
                });
                if (hasPresentation) {
                    $('#hidepresentation').click(function() {
                        utils.togglePresentation(false);
                    });
                    $('#showpresentation').click(function() {
                        utils.togglePresentation(true);
                    });
                }
                utils.clearPointerEventsFromIcons();
            }

            );
        }
    };
});
