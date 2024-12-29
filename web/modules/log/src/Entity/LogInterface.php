<?php

namespace Drupal\log\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Log entities.
 *
 * @ingroup log
 */
interface LogInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Gets the log name.
   *
   * @return string
   *   The log name.
   */
  public function getName();

  /**
   * Sets the log name.
   *
   * @param string $name
   *   The log name.
   *
   * @return \Drupal\log\Entity\LogInterface
   *   The log entity.
   */
  public function setName($name);

  /**
   * Gets the log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the log.
   */
  public function getCreatedTime();

  /**
   * Sets the log creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the log.
   *
   * @return \Drupal\log\Entity\LogInterface
   *   The log entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the name pattern from the log type.
   *
   * @return string
   *   The name pattern.
   */
  public function getTypeNamePattern();

  /**
   * Gets the label of the the log type.
   *
   * @return string
   *   The label of the log type.
   */
  public function getBundleLabel();

}
