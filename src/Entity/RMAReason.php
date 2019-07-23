<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the RMA reason entity.
 *
 * @ConfigEntityType(
 *   id = "rma_reason",
 *   label = @Translation("RMA reason"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\RMAReasonListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\RMAReasonForm",
 *       "edit" = "Drupal\commerce_rma\Form\RMAReasonForm",
 *       "delete" = "Drupal\commerce_rma\Form\RMAReasonDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\RMAReasonHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "rma_reason",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rma_reason/{rma_reason}",
 *     "add-form" = "/admin/structure/rma_reason/add",
 *     "edit-form" = "/admin/structure/rma_reason/{rma_reason}/edit",
 *     "delete-form" = "/admin/structure/rma_reason/{rma_reason}/delete",
 *     "collection" = "/admin/structure/rma_reason"
 *   }
 * )
 */
class RMAReason extends ConfigEntityBase implements RMAReasonInterface {

  /**
   * The RMA reason ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The RMA reason label.
   *
   * @var string
   */
  protected $label;

  /**
   * The RMA reason description.
   *
   * @var string
   */
  protected $description;

  /**
   * The RMA reason weight.
   *
   * @var int
   */
  protected $weight;


  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

}
