<?php

namespace Drupal\entity_reference_integrity;

use Drupal\Core\Entity\EntityInterface;

/**
 * An interface for calculating entity dependency.
 */
interface EntityReferenceDependencyManagerInterface {

  /**
   * Check if an entity has dependent entties.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return bool
   *   If the entity is referenced from elsewhere.
   */
  public function hasDependents(EntityInterface $entity);

  /**
   * List the entities that reference the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Array of entity type IDs with arrays of loaded entities.
   */
  public function getDependentEntities(EntityInterface $entity);

  /**
   * List the entity IDs that reference the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Array of entity type IDs with arrays of entity IDs.
   */
  public function getDependentEntityIds(EntityInterface $entity);

  /**
   * Build an access denied reason string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that has dependents.
   * @param bool $translate
   *   Optional boolean to translate the string. Defaults to TRUE.
   *
   * @return string
   */
  public static function getAccessDeniedReason(EntityInterface $entity, bool $translate = TRUE);

}
