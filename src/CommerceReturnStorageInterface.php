<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_rma\Entity\CommerceReturnInterface;

/**
 * Defines the storage handler class for RMA entities.
 *
 * This extends the base storage class, adding required special handling for
 * RMA entities.
 *
 * @ingroup commerce_rma
 */
interface CommerceReturnStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of RMA revision IDs for a specific RMA.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnInterface $entity
   *   The RMA entity.
   *
   * @return int[]
   *   RMA revision IDs (in ascending order).
   */
  public function revisionIds(CommerceReturnInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as RMA author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   RMA revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnInterface $entity
   *   The RMA entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CommerceReturnInterface $entity);

  /**
   * Unsets the language for all RMA with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
