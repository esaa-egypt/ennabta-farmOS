<?php

namespace Drupal\fraction;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for storing the fraction decimal value for validation.
 */
class FractionDecimalProperty extends TypedData {

  /**
   * Cached value.
   *
   * @var string|null
   */
  protected $decimal = NULL;

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {

    // If a value is already available, return it.
    if ($this->decimal !== NULL) {
      return $this->decimal;
    }

    // Load the parent item.
    $item = $this->getParent();

    // If numerator or denominator are null, the decimal is also null.
    if (is_null($item->numerator) || is_null($item->denominator)) {
      $this->decimal = NULL;
    }

    // Otherwise, create a Fraction object and generate decimal value with
    // automatic precision.
    else {
      $fraction = new Fraction($item->numerator, $item->denominator);
      $this->decimal = $fraction->toDecimal(0, TRUE);
    }

    return $this->decimal;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->decimal = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
