<?php

namespace Drupal\fraction;

/**
 * Defines the interface for Fraction class.
 *
 * @package fraction
 */
interface FractionInterface {

  /**
   * Sets the numerator.
   *
   * @param string|int $numerator
   *   The numerator value.
   *
   * @return Fraction
   *   Returns this Fraction object.
   */
  public function setNumerator($numerator);

  /**
   * Sets the denominator.
   *
   * @param string|int $denominator
   *   The denominator value.
   *
   * @return Fraction
   *   Returns this Fraction object.
   */
  public function setDenominator($denominator);

  /**
   * Gets the numerator.
   *
   * @return string
   *   Returns the numerator value.
   */
  public function getNumerator();

  /**
   * Gets the denominator.
   *
   * @return string
   *   Returns the denominator value.
   */
  public function getDenominator();

  /**
   * Return a string representation of the fraction.
   *
   * @param string $separator
   *   The separator to place between the numerator and denominator.
   *
   * @return string
   *   Returns a string with the numerator, separator, and denominator.
   */
  public function toString(string $separator = '/');

  /**
   * Calculates the decimal equivalent of the fraction.
   *
   * @param int $precision
   *   The desired decimal precision, defaults to 0.
   * @param bool $auto_precision
   *   Boolean, whether or not the precision should be automatically calculated.
   *   This option provides more precision when you need it, and less when you
   *   don't. If set to TRUE, it will try to determine the maximum precision
   *   (this only works if the denominator is base 10). If the resulting
   *   precision is greater than $precision, it will be used instead.
   *
   * @return string
   *   Returns the decimal equivalent of the fraction as a PHP string.
   */
  public function toDecimal(int $precision, bool $auto_precision = FALSE);

  /**
   * Calculates the numerator and denominator from a decimal value.
   *
   * @param string|int|float $value
   *   The decimal value to start with.
   *
   * @return Fraction
   *   Returns this object.
   */
  public static function createFromDecimal($value);

  /**
   * Calculate the fraction's greatest common divisor using Euclid's algorithm.
   *
   * @return string
   *   Returns the greatest common divisor.
   */
  public function gcd();

  /**
   * Reduces the fraction to its simplest form.
   *
   * @return Fraction
   *   Returns the reduced fraction as a new Fraction object.
   */
  public function reduce();

  /**
   * Reciprocates the fraction.
   *
   * @return Fraction
   *   Returns the reciprocal as a new Fraction object.
   */
  public function reciprocate();

  /**
   * Adds another fraction to this one.
   *
   * @param Fraction $fraction
   *   Another fraction object to add to this one.
   *
   * @return Fraction
   *   Returns the arithmetic result as a new Fraction object.
   */
  public function add(Fraction $fraction);

  /**
   * Subtracts another fraction from this one.
   *
   * @param Fraction $fraction
   *   Another fraction object to subtract this one.
   *
   * @return Fraction
   *   Returns the arithmetic result as a new Fraction object.
   */
  public function subtract(Fraction $fraction);

  /**
   * Multiplies this fraction with another one.
   *
   * @param Fraction $fraction
   *   Another fraction object to multiply with.
   *
   * @return Fraction
   *   Returns the arithmetic result as a new Fraction object.
   */
  public function multiply(Fraction $fraction);

  /**
   * Divides this fraction by another one.
   *
   * @param Fraction $fraction
   *   Another fraction object to divide by.
   *
   * @return Fraction
   *   Returns the arithmetic result as a new Fraction object.
   */
  public function divide(Fraction $fraction);

}
