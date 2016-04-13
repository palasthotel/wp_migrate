<?php

function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	echo "tried to mail but didnt\n";
}

function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
	echo "tried to notify new user ".$user_id." but didnt\n";
}