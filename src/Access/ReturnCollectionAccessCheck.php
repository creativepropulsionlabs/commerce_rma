<?php

namespace Drupal\commerce_rma\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the Return collection route.
 */
class ReturnCollectionAccessCheck implements AccessInterface {

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
      'partial_returned',
      'returned',
    ];
    // Check if this is a cart order.
    $order_is_completed = in_array($order->getState()->getId(), $show_return_states);

    // Only allow access if order type has a corresponding return type.
    // @todo should we validate that the return type exists?
    return AccessResult::allowedIf($return_type_id !== NULL)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'administer commerce return'))
      ->andIf(AccessResult::allowedIf($order_is_completed))
      ->addCacheableDependency($order_type)
      ->addCacheableDependency($order);
  }

}
