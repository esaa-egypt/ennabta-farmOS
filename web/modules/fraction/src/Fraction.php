<?php

namespace Drupal\fraction;

/**
 * A simple class for representing and acting upon a fraction.
 */
class Fraction implements FractionInterface {

  /**
   * Numerator of the fraction.
   *
   * @var string
   */
  protected $numerator;

  /**
   * Denominator of the fraction.
   *
   * @var string
   */
  protected $denominator;

  /**
   * Constructs a Fraction object.
   *
   * @param string|int $numerator
   *   The fraction's numerator. Defaults to 0.
   * @param string|int $denominator
   *   The fraction's denominator. Defaults to 1.
   */
  public function __construct($numerator = 0, $denominator = 1) {
    $this->setNumerator($numerator);
    $this->setDenominator($denominator);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDecimal($value) {

    // Calculate the precision by counting the number of decimal places.
    $precision = strlen(substr(strrchr($value, '.'), 1));

    // Create the denominator by raising 10 to the power of the precision.
    if (function_exists('bcpow')) {
      $denominator = bcpow(10, $precision);
    }
    else {
      $denominator = pow(10, $precision);
    }

    // Calculate the numerator by multiplying the value by the denominator.
    if (function_exists('bcmul')) {
      $numerator = bcmul($value, $denominator, 0);
    }
    else {
      $numerator = $value * $denominator;
    }

    return new Fraction($numerator, $denominator);
  }

  /**
   * {@inheritdoc}
   */
  public function setNumerator($value) {

    // Cast the value as a string and save it.
    $this->numerator = (string) $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDenominator($value) {

    // Protect against division by zero.
    if (empty($value)) {
      $this->setNumerator(0);
      $value = 1;
    }

    // Normalize negative fractions.
    // If the denominator is negative, invert the signs for both numbers.
    if ($value < 0) {
      $numerator = $this->getNumerator();
      $numerator = $numerator * -1;
      $this->setNumerator($numerator);
      $value = $value * -1;
    }

    // Cast the value as a string and save it.
    $this->denominator = (string) $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumerator() {
    return $this->numerator;
  }

  /**
   * {@inheritdoc}
   */
  public function getDenominator() {
    return $this->denominator;
  }

  /**
   * {@inheritdoc}
   */
  public function toString(string $separator = '/') {

    // Get the numerator and denominator.
    $numerator = $this->getNumerator();
    $denominator = $this->getDenominator();

    // Concatenate with the separator and return.
    return $numerator . $separator . $denominator;
  }

  /**
   * {@inheritdoc}
   */
  public function toDecimal(int $precision = 0, bool $auto_precision = FALSE) {

    // Get the numerator and denominator.
    $numerator = $this->getNumerator();
    $denominator = $this->getDenominator();

    // If auto precision is on figure out the maximum precision.
    if ($auto_precision) {

      // If the denominator is base-10, max precision is the number of zeroes
      // in the denominator.
      if ($denominator % 10 == 0) {
        $max_precision = strlen($denominator) - 1;
      }

      // Or, if the denominator is 1, max precision is zero.
      elseif ($denominator == 1) {
        $max_precision = 0;
      }

      // Or, if the denominator is a multiple of 2 or 5, the fraction is known
      // to be terminating, and we can figure out the precision.
      elseif ($this->isTerminating()) {
        $max_precision = $this->terminatingPrecision($this->reduce()->getDenominator());
      }

      // Otherwise, max precision is the denominator length.
      else {
        $max_precision = strlen($denominator);
      }

      // Use the greater of the two precisions.
      $precision = ($max_precision > $precision) ? $max_precision : $precision;
    }

    // Divide the numerator by the denominator (using BCMath if available).
    if (function_exists('bcdiv')) {

      // Divide the numerator and denominator, with extra precision.
      $value = bcdiv($numerator, $denominator, $precision + 1);

      // Return a decimal string rounded to the final precision.
      return $this->bcRound($value, $precision);
    }

    // If BCMath is not available, use normal PHP float division and rounding.
    else {
      return (string) round($numerator / $denominator, $precision);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function gcd() {

    // Get the numerator and denominator.
    $numerator = $this->getNumerator();
    $denominator = $this->getDenominator();

    // Make sure both numbers are positive.
    $a = str_replace('-', '', $numerator);
    $b = str_replace('-', '', $denominator);

    // Euclid's algorithm gives us the greatest common divisor.
    // Use BCMath's modulus function if available.
    if (function_exists('bcmod')) {
      while ($b != 0) {
        $t = $b;
        $b = bcmod($a, $b);
        $a = $t;
      }
    }
    else {
      while ($b != 0) {
        $t = $b;
        $b = $a % $b;
        $a = $t;
      }
    }

    return (string) $a;
  }

  /**
   * {@inheritdoc}
   */
  public function reduce() {

    // Get the numerator and denominator.
    $numerator = $this->getNumerator();
    $denominator = $this->getDenominator();

    // Calculate the greatest common divisor.
    $gcd = $this->gcd();

    // Divide the numerator and denominator by the gcd.
    // Use BCMath division if available.
    if (function_exists('bcdiv')) {
      $numerator = bcdiv($numerator, $gcd, 0);
      $denominator = bcdiv($denominator, $gcd, 0);
    }
    else {
      $numerator = $numerator / $gcd;
      $denominator = $denominator / $gcd;
    }

    // Create a new Fraction object.
    return new Fraction($numerator, $denominator);
  }

  /**
   * {@inheritdoc}
   */
  public function reciprocate() {

    // Get the numerator and denominator, but flipped.
    $numerator = $this->getDenominator();
    $denominator = $this->getNumerator();

    // Create a new Fraction object.
    return new Fraction($numerator, $denominator);
  }

  /**
   * {@inheritdoc}
   */
  public function add(Fraction $fraction) {

    // Get the numerator and denominator of each fraction.
    $numerator1 = $this->getNumerator();
    $denominator1 = $this->getDenominator();
    $numerator2 = $fraction->getNumerator();
    $denominator2 = $fraction->getDenominator();

    // Calculate the sum of the two fractions.
    // Use BCMath if available.
    if (function_exists('bcmul') && function_exists('bcadd')) {
      $denominator = bcmul($denominator1, $denominator2, 0);
      $numerator = bcadd(bcmul($numerator1, $denominator2, 0), bcmul($numerator2, $denominator1, 0), 0);
    }
    else {
      $denominator = $denominator1 * $denominator2;
      $numerator = $numerator1 * $denominator2 + $numerator2 * $denominator1;
    }

    // Create a new Fraction object and reduce it.
    $fraction = new Fraction($numerator, $denominator);
    $fraction = $fraction->reduce();
    return $fraction;
  }

  /**
   * {@inheritdoc}
   */
  public function subtract(Fraction $fraction) {

    // Get the numerator and denominator of each fraction.
    $numerator1 = $this->getNumerator();
    $denominator1 = $this->getDenominator();
    $numerator2 = $fraction->getNumerator();
    $denominator2 = $fraction->getDenominator();

    // Calculate the difference of the two fractions.
    // Use BCMath if available.
    if (function_exists('bcmul') && function_exists('bcsub')) {
      $denominator = bcmul($denominator1, $denominator2, 0);
      $numerator = bcsub(bcmul($numerator1, $denominator2, 0), bcmul($numerator2, $denominator1, 0), 0);
    }
    else {
      $denominator = $denominator1 * $denominator2;
      $numerator = $numerator1 * $denominator2 - $numerator2 * $denominator1;
    }

    // Create a new Fraction object and reduce it.
    $fraction = new Fraction($numerator, $denominator);
    $fraction = $fraction->reduce();
    return $fraction;
  }

  /**
   * {@inheritdoc}
   */
  public function multiply(Fraction $fraction) {

    // Get the numerator and denominator of each fraction.
    $numerator1 = $this->getNumerator();
    $denominator1 = $this->getDenominator();
    $numerator2 = $fraction->getNumerator();
    $denominator2 = $fraction->getDenominator();

    // Calculate the product of the two fractions.
    // Use BCMath if available.
    if (function_exists('bcmul')) {
      $numerator = bcmul($numerator1, $numerator2, 0);
      $denominator = bcmul($denominator1, $denominator2, 0);
    }
    else {
      $numerator = $numerator1 * $numerator2;
      $denominator = $denominator1 * $denominator2;
    }

    // Create a new Fraction object and reduce it.
    $fraction = new Fraction($numerator, $denominator);
    $fraction = $fraction->reduce();
    return $fraction;
  }

  /**
   * {@inheritdoc}
   */
  public function divide(Fraction $fraction) {

    // Reciprocate the fraction.
    $reciprocal = $fraction->reciprocate();

    // Get the numerator and denominator of each fraction.
    $numerator1 = $this->getNumerator();
    $denominator1 = $this->getDenominator();
    $numerator2 = $reciprocal->getNumerator();
    $denominator2 = $reciprocal->getDenominator();

    // Calculate the quotient of the two fractions.
    // Use BCMath if available.
    if (function_exists('bcmul')) {
      $numerator = bcmul($numerator1, $numerator2, 0);
      $denominator = bcmul($denominator1, $denominator2, 0);
    }
    else {
      $numerator = $numerator1 * $numerator2;
      $denominator = $denominator1 * $denominator2;
    }

    // Create a new Fraction object and reduce it.
    $fraction = new Fraction($numerator, $denominator);
    $fraction = $fraction->reduce();
    return $fraction;
  }

  /**
   * Test if the fraction is terminating.
   *
   * @return bool
   *   Returns TRUE if the fraction is terminating, FALSE if it is repeating.
   */
  protected function isTerminating() {
    $d = $this->getDenominator();
    $d /= $this->gcd();
    while ($d % 2 == 0) {
      $d /= 2;
    }
    while ($d % 5 == 0) {
      $d /= 5;
    }
    return $d == 1;
  }

  /**
   * Given the denominator of a terminating fraction, calculate the precision.
   *
   * @param string $denominator
   *   The denominator.
   * @param int $exponent
   *   An exponent that is incremented with each iteration.
   *
   * @return int
   *   The number of decimal places required to represent the fraction as a
   *   decimal.
   */
  protected function terminatingPrecision(string $denominator, int $exponent = 1) {
    if (function_exists('bcpow')) {
      $done = bcmod(bcpow(10, $exponent), $denominator) === '0';
    }
    else {
      $done = pow(10, $exponent) % $denominator === 0;
    }
    return $done ? $exponent : $this->terminatingPrecision($denominator, $exponent + 1);
  }

  /**
   * BCMath decimal rounding function.
   *
   * @param int|float|string $value
   *   The value to round.
   * @param int $precision
   *   The desired decimal precision.
   *
   * @return string
   *   Returns a rounded decimal value, as a PHP string.
   */
  protected function bcRound($value, $precision) {
    if ($value[0] != '-') {
      return bcadd($value, '0.' . str_repeat('0', $precision) . '5', $precision);
    }
    return bcsub($value, '0.' . str_repeat('0', $precision) . '5', $precision);
  }

}
