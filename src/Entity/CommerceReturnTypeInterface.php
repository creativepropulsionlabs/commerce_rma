<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;
use Drupal\commerce_order\Entity\OrderTypeInterface;

/**
 * Provides an interface for defining RMA type entities.
 */
interface CommerceReturnTypeInterface extends CommerceBundleEntityInterface {


  /**
   * Gets the order type's workflow ID.
   *
   * @return string
   *   The order type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the order type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);

}
