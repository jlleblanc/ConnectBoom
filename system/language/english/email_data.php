<?php

//---------------------------------------------------
//	Admin Notification of Mailinglist subscription
//--------------------------------------------------

function admin_notify_mailinglist_title()
{
return <<<EOF
Someone has subscribed to your mailing list
EOF;
}

function admin_notify_mailinglist()
{
return <<<EOF
A new mailing list subscription has been accepted.

Email Address: {email}
Mailing List: {mailing_list}
EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of New Entry
//--------------------------------------------------

function admin_notify_entry_title()
{
return <<<EOF
A new weblog entry has been posted
EOF;
}

function admin_notify_entry()
{
return <<<EOF
A new entry has been posted in the following weblog:
{weblog_name}

The title of the entry is:
{entry_title}

Posted by: {name}
Email: {email}

To read the entry please visit: 
{entry_url}

EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of New Member Registrations
//--------------------------------------------------

function admin_notify_reg_title()
{
return <<<EOF
Notification of new member registration
EOF;
}

function admin_notify_reg()
{
return <<<EOF
The following person has submitted a new member registration: {name}

At: {site_name}

Your control panel URL: {control_panel_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of New Comment
//--------------------------------------------------

function admin_notify_comment_title()
{
return <<<EOF
You have just received a comment
EOF;
}

function admin_notify_comment()
{
return <<<EOF
You have just received a comment for the following weblog:
{weblog_name}

The title of the entry is:
{entry_title}

Located at: 
{comment_url}

Posted by: {name}
Email: {email}
URL: {url}
Location: {location}

{comment}
EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of New Trackback
//--------------------------------------------------

function admin_notify_trackback_title()
{
return <<<EOF
You have just received a trackback
EOF;
}

function admin_notify_trackback()
{
return <<<EOF
You have just received a trackback for the following entry:
{entry_title}

Located at: 
{comment_url}

The trackback was sent from the following weblog:
{sending_weblog_name}

Entry Title:
{sending_entry_title}

Weblog URL:
{sending_weblog_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Membership Activation Instructions
//--------------------------------------------------

function mbr_activation_instructions_title()
{
return <<<EOF
Enclosed is your activation code
EOF;
}

function mbr_activation_instructions()
{
return <<<EOF
Thank you for your new member registration.

To activate your new account, please visit the following URL:

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}

{site_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Member Forgotten Password Instructions
//--------------------------------------------------

function forgot_password_instructions_title()
{
return <<<EOF
Login information
EOF;
}

function forgot_password_instructions()
{
return <<<EOF
{name},

To reset your password, please go to the following page:

{reset_url}

Your password will be automatically reset, and a new password will be emailed to you.

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}
EOF;
}
/* END */



//---------------------------------------------------
//	Reset Password Notification
//--------------------------------------------------

function reset_password_notification_title()
{
return <<<EOF
New Login Information
EOF;
}

function reset_password_notification()
{
return <<<EOF
{name},

Here is your new login information:

Username: {username}
Password: {password}

{site_name}
{site_url}
EOF;
}
/* END */



//---------------------------------------------------
//	Validated Member Notification
//--------------------------------------------------

function validated_member_notify_title()
{
return <<<EOF
Your membership account has been activated
EOF;
}

function validated_member_notify()
{
return <<<EOF
{name},

Your membership account has been activated and is ready for use.

Thank You!

{site_name}
{site_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Decline Member Validation
//--------------------------------------------------

function decline_member_validation_title()
{
return <<<EOF
Your membership account has been declined
EOF;
}

function decline_member_validation()
{
return <<<EOF
{name},

We're sorry but our staff has decided not to validate your membership.

{site_name}
{site_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Mailinglist Activation Instructions
//--------------------------------------------------

function mailinglist_activation_instructions_title()
{
return <<<EOF
Email Confirmation
EOF;
}

function mailinglist_activation_instructions()
{
return <<<EOF
Thank you for joining the "{mailing_list}" mailing list!

Please click the link below to confirm your email.

If you do not want to be added to our list, ignore this email.

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}
EOF;
}
/* END */



//---------------------------------------------------
//	Comment Notification
//--------------------------------------------------

function comment_notification_title()
{
return <<<EOF
Someone just responded to your comment
EOF;
}

function comment_notification()
{
return <<<EOF
Someone just responded to the entry you subscribed to at:
{weblog_name}

The title of the entry is:
{entry_title}

You can see the comment at the following URL:
{comment_url}

{comment}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of New Gallery Comment
//--------------------------------------------------

function admin_notify_gallery_comment_title()
{
return <<<EOF
You have just received a comment
EOF;
}

function admin_notify_gallery_comment()
{
return <<<EOF
You have just received a comment for the following photo gallery:
{gallery_name}

The title of the entry is:
{entry_title}

Located at: 
{comment_url}

{comment}
EOF;
}
/* END */

//---------------------------------------------------
//	Gallery Comment Notification
//--------------------------------------------------

function gallery_comment_notification_title()
{
return <<<EOF
Someone just responded to your comment
EOF;
}

function gallery_comment_notification()
{
return <<<EOF
Someone just responded to the photo entry you subscribed to at:
{gallery_name}

You can see the comment at the following URL:
{comment_url}

{comment}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
}
/* END */



//---------------------------------------------------
//	Admin Notification of New Forum Post
//--------------------------------------------------

function admin_notify_forum_post_title()
{
return <<<EOF
Someone just posted in {forum_name}
EOF;
}

function admin_notify_forum_post()
{
return <<<EOF
{name_of_poster} just submitted a new post in {forum_name}

The title of the thread is:
{title}

The post can be found at:
{post_url}

{body}
EOF;
}
/* END */


//---------------------------------------------------
//	Forum Post User Notification
//--------------------------------------------------

function forum_post_notification_title()
{
return <<<EOF
Someone just posted in {forum_name}
EOF;
}

function forum_post_notification()
{
return <<<EOF
Someone just posted in a thread you subscribed to at:
{forum_name}

The title of the thread is:
{title}

The post can be found at:
{post_url}

{body}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
}
/* END */


//---------------------------------------------------
//	Private Message Notification
//--------------------------------------------------

function private_message_notification_title()
{
return <<<EOF
Someone has sent you a Private Message
EOF;
}

function private_message_notification()
{
return <<<EOF

{recipient_name},

{sender_name} has just sent you a Private Message titled '{message_subject}'.

You can see the Private Message by logging in and viewing your InBox at:
{site_url}

To stop receiving notifications of Private Messages, turn the option off in your Email Settings.
EOF;
}
/* END */


/* -------------------------------------
/*  Notification of Full PM InBox
/* -------------------------------------*/

function pm_inbox_full_title()
{
return <<<EOF
Your private message mailbox is full
EOF;
}

function pm_inbox_full()
{
return <<<EOF
{recipient_name},

{sender_name} has just attempted to send you a Private Message,
but your InBox is full, exceeding the maximum of {pm_storage_limit}.

Please log in and remove unwanted messages from your InBox at:
{site_url}
EOF;
}
/* END */


/* -------------------------------------
/*  Notification of Forum Topic Moderation
/* -------------------------------------*/

function forum_moderation_notification_title()
{
return <<<EOF
Moderation notification in {forum_name}
EOF;
}

function forum_moderation_notification()
{
return <<<EOF
{name_of_recipient}, a moderator has {moderation_action} your thread.

The title of the thread is:
{title}

The thread can be found at:
{thread_url}
EOF;
}
/* END */


/* -------------------------------------
/*  Notification of Forum Post Report
/* -------------------------------------*/

function forum_report_notification_title()
{
return <<<EOF
Post reported in {forum_name}
EOF;
}

function forum_report_notification()
{
return <<<EOF
{reporter_name} just reported a post written by {author} in:
{forum_name}

The reason(s) for the report:
{reasons}

Additional notes from {reporter_name}:
{notes}

The post can be found at:
{post_url}

Contents of reported post:
{body}
EOF;
}
/* END */


/* -------------------------------------
//  OFFLINE SYSTEM PAGE
/* -------------------------------------*/

function offline_template()
{
return <<<EOF
<html>
<head>

<title>System Offline</title>

<style type="text/css">

body { 
background-color:	#ffffff; 
margin:				50px; 
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
letter-spacing:		.09em;
text-decoration:	none;
color:              #330099;
background-color:   transparent;
}
  
a:visited {
color:				#330099;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:    underline;
background-color:	transparent;
}

#content  {
border:				#999999 1px solid;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}
</style>

</head>

<body>

<div id="content">

<h1>System Offline</h1>

<p>This site is currently offline</p>

</div>

</body>

</html>
EOF;
}
/* END */


/* -------------------------------------
//  User Messages Template
/* -------------------------------------*/

function message_template()
{
return <<<EOF
<html>
<head>

<title>{title}</title>

<meta http-equiv='content-type' content='text/html; charset={charset}' />

{meta_refresh}

<style type="text/css">

body { 
background-color:	#ffffff; 
margin:				50px; 
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
letter-spacing:		.09em;
text-decoration:	none;
color:              #330099;
background-color:   transparent;
}
  
a:visited {
color:				#330099;
background-color:	transparent;
}

a:active {
color:				#ccc;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:    underline;
background-color:	transparent;
}

#content  {
border:				#000 1px solid;
background-color: 	#DEDFE3;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}

ul {
margin-bottom: 		16px;
}

li {
list-style:			square;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		8px;
margin-bottom: 		8px;
color: 				#000;
}

</style>

</head>

<body>

<div id="content">

<h1>{heading}</h1>

{content}

<p>{link}</p>

</div>

</body>

</html>
EOF;
}
/* END */


/* -------------------------------------
//  Mailing List Template
/* -------------------------------------*/

function mailinglist_template()
{
return <<<EOF
{message_text}

To remove your email from this mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
}
/* END */


?>