<?php

namespace Drupal\fraction\Feeds\Target;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\fraction\Fraction;

/**
 * Defines a fraction field mapper.
 *
 * @FeedsTarget(
 *   id = "fraction",
 *   field_types = {
 *     "fraction",
 *   }
 * )
 */
class FractionTarget extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * Helper function to define the ways to import the fraction data.
   *
   * @return array
   *   Array with all the possible types.
   */
  protected function importTypes() {
    return [
      'fraction' => $this->t('Fraction'),
      'decimal' => $this->t('Decimal'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['type' => 'fraction'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter format'),
      '#options' => $this->importTypes(),
      '#default_value' => $this->configuration['type'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $types = $this->importTypes();
    return $this->t('Import type: %type', ['%type' => $types[$this->configuration['type']]]);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $item = is_string($values['value']) ? trim($values['value']) : $values['value'];
    unset($values['value']);

    switch ($this->configuration['type']) {
      case 'fraction':
        // Pull out the numerator and denominator.
        $parts = is_string($item) ? explode('/', $item) : [];

        if (!empty($parts[0]) && is_numeric($parts[0]) && !empty($parts[1]) && is_numeric($parts[1]) && $parts[1] >= 0) {
          $values['numerator'] = $parts[0];
          $values['denominator'] = $parts[1];
        }
        else {
          $values['numerator'] = '';
          $values['denominator'] = '';
        }
        break;

      case 'decimal':

        // If item is non-numeric or an empty string, create an empty fraction.
        // Otherwise, pass the value to createFromDecimal().
        if (!is_numeric($item) || $item === '') {
          $values['numerator'] = '';
          $values['denominator'] = '';
        }
        else {
          $fraction = Fraction::createFromDecimal($item);
          $values['numerator'] = $fraction->getNumerator();
          $values['denominator'] = $fraction->getDenominator();
        }
        break;
    }
  }

}
