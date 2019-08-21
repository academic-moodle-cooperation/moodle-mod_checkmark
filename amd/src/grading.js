define(['jquery', 'core/str'], function ($, str) {

    var State = function (name,state) {
        this.name = name;
        this.state = state;
    };

    var Grading = function (originalstate) {
        this.originalState = originalstate;
    };

    Grading.prototype.toogleOverwiteHint = function (event) {
        $('.' + event.target.id).toggle();
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
            $(document).ready(function () {
                $('input.examplecheck').change(function (event) {
                    grading.toogleOverwiteHint(event);
                    grading.setPoints(grading.calculateSum());
                });
                /*
                Codereview SN:
                when you use the function in that way - grading.resetOverwrite, and not function() {grading.resetOverwrite()},
                then you don't have access to "this" object inside resetOverwrite function
                If it is a static function, then it's fine. But in the code it says console.log(this.originalState)
                Well the thing with this code is that I thought the "Nachkreuzen" feature gets extra controls but that wasn't the case. As there is no reset button, there is no need for resetting the checks

                 */
                $('#id_xgrade').change(grading.resetFeedback);
            });

        }
    };
});