<?php

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Session\AccountInterface;

/**
 * Action that reschedules a log entity.
 *
 * @Action(
 *   id = "log_reschedule_action",
 *   label = @Translation("Reschedules a log"),
 *   type = "log",
 *   confirm_form_route_name = "log.log_schedule_action_form"
 * )
 */
class LogReschedule extends LogActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\log\Entity\LogInterface $object */
    $result = $object->get('timestamp')->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
