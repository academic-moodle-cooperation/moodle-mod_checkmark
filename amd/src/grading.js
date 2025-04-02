define(['jquery', 'core/str'], function($, str) {

    var State = function(name, state) {
        this.name = name;
        this.state = state;
    };

    var Grading = function(originalstate) {
        this.originalState = originalstate;
    };

    Grading.prototype.toggleOverwiteHint = function(event) {
        var curHint = $('.' + event.target.id);
        if (curHint.hasClass('d-none')) {
            curHint.removeClass("d-none");
        } else {
            curHint.addClass("d-none");
        }
    };
    Grading.prototype.calculateSum = function() {
        var sum = 0;
        $('input.examplecheck').each(function() {
            if ($(this).is(':checked')) {
                var classname = $(this).attr('class');
                var classes = classname.split(' ');
                classes.forEach(function(value) {
                    if (value.startsWith("$")) {
                        sum += parseFloat(value.substring(1));
                    }
                });
            }
        });
        return sum;
    };
    Grading.prototype.setPoints = function(points) {
        $('#id_xgrade').val(points);
        var strings = [
            {
                key: 'strautograded',
                component: 'checkmark'
            },
        ];
        str.get_strings(strings).then(function (results) {
            $('#id_feedback_editoreditable:first-child').text(results[0]);
        });
    };

    Grading.prototype.resetFeedback = function() {
        $('#id_feedback_editoreditable:first-child').text('');
    };
    return {
        init: function() {
            var originalState = [];
            $('.overwritetag').each(function() {
               originalState.push(new State(this.getAttribute('class'), this.style.display !== 'none'));
            });
            var grading = new Grading(originalState);
            $(document).ready(function() {
                $('input.examplecheck').change(function(event) {
                    grading.toggleOverwiteHint(event);
                    grading.setPoints(grading.calculateSum());
                });
                $('#id_xgrade').change(grading.resetFeedback);
            });

        }
    };
});