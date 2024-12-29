<?php

namespace Drupal\Tests\fraction\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\fraction\Fraction;

/**
 * Provides tests for the Fraction class.
 *
 * @group Fraction
 */
class FractionTest extends UnitTestCase {

  /**
   * Returns a Fraction object from a numerator and denominator.
   *
   * This allows for easier chaining of Fraction methods in tests.
   *
   * @param int $numerator
   *   The fraction's numerator.
   * @param int $denominator
   *   The fraction's denominator.
   *
   * @return \Drupal\fraction\Fraction
   *   Returns a $this->fraction object.
   */
  protected function fraction($numerator = 0, $denominator = 1) {
    return new Fraction($numerator, $denominator);
  }

  /**
   * Test the Fraction class and it's methods.
   */
  public function testFraction() {

    // Test creation of a fraction.
    $fraction = $this->fraction(1, 2);
    $numerator = $fraction->getNumerator();
    $denominator = $fraction->getDenominator();
    $message = 'A fraction of 1/2 should return a numerator of 1.';
    $this->assertEquals($numerator, '1', $message);
    $message = 'A fraction of 1/2 should return a denominator of 2.';
    $this->assertEquals($denominator, '2', $message);

    // Test creation of an empty fraction.
    $fraction = $this->fraction();
    $numerator = $fraction->getNumerator();
    $denominator = $fraction->getDenominator();
    $message = 'An empty fraction should return a numerator of 0.';
    $this->assertEquals($numerator, '0', $message);
    $message = 'An empty fraction should return a denominator of 1.';
    $this->assertEquals($denominator, '1', $message);

    // Test returning a fraction as a string.
    $result = $this->fraction(1, 2)->toString();
    $message = 'A fraction with a numerator of 1 and a denominator of 2 should return a string of "1/2".';
    $this->assertEquals($result, '1/2', $message);

    // Test returning a fraction as a string with a different separator.
    $result = $this->fraction(1, 2)->toString(':');
    $message = 'A fraction with a numerator of 1 and a denominator of 2 should return a string of "1:2" (when a colon separator is specified).';
    $this->assertEquals($result, '1:2', $message);

    // Test division by zero.
    $result = $this->fraction(1, 0)->toString();
    $message = 'A fraction of 1/0 should return 0/1 (avoid division by zero).';
    $this->assertEquals($result, '0/1', $message);

    // Test normalization of negative fractions.
    $result = $this->fraction(-1, 2)->toString();
    $message = 'A fraction of -1/2 should normalize to -1/2.';
    $this->assertEquals($result, '-1/2', $message);
    $result = $this->fraction(1, -2)->toString();
    $message = 'A fraction of 1/-2 should normalize to -1/2.';
    $this->assertEquals($result, '-1/2', $message);
    $result = $this->fraction(-1, -2)->toString();
    $message = 'A fraction of -1/-2 should normalize to 1/2.';
    $this->assertEquals($result, '1/2', $message);

    // Test converting a fraction to a decimal.
    $result = $this->fraction(1, 2)->toDecimal(1);
    $message = 'A fraction of 1/2 should return a decimal of 0.5 (with precision 1)';
    $this->assertEquals($result, '0.5', $message);

    // Test decimal precision (rounding up).
    $result = $this->fraction(1, 2)->toDecimal(0);
    $message = 'A fraction of 1/2 with no precision should round to 1.';
    $this->assertEquals($result, '1', $message);

    // Test decimal precision (rounding down).
    $result = $this->fraction(2, 5)->toDecimal(0);
    $message = 'A fraction of 2/5 with no precision should round to 0.';
    $this->assertEquals($result, '0', $message);

    // Test automatic precision for base-10 denominator.
    $result = $this->fraction(1, 1000)->toDecimal(2, TRUE);
    $message = 'A fraction of 1/1000 with precision 2 and auto-precision enabled should round to 0.001.';
    $this->assertEquals($result, '0.001', $message);

    // Test automatic precision for denominator of 1.
    $result = $this->fraction(3, 1)->toDecimal(0, TRUE);
    $message = 'A fraction of 3/1 should return a decimal of 3 (with precision 0, auto_precision)';
    $this->assertEquals($result, '3', $message);

    // Test automatic precision for terminating fractions.
    $result = $this->fraction(1, 8)->toDecimal(0, TRUE);
    $message = 'A fraction of 1/8 should return a decimal of 0.125 (with precision 0, auto_precision)';
    $this->assertEquals($result, '0.125', $message);

    // Test automatic precision for fractions with a denominator greater than
    // 2147483647.
    $result = $this->fraction(1, 2147483648)->toDecimal(0, TRUE);
    $message = 'A fraction of 1/2147483648 should return a decimal of 0.0000000004656612873077392578125 (with precision 0, auto_precision)';
    $this->assertEquals('0.0000000004656612873077392578125', $result, $message);

    // Test automatic precision for non-terminating fractions: 1/6.
    $result = $this->fraction(1, 6)->toDecimal(3, TRUE);
    $message = 'A fraction of 1/6 should return a decimal of 0.167 (with precision 0, auto_precision)';
    $this->assertEquals($result, '0.167', $message);

    // Test automatic precision for non-terminating fractions: 1/3.
    $result = $this->fraction(1, 3)->toDecimal(3, TRUE);
    $message = 'A fraction of 1/3 should return a decimal of 0.333 (with precision 3, auto_precision)';
    $this->assertEquals($result, '0.333', $message);

    // Test creation of a fraction from a decimal.
    $result = Fraction::createFromDecimal(0.5)->toString();
    $message = 'The createFromDecimal() method should create a fraction of 5/10 from a decimal of 0.5.';
    $this->assertEquals($result, '5/10', $message);

    // Test calculation of greatest common divisor.
    $result = $this->fraction(5, 10)->gcd();
    $message = 'The greatest common divisor of 1/2 is 5.';
    $this->assertEquals($result, '5', $message);

    // Test reduction of a fraction to its simplest form.
    $result = $this->fraction(5, 10)->reduce()->toString();
    $message = 'A fraction of 5/10 should be reducible to 1/2.';
    $this->assertEquals($result, '1/2', $message);

    // Test fraction addition.
    $result = $this->fraction(1, 2)->add($this->fraction(1, 3))->toString();
    $message = '1/2 + 1/3 = 5/6';
    $this->assertEquals($result, '5/6', $message);

    // Test fraction subtraction.
    $result = $this->fraction(2, 3)->subtract($this->fraction(1, 7))->toString();
    $message = '2/3 - 1/7 = 11/21';
    $this->assertEquals($result, '11/21', $message);

    // Test fraction multiplication.
    $result = $this->fraction(2, 5)->multiply($this->fraction(1, 4))->toString();
    $message = '2/5 * 1/4 = 1/10';
    $this->assertEquals($result, '1/10', $message);

    // Test fraction division.
    $result = $this->fraction(5, 7)->divide($this->fraction(1, 5))->toString();
    $message = '5/7 / 1/5 = 25/7';
    $this->assertEquals($result, '25/7', $message);

    // Test fraction reciprocation.
    $result = $this->fraction(1, 2)->reciprocate()->toString();
    $message = 'The reciprocal of 1/2 is 2/1.';
    $this->assertEquals($result, '2/1', $message);

    // Test that reciprocation of a zero numerator does not result in division
    // by zero.
    $result = $this->fraction(0, 1)->reciprocate()->toString();
    $message = 'The reciprocal of 0/1 is 0/1 (avoid division by zero).';
    $this->assertEquals($result, '0/1', $message);

    // Test that decimal arithmetic results are reduced.
    $result = Fraction::createFromDecimal('0.1')->add(Fraction::createFromDecimal('0.2'))->toDecimal(0, TRUE);
    $message = '0.1 + 0.2 = 0.3';
    $this->assertEquals('0.3', $result, $message);

    // Test floating point arithmetic.
    $result = Fraction::createFromDecimal('3')->subtract(Fraction::createFromDecimal('2.99'))->toDecimal(0, TRUE);
    $message = '3 - 2.99 = 0.01';
    $this->assertEquals('0.01', $result, $message);
  }

}
