Moodle App link filter plugin
====================================

With this plugin you can easily create links to open the Moodle App (or your custom app if you have one). You only need to specify the URL that you want to be opened (e.g. a course URL) and the filter will automatically create the URL to launch the app.

You can also specify if you want to use a certain username in the app.

This filter will be applied when the user navigates using a browser, but not when the user navigates using the app (the app is already open, you don't need to open it with a link).

# To Install it manually #

- Unzip the plugin in the moodle .../filter/ directory.
- Enable it from "Site Administration >> Plugins >> Filters >> Manage filters".

# To Use it #

Just add the attribute "data-app-link" to a regular link:

    <a href="https://domain.com/course/view.php?id=2" data-app-link>Click me</a>

If no value is assigned to data-app-link, the default URL scheme will be used. The default scheme can be changed in "Site Administration >> Plugins >> Filters >> Moodle App link". Leaving this setting empty will use the official Moodle app scheme: "moodlemobile".

If you want to force a scheme only for a certain link you can do it like this:

    <a href="https://domain.com/course/view.php?id=2" data-app-link="myscheme">Click me</a>

# Add a username #

By default, no username will be added to the URL. This means that, if there are several users from that site stored in the app, the app will use the one that's currently logged in. If there is no user currently logged in, the user will have to choose which account does he want to use to open the link.

You can specify a username so the app will force to use that user. If there are several users from that site stored in the app, the app will always use the user you specified. In case that user is not stored, the user will be sent to the credentials screen and the username will be prepopulated.

You can force to use a certain username, like this:

    <a href="https://domain.com/course/view.php?id=2" data-app-link data-username="john.smith">Click me</a>

If data-username is left empty, the filter will automatically add the username of the user that's viewing the link:

    <a href="https://domain.com/course/view.php?id=2" data-app-link data-username>Click me</a>

In the example above, the app will be opened with the same user I'm using in browser.

# See also #

- [Atto button for Moodle App link filter plugin](https://github.com/dpalou/moodle-atto_applink)
- [Open the Moodle app from another app](https://docs.moodle.org/dev/Open_the_Moodle_app_from_another_app)
