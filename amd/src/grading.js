define(['jquery', 'core/str'], function ($, str) {

    var name;
    var state;
    var State = function (name,state) {
        this.name = name;
        this.state = state;
    };

    var originalState;
    var Grading = function (originalstate) {
        this.originalState = originalstate;
    };

    Grading.prototype.toogleOverwiteHint = function (event) {
        //console.log("Hurray!" + event.target.id);
        $('.' + event.target.id).toggle();
    };

    Grading.prototype.resetOverwrite = function () {
        console.log(this.originalState);
    };
    
    Grading.prototype.calculateSum = function () {
        var sum = 0;
        $('input.examplecheck').each(function (index) {
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
    Grading.prototype.setPoints = function (points) {
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

    Grading.prototype.resetFeedback = function () {
        $('#id_feedback_editoreditable:first-child').text('');
    };
    return {
        init: function () {
            var originalState = [];
            $('.overwritetag').each(function( index ) {
               originalState.push(new State(this.getAttribute('class'),this.style.display !== 'none'));
            });
            var grading = new Grading(originalState);
            grading.originalState = originalState;
            console.log(grading.originalState);
            $(document).ready(function () {
                $('input.examplecheck').change(function (event) {
                    grading.toogleOverwiteHint(event);
                    grading.setPoints(grading.calculateSum());
                });
                $('#id_xgrade').change(grading.resetFeedback);
                $('#id_resetbutton').click(grading.resetOverwrite);
            });

        }
    };
});