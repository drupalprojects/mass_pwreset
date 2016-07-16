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
    return 'mass_pwreset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Role Options'),
      '#description' => $this->t('Select all users or specific roles below.'),
      '#open' => TRUE,
    ];
    $form['options']['authenticated'] = [
      '#type' => 'details',
      '#title' => $this->t('Authenticated Role'),
      '#description' => $this->t('Selecting Authenticated will reset all users.'),
      '#open' => TRUE,
    ];
    $form['options']['authenticated']['authenticated_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all users'),
      '#required' => FALSE,
    ];
    $form['options']['custom_roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#open' => TRUE,
    ];
    $form['options']['custom_roles']['selected_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Roles to Reset'),
      '#options' => mass_pwreset_get_custom_roles(),
      '#required' => FALSE,
      '#states' => [
        'disabled' => [
          ':input[name="authenticated_role"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['notify'] = [
      '#type' => 'details',
      '#title' => $this->t('Notify Users'),
      '#open' => TRUE,
    ];
    $form['notify']['notify_active_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify active users of password reset via email'),
      '#default_value' => 0,
    ];
    $form['notify']['notify_blocked_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify blocked users of password reset via email'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="notify_active_users"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Administrator Reset'),
      '#description' => $this->t('Include the administrative superuser id 1 account in the list of passwords being reset.'),
      '#open' => FALSE,
    ];
    $form['admin']['include_admin_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include admin user (uid1)'),
      '#default_value' => 0,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['reset_passwords'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Passwords'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('authenticated_role') == 1) {
      // Get all user IDs, excluding uid 1.
      $uids = mass_pwreset_get_uids();
      $list_of_roles = 'authenticated role';
    }
    else {
      // Get user IDs from selected roles.
      $roles = $form_state->getValue('selected_roles');
      $uids = mass_pwreset_get_uids_by_selected_roles(array_filter($roles));
      $list_of_roles = implode(', ', array_filter($roles));
    }

    if (!isset($uids)) {
      drupal_set_message(t('There was an error getting user IDs to reset.'), 'error');
      return array();
    }

    // Include the administrative user uid 1 if applicable.
    if ($form_state->getValue('include_admin_user') == 1) {
      array_push($uids, '1');
    }

    $batch_data = array(
      'uids' => $uids,
      'notify_active_users' => $form_state->getValue('notify_active_users'),
      'notify_blocked_users' => $form_state->getValue('notify_blocked_users'),
      'list_of_roles' => $list_of_roles,
    );
    // Initiate the batch process.
    mass_pwreset_multiple_reset($batch_data);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // User must select roles for mass password reset.
    $selected_roles_count = count(array_filter($form_state->getValue('selected_roles')));
    if ($form_state->getValue('authenticated_role') == 0 && $selected_roles_count == 0) {
      $form_state->setErrorByName('authenticated_role', $this->t('Please select all users or select specific roles'));
    }
  }

}
