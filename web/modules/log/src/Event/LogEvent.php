<?php

namespace Drupal\log\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\log\Entity\LogInterface;

/**
 * Event that is fired by log save, delete and clone operations.
 */
class LogEvent extends Event {

  const PRESAVE = 'log_presave';
  const INSERT = 'log_insert';
  const UPDATE = 'log_update';
  const DELETE = 'log_delete';
  const CLONE = 'log_clone';

  /**
   * The Log entity.
   *
   * @var \Drupal\log\Entity\LogInterface
   */
  public LogInterface $log;

  /**
   * Constructs the object.
   *
   * @param \Drupal\log\Entity\LogInterface $log
   *   The Log entity.
   */
  public function __construct(LogInterface $log) {
    $this->log = $log;
  }

}
