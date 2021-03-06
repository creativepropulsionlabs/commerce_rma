<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining RMA item entities.
 *
 * @ingroup commerce_rma
 */
interface CommerceReturnItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets Quantity.
   *
   * @return string
   */
  public function getQuantity();

  /**
   * Gets Confirmed Total Quantity.
   *
   * @return string
   */
  public function getConfirmedTotalQuantity();

  /**
   * Gets Total Price.
   *
   * @return \Drupal\commerce_price\Price
   */
  public function getTotalPrice();

  /**
   * Gets Confirmed Total Price.
   *
   * @return \Drupal\commerce_price\Price
   */
  public function getConfirmedTotalPrice();



  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItem
   *   The order item.
   */
  public function getOrderItem();

  /**
   * Sets the order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $orderItem
   *   The RMA item name.
   *
   * @return $this
   */
  public function setOrderItem(OrderItem $orderItem);

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
   * @return $this
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
   * @return $this
   */
  public function setCreatedTime($timestamp);

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
  public function setItem(OrderItemInterface $item);

}
