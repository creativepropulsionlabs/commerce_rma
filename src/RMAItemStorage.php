<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_rma\Entity\RMAItemInterface;

/**
 * Defines the storage handler class for RMA item entities.
 *
 * This extends the base storage class, adding required special handling for
 * RMA item entities.
 *
 * @ingroup commerce_rma
 */
class RMAItemStorage extends SqlContentEntityStorage implements RMAItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(RMAItemInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {rma_item_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {rma_item_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(RMAItemInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {rma_item_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('rma_item_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
