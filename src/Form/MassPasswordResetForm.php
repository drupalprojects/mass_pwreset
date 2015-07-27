<?php
/**
 * @file
 * Contains \Drupal\mass_pwreset\Form\MassPasswordResetForm.
 */

namespace Drupal\mass_pwreset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mass_pwreset\Batch;
use Drupal\mass_pwreset\MassPasswordReset;

/**
 * Mass Password Reset Form.
 */
class MassPasswordResetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Options'),
      '#description' => t('Selecting Authenticated user will reset all authenticated roles.'),
      '#open' => TRUE,
    );
    $form['options']['choose_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Choose Roles to Reset'),
      '#options' => user_role_names(),
      '#required' => TRUE,
    );
    $form['options']['include_admin_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include admin user (uid1)'),
      '#description' => t('Include the administrative superuser id 1 account in the list of passwords being reset.'),
      '#default_value' => 0,
      '#states' => array(
        'visible' => array(
          ':input[name="choose_roles[administrator]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['notify'] = array(
      '#type' => 'details',
      '#title' => t('Notify Users'),
      '#open' => TRUE,
    );
    $form['notify']['notify_users'] = array(
      '#type' => 'checkbox',
      '#title' => t('Notify users of password reset via email'),
      '#description' => t("Notify users of password reset with Drupal's password recovery email."),
      '#default_value' => 0,
    );
    $form['notify']['notify_blocked_users'] = array(
      '#type' => 'checkbox',
      '#title' => t('Notify blocked users of password reset via email'),
      '#description' => t("Notify users that are blocked from the site."),
      '#default_value' => 0,
      '#states' => array(
        'visible' => array(
          ':input[name="notify_users"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['reset_passwords'] = array(
      '#type' => 'submit',
      '#value' => t('Reset Passwords'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach ($form_state->getValue(['choose_roles']) as $r) {
      if (!empty($r)) {
        $roles[] = $r;
      }
    }
    $uids = MassPasswordReset::getUidsByRole($roles);

    if ($form_state->getValue(['include_admin_user']) != '1') {
      unset($uids[1]);
    }

    $uids = array_values($uids);

    $data = array(
      'uids' => $uids,
      'notify_users' => $form_state->values(['notify_users']),
    );

    $batch = new BatchPasswordReset($data);

  }

}
