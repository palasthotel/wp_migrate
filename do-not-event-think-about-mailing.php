<?php
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
//	echo "tried to mail but didn't\n";
	return "I-DO-NOT-THINK";
}

function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
//	echo "tried to notify new user ".$user_id." but didn't\n";
	return "I-DO-NOT-THINK";
}

function wp_password_change_notification( $user ) {
//	echo "tried to notify password change to user ".$user_id." but didn't\n";
	return "I-DO-NOT-THINK";
}