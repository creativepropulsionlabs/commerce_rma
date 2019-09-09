<?php

namespace Drupal\commerce_rma\Entity;

use CommerceGuys\Intl\Calculator;
use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

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
 *       "user-add" = "Drupal\commerce_rma\Form\CommerceReturnFormAdd",
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
 *     "add-user-page" = "/user/{user}/orders/{commerce_order}/returns/add",
 *     "collection" = "/admin/commerce/orders/{commerce_order}/returns",
 *     "add-form" = "/admin/commerce/orders/{commerce_order}/returns/add/{commerce_return_type}",
 *     "add-user-form" = "/user/{user}/orders/{commerce_order}/returns/{commerce_return_type}/add",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/returns/{commerce_return}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/returns/{commerce_return}/delete",
 *   },
 *   bundle_entity_type = "commerce_return_type",
 *   field_ui_base_route = "entity.commerce_return_type.edit_form"
 * )
 */
class CommerceReturn extends CommerceContentEntityBase implements CommerceReturnInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

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

    $this->recalculateTotals();
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
      // Force to place return to check it by manager.
      $workflow_manager = \Drupal::service('plugin.manager.workflow');
      $order_bundle = $order->bundle();
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $this->entityTypeManager()->getStorage('commerce_order_type')->load($order_bundle);
      $order_return_workflow_id = $order_type->getThirdPartySetting('commerce_rma', 'return_workflow');
      /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_return_workflow */
      $order_return_workflow = $workflow_manager->createInstance($order_return_workflow_id);
      $transition_id = 'place';
      $transition = $order_return_workflow->getTransition($transition_id);
      if ($order->get('return_state')->isEmpty()) {
        $order->return_state = 'draft';
        $order->save();
      }
      $order->get('return_state')->first()->applyTransition($transition);
      $order->save();

    }
    else {

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The ID of the associated user.'))
      ->setSetting('target_type', 'user')
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
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Return.'))
      ->setReadOnly(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
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



    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total return price'))
      ->setDescription(t('The return total price (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_unit_price',
        'weight' => -4,
      ]);

    $fields['total_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Quantity'))
      ->setDescription(t('The quantity for return.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['confirmed_total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total returned price (Confirmed)'))
      ->setDescription(t('The returned total price (Value which should be returned to user). Manager can modify this value if manual return is in use.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed_total_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Quantity (Confirmed)'))
      ->setDescription(t('The quantity for return (confirmed).'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  public function recalculateTotals() {
    $total_price = new Price('0', $this->getOrder()->getTotalPrice()->getCurrencyCode());
    $total_quantity = '0';
    $confirmed_total_price = new Price('0', $this->getOrder()->getTotalPrice()->getCurrencyCode());
    $confirmed_total_quantity = '0';
    foreach ($this->getItems() as $item) {
      $total_price = $total_price->add($item->getTotalPrice());
      if ($item->getConfirmedTotalPrice()) {
        $confirmed_total_price = $confirmed_total_price->add($item->getConfirmedTotalPrice());
      }
      $total_quantity = Calculator::add($total_quantity, $item->getQuantity());
      if ($item->getConfirmedTotalQuantity()) {
        $confirmed_total_quantity = Calculator::add($confirmed_total_quantity, $item->getConfirmedTotalQuantity());
      }
    }
    $this->set('total_price', $total_price);
    $this->set('confirmed_total_price', $confirmed_total_price);
    $this->set('total_quantity', $total_quantity);
    $this->set('confirmed_total_quantity', $confirmed_total_quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('return_items')->referencedEntities();
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

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnInterface $rma_order
   *   The RMA.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getOrderWorkflowId($rma_order) {
    $workflow = CommerceReturnType::load($rma_order->bundle())->getWorkflowId();
    return $workflow;
  }

}
