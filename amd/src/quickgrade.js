define(['jquery', 'core/str'], function ($, str) {

    var State = function (name,state) {
        this.name = name;
        this.state = state;
    };

    var Quickgrade = function () {
    };

    Quickgrade.prototype.toogleOverwiteHint = function (element) {
        if($(element).hasClass('exborder')) {
            $(element).removeClass('exborder');
        } else {
            $(element).addClass('exborder');
        }

    };

    Quickgrade.prototype.calculateSum = function (line) {
        var sum = 0;
        $('input.checkline' + line).each(function () {
            if($(this).is(':checked')) {
                var classname = $(this).attr('class');
                var classes = classname.split(' ');
                classes.forEach(function (value) {
                    if (value.startsWith("$")) {
                        sum += parseFloat(value.substring(1));
                    }
                });
            }
        });
        return sum;
    };
    Quickgrade.prototype.setPoints = function (line,points) {
        $('#menumenu' + line).val(points);

        var strings = [
            {
                key: 'strautograded',
                component: 'checkmark'
            },
        ];
        str.get_strings(strings).then(function (results) {
            $('#feedback' + line).text(results[0]);
        });

    };

    Quickgrade.prototype.resetFeedback = function () {
        $('#id_feedback_editoreditable:first-child').text('');
    };
    return {
        init: function () {
            var originalState = [];
            $('.overwritetag').each(function() {
                originalState.push(new State(this.getAttribute('class'),this.style.display !== 'none'));
            });
            var quickgrade = new Quickgrade(originalState);
            quickgrade.originalState = originalState;
            $(document).ready(function () {
                $('input.examplecheck').change(function (event) {
                    var line = event.target.attributes['value'].nodeValue;
                    quickgrade.setPoints(line,quickgrade.calculateSum(line));
                    quickgrade.toogleOverwiteHint(event.target.parentElement.lastChild);
                });
                /*
                $('#id_xgrade').change(grading.resetFeedback);
                $('#id_resetbutton').click(grading.resetOverwrite);
                */
            });

        }
    };
});