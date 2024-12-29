<?php

namespace Drupal\entity_reference_integrity_enforce;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface;
use Drupal\entity_reference_integrity_enforce\Exception\ProtectedEntityException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook into entity deletes and throw an exception to prevent them disappearing.
 */
class EntityPredelete implements ContainerInjectionInterface {

  /**
   * The dependency manager.
   *
   * @var \Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface
   */
  protected $dependencyManager;

  /**
   * The entity type IDs protection is enabled for.
   *
   * @var array
   */
  protected $enabledEntityTypeIds;

  /**
   * Create a DeleteFormAlter object.
   */
  public function __construct(EntityReferenceDependencyManagerInterface $calculator, array $enabled_entity_type_ids) {
    $this->dependencyManager = $calculator;
    $this->enabledEntityTypeIds = $enabled_entity_type_ids;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_reference_integrity.dependency_manager'),
      $container->get('config.factory')->get('entity_reference_integrity_enforce.settings')->get('enabled_entity_type_ids')
    );
  }

  /**
   * Implements hook_entity_delete().
   */
  public function entityDelete(EntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), $this->enabledEntityTypeIds, TRUE) && $this->dependencyManager->hasDependents($entity)) {
      throw new ProtectedEntityException(sprintf('Cannot delete "%s" of type "%s" with label "%s" and ID "%s" because other content is referencing it and the integrity of this entity type is enforced.', $entity->getEntityTypeId(), $entity->bundle(), $entity->label(), $entity->id()));
    }
  }

}
