<?php

namespace Drupal\commerce_price_rule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for price rule lists.
 *
 * We currently provide a View for managing lists. It is therefore questionable
 * whether we really need a list builder. It is added, however, so that they
 * default operations are automatically added and because it is not clear yet
 * whether the View is enough or whether we will need some more advanced display
 * such as adding a column with the price rules that each list is associated
 * with.
 *
 * @I Inject string translation service
 *    type     : task
 *    priority : low
 *    labels   : coding-standards, 2.0@alpha
 *    note     : We don't do it now to not break any class potentially extending
 *               this one. Actually, this injection should happen in
 *               EntityListBuilder in core.
 */
class PriceListListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entities being listed.
   *
   * @var \Drupal\commerce_price_rule\Entity\PriceListInterface[]
   */
  protected $entities = [];

  /**
   * Constructs a new PriceListListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($entity_type, $storage);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_price_rule_lists';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $entities = $this->storage->loadMultiple($entity_ids);

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_price_rule\Entity\PriceListInterface $entity */
    $row['name'] = $entity->label();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->formBuilder->getForm($this);
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entities = $this->load();
    $delta = 10;
    // Dynamically expand the allowed delta based on the number of entities.
    $count = count($this->entities);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    $form['price_rule_lists'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t(
        'There are no @label yet.',
        ['@label' => $this->entityType->getPluralLabel()]
      ),
    ];
    foreach ($this->entities as $entity) {
      $row = $this->buildRow($entity);
      $row['name'] = ['#markup' => $row['name']];
      if (empty($row['description'])) {
        $row['description'] = [];
      }
      $row['description'] = ['#markup' => $row['description']];
      $form['price_rule_lists'][$entity->id()] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do upon submission.
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['manage_items'] = [
      'title' => $this->t('Manage Items'),
      'url' => Url::fromRoute(
        'view.commerce_price_rule_manage_price_list_items.page',
        ['arg_0' => $entity->id()]
      ),
      'weight' => 0,
    ];

    return $operations;
  }

}
