<?php

namespace Drupal\commerce_rma\Access;

use Drupal\commerce_price\Calculator;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the Return collection route.
 */
class ReturnAddAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ShipmentCollectionAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the Return collection.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());

    $perm = AccessResult::allowedIfHasPermission($account, 'administer commerce return')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'add commerce return entities'));
    if (!$perm->isAllowed()) {
      return AccessResult::forbidden()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }

    // Check if this is a cart order.
    if ($order->getState()->getId() != 'completed') {
      return AccessResult::forbidden()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }

    $return_max_order_age = $order_type->getThirdPartySetting('commerce_rma', 'return_max_order_age', 0);
    $order_max_age_timestamp = \Drupal::time()->getRequestTime() - $return_max_order_age * 86400;
    if ($return_max_order_age && $order->getCompletedTime() < $order_max_age_timestamp) {
      return AccessResult::forbidden()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }

    $return_type_id = $order_type->getThirdPartySetting('commerce_rma', 'return_type');
    if (is_null($return_type_id)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }

    if ($order->get('returns')->isEmpty()) {
      return AccessResult::allowed()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface[] $returns */
    $returns = $order->get('returns')->referencedEntities();
    $skip_return_states = ['canceled', 'rejected'];

    foreach ($returns as $return_id => $return) {
      if (in_array($return->getState()->value, $skip_return_states)) {
        unset($returns[$return_id]);
      }
    }

    $order_requested_quantity = "0";
    foreach ($returns as $return_id => $return) {
      $field_name = $return->getState()->value == 'draft' ? 'total_quantity' : 'confirmed_total_quantity';
      $return_quantity = $return->get($field_name)->getValue()[0]['value'];
      $order_requested_quantity = Calculator::add($order_requested_quantity, $return_quantity);
    }

    $original_order_quantity = "0";
    foreach ($order->getItems() as $order_item) {
      $original_order_quantity = Calculator::add($original_order_quantity, $order_item->getQuantity());
    }

    if (Calculator::compare($original_order_quantity, $order_requested_quantity) === 1) {
      return AccessResult::allowed()
        ->addCacheableDependency($order_type)
        ->addCacheableDependency($order);
    }

    return AccessResult::forbidden()
      ->addCacheableDependency($order_type)
      ->addCacheableDependency($order);
  }

}
