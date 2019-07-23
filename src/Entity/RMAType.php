<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the RMA type entity.
 *
 * @ConfigEntityType(
 *   id = "rma_type",
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
 *   config_prefix = "rma_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "rma",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rma_type/{rma_type}",
 *     "add-form" = "/admin/structure/rma_type/add",
 *     "edit-form" = "/admin/structure/rma_type/{rma_type}/edit",
 *     "delete-form" = "/admin/structure/rma_type/{rma_type}/delete",
 *     "collection" = "/admin/structure/rma_type"
 *   }
 * )
 */
class RMAType extends ConfigEntityBundleBase implements RMATypeInterface {

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
