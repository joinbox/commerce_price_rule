<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\Condition;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product type condition.
 *
 * @CommerceCondition(
 *   id = "price_rule_product_type",
 *   label = @Translation("Product Type"),
 *   display_label = @Translation("Limit by product type"),
 *   category = @Translation("Product"),
 *   entity_type = "commerce_product",
 * )
 */
class ProductType extends ConditionBase implements
  ContainerFactoryPluginInterface {

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Constructs a new ProductType object.
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

    $this->productTypeStorage = $entity_type_manager
      ->getStorage('commerce_product_type');
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
      'product_types' => [],
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

    $product_types = EntityHelper::extractLabels(
      $this->productTypeStorage->loadMultiple()
    );
    $form['product_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Product types'),
      '#options' => $product_types,
      '#default_value' => $this->configuration['product_types'],
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
    $product_types = array_filter($values['product_types']);
    if ($product_types) {
      $this->configuration['product_types'] = $product_types;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    return in_array(
      $entity->bundle(),
      $this->configuration['product_types']
    );
  }

}
