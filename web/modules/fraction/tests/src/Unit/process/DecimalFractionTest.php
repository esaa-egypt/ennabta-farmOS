<?php

namespace Drupal\Tests\fraction\Unit\process;

use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\fraction\Plugin\migrate\process\DecimalFraction;

/**
 * Tests the decimal fraction process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\fraction\Plugin\migrate\process\DecimalFraction
 */
class DecimalFractionTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new DecimalFraction([], 'decimal_fraction', []);
    parent::setUp();
  }

  /**
   * Test decimal fraction plugin.
   *
   * @dataProvider decimalFractionDataProvider
   */
  public function testDecimalFraction($input, $expected_output) {
    $output = $this->plugin->transform($input, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($output['numerator'], $expected_output['numerator']);
    $this->assertSame($output['denominator'], $expected_output['denominator']);
  }

  /**
   * Data provider for testDecimalFraction().
   *
   * @return array
   *   Array of input values and expected output values.
   */
  public function decimalFractionDataProvider() {
    return [
      'basic decimal' => [
        'input' => 0.5,
        'expected_output' => [
          'numerator' => '5',
          'denominator' => '10',
        ],
      ],
      'negative decimal' => [
        'input' => -1,
        'expected_output' => [
          'numerator' => '-1',
          'denominator' => '1',
        ],
      ],
      'zero value' => [
        'input' => 0,
        'expected_output' => [
          'numerator' => '0',
          'denominator' => '1',
        ],
      ],
      'empty string' => [
        'input' => '',
        'expected_output' => [
          'numerator' => '',
          'denominator' => '',
        ],
      ],
      'string' => [
        'input' => 'test',
        'expected_output' => [
          'numerator' => '',
          'denominator' => '',
        ],
      ],
    ];
  }

}
