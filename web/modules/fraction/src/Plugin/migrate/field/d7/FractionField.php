<?php

namespace Drupal\fraction\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migrate from d7 fraction structure.
 *
 * @MigrateField(
 *   id = "fraction",
 *   core = {7},
 *   source_module = "fraction",
 *   destination_module = "fraction"
 * )
 */
class FractionField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'fraction_default' => 'fraction',
      'fraction_decimal' => 'fraction_decimal',
      'fraction_percentage' => 'fraction_percentage',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'fraction_default' => 'fraction',
      'fraction_decimal' => 'fraction_decimal',
      'fraction_percentage' => 'fraction_percentage',
    ];
  }

}
