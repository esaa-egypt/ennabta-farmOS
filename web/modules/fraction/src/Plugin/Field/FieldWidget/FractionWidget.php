<?php

namespace Drupal\fraction\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fraction' widget.
 *
 * @FieldWidget(
 *   id = "fraction",
 *   label = @Translation("Fraction"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class FractionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $element['fraction'] = [
      '#type' => 'fraction',
      '#title' => $this->t('Fraction'),
      '#default_value' => [
        'numerator' => $items[$delta]->numerator ?? NULL,
        'denominator' => $items[$delta]->denominator ?? NULL,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      // Remove the fraction form element wrapper if it exists to make the
      // FractionItem::setValue method more consistent.
      $value = $value['fraction'] ?? $value;
    }
    return $values;
  }

}
