<?php

namespace Drupal\Tests\entity_reference_integrity_enforce\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_reference_integrity\EntityReferenceIntegrityEntityHandler;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use GuzzleHttp\RequestOptions;

/**
 * Tests referential integrity via JSONAPI.
 *
 * @group entity_reference_integrity_enforce
 */
class JsonApiTest extends BrowserTestBase {

  use EntityReferenceTestTrait;
  use JsonApiRequestTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
    'jsonapi',
    'node',
    'entity_reference_integrity_enforce',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity reference integrity entity handler.
   *
   * @var \Drupal\entity_reference_integrity\EntityReferenceIntegrityEntityHandler
   */
  protected $referenceIntegrityHandler;

  /**
   * Test node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

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

    $entity_type_manager = $this->container->get('entity_type.manager');
    $node_storage = $entity_type_manager->getStorage('node');

    /** @var \Drupal\entity_reference_integrity\EntityReferenceIntegrityEntityHandler $entity_reference_integrity_handler */
    $this->referenceIntegrityHandler = $entity_type_manager->getHandler('node', 'entity_reference_integrity');

    // Create a new node-type.
    $this->nodeType = NodeType::create([
      'type' => $node_type = mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->nodeType->save();

    // Create an entity reference field to test with.
    $this->createEntityReferenceField('node', $node_type, 'test_reference_field', 'Test reference field', 'node');

    // Create testing nodes.
    $this->referencedNode = $node_storage->create([
      'title' => 'Node to delete',
      'type'  => $node_type,
    ]);
    $this->referencedNode->save();
    $this->testNode = $node_storage->create([
      'title' => 'Referenced node',
      'type'  => $node_type,
      'test_reference_field' => [
        'entity' => $this->referencedNode,
      ],
    ]);
    $this->testNode->save();

    // Create a user with permission to delete the test nodes.
    $this->testUser = $this->createUser(['delete any ' . $this->nodeType->id() . ' content']);

    // Allow entities to be updated via JSONAPI.
    \Drupal::configFactory()
      ->getEditable('jsonapi.settings')
      ->set('read_only', FALSE)
      ->save();

    // Enable reference integrity for nodes.
    \Drupal::configFactory()
      ->getEditable('entity_reference_integrity_enforce.settings')
      ->set('enabled_entity_type_ids', ['node' => 'node'])
      ->save();

    // Rebuild routes to include the new node type.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Test deleting the referenced node via the API.
   */
  public function testApiResourceDelete() {

    // Setup the request.
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Authorization'] = 'Basic ' . base64_encode($this->testUser->name->value . ':' . $this->testUser->passRaw);

    // Build a url for the individual JSONAPI resource.
    $resource_type_name = $this->referencedNode->getEntityTypeId() . '--' . $this->referencedNode->bundle();
    $node_url = Url::fromRoute(sprintf('jsonapi.%s.individual', $resource_type_name), ['entity' => $this->referencedNode->uuid()])->setAbsolute();

    // Assert access denied when the node has dependents.
    $this->assertTrue($this->referenceIntegrityHandler->hasDependents($this->referencedNode));
    $response = $this->request('DELETE', $node_url, $request_options);
    $this->assertEquals(403, $response->getStatusCode());

    // Assert valid response body. Should be a JSONAPI response with errors.
    $response_data = Json::decode((string) $response->getBody());
    $this->assertArrayHasKey('errors', $response_data);
    $this->assertEquals(1, sizeof($response_data['errors']));
    $this->assertArrayHasKey('status', $response_data['errors'][0]);
    $this->assertEquals('403', $response_data['errors'][0]['status']);

    // Assert that the correct reason is provided. This ensures the 403 is
    // given for the right reason.
    $reason = EntityReferenceIntegrityEntityHandler::getAccessDeniedReason($this->referencedNode, FALSE);
    $this->assertArrayHasKey('detail', $response_data['errors'][0]);
    $this->assertEquals($reason, $response_data['errors'][0]['detail']);

    // Unset the node reference.
    $this->testNode->test_reference_field = [];
    $this->testNode->save();

    // Assert access allowed when the node has no dependents.
    $this->assertFalse($this->referenceIntegrityHandler->hasDependents($this->referencedNode));
    $response = $this->request('DELETE', $node_url, $request_options);
    $this->assertEquals(204, $response->getStatusCode());

    // Test that the unreferenced node can be deleted.
    $node_url = Url::fromRoute(sprintf('jsonapi.%s.individual', $resource_type_name), ['entity' => $this->testNode->uuid()])->setAbsolute();
    $this->assertFalse($this->referenceIntegrityHandler->hasDependents($this->referencedNode));
    $response = $this->request('DELETE', $node_url, $request_options);
    $this->assertEquals(204, $response->getStatusCode());
  }
}
