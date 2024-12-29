<?php

namespace Drupal\migrate_source_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Drupal\migrate_source_ui\StubMigrationMessage;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class MigrateSourceUiForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Config object for migrate_source_ui.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var FileSystemInterface
   */
  protected $fileSystem;

  /**
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The File System service.
   */
  public function __construct(MigrationPluginManager $plugin_manager_migration, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system) {
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
    $this->config = $config_factory->get('migrate_source_ui.settings');
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_source_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->definitions as $definition) {
      $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
      if ($migrationInstance->getSourcePlugin() instanceof CSV || $migrationInstance->getSourcePlugin() instanceof Json || $migrationInstance->getSourcePlugin() instanceof Xml) {
        $id = $definition['id'];
        $options[$id] = $this->t('%id (supports %file_type)', [
          '%id' => $definition['label'] ?? $id,
          '%file_type' => $this->getFileExtensionSupported($migrationInstance),
        ]);
      }
    }
    natcasesort($options);
    $form['migrations'] = [
      '#type' => 'select',
      '#title' => $this->t('Migrations'),
      '#options' => $options,
    ];
    $form['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the source file'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'json csv xml',
        ],
      ],
    ];
    $form['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 1,
    ];
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $migration_id = $form_state->getValue('migrations');
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
    $extension = $this->getFileExtensionSupported($migrationInstance);

    $validators = ['FileExtension' => ['extensions' => $extension]];
    // Check to see if a specific file temp directory is configured. If not,
    // default the value to FALSE, which will instruct file_save_upload() to
    // use Drupal's temporary files scheme.
    $file_destination = $this->config->get('file_temp_directory');
    if (is_null($file_destination)) {
      $file_destination = FALSE;
    }

    $directory = $this->fileSystem->realpath($file_destination);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file = file_save_upload('source_file', $validators, $file_destination, 0, FileSystemInterface::EXISTS_REPLACE);

    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      // File upload failed.
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = $form_state->getValue('migrations');
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $migration_id]));
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $this->getBatchOptions($form, $form_state));
    $executable->batchImport();
  }

  /**
   * Prepares an array of migrate batch options.
   */
  protected function getBatchOptions($form, FormStateInterface $form_state) {
    $options = [
      'configuration' => [
        'source' => [
          'path' => $form_state->getValue('file_path'),
        ],
      ],
    ];

    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $options['update'] = 1;
    }

    return $options;
  }

  /**
   * The allowed file extension for the migration.
   *
   * @param \Drupal\migrate\Plugin\Migration $migrationInstance
   *   The migration instance.
   *
   * @return string
   *   The file extension.
   */
  public function getFileExtensionSupported(Migration $migrationInstance) {
    $extension = 'csv';
    if ($migrationInstance->getSourcePlugin() instanceof CSV) {
      $extension = 'csv';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Json) {
      $extension = 'json';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Xml) {
      $extension = 'xml';
    }

    return $extension;
  }

}
