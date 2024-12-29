<?php

namespace Drupal\fraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\fraction\Fraction;

/**
 * Format fraction as percentage.
 *
 * @FieldFormatter(
 *   id = "fraction_percentage",
 *   label = @Translation("Percentage"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class PercentageFormatter extends FractionDecimalFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Iterate through the items.
    foreach ($items as $delta => $item) {
      /** @var \Drupal\fraction\Fraction $percentage */
      $percentage = $item->fraction->multiply(Fraction::createFromDecimal('100'));

      $auto_precision = !empty($this->getSetting('auto_precision'));
      $output = $percentage->toDecimal($this->getSetting('precision'), $auto_precision) . '%';

      $elements[$delta] = [
        '#markup' => $this->viewOutput($item, $output),
      ];
    }

    return $elements;
  }

}
