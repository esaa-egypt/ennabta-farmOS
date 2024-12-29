<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides tests for the Entity Field Manager.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityFieldManager
 *
 * @group Entity
 */
class EntityFieldManagerTest extends EntityKernelTestBase {

  /**
   * The bundle field map key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $bundleFieldMap;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->bundleFieldMap = $this->container->get('keyvalue')
      ->get('entity.definitions.bundle_field_map');
  }

  /**
   * Tests rebuilding the bundle field map.
   *
   * @covers ::rebuildBundleFieldMap
   */
  public function testRebuildBundleFieldMap() {
    // Set up some fields on the 'entity_test' and 'user' entity types.
    entity_test_create_bundle('bundle1');
    entity_test_create_bundle('bundle2');
    foreach (['entity_test', 'user'] as $entity_type_id) {
      FieldStorageConfig::create([
        'field_name' => 'test_field',
        'type' => 'text',
        'entity_type' => $entity_type_id,
      ])->save();
    }
    FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'bundle1',
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'bundle2',
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'user',
      'bundle' => 'user',
    ])->save();

    // Save original field maps.
    $original_field_map = $this->container->get('entity_field.manager')->getFieldMap();
    $this->assertIsArray($original_field_map);
    $original_bundle_field_map = $this->bundleFieldMap->getAll();
    $this->assertIsArray($original_bundle_field_map);

    // Simulate corrupt data by adding a nonexistent entity type.
    $this->bundleFieldMap->deleteAll();
    $this->bundleFieldMap->set('nonexistent', $original_bundle_field_map['user']);
    // Manually clear the entity_field_map cache to rebuild with corrupt data.
    $this->container->set('entity_field.manager', NULL);
    $this->container->get('cache.discovery')->delete('entity_field_map');
    $bad_field_map = $this->container->get('entity_field.manager')->getFieldMap();
    $this->assertIsArray($bad_field_map);
    $this->assertNotEquals($original_field_map, $bad_field_map);

    // Rebuild bundle field map.
    $this->container->get('entity_field.manager')->rebuildBundleFieldMap();

    // Check that bundle field map was rebuilt.
    $new_bundle_field_map = $this->bundleFieldMap->getAll();
    $this->assertIsArray($new_bundle_field_map);
    $this->assertEquals($original_bundle_field_map, $new_bundle_field_map, 'The rebuilt bundle field map matches the original one.');

    // Check that field map was rebuilt.
    $this->container->set('entity_field.manager', NULL);
    $new_field_map = $this->container->get('entity_field.manager')->getFieldMap();
    $this->assertIsArray($new_field_map);
    $this->assertEquals($original_field_map, $new_field_map, 'The rebuilt field map matches the original one.');
  }

}
