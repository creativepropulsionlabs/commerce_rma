<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining RMA reason entities.
 */
interface RMAReasonInterface extends ConfigEntityInterface {

  /**
   * Gets RMA reason description.
   */
  public function getDescription();

  /**
   * Gets RMA reason weight.
   */
  public function getWeight();

}
