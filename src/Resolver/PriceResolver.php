<?php

namespace Drupal\commerce_price_rule\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the price based on the available and applicable price rules.
 */
class PriceResolver implements PriceResolverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PriceResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    // Load all available price rules for the given store.
    $price_rule_storage = $this->entityTypeManager
      ->getStorage('commerce_price_rule');
    $price_rules = $price_rule_storage->loadAvailable($context->getStore());

    // Find the first price rule that is available and applicable. Apply it
    // i.e. request a price. If we are given a price, then return it as the
    // result of the price resolution. If we are not given a price, continue to
    // the next price rule.
    foreach ($price_rules as $price_rule) {
      $available = $price_rule->available($entity, $quantity, $context, []);
      if (!$available) {
        continue;
      }

      if (!$price_rule->applies($entity, $quantity, $context, [])) {
        continue;
      }

      $price = $price_rule->calculate($entity, $quantity, $context);
      if ($price) {
        return $price;
      }
    }
  }

}
