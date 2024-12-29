<?php

namespace Drupal\entity_reference_validators\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class CircularReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CircularReferenceConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var CircularReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }

    $entity = $value->getEntity();
    if (!isset($entity) || $entity->isNew()) {
      return;
    }
    $field_name = $value->getFieldDefinition()->getName();

    foreach ($value as $delta => $item) {
      $id = $item->target_id;
      // '0' or NULL are considered valid empty references.
      if (empty($id)) {
        continue;
      }

      $error_entities = $this->isEntityReferenced($entity, $item->entity, $field_name, (bool) $constraint->deep);
      if ($error_entities) {
        // @todo use graph in $error_entities to improve error message when
        //   doing a deep check.
        $this->context->buildViolation($constraint->message)
          ->setParameter('%type', $item->entity->getEntityTypeId())
          ->setParameter('%id', $item->entity->id())
          ->setInvalidValue($item->entity)
          ->atPath((string) $delta . '.target_id')
          ->addViolation();
      }
    }
  }

  /**
   * Determines if the entity is referenced.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being validated.
   * @param \Drupal\Core\Entity\EntityInterface $referenced_entity
   *   Related entity.
   * @param string $field_name
   *   The name of the field being validated.
   * @param bool $deep
   *   Whether to check the entities referenced by the referenced entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The chain of entities that leads to the entity. An empty array if the
   *   entity is not referenced.
   */
  protected function isEntityReferenced(EntityInterface $entity, EntityInterface $referenced_entity, string $field_name, bool $deep) {
    // First check if the entity is self, then it's a circular dependency.
    if ($entity->id() === $referenced_entity->id() && $entity->getEntityTypeId() === $referenced_entity->getEntityTypeId()) {
      return [$referenced_entity];
    }

    if ($deep) {
      // If the entity is not self, get down the rabbit hole.
      foreach ($referenced_entity->get($field_name)->referencedEntities() as $child_entity) {
        // Recursively call to the this method to visit the whole tree.
        $entity_with_reference = $this->isEntityReferenced($entity, $child_entity, $field_name, $deep);
        if ($entity_with_reference) {
          array_unshift($entity_with_reference, $referenced_entity);
          return $entity_with_reference;
        }
      }
    }

    return [];
  }

}
