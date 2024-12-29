<?php

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Session\AccountInterface;

/**
 * Action that clones a log entity.
 *
 * @Action(
 *   id = "log_clone_action",
 *   label = @Translation("Clones a log"),
 *   type = "log",
 *   confirm_form_route_name = "log.log_clone_action_form"
 * )
 */
class LogClone extends LogActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\log\Entity\LogInterface $object */
    $result = $object->access('view', $account, TRUE)
      ->andIf($object->access('create', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
