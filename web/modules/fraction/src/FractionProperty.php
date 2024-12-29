<?php

namespace Drupal\fraction;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for storing the fraction.
 */
class FractionProperty extends TypedData {

  /**
   * Cached Fraction object.
   *
   * @var Fraction|null
   */
  protected $fraction = NULL;

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {
    // If a Fraction object is already available, return it.
    if ($this->fraction !== NULL) {
      return $this->fraction;
    }

    // Load the parent item.
    $item = $this->getParent();

    // If numerator or denominator are null, the fraction is also null.
    if (is_null($item->numerator) || is_null($item->denominator)) {
      $this->fraction = NULL;
    }

    // Otherwise, create a Fraction object with the numerator and denominator.
    else {
      $this->fraction = new Fraction($item->numerator, $item->denominator);
    }

    return $this->fraction;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->fraction = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
