<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
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
      if ($this->options['allow_decimal']) {
        $form_display = commerce_get_entity_display('commerce_order_item', $order_item->bundle(), 'form');
        $quantity_component = $form_display->getComponent('quantity');
        $step = $quantity_component['settings']['step'];
        // @todo Fix logic and document.
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
        '#max' => $order_item->getQuantity(),
        '#step' => $step,
        '#required' => TRUE,
      ];
    }

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
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
      $commerce_return_item = $return_item_storage->create([
        'type' => 'default',
        'name' => $order_item->getTitle(),
        'unit_price' => $order_item->getUnitPrice(),
        'confirmed_price' => $order_item->getUnitPrice(),
        'quantity' => $form_state->getValue($this->options['id'])[$row_index],
        'confirmed_quantity' => $form_state->getValue($this->options['id'])[$row_index],
        'order_item' => $order_item->id(),
        'note' => isset($note_handler) ? $form_state->getValue($note_handler['id'])[$row_index] : NULL,
      ]);
      if ($reason) {
        $commerce_return_item->reason = $reason;
      }
      $commerce_return_item->save();
      $items_to_return[] = $commerce_return_item->id();
    }

    if ($items_to_return) {
      $address = $form_state->getValue('billing_information');
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
        ->set('address', $address)
        ->save();

      $order_number = $order->getOrderNumber();
      if (empty($order_number)) {
        $order_number = $order->id();
      }

      // Create new Return object.
      /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $commerce_return */
      $commerce_return = $commerce_return_storage->create([
        'name' => $this->t('Return for order !order', ['!order' => $order_number]),
        'type' => 'default',
        'return_items' => $items_to_return,
        'billing_profile' => $billing_profile,
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
        $this->messenger->addStatus($this->t('Order @label is returning.', [
          '@label' => $order->label(),
        ]));
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

}
