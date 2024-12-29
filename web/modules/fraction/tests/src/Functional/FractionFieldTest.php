<?php

namespace Drupal\Tests\fraction\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the creation of fraction fields.
 *
 * @group Fraction
 */
class FractionFieldTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'entity_test', 'field_ui', 'fraction'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'bypass node access',
      'administer entity_test fields',
      'access administration pages',
    ]));
  }

  /**
   * Test decimal widget field.
   */
  public function testFractionWidgetDecimal() {
    $max = 100;
    $min = 10;
    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'fraction',
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' =>
        [
          'max' => $max,
          'min' => $min,
        ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][decimal]", '');

    // Submit decimal value.
    $value = '14.5678';
    $edit = [
      "{$field_name}[0][decimal]" => $value,
    ];
    $this->submitForm($edit, $this->t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains($this->t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertSession()->responseContains($value);

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = [
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][decimal]" => $wrong_entry,
      ];
      $this->submitForm($edit, $this->t('Save'));
      $this->assertSession()->responseContains($this->t('%name must be a number.', ['%name' => $field_name]));
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = [
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][decimal]" => $wrong_entry,
      ];
      $this->submitForm($edit, $this->t('Save'));
      $this->assertSession()->responseContains($this->t('%name must be a number.', ['%name' => $field_name]));
    }

    // Try to set a value above the maximum value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][decimal]" => $max + 0.123,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('%name: the value may be no greater than %maximum.', ['%name' => $field_name, '%maximum' => $max]));

    // Try to set a value below the minimum value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][decimal]" => $min - 0.123,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('%name: the value may be no less than %minimum.', ['%name' => $field_name, '%minimum' => $min]));

    // Test the fraction decimal element limits.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][decimal]" => 10.1234567891,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The maximum number of digits after the decimal place is 9.'));
  }

  /**
   * Test decimal widget field with negative values.
   */
  public function testFractionWidgetDecimalNegative() {
    // Create the test field.
    $field_name = mb_strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'fraction',
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][decimal]", '');

    // Submit negative decimal value.
    $value = '-14.5678';
    $edit = [
      "{$field_name}[0][decimal]" => $value,
    ];
    $this->submitForm($edit, $this->t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains($this->t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertSession()->responseContains($value);
  }

  /**
   * Test decimal widget field.
   */
  public function testFractionWidgetFraction() {
    $max = 100;
    $min = 10;
    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'fraction',
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' =>
        [
          'max' => $max,
          'min' => $min,
        ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction'])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction'])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][fraction][numerator]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][fraction][denominator]", '');

    // Submit fraction value.
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 150,
      "{$field_name}[0][fraction][denominator]" => 10,
    ];
    $this->submitForm($edit, $this->t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains($this->t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertSession()->responseContains('150');
    $this->assertSession()->responseContains('10');

    // Try to set a value above the maximum value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 15000,
      "{$field_name}[0][fraction][denominator]" => 10,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('%name: the value may be no greater than %maximum.', ['%name' => $field_name, '%maximum' => $max]));

    // Try to set a value below the minimum value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 1,
      "{$field_name}[0][fraction][denominator]" => 10,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('%name: the value may be no less than %minimum.', ['%name' => $field_name, '%minimum' => $min]));

    // Empty denominator.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 1,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The denominator of a fraction cannot be zero or empty (if a numerator is provided).'));

    // Numerators must be between -9223372036854775808 and 9223372036854775807.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => '-9223372036854775809',
      "{$field_name}[0][fraction][denominator]" => 10,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The numerator of a fraction must be between -9223372036854775808 and 9223372036854775807.'));
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => '9223372036854775808',
      "{$field_name}[0][fraction][denominator]" => 10,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The numerator of a fraction must be between -9223372036854775808 and 9223372036854775807.'));

    // Denominators must be between 0 and 2147483647.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 10,
      "{$field_name}[0][fraction][denominator]" => -1,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The denominator of a fraction must be greater than 0 and less than 2147483647.'));
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][fraction][numerator]" => 10,
      "{$field_name}[0][fraction][denominator]" => 2147483648,
    ];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseContains($this->t('The denominator of a fraction must be greater than 0 and less than 2147483647.'));
  }

  /**
   * Test min/max with decimal values.
   */
  public function testFractionWidgetMinMaxDecimal() {
    $this->drupalCreateContentType(['type' => 'article']);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/admin/structure/types/manage/article/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'fraction');
    $page->fillField('label', 'Fraction field');
    $page->fillField('field_name', 'fraction_field');
    $page->pressButton('Save and continue');
    $page->pressButton('Save field settings');
    $page->fillField('settings[min]', 10.5);
    $page->fillField('settings[max]', 100.5);
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Fraction field configuration.');
  }

  /**
   * Tests that editing an empty fraction doesn't show a 0.
   *
   * @see https://www.drupal.org/project/fraction/issues/3096236
   */
  public function testFractionWidgetDecimalEditNull() {
    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'fraction',
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'fraction_decimal'])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][decimal]", '');

    // Submit decimal value.
    $value = '1.234';
    $edit = [
      "{$field_name}[0][decimal]" => $value,
    ];
    $this->submitForm($edit, $this->t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains($this->t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertSession()->responseContains($value);

    // Empty the field.
    $edit = [
      "{$field_name}[0][decimal]" => NULL,
    ];
    $this->drupalGet("entity_test/manage/$id/edit");
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->responseNotContains($value);

    // The field should have no value (not 0, just empty).
    $this->drupalGet("entity_test/manage/$id/edit");
    $xpath = $this->assertSession()->buildXPathQuery('//input[@name=:value]', [':value' => "{$field_name}[0][decimal]"]);
    $elements = $this->xpath($xpath);
    $element = reset($elements);
    $this->assertSame($element->getValue(), '', 'Field is empty');
  }

}
