<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_rma\Entity\CommerceReturnInterface;
use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the storage handler class for RMA entities.
 *
 * This extends the base storage class, adding required special handling for
 * RMA entities.
 *
 * @ingroup commerce_rma
 */
class CommerceReturnStorage extends CommerceContentEntityStorage implements CommerceReturnStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CommerceReturnInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {rma_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {rma_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CommerceReturnInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {rma_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('rma_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
