<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the RMA entity.
 *
 * @see \Drupal\commerce_rma\Entity\CommerceReturn.
 */
class CommerceReturnAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $entity */
    switch ($operation) {
      case 'view':
//        if (!$entity->isPublished()) {
//          return AccessResult::allowedIfHasPermission($account, 'view unpublished rma entities');
//        }
        return AccessResult::allowedIfHasPermission($account, 'view published rma entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit rma entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete rma entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add rma entities');
  }

}
