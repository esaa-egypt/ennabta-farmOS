<?php

namespace Drupal\Tests\fraction\Kernel\Plugin\Constraints;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\fraction\Fraction;
use Drupal\fraction\Plugin\Validation\Constraint\FractionConstraint;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests Constraint for Fraction.
 *
 * @group Fraction
 */
class FractionConstraintTest extends EntityKernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = ['node', 'fraction'];

  /**
   * EntityInterface.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Field name.
   *
   * @var string
   */
  const FIELD_NAME = 'field_fraction_test';

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create([
      'type' => 'article',
      'label' => 'Article',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => self::FIELD_NAME,
      'type' => 'fraction',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => self::FIELD_NAME,
      'bundle' => 'article',
    ])->save();

    $this->entity = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'article',
      'title' => 'test',
      self::FIELD_NAME => [
        'numerator' => 1,
        'denominator' => 1,
      ],
    ]);
  }

  /**
   * Tests constraints.
   */
  public function testConstraints() {
    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);
    $this->assertViolations($violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '0', 'numerator' => '1'];

    $violations = $this->entity->validate();
    $this->assertCount(1, $violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '2147483649', 'numerator' => '1'];

    $violations = $this->entity->validate();
    $this->assertCount(1, $violations);
    $this->assertViolations($violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '123456', 'numerator' => '1'];

    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '1', 'numerator' => '9223372036854775808'];

    $violations = $this->entity->validate();
    $this->assertCount(2, $violations);
    $this->assertViolations($violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '1', 'numerator' => '-9223372036854775809'];

    $violations = $this->entity->validate();
    $this->assertCount(2, $violations);
    $this->assertViolations($violations);

    $this->entity->{self::FIELD_NAME} = ['denominator' => '1', 'numerator' => '92233720368547758'];

    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);

    $this->entity->{self::FIELD_NAME} = ['decimal' => '1.255'];
    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);

    $decimal = '9223372.993685481231231';
    $this->entity->{self::FIELD_NAME} = $this->generateFraction($decimal);

    $violations = $this->entity->validate();
    $this->assertCount(2, $violations);
    $this->assertViolations($violations);

    $decimal = '11.1234';
    $this->entity->{self::FIELD_NAME} = $this->generateFraction($decimal);

    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);

    $decimal = '8.1251251251';
    $this->entity->{self::FIELD_NAME} = $this->generateFraction($decimal);

    $violations = $this->entity->validate();
    $this->assertCount(1, $violations);
    $this->assertViolations($violations);

    $decimal = '8.12512512';
    $this->entity->{self::FIELD_NAME} = $this->generateFraction($decimal);

    $violations = $this->entity->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Helper method that generates fraction from decimal to set it on field.
   *
   * @param string $decimal
   *   Decimal to convert to fraction.
   *
   * @return array
   *   Array to be set on field.
   */
  protected function generateFraction(string $decimal) {
    $fraction = Fraction::createFromDecimal($decimal);
    $numerator = $fraction->getNumerator();
    $denominator = $fraction->getDenominator();

    // Set the numerator and denominator values for the field and store
    // them in array which will be saved on the field.
    return [
      'decimal' => $decimal,
      'numerator' => $numerator,
      'denominator' => $denominator,
    ];
  }

  /**
   * Helper method to validate.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationList $violations
   *   Violation list.
   */
  protected function assertViolations(EntityConstraintViolationList $violations) {
    foreach ($violations as $violation) {
      $constraint = $violation->getConstraint();
      if (!($constraint instanceof FractionConstraint || $constraint instanceof PrimitiveTypeConstraint)) {
        $this->fail('Wrong type of constraint');
      }
    }
  }

}
