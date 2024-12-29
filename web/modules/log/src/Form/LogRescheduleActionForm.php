<?php

namespace Drupal\log\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a log reschedule confirmation form.
 */
class LogRescheduleActionForm extends LogActionFormBase {

  /**
   * The action id.
   *
   * @var string
   */
  protected $actionId = 'log_reschedule_action';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_reschedule_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->logs), 'Are you sure you want to reschedule this log?', 'Are you sure you want to reschedule these logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reschedule');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['type_of_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reschedule by a relative date'),
      '#weight' => -10,
    ];
    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => $form['date']['#title'],
      '#weight' => -9,
    ];

    // Datetime fields need to be wrapped for #states to work.
    // @see https://www.drupal.org/project/drupal/issues/2419131
    $form['absolute'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="type_of_date"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['absolute']['date'] = $form['date'];
    unset($form['absolute']['date']['#title']);
    $form['absolute']['date']['#required'] = FALSE;
    unset($form['date']);

    $form['relative'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="type_of_date"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['relative']['amount'] = [
      '#type' => 'number',
      '#size' => 4,
    ];
    $form['relative']['time'] = [
      '#type' => 'select',
      '#options' => [
        'hour' => $this->t('Hours'),
        'day' => $this->t('Days'),
        'week' => $this->t('Weeks'),
        'month' => $this->t('Months'),
        'year' => $this->t('Years'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $type_of_date = $form_state->getValue('type_of_date');
    if ($type_of_date) {
      $amount = $form_state->getValue('amount');
      $time = $form_state->getValue('time');
      if (empty($amount)) {
        $form_state->setError($form['relative']['amount'], 'Please enter the amount of time for rescheduling.');
      }
      if (empty($time)) {
        $form_state->setError($form['relative']['amount'], 'Please enter the time units for rescheduling.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Filter out logs the user doesn't have access to.
    $inaccessible_logs = [];
    $accessible_logs = [];
    $current_user = $this->currentUser();
    foreach ($this->logs as $log) {
      if (!$log->get('timestamp')->access('edit', $current_user) || !$log->get('status')->access('edit', $current_user) || !$log->access('update', $current_user)) {
        $inaccessible_logs[] = $log;
        continue;
      }
      $accessible_logs[] = $log;
    }

    if ($form_state->getValue('confirm') && !empty($accessible_logs)) {
      $count = count($accessible_logs);
      $type_of_date = $form_state->getValue('type_of_date');
      if ($type_of_date) {
        $amount = $form_state->getValue('amount');
        $time = $form_state->getValue('time');
        $sign = ($amount >= 0) ? '+' : '';
        foreach ($accessible_logs as $log) {
          $new_date = new DrupalDateTime();
          $new_date->setTimestamp($log->get('timestamp')->value);
          $new_date->modify("$sign$amount $time");
          if ($log->get('status')->first()->isTransitionAllowed('to_pending')) {
            $log->get('status')->first()->applyTransitionById('to_pending');
          }
          $log->set('timestamp', $new_date->getTimestamp());
          $log->setRevisionLogMessage($form_state->getValue('revision_message'));
          $log->setNewRevision(TRUE);
          $log->save();
        }
      }
      else {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $new_date */
        $new_date = $form_state->getValue('date');
        foreach ($accessible_logs as $log) {
          if ($log->get('status')->first()->isTransitionAllowed('to_pending')) {
            $log->get('status')->first()->applyTransitionById('to_pending');
          }
          $log->set('timestamp', $new_date->getTimestamp());
          $log->setRevisionLogMessage($form_state->getValue('revision_message'));
          $log->setNewRevision(TRUE);
          $log->save();
        }
      }
      $this->messenger()->addMessage($this->formatPlural($count, 'Rescheduled 1 log.', 'Rescheduled @count logs.'));
    }

    // Add warning message if there were inaccessible logs.
    if (!empty($inaccessible_logs)) {
      $inaccessible_count = count($inaccessible_logs);
      $this->messenger()->addWarning($this->formatPlural($inaccessible_count, 'Could not reschedule @count log because you do not have the necessary permissions.', 'Could not reschedule @count logs because you do not have the necessary permissions.'));
    }

    parent::submitForm($form, $form_state);
  }

}
