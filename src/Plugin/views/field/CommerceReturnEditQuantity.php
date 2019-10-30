<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use CommerceGuys\Intl\Calculator;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

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
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')
      ->load($this->view->argument['order_id']->getValue());

    //    $form['#attached'] = [
    //      'library' => ['commerce_cart/cart_form'],
    //    ];
    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->getEntity($row);
      $form[$this->options['id']][$row_index] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#title_display' => 'invisible',
        '#size' => 4,
        '#required' => TRUE,
      ] + $this->getMaxQuantity($order_item, $order);
    }

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();
    $billing_address = $billing_profile->get('address')->getValue();
    $billing_address = array_shift($billing_address);
    $form['actions']['another_location_billing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you need to be billed to another location'),
      '#weight' => 0,
    ];
    $form['actions']['billing_information'] = [
      '#type' => 'address',
      '#title' => $this->t('Billing information'),
      '#default_value' => [
        'given_name' => $billing_address['given_name'],
        'family_name' => $billing_address['family_name'],
        'organization' => $billing_address['organization'],
        'address_line1' => $billing_address['address_line1'],
        'address_line2' => $billing_address['address_line2'],
        'postal_code' => $billing_address['postal_code'],
        'locality' => $billing_address['locality'],
        'administrative_area' => $billing_address['administrative_area'],
        'country_code' => $billing_address['country_code'],
        'langcode' => $billing_address['langcode'],
      ],
      '#weight' => 0,
      '#states' => array(
        'invisible' => array(
          ':input[name="another_location_billing"]' => array('checked' => FALSE),
        ),
      ),
    ];

    if ($order->hasField('shipments')) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = $order->get('shipments')->entity;
      $shipping_address = $shipment->getShippingProfile()->get('address')->getValue();
      $shipping_address = array_shift($shipping_address);

      $form['actions']['another_location_shipping'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check if you need to be shipped to another location'),
        '#weight' => 0,
      ];
      $form['actions']['shipping_information'] = [
        '#type' => 'address',
        '#title' => $this->t('Shipping information'),
        '#default_value' => [
          'given_name' => $shipping_address['given_name'],
          'family_name' => $shipping_address['family_name'],
          'organization' => $shipping_address['organization'],
          'address_line1' => $shipping_address['address_line1'],
          'address_line2' => $shipping_address['address_line2'],
          'postal_code' => $shipping_address['postal_code'],
          'locality' => $shipping_address['locality'],
          'administrative_area' => $shipping_address['administrative_area'],
          'country_code' => $shipping_address['country_code'],
          'langcode' => $shipping_address['langcode'],
        ],
        '#weight' => 0,
        '#states' => array(
          'invisible' => array(
            ':input[name="another_location_shipping"]' => array('checked' => FALSE),
          ),
        ),
      ];
    }
    $form['actions']['submit']['#rma_return'] = TRUE;
    $form['actions']['submit']['#show_update_message'] = TRUE;
    // Replace the form submit button label.
    $form['actions']['submit']['#value'] = $this->t('Return');
    $form['actions']['submit']['#weight'] = 1;
    $destination = \Drupal::request()->server->get('HTTP_REFERER');
    $form_state->set('destination', $destination);
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
    if (empty($triggering_element['#rma_return'])) {
      // Don't run when the "Remove" or "Empty cart" buttons are pressed.
      return;
    }

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $return_item_storage = $this->entityTypeManager->getStorage('commerce_return_item');
    $commerce_return_storage = $this->entityTypeManager->getStorage('commerce_return');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($this->view->argument['order_id']->getValue());
    $quantities = $form_state->getValue($this->options['id'], []);

    // Find - if we connect handler for RMA reason field.
    $handlers = $this->view->getHandlers('field');

    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_reason') {
        $reason_handler = $handler;
        break;
      }
    }
    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_files') {
        $attachments_handler = $handler;
        break;
      }
    }
    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_expected_resolution') {
        $expected_resolution_handler = $handler;
        break;
      }
    }
    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_note') {
        $note_handler = $handler;
        break;
      }
    }

    // Create list of new return item objects.
    $items_to_return = [];
    foreach ($quantities as $row_index => $quantity) {
      if (!is_numeric($quantity) || $quantity <= 0) {
        // The input might be invalid if the #required or #min attributes
        // were removed by an alter hook.
        continue;
      }
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->getEntity($this->view->result[$row_index]);
      if ($order_item->getQuantity() < $quantity) {
        // The quantity hasn't changed.
        continue;
      }

      $reason = isset($reason_handler) ? $form_state->getValue($reason_handler['id'])[$row_index] : NULL;
      $expected_resolution = isset($expected_resolution_handler) ? $form_state->getValue($expected_resolution_handler['id'])[$row_index] : NULL;
      $attachments = isset($attachments_handler) ? $form_state->getValue($attachments_handler['id'])[$row_index] : NULL;
      $commerce_return_item = $return_item_storage->create([
        'type' => 'default',
        'name' => $order_item->getTitle(),
        'unit_price' => $order_item->getUnitPrice(),
        'confirmed_price' => $order_item->getUnitPrice(),
        'quantity' => $form_state->getValue($this->options['id'])[$row_index],
        'confirmed_quantity' => 0,
        'order_item' => $order_item->id(),
        'note' => isset($note_handler) ? $form_state->getValue($note_handler['id'])[$row_index] : NULL,
      ]);
      if ($reason) {
        $commerce_return_item->reason = $reason;
      }
      if ($expected_resolution) {
        $commerce_return_item->expected_resolution = $expected_resolution;
      }
      $commerce_return_item->save();
      if ($attachments) {
        foreach ($attachments as $attachment) {
          $file = File::load( $attachment );
          $file->setPermanent();
          $file->save();
          $file_usage->add($file, 'commerce_rma', 'commerce_return_item', $commerce_return_item->id());
        }
        $commerce_return_item->field_attachments = $attachments;
        $commerce_return_item->save();
      }
      $items_to_return[] = $commerce_return_item->id();
    }

    if ($items_to_return) {
      $billing_address = $form_state->getValue('billing_information');
      /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
      $profile = $order->getBillingProfile();
      if (!$profile) {
        $profile_storage = $this->entityTypeManager->getStorage('profile');
        $billing_profile = $profile_storage->create([
          'type' => 'customer',
          'uid' => $order->getCustomerId(),
        ]);
      }
      else  {
        $billing_profile = $profile->createDuplicate();
      }
      $billing_profile
        ->enforceIsNew(TRUE)
        ->set('address', $billing_address)
        ->save();

      $shipping_address = $form_state->getValue('shipping_information');
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = $order->get('shipments')->entity;
      $shipping_profile = $shipment->getShippingProfile();

      if (!$shipping_profile) {
        $profile_storage = $this->entityTypeManager->getStorage('profile');
        $shipping_profile = $profile_storage->create([
          'type' => 'customer',
          'uid' => $order->getCustomerId(),
        ]);
      }
      else  {
        $shipping_profile = $shipping_profile->createDuplicate();
      }
      $shipping_profile
        ->enforceIsNew(TRUE)
        ->set('address', $shipping_address)
        ->save();

      $order_number = $order->getOrderNumber();
      if (empty($order_number)) {
        $order_number = $order->id();
      }

      // Create new Return object.
      /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $commerce_return */
      $commerce_return = $commerce_return_storage->create([
        'name' => $this->t('Return for order :order', [':order' => $order_number]),
        'type' => 'default',
        'return_items' => $items_to_return,
        'billing_profile' => $billing_profile,
        'shipping_profile' => $shipping_profile,
        'order_id' => $order->id(),
        'user_id' => $order->getCustomerId(),
      ]);
      $commerce_return->set('total_price', $order->getTotalPaid());
      $commerce_return->save();
      $destination = $form_state->get('destination');
      parse_url($destination);
      $destination = UrlHelper::parse($destination)['query']['destination'];
      if (!empty($destination)) {
        $form_state->setRedirectUrl(Url::fromUserInput($destination));
      }
      if (!empty($triggering_element['#show_update_message'])) {
        $this->messenger->addStatus($this->t('Order @label - return requested.', [
          '@label' => $order->label(),
        ]));
      }

    }
    $logStorage = $this->entityTypeManager->getStorage('commerce_log');

    $logStorage->generate($order, 'order_return_added', [
      'return_id' => $commerce_return->id(),
      'user' => \Drupal::currentUser()->getDisplayName(),
    ])->save();
    $logStorage->generate($commerce_return, 'return_added', [
      'return_id' => $commerce_return->id(),
      'user' => \Drupal::currentUser()->getDisplayName(),
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  protected function getMaxQuantity(OrderItemInterface $order_item, OrderInterface $order) {
    $step = 1;
    if ($this->options['allow_decimal']) {
      $form_display = commerce_get_entity_display('commerce_order_item', $order_item->bundle(), 'form');
      $quantity_component = $form_display->getComponent('quantity');
      $step = $quantity_component['settings']['step'];
    }
    $data = [
      '#default_value' => 0,
      '#min' => 0,
      '#max' => $order_item->getQuantity(),
      '#step' => $step,
    ];
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface[] $returns */
    $returns = $order->get('returns')->referencedEntities();
    $count = '0';
    $accepted_states = [
      'approved',
      'completed',
    ];
    $skip_return_states = ['canceled', 'rejected'];

    foreach ($returns as $return_id => $return) {
      if (in_array($return->getState()->value, $skip_return_states)) {
        unset($returns[$return_id]);
      }
    }

    foreach ($returns as $return) {
      $return_items = $return->getItems();
      $count = '0';
      foreach ($return_items as $return_item) {
        if ($return_item->getOrderItem()->id() == $order_item->id()){
          $item_quantity = $return->getState()->value =='draft' ? $return_item->getQuantity() : $return_item->getConfirmedTotalQuantity();
          $count = Calculator::add($count, $item_quantity);
        }
      }
    }
    $data['#max'] = Calculator::subtract($order_item->getQuantity(), $count);
    if ($data['#max'] < 0) {
      $data['#max'] = 0;
    }
//    if ($data['#default_value'] > $data['#max']) {
//      $data['#default_value'] = $data['#max'];
//    }
    return $data;
  }

}
