<?php

namespace Drupal\commerce_price_rule\Form;

use Drupal\Component\Datetime\TimeInterface;

use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the price list item add/edit form.
 */
class PriceListItemForm extends ContentEntityForm {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   *
   * @I Remove injected database connection that seems to be unused
   *    type     : task
   *    priority : low
   *    labels   : coding-standards, 2.0@alpha
   */
  protected $databaseConnection;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a PriceListItemForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    DatabaseConnection $database_connection,
    RouteMatchInterface $route_match,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL
  ) {
    parent::__construct(
      $entity_repository,
      $entity_type_bundle_info,
      $time
    );

    $this->databaseConnection = $database_connection;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Move the price list field to an Advanced fieldset. Moving items between
    // lists would not be very common and while we want to make it possible we
    // want to make it a bit more difficult to prevent accidentally moving
    // items.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#description' => $this->t('Move this item to another list.'),
    ];
    $form['advanced']['price_list_id'] = $form['price_list_id'];
    unset($form['price_list_id']);

    // If we don't have a price list, which means we're on the add form, get the
    // price list id from the url.
    $price_list = $this->entity->getPriceList();
    if ($price_list) {
      return $form;
    }

    $price_list_id = $this->routeMatch
      ->getParameter('commerce_price_rule_list');
    if (!$price_list_id) {
      return $form;
    }

    $price_list = $this->entityTypeManager
      ->getStorage('commerce_price_rule_list')
      ->load($price_list_id);
    $form['advanced']['price_list_id']['widget'][0]['target_id']['#default_value'] = $price_list;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage(
      $this->t(
        'Saved the %label price list item.',
        ['%label' => $this->entity->label()]
      )
    );
  }

}
