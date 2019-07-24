<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the RMA entity.
 *
 * @ingroup commerce_rma
 *
 * @ContentEntityType(
 *   id = "commerce_rma_entity",
 *   label = @Translation("RMA"),
 *   bundle_label = @Translation("RMA type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\RMAListBuilder",
 *     "views_data" = "Drupal\commerce_rma\Entity\RMAViewsData",
 *     "translation" = "Drupal\commerce_rma\RMATranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_rma\Form\RMAForm",
 *       "add" = "Drupal\commerce_rma\Form\RMAForm",
 *       "edit" = "Drupal\commerce_rma\Form\RMAForm",
 *       "delete" = "Drupal\commerce_rma\Form\RMADeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\RMAHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\commerce_rma\RMAAccessControlHandler",
 *   },
 *   base_table = "commerce_rma_entity",
 *   data_table = "commerce_rma_entity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer rma entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/commerce_rma_entity/{commerce_rma_entity}",
 *     "add-page" = "/admin/commerce/commerce_rma_entity/add",
 *     "add-form" = "/admin/commerce/commerce_rma_entity/add/{commerce_rma_type}",
 *     "edit-form" = "/admin/commerce/commerce_rma_entity/{commerce_rma_entity}/edit",
 *     "delete-form" = "/admin/commerce/commerce_rma_entity/{commerce_rma_entity}/delete",
 *     "collection" = "/admin/commerce/commerce_rma_entity",
 *   },
 *   bundle_entity_type = "commerce_rma_type",
 *   field_ui_base_route = "entity.commerce_rma_type.edit_form"
 * )
 */
class RMA extends CommerceContentEntityBase implements RMAInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the RMA entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The RMA state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_rma\Entity\RMA', 'getWorkflowId']);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rma\Entity\RMAInterface $rma_order
   *   The RMA.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(RMAInterface $rma_order) {
    $workflow = RMAType::load($rma_order->bundle())->getWorkflowId();
    return $workflow;
  }

}
