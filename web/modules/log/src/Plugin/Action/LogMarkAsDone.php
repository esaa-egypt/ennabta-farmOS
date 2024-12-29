<?php

namespace Drupal\log\Plugin\Action;

/**
 * Action that marks a log as done.
 *
 * @Action(
 *   id = "log_mark_as_done_action",
 *   label = @Translation("Sets a Log as done"),
 *   type = "log"
 * )
 */
class LogMarkAsDone extends LogStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'done';

}
