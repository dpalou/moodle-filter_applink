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
 * Tests for filter_applink.
 *
 * @package    filter_applink
 * @category   test
 * @copyright  2019 Dani Palou <dani@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/applink/filter.php');
require_once($CFG->dirroot . '/filter/applink/tests/classes/filter_mock.php');

/**
 * Unit tests for Moodle App link filter.
 *
 * Test that the filter produces the right content for each case.
 *
 * @package    filter_applink
 * @category   test
 * @copyright  2019 Dani Palou <dani@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_applink_testcase extends advanced_testcase {
    /** @var object The filter plugin object to perform the tests on */
    protected $filter;

    /** @var object A mock object to perform the tests simulating a WS request. */
    protected $filtermock;

    /** @var string Default scheme for the site. */
    protected $defaultscheme;

    /**
     * Setup the test framework
     *
     * @return void
     */
    protected function setUp() {
        $this->resetAfterTest();

        $this->filter = new filter_applink(context_system::instance(), array());
        $this->filtermock = new filter_applink_mock(context_system::instance(), array());


        $this->defaultscheme = get_config('filter_applink', 'urlscheme');
        if (empty($this->defaultscheme)) {
            $this->defaultscheme = 'moodlemobile';
        }
    }

    /**
     * Test the data-app-link attribute.
     *
     * @return void
     */
    public function test_scheme() {
        global $CFG;

        $tests = array(
            array ( // No filter.
                'before' => 'Please <a href="' . $CFG->wwwroot . '">click here</a>',
                'after' => 'Please <a href="' . $CFG->wwwroot . '">click here</a>'
            ),
            array ( // No href.
                'before' => 'Please <a data-app-link>click here</a>',
                'after' => 'Please <a data-app-link>click here</a>'
            ),
            array ( // Test default scheme with root URL.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-app-link>click here</a>',
                'after' => 'Please <a href="' . $this->defaultscheme . '://' . $CFG->wwwroot . '">click here</a>'
            ),
            array ( // Test default scheme with root URL and a different order.
                'before' => 'Please <a data-app-link href="' . $CFG->wwwroot . '">click here</a>',
                'after' => 'Please <a href="' . $this->defaultscheme . '://' . $CFG->wwwroot . '">click here</a>'
            ),
            array ( // Test forced scheme with root URL.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-app-link="forcedscheme">click here</a>',
                'after' => 'Please <a href="forcedscheme://' . $CFG->wwwroot . '">click here</a>'
            ),
            array ( // Test default scheme with a specific URL.
                'before' => 'Please <a href="' . $CFG->wwwroot . '/course/view.php?id=2" data-app-link>click here</a>',
                'after' => 'Please <a href="moodlemobile://' . $CFG->wwwroot . '?redirect=' . $CFG->wwwroot .
                                '/course/view.php?id=2">click here</a>'
            ),
        );

        foreach ($tests as $test) {
            $this->assertEquals($test['after'], $this->filter->filter($test['before']));
        }

        // Change the scheme setting.
        $newscheme = 'fakescheme';
        set_config('urlscheme', $newscheme, 'filter_applink');

        $this->assertEquals(
            'Please <a href="' . $newscheme . '://' . $CFG->wwwroot . '">click here</a>',
            $this->filter->filter('Please <a href="' . $CFG->wwwroot . '" data-app-link>click here</a>')
        );
    }

    /**
     * Test the data-username attribute.
     *
     * @return void
     */
    public function test_username() {
        global $CFG;

        // Create a user and use it.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create the base URL with current username attached and a fake username.
        $currentuserwwwroot = preg_replace('/(https?:\/\/)/is', '$1'. $user->username . '@', $CFG->wwwroot);
        $fakeuserwwwroot = preg_replace('/(https?:\/\/)/is', '$1fakeuser@', $CFG->wwwroot);

        $tests = array(
            array ( // No username.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-app-link>click here</a>',
                'after' => 'Please <a href="' . $this->defaultscheme . '://' . $CFG->wwwroot . '">click here</a>'
            ),
            array ( // Username, but no data-app-link.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-username>click here</a>',
                'after' => 'Please <a href="' . $CFG->wwwroot . '" data-username>click here</a>',
            ),
            array ( // Current username.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-app-link data-username>click here</a>',
                'after' => 'Please <a href="' . $this->defaultscheme . '://' . $currentuserwwwroot . '">click here</a>'
            ),
            array ( // Forced username.
                'before' => 'Please <a href="' . $CFG->wwwroot . '" data-app-link data-username="fakeuser">click here</a>',
                'after' => 'Please <a href="' . $this->defaultscheme . '://' . $fakeuserwwwroot . '">click here</a>'
            ),
        );

        foreach ($tests as $test) {
            $this->assertEquals($test['after'], $this->filter->filter($test['before']));
        }
    }

    /**
     * Test the filter isn't applied when the request comes from WS.
     *
     * @return void
     */
    public function test_ws_request() {
        global $CFG;

        // Simulate a request that comes from WS.
        $this->assertEquals(
            'Please <a href="' . $this->defaultscheme . '://' . $CFG->wwwroot . '">click here</a>',
            $this->filtermock->filter('Please <a href="' . $this->defaultscheme . '://' . $CFG->wwwroot . '">click here</a>')
        );
    }
}
