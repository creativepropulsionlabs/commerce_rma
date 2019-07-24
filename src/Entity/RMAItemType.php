<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the RMA item type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_rma_item_type",
 *   label = @Translation("RMA item type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\RMAItemTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\RMAItemTypeForm",
 *       "edit" = "Drupal\commerce_rma\Form\RMAItemTypeForm",
 *       "delete" = "Drupal\commerce_rma\Form\RMAItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\RMAItemTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_rma_item_type",
 *   admin_permission = "administer commerce_rma_item_type",
 *   bundle_of = "commerce_rma_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rma_item_type/{commerce_rma_item_type}",
 *     "add-form" = "/admin/commerce/rma_item_type/add",
 *     "edit-form" = "/admin/commerce/rma_item_type/{commerce_rma_item_type}/edit",
 *     "delete-form" = "/admin/commerce/rma_item_type/{commerce_rma_item_type}/delete",
 *     "collection" = "/admin/commerce/rma_item_type"
 *   }
 * )
 */
class RMAItemType extends CommerceBundleEntityBase implements RMAItemTypeInterface {

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
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;


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
