<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings that allow configuring various applink features.
 *
 * @package    filter_applink
 * @copyright  2019 Dani Palou <dani@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // URL scheme.
    $setting = new admin_setting_configtext('filter_applink/urlscheme',
                                         new lang_string('urlscheme', 'filter_applink'),
                                         new lang_string('urlscheme_help', 'filter_applink'),
                                         '',
                                         PARAM_ALPHA);
    $settings->add($setting);
}