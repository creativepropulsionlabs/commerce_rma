<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the Commerce return item type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_return_item_type",
 *   label = @Translation("Commerce return item type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\CommerceReturnItemTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnItemTypeForm",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnItemTypeForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\CommerceReturnItemTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_return_item_type",
 *   admin_permission = "administer commerce_return_item_type",
 *   bundle_of = "commerce_return_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/commerce_return_item_type/{commerce_return_item_type}",
 *     "add-form" = "/admin/commerce/commerce_return_item_type/add",
 *     "edit-form" = "/admin/commerce/commerce_return_item_type/{commerce_return_item_type}/edit",
 *     "delete-form" = "/admin/commerce/commerce_return_item_type/{commerce_return_item_type}/delete",
 *     "collection" = "/admin/commerce/commerce_return_item_type"
 *   }
 * )
 */
class CommerceReturnItemType extends CommerceBundleEntityBase implements CommerceReturnItemTypeInterface {

  /**
   * The RMA item type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The RMA item type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The workflow ID.
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


//  /**
//   * The RMA workflow.
//   *
//   * @var \Drupal\commerce_rma\Entity\RMAWorkflow
//   */
//  protected $workflow;

//  /**
//   * {@inheritdoc}
//   *
//   * @return \Drupal\commerce_rma\Entity\RMAWorkflow
//   */
//  public function getWorkflow() {
//    return $this->workflow;
//  }

}
