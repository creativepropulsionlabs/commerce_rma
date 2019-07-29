<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_rma\Entity\CommerceReturn;
use Drupal\commerce_rma\Entity\CommerceReturnItem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for editing the order item quantity.
 *
 * @ViewsField("commerce_rma_order_item_edit_quantity")
 */
class CommerceReturnEditQuantity extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EditQuantity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartManagerInterface $cart_manager, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartManager = $cart_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_manager'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['allow_decimal'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['allow_decimal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow decimal quantities'),
      '#default_value' => $this->options['allow_decimal'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }
    /** @var OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($this->view->argument['order_id']->getValue());

//    $form['#attached'] = [
//      'library' => ['commerce_cart/cart_form'],
//    ];
    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->getEntity($row);
      if ($this->options['allow_decimal']) {
        $form_display = commerce_get_entity_display('commerce_order_item', $order_item->bundle(), 'form');
        $quantity_component = $form_display->getComponent('quantity');
        $step = $quantity_component['settings']['step'];
        $precision = $step >= '1' ? 0 : strlen($step) - 2;
      }
      else {
        $step = 1;
        $precision = 0;
      }

      $form[$this->options['id']][$row_index] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#title_display' => 'invisible',
        '#default_value' => round($order_item->getQuantity(), $precision),
        '#size' => 4,
        '#min' => 0,
        '#max' => 9999,
        '#step' => $step,
        '#required' => TRUE,
      ];
    }

    /** @var ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();
    $address = $billing_profile->get('address')->getValue();
    $address = array_shift($address);

    $form['actions']['billing_information'] = [
      '#type' => 'address',
      '#default_value' => [
        'given_name' => $address['given_name'],
        'family_name' => $address['family_name'],
        'organization' => $address['organization'],
        'address_line1' => $address['address_line1'],
        'address_line2' => $address['address_line2'],
        'postal_code' => $address['postal_code'],
        'locality' => $address['locality'],
        'administrative_area' => $address['administrative_area'],
        'country_code' => $address['country_code'],
        'langcode' => $address['langcode'],
      ],
      '#weight' => 0,
    ];

    $form['actions']['submit']['#rma_refund'] = TRUE;
    $form['actions']['submit']['#show_update_message'] = TRUE;
    // Replace the form submit button label.
    $form['actions']['submit']['#value'] = $this->t('Refund');
    $form['actions']['submit']['#weight'] = 1;
  }

  /**
   * Submit handler for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#rma_refund'])) {
      // Don't run when the "Remove" or "Empty cart" buttons are pressed.
      return;
    }

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($this->view->argument['order_id']->getValue());
    $quantities = $form_state->getValue($this->options['id'], []);

    // Find - if we connect handler for RMA reason field
    $handlers = $this->view->getHandlers('field');

    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_reason') {
        $reason_handler = $handler;
        break;
      }
    }
    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_note') {
        $note_handler = $handler;
        break;
      }
    }

    $save_cart = FALSE;
    $name = t('RMAForOrder').$order->label();

    // Create list of new RMA item objects.
    $commerce_return_items = [];

    foreach ($quantities as $row_index => $quantity) {
      if (!is_numeric($quantity) || $quantity < 0) {
        // The input might be invalid if the #required or #min attributes
        // were removed by an alter hook.
        continue;
      }
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->getEntity($this->view->result[$row_index]);
      if ($order_item->getQuantity() < $quantity) {
//         The quantity hasn't changed.
        continue;
      }

      /** @var \Drupal\commerce_rma\Entity\CommerceReturnItemInterface $commerce_return_item */
      $commerce_return_item = CommerceReturnItem::create([
        // TODO Check IT! For test! 'type' must be different!!
        'type' => 'default',
        'name' => $order_item->getTitle(),
        'amount' => $order_item->getUnitPrice(),
//        'quantity' => $order_item->getQuantity(),
        // IMPORTANT! Take quantity from form - NOT from order.
        'quantity' => $form_state->getValue('edit_rma_quantity')[$row_index],
        // TODO CHECK THIS field!! Must be normal field! from code
        'field_order_item' => $order_item,
        // IMPORTANT! Take reason from form - NOT from order.
        // TODO CHECK THIS field!! Must be normal field! from code
        'field_reason' => isset($reason_handler) ? $form_state->getValue($reason_handler['id'])[$row_index] : NULL,
        'field_note' => isset($note_handler) ? $form_state->getValue($note_handler['id'])[$row_index] : NULL,
      ]);
//        $commerce_return_item->save();
      $commerce_return_items[] = $commerce_return_item;

//      if ($quantity > 0) {
//        $order_item->setQuantity($quantity);
//        $this->cartManager->updateOrderItem($cart, $order_item, FALSE);
//      }
//      else {
//         Treat quantity "0" as a request for deletion.
//        $this->cartManager->removeOrderItem($cart, $order_item, FALSE);
//      }
      $save_cart = TRUE;
    }

    if ($save_cart) {

      /** @var ProfileInterface $new_billing_profile */
//      $new_billing_profile = $order->getBillingProfile()->createDuplicate();
      $new_address = $form_state->getValue('billing_information');
//      $address = [
//        'given_name' => $new_address['given_name'],
//          'family_name' => $new_address['family_name'],
//          'organization' => $new_address['organization'],
//          'address_line1' => $new_address['address_line1'],
//          'address_line2' => $new_address['address_line2'],
//          'postal_code' => $new_address['postal_code'],
//          'locality' => $new_address['locality'],
//          'administrative_area' => $new_address['administrative_area'],
//          'country_code' => $new_address['country_code'],
//          'langcode' => $new_address['langcode'],
//        ];
//        $new_billing_profile->set('address', $address);

//      $profile_storage = $this->entityTypeManager->getStorage('profile');
      // Create billing profile for RMA object.
      /** @var ProfileInterface $profile */
//      $profile = Profile::create([
//        'type' => 'customer',
//        'uid' => $order->getCustomerId(),
//      ]);
//      $profile->save();

      // Create new RMA object.
      /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $commerce_return */
      $commerce_return = CommerceReturn::create([
        'name' => $name,
        // TODO CHECK IT For test! - type must be different!!
        'type' => 'default',
        'commerce_return_items' => $commerce_return_items,
//        'field_billing_information' => $new_billing_profile,
        'field_billing_information' => $new_address,
      ]);
      $commerce_return->save();

//      $cart->save();
      if (!empty($triggering_element['#show_update_message'])) {
        $this->messenger()->addMessage($this->t('Order @label is returning.', [
          '@label' => $order->label(),
        ]));
      }
    }


//    if ($form_state->getTriggeringElement()['#id'] == 'edit-submit') {

//      // Create new RMA object.
//      $name = t('RMAForOrder').$order->label();
//
//      // Create list of new RMA item objects.
//      $commerce_return_items = [];
//      $order_items = $order->get('order_items')->getValue();
//
//      foreach ($order_items as $order_item_id_mas) {
//        $order_item_id = $order_item_id_mas['target_id'];
//        /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
//        $order_item = OrderItem::load($order_item_id);
//        /** @var \Drupal\commerce_rma\Entity\CommerceReturnItemInterface $commerce_return_item */
//        $commerce_return_item = RMAItem::create([
//          // TODO Check IT! For test! 'type' must be different!!
//          'type' => 'default',
//          'name' => $order_item->getTitle(),
//          'amount' => $order_item->get('unit_price'),
//          'quantity' => $order_item->get('quantity'),
//          // TODO CHECK THIS field!! Must be normal field!
//          'field_order_item' => $order_item,
//        ]);
////        $commerce_return_item->save();
//        $commerce_return_items[] = $commerce_return_item;
//      }
//
//      /** @var \Drupal\commerce_rma\Entity\RMAInterface $commerce_return */
//      $commerce_return = RMA::create([
//        'name' => $name,
//        // TODO CHECK IT For test! - type must be different!!
//        'type' => 'default',
//        'commerce_return_items' => $commerce_return_items,
//      ]);
//      $commerce_return->save();
//
//      $this->messenger()->addMessage($this->t('Order @label is returning.', [
//        '@label' => $order->label(),
//      ]));
//    }
//  }


//    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
//    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
//    $cart = $order_storage->load($this->view->argument['order_id']->getValue());
//    $quantities = $form_state->getValue($this->options['id'], []);
//    $save_cart = FALSE;
//    foreach ($quantities as $row_index => $quantity) {
//      if (!is_numeric($quantity) || $quantity < 0) {
//        // The input might be invalid if the #required or #min attributes
//        // were removed by an alter hook.
//        continue;
//      }
//      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
//      $order_item = $this->getEntity($this->view->result[$row_index]);
//      if ($order_item->getQuantity() == $quantity) {
//        // The quantity hasn't changed.
//        continue;
//      }
//
//      if ($quantity > 0) {
//        $order_item->setQuantity($quantity);
//        $this->cartManager->updateOrderItem($cart, $order_item, FALSE);
//      }
//      else {
//        // Treat quantity "0" as a request for deletion.
//        $this->cartManager->removeOrderItem($cart, $order_item, FALSE);
//      }
//      $save_cart = TRUE;
//    }
//
//    if ($save_cart) {
//      $cart->save();
//      if (!empty($triggering_element['#show_update_message'])) {
//        $this->messenger->addMessage($this->t('Your shopping cart has been updated.'));
//      }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

}
