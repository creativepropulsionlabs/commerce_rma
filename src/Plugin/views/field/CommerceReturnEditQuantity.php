<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use CommerceGuys\Intl\Calculator;
use Drupal\commerce\InlineFormManager;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
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
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;


  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The return item storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $returnItemStorage;

  /**
   * The return storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $returnStorage;

  /**
   * Log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

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
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, InlineFormManager $inline_form_manager, FileUsageInterface $file_usage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->inlineFormManager = $inline_form_manager;
    $this->fileUsage = $file_usage;
    $this->orderStorage = $this->entityTypeManager->getStorage('commerce_order');
    $this->returnItemStorage = $this->entityTypeManager->getStorage('commerce_return_item');
    $this->returnStorage = $this->entityTypeManager->getStorage('commerce_return');
    $this->logStorage = $this->entityTypeManager->getStorage('commerce_log');
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
      $container->get('messenger'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('file.usage')
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
   * {@inheritdoc}
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
    $order = $this->orderStorage->load($this->view->argument['order_id']->getValue());

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
    $billing_profile = $order->getBillingProfile()->createDuplicate();
    $billing_inline_form = $this->inlineFormManager->createInstance('customer_profile', [
      'available_countries' => $order->getStore()->getBillingCountries(),
    ], $billing_profile);

    $form['actions']['another_location_billing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you need to be billed to another location'),
      '#weight' => 0,
    ];

    $form['actions']['billing_profile'] = [
      '#parents' => ['actions', 'billing_profile'],
      '#inline_form' => $billing_inline_form,
      '#weight' => 0,
      '#states' => [
        'invisible' => [
          ':input[name="another_location_billing"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['actions']['billing_profile'] = $billing_inline_form->buildInlineForm($form['actions']['billing_profile'], $form_state);

    if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = $order->get('shipments')->entity;
      $shipping_profile = $shipment->getShippingProfile()->createDuplicate();

      $shipping_inline_form = $this->inlineFormManager->createInstance('customer_profile', [
        'available_countries' => $order->getStore()->getBillingCountries(),
      ], $shipping_profile);

      $form['actions']['another_location_shipping'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check if you need to be shipped to another location'),
        '#weight' => 0,
      ];
      $form['actions']['shipping_profile'] = [
        '#parents' => ['actions', 'shipping_profile'],
        '#inline_form' => $shipping_inline_form,
        '#weight' => 0,
        '#states' => [
          'invisible' => [
            ':input[name="another_location_shipping"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['actions']['shipping_profile'] = $shipping_inline_form->buildInlineForm($form['actions']['shipping_profile'], $form_state);
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
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $quantities = $form_state->getValue($this->options['id'], []);
    $total_quantities = 0;
    foreach ($quantities as $row_index => $quantity) {
      $total_quantities += $quantity;
    }
    if ($total_quantities == 0) {
      $form_state->setErrorByName('', $this->emptySelectedMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No Quantity selected.');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#rma_return'])) {
      // Don't run when the "Remove" or "Empty cart" buttons are pressed.
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->load($this->view->argument['order_id']->getValue());
    $quantities = $form_state->getValue($this->options['id'], []);

    $reason_handler = $this->getHandlerById('reason');
    $attachments_handler = $this->getHandlerById('files');
    $expected_resolution_handler = $this->getHandlerById('expected_resolution');
    $note_handler = $this->getHandlerById('note');

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
      $commerce_return_item = $this->returnItemStorage->create([
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
          $this->fileUsage->add($file, 'commerce_rma', 'commerce_return_item', $commerce_return_item->id());
        }
        $commerce_return_item->field_attachments = $attachments;
        $commerce_return_item->save();
      }
      $items_to_return[] = $commerce_return_item->id();
    }

    if ($items_to_return) {
      /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $billing_inline_form */
      $billing_inline_form = $form['actions']['billing_profile']['#inline_form'];
      /** @var \Drupal\profile\Entity\ProfileInterface $shipping_profile */
      $billing_profile = $billing_inline_form->getEntity();

      /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $shipping_inline_form */
      $shipping_inline_form = $form['actions']['shipping_profile']['#inline_form'];
      /** @var \Drupal\profile\Entity\ProfileInterface $shipping_profile */
      $shipping_profile = $shipping_inline_form->getEntity();

      $order_number = $order->getOrderNumber();
      if (empty($order_number)) {
        $order_number = $order->id();
      }

      // Create new Return object.
      /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $commerce_return */
      $commerce_return = $this->returnStorage->create([
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

      foreach ($commerce_return->getItems() as $item_to_return) {
        $this->logStorage->generate($commerce_return, "return_item_added", [
          'product_title' => $item_to_return->label(),
          'quantity' => $item_to_return->getQuantity(),
          'comment' => $item_to_return->get('note')->value,
        ])->save();
        $this->logStorage->generate($order, "order_return_item_added", [
          'product_title' => $item_to_return->label(),
          'quantity' => $item_to_return->getQuantity(),
        ])->save();
      }
    }

    $this->logStorage->generate($order, 'order_return_added', [
      'return_id' => $commerce_return->id(),
    ])->save();
    $this->logStorage->generate($commerce_return, 'return_added', [
      'return_id' => $commerce_return->id(),
    ])->save();
  }

  /**
   * Helper to find if RMA handler is available.
   *
   * @param string $id
   *   Handler id.
   *
   * @return bool
   */
  protected function getHandlerById($id) {
    $handlers = $this->view->getHandlers('field');

    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == "commerce_rma_order_item_edit_{$id}") {
        return $handler;
      }
    }

    return FALSE;
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
