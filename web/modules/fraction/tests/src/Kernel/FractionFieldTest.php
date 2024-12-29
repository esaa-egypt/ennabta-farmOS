<?php

namespace Drupal\Tests\fraction\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\fraction\Fraction;
use Drupal\fraction\Plugin\Field\FieldType\FractionItem;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Class to test the FractionItem field.
 *
 * @group Fraction
 */
class FractionFieldTest extends FieldKernelTestBase {

  /**
   * Field name to use.
   */
  const FIELD_NAME = 'field_fraction_test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'fraction'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    NodeType::create([
      'type' => 'article',
      'label' => 'Article',
    ])->save();

    $this->fieldTestData->{$this::FIELD_NAME . '_storage'} = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => self::FIELD_NAME,
      'type' => 'fraction',
    ]);
    $this->fieldTestData->{$this::FIELD_NAME . '_storage'}->save();

    $this->fieldTestData->{$this::FIELD_NAME} = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => self::FIELD_NAME,
      'bundle' => 'article',
    ]);
    $this->fieldTestData->{$this::FIELD_NAME}->save();
  }

  /**
   * Test field properties.
   */
  public function testFieldProperties() {

    // Check for the correct main property name.
    $this->assertNull(FractionItem::mainPropertyName(), 'The fraction item main property name is NULL.');

    // Check for correct property definitions.
    $definitions = FractionItem::propertyDefinitions($this->fieldTestData->{$this::FIELD_NAME . '_storage'});
    $properties = ['numerator', 'denominator', 'fraction', 'decimal'];
    foreach ($properties as $property) {
      $this->assertNotEmpty($definitions[$property], "The fraction item has a $property property.");
    }

    // Ensure the decimal property is not internal to be included in JSON:API.
    $this->assertFalse($definitions['decimal']->isInternal(), 'The fraction item decimal property is not internal.');
  }

  /**
   * Tests setting the fraction field item from a decimal.
   */
  public function testSetValue() {
    // Setup the entity and field.
    $node = Node::create([
      'type' => 'article',
      'title' => 'test',
    ]);
    $node->save();
    $fraction_field = $node->get(self::FIELD_NAME);

    // Confirm that an empty field returns a null values.
    $fraction_field->set(0, NULL);
    $this->assertEquals(NULL, $fraction_field->first()->numerator);
    $this->assertEquals(NULL, $fraction_field->first()->denominator);
    $this->assertEquals(NULL, $fraction_field->first()->get('fraction')->getValue());
    $this->assertEquals(NULL, $fraction_field->first()->get('decimal')->getValue());

    // Set value with a decimal.
    $fraction_field->set(0, 1.2);
    $this->assertEquals(12, $fraction_field->first()->numerator);
    $this->assertEquals(10, $fraction_field->first()->denominator);
    $this->assertEquals('12/10', $fraction_field->first()->get('fraction')->getValue()->toString());
    $this->assertEquals('1.2', $fraction_field->first()->get('decimal')->getValue());

    // Change the value.
    $fraction_field->set(0, 12.3);
    $this->assertEquals(123, $fraction_field->first()->numerator);
    $this->assertEquals(10, $fraction_field->first()->denominator);
    $this->assertEquals('123/10', $fraction_field->first()->get('fraction')->getValue()->toString());
    $this->assertEquals('12.3', $fraction_field->first()->get('decimal')->getValue());

    // Set value with the decimal property.
    $fraction_field->set(0, ['decimal' => 45.6]);
    $this->assertEquals(456, $fraction_field->first()->numerator);
    $this->assertEquals(10, $fraction_field->first()->denominator);
    $this->assertEquals('456/10', $fraction_field->first()->get('fraction')->getValue()->toString());
    $this->assertEquals('45.6', $fraction_field->first()->get('decimal')->getValue());
  }

  /**
   * Tests setting the sample value.
   */
  public function testGenerateSampleValue() {
    $node = Node::create([
      'type' => 'article',
      'title' => 'test',
    ]);
    $node->save();
    $node->get(self::FIELD_NAME)->generateSampleItems();
    $this->entityValidateAndSave($node);
    $result = $node->get(self::FIELD_NAME)->getValue();
    $result = reset($result);
    $this->assertTrue(is_array($result));
    $this->assertArrayHasKey('numerator', $result);
    $this->assertArrayHasKey('denominator', $result);
    $this->assertTrue($result['denominator'] > 0, TRUE);
  }

}
