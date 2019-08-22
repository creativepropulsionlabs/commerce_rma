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
class ConfirmTransitionAccessCheck implements AccessInterface {

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
    $entity_id = $route_match->getParameter('commerce_return');
    $transition_id = $route_match->getParameter('workflow_transition');
    $storage = $this->entityTypeManager->getStorage('commerce_return');
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $entity */
    $entity = $storage->load($entity_id);


    // Only allow access if order type has a corresponding return type.
    // @todo should we validate that the return type exists?
    $perm = 'use commerce_return ' . $entity->bundle() . ' ' . $transition_id . ' transition';
    return AccessResult::allowedIfHasPermission($account, $perm)
      ->addCacheableDependency($transition_id)
      ->addCacheableDependency($entity);
  }

}
