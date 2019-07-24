<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the RMA type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_rma_type",
 *   label = @Translation("RMA type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\RMATypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\RMATypeForm",
 *       "edit" = "Drupal\commerce_rma\Form\RMATypeForm",
 *       "delete" = "Drupal\commerce_rma\Form\RMATypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\RMATypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_rma_type",
 *   admin_permission = "administer commerce_rma_type",
 *   bundle_of = "commerce_rma_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rma_type/{commerce_rma_type}",
 *     "add-form" = "/admin/commerce/rma_type/add",
 *     "edit-form" = "/admin/commerce/rma_type/{commerce_rma_type}/edit",
 *     "delete-form" = "/admin/commerce/rma_type/{commerce_rma_type}/delete",
 *     "collection" = "/admin/commerce/rma_type"
 *   }
 * )
 */
class RMAType extends CommerceBundleEntityBase  implements RMATypeInterface {

  /**
   * The RMA type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The RMA type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The RMA workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   *
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // The order type must depend on the module that provides the workflow.
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflow = $workflow_manager->createInstance($this->getWorkflowId());
    $this->calculatePluginDependencies($workflow);

    return $this;
  }


}
