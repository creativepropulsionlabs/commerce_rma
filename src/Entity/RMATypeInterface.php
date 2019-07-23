<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining RMA type entities.
 */
interface RMATypeInterface extends ConfigEntityInterface {

  /**
   * Gets the RMA workflow.
   *
   * @return \Drupal\commerce_rma\Entity\RMAWorkflow
   */
  public function getWorkflow();

}
