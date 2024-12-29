<?php

namespace Drupal\log\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\log\Event\LogEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a log clone confirmation form.
 */
class LogCloneActionForm extends LogActionFormBase {

  /**
   * The action id.
   *
   * @var string
   */
  protected $actionId = 'log_clone_action';

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a LogCloneActionForm form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $user, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($temp_store_factory, $entity_type_manager, $user);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_clone_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->logs), 'Are you sure you want to clone this log?', 'Are you sure you want to clone these logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clone');
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
      if (!$log->access('view', $current_user) || !$log->access('create', $current_user)) {
        $inaccessible_logs[] = $log;
        continue;
      }
      $accessible_logs[] = $log;
    }

    /** @var \Drupal\Core\Datetime\DrupalDateTime $new_date */
    if ($form_state->getValue('confirm') && !empty($accessible_logs)) {
      $new_date = $form_state->getValue('date');
      $count = count($this->logs);
      foreach ($accessible_logs as $log) {
        $cloned_log = $log->createDuplicate();
        $cloned_log->set('timestamp', $new_date->getTimestamp());
        $cloned_log->setOwnerId($current_user->id());
        $cloned_log->setRevisionLogMessage($form_state->getValue('revision_message'));

        // Dispatch the log_clone event.
        $event = new LogEvent($cloned_log);
        $this->eventDispatcher->dispatch($event, LogEvent::CLONE);
        $event->log->save();
      }
      $this->messenger()->addMessage($this->formatPlural($count, 'Cloned 1 log.', 'Cloned @count logs.'));
    }

    // Add warning message if there were inaccessible logs.
    if (!empty($inaccessible_logs)) {
      $inaccessible_count = count($inaccessible_logs);
      $this->messenger()->addWarning($this->formatPlural($inaccessible_count, 'Could not clone @count log because you do not have the necessary permissions.', 'Could not clone @count logs because you do not have the necessary permissions.'));
    }

    parent::submitForm($form, $form_state);
  }

}
