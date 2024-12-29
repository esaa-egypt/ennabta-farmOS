<?php

namespace Drupal\log\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;

/**
 * Field handler to enable custom click-sort behavior for timestamp and id.
 *
 * @ViewsField("log_field")
 */
class LogField extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    // No column selected, can't continue.
    if (empty($this->options['click_sort_column'])) {
      return;
    }

    $this->ensureMyTable();
    $field_storage_definition = $this->getFieldStorageDefinition();
    $column = $this->getTableMapping()->getFieldColumnName($field_storage_definition, $this->options['click_sort_column']);
    if (!isset($this->aliases[$column])) {
      // Column is not in query; add a sort on it (without adding the column).
      $this->aliases[$column] = $this->tableAlias . '.' . $column;
    }
    $this->query->addOrderBy(NULL, NULL, $order, $this->aliases[$column]);
    $this->query->addOrderBy($this->tableAlias, 'id', $order);
  }

}
