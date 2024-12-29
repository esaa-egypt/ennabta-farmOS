<?php

namespace Drupal\log\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Log source from database.
 *
 * @MigrateSource(
 *   id = "d7_log",
 *   source_module = "log"
 * )
 */
class Log extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('log', 'l')
      ->fields('l')
      ->distinct()
      ->orderBy('id');

    if (isset($this->configuration['bundle'])) {
      $query->condition('l.type', (array) $this->configuration['bundle'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('The log ID'),
      'name' => $this->t('The log name'),
      'type' => $this->t('The log type'),
      'uid' => $this->t('The log author ID'),
      'timestamp' => $this->t('Timestamp of the event being logged'),
      'created' => $this->t('Timestamp when the log was created'),
      'changed' => $this->t('Timestamp when the log was last modified'),
      'done' => $this->t('Boolean indicating whether the log is done (the event happened)'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $id = $row->getSourceProperty('id');
    $type = $row->getSourceProperty('type');

    // Get Field API field values.
    foreach ($this->getFields('log', $type) as $field_name => $field) {
      $row->setSourceProperty($field_name, $this->getFieldValues('log', $field_name, $id));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

}
