=== Notifications Center ===
Contributors: ninadjeret
Donate link: http://www.notificationscenter.com/en/donate
Tags: email, emails, notification, notifications
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get notified for many Wordpress actions, with beautiful, responsive and personnalised emails.

== Description ==

Add to your site a powerfull system to create any notification you need. You could override wordpress default ones or build yours in a few minuts.
Add a smart design to your notification with responsive and personnalised emails.

= Be alerted for what is important for you =

You can add notification for many wordpress or user actions, to be alerted of what is important for you

= Send notification to the right person =

For each notification, choose between several recipients : 

* selected users
* All users in selected roles
* specific email addresses

= The right word at the right place =

With the variable system, write your notification content with some variables which will be replaced when the email is sent, eg : Post title, author email, etc.

= Take control of wordpress default emails =

Bored of default Wordpress plain text emails ? You can now desactivate any default email or duplicate it into Notifications Center to change design, content and/or recipients !

= Customize your email design =

In only some clics, personnalize your email template. Add your logo, your colors, change some background content and that's all. You've got a beautiful and responsive email that fits your brand.
And cherry on the cake, you can previsualize in real time all the changes you make in the Email Customizer.

= Current supported actions =

Posts (works fine with any custom post type) :

* a post is pending review
* a Post is published
* a post is moved to trash
* a post is moved to draft
* a post is planned for publication

Comments : 

* a comment is published
* a comment is waiting for moderation (overrides WP default email)
* a comment get responded

System : 

* Wordpress updgrade core to new version
* User ask for password recovering
* a new user registered

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Notification->Settings screen to configure your email template
1. Start building notifications :)

== Screenshots ==

1. Creating a new notification
2. Choose beetwen several template designs for your emails (more templates to come)
3. Personnalize your choosen template to match your brand, your colors, etc.

== Changelog ==

= 1.5.0 =
* New : Log functionnality : You can now track emails sents threw NC & where emails are opened
* New : GravityForms compatibility : Gravity forms emails can now use NC template & be logged
* New : Masks are now applied also on Subject field 
* Fixed : Password reset link is back
* Fixed : minor bug fixes

= 1.3.3 =
* Fixed : Button in email template now appears correctly

= 1.3.0 =
* New notification : Send notifications when a post is duplicated (requires Duplicate Post plugin)
* New : Import/export notifications & settings
* New : Add a title to your notifications, different from the email subject (set up in settings)
* Fixed : Recipients now appear correctly in Notifications list
* Fixed : Warning message won't appear in sent emails
* Fixed : Documentation link updated (now opens in a new tab)
* Fixed : Minor warning messages during template customization won't appear

= 1.2.0 =
* Theme Support for Email design : you can now override default HTML email template
* PHP warning messages fix
* Minor bug fixes
* Minor translate fixes

= 1.1.2 =
* New wordpress default email dashboard : desactivate any email and/or dupliacte it in Notifications center
* Added new variables to use in content (current time & current date)
* Added type filter on Notifications list screen
* Minor design improvments
* Minor update on User password reset notification
* Minor text corrections
* Minor bug fixes on Field API

= 1.0.2 =
* Added ability to block email sending thru hooks
* Prevent JS conflict for future updates

= 1.0.1 =
* Template customization enhancement
* Settings are now saved without reloading page
* Minor CSS and JS fixes
* Under the hood : New way to add custom setting fields & Code documentation enhancement 

= 0.9.1 =
* New Login notification : be alerted when your account is used to log in 
* Code documentation enhancement
* Some email customization minor enhancement 
* Modified some function and filter names for optimising naming logic and Prevent conflic with other plugins

= 0.9 =
* First public version

== Upgrade Notice ==

= 1.0.2 =
Improved stability & minor enhancement 

= 1.0.1 =
Improved stability & minor enhancement 

= 0.9.1 =
First stable release

= 0.9.1 =
Some new stuff

= 0.9 =
First public version
