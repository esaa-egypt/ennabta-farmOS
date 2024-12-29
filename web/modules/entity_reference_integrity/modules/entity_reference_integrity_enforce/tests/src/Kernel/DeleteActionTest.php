<?php

namespace Drupal\Tests\entity_reference_integrity_enforce\Kernel;

use Drupal\entity_reference_integrity_enforce\Plugin\Action\DeleteAction;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\user\Entity\User;

/**
 * Test behavior of the DeleteAction plugin.
 *
 * @see \Drupal\KernelTests\Core\Action\DeleteActionTest
 *
 * @group entity_reference_integrity_enforce
 */
class DeleteActionTest extends KernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'node',
    'entity_test',
    'entity_reference_integrity',
    'entity_reference_integrity_enforce',
  ];

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The entity dependency manager.
   *
   * @var \Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface
   */
  protected $dependencyManager;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * A test node that is referenced.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referencedNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);

    $this->actionManager = $this->container->get('plugin.manager.action');
    $this->dependencyManager = $this->container->get('entity_reference_integrity.dependency_manager');

    // Create a new node-type.
    NodeType::create([
      'type' => $node_type = mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ])->save();

    // Create an entity reference field to test with.
    $this->createEntityReferenceField('node', $node_type, 'test_reference_field', 'Test reference field', 'node');

    // Create testing nodes.
    $this->referencedNode = Node::create([
      'title' => 'Node to delete',
      'type'  => $node_type,
    ]);
    $this->referencedNode->save();
    $this->testNode = Node::create([
      'title' => 'Referenced node',
      'type'  => $node_type,
      'test_reference_field' => [
        'entity' => $this->referencedNode,
      ],
    ]);
    $this->testNode->save();

    // Enable reference integrity for nodes.
    \Drupal::configFactory()
           ->getEditable('entity_reference_integrity_enforce.settings')
           ->set('enabled_entity_type_ids', ['node' => 'node'])
           ->save();

    // A test user is required for testing access.
    $this->testUser = User::create([
      'name' => 'Gerald',
    ]);
    $this->testUser->save();
    \Drupal::service('current_user')->setAccount($this->testUser);
  }

  /**
   * Test that delete action denies access to protected entities.
   */
  public function testDeleteAction() {
    // Ensure the DeleteAction exists and is using the extended class.
    $actions = $this->actionManager->getDefinitions();
    $this->assertArrayHasKey('entity:delete_action:node', $actions);
    $this->assertEquals(DeleteAction::class, $actions['entity:delete_action:node']['class']);
    $action = $this->actionManager->createInstance('entity:delete_action:node');

    // The referencedNode has dependents, the action should deny access.
    $this->assertTrue($this->dependencyManager->hasDependents($this->referencedNode));
    $this->assertFalse($action->access($this->referencedNode, $this->testUser));

    // Unset the node reference.
    $this->testNode->test_reference_field = [];
    $this->testNode->save();

    // The referencedNode has no dependents, the action should allow access.
    $this->assertFalse($this->dependencyManager->hasDependents($this->referencedNode));
    $this->assertTrue($action->access($this->referencedNode, $this->testUser));
  }
}
