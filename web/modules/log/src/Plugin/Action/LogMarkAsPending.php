<?php

namespace Drupal\log\Plugin\Action;

/**
 * Action that marks a log as pending.
 *
 * @Action(
 *   id = "log_mark_as_pending_action",
 *   label = @Translation("Sets a Log as pending"),
 *   type = "log"
 * )
 */
class LogMarkAsPending extends LogStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'pending';

}
