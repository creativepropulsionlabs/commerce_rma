<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining RMA entities.
 *
 * @ingroup commerce_rma
 */
interface CommerceReturnInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Return items.
   *
   * @return \Drupal\commerce_rma\Entity\CommerceReturnItemInterface[]
   */
  public function getItems();

  /**
   * Order
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   */
  public function getOrder();

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
   * @return \Drupal\commerce_rma\Entity\CommerceReturnInterface
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
   * @return \Drupal\commerce_rma\Entity\CommerceReturnInterface
   *   The called RMA entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the return state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The return state.
   */
  public function getState();

}
