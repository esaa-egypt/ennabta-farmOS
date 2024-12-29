<?php

namespace Drupal\Tests\fraction\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Utility\UpdateException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Provides tests for updating the schema from signed to unsigned.
 *
 * @group Fraction
 */
class FractionUpdateTest extends EntityKernelTestBase {

  /**
   * Connection to the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Database schema instance.
   *
   * @var \Drupal\Core\Database\Schema
   */
  protected $schema;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'fraction',
    'fraction_test',
    'node',
  ];

  /**
   * Fields to check the update.
   *
   * @var array
   */
  protected $fieldsToUpdate;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = Database::getConnection();
    $this->schema = $this->connection->schema();

    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    FieldStorageConfig::create([
      'field_name' => 'field_fraction_node',
      'entity_type' => 'node',
      'type' => 'fraction',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_fraction_node',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $this->randomMachineName() . '_label',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_fraction_user',
      'entity_type' => 'user',
      'type' => 'fraction',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_fraction_user',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => $this->randomMachineName() . '_label',
    ])->save();

    $this->fieldsToUpdate = [
      [
        'table_name' => 'node_field_data',
        'columns' => [
          'numerator' => 'fraction__numerator',
          'denominator' => 'fraction__denominator',
        ],
      ],
      [
        'table_name' => 'node__field_fraction_node',
        'columns' => [
          'numerator' => 'field_fraction_node_numerator',
          'denominator' => 'field_fraction_node_denominator',
        ],
      ],
      [
        'table_name' => 'user__field_fraction_user',
        'columns' => [
          'numerator' => 'field_fraction_user_numerator',
          'denominator' => 'field_fraction_user_denominator',
        ],
      ],
    ];

    // Revert all the fields (with no data) to be unsigned so the post_update
    // hook can be run.
    foreach ($this->fieldsToUpdate as $field) {
      $this->schema->changeField($field['table_name'], $field['columns']['denominator'], $field['columns']['denominator'], [
        'type' => 'int',
        'not null' => TRUE,
        'unsigned' => TRUE,
        'default' => 1,
      ]);
    }
  }

  /**
   * Tests the denominator update.
   */
  public function testUpdateDenominatorSigned() {
    // Unsigned fields should reject negative values.
    foreach ($this->fieldsToUpdate as $field) {
      if ($field['table_name'] == 'node_field_data') {
        continue;
      }
      $this->assertFalse($this->tryUnsignedInsert($field['table_name'], $field['columns']), 'Column rejected a negative value.');
    }

    // Run the fraction update only.
    $post_update_registry = $this->container->get('update.post_update_registry');
    foreach ($post_update_registry->getUpdateFunctions('fraction') as $function) {
      $function();
    }

    // Once the field is changed, negative values should be accepted. There's
    // no unintrusive way to consistently check the actual SQL schema, so
    // checking that there's a change on behaviour regarding signed/unsigned is
    // the fastest way to check.
    foreach ($this->fieldsToUpdate as $field) {
      if ($field['table_name'] == 'node_field_data') {
        continue;
      }
      $this->assertTrue($this->tryUnsignedInsert($field['table_name'], $field['columns']), 'Column accepted a negative value.');
    }
  }

  /**
   * Tests that data over the limit triggers a message on update.
   */
  public function testUpdateDenominatorSignedException() {
    // Unsigned fields should reject negative values.
    foreach ($this->fieldsToUpdate as $field) {
      if ($field['table_name'] == 'node_field_data') {
        continue;
      }
      $this->assertFalse($this->tryUnsignedInsert($field['table_name'], $field['columns']), 'Column rejected a negative value.');
    }

    // Insert a value higher than the signed int bounds so that, when checked
    // will trigger the update exception.
    $this->container->get('database')->insert('node__field_fraction_node')
      ->fields(
        [
          'entity_id' => 1,
          'revision_id' => 1,
          'delta' => 0,
          'field_fraction_node_numerator' => 9,
          'field_fraction_node_denominator' => 2147483699,
        ]
      )
      ->execute();
    try {
      // Run the fraction update only.
      $post_update_registry = $this->container->get('update.post_update_registry');
      foreach ($post_update_registry->getUpdateFunctions('fraction') as $function) {
        $function();
      }
      // If the code reach this point, means that the update succeeded.
      $this->fail('Failed due to update going through when it should not.');
    }
    catch (UpdateException $e) {
      foreach ($this->fieldsToUpdate as $field) {
        if ($field['table_name'] == 'node_field_data') {
          continue;
        }
        $this->assertFalse($this->tryUnsignedInsert($field['table_name'], $field['columns']), 'Column rejected a negative value.');
      }
    }
  }

  /**
   * Tries to insert a negative value into columns defined as unsigned.
   *
   * @see \Drupal\KernelTests\Core\Database\SchemaTest::tryUnsignedInsert()
   */
  public function tryUnsignedInsert($table_name, $columns) {

    try {
      $this->container->get('database')->insert($table_name)
        ->fields(
          [
            'entity_id' => 1,
            'revision_id' => 1,
            'delta' => 0,
            $columns['numerator'] => 1,
            $columns['denominator'] => -10,
          ]
        )
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
