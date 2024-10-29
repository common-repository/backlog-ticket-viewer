=== Backlog Ticket Viewer ===
Contributors: pressmaninc, akihirokato, hiroshisekiguchi, kazunao
Tags: backlog, dashboard, wordpress dashboard
Requires at least: 4.9
Requires PHP: 7.1
Tested up to: 5.0
License: GNU GPL v2 or higher
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display Backlog ticket on the dashboard.


== Description ==
Display information of Backlog tickets on the dashboard.
[Backlog](https://backlog.com/) is an online project management tool which is provided by nulab.
This plugin uses [Backlog API](https://developer.nulab-inc.com/docs/backlog/) to get tickets(issues) from your project.
s
== Installation ==
1. Upload the 'backlog-ticket-viewer' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the Plugins menu in WordPress.

== Use filter hook ==
You can specify the following values by a filter hook.
1. Target Team Name.
2. TargetApi Key.
3. Target Project Id.
4. Maximum display count for Backlog Ticket list.

== Changelog ==
= 1.0.0 =
first version.
