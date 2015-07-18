<?php
/**
 * @file
 * Contains \Drupal\mass_pwreset\Form\MassPasswordResetForm.
 */

namespace Drupal\mass_pwreset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
      '#open' => TRUE,
    );
    $form['options']['choose_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Choose for what role'),
      '#options' => array('Temporary', 'Options'),
      '#required' => TRUE,
    );
    $form['options']['notify_users'] = array(
      '#type' => 'checkbox',
      '#title' => t('Notify users of password reset via email'),
      '#description' => t("Notify users of password reset with Drupal's password recovery email."),
      '#default_value' => 0,
    );
    $form['options']['include_admin_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include admin user (uid1)'),
      '#description' => t('Include the administrative superuser id 1 account in the list of passwords being reset.'),
      '#default_value' => 0,
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
    drupal_set_message('The form was submitted.');
  }

}
