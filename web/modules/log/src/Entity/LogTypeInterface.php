<?php

namespace Drupal\log\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining Log type entities.
 */
interface LogTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * Returns the name pattern for a log type.
   *
   * @return string
   *   The log type name pattern.
   */
  public function getNamePattern();

}
