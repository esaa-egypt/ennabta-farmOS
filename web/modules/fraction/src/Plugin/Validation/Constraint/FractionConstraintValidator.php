<?php

namespace Drupal\fraction\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FractionConstraint constraint.
 */
class FractionConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\fraction\Plugin\Field\FieldType\FractionItem $fraction */
    $fractionItem = $items;
    $values = $fractionItem->getValue();

    if (empty($values['denominator']) && !empty($values['numerator'])) {
      $this->context->addViolation($constraint->denominatorNotZero);
      return;
    }
    if (!empty($values['denominator']) && ((string) $values['denominator'] <= '0' || (string) $values['denominator'] > '2147483647')) {
      $this->context->addViolation($constraint->denominatorOutOfRange);
      return;
    }
    if (!empty($values['numerator']) && ((string) $values['numerator'] < '-9223372036854775808' || (string) $values['numerator'] > '9223372036854775807')) {
      $this->context->addViolation($constraint->numeratorOutOfRange);
      return;
    }
    if ((string) $values['denominator'] > '1000000000') {
      $this->context->addViolation($constraint->maxNumberOfDecimals);
      return;
    }
  }

}
