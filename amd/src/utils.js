// Put this file in path/to/plugin/amd/src
// You can call it anything you like


define(['jquery'], function($) {


    var Utils = function(){};

    var excount;
    var baseurl;



    Utils.collapseorExpandExamples = function(show) {
        if(show) {
            Utils.getForAllExamples('tshow');
            $("th.colexample").show();
            $('#showall').hide();
            $('#hideall').show();
        }
        else {
            Utils.getForAllExamples('thide');
            $("th.colexample").hide();
            $('#showall').show();
            $('#hideall').hide();
        }
    };
    Utils.getForAllExamples = function (key) {
        var allexamples = Utils.getExampleSelectors();
        //console.log(path);
        allexamples.forEach(function (value) {
            $.get(baseurl+"&" + key + "=" + value);
        });
    };
    Utils.getExampleSelectors = function () {
        var allexamples = [];
        $("th.colexample").each(function () {
            var classes = $(this).attr("class");
            var classes_arr  = classes.split(" ");
            classes_arr.forEach(function (value) {
                if(value.startsWith("example")) {
                    allexamples.push(value);
                }
            });
        });
        //console.log(allexamples);
        return allexamples;

    };
    Utils.getBaseUrl = function () {
        var url = $("th.colexample:eq(" + excount + ") a:last").attr('href');
        url = url.substring(0,url.lastIndexOf("&"));
        //console.log(url);
        return url;
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

            excount = Math.floor($("th.colexample").length / 2) - 1;
            //console.log(excount);
            $("th.timesubmitted").prepend('<a title="Show All" id="showall" aria-expanded="false" ' +
                'aria-controls="mod-checkmark-submissions_r0_c3 mod-checkmark-submissions_r1_c3 mod-checkmark-submissions_r2_c3 ' +
                'mod-checkmark-submissions_r3_c3" ' + 'href="javascript:void(0)">' +
                '<i class="icon fa fa-plus fa-fw " title="Show" aria-label="Show"></i></a></br>');
            $("th.colexample:eq(" + excount + ")").prepend('        <a title="Hide All" id="hideall" aria-expanded="true" ' +
                'aria-controls="mod-checkmark-submissions_r0_c8 mod-checkmark-submissions_r1_c8 mod-checkmark-submissions_r2_c8 ' +
                'mod-checkmark-submissions_r3_c8" ' + 'href="javascript:void(0)"><i class="icon fa fa-minus fa-fw "' +
                ' title="Hide" aria-label="Hide"></i></a></br>\n');
            if(excount > 0) {
                $('#showall').hide();
            }
            else {
                $('#hideall').hide();
            }
            $.when(this).done(function (){ $('#hideall').click(function(){Utils.collapseorExpandExamples(false);});});
            $.when(this).done(function (){ $('#showall').click(function(){Utils.collapseorExpandExamples(true);});});
            baseurl = Utils.getBaseUrl();

        }
    };

});
