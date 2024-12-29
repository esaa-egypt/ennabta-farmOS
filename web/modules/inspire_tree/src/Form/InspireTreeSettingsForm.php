<?php

namespace Drupal\inspire_tree\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Inspire tree settings.
 */
class InspireTreeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['inspire_tree.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inspire_tree_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('inspire_tree.settings');

    $form['mode'] = [
      '#title' => $this->t('Mode'),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'light' => $this->t('Light mode'),
        'dark' => $this->t('Dark mode'),
      ],
      '#description' => $this->t('Select dark or light mode, or none to avoid including any css.'),
      '#default_value' => $config->get('mode') ?? 'none',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($mode = $form_state->getValue('mode')) {
      $this->config('inspire_tree.settings')
        ->set('mode', $mode)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
