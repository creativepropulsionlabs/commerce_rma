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
        return AccessResult::allowedIfHasPermission($account, 'view published commerce return entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit commerce return entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete commerce return entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add commerce return entities');
  }

}
