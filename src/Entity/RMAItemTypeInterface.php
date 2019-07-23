<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining RMA item type entities.
 */
interface RMAItemTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the RMA workflow.
   *
   * @return \Drupal\commerce_rma\Entity\RMAWorkflow
   */
  public function getWorkflow();
}
