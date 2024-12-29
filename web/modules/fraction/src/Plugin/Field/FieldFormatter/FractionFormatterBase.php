<?php

namespace Drupal\fraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for fraction formatters.
 */
abstract class FractionFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'prefix_suffix' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display prefix and suffix'),
      '#default_value' => $this->getSetting('prefix_suffix'),
      '#weight' => 10,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('prefix_suffix')) {
      $summary[] = $this->t('Display with prefix and suffix.');
    }

    return $summary;
  }

  /**
   * Account for extra output such as prefixes and suffixes.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item to evaluate.
   * @param string $output
   *   The output of the field.
   *
   * @return string
   *   The output with all relevant additions.
   */
  protected function viewOutput(FieldItemInterface $item, $output = '') {
    $field_settings = $this->getFieldSettings();
    // Account for prefix and suffix.
    if ($this->getSetting('prefix_suffix')) {
      $prefixes = isset($field_settings['prefix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $field_settings['prefix'])) : [''];
      $suffixes = isset($field_settings['suffix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $field_settings['suffix'])) : [''];
      $prefix = (count($prefixes) > 1) ? $this->formatPlural($item->decimal, $prefixes[0], $prefixes[1]) : $prefixes[0];
      $suffix = (count($suffixes) > 1) ? $this->formatPlural($item->decimal, $suffixes[0], $suffixes[1]) : $suffixes[0];
      $output = $prefix . $output . $suffix;
    }

    return $output;
  }

}
