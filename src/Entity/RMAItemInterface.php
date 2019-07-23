<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\commerce_price\Price;

/**
 * Provides an interface for defining RMA item entities.
 *
 * @ingroup commerce_rma
 */
interface RMAItemInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Gets the RMA item name.
   *
   * @return string
   *   Name of the RMA item.
   */
  public function getName();

  /**
   * Sets the RMA item name.
   *
   * @param string $name
   *   The RMA item name.
   *
   * @return \Drupal\commerce_rma\Entity\RMAItemInterface
   *   The called RMA item entity.
   */
  public function setName($name);

  /**
   * Gets the RMA item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the RMA item.
   */
  public function getCreatedTime();

  /**
   * Sets the RMA item creation timestamp.
   *
   * @param int $timestamp
   *   The RMA item creation timestamp.
   *
   * @return \Drupal\commerce_rma\Entity\RMAItemInterface
   *   The called RMA item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the RMA item revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the RMA item revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\commerce_rma\Entity\RMAItemInterface
   *   The called RMA item entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the RMA item revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the RMA item revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\commerce_rma\Entity\RMAItemInterface
   *   The called RMA item entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the RMA item  amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The shipment amount, or NULL if unknown.
   */
  public function getAmount();

  /**
   * Sets the RMA item amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The RMA item amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount);

  /**
   * Gets the RMA item state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The RMA item state.
   */
  public function getState();

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getItem();

  /**
   * Sets the order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item.
   *
   * @return $this
   */
  public function setItem($item);

}
