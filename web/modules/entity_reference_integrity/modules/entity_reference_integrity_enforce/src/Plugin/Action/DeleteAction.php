<?php

namespace Drupal\entity_reference_integrity_enforce\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Plugin\Action\DeleteAction as CoreDeleteAction;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the core DeleteAction plugin.
 *
 * @see \Drupal\Core\Action\Plugin\Action\DeleteAction
 */
class DeleteAction extends CoreDeleteAction {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // First check if the account has access.
    $access = parent::access($object, $account, TRUE);

    // Bail if the object is not an entity or access is denied.
    if (!$object instanceof EntityInterface || !$access->isAllowed()) {
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Check for dependent entities.
    $has_dependents = FALSE;
    $enabled_entity_type_ids = \Drupal::config('entity_reference_integrity_enforce.settings')->get('enabled_entity_type_ids');
    if (in_array($object->getEntityTypeId(), $enabled_entity_type_ids, TRUE)) {
      $has_dependents = $this->entityTypeManager->getHandler($object->getEntityTypeId(), 'entity_reference_integrity')->hasDependents($object);
    }

    // Deny access if it has dependents.
    $reason = $this->t('Can not delete the @entity_type_label %entity_label as it is being referenced by another entity.', [
      '@entity_type_label' => $object->getEntityType()->getLabel(),
      '%entity_label' => $object->label(),
    ])->render();
    $integrity_access = AccessResult::forbiddenIf($has_dependents, $reason);
    $access = $access->orIf($integrity_access);

    return $return_as_object ? $access : $access->isAllowed();
  }

}
