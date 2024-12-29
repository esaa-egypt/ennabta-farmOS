<?php

namespace Drupal\fraction\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Number;
use Drupal\fraction\Fraction;

/**
 * Provides a fraction decimal form element.
 *
 * Usage example:
 * @code
 * $form['fraction'] = [
 *   '#type' => 'fraction_decimal',
 *   '#title' => $this->t('Fraction'),
 *   '#default_value' => 10.385,
 *   '#size' => 60,
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("fraction_decimal")
 */
class FractionDecimal extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#step' => 'any',
      '#size' => 15,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#element_validate' => [
        [$class, 'validateDecimal'],
      ],
      '#process' => [
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNumber'],
        [$class, 'preRenderGroup'],
      ],
      '#theme' => 'input__textfield',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Validates the fraction_decimal element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDecimal(array $element, FormStateInterface $form_state, array &$complete_form) {
    $value = trim($element['#value']);

    // Only continue with validation if the value is not empty.
    if ($element['#value'] === '') {
      return;
    }

    // Do basic number validations.
    Number::validateNumber($element, $form_state, $complete_form);

    // Only continue with validation if value is a valid number.
    if (count($form_state->getErrors()) !== 0) {
      return;
    }

    // Convert the value to a fraction.
    $fraction = Fraction::createFromDecimal($value);

    // Get the numerator and denominator.
    $numerator = $fraction->getNumerator();
    $denominator = $fraction->getDenominator();

    // Set the numerator and denominator values for the form.
    $values = [
      'decimal' => $value,
      'numerator' => $numerator,
      'denominator' => $denominator,
    ];
    $form_state->setValueForElement($element, $values);

    // The maximum number of digits after the decimal place is 9.
    // Explicitly perform a string comparison to ensure precision.
    if ((string) $denominator > '1000000000') {
      $form_state->setError($element, t('The maximum number of digits after the decimal place is 9.'));
    }

    // Ensure that the decimal value is within an acceptable value range.
    // Convert the fraction back to a decimal, because that is what will be
    // stored. Explicitly perform a string comparison to ensure precision.
    $decimal = (string) $fraction->toDecimal(0, TRUE);
    $min_decimal_fraction = new Fraction('-9223372036854775808', $denominator);
    $min_decimal = (string) $min_decimal_fraction->toDecimal(0, TRUE);
    $max_decimal_fraction = new Fraction('9223372036854775807', $denominator);
    $max_decimal = (string) $max_decimal_fraction->toDecimal(0, TRUE);
    $scale = strlen($denominator) - 1;
    $in_bounds = static::checkInBounds($decimal, $min_decimal, $max_decimal, $scale);
    if (!$in_bounds) {
      $form_state->setError($element, t('The number you entered is outside the range of acceptable values. This limitation is related to the decimal precision, so reducing the precision may solve the problem.'));
    }
  }

  /**
   * Prepares a #type 'fraction_decimal' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderNumber(array $element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element,
      ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']
    );
    static::setAttributes($element, ['form-text']);

    return $element;
  }

  /**
   * Helper method to check if a given value is in between two other values.
   *
   * Uses BCMath and strings for arbitrary-precision operations where possible.
   *
   * @param string $value
   *   The value to check.
   * @param string $min
   *   The minimum bound.
   * @param string $max
   *   The maximum bound.
   * @param int $scale
   *   Optional scale integer to pass into bcsub() if BCMath is used.
   *
   * @return bool
   *   Returns TRUE if $number is between $min and $max, FALSE otherwise.
   */
  public static function checkInBounds($value, $min, $max, $scale = 0) {

    // If BCMath isn't available, let PHP handle it via normal float comparison.
    if (!function_exists('bcsub')) {
      return ($value > $max || $value < $min) ? FALSE : TRUE;
    }

    // Subtract the minimum bound and maximum bounds from the value.
    $diff_min = bcsub($value, $min, $scale);
    $diff_max = bcsub($value, $max, $scale);

    // If either have a difference of zero, then the value is in bounds.
    if ($diff_min == 0 || $diff_max == 0) {
      return TRUE;
    }

    // If the first character of $diff_min is a negative sign (-), then the
    // value is less than the minimum, and therefore out of bounds.
    if (substr($diff_min, 0, 1) == '-') {
      return FALSE;
    }

    // If the first character of $diff_max is a number, then the value is
    // greater than the maximum, and therefore out of bounds.
    if (is_numeric(substr($diff_max, 0, 1))) {
      return FALSE;
    }

    // Assume the value is in bounds if none of the above said otherwise.
    return TRUE;
  }

}
