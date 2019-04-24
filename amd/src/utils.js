// Put this file in path/to/plugin/amd/src
// You can call it anything you like


define(['jquery'], function($) {


    var Utils = function(){};
    var checked = false;



    Utils.collapseorExpandExamples = function() {
        if(checked) {
            $("[class*=example]").show();
            checked = false;
        }
        else {
            $("[class*=example]").hide();
            checked = true;
        }
    };

    /*Utils.setLocString = require(['core/str'], function(str,strid,fieldid) {
        var string = str.get_string(strid,'checkmark','','');
        $.when(string).done(function(localizedEditString) {$(fieldid).text()=localizedEditString});
    });
    */


    return {
        init: function() {
            // Put whatever you like here. $ is available
            // to you as normal.

            $.when(this).done(function (){ $('#id_hideexamples').click(function(){Utils.collapseorExpandExamples()})});

        }
    };

});
