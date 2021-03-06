<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product variation condition.
 *
 * @CommerceCondition(
 *   id = "price_rule_product_variation",
 *   label = @Translation("Product Variation"),
 *   display_label = @Translation("Limit by product variation"),
 *   category = @Translation("Product"),
 *   entity_type = "commerce_product_variation",
 * )
 */
class ProductVariation extends ConditionBase implements
  ContainerFactoryPluginInterface {

  /**
   * The product variation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationStorage;

  /**
   * Constructs a new ProductVariation object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->variationStorage = $entity_type_manager
      ->getStorage('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'product_variations' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $product_variations = NULL;
    $product_variation_ids = array_column(
      $this->configuration['product_variations'],
      'product_variation_id'
    );
    if (!empty($product_variation_ids)) {
      $product_variations = $this->variationStorage
        ->loadMultiple($product_variation_ids);
    }
    $form['product_variations'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Products variations'),
      '#default_value' => $product_variations,
      '#target_type' => 'commerce_product_variation',
      '#tags' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['product_variations'] = [];
    foreach ($values['product_variations'] as $value) {
      $this->configuration['product_variations'][] = [
        'product_variation_id' => $value['target_id'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    $product_variation_ids = array_column(
      $this->configuration['product_variations'],
      'product_variation_id'
    );
    return in_array($entity->id(), $product_variation_ids);
  }

}
