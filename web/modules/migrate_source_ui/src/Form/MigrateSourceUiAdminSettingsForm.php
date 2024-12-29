<?php

namespace Drupal\migrate_source_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form class.
 */
class MigrateSourceUiAdminSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'migrate_source_ui.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_source_ui_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $form['file_temp_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Temporary file directory'),
      '#description' => $this->t("Directory where the uploaded source
        file will live temporarily. Should follow the full Drupal stream syntax,
        e.g.: <code>private://tmp</code>. The module will use Drupal's default
        <code>temporary://</code> stream if this is not set."),
      '#default_value' => $config->get('file_temp_directory'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
