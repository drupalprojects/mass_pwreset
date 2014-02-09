<?php

/**
 * Batch process callback
 */
function mass_pwreset_batch_process($data, &$context) {
	if (!isset($context['sandbox']['progress'])) {
		$context['sandbox']['progress'] = 0;
		$context['sandbox']['current_row'] = 0;
		$context['sandbox']['max'] = count($data['uids']);
	}
	
	$i = $context['sandbox']['current_row'];
	
	if(isset($data['uids'][$i])){
		$user = user_load($data['uids'][$i]);
		
		mass_pwreset_execute_reset($user);
		$context['results'][] = t('Reset user %user', array('%mail' => $user->name));
		
		if ($data['notify_users'] == '1') {
			mass_pwreset_notify($user);
			$context['results'][] = t('The e-mail has been sent to %mail', array('%mail' => $user->mail));
		}
	}
	
	$context['sandbox']['progress']+= 1;
	$context['sandbox']['current_row']+= 1;
	
	if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
		$context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
	}
}

/**
 * Batch finish callback
 */
function mass_pwreset_batch_finished($success, $results, $operations) {
	if ($success) {
		drupal_set_message(t('!count processed.', array('!count' => count($results))));
	}
	else {
		$error_operation = reset($operations);
		$message = t('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
		drupal_set_message($message, 'error');
	}
}

/**
 * Callback: Reset User password
 */
function mass_pwreset_execute_reset($user) {
	$new_pass = _mass_pwreset_generate_password(12, TRUE);
	user_save($user, array('pass' => $new_pass));
	drupal_set_message(t('Users passwords reset.'), 'status');
}

/**
 * Callback: Notify User
 */
function mass_pwreset_notify($user) {
	_user_mail_notify('password_reset', $user);
	drupal_set_message(t('Users notified via email.'), 'status');
}

