<?php

/**
 * @file
 * Post update functions for Fraction.
 */

use Drupal\Core\Utility\UpdateException;

/**
 * Alter schema to make denominator signed.
 *
 * This is done so the field is leveled between MySQL and Postgres and migration
 * is possible between the two. When a integer is unsigned in Postges, Drupal
 * will create a bigint for it, while with MySQL, it would create a regular int.
 *
 * @see \Drupal\Core\Database\Driver\pgsql\Schema::processField()
 * @see https://www.drupal.org/project/fraction/issues/2729315
 */
function fraction_post_update_make_denominator_signed() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $entity_storage_schema = \Drupal::keyValue('entity.storage_schema.sql');
  $entity_field_manager->clearCachedFieldDefinitions();
  $updated_fields = $fields_to_update = [];

  // Get all fraction fields in the system.
  $fraction_fields = $entity_field_manager->getFieldMapByFieldType('fraction');
  foreach ($fraction_fields as $entity_type_id => $fields) {
    $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();
    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field_info) {
      $storage_definition = $entity_field_manager->getFieldStorageDefinitions($entity_type_id)[$field_name];
      $column_name = $table_mapping->getFieldColumnName($storage_definition, 'denominator');
      $table_name = $table_mapping->getFieldTableName($field_name);
      $max_denominator = \Drupal::database()->query('SELECT MAX(' . $column_name . ') FROM {' . $table_name . '}')->fetchField();

      $revision_table_name = $table_mapping->getDedicatedRevisionTableName($field_storage_definitions[$field_name]);
      if (\Drupal::database()->schema()->tableExists($revision_table_name)) {
        $max_revision_denominator = \Drupal::database()->query('SELECT MAX(' . $column_name . ') FROM {' . $revision_table_name . '}')->fetchField();
        $max_denominator = max($max_denominator, $max_revision_denominator);
      }

      // Max value on signed int for MySQL or int in PostgreSQL is 2147483648.
      if ($max_denominator >= 2147483648) {
        // If the max value is bigger than the limits for MySQL/Postgres, warn
        // the user.
        throw new UpdateException('Fraction works with signed integer schema fields for denominator, some of your values in the database exceed this limit, please check the fraction field tables and review the data before running this update. See https://www.drupal.org/project/fraction/issues/2729315 for further details.');
      }
      else {
        \Drupal::service('entity.last_installed_schema.repository')->setLastInstalledFieldStorageDefinition($storage_definition);
        $field_schema = $entity_storage_schema->get("$entity_type_id.field_schema_data.$field_name");
        $fields_to_update[] = compact('field_name', 'table_name', 'column_name', 'entity_type_id', 'field_schema', 'revision_table_name');
      }
    }
  }

  if (empty($fields_to_update)) {
    return 'No fraction fields found to update.';
  }

  foreach ($fields_to_update as $field_to_update) {
    $field_name = $field_to_update['field_name'];
    $table_name = $field_to_update['table_name'];
    $column_name = $field_to_update['column_name'];
    $entity_type_id = $field_to_update['entity_type_id'];
    $field_schema = $field_to_update['field_schema'];
    $revision_table_name = $field_to_update['revision_table_name'];
    fraction_alter_denominator_helper($table_name, $column_name);
    unset($field_schema[$table_name]['fields'][$column_name]['unsigned']);
    if (\Drupal::database()->schema()->tableExists($revision_table_name)) {
      unset($field_schema[$revision_table_name]['fields'][$column_name]['unsigned']);
    }
    // Ensure that the field schema is updated accordingly to the change of
    // the field so the entity updates service does not alert of this
    // change.
    $entity_storage_schema->set("$entity_type_id.field_schema_data.$field_name", $field_schema);
    $updated_fields[] = $field_name;
  }

  $entity_field_manager->clearCachedFieldDefinitions();
  return 'Fraction fields updated: ' . implode(',', $updated_fields);
}

/**
 * Helper function that updates the field schema for denominator.
 *
 * @param string $table_name
 *   The database table name.
 * @param string $column_name
 *   The database column name.
 */
function fraction_alter_denominator_helper($table_name, $column_name) {
  \Drupal::database()->schema()->changeField($table_name, $column_name, $column_name, [
    'description' => 'Fraction denominator value',
    'type' => 'int',
    'not null' => TRUE,
    'default' => 1,
  ]);
}

/**
 * Alter schema for the not null changes.
 *
 * @see https://www.drupal.org/project/fraction/issues/3223868
 */
function fraction_post_update_not_null_field_schema_fix() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $entity_storage_schema = \Drupal::keyValue('entity.storage_schema.sql');
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $database_schema = \Drupal::database()->schema();
  $updated_fields = $fields_to_update = [];

  $entity_field_manager->clearCachedFieldDefinitions();
  // Get all fraction fields in the system.
  $fraction_fields = $entity_field_manager->getFieldMapByFieldType('fraction');
  foreach ($fraction_fields as $entity_type_id => $fields) {
    $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();
    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field_info) {
      $storage_definition = $field_storage_definitions[$field_name];
      $column_names = [
        $table_mapping->getFieldColumnName($storage_definition, 'numerator'),
        $table_mapping->getFieldColumnName($storage_definition, 'denominator'),
      ];
      $table_name = $table_mapping->getFieldTableName($field_name);
      $revision_table_name = $table_mapping->getDedicatedRevisionTableName($field_storage_definitions[$field_name]);

      $last_installed_schema_repository->setLastInstalledFieldStorageDefinition($storage_definition);
      $field_schema = $entity_storage_schema->get("$entity_type_id.field_schema_data.$field_name");
      $fields_to_update[] = compact('field_name', 'table_name', 'column_names', 'entity_type_id', 'field_schema', 'revision_table_name');
    }
  }

  if (empty($fields_to_update)) {
    return 'No fraction fields found to update.';
  }

  foreach ($fields_to_update as $field_to_update) {
    $field_name = $field_to_update['field_name'];
    $table_name = $field_to_update['table_name'];
    $column_names = $field_to_update['column_names'];
    $entity_type_id = $field_to_update['entity_type_id'];
    $field_schema = $field_to_update['field_schema'];
    $revision_table_name = $field_to_update['revision_table_name'];

    foreach ($column_names as $column_name) {
      $database_schema->changeField($table_name, $column_name, $column_name, [
        'description' => 'Fraction ' . $column_name . ' value',
        'type' => 'int',
        'not null' => FALSE,
      ]);
      unset($field_schema[$table_name]['fields'][$column_name]['default']);
      if ($database_schema->tableExists($revision_table_name)) {
        unset($field_schema[$revision_table_name]['fields'][$column_name]['default']);
      }
    }
    // Ensure that the field schema is updated accordingly to the change of
    // the field so the entity updates service does not alert of this
    // change.
    $entity_storage_schema->set("$entity_type_id.field_schema_data.$field_name", $field_schema);
    $updated_fields[] = $field_name;
  }

  $entity_field_manager->clearCachedFieldDefinitions();
  return 'Fraction fields updated: ' . implode(',', $updated_fields);
}
