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
 * Moodle App link content filter.
 *
 * @package    filter_applink
 * @copyright  2019 Dani Palou <dani@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given some text, treat all the anchors that have the data-app-link atttribute, converting them to a
 * link to open the Moodle app.
 *
 * The user can configure the schema to use and also the username to put in the link.
 *
 * Example syntax:
 *    <a href="..." data-app-link>basic usage</a>
 *    <a href="..." data-app-link="myscheme" data-username>advanced usage</a>
 *
 * @package    filter_applink
 * @copyright  2019 Dani Palou <dani@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_applink extends moodle_text_filter {

    /**
     * This function will convert the href of anchors containing data-app-link to a link to open the Moodle app.
     *
     * @param string $text The text to filter.
     * @param array $options The filter options.
     * @return string The filtered text.
     */
    public function filter($text, array $options = array()) {

        if ($this->is_ws_access()) {
            // Don't apply the filter in WS access because it's probably the app already.
            return $text;
        }

        // Check if the text contains any data-app-link. stripos is faster than checking a regex.
        if (stripos($text, 'data-app-link') === false) {
            return $text;
        }

        $search = '/<a[^>]+data-app-link[^>]*>/is'; // Search anchors that have the "data-app-link" attribute.
        $result = preg_replace_callback($search, function($matches) {
            return $this->replace_callback($matches);
        }, $text);

        if (is_null($result)) {
            return $text; // Error during regex processing, keep original text.
        } else {
            return $result;
        }
    }

    /**
     * This function filters the current anchor. If the anchor contains an href, it will be
     * converted to a link to open the app.
     *
     * @param array $textblock An array containing the matching captured pieces of the
     *                         regular expression. It's just the whole anchor.
     * @return string
     */
    protected function replace_callback($textblock) {
        global $CFG, $USER;

        // Get the href and scheme to use.
        $hrefmatches = $this->get_attr_data('href', $textblock[0]);
        $schemematches = $this->get_attr_data('data-app-link', $textblock[0]);

        if (!$hrefmatches || !$schemematches) {
            // No href, stop.
            return $textblock[0];
        }

        $scheme = $schemematches[1];

        if (empty($scheme)) {
            // Scheme not specified, use the default one.
            $scheme = get_config('filter_applink', 'urlscheme');
            if (empty($scheme)) {
                // Not configured, use the Moodle official app scheme.
                $scheme = 'moodlemobile';
            }
        }

        // Get the username to use.
        $usernamematches = $this->get_attr_data('data-username', $textblock[0]);
        if (!$usernamematches) {
            $username = '';
        } else {
            if (empty($usernamematches[1])) {
                // No username specified, use current user if any.
                $username = !empty($USER->id) ? $USER->username : '';
            } else {
                $username = $usernamematches[1];
            }
        }

        // Create the new link.
        $appurl = $scheme . '://';

        $baseurl = $CFG->wwwroot;
        if ($username) {
            $baseurl = preg_replace('/(https?:\/\/)/is', "$1$username@", $baseurl);
        }

        $appurl .= $baseurl;

        if ($hrefmatches[1] != $CFG->wwwroot) {
            // The URL is not the base URL of the site, set the redirect.
            $appurl .= '?redirect=' . $hrefmatches[1];
        }

        // Remove the attributes of this filter.
        $result = preg_replace($this->get_attr_regex('data-app-link'), '', $textblock[0]);
        $result = preg_replace($this->get_attr_regex('data-username'), '', $result);

        return str_replace($hrefmatches[1], $appurl, $result);
    }

    /**
     * Get the value of a certain attribute.
     *
     * @param string $name Name of the attribute.
     * @param string $text Text to search in.
     * @return array The first element has the full match (name+value), the second has the value.
     */
    protected function get_attr_data($name, $text) {
        preg_match($this->get_attr_regex($name), $text, $matches);

        if (!empty($matches)) {
            return array($matches[0], count($matches) > 2 ? $matches[2] : '');
        }

        return false;
    }

    /**
     * Get the regex to match a certain attribute.
     *
     * @param string $name Name of the attribute.
     * @return string Regex.
     */
    protected function get_attr_regex($name) {
        return '/ '.$name.'(=[ |\n]*"([^"]*)")?/is';
    }

    /**
     * Detects if the user is accesing Moodle via Web Services.
     *
     * @return boolean True if the user is accesing via WS
     */
    protected function is_ws_access() {
        global $ME;

        // First check this global const.
        if (WS_SERVER) {
            return true;
        }

        // Check rare cases, like webservice/pluginfile.php.
        if (!is_null($ME) && strpos($ME, 'webservice/') !== false) {
            $token = optional_param('token', '', PARAM_ALPHANUM);
            if ($token) {
                return true;
            }
        }

        return false;
    }
}
