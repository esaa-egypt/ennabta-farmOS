<?php

namespace Drupal\log;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the controller class for logs.
 *
 * This extends the base storage class, adding required special handling for
 * log entities.
 */
class LogStorage extends SqlContentEntityStorage {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend to be used.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, Token $token) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('token'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($update && $this->entityType->isTranslatable()) {
      $this->invokeTranslationHooks($entity);
    }

    // Get the log's current name.
    $current_name = $entity->get('name')->value;

    // We will automatically set the log name under two conditions:
    // 1. Saving new/existing logs without a name.
    // 2. Updating existing logs that were saved using the naming pattern.
    $set_name = FALSE;
    if (empty($current_name)) {
      $set_name = TRUE;
    }
    elseif ($update && !empty($entity->original)) {

      // Generate a log name using the original entity.
      $original_generated_name = $this->generateLogName($entity->original);

      // Compare the current log name to what would have been the original
      // auto-generated name, to determine if the name was auto-generated
      // previously. If it was, we will regenerate it.
      if ($current_name == $original_generated_name) {
        $set_name = TRUE;
      }
    }

    // We must run the parent method before we set the name, so that new logs
    // have an ID that can be used in token replacements.
    // Also, we must run the parent method after the logic above, because the
    // parent method unsets $entity->original.
    parent::doPostSave($entity, $update);

    // Set the log name, if necessary.
    if ($set_name) {

      // Generate a new name.
      $new_name = $this->generateLogName($entity);

      // If the name has been changed, update the entity.
      if ($current_name != $new_name) {
        $entity->set('name', $new_name);
        $entity->save();
      }
    }
  }

  /**
   * Helper method for generating a log name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The log entity.
   *
   * @return string
   *   Returns the generated log name.
   */
  protected function generateLogName(EntityInterface $entity) {

    // Get the log type's naming pattern.
    $name_pattern = $entity->getTypeNamePattern();

    // Pass in an empty bubbleable metadata object, so we can avoid starting a
    // renderer, for example if this happens in a REST resource creating
    // context.
    return $this->token->replace(
      $name_pattern,
      ['log' => $entity],
      [],
      new BubbleableMetadata()
    );
  }

}
