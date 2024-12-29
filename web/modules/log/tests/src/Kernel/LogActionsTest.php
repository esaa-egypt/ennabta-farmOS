<?php

namespace Drupal\Tests\log\Kernel;

use Drupal\Core\Action\ActionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\log\Traits\LogCreationTrait;

/**
 * Tests for log actions.
 *
 * @group Log
 */
class LogActionsTest extends KernelTestBase {

  use LogCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'log',
    'log_test',
    'datetime',
    'state_machine',
  ];

  /**
   * The action manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->actionManager = $this->container->get('plugin.manager.action');
    $this->installEntitySchema('user');
    $this->installEntitySchema('log');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['log', 'log_test']);
  }

  /**
   * Tests that all the custom actions are available for the log entity type.
   */
  public function testAvailableActions() {
    $definitions = $this->actionManager->getDefinitionsByType('log');
    $expected_actions = [
      'log_mark_as_done_action',
      'log_mark_as_pending_action',
      'log_clone_action',
      'log_reschedule_action',
    ];
    foreach ($expected_actions as $expected_action) {
      $this->assertTrue(in_array($expected_action, array_keys($definitions)));
    }
  }

  /**
   * Tests that the mark as done action sets the right state.
   */
  public function testMarkAsDoneAction() {
    $action = $this->actionManager->createInstance('log_mark_as_done_action');
    $this->assertTrue($action instanceof ActionInterface, 'The action implements the correct interface.');
    $new_log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'status' => 'pending',
    ]);
    $new_log->save();
    $action->execute($new_log);
    $storage = $this->container->get('entity_type.manager')->getStorage('log');
    $log = $storage->load($new_log->id());
    $this->assertEquals('done', $log->get('status')->value);
  }

  /**
   * Tests that the mark as pending action sets the right state.
   */
  public function testMarkAsPendingAction() {
    $action = $this->actionManager->createInstance('log_mark_as_pending_action');
    $this->assertTrue($action instanceof ActionInterface, 'The action implements the correct interface.');
    $new_log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'status' => 'done',
    ]);
    $new_log->save();
    $action->execute($new_log);
    $storage = $this->container->get('entity_type.manager')->getStorage('log');
    $log = $storage->load($new_log->id());
    $this->assertEquals('pending', $log->get('status')->value);
  }

}
