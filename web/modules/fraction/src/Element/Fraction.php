<?php

namespace Drupal\fraction\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a fraction form element.
 *
 * Usage example:
 * @code
 * $form['fraction'] = [
 *   '#type' => 'fraction',
 *   '#title' => $this->t('Fraction'),
 *   '#default_value' => ['numerator' => 10, 'denominator' => 100],
 *   '#size' => 60,
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("fraction")
 */
class Fraction extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 10,
      '#element_validate' => [
        [$class, 'validateFraction'],
      ],
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the fraction form element.
   *
   * @param array $element
   *   The initial fraction form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built fraction form element.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a fraction element must be an array with "numerator" and "denominator" keys.');
    }
    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-fraction';

    $element['numerator'] = [
      '#type' => 'number',
      '#title' => t('Numerator'),
      '#title_display' => $element['#title_display'],
      '#default_value' => $default_value ? $default_value['numerator'] : NULL,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#error_no_message' => TRUE,
    ];
    $element['denominator'] = [
      '#type' => 'number',
      '#title' => t('Denominator'),
      '#title_display' => $element['#title_display'],
      '#default_value' => $default_value ? $default_value['denominator'] : NULL,
      '#description' => !empty($element['#description']) ?? '',
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#error_no_message' => TRUE,
    ];

    // Remove the keys that were transferred to child elements.
    unset($element['#size']);
    unset($element['#description']);

    return $element;
  }

  /**
   * Form element validation handler for #type 'fraction'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateFraction(&$element, FormStateInterface $form_state, &$complete_form) {
    $numerator = $element['numerator']['#value'];
    $denominator = $element['denominator']['#value'];

    // If the denominator is empty, but the numerator isn't, print an error.
    if (empty($denominator) && !empty($numerator)) {
      $form_state->setError($element, t('The denominator of a fraction cannot be zero or empty (if a numerator is provided).'));
    }

    // Numerators must be between -9223372036854775808 and 9223372036854775807.
    // Explicitly perform a string comparison to ensure precision.
    if (!empty($numerator) && ((string) $numerator < '-9223372036854775808' || (string) $numerator > '9223372036854775807')) {
      $form_state->setError($element, t('The numerator of a fraction must be between -9223372036854775808 and 9223372036854775807.'));
    }

    // Denominators must be between 1 and 2147483647.
    // Explicitly perform a string comparison to ensure precision.
    if (!empty($denominator) && ((string) $denominator <= '0' || (string) $denominator > '2147483647')) {
      $form_state->setError($element, t('The denominator of a fraction must be greater than 0 and less than 2147483647.'));
    }
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('numerator', $default_value) || !array_key_exists('denominator', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

}
