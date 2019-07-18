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
                $('input.examplecheck').change(grading.toogleOverwiteHint);
                $('#id_resetbutton').click(grading.resetOverwrite);
            });

        }
    };
});