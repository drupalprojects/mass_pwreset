<?php

/**
 * @file
 * Contains batch process functions for resetting passwords.
 */

use \Drupal\user\Entity\User as User;

/**
 * Batch process callback.
 *
 * Loads user object, resets password, and optionally sends notify email.
 */
function mass_pwreset_batch_process($data, &$context) {
  // Initiate count values for batch iteration.
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_row'] = 0;
    $context['sandbox']['max'] = count($data['uids']);
  }

  // Establish index for loading user objects.
  $i = $context['sandbox']['current_row'];

  // Reset user password.
  if (isset($data['uids'][$i])) {
    $user = User::load($data['uids'][$i]);
    $user->setPassword(user_password(40));
    $user->save();

    // Update status message.
    $context['message'] = t('Processing uid: @uid', array('@uid' => $user->id()));
    $context['results'][] = t('User password reset');

    // Send password reset email and log event.
    if ($data['notify_active_users'] == 1) {
      // Set batch message.
      $email_message = t('Password reset email sent to uid: @uid', array('@uid' => $user->id()));
      $context['message'] = $email_message;

      // Notify active users.
      if (!$user->isBlocked()) {
        _user_mail_notify('password_reset', $user);
        \Drupal::logger('mass_pwreset')->notice($email_message);
      }
      // Notify blocked users if applicable.
      if ($data['notify_blocked_users'] == 1 && $user->isBlocked()) {
        _user_mail_notify('password_reset', $user);
        \Drupal::logger('mass_pwreset')->notice($email_message);
      }
    }

    // Log password reset for this uid.
    $message = t('Password reset for uid: @uid', array('@uid' => $user->id()));
    \Drupal::logger('mass_pwreset')->notice($message);
  }

  // Update batch progress information.
  $context['sandbox']['progress'] += 1;
  $context['sandbox']['current_row'] += 1;

  // Inform the batch engine of estimated completion level reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Batch finish callback.
 *
 * Logs finished results and set message to user.
 */
function mass_pwreset_batch_finished($success, $results, $operations) {
  if ($success) {
    $passwords_reset = count($results);
    $message = t('Mass password reset batch finished resetting @count passwords.', array('@count' => $passwords_reset));
    // Log password reset batch finished.
    \Drupal::logger('mass_pwreset')->notice($message);
    // Display message to user.
    drupal_set_message($message);
  }
  else {
    // Log password reset batch failure.
    \Drupal::logger('mass_pwreset')->error(t('Mass password reset batch failed.'));
    drupal_set_message(t('Password reset batch has failed.'), 'error');
  }
}
