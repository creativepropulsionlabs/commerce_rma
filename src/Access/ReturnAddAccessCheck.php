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
    $return_type_id = $order_type->getThirdPartySetting('commerce_rma', 'return_type');
    $show_return_states = [
      'completed',
      'returned',
    ];
    // Check if this is a cart order.
    $order_is_completed = in_array($order->getState()->getId(), $show_return_states);
    $order_is_not_returned_full = !in_array($order->get('return_state')->value, $show_return_states);
    if ($order_is_not_returned_full && !$order->get('returns')->isEmpty()) {
      // Check if requested quantity count less then existing in order (except Cancelled).
      $order_requested_quantity = "0";
      foreach ($order->get('returns')->referencedEntities() as $return) {
        if ($return->getState()->value != 'canceled') {
          $return_quantity = $return->get('confirmed_total_quantity')->getValue()[0]['value'];
          $order_requested_quantity = Calculator::add($order_requested_quantity, $return_quantity);
        }
      }
      $original_order_quantity = "0";
      foreach ($order->getItems() as $order_item) {
        $original_order_quantity = Calculator::add($original_order_quantity, $order_item->getQuantity());
      }
      if (Calculator::compare($original_order_quantity, $order_requested_quantity) !== 1) {
        $order_is_not_returned_full = FALSE;
      }
    }

    // Only allow access if order type has a corresponding return type.
    // @todo should we validate that the return type exists?
    $perm = AccessResult::allowedIfHasPermission($account, 'administer commerce return')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'add commerce return entities'));
    $res = AccessResult::allowedIf($return_type_id !== NULL)
      ->andIf($perm)
      ->andIf(AccessResult::allowedIf($order_is_completed))
      ->andIf(AccessResult::allowedIf($order_is_not_returned_full))
      ->addCacheableDependency($order_type)
      ->addCacheableDependency($order);
    return $res;
  }

}
