<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;

/**
 * Provides an interface for defining RMA item type entities.
 */
interface CommerceReturnItemTypeInterface extends CommerceBundleEntityInterface {

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
