<?php
/**
 * @file
 * Contains \Drupal\mass_pwreset\BatchPasswordReset.
 */

namespace Drupal\mass_pwreset\Batch;

use Drupal\mass_pwreset\MassPasswordReset;

/**
 * Batch Password Reset Class.
 */
class BatchPasswordReset {

  /**
   * Constructs the batch data array.
   */
  public function __construct($data) {
    $batch = array(
      'operations' => array(
        array($this->batchProcess, array($data)),
      ),
      'finished' => $this->finishedBatch,
      'title' => t('Multiple password reset'),
      'init_message' => t('Multiple password reset in progress.'),
      'progress_message' => t('Executed @current of @total.'),
      'error_message' => t('The Multiple password reset have encountered an error'),
      'file' => drupal_get_path('module', 'mass_pwreset') . 'src/BatchPasswordReset.php',
    );

    batch_set($batch);
  }

  /**
   * Batch process callback.
   */
  public function processBatch($data, &$context) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_row'] = 0;
      $context['sandbox']['max'] = count($data['uids']);
    }

    $i = $context['sandbox']['current_row'];

    if (isset($data['uids'][$i])) {
      $user = user_load($data['uids'][$i]);

      $this->executeBatch($user);
      $context['results'][] = t('Reset user %user', array('%mail' => $user->name));

      if ($data['notify_users'] == '1') {
        $this->notifyBatch($user);
        $context['results'][] = t('The e-mail has been sent to %mail', array('%mail' => $user->mail));
      }
    }

    $context['sandbox']['progress'] += 1;
    $context['sandbox']['current_row'] += 1;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch finish callback.
   */
  private function finishedBatch($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t('!count processed.', array('!count' => count($results))));
    }
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', array(
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE))
      );
      drupal_set_message($message, 'error');
    }
  }

  /**
   * Callback: Reset User password.
   */
  private function executeBatch($user) {
    $new_pass = generatePassword(12, TRUE);
    user_save($user, array('pass' => $new_pass));
    drupal_set_message(t('Users passwords reset.'), 'status');
  }

  /**
   * Callback: Notify User.
   */
  private function notifyBatch($user) {
    _user_mail_notify('password_reset', $user);
    drupal_set_message(t('Users notified via email.'), 'status');
  }

}
