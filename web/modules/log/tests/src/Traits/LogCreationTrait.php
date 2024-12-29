<?php

namespace Drupal\Tests\log\Traits;

use Drupal\log\Entity\Log;

/**
 * Provides methods to create log entities.
 *
 * This trait is meant to be used only by test classes.
 */
trait LogCreationTrait {

  /**
   * Creates a log entity.
   *
   * @param array $values
   *   Array of values to feed the entity.
   *
   * @return \Drupal\log\Entity\LogInterface
   *   The log entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createLogEntity(array $values = []) {
    /** @var \Drupal\log\Entity\LogInterface $entity */
    $entity = Log::create($values + [
      'name' => $this->randomMachineName(),
      'type' => 'default',
    ]);
    $entity->save();
    return $entity;
  }

}
