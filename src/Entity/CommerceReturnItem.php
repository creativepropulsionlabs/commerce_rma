<?php

namespace Drupal\commerce_rma\Entity;

use CommerceGuys\Intl\Calculator;
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
    return $this->get('order_item')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    if (!$this->get('total_price')->isEmpty()) {
      return $this->get('total_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmedTotalPrice() {
    if (!$this->get('confirmed_total_price')->isEmpty()) {
      return $this->get('confirmed_total_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItem(OrderItem $orderItem) {
    $this->set('order_item', $orderItem);
    return $this;
  }

  function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // @todo need refactor (case with manual add  item)
    if (empty($this->label())) {
      $order_id = \Drupal::routeMatch()->getParameter('commerce_order');
//      $order = \Drupal::entityTypeManager()->getStorage('commerce_order')
      $this->set('name', 'Return for Order #' . $order_id);
//      $this->set('order_id', $order_id);
    }
    $this->recalculateTotals();
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
  public function getUnitPrice() {
    if (!$this->get('unit_price')->isEmpty()) {
      return $this->get('unit_price')->first()->toPrice();
    }
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmedPrice() {
    if (!$this->get('confirmed_price')->isEmpty()) {
      return $this->get('confirmed_price')->first()->toPrice();
    }
    return new Price('0', $this->getOrderItem()->getOrder()->getTotalPrice()->getCurrencyCode());
  }
  /**
   * {@inheritdoc}
   */
  public function setUnitPrice(Price $price) {
    $this->set('unit_price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem() {
    return $this->get('order_item')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItem(OrderItemInterface $item) {
    $this->set('order_item', $item);
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
      ->setReadOnly(TRUE)
      ->setSettings([
        'max_length' => 512,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
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

    $fields['unit_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Requested item unit price'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Requested Quantity'))
      ->setDescription(t('The quantity for return.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_quantity',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Requested total'))
      ->setDescription(t('The return total price (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['confirmed_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Confirmed Quantity'))
      ->setDescription(t('The quantity for return (confirmed).'))
//      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_quantity',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Confirmed Price'))
      ->setDescription(t('The amount of money for return (confirmed).'))
//      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);







    $fields['order_item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item'))
      ->setDescription(t('The order item.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTargetEntityTypeId('commerce_return_item')
      ->setSetting('target_type', 'commerce_order_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['reason'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reason'))
      ->setDescription(t('The reason of item return.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_return_reason')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setReadOnly(TRUE)
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

    $fields['expected_resolution'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Expected resolution'))
      ->setDescription(t('The expected resolution of item return.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_return_reason')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setReadOnly(TRUE)
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
      ->setLabel(t("Client's Note"))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -1,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['manager_note'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t("Manager's note"))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 2,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);



    $fields['confirmed_total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total returned price (confirmed)'))
      ->setDescription(t('The returned total price (Value of money which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (string) $this->get('quantity')->value;
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmedQuantity() {
    if ($this->get('confirmed_quantity')->isEmpty()) {
      return '0';
    }
    return (string) $this->get('confirmed_quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmedTotalQuantity() {
    return (string) $this->get('confirmed_quantity')->value;
  }

  public function recalculateTotals() {
    $price = $this->getUnitPrice();
    if (empty($price)) {
      $price = $this->getItem()[0]->getUnitPrice();
      $this->setUnitPrice($price);
    }
    $total_price = $price->multiply($this->getQuantity());
    $this->set('total_price', $total_price);
    $confirmed_quantity = $this->getConfirmedQuantity();
    $confirmed_price = $this->getConfirmedPrice();
    $confirmed_total_price = $confirmed_price->multiply($confirmed_quantity);
    $this->set('confirmed_total_price', $confirmed_total_price);
  }

}
