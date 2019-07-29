<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the RMA type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_return_type",
 *   label = @Translation("RMA type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\CommerceReturnTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnTypeForm",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnTypeForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\CommerceReturnTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_return_type",
 *   admin_permission = "administer commerce_return_type",
 *   bundle_of = "commerce_return",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rma_type/{commerce_return_type}",
 *     "add-form" = "/admin/commerce/rma_type/add",
 *     "edit-form" = "/admin/commerce/rma_type/{commerce_return_type}/edit",
 *     "delete-form" = "/admin/commerce/rma_type/{commerce_return_type}/delete",
 *     "collection" = "/admin/commerce/rma_type"
 *   }
 * )
 */
class CommerceReturnType extends CommerceBundleEntityBase  implements CommerceReturnTypeInterface {

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
