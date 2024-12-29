<?php

namespace Drupal\fraction\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for Fraction fields.
 *
 * Overrides query function to use a formula which divides the numerator
 * by the denominator.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("fraction")
 */
class Fraction extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Ensure the main table for this field is included.
    $this->ensureMyTable();

    // Formula for calculating the final value, by dividing numerator by
    // denominator. These are added as additional fields in
    // hook_field_views_data_alter().
    $numerator = $this->tableAlias . '.' . $this->definition['additional fields']['numerator'];
    $denominator = $this->tableAlias . '.' . $this->definition['additional fields']['denominator'];
    // Multiply the numerator field by 1.0 so the database returns a decimal
    // from the computation.
    $formula = '1.0 * ' . $numerator . ' / ' . $denominator;

    // Add the orderby.
    $this->query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_decimal');
  }

}
