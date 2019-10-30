<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the RMA reason entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_return_reason",
 *   label = @Translation("RMA reason"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\CommerceReturnReasonListBuilder",
 *     "access" = "Drupal\commerce_rma\CommerceReturnReasonAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnReasonForm",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnReasonForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnReasonDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\CommerceReturnReasonHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_return_reason",
 *   admin_permission = "administer return reasons",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rma_reason/{commerce_return_reason}",
 *     "add-form" = "/admin/commerce/rma_reason/add",
 *     "edit-form" = "/admin/commerce/rma_reason/{commerce_return_reason}/edit",
 *     "delete-form" = "/admin/commerce/rma_reason/{commerce_return_reason}/delete",
 *     "collection" = "/admin/commerce/rma_reason"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "weight",
 *     "type"
 *   }
 * )
 */
class CommerceReturnReason extends ConfigEntityBase implements CommerceReturnReasonInterface {

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
   * The RMA reason type.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

}
