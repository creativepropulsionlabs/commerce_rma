<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining RMA entities.
 *
 * @ingroup commerce_rma
 */
interface RMAInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the RMA name.
   *
   * @return string
   *   Name of the RMA.
   */
  public function getName();

  /**
   * Sets the RMA name.
   *
   * @param string $name
   *   The RMA name.
   *
   * @return \Drupal\commerce_rma\Entity\RMAInterface
   *   The called RMA entity.
   */
  public function setName($name);

  /**
   * Gets the RMA creation timestamp.
   *
   * @return int
   *   Creation timestamp of the RMA.
   */
  public function getCreatedTime();

  /**
   * Sets the RMA creation timestamp.
   *
   * @param int $timestamp
   *   The RMA creation timestamp.
   *
   * @return \Drupal\commerce_rma\Entity\RMAInterface
   *   The called RMA entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the RMA revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the RMA revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\commerce_rma\Entity\RMAInterface
   *   The called RMA entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the RMA revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the RMA revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\commerce_rma\Entity\RMAInterface
   *   The called RMA entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the RMA workflow state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The RMA workflow state.
   */
  public function getStates();

  /**
   * Gets RMA items.
   *
   * @return \Drupal\commerce_rma\Entity\RMAItem[]
   */
  public function getItems();

  /**
   * Sets RMA items.
   *
   * @param \Drupal\commerce_rma\Entity\RMAItem[] $items
   *   The order items.
   *
   * @return $this
   */
  public function setItems(array $items);

}
