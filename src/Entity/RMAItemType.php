<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the RMA item type entity.
 *
 * @ConfigEntityType(
 *   id = "rma_item_type",
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
 *   config_prefix = "rma_item_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "rma_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rma_item_type/{rma_item_type}",
 *     "add-form" = "/admin/structure/rma_item_type/add",
 *     "edit-form" = "/admin/structure/rma_item_type/{rma_item_type}/edit",
 *     "delete-form" = "/admin/structure/rma_item_type/{rma_item_type}/delete",
 *     "collection" = "/admin/structure/rma_item_type"
 *   }
 * )
 */
class RMAItemType extends ConfigEntityBundleBase implements RMAItemTypeInterface {

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
   * The RMA workflow.
   *
   * @var \Drupal\commerce_rma\Entity\RMAWorkflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_rma\Entity\RMAWorkflow
   */
  public function getWorkflow() {
    return $this->workflow;
  }

}
