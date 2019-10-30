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
class CommerceReturnReasonAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['access commerce return reasons']);

      default:
        return parent::checkAccess($entity, $operation, $account);
    }

  }

}
