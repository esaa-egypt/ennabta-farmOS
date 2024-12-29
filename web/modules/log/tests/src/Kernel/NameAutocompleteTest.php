<?php

namespace Drupal\Tests\log\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\log\Traits\LogCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests name autocomplete for logs.
 *
 * @group Log
 */
class NameAutocompleteTest extends EntityKernelTestBase {

  use LogCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'log',
    'log_test',
    'datetime',
    'entity',
    'state_machine',
  ];

  /**
   * An admin account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminAccount;

  /**
   * An account with 'view any default log' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anyAccount;

  /**
   * An account with 'view own default log' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $ownAccount;

  /**
   * An account with no view permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $noneAccount;

  /**
   * A collection of logs.
   *
   * @var \Drupal\log\Entity\LogInterface[]
   */
  protected $logs = [];

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('log');
    $this->installConfig(['log', 'log_test']);

    // Create the test user accounts.
    $this->adminAccount = $this->createUser([], ['administer log']);
    $this->anyAccount = $this->createUser([], [
      'view any default log',
      'create default log',
    ]);
    $this->ownAccount = $this->createUser([], [
      'view own default log',
      'create default log',
    ]);
    $this->noneAccount = $this->createUser([], ['create default log']);

    // Create the different log entries.
    $this->logs[] = $this->createLogEntity([
      'name' => 'First log',
      'uid' => $this->adminAccount->id(),
    ]);
    $this->logs[] = $this->createLogEntity([
      'name' => 'Second log',
      'uid' => $this->adminAccount->id(),
    ]);
    $this->logs[] = $this->createLogEntity([
      'name' => 'Third log',
      'uid' => $this->ownAccount->id(),
    ]);
  }

  /**
   * Returns the result of an autocomplete request.
   *
   * @param string $input
   *   The label of the entity to query by.
   *
   * @return mixed
   *   The JSON value encoded in its appropriate PHP type.
   *
   * @throws \Exception
   */
  protected function getAutocompleteResult($input) {
    // Rebuild the route cache on each request to avoid parameter bag cache
    // leaks.
    $this->container->get('router.builder')->rebuild();

    // Build the autocomplete request, 'q' is the right parameter to mock this.
    $request = Request::create('/log/default/autocomplete');
    $request->query->set('q', $input);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');
    $response = $http_kernel->handle($request);

    // The response is a JsonResponse and the content is a string that needs to
    // be decoded to array.
    $result = $response->getContent();
    return Json::decode($result);
  }

  /**
   * Tests name autocomplete route.
   */
  public function testLogNameAutocomplete() {
    // Tests admin account with an autocomplete query that shouldn't return any
    // logs.
    $this->container->get('current_user')->setAccount($this->adminAccount);
    $result = $this->getAutocompleteResult('nonsense');
    $this->assertEmpty($result, 'No results for non matching search query.');

    // Tests admin account so it returns the complete set of logs.
    $result = $this->getAutocompleteResult('log');
    $this->assertEquals(count($this->logs), count($result), 'Number of results for matching query and admin user is as expected.');

    // With an account that has 'view any default log' permission, the result
    // should be the complete set of logs.
    $this->container->get('current_user')->setAccount($this->anyAccount);
    $result = $this->getAutocompleteResult('log');
    $this->assertEquals(3, count($result), 'Number of results for matching query and user with view any permission is as expected.');

    // With an account that has 'view own default log' permission, the result
    // should be the logs belonging to that user.
    $this->container->get('current_user')->setAccount($this->ownAccount);
    $result = $this->getAutocompleteResult('log');
    $this->assertEquals(1, count($result), 'Number of results for matching query and user with view own permission is as expected.');
    $own_log = array_filter($this->logs, function ($log) {
      /** @var \Drupal\log\Entity\LogInterface $log */
      return $log->id() == $this->ownAccount->id();
    });
    $own_log = reset($own_log);
    $this->assertEquals($result[0], $own_log->label(), 'The right log for the user is returned.');

    // With an account with no permissions and the right query, there should be
    // no results anyway.
    $this->container->get('current_user')->setAccount($this->noneAccount);
    $result = $this->getAutocompleteResult('log');
    $this->assertEmpty($result, 'No results for user without permissions.');
  }

  /**
   * Tests the order of logs returned.
   */
  public function testLogNameAutocompleteMultipleLogs() {
    // Add a duplicate log that should be on top of the results.
    $this->logs[] = $this->createLogEntity([
      'name' => 'Z log',
      'uid' => $this->adminAccount->id(),
    ]);
    $this->logs[] = $this->createLogEntity([
      'name' => 'Z log',
      'uid' => $this->adminAccount->id(),
    ]);

    $this->container->get('current_user')->setAccount($this->adminAccount);
    $result = $this->getAutocompleteResult('log');
    $this->assertEquals(count($this->logs) - 1, count($result), 'Duplicated log is not duplicated in the autocomplete results.');
    $expected_order = [
      'Z log',
      'First log',
      'Second log',
      'Third log',
    ];
    $this->assertEquals($expected_order, $result, 'Order of results is as expected.');
  }

}
