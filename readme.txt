=== Online Lesson Booking ===
Contributors: tnomi
Donate link: http://olbsys.com/extensions/
Tags: booking, reservation, appointment, timetable, lesson 
Requires at least: 3.5
Tested up to: 4.8.0
Stable tag: 0.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plug-in supplies the reservation-form and scheduler for the one-to-one online lesson. 

== Description ==

Online Lesson Booking system (OLB) was made in order to equip a web site with the reservation-form and scheduler for one-to-one online lesson.

Teacher (author) sets up a timetable using a scheduler, and member (subscriber) makes a reservation by clicking timetable.
Teacher and a member are informed by e-mail in the case of reservation and cancellation. 

Visit [The User's Guide (ja)](http://olbsys.com)/[(en)](http://olbsys.com/en/) for more info.

== Installation ==

Visit [Setup Guide (ja)](http://olbsys.com/setup/)/[(en)](http://olbsys.com/en/setup/).

= Installation =

1. Donwload plugin file and unzip it.
2. Upload "online-lesson-booking-system" folder to the "/wp-content/plugins/" directory
3. Activate the plugin through the "Plugins" menu in WordPress

= Plugin set up =

1. Open the WordPress admin panel, and go to the plugin option page "OLBsystem".
2. Menu "OLBsystem > General" is setup about reservation and a timetable.
3. Menu "OLBsystem > Special pages" is setup of the name (slug) of a page indispensable to a system.
4. Menu "OLBsystem > Mail" is Edit of the text of notice mail.
5. This plug-in uses JQuery. Insert in your theme-file(functions.php) the code "wp_enqueue_script('jquery');". 
6. Some special pages are already created, when the plug-in was activated.
7. Activate added widget "Members only", "Teachers only", and "Admins only".

= Edit the schedule of teacher = 
1. Add some users as teacher. Teacher's role is  "author".
2. Open the their profile-edit-page, check the item of "teacher".
3. Log in as a teacher. Access the "editschedule" page and set a schedule.
4. Make the information of each teacher as "post" (ex. with "teacher" category, etc.), and insert short cord [olb_weekly_schedule id="xx"].

* "id" is ID number of each teacher. ID number is confirmed with a list of users in admin-page.

= Member registration =

1. Check the item of the "membership" (anyone can register) in the admin page of WordPress.
2. A "new user's default role" is "subscriber". 
3. Members perform new user's registration themselves. Member must set item "Skype ID".
4. Administrator update the item "term of validity" of member's profile. (ex. after checking the payment from a member, etc.)

* "Ticket system" can be chosen from version 0.4.0. 

== Frequently Asked Questions ==

Visit [The User's Guide (ja)](http://olbsys.com)/[(en)](http://olbsys.com/en/) which covered all of features of this plugin.

= How is reservation information saved? =

An original table is created in a database, and it saves there. 

= Is a member controlling function included? =

Not include. Please use the "membership" which is a standard function of WordPress, or compensate with other plug-in. 

== Screenshots ==

1. "Scheduler" page 
2. "Daily schedule" page 
3. "Weekly schedule" page
4. "Reservation form" page 
5. "Plugin option" page

== Changelog ==

See [Change log (ja)](http://olbsys.com/category/updates/)/[(en)](http://olbsys.com/en/category/updates/).

= 0.8.0 =

* Fixed some notices and warnings displayed in "WP_DEBUG" mode.
* Several variables available for notification have been added.

= 0.7.9 =

* The bug by which the rest of a free lesson will be the negative value was corrected.

= 0.7.8 =

* Option which send reservation notifications to also administrator was added.

= 0.7.7 =

* Fixed a bug in the calendar.<br>
( About the problem that occured when that will be  specified the start day of the week )

= 0.7.6 =

* Domain Path of locale folder was changed “/languages”.
* The accessing to the cancellation URL for member by teacher is recirected to that for the teacher.
* Bug fix in "canReport()" and "report()".

= 0.7.5 =

* Bug fix.
* "front.js" is loaded with the "jQuery" by the "wp_enqueue_script()".
* Action hook "parse_request" was changed to "template_redirect".

= 0.7.4 =

* Correction of the bug in which reservation fails in WordPress 4.4.

= 0.7.3 =

* The property "Teacher" is possible to set in a "Add New User" page.<br>
If "Teacher" property was checked, "Role" is changed to "Author" automatically.<br>
(It is so even in a "User Edit" page.)

= 0.7.2 =

* Bug fix caused by abolition of "WPLANG".

= 0.7.1 =

* An incomplete file in Version 0.7.0 was complemented.

= 0.7.0 =

* The option which invalidates a judgement of "Term of validity" was added into the plugin option page "OLBsystem:General".

= 0.6.8 =

* The time format of the "[Reservation form (ja)](http://olbsys.com/setup/special-pages/#reserve_form)/[(en)](http://olbsys.com/en/setup/special-pages/#reserve_form)" and the "[Cancellation form (ja)](http://olbsys.com/setup/special-pages/#cancel_form)/[(en)](http://olbsys.com/en/setup/special-pages/#cancel_form)" were corrected from "00:00:00" to "00:00".<br>
e.g. "2015-05-30 14:30"

= 0.6.7 =

* When the user opened a page which needs login, the user is returned to the page just after the login.
* The word in timetable which indicates the reservation state ('Open', 'closed', etc.) were changed to gettext.<br>Those can be translated.
* Small change in HTML. Some classes were added.

= 0.6.6 =

* The contents of a “Cancellation form for teacher” page and schedule list were changed a little.
* A detail of reservation which cancellation request already closed were linked in schedule list.
* Some filters were added.

= 0.6.5 =

* The mail address format of the reservation notice for the user was changed to "user@example.com" from "User &lt;user@example.com&gt;".<br>Because a send error on wp_mail() occurs in several servers.

= 0.6.4 =

* Record sorting of the "member's schedule" was corrected to an ascending order of time. (from descending order.)
* The default "width" of some tables (timetable, calendar, etc.) in "front.css" were changed. 

= 0.6.3 =

* The bug of the notice mail of "reservation/cancellation" was corrected. 

= 0.6.2 =

* The bug in the deadline time calculation which receives reservation and cancellation was corrected. 
* The variable which can be used in the notice mail of reservation was added. <br>
"%USER_TERM%" is the member's term of validity.<br>
"%USER_TERM_REM%" is the remaining days of a member's term of validity.<br>
"%USER_TICKETS%" is the number of tickets which the member owns.

= 0.6.1 =

* The update process of a teacher's profile item "website" was improved. <br>
The item will be updated by "bulk action (edit post) ", also by "Import". 

= 0.6.0 =

* Profile edit by a teacher was changed a little. 
* Change of the term of validity by an administrator was changed a little. 
* New information feed from "olbsys.com" was added.

= 0.5.4 =

* The bug in the case of the profile edit and display by teacher user was corrected. 

= 0.5.3 =

* The filter hooks was added. Those are the receiver's addresses of the notice e-mail of reservation (or cancellation). 

= 0.5.2 =

* Malfunction was solved when used together with "[Events Manager](https://wordpress.org/plugins/events-manager/ )" etc.<br> 
(The malfunction is 404 errors when the subpage below an "Events" page is accessed, for example.) 

= 0.5.1 =

* Small bug fix. 

= 0.5.0 =

* "Calendar" short code was added. On "Daily Schedule" pages, the date can be chosen from a calendar.<br>
The type of a calendar is two kinds. They are "monthly" or "weekly".<br>
[&raquo; About Daily schedule (ja)](http://olbsys.com/setup/teachers/#daily_schedule)/[(en)](http://olbsys.com/en/setup/teachers/#daily_schedule).

* The teacher's self-portrait can be displayed on "Daily schedule".<br>
Set a "Featured Image" in each "post" of teacher information.<br>
[&raquo; About Teacher's portrait (ja)](http://olbsys.com/setup/teachers/#daily_schedule)/[(en)](http://olbsys.com/en/setup/teachers/#daily_schedule).

= 0.4.5 =

* The message in a "Ticket-logs" was changed partially.
* Bug fix

= 0.4.4 =

* With the output of Short-code in contents, a translation file (.mo file) is read according to the value of current locale information (get_locale()). <br>
(For example, in the cases of multilingualization etc.) <br />
However, the translation files which are attached at present are only Japanese and English. Sorry. 
* Bug fix

= 0.4.3 =

* Bug fix

= 0.4.2 =

* Bug fix

= 0.4.1 =

* The display style of "Ticket logs" was changed. 
* Also when the "Term of validity" is extended, it is displayed on "Ticket logs". 
* Bug fix

= 0.4.0 =

* The limit of the number of reservation per month can be specified. 
* "Ticket system" can be chosen. It is the system of giving each member tickets and making a reservation by consuming ticket. If tickets run short, the member has to purchase.
* Administrator can see the page which they use pretending to be a member or a teacher. 
* Some special pages were added and changed. 
* Some short-code were added. 
* Bug fix

= 0.3.1 =

* "Members info" page was added one of special page

= 0.3.0 =

* Table structure and processing were changed
* "Admin only" widget was added
* Bug fix

= 0.2.0.1 =

* Small bug fix

= 0.2.0 =

* first release.

== Upgrade Notice ==

= 0.8.0 =

Fixed some warnings in "WP_DEBUG" mode. And, several variables for notification have been added.
