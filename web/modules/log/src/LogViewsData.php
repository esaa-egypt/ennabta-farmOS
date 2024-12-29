<?php

namespace Drupal\log;

use Drupal\entity\EntityViewsData;

/**
 * Provides views data for the file entity type.
 */
class LogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['log_field_data']['timestamp']['sort']['id'] = 'log_standard';
    $data['log_field_data']['timestamp']['field']['id'] = 'log_field';

    return $data;
  }

}
