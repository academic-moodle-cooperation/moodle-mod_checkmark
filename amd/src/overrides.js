// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles overlays/tooltips in checkmark!
 *
 * @module   mod_checkmark/checkboxcontroller
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_checkmark/overrides
 */
define(['jquery', 'jqueryui', 'core/str', 'core/log'], function($, jqui, str, log) {

    /**
     * @constructor
     * @alias module:mod_checkmark/overrides
     */
    var Overrides = function() {
        /**
         * @var object table object to use
         */
        this.table = {};
    };

    var instance = new Overrides();

    /**
     * Initialises the JavaScript
     * @param {array} config Config params
     */
    instance.initializer = function(config) {
        this.table = $(config.id);

        log.info('Initialize report JS!', 'mod_checkmark');

        var tofetch = [
            {key: 'dates_overwritten', component: 'mod_checkmark'},
            {key: 'availabledate', component: 'mod_checkmark'},
            {key: 'duedate', component: 'mod_checkmark'},
            {key: 'cutoffdate', component: 'mod_checkmark'}
        ];
        str.get_strings(tofetch).done(function(s) {

            log.info('Successfully acquired strings: ' + s, 'mod_checkmark');
            log.info('Register tooltips!', 'mod_checkmark');

            $('#mod-checkmark-submissions').tooltip({
                items: '.overridetooltip',
                track: true,
                content: function() {
                    var element = $(this);

                    var timeavailable = element.data('timeavailable');
                    var timedue = element.data('timedue');
                    var cutoffdate = element.data('cutoffdate');

                    var content = '<div class="checkmarkoverlay"';
                    content += 'aria-describedby="' + element.attr('id') + '">';
                    content += s[0]; // Is string 'dates_overwritten' from 'mod_checkmark'!

                    if (timeavailable !== '') {
                        content += '<div class="timeavailable">' + s[1] + // Is string 'timeavailable' from 'mod_checkmark']!
                                '&nbsp;' + timeavailable + '</div>';
                    }
                    if (timedue !== '') {
                        content += '<div class="timedue">' + s[2] + // Is string 'timedue' from 'mod_checkmark'!
                                '&nbsp;' + timedue + '</div>';
                    }
                    if (cutoffdate !== '') {
                        content += '<div class="cutoffdate">' + s[3] + // Is string 'cutoffdate' from 'mod_checkmark'!
                                '&nbsp;' + cutoffdate + '</div>';
                    }

                    content += '</div>';

                    return content;
                }
            });
        }).fail(function(ex) {
            log.error('Error getting strings: ' + ex, 'mod_checkmark');
        });
    };

    return instance;
});
