<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the RMA item entity.
 *
 * @ingroup commerce_rma
 *
 * @ContentEntityType(
 *   id = "commerce_return_item",
 *   label = @Translation("RMA item"),
 *   bundle_label = @Translation("RMA item type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_rma\CommerceReturnItemStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\CommerceReturnItemListBuilder",
 *     "views_data" = "Drupal\commerce_rma\Entity\CommerceReturnItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_rma\Form\CommerceReturnItemForm",
 *       "add" = "Drupal\commerce_rma\Form\CommerceReturnItemForm",
 *       "edit" = "Drupal\commerce_rma\Form\CommerceReturnItemForm",
 *       "delete" = "Drupal\commerce_rma\Form\CommerceReturnItemDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\CommerceReturnItemHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\commerce_rma\CommerceReturnItemAccessControlHandler",
 *   },
 *   base_table = "commerce_return_item",
 *   data_table = "commerce_return_item_field_data",
 *   revision_table = "commerce_return_item_revision",
 *   revision_data_table = "commerce_return_item_field_revision",
 *   admin_permission = "administer commerce_return_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rma_item/{commerce_return_item}",
 *     "add-page" = "/admin/commerce/rma_item/add",
 *     "add-form" = "/admin/commerce/rma_item/add/{commerce_return_item_type}",
 *     "edit-form" = "/admin/commerce/rma_item/{commerce_return_item}/edit",
 *     "delete-form" = "/admin/commerce/rma_item/{commerce_return_item}/delete",
 *     "collection" = "/admin/commerce/rma_item",
 *   },
 *   bundle_entity_type = "commerce_return_item_type",
 *   field_ui_base_route = "entity.commerce_return_item_type.edit_form"
 * )
 */
class CommerceReturnItem extends CommerceContentEntityBase implements CommerceReturnItemInterface {

  use EntityChangedTrait;

  /**
   * The purchasable entity type ID.
   *
   * @var \Drupal\commerce_order\Entity\OrderItem
   */
  protected $orderItem;

  /**
   * {@inheritdoc}
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItem(OrderItem $orderItem) {
    $this->set('order_item', $orderItem);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
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
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
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
  public function getItem() {
    return $this->get('item')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItem(OrderItemInterface $item) {
    $this->set('item', $item);
//    $this->recalculateTotalPrice();
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the RMA item entity.'))
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

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The amount for return.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Confirmed Amount'))
      ->setDescription(t('The amount for return (confirmed).'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ConfirmedQuantity'))
      ->setDescription(t('The quantity for return (confirmed).'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['manager_note'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t("Manager's note"))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The quantity for return.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
      ->setSetting('workflow_callback', ['\Drupal\commerce_rma\Entity\CommerceReturnItem', 'getWorkflowId']);

    $fields['order_item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item'))
      ->setDescription(t('The order item.'))
      ->setRequired(TRUE)
      ->setTargetEntityTypeId('commerce_return_item')
      ->setSetting('target_type', 'commerce_order_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['note'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Note'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['reason'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reason'))
      ->setDescription(t('The reason of item return.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_return_reason')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total return amount'))
      ->setDescription(t('The return total amount (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_total_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total returned amount'))
      ->setDescription(t('The returned total amount (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnItemInterface $rma_item
   *   The RMA Item
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(CommerceReturnItemInterface $rma_item) {
    $workflow = CommerceReturnItemType::load($rma_item->bundle())->getWorkflowId();
    return $workflow;
  }

}
