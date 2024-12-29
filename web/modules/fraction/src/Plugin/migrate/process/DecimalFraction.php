<?php

namespace Drupal\fraction\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\fraction\Fraction;

/**
 * Provides a 'DecimalFraction' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "decimal_fraction",
 *  handle_multiples = TRUE
 * )
 *
 * Extracts the numerator and denominator that can be directly mapped to a
 * Fraction field from a decimal input.
 *
 * Example:
 *
 * @code
 *   fraction:
 *     plugin: decimal_fraction
 *     source: decimal
 * @endcode
 */
class DecimalFraction extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_numeric($value) || $value === '') {
      return [
        'numerator' => '',
        'denominator' => '',
      ];
    }
    else {
      $fraction = Fraction::createFromDecimal($value);

      return [
        'numerator' => $fraction->getNumerator(),
        'denominator' => $fraction->getDenominator(),
      ];
    }
  }

}
