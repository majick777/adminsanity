=== AdminSanity ===
Contributors: majick
Donate link: https://wpmedic.tech
Tags: wordpress admin, admin menu, admin notices, admin bar, cleaner
Requires at least: 4.0.0
Tested up to: 6.8.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

AdminSanity brings sanity through sanitization to your WordPress Admin Area. Cleanly.

== Description ==

AdminSanity brings sanity through sanitization to your WordPress Admin Area. Cleanly.

AdminSanity started as a Gist to address the WordPress Admin Menu clutter, automatically splitting and re-ordered menu to clean up the mess that appears as more and more plugins are added to a WordPress site. This idea was inspired by [this TRAC proposal for a "Simplified WordPress Admin Navigation"](https://core.trac.wordpress.org/ticket/47012), as a proof of concept for a potential core Feature plugin.

Now, it includes modules not just for cleaning up and improving accesss to both the *Admin Menu* and *Admin Bar*, but also includes a module for turning *Admin Notice* clutter at the top of your admin screen into an tabbed notice type display interface! This means a sigh of relief guaranteed, or your money back! (Actually nevermind - it's free - just like your Admin Area soon will be free of clutter!)

Oh and plus did I mention it supports responsive width changes and admin colour schemes?! :-D

= AdminSanity Menu =

The AdminSanity Menu module automatically sorts the Admin Menu into three sections: 

- *Content* - Dashboard, all Post Types | Media, Links and Comments
- *Manage* - Settings, [Plugin Settings], Appearance, Plugins, Users, Tools
- *Extensions* - any other extra top level menu items not present by default

And, to add an extra layer of navigation accessibility, a meta-menu level has been added to each of the Admin Menu sections, with the corresponding labels: *Content*, *Manage* and *Extensions*. So that rather than trying to replace the existing Admin Menu design (which would be hard to make backwards compatible), it is just adapted to provide improved menu access.

Additionally, a new "Plugin Settings" menu item has been added, with any Settings submenu items moved there *that are not there by default* (ie. from WordPress itself.) This greatly improves the ease of navigation in your admin area when searching for either Core or Plugin settings.

Then, for even faster ease of access, an "Expand menu" icon has been added at the top of the Admin Menu (to complement the existing "Collapse menu" icon at the bottom) which expands to provide a full page three column display of the entire Admin Menu with all submenus expanded so you can instantly see every menu at once. Wow!

= AdminSanity Notices =

The AdminSanity Notice module attempts to solve probably **the** most long-standing thorn in any WordPress user's side - the distracting amount of *NOTICES* at the top of every single Admin page! By providing a tabbed interface for notice types (*Error*, *Update*, *Warnings*, *Messages*, *Notices*), you get a colour-coded and at-a-glance display of how many notices of each type there are, so you can prioritize and stay on task.

Clicking on any of the notice types instantly displays all the notices for that type. Nice! And as an added bonus, if you want to access Notices by their user notice level, you can do that too. Clicking on the *Notices* label reveals an extra tab menu that is sorted by notice type instead: *All*, *General*, *Network*, *Admin* and *User*. Neat!

= AdminSanity Bar =

After experiencing the Admin Bar getting cluttered also and splitting itself into two lines (and thus making some of the bar items unclickable!) I decided to add some responsive height handling to fix the bar display when this happened. And also, add a new "Shuffle" Cycler icon to the start of the Admin Bar, which cycles the visibility of the different Admin Bar items. Each click of the icon will cycle through three display options, giving you faster access to what you were looking for:

- All Bar menu items (Default Admin Bar view)
- Default Bar menu items (added by WordPress)
- Non-default Bar menu items (from plugins and themes)

And again, similar to the Admin Menu, for faster ease of access to all Admin Bar items at once, a "Dropdown" toggle item is added at the end of the Admin Bar, providing an expanded view of all bar menu items and their submenus (including nested submenus which are accessible via dropdown arrows for those submenu items.) Nifty!

= Come Get Some Admin Sanity! =

Isn't it time for **AdminSanity** to bring some sanity back to your Admin Area?!


== Installation ==

1. Upload plugin .zip file to the `/wp-content/plugins/` directory and unzip.
2. Activate the plugin through the 'Plugins' menu in the WordPress Admin

== Frequently Asked Questions ==

= How do I get started? =

Once you have activated the plugin you will see these changes in your WordPress Admin area:

**Admin Menu** 
- a sorted menu with three sections: Content, Settings and Extensions
- an "Expand menu" item at the top to view a full page expanded Admin Menu
- an additional Plugin Settings menu item for non-default settings pages
**Admin Notices** 
- notices are grouped into clickable dropdown tabs according to notice types
- clicking on Notices reveals an extra menu sorted by notice level
**Admin Bar** 
- a "Shuffle" cycler icon to cycle between all, default and extra bar items
- a "Dropdown" toggle icon to expand the Admin Bar and view all submenus

See the full plugin description for more details on what each of these modifications does.

= Where are the Plugin Settings? =

A settings page is available via the Plugin Settings submenu (or the Settings submenu when the Menu module is inactive.)
Configuration is also currently available via constants and filters (see below.)

= Can I use this as a must-use plugin? =

Yes. Simply copy the individual module file(s) you wish to use into your `/wp-content/mu-plugins/` directory. Alternatively you can also copy the entire plugin into that directory if you wish.

= How do I turn a specific module off? =

Modules can be disabled via te settings page or by defining any of the following constants as `false`:

`ADMINSANITY_LOAD_MENU`, `ADMINSANITY_LOAD_BAR`, `ADMINSANITY_LOAD_NOTICES`

eg. `define( 'ADMINSANITY_LOAD_MENU', false );`

Or returning `false` to any of the following value filters:

`adminsanity_load_menu`, `adminsanity_load_bar`, `adminsanity_load_notices`

eg. `add_filter( 'adminsanity_load_bar', '__return_false' );

= Can I turn off some of the additional features? =

Yes, on the plugin settings page. These features can be also disabled using constants or filters in the same way as above:

| Feature              | Constant                  | Filter |
| Meta Menu Headings   | ADMINSANITY_MENU_METAS    | adminsanity_menu_metas    |
| Plugin Settings Menu | ADMINSANITY_MENU_PLUGINS  | adminsanity_menu_plugins  |
| Expand Menu Icon     | ADMINSANITY_MENU_EXPANDER | adminsanity_menu_expander |
| Bar Frontend Loading | ADMINSANITY_BAR_FRONTEND  | adminsanity_bar_frontend  |
| Bar Item Switcher    | ADMINSANITY_BAR_CYCLER    | adminsanity_bar_cycler    |
| Bar Dropdown Toggle  | ADMINSANITY_BAR_DROPDOWN  | adminsanity_bar_dropdown  |

= Can I keep the position of some items in the Admin Menu? =

Yes. Although tricky, a filter for this was included in the initial proof of concept Gist for the admin menu. Simply return an array of admin menu slugs to the filter `adminsanity_menu_keep_positions` and AdminSanity will attempt to keep those menu items from moving when it sorts the other items (See example in plugin code, function `adminsanity_menu_keep_position_test`.) Note that depending on the menu items's initial position, this may have mixed results in where it appears in relation to the other newly rearranged menu items.


== Screenshots ==

== Changelog ==

= 1.0.4 =
* Updated: Plugin Panel (1.3.5)
* Fixed: Admin Bar Frontend module loading option key

= 1.0.3 =
* Improved: do not load Menu/Notices on Block Editor pages
* Fixed: removed conflicting postbox class on notifications box
* Admin Menu: changed expanded menu autoload to on-demand load
* Admin Menu: keep menu positions for top WordPress.Com items
* Admin Bar: fix to conflict with post type template edit pages
* Admin Bar: auto disable bar module on WordPress.Com
* Admin Notices: added Elementor Notice class
* Admin Notices: do not float right after collapse click

= 1.0.2 =
* Updates: Plugin Panel (1.2.8)
* Admin Bar: fix undefined post_type on post list/edit screens
* Admin Menu: fix undefined index warning for debug mode

= 1.0.1 =
* Added: Plugin Panel library v1.2.2
* Added: plugin settings for Plugin Panel
* Added: plugin images for admin page
* Fixed: Conflict with Bar and Woocommerce Product Attributes page

= 1.0.0 =
* Fixed: explicitly sanitize and/or validate GET/REQUEST values
* Fixed: added missing menu style media query close bracket

= 0.9.9 =
* Admin Menu: add full page expanded admin menu
* Admin Menu: admin menu expander icon to admin bar
* Admin Menu: clone admin colour scheme rules
* Admin Bar: add full dropdown admin bar menu
* Admin Bar: added option to disable module on frontend
* Admin Bar: added mobile width responsiveness styles
* Admin Notices: improve menu styles and add count bubbles
* Admin Notices: add Commerce message types menu
* Admin Notices: added height animations to menu
* Fixed: standardize all function, constant and filter names
* Fixed: function and style integration between modules
* Fixed: added float reset div after admin notices box

= 0.9.8 =
* Loader: add loading of AdminSanity modules (using constants and filters)
* Admin Bar: initial version with cycler between default and extra items
* Admin Notices: add classes to notice types as subarray values
* Admin Notices: fix to handle 'notice-success' as Message notice type
* Admin Menu: added dropdown Meta-Menus to Sections: Content, Manage, Extensions
* Admin Menu: new Plugin Settings menu item for non-default Settings submenu items
* Admin Menu: added meta menu order filtering (prototype)
* Admin Menu: added menu separator IDs to menu item classes

= 0.9.7 =
* Admin Notices: initial version to sort and display notice levels and types

= 0.9.6 =
* Admin Menu: added some bugfixes to initial admin menu

= 0.9.5 =
* Admin Menu: initial proof of concept for split admin menu reordering (Gist)

== Upgrade Notice ==
