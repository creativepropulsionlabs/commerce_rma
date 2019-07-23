<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface RMAItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of RMA item revision IDs for a specific RMA item.
   *
   * @param \Drupal\commerce_rma\Entity\RMAItemInterface $entity
   *   The RMA item entity.
   *
   * @return int[]
   *   RMA item revision IDs (in ascending order).
   */
  public function revisionIds(RMAItemInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as RMA item author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   RMA item revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\commerce_rma\Entity\RMAItemInterface $entity
   *   The RMA item entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(RMAItemInterface $entity);

  /**
   * Unsets the language for all RMA item with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
