<?php

namespace Drupal\Tests\log\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Log CRUD.
 */
abstract class LogTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The log storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected $storage;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'entity',
    'user',
    'log',
    'log_test',
    'field',
    'text',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->storage = $entity_type_manager->getStorage('log');
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
    drupal_flush_all_caches();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'access administration pages',
      'administer log',
      'view any log',
      'create default log',
      'view any default log',
      'update own default log',
      'update any default log',
      'delete own default log',
      'delete any default log',
    ];
  }

  /**
   * Creates a log entity.
   *
   * @param array $values
   *   Array of values to feed the entity.
   *
   * @return \Drupal\log\Entity\LogInterface
   *   The log entity.
   */
  protected function createLogEntity(array $values = []) {
    $entity = $this->storage->create($values + [
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'type' => 'default',
    ]);
    return $entity;
  }

}
