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
 *   id = "commerce_return",
 *   label = @Translation("Commerce return"),
 *   bundle_label = @Translation("Commerce return type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\CommerceReturnListBuilder",
 *     "views_data" = "Drupal\commerce_rma\Entity\CommerceReturnViewsData",
 *     "translation" = "Drupal\commerce_rma\CommerceReturnTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_rma\Form\CommerceReturnForm",
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnForm",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\CommerceReturnHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\commerce_rma\CommerceReturnAccessControlHandler",
 *   },
 *   base_table = "commerce_return",
 *   data_table = "commerce_return_field_data",
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
 *     "canonical" = "/admin/commerce/commerce_return/{commerce_return}",
 *     "add-page" = "/admin/commerce/commerce_return/add",
 *     "add-form" = "/admin/commerce/commerce_return/add/{commerce_return_type}",
 *     "edit-form" = "/admin/commerce/commerce_return/{commerce_return}/edit",
 *     "delete-form" = "/admin/commerce/commerce_return/{commerce_return}/delete",
 *     "collection" = "/admin/commerce/commerce_return",
 *   },
 *   bundle_entity_type = "commerce_return_type",
 *   field_ui_base_route = "entity.commerce_return_type.edit_form"
 * )
 */
class CommerceReturn extends CommerceContentEntityBase implements CommerceReturnInterface {

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
      ->setSetting('workflow_callback', ['\Drupal\commerce_rma\Entity\CommerceReturn', 'getWorkflowId']);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnInterface $rma_order
   *   The RMA.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(CommerceReturnInterface $rma_order) {
    $workflow = CommerceReturnType::load($rma_order->bundle())->getWorkflowId();
    return $workflow;
  }

}
