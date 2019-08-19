<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

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
 *     "form" = {
 *       "default" = "Drupal\commerce_rma\Form\CommerceReturnForm",
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnFormAdd",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_rma\ReturnRouteProvider",
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
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/orders/{commerce_order}/returns/{commerce_return}",
 *     "add-page" = "/admin/commerce/orders/{commerce_order}/returns/add",
 *     "collection" = "/admin/commerce/orders/{commerce_order}/returns",
 *     "add-form" = "/admin/commerce/orders/{commerce_order}/returns/add/{commerce_return_type}",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/returns/{commerce_return}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/returns/{commerce_return}/delete",
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
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_order'] = $this->getOrderId();
    return $uri_route_parameters;
  }

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
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (['order_id', 'return_items'] as $field) {
      if ($this->get($field)->isEmpty()) {
        throw new EntityMalformedException(sprintf('Required return field "%s" is empty.', $field));
      }
    }
  }

  function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if (!$update) {
      $order = $this->getOrder();
      $order->get('returns')->appendItem([
        'target_id' => $this->id()
      ]);
      $order->save();
    }
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
      ->setSetting('workflow_callback', [
        CommerceReturn::class,
        'getWorkflowId',
      ]);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Billing information'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'rma_commerce_billing_profile',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['refund_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Refund gateway'))
      ->setDescription(t('The refund gateway.'))
      ->setSetting('target_type', 'commerce_refund_gateway')
      ->setReadOnly(TRUE);

    // The order backreference.
    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['total_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total return amount'))
      ->setDescription(t('The return total amount (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_total_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total returned amount (Confirmed)'))
      ->setDescription(t('The returned total amount (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
