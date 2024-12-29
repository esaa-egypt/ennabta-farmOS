<?php

namespace Drupal\Tests\fraction\Unit\Feeds\Target;

use Drupal\Core\Form\FormState;
use Drupal\fraction\Feeds\Target\FractionTarget;
use Drupal\Tests\feeds\Unit\Feeds\Target\FieldTargetTestBase;

/**
 * @coversDefaultClass \Drupal\fraction\Feeds\Target\FractionTarget
 * @group feeds
 */
class FractionTargetTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'fraction';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return FractionTarget::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValueFraction() {
    $method = $this->getMethod('Drupal\fraction\Feeds\Target\FractionTarget', 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->createMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
      'type' => 'fraction',
    ];
    $target = new FractionTarget($configuration, 'fraction', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    // Test a basic fraction: 1/2.
    $values = ['value' => '1/2'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '1');
    $this->assertSame($values['denominator'], '2');

    // Test that a zero numerator results in an empty fraction.
    $values = ['value' => '0/1'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');

    // Test that a zero denominator results in an empty fraction.
    $values = ['value' => '1/0'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');

    // Test that a negative numerator works.
    $values = ['value' => '-1/2'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '-1');
    $this->assertSame($values['denominator'], '2');

    // Test that a negative denominator results in an empty fraction.
    $values = ['value' => '1/-2'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');

    // Test that a decimal value results in an empty fraction.
    $values = ['value' => '1.0'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');

    // Test that a non-numeric value results in an empty fraction.
    $values = ['value' => 'te/st'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValueDecimal() {
    $method = $this->getMethod('Drupal\fraction\Feeds\Target\FractionTarget', 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->createMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
      'type' => 'decimal',
    ];
    $target = new FractionTarget($configuration, 'fraction', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    // Test a basic decimal.
    $values = ['value' => 0.5];
    $method(0, $values);
    $this->assertSame($values['numerator'], '5');
    $this->assertSame($values['denominator'], '10');

    // Test a negative decimal.
    $values = ['value' => -1];
    $method(0, $values);
    $this->assertSame($values['numerator'], '-1');
    $this->assertSame($values['denominator'], '1');

    // Test that a zero value results in 0/1.
    $values = ['value' => 0];
    $method(0, $values);
    $this->assertSame($values['numerator'], '0');
    $this->assertSame($values['denominator'], '1');

    // Test that an empty value results in an empty fraction.
    $values = ['value' => ''];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');

    // Test that a string value results in an empty fraction.
    $values = ['value' => 'test'];
    $method(0, $values);
    $this->assertSame($values['numerator'], '');
    $this->assertSame($values['denominator'], '');
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $method = $this->getMethod('Drupal\fraction\Feeds\Target\FractionTarget', 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->createMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    $target = new FractionTarget($configuration, 'fraction', []);
    $target->setStringTranslation($this->getStringTranslationStub());
    $form_state = new FormState();
    $form = $target->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 1);
    $this->assertArrayHasKey('type', $form);
  }

}
