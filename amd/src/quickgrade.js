define(['jquery', 'core/str'], function ($, str) {

    var name;
    var state;
    var State = function (name,state) {
        this.name = name;
        this.state = state;
    };

    var originalState;
    var Quickgrade = function (originalstate) {
        this.originalState = originalstate;
    };

    Quickgrade.prototype.toogleOverwiteHint = function (element) {
        console.log("Hurray!" + event.target.id);
        if($(element).hasClass('exborder')) {
            $(element).removeClass('exborder');
        } else {
            $(element).addClass('exborder');
        }

    };

    Quickgrade.prototype.resetOverwrite = function () {
        console.log(this.originalState);
    };

    Quickgrade.prototype.calculateSum = function (line) {
        var sum = 0;
        $('input.checkline' + line).each(function (index) {
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
        console.log(sum);
        return sum;
    };
    Quickgrade.prototype.setPoints = function (line,points) {
        console.log('#menumenu' + line);
        $('#menumenu' + line).val(points);
        /*
        var strings = [
            {
                key: 'strautograded',
                component: 'checkmark'
            },
        ];
        str.get_strings(strings).then(function (results) {
            $('#id_feedback_editoreditable:first-child').text(results[0]);
        });
        */
    };

    Quickgrade.prototype.resetFeedback = function () {
        $('#id_feedback_editoreditable:first-child').text('');
    };
    return {
        init: function () {
            var originalState = [];
            $('.overwritetag').each(function( index ) {
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