<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the user role condition.
 *
 * @CommerceCondition(
 *   id = "price_rule_customer_role",
 *   label = @Translation("Role"),
 *   display_label = @Translation("Limit by role"),
 *   category = @Translation("Customer"),
 *   entity_type = "user",
 * )
 */
class CustomerRole extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'roles' => [],
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

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed roles'),
      '#default_value' => $this->configuration['roles'],
      '#options' => array_map(
        '\Drupal\Component\Utility\Html::escape',
        user_role_names()
      ),
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
    $this->configuration['roles'] = array_filter($values['roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    $roles = $entity ? $entity->getRoles() : ['anonymous'];

    return (bool) array_intersect($this->configuration['roles'], $roles);
  }

}
