<?php

namespace Drupal\fraction\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Plugin implementation of the 'fraction_constraint'.
 *
 * @Constraint(
 *   id = "FractionConstraint",
 *   label = @Translation("Fraction constraint", context = "Validation"),
 * )
 */
class FractionConstraint extends Constraint {

  /**
   * Constraint message if denominator is empty or zero.
   *
   * @var string
   */
  public $denominatorNotZero = 'The denominator of a fraction cannot be zero or empty.';

  /**
   * Constraint message if denominator is out of range from 1 to 2147483647.
   *
   * @var string
   */
  public $denominatorOutOfRange = 'The denominator must be between 1 and 2147483647';

  /**
   * Constraint message if numerator is out of range.
   *
   * Range spans from -9223372036854775808 to 9223372036854775807
   *
   * @var string
   */
  public $numeratorOutOfRange = 'The numerator must be between -9223372036854775808 and 9223372036854775807.';

  /**
   * Constraint message if number of decimal digits is greater than 9.
   *
   * @var string
   */
  public $maxNumberOfDecimals = 'The maximum number of digits after the decimal place is 9.';

}
