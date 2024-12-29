<?php

namespace Drupal\Tests\log\Kernel;

use Drupal\Tests\log\Traits\LogCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests for Drupal\log\Plugin\views\sort\LogTimestampIdSort handler.
 *
 * @group Log
 */
class SortTimestampIdTest extends ViewsKernelTestBase {

  use LogCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['log', 'log_test', 'datetime', 'state_machine'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['log_test_view'];

  /**
   * ASC expected result.
   *
   * @var array
   */
  protected $expectedResultASC = [];

  /**
   * DESC expected result.
   *
   * @var array
   */
  protected $expectedResultDESC = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('log');
    $this->installConfig(['log', 'log_test']);

    ViewTestData::createTestViews(get_class($this), ['log_test']);

    // Establish two different timestamps so the sort is meaningful.
    $first_timestamp = 376185600;
    $second_timestamp = 386121600;

    // Three entities is the minimum amount to test two with the same timestamp
    // and different ID and one with unique timestamp.
    $first_entity = $this->createLogEntity([
      'name' => 'First',
      'timestamp' => $first_timestamp,
    ]);
    $second_entity = $this->createLogEntity([
      'name' => 'Second',
      'timestamp' => $first_timestamp,
    ]);
    $third_entity = $this->createLogEntity([
      'name' => 'Third',
      'timestamp' => $second_timestamp,
    ]);

    // Fill the expected results for the combinations.
    $this->expectedResultASC = [
      ['name' => $first_entity->get('name')->value, 'id' => $first_entity->id()],
      ['name' => $second_entity->get('name')->value, 'id' => $second_entity->id()],
      ['name' => $third_entity->get('name')->value, 'id' => $third_entity->id()],
    ];
    $this->expectedResultDESC = [
      ['name' => $third_entity->get('name')->value, 'id' => $third_entity->id()],
      ['name' => $second_entity->get('name')->value, 'id' => $second_entity->id()],
      ['name' => $first_entity->get('name')->value, 'id' => $first_entity->id()],
    ];
  }

  /**
   * Tests the sorting: Timestamp /ID ASC.
   */
  public function testLogTimestampIdAscSort() {
    $view = Views::getView('log_test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'timestamp' => [
        'id' => 'timestamp',
        'table' => 'log_field_data',
        'field' => 'timestamp',
        'relationship' => 'none',
        'order' => 'ASC',
        'plugin_id' => 'log_standard',
      ],
    ]);

    $this->executeView($view);

    $this->assertEquals(3, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->expectedResultASC, [
      'name' => 'name',
      'id' => 'id',
    ], 'ASC sort displays as expected');
    $view->destroy();
    unset($view);
  }

  /**
   * Tests the sorting: Timestamp/ID DESC.
   */
  public function testLogTimestampIdDescSort() {
    $view = Views::getView('log_test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'timestamp' => [
        'id' => 'timestamp',
        'table' => 'log_field_data',
        'field' => 'timestamp',
        'relationship' => 'none',
        'order' => 'DESC',
        'plugin_id' => 'log_standard',
      ],
    ]);

    $this->executeView($view);

    $this->assertEquals(3, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->expectedResultDESC, [
      'name' => 'name',
      'id' => 'id',
    ], 'DESC sort displays as expected');
    $view->destroy();
    unset($view);
  }

}
