<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining RMA reason entities.
 */
interface CommerceReturnReasonInterface extends ConfigEntityInterface {

  /**
   * Gets RMA reason description.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Gets RMA reason weight.
   *
   * @return int
   *   The weight of reason.
   */
  public function getWeight();

  /**
   * Gets RMA reason type.
   *
   * @return string
   *   The type of reason.
   */
  public function getType();


}
